<?php declare(strict_types=1);

namespace FAU\User;

use ILIAS\DI\Container;

/**
 * Service for user related data
 */
class Service
{
    /**
     * @var Container
     */
    protected $dic;

    /** @var Repository */
    protected $repository;


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
            $this->repository = new Repository($this->dic->database());
        }
        return $this->repository;
    }

    /**
     * Get the Migration Handler
     */
    public function migration() : Migration
    {
        return new Migration($this->dic->database());
    }

    /**
     * Get the educations of a user as text
     * An education is given as Title: Text
     * Educations are separated by newlines
     */
    public function getEducationsAsText(int $user_id) : string
    {
        $texts = [];
        foreach ($this->repo()->getEducationsOfUser($user_id) as $education) {
            $texts[] = $education->getTitle() . ': ' . $education->getText();
        }
        return implode("\n", $texts);
    }
}