<?php

require_once(__DIR__ . '/trait.ilCampoStudOnDataFunctions.php');

/**
 * fau: campoData - module record in the studon database.
 */
class ilCampoRequirement extends ActiveRecord
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
     * Create the database table structure
     * THIS WILL GO TO THE DBUPDATE SCRIPT
     */
    public static function createTable()
    {
        global $DIC;
        $ilDB = $DIC->database();

        $fields = array(
            'requirement_id' => array(
                'notnull' => '1',
                'type' => 'integer',
                'length' => '4',

            ),
            'requirement_name' => array(
                'type' => 'text',
                'length' => '250',

            ),
        ); #enter database-field information here

        if (! $ilDB->tableExists('campo_requirement')) {
            $ilDB->createTable('campo_requirement', $fields);
            $ilDB->addPrimaryKey('campo_requirement', array( 'requirement_id' ));
        }
    }
}
