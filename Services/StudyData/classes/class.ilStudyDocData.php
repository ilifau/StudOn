<?php

/* fau: studyData - new class ilStudyDocData. */

require_once(__DIR__ . '/abstract/class.ilStudyData.php');
require_once(__DIR__ . '/class.ilStudyCourseSubject.php');

/**
 * Data of a doc program
 */
class ilStudyDocData extends ilStudyData
{
    /** @inheritdoc */
    protected static $cache;

    /** @var int */
    public $prog_id;

    /** @var ilDateTime */
    public $prog_approval;

    /**
     * @inheritDoc
     */
    public static function _read($user_id) : array
    {
        global $DIC;
        $ilDB = $DIC->database();

        $query = 'SELECT usr_id, prog_id, prog_approval'
            . ' FROM usr_doc_prog'
            . ' WHERE usr_id=' . $ilDB->quote($user_id, 'integer');
        $result = $ilDB->query($query);

        $progs = [];
        while ($row = $ilDB->fetchAssoc($result)) {
            $prog = new static;
            $prog->user_id = $row['usr_id'];
            $prog->prog_id = $row['prog_id'];
            if (isset($row['prog_approval'])) {
                try {
                    $prog->prog_approval = new ilDate($row['prog_approval'], IL_CAL_DATE);
                } catch (Exception $e) {
                    $prog->prog_approval = null;
                }
            }
            $progs[] = $prog;
        }

        return $progs;
    }

    /**
     * @inheritDoc
     */
    public static function _count($user_id) : int
    {
        global $DIC;
        $ilDB = $DIC->database();

        $query = "SELECT count(*) num FROM usr_doc_prog WHERE usr_id = " . $ilDB->quote($user_id, 'integer');
        $result = $ilDB->query($query);

        if ($row = $ilDB->fetchAssoc($result)) {
            return $row['num'];
        }
        return 0;
    }

    /**
     * @inheritDoc
     */
    public function getText() : string
    {
        require_once('Services/StudyData/classes/class.ilStudyOptionDocProgram.php');

        global $DIC;
        $lng = $DIC->language();

        $text = '';
        if (!empty($this->prog_id)) {
            $text = ilStudyOptionDocProgram::_lookupText($this->prog_id);
        }
        if ($this->prog_approval instanceof ilDate) {
            if (empty($text)) {
                $text = $lng->txt('studydata_promotion');
            }
            $text .= ', ' . $lng->txt('studydata_promotion_approval') . ' ';
            $text .= ilDatePresentation::formatDate($this->prog_approval);
        }

        return $text;
    }


    /**
     * @inheritDoc
     */
    public static function _delete($user_id)
    {
        global $DIC;
        $ilDB = $DIC->database();

        $query = "DELETE FROM usr_doc_prog WHERE usr_id = " . $ilDB->quote($user_id, 'integer');
        $ilDB->manipulate($query);
    }

    /**
     * @inheritDoc
     */
    public function write()
    {
        global $DIC;
        $ilDB = $DIC->database();

        $approval_str = null;
        if ($this->prog_approval instanceof ilDate) {
            $approval_str = $this->prog_approval->get(IL_CAL_DATE);
        }

        $ilDB->replace(
            'usr_doc_prog',
            [
                'usr_id' => ['integer', $this->user_id],
            ],
            [
                'prog_id' => ['integer', $this->prog_id],
                'prog_approval' => ['text', $approval_str]
            ]
        );
    }
}
