<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once('./Services/Wizard/classes/class.ilWizardGUI.php');

/**
* fim: [univis] import wizard for univis lectures
*
* @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
* @version $Id: $
*
* @ilCtrl_Calls ilUnivisImportLecturesGUI:
*/
class ilUnivisImportLecturesGUI extends ilWizardGUI
{
	/**
	* Minimum length of a search pattern
	*/
 	var $min_pattern_length = 3;

	/**
	* Definition of all wizard modes
	*/
	var $mode_definitions = array (

		'category_course_import' => array (
			'title_var' => 'univis_category_course_import',
			'select_mode' => 'multiple',
			'steps' => array (
		        array (
						'cmd' => 'showSearchForm',
						'title_var' => 'univis_lectures_search_title',
						'desc_var' => 'univis_lectures_search_import_desc',
						'prev_cmd' => '',
						'next_cmd' => 'submitSearchForm'
				),
		        array (
						'cmd' => 'showLecturesSelection',
						'title_var' => 'univis_lectures_selection_title',
						'desc_var' => 'univis_lectures_selection_import_desc',
						'prev_cmd' => 'showSearchForm',
						'next_cmd' => 'submitLecturesSelection',
				),
		        array (
						'cmd' => 'showConditionsForm',
						'title_var' => 'univis_lecture_conditions_title',
						'desc_var' => 'univis_lecture_conditions_desc',
						'prev_cmd' => 'showLecturesSelection',
						'next_cmd' => 'submitConditionsForm'
				)
			)
		),

		'category_course_update' => array (
			'title_var' => 'univis_category_course_update',
			'select_mode' => 'multiple',
			'steps' => array (
		        array (
						'cmd' => 'showLecturesSelection',
						'title_var' => 'univis_lectures_selection_title',
						'desc_var' => 'univis_lectures_selection_update_desc',
						'prev_cmd' => '',
						'next_cmd' => 'submitLecturesSelection',
				),
		        array (
						'cmd' => 'showConditionsForm',
						'title_var' => 'univis_lecture_conditions_title',
						'desc_var' => 'univis_lecture_conditions_desc',
						'prev_cmd' => 'showLecturesSelection',
						'next_cmd' => 'submitConditionsForm'
				)
			)
		),

		'course_data_import' => array (
			'title_var' => 'univis_course_data_import',
			'select_mode' => 'single',
			'steps' => array (
		        array (
						'cmd' => 'showSearchForm',
						'title_var' => 'univis_lecture_search_title',
						'desc_var' => 'univis_lecture_search_import_desc',
						'prev_cmd' => '',
						'next_cmd' => 'submitSearchForm'
				),
		        array (
						'cmd' => 'showLecturesSelection',
						'title_var' => 'univis_lecture_selection_title',
						'desc_var' => 'univis_lecture_selection_import_desc',
						'prev_cmd' => 'showSearchForm',
						'next_cmd' => 'submitLecturesSelection',
				),
		        array (
						'cmd' => 'showConditionsForm',
						'title_var' => 'univis_lecture_conditions_title',
						'desc_var' => 'univis_lecture_conditions_desc',
						'prev_cmd' => 'showLecturesSelection',
						'next_cmd' => 'submitConditionsForm'
				)
			)
		),

		'course_data_update' => array (
			'title_var' => 'univis_course_data_update',
			'select_mode' => 'single',
			'steps' => array (
		        array (
						'cmd' => 'showConditionsForm',
						'title_var' => 'univis_lecture_conditions_title',
						'desc_var' => 'univis_lecture_conditions_desc',
						'prev_cmd' => '',
						'next_cmd' => 'submitConditionsForm'
				)
			)
		)
	);


	/**
	* Constructor
	* @access public
	*/
	function __construct($a_parent_gui)
	{
		// init wizard
		parent::__construct($a_parent_gui);

		// specific language vars
		$this->lng->loadLanguageModule("univis");
		$this->lng->loadLanguageModule("crs");
		$this->lng->loadLanguageModule("dateplaner");
		$this->lng->loadLanguageModule('rep');

		// get course constants
		require_once('./Modules/Course/classes/class.ilCourseConstants.php');

		// init import object
		require_once('./Services/UnivIS/classes/class.ilUnivisImport.php');
		$this->import = new ilUnivisImport();
	}


	/**
	* Execute a command (main entry point)
	* @param 	string      specific command to be executed (or empty)
	* @access 	public
	*/
	public function &executeCommand($a_cmd = '')
	{
		global $ilAccess, $ilErr;

		// check if import is allowed for parent object
		switch ($this->parent_type)
		{
	        case 'cat':
				if ($ilAccess->checkAccess('create_crs', '', $this->parent_ref_id))
				{
					break;
				}
	        case 'crs':
				if ($ilAccess->checkAccess('write', '', $this->parent_ref_id))
				{
					break;
				}

			default:
			{
				$ilErr->raiseError($this->lng->txt("permission_denied"),$ilErr->MESSAGE);
			}
		}

		// call the wizard command handling
		return parent::executeCommand($a_cmd);
	}


	/**
	* Start the course import in a category
	*/
	protected function startCategoryCourseImport()
	{
		$this->setMode('category_course_import');
		$this->values->deleteSessionValues('lectures_selection');
		return $this->executeCommand('showSearchForm');
	}


	/**
	* Start the course update in a category
	*/
	protected function startCategoryCourseUpdate()
	{
		$this->setMode('category_course_update');
		$this->values->deleteSessionValues('lectures_selection');

		// get the import ids of visible courses in this categiry
		global $tree, $ilAccess;
		$childs = $tree->getChildsByType($this->parent_ref_id, 'crs');
		$import_ids = array();
		foreach ($childs as $child)
		{
            if ($ilAccess->checkAccess('visible','',$child['ref_id'],'crs',$child['obj_id'])
			and $import_id = ilObject::_getImportIdForObjectId($child['obj_id']))
			{
                $import_ids[] = $import_id;
            }
		}

		// import the lectures data from univis
		$this->import->cleanupLectures();
		foreach ($import_ids as $import_id)
		{
	        if (ilUnivisLecture::_isIliasImportId($import_id))
			{
            	$this->import->importLecture($import_id);
			}
        }

		// check if courses can be updated
		if (!$this->import->countLectures())
		{
	        ilUtil::sendFailure($this->lng->txt('univis_category_course_update_no_found'), true);
			return $this->executeCommand('returnToParent');

	    }
		else
		{
			return $this->executeCommand('showLecturesSelection');
		}
	}

	/**
	* Start the course data import
	*/
	protected function startCourseDataImport()
	{
		$this->setMode('course_data_import');
		$this->values->deleteSessionValues('lectures_selection');
		return $this->executeCommand('showSearchForm');
	}


