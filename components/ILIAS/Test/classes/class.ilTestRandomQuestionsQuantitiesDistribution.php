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
 * @author        Björn Heyser <bheyser@databay.de>
 * @version        $Id$
 *
 * @package components\ILIAS/Test
 */
class ilTestRandomQuestionsQuantitiesDistribution
{
    /**
     * @var array<int, ilTestRandomQuestionSetSourcePoolDefinitionList>
     */
    protected array $quest_related_src_pool_def_register = [];

    /**
     * @var array<int, ilTestRandomSetQuestionCollection>
     */
    protected array $src_pool_def_related_quest_register = [];


    public function __construct(
        private ilDBInterface $db,
        private ilTestRandomSourcePoolDefinitionQuestionCollectionProvider $question_collection_provider,
        private ilTestRandomQuestionSetSourcePoolDefinitionList $source_pool_definition_list
    ) {
    }

    protected function buildSourcePoolDefinitionListInstance(): ilTestRandomQuestionSetSourcePoolDefinitionList
    {
        $any_test_object = new ilObjTest();
        $non_required_db = $this->db;
        $non_used_factory = new ilTestRandomQuestionSetSourcePoolDefinitionFactory($non_required_db, $any_test_object);
        return new ilTestRandomQuestionSetSourcePoolDefinitionList($non_required_db, $any_test_object, $non_used_factory);
    }

    protected function buildRandomQuestionCollectionInstance(): ilTestRandomQuestionSetQuestionCollection
    {
        return new ilTestRandomQuestionSetQuestionCollection();
    }

    protected function buildQuestionCollectionSubsetApplicationInstance(): ilTestRandomQuestionCollectionSubsetApplication
    {
        return new ilTestRandomQuestionCollectionSubsetApplication();
    }

    protected function buildQuestionCollectionSubsetApplicationListInstance(): ilTestRandomQuestionCollectionSubsetApplicationList
    {
        return new ilTestRandomQuestionCollectionSubsetApplicationList();
    }

    protected function resetQuestRelatedSrcPoolDefRegister()
    {
        $this->quest_related_src_pool_def_register = [];
    }

    protected function registerQuestRelatedSrcPoolDef(
        int $question_id,
        ilTestRandomQuestionSetSourcePoolDefinition $definition
    ): void {
        if (!array_key_exists($question_id, $this->quest_related_src_pool_def_register)) {
            $this->quest_related_src_pool_def_register[$question_id] = $this->buildSourcePoolDefinitionListInstance();
        }

        $this->quest_related_src_pool_def_register[$question_id]->addDefinition($definition);
    }

    protected function getQuestRelatedSrcPoolDefinitionList(int $question_id): ?ilTestRandomQuestionSetSourcePoolDefinitionList
    {
        if (isset($this->quest_related_src_pool_def_register[$question_id])) {
            return $this->quest_related_src_pool_def_register[$question_id];
        }

        return null;
    }

    protected function resetSrcPoolDefRelatedQuestRegister()
    {
        $this->src_pool_def_related_quest_register = [];
    }

    protected function registerSrcPoolDefRelatedQuest(
        int $definition_id,
        ilTestRandomQuestionSetQuestion $random_set_question
    ): void {
        if (!isset($this->src_pool_def_related_quest_register[$definition_id])) {
            $this->src_pool_def_related_quest_register[$definition_id] = $this->buildRandomQuestionCollectionInstance();
        }

        $this->src_pool_def_related_quest_register[$definition_id]->addQuestion($random_set_question);
    }

    protected function getSrcPoolDefRelatedQuestionCollection(int $definition_id): ilTestRandomQuestionSetQuestionCollection
    {
        if (isset($this->src_pool_def_related_quest_register[$definition_id])) {
            return $this->src_pool_def_related_quest_register[$definition_id];
        }

        return new ilTestRandomQuestionSetQuestionCollection();
    }

    protected function initialiseRegisters(): void
    {
        foreach ($this->getSrcPoolDefQuestionCombinationCollection() as $random_question) {
            $source_pool_definition = $this->source_pool_definition_list->getDefinition(
                $random_question->getSourcePoolDefinitionId()
            );

            $this->registerSrcPoolDefRelatedQuest(
                $random_question->getSourcePoolDefinitionId(),
                $random_question
            );

            if ($source_pool_definition && $random_question->getQuestionId()) {
                $this->registerQuestRelatedSrcPoolDef(
                    $random_question->getQuestionId(),
                    $source_pool_definition
                );
            }
        }
    }

