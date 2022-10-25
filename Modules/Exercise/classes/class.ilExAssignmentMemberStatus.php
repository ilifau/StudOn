<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Exercise assignment member status
 *
 * @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
 * @ingroup ModulesExercise
 */
class ilExAssignmentMemberStatus
{
    // fau: exPlag - constants
    const PLAG_NONE = 'none';
    const PLAG_SUSPICION = 'suspicion';
    const PLAG_DETECTED = 'detected';
    // fau.

    /**
     * @var ilDB
     */
    protected $db;

    protected $ass_id; // [int]
    protected $user_id;  // [int]
    protected $notice; // [string]
    protected $returned;  // [int]
    protected $solved;  // [int] - obsolete?!
    protected $sent; // [int]
    protected $sent_time; // [datetime]
    protected $feedback; // [int]
    protected $feedback_time; // [datetime]
    protected $status = "notgraded";  // [string]
    protected $status_time; // [datetime]
    protected $mark; // [string]
    protected $comment; // [string]
    protected $db_exists; // [bool]
    protected $returned_update; // [bool]
    protected $status_update; // [bool]

    // fau: exPlag - class variables
    protected $plag_flag; // [int]
    protected $plag_comment; // [string]
    // fau.

    // fau: exStatement - class variable
    protected $time_authorship_statement; // [string]
    // fau.



    public function __construct($a_ass_id, $a_user_id)
    {
        global $DIC;

        $this->db = $DIC->database();
        $this->ass_id = $a_ass_id;
        $this->user_id = $a_user_id;
        
        $this->read();
    }

    // fau: exCalc - getter for user id
    public function getUserId() {
        return $this->user_id;
    }
    // fau.

    // fau: exCalc - getter for assignment id
    public function getAssignmentId() {
        return $this->ass_id;
    }
    // fau.


    public function setNotice($a_value)
    {
        $this->notice = $a_value;
    }
    
    public function getNotice()
    {
        return $this->notice;
    }
    
    public function setReturned($a_value)
    {
        if ($a_value &&
            !$this->returned) {
            $this->returned_update = true;
        }
        $this->returned = $a_value;
    }
    
    public function getReturned()
    {
        return $this->returned;
    }
    
    public function setSolved($a_value)
    {
        $this->solved = $a_value;
    }
    
    public function getSolved()
    {
        return $this->solved;
    }
    
    protected function setStatusTime($a_value)
    {
        $this->status_time = $a_value;
    }
    
    public function getStatusTime()
    {
        return $this->status_time;
    }
    
    public function setSent($a_value)
    {
        if ($a_value && $a_value != $this->sent) {
            $this->setSentTime(ilUtil::now());
        }
        $this->sent = $a_value;
    }
    
    public function getSent()
    {
        return $this->sent;
    }
    
    protected function setSentTime($a_value)
    {
        $this->sent_time = $a_value;
    }
    
    public function getSentTime()
    {
        return $this->sent_time;
    }
    
    public function setFeedback($a_value)
    {
        if ($a_value != $this->sent) {
            $this->setFeedbackTime(ilUtil::now());
        }
        $this->feedback = $a_value;
    }
    
    public function getFeedback()
    {
        return $this->feedback;
    }
    
    protected function setFeedbackTime($a_value)
    {
        $this->feedback_time = $a_value;
    }
    
    public function getFeedbackTime()
    {
        return $this->feedback_time;
    }
    
    public function setStatus($a_value)
    {
        if ($a_value != $this->status) {
            $this->setStatusTime(ilUtil::now());
            $this->status = $a_value;
            $this->status_update = true;
        }
    }
    
    public function getStatus()
    {
        return $this->status;
    }


    // fau: exPlag - new function getEffectiveStatus()
    /**
     * Get the effective status if plagiarism is taken into account
     * @return null
     */
    public function getEffectiveStatus()
    {
        if ($this->isPlagDetected()) {
            return 'failed';
        }
        return $this->status;
    }
    // fau.


    public function setMark($a_value)
    {
        if ($a_value != $this->mark) {
            $this->setStatusTime(ilUtil::now());
            // fau: exCalc - force a recalculation of the exercise status if mark is changed (postUpdateStatus)
            $this->status_update = true;
            // fau.
        }
        $this->mark = $a_value;
    }
    
    public function getMark()
    {
        return $this->mark;
    }

    // fau: exPlag - new function getEffectiveMark()
    // fau: exCalc - new function getEffectiveMark()
    /**
     * Get the effective mark if plagiarism is taken into account
     * @param bool $a_numeric
     * @return float|null
     */
    public function getEffectiveMark($a_numeric = false)
    {
        $a_numeric = (bool) $a_numeric;

        // treat plagiarism
        if ($this->isPlagDetected()) {
            if ($a_numeric) {
                return 0;
            }
            else {
                return null;
            }
        }

        // treat non numeric marks
        if ($a_numeric) {
            $mark = str_replace(',', '.', $this->mark);

            if (!is_numeric($mark)) {
                return null;
            }
            else {
                return (float) $mark;
            }

        }

        return $this->mark;
    }
    // fau.


