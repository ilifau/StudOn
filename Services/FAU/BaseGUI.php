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
use ilTemplate;

abstract class BaseGUI
{
    protected Container $dic;
    protected ilCtrl $ctrl;
    protected ilTabsGUI $tabs;
    /** @var ilGlobalTemplateInterface $tpl  */
    protected $tpl;
    protected ilLanguage $lng;
    protected ilToolbarGUI $toolbar;
    protected Factory $factory;
    protected Renderer $renderer;
    protected RequestInterface $request;
    protected \ILIAS\Refinery\Factory $refinery;


    public function __construct()
    {
        global $DIC;

        $this->dic = $DIC;
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

}