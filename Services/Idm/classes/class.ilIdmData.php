<?php
/* fau: idmData - new class for idm data. */

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * Class ilIdmData
 *
 * @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
 */
class ilIdmData
{
    /**
     * @var string  fau identity (user account)
     */
    public $identity = '';

    /**
     * @var string  date of last change (mysql format)
     */
    public $last_change = '';

    /**
     * @var string  family name
     */
    public $lastname = '';

    /**
     * @var string  given name
     */
    public $firstname = '';

    /**
     * @var string  email address
     */
    public $email = '';

    /**
     * @var string  gender  ('m' or 'f')
     */
    public $gender = '';

    /**
     * @var string  coded password
     */
    public $coded_password = '';


    /**
     * @var string  matriculation number
     */
    public $matriculation = '';


    /**
     * @var array   affiliations ('employee', 'member', 'student', 'affiliate')
     */
    public $affiliations = array();

    /**
     * @var string  null | 'auto'   or a specific string
     */
    public $fau_employee = null;


    /**
     * @var string  null | 'auto'   or a specific string
     */
    public $fau_student = null;


    /**
     * @var string  null | 'auto'   or a specific string
     */
    public $fau_guest = null;


    /**
     * @var array   study data
     */
    public $studies = array();


    /**
     *
     * @param string    $identity
     */
    public function __construct($identity = null)
    {
        if (isset($identity))
        {
            $this->read($identity);
        }
    }


    /**
     * Read the identity data from the idm database
     * @param   string      $identity
     * @return  boolean
     */
    public function read($identity = null)
    {
        if (isset($identity))
        {
            $this->identity = $identity;
        }

        require_once ('Services/Idm/classes/class.ilDBIdm.php');
        $ilDBIdm = ilDBIdm::getInstance();
        if (!isset($ilDBIdm))
        {
            return false;
        }

        $query = "SELECT * FROM identities WHERE pk_persistent_id = ". $ilDBIdm->quote($this->identity,'text');
        $result = $ilDBIdm->query($query);
        if ($rawdata = $ilDBIdm->fetchAssoc($result))
        {
            $this->setRawData($rawdata);
            return true;
        }
        else
        {
            return false;
        }
    }


    /**
     * Set the properties from an array of raw data
     *
     * @param   array           raw data (assoc, names like columns of idm.identities)
     * @param   boolean         format is coming from shibboleth authentication
     */
    public function setRawData($raw, $fromShibboleth = false)
    {
        $this->identity = trim($raw['pk_persistent_id']);
        $this->last_change = $raw['last_change'];
        $this->lastname = $raw['sn'];
        $this->firstname = $raw['given_name'];
        $this->email = $raw['mail'];
        switch ($raw['schac_gender'])
        {
            // genders are differently coded in provisions by sso and database
            case '1':
                $this->gender = $fromShibboleth ? 'm' :'f';
                break;
            case '2':
                $this->gender = $fromShibboleth ? 'f': 'm';
               break;
            default:
                $this->gender = '';
                break;
        }
        $this->coded_password = $raw['user_password'];
        $this->affiliations = explode(';',$raw['unscoped_affiliation']);

        // matriculation
        $code = $raw['schac_personal_unique_code'];
        $pattern = 'uni-erlangen.de:Matrikelnummer:';
        $pos = strpos($code, $pattern);
        if ($pos !== false)
        {
            $this->matriculation = trim(substr($code,$pos+strlen($pattern)));
        }
        else{
            $this->matriculation = '';
        }

        // fau specific attributes
        $this->fau_employee = $raw['fau_employee'];
        $this->fau_student = $raw['fau_student'];
        $this->fau_guest = $raw['fau_guest'];

        // fau study data
        $this->studies = array();
        if ($raw['fau_features_of_study'])
        {
            $fau = explode("#", $raw['fau_features_of_study']);
            $i = 0;

            $matriculation = $fau[$i++];
            $ref_semester = $fau[$i++];

            for ($study = 1; $study <= 3; $study ++)
            {
                $studata = array();
                $studata['degree_id'] = $fau[$i++];
                if ($fromShibboleth)
                {
                    // old format provided with sso has semester per study
                    $semester = $fau[$i++];
                }
                $studata['school_id'] = $fau[$i++];
                $studata['ref_semester'] =  $ref_semester;

                for ($subject = 1; $subject <= 3; $subject++)
                {
                    $subdata = array();
                    $subdata['subject_id'] = $fau[$i++];
                    if ($fromShibboleth)
                    {
                        $subdata['semester'] = $semester;
                    }
                    else
                    {
                        // new format in database has semester per subject
                        $subdata['semester'] = $fau[$i++];
                    }

                    // this subjects is set
                    if ($subdata['subject_id'])
                    {
                        $studata['subjects'][] = $subdata;
                    }
                }

                // this study is set
                if ($studata['degree_id'])
                {
                    $this->studies[] = $studata;
                }
            }
        }
    }

    /**
     * Apply the basic IDM data to a user account
     * Note: the id must exist
     *
     * @param   ilObjUser   $userObj
     * @param   string      $mode   'create' or 'update'
     */
    public function applyToUser(ilObjUser $userObj, $mode = 'update')
    {
        global $ilSetting;

        // update the profile fields if auth mode is shibboleth
        if ($userObj->getAuthMode() == "shibboleth")
        {
            if (!empty($this->firstname)) {
                $userObj->setFirstname($this->firstname);
            }
            if (!empty($this->lastname)) {
                $userObj->setLastname($this->lastname);
            }
            if (!empty($this->gender)) {
                $userObj->setGender($this->gender);
            }
            if (!empty($this->email)
                and (is_null($userObj->getEmail()) || $userObj->getEmail() == '' || $userObj->getEmail() == $ilSetting->get('mail_external_sender_noreply')))
            {
                $userObj->setEmail($this->email);
            }
            if (!empty($this->coded_password)) {
                $userObj->setPasswd($this->coded_password, IL_PASSWD_SSHA);
            }

            // dependent system data
            $userObj->setFullname();
            $userObj->setTitle($userObj->getFullname());
            $userObj->setDescription($userObj->getEmail());
        }

        // always update external account and password
        $userObj->setExternalAccount($this->identity);
        $userObj->setExternalPasswd($this->coded_password);

        // time limit and activation
        if (ilCust::get('shib_create_limited'))
        {
            $limit = new ilDateTime(ilCust::get('shib_create_limited'), IL_CAL_DATE);
            $userObj->setTimeLimitUnlimited(0);
            $userObj->setTimeLimitFrom(time());
            $userObj->setTimeLimitUntil($limit->get(IL_CAL_UNIX));
        }
        else
        {
            $userObj->setTimeLimitUnlimited(1);
            $userObj->setTimeLimitFrom(time());
            $userObj->setTimeLimitUntil(time());
        }
        $userObj->setActive(1, 6);
        $userObj->setTimeLimitOwner(7);

        // always update matriculation number
        if (!empty($this->matriculation)) {
            $userObj->setMatriculation($this->matriculation);
        }

        // insert the user data if account is newly created
        if ($mode == 'create') {
            $userObj->saveAsNew();
        }

        // always update the account (this also updates the object title and description)
        $userObj->update();


        if (!empty($this->studies)) {

            require_once('Services/StudyData/classes/class.ilStudyData.php');
            ilStudyData::_saveStudyData($userObj->getId(), $this->studies);
        }

        // update role assignments
        require_once('Services/AuthShibboleth/classes/class.ilShibbolethRoleAssignmentRules.php');
        ilShibbolethRoleAssignmentRules::updateAssignments($userObj->getId(), (array) $this);
    }
}