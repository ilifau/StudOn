<?php
include_once("./Customizing/classes/class.ilPermissionUtils.php");

/**
 * fim: [cust] permission patches for ILIAS 5.1
 */
class ilPermissionPatches51
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
	 * Copy from Booking Pool to Combined Subscription
	 */
	public function initCombiSubscription()
	{
		$pu = new ilPermissionUtils(true);

		$pu->copyDefaultPermission('book','visible',			'xcos','visible');
		$pu->copyDefaultPermission('book','read',				'xcos','read');
		$pu->copyDefaultPermission('book','write',				'xcos','write');
		$pu->copyDefaultPermission('book','delete',				'xcos','delete');
		$pu->copyDefaultPermission('book','edit_permission',	'xcos','edit_permission');

		$pu->copyDefaultPermissions(
			array('cat','crs','grp','fold'), array(
			array('create_book', 'create_xcos')
		));
		$pu->copyPermissions(
			array('cat','crs','grp','fold'), array(
			array('create_book', 'create_xcos')
		));
	}


}