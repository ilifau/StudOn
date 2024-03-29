<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * Used for container export with tests
 *
 * @author Stefan Meyer <meyer@leifos.com>
 */
class ilSurveyExporter extends ilXmlExporter
{
    /**
     * @var ilSurveyDataSet
     */
    private $ds;

    /**
     * Initialisation
     */
    public function init()
    {
        $this->ds = new ilSurveyDataSet();
        $this->ds->setExportDirectories($this->dir_relative, $this->dir_absolute);
        $this->ds->setDSPrefix("ds");
    }


    /**
     * Get xml representation
     *
     * @param string $a_enitity entity
     * @param string $a_target_release target release
     * @param string $a_id id
     * @return string xml string
     */
    public function getXmlRepresentation($a_entity, $a_schema_version, $a_id)
    {
        if ($a_entity == "svy") {
            $svy = new ilObjSurvey($a_id, false);
            $svy->loadFromDb();

            $svy_exp = new ilSurveyExport($svy, 'xml');
            $zip = $svy_exp->buildExportFile();

            // Unzip, since survey deletes this dir
            ilUtil::unzip($zip);

            // unzip does not extract the included directory
            // Modules/Survey/set_1 anymore (since 7/2023)
            $missing = $svy_exp->export_dir . "/" . $svy_exp->subdir .
                "/Modules/Survey/set_1";
            ilUtil::makeDirParents($missing);

            // here: svy_data/svy_301/export/1698817474__0__svy_301
            //       svy_301/export/1698817474__0__svy_301/Modules/Survey/set_1
            //       svy_data/svy_301/export/1698817474__0__svy_301.zip
            $GLOBALS['ilLog']->write(__METHOD__ . ': Created zip file ' . $zip);
            return "";
        } else {
            return $this->ds->getXmlRepresentation($a_entity, $a_schema_version, $a_id, "", true, true);
        }
    }

    /**
     * Get tail dependencies
     *
     * @param string $a_enitity entity
     * @param string $a_target_release target release
     * @param array $a_ids ids
     * @return array array of array with keys "component", entity", "ids"
     */
    public function getXmlExportTailDependencies($a_entity, $a_target_release, $a_ids)
    {
        if ($a_entity == "svy") {
            return array(
                    array(
                            "component" => "Modules/Survey",
                            "entity" => "svy_quest_skill",
                            "ids" => $a_ids),
                    array(
                            "component" => "Modules/Survey",
                            "entity" => "svy_skill_threshold",
                            "ids" => $a_ids),
                    array(
                            "component" => "Services/Object",
                            "entity" => "common",
                            "ids" => $a_ids)
            );
        }
        return array();
    }

    /**
     * Returns schema versions that the component can export to.
     * ILIAS chooses the first one, that has min/max constraints which
     * fit to the target release. Please put the newest on top.
     *
     * @return
     */
    public function getValidSchemaVersions($a_entity)
    {
        if ($a_entity == "svy") {
            return array(
                    "4.1.0" => array(
                            "namespace" => "http://www.ilias.de/Modules/Survey/htlm/4_1",
                            "xsd_file" => "ilias_svy_4_1.xsd",
                            "uses_dataset" => false,
                            "min" => "4.1.0",
                            "max" => "")
            );
        } else {
            return array(
                    "5.1.0" => array(
                            "namespace" => "http://www.ilias.de/Modules/Survey/svy/5_1",
                            "xsd_file" => "ilias_svy_5_1.xsd",
                            "uses_dataset" => true,
                            "min" => "5.1.0",
                            "max" => "")
            );
        }
    }
}
