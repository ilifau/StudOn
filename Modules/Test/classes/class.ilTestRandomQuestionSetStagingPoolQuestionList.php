<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Handles a list of questions
 *
 * @author		Björn Heyser <bheyser@databay.de>
 * @version		$Id$
 * 
 * @package		Modules/TestQuestionPool
 * 
 */
class ilTestRandomQuestionSetStagingPoolQuestionList implements Iterator
{
	/**
	 * @var ilDB
	 */
	private $db = null;
	
	/**
	 * @var ilPluginAdmin
	 */
	private $pluginAdmin = null;

	/**
	 * @var integer
	 */
	private $testObjId = -1;

	/**
	 * @var integer
	 */
	private $testId = -1;

	/**
	 * @var integer
	 */
	private $poolId = -1;

	/**
	 * @var array
	 */
	private $taxFilters = array();

// fau: taxGroupFilter - class variables for question grouping and selection
	private $groupTaxId = null;
	/** @var array node_id => question_ids[] */
	private $groupedQuestions = array();

	private $selectSize = 0;
// fau.

// fau: typeFilter private variable
	/**
	 * @var array
	 */
	private $typeFilter = array();
// fau.

// fau: randomSetOrder -class variable for ordering the selected questions
	private	$orderBy = null;
// fau.

	/**
	 * @var array
	 */
	private $questions = array();

	/**
	 * @param ilDB $db
	 * @param ilPluginAdmin $pluginAdmin
	 */
	public function __construct(ilDB $db, ilPluginAdmin $pluginAdmin)
	{
		$this->db = $db;
		$this->pluginAdmin = $pluginAdmin;
	}

	public function setTestObjId($testObjId)
	{
		$this->testObjId = $testObjId;
	}

	public function getTestObjId()
	{
		return $this->testObjId;
	}

	public function setTestId($testId)
	{
		$this->testId = $testId;
	}

	public function getTestId()
	{
		return $this->testId;
	}

	public function setPoolId($poolId)
	{
		$this->poolId = $poolId;
	}

	public function getPoolId()
	{
		return $this->poolId;
	}

	public function addTaxonomyFilter($taxId, $taxNodes)
	{
		$this->taxFilters[$taxId] = $taxNodes;
	}

	public function getTaxonomyFilters()
	{
		return $this->taxFilters;
	}

// fau: taxGroupFilter - getter/setter
	public function getGroupTaxId()
	{
		return $this->groupTaxId;
	}

	public function setGroupTaxId($groupTaxId)
	{
		$this->groupTaxId = $groupTaxId;
	}

	public function setSelectSize($size)
	{
		$this->selectSize = $size;
	}

	public function getSelectSize()
	{
		return $this->selectSize;
	}
// fau.

// fau: typeFilter - getter/setter
	public function getTypeFilter()
	{
		return $this->typeFilter;
	}

	public function setTypeFilter($typeFilter)
	{
		$this->typeFilter = $typeFilter;
	}
// fau.

// fau: - randomSetOrder - getter/setter
	public function getOrderBy()
	{
		return $this->orderBy;
	}

	public function setOrderBy($orderBy)
	{
		$this->orderBy = $orderBy;
	}
// fau.

// fau: - randomSetOrder - sort the loaded questions
	public function loadQuestions()
	{		
		$query = "
			SELECT		{$this->getSortKey()}
						qpl_questions.question_id,
						qpl_qst_type.type_tag,
						qpl_qst_type.plugin

			FROM		tst_rnd_cpy

			INNER JOIN	qpl_questions
			ON			qpl_questions.question_id = tst_rnd_cpy.qst_fi

			INNER JOIN	qpl_qst_type
			ON			qpl_qst_type.question_type_id = qpl_questions.question_type_fi

			WHERE		tst_rnd_cpy.tst_fi = %s
			AND			tst_rnd_cpy.qpl_fi = %s

			{$this->getConditionalExpression()}
		";

		$res = $this->db->queryF(
			$query, array('integer', 'integer'), array($this->getTestId(), $this->getPoolId())
		);

		//vd($this->db->db->last_query);

		$questions = array();
		while( $row = $this->db->fetchAssoc($res) )
		{
			if( !$this->isActiveQuestionType($row) )
			{
				continue;
			}
			$questions[$row['question_id']] = $row['sort_key'];
		}

		if ($this->getOrderBy())
		{
			natsort($questions);
		}

		$this->questions = array_keys($questions);
	}
// fau.

// fau: randomSetOrder - get a sort key field
	private function getSortKey()
	{
		switch ($this->getOrderBy())
		{
			case 'title':
				return 'qpl_questions.title sort_key,';
			case 'description':
				return 'qpl_questions.description sort_key,';
			case 'random':
				return 'RAND() sort_key,';
			default:
				return "'A' AS sort_key,";
		}
	}
// fau.

