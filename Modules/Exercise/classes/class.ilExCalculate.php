<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * fau: exManCalc - new class for result calculation.
 *
 * @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
 * @version $Id$
 *
 * @ingroup ModulesExercise
*/
class ilExCalculate
{
	private $object = null;
	private $options = array();
	private $assignments = array();
	private $count_mandatory = 0;
	
	/**
	 * constructor
	 * 
	 * @param object	related test object
	 */
	function __construct($a_object)
	{
		$this->object = $a_object;
		$this->readOptions();
		$this->readAssignments();
	}
	
	/**
	 * init the options 
	 */
	function initOptions()
	{
		$this->options = array();
		$this->options['mark_function'] = 'average';		// average | sum
		$this->options['mark_select'] = 'all';				// all | mandatory | number
		$this->options['mark_select_count'] = '';			// 
		$this->options['mark_select_order'] = '';			// highest | lowest
		$this->options['status_calculate'] = '';			// 0 | 1
		$this->options['status_compare'] = '';				// higher_equal | lower_equal 	
		$this->options['status_compare_value'] = '';		
	}
	
	
	/**
	 * read the mycampus options for this test from the database
	 */
	function readOptions()
	{
		global $ilDB;
		
		$this->initOptions();
		
		$query = 'SELECT * FROM exc_calc_options WHERE obj_id='
			. $ilDB->quote($this->object->getId(), 'integer');	
		$result = $ilDB->query($query);
		
		while ($row = $ilDB->fetchAssoc($result))
		{
			$this->options[$row['option_key']] = $row['option_value'];
		}
	}
	
	
	/**
	 * write the mycampus options for this test to the datebase
	 */
	function writeOptions()
	{
		global $ilDB;
		
		$query = 'DELETE FROM exc_calc_options WHERE obj_id='
			. $ilDB->quote($this->object->getId(), 'integer');	
		
		$ilDB->manipulate($query);
		
		if (count($this->options))
		{
			$insert = array();
			foreach ($this->options as $key => $value)
			{
				$insert[] = array($key, $value);
			}
			
			$query = 'INSERT INTO exc_calc_options(obj_id, option_key, option_value) VALUES ('
				.$ilDB->quote($this->object->getId(), 'integer') . ', ?, ?)';
	
			$query = $ilDB->prepareManip($query, array('text', 'text'));
			$ilDB->executeMultiple($query, $insert);
		}
	}	
	
	
	/**
	 * get an option value
	 * 
	 * @param 	string	key
	 * @return 	string	value
	 */
	function getOption($a_key)
	{
		return $this->options[$a_key];
	}
	
	
	/**
	 * set an option value
	 * 
	 * @param 	string	key
	 * @param 	string	value
	 */
	function setOption($a_key, $a_value)
	{
		$this->options[$a_key] = $a_value;
	}

	
	/**
	 * calculate the overall results and store them in the learning progress
	 * 
	 * @param 	array	list of user ids  (empty for all users)
	 */
	function calculateResults($a_usr_ids = array())
	{
		global $ilDB;
		
		include_once "./Modules/Exercise/classes/class.ilExerciseMembers.php";
		include_once 'Services/Tracking/classes/class.ilLPMarks.php';
		
		// get the list of assignments
		$ass_ids = array_keys($this->assignments);
		
		// get the list of users
		if (count($a_usr_ids))
		{
			$usr_ids = $a_usr_ids;
		}
		else
		{
			$usr_ids = ilExerciseMembers::_getMembers($this->object->getId());
		}
		
		// get the results data for all assignments and selected users
		$q = "SELECT * FROM exc_mem_ass_status WHERE "
			.$ilDB->in('ass_id', $ass_ids, false, 'integer'). " AND "
			.$ilDB->in('usr_id', $usr_ids, false, 'integer');
		$set = $ilDB->query($q);
		while($rec = $ilDB->fetchAssoc($set))
		{
			$results[$rec['usr_id']][$rec['ass_id']] = $rec;
		}
		
		// calculate and write the overall mark and status
		foreach ($usr_ids as $usr_id)
		{
			$mark = $this->calculateMarkOfUser($results[$usr_id]);			

			$marks_obj = new ilLPMarks($this->object->getId(), $usr_id);
			$marks_obj->setMark($mark);
			$marks_obj->update();
			
			if ($this->options['status_calculate'])
			{
				$status = $this->calculateStatusByMark($mark);
				ilExerciseMembers::_writeStatus($this->object->getId(), $usr_id, $status);	
			}
		}
	}

	
	/**
	 * calculate the mark depending on assignment results
	 * 
	 * @param 	array	assignment data of a user ($assignment id -> result array)
	 * @return	int		calculated mark (or null if mark couldn't be calculated)
	 */	
	private function calculateMarkOfUser($a_results)
	{
		// lists of marks
		$selected = array();
		$candidates = array();
		
		// pre-selection of results
		foreach ($a_results as $result)
		{
			$mandatory = $this->assignments[$result['ass_id']]['mandatory'];
			
			if (empty($result['mark']))
			{
				if ($mandatory)
				{
					// mandatory mark not available
					return null;
				}
			}
			else
			{
				$mark = str_replace(',', '.', $result['mark']);
				
				if (!is_numeric($mark))
				{
					// mark can't be used for calculation
					return null;
				}				
				elseif ($mandatory)
				{
					$selected[] = $mark;
				}
				elseif ($this->options['mark_select'] == 'marked')
				{	
					$selected[] = $mark;
				}
				elseif ($this->options['mark_select'] == 'number')
				{
					$candidates[] = $mark;
				}
			}
		}

		// selection of candidates
		if ($this->options['mark_select'] == 'number')
		{
			$needed =  $this->options['mark_select_count'];
			if (count($selected) + count($candidates) < $needed)
			{
				// not enough marks available
				return null;
			}
			elseif (count($selected) < $needed)
			{
				sort($candidates, SORT_NUMERIC);	
				if ($this->options['mark_select_order'] = 'highest')
				{
					$candidates = array_reverse($candidates);						
				}
				
				for ($i = 0; $i < $needed - count($selected); $i++)
				{
					$selected[] = $candidates[$i];
				}
			}
		}
		
		// calculation of the mark
		$sum = 0;
		foreach ($selected as $mark)
		{
			$sum += $mark;
		}
		switch ($this->options['mark_function'])
		{
			case 'sum':
				return $sum;
				break;
				
			case 'average':
				return round($sum / count($selected), 2);
				break;

			default:
				return null;
				break;	
		}	
	}
	
	
	/**
	 * calculate the status depending on the mark
	 *
	 * @param 	int 	mark
	 * @return	string	learning progress status
	 */
	private function calculateStatusByMark($a_mark = null)
	{
		if (!isset($a_mark))
		{
			return $this->options['status_default'];
		}
		
		$mark = (float) $a_mark;
		$value = (float) $this->options['status_compare_value'];
		switch($this->options['status_compare'])
		{
			case 'higher':
				return $mark > $value ? 'passed' : 'failed';
				break;
			
			case 'lower':
				return $mark < $value ? 'passed' : 'failed';
				break;
			
			case 'higher_equal':
				return $mark >= $value ? 'passed' : 'failed';
				break;

			case 'lower_equal':
				return $mark <= $value ? 'passed' : 'failed';
				break;		
		}
	}
	
	
	/**
	 * read the assignments data into an indexed array
	 */
	private function readAssignments()
	{
		include_once "./Modules/Exercise/classes/class.ilExAssignment.php";
		$ass_data = ilExAssignment::getAssignmentDataOfExercise($this->object->getId());
		
		$this->count_mandatory = 0;
		$this->assignments = array();
		foreach ($ass_data as $data)
		{
			$this->assignments[$data['id']] = $data;
			if ($data['mandatory'])
			{
				$this->count_mandatory++;
			}
		}
	}
}

?>
