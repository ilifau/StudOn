<?php

/**
 * fau: campoExport - Category export GUI class
 *
 * @ilCtrl_IsCalledBy ilCategoryExportGUI: ilObjCategoryGUI
 */
class ilCategoryExportGUI extends ilExportGUI
{
    /** @var ilObjCategory */
    protected $obj;


    /**
     * Constructor
     */
    public function __construct($a_parent_gui, $a_main_obj = null)
    {
       parent::__construct($a_parent_gui, $a_main_obj);
       $this->addFormat('xml');

       ilUtil::sendInfo('Category Export');
    }
}