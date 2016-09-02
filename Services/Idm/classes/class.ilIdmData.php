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
            $this->identity = $identity;
            $this->read();
        }
    }


    /**
     * Read the identity data from the idm database
     * @return boolean
     */
    public function read()
    {
        require_once ('Services/Idm/classes/class.ilDBIdm.php');
        $ilDBIdm = ilDBIdm::getInstance();

        $query = "SELECT * FROM identities WHERE pk_persistent_id = ". $ilDBIdm->quote($this->identity,'text');
        $result = $ilDBIdm->query($query);
        if ($rawdata = $ilDBIdm->fetchAssoc($result))
        {
            $this->setRawData($rawdata);
            return true;
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

        // study data
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
}