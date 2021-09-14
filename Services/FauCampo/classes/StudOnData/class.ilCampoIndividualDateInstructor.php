<?php

require_once(__DIR__ . '/trait.ilCampoStudOnDataFunctions.php');

/**
 * fau: campoData - module record in the studon database.
 */
class ilCampoIndividualDateInstructor extends ActiveRecord
{
    use ilCampoStudOnDataFunctions;

    /**
     * @var string
     */
    protected $connector_container_name = 'campo_individual_instructor';


    /**
     * @var integer
     * @con_has_field        true
     * @con_is_primary       true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    public $individual_dates_id;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           250
     */
    public $idm_uid;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           250
     */
    public $first_name;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           250
     */
    public $surname;

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
     * @con_length           250
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
            'individual_dates_id' => array(
                'notnull' => '1',
                'type' => 'integer',
                'length' => '4',

            ),
            'idm_uid' => array(
                'type' => 'text',
                'length' => '250',

            ),
            'first_name' => array(
                'type' => 'text',
                'length' => '250',

            ),
            'surname' => array(
                'type' => 'text',
                'length' => '250',

            ),
            'fauorg_nr' => array(
                'type' => 'text',
                'length' => '250',

            ),
            'orgunit' => array(
                'type' => 'text',
                'length' => '250',

            ),
        );

        if (! $ilDB->tableExists('campo_individual_date_instructor')) {
            $ilDB->createTable('campo_individual_date_instructor', $fields);
            $ilDB->addPrimaryKey('campo_individual_date_instructor', array( 'idm_uid' ));
        }
    }
}