	/**
	* Start the course data update
	*/
	protected function startCourseDataUpdate()
	{
		$this->setMode('course_data_update');
		$this->values->deleteSessionValues('lectures_selection');

		$import_id = ilObject::_getImportIdForObjectId($this->parent_obj_id);
		$this->import->cleanupLectures();
        $this->import->importLecture($import_id);

		$selected_ids = array();
		foreach (ilUnivisLecture::_getLecturesData() as $lecture_id => $data)
		{
	        $selected_ids[$lecture_id] = $lecture_id;
		}
		$this->values->setSessionValue('lectures_selection','selected_ids', $selected_ids);

		return $this->executeCommand('showConditionsForm');
	}


	/**
	* Start the course data delete
	*/
	protected function startCourseDataDelete()
	{
		global $ilCtrl;

		require_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$form->setTitle($this->lng->txt("univis_course_data_delete"));
		$form->setFormAction($ilCtrl->getFormAction($this));

		$item = new ilCustomInputGUI('','');
		$item->setHtml($this->lng->txt("univis_confirm_course_data_delete"));
		$form->addItem($item);

		$form->addCommandButton('performCourseDataDelete',$this->lng->txt("ok"));
		$form->addCommandButton('returnToParent',$this->lng->txt("cancel"));

		$this->tpl->setRightContent('&nbsp;');
		$this->tpl->setContent($form->getHTML());
		return;
	}


	/**
	* Perform the course data delete
	*/
	protected function performCourseDataDelete()
	{
 		$crs = $this->parent_gui->object;
		$crs->setImportId('');
		$crs->update();

	    ilUtil::sendSuccess($this->lng->txt('univis_course_data_delete_succeeded'), true);
		return $this->executeCommand('returnToParent');
	}


	/**
	* show the search form
	*/
	protected function showSearchForm()
	{
		require_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->search_form = new ilPropertyFormGUI();
		$this->search_form->setTitle($this->lng->txt($this->step['title_var']));
		$this->search_form->setOpenTag(false);
		$this->search_form->setCloseTag(false);

		// search mode
		$search_mode = $this->values->getSessionValue('search_form','search_mode','search_by_title');
		$radio = new ilRadioGroupInputGUI($this->lng->txt('univis_search_mode'),'search_mode');
		$radio->setValue($search_mode);

		// search by title
		$option = new ilRadioOption($this->lng->txt('univis_search_by_title'),'search_by_title');
		$item = new ilTextInputGUI($this->lng->txt('univis_lecture_title'), 'title');
		$item->setInfo($this->lng->txt('univis_search_by_title_info'));
		$item->setValue($search_mode == 'search_by_title' ? $this->values->getSessionValue('search_form','title') : '');
		$option->addSubItem($item);
		$radio->addOption($option);

		// search by department
		$option = new ilRadioOption($this->lng->txt('univis_search_by_department'),'search_by_department');
		$item = new ilTextInputGUI($this->lng->txt('univis_department_title'), 'department_title');
		$item->setInfo($this->lng->txt('univis_search_by_department_info'));
		$item->setValue($search_mode == 'search_by_department' ? $this->values->getSessionValue('search_form','department_title') : '');
		$option->addSubItem($item);
		$department_options = $this->values->getSessionValue('search_form','department_options', array());
		if (count($department_options) > 2)
		{
	        $item = new ilSelectInputGUI($this->lng->txt('univis_select_department'),'department_id');
			$item->setInfo($this->lng->txt('univis_select_department_info'));
			$item->setOptions($department_options);
			$item->setValue($this->values->getSessionValue('search_form','department_id'));
			$option->addSubItem($item);
	    }
		$radio->addOption($option);

		// search by lecturer
		$option = new ilRadioOption($this->lng->txt('univis_search_by_lecturer'),'search_by_lecturer');
		$item = new ilTextInputGUI($this->lng->txt('univis_lecturer_name'), 'lecturer_name');
		$item->setInfo($this->lng->txt('univis_search_by_lecturer_info'));
		$item->setValue($search_mode == 'search_by_lecturer' ? $this->values->getSessionValue('search_form','lecturer_name') : '');
		$option->addSubItem($item);
		$lecturer_options = $this->values->getSessionValue('search_form','lecturer_options', array());
		if (count($lecturer_options) > 2)
		{
	        $item = new ilSelectInputGUI($this->lng->txt('univis_select_lecturer'),'lecturer_id');
			$item->setInfo($this->lng->txt('univis_select_lecturer_info'));
			$item->setOptions($lecturer_options);
			$item->setValue($this->values->getSessionValue('search_form','lecturer_id'));
			$option->addSubItem($item);
	    }
		$radio->addOption($option);

		$this->search_form->addItem($radio);

	    return $this->output($this->search_form->getHTML());
	}


