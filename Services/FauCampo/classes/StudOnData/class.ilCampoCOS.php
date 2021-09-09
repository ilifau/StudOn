<?php

require_once(__DIR__ . '/trait.ilCampoStudOnDataFunctions.php');

/**
 * fau: campoData - module record in the studon database.
 */
class ilCampoCOS extends ActiveRecord
{
    use ilCampoStudOnDataFunctions;

    /**
     * @var string
     */
    protected $connector_container_name = 'campo_cos';

    /**
     * Create the database table structure
     * THIS WILL GO TO THE DBUPDATE SCRIPT
     */

    /**
     * @var integer
     * @con_has_field        true
     * @con_is_primary       true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    public $module_id;

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
    public $degree;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           250
     */
    public $subject;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           250
     */
    public $major;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           250
     */
    public $subject_indicator;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           250
     */
    public $version;


    public static function createTable()
    {
        global $DIC;
        $ilDB = $DIC->database();

        $fields = array(
            'module_id' => array(
                'notnull' => '1',
                'type' => 'integer',
                'length' => '4',

            ),
            'cos_id' => array(
                'notnull' => '1',
                'type' => 'integer',
                'length' => '4',

            ),
            'degree' => array(
                'type' => 'text',
                'length' => '250',

            ),
            'subject' => array(
                'type' => 'text',
                'length' => '250',

            ),
            'major' => array(
                'type' => 'text',
                'length' => '250',

            ),
            'subject_indicator' => array(
                'type' => 'text',
                'length' => '250',

            ),
            'version' => array(
                'type' => 'text',
                'length' => '250',

            ),

        );

        if (! $ilDB->tableExists('campo_cos')) {
            $ilDB->createTable('campo_cos', $fields);
            $ilDB->addPrimaryKey('campo_cos', array( 'cos_id' ));
        }
    }
}
