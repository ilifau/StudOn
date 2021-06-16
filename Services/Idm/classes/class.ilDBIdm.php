<?php
/* fau: idmData - new class for connection to idm database. */

/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* MySQL Wrapper for a connection to the idm database
*
* This class extends the main ILIAS database wrapper ilDB.
*/
class ilDBIdm extends ilDBPdoMySQLInnoDB
{

    /** @var  ilDBIdm $instance */
    private static $instance;


    /**
     * Get the idm database connection instance
     * @return ilDBIdm | null
     */
    public static function getInstance()
    {
        global $DIC;

        // read the client settings if available
        if (isset($DIC) && $DIC->offsetExists('ilClientIniFile'))
        {
            /** @var ilIniFile $ilClientIniFile */
            $ilClientIniFile = $DIC['ilClientIniFile'];
            $settings = $ilClientIniFile->readGroup('db_idm');
        }

        if (!isset(self::$instance))
        {
            try {
                $instance = new self;
                $instance->setDBHost($settings['host']);
                $instance->setDBPort($settings['port']);
                $instance->setDBUser($settings['user']);
                $instance->setDBPassword($settings['pass']);
                $instance->setDBName($settings['name']);
                if (!$instance->connect()) {
                    $DIC->logger()->root()->warning("can't connect to idm database");
                    return null;
                }

                // test connection
                $instance->query('SELECT count(*) from identities');
            }
            catch (Exception $e) {
                $DIC->logger()->root()->warning($e->getMessage());
                return null;
            }

            self::$instance = $instance;
        }

        return self::$instance;
    }
}
