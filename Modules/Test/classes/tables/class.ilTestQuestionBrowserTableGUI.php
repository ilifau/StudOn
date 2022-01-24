<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once './Services/Table/classes/class.ilTable2GUI.php';
require_once 'Modules/TestQuestionPool/classes/class.ilAssQuestionList.php';

/**
 *
 * @author Helmut Schottmüller <ilias@aurealis.de>
 *
 * @version $Id$
 *
 * @ingroup ModulesGroup
 *
 * @ilCtrl_Calls ilTestQuestionBrowserTableGUI: ilFormPropertyDispatchGUI
 * @ilCtrl_Calls ilTestQuestionBrowserTableGUI: ilTestQuestionBrowserTableGUI
 */
class ilTestQuestionBrowserTableGUI extends ilTable2GUI
{
    const REPOSITORY_ROOT_NODE_ID = 1;
    
    const CONTEXT_PARAMETER = 'question_browse_context';
    const CONTEXT_PAGE_VIEW = 'contextPageView';
    const CONTEXT_LIST_VIEW = 'contextListView';
    
    const MODE_PARAMETER = 'question_browse_mode';
    const MODE_BROWSE_POOLS = 'modeBrowsePools';
    const MODE_BROWSE_TESTS = 'modeBrowseTests';
    
    const CMD_BROWSE_QUESTIONS = 'browseQuestions';
    const CMD_APPLY_FILTER = 'applyFilter';
    const CMD_RESET_FILTER = 'resetFilter';
    const CMD_INSERT_QUESTIONS = 'insertQuestions';
    
    protected $writeAccess = false;

    /**
     * @var \ilCtrl
     */
    protected $ctrl;

    /**
     * @var \ilGlobalTemplateInterface
     */
    protected $mainTpl;

    /**
     * @var \ilTabsGUI
     */
    protected $tabs;
    
    /**
     * @var \ilLanguage
     */
    protected $lng;

    /**
     * @var ilTree
     */
    protected $tree;

    /**
     * @var ilDBInterface
     */
    protected $db;

    /**
     * @var ilPluginAdmin
     */
    protected $pluginAdmin;

    /**
     * @var ilObjTest
     */
    protected $testOBJ;

    /**
     * @var ilAccessHandler
     */
    protected $access;

    /**
     * ilTestQuestionBrowserTableGUI constructor.
     * @param ilCtrl           $ctrl
     * @param ilGlobalTemplateInterface $mainTpl
     * @param ilTabsGUI        $tabs
     * @param ilLanguage       $lng
     * @param ilTree           $tree
     * @param ilDBInterface    $db
     * @param ilPluginAdmin    $pluginAdmin
     * @param ilObjTest        $testOBJ
     * @param ilAccessHandler  $access
     */
    public function __construct(
        ilCtrl $ctrl,
        ilGlobalTemplateInterface $mainTpl,
        ilTabsGUI $tabs,
        ilLanguage $lng,
        ilTree $tree,
        ilDBInterface $db,
        ilPluginAdmin $pluginAdmin,
        ilObjTest $testOBJ,
        ilAccessHandler $access
    ) {
        $this->ctrl = $ctrl;
        $this->mainTpl = $mainTpl;
        $this->tabs = $tabs;
        $this->lng = $lng;
        $this->tree = $tree;
        $this->db = $db;
        $this->pluginAdmin = $pluginAdmin;
        $this->testOBJ = $testOBJ;
        $this->access = $access;

        $this->setId('qpl_brows_tabl_' . $this->testOBJ->getId());

        parent::__construct($this, self::CMD_BROWSE_QUESTIONS);
        $this->setFilterCommand(self::CMD_APPLY_FILTER);
        $this->setResetCommand(self::CMD_RESET_FILTER);
        // fau: testQuestionBrowserRoot - always show the filters
        $this->setDisableFilterHiding(true);
        // fau.

        $this->setFormName('questionbrowser');
        $this->setStyle('table', 'fullwidth');
        $this->addColumn('', '', '1%', true);
        $this->addColumn($this->lng->txt("tst_question_title"), 'title', '');
        $this->addColumn($this->lng->txt("description"), 'description', '');
        $this->addColumn($this->lng->txt("tst_question_type"), 'ttype', '');
        $this->addColumn($this->lng->txt("author"), 'author', '');
        $this->addColumn($this->lng->txt('qst_lifecycle'), 'lifecycle', '');
        $this->addColumn($this->lng->txt("create_date"), 'created', '');
        $this->addColumn($this->lng->txt("last_update"), 'tstamp', '');  // name of col is proper "updated" but in data array the key is "tstamp"
        $this->addColumn($this->getParentObjectLabel(), 'qpl', '');
        $this->addColumn($this->lng->txt("working_time"), 'working_time', '');
        $this->setSelectAllCheckbox('q_id');
        $this->setRowTemplate("tpl.il_as_tst_question_browser_row.html", "Modules/Test");

        $this->setFormAction($this->ctrl->getFormAction($this->getParentObject(), $this->getParentCmd()));
        $this->setDefaultOrderField("title");
        $this->setDefaultOrderDirection("asc");
        
        $this->enable('sort');
        //$this->enable('header');
        $this->enable('select_all');
        $this->initFilter();
        $this->setDisableFilterHiding(true);
    }

