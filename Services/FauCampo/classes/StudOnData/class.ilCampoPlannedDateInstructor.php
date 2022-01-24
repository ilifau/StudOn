<?php

require_once(__DIR__ . '/trait.ilCampoStudOnDataFunctions.php');

/**
 * fau: campoData - module record in the studon database.
 */
class ilCampoPlannedDateInstructor extends ActiveRecord
{
    use ilCampoStudOnDataFunctions;

    /**
     * @var string
     */
    protected $connector_container_name = 'campo_instructor';


    /**
     * @var integer
     * @con_has_field        true
     * @con_is_primary       true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    public $planned_dates_id;

    /**
     * @var integer
     * @con_has_field        true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    public $idm_uid;

    /**
     * @var integer
     * @con_has_field        true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    public $firstname;

    /**
     * @var integer
     * @con_has_field        true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    public $surname;

    /**
     * @var integer
     * @con_has_field        true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    public $fauorg_nr;

    /**
     * @var integer
     * @con_has_field        true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
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
            'planned_dates_id' => array(
                'notnull' => '1',
                'type' => 'integer',
                'length' => '4',

            ),
            'idm_uid' => array(
                'notnull' => '1',
                'type' => 'integer',
                'length' => '4',

            ),
            'firstname' => array(
                'notnull' => '1',
                'type' => 'integer',
                'length' => '4',

            ),
            'surname' => array(
                'notnull' => '1',
                'type' => 'integer',
                'length' => '4',

            ),
            'fauorg_nr' => array(
                'notnull' => '1',
                'type' => 'integer',
                'length' => '4',

            ),
            'orgunit' => array(
                'notnull' => '1',
                'type' => 'integer',
                'length' => '4',

            ),

        );
        if (! $ilDB->tableExists('campo_planned_date_instructor')) {
            $ilDB->createTable('campo_planned_date_instructor', $fields);
            $ilDB->addPrimaryKey('campo_planned_date_instructor', array( '$idm_uid' ));
        }
    }
}
