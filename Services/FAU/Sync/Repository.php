<?php

namespace FAU\Sync;

use FAU\RecordRepo;
use FAU\Study\Data\ImportId;
use FAU\Study\Data\Term;
use FAU\Staging\Data\StudOnMember;
use FAU\Staging\Data\StudOnCourse;
use FAU\RecordData;
use FAU\User\Data\Member;

/**
 * Repository for database access across the sub services used in the synchronisation
 */
class Repository extends RecordRepo
{

    /**
     * Get all records of a type for synchronisation
     * The array is indexed by keys generated with the key() function of the records
     * @return RecordData[]
     */
    public function getAllForSync(RecordData $model) : array
    {
        return $this->getAllRecords($model, false, true);
    }


    /**
     * Get the ids of courses or users with a specific role assignment
     * @param string    $role   role to query for (see Member)
     * @param string    $key    key to return (user_id oder course_id)
     * @param int|null  $user_id        for condition
     * @param int|null  $course_id      for condition
     * @param int|null  $event_id       for condition
     * @param Term|null $term           for condition
     * @return int[]
     */
    public function getIdsForCampoRoles(
        string $role,
        string $key,
        ?int $user_id = null,
        ?int $course_id = null,
        ?int $event_id = null,
        ?Term $term = null
    ) : array
    {
        switch ($key) {
            case 'user_id':
                $field = 'p.user_id';
                break;
            case 'course_id':
                $field = 'c.course_id';
                break;
            default:
                return [];
        }

        switch ($role) {
            case Member::ROLE_EVENT_RESPONSIBLE:
                $query = "SELECT %s 
                    FROM fau_user_persons p
                    JOIN fau_study_event_resps e ON e.person_id = p.person_id
                    JOIN fau_study_courses c ON c.event_id = e.event_id";
                    break;

            case Member::ROLE_COURSE_RESPONSIBLE:
                $query = "SELECT %s
                    FROM fau_user_persons p
                    JOIN fau_study_course_resps r ON r.person_id = p.person_id
                    JOIN fau_study_courses c ON c.course_id = r.course_id";
                    break;

                case Member::ROLE_INSTRUCTOR:
                $query = "SELECT %s 
                    FROM fau_user_persons p
                    JOIN fau_study_instructors i ON i.person_id = p.person_id
                    JOIN fau_study_plan_dates d ON d.planned_dates_id = i.planned_dates_id
                    JOIN fau_study_courses c ON c.course_id = d.course_id";
                    break;

            case Member::ROLE_INDIVIDUAL_INSTRUCTOR:
                $query = "SELECT %s 
                    FROM fau_user_persons p
                    JOIN fau_study_indi_insts i ON i.person_id = p.person_id
                    JOIN fau_study_indi_dates id ON id.individual_dates_id = i.individual_dates_id
                    JOIN fau_study_plan_dates pd ON pd.planned_dates_id = id.planned_dates_id
                    JOIN fau_study_courses c ON c.course_id = pd.course_id";
                    break;
            default:
                return [];
        }

        $conditions = [];
        if (isset($user_id)) {
            $conditions[] = "p.user_id = " . $this->db->quote($user_id, 'integer');
        }
        if (isset($course_id)) {
            $conditions[] = "c.course_id =" . $this->db->quote($course_id, 'integer');
        }
        if (isset($event_id)) {
            $conditions[] = "c.event_id = " . $this->db->quote($event_id, 'integer');
        }
        if (isset($term)) {
            $conditions[] = "c.term_year = " . $this->db->quote($term->getYear(), 'integer');
            $conditions[] = "c.term_type_id = " . $this->db->quote($term->getTypeId(), 'integer');
        }

        $query = sprintf($query, $field) . (empty($conditions) ? '' : " WHERE " . implode(' AND ', $conditions));
        return $this->getIntegerList($query, $key);
    }