    public function setWriteAccess($value)
    {
        $this->writeAccess = $value;
    }

    public function hasWriteAccess()
    {
        return $this->writeAccess;
    }
    
    public function init()
    {
        if ($this->hasWriteAccess()) {
            $this->addMultiCommand(self::CMD_INSERT_QUESTIONS, $this->lng->txt('insert'));
        }
    }

    public function executeCommand()
    {
        $this->handleParameters();
        $this->handleTabs();
        
        switch ($this->ctrl->getNextClass($this)) {
            case strtolower(__CLASS__):
            case '':

                $cmd = $this->ctrl->getCmd() . 'Cmd';
                return $this->$cmd();

            default:

                $this->ctrl->setReturn($this, self::CMD_BROWSE_QUESTIONS);
                return parent::executeCommand();
        }
    }
    
    private function browseQuestionsCmd()
    {
        $this->setData($this->getQuestionsData());
        
        $this->mainTpl->setContent($this->ctrl->getHTML($this));
    }
    
    private function applyFilterCmd()
    {
        $this->writeFilterToSession();
        $this->ctrl->redirect($this, self::CMD_BROWSE_QUESTIONS);
    }
    
    private function resetFilterCmd()
    {
        $this->resetFilter();
        $this->ctrl->redirect($this, self::CMD_BROWSE_QUESTIONS);
    }
    
    private function insertQuestionsCmd()
    {
        $selected_array = (is_array($_POST['q_id'])) ? $_POST['q_id'] : array();
        if (!count($selected_array)) {
            ilUtil::sendInfo($this->lng->txt("tst_insert_missing_question"), true);
            $this->ctrl->redirect($this, self::CMD_BROWSE_QUESTIONS);
        }
        
        include_once "./Modules/TestQuestionPool/classes/class.assQuestion.php";

        $testQuestionSetConfig = $this->buildTestQuestionSetConfig();
        
        $manscoring = false;
        
        foreach ($selected_array as $key => $value) {
            $last_question_id = $this->testOBJ->insertQuestion($testQuestionSetConfig, $value);
            
            if (!$manscoring) {
                $manscoring = $manscoring | assQuestion::_needsManualScoring($value);
            }
        }
        
        $this->testOBJ->saveCompleteStatus($testQuestionSetConfig);
        
        if ($manscoring) {
            ilUtil::sendInfo($this->lng->txt("manscoring_hint"), true);
        } else {
            ilUtil::sendSuccess($this->lng->txt("tst_questions_inserted"), true);
        }

        //$this->ctrl->setParameter($this, 'q_id', $last_question_id); // for page view ?
        
        $this->ctrl->redirectByClass($this->getBackTargetCmdClass(), $this->getBackTargetCommand());
    }
    