    // fau: exMaxPoints - new function getMarkWithInfo
    /**
     * Get the mark with an extended info
     * @param ilExAssignment $assignment
     * @param bool $effective
     * @return string
     */
    public function getMarkWithInfo (ilExAssignment $assignment, $effective = true) {
        global $DIC;
        $lng = $DIC->language();

        $mark = ($effective ? $this->getEffectiveMark($assignment->hasNumericPoints()) : $this->getMark());
        if ($assignment->getMaxPoints() && isset($mark)) {
            if ($assignment->checkMark($mark)) {
                $percent = 100 * (float) $mark /  $assignment->getMaxPoints();
                return sprintf($lng->txt("exc_mark_percent"), $mark, round($percent, 2));
            }
            else {
                return sprintf($lng->txt("exc_mark_invalid"), $mark);
            }
        }
        return $mark;
    }
    // fau.


    // fau: exPlag -get info about plagiarism
    public function getPlagInfo(ilExAssignment $assignment) {
        global $DIC;
        $lng = $DIC->language();

        if ($this->getPlagFlag() == self::PLAG_DETECTED) {
            $text = $lng->txt('exc_plag_detected_info');
            if (!empty($this->getPlagComment())) {
                $text .= $lng->txt('exc_plag_see_comment');
            }
            if ($this->getMark() != $this->getEffectiveMark($assignment->hasNumericPoints())) {
                $text .= $lng->txt('exc_plag_original_mark')
                    . ' ' . $this->getMarkWithInfo($assignment, false);
            }
            return $text;
        }
        return '';
    }
    // fau.

    public function setComment($a_value)
    {
        $this->comment = $a_value;
    }
    
    public function getComment()
    {
        return $this->comment;
    }

    // fau: exPlag - getter, setter and checker
    /**
     * @return string
     */
    public function getPlagFlag()
    {
        return $this->plag_flag;
    }

    /**
     * @param string $plag_flag
     */
    public function setPlagFlag($plag_flag)
    {
        // fau: exPlag - force a recalculation of the exercise status if plag flag is changed (postUpdateStatus)
        if ($plag_flag != $this->plag_flag) {
            $this->status_update = true;
        }
        // fau.
        $this->plag_flag = $plag_flag;
    }

    /**
     * @return string
     */
    public function getPlagComment()
    {
        return $this->plag_comment;
    }

    /**
     * @param string $plag_comment
     */
    public function setPlagComment($plag_comment)
    {
        $this->plag_comment = $plag_comment;
    }

    /**
     * Is a plagiarism detected
     * @return bool
     */
    public function isPlagDetected()
    {
        return ($this->plag_flag == self::PLAG_DETECTED);
    }

    /**
     * Is a plagiarism suspected
     * @return bool
     */
    public function isPlagSuspected()
    {
        return ($this->plag_flag == self::PLAG_SUSPICION);
    }
    // fau.

    // fau: exStatement  - setters and getters
    /**
     * User has checked the authorship statement
     */
    public function hasAuthorshipStatement()
    {
        return !empty($this->time_authorship_statement);
    }

    /**
     * Set if the user has checked the authorship statement
     * @param bool $stated
     */
    public function setAuthorshipStatement($stated)
    {
        if ($stated && empty($this->time_authorship_statement)) {
            $this->time_authorship_statement = strftime("%Y-%m-%d %H:%M:%S");
        }

        if (!$stated) {
            $this->time_authorship_statement = null;
        }
    }

    /**
     * Get the time of an authorship statement
     * @return string
     */
    public function getAuthorshipStatementTime()
    {
        return $this->time_authorship_statement;
    }


    /**
     * Set the time of an authorship statement
     * @param string $time
     */
    public function setAuthorshipStatementTime($time)
    {
        $this->time_authorship_statement = $time;
    }
    // fau.


    // fau: exCalc - support getting multiple instances
    /**
     * Read the properties
     */
    protected function read()
    {
        $ilDB = $this->db;
        
        $set = $ilDB->query("SELECT * FROM exc_mem_ass_status" .
            " WHERE ass_id = " . $ilDB->quote($this->ass_id, "integer") .
            " AND usr_id = " . $ilDB->quote($this->user_id, "integer"));
        if ($row = $ilDB->fetchAssoc($set)) {
            $this->setPropertiesByRow($row);
        }
    }

    /**
     * Set the object data from a database row
     * @param $row
     */
    protected function setPropertiesByRow($row) {
        // not using setters to circumvent any datetime-logic/-magic
        $this->notice = $row["notice"];
        $this->returned = $row["returned"];
        $this->solved = $row["solved"];
        $this->status_time = $row["status_time"];
        $this->sent = $row["sent"];
        $this->sent_time = $row["sent_time"];
        $this->feedback_time = $row["feedback_time"];
        $this->feedback = $row["feedback"];
        $this->status = $row["status"];
        $this->mark = $row["mark"];
        $this->comment = $row["u_comment"];
        // fau: exPlag - read values
        $this->plag_flag = $row["plag_flag"];
        $this->plag_comment = $row["plag_comment"];
        // fau.
        // fau: exStatement - read the authorship statement time from db
        $this->time_authorship_statement = $row['time_authorship_statement'];
        // fau.
        $this->db_exists = true;
    }

