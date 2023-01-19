<?php

use ILIAS\DI\Container;
use FAU\Ilias\CourseUsersExport;
use FAU\Study\Data\Term;

/**
 * fau: campoExport - Category export GUI class
 *
 * @ilCtrl_IsCalledBy ilCategoryExportGUI: ilObjCategoryGUI
 */
class ilCategoryExportGUI extends ilExportGUI
{
    protected Container $dic;
    protected ilTree $tree;
    protected ilCtrl $ctrl;
    protected ilLanguage $lng;


    /** @var ilObjCategory */
    protected $obj;


    /**
     * Constructor
     */
    public function __construct($a_parent_gui, $a_main_obj = null)
    {
        global $DIC;
        $this->dic = $DIC;
        $this->tree = $DIC->repositoryTree();
        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();

        parent::__construct($a_parent_gui, $a_main_obj);
        $this->addFormat('xml');
    }

    public function listExportFiles()
    {
        parent::listExportFiles();
        if ($this->tree->getDepth($this->obj->getRefId()) >= 4 && ilCust::extendedUserDataAccess()) {

            $button = ilLinkButton::getInstance();
            $button->setUrl($this->ctrl->getLinkTarget($this, 'showExportCourseUsersForm'));
            $button->setCaption($this->lng->txt('fau_export_course_members'), false);
            $this->dic->toolbar()->addButtonInstance($button);
        }
    }


    public function showExportCourseUsersForm()
    {
        $form = new ilPropertyFormGUI();
        $form->setPreventDoubleSubmission(false);
        $form->setFormAction($this->ctrl->getFormAction($this, 'show'));
        $form->setTitle($this->lng->txt('fau_export_course_members'));
        $form->setDescription($this->lng->txt('fau_export_course_members_info'));

        $term = new ilSelectInputGUI($this->lng->txt('studydata_semester'), 'term_id');
        $term->setInfo($this->lng->txt('fau_course_export_term_info'));
        $options = $this->dic->fau()->study()->getTermSearchOptions(null, true);
        $current = $this->dic->fau()->study()->getCurrentTerm()->toString();
        $preferred = $this->dic->fau()->tools()->preferences()->getTermIdForExports();
        $term->setOptions($options);
        if (isset($options[$preferred])) {
            $term->setValue($preferred);
        }
        elseif (isset($options[$current])) {
            $term->setValue($current);
        }
        else {
            $term->setValue((string) current($options));
        }
        $form->addItem($term);

        $groups = new ilCheckboxInputGUI($this->lng->txt('fau_export_with_group_members'), 'export_with_groups');
        $groups->setInfo($this->lng->txt('fau_export_with_group_members_info'));
        $groups->setChecked($this->dic->fau()->tools()->preferences()->getExportWithGroups());
        $form->addItem($groups);

        $form->addCommandButton('doExportCourseUsers', $this->lng->txt('export'));
        $form->addCommandButton('listExportFiles', $this->lng->txt('cancel'));

        $this->tpl->setContent($form->getHTML());
    }


    public function doExportCourseUsers()
    {
        $this->dic->fau()->tools()->preferences()->setTermIdForExports((string) $_POST['term_id']);
        $this->dic->fau()->tools()->preferences()->setExportWithGroups((bool) $_POST['export_with_groups']);

        $this->listExportFiles();

        $export = new CourseUsersExport($this->obj->getRefId(), Term::fromString((string) $_POST['term_id']), (bool) $_POST['export_with_groups']);
        $file = $export->exportCoursesUsers();

        if (is_file($file)) {
            ilUtil::deliverFile($file, basename($file), '', false, true);
        }
        else {
            ilUtil::sendFailure($this->lng->txt('fau_export_course_members_failed'), true);
            $this->ctrl->redirect($this, 'listExportFiles');
        }
    }
}