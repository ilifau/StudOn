<#1>
<?php
    /** @var ilDBInterface $ilDB */
    /** @var ilCtrlStructureReader $ilCtrlStructureReader */

    /**
    * fau: studyData - Create the tables for study data.
    */
    if (!$ilDB->tableExists('usr_study')) {
        $ilDB->createTable('usr_study', array(
            'usr_id' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0),
            'study_no' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0),
            'semester' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0),
            'school_id' => array('type' => 'integer', 'length' => 4, 'notnull' => false, 'default' => null),
            'degree_id' => array('type' => 'integer', 'length' => 4, 'notnull' => false, 'default' => null),
            'ref_semester' => array('type' => 'text', 'length' => 10, 'notnull' => false, 'default' => null),
        ));
        $ilDB->addPrimaryKey('usr_study', array('usr_id', 'study_no'));
    }
    
    if (!$ilDB->tableExists('usr_subject')) {
        $ilDB->createTable('usr_subject', array(
            'usr_id' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0),
            'study_no' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0),
            'subject_id' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0),
        ));
        $ilDB->addPrimaryKey('usr_subject', array('usr_id', 'study_no', 'subject_id'));
    }

    if (!$ilDB->tableExists('study_schools')) {
        $ilDB->createTable('study_schools', array(
            'school_id' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0),
            'school_title' => array('type' => 'text', 'length' => 250, 'notnull' => false, 'default' => null),
        ));
        $ilDB->addPrimaryKey('study_schools', array('school_id'));
    }
    
    if (!$ilDB->tableExists('study_subjects')) {
        $ilDB->createTable('study_subjects', array(
            'subject_id' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0),
            'subject_title' => array('type' => 'text', 'length' => 250, 'notnull' => false, 'default' => null),
        ));
        $ilDB->addPrimaryKey('study_subjects', array('subject_id'));
    }
    
    if (!$ilDB->tableExists('study_degrees')) {
        $ilDB->createTable('study_degrees', array(
            'degree_id' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0),
            'degree_title' => array('type' => 'text', 'length' => 250, 'notnull' => false, 'default' => null),
        ));
        $ilDB->addPrimaryKey('study_degrees', array('degree_id'));
    }
?>
<#2>
<?php
    /**
    * Extend the course event settings
    */
    if (!$ilDB->tableColumnExists('event', 'max_participants')) {
        $ilDB->addTableColumn(
            'event',
            'max_participants',
            array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0)
        );
    }

    if (!$ilDB->tableColumnExists('crs_settings', 'subscription_with_events')) {
        $ilDB->addTableColumn(
            'crs_settings',
            'subscription_with_events',
            array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0)
        );
    }
?>
<#3>
<?php
    /**
    * Add subject to the waiting list
    * Extend the subjects to 4000 characters
    */
    if (!$ilDB->tableColumnExists('crs_waiting_list', 'subject')) {
        $ilDB->addTableColumn(
            'crs_waiting_list',
            'subject',
            array('type' => 'text', 'length' => 4000, 'notnull' => false, 'default' => null)
        );
        
        $ilDB->modifyTableColumn(
            'il_subscribers',
            'subject',
            array('type' => 'text', 'length' => 4000, 'notnull' => false, 'default' => null)
        );
    }
?>
<#4>
<?php
    /**
    * Add the support for a subscription lot list
    */
    if (!$ilDB->tableExists('il_subscribers_lot')) {
        $ilDB->createTable('il_subscribers_lot', array(
            'usr_id' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0),
            'obj_id' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0),
        ));
        $ilDB->addPrimaryKey('il_subscribers_lot', array('usr_id', 'obj_id'));
    }

    if (!$ilDB->tableColumnExists('crs_settings', 'lot_list')) {
        $ilDB->addTableColumn(
            'crs_settings',
            'lot_list',
            array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0)
        );
    }
    
    if (!$ilDB->tableColumnExists('grp_settings', 'lot_list')) {
        $ilDB->addTableColumn(
            'grp_settings',
            'lot_list',
            array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0)
        );
    }
?>
<#5>
<?php
    /**
    * fau: studyCond - Add the support for study data based subscription conditions.
    */
    if (!$ilDB->tableExists('il_sub_studycond')) {
        $ilDB->createTable('il_sub_studycond', array(
            'cond_id' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0),
            'obj_id' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0),
            'subject_id' => array('type' => 'integer', 'length' => 4, 'notnull' => false, 'default' => null),
            'degree_id' => array('type' => 'integer', 'length' => 4, 'notnull' => false, 'default' => null),
            'min_semester' => array('type' => 'integer', 'length' => 4, 'notnull' => false, 'default' => null),
            'max_semester' => array('type' => 'integer', 'length' => 4, 'notnull' => false, 'default' => null),
            'ref_semester' => array('type' => 'text', 'length' => 10, 'notnull' => false, 'default' => null),
        ));
        $ilDB->addPrimaryKey('il_sub_studycond', array('cond_id'));
        $ilDB->createSequence('il_sub_studycond');
    }
?>
<#6>
<?php
    /**
    * Add a password field to web link items
    */
    if (!$ilDB->tableColumnExists('webr_items', 'password')) {
        $ilDB->addTableColumn(
            'webr_items',
            'lot_list',
            array('type' => 'text', 'length' => 50, 'notnull' => false, 'default' => null)
        );
    }
?>
<#7>
<?php
    /**
     * Create webform tables
     * Deprecated!
     */
