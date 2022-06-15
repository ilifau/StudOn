<?php declare(strict_types=1);

namespace FAU\Cond;

class Migration
{
    protected \ilDBInterface $db;

    public function __construct(\ilDBInterface $a_db)
    {
        $this->db = $a_db;
    }

    public function createTables(bool $drop = false)
    {
        $this->createModuleRestrictionsTable($drop);
        $this->createRequirementsTable($drop);
        $this->createRestrictionsTable($drop);
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
            'compulsory'       => ['type' => 'text',    'length' => 4000,   'notnull' => false, 'default' => null],
        ],
            $drop
        );
        $this->db->addPrimaryKey('fau_cond_restrictions', ['id']);
        $this->db->addIndex('fau_cond_restrictions', ['restriction'], 'i1');
    }


}