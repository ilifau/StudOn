<?php

namespace FAU\Tools;

use ILIAS\DI\Container;
use ilCust;
use ilObjUser;

class Settings
{
    const DEFAULT_OWNER_ID = 'default_owner_id';
    const GROUP_DTPL_ID = 'group_dtpl_id';
    const COURSE_DTPL_ID = 'course_dtpl_id';
    const FALLBACK_PARENT_CAT_ID = 'fallback_parent_cat_id';

    protected Container $dic;

    /**
     * @var array cache of settings (key => value)
     */
    protected array $cache = [];


    public function __construct(Container $dic)
    {
        $this->dic = $dic;
    }

    /**
     * Get the default owner id for created objects
     * @return int
     */
    public function getDefaultOwnerId() : int
    {
        if (isset($this->cache[self::DEFAULT_OWNER_ID])) {
            return $this->cache[self::DEFAULT_OWNER_ID];
        }

        $login = (string) ilCust::get('fau_default_owner_login');
        if (empty($value = (int) ilObjUser::_lookupId($login))) {
            $value = 6; // root user as default
        }

        $this->cache[self::DEFAULT_OWNER_ID] = $value;
        return $value;
    }


    /**
     * Get the default didactic template id for group creation
     * @return int
     */
    public function getGroupDidacticTemplateId() : int
    {
        if (isset($this->cache[self::GROUP_DTPL_ID])) {
            return $this->cache[self::GROUP_DTPL_ID];
        }

        $value = (int) ilCust::get('fau_group_dtpl_id');

        $this->cache[self::GROUP_DTPL_ID] = $value;
        return $value;
    }


    /**
     * Get the default didactic template id for course creation
     * @return int
     */
    public function getCourseDidacticTemplateId() : int
    {
        if (isset($this->cache[self::COURSE_DTPL_ID])) {
            return $this->cache[self::COURSE_DTPL_ID];
        }

        $value = (int) ilCust::get('fau_course_dtpl_id');

        $this->cache[self::COURSE_DTPL_ID] = $value;
        return $value;
    }

    /**
     * Get the default didactic template id for course creation
     * @return int
     */
    public function getFallbackParentCatId() : int
    {
        if (isset($this->cache[self::FALLBACK_PARENT_CAT_ID])) {
            return $this->cache[self::FALLBACK_PARENT_CAT_ID];
        }

        $value = (int) ilCust::get('fau_fallback_parent_cat_id');

        $this->cache[self::FALLBACK_PARENT_CAT_ID] = $value;
        return $value;
    }

}