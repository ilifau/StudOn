<?php


/**
 * fau: exCalc - new GUI class for result calculations.
 * @author            Fred Neumann <fred.neumann@fau.de>
 * @ingroup           ModulesExercise
 * @ilCtrl_IsCalledBy ilExCalculateGUI: ilObjExerciseGUI, ilExerciseManagementGUI
 */
class ilExCalculateGUI
{
    /**
     * Where is the gui embedded (settings tab or grading tab)
     */
    const PARENT_SETTINGS = 'settings';
    const PARENT_GRADING = 'grading';

    /**
     * How is the gui galled (on own subtab or from a parent tab)
     */
    const DISPLAY_SUBTAB = 'subtab';
    const DISPLAY_PARENT = 'parent';

    /** @var ilTemplate */
    protected $tpl;

    /** @var ilCtrl */
    protected $ctrl;

    /** @var ilLanguage */
    protected $lng;

    /** @var ilObjExercise */
    protected $exercise;

    /** @var ilExCalculate */
    protected $calculator;

    /**
     * Where is the gui embedded (settings tab or grading tab)
     * @var string
     */
    protected $parent_type;

    /**
     * How is the gui galled (on own subtab or from a parent tab)
     * @var string
     */
    protected $display_type;

    /**
     * Constructor
     * @param ilObjExercise $a_exercise
     * @param string $a_parent_type (PARENT_SETTINGS | PARENT_GRADING)
     */
    public function __construct(ilObjExercise $a_exercise, $a_parent_type)
    {
        global $DIC;

        $this->tpl = $DIC['tpl'];
        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();

        $this->exercise = $a_exercise;
        $this->parent_type = $a_parent_type;
        
        // set if gui is shown on its own sub tab on on another parent tab
        if ($_GET['display'] == 'parent' || in_array($this->ctrl->getCmd(), ['callCalculateAll']))
        {
            $this->display_type = self::DISPLAY_PARENT;
            $this->ctrl->setParameter($this, 'display', 'parent');
        }
        else {
            $this->display_type = self::DISPLAY_SUBTAB;
        }
    }

    /**
     * Get how the gui is displayed 
     * @return string   (DISPLAY_SUBTAB | DISPLAY_PARENT)
     */
    public function getDisplayType() {
        return $this->display_type;
    }

    /**
     * Check if the current user can edit the settings
     */
    public function canEditSettings() {
        global $DIC;
        return (
            $this->exercise->getPassMode() == ilObjExercise::PASS_MODE_MANUAL ||
                ($this->exercise->getPassMode() == ilObjExercise::PASS_MODE_CALC 
                    && $DIC->access()->checkAccess('write', '', $this->exercise->getRefId())));
    }

    /**
     * Check if the current user can calculate results
     * @return bool
     */
    public function canCalculate() {
        // permission check is already done by parent
        return ($this->parent_type == self::PARENT_GRADING &&
                ($this->exercise->getPassMode() == ilObjExercise::PASS_MODE_MANUAL ||
                    $this->exercise->getPassMode() == ilObjExercise::PASS_MODE_CALC));
    }
    
    /**
     * Main entry
     */
    public function executeCommand()
    {
        // set the calculator here to prevent database reads in constructor
        include_once "./Modules/Exercise/classes/class.ilExCalculate.php";
        $this->calculator = new ilExCalculate($this->exercise);

        $next_class = $this->ctrl->getNextClass($this);
        $cmd = $this->ctrl->getCmd("editSettings");

        switch ($next_class) {
            default:
                switch ($cmd) {
                    case 'editSettings':
                    case 'saveSettings':
                        if ($this->canEditSettings()) {
                            $this->$cmd();
                        }
                        break;

                    case 'callCalculateAll':
                    case 'confirmCalculateAll':
                    case 'calculateAll':
                        if ($this->canCalculate()) {
                            $this->$cmd();
                        }
                        break;

                    case 'cancelOrReturn':
                        $this->$cmd();
                        break;
                }
        }
    }

