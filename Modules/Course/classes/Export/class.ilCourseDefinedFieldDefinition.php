<?php
/*
    +-----------------------------------------------------------------------------+
    | ILIAS open source                                                           |
    +-----------------------------------------------------------------------------+
    | Copyright (c) 1998-2006 ILIAS open source, University of Cologne            |
    |                                                                             |
    | This program is free software; you can redistribute it and/or               |
    | modify it under the terms of the GNU General Public License                 |
    | as published by the Free Software Foundation; either version 2              |
    | of the License, or (at your option) any later version.                      |
    |                                                                             |
    | This program is distributed in the hope that it will be useful,             |
    | but WITHOUT ANY WARRANTY; without even the implied warranty of              |
    | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
    | GNU General Public License for more details.                                |
    |                                                                             |
    | You should have received a copy of the GNU General Public License           |
    | along with this program; if not, write to the Free Software                 |
    | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
    +-----------------------------------------------------------------------------+
*/

define("IL_CDF_SORT_ID", 'field_id');
define("IL_CDF_SORT_NAME", 'field_name');

define('IL_CDF_TYPE_TEXT', 1);
define('IL_CDF_TYPE_SELECT', 2);
// fau: courseUdf - add type email and checkbox
define('IL_CDF_TYPE_EMAIL', 10);
define('IL_CDF_TYPE_CHECKBOX', 11);
// fau.

/**
* @author Stefan Meyer <meyer@leifos.com>
* @version $Id$
*
*
* @ingroup Modules/Course
*/
class ilCourseDefinedFieldDefinition
{
    private $db;
    private $obj_id;

    private $id;
    private $name;
    private $type;
    private $values;
    private $value_options = array();
    private $required;

    // fau: courseUdf - add properties
    private $description;
    private $email_auto;
    private $email_text;
    private $parent_field_id;
    private $parent_value_id;
    // fau.

    /**
     * Constructor
     *
     * @access public
     * @param int course obj_id
     * @param int field_id
     *
     */
    public function __construct($a_obj_id, $a_field_id = 0)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
        
        $this->db = $ilDB;
        $this->obj_id = $a_obj_id;
        $this->id = $a_field_id;
        
