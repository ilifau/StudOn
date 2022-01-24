<?php

require_once(__DIR__ . '/trait.ilCampoStudOnDataFunctions.php');

/**
 * fau: campoData - module record in the studon database.
 */
class ilCampoModule extends ActiveRecord
{
    use ilCampoStudOnDataFunctions;


    /**
     * @var string
     */
    protected $connector_container_name = 'campo_module';

    /**
     * @var integer
     * @con_has_field        true
     * @con_is_primary       true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           11
     */
    public $module_id;

    /**
     * @var integer
     * @con_has_field        true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           11
     */
    public $event_id;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           250
     */
    public $module_nr;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           1000
     */
    public $module_name;


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
                'length' => '11',

            ),
            'event_id' => array(
                'notnull' => '1',
                'type' => 'integer',
                'length' => '11',

            ),
            'module_nr' => array(
                'type' => 'text',
                'length' => '250',

            ),
            'module_name' => array(
                'type' => 'text',
                'length' => '1000',

            ),
        );

        if (! $ilDB->tableExists('campo_module')) {
            $ilDB->createTable('campo_module', $fields);
            $ilDB->addPrimaryKey('campo_module', array( 'module_id' ));
        }
    }
}
