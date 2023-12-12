<?php

declare(strict_types=1);

/**
 * Input for repository selection
 * Similar to implementation of ilRepositorySelector2InputGUI
 * NOTE: this can only be used ONCE in a property form!
 *
 * @ilCtrl_IsCalledBy fauRepositorySelectorInputGUI: ilFormPropertyDispatchGUI
 */
class fauRepositorySelectorInputGUI extends ilExplorerSelectInputGUI
{
    protected ?Closure $title_modifier = null;

    /**
     * @param object|null $forwarder object that forwards to ilFormPropertyDispatchGUI, usually a form object
     *                               but might be a table as well, e.g. if inputs are used in filter
     */
    public function __construct(
        string $a_title,
        string $a_postvar,
        bool $a_multi = false,
        ?object $forwarder = null
    ) {
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
        $this->getExplorerGUI()->setSelectMode($a_postvar . "_sel", $this->multi_nodes);

        parent::__construct($a_title, $a_postvar, $this->explorer_gui, $this->multi_nodes);
        $this->setType("rep_select");
    }

    public function setTitleModifier(?Closure $a_val): void
    {
        $this->title_modifier = $a_val;
        if ($a_val != null) {
            $this->getExplorerGUI()->setNodeContentModifier(function ($a_node) use ($a_val) {
                return $a_val($a_node["child"]);
            });
        } else {
            $this->getExplorerGUI()->setNodeContentModifier(null);
        }
    }

    public function getTitleModifier(): ?Closure
    {
        return $this->title_modifier;
    }

    public function getTitleForNodeId($a_id): string
    {
        $c = $this->getTitleModifier();
        if (is_callable($c)) {
            return $c($a_id);
        }
        return ilObject::_lookupTitle(ilObject::_lookupObjId((int) $a_id));
    }

    public function getExplorerGUI(): fauRepositorySelectionExplorerGUI
    {
        return $this->explorer_gui;
    }

    public function setExplorerGUI(\fauRepositorySelectionExplorerGUI $explorer): void
    {
        $this->explorer_gui = $explorer;
    }

    public function getOnloadCode(): array
    {
        return [
            "il.Explorer2.initSelect('" . $this->getFieldId() . "');"
        ];
    }

    public function getHTML(): string
    {
        $ilCtrl = $this->ctrl;
        $ilCtrl->setParameterByClass("ilformpropertydispatchgui", "postvar", $this->postvar);
        $html = parent::render();
        $ilCtrl->setParameterByClass("ilformpropertydispatchgui", "postvar", $this->str("postvar"));
        return $html;
    }

    public function render(string $a_mode = "property_form"): string
    {
        $ilCtrl = $this->ctrl;
        $ilCtrl->setParameterByClass("ilformpropertydispatchgui", "postvar", $this->postvar);
        $ret = parent::render($a_mode);
        $ilCtrl->setParameterByClass("ilformpropertydispatchgui", "postvar", $this->str("postvar"));
        return $ret;
    }
}
