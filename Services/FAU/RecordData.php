<?php declare(strict_types=1);

namespace FAU;

/**
 * Base class for representing objects of database records
 * The functions defined in this base class are intended to be used by the corresponding repository
 * 
 * @see RecordRepo
 */
abstract class RecordData
{
    /**
     * Get the name of the database table
     */
    abstract public static function getTableName() : string;

    /**
     * Get if the table has a sequence
     */
    public static function hasTableSequence() : bool
    {
        return false;
    }

    /**
     * Get the ilDBInterface types of the primary key fields
     * @return array    key field name =>  type
     */
    abstract public static function getTableKeyTypes() : array;

    /**
     * Get the ilDBInterface types of the other fields
     * @return array    other field name =>  type
     */
    abstract public static function getTableOtherTypes() : array;

    /**
     * Get the single row data of a database query
     * @return array   field name => value
     */
    abstract public function getTableRow() : array;

    /**
     * Get a clone with the single row data of a database query
     */
    abstract public function withTableRow(array $row) : self;

    /**
     * Get the sequence value (if a sequence exists)
     */
    public function getTableSequence() : ?int
    {
        return null;
    }

    /**
     * Get a clone with a sequence value
     */
    public function withTableSequence(int $value) : self {
        return clone $this;
    }
}