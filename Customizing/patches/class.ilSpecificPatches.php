<?php
/**
 * fau: customPatches - specific patches.
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

        $query1 = "SELECT * FROM page_object WHERE content LIKE " . $ilDB->quote("%".$params['search']."%", 'text');

        if ($params['parent_id'] > 0) {
            $query1 .= " AND parent_id=".$ilDB->quote($params['parent_id'], 'integer');
        }

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
     * Copied or imported pages may have created double usages of the same content id
     * DANGER! May not work as expected anymore!
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

                    // first found content id can be kept, remember it
                    $content_id = $properties['content_id'];
                    if (!in_array($content_id, $found_content_ids)) {
                        // echo "\nPage " . $row['page_id'] . ": found and kept h5p id " . $content_id;
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


    /**
     * Send an email to all active users
     * @param string[] $params
     * @return false|void
     */
    function sendMassMail($params = array('subject' => 'StudOn', 'bodyfile' => 'data/mail.txt'))
    {
        global $DIC;
        $db = $DIC->database();

        $subject = $params['subject'];
        $content = file_get_contents($params['bodyfile']);
        if (empty($content)) {
            echo "Mail body not read!";
            return;
        }

        $query = "
            SELECT usr_id, login
            FROM usr_data
            WHERE active = 1
            AND mass_mail_sent IS NULL
            AND first_login IS NOT NULL
            AND login NOT LIKE '%.test1'
            AND login NOT LIKE '%.test2'
            AND (time_limit_unlimited = 1 OR (time_limit_from < UNIX_TIMESTAMP() AND time_limit_until > UNIX_TIMESTAMP()))
        ";

//        $query = "
//            SELECT usr_id, login
//            FROM usr_data
//            WHERE login like 'fred.neumann%'
//            AND mass_mail_sent IS NULL
//        ";

        $result = $db->query($query);

        $count = 1;
        while ($row = $db->fetchAssoc($result)) {

            $login = $row['login'];

            $mail = new ilMail(ANONYMOUS_USER_ID);
            $errors = $mail->sendMail($login, '', '', $subject, $content, [],['system'], false);

            if (!empty($errors)) {
                echo $count++ . ': ' . $login . " (ERROR)\n";
            }
            else {
                echo $count++ . ': ' . $login . "\n";
            }

            $sent = date("Y-m-d H:i:s");
            $update = "UPDATE usr_data set mass_mail_sent=" . $db->quote($sent, 'text')
                . ' WHERE usr_id = ' . $db->quote($row['usr_id'], 'integer');

            $db->manipulate($update);

            usleep(360000);
        }
    }
    

    public function renameObjects()
    {
        global $DIC;
        $ilDB = $this->db;
        $app_event = $DIC->event();

        $lines = file(__DIR__ . '/rename.csv');
        foreach ($lines as $line) {
            list($ref_id, $title) = explode(';', $line);

            $title = trim($title);
            $obj_id = ilObject::_lookupObjId($ref_id);
            $type = ilObject::_lookupType($obj_id);

            $q = "UPDATE object_data SET title = " . $ilDB->quote($title, "text") .
                " WHERE obj_id = " . $ilDB->quote($obj_id, "integer");
            echo $q . "\n";

            $ilDB->manipulate($q);

            $app_event->raise(
                'Services/Object',
                'update',
                array('obj_id' => $obj_id,
                    'obj_type' => $type,
                    'ref_id' => $ref_id)
            );

            $trans = ilObjectTranslation::getInstance($obj_id);
            $trans->setDefaultTitle($title);
            $trans->save();
        }
    }

    /**
     * @return void
     */
    public function syncExamUsers($params=["deactivate_missing" => false, "deactivate_participants" => false]) 
    {
        global $DIC;
        $db = $DIC->database();
        $review = $DIC->rbac()->review();

        $time_string = (new ilDateTime(time(), IL_CAL_UNIX))->get(IL_CAL_DATETIME);
        
        $settings = $DIC->clientIni()->readGroup('db_remote');
        $remote = new \ilDBPdoMySQLInnoDB();
        $remote->setDBHost($settings['host']);
        $remote->setDBPort($settings['port']);
        $remote->setDBUser($settings['user']);
        $remote->setDBPassword($settings['pass']);
        $remote->setDBName($settings['name']);
        if (!$remote->connect()) {
            throw new Exception("can't connect to remote db");
        }
        
        // get the participant role id
        $participant_role_id = 0;
        $query = "SELECT obj_id FROM object_data WHERE `type` = 'role' AND title = 'Teilnehmer'";
        $result = $db->query($query);
        if ($row = $db->fetchAssoc($result)) {
            $participant_role_id = $row['obj_id'];
        }
        
        // loop through all accounts
        $query = "SELECT usr_id, login FROM usr_data";
        $result = $db->query($query);
        while ($row = $db->fetchAssoc($result)) {
            $usr_id = $row['usr_id'];
            $login = $row['login'];
            
            if ($login == 'root') {
                continue;
            }
            
            $query = "SELECT * FROM usr_data where login = " . $db->quote($login, 'text');
            $result2 = $remote->query($query);
            if ($data = $remote->fetchAssoc($result2)) {
                
                // account is found in remote database
                echo "\nUPDATE $login";
                $this->db->update('usr_data',
                    [
                        'login' => ['text', $data['login']],
                        'firstname' => ['text', $data['firstname']],
                        'lastname' => ['text', $data['lastname']],
                        'title' => ['text', $data['title']],
                        'gender' => ['text', $data['gender']],
                        'email' => ['text', $data['email']],
                        'institution' => ['text', $data['institution']],
                        'matriculation' => ['text', $data['matriculation']],
                        'approve_date' => ['text', $data['approve_date']],
                        'agree_date' => ['text', $data['agree_date']],
                        'auth_mode' => ['text', $data['auth_mode']],
                        'ext_account' => ['text', $data['ext_account']],
                        'passwd' => ['text', $data['passwd']],
                        'passwd_enc_type' => ['text', $data['passwd_enc_type']],
                        'passwd_salt' => ['text', $data['passwd_salt']],
                        'active' => ['integer', $data['active']],
                        'time_limit_unlimited' => ['integer', $data['time_limit_unlimited']],
                        'time_limit_from' => ['integer', $data['time_limit_from']],
                        'time_limit_until' => ['integer', $data['time_limit_until']],
                        'last_update' => ['text', $time_string],
                        'last_password_change' => ['integer', time()],
                    ],
                    [
                        'usr_id' => ['integer', $usr_id]
                    ]
                );
            }
            elseif ($params['deactivate_missing']) {
                
                // account is not found in remote database, so deactivate
                echo "\nDEACTIVE missing $login";
                $this->db->update('usr_data', ['active' => 0], ['usr_id' => ['integer', $usr_id]]);
            }

            if ($params['deactivate_participants']) {
                $roles = $review->assignedGlobalRoles($usr_id);
                if (count($roles) == 1 && $roles[0] == $participant_role_id) {
                    
                    // account is only participant, so deactivate
                    echo "\nDEACTIVE participant $login";
                    $this->db->update('usr_data', ['active' => 0], ['usr_id' => ['integer', $usr_id]]);
                }
            }
        }
        
        echo "\nDelete password of shibboleth accounts...";
        $query = "UPDATE usr_data SET passwd = NULL WHERE auth_mode = 'shibboleth'";
        $db->manipulate($query);
    }
}