	/**
	* submit the search form
	*/
	protected function submitSearchForm()
	{
	    // this will be set to true if a search has to be refined
		$retry = false;

		$search_mode = $this->values->saveRequestValue('search_form', 'search_mode');
		switch($search_mode)
		{
	        case 'search_by_title':

             	$title = $this->values->saveRequestValue('search_form', 'title');

				if (strlen($title) < $this->min_pattern_length)
				{
	                ilUtil::sendFailure(sprintf($this->lng->txt('univis_pattern_too_short'), $this->min_pattern_length));
					$retry = true;
				}
				else
				{
	                $found = $this->import->importLectures($title);
					if ($found === false)
					{
		            	ilUtil::sendFailure($this->import->getErrorMessage().$this->lng->txt('univis_search_info_retry'));
						$retry = true;
					}
					elseif ($found == 0)
					{
		            	ilUtil::sendFailure($this->lng->txt('univis_no_lectures_found').$this->lng->txt('univis_search_info_retry'));
						$retry = true;
					}
				}
 				break;

	        case 'search_by_department':

				$department_id = $this->values->saveRequestValue('search_form', 'department_id');
                $department_title = $this->values->saveRequestValue('search_form', 'department_title');

				// seach by department id (orgnr)
				if ($department_id)
				{
	                $found = $this->import->importLectures('','',$department_id);
					if ($found === false)
					{
		            	ilUtil::sendFailure($this->import->getErrorMessage().$this->lng->txt('univis_search_info_retry'));
						$retry = true;
					}
					elseif ($found == 0)
					{
		            	ilUtil::sendFailure($this->lng->txt('univis_no_lectures_found').$this->lng->txt('univis_search_info_retry'));
						$retry = true;
					}
				}
				// check search pattern
				elseif (strlen($department_title) < $this->min_pattern_length)
				{
	                ilUtil::sendFailure(sprintf($this->lng->txt('univis_pattern_too_short'), $this->min_pattern_length));
					$retry = true;
				}
				// search by department title
				else
				{
					$found = $this->import->importDepartments($department_title);
					$department_options = ilUnivisDepartment::_getOptionsForLectureSearch(true);
					$this->values->setSessionValue('search_form','department_options', $department_options);

					if ($found === false)
					{
		            	ilUtil::sendFailure($this->import->getErrorMessage().$this->lng->txt('univis_search_info_retry'));
						$retry = true;
					}
					elseif ($found == 0)
					{
		            	ilUtil::sendFailure($this->lng->txt('univis_no_department_found').$this->lng->txt('univis_search_info_retry'));
						$retry = true;
					}
					elseif ($found > 1)
					{
		                ilUtil::sendFailure($this->lng->txt('univis_refine_department'));
						$retry = true;
					}
					else
					{
	                    // only one department found => search by its id
						$department_id = key(ilUnivisDepartment::_getOptionsForLectureSearch(false));
						$this->values->setSessionValue('search_form','department_id', $department_id);

		                $found = $this->import->importLectures('','',$department_id);
						if ($found === false)
						{
			            	ilUtil::sendFailure($this->import->getErrorMessage().$this->lng->txt('univis_search_info_retry'));
							$retry = true;
						}
						elseif ($found == 0)
						{
			            	ilUtil::sendFailure($this->lng->txt('univis_no_lectures_found').$this->lng->txt('univis_search_info_retry'));
							$retry = true;
						}
					}
				}
				break;


	        case 'search_by_lecturer':

				$lecturer_id = $this->values->saveRequestValue('search_form', 'lecturer_id');
                $lecturer_name = $this->values->saveRequestValue('search_form', 'lecturer_name');

				// seach by lecturer id
				if ($lecturer_id)
				{
	                $found = $this->import->importLectures('', $lecturer_id);
					if ($found === false)
					{
		            	ilUtil::sendFailure($this->import->getErrorMessage().$this->lng->txt('univis_search_info_retry'));
						$retry = true;
					}
					elseif ($found == 0)
					{
		            	ilUtil::sendFailure($this->lng->txt('univis_no_lectures_found').$this->lng->txt('univis_search_info_retry'));
						$retry = true;
					}
				}
				// check search pattern
				elseif (strlen($lecturer_name) < $this->min_pattern_length)
				{
	                ilUtil::sendFailure(sprintf($this->lng->txt('univis_pattern_too_short'), $this->min_pattern_length));
					$retry = true;
				}
				// search by lecturer name
				else
				{
					$found = $this->import->importPersons($lecturer_name);
					$lecturer_options = ilUnivisPerson::_getOptionsForLectureSearch(true);
					$this->values->setSessionValue('search_form','lecturer_options', $lecturer_options);

					if ($found === false)
					{
		            	ilUtil::sendFailure($this->import->getErrorMessage().$this->lng->txt('univis_search_info_retry'));
						$retry = true;
					}
					elseif ($found == 0)
					{
		            	ilUtil::sendFailure($this->lng->txt('univis_no_lecturer_found').$this->lng->txt('univis_search_info_retry'));
						$retry = true;
					}
					elseif ($found > 1)
					{
		                ilUtil::sendFailure($this->lng->txt('univis_refine_lecturer'));
						$retry = true;
					}
					else
					{
	                    // only one lecturer found => search by its id
						$lecturer_id = key(ilUnivisPerson::_getOptionsForLectureSearch(false));
						$this->values->setSessionValue('search_form','lecturer_id', $lecturer_id);

		                $found = $this->import->importLectures('', $lecturer_id);
						if ($found === false)
						{
			            	ilUtil::sendFailure($this->import->getErrorMessage().$this->lng->txt('univis_search_info_retry'));
							$retry = true;
						}
						elseif ($found == 0)
						{
			            	ilUtil::sendFailure($this->lng->txt('univis_no_lectures_found').$this->lng->txt('univis_search_info_retry'));
							$retry = true;
						}
					}
				}
				break;

			default:
				$retry = true;
	    }

		// choose next or previous step
		if ($retry)
		{
		    return $this->executeCommand('showSearchForm');
		}
		else
		{
 			$this->values->deleteSessionValues('lectures_selection');
			return $this->executeCommand('showLecturesSelection');
		}
	}


	/**
	* show the selection of lectures
	*/
	protected function showLecturesSelection($a_nav_value = '')
	{
		// build the table of form definitions
		include_once 'Services/UnivIS/classes/class.ilUnivisImportLecturesTableGUI.php';
		$table_gui = new ilUnivisImportLecturesTableGUI($this, 'showLecturesSelection');
		$table_gui->setFormName($this->getFormName());
		$table_gui->setParentObjId($this->parent_obj_id);
		$table_gui->setWizardMode($this->mode);
		$table_gui->setSelectMode($this->mode_data['select_mode']);
		$table_gui->setSelectedIds($this->updateLecturesSelection());
		return $this->output($table_gui->getHTML());
	}


	/**
	* update the list of selected lecture ids
	*/
	protected function updateLecturesSelection()
	{
		// array (id => id)
		$selected_ids = $this->values->getSessionValue('lectures_selection','selected_ids');
		$selected_ids = is_array($selected_ids) ? $selected_ids : array();

		// array (id1, id2, ...)
		$checked_ids = $this->values->getRequestValue('checked_ids');
		$hidden_ids = $this->values->getRequestValue('hidden_ids');

		$checked_ids = is_array($checked_ids) ? $checked_ids : array();
		$hidden_ids = is_array($hidden_ids) ? $hidden_ids : array();

		switch ($this->mode_data['select_mode'])
		{
	        case 'multiple':
				foreach ($hidden_ids as $id)
				{
			        if (in_array($id, $checked_ids))
					{
			            $selected_ids[$id] = $id;
			        }
					else
					{
			            unset($selected_ids[$id]);
			        }
				}
				break;

			case 'single':
	            if ($id = current($checked_ids))
				{
	                $selected_ids = array($id => $id);
				}
				else
				{
					$selected_ids = array();
	            }
				break;
		}

		$this->values->setSessionValue('lectures_selection','selected_ids', $selected_ids);
		return $selected_ids;
	}


	/**
	* submit the selection of lectures
	*/
	protected function submitLecturesSelection()
	{
	    if (count($this->updateLecturesSelection()))
		{
			return $this->executeCommand('showConditionsForm');
		}
		else
		{
			ilUtil::sendFailure($this->lng->txt('univis_no_lectures_selected'));
		    return $this->executeCommand('showLecturesSelection');
 		}
	}


