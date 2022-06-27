<?php

namespace FAU\Sync;


use ILIAS\DI\Container;
use FAU\Staging\Data\DipData;
use FAU\Study\Data\Module;
use FAU\Study\Data\ModuleCos;
use FAU\Study\Data\CourseOfStudy;
use FAU\User\Data\Education;
use FAU\Study\Data\ModuleEvent;
use FAU\User\Data\Achievement;
use FAU\Study\Data\CourseResponsible;
use FAU\Study\Data\Event;
use FAU\Study\Data\EventOrgunit;
use FAU\Study\Data\EventResponsible;
use FAU\Study\Data\IndividualDate;
use FAU\Study\Data\IndividualInstructor;
use FAU\Study\Data\Instructor;
use FAU\Cond\Data\Requirement;
use FAU\Cond\Data\ModuleRestriction;
use FAU\Study\Data\PlannedDate;
use FAU\Cond\Data\Restriction;
use FAU\Study\Data\DocProgramme;
use FAU\Study\Data\StudyDegree;
use FAU\Study\Data\StudyEnrolment;
use FAU\Study\Data\StudyField;
use FAU\Study\Data\StudyForm;
use FAU\Study\Data\StudySchool;
use FAU\Study\Data\StudySubject;
use FAU\Study\Data\Course;
use FAU\Study\Data\Term;

/**
 * Synchronisation of data coming from campo
 * This will update data of the Study, User and Cond services
 */
class SyncWithCampo extends SyncBase
{
    protected Container $dic;

    /**
     * Synchronize data (called by cron job)
     * Counted items are the campo courses
     */
    public function synchronize() : void
    {
        // value sources
        $this->syncDocProgrammes();
        $this->syncStudyDegrees();
        $this->syncStudyEnrolments();
        $this->syncStudyFields();
        $this->syncStudyForms();
        $this->syncStudySchools();
        $this->syncStudySubjects();

        // study structure
        // courses must be synced first, changes in other data may set the dirty status in the course data
        $this->syncCourses();
        $this->syncEvents();
        $this->syncEventOrgunits();
        $this->syncEventModules();
        $this->syncModuleCos();
        $this->syncPlannedDates();
        $this->syncIndividualDates();

        // conditions
        $this->syncModuleRestrictions();
        $this->syncRestrictions();

        // person assignments
        $this->syncEventResponsibles();
        $this->syncCourseResponsibles();
        $this->syncInstructors();
        $this->syncIndividualInstructors();
        $this->syncAchievements();
        $this->syncEducations();
    }

    /**
     * Synchronize the data found in the staging table campo_achievements
     * No change marks by DIP are available => all data has to be compared
     */
    protected function syncAchievements() : void
    {
        $this->info('syncAchievements...');

        // get all achievements from the staging database
        $stagingIds = [];
        foreach($this->staging->repo()->getAchievements() as $achievement) {
            $stagingIds[$achievement->getRequirementId()][$achievement->getPersonId()] = true;
        }

        // get all achievements from StudOn
        $studonIds = [];
        foreach($this->user->repo()->getAllAchievements() as $achievement) {
            $studonIds[$achievement->getRequirementId()][$achievement->getPersonId()] = true;
        }

        // treat added achievements (exist in staging, but not yet in StudOn)
        foreach ($stagingIds as $requirement_id => $person_ids) {
            foreach ($person_ids as $person_id => $flag) {
                if (!isset($studonIds[$requirement_id][$person_id])) {
                    $this->user->repo()->save(new Achievement($requirement_id, $person_id));
                }
            }
        }

        // treat deleted achievements (exist yet in StudOn, but no more in staging)
        foreach ($studonIds as $requirement_id => $person_ids) {
            foreach ($person_ids as $person_id => $flag) {
                if (!isset($stagingIds[$requirement_id][$person_id])) {
                    $this->user->repo()->delete(new Achievement($requirement_id, $person_id));
                }
            }
        }
    }


