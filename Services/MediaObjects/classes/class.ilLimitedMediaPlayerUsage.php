<?php
/**
 * Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE
 */

/**
 * fim: [media] usage log for limited media player
 *
 * @author Jesus Copado <jesus.copado@fim.uni-erlangen.de>
 * @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
 * @version $Id$
 */
class ilLimitedMediaPlayerUsage
{
	const CONTEXT_USER = 'user';
	const CONTEXT_SESSION = 'session';
	const CONTEXT_TESTPASS = 'testpass';
	
	/**
	 * @var integer		count of media uses
	 */
	private $uses = 0;
	
	/**
	 * @var integer		testpass of the counted uses (-1 if context is not a testpass)
	 */
	private $pass = -1;	
	
	private $page_id;
	private $mob_id;
	private $user_id;
	private $context;

	/**
	 * Constructor
	 * @param	int		id of the content page
	 * @param	int		id of the media object
	 * @param	int		user id
	 * @param	string	usage context
	 */
	public function __construct($page_id, $mob_id, $user_id, $context = self::CONTEXT_SESSION)
	{
		$this->page_id = (int) $page_id;
		$this->mob_id = (int) $mob_id;
		$this->user_id = (int) $user_id;
		$this->context = $context;
		$this->read();
	}

	
	/**
	 * Get the number of uses for the currently active context
	 * @param	int		(optional) currently active test pass
	 * @return	int		number us uses 
	 */
	public function getUses($pass = 0)
	{
		if ($this->context == self::CONTEXT_TESTPASS)
		{
			if ($this->pass != $pass)
			{
				// new test pass started
				// or test data of user deleted
				return 0;
			}
			else
			{
				return $this->uses;
			}
		}
		else
		{
			return $this->uses;
		}
			
	}
	
	/**
	 * Set the number of uses for the currently active context
	 * @param	int		number of uses to set
	 * @param	int		(optional) test pass to set
	 */
	public function updateUsage($uses, $pass = 0)
	{
		if ($this->context == self::CONTEXT_TESTPASS)
		{
			$this->uses = (int) $uses;
			$this->pass = (int) $pass;
		}
		else
		{
			$this->uses = (int) $uses;
			$this->pass = -1;
		}
		$this->write();
	}
	
	/**
	 * read data from storage, depending on the context
	 */
	private function read()
	{
		global $ilDB;
		
		if ($this->context == self::CONTEXT_SESSION)
		{
			$uses = $_SESSION['lmpy_uses-'. $this->page_id .'-'.$this->mob_id .'-'.$this->user_id];			
			if (isset($uses))
			{
				$this->uses = (int) $uses;
			}
			else
			{
				$this->uses = 0;
			}
		}
		else
		{			
			$query = "SELECT * FROM lmpy_uses "
			. " WHERE page_id = " . $ilDB->quote($this->page_id, 'integer')
			. " AND mob_id = " . $ilDB->quote($this->mob_id, 'integer')
			. " AND user_id = " . $ilDB->quote($this->user_id, 'integer');
			$res = $ilDB->query($query);

			$row = $ilDB->fetchAssoc($res);
			if ($row)
			{
				$this->uses = (int) $row['uses'];
				$this->pass = (int) $row['pass'];
			}
		}
	}
	
	/**
	 * write data to the storage, depending on the context
	 */
	private function write()
	{
		global $ilDB;
		
		if ($this->context == self::CONTEXT_SESSION)
		{
			$_SESSION['lmpy_uses-'. $this->page_id .'-'.$this->mob_id .'-'.$this->user_id] = (int) $this->uses;	
		}
		else
		{
			$ilDB->replace('lmpy_uses', 
				array(
					'page_id' => array('integer', $this->page_id),
					'mob_id' => array('integer', $this->mob_id),
					'user_id' => array('integer', $this->user_id),
				), 
				array(
					'uses' => array('integer', $this->uses),
					'pass' => array('integer', $this->pass)
				)
			);	
		}
	}

	/**
	 * delete the test pass related uasge data
	 *
	 * @param	integer		$a_obj_id	test object id
	 * @param	integer		$a_user_id	(optional) user id
	 */
	static function _deleteTestPassUsages($a_obj_id, $a_user_id = null)
	{
		global $ilDB;

		$query = "DELETE FROM lmpy_uses"
			." WHERE page_id IN (SELECT page_id FROM page_object WHERE parent_id = " . $ilDB->quote($a_obj_id, "integer") . ")"
			." AND pass >= 0";

		if (isset($a_user_id))
		{
			$query .= " AND user_id = ". $ilDB->quote($a_user_id, "integer");
		}

		$ilDB->manipulate($query);
	}
}
?>