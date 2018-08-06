<?php
include_once("./Customizing/classes/class.ilPermissionUtils.php");

/**
 * fim: [cust] permission patches for ILIAS 4.3
 */
class ilPermissionPatches43
{

	public function initPoll()
	{
		$pu = new ilPermissionUtils(true);

		$pu->copyDefaultPermission('svy','read',				'poll','read');
		$pu->copyDefaultPermission('svy','copy',				'poll','copy');
		$pu->copyDefaultPermission('svy','write',				'poll','write');
		$pu->copyDefaultPermission('svy','delete',				'poll','delete');
		$pu->copyDefaultPermission('svy','edit_permission',		'poll','edit_permission');

		$pu->copyDefaultPermissions(
			array('cat','crs','grp','fold'), array(
			array('create_svy', 'create_poll')
		));
		$pu->copyPermissions(
			array('cat','crs','grp','fold'), array(
			array('create_svy', 'create_poll')
		));
	}


	public function initBlog()
	{
		$pu = new ilPermissionUtils(true);

		$pu->copyDefaultPermission('wiki','visible',			'blog','visible');
		$pu->copyDefaultPermission('wiki','read',				'blog','read');
		$pu->copyDefaultPermission('wiki','copy',				'blog','copy');
		$pu->copyDefaultPermission('wiki','write',				'blog','write');
		$pu->copyDefaultPermission('wiki','delete',				'blog','delete');
		$pu->copyDefaultPermission('wiki','edit_content',		'blog','contribute');
		$pu->copyDefaultPermission('wiki','edit_permission',	'blog','edit_permission');

		$pu->copyDefaultPermissions(
			array('cat','crs','grp','fold'), array(
			array('create_wiki', 'create_blog')
		));
		$pu->copyPermissions(
			array('cat','crs','grp','fold'), array(
			array('create_wiki', 'create_blog')
		));
	}

	public function initDataCollection()
	{
		$pu = new ilPermissionUtils(true);

		$pu->copyDefaultPermission('wiki','visible',			'dcl','visible');
		$pu->copyDefaultPermission('wiki','read',				'dcl','read');
		$pu->copyDefaultPermission('wiki','copy',				'dcl','copy');
		$pu->copyDefaultPermission('wiki','write',				'dcl','write');
		$pu->copyDefaultPermission('wiki','delete',				'dcl','delete');
		$pu->copyDefaultPermission('wiki','edit_content',		'dcl','add_entry');
		$pu->copyDefaultPermission('wiki','edit_permission',	'dcl','edit_permission');

		$pu->copyDefaultPermissions(
			array('cat','crs','grp','fold'), array(
			array('create_wiki', 'create_dcl')
		));
		$pu->copyPermissions(
			array('cat','crs','grp','fold'), array(
			array('create_wiki', 'create_dcl')
		));
	}

	public function initItemGroup()
	{
		$pu = new ilPermissionUtils(true);

		$pu->copyDefaultPermission('cat','visible',				'itgr','visible');
		$pu->copyDefaultPermission('cat','read',				'itgr','read');
		$pu->copyDefaultPermission('cat','copy',				'itgr','copy');
		$pu->copyDefaultPermission('cat','write',				'itgr','write');
		$pu->copyDefaultPermission('cat','delete',				'itgr','delete');
		$pu->copyDefaultPermission('cat','edit_permission',		'itgr','edit_permission');

		$pu->copyDefaultPermission('fold','visible',			'itgr','visible');
		$pu->copyDefaultPermission('fold','read',				'itgr','read');
		$pu->copyDefaultPermission('fold','copy',				'itgr','copy');
		$pu->copyDefaultPermission('fold','write',				'itgr','write');
		$pu->copyDefaultPermission('fold','delete',				'itgr','delete');
		$pu->copyDefaultPermission('fold','edit_permission',	'itgr','edit_permission');

		$pu->copyDefaultPermissions(
			array('cat','crs','grp','fold'), array(
			array('write', 	'create_itgr'),
		));
		$pu->copyPermissions(
			array('cat','crs','grp','fold'), array(
			array('write', 	'create_itgr'),
		));
	}


	public function initFlashCards()
	{
		$pu = new ilPermissionUtils(true);

		$pu->copyDefaultPermission('glo','visible',				'xflc','visible');
		$pu->copyDefaultPermission('glo','read',				'xflc','read');
		$pu->copyDefaultPermission('glo','write',				'xflc','write');
		$pu->copyDefaultPermission('glo','delete',				'xflc','delete');
		$pu->copyDefaultPermission('glo','edit_permission',		'xflc','edit_permission');

		$pu->copyDefaultPermissions(
			array('cat','crs','grp','fold'), array(
				array('create_glo', 'create_xflc')
		));
		$pu->copyPermissions(
			array('cat','crs','grp','fold'), array(
				array('create_glo', 'create_xflc')
		));
	}


