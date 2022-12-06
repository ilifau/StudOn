<?php declare(strict_types=1);

namespace FAU\Setup;

class FAUStudySteps
{
    protected \ilDBInterface $db;

    public function prepare(\ilDBInterface $a_db)
    {
        $this->db = $a_db;
    }

    public function custom_step_95()
    {
        $this->createCoursesTable(false);
        $this->createCourseOfStudyTable(false);
        $this->createCourseResponsiblesTable(false);
        $this->createDocProgrammes(false);
        $this->createEventsTable(false);
        $this->createEventOrgunitsTable(false);
        $this->createEventResponsiblesTable(false);
        $this->createIndividualDatesTable(false);
        $this->createIndividualInstructorsTable(false);
        $this->createInstructorsTable(false);
        $this->createModulesTable(false);
        $this->createModuleCosTable(false);
        $this->createModuleEventsTable(false);
        $this->createPlannedDatesTable(false);
        $this->createStudyDegreesTable(false);
        $this->createStudyEnrolmentsTable(false);
        $this->createStudyFieldsTable(false);
        $this->createStudyFormsTable(false);
        $this->createStudySchoolsTable(false);
        $this->createStudyStatusTable(false);
        $this->createStudySubjectsTable(false);
        $this->createStudyTypesTable(false);
    }

    public function custom_step_98()
    {
        $this->addCourseDeleted();
    }

    public function custom_step_99()
    {
        $this->addEventOrgunitRelationId();
    }

    public function custom_step_100()
    {
        $this->changeEventOrgunitsPrimaryKey();
    }

    public function custom_step_105()
    {
        $this->extendEventsTextColumns();
    }

    public function custom_step_108()
    {
        $this->extendCoursesTextColumns();
    }

    public function custom_step_110()
    {
        $this->changeCosMajorToMulti();
    }

    public function custom_step_111()
    {
        $this->changeCourseLiteratureClob();
    }

    public function custom_step_112()
    {
        $this->changeEventCommentClob();
    }


