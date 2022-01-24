<?php
// fau: exMultiFeedbackStructure - new confirmation table for teams.

/* Copyright (c) 1998-2011 ILIAS open source, Extended GPL, see docs/LICENSE */


class ilFeedbackConfirmationTable2TeamsGUI extends ilTable2GUI
{
    /**
     * @var ilAccessHandler
     */
    protected $access;

    /**
     * @var ilObjUser
     */
    protected $user;

    /**
     * Constructor
     */
    public function __construct($a_parent_obj, $a_parent_cmd, $a_ass)
    {
        global $DIC;

        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();
        $this->access = $DIC->access();
        $this->user = $DIC->user();
        
        $this->ass = $a_ass;
        $this->setId("exc_mdf_upload_team");
        parent::__construct($a_parent_obj, $a_parent_cmd);
        $this->setLimit(9999);
        $this->setData($this->ass->getMultiFeedbackFiles($this->user->getId()));
        $this->setTitle($this->lng->txt("exc_multi_feedback_files"));
        $this->setSelectAllCheckbox("file[]");
        
        $this->addColumn("", "", "1px", true);
        $this->addColumn($this->lng->txt("exc_team"), "team");
        $this->addColumn($this->lng->txt("folder"), "folder");
        $this->addColumn($this->lng->txt("file"), "file");
        
        $this->setFormAction($this->ctrl->getFormAction($a_parent_obj));
        $this->setRowTemplate("tpl.multi_feedback_confirmation_teams_row.html", "Modules/Exercise");

        $this->addCommandButton("saveMultiFeedback",  $this->lng->txt("save"));
        $this->addCommandButton("cancelMultiFeedback",  $this->lng->txt("cancel"));
    }
    
    /**
     * Fill table row
     */
    protected function fillRow($a_set)
    {
        $names = [];
        foreach ($a_set['members'] as $user) {
            $names[] = $user['lastname'] . ', ' . $user['firstname'];
        }

        $this->tpl->setVariable("TEAM_ID", $a_set["team_id"]);
        $this->tpl->setVariable("TEAM", implode('<br/>', $names) . '<br />(' . $a_set['team_id'] . ')');
        $this->tpl->setVariable("FOLDER", $a_set["folder"]);
        $this->tpl->setVariable("FILE", $a_set["file"]);
        $this->tpl->setVariable("POST_FILE", md5($a_set["folder"] . '/'. $a_set["file"]));
    }
}
