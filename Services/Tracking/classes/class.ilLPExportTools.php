<?php
// fau: LPExport
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

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
        $csv = new ilCSVWriter();
        $csv->setSeparator(";");
        $csv->addColumn("Matriculation Number");
        $csv->addColumn("Login");
        $csv->addColumn("First Name");
        $csv->addColumn("Last Name");
        $csv->addColumn("Mark");
        $csv->addColumn("Statuscode");
        $csv->addColumn("Status Description");
        
        // build the data rows
        $users = $this->gatherLPUsers();
        
        foreach ($matriculations as $matriculation)
        {
            $csv->addRow();
            $csv->addColumn($matriculation);

            foreach ($users as $user)
            {
                $userfields = ilObjUser::_lookupFields($user);
                
                if($userfields["matriculation"] == $matriculation)
                {
                    $csv->addColumn($userfields['login']);
                    $csv->addColumn($userfields['firstname']);
                    $csv->addColumn($userfields['lastname']);

                    $csv->addColumn(ilLPMarks::_lookupMark($user, $this->obj_id));
                    $status = ilLPStatus::_lookupStatus($this->obj_id, $user);
                    $csv->addColumn($status);
                    $csv->addColumn($this->getStatusText($status));
                    break;
                }
            }
        }
            
        $date = new ilDate(time(), IL_CAL_UNIX);
        ilUtil::deliverData($csv->getCSVString(), $date->get(IL_CAL_FKT_DATE, 'Y-m-d')."_lp_export.csv", "text/csv");
    }
    
    /**
     * gather users with learning progress
     */
    private function gatherLPUsers()
    {
        $user_ids = ilLPMarks::_getAllUserIds($this->obj_id);
       
        return $user_ids;
    }

    /**
     * Get status description from statuscode
     */
    private function getStatusText($status)
    {
        if($status == 0){
            return ilLearningProgressBaseGUI::_getStatusText(ilLPStatus::LP_STATUS_NOT_ATTEMPTED_NUM);
        }
        else{ 
            return ilLearningProgressBaseGUI::_getStatusText($status);
        }
    }
}
// fau.