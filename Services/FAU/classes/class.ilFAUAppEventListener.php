<?php

use ILIAS\DI\Container;


class ilFAUAppEventListener implements ilAppEventListener
{
    protected Container $dic;
    protected \ilLogger $log;

    /**
     * Constructor
     */
    public function __construct(Container $dic)
    {
        $this->dic = $dic;
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
        global $DIC;
        switch ($a_component) {

            case 'Modules/Group':
            case 'Modules/Course':
                switch ($a_event) {
                    case 'update':
                        (new self($DIC))->handleObjectUpdate((int) $a_parameter['obj_id']);

                    case 'delete':
                        (new self($DIC))->handleObjectDelete((int) $a_parameter['obj_id']);
                        break;

                    case 'addParticipant':
                        (new self($DIC))->handleAddParticipant((int) $a_parameter['obj_id'], (int) $a_parameter['usr_id'], (int) $a_parameter['role_id']);
                        break;

                    case 'deleteParticipant':
                        (new self($DIC))->handleDeleteParticipant((int) $a_parameter['obj_id'], (int) $a_parameter['usr_id'], (int) $a_parameter['role_id']);
                        break;
                }
                break;

            case 'Services/Object':
                switch ($a_event) {
                    case 'toTrash':
                        (new self($DIC))->handleObjectDelete((int) $a_parameter['obj_id']);
                        break;
                }
                break;

            case 'Services/User':
                switch ($a_event) {
                    case 'deleteUser':
                        (new self($DIC))->handleUserDelete((int) $a_parameter['usr_id']);
                        break;
                }
                break;
        }
    }

    /**
     * Handle the update of object settings
     */
    public function handleObjectUpdate(int $obj_id)
    {
        $this->dic->fau()->ilias()->objects()->handleUpdate($obj_id);
    }

    /**
     * Handle the deletion of a course or a group
     * (trash or final delete)
     */
    public function handleObjectDelete(int $obj_id)
    {
        // delete the reference in a campo course
        // new object will be created in the next sync
        foreach ($this->dic->fau()->study()->repo()->getCoursesByIliasObjId($obj_id) as $course) {
            if ($course->isDeleted()) {
                // course entry no longer needed - staging entry is already deleted
                $this->dic->fau()->study()->repo()->delete($course);
            }

            $this->dic->fau()->study()->repo()->save(
                $course->withIliasObjId(null)->withIliasProblem(null)->asChanged(false)
            );
        }

        // delete the membership status
        foreach ($this->dic->fau()->user()->repo()->getMembersOfObject($obj_id , false) as $member) {
            $this->dic->fau()->user()->repo()->delete($member);
        }

        // remove the references to course and event in the import id
        $this->dic->fau()->sync()->repo()->removeObjectFauImportId($obj_id);
    }

    /**
     * Handle the deletion of a user account
     */
    public function handleUserDelete(int $user_id)
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
    public function handleAddParticipant(int $obj_id, int $user_id, int $role_id)
    {
        if (!$role_id == IL_CRS_MEMBER || $role_id == IL_GRP_MEMBER) {
            $this->dic->fau()->user()->saveMembership($obj_id, $user_id);
        }
    }

    /**
     * Handle the deletion of a participant to a course or group
     */
    public function handleDeleteParticipant(int $obj_id, int $user_id, int $role_id)
    {
        if (!$role_id == IL_CRS_MEMBER || $role_id == IL_GRP_MEMBER) {
            $this->dic->fau()->user()->deleteMembership($obj_id, $user_id);
        }
    }

}