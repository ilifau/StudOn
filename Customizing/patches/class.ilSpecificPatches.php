<?php
/**
 * fim: [cust] specific patches.
 */
class ilSpecificPatches
{
    /** @var ilDBInterface  */
    protected $db;

    public function __construct() {
        global $DIC;
        $this->db = $DIC->database();
    }

	/**
	 * Add an imported online help module to the repository
	 */
	public function addOnlineHelpToRepository($params = array('obj_id'=>null, 'parent_ref_id'=> null))
	{
		$help_obj = new ilObject();
		$help_obj->setId($params['obj_id']);
		$help_obj->createReference();
		$help_obj->putInTree($params['parent_ref_id']);
	}

    /**
     * Replace a  text in several content pages
     *
     * @param array $params
     */
    public function replacePageTexts($params = array('parent_id'=> 0, 'search' => '', 'replace'=> ''))
    {
        global  $ilDB;

        $query1 = "SELECT * FROM page_object WHERE parent_id=".$ilDB->quote($params['parent_id'], 'integer')
            . " AND content LIKE " . $ilDB->quote("%".$params['search']."%", 'text');

        $result = $ilDB->query($query1);

        while ($row = $ilDB->fetchAssoc($result))
        {
            $content = str_replace($params['search'], $params['replace'], $row['content']);

            $query2 = "UPDATE page_object "
                . " SET content = ". $ilDB->quote($content,'text')
                . " , render_md5 = NULL, rendered_content = NULL, rendered_time = NULL"
                . " WHERE page_id = " . $ilDB->quote($row['page_id'], 'integer')
                . " AND parent_type = " . $ilDB->quote($row['parent_type'], 'text')
                . " AND lang = ". $ilDB->quote($row['lang'], 'text');


            echo $query2. "\n\n";
            $ilDB->manipulate($query2);
        }
    }

    /**
     * Create separate H5P objects for H5P contents in ILIAS pages
     * Copied or imported pages may have vreated double usages of the same content id
     */
    public function splitH5PPageContents()
    {
        require_once "Customizing/global/plugins/Services/COPage/PageComponent/H5PPageComponent/classes/class.ilH5PPageComponentPlugin.php";
        $h5pPlugin = new ilH5PPageComponentPlugin();

        if (!$h5pPlugin->isActive()) {
            echo "\plugin not active!";
            return;
        }

        // run this on the slave (or on the development platform)
        // export the result as inserts
        // insert the ids to help table _page_ids
        // $query = "SELECT page_id, parent_type FROM page_object WHERE content LIKE '%H5PPageComponent%' ORDER BY page_id ASC";

        // run this on the master
        $query = "SELECT page_id, parent_type FROM _page_ids  ORDER BY page_id ASC";

        $found_content_ids = [];
        $result = $this->db->query($query);
        while ($row = $this->db->fetchAssoc($result)) {

            /** @var ilPageObject $pageObject */
            $pageObject = ilPageObjectFactory::getInstance($row['parent_type'], $row['page_id']);
            $xml =  $pageObject->getXmlContent();
            $dom = domxml_open_mem(
                '<?xml version="1.0" encoding="UTF-8"?>' . $xml,
                DOMXML_LOAD_PARSING,
                $error
            );
            $xpath = new DOMXPath($dom->myDOMDocument);

            $modified = false;
            /** @var DOMElement $node */
            $nodes = $xpath->query("//Plugged");
            foreach ($nodes as $node) {
                $plugin_name = $node->getAttribute('PluginName');
                $plugin_version = $node->getAttribute('PluginVersion');

                if ($plugin_name == 'H5PPageComponent') {
                    $properties = [];
                    /** @var DOMElement $child */
                    foreach ($node->childNodes as $child) {
                        $properties[$child->getAttribute('Name')] = $child->nodeValue;
                    }

                    // first found content id kan be kept, remember it
                    $content_id = $properties['content_id'];
                    if (!in_array($content_id, $found_content_ids)) {
                        echo "\nPage " . $row['page_id'] . ": found and kept h5p id " . $content_id;
                        $found_content_ids[] = $content_id;
                        continue;
                    }

                    // let the plugin copy additional content
                    // and allow it to modify the saved parameters
                    $h5pPlugin->setPageObj($pageObject);
                    $h5pPlugin->onClone($properties, $plugin_version);

                    // a non-existing id is kept in onClone
                    if ($properties['content_id'] == $content_id) {
                        echo "\nPage " . $row['page_id'] . ": h5p id " .$content_id . " could not be cloned";
                    }
                    else {
                        foreach ($node->childNodes as $child) {
                            $node->removeChild($child);
                        }
                        foreach ($properties as $name => $value) {
                            $child = new DOMElement('PluggedProperty', $value);
                            $node->appendChild($child);
                            $child->setAttribute('Name', $name);
                        }
                        $modified = true;

                        echo "\nPage " . $row['page_id'] . ": replaced h5p id " .$content_id . " by " .  $properties['content_id'];
                    }
                }
            }

            if ($modified) {
                $xml = $dom->dump_mem(0, $pageObject->encoding);
                $xml = preg_replace('/<\?xml[^>]*>/i', "", $xml);
                $xml = preg_replace('/<!DOCTYPE[^>]*>/i', "", $xml);

                $pageObject->setXMLContent($xml);
                $pageObject->update();
            }
        }
    }


