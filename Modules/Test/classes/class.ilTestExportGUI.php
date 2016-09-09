<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Export/classes/class.ilExportGUI.php';

/**
 * Export User Interface Class
 * 
 * @author       Michael Jansen <mjansen@databay.de>
 * @author       Maximilian Becker <mbecker@databay.de>
 *               
 * @version      $Id$
 *               
 * @ingroup      ModulesTest
 *               
 * @ilCtrl_Calls ilTestExportGUI:
 */
class ilTestExportGUI extends ilExportGUI
{
	public function __construct($a_parent_gui, $a_main_obj = null)
	{
		global $ilPluginAdmin;

		parent::__construct($a_parent_gui, $a_main_obj);

		$this->addFormat('xml', $a_parent_gui->lng->txt('ass_create_export_file'), $this, 'createTestExport');
		$this->addFormat('xmlres', $a_parent_gui->lng->txt('ass_create_export_file_with_results'), $this, 'createTestExportWithResults');
		$this->addFormat('csv', $a_parent_gui->lng->txt('ass_create_export_test_results'), $this, 'createTestResultsExport');
// fau: manualTestArchiving - allow creation of archive to admins
		global $rbacsystem;
		if($rbacsystem->checkAccess("visible", SYSTEM_FOLDER_ID))
		{
			$this->addFormat( 'arc',
							  $a_parent_gui->lng->txt( 'ass_create_export_test_archive' ),
							  $this,
							  'createTestArchiveExport'
			);
		}
// fau.

        // fim: [exam] Button to export test results for my campus
        global $ilCust;
        if ($ilCust->getSetting('tst_export_mycampus'))
        {
            $this->addFormat('prf', $a_parent_gui->lng->txt('ass_create_export_mycampus'), $this, 'createTestResultsMyCampus');
        }
        // fim.


		$pl_names = $ilPluginAdmin->getActivePluginsForSlot(IL_COMP_MODULE, 'Test', 'texp');
		foreach($pl_names as $pl)
		{
			/**
			 * @var $plugin ilTestExportPlugin
			 */
			$plugin = ilPluginAdmin::getPluginObject(IL_COMP_MODULE, 'Test', 'texp', $pl);
			$plugin->setTest($this->obj);
			$this->addFormat(
				$plugin->getFormat(),
				$plugin->getFormatLabel(),
				$plugin,
				'export'
			);
		}
	}

	/**
	 * @return ilTestExportTableGUI
	 */
	protected function buildExportTableGUI()
	{
		require_once 'Modules/Test/classes/tables/class.ilTestExportTableGUI.php';
		$table = new ilTestExportTableGUI($this, 'listExportFiles', $this->obj);
		return $table;
	}

	/**
	 * Create test export file
	 */
	public function createTestExport()
	{
		/**
		 * @var $lng ilLanguage
		 * @var $ilCtrl ilCtrl
		 */
		global $lng, $ilCtrl;

		require_once 'Modules/Test/classes/class.ilTestExport.php';
		$test_exp = new ilTestExport($this->obj, 'xml');
		$test_exp->buildExportFile();
		ilUtil::sendSuccess($lng->txt('exp_file_created'), true);
		$ilCtrl->redirectByClass('iltestexportgui');
	}

	/**
	 * Create test export file
	 */
	public function createTestExportWithResults()
	{
		/**
		 * @var $lng ilLanguage
		 * @var $ilCtrl ilCtrl
		 */
		global $lng, $ilCtrl;

		require_once 'Modules/Test/classes/class.ilTestExport.php';
		$test_exp = new ilTestExport($this->obj, 'xml');
		$test_exp->setResultExportingEnabledForTestExport(true);
		$test_exp->buildExportFile();
		ilUtil::sendSuccess($lng->txt('exp_file_created'), true);
		$ilCtrl->redirectByClass('iltestexportgui');
	}