//	if(!$ilDB->tableExists('webform_types'))
//	{
//		$ilDB->createTable('webform_types', array(
//			'form_id' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0),
//			'lm_obj_id' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0),
//			'form_name' => array('type' => 'text', 'length' => 255, 'notnull' => false, 'default' => null),
//			'dataset_id' => array('type' => 'text', 'length' => 255, 'notnull' => false, 'default' => '0'),
//			'title' => array('type' => 'text', 'length' => 255, 'notnull' => false, 'default' => null),
//			'path' => array('type' => 'text', 'length' => 255, 'notnull' => false, 'default' => null),
//			'send_maxdate' => array('type' => 'date', 'notnull' => false, 'default' => null),
//			'solution_ref' => array('type' => 'text', 'length' => 255, 'notnull' => false, 'default' => null),
//			'solution_mode' => array('type' => 'text', 'length' => 7, 'notnull' => false, 'default' => 'checked'),
//			'solution_date' => array('type' => 'date', 'notnull' => false, 'default' => null),
//			'forum' => array('type' => 'text', 'length' => 255, 'notnull' => false, 'default' => null),
//			'forum_parent' => array('type' => 'text', 'length' => 255, 'notnull' => false, 'default' => null),
//			'forum_subject' => array('type' => 'text', 'length' => 255, 'notnull' => false, 'default' => null),
//		));
//		$ilDB->addPrimaryKey('webform_types',array('form_id'));
//		$ilDB->createSequence('webform_types');
//	}
//
//	if(!$ilDB->tableExists('webform_savings'))
//	{
//		$ilDB->createTable('webform_savings', array(
//			'save_id' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0),
//			'user_id' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0),
//			'form_id' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0),
//			'dataset_id' => array('type' => 'text', 'length' => 255, 'notnull' => false, 'default' => '0'),
//			'savedate' => array('type' => 'date', 'notnull' => false, 'default' => null),
//			'senddate' => array('type' => 'date', 'notnull' => false, 'default' => null),
//			'checkdate' => array('type' => 'date', 'notnull' => false, 'default' => null),
//			'is_forum_saving' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0),
//		));
//		$ilDB->addPrimaryKey('webform_savings',array('save_id'));
//		$ilDB->createSequence('webform_savings');
//	}
//
//	if(!$ilDB->tableExists('webform_entries'))
//	{
//		$ilDB->createTable('webform_entries', array(
//			'entry_id' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0),
//			'save_id' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0),
//			'fieldname' => array('type' => 'text', 'length' => 255, 'notnull' => false, 'default' => null),
//			'fieldvalue' => array('type' => 'clob', 'notnull' => false, 'default' => null),
//		));
//		$ilDB->addPrimaryKey('webform_entries',array('entry_id'));
//		$ilDB->createSequence('webform_entries');
//	}
?>
<#8>
<?php
    /**
    * Add the switches to show membership limits
    */
    if (!$ilDB->tableColumnExists('crs_settings', 'show_mem_limit')) {
        $ilDB->addTableColumn(
            'crs_settings',
            'show_mem_limit',
            array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 1)
        );
    }
    
    if (!$ilDB->tableColumnExists('grp_settings', 'show_mem_limit')) {
        $ilDB->addTableColumn(
            'grp_settings',
            'show_mem_limit',
            array('type' => 'integer',	'length' => 4, 'notnull' => true, 'default' => 1)
        );
    }
?>
<#9>
<?php
    /**
     * fau: studyCond - Create the table for studydata conditions.
     */
    if (!$ilDB->tableExists('il_studycond')) {
        $ilDB->createTable("il_studycond", array(
            'cond_id' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0),
            'ref_id' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0),
            'school_id' => array('type' => 'integer', 'length' => 4, 'notnull' => false),
            'subject_id' => array('type' => 'integer', 'length' => 4, 'notnull' => false),
            'degree_id' => array('type' => 'integer', 'length' => 4, 'notnull' => false),
            'min_semester' => array('type' => 'integer', 'length' => 4, 'notnull' => false),
            'max_semester' => array('type' => 'integer', 'length' => 4, 'notnull' => false),
            'ref_semester' => array('type' => 'text', 'length' => 10, 'notnull' => false, 'default' => null),
        ));
        $ilDB->addPrimaryKey("il_studycond", array("cond_id"));
        $ilDB->createSequence("il_studycond");
    }
?>
<#10>
<?php
    /**
    * Add a column to always store the external password coming from SSO
    * Extend the standard password column to the same size
    */
    if (!$ilDB->tableColumnExists('usr_data', 'ext_passwd')) {
        $ilDB->addTableColumn(
            'usr_data',
            'ext_passwd',
            array('type' => 'text', 'length' => 100, 'notnull' => false, 'default' => null)
        );

        $ilDB->modifyTableColumn(
            'usr_data',
            'passwd',
            array('type' => 'text', 'length' => 100, 'notnull' => false, 'default' => null)
        );
    }
?>
<#11>
<?php
    /**
    * Add fields for test specific passed/failed messages
    */
    if (!$ilDB->tableColumnExists('tst_tests', 'mark_tst_passed')) {
        $ilDB->addTableColumn(
            'tst_tests',
            'mark_tst_passed',
            array('type' => 'text','length' => 4000, 'notnull' => false, 'default' => null)
        );
    }

    if (!$ilDB->tableColumnExists('tst_tests', 'mark_tst_failed')) {
        $ilDB->addTableColumn(
            'tst_tests',
            'mark_tst_failed',
            array('type' => 'text','length' => 4000, 'notnull' => false, 'default' => null)
        );
    }
?>
<#12>
<?php
    /**
    * Extends the length of stored object title and description
    */
    $ilDB->modifyTableColumn(
        'object_data',
        'title',
        array('type' => 'text', 'length' => 250, 'notnull' => false, 'default' => null)
    );

    $ilDB->modifyTableColumn(
        "object_data",
        "description",
        array('type' => 'text', 'length' => 250, 'notnull' => false, 'default' => null)
    );
    
    $q = "UPDATE object_data dat, object_description des"
    . " SET dat.description = des.description"
    . " WHERE dat.obj_id = des.obj_id"
    . " AND des.description IS NOT NULL";
    $ilDB->manipulate($q);

?>
<#13>
<?php
    /**
    * Create authentication logging table
    */
    if (!$ilDB->tableExists('ut_auth')) {
        $ilDB->createTable("ut_auth", array(
            'auth_id' => array('type' => 'integer', 'length' => 4, 'notnull' => true),
            'auth_time' => array('type' => 'timestamp', 'notnull' => false),
            'auth_year' => array('type' => 'integer', 'length' => 4, 'notnull' => false),
            'auth_month' => array('type' => 'integer', 'length' => 4, 'notnull' => false),
            'auth_day' => array('type' => 'integer', 'length' => 4, 'notnull' => false),
            'auth_action' => array( 'type' => 'text', 'length' => 20, 'notnull' => false),
            'auth_mode' => array('type' => 'text', 'length' => 20, 'notnull' => false),
            'username' => array('type' => 'text', 'length' => 80, 'notnull' => false),
            'remote_addr' => array('type' => 'text', 'length' => 16, 'notnull' => false),
            'server_addr' => array('type' => 'text', 'length' => 16, 'notnull' => false)
        ));
        $ilDB->addPrimaryKey("ut_auth", array("auth_id"));
        $ilDB->createSequence("ut_auth");
    }
