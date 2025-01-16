<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

/**
 * TableGUI class for registration codes
 * @author       Alex Killing <alex.killing@gmx.de>
 * @version      $Id$
 * @ilCtrl_Calls ilRegistrationCodesTableGUI:
 * @ingroup      ServicesRegistration
 */
class ilRegistrationCodesTableGUI extends ilTable2GUI
{
    protected ilAccessHandler $access;
    protected ilRbacReview $rbacreview;

    public array $filter = [];
    /** @var array<int, string> */
    protected array $role_map = [];

    /**
     * Constructor
     */
    public function __construct(object $a_parent_obj, string $a_parent_cmd)
    {
        global $DIC;

        $this->access = $DIC->access();
        $this->rbacreview = $DIC->rbac()->review();
        $this->setId("registration_code");
        parent::__construct($a_parent_obj, $a_parent_cmd);

        $this->addColumn("", "", "1", true);
        // fau: regCodes - use selectable headrs, add actions column
        $columns = $this->getSelectableColumns();
        foreach ($this->getSelectedColumns() as $c => $caption) {
            if ($c == "role_local" || $c == "alimit") {
                $c = "";
            }
            if (isset($columns[$caption]))
                $this->addColumn($columns[$caption]['txt'], $c);
        }

        //$this->addColumn($this->lng->txt('actions'));
        // fau.
        
        $this->setExternalSorting(true);
        $this->setExternalSegmentation(true);
        $this->setEnableHeader(true);
        $this->setFormAction($this->ctrl->getFormAction($this->parent_obj, "listCodes"));
        $this->setRowTemplate("tpl.code_list_row.html", "Services/Registration");
        $this->setEnableTitle(true);
        $this->initFilter();
        $this->setFilterCommand("applyCodesFilter");
        $this->setResetCommand("resetCodesFilter");
        $this->setDefaultOrderField("generated"); // #11341
        $this->setDefaultOrderDirection("desc");

        $this->setSelectAllCheckbox("id[]");
        $this->setTopCommands(true);
        // fau: regCodes - add excel export
        $this->setExportFormats(array(self::EXPORT_EXCEL));
        // fau.        

        if ($this->access->checkAccess("write", '', $a_parent_obj->ref_id)) {
            $this->addMultiCommand("deleteConfirmation", $this->lng->txt("delete"));
        }
        $this->addCommandButton("exportCodes", $this->lng->txt("registration_codes_export"));
        $this->getItems();
    }