    private function handleParameters()
    {
        $this->ctrl->saveParameter($this, self::CONTEXT_PARAMETER);
        if (isset($_GET[self::CONTEXT_PARAMETER])) {
            $this->addHiddenInput(self::CONTEXT_PARAMETER, $_GET[self::CONTEXT_PARAMETER]);
        } elseif (isset($_POST[self::CONTEXT_PARAMETER])) {
            $this->addHiddenInput(self::CONTEXT_PARAMETER, $_POST[self::CONTEXT_PARAMETER]);
        }
        
        $this->ctrl->saveParameter($this, self::MODE_PARAMETER);
        if (isset($_GET[self::MODE_PARAMETER])) {
            $this->addHiddenInput(self::MODE_PARAMETER, $_GET[self::MODE_PARAMETER]);
        } elseif (isset($_POST[self::MODE_PARAMETER])) {
            $this->addHiddenInput(self::MODE_PARAMETER, $_POST[self::MODE_PARAMETER]);
        }
    }

    private function fetchContextParameter()
    {
        if (isset($_POST[self::CONTEXT_PARAMETER])) {
            return $_POST[self::CONTEXT_PARAMETER];
        }

        if (isset($_GET[self::CONTEXT_PARAMETER])) {
            return $_GET[self::CONTEXT_PARAMETER];
        }

        return null;
    }

    private function fetchModeParameter()
    {
        if (isset($_POST[self::MODE_PARAMETER])) {
            return $_POST[self::MODE_PARAMETER];
        }

        if (isset($_GET[self::MODE_PARAMETER])) {
            return $_GET[self::MODE_PARAMETER];
        }
        
        return null;
    }
    
    private function handleTabs()
    {
        $this->tabs->clearTargets();
        $this->tabs->clearSubTabs();
        
        $this->tabs->setBackTarget(
            $this->getBackTargetLabel(),
            $this->getBackTargetUrl()
        );
        
        $this->tabs->addTab(
            'browseQuestions',
            $this->getBrowseQuestionsTabLabel(),
            $this->getBrowseQuestionsTabUrl()
        );
    }
    
    private function getBackTargetLabel()
    {
        return $this->lng->txt('backtocallingtest');
    }

    private function getBackTargetUrl()
    {
        return $this->ctrl->getLinkTargetByClass(
            $this->getBackTargetCmdClass(),
            $this->getBackTargetCommand()
        );
    }
    
    private function getBackTargetCmdClass()
    {
        switch ($this->fetchContextParameter()) {
            case self::CONTEXT_LIST_VIEW:

                return 'ilObjTestGUI';

            case self::CONTEXT_PAGE_VIEW:

                return 'ilTestExpressPageObjectGUI';
        }
        
        return '';
    }
    
    private function getBackTargetCommand()
    {
        switch ($this->fetchContextParameter()) {
            case self::CONTEXT_LIST_VIEW:

                return 'questions';

            case self::CONTEXT_PAGE_VIEW:

                return 'showPage';
        }

        return '';
    }

    private function getBrowseQuestionsTabLabel()
    {
        switch ($this->fetchModeParameter()) {
            case self::MODE_BROWSE_POOLS:

                return $this->lng->txt('tst_browse_for_qpl_questions');

            case self::MODE_BROWSE_TESTS:

                return $this->lng->txt('tst_browse_for_tst_questions');
        }

        return '';
    }

    private function getBrowseQuestionsTabUrl()
    {
        return $this->ctrl->getLinkTarget($this, self::CMD_BROWSE_QUESTIONS);
    }

