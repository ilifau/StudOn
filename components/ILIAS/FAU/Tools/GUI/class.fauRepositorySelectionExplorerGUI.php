<?php

class fauRepositorySelectionExplorerGUI extends ilRepositorySelectorExplorerGUI
{
    protected ilSetting $settings;
    protected ilCtrl $ctrl;
    protected ilRbacSystem $rbacsystem;
    protected ilDBInterface $db;
    
    /**
     * {@inheritdoc}
     */
    public function __construct(
        $a_parent_obj,
        string $a_parent_cmd,
        $a_selection_gui = null,
        string $a_selection_cmd = "selectObject",
        string $a_selection_par = "sel_ref_id",
        string $a_id = "fau_explorer_selection",
        string $a_node_parameter_name = "node_id"
    )
    {        
        /** @var \ILIAS\DI\Container $DIC */
        global $DIC;
        /**
        * Set the types that can be selected
        */
        $this->selectable_types = ['cat'];
        $this->settings = $DIC->settings();
        $this->ctrl = $DIC->ctrl();
        $this->rbacsystem = $DIC->rbac()->system();
        $this->db = $DIC->database();
        $this->setTypeWhiteList(array('root', 'cat', 'crs', 'grp', 'fold'));
        parent::__construct($a_parent_obj, $a_parent_cmd, $a_selection_gui, $a_selection_cmd, $a_selection_par, $a_id, $a_node_parameter_name);
    }

    /**
     * Sort childs
     *
     * @param array $a_childs array of child nodes
     * @param mixed $a_parent_node parent node
     *
     * @return array array of childs nodes
     */
    /*
    public function sortChilds(array $a_childs, $a_parent_node_id): array
    {
        $objDefinition = $this->obj_definition;
        $ilAccess = $this->access;

        $parent_obj_id = ilObject::_lookupObjId($a_parent_node_id);
        if ($parent_obj_id > 0) {
            $parent_type = ilObject::_lookupType($parent_obj_id);
        } else {
            $parent_type = "dummy";
            $this->type_grps["dummy"] = array("root" => "dummy");
        }

        // alex: if this is not initialized, things are messed up
        // see bug 0015978
        $this->type_grps = array();

        if (empty($this->type_grps[$parent_type])) {
            $this->type_grps[$parent_type] =
                $objDefinition->getGroupedRepositoryObjectTypes($parent_type);
        }

        // #14465 - item groups
        include_once('./Services/Object/classes/class.ilObjectActivation.php');
        $group = array();
        $igroup = array(); // used for item groups, see bug #0015978
        $in_any_group = array();
        foreach ($a_childs as $child) {
            // item group: get childs
            if ($child["type"] == "itgr") {
                $g = $child["child"];
                $items = ilObjectActivation::getItemsByItemGroup($g);
                if ($items) {
                    // add item group ref id to item group block
                    $this->type_grps[$parent_type]["itgr"]["ref_ids"][] = $g;

                    // #16697 - check item group permissions
                    $may_read = $ilAccess->checkAccess('read', '', $g);

                    // see bug #0015978
                    if ($may_read) {
                        include_once("./Services/Container/classes/class.ilContainerSorting.php");
                        $items = ilContainerSorting::_getInstance($parent_obj_id)->sortSubItems('itgr', $child["obj_id"], $items);
                    }

                    foreach ($items as $item) {
                        $in_any_group[] = $item["child"];

                        if ($may_read) {
                            $igroup[$g][] = $item;
                            $group[$g][] = $item;
                        }
                    }
                }
            }
            // type group
            else {
                $g = $objDefinition->getGroupOfObj($child["type"]);
                if ($g == "") {
                    $g = $child["type"];
                }
                $group[$g][] = $child;
            }
        }

        $in_any_group = array_unique($in_any_group);

        // custom block sorting?
        include_once("./Services/Container/classes/class.ilContainerSorting.php");
        $sort = ilContainerSorting::_getInstance($parent_obj_id);
        $block_pos = $sort->getBlockPositions();
        if (is_array($block_pos) && count($block_pos) > 0) {
            $tmp = $this->type_grps[$parent_type];

            $this->type_grps[$parent_type] = array();
            foreach ($block_pos as $block_type) {
                // type group
                if (!is_numeric($block_type) &&
                    array_key_exists($block_type, $tmp)) {
                    $this->type_grps[$parent_type][$block_type] = $tmp[$block_type];
                    unset($tmp[$block_type]);
                }
                // item group
                else {
                    // using item group ref id directly
                    $this->type_grps[$parent_type][$block_type] = array();
                }
            }

            // append missing
            if (sizeof($tmp)) {
                foreach ($tmp as $block_type => $grp) {
                    $this->type_grps[$parent_type][$block_type] = $grp;
                }
            }

            unset($tmp);
        }

        $childs = array();
        $done = array();

        foreach ($this->type_grps[$parent_type] as $t => $g) {
            // type group
            if (is_array($group[$t])) {
                // see bug #0015978
                // custom sorted igroups
                if (is_array($igroup[$t])) {
                    foreach ($igroup[$t] as $k => $item) {
                        if (!in_array($item["child"], $done)) {
                            $childs[] = $item;
                            $done[] = $item["child"];
                        }
                    }
                } else {
                    // do we have to sort this group??
                    include_once("./Services/Container/classes/class.ilContainer.php");
                    include_once("./Services/Container/classes/class.ilContainerSorting.php");
                    $sort = ilContainerSorting::_getInstance($parent_obj_id);
                    $group = $sort->sortItems($group);

                    // need extra session sorting here
                    if ($t == "sess") {
                    }

                    foreach ($group[$t] as $k => $item) {
                        if (!in_array($item["child"], $done) &&
                            !in_array($item["child"], $in_any_group)) { // #16697
                            $childs[] = $item;
                            $done[] = $item["child"];
                        }
                    }
                }
            }
            // item groups (if not custom block sorting)
            elseif ($t == "itgr" &&
                is_array($g["ref_ids"])) {
                foreach ($g["ref_ids"] as $ref_id) {
                    if (isset($group[$ref_id])) {
                        foreach ($group[$ref_id] as $k => $item) {
                            if (!in_array($item["child"], $done)) {
                                $childs[] = $item;
                                $done[] = $item["child"];
                            }
                        }
                    }
                }
            }
        }

        return $childs;
    }*/
   

