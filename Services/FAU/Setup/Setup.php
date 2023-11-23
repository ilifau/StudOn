<?php declare(strict_types=1);

namespace FAU\Setup;


use ilDBInterface;

/**
 * Setup for FAU integration
 * This works as a factory for the update step classes
 */
class Setup
{
    protected ilDBInterface $db;

    public function __construct(ilDBInterface $db)
    {
        $this->db = $db;
    }

    public static function instance(ilDBInterface $db) : self
    {
        return new self($db);
    }

    /**
     * Get the update steps for conditions data
     */
    public function cond() : FAUCondSteps
    {
        $steps = new FAUCondSteps();
        $steps->prepare($this->db);
        return $steps;
    }


    /**
     * Get the update steps for organisational data
     */
    public function org() : FAUOrgSteps
    {
        $steps = new FAUOrgSteps();
        $steps->prepare($this->db);
        return $steps;
    }

    /**
     * Get the update steps for study related data
     */
    public function study() : FAUStudySteps
    {
        $steps = new FAUStudySteps();
        $steps->prepare($this->db);
        return $steps;
    }

    /**
     * Get the update steps for user data
     */
    public function user() : FAUUserSteps
    {
        $steps = new FAUUserSteps();
        $steps->prepare($this->db);
        return $steps;
    }
}