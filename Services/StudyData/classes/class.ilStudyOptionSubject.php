<?php
/* fau: studyData - new class ilStudyOptionSubject. */

require_once(__DIR__ . '/abstract/class.ilStudyOption.php');

/**
 * Structured doc program
 */
class ilStudyOptionSubject extends ilStudyOption
{
    /** @inheritdoc */
    protected static $cache;

    /** @inheritdoc */
    protected static $allCached;

    /**
     * @inheritDoc
     */
    protected static function _read(array $ids = null) : array
    {
        global $DIC;
        $ilDB = $DIC->database();

        $query = "SELECT subject_id, subject_title FROM study_subjects";
        if (!empty($ids)) {
            $query .= " WHERE " . $ilDB->in('subject_id', $ids, false, 'integer');
        }
        $query .= " ORDER BY subject_title";
        $result = $ilDB->query($query);

        $options = [];
        while ($row = $ilDB->fetchAssoc($result)) {
            $option = new static;
            $option->id = $row['subject_id'];
            $option->title = $row['subject_title'];
            $options[$option->id] = $option;
        }
        return $options;
    }


    /**
     * @inheritDoc
     */
    public static function _delete(array $ids = null)
    {
        global $DIC;
        $ilDB = $DIC->database();

        $query = "DELETE FROM study_subjects";
        if (!empty($ids)) {
            $query .= "WHERE" . $ilDB->in('subject_id', $ids, false, 'integer');
        }
        $ilDB->manipulate($query);
    }


    /**
     * @inheritDoc
     */
    public function write()
    {
        global $DIC;
        $ilDB = $DIC->database();

        $query = "REPLACE INTO study_subjects(subject_id, subject_title) VALUES ("
            . $ilDB->quote($this->id, 'integer') . ', '
            . $ilDB->quote($this->title, 'text') . ')';

        $ilDB->manipulate($query);
    }


    /**
     * @inheritDoc
     */
    protected function getId() : int
    {
        return $this->id;
    }


    /**
     * @inheritDoc
     */
    protected function getText() : string
    {
        global $DIC;
        $lng = $DIC->language();

        return (empty($this->title) ? $lng->txt('studydata_unknown_subject') : $this->title) . ' [' . $this->id . ']';
    }
}
