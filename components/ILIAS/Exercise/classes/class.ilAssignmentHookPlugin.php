<?php

/**
 * fau: exAssHook - new class ilAssignmentHookPlugin.
 */
abstract class ilAssignmentHookPlugin extends ilPlugin
{
    public const SLOT_ID = 'exashk';

    /**
     * Get the ids of the available assignment types
     * Currently plugin authors have to take care of unique type ids
     * @return int[]
     */
    abstract public function getAssignmentTypeIds();

    /**
     * Get an assignment type by its id
     * @param int $a_id
     * @return ilExAssignmentTypeInterface
     */
    abstract public function getAssignmentTypeById($a_id);

    /**
     * Get an assignment type GUI by its id
     * @param int $a_id
     * @return ilExAssignmentTypeGUIInterface
     */
    abstract public function getAssignmentTypeGuiById($a_id);

    /**
     * Get the class names of the assignment type GUIs
     * @return string[] (indexed by type id)
     */
    abstract public function getAssignmentTypeGuiClassNames();
}
