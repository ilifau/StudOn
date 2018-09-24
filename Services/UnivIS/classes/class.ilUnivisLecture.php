<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once ('./Services/UnivIS/classes/class.ilUnivisData.php');

/**
*  fim: [uvivis] class for looking up lectures data
*/
class ilUnivisLecture extends ilUnivisData
{
    /**
	* Read the data (to be overwritten)
	*
	* @param 	string     string representation of the primary key
	*/
	function read($a_primary_key)
	{
	    global $ilDB;

		$query = "SELECT * FROM univis_lecture "
				." WHERE ". parent::_getLookupCondition()
				." AND ". parent::_getPrimaryCondition('univis_lecture', $a_primary_key);

		$result = $ilDB->query($query);
		if ($row = $ilDB->fetchAssoc($result))
		{
	        $this->data = $row;
		}
		else
		{
	        $this->data = array();
		}
	}


    /**
	* Get the primary key
	* The primary key should be extracted from the data
	*
	* @return 	string     string representation of the primary key
	*/
	function getPrimaryKey()
	{
	    return parent::_getPrimaryKey('univis_lecture', $this->data);
	}


    /**
	* Get id used as import id for ILIAS
	*
	* @return 	string    id
	*/
	function getIliasImportId()
	{
		return $this->data['semester'].'.Lecture.'.$this->data['id'];
	}


    /**
	* check if lecture registration is done via myCampus
	*
	* @return 	boolean
	*/
	function hasMyCampusRegistration()
	{
		return ($this->data['regsystem'] == 'cit');
	}

