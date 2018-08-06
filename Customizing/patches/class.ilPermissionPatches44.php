<?php
include_once("./Customizing/classes/class.ilPermissionUtils.php");

/**
 * fim: [cust] permission patches for ILIAS 4.4
 */
class ilPermissionPatches44
{

	public function initBibliography()
	{
		$pu = new ilPermissionUtils(true);

		$pu->copyDefaultPermission('glo','visible',			    'bibl','visible');
		$pu->copyDefaultPermission('glo','read',				'bibl','read');
		$pu->copyDefaultPermission('glo','copy',				'bibl','copy');
		$pu->copyDefaultPermission('glo','write',				'bibl','write');
		$pu->copyDefaultPermission('glo','delete',				'bibl','delete');
		$pu->copyDefaultPermission('glo','edit_permission',	    'bibl','edit_permission');

		$pu->copyDefaultPermissions(
			array('cat','crs','grp','fold'), array(
			array('create_glo', 'create_bibl')
		));
		$pu->copyPermissions(
			array('cat','crs','grp','fold'), array(
			array('create_glo', 'create_bibl')
		));
	}


    public function initPortfolioTemplate()
    {
        $pu = new ilPermissionUtils(true);

        $pu->copyDefaultPermission('wiki','visible',			'prtt','visible');
        $pu->copyDefaultPermission('wiki','read',				'prtt','read');
        $pu->copyDefaultPermission('wiki','copy',				'prtt','copy');
        $pu->copyDefaultPermission('wiki','write',				'prtt','write');
        $pu->copyDefaultPermission('wiki','delete',				'prtt','delete');
        $pu->copyDefaultPermission('wiki','edit_permission',	'prtt','edit_permission');

        $pu->copyDefaultPermissions(
            array('cat','crs','grp','fold'), array(
            array('create_wiki', 'create_prtt')
        ));
        $pu->copyPermissions(
            array('cat','crs','grp','fold'), array(
            array('create_wiki', 'create_prtt')
        ));
    }


    public function initGlossaryEditContent()
    {
        $pu = new ilPermissionUtils(true);

        $pu->copyDefaultPermission('glo','write',		'glo','edit_content');
        $pu->copyPermissions(
            array('glo'), array(
            array('write', 'edit_content')
        ));

    }


    public function initEtherpad()
    {
        $pu = new ilPermissionUtils(true);

        $pu->copyDefaultPermission('chtr','visible',			'xpdl','visible');
        $pu->copyDefaultPermission('chtr','read',				'xpdl','read');
        $pu->copyDefaultPermission('chtr','write',				'xpdl','write');
        $pu->copyDefaultPermission('chtr','delete',				'xpdl','delete');
        $pu->copyDefaultPermission('chtr','edit_permission',	'xpdl','edit_permission');

        $pu->copyDefaultPermissions(
            array('cat','crs','grp','fold'), array(
            array('create_chtr', 'create_xpdl')
        ));
        $pu->copyPermissions(
            array('cat','crs','grp','fold'), array(
            array('create_chtr', 'create_xpdl')
        ));
    }
}