    /**
     * Get user items
     */
    private function getItems(): void
    {
        $this->determineOffsetAndOrder();

        // #12737
        if (!array_key_exists($this->getOrderField(), $this->getSelectedColumns())) {
            $this->setOrderField($this->getDefaultOrderField());
        }

        $codes_data = ilRegistrationCode::getCodesData(
            $this->getOrderField(),
            $this->getOrderDirection(),
            $this->getOffset(),
            $this->getLimit(),
            (string) $this->filter["code"],
            (int) $this->filter["role"],
            (string) $this->filter["generated"],
            (string) $this->filter["alimit"]
        );

        if (count($codes_data["set"]) === 0 && $this->getOffset() > 0) {
            $this->resetOffset();
            $codes_data = ilRegistrationCode::getCodesData(
                $this->getOrderField(),
                $this->getOrderDirection(),
                $this->getOffset(),
                $this->getLimit(),
                (string) $this->filter["code"],
                (int) $this->filter["role"],
                (string) $this->filter["generated"],
                (string) $this->filter["alimit"]
            );
        }

        $options = [];
        $this->role_map = [];
        foreach ($this->rbacreview->getGlobalRoles() as $role_id) {
            if (!in_array($role_id, [SYSTEM_ROLE_ID, ANONYMOUS_ROLE_ID], true)) {
                $this->role_map[$role_id] = ilObject::_lookupTitle($role_id);
            }
        }

        // fau: regCodes - extend table items
        ilDatePresentation::setUseRelativeDates(false);
        $login_types = ilRegistrationSettings::getLoginGenerationTypes();        
        
        $result = [];
        foreach ($codes_data["set"] as $k => $code) {
            $result[$k]["code"] = $code["code"];
            $result[$k]["code_id"] = $code["code_id"];

            $result[$k]["title"] = $code["title"];
            $result[$k]["description"] = $code["description"];
            
            $result[$k]["generated"] = ilDatePresentation::formatDate(new ilDateTime($code["generated"], IL_CAL_UNIX));

            $result[$k]["use_limit"] = empty($code["use_limit"]) ? $this->lng->txt('reg_code_use_unlimited') : $code["use_limit"];
            $result[$k]["use_count"] = $code["use_count"];


            $logins = array();
            foreach (explode(";", $code["notification_users"]) as $id) {
                if ($login = ilObjUser::_lookupLogin((int) trim($id))) {
                    $logins[] = $login;
                }
            }

            $result[$k]["login_generation_type"] = $login_types[$code["login_generation_type"]];
            $result[$k]["password_generation"] = $code["password_generation"] ? $this->lng->txt('yes') : $this->lng->txt('no');
            $result[$k]["captcha_required"] = $code["captcha_required"] ? $this->lng->txt('yes') : $this->lng->txt('no');
            $result[$k]["email_verification"] = $code["email_verification"] ? $this->lng->txt('yes') : $this->lng->txt('no');
            $result[$k]["email_verification_time"] = $code["email_verification_time"];
            $result[$k]["notification_logins"] = implode(", ", $logins);
            // fau.
            if ($code["used"]) {
                $result[$k]["used"] = ilDatePresentation::formatDate(new ilDateTime($code["used"], IL_CAL_UNIX));
            } else {
                $result[$k]["used"] = "";
            }

            if ($code["role"]) {
                $result[$k]["role"] = $this->role_map[$code["role"]] ?? $this->lng->txt('deleted');
            } else {
                $result[$k]["role"] = "";
            }

            if ($code["role_local"]) {
                $local = [];
                foreach (explode(";", $code["role_local"]) as $role_id) {
                    $role = ilObject::_lookupTitle((int) $role_id);
                    if ($role) {
                        $local[] = $role;
                    }
                }
                if (count($local)) {
                    sort($local);
                    $result[$k]["role_local"] = implode("<br />", $local);
                }
            } else {
                $result[$k]["role_local"] = "";
            }

            if ($code["alimit"]) {
                switch ($code["alimit"]) {
                    case "unlimited":
                        $result[$k]["alimit"] = $this->lng->txt("reg_access_limitation_none");
                        break;

                    case "absolute":
                        $result[$k]["alimit"] = $this->lng->txt("reg_access_limitation_mode_absolute_target") .
                            ": " . ilDatePresentation::formatDate(new ilDate($code["alimitdt"], IL_CAL_DATE));
                        break;

                    case "relative":
                        $limit_caption = [];
                        $limit = unserialize($code["alimitdt"], ['allowed_classes' => false]);
                        if ((int) $limit["d"]) {
                            $limit_caption[] = (int) $limit["d"] . " " . $this->lng->txt("days");
                        }
                        if ((int) $limit["m"]) {
                            $limit_caption[] = (int) $limit["m"] . " " . $this->lng->txt("months");
                        }
                        if ((int) $limit["y"]) {
                            $limit_caption[] = (int) $limit["y"] . " " . $this->lng->txt("years");
                        }
                        if (count($limit_caption)) {
                            $result[$k]["alimit"] = $this->lng->txt("reg_access_limitation_mode_relative_target") .
                                ": " . implode(", ", $limit_caption);
                        }
                        break;
                }
            }
        }

        $this->setMaxCount((int) $codes_data["cnt"]);
        $this->setData($result);
    }

