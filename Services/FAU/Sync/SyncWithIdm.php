<?php

namespace FAU\Sync;


use ILIAS\DI\Container;

/**
 * Synchronisation of data coming from IDM
 * This will update data of the User service
 */
class SyncWithIdm extends SyncBase
{

    /**
     * Synchronize data (called by cron job)
     * Counted items are the persons
     */
    public function synchronize() : void
    {

    }
}

