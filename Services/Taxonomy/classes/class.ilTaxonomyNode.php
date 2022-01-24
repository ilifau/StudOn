<?php

/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * Taxonomy node
 *
 * @author Alex Killing <alex.killing@gmx.de>
 * @version $Id$
 *
 * @ingroup ServicesTaxonomy
 */
class ilTaxonomyNode
{
    /**
     * @var ilDB
     */
    protected $db;

    public $type;
    public $id;
    public $title;
    // fau: taxDesc - class variable
    public $description;
    // fau.

    /**
     * Constructor
     * @access	public
     */
    public function __construct($a_id = 0)
    {
        global $DIC;

        $this->db = $DIC->database();
        $this->id = $a_id;
        
        //		include_once("./Services/Taxonomy/classes/class.ilTaxonomyTree.php");
        //		$this->taxonomy_tree = new ilTaxonomyTree();

        if ($a_id != 0) {
            $this->read();
        }

        $this->setType("taxn");
    }

    /**
     * Set title
     *
     * @param	string		$a_title	title
     */
    public function setTitle($a_title)
    {
        $this->title = $a_title;
    }

    /**
     * Get title
     *
     * @return	string		title
     */
    public function getTitle()
    {
        return $this->title;
    }

    // fau: taxDesc - set/get description
    /**
     * Set description
     *
     * @param	string		$a_description	description
     */
    public function setDescription($a_description)
    {
        $this->description = $a_description;
    }

    /**
     * Get description
     *
     * @return	string		description
     */
    public function getDescription()
    {
        return $this->description;
    }
    // fau.

    /**
     * Set type
     *
     * @param	string		Type
     */
    public function setType($a_type)
    {
        $this->type = $a_type;
    }

    /**
     * Get type
     *
     * @return	string		Type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set Node ID
     *
     * @param	int		Node ID
     */
    public function setId($a_id)
    {
        $this->id = $a_id;
    }

    /**
     * Get Node ID
     *
     * @param	int		Node ID
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set order nr
     *
     * @param int $a_val order nr
     */
    public function setOrderNr($a_val)
    {
        $this->order_nr = $a_val;
    }
    
    /**
     * Get order nr
     *
     * @return int order nr
     */
    public function getOrderNr()
    {
        return $this->order_nr;
    }

    /**
     * Set taxonomy id
     *
     * @param int $a_val taxonomy id
     */
    public function setTaxonomyId($a_val)
    {
        $this->taxonomy_id = $a_val;
    }
    
    /**
     * Get taxonomy id
     *
     * @return int taxonomy id
     */
    public function getTaxonomyId()
    {
        return $this->taxonomy_id;
    }
    
    /**
     * Read data from database
     */
    public function read()
    {
        $ilDB = $this->db;

        if (!isset($this->data_record)) {
            $query = "SELECT * FROM tax_node WHERE obj_id = " .
                $ilDB->quote($this->id, "integer");
            $obj_set = $ilDB->query($query);
            $this->data_record = $ilDB->fetchAssoc($obj_set);
        }
        $this->setType($this->data_record["type"]);
        $this->setTitle($this->data_record["title"]);
        // fau: taxDesc - read description
        $this->setDescription($this->data_record["description"]);
        // fau.
        $this->setOrderNr($this->data_record["order_nr"]);
        $this->setTaxonomyId($this->data_record["tax_id"]);
    }

    /**
     * Create taxonomy node
     */
    public function create()
    {
        $ilDB = $this->db;
        
        if ($this->getTaxonomyId() <= 0) {
            die("ilTaxonomyNode->create: No taxonomy ID given");
        }

        // insert object data
        $id = $ilDB->nextId("tax_node");
        // fau: taxDesc - create record with description
        $query = "INSERT INTO tax_node (obj_id, title, description, type, create_date, order_nr, tax_id) " .
            "VALUES (" .
            $ilDB->quote($id, "integer") . "," .
            $ilDB->quote($this->getTitle(), "text") . "," .
            $ilDB->quote($this->getDescription(), "text") . "," .
            $ilDB->quote($this->getType(), "text") . ", " .
            $ilDB->now() . ", " .
            $ilDB->quote((int) $this->getOrderNr(), "integer") . ", " .
            $ilDB->quote((int) $this->getTaxonomyId(), "integer") .
            ")";
        // fau.
        $ilDB->manipulate($query);
        $this->setId($id);
    }

    /**
     * Update Node
     */
    public function update()
    {
        $ilDB = $this->db;

        $query = "UPDATE tax_node SET " .
            " title = " . $ilDB->quote($this->getTitle(), "text") .
// fau: taxDesc - update record with description
            " description = " . $ilDB->quote($this->getDescription(), "text") .
// fau.
            " ,order_nr = " . $ilDB->quote((int) $this->getOrderNr(), "integer") .
            " WHERE obj_id = " . $ilDB->quote($this->getId(), "integer");

        $ilDB->manipulate($query);
    }

    /**
     * Delete taxonomy node
     */
    public function delete()
    {
        $ilDB = $this->db;
        
        // delete all assignments of the node
        include_once("./Services/Taxonomy/classes/class.ilTaxNodeAssignment.php");
        ilTaxNodeAssignment::deleteAllAssignmentsOfNode($this->getId());
        
        $query = "DELETE FROM tax_node WHERE obj_id= " .
            $ilDB->quote($this->getId(), "integer");
        $ilDB->manipulate($query);
    }