	/**
	 * Merge the questions of different pools into one pool, structured by taxonomy
	 * @param array $params
	 */
	public function mergeQuestionPoolsAsTaxonomy($params = array(
		'containerRefId'=> 0,
		'targetRefId'=> 0,
		'navTax'=>'Thema',
		'randomTax'=> "Verwendung",
		'randomNodes' => array('Übung'=> 0.75, 'Klausur' => 1)),
		$currentRefId = null,
		$currentNodeId = null)
	{
		global $tree, $ilDB, $ilLog;

		include_once("./Services/Taxonomy/classes/class.ilObjTaxonomy.php");
		include_once("./Services/Taxonomy/classes/class.ilTaxonomyNode.php");
		include_once("./Services/Taxonomy/classes/class.ilTaxonomyTree.php");
		include_once("./Services/Taxonomy/classes/class.ilTaxNodeAssignment.php");

		static $navTax = null;
		static $navTree = null;
		static $navAss = null;
		static $randomTax = null;
		static $randomAss = null;
		static $randomNodes = array();	//node_id => share

		// set the tag objects
		$targetObjId = ilObject::_lookupObjId($params['targetRefId']);

		// create the navigation taxonomy (only at first call)
		if (!isset($navTax))
		{
			$navTax = new ilObjTaxonomy();
			$navTax->setTitle($params['navTax']);
			$navTax->create();
			ilObjTaxonomy::saveUsage($navTax->getId(), $targetObjId);

			$navTree = $navTax->getTree();
			$navTree->readRootId();

			$navAss = new ilTaxNodeAssignment('qpl',$targetObjId,'quest',$navTax->getId());
		}

		// create the random taxonomy and its nodes (only at first call)
		if (!isset($randomTax) and !empty($params['randomTax']))
		{
			$randomTax = new ilObjTaxonomy();
			$randomTax->setTitle($params['randomTax']);
			$randomTax->create();
			ilObjTaxonomy::saveUsage($randomTax->getId(), $targetObjId);

			$randomTree = $randomTax->getTree();
			$randomTree->readRootId();
			foreach ($params['randomNodes'] as $title => $share)
			{
				$node = new ilTaxonomyNode();
				$node->setTaxonomyId($randomTax->getId());
				$node->setTitle($title);
				$node->create();
				$randomTree->insertNode($node->getId(), $randomTree->getRootId());
				$randomNodes[$node->getId()] = $share;
			}

			$randomAss = new ilTaxNodeAssignment('qpl',$targetObjId,'quest',$randomTax->getId());
		}

		// get the current positions in repository and taxonomy
		if (!isset($currentRefId)) {
			$currentRefId = $params['containerRefId'];
		}
		if (!isset($currentNodeId)) {
			$currentNodeId = $navTree->getRootId();
		}

		foreach ($tree->getChilds($currentRefId) as $repNodeData)
		{
			// don't process the target pool
			if ($repNodeData['ref_id'] == $params['targetRefId']) {
				continue;
			}

			// all possible types: create taxonomy node
			if (in_array($repNodeData['type'], array('cat','crs','grp','fold','qpl')))
			{
				$title = $repNodeData['title'];
				$description = $repNodeData['description'];
				if (empty($description) and strpos($title, ':') > 0 )
				{
					$description = trim(substr($title, strpos($title, ':') +1));
					$title = trim(substr($title, 0, strpos($title, ':')));
				}
				$node = new ilTaxonomyNode();
				$node->setTaxonomyId($navTax->getId());
				$node->setTitle($title);
				$node->setDescription($description);
				$node->create();
				$navTree->insertNode($node->getId(), $currentNodeId);
			}

			switch ($repNodeData['type'])
			{
				// container: create navigation node, process childs
				case 'cat':
				case 'crs':
				case 'grp':
				case 'fold':
					$this->mergeQuestionPoolsAsTaxonomy($params, $repNodeData['ref_id'], $node->getId());
					break;

				// question pool: assign questions
				case 'qpl':

					$questionIds = array();
					$query = "SELECT question_id, title FROM qpl_questions WHERE obj_fi = ". $ilDB->quote($repNodeData['obj_id'], 'integer');
					$result = $ilDB->query($query);

					// move questions to the new pool and collect their ids
					while ($row = $ilDB->fetchAssoc($result))
					{
						echo ".";
						$update = "UPDATE qpl_questions SET obj_fi = " . $ilDB->quote($targetObjId, 'integer')
							. " WHERE question_id = " .$ilDB->quote($row['question_id'], 'integer');
						$ilDB->manipulate($update);
						$questionIds[] = $row['question_id'];

						// assign the navigation taxonomy
						$navAss->addAssignment($node->getId(), $row['question_id']);
					}

					// assign the random taxonomies
					foreach ($randomNodes as $nodeId => $share)
					{
						$num = floor($share * count($questionIds));
						if ($num > 0)
						{
							$randKeys = (array) array_rand($questionIds, $num);
							foreach ($randKeys as $key)
							{
								$randomAss->addAssignment($nodeId, $questionIds[$key]);
							}
						}
					}

					break;

				default:
					break;
			}
		}
	}