	/**
	 * Create results export file
	 */
	public function createTestResultsExport()
	{
		/**
		 * @var $lng ilLanguage
		 * @var $ilCtrl ilCtrl
		 */
		global $lng, $ilCtrl;

		require_once 'Modules/Test/classes/class.ilTestExport.php';
		$test_exp = new ilTestExport($this->obj, 'results');
		$test_exp->buildExportFile();
		ilUtil::sendSuccess($lng->txt('exp_file_created'), true);
		$ilCtrl->redirectByClass('iltestexportgui');
	}

    // fim: [campus] create test results for my campus
    public function createTestResultsMyCampus()
    {
        global $ilCtrl;
        $ilCtrl->redirectByClass("iltestmycampusgui");
    }
    // fim.

// fau: manualTestArchiving - new implementation
	function createTestArchiveExport()
	{
		global $ilAccess, $ilCtrl, $ilDB, $lng, $rbacsystem,  $ilObjDataCache;

		if($rbacsystem->checkAccess("visible", SYSTEM_FOLDER_ID))
		{
			include_once("./Modules/Test/classes/class.ilTestArchiver.php");
			include_once("./Modules/Test/classes/class.ilTestPDFGenerator.php");

			$test_id = $this->obj->getId();
			$archive_exp = new ilTestArchiver($test_id);

			// create PDF for the best solution
			$best_solution_html = $this->generateBestSolution();
			$best_solution_pdf =  ilUtil::ilTempnam().'.pdf';
			ilTestPDFGenerator::generatePDF($best_solution_html, ilTestPDFGenerator::PDF_OUTPUT_FILE, $best_solution_pdf);
			$archive_exp->handInTestBestSolution($best_solution_html, $best_solution_pdf);
			unlink($best_solution_pdf);

			require_once 'Modules/Test/classes/class.ilTestResultHeaderLabelBuilder.php';
			$testResultHeaderLabelBuilder = new ilTestResultHeaderLabelBuilder($lng, $ilObjDataCache);
			$testResultHeaderLabelBuilder->setTestObjId($this->obj->getId());
			$testResultHeaderLabelBuilder->setTestRefId($this->obj->getRefId());

			// create PDFs for all participants (copied from ilTestScoring and adapted)
			require_once './Modules/Test/classes/class.ilTestEvaluationGUI.php';
			$test_evaluation_gui = new ilTestEvaluationGUI($this->obj);
			$participants = $this->obj->getCompleteEvaluationData(false)->getParticipants();
			if (is_array($participants))
			{
				/** @var  ilTestEvaluationUserData $userdata */
				foreach ($participants as $active_id => $userdata)
				{
					if (is_object($userdata) && is_array($userdata->getPasses()))
					{
						$testResultHeaderLabelBuilder->setUserId($userdata->getUserID());

						$passes = $userdata->getPasses();
						foreach ($passes as $pass => $passdata)
						{
							if (is_object( $passdata ))
							{
								$result_array = $this->obj->getTestResult($active_id, $pass);

								$user_solution_html = $test_evaluation_gui->getPassListOfAnswers($result_array, $active_id, $pass, true, false, false, true, false, null, $testResultHeaderLabelBuilder);
								$user_solution_pdf = ilUtil::ilTempnam().'.pdf';
								ilTestPDFGenerator::generatePDF($user_solution_html, ilTestPDFGenerator::PDF_OUTPUT_FILE, $user_solution_pdf);

								$localname = $userdata->getName().', '.$userdata->getLogin().', Pass'.$pass.'.pdf';
								$archive_exp->handInTestUserSolution($localname, $user_solution_pdf);
								unlink($user_solution_pdf);
							}
						}
					}
				}
			}

			// pack the archive
			if ($archive_exp->compressTestArchive($this->obj->getTitle()))
			{
				ilUtil::sendSuccess($lng->txt('exp_file_created'), true);
			}
			else
			{
				ilUtil::sendFailure($lng->txt("cannot_export_archive"), TRUE);
			}
			$archive_exp->deleteTestArchive();
		}
		else
		{
			ilUtil::sendFailure($lng->txt("cannot_export_archive"), TRUE);
		}

		$ilCtrl->redirectByClass('iltestexportgui');
	}
// fau.

// fau: manualTestArchiving - new function to generate the best solution
	protected function generateBestSolution()
	{
		global $lng;

		$template = new ilTemplate("tpl.il_as_tst_print_test_confirm.html", true, true, "Modules/Test");

		$max_points= 0;
		$counter = 0;
		foreach ($this->obj->questions as $question)
		{
			/** @var AssQuestionGUI $question_gui */
			$question_gui = $this->obj->createQuestionGUI("", $question);
			$max_points += $question_gui->object->getMaximumPoints();
			$counter ++;

			$template->setCurrentBlock("question");
			$template->setVariable("STYLE_PRINT_PAGEBREAKS", "page-break-before:always;");
			$template->setVariable("COUNTER_QUESTION", $counter.".");
			$template->setVariable("TXT_QUESTION_ID", $lng->txt('question_id_short'));
			$template->setVariable("QUESTION_ID", $question_gui->object->getId());
			$template->setVariable("QUESTION_TITLE", ilUtil::prepareFormOutput($question_gui->object->getTitle()));
			$template->setVariable("QUESTION_POINTS", $question_gui->object->getMaximumPoints() . " "
					. $lng->txt($question_gui->object->getMaximumPoints() == 1 ? "point": 'points'));

			$result_output = $question_gui->getSolutionOutput(0, null, true, true, false, false, true, false);
			$template->setVariable("SOLUTION_OUTPUT", empty($result_output) ? $question_gui->getPreview(FALSE) : $result_output);
			$template->parseCurrentBlock("question");
		}

		$print_date = mktime(date("H"), date("i"), date("s"), date("m")  , date("d"), date("Y"));

		ilDatePresentation::setUseRelativeDates(false);

		$template->touchBlock('print');
		$template->setVariable("TITLE", $this->obj->getTitle());
		$template->setVariable("PRINT_TEST", $lng->txt("tst_print"));
		$template->setVariable("TXT_PRINT_DATE", $lng->txt("date"));
		$template->setVariable("VALUE_PRINT_DATE", ilDatePresentation::formatDate(new ilDate(time(),IL_CAL_UNIX)));
		$template->setVariable("TXT_MAXIMUM_POINTS", $lng->txt("tst_maximum_points"));
		$template->setVariable("VALUE_MAXIMUM_POINTS",$max_points);

		return $template->get();
	}
// fau.