    public function initFilter()
    {
        // title
        include_once("./Services/Form/classes/class.ilTextInputGUI.php");
        $ti = new ilTextInputGUI($this->lng->txt("tst_qbt_filter_question_title"), "title");
        $ti->setMaxLength(64);
        $ti->setSize(20);
        $ti->setValidationRegexp('/(^[^%]+$)|(^$)/is');
        $this->addFilterItem($ti);
        $ti->readFromSession();
        $this->filter["title"] = $ti->getValue();
        
        // description
        $ti = new ilTextInputGUI($this->lng->txt("description"), "description");
        $ti->setMaxLength(64);
        $ti->setSize(20);
        $ti->setValidationRegexp('/(^[^%]+$)|(^$)/is');
        $this->addFilterItem($ti);
        $ti->readFromSession();
        $this->filter["description"] = $ti->getValue();
        
        // author
        $ti = new ilTextInputGUI($this->lng->txt("author"), "author");
        $ti->setMaxLength(64);
        $ti->setSize(20);
        $this->addFilterItem($ti);
        $ti->setValidationRegexp('/(^[^%]+$)|(^$)/is');
        $ti->readFromSession();
        $this->filter["author"] = $ti->getValue();
        
        // lifecycle
        $lifecycleOptions = array_merge(
            array('' => $this->lng->txt('qst_lifecycle_filter_all')),
            ilAssQuestionLifecycle::getDraftInstance()->getSelectOptions($this->lng)
        );
        $lifecycleInp = new ilSelectInputGUI($this->lng->txt('qst_lifecycle'), 'lifecycle');
        $lifecycleInp->setOptions($lifecycleOptions);
        $this->addFilterItem($lifecycleInp);
        $lifecycleInp->readFromSession();
        $this->filter['lifecycle'] = $lifecycleInp->getValue();
        
        // questiontype
        include_once("./Services/Form/classes/class.ilSelectInputGUI.php");
        include_once("./Modules/TestQuestionPool/classes/class.ilObjQuestionPool.php");
        $types = ilObjQuestionPool::_getQuestionTypes();
        $options = array();
        $options[""] = $this->lng->txt('filter_all_question_types');
        foreach ($types as $translation => $row) {
            $options[$row['type_tag']] = $translation;
        }

        $si = new ilSelectInputGUI($this->lng->txt("question_type"), "type");
        $si->setOptions($options);
        $this->addFilterItem($si);
        $si->readFromSession();
        $this->filter["type"] = $si->getValue();
        
        // question pool
        $ti = new ilTextInputGUI($this->getParentObjectLabel(), 'parent_title');
        $ti->setMaxLength(64);
        $ti->setSize(20);
        $ti->setValidationRegexp('/(^[^%]+$)|(^$)/is');
        $this->addFilterItem($ti);
        $ti->readFromSession();
        $this->filter['parent_title'] = $ti->getValue();
    
        // repo root node
        // fau: testQuestionBrowserRoot - set the default root and show message if no filter is set
        require_once 'Services/Form/classes/class.ilRepositorySelectorInputGUI.php';
        $ri = new ilRepositorySelectorInputGUI($this->lng->txt('container'), 'repository_root_node');
        $ri->setHeaderMessage($this->lng->txt('question_browse_area_info'));
        $ri->setClickableTypes(array('qpl'));
        $this->addFilterItem($ri);
        $ri->readFromSession();
        if (empty($ri->getValue())) {
            ilUtil::sendInfo($this->lng->txt('tst_question_browse_root_message'));
            $ri->setValue($this->getValidRoot());
        }
        // fau.
        $this->filter['repository_root_node'] = $ri->getValue();
    }

    // fau: testQuestionBrowserRoot - use the test object container as default
    private function getValidRoot($a_value = null)
    {
        global $tree;

        if (!empty($a_value)) {
            return $a_value;
        } else {
            return $tree->getParentId($this->testOBJ->getRefId());
        }
    }
    // fau.

    private function getParentObjectLabel()
    {
        switch ($this->fetchModeParameter()) {
            case self::MODE_BROWSE_POOLS:

                return $this->lng->txt('qpl');

            case self::MODE_BROWSE_TESTS:

                return $this->lng->txt('tst');
        }

        return '';
    }
    
    protected function getTranslatedLifecycle($lifecycle)
    {
        try {
            return ilAssQuestionLifecycle::getInstance($lifecycle)->getTranslation($this->lng);
        } catch (ilTestQuestionPoolInvalidArgumentException $e) {
            return '';
        }
    }

    public function fillRow($data)
    {
        $this->tpl->setVariable("QUESTION_ID", $data["question_id"]);
        $this->tpl->setVariable("QUESTION_TITLE", $data["title"]);
        $this->tpl->setVariable("QUESTION_COMMENT", $data["description"]);
        include_once "./Modules/TestQuestionPool/classes/class.assQuestion.php";
        $this->tpl->setVariable("QUESTION_TYPE", assQuestion::_getQuestionTypeName($data["type_tag"]));
        $this->tpl->setVariable("QUESTION_AUTHOR", $data["author"]);
        $this->tpl->setVariable("QUESTION_LIFECYCLE", $this->getTranslatedLifecycle($data['lifecycle']));
        $this->tpl->setVariable("QUESTION_CREATED", ilDatePresentation::formatDate(new ilDate($data['created'], IL_CAL_UNIX)));
        $this->tpl->setVariable("QUESTION_UPDATED", ilDatePresentation::formatDate(new ilDate($data["tstamp"], IL_CAL_UNIX)));
        $this->tpl->setVariable("QUESTION_POOL", $data['parent_title']);
        $this->tpl->setVariable("WORKING_TIME", $data['working_time']);
    }

