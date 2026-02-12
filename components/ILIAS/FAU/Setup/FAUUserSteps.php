<?php declare(strict_types=1);

namespace FAU\Setup;

class FAUUserSteps
{
    protected \ilDBInterface $db;

    public function prepare(\ilDBInterface $a_db)
    {
        $this->db = $a_db;
    }

    public function custom_step_96() {
        $this->createUserAchievementsTable(false);
        $this->createUserEducationsTable(false);
        $this->createUserPersonsTable(false);
    }

    public function custom_step_97() {
        $this->createMembersTable(false);
    }


    public function custom_step_101()
    {
        $this->createUserOrgRolesTable(false);
    }

    public function custom_step_106()
    {
        // drop the old version
        $this->createUserEducationsTable(true);
    }

    public function custom_step_109()
    {
        $this->addEducationsGrade();
    }

    public function custom_step_121()
    {
        $this->addUserIdleExtAccount();
    }

    public function custom_step_129()
    {
        $this->addMemberCourseId();
    }

    protected function createUserAchievementsTable(bool $drop = false)
    {
        $this->db->createTable('fau_user_achievements', [
            'person_id'       => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'requirement_id'  => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            ],
            $drop
        );
        $this->db->addPrimaryKey('fau_user_achievements', ['person_id', 'requirement_id']);
    }

    protected function createUserEducationsTable(bool $drop = false)
    {
        $this->db->createTable('fau_user_educations', [
            'id'            => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'semester'      => ['type' => 'text',       'length' => 250,    'notnull' => false],
            'person_id'     => ['type' => 'integer',    'length' => 4,      'notnull' => false],
            'examnr'        => ['type' => 'text',       'length' => 250,    'notnull' => false],
            'date_of_work'  => ['type' => 'date',                           'notnull' => false],
            'examname'      => ['type' => 'text',       'length' => 250,    'notnull' => false],
            'orgunit'       => ['type' => 'text',       'length' => 250,    'notnull' => false],
            'additional_text' => ['type' => 'text',       'length' => 4000,    'notnull' => false],
            ],
            $drop
        );
        $this->db->addPrimaryKey('fau_user_educations', ['id']);
        $this->db->addIndex('fau_user_educations', ['semester'], 'i1');
        $this->db->addIndex('fau_user_educations', ['person_id'], 'i2');
        $this->db->addIndex('fau_user_educations', ['orgunit'], 'i3');
    }


    public function createUserPersonsTable(bool $drop = false)
    {
        $this->db->createTable('fau_user_persons', [
            'user_id'               => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'person_id'             => ['type' => 'integer',    'length' => 4,      'notnull' => false, 'default' => null],
            'employee'              => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'student'               => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'guest'                 => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'doc_approval_date'     => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'doc_programmes_text'   => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'doc_programmes_code'   => ['type' => 'text',       'length' => 20,     'notnull' => false, 'default' => null],
            'studydata'             => ['type' => 'clob',                           'notnull' => false, 'default' => null],
            'orgdata'               => ['type' => 'clob',                           'notnull' => false, 'default' => null],
            ],
            $drop
        );
        $this->db->addPrimaryKey('fau_user_persons', ['user_id']);
        $this->db->addIndex('fau_user_persons', ['person_id'], 'i1');
    }


    protected function createMembersTable(bool $drop = false)
    {
        $this->db->createTable('fau_user_members', [
            'obj_id'                => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'user_id'               => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'module_id'             => ['type' => 'integer',    'length' => 4,      'notnull' => false],
            'event_responsible'     => ['type' => 'integer',    'length' => 4,      'notnull' => true, 'default' => false],
            'course_responsible'    => ['type' => 'integer',    'length' => 4,      'notnull' => true, 'default' => false],
            'instructor'            => ['type' => 'integer',    'length' => 4,      'notnull' => true, 'default' => false],
            'individual_instructor' => ['type' => 'integer',    'length' => 4,      'notnull' => true, 'default' => false],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_user_members', ['obj_id', 'user_id']);
        $this->db->addIndex('fau_user_members', ['user_id'], 'i1');
    }

    protected function createUserOrgRolesTable(bool $drop = false)
    {
        if ($drop || !$this->db->tableExists('fau_user_org_roles')) {

            $this->db->createTable('fau_user_org_roles', [
                'user_id' => ['type' => 'integer', 'length' => 4, 'notnull' => true],
                'ref_id' => ['type' => 'integer', 'length' => 4, 'notnull' => true],
                'type' => ['type' => 'text', 'length' => 50, 'notnull' => true],
            ],
                $drop
            );
            $this->db->addPrimaryKey('fau_user_org_roles', ['user_id', 'ref_id', 'type']);
        }
    }

    public function addEducationsGrade()
    {
        if (!$this->db->tableColumnExists('fau_user_educations', 'grade')) {
            $this->db->addTableColumn('fau_user_educations', 'grade',
                ['type' => 'float',  'notnull' => false]);
        }
    }

    public function addUserIdleExtAccount()
    {
        if (!$this->db->tableColumnExists('usr_data', 'idle_ext_account')) {
            $this->db->addTableColumn('usr_data', 'idle_ext_account',
                ['type' => 'text',  'length' => 250, 'notnull' => false]);
            $this->db->addIndex('usr_data', ['idle_ext_account'], 'idl');
        }
    }

    protected function addMemberCourseId()
    {
        $this->db->addTableColumn('fau_user_members', 'course_id',
            ['type' => 'integer', 'notnull' => false, 'default' => null]);
        $this->db->addIndex('fau_user_members', ['course_id'], 'i2');
        
        $this->db->manipulate("UPDATE fau_user_members u JOIN fau_study_courses c ON u.obj_id = c.ilias_obj_id SET u.course_id = c.course_id");
    }

}