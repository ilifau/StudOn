<?php

namespace FAU\Sync;


use ILIAS\DI\Container;
use FAU\Staging\Data\DipData;
use FAU\Study\Data\Module;
use FAU\Study\Data\ModuleCos;
use FAU\Study\Data\CourseOfStudy;
use FAU\User\Data\Education;

class SyncWithCampo
{
    protected Container $dic;

    protected int $courses_added = 0;
    protected int $courses_updated = 0;
    protected int $courses_deleted = 0;

    protected array $errors = [];

    /**
     * Constructor
     */
    public function __construct(Container $dic)
    {
        $this->dic = $dic;
    }

    /**
     * Get the number of added course
     */
    public function getCoursesAdded() : int
    {
        return $this->courses_added;
    }

    /**
     * Get the number of updated course
     */
    public function getCoursesUpdated() : int
    {
        return $this->courses_updated;
    }

    /**
     * Get the number of deleted courses
     */
    public function getCoursesDeleted() : int
    {
        return $this->courses_deleted;
    }

    /**
     * Check if the call produced an error
     */
    public function hasErrors() : bool
    {
        return !empty($this->errors);
    }

    /**
     * Get a list of error messages
     */
    public function getErrors() : array
    {
        return $this->errors;
    }

    /**
     * Synchronize StudOn with campo
     * (called by cron job)
     */
    public function synchronize()
    {
        $this->syncCampoModule();
        $this->syncCampoModuleCos();
        $this->syncEducations();
    }

    /**
     * Synchronize data found in the staging table campo_module
     * todo: treat event relationship
     */
    protected function syncCampoModule()
    {
        $this->info('syncCampoModule...');
        $moduleDone=[];
        foreach ($this->dic->fau()->staging()->repo()->getModulesToDo() as $record) {
            $module = new Module(
                $record->getModuleId(),
                $record->getModuleNr(),
                $record->getModuleName()
            );
            switch ($record->getDipStatus()) {
                case DipData::INSERTED:
                case DipData::CHANGED:
                    if (!isset($moduleDone[$record->getModuleId()])) {
                        $this->dic->fau()->study()->repo()->save($module);
                    }
                    break;
                case DipData::DELETED:
                    if (!isset($moduleDone[$record->getModuleId()])) {
                        $this->dic->fau()->study()->repo()->delete($module);
                    }
                    break;
            }
            $moduleDone[$record->getModuleId()]=true;
            $this->dic->fau()->staging()->repo()->setDipProcessed($record);
        }
    }

    /**
     * Synchronize data found in the staging table campo_module_cos
     */
    protected function syncCampoModuleCos()
    {
        $this->info('syncCampoModuleCos...');
        $cosDone=[];
        foreach ($this->dic->fau()->staging()->repo()->getModuleCosToDo() as $record) {
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
                    if (!isset($cosDone[$record->getCosId()])) {
                        $this->dic->fau()->study()->repo()->save($cos);
                    }
                    $this->dic->fau()->study()->repo()->save($moduleCos);
                    break;
                case DipData::DELETED:
                    $this->dic->fau()->study()->repo()->delete($moduleCos);
                    break;
            }
            $cosDone[$record->getCosId()]=true;
            $this->dic->fau()->staging()->repo()->setDipProcessed($record);
        }
    }

    /**
     * Synchronize data found in the staging table campo_specific_educations
     */
    protected function syncEducations()
    {
        $this->info('syncEducations...');
        foreach ($this->dic->fau()->staging()->repo()->getEducationsToDo() as $record) {
            if (!empty($user_id = $this->dic->fau()->user()->findUserIdByIdmUid($record->getIdmUid()))) {
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
                        $this->dic->fau()->user()->repo()->save($education);
                        break;
                    case DipData::DELETED:
                        $this->dic->fau()->user()->repo()->delete($education);
                        break;
                }
                $this->dic->fau()->staging()->repo()->setDipProcessed($record);
            }
        }
    }

    /**
     * Add an info text to the console anf to the log
     */
    protected function info(?string $text)
    {
        if (!\ilContext::usesHTTP()) {
            echo $text . "\n";
        }
        $this->dic->logger()->fau()->info($text);
    }
}

