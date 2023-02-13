<?php declare(strict_types=1);

namespace FAU\Study;

use FAU\RecordRepo;
use FAU\Study\Data\Module;
use FAU\Study\Data\CourseOfStudy;
use FAU\Study\Data\ModuleCos;
use FAU\RecordData;
use FAU\Study\Data\ModuleEvent;
use FAU\Study\Data\CourseResponsible;
use FAU\Study\Data\Event;
use FAU\Study\Data\EventOrgunit;
use FAU\Study\Data\EventResponsible;
use FAU\Study\Data\IndividualDate;
use FAU\Study\Data\IndividualInstructor;
use FAU\Study\Data\Instructor;
use FAU\Study\Data\PlannedDate;
use FAU\Study\Data\DocProgramme;
use FAU\Study\Data\StudyDegree;
use FAU\Study\Data\StudyEnrolment;
use FAU\Study\Data\StudyField;
use FAU\Study\Data\StudyForm;
use FAU\Study\Data\StudySchool;
use FAU\Study\Data\StudySubject;
use FAU\Study\Data\Course;
use FAU\Study\Data\StudyStatus;
use FAU\Study\Data\StudyType;
use FAU\Study\Data\Term;
use FAU\Study\Data\ImportId;
use FAU\Study\Data\SearchCondition;
use FAU\Study\Data\SearchResultEvent;
use FAU\Study\Data\SearchResultObject;

/**
 * Repository for accessing data of study related data
 * @todo replace type hints with union types in PHP 8
 */
class Repository extends RecordRepo
{
    /**
     * @return DocProgramme[]
     */
    public function getDocProgrammes() : array
    {
        return $this->getAllRecords(DocProgramme::model());
    }

    /**
     * @return StudyDegree[]
     */
    public function getStudyDegrees() : array
    {
        return $this->getAllRecords(StudyDegree::model());
    }

    /**
     * @return StudyEnrolment[]
     */
    public function getStudyEnrolments() : array
    {
        return $this->getAllRecords(StudyEnrolment::model());
    }

    /**
     * @return StudyField[]
     */
    public function getStudyFields() : array
    {
        return $this->getAllRecords(StudyField::model());
    }

    /**
     * @return StudyForm[]
     */
    public function getStudyForms() : array
    {
        return $this->getAllRecords(StudyForm::model());
    }

    /**
     * @return StudySchool[]
     */
    public function getStudySchools() : array
    {
        return $this->getAllRecords(StudySchool::model());
    }

    /**
     * @return StudyStatus[]
     */
    public function getStudyStatuses() : array
    {
        return $this->getAllRecords(StudyStatus::model());
    }


    /**
     * @return StudySubject[]
     */
    public function getStudySubjects() : array
    {
        return $this->getAllRecords(StudySubject::model());
    }

    /**
     * @return StudyType[]
     */
    public function getStudyTypes() : array
    {
        return $this->getAllRecords(StudyType::model());
    }

    /**
     * @return Term[]
     */
    public function getStoredTermsByString() : array
    {
        $terms = [];
        /** @var Term $term */
        foreach($this->getAllRecords(Term::model(), true) as $term) {
            $terms[$term->toString()] = $term;
        }
        return $terms;
    }


    /**
     * Get a single Doc Programme
     * @return DocProgramme|null
     */
    public function getDocProgramme(string $prog_code, ?DocProgramme $default = null) : ?RecordData
    {
        $query = "SELECT * from fau_study_doc_progs WHERE prog_code = " . $this->db->quote($prog_code, 'text');
        return $this->getSingleRecord($query, DocProgramme::model(), $default);
    }

    /**
     * Get a single Study Degree
     * @return StudyDegree|null
     */
    public function getStudyDegree(int $id, ?StudyDegree $default = null) : ?RecordData
    {
        $query = "SELECT * from fau_study_degrees WHERE degree_his_id = " . $this->db->quote($id, 'integer');
        return $this->getSingleRecord($query, StudyDegree::model(), $default);
    }