?>
<#14>
<?php
    /**
    * Add a column to store the DocOrder user id for an account
    */
    if (!$ilDB->tableColumnExists('usr_data', 'docorder_id')) {
        $ilDB->addTableColumn(
            'usr_data',
            'docorder_id',
            array('type' => 'text', 'length' => 20)
        );
    }
?>
<#15>
<?php
    /**
     * Create the table to store the test result export options for my campus
     */
    if (!$ilDB->tableExists('tst_mycampus_options')) {
        $ilDB->createTable('tst_mycampus_options', array(
            'obj_id' => array('type' => 'integer', 'length' => 4, 'notnull' => true),
            'option_key' => array('type' => 'text', 'length' => 100, 'notnull' => false),
            'option_value' => array('type' => 'text', 'length' => 2000, 'notnull' => false)
        ));
        $ilDB->addPrimaryKey("tst_mycampus_options", array('obj_id', 'option_key'));
    }
?>
<#16>
<?php
    /**
     * Create the table to store the result calculation options for exercises
     */
    if (!$ilDB->tableExists('exc_calc_options')) {
        $ilDB->createTable('exc_calc_options', array(
            'obj_id' => array('type' => 'integer', 'length' => 4, 'notnull' => true),
            'option_key' => array('type' => 'text', 'length' => 100, 'notnull' => false),
            'option_value' => array('type' => 'text', 'length' => 2000, 'notnull' => false)
        ));
        $ilDB->addPrimaryKey('exc_calc_options', array('obj_id', 'option_key'));
    }
?>
<#17>
<?php
    /**
     * Create the table to mark ojects for evaluation
     */
    if (!$ilDB->tableExists('eval_marked_objects')) {
        $ilDB->createTable('eval_marked_objects', array(
            'ref_id' => array('type' => 'integer', 'length' => 4, 'notnull' => true)
        ));
        $ilDB->addPrimaryKey('eval_marked_objects', array('ref_id'));
    }
?>
<#18>
<?php
    /**
     * Add a flag to the waiting list to indicate whether an entry is a subscription request
     */
    if (!$ilDB->tableColumnExists('crs_waiting_list', 'to_confirm')) {
        $ilDB->addTableColumn(
            'crs_waiting_list',
            'to_confirm',
            array('type' => 'integer', 'length' => 1, 'notnull' => true, 'default' => 0)
        );
    }
?>
<#19>
<?php
    /**
     * fau: extendBenchmark - Add backtrace info to the benchmark data
     */
    if (!$ilDB->tableColumnExists('benchmark', 'backtrace')) {
        $ilDB->addTableColumn(
            'benchmark',
            'backtrace',
            array('type' => 'clob', 'notnull' => false)
        );
    }
?>
<#20>
<?php
/** obsolete */
?>
<#21>
<?php
    /**
    * Add field for materialized path
    * (obsolete in 4.4)
    */
//	if( !$ilDB->tableColumnExists('tree', 'path'))
//	{
//		$ilDB->addTableColumn('tree', 'path',
//			array('type' => 'text', 'length' => 4000, 'notnull'	=> false, 'default'	=> null)
//		);
//		$ilDB->addIndex('tree', array('path'), 'i9');
//	}
?>
<#22>
<?php
    /**
    * fau: limitedMediaPlayer - add table for the limited media player.
    */
    if (!$ilDB->tableExists('lmpy_uses')) {
        $fields = array(
            'page_id' => array(
                'type' => 'integer',
                'length' => 4,
                'notnull' => true
            ),
            'mob_id' => array(
                'type' => 'integer',
                'length' => 4,
                'notnull' => true
            ),
            'user_id' => array(
                'type' => 'integer',
                'length' => 4,
                'notnull' => true
            ),
            'uses' => array(
                'type' => 'integer',
                'length' => 4,
                'notnull' => true,
                'default' => 0
            ),
            'pass' => array(
                'type' => 'integer',
                'length' => 4,
                'notnull' => true,
                'default' => -1
            )
        );
        $ilDB->createTable("lmpy_uses", $fields);
        $ilDB->addPrimaryKey("lmpy_uses", array("page_id", "mob_id", "user_id"));
    }
?>
<#23>
<?php
        /**
         * Add field for custom css in styles
         */
        if (!$ilDB->tableColumnExists('style_data', 'custom_css')) {
            $ilDB->addTableColumn(
                'style_data',
                'custom_css',
                array('type' => 'clob', 'notnull' => false)
            );
        }
?>
<#24>
<?php
    $ilCtrlStructureReader->getStructure();
?>
<#25>
<?php
    /**
     * fau: studyData - Add semester for studydata subjects.
     */
    if (!$ilDB->tableColumnExists('usr_subject', 'semester')) {
        $ilDB->addTableColumn(
            'usr_subject',
            'semester',
            array('type' => 'integer', 'length' => 4, 'notnull' => false, 'default' => null)
        );
    }
?>
<#26>
<?php
    /**
     * fau: studyData - Add subject_no for studydata subjects.
     */
    if (!$ilDB->tableColumnExists('usr_subject', 'subject_no')) {
        $ilDB->addTableColumn(
            'usr_subject',
            'subject_no',
            array('type' => 'integer', 'length' => 4, 'notnull' => false, 'default' => null)
        );
    }
?>
<#27>
<?php
    /**
     * fau: studyData - Drop old study registration support.
     */
    if ($ilDB->tableExists('study_matriculations')) {
        $ilDB->dropTable('study_matriculations');
    }

    if ($ilDB->tableExists('study_matriculations')) {
        $ilDB->dropTable('study_mapping');
    }
?>
<#28>
<?php
    /**
     * Switch the user styles to Delos
     *
     * Don't forget to change it also in client.ini.php
     */
    $ilDB->manipulate("UPDATE usr_pref SET value = 'delos' WHERE keyword='style'");
?>
<#29>
<?php
    /**
     * Move tree view switch to the left
     */
    $ilDB->manipulate("UPDATE settings SET value = 'left' WHERE module='common' AND keyword='tree_frame'");
?>
<#30>
<?php
    /**
     * optimize queries on page_style_usage
     */
//    if (!$ilDB->indexExistsByFields('page_style_usage', array('page_id'))) {
//        $ilDB->addIndex('page_style_usage', array('page_id'), 'i1');
//    }
?>
<#31>
<?php
    /**
     * optimize queries on file_usage
     */
