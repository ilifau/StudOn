<?php
// Copyright (c) 2021 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE


/**
* MySQL Wrapper for a connection to a remote ILIAS database
*
* This class extends the main ILIAS database wrapper ilDB.
*/
class ilRemoteAuthDB extends ilDBPdoMySQLInnoDB
{

	/** @var  ilExamAdminConnectorDB $instance */
	private static $instance;


	/**
	 * Get the database connection instance
	 * @return ilExamAdminConnectorDB
     * @throws Exception
	 */
	public static function getInstance()
	{
	    global $DIC;

        // read the client settings if available
        if (isset($DIC) && $DIC->offsetExists('ilClientIniFile'))
        {
            /** @var ilIniFile $ilClientIniFile */
            $ilClientIniFile = $DIC['ilClientIniFile'];
            $settings = $ilClientIniFile->readGroup('db_remote');
        }

        if (!isset(self::$instance))
        {
            $instance = new self;
            $instance->setDBHost($settings['host']);
            $instance->setDBPort($settings['port']);
            $instance->setDBUser($settings['user']);
            $instance->setDBPassword($settings['pass']);
            $instance->setDBName($settings['name']);
            if (!$instance->connect(true))
            {
                return null;
            }
            self::$instance = $instance;
        }

        return self::$instance;
	}

    /**
     * Get a user account with remote data
     * @param string $login
     * @return ilObjUser|null
     */
	public function getRemoteUser($login)
    {
        $query = "SELECT * from usr_data WHERE login = " . $this->quote($login, 'text');
        $result = $this->query($query);
        if ($data = $this->fetchAssoc($result)) {
            $data["passwd_type"] = IL_PASSWD_CRYPTED;
            $user = new ilObjUser();
            $user->assignData($data);
            // prevent user object from misinterpretation as local user and from manipulation
            $user->setId(- $user->getId());
            return $user;
        }
        return null;
    }
}