        if ($this->id) {
            $this->read();
        }
    }
    
    /**
     * Clone fields
     *
     * @access public
     * @static
     *
     * @param int source obj id
     * @param int target obj_id
     */
    public static function _clone($a_source_id, $a_target_id)
    {
        // fau: courseUdf - clone extended properties
        $origFields = array();
        $clonedFields = array();
        $idMap = array();

        /** @var ilCourseDefinedFieldDefinition $field_obj */
        foreach (ilCourseDefinedFieldDefinition::_getFields($a_source_id) as $field_obj) {
            $origFields[$field_obj->getId()] = $field_obj;
        }

        foreach ($origFields as $field_id => $field_obj) {
            $cdf = new ilCourseDefinedFieldDefinition($a_target_id);
            $cdf->setName($field_obj->getName());
            $cdf->setType($field_obj->getType());
            $cdf->setValues($field_obj->getValues());
            $cdf->setValueOptions($field_obj->getValueOptions());
            $cdf->enableRequired($field_obj->isRequired());
            $cdf->setDescription($field_obj->getDescription());
            $cdf->setEmailText($field_obj->getEmailText());
            $cdf->setEmailAuto($field_obj->getEmailAuto());
            $cdf->setParentFieldId($field_obj->getParentFieldId());
            $cdf->setParentValueId($field_obj->getParentValueId());
            $cdf->save();

            $idMap[$field_obj->getId()] = $cdf->getId();
            $clonedFields[$cdf->getId()] = $cdf;
        }

        // map the cloned parent ids
        /** @var ilCourseDefinedFieldDefinition $cdf */
        foreach ($clonedFields as $field_id => $cdf) {
            if ($cdf->getParentFieldId()) {
                $cdf->setParentFieldId($idMap[$cdf->getParentFieldId()]);
                $cdf->update();
            }
        }
        // fau.
    }
    
    /**
     * Delete all fields of a container
     *
     * @access public
     * @static
     * @param int container_id
     *
     */
    public static function _deleteByContainer($a_container_id)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
        
        // Delete user entries
        include_once('Modules/Course/classes/Export/class.ilCourseUserData.php');
        foreach (ilCourseDefinedFieldDefinition::_getFieldIds($a_container_id) as $field_id) {
            ilCourseUserData::_deleteByField($field_id);
        }

        $query = "DELETE FROM crs_f_definitions " .
            "WHERE obj_id = " . $ilDB->quote($a_container_id, 'integer') . " ";
        $res = $ilDB->manipulate($query);
    }
    
    /**
     * Check if there are any define fields
     *
     * @access public
     * @param int container_id
     */
    public static function _hasFields($a_container_id)
    {
        return count(ilCourseDefinedFieldDefinition::_getFields($a_container_id));
    }
    
    /**
     * Get all fields of a container
     *
     * @access public
     * @static
     * @param int container obj_id
     * @return ilCourseDefinedFieldDefinitions[]
     */
    public static function _getFields($a_container_id, $a_sort = IL_CDF_SORT_NAME)
    {
        foreach (ilCourseDefinedFieldDefinition::_getFieldIds($a_container_id, IL_CDF_SORT_ID) as $field_id) {
            $fields[] = new ilCourseDefinedFieldDefinition($a_container_id, $field_id);
        }
        return $fields ? $fields : array();
    }

    // fau: courseUdf - new function _getPossibleParentFields

    /**
     * Get the possible parent fields for a field
     * The parent field must be of SELECT type and must not have an own parent
     *
     * @param $a_container_id
     * @param $a_field_id
     * @param string $a_sort
     * @return ilCourseDefinedFieldDefinition[]
     */
    public static function _getPossibleParentFields($a_container_id, $a_field_id = null, $a_sort = IL_CDF_SORT_NAME)
    {
        $fields = array();
        foreach (ilCourseDefinedFieldDefinition::_getFieldIds($a_container_id, $a_sort) as $field_id) {
            if (empty($a_field_id) || $field_id != $a_field_id) {
                $field = new ilCourseDefinedFieldDefinition($a_container_id, $field_id);
                if ($field->getType() == IL_CDF_TYPE_SELECT && empty($field->getParentFieldId() && !empty($field->getValues()))) {
                    $fields[] = $field;
                }
            }
        }
        return $fields;
    }
    // fau.

    // fau: courseUdf - new function _getChildFields()
    /**
     * Count the child fields assiciated with a field
     * @param $a_container_id
     * @param $a_field_id
     * @return ilCourseDefinedFieldDefinition[]
     */
    public static function _getChildFields($a_container_id, $a_field_id)
    {
        global $ilDB;

        $query = "SELECT field_id FROM crs_f_definitions " .
            "WHERE obj_id = " . $ilDB->quote($a_container_id, 'integer') . " " .
            "AND parent_field_id = " . $ilDB->quote($a_field_id, 'integer');

        $res = $ilDB->query($query);
        $childs = array();
        while ($row = $ilDB->fetchObject($res)) {
            $childs[] = new ilCourseDefinedFieldDefinition($a_container_id, $res->field_id);
        }
        return $childs;
    }
    // fau.

    /**
     * Get required filed id's
     *
     * @access public
     * @static
     *
     * @param int container id
     */
    public static function _getRequiredFieldIds($a_obj_id)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
        

        // fau: courseUdf - get only the required fields on top level
        //					required sub fields are checked in the input form
        $query = "SELECT * FROM crs_f_definitions " .
            "WHERE obj_id = " . $ilDB->quote($a_obj_id, 'integer') . " " .
            "AND field_required = 1 " .
            "AND parent_field_id IS NULL";
        // fau.
        $res = $ilDB->query($query);
        while ($row = $ilDB->fetchObject($res)) {
            $req_fields[] = $row->field_id;
        }
        return $req_fields ? $req_fields : array();
    }
    
    /**
     * Fields to info string
     *
     * @access public
     * @static
     *
     * @param int obj_id
     */
    public static function _fieldsToInfoString($a_obj_id)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
        
        
        $query = "SELECT field_name FROM crs_f_definitions " .
            "WHERE obj_id = " . $ilDB->quote($a_obj_id, 'integer');
        
        $res = $ilDB->query($query);
        $fields = array();
        while ($row = $ilDB->fetchObject($res)) {
            $fields[] = $row->field_name;
        }
        return implode('<br />', $fields);
    }
    
    /**
     * Get all field ids of a container
     *
     * @access public
     * @static
     * @param int container obj_id
     * @return array array of field ids
     */
    public static function _getFieldIds($a_container_id, $a_sort = IL_CDF_SORT_ID)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
        
        $query = "SELECT field_id FROM crs_f_definitions " .
            "WHERE obj_id = " . $ilDB->quote($a_container_id, 'integer') . " " .
            "ORDER BY " . IL_CDF_SORT_ID;
        $res = $ilDB->query($query);
        while ($row = $ilDB->fetchObject($res)) {
            $field_ids[] = $row->field_id;
        }
        return $field_ids ? $field_ids : array();
    }
        
    /**
     * Lookup field name
     *
     * @access public
     * @static
     *
     * @param int field_id
     */
    public static function _lookupName($a_field_id)
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
        
        $query = "SELECT * FROM crs_f_definitions " .
            "WHERE field_id = " . $ilDB->quote($a_field_id, 'integer');
        
        $res = $ilDB->query($query);
        $row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT);
        
        return $row->field_name ? $row->field_name : '';
    }
    
    public function getObjId()
    {
        return $this->obj_id;
    }
    public function getId()
    {
        return $this->id;
    }
    public function getType()
    {
        return $this->type;
    }
    public function setType($a_type)
    {
        $this->type = $a_type;
    }
    public function getName()
    {
        return $this->name;
    }
    public function setName($a_name)
    {
        $this->name = $a_name;
    }
    public function getValues()
    {
        return $this->values ? $this->values : array();
    }
    public function setValues($a_values)
    {
        $this->values = $a_values;
    }
    public function getValueById($a_id)
    {
        if (is_array($this->values) and array_key_exists($a_id, $this->values)) {
            return $this->values[$a_id];
        }
        return '';
    }
    public function getIdByValue($a_value)
    {
        return (($pos = array_search($a_value, $this->values)) === false) ? -1 : $pos;
    }
    
    public function isRequired()
    {
        return (bool) $this->required;
    }
    public function enableRequired($a_status)
    {
        $this->required = $a_status;
    }
    
    public function setValueOptions($a_options)
    {
        $this->value_options = $a_options;
    }
    
    public function getValueOptions()
    {
        return (array) $this->value_options;
    }

    // fau: courseUdf - setters and getters
    public function setDescription($a_description)
    {
        $this->description = $a_description;
    }
    public function getDescription()
    {
        return (string) $this->description;
    }

    public function setEmailAuto($a_auto)
    {
        $this->email_auto = $a_auto;
    }
    public function getEmailAuto()
    {
        return (bool) $this->email_auto;
    }

    public function setEmailText($a_text)
    {
        $this->email_text = $a_text;
    }
    public function getEmailText()
    {
        return (string) $this->email_text;
    }
    public function setParentFieldId($a_id)
    {
        $this->parent_field_id = $a_id;
    }
    public function getParentFieldId()
    {
        return (string) $this->parent_field_id;
    }
    public function setParentValueId($a_id)
    {
        $this->parent_value_id = $a_id;
    }
    public function getParentValueId()
    {
        return (string) $this->parent_value_id;
    }

    // fau.

    /**
     * Prepare an array of options for ilUtil::formSelect()
     *
     * @access public
     * @param
     *
     */
    public function prepareSelectBox()
    {
        global $DIC;

        $lng = $DIC['lng'];
        
        $options = array();
        $options[''] = $lng->txt('select_one');
        
        foreach ($this->values as $key => $value) {
            $options[$this->getId() . '_' . $key] = $value;
        }
        return $options;
    }
    
    /**
     * Prepare values from POST
     *
     * @param array array of values
     * @access public
     */
    public function prepareValues($a_values)
    {
        $tmp_values = array();
        
        if (!is_array($a_values)) {
            return false;
        }
        foreach ($a_values as $idx => $value) {
            if (strlen($value)) {
                $tmp_values[$idx] = $value;
            }
        }
        return $tmp_values ? $tmp_values : array();
    }
    
    /**
     * Append Values
     *
     * @access public
     */
    public function appendValues($a_values)
    {
        if (!is_array($a_values)) {
            return false;
        }
        $this->values = array_unique(array_merge($this->values, $a_values));
        #sort($this->values);
        return true;
    }
    
    /**
     * Delete value by id
     *
     * @access public
     */
    public function deleteValue($a_id)
    {
        if (!isset($this->values[$a_id])) {
            return false;
        }
        unset($this->values[$a_id]);
        array_merge($this->values);
        $this->update();
        return true;
    }
    
    /**
     * Save
     *
     * @access public
     *
     */
    public function save()
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
        
        $next_id = $ilDB->nextId('crs_f_definitions');
        // fau: courseUdf - save additional properties
        $query = "INSERT INTO crs_f_definitions (field_id,obj_id,field_name,field_type,field_values,field_required,field_values_opt,field_desc,field_email_auto,field_email_text, parent_field_id, parent_value_id) " .
            "VALUES ( " .
            $ilDB->quote($next_id, 'integer') . ", " .
            $this->db->quote($this->getObjId(), 'integer') . ", " .
            $this->db->quote($this->getName(), "text") . ", " .
            $this->db->quote($this->getType(), 'integer') . ", " .
            $this->db->quote(serialize($this->getValues()), 'text') . ", " .
            $ilDB->quote($this->isRequired(), 'integer') . ", " .
            $ilDB->quote(serialize($this->getValueOptions()), 'text') . ', ' .
            $this->db->quote($this->getDescription(), 'text') . ', ' .
            $this->db->quote($this->getEmailAuto(), 'integer') . ', ' .
            $this->db->quote($this->getEmailText(), 'text') . ', ' .
            $this->db->quote($this->getParentFieldId(), 'integer') . ', ' .
            $this->db->quote($this->getParentValueId(), 'integer') .
            ") ";
        // fau.
        $res = $ilDB->manipulate($query);
        $this->id = $next_id;
            
        return true;
    }
    
    /**
     * Update a field
     *
     * @access public
     */
    public function update()
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
        

        // fau: courseUdf - update additional properties
        $query = "UPDATE crs_f_definitions " .
            "SET field_name = " . $this->db->quote($this->getName(), 'text') . ", " .
            "field_type = " . $this->db->quote($this->getType(), 'integer') . ", " .
            "field_values = " . $this->db->quote(serialize($this->getValues()), 'text') . ", " .
            "field_required = " . $ilDB->quote($this->isRequired(), 'integer') . ", " .
            'field_values_opt = ' . $ilDB->quote(serialize($this->getValueOptions()), 'text') . ', ' .
            'field_desc = ' . $this->db->quote($this->getDescription(), 'text') . ', ' .
            'field_email_auto = ' . $this->db->quote($this->getEmailAuto(), 'integer') . ', ' .
            'field_email_text = ' . $this->db->quote($this->getEmailText(), 'text') . ', ' .
            'parent_field_id = ' . $this->db->quote($this->getParentFieldId(), 'integer') . ', ' .
            'parent_value_id = ' . $this->db->quote($this->getParentValueId(), 'integer') . ' ' .
            "WHERE field_id = " . $this->db->quote($this->getId(), 'integer') . " " .
            "AND obj_id = " . $this->db->quote($this->getObjId(), 'integer');
        // fau.
        $res = $ilDB->manipulate($query);
        return true;
    }
    
    /**
     * Delete a field
     *
     * @access public
     * @param
     *
     */
    public function delete()
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];
        
        include_once('Modules/Course/classes/Export/class.ilCourseUserData.php');
        ilCourseUserData::_deleteByField($this->getId());
        
        $query = "DELETE FROM crs_f_definitions " .
            "WHERE field_id = " . $this->db->quote($this->getId(), 'integer') . " ";
        $res = $ilDB->manipulate($query);

        // fau: courseUdf - free the sub fields when parent field is deleted
        $query = "UPDATE crs_f_definitions " .
            "SET parent_field_id = NULL, parent_value_id = NULL WHERE parent_field_id = " . $this->db->quote($this->getId(), 'integer') . " ";
        $res = $ilDB->manipulate($query);
        // fau.
    }
    
    /**
     * Read DB entries
     *
     * @access private
     *
     */
    private function read()
    {
        $query = "SELECT * FROM crs_f_definitions " .
            "WHERE field_id = " . $this->db->quote($this->getId(), 'integer') . " " .
            "AND obj_id = " . $this->db->quote($this->getObjId(), 'integer') . " ";
        
        $res = $this->db->query($query);
        $row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT);
        
        $this->setName($row->field_name);
        $this->setType($row->field_type);
        $this->setValues(unserialize($row->field_values));
        $this->setValueOptions(unserialize($row->field_values_opt));
        $this->enableRequired($row->field_required);
        // fau: courseUdf - read additional properties
        $this->setDescription($row->field_desc);
        $this->setEmailAuto($row->field_email_auto);
        $this->setEmailText($row->field_email_text);
        $this->setParentFieldId($row->parent_field_id);
        $this->setParentValueId($row->parent_value_id);
        // fau.
    }
}
