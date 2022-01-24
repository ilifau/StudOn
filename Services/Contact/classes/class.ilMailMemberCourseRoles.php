<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Contact/classes/class.ilAbstractMailMemberRoles.php';

/**
 * Class ilMailMemberCourseRoles
 * @author Nadia Matuschek <nmatuschek@databay.de>
 */
class ilMailMemberCourseRoles extends ilAbstractMailMemberRoles
{
    /**
     * @var ilLanguage
     */
    protected $lng;

    /**
     * @var ilRbacReview
     */
    protected $rbacreview;

    /**
     * ilMailMemberCourseRoles constructor.
     */
    public function __construct()
    {
        global $DIC;

        $this->lng = $DIC['lng'];
        $this->rbacreview = $DIC['rbacreview'];
    }

    /**
     * @return string
     */
    public function getRadioOptionTitle()
    {
        return $this->lng->txt('mail_crs_roles');
    }

    /**
     * @param $ref_id
     * @return array sorted_roles
     */
    public function getMailRoles($ref_id)
    {
        $role_ids = $this->rbacreview->getLocalRoles($ref_id);

        // Sort by relevance
        $sorted_role_ids = array();
        $counter = 3;

        foreach ($role_ids as $role_id) {
            // fau: mailToRoleAddress - always use the role title for standard roles
            $role_title = ilObject::_lookupTitle($role_id);
            if (substr($role_title, 0, 7) == 'il_crs_') {
                $mailbox = '#' . $role_title;
            } else {
                $mailbox = $this->getMailboxRoleAddress($role_id);
            }
            // fau.

            switch (substr($role_title, 0, 8)) {
                case 'il_crs_a':
// fau: mailToMembers - identify admins for mail roles
                    $sorted_role_ids[2]['is_admin'] = true;
// fau.
                    $sorted_role_ids[2]['role_id'] = $role_id;
                    $sorted_role_ids[2]['mailbox'] = $mailbox;
                    $sorted_role_ids[2]['form_option_title'] = $this->lng->txt('send_mail_admins');
                    break;

                case 'il_crs_t':
                    $sorted_role_ids[1]['role_id'] = $role_id;
                    $sorted_role_ids[1]['mailbox'] = $mailbox;
                    $sorted_role_ids[1]['form_option_title'] = $this->lng->txt('send_mail_tutors');
                    break;

                case 'il_crs_m':
                    $sorted_role_ids[0]['role_id'] = $role_id;
                    $sorted_role_ids[0]['mailbox'] = $mailbox;
                    $sorted_role_ids[0]['form_option_title'] = $this->lng->txt('send_mail_members');
                    break;

                default:
                    $sorted_role_ids[$counter]['role_id'] = $role_id;
                    $sorted_role_ids[$counter]['mailbox'] = $mailbox;
                    $sorted_role_ids[$counter]['form_option_title'] = $role_title;

                    $counter++;
                    break;
            }
        }
        ksort($sorted_role_ids, SORT_NUMERIC);

        return $sorted_role_ids;
    }
}
