<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once "./Services/Object/classes/class.ilObjectGUI.php";

/**
* Class ilObjCourseGroupingGUI
*
* @author your name <your email>
* @version $Id$
*
 * @ilCtrl_Calls ilObjCourseGroupingGUI: ilPropertyFormGUI
*/
class ilObjCourseGroupingGUI
{
    public $content_obj;
    public $tpl;
    public $ctrl;
    public $lng;
    
    /**
     * Constructor
     * @access public
     */
    public function __construct($content_obj, $a_obj_id = 0)
    {
        global $DIC;

        $tpl = $DIC['tpl'];
        $ilCtrl = $DIC['ilCtrl'];
        $lng = $DIC['lng'];
        $ilObjDataCache = $DIC['ilObjDataCache'];

        $this->tpl = $tpl;
        $this->ctrl = $ilCtrl;
        $this->lng = $lng;

        $this->type = "crsg";
        $this->content_obj = $content_obj;
        $this->content_type = $ilObjDataCache->lookupType($this->content_obj->getId());

        $this->id = $a_obj_id;
        $this->ctrl->saveParameter($this, 'obj_id');

        $this->__initGroupingObject();
    }
    
    public function executeCommand()
    {
        // fau: groupingSelector - forward command to property form
        global $DIC;
        $class = $DIC->ctrl()->getNextClass($this);
        switch ($class) {
            case "ilpropertyformgui":
                $form = $this->initForm(false);
                $DIC->ctrl()->forwardCommand($form);
                return;
        }
        // fau.

        $ilTabs = $DIC['ilTabs'];

        $ilTabs->setTabActive('crs_groupings');

        $cmd = $this->ctrl->getCmd();
        if (!$cmd = $this->ctrl->getCmd()) {
            $cmd = "edit";
        }
        $this->$cmd();
    }

    // PRIVATE
    public function __initGroupingObject()
    {
        include_once './Modules/Course/classes/class.ilObjCourseGrouping.php';

        $this->grp_obj = new ilObjCourseGrouping($this->id);
    }

    public function getContentType()
    {
        return $this->content_type;
    }

    public function listGroupings()
    {
        global $DIC;

        $ilErr = $DIC['ilErr'];
        $ilAccess = $DIC['ilAccess'];
        $ilToolbar = $DIC['ilToolbar'];
        $tpl = $DIC['tpl'];

        if (!$ilAccess->checkAccess('write', '', $this->content_obj->getRefId())) {
            $ilErr->raiseError($this->lng->txt('permission_denied'), $ilErr->MESSAGE);
        }
        
        $ilToolbar->addButton(
            $this->lng->txt('crs_add_grouping'),
            $this->ctrl->getLinkTarget($this, 'create')
        );

        include_once 'Modules/Course/classes/class.ilCourseGroupingTableGUI.php';
        $table = new ilCourseGroupingTableGUI($this, 'listGroupings', $this->content_obj);
        
        $tpl->setContent($table->getHTML());
    }

    // fau: limitSub - new function addWaitingMembers()
    /**
     * Add waiting members to the grouped objects
     * this calls their handleAutoFill function()
     */
    public function addWaitingMembers()
    {
        /** @var ilAccessHandler $ilAccess */
        global $lng, $ilAccess;

        $sum = 0;
        $message = "";
        $grouping = new ilObjCourseGrouping((int) $_GET['obj_id']);

        foreach ($grouping->getAssignedItems() as $condition) {
            if ($ilAccess->checkAccess('write', '', $condition['target_ref_id'], $condition['target_type'])) {
                if ($object = ilObjectFactory::getInstanceByRefId($condition['target_ref_id'])) {
                    // call manual auto fill
                    $added = $object->handleAutoFill(true);
                    if (!empty($added)) {
                        $list = "";
                        foreach ($added as $user_id) {
                            $list .= ", " . ilObjUser::_lookupLogin($user_id);
                        }
                        $message .= "<br />" . $object->getTitle() . ': ' . $list;
                        $sum += count($added);
                    }
                }
            }
        }

        if ($sum == 0) {
            ilUtil::sendFailure($this->lng->txt('sub_no_member_added'));
        } else {
            ilUtil::sendSuccess(sprintf($lng->txt($sum == 1 ? 'sub_added_member' : 'sub_added_members'), $sum) . $message);
        }

        $this->listGroupings();
    }
    // fau.

