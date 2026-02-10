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
abstract class ilTestRandomQuestionSetBuilder implements ilTestRandomSourcePoolDefinitionQuestionCollectionProvider
{
    protected $checkMessages = [];

    protected function __construct(
        protected ilDBInterface $db,
        protected ilLanguage $lng,
        protected TestLogger $logger,
        protected ilObjTest $testOBJ,
        protected ilTestRandomQuestionSetConfig $questionSetConfig,
        protected ilTestRandomQuestionSetSourcePoolDefinitionList $sourcePoolDefinitionList,
        protected ilTestRandomQuestionSetStagingPoolQuestionList $stagingPoolQuestionList
    ) {
        $this->stagingPoolQuestionList->setTestObjId($this->testOBJ->getId());
        $this->stagingPoolQuestionList->setTestId($this->testOBJ->getTestId());
    }

    abstract public function checkBuildable();

    abstract public function performBuild(ilTestSession $testSession);

    public function getSrcPoolDefListRelatedQuestCombinationCollection(
        ilTestRandomQuestionSetSourcePoolDefinitionList $sourcePoolDefinitionList
    ): ilTestRandomQuestionSetQuestionCollection {
        $question_stage = new ilTestRandomQuestionSetQuestionCollection();
        foreach ($sourcePoolDefinitionList as $definition) {
            $questions = $this->getSrcPoolDefRelatedQuestCollection($definition);
            $question_stage->mergeQuestionCollection($questions);
        }

        return $question_stage;
    }

    public function getSrcPoolDefRelatedQuestCollection(
        ilTestRandomQuestionSetSourcePoolDefinition $definition
    ): ilTestRandomQuestionSetQuestionCollection {
        return $this->buildSetQuestionCollection(
            $definition,
            $this->getQuestionIdsForSourcePoolDefinitionIds($definition)
        );
    }

    public function getSrcPoolDefListRelatedQuestUniqueCollection(
        ilTestRandomQuestionSetSourcePoolDefinitionList $sourcePoolDefinitionList
    ): ilTestRandomQuestionSetQuestionCollection {
        return $this->getSrcPoolDefListRelatedQuestCombinationCollection($sourcePoolDefinitionList)
            ->getUniqueQuestionCollection();
    }

    private function getQuestionIdsForSourcePoolDefinitionIds(
        ilTestRandomQuestionSetSourcePoolDefinition $definition
    ): array {
        $this->stagingPoolQuestionList->resetQuestionList();
        $this->stagingPoolQuestionList->setPoolId($definition->getPoolId());

        if ($this->hasTaxonomyFilter($definition)) {
            foreach ($definition->getMappedTaxonomyFilter() as $tax_id => $node_ids) {
                $this->stagingPoolQuestionList->addTaxonomyFilter($tax_id, $node_ids);
            }
        }

        if ($definition->getLifecycleFilter() !== []) {
            $this->stagingPoolQuestionList->setLifecycleFilter($definition->getLifecycleFilter());
        }

        if ($this->hasTypeFilter($definition)) {
            $this->stagingPoolQuestionList->setTypeFilter($definition->getTypeFilter());
        }

        $this->stagingPoolQuestionList->loadQuestions();
        return $this->stagingPoolQuestionList->getQuestions();
    }

    private function buildSetQuestionCollection(
        ilTestRandomQuestionSetSourcePoolDefinition $definition,
        array $question_ids
    ): ilTestRandomQuestionSetQuestionCollection {
        $set_question_collection = new ilTestRandomQuestionSetQuestionCollection();

        foreach ($question_ids as $question_id) {
            $set_question = new ilTestRandomQuestionSetQuestion();
            $set_question->setQuestionId($question_id);
            $set_question->setSourcePoolDefinitionId($definition->getId());
            $set_question_collection->addQuestion($set_question);
        }

        return $set_question_collection;
    }

    private function hasTaxonomyFilter(
        ilTestRandomQuestionSetSourcePoolDefinition $definition
    ): bool {
        if ($definition->getMappedTaxonomyFilter() === []) {
            return false;
        }
        return true;
    }

    private function hasTypeFilter(
        ilTestRandomQuestionSetSourcePoolDefinition $definition
    ): bool {
        if ($definition->getTypeFilter() === []) {
            return false;
        }

        return true;
    }

    protected function storeQuestionSet(
        ilTestSession $test_session,
        ilTestRandomQuestionSetQuestionCollection $question_set
    ): void {
        $position = 0;
        foreach ($question_set->getQuestions() as $set_question) {
            $set_question->setSequencePosition($position++);
            $this->storeQuestion($test_session, $set_question);
        }
    }

    private function storeQuestion(
        ilTestSession $test_session,
        ilTestRandomQuestionSetQuestion $set_question
    ): void {
        $next_id = $this->db->nextId('tst_test_rnd_qst');

        $this->db->insert('tst_test_rnd_qst', [
            'test_random_question_id' => ['integer', $next_id],
            'active_fi' => ['integer', $test_session->getActiveId()],
            'question_fi' => ['integer', $set_question->getQuestionId()],
            'sequence' => ['integer', $set_question->getSequencePosition()],
            'pass' => ['integer', $test_session->getPass()],
            'tstamp' => ['integer', time()],
            'src_pool_def_fi' => ['integer', $set_question->getSourcePoolDefinitionId()]
        ]);
    }

    protected function fetchQuestionsFromStageRandomly(
        ilTestRandomQuestionSetQuestionCollection $questionStage,
        int $requiredQuestionAmount
    ): ilTestRandomQuestionSetQuestionCollection {
        return $questionStage->getRandomQuestionCollection($requiredQuestionAmount);
    }

    protected function handleQuestionOrdering(
        ilTestRandomQuestionSetQuestionCollection $question_set
    ): void {
        if ($this->testOBJ->getShuffleQuestions()) {
            $question_set->shuffleQuestions();
        }
    }

    // =================================================================================================================

    final public static function getInstance(
        ilDBInterface $db,
        ilLanguage $lng,
        TestLogger $logger,
        ilObjTest $testOBJ,
        ilTestRandomQuestionSetConfig $questionSetConfig,
        ilTestRandomQuestionSetSourcePoolDefinitionList $sourcePoolDefinitionList,
        ilTestRandomQuestionSetStagingPoolQuestionList $stagingPoolQuestionList
    ) {
        if ($questionSetConfig->isQuestionAmountConfigurationModePerPool()) {
            return new ilTestRandomQuestionSetBuilderWithAmountPerPool(
                $db,
                $lng,
                $logger,
                $testOBJ,
                $questionSetConfig,
                $sourcePoolDefinitionList,
                $stagingPoolQuestionList
            );
        }

        return new ilTestRandomQuestionSetBuilderWithAmountPerTest(
            $db,
            $lng,
            $logger,
            $testOBJ,
            $questionSetConfig,
            $sourcePoolDefinitionList,
            $stagingPoolQuestionList
        );
    }

    public function getCheckMessages(): array
    {
        return $this->checkMessages;
    }
}