    /**
     * Get a single Study Enrolment
     * @return StudyEnrolment|null
     */
    public function getStudyEnrolment(int $id, ?StudyEnrolment $default = null) : ?RecordData
    {
        $query = "SELECT * from fau_study_enrolments WHERE enrolment_id = " . $this->db->quote($id, 'integer');
        return $this->getSingleRecord($query, StudyEnrolment::model(), $default);
    }


    /**
     * Get a single Study Field
     * @return StudyField|null
     */
    public function getStudyField(int $id, ?StudyField $default = null) : ?RecordData
    {
        $query = "SELECT * from fau_study_fields WHERE field_id = " . $this->db->quote($id, 'integer');
        return $this->getSingleRecord($query, StudyField::model(), $default);
    }

    /**
     * Get a single Study Form
     * @return StudyForm|null
     */
    public function getStudyForm(int $id, ?StudyForm $default = null) : ?RecordData
    {
        $query = "SELECT * from fau_study_forms WHERE form_id = " . $this->db->quote($id, 'integer');
        return $this->getSingleRecord($query, StudyForm::model(), $default);
    }


    /**
     * Get a single Study School
     * @return StudySchool|null
     */
    public function getStudySchool(int $id, ?StudySchool $default = null) : ?RecordData
    {
        $query = "SELECT * from fau_study_schools WHERE school_his_id = " . $this->db->quote($id, 'integer');
        return $this->getSingleRecord($query, StudySchool::model(), $default);
    }

    /**
     * Get a single Study Status
     * @return StudyStatus|null
     */
    public function getStudyStatus(int $id, ?StudyStatus $default = null) : ?RecordData
    {
        $query = "SELECT * from fau_study_status WHERE status_his_id = " . $this->db->quote($id, 'integer');
        return $this->getSingleRecord($query, StudyStatus::model(), $default);
    }

    /**
     * Get a single Study Subject
     * @return StudySubject|null
     */
    public function getStudySubject(int $id, ?StudySubject $default = null) : ?RecordData
    {
        $query = "SELECT * from fau_study_subjects WHERE subject_his_id = " . $this->db->quote($id, 'integer');
        return $this->getSingleRecord($query, StudySubject::model(), $default);
    }

    /**
     * Get a single Study Type
     * @return StudyType|null
     */
    public function getStudyType(string $uniquename, ?StudyType $default = null) : ?RecordData
    {
        $query = "SELECT * from fau_study_types WHERE type_uniquename = " . $this->db->quote($uniquename, 'text');
        return $this->getSingleRecord($query, StudyType::model(), $default);
    }


    /**
     * Gat a single Event
     * @return Event|null
     */
    public function getEvent(int $event_id, ?Event $default = null) : ?RecordData
    {
        $query = "SELECT * from fau_study_events WHERE event_id = " . $this->db->quote($event_id, 'integer');
        return $this->getSingleRecord($query, Event::model(), $default);
    }

    /**
     * Get the list of responsible org units for an event
     * The list is ordered by the relation id which leads to preference by assigning records
     * @return EventOrgunit[]
     */
    public function getEventOrgunitsByEventId(int $event_id) : array
    {
        $query = "SELECT * from fau_study_event_orgs WHERE event_id = " . $this->db->quote($event_id, 'integer')
            ." ORDER BY relation_id";
        return $this->queryRecords($query, EventOrgunit::model());
    }

    /**
     * Count the courses an event has in a term
     * Exclude the deleted or cancelled courses
     */
    public function countCoursesOfEventInTerm(int $event_id, Term $term) : int
    {
        $query = "SELECT COUNT(*) FROM fau_study_courses WHERE event_id = " . $this->db->quote($event_id, 'integer')
        . " AND term_year = " . $this->db->quote($term->getYear(), 'integer')
        . " AND term_type_id = " . $this->db->quote($term->getTypeId(), 'integer')
        . " AND (deleted IS NULL or deleted = 0)"
        . " AND (cancelled IS NULL or cancelled = 0)";
        return $this->countRecords($query);
    }

