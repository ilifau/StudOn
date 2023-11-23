<?php declare(strict_types=1);

namespace FAU\Setup;

use FAU\Study\Data\Term;

class FAUCondSteps
{
    protected \ilDBInterface $db;

    public function prepare(\ilDBInterface $a_db)
    {
        $this->db = $a_db;
    }

    /**
     * Create the new database tables for conditions
     * Please run the following function as patches after this step
     * @see fillCosConditionsFromStudydata
     * @see fillDocConditionsFromStudydata
     */
    public function custom_step_93()
    {
        // hard restrictions
        $this->createModuleRestrictionsTable(false);
        $this->createRequirementsTable(false);
        $this->createRestrictionsTable(false);

        // soft conditions
        $this->createCosConditionsTable(false);
        $this->createDocConditionsTable(false);
    }

    /**
     * Create the table for event restrictions
     */
    public function custom_step_104()
    {
        $this->createEventRestrictionsTable(false);
    }

    /**
     * Create the table for event restrictions
     */
    public function custom_step_107()
    {
        $this->createEventRestCosTable(false);
    }


    protected function createEventRestrictionsTable(bool $drop = false)
    {
        $this->db->createTable('fau_cond_event_rests', [
            'event_id'         => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'restriction'       => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'requirement_id'    => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'compulsory'        => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_cond_event_rests', ['event_id', 'restriction', 'requirement_id']);
    }

    protected function createEventRestCosTable(bool $drop = false)
    {
        $this->db->createTable('fau_cond_evt_rest_cos', [
            'event_id'          => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'restriction'       => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'cos_id'            => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'exception'         => ['type' => 'integer',    'length' => 4,      'notnull' => true],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_cond_evt_rest_cos', ['event_id', 'restriction', 'cos_id']);
    }


    protected function createModuleRestrictionsTable(bool $drop = false)
    {
        $this->db->createTable('fau_cond_mod_rests', [
            'module_id'         => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'restriction'       => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'requirement_id'    => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'compulsory'        => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_cond_mod_rests', ['module_id', 'restriction', 'requirement_id']);
    }


    protected function createRequirementsTable(bool $drop = false)
    {
        $this->db->createTable('fau_cond_requirements', [
            'requirement_id'    => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'requirement_name'  => ['type' => 'text',       'length' => 250,    'notnull' => true],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_cond_requirements', ['requirement_id']);
    }

    protected function createRestrictionsTable(bool $drop = false)
    {
        $this->db->createTable('fau_cond_restrictions', [
            'id'            => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'restriction'   => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'type'          => ['type' => 'text',       'length' => 4000,   'notnull' => false, 'default' => null],
            'compare'       => ['type' => 'text',       'length' => 4000,   'notnull' => false, 'default' => null],
            'number'        => ['type' => 'integer',    'length' => 4,      'notnull' => false, 'default' => null],
            'compulsory'    => ['type' => 'text',    'length' => 4000,   'notnull' => false, 'default' => null],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_cond_restrictions', ['id']);
        $this->db->addIndex('fau_cond_restrictions', ['restriction'], 'i1');
    }

    protected function createCosConditionsTable(bool $drop = false)
    {
        $this->db->createTable('fau_cond_cos', [
            'id'                => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'ilias_obj_id'      => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'subject_his_id'    => ['type' => 'integer',    'length' => 4,      'notnull' => false, 'default' => null],
            'degree_his_id'     => ['type' => 'integer',    'length' => 4,      'notnull' => false, 'default' => null],
            'school_his_id'     => ['type' => 'integer',    'length' => 4,      'notnull' => false, 'default' => null],
            'enrolment_id'      => ['type' => 'integer',    'length' => 4,      'notnull' => false, 'default' => null],
            'min_semester'      => ['type' => 'integer',    'length' => 4,      'notnull' => false, 'default' => null],
            'max_semester'      => ['type' => 'integer',    'length' => 4,      'notnull' => false, 'default' => null],
            'ref_term_year'     => ['type' => 'integer',    'length' => 4,      'notnull' => false, 'default' => null],
            'ref_term_type_id'  => ['type' => 'integer',    'length' => 4,      'notnull' => false, 'default' => null],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_cond_cos', ['id']);
        $this->db->addIndex('fau_cond_cos', ['ilias_obj_id'], 'i1');
        $this->db->createSequence('fau_cond_cos');
    }

    protected function createDocConditionsTable(bool $drop = false)
    {
        $this->db->createTable('fau_cond_doc_prog', [
            'id'                => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'ilias_obj_id'      => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'prog_code'         => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'min_approval_date' => ['type' => 'date',                           'notnull' => false, 'default' => null],
            'max_approval_date' => ['type' => 'date',                           'notnull' => false, 'default' => null],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_cond_doc_prog', ['id']);
        $this->db->addIndex('fau_cond_doc_prog', ['ilias_obj_id'], 'i1');
        $this->db->createSequence('fau_cond_doc_prog');
    }

    /**
     * Transfer the study data conditions
     * switch old ids (numeric uniquenames) to the new his_id which match the values in the JSON of identities.fau_studydata
     * Access to the idm database is needed for looking up the old ids
     * @param \ilDBInterface $idm   database connection to the idm database
     */
    public function fillCosConditionsFromStudydata(\ilDBInterface $idm)
    {
        $degree_his = [];
        $query = "SELECT degree_id, degree_his_id FROM study_degrees";
        $result = $idm->query($query);
        while ($row = $idm->fetchAssoc($result)) {
            $degree_his[$row['degree_id']] = $row['degree_his_id'];
        }

        $subject_his = [];
        $query = "SELECT subject_id, subject_his_id FROM study_subjects";
        $result = $idm->query($query);
        while ($row = $idm->fetchAssoc($result)) {
            $subject_his[$row['subject_id']] = $row['subject_his_id'];
        }

        $school_his = [];
        $query = "SELECT school_id, school_his_id FROM study_schools";
        $result = $idm->query($query);
        while ($row = $idm->fetchAssoc($result)) {
            $school_his[$row['school_id']] = $row['school_his_id'];
        }

        $enrolment_ids = [];
        $query = "SELECT enrolment_id, enrolment_uniquename FROM study_enrolments";
        $result = $idm->query($query);
        while ($row = $idm->fetchAssoc($result)) {
            $enrolment_ids[$row['enrolment_uniquename']] = $row['enrolment_id'];
        }

        $query = "SELECT * FROM study_course_cond";
        $result = $this->db->query($query);
        while ($row = $this->db->fetchAssoc($result)) {
            $id = $this->db->nextId('fau_cond_cos');
            $this->db->insert('fau_cond_cos', [
                'id'                => ['integer', $id],
                'ilias_obj_id'      => ['integer', $row['obj_id']],
                'subject_his_id'    => ['integer', $row['subject_id'] ? $subject_his[$row['subject_id']] : null],
                'degree_his_id'     => ['integer', $row['degree_id'] ? $degree_his[$row['degree_id']] : null],
                'school_his_id'     => ['integer', $row['school_id'] ? $school_his[$row['school_id']] : null],
                'enrolment_id'      => ['integer', $row['study_type'] ? $enrolment_ids[$row['study_type']] : null],
                'min_semester'      => ['integer', $row['min_semester']],
                'max_semester'      => ['integer', $row['max_semester']],
                'ref_term_year'     => ['integer', $row['ref_semester'] ? Term::fromString($row['ref_semester'])->getYear() : null],
                'ref_term_type_id'  => ['integer', $row['ref_semester'] ? Term::fromString($row['ref_semester'])->getTypeId() : null],
            ]);
        }
    }

    /**
     * Transfer the doc program conditions
     */
    public function fillDocConditionsFromStudydata()
    {
        $query = "SELECT * FROM study_doc_cond";
        $result = $this->db->query($query);
        while ($row = $this->db->fetchAssoc($result)) {
            $id = $this->db->nextId('fau_cond_doc_prog');
            $this->db->insert('fau_cond_doc_prog', [
                'id'                => ['integer', $id],
                'ilias_obj_id'      => ['integer', $row['obj_id']],
                'prog_code'         => ['text', $row['cond_id']],
                'min_approval_date' => ['date', $row['min_approval_date']],
                'max_approval_date' => ['date', $row['max_approval_date']]
            ]);
        }
    }
}