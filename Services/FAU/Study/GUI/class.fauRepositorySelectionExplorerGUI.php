<?php

class fauRepositorySelectionExplorerGUI extends ilTreeExplorerGUI
{
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
        $this->setTypeWhiteList(array_merge(array('root', 'cat', 'crs', 'grp', 'fold'), $a_types));
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