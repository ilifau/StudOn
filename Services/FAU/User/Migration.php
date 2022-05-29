<?php declare(strict_types=1);

namespace FAU\User;

class Migration
{
    protected \ilDBInterface $db;

    public function __construct(\ilDBInterface $a_db)
    {
        $this->db = $a_db;
    }

    public function createTables() {
        $this->createUserEducationsTable();
    }

    public function createUserEducationsTable()
    {
        $this->db->createTable('fau_user_educations', [
            'user_id'       => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'type'          => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'key'           => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'value'         => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'key_title'     => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'value_text'    => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            ],
            true
        );
        $this->db->addPrimaryKey('fau_user_educations', ['user_id', 'type', 'key']);
    }

}