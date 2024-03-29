<?php

class fauRepositorySelectionExplorerGUI extends ilTreeExplorerGUI
{
    /**
     * @var ilSetting
     */
    protected $settings;

    /**
     * @var ilObjectDefinition
     */
    protected $obj_definition;

    /**
     * @var ilLanguage
     */
    protected $lng;

    /**
     * @var ilCtrl
     */
    protected $ctrl;

    /**
     * @var ilAccessHandler
     */
    protected $access;

    /**
     * @var ilRbacSystem
     */
    protected $rbacsystem;

    /**
     * @var ilDBInterface
     */
    protected $db;

    /**
     * Set the types that can be selected
     * @var array
     */
    protected $selectableTypes = ['cat'];

    /**
     * {@inheritdoc}
     */
    public function __construct($a_parent_obj, $a_parent_cmd)
    {
        global $DIC;

        $this->tree = $DIC->repositoryTree();
        $this->settings = $DIC->settings();
        $this->obj_definition = $DIC["objDefinition"];
        $this->lng = $DIC->language();
        $this->ctrl = $DIC->ctrl();
        $this->access = $DIC->access();
        $this->rbacsystem = $DIC->rbac()->system();
        $this->db = $DIC->database();

        $element_id = 'fau_explorer_selection';

        parent::__construct($element_id, $a_parent_obj, $a_parent_cmd, $DIC->repositoryTree());
        $this->setAjax(true);
        $this->setTypeWhiteList(array('root', 'cat', 'crs', 'grp', 'fold'));
    }

    /**
     * Set the types that can be selected
     * @param array $a_types
     */
    public function setSelectableTypes($a_types)
    {
        $this->selectableTypes = $a_types;
        $this->setTypeWhiteList(array_merge($this->getTypeWhiteList(), $a_types));
    }

    /**
     * Sort childs
     *
     * @param array $a_childs array of child nodes
     * @param mixed $a_parent_node parent node
     *
     * @return array array of childs nodes
     */
    public function sortChilds($a_childs, $a_parent_node_id)
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
    }

    /**
     * Get node content
     *
     * @param array
     * @return
     */
    public function getNodeContent($a_node)
    {
        $title = $a_node["title"];

        if ($a_node["child"] == $this->getNodeId($this->getRootNode())) {
            if ($title == "ILIAS") {
                $title = $this->lng->txt("repository");
            }
        }
        return $title;
    }

    public function getNodeHref($a_node)
    {
        return '#';
    }

    /**
     * Get node icon
     *
     * @param array
     * @return
     */
    public function getNodeIcon($a_node)
    {
        $obj_id = ilObject::_lookupObjId($a_node["child"]);
        return ilObject::_getIcon($obj_id, "tiny", $a_node["type"]);
    }


    /**
     * {@inheritdoc}
     */
    protected function isNodeSelectable($a_node)
    {
        if (!empty($this->selectableTypes)) {
            return in_array($a_node['type'], $this->selectableTypes);
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function setNodeSelected($a_id)
    {
        parent::setNodeSelected($a_id);
        $this->setPathOpen($a_id);
    }




}