    protected function resetRegisters(): void
    {
        $this->resetQuestRelatedSrcPoolDefRegister();
        $this->resetSrcPoolDefRelatedQuestRegister();
    }

    protected function getSrcPoolDefQuestionCombinationCollection(): ilTestRandomQuestionSetQuestionCollection
    {
        return $this->question_collection_provider->getSrcPoolDefListRelatedQuestCombinationCollection(
            $this->source_pool_definition_list
        );
    }

    protected function getExclusiveQuestionCollection(int $definition_id): ilTestRandomQuestionSetQuestionCollection
    {
        $exclusive_qst_collection = $this->buildRandomQuestionCollectionInstance();
        foreach ($this->getSrcPoolDefRelatedQuestionCollection($definition_id) as $question) {
            if ($this->isQuestionUsedByMultipleSrcPoolDefinitions($question)) {
                continue;
            }

            $exclusive_qst_collection->addQuestion($question);
        }
        return $exclusive_qst_collection;
    }

    protected function getSharedQuestionCollection(int $definition_id): ilTestRandomQuestionSetQuestionCollection
    {
        return $this->getSrcPoolDefRelatedQuestionCollection($definition_id)
            ->getRelativeComplementCollection(
                $this->getExclusiveQuestionCollection($definition_id)
            );
    }

    protected function getIntersectionQuestionCollection(
        int $this_definition_id,
        int $that_definition_id
    ): ilTestRandomQuestionSetQuestionCollection {
        $this_def_related_shared_qst_collection = $this->getSharedQuestionCollection($this_definition_id);
        $that_def_related_shared_qst_collection = $this->getSharedQuestionCollection($that_definition_id);

        return $this_def_related_shared_qst_collection->getIntersectionCollection(
            $that_def_related_shared_qst_collection
        );
    }

    /**
     * @return array<$definitionId, ilTestRandomQuestionSetQuestionCollection>
     */
    protected function getIntersectionQstCollectionByDefinitionMap(
        ilTestRandomQuestionSetSourcePoolDefinition $definition
    ): array {
        $intersectionQstCollectionsByDefId = [];

        $sharedQuestionCollection = $this->getSharedQuestionCollection($definition->getId());
        foreach ($sharedQuestionCollection as $sharedQuestion) {
            $relatedSrcPoolDefList = $this->getQuestRelatedSrcPoolDefinitionList($sharedQuestion->getQuestionId());
            foreach ($relatedSrcPoolDefList as $otherDefinition) {
                if ($otherDefinition->getId() == $definition->getId()) {
                    continue;
                }

                if (isset($intersectionQstCollectionsByDefId[$otherDefinition->getId()])) {
                    continue;
                }

                $intersectionQuestionCollection = $this->getIntersectionQuestionCollection(
                    $definition->getId(),
                    $otherDefinition->getId()
                );

                $intersectionQstCollectionsByDefId[$otherDefinition->getId()] = $intersectionQuestionCollection;
            }
        }

        return $intersectionQstCollectionsByDefId;
    }

    protected function getIntersectionQuestionCollectionSubsetApplicationList(
        ilTestRandomQuestionSetSourcePoolDefinition $definition
    ): ilTestRandomQuestionCollectionSubsetApplicationList {
        $qstCollectionSubsetApplicationList = $this->buildQuestionCollectionSubsetApplicationListInstance();

        $intersectionQstCollectionByDefIdMap = $this->getIntersectionQstCollectionByDefinitionMap($definition);
        foreach ($intersectionQstCollectionByDefIdMap as $otherDefinitionId => $intersectionCollection) {
            /* @var ilTestRandomQuestionSetQuestionCollection $intersectionCollection */

            $qstCollectionSubsetApplication = $this->buildQuestionCollectionSubsetApplicationInstance();
            $qstCollectionSubsetApplication->setQuestions($intersectionCollection->getQuestions());
            $qstCollectionSubsetApplication->setApplicantId($otherDefinitionId);

            $qstCollectionSubsetApplication->setRequiredAmount(
                $this->source_pool_definition_list->getDefinition($otherDefinitionId)->getQuestionAmount()
            );

            $qstCollectionSubsetApplicationList->addCollectionSubsetApplication($qstCollectionSubsetApplication);
        }

        return $qstCollectionSubsetApplicationList;
    }

