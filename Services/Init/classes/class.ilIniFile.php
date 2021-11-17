<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
* INIFile Parser
*
* Early access in init proceess!
* Avoid further dependencies like logging or other services
*
* Description:
*
* A Simpe Ini File Implementation to keep settings
* in a simple file instead of in a DB
* Based upon class.INIfile.php by Mircho Mirev <mircho@macropoint.com>
*
* Usage Examples:
* $ini = new IniFile("./ini.ini");
* Read entire group in an associative array
* $grp = $ini->read_group("MAIN");
* //prints the variables in the group
* if ($grp)
* for(reset($grp); $key=key($grp); next($grp))
* {
* echo "GROUP ".$key."=".$grp[$key]."<br>";
* }
* //set a variable to a value
* $ini->setVariable("NEW","USER","JOHN");
* //Save the file
* $ini->save_data();
*
* @author Mircho Mirev <mircho@macropoint.com>
* @author Peter Gabriel <peter@gabriel-online.net>
* @version $Id$
*
*/
class ilIniFile
{
    /**
    * name of file
    * @var string
    * @access public
    */
    public $INI_FILE_NAME = "";

    /**
    * error var
    * @var string
    * @access public
    */
    public $ERROR = "";

    /**
    * sections in ini-file
    * @var array
    * @access public
    */
    public $GROUPS = array();

    /**
    * actual section
    * @var string
    * @access public
    */
    public $CURRENT_GROUP = "";

    /**
    * Constructor
    * @access	public
    * @param	string		name of file to be parsed
    * @return	boolean
    */
    public function __construct($a_ini_file_name)
    {
        //check if a filename is given
        if (empty($a_ini_file_name)) {
            $this->error("no_file_given");
            return false;
        }

        $this->INI_FILE_NAME = $a_ini_file_name;
        return true;
    }

    /**
    * read from ini file
    * @access	public
    * @return	boolean
    */
    public function read()
    {
        //check if file exists
        if (!file_exists($this->INI_FILE_NAME)) {
            $this->error("file_does_not_exist");
            return false;
        } else {
            //parse the file
            if ($this->parse() == false) {
                return false;
            }
        }

        return true;
    }

    /**
    * load and parse an inifile
    * @access	private
    * @return	boolean
    */
    public function parse()
    {
        //use php4 function parse_ini_file
        $this->GROUPS = @parse_ini_file($this->INI_FILE_NAME, true);

        //check if groups are filled
        if ($this->GROUPS == false) {
            // fau: fixIniFile - don't try to fix the ini file !
            // this may increase the size of the file each time called
            // when the problem is not caused by the php tag
            return false;
            // fau.
            
            // second try
            $this->fixIniFile();
            
            $this->GROUPS = @parse_ini_file($this->INI_FILE_NAME, true);
            if ($this->GROUPS == false) {
                $this->error("file_not_accessible");
                return false;
            }
        }
        //set current group
        $temp = array_keys($this->GROUPS);
        $this->CURRENT_GROUP = $temp[count($temp) - 1];
        return true;
    }

    /**
     * Fix ini file (make it compatible for PHP 5.3)
     */
    public function fixIniFile()
    {
        // first read content
        $lines = array();
        $fp = @fopen($this->INI_FILE_NAME, "r");
        while (!feof($fp)) {
            $l = fgets($fp, 4096);
            $skip = false;
            if ((substr($l, 0, 2) == "/*" && $starttag) ||
                substr($l, 0, 5) == "*/ ?>") {
                $skip = true;
            }
            $starttag = false;
            if (substr($l, 0, 5) == "<?php") {
                $l = "; <?php exit; ?>";
                $starttag = true;
            }
            if (!$skip) {
                $l = str_replace("\n", "", $l);
                $l = str_replace("\r", "", $l);
                $lines[] = $l;
            }
        }
        fclose($fp);
        
        // now write it back
        $fp = @fopen($this->INI_FILE_NAME, "w");
        
        if (!empty($fp)) {
            foreach ($lines as $l) {
                fwrite($fp, $l . "\r\n");
            }
        }
        fclose($fp);
    }
    
