<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * @author		Björn Heyser <bheyser@databay.de>
 * @version		$Id$
 *
 * @package     Modules/Test
 */
class ilTestTaxonomyFilterLabelTranslater
{
    // TODO-RND2017: rename class to ilTest>Question<FilterLabelTranslator

    /**
     * @var ilDBInterface
     */
    private $db = null;

    private $taxonomyTreeIds = null;
    private $taxonomyNodeIds = null;

    private $taxonomyTreeLabels = null;
    private $taxonomyNodeLabels = null;

    // fau: taxDesc - class variable for node descriptions
    private $taxonomyNodeDescriptions = null;
    // fau.

    // fau: taxDesc - class variable for parent relation
    private $taxononyTreeParentIds = null;
    // fau.

    // fau: taxFilter/typeFilter - class variable
    private $typeLabels = null;
    // fau.

    /**
     * @param ilDBInterface $db
     */
    public function __construct(ilDBInterface $db)
    {
        $this->db = $db;

        $this->taxonomyTreeIds = array();
        $this->taxonomyNodeIds = array();

        $this->taxonomyTreeLabels = array();
        $this->taxonomyNodeLabels = array();

        // fau: taxDesc - init node descriptions and parents
        $this->taxonomyNodeDescriptions = array();
        $this->taxononyTreeParentIds = array();
        // fau.

        // fau: taxFilter/typeFilter - init node descriptions
        $this->loadTypeLabels();
        // fau.
    }

    public function loadLabels(ilTestRandomQuestionSetSourcePoolDefinitionList $sourcePoolDefinitionList)
    {
        $this->collectIds($sourcePoolDefinitionList);

        $this->loadTaxonomyTreeLabels();
        $this->loadTaxonomyNodeLabels();
    }

    private function collectIds(ilTestRandomQuestionSetSourcePoolDefinitionList $sourcePoolDefinitionList)
    {
        foreach ($sourcePoolDefinitionList as $definition) {
            /** @var ilTestRandomQuestionSetSourcePoolDefinition $definition */

            // fau: taxFilter/typeFilter - get ids from new taxonomy filter

            // original filter will be shown before synchronisation
            foreach ($definition->getOriginalTaxonomyFilter() as $taxId => $nodeIds) {
                $this->taxonomyTreeIds[] = $taxId;
                foreach ($nodeIds as $nodeId) {
                    $this->taxonomyNodeIds[] = $nodeId;
                }
            }

            // mapped filter will be shown after synchronisation
            foreach ($definition->getMappedTaxonomyFilter() as $taxId => $nodeIds) {
                $this->taxonomyTreeIds[] = $taxId;
                foreach ($nodeIds as $nodeId) {
                    $this->taxonomyNodeIds[] = $nodeId;
                }
            }

            #$this->taxonomyTreeIds[] = $definition->getMappedFilterTaxId();
            #$this->taxonomyNodeIds[] = $definition->getMappedFilterTaxNodeId();
            // fau.
        }
    }

    private function loadTaxonomyTreeLabels()
    {
        $IN_taxIds = $this->db->in('obj_id', $this->taxonomyTreeIds, false, 'integer');

        $query = "
			SELECT		obj_id tax_tree_id,
						title tax_tree_title

			FROM		object_data

			WHERE		$IN_taxIds
			AND			type = %s
		";

        $res = $this->db->queryF($query, array('text'), array('tax'));

        while ($row = $this->db->fetchAssoc($res)) {
            $this->taxonomyTreeLabels[ $row['tax_tree_id'] ] = $row['tax_tree_title'];
        }
    }

    private function loadTaxonomyNodeLabels()
    {
        // fau: taxDesc - load tax node descriptions and parents for full taxonomies

        $IN_tree_ids = $this->db->in('tax_tree.tax_tree_id', array_unique($this->taxonomyTreeIds), false, 'integer');
        $IN_nodeIds = $this->db->in('tax_node.obj_id', $this->taxonomyNodeIds, false, 'integer');

        $query = "
					SELECT		tax_node.obj_id tax_node_id,
								tax_node.title tax_node_title,
								tax_node.description tax_node_description,
								tax_tree.parent tax_tree_parent

					FROM		tax_node
					INNER JOIN  tax_tree ON (tax_tree.tax_tree_id = tax_node.tax_id AND tax_tree.child = tax_node.obj_id)

					WHERE		$IN_tree_ids
				";

        $res = $this->db->query($query);

        while ($row = $this->db->fetchAssoc($res)) {
            $this->taxonomyNodeLabels[ $row['tax_node_id'] ] = $row['tax_node_title'];
            $this->taxonomyNodeDescription[ $row['tax_node_id'] ] = $row['tax_node_description'];
            $this->taxononyTreeParentIds[ $row['tax_node_id'] ] = $row['tax_tree_parent'];
        }
        // fau.
    }

