<?php

namespace FAU\Sync;

use FAU\RecordRepo;
use FAU\Study\Data\Term;
use FAU\Staging\Data\StudOnMember;
use FAU\Staging\Data\StudOnCourse;
use FAU\RecordData;

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
     * Get the ids of existing users for the responsible persons of the event of a course
     * @return int[]
     */
    public function getUserIdsOfEventResponsibles(int $course_id) : array
    {
        $query = "SELECT p.user_id FROM fau_user_persons p"
            ." JOIN fau_study_event_resps e ON e.person_id = p.person_id"
            ." JOIN fau_study_courses c ON c.event_id = e.event_id"
            ." WHERE c.course_id =" . $this->db->quote($course_id, 'integer');
        return $this->getIntegerList($query, 'user_id');
    }

    /**
     * Get the ids of existing users for the responsible persons of a course
     * @return int[]
     */
    public function getUserIdsOfCourseResponsibles(int $course_id) : array
    {
        $query = "SELECT p.user_id FROM fau_user_persons p"
            ." JOIN fau_study_course_resps c ON c.person_id = p.person_id"
            ." WHERE c.course_id =" . $this->db->quote($course_id, 'integer');
        return $this->getIntegerList($query, 'user_id');
    }

    /**
     * Get the ids of existing users for the instructors of a course
     * @return int[]
     */
    public function getUserIdsOfInstructors(int $course_id) : array
    {
        $query = "SELECT p.user_id FROM fau_user_persons p"
            ." JOIN fau_study_instructors i ON i.person_id = p.person_id"
            ." JOIN fau_study_plan_dates d ON d.planned_dates_id = i.planned_dates_id"
            ." WHERE d.course_id =" . $this->db->quote($course_id, 'integer');
        return $this->getIntegerList($query, 'user_id');
    }

    /**
     * Get the ids of existing users for the individual instructors in a course
     * @return int[]
     */
    public function getUserIdsOfIndividualInstructors(int $course_id) : array
    {
        $query = "SELECT p.user_id FROM fau_user_persons p"
            ." JOIN fau_study_indi_insts i ON i.person_id = p.person_id"
            ." JOIN fau_study_indi_dates id ON id.individual_dates_id = i.individual_dates_id"
            ." JOIN fau_study_plan_dates pd ON pd.planned_dates_id = id.planned_dates_id"
            ." WHERE pd.course_id =" . $this->db->quote($course_id, 'integer');
        return $this->getIntegerList($query, 'user_id');
    }

    /**
     * Get the ids of courses in a term where a user is responsible for the event
     * @return int[]
     */
    public function getCourseIdsOfEventResponsible(int $user_id, Term $term) : array
    {
        $query = "SELECT c.course_id FROM fau_user_persons p"
            ." JOIN fau_study_event_resps e ON e.person_id = p.person_id"
            ." JOIN fau_study_courses c ON c.event_id = e.event_id"
            ." WHERE p.user_id = " . $this->db->quote($user_id, 'integer')
            ." AND c.term_year = " . $this->db->quote($term->getYear(), 'integer')
            ." AND c.term_type_id = " . $this->db->quote($term->getTypeId(), 'integer');
        return $this->getIntegerList($query, 'course_id');
    }

    /**
     *  Get the ids of courses in a term where a user is responsible for the course
     * @return int[]
     */
    public function getCourseIdsOfCourseResponsible(int $user_id, Term $term) : array
    {
        $query = "SELECT c.course_id FROM fau_user_persons p"
            ." JOIN fau_study_course_resps r ON r.person_id = p.person_id"
            ." JOIN fau_study_courses c ON c.course_id = r.course_id"
            ." WHERE p.user_id = " . $this->db->quote($user_id, 'integer')
            ." AND c.term_year = " . $this->db->quote($term->getYear(), 'integer')
            ." AND c.term_type_id = " . $this->db->quote($term->getTypeId(), 'integer');
        return $this->getIntegerList($query, 'course_id');
    }

    /**
     * Get the ids of courses in a term where a user is instructor
     * @return int[]
     */
    public function getCourseIdsOfInstructor(int $user_id, Term $term) : array
    {
        $query = "SELECT c.course_id FROM fau_user_persons p"
            ." JOIN fau_study_instructors i ON i.person_id = p.person_id"
            ." JOIN fau_study_plan_dates d ON d.planned_dates_id = i.planned_dates_id"
            ." JOIN fau_study_courses c ON c.course_id = d.course_id"
            ." WHERE p.user_id = " . $this->db->quote($user_id, 'integer')
            ." AND c.term_year = " . $this->db->quote($term->getYear(), 'integer')
            ." AND c.term_type_id = " . $this->db->quote($term->getTypeId(), 'integer');
        return $this->getIntegerList($query, 'course_id');
    }

    /**
     * Get the ids of existing users for the individual instructors in a course
     * @return int[]
     */
    public function getCourseIdsOfIndividualInstructor(int $user_id, Term $term) : array
    {
        $query = "SELECT c.course_id FROM fau_user_persons p"
            ." JOIN fau_study_indi_insts i ON i.person_id = p.person_id"
            ." JOIN fau_study_indi_dates id ON id.individual_dates_id = i.individual_dates_id"
            ." JOIN fau_study_plan_dates pd ON pd.planned_dates_id = id.planned_dates_id"
            ." JOIN fau_study_courses c ON c.course_id = pd.course_id"
            ." WHERE p.user_id =" . $this->db->quote($user_id, 'integer')
            ." AND c.term_year = " . $this->db->quote($term->getYear(), 'integer')
            ." AND c.term_type_id = " . $this->db->quote($term->getTypeId(), 'integer');
        return $this->getIntegerList($query, 'course_id');
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
     * Remove the import id from an object
     */
    public function removeObjectFauImportId(int $obj_id)
    {
        $query = "UPDATE object_data set import_id = NULL WHERE import_id LIKE 'FAU%'"
                ." AND obj_id = ". $this->db->quote($obj_id, 'integer');
        $this->db->manipulate($query);
    }

    /**
     * Get the ILIAS categories where roles can be assigned
     * @return string[] ref_ids indexed by fauorg_nr
     */
    public function getAssignableCategories() : array
    {
        $query = "SELECT fauorg_nr, ilias_ref_id FROM fau_org_orgunits WHERE ilias_ref_id IS NOT NULL AND assignable = 1 AND no_manager = 0";
        $result = $this->db->query($query);
        $categories = [];
        while ($row = $this->db->fetchAssoc($result)) {
            $categories[$row['fauorg_nr']] = $row['ilias_ref_id'];
        }
        return $categories;
    }

    /**
     * Get the members of all courses in a term for sending back to campo
     * The status is taken from the learning progress because groups don't have a setting for 'passed' in the member list
     * In courses the status from the member list is written to the learning progress
     * The status 'failed' can't be set manually yet
     * @param Term $term
     * @return StudOnMember[]
     */
    public function getMembersOfCoursesToSyncBack(Term $term) : array
    {
        $query = "
            SELECT c.course_id, p.person_id, m.module_id, 'registered' status, c.term_year, c.term_type_id,
            CASE s.status WHEN 2 THEN 'passed' WHEN 3 THEN 'failed' ELSE 'registered' END AS status
            FROM fau_study_courses c
            JOIN object_reference r ON r.obj_id = c.ilias_obj_id
            JOIN rbac_fa fa ON fa.parent = r.ref_id AND fa.assign = 'y'
            JOIN object_data o ON o.obj_id = fa.rol_id AND (o.title LIKE 'il_crs_member%' OR o.title LIKE 'il_grp_member%')
            JOIN rbac_ua ua ON ua.rol_id = fa.rol_id
            JOIN fau_user_persons p ON p.user_id = ua.usr_id AND p.person_id IS NOT NULL
            LEFT JOIN fau_user_members m ON m.obj_id = r.obj_id AND m.user_id = ua.usr_id
            LEFT JOIN ut_lp_marks s ON s.obj_id = r.obj_id AND s.usr_id = p.user_id
            WHERE c.term_year = " . $this->db->quote($term->getYear(), 'integer') . "
            AND c.term_type_id = ". $this->db->quote($term->getTypeId(), 'integer');

        return $this->queryRecords($query, StudOnMember::model(), false, true);
    }

    /**
     * Get the maximum members of all courses in a term for sending back to campo
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
     * Get the ids of courses in a term where members or settings can be sent back to campo
     * @param Term $term
     * @return array
     */
    public function getCourseIdsToSyncBack(Term $term) : array
    {
        $query = "SELECT course_id FROM fau_study_courses"
            ." WHERE term_year = " . $this->db->quote($term->getYear(), 'integer')
            ." AND term_type_id = " . $this->db->quote($term->getTypeId(), 'integer')
            ." AND ilias_obj_id IS NOT NULL";
        return $this->getIntegerList($query, 'course_id', false);
    }
}