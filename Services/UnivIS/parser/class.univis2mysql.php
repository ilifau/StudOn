<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2008 Starbugs (univis2typo3@googlegroups.com)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

require_once('./Services/UnivIS/parser/inc.univisdebug.php');
require_once('./Services/UnivIS/parser/parsers/class.univisparser.php');
require_once('./Services/UnivIS/parser/modules/generic_module.php');
require_once('./Services/UnivIS/parser/modules/departments_module.php');
require_once('./Services/UnivIS/parser/modules/lectures_module.php');
require_once('./Services/UnivIS/parser/modules/persons_module.php');

/**
 * fim: [univis] new class for writing univis data to mysql tables
 *
 * Tests connection to Univis-Server
 * Downloads content from UnivIS via PRG interface in XML
 * Validates XML files
 * finally parse the content and stores
 * it into a mysql database
 *
 * Taken from the univis2typo3 project and adapted for ILIAS
 *
 * @author	Starbugs <univis2typo3@googlegroups.com>
 * @modifier Fred neumann <fred.neumann@fim.uni-erlangen.de>
 */
class univis2mysql
{
	/**
	* defined error codes
	*/
	const ERROR_NONE = 0;
	const ERROR_CONNECT = 1;
	const ERROR_TRANSFER = 2;
	const ERROR_VALIDATE = 3;

	/**
	* errorcode from the last import
	*/
	var $error = false;

    /**
	* list of files to be processed
	*
	* (int) => array (	'module' 	=> (string),
	*		  			'url'		=> (string),
	*					'filename'	=> (string),
	*                   'semester'  => (string)
	*/
    var $files = array();


	/**
	* validate an xml structure
	*
	* @param    string  	xml structure
	* @return   boolean     valid (true/false)
	*/
	function validateXML($xmlToValidate)
	{
		$xmlParser= xml_parser_create();
		if(!xml_parse($xmlParser, $xmlToValidate, true))
		{
			U2T3_DEBUG("ERROR occurred\n");
			if(xml_get_error_code($xmlParser))
			{
				U2T3_ERROR(xml_error_string(xml_get_error_code($xmlParser))."\n");
			}
			return false;
		}
		return true;
	}

	/**
	* check if univis server can be connected
	*
	* @param    array  		configuration
	* @return   boolean     server can be connected (true/false)
	*/
	function univisOnlineTest($conf)
	{
		ini_set('max_execution_time', 300);
		U2T3_DEBUG("> Testing if UnivIS server is reachable.\n");
		U2T3_DEBUG("  Try to open socket port 80 of the UnivIS server: ".$conf['univis']['server']." \n");
		$fp = @fsockopen($conf['univis']['server'], $conf['univis']['port'], $errno, $errstring, 5);
		if(!$fp)
		{
			U2T3_ERROR("  Could not connect to UnivIS server.\n");
			return false;
		}
		else
		{
			fclose($fp);
			switch ($errno) {
				case 0:
					U2T3_DEBUG("< UnivIS server reachable.\n");
					return true;
					break;
				case 110:
					U2T3_ERROR("  UnivIS connection timed out.\n");
					return false;
					break;
				case 111:
					U2T3_ERROR("  Server seems to be offline.\n");
					return false;
					break;
				default:
					U2T3_ERROR("  Unknown error: ($errno: $errstring).\n");
					return false;
			}
		}
	}


