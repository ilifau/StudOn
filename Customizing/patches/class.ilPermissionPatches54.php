<?php
include_once("./Customizing/classes/class.ilPermissionUtils.php");

/**
 * fau: customPatches - permission patches for ILIAS 5.4
 */
class ilPermissionPatches54
{
    /**
     * Adapt the permissions of blogs
     */
    public function adaptBlog()
    {
        $pu = new ilPermissionUtils(true);

        $pu->copyDefaultPermission('blog','write', 'blog','redact');

        $pu->copyPermissions(
            array('blog'), array(
            array('write', 'redact')
        ));
    }

    /**
     * Adapt the permissions of blogs
     */
    public function adaptDataCollection()
    {
        $pu = new ilPermissionUtils(true);

        $pu->copyDefaultPermission('dcl','write', 'dcl','edit_content');

        $pu->copyPermissions(
            array('dcl'), array(
            array('write', 'edit_content')
        ));
    }

    /**
     * Adapt the permissions of wikis
     */
    public function adaptWiki()
    {
        $pu = new ilPermissionUtils(true);

        $pu->copyDefaultPermission('wiki','write', 'wiki','statistics_read');
        $pu->copyDefaultPermission('wiki','write', 'wiki','edit_page_meta');
        $pu->copyDefaultPermission('wiki','write', 'wiki','edit_wiki_navigation');
        $pu->copyDefaultPermission('wiki','write', 'wiki','activate_wiki_protection');
        $pu->copyDefaultPermission('wiki','write', 'wiki','wiki_html_export');
        $pu->copyDefaultPermission('wiki','write', 'wiki','delete_wiki_pages');

        $pu->copyPermissions(array('wiki'), array(
            array('write', 'statistics_read'),
            array('write', 'edit_page_meta'),
            array('write', 'edit_wiki_navigation'),
            array('write', 'activate_wiki_protection'),
            array('write', 'wiki_html_export'),
            array('write', 'delete_wiki_pages'),
        ));
    }

    /**
     * Copy from Learning Module to Content Page
     */
    public function initContentPage()
    {
        $pu = new ilPermissionUtils(true);

        $pu->copyDefaultPermission('lm','visible',			    'copa','visible');
        $pu->copyDefaultPermission('lm','read',				    'copa','read');
        $pu->copyDefaultPermission('lm','copy',				    'copa','copy');
        $pu->copyDefaultPermission('lm','write',				    'copa','write');
        $pu->copyDefaultPermission('lm','delete',				    'copa','delete');
        $pu->copyDefaultPermission('lm','read_learning_progress',	'copa','read_learning_progress');
        $pu->copyDefaultPermission('lm','edit_learning_progress',	'copa','edit_learning_progress');
        $pu->copyDefaultPermission('lm','edit_permission',	    'copa','edit_permission');
    }

    /**
     * Copy from Test to Individual Assessment
     */
    public function initIndividualAssesment()
    {
        $pu = new ilPermissionUtils(true);

        $pu->copyDefaultPermission('tst','visible',			    'iass','visible');
        $pu->copyDefaultPermission('tst','read',				    'iass','read');
        $pu->copyDefaultPermission('tst','copy',				    'iass','copy');
        $pu->copyDefaultPermission('tst','write',				    'iass','write');
        $pu->copyDefaultPermission('tst','delete',				'iass','delete');
        $pu->copyDefaultPermission('tst','read_learning_progress','iass','read_learning_progress');
        $pu->copyDefaultPermission('tst','edit_learning_progress','iass','edit_learning_progress');
        $pu->copyDefaultPermission('tst','edit_permission',	    'iass','edit_permission');
        $pu->copyDefaultPermission('tst','tst_results',	        'iass','edit_members');
        $pu->copyDefaultPermission('tst','tst_results',	        'iass','amend_grading');
    }

