<?php
// fau: LPExport
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */
include_once "Services/Tracking/classes/class.ilLPMarks.php";
include_once "Services/Tracking/classes/class.ilLPStatus.php";
include_once "Services/Tracking/classes/class.ilLearningProgressBaseGUI.php";

/**
 * Class ilLPExportTools - tools for learning progress export
 *
 * @author Christina Fuchs <christina.fuchs@ili.fau.de>
 *
 * @version $Id$
 *
 */
class ilLPExportTools
{
    private $obj_id;
      
    /**
     * constructor
     *
     */
    public function __construct($a_obj_id)
    {
        $this->obj_id = $a_obj_id;
    }
        
    /**
     * create the learning progress export file and download
     * @param string $matriculations - list of matriculations
     */
    public function createExportFile($matriculations)
    {
        $matriculations = preg_split('/\r\n|\r|\n/',$matriculations);
        // build the header row
        $header = array("Matriculation Number", "Login", "First Name", "Last Name", "Mark", "Statuscode", "Status Description");
        
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
                    $row[] = $this->getStatusText($status);
                    break;
                }
            }
            $rows[] = $row;
        }
            
        return $this->writeExportFileCSV($header, $rows);
    }
    
    /**
     * gather users with learning progress
     */
    private function gatherLPUsers()
    {
        include_once "Services/Tracking/classes/class.ilLPMarks.php";
        $user_ids = ilLPMarks::_getAllUserIds($this->obj_id);
       
        return $user_ids;
    }

    /**
     * write the result data to CSV file and download the file
     *
     * @param 	array	header fields
     * @param 	array	row arrays
     */
    private function writeExportFileCSV($a_header = array(), $a_rows = array())
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

        // download fie and delete afterwards
        ilUtil::deliverFile($filename, 'lp_export.csv', '', false, true);
        
        return true;
    }

    /**
     * Get status description from statuscode
     */
    private function getStatusText($status)
    {
        if($status == 0)
            return ilLearningProgressBaseGUI::_getStatusText(ilLPStatus::LP_STATUS_NOT_ATTEMPTED_NUM);
        else 
            return ilLearningProgressBaseGUI::_getStatusText($status);
    }
}
// fau.