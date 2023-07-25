<?php

use FAU\BaseGUI;
use ILIAS\UI\Component\Input\Container\Filter\Standard;

use ILIAS\UI\Component\Item\Group;
use ILIAS\UI\Component\ViewControl\Pagination;
use FAU\Study\Data\ImportId;
use FAU\Study\Data\Term;
use FAU\Study\Data\Course;

/**
 * Search for events from campo
 *
 * @ilCtrl_Calls fauStudyMyModulesGUI: ilPropertyFormGUI, ilObjRootFolderGUI
 */
class fauStudyMyModulesGUI extends BaseGUI
{
    const CHECKBOX_NAME = 'id[]';
    const PAGINATION_NAME = 'page';
    

    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute a command
     */
    public function executeCommand()
    {
        $this->tpl->loadStandardTemplate();
        $this->tpl->setTitle($this->lng->txt('fau_my_modules_selection'));
        $this->tpl->setTitleIcon(ilUtil::getImagePath('icon_my_modules.svg'));

        $cmd = $this->ctrl->getCmd('show');
        $next_class = $this->ctrl->getNextClass();

        switch ($next_class) {
            default:
                switch ($cmd)
                {
                    case 'show':
                    case 'applyFilter':
                    case 'saveModules':
                        $this->$cmd();
                        break;

                    default:
                        $this->tpl->setContent('unknown command: ' . $cmd);
                }
        }

        $this->tpl->printToStdout();
    }
    
    protected function getFilter() : Standard
    {
        global $DIC;
        $select = $DIC->ui()->factory()->input()->field()->select($this->lng->txt('studydata_semester'), $DIC->fau()->study()->getTermSearchOptions())
                      ->withValue($DIC->fau()->tools()->preferences()->getTermIdForMyMemberships());
        $action = $DIC->ctrl()->getLinkTarget($this, "applyFilter", "", true);
        return $DIC->uiService()->filter()->standard("fauFilterMyMem", $action, ["term_id" => $select], [true], true, true);
    }
    
    protected function applyFilter()
    {
        global $DIC;
        $filter_data = $DIC->uiService()->filter()->getData($this->getFilter());
        $DIC->fau()->tools()->preferences()->setTermIdForMyMemberships($filter_data['term_id']);
        $this->ctrl->redirect($this, 'show');
    }
    
    protected function show()
    {
        $tpl = new ilTemplate("tpl.fau_study_my_modules.html",true,true,"Services/FAU/Study/GUI");

        $tpl->setVariable('INFO1', $this->lng->txt('fau_my_modules_info1'));
        $tpl->setVariable('INFO2', $this->lng->txt('fau_my_modules_info2'));
        $tpl->setVariable('FORMACTION', $this->ctrl->getFormAction($this, 'saveModules'));
        $tpl->setVariable('FILTER_HTML', $this->renderer->render($this->getFilter()));
        $tpl->setVariable('LIST_HTML', $this->dic->ui()->renderer()->render($this->getList()));
        $tpl->setVariable('CMD_MOVE', 'saveModules');
        $tpl->setVariable('TXT_SAVE', $this->lng->txt('fau_my_modules_save'));
        $this->tpl->setContent($tpl->get());
    }
    
