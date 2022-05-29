<?php

namespace FAU\Sync;


use ILIAS\DI\Container;
use FAU\Staging\Data\DipData;
use FAU\Campo\Data\Module;
use FAU\Campo\Data\ModuleCos;
use FAU\Campo\Data\CourseOfStudy;
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
    }

    /**
     * Synchronize data found in the staging table campo_module
     * todo: treat event relationship
     */
    public function syncCampoModule()
    {
        foreach ($this->dic->fau()->staging()->repo()->getModulesToDo() as $record) {
            $module = (new Module())
                ->withModuleId($record->getModuleId())
                ->withModuleNr($record->getModuleNr())
                ->withModuleName($record->getModuleName());

            switch ($record->getDipStatus()) {
                case DipData::INSERTED:
                case DipData::CHANGED:
                    $this->dic->fau()->campo()->repo()->saveModule($module);
                    break;
                case DipData::DELETED:
                    $this->dic->fau()->campo()->repo()->deleteModule($module);
                    break;
            }
            //$this->dic->fau()->staging()->repo()->setModuleDone($record);
        }
    }

    /**
     * Synchronize data found in the staging table campo_module_cos
     */
    public function syncCampoModuleCos()
    {
        foreach ($this->dic->fau()->staging()->repo()->getModuleCosToDo() as $record) {
            $cos = (new CourseOfStudy())
                ->withCosId($record->getCosId())
                ->withDegree($record->getDegree())
                ->withSubject($record->getSubject())
                ->withMajor($record->getMajor())
                ->withSubjectIndicator($record->getSubjectIndicator())
                ->withVersion($record->getVersion());

            $moduleCos = (new ModuleCos())
                ->withCosId($record->getCosId())
                ->withModuleId($record->getModuleId());

            switch ($record->getDipStatus()) {
                case DipData::INSERTED:
                case DipData::CHANGED:
                    $this->dic->fau()->campo()->repo()->saveCos($cos);
                    $this->dic->fau()->campo()->repo()->saveModuleCos($moduleCos);
                    break;
                case DipData::DELETED:
                    $this->dic->fau()->campo()->repo()->deleteModuleCos($moduleCos);
                    break;
            }
            //$this->dic->fau()->staging()->repo()->setModuleCosDone($record);
        }
    }

    /**
     * Synchronize data found in the staging table campo_specific_educations
     */
    public function syncEducations()
    {
        foreach ($this->dic->fau()->staging()->repo()->getEducationsToDo() as $record) {

            if (!empty($user_id = $this->dic->fau()->user()->findUserIdByIdmUid($record->getIdmUid()))) {
                $education = (new Education())
                    ->withUserId($user_id)
                    ->withType($record->getType())
                    ->withKey($record->getKey())
                    ->withValue($record->getValue())
                    ->withKeyTitle($record->getKeyTitle())
                    ->withValueText($record->getValueText());

                switch ($record->getDipStatus()) {
                    case DipData::INSERTED:
                    case DipData::CHANGED:
                        $this->dic->fau()->user()->repo()->saveEducation($education);
                        break;
                    case DipData::DELETED:
                        $this->dic->fau()->user()->repo()->deleteEducation($education);
                        break;
                }
                //$this->dic->fau()->staging()->repo()->setModuleDone($record);

            }
        }
    }
}

