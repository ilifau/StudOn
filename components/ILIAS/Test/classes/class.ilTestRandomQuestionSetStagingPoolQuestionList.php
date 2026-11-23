<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

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
    private ilDBInterface $db;
    private ilComponentRepository $component_repository;

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
    private $taxFilters = [];

    // fau: taxFilter/typeFilter - private variable
    // TODO-RND2017: rename to typesFilter (multiple types allowed)
    /**
     * @var array
     */
    private $typeFilter = [];
    // fau.

    /**
     * @var array
     */
    private $lifecycleFilter = [];

    /**
     * @var array
     */
    private $questions = [];

    // fau: taxGroupFilter - question grouping and selection
    private ?int $group_tax_id = null;
    /** @var array<int, int[]> node_id => question_ids */
    private array $grouped_questions = [];
    private int $select_size = 0;
    // fau.

    // fau: randomSetOrder - order of the loaded questions
    private ?string $order_by = null;
    // fau.

    public function __construct(ilDBInterface $db, ilComponentRepository $component_repository)
    {
        $this->db = $db;
        $this->component_repository = $component_repository;
    }

    public function setTestObjId($testObjId)
    {
        $this->testObjId = $testObjId;
    }

    public function getTestObjId(): int
    {
        return $this->testObjId;
    }

    public function setTestId($testId)
    {
        $this->testId = $testId;
    }

    public function getTestId(): int
    {
        return $this->testId;
    }

    public function setPoolId($poolId)
    {
        $this->poolId = $poolId;
    }

    public function getPoolId(): int
    {
        return $this->poolId;
    }

    public function addTaxonomyFilter($taxId, $taxNodes)
    {
        $this->taxFilters[$taxId] = $taxNodes;
    }

    public function getTaxonomyFilters(): array
    {
        return $this->taxFilters;
    }

    // fau: taxFilter/typeFilter - getter/setter
    public function getTypeFilter()
    {
        return $this->typeFilter;
    }

    public function setTypeFilter($typeFilter)
    {
        $this->typeFilter = $typeFilter;
    }
    // fau.

    /**
     * @return array
     */
    public function getLifecycleFilter(): array
    {
        return $this->lifecycleFilter;
    }

    /**
     * @param array $lifecycleFilter
     */
    public function setLifecycleFilter(array $lifecycleFilter)
    {
        $this->lifecycleFilter = $lifecycleFilter;
    }

    // fau: taxGroupFilter - getters and setters
    public function getGroupTaxId(): ?int
    {
        return $this->group_tax_id;
    }

    public function setGroupTaxId(?int $group_tax_id): void
    {
        $this->group_tax_id = $group_tax_id;
    }

    public function getSelectSize(): int
    {
        return $this->select_size;
    }

    public function setSelectSize(int $select_size): void
    {
        $this->select_size = $select_size;
    }
    // fau.

    // fau: randomSetOrder - getter, setter and sort key
    public function getOrderBy(): ?string
    {
        return $this->order_by;
    }

    public function setOrderBy(?string $order_by): void
    {
        $this->order_by = $order_by;
    }

    private function getSortKey(): string
    {
        return match ($this->getOrderBy()) {
            'title' => 'qpl_questions.title sort_key,',
            'description' => 'qpl_questions.description sort_key,',
            'random' => 'RAND() sort_key,',
            default => "'A' sort_key,"
        };
    }
    // fau.

    public function loadQuestions()
    {
        // fau: randomSetOrder - sort the loaded questions
        $query = "
			SELECT		{$this->getSortKey()}
						qpl_questions.question_id,
						qpl_qst_type.type_tag,
						qpl_qst_type.plugin,
						qpl_qst_type.plugin_name

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
            $query,
            ['integer', 'integer'],
            [$this->getTestId(), $this->getPoolId()]
        );

        //echo sprintf($query, $this->getTestId(), $this->getPoolId());exit;

        $sort_keys = [];
        while ($row = $this->db->fetchAssoc($res)) {
            $row = ilAssQuestionType::completeMissingPluginName($row);

            if (!$this->isActiveQuestionType($row)) {
                continue;
            }

            $sort_keys[(int) $row['question_id']] = (string) $row['sort_key'];
        }
        if ($this->getOrderBy()) {
            natcasesort($sort_keys);
        }
        $this->questions = array_keys($sort_keys);
        // fau.
    }

    private function getConditionalExpression(): string
    {
        $CONDITIONS = $this->getTaxonomyFilterExpressions();

        // fau: taxFilter/typeFilter - add the type filter expression to conditions
        $CONDITIONS = array_merge($CONDITIONS, $this->getTypeFilterExpressions());
        // fau.

        $CONDITIONS = array_merge($CONDITIONS, $this->getLifecycleFilterExpressions());

        $CONDITIONS = implode(' AND ', $CONDITIONS);

        return strlen($CONDITIONS) ? 'AND ' . $CONDITIONS : '';
    }

    private function getTaxonomyFilterExpressions(): array
    {
        $expressions = [];

        foreach ($this->getTaxonomyFilters() as $taxId => $tax_nodes) {
            $questionIds = [];

            $forceBypass = true;

            foreach ($tax_nodes as $taxNode) {
                $forceBypass = false;

                $taxTree = new ilTaxonomyTree($taxId);

                $taxNodeAssignment = new ilTaxNodeAssignment('tst', $this->getTestObjId(), 'quest', $taxId);

                $subNodes = $taxTree->getSubTreeIds((int) $taxNode);
                $subNodes[] = $taxNode;

                $taxItems = $taxNodeAssignment->getAssignmentsOfNode($subNodes);

                // fau: taxGroupFilter - collect the questions per group node
                if ((int) $taxId === $this->getGroupTaxId()) {
                    $this->grouped_questions[(int) $taxNode] = array_values(array_unique(
                        array_map(static fn(array $item): int => (int) $item['item_id'], $taxItems)
                    ));
                }
                // fau.

                foreach ($taxItems as $taxItem) {
                    $questionIds[$taxItem['item_id']] = $taxItem['item_id'];
                }
            }

            if (!$forceBypass) {
                $expressions[] = $this->db->in('question_id', $questionIds, false, 'integer');
            }
        }

        return $expressions;
    }

    private function getLifecycleFilterExpressions(): array
    {
        if (count($this->lifecycleFilter)) {
            return [
                $this->db->in('lifecycle', $this->lifecycleFilter, false, 'text')
            ];
        }

        return [];
    }

    // fau: taxFilter/typeFilter - get the expressions for a type filter
    private function getTypeFilterExpressions(): array
    {
        if (count($this->typeFilter)) {
            return [
                $this->db->in('question_type_fi', $this->typeFilter, false, 'integer')
            ];
        }

        return [];
    }
    // fau;

    private function isActiveQuestionType(array $questionData): bool
    {
        if (!isset($questionData['plugin'])) {
            return false;
        }

        if (!$questionData['plugin']) {
            return true;
        }

        if (!$this->component_repository->getComponentByTypeAndName(
            ilComponentInfo::TYPE_COMPONENT,
            'TestQuestionPool'
        )->getPluginSlotById('qst')->hasPluginName($questionData['plugin_name'])) {
            return false;
        }

        return $this->component_repository
            ->getComponentByTypeAndName(
                ilComponentInfo::TYPE_COMPONENT,
                'TestQuestionPool'
            )
            ->getPluginSlotById(
                'qst'
            )
            ->getPluginByName(
                $questionData['plugin_name']
            )->isActive();
    }

    public function resetQuestionList()
    {
        $this->questions = [];
        $this->taxFilters = [];
        $this->typeFilter = [];
        $this->poolId = -1;
        // fau: taxGroupFilter - reset the groups of the previous rule
        $this->grouped_questions = [];
        // fau.
    }

    // fau: taxGroupFilter - select questions from a random group
    public function getSelectedQuestions(): array
    {
        if ($this->getSelectSize() <= 0) {
            return [];
        }

        $candidates = [];
        if ($this->getGroupTaxId()) {
            $valid_groups = [];
            foreach ($this->grouped_questions as $node_questions) {
                $filtered = array_values(array_intersect($this->questions, $node_questions));
                if (count($filtered) >= $this->getSelectSize()) {
                    $valid_groups[] = $filtered;
                }
            }
            if ($valid_groups === []) {
                return [];
            }
            $candidates = $valid_groups[array_rand($valid_groups)];
        } elseif (count($this->questions) >= $this->getSelectSize()) {
            $candidates = $this->questions;
        } else {
            return [];
        }

        $selected_keys = (array) array_rand($candidates, $this->getSelectSize());
        return array_values(array_intersect_key($candidates, array_flip($selected_keys)));
    }
    // fau.

    public function getQuestions(): array
    {
        return array_values($this->questions);
    }

    // =================================================================================================================

    public function rewind(): void
    {
        reset($this->questions);
    }

    public function current(): ?int
    {
        $current = current($this->questions);
        return $current !== false ? $current : null;
    }

    public function key(): ?int
    {
        return key($this->questions);
    }

    public function next(): void
    {
        next($this->questions);
    }

    public function valid(): bool
    {
        return key($this->questions) !== null;
    }

    public static function updateSourceQuestionPoolId($testId, $oldPoolId, $newPoolId)
    {
        global $DIC;
        $db = $DIC['ilDB'];

        $query = "UPDATE tst_rnd_cpy SET qpl_fi = %s WHERE tst_fi = %s AND qpl_fi = %s";

        $db->manipulateF(
            $query,
            ['integer', 'integer', 'integer'],
            [$newPoolId, $testId, $oldPoolId]
        );
    }
}