    /**
     * Get multiple instances, indexed by usr_id and ass_id
     * @param int[] $a_usr_ids
     * @param int[] $a_ass_ids
     * @return self[]
     */
    public static function getMultiple($a_usr_ids = [], $a_ass_ids = [])
    {
        global $DIC;
        $ilDB = $DIC->database();

        $query = "SELECT * FROM exc_mem_ass_status WHERE "
            . $ilDB->in('ass_id', $a_ass_ids, false, 'integer') . " AND "
            . $ilDB->in('usr_id', $a_usr_ids, false, 'integer');
        $result = $ilDB->query($query);

        $instances = [];
        while ($row = $ilDB->fetchAssoc($result)) {
            $status = new self($row['ass_id'], $row['usr_id']);
            $status->setPropertiesByRow($row);
            $instances[] = $status;
        }
        return $instances;
    }
    // fau.

    protected function getFields()
    {
        return array(
            "notice" => array("text", $this->getNotice())
            // fau: fixExStatusUpdate - prevent null for saving returned flag
            ,"returned" => array("integer", (int) $this->getReturned())
            // fau.
            ,"solved" => array("integer", $this->getSolved())
            ,"status_time" => array("timestamp", $this->getStatusTime())
            ,"sent" => array("integer", $this->getSent())
            ,"sent_time" => array("timestamp", $this->getSentTime())
            ,"feedback_time" => array("timestamp", $this->getFeedbackTime())
            ,"feedback" => array("integer", (int) $this->getFeedback())
            ,"status" => array("text", $this->getStatus())
            ,"mark" => array("text", $this->getMark())
            ,"u_comment" => array("text", $this->getComment())
            // fau: exPlag - get the plag fields
            ,"plag_flag" => array("text", $this->getPlagFlag())
            ,"plag_comment" => array("text", $this->getPlagComment())
            // fau.
            // fau: testStatement: update authorship statement time in db
            ,'time_authorship_statement' => array('text', $this->getAuthorshipStatementTime()),
            // fau.
        );
    }
    
    public function update()
    {
        $ilDB = $this->db;
        
        $keys = array(
            "ass_id" => array("integer", $this->ass_id)
            ,"usr_id" => array("integer", $this->user_id)
        );
        $fields = $this->getFields();
        if (!$this->db_exists) {
            $fields = array_merge($keys, $fields);
            $ilDB->insert("exc_mem_ass_status", $fields);
        } else {
            $ilDB->update("exc_mem_ass_status", $fields, $keys);
        }
        
        if ($this->returned_update) {
            $this->postUpdateReturned();
        }
        if ($this->status_update) {
            $this->postUpdateStatus();
        }
    }
    
    protected function postUpdateReturned()
    {
        $ilDB = $this->db;
        
        // first upload => notification on submission?
        $set = $ilDB->query("SELECT fb_cron, fb_date, fb_file" .
            " FROM exc_assignment" .
            " WHERE id = " . $ilDB->quote($this->ass_id, "integer"));
        $row = $ilDB->fetchAssoc($set);
        if ($row["fb_cron"] &&
            $row["fb_file"] &&
            $row["fb_date"] == ilExAssignment::FEEDBACK_DATE_SUBMISSION) { // #16200
            ilExAssignment::sendFeedbackNotifications($this->ass_id, $this->user_id);
        }
    }
        
    protected function postUpdateStatus()
    {
        $ass = new ilExAssignment($this->ass_id);
        $exc = new ilObjExercise($ass->getExerciseId(), false);
        $exc->updateUserStatus($this->user_id);
    }

    /**
     * @deprecated will be deleted with R8
     */
    public function getStatusIcon() : string
    {
        // fau: exPlag - use effective status for status icon
        switch ($this->getEffectiveStatus()) {
        // fau.
            case "passed":
                return "scorm/passed.svg";
            
            case "failed":
                return "scorm/failed.svg";
                
            default:
                return "scorm/not_attempted.svg";
        }
    }
    
    /**
     * Check whether exercise has been sent to any student per mail.
     */
    public static function lookupAnyExerciseSent($a_ass_id)
    {
        global $DIC;

        $ilDB = $DIC->database();

        $q = "SELECT count(*) AS cnt" .
            " FROM exc_mem_ass_status" .
            " WHERE NOT sent_time IS NULL" .
            " AND ass_id = " . $ilDB->quote($a_ass_id, "integer");
        $set = $ilDB->query($q);
        $rec = $ilDB->fetchAssoc($set);
        return ($rec["cnt"] > 0);
    }
}