//    if (!$ilDB->indexExistsByFields('file_usage', array('usage_id'))) {
//        $ilDB->addIndex('file_usage', array('usage_id'), 'i1');
//    }
?>
<#32>
<?php
    /**
     * optimize queries on event_appointment
     */
//    if (!$ilDB->indexExistsByFields('event_appointment', array('event_id'))) {
//        $ilDB->addIndex('event_appointment', array('event_id'), 'i1');
//    }
?>
<#33>
<?php
    /**
     * optimize queries on frm_posts_tree
     */
//    if (!$ilDB->indexExistsByFields('frm_posts_tree', array('pos_fk'))) {
//        $ilDB->addIndex('frm_posts_tree', array('pos_fk'), 'i1');
//    }
?>
<#34>
<?php
    /**
     * fau: studyCond - optimize queries on study conditions.
     */
    if (!$ilDB->indexExistsByFields('il_studycond', array('ref_id'))) {
        $ilDB->addIndex('il_studycond', array('ref_id'), 'i1');
    }
?>
<#35>
<?php
    /**
     * is done at #4282 in dbupdate_04
     */
?>
<#36>
<?php
    /**
     * fau: taxFilter - extend the random question set condition to multiple taxonomy and node ids
     */
    if (!$ilDB->tableColumnExists('tst_rnd_quest_set_qpls', 'origin_tax_filter')) {
        $ilDB->addTableColumn(
            'tst_rnd_quest_set_qpls',
            'origin_tax_filter',
            array('type' => 'text', 'length' => 4000, 'notnull' => false, 'default' => null)
        );
    }
    if (!$ilDB->tableColumnExists('tst_rnd_quest_set_qpls', 'mapped_tax_filter')) {
        $ilDB->addTableColumn(
            'tst_rnd_quest_set_qpls',
            'mapped_tax_filter',
            array('type' => 'text', 'length' => 4000, 'notnull' => false, 'default' => null)
        );
    }

    $query = "SELECT * FROM tst_rnd_quest_set_qpls WHERE origin_tax_fi IS NOT NULL OR mapped_tax_fi IS NOT NULL";
    /** @var PDOStatement $result */
    $result = $ilDB->query($query);
    while ($row = $ilDB->fetchObject($result)) {
        if (!empty($row->origin_tax_fi)) {
            $origin_tax_filter = serialize(array((int) $row->origin_tax_fi => array((int) $row->origin_node_fi)));
        } else {
            $origin_tax_filter = null;
        }

        if (!empty($row->mapped_tax_fi)) {
            $mapped_tax_filter = serialize(array((int) $row->mapped_tax_fi => array((int) $row->mapped_node_fi)));
        } else {
            $mapped_tax_filter = null;
        }

        $update = "UPDATE tst_rnd_quest_set_qpls SET "
            . " origin_tax_fi = NULL, origin_node_fi = NULL, mapped_tax_fi = NULL, mapped_node_fi = NULL, "
            . " origin_tax_filter = " . $ilDB->quote($origin_tax_filter, 'text') . ", "
            . " mapped_tax_filter = " . $ilDB->quote($mapped_tax_filter, 'text')
            . " WHERE def_id = " . $ilDB->quote($row->def_id,  'integer');

        $ilDB->manipulate($update);
    }
?>
<#37>
<?php
    /**
     * fau: taxDesc - add description to taxonomy nodes
     */
    if (!$ilDB->tableColumnExists('tax_node', 'description')) {
        $ilDB->addTableColumn(
            'tax_node',
            'description',
            array('type' => 'text', 'length' => 4000, 'notnull' => false, 'default' => null)
        );
    }
?>
<#38>
<?php
    /**
     * move old max_participants in session to new reg_limit_users
     * and delete max_participants
     */
    if ($ilDB->tableColumnExists('event', 'max_participants')) {
        $query = 'UPDATE event set reg_limited = 1, reg_limit_users = max_participants WHERE max_participants > 0';
        $ilDB->manipulate($query);

        $ilDB->dropTableColumn('event', 'max_participants');
    }
?>
<#39>
<?php
    /**
     * fau: relativeLink - create the link table
     */
    if (!$ilDB->tableExists('il_relative_link')) {
        $fields = array(
            'target_type' => array(
                'type' => 'text',
                'length' => 10,
                'notnull' => true
            ),
            'target_id' => array(
                'type' => 'integer',
                'length' => 4,
                'notnull' => true
            ),
            'code' => array(
                'type' => 'text',
                'length' => 8,
                'notnull' => true
            )
        );
        $ilDB->createTable("il_relative_link", $fields);
        $ilDB->addPrimaryKey("il_relative_link", array("target_type", "target_id"));
        $ilDB->addIndex("il_relative_link", array('code'), 'i1');
    }
?>
<#40>
<?php
    /**
     * fau: relativeLink - reload control structure for relative link service
     */
    $ilCtrlStructureReader->getStructure();
?>
<#41>
<?php
    /**
     * fau: exResTime - add column for results availability date
     */
    if (!$ilDB->tableColumnExists('exc_assignment', 'res_time')) {
        $ilDB->addTableColumn(
            'exc_assignment',
            'res_time',
            array('type' => 'integer', 'length' => 4, 'notnull' => false, 'default' => null)
        );
    }
?>
<#42>
<?php
    /**
     * fau: lmLayout - change outdated layout win2toc to toc2win
     */
    $ilDB->manipulate("UPDATE content_object SET default_layout='toc2win' WHERE default_layout='win2toc'");
    $ilDB->manipulate("UPDATE lm_data SET layout='toc2win' WHERE layout='win2toc'");
?>
<#43>
<?php
    /**
     * fau: typeFilter - extend the random question set condition to question type
     */
    if (!$ilDB->tableColumnExists('tst_rnd_quest_set_qpls', 'type_filter')) {
        $ilDB->addTableColumn(
            'tst_rnd_quest_set_qpls',
            'type_filter',
            array('type' => 'text', 'length' => 250, 'notnull' => false, 'default' => null)
        );
    }