	/**
	 * check if the lecture has a waiting list
	 * 
	 * @return boolean	
	 */
	function hasWaitingList()
	{
		if ($this->data['regqueue'] == 'random')
		{
			return false;
		}
		elseif ($this->data['regwlist'] == 'ja')
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * check if the lecture has a lot list
	 * 
	 * @return boolean
	 */
	function hasLotList()
	{
		if ($this->data['regqueue'] == 'random')
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
    /**
	* Get a link for looking up the data in univis
	*/
	function getUnivisLink()
	{
	    return parent::_getPrgUrl().'search=lectures&show=llong&id='.$this->data['id']
								.'&sem='.$this->data['semester'];
	}

    /**
	* Get the lecturers
	*/
	function getLecturers()
	{
	    $lecturers = ilUnivisPerson::_getLecturersOfLecture($this->data['key'], $this->data['semester']);
		return $lecturers;
	}

	/**
	* Get the maximum turnout
	*/
	function getMaxturnout()
	{
	    return $this->data['maxturnout'];
	}
	
	/**
	 * Get the registration start date
	 * 
	 * @return object	datetime object
	 */
	function getRegStart()
	{
		require_once('./Services/Calendar/classes/class.ilDateTime.php');		
		$date = new ilDateTime(strtotime($this->data['regstart'] . ' ' . $this->data['regstarttime']), IL_CAL_UNIX);
		return $date;
	}
	
	/**
	 * Get the registration end date
	 * 
	 * @return object	datetime object
	 */
	function getRegEnd()
	{
		require_once('./Services/Calendar/classes/class.ilDateTime.php');		
		$date = new ilDateTime(strtotime($this->data['regend'] . ' ' . $this->data['regendtime']), IL_CAL_UNIX);
		return $date;
	}
	
    /**
	* Get the Title for display
	*
	* @param    boolean     add short info in brackets
	*/
	function getDisplayTitle($a_linked = false, $a_with_short = true)
	{
		$info = $this->data['name'];

		if ($a_with_short and $this->data['short'])
		{
	        $info .= ' [' . $this->data['short'] . ']';
		}


	    if ($a_linked)
		{
	        return  sprintf('<a href="%s" target="_blank">%s</a>',
							$this->getUnivisLink(),
							$info);
	    }
		else
		{
			return $info;
		}
	}


    /**
	* Get the Info line for display
	*/
	function getDisplayInfo($a_linked = false, $a_with_comment = false)
	{
	    global $lng;

		$infos = array();

		// type
		if ($this->data['type'])
		{
			$infos[] = $lng->txt('univis_lecture_type_' . $this->data['type']);
		}
		// sws
		if ($this->data['sws'])
		{
			$infos[] = $this->data['sws'] . ' ' . $lng->txt('univis_sws');
		}

		// schein
		if ($this->data['benschein'])
		{
	        $infos[] = $lng->txt('univis_benschein');
		}
		elseif ($this->data['schein'])
		{
	        $infos[] = $lng->txt('univis_schein');
	    }

		// bonus / malus
		if ($this->data['bonus'])
		{
	        $infos[] = $lng->txt('univis_credits'). ' ' . $this->data['bonus']
			. $this->data['malus'] ? ('/' .$this->data['malus']) : '';
		}

		// ects
		if ($this->data['ects'] and $this->data['ects_cred'])
		{
	        $infos[] = $lng->txt('univis_ects_credits'). ' ' . $this->data['ects_cred'];
		}
		elseif ($this->data['ects'])
		{
	        $infos[] = $lng->txt('univis_ects');
	    }

		// flags
		$flags = array(
			'beginners', 'fachstud', 'fruehstud', 'senior', 'women', 'schluessel', 'allfak', 'gast',
			'mag','dipl','mast','bac','laew','lafv','lafn','lafb','ladidg','ladidh','ladidf',
			'einf','medabschn1','medabschn2','praktjahr','schwerp'
		);
		foreach ($flags as $flag)
		{
	        if ($this->data[$flag])
			{
	            $infos[] = $lng->txt('univis_'.$flag);
			}
		}

		if ($a_with_comment and $this->data['comment'])
		{
			$infos[] = $this->data['comment'];
	    }

		return implode(', ', $infos);
	}


    /**
	* Get the Info line for display
	*/
	function getDisplayInfoShort($a_linked = false)
	{
	    global $lng;

		$infos = array();

		// type
		if ($this->data['type'])
		{
			$infos[] = strtoupper($this->data['type']);
		}
		// sws
		if ($this->data['sws'])
		{
			$infos[] = $this->data['sws'] . ' ' . $lng->txt('univis_sws');
		}

		// schein
		if ($this->data['benschein'])
		{
	        $infos[] = $lng->txt('univis_benschein_short');
		}
		elseif ($this->data['schein'])
		{
	        $infos[] = $lng->txt('univis_schein');
	    }

		// bonus / malus
		if ($this->data['bonus'])
		{
	        $infos[] = $lng->txt('univis_credits'). ' ' . $this->data['bonus']
			. $this->data['malus'] ? ('/' .$this->data['malus']) : '';
		}

		// ects
		if ($this->data['ects'] and $this->data['ects_cred'])
		{
	        $infos[] = $lng->txt('univis_ects_credits_short'). ' ' . $this->data['ects_cred'];
		}
		elseif ($this->data['ects'])
		{
	        $infos[] = $lng->txt('univis_ects_short');
	    }

		// flags
		$flags = array(
			'beginners', 'fachstud', 'fruehstud', 'senior', 'women', 'schluessel', 'allfak', 'gast',
			'mag','dipl','mast','bac','laew','lafv','lafn','lafb','ladidg','ladidh','ladidf',
			'einf','medabschn1','medabschn2','praktjahr','schwerp'
		);
		foreach ($flags as $flag)
		{
	        if ($this->data[$flag])
			{
	            $infos[] = $lng->txt('univis_'.$flag.'_short');
			}
		}

		return implode('; ', $infos);
	}


    /**
	* Get the comment for display
	*/
	function getDisplayComment()
	{
	    return $this->data['comment'];
	}


    /**
	* Get the lecturers
	*/
	function getDisplayLecturers($a_linked = false)
	{
		$info = array();

	    $lecturers = ilUnivisPerson::_getLecturersOfLecture($this->data['key'], $this->data['semester']);
		foreach ($lecturers as $lecturer)
		{
	        $info[] = $lecturer->getDisplay($a_linked);
	    }
		return implode(', ', $info);
	}

    /**
	* Get the lecturers
	*/
	function getDisplayLecturersShort($a_linked = false)
	{
		$info = array();

	    $lecturers = ilUnivisPerson::_getLecturersOfLecture($this->data['key'], $this->data['semester']);
		foreach ($lecturers as $lecturer)
		{
	        $info[] = $lecturer->getDisplayShort($a_linked);
	    }
		return implode('<br />', $info);
	}


    /**
	* Get the terms
	*/
	function getDisplayTerms($a_linked = false)
	{
	    global $lng;

		$info = array();
	    $terms = ilUnivisTerm::_getTermsOfLecture($this->data['key'], $this->data['semester']);

		foreach ($terms as $term)
		{
	        $info[] = $term->getDisplay($a_linked);
	    }

		if ($this->data['startdate'])
		{
			$info[] = $lng->txt('univis_date_from'). ' '. date('d.m.Y',strtotime($this->data['startdate']));
	    }

		return implode('<br />', $info);
	}


   	/**
	* Get the sudies for display
	*/
	function getDisplayStudies()
	{
		$info = array();
	    $studies = ilUnivisStudy::_getStudiesOfLecture($this->data['key'], $this->data['semester']);

		foreach ($studies as $study)
		{
	        $info[] = $study->getDisplay($a_linked);
	    }
		return implode('<br />', $info);
	}

   	/**
	* Get the prerequisites for display
	*/
	function getDisplayPrerequisites()
	{
	    return self::_convertText($this->data['organizational']);
	}

   	/**
	* Get the summary prerequisites for display
	*/
	function getDisplaySummary()
	{
		return self::_convertText($this->data['summary']);
	}

   	/**
	* Get the literature  for display
	*/
	function getDisplayLiterature()
	{
		return self::_convertText($this->data['literature']);
	}

   	/**
	* Get the ects info for display
	*/
	function getDisplayEctsInfo()
	{
	    global $lng;

	    $info = array();
		if ($this->data['ects_name'])
		{
	        $info[] = $lng->txt('univis_ects_info_name') . ' ' . $this->data['ects_name'];
	    }
		if ($this->data['ects_cred'])
		{
	        $info[] = $lng->txt('univis_ects_info_credits') . ' ' . $this->data['ects_cred'];
	    }
		if ($this->data['ects_organizational'])
		{
	        $info[] = $lng->txt('univis_ects_info_organizational') . ' ' . $this->data['ects_organizational'];
	    }
		if ($this->data['ects_prerequisites'])
		{
			$info[] = $lng->txt('univis_ects_info_prerequisites') . ' ' . $this->data['ects_prerequisites'];
		}
		if ($this->data['ects_summary'])
		{
	        $info[] = $lng->txt('univis_ects_info_summary') . ' ' . $this->data['ects_summary'];
	    }
		if ($this->data['ects_literature'])
		{
	        $info[] = $lng->txt('univis_ects_info_literature') . ' ' . $this->data['ects_literature'];
	    }

		return implode('<br />', $info);
	}


   	/**
	* Get the additional info for display
	*/
	function getDisplayAdditionalInfo()
	{
	    global $lng;

	    $info = array();

		if ($this->data['keywords'])
		{
	        $info[] = $lng->txt('univis_keywords') . ' ' . $this->data['keywords'];
	    }
		if ($this->data['turnout'])
		{
	        $info[] = $lng->txt('univis_turnout') . ' ' . $this->data['turnout'];
	    }
		if ($this->data['maxturnout'])
		{
	        $info[] = $lng->txt('univis_maxturnout') . ' ' . $this->data['maxturnout'];
	    }
		if ($this->data['regsystem'] == 'cit')
		{
	        $info[] = sprintf($lng->txt('univis_mycampus_registration'), $this->getIliasImportId());
	    }

		return implode('<br />', $info);

	}

   	/**
	* Get the  for display
	*/
	function getDisplayInstitution()
	{
	    return $this->data['orgname'];
	}

	/**
	* Get the semester for display
	*/
	function getDisplaySemester()
	{
	    global $lng;

	    $semester = $this->data['semester'];

		$year = substr($semester, 0, 4);
		$term = substr($semester, 4);

		if ($term == "s")
		{
	        return sprintf($lng->txt('univis_ss'), $year);
		}
		else
		{
	        return sprintf($lng->txt('univis_ws'), $year, $year + 1);
	    }
	}


  	/**
	* check if an id is an ILIAS import Iid
	*
	* @return
	*/

	static function _isIliasImportId($import_id)
	{
	    $parts = self::_splitIliasImportId($import_id);

		return (
			$parts['type'] == 'Lecture'
			and is_numeric($parts['id'])
			and is_numeric($parts['semester']['year'])
			and ($parts['semester']['sem'] = 's'
				 or $parts['semester']['sem'] = 'w')
		);
	}

   	/**
	* analyse an import id and return the parts as an array
	*
	* @return 	array   ('semester' => array , 'type' => string, 'id' => integer)
	*/
	static function _splitIliasImportId($import_id)
	{
		$split = explode('.', $import_id);

		$ret = array();
		$ret['semester'] = self::_getSemesterFromString($split[0]);
		$ret['type'] = $split[1];
		$ret['id'] = (int) $split[2];

		return $ret;
	}

   /**
	* Get a link for looking up the data in univis
	*/
	static function _getLinkForIliasImportId($import_id)
	{
		$parts = self::_splitIliasImportId($import_id);

	    return parent::_getPrgUrl().'search=lectures&show=llong&id='.$parts['id']
								.'&sem='.self::_getStringFromSemester($parts['semester']);
	}


   	/**
	* analyse a semester string and get the parts as an array
	*
	* @return 	array    ('year' => (nteger, 'sem' => string)
	*/
	static function _getSemesterFromString($a_string)
	{
	    $ret = array();
		$ret['year'] = (int) substr($a_string, 0, 4);
		$ret['sem'] = substr($a_string, 4, 1);

		return $ret;
	}

   	/**
	* alalyse a semester string and get the parts as an array
	*
	* @return 	array       ('year' => integer, 'sem' => string)
	*/
	static function _getStringFromSemester($a_semester)
	{
		return $a_semester['year'].$a_semester['sem'];
	}


   	/**
	* Get all imported lectures data
	* @return 	array       primary_key => (array) row data
	*/
	static function _getLecturesData()
	{
	    global $ilDB;

	    $return = array();

		$query = "SELECT * FROM univis_lecture "
				." WHERE ". parent::_getLookupCondition()
				." ORDER BY ". $ilDB->quoteIdentifier('name');

		$result = $ilDB->query($query);

		while ($row = $ilDB->fetchAssoc($result))
		{
	        // EXPERIMANTAL: don't get hidden lectures
	        //if (strpos($row['key'],'._') !== false)
			// {
	        //    continue;
			// }
			$primary_key = parent::_getPrimaryKey('univis_lecture', $row);
		    $return[$primary_key] = $row;
		}
		return $return;
	}
}
?>
