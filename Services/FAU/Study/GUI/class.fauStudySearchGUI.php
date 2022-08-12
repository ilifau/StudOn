<?php

use FAU\BaseGUI;
use FAU\Study\Data\SearchCondition;
use FAU\Study\Search;
use ILIAS\UI\Component\Item\Group;
use ILIAS\UI\Component\ViewControl\Pagination;

/**
 * Search for events from campo
 *
 * @ilCtrl_Calls fauStudySearchGUI: ilPropertyFormGUI, ilObjRootFolderGUI
 */
class fauStudySearchGUI extends BaseGUI
{
    const CHECKBOX_NAME = 'id[]';
    const PAGINATION_NAME = 'page';

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

            case strtolower(ilObjRootFolderGUI::class):
                $container = new ilObjRootFolderGUI(array(), 1, true, false);
                $this->ctrl->setReturn($this, 'show');
                $this->ctrl->forwardCommand($container);
                break;


            default:
                switch ($cmd)
                {
                    case 'show':
                    case 'page':
                    case 'search':
                    case 'reset':
                    case 'cut':
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
        //ilUtil::sendInfo('<pre>' . print_r($this->search->getCondition(), true) . '</pre>');

        $tpl = new ilTemplate("tpl.fau_study_search.html",true,true,"Services/FAU/Study/GUI");

        $form = $this->getSearchForm($this->search->getCondition());
        $tpl->setVariable('SEARCH_FORM_HTML', $form->getHTML());

        if (!$this->search->getCondition()->isEmpty()) {

            // get the paging after the list because it depends on the number of found records
            $list = $this->getList();
            $paging = $this->getPaging();

            $tpl->setVariable('RESULT_LIST_HTML', $this->dic->ui()->renderer()->render($list));
            $tpl->setVariable('RESULT_PAGING_HTML', isset($paging) ? $this->dic->ui()->renderer()->render($paging) : '');

            if ($this->allow_move) {
                $tpl->setVariable('FORMACTION', $this->ctrl->getFormAction($this));
                $tpl->setVariable('SEL_ALL_PARENT', '');
                $tpl->setVariable('SEL_ALL_CB_NAME', self::CHECKBOX_NAME);
                $tpl->setVariable('TXT_SELECT_ALL', $this->lng->txt('select_all'));
                $tpl->setVariable('TXT_MOVE_COURSES', $this->lng->txt('fau_move_selected_courses'));
                $tpl->setVariable('CMD_MOVE', 'cut');

                $tpl->setVariable('ICON_DOWNRIGHT', $this->renderer->render(
                    $this->factory->image()->standard(ilUtil::getImagePath('arrow_downright.svg'), $this->lng->txt('actions'))));
            }
        }
        $this->tpl->setContent($tpl->get());
    }

     /**
     * Start a new search
     */
    protected function search()
    {
        $form = $this->getSearchForm($this->search->getCondition());
        $form->checkInput();
        $form->setValuesByPost();
        // this also resets the count of found records and the paging
        $this->search->setCondition($this->getSearchFormCondition($form));
        $this->search->clearCacheForCondition();
        $this->ctrl->redirect($this, 'show');
    }

    /**
     * Reset the search conditions
     */
    protected function reset()
    {
        // this also resets the count of found records and the paging
        $this->search->setCondition(SearchCondition::model());
        $this->ctrl->redirect($this, 'show');
    }

    /**
     * Move to another page
     */
    protected function page()
    {
        $params = $this->dic->http()->request()->getQueryParams();
        $page = (int) ($params[self::PAGINATION_NAME] ?? 0);
        $this->search->setCondition($this->search->getCondition()->withPage($page));
        $this->show();
    }

    /**
     * Get the search form
     */
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

    /**
     * Get a new searching condition from the search form
     */
    protected function getSearchFormCondition(ilPropertyFormGUI $form) : SearchCondition
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

