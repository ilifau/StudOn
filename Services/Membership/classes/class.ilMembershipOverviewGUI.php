<?php

// fau: filterMyMem - use Filter\Standard
use ILIAS\UI\Implementation\Component\Input\Container\Filter\Standard;
// fau.
/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

/**
 * Membership overview
 * @ilCtrl_Calls ilMembershipOverviewGUI: ilMembershipBlockGUI
 * @author       killing@leifos.de
 */
class ilMembershipOverviewGUI implements ilCtrlBaseClassInterface
{
    protected ilCtrlInterface $ctrl;
    protected ilLanguage $lng;
    protected ilGlobalTemplateInterface $main_tpl;

    public function __construct()
    {
        global $DIC;

        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();
        $this->main_tpl = $DIC->ui()->mainTemplate();
    }

    public function executeCommand(): void
    {
        $ctrl = $this->ctrl;
        $next_class = $ctrl->getNextClass($this);
        $cmd = $ctrl->getCmd("show");
        $this->main_tpl->setTitleIcon(ilUtil::getImagePath('icon_crgr.svg'));
        $this->main_tpl->setTitle($this->lng->txt("my_courses_groups"));
        switch ($next_class) {
            case strtolower(ilMembershipBlockGUI::class):
                $ctrl->setReturn($this, "show");
                $block = new ilMembershipBlockGUI();
                $ret = $this->ctrl->forwardCommand($block);
                if ($ret != "") {
                    $this->main_tpl->setContent($ret);
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

    protected function show(): void
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
        if (!isset($filter_data['term_id']))
            $filter_data['term_id'] = null;
        $DIC->fau()->tools()->preferences()->setTermIdForMyMemberships($filter_data['term_id']);
        $this->ctrl->redirect($this, 'show');
    }
    // fau.    
}
