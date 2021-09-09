<?php

require_once(__DIR__ . '/trait.ilCampoStudOnDataFunctions.php');

/**
 * fau: campoData - module record in the studon database.
 */
class ilCampoStudentCOS extends ActiveRecord
{
    use ilCampoStudOnDataFunctions;

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
     * Create the database table structure
     * THIS WILL GO TO THE DBUPDATE SCRIPT
     */
    public static function createTable()
    {
        global $DIC;
        $ilDB = $DIC->database();

        $fields = array(
            'idm_uid' => array(
                'notnull' => '1',
                'type' => 'text',
                'length' => '250',

            ),
            'cos_id' => array(
                'notnull' => '1',
                'type' => 'integer',
                'length' => '4',

            ),
            'student_semester' => array(
                'type' => 'text',
                'length' => '4000',

            ),
            'related_semester' => array(
                'type' => 'text',
                'length' => '4000',

            ),
        );

        if (! $ilDB->tableExists('campo_student_cos')) {
            $ilDB->createTable('campo_student_cos', $fields);
            $ilDB->addPrimaryKey('campo_student_cos', array( 'student_cos_id' ));
        }
    }
}
