<?php declare(strict_types=1);

namespace FAU;

/**
 * Base class for representing objects of database records
 * The functions defined in this base class are intended to be used by the corresponding repository
 *
 * @see RecordRepo
 * @todo: replace 'static' type hints with return types in PHP 8
 */
abstract class RecordData
{
    /**
     * Provide a string of important properties for logging with info level
     * The string should be short enough to be inserted in a log line
     * The class name does not need to be included
     * @return string
     */
    abstract public function info() : string;

    /**
     *  Provide a string of all properties for logging with debug level
     * @return string
     */
    public function debug() : string
    {
        return print_r($this, true);
    }

    /**
     * Get an example instance with default values
     * Used to provide an argument for RecordRepo::queryRecords()
     * @see RecordRepo::queryRecords()
     * @return static
     */
    abstract public static function model();


    /**
     * Get the name of the database table
     */
    abstract public static function getTableName() : string;

    /**
     * Get if the table has a sequence field
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
     * @return static
     */
    abstract public function withTableRow(array $row);

    /**
     * Get the sequence value (if a sequence exists)
     */
    public function getTableSequence() : ?int
    {
        return null;
    }

    /**
     * Get a clone with a sequence value
     * @return static
     */
    public function withTableSequence(int $value)
    {
        return clone $this;
    }
}