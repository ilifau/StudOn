<?php declare(strict_types=1);

namespace FAU\Campo;

use FAU\RecordRepo;
use FAU\Campo\Data\Module;
use FAU\Campo\Data\CourseOfStudy;
use FAU\Campo\Data\ModuleCos;

/**
 * Repository for accessing FAU user data
 */
class Repository extends RecordRepo
{
    public function saveModule(Module $record)
    {
        $this->replaceRecord($record);
    }
    public function deleteModule(Module $record)
    {
        $this->deleteRecord($record);
    }

    public function saveCos(CourseOfStudy $record)
    {
        $this->replaceRecord($record);
    }
    public function deleteCos(CourseOfStudy $record)
    {
        $this->deleteRecord($record);
    }

    public function saveModuleCos(ModuleCos $record)
    {
        $this->replaceRecord($record);
    }
    public function deleteModuleCos(ModuleCos $record)
    {
        $this->deleteRecord($record);
    }


}