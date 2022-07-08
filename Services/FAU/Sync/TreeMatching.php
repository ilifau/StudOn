<?php

namespace FAU\Sync;

use ILIAS\DI\Container;
use ilLanguage;
use ilObject;
use ilObjCategory;

use FAU\Study\Data\Course;
use FAU\Study\Data\Term;
use FAU\Study\Data\ImportId;
use FAU\Org\Data\Orgunit;
use FAU\Settings;
use ilContainer;

/**
 * Functions for matching the orgunit and repository tree
 * Used in the ilias synchronisation
 * @see SyncWithIlias
 */
class TreeMatching
{
    protected Container $dic;
    protected ilLanguage $lng;
    protected \FAU\Org\Service $org;
    protected \FAU\Study\Service $study;
    protected Settings $settings;


    /**
     * Constructor
     */
    public function __construct(Container $dic)
    {
        $this->dic = $dic;
        $this->lng = $dic->language();
        $this->org = $dic->fau()->org();
        $this->study = $dic->fau()->study();
        $this->settings = $dic->fau()->settings();
    }


    /**
     * Find or create the ILIAS category where the course for an event and term should be created
     * @return ?int ref_id of the created category of null if not possible
     */
    public function findOrCreateCourseCategory(Course $course, Term $term) : ?int
    {
        $creationUnit = null;

        // search for an org unit that allows course creation and has an ilias category assigned
        foreach ($this->study->repo()->getEventOrgunitsByEventId($course->getEventId()) as $unit) {
            if (empty($responsibleUnit = $this->org->repo()->getOrgunitByNumber($unit->getFauorgNr()))) {
                $this->study->repo()->save($course->withIliasProblem(
                    'Responsible org unit ' . $unit->getFauorgNr() . ' not found!'));
                continue; // next unit
            }
            if (empty($creationUnit = $this->findOrgUnitForCourseCreation($responsibleUnit))) {
                $this->org->repo()->save($responsibleUnit->withProblem(
                    "No org unit with ilias category found for course creation!\n    "
                    . implode("\n    ", $this->getOrgPathLog($responsibleUnit,true))
                ));
                continue;   // next unit
            }
            break;  // creationUnit found
        }
        // try fallback parent category
        if (empty($creationUnit)) {
            $this->study->repo()->save($course->withIliasProblem("No org unit found for course creation!"));
            $parent_ref_id = $this->settings->getFallbackParentCatId();
            if (empty($parent_ref_id)) {
                return null;
            }
        }
        // check if the assigned ilias reference is a category and not deleted
        elseif (ilObject::_lookupType($creationUnit->getIliasRefId(), true) != 'cat'
            || ilObject::_isInTrash($creationUnit->getIliasRefId())) {
            $this->study->repo()->save($creationUnit->withProblem('No ILIAS category found for the ref_id'));
            $this->study->repo()->save($course->withIliasProblem("No org unit found for course creation!"));
            return null;
        }
        else {
            $parent_ref_id = $creationUnit->getIliasRefId();
        }

        // find the sub category for course creation in the term
        foreach($this->dic->repositoryTree()->getChildsByType($parent_ref_id, 'cat') as $node) {
            if (ImportId::fromString(ilObject::_lookupImportId($node['obj_id']))->getTermId() == $term->toString()) {
                return (int) $node['child'];
            }
        }
        return  $this->createCourseCategory($parent_ref_id, $term, $creationUnit);
    }

    /**
     * Find an org unit in the path of a unit that should be used for course creation
     * If there is a parent with "collect courses" and an ILIAS ref_id assigned, take this one
     * Otherwise take the nearest ancestor with ref_id assigned and not "no_manager"
     */
    protected function findOrgUnitForCourseCreation(Orgunit $unit) : ?Orgunit
    {
        $found = null;

        // reverse: from unit to root
        $pathUnits = array_reverse($this->org->getPathUnits($unit));

        // take faculty as fallback (univ is last index)
        $fallback_index = count($pathUnits) - 2;
        foreach ($pathUnits as $index => $pathUnit) {

            // always take the highest collector if ilias object is assigned
            if (!empty($pathUnit->getIliasRefId())
                && $pathUnit->getCollectCourses()
            ) {
                $found = $pathUnit;
            }
            // take the nearest parent if possible
            elseif (!empty($pathUnit->getIliasRefId())
                && !$pathUnit->getNoManager()
                && empty($found)
            ) {
                $found = $pathUnit;
            }
            // take the faculty even if it has "no_manager"
            elseif(!empty($pathUnit->getIliasRefId())
                && $index == $fallback_index
                && empty($found)
            ) {
                $found = $pathUnit;
            }
        }
        return $found;
    }

