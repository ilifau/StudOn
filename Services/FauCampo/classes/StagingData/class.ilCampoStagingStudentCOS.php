<?php

require_once(__DIR__ . '/trait.ilCampoStagingDataFunctions.php');

/**
 * fau: campoData - event record in the staging database.
 */
class ilCampoStagingStudentCOS extends ActiveRecord
{
    use ilCampoStagingDataFunctions;

    /**
     * @var string
     */
    protected $connector_container_name = 'campo_student_cos';


    /**
     * @var integer
     * @con_has_field        true
     * @con_is_primary       true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    public $cos_id;

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
    public $student_semester;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           250
     */
    public $related_semester;

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
