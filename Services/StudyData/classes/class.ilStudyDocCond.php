<?php
/* fau: studyCond - new class ilStudyDocCond. */

require_once(__DIR__ . '/abstract/class.ilStudyCond.php');

/**
 * Conditions related to a doc program
 */
class ilStudyDocCond extends ilStudyCond
{
    /** @inheritdoc  */
    protected static $cache;


    /** @var integer */
    public $prog_id;

    /** @var ilDateTime */
    public $min_approval_date;

    /** @var ilDateTime */
    public $max_approval_date;


    /**
     * @inheritDoc
     */
    public static function _read($obj_id) : array
    {
        global $DIC;
        $ilDB = $DIC->database();

        $query = "SELECT * FROM study_doc_cond " .
            "WHERE obj_id = " . $ilDB->quote($obj_id, 'integer');
        $result = $ilDB->query($query);

        $conditions = [];
        while ($row = $ilDB->fetchAssoc($result)) {
            $cond = new static;
            $cond->setRowData($row);
            $conditions[] = $cond;
        }
        return $conditions;
    }

    /**
     * @inheritDoc
     */
    public static function _count($obj_id) : int
    {
        global $DIC;
        $ilDB = $DIC->database();

        $query = "SELECT count(*) num FROM study_doc_cond " .
            "WHERE obj_id = " . $ilDB->quote($obj_id, 'integer');
        $result = $ilDB->query($query);

        if ($row = $ilDB->fetchAssoc($result)) {
            return $row['num'];
        }
        return 0;
    }

    /**
     * @inheritDoc
     */
    public static function _delete($obj_id)
    {
        global $DIC;
        $ilDB = $DIC->database();

        $query = "DELETE FROM study_doc_cond WHERE obj_id = " . $ilDB->quote($obj_id, 'integer');
        $ilDB->manipulate($query);
    }

    /**
     * @inheritDoc
     */
    public function getText() : string
    {
        global $DIC;
        $lng = $DIC->language();

        $ctext = [];
        if (isset($this->prog_id)) {
            require_once('Services/StudyData/classes/class.ilStudyOptionDocProgram.php');
            $ctext[] = ilStudyOptionDocProgram::_lookupText($this->prog_id);
        }

        if ($this->min_approval_date instanceof ilDate and $this->max_approval_date instanceof ilDate) {
            $ctext[] = sprintf(
                $lng->txt('studycond_min_max_approval_date'),
                ilDatePresentation::formatPeriod($this->min_approval_date, $this->max_approval_date)
            );
        } elseif ($this->min_approval_date instanceof ilDate) {
            $ctext[] = sprintf($lng->txt('studycond_min_approval_date'), ilDatePresentation::formatDate($this->min_approval_date));
        } elseif ($this->max_approval_date instanceof ilDate) {
            $ctext[] = sprintf($lng->txt('studycond_max_approval_date'), ilDatePresentation::formatDate($this->max_approval_date));
        }

        return implode($lng->txt('studycond_criteria_delimiter') . ' ', $ctext);
    }


    /**
     * @inheritDoc
     */
    public function read()
    {
        global $DIC;
        $ilDB = $DIC->database();

        $query = "SELECT * FROM study_doc_cond " .
            "WHERE cond_id = " . $ilDB->quote($this->cond_id, 'integer');

        $result = $ilDB->query($query);
        if ($row = $ilDB->fetchAssoc($result)) {
            $this->setRowData($row);
        }
    }


    /**
     * @inheritDoc
     */
    public function write()
    {
        global $DIC;
        $ilDB = $DIC->database();

        if (empty($this->cond_id)) {
            $this->cond_id = $ilDB->nextId('study_doc_cond');
        }

        $min_approval_date = ($this->min_approval_date instanceof ilDate) ? $this->min_approval_date->get(IL_CAL_DATE) : null;
        $max_approval_date = ($this->max_approval_date instanceof ilDate) ? $this->max_approval_date->get(IL_CAL_DATE) : null;

        $query = "REPLACE INTO study_doc_cond ("
            . "cond_id, obj_id, prog_id, min_approval_date, max_approval_date) "
            . "VALUES("
            . $ilDB->quote($this->cond_id, 'integer') . ", "
            . $ilDB->quote($this->obj_id, 'integer') . ", "
            . $ilDB->quote($this->prog_id, 'integer') . ", "
            . $ilDB->quote($min_approval_date, 'text') . ", "
            . $ilDB->quote($max_approval_date, 'text') . ")";
        $ilDB->manipulate($query);
    }


    /**
     * Set the data from an assoc array
     *
     * @param   array   $a_row assoc array of data
     */
    private function setRowData($a_row = [])
    {
        $this->cond_id = $a_row['cond_id'];
        $this->obj_id = $a_row['obj_id'];
        $this->prog_id = $a_row['prog_id'];

        $this->min_approval_date = null;
        if (!empty($a_row['min_approval_date'])) {
            $this->min_approval_date = new ilDate($a_row['min_approval_date'], IL_CAL_DATE);
        }

        $this->max_approval_date = null;
        if (!empty($a_row['max_approval_date'])) {
            $this->max_approval_date = new ilDate($a_row['max_approval_date'], IL_CAL_DATE);
        }
    }


    /**
     * Check the doc program for the condition
     * Only one program needs to be satisfied
     *
     * @param ilStudyDocData[] $data
     * @return bool
     */
    public function check(array $data) : bool
    {
        foreach ($data as $doc) {
            // check the criteria for each doc program
            // all defined criteria must be satisfied
            // continue with next doc program on failure

            if (isset($this->prog_id) and (!isset($doc->prog_id) or $this->prog_id != $doc->prog_id)) {
                continue; // failed
            }

            if ($this->min_approval_date instanceof ilDate) {
                if (!($doc->prog_approval instanceof ilDate) || ilDate::_before(
                    $doc->prog_approval,
                    $this->min_approval_date,
                    IL_CAL_DAY
                )) {
                    continue; // failed
                }
            }
            if ($this->max_approval_date instanceof ilDate) {
                if (!($doc->prog_approval instanceof ilDate) || ilDate::_after(
                    $doc->prog_approval,
                    $this->max_approval_date,
                    IL_CAL_DAY
                )) {
                    continue; // failed
                }
            }

            // this doc program fits
            return true;
        }

        // none of doc programs fits
        return false;
    }

    /**
     * @inheritDoc
     */
    public function delete()
    {
        global $DIC;
        $ilDB = $DIC->database();

        $query = "DELETE FROM study_doc_cond WHERE cond_id = " . $ilDB->quote($this->cond_id, 'integer');
        $ilDB->manipulate($query);
    }
}
