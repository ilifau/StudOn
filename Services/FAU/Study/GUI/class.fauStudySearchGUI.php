<?php

use FAU\BaseGUI;
use FAU\Study\Data\SearchCondition;
use FAU\Study\Search;
use ILIAS\UI\Component\Item\Group;

/**
 * Search for events from campo
 *
 * @ilCtrl_Calls fauStudySearchGUI: ilPropertyFormGUI
 */
class fauStudySearchGUI extends BaseGUI
{
    const CHECKBOX_NAME = 'id[]';

    protected Search $search;

    protected $allow_move = false;


    public function __construct() {
        parent::__construct();
        $this->search = $this->dic->fau()->study()->search();
        $this->lng->loadLanguageModule('search');
    }

    /**
     * Execute a command
     */
    public function executeCommand()
    {
        $this->tpl->loadStandardTemplate();
        $this->tpl->setTitle($this->lng->txt('fau_search'));
        $this->tpl->setTitleIcon(ilObject::_getIcon("", "big", "src"));

        $cmd = $this->ctrl->getCmd('show');
        $next_class = $this->ctrl->getNextClass();

        switch ($next_class) {
            case strtolower(ilPropertyFormGUI::class):
                $form = $this->getSearchForm($this->search->getCondition());
                $this->ctrl->forwardCommand($form);
                break;

            default:
                switch ($cmd)
                {
                    case 'show':
                    case 'search':
                    case 'reset':
                    case 'move':
                        $this->$cmd();
                        break;

                    default:
                        $this->tpl->setContent('unknown command: ' . $cmd);
                }
        }

        $this->tpl->printToStdout();
    }

    protected function show()
    {
        $tpl = new ilTemplate("tpl.fau_study_search.html",true,true,"Services/FAU/Study/GUI");

        $cond = $this->search->getCondition();
        $form = $this->getSearchForm($cond);

        $tpl->setVariable('SEARCH_FORM_HTML', $form->getHTML());
        if (!$cond->isEmpty()) {
            $tpl->setVariable('RESULT_LIST_HTML', $this->dic->ui()->renderer()->render($this->getList($cond)));

            if ($this->allow_move) {
                $tpl->setVariable('FORMACTION', $this->ctrl->getFormAction($this));
                $tpl->setVariable('SEL_ALL_PARENT', '');
                $tpl->setVariable('SEL_ALL_CB_NAME', self::CHECKBOX_NAME);
                $tpl->setVariable('TXT_SELECT_ALL', $this->lng->txt('select_all'));
                $tpl->setVariable('TXT_MOVE_COURSES', $this->lng->txt('fau_move_selected_courses'));

                $tpl->setVariable('ICON_DOWNRIGHT', $this->renderer->render(
                    $this->factory->image()->standard(ilUtil::getImagePath('arrow_downright.svg'), $this->lng->txt('actions'))));
            }
        }
        $this->tpl->setContent($tpl->get());
    }

    protected function search()
    {
        $form = $this->getSearchForm($this->search->getCondition());
        $form->checkInput();
        $form->setValuesByPost();
        $this->search->setCondition($this->getFormCondition($form));
        $this->ctrl->redirect($this, 'show');
    }

    protected function reset()
    {
        $this->search->setCondition(SearchCondition::model());
        $this->ctrl->redirect($this, 'show');
    }