    /**
     * Get the courses an event has in a term
     * @return Course[] indexed by course_id
     */
    public function getCoursesOfEventInTerm(int $event_id, Term $term, $useCache = true) : array
    {
        $query = "SELECT * FROM fau_study_courses WHERE event_id = " . $this->db->quote($event_id, 'integer')
            . " AND term_year = " . $this->db->quote($term->getYear(), 'integer')
            . " AND term_type_id = " . $this->db->quote($term->getTypeId(), 'integer');
        return $this->queryRecords($query, Course::model(), $useCache);
    }

    /**
     * Get the course ids of courses offered by org units with given ids
     * @return int[]
     */
    public function getCourseIdsOfOrgUnitsInTerm(array $unit_ids, Term $term, $useCache = true) : array
    {
        $query = "
        SELECT c.course_id
        FROM fau_study_courses c
        JOIN fau_study_events e ON e.event_id = c.event_id
        JOIN fau_study_event_orgs o ON o.event_id = e.event_id
        JOIN fau_org_orgunits u ON u.fauorg_nr = o.fauorg_nr
        WHERE " . $this->db->in('u.id', $unit_ids, false, 'integer')
        . " AND term_year = " . $this->db->quote($term->getYear(), 'integer')
        . " AND term_type_id = " . $this->db->quote($term->getTypeId(), 'integer');
        return $this->getIntegerList($query, 'course_id', $useCache);
    }


    /**
     * Get the courses of an event
     * @param int  $event_id
     * @param bool $useCache
     * @return Course[] indexed by course_id
     */
    public function getCoursesOfEvent(int $event_id, bool $useCache = true) : array
    {
        $query = "SELECT * from fau_study_courses WHERE event_id = " . $this->db->quote($event_id, 'integer');
        return $this->queryRecords($query, Course::model(), $useCache);
    }

    /**
     * Get the courses of events
     * @param int[] $event_ids
     * @param bool $useCache
     * @return Course[] indexed by course_id
     */
    public function getCoursesOfEvents(array $event_ids, bool $useCache = true) : array
    {
        $query = "SELECT * from fau_study_courses WHERE ". $this->db->in('event_id', $event_ids, false,'integer');
        return $this->queryRecords($query, Course::model(), $useCache);
    }


    /**
     * Get the courses with certain ids
     * @return Course[] indexed by course_id
     */
    public function getCoursesByIds(array $ids, bool $useCache = true) : array
    {
        $query = "SELECT * from fau_study_courses WHERE ". $this->db->in('course_id', $ids, false, 'integer');
        return $this->queryRecords($query, Course::model(), $useCache);
    }

    /**
     * Get the courses with certain ilias_obj_ids
     * @return Course[] indexed by course_id
     */
    public function getCoursesByIliasObjId(int $id, bool $useCache = true) : array
    {
        $query = "SELECT * from fau_study_courses WHERE ilias_obj_id = ". $this->db->quote($id, 'integer');
        return $this->queryRecords($query, Course::model(), $useCache);
    }

    /**
     * Gat a single Course
     * @return Course|null
     */
    public function getCourse(int $course_id, ?Course $default = null) : ?RecordData
    {
        $query = "SELECT * from fau_study_courses WHERE course_id = " . $this->db->quote($course_id, 'integer');
        return $this->getSingleRecord($query, Course::model(), $default);
    }


    /**
     * Get the course of a planned date
     * @param int[] $planned_dates_ids
     * @return Course[]
     */
    public function getCoursesOfPlannedDates(array $planned_dates_ids, bool $useCache = true) : array
    {
        $query = "
            SELECT c.* from fau_study_courses c
            JOIN fau_study_plan_dates p ON p.course_id = c.course_id
            WHERE " . $this->db->in('p.planned_dates_id', $planned_dates_ids, false, 'integer');
        return $this->queryRecords($query, Course::model(), $useCache);
    }

