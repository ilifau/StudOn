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
    // hey: fixRandomTestBuildable - improvment of improved pass build check
    /**
     * @return bool
     */
    public function checkBuildableNewer()
    {
        global $DIC; /* @var ILIAS\DI\Container $DIC */
        $lng = $DIC['lng'];
            
        $isBuildable = true;
        
        require_once 'Modules/Test/classes/class.ilTestRandomQuestionsQuantitiesDistribution.php';
        $quantitiesDistribution = new ilTestRandomQuestionsQuantitiesDistribution($this);
        $quantitiesDistribution->setSourcePoolDefinitionList($this->sourcePoolDefinitionList);
        $quantitiesDistribution->initialise();
        
        // perhaps not every with every BUT every with any next ??!
        // perhaps exactly like this !!? I dont know :-)
        // it should be about vice versa rule conflict reporting
        
        foreach ($this->sourcePoolDefinitionList as $definition) {
            // fau: taxGroupFilter - get a sample group and check its size
            //		Note: This does not check conflicts with other rules!
            //		Therefore auhors must ensure that group filters do not produce conflicts
            if ($definition->getOriginalGroupTaxId()) {
                $group = $this->getQuestionSetForSourcePoolDefinition($definition);
                if ($group->isSmallerThan($definition->getQuestionAmount())) {
                    $this->checkMessages[] = sprintf(
                        $lng->txt('tst_msg_rand_quest_set_pass_not_buildable_group'),
                        $definition->getSequencePosition()
                    );
                    $isBuildable = false;
                }
            }
            // fau.

            /** @var ilTestRandomQuestionSetSourcePoolDefinition $definition */
            
            $quantityCalculation = $quantitiesDistribution->calculateQuantities($definition);
            
            if ($quantityCalculation->isRequiredAmountGuaranteedAvailable()) {
                continue;
            }
            
            $isBuildable = false;
            
            $this->checkMessages[] = $quantityCalculation->getDistributionReport($lng);
        }
        
        return $isBuildable;
    }
    // hey.
    
    /**
     * @return bool
     */
    public function checkBuildable()
    {
        // hey: fixRandomTestBuildable - improved the buildable check improvement
        return $this->checkBuildableNewer();
        // hey.
        
        $questionStage = $this->getSrcPoolDefListRelatedQuestUniqueCollection($this->sourcePoolDefinitionList);

        if ($questionStage->isSmallerThan($this->sourcePoolDefinitionList->getQuestionAmount())) {
            return false;
        }

        return true;
    }

    public function performBuild(ilTestSession $testSession)
    {
        $questionSet = new ilTestRandomQuestionSetQuestionCollection();

        foreach ($this->sourcePoolDefinitionList as $definition) {
            /** @var ilTestRandomQuestionSetSourcePoolDefinition $definition */

            $requiredQuestionAmount = $definition->getQuestionAmount();

            // fau: taxGroupFilter - draw a question group randomly
            if (!empty($definition->getMappedGroupTaxId())) {
                // draw the needed amount of questions from a filternode of the group taxonomy
                $potentialQuestionStage = $this->getQuestionSetForSourcePoolDefinition($definition);
            } else {
                $potentialQuestionStage = $this->getSrcPoolDefRelatedQuestCollection($definition);
            }
            // fau.
            $actualQuestionStage = $potentialQuestionStage->getRelativeComplementCollection($questionSet);

            if ($actualQuestionStage->isGreaterThan($requiredQuestionAmount)) {
                $questions = $this->fetchQuestionsFromStageRandomly($actualQuestionStage, $requiredQuestionAmount);
            } else {
                // fau: fixRandomTestBuildable - log missing questions for a random test rule
                if ($actualQuestionStage->isSmallerThan($requiredQuestionAmount)) {
                    global $DIC;
                    $ilDB = $DIC['ilDB'];
                    $ilLog = $DIC['ilLog'];
                    if (!isset($translator)) {
                        require_once("./Modules/Test/classes/class.ilTestTaxonomyFilterLabelTranslater.php");
                        $translator = new ilTestTaxonomyFilterLabelTranslater($ilDB);
                        $translator->loadLabels($this->sourcePoolDefinitionList);
                    }
                    $ilLog->write("RANDOM TEST: missing questions for: "
                        . implode(" - ", array($definition->getPoolTitle(), $translator->getTaxonomyFilterLabel($definition->getMappedTaxonomyFilter()))));
                }
                // fau.
                $questions = $actualQuestionStage;
            }

            $questionSet->mergeQuestionCollection($questions);
        }

        $requiredQuestionAmount = $this->sourcePoolDefinitionList->getQuestionAmount();

        if ($questionSet->isSmallerThan($requiredQuestionAmount)) {
            $missingQuestionCount = $questionSet->getMissingCount($requiredQuestionAmount);
            // fau: fixRandomTestBuildable - avoid already chosen questions being used as fillers
            $potentialQuestionStage = $this->getSrcPoolDefListRelatedQuestUniqueCollection($this->sourcePoolDefinitionList);
            $actualQuestionStage = $potentialQuestionStage->getRelativeComplementCollection($questionSet);
            $questions = $this->fetchQuestionsFromStageRandomly($actualQuestionStage, $missingQuestionCount);
            // fau.
            $questionSet->mergeQuestionCollection($questions);
        }

        $this->handleQuestionOrdering($questionSet);

        $this->storeQuestionSet($testSession, $questionSet);
    }
}
