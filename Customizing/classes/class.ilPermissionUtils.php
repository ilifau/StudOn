<?php
/**
 * fim: [cust] Utilies for handling permission settings
 * See also the ILIAS permission guidelines for new object types
 *
 * @see \ilDBUpdateNewObjectType::applyInitialPermissionGuideline
 * @see https://www.ilias.de/docu/goto_docu_wiki_wpage_2273_1357.html
 */
class ilPermissionUtils
{
	/**
	 * allow output
	 * @var boolean
	 */
	var $output = false;
	
	/**
	 * operation ids
	 * @var array
	 */
	var $ops_ids = array();
	
	
	/**
	 * Constructor
	 */
	function __construct($a_output = false)
	{
		$this->output = (bool) $a_output;
	}
	
	
	/**
	* Copy a default permission in all RBAC templates
	*
	* An RBAC template defines the permission settings applied to new objects.
	* RBAC templates exists for each role, role template and for each local policy.
	* This function does not change the permission settings of existing objects.
	*
	* Example:
	* copyDefaultPermission('svy', 'read', 'poll', read');
	* This will set read permission for new polls where read permission is set for new surveys
	*
	* @param 	string		source type
	* @param	string		source operation,
	* @param 	string		target type
	* @param	string		target operation
	*/
	public function copyDefaultPermission($a_source_type, $a_source_operation, $a_target_type, $a_target_operation)
	{
		global $ilDB;
		
		if($this->output)
		{
			echo "copyDefaultPermission($a_source_type, $a_source_operation, $a_target_type, $a_target_operation)...\n";
		}
	
		$source_ops_id = $this->getRbacOpsId($a_source_operation);
		$target_ops_id = $this->getRbacOpsId($a_target_operation);

        $q_source_type = $ilDB->quote($a_source_type, "text");
        $q_target_type = $ilDB->quote($a_target_type, "text");
        $q_source_ops_id = $ilDB->quote($source_ops_id, 'integer');
        $q_target_ops_id = $ilDB->quote($target_ops_id, 'integer');

        $q = "
          INSERT INTO rbac_templates(parent, rol_id, type, ops_id)
			SELECT s.parent, s.rol_id, $q_target_type, $q_target_ops_id
            FROM rbac_templates s
			WHERE s.type = $q_source_type
			AND s.ops_id = $q_source_ops_id
			AND NOT EXISTS (
			  SELECT * FROM rbac_templates t
			  WHERE t.parent = s.parent
			  AND t.rol_id = s.rol_id
			  AND t.type = $q_target_type
			  AND t.ops_id = $q_target_ops_id
			)
		";

		$ilDB->manipulate($q);
	}
	
	
	/**
	* Copy default permissions commonly for some types in all RBAC templates
	*
	* An RBAC template defines the permission settings applied to new objects.
	* RBAC templates exists for each role, role template and for each local policy.
	* This function does not change the permission settings of existing objects.
	*
	* Example:
	* copyDefaultPermissions(	array ('cat','crs','grp','fold'),
	*							array (	
	*								array ('create_wiki', 	'create_blog'),
	*								array ('create_svy, 	'create_poll')
	*						));						
	*
	* This will add the following default permissions for all new container objects: 
	* - creation of blogs where wikis can be created 
	* - creation of polls where surveys can be created
	*
	* @param	array		types to modify
	* @param	array		operations to copy
	*/
	public function copyDefaultPermissions($a_types = array(), $a_operations = array())
	{
		foreach ($a_types as $type)
		{
			foreach ($a_operations as $pair)
			{
				$this->copyDefaultPermission($type, $pair[0], $type, $pair[1]);
			}
		}
	}
	
	
	/**
	* Copy existing permissions in all objects of specific types
	* 
	* Example:
	* copyPermissions(		array ('cat','crs','grp','fold'),
	*						array (	
	*								array ('create_wiki', 	'create_blog'),
	*								array ('create_svy, 	'create_poll')
	*				));		
	*				
	* This will add the following actual permissions in all container objects: 
	* - creation of blogs where wikis can be created 
	* - creation of polls where surveys can be created
	*
	* @param	array		types to modify
	* @param	array		operations to copy
	*/
	public function copyPermissions($a_types = array(), $a_operations = array())
	{
		global $ilDB;
		
		if($this->output)
		{
			echo "copyPermissions(" .print_r($a_types, true). ", " . print_r($a_operations, true). ")...\n";
		}
	
		// convert the operation names to ids
		foreach ($a_operations as $pair)
		{
			$operations[] = array(
				$this->getRbacOpsId($pair[0]), 
				$this->getRbacOpsId($pair[1])
			);	
		}
			
		$q = "SELECT pa.rol_id, pa.ops_id, pa.ref_id"
			." FROM rbac_pa pa"
			." INNER JOIN object_reference obr ON obr.ref_id = pa.ref_id"
			." INNER JOIN object_data od ON od.obj_id = obr.obj_id"
			." WHERE ". $ilDB->in("od.type", $a_types, false, 'text');
	
		$r = $ilDB->query($q);
		while($row = $ilDB->fetchObject($r))
		{
			$permissions = unserialize($row->ops_id);
			$modified = false;
			
			foreach ($operations as $pair)
			{
				if (in_array($pair[0], $permissions)
					and !in_array($pair[1], $permissions))
				{
					$permissions[] = (int) $pair[1];
					$modified = true;
				}
			}
			
			if ($modified)
			{
				$new_ops_id = serialize($permissions);
	
				$q="UPDATE rbac_pa "
					." SET ops_id = ". $ilDB->quote($new_ops_id, "text")
					." WHERE rol_id = ". $ilDB->quote($row->rol_id, "integer")
					." AND ref_id = ". $ilDB->quote($row->ref_id, "integer");
				$ilDB->manipulate($q);
			}
		}
	}
	
	
	/**
	* Set default permission in all RBAC templates for certain roles
	*
	* An RBAC template defines the permission settings applied to new objects.
	* RBAC templates exists for each role, role template and for each local policy.
	* This function does not change the permission settings of existing objects.
	*
	* Example:
	* setDefaultPermissions(	array('chtr'), 
	* 							array('write','delete'), 
	* 							array('il_crs_admin%', 'il_grp_admin%')
	* 						);
	* This will set write and delete permission for new chatrooms to course and group admins
	*
	* @param 	array		list of objects
	* @param	array		list of operations
	* @param 	array		list of 'like' patterns for role titles
	*/
	public function setDefaultPermissions($a_types = array(), $a_operations = array(), $a_roles = array())
	{
		global $ilDB;
		
		if($this->output)
		{
			echo "setDefaultPermission(\n"
				.print_r($a_types, true). ", "
				.print_r($a_operations, true). ", "
				.print_r($a_roles, true). "...\n";
		}
	
		// get the list of operation ids
		$ops_ids = array();
		foreach ($a_operations as $ops_name)
		{
			$ops_ids[] = $this->getRbacOpsId($ops_name);
		}
		
		// build the condition to select the roles
		$cond = array();
		foreach ($a_roles as $pattern)
		{
        $cond[] = "o.title LIKE ". $ilDB->quote($pattern, "text");
		}
		$cond = "(" . implode(" OR ", $cond) . ")";
		
		
		// select the relevant templates for the roles
		$q = " SELECT DISTINCT t.parent, t.rol_id "
			." FROM rbac_templates t"
			." INNER JOIN object_data o ON o.obj_id = t.rol_id"
			." WHERE " . $cond;

        //echo $q . "\n";
		
		$result = $ilDB->query($q);
		while ($row = $ilDB->fetchAssoc($result))
		{
			foreach ($a_types as $type)
			{
				foreach ($ops_ids as $ops_id)
				{

                    $q_rol_id = $ilDB->quote($row["rol_id"]);
                    $q_type = $ilDB->quote($type, "text");
                    $q_ops_id =  $ilDB->quote($ops_id, "integer");
                    $q_parent = $ilDB->quote($row["parent"], "integer");

					$q = "INSERT INTO rbac_templates (rol_id, type, ops_id, parent)
						  SELECT $q_rol_id, $q_type, $q_ops_id, $q_parent FROM DUAL
						  WHERE NOT EXISTS (
						    SELECT * FROM rbac_templates
						    WHERE rol_id = $q_rol_id
						    AND type = $q_type
						    AND ops_id = $q_ops_id
						    AND parent = $q_parent
						)";
					
					// echo $q . "\n";
					$ilDB->manipulate($q);
				}
			}
		}
	}
	
	
	/**
	* Set existing permissions in all objects of specific types
	* 
	* Example:
	* setPermissions(	array('chtr'), 
	* 					array('write','delete'), 
	* 					array('il_crs_admin%', 'il_grp_admin%')
	* 				);
	*				
	* This will set write and delete permission for existing chatrooms to course and group admins
	*
	* @param 	array		list of objects
	* @param	array		list of operations
	* @param 	array		list of 'like' patterns for role titles
	*/
	public function setPermissions($a_types = array(), $a_operations = array(), $a_roles = array())
	{
		global $ilDB, $rbacreview;
		
		if($this->output)
		{
			echo "setPermission(\n"
				.print_r($a_types, true). ", "
				.print_r($a_operations, true). ", "
				.print_r($a_roles, true). "...\n";
		}

		// make regexp patterns of the role titles
		$patterns = array();
		foreach ($a_roles as $pattern)
		{
			$pattern = str_replace('%','.*', $pattern);
			$pattern = str_replace('\.*','%', $pattern);
			$pattern = str_replace('_','.?', $pattern);	
			$pattern = str_replace('\.?','_', $pattern);	
			$patterns[] = '/^' . $pattern . '$/';		
		}
		
		// get the list of operation ids
		$ops_ids = array();
		foreach ($a_operations as $ops_name)
		{
			$ops_ids[] = $this->getRbacOpsId($ops_name);
		}
				
		// select the relevant objects			
		$q = "SELECT r.ref_id"
			." FROM object_reference r"
			." INNER JOIN object_data d ON d.obj_id = r.obj_id"
			." WHERE ". $ilDB->in("d.type", $a_types, false, 'text');
			
		//echo $q . "\n";		
		$r = $ilDB->query($q);
		while($row = $ilDB->fetchObject($r))
		{
			// get the active roles for the object
			$role_ids = $rbacreview->getParentRoleIds($row->ref_id);
			
			foreach ($role_ids as $role)
			{
				//echo $role['title'] . "=";
				//echo $role['obj_id'] . "\n";
				
				foreach($patterns as $pattern)
				{
					if (preg_match($pattern, $role['title']))
					{
						// select the existing permission assignments for role and object
						$q2 = "SELECT ops_id"
							." FROM rbac_pa"
							." WHERE rol_id = ". $ilDB->quote($role['obj_id'], "integer")
							." AND ref_id = ". $ilDB->quote($row->ref_id, "integer");
						
						//echo $q2 . "\n";	
						$r2 = $ilDB->query($q2);					
						if ($row2 = $ilDB->fetchObject($r2))
						{
							// permission assignment found
							
							$permissions = unserialize($row2->ops_id);
							if (!is_array($permissions))
							{
								$permissions = array();
							}
							$modified = false;
							
							foreach ($ops_ids as $ops_id)
							{
								if (!in_array($ops_id, $permissions))
								{
									$permissions[] = (int) $ops_id;
									$modified = true;
								}
							}
								
							if ($modified)
							{
								$new_ops_id = serialize($permissions);    
					
								$q3 = "UPDATE rbac_pa "
									." SET ops_id = ". $ilDB->quote($new_ops_id, "text")
									." WHERE rol_id = ". $ilDB->quote($role['obj_id'], "integer")
									." AND ref_id = ". $ilDB->quote($row->ref_id, "integer");
								
								//echo $q3 . "\n";
								$ilDB->manipulate($q3);			
							}
						}
						else
						{
							// permission assignment not found
							$new_ops_id = serialize($ops_ids);

							$q3 = "INSERT INTO rbac_pa (rol_id, ref_id, ops_id)"
								. " VALUES ("
								. $ilDB->quote($role['obj_id'], "integer"). ", "
								. $ilDB->quote($row->ref_id, "integer"). ", "
								. $ilDB->quote($new_ops_id, "text")
								. ")";
															
							//echo $q3 . "\n";
							$ilDB->manipulate($q3);			
							
						}
					}
				}
			}
		}	
	}
	
	
	/**
	* Get the Id of an RBAC operation
	*
	* @param 	string		operation name, e.g 'write'
	* @return	int			operation identifier
	*/
	private function getRbacOpsId($a_operation)
	{		
		if(empty($this->ops_ids))
		{
			global $ilDB;
			
			$q = "SELECT operation, ops_id FROM rbac_operations";
			$r = $ilDB->query($q);
			while ($row = $ilDB->fetchObject($r))
			{
				$this->ops_ids[$row->operation] = (int) $row->ops_id;
			}
		}
	
		return $this->ops_ids[$a_operation];
	}
}
?>