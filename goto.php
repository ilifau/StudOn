<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* redirection script
* todo: (a better solution should control the processing
* via a xml file)
*
* $_GET["target"]  should be of format <type>_<id>
*
* @author Alex Killing <alex.killing@gmx.de>
* @package ilias-core
* @version $Id$
*/

//var_dump ($_SESSION);
//var_dump ($_COOKIE);

// this should bring us all session data of the desired
// client
require_once("Services/Init/classes/class.ilInitialisation.php");
ilInitialisation::initILIAS();

// special handling for direct navigation request
require_once "./Services/Navigation/classes/class.ilNavigationHistoryGUI.php";
$nav_hist = new ilNavigationHistoryGUI();
$nav_hist->handleNavigationRequest();

// store original parameter before plugin slot may influence it
$orig_target = $_GET['target'];

// user interface plugin slot hook
if (is_object($ilPluginAdmin))
{
	// get user interface plugins
	$pl_names = $ilPluginAdmin->getActivePluginsForSlot(IL_COMP_SERVICE, "UIComponent", "uihk");

	// search
	foreach ($pl_names as $pl)
	{
		$ui_plugin = ilPluginAdmin::getPluginObject(IL_COMP_SERVICE, "UIComponent", "uihk", $pl);
		$gui_class = $ui_plugin->getUIClassInstance();
		$gui_class->gotoHook();
	}
}

// fau: relativeLink - goto hook for rewriting the target
if (substr($_GET['target'], 0, 6) == 'lcode_')
{
	require_once("Services/RelativeLink/classes/class.ilRelativeLinkGUI.php");
	$relgui = new ilRelativeLinkGUI();
	$relgui->gotoHook();
}
// fau.

// fau: numericLink - lookup the type when only the ref_id or obj_id is given
if (is_numeric($_GET['target']))
{
    $type = ilObject::_lookupType((int) $_GET['target'], true);

    // check if obj_id is given
    if (empty($type))
    {
        $ref_ids = ilObject::_getAllReferences($_GET['target']);
        foreach($ref_ids as $ref_id)
        {
            if(!ilObject::_isInTrash($ref_id))
            {
            	$_GET['target'] = $ref_id;
                $type = ilObject::_lookupType((int) $_GET['target'], true);
            	break;
            }
        }
	}

    if (!empty($type)) {
        $_GET['target'] = $type . '_' . (int) $_GET['target'];
    }
}
// fau.

$r_pos = strpos($_GET["target"], "_");
$rest = substr($_GET["target"], $r_pos+1);
$target_arr = explode("_", $_GET["target"]);
$target_type = $target_arr[0];
$target_id = $target_arr[1];
$additional = $target_arr[2];		// optional for pages

// imprint has no ref id...
if($target_type == "impr")
{
	ilUtil::redirect('ilias.php?baseClass=ilImprintGUI');
}

// goto is not granted?
include_once("Services/Init/classes/class.ilStartUpGUI.php");
if(!ilStartUpGUI::_checkGoto($_GET["target"]))
{
	// if anonymous: go to login page
	if(!$ilUser->getId() || $ilUser->isAnonymous())
	{
		ilUtil::redirect("login.php?target=".$orig_target."&cmd=force_login&lang=".$ilUser->getCurrentLanguage());
	}
	else
	{
		// message if target given but not accessible
		$tarr = explode("_", $_GET["target"]);
		if ($tarr[0] != "pg" && $tarr[0] != "st" && $tarr[1] > 0)
		{
			ilUtil::sendFailure(sprintf($lng->txt("msg_no_perm_read_item"),
				ilObject::_lookupTitle(ilObject::_lookupObjId($tarr[1]))), true);
		}

		ilUtil::redirect('ilias.php?baseClass=ilPersonalDesktopGUI');
	}
}

/*
 * fim: [cust] explanation of target handling
 *
 * target:			crs_123_join
 *
 * target_arr: 		array(crs, 123, join)
 * target_type: 	crs
 * target_id: 		123
 * rest: 			123_join
 * additional: 		join
 *
 * target: 			univis_2011s.Lecture.21152058_join
 *
 * target_arr: 		array(univis, 2011s.Lecture.21152058, join)
 * target_type: 	univis
 * target_id: 		2011s.Lecture.21152058
 * rest: 			2011s.Lecture.21152058_join
 * additional: 		join
 *
 * called from ilInitialisation:
 * ilStartUpGUI::_checkGoto($_GET["target"])
 * - returns true for target types 'univis' and 'studon'
 * - returns false for join command if user is anonymous
 *
 * called afterwards from goto.php:
 * ilObjXyzGUI::_goto($rest) 					(default implementation)
 * ilObjXyzGUI::_goto($target_id, $additional)	(specific implementation)
 *
 * fim.
 */

