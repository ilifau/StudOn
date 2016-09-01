<?php
include_once("./Customizing/classes/class.ilPermissionUtils.php");

/**
 * fim: [cust] permission patches for ILIAS 5.0
 */
class ilPermissionPatches50
{
	/**
	 * Copy from Chatroom to AdobeConnect
	 */
	public function initAdobeConnect()
	{
		$pu = new ilPermissionUtils(true);

		$pu->copyDefaultPermission('chtr','visible',			'xavc','visible');
		$pu->copyDefaultPermission('chtr','read',				'xavc','read');
		$pu->copyDefaultPermission('chtr','write',				'xavc','write');
		$pu->copyDefaultPermission('chtr','delete',				'xavc','delete');
		$pu->copyDefaultPermission('chtr','edit_permission',	'xavc','edit_permission');

		$pu->copyDefaultPermissions(
			array('cat','crs','grp','fold'), array(
			array('create_chtr', 'create_xavc')
		));
		$pu->copyPermissions(
			array('cat','crs','grp','fold'), array(
			array('create_chtr', 'create_xavc')
		));
	}

	/**
	 * Copy from MediaCast to Interactive Video
	 */
	public function initInteractiveVideo()
	{
		$pu = new ilPermissionUtils(true);

		$pu->copyDefaultPermission('mcst','visible',			'xvid','visible');
		$pu->copyDefaultPermission('mcst','read',				'xvid','read');
		$pu->copyDefaultPermission('mcst','write',				'xvid','write');
		$pu->copyDefaultPermission('mcst','delete',				'xvid','delete');
		$pu->copyDefaultPermission('mcst','edit_permission',	'xvid','edit_permission');

		$pu->copyDefaultPermissions(
			array('cat','crs','grp','fold'), array(
			array('create_mcst', 'create_xvid')
		));
		$pu->copyPermissions(
			array('cat','crs','grp','fold'), array(
			array('create_mcst', 'create_xvid')
		));
	}


	/**
	 * Copy from Survey to LiveVoting
	 */
	public function initLiveVoting()
	{
		$pu = new ilPermissionUtils(true);

		$pu->copyDefaultPermission('svy','visible',			    'xlvo','visible');
		$pu->copyDefaultPermission('svy','read',				'xlvo','read');
		$pu->copyDefaultPermission('svy','write',				'xlvo','write');
		$pu->copyDefaultPermission('svy','delete',				'xlvo','delete');
		$pu->copyDefaultPermission('svy','edit_permission',	    'xlvo','edit_permission');

		$pu->copyDefaultPermissions(
			array('cat','crs','grp','fold'), array(
			array('create_svy', 'create_xlvo')
		));
		$pu->copyPermissions(
			array('cat','crs','grp','fold'), array(
			array('create_svy', 'create_xlvo')
		));
	}


	/**
	 * Init "copy" permission for Flashcard plugin
	 */
	public function initFlashcardsCopyPermission()
	{
		$pu = new ilPermissionUtils(true);

		$pu->copyDefaultPermission('xflc','write',		'xflc','copy');
		$pu->copyPermissions(
			array('xflc'), array(
			array('write', 'copy')
		));
	}
}