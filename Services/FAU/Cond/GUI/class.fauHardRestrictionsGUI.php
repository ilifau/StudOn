<?php

use FAU\BaseGUI;
use FAU\Study\Data\ImportId;
use FAU\Cond\Service;
use FAU\Study\Data\Event;

/**
 * GUI for the display of hard restrictions and check results
 * @ilCtrl_Calls fauHardRestrictionsGUI:
 */
class fauHardRestrictionsGUI extends BaseGUI
{
    protected Service $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = $this->dic->fau()->cond();
    }


    /**
     * Get an instance of the class
     * @return static
     */
    public static function getInstance() : self
    {
        static $instance = null;
        if (!isset($instance)) {
            $instance = new self();
        }
        return $instance;
    }

    /**
     * Execute a command
     */
    public function executeCommand()
    {
        $this->tpl->loadStandardTemplate();

        $cmd = $this->ctrl->getCmd('show');
        $next_class = $this->ctrl->getNextClass();

        switch ($next_class) {
            default:
                switch ($cmd)
                {
                    case 'showRestrictionsModal':
                        $this->$cmd();
                        break;

                    default:
                        $this->tpl->setContent('unknown command: ' . $cmd);
                }
        }

        $this->tpl->printToStdout();
    }

    /**
     * Get the link for a modal to show all restrictions of an event 
     * The restrictions will be checked for the current user
     */
    public function getRestrictionsModalLink(ImportId $import_id, int $ref_id) : string
    {
        $this->ctrl->setParameter($this, 'ref_id', $ref_id);
        $this->ctrl->setParameter($this, 'import_id', $import_id->toString());
        $this->ctrl->setParameter($this, 'user_id', $this->dic->user()->getId());

        $modal = $this->factory->modal()->roundtrip('', $this->factory->legacy(''))
            ->withAsyncRenderUrl($this->ctrl->getLinkTarget($this, 'showRestrictionsModal'));

        $button = $this->factory->button()->shy('» ' . $this->lng->txt('fau_rest_hard_restrictions'), '#')
            ->withOnClick($modal->getShowSignal());

        return $this->renderer->render([$modal, $button]);
    }

    /**
     * Get the link for a modal to show all restrictions of an event
     * The link will already show the check result for a user
     * 
     * @param \FAU\Cond\HardRestrictions $restrictions checked restrictions
     * @param int|null    $selected_module_id ID of the selected module by the user
     * @param string|null $link_label specific link label to be used for link
     * @return string
     */
    public function getResultModalLink(
        \FAU\Cond\HardRestrictions $restrictions,
        ?int $selected_module_id = null,
        ?string $link_label = null
    ) : string
    {
        $this->ctrl->saveParameter($this, 'ref_id');
        $this->ctrl->setParameter($this, 'import_id', $restrictions->getCheckedImportId()->toString());
        $this->ctrl->setParameter($this, 'user_id', $restrictions->getCheckedUserId());
        $this->ctrl->setParameter($this, 'module_id', $selected_module_id);

        if (!isset($link_label)) {
            $link_label = '» ' .$this->lng->txt($restrictions->getCheckPassed() ?
                    'fau_check_info_passed_restrictions' : 'fau_check_info_failed_restrictions');
        }

        $modal = $this->factory->modal()->roundtrip('', $this->factory->legacy(''))
            ->withAsyncRenderUrl($this->ctrl->getLinkTarget($this, 'showRestrictionsModal'));

        $button = $this->factory->button()->shy($link_label, '#')
            ->withOnClick($modal->getShowSignal());

        return $this->renderer->render([$modal, $button]);
    }
    

    /**
     * Get an async modal with content to show the result of a restrictions check
     */
    protected function showRestrictionsModal()
    {
        $params = $this->request->getQueryParams();
        $ref_id = isset($params['ref_id']) ? (int) $params['ref_id'] : 0;
        $import_id = ImportId::fromString((string) $params['import_id'] ?? '');
        $user_id = isset($params['user_id']) ? (int) $params['user_id'] : 0;
        $selected_module_id = isset($params['module_id']) ? (int) $params['module_id'] : 0;
        
        // check if modal can be shown for other users (data protection)
        if ($user_id != $this->dic->user()->getId()) {
            
            switch (ilObject::_lookupType($ref_id, true)) {
                case 'crs':
                case 'grp':
                    if (!$this->dic->access()->checkAccess('manage_members', '', $ref_id)) {
                        exit;
                    }
                    if (!ilParticipants::getInstance($ref_id)->isAssigned($user_id)) {
                        exit;
                    }
                    break;
                    
                case 'xcos':
                    if (!$this->dic->access()->checkAccess('write', '', $ref_id)) {
                        exit;
                    }
                    if (empty(ilCoSubUser::_getById(ilObject::_lookupObjId($ref_id), $user_id))) {
                        exit;
                    }
            }
        }

        // get the selelcted module if not provided
        if (empty($selected_module_id)) {
            $selected_module_id = (int) $this->dic->fau()->user()->getSelectedModuleId(ilObject::_lookupObjId($ref_id), $user_id);
        }
        
        $restrictions = $this->dic->fau()->cond()->hard();
        $restrictions->checkByImportId($import_id, $user_id);
        $module_info = '';
        if (!empty($selected_module_id)) {
            foreach ($this->dic->fau()->study()->repo()->getModules([$selected_module_id]) as $module) {
                $module_info = $this->lng->txt('fau_selected_module') . ': ' .
                    $this->dic->fau()->tools()->format()->list([$module->getLabel()]);
            }
        }

        if (!empty($selected_module_id)) {
            $filter = 'selected';
        } elseif($restrictions->getCheckPassed()) {
            $filter = 'passed';
        } elseif (!empty($restrictions->getCheckedFittingModules())) {
            $filter = 'fitting';
        } else {
            $filter = 'all';
        }

        $parts = [$this->factory->legacy('<p>' . $restrictions->getCheckMessage() . '</p>')];
        if (!empty($restrictions->getCheckedUserCosTexts())) {
            $parts[] = $this->factory->legacy('<p>' . $this->lng->txt('fau_your_courses_of_study') .
                ' ('. $restrictions->getCheckedTermTitle(). '):<br>'. $restrictions->getCheckedUserCosTexts() . '</p>');
            $parts[] = $this->factory->legacy('<p>' . $module_info . '</p>');
            $parts[] = $this->factory->panel()->standard($this->lng->txt('fau_rest_hard_restrictions'),
                $this->factory->legacy($this->getCheckedRestrictionsHTML(
                    $restrictions->getCheckedRestrictionTexts(true, $selected_module_id), $filter)));
        }
        else {
            $parts[] = $this->factory->panel()->standard($this->lng->txt('fau_rest_hard_restrictions'),
                $this->factory->listing()->unordered($this->service->hard()->getEventRestrictionTexts($import_id->getEventId())));
        }

        $modal = $this->factory->modal()->roundtrip(
            sprintf($this->lng->txt('fau_check_info_restrictions_for'), ilObjUser::_lookupFullname((int) $restrictions->getCheckedUserId())),
            $parts
        )->withCancelButtonLabel('close');

        echo $this->renderer->render($modal);
        exit;
    }


    /**
     * Get the list of checked restrictions with a filter for modules
     * @param \FAU\Cond\Data\RestrictionText[] $restrictionsTexts
     * @param string $filter
     */
    protected function getCheckedRestrictionsHTML(array $restrictionsTexts, $filter='fitting'): string
    {
        $tpl = new ilTemplate("tpl.filtered_restrictions.html", true, true, "Services/FAU/Cond/GUI");

        foreach ($restrictionsTexts as $text) {
            $tpl->setCurrentBlock('item');
            $tpl->setVariable('CONTENT', $text->getContent());
            $tpl->setVariable('MODULE', $text->isModule() ? 'module' : '');
            $tpl->setVariable('FITTING', $text->isFitting() ? 'fitting' : '');
            $tpl->setVariable('PASSED', $text->isPassed() ? 'passed' : '');
            $tpl->setVariable('SELECTED', $text->isSelected() ? 'selected' : '');
            $tpl->parseCurrentBlock();
        }

        $container_id = str_replace('-', '', (new ILIAS\Data\UUID\Factory)->uuid4AsString());
        $call = "il.FAU.hardRestrictions.toggleModules('$container_id', '%s'); return false;";
        $init = "il.FAU.hardRestrictions.toggleModules('$container_id', '%s');";

        $tpl->setVariable('SOURCE', './Services/FAU/Cond/GUI/templates/js/hard_restrictions.js');
        $tpl->setVariable('INIT', sprintf($init, $filter));
        $tpl->setVariable('CONTAINER_ID', $container_id);
        $tpl->setVariable('ARIA_LABEL', $this->lng->txt('fau_filter_restrictions'));
        $tpl->setVariable('FILTER', $this->lng->txt('fau_filter_restrictions'));
        $tpl->setVariable('RESTRICTIONS', $this->lng->txt('fau_rest_hard_restrictions'));


        $tpl->setVariable('TXT_ALL', $this->lng->txt('fau_filter_restrictions_all'));
        $tpl->setVariable('TXT_FITTING', $this->lng->txt('fau_filter_restrictions_fitting'));
        $tpl->setVariable('TXT_PASSED', $this->lng->txt('fau_filter_restrictions_passed'));
        $tpl->setVariable('TXT_SELECTED', $this->lng->txt('fau_filter_restrictions_selected'));

        $tpl->setVariable('CLICK_ALL', sprintf($call, 'all'));
        $tpl->setVariable('CLICK_FITTING', sprintf($call, 'fitting'));
        $tpl->setVariable('CLICK_PASSED', sprintf($call, 'passed'));
        $tpl->setVariable('CLICK_SELECTED', sprintf($call, 'selected'));

        return $tpl->get();
    }
}