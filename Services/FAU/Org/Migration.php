<?php declare(strict_types=1);

namespace FAU\Org;

class Migration
{
    protected \ilDBInterface $db;

    public function __construct(\ilDBInterface $a_db)
    {
        $this->db = $a_db;
    }

    public function createTables() {
        $this->createOrgUnitsTable();
    }

    protected function createOrgUnitsTable()
    {
        $this->db->createTable('fau_org_orgunits', [
            'id'            => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'parent_id'     => ['type' => 'integer',    'length' => 4,      'notnull' => false,    'default' => null],
            'assignable'    => ['type' => 'integer',    'length' => 4,      'notnull' => false,    'default' => null],
            'fauorg_nr'     => ['type' => 'text',       'length' => 250,    'notnull' => false,    'default' => null],
            'valid_from'    => ['type' => 'date',                           'notnull' => false,    'default' => null],
            'valid_to'      => ['type' => 'date',                           'notnull' => false,    'default' => null],
            'shorttext'     => ['type' => 'text',       'length' => 250,    'notnull' => false,    'default' => null],
            'defaulttext'   => ['type' => 'text',       'length' => 1000,   'notnull' => false,    'default' => null],
            'longtext'      => ['type' => 'text',       'length' => 4000,   'notnull' => false,    'default' => null],
            'ilias_ref_id'  => ['type' => 'integer',    'length' => 4,      'notnull' => false,    'default' => null],
       ],
            true
        );
        $this->db->addPrimaryKey('fau_org_orgunits', ['id']);
        $this->db->addIndex('fau_org_orgunits', ['parent_id'], 'i1');
        $this->db->addIndex('fau_org_orgunits', ['fauorg_nr'], 'i1');
    }


}