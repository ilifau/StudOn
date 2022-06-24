<?php declare(strict_types=1);

namespace FAU\Sync;

use ILIAS\DI\Container;
/**
 * Service for synchronizing data between staging database and studon
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

    public function campo() : SyncWithCampo
    {
        return new SyncWithCampo($this->dic);
    }

    public function org() : SyncWithOrg
    {
        return new SyncWithOrg($this->dic);
    }

    public function idm() : SyncWithIdm
    {
        return new SyncWithIdm($this->dic);
    }

}