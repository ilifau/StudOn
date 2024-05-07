<?php

use ILIAS\DI\Container;
use FAU\Ilias\CourseUsersExport;
use FAU\Study\Data\Term;
use FAU\Ilias\AbstractExport;

/**
 * fau: campoExport - Container export GUI class
 *
 * @ilCtrl_IsCalledBy ilContainerExportGUI: ilObjCategoryGUI, ilObjCourseGUI, ilObjGroupGUI
 */
class ilContainerExportGUI extends ilExportGUI
{
    protected Container $dic;
    protected ilTree $tree;
    protected ilCtrl $ctrl;
    protected ilLanguage $lng;


    /** @var ilContainer */
    protected $obj;

    /** @var ilGlobalTemplate */
    protected $tpl;
    
    /**
     * @var int[]
     */
    protected $export_ref_ids = [];

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
        
        $this->export_ref_ids = $this->dic->fau()->ilias()->objects()->getPathRefIdsWithCollectedExports($this->obj->getRefId());
    }
    
    public function listExportFiles()
    {
        parent::listExportFiles();
        if ($this->canExportUserData()) {
            $button = ilLinkButton::getInstance();
            $button->setUrl($this->ctrl->getLinkTarget($this, 'showExportCourseUsersForm'));
            $button->setCaption($this->lng->txt('fau_export_course_members'), false);
            $this->dic->toolbar()->addSeparator();
            $this->dic->toolbar()->addButtonInstance($button);
        }
    }


    public function showExportCourseUsersForm()
    {
        if (!$this->canExportUserData()) {
            $this->tpl->setOnScreenMessage('failure', $this->lng->txt('permission_denied'), true);
            $this->ctrl->redirect($this, 'listExportFiles');
        }
        
        switch ($this->obj->getType()) {
            case 'cat':
                $description = $this->lng->txt('fau_export_course_members_cat_info');
                break;
            case 'crs':
                $description = $this->lng->txt('fau_export_course_members_crs_info');
                break;
            case 'grp':
                $description = $this->lng->txt('fau_export_course_members_grp_info');
                break;
            default:
                $description = '';
        }
        
        $form = new ilPropertyFormGUI();
        $form->setPreventDoubleSubmission(false);
        $form->setFormAction($this->ctrl->getFormAction($this, 'show'));
        $form->setTitle($this->lng->txt('fau_export_course_members'));
        $form->setDescription($description);

        $options = [];
        foreach ($this->export_ref_ids as $ref_id) {
            $options[$ref_id] = ilObject::_lookupTitle(ilObject::_lookupObjId($ref_id));
        }
        $base = new ilSelectInputGUI($this->lng->txt('fau_export_course_members_base'), 'base_ref_id');
        $base->setInfo($this->lng->txt('fau_export_course_members_base_info'));
        $base->setOptions($options);
        $form->addItem($base);
        
        $term = new ilSelectInputGUI($this->lng->txt('studydata_semester'), 'term_ids');
        $term->setMulti(true);
        $term->setInfo($this->lng->txt('fau_course_export_term_info'));
        $options = $this->dic->fau()->study()->getTermSearchOptions(null, true);
        $current = $this->dic->fau()->study()->getCurrentTerm()->toString();
        $preferred = $this->dic->fau()->tools()->preferences()->getTermIdsForExports();
        $term->setOptions($options);
        $term->setValue($preferred);
        $form->addItem($term);

        $groups = new ilCheckboxInputGUI($this->lng->txt('fau_export_course_members_with_groups'), 'export_with_groups');
        $groups->setInfo($this->lng->txt('fau_export_course_members_with_groups_info'));
        $groups->setChecked($this->dic->fau()->tools()->preferences()->getExportWithGroups());
        $form->addItem($groups);

        $form->addCommandButton('doExportCourseUsers', $this->lng->txt('export'));
        $form->addCommandButton('listExportFiles', $this->lng->txt('cancel'));

        $this->tpl->setContent($form->getHTML());
    }


    public function doExportCourseUsers()
    {
        if (!$this->canExportUserData()) {
            $this->tpl->setOnScreenMessage('failure', $this->lng->txt('permission_denied'), true);
            $this->ctrl->redirect($this, 'listExportFiles');
        }

        $parameters = $this->dic->http()->request()->getParsedBody();
        if (!is_array($parameters)) {
            $this->tpl->setOnScreenMessage('failure', $this->lng->txt('wrong_request'), true);
            $this->ctrl->redirect($this, 'listExportFiles');
        }
        
        $base_ref_id = (int) ($parameters['base_ref_id'] ?? 0);
        if (!in_array($base_ref_id, $this->export_ref_ids)) {
            $this->tpl->setOnScreenMessage('failure', $this->lng->txt('wrong_request'), true);
            $this->ctrl->redirect($this, 'listExportFiles');
        }
        
        $term_ids = [];
        $terms = [];
        foreach ((array) ($parameters['term_ids'] ?? []) as $term_id) {
            $term_ids[] = (string) $term_id;
            if (empty($term_id)) {
                $terms[] = null;
            }
            else {
                $terms[] = Term::fromString((string) $term_id);
            }
        }
        $this->dic->fau()->tools()->preferences()->setTermIdsForExports($term_ids);
        $this->dic->fau()->tools()->preferences()->setExportWithGroups((bool) ($parameters['export_with_groups'] ?? false));

        $this->listExportFiles();

        $filter_obj_id = (CourseUsersExport::supportsUsersFilterObjectType($this->obj->getType()) ? $this->obj->getId() : null);
        $export = new CourseUsersExport($base_ref_id, $terms, (bool) ($parameters['export_with_groups'] ?? false));
        $file = $export->exportCoursesUsers(AbstractExport::TYPE_EXCEL, $filter_obj_id);

        if (is_file($file)) {
            ilUtil::deliverFile($file, basename($file), '', false, true);
        }
        else {
            $this->tpl->setOnScreenMessage('failure', $this->lng->txt('fau_export_course_members_failed'), true);
            $this->ctrl->redirect($this, 'listExportFiles');
        }
    }
    
    /**
     * Check if the user can export collected membership data at this position
     */
    protected function canExportUserData() : bool
    {
        if (empty($this->export_ref_ids)) {
            return false;
        }
        
        if (!ilCust::extendedUserDataAccess()) {
            return false;
        }
        
        if (CourseUsersExport::supportsUsersFilterObjectType($this->obj->getType())) {
            return $this->dic->access()->checkAccess('manage_members','', $this->obj->getRefId());
        }
        
        return true;
    }

    
    /**
     * Check if the user can export collected course statistics at this position
     */
    protected function canExportStatistics() : bool
    {
        return (count($this->export_ref_ids) > 0);
    }
}