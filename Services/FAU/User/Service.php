<?php declare(strict_types=1);

namespace FAU\User;

use ILIAS\DI\Container;

/**
 * Service for user related data
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

    /**
     * Find the Id of a studOn user by the IDM id
     */
    public function findUserIdByIdmUid(string $idm_uid) : ?int
    {
       if ($id = \ilObjUser::_findUserIdByAccount($idm_uid)) {
           return (int) $id;
       }
       return null;
    }
}