<?php
// fau: requestLog - new class RequestLog.

class RequestLog
{
	private static $instance;

	var $log_dir = "data/logs";
	
	var $filename = "";

	var	$access_name = array(	1 => 'PHP_INI_USER',
		                    	2 => 'PHP_INI_PERDIR',
		                    	4 => 'PHP_INI_SYSTEM',
		                    	7 => 'PHP_INI_ALL');
	var $wrap = 80;

	var $fp = null;


	/**
	* A private constructor; prevents direct creation of object
	*/
	private function __construct()
	{
		list($usec, $sec) = explode(" ", microtime());
		$this->filename = date("d-m-Y_H-i-s", $sec) . "_" . substr($usec, 2) . ".html";
	}
	
	
	/**
	* singleton method
	*/
	public static function getInstance()
	{
	   if (!isset(self::$instance)) {
	       $c = __CLASS__;
	       self::$instance = new $c;
	   }

	   return self::$instance;
	}

	/**
	* write request data to the log file
	*/
	public function writeRequestLog()
	{
		$this->write($this->getNamedAssocDump($_GET, '$_GET'));
		$this->write($this->getNamedAssocDump($_POST, '$_POST'));
		$this->write($this->getNamedAssocDump($_COOKIE, '$_COOKIE'));
		$this->write($this->getNamedAssocDump($_SERVER, '$_SERVER'));
	}

	/**
	* write a SOAP request to the log file
	*/
	public function writeSoapLog()
	{
		$this->write($this->getNamedAssocDump($_GET, '$_GET'));
		$this->writeRawInput();
		$this->write($this->getNamedAssocDump($_SERVER, '$_SERVER'));
	}


	/**
	* write request data to the log file
	*/
	public function writeServerLog()
	{
		$this->write($this->getNamedAssocDump($_SERVER, '$_SERVER'));
		$this->write($this->getNamedIniDump('PHP_INI'));
	}
	
	/**
	* write session data to the log file
	*/
	public function writeSessionLog()
	{
		$this->write($this->getNamedAssocDump($_SESSION, '$_SESSION'));
	}


	/**
	* write  variable to the log file
	*/
	public function writeVarDump(&$a_var, $a_name = '')
	{
	    if ($a_name)
		{
	        $a_name = '<b>'.$a_name.': </b>';
		}
		$this->write('<pre>'.$a_name.$this->getVarDump($a_var).'</pre>');
	}


	/**
	* write a backtrace to the log file
	*/
	public function writeBacktrace()
	{
		$this->write('<pre>'.htmlspecialchars($this->getBacktrace()).'</pre>');
	}


	/**
	* write a line
	*/
	public function writeLine($a_line)
	{
		$this->write($a_line.'<br/>');
	}


	/**
	* write the raw input
	*/
	public function writeRawInput()
	{
	    $input = file_get_contents('php://input');
		$this->write('<pre><h1>Raw Input</h1>'.htmlspecialchars($input).'</pre><br/>');
 	}

 	/**
 	 * shows the contents of the logfile
 	 * @return unknown_type
 	 */
 	public function showLog()
 	{
 		readfile($this->log_dir. "/". $this->filename);
 	}
 	

	/**
	* Write a string to the log file
	* create the log file if it does not exist
	*/
	private function write($a_string)
	{
	    if (!isset($this->fp))
		{
	       	$this->fp = fopen($this->log_dir. "/". $this->filename, 'w');
			fwrite($this->fp, $this->getStyles());
	    }
		fwrite($this->fp, $a_string);
	}

	private function getStyles()
	{
		$ret = "<style>\n"
		    . "body, h1, th, td {font-family: monospace;} \n"
		    . "</style> \n";
		    
		return $ret;
	}
	
	private function getNamedIniDump($a_title)
	{
		$ret = '<h1>'. $a_title .'</h1>' . "\n";
		$ret .= '<table border="1" cellspacing="0" cellpadding="2">'. "\n";
		$ret .= '<th>Key</th><th>Global Value</th><th>Local Value</th><th>Access</th>' ."\n";

		$ini = ini_get_all();
		foreach ($ini as $key => $values)
		{
			$ret.= '<tr><td>'.$key.'</td>'. "\n";
			$ret .= '<td>'. wordwrap($values['global_value'],floor($this->wrap / 2),"\n", 1) .'&nbsp;</td>'. "\n";
			$ret .= '<td>'. wordwrap($values['local_value'],floor($this->wrap / 2) ,"\n", 1) .'&nbsp;</td>'. "\n";
			$ret .= '<td>'. $this->access_name[$values['access']] .'&nbsp;</td></tr>'. "\n";
		}
		$ret .= '</table>'."\n";

		return $ret;
	}
	
	
	private function getNamedAssocDump(&$a_var, $a_title)
	{
		$ret = '<h1>'. $a_title. '</h1>' . "\n";
		$ret .= $this->getAssocDump($a_var);
		return $ret;
	}
	
	
	private function getAssocDump(&$a_var, $a_depth = 0)
	{
		if ($a_depth > $this->max_depth)
		{
			return '';
		}
		elseif (count($a_var))
		{
			$ret = '<table border="1" cellspacing="0" cellpadding="2">'. "\n";
			$ret .= '<th>Key</th><th>Value</th>' ."\n";
			foreach ($a_var as $key => $value)
			{
				$ret.= '<tr><td>'.$key.'</td>'. "\n";
				
				if (is_array($value))
				{
					$ret .= '<td>'.$this->getAssocDump($value).'</td></tr>'. "\n";
				}
				else
				{
					$ret .= '<td>'. wordwrap($this->getVarDump($value), $this->wrap, "\n", 1) .'&nbsp;</td></tr>'. "\n";
				}
			}
			$ret .= '</table>'."\n";
			
			return $ret;
		}
	}
	
	
	private function getVarDump(&$a_var)
	{
		return print_r($a_var, true);
	}


	private function getBacktrace()
	{
		$i = 0;
		foreach (debug_backtrace() as $step)
		{
            if ($i > 0)
			{
				$backtrace .= '['.$i.'] '.$step['file'].' '.$step['line'].': '.$step['function']."()\n";
			}
			$i++;
		}
		$backtrace .= '['.$i.'] '.$_SERVER['REQUEST_URI'];
		return $backtrace;
	}
}

?>