	public function initBookingManager()
	{
		$pu = new ilPermissionUtils(true);

		$pu->copyDefaultPermission('svy','visible',				'book','visible');
		$pu->copyDefaultPermission('svy','read',				'book','read');
		$pu->copyDefaultPermission('svy','write',				'book','write');
		$pu->copyDefaultPermission('svy','delete',				'book','delete');
		$pu->copyDefaultPermission('svy','edit_permission',		'book','edit_permission');

		$pu->copyDefaultPermissions(
			array('cat','crs','grp','fold'), array(
			array('create_svy', 'create_book')
		));
		$pu->copyPermissions(
			array('cat','crs','grp','fold'), array(
			array('create_svy', 'create_book')
		));
	}


	public function initCategoryReference()
	{
		$pu = new ilPermissionUtils(true);

		$pu->copyDefaultPermission('cat','visible',				'catr','visible');
		$pu->copyDefaultPermission('cat','copy',				'catr','copy');
		$pu->copyDefaultPermission('cat','write',				'catr','write');
		$pu->copyDefaultPermission('cat','delete',				'catr','delete');
		$pu->copyDefaultPermission('cat','edit_permission',		'catr','edit_permission');

		$pu->copyDefaultPermission('fold','visible',			'catr','visible');
		$pu->copyDefaultPermission('fold','copy',				'catr','copy');
		$pu->copyDefaultPermission('fold','write',				'catr','write');
		$pu->copyDefaultPermission('fold','delete',				'catr','delete');
		$pu->copyDefaultPermission('fold','edit_permission',	'catr','edit_permission');

		$pu->copyDefaultPermissions(
			array('cat','crs','grp','fold'), array(
			array('create_cat', 'create_catr')
		));
		$pu->copyPermissions(
			array('cat','crs','grp','fold'), array(
			array('create_cat', 'create_catr')
		));
	}


	public function initCourseReference()
	{
		$pu = new ilPermissionUtils(true);

		$pu->copyDefaultPermission('crs','visible',				'crsr','visible');
		$pu->copyDefaultPermission('crs','copy',				'crsr','copy');
		$pu->copyDefaultPermission('crs','write',				'crsr','write');
		$pu->copyDefaultPermission('crs','delete',				'crsr','delete');
		$pu->copyDefaultPermission('crs','edit_permission',		'crsr','edit_permission');

		$pu->copyDefaultPermissions(
			array('cat','crs','grp','fold'), array(
			array('create_crs', 'create_crsr')
		));
		$pu->copyPermissions(
			array('cat','crs','grp','fold'), array(
			array('create_crs', 'create_crsr')
		));
	}


	public function initChatroom()
	{
		$pu = new ilPermissionUtils(true);

		$pu->copyDefaultPermission('frm','visible',				'chtr','visible');
		$pu->copyDefaultPermission('frm','read',				'chtr','read');
		$pu->copyDefaultPermission('frm','write',				'chtr','write');
		$pu->copyDefaultPermission('frm','moderate_frm',		'chtr','moderate');
		$pu->copyDefaultPermission('frm','delete',				'chtr','delete');
		$pu->copyDefaultPermission('frm','copy',				'chtr','copy');
		$pu->copyDefaultPermission('frm','edit_permission',		'chtr','edit_permission');

		$pu->setDefaultPermissions(
			array('chtr'),
			array('visible', 'read'),
			array('il_crs_admin%', 'il_crs_tutor%', 'il_crs_member%', 'il_grp_admin%', 'il_grp_member%')
		);
		$pu->setDefaultPermissions(
			array('chtr'),
			array('write', 'moderate', 'copy'),
			array('il_crs_admin%', 'il_crs_tutor%', 'il_grp_admin%')
		);
		$pu->setDefaultPermissions(
			array('chtr'),
			array('delete'),
			array('il_crs_admin%', 'il_grp_admin%')
		);
		$pu->setPermissions(
			array('chtr'),
			array('visible', 'read'),
			array('il_crs_admin%', 'il_crs_tutor%', 'il_crs_member%', 'il_grp_admin%', 'il_grp_member%')
		);
		$pu->setPermissions(
			array('chtr'),
			array('write', 'moderate', 'copy'),
			array('il_crs_admin%', 'il_crs_tutor%', 'il_grp_admin%')
		);
		$pu->setPermissions(
			array('chtr'),
			array('delete'),
			array('il_crs_admin%', 'il_grp_admin%')
		);
	}


	public function initSessionInGroup()
	{
		$pu = new ilPermissionUtils(true);

		$pu->setDefaultPermissions(
			array('sess'),
			array('visible', 'read'),
			array('il_grp_admin%', 'il_grp_member%')
		);
		$pu->setDefaultPermissions(
			array('sess'),
			array('write', 'copy', 'delete'),
			array('il_grp_admin%')
		);
		$pu->setPermissions(
			array('sess'),
			array('visible', 'read'),
			array('il_grp_admin%', 'il_grp_member%')
		);
		$pu->setPermissions(
			array('sess'),
			array('write', 'copy', 'delete'),
			array('il_grp_admin%')
		);
		$pu->copyDefaultPermissions(
			array('cat','crs','grp','fold'), array(
			array('create_fold', 'create_sess')
		));
		$pu->copyPermissions(
			array('cat','crs','grp','fold'), array(
			array('create_fold', 'create_sess')
		));
	}
}