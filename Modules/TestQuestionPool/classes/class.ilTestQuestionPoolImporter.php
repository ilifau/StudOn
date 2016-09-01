<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Export/classes/class.ilXmlImporter.php");

/**
 * Importer class for question pools
 *
 * @author Helmut Schottmüller <ilias@aurealis.de>
 * @version $Id$
 * @ingroup ModulesLearningModule
 */

class ilTestQuestionPoolImporter extends ilXmlImporter
{
	/**
	 * Import XML
	 *
	 * @param
	 * @return
	 */
	function importXmlRepresentation($a_entity, $a_id, $a_xml, $a_mapping)
	{
		include_once "./Modules/TestQuestionPool/classes/class.ilObjQuestionPool.php";
		ilObjQuestionPool::_setImportDirectory($this->getImportDirectory());

		// Container import => test object already created
		if($new_id = $a_mapping->getMapping('Services/Container','objs',$a_id))
		{
			$newObj = ilObjectFactory::getInstanceByObjId($new_id,false);
		}
// fau: taxExport - support an import of a single question pool
		else if ($new_id = $a_mapping->getMapping('Modules/TestQuestionPool','qpl', "new_id"))	 // this mapping is only set by ilObjQuestionPoolGUI
		{
			$newObj = ilObjectFactory::getInstanceByObjId($new_id,false);
		}
// fau.
		else	// case ii, non container
		{
			// Shouldn't happen
			$GLOBALS['ilLog']->write(__METHOD__.': Called in non container mode');
			return false;
		}

		list($xml_file,$qti_file) = $this->parseXmlFileNames();

		if(!@file_exists($xml_file))
		{
			$GLOBALS['ilLog']->write(__METHOD__.': Cannot find xml definition: '. $xml_file);
			return false;
		}
		if(!@file_exists($qti_file))
		{
			$GLOBALS['ilLog']->write(__METHOD__.': Cannot find xml definition: '. $qti_file);
			return false;
		}

		include_once "./Modules/TestQuestionPool/classes/class.ilObjQuestionPool.php";
		ilObjQuestionPool::_setImportDirectory($this->getImportDirectory());

		// FIXME: Copied from ilObjQuestionPoolGUI::importVerifiedFileObject
		// TODO: move all logic to ilObjQuestionPoolGUI::importVerifiedFile and call 
		// this method from ilObjQuestionPoolGUI and ilTestImporter 

		$GLOBALS['ilLog']->write(__METHOD__.': xml file: '. $xml_file . ", qti file:" . $qti_file);
		
		$newObj->setOnline(true);
		$newObj->saveToDb();
		
		// start parsing of QTI files
		include_once "./Services/QTI/classes/class.ilQTIParser.php";
		$qtiParser = new ilQTIParser($qti_file, IL_MO_PARSE_QTI, $newObj->getId(), null);
		$result = $qtiParser->startParsing();

		// import page data
		if (strlen($xml_file))
		{
			include_once ("./Modules/LearningModule/classes/class.ilContObjParser.php");
			$contParser = new ilContObjParser($newObj, $xml_file, basename($this->getImportDirectory()));
			$contParser->setQuestionMapping($qtiParser->getImportMapping());
			$contParser->startParsing();
		}

		$a_mapping->addMapping("Modules/TestQuestionPool", "qpl", $a_id, $newObj->getId());

// fau: taxExport - add mappings for imported taxonomy assignments
		foreach($qtiParser->getImportMapping() as $key => $map)
		{
			// see ilGlossaryImporter::importXmlRepresentation()
			// see ilTaxonomyDataSet::importRecord()

			// $key is of form 'il_0_qst_2130209'
			// $map['pool'] qives the new question_id of the new question pool
			// $map['test'] gives the question_id of the new test
			// see assNumericImport::fromXML() for an example (end of function)
			$parts = explode('_', $key);
			$old = end($parts);
			$new = $map['pool'];

			$a_mapping->addMapping("Services/Taxonomy", "tax_item",
				"qpl:quest:".$old, $new);

			// this is since 4.3 does not export these ids but 4.4 tax node assignment needs it
			$a_mapping->addMapping("Services/Taxonomy", "tax_item_obj_id",
				"qpl:quest:".$old, $newObj->getId());
		}
// fau.

		ilObjQuestionPool::_setImportDirectory(null);
	}

// fau: taxExport - final procesing of taxonomy usages
	function finalProcessing($a_mapping)
	{
		// see ilGlossaryImporter::finalProcessing()

		include_once("./Services/Taxonomy/classes/class.ilObjTaxonomy.php");
		$maps = $a_mapping->getMappingsOfEntity("Modules/TestQuestionPool", "qpl");
		foreach ($maps as $old => $new)
		{
			if ($old != "new_id" && (int) $old > 0)
			{
				// get all new taxonomys of this object
				$new_tax_ids = $a_mapping->getMapping("Services/Taxonomy", "tax_usage_of_obj", $old);
				if (!empty($new_tax_ids))
				{
					$tax_ids = explode(":", $new_tax_ids);
					foreach ($tax_ids as $tid)
					{
						ilObjTaxonomy::saveUsage($tid, $new);
					}
				}
			}
		}
	}
// fau.

	/**
	 * Create qti and xml file name
	 * @return array 
	 */
	protected function parseXmlFileNames()
	{
		$GLOBALS['ilLog']->write(__METHOD__.': '.$this->getImportDirectory());
		
		$basename = basename($this->getImportDirectory());

		$xml = $this->getImportDirectory().'/'.$basename.'.xml';
		$qti = $this->getImportDirectory().'/'.preg_replace('/qpl/', 'qti', $basename).'.xml';
		
		return array($xml,$qti);
	}
}

?>