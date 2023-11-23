<?php

use ILIAS\DI\Container;


class ilFAUAppEventListener implements ilAppEventListener
{
    static self $instance;

    protected Container $dic;
    protected \ilLogger $log;

    protected bool $active = true;


    /**
     * Constructor
     */
    protected function __construct(Container $dic)
    {
        $this->dic = $dic;
    }

    /**
     * Singleton
     */
    public static function getInstance() : self
    {
        global $DIC;
        if (!isset(self::$instance)) {
            self::$instance = new self($DIC);
        }
        return self::$instance;
    }

    /**
     * Set the active status of the singleton instance
     */
    public function setActive(bool $active)
    {
        $this->active = $active;
    }

    /**
     * Get the active status of the singleton instance
     */
    public function isActive() : bool
    {
        return $this->active;
    }

    /**
     * Handle events like create, update, delete
     *
     * @access public
     * @param	string	$a_component	component, e.g. "Modules/Forum" or "Services/User"
     * @param	string	$a_event		event e.g. "createUser", "updateUser", "deleteUser", ...
     * @param	array	$a_parameter	parameter array (assoc), array("name" => ..., "phone_office" => ...)	 *
     * @static
     */
    public static function handleEvent($a_component, $a_event, $a_parameter)
    {
        if (!self::getInstance()->isActive()) {
            return;
        }

        switch ($a_component) {

            case 'Modules/Group':
            case 'Modules/Course':
                switch ($a_event) {
                    case 'update':
                        self::getInstance()->handleObjectUpdate((int) $a_parameter['obj_id']);
                        break;

                    case 'delete':
                        self::getInstance()->handleObjectDelete((int) $a_parameter['obj_id']);
                        break;

                    case 'addParticipant':
                        self::getInstance()->handleAddParticipant((int) $a_parameter['obj_id'], (int) $a_parameter['usr_id'], (int) $a_parameter['role_id']);
                        break;

                    case 'deleteParticipant':
                        self::getInstance()->handleDeleteParticipant((int) $a_parameter['obj_id'], (int) $a_parameter['usr_id'], (int) $a_parameter['role_id']);
                        break;
                }
                break;

            case 'Services/AccessControl':
                switch ($a_event) {
                    case 'assignUser':
                        self::getInstance()->handleAddToRole((int) $a_parameter['obj_id'], (int) $a_parameter['usr_id'], (int) $a_parameter['role_id'], (string) $a_parameter['type']);
                        break;

                    case 'deassignUser':
                        self::getInstance()->handleRemoveFromRole((int) $a_parameter['obj_id'], (int) $a_parameter['usr_id'], (int) $a_parameter['role_id'], (string) $a_parameter['type']);
                        break;
                }
                break;

            case 'Services/Object':
                switch ($a_event) {
                    case 'toTrash':
                        self::getInstance()->handleObjectDelete((int) $a_parameter['obj_id']);
                        break;
                }
                break;

            case 'Services/User':
                switch ($a_event) {
                    case 'deleteUser':
                        self::getInstance()->handleUserDelete((int) $a_parameter['usr_id']);
                        break;
                }
                break;
        }
    }

    /**
     * Handle the update of object settings
     */
    protected function handleObjectUpdate(int $obj_id)
    {
        $this->dic->fau()->ilias()->objects()->handleUpdate($obj_id);
    }

    /**
     * Handle the deletion of a course or a group
     * (trash or final delete)
     */
    protected function handleObjectDelete(int $obj_id)
    {
        // delete the reference in a campo course
        // new object will be created in the next sync
        // Important: don't use cache - record may already be changed by \FAU\Ilias\Transfer::moveCampoConnection
        foreach ($this->dic->fau()->study()->repo()->getCoursesByIliasObjId($obj_id, false) as $course) {
            if ($course->isDeleted()) {
                // course entry no longer needed - staging entry is already deleted
                $this->dic->fau()->study()->repo()->delete($course);
            }

            $this->dic->fau()->study()->repo()->save(
                $course->withIliasObjId(null)->withIliasProblem(null)->asChanged(false)
            );
        }

        // delete the membership status
        foreach ($this->dic->fau()->user()->repo()->getMembersOfObject($obj_id , null, false) as $member) {
            $this->dic->fau()->user()->repo()->delete($member);
        }

        // remove the references to course and event in the import id
        $this->dic->fau()->sync()->repo()->removeObjectFauImportId($obj_id);
    }

    /**
     * Handle the deletion of a user account
     */
    protected function handleUserDelete(int $user_id)
    {
        // delete the membership status
        foreach ($this->dic->fau()->user()->repo()->getMembersOfUser($user_id , false) as $member) {
            $this->dic->fau()->user()->repo()->delete($member);
        }

        // delete the person record
        if (!empty($person = $this->dic->fau()->user()->repo()->getPersonOfUser($user_id))) {
            $this->dic->fau()->user()->repo()->delete($person);
        }
    }