	/**
	* show the form for import conditions
	 * @return ilPropertyFormGUI
	*/
	protected function initConditionsForm()
	{
		require_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->conditions_form = new ilPropertyFormGUI();
		$this->conditions_form->setOpenTag(false);
		$this->conditions_form->setCloseTag(false);


		// header: show in list
		$header = new ilFormSectionHeaderGUI();
		$header->setTitle($this->lng->txt('univis_header_show_on_list'));
        $this->conditions_form->addItem($header);

		//  set title
		$item = new ilCheckboxInputGUI($this->lng->txt('title'),'set_title');
		$item->setOptionTitle($this->lng->txt('univis_set_title'));
		$item->setInfo($this->lng->txt('univis_info_set_title'));
		$item->setChecked($this->values->getSessionValue('conditions_form','set_title','1'));

				// short title
				$subitem = new ilCheckboxInputGUI('','set_title_short');
				$subitem->setOptionTitle($this->lng->txt('univis_add_title_short'));
				$subitem->setChecked($this->values->getSessionValue('conditions_form','set_title_short','0'));
				$item->addSubItem($subitem);

       	$this->conditions_form->addItem($item);

		//  description
		$item = new ilCheckboxInputGUI($this->lng->txt('description'),'set_description');
		$item->setOptionTitle($this->lng->txt('univis_set_description'));
		$item->setInfo($this->lng->txt('univis_info_set_description'));
		$item->setChecked($this->values->getSessionValue('conditions_form','set_description','1'));

			// short info
			$subitem = new ilCheckboxInputGUI('','set_description_short_info');
				$subitem->setOptionTitle($this->lng->txt('univis_short_info'));
			$subitem->setChecked($this->values->getSessionValue('conditions_form','set_description_short_info','1'));
			$item->addSubItem($subitem);

			// lecturer
			$subitem = new ilCheckboxInputGUI('','set_description_lecturer');
			$subitem->setOptionTitle($this->lng->txt('univis_lecturer'));
			$subitem->setChecked($this->values->getSessionValue('conditions_form','set_description_lecturer','1'));
			$item->addSubItem($subitem);

			// comment
			$subitem = new ilCheckboxInputGUI('','set_description_comment');
				$subitem->setOptionTitle($this->lng->txt('univis_comment'));
			$subitem->setChecked($this->values->getSessionValue('conditions_form','set_description_comment','0'));
			$item->addSubItem($subitem);

			// terms
			$subitem = new ilCheckboxInputGUI('','set_description_terms');
			$subitem->setOptionTitle($this->lng->txt('univis_terms'));
			$subitem->setChecked($this->values->getSessionValue('conditions_form','set_description_terms','0'));
			$item->addSubItem($subitem);


		$this->conditions_form->addItem($item);

		// header: show on info screen
		$header = new ilFormSectionHeaderGUI();
		$header->setTitle($this->lng->txt('univis_header_show_on_info_screen'));
        $this->conditions_form->addItem($header);

		// syllabus
		$item = new ilCheckboxInputGUI($this->lng->txt('crs_syllabus'),'set_syllabus');
		$item->setOptionTitle($this->lng->txt('univis_set_syllabus'));
		$item->setInfo($this->lng->txt('univis_info_set_syllabus'));
		$item->setChecked($this->values->getSessionValue('conditions_form','set_syllabus','0'));

			// lecturers
			$subitem = new ilCheckboxInputGUI('','set_syllabus_lecturers');
			$subitem->setOptionTitle($this->lng->txt('univis_lecturer'));
			$subitem->setChecked($this->values->getSessionValue('conditions_form','set_syllabus_lecturers','1'));
			$item->addSubItem($subitem);

			// info
			$subitem = new ilCheckboxInputGUI('','set_syllabus_info');
			$subitem->setOptionTitle($this->lng->txt('univis_info'));
			$subitem->setChecked($this->values->getSessionValue('conditions_form','set_syllabus_info','1'));
			$item->addSubItem($subitem);

			// info comment
			$subitem = new ilCheckboxInputGUI('','set_syllabus_comment');
			$subitem->setOptionTitle($this->lng->txt('univis_comment'));
			$subitem->setChecked($this->values->getSessionValue('conditions_form','set_syllabus_comment','1'));
			$item->addSubItem($subitem);

			// terms
			$subitem = new ilCheckboxInputGUI('','set_syllabus_terms');
			$subitem->setOptionTitle($this->lng->txt('univis_terms'));
			$subitem->setChecked($this->values->getSessionValue('conditions_form','set_syllabus_terms','1'));
			$item->addSubItem($subitem);

			// studies
			$subitem = new ilCheckboxInputGUI('','set_syllabus_studies');
			$subitem->setOptionTitle($this->lng->txt('univis_studies'));
			$subitem->setChecked($this->values->getSessionValue('conditions_form','set_syllabus_studies','1'));
			$item->addSubItem($subitem);

			// prerequisites
			$subitem = new ilCheckboxInputGUI('','set_syllabus_prerequisites');
			$subitem->setOptionTitle($this->lng->txt('univis_prerequisites'));
			$subitem->setChecked($this->values->getSessionValue('conditions_form','set_syllabus_prerequisites','1'));
			$item->addSubItem($subitem);

			// summary
			$subitem = new ilCheckboxInputGUI('','set_syllabus_summary');
			$subitem->setOptionTitle($this->lng->txt('univis_summary'));
			$subitem->setChecked($this->values->getSessionValue('conditions_form','set_syllabus_summary','1'));
			$item->addSubItem($subitem);

			// literature
			$subitem = new ilCheckboxInputGUI('','set_syllabus_literature');
			$subitem->setOptionTitle($this->lng->txt('univis_literature'));
			$subitem->setChecked($this->values->getSessionValue('conditions_form','set_syllabus_literature','1'));
			$item->addSubItem($subitem);

			// ects
			$subitem = new ilCheckboxInputGUI('','set_syllabus_ects_info');
			$subitem->setOptionTitle($this->lng->txt('univis_ects_info'));
			$subitem->setChecked($this->values->getSessionValue('conditions_form','set_syllabus_ects_info','1'));
			$item->addSubItem($subitem);

			// additional info
			$subitem = new ilCheckboxInputGUI('','set_syllabus_additional_info');
			$subitem->setOptionTitle($this->lng->txt('univis_additional_info'));
			$subitem->setChecked($this->values->getSessionValue('conditions_form','set_syllabus_additional_info','1'));
			$item->addSubItem($subitem);

			// institution
			$subitem = new ilCheckboxInputGUI('','set_syllabus_institution');
			$subitem->setOptionTitle($this->lng->txt('univis_institution'));
			$subitem->setChecked($this->values->getSessionValue('conditions_form','set_syllabus_institution','1'));
			$item->addSubItem($subitem);

      	$this->conditions_form->addItem($item);


		// contact
		$radio = new ilRadioGroupInputGUI($this->lng->txt('univis_contact_data'),'set_contact');
		$radio->setValue($this->values->getSessionValue('conditions_form','set_contact','not'));

			// not
			$option = new ilRadioOption($this->lng->txt('univis_set_contact_not'),'not');
			$radio->addOption($option);
			// first lecturer
			$option = new ilRadioOption($this->lng->txt('univis_set_contact_first'),'first');
			$radio->addOption($option);
			// own setting
			$option = new ilRadioOption($this->lng->txt('univis_set_contact_own'),'own');

				//name
				$subitem = new ilTextInputGUI($this->lng->txt('crs_contact_name'), 'contact_name');
				$subitem->setValue($this->values->getSessionValue('conditions_form','contact_name'));
				$option->addSubItem($subitem);

				//responsibility
				$subitem = new ilTextInputGUI($this->lng->txt('crs_contact_responsibility'), 'contact_responsibility');
				$subitem->setValue($this->values->getSessionValue('conditions_form','contact_responsibility'));
				$option->addSubItem($subitem);

				//phone
				$subitem = new ilTextInputGUI($this->lng->txt('crs_contact_phone'), 'contact_phone');
				$subitem->setValue($this->values->getSessionValue('conditions_form','contact_phone'));
				$option->addSubItem($subitem);

				//email
				$subitem = new ilTextInputGUI($this->lng->txt('crs_contact_email'), 'contact_email');
				$subitem->setValue($this->values->getSessionValue('conditions_form','contact_email'));
				$option->addSubItem($subitem);

				//consultation
				$subitem = new ilTextAreaInputGUI($this->lng->txt('crs_contact_consultation'), 'contact_consultation');
				$subitem->setValue($this->values->getSessionValue('conditions_form','contact_consultation'));
				$option->addSubItem($subitem);

			$radio->addOption($option);
      	$this->conditions_form->addItem($radio);

		// header: settings
		$header = new ilFormSectionHeaderGUI();
		$header->setTitle($this->lng->txt('univis_header_settings'));
		$header->setInfo($this->lng->txt('univis_header_settings_desc'));
        $this->conditions_form->addItem($header);

		// settings info
		$item = new ilCustomInputGUI();
		$item->setInfo($this->lng->txt('univis_header_settings_desc'));
        $this->conditions_form->addItem($item);

		// set activation
		$item = new ilCheckboxInputGUI($this->lng->txt('crs_visibility'),'set_activation');
		$item->setOptionTitle($this->lng->txt('univis_set_activation'));
		$item->setChecked($this->values->getSessionValue('conditions_form','set_activation','0'));

			$online = new ilCheckboxInputGUI($this->lng->txt('rep_activation_online'),'activation_online');
			$online->setChecked($this->values->getSessionValue('conditions_form','activation_online','0'));
			$online->setInfo($this->lng->txt('crs_activation_online_info'));
			$item->addSubItem($online);

			$act_type = new ilCheckboxInputGUI($this->lng->txt('crs_visibility_until'), 'activation_type');
			$act_type->setChecked($this->values->getSessionValue('conditions_form','activation_type','0'));

				$this->tpl->addJavaScript('./Services/Form/js/date_duration.js');
				include_once "Services/Form/classes/class.ilDateDurationInputGUI.php";
				$dur = new ilDateDurationInputGUI($this->lng->txt('rep_time_period'), "access_period");
				$dur->setShowTime(true);
				$dur->setStart(new ilDateTime($this->values->getSessionValue('conditions_form', 'access_start', time()), IL_CAL_UNIX));
				$dur->setStartText($this->lng->txt('rep_activation_limited_start'));
				$dur->setEnd(new ilDateTime($this->values->getSessionValue('conditions_form','access_end', mktime(0,0,0,12,31,date("Y",time())+1)), IL_CAL_UNIX));
				$dur->setEndText($this->lng->txt('rep_activation_limited_end'));
				$act_type->addSubItem($dur);

				$visible = new ilCheckboxInputGUI($this->lng->txt('rep_activation_limited_visibility'), 'activation_visibility');
				$visible->setInfo($this->lng->txt('crs_activation_limited_visibility_info'));
				$visible->setChecked($this->values->getSessionValue('conditions_form','activation_visibility','0'));
				$act_type->addSubItem($visible);

			$item->addSubItem($act_type);

        $this->conditions_form->addItem($item);

		// set registration
		$item = new ilCheckboxInputGUI($this->lng->txt('crs_reg'),'set_registration');
		$item->setOptionTitle($this->lng->txt('univis_set_registration'));
		$item->setInfo($this->lng->txt('univis_info_set_registration'));
		$item->setChecked($this->values->getSessionValue('conditions_form','set_registration','0'));

			// registration type
			$reg_type = new ilRadioGroupInputGUI($this->lng->txt('crs_registration_limited'),'subscription_limitation_type');
			$reg_type->setValue($this->values->getSessionValue('conditions_form','subscription_limitation_type',IL_CRS_SUBSCRIPTION_UNIVIS));

				$opt = new ilRadioOption($this->lng->txt('crs_reg_univis'),IL_CRS_SUBSCRIPTION_UNIVIS);
				$opt->setInfo($this->lng->txt('crs_reg_univis_info'));
				$reg_type->addOption($opt);
			
				$opt = new ilRadioOption($this->lng->txt('crs_reg_no_selfreg'),IL_CRS_SUBSCRIPTION_DEACTIVATED);
				$opt->setInfo($this->lng->txt('crs_registration_deactivated'));
				$reg_type->addOption($opt);

				$opt = new ilRadioOption($this->lng->txt('mem_unlimited'),IL_CRS_SUBSCRIPTION_UNLIMITED);
				$reg_type->addOption($opt);

				$opt = new ilRadioOption($this->lng->txt('crs_registration_limited'),IL_CRS_SUBSCRIPTION_LIMITED);

					$this->tpl->addJavaScript('./Services/Form/js/date_duration.js');
					include_once "Services/Form/classes/class.ilDateDurationInputGUI.php";
					$dur = new ilDateDurationInputGUI($this->lng->txt('crs_registration_period'), "subscription_period");
					$dur->setShowTime(true);

					$dur->setStart(new ilDateTime($this->values->getSessionValue('conditions_form','subscription_start', time()), IL_CAL_UNIX));
					$dur->setStartText($this->lng->txt('crs_start'));
					$dur->setEnd(new ilDateTime($this->values->getSessionValue('conditions_form','subscription_end', mktime(0,0,0,12,31,date("Y",time())+1)),IL_CAL_UNIX));
					$dur->setEndText($this->lng->txt('crs_end'));
					// fim: [memad] show deny time for registration

					$deny_regstart_from = ilCust::get('ilias_deny_regstart_from');
					$deny_regstart_to = ilCust::get('ilias_deny_regstart_to');
					if ($deny_regstart_from and $deny_regstart_to)
					{
						$deny_regstart_from = new ilDateTime($deny_regstart_from, IL_CAL_DATETIME);
						$deny_regstart_to = new ilDateTime($deny_regstart_to, IL_CAL_DATETIME);
						$dur->setInfo(sprintf($this->lng->txt('deny_regstart_message'),
							ilDatePresentation::formatDate($deny_regstart_from),
							ilDatePresentation::formatDate($deny_regstart_to)));
					}
					// fim.
					$opt->addSubItem($dur);

				$reg_type->addOption($opt);
			$item->addSubItem($reg_type);

			// registration procedure
			$reg_proc = new ilRadioGroupInputGUI($this->lng->txt('crs_registration_type'),'subscription_type');
			$reg_proc->setValue($this->values->getSessionValue('conditions_form','subscription_type',IL_CRS_SUBSCRIPTION_CONFIRMATION));

				$opt = new ilRadioOption($this->lng->txt('crs_subscription_options_confirmation'),IL_CRS_SUBSCRIPTION_CONFIRMATION);
				$reg_proc->addOption($opt);
				$opt = new ilRadioOption($this->lng->txt('crs_subscription_options_direct'),IL_CRS_SUBSCRIPTION_DIRECT);
				$reg_proc->addOption($opt);
				$opt = new ilRadioOption($this->lng->txt('crs_subscription_options_password'),IL_CRS_SUBSCRIPTION_PASSWORD);
					$pass = new ilTextInputGUI('','subscription_password');
					$pass->setSubmitFormOnEnter(true);
					$pass->setSize(12);
					$pass->setMaxLength(32);
					$pass->setValue($this->values->getSessionValue('conditions_form','subscription_password'));
					$opt->addSubItem($pass);
				$reg_proc->addOption($opt);
			$item->addSubItem($reg_proc);
       	$this->conditions_form->addItem($item);

		// membership limitation
		$item = new ilCheckboxInputGUI($this->lng->txt('crs_subscription_max_members_short'),'set_membership_limitation');
		$item->setOptionTitle($this->lng->txt('univis_set_membership_limitation'));
		$item->setInfo($this->lng->txt('univis_info_set_membership_limitation'));
		$item->setChecked($this->values->getSessionValue('conditions_form','set_membership_limitation',''));

// fau: fairSub - add fair info and arrange and explain options for waiting list
		$fair_date = new ilNonEditableValueGUI($this->lng->txt('sub_fair_date'));
		$fair_date->setValue($this->lng->txt('sub_fair_date_default'));
		$fair_date->setInfo($this->lng->txt('sub_fair_date_info'));
		$item->addSubItem($fair_date);

		$wait = new ilRadioGroupInputGUI($this->lng->txt("crs_waiting_list"),'waiting_list');
		$wait->setValue($this->values->getSessionValue('conditions_form','waiting_list','univis'));

		$option = new ilRadioOption($this->lng->txt('sub_fair_waiting_univis'),'univis');
		$option->setInfo($this->lng->txt('sub_fair_waiting_univis_info'));
		$wait->addOption($option);

		$option = new ilRadioOption($this->lng->txt('sub_fair_autofill'), 'auto');
		$option->setInfo($this->lng->txt('sub_fair_autofill_info'));
		$wait->addOption($option);

		$option = new ilRadioOption($this->lng->txt('sub_fair_auto_manu'), 'auto_manu');
		$option->setInfo($this->lng->txt('sub_fair_auto_manu_info'));
		$wait->addOption($option);

		$option = new ilRadioOption($this->lng->txt('sub_fair_waiting'), 'manu');
		$option->setInfo($this->lng->txt('sub_fair_waiting_info'));
		$wait->addOption($option);

		$option = new ilRadioOption($this->lng->txt('sub_fair_no_list'), 'no_list');
		$option->setInfo($this->lng->txt('sub_fair_no_list_info'));
		$wait->addOption($option);

		$item->addSubItem($wait);
// fau.
       $this->conditions_form->addItem($item);

       	// fim: [evasys] add item for evaluation
		require_once ("Services/Evaluation/classes/class.ilEvaluationData.php");
		if (ilEvaluationData::_isEvaluationActivated($this->parent_ref_id))
		{
			$eval = new ilCheckboxInputGUI($this->lng->txt('eval_mark_for_evaluation'),'mark_for_evaluation');
			$eval->setInfo($this->lng->txt('eval_mark_for_evaluation_info_import'));
			$this->conditions_form->addItem($eval);
		}
		// fim.


		return $this->conditions_form;
	}

