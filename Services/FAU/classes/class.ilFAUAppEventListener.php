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
     */
    protected function handleAddParticipant(int $obj_id, int $user_id, int $role_id)
    {
        if ($role_id == IL_CRS_MEMBER || $role_id == IL_GRP_MEMBER) {
            $this->dic->fau()->user()->saveMembership($obj_id, $user_id);
        }
    }

    /**
     * Handle the deletion of a participant to a course or group
     */
    protected function handleDeleteParticipant(int $obj_id, int $user_id, int $role_id)
    {
        if ($role_id == IL_CRS_MEMBER || $role_id == IL_GRP_MEMBER) {
            $this->dic->fau()->user()->deleteMembership($obj_id, $user_id);
        }
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
}