    /**
     * Get the ids of existing ilias objects for an event in a term
     * @return int[]
     */
    public function getObjectIdsForEventInTerm(int $event_id, Term $term, $useCache = true) : array
    {
        $query = "SELECT c.ilias_obj_id FROM fau_study_courses c"
            . " JOIN object_reference r ON r.obj_id = c.ilias_obj_id AND r.deleted IS NULL"
            . " WHERE c.event_id = " . $this->db->quote($event_id, 'integer')
            . " AND c.term_year = " . $this->db->quote($term->getYear(), 'integer')
            . " AND c. term_type_id = " . $this->db->quote($term->getTypeId(), 'integer');
        return $this->getIntegerList($query, 'ilias_obj_id', $useCache = true);
    }

    /**
     * Get the ids of RBAC operations
     * @param string[] $names
     * @return int[]
     */
    public function getRbacOperationIds(array $names) : array
    {
        $query = "SELECT ops_id FROM rbac_operations WHERE " . $this->db->in('operation', $names, false, 'text');
        return $this->getIntegerList($query, 'ops_id');
    }


    /**
     * Reset the last update date of an object to the create date
     */
    public function resetObjectLastUpdate(int $obj_id)
    {
        $query = "UPDATE object_data SET last_update = create_date WHERE obj_id = " . $this->db->quote($obj_id, 'integer');
        $this->db->manipulate($query);
    }

    /**
     * update the FAU import id in an object
     */
    public function updateObjectFauImportId(int $obj_id, ImportId $import_id)
    {
        $query = "UPDATE object_data set import_id = " . $this->db->quote($import_id->toString(), 'text')
            ." WHERE obj_id = ". $this->db->quote($obj_id, 'integer');
        $this->db->manipulate($query);
    }

    /**
     * Remove the FAU import id from an object
     */
    public function removeObjectFauImportId(int $obj_id)
    {
        $query = "UPDATE object_data set import_id = NULL WHERE import_id LIKE 'FAU%'"
                ." AND obj_id = ". $this->db->quote($obj_id, 'integer');
        $this->db->manipulate($query);
    }
    
    
    /**
     * Get the members of all courses in a term for sending back to campo
     *
     * - The status of courses is taken from the 'passed' flag in the obj_members table
     * - The status of groups is taken from the learning progress because groups don't have a separate setting for 'passed'
     * - The status 'failed' can't be processed by campo - it is mapped to 'registered'
     *
     * @return StudOnMember[]
     */
    public function getMembersOfCoursesInTermToSyncBack(Term $term) : array
    {
        $query = "
            SELECT c.course_id, p.person_id, m.module_id, c.term_year, c.term_type_id,
            CASE om.passed WHEN 1 THEN 'passed' ELSE 'registered' END AS `status`
            FROM fau_study_courses c
            JOIN object_reference r ON r.obj_id = c.ilias_obj_id
            JOIN rbac_fa fa ON fa.parent = r.ref_id AND fa.assign = 'y'
            JOIN object_data o ON o.obj_id = fa.rol_id AND o.title LIKE 'il_crs_member%'
            JOIN rbac_ua ua ON ua.rol_id = fa.rol_id
            JOIN fau_user_persons p ON p.user_id = ua.usr_id AND p.person_id IS NOT NULL
            LEFT JOIN fau_user_members m ON m.obj_id = r.obj_id AND m.user_id = ua.usr_id
            LEFT JOIN obj_members om ON om.obj_id = r.obj_id AND om.usr_id = ua.usr_id " .
            " WHERE c.term_year = " . $this->db->quote($term->getYear(), 'integer') .
            " AND c.term_type_id = ". $this->db->quote($term->getTypeId(), 'integer') . "
			UNION
            SELECT c.course_id, p.person_id, m.module_id, c.term_year, c.term_type_id,
            CASE s.status WHEN 2 THEN 'passed' ELSE 'registered' END AS `status`
            FROM fau_study_courses c
            JOIN object_reference r ON r.obj_id = c.ilias_obj_id
            JOIN rbac_fa fa ON fa.parent = r.ref_id AND fa.assign = 'y'
            JOIN object_data o ON o.obj_id = fa.rol_id AND o.title LIKE 'il_grp_member%'
            JOIN rbac_ua ua ON ua.rol_id = fa.rol_id
            JOIN fau_user_persons p ON p.user_id = ua.usr_id AND p.person_id IS NOT NULL
            LEFT JOIN fau_user_members m ON m.obj_id = r.obj_id AND m.user_id = ua.usr_id
            LEFT JOIN ut_lp_marks s ON s.obj_id = r.obj_id AND s.usr_id = p.user_id " .
            " WHERE c.term_year = " . $this->db->quote($term->getYear(), 'integer') .
            " AND c.term_type_id = ". $this->db->quote($term->getTypeId(), 'integer');
        
        return $this->queryRecords($query, StudOnMember::model(), false, true);
    }


