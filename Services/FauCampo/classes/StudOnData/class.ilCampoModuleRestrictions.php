<?php

require_once(__DIR__ . '/trait.ilCampoStudOnDataFunctions.php');

/**
 * fau: campoData - module record in the studon database.
 */
class ilCampoModuleRestrictions extends ActiveRecord
{
    use ilCampoStudOnDataFunctions;

    /**
     * @var string
     */
    protected $connector_container_name = 'campo_module_restrictions';


    /**
     * @var integer
     * @con_has_field        true
     * @con_is_primary       true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    public $module_id;

    /**
     * @var integer
     * @con_has_field        true
     * @con_is_primary       true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    public $restriction;

    /**
     * @var integer
     * @con_has_field        true
     * @con_is_primary       true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    public $requirement_id;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           250
     */
    public $requirement_name;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           250
     */
    public $compulsory;


    /**
     * Create the database table structure
     * THIS WILL GO TO THE DBUPDATE SCRIPT
     */
    public static function createTable()
    {
        global $DIC;
        $ilDB = $DIC->database();

        $fields = array(
            'module_id' => array(
                'notnull' => '1',
                'type' => 'integer',
                'length' => '4',

            ),
            'restriction' => array(
                'notnull' => '1',
                'type' => 'integer',
                'length' => '4',

            ),
            'requirement_id' => array(
                'notnull' => '1',
                'type' => 'integer',
                'length' => '4',

            ),
            'requirement_name' => array(
                'type' => 'text',
                'length' => '250',

            ),
            'compulsory' => array(
                'type' => 'text',
                'length' => '250',

            ),

        );

        if (! $ilDB->tableExists('campo_module_restrictions')) {
            $ilDB->createTable('campo_module_restrictions', $fields);
            $ilDB->addPrimaryKey('campo_module_restrictions', array( 'requirement_id' ));
        }
    }
}