	/**
	 * Show the import conditions form
	 */
	protected function showConditionsForm()
	{
		$form = $this->initConditionsForm();
		return $this->output($form->getHTML());
	}


	/**
	* submit the form for import conditions
	*/
	protected function submitConditionsForm()
	{
		$form = $this->initConditionsForm();
		$form->checkInput();
		//$form->setValuesByPost();

	    $request_names = array (
	        'set_title',
			'set_title_short',
			'set_description',
			'set_description_short_info',
		    'set_description_lecturer',
			'set_description_comment',
			'set_description_terms',
			'set_syllabus',
			'set_syllabus_lecturers',
			'set_syllabus_info',
			'set_syllabus_comment',
			'set_syllabus_terms',
			'set_syllabus_studies',
			'set_syllabus_prerequisites',
			'set_syllabus_ects_info',
			'set_syllabus_summary',
			'set_syllabus_literature',
			'set_syllabus_additional_info',
			'set_syllabus_institution',
			'set_contact',
			'contact_name',
			'contact_phone',
			'contact_email',
			'contact_consultation',
			'set_activation',
		    'activation_online',
			'activation_type',
		    'activation_visibility',
			'set_registration',
			'subscription_limitation_type',
			'subscription_type',
			'subscription_password',
			'set_membership_limitation',
			'waiting_list',
	    	'mark_for_evaluation'
		);

		foreach ($request_names as $name)
		{
            $this->values->saveRequestValue('conditions_form', $name);
  		}

  		/** @var ilDateDurationInputGUI $access_period */
  		$access_period = $form->getItemByPostVar('access_period');
  		if (!empty($access_period->getStart()))
		{
			$this->values->setSessionValue('conditions_form', 'access_start', $access_period->getStart()->get(IL_CAL_UNIX));
		}
		if (!empty($access_period->getEnd()))
		{
			$this->values->setSessionValue('conditions_form', 'access_end', $access_period->getEnd()->get(IL_CAL_UNIX));
		}

		/** @var ilDateDurationInputGUI $subscription_period */
		$subscription_period = $form->getItemByPostVar('subscription_period');
		if (!empty($subscription_period->getStart()))
		{
			$this->values->setSessionValue('conditions_form', 'subscription_start', $subscription_period->getStart()->get(IL_CAL_UNIX));
		}
		if (!empty($subscription_period->getEnd()))
		{
			$this->values->setSessionValue('conditions_form', 'subscription_end', $subscription_period->getEnd()->get(IL_CAL_UNIX));
		}


		// retry if checks failes
		$retry = false;

		// choose next or previous step
		if ($retry)
		{
	    	return $this->executeCommand('showConditionsForm');
		}
		else
		{
 		    return $this->executeCommand('performImport');
		}
	}

