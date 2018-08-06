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
require_once('./Services/UnivIS/parser/models/univis_model.php');
require_once('./Services/UnivIS/parser/models/lecture_model.php');
require_once('./Services/UnivIS/parser/models/person_model.php');
require_once('./Services/UnivIS/parser/models/research_model.php');
require_once('./Services/UnivIS/parser/models/publication_model.php');
require_once('./Services/UnivIS/parser/models/room_model.php');
require_once('./Services/UnivIS/parser/models/thesis_model.php');
require_once('./Services/UnivIS/parser/models/job_model.php');
require_once('./Services/UnivIS/parser/models/org_model.php');

/**
 * fim: [univis] new class for parsing univis data
 *
 * Taken from the univis2typo3 project and adapted for ILIAS
 *
 * @author	Starbugs <univis2typo3@googlegroups.com>
 * @modifier Fred neumann <fred.neumann@fim.uni-erlangen.de>
 */
class univisparser
{
	var $depth = 0;

    var $department_id;
    var $xmlParser;
    var $tagName;
    var $modelClass;
    var $childParser;
    var $modelObj;
    var $attrName;
    var $parent;
    var $parentKey;


    function __Construct($name = 'invalid')
    {
		U2T3_DEBUG_PARSER($this->depth,"Call Parser constructor.\n");
		$this->tagName = $name.'--';
		$this->parent = null;
		$this->parentKey = '';
    }

    function setParent($parent)
    {
		U2T3_DEBUG_PARSER($this->depth,"Set parent and parent xmlParser.\n");
		$this->parent =& $parent;
		$this->xmlParser =& $parent->xmlParser;
    }
    
    function setParentKey($parentKey)
    {
		U2T3_DEBUG_PARSER($this->depth,"Set parentKey to $parentKey \n");
		$this->parentKey = $parentKey;
    }

    function unsetChild()
    {
		U2T3_DEBUG_PARSER($this->depth,"Unset child and childParser.\n");
		unset($this->childParser);
		$this->childParser = null;
    }
    
    function startElement($parser, $name, $attrs) 
    {
		$name = strtolower($name);
		U2T3_DEBUG_PARSER($this->depth,"Startelement: $name\n");
		$this->depth++;

		$modelClassForTag = ucfirst($name).'Model';
		if (class_exists($modelClassForTag))
		{
		    // we reached a tag for which a model exists
		    // create that model
		    $modelObjForTag = new $modelClassForTag();
		    $modelObjForTag->department_id = $this->department_id;
		    U2T3_DEBUG_PARSER($this->depth,"A model for this tag exists. Generate new object. ParentModelClass for this object is {$modelObjForTag->parentModelClass}.\n");
		    // check whether it fits into the hierarchy
		    // FIXME: parentModelClass is no attribute of $this!
		    $modelFits = $modelObjForTag->fitsIntoDOM($this->modelClass, @$this->parentModelClass);
		  /*  // populate the model with the tag's attributes
		    foreach($attrs as $key => $value)
		    {
			U2T3_DEBUG_PARSER($this->depth,"Set tag attribute: $key = $value \n");
			$this->modelObj->setAttribute($key, $value);
		    }
	*/
		    if (!$modelFits)
		    {
				U2T3_DEBUG_PARSER($this->depth,"Error: Model '{$modelClassForTag}' fits not, interpreting tag-name '{$name}' as a property of '{$this->modelClass}'\n");
		    }
		    else
		    {
				if (!$this->modelObj)
				{
				    // use this parser
				    // Question: can this happen for non top-level tags?
				    // Answer: Yes, this can happen for tags with a model and no parentModel
				    // This should be a top-level tag, but not necessarily
				    U2T3_DEBUG_PARSER($this->depth,"No modelObj, set this->tagName = $name and modelClass = $modelClassForTag and set modelObj!\n");
				    $this->tagName = $name;
				    $this->modelClass = $modelClassForTag;
				    $this->modelObj =& $modelObjForTag;
				    // populate the model with the tag's attributes
				    foreach($attrs as $key => $value)
				    {
						U2T3_DEBUG_PARSER($this->depth,"Set tag attribute (a): $key = $value \n");
						$this->modelObj->setAttribute($key, $value);
				    }
				}
				else
				{
				    // populate the model with the tag's attributes
				    foreach($attrs as $key => $value)
				    {
						U2T3_DEBUG_PARSER($this->depth,"Set tag attribute (b): $key = $value \n");
						$modelObjForTag->setAttribute($key, $value);
				    }
				    // create a child parser
				    U2T3_DEBUG_PARSER($this->depth,"Generating child parser for {$modelClassForTag}\n");
				    $this->childParser = new UnivisParser();
				    $this->childParser->department_id=$this->department_id;
				    $this->childParser->setParent($this);
				    U2T3_DEBUG_PARSER($this->depth,"Set childParser->modelClass = $modelClassForTag and childParser->tagName = $name\n ");
				    $this->childParser->modelClass = $modelClassForTag;
				    $this->childParser->modelObj =& $modelObjForTag;
				    $this->childParser->tagName = $name;
				    //$this->depth[$parser]++;
				    U2T3_DEBUG_PARSER($this->depth,"Set xml element and character_data handler\n");
				    xml_set_element_handler($this->xmlParser, array($this->childParser, "startElement"), array($this->childParser, "endElement"));
				    xml_set_character_data_handler($this->xmlParser, array($this->childParser, "characterData"));
				}
			}
		}

		if ($this->modelObj)
		{
		    U2T3_DEBUG_PARSER($this->depth,"'{$this->tagName}' has a modelObj.\n");
		    if ('univisref' == $name)
		    {
			if ($this->attrName)
			{
			    U2T3_DEBUG_PARSER($this->depth,"Found univisref tag: setAttribute {$this->attrName} = {$attrs['KEY']}\n");
			    $this->modelObj->setAttribute($this->attrName, $attrs['KEY']);
			}
			else
			{
			    $_attrName = substr($this->modelClass, 0, strlen($this->modelClass) - 5);
			    $_attrName = strtolower($_attrName);
			    U2T3_DEBUG_PARSER($this->depth,"Found univisref tag: ATTRNAME NOT SET. modelClass={$this->modelClass}\n");
			    U2T3_DEBUG_PARSER($this->depth,"Found univisref tag: setAttribute {$_attrName} = {$attrs['KEY']}\n");
			    $this->modelObj->setAttribute($_attrName, $attrs['KEY']);
			}
		    }
		    if ($this->modelObj->parentModelKey != '')
		    {
			U2T3_DEBUG_PARSER($this->depth,"parentModelKey is not NULL: set childModelKeyAttribute {$this->modelObj->childModelKey} from parentKey {$this->modelObj->parentModelKey} with value:{$this->parent->modelObj->getAttribute($this->modelObj->parentModelKey)}\n");
			$this->modelObj->setAttribute($this->modelObj->childModelKey, $this->parent->modelObj->getAttribute($this->modelObj->parentModelKey), false);
			$this->modelObj->parentModelKey = '';
		    }
		    $this->attrName = $name;
		}

    }
	
