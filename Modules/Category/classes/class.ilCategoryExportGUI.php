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

        if ($this->tree->getDepth($this->obj->getRefId()) >= 4 && ilCust::extendedUserDataAccess()) {
            $this->addFormat('csv', 'Kursteilnehmer/innen exportieren', $this, 'callExportCourseUsers');
        }
    }


    public function callExportCourseUsers()
    {
        $this->ctrl->redirect($this, 'showExportCourseUsersForm');
    }


    public function showExportCourseUsersForm()
    {
        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this, 'show'));
        $form->setTitle($this->lng->txt('fau_export_course_members'));
        $form->setDescription($this->lng->txt('fau_export_course_members_info'));

        $term = new ilSelectInputGUI($this->lng->txt('studydata_semester'), 'term_id');
        $options = $this->dic->fau()->study()->getTermSearchOptions(null, false);
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
        $form->addCommandButton('doExportCourseUsers', $this->lng->txt('export'));
        $form->addCommandButton('listExportFiles', $this->lng->txt('cancel'));

        $this->tpl->setContent($form->getHTML());
    }


    public function doExportCourseUsers()
    {
        $export = new CourseUsersExport(Term::fromString((string) $_GET['term_id']), $this->obj->getRefId());
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