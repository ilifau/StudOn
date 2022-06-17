<?php

namespace FAU\Sync;

use FAU\Staging\Data\Orgunit;

/**
 * Synchronisation of data coming from fau.org
 * This will update data of the org service
 */
class SyncWithOrg extends SyncBase
{

    /**
     * Synchronize data (called by cron job)
     * Counted items are the org units
     */
    public function synchronize() : void
    {
        $this->syncOrgunits();
    }


    /**
     * Synchronize data found in the staging table fau_orgunit
     * @todo respect valid period
     */
    protected function syncOrgunits()
    {
        $this->info('syncOrgunits...');
        $stagingUnits = $this->staging->repo()->getOrgunits();
        $orgUnits = $this->org->repo()->getOrgunits();

        foreach ($stagingUnits as $unit)
        {
            if (!isset($orgUnits[$unit->getId()])) {
                $newUnit = new \FAU\Org\Data\Orgunit(
                    $unit->getId(),
                    $this->getIdPath($unit, $stagingUnits),
                    $unit->getParentId(),
                    $unit->getAssignable(),
                    $unit->getFauOrgKey(),
                    $unit->getValidFrom(),
                    $unit->getValidTo(),
                    $unit->getShorttext(),
                    $unit->getDefaulttext(),
                    $unit->getLongtext(),
                    null,
                    false,
                    false,
                    null
                );
                $this->org->repo()->save($newUnit);
                $this->increaseItemsAdded();
                if ($unit->getAssignable()) {
                    $this->addWarning('NEW assignable Orgunit ' . $unit->getId() . ': ' . $unit->getShorttext());
                }
            }
            else {
                $oldUnit = $orgUnits[$unit->getId()];
                $newUnit = $oldUnit
                    ->withPath($this->getIdPath($unit, $stagingUnits))
                    ->withParentId($unit->getParentId())
                    ->withAssignable($unit->getAssignable())
                    ->withFauorgNr($unit->getFauOrgKey())
                    ->withValidFrom($unit->getValidFrom())
                    ->withValidTo($unit->getValidTo())
                    ->withShorttext($unit->getShorttext())
                    ->withDefaulttext($unit->getDefaulttext())
                    ->withLongtext($unit->getLongtext());
                $this->org->repo()->save($newUnit);
                $this->increaseItemsUpdated();
                if ($newUnit->getPath() != $oldUnit->getPath()) {
                    $this->addWarning('CHANGED Orgunit path for ' . $unit->getId() . ': ' . $unit->getShorttext());
                }
            }
        }
    }

    /**
     * Get the path of Ids from FAU root to the staging org unit
     * This path will be saved in the orgunit of StudOn
     * @param Orgunit $unit
     * @param Orgunit[]  $allUnits
     */
    protected function getIdPath(Orgunit $unit, array $allUnits) : string
    {
        $path = (string) $unit->getId();
        while ($unit->getParentId() !== null && isset($allUnits[$unit->getParentId()])) {
            $path = $unit->getParentId() . '.' . $path;
            $unit = $allUnits[$unit->getParentId()];
        }
        return $path;
    }
}

