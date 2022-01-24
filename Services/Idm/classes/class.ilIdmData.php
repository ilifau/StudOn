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
     * @var ilStudyCourseData[]   study data
     */
    public $studies = [];

    /**
     * @var ilStudyDocData[]  doc program data
     */
    public $docdata = [];

    /**
     * @var ilDBIdm
     */
    protected $idmDB;

    /**
     * Constructor
     */
    public function __construct()
    {
        require_once('Services/Idm/classes/class.ilDBIdm.php');
        $this->idmDB = ilDBIdm::getInstance();

        ilStudyAccess::_requireData();
    }

    /**
     * Read the identity data from the idm database
     * @param string $identity
     * @return  boolean
     */
    public function read($identity = null)
    {
        if (isset($identity)) {
            $this->identity = $identity;
        }

        if (!isset($this->idmDB)) {
            return false;
        }

        $query = "SELECT * FROM identities WHERE pk_persistent_id = " . $this->idmDB->quote($this->identity, 'text');
        $result = $this->idmDB->query($query);
        if ($rawdata = $this->idmDB->fetchAssoc($result)) {
            $this->setRawData($rawdata);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Update the list of doc programmes
     * @return int count of written options
     */
    public function updateDocPrograms()
    {
        require_once "Services/StudyData/classes/class.ilStudyOptionDocProgram.php";

        if (!isset($this->idmDB)) {
            return 0;
        }

        $query = "SELECT * FROM doc_programmes";
        $result = $this->idmDB->query($query);

        $options = [];
        while ($row = $this->idmDB->fetchAssoc($result)) {
            $option = new ilStudyOptionDocProgram();
            $option->id = (int) $row['prog_code'];
            $option->title = (string) $row['prog_text'];
            if (!empty($row['prog_end_date']) && $row['prog_end_date'] != '9999-12-31 00:00:00') {
                try {
                    $option->end = new ilDateTime($row['prog_end_date'], IL_CAL_DATETIME);
                } catch (ilDateTimeException $e) {
                    $option->end = null;
                }
            }
            $options[] = $option;
        }

        if (count($options) > 0) {
            ilStudyOptionDocProgram::_delete();
            foreach ($options as $option) {
                $option->write();
            }
        }
        return count($options);
    }

    /**
     * Update the list of degrees
     * @return int count of written options
     */
    public function updateStudyDegrees()
    {
        require_once "Services/StudyData/classes/class.ilStudyOptionDegree.php";

        if (!isset($this->idmDB)) {
            return 0;
        }

        $query = "SELECT * FROM study_degrees";
        $result = $this->idmDB->query($query);

        $options = [];
        while ($row = $this->idmDB->fetchAssoc($result)) {
            $option = new ilStudyOptionDegree();
            $option->id = (int) $row['degree_id'];
            $option->title = (string) $row['degree_title'];
            $options[] = $option;
        }

        if (count($options) > 0) {
            ilStudyOptionDegree::_delete();
            foreach ($options as $option) {
                $option->write();
            }
        }
        return count($options);
    }

    /**
     * Update the list of schools
     * @return int count of written options
     */
    public function updateStudySchools()
    {
        require_once "Services/StudyData/classes/class.ilStudyOptionSchool.php";

        if (!isset($this->idmDB)) {
            return 0;
        }

        $query = "SELECT * FROM study_schools";
        $result = $this->idmDB->query($query);

        $options = [];
        while ($row = $this->idmDB->fetchAssoc($result)) {
            $option = new ilStudyOptionSchool();
            $option->id = (int) $row['school_id'];
            $option->title = (string) $row['school_title'];
            $options[] = $option;
        }

        if (count($options) > 0) {
            ilStudyOptionSchool::_delete();
            foreach ($options as $option) {
                $option->write();
            }
        }
        return count($options);
    }

    /**
     * Update the list of subjects
     * @return int count of written options
     */
    public function updateStudySubjects()
    {
        require_once "Services/StudyData/classes/class.ilStudyOptionSubject.php";

        if (!isset($this->idmDB)) {
            return 0;
        }

        $query = "SELECT * FROM study_subjects";
        $result = $this->idmDB->query($query);

        $options = [];
        while ($row = $this->idmDB->fetchAssoc($result)) {
            $option = new ilStudyOptionSubject();
            $option->id = (int) $row['subject_id'];
            $option->title = (string) $row['subject_title'];
            $options[] = $option;
        }

        if (count($options) > 0) {
            ilStudyOptionSubject::_delete();
            foreach ($options as $option) {
                $option->write();
            }
        }
        return count($options);
    }

    /**
     * Set the properties from an array of raw data
     * @param array           raw data (assoc, names like columns of idm.identities)
     * @param boolean         format is coming from shibboleth authentication
     */
    public function setRawData($raw, $fromShibboleth = false)
    {
        $this->identity = trim($raw['pk_persistent_id']);
        $this->last_change = $raw['last_change'];
        $this->lastname = $raw['sn'];
        $this->firstname = $raw['given_name'];
        $this->email = $raw['mail'];
        switch ($raw['schac_gender']) {
            // genders are differently coded in provisions by sso and database
            case '1':
                $this->gender = $fromShibboleth ? 'm' : 'f';
                break;
            case '2':
                $this->gender = $fromShibboleth ? 'f' : 'm';
                break;
            default:
                $this->gender = 'n';
                break;
        }
        $this->coded_password = $raw['user_password'];
        $this->affiliations = explode(';', $raw['unscoped_affiliation']);

        // matriculation
        $code = $raw['schac_personal_unique_code'];
        $pattern = 'uni-erlangen.de:Matrikelnummer:';
        $pos = strpos($code, $pattern);
        if ($pos !== false) {
            $this->matriculation = trim(substr($code, $pos + strlen($pattern)));
        } else {
            $this->matriculation = '';
        }

        // fau specific attributes
        $this->fau_employee = $raw['fau_employee'];
        $this->fau_student = $raw['fau_student'];
        $this->fau_guest = $raw['fau_guest'];

        // fau study data
        $this->studies = [];
        if ($raw['fau_features_of_study']) {
            $fau = explode("#", $raw['fau_features_of_study']);
            $i = 0;

            $matriculation = $fau[$i++];
            $ref_semester = $fau[$i++];

            for ($study = 1; $study <= 3; $study++) {
                $studata = new ilStudyCourseData();
                $studata->study_no = $study;
                $studata->degree_id = $fau[$i++];
                if ($fromShibboleth) {
                    // old format provided with sso has semester per study
                    $semester = $fau[$i++];
                }
                $studata->school_id = $fau[$i++];
                $studata->ref_semester = $ref_semester;
                $type = substr($raw['fau_studytype'], $study - 1, 1);
                if (!empty($type) && $type != '_') {
                    $studata->study_type = $type;
                }

                for ($subject = 1; $subject <= 3; $subject++) {
                    $subdata = new ilStudyCourseSubject();
                    $subdata->study_no = $study;
                    $subdata->subject_id = $fau[$i++];
                    if ($fromShibboleth) {
                        $subdata->semester = $semester;
                    } else {
                        // new format in database has semester per subject
                        $subdata->semester = $fau[$i++];
                    }

                    // this subjects is set
                    if ($subdata->subject_id) {
                        $studata->subjects[] = $subdata;
                    }
                }

                // this study is set
                if ($studata->degree_id) {
                    $this->studies[] = $studata;
                }
            }
        }

        // set data for structured doc programme
        $doc = new ilStudyDocData();
        if (!empty($raw['fau_doc_programmes_code']) && is_numeric($raw['fau_doc_programmes_code'])) {
            $doc->prog_id = (int) $raw['fau_doc_programmes_code'];
        }
        if ((!empty($raw['fau_doc_approval_date']))) {
            $year = substr($raw['fau_doc_approval_date'], 0, 4);
            $month = substr($raw['fau_doc_approval_date'], 4, 2);
            $day = substr($raw['fau_doc_approval_date'], 6, 2);
            try {
                $doc->prog_approval = new ilDate($year . '-' . $month . '-' . $day, IL_CAL_DATE);
            } catch (Exception $e) {
                $doc->prog_approval = null;
            }
        }
        if (!empty($doc->prog_id || !empty($doc->prog_approval))) {
            $this->docdata[] = $doc;
        }
    }

    /**
     * Apply the basic IDM data to a user account
     * Note: the id must exist
     * @param ilObjUser $userObj
     * @param string    $mode 'create' or 'update'
     * @throws ilDateTimeException
     */
    public function applyToUser(ilObjUser $userObj, $mode = 'update')
    {
        global $ilSetting;

        // update the profile fields if auth mode is shibboleth
        if ($userObj->getAuthMode() == "shibboleth") {
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
                and (is_null($userObj->getEmail()) || $userObj->getEmail() == '' || $userObj->getEmail() == $ilSetting->get('mail_external_sender_noreply'))) {
                $userObj->setEmail($this->email);
            }
            if (!empty($this->coded_password)) {
                $userObj->setPasswd($this->coded_password, IL_PASSWD_CRYPTED);
                if (substr($this->coded_password, 0, 6) == '{SSHA}') {
                    $userObj->setPasswordEncodingType('idmssha');
                } elseif (substr($this->coded_password, 0, 7) == '{CRYPT}') {
                    $userObj->setPasswordEncodingType('idmcrypt');
                }
            }

            // dependent system data
            $userObj->setFullname();
            $userObj->setTitle($userObj->getFullname());
            $userObj->setDescription($userObj->getEmail());
        }

        // set the identity as external account for shibboleth authentication
        // if it is not already set by another account
        if (empty($userObj->getExternalAccount())) {
            if (empty(ilObjUser::_findLoginByField('ext_account', $this->identity))) {
                $userObj->setExternalAccount( $this->identity);
            }
        }

        // time limit and activation
        if ($mode == 'create') {
            // can be used in test platform for limited access
            if (ilCust::get('shib_create_limited')) {
                $limit = new ilDateTime(ilCust::get('shib_create_limited'), IL_CAL_DATE);
                $userObj->setTimeLimitUnlimited(0);
                $userObj->setTimeLimitFrom(time() - 10);
                $userObj->setTimeLimitUntil($limit->get(IL_CAL_UNIX));
            } else {
                $userObj->setTimeLimitUnlimited(1);
                $userObj->setTimeLimitFrom(time());
                $userObj->setTimeLimitUntil(time());
            }
        }
        $userObj->setActive(1, 6);
        $userObj->setTimeLimitOwner(7);

        // always update the matriculation number
        if (!empty($this->matriculation)) {
            $userObj->setMatriculation($this->matriculation);
        }

        // insert the user data if account is newly created
        if ($mode == 'create') {
            $userObj->saveAsNew();
        }

        // always update the account (this also updates the object title and description)
        $userObj->update();

        // save study data only if they are delivered
        if (!empty($this->studies)) {
            ilStudyCourseData::_delete($userObj->getId());
            foreach ($this->studies as $study) {
                $study->user_id = $userObj->getId();
                foreach ($study->subjects as $subject) {
                    $subject->user_id = $userObj->getId();
                }
                $study->write();
            }
        }

        // always save the doc programmes data
        ilStudyDocData::_delete($userObj->getId());
        foreach ($this->docdata as $doc) {
            $doc->user_id = $userObj->getId();
            $doc->write();
        }

        // update role assignments
        require_once('Services/AuthShibboleth/classes/class.ilShibbolethRoleAssignmentRules.php');
        ilShibbolethRoleAssignmentRules::updateAssignments($userObj->getId(), (array) $this);
    }

    /**
     * Get a dummy user for password verification
     * @return ilObjUser
     */
    public function getDummyUser()
    {
        $userObj = new ilObjUser();
        $userObj->setFirstname($this->firstname);
        $userObj->setLastname($this->lastname);
        $userObj->setGender($this->gender);
        $userObj->setEmail($this->email);
        $userObj->setMatriculation($this->matriculation);
        $userObj->setFullname();
        $userObj->setTitle($userObj->getFullname());
        $userObj->setDescription($userObj->getEmail());

        $userObj->setPasswd($this->coded_password, IL_PASSWD_CRYPTED);
        if (substr($this->coded_password, 0, 6) == '{SSHA}') {
            $userObj->setPasswordEncodingType('idmssha');
        } elseif (substr($this->coded_password, 0, 7) == '{CRYPT}') {
            $userObj->setPasswordEncodingType('idmcrypt');
        }

        $userObj->setExternalAccount($this->identity);
        $userObj->setActive(1, 6);

        return $userObj;
    }
}
