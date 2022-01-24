<?php
/* fau: studyCond - new class ilStudyCond. */

/**
 * Basee class for all study related conditions
 */
abstract class ilStudyCond
{
    /**
     * Static cache
     * (needs to be redefined in all child classes, access with static::$cache)
     * @var array  obj_id => ilStudyCond[]
     */
    protected static $cache;


    /** @var integer */
    public $obj_id;

    /** @var integer */
    public $cond_id;


    /**
     * Constructor
     *
     * @param int $a_cond_id
     */
    public function __construct($a_cond_id = null)
    {
        if ($this->cond_id = $a_cond_id) {
            $this->read();
        }
    }


    /**
     * Read all conditions of an object
     * @param integer   $obj_id
     * @return static[]
     */
    abstract public static function _read($obj_id) : array;


    /**
     * Check if an object has conditions
     * @param $obj_id
     * @return int
     */
    abstract public static function _count($obj_id) : int;


    /**
     * Delete the data of an object
     * @param int $obj_id
     */
    abstract public static function _delete($obj_id);


    /**
     * Get the textual description of the condition
     */
    abstract public function getText() : string;


    /**
     * Read a condition from the database
     */
    abstract public function read();


    /**
     * Write a condition to the database
     */
    abstract public function write();

    /**
     *Delete a condition in the database
     */
    abstract public function delete();


    /**
     *
     * @param ilStudyData[] $data
     * @return bool
     */
    abstract public function check(array $data) : bool;


    /**
     * Get the conditions (with cache)
     * @param int $obj_id
     * @return static[]
     */
    public static function _get($obj_id)
    {
        if (!isset(static::$cache[$obj_id])) {
            static::$cache[$obj_id] = static::_read($obj_id);
        }
        return static::$cache[$obj_id];
    }

    /**
     * Check if an object has conditions (with cache)
     * @param $obj_id
     * @return bool
     */
    public static function _has($obj_id) : bool
    {
        if (!empty(static::$cache[$obj_id])) {
            return true;
        }

        return (static::_count($obj_id)) > 0;
    }


    /**
     * Clone the conditions
     * @param $from_obj_id
     * @param $to_obj_id
     */
    public static function _clone($from_obj_id, $to_obj_id)
    {
        foreach (static::_get($from_obj_id) as $data) {
            $clone = clone $data;
            $clone->cond_id = null;
            $clone->obj_id = $to_obj_id;
            $clone->write();
        }
    }
}
