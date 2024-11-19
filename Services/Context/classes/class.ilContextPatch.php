<?php
/**
 * fau: customPatches - new context for patches.
 * Allow HTTP and HTML and Templates, which may be needed for deleting objects
 */

include_once "Services/Context/interfaces/interface.ilContextTemplate.php";

/**
 * Service context for patches
 *
 * @ingroup ServicesContext
 */
class ilContextPatch implements ilContextTemplate
{
    /**
     * Are redirects supported?
     *
     * @return bool
     */
    public static function supportsRedirects(): bool
    {
        return false;
    }
    
    /**
     * Based on user authentication?
     *
     * @return bool
     */
    public static function hasUser(): bool
    {
        return true;
    }
    
    /**
     * Uses HTTP aka browser
     *
     * @return bool
     */
    public static function usesHTTP(): bool
    {
        return false;
    }
    
    /**
     * Has HTML output
     *
     * @return bool
     */
    public static function hasHTML(): bool
    {
        return true;
    }
    
    /**
     * Uses template engine
     *
     * @return bool
     */
    public static function usesTemplate(): bool
    {
        return true;
    }
    
    /**
     * Init client
     *
     * @return bool
     */
    public static function initClient(): bool
    {
        return true;
    }
    
    /**
     * Try authentication
     *
     * @return bool
     */
    public static function doAuthentication(): bool
    {
        return true;
    }

    /**
     * Check if persistent session handling is supported
     * @return boolean
     */
    public static function supportsPersistentSessions(): bool
    {
        return false;
    }

    /**
     * Supports push messages
     *
     * @return bool
     */
    public static function supportsPushMessages(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function isSessionMainContext(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function modifyHttpPath(string $httpPath) : string
    {
        return $httpPath;
    }
}
