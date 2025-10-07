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
    protected ilComponentRepository $component_repository;
    protected ilComponentFactory $component_factory;
    /**
     * Get the active plugins
     */
    protected function getActivePlugins() {
        if (!isset($this->plugins)) {
            global $DIC;
            
            $this->plugins = [];
            
            try {
                // Methode 1: Über Component Repository
                $component_repo = $DIC["component.repository"];
                
                // Versuche verschiedene Ansätze
                if (method_exists($component_repo, 'getPlugins')) {
                    $plugins_generator = $component_repo->getPlugins();
                    $all_plugins = iterator_to_array($plugins_generator);
                    
                    foreach ($all_plugins as $plugin_info) {
                        // Versuche verschiedene Eigenschaften zu lesen
                        $plugin_id = method_exists($plugin_info, 'getId') ? $plugin_info->getId() : 'unknown';
                        $plugin_name = method_exists($plugin_info, 'getName') ? $plugin_info->getName() : 'unknown';
                        $is_active = method_exists($plugin_info, 'isActive') ? $plugin_info->isActive() : false;
                        
                        // Suche nach ExAutoScore
                        if (strpos(strtolower($plugin_id), 'exautoscore') !== false || 
                            strpos(strtolower($plugin_name), 'exautoscore') !== false) {
                            
                            if ($is_active) {
                                try {
                                    $component_factory = $DIC["component.factory"];
                                    $plugin_instance = $component_factory->getPlugin($plugin_id);
                                    $this->plugins[] = $plugin_instance;
                                } catch (Exception $e) {
                                    // Plugin-Instanziierung fehlgeschlagen
                                }
                            }
                        }
                    }
                }
                
            } catch (Exception $e) {
                // Component Repository Ansatz fehlgeschlagen
            }
            
            // Fallback: Direkte Plugin-Instanziierung
            if (empty($this->plugins)) {
                $plugin_path = ILIAS_ABSOLUTE_PATH . '/Customizing/global/plugins/Modules/Exercise/AssignmentHook/ExAutoScore/classes/class.ilExAutoScorePlugin.php';
                
                if (file_exists($plugin_path)) {
                    require_once($plugin_path);
                    
                    if (class_exists('ilExAutoScorePlugin')) {
                        try {
                            $plugin = ilExAutoScorePlugin::getInstance();
                            
                            // Prüfe Plugin-Status
                            if (method_exists($plugin, 'isActive') && $plugin->isActive()) {
                                $this->plugins[] = $plugin;
                            }
                        } catch (Exception $e) {
                            // Direkte Plugin-Instanziierung fehlgeschlagen
                        }
                    }
                }
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
        ilExAssignment::TYPE_WIKI_TEAM => "ilExAssTypeWikiTeamGUI",
        // fau: exAssTest - add test result type gui
        ilExAssignment::TYPE_TEST_RESULT => "ilExAssTypeTestResultGUI",
        ilExAssignment::TYPE_TEST_RESULT_TEAM => "ilExAssTypeTestResultTeamGUI"
        // fau.        
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
            // fau: exAssTest - get instance for type test result gui
            case ilExAssignment::TYPE_TEST_RESULT:
                include_once("./Modules/Exercise/AssignmentTypes/GUI/classes/class.ilExAssTypeTestResultGUI.php");
                return new ilExAssTypeTestResultGUI();

            case ilExAssignment::TYPE_TEST_RESULT_TEAM:
                include_once("./Modules/Exercise/AssignmentTypes/GUI/classes/class.ilExAssTypeTestResultTeamGUI.php");
                return new ilExAssTypeTestResultTeamGUI();

            // fau.                
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
