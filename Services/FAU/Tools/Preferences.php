<?php

namespace FAU\Tools;

use ILIAS\DI\Container;
use ilSession;
use FAU\Study\Data\SearchCondition;

class Preferences
{
    const TERM_FOR_MY_MEMBERSHIPS = 'fau_term_for_my_memberships';

    const SEARCH_PATTERN = 'fau_search_pattern';
    const SEARCH_TERM_ID = 'fau_search_term_id';
    const SEARCH_COS_ID = 'fau_search_cos_id';
    const SEARCH_MODULE_ID = 'fau_search_module_id';
    const SEARCH_REF_ID = 'fau_search_ref_id';
    const SEARCH_FITTING = 'fau_search_fitting';


    protected Container $dic;

    public function __construct(Container $dic)
    {
        $this->dic = $dic;
    }

    /**
     * Get the search condition
     */
    public function getSearchCondition() : SearchCondition
    {
        return new SearchCondition(
            (string) $this->getPreference(self::SEARCH_PATTERN),
            (string) $this->getPreference(self::SEARCH_TERM_ID),
            (array) explode(',', $this->getPreference(self::SEARCH_COS_ID)),
            (array) explode(',', $this->getPreference(self::SEARCH_MODULE_ID)),
            (int) $this->getPreference(self::SEARCH_REF_ID),
            (bool) $this->getPreference(self::SEARCH_FITTING)
        );
    }

    /**
     * Set the search condition
     * @param SearchCondition $condition
     */
    public function setSearchCondition(SearchCondition $condition)
    {
        $this->setPreference(self::SEARCH_PATTERN, (string) $condition->getPattern());
        $this->setPreference(self::SEARCH_TERM_ID, (string) $condition->getTermId());
        $this->setPreference(self::SEARCH_COS_ID, (implode(',',$condition->getCosIds())));
        $this->setPreference(self::SEARCH_MODULE_ID, (implode(',',$condition->getModuleIds())));
        $this->setPreference(self::SEARCH_REF_ID, (string) $condition->getIliasRefId());
        $this->setPreference(self::SEARCH_FITTING, (string) $condition->getFitting());
    }

    /**
     * Get the term id filter for the list of memberships
     */
    public function getTermIdForMyMemberships() : string
    {
        return $this->getPreference(self::TERM_FOR_MY_MEMBERSHIPS);
    }

    /**
     * Set the term id filter for the list of memberships
     */
    public function setTermIdForMyMemberships(?string $term_id)
    {
        $this->setPreference(self::TERM_FOR_MY_MEMBERSHIPS, (string) $term_id);
    }


    /**
     * Get a preference from the current user or from the session for anonymous
     */
    protected function getPreference(string $key) : string
    {
        if ($this->dic->user()->isAnonymous()) {
            return (string) ilSession::get($key);
        }
        else {
            return (string) $this->dic->user()->getPref($key);
        }
    }

    /**
     * Set and save a preference for the current user or in the session for anonymous
     */
    protected function setPreference(string $key, string $value)
    {
        if ($this->dic->user()->isAnonymous()) {
            ilSession::set($key, $value);
        }
        else {
            $this->dic->user()->setPref($key, $value);
            $this->dic->user()->writePrefs();
        }

    }

}