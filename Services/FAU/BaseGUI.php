<?php

namespace FAU;

use ILIAS\DI\Container;
use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;
use Psr\Http\Message\RequestInterface;
use ilCtrl;
use ilTabsGUI;
use ilGlobalTemplateInterface;
use ilLanguage;
use ilToolbarGUI;
use ilErrorHandling;
use ilAccessHandler;

abstract class BaseGUI
{
    protected Container $dic;
    protected ilAccessHandler $access;
    protected ilCtrl $ctrl;
    protected ilTabsGUI $tabs;
    protected ilLanguage $lng;
    /** @var ilGlobalTemplateInterface $tpl  */
    protected $tpl;
    protected ilToolbarGUI $toolbar;
    protected Factory $factory;
    protected Renderer $renderer;
    protected RequestInterface $request;
    protected \ILIAS\Refinery\Factory $refinery;


    public function __construct()
    {
        global $DIC;

        $this->dic = $DIC;
        $this->access = $DIC->access();
        $this->ctrl = $this->dic->ctrl();
        $this->tabs = $this->dic->tabs();
        $this->toolbar = $this->dic->toolbar();
        $this->lng = $this->dic->language();
        $this->tpl = $this->dic->ui()->mainTemplate();
        $this->factory = $this->dic->ui()->factory();
        $this->renderer = $this->dic->ui()->renderer();
        $this->request = $this->dic->http()->request();
        $this->refinery = $this->dic->refinery();
    }

    /**
     * Raise an error that should be shown to the user
     */
    protected function raiseError($message)
    {
        /** @var ilErrorHandling $ilErr */
        $ilErr = $this->dic['ilErr'];
        $ilErr->raiseError($message, $ilErr->MESSAGE);
    }

}