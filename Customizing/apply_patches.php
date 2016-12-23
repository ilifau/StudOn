<?php
/**
 * fim: [cust] apply local patches
 *
 * called from console: apply_patch.php username password client_id
 */

chdir(dirname(__FILE__)."/..");
include_once("./Customizing/classes/class.ilPatchUtils.php");
$p = new ilPatchUtils();

/*******************
 * Studydata actions
 *******************/

//$p->applyPatch('ilStudyDataPatches.updateStudyDataCodes');
//$p->applyPatch('ilStudyDataPatches.searchStudentsWithDifferentFaculties');
//$p->applyPatch('ilStudyDataPatches.testIdmData', array('identity' => 'ar38icuf'));

/*****************
* Specific actions
******************/

//$p->applyPatch('ilSpecificPatches.addOnlineHelpToRepository', array('obj_id'=>1372742, 'parent_ref_id'=>23071));
//$p->applyPatch('ilSpecificPatches.replacePageTexts', array('parent_id'=>1436623, 'search'=>'131.188.192.86', 'replace'=> '131.188.103.103'));
//$p->applyPatch('ilSpecificPatches.mergeQuestionPoolsAsTaxonomy', array('containerRefId' => 1187922, 'targetRefId' => 1307954, 'navTax' =>'Thema', 'randomTax' => "Verwendung", 'randomNodes' => array('Übung'=> 0.75, 'Klausur' => 1)));
//$p->applyPatch('ilSpecificPatches.compareAccountingQuestionResults');
//$p->applyPatch('ilSpecificPatches.convertAccountingQuestionResults');
//$p->applyPatch('ilSpecificPatches.changeRemoteMediaUrlPrefix', array('search'=> 'http://', 'replace' => 'https://', 'update' => false));


/***********
 * Cleanups
 **********/
//$p->applyPatch('ilCleanupPatches.checkDoublePermissionTemplates', array('cleanup' => false, 'min'=> 2));
//$p->applyPatch('ilCleanupPatches.RemoveTrashedObjects', array('types' => 'bibl,blog,book,catr,chtr,crsr,dcl,exc,feed,frm,glo,htlm,itgr,lm,mcst,mep,poll,prtt,qpl,sahs,sess,spl,svy,tst,webr,wiki,xcos,xflc,xlvo,xpdl,xxco', 'deleted_before' => '2016-10-01 00:00:00', 'limit' => null));
//$p->applyPatch('ilCleanupPatches.RemoveTrashedObjects', array('types' => 'file', 'deleted_before' => '2016-10-01 00:00:00', 'limit' => null));
//$p->applyPatch('ilCleanupPatches.RemoveTrashedObjects', array('types' => 'fold', 'deleted_before' => '2016-10-01 00:00:00', 'limit' => null));
//$p->applyPatch('ilCleanupPatches.RemoveTrashedObjects', array('types' => 'grp', 'deleted_before' => '2016-10-01 00:00:00', 'limit' => null));
//$p->applyPatch('ilCleanupPatches.RemoveTrashedObjects', array('types' => 'crs', 'deleted_before' => '2016-10-01 00:00:00', 'limit' => null));
//$p->applyPatch('ilCleanupPatches.RemoveTrashedObjects', array('types' => 'cat', 'deleted_before' => '2016-10-01 00:00:00', 'limit' => null));
//$p->applyPatch('ilCleanupPatches.deleteOldPageHistory', array('delete_until' => '2016-10-01 00:00:00'));
//$p->applyPatch('ilCleanupPatches.moveDeletedMediaObjects', array('keep_deleted_after' => '2016-10-01 00:00:00'));


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
//$p->applyPatch('ilPermissionPatches51.initAdobeConnect');