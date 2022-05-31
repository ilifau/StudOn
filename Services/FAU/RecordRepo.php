<?php declare(strict_types=1);

namespace FAU;

/**
 * Base class of a database repository handling RecordData objects
 * @see RecordData
 */
abstract class RecordRepo
{
    protected \ilDBInterface $db;
    protected \ilLogger $logger;

    public function __construct(\ilDBInterface $a_db, \ilLogger $logger)
    {
        $this->db = $a_db;
        $this->logger = $logger;
    }

    /**
     * Query for records
     * @return RecordData[]
     */
    protected function queryRecords(string $query, RecordData $model, $useCache = true) : array
    {
        $records = [];
        $result = $this->db->query($query);
        while ($row = $this->db->fetchAssoc($result)) {
            $record = $model->withTableRow($row);
            $this->logAction('READ', $record);
            $records[] = $record;
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
        $this->logAction('INSERT', $record);
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
        $this->logAction('REPLACE', $record);
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
        $this->logAction('UPDATE', $record);
        $this->db->update($record::getTableName(), array_merge($key_fields, $other_fields), $key_fields);
    }

    /**
     * Delete a record
     */
    protected function deleteRecord(RecordData $record)
    {
        $conditions[] = '';
        foreach($this->getFieldsArray($record, $record::getTableKeyTypes()) as $quotedKey => $field) {
            $conditions[] = $quotedKey . " = " . $this->db->quote($field[1], $field[0]);
        }
        $query = "DELETE FROM " . $this->db->quoteIdentifier($record::getTableName())
            . " WHERE " . implode(" AND ", $conditions);
        $this->logAction('DELETE', $record);
        $this->db->manipulate($query);
    }

    /**
     * Get the typed field values
     * @param RecordData $record
     * @param array $types  field name => type
     * @return array    quoted field name => [type, value]
     */
    private function getFieldsArray(RecordData $record, array $types) : array
    {
        $fields = [];
        foreach ($record->getTableRow() as $key => $value) {
            if (isset($types[$key])) {
                $fields[$this->db->quoteIdentifier($key)] = [$types[$key], $value];
            }
        }
        return $fields;
    }

    /**
     * Log a database action for the record
     * @param string     $action
     * @param RecordData $record
     */
    protected function logAction(string $action, RecordData $record)
    {
        if ($this->logger->isHandling(\ilLogLevel::DEBUG)) {
            $entry = $action . ' ' . get_class($record) . ' | ' . $record->debug();
            $this->logger->debug($entry);
            if (!\ilContext::usesHTTP()) {
                echo $entry . "\n";
            }
        }
        if ($this->logger->isHandling(\ilLogLevel::INFO)) {
            $entry = $action . ' '. get_class($record) . ' | ' . $record->info();
            $this->logger->info($entry);
            if (!\ilContext::usesHTTP()) {
                echo $entry . "\n";
            }
        }
    }
}