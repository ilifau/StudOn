<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once 'Services/RelativeLink/classes/class.ilRelativeLink.php';

/**
 * fau: relativeLink - new class ilRelativeLinkGUI
 *
 * @author Fred Neumann <fred.neumann@fau.de>
 * @version $Id: $
 */
class ilRelativeLinkGUI
{
    /* @var string $target_type */
    private $target_type;

    /* @var integer $target_id */
    private $target_id;

    /* @var ilRelativeLink */
    private $linkObj = null;


    /**
     * Set the link target
     *
     * @param string	$a_target_type
     * @param integer	$a_target_id
     */
    public function setTarget($a_target_type, $a_target_id)
    {
        $this->target_id = $a_target_id;
        $this->target_type = $a_target_type;
    }


    /**
     * Get the GUI element to show and create the link
     * @param	boolean 	$a_with_label
     * @return 	string
     */
    public function getHTML($a_with_label)
    {
        global $ilCtrl, $tpl, $lng;

        // get existing link object, but do not create automatically
        $this->linkObj = ilRelativeLink::getForTarget($this->target_type, $this->target_id, false);

        // prepare the javascript and ajax part
        $ilCtrl->setParameterByClass("ilrelativelinkgui", "target_id", $this->target_id);
        $ilCtrl->setParameterByClass("ilrelativelinkgui", "target_type", $this->target_type);
        $tpl->addJavascript('./Services/RelativeLink//js/ilRelativeLink.js');
        $tpl->addOnLoadCode('il.RelativeLink.init(' . json_encode(array(
                'ajax_url' => $ilCtrl->getLinkTargetByClass("ilrelativelinkgui", "createLink", "", true),
                'show_link' => (int) isset($this->linkObj)
            )) . ')');

        // create the gui element
        $ltpl = new ilTemplate("tpl.relative_link.html", true, true, "Services/RelativeLink");
        if ($a_with_label) {
            $ltpl->setVariable("TXT_RELATIVE_LINK", $lng->txt('relative_link_label'));
        }
        $ltpl->setVariable("TXT_CREATE_LINK", $lng->txt('relative_link_create'));
        $ltpl->setVariable("TXT_RELATIVE_LINK_DESCRIPTION", $lng->txt('relative_link_description'));
        $ltpl->setVariable("LINK", isset($this->linkObj) ? $this->linkObj->getUrl() : '');
        return $ltpl->get();
    }


    /**
     * execute command (for ajax calls)
     */
    public function executeCommand()
    {
        global $ilCtrl;

        $cmd = $ilCtrl->getCmd("createLink");
        switch ($cmd) {
            case 'createLink':
                $this->$cmd();
                break;
        }
    }


    /**
     * Create a link and return its URL
     */
    public function createLink()
    {
        /* @var ilAccessHandler $ilAccess */
        global $ilAccess;

        $this->setTarget($_GET['target_type'], $_GET['target_id']);

        $access = false;
        $ref_ids = ilRelativeLink::getRefIdsForTarget($this->target_type, $this->target_id);
        foreach ($ref_ids as $ref_id) {
            if ($ilAccess->checkAccess('write', '', $ref_id)) {
                $access = true;
                break;
            }
        }

        if ($access) {
            // this creates the link code if it does not exist
            $this->linkObj = ilRelativeLink::getForTarget($this->target_type, $this->target_id, true);
            echo json_encode(array('link' => $this->linkObj->getUrl()));
        } else {
            echo json_encode(array('link' => 'you have no write access'));
        }
    }


    /**
     * Hook for goto.php
     * This manipulates the target
     */
    public function gotoHook()
    {
        global $lng;

        // everything after "lcode_"
        $code = substr($_GET['target'], 6);

        $target = ilRelativeLink::getNearestGotoTarget($code);

        if (empty($target)) {
            include_once './Services/User/classes/class.ilUserUtil.php';
            $url = ilUserUtil::getStartingPointAsUrl();

            include_once './Services/Utilities/classes/class.ilUtil.php';
            ilUtil::sendFailure(sprintf($lng->txt('relative_link_not_found'), $code), true);
            ilUtil::redirect($url);
        } else {
            // use redirect to prevent the relative link from being bookmarked by the user
            ilUtil::redirect('goto.php?target=' . $target);
        }
    }
}
