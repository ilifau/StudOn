<?php
/**
 * fau: customPatches - apply local patches
 *
 * called from console: apply_patch.php username password client_id
 */
chdir(dirname(__FILE__)."/..");
include_once("./Customizing/classes/class.ilPatchStartUp.php");
$p = new ilPatchStartUp($_SERVER['argv'][3], $_SERVER['argv'][1], $_SERVER['argv'][2]);
$p->login();

/*****************
* Specific actions
******************/

//$p->applyPatch('ilSpecificPatches.addOnlineHelpToRepository', array('obj_id'=>19093, 'parent_ref_id'=>97));
//$p->applyPatch('ilSpecificPatches.replacePageTexts', array('parent_id'=>1436623, 'search'=>'131.188.192.86', 'replace'=> '131.188.103.103'));
//$p->applyPatch('ilSpecificPatches.mergeQuestionPoolsAsTaxonomy', array('containerRefId' => 1187922, 'targetRefId' => 1307954, 'navTax' =>'Thema', 'randomTax' => "Verwendung", 'randomNodes' => array('Übung'=> 0.75, 'Klausur' => 1)));
//$p->applyPatch('ilSpecificPatches.compareAccountingQuestionResults');
//$p->applyPatch('ilSpecificPatches.convertAccountingQuestionResults');
//$p->applyPatch('ilSpecificPatches.changeRemoteMediaUrlPrefix', array('search'=> 'http://', 'replace' => 'https://', 'update' => false));
//$p->applyPatch('ilSpecificPatches.removeCourseMembersWhenOnWaitingList', array('obj_id' => 2569770));
//$p->applyPatch('ilSpecificPatches.countExerciseUploads', array('start_id'=> 737000));

//$p->applyPatch('ilSpecificPatches.importUsersOnline', array('inputfile'=> 'data/logs/2019-10-14_bis_2020-10-24/online.log'));
//$p->applyPatch('ilSpecificPatches.importUsersOnline', array('inputfile'=> 'data/logs/2020-10-24_bis_2021-01-01/online.log'));
//$p->applyPatch('ilSpecificPatches.importUsersOnline', array('inputfile'=> 'data/logs/2021-01-01_bis_2021-02-15/online.log'));


/***********
 * Cleanups
 **********/
// This needs a query on the slave and filling a help table!
// SELECT page_id, parent_type FROM page_object WHERE content LIKE '%H5PPageComponent%' ORDER BY page_id ASC
// Insert the result to _page_ids
// $p->applyPatch('ilSpecificPatches.splitH5PPageContents');

//$p->applyPatch('ilCleanupPatches.RemoveTrashedObjects', array('types' => 'bibl,blog,book,catr,chtr,copa,crsr,dcl,exc,feed,frm,glo,grpr,htlm,iass,itgr,lm,mcst,mep,poll,prg,prtt,qpl,sahs,sess,spl,svy,tst,webr,wiki,xcos,xhfp,xflc,xlvo,xpdl,xsrl,xvid,xxco', 'deleted_before' => '2021-03-01 00:00:00', 'limit' => null));
//$p->applyPatch('ilCleanupPatches.RemoveTrashedObjects', array('types' => 'file', 'deleted_before' => '2021-03-01 00:00:00', 'limit' => null));
//$p->applyPatch('ilCleanupPatches.RemoveTrashedObjects', array('types' => 'lso', 'deleted_before' => '2021-03-01 00:00:00', 'limit' => null));
//$p->applyPatch('ilCleanupPatches.RemoveTrashedObjects', array('types' => 'fold', 'deleted_before' => '2021-03-01 00:00:00', 'limit' => null));
//$p->applyPatch('ilCleanupPatches.RemoveTrashedObjects', array('types' => 'grp', 'deleted_before' => '2021-03-01 00:00:00', 'limit' => null));
//$p->applyPatch('ilCleanupPatches.RemoveTrashedObjects', array('types' => 'crs', 'deleted_before' => '2021-03-01 00:00:00', 'limit' => null));
//$p->applyPatch('ilCleanupPatches.RemoveTrashedObjects', array('types' => 'cat', 'deleted_before' => '2021-03-01 00:00:00', 'limit' => null));
//$p->applyPatch('ilCleanupPatches.deleteOldPageHistory', array('delete_until' => '2021-03-01 00:00:00'));
//$p->applyPatch('ilCleanupPatches.moveDeletedMediaObjects', array('keep_deleted_after' => '2021-03-01 00:00:00'));

//$p->applyPatch('ilCleanupPatches.setOldUsersInactive', array('inactive_since' => '2020-04-01 00:00:00', 'limit' => null));
//$p->applyPatch('ilCleanupPatches.deleteInactiveUsers', array('inactive_since' => '2019-04-01 00:00:00', 'limit' => null));
//$p->applyPatch('ilCleanupPatches.handleObsoleteTestAccounts', array('limit' => null));

/*******************
* Patches for UnivIS
********************/

//$p->applyPatch('ilUnivisPatches.dropUnivisTables');
//$p->applyPatch('ilUnivisPatches.createUnivisTables');
//$p->applyPatch('ilUnivisPatches.testUnivisImport');


/*******************************
 * New Permissions in ILIAS 4.4
 ******************************/

//$p->applyPatch('ilPermissionPatches44.initBibliography');
//$p->applyPatch('ilPermissionPatches44.initPortfolioTemplate');
//$p->applyPatch('ilPermissionPatches44.initGlossaryEditContent');
//$p->applyPatch('ilPermissionPatches44.initEtherpad');

/*******************************
 * New Permissions in ILIAS 5.0
 ******************************/

//$p->applyPatch('ilPermissionPatches50.initLiveVoting');
//$p->applyPatch('ilPermissionPatches50.initFlashcardsCopyPermission');

/*******************************
 * New Permissions in ILIAS 5.1
 ******************************/

//$p->applyPatch('ilPermissionPatches51.initInteractiveVideo');
//$p->applyPatch('ilPermissionPatches51.initCombiSubscription');
//$p->applyPatch('ilPermissionPatches51.initAdobeConnect');

/*******************************
 * New Permissions in ILIAS 5.4
 ******************************/

//$p->applyPatch('ilPermissionPatches54.adaptBlog');
//$p->applyPatch('ilPermissionPatches54.adaptDataCollection');
//$p->applyPatch('ilPermissionPatches54.adaptWiki');
//$p->applyPatch('ilPermissionPatches54.initContentPage');
//$p->applyPatch('ilPermissionPatches54.initIndividualAssesment');
//$p->applyPatch('ilPermissionPatches54.initLearningSequence');
//$p->applyPatch('ilPermissionPatches54.initH5P');
//$p->applyPatch('ilPermissionPatches54.initLearnplaces');
//$p->applyPatch('ilPermissionPatches54.initGroupReference');
//
//$p->applyPatch('ilPermissionPatches54.initCreatePermissions');
//$p->applyPatch('ilPermissionPatches54.initEduSharing');

$p->logout();