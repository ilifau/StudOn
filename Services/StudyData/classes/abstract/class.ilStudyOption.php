<?php
/* fau: studyData - new class ilStudyOption. */

/**
 * Base class for study options
 */
abstract class ilStudyOption
{
    /** @var integer */
    public $id;

    /** @var string */
    public $title;

    /**
     * Static cache
     * (needs to be redefined in all child classes, access with static::$cache)
     * @var static[]  Options indexed by id
     */
    protected static $cache;

    /**
     * Indicates that all options are already cached
     * (needs to be redefined in all child classes, access with static::$allCached)
     * @var bool
     */
    protected static $allCached;

    /**
     * Read options given by ids (or null if ids are empty)
     * Return array must be indexed by the id
     * This will not use the cache
     *
     * @param int[] $ids
     * @return static[]  indexed by id
     */
    abstract protected static function _read(array $ids = null) : array;

    /**
     * Delete options given by ids (or null if ids are empty)
     * This will not use the cache
     *
     * @param int[] $ids
     */
    abstract protected static function _delete(array $ids = null);

    /**
     * Write the data of an option
     */
    abstract protected function write();


    /**
     * Get the id of an option
     * @return int
     */
    abstract protected function getId() : int;

    /**
     * Get the text of an option
     * @return string
     */
    abstract protected function getText() : string;

    /**
     * Get the text for the select options
     * Can be overridden to vary between texts displayed and in select field
     * @return string
     */
    protected function getSelectText()
    {
        return $this->getText();
    }

    /**
     * Get the options for a select field
     * This does not need to use a cache
     *
     * @param int $emptyId   add a 'please select' at the beginning with that id
     * @param int $chosenId   add a 'unknown option' at the end if that id is not in the list
     * @return array    id => text
     */
    public static function _getSelectOptions(int $emptyId = null, $chosenId = null) : array
    {
        global $DIC;
        $lng = $DIC->language();

        $return = [];

        if (isset($emptyId)) {
            $return[$emptyId] = $lng->txt("please_select");
        }

        $options = static::_getAll();
        foreach ($options as $option) {
            $return[$option->getId()] = $option->getSelectText();
        }

        if (isset($chosenId) && !isset($options[$chosenId]) && (!isset($emptyId) || $chosenId != $emptyId)) {
            $option = new static;
            $option->id = $chosenId;
            $return[$option->getId()] = $option->getSelectText();
        }
        return $return;
    }

    /**
     * Get all options (with cache)
     * @return static[]
     */
    protected static function _getAll()
    {
        if (!static::$allCached) {
            static::$cache = static::_read();
            static::$allCached = true;
        }
        return static::$cache;
    }

    /**
     * Get an option (with cache)
     * @param int $id
     * @return static|null
     */
    public static function _get($id = null)
    {
        if (isset(static::$cache[$id])) {
            return static::$cache[$id];
        }

        if (isset($id)) {
            $options = static::_read([$id]);
        }

        if (!empty($options)) {
            static::$cache[$id] = $options[$id];
            return static::$cache[$id];
        }

        return null;
    }

    /**
     * Lookup the text for an option
     * This should use a static cache
     *
     * @param int|null $id
     * @return string
     */
    public static function _lookupText($id) : string
    {
        if (!isset($id)) {
            return '';
        }

        $option = static::_get($id);

        if (isset($option)) {
            return $option->getText();
        } else {
            $option = new static;
            $option->id = $id;
            return $option->getText();
        }
    }
}
