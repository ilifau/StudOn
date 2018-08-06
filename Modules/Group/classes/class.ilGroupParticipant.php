<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2006 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/

include_once('./Services/Membership/classes/class.ilParticipant.php');

/** 
* fim: [meminf] new class, copied from ilCourseParticipant
 *
* @ingroup ModulesGroup
*/

class ilGroupParticipant extends ilParticipant
{
	const COMPONENT_NAME = 'Modules/Course';
	
	protected static $instances = array();
	
	/**
	 * Singleton constructor
	 *
	 * @access protected
	 * @param int obj_id of container
	 */
	public function __construct($a_obj_id,$a_usr_id)
	{
		$this->type = 'grp';
		
		parent::__construct(self::COMPONENT_NAME,$a_obj_id,$a_usr_id);
	}

	/**
	 * Get singleton instance
	 *
	 * @access public
	 * @static
	 *
	 * @param int obj_id
	 * @return ilCourseParticipant
	 */
	public static function _getInstanceByObjId($a_obj_id,$a_usr_id)
	{
		if(self::$instances[$a_obj_id][$a_usr_id])
		{
			return self::$instances[$a_obj_id][$a_usr_id];
		}
		return self::$instances[$a_obj_id][$a_usr_id] = new ilGroupParticipant($a_obj_id,$a_usr_id);
	}
}
?>