	public function listExportFiles()
	{
		global $tpl, $ilToolbar, $ilCtrl, $lng;

		$ilToolbar->setFormAction($ilCtrl->getFormAction($this));

		if(count($this->getFormats()) > 1)
		{
			foreach($this->getFormats() as $f)
			{
				$options[$f["key"]] = $f["txt"];
			}
			include_once 'Services/Form/classes/class.ilSelectInputGUI.php';
			$si = new ilSelectInputGUI($lng->txt("type"), "format");
			$si->setOptions($options);
			$ilToolbar->addInputItem($si, true);
			$ilToolbar->addFormButton($lng->txt("exp_create_file"), "createExportFile");
		}
		else
		{
			$format = $this->getFormats();
			$format = $format[0];
			$ilToolbar->addFormButton($lng->txt("exp_create_file") . " (" . $format["txt"] . ")", "create_" . $format["key"]);
		}

		require_once 'class.ilTestArchiver.php';
		$archiver = new ilTestArchiver($this->getParentGUI()->object->getId());
		$archive_dir = $archiver->getZipExportDirectory();
		$archive_files = array();
		if( file_exists($archive_dir) && is_dir($archive_dir) )
		{
			$archive_files = scandir($archive_dir);
		}
		
		$export_dir   = $this->obj->getExportDirectory();
		$export_files = $this->obj->getExportFiles($export_dir);
		$data         = array();
		if(count($export_files) > 0)
		{
			foreach($export_files as $exp_file)
			{
				$file_arr = explode("__", $exp_file);
				array_push($data, array(
					'file'      => $exp_file,
					'size'      => filesize($export_dir . "/" . $exp_file),
                    // fim: [campus] support export files with other naming scheme
					'timestamp' => filemtime($export_dir . "/" . $exp_file)
                    // fim.
				));
			}
		}

		if(count($archive_files) > 0)
		{
			foreach($archive_files as $exp_file)
			{
				if ($exp_file == '.' || $exp_file == '..')
				{
					continue;
				}
// fau: manualTestArchiving - support other naming_scheme
				array_push($data, array(

									'file' => $exp_file,
									'size' => filesize($archive_dir."/".$exp_file),
									'timestamp' => filemtime($archive_dir . "/" . $exp_file)
								));
// fau.
			}
		}

		$table = $this->buildExportTableGUI();
		$table->setSelectAllCheckbox("file");
		foreach($this->getCustomColumns() as $c)
		{
			$table->addCustomColumn($c["txt"], $c["obj"], $c["func"]);
		}
		
		foreach($this->getCustomMultiCommands() as $c)
		{
			$table->addCustomMultiCommand($c["txt"], "multi_".$c["func"]);
		}

		$table->setData($data);
		$tpl->setContent($table->getHTML());
	}

