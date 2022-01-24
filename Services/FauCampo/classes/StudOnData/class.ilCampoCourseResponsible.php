<?php

require_once(__DIR__ . '/trait.ilCampoStudOnDataFunctions.php');

/**
 * fau: campoData - module record in the studon database.
 */
class ilCampoCourseResponsible extends ActiveRecord
{
    use ilCampoStudOnDataFunctions;

    /**
     * @var string
     */
    protected $connector_container_name = 'campo_course_responsible';

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
    public $firstname;

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
            'course_id' => array(
                'notnull' => '1',
                'type' => 'integer',
                'length' => '4',

            ),
            'idm_uid' => array(
                'type' => 'text',
                'length' => '250',

            ),
            'firstname' => array(
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

        if (! $ilDB->tableExists('campo_course_responsible')) {
            $ilDB->createTable('campo_course_responsible', $fields);
            $ilDB->addPrimaryKey('campo_course_responsible', array( 'course_id' ));
        }
    }
}
