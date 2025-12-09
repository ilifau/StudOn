<?php

namespace FAU\Ilias\Helper;

/**
 * trait for providing additional ilObjRootFolderGUI methods
 */
trait ilObjRootFolderGUIHelper 
{
    // fau: fauService - provide missing framesetObject function
    /**
    * output tree frameset
    */
    
    public function framesetObject()
    {
        $lng = $this->lng;
        $ilCtrl = $this->ctrl;
        $ilAccess = $this->access;
        
        $ilCtrl->redirectByClass("ilrepositorygui", "");
    }
    // fau.
}