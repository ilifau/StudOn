<?php

require_once(__DIR__ . '/trait.ilCampoStudOnDataFunctions.php');

/**
 * fau: campoData - module record in the studon database.
 */
class ilCampoAchievement extends ActiveRecord
{
    use ilCampoStudOnDataFunctions;

    /**
     * @var string
     */
    protected $connector_container_name = 'campo_achievement';


    /**
     * @var string
     * @con_has_field        true
     * @con_is_primary       true
     * @con_is_notnull       true
     * @con_fieldtype        text
     * @con_length           250
     */
    public $idm_uid;

    /**
     * @var integer
     * @con_has_field        true
     * @con_is_primary       true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           11
     */
    public $requirement_id;

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
            'requirement_id' => array(
                'notnull' => '1',
                'type' => 'integer',
                'length' => '11',

            ),
        );

        if (! $ilDB->tableExists('campo_achievement')) {
            $ilDB->createTable('campo_achievement', $fields);
            $ilDB->addPrimaryKey('campo_achievement', array( 'achievement_id' ));
        }
    }
}
