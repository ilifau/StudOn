<?php declare(strict_types=1);

namespace FAU\Setup;

use ILIAS\DI\Container;

/**
 * Setup Service for FAU integration
 * This works as a factory for the update step classes
 */
class Service
{
    protected Container $dic;

    public function __construct(Container $dic)
    {
        $this->dic = $dic;
    }

    /**
     * Get the update steps for conditions data
     */
    public function cond() : FAUCondSteps
    {
        $steps = new FAUCondSteps();
        $steps->prepare($this->dic->database());
        return $steps;
    }


    /**
     * Get the update steps for organisational data
     */
    public function org() : FAUOrgSteps
    {
        $steps = new FAUOrgSteps();
        $steps->prepare($this->dic->database());
        return $steps;
    }

    /**
     * Get the update steps for study related data
     */
    public function study() : FAUStudySteps
    {
        $steps = new FAUStudySteps();
        $steps->prepare($this->dic->database());
        return $steps;
    }

    /**
     * Get the update steps for user data
     */
    public function user() : FAUUserSteps
    {
        $steps = new FAUUserSteps();
        $steps->prepare($this->dic->database());
        return $steps;
    }



}