    /**
     * Get the passed members of all courses for sending back to campo
     *
     * - The status of courses is taken from the 'passed' flag in the obj_members table
     * - The status of groups is taken from the learning progress because groups don't have a separate setting for 'passed'
     *
     * @return StudOnMember[]
     */
    public function getPassedMembersOfCoursesToSyncBack($passing_module_ids) : array
    {
        $query = "
            SELECT c.course_id, p.person_id, m.module_id, c.term_year, c.term_type_id, 'passed' AS `status`
            FROM fau_study_courses c
            JOIN object_reference r ON r.obj_id = c.ilias_obj_id
            JOIN rbac_fa fa ON fa.parent = r.ref_id AND fa.assign = 'y'
            JOIN object_data o ON o.obj_id = fa.rol_id AND o.title LIKE 'il_crs_member%'
            JOIN rbac_ua ua ON ua.rol_id = fa.rol_id
            JOIN fau_user_persons p ON p.user_id = ua.usr_id AND p.person_id IS NOT NULL
            LEFT JOIN fau_user_members m ON m.obj_id = r.obj_id AND m.user_id = ua.usr_id
            LEFT JOIN obj_members om ON om.obj_id = r.obj_id AND om.usr_id = ua.usr_id 
            WHERE om.passed = 1
            AND (c.send_passed = 'lp' OR ". $this->db->in('m.module_id', $passing_module_ids, false, 'integer') . ") 
			UNION
            SELECT c.course_id, p.person_id, m.module_id, c.term_year, c.term_type_id, 'passed' AS `status`
            FROM fau_study_courses c
            JOIN object_reference r ON r.obj_id = c.ilias_obj_id
            JOIN rbac_fa fa ON fa.parent = r.ref_id AND fa.assign = 'y'
            JOIN object_data o ON o.obj_id = fa.rol_id AND o.title LIKE 'il_grp_member%'
            JOIN rbac_ua ua ON ua.rol_id = fa.rol_id
            JOIN fau_user_persons p ON p.user_id = ua.usr_id AND p.person_id IS NOT NULL
            LEFT JOIN fau_user_members m ON m.obj_id = r.obj_id AND m.user_id = ua.usr_id
            LEFT JOIN ut_lp_marks s ON s.obj_id = r.obj_id AND s.usr_id = p.user_id 
            WHERE s.status = 2
            AND (c.send_passed = 'lp' OR ". $this->db->in('m.module_id', $passing_module_ids, false, 'integer') . ") 
            ";

        return $this->queryRecords($query, StudOnMember::model(), false, true);
    }

