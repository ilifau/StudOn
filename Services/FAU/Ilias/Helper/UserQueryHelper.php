<?php

namespace FAU\Ilias\Helper;
use ilUserQuery; 

/**
 * trait for providing additional ilUserQuery methods
 */
trait UserQueryHelper 
{
    // fau: userData - class variable for ref_id to filter educations
    private $educations_ref_id = null;
    // fau.
    
    // fau: userData - getter and setter for ref_id to filter educations
    /**
     * Set the ref_id to filter the list of educations
     */
    public function setEducationsRefId(?int $ref_id)
    {
        $this->educations_ref_id = $ref_id;
    }

    /**
     * Get the ref_id to filter the list of educations
     */
    public function getEducationsRefId() : ?int
    {
        return $this->educations_ref_id;
    }
    // fau.
}