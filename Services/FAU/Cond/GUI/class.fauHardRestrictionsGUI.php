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
                    case 'getRestrictionsModalContent':
                        $this->$cmd();
                        break;

                    default:
                        $this->tpl->setContent('unknown command: ' . $cmd);
                }
        }

        $this->tpl->printToStdout();
    }


    /**
     * Get a result string that is linked with a modal to show details
     * @param bool        $passed    Restrictions are passed (will determine the text that is directly shown)
     * @param string      $info      The detailed info if restrictions are failed (linked in the modal)
     * @param string      $username  Full name of the user (will be shown in the modal title)
     * @param int|null    $module_id ID of the selected modal by the user
     * @param string|null $passed_label    label to be used for passed restrictions
     * @param string|null $failed_label    label to be used for failed restrictions
     * @return string
     */
    public function getResultWithModalHtml(bool $passed, string $info, string $username, ?int $module_id,
        ?string $passed_label = null, ?string $failed_label = null) : string
    {
        $passed_label = $passed_label ?? $this->lng->txt('fau_check_info_passed_restrictions');
        $failed_label = $failed_label ??  $this->lng->txt('fau_check_info_failed_restrictions');

        // no detailed info if restrictions are passed
        if ($passed) {
            return $passed_label;
        }

        $module_info = '';
        if (!empty($module_id)) {
            foreach ($this->dic->fau()->study()->repo()->getModules([$module_id]) as $module) {
                $module_info = '<p>' . $this->lng->txt('fau_selected_module') . ': '
                    . $module->getModuleName() . ' (' . $module->getModuleNr() . ')</p>';
            }
        }

        $modal_id = rand(1000000,9999999);
        $modal = ilModalGUI::getInstance();
        $modal->setId($modal_id);
        $modal->setType(ilModalGUI::TYPE_LARGE);
        $modal->setBody($module_info . $info);
        $modal->setHeading(sprintf($this->lng->txt('fau_check_info_restrictions_for'), $username));

        $onclick = "$('#$modal_id').modal('show')";
        $link = '<a onclick="' . $onclick . '">» ' . $failed_label . '</a>';
        return $modal->getHTML() . $link;
    }

    /**
     * Get the link for a modal to show restrictions of an event
     * @param int    $event_id
     * @param string $term_id       e.g. '20222'
     * @param bool    $with_check    true, if restrictions should be checked for the current user
     */
    public function getRestrictionsModalLink(int $event_id, string $term_id, bool $with_check = false)
    {
        $this->ctrl->setParameter($this, 'event_id', $event_id);
        $this->ctrl->setParameter($this, 'term_id', $term_id);
        $this->ctrl->setParameter($this, 'with_check', $with_check);

        $modal = $this->factory->modal()->roundtrip('', $this->factory->legacy(''))
                         ->withAsyncRenderUrl($this->ctrl->getLinkTarget($this));

        $button = $this->factory->button()->shy('» ' . $this->lng->txt('fau_rest_hard_restrictions'), '#')
                          ->withOnClick($modal->getShowSignal());

        echo $this->renderer->render([$modal, $button]);
        exit;
    }

    /**
     * Get an async modal with content to show restrictions
     */
    protected function getRestrictionsModalContent()
    {
        $params = $this->request->getQueryParams();
        $event_id = isset($params['event_id']) ? (int) $params['event_id'] : null;
        $term_id = isset($params['term_id']) ? (string) $params['term_id'] : null;
        $with_check = isset($params['with_check']) && (bool) $params['with_check'];

        $import_id = new ImportId($term_id, $event_id);
        $hardRestrictions = $this->service->hard();

        if ($with_check) {
            $hardRestrictions->checkByImportId($import_id, $this->dic->user()->getId());

            $title = sprintf($this->lng->txt('fau_check_info_restrictions_for'), $this->dic->user()->getFullname());
            $content = $this->factory->legacy($hardRestrictions->getCheckResultInfo());
        }
        else {
            $event = $this->dic->fau()->study()->repo()->getEvent($event_id, Event::model());
            $title = sprintf($this->lng->txt('fau_check_info_restrictions_for'), $event->getTitle());
            $content = $this->factory->legacy($this->service->hard()->getEventRestrictionTexts($event_id));
        }

        $modal = $this->factory->modal()->roundtrip($title, $content);
        echo $this->renderer->render($modal);
        exit;
    }
}