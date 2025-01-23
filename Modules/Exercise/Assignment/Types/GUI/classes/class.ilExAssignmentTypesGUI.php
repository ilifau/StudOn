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

/**
 * Assignment types gui.
 *
 * @author killing@leifos.de
 * @ingroup ModulesExercise
 */
class ilExAssignmentTypesGUI
{
    // fau: exAssHook - load the plugins

    /** @var ilAssignmentHookPlugin[] */
    protected $plugins;

    /**
     * Get the active plugins
     */
    protected function getActivePlugins() {
        if (!isset($this->plugins)) {
            $this->plugins = [];
            $names = ilPluginAdmin::getActivePluginsForSlot(IL_COMP_MODULE, 'Exercise', 'exashk');
            foreach ($names as $name) {
                $this->plugins[] = ilPlugin::getPluginObject(IL_COMP_MODULE, 'Exercise','exashk', $name);
            }
        }

        return $this->plugins;
    }
    // fau.
    protected array $class_names = array(
        ilExAssignment::TYPE_UPLOAD => "ilExAssTypeUploadGUI",
        ilExAssignment::TYPE_BLOG => "ilExAssTypeBlogGUI",
        ilExAssignment::TYPE_PORTFOLIO => "ilExAssTypePortfolioGUI",
        ilExAssignment::TYPE_UPLOAD_TEAM => "ilExAssTypeUploadTeamGUI",
        ilExAssignment::TYPE_TEXT => "ilExAssTypeTextGUI",
        ilExAssignment::TYPE_WIKI_TEAM => "ilExAssTypeWikiTeamGUI"
    );

    /**
     * Constructor
     */
    public function __construct()    
    {
        // fau: exAssHook - add plugins to the class names
        foreach ($this->getActivePlugins() as $plugin) {
            foreach ($plugin->getAssignmentTypeGuiClassNames() as $id => $name ) {
                $this->class_names[$id] = $name;
            }
        }
        // fau.        
    }

    /**
     * Get instance
     */
    public static function getInstance(): \ilExAssignmentTypesGUI
    {
        return new self();
    }

    /**
     * Get type gui object by id
     *
     * Centralized ID management is still an issue to be tackled in the future and caused
     * by initial consts definition.
     *
     * @param int $a_id type id
     */
    public function getById(int $a_id): ilExAssignmentTypeGUIInterface | ilExAssTypeInactiveGUI
    {
        switch ($a_id) {
            case ilExAssignment::TYPE_UPLOAD:
                return new ilExAssTypeUploadGUI();

            case ilExAssignment::TYPE_BLOG:
                return new ilExAssTypeBlogGUI();

            case ilExAssignment::TYPE_PORTFOLIO:
                return new ilExAssTypePortfolioGUI();

            case ilExAssignment::TYPE_UPLOAD_TEAM:
                return new ilExAssTypeUploadTeamGUI();

            case ilExAssignment::TYPE_TEXT:
                return new ilExAssTypeTextGUI();

            case ilExAssignment::TYPE_WIKI_TEAM:
                return new ilExAssTypeWikiTeamGUI();
            // fau: exAssHook - return the type of a plugin for the id
            default:
                foreach ($this->getActivePlugins() as $plugin) {
                    if (in_array($a_id, $plugin->getAssignmentTypeIds())) {
                        return $plugin->getAssignmentTypeGuiById($a_id);
                    }
                }

                include_once("./Modules/Exercise/AssignmentTypes/GUI/classes/class.ilExAssTypeInactiveGUI.php");
                return new ilExAssTypeInactiveGUI();

            // fau.                
        }

        
        // we should throw some exception here
        // fau: exAssHook -> code not reachable -> commented out
        // throw new ilExcUnknownAssignmentTypeException("Unkown Assignment Type ($a_id).");
        // .fau
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

    /**
     * Get type id for class name
     *
     * @param $a_string
     * @return null|int
     */
    public function getIdForClassName($a_string)
    {
        foreach ($this->class_names as $k => $cn) {
            if (strtolower($cn) === strtolower($a_string)) {
                return $k;
            }
        }
        return null;
    }
}
