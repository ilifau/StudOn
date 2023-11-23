<?php

namespace FAU;

use ILIAS\DI\Container;
use ilLanguage;
use FAU\Tools\Settings;
use FAU\Tools\Preferences;

abstract class SubService
{
    protected Container $dic;
    protected Settings $settings;
    protected Preferences $preferences;
    protected ilLanguage $lng;

    /**
     * Constructor
     */
    public function __construct(Container $dic)
    {
        $this->dic = $dic;
        $this->lng = $dic->language();
    }

    /**
     * Get the system settings for the FAU service
     */
    public function settings() : Settings
    {
        if (!isset($this->settings)) {
            $this->settings = new Settings($this->dic);
        }
        return $this->settings;
    }


    /**
     * Get the user preferences for the FAU service
     */
    public function preferences() : Preferences
    {
        if (!isset($this->preferences)) {
            $this->preferences = new Preferences($this->dic);
        }
        return $this->preferences;
    }
}