    // fau: taxFilter/typeFilter - load type labels
    private function loadTypeLabels()
    {
        $this->typeLabels = array();

        require_once("./Modules/TestQuestionPool/classes/class.ilObjQuestionPool.php");
        foreach (ilObjQuestionPool::_getQuestionTypes(true) as $translation => $data) {
            $this->typeLabels[$data['question_type_id']] = $translation;
        }
    }
    // fau.

    public function getTaxonomyTreeLabel($taxonomyTreeId)
    {
        return $this->taxonomyTreeLabels[$taxonomyTreeId];
    }

    public function getTaxonomyNodeLabel($taxonomyTreeId)
    {
        // fau: taxDesc - get the path as taxonomy label
        $nodeId = $taxonomyTreeId;
        $path = [];
        while ($parentId = $this->taxononyTreeParentIds[$nodeId]) {
            $path[] = $this->taxonomyNodeLabels[$nodeId];
            $nodeId = $parentId;
        }
        return implode(' / ', array_reverse($path));
        // fau.
    }

    // fau: taxDesc - get node description
    public function getTaxonomyNodeDescription($taxonomyTreeId)
    {
        return $this->taxonomyNodeDescription[$taxonomyTreeId];
    }
    // fim.

    public function loadLabelsFromTaxonomyIds($taxonomyIds)
    {
        $this->taxonomyTreeIds = $taxonomyIds;

        $this->loadTaxonomyTreeLabels();
    }

    // fau: taxFilter/typeFilter - get a labels for filters
    /**
     * Get the label for a taxonomy filter
     * @param array 	taxId => [nodeId, ...]
     * @param string	delimiter for separate taxonomy conditions
     * @param string	delimiter between taxonomy name and node list
     * @param string	delimiter between nodes in the node list
     */
    public function getTaxonomyFilterLabel($filter = array(), $filterDelimiter = ' + ', $taxNodeDelimiter = ': ', $nodesDelimiter = ', ')
    {
        $labels = array();
        foreach ($filter as $taxId => $nodeIds) {
            $nodes = array();
            foreach ($nodeIds as $nodeId) {
                // fau: taxDesc - add taxonomy  description tooltip
                $description = $this->getTaxonomyNodeDescription($nodeId);

                if (!empty($description)) {
                    require_once("Services/UIComponent/Tooltip/classes/class.ilTooltipGUI.php");
                    ilTooltipGUI::addTooltip('ilTaxonomyNode' . $nodeId, $description);

                    $nodes[] = '<span id="ilTaxonomyNode' . $nodeId . '">' . $this->getTaxonomyNodeLabel($nodeId)
                        . ' <small><span class="glyphicon glyphicon-info-sign"></span></small></span>';
                } else {
                    $nodes[] = $this->getTaxonomyNodeLabel($nodeId);
                }
            }
            // fau.
            $labels[] .= $this->getTaxonomyTreeLabel($taxId) . $taxNodeDelimiter . implode($nodesDelimiter, $nodes);
        }
        return implode($filterDelimiter, $labels);
    }
    
    /**
     * Get the label for a lifecycle filter
     * @param array $filter	list of lifecycle identifiers
     */
    public function getLifecycleFilterLabel($filter = array())
    {
        global $DIC; /* @var ILIAS\DI\Container $DIC */
        
        $lifecycles = array();
        
        $lifecycleTranslations = ilAssQuestionLifecycle::getDraftInstance()->getSelectOptions($DIC->language());
        
        foreach ($filter as $lifecycle) {
            $lifecycles[] = $lifecycleTranslations[$lifecycle];
        }
        asort($lifecycles);
        return implode(', ', $lifecycles);
    }

    /**
     * Get the label for a type filter
     * @param array $filter	list of type ids
     */
    public function getTypeFilterLabel($filter = array())
    {
        $types = array();

        foreach ($filter as $type_id) {
            $types[] = $this->typeLabels[$type_id];
        }
        asort($types);
        return implode(', ', $types);
    }
    // fau.

    // fau: taxGroupFilter - new function to get a label for group selection in taxonomy
    public function getGroupTaxonomyLabel($taxId)
    {
        global $lng;

        return $lng->txt('tst_group_filter') . ': ' . $this->getTaxonomyTreeLabel($taxId);
    }
    // fau.
}
