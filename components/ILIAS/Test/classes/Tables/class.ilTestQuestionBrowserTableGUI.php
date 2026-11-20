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

declare(strict_types=1);

use ILIAS\Data\Factory as DataFactory;
use ILIAS\HTTP\GlobalHttpState;
use ILIAS\Refinery\Factory as Refinery;
use ILIAS\Taxonomy\DomainService as TaxonomyService;
use ILIAS\Test\Logging\TestLogger;
use ILIAS\Test\Questions\Presentation\QuestionsBrowserFilter;
use ILIAS\Test\Questions\Presentation\QuestionsBrowserTable;
use ILIAS\Test\RequestDataCollector;
use ILIAS\TestQuestionPool\Questions\GeneralQuestionPropertiesRepository;
use ILIAS\UI\Component\Input\Container\Filter\Filter;
use ILIAS\UI\Factory as UIFactory;
use ILIAS\UI\Renderer as UIRenderer;

/**
 * @ilCtrl_Calls ilTestQuestionBrowserTableGUI: ilFormPropertyDispatchGUI
 */
class ilTestQuestionBrowserTableGUI
{
    public const REPOSITORY_ROOT_NODE_ID = 1;
    public const MODE_PARAMETER = 'question_browse_mode';
    public const MODE_BROWSE_POOLS = 'modeBrowsePools';
    public const MODE_BROWSE_TESTS = 'modeBrowseTests';

    public const CMD_BROWSE_QUESTIONS = 'browseQuestions';
    public const CMD_INSERT_QUESTIONS = 'insertQuestions';

    public function __construct(
        private readonly ilTabsGUI $tabs,
        private readonly ilTree $tree,
        private readonly ilDBInterface $db,
        private readonly TestLogger $logger,
        private readonly ilComponentRepository $component_repository,
        private readonly ilObjTest $test_obj,
        private readonly ilObjUser $current_user,
        private readonly ilAccessHandler $access,
        private readonly GlobalHttpState $http_state,
        private readonly Refinery $refinery,
        private readonly UIFactory $ui_factory,
        private readonly UIRenderer $ui_renderer,
        private readonly RequestDataCollector $testrequest,
        private readonly GeneralQuestionPropertiesRepository $questionrepository,
        private readonly ilLanguage $lng,
        private readonly ilCtrl $ctrl,
        private readonly ilGlobalTemplateInterface $main_tpl,
        private readonly ilUIService $ui_service,
        private readonly DataFactory $data_factory,
        private readonly TaxonomyService $taxonomy,
    ) {
    }

    public function executeCommand(): bool
    {
        $this->handleWriteAccess();
        $this->handleTabs();

        switch (strtolower((string) $this->ctrl->getNextClass($this))) {
            case strtolower(self::class):
            case '':
                $cmd = $this->ctrl->getCmd() . 'Cmd';
                return $this->$cmd();

            default:
                $this->ctrl->setReturn($this, self::CMD_BROWSE_QUESTIONS);
                return $this->browseQuestionsCmd();
        }
    }

    private function handleWriteAccess(): void
    {
        if (!$this->access->checkAccess('write', '', $this->test_obj->getRefId())) {
            $this->ctrl->redirectByClass(ilObjTestGUI::class, ilObjTestGUI::SHOW_QUESTIONS_CMD);
        }
    }

    private function browseQuestionsCmd(): bool
    {
        $this->ctrl->setParameter($this, self::MODE_PARAMETER, $this->testrequest->raw(self::MODE_PARAMETER));
        $action = $this->ctrl->getLinkTarget($this, self::CMD_BROWSE_QUESTIONS);

        $mode = $this->ctrl->getParameterArrayByClass(self::class)[self::MODE_PARAMETER];
        $parent_title = ($mode === self::MODE_BROWSE_TESTS ? 'test_title' : 'tst_source_question_pool');

        $filter = $this->getQuestionsBrowserFilterComponent($parent_title, $action);
        // fau: testQuestionBrowserRoot - search only in the selected area
        $filter_data = $this->ui_service->filter()->getData($filter);
        $area_root = $this->resolveAreaRoot($filter_data, true);
        unset($filter_data['area_root'], $filter_data['area_ref_id']);
        $this->main_tpl->setOnScreenMessage('info', sprintf(
            $this->lng->txt('tst_question_browse_root_message'),
            $area_root === self::REPOSITORY_ROOT_NODE_ID
                ? $this->lng->txt('tst_question_browse_area_all')
                : ilObject::_lookupTitle(ilObject::_lookupObjId($area_root))
        ));
        $question_browser_table = $this->getQuestionsBrowserTable($parent_title, $area_root);

        $this->main_tpl->setContent(
            $this->ui_renderer->render([
                $filter,
                $question_browser_table->getComponent($this->http_state->request(), $filter_data)
            ])
        );
        // fau.

        return true;
    }

