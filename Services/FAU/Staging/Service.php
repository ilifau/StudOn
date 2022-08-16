<?php

namespace FAU\Staging;

use ILIAS\DI\Container;
use FAU\SubService;

/**
 * Service for data in the staging database
 */
class Service extends SubService
{
    protected Repository $repository;
    protected \ilDBInterface $database;


    /**
     * Get the repository for staging data
     * return null if the database can't be connected
     */
    public function repo() : ?Repository
    {
        if(!isset($this->repository)) {
            $this->repository = new Repository($this->database(), $this->dic->logger()->fau());
        }
        if (isset($this->repository)) {
            $this->repository->enableDipQueryStatus($this->settings()->getDipQueryStatus());
            $this->repository->enableDipSetProcessed($this->settings()->getDipSetProcessed());
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