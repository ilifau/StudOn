<?php
// fau: exAssTest - new class ilExAssTypeTestResultSelectInputGUI.

require_once(__DIR__ . '/class.ilExAssTypeTestResultSelectionExplorerGUI.php');

/**
 * Input for repository selection
 * NOTE: this can only be used ONCE in a property form!
 *
 * @ilCtrl_IsCalledBy ilExAssTypeTestResultSelectInputGUI: ilFormPropertyDispatchGUI
 */
class ilExAssTypeTestResultSelectInputGUI extends ilExplorerSelectInputGUI
{
    /**
     * @var ilExAssTypeTestResultSelectionExplorerGUI
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

        $this->explorer_gui = new ilExAssTypeTestResultSelectionExplorerGUI(
            array('ilpropertyformgui', 'ilformpropertydispatchgui', 'ilexasstypetestresultselectinputgui'),
            'handleExplorerCommand');

        $this->explorer_gui->setSelectMode($a_postvar.'_sel', $a_multi);
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