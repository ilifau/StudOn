<?php

require_once(__DIR__ . '/trait.ilCampoStagingDataFunctions.php');

/**
 * fau: campoData - event record in the staging database.
 */
class ilCampoStagingRequirement extends ActiveRecord
{
    use ilCampoStagingDataFunctions;

    /**
     * @var string
     */
    protected $connector_container_name = 'campo_module_restrictions';


    /**
     * @var integer
     * @con_has_field        true
     * @con_is_primary       true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    public $requirement_id;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           250
     */
    public $requirement_name;

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