?>
<#44>
<?php
    /**
     * fau: regCodes - extend the settings of registration codes.
     */
    if (!$ilDB->tableColumnExists('reg_registration_codes', 'title')) {
        $ilDB->addTableColumn(
            'reg_registration_codes',
            'title',
            array('type' => 'text', 'length' => 250, 'notnull' => false, 'default' => null)
        );
        $ilDB->addTableColumn(
            'reg_registration_codes',
            'description',
            array('type' => 'text', 'length' => 4000, 'notnull' => false, 'default' => null)
        );
        $ilDB->addTableColumn(
            'reg_registration_codes',
            'use_limit',
            array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 1)
        );
        $ilDB->addTableColumn(
            'reg_registration_codes',
            'use_count',
            array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0)
        );
        $ilDB->addTableColumn(
            'reg_registration_codes',
            'login_generation_type',
            array('type' => 'text', 'length' => 20, 'notnull' => true, 'default' => 'guestlistener')
        );
        $ilDB->addTableColumn(
            'reg_registration_codes',
            'password_generation',
            array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0)
        );
        $ilDB->addTableColumn(
            'reg_registration_codes',
            'captcha_required',
            array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0)
        );
        $ilDB->addTableColumn(
            'reg_registration_codes',
            'email_verification',
            array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0)
        );
        $ilDB->addTableColumn(
            'reg_registration_codes',
            'email_verification_time',
            array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 600)
        );
        $ilDB->addTableColumn(
            'reg_registration_codes',
            'notification_users',
            array('type' => 'text', 'length' => 250, 'notnull' => false, 'default' => null)
        );
    }

?>
<#45>
<?php
    // fau: objectSub - add sub_ref_id in database scheme
    if (!$ilDB->tableColumnExists('crs_settings', 'sub_ref_id')) {
        $ilDB->addTableColumn(
            'crs_settings',
            'sub_ref_id',
            array('type' => 'integer', 'length' => 4, 'notnull' => false, 'default' => null)
        );
    }
    if (!$ilDB->tableColumnExists('grp_settings', 'sub_ref_id')) {
        $ilDB->addTableColumn(
            'grp_settings',
            'sub_ref_id',
            array('type' => 'integer', 'length' => 4, 'notnull' => false, 'default' => null)
        );
    }
    if (!$ilDB->tableColumnExists('event', 'sub_ref_id')) {
        $ilDB->addTableColumn(
            'event',
            'sub_ref_id',
            array('type' => 'integer', 'length' => 4, 'notnull' => false, 'default' => null)
        );
    }
    // fau.
?>
<#46>
<?php
    // fau: fairSub - add sub_fair in database scheme of courses
    if (!$ilDB->tableColumnExists('crs_settings', 'sub_fair')) {
        $ilDB->addTableColumn(
            'crs_settings',
            'sub_fair',
            array('type' => 'integer', 'length' => 4, 'notnull' => false, 'default' => null)
        );
    }
    // fau.
?>
<#47>
    <?php
    // fau: fairSub - add sub_last_fill in database scheme of courses
    if (!$ilDB->tableColumnExists('crs_settings', 'sub_last_fill')) {
        $ilDB->addTableColumn(
            'crs_settings',
            'sub_last_fill',
            array('type' => 'integer', 'length' => 4, 'notnull' => false, 'default' => null)
        );
    }
    // fau.
?>
<#48>
<?php
    // fau: taxGroupFilter - taxonomy for group filter
    if (!$ilDB->tableColumnExists('tst_rnd_quest_set_qpls', 'origin_group_tax_fi')) {
        $ilDB->addTableColumn(
            'tst_rnd_quest_set_qpls',
            'origin_group_tax_fi',
            array('type' => 'integer', 'length' => 4, 'notnull' => false, 'default' => null)
        );
    }
    if (!$ilDB->tableColumnExists('tst_rnd_quest_set_qpls', 'mapped_group_tax_fi')) {
        $ilDB->addTableColumn(
            'tst_rnd_quest_set_qpls',
            'mapped_group_tax_fi',
            array('type' => 'integer', 'length' => 4, 'notnull' => false, 'default' => null)
        );
    }
    // fau.
?>
<#49>
<?php
    // fau: randomSetOrder - order of questions in a random set
    if (!$ilDB->tableColumnExists('tst_rnd_quest_set_qpls', 'order_by')) {
        $ilDB->addTableColumn(
            'tst_rnd_quest_set_qpls',
            'order_by',
            array('type' => 'text', 'length' => 20, 'notnull' => false, 'default' => null)
        );
    }
    // fau.
?>
<#50>
<?php
    // fau: fairSub - add sub_fair in database scheme of groups
    if (!$ilDB->tableColumnExists('grp_settings', 'sub_fair')) {
        $ilDB->addTableColumn(
            'grp_settings',
            'sub_fair',
            array('type' => 'integer', 'length' => 4, 'notnull' => false, 'default' => null)
        );
    }
    // fau.
?>
<#51>
<?php
    // fau: fairSub - add sub_last_fill in database scheme of groups
    if (!$ilDB->tableColumnExists('grp_settings', 'sub_last_fill')) {
        $ilDB->addTableColumn(
            'grp_settings',
            'sub_last_fill',
            array('type' => 'integer', 'length' => 4, 'notnull' => false, 'default' => null)
        );
    }
    // fau.
?>
<#52>
<?php
    // fau: fairSub - add sub_auto_fill in database scheme of courses
    if (!$ilDB->tableColumnExists('crs_settings', 'sub_auto_fill')) {
        $ilDB->addTableColumn(
            'crs_settings',
            'sub_auto_fill',
            array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 1)
        );
    }
    // fau.
?>
<#53>
    <?php
    // fau: fairSub - add sub_auto_fill in database scheme of groups
    if (!$ilDB->tableColumnExists('grp_settings', 'sub_auto_fill')) {
        $ilDB->addTableColumn(
            'grp_settings',
            'sub_auto_fill',
            array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 1)
        );
    }
    // fau.
?>
<#54>
    <?php
    // fau: courseUdf - add description
    if (!$ilDB->tableColumnExists('crs_f_definitions', 'field_desc')) {
        $ilDB->addTableColumn(
            'crs_f_definitions',
            'field_desc',
            array('type' => 'clob', 'notnull' => false, 'default' => null)
        );
    }
    // fau.
    // fau: courseUdf - add email auto-send
    if (!$ilDB->tableColumnExists('crs_f_definitions', 'field_email_auto')) {
        $ilDB->addTableColumn(
            'crs_f_definitions',
            'field_email_auto',
            array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0)
        );
    }
    // fau.
    // fau: courseUdf - add email text
    if (!$ilDB->tableColumnExists('crs_f_definitions', 'field_email_text')) {
        $ilDB->addTableColumn(
            'crs_f_definitions',
            'field_email_text',
            array('type' => 'clob', 'notnull' => false, 'default' => null)
        );
    }
    // fau.
