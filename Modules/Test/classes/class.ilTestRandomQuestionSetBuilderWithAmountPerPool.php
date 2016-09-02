<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Modules/Test/classes/class.ilTestRandomQuestionSetBuilder.php';

/**
 * @author		Björn Heyser <bheyser@databay.de>
 * @version		$Id$
 *
 * @package     Modules/Test
 */
class ilTestRandomQuestionSetBuilderWithAmountPerPool extends ilTestRandomQuestionSetBuilder
{
	public function checkBuildable()
	{
// fau: fixRandomTestBuildable - improved the check for buildable test
		global $lng;

		$this->checkMessages = array();
		$questionsPerDefinition = array();
		$questionsMatchingCount = array();
		$buildable = true;

		// first round: collect all used questions and count their matching
		/** @var ilTestRandomQuestionSetSourcePoolDefinition $definition */
		foreach($this->sourcePoolDefinitionList as $definition)
		{
			$questionsPerDefinition[$definition->getId()] = array();
			$stage = $this->getQuestionStageForSourcePoolDefinition($definition);
			foreach ($stage->getInvolvedQuestionIds() as $id)
			{
				$questionsPerDefinition[$definition->getId()][$id]++;
				$questionsMatchingCount[$id]++;
			}
		}

		// second round: count the exclusive questions of each definition
		foreach($this->sourcePoolDefinitionList as $definition)
		{
			$exclusive = 0;
			foreach ($questionsPerDefinition[$definition->getId()] as $id => $used)
			{
				// all matchings are from this definition
				if ($questionsMatchingCount[$id] == $used)
				{
					// increase the number of exclusive questions
					$exclusive++;
				}
			}
			if ($exclusive < $definition->getQuestionAmount())
			{
				$buildable = false;
				$this->checkMessages[] = sprintf($lng->txt('tst_msg_rand_quest_set_pass_not_buildable_detail'),
					$definition->getSequencePosition());
			}
		}

		// return $buildable;

		// keep old check for a while but messages will be created for the new check
		$questionStage = $this->getQuestionStageForSourcePoolDefinitionList($this->sourcePoolDefinitionList);
		if( $questionStage->isSmallerThan($this->sourcePoolDefinitionList->getQuestionAmount()) )
		{
			return false;
		}

		return true;
// fau.
	}

	public function performBuild(ilTestSession $testSession)
	{
		$questionSet = new ilTestRandomQuestionSetQuestionCollection();

		foreach($this->sourcePoolDefinitionList as $definition)
		{
			/** @var ilTestRandomQuestionSetSourcePoolDefinition $definition */

			$requiredQuestionAmount = $definition->getQuestionAmount();

			$potentialQuestionStage = $this->getQuestionStageForSourcePoolDefinition($definition);

			$actualQuestionStage = $potentialQuestionStage->getRelativeComplementCollection($questionSet);

			if( $actualQuestionStage->isGreaterThan($requiredQuestionAmount) )
			{
				$questions = $this->fetchQuestionsFromStageRandomly($actualQuestionStage, $requiredQuestionAmount);
			}
			else
			{
// fau: fixRandomTestBuildable - log missing questions for a random test rule
				if( $actualQuestionStage->isSmallerThan($requiredQuestionAmount) )
				{
					global $ilDB, $ilLog;
					if (!isset($translator))
					{
						require_once("./Modules/Test/classes/class.ilTestTaxonomyFilterLabelTranslater.php");
						$translator = new ilTestTaxonomyFilterLabelTranslater($ilDB);
						$translator->loadLabels($this->sourcePoolDefinitionList);
					}
					$ilLog->write("RANDOM TEST: missing questions for: "
						. implode(" - ",array($definition->getPoolTitle(), $translator->getTaxonomyFilterLabel($definition->getMappedTaxonomyFilter()))));
				}
// fau.
				$questions = $actualQuestionStage;
			}

			$questionSet->mergeQuestionCollection($questions);
		}

		$requiredQuestionAmount = $this->sourcePoolDefinitionList->getQuestionAmount();

		if( $questionSet->isSmallerThan($requiredQuestionAmount) )
		{
			$missingQuestionCount = $questionSet->getMissingCount($requiredQuestionAmount);
// fau: fixRandomTestBuildable - avoid already chosen questions being used as fillers
			$potentialQuestionStage = $this->getQuestionStageForSourcePoolDefinitionList($this->sourcePoolDefinitionList);
			$actualQuestionStage = $potentialQuestionStage->getRelativeComplementCollection($questionSet);
			$questions = $this->fetchQuestionsFromStageRandomly($actualQuestionStage, $missingQuestionCount);
			$ilLog->write("RANDOM TEST: added questions:" .implode(',', $questions->getInvolvedQuestionIds()));
// fau.
			$questionSet->mergeQuestionCollection($questions);
		}

		$this->handleQuestionOrdering($questionSet);

		$this->storeQuestionSet($testSession, $questionSet);
	}
}