    private function getQuestionsBrowserFilterComponent(string $parent_title = '', string $action = ''): Filter
    {
        return (new QuestionsBrowserFilter(
            $this->ui_service,
            $this->lng,
            $this->ui_factory,
            "question_browser_filter_{$this->testrequest->getRefId()}",
            // fau: testQuestionBrowserRoot - options for the search area
            $parent_title,
            $this->getAreaOptions()
            // fau.
        ))->getComponent($action, $this->http_state->request());
    }

    // fau: testQuestionBrowserRoot - search area from filter, default is the container of the test
    private function getAreaOptions(): array
    {
        $options = [];
        foreach (array_reverse($this->tree->getPathFull($this->tree->getParentId($this->test_obj->getRefId()))) as $node) {
            if ((int) $node['child'] === self::REPOSITORY_ROOT_NODE_ID) {
                continue;
            }
            $options[(string) $node['child']] = $node['title'];
        }
        $options[(string) self::REPOSITORY_ROOT_NODE_ID] = $this->lng->txt('tst_question_browse_area_all');
        return $options;
    }

    private function resolveAreaRoot(?array $filter_data, bool $show_messages = false): int
    {
        $ref_id = (int) ($filter_data['area_ref_id'] ?? 0);
        if ($ref_id > 0) {
            if ($this->tree->isInTree($ref_id) && $this->access->checkAccess('read', '', $ref_id)) {
                return $ref_id;
            }
            if ($show_messages) {
                $this->main_tpl->setOnScreenMessage('failure', $this->lng->txt('tst_question_browse_area_ref_id_invalid'));
            }
        }

        $selected = (int) ($filter_data['area_root'] ?? 0);
        if (array_key_exists((string) $selected, $this->getAreaOptions())) {
            return $selected;
        }
        return $this->tree->getParentId($this->test_obj->getRefId());
    }
    // fau.

    // fau: testQuestionBrowserRoot - search only in the selected area
    private function getQuestionsBrowserTable(string $parent_title = '', int $area_root = self::REPOSITORY_ROOT_NODE_ID): QuestionsBrowserTable
    {
        $question_list = new ilAssQuestionList($this->db, $this->lng, $this->refinery, $this->component_repository);
        $question_list = $this->addModeParametersToQuestionList($question_list, $area_root);
        // fau.

        return new QuestionsBrowserTable(
            (string) $this->test_obj->getId(),
            $this->current_user,
            $this->ui_factory,
            $this->ui_renderer,
            $this->lng,
            $this->ctrl,
            $this->data_factory,
            $question_list,
            $this->taxonomy,
            $parent_title
        );
    }

    private function insertQuestionsCmd(): void
    {
        $selected_array = $this->http_state->wrapper()->query()->retrieve(
            'qlist_q_id',
            $this->refinery->byTrying([
                $this->refinery->kindlyTo()->listOf($this->refinery->kindlyTo()->int()),
                $this->refinery->kindlyTo()->listOf($this->refinery->kindlyTo()->string()),
                $this->refinery->always([])
            ])
        );

        if ($selected_array === []) {
            $this->main_tpl->setOnScreenMessage('info', $this->lng->txt('tst_insert_missing_question'), true);
            $this->ctrl->redirect($this, self::CMD_BROWSE_QUESTIONS);
        }

        if (in_array('ALL_OBJECTS', $selected_array, true)) {
            // fau: testQuestionBrowserRoot - search only in the selected area
            $filter_data = $this->ui_service->filter()->getData($this->getQuestionsBrowserFilterComponent()) ?? [];
            $area_root = $this->resolveAreaRoot($filter_data);
            unset($filter_data['area_root'], $filter_data['area_ref_id']);
            $selected_array = array_keys(
                $this->getQuestionsBrowserTable('', $area_root)->loadRecords($filter_data)
            );
            // fau.
        }

        array_map(
            fn(int $v): int => $this->test_obj->insertQuestion($v),
            $selected_array
        );

        $this->test_obj->saveCompleteStatus($this->buildTestQuestionSetConfig());

        $this->main_tpl->setOnScreenMessage('success', $this->lng->txt('tst_questions_inserted'), true);

        $this->ctrl->redirectByClass(ilObjTestGUI::class, ilObjTestGUI::SHOW_QUESTIONS_CMD);
    }

