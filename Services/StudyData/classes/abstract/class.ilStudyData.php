<?php
/* fau: studyData - new class ilStudyData. */

/**
 * Base class for all study related data
 */
abstract class ilStudyData
{

    /**
     * Static cache
     * (needs to be redefined in all child classes, access with static::$cache)
     * @var array  user_id => ilStudyData[]
     */
    protected static $cache;


    /** @var integer */
    public $user_id;


    /**
     * Read all data for a user
     * @param integer   $user_id
     * @return static[]
     */
    abstract public static function _read($user_id) : array;

    /**
     * Check if a user has data
     * @param $user_id
     * @return int
     */
    abstract public static function _count($user_id) : int;

    /**
     * Delete the data of a user
     * @param int $user_id
     */
    abstract public static function _delete($user_id);

    /**
     * Get the textual description of the data
     */
    abstract public function getText() : string;

    /**
     * Write the data of an option
     */
    abstract public function write();

    /**
     * Get the data (with cache)
     * @param int $user_id
     * @return static[]
     */
    public static function _get($user_id)
    {
        if (!isset(static::$cache[$user_id])) {
            static::$cache[$user_id] = static::_read($user_id);
        }
        return static::$cache[$user_id];
    }

    /**
     * Check if a user has data (with cache)
     * @param $user_id
     * @return bool
     */
    public static function _has($user_id) : bool
    {
        if (!empty(static::$cache[$user_id])) {
            return true;
        }

        return (static::_count($user_id)) > 0;
    }


    /**
     * Clone the data
     * @param $from_user_id
     * @param $to_user_id
     */
    public static function _clone($from_user_id, $to_user_id)
    {
        foreach (static::_get($from_user_id) as $data) {
            $clone = clone $data;
            $clone->user_id = $to_user_id;
            $clone->write();
        }
    }
}
