<?php
/* Copyright (c) 1998-2011 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* fim: [vhb] new class ilVhbAadmin
*
* @author Fred Neumann <fred.neumann@fim.uni-erlangen.de> 
*/
class ilVhbAdmin
{
	
	function initIlias()
	{
		// Set the cookies correctly
		// (copied from goto.php)
		if (isset($_GET["client_id"]))
		{
			$cookie_domain = $_SERVER['SERVER_NAME'];
			$cookie_path = dirname( $_SERVER['PHP_SELF'] );

			/* if ilias is called directly within the docroot $cookie_path
			is set to '/' expecting on servers running under windows..
			here it is set to '\'.
			in both cases a further '/' won't be appended due to the following regex
			*/
			$cookie_path .= (!preg_match("/[\/|\\\\]$/", $cookie_path)) ? "/" : "";

			if($cookie_path == "\\") $cookie_path = '/';

			$cookie_domain = ''; // Temporary Fix

			setcookie("ilClientId", $_GET["client_id"], 0, $cookie_path, $cookie_domain);

			$_COOKIE["ilClientId"] = $_GET["client_id"];
		}

		// Include the header script to initialize ILIAS
		require_once("./include/inc.header.php");
	}


	function checkAdminPermission()
	{
	    global $rbacsystem;

		if (!$rbacsystem->checkAccess("visible,read", SYSTEM_FOLDER_ID))
		{
			echo "<pre>\n";
			echo "Diese Funktion ist nur für Administratoren aufrufbar!\n";
			echo "</pre>\n";
			exit;
		}
	}


	function anonymize($local_prefix, $external_prefix, $local_sign)
	{
	    global $ilDB;

		$query = "SELECT login, usr_id FROM usr_data WHERE login LIKE '%X%'";

		$res = $ilDB->query($query);
		while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC))
		{
	        $newlogin = false;

			if (preg_match("/^.+".$local_sign."$/", $row['login']))
			{
	            $newlogin = $local_prefix . $row['usr_id'];
	        }
			elseif (preg_match("/^.+X[0-9]+$/", $row['login']))
			{
	            $newlogin = $external_prefix . $row['usr_id'];
	        }

			if ($newlogin)
			{
				$query = "UPDATE usr_data"
					. " SET login=". $ilDB->quote($newlogin, 'text')
					. " WHERE usr_id =". $ilDB->quote($row['usr_id'], 'integer');

				echo $row['login'] . " => " . $newlogin. "<br />";
				$ilDB->manipulate($query);
			}
		}
		echo "Done.";
	}


	function resetPrefs($local_prefix, $external_prefix)
	{
		global $ilDB;

		$query = "SELECT usr_id FROM usr_data "
			. " WHERE login LIKE " . $ilDB->quote($local_prefix.'%', 'text')
			. " OR login LIKE ". $ilDB->quote($external_prefix.'%', 'text');

		$res = $ilDB->query($query);
		while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$userObj = new ilObjUser($row['usr_id']);
			echo $userObj->getLogin() . "<br />";
			$userObj->setPref("public_profile", "n");
			$userObj->setPref("show_users_online", "n");
			$userObj->setPref("hide_own_online_status", "y");
			$userObj->writePrefs();
		}
		echo "Done.";
	}
}
?>
