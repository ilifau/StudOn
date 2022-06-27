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
}