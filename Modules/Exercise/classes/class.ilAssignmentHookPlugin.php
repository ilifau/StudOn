<?php
/**
 * fau: exAssHook - new class ilAssignmentHookPlugin.
 *
 * @ingroup ModulesExercise
 */
abstract class ilAssignmentHookPlugin extends ilPlugin
{
    /**
     * @var ilExAssignmentTypeInterface[]   indexed by type id (integer)
     */
    private $assignment_types;

    /**
     * @inheritDoc
     */
    final public function getComponentType() {
        return IL_COMP_MODULE;
    }

    /**
     * @inheritDoc
     */
    final public function getComponentName() {
        return "Exercise";
    }

    /**
     * @inheritDoc
     */
    final public function getSlot() {
        return "AssignmentHook";
    }

    /**
     * @inheritDoc
     */
    final public function getSlotId() {
        return "exashk";
    }

    /**
     * @inheritDoc
     */
    final protected function slotInit() {
        // nothing to do here.
    }

    /**
     * Get the ids of the available assignment types
     * Currently plugin authors have to take care of unique type ids
     * @return integer[]
     */
    abstract function getAssignmentTypeIds();

    /**
     * Get an assignment type by its id
     * @param integer $a_id
     * @return ilExAssignmentTypeInterface
     */
    abstract function getAssignmentTypeById($a_id);

    /**
     * Get an assignment type GUI by its id
     * @param integer $a_id
     * @return ilExAssignmentTypeGUIInterface
     */
    abstract function getAssignmentTypeGuiById($a_id);

    /**
     * Get the class names of the assignment type GUIs
     * @return string[] (indexed by type id)
     */
    abstract function getAssignmentTypeGuiClassNames();
}