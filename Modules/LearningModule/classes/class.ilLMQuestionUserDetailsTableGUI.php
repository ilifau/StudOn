<?php
// fau: lmQStat - new table for user details.

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once('./Services/Table/classes/class.ilTable2GUI.php');
include_once('./Modules/TestQuestionPool/classes/class.assQuestion.php');
include_once('./Modules/LearningModule/classes/class.ilLMObject.php');
include_once('./Services/Tracking/classes/class.ilLearningProgressBaseGUI.php');
include_once('./Services/Tracking/classes/class.ilLPStatus.php');

/**
 * Question user details table
 *
 * @author Fred Neumann <fred.neumann@fau.de>
 * @version $Id$
 *
 * @ingroup ModulesLearningModule
 */
class ilLMQuestionUserDetailsTableGUI extends ilTable2GUI
{
    /**
    * Constructor
    */
    public function __construct($a_parent_obj, $a_parent_cmd, $a_lm, $a_user_id)
    {
        global $ilCtrl, $lng, $ilAccess, $lng, $rbacsystem;

        $this->lm = $a_lm;
        $this->user_id = $a_user_id;

        $this->setId('lm_qst_usr_det' . $this->lm->getId());

        $lng->loadLanguageModule('trac');

        parent::__construct($a_parent_obj, $a_parent_cmd);

        $this->addColumn($this->lng->txt('pg'));
        $this->addColumn($this->lng->txt('question'));
        $this->addColumn($this->lng->txt('status'));
        $this->addColumn($this->lng->txt('cont_tries'));
        $this->addColumn($this->lng->txt('cont_unlocked'));

        $this->setExternalSorting(true);
        $this->setExternalSegmentation(true);
        $this->setEnableHeader(true);
        $this->setFormAction($ilCtrl->getFormAction($this->parent_obj, $this->parent_cmd));
        $this->setRowTemplate('tpl.lm_question_user_details_row.html', 'Modules/LearningModule');
        $this->setEnableTitle(true);

        $name = ilObjUser::_lookupName($this->user_id);
        $this->setTitle($name['lastname'] . ', ' . $name['firstname'] . ' [' . $name['login'] . ']');

        $this->determineOffsetAndOrder();
        $this->getItems();
    }

    /**
    * Get user items
    */
    public function getItems()
    {
        global $ilDB, $lng, $ilPluginAdmin;

        include_once('./Modules/LearningModule/classes/class.ilLMPageObject.php');

        // get the questions to display
        $questions = ilLMPageObject::queryQuestionsOfLearningModule(
            $this->lm->getId(),
            ilUtil::stripSlashes($this->getOrderField()),
            ilUtil::stripSlashes($this->getOrderDirection()),
            ilUtil::stripSlashes($this->getOffset()),
            ilUtil::stripSlashes($this->getLimit())
        );

        if (count($questions['set']) == 0 && $this->getOffset() > 0) {
            $this->resetOffset();
            $questions = ilLMPageObject::queryQuestionsOfLearningModule(
                $this->lm->getId(),
                ilUtil::stripSlashes($this->getOrderField()),
                ilUtil::stripSlashes($this->getOrderDirection()),
                ilUtil::stripSlashes($this->getOffset()),
                ilUtil::stripSlashes($this->getLimit())
            );
        }

        $question_ids = array();
        foreach ($questions['set'] as $question) {
            $question_ids[] = $question['question_id'];
        }

        // get more question information
        include_once('./Modules/TestQuestionPool/classes/class.ilAssQuestionList.php');
        $qlist = new ilAssQuestionList($ilDB, $lng, $ilPluginAdmin, 0);
        $qlist->addFieldFilter('question_id', $question_ids);
        $qlist->load();
        $qdata = $qlist->getQuestionDataArray();


        // get answer information
        include_once('./Services/COPage/classes/class.ilPageQuestionProcessor.php');
        $answer_status = ilPageQuestionProcessor::getAnswerStatus($question_ids, $this->user_id);

        for ($i = 0; $i < count($questions['set']); $i++) {
            $question = $questions['set'][$i];
            $question_id = $question['question_id'];

            if (!isset($answer_status[$question_id])) {
                $question['status'] = ilLPStatus::LP_STATUS_NOT_ATTEMPTED;
                $question['tries'] = 0;
                $question['unlocked'] = false;
            } else {
                $status = $answer_status[$question_id];

                if ($status['passed']) {
                    $question['status'] = ilLPStatus::LP_STATUS_COMPLETED;
                } elseif ($qdata[$question_id]['nr_of_tries'] > 0 and $status['try'] >= $qdata[$question_id]['nr_of_tries']) {
                    $question['status'] = ilLPStatus::LP_STATUS_FAILED;
                } else {
                    $question['status'] = ilLPStatus::LP_STATUS_IN_PROGRESS;
                }
                $question['tries'] = $status['try'];
                $question['unlocked'] = (bool) $status['unlocked'];
            }

            $questions['set'][$i] = $question;
        }

        $this->setMaxCount($questions['cnt']);
        $this->setData($questions['set']);
    }


    /**
    * Fill table row
    */
    protected function fillRow($a_set)
    {
        global $lng;

        $this->tpl->setVariable('PAGE_TITLE', ilLMObject::_lookupTitle($a_set['page_id']));
        $this->tpl->setVariable('QUESTION', assQuestion::_getQuestionText($a_set['question_id']));

        $this->tpl->setVariable($tpl_prefix . 'STATUS_IMG', ilLearningProgressBaseGUI::_getImagePathForStatus($a_set['status']));
        $this->tpl->setVariable($tpl_prefix . 'STATUS_ALT', $lng->txt($a_set['status']));

        $this->tpl->setVariable('VAL_TRIES', $a_set['tries']);
        $this->tpl->setVariable('VAL_UNLOCKED', $a_set['unlocked'] ? $lng->txt('yes') : $lng->txt('no'));
    }
}