    /**
     * Get the course of an individual date
     * @param int[] $individual_dates_ids
     * @return Course[]
     */
    public function getCoursesOfIndividualDates(array $individual_dates_ids, bool $useCache = true) : array
    {
        $query = "
            SELECT c.* from fau_study_courses c
            JOIN fau_study_plan_dates p ON p.course_id = c.course_id
            JOIN fau_study_indi_dates i ON i.planned_dates_id = p.planned_dates_id
            WHERE " . $this->db->in('i.individual_dates_id', $individual_dates_ids, false, 'integer');
        return $this->queryRecords($query, Course::model(), $useCache);
    }

    /**
     * Get the courses of a term that need to be created in StudOn
     * @param Term $term
     * @param int[] $course_ids
     * @return Course[]
     */
    public function getCoursesByTermToCreate(Term $term, ?array $course_ids = null) : array
    {
        $query = "SELECT * FROM fau_study_courses "
            . " WHERE term_year = " . $this->db->quote($term->getYear(), 'integer')
            . " AND term_type_id = " . $this->db->quote($term->getTypeId(), 'integer');
        if (is_array($course_ids)) {
            $query .= " AND " . $this->db->in('course_id', $course_ids, false, 'integer');
        }
        else {
            $query .= " AND ilias_obj_id IS NULL";
        }
        return $this->queryRecords($query, Course::model(), false);
    }

    /**
     * Get the courses of a term that need to be updated in StudOn
     * Either the courses or their events have a dirty flag
     * @param Term $term
     * @param int[] $course_ids
     * @return Course[]
     */
    public function getCoursesByTermToUpdate(Term $term, ?array $course_ids = null) : array
    {
        $query = "SELECT * FROM fau_study_courses "
            . " WHERE term_year = " . $this->db->quote($term->getYear(), 'integer')
            . " AND term_type_id = " . $this->db->quote($term->getTypeId(), 'integer');
        if (is_array($course_ids)) {
            $query .= " AND " . $this->db->in('course_id', $course_ids, false, 'integer');
        }
        else {
            $query .= " AND ilias_dirty_since IS NOT NULL";
        }
        return $this->queryRecords($query, Course::model(), false);
    }

    /**
     * Check if an object id of ilias is stored in records of campo courses
     */
    public function isIliasObjIdUsedInCourses(int $obj_id) : bool
    {
        $query = "SELECT course_id FROM fau_study_courses WHERE ilias_obj_id = " . $this->db->quote($obj_id, 'integer');
        return $this->hasRecord($query);
    }

    /**
     * Get the Import id from an ilias object
     */
    public function getImportId(int $obj_id) : ImportId
    {
        $query = "SELECT import_id FROM object_data WHERE obj_id = " . $this->db->quote($obj_id, 'integer');
        foreach ($this->getStringList($query, 'import_id') as $import_id) {
            return ImportId::fromString($import_id);
        }
        return ImportId::fromString('');
    }

    /**
     * Get the IDs of objects that have a certain import id
     * @param ImportId $import_id
     * @return int[]
     */
    public function getObjectIdsWithImportId(ImportId $import_id) : array
    {
        $query = "SELECT obj_id FROM object_data WHERE import_id = " . $this->db->quote($import_id->toString(), 'text');
        return $this->getIntegerList($query, 'obj_id');
    }

    /**
     * Get Modules
     * @param int[]|null $ids  (get all if null, none if empty)
     * @return Module[]
     */
    public function getModules(array $ids = null) : array
    {
        if ($ids === null) {
            return $this->getAllRecords(Module::model());
        }
        elseif (empty($ids)) {
            return [];
        }
        $query = "SELECT * FROM fau_study_modules WHERE "
            . $this->db->in('module_id', $ids, false, 'integer');
        return $this->queryRecords($query, Module::model());
    }


    /**
     * Check if an event has modules with assigned courses of study
     * @param int $event_id
     * @return bool
     */
    public function hasModuleWithCos(int $event_id) : bool
    {
        $query = "SELECT mc.module_id FROM fau_study_mod_events me "
            . " JOIN fau_study_module_cos mc ON mc.module_id = me.module_id"
            . " WHERE me.event_id = "
            . $this->db->quote($event_id, 'integer')
            . " LIMIT 1";
        return !empty($this->getIntegerList($query, 'module_id'));
    }