    private function handleTabs(): void
    {
        $this->tabs->clearTargets();
        $this->tabs->clearSubTabs();

        $this->tabs->setBackTarget(
            $this->lng->txt('backtocallingtest'),
            $this->ctrl->getLinkTargetByClass(ilObjTestGUI::class, ilObjTestGUI::SHOW_QUESTIONS_CMD)
        );

        $browseQuestionsTabLabel = match ($this->testrequest->raw(self::MODE_PARAMETER)) {
            self::MODE_BROWSE_POOLS => $this->lng->txt('tst_browse_for_qpl_questions'),
            self::MODE_BROWSE_TESTS => $this->lng->txt('tst_browse_for_tst_questions'),
            default => ''
        };

        $this->tabs->addTab(
            self::CMD_BROWSE_QUESTIONS,
            $browseQuestionsTabLabel,
            $this->ctrl->getLinkTarget($this, self::CMD_BROWSE_QUESTIONS)
        );
        $this->tabs->activateTab('browseQuestions');
    }

    private function buildTestQuestionSetConfig(): ilTestQuestionSetConfig
    {
        return (new ilTestQuestionSetConfigFactory(
            $this->tree,
            $this->db,
            $this->lng,
            $this->logger,
            $this->component_repository,
            $this->test_obj,
            $this->questionrepository
        ))->getQuestionSetConfig();
    }

    // fau: testQuestionBrowserRoot - search only in the selected area
    private function addModeParametersToQuestionList(ilAssQuestionList $question_list, int $area_root): ilAssQuestionList
    {
        if ($this->testrequest->raw(self::MODE_PARAMETER) === self::MODE_BROWSE_TESTS) {
            $question_list->setParentObjectType('tst');
            $question_list->setParentObjIdsFilter($this->getTestParentObjIds($area_root) ?: [0]);
            // fau.
            $question_list->setQuestionInstanceTypeFilter(\ilAssQuestionList::QUESTION_INSTANCE_TYPE_ALL);
            $question_list->setExcludeQuestionIdsFilter($this->test_obj->getQuestions());
            return $question_list;
        }

        // fau: testQuestionBrowserRoot - search only in the selected area
        $question_list->setParentObjIdsFilter($this->getQuestionParentObjIds($area_root) ?: [0]);
        // fau.
        $question_list->setQuestionInstanceTypeFilter(\ilAssQuestionList::QUESTION_INSTANCE_TYPE_ORIGINALS);
        $question_list->setExcludeQuestionIdsFilter($this->test_obj->getExistingQuestions());
        return $question_list;
    }

    private function getQuestionParentObjIds(int $repositoryRootNode): array
    {
        $parents = $this->tree->getSubTree(
            $this->tree->getNodeData($repositoryRootNode),
            true,
            ['qpl']
        );

        $parentIds = [];

        foreach ($parents as $nodeData) {
            if ((int) $nodeData['obj_id'] === $this->test_obj->getId()) {
                continue;
            }

            $parentIds[$nodeData['obj_id']] = $nodeData['obj_id'];
        }

        $parentIds = array_map('intval', array_values($parentIds));
        $available_pools = array_map('intval', array_keys(\ilObjQuestionPool::_getAvailableQuestionpools(true)));
        // fau: testQuestionBrowserRoot - search only in the selected area
        return array_values(array_intersect($parentIds, $available_pools));
        // fau.
    }

    // fau: testQuestionBrowserRoot - tests in the selected area with write access
    private function getTestParentObjIds(int $area_root): array
    {
        $obj_ids = [];
        foreach ($this->tree->getSubTree($this->tree->getNodeData($area_root), true, ['tst']) as $node) {
            $obj_id = (int) $node['obj_id'];
            if ($obj_id !== $this->test_obj->getId()
                && $this->access->checkAccess('write', '', (int) $node['child'])) {
                $obj_ids[$obj_id] = $obj_id;
            }
        }
        return array_values($obj_ids);
    }
    // fau.
}