?>
<#55>
<?php
    // fau: courseUdf - add parent field id
    if (!$ilDB->tableColumnExists('crs_f_definitions', 'parent_field_id')) {
        $ilDB->addTableColumn(
            'crs_f_definitions',
            'parent_field_id',
            array('type' => 'integer', 'length' => 4, 'notnull' => false, 'default' => null)
        );
    }
    // fau.
?>
<#56>
<?php
    // fau: courseUdf - add parent value_id
    if (!$ilDB->tableColumnExists('crs_f_definitions', 'parent_value_id')) {
        $ilDB->addTableColumn(
            'crs_f_definitions',
            'parent_value_id',
            array('type' => 'integer', 'length' => 4, 'notnull' => false, 'default' => null)
        );
    }
    // fau.
?>
<#57>
<?php
/* obsolete */
?>
<#58>
<?php
/**
 * fau: extendBenchmark - Add backtrace info to the benchmark data
 */
if (!$ilDB->tableColumnExists('benchmark', 'backtrace')) {
    $ilDB->addTableColumn(
        'benchmark',
        'backtrace',
        array('type' => 'clob', 'notnull' => false)
    );
}
?>
<#59>
<?php
/**
 * fau: extendBenchmark - Add backtrace info to the benchmark data
 */
if (!$ilDB->tableColumnExists('benchmark', 'backtrace')) {
    $ilDB->addTableColumn(
        'benchmark',
        'backtrace',
        array('type' => 'clob', 'notnull' => false)
    );
}
?>
<#60>
<?php
/**
 * fau: lpQuestionsPercent - Add percent to
 */
if (!$ilDB->tableColumnExists('ut_lp_settings', 'questions_percent')) {
    $ilDB->addTableColumn(
        'ut_lp_settings',
        'questions_percent',
        array('type' => 'float', 'notnull' => true, 'default' => 100)
    );
}
?>
<#61>
<?php
/**
 * fau: stornoBook - add setting to allow storno
 */
if (!$ilDB->tableColumnExists('booking_settings', 'user_storno')) {
    $ilDB->addTableColumn(
        'booking_settings',
        'user_storno',
        array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 1)
    );
}
?>
<#62>
<?php
/**
 * fau: studyData - add the column for study type.
 */
if (!$ilDB->tableColumnExists('usr_study', 'study_type')) {
    $ilDB->addTableColumn(
        'usr_study',
        'study_type',
        array('type' => 'text', 'length' => 1, 'notnull' => false, 'default' => null)
    );
}
/**
 * fau: studyCond - add the column for study type.
 */
if (!$ilDB->tableColumnExists('il_studycond', 'study_type')) {
    $ilDB->addTableColumn(
        'il_studycond',
        'study_type',
        array('type' => 'text', 'length' => 1, 'notnull' => false, 'default' => null)
    );
}
/**
 * fau: studyCond - add the column for study type.
 */
if (!$ilDB->tableColumnExists('il_sub_studycond', 'study_type')) {
    $ilDB->addTableColumn(
        'il_sub_studycond',
        'study_type',
        array('type' => 'text', 'length' => 1, 'notnull' => false, 'default' => null)
    );
}
?>
<#63>
<?php
/**
 * fau: inheritContentStyle - add the ref_id of a container style (set if scope is the sub tree)
 */
if (!$ilDB->tableColumnExists('style_usage', 'scope_ref_id')) {
    $ilDB->addTableColumn(
        'style_usage',
        'scope_ref_id',
        array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0)
    );
    $ilDB->addIndex('style_usage', array('scope_ref_id'), 'i9');
}
?>
<#64>
<?php
/**
 * fau: studyData - Create the tables for study doc programmes.
 */
if (!$ilDB->tableExists('usr_doc_prog')) {
    $ilDB->createTable('usr_doc_prog', array(
        'usr_id' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0),
        'prog_id' => array('type' => 'integer', 'length' => 4, 'notnull' => false, 'default' => null),
        'prog_approval' => array('type' => 'date', 'notnull' => false, 'default' => null),
    ));
    $ilDB->addPrimaryKey('usr_doc_prog', array('usr_id'));
}

if (!$ilDB->tableExists('study_doc_prog')) {
    $ilDB->createTable('study_doc_prog', array(
        'prog_id' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0),
        'prog_text' => array('type' => 'text', 'length' => 250, 'notnull' => false, 'default' => null),
        'prog_end' => array('type' => 'timestamp', 'notnull' => false, 'default' => null),
    ));
    $ilDB->addPrimaryKey('study_doc_prog', array('prog_id'));
}
?>
<#65>
<?php
/**
 * fau: studyCond - create the table for study doc conditions
 */
if (!$ilDB->tableExists('study_doc_cond')) {
    $ilDB->createTable('study_doc_cond', array(
        'cond_id' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0),
        'obj_id' => array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0),
        'prog_id' => array('type' => 'integer', 'length' => 4, 'notnull' => false, 'default' => null),
        'min_approval_date' => array('type' => 'date', 'notnull' => false, 'default' => null),
        'max_approval_date' => array('type' => 'date', 'notnull' => false, 'default' => null),
    ));
    $ilDB->addPrimaryKey('study_doc_cond', array('cond_id'));
    $ilDB->addIndex('study_doc_cond', array('obj_id'), 'i1');
    $ilDB->createSequence('study_doc_cond');
}
?>
<#66>
<?php
/**
 * fau: studyCond - rename the table for study course conditions
 */
if (!$ilDB->tableExists('study_course_cond') && $ilDB->tableExists('il_sub_studycond')) {
    $ilDB->manipulate('RENAME TABLE `il_sub_studycond` TO `study_course_cond`');
    $ilDB->manipulate('RENAME TABLE `il_sub_studycond_seq` TO `study_course_cond_seq`');
}
?>
<#67>
<?php
/**
 * fau: studyCond - add school_id to course conditions
 */
