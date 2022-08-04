<?php

namespace FAU\Sync;


use FAU\Staging\Data\Identity;
use ilObjUser;
use ilCust;
use ilShibbolethRoleAssignmentRules;
use FAU\User\Data\Person;

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
     * @see \ilAuthProviderSamlStudOn::generateLogin()
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
     * Note: the id must exist
     * @param Identity $identity    data from the identity management
     * @param ilObjUser $userObj    ILIAS user object (already with id)
     */
    public function applyIdentityToUser(Identity $identity, ilObjUser $userObj)
    {
        // always update the matriculation number
        if (!empty($identity->getMatriculation())) {
            $userObj->setMatriculation($identity->getMatriculation());
        }

        // update the profile fields if auth mode is shibboleth
        if ($userObj->getAuthMode() == "shibboleth") {
            if (!empty($identity->getGivenName())) {
                $userObj->setFirstname($identity->getGivenName());
            }
            if (!empty($identity->getSn())) {
                $userObj->setLastname($identity->getSn());
            }
            if (!empty($identity->getIliasGender())) {
                $userObj->setGender($identity->getIliasGender());
            }
            if (!empty($identity->getUserPassword())) {
                $userObj->setPasswd($identity->getUserPassword(), IL_PASSWD_CRYPTED);
                if (substr($identity->getUserPassword(), 0, 6) == '{SSHA}') {
                    $userObj->setPasswordEncodingType('idmssha');
                } elseif (substr($identity->getUserPassword(), 0, 7) == '{CRYPT}') {
                    $userObj->setPasswordEncodingType('idmcrypt');
                }
            }
            // don't overwrite an existing e-mail
            if (!empty($identity->getMail() && empty($userObj->getEmail()))) {
                $userObj->setEmail($identity->getMail());
            }

            // dependent system data
            $userObj->setFullname();
            $userObj->setTitle($userObj->getFullname());
            $userObj->setDescription($userObj->getEmail());
        }

        // set the identity as external account for shibboleth authentication
        // if it is not already set by another account
        if (empty($userObj->getExternalAccount())) {
            if (empty(ilObjUser::_findLoginByField('ext_account', $identity->getPkPersistentId()))) {
                $userObj->setExternalAccount($identity->getPkPersistentId());
            }
        }

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
        $this->sync->roles()->updateUserOrgRoles($person);

        // set the responsible or instructor roles for a newly created account
        // (update for existing users is done in the sync of courses)
        if ($new) {
            $this->sync->roles()->updateUserParticipation($userObj->getId());
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


    /**
     * Get a dummy user object for local password verification
     * This user object must not be saved!
     */
    public function getDummyUserForLocalAuth(Identity $identity): ilObjUser
    {
        $userObj = new ilObjUser();
        $userObj->setFirstname($identity->getGivenName());
        $userObj->setLastname($identity->getSn());
        $userObj->setGender($identity->getIliasGender());
        $userObj->setEmail($identity->getMail());
        $userObj->setMatriculation($identity->getMatriculation());
        $userObj->setFullname(); // takes the previously set data
        $userObj->setTitle($userObj->getFullname());
        $userObj->setDescription($userObj->getEmail());

        $userObj->setPasswd($identity->getUserPassword(), IL_PASSWD_CRYPTED);
        if (substr($identity->getUserPassword(), 0, 6) == '{SSHA}') {
            $userObj->setPasswordEncodingType('idmssha');
        } elseif (substr($identity->getUserPassword(), 0, 7) == '{CRYPT}') {
            $userObj->setPasswordEncodingType('idmcrypt');
        }

        $userObj->setExternalAccount($identity->getPkPersistentId());
        $userObj->setActive(1, 6);

        return $userObj;
    }
}

