<?php declare(strict_types=1);

namespace FAU\User;

class Migration
{
    protected \ilDBInterface $db;

    public function __construct(\ilDBInterface $a_db)
    {
        $this->db = $a_db;
    }

    public function createTables(bool $drop = false) {
        $this->createUserAchievementsTable($drop);
        $this->createUserEducationsTable($drop);
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

}