    protected function saveModules()
    {
        $user_id = $this->dic->user()->getId();
        
        $params = $this->request->getParsedBody();
        $module_ids = (array) ($params['module_ids']) ?? [];
        
        foreach ($module_ids as $course_id => $module_id) {
            $module_id = (int) $module_id;
            $course_id = (int) $course_id;
            
            if (!empty($course = $this->dic->fau()->study()->repo()->getCourse($course_id))) {
                $term = new Term($course->getTermYear(), $course->getTermTypeId());
                $import_id = new ImportId($term->toString(), $course->getEventId(), $course->getCourseId());
                
                if (!empty($obj_id = $course->getIliasObjId())) {
                    foreach (ilObject::_getAllReferences($obj_id) as $ref_id) {
                        if (ilParticipants::_isParticipant($ref_id, $user_id)) {
                            $hardRestrictions = $this->dic->fau()->cond()->hard();
                            $hardRestrictions->checkByImportId($import_id, $user_id);
                            
                            $default_member = new \FAU\User\Data\Member($obj_id, $user_id);
                            if (empty($module_id)) {
                                $member = $this->dic->fau()->user()->repo()->getMember($obj_id, $user_id, $default_member)->withModuleId(null);
                                $this->dic->fau()->user()->repo()->save($member);
                            }
                            else {
                                $options = $hardRestrictions->getCheckedModuleSelectOptions();
                                $disabled_ids = $hardRestrictions->getCheckedModuleSelectDisabledIds();
                                if (isset($options[$module_id]) && !in_array($module_id, $disabled_ids)) {
                                    $member = $this->dic->fau()->user()->repo()->getMember($obj_id, $user_id, $default_member)->withModuleId($module_id);
                                    $this->dic->fau()->user()->repo()->save($member);
                                }
                            }
                        }
                    }
                }
            }
        }
        
        $this->tpl->setOnScreenMessage('success', $this->lng->txt('fau_my_modules_is_saved'), true);
        $this->ctrl->redirect($this, 'show');
    }
    

    /**
     * Get the list of objects for setting the module_id
     */
    protected function getList() : Group
    {
        $this->lng->loadLanguageModule('trac');
        ilDatePresentation::setUseRelativeDates(false);
        
        $icon_crs = $this->factory->symbol()->icon()->standard('crs', $this->lng->txt('obj_crs'), 'medium');
        $icon_grp = $this->factory->symbol()->icon()->standard('grp', $this->lng->txt('obj_grp'), 'medium');

        $earliest_passed = $this->dic->fau()->tools()->convert()->dbDateToUnix(
            $this->dic->fau()->tools()->convert()->unixToDbDate(time() - 86400)
        );

        $synced_term_ids = [];
        foreach ($this->dic->fau()->sync()->getTermsToSync(true) as $term) {
            $synced_term_ids[] = $term->toString();
        }
        
        $data_items = [];
        $provider =  new ilPDSelectedItemsBlockMembershipsProvider($this->dic->user());
        foreach ($provider->getItems(['crs', 'grp']) as $item) {
            foreach ($this->dic->fau()->study()->repo()->getCoursesByIliasObjId($item['obj_id']) as $course) {
                $term = new Term($course->getTermYear() , $course->getTermTypeId());
                // fallback end date for courses without a planned or individual end date
                $term_end = $this->dic->fau()->study()->getTermEndTime($term);

                $item['course_id'] = $course->getCourseId();
                $item['event_id'] = $course->getEventId();
                $item['term_year'] = $course->getTermYear();
                $item['term_type_id'] = $course->getTermTypeId();
                $item['send_passed'] = $course->getSendPassed();

                $member = $this->dic->fau()->user()->repo()->getMember($item['obj_id'], $this->dic->user()->getId());
                $item['module_id'] = (isset($member) ? $member->getModuleId() : null);
                $item['show_studon_status'] = ($course->getSendPassed() == Course::SEND_PASSED_LP);
                
                $lp_status = \ilLPStatus::_lookupStatus($item['obj_id'], $this->dic->user()->getId(), false);
                if ($lp_status == ilLPStatus::LP_STATUS_COMPLETED_NUM) {
                    $item['studon_status'] = $this->lng->txt('crs_member_passed');
                }
                else {
                    $item['studon_status'] = $this->lng->txt('crs_member_not_passed');
                }
                
                $last_date = $this->dic->fau()->sync()->repo()->getCourseMaxDateAsTimestamp($course->getCourseId()) ?? $term_end;
                if (!in_array($term->toString(), $synced_term_ids)) {
                    $item['campo_status'] = $this->lng->txt('fau_campo_member_status_not_synced');
                }
                elseif ($course->getSendPassed() == Course::SEND_PASSED_NONE) {
                    $item['campo_status'] = $this->lng->txt('fau_campo_member_status_registered_never_passed');
                }
                elseif (!empty($last_date) && $last_date >= $earliest_passed) {
                    $date_pres = ilDatePresentation::formatDate(new ilDate($last_date, IL_CAL_UNIX));
                    
                    $item['campo_status'] = sprintf($this->lng->txt($course->getSendPassed() == Course::SEND_PASSED_ALL
                        ? 'fau_campo_member_status_registered_before_all' 
                        : 'fau_campo_member_status_registered_before_lp'), $date_pres); 
                }
                elseif ($course->getSendPassed() == Course::SEND_PASSED_ALL) {
                    $item['campo_status'] = $this->lng->txt('fau_campo_member_status_passed_all');
                }
                elseif ($lp_status == ilLPStatus::LP_STATUS_COMPLETED_NUM) {
                    $item['campo_status'] = $this->lng->txt('fau_campo_member_status_passed_lp');
                }
                else {
                    $item['campo_status'] = $this->lng->txt('fau_campo_member_status_registered_not_passed');
                }
                
                $data_items[] = $item;
            }
        }

        $gui_items = [];
        foreach ($data_items as $item) {
            
            $link = ilLink::_getStaticLink($item['ref_id'], $item['type']);
            $term = new Term($item['term_year'] , $item['term_type_id']);
            $import_id = new ImportId($term->toString(),$item['event_id'], $item['course_id']);
            
            $info_gui = $this->dic->fau()->study()->info();
            $description = $info_gui->getLinksLine($import_id, $item['ref_id'])
                . $this->getModuleSelectionHtml($import_id->toString(), 'module_ids[' . $item['course_id'] . ']', $item['module_id']);
            
            $props = [
                $this->lng->txt('fau_campo_member_status_for_campo') => $item['campo_status'],
            ];
            
            if ($item['show_studon_status']) {
                $props[ $this->lng->txt('fau_campo_member_status_in_studon')] =  $item['studon_status'];
            }
            
            $gui_item = $this->factory->item()->standard('<a href="' . $link . '">'.$item['title'].'</a>')
                                  ->withDescription($description)
                                  ->withLeadIcon($item['type'] == 'crs' ? $icon_crs : $icon_grp)
                                  ->withProperties($props);

            $gui_items[] = $gui_item;
        }

        if (empty($gui_items)) {
            return $this->factory->item()->group($this->lng->txt('fau_my_modules_not_found'), $gui_items);
        }
        else {
            return $this->factory->item()->group($this->lng->txt('fau_my_modules_list'), $gui_items);
        }
    }