    protected function getSearchForm(SearchCondition $condition): ilPropertyFormGUI
    {
        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this, 'show'));

        $pattern = new ilTextInputGUI($this->lng->txt('fau_search_title'), 'pattern');
        $pattern->setInfo($this->lng->txt('fau_search_title_info'));
        $pattern->setValue($condition->getPattern());
        $form->addItem($pattern);

        $term = new ilSelectInputGUI($this->lng->txt('studydata_semester'), 'term_id');
        $options = $this->dic->fau()->study()->getTermSearchOptions(null, false);
        $current = $this->dic->fau()->study()->getCurrentTerm()->toString();
        $term->setOptions($options);
        if (!empty($condition->getTermId() && isset($options[$condition->getTermId()]))) {
            $term->setValue($condition->getTermId());
        }
        elseif (isset($options[$current])) {
            $term->setValue($current);
        }
        else {
            $term->setValue((string) current($options));
        }
        $form->addItem($term);

        $cos = new fauComboInputGUI($this->lng->txt('studydata_cos'), 'cos_ids');
        $cos->setOptions($this->dic->fau()->study()->getCourseOfStudySelectOptions(0));
        $cos->setValue($condition->getCosIds());
        $form->addItem($cos);

        $mod = new fauComboInputGUI($this->lng->txt('studydata_module'), 'module_ids');
        $mod->setOptions($this->dic->fau()->study()->getModuleSelectOptions(0));
        $mod->setValue($condition->getModuleIds());
        $form->addItem($mod);

        $ref = new fauRepositorySelectorInputGUI($this->lng->txt('search_area'), 'search_ref_id');
        $ref->setTypeWhitelist(['root', 'cat']);
        $ref->setSelectableTypes(['cat']);
        $ref->setValue($condition->getIliasRefId());
        $form->addItem($ref);

        $form->addCommandButton('search', $this->lng->txt('search'));
        $form->addCommandButton('reset', $this->lng->txt('reset'));
        return $form;
    }

    protected function getFormCondition(ilPropertyFormGUI $form) : SearchCondition
    {
        /** @var ilTextInputGUI $pattern */
        $pattern = $form->getItemByPostVar('pattern');

        /** @var ilSelectInputGUI $term */
        $term = $form->getItemByPostVar('term_id');

        /** @var fauComboInputGUI $cos */
        $cos = $form->getItemByPostVar('cos_ids');

        /** @var fauComboInputGUI $mod */
        $mod = $form->getItemByPostVar('module_ids');

        /** @var fauRepositorySelectorInputGUI $ref */
        $ref = $form->getItemByPostVar('search_ref_id');

        return new SearchCondition(
            (string) $pattern->getValue(),
            (string) $term->getValue(),
            (string) $cos->getValue(),
            (string) $mod->getValue(),
            (int) $ref->getValue(),
            false
        );
    }


    protected function getList(SearchCondition $condition) : Group
    {
        $events = $this->search->getEventList();

        $icon_crs = $this->factory->symbol()->icon()->standard('crs', 'course', 'medium');
        $icon_missing = $this->factory->symbol()->icon()->standard('pecrs', 'missing', 'medium');
        $listGui = new ilObjCourseListGUI();;


        $this->allow_move = false;

        $items = [];
        foreach ($events as $event) {

            if(empty($event->getIliasRefId()) || !$event->isVisible()) {
                $item = $this->factory->item()->standard($event->getEventTitle())
                    ->withDescription($event->getEventShorttext())
                    ->withLeadIcon($icon_missing);
            }
            else {
                $link = ilLink::_getStaticLink($event->getIliasRefId(), 'crs');
                $title = $event->getIliasTitle();
                $props = [];
                $listGui->initItem($event->getIliasRefId(), ilObject::_lookupObjId($event->getIliasRefId()), 'crs');
                foreach ($listGui->getProperties() as $property) {
                    $props[$property['property']] = $property['value'];
                }
                $item = $this->factory->item()->standard('<a href="' . $link . '">'.$title.'</a>')
                    ->withDescription($event->getIliasDescription())
                    ->withLeadIcon($icon_crs)
                    ->withProperties($props)
                    ->withCheckbox(self::CHECKBOX_NAME, $event->isMoveable() ? $event->getIliasRefId() : null);

                if ($event->isMoveable()) {
                    $this->allow_move = true;
                }
            }

            $items[] = $item;
        }

//        $items = [];
//        $items[] = $this->factory->item()->standard(
//            '<a href="#"> Italienisch: Elementarkurs I - ItaliaNet A1 (Blended Learning Kurs - 2 SWS in Präsenz)</a>')
//            ->withProperties([
//                'Parallelgruppe 1' => 'Dozenten',
//                'Parallelgruppe 2' => 'Dozenten'
//            ])
//            ->withLeadIcon($icon)
//            ->withCheckbox(self::CHECKBOX_NAME, null)
//            ->withDescription('Übung, SZIT1EK1aBL');
//
//        $items[] = $this->factory->item()->standard(
//            '<a href="#"> Italienisch: Elementarkurs II - ItaliaNet A2 (Blended Learning Kurs - 2 SWS in Präsenz)</a>')
//            ->withLeadIcon($icon)
//            ->withCheckbox(self::CHECKBOX_NAME, '2')
//            ->withDescription('Übung, SZITIKEK2aBL');
//
//        $items[] = $this->factory->item()->standard(
//            '<a href="#"> Italienisch: Elementarkurs II - ItaliaNet B1 (Blended Learning Kurs - 2 SWS in Präsenz)</a>')
//            ->withLeadIcon($icon)
//            ->withCheckbox(self::CHECKBOX_NAME, 3)
//            ->withDescription('Übung, SZIT2EK3BL, 6 SWS, Italienisch');

        if (empty($items)) {
            return $this->factory->item()->group('Keine Lehrveranstaltungen gefunden', $items);
        } else {
            return $this->factory->item()->group('Gefundene Lehrveranstaltungen', $items);
        }

    }


    protected function move()
    {
        ilUtil::sendInfo('Das Verschieben ist in Kürze verfügbar.', true);

//        $_GET['ref_id'] = 1;
//        $container = new ilContainerGUI(array(), 0, false, false);
//        $container->cutObject();

        $this->ctrl->redirect($this, 'show');
    }
}