<?php declare(strict_types=1);

namespace FAU\Org;

use ILIAS\DI\Container;

/**
 * Service for organisational data
 */
class Service
{
    protected Container $dic;
    protected Repository $repository;


    /**
     * Constructor
     */
    public function __construct(Container $dic)
    {
        $this->dic = $dic;
    }

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

            // get the path of unit by organisational relationship
            $refsByOrg = [];
            $listByOrg = [];
            foreach ($unit->getPathIds() as $path_org_id) {
                if ($path_org_id == 1 || $path_org_id == $unit->getId()) {
                    continue;
                }
                $pathUnit = $unitsById[$path_org_id];
                $text = $pathUnit->getLongtext() . ' [' . $pathUnit->getShorttext() . ']';

                if (!empty($pathUnit->getIliasRefId())) {
                    $text .= ' (https://studon.fau.de/' . $pathUnit->getIliasRefId() . ')';
                    $refsByOrg[] = $pathUnit->getIliasRefId();
                }
//                else {
//                    $refsByOrg[] = 0;     // to find missing parent relations
//                }
                $listByOrg[] = $text;
            }

            $refsByTree = [];
            $listByTree = [];
            foreach ($this->dic->repositoryTree()->getPathId($ref_id) as $path_ref_id) {
                if ($path_ref_id == 1 || $path_ref_id == $unit->getIliasRefId()) {
                    continue;
                }
                $listByTree[] = \ilObject::_lookupTitle(\IlObject::_lookupObjId($path_ref_id))
                    . ' (https://studon.fau.de/' . $path_ref_id . ')';

                if (isset($unitsByRefId[$path_ref_id])) {
                    $refsByTree[] = $path_ref_id;
                }
            }

            if (implode('.', $refsByOrg) != implode('.', $refsByTree)) {
                $problem =
                     "FAU parents: \n    " . implode("\n    ", $listByOrg). "\n"
                    . "ILIAS parents: \n    " . implode("\n    ", $listByTree);

                $this->repo()->save($unit->withProblem($problem));
                continue;
            }

            $this->repo()->save($unit->withProblem(null));
        }
    }
}