	/**
	 * Compare the stored results of accounting questions with newly calculated
	 * This works with AccountingQuestion plugin version 1.3.1
	 * @ TODO: change call of getSolutionStored in ilias 5.1 or delete this patch
	 */
	public function compareAccountingQuestionResults()
	{
		global $ilDB;
		require_once ("./Customizing/global/plugins/Modules/TestQuestionPool/Questions/assAccountingQuestion/classes/class.assAccountingQuestion.php");

		$query = "
			SELECT DISTINCT question_fi, active_fi, pass
			FROM tst_solutions
			WHERE value1 LIKE 'accqst%'
			ORDER BY question_fi, active_fi, pass";
		$query_result = $ilDB->query($query);

		$question = new assAccountingQuestion();
		while($row = $ilDB->fetchObject($query_result))
		{
			// get a new question
			if ($row->question_id != $question->getId())
			{
				echo  $row->question_fi.", ";
				$question->loadFromDb($row->question_fi);
			}

			// get the stored solution in old format (prior to 1.3.1)
			// TODO: add a second true in ilias 5.1
			$solution = $question->getSolutionStored($row->active_fi, $row->pass, true);
			foreach ($question->getParts() as $part_obj)
			{
				$part_id = $part_obj->getPartId();
				if (isset($solution[$part_id]['result']))
				{
					$part_obj->analyzeWorkingXML($solution[$part_id]['input']);
					$stored = $solution[$part_id]['result'];
					$calculated = $part_obj->calculateReachedPoints();

					// points differ
					if ($calculated != $stored)
					{
						echo "\n";
						echo "question ". $row->question_fi.", ";
						echo "active ". $row->active_fi.", ";
						echo "pass ". $row->pass.", ";
						echo "stored ". $stored, ", ";
						echo "calculated " . $calculated."\n";
						echo "Booking: " .print_r($part_obj->getBookingData(), true);
						echo "Working: " .print_r($part_obj->getWorkingData(),true);
						echo $solution[$part_id]['correct'];
						echo $solution[$part_id]['student'];
						exit;
					}
				}
			}
		}
	}


	/*
	 * Convert all results of accounting questions to the format of version 1.3.1
	 * This is done as a patch for studon to keep da data in exam platforms untouched
	 * New versions of the plugin are able to read the old format
	 * But the ond format needs much more records and space
	 */
	function convertAccountingQuestionResults()
	{
		global $ilDB;

		// Remove old stored results from flash (1.3.1 will calculate them from the input
		$ilDB->manipulate("DELETE FROM tst_solutions WHERE value1 LIKE 'accqst_student%'");
		$ilDB->manipulate("DELETE FROM tst_solutions WHERE value1 LIKE 'accqst_correct%'");
		$ilDB->manipulate("DELETE FROM tst_solutions WHERE value1 LIKE 'accqst_result%'");
	}


