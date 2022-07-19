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
    const CHECKBOX_NAME = 'fau_study_obj_id[]';

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
            $tpl->setVariable('RESULT_LIST_HTML', $this->dic->ui()->renderer()->render($this->getEventList($cond)));

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
        var_dump($_POST);
        $form = $this->getSearchForm($this->search->getCondition());
        $form->checkInput();
        $form->setValuesByPost();
        $this->search->setCondition($this->getFormCondition($form));
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
        $term->setOptions($this->dic->fau()->study()->getTermSearchOptions($condition->getTermId(), false));
        $term->setValue($condition->getIliasRefId());
        $form->addItem($term);

        $cos = new fauComboInputGUI($this->lng->txt('studydata_cos'), 'cos_id');
        $cos->setOptions($this->dic->fau()->study()->getCourseOfStudySelectOptions(0));
        $cos->setValue(implode(',', $condition->getCosIds()));
        $form->addItem($cos);

        $mod = new fauComboInputGUI($this->lng->txt('studydata_module'), 'module_id');
        $mod->setOptions($this->dic->fau()->study()->getModuleSelectOptions(0));
        $mod->setValue(implode(',', $condition->getModuleIds()));
        $form->addItem($mod);

        $ref = new fauRepositorySelectorInputGUI($this->lng->txt('search_area'), 'search_ref_id');
        $ref->setTypeWhitelist(['root', 'cat']);
        $ref->setSelectableTypes(['cat']);
        $ref->setValue($condition->getIliasRefId());
        $form->addItem($ref);

        $form->addCommandButton('search', $this->lng->txt('search'));
        return $form;
    }

    protected function getFormCondition(ilPropertyFormGUI $form) : SearchCondition
    {
        /** @var ilTextInputGUI $pattern */
        $pattern = $form->getItemByPostVar('pattern');

        /** @var ilSelectInputGUI $term */
        $term = $form->getItemByPostVar('term_id');

        /** @var fauComboInputGUI $cos */
        $cos = $form->getItemByPostVar('cos_id');

        /** @var fauComboInputGUI $mod */
        $mod = $form->getItemByPostVar('module_id');

        /** @var fauRepositorySelectorInputGUI $ref */
        $ref = $form->getItemByPostVar('search_ref_id');

        return new SearchCondition(
            (string) $pattern->getValue(),
            (string) $term->getValue(),
            (array) explode(',', $cos->getValue()),
            (array) explode(',', $mod->getValue()),
            (int) $ref->getValue(),
            false
        );
    }


    protected function getEventList(SearchCondition $condition) : Group
    {
        $icon = $this->factory->symbol()->icon()->standard('crs', 'course', 'medium');

        $this->allow_move = false;

        $items = [];
        $items[] = $this->factory->item()->standard(
            '<a href="#"> Italienisch: Elementarkurs I - ItaliaNet A1 (Blended Learning Kurs - 2 SWS in Präsenz)</a>')
            ->withLeadIcon($icon)
            ->withCheckbox(self::CHECKBOX_NAME, null)
            ->withDescription('Übung, SZIT1EK1aBL');

        $items[] = $this->factory->item()->standard(
            '<a href="#"> Italienisch: Elementarkurs II - ItaliaNet A2 (Blended Learning Kurs - 2 SWS in Präsenz)</a>')
            ->withLeadIcon($icon)
            ->withCheckbox(self::CHECKBOX_NAME, '2')
            ->withDescription('Übung, SZITIKEK2aBL');

        $items[] = $this->factory->item()->standard(
            '<a href="#"> Italienisch: Elementarkurs II - ItaliaNet B1 (Blended Learning Kurs - 2 SWS in Präsenz)</a>')
            ->withLeadIcon($icon)
            ->withCheckbox(self::CHECKBOX_NAME, 3)
            ->withDescription('Übung, SZIT2EK3BL, 6 SWS, Italienisch');

        return $this->factory->item()->group('Gefundene Lehrveranstaltungen', $items);
    }


    protected function move()
    {
        $this->tpl->setContent('<pre>' . print_r($_POST, true) . '</pre>');
    }
}