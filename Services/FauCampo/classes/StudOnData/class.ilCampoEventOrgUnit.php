<?php

require_once(__DIR__ . '/trait.ilCampoStudOnDataFunctions.php');

/**
 * fau: campoData - event record in the studon database.
 */
class ilCampoEventOrgUnit extends ActiveRecord
{
    use ilCampoStudOnDataFunctions;

    /**
     * @var string
     */
    protected $connector_container_name = 'campo_event_orgunit';


    /**
     * @var integer
     * @con_has_field        true
     * @con_is_primary       true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    public $event_id;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           250
     */
    public $fauorg_nr;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           1000
     */
    public $orgunit;

    /**
     * Create the database table structure
     * THIS WILL GO TO THE DBUPDATE SCRIPT
     */
    public static function createTable()
    {
        global $DIC;
        $ilDB = $DIC->database();

        $fields = array(
            'event_id' => array(
                'notnull' => '1',
                'type' => 'integer',
                'length' => '4',

            ),
            'fauorg_nr' => array(
                'type' => 'text',
                'length' => '250',

            ),
            'orgunit' => array(
                'type' => 'text',
                'length' => '1000',

            ),
        );

        if (! $ilDB->tableExists('campo_event_org_unit')) {
            $ilDB->createTable('campo_event_org_unit', $fields);
            $ilDB->addPrimaryKey('campo_event_org_unit', array( 'event_id' ));
        }
    }
}
