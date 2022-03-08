<?php


/* Copyright (c) 2019 Richard Klees <richard.klees@concepts-and-training.de>, Fabian Schmid <fs@studer-raimann.ch> Extended GPL, see docs/LICENSE */

namespace ILIAS\Setup\Objective;

use ILIAS\Setup;

/**
 * Read the client id of the installation from the data directory.
 *
 * ATTENTION: This might be placed better in some service, rather then being located
 * here in the Setup-library. Currently I don't know where, though. Maybe we also
 * might be able to remove this altogether if the multi-client code has been removed.
 */
class ClientIdReadObjective implements Setup\Objective
{
    /**
     * Uses hashed Path.
     *
     * @inheritdocs
     */
    public function getHash() : string
    {
        return hash("sha256", self::class);
    }

    /**
     * @inheritdocs
     */
    public function getLabel() : string
    {
        return "Read client-id from data-directory.";
    }

    /**
     * Defaults to 'true'.
     *
     * @inheritdocs
     */
    public function isNotable() : bool
    {
        return false;
    }

    /**
     * @inheritdocs
     */
    public function getPreconditions(Setup\Environment $environment) : array
    {
        return [];
    }

    /**
     * @inheritdocs
     */
    public function achieve(Setup\Environment $environment) : Setup\Environment
    {
        // fau: clientByUrl - always take the client id from the setup config
        $common_config = $environment->getConfigFor("common");
        $client_id = $common_config->getClientId();

        $client_dir = $this->getDataDirectoryPath() . $client_id;
        if ($this->isDirectory($client_dir)) {
            throw new Setup\UnachievableException(
                "The client directory '$client_dir' does not exist. " .
                "Probably ILIAS is not installed."
            );
        }

        return $environment->withResource(Setup\Environment::RESOURCE_CLIENT_ID, $client_id);
        // fau.

    }

    protected function getDataDirectoryPath() : string
    {
        return dirname(__DIR__, 3) . "/data";
    }

    protected function scanDirectory(string $path) : array
    {
        return scandir($path);
    }

    protected function isDirectory(string $path) : bool
    {
        return is_dir($path);
    }
 
    /**
     * @inheritDoc
     */
    public function isApplicable(Setup\Environment $environment) : bool
    {
        return $environment->getResource(Setup\Environment::RESOURCE_CLIENT_ID) === null;
    }
}