    /**
     * Synchronize data found in the staging table campo_course
     */
    protected function syncCourses() : void
    {
        $this->info('syncCourses...');
        foreach ($this->staging->repo()->getCoursesToDo() as $record) {
            $course = new Course(
                $record->getCourseId(),
                $record->getEventId(),
                $record->getTermYear(),
                $record->getTermTypeId(),
                $record->getKParallelgroupId(),
                $record->getTitle(),
                $record->getShorttext(),
                $record->getHoursPerWeek(),
                $record->getAttendeeMaximum(),
                $record->getCancelled(),
                $record->getTeachingLanguage(),
                $record->getCompulsoryRequirement(),
                $record->getContents(),
                $record->getLiterature(),
            );
            if ($existing = $this->study->repo()->getCourse($record->getCourseId())) {
                $course = $course
                    ->withIliasObjId($existing->getIliasObjId())
                    ->withIliasProblem($existing->getIliasProblem())
                    ->asChanged(true);
            }
            switch ($record->getDipStatus()) {
                case DipData::INSERTED:
                case DipData::CHANGED:
                    $this->study->repo()->save($course);
                    break;
                case DipData::DELETED:
                    $this->study->repo()->delete($course);
                    break;
            }
            $this->staging->repo()->setDipProcessed($record);
        }
    }

    /**
     * Synchronize data found in the staging table campo_course_responsible
     */
    protected function syncCourseResponsibles() : void
    {
        $this->info('syncCourseResponsibles...');
        foreach ($this->staging->repo()->getCourseResponsiblesToDo() as $record) {
            $responsible = new CourseResponsible(
                $record->getCourseId(),
                $record->getPersonId()
            );
            switch ($record->getDipStatus()) {
                case DipData::INSERTED:
                case DipData::CHANGED:
                    $this->study->repo()->save($responsible);
                    break;
                case DipData::DELETED:
                    $this->study->repo()->delete($responsible);
                    break;
            }
            // mark course as changed to trigger a role update in the related ILIAS course or group
            if ($course = $this->study->repo()->getCourse($record->getCourseId())) {
                $this->study->repo()->save($course->asChanged(true));
            }
            $this->staging->repo()->setDipProcessed($record);
        }
    }

    /**
     * Synchronize the data found in the staging table doc_programmes
     */
    protected function syncDocProgrammes() : void
    {
        $this->info('syncDocProgrammes...');

        $stagingRecords = $this->staging->repo()->getDocProgrammes();
        $studyRecords = $this->study->repo()->getDocProgrammes();

        // sync all staging records to get updates
        foreach ($stagingRecords as $record) {
            $this->study->repo()->save(new DocProgramme(
                $record->getProgCode(),
                $record->getProgText(),
                $record->getProgEndDate()
            ));
        }
        // delete records in StudOn that are no longer in staging
        foreach ($studyRecords as $record) {
            if (!isset($stagingRecords[$record->getProgCode()])) {
                $this->study->repo()->delete($record);
            }
        }
    }

    /**
     * Synchronize data found in the staging table campo_event
     */
    protected function syncEvents() : void
    {
        $this->info('syncEvents...');
        foreach ($this->staging->repo()->getEventsToDo() as $record) {
            $event = new Event(
                $record->getEventId(),
                $record->getEventtype(),
                $record->getTitle(),
                $record->getShorttext(),
                $record->getComment(),
                $record->getGuest()
            );
            switch ($record->getDipStatus()) {
                case DipData::INSERTED:
                case DipData::CHANGED:
                    $this->study->repo()->save($event);
                    break;
                case DipData::DELETED:
                    $this->study->repo()->delete($event);
                    break;
            }
            // set the related courses as changed to trigger an update
            foreach ($this->study->repo()->getCoursesOfEvent($record->getEventId()) as $course) {
                $this->study->repo()->save($course->asChanged(true));
            }
            $this->staging->repo()->setDipProcessed($record);
        }
    }

    /**
     * Synchronize data found in the staging table campo_event_orgunit
     */
    protected function syncEventOrgunits() : void
    {
        $this->info('syncEventOrgunits...');
        foreach ($this->staging->repo()->getEventOrgunitsToDo() as $record) {
            $eventUnit = new EventOrgunit(
                $record->getEventId(),
                $record->getFauorgNr(),
            );
            switch ($record->getDipStatus()) {
                case DipData::INSERTED:
                case DipData::CHANGED:
                    $this->study->repo()->save($eventUnit);
                    break;
                case DipData::DELETED:
                    $this->study->repo()->delete($eventUnit);
                    break;
            }
            $this->staging->repo()->setDipProcessed($record);
        }
    }

