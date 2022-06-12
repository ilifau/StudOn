<?php declare(strict_types=1);

namespace FAU\Study;

class Migration
{
    protected \ilDBInterface $db;

    public function __construct(\ilDBInterface $a_db)
    {
        $this->db = $a_db;
    }

    public function createTables(bool $drop = false) {
        $this->createCourseTable($drop);
        $this->createCourseOfStudyTable($drop);
        $this->createDocProgrammes($drop);
        $this->createEventsTable($drop);
        $this->createEventOrgunitsTable($drop);
        $this->createEventResponsiblesTable($drop);
        $this->createIndividualDatesTable($drop);
        $this->createIndividualInstructorsTable($drop);
        $this->createInstructorsTable($drop);
        $this->createModulesTable($drop);
        $this->createModuleCosTable($drop);
        $this->createModuleEventsTable($drop);
        $this->createPlannedDatesTable($drop);
        $this->createRequirementsTable($drop);
        $this->createModuleRestrictionsTable($drop);
        $this->createStudyDegreesTable($drop);
        $this->createStudyEnrolmentsTable($drop);
        $this->createStudyFieldsTable($drop);
        $this->createStudyFormsTable($drop);
        $this->createStudySchoolsTable($drop);
        $this->createStudySubjectsTable($drop);
    }

    protected function createCourseTable(bool $drop = false)
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
            $drop
        );
        $this->db->addPrimaryKey('fau_study_course', ['course_id']);
        $this->db->addIndex('fau_study_course', ['event_id'], 'i1');
        $this->db->addIndex('fau_study_course', ['ilias_obj_id'], 'i2');
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
        $this->db->createTable('fau_study_course_responsibles', [
            'course_id'             => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'person_id'             => ['type' => 'integer',    'length' => 4,      'notnull' => true],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_study_course_responsibles', ['course_id', 'person_id']);
        $this->db->addIndex('fau_study_course_responsibles', ['person_id'], 'i1');
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
            'event_id'      => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'eventtype'     => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'title'         => ['type' => 'text',       'length' => 1000,   'notnull' => false, 'default' => null],
            'shorttext'     => ['type' => 'text',       'length' => 1000,   'notnull' => false, 'default' => null],
            'comment'       => ['type' => 'text',       'length' => 4000,   'notnull' => false, 'default' => null],
            'guest'         => ['type' => 'integer',    'length' => 4,      'notnull' => false, 'default' => null],
            'ilias_obj_id'  => ['type' => 'integer',    'length' => 4,      'notnull' => false, 'default' => null],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_study_events', ['event_id']);
        $this->db->addIndex('fau_study_events', ['ilias_obj_id'], 'i1');
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
        $this->db->addPrimaryKey('fau_study_event_resps', ['event_id']);
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

    protected function createModuleRestrictionsTable(bool $drop = false)
    {
        $this->db->createTable('fau_study_mod_rests', [
            'module_id'         => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'restriction'       => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'requirement_id'    => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'compulsory'        => ['type' => 'text',       'length' => 250,    'notnull' => true],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_study_mod_rests', ['module_id', 'restriction', 'requirement_id']);
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

    protected function createRequirementsTable(bool $drop = false)
    {
        $this->db->createTable('fau_study_requirements', [
            'requirement_id'    => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'requirement_name'  => ['type' => 'text',       'length' => 250,    'notnull' => true],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_study_requirements', ['requirement_id']);
    }

    protected function createRestrictionsTable(bool $drop = false)
    {
        $this->db->createTable('fau_study_restrictions', [
            'id'            => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'restriction'   => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'type'          => ['type' => 'text',       'length' => 4000,   'notnull' => false, 'default' => null],
            'compare'       => ['type' => 'text',       'length' => 4000,   'notnull' => false, 'default' => null],
            'number'        => ['type' => 'integer',    'length' => 4,      'notnull' => false, 'default' => null],
            'compulsory'       => ['type' => 'text',    'length' => 4000,   'notnull' => false, 'default' => null],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_study_restrictions', ['id']);
        $this->db->addIndex('fau_study_restrictions', ['restriction'], 'i1');
    }


    protected function createStudyDegreesTable(bool $drop = false)
    {
        $this->db->createTable('fau_study_degrees', [
            'degree_his_id'     => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'degree_title'      => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'degree_title_en'   => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'degree_uniquename' => ['type' => 'text',       'length' => 250,    'notnull' => true],
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
            'school_title'      => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'school_title_en'   => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'school_uniquename' => ['type' => 'text',       'length' => 250,    'notnull' => true],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_study_schools', ['school_his_id']);
    }

    protected function createStudySubjectsTable(bool $drop = false)
    {
        $this->db->createTable('fau_study_subjects', [
            'subject_his_id'     => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'subject_title'      => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'subject_title_en'   => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'subject_uniquename' => ['type' => 'text',       'length' => 250,    'notnull' => true],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_study_subjects', ['subject_his_id']);
    }

}