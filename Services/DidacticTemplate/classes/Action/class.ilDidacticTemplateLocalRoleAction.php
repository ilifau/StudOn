<?php

declare(strict_types=1);
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * represents a creation of local roles action
 * @author  Stefan Meyer <meyer@leifos.com>
 * @ingroup ServicesDidacticTemplates
 */
class ilDidacticTemplateLocalRoleAction extends ilDidacticTemplateAction
{
    private int $role_template_id = 0;

    public function __construct(int $a_action_id = 0)
    {
        parent::__construct($a_action_id);
    }

    public function getType(): int
    {
        return self::TYPE_LOCAL_ROLE;
    }

    public function setRoleTemplateId(int $a_role_template_id): void
    {
        $this->role_template_id = $a_role_template_id;
    }

    public function getRoleTemplateId(): int
    {
        return $this->role_template_id;
    }

    public function apply(): bool
    {     
        $source = $this->initSourceObject();

        // fau: updateDidacticTemplateRole - search existing roles and update their permissions instead of creating a new one
        global $DIC;

        $rbacreview = $DIC['rbacreview'];
        $rbacadmin = $DIC['rbacadmin'];

        $template_title = ilObject::_lookupTitle($this->getRoleTemplateId());
        $roles = [];
        foreach ($rbacreview->getLocalRoles($this->getRefId()) as $role_id) {
            $role_title = ilObject::_lookupTitle($role_id);
            //echo $role_title;
            if (substr($role_title, 0, strlen($template_title)) == $template_title) {
                $roles[] = new ilObjRole($role_id);
            }
        }
        //exit;
        if (empty($roles)) {
            $role = new ilObjRole();
            $role->setTitle(ilObject::_lookupTitle($this->getRoleTemplateId()) . '_' . $this->getRefId());
            $role->setDescription(ilObject::_lookupDescription($this->getRoleTemplateId()));
            $role->create();
            $rbacadmin->assignRoleToFolder($role->getId(), $source->getRefId(), "y");

            $roles[] = $role;
        }

        /** @var ilObjRole $role */
        foreach ($roles as $role) {
            ilLoggerFactory::getLogger('otpl')->info('Using rolt: ' . $this->getRoleTemplateId() . ' with title "' . ilObject::_lookupTitle($this->getRoleTemplateId()) . '". ');

            // Copy template permissions

            ilLoggerFactory::getLogger('otpl')->debug(
                'Copy role template permissions ' .
                'tpl_id: ' . $this->getRoleTemplateId() . ' ' .
                'parent: ' . ROLE_FOLDER_ID . ' ' .
                'role_id: ' . $role->getId() . ' ' .
                'role_parent: ' . $source->getRefId()
            );


            $rbacadmin->copyRoleTemplatePermissions(
                $this->getRoleTemplateId(),
                ROLE_FOLDER_ID,
                $source->getRefId(),
                $role->getId(),
                true
            );

            // Set permissions
            $ops = $rbacreview->getOperationsOfRole($role->getId(), $source->getType(), $source->getRefId());
            $rbacadmin->grantPermission($role->getId(), $ops, $source->getRefId());

            // change existing objects
            $protected = $rbacreview->isProtected($source->getRefId(), $role->getId());
            $role->changeExistingObjects(
                $source->getRefId(),
                $protected ? ilObjRole::MODE_PROTECTED_DELETE_LOCAL_POLICIES : ilObjRole::MODE_UNPROTECTED_DELETE_LOCAL_POLICIES,
                array('all')
            );
        }
        // fau.

        return true;
    }

    public function revert(): bool
    {
        // @todo: revert could delete the generated local role. But on the other hand all users
        // assigned to this local role would be deassigned. E.g. if course or group membership
        // is handled by didactic templates, all members would get lost.
        return false;
    }

    public function save(): int
    {
        if (!parent::save()) {
            return 0;
        }

        $query = 'INSERT INTO didactic_tpl_alr (action_id,role_template_id) ' .
            'VALUES ( ' .
            $this->db->quote($this->getActionId(), 'integer') . ', ' .
            $this->db->quote($this->getRoleTemplateId(), 'integer') . ' ' .
            ') ';
        $res = $this->db->manipulate($query);

        return $this->getActionId();
    }

    public function delete(): void
    {
        parent::delete();

        $query = 'DELETE FROM didactic_tpl_alr ' .
            'WHERE action_id = ' . $this->db->quote($this->getActionId(), 'integer');
        $this->db->manipulate($query);
    }

    public function toXml(ilXmlWriter $writer): void
    {
        $writer->xmlStartTag('localRoleAction');

        $il_id = 'il_' . IL_INST_ID . '_' . ilObject::_lookupType($this->getRoleTemplateId()) . '_' . $this->getRoleTemplateId();

        $writer->xmlStartTag(
            'roleTemplate',
            [
                'id' => $il_id
            ]
        );

        $exp = new ilRoleXmlExport();
        $exp->setMode(ilRoleXmlExport::MODE_DTPL);
        $exp->addRole($this->getRoleTemplateId(), ROLE_FOLDER_ID);
        $exp->write();
        $writer->appendXML($exp->xmlDumpMem(false));
        $writer->xmlEndTag('roleTemplate');
        $writer->xmlEndTag('localRoleAction');
    }

    public function read(): void
    {
        parent::read();
        $query = 'SELECT * FROM didactic_tpl_alr ' .
            'WHERE action_id = ' . $this->db->quote($this->getActionId(), 'integer');
        $res = $this->db->query($query);
        while ($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT)) {
            $this->setRoleTemplateId((int) $row->role_template_id);
        }
    }
}