if (!$ilDB->tableColumnExists('study_course_cond', 'school_id')) {
    $ilDB->addTableColumn(
        'study_course_cond',
        'school_id',
        array('type' => 'integer', 'length' => 4, 'notnull' => false, 'default' => null)
    );
}
?>
<#68>
<?php
/**
 * fau: studyCond - drop the old studycond table
 */
if ($ilDB->tableExists('il_studycond')) {
    $ilDB->manipulate('DROP TABLE IF EXISTS il_studycond');
}
if ($ilDB->tableExists('il_studycond_seq')) {
    $ilDB->manipulate('DROP TABLE IF EXISTS il_studycond_seq');
}
?>
<#69>
<?php
$ilCtrlStructureReader->getStructure();
?>
<#70>
<?php
/**
 * fau: studyCond - streamline null values in conditions
 */
$query = "UPDATE study_course_cond set subject_id = NULL WHERE subject_id = 0";
$ilDB->manipulate($query);

$query = "UPDATE study_course_cond set degree_id = NULL WHERE degree_id = 0";
$ilDB->manipulate($query);

$query = "UPDATE study_course_cond set min_semester = NULL WHERE min_semester = 0";
$ilDB->manipulate($query);

$query = "UPDATE study_course_cond set max_semester = NULL WHERE max_semester = 0";
$ilDB->manipulate($query);

$query = "UPDATE study_course_cond set ref_semester = NULL WHERE ref_semester = ''";
$ilDB->manipulate($query);

$query = "UPDATE study_course_cond set study_type = NULL WHERE study_type = ''";
$ilDB->manipulate($query);
?>
<#71>
<?php
/**
 * fau: studyCond - streamline null values in data
 */
$query = "UPDATE usr_study set degree_id = NULL WHERE degree_id = 0";
$ilDB->manipulate($query);
?>
<#72>
<?php
/**
 * fau: exFileSuffixes - add the file_suffixes column to db
 */
if (!$ilDB->tableColumnExists('exc_assignment', 'file_suffixes')) {
    $ilDB->addTableColumn(
        'exc_assignment',
        'file_suffixes',
        array('type' => 'text', 'length' => 250, 'notnull' => false, 'default' => null)
    );
}
?>
<#73>
<?php
/**
 * fau: exFileSuffixes - add the file_suffixes_case column to db
 */
if (!$ilDB->tableColumnExists('exc_assignment', 'file_suffixes_case')) {
    $ilDB->addTableColumn(
        'exc_assignment',
        'file_suffixes_case',
        array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0)
    );
}
?>
<#74>
<?php
/**
 * fau: exMaxPoints - add the max_points column to db
 */
if (!$ilDB->tableColumnExists('exc_assignment', 'max_points')) {
    $ilDB->addTableColumn(
        'exc_assignment',
        'max_points',
        array('type' => 'float', 'notnull' => false, 'default' => null)
    );
}
?>
<#75>
<?php
/**
 * fau: exGradeTime - add column for grading availability date
 */
if (!$ilDB->tableColumnExists('exc_assignment', 'grade_start')) {
    $ilDB->addTableColumn(
        'exc_assignment',
        'grade_start',
        array('type' => 'integer', 'length' => 4, 'notnull' => false, 'default' => null)
    );
}
?>
<#76>
<?php
/**
 * fau: exPlag - add columns for plagiarism state and comment
 */
if (!$ilDB->tableColumnExists('exc_mem_ass_status', 'plag_flag')) {
    $ilDB->addTableColumn(
        'exc_mem_ass_status',
        'plag_flag',
        array('type' => 'text', 'length' => 10, 'notnull' => false, 'default' => 'none')
    );
}
if (!$ilDB->tableColumnExists('exc_mem_ass_status', 'plag_comment')) {
    $ilDB->addTableColumn(
        'exc_mem_ass_status',
        'plag_comment',
        array('type' => 'text', 'length' => 4000, 'notnull' => false, 'default' => null)
    );
}
?>
<#77>
<?php
/**
 * fau: exTeamLimit - add the max_team_members
 */
if (!$ilDB->tableColumnExists('exc_assignment', 'max_team_members')) {
    $ilDB->addTableColumn(
        'exc_assignment',
        'max_team_members',
        array('type' => 'integer', 'notnull' => false, 'default' => null)
    );
}
?>
<#78>
<?php
/**
 * fau: exAssHook - load the hook definition
 */
$ilCtrlStructureReader->getStructure();
?>
<#79>
<?php
/**
 * fau: exNotify - add column for feedback notification
 */
if (!$ilDB->tableColumnExists('exc_data', 'feedback_notification')) {
    $ilDB->addTableColumn(
        'exc_data',
        'feedback_notification',
        array('type' => 'integer', 'length' => 4, 'notnull' => false, 'default' => 1)
    );
}
?>
<#80>
<?php
/**
 * fau: exNotify - add column for feedback notification
 */
$fields = array(
    'id' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '4',
    ),
    'exercise_id' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '4',
    ),
    'test_ref_id' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '4',
    ),
);
if (! $ilDB->tableExists('exc_ass_test_result')) {
    $ilDB->createTable('exc_ass_test_result', $fields);
    $ilDB->addPrimaryKey('exc_ass_test_result', array( 'id' ));
}
?>
<#81>
<?php
/**
 * fau: exAssTest - reload control structure
 */
$ilCtrlStructureReader->getStructure();
?>
<#82>
<?php
/**
 * fau: idmPass - add encoding types for idm passwords
 */
$ilDB->manipulate("UPDATE usr_data SET passwd_enc_type = 'idmssha' WHERE passwd LIKE '{SSHA}%'");
$ilDB->manipulate("UPDATE usr_data SET passwd_enc_type = 'idmcrypt' WHERE passwd LIKE '{CRYPT}%'");
?>
<#83>
<?php
/**
 * fau: idmPass - drop unnecessary ext_passwd
 */
$ilDB->dropTableColumn('usr_data', 'ext_passwd');
?>
<#84>
<?php
$ilDB->modifyTableColumn("usr_data", "passwd", [
    "type" => \ilDBConstants::T_TEXT,
    "length" => 250,
    "notnull" => false,
    "fixed" => false
]);
?>
<#85>
<?php
/**
 * fau: testStatement - database changes
 */
