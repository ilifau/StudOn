<?php declare(strict_types=1);

namespace FAU\Study;

class Migration
{
    protected \ilDBInterface $db;

    public function __construct(\ilDBInterface $a_db)
    {
        $this->db = $a_db;
    }

    public function createTables() {
        $this->createCourseTable();
        $this->createCourseOfStudyTable();
        $this->createDocProgrammes();
        $this->createEventsTable();
        $this->createEventOrgunitsTable();
        $this->createEventResponsiblesTable();
        $this->createIndividualDatesTable();
        $this->createIndividualInstructorsTable();
        $this->createInstructorsTable();
        $this->createModulesTable();
        $this->createModuleCosTable();
        $this->createModuleEventsTable();
        $this->createPlannedDatesTable();
        $this->createRequirementsTable();
        $this->createModuleRestrictionsTable();
        $this->createStudyDegreesTable();
        $this->createStudyEnrolmentsTable();
        $this->createStudyFieldsTable();
        $this->createStudyFormsTable();
        $this->createStudySchoolsTable();
        $this->createStudySubjectsTable();
    }

    protected function createCourseTable()
    {
        $this->db->createTable('fau_study_course', [
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
        ],
            true
        );
        $this->db->addPrimaryKey('fau_study_course', ['course_id']);
        $this->db->addIndex('fau_study_course', ['event_id'], 'i1');
        $this->db->addIndex('fau_study_course', ['ilias_obj_id'], 'i2');
    }

    protected function createCourseOfStudyTable()
    {
        $this->db->createTable('fau_study_cos', [
            'cos_id'            => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'degree'            => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'subject'           => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'major'             => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'subject_indicator' => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'version'           => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
        ],
            true
        );
        $this->db->addPrimaryKey('fau_study_cos', ['cos_id']);
    }

    protected function createCourseResponsiblesTable()
    {
        $this->db->createTable('fau_study_course_responsibles', [
            'course_id'             => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'person_id'             => ['type' => 'integer',    'length' => 4,      'notnull' => true],
        ],
            true
        );
        $this->db->addPrimaryKey('fau_study_course_responsibles', ['course_id', 'person_id']);
        $this->db->addIndex('fau_study_course_responsibles', ['person_id'], 'i1');
    }

    protected function createDocProgrammes()
    {
        $this->db->createTable('fau_study_doc_programmes', [
            'prog_code'         => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'prog_text'         => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'prog_end_date'     => ['type' => 'text',       'length' => 250,    'notnull' => true],
        ],
            true
        );
        $this->db->addPrimaryKey('fau_study_doc_programmes', ['prog_code']);
    }

    protected function createEventsTable()
    {
        $this->db->createTable('fau_study_events', [
            'event_id'      => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'eventtype'     => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'title'         => ['type' => 'text',       'length' => 1000,   'notnull' => false, 'default' => null],
            'shorttext'     => ['type' => 'text',       'length' => 1000,   'notnull' => false, 'default' => null],
            'comment'       => ['type' => 'text',       'length' => 4000,   'notnull' => false, 'default' => null],
            'guest'         => ['type' => 'integer',    'length' => 4,      'notnull' => false, 'default' => null],
            'ilias_obj_id'  => ['type' => 'integer',    'length' => 4,      'notnull' => false, 'default' => null],
        ],
            true
        );
        $this->db->addPrimaryKey('fau_study_events', ['event_id']);
        $this->db->addIndex('fau_study_events', ['ilias_obj_id'], 'i1');
    }

    protected function createEventOrgunitsTable()
    {
        $this->db->createTable('fau_study_event_orgunits', [
            'event_id'      => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'fauorg_nr'     => ['type' => 'text',       'length' => 250,    'notnull' => true],
        ],
            true
        );
        $this->db->addPrimaryKey('fau_study_event_orgunits', ['event_id']);
        $this->db->addIndex('fau_study_event_orgunits', ['fauorg_nr'], 'i1');
    }

    protected function createEventResponsiblesTable()
    {
        $this->db->createTable('fau_study_event_responsibles', [
            'event_id'      => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'person_id'     => ['type' => 'integer',    'length' => 4,      'notnull' => true],
        ],
            true
        );
        $this->db->addPrimaryKey('fau_study_event_responsibles', ['event_id']);
        $this->db->addIndex('fau_study_event_responsibles', ['person_id'], 'i1');
    }

    protected function createIndividualDatesTable()
    {
        $this->db->createTable('fau_study_individual_dates', [
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
        ]);
        $this->db->addPrimaryKey('fau_study_individual_dates', ['individual_dates_id']);
        $this->db->addIndex('fau_study_individual_dates', ['planned_dates_id'], 'i1');
    }

    protected function createIndividualInstructorsTable()
    {
        $this->db->createTable('fau_study_individual_instructors', [
            'individual_dates_id'   => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'person_id'             => ['type' => 'integer',    'length' => 4,      'notnull' => true],
        ]);
        $this->db->addPrimaryKey('fau_study_individual_instructors', ['individual_dates_id']);
        $this->db->addIndex('fau_study_individual_instructors', ['person_id'], 'i1');
    }

    protected function createInstructorsTable()
    {
        $this->db->createTable('fau_study_instructors', [
            'planned_dates_id'      => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'person_id'             => ['type' => 'integer',    'length' => 4,      'notnull' => true],
        ]);
        $this->db->addPrimaryKey('fau_study_instructors', ['planned_dates_id']);
        $this->db->addIndex('fau_study_instructors', ['person_id'], 'i1');
    }