    /**
     * Get the 'send_passed' settings of courses in a term where members or settings can be sent back to campo
     * @param Term $term
     * @return array    course_id => send_passed
     */
    public function getCourseSendPassedToSyncBack(Term $term) : array
    {
        $query = "SELECT course_id, send_passed FROM fau_study_courses"
            ." WHERE term_year = " . $this->db->quote($term->getYear(), 'integer')
            ." AND term_type_id = " . $this->db->quote($term->getTypeId(), 'integer');
        $result = $this->db->query($query);

        $send = [];
        while ($row = $this->db->fetchAssoc($result)) {
            $send[$row['course_id']] = $row['send_passed'];
        }

        return $send;
    }
    
    
    /**
     * Get the module ids of modules for which a 'passed' status of members should be sent to campo
     * Members of the same courses which are 'passed' may get a different status sent back depending on the module which they have chosen
     * @return int[]
     */
    public function getModuleIdsToSendPassed() : array
    {
        $query = "
            SELECT distinct module_id 
            FROM fau_study_module_cos mc
            JOIN fau_study_cos c ON c.cos_id = mc.cos_id
            WHERE c.subject = 'Evangelische Theologie'
        ";
        
        return $this->getIntegerList($query, 'module_id');
    }
    

    /**
     * Get the course data with maximum members of all courses in a term for sending back to campo
     * @param Term $term
     * @return StudOnCourse[]
     */
    public function getCoursesToSyncBack(Term $term) : array
    {
        $query = "
            SELECT c.course_id, c.term_year, c.term_type_id,
            CASE s.sub_mem_limit WHEN 1 THEN s.sub_max_members ELSE NULL END AS attendee_maximum
            FROM fau_study_courses c
            JOIN crs_settings s ON s.obj_id = c.ilias_obj_id
            WHERE c.term_year = " . $this->db->quote($term->getYear(), 'integer') . "
            AND c.term_type_id = ". $this->db->quote($term->getTypeId(), 'integer') . "
            UNION
            SELECT c.course_id, c.term_year, c.term_type_id,
            CASE g.registration_mem_limit WHEN 1 THEN g.registration_max_members ELSE NULL END AS attendee_maximum
            FROM fau_study_courses c
            JOIN grp_settings g ON g.obj_id = c.ilias_obj_id
            WHERE c.term_year = " . $this->db->quote($term->getYear(), 'integer') . "
            AND c.term_type_id = ". $this->db->quote($term->getTypeId(), 'integer');

        return $this->queryRecords($query, StudOnCourse::model(), false, true);
    }

    /**
     * Get the timestamps of the maximum individual dates of all courses indexed by course ids
     * @return int[] 
     */
    public function getCoursesMaxDatesAsTimestamps() : array
    {
        $query = "
            SELECT p.course_id, MAX(UNIX_TIMESTAMP(p.enddate)) max_planned_date, MAX(UNIX_TIMESTAMP(i.`date`)) max_individual_date
            FROM fau_study_plan_dates p 
            LEFT JOIN fau_study_indi_dates i ON i.planned_dates_id = p.planned_dates_id 
            GROUP BY p.course_id 
        ";
        $times = [];
        $result = $this->db->query($query);
        while ($row = $this->db->fetchAssoc($result)) {
            $max_planned = $row['max_planned_date'] ?? 0;
            $max_individual = $row['max_individual_date'] ?? 0;
            
            if ($max_planned == 0 && $max_individual == 0) {
                continue;
            }
            else {
                $times[$row['course_id']] = max($max_planned, $max_individual);
            }
        }
        return $times;
    }

    /**
     * Get the timestamps of the maximum individual date of a course
     * @param int $course_id
     * @return int|null
     */
    public function getCourseMaxDateAsTimestamp(int $course_id) : ?int
    {
        $query = "
            SELECT MAX(UNIX_TIMESTAMP(p.enddate)) max_planned_date, MAX(UNIX_TIMESTAMP(i.`date`)) max_individual_date
            FROM fau_study_plan_dates p 
            LEFT JOIN fau_study_indi_dates i ON i.planned_dates_id = p.planned_dates_id    
            WHERE p.course_id =" . $this->db->quote($course_id, 'int') . "       
        ";
        $result = $this->db->query($query);
        if ($row = $this->db->fetchAssoc($result)) {
            $max_planned = $row['max_planned_date'] ?? 0;
            $max_individual = $row['max_individual_date'] ?? 0;

            if ($max_planned == 0 && $max_individual == 0) {
                return null;
            }
            else {
               return max($max_planned, $max_individual);
            }
        }
        return null;
    }
}