    /**
     * Handle the adding of a participant to a course or group
     * (called for courses and groups)
     */
    protected function handleAddParticipant(int $obj_id, int $user_id, int $role_id)
    {
        if ($role_id == IL_CRS_MEMBER || $role_id == IL_GRP_MEMBER) {
            $this->dic->fau()->user()->saveMembership($obj_id, $user_id);
        }
        // fau: cascadeMembers - add as members to parent courses and groups
        $this->addParticipantToParents($obj_id, $user_id);
        // fau.
    }

    /**
     * Handle the deletion of a participant to a course or group
     * (called for courses and groups)
     */
    protected function handleDeleteParticipant(int $obj_id, int $user_id, int $role_id)
    {
        if ($role_id == IL_CRS_MEMBER || $role_id == IL_GRP_MEMBER) {
            $this->dic->fau()->user()->deleteMembership($obj_id, $user_id);
        }
        // fau: cascadeMembers - add as members to parent courses and groups
        $this->removeParticipantFromChildren($obj_id, $user_id);
        // fau.
    }

    /**
     * Handle the adding of a user to a role
     */
    protected function handleAddToRole(int $obj_id, int $user_id, int $role_id, string $type)
    {
        if ($type == 'crs' || $type == 'grp') {
            $title = ilObject::_lookupTitle($role_id);
            if (in_array(substr($title, 0, 14), ['il_crs_member_', 'il_grp_member_'])) {
                $this->dic->fau()->user()->saveMembership($obj_id, $user_id);
            }
        }
    }

    /**
     * Handle the removing of a user to a role
     */
    protected function handleRemoveFromRole(int $obj_id, int $user_id, int $role_id, string $type)
    {
        if ($type == 'crs' || $type == 'grp') {
            $title = ilObject::_lookupTitle($role_id);
            if (in_array(substr($title, 0, 14), ['il_crs_member_', 'il_grp_member_'])) {
                $this->dic->fau()->user()->deleteMembership($obj_id, $user_id);
            }
        }
    }

    /**
     * Ensure that an added participant is also added to the parent course or groups
     * 
     * @param int $obj_id
     * @param int $user_id
     */
    protected function addParticipantToParents(int $obj_id, int $user_id): void
    {
        // prevent recursive calls in the loop
        $this->setActive(false);
        
        $ref_id = $this->dic->fau()->ilias()->objects()->getUntrashedReference($obj_id);
        if (isset($ref_id)) {
            $path = $this->dic->repositoryTree()->getPathId($ref_id);
            foreach ($path as $path_id) {
                if ($path_id == $ref_id) {
                    continue;
                }
                $type = ilObject::_lookupType($path_id, true);
                if (in_array($type, ['crs', 'grp'])) {
                    if (!ilParticipants::_isParticipant($path_id, $user_id)) {
                        $path_obj_id = ilObject::_lookupObjId($path_id);
                        switch ($type) {
                            case 'crs':
                                $participants = ilCourseParticipants::_getInstanceByObjId($path_obj_id);
                                $participants->add($user_id, IL_CRS_MEMBER);
                            case 'grp':
                                $participants = ilGroupParticipants::_getInstanceByObjId($path_obj_id);
                                $participants->add($user_id, IL_GRP_MEMBER);
                        }
                    }
                }
            }
        }
        
        $this->setActive(true);
    }

    /**
     * Ensure that a removed participant is also removed from child objects
     * This will recourse if a participant is removed from a child object
     * @param int $obj_id
     * @param int $user_id
     * @return void
     */
    protected function removeParticipantFromChildren(int $obj_id, int $user_id) : void
    {
        $ref_id = $this->dic->fau()->ilias()->objects()->getUntrashedReference($obj_id);
        if (isset($ref_id)) {
            $nodes = $this->dic->repositoryTree()->getChildsByTypeFilter($ref_id, ['grp', 'sess', 'lso']);
            foreach($nodes as $node) {
                if (ilParticipants::_isParticipant($node['child'], $user_id)) {
                    switch ($node['type']) {
                        case 'grp':
                            $participants = ilGroupParticipants::_getInstanceByObjId($node['obj_id']);
                            $participants->delete($user_id);
                            break;
                        case 'sess':
                            $participants = ilSessionParticipants::_getInstanceByObjId($node['obj_id']);
                            $participants->delete($user_id);
                            break;
                        case 'lso':
                            $participant = ilLearningSequenceParticipants::_getInstanceByObjId($node['obj_id']);
                            $participant->delete($user_id);
                            break;
                    }
                }
            }
        }
    }
}