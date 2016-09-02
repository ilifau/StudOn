<?php
// fau: retryPage - GUI class to show the page.

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* Show a page to retry a request if the system is buisy
*
* @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
* @version $Id: $
*
* @ingroup ServicesInit
*/

class ilRetryGUI
{
	
	/**
	 * GET parameters
	 * @var array
	 */
	var $get = array();
	
	/**
	 * POST parameters
	 * @var array
	 */
	var $post = array();
	
	
	/**
	 * Short reason text
	 * @var string
	 */
	var $reason_text = "";
	
	/**
	 * default seconds to reload the page
	 * @var integer
	 */
	var $reload = 30;

	/**
	 * Show debugging info
	 * @var bool|mixed
	 */
	var $debug = DEVMODE;

	/**
	 * constructor
	 * 
	 * @param string reason for showing retry page
	 */
	public function __construct($a_reason = "")
	{
		global $ilClientIniFile;	
		
		switch ($a_reason)
		{
			case "anonymous_not_found":
				$this->reason_text = "ANONYMOUS user with the object_id ".ANONYMOUS_USER_ID." not found!";
				$this->reload = (int) $ilClientIniFile->readVariable("db","retry_seconds");
				break;
				
			case "max_connections_reached":
				$this->reason_text = "Maximum database connections reached.";
		  		$this->reload = (int) $ilClientIniFile->readVariable("db","retry_seconds");
		  		break;

			case "retry_forced":
				$this->reason_text = "Retry is forced";
				$this->reload = (int) $ilClientIniFile->readVariable("db","retry_seconds");
				break;


			case "":
				$this->reason_text = "Unknown reason.";
				$this->debug = true;
				break;

			default:
				$this->reason_text = $a_reason;
				break;
		}
	}
	
	/**
	 * Handle a request
	 * @return unknown_type
	 */
	public function handleRequest()
	{
		if (ilContext::hasHTML())
		{
			if (isset($_GET["cmdMode"]) && $_GET["cmdMode"] == "asynch")
			{
				header("HTTP/1.0 503 Service Unavailable");
				header("Retry-After: " . $this->reload);
				echo $this->reason_text;
				exit;
			}
			else
			{
				$this->showResponsePage();
			}
			exit;
		}
		elseif(ilContext::usesHTTP())
		{
			header("HTTP/1.0 503 Service Unavailable");
			header("Retry-After: " . $this->reload);
			echo $this->reason_text;
			exit;
		}
		else
		{
			echo $this->reason_text;
			exit;
		}	
	}
	
	/**
	 * show the retry page and exit
	 */
	public function showResponsePage()
	{
		global $ilClientIniFile;

		// URL to script
		$protocol = $_SERVER["HTTPS"] ? 'https://' : 'http://';
		$host = $_SERVER['HTTP_HOST'];
		$path = $_SERVER['PHP_SELF'];

		// GET parameters
		$query = "";
		$this->get = $_GET;
		$this->get['retry_nocache'] = (string) rand(0, 32000);
		if ($this->get['retry_forced'])
		{
			unset($this->get['retry_forced']);
		}
		elseif ($ilClientIniFile->readVariable("db","retry_forced"));
		{
			$this->get['retry_forced'] = 1;
		}
		foreach ($this->get as $name => $value)
		{
			$query = ilUtil::appendUrlParameterString($query,$name."=".$value, false);
		}

		// POST parameters
		$fields = "";
		if (!count($_POST))
		{
			$method = "get";
			foreach ($this->get as $name => $value)
			{
				$fields .= sprintf('<input type ="hidden" name="%s" value="%s" />'."\n",
					$this->prepareFormOutput($name),
					$this->prepareFormOutput($value));
			}
			$action = $this->prepareFormOutput($protocol.$host.$path);
		}
		else
		{
			$method = "post";

			foreach ($this->get as $name => $value)
			{
				$query = ilUtil::appendUrlParameterString($query,$name."=".$value, false);
			}
			$action = $this->prepareFormOutput($protocol.$host.$path.$query);

			$this->parsePostArray($_POST);
			foreach ($this->post as $name => $value)
			{
				$fields .= sprintf('<input type ="hidden" name="%s" value="%s" />'."\n",
					$this->prepareFormOutput($name),
					$this->prepareFormOutput($value));
			}
		}

		// content
		if($this->debug)
		{
			$debug_url = '<br /><br />'.$action;
			if (!empty($_POST))
			{
				$debug_post =  '<br />$_POST='.print_r($_POST, true);
			}
			$i = 1;
			foreach (array_reverse(debug_backtrace()) as $step)
			{
				$backtrace .= '['.$i.'] '.$step['file'].' '.$step['line'].': '.$step['function']."()\n";
				$i++;
			}
		}

		// show retry button if retry is forced
		if($this->get['retry_forced'])
		{
			$retry_button = '<a class="btn btn-primary" onclick="newTrial()">Retry</a>';
		}

		$content = file_get_contents('./Services/Init/templates/default/tpl.retry_content.html');
		$content = str_replace('{METHOD}',$method, $content);
		$content = str_replace('{ACTION}',$action, $content);
		$content = str_replace('{FIELDS}',$fields, $content);
		$content = str_replace('{REASON}',$this->reason_text, $content);
		$content = str_replace('{DEBUG_URL}', $debug_url, $content);
		$content = str_replace('{DEBUG_POST}', $debug_post, $content);
		$content = str_replace('{DEBUG_BACKTRACE}', $backtrace, $content);
		$content = str_replace('{RETRY_BUTTON}', $retry_button, $content);

		// javascript
		$script = file_get_contents('./Services/Init/templates/default/tpl.retry_script.html');
		$script = str_replace('{RELOAD}',$this->reload, $script);

		// page
		$page = file_get_contents('./Services/Init/templates/default/tpl.retry_page.html');
		$page = str_replace('{CONTENT}',$content, $page);
		$page = str_replace('{SCRIPT}',$script, $page);
		$page = str_replace('{SERVER}', gethostbyaddr($_SERVER['SERVER_ADDR']), $page);

		echo $page;
	}
	
	
	/**
	 * Make a flat array with names prepared for the new form
	 * 
	 * @param 	array 	nested values from the $_POST (sub-)array
	 * @param	string	prefix used in the parsing
	 */
	private function parsePostArray($post = array(), $prefix = "")
	{
		foreach ($post as $key => $value)
		{
			// get the correct new name
			if ($prefix)
			{
				$name = $prefix . '['. $key. ']';
			}
			else
			{
				$name = $key; 		
			}
			
			// add the value(s)
			if (is_array($value))
			{
				$this->parsePostArray($value, $name);
			}
			else
			{
				$this->post[$name] = $value;	
			}
		}
	}

	
	/**
	 * Prepare the form output
	 * 
	 * @param string	text to prepare
	 * @return string	prepared text
	 */
	private function prepareFormOutput($a_str)
	{
		if (ini_get("magic_quotes_gpc"))
		{
			$a_str = stripslashes($a_str);
		}
		
		$a_str = htmlspecialchars($a_str);
		// Added replacement of curly brackets to prevent
		// problems with PEAR templates, because {xyz} will
		// be removed as unused template variable
		$a_str = str_replace("{", "&#123;", $a_str);
		$a_str = str_replace("}", "&#125;", $a_str);
		// needed for LaTeX conversion \\ in LaTeX is a line break
		// but without this replacement, php changes \\ to \
		$a_str = str_replace("\\", "&#92;", $a_str);
		
		return ($a_str);
	}
}