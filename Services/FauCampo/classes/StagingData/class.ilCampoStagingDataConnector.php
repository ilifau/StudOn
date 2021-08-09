<?php

/**
 * Class ilCampoStagingDataConnector
 */
class ilCampoStagingDataConnector extends arConnectorDB
{
    /**
     * @return ilDBInterface
     */
    protected function returnDB()
    {
        require_once('./Services/Idm/classes/class.ilDBIdm.php');
        return ilDBIdm::getInstance();
    }
}