    public function askDeleteGrouping()
    {
        global $DIC;

        $ilErr = $DIC['ilErr'];
        $ilAccess = $DIC['ilAccess'];
        $tpl = $DIC['tpl'];

        if (!$ilAccess->checkAccess('write', '', $this->content_obj->getRefId())) {
            $ilErr->raiseError($this->lng->txt('permission_denied'), $ilErr->MESSAGE);
        }

        if (empty($_POST['grouping'])) {
            ilUtil::sendFailure($this->lng->txt('crs_grouping_select_one'));
            $this->listGroupings();
            
            return false;
        }
        // fau: groupingSelector - check if groupings can be deleted
        foreach ($_POST['grouping'] as $grouping_id) {
            if (!$this->allItemsWritable($grouping_id)) {
                ilUtil::sendFailure($this->lng->txt('groupings_assigned_obj_not_writable_' . $this->content_obj->getType()));
                $this->listGroupings();
                return false;
            }
        }
        // fau.

        // display confirmation message
        include_once("./Services/Utilities/classes/class.ilConfirmationGUI.php");
        $cgui = new ilConfirmationGUI();
        $cgui->setFormAction($this->ctrl->getFormAction($this));
        $cgui->setHeaderText($this->lng->txt("crs_grouping_delete_sure"));
        $cgui->setCancel($this->lng->txt("cancel"), "listGroupings");
        $cgui->setConfirm($this->lng->txt("delete"), "deleteGrouping");

        // list objects that should be deleted
        foreach ($_POST['grouping'] as $grouping_id) {
            $tmp_obj = new ilObjCourseGrouping($grouping_id);
            $cgui->addItem("grouping[]", $grouping_id, $tmp_obj->getTitle());
        }

        $tpl->setContent($cgui->getHTML());
    }

    public function deleteGrouping()
    {
        global $DIC;

        $ilErr = $DIC['ilErr'];
        $ilAccess = $DIC['ilAccess'];

        if (!$ilAccess->checkAccess('write', '', $this->content_obj->getRefId())) {
            $ilErr->raiseError($this->lng->txt('permission_denied'), $ilErr->MESSAGE);
        }
        // fau: groupingSelector - check if groupings can be deleted
        foreach ($_POST['grouping'] as $grouping_id) {
            if (!$this->allItemsWritable($grouping_id)) {
                $ilErr->raiseError($this->lng->txt('permission_denied'), $ilErr->MESSAGE);
            }
        }
        // fau.

        foreach ($_POST['grouping'] as $grouping_id) {
            $tmp_obj = new ilObjCourseGrouping((int) $grouping_id);
            $tmp_obj->delete();
        }
        
        ilUtil::sendSuccess($this->lng->txt('crs_grouping_deleted'), true);
        $this->ctrl->redirect($this, 'listGroupings');
    }

    public function create($a_form = null)
    {
        global $DIC;

        $ilErr = $DIC['ilErr'];
        $ilAccess = $DIC['ilAccess'];
        $tpl = $DIC['tpl'];
        
        if (!$ilAccess->checkAccess('write', '', $this->content_obj->getRefId())) {
            $ilErr->raiseError($this->lng->txt('permission_denied'), $ilErr->MESSAGE);
        }
        
        if (!$a_form) {
            $a_form = $this->initForm(true);
        }
        
        $tpl->setContent($a_form->getHTML());
    }
    