    function endElement($parser, $name) 
    {
		$this->depth--;
		$name = strtolower($name);
		U2T3_DEBUG_PARSER($this->depth,"Endelement: $name\n");
		if ($name == $this->tagName && $this->modelObj)
		{
		    // debug
		    U2T3_DEBUG_PARSER($this->depth,"store Object\n");
			// fim: [univis] optionally debug parsed model befoe storing
			// log_var($this->modelObj->attributes, get_class($this->modelObj));
			// fim.
		    $this->modelObj->store();
		    $this->modelObj = null;
		    unset($this->modelObj);
		}

		$this->attrName = false;
		//if ($name == $this->parent->tagName)
		if ($name == $this->tagName && $this->parent)
		{
		    U2T3_DEBUG_PARSER($this->depth,"Return from child {$this->modelClass}\n");
	            xml_set_element_handler($this->parent->xmlParser, array($this->parent, "startElement"), array($this->parent, "endElement"));
	            xml_set_character_data_handler($this->parent->xmlParser, array($this->parent, "characterData"));
		    $this->parent->unsetChild();
		}
    }

    function characterData($parser, $data) 
    {
		if (!trim($data))
		{
		    return;
		}
		if ($this->attrName != false)
		{
		    U2T3_DEBUG_PARSER($this->depth,"Add attribute {$this->attrName} = $data to {$this->tagName}\n");
		    $this->modelObj->setAttribute($this->attrName, $data);
		}
    }

    function parse($data)
    {
		U2T3_DEBUG_PARSER($this->depth,"Start parsing of {$this->tagName} \n");
		$this->xmlParser = xml_parser_create();
		xml_set_element_handler($this->xmlParser, array($this, "startElement"), array($this, "endElement"));
		xml_set_character_data_handler($this->xmlParser, array($this, "characterData"));
		if (!xml_parse($this->xmlParser, $data, true))
		{
		    $this->errorString = xml_error_string(xml_get_error_code($this->xmlParser));
		    $this->errorLine = xml_get_current_line_number($this->xmlParser);
		    trigger_error('XML error at line '.$this->errorLine.': '.$this->errorString); // only notice
		    return false;
		}
		xml_parser_free($this->xmlParser);
		return true;
    }
}
?>
