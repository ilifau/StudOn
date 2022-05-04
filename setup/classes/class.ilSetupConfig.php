<?php

/* Copyright (c) 2019 Richard Klees <richard.klees@concepts-and-training.de> Extended GPL, see docs/LICENSE */

use ILIAS\Setup;
use ILIAS\Data\Password;

class ilSetupConfig implements Setup\Config
{
    /**
     * @var	\ILIAS\Data\ClientId
     */
    protected $client_id;

    /**
     * @var \DateTimeZone
     */
    protected $server_timezone;

    // fau: absolutePath - class variable
    protected $absolute_path;
    // fau.

    /**
     * @var	bool
     */
    protected $register_nic;

    public function __construct(
        \ILIAS\Data\ClientId $client_id,
        \DateTimeZone $server_timezone,
        bool $register_nic
    ) {
        $this->client_id = $client_id;
        $this->server_timezone = $server_timezone;
        $this->register_nic = $register_nic;

        // fau: absolutePath - initialize default value
        $this->absolute_path = dirname(__DIR__, 2);
        // fau.
    }

    public function getClientId() : string
    {
        return $this->client_id->toString();
    }

    public function getServerTimeZone() : \DateTimeZone
    {
        return $this->server_timezone;
    }

    public function getRegisterNIC() : bool
    {
        return $this->register_nic;
    }

    // fau: absolutePath - mutation and getter
    /**
     * Optionally set a new absolute path
     * This is needed if setup runs on file server
     * but mounted nfs on webservers has a different path and nfs on file server is just a symboliclink
     * @param string $path
     * @return ilSetupConfig
     */
    public function withAbsolutePath(string $path)
    {
        // cut a trailing slash
        if (substr($path, 0, -1) == '/') {
            $path = substr($path, 0, strlen($path) -1);
        }

        // ensure absolute path
        if(substr($path, 0, 1) != '/') {
            throw new \InvalidArgumentException(
                "absolute_path $path must start with a /"
            );
        }

        // ensure uniqueness with the installation directory
        if (realpath($path) != realpath($this->absolute_path)) {
            throw new \InvalidArgumentException(
                "absolute_path $path must point to the installation directory"
            );
        }

        $clone = clone $this;
        $clone->absolute_path = $path;
        return $clone;
    }

    public function getAbsolutePath(): string
    {
        return $this->absolute_path;
    }
    // fau.
}