    protected function createCoursesTable(bool $drop = false)
    {
        $this->db->createTable('fau_study_courses', [
            'course_id'             => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'event_id'              => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'term_year'             => ['type' => 'integer',    'length' => 4,      'notnull' => false],
            'term_type_id'          => ['type' => 'integer',    'length' => 4,      'notnull' => false],
            'k_parallelgroup_id'    => ['type' => 'integer',    'length' => 4,      'notnull' => false],
            'title'                 => ['type' => 'text',       'length' => 1000,   'notnull' => false],
            'shorttext'             => ['type' => 'text',       'length' => 1000,   'notnull' => false],
            'hours_per_week'        => ['type' => 'float',                          'notnull' => false],
            'attendee_maximum'      => ['type' => 'integer',    'length' => 4,      'notnull' => false],
            'cancelled'             => ['type' => 'integer',    'length' => 4,      'notnull' => false],
            'teaching_language'     => ['type' => 'text',       'length' => 250,    'notnull' => false],
            'compulsory_requirement' => ['type' => 'text',      'length' => 4000,   'notnull' => false],
            'contents'              => ['type' => 'clob',                           'notnull' => false],
            'literature'            => ['type' => 'text',       'length' => 4000,   'notnull' => false],
            'ilias_obj_id'          => ['type' => 'integer',    'length' => 4,      'notnull' => false],
            'ilias_dirty_since'     => ['type' => 'timestamp',                      'notnull' => false],
            'ilias_problem'         => ['type' => 'text',       'length' => 4000,   'notnull' => false],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_study_courses', ['course_id']);
        $this->db->addIndex('fau_study_courses', ['event_id'], 'i1');
        $this->db->addIndex('fau_study_courses', ['term_year'], 'i2');
        $this->db->addIndex('fau_study_courses', ['title'], 'i3');
        $this->db->addIndex('fau_study_courses', ['ilias_obj_id'], 'i4');
        $this->db->addIndex('fau_study_courses', ['ilias_dirty_since'], 'i5');
    }

    protected function createCourseOfStudyTable(bool $drop = false)
    {
        $this->db->createTable('fau_study_cos', [
            'cos_id'            => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'degree'            => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'subject'           => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'major'             => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'subject_indicator' => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'version'           => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_study_cos', ['cos_id']);
    }

    protected function createCourseResponsiblesTable(bool $drop = false)
    {
        $this->db->createTable('fau_study_course_resps', [
            'course_id'             => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'person_id'             => ['type' => 'integer',    'length' => 4,      'notnull' => true],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_study_course_resps', ['course_id', 'person_id']);
        $this->db->addIndex('fau_study_course_resps', ['person_id'], 'i1');
    }

    protected function createDocProgrammes(bool $drop = false)
    {
        $this->db->createTable('fau_study_doc_progs', [
            'prog_code'         => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'prog_text'         => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'prog_end_date'     => ['type' => 'text',       'length' => 250,    'notnull' => true],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_study_doc_progs', ['prog_code']);
    }

    protected function createEventsTable(bool $drop = false)
    {
        $this->db->createTable('fau_study_events', [
            'event_id'          => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'eventtype'         => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'title'             => ['type' => 'text',       'length' => 1000,   'notnull' => false, 'default' => null],
            'shorttext'         => ['type' => 'text',       'length' => 1000,   'notnull' => false, 'default' => null],
            'comment'           => ['type' => 'text',       'length' => 4000,   'notnull' => false, 'default' => null],
            'guest'             => ['type' => 'integer',    'length' => 4,      'notnull' => false, 'default' => null],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_study_events', ['event_id']);
        $this->db->addIndex('fau_study_events', ['title'], 'i1');
    }

    protected function createEventOrgunitsTable(bool $drop = false)
    {
        $this->db->createTable('fau_study_event_orgs', [
            'event_id'      => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'fauorg_nr'     => ['type' => 'text',       'length' => 250,    'notnull' => true],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_study_event_orgs', ['event_id']);
        $this->db->addIndex('fau_study_event_orgs', ['fauorg_nr'], 'i1');
    }

    protected function createEventResponsiblesTable(bool $drop = false)
    {
        $this->db->createTable('fau_study_event_resps', [
            'event_id'      => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'person_id'     => ['type' => 'integer',    'length' => 4,      'notnull' => true],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_study_event_resps', ['event_id', 'person_id']);
        $this->db->addIndex('fau_study_event_resps', ['person_id'], 'i1');
    }

    protected function createIndividualDatesTable(bool $drop = false)
    {
        $this->db->createTable('fau_study_indi_dates', [
            'individual_dates_id'   => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'planned_dates_id'      => ['type' => 'integer',    'length' => 4,      'notnull' => false, 'default' => null],
            'term_year'             => ['type' => 'integer',    'length' => 4,      'notnull' => false, 'default' => null],
            'term_type_id'          => ['type' => 'integer',    'length' => 4,      'notnull' => false, 'default' => null],
            'date'                  => ['type' => 'date',                           'notnull' => false, 'default' => null],
            'starttime'             => ['type' => 'time',                           'notnull' => false, 'default' => null],
            'endtime'               => ['type' => 'time',                           'notnull' => false, 'default' => null],
            'famos_code'            => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'comment'               => ['type' => 'text',       'length' => 4000,   'notnull' => false, 'default' => null],
            'cancelled'             => ['type' => 'integer',    'length' => 4,      'notnull' => false, 'default' => null],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_study_indi_dates', ['individual_dates_id']);
        $this->db->addIndex('fau_study_indi_dates', ['planned_dates_id'], 'i1');
    }

    protected function createIndividualInstructorsTable(bool $drop = false)
    {
        $this->db->createTable('fau_study_indi_insts', [
            'individual_dates_id'   => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'person_id'             => ['type' => 'integer',    'length' => 4,      'notnull' => true],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_study_indi_insts', ['individual_dates_id']);
        $this->db->addIndex('fau_study_indi_insts', ['person_id'], 'i1');
    }

    protected function createInstructorsTable(bool $drop = false)
    {
        $this->db->createTable('fau_study_instructors', [
            'planned_dates_id'      => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'person_id'             => ['type' => 'integer',    'length' => 4,      'notnull' => true],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_study_instructors', ['planned_dates_id']);
        $this->db->addIndex('fau_study_instructors', ['person_id'], 'i1');
    }

    protected function createModulesTable(bool $drop = false)
    {
        $this->db->createTable('fau_study_modules', [
            'module_id'     => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'module_nr'     => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'module_name'   => ['type' => 'text',       'length' => 1000,   'notnull' => false, 'default' => null],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_study_modules', ['module_id']);
    }

    protected function createModuleCosTable(bool $drop = false)
    {
        $this->db->createTable('fau_study_module_cos', [
            'module_id'     => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'cos_id'        => ['type' => 'integer',    'length' => 4,      'notnull' => true],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_study_module_cos', ['module_id', 'cos_id']);
        $this->db->addIndex('fau_study_module_cos', ['cos_id'], 'i1');
    }

    protected function createModuleEventsTable(bool $drop = false)
    {
        $this->db->createTable('fau_study_mod_events', [
            'module_id'     => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'event_id'      => ['type' => 'integer',    'length' => 4,      'notnull' => true],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_study_mod_events', ['module_id', 'event_id']);
        $this->db->addIndex('fau_study_mod_events', ['event_id'], 'i1');
    }

    protected function createPlannedDatesTable(bool $drop = false)
    {
        $this->db->createTable('fau_study_plan_dates', [
            'planned_dates_id'  => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'course_id'         => ['type' => 'integer',    'length' => 4,      'notnull' => false, 'default' => null],
            'term_year'         => ['type' => 'integer',    'length' => 4,      'notnull' => false, 'default' => null],
            'term_type_id'      => ['type' => 'integer',    'length' => 4,      'notnull' => false, 'default' => null],
            'rhythm'            => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'starttime'         => ['type' => 'time',                           'notnull' => false, 'default' => null],
            'endtime'           => ['type' => 'time',                           'notnull' => false, 'default' => null],
            'academic_time'     => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'startdate'         => ['type' => 'date',                           'notnull' => false, 'default' => null],
            'enddate'           => ['type' => 'date',                           'notnull' => false, 'default' => null],
            'famos_code'        => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'expected_attendees'=> ['type' => 'integer',    'length' => 4,      'notnull' => false, 'default' => null],
            'comment'           => ['type' => 'text',       'length' => 4000,   'notnull' => false, 'default' => null],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_study_plan_dates', ['planned_dates_id']);
        $this->db->addIndex('fau_study_plan_dates', ['course_id'], 'i1');
    }


    protected function createStudyDegreesTable(bool $drop = false)
    {
        $this->db->createTable('fau_study_degrees', [
            'degree_his_id'     => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'degree_uniquename' => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'degree_title'      => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'degree_title_en'   => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_study_degrees', ['degree_his_id']);
    }

    protected function createStudyEnrolmentsTable(bool $drop = false)
    {
        $this->db->createTable('fau_study_enrolments', [
            'enrolment_id'          => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'enrolment_uniquename'  => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'enrolment_title'       => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'enrolment_title_en'    => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_study_enrolments', ['enrolment_id']);
    }

    protected function createStudyFieldsTable(bool $drop = false)
    {
        $this->db->createTable('fau_study_fields', [
            'field_id'          => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'field_uniquename'  => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'field_title'       => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'field_title_en'    => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_study_fields', ['field_id']);
    }

    protected function createStudyFormsTable(bool $drop = false)
    {
        $this->db->createTable('fau_study_forms', [
            'form_id'          => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'form_uniquename'  => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'form_title'       => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'form_title_en'    => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_study_forms', ['form_id']);
    }

    protected function createStudySchoolsTable(bool $drop = false)
    {
        $this->db->createTable('fau_study_schools', [
            'school_his_id'     => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'school_uniquename' => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'school_title'      => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'school_title_en'   => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_study_schools', ['school_his_id']);
    }

    protected function createStudyStatusTable(bool $drop = false)
    {
        $this->db->createTable('fau_study_status', [
            'status_his_id'     => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'status_uniquename' => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'status_title'      => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'status_title_en'   => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_study_status', ['status_his_id']);
    }


    protected function createStudySubjectsTable(bool $drop = false)
    {
        $this->db->createTable('fau_study_subjects', [
            'subject_his_id'     => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'subject_uniquename' => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'subject_title'      => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'subject_title_en'   => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_study_subjects', ['subject_his_id']);
    }

    protected function createStudyTypesTable(bool $drop = false)
    {
        $this->db->createTable('fau_study_types', [
            'type_uniquename' => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'type_title'      => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'type_title_en'   => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_study_types', ['type_uniquename']);
    }

    protected function addCourseDeleted()
    {
        if (!$this->db->tableColumnExists('fau_study_courses', 'deleted')) {
            $this->db->addTableColumn('fau_study_courses','deleted',
                ['type' => 'integer',    'length' => 4,      'notnull' => false],
            );
        }
    }

    protected function addEventOrgunitRelationId()
    {
        if (!$this->db->tableColumnExists('fau_study_event_orgs', 'relation_id')) {
            $this->db->addTableColumn('fau_study_event_orgs','relation_id',
                ['type' => 'integer',    'length' => 4,      'notnull' => false],
            );
        }
    }

    protected function changeEventOrgunitsPrimaryKey()
    {
        $this->db->dropPrimaryKey('fau_study_event_orgs');
        $this->db->addPrimaryKey('fau_study_event_orgs', ['event_id', 'fauorg_nr']);
    }


    protected function extendEventsTextColumns()
    {
        $this->db->modifyTableColumn('fau_study_events', 'title',
            ['type' => 'text', 'length' => 4000,   'notnull' => false, 'default' => null]);

        $this->db->modifyTableColumn('fau_study_events', 'shorttext',
            ['type' => 'text', 'length' => 4000,   'notnull' => false, 'default' => null]);
    }

    protected function extendCoursesTextColumns()
    {
        $this->db->modifyTableColumn('fau_study_courses', 'title',
            ['type' => 'text', 'length' => 4000,   'notnull' => false, 'default' => null]);

        $this->db->modifyTableColumn('fau_study_courses', 'shorttext',
            ['type' => 'text', 'length' => 4000,   'notnull' => false, 'default' => null]);
    }

    protected function changeCosMajorToMulti()
    {
        $this->db->modifyTableColumn('fau_study_cos', 'major',
            ['type' => 'text', 'length' => 4000,   'notnull' => false, 'default' => null]);

        $query = "SELECT cos_id, major FROM fau_study_cos";
        $result = $this->db->query($query);
        while ($row = $this->db->fetchAssoc($result)) {
            $cos_id = $row['cos_id'];
            $major = isset($row['major']) ? serialize([(string) $row['major']]) : serialize([]);

            $update = "UPDATE fau_study_cos SET major= %s WHERE cos_id = %s";
            $this->db->manipulateF($update, ['text', 'integer'], [$major, $cos_id]);
        }

        $this->db->renameTableColumn('fau_study_cos', 'major', 'majors');
    }

    protected function changeCourseLiteratureClob()
    {
        $this->db->modifyTableColumn('fau_study_courses', 'literature',
            ['type' => 'clob', 'notnull' => false, 'default' => null]);
    }

    protected function changeEventCommentClob()
    {
        $this->db->modifyTableColumn('fau_study_events', 'comment',
            ['type' => 'clob', 'notnull' => false, 'default' => null]);
    }

}