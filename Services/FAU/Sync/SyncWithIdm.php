<?php

namespace FAU\Sync;


use ILIAS\DI\Container;

/**
 * Synchronisation of data coming from IDM
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

