<?php

use FAU\BaseGUI;
use FAU\Study\Repository;

/**
 * GUI for the display of course related data
 * @ilCtrl_Calls fauStudyInfoGUI:
 */
class fauStudyInfoGUI extends BaseGUI
{
    protected \FAU\Study\Service $service;
    protected Repository $repo;


    public function __construct()
    {
        parent::__construct();
        $this->service = $this->dic->fau()->study();
        $this->repo = $this->dic->fau()->study()->repo();
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
                    case 'showDatesModal':
                        $this->$cmd();
                        break;

                    default:
                        $this->tpl->setContent('unknown command: ' . $cmd);
                }
        }

        $this->tpl->printToStdout();
    }



    /**
     * Show a linked planned dates line for a course or a group
     * @return string   html code of the linked info
     */
    public function getLinkedDatesInfo(?int $course_id, ?int $obj_id) : string
    {
        if (empty($course_id)) {
            $importId = $this->repo->getImportId((int) $obj_id);
            $course_id = (int) $importId->getCourseId();
        }
        if (empty($course_id)) {
            return '';
        }
        if (empty($text = $this->service->dates()->getPlannedDatesList($course_id, false))) {
            return '';
        }

        $text = ilUtil::shortenText($text, 100, true);


        $this->ctrl->setParameter($this, 'course_id', $course_id);
        $modal = $this->factory->modal()->roundtrip('', $this->factory->legacy((string) $course_id))
            ->withAsyncRenderUrl($this->ctrl->getLinkTarget($this, 'showDatesModal'))
            ->withResetSignals();
        $button = $this->factory->button()->shy('» ' . $text, '#')
            ->withResetTriggeredSignals()
             ->withOnClick($modal->getShowSignal());

        return $this->renderer->render([$modal, $button]);
    }

    /**
     * Get an async modal with content to show restrictions
     */
    protected function showDatesModal()
    {
        $params = $this->request->getQueryParams();
        $course_id = isset($params['course_id']) ? (int) $params['course_id'] : null;
        $course = $this->repo->getCourse($course_id);

        $title = $this->lng->txt('fau_dates_planned');
        $content = $this->lng->txt('fau_dates_missing');

        if (!empty($course)) {
            if (!empty($course->getIliasObjId())) {
                $title = sprintf($this->lng->txt('fau_dates_planned_for'), ilObject::_lookupTitle($course->getIliasObjId()));
            }
            else {
                $title = $course->getTitle();
            }
            $plan_dates = $this->service->dates()->getPlannedDatesList($course->getCourseId(), true);
            if (!empty($plan_dates)) {
                $content = $this->lng->txt('fau_dates_planned') . $plan_dates;
            }
            $indiv_dates = $this->service->dates()->getIndividualDatesList($course->getCourseId(), true);
            if (!empty($indiv_dates)) {
                $content .= $this->lng->txt('fau_dates_indiv') . $indiv_dates;
            }
        }

        $modal = $this->factory->modal()->roundtrip($title, $this->factory->legacy($content))
            ->withCancelButtonLabel('close');
        echo $this->renderer->render($modal);
        exit;
    }
}