    /**
     * Get the list of events as an item group
     * This does the query
     */
    protected function getList() : Group
    {
        $listGUI = new ilObjCourseListGUI();
        $pathGUI = new ilPathGUI();
        $pathGUI->enableTextOnly(false);

        $icon_crs = $this->factory->symbol()->icon()->standard('crs', $this->lng->txt('fau_search_ilias_course'), 'medium');
        $icon_missing = $this->factory->symbol()->icon()->standard('pecrs', $this->lng->txt('fau_search_ilias_course_not'), 'medium');

        $items = [];
        $this->allow_move = false;
        foreach ($this->search->getEventList() as $event) {

            if(empty($event->getIliasRefId()) || !$event->isVisible()) {
                $item = $this->factory->item()->standard((string) $event->getEventTitle())
                    ->withDescription((string) $event->getEventShorttext())
                    ->withLeadIcon($icon_missing)
                    ->withProperties([
                        $this->lng->txt('fau_search_ilias_course') => $this->lng->txt(empty($event->getIliasRefId()) ?
                            'fau_search_ilias_course_not_found' : 'fau_search_ilias_course_not_visible')
                    ])
                    ->withCheckbox(self::CHECKBOX_NAME);
            }
            else {
                $link = ilLink::_getStaticLink($event->getIliasRefId(), 'crs');
                $title = $event->getIliasTitle();
                $description = $event->getIliasDescription() . ' ' . $pathGUI->getPath(1, $event->getIliasRefId());
                $props = [];
                $listGUI->initItem($event->getIliasRefId(), ilObject::_lookupObjId($event->getIliasRefId()), 'crs');
                foreach ($listGUI->getProperties() as $property) {
                    $props[$property['property']] = $property['value'];
                }
                if ($event->isNested()) {
                    $list = [];
                    foreach ($this->dic->fau()->ilias()->objects()->getParallelGroupsInfos($event->getIliasRefId()) as $group) {
                        $entry = $group->getTitle();
                        if (!empty($group->getInfoHtml())) {
                            $entry .= '<br><small>' . $group->getInfoHtml() . '</small>';
                        }
                        $list[] = '<li>' . $entry . '</li>';
                    }
                    if (!empty($list)) {
                        $description .= '<ul>'. implode('', $list) . '</ul>';
                    }
                }
                $item = $this->factory->item()->standard('<a href="' . $link . '">'.$title.'</a>')
                    ->withDescription($description)
                    ->withLeadIcon($icon_crs)
                    ->withProperties($props)
                    ->withCheckbox(self::CHECKBOX_NAME, $event->isMoveable() ? $event->getIliasRefId() : null);

                if ($event->isMoveable()) {
                    $this->allow_move = true;
                }
            }
            $items[] = $item;
        }

        if (empty($items)) {
            return $this->factory->item()->group($this->lng->txt('fau_search_no_events_found'), $items);
        }
        else {
            $cond = $this->search->getCondition();
            if ($cond->needsPaging()) {
                $found = sprintf($this->lng->txt('fau_search_numbers_of'),
                    $cond->getOffset() + 1,
                    min($cond->getOffset() + $cond->getLimit(), $cond->getFound()),
                    $cond->getFound()
                );
            }
            else {
                $found = $cond->getFound();
            }
            return $this->factory->item()->group($this->lng->txt('fau_search_found_events') . ' ' . $found, $items);
        }

    }

    /**
     * Get the paging control (if needed)
     */
    protected function getPaging() : ?Pagination
    {
        $condition = $this->search->getCondition();
        if (!$condition->needsPaging()) {
            return null;
        }
        return $this->factory->viewControl()->pagination()
              ->withTargetURL($this->ctrl->getLinkTarget($this, 'page'), self::PAGINATION_NAME)
              ->withTotalEntries((int) $condition->getFound())
              ->withPageSize((int) $condition->getLimit())
              ->withCurrentPage($condition->getPage());
    }

    /**
     * Move selected courses to another location
     * This command must be named 'cut' because ilContainerGUI expects this
     */
    protected function cut()
    {
        $_GET['ref_id'] = 1;
        $container = new ilObjRootFolderGUI(array(), 1, true, false);
        $container->cutObject();
    }
}