	/**
	 * Change the URL prefix of referenced media in media objects
	 * Clear the rendered content of the ilias pages in which theyare used
	 */
	function changeRemoteMediaUrlPrefix($params = array('search'=> '', 'replace' => '', 'update' => false))
	{
		global $ilDB;

		require_once "./Services/MediaObjects/classes/class.ilObjMediaObject.php";

		$query1 = "SELECT * FROM media_item WHERE location_type='Reference' AND location LIKE "
			.$ilDB->quote($params['search'].'%','text');

		$result1 = $ilDB->query($query1);
		while ($row1 = $ilDB->fetchAssoc($result1))
		{
			$original = $row1['location'];
			$replacement = $params['replace'] . substr($original, strlen($params['search']));

			echo $original . ' => ' . $replacement. "\n";

			if ($params['update'])
			{
				$query2 = "UPDATE media_item SET location = " . $ilDB->quote($replacement, 'text')
							. "WHERE id = " . $ilDB->quote($row1['id'], 'integer');
				$ilDB->manipulate($query2);
}

			$usages = ilObjMediaObject::lookupUsages($row1['mob_id'], true);
			foreach ($usages as $usage)
			{
				if (substr($usage['type'], -3) == ':pg')
				{
					$obj_id = ilObjMediaObject::getParentObjectIdForUsage($usage, true);
					$obj_type = ilObject::_lookupType($obj_id);
					$references = ilObject::_getAllReferences($obj_id);
					foreach ($references as $ref_id)
					{
						if ($obj_type == 'lm')
						{
							echo "\t"."https://www.studon.fau.de/pg" . $usage['id']. '_' .$ref_id  . '.html' . "\n";
						}
						elseif ($obj_type == 'wiki')
						{
							echo "\t"."https://www.studon.fau.de/wikiwpage_" . $usage['id']. '_' .$ref_id  . '.html' . "\n";
						}
						else
						{
							echo "\t"."https://www.studon.fau.de/" . $obj_type . $ref_id . '.html' . "\n";
						}
					}

					if ($params['update'])
					{
						$query3 = "UPDATE page_object SET render_md5 = null, rendered_content = null, rendered_time= null"
							." WHERE page_id=" . $ilDB->quote($usage['id'], 'integer');
						$ilDB->manipulate($query3);
					}
				}
			}
		}
	}


	/**
	 * Remove members from a course that are on the waiting list
	 */
	function removeCourseMembersWhenOnWaitingList($params=array('obj_id'=> 0))
	{
		include_once('./Modules/Course/classes/class.ilObjCourse.php');
		include_once('./Modules/Course/classes/class.ilCourseParticipants.php');
		include_once('./Modules/Course/classes/class.ilCourseWaitingList.php');

		$list_obj = new ilCourseWaitingList($params['obj_id']);
		$part_obj = new ilCourseParticipants($params['obj_id']);

		foreach ($part_obj->getMembers() as $user_id)
		{
			if ($list_obj->isOnList($user_id))
			{
				$part_obj->delete($user_id);
				echo "deleted: ". $user_id;
			}
		}
	}

    /**
     * Count the uploads done in exercises and sum up the file sizes
     */
	function countExerciseUploads($params = array('start_id'=> 730000)) {
	    global $DIC;

	    $query = "
	    SELECT filename from exc_returned t
        WHERE returned_id > " . $DIC->database()->quote((int) $params['start_id'], 'integer') . "
        AND filename IS NOT null
	    ";

	    $res = $DIC->database()->query($query);

	    $count = 0;
	    $sum = 0;
	    $max = 0;

	    while ($row = $DIC->database()->fetchAssoc($res)) {
            $size = filesize($row['filename']);
            if ($size) {
                echo "\n" . $size . ' ' . $row['filename'];
                $count++;
                $sum += $size;
                if ($size > $max) {
                    $max = $size;
                }
            }
         }

        echo "\nResult: ";
	    echo "\nCount: " . $count;
	    echo "\nSum: " . $sum;
        echo "\nMax: " . $max;
	    echo "\nAverage: " . $sum / $count;
    }
}