    /**
    * save ini-file-data to filesystem
    * @access	private
    * @return	boolean
    */
    public function write()
    {
        $fp = @fopen($this->INI_FILE_NAME, "w");
        
        if (empty($fp)) {
            $this->error("Cannot create file $this->INI_FILE_NAME");
            return false;
        }
        
        //write php tags (security issue)
        $result = fwrite($fp, "; <?php exit; ?>\r\n");

        $groups = $this->readGroups();
        $group_cnt = count($groups);
        
        for ($i = 0; $i < $group_cnt; $i++) {
            $group_name = $groups[$i];
            //prevent empty line at beginning of ini-file
            if ($i == 0) {
                $res = sprintf("[%s]\r\n", $group_name);
            } else {
                $res = sprintf("\r\n[%s]\r\n", $group_name);
            }
            
            $result = fwrite($fp, $res);
            $group = $this->readGroup($group_name);
            
            for (reset($group); $key = key($group);next($group)) {
                $res = sprintf("%s = %s\r\n", $key, "\"" . $group[$key] . "\"");
                $result = fwrite($fp, $res);
            }
        }
        
        
        fclose($fp);

        return true;
    }

    /**
    * returns the content of IniFile
    * @access	public
    * @return	string		content
    */
    public function show()
    {
        $groups = $this->readGroups();
        $group_cnt = count($groups);
        
        //clear content
        $content = "";
        
        // go through all groups
        for ($i = 0; $i < $group_cnt; $i++) {
            $group_name = $groups[$i];
            //prevent empty line at beginning of ini-file
            if ($i == 0) {
                $content = sprintf("[%s]\n", $group_name);
            } else {
                $content .= sprintf("\n[%s]\n", $group_name);
            }

            $group = $this->readGroup($group_name);
            
            //go through group an display all variables
            for (reset($group); $key = key($group);next($group)) {
                $content .= sprintf("%s = %s\n", $key, $group[$key]);
            }
        }

        return $content;
    }
    
    /**
    * returns number of groups
    * @access	public
    * @return	integer
    */
    public function getGroupCount()
    {
        return count($this->GROUPS);
    }
    
    /**
    * returns an array with the names of all the groups
    * @access	public
    * @return	array	groups
    */
    public function readGroups()
    {
        $groups = array();

        for (reset($this->GROUPS);$key = key($this->GROUPS);next($this->GROUPS)) {
            $groups[] = $key;
        }

        return $groups;
    }
    
    /**
    * checks if a group exists
    * @access	public
    * @param	string		group name
    * @return	boolean
    */
    public function groupExists($a_group_name)
    {
        if (!isset($this->GROUPS[$a_group_name])) {
            return false;
        }
        
        return true;
    }
    
    /**
    * returns an associative array of the variables in one group
    * @access	public
    * @param	string		group name
    * @return	mixed		return array of values or boolean 'false' on failure
    */
    public function readGroup($a_group_name)
    {
        if (!$this->groupExists($a_group_name)) {
            $this->error("Group '" . $a_group_name . "' does not exist");
            return false;
        }
        
        return $this->GROUPS[$a_group_name];
    }
    
    /**
    * adds a new group
    * @access	public
    * @param	string		group name
    * @return	boolean
    */
    public function addGroup($a_group_name)
    {
        if ($this->groupExists($a_group_name)) {
            $this->error("Group '" . $a_group_name . "' exists");
            return false;
        }

        $this->GROUPS[$a_group_name] = array();
        return true;
    }
    
    /**
    * removes a group
    * @access	public
    * @param	string		group name
    * @return	boolean
    */
    public function removeGroup($a_group_name)
    {
        if (!$this->groupExists($a_group_name)) {
            $this->error("Group '" . $a_group_name . "' does not exist");
            return false;
        }

        unset($this->GROUPS[$a_group_name]);
        return true;
    }

    /**
    * returns if a variable exists or not
    * @access	public
    * @param	string		group name
    * @param	string		value
    * @return	mixed		return true if value exists or false
    */
    public function variableExists($a_group, $a_var_name)
    {
        return isset($this->GROUPS[$a_group][$a_var_name]);
    }
    
    
    /**
    * reads a single variable from a group
    * @access	public
    * @param	string		group name
    * @param	string		value
    * @return	mixed		return value string or boolean 'false' on failure
    */
    public function readVariable($a_group, $a_var_name)
    {
        // fau: clientByUrl - dynamically get http_path and default client
        if ($a_group == 'server' && $a_var_name == 'http_path') {
            return $this->getHttpPath();
        }

        if ($a_group == 'clients' && $a_var_name == 'default') {
            return $this->getClient();
        }
        // fau.

        if (!isset($this->GROUPS[$a_group][$a_var_name])) {
            $this->error("'" . $a_var_name . "' does not exist in '" . $a_group . "'");
            return false;
        }
        
        return trim($this->GROUPS[$a_group][$a_var_name]);
    }
    
