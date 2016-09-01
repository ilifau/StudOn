<?php
/**
 * fim: [cust] patch utilities.
 */
class ilPatchUtils
{
	public function __construct()
	{
		// set error reporting
		error_reporting (E_ALL ^ E_NOTICE);
		ini_set("display_errors","on");


		include_once "Services/Context/classes/class.ilContext.php";
		ilContext::init(ilContext::CONTEXT_CRON);

		include_once 'Services/Authentication/classes/class.ilAuthFactory.php';
		ilAuthFactory::setContext(ilAuthFactory::CONTEXT_CRON);

		$_COOKIE["ilClientId"] = $_SERVER['argv'][3];
		$_POST['username'] = $_SERVER['argv'][1];
		$_POST['password'] = $_SERVER['argv'][2];

		if($_SERVER['argc'] < 3)
		{
			die("Usage: apply_patches.php username password client\n");
		}

		require_once("Services/Init/classes/class.ilInitialisation.php");
		ilInitialisation::initILIAS();
	}


	/**
	 * Apply a patch given by its function name
	 *
	 * @param	string 	patch identifier (class.method)
	 * @param	mixed	parameters (single oder array)
	 */
	public function applyPatch($a_patch, $a_params = null)
	{
		$start = time();
		echo "Apply " . $a_patch . " ... \n";

		$class = substr($a_patch, 0, strpos($a_patch, '.'));
		$method = substr($a_patch, strpos($a_patch, '.') + 1);

		// get the patch class
		require_once ("./Customizing/patches/class.".$class.".php");
		$object = new $class;

		// call the patch method
		$error = $object->$method($a_params);

		// output the result and remember success
		if ($error != "")
		{
			echo $error . " Failed.\n";
		}
		else
		{
			echo "Done.\n";
		}

		echo "Time (s): " .(time() - $start) . "\n\n";
	}
} 