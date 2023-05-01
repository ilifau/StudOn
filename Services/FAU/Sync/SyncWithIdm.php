<?php

namespace FAU\Sync;


use FAU\Staging\Data\Identity;
use ilObjUser;
use ilCust;
use ilShibbolethRoleAssignmentRules;
use FAU\User\Data\Person;
use ILIAS\DI\Exceptions\Exception;

/**
 * Synchronisation of data coming from IDM
 * This will update data of the User service
 */
class SyncWithIdm extends SyncBase
{

    /**
     * Synchronize data (called by cron job)
     * Counted items are the persons
     */
    public function synchronize() : void
    {
        $this->syncPersonData();
    }

    /**
     * Synchronize the person data of all idm accounts
     * @see \ilAuthProviderSamlStudOn::findOrGenerateLogin()
     * @see \ilAuthProviderSamlStudOn::getUpdatedUser()
     */
    public function syncPersonData()
    {
        $this->info('syncPersonData...');
        foreach ($this->staging->repo()->getIdentities() as $identity) {
            $user_id = 0;

             // Try the identity as login
            if ($login = ilObjUser::_findLoginByField('login', $identity->getPkPersistentId())) {
                $user_id = (int) ilObjUser::_lookupId($login);
            }
            // Try the identity as external account
            else if ($login = ilObjUser::_findLoginByField('ext_account', $identity->getPkPersistentId())) {
                $user_id = (int) ilObjUser::_lookupId($login);
            }

            // update the found user
            if (!empty($user_id)) {
                $userObj = new ilObjUser($user_id);
                $this->info('UPDATE ' . $userObj->getFullname() . ' (' . $userObj->getLogin() .') ...') ;
                $this->applyIdentityToUser($identity, $userObj);
                $this->increaseItemsUpdated();
            }
        }
    }


    /**
     * Apply the basic IDM data to a user account
     * Note: the id must exist, external account and auth mode must be already set
     *
     * @param Identity $identity    data from the identity management
     * @param ilObjUser $userObj    ILIAS user object (already with id)
     */
    public function applyIdentityToUser(Identity $identity, ilObjUser $userObj)
    {
        // fields that are updated if they are set in the idm data
        if (!empty($identity->getGivenName())) {
            $userObj->setFirstname($identity->getGivenName());
        }
        if (!empty($identity->getSn())) {
            $userObj->setLastname($identity->getSn());
        }
        if (!empty($identity->getIliasGender())) {
            $userObj->setGender($identity->getIliasGender());
        }

        // always update the matriculation - this may delete an outdated one
        $userObj->setMatriculation($identity->getMatriculation());

        // don't overwrite an existing e-mail
        if (!empty($identity->getMail() && empty($userObj->getEmail()))) {
            $userObj->setEmail($identity->getMail());
        }

        // dependent system data
        $userObj->setFullname();
        $userObj->setTitle($userObj->getFullname());
        $userObj->setDescription($userObj->getEmail());

        // always update the account (this also updates the object title and description)
        $userObj->update();

        // update role assignments
        ilShibbolethRoleAssignmentRules::updateAssignments(
            $userObj->getId(), $identity->getShibbolethAttributes());

        // update or create the assigned person data
        $new = false;
        if (empty($person = $this->user->repo()->getPersonOfUser($userObj->getId()))) {
            $person = Person::model()->withUserId($userObj->getId());
            $new = true;
        }
        $person = $this->getPersonUpdate($person, $identity);
        $this->user->repo()->save($person);

        // always update the organisational roles of a person
        try {
            $this->sync->roles()->updateUserOrgRoles($person);
        }
        catch (Exception $e) {
            // ignore exception
        }

        // set the responsible or instructor roles for a newly created account
        // (update for existing users is done in the sync of courses and would be too time consuming here)
        if ($new) {
            $this->sync->roles()->applyNewUserCourseRoles($userObj->getId());
        }
    }



    /**
     * Get an updated person record (not yet saved)
     * @param Identity $identity
     * @param Person $person
     * @return Person
     */
    protected function getPersonUpdate(Person $person, Identity $identity) : Person
    {
        $studydata = array_merge(
            (array) json_decode($person->getStudydata(), true),
            $this->getStudydataByPeriod((array) json_decode($identity->getFauStudydata(), true)),
            $this->getStudydataByPeriod((array) json_decode($identity->getFauStudydataNext(), true)));

        // reformat the approval date to match the date datatype
        $doc_approval_date = null;
        if ((!empty($date = $identity->getFauDocApprovalDate()))) {
            $doc_approval_date = substr($date, 0, 4) . '-'
                . substr($date, 4, 2) . '-'
                . substr($date, 6, 2);
        }

        $person = new Person(
            $person->getUserId(),
            $identity->getFauCampoPersonId(),
            $identity->getFauEmployee(),
            $identity->getFauStudent(),
            $identity->getFauGuest(),
            $doc_approval_date,
            $identity->getFauDocProgrammesText(),
            $identity->getFauDocProgrammesCode(),
            json_encode($studydata),
            $identity->getFauOrgdata()
        );

        return $person;
    }

    /**
     * Nest a flat list of study data by the period of the studies
     * Add "P" as prefix for the
     *
     * @param $studydata
     * @return array
     */
    protected function getStudydataByPeriod($studydata) {

        $indexed = [];
        foreach ($studydata as $study) {
            if (isset($study['period'])) {
                $indexed['P' . $study['period']][] = $study;
            }
        }
        return $indexed;
    }
}

