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

    /**
     * Cached query results
     * @var RecordData[][]  query hash => recordData[]
     */
    private $recordCache = [];

    /**
     * Cached checks for existence
     * @var bool[]      query hash => record exists
     */
    private $boolCache = [];

    /**
     * Constructor
     */
    public function __construct(\ilDBInterface $a_db, \ilLogger $logger)
    {
        $this->db = $a_db;
        $this->logger = $logger;
    }

    /**
     * Check if a query has a record
     */
    protected function hasRecord(string $query, $useCache = true) : bool
    {
        $hash = md5($query);
        if ($useCache && isset($this->boolCache[$hash])) {
            return $this->boolCache[$hash];
        }
        $result = $this->db->query($query);
        $exists = !empty($this->db->fetchAssoc($result));

        if ($useCache) {
            $this->boolCache[$hash] = $exists;
        }
        return $exists;
    }

    /**
     * Get the record objects for standard tables
     * The tables should be short enough to get all records
     * @return RecordData[]
     */
    protected function getAllRecords(RecordData $model, $useCache = true) : array
    {
        $query = "SELECT * FROM " . $this->db->quoteIdentifier($model::tableName());
        return $this->queryRecords($query, $model, $useCache);
    }

    /**
     * Get a single record from a query
     * Optionally provide a default instance
     */
    protected function getSingleRecord(string $query, RecordData $model, ?RecordData $default = null, $useCache = true) : ?RecordData
    {
        foreach ($this->queryRecords($query, $model, $useCache) as $record) {
            return $record;
        }
        return $default;
    }


    /**
     * Query for records
     * If the model has a single key field then this field value is used as the array index
     *
     * @return RecordData[]     key value => RecordData
     */
    protected function queryRecords(string $query, RecordData $model, $useCache = true) : array
    {
        $hash = md5($query);
        if ($useCache && isset($this->recordCache[$hash])) {
            return $this->recordCache[$hash];
        }

        $hasSingleKey = (count($model::tableKeyTypes()) == 1);

        $records = [];
        $result = $this->db->query($query);
        while ($row = $this->db->fetchAssoc($result)) {
            $record = $model::from($row);
            $this->logAction('READ', $record);
            if ($hasSingleKey) {
                $records[$record->key()] = $record;
            }
            else {
                $records[] = $record;
            }
        }

        if ($useCache) {
            $this->recordCache[$hash] = $records;
        }
        return $records;
    }

    /**
     * Insert the records
     * @return RecordData  the inserted record (eventually wit the new sequence number
     */
    protected function insertRecord(RecordData $record) : RecordData
    {
        if ($record::tableHasSequence() && empty($record->sequence())) {
            $record = $record->withTableSequence((int) $this->db->nextId($record::tableName()));
        }
        $types = array_merge($record::tableKeyTypes(), $record::tableOtherTypes());
        $fields = $this->getFieldsArray($record, $types);
        $this->logAction('INSERT', $record);
        $this->db->insert($record::tableName(), $fields);
        return $record;
    }

    /**
     * Insert or update the record
     * @return RecordData  the inserted or updated record (eventually wit the new sequence number)
     */
    protected function replaceRecord(RecordData $record) : RecordData
    {
        if ($record::tableHasSequence() && empty($record->sequence())) {
            $record = $record->withTableSequence((int) $this->db->nextId($record::tableName()));
        }
        $key_fields = $this->getFieldsArray($record, $record::tableKeyTypes());
        $other_fields = $this->getFieldsArray($record, $record::tableOtherTypes());
        $this->logAction('REPLACE', $record);
        $this->db->replace($record::tableName(), $key_fields, $other_fields);
        return $record;
    }

    /**
     * Update a record
     */
    protected function updateRecord(RecordData $record)
    {
        $key_fields = $this->getFieldsArray($record, $record::tableKeyTypes());
        $other_fields = $this->getFieldsArray($record, $record::tableOtherTypes());
        $this->logAction('UPDATE', $record);
        $this->db->update($record::tableName(), array_merge($key_fields, $other_fields), $key_fields);
    }

    /**
     * Delete a record
     */
    protected function deleteRecord(RecordData $record)
    {
        $conditions = [];
        foreach($this->getFieldsArray($record, $record::tableKeyTypes()) as $quotedKey => $field) {
            $conditions[] = $quotedKey . " = " . $this->db->quote($field[1], $field[0]);
        }
        $query = "DELETE FROM " . $this->db->quoteIdentifier($record::tableName())
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
        foreach ($record->row() as $key => $value) {
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
        $entry = $action . ' '. get_class($record) . ' | ' . $record->info();
        if (!\ilContext::usesHTTP()) {
            echo $entry . "\n";
        }

        if ($this->logger->isHandling(\ilLogLevel::DEBUG)) {
            $entry = $action . ' ' . get_class($record) . ' | ' . $record->debug();
            //$this->logger->debug($entry);
        }
        if ($this->logger->isHandling(\ilLogLevel::INFO)) {
            $entry = $action . ' '. get_class($record) . ' | ' . $record->info();
            //$this->logger->info($entry);
        }
    }
}