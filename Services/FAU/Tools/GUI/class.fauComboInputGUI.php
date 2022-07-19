<?php

/**
 * This class represents a combo box selection list
 */
class fauComboInputGUI extends ilSubEnabledFormPropertyGUI implements ilTableFilterItem, ilToolbarItem
{
    protected $cust_attr = array();
    protected $options = [];
    protected $value;

    /**
     * Constructor
     *
     * @param	string	$a_title	Title
     * @param	string	$a_postvar	Post Variable
     */
    public function __construct($a_title = "", $a_postvar = "")
    {
        global $DIC;

        $this->lng = $DIC->language();
        parent::__construct($a_title, $a_postvar);
        $this->setType("combo");

        $this->addCustomAttribute('style="width: 90%;"');
        $this->addCustomAttribute(' class="autocomplete"');
    }

    /**
     * Set Options.
     *
     * @param array $a_options	Options. Array ("value" => "option_text")
     */
    public function setOptions($a_options)
    {
        $this->options = $a_options;
    }

    /**
     * Get Options.
     *
     * @return	array	Options. Array ("value" => "option_text")
     */
    public function getOptions()
    {
        return $this->options ? $this->options :[];
    }

    /**
     * Set Value.
     *
     * @param	string	$a_value	Value
     */
    public function setValue($a_value)
    {
        $this->value = $a_value;
    }

    /**
     * Get Value.
     *
     * @return	string	Value
     */
    public function getValue()
    {
        return $this->value;
    }


    /**
     * Set value by array
     *
     * @param	array	$a_values	value array
     */
    public function setValueByArray($a_values)
    {
        $this->setValue($a_values[$this->getPostVar()]);
        foreach ($this->getSubItems() as $item) {
            $item->setValueByArray($a_values);
        }
    }

    /**
     * Check input, strip slashes etc. set alert, if input is not ok.
     *
     * @return	boolean		Input ok, true/false
     */
    public function checkInput()
    {
        $_POST[$this->getPostVar()] = ilUtil::stripSlashes($_POST[$this->getPostVar()]);
        if ($this->getRequired() && trim($_POST[$this->getPostVar()]) == "") {
            $this->setAlert($this->lng->txt("msg_input_is_required"));
            return false;
        } elseif (!array_key_exists($_POST[$this->getPostVar()], (array) $this->getOptions())) {
            $this->setAlert($this->lng->txt('msg_invalid_post_input'));
            return false;
        }
        return $this->checkSubItemsInput();
    }


    public function addCustomAttribute($a_attr)
    {
        $this->cust_attr[] = $a_attr;
    }

    public function getCustomAttributes()
    {
        return (array) $this->cust_attr;
    }

    /**
     * Render item
     */
    public function render($a_mode = "")
    {
        $tpl = new ilTemplate("tpl.prop_select.html", true, true, "Services/FAU/Tools/GUI");

        foreach ($this->getCustomAttributes() as $attr) {
            $tpl->setCurrentBlock('cust_attr');
            $tpl->setVariable('CUSTOM_ATTR', $attr);
            $tpl->parseCurrentBlock();
        }

        // determine value to select. Due to accessibility reasons we
        // should always select a value (per default the first one)
        $first = true;
        foreach ($this->getOptions() as $option_value => $option_text) {
            if ($first) {
                $sel_value = $option_value;
            }
            $first = false;
            if ((string) $option_value == (string) $this->getValue()) {
                $sel_value = $option_value;
            }
        }
        foreach ($this->getOptions() as $option_value => $option_text) {
            $tpl->setCurrentBlock("prop_select_option");
            $tpl->setVariable("VAL_SELECT_OPTION", ilUtil::prepareFormOutput($option_value));
            if ((string) $sel_value == (string) $option_value) {
                $tpl->setVariable(
                    "CHK_SEL_OPTION",
                    'selected="selected"'
                );
            }
            $tpl->setVariable("TXT_SELECT_OPTION", $option_text);
            $tpl->parseCurrentBlock();
        }
        $tpl->setVariable("ID", $this->getFieldId());

        $postvar = $this->getPostVar();

        $tpl->setVariable("POST_VAR", $postvar);
        if ($this->getDisabled()) {
            $hidden = $this->getHiddenTag($postvar, $this->getValue());
            $tpl->setVariable("DISABLED", " disabled=\"disabled\"");
            $tpl->setVariable("HIDDEN_INPUT", $hidden);
        }

        $tpl->setVariable("ARIA_LABEL", ilUtil::prepareFormOutput($this->getTitle()));

       // $this->enableComboBox();

        return $tpl->get();
    }

    /**
     * Enable the combobox functions on the field
     */
    protected function enableComboBox()
    {
        global $DIC;
        $DIC->globalScreen()->layout()->meta()->addCss('./Services/FAU/Tools/GUI/js/combobox/dist/combobox.css');
        $DIC->globalScreen()->layout()->meta()->addJs('./Services/FAU/Tools/GUI/js/combobox/dist/combobox.js');
    }


    /**
     * Insert property html
     *
     * @return	int	Size
     */
    public function insert($a_tpl)
    {
        $a_tpl->setCurrentBlock("prop_generic");
        $a_tpl->setVariable("PROP_GENERIC", $this->render());
        $a_tpl->parseCurrentBlock();
    }

    /**
     * Get HTML for table filter
     */
    public function getTableFilterHTML()
    {
        $html = $this->render();
        return $html;
    }

    /**
     * Get HTML for toolbar
     */
    public function getToolbarHTML()
    {
        $html = $this->render("toolbar");
        return $html;
    }
}