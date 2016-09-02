<?php
/* fim: [webform] new class. */

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* Class ilWebFormUtils
* Provides general methods for the web forms
*
* @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
* $Id:  $
*
*/
class ilWebFormUtils
{
	/**
	* Get all forms in a course, sorted by module and title
	*
	* @param    int     ref id of the course
	* @return   array   form_id => module and form title
	*/
	function _getCourseFormsList($a_ref_id)
	{
		global $tree, $ilDB;

		// get HTML modules in the course
		$node = $tree->getNodeData($a_ref_id);
		$childs = $tree->getSubTree($node, true, "htlm");

		// sort modules by title
		$modules = array();
		foreach ($childs as $child)
		{
			$modules[$child["obj_id"]] = $child["title"];
		}
		asort($modules);

		// get forms of the modules
		$list = array();
		foreach ($modules as $module_id => $module_title)
		{
			$q = "SELECT * FROM webform_types WHERE lm_obj_id = "
				. $ilDB->quote($module_id, 'integer')
				. " ORDER BY title";
			$result = $ilDB->query($q);
			while ($row = $ilDB->fetchAssoc($result))
			{
				$list[$row["form_id"]] = $module_title." / ". $row["title"];
			}
		}
		return $list;
	}
	
	/**
	* Get all users in a course, sorted by name
	*
	* @param    int     obj id of the course
	* @return   array   user_id => name
	*/
	function _getCourseUsersList($a_obj_id)
	{
		include_once  "./Modules/Course/classes/class.ilCourseParticipants.php";

		$participantsObj = ilCourseParticipants::_getInstanceByObjId($a_obj_id);
		$participants = $participantsObj->getParticipants();

		$list = array();
		foreach ($participants as $user_id)
		{
			$name = ilObjUser::_lookupName($user_id);
			$list[$user_id] = $name["lastname"]. ", ". $name["firstname"];
		}
		asort($list);
		return $list;
	}
	
	
	/**
	* Find the readable reference of an object
	* @param    int     Object Id 
	* @return   int     Ref Id 
	*/
	function _findReadableRefId($a_obj_id)
	{
		global $ilAccess;
		
		$refs = ilObject::_getAllReferences($a_obj_id);
		foreach ($refs as $ref_id)
		{
			if ($ilAccess->checkAccess("read","view",$ref_id))
			{
				return $ref_id;
			}
		}
	}

	/**
	* Find the ref_id of the parent course
	* Object and course have to be readable
	*
	* @param    int     Object Id of the learning module
	* @return   int     Ref Id pf the parent course
	*/
	function _findCourseRefId($a_lm_obj_id)
	{
		global $ilAccess, $tree;

		$refs = ilObject::_getAllReferences($a_lm_obj_id);
		foreach ($refs as $ref_id)
		{
			if ($ilAccess->checkAccess("read","view",$ref_id))
			{
				if ($crs_ref_id = $tree->checkForParentType($ref_id, "crs"))
				{
					if ($ilAccess->checkAccess("read","view",$crs_ref_id))
					{
						return $crs_ref_id;
					}
				}
			}
		}
	}

	/**
	* Determine the reference id of a forum
	* The forum nust be within the same course as the learning module
	*
	* @param 	string 	The name of the forum
	* @param    int     object id of the learning module
	* @return 	int 	Reference id of the forum
	*/
	function _findForumRefId($a_forum_name, $a_lm_obj_id)
	{
		global $tree, $ilAccess;

		if (!$crs_ref_id = self::_findCourseRefId($a_lm_obj_id))
		{
			return 0;
		}

		$node = $tree->getNodeData($crs_ref_id);
		$forums = $tree->getSubTree($node, true, "frm");
		foreach ($forums as $forum)
		{
			if (($forum["title"] == $a_forum_name)
			and $ilAccess->checkAccess("read", "view", $forum["ref_id"]))
			{
				return $forum["ref_id"];
			}
		}
		return 0;
	}

	/**
	* Determine the thread id of the forum thread posting
	* @return 	array 	"thr_pk" => thread id, "pos_pk" => thread position
	* @access	static
	*/
	function _findForumThreadId($a_forum_id, $a_thread_name)
	{
		global $ilDB,$ilErr;
		$ret = array();

		$q = "SELECT thr_pk FROM frm_threads ".
		" WHERE thr_top_fk = ".$ilDB->quote($a_forum_id, 'integer').
		" AND thr_subject = ".$ilDB->quote($a_thread_name, 'text');
		$r = $ilDB->query($q);
		if ($r->numRows() == 0)
		{
			return $ret;
		}
		$row = $ilDB->fetchAssoc($r);

		// The smallest pos_pk for a thread id in table frm_posts is the position
		// of the thread itself (i guess).
		$thread_pk = $row["thr_pk"];

		$ilDB->setLimit(1);
		$q = "SELECT pos_pk FROM frm_posts ".
		" WHERE pos_thr_fk = ".$ilDB->quote($thread_pk, 'integer').
		" ORDER BY pos_pk ASC";
		$r = $ilDB->query($q);
		if ($r->numRows() == 0)
		{
			return $ret;
		}
		$row = $ilDB->fetchAssoc($r);

		$ret["pos_pk"] = $row["pos_pk"];
		$ret["thr_pk"] = $thread_pk;
		return $ret;
	}

} // END class.ilWebFormUtils

?>
