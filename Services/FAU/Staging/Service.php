<?php

namespace FAU\Staging;

use ILIAS\DI\Container;

/**
 * Service for data in the staging database
 */
class Service
{
    protected Container $dic;
    protected Repository $repository;
    protected \ilDBInterface $database;

    /**
     * Constructor
     */
    public function __construct(Container $dic)
    {
        $this->dic = $dic;
    }


    /**
     * Get the repository for staging data
     * return null if the database can't be connected
     */
    public function repo() : ?Repository
    {
        if(!isset($this->repository)) {
            $this->repository = new Repository($this->database(), $this->dic->logger()->fau());
        }
        return $this->repository;
    }

    /**
     * Get the database object for the staging database
     * @return ?\ilDBInterface
     */
    public function database() : ?\ilDBInterface
    {
        if (!isset($this->database)) {
            try {
                $settings = $this->dic->clientIni()->readGroup('db_idm');
                $db = new \ilDBPdoMySQLInnoDB();
                $db->setDBHost($settings['host']);
                $db->setDBPort($settings['port']);
                $db->setDBUser($settings['user']);
                $db->setDBPassword($settings['pass']);
                $db->setDBName($settings['name']);
                if (!$db->connect()) {
                    $this->dic->logger()->root()->warning("can't connect to idm database");
                    return null;
                }
            }
            catch (\Exception $e) {
                $this->dic->logger()->root()->warning($e->getMessage());
                return null;
            }
            $this->database = $db;
        }
        return $this->database;
    }
}