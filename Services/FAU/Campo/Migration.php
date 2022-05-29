<?php declare(strict_types=1);

namespace FAU\Campo;

class Migration
{
    protected \ilDBInterface $db;

    public function __construct(\ilDBInterface $a_db)
    {
        $this->db = $a_db;
    }

    public function createTables() {
        $this->createModuleTable();
        $this->createCourseOfStudyTable();
    }

    public function createModuleTable()
    {
        $this->db->createTable('fau_campo_modules', [
            'module_id'     => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'module_nr'     => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'module_name'   => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            ],
            true
        );
        $this->db->addPrimaryKey('fau_campo_modules', ['module_id']);
    }

    public function createCourseOfStudyTable()
    {
        $this->db->createTable('fau_campo_cos', [
            'cos_id'            => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'degree'            => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'subject'           => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'major'             => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'subject_indicator' => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
            'version'           => ['type' => 'text',       'length' => 250,    'notnull' => false, 'default' => null],
        ],
            true
        );
        $this->db->addPrimaryKey('fau_campo_cos', ['cos_id']);
    }

    public function createModuleCosTable()
    {
        $this->db->createTable('fau_campo_module_cos', [
            'module_id'     => ['type' => 'integer',    'length' => 4,      'notnull' => true],
            'cos_id'        => ['type' => 'integer',    'length' => 4,      'notnull' => true],
        ],
            true
        );
        $this->db->addPrimaryKey('fau_campo_modules', ['module_id', 'cos_id']);
    }

}