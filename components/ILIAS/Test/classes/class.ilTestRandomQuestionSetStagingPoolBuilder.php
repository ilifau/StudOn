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

use ILIAS\Test\Logging\TestLogger;

/**
 * @author		Björn Heyser <bheyser@databay.de>
 * @version		$Id$
 *
 * @package		Modules/Test
 */
class ilTestRandomQuestionSetStagingPoolBuilder
{
    public function __construct(
        private readonly ilDBInterface $db,
        private readonly TestLogger $logger,
        private readonly ilObjTest $test_obj
    ) {
    }

    // =================================================================================================================

    public function rebuild(ilTestRandomQuestionSetSourcePoolDefinitionList $source_pool_definition_list): void
    {
        $this->reset();
        $this->build($source_pool_definition_list);
    }

    public function reset()
    {
        $this->removeMirroredTaxonomies();
        $this->removeStagedQuestions();
    }

    private function removeMirroredTaxonomies()
    {
        foreach (ilObjTaxonomy::getUsageOfObject($this->test_obj->getId()) as $tax_id) {
            $taxonomy = new ilObjTaxonomy($tax_id);
            $taxonomy->delete();
        }
    }

    private function removeStagedQuestions()
    {
        $res = $this->db->queryF(
            'SELECT * FROM tst_rnd_cpy WHERE tst_fi = %s',
            ['integer'],
            [$this->test_obj->getTestId()]
        );

        while ($row = $this->db->fetchAssoc($res)) {
            try {
                $question = assQuestion::instantiateQuestion($row['qst_fi']);
            } catch (InvalidArgumentException $ex) {
                $this->logger->warning(
                    "could not delete staged random question (ref={$this->test_obj->getRefId()} / qst={$row['qst_fi']})"
                );
                return;
            }
            $question->delete($row['qst_fi']);
        }

        $this->db->manipulateF(
            'DELETE FROM tst_rnd_cpy WHERE tst_fi = %s',
            ['integer'],
            [$this->test_obj->getTestId()
        ]
        );
    }

    private function build(
        ilTestRandomQuestionSetSourcePoolDefinitionList $source_pool_definition_list
    ): void {
        $question_id_mapping_per_pool = [];
        foreach ($source_pool_definition_list as $definition) {
            $tax_filter = $definition->getOriginalTaxonomyFilter();
            $type_filter = $definition->getTypeFilter();
            $lifecycle_filter = $definition->getLifecycleFilter();

            $filter_items = null;
            foreach ($tax_filter as $tax_id => $node_ids) {
                $tax_items = [];
                foreach ($node_ids as $node_id) {
                    $node_items = ilObjTaxonomy::getSubTreeItems(
                        'qpl',
                        $definition->getPoolId(),
                        'quest',
                        $tax_id,
                        $node_id
                    );

                    foreach ($node_items as $node_item) {
                        $tax_items[] = $node_item['item_id'];
                    }
                }

                $filter_items = isset($filter_items) ? array_intersect($filter_items, array_unique($tax_items)) : array_unique($tax_items);
            }

            $question_id_mapping_per_pool = $this->stageQuestionsFromSourcePool(
                $definition->getPoolId(),
                $question_id_mapping_per_pool,
                $filter_items !== null ? array_values($filter_items) : null,
                $type_filter,
                $lifecycle_filter
            );
        }

        foreach ($question_id_mapping_per_pool as $source_pool_id => $question_id_mapping) {
            $taxonomiesKeysMap = $this->mirrorSourcePoolTaxonomies($source_pool_id, $question_id_mapping);
            $this->applyMappedTaxonomiesKeys($source_pool_definition_list, $taxonomiesKeysMap, $source_pool_id);
        }
    }

    private function stageQuestionsFromSourcePool(
        int $source_pool_id,
        array $question_id_mapping_per_pool,
        array $filter_ids = null,
        array $type_filter = null,
        array $lifecycle_filter = null
    ): array {
        $query = 'SELECT question_id FROM qpl_questions WHERE obj_fi = %s AND complete = %s AND original_id IS NULL';
        if (!empty($filter_ids)) {
            $query .= ' AND ' . $this->db->in('question_id', $filter_ids, false, 'integer');
        }
        if (!empty($type_filter)) {
            $query .= ' AND ' . $this->db->in('question_type_fi', $type_filter, false, 'integer');
        }
        if (!empty($lifecycle_filter)) {
            $query .= ' AND ' . $this->db->in('lifecycle', $lifecycle_filter, false, 'text');
        }
        $res = $this->db->queryF($query, ['integer', 'text'], [$source_pool_id, 1]);

        while ($row = $this->db->fetchAssoc($res)) {
            if (!isset($question_id_mapping_per_pool[$source_pool_id])) {
                $question_id_mapping_per_pool[$source_pool_id] = [];
            }
            if (!isset($question_id_mapping_per_pool[$source_pool_id][ $row['question_id'] ])) {
                $question = assQuestion::instantiateQuestion($row['question_id']);
                $duplicateId = $question->duplicate(true, '', '', -1, $this->test_obj->getId());

                $nextId = $this->db->nextId('tst_rnd_cpy');
                $this->db->insert('tst_rnd_cpy', [
                    'copy_id' => ['integer', $nextId],
                    'tst_fi' => ['integer', $this->test_obj->getTestId()],
                    'qst_fi' => ['integer', $duplicateId],
                    'qpl_fi' => ['integer', $source_pool_id]
                ]);

                $question_id_mapping_per_pool[$source_pool_id][ $row['question_id'] ] = $duplicateId;
            }
        }

        return $question_id_mapping_per_pool;
    }

    private function mirrorSourcePoolTaxonomies(
        int $source_pool_id,
        array $questionId_mapping
    ): ilQuestionPoolDuplicatedTaxonomiesKeysMap {
        $duplicator = new ilQuestionPoolTaxonomiesDuplicator();
        $duplicator->setSourceObjId($source_pool_id);
        $duplicator->setSourceObjType('qpl');
        $duplicator->setTargetObjId($this->test_obj->getId());
        $duplicator->setTargetObjType($this->test_obj->getType());
        $duplicator->setQuestionIdMapping($questionId_mapping);
        $duplicator->duplicate($duplicator->getAllTaxonomiesForSourceObject());

        return $duplicator->getDuplicatedTaxonomiesKeysMap();
    }

    private function applyMappedTaxonomiesKeys(
        ilTestRandomQuestionSetSourcePoolDefinitionList $source_pool_definition_list,
        ilQuestionPoolDuplicatedTaxonomiesKeysMap $taxonomies_keys_map,
        int $source_pool_id
    ): void {
        foreach ($source_pool_definition_list as $definition) {
            if ($definition->getPoolId() === $source_pool_id) {
                $definition->mapTaxonomyFilter($taxonomies_keys_map);
            }
        }
    }
}