    /**
     * Copy from Group to LearningSequence
     */
    public function initLearningSequence()
    {
        $pu = new ilPermissionUtils(true);

        $pu->copyDefaultPermission('grp','visible',			    'lso','visible');
        $pu->copyDefaultPermission('grp','read',				    'lso','read');
        $pu->copyDefaultPermission('grp','copy',				    'lso','copy');
        $pu->copyDefaultPermission('grp','write',				    'lso','write');
        $pu->copyDefaultPermission('grp','delete',				'lso','delete');
        $pu->copyDefaultPermission('grp','join',				    'lso','participate');
        $pu->copyDefaultPermission('grp','leave',				    'lso','unparticipate');
        $pu->copyDefaultPermission('grp','read_learning_progress','lso','read_learning_progress');
        $pu->copyDefaultPermission('grp','edit_learning_progress','lso','edit_learning_progress');
        $pu->copyDefaultPermission('grp','edit_permission',	    'lso','edit_permission');
        $pu->copyDefaultPermission('grp','manage_members',	    'lso','manage_members');

        $pu->copyDefaultPermission('grp','create_file',	'lso','create_file');
        $pu->copyDefaultPermission('grp','create_lm',	    'lso','create_lm');
        $pu->copyDefaultPermission('grp','create_htlm',	'lso','create_htlm');
        $pu->copyDefaultPermission('grp','create_sahs',	 'lso','create_sahs');
        $pu->copyDefaultPermission('grp','create_exc',	 'lso','create_exc');
        $pu->copyDefaultPermission('grp','create_tst',	'lso','create_tst');
        $pu->copyDefaultPermission('grp','create_svy',	 'lso','create_svy');
        $pu->copyDefaultPermission('grp','create_tst',	 'lso','create_iass');
        $pu->copyDefaultPermission('grp','create_lm',	    'lso','create_copa');
    }


    /**
     * Copy from Learning Module to H5P
     */
    public function initH5P()
    {
        $pu = new ilPermissionUtils(true);

        $pu->copyDefaultPermission('lm','visible',			    'xhfp','visible');
        $pu->copyDefaultPermission('lm','read',				    'xhfp','read');
        $pu->copyDefaultPermission('lm','copy',				    'xhfp','copy');
        $pu->copyDefaultPermission('lm','write',				    'xhfp','write');
        $pu->copyDefaultPermission('lm','delete',				    'xhfp','delete');
        $pu->copyDefaultPermission('lm','edit_permission',	    'xhfp','edit_permission');
    }

    /**
     * Copy from Learning Module to Learnplaces
     */
    public function initLearnplaces()
    {
        $pu = new ilPermissionUtils(true);

        $pu->copyDefaultPermission('lm','visible',			    'xsrl','visible');
        $pu->copyDefaultPermission('lm','read',				    'xsrl','read');
        $pu->copyDefaultPermission('lm','write',				    'xsrl','write');
        $pu->copyDefaultPermission('lm','delete',				    'xsrl','delete');
        $pu->copyDefaultPermission('lm','edit_permission',	    'xsrl','edit_permission');
    }

    /**
     * Copy from Course Reference to Group Reference
     */
    public function initGroupReference()
    {
        $pu = new ilPermissionUtils(true);

        $pu->copyDefaultPermission('crsr','visible',			    'grpr','visible');
        $pu->copyDefaultPermission('crsr','copy',				    'grpr','copy');
        $pu->copyDefaultPermission('crsr','write',				'grpr','write');
        $pu->copyDefaultPermission('crsr','delete',				'grpr','delete');
        $pu->copyDefaultPermission('crsr','edit_permission',	    'grpr','edit_permission');
    }

    /**
     * Init the create permissions for new objects
     */
    public function initCreatePermissions()
    {
        $pu = new ilPermissionUtils(true);

        $pu->copyDefaultPermissions(
            array('cat','crs','grp','fold'), array(
            array('create_grp', 'create_grpr'),
            array('create_lm', 'create_copa'),
            array('create_tst', 'create_iass'),
            array('create_grp', 'create_lso'),
            array('create_lm', 'create_xhfp'),
            array('create_lm', 'create_xsrl'),

        ));

        $pu->copyPermissions(
            array('cat','crs','grp','fold'), array(
            array('create_grp', 'create_grpr'),
            array('create_lm', 'create_copa'),
            array('create_tst', 'create_iass'),
            array('create_grp', 'create_lso'),
            array('create_lm', 'create_xhfp'),
            array('create_lm', 'create_xsrl')
        ));
    }

    /**
     * Init EduSharing
     */
    public function initEduSharing()
    {
        $pu = new ilPermissionUtils(true);

        $pu->copyDefaultPermissions(
            array('cat','crs','grp','fold'), array(
            array('create_lm', 'create_xesr')
        ));

        $pu->copyPermissions(
            array('cat','crs','grp','fold'), array(
            array('create_lm', 'create_xesr'),
        ));


        $pu->copyDefaultPermission('lm','visible',			    'xesr','visible');
        $pu->copyDefaultPermission('lm','read',			    'xesr','read');
        $pu->copyDefaultPermission('lm','copy',				    'xesr','copy');
        $pu->copyDefaultPermission('lm','write',				'xesr','write');
        $pu->copyDefaultPermission('lm','delete',				'xesr','delete');
        $pu->copyDefaultPermission('lm','edit_permission',	    'xesr','edit_permission');
    }
}