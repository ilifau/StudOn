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
 * @author		Björn Heyser <bheyser@databay.de>
 * @version		$Id$
 *
 * @package		Modules/Test
 */
class ilTestRandomQuestionsSrcPoolDefinitionQuantitiesCalculation
{
    protected ilTestRandomQuestionSetSourcePoolDefinitionList $intersectionQuantitySharingDefinitionList;
    protected int $overallQuestionAmount;
    protected int $exclusiveQuestionAmount;
    protected int $availableSharedQuestionAmount;

    public function __construct(
        protected readonly ilTestRandomQuestionSetSourcePoolDefinition $sourcePoolDefinition
    ) {
    }

    public function getSourcePoolDefinition(): ilTestRandomQuestionSetSourcePoolDefinition
    {
        return $this->sourcePoolDefinition;
    }

    public function getIntersectionQuantitySharingDefinitionList(): ilTestRandomQuestionSetSourcePoolDefinitionList
    {
        return $this->intersectionQuantitySharingDefinitionList;
    }

    public function setIntersectionQuantitySharingDefinitionList(
        ilTestRandomQuestionSetSourcePoolDefinitionList $intersectionQuantitySharingDefinitionList
    ): void {
        $this->intersectionQuantitySharingDefinitionList = $intersectionQuantitySharingDefinitionList;
    }

    public function getOverallQuestionAmount(): int
    {
        return $this->overallQuestionAmount;
    }

    public function setOverallQuestionAmount(int $overallQuestionAmount): void
    {
        $this->overallQuestionAmount = $overallQuestionAmount;
    }

    public function getExclusiveQuestionAmount(): int
    {
        return $this->exclusiveQuestionAmount;
    }

    public function setExclusiveQuestionAmount(int $exclusiveQuestionAmount): void
    {
        $this->exclusiveQuestionAmount = $exclusiveQuestionAmount;
    }

    public function getAvailableSharedQuestionAmount(): int
    {
        return $this->availableSharedQuestionAmount;
    }

    public function setAvailableSharedQuestionAmount(int $availableSharedQuestionAmount): void
    {
        $this->availableSharedQuestionAmount = $availableSharedQuestionAmount;
    }

    protected function getReservedSharedQuestionAmount(): int
    {
        return $this->getOverallQuestionAmount() - (
            $this->getExclusiveQuestionAmount() + $this->getAvailableSharedQuestionAmount()
        );
    }

    protected function getRemainingRequiredQuestionAmount(): int
    {
        $requiredQuestionAmount = $this->getSourcePoolDefinition()->getQuestionAmount();
        $exclusiveQuestionAmount = $this->getExclusiveQuestionAmount();

        return $requiredQuestionAmount - $exclusiveQuestionAmount;
    }

    protected function isRequiredQuestionAmountSatisfiedByOverallQuestionQuantity(): bool
    {
        $requiredQuestionAmount = $this->getSourcePoolDefinition()->getQuestionAmount();
        $overallQuestionAmount = $this->getOverallQuestionAmount();

        return $overallQuestionAmount >= $requiredQuestionAmount;
    }

    protected function isRequiredQuestionAmountSatisfiedByExclusiveQuestionQuantity(): bool
    {
        $requiredQuestionAmount = $this->getSourcePoolDefinition()->getQuestionAmount();
        $exclusiveQuestionAmount = $this->getExclusiveQuestionAmount();

        return $exclusiveQuestionAmount >= $requiredQuestionAmount;
    }

    protected function isRemainingRequiredQuestionAmountSatisfiedBySharedQuestionQuantity(): bool
    {
        $remainingRequiredQuestionAmount = $this->getRemainingRequiredQuestionAmount();
        $availableSharedQuestionAmount = $this->getAvailableSharedQuestionAmount();

        return $availableSharedQuestionAmount >= $remainingRequiredQuestionAmount;
    }

    protected function sourcePoolDefinitionIntersectionsExist(): bool
    {
        if ($this->getIntersectionQuantitySharingDefinitionList()->getDefinitionCount() > 0) {
            return true;
        }

        return false;
    }

    public function isRequiredAmountGuaranteedAvailable(): bool
    {
        if ($this->isRequiredQuestionAmountSatisfiedByExclusiveQuestionQuantity()) {
            return true;
        }

        if ($this->isRemainingRequiredQuestionAmountSatisfiedBySharedQuestionQuantity()) {
            return true;
        }

        return false;
    }

    public function getDistributionReport(ilLanguage $lng): string
    {
        $report = $this->getRuleSatisfactionResultMessage($lng);

        if ($this->sourcePoolDefinitionIntersectionsExist()) {
            $report .= ' ' . $this->getConcurrentRuleConflictMessage($lng);
        }

        return $report;
    }

    protected function getRuleSatisfactionResultMessage(ilLanguage $lng): string
    {
        if ($this->isRequiredQuestionAmountSatisfiedByOverallQuestionQuantity()) {
            return sprintf(
                $lng->txt('tst_msg_rand_quest_set_rule_not_satisfied_reserved'),
                $this->getSourcePoolDefinition()->getSequencePosition(),
                $this->getSourcePoolDefinition()->getQuestionAmount(),
                $this->getOverallQuestionAmount()
            );
        }

        return sprintf(
            $lng->txt('tst_msg_rand_quest_set_rule_not_satisfied_missing'),
            $this->getSourcePoolDefinition()->getSequencePosition(),
            $this->getSourcePoolDefinition()->getQuestionAmount(),
            $this->getOverallQuestionAmount()
        );
    }

    protected function getConcurrentRuleConflictMessage(ilLanguage $lng): string
    {
        $definitionsString = '<br />' . $this->buildIntersectionQuestionSharingDefinitionsString($lng);

        if ($this->isRequiredQuestionAmountSatisfiedByOverallQuestionQuantity()) {
            return sprintf(
                $lng->txt('tst_msg_rand_quest_set_rule_not_satisfied_reserved_shared'),
                $this->getAvailableSharedQuestionAmount(),
                $definitionsString
            );
        }

        return sprintf(
            $lng->txt('tst_msg_rand_quest_set_rule_not_satisfied_missing_shared'),
            $this->getReservedSharedQuestionAmount(),
            $definitionsString
        );
    }

    protected function buildIntersectionQuestionSharingDefinitionsString(ilLanguage $lng): string
    {
        $definitionsString = [];

        foreach ($this->getIntersectionQuantitySharingDefinitionList() as $definition) {
            $definitionsString[] = sprintf(
                $lng->txt('tst_msg_rand_quest_set_rule_label'),
                $definition->getSequencePosition()
            );
        }

        $definitionsString = '<ul><li>' . implode('</li><li>', $definitionsString) . '</li></ul>';
        return $definitionsString;
    }
}
