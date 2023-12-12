<?php

/**
 * Input for repository selection
 * NOTE: this can only be used ONCE in a property form!
 *
 * @ilCtrl_IsCalledBy fauRepositorySelectorInputGUI: ilFormPropertyDispatchGUI
 */
class fauRepositorySelectorInputGUI extends ilExplorerSelectInputGUI
{
    /**
     * @param object|null $forwarder object that forwards to ilFormPropertyDispatchGUI, usually a form object
     *                               but might be a table as well, e.g. if inputs are used in filter
     */
    public function __construct(
        string $a_title,
        string $a_postvar,
        bool $a_multi = false,
        ?object $forwarder = null
    ) 
    {
        global $DIC;

        $this->ctrl = $DIC->ctrl();
        $this->multi_nodes = $a_multi;
        $this->postvar = $a_postvar;       
        
        $forwarder_class = (is_null($forwarder))
        ? ilPropertyFormGUI::class
        : get_class($forwarder);

        $this->explorer_gui = new fauRepositorySelectionExplorerGUI(
            [$forwarder_class, ilFormPropertyDispatchGUI::class, fauRepositorySelectorInputGUI::class],
            $this->getExplHandleCmd(),
            $this,
            "selectRepositoryItem",
            "root_id",
            "rep_exp_sel_" . $a_postvar
        );

        $this->explorer_gui->setSelectMode($a_postvar . "_sel", $this->multi_nodes);

        parent::__construct($a_title, $a_postvar, $this->explorer_gui, $this->multi_nodes);
        $this->setType("rep_select");
    }

    /**
     * {@inheritdoc}
     */
    public function getTitleForNodeId($a_id): string
    {
        return ilObject::_lookupTitle(ilObject::_lookupObjId($a_id));
    }
}