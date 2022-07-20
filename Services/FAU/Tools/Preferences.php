<?php

namespace FAU\Tools;

use ILIAS\DI\Container;
use ilSession;
use FAU\Study\Data\SearchCondition;

class Preferences
{
    const TERM_FOR_MY_MEMBERSHIPS = 'fau_term_for_my_memberships';
    const SEARCH_CONDITION = 'fau_search_condition';

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
        return SearchCondition::from((array) json_decode($this->getPreference(self::SEARCH_CONDITION), true));
    }

    /**
     * Set the search condition
     * @param SearchCondition $condition
     */
    public function setSearchCondition(SearchCondition $condition)
    {
        $this->setPreference(self::SEARCH_CONDITION, json_encode($condition->row()));
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