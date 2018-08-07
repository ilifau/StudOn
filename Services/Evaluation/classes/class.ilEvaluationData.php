<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* fim: [evasys] class for evaluation data
*/

class ilEvaluationData
{	

	/**
	 * Check if an object can be marked for evaluation
	 * 
	 * The object must have an imported univis_id 
	 * 
	 * @param 	object	 	course or group
	 * @return	boolean		object is evaluable
	 */
	static function _isObjEvaluable($a_obj)
	{
		require_once ('./Services/UnivIS/classes/class.ilUnivisLecture.php');
		if (ilUnivisLecture::_isIliasImportId($a_obj->getImportId()))
		{
			return true;
        }
        else
        {
        	return false;
        }
	}
	
	
	/**
	 * Check if evaluation is activated for an objects sutree
	 * 
	 * The object must be a descendant of a category in the custom setting 'eval_categories'
	 * 
	 * @param 	integer	 	ref id
	 * @return	boolean		evaluation is activated
	 */
	static function _isEvaluationActivated($a_ref_id)
	{
		global $tree;

		$categories = explode(',', ilCust::get('eval_categories'));
        foreach ($categories as $cat_ref_id)
		{
			if ((int) $cat_ref_id > 0 and $tree->isGrandChild((int) $cat_ref_id, $a_ref_id))
			{
				return true;
			}
		}
		return false;
	}

	
	/**
	 * check if an object is marked for evaluation
	 * 
	 * @param 	object	 	course or group
	 * @return	boolean		object is marked
	 */
	static function _isObjMarkedForEvaluation($a_obj)
	{
		global $ilDB;
		
		$query = "SELECT ref_id FROM eval_marked_objects WHERE ref_id = "
				. $ilDB->quote($a_obj->getRefId(), 'integer');

		$result = $ilDB->query($query);
		if ($ilDB->fetchAssoc($result))
		{
			return true;
		}
		else
		{
			return false;
		}				
	}
	
	
	/**
	 * mark/unmark an object for evaluation
	 * 
	 * @param 	object	 	course or group
	 * @param	boolean		status
	 */
	static function  _setObjMarkedForEvaluation($a_obj, $a_mark = true)
	{
		global $ilDB;
		
		if ($a_mark)
		{
			$ilDB->replace(
				'eval_marked_objects',
				array('ref_id' => array('integer', $a_obj->getRefId())),
				array()
			);	
		}
		else
		{
			$query = "DELETE FROM eval_marked_objects WHERE ref_id = "
			. $ilDB->quote($a_obj->getRefId(), 'integer');
			$ilDB->manipulate($query);
		}
	}
}

?>