	/**
	* update the univis tables according to configuration
 	* an error code can be fetched with getError() afterwards
	*
	* @param    array  		configuration
	* @return   boolean     update successful (true/false)
	*/
	function updateFromUnivis($conf)
	{
	    // Cleanup error from last import
	    $this->error = self::ERROR_NONE;
	
	    // Cleanup list of import files
		$this->files = array();

		// Online test
		U2T3_DEBUG("Test if UnivIS is online");
		if (false === $this->univisOnlineTest($conf))
		{
	        $this->error = self::ERROR_CONNECT;
			return false;
		}

		// get temporary directory for downloaded files
		U2T3_DEBUG("Create tempory folder");
		$tempdir = $conf['univis']['tempdir'];
        if (!is_dir($tempdir))
        {
             ilUtil::createDirectory($tempdir);
        }

		// get the list of files
		U2T3_DEBUG("Create list of files to be downloaded");
		foreach ($conf['univis']['modules'] as $module => $import)
		{
			if (!$import)
			{
				continue;
			}

			// get the controller of the univis PRG request
			$moduleClass = ucfirst($module).'Module';
			if (!class_exists($moduleClass))
			{
				$moduleClass = 'GenericModule';
			}

			// define starting semester for LecturesModule
			if ($moduleClass == 'LecturesModule')
			{
				$moduleController = new LecturesModule($module,
					$conf['univis']['first_semester'],
					$conf['univis']['last_semester']);
	        }
			else
			{
				$moduleController = new $moduleClass($module);
			}

			// loop over URLs defined by the module controller
			do
			{
				// get the url from the module controller
				$url = $moduleController->getUrl($conf['univis']);

				// use the GET parameters for the filename
				$filename = $tempdir .'/'. str_replace($conf['univis']['prg_url'],'', $url);

				// optionally get the semester
                $semester = ($moduleClass == 'LecturesModule' ? $moduleController->getSemesterString() : '');

				// add the definition to the list of files
				$this->files[] = array (
											'module' => $module,
											'url' => $url,
											'filename' => $filename,
											'semester' => $semester
										);

			}
			while($moduleController->hasMoreUrls());
		}

		if (!count($this->files))
		{
			U2T3_DEBUG("No files found!");
	        $this->error = self::ERROR_TRANSFER;
	        return false;
	    }

		// load and save all XML files
		U2T3_DEBUG("Downloading UnivIS XML files.\n");
		foreach ($this->files as $index => $file)
		{
	        // apply  PRG request to get the XML data
			$url = $file['url'];
			$module = $file['module'];
			$filename = $file['filename'];

			U2T3_DEBUG( '> Fetching '.ucfirst($module)." XML data from\n    {$url}\n");
			$rawxml = file_get_contents($url);
			if ($rawxml === false)
			{
				$this->cleanupFiles();
	            $this->error = self::ERROR_TRANSFER;
				return false;
			}

			// replace line endings
			$xml = str_replace(array('&#x0A;', '&#x0D;'), array("\n", "\r"), $rawxml);
			unset($rawxml);

			// save the xml code in file
			file_put_contents($filename, $xml);
			// fim: [univis] optionally save xml file in logs folder
			//copy($filename, ILIAS_ABSOLUTE_PATH ."/data/logs/".basename($filename));
			// fim.
		}


		// Validate XML Files
		U2T3_DEBUG("Validate XML Files\n");
		foreach ($this->files as $index => $file)
		{
			$url = $file['url'];
			$module = $file['module'];
			$filename = $file['filename'];

			U2T3_DEBUG("Reading file: ".$filename);
			$rawxml = file_get_contents($filename);
			if ($rawxml === false)
			{
				$this->cleanupFiles();
	            $this->error = self::ERROR_TRANSFER;
				return false;
			}

			// Validate XML
			U2T3_DEBUG('> Validating XML: '.ucfirst($module)."\n");
			$rv = $this->validateXML($rawxml);
			unset($rawxml);

			if (false === $rv)
			{
	            // not valid: delete file and take it from file list
				U2T3_ERROR('> Error Validating XML: '.$filename."\n");
				//unlink($filename);
				//unset($files[$index]);
				$this->cleanupFiles();
				$this->error = self::ERROR_VALIDATE;
				return false;
			}
			else
			{
				U2T3_DEBUG("< XML Validated:".$filename."\n");
			}
		}

		// Parse the remaining XML Files
		U2T3_DEBUG("Parsing XML files\n");
		foreach ($this->files as $index => $file)
		{
			$url = $file['url'];
			$module = $file['module'];
			$filename = $file['filename'];

			// this global variable is needed in the lecture models
			$GLOBALS['semester'] = $file['semester'];

			U2T3_DEBUG("Reading file: ".$filename );
			$rawxml = file_get_contents($filename);

			// Load parser
			U2T3_DEBUG('> Parsing of '.ucfirst($module)."\n");
			$parserClass = 'univisparser';
			$parser = new $parserClass($module);
			$parser->department_id = $conf['univis']['department'];
			$parser->parse($rawxml);
			U2T3_DEBUG('< Parsing of '.ucfirst($module)." done.\n");
		}

		// deleta all XML files
		$this->cleanupFiles();

		// all successful
	    $this->error = self::ERROR_NONE;
		return true;
	}


    /**
	* cleanup of downloaded files
	*/
	function cleanupFiles()
	{
		U2T3_DEBUG("Deleting tempory folder");
		foreach ($this->files as $index => $file)
		{
	        unlink( $file['filename']);
	    }
	}


   	/**
	* get the error code from the last Update
	*
	* @return   integer     error code (0 = success)
	*/
	function getError()
	{
	    return $this->error;
	}
}
?>