    /**
     * Get Courses of Study
     * @param int[]|null $ids  (get all if null, none if empty)
     * @param bool $useCache cache the resulting records of exactly this query
     * @param bool $forceIndex force using the record key as array index, even if it is composed of several fields
     * @return CourseOfStudy[]
     */
    public function getCoursesOfStudy(?array $ids = null, $useCache = true, bool $forceIndex = false) : array
    {
        if ($ids === null) {
            return $this->getAllRecords(CourseOfStudy::model(), $useCache, $forceIndex);
        }
        elseif (empty($ids)) {
            return [];
        }
        $query = "SELECT * FROM fau_study_cos WHERE "
            . $this->db->in('cos_id', $ids, false, 'integer');
        return $this->queryRecords($query, CourseOfStudy::model(), $useCache, $forceIndex);
    }

    /**
     * Get Courses of Study
     * @return CourseOfStudy[]
     */
    public function getCoursesOfStudyForModule(int $module_id) : array
    {

        $query = "
                SELECT DISTINCT c.* FROM fau_study_cos c 
                JOIN fau_study_module_cos mc ON mc.cos_id = c.cos_id
                WHERE mc.module_id =" . $this->db->quote($module_id, 'integer');
        return $this->queryRecords($query, CourseOfStudy::model());
    }


    /**
     * Get Courses of Study
     * @return int[]
     */
    public function getCoursesOfStudyIdsForModule(int $module_id) : array
    {
        $query = "
                SELECT DISTINCT cos_id 
                FROM fau_study_module_cos
                WHERE module_id =" . $this->db->quote($module_id, 'integer');
        return $this->getIntegerList($query, 'cos_id');
    }

    /**
     * Get Module to Course of Study assignments
     * @param int[]|null $module_ids   (get all if null, none if empty)
     * @param int[]|null $cos_ids   (get all if null, none if empty)
     * @param bool $useCache cache the resulting records of exactly this query
     * @param bool $forceIndex force using the record key as array index, even if it is composed of several fields
     * @return ModuleCos[]
     */
    public function getModuleCos(?array $module_ids = null, ?array $cos_ids = null, $useCache = true, bool $forceIndex = false) : array
    {
        $cond = [];
        if (isset($module_ids)) {
            if (empty($module_ids)) {
                return [];
            }
            $cond[] = $this->db->in('module_id', $module_ids, false, 'integer');
        }
        if (isset($cos_ids)) {
            if (empty($cos_ids)) {
                return [];
            }
            $cond[] = $this->db->in('cos_id', $cos_ids, false, 'integer');
        }

        if (empty($cond)) {
            return $this->getAllRecords(ModuleCos::model(), $useCache, $forceIndex);
        }

        $query = "SELECT * FROM fau_study_module_cos WHERE " . implode (' AND ', $cond);
        return $this->queryRecords($query, ModuleCos::model(), $useCache, $forceIndex);
    }

    /**
     * Get Module to Event assignments
     * @param int[]|null $event_ids   (get all if null, none if empty)
     * @param bool $useCache cache the resulting records of exactly this query
     * @param bool $forceIndex force using the record key as array index, even if it is composed of several fields
     * @return ModuleEvent[]
     */
    public function getModuleEvent(?array $event_ids = null, $useCache = true, bool $forceIndex = false) : array
    {
        if ($event_ids === null) {
            return $this->getAllRecords(ModuleEvent::model(), $useCache, $forceIndex);
        }
        elseif (empty($event_ids)) {
            return [];
        }
        $query = "SELECT * FROM fau_study_mod_events WHERE "
            . $this->db->in('event_id', $event_ids, false, 'integer');
        return $this->queryRecords($query, ModuleEvent::model(), $useCache, $forceIndex);
    }

