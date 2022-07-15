<?php declare(strict_types=1);

namespace FAU\User;

use FAU\User\Data\Education;
use FAU\RecordRepo;
use FAU\RecordData;
use FAU\User\Data\Achievement;
use FAU\User\Data\Person;
use FAU\User\Data\Member;
use FAU\Study\Data\Term;

/**
 * Repository for accessing FAU user data
 * The main data from IDM is stored in a "Person" record
 * @todo replace type hints with union types in PHP 8
 */
class Repository extends RecordRepo
{
    /**
     * Check if a user has a person record assigned
     */
    public function checkUserHasPerson(int $user_id) : bool
    {
        $query = "SELECT 1 FROM fau_user_persons WHERE user_id =" . $this->db->quote($user_id, 'integer');
        return $this->hasRecord($query);
    }

    /**
     * Get the person data of an ILIAS user
     * @return ?Person
     */
    public function getPersonOfUser(int $user_id) : ?RecordData
    {
        $query = "SELECT * FROM fau_user_persons WHERE user_id =" . $this->db->quote($user_id, 'integer');
        return $this->getSingleRecord($query, Person::model());
    }


    /**
     * Delete the educations of a user account (e.g. if user is deleted)
     */
    public function deleteEducationsOfUser(int $user_id) : void
    {
        $this->db->manipulateF("DELETE FROM fau_user_educations WHERE user_id = %s", ['int'], [$user_id]);
    }

    /**
     * Get the educations assigned to a user
     * @return Education[]
     */
    public function getEducationsOfUser(int $user_id, ?string $type = null) : array
    {
        $query = "SELECT * FROM fau_user_educations WHERE user_id = " . $this->db->quote($user_id, 'integer');
        if (isset($type))  {
            $query .= " AND " . $this->db->quoteIdentifier('type') . ' = ' . $this->db->quote($type, 'text');
        }
        return $this->queryRecords($query, Education::model());
    }

    /**
     * Get all achievements
     * @return Achievement[]
     */
    public function getAllAchievements() : array
    {
        return $this->getAllRecords(Achievement::model(), false);
    }

    /**
     * Get the achievements of a person
     * @return Achievement[]
     */
    public function getAchievementsOfPerson(int $person_id) : array
    {
        $query = "SELECT * FROM fau_user_achievements WHERE person_id = " . $this->db->quote($person_id, 'integer');
        return $this->queryRecords($query, Achievement::model());
    }


    /**
     * Get the member records of an ilias object (course or group) which are assigned by campo
     * @return Member[]     indexed by user_id
     */
    public function getMembersOfObject(int $obj_id, bool $useCache = true) : array
    {
        $query = "SELECT * FROM fau_user_members WHERE obj_id = " . $this->db->quote($obj_id, 'integer');
        return $this->queryRecords($query, Member::model(), $useCache, 'user_id');
    }

    /**
     * Get the user ids of the members of an ilias object (course or group) which are assigned by campo
     * The additional conditions can be 1 or 0, null will be ignored
     * Conditions wil be combined with OR
     * @return int[]     indexed by user_id
     */
    public function getUserIdsOfObjectMembers(
        int $obj_id,
        bool $useCache = true,
        ?bool $event_responsible = null,
        ?bool $course_responsible = null,
        ?bool $instructor = null,
        ?bool $individual_instructor = null
    ) : array
    {
        $query = "SELECT user_id FROM fau_user_members WHERE obj_id = " . $this->db->quote($obj_id, 'integer');

        $conditions = [];
        if (isset($event_responsible)) {
            $conditions[] = "event_responsible = " . $this->db->quote($event_responsible, 'integer');
        }
        if (isset($course_responsible)) {
            $conditions[] = "course_responsible = " . $this->db->quote($course_responsible, 'integer');
        }
        if (isset($instructor)) {
            $conditions[] = "instructor = " . $this->db->quote($instructor, 'integer');
        }
        if (isset($individual_instructor)) {
            $conditions[] ="individual_instructor = " . $this->db->quote($individual_instructor, 'integer');
        }
        if (!empty($conditions)) {
            $query .= ' AND (' .implode(' OR ', $conditions) . ')';
        }

        return $this->getIntegerList($query, 'user_id', $useCache);
    }


    /**
     * Get the member records of an ilias user
     * @return Member[]     indexed by obj_id
     */
    public function getMembersOfUser(int $user_id, bool $useCache = true) : array
    {
        $query = "SELECT * FROM fau_user_members WHERE user_id = " . $this->db->quote($user_id, 'integer');
        return $this->queryRecords($query, Member::model(), $useCache, 'obj_id');
    }


    /**
     * Save record data of an allowed type
     * @param Achievement|Education|Person $record
     */
    public function save(RecordData $record)
    {
        $this->replaceRecord($record);
    }


    /**
     * Delete record data of an allowed type
     * @param Achievement|Education|Person $record
     */
    public function delete(RecordData $record)
    {
        $this->deleteRecord($record);
    }
}