    public function initForm($a_create)
    {
        include_once "Services/Form/classes/class.ilPropertyFormGUI.php";
        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this));
        
        $title = new ilTextInputGUI($this->lng->txt('title'), 'title');
        $title->setRequired(true);
        $form->addItem($title);
        
        $desc = new ilTextAreaInputGUI($this->lng->txt('description'), 'description');
        $form->addItem($desc);
        
        $options = array('login' => 'login',
                         'email' => 'email',
                         'matriculation' => 'matriculation');

        foreach ($options as $value => $caption) {
            $options[$value] = $this->lng->txt($caption);
        }
        $uniq = new ilSelectInputGUI($this->lng->txt('unambiguousness'), 'unique');
        $uniq->setRequired(true);
        $uniq->setOptions($options);
        $form->addItem($uniq);

        // fau: groupingSelector - add a repository picker to the form
        $selector = new ilRepositorySelector2InputGUI($this->lng->txt('groupings_assigned_obj_' . $this->getContentType()), 'items', true);
        /** @var ilRepositorySelectorExplorerGUI $explorer */
        $explorer = $selector->explorer_gui;
        $explorer->setSelectableTypes([$this->getContentType()]);
        $explorer->setWriteRequired(true);
        $selector->setInfo($this->lng->txt('groupings_assigned_obj_info_' . $this->getContentType()));
        $form->addItem($selector);

        if ($a_create) {
            $title->setValue($this->lng->txt('groupings_of') . ': ' . $this->content_obj->getTitle());
            $selector->setValue([$this->content_obj->getRefId()]);
            $form->setTitle($this->lng->txt('crs_add_grouping'));
            $form->addCommandButton('add', $this->lng->txt('btn_add'));
        } else {
            $grouping = new ilObjCourseGrouping($_REQUEST['obj_id']);
            $title->setValue($grouping->getTitle());
            $desc->setValue($grouping->getDescription());
            $uniq->setValue($grouping->getUniqueField());

            // assignments
            $items = array();
            foreach ($grouping->getAssignedItems() as $cond_data) {
                $items[] = $cond_data['target_ref_id'];
            }
            $selector->setValue($items);
            
            $form->setTitle($this->lng->txt('edit_grouping'));
            $form->addCommandButton('update', $this->lng->txt('save'));
        }
        // fau.

        $form->addCommandButton('listGroupings', $this->lng->txt('cancel'));
        
        return $form;
    }

    public function add()
    {
        $form = $this->initForm(true);
        if ($form->checkInput()) {
            $this->grp_obj->setTitle($form->getInput('title'));
            $this->grp_obj->setDescription($form->getInput('description'));
            $this->grp_obj->setUniqueField($form->getInput('unique'));
            
            if ($this->grp_obj->create($this->content_obj->getRefId(), $this->content_obj->getId())) {
                // fau: groupingSelector - assign items when grouping is added
                $this->assignItems($this->grp_obj, $_POST['items']);
                // fau.
                ilUtil::sendSuccess($this->lng->txt('crs_grp_added_grouping'), true);
            } else {
                ilUtil::sendFailure($this->lng->txt('crs_grp_err_adding_grouping'), true);
            }
            
            $this->ctrl->redirect($this, 'listGroupings');
        }

        $form->setValuesByPost();
        $this->create($form);
    }
    
    public function edit($a_form = null)
    {
        global $DIC;

        $ilErr = $DIC['ilErr'];
        $ilAccess = $DIC['ilAccess'];
        $tpl = $DIC['tpl'];

        if (!$ilAccess->checkAccess('write', '', $this->content_obj->getRefId())) {
            $ilErr->raiseError($this->lng->txt('permission_denied'), $ilErr->MESSAGE);
        }

        // fau: groupingSelector - check if all assigned objects are writeable
        if (!$this->allItemsWritable($_REQUEST['obj_id'])) {
            ilUtil::sendFailure($this->lng->txt('groupings_assigned_obj_not_writable_' . $this->content_obj->getType()), true);
            $this->ctrl->redirect($this, 'listGroupings');
        }
        // fau.
        
        if (!$a_form) {
            $a_form = $this->initForm(false);
        }
        
        $tpl->setContent($a_form->getHTML());
    }

    public function update()
    {
        global $DIC;

        $ilErr = $DIC['ilErr'];
        $ilAccess = $DIC['ilAccess'];
        $ilObjDataCache = $DIC['ilObjDataCache'];
        
        if (!$ilAccess->checkAccess('write', '', $this->content_obj->getRefId())) {
            $ilErr->raiseError($this->lng->txt('permission_denied'), $ilErr->MESSAGE);
        }
        // fau: groupingSelector - check if all assigned objects are writeable
        if (!$this->allItemsWritable($_REQUEST['obj_id'])) {
            $ilErr->raiseError($this->lng->txt('permission_denied'), $ilErr->MESSAGE);
        }
        // fau.

        $form = $this->initForm(false);
        if ($form->checkInput()) {
            $tmp_grouping = new ilObjCourseGrouping($_REQUEST['obj_id']);
            $tmp_grouping->setTitle($form->getInput('title'));
            $tmp_grouping->setDescription($form->getInput('description'));
            $tmp_grouping->setUniqueField($form->getInput('unique'));
            $tmp_grouping->update();

            // fau: groupingSelector - assign items when grouping is updated
            $this->assignItems($tmp_grouping, $_POST['items']);
            // fau.
            ilUtil::sendSuccess($this->lng->txt('settings_saved'), true);
            $this->ctrl->redirect($this, 'listGroupings');
        }
        
        $form->setValuesByPost();
        $this->edit($form);
    }

    // fau: groupingSelector - new function alItemsWritable()
    /**
     * Cceck if all items of a grouping are writable
     * @param int $obj_id
     * @return bool
     */
    protected function allItemsWritable($obj_id)
    {
        global $DIC;

        $grouping = new ilObjCourseGrouping($obj_id);
        foreach ($grouping->getAssignedItems() as $cond_data) {
            $ref_id = $cond_data['target_ref_id'];

            if (!ilObject::_isInTrash($ref_id) && !$DIC->access()->checkAccess('write', '', $ref_id)) {
                return false;
            }
        }
        return true;
    }
    // fau.


    public function selectCourse()
    {
        global $DIC;

        $ilErr = $DIC['ilErr'];
        $ilAccess = $DIC['ilAccess'];
        $tpl = $DIC['tpl'];
        $ilTabs = $DIC['ilTabs'];

        if (!$ilAccess->checkAccess('write', '', $this->content_obj->getRefId())) {
            $ilErr->raiseError($this->lng->txt('permission_denied'), $ilErr->MESSAGE);
        }

        if (!$_GET['obj_id']) {
            ilUtil::sendFailure($this->lng->txt('crs_grp_no_grouping_id_given'));
            $this->listGroupings();
            return false;
        }
        
        $ilTabs->clearTargets();
        $ilTabs->setBackTarget(
            $this->lng->txt('back'),
            $this->ctrl->getLinkTarget($this, 'edit')
        );

        $tmp_grouping = new ilObjCourseGrouping((int) $_GET['obj_id']);
        
        include_once 'Modules/Course/classes/class.ilCourseGroupingAssignmentTableGUI.php';
        $table = new ilCourseGroupingAssignmentTableGUI($this, 'selectCourse', $this->content_obj, $tmp_grouping);
        
        $tpl->setContent($table->getHTML());
        
        return true;
    }

    public function assignCourse()
    {
        global $DIC;

        $ilErr = $DIC['ilErr'];
        $ilAccess = $DIC['ilAccess'];
        $ilObjDataCache = $DIC['ilObjDataCache'];
        $tree = $DIC['tree'];
        $ilUser = $DIC['ilUser'];

        if (!$ilAccess->checkAccess('write', '', $this->content_obj->getRefId())) {
            $ilErr->raiseError($this->lng->txt('permission_denied'), $ilErr->MESSAGE);
        }

        if (!$_GET['obj_id']) {
            $this->listGroupings();
            return false;
        }
    
        // delete all existing conditions
        include_once './Services/Conditions/classes/class.ilConditionHandler.php';
        $condh = new ilConditionHandler();
        $condh->deleteByObjId((int) $_GET['obj_id']);

        $added = 0;
        $container_ids = is_array($_POST['crs_ids']) ? $_POST['crs_ids'] : array();
        foreach ($container_ids as $course_ref_id) {
            $tmp_crs = ilObjectFactory::getInstanceByRefId($course_ref_id);
            $tmp_condh = new ilConditionHandler();
            $tmp_condh->enableAutomaticValidation(false);

            $tmp_condh->setTargetRefId($course_ref_id);
            $tmp_condh->setTargetObjId($tmp_crs->getId());
            $tmp_condh->setTargetType($this->getContentType());
            $tmp_condh->setTriggerRefId(0);
            $tmp_condh->setTriggerObjId($this->id);
            $tmp_condh->setTriggerType('crsg');
            $tmp_condh->setOperator('not_member');
            $tmp_condh->setValue($this->grp_obj->getUniqueField());

            if (!$tmp_condh->checkExists()) {
                $tmp_condh->storeCondition();
                ++$added;
            }
        }
        
        ilUtil::sendSuccess($this->lng->txt('settings_saved'), true);
        $this->ctrl->redirect($this, 'edit');
    }

    // fau: groupingSelector - new function assignItems()
    /**
     * Assign items to a grouping
     *
     * @param ilObjCourseGrouping $grpObj
     * @param int[] $ref_ids
     */
    protected function assignItems(ilObjCourseGrouping $grpObj, $ref_ids = [])
    {
        global $DIC;

        // delete all existing conditions
        $condh = new ilConditionHandler();
        $condh->deleteByObjId($grpObj->getId());

        // create the new condition
        $rejected = [];
        foreach ($ref_ids as $ref_id) {
            $ref_id = (int) $ref_id;
            $obj_id = ilObject::_lookupObjId($ref_id);
            $type = ilObject::_lookupType($obj_id);

            if ($type != $this->getContentType() || !$DIC->access()->checkAccess('write', '', $ref_id)) {
                $rejected[] = ilObject::_lookupTitle($obj_id);
                continue;
            }

            $tmp_condh = new ilConditionHandler();
            $tmp_condh->enableAutomaticValidation(false);

            $tmp_condh->setTargetRefId($ref_id);
            $tmp_condh->setTargetObjId($obj_id);
            $tmp_condh->setTargetType($this->getContentType());
            $tmp_condh->setTriggerRefId(0);
            $tmp_condh->setTriggerObjId($grpObj->getId());
            $tmp_condh->setTriggerType('crsg');
            $tmp_condh->setOperator('not_member');
            $tmp_condh->setValue($grpObj->getUniqueField());

            if (!$tmp_condh->checkExists()) {
                $tmp_condh->storeCondition();
            }
        }

        if (!empty($rejected)) {
            ilUtil::sendInfo($this->lng->txt('permission_denied_for') . '<br />' . implode('<br />', $rejected), true);
        }
    }
    // fau.
} // END class.ilObjCourseGrouping
