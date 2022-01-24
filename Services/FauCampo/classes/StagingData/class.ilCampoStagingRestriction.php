<?php

require_once(__DIR__ . '/trait.ilCampoStagingDataFunctions.php');

/**
 * fau: campoData - event record in the staging database.
 */
class ilCampoStagingRestriction extends ActiveRecord
{
    use ilCampoStagingDataFunctions;

    /**
     * @var string
     */
    protected $connector_container_name = 'campo_restrictions';

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_is_primary       true
     * @con_fieldtype        text
     * @con_length           250
     */
    public $restriction;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           4000
     */
    public $type;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           4000
     */
    public $compare;

    /**
     * @var integer
     * @con_has_field        true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    public $number;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           4000
     */
    public $compulsory;

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