	/**
	* import the lectures data
	*/
	protected function performImport()
	{
	    global $ilAccess, $ilUser;

		require_once('./Modules/Course/classes/class.ilObjCourse.php');
		require_once('./Services/Tracking/classes/class.ilChangeEvent.php');
		
		// initialize the counters
		$imported = 0;
		$updated = 0;
		$ignored = 0;

	    // get the list of lectures to import
		$selected_ids = $this->values->getSessionValue('lectures_selection','selected_ids');
		$selected_ids = is_array($selected_ids) ? $selected_ids : array();

		// get the array of import conditions for quick access
		$cond = $this->values->getSessionValues('conditions_form');

		// loop over all selected lectures
		foreach ($selected_ids as $lecture_id => $dummy)
		{
	        // get lecture data
            $lecture = new ilUnivisLecture($lecture_id);
			$import_id = $lecture->getIliasImportId();

 			// check for existing course
			$obj_id = ilObject::_lookupObjIdByImportId($import_id);
            if (ilObject::_hasUntrashedReference($obj_id))
			{
	            // get the ref_id of an untrashed course
				$ref_id = current(ilObject::_getAllReferences($obj_id));
			}
			else
			{
                // clear import id in trash
				ilObject::_writeImportId($obj_id, '');
				$ref_id = 0;
				$obj_id = 0;
			}

			// check what to do
			switch ($this->mode)
			{
            	case 'category_course_import':
					if ($ref_id)
					{
						$ignored++;
						continue 2; //foreach
			        }
					else
					{
			            $imported++;
						$crs = new ilObjCourse();
						$crs->setType('crs');
						$crs->setTitle($lecture->getDisplayTitle());
						$crs->setDescription('');
						$crs->create();
						$crs->createReference();
						$crs->putInTree($this->parent_ref_id);
						$crs->setPermissions($this->parent_ref_id);
						
						include_once "./Modules/Course/classes/class.ilCourseParticipants.php";
						$members_obj = ilCourseParticipants::_getInstanceByObjId($crs->getId());
						$members_obj->add($ilUser->getId(),IL_CRS_ADMIN);
						$members_obj->updateNotification($ilUser->getId(),1);

						if (ilChangeEvent::_isActive())
						{
							ilChangeEvent::_recordWriteEvent($crs->getId(), $ilUser->getId(), 'create');
						}
					}
					break;

				case 'category_course_update':
					if (!$ref_id or !$ilAccess->checkAccess("write", '', $ref_id, 'crs', $obj_id))
					{
	                    $ignored++;
						continue 2;  //foreach
					}
					else
					{
	                    $updated++;
			            $crs = new ilObjCourse($ref_id);
					}
					break;

				case 'course_data_import':
					if ($ref_id)
					{
						$ignored++;
						continue 2; //foreach
			        }
					else
					{
	                    $updated++;
						$ref_id = $this->parent_ref_id;
						$obj_id = $this->parent_obj_id;
						$crs = $this->parent_gui->object;
	                }
					break;

				case 'course_data_update':
					if ($ref_id != $this->parent_ref_id)
					{
	                    $ignored++;
						continue 2;  //foreach
					}
					else
					{
	                    $updated++;
						$obj_id = $this->parent_obj_id;
						$crs = $this->parent_gui->object;
	                }
					break;

				default:
                    $ignored++;
					continue 2;  //foreach
	        }

			// set always the import id
			$crs->setImportId($import_id);

			// set title
			if ($cond['set_title'])
			{
				$crs->setTitle($lecture->getDisplayTitle(false, $cond['set_title_short']));
	        }

			// set description
			if ($cond['set_description'])
			{
	            $parts = array();
				if ($cond['set_description_short_info'])
				{
	                $parts[] = $lecture->getDisplayInfoShort(false);
				}
				if ($cond['set_description_lecturer'])
				{
					$parts[] = $lecture->getDisplayLecturers(false);
				}
				if ($cond['set_description_comment'])
				{
	                $parts[] = $lecture->getDisplayComment();
				}
				$description = implode("; ", $parts);

				if ($cond['set_description_terms'])
				{
	                if ($terms = $lecture->getDisplayTerms(false))
					{
						$description .= '<br />'. $terms;
					}
				}
				$crs->setDescription($description);
	        }

			// set syllabus
			if ($cond['set_syllabus'])
			{
	           $parts = array();

				if ($cond['set_syllabus_lecturers'])
				{
	                $parts['univis_lecturer'] = $lecture->getDisplayLecturers(true);
				}
				if ($cond['set_syllabus_info'])
				{
	                $parts['univis_info'] = $lecture->getDisplayInfo(true, $cond['set_syllabus_comment']);
				}
				if ($cond['set_syllabus_terms'])
				{
	                $parts['univis_terms'] = $lecture->getDisplayTerms(true);
				}
				if ($cond['set_syllabus_studies'])
				{
	                $parts['univis_studies'] = $lecture->getDisplayStudies();
				}
				if ($cond['set_syllabus_prerequisites'])
				{
	                $parts['univis_prerequisites'] = $lecture->getDisplayPrerequisites();
				}
				if ($cond['set_syllabus_summary'])
				{
	                $parts['univis_summary'] = $lecture->getDisplaySummary();
				}
				if ($cond['set_syllabus_literature'])
				{
	                $parts['univis_literature'] = $lecture->getDisplayLiterature();
				}
				if ($cond['set_syllabus_ects_info'])
				{
	                $parts['univis_ects_info'] = $lecture->getDisplayEctsInfo();
				}
				if ($cond['set_syllabus_additional_info'])
				{
	                $parts['univis_additional_info'] = $lecture->getDisplayAdditionalInfo();
				}
				if ($cond['set_syllabus_institution'])
				{
	                $parts['univis_institution'] = $lecture->getDisplayInstitution();
				}

				$tpl = new ilTemplate("tpl.univis_details.html", true, true, "Services/UnivIS");
				foreach ($parts as $head => $data)
				{
					if ($data)
					{
						$tpl->setCurrentBlock('part');
						$tpl->setVariable('HEAD', $this->lng->txt($head));
						$tpl->setVariable('DATA', $data);
						$tpl->parseCurrentBlock();
					}
				}

				$crs->setSyllabus($tpl->get());
      		}

			// set contact
			if ($cond['set_contact'] == 'first')
			{
	            $contact_name = '';
				$contact_email = '';
				$contact_phone = '';
				$consultations = array();

	            $list = $lecture->getLecturers();
				if (count($list))
				{
					$person = current($list);
					$contact_name = $person->getDisplay(false);

					$list = $person->getLocations();
					if (count($list))
					{
						$location = current($list);
						$contact_email = $location->getEmail();
						$contact_phone = $location->getPhone();
					}

					$list = $person->getOfficehours();
					if (is_array($list))
					{
						foreach ($list as $officehour)
						{
							$consultations[] = $officehour->getDisplay();
						}
					}
				}

				$crs->setContactName($contact_name);
				$crs->setContactEmail($contact_email);
				$crs->setContactPhone($contact_phone);
				$crs->setContactConsultation(implode(" \n", $consultations));
	        }
			elseif ($cond['set_contact'] == 'own')
			{
				$crs->setContactName($cond['contact_name']);
				$crs->setContactEmail($cond['contact_email']);
				$crs->setContactPhone($cond['contact_phone']);
				$crs->setContactConsultation($cond['contact_consultation']);
	        }

			// activation
			if ($cond['set_activation'])
			{
				$crs->setActivationType((bool) $cond['activation_type'] ? IL_CRS_ACTIVATION_LIMITED : IL_CRS_ACTIVATION_UNLIMITED);
				$crs->setOfflineStatus(!(bool) $cond['activation_online']);

				$crs->setActivationStart($this->values->getSessionValue('conditions_form','access_start', time()));
				$crs->setActivationEnd($this->values->getSessionValue('conditions_form','access_end', mktime(0,0,0,12,31,date("Y",time())+1)));
				$crs->setActivationVisibility((bool)$cond['activation_visibility']);
	        }

			// registration
			if ($lecture->hasMyCampusRegistration())
			{
                $crs->setSubscriptionType(IL_CRS_SUBSCRIPTION_MYCAMPUS);
	         	$crs->setSubscriptionLimitationType(IL_CRS_SUBSCRIPTION_MYCAMPUS);
 	        }
			elseif ($cond['set_registration'])
			{
				 if ($cond['subscription_limitation_type'] == IL_CRS_SUBSCRIPTION_UNIVIS)
				 {
				 	// get registration period from univis
		            $crs->setSubscriptionLimitationType(IL_CRS_SUBSCRIPTION_LIMITED);
				 	$start = $lecture->getRegStart()->get(IL_CAL_UNIX);
				 	$end = $lecture->getRegEnd()->get(IL_CAL_UNIX);
				 }
				 else
				 {
				 	// get registration period from the form
		            $crs->setSubscriptionLimitationType($cond['subscription_limitation_type']);
					$start = $this->values->getSessionValue('conditions_form','subscription_start', time());
					$end = $this->values->getSessionValue('conditions_form','subscription_end', mktime(0,0,0,12,31,date("Y",time())+1));
				 }	

				$crs->setSubscriptionStart($start);
				$crs->setSubscriptionEnd($end);
				$crs->setSubscriptionType($cond['subscription_type']);
	            $crs->setSubscriptionPassword($cond['subscription_password']);
				 
			}
			else
			{
				// no manual setting of registration
				// take subscription limitation from univis
				$start = $lecture->getRegStart()->get(IL_CAL_UNIX);
				$end = $lecture->getRegEnd()->get(IL_CAL_UNIX);
			 	$crs->setSubscriptionStart($start);
				$crs->setSubscriptionEnd($end);
	            $crs->setSubscriptionLimitationType(IL_CRS_SUBSCRIPTION_LIMITED);
				$crs->setSubscriptionType(IL_CRS_SUBSCRIPTION_DIRECT);
			}

			// membership limitation
			if ($cond['set_membership_limitation'] and !$lecture->hasMyCampusRegistration())
			{
	            $maxturnout = $lecture->getMaxturnout();
                $crs->enableSubscriptionMembershipLimitation($maxturnout > 0 ? 1 : 0);
				$crs->setSubscriptionMaxMembers((int) $maxturnout);

// fau: fairSub - waiting list settings
				$crs->setSubscriptionFair($crs->getSubscriptionStart() + $crs->getSubscriptionMinFairSeconds());
				
				if ($cond['waiting_list'] = 'univis')
				{
					$cond['waiting_list'] = $lecture->hasWaitingList() ? 'auto' : 'no_list';
				}

				switch($_POST['waiting_list'])
				{
					case 'auto':
						$crs->setSubscriptionAutoFill(true);
						$crs->enableWaitingList(true);
						$crs->setWaitingListAutoFill(true);
						break;

					case 'auto_manu':
						$crs->setSubscriptionAutoFill(true);
						$crs->enableWaitingList(true);
						$crs->setWaitingListAutoFill(false);
						break;

					case 'manu':
						$crs->setSubscriptionAutoFill(false);
						$crs->enableWaitingList(true);
						$crs->setWaitingListAutoFill(false);
						break;

					default:
						$crs->setSubscriptionAutoFill(true);
						$crs->enableWaitingList(false);
						$crs->setWaitingListAutoFill(false);
						break;
				}
			}
// fau.

			// update the course
			$crs->update();

			// fim: [evasys] add course for evaluation
			if ($cond['mark_for_evaluation'])
			{
				require_once ("Services/Evaluation/classes/class.ilEvaluationData.php");
				if (ilEvaluationData::_isEvaluationActivated($this->parent_ref_id)
				and ilEvaluationData::_isObjEvaluable($crs))
				{
					ilEvaluationData::_setObjMarkedForEvaluation($crs, true);	
				}
			}
			// fim.
						
			if (ilChangeEvent::_isActive())
			{
				ilChangeEvent::_recordWriteEvent($crs->getId(), $ilUser->getId(), 'update');
			}

		} // foreach

		// ignored lectures
		if ($ignored == 1)
		{
	        ilUtil::sendFailure($this->lng->txt('univis_message_lecture_ignored'), true);
		}
		elseif ($ignored > 0)
		{
	        ilUtil::sendFailure(sprintf($this->lng->txt('univis_message_lectures_ignored'), $ignored), true);
	    }

		// imported lectures
		if ($imported == 1)
		{
	        ilUtil::sendSuccess($this->lng->txt('univis_message_lecture_imported'), true);
		}
		elseif ($imported > 0)
		{
	        ilUtil::sendSuccess(sprintf($this->lng->txt('univis_message_lectures_imported'), $imported), true);
	    }

		// updated courses
		if ($updated == 1 and $ref_id == $this->parent_ref_id)
		{
	        ilUtil::sendSuccess($this->lng->txt('univis_message_this_course_updated'), true);
		}
		elseif ($updated == 1)
		{
	        ilUtil::sendSuccess($this->lng->txt('univis_message_course_updated'), true);
		}
		elseif ($updated > 0)
		{
	        ilUtil::sendSuccess(sprintf($this->lng->txt('univis_message_courses_updated'), $updated), true);
	    }

		return $this->executeCommand('returnToParent');
	}
}
?>