    /**
     * Show the settings form with the saved options
     */
    protected function editSettings()
    {
//        global $DIC;
//
//        if ($this->canCalculate()) {
//            $button = ilLinkButton::getInstance();
//            $button->setCaption('exc_calculate_overall_results');
//            $button->setUrl($this->ctrl->getLinkTargetByClass(['ilexercisemanagementgui', 'ilexcalculategui'], 'confirmCalculateAll'));
//            $DIC->toolbar()->addButtonInstance($button);
//        }

        if ($this->exercise->getPassMode() == ilObjExercise::PASS_MODE_CALC) {
            ilUtil::sendInfo($this->lng->txt('exc_calc_settings_update_status'));
        }

        $form = $this->initForm();
        $this->readFormValues($form);
        $this->tpl->setContent($form->getHTML());
    }

    /**
     * Save the settings
     */
    protected function saveSettings()
    {
        $form = $this->initForm();
        if ($this->validateForm($form)) {
            $this->saveFormValues($form);
            ilUtil::sendInfo($this->lng->txt("settings_saved"), true);
            $this->ctrl->redirect($this, 'editSettings');
        }
        else {
            ilUtil::sendFailure($this->lng->txt("form_input_not_valid"));
            $form->setValuesByPost();
            $this->tpl->setContent($form->getHTML());
        }
    }