    public function sortChilds(array $a_childs, $a_parent_node_id): array
    {
        $objDefinition = $this->obj_definition;

        $parent_obj_id = ilObject::_lookupObjId((int) $a_parent_node_id);

        if ($parent_obj_id > 0) {
            $parent_type = ilObject::_lookupType($parent_obj_id);
        } else {
            $parent_type = "dummy";
            $this->type_grps["dummy"] = ["root" => "dummy"];
        }

        if (empty($this->type_grps[$parent_type])) {
            $this->type_grps[$parent_type] =
                $objDefinition::getGroupedRepositoryObjectTypes($parent_type);
        }
        $group = [];

        foreach ($a_childs as $child) {
            $g = $objDefinition->getGroupOfObj($child["type"]);
            if ($g == "") {
                $g = $child["type"];
            }
            $group[$g][] = $child;
        }

        // #14587 - $objDefinition->getGroupedRepositoryObjectTypes does NOT include side blocks!
        $wl = $this->getTypeWhiteList();
        if (is_array($wl) && in_array("poll", $wl, true)) {
            $this->type_grps[$parent_type]["poll"] = [];
        }

        $childs = [];
        foreach ($this->type_grps[$parent_type] as $t => $g) {
            if (isset($group[$t])) {
                // do we have to sort this group??
                $sort = ilContainerSorting::_getInstance($parent_obj_id);
                $group = $sort->sortItems($group);

                foreach ($group[$t] as $k => $item) {
                    $childs[] = $item;
                }
            }
        }

        return $childs;
    }   
}