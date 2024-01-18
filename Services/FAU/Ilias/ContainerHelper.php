<?php

namespace FAU\Ilias;

/**
 * trait for providing additional ilContainer methods
 */
trait ContainerHelper 
{
    
    // fau: paraSub - functions for checking parallel group relationships
    /**
     * Check if the object is a parallel group
     */
    public function isParallelGroup()
    {
        if (!isset($this->is_parallel_group)) {
            if ($this->type != 'grp') {
                $this->is_parallel_group = false;
            }
            else {
                $importId = \FAU\Study\Data\ImportId::fromString($this->getImportId());
                $this->is_parallel_group = $importId->isForCampo();
            }
        }
        return  $this->is_parallel_group;
    }


    /**
     * Check if the object is a parallel group
     */
    public function hasParallelGroups()
    {
        if (!isset($this->has_parallel_groups)) {
            if ($this->type != 'crs') {
                $this->has_parallel_groups = false;
            }
            else {
                $importId = \FAU\Study\Data\ImportId::fromString($this->getImportId());
                $this->has_parallel_groups = !empty($importId->getEventId()) && empty($importId->getCourseId());
            }
        }
        return  $this->has_parallel_groups;
    }
    // fau.
}