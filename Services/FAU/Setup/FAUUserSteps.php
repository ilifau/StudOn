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
            'user_id'       => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'type'          => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'key'           => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'value'         => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'key_title'     => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'value_text'    => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            ],
            $drop
        );
        $this->db->addPrimaryKey('fau_user_educations', ['user_id', 'type', 'key']);
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
            'course_id'             => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'user_id'               => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'module_id'             => ['type' => 'integer',    'length' => 4,      'notnull' => false],
            'event_responsible'     => ['type' => 'integer',    'length' => 4,      'notnull' => true, 'default' => false],
            'course_responsible'    => ['type' => 'integer',    'length' => 4,      'notnull' => true, 'default' => false],
            'instructor'            => ['type' => 'integer',    'length' => 4,      'notnull' => true, 'default' => false],
            'individual_instructor' => ['type' => 'integer',    'length' => 4,      'notnull' => true, 'default' => false],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_user_members', ['course_id', 'user_id']);
        $this->db->addIndex('fau_user_members', ['user_id'], 'i1');
    }

}