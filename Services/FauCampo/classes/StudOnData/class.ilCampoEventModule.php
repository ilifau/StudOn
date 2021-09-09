<?php

require_once(__DIR__ . '/trait.ilCampoStudOnDataFunctions.php');

/**
 * fau: campoData - event record in the studon database.
 */
class ilCampoEventModule extends ActiveRecord
{
    use ilCampoStudOnDataFunctions;

    /**
     * @var string
     */
    protected $connector_container_name = 'campo_event_module';

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
     * @con_is_primary       true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           11
     */
    public $event_id;

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
        );

        if (! $ilDB->tableExists('campo_event_module')) {
            $ilDB->createTable('campo_event_module', $fields);
            $ilDB->addPrimaryKey('campo_event_module', array( 'module_id' ));
        }
    }
}
