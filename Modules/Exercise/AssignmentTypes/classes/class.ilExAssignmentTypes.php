<?php

/* Copyright (c) 1998-2018 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Assignment types. Gives information on available types and acts as factory
 * to get assignment type objects.
 *
 * @author killing@leifos.de
 * @ingroup ModulesExercise
 */
class ilExAssignmentTypes
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

    /**
     * Constructor
     */
    protected function __construct()
    {
    }

    /**
     * Get instance
     *
     * @return ilExAssignmentTypes
     */
    public static function getInstance()
    {
        return new self();
    }

    /**
     * Get all ids
     *
     * @param
     * @return
     */
    public function getAllIds()
    {
        // fau: exAssHook - add dummy plugin ids to the type ids
        // fau: exAssTest - add type for test results
        $ids = [
            ilExAssignment::TYPE_UPLOAD,
            ilExAssignment::TYPE_UPLOAD_TEAM,
            ilExAssignment::TYPE_TEXT,
            ilExAssignment::TYPE_BLOG,
            ilExAssignment::TYPE_PORTFOLIO,
            ilExAssignment::TYPE_WIKI_TEAM,
            ilExAssignment::TYPE_TEST_RESULT,
            ilExAssignment::TYPE_TEST_RESULT_TEAM
        ];

        foreach ($this->getActivePlugins() as $plugin) {
            $ids = array_merge($ids, $plugin->getAssignmentTypeIds());
        }

        return $ids;
        // fau.
    }

    /**
     * Is valid id
     *
     * @param int $a_id
     * @return bool
     */
    public function isValidId($a_id)
    {
        // fau: exAssHook - allow type ids of inactive plugins
        return true;
        // return in_array($a_id, $this->getAllIds());
        // fau.
    }



    /**
     * Get all
     *
     * @param
     * @return
     */
    public function getAll()
    {
        return array_column(
            array_map(
                function ($id) {
                    return [$id, $this->getById($id)];
                },
                $this->getAllIds()
            ),
            1,
            0
        );
    }
    
    /**
     * Get all activated
     *
     * @param
     * @return
     */
    public function getAllActivated()
    {
        return array_filter($this->getAll(), function (ilExAssignmentTypeInterface $at) {
            return $at->isActive();
        });
    }
    

    /**
     * Get type object by id
     *
     * Centralized ID management is still an issue to be tackled in the future and caused
     * by initial consts definition.
     *
     * @param int $a_id type id
     * @return ilExAssignmentTypeInterface
     */
    public function getById($a_id)
    {
        // fau: exAssHook - include ilExAssignmentTypeExtendedInterface
        include_once "./Modules/Exercise/AssignmentTypes/classes/interface.ilExAssignmentTypeExtendedInterface.php";
        // fau.

        switch ($a_id) {
            case ilExAssignment::TYPE_UPLOAD:
                include_once("./Modules/Exercise/AssignmentTypes/classes/class.ilExAssTypeUpload.php");
                return new ilExAssTypeUpload();
                break;

            case ilExAssignment::TYPE_BLOG:
                include_once("./Modules/Exercise/AssignmentTypes/classes/class.ilExAssTypeBlog.php");
                return new ilExAssTypeBlog();
                break;

            case ilExAssignment::TYPE_PORTFOLIO:
                include_once("./Modules/Exercise/AssignmentTypes/classes/class.ilExAssTypePortfolio.php");
                return new ilExAssTypePortfolio();
                break;

            case ilExAssignment::TYPE_UPLOAD_TEAM:
                include_once("./Modules/Exercise/AssignmentTypes/classes/class.ilExAssTypeUploadTeam.php");
                return new ilExAssTypeUploadTeam();
                break;

            case ilExAssignment::TYPE_TEXT:
                include_once("./Modules/Exercise/AssignmentTypes/classes/class.ilExAssTypeText.php");
                return new ilExAssTypeText();
                break;

            case ilExAssignment::TYPE_WIKI_TEAM:
                include_once("./Modules/Exercise/AssignmentTypes/classes/class.ilExAssTypeWikiTeam.php");
                return new ilExAssTypeWikiTeam();
                break;

            // fau: exAssTest - get assignment type instance
            case ilExAssignment::TYPE_TEST_RESULT:
                include_once("./Modules/Exercise/AssignmentTypes/classes/class.ilExAssTypeTestResult.php");
                return new ilExAssTypeTestResult();
                break;

            case ilExAssignment::TYPE_TEST_RESULT_TEAM:
                include_once("./Modules/Exercise/AssignmentTypes/classes/class.ilExAssTypeTestResultTeam.php");
                return new ilExAssTypeTestResultTeam();
                break;
            // fau.

                // fau: exAssHook - return the type of a plugin for the id
            default:
                foreach ($this->getActivePlugins() as $plugin) {
                    if (in_array($a_id, $plugin->getAssignmentTypeIds())) {
                        return $plugin->getAssignmentTypeById($a_id);
                    }
                }

                include_once("./Modules/Exercise/AssignmentTypes/classes/class.ilExAssTypeInactive.php");
                return new ilExAssTypeInactive();
                // fau.
        }

        // we should throw some exception here
    }

    /**
     * Get assignment type IDs for given submission type
     *
     * @param int $a_submission_type
     * @return array
     */
    public function getIdsForSubmissionType($a_submission_type)
    {
        $ids = [];
        foreach ($this->getAllIds() as $id) {
            if ($this->getById($id)->getSubmissionType() == $a_submission_type) {
                $ids[] = $id;
            }
        }
        return $ids;
    }
}
