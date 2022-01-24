<?php

require_once(__DIR__ . '/trait.ilCampoStagingDataFunctions.php');

/**
 * fau: campoData - event record in the staging database.
 */
class ilCampoStagingCourse extends ActiveRecord
{
    use ilCampoStagingDataFunctions;

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
    public $attendee_maximum;

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
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           250
     */
    public $dip_status;


    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        timestamp
     */
    public $dip_timestamp;
}
