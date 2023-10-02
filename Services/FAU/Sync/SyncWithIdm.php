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
    const ROLE_USER = 4;
    const ROLE_GUEST = 5;
    
    
    /**
     * Synchronize data (called by cron job)
     * Counted items are the updated persons
     */
    public function synchronize() : void
    {
        $this-$this->migrateToLocal();
        $this->migrateToSSO();
        $this->syncPersonData();
    }

    /**
     * Synchronize the person data of all idm accounts
     * @see \ilAuthProviderSamlStudOn::findLogin()
     * @see \ilAuthProviderSamlStudOn::getUpdatedUser()
     */
    public function syncPersonData($uid = null)
    {
        $this->info('syncPersonData...');
        if (isset($uid)) {
            $identities = [$this->staging->repo()->getIdentity($uid)];
        }
        else {
            $identities = $this->staging->repo()->getIdentities();
        }
        
        foreach ($identities as $identity) {
            $user_id = 0;

            // Try the identity as external account
            if ($login = ilObjUser::_findLoginByField('ext_account', $identity->getPkPersistentId())) {
                $user_id = (int) ilObjUser::_lookupId($login);
            }

            // update the found user
            if (!empty($user_id)) {
                $userObj = new ilObjUser($user_id);
                $this->applyIdentityToUser($identity, $userObj);
                $this->increaseItemsUpdated();
            }
        }
        $this->info('Synced: ' . $this->getItemsUpdated());
    }



    /**
     * Migrate accounts from SSO to local authentication if no idm data is available
     * @param string|null $login
     * @return void
     * @throws \ilDateTimeException
     */
    public function migrateToLocal(string $login = null) 
    {
        $this->info('migrateToLocal...');
        
        $mail_template_de = file_get_contents(__DIR__ . '/templates/mail_migrate_local_de.html');
        $mail_template_en = file_get_contents(__DIR__ . '/templates/mail_migrate_local_en.html');

        $now = new \ilDateTime(time(), IL_CAL_UNIX);
        $limit = new \ilDate($now->get(IL_CAL_DATE), IL_CAL_DATE);
        $limit->increment(IL_CAL_YEAR, 1);

        $this->info("Set account limits to " . \ilDatePresentation::formatDate($limit));
        
        $persistent_ids = $this->staging->repo()->getPersistentIds();
        $users = $this->user->repo()->getUserDataWithSSO($login);

        $count = 0;
        foreach ($users as $userData) {
            
            if (in_array($userData->getExtAccount(), $persistent_ids)) {
                continue;
            }

            $this->info("MIGRATE " . $userData->getFirstname() . ' ' . $userData->getLastname() . ' (' . $userData->getLogin() . ')');
            $count++;
            
            $userObj = new ilObjUser($userData->getUserId());
            $userObj->setAuthMode('local');
            $userObj->setPasswd(random_bytes(20), IL_PASSWD_PLAIN);
            $userObj->setIdleExtAccount($userObj->getExternalAccount());
            $userObj->setExternalAccount(null);
            $userObj->setTimeLimitUnlimited(false);
            $userObj->setTimeLimitFrom($now->get(IL_CAL_UNIX));
            $userObj->setTimeLimitUntil($limit->get(IL_CAL_UNIX));
            $userObj->update();

            $this->dic->rbac()->admin()->assignUser(self::ROLE_GUEST, $userObj->getId());
            $this->dic->rbac()->admin()->deassignUser(self::ROLE_USER, $userObj->getId());

            $salutation = \ilMail::getSalutation($userObj->getId());
            $to = $userObj->getEmail();

            $body = $userObj->getLanguage() == 'de' ? $mail_template_de : $mail_template_en;
            $body = str_replace('{limit}', \ilDatePresentation::formatDate($limit), $body);
            $body = str_replace('{salutation}', $salutation, $body);
            $body = str_replace('{login}', $userObj->getLogin(), $body);

            $mail = new \ilMimeMail();
            $mail->To($userObj->getEmail());
            $mail->Subject(sprintf($this->dic->language()->txtlng('fau', 'sso_mail_migrate_local', $userObj->getLanguage()), $userObj->getLogin()));
            $mail->Body($body);
            $mail->From(new \ilMailMimeSenderSystem($this->dic->settings()));
            $mail->send();
        }
        $this->info("Migrated: " . $count);
    }

    /**
     * Migrate accounts from local authentication to SSO if idm data is available
     * @param string|null $login
     * @return void
     * @throws \ilDateTimeException
     */
    public function migrateToSSO(string $login = null)
    {
        $this->info('migrateToSSO...');
        
        $mail_template_de = file_get_contents(__DIR__ . '/templates/mail_migrate_sso_de.html');
        $mail_template_en = file_get_contents(__DIR__ . '/templates/mail_migrate_sso_en.html');
        
        $persistent_ids = $this->staging->repo()->getPersistentIds();
        if (empty($persistent_ids)) {
            // no or wrong connection to the idm database
            return;
        }
        $users = $this->user->repo()->getUserDataWithFormerSSO($login);

        $count = 0;
        foreach ($users as $userData) {

            if (!in_array($userData->getIdleExtAccount(), $persistent_ids)) {
                continue;
            }

            // prevent double use of external account
            if (ilObjUser::_checkExternalAuthAccount('shibboleth', $userData->getIdleExtAccount())) {
                $userObj = new ilObjUser($userData->getUserId());
                $userObj->setIdleExtAccount(null);
                $userObj->update();
                continue;
            }
            
            $this->info("MIGRATE " . $userData->getFirstname() . ' ' . $userData->getLastname() . ' (' . $userData->getLogin() . ')');
            $count++;

            $userObj = new ilObjUser($userData->getUserId());
            $userObj->setAuthMode('shibboleth');
            $userObj->setExternalAccount($userObj->getIdleExtAccount());
            $userObj->setIdleExtAccount(null);

            // reset agreement to force a new acceptance if user is not active
            if (!$userObj->getActive() || !$userObj->checkTimeLimit()) {
                $userObj->setAgreeDate(null);
            }

            // activate an inactive or timed out account 
            // it is assumed that all users with idm data are allowed to access studon
            $userObj->setActive(1, 6);
            $userObj->setTimeLimitUnlimited(true);
            $userObj->setTimeLimitOwner(7);
            $userObj->update();

            // apply the IDM data and update the user
            // this also sets the global role according to the shibboleth assignment rules
            $identity = $this->dic->fau()->staging()->repo()->getIdentity($userObj->getExternalAccount());
            $this->applyIdentityToUser($identity, $userObj);

            $salutation = \ilMail::getSalutation($userObj->getId());
            $to = $userObj->getEmail();

            $body = $userObj->getLanguage() == 'de' ? $mail_template_de : $mail_template_en;
            $body = str_replace('{salutation}', $salutation, $body);
            $body = str_replace('{login}', $userObj->getLogin(), $body);
            $body = str_replace('{ext_account}', $userObj->getExternalAccount(), $body);

            $mail = new \ilMimeMail();
            $mail->To($userObj->getEmail());
            $mail->Subject(sprintf($this->dic->language()->txtlng('fau', 'sso_mail_migrate_sso', $userObj->getLanguage()), $userObj->getLogin()));
            $mail->Body($body);
            $mail->From(new \ilMailMimeSenderSystem($this->dic->settings()));
            $mail->send();
        }
        $this->info("Migrated: " . $count);
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
        $has_new_person_id = false;
        if (empty($person = $this->user->repo()->getPersonOfUser($userObj->getId()))) {
            $person = Person::model()->withUserId($userObj->getId());
        }
        if ((int) $identity->getFauCampoPersonId() != (int) $person->getPersonId()) {
            $has_new_person_id = true;
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

        // set the responsible or instructor roles if a person_id is newly assigned
        // (update for existing users is done in the sync of courses and would be too time consuming here)
        if ($has_new_person_id) {
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
     * Add "P" as prefix for the index otherwise the index is treated as numeric which may cause problems
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