    /**
     * Synchronize data found in the staging table campo_event_responsible
     */
    protected function syncEventResponsibles() : void
    {
        $this->info('syncEventResponsibles...');
        foreach ($this->staging->repo()->getEventResponsiblesToDo() as $record) {
            $responsible = new EventResponsible(
                $record->getEventId(),
                $record->getPersonId()
            );
            switch ($record->getDipStatus()) {
                case DipData::INSERTED:
                case DipData::CHANGED:
                    $this->study->repo()->save($responsible);
                    break;
                case DipData::DELETED:
                    $this->study->repo()->delete($responsible);
                    break;
            }
            // set the related courses as changed to trigger an update
            foreach ($this->study->repo()->getCoursesOfEvent($record->getEventId()) as $course) {
                $this->study->repo()->save($course->asChanged(true));
            }
            $this->staging->repo()->setDipProcessed($record);
        }
    }


    /**
     * Synchronize data found in the staging table campo_module
     * This table combines the event to module relationship with the module data
     * The data is saved to different tables for the relationship and the module data
     * The records to do are provided in time order of their marked changes
     * Later changes will overwrite former changes
     * Modules don't have to be deleted
     */
    protected function syncEventModules() : void
    {
        $this->info('syncEventModules...');
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
                    $this->study->repo()->save($module);
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
     * Synchronize data found in the staging table campo_specific_educations
     * @todo: a new data scheme will be provided
     */
    protected function syncEducations() : void
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

    /**
     * Synchronize data found in the staging table campo_individual_dates
     */
    protected function syncIndividualDates() : void
    {
        $this->info('syncIndividualDates...');
        foreach ($this->staging->repo()->getIndividualDatesToDo() as $record) {
            $date = new IndividualDate(
                $record->getIndividualDatesId(),
                $record->getPlannedDatesId(),
                $record->getTermYear(),
                $record->getTermTypeId(),
                $record->getDate(),
                $record->getStarttime(),
                $record->getEndtime(),
                $record->getFamosCode(),
                $record->getComment(),
                $record->getCancelled()

            );
            switch ($record->getDipStatus()) {
                case DipData::INSERTED:
                case DipData::CHANGED:
                    $this->study->repo()->save($date);
                    break;
                case DipData::DELETED:
                    $this->study->repo()->delete($date);
                    break;
            }
            $this->staging->repo()->setDipProcessed($record);
        }
    }


    /**
     * Synchronize data found in the staging table campo_individual_instructor
     */
    protected function syncIndividualInstructors() : void
    {
        $this->info('syncIndividualInstructors...');
        foreach ($this->staging->repo()->getIndividualInstructorsToDo() as $record) {
            $instructor = new IndividualInstructor(
                $record->getIndividualDatesId(),
                $record->getPersonId()
            );
            switch ($record->getDipStatus()) {
                case DipData::INSERTED:
                case DipData::CHANGED:
                    $this->study->repo()->save($instructor);
                    break;
                case DipData::DELETED:
                    $this->study->repo()->delete($instructor);
                    break;
            }

            // mark course as changed to trigger a role update in the related ILIAS course or group
            if ($course = $this->study->repo()->getCourseOfIndividualDate($record->getIndividualDatesId())) {
                $this->study->repo()->save($course->asChanged(true));
            }
            $this->staging->repo()->setDipProcessed($record);
        }
    }

    /**
     * Synchronize data found in the staging table campo_instructor
     */
    protected function syncInstructors() : void
    {
        $this->info('syncInstructors...');
        foreach ($this->staging->repo()->getInstructorsToDo() as $record) {
            $instructor = new Instructor (
                $record->getPlannedDatesId(),
                $record->getPersonId()
            );
            switch ($record->getDipStatus()) {
                case DipData::INSERTED:
                case DipData::CHANGED:
                    $this->study->repo()->save($instructor);
                    break;
                case DipData::DELETED:
                    $this->study->repo()->delete($instructor);
                    break;
            }
            // mark course as changed to trigger a role update in the related ILIAS course or group
            if ($course = $this->study->repo()->getCourseOfPlannedDate($record->getPlannedDatesId())) {
                $this->study->repo()->save($course->asChanged(true));
            }

            $this->staging->repo()->setDipProcessed($record);
        }
    }

    /**
     * Synchronize data found in the staging table campo_module_cos
     * This table combines the module to course of study relationship with the course of study data
     * The records to do are provided in time order of their marked changes
     * Later changes will overwrite former changes
     * Courses of study don't have to be deleted
     */
    protected function syncModuleCos() : void
    {
        $this->info('syncModuleCos...');
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
                    $this->study->repo()->save($cos);
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
     * Synchronize data found in the staging table campo_module_restrictions
     * This table combines the module to requirement relationship with the requirements data
     * The records to do are provided in time order of their marked changes
     * Later changes will overwrite former changes
     * Requirements don't have to be deleted
     */
    protected function syncModuleRestrictions() : void
    {
        $this->info('syncModuleRestrictions...');
        foreach ($this->staging->repo()->getModuleRestrictionsToDo() as $record) {
            $requirement = new Requirement(
                $record->getRequirementId(),
                $record->getRequirementName()
            );
            $moduleRes = new ModuleRestriction(
                $record->getModuleId(),
                $record->getRestriction(),
                $record->getRequirementId(),
                $record->getCompulsory()
            );
            switch ($record->getDipStatus()) {
                case DipData::INSERTED:
                case DipData::CHANGED:
                    // restrictions regarding the study semester may have no real requirement
                    // in this case the requirement id is 0 and the requirement should not be saved
                    if ($requirement->getRequirementId() !== 0) {
                        $this->cond->repo()->save($requirement);
                    }
                    $this->cond->repo()->save($moduleRes);
                    break;
                case DipData::DELETED:
                    $this->study->repo()->delete($moduleRes);
                    break;
            }
            $this->staging->repo()->setDipProcessed($record);
        }
    }


    /**
     * Synchronize data found in the staging table campo_planned_dates
     */
    protected function syncPlannedDates() : void
    {
        $this->info('syncPlannedDates...');
        foreach ($this->staging->repo()->getPlannedDatesToDo() as $record) {
            $date = new PlannedDate(
                $record->getPlannedDatesId(),
                $record->getCourseId(),
                $record->getTermYear(),
                $record->getTermTypeId(),
                $record->getRhythm(),
                $record->getStarttime(),
                $record->getEndtime(),
                $record->getAcademicTime(),
                $record->getStartdate(),
                $record->getEnddate(),
                $record->getFamosCode(),
                $record->getExpectedAttendees(),
                $record->getComment()
            );
            switch ($record->getDipStatus()) {
                case DipData::INSERTED:
                case DipData::CHANGED:
                    $this->study->repo()->save($date);
                    break;
                case DipData::DELETED:
                    $this->study->repo()->delete($date);
                    break;
            }
            $this->staging->repo()->setDipProcessed($record);
        }
    }

    /**
     * Synchronize data found in the staging table campo_restrictions
     */
    protected function syncRestrictions() : void
    {
        $this->info('syncRestrictions...');
        foreach ($this->staging->repo()->getRestrictionsToDo() as $record) {
            $restriction = new Restriction(
                $record->getId(),
                $record->getRestriction(),
                $record->getType(),
                $record->getCompare(),
                $record->getNumber(),
                $record->getCompulsory()
            );
            switch ($record->getDipStatus()) {
                case DipData::INSERTED:
                case DipData::CHANGED:
                    $this->cond->repo()->save($restriction);
                    break;
                case DipData::DELETED:
                    $this->cond->repo()->delete($restriction);
                    break;
            }
            $this->staging->repo()->setDipProcessed($record);
        }
    }

    /**
     * Synchronize the data found in the staging table study_degrees
     */
    protected function syncStudyDegrees() : void
    {
        $this->info('syncStudyDegrees...');

        $stagingRecords = $this->staging->repo()->getStudyDegrees();
        $studyRecords = $this->study->repo()->getStudyDegrees();

        // sync all staging records to get updates
        foreach ($stagingRecords as $record) {
            $this->study->repo()->save(new StudyDegree(
                $record->getDegreeHisId(),
                $record->getDegreeUniquename(),
                $record->getDegreeTitle(),
                $record->getDegreeTitleEn()
            ));
        }
        // delete records in StudOn that are no longer in staging
        foreach ($studyRecords as $record) {
            if (!isset($stagingRecords[$record->getDegreeHisId()])) {
                $this->study->repo()->delete($record);
            }
        }
    }

    /**
     * Synchronize the data found in the staging table study_enrolments
     */
    protected function syncStudyEnrolments() : void
    {
        $this->info('syncStudyEnrolments...');

        $stagingRecords = $this->staging->repo()->getStudyEnrolments();
        $studyRecords = $this->study->repo()->getStudyEnrolments();

        // sync all staging records to get updates
        foreach ($stagingRecords as $record) {
            $this->study->repo()->save(new StudyEnrolment(
                $record->getEnrolmentId(),
                $record->getEnrolmentUniquename(),
                $record->getEnrolmentTitle(),
                $record->getEnrolmentTitleEn()
            ));
        }
        // delete records in StudOn that are no longer in staging
        foreach ($studyRecords as $record) {
            if (!isset($stagingRecords[$record->getEnrolmentId()])) {
                $this->study->repo()->delete($record);
            }
        }
    }

    /**
     * Synchronize the data found in the staging table study_fields
     */
    protected function syncStudyFields() : void
    {
        $this->info('syncStudyFields...');

        $stagingRecords = $this->staging->repo()->getStudyFields();
        $studyRecords = $this->study->repo()->getStudyFields();

        // sync all staging records to get updates
        foreach ($stagingRecords as $record) {
            $this->study->repo()->save(new StudyField(
                $record->getFieldId(),
                $record->getFieldUniquename(),
                $record->getFieldTitle(),
                $record->getFieldTitleEn()
            ));
        }
        // delete records in StudOn that are no longer in staging
        foreach ($studyRecords as $record) {
            if (!isset($stagingRecords[$record->getFieldId()])) {
                $this->study->repo()->delete($record);
            }
        }
    }

    /**
     * Synchronize the data found in the staging table study_forms
     */
    protected function syncStudyForms() : void
    {
        $this->info('syncStudyFields...');

        $stagingRecords = $this->staging->repo()->getStudyForms();
        $studyRecords = $this->study->repo()->getStudyForms();

        // sync all staging records to get updates
        foreach ($stagingRecords as $record) {
            $this->study->repo()->save(new StudyForm(
                $record->getFormId(),
                $record->getFormUniquename(),
                $record->getFormTitle(),
                $record->getFormTitleEn()
            ));
        }
        // delete records in StudOn that are no longer in staging
        foreach ($studyRecords as $record) {
            if (!isset($stagingRecords[$record->getFormId()])) {
                $this->study->repo()->delete($record);
            }
        }
    }

    /**
     * Synchronize the data found in the staging table study_schools
     */
    protected function syncStudySchools() : void
    {
        $this->info('syncStudySchools...');

        $stagingRecords = $this->staging->repo()->getStudySchools();
        $studyRecords = $this->study->repo()->getStudySchools();

        // sync all staging records to get updates
        foreach ($stagingRecords as $record) {
            $this->study->repo()->save(new StudySchool(
                $record->getSchoolHisId(),
                $record->getSchoolUniquename(),
                $record->getSchoolTitle(),
                $record->getSchoolTitleEn()
            ));
        }
        // delete records in StudOn that are no longer in staging
        foreach ($studyRecords as $record) {
            if (!isset($stagingRecords[$record->getSchoolHisId()])) {
                $this->study->repo()->delete($record);
            }
        }
    }


    /**
     * Synchronize the data found in the staging table study_subjects
     */
    protected function syncStudySubjects() : void
    {
        $this->info('syncStudySubjects...');

        $stagingRecords = $this->staging->repo()->getStudySubjects();
        $studyRecords = $this->study->repo()->getStudySubjects();

        // sync all staging records to get updates
        foreach ($stagingRecords as $record) {
            $this->study->repo()->save(new StudySubject(
                $record->getSubjectHisId(),
                $record->getSubjectUniquename(),
                $record->getSubjectTitle(),
                $record->getSubjectTitleEn()

            ));
        }
        // delete records in StudOn that are no longer in staging
        foreach ($studyRecords as $record) {
            if (!isset($stagingRecords[$record->getSubjectHisId()])) {
                $this->study->repo()->delete($record);
            }
        }
    }

}