// fim: [cust] studon specific goto requests
if ($target_type == 'studon')
{
	switch($target_id)
	{
    	case "exportrequest":
			include_once 'Services/StudyData/classes/class.ilStudyExportRequestGUI.php';
			$ilCtrl->setTargetScript("goto.php");
			$ilCtrl->getCallStructure("ilstudyexportrequestgui");
			$ilCtrl->setParameterByClass("ilstudyexportrequestgui", "target", "studon_exportrequest");
			$ilCtrl->forwardCommand(new ilStudyExportRequestGUI());
			exit;

		case "agreement":
			ilUtil::redirect('ilias.php?baseClass=ilStartUpGUI&cmd=showTermsOfService');
			break;

		case "regstarts":
			ilUtil::redirect('ilias.php?baseClass=ilRegistrationPeriodLimiterGUI');
			break;

// fau: regCodes - add code to registration link
		case "register":
			if ($additional)
			{
				ilUtil::redirect('register.php?code='.$additional);
			}
			else
			{
				ilUtil::redirect('register.php');
			}
		    break;
// fau.
	}
}
// fim.

// fim: [univis] univis specific goto requests
// DEPRECATED: univis links are handeled now by univis.php
if ($target_type == 'univis')
{
	// search for the course by univis_id
	$obj_id = ilObject::_lookupObjIdByImportId($target_id);
	if (!$obj_id)
	{
		ilUtil::sendFailure($lng->txt('univis_link_object_not_found'),true);
		ilUtil::redirect('index.php');
	}
	$ref_ids = ilObject::_getAllReferences($obj_id);
	if (count($ref_ids) == 0)
	{
		ilUtil::sendFailure($lng->txt('univis_link_object_not_found'),true);
		ilUtil::redirect('index.php');
	}
	$ref_id = end($ref_ids);

	// redefine the parameters for standard target handling
	$target_type = ilObject::_lookupType($obj_id);
	$target_id = $ref_id;
	$rest = $target_id . '_'. $additional;
}
// fim.


// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
//
//               FOR NEW OBJECT TYPES:
//       PLEASE USE DEFAULT IMPLEMENTATION ONLY
//
// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

