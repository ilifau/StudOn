<?php declare(strict_types=1);

namespace FAU;

/**
 * Base class of a database repository handling record data
 * @see RecordData
 */
abstract class RecordRepo
{
    protected \ilDBInterface $db;

    public function __construct(\ilDBInterface $a_db)
    {
        $this->db = $a_db;
    }

    /**
     * Query for records
     * @return RecordData[]
     */
    protected function queryRecords(string $query, RecordData $prototype) : array
    {
        $records = [];
        while ($row = $this->db->fetchAssoc($this->db->query($query))) {
            $records[] = $prototype->withTableRow($row);
        }
        return $records;
    }

    /**
     * Insert the records
     * @return RecordData  the inserted record (eventually wit the new sequence number
     */
    protected function insertRecord(RecordData $record) : RecordData
    {
        if ($record::hasTableSequence() && empty($record->getTableSequence())) {
            $record = $record->withTableSequence($this->db->nextId($record::getTableName()));
        }
        $types = array_merge($record::getTableKeyTypes(), $record::getTableOtherTypes());
        $fields = $this->getFieldsArray($record, $types);
        $this->db->insert($record::getTableName(), $fields);
        return $record;
    }

    /**
     * Insert or update the record
     * @return RecordData  the inserted or updated record (eventually wit the new sequence number)
     */
    protected function replaceRecord(RecordData $record) : RecordData
    {
        if ($record::hasTableSequence() && empty($record->getTableSequence())) {
            $record = $record->withTableSequence($this->db->nextId($record::getTableName()));
        }
        $key_fields = $this->getFieldsArray($record, $record::getTableKeyTypes());
        $other_fields = $this->getFieldsArray($record, $record::getTableOtherTypes());
        $this->db->replace($record::getTableName(), $key_fields, $other_fields);
        return $record;

    }

    /**
     * Update a record
     */
    protected function updateRecord(RecordData $record)
    {
        $key_fields = $this->getFieldsArray($record, $record::getTableKeyTypes());
        $other_fields = $this->getFieldsArray($record, $record::getTableOtherTypes());
        $this->db->update($record::getTableName(), array_merge($key_fields, $other_fields), $key_fields);
    }

    /**
     * Delete a record
     */
    protected function deleteRecord(RecordData $record)
    {
        $conditions[] = '';
        foreach($this->getFieldsArray($record, $record::getTableKeyTypes()) as $key => $field) {
            $conditions[] = $this->db->quoteIdentifier($key) . " = " . $this->db->quote($field[1], $field[0]);
        }
        $query = "DELETE FROM " . $this->db->quoteIdentifier($record::getTableName())
            . " WHERE " . implode(" AND ", $conditions);
        $this->db->manipulate($query);
    }

    /**
     * Get the typed field values
     * @param RecordData $record
     * @param array $types  field name => type
     * @return array    field name => [type, value]
     */
    private function getFieldsArray(RecordData $record, array $types) : array
    {
        $fields = [];
        foreach ($record->getTableRow() as $key => $value) {
            if (isset($types[$key])) {
                $fields[$key] = [$types[$key], $value];
            }
        }
        return $fields;
    }
}