	public function download()
	{
		/**
		 * @var $lng ilLanguage
		 * @var $ilCtrl ilCtrl
		 */
		global $lng, $ilCtrl;

		if(isset($_GET['file']) && $_GET['file'])
		{
			$_POST['file'] = array($_GET['file']);
		}

		if(!isset($_POST['file']))
		{
			ilUtil::sendInfo($lng->txt('no_checkbox'), true);
			$ilCtrl->redirect($this, 'listExportFiles');
		}

		if(count($_POST['file']) > 1)
		{
			ilUtil::sendInfo($lng->txt('select_max_one_item'), true);
			$ilCtrl->redirect($this, 'listExportFiles');
		}

		require_once 'class.ilTestArchiver.php';
		$archiver = new ilTestArchiver($this->getParentGUI()->object->getId());

		$filename = basename($_POST["file"][0]);
		$exportFile = $this->obj->getExportDirectory().'/'.$filename;
		$archiveFile = $archiver->getZipExportDirectory().'/'.$filename;

		if( file_exists($exportFile) )
		{
			ilUtil::deliverFile($exportFile, $filename);
		}

		if( file_exists($archiveFile) )
		{
			ilUtil::deliverFile($archiveFile, $filename);
		}

		$ilCtrl->redirect($this, 'listExportFiles');
	}

	/**
	 * Delete files
	 */
	public function delete()
	{
		/**
		 * @var $lng ilLanguage
		 * @var $ilCtrl ilCtrl
		 */
		global $lng, $ilCtrl;

		require_once 'class.ilTestArchiver.php';
		$archiver = new ilTestArchiver($this->getParentGUI()->object->getId());
		$archiveDir = $archiver->getZipExportDirectory();
		
		$export_dir = $this->obj->getExportDirectory();
		foreach($_POST['file'] as $file)
		{
			$file = basename($file);
			$dir = substr($file, 0, strlen($file) - 4);

			if( !strlen($file) || !strlen($dir) )
			{
				continue;
			}
			
			$exp_file = $export_dir.'/'.$file;
			$arc_file = $archiveDir.'/'.$file;
			$exp_dir = $export_dir.'/'.$dir;
			if(@is_file($exp_file))
			{
				unlink($exp_file);
			}
			if(@is_file($arc_file))
			{
				unlink($arc_file);
			}
			if(@is_dir($exp_dir))
			{
				ilUtil::delDir($exp_dir);
			}
		}
		ilUtil::sendSuccess($lng->txt('msg_deleted_export_files'), true);
		$ilCtrl->redirect($this, 'listExportFiles');
	}
}