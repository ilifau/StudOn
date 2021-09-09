<?php

require_once(__DIR__ . '/trait.ilCampoStudOnDataFunctions.php');

/**
 * fau: campoData - module record in the studon database.
 */
class ilCampoCourse extends ActiveRecord
{
    use ilCampoStudOnDataFunctions;

    /**
     * @var string
     */
    protected $connector_container_name = 'campo_course';

    /**
     * @var integer
     * @con_has_field        true
     * @con_is_primary       true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    public $course_id;

    /**
     * @var integer
     * @con_has_field        true
     * @con_is_primary       false
     * @con_is_notnull       false
     * @con_fieldtype        integer
     * @con_length           4
     */
    public $event_id;

    /**
     * @var integer
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        integer
     * @con_length           11
     */
    public $term_year;

    /**
     * @var integer
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        integer
     * @con_length           11
     */
    public $term_type_id;

    /**
     * @var integer
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        integer
     * @con_length           11
     */
    public $k_parallelgroup_id;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           1000
     */
    public $title;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           1000
     */
    public $shorttext;

    /**
     * @var float
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        float
     */
    public $hours_per_week;

    /**
     * @var integer
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        integer
     * @con_length           11
     */
    public $maximum_attendees;

    /**
     * @var integer
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        integer
     * @con_length           11
     */
    public $cancelled;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           250
     */
    public $teaching_language;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           4000
     */
    public $compulsory_requirement;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           4000
     */
    public $contents;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           4000
     */
    public $literature;


    /**
     * Create the database table structure
     * THIS WILL GO TO THE DBUPDATE SCRIPT
     */
    public static function createTable()
    {
        global $DIC;
        $ilDB = $DIC->database();

        $fields = array(
            'course_id' => array(
                'notnull' => '1',
                'type' => 'integer',
                'length' => '4',

            ),
            'event_id' => array(
                'type' => 'integer',
                'length' => '4',

            ),
            'term_year' => array(
                'type' => 'integer',
                'length' => '11',

            ),
            'term_type_id' => array(
                'type' => 'integer',
                'length' => '11',

            ),
            'k_parallelgroup_id' => array(
                'type' => 'integer',
                'length' => '11',

            ),
            'title' => array(
                'type' => 'text',
                'length' => '1000',

            ),
            'shorttext' => array(
                'type' => 'text',
                'length' => '1000',

            ),
            'hours_per_week' => array(
                'type' => 'float',

            ),
            'maximum_attendees' => array(
                'type' => 'integer',
                'length' => '11',

            ),
            'cancelled' => array(
                'type' => 'integer',
                'length' => '11',

            ),
            'teaching_language' => array(
                'type' => 'text',
                'length' => '250',

            ),
            'compulsory_requirement' => array(
                'type' => 'text',
                'length' => '4000',

            ),
            'contents' => array(
                'type' => 'text',
                'length' => '4000',

            ),
            'literature' => array(
                'type' => 'text',
                'length' => '4000',

            ),

        );

        if (! $ilDB->tableExists('campo_course')) {
            $ilDB->createTable('campo_course', $fields);
            $ilDB->addPrimaryKey('campo_course', array( 'course_id' ));
        }
    }
}