    /**
    * sets a variable in a group
    * @access	public
    * @param	string
    * @param	string
    * @param	string
    * @return	boolean
    */
    public function setVariable($a_group_name, $a_var_name, $a_var_value)
    {
        if (!$this->groupExists($a_group_name)) {
            $this->error("Group '" . $a_group_name . "' does not exist");
            return false;
        }
        
        $this->GROUPS[$a_group_name][$a_var_name] = $a_var_value;
        return true;
    }
    
    /**
    * set error message
    * @access	public
    * @param	string
    */
    public function error($a_errmsg)
    {
        $this->ERROR = $a_errmsg;

        return true;
    }
    
    /**
    * returns error
    * @access	public
    * @return	string
    */
    public function getError()
    {
        return $this->ERROR;
    }

    // fau: clientByUrl - new functions
    /**
     * Get the client from the ilias.ini
     *
     * Check the requested url against the patterns defined in [client_urls].
     * Use the first client where the url matches the pattern.
     * If no client matches, use the default from [clients].
     *
     * Example of the ini group:
     * [client_urls]
     * StudOn[] = "https://www.studon.fau.de/studon/"
     * StudOn[] = "/studon/"
     * StudOnTest[] = "https://www.studon.fau.de/studon-test/"
     * StudOnTest[] = "/studon-test/"
     * StudOnTest[] = "/test/"
     *
     * The first pattern for each client should be a full url to be used for getHttpPath().
     * Please note the trailing slash at the end of the patterns to clearly match the directory names.
     *
     * @return string
     * @see self::urlMatches()
     */
    public function getClient()
    {
        if (isset($this->GROUPS['client_urls'])) {
            $request = [
                'host' => $_SERVER['SERVER_NAME'],
                'port' => $_SERVER['SERVER_PORT'],
                'path' => $_SERVER['SCRIPT_NAME']
            ];

            foreach ((array) $this->GROUPS['client_urls'] as $client => $patterns) {
                foreach ((array) $patterns as $pattern) {
                    $condition = (array) parse_url($pattern);
                    if ($this->urlMatches($request, $condition)) {
                        return $client;
                    }
                }
            }
        }

        if (isset($this->GROUPS['clients']['default'])) {
            return trim($this->GROUPS['clients']['default']);
        }

        return '';
    }

    /**
     * Get the http_path from the ilias.ini
     *
     * If a CLIENT_ID is defined or of client can be determined with getClient(), then
     * use the first url defined in the group [client_urls] for this client (which should be a full url).
     * Otherwise use the http_path in the group [server].
     * A trailing slash will be removed from the end.
     *
     * @return string
     * @see self::getClient()
     */
    public function getHttpPath()
    {
        if (defined('CLIENT_ID')) {
            $client = CLIENT_ID;
        }
        else {
            $client = $this->getClient();
        }

        // client specific url (first of list)
        if (isset($this->GROUPS['client_urls'][$client])) {
           $urls = (array) $this->GROUPS['client_urls'][$client];
           return ($this->removeTrailingSlashes(trim($urls[0])));
        }

        // default url
        if (isset($this->GROUPS['server']['http_path'])) {
            return ($this->removeTrailingSlashes(trim($this->GROUPS['server']['http_path'])));
        }
    }

    /**
     * Check if a request matches a given condition
     * The request and condition are arrays like produced by parse url
     * A request matches, if every existing part in the condition is matched by the corresponding part in the request
     * A required host or port must match completely
     * A required path must match the beginning of the path in the url
     * The scheme, user, pass, query and fragment are ignored
     *
     * @param array $request
     * @param array $condition
     * @return bool
     */
    protected function urlMatches($request, $condition)
    {
        foreach ($condition as $part => $value) {
            switch ($part) {
                case "host":
                case "port":
                    if (!isset($request[$part]) || $request[$part] != $value) {
                        return false;
                    }
                    break;
                case "path":
                    if (!isset($request[$part]) || substr($request[$part], 0, strlen($value)) != $value) {
                        return false;
                    }
                    break;
            }
        }
        return true;
    }

    /**
     * Remove trailing slashes from a url or path
     *
     * @param string $path
     * @return string
     */
    protected function removeTrailingSlashes($path)
    {
        return preg_replace("/[\/\\\]+$/", "", $path);
    }
    // fau.

} //END class.ilIniFile
