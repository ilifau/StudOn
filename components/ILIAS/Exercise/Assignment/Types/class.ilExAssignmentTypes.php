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

use ILIAS\Exercise;

/**
 * Assignment types. Gives information on available types and acts as factory
 * to get assignment type objects.
 *
 * @author Alexander Killing <killing@leifos.de>
 */
class ilExAssignmentTypes
{
    protected Exercise\InternalService $service;

    // fau: exAssHook - load the plugins
    /** @var ilAssignmentHookPlugin[]|null */
    protected static ?array $plugins = null;

    /**
     * Get the active plugins
     * @return ilAssignmentHookPlugin[]
     */
    public static function getActivePlugins(): array
    {
        global $DIC;

        if (self::$plugins === null) {
            self::$plugins = [];
            try {
                foreach ($DIC['component.factory']->getActivePluginsInSlot(ilAssignmentHookPlugin::SLOT_ID) as $plugin) {
                    if ($plugin instanceof ilAssignmentHookPlugin) {
                        self::$plugins[] = $plugin;
                    }
                }
            } catch (Throwable $e) {
                $DIC->logger()->exc()->warning('exAssHook: could not load assignment hook plugins: ' . $e->getMessage());
            }
        }
        return self::$plugins;
    }
    // fau.

    protected function __construct(?Exercise\InternalService $service = null)
    {
        global $DIC;

        $this->service = ($service == null)
            ? $DIC->exercise()->internal()
            : $service;
    }

    public static function getInstance(): ilExAssignmentTypes
    {
        return new self();
    }

    public function getAllIds(): array
    {
        // fau: exAssHook - add the type ids of the active plugins
        $ids = [
            ilExAssignment::TYPE_UPLOAD,
            ilExAssignment::TYPE_UPLOAD_TEAM,
            ilExAssignment::TYPE_TEXT,
            ilExAssignment::TYPE_BLOG,
            ilExAssignment::TYPE_PORTFOLIO,
            // fau: exAssTest - add type for test results
            ilExAssignment::TYPE_WIKI_TEAM,
            ilExAssignment::TYPE_TEST_RESULT,
            ilExAssignment::TYPE_TEST_RESULT_TEAM
            // fau.
        ];

        foreach (self::getActivePlugins() as $plugin) {
            $ids = array_merge($ids, $plugin->getAssignmentTypeIds());
        }

        return $ids;
        // fau.
    }

    public function isValidId($a_id): bool
    {
        // fau: exAssHook - allow type ids of inactive plugins
        return true;
        // return in_array($a_id, $this->getAllIds());
        // fau.
    }



    /**
     * Get all
     * @return ilExAssignmentTypeInterface[]
     * @throws ilExcUnknownAssignmentTypeException
     */
    public function getAll(): array
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
     * @return ilExAssignmentTypeInterface[]
     * @throws ilExcUnknownAssignmentTypeException
     */
    public function getAllActivated(): array
    {
        return array_filter($this->getAll(), function (ilExAssignmentTypeInterface $at) {
            return $at->isActive();
        });
    }

    /**
     * Get all allowed types for an exercise for an exercise
     * @param ilObjExercise $exc
     * @return ilExAssignmentTypeInterface[]
     * @throws ilExcUnknownAssignmentTypeException
     */
    public function getAllAllowed(ilObjExercise $exc): array
    {
        $random_manager = $this->service->domain()->assignment()->randomAssignments($exc);
        $active = $this->getAllActivated();

        // no team assignments, if random mandatory assignments is activated
        if ($random_manager->isActivated()) {
            $active = array_filter($active, function (ilExAssignmentTypeInterface $at) {
                return !$at->usesTeams();
            });
        }
        return $active;
    }

    /**
     * Get type object by id
     *
     * Centralized ID management is still an issue to be tackled in the future and caused
     * by initial consts definition.
     *
     * @param int $a_id type id
     * @return ilExAssignmentTypeInterface
     * @throws ilExcUnknownAssignmentTypeException
     */
    public function getById(int $a_id): ilExAssignmentTypeInterface
    {
        switch ($a_id) {
            case ilExAssignment::TYPE_UPLOAD:
                return new ilExAssTypeUpload();

            case ilExAssignment::TYPE_BLOG:
                return new ilExAssTypeBlog();

            case ilExAssignment::TYPE_PORTFOLIO:
                return new ilExAssTypePortfolio();

            case ilExAssignment::TYPE_UPLOAD_TEAM:
                return new ilExAssTypeUploadTeam();

            case ilExAssignment::TYPE_TEXT:
                return new ilExAssTypeText();

            case ilExAssignment::TYPE_WIKI_TEAM:
                return new ilExAssTypeWikiTeam();

            // fau: exAssTest - get assignment type instance
            case ilExAssignment::TYPE_TEST_RESULT:
                return new ilExAssTypeTestResult();

            case ilExAssignment::TYPE_TEST_RESULT_TEAM:
                return new ilExAssTypeTestResultTeam();
            // fau.

            // fau: exAssHook - return the type of a plugin for the id
            default:
                foreach (self::getActivePlugins() as $plugin) {
                    if (in_array($a_id, $plugin->getAssignmentTypeIds())) {
                        return $plugin->getAssignmentTypeById($a_id);
                    }
                }
                return new ilExAssTypeInactive();
            // fau.
        }

        // fau: exAssHook - code not reachable
        // throw new ilExcUnknownAssignmentTypeException("Unknown Assignment Type ($a_id).");
        // fau.
    }

    /**
     * Get assignment type IDs for given submission type
     * @param string $a_submission_type
     * @return int[]
     * @throws ilExcUnknownAssignmentTypeException
     */
    public function getIdsForSubmissionType(string $a_submission_type): array
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