    /**
     * Search for events
     * @param SearchCondition $condition
     * @return SearchResultEvent[]
     */
    public function searchEvents(SearchCondition $condition) : array
    {
        $modJoin = '';
        $cosJoin = '';
        $treeJoin = '';

        if (!empty($condition->getCosIdsArray())) {
            $modJoin = "JOIN fau_study_mod_events me ON me.event_id = c.event_id";
            $cosJoin = "JOIN fau_study_module_cos mc ON mc.module_id = me.module_id AND "
                . $this->db->in('mc.cos_id', $condition->getCosIdsArray(), false, 'integer');
        }
        if (!empty($condition->getModuleIdsArray())) {
            $modJoin = "JOIN fau_study_mod_events me ON me.event_id = c.event_id AND "
                . $this->db->in('me.module_id', $condition->getModuleIdsArray(), false, 'integer');
        }
        if (!empty($condition->getIliasPath())) {
            $objJoin = "JOIN object_data o ON o.obj_id = c.ilias_obj_id";
            $refJoin = "JOIN object_reference r ON r.obj_id = c.ilias_obj_id AND r.deleted IS NULL";
            $treeJoin = "JOIN tree t ON t.child = r.ref_id AND "
                . $this->db->like('t.path', 'text', $condition->getIliasPath() . '%', false);
        }
        else {
            $objJoin = "LEFT JOIN object_data o ON o.obj_id = c.ilias_obj_id";
            $refJoin = 'LEFT JOIN object_reference r ON r.obj_id = c.ilias_obj_id AND r.deleted IS NULL';
        }
        if (!empty($condition->getPattern())) {
            $pattern = str_replace('*', '%', $condition->getPattern()) . '%';
            $titleCond = "AND ("
                . $this->db->like('e.title', 'text', $pattern) . " OR "
                . $this->db->like('c.title', 'text', $pattern) . " OR "
                . $this->db->like('o.title', 'text', $pattern)
                . ")";
        }

        $query = "
            SELECT DISTINCT e.event_id, e.eventtype , e.title, e.shorttext, e.guest, r.obj_id, r.ref_id
            FROM fau_study_events e 
            JOIN fau_study_courses c ON c.event_id = e.event_id
            $modJoin 
            $cosJoin
            $objJoin
            $refJoin
            $treeJoin
            WHERE c.term_year = " . $this->db->quote($condition->getTerm()->getYear(), 'integer') . "
            AND c.term_type_id = ". $this->db->quote($condition->getTerm()->getTypeId(), 'integer') . "      
            $titleCond
        ";

        //\ilUtil::sendInfo(nl2br($query), true);
        $result = $this->db->query($query);

        $events = [];
        while ($row = $this->db->fetchAssoc($result)) {
            $event = SearchResultEvent::from($row);
            $object = SearchResultObject::from($row);
            if (isset($events[$event->getEventId()])) {
                $event = $events[$event->getEventId()];
            }
            if ($object->isValid()) {
                $event = $event->withObject($object);
            }
            $events[$event->getEventId()] = $event;
        }
        return $events;
    }

    /**
     * Save record data of an allowed type
     * @param Course|CourseOfStudy|CourseResponsible|DocProgramme|EventOrgunit|EventResponsible|IndividualDate|IndividualInstructor|Instructor|Module|ModuleCos|ModuleEvent|PlannedDate|StudyDegree|StudyEnrolment|StudyField|StudyForm|StudySchool|StudySubject $record
     */
    public function save(RecordData $record)
    {
        $this->replaceRecord($record);
    }

    /**
     * Delete record data of an allowed type
     * @param Course|CourseOfStudy|CourseResponsible|DocProgramme|EventOrgunit|EventResponsible|IndividualDate|IndividualInstructor|Instructor|Module|ModuleCos|ModuleEvent|PlannedDate|StudyDegree|StudyEnrolment|StudyField|StudyForm|StudySchool|StudySubject $record
     */
    public function delete(RecordData $record)
    {
        $this->deleteRecord($record);
    }
}