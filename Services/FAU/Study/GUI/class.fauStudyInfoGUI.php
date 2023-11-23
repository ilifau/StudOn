<?php

use FAU\BaseGUI;
use FAU\Study\Repository;
use FAU\Study\Data\Term;
use FAU\Study\Data\ImportId;
use FAU\Study\Data\Event;
use FAU\Study\Data\Course;
use FAU\Study\Service;
use FAU\Ilias\Data\ContainerInfo;
use ILIAS\UI\Component\Button\Button;
use ILIAS\UI\Component\Modal\Modal;

/**
 * GUI for the display of course related data
 * @ilCtrl_Calls fauStudyInfoGUI:
 */
class fauStudyInfoGUI extends BaseGUI
{
    protected Service $service;
    protected Repository $repo;


    public function __construct()
    {
        parent::__construct();
        $this->service = $this->dic->fau()->study();
        $this->repo = $this->dic->fau()->study()->repo();
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
                    case 'showDetailsModal':
                        $this->$cmd();
                        break;

                    default:
                        $this->tpl->setContent('unknown command: ' . $cmd);
                }
        }

        $this->tpl->printToStdout();
    }

    /**
     * Get a line with planned dates for a course or a group
     * @return string   html code of the linked info
     */
    public function getDatesLine(ImportId $import_id) : string
    {
        $list = $this->service->dates()->getPlannedDatesList($import_id->getCourseId(), false);
        $text = implode(' | ', $list);
        return ilUtil::shortenText($text, 100, true);
    }

    /**
     * Show a line  with responsibles for a course or group
     * @return string   html code of the linked info
     */
    public function getResponsiblesLine(ImportId $import_id) : string
    {
        // don't show event responsibles
        $list = $this->service->persons()->getResponsiblesList(null, $import_id->getCourseId(), false);
        $text = implode(' | ', array_unique($list));
        return ilUtil::shortenText($text, 100, true);
    }


    /**
     * Get the links shown for an object or group
     */
    public function getLinksLine(ImportId $import_id, int $ref_id) : string
    {
        If ($import_id->isForCampo()) {
            $links = [];
            $links[] = $this->getDetailsLink($import_id, $ref_id, $title = $this->lng->txt('fau_details_link'));
            if ($this->dic->fau()->cond()->hard()->hasEventOrModuleRestrictions($import_id->getEventId())) {
                $links[] = fauHardRestrictionsGUI::getInstance()->getRestrictionsModalLink($import_id->getEventId());
            }
            $links[] = $this->getCampoLink($import_id, $title = $this->lng->txt('fau_campo_link'));

            $term = $this->service->getTermText(Term::fromString($import_id->getTermId()), true);
            return $term . ': ' . implode(' | ', $links);
        }
        return '';
    }

    /**
     * Get the link to show the details for an event or course
     */
    public function getDetailsLink(ImportId $import_id, int $ref_id, string $title)
    {
        $this->ctrl->setParameter($this, 'import_id', $import_id->toString());
        $this->ctrl->setParameter($this, 'ref_id', $ref_id);

        $modal = $this->factory->modal()->roundtrip('', $this->factory->legacy(''))
                               ->withAsyncRenderUrl($this->ctrl->getLinkTarget($this, 'showDetailsModal'))
                               ->withResetSignals();
        $button = $this->factory->button()->shy($title, '#')
                                ->withResetTriggeredSignals()
                                ->withOnClick($modal->getShowSignal());

        return $this->renderer->render([$modal, $button]);
    }

    /**
     * Get the link to show the details for an event or course
     */
    protected function getDetailsModal(ImportId $import_id, int $ref_id): Modal
    {
        $this->ctrl->setParameter($this, 'import_id', $import_id->toString());
        $this->ctrl->setParameter($this, 'ref_id', $ref_id);

        $modal = $this->factory->modal()->roundtrip('', $this->factory->legacy(''))
                               ->withAsyncRenderUrl($this->ctrl->getLinkTarget($this, 'showDetailsModal'))
                               ->withResetSignals();
        return $modal;
    }

    /**
     * Get the link to show the details for an event or course
     */
    protected function getDetailsButton(Modal $modal, string $title) : Button
    {
        $button = $this->factory->button()->shy($title, '#')
                                ->withResetTriggeredSignals()
                                ->withOnClick($modal->getShowSignal());
        return $button;
    }


    /**
     * Get the link to campo for an event and term
     */
    protected function getCampoLink(ImportId $import_id, string $title)
    {
        $term = Term::fromString($import_id->getTermId());
        $url = $this->service->getCampoUrl($import_id->getEventId(), $term);
        return $this->renderer->render($this->factory->link()->standard($title, $url)->withOpenInNewViewport(true));
    }

    /**
     * Show a modal with details for a campo course or event
     */
    protected function showDetailsModal()
    {
        $params = $this->request->getQueryParams();
        $import_id = ImportId::fromString($params['import_id']);
        $ref_id = (int) $params['ref_id'];

        $event = $this->repo->getEvent((int) $import_id->getEventId());
        $course = $this->repo->getCourse((int) $import_id->getCourseId());
        $term = Term::fromString((string) $import_id->getTermId());

        // modal title
        if (!empty($course)) {
            if (!empty($course->getIliasObjId())) {
               $title = ilObject::_lookupTitle($course->getIliasObjId());
            } else {
                $title = $course->getTitle();
            }
        } elseif (!empty($event)) {
            $title = $event->getTitle();
        }

        // modal content
        $panels = [];
        if (!empty($event)) {
            if (!empty($props = $this->getEventProperties($event, $term, $ref_id, empty($course), true))) {
                $panels[] = $this->factory->panel()->standard($this->lng->txt('fau_campo_event'),
                    $this->factory->listing()->descriptive($props));
            }
        }
        if (!empty($course)) {
            if (!empty($props = $this->getCourseProperties($course, $term, true))) {
                $panels[] = $this->factory->panel()->standard($this->lng->txt('fau_campo_course')
                    . ' (' . $this->service->getTermText($term) . ')',
                    $this->factory->listing()->descriptive($props));
            }
            if (!empty($props = $this->getDateProperties($course))) {
                $panels[] = $this->factory->panel()->standard($this->lng->txt('fau_dates'),
                    $this->factory->listing()->descriptive($props));
            }
        }

        $modal = $this->factory->modal()->roundtrip($title, $panels)->withCancelButtonLabel('close');
        echo $this->renderer->render($modal);
        exit;
    }

    /**
     * @param ilInfoScreenGUI $info
     * @param ImportId        $import_id
     * @param int             $ref_id
     * @return void
     */
    public function addInfoScreenSections(ilInfoScreenGUI $info, ImportId $import_id, int $ref_id)
    {
        $event = $this->repo->getEvent((int) $import_id->getEventId());
        $course = $this->repo->getCourse((int) $import_id->getCourseId());
        $term = Term::fromString((string) $import_id->getTermId());

        if (!empty($event)) {
            if (!empty($props = $this->getEventProperties($event, $term, $ref_id, empty($course), false))) {
                $info->addSection($this->lng->txt('fau_campo_event'));
                foreach ($props as $label => $content) {
                    $info->addProperty($label, $content);
                }
            }
        }
        if (!empty($course)) {
            if (!empty($props = $this->getCourseProperties($course, $term, false))) {
                $info->addSection($this->lng->txt('fau_campo_course') . ' (' . $this->service->getTermText($term) . ')');
                foreach ($props as $label => $content) {
                    $info->addProperty($label, $content);
                }
            }
            if (!empty($props = $this->getDateProperties($course))) {
                $info->addSection($this->lng->txt('fau_dates'));
                foreach ($props as $label => $content) {
                    $info->addProperty($label, $content);
                }
            }
        }
    }


    /**
     * Get the properties for the details view of an event
     * @return array title => html
     */
    protected function getEventProperties(Event $event, Term $term, int $ref_id, bool $with_groups, bool $in_modal) : array
    {
        $props = [];
        $import_id = new ImportId($term->toString(), $event->getEventId());

        $title = $event->getTitle();
        if ($term->isValid()) {
            $title = $this->renderer->render(
                $this->factory->link()->standard($title, $this->service->getCampoUrl($event->getEventId(), $term))
                    ->withOpenInNewViewport(true));
        }
        if (!empty($event->getShorttext())) {
            $title .= ' (' . $event->getShorttext() . ')';
        }
        $props[$this->lng->txt('title')] = $title;

        if (!empty($event->getEventtype())) {
            $props[$this->lng->txt('fau_campo_event_type')] = $event->getEventtype();
        }
        if (!empty($event->getComment())) {
            $props[$this->lng->txt('comment')]  = $event->getComment();
        }
        if (!empty($info = $this->getEventOrgunitsInfo($event))) {
            $props[$this->lng->txt('fau_campo_assigned_orgunits')] = $info;
        }
// Don't show event responsibles
//        if (!empty($list = $this->service->persons()->getResponsiblesList($event->getEventId(), null))) {
//            $props[$this->lng->txt('fau_campo_responsibles')] = $this->renderList($list);
//        }
        
        // take the common texts for all parallelgroups from the firts found group
        foreach ($this->dic->fau()->study()->repo()->getCoursesOfEventInTerm($event->getEventId(), $term) as $course) {
            if (!empty($course->getContentsAll())) {
                $props[$this->lng->txt('fau_campo_contents')]  = $this->renderCampoText($course->getContentsAll());
            }
            if (!empty($course->getLiteratureAll())) {
                $props[$this->lng->txt('fau_campo_literature')]  =  $this->renderCampoText($course->getLiteratureAll());
            }
            if (!empty($course->getRecommendedRequirementAll())) {
                $props[$this->lng->txt('fau_campo_recommended_requirement')]  =  $this->renderCampoText($course->getRecommendedRequirementAll());
            }
            if (!empty($course->getLearningTargetAll())) {
                $props[$this->lng->txt('fau_campo_learning_target')]  =  $this->renderCampoText($course->getLearningTargetAll());
            }
            if (!empty($course->getTargetGroupAll())) {
                $props[$this->lng->txt('fau_campo_target_group')]  =  $this->renderCampoText($course->getTargetGroupAll());
            }
            break;
        }
        
        
        if (!$in_modal && $this->dic->fau()->cond()->hard()->hasEventOrModuleRestrictions($event->getEventId())) {
            $hardRestrictions = $this->dic->fau()->cond()->hard();
            $hardRestrictions->checkByImportId($import_id, $this->dic->user()->getId());
            $restrictions_html = fauHardRestrictionsGUI::getInstance()->getResultModalLink($hardRestrictions);
            $props[$this->lng->txt('fau_rest_hard_restrictions')] = $restrictions_html;
        }

        if ($with_groups && !empty($info = $this->getParallelGroupsInfo($ref_id, $in_modal, true))) {
            $props[$this->lng->txt('fau_parallel_groups') . ' (' . $this->service->getTermText($term) . ')']= $info;
        }

        return $props;
    }

    /**
     * Get the properties for the details view of a course
     * @return array title => html
     */
    protected function getCourseProperties(Course $course, Term $term, bool $in_modal) : array
    {
        $props = [];

        $title = $course->getTitle();
        if ($term->isValid()) {
            $title = $this->renderer->render(
                $this->factory->link()->standard($title, $this->service->getCampoUrl($course->getEventId(), $term))
                              ->withOpenInNewViewport(true));
        }
        if (!empty($course->getShorttext())) {
            $title .= ' (' . $course->getShorttext() . ')';
        }
        $props[$this->lng->txt('title')] = $title;
        if (!empty($course->getHoursPerWeek())) {
            $props[$this->lng->txt('fau_campo_hours_per_week')]  = (string) $course->getHoursPerWeek();
        }
        if (!empty($course->getTeachingLanguage())) {
            $props[$this->lng->txt('language')]  = $course->getTeachingLanguage();
        }
        if (!empty($course->getContents())) {
            $props[$this->lng->txt('fau_campo_contents')]  = $this->renderCampoText($course->getContents());
        }
        if (!empty($course->getLiterature())) {
            $props[$this->lng->txt('fau_campo_literature')]  =  $this->renderCampoText($course->getLiterature());
        }
        if (!empty($course->getRecommendedRequirement())) {
            $props[$this->lng->txt('fau_campo_recommended_requirement')]  =  $this->renderCampoText($course->getRecommendedRequirement());
        }
        if (!empty($course->getLearningTarget())) {
            $props[$this->lng->txt('fau_campo_learning_target')]  =  $this->renderCampoText($course->getLearningTarget());
        }
        if (!empty($course->getTargetGroup())) {
            $props[$this->lng->txt('fau_campo_target_group')]  =  $this->renderCampoText($course->getTargetGroup());
        }
        if (!empty($list = $this->service->persons()->getResponsiblesList(null, $course->getCourseId()))) {
            $props[$this->lng->txt('fau_campo_responsibles')] = $this->renderList($list);
        }
        return $props;
    }


    /**
     * Get the date properties for the details view of a course
     * @return array title => html
     */
    protected function getDateProperties(Course $course) : array
    {
        $props = [];
        $plan_dates = $this->service->dates()->getPlannedDatesList($course->getCourseId(), true);
        if (!empty($plan_dates)) {
            $props[$this->lng->txt('fau_dates_planned')] = $this->renderList($plan_dates);
        }

        $indiv_dates = $this->service->dates()->getIndividualDatesList($course->getCourseId(), true);
        if (!empty($indiv_dates)) {
            $props[$this->lng->txt('fau_dates_indiv')] = $this->renderList($indiv_dates);
        }
        return $props;
    }

    /**
     * Get a List of orgunits assigned with an event
     */
    protected function getEventOrgunitsInfo(Event $event, $aligned = true) : string
    {
        $list = [];
        foreach ($this->repo->getEventOrgunitsByEventId($event->getEventId()) as $eventOrgunit) {
            $unit = $this->dic->fau()->org()->repo()->getOrgunitByNumber($eventOrgunit->getFauorgNr());
            if (!empty($unit)) {
                if (!empty($unit->getIliasRefId())) {
                    $title = $this->renderer->render($this->factory->link()->standard($unit->getLongtext(), ilLink::_getStaticLink($unit->getIliasRefId())));
                }
                else {
                    $title = $unit->getLongtext();
                }
                $list[] = $title . ' (' . $unit->getFauorgNr() . ')';
            }
        }
        return $this->renderList($list, $aligned);
    }

    /**
     * Get a List of parallel groups enclosed in an object
     */
    public function getParallelGroupsInfo(int $ref_id,  bool $in_modal, bool $aligned) : string
    {
        $list = [];
        $modals = [];
        foreach ($this->dic->fau()->ilias()->objects()->getParallelGroupsInfos($ref_id) as $group) {
            if ($in_modal) {
                $list[] = $this->getGroupInfo($group, true);
            } else {
                $import_id = ImportId::fromString((string) $group->getImportId())->withEventId(null);
                $modal = $this->getDetailsModal($import_id, $group->getRefId());
                $modals[] = $modal;
                $list[] = $this->getGroupInfo($group, true, $this->getDetailsButton($modal, $group->getTitle()));
            }
        }
        return $this->renderer->render($modals) . $this->renderList($list, $aligned);
    }

    /**
     * Get the info about a parallel group
     */
    public function getGroupInfo(ContainerInfo $group, bool $with_title, ?Button $title_button = null) : string
    {
        // set event id null to prevent event infos being shown in the details modal
        $import_id = ImportId::fromString((string) $group->getImportId())->withEventId(null);

        $parts = [];
        if (isset($title_button)) {
            $parts[] = $this->renderer->render($title_button);
        }
        elseif ($with_title) {
            $parts[] = '<em>' . $group->getTitle() . '</em>';
        }
        if (!empty($group->getDescription())) {
            $parts[] = $group->getDescription();
        }
        if (!empty($line = $this->getDatesLine($import_id))) {
            $parts[] = $line;
        }
        if (!empty($line = $this->getResponsiblesLine($import_id))) {
            $parts[] = $line;
        }
        foreach ($group->getProperties() as $prop) {
            if ($prop->hasAlert()) {
                $parts[] = '<strong>' . $prop->getString() . '</strong>';
            }
            else {
                $parts[] = $prop->getString();
            }
        }
        return implode("<br />\n", $parts);
    }

    public function getGroupTitleWithDetailsLink(ContainerInfo $group) : string
    {
        // set event id null to prevent event infos being shown in the details modal
        $import_id = ImportId::fromString((string) $group->getImportId())->withEventId(null);
        return $this->getDetailsLink($import_id, $group->getRefId(), $group->getTitle());
    }

    /**
     * Render a list of string elements
     * @param string[] $list
     * @return string
     */
    protected function renderList(array $list, bool $aligned = true) : string
    {
        $html = $this->renderer->render($this->factory->listing()->unordered($list));
        if ($aligned) {
            $html = '<div style="margin-top:-10px; margin-left:-30px">' . $html . '</div>';
        }
        return $html;
    }


    /**
     * Render a richtext field from campo
     * @param string $text
     * @return void
     */
    protected function renderCampoText(?string $text) : string
    {
        $additional_tags = ['br','ul','ol','li'];

        foreach ($additional_tags as $tag) {
            $text = ilUtil::maskTag($text, $tag);
        }

        $text = ilUtil::secureString($text);

        foreach ($additional_tags as $tag) {
            $text = ilUtil::unmaskTag($text, $tag);
        }

        return $text;
    }
}
