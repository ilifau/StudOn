<?php declare(strict_types=1);

namespace FAU\Sync;

use ILIAS\DI\Container;
use FAU\User\Migration;

/**
 * Service for user related data
 */
class Service
{
    protected Container $dic;


    /**
     * Constructor
     */
    public function __construct(Container $dic)
    {
        $this->dic = $dic;
    }

    /**
     * Get the Migration Handler
     */
    public function campo() : SyncWithCampo
    {
        return new SyncWithCampo($this->dic);
    }


}