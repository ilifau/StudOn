<?php
// fau: lmQStat - new table for user list.

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Table/classes/class.ilTable2GUI.php");
include_once("./Modules/TestQuestionPool/classes/class.assQuestion.php");
include_once("./Modules/LearningModule/classes/class.ilLMObject.php");


/**
 * Question list table
 *
 * @author Fred Neumann <afred.neumann@fau.de>
 * @version $Id$
 *
 * @ingroup ModulesLearningModule
 */
class ilLMQuestionUsersTableGUI extends ilTable2GUI
{
    /**
    * Constructor
    */
    public function __construct($a_parent_obj, $a_parent_cmd, $a_lm)
    {
        global $ilCtrl, $lng, $ilAccess, $lng, $rbacsystem;

        $this->lm = $a_lm;

        $this->setId("lm_qst_usr" . $this->lm->getId());

        parent::__construct($a_parent_obj, $a_parent_cmd);

        $this->addColumn($this->lng->txt("id"), "", 0, true);
        $this->addColumn($this->lng->txt("user"), "lastname");
        $this->addColumn($this->lng->txt("login"), "login");
        $this->addColumn($this->lng->txt("cont_users_answered"));
        $this->addColumn($this->lng->txt("cont_correct_after_first"));
        $this->addColumn($this->lng->txt("cont_second"));
        $this->addColumn($this->lng->txt("cont_third_and_more"));
        $this->addColumn($this->lng->txt("cont_never"));

        $this->setFormAction($ilCtrl->getFormAction($this->parent_obj, $this->parent_cmd));
        $this->setRowTemplate("tpl.lm_question_user_row.html", "Modules/LearningModule");

        $this->setExternalSegmentation(true);
        $this->setExternalSorting(true);
        $this->setDefaultOrderField("lastname");
        $this->setDefaultOrderDirection("asc");

        $this->determineOffsetAndOrder();
        $this->getItems();
        $this->setExportFormats(array(self::EXPORT_EXCEL));
    }

    /**
    * Get user items
    */
    public function getItems()
    {
        include_once("Services/Tracking/classes/class.ilChangeEvent.php");
        $progress_user_ids = ilChangeEvent::lookupUsersInProgress($this->lm->getId());

        include_once("Services/User/classes/class.ilUserQuery.php");
        $user_query = new ilUserQuery();
        $user_query->setOrderField(ilUtil::stripSlashes($this->getOrderField()));
        $user_query->setOrderDirection(ilUtil::stripSlashes($this->getOrderDirection()));
        $user_query->setOffset(ilUtil::stripSlashes($this->getOffset()));
        $user_query->setLimit(ilUtil::stripSlashes($this->getLimit()));
        $user_query->setUserFilter($progress_user_ids);
        $user_query_result = $user_query->query();

        $users_ids = array();
        foreach ($user_query_result['set'] as $user) {
            $user_ids[] = $user['usr_id'];
        }

        // load question answer information
        include_once("./Services/COPage/classes/class.ilPageQuestionProcessor.php");
        $multi_status = ilPageQuestionProcessor::getAnswersForLm($this->lm->getId(), $user_ids);

        $data = array();
        foreach ($user_query_result['set'] as $user) {
            $row = array(
                'usr_id' => $user['usr_id'],
                'lastname' => $user['lastname'] . ', ' . $user['firstname'],
                'login' => $user['login'],
                'answered' => 0,
                'first' => 0,
                'second' => 0,
                'third_or_more' => 0,
                'never' => 0
            );

            if (isset($multi_status[$user['usr_id']])) {
                foreach ($multi_status[$user['usr_id']] as $question_id => $answer_status) {
                    $row['answered']++;
                    if ($answer_status['passed']) {
                        if ($answer_status['try'] == 1) {
                            $row['first']++;
                        } elseif ($answer_status['try'] == 2) {
                            $row['second']++;
                        } elseif ($answer_status['try'] >= 3) {
                            $row['third_or_more']++;
                        }
                    } else {
                        $row['never']++;
                    }
                }
            }
            $data[] = $row;
        }

        $this->setMaxCount($user_query_result['cnt']);
        $this->setData($data);
    }


    /**
    * Fill table row
    */
    protected function fillRow($a_set)
    {
        global $ilCtrl, $lng;

        $ilCtrl->setParameter($this->parent_obj, 'user_id', $a_set['usr_id']);

        $this->tpl->setVariable('USER', $a_set['lastname']);
        $this->tpl->setVariable('LOGIN', $a_set['login']);
        $this->tpl->setVariable('LINK', $ilCtrl->getLinkTarget($this->parent_obj, 'listQuestionUserDetails'));

        $this->tpl->setVariable('VAL_ANSWERED', (int) $a_set['answered']);
        if ($a_set['answered'] == 0) {
            $this->tpl->setVariable('VAL_CORRECT_FIRST', ' ');
            $this->tpl->setVariable('VAL_CORRECT_SECOND', ' ');
            $this->tpl->setVariable('VAL_CORRECT_THIRD_OR_MORE', ' ');
            $this->tpl->setVariable('VAL_NEVER', ' ');
        } else {
            $this->tpl->setVariable('VAL_CORRECT_FIRST', round(100 / $a_set['answered'] * $a_set['first']) . ' %');
            $this->tpl->setVariable('VAL_CORRECT_SECOND', round(100 / $a_set['answered'] * $a_set['second']) . ' %');
            $this->tpl->setVariable('VAL_CORRECT_THIRD_AND_MORE', round(100 / $a_set['answered'] * $a_set['third_or_more']) . ' %');
            $this->tpl->setVariable('VAL_NEVER', round(100 / $a_set['answered'] * $a_set['never']) . ' %');
        }
    }
}
