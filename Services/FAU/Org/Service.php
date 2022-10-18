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
     * Get the shorttexts of the org units that are found on the path of an ilias reference
     * @param int|null $ref_id
     * @return string[]
     */
    public function getShorttextsOnIliasPath(?int $ref_id) : array
    {
        if (empty($ref_id)) {
            return [];
        }
        $texts = [];
        $path_ids = $this->dic->repositoryTree()->getPathId($ref_id);
        foreach ($this->repo()->getOrgunitsByRefIds($path_ids) as $unit) {
            $texts[] = $unit->getShorttext();
        }
        return $texts;
    }
}