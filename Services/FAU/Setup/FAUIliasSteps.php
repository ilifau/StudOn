<?php declare(strict_types=1);

namespace FAU\Setup;

class FAUIliasSteps
{
    protected \ilDBInterface $db;

    public function prepare(\ilDBInterface $a_db)
    {
        $this->db = $a_db;
    }

    public function custom_step_130() {
        $this->createRegLogTable(false);
    }

    
    protected function createRegLogTable(bool $drop = false)
    {
        $this->db->createTable('fau_ilias_reglog', [
            'id'            => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'timestamp'     => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'action'        => ['type' => 'text',       'length' => 250,    'notnull' => true],
            'actor_id'      => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'user_id'       => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'obj_id'        => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'to_confirm'    => ['type' => 'integer',    'length' => 4,      'notnull' => true, 'default' => 0],
            'module_id'     => ['type' => 'integer',    'length' => 4,      'notnull' => false, 'default' => null],
            'subject'       => ['type' => 'text',       'length' => 4000,   'notnull' => false, 'default' => null],
            ],
            $drop
        );
        $this->db->addPrimaryKey('fau_ilias_reglog', ['id']);
        $this->db->createSequence('fau_ilias_reglog');
        $this->db->addIndex('fau_ilias_reglog', ['timestamp'], 'i1');
        $this->db->addIndex('fau_ilias_reglog', ['actor_id'], 'i2');
        $this->db->addIndex('fau_ilias_reglog', ['user_id'], 'i3');
        $this->db->addIndex('fau_ilias_reglog', ['obj_id'], 'i4');
    }
}