<?php

require_once(__DIR__ . '/trait.ilCampoStudOnDataFunctions.php');

/**
 * fau: campoData - event record in the studon database.
 */
class ilCampoEvent extends ActiveRecord
{
    use ilCampoStudOnDataFunctions;

    /**
     * @var string
     */
    protected $connector_container_name = 'campo_event';


    /**
     * @var integer
     * @con_has_field        true
     * @con_is_primary       true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    public $event_id;


    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           250
     */
    public $eventtype;


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
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           4000
     */
    public $comment;

    /**
     * @var int
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           4
     */
    public $guest;

    /**
     * Create the database table structure
     * THIS WILL GO TO THE DBUPDATE SCRIPT
     */
    public static function createTable()
    {
        global $DIC;
        $ilDB = $DIC->database();

        $fields = array(
            'event_id' => array(
                'notnull' => '1',
                'type' => 'integer',
                'length' => '4',

            ),
            'eventtype' => array(
                'type' => 'text',
                'length' => '250',

            ),
            'title' => array(
                'type' => 'text',
                'length' => '1000',

            ),
            'shorttext' => array(
                'type' => 'text',
                'length' => '1000',

            ),
            'comment' => array(
                'type' => 'text',
                'length' => '4000',

            ),
            'guest' => array(
                'type' => 'text',
                'length' => '4',

            ),

        );
        if (! $ilDB->tableExists('campo_event')) {
            $ilDB->createTable('campo_event', $fields);
            $ilDB->addPrimaryKey('campo_event', array( 'event_id' ));
        }
    }
}
