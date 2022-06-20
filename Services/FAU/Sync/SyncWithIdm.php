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

    }

    /**
     * Apply the basic IDM data to a user account
     * Note: the id must exist
     * @param Identity $identity    data from the identity management
     * @param ilObjUser $userObj    ILIAS user object (already with id)
     * @param bool $is_new          user object is newly created
     */
    public function applyToUser(Identity $identity, ilObjUser $userObj, $is_new = false)
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

        // time limit and activation
        if ($is_new) {
            // can be used in test platform for limited access
            if (ilCust::get('shib_create_limited')) {
                $userObj->setTimeLimitUnlimited(0);
                $userObj->setTimeLimitFrom(time() - 10);
                $userObj->setTimeLimitUntil($this->tools->dbDateToUnix(
                    ilCust::get('shib_create_limited')));
            } else {
                $userObj->setTimeLimitUnlimited(1);
                $userObj->setTimeLimitFrom(time());
                $userObj->setTimeLimitUntil(time());
            }
        }
        $userObj->setActive(1, 6);
        $userObj->setTimeLimitOwner(7);

        // insert the user data if account is newly created
        if ($is_new) {
            $userObj->saveAsNew();
        }

        // always update the account (this also updates the object title and description)
        $userObj->update();

        // update role assignments
        ilShibbolethRoleAssignmentRules::updateAssignments(
            $userObj->getId(), $identity->getShibbolethAttributes());

        // update or create the assigned person data
        if (empty($person = $this->user->repo()->getPersonOfUser($userObj->getId()))) {
            $person = Person::model()->withUserId($userObj->getId());
        }
        $person = $this->getPersonUpdate($person, $identity);
        $this->user->repo()->save($person);


        $this->updateOrgAccess($person);
    }

    /**
     * Todo: Update the access to categories of organisations
     */
    protected function updateOrgAccess(Person $person)
    {

    }


    /**
     * Get an updated person record (not yet saved)
     * @param Identity $identity
     * @param Person $person
     * @return Person
     */
    protected function getPersonUpdate(Person $person, Identity $identity) : Person
    {
        $studydata = json_decode($person->getStudydata(), true) ?? [];
        $fau_studydata = json_decode($identity->getFauStudydata(), true) ?? [];
        $fau_studydata_next = json_decode($identity->getFauStudydata(), true) ?? [];

        // merge the studydata
        // person keeps study data of older semesters
        if (isset($fau_studydata['period'])) {
            $studydata[$fau_studydata['period']] = $fau_studydata;
        }
        if (isset($fau_studydata_next['period'])) {
            $studydata[$fau_studydata_next['period']] = $fau_studydata_next;
        }

        // reformat the approval date to match the date datatype
        $doc_approval_date = null;
        if ((!empty($date = $identity->getFauDocApprovalDate()))) {
            $doc_approval_date = substr($date, 0, 4) . '-'
                . substr($date, 0, 4) . '-'
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
            $studydata,
            $identity->getFauOrgdata()
        );

        return $person;
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

