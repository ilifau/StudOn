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
    const EXCLUDE_CREATE_ORG_IDS = 'exclude_create_org_ids';
    const AUTHOR_ROLE_TEMPLATE_ID = 'author_role_template_id';
    const MANAGER_ROLE_TEMPLATE_ID = 'manager_role_template_id';


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
     * Get the template id of the author role
     * @return int
     */
    public function getAuthorRoleTemplateId() : int
    {
        if (isset($this->cache[self::AUTHOR_ROLE_TEMPLATE_ID])) {
            return $this->cache[self::AUTHOR_ROLE_TEMPLATE_ID];
        }

        $value = (int) ilCust::get('fau_author_role_template_id');

        $this->cache[self::AUTHOR_ROLE_TEMPLATE_ID] = $value;
        return $value;
    }

    /**
     * Get the template id of the manager role
     * @return int
     */
    public function getManagerRoleTemplateId() : int
    {
        if (isset($this->cache[self::MANAGER_ROLE_TEMPLATE_ID])) {
            return $this->cache[self::MANAGER_ROLE_TEMPLATE_ID];
        }

        $value = (int) ilCust::get('fau_manager_role_template_id');

        $this->cache[self::MANAGER_ROLE_TEMPLATE_ID] = $value;
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


    /**
     * Get the ids of org units for which the creation of courses shoud be exluded
     * Their child units should also be excluded
     * @return int[]
     */
    public function getExcludeCreateOrgIds() : array
    {
        if (isset($this->cache[self::EXCLUDE_CREATE_ORG_IDS])) {
            return $this->cache[self::EXCLUDE_CREATE_ORG_IDS];
        }

        $list = explode(',', (string) ilCust::get('fau_exclude_create_org_ids'));
        $ids = [];
        foreach ($list as $entry) {
            $ids[] = (int) trim($entry);
        }

        $this->cache[self::EXCLUDE_CREATE_ORG_IDS] = $ids;
        return $ids;
    }

}