<?php declare(strict_types=1);

namespace FAU\Org;

use ILIAS\DI\Container;
use FAU\Org\Data\Orgunit;
use FAU\SubService;

/**
 * Service for organisational data
 */
class Service extends SubService
{
    protected Repository $repository;

    /**
     * Get the repository for user data
     */
    public function repo() : Repository
    {
        if(!isset($this->repository)) {
            $this->repository = new Repository($this->dic->database(), $this->dic->logger()->fau());
        }
        return $this->repository;
    }

    /**
     * Get the org units from the path of a units
     * @param Orgunit $unit
     * @return Orgunit[]
     */
    public function getPathUnits(OrgUnit $unit) : array
    {
        $path = [];
        foreach ($unit->getPathIds() as $id) {
            $path[] = $this->repository->getOrgunit($id);
        }
        return $path;
    }

    /**
     * Get lines with titles and links of the org unit path
     * @param Orgunit $unit
     * @return string[]
     */
    public function getOrgPathLog(Orgunit $unit, $include_unit = false) : array
    {
        $list = [];
        foreach ($this->getPathUnits($unit) as $pathUnit) {
            if ($pathUnit->getId() == 1 || ($pathUnit->getId() == $unit->getId() && !$include_unit)) {
                continue;
            }
            $text = $pathUnit->getLongtext() . ' [' . $pathUnit->getShorttext() . ']';
            if (!empty($pathUnit->getIliasRefId())) {
                $text .= ' (https://studon.fau.de/' . $pathUnit->getIliasRefId() . ')';
            }
            $list[] = $text;
        }
        return $list;
    }


    /**
     * Get lines with titles and links of the ILIAS path
     * @param Orgunit $unit
     * @return string[]
     */
    public function getIliasPathLog(Orgunit $unit, $include_unit = false) : array
    {
        $list = [];
        if (!empty($unit->getIliasRefId())) {
            foreach ($this->dic->repositoryTree()->getPathId($unit->getIliasRefId()) as $path_ref_id) {
                if ($path_ref_id == 1 || ($path_ref_id == $unit->getIliasRefId() && !$include_unit)) {
                    continue;
                }
                $list[] = \ilObject::_lookupTitle(\IlObject::_lookupObjId($path_ref_id))
                    . ' (https://studon.fau.de/' . $path_ref_id . ')';
            }
        }
        return $list;
    }

    /**
     * Check the relations of org units and ILIAS categories
     * Treat only units that are connected with a ref id
     */
    public function checkOrgUnitRelations()
    {
        // collect the org units by their ilias ref_id
        $unitsById = $this->repo()->getOrgunits();
        $unitsByRefId = [];
        foreach ($this->repo()->getOrgunitsWithRefId() as $unit) {
            if (!empty($unit->getIliasRefId())) {
                $unitsByRefId[$unit->getIliasRefId()] = $unit;
            }
        }

        // check the org units with references for inconsistent paths
        foreach ($unitsByRefId as $ref_id => $unit) {

            // check the basic requirement for a relation: non-deleted category
            if (!\ilObject::_exists($ref_id, true)) {
                $this->repo()->save($unit->withProblem('ILIAS object does not exist'));
                continue;
            }
            if (\ilObject::_lookupType($ref_id, true) != 'cat') {
                $this->repo()->save($unit->withProblem('ILIAS object is not a category'));
                continue;
            }
            if (\ilObject::_isInTrash($ref_id, true)) {
                $this->repo()->save($unit->withProblem('ILIAS object is in trash'));
                continue;
            }

            // get the path to the unit by organisational relationship
            // add only org units with assigned ILIAS category
            $refsByOrg = [];
            foreach ($unit->getPathIds() as $path_org_id) {
                $pathUnit = $unitsById[$path_org_id];
                if ($pathUnit->getId() != 1 && $pathUnit->getId() != $unit->getId() && !empty($pathUnit->getIliasRefId())) {
                    $refsByOrg[] = $pathUnit->getIliasRefId();
                }
            }

            // get the path to the unit's category by ILIAS tree
            // add only ILIAS categories with assigned org unit
            $refsByTree = [];
            foreach ($this->dic->repositoryTree()->getPathId($ref_id) as $path_ref_id) {
                if ($path_ref_id != 1 && $path_ref_id != $unit->getIliasRefId() &&  !empty($unitsByRefId[$path_ref_id])) {
                    $refsByTree[] = $path_ref_id;
                }
            }

            // check if both reduced paths are the same
            if (implode('.', $refsByOrg) != implode('.', $refsByTree)) {
                $this->repo()->save($unit->withProblem(
                     "FAU parents: \n    " . implode("\n    ", $this->getOrgPathLog($unit)). "\n"
                    . "ILIAS parents: \n    " . implode("\n    ", $this->getIliasPathLog($unit))
                ));
            }
        }
    }
}