    protected function getIntersectionSharingDefinitionList(
        ilTestRandomQuestionSetSourcePoolDefinition $definition
    ): ilTestRandomQuestionSetSourcePoolDefinitionList {
        $intersectionSharingDefinitionList = $this->buildSourcePoolDefinitionListInstance();

        $sharedQuestionCollection = $this->getSharedQuestionCollection($definition->getId());
        foreach ($sharedQuestionCollection as $sharedQuestion) {
            $relatedSrcPoolDefList = $this->getQuestRelatedSrcPoolDefinitionList($sharedQuestion->getQuestionId());
            foreach ($relatedSrcPoolDefList as $otherDefinition) {
                if ($otherDefinition->getId() == $definition->getId()) {
                    continue;
                }

                if ($intersectionSharingDefinitionList->hasDefinition($otherDefinition->getId())) {
                    continue;
                }

                $intersectionSharingDefinitionList->addDefinition($otherDefinition);
            }
        }

        return $intersectionSharingDefinitionList;
    }

    protected function isQuestionUsedByMultipleSrcPoolDefinitions(ilTestRandomQuestionSetQuestion $question): bool
    {
        /* @var ilTestRandomQuestionSetSourcePoolDefinitionList $qstRelatedSrcPoolDefList */
        $qstRelatedSrcPoolDefList = $this->quest_related_src_pool_def_register[$question->getQuestionId()];
        return $qstRelatedSrcPoolDefList->getDefinitionCount() > 1;
    }

    protected function getSrcPoolDefRelatedQuestionAmount(ilTestRandomQuestionSetSourcePoolDefinition $definition): int
    {
        return $this->getSrcPoolDefRelatedQuestionCollection($definition->getId())->getQuestionAmount();
    }

    protected function getExclusiveQuestionAmount(ilTestRandomQuestionSetSourcePoolDefinition $definition): int
    {
        return $this->getExclusiveQuestionCollection($definition->getId())->getQuestionAmount();
    }

    protected function getAvailableSharedQuestionAmount(ilTestRandomQuestionSetSourcePoolDefinition $definition): int
    {
        $intersectionSubsetApplicationList = $this->getIntersectionQuestionCollectionSubsetApplicationList($definition);

        foreach ($this->getSharedQuestionCollection($definition->getId()) as $sharedQuestion) {
            $intersectionSubsetApplicationList->handleQuestionRequest($sharedQuestion);
        }

        return $intersectionSubsetApplicationList->getNonReservedQuestionAmount();
    }

    protected function getRequiredSharedQuestionAmount(ilTestRandomQuestionSetSourcePoolDefinition $definition): int
    {
        $exclusiveQstCollection = $this->getExclusiveQuestionCollection($definition->getId());
        $missingExclsuiveQstCount = $exclusiveQstCollection->getMissingCount($definition->getQuestionAmount());
        return $missingExclsuiveQstCount;
    }

    protected function requiresSharedQuestions(ilTestRandomQuestionSetSourcePoolDefinition $definition): bool
    {
        return $this->getRequiredSharedQuestionAmount($definition) > 0;
    }


    public function initialise()
    {
        $this->initialiseRegisters();
    }

    public function reset()
    {
        $this->resetRegisters();
    }

    public function calculateQuantities(
        ilTestRandomQuestionSetSourcePoolDefinition $definition
    ): ilTestRandomQuestionsSrcPoolDefinitionQuantitiesCalculation {
        $quantity_calculation = new ilTestRandomQuestionsSrcPoolDefinitionQuantitiesCalculation($definition);
        $quantity_calculation->setOverallQuestionAmount($this->getSrcPoolDefRelatedQuestionAmount($definition));
        $quantity_calculation->setExclusiveQuestionAmount($this->getExclusiveQuestionAmount($definition));
        $quantity_calculation->setAvailableSharedQuestionAmount($this->getAvailableSharedQuestionAmount($definition));

        $quantity_calculation->setIntersectionQuantitySharingDefinitionList(
            $this->getIntersectionSharingDefinitionList($definition)
        );

        return $quantity_calculation;
    }
}
