<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
*   fim: [univis] Storing of class dependent values in session
*/
class ilSessionValues
{

    /**
    * Constructor
    *
    * @param    string      identifier for the session values (e.g. gui class name)
    */
    public function __construct($a_identifier)
    {
        // initialize the SESSION array to store session variables
        if (!is_array($_SESSION[$a_identifier])) {
            $_SESSION[$a_identifier] = array();
        }
        $this->values =&$_SESSION[$a_identifier];
        
        //echo $a_identifier;
        //var_dump($this->values);
    }


    /**
    * Save a request value in sesion and return it
    * Slashes are stripped from the request value
    *
    * @param    string      name of the section in session values
    * @param    string      name of the GET or POST or variable
    * @return   mixed       value
    */
    public function saveRequestValue($a_section, $a_name)
    {
        $value = $this->getRequestValue($a_name);
        if (!isset($value)) {
            // treat a non-existing request value as empty string
            $value = '';
        }
        $this->setSessionValue($a_section, $a_name, $value);
        return $value;
    }


    /**
    * Get any value that is set (request or session or default)
    * Slashes are stripped from the request value
    *
    * @param    string      name of the section in session values
    * @param    string      name of the GET or POST or variable
    * @param    mixed       default value
    * @return   mixed       value
    */
    public function getAnyValue($a_section, $a_name, $a_default_value = null)
    {
        $value = $this->getRequestValue($a_name);

        if (isset($value)) {
            return $value;
        } else {
            return $this->getSessionValue($a_section, $a_name, $a_default_value);
        }
    }


    /**
    * Read a value that is either coming from GET, POST
    * Slashes are stripped from request value
    *
    * @param    string      name of the GET or POST or variable
    * @return   mixed       value or null if not found;
    */
    public function getRequestValue($a_name)
    {
        if (isset($_GET[$a_name])) {
            return ilUtil::stripSlashesRecursive($_GET[$a_name]);
        } elseif (isset($_POST[$a_name])) {
            return ilUtil::stripSlashesRecursive($_POST[$a_name]);
        } else {
            return null;
        }
    }


    /**
    * Get a value from the session variables
    *
    * @param    string      name of the section in session values
    * @param    string      name of the variable
    * @param    mixed       default value
    * @return   mixed       value
    */
    public function getSessionValue($a_section, $a_name, $a_default_value = null)
    {
        if (isset($this->values[$a_section][$a_name])) {
            return $this->values[$a_section][$a_name];
        } else {
            return $a_default_value;
        }
    }


    /**
     * Get a start date object from the session variables
     * The session value is an array as saved from a DurationInput field
     *
     * @param    string      name of the section in session values
     * @param    string      name of the variable
     * @param    integer     default date (unix timestamp)
     * @return
     */
    public function getSessionDurationStart($a_section, $a_name, $a_default_timestamp = 0)
    {
        global $ilUser;

        include_once('./Services/Calendar/classes/class.ilDateTime.php');

        $value = $this->getSessionValue($a_section, $a_name);

        if (true || !is_array($value)) {
            return new ilDateTime($a_default_timestamp, IL_CAL_UNIX);
        } else {
            return new ilDateTime($value['start'], IL_CAL_FKT_DATE, $ilUser->getTimeZone());
        }
    }

    /**
     * Get an end date object from the session variables
     * The session value is an array as saved from a DurationInput field
     *
     * @param    string      name of the section in session values
     * @param    string      name of the variable
     * @param    integer     default date (unix timestamp)
     * @return
     */
    public function getSessionDurationEnd($a_section, $a_name, $a_default_timestamp = 0)
    {
        global $ilUser;

        include_once('./Services/Calendar/classes/class.ilDateTime.php');

        $value = $this->getSessionValue($a_section, $a_name);

        if (true||!is_array($value)) {
            return new ilDateTime($a_default_timestamp, IL_CAL_UNIX);
        } else {
            return new ilDateTime($value['end'], IL_CAL_DATETIME, $ilUser->getTimeZone());
        }
    }


    /**
    * Get all session values from a section
    *
    * @param    string      name of the section in session values
    * @return   array       section (key => value)
    */
    public function getSessionValues($a_section)
    {
        if (is_array($this->values[$a_section])) {
            return $this->values[$a_section];
        } else {
            return array();
        }
    }


    /**
    * Set a value in the session variables
    *
    * @param    string      name of the section in session values
    * @param    string      name of the variable
    * @param    mixed       value
    */
    public function setSessionValue($a_section, $a_name, $a_value)
    {
        if (!isset($this->values[$a_section])) {
            $this->values[$a_section] = array();
        }

        $this->values[$a_section][$a_name] = $a_value;
    }


    /**
    * Delete all session values of a specific section
    *
    * @param    string      name of the section in session values
    */
    public function deleteSessionValues($a_section)
    {
        unset($this->values[$a_section]);
    }


    /**
    * Delete all session values
    */
    public function deleteAllSessionValues()
    {
        $this->values = array();
    }
}
