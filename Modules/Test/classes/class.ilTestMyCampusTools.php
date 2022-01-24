<?php
// fau: campusGrades - new class ilTestMyCampusTools.

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* Tools for my campus export of test results.
*/
class ilTestMyCampusTools
{
    private $test_obj = null;
    private $options = array();
    
    private $registrations = array();
    private $check_registrations = false;
    
    private $csv_delimiter = ";";
    
    /**
     * constructor
     *
     * @param object	related test object
     */
    public function __construct($a_object)
    {
        $this->test_obj = $a_object;
        $this->readOptions();
    }
    
    /**
     * init the mycampus options for this test
     */
    public function initOptions()
    {
        $this->options = array();
        $this->options['exam_number'] = '';
        $this->options['rating_type'] = '';			// marked | not_marked
        $this->options['mark_field'] = 'short';		// short | official
        $this->options['other_fields'] = '';		// firstname,lastname,datemstarttime
    }
    
    
    /**
     * read the mycampus options for this test from the database
     */
    public function readOptions()
    {
        global $ilDB;
        
        $this->initOptions();
        
        $query = 'SELECT * FROM tst_mycampus_options WHERE obj_id='
            . $ilDB->quote($this->test_obj->getId(), 'integer');
        $result = $ilDB->query($query);
        
        while ($row = $ilDB->fetchAssoc($result)) {
            $this->options[$row['option_key']] = $row['option_value'];
        }
    }
    
    
    /**
     * write the mycampus options for this test to the datebase
     */
    public function writeOptions()
    {
        global $ilDB;
        
        $query = 'DELETE FROM tst_mycampus_options WHERE obj_id='
            . $ilDB->quote($this->test_obj->getId(), 'integer');
        
        $ilDB->manipulate($query);
        
        if (count($this->options)) {
            $insert = array();
            foreach ($this->options as $key => $value) {
                $insert[] = array($key, $value);
            }
            
            $query = 'INSERT INTO tst_mycampus_options(obj_id, option_key, option_value) VALUES ('
                . $ilDB->quote($this->test_obj->getId(), 'integer') . ', ?, ?)';
    
            $query = $ilDB->prepareManip($query, array('text', 'text'));
            $ilDB->executeMultiple($query, $insert);
        }
    }
    
    
    /**
     * get an option value
     *
     * @param 	string	key
     * @return 	string	value
     */
    public function getOption($a_key)
    {
        return $this->options[$a_key];
    }
    
    
    /**
     * set an option value
     *
     * @param 	string	key
     * @param 	string	value
     */
    public function setOption($a_key, $a_value)
    {
        $this->options[$a_key] = $a_value;
    }

    /**
     * check an uploaded registrations file
     *
     * @param	string	full path to an uploaded file
     * @return	string	error message or empty
     */
    public function checkRegistrationsFile($a_file)
    {
        global $lng;
        
        if (!is_file($a_file)) {
            return "";
        }
        
        $fp = fopen($a_file, "r");
        
        // get the index of the column with the matriculation number
        $titles = fgetcsv($fp, 0, $this->csv_delimiter);
        
        $matcol = null;
        foreach ($titles as $column => $title) {
            switch (trim(strtolower($title))) {
                case "mtknr":
                    $matcol = $column;
                    break;
                
                case "vorname":
                    $firstnamecol = $column;
                    break;
                
                case "nachname":
                    $lastnamecol = $column;
                    break;
            }
        }
        
        // return error if matriculation column is not found
        if (!isset($matcol)) {
            return $lng->txt("ass_mycampus_matcol_not_found");
        }
        
        // read the matriculations
        while (($data = fgetcsv($fp, 0, $this->csv_delimiter)) !== false) {
            if (trim($data[$matcol])) {
                $this->registrations[trim($data[$matcol])] = array(
                    "matriculation" => trim($data[$matcol]),
                    "firstname" => utf8_encode(trim($data[$firstnamecol])),
                    "lastname" => utf8_encode(trim($data[$lastnamecol]))
                    );
            }
        }
        $this->check_registrations = true;
        
        fclose($fp);
    }
    