    /**
     * Init filter
     */
    public function initFilter(): void
    {
        // code
        $ti = new ilTextInputGUI($this->lng->txt("registration_code"), "query");
        $ti->setMaxLength(ilRegistrationCode::CODE_LENGTH);
        $ti->setSize(20);
        $ti->setSubmitFormOnEnter(true);
        $this->addFilterItem($ti);
        $ti->readFromSession();
        $this->filter["code"] = $ti->getValue();

        // role

        $this->role_map = [];
        foreach ($this->rbacreview->getGlobalRoles() as $role_id) {
            if (!in_array($role_id, [SYSTEM_ROLE_ID, ANONYMOUS_ROLE_ID], true)) {
                $this->role_map[$role_id] = ilObject::_lookupTitle($role_id);
            }
        }

        $options = ["" => $this->lng->txt("registration_roles_all")] +
            $this->role_map;
        $si = new ilSelectInputGUI($this->lng->txt("role"), "role");
        $si->setOptions($options);
        $this->addFilterItem($si);
        $si->readFromSession();
        $this->filter["role"] = $si->getValue();

        // access limitation
        $options = [
            "" => $this->lng->txt("registration_codes_access_limitation_all"),
            "unlimited" => $this->lng->txt("reg_access_limitation_none"),
            "absolute" => $this->lng->txt("reg_access_limitation_mode_absolute"),
            "relative" => $this->lng->txt("reg_access_limitation_mode_relative")
        ];
        $si = new ilSelectInputGUI($this->lng->txt("reg_access_limitations"), "alimit");
        $si->setOptions($options);
        $this->addFilterItem($si);
        $si->readFromSession();
        $this->filter["alimit"] = $si->getValue();

        // generated
        $options = ["" => $this->lng->txt("registration_generated_all")];
        foreach (ilRegistrationCode::getGenerationDates() as $date) {
            $options[$date] = ilDatePresentation::formatDate(new ilDateTime($date, IL_CAL_UNIX));
        }
        $si = new ilSelectInputGUI($this->lng->txt("registration_generated"), "generated");
        $si->setOptions($options);
        $this->addFilterItem($si);
        $si->readFromSession();
        $this->filter["generated"] = $si->getValue();
    }

    /* fau: regCodes
    public function getSelectedColumns(): array
    {
        return [
            "code" => "registration_code",
            "role" => "registration_codes_roles",
            "role_local" => "registration_codes_roles_local",
            "alimit" => "reg_access_limitations",
            "generated" => "registration_generated",
            "used" => "registration_used"
        ];
    }
    */ 

    // fau: regCodes - define selectable columns
    public function getSelectableColumns(): array
    {
        return array(
            'code' => array(
                'txt' => $this->lng->txt("registration_code"),
                'default' => true
            ),
            'title' => array(
                'txt' => $this->lng->txt("title"),
                'default' => true
            ),
            'generated' => array(
                'txt' => $this->lng->txt("registration_generated"),
                'default' => true
            ),
            'use_limit' => array(
                'txt' => $this->lng->txt("reg_code_use_limit"),
                'default' => true
            ),
            'use_count' => array(
                'txt' => $this->lng->txt("reg_code_use_count"),
                'default' => true
            ),
            'used' => array(
                'txt' => $this->lng->txt("reg_code_last_used"),
                'default' => true
            ),
            'role' => array(
                'txt' => $this->lng->txt("registration_codes_roles"),
                'default' => true
            ),
            'role_local' => array(
                'txt' => $this->lng->txt("registration_codes_roles_local"),
                'default' => true
            ),
            'alimit' => array(
                'txt' => $this->lng->txt("reg_access_limitations"),
                'default' => true
            ),
            'description' => array(
                'txt' => $this->lng->txt("description"),
                'default' => false
            ),
            'login_generation_type' => array(
                'txt' => $this->lng->txt("reg_login_generation_type"),
                'default' => false
            ),
            'password_generation' => array(
                'txt' => $this->lng->txt("passwd_generation"),
                'default' => false
            ),/*
            'captcha_required' => array(
                'txt' => $this->lng->txt("adm_captcha_anonymous_short"),
                'default' => false
            ),*/
            'email_verification' => array(
                'txt' => $this->lng->txt("reg_type_confirmation"),
                'default' => false
            ),
            'email_verification_time' => array(
                'txt' => $this->lng->txt("reg_confirmation_hash_life_time"),
                'default' => false
            ),
            'notification_logins' => array(
                'txt' => $this->lng->txt("reg_notification"),
                'default' => false
            ),
        );
    }
    // fau.    

    // fau: regCodes - show selected columns and edit link
    protected function fillRow(array $a_set): void
    {
        /** @var ilCtrl $ilCtrl */
        global $ilCtrl;

        $this->tpl->setVariable("ID", $a_set["code_id"]);
        foreach (array_keys($this->getSelectedColumns()) as $c) {
            $this->tpl->setCurrentBlock('column');
            $this->tpl->setVariable("VAL", $a_set[$c] ?? "" . ' ');
            $this->tpl->parseCurrentBlock();
        }

        $ilCtrl->setParameter($this->parent_obj, 'code', $a_set['code']);
        $this->tpl->setVariable('LINK_EDIT', $ilCtrl->getLinkTarget($this->parent_obj, 'editCode'));
        $this->tpl->setVariable('TXT_EDIT', $this->lng->txt('edit'));
    }
    // fau.

}
