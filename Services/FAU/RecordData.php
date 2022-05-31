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
     * Name of the database table, must be overridden
     */
    protected const tableName = '';

    /**
     * Table uses a sequence, must be overridden
     */
    protected const hasSequence = false;

    /**
     * ilDBInterface types of the primary key fields, must be overridden
     * key field name =>  type
     */
    protected const keyTypes = [];

    /**
     * Get the ilDBInterface types of the other fields, must be overridden
     * other field name =>  type
     */
    protected const otherTypes = [];

    /**
     * Get an example instance with default values
     * Used to provide an argument for RecordRepo::queryRecords()
     * @see RecordRepo::queryRecords()
     * @return static
     */
    abstract public static function model();

    /**
     * Get an instance with the single row data of a database query
     * @return static
     */
    abstract public static function from(array $row);

    /**
     * Get the sequence value (if a sequence exists)
     */
    public function sequence() : ?int
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

    /**
     * Get the name of the database table
     */
    public static function tableName() : string
    {
        return static::tableName;
    }

    /**
     * Get if the table uses a sequence
     */
    public static function tableHasSequence() : bool
    {
        return static::hasSequence;
    }

    /**
     * Get the ilDBInterface types of the primary key fields
     * @return array    key field name =>  type
     */
    public static function tableKeyTypes() : array
    {
        return static::keyTypes;
    }

    /**
     * Get the ilDBInterface types of the other fields
     *  @return array   other field name =>  type
     */
    public static function tableOtherTypes() : array
    {
        return static::otherTypes;
    }

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
}