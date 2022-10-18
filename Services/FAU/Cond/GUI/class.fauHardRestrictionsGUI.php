<?php

use FAU\BaseGUI;

/**
 * GUI for the display of hard restrictions and check results
 */
class fauHardRestrictionsGUI extends BaseGUI
{
    /**
     * Get an instance of the class
     * @return static
     */
    public static function getInstance() : self
    {
        static $instance = null;
        if (!isset($instance)) {
            $instance = new self();
        }
        return $instance;
    }

    /**
     * Get a result string that is linked with a modal to show details
     * @param bool        $passed    Restrictions are passed (will determine the text that is directly shown)
     * @param string      $info      The detailed info if restrictions are failed (linked in the modal)
     * @param string      $username  Full name of the user (will be shown in the modal title)
     * @param int|null    $module_id ID of the selected modal by the user
     * @param string|null $passed_label    label to be used for passed restrictions
     * @param string|null $failed_label    label to be used for failed restrictions
     * @return string
     */
    public function getResultWithModalHtml(bool $passed, string $info, string $username, ?int $module_id,
        ?string $passed_label = null, ?string $failed_label = null) : string
    {
        $passed_label = $passed_label ?? $this->lng->txt('fau_check_info_passed_restrictions');
        $failed_label = $failed_label ??  $this->lng->txt('fau_check_info_failed_restrictions');

        // no detailed info if restrictions are passed
        if ($passed) {
            return $passed_label;
        }

        $module_info = '';
        if (!empty($module_id)) {
            foreach ($this->dic->fau()->study()->repo()->getModules([$module_id]) as $module) {
                $module_info = '<p>' . $this->lng->txt('fau_selected_module') . ': '
                    . $module->getModuleName() . ' (' . $module->getModuleNr() . ')</p>';
            }
        }

        $modal_id = rand(1000000,9999999);
        $modal = ilModalGUI::getInstance();
        $modal->setId($modal_id);
        $modal->setType(ilModalGUI::TYPE_LARGE);
        $modal->setBody($module_info . $info);
        $modal->setHeading(sprintf($this->lng->txt('fau_check_info_restrictions_for'), $username));

        $onclick = "$('#$modal_id').modal('show')";
        $link = '<a onclick="' . $onclick . '">» ' . $failed_label . '</a>';
        return $modal->getHTML() . $link;
    }
}