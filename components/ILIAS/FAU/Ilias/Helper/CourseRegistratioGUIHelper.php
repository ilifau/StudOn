<?php

namespace FAU\Ilias\Helper;
use ilFormSectionHeaderGUI;
use ilCheckboxGroupInputGUI;
use ilCheckboxOption;

/**
 * trait for providing additional ilCourseRegistrationGUI methods
 */
trait CourseRegistrationGUIHelper 
{
    // fau: paraSub - fill form with the selection of parallel groups
    public function fillGroupSelection()
    {
        global $DIC;
        $info_gui = $DIC->fau()->study()->info();
        if (empty($this->registration->getParallelGroupsInfos())) {
            return;
        }

        $head = new ilFormSectionHeaderGUI();
        $head->setTitle($this->lng->txt('fau_sub_select_groups'));
        $head->setInfo($this->lng->txt('fau_sub_select_groups_info'));
        $this->form->addItem($head);

        $cb = new ilCheckboxGroupInputGUI($this->lng->txt('fau_sub_select_groups'), 'group_ref_ids');
        $cb->setRequired(true);
        $selected = [];
        foreach ($this->registration->getParallelGroupsInfos() as $group) {
            if ($group->isOnWaitingList()) {
                $selected[] = $group->getRefId();
            }
            if ($this->registration->isDirectJoinPossibleForGroup($group)) {
                $group = $group->withProperty((new \FAU\Ilias\Data\ListProperty(null, $this->lng->txt('fau_sub_direct_possible')))->withAlert(true));
            }
            else {
                $group = $group->withProperty((new \FAU\Ilias\Data\ListProperty(null, $this->lng->txt('mem_request_waiting')))->withAlert(true));
            }
            $option = new ilCheckboxOption($info_gui->getGroupTitleWithDetailsLink($group), $group->getRefId());
            $option->setInfo($info_gui->getGroupInfo($group, false));
            $option->setDisabled(!$group->wouldSubscriptionBePossible());
            $cb->addOption($option);
        }
        $cb->setValue($selected);
        $this->form->addItem($cb);

    }
    // fau.
}