<?php

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\UI\Component\Input\Container\Filter\Standard;

/**
 * Membership overview
 *
 * @ilCtrl_Calls ilMembershipOverviewGUI: ilPDMembershipBlockGUI
 *
 * @author killing@leifos.de
 */
class ilMembershipOverviewGUI
{
    /**
     * @var \ilCtrl
     */
    protected $ctrl;

    /**
     * @var \ilLanguage
     */
    protected $lng;


    /**
     * @var \ilTemplate
     */
    protected $main_tpl;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $DIC;

        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();
        $this->main_tpl = $DIC->ui()->mainTemplate();
    }

    /**
     * Execute command
     */
    public function executeCommand()
    {
        $ctrl = $this->ctrl;

        $next_class = $ctrl->getNextClass($this);
        $cmd = $ctrl->getCmd("show");

        switch ($next_class) {
            case "ilpdmembershipblockgui":
                $ctrl->setReturn($this, "show");
                $block = new ilPDMembershipBlockGUI(true);
                $ret = $this->ctrl->forwardCommand($block);
                if ($ret != "") {
                    //$this->displayHeader();
                    $this->main_tpl->setContent($ret);
                    //$this->tpl->printToStdout();
                }
                break;

            default:
                // fau: filterMyMem - add allowed command
                if (in_array($cmd, array("show", "applyFilter"))) {
                    $this->$cmd();
                }
                // fau.
        }
        $this->main_tpl->printToStdout();
    }

    /**
     * Show
     */
    protected function show()
    {
        $main_tpl = $this->main_tpl;
        $lng = $this->lng;

        $main_tpl->setTitle($lng->txt("my_courses_groups"));

        $block = new ilPDMembershipBlockGUI(true);

        // fau: filterMyMem - add filter to page
        global $DIC;
        $renderer = $DIC->ui()->renderer();
        $main_tpl->setContent($renderer->render($this->getFilter()) . $block->getHTML());
        // fau.
    }

    // fau: filterMyMem - get the filter control
    protected function getFilter() : Standard
    {
        global $DIC;
        $select = $DIC->ui()->factory()->input()->field()->select($this->lng->txt('studydata_semester'), $DIC->fau()->study()->getTermSearchOptions())
        ->withValue($DIC->fau()->tools()->preferences()->getTermIdForMyMemberships());
        $action = $DIC->ctrl()->getLinkTarget($this, "applyFilter", "", true);
        return $DIC->uiService()->filter()->standard("fauFilterMyMem", $action, ["term_id" => $select], [true], true, true);
    }
    // fau.

    // fau: filterMyMem - apply the filter
    protected function applyFilter()
    {
        global $DIC;
        $filter_data = $DIC->uiService()->filter()->getData($this->getFilter());
        $DIC->fau()->tools()->preferences()->setTermIdForMyMemberships($filter_data['term_id']);
        $this->ctrl->redirect($this, 'show');
    }
    // fau.
}
