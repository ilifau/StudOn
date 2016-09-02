<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once ("Services/UnivIS/classes/class.ilUnivis.php");

/**
*  fim: [univis] class for executing requests from Univis
*/
class ilUnivisRequest
{
	/**
	 * Execute a request
	 */
	public function execute()
	{	
		$univis_id = ilUtil::stripSlashes($_GET['id']);
		
		switch ($_GET['cmd'])
		{
			case "exists":
				$this->getExistence($univis_id, $_GET['id']);
				break;
			
			case "link":
				$this->showLink($univis_id);
				break;
				
			case "join":
				$this->gotoObject($univis_id, '_join');
				break;
				
			default:
				$this->gotoObject($univis_id);
				break;
		}
	}
	
	
	/**
	 *  Check if an object with a specific univis id exists and is visible
	 *  
	 *  @param 	string		univis id
	 */
	private function getExistence($univis_id)
	{
		$objects = ilUnivis::_getUntrashedObjectsForUnivisId($univis_id);
		header('Access-Control-Allow-Origin: *');
		echo count($objects) ? 1 : 0;
		exit;
	}
	
	
	/**
	 *  Go to the object defined by the univis id
	 *  
	 *  @param 	string		univis id
	 *  @param	string		appendix for target
	 */
	private function gotoObject($univis_id, $append = '')
	{
		ilUtil::redirect('goto.php?target=univis_'. $univis_id . $append);
	}
	
	
	/**
	 * Show a link to a resource identified by a univis id
	 * 
	 *  @param 	string		univis id
	 */
	private function showLink($univis_id)
	{
		global $lng;
		
		$objects = ilUnivis::_getUntrashedObjectsForUnivisId($univis_id);
		if (!count($objects))
		{
			$content = "";
		}
		else
		{
			$object = current($objects);
			require_once('./Services/Link/classes/class.ilLink.php');
			$content = sprintf($lng->txt('univis_link_to_studon'), ilLink::_getLink($object['ref_id']));			
		}
		
		$html = file_get_contents('./Services/UnivIS/templates/default/tpl.univis_frame_content.html');
		$html = str_replace('{CONTENT}', $content, $html);
		echo $html;
	}
}

?>