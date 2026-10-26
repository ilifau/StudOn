<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

class ilExAssignmentTypesGUI
{
    protected array $class_names = array(
        ilExAssignment::TYPE_UPLOAD => "ilExAssTypeUploadGUI",
        ilExAssignment::TYPE_BLOG => "ilExAssTypeBlogGUI",
        ilExAssignment::TYPE_PORTFOLIO => "ilExAssTypePortfolioGUI",
        ilExAssignment::TYPE_UPLOAD_TEAM => "ilExAssTypeUploadTeamGUI",
        ilExAssignment::TYPE_TEXT => "ilExAssTypeTextGUI",
        // fau: exAssTest - add test result type gui
        ilExAssignment::TYPE_WIKI_TEAM => "ilExAssTypeWikiTeamGUI",
        ilExAssignment::TYPE_TEST_RESULT => "ilExAssTypeTestResultGUI",
        ilExAssignment::TYPE_TEST_RESULT_TEAM => "ilExAssTypeTestResultTeamGUI"
        // fau.
    );

    public function __construct(
        protected \ILIAS\Exercise\InternalDomainService $domain,
        protected \ILIAS\Exercise\InternalGUIService $gui
    ) {
        // fau: exAssHook - add plugins to the class names
        foreach (ilExAssignmentTypes::getActivePlugins() as $plugin) {
            foreach ($plugin->getAssignmentTypeGuiClassNames() as $id => $name) {
                $this->class_names[$id] = $name;
            }
        }
        // fau.
    }

    /**
     * Get type gui object by id
     *
     * Centralized ID management is still an issue to be tackled in the future and caused
     * by initial consts definition.
     *
     * @param int $a_id type id
     */
    public function getById(int $a_id): ilExAssignmentTypeGUIInterface
    {
        switch ($a_id) {
            case ilExAssignment::TYPE_UPLOAD:
                return new ilExAssTypeUploadGUI(
                    $this->domain,
                    $this->gui
                );

            case ilExAssignment::TYPE_BLOG:
                return new ilExAssTypeBlogGUI(
                    $this->domain,
                    $this->gui
                );

            case ilExAssignment::TYPE_PORTFOLIO:
                return new ilExAssTypePortfolioGUI(
                    $this->domain,
                    $this->gui
                );

            case ilExAssignment::TYPE_UPLOAD_TEAM:
                return new ilExAssTypeUploadTeamGUI(
                    $this->domain,
                    $this->gui
                );

            case ilExAssignment::TYPE_TEXT:
                return new ilExAssTypeTextGUI(
                    $this->domain,
                    $this->gui
                );

            case ilExAssignment::TYPE_WIKI_TEAM:
                return new ilExAssTypeWikiTeamGUI(
                    $this->domain,
                    $this->gui
                );

            // fau: exAssTest - get instance for type test result gui
            case ilExAssignment::TYPE_TEST_RESULT:
                return new ilExAssTypeTestResultGUI(
                    $this->domain,
                    $this->gui
                );

            case ilExAssignment::TYPE_TEST_RESULT_TEAM:
                return new ilExAssTypeTestResultTeamGUI(
                    $this->domain,
                    $this->gui
                );
            // fau.

            // fau: exAssHook - return the type gui of a plugin for the id
            default:
                foreach (ilExAssignmentTypes::getActivePlugins() as $plugin) {
                    if (in_array($a_id, $plugin->getAssignmentTypeIds())) {
                        return $plugin->getAssignmentTypeGuiById($a_id);
                    }
                }
                return new ilExAssTypeInactiveGUI();
            // fau.
        }

        // fau: exAssHook - code not reachable
        // throw new ilExcUnknownAssignmentTypeException("Unkown Assignment Type ($a_id).");
        // fau.
    }

    /**
     * Get type gui object by classname
     *
     * @param
     * @return
     */
    public function getByClassName($a_class_name): \ilExAssignmentTypeGUIInterface
    {
        $id = $this->getIdForClassName($a_class_name);
        return $this->getById($id);
    }


    /**
     * Checks if a class name is a valid exercise assignment type GUI class
     * (case insensitive, since ilCtrl uses lower keys due to historic reasons)
     *
     * @param string
     */
    public function isExAssTypeGUIClass($a_string): bool
    {
        foreach ($this->class_names as $cn) {
            if (strtolower($cn) === strtolower($a_string)) {
                return true;
            }
        }
        return false;
    }

    public function getIdForClassName($a_string): ?int
    {
        foreach ($this->class_names as $k => $cn) {
            if (strtolower($cn) === strtolower($a_string)) {
                return $k;
            }
        }
        return null;
    }
}
