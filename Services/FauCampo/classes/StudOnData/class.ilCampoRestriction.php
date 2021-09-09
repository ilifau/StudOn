<?php

require_once(__DIR__ . '/trait.ilCampoStudOnDataFunctions.php');

/**
 * fau: campoData - module record in the studon database.
 */
class ilCampoRestriction extends ActiveRecord
{
    use ilCampoStudOnDataFunctions;

    /**
     * @var string
     */
    protected $connector_container_name = 'campo_restriction';

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
     * Create the database table structure
     * THIS WILL GO TO THE DBUPDATE SCRIPT
     */
    public static function createTable()
    {
        global $DIC;
        $ilDB = $DIC->database();

        $fields = array(
            'restriction' => array(
                'type' => 'text',
                'length' => '250',

            ),
            'type' => array(
                'type' => 'text',
                'length' => '4000',

            ),
            'compare' => array(
                'type' => 'text',
                'length' => '4000',

            ),
            'number' => array(
                'notnull' => '1',
                'type' => 'integer',
                'length' => '4',

            ),
            'compulsory' => array(
                'type' => 'text',
                'length' => '4000',

            ),

        );
        if (! $ilDB->tableExists('campo_restriction')) {
            $ilDB->createTable('campo_restriction', $fields);
            $ilDB->addPrimaryKey('campo_restriction', array( 'restriction' ));
        }
    }
}
