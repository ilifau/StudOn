<?php

namespace FAU\Ilias\Helper;
use ilDatePresentation;
use ilDateTime;
use ilCourseWaitingList;
/**
 * trait for providing additional ilObjCourse methods
 */
trait ObjCourseHelper 
{
    // fau: fairSub#13 - getter / setter
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
            'crs_settings',
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
        $relative = ilDatePresentation::useRelativeDates();
        ilDatePresentation::setUseRelativeDates($a_relative);
        $fairdate = ilDatePresentation::formatDate(new ilDateTime($this->getSubscriptionFair(), IL_CAL_UNIX));
        ilDatePresentation::setUseRelativeDates($relative);
        return $fairdate;
    }

    // fau: fairSub#12 - check if current time is in fair time span
    public function inSubscriptionFairTime($a_time = null)
    {
        if (!isset($a_time)) {
            $a_time = time();
        }

        if (!$this->isSubscriptionMembershipLimited() && !$this->hasParallelGroups()) {
            return false;
        } elseif (empty($this->getSubscriptionMaxMembers()) && !$this->hasParallelGroups()) {
            return false;
        } elseif (!empty( $this->getSubscriptionStart()) && $a_time < (int) $this->getSubscriptionStart()) {
            return false;
        } elseif ($a_time > $this->getSubscriptionFair()) {
            return false;
        } else {
            return true;
        }
    }
    // fau.
    // fau: new function getWaitingList()
    public function getWaitingList() : ilCourseWaitingList
    {
        $this->initWaitingList();
        return $this->waiting_list_obj;
    }
    // fau.        
}