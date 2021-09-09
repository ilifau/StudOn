<?php

require_once(__DIR__ . '/trait.ilCampoStudOnDataFunctions.php');

/**
 * fau: campoData - module record in the studon database.
 */
class ilCampoIndividualDate extends ActiveRecord
{
    use ilCampoStudOnDataFunctions;

    /**
     * @var string
     */
    protected $connector_container_name = 'campo_individual_date';

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
     * @var integer
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        integer
     * @con_length           4
     */
    public $planned_dates_id;

    /**
     * @var integer
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        integer
     * @con_length           4
     */
    public $term_year;

    /**
     * @var integer
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        integer
     * @con_length           4
     */
    public $term_type_id;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        date
     */
    public $date;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        time
     */
    public $starttime;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        time
     */
    public $endtime;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           250
     */
    public $famos_code;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           4000
     */
    public $comment;

    /**
     * @var int
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           4
     */
    public $cancelled;

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
            'planned_dates_id' => array(
                'type' => 'integer',
                'length' => '4',

            ),
            'term_year' => array(
                'type' => 'integer',
                'length' => '4',

            ),
            'term_type_id' => array(
                'type' => 'integer',
                'length' => '4',

            ),
            'date' => array(
                'type' => 'date',

            ),
            'starttime' => array(
                'type' => 'time',

            ),
            'endtime' => array(
                'type' => 'time',

            ),
            'famos_code' => array(
                'type' => 'text',
                'length' => '250',

            ),
            'comment' => array(
                'type' => 'text',
                'length' => '4000',

            ),
            'cancelled' => array(
                'type' => 'text',
                'length' => '4',

            ),

        );

        if (! $ilDB->tableExists('campo_individual_date')) {
            $ilDB->createTable('campo_individual_date', $fields);
            $ilDB->addPrimaryKey('campo_individual_date', array( 'individual_dates_id' ));
        }
    }
}
