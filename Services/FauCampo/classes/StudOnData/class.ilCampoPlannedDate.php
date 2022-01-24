<?php

require_once(__DIR__ . '/trait.ilCampoStudOnDataFunctions.php');

/**
 * fau: campoData - module record in the studon database.
 */
class ilCampoPlannedDate extends ActiveRecord
{
    use ilCampoStudOnDataFunctions;

    /**
     * @var string
     */
    protected $connector_container_name = 'campo_planned_dates';


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
    public $course_id;

    /**
     * @var integer
     * @con_has_field        true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    public $term_year;

    /**
     * @var integer
     * @con_has_field        true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    public $term_type_id;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           250
     */
    public $rhythm;

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
    public $academic_time;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        date
     */
    public $startdate;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        date
     */
    public $enddate;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           250
     */
    public $famos_code;

    /**
     * @var integer
     * @con_has_field        true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    public $expected_attendees;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           4000
     */
    public $comment;

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
            'course_id' => array(
                'notnull' => '1',
                'type' => 'integer',
                'length' => '4',

            ),
            'term_year' => array(
                'notnull' => '1',
                'type' => 'integer',
                'length' => '4',

            ),
            'term_type_id' => array(
                'notnull' => '1',
                'type' => 'integer',
                'length' => '4',

            ),
            'rhythm' => array(
                'type' => 'text',
                'length' => '250',

            ),
            'starttime' => array(
                'type' => 'time',

            ),
            'endtime' => array(
                'type' => 'time',

            ),
            'academic_time' => array(
                'type' => 'text',
                'length' => '250',

            ),
            'startdate' => array(
                'type' => 'date',

            ),
            'enddate' => array(
                'type' => 'date',

            ),
            'famos_code' => array(
                'type' => 'text',
                'length' => '250',

            ),
            'expected_attendees' => array(
                'notnull' => '1',
                'type' => 'integer',
                'length' => '4',

            ),
            'comment' => array(
                'type' => 'text',
                'length' => '4000',

            ),

        );

        if (! $ilDB->tableExists('campo_planned_date')) {
            $ilDB->createTable('campo_planned_date', $fields);
            $ilDB->addPrimaryKey('campo_planned_date', array( 'planned_dates_id' ));
        }
    }
}
