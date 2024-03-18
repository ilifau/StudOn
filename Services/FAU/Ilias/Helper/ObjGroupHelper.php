<?php

namespace FAU\Ilias\Helper;
use ilDatePresentation;
use ilDateTime;
use ilGroupWaitingList;
/**
 * trait for providing additional ilObjGroup methods
 */
trait ObjGroupHelper 
{
    // fau: fairSub - getter / setter
    public function getSubscriptionFair()
    {
        return (int) $this->subscription_fair;
    }
    public function setSubscriptionFair($a_value)
    {
        $this->subscription_fair = $a_value;
    }
    public function getSubscriptionAutoFill()
    {
        return (bool) $this->subscription_auto_fill;
    }
    public function setSubscriptionAutoFill($a_value)
    {
        $this->subscription_auto_fill = (bool) $a_value;
    }
    public function getSubscriptionLastFill()
    {
        return $this->subscription_last_fill;
    }
    public function setSubscriptionLastFill($a_value)
    {
        $this->subscription_last_fill = $a_value;
    }
    public function saveSubscriptionLastFill($a_value = null)
    {
        global $ilDB;
        $ilDB->update(
            'grp_settings',
            array('sub_last_fill' => array('integer', $a_value)),
            array('obj_id' => array('integer', $this->getId()))
        );
        $this->subscription_last_fill = $a_value;
    }

    public function getSubscriptionMinFairSeconds()
    {
        global $ilSetting;
        return $ilSetting->get('SubscriptionMinFairSeconds', 3600);
    }

    public function getSubscriptionFairDisplay($a_relative)
    {
        require_once('Services/Calendar/classes/class.ilDatePresentation.php');
        $relative = ilDatePresentation::useRelativeDates();
        ilDatePresentation::setUseRelativeDates($a_relative);
        $fairdate = ilDatePresentation::formatDate(new ilDateTime($this->getSubscriptionFair(), IL_CAL_UNIX));
        ilDatePresentation::setUseRelativeDates($relative);
        return $fairdate;
    }
    // fau.

    // fau: fairSub - check if current time is in fair time span
    public function inSubscriptionFairTime($a_time = null)
    {
        if (!isset($a_time)) {
            $a_time = time();
        }

        if (!$this->isMembershipLimited()) {
            return false;
        } elseif (empty($this->getMaxMembers())) {
            return false;
        } elseif ($a_time > $this->getSubscriptionFair()) {
            return false;
        } elseif (!empty($this->getRegistrationStart()) &&
            !$this->getRegistrationStart()->isNull() &&
            $a_time < $this->getRegistrationStart()->get(IL_CAL_UNIX)) {
            return false;
        } else {
            return true;
        }
    }
    // fau.
}