switch($target_type)
{
	// exception, must be kept for now
	case "pg":
		require_once("./Modules/LearningModule/classes/class.ilLMPageObjectGUI.php");
		ilLMPageObjectGUI::_goto($rest);
		break;

	// exception, must be kept for now
	case "st":
		require_once("./Modules/LearningModule/classes/class.ilStructureObjectGUI.php");
		ilStructureObjectGUI::_goto($target_id, $additional);
		break;

	// exception, must be kept for now
	case "git":
		require_once("./Modules/Glossary/classes/class.ilGlossaryTermGUI.php");
		$target_ref_id = $target_arr[2];
		ilGlossaryTermGUI::_goto($target_id, $target_ref_id);
		break;

	// please migrate to default branch implementation
	case "glo":
		require_once("./Modules/Glossary/classes/class.ilObjGlossaryGUI.php");
		ilObjGlossaryGUI::_goto($target_id);
		break;
				
	// please migrate to default branch implementation
	case "lm":
		require_once("./Modules/LearningModule/classes/class.ilObjContentObjectGUI.php");
		ilObjContentObjectGUI::_goto($target_id);
		break;

	// please migrate to default branch implementation
	case "htlm":
		require_once("./Modules/HTMLLearningModule/classes/class.ilObjFileBasedLMGUI.php");
		ilObjFileBasedLMGUI::_goto($target_id);
		break;
		
	// please migrate to default branch implementation
	case "frm":
		require_once("./Modules/Forum/classes/class.ilObjForumGUI.php");
		$target_thread = $target_arr[2];
		$target_posting = $target_arr[3];
		ilObjForumGUI::_goto($target_id, $target_thread, $target_posting);
		break;
		
	// please migrate to default branch implementation
	case "exc":
		require_once("./Modules/Exercise/classes/class.ilObjExerciseGUI.php");
		ilObjExerciseGUI::_goto($target_id, $rest);
		break;
		
	// please migrate to default branch implementation
	case "tst":
		require_once("./Modules/Test/classes/class.ilObjTestGUI.php");
		ilObjTestGUI::_goto($target_id);
		break;
	
	// please migrate to default branch implementation
	case "qpl":
		require_once("./Modules/TestQuestionPool/classes/class.ilObjQuestionPoolGUI.php");
		ilObjQuestionPoolGUI::_goto($target_id);
		break;

	// please migrate to default branch implementation
	case "spl":
		require_once("./Modules/SurveyQuestionPool/classes/class.ilObjSurveyQuestionPoolGUI.php");
		ilObjSurveyQuestionPoolGUI::_goto($target_id);
		break;

	// please migrate to default branch implementation
	case "svy":
		require_once("./Modules/Survey/classes/class.ilObjSurveyGUI.php");
		if (array_key_exists("accesscode", $_GET))
		{
			ilObjSurveyGUI::_goto($target_id, $_GET["accesscode"]);
		}
		else
		{
			ilObjSurveyGUI::_goto($target_id);
		}
		break;

	// please migrate to default branch implementation
	case "webr":
		require_once("./Modules/WebResource/classes/class.ilObjLinkResourceGUI.php");
		ilObjLinkResourceGUI::_goto($target_id, $rest);
		break;

	// please migrate to default branch implementation
	case "sahs":
		require_once("./Modules/ScormAicc/classes/class.ilObjSAHSLearningModuleGUI.php");
		ilObjSAHSLearningModuleGUI::_goto($target_id);
		break;

	// please migrate to default branch implementation
	case "cat":
		require_once("./Modules/Category/classes/class.ilObjCategoryGUI.php");
		ilObjCategoryGUI::_goto($target_id);
		break;

	// please migrate to default branch implementation
	case "crs":
		require_once("Modules/Course/classes/class.ilObjCourseGUI.php");
		ilObjCourseGUI::_goto($target_id, $additional);
		break;

	// please migrate to default branch implementation
	case "grp":
		require_once("./Modules/Group/classes/class.ilObjGroupGUI.php");
		ilObjGroupGUI::_goto($target_id, $additional);
		break;
		
	// please migrate to default branch implementation
	case 'fold':
		require_once("./Modules/Folder/classes/class.ilObjFolderGUI.php");
		ilObjFolderGUI::_goto($target_id);
		break;
	
	// please migrate to default branch implementation
	case "file":
		require_once("./Modules/File/classes/class.ilObjFileGUI.php");
		ilObjFileGUI::_goto($target_id, $rest);
		break;

	// please migrate to default branch implementation
	case "mcst":
		require_once("./Modules/MediaCast/classes/class.ilObjMediaCastGUI.php");
		ilObjMediaCastGUI::_goto($target_id);
		break;

	// please migrate to default branch implementation
	case 'root':
		require_once('./Modules/RootFolder/classes/class.ilObjRootFolderGUI.php');
		ilObjRootFolderGUI::_goto($target_id);
		break;
		
	// please migrate to default branch implementation
	case 'cert':
		require_once('./Services/Certificate/classes/class.ilCertificate.php');
		ilCertificate::_goto($target_id);
		break;

	// links to the documentation of the kitchen sink in the administration
	case 'stys':
		require_once('./Services/Style/System/classes/class.ilSystemStyleMainGUI.php');
		ilSystemStyleMainGUI::_goto($target_id,$target_arr);
		break;

	//
	// default implementation (should be used by all new object types)
	//
	default:
		if (!$objDefinition->isPlugin($target_type))
		{
			$class_name = "ilObj".$objDefinition->getClassName($target_type)."GUI";
			$location = $objDefinition->getLocation($target_type);
			if (is_file($location."/class.".$class_name.".php"))
			{
				include_once($location."/class.".$class_name.".php");
				call_user_func(array($class_name, "_goto"), $rest);
			}
		}
		else
		{
			$class_name = "ilObj".$objDefinition->getClassName($target_type)."GUI";
			$location = $objDefinition->getLocation($target_type);
			if (is_file($location."/class.".$class_name.".php"))
			{
				include_once($location."/class.".$class_name.".php");
				call_user_func(array($class_name, "_goto"), array($rest, $class_name));
			}
		}
		break;
}
?>