    /**
     * Copy taxonomy node
     */
    public function copy($a_tax_id = 0)
    {
        $taxn = new ilTaxonomyNode();
        $taxn->setTitle($this->getTitle());
        // fau: taxDesc - copy description
        $taxn->setDescription($this->getDescription());
        // fau.
        $taxn->setType($this->getType());
        $taxn->setOrderNr($this->getOrderNr());
        if ($a_tax_id == 0) {
            $taxn->setTaxonomyId($this->getTaxonomyId());
        } else {
            $taxn->setTaxonomyId($a_tax_id);
        }

        $taxn->create();

        return $taxn;
    }

    /**
     * Lookup
     *
     * @param	int			Node ID
     */
    protected static function _lookup($a_obj_id, $a_field)
    {
        global $DIC;

        $ilDB = $DIC->database();

        $query = "SELECT $a_field FROM tax_node WHERE obj_id = " .
            $ilDB->quote($a_obj_id, "integer");
        $obj_set = $ilDB->query($query);
        $obj_rec = $ilDB->fetchAssoc($obj_set);

        return $obj_rec[$a_field];
    }

    /**
     * Lookup Title
     *
     * @param	int			node ID
     * @return	string		title
     */
    public static function _lookupTitle($a_obj_id)
    {
        global $DIC;

        $ilDB = $DIC->database();

        return self::_lookup($a_obj_id, "title");
    }

    // fau: taxDesc - function to lookup description
    /**
     * Lookup Description
     *
     * @param	int			node ID
     * @return	string		description
     */
    public static function _lookupDescription($a_obj_id)
    {
        global $ilDB;

        return self::_lookup($a_obj_id, "description");
    }
    // fau.

    /**
     * Put this node into the taxonomy tree
     */
    public static function putInTree(
        $a_tax_id,
        $a_node,
        $a_parent_id = "",
        $a_target_node_id = "",
        $a_order_nr = 0
    ) {
        include_once("./Services/Taxonomy/classes/class.ilTaxonomyTree.php");
        $tax_tree = new ilTaxonomyTree($a_tax_id);

        // determine parent
        $parent_id = ($a_parent_id != "")
            ? $a_parent_id
            : $tax_tree->readRootId();

        // determine target
        if ($a_target_node_id != "") {
            $target = $a_target_node_id;
        } else {
            // determine last child that serves as predecessor
            $childs = $tax_tree->getChilds($parent_id);

            if (count($childs) == 0) {
                $target = IL_FIRST_NODE;
            } else {
                $target = $childs[count($childs) - 1]["obj_id"];
            }
        }

        if ($tax_tree->isInTree($parent_id) && !$tax_tree->isInTree($a_node->getId())) {
            $tax_tree->insertNode($a_node->getId(), $parent_id, $target);
        }
    }

    /**
     * Write order nr
     *
     * @param
     * @return
     */
    public static function writeOrderNr($a_node_id, $a_order_nr)
    {
        global $DIC;

        $ilDB = $DIC->database();
        
        $ilDB->manipulate(
            "UPDATE tax_node SET " .
            " order_nr = " . $ilDB->quote($a_order_nr, "integer") .
            " WHERE obj_id = " . $ilDB->quote($a_node_id, "integer")
        );
    }
    
    /**
     * Write title
     *
     * @param
     * @return
     */
    public static function writeTitle($a_node_id, $a_title)
    {
        global $DIC;

        $ilDB = $DIC->database();
        $ilDB->manipulate(
            "UPDATE tax_node SET " .
            " title = " . $ilDB->quote($a_title, "text") .
            " WHERE obj_id = " . $ilDB->quote($a_node_id, "integer")
        );
    }

    // fau: taxDesc - statically write the description
    /**
     * Write description
     *
     * @param
     * @return
     */
    public static function writeDescription($a_node_id, $a_description)
    {
        global $ilDB;

        $ilDB->manipulate(
            "UPDATE tax_node SET " .
            " description = " . $ilDB->quote($a_description, "text") .
            " WHERE obj_id = " . $ilDB->quote($a_node_id, "integer")
        );
    }
    // fau.

    /**
     * Put this node into the taxonomy tree
     */
    public static function getNextOrderNr($a_tax_id, $a_parent_id)
    {
        include_once("./Services/Taxonomy/classes/class.ilTaxonomyTree.php");
        $tax_tree = new ilTaxonomyTree($a_tax_id);
        if ($a_parent_id == 0) {
            $a_parent_id = $tax_tree->readRootId();
        }
        $childs = $tax_tree->getChilds($a_parent_id);
        $max = 0;

        foreach ($childs as $c) {
            if ($c["order_nr"] > $max) {
                $max = $c["order_nr"] + 10;
            }
        }

        return $max;
    }
    
    /**
     * Fix order numbers
     *
     * @param
     * @return
     */
    public static function fixOrderNumbers($a_tax_id, $a_parent_id)
    {
        include_once("./Services/Taxonomy/classes/class.ilTaxonomyTree.php");
        $tax_tree = new ilTaxonomyTree($a_tax_id);
        if ($a_parent_id == 0) {
            $a_parent_id = $tax_tree->readRootId();
        }
        $childs = $tax_tree->getChilds($a_parent_id);
        $childs = ilUtil::sortArray($childs, "order_nr", "asc", true);

        $cnt = 10;
        foreach ($childs as $c) {
            self::writeOrderNr($c["child"], $cnt);
            $cnt += 10;
        }
    }
}
