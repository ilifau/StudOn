<?php

/**
 * Input for repository selection
 * NOTE: this can only be used ONCE in a property form!
 *
 * @ilCtrl_IsCalledBy fauRepositorySelectInputGUI: ilFormPropertyDispatchGUI
 */
class fauRepositorySelectorInputGUI extends ilExplorerSelectInputGUI
{
    /**
     * @var fauRepositorySelectionExplorerGUI
     */
    protected $explorer_gui;

    /**
     * {@inheritdoc}
     */
    public function __construct($title, $a_postvar, $a_explorer_gui = null, $a_multi = false)
    {
        global $DIC;
        $DIC->ctrl()->setParameterByClass('ilformpropertydispatchgui', 'postvar', $a_postvar);

        ilOverlayGUI::initJavascript();

        $this->explorer_gui = new fauRepositorySelectionExplorerGUI(
            array('ilpropertyformgui', 'ilformpropertydispatchgui', 'fauRepositorySelectorInputGUI'),
            'handleExplorerCommand');

        $this->explorer_gui->setSelectMode($a_postvar.'_sel', $a_multi);

        parent::__construct($title, $a_postvar, $this->explorer_gui, $a_multi);
        $this->setType('repository_select');
    }

    /**
     * Set the types that can be selected
     * @param array $a_types
     */
    public function setSelectableTypes($a_types)
    {
        $this->explorer_gui->setSelectableTypes($a_types);
    }


    /**
     * {@inheritdoc}
     */
    public function getTitleForNodeId($a_id)
    {
        return ilObject::_lookupTitle(ilObject::_lookupObjId($a_id));
    }
}