    /**
     * create the export files for my campus
     */
    public function createExportFiles()
    {
        global $lng;
        
        // prepare the checking of registrations
        // this array is reduced by every found participant
        if ($this->check_registrations) {
            $registrations = $this->registrations;
        }
        
        $other_fields = explode(',', $this->getOption('other_fields'));
        
        // build the header row
        $header = array();
        $header[] = 'mtknr';
        if (in_array('lastname', $other_fields)) {
            $header[] = 'nachname';
        }
        if (in_array('firstname', $other_fields)) {
            $header[] = 'vorname';
        }
        $header[] = 'bewertung';
        if (in_array('date', $other_fields)) {
            $header[] = 'pdatum';
        }
        if (in_array('starttime', $other_fields)) {
            $header[] = 'pbeginn';
        }
        
        // build the date rows
        $rows = array();
        $data = &$this->test_obj->getCompleteEvaluationData(true, '', '');
        foreach ($data->getParticipants() as $active_id => $participant) {
            $row = array();
            $userfields = ilObjUser::_lookupFields($participant->getUserID());
            
            // check if the matriculation should be exported
            if ($this->check_registrations) {
                if (isset($registrations[$userfields['matriculation']])) {
                    // remove a found participant
                    unset($registrations[$userfields['matriculation']]);
                } else {
                    // don't export the participant
                    continue;
                }
            }
            
            $row[] = $userfields['matriculation'];
            if (in_array('lastname', $other_fields)) {
                $row[] = $userfields['lastname'];
            }
            if (in_array('firstname', $other_fields)) {
                $row[] = $userfields['firstname'];
            }
            if ($this->getOption('rating_type') == 'marked') {
                if ($this->getOption('mark_field') == 'short') {
                    $row[] = $participant->getMark();
                } else {
                    $row[] = $participant->getMarkOfficial();
                }
            } else {
                if ($participant->getPassed()) {
                    $row[] = '+';
                } else {
                    $row[] = '-';
                }
            }
            $start = $participant->getFirstVisit();
            
            if (in_array('date', $other_fields)) {
                $row[] = date('d.m.Y', $start);
            }
            if (in_array('starttime', $other_fields)) {
                $row[] = date('H:i', $start);
            }
        
            // index with padded matriculation for sorting
            $rows[sprintf("m%020d", $userfields['matriculation'])] = $row;
        }
        
        // sort the rows by matriculation number
        ksort($rows);
        
        $this->writeExportFileCVS($header, $rows);
        
        // get info about matriculations that were not found
        if ($this->check_registrations) {
            $mess = "";
            foreach ($registrations as $matriculation => $data) {
                $mess .= sprintf(
                    '<br />%s; %s; %s',
                    $data['matriculation'],
                    $data['lastname'],
                    $data['firstname']
                );
            }
            if ($mess) {
                $mess = sprintf($lng->txt('ass_mycampus_missing_participants'), $mess);
            }
            
            return $mess;
        } else {
            return "";
        }
    }
    
    
    /**
     * write the result data to CVS export file
     *
     * @param 	array	header fields
     * @param 	array	row arrays
     */
    public function writeExportFileCVS($a_header = array(), $a_rows = array())
    {
        // create the export directory if neccessary
        $this->test_obj->createExportDirectory();
        $this->export_dir = $this->test_obj->getExportDirectory();
        
        // write the CVS file
        $filename = 'prf_' . $this->getOption('exam_number') . '.csv';

        $file = fopen($this->export_dir . "/" . $filename, "w");
        fwrite($file, utf8_decode(implode(';', $a_header) . "\r\n"));
        foreach ($a_rows as $key => $row) {
            fwrite($file, utf8_decode(implode(';', $row) . "\r\n"));
        }
        fclose($file);
    }
}