if (!$ilDB->tableColumnExists('tst_tests', 'require_authorship_statement')) {
    $ilDB->addTableColumn(
        'tst_tests',
        'require_authorship_statement',
        array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0)
    );
}
if (!$ilDB->tableColumnExists('tst_active', 'time_authorship_statement')) {
    $ilDB->addTableColumn(
        'tst_active',
        'time_authorship_statement',
        array('type' => 'timestamp', 'notnull' => false, 'default' => null)
    );
}
?>
<#86>
<?php
/**
 * fau: testStatement - database changes
 */
if (!$ilDB->tableColumnExists('exc_assignment', 'require_authorship_statement')) {
    $ilDB->addTableColumn(
        'exc_assignment',
        'require_authorship_statement',
        array('type' => 'integer', 'length' => 4, 'notnull' => true, 'default' => 0)
    );
}
if (!$ilDB->tableColumnExists('exc_mem_ass_status', 'time_authorship_statement')) {
    $ilDB->addTableColumn(
        'exc_mem_ass_status',
        'time_authorship_statement',
        array('type' => 'timestamp', 'notnull' => false, 'default' => null)
    );
}
?>
<#87>
<?php
/**
 * fau: countUsersOnline - log table
 */
$fields = array(
    'check_time' => array(
        'notnull' => '1',
        'type' => 'timestamp'
    ),
    'check_year' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '2',
    ),
    'check_month' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '1',
    ),
    'check_day' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '1',
    ),
    'check_hour' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '1',
    ),
    'check_minute' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '1',
    ),
    'window_seconds' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '2',
    ),
    'users_online' => array(
    'notnull' => '1',
    'type' => 'integer',
    'length' => '2',
    )
);

if (!$ilDB->tableExists('ut_count_online')) {
    $ilDB->createTable('ut_count_online', $fields);
    $ilDB->addPrimaryKey('ut_count_online', array( 'check_time' ));
}
?>
<#88>
<?php
/**
 * fau: countUsersOnline - indexes
 */
if (!$ilDB->indexExistsByFields('ut_count_online', ['check_year', 'check_month', 'check_day'])) {
    $ilDB->addIndex('ut_count_online', ['check_year', 'check_month', 'check_day'], 'i1');
}
if (!$ilDB->indexExistsByFields('ut_count_online', ['check_hour', 'check_minute'])) {
    $ilDB->addIndex('ut_count_online', ['check_hour', 'check_minute'], 'i2');
}
?>
<#89>
<?php
/**
 * fau: loginLog - add field for browser
 */
if (!$ilDB->tableColumnExists('ut_auth', 'user_agent')) {
    $ilDB->addTableColumn(
        'ut_auth',
        'user_agent',
        array('type' => 'text', 'length' => 250, 'notnull' => false, 'default' => null)
    );
}
?>
<#90>
<?php
/**
 * fau: forumCopage - include changes from databay
 */
/** @var ilDBInterface $ilDB */
if ($ilDB->tableExists('frm_settings') && !$ilDB->tableColumnExists('frm_settings', 'stylesheet')) {
    $ilDB->addTableColumn(
        'frm_settings',
        'stylesheet',
        [
            'type' => 'integer',
            'notnull' => true,
            'length' => 4,
            'default' => 0
        ]
    );
}
?>
<#91>
<?php
/**
 * fau: massMail - add field for mass mail sent time
 */
if (!$ilDB->tableColumnExists('usr_data', 'mass_mail_sent')) {
    $ilDB->addTableColumn(
        'usr_data',
        'mass_mail_sent',
        array('type' => 'timestamp', 'notnull' => false, 'default' => null)
    );
}
?>
<#92>
<?php
/**
 * fau: loginLog - add indexes to ut_auth
 */
if (!$ilDB->indexExistsByFields('ut_auth', ['auth_time'])) {
    $ilDB->addIndex('ut_auth', ['auth_time'], 'i1');
}
if (!$ilDB->indexExistsByFields('ut_auth', ['username'])) {
    $ilDB->addIndex('ut_auth', ['username'], 'i2');
}
?>
<#93>
<?php
/**
 * fau: fauService - create the new condition tables
 */
\FAU\Setup\Setup::instance($ilDB)->cond()->custom_step_93();
?>
<#94>
<?php
/**
 * fau: fauService - create the new orgunit table
 */
\FAU\Setup\Setup::instance($ilDB)->org()->custom_step_94();
?>
<#95>
<?php
/**
 * fau: fauService - create the new study tables
 */
\FAU\Setup\Setup::instance($ilDB)->study()->custom_step_95();
?>
<#96>
<?php
/**
 * fau: fauService - create the new user tables
 */
\FAU\Setup\Setup::instance($ilDB)->user()->custom_step_96();
?>
<#97>
<?php
/**
 * fau: fauService - create the new members table
 */
\FAU\Setup\Setup::instance($ilDB)->user()->custom_step_97();
?>
<#98>
<?php
/**
 * fau: fauService - add the 'deleted' colum to the table of courses
 */
\FAU\Setup\Setup::instance($ilDB)->study()->custom_step_98();
?>
<#99>
<?php
/**
 * fau: fauService - add the 'relation_id' colum to the table of event orgunits
 */
\FAU\Setup\Setup::instance($ilDB)->study()->custom_step_99();
?>
<#100>
<?php
/**
 * fau: fauService - extend the primary key of the table event orgunits
 */
\FAU\Setup\Setup::instance($ilDB)->study()->custom_step_100();
?>
<#101>
<?php
/**
 * fau: fauService - create the user org roles table
 */
\FAU\Setup\Setup::instance($ilDB)->user()->custom_step_101();
?>
<#102>
<?php
    /**
     * fau: campoGrades - Create the table to store the test result export options for campo
     */
    if (!$ilDB->tableExists('tst_campo_options')) {
        $ilDB->createTable('tst_campo_options', array(
            'obj_id' => array('type' => 'integer', 'length' => 4, 'notnull' => true),
            'option_key' => array('type' => 'text', 'length' => 100, 'notnull' => false),
            'option_value' => array('type' => 'text', 'length' => 2000, 'notnull' => false)
        ));
        $ilDB->addPrimaryKey("tst_campo_options", array('obj_id', 'option_key'));
    }
?>
<#103>
<?php
/**
 * fau: modSelect - add the module_id to the waiting list
 */
if (!$ilDB->tableColumnExists('crs_waiting_list', 'module_id')) {
    $ilDB->addTableColumn(
        'crs_waiting_list',
        'module_id',
        array('type' => 'integer', 'length' => 4, 'notnull' => false)
    );
}
?>
