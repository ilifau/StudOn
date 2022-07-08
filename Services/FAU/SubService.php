<?php

namespace FAU;

use ILIAS\DI\Container;
use ilLanguage;

abstract class SubService
{
    protected Container $dic;
    protected Settings $settings;
    protected ilLanguage $lng;

    /**
     * Constructor
     */
    public function __construct(Container $dic)
    {
        $this->dic = $dic;
        $this->lng = $dic->language();
        $this->settings = $dic->fau()->settings();
    }
}