    /**
     * Get the reference to the ilias course or group for a course
     */
    public function getIliasRefIdForCourse(Course $course) : ?int
    {
        if (!empty($course->getIliasObjId())) {
            foreach (ilObject::_getAllReferences($course->getIliasObjId()) as $ref_id) {
                if (!ilObject::_isInTrash($ref_id)) {
                    return $ref_id;
                }
            }
        }
        return null;
    }

    /**
     * Find the parent course of a group
     */
    public function findParentIliasCourse(int $ref_id) : ?int
    {
        foreach ($this->dic->repositoryTree()->getPathId($ref_id) as $path_id) {
            if (ilObject::_lookupType($path_id, true) == 'crs') {
                return $path_id;
            }
        }
        return null;
    }

    /**
     * Create the category hat should get new courses of a term
     */
    protected function createCourseCategory(int $parent_ref_id, Term $term, ?Orgunit $unit): int
    {
        $category = new ilObjCategory();
        $category->setTitle($this->lng->txtlng('fau', 'fau_campo_courses', 'de')
            . ': ' . $this->study->getTermTextForLang($term, 'de', true)
            . (isset($unit) ? ' [' . $unit->getShorttext() . ']' : '')
        );
        $category->setDescription($this->lng->txtlng('fau', 'fau_campo_courses_desc', 'de'));
        $category->setImportId(ImportId::fromObjects($term)->toString());
        $category->setOwner($this->settings->getDefaultOwnerId());
        $category->create();

        $trans = $category->getObjectTranslation();
        $trans->addLanguage('en',
            $this->lng->txtlng('fau', 'fau_campo_courses', 'en')
            . ': ' . $this->study->getTermTextForLang($term, 'en'),
            $this->lng->txtlng('fau', 'fau_campo_courses_desc', 'en'),
            false);
        $trans->save();

        $category->createReference();
        $category->putInTree($parent_ref_id);
        $category->setPermissions($parent_ref_id);
        ilContainer::_writeContainerSetting($category->getId(), "block_limit", 100);
        return $category->getRefId();
    }


    /**
     * Get lines with titles and links of the org unit path
     * @return string[]
     */
    public function getOrgPathLog(Orgunit $unit, $include_unit = false) : array
    {
        $list = [];
        foreach ($this->org->getPathUnits($unit) as $pathUnit) {
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
        $unitsById = $this->org->repo()->getOrgunits();
        $unitsByRefId = [];
        foreach ($this->org->repo()->getOrgunitsWithRefId() as $unit) {
            if (!empty($unit->getIliasRefId())) {
                $unitsByRefId[$unit->getIliasRefId()] = $unit;
            }
        }

        // check the org units with references for inconsistent paths
        foreach ($unitsByRefId as $ref_id => $unit) {

            // check the basic requirement for a relation: non-deleted category
            if (!\ilObject::_exists($ref_id, true)) {
                $this->org->repo()->save($unit->withProblem('ILIAS object does not exist'));
                continue;
            }
            if (\ilObject::_lookupType($ref_id, true) != 'cat') {
                $this->org->repo()->save($unit->withProblem('ILIAS object is not a category'));
                continue;
            }
            if (\ilObject::_isInTrash($ref_id)) {
                $this->org->repo()->save($unit->withProblem('ILIAS object is in trash'));
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
                $this->org->repo()->save($unit->withProblem(
                    "FAU parents: \n    " . implode("\n    ", $this->getOrgPathLog($unit)). "\n"
                    . "ILIAS parents: \n    " . implode("\n    ", $this->getIliasPathLog($unit))
                ));
            }
        }
    }

}