    /**
     * @return ilTestQuestionSetConfig
     */
    private function buildTestQuestionSetConfig()
    {
        require_once 'Modules/Test/classes/class.ilTestQuestionSetConfigFactory.php';
        
        $testQuestionSetConfigFactory = new ilTestQuestionSetConfigFactory(
            $this->tree,
            $this->db,
            $this->pluginAdmin,
            $this->testOBJ
        );
        
        return $testQuestionSetConfigFactory->getQuestionSetConfig();
    }

    /**
     * @return array
     */
    private function getQuestionsData()
    {
        $questionList = new ilAssQuestionList($this->db, $this->lng, $this->pluginAdmin);

        $questionList->setQuestionInstanceTypeFilter($this->getQuestionInstanceTypeFilter());
        $questionList->setExcludeQuestionIdsFilter($this->testOBJ->getExistingQuestions());

        $repositoryRootNode = self::REPOSITORY_ROOT_NODE_ID;
        
        foreach ($this->getFilterItems() as $item) {
            if ($item->getValue() !== false) {
                switch ($item->getPostVar()) {
                    case 'title':
                    case 'description':
                    case 'author':
                    case 'lifecycle':
                    case 'type':
                    case 'parent_title':
                        
                        $questionList->addFieldFilter($item->getPostVar(), $item->getValue());
                        break;
                    
                    case 'repository_root_node':
                        
                        $repositoryRootNode = $item->getValue();
                }
            }
        }

        // fau: testQuestionBrowserRoot - use the default root if no filter is set
        $repositoryRootNode = $this->getValidRoot($repositoryRootNode);
        // fau.
        $parentObjectIds = $this->getQuestionParentObjIds($repositoryRootNode);
        
        if (!count($parentObjectIds)) {
            return array();
        }
        
        $questionList->setParentObjIdsFilter($parentObjectIds);

        $questionList->load();
        
        return $questionList->getQuestionDataArray();
    }
    
    private function getQuestionInstanceTypeFilter()
    {
        if ($this->fetchModeParameter() == self::MODE_BROWSE_TESTS) {
            return ilAssQuestionList::QUESTION_INSTANCE_TYPE_DUPLICATES;
        }

        return ilAssQuestionList::QUESTION_INSTANCE_TYPE_ORIGINALS;
    }
    
    private function getQuestionParentObjIds($repositoryRootNode)
    {
        // fau: testQuestionBrowserRoot - avoid a duplicate scan for question pools
        if ($this->fetchModeParameter() == self::MODE_BROWSE_POOLS) {
            return array_map('intval', array_keys(ilObjQuestionPool::_getAvailableQuestionpools(
                true,
                false,
                false,
                false,
                false,
                "read",
                "",
                $repositoryRootNode
            )));
        } elseif ($this->fetchModeParameter() == self::MODE_BROWSE_TESTS) {
            $parents = $this->tree->getSubTree(
                $this->tree->getNodeData($repositoryRootNode),
                true,
                $this->getQuestionParentObjectType()
            );

            $parentIds = array();

            foreach ($parents as $nodeData) {
                if ($nodeData['obj_id'] == $this->testOBJ->getId()) {
                    continue;
                }

                $parentIds[ $nodeData['obj_id'] ] = $nodeData['obj_id'];
            }

            $parentIds = array_map('intval', array_values($parentIds));

            $access = $this->access;

            return array_filter($parentIds, function ($obj_id) use ($access) {
                $refIds = ilObject::_getAllReferences($obj_id);
                $refId = current($refIds);
                return $this->access->checkAccess('write', '', $refId);
            });
        }
        // fau.

        // Return no parent ids if the user wants to hack...
        return array();
    }
    
    private function getQuestionParentObjectType()
    {
        if ($this->fetchModeParameter() == self::MODE_BROWSE_TESTS) {
            return 'tst';
        }

        return 'qpl';
    }
}