    /**
     * Get the HTML code 
     * @param string|null $import_id
     * @param string      $module_post_var
     * @param int|null    $selected_module_id
     * @return string
     */
    public function getModuleSelectionHtml(?string $import_id, string $module_post_var, ?int $selected_module_id) : string
    {
        $html = '';
        $import_id = \FAU\Study\Data\ImportId::fromString($import_id);
        if ($import_id->isForCampo()) {
            $hardRestrictions = $this->dic->fau()->cond()->hard();
            $hardRestrictions->checkByImportId($import_id, $this->dic->user()->getId());

            $html = '<p>' .$this->lng->txt('fau_rest_hard_restrictions') . ': '
                . fauHardRestrictionsGUI::getInstance()->getResultModalLink($hardRestrictions, $selected_module_id) . '</p>';
            
            $options = $hardRestrictions->getCheckedModuleSelectOptions();
            $disabled_ids = $hardRestrictions->getCheckedModuleSelectDisabledIds();
            if (true || !empty($options)) {
                $html .= '<p><label for="' . $module_post_var . '">' . $this->lng->txt(
                        'fau_module_select'
                    ) . ':</label> ';
                $html .= "<select id=\"$module_post_var\" name=\"$module_post_var\">";
                $html .= "<option value=\"0\">" . $this->lng->txt('please_select') . "</option>\n";
                foreach ($options as $module_id => $module_label) {
                    $text = ilUtil::prepareFormOutput($module_label);
                    $selected = ($module_id == $selected_module_id ? 'selected' : '');
                    $disabled = (in_array($module_id, $disabled_ids) ? 'disabled="disabled' : '');
                    $html .= "<option $disabled $selected value=\"$module_id\">$text</option>\n";
                }
                $html .= "</select></p>";
            }
        }
        return $html;
    }

}