	private function getConditionalExpression()
	{
		$CONDITIONS = $this->getTaxonomyFilterExpressions();
// fau: typeFilter - add the type filter expression to conditions
		$CONDITIONS = array_merge($CONDITIONS,  $this->getTypeFilterExpressions());
// fau.
		$CONDITIONS = implode(' AND ', $CONDITIONS);

		return strlen($CONDITIONS) ? 'AND '.$CONDITIONS : '';
	}

	private function getTaxonomyFilterExpressions()
	{
		$expressions = array();

		require_once 'Services/Taxonomy/classes/class.ilTaxonomyTree.php';
		require_once 'Services/Taxonomy/classes/class.ilTaxNodeAssignment.php';

		foreach($this->getTaxonomyFilters() as $taxId => $taxNodes)
		{
			$questionIds = array();

			$forceBypass = true;

			foreach($taxNodes as $taxNode)
			{
				$forceBypass = false;

				$taxTree = new ilTaxonomyTree($taxId);

				$taxNodeAssignment = new ilTaxNodeAssignment('tst', $this->getTestObjId(), 'quest', $taxId);

				$subNodes = $taxTree->getSubTreeIds($taxNode);
				$subNodes[] = $taxNode;

				$taxItems = $taxNodeAssignment->getAssignmentsOfNode($subNodes);

// fau: taxGroupFilter - collect the defined questions per group node
				if ($taxId == $this->getGroupTaxId())
				{
					$group = array();
					foreach($taxItems as $taxItem)
					{
						$group[] = $taxItem['item_id'];
					}
					$this->groupedQuestions[$taxNode] = array_unique($group);
				}
// fau.
				foreach($taxItems as $taxItem)
				{
					$questionIds[$taxItem['item_id']] = $taxItem['item_id'];
				}
			}

			if( !$forceBypass )
			{
				$expressions[] = $this->db->in('question_id', $questionIds, false, 'integer');
			}
		}

		return $expressions;
	}

// fau: typeFilter - get the expressions for a type filter
	private function getTypeFilterExpressions()
	{
		$expressions = array();
		if (!empty($this->typeFilter))
		{
			$expressions[] = $this->db->in('question_type_fi', $this->typeFilter, false, 'integer');
		}
		return $expressions;
	}
// fau;

	private function isActiveQuestionType($questionData)
	{
		if( !isset($questionData['plugin']) )
		{
			return false;
		}
		
		if( !$questionData['plugin'] )
		{
			return true;
		}
		
		return $this->pluginAdmin->isActive(IL_COMP_MODULE, 'TestQuestionPool', 'qst', $questionData['type_tag']);
	}

	public function resetQuestionList()
	{
		$this->questions = array();
		$this->taxFilters = array();

		$this->testObjId = -1;
		$this->testId = -1;
		$this->poolId = -1;

	}
	
	public function getQuestions()
	{
		return array_values($this->questions);
	}


// fau: taxGroupFilter - get selected questions
	public function getSelectedQuestions()
	{
		if (empty($this->getSelectSize()))
		{
			return array();
		}

		if ($this->getGroupTaxId())
		{
			$validGroups = array();
			foreach($this->groupedQuestions	as $taxNode => $nodeQuestions)
			{
				// filter the really found questions by the question ids of the group node
				// this keeps them in the loaded order
				$filteredQuestions = array_intersect($this->questions, $nodeQuestions);

				// collect groups that have enough questions
				if (count($filteredQuestions) >= $this->getSelectSize())
				{
					$validGroups[] = $filteredQuestions;
				}
			}

			if (count($validGroups))
			{
				// choose a question group randomly
				$groupQuestions = $validGroups[array_rand($validGroups)];

				// choose the selected amount of questions and keep their order
				$selectedKeys = array_rand($groupQuestions, $this->getSelectSize());
				$selectedQuestions = array_values(array_intersect_key($groupQuestions, array_flip($selectedKeys)));
				return $selectedQuestions;
			}
		}
		elseif (count($this->questions) >= $this->getSelectSize())
		{
			// choose the selected amount of questions and keep their order
			$selectedKeys = array_rand($this->questions, $this->getSelectSize());
			$selectedQuestions = array_values(array_intersect_key($this->questions, array_flip($selectedKeys)));
			return $selectedQuestions;
		}

		return array();
	}
// fau.

	// =================================================================================================================

	/**
	 * @return ilTestRandomQuestionSetSourcePoolDefinition
	 */
	public function rewind()
	{
		return reset($this->questions);
	}

	/**
	 * @return ilTestRandomQuestionSetSourcePoolDefinition
	 */
	public function current()
	{
		return current($this->questions);
	}

	/**
	 * @return integer
	 */
	public function key()
	{
		return key($this->questions);
	}

	/**
	 * @return ilTestRandomQuestionSetSourcePoolDefinition
	 */
	public function next()
	{
		return next($this->questions);
	}

	/**
	 * @return boolean
	 */
	public function valid()
	{
		return key($this->questions) !== null;
	}
}
