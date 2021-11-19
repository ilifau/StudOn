<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Importer class for forums
 *
 * @author Stefan Meyer <meyer@leifos.com>
 * @version $Id: $
 * @ingroup ModulesForum
 */
class ilForumImporter extends ilXmlImporter implements ilForumObjectConstants
{
    /**
     * Import XML
     *
     * @param
     * @return
     */
    public function importXmlRepresentation($a_entity, $a_id, $a_xml, $a_mapping)
    {
        // case i container
        if ($new_id = $a_mapping->getMapping('Services/Container', 'objs', $a_id)) {
            $newObj = ilObjectFactory::getInstanceByObjId($new_id, false);
        } else {	// case ii, non container
            $newObj = new ilObjForum();
            $newObj->setType('frm');
            $newObj->create();
        }

        $parser = new ilForumXMLParser($newObj, $a_xml, $a_mapping);
        $parser->setImportDirectory($this->getImportDirectory());
        $parser->setImportInstallId($this->getInstallId());
        $parser->setSchemaVersion($this->getSchemaVersion());
        $parser->startParsing();

        $a_mapping->addMapping("Modules/Forum", "frm", $a_id, $newObj->getId());
    }

    public function finalProcessing($a_mapping) : void
    {
        parent::finalProcessing($a_mapping);

        $copaMap = $a_mapping->getMappingsOfEntity('Services/COPage', 'pg');
        foreach ($copaMap as $oldCopaId => $newCopaId) {
            $newCopaId = substr($newCopaId, strlen(self::OBJ_TYPE) + 1);
            ilForumPage::_writeParentId(self::OBJ_TYPE, $newCopaId, $newCopaId);
        }

        $styleMapping = $a_mapping->getMappingsOfEntity('Modules/Forum', 'style');
        foreach ($styleMapping as $newForumId => $oldStyleId) {
            $newStyleId = (int) $a_mapping->getMapping('Services/Style', 'sty', $oldStyleId);
            if ($newForumId > 0 && $newStyleId > 0) {
                $frm = ilObjectFactory::getInstanceByObjId($newForumId, false);
                if (!$frm || !($frm instanceof ilObjForum)) {
                    continue;
                }
                ilForumProperties::getInstance($frm->getId())->writeStyleSheetId($newStyleId);
            }
        }
    }
}
