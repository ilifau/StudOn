<?php
/**
 * fau: univisImport - selector for univis actions in a container.
 *
 * @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
 * @version $Id: $
 */
class ilUnivisImportActionsGUI
{
    /**
     * @var object		the current container object
     */
    public $object = null;

    /**
     * @var array	callable actions [title=> string, link=>string], [...]
     */
    public $actions = array();


    public function __construct($a_object)
    {
        global $lng, $ilCtrl;

        $this->lng = $lng;
        $this->ctrl = $ilCtrl;
        $this->object = $a_object;

        $this->initActions();
    }


    /**
     * Initialize the list of available actions
     */
    protected function initActions()
    {
        global $ilAccess;

        // check if import is allowed for parent object
        switch ($this->object->getType()) {
            case 'cat':
                if ($ilAccess->checkAccess('create_crs', '', $this->object->getRefId())) {
                    $this->actions[] = array(
                        'title' => $this->lng->txt('univis_category_course_import'),
                        'link' => $this->ctrl->getLinkTargetByClass('ilUnivisImportLecturesGUI', 'startCategoryCourseImport'));

                    $this->actions[] = array(
                        'title' => $this->lng->txt('univis_category_course_update'),
                        'link' => $this->ctrl->getLinkTargetByClass('ilUnivisImportLecturesGUI', 'startCategoryCourseUpdate'));
                }
                break;

            case 'crs':
                if ($ilAccess->checkAccess('write', '', $this->object->getRefId())) {
                    if (strpos($this->object->getImportId(), 'Lecture') != false) {
                        $this->actions[] = array(
                            'title' => $this->lng->txt('univis_course_data_update'),
                            'link' => $this->ctrl->getLinkTargetByClass('ilUnivisImportLecturesGUI', 'startCourseDataUpdate'));

                        $this->actions[] = array(
                            'title' => $this->lng->txt('univis_course_data_other_import'),
                            'link' => $this->ctrl->getLinkTargetByClass('ilUnivisImportLecturesGUI', 'startCourseDataImport'));

                        $this->actions[] = array(
                            'title' => $this->lng->txt('univis_course_data_delete'),
                            'link' => $this->ctrl->getLinkTargetByClass('ilUnivisImportLecturesGUI', 'startCourseDataDelete'));
                    } else {
                        $this->actions[] = array(
                            'title' => $this->lng->txt('univis_course_data_import'),
                            'link' => $this->ctrl->getLinkTargetByClass('ilUnivisImportLecturesGUI', 'startCourseDataImport'));
                    }
                }
                break;
        }
    }


    public function render()
    {
        global $DIC;

        $toolbar = $DIC->toolbar();

        if (count($this->actions)) {
            include_once("./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php");
            $selection = new ilAdvancedSelectionListGUI();
            $selection->setListTitle($this->lng->txt("univis_data_transfer"));
            $selection->setPullRight(false);
            foreach ($this->actions as $action) {
                $selection->addItem($action['title'], '', $action['link']);
            }
            $toolbar->addStickyItem($selection);
        }
    }
}
