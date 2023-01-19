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
     * Longtexts of of units where also the longtexts of all child units should be returned by getLongtextsOnIliasPath()
     * This is used for querying the educations of users that should be displayed or exported
     *
     * E.g. Educations of the "Sprachenzentrum" should also include educations of "Sprachenzentrum, Abteilung Fremdsprachenausbildung Nürnberg"
     * even if we are in a different sub tree of the StudOn category for "Sprachenzentrum"
     *
     * @var string[]
     */
    protected $longtexts_with_childs = [
        'Sprachenzentrum'
    ];

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
     * Get the longtexts of the org units that are found on the path of an ilias reference
     * @param int|null $ref_id
     * @return string[]
     * @see self::$longtexts_with_childs
     */
    public function getLongtextsOnIliasPath(?int $ref_id) : array
    {
        if (empty($ref_id)) {
            return [];
        }
        $texts = [];
        $path_ids = $this->dic->repositoryTree()->getPathId($ref_id);

        $units = $this->repo()->getOrgunitsByRefIds($path_ids);
        foreach ($units as $unit) {
            if (in_array($unit->getLongtext(), $this->longtexts_with_childs)) {
                $childs = $this->repo()->getOrgunitsByPath($unit->getPath());
                foreach ($childs as $child) {
                    $texts[] = $child->getLongtext();
                }
            }
            $texts[] = $unit->getLongtext();
        }
        return array_unique($texts);
    }
}