    /**
     * Initialize the settings form
     * @return ilPropertyFormGUI
     */
    protected function initForm()
    {
        include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this, "submitExportForm"));
        $form->setTitle($this->lng->txt("exc_calc_settings"));

        // calculate selection
        $group = new ilRadioGroupInputGUI($this->lng->txt('exc_calc_mark_select'), 'mark_select');
        $group->setInfo($this->lng->txt('exc_calc_mark_select_info'));
        $group->setRequired(true);

        $option = new ilRadioOption($this->lng->txt('exc_calc_mark_select_marked'), ilExCalculate::SELECT_MARKED);
        $group->addOption($option);

        $option = new ilRadioOption($this->lng->txt('exc_calc_mark_select_mandatory'), ilExCalculate::SELECT_MANDATORY);
        $group->addOption($option);

        $option = new ilRadioOption($this->lng->txt('exc_calc_mark_select_number'), ilExCalculate::SELECT_NUMBER);

        // order
        $subitem = new ilSelectInputGUI($this->lng->txt('exc_calc_mark_select_order'), 'mark_select_order');
        $subitem->setRequired(true);
        $subitem->setOptions(array(
            ilExCalculate::ORDER_HIGHEST => $this->lng->txt('exc_calc_mark_select_highest'),
            ilExCalculate::ORDER_LOWEST => $this->lng->txt('exc_calc_mark_select_lowest')
        ));
        $option->addSubItem($subitem);

        // number
        $subitem = new ilNumberInputGUI($this->lng->txt('exc_calc_mark_select_count'), 'mark_select_count');
        $subitem->setInfo($this->lng->txt('exc_calc_mark_select_count_info'));
        $subitem->setSize(2);
        $subitem->setMinValue(1);
        $subitem->setDecimals(0);
        $subitem->setRequired(true);
        $option->addSubItem($subitem);

        $group->addOption($option);
        $form->addItem($group);

        $force = new ilCheckboxInputGUI($this->lng->txt('exc_calc_mark_force_zero'), 'mark_force_zero');
        $force->setInfo($this->lng->txt('exc_calc_mark_force_zero_info'));
        $form->addItem($force);

        // calculate function
        $group = new ilRadioGroupInputGUI($this->lng->txt('exc_calc_mark_function'), 'mark_function');
        $group->setRequired(true);

        $option = new ilRadioOption($this->lng->txt('exc_calc_mark_function_sum'), ilExCalculate::FUNCTION_SUM);
        $option->setInfo($this->lng->txt('exc_calc_mark_function_sum_info'));
        $group->addOption($option);

        $option = new ilRadioOption($this->lng->txt('exc_calc_mark_function_average'), ilExCalculate::FUNCTION_AVERAGE);
        $option->setInfo($this->lng->txt('exc_calc_mark_function_average_info'));
        $group->addOption($option);

        $form->addItem($group);

        // status calculation
        $item = new ilCheckboxInputGUI($this->lng->txt('exc_calc_status'), 'status_calculate');
        $item->setInfo($this->lng->txt('exc_calc_status_info'));

        // order
        $subitem = new ilSelectInputGUI($this->lng->txt('exc_calc_status_compare'), 'status_compare');
        $subitem->setRequired(true);
        $subitem->setOptions(array(
            ilExCalculate::COMPARE_LOWER => $this->lng->txt('exc_calc_status_compare_lower'),
            ilExCalculate::COMPARE_HIGHER => $this->lng->txt('exc_calc_status_compare_higher'),
            ilExCalculate::COMPARE_LOWER_EQUAL => $this->lng->txt('exc_calc_status_compare_lower_equal'),
            ilExCalculate::COMPARE_HIGHER_EQUAL => $this->lng->txt('exc_calc_status_compare_higher_equal')
        ));
        $item->addSubItem($subitem);

        // base
        $base = new ilRadioGroupInputGUI($this->lng->txt('exc_calc_status_compare_base'), 'status_compare_base');
        $base->setRequired(true);
        $item->addSubItem($base);

        // number
        $base_abs = new ilRadioOption($this->lng->txt('exc_calc_status_compare_absolute'), ilExCalculate::BASE_ABSOLUTE);
        $base_abs->setInfo($this->lng->txt('exc_calc_status_compare_absolute_info'));
        $number = new ilNumberInputGUI($this->lng->txt('exc_calc_status_compare_value'), 'status_compare_value_absolute');
        $number->setInfo($this->lng->txt('exc_calc_status_compare_info'));
        $number->setSize(5);
        $number->setDecimals(2);
        $number->setRequired(true);
        $base_abs->addSubItem($number);
        $base->addOption($base_abs);

        // percent
        $base_perc = new ilRadioOption($this->lng->txt('exc_calc_status_compare_percent'), ilExCalculate::BASE_PERCENT);
        $base_perc->setInfo($this->lng->txt('exc_calc_status_compare_percent_info'));
        $percent = new ilNumberInputGUI($this->lng->txt('exc_calc_status_compare_value'), 'status_compare_value_percent');
        $percent->setInfo($this->lng->txt('exc_calc_status_compare_info'));
        $percent->setSize(5);
        $percent->setMinValue(0);
        $percent->setMaxValue(100);
        $percent->setDecimals(2);
        $percent->setSuffix('%');
        $percent->setRequired(true);
        $base_perc->addSubItem($percent);
        $base->addOption($base_perc);

        // default value
        $subitem = new ilSelectInputGUI($this->lng->txt('exc_calc_status_default'), 'status_default');
        $subitem->setRequired(true);
        $subitem->setInfo($this->lng->txt('exc_calc_status_default_info'));
        $subitem->setOptions(array(
            ilExCalculate::STATUS_NOTGRADED => $this->lng->txt('exc_notgraded'),
            ilExCalculate::STATUS_PASSED => $this->lng->txt('exc_passed'),
            ilExCalculate::STATUS_FAILED => $this->lng->txt('exc_failed')
        ));
        $item->addSubItem($subitem);

        $form->addItem($item);

        $form->addCommandButton("saveSettings", $this->lng->txt("save"));
        return $form;
    }

    /**
     * Validate the form inputs
     * @param ilPropertyFormGUI $form
     * @return bool
     */
    protected function validateForm(ilPropertyFormGUI $form)
    {
        // standard checks
        $form->setDisableStandardMessage(true);
        $ok = $form->checkInput();

        // check for mandatory assignments
        if ($form->getInput('mark_select') == ilExCalculate::SELECT_NUMBER and $form->getInput('mark_select_count') != '') {
            include_once "./Modules/Exercise/classes/class.ilExAssignment.php";
            $mandatory = ilExAssignment::countMandatory($this->exercise->getId());
            if ($form->getInput('mark_select_count') < $mandatory) {
                $ok = false;
                /** @var ilNumberInputGUI $item */
                $item = $form->getItemByPostVar('mark_select_count');
                $item->setAlert(sprintf($this->lng->txt('exc_calc_mark_select_count_message'), $mandatory));
            }
        }

        return $ok;
    }

    /**
     * Read the form values
     * @param ilPropertyFormGUI $form
     */
    protected function readFormValues(ilPropertyFormGUI $form)
    {
        $values = array();
        $values['mark_function'] = $this->calculator->mark_function;
        $values['mark_select'] = $this->calculator->mark_select;
        $values['mark_select_order'] = $this->calculator->mark_select_order;
        $values['mark_select_count'] = $this->calculator->mark_select_count;
        $values['mark_force_zero'] = $this->calculator->mark_force_zero;
        $values['status_calculate'] = $this->calculator->status_calculate;
        $values['status_compare'] = $this->calculator->status_compare;
        $values['status_compare_base'] = $this->calculator->status_compare_base;
        if ($this->calculator->status_compare_base == ilExCalculate::BASE_ABSOLUTE) {
            $values['status_compare_value_absolute'] = $this->calculator->status_compare_value;
        }
        else {
            $values['status_compare_value_percent'] = $this->calculator->status_compare_value * 100;
        }
        $values['status_default'] = $this->calculator->status_default;
        $form->setValuesByArray($values);
    }

    /**
     * Save the form values
     * @param ilPropertyFormGUI $form
     */
    protected function saveFormValues(ilPropertyFormGUI $form)
    {
        $this->calculator->mark_function = (string) $form->getInput('mark_function');
        $this->calculator->mark_select = (string) $form->getInput('mark_select');
        $this->calculator->mark_select_order = (string) $form->getInput('mark_select_order');
        $this->calculator->mark_select_count = (int) $form->getInput('mark_select_count');
        $this->calculator->mark_force_zero = (bool) $form->getInput('mark_force_zero');
        $this->calculator->status_calculate = (bool) $form->getInput('status_calculate');
        $this->calculator->status_compare = (string) $form->getInput('status_compare');
        $this->calculator->status_compare_base = (string) $form->getInput('status_compare_base');
        if ($this->calculator->status_compare_base == ilExCalculate::BASE_ABSOLUTE) {
            $this->calculator->status_compare_value = (float) $form->getInput('status_compare_value_absolute');
        }
        else {
            $this->calculator->status_compare_value = (float) $form->getInput('status_compare_value_percent') / 100;
        }
        $this->calculator->status_default = (string) $form->getInput('status_default');
        $this->calculator->writeOptions();
    }

    /**
     * Entry function to set determine the display type
     */
    protected function callCalculateAll()
    {
        $this->confirmCalculateAll();
    }

    /**
     * Confirm the calculation
     */
    protected function confirmCalculateAll()
    {
        $gui = new ilConfirmationGUI();
        $gui->setFormAction($this->ctrl->getFormAction($this));
        $gui->setHeaderText($this->lng->txt('exc_calc_confirm_all'));
        $gui->setCancel($this->lng->txt('cancel'), 'cancelOrReturn');
        $gui->setConfirm($this->lng->txt('exc_calc_calculate'), 'calculateAll');
        $this->tpl->setContent($gui->getHTML());
    }

    /**
     * Calculate the results for all participants
     */
    protected function calculateAll()
    {
        $this->calculator->calculateResults();
        ilUtil::sendSuccess($this->lng->txt("exc_calc_all_calculated"), true);
        $this->ctrl->returnToParent($this);
    }

    /**
     * Cancel the or return from an operation
     */
    protected function cancelOrReturn()
    {
        switch ($this->display_type) {
            case self::DISPLAY_SUBTAB:
                $this->ctrl->redirect($this);
                break;

            case self::DISPLAY_PARENT:
                $this->ctrl->returnToParent($this);
                break;
        }
    }
}
