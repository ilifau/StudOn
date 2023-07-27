<?php

namespace FAU\Tools;

use ILIAS\DI\Container;
use ilSession;
use FAU\Study\Data\SearchCondition;

class Preferences
{
    const TERM_FOR_MY_MEMBERSHIPS = 'fau_term_for_my_memberships';
    const TERMS_FOR_EXPORTS = 'fau_terms_for_exports';
    const EXPORT_WITH_GROUPS = 'fau_export_with_groups';
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
     * Get the term id filter for the export of course data or members
     * @return string
     */
    public function getTermIdsForExports() : array
    {
        try {
            return (array) json_decode($this->getPreference(self::TERMS_FOR_EXPORTS));
        }
        catch (\Exception $e) {
            return [];
        }
        
    }

    /**
     * Set the term id filter for the export of course data or members
     * @param string[] $term_ids
     */
    public function setTermIdsForExports(array $term_ids)
    {
        $this->setPreference(self::TERMS_FOR_EXPORTS, json_encode($term_ids));
    }


    /**
     * Get if the export function in a category should include groups and not only courses
     */
    public function getExportWithGroups() : bool
    {
        return (bool) $this->getPreference(self::EXPORT_WITH_GROUPS);
    }

    /**
     * Set if the export function in a category should include groups and not only courses
     */
    public function setExportWithGroups(bool $export)
    {
        $this->setPreference(self::EXPORT_WITH_GROUPS, (string) $export);
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