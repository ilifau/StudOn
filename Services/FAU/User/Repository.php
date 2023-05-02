<?php declare(strict_types=1);

namespace FAU\User;

use FAU\User\Data\Education;
use FAU\RecordRepo;
use FAU\RecordData;
use FAU\User\Data\Achievement;
use FAU\User\Data\Person;
use FAU\User\Data\Member;
use FAU\Study\Data\Term;
use FAU\User\Data\UserOrgRole;
use FAU\User\Data\UserData;

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
     * Get the person data of an ILIAS user
     * @return Person[]
     */
    public function getPersonsOfUsers(array $user_ids) : array
    {
        $query = "SELECT * FROM fau_user_persons WHERE " . $this->db->in('user_id', $user_ids, false, 'integer');
        return $this->queryRecords($query, Person::model());
    }


    /**
     * Get the applied roles of an  ILIAS user
     * @return UserOrgRole[]
     */
    public function getOrgRolesOfUser(int $user_id) : array
    {
        $query = "SELECT * FROM fau_user_org_roles WHERE user_id =" . $this->db->quote($user_id, 'integer');
        return $this->queryRecords($query, UserOrgRole::model());
    }

    /**
     * Get the educations assigned to a person
     * @param int|null $person_id
     * @param string[]|null $orgunits    show educations of these orgunits (shorttexts)
     * @return Education[]
     */
    public function getEducationsOfPerson(?int $person_id, ?array $orgunits = null) : array
    {
        $query = "SELECT * FROM fau_user_educations WHERE person_id = " . $this->db->quote((int) $person_id, 'integer');
        if (isset($orgunits))  {
            $query .= " AND " . $this->db->in('orgunit', $orgunits, false,'text');
        }
        return $this->queryRecords($query, Education::model());
    }

    /**
     * Get the educations assigned to persons
     * @param int[] $person_ids
     * @param string[]|null $orgunits    show educations of these orgunits (shorttexts)
     * @return Education[]
     */
    public function getEducationsOfPersons(array $person_ids, ?array $orgunits = null) : array
    {
        $query = "SELECT * FROM fau_user_educations WHERE " . $this->db->in('person_id', $person_ids, false,'integer');
        if (isset($orgunits))  {
            $query .= " AND " . $this->db->in('orgunit', $orgunits, false,'text');
        }
        return $this->queryRecords($query, Education::model());
    }

    /**
     * Get the achievements of a person
     * @return Achievement[]
     */
    public function getAchievementsOfPerson(?int $person_id) : array
    {
        $query = "SELECT * FROM fau_user_achievements WHERE person_id = " . $this->db->quote((int) $person_id, 'integer');
        return $this->queryRecords($query, Achievement::model());
    }

    /**
     * Get a member record
     * @return Member|null
     */
    public function getMember(int $obj_id, int $user_id, ?Member $default = null) : ?RecordData
    {
        $query = "SELECT * FROM fau_user_members WHERE obj_id = " . $this->db->quote($obj_id, 'integer')
            . " AND user_id=" . $this->db->quote($user_id, 'integer');
        return $this->getSingleRecord($query, Member::model(), $default, false);
    }

    /**
     * Get the member records of an ilias user
     * @return Member[]     indexed by obj_id
     */
    public function getMembersOfUser(int $user_id, bool $useCache = true) : array
    {
        $query = "SELECT * FROM fau_user_members WHERE user_id = " . $this->db->quote($user_id, 'integer');
        return $this->queryRecords($query, Member::model(), $useCache, true, 'obj_id');
    }

    /**
     * Get the member records of an ilias object (course or group) which are assigned by campo
     * @return Member[]     indexed by user_id
     */
    public function getMembersOfObject(int $obj_id, ?int $user_id = null, bool $useCache = true) : array
    {
        $query = "SELECT * FROM fau_user_members WHERE obj_id = " . $this->db->quote($obj_id, 'integer');
        if (isset($user_id)) {
            $query .= " AND user_id = " . $this->db->quote($user_id, 'integer');
        }
        return $this->queryRecords($query, Member::model(), $useCache, true, 'user_id');
    }

    /**
     * Get the member records of ilias objects (courses or groups) which are assigned to a campo event for a term
     * @return Member[]
     */
    public function getMembersOfEventInTerm(int $event_id, Term $term, ?int $user_id = null, bool $useCache = false) : array
    {
        $query = "SELECT m.* FROM fau_user_members m
            JOIN fau_study_courses c on c.ilias_obj_id = m.obj_id
            WHERE c.event_id = " . $this->db->quote($event_id, 'integer')
            . " AND c.term_year = " . $this->db->quote($term->getYear(), 'integer')
            . " AND c.term_type_id = " . $this->db->quote($term->getTypeId(), 'integer');

        if (isset($user_id)) {
            $query .= " AND m.user_id = " . $this->db->quote($user_id, 'integer');
        }
        return $this->queryRecords($query, Member::model(), $useCache);
    }

    /**
     * Move the stored memberships from one object to another
     */
    public function moveMembers(int $from_obj_id, int $to_obj_id)
    {
        // use REPLACE and DELETE instead of UPDATE to avoid duplicate primary key errors
        $query = "REPLACE INTO fau_user_members(obj_id, user_id, module_id, course_responsible, instructor, individual_instructor)
        SELECT %s, user_id, module_id, course_responsible, instructor, individual_instructor
        FROM fau_user_members
        WHERE obj_id = %s";
        $this->db->manipulateF($query, ['integer', 'integer'], [$to_obj_id, $from_obj_id]);

        $query = "DELETE FROM fau_user_members WHERE obj_id = %s";
        $this->db->manipulateF($query, ['integer'], [$from_obj_id]);
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
     * Get the module ids selected by participants
     * @param int[] $obj_ids
     * @return int[] selected module_ids (indexed by the user ids)
     */
    public function getSelectedModuleIdsOfMembers(array $obj_ids) : array
    {
        if (empty($obj_ids)) {
            return [];
        }
        $module_ids = [];
        $query = "SELECT user_id, module_id FROM fau_user_members WHERE module_id IS NOT NULL
            AND " . $this->db->in('obj_id', $obj_ids, false, 'integer');
        $result = $this->db->query($query);
        while ($row = $this->db->fetchAssoc($result)) {
            $module_ids[$row['user_id']] = $row['module_id'];
        }
        return $module_ids;
    }

    /**
     * Get the login of an account ther has an identifier identifiers as login, external account or idle external account
     */
    public function findLoginWithUsedIdentifier(string $identifier, int $ignore_user_id) : ?string
    {
        $query = "SELECT login FROM usr_data " .
            " WHERE usr_id <> " . $this->db->quote($ignore_user_id, 'integer') .
            " AND ( login = " . $this->db->quote($identifier, 'text') .
            "       OR ext_account = " . $this->db->quote($identifier, 'text') .
            "       OR idle_ext_account = " . $this->db->quote($identifier, 'text') .
            " )";

        $result = $this->db->query($query);
        while ($row = $this->db->fetchAssoc($result)) {
            return $row['login'];
        }
        return null;
    }

    /**
     * Find the logins of local accounts with a given firstname and lastname
     * @return string[]
     */
    public function findLocalLoginsByName(?string $firstname, ?string $lastname) : array
    {
        $query = "SELECT login FROM usr_data WHERE (auth_mode = 'local' OR auth_mode = 'default)";
        if (!empty($firstname)) {
            $query .= " AND firstname = " . $this->db->quote($firstname, 'text');
        }
        if (!empty($lastname)) {
            $query .= " AND lastname = " . $this->db->quote($lastname, 'text');
        }
        $result = $this->db->query($query);

        $logins = [];
        while ($row = $this->db->fetchAssoc($result)) {
            $logins[] = $row['login'];
        }
        return $logins;
    }


    /**
     * Get the data of users
     * @param int[] $user_ids
     * @return UserData[]
     */
    public function getUserData(array $user_ids) : array
    {
        $query = "SELECT usr_id, login, firstname, lastname, gender, email, matriculation FROM usr_data WHERE "
            . $this->db->in('usr_id', $user_ids, false, 'integer');
        return $this->queryRecords($query, UserData::model(), false, true);
    }

    /**
     * Get the data of user accounts for person ids
     * @param int[] $person_ids
     * @return UserData[]
     */
    public function getUserDataOfPersons(array $person_ids) : array
    {
        $query = "SELECT u.usr_id, u.login, u.firstname, u.lastname, u.gender, u.email, u.matriculation 
            FROM usr_data u
            JOIN fau_user_persons p ON p.user_id = u.usr_id
            WHERE " . $this->db->in('person_id', $person_ids, false, 'integer');
        return $this->queryRecords($query, UserData::model());

    }

    /**
     * Add the info about public profile to the data of users
     * @param UserData[] $users
     * @return UserData[]
     */
    public function addPublicProfile(array $users) : array
    {
        $query = "SELECT usr_id FROM usr_pref WHERE keyword = 'public_profile' AND (value = 'y' OR value = 'g')
            AND " . $this->db->in('usr_id', array_keys($users), false, 'integer');

        foreach ($this->getIntegerList($query, 'usr_id') as $usr_id) {
            $users[$usr_id] = $users[$usr_id]->withPublicProfile(true);
        }
        return $users;
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
     * @param Achievement|Education|Person|UserOrgRole|Member $record
     */
    public function delete(RecordData $record)
    {
        $this->deleteRecord($record);
    }
}