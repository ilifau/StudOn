<?php
// fau: LPExport
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */
include_once "Services/Tracking/classes/class.ilLPMarks.php";
include_once "Services/Tracking/classes/class.ilLPStatus.php";
include_once "Services/Tracking/classes/class.ilLearningProgressBaseGUI.php";

/**
* Tools for learning progress export 
*/
class ilLPExportTools
{
    private $lp_obj = null;
    private $obj_id;
    private $options = array();
    private $tracked_user = null;
      
    /**
     * constructor
     *
     */
    public function __construct($a_obj_id)
    {
        $this->obj_id = $a_obj_id;
        $this->lp_obj = ilObjectLP::getInstance($this->obj_id);
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
     * create the export files for my campus
     */
    public function createExportFile($matriculations, $export_subdir)
    {
        global $lng;
        
        $matriculations = preg_split('/\r\n|\r|\n/',$matriculations);
        // build the header row
        $header = array("Matrikelnummer", "Benutzername", "Vorname", "Nachname", "Note", "Statuscode", "Statusbezeichnung");
        
        // build the data rows
        $rows = array();

        $users = $this->gatherLPUsers();
        
        foreach ($matriculations as $matriculation)
        {
            $row = array();
            $row[] = $matriculation;

            foreach ($users as $user)
            {
                $userfields = ilObjUser::_lookupFields($user);
                
                if($userfields["matriculation"] == $matriculation)
                {
                    $row[] = $userfields['login'];
                    $row[] = $userfields['firstname'];
                    $row[] = $userfields['lastname'];

                    $row[]= ilLPMarks::_lookupMark($user, $this->obj_id);
                    $status = ilLPStatus::_lookupStatus($this->obj_id, $user);
                    $row[] = $status;
                    $row[] = ilLearningProgressBaseGUI::_getStatusText($status);
                }
                // index with padded matriculation for sorting
                $rows[sprintf("m%020d", $matriculation)] = $row;
            }
        }
       
        // sort the rows by matriculation number
       // ksort($rows);
        
        $this->writeExportFileCSV($header, $rows, $export_subdir);
        
        return "";
    }
    
    protected function gatherLPUsers()
    {
        include_once "Services/Tracking/classes/class.ilLPMarks.php";
        $user_ids = ilLPMarks::_getAllUserIds($this->obj_id);
        
        //include_once "Services/Tracking/classes/class.ilChangeEvent.php";
        //$user_ids = array_merge($user_ids, ilChangeEvent::_getAllUserIds($this->obj_id));
        
        return $user_ids;
    }
    /**
     * write the result data to CSV export file
     *
     * @param 	array	header fields
     * @param 	array	row arrays
     */
    public function writeExportFileCSV($a_header = array(), $a_rows = array(), $export_subdir)
    {
        // get the export directory
        $this->export_dir = ilUtil::getDataDir()."/temp/";
        $type = "csv";

        // write the CSV file
        $filename = $this->export_dir . 'lp_export.csv';
        if(file_exists($filename)) {
            unlink($filename);
        }
        $file = fopen($filename, "w");
        fwrite($file, utf8_decode(implode(';', $a_header) . "\r\n"));
        foreach ($a_rows as $key => $row) {
            fwrite($file, utf8_decode(implode(';', $row) . "\r\n"));
        }
        fclose($file);
        ilUtil::deliverFile($filename, 'lp_export.csv');
    }
}