    protected function createModulesTable()
    {
        $this->db->createTable('fau_study_modules', [
            'module_id'     => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'module_nr'     => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'module_name'   => ['type' => 'text',       'length' => 1000,   'notnull' => false, 'default' => null],
        ],
            true
        );
        $this->db->addPrimaryKey('fau_study_modules', ['module_id']);
    }

    protected function createModuleCosTable()
    {
        $this->db->createTable('fau_study_module_cos', [
            'module_id'     => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'cos_id'        => ['type' => 'integer',    'length' => 4,      'notnull' => true],
        ],
            true
        );
        $this->db->addPrimaryKey('fau_study_module_cos', ['module_id', 'cos_id']);
        $this->db->addIndex('fau_study_module_cos', ['cos_id'], 'i1');
    }

    protected function createModuleEventsTable()
    {
        $this->db->createTable('fau_study_module_events', [
            'module_id'     => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'event_id'      => ['type' => 'integer',    'length' => 4,      'notnull' => true],
        ],
            true
        );
        $this->db->addPrimaryKey('fau_study_module_events', ['module_id', 'event_id']);
        $this->db->addIndex('fau_study_module_events', ['event_id'], 'i1');
    }

    protected function createModuleRestrictionsTable()
    {
        $this->db->createTable('fau_study_module_restrictions', [
            'module_id'         => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'restriction'       => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'requirement_id'    => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'compulsory'        => ['type' => 'text',       'length' => 250,    'notnull' => true],
        ],
            true
        );
        $this->db->addPrimaryKey('fau_study_module_restrictions', ['module_id', 'restriction', 'requirement_id']);
    }

    protected function createPlannedDatesTable()
    {
        $this->db->createTable('fau_study_planned_dates', [
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
            true
        );
        $this->db->addPrimaryKey('fau_study_planned_dates', ['planned_dates_id']);
        $this->db->addIndex('fau_study_planned_dates', ['course_id'], 'i1');
    }

    protected function createRequirementsTable()
    {
        $this->db->createTable('fau_study_requirements', [
            'requirement_id'    => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'requirement_name'  => ['type' => 'text',       'length' => 250,    'notnull' => true],
        ],
            true
        );
        $this->db->addPrimaryKey('fau_study_requirements', ['requirement_id']);
    }

    protected function createRestrictionsTable()
    {
        $this->db->createTable('fau_study_restrictions', [
            'id'            => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'restriction'   => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'type'          => ['type' => 'text',       'length' => 4000,   'notnull' => false, 'default' => null],
            'compare'       => ['type' => 'text',       'length' => 4000,   'notnull' => false, 'default' => null],
            'number'        => ['type' => 'integer',    'length' => 4,      'notnull' => false, 'default' => null],
            'compulsory'       => ['type' => 'text',    'length' => 4000,   'notnull' => false, 'default' => null],
        ],
            true
        );
        $this->db->addPrimaryKey('fau_study_restrictions', ['id']);
        $this->db->addIndex('fau_study_restrictions', ['restriction'], 'i1');
    }


    protected function createStudyDegreesTable()
    {
        $this->db->createTable('fau_study_degrees', [
            'degree_his_id'     => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'degree_title'      => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'degree_title_en'   => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'degree_uniquename' => ['type' => 'text',       'length' => 250,    'notnull' => true],
        ],
            true
        );
        $this->db->addPrimaryKey('fau_study_degrees', ['degree_his_id']);
    }

    protected function createStudyEnrolmentsTable()
    {
        $this->db->createTable('fau_study_enrolments', [
            'enrolment_id'          => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'enrolment_uniquename'  => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'enrolment_title'       => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'enrolment_title_en'    => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
        ],
            true
        );
        $this->db->addPrimaryKey('fau_study_enrolments', ['enrolment_id']);
    }

    protected function createStudyFieldsTable()
    {
        $this->db->createTable('fau_study_fields', [
            'field_id'          => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'field_uniquename'  => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'field_title'       => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'field_title_en'    => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
        ],
            true
        );
        $this->db->addPrimaryKey('fau_study_fields', ['field_id']);
    }

    protected function createStudyFormsTable()
    {
        $this->db->createTable('fau_study_forms', [
            'form_id'          => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'form_uniquename'  => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'form_title'       => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'form_title_en'    => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
        ],
            true
        );
        $this->db->addPrimaryKey('fau_study_forms', ['form_id']);
    }

    protected function createStudySchoolsTable()
    {
        $this->db->createTable('fau_study_schools', [
            'school_his_id'     => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'school_title'      => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'school_title_en'   => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'school_uniquename' => ['type' => 'text',       'length' => 250,    'notnull' => true],
        ],
            true
        );
        $this->db->addPrimaryKey('fau_study_schools', ['school_his_id']);
    }

    protected function createStudySubjectsTable()
    {
        $this->db->createTable('fau_study_subjects', [
            'subject_his_id'     => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'subject_title'      => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'subject_title_en'   => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'subject_uniquename' => ['type' => 'text',       'length' => 250,    'notnull' => true],
        ],
            true
        );
        $this->db->addPrimaryKey('fau_study_subjects', ['subject_his_id']);
    }

}