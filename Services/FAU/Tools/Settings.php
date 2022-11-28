<?php

namespace FAU\Tools;

use ILIAS\DI\Container;
use ilCust;
use ilObjUser;

class Settings
{
    const DEFAULT_OWNER_ID = 'fau_default_owner_login';
    const GROUP_DTPL_ID = 'fau_group_dtpl_id';
    const COURSE_DTPL_ID = 'fau_course_dtpl_id';
    const FALLBACK_PARENT_CAT_ID = 'fau_fallback_parent_cat_id';
    const MOVE_PARENT_CAT_IDS = 'fau_move_parent_cat_ids';
    const EXCLUDE_CREATE_ORG_IDS = 'fau_exclude_create_org_ids';
    const RESTRICT_CREATE_ORG_IDS = 'fau_restrict_create_org_ids';
    const AUTHOR_ROLE_TEMPLATE_ID = 'fau_author_role_template_id';
    const MANAGER_ROLE_TEMPLATE_ID = 'fau_manager_role_template_id';
    const DIP_QUERY_STATUS = 'fau_dip_query_status';
    const DIP_SET_PROCESSED = 'fau_dip_set_processed';

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
     * Get a cached value from a callable function
     */
    protected function getCachedValue(string $key, callable $function)
    {
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }
        $value = $function();
        $this->cache[$key] = $value;
        return $value;
    }

    /**
     * Get the default owner id for created objects
     */
    public function getDefaultOwnerId() : int
    {
        return  $this->getCachedValue(self::DEFAULT_OWNER_ID, function() {
            $login = (string) ilCust::get(self::DEFAULT_OWNER_ID);
            if (empty($value = (int) ilObjUser::_lookupId($login))) {
                $value = 6; // root user as default
            }
            return $value;
        });
    }

    /**
     * Get the template id of the author role
     */
    public function getAuthorRoleTemplateId() : int
    {
        return $this->getCachedValue(self::AUTHOR_ROLE_TEMPLATE_ID, function() {
            return (int) ilCust::get(self::AUTHOR_ROLE_TEMPLATE_ID);
        });
    }

    /**
     * Get the template id of the manager role
     */
    public function getManagerRoleTemplateId() : int
    {
        return $this->getCachedValue(self::MANAGER_ROLE_TEMPLATE_ID, function() {
            return (int) ilCust::get(self::MANAGER_ROLE_TEMPLATE_ID);
        });
    }

    /**
     * Get the default didactic template id for group creation
     */
    public function getGroupDidacticTemplateId() : int
    {
        return $this->getCachedValue(self::GROUP_DTPL_ID, function() {
            return (int) ilCust::get(self::GROUP_DTPL_ID);
        });
    }

    /**
     * Get the default didactic template id for course creation
     */
    public function getCourseDidacticTemplateId() : int
    {
        return $this->getCachedValue(self::COURSE_DTPL_ID, function() {
            return (int) ilCust::get(self::COURSE_DTPL_ID);
        });
    }

    /**
     * Get the if of the fallback category for lost courses
     */
    public function getFallbackParentCatId() : int
    {
        return $this->getCachedValue(self::FALLBACK_PARENT_CAT_ID, function() {
            return (int) ilCust::get(self::FALLBACK_PARENT_CAT_ID);
        });
    }

    /**
     * Get the id of parent categories for the faculty
     * @return int[]
     */
    public function getMoveParentCatIds() : array
    {
        return  $this->getCachedValue(self::MOVE_PARENT_CAT_IDS, function() {
            $ids = [];
            foreach (explode(',', (string) ilCust::get(self::MOVE_PARENT_CAT_IDS)) as $id) {
                $ids[] = (int) trim($id);
            }
            return $ids;
        });
    }

    /**
     * Get the ids of org units in which the creation of courses should be excluded
     * courses should not be created for these units and their child units
     * @return int[]
     */
    public function getExcludeCreateOrgIds() : array
    {
        return $this->getCachedValue(self::EXCLUDE_CREATE_ORG_IDS, function() {
            $ids = [];
            foreach (explode(',', (string) ilCust::get(self::EXCLUDE_CREATE_ORG_IDS)) as $id) {
                $ids[] = (int) trim($id);
            }
            return $ids;
        });
    }

    /**
     * Get the ids of org units to which the creation of courses should be restricted
     * courses should only be created for these units and their child units
     * @return int[]
     */
    public function getRestrictCreateOrgIds() : array
    {
        return $this->getCachedValue(self::RESTRICT_CREATE_ORG_IDS, function() {
            $ids = [];
            foreach (explode(',', (string) ilCust::get(self::RESTRICT_CREATE_ORG_IDS)) as $id) {
                $ids[] = (int) trim($id);
            }
            return $ids;
        });
    }


    /**
     * Get if the campo synchronization should query for DIP records with a status flag
     */
    public function getDipQueryStatus() : bool
    {
        return $this->getCachedValue(self::DIP_QUERY_STATUS, function() {
            return (bool) ilCust::get(self::DIP_QUERY_STATUS);
        });
    }


    /**
     * Get if the campo synchronization should clear the DIP flag when a record is processed
     */
    public function getDipSetProcessed() : bool
    {
        return $this->getCachedValue(self::DIP_SET_PROCESSED, function() {
            return (bool) ilCust::get(self::DIP_SET_PROCESSED);
        });
    }

}