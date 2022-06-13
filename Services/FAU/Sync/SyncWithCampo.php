<?php

namespace FAU\Sync;


use ILIAS\DI\Container;
use FAU\Staging\Data\DipData;
use FAU\Study\Data\Module;
use FAU\Study\Data\ModuleCos;
use FAU\Study\Data\CourseOfStudy;
use FAU\User\Data\Education;
use FAU\Study\Data\ModuleEvent;

/**
 * Synchronisation of data coming from campo
 */
class SyncWithCampo extends syncBase
{
    protected Container $dic;

    /**
     * Synchronize data (called by cron job)
     * Counted items are the campo courses
     */
    public function synchronize() : void
    {
        $this->syncEventModules();
        $this->syncCampoModuleCos();
        $this->syncEducations();
    }

    /**
     * Synchronize data found in the staging table campo_module
     */
    protected function syncEventModules()
    {
        $this->info('syncEventModules...');
        $moduleSaved=[];
        foreach ($this->staging->repo()->getEventModulesToDo() as $record) {
            $module = new Module(
                $record->getModuleId(),
                $record->getModuleNr(),
                $record->getModuleName()
            );
            $moduleEvent = new ModuleEvent(
                $record->getModuleId(),
                $record->getEventId(),
            );
            switch ($record->getDipStatus()) {
                case DipData::INSERTED:
                case DipData::CHANGED:
                    if (!isset($moduleSaved[$record->getModuleId()])) {
                        $this->study->repo()->save($module);
                        $moduleSaved[$record->getModuleId()]=true;
                    }
                    $this->study->repo()->save($moduleEvent);
                    break;
                case DipData::DELETED:
                    $this->study->repo()->delete($moduleEvent);
                    break;
            }
            $this->staging->repo()->setDipProcessed($record);
        }
    }

    /**
     * Synchronize data found in the staging table campo_module_cos
     */
    protected function syncCampoModuleCos()
    {
        $this->info('syncCampoModuleCos...');
        $cosSaved=[];
        foreach ($this->staging->repo()->getModuleCosToDo() as $record) {
            $cos = new CourseOfStudy(
                $record->getCosId(),
                $record->getDegree(),
                $record->getSubject(),
                $record->getMajor(),
                $record->getSubjectIndicator(),
                $record->getVersion()
            );
            $moduleCos = new ModuleCos(
                $record->getModuleId(),
                $record->getCosId()
            );
            switch ($record->getDipStatus()) {
                case DipData::INSERTED:
                case DipData::CHANGED:
                    if (!isset($cosSaved[$record->getCosId()])) {
                        $this->study->repo()->save($cos);
                        $cosSaved[$record->getCosId()]=true;
                    }
                    $this->study->repo()->save($moduleCos);
                    break;
                case DipData::DELETED:
                    $this->study->repo()->delete($moduleCos);
                    break;
            }
            $this->staging->repo()->setDipProcessed($record);
        }
    }

    /**
     * Synchronize data found in the staging table campo_specific_educations
     */
    protected function syncEducations()
    {
        $this->info('syncEducations...');
        foreach ($this->staging->repo()->getEducationsToDo() as $record) {
            if (!empty($user_id = $this->user->findUserIdByIdmUid($record->getIdmUid()))) {
                $education = new Education(
                    $user_id,
                    $record->getType(),
                    $record->getKey(),
                    $record->getValue(),
                    $record->getKeyTitle(),
                    $record->getValueText()
                );
                switch ($record->getDipStatus()) {
                    case DipData::INSERTED:
                    case DipData::CHANGED:
                        $this->user->repo()->save($education);
                        break;
                    case DipData::DELETED:
                        $this->user->repo()->delete($education);
                        break;
                }
                $this->staging->repo()->setDipProcessed($record);
            }
        }
    }

}

