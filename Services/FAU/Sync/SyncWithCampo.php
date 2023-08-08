<?php

namespace FAU\Sync;


use ILIAS\DI\Container;
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
use FAU\Cond\Data\EventRestriction;
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
use FAU\Study\Data\EventType;

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
        $this->syncTerms();

        // study structure
        // courses must be synced first, changes in other data may set the dirty status in the course data
        $this->syncCourses();
        $this->syncEvents();
        $this->syncEventOrgunits();
        $this->syncEventTypes();
        $this->syncEventModules();
        $this->syncModuleCos();
        $this->syncPlannedDates();
        $this->syncIndividualDates();

        // conditions
        $this->syncModuleRestrictions();
        $this->syncEventRestrictions();
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
     * FULL SYNC
     */
    public function syncAchievements() : void
    {
        $this->info('syncAchievements...');
        $existing = $this->sync->repo()->getAllForSync(Achievement::model());

        foreach ($this->staging->repo()->getAchievements() as $record) {
            $entry = new Achievement(
                $record->getRequirementId(),
                $record->getPersonId()
            );
            if (!isset($existing[$entry->key()]) || $existing[$entry->key()]->hash() != $entry->hash()) {
                $this->study->repo()->save($entry);
            }
            // record is still needed
            unset($existing[$entry->key()]);
        }

        // delete existing records that are no longer needed
        foreach ($existing as $entry) {
            $this->user->repo()->delete($entry);
        }
    }

    /**
     * Synchronize data found in the staging table campo_course
     * FULL SYNC
     */
    public function syncCourses() : void
    {
        $this->info('syncCourses...');
        /** @var Course[] $existing */
        $existing = $this->sync->repo()->getAllForSync(Course::model());

        foreach ($this->staging->repo()->getCourses() as $record) {
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
                $record->getDeleted(),
                $record->getTeachingLanguage(),
                $record->getContents(),
                $record->getLiterature(),
                $record->getRecommendedRequirement(),
                $record->getLearningTarget(),
                $record->getTargetGroup(),
                $record->getContentsAll(),
                $record->getLiteratureAll(),
                $record->getRecommendedRequirementAll(),
                $record->getLearningTargetAll(),
                $record->getTargetGroupAll()
            );
            if (isset($existing[$course->key()])) {
                $old = $existing[$course->key()];
                $course = $course
                    ->withIliasObjId($old->getIliasObjId())
                    ->withIliasProblem($old->getIliasProblem())
                    ->withIliasDirtySince($old->getIliasDirtySince())
                    ->withTitleDirty($old->isTitleDirty())
                    ->withDescriptionDirty($old->isDescriptionDirty())
                    ->withEventTitleDirty($old->isEventTitleDirty())
                    ->withEventDescriptionDirty($old->isEventDescriptionDirty())
                    ->withMaximumDirty($old->isMaximumDirty())
                    ->withSendPassed($old->getSendPassed());

                if ($old->hash() != $course->hash()) {
                    if ($old->getTitle() != $course->getTitle()
                        || $old->getKParallelgroupId() != $course->getKParallelgroupId()) {
                        $course = $course->withTitleDirty(true);
                    }
                    if ($old->getHoursPerWeek() != $course->getHoursPerWeek()
                        || $old->getTeachingLanguage() != $course->getTeachingLanguage()) {
                        $course = $course->withDescriptionDirty(true);
                    }
                    if ($old->getAttendeeMaximum() != $course->getAttendeeMaximum()) {
                        $course = $course->withMaximumDirty(true);
                    }
            
                    // set the general update trigger
                    $this->study->repo()->save($course->asChanged(true));
                }
            } elseif (!$course->isDeleted()) {
                $this->study->repo()->save($course
                    ->withTitleDirty(true)
                    ->withDescriptionDirty(true)
                    ->withEventTitleDirty(true)
                    ->withEventDescriptionDirty(true)
                    ->withMaximumDirty(true)
                    ->asChanged(true));
            }
            // course is still needed
            unset($existing[$course->key()]);
        }

        // mark remaining existing courses as deleted
        // this will be processed in SyncWithIlias
        foreach ($existing as $course) {
            $this->study->repo()->save($course->withDeleted(true)->asChanged(true));
        }
    }

    /**
     * Synchronize data found in the staging table campo_course_responsible
     * FULL SYNC
     */
    public function syncCourseResponsibles() : void
    {
        $this->info('syncCourseResponsibles...');

        /** @var CourseResponsible[] $existing */
        $existing = $this->sync->repo()->getAllForSync(CourseResponsible::model());
        $touched = [];

        foreach ($this->staging->repo()->getCourseResponsibles() as $record) {
            $entry = new CourseResponsible(
                $record->getCourseId(),
                $record->getPersonId(),
                $record->getSortOrder()
            );

            if (!isset($existing[$entry->key()])) {
                $this->study->repo()->save($entry);
                $touched[$entry->getCourseId()] = true;
            } elseif ($existing[$entry->key()]->hash() != $entry->hash()) {
                // sort order is changed - no need to update the course
                $this->study->repo()->save($entry);
            }

            // record is still needed
            unset($existing[$entry->key()]);
        }

        // delete existing records that are no longer needed
        foreach ($existing as $entry) {
            $this->study->repo()->delete($entry);
            $touched[$entry->getCourseId()] = true;
        }

        // set the related courses as changed to trigger an update
        if (!empty($touched)) {
            foreach ($this->study->repo()->getCoursesByIds(array_keys($touched), false) as $course) {
                $this->study->repo()->save($course->asChanged(true));
            }
        }
    }

    /**
     * Synchronize the data found in the staging table doc_programmes
     * FULL SYNC
     */
    public function syncDocProgrammes() : void
    {
        $this->info('syncDocProgrammes...');
        $existing = $this->sync->repo()->getAllForSync(DocProgramme::model());

        foreach ($this->staging->repo()->getDocProgrammes() as $record) {
            $entry = new DocProgramme(
                $record->getProgCode(),
                $record->getProgText(),
                $record->getProgEndDate()
            );
            if (!isset($existing[$entry->key()]) || $existing[$entry->key()]->hash() != $entry->hash()) {
                $this->study->repo()->save($entry);
            }
            // record is still needed
            unset($existing[$entry->key()]);

        }

        // delete existing records that are no longer needed
        foreach ($existing as $entry) {
            $this->user->repo()->delete($entry);
        }
    }

    /**
     * Synchronize data found in the staging table campo_event
     * FULL SYNC
     */
    public function syncEvents() : void
    {
        $this->info('syncEvents...');

        /** @var Event[] $existing */
        $existing = $this->sync->repo()->getAllForSync(Event::model());
        $touched = [];
        $title_dirty = [];
        $description_dirty = [];

        foreach ($this->staging->repo()->getEvents() as $record) {
            $entry = new Event(
                $record->getEventId(),
                $record->getEventtype(),
                $record->getTitle(),
                $record->getShorttext(),
                $record->getComment(),
                $record->getGuest()
            );
            if (!isset($existing[$entry->key()])) {
                $this->study->repo()->save($entry);
                $touched[$entry->getEventId()] = true;
            }
            elseif ($existing[$entry->key()]->hash() != $entry->hash()) {
                $old = $existing[$entry->key()];
                if ($old->getTitle() != $entry->getTitle()) {
                    $title_dirty[$entry->getEventId()] = true;
                }
                if ($old->getEventtype() != $entry->getEventtype()
                    || $old->getShorttext() != $entry->getShorttext()) {
                    $description_dirty[$entry->getEventId()] = true;    
                }
                $this->study->repo()->save($entry);
                $touched[$entry->getEventId()] = true;
            }
            // record is still needed
            unset($existing[$entry->key()]);
        }

        // delete existing records that are no longer needed
        foreach ($existing as $entry) {
            $this->study->repo()->delete($entry);
            $touched[$entry->getEventId()] = true;
        }

        // set the related courses as changed to trigger an update
        if (!empty($touched)) {
            foreach ($this->study->repo()->getCoursesOfEvents(array_keys($touched), false) as $course) {
                if (isset($title_dirty[$course->getEventId()])) {
                    $course = $course->withEventTitleDirty(true);
                }
                if (isset($description_dirty[$course->getEventId()])) {
                    $course = $course->withEventDescriptionDirty(true);
                }
                $this->study->repo()->save($course->asChanged(true));
            }
        }
    }
    /**
     * Synchronize the list of distinct event types
     * FULL SYNC
     */

    public function syncEventTypes() : void
    {
        $this->info('syncEventTypes...');
        $existing = $this->sync->repo()->getAllForSync(EventType::model());

        foreach ($this->staging->repo()->getEventTypeValues() as $value) {
            $entry = new EventType($value, null, null);
            if (!isset($existing[$entry->key()])) {
                $this->study->repo()->save($entry);
            }
            // record is still needed
            unset($existing[$entry->key()]);
        }
        // delete existing records that are no longer needed
        foreach ($existing as $entry) {
            $this->study->repo()->delete($entry);
        }
    }


    /**
     * Synchronize data found in the staging table campo_event_orgunit
     * FULL SYNC
     */
    public function syncEventOrgunits() : void
    {
        $this->info('syncEventOrgunits...');
        $existing = $this->sync->repo()->getAllForSync(EventOrgunit::model());

        foreach ($this->staging->repo()->getEventOrgunits() as $record) {
            $entry = new EventOrgunit(
                $record->getEventId(),
                $record->getFauorgNr(),
                $record->getRelationId()
            );
            if (!isset($existing[$entry->key()]) || $existing[$entry->key()]->hash() != $entry->hash()) {
                $this->study->repo()->save($entry);
            }
            // record is still needed
            unset($existing[$entry->key()]);
        }
        // delete existing records that are no longer needed
        foreach ($existing as $entry) {
            $this->study->repo()->delete($entry);
        }
    }

    /**
     * Synchronize data found in the staging table campo_event_responsible
     * FULL SYNC
     */
    public function syncEventResponsibles() : void
    {
        $this->info('syncEventResponsibles...');

        /** @var EventResponsible[] $existing */
        $existing = $this->sync->repo()->getAllForSync(EventResponsible::model());
        $touched = [];

        foreach ($this->staging->repo()->getEventResponsibles() as $record) {
            $entry = new EventResponsible(
                $record->getEventId(),
                $record->getPersonId()
            );
            if (!isset($existing[$entry->key()])) {
                $this->study->repo()->save($entry);
                $touched[$entry->getEventId()] = true;
            }
            // record is still needed
            unset($existing[$entry->key()]);
        }

        // delete existing records that are no longer needed
        foreach ($existing as $entry) {
            $this->study->repo()->delete($entry);
            $touched[$entry->getEventId()] = true;
        }

        // set the related courses as changed to trigger an update
        if (!empty($touched)) {
            foreach ($this->study->repo()->getCoursesOfEvents(array_keys($touched), false) as $course) {
                $this->study->repo()->save($course->asChanged(true));
            }
        }
    }

    /**
     * Synchronize data found in the staging table campo_module
     * This table combines the event to module relationship with the module data
     * The data is saved to different tables for the relationship and the module data
     * FULL SYNC
     * Modules don't have to be deleted
     */
    public function syncEventModules() : void
    {
        $this->info('syncEventModules...');

        $existingMod = $this->sync->repo()->getAllForSync(Module::model());
        $existingModEv = $this->sync->repo()->getAllForSync(ModuleEvent::model());

        foreach ($this->staging->repo()->getEventModules() as $record) {
            $module = new Module(
                $record->getModuleId(),
                $record->getModuleNr(),
                $record->getModuleName()
            );
            $moduleEvent = new ModuleEvent(
                $record->getModuleId(),
                $record->getEventId(),
            );

            if (!isset($existingMod[$module->key()]) || $existingMod[$module->key()]->hash() != $module->hash()) {
                $this->study->repo()->save($module);
                $existingMod[$module->key()] = $module;
            }
            if (!isset($existingModEv[$moduleEvent->key()])) {
                $this->study->repo()->save($moduleEvent);
            }

            // module event relation is treated, so remove from the list
            unset($existingModEv[$moduleEvent->key()]);
        }

        // delete all remaining old module event relations
        foreach ($existingModEv as $moduleEvent) {
            $this->study->repo()->delete($moduleEvent);
        }
    }

    /**
     * Synchronize data of the staging table campo_specific_educations
     * FULL SYNC
     */
    public function syncEducations() : void
    {
        $this->info('syncEducations...');
        $existing = $this->sync->repo()->getAllForSync(Education::model());

        foreach ($this->staging->repo()->getEducations() as $record) {
            $entry = new Education(
                $record->getId(),
                $record->getSemester(),
                $record->getPersonId(),
                $record->getExamnr(),
                $record->getDateOfWork(),
                $record->getExamname(),
                $record->getGrade(),
                $record->getOrgunit(),
                $record->getAdditionalText()
            );
            if (!isset($existing[$entry->key()]) || $existing[$entry->key()]->hash() != $entry->hash()) {
                $this->study->repo()->save($entry);
            }
            // record is still needed
            unset($existing[$entry->key()]);
        }

        // delete existing records that are no longer needed
        foreach ($existing as $entry) {
            $this->study->repo()->delete($entry);
        }
    }

    /**
     * Synchronize data found in the staging table campo_individual_dates
     * FULL SYNC
     */
    public function syncIndividualDates() : void
    {
        $this->info('syncIndividualDates...');
        $existing = $this->sync->repo()->getAllForSync(IndividualDate::model());

        foreach ($this->staging->repo()->getIndividualDates() as $record) {
            $entry = new IndividualDate(
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
            if (!isset($existing[$entry->key()]) || $existing[$entry->key()]->hash() != $entry->hash()) {
                $this->study->repo()->save($entry);
            }
            // record is still needed
            unset($existing[$entry->key()]);
        }
        // delete existing records that are no longer needed
        foreach ($existing as $entry) {
            $this->study->repo()->delete($entry);
        }
    }

    /**
     * Synchronize data found in the staging table campo_individual_instructor
     * FULL SYNC
     */
    public function syncIndividualInstructors() : void
    {
        $this->info('syncIndividualInstructors...');

        /** @var IndividualInstructor[] $existing */
        $existing = $this->sync->repo()->getAllForSync(IndividualInstructor::model());
        $touched = [];

        foreach ($this->staging->repo()->getIndividualInstructors() as $record) {
            $entry = new IndividualInstructor(
                $record->getIndividualDatesId(),
                $record->getPersonId()
            );
            if (!isset($existing[$entry->key()])) {
                $this->study->repo()->save($entry);
                $touched[$entry->getIndividualDatesId()] = true;
            }
            // record is still needed
            unset($existing[$entry->key()]);
        }

        // delete existing records that are no longer needed
        foreach ($existing as $entry) {
            $this->study->repo()->delete($entry);
            $touched[$entry->getIndividualDatesId()] = true;
        }

        // set the related courses as changed to trigger an update
        if (!empty($touched)) {
            foreach ($this->study->repo()->getCoursesOfIndividualDates(array_keys($touched), false) as $course) {
                $this->study->repo()->save($course->asChanged(true));
            }
        }
    }

    /**
     * Synchronize data found in the staging table campo_instructor
     * FULL SYNC
     */
    public function syncInstructors() : void
    {
        $this->info('syncInstructors...');

        /** @var Instructor[] $existing */
        $existing = $this->sync->repo()->getAllForSync(Instructor::model());
        $touched = [];

        foreach ($this->staging->repo()->getInstructors() as $record) {
            $entry = new Instructor (
                $record->getPlannedDatesId(),
                $record->getPersonId()
            );
            if (!isset($existing[$entry->key()])) {
                $this->study->repo()->save($entry);
                $touched[$entry->getPlannedDatesId()] = true;
            }
            // record is still needed
            unset($existing[$entry->key()]);
        }

        // delete existing records that are no longer needed
        foreach ($existing as $entry) {
            $this->study->repo()->delete($entry);
            $touched[$entry->getPlannedDatesId()] = true;
        }

        // set the related courses as changed to trigger an update
        if (!empty($touched)) {
            foreach ($this->study->repo()->getCoursesOfPlannedDates(array_keys($touched), false) as $course) {
                $this->study->repo()->save($course->asChanged(true));
            }
        }
    }

    /**
     * Synchronize data found in the staging table campo_module_cos
     * This table combines the module to course of study relationship with the course of study data
     * FULL SYNC
     * Module to Course of Study relations are deleted if they no longer exist
     * Courses of study don't have to be deleted
     */
    public function syncModuleCos() : void
    {
        /** @var CourseOfStudy[] $workingCos */
        /** @var CourseOfStudy[] $existingCos */
        $workingCos = [];
        $existingCos = $this->sync->repo()->getAllForSync(CourseOfStudy::model());
        $existingModCos = $this->sync->repo()->getAllForSync(ModuleCos::model());

        $this->info('syncModuleCos...');
        foreach ($this->staging->repo()->getModuleCos() as $record) {
            $cos = new CourseOfStudy(
                $record->getCosId(),
                $record->getDegree(),
                $record->getSubject(),
                [$record->getMajor()],
                $record->getSubjectIndicator(),
                $record->getVersion()
            );

            if (!isset($workingCos[$cos->key()])) {
                $workingCos[$cos->key()] = $cos;
            } else {
                // cos may appear multiple times with different majors
                // update with added major in existing list
                $workingCos[$cos->key()] = $workingCos[$cos->key()]
                    ->withAddedMajor($record->getMajor());
            }

            $moduleCos = new ModuleCos(
                $record->getModuleId(),
                $record->getCosId()
            );
            if (!isset($existingModCos[$moduleCos->key()])) {
                $this->study->repo()->save($moduleCos);
            }
            // record is still needed
            unset($existingModCos[$moduleCos->key()]);
        }

        // delete existing records that are no longer needed
        foreach ($existingModCos as $moduleCos) {
            $this->study->repo()->delete($moduleCos);
        }

        // save the added or changed courses of study
        foreach ($workingCos as $cos) {
            if (!isset($existingCos[$cos->key()]) || $existingCos[$cos->key()]->hash() != $cos->hash()) {
                $this->study->repo()->save($cos);
            }
        }
    }

    /**
     * Synchronize data found in the staging table campo_module_restrictions
     * This table combines the module to requirement relationship with the requirements data
     * FULL SYNC
     * Requirements don't have to be deleted
     */
    public function syncModuleRestrictions() : void
    {
        $this->info('syncModuleRestrictions...');

        $existingReq = $this->sync->repo()->getAllForSync(Requirement::model());
        $existingRest = $this->sync->repo()->getAllForSync(ModuleRestriction::model());

        foreach ($this->staging->repo()->getModuleRestrictions() as $record) {
            if ($record->getRequirementId() != 0) {
                $requirement = new Requirement(
                    $record->getRequirementId(),
                    $record->getRequirementName()
                );
                // save only if changed, it may be repeated in staging loop
                if (!isset($existingReq[$requirement->key()]) || $existingReq[$requirement->key()]->hash() != $requirement->hash()) {
                    $this->study->repo()->save($requirement);
                    $existingReq[$requirement->key()] = $requirement;
                }
            }

            $moduleRest = new ModuleRestriction(
                $record->getModuleId(),
                $record->getRestriction(),
                $record->getRequirementId(),
                $record->getCompulsory()
            );
            if (!isset($existingRest[$moduleRest->key()]) || $existingRest[$moduleRest->key()]->hash() != $moduleRest->hash()) {
                $this->study->repo()->save($moduleRest);
            }
            // existing record is still needed, this occurs only once in staging loop
            unset($existingRest[$moduleRest->key()]);
        }

        // delete all remaining old module restrictions
        foreach ($existingRest as $moduleRest) {
            $this->cond->repo()->delete($moduleRest);
        }
    }

    /**
     * Synchronize data found in the staging table campo_module_restrictions
     * This table combines the module to requirement relationship with the requirements data
     * FULL SYNC
     * Requirements don't have to be deleted
     */
    public function syncEventRestrictions() : void
    {
        $this->info('syncEventRestrictions...');

        $existingReq = $this->sync->repo()->getAllForSync(Requirement::model());
        $existingRest = $this->sync->repo()->getAllForSync(EventRestriction::model());

        foreach ($this->staging->repo()->getEventRestrictions() as $record) {
            if ($record->getRequirementId() != 0) {
                $requirement = new Requirement(
                    $record->getRequirementId(),
                    $record->getRequirementName()
                );
                // save only if changed, it may be repeated in staging loop
                if (!isset($existingReq[$requirement->key()]) || $existingReq[$requirement->key()]->hash() != $requirement->hash()) {
                    $this->study->repo()->save($requirement);
                    $existingReq[$requirement->key()] = $requirement;
                }
            }

            $eventRest = new EventRestriction(
                $record->getEventId(),
                $record->getRestriction(),
                $record->getRequirementId(),
                $record->getCompulsory()
            );
            if (!isset($existingRest[$eventRest->key()]) || $existingRest[$eventRest->key()]->hash() != $eventRest->hash()) {
                $this->study->repo()->save($eventRest);
            }
            // existing record is still needed, this occurs only once in staging loop
            unset($existingRest[$eventRest->key()]);
        }

        // delete all remaining old module restrictions
        foreach ($existingRest as $eventRest) {
            $this->cond->repo()->delete($eventRest);
        }
    }

    /**
     * Synchronize data found in the staging table campo_planned_dates
     * FULL SYNC
     */
    public function syncPlannedDates() : void
    {
        $this->info('syncPlannedDates...');
        $existing = $this->sync->repo()->getAllForSync(PlannedDate::model());

        foreach ($this->staging->repo()->getPlannedDates() as $record) {
            $entry = new PlannedDate(
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
            if (!isset($existing[$entry->key()]) || $existing[$entry->key()]->hash() != $entry->hash()) {
                $this->study->repo()->save($entry);
            }
            // record is still needed
            unset($existing[$entry->key()]);
        }
        // delete existing records that are no longer needed
        foreach ($existing as $entry) {
            $this->study->repo()->delete($entry);
        }
    }

    /**
     * Synchronize data found in the staging table campo_restrictions
     * FULL SYNC
     */
    public function syncRestrictions() : void
    {
        $this->info('syncRestrictions...');
        $existing = $this->sync->repo()->getAllForSync(Restriction::model());

        foreach ($this->staging->repo()->getRestrictions() as $record) {
            $entry = new Restriction(
                $record->getId(),
                $record->getRestriction(),
                $record->getType(),
                $record->getCompare(),
                $record->getNumber(),
                $record->getCompulsory()
            );
            if (!isset($existing[$entry->key()]) || $existing[$entry->key()]->hash() != $entry->hash()) {
                $this->study->repo()->save($entry);
            }
            // record is still needed
            unset($existing[$entry->key()]);
        }
        // delete existing records that are no longer needed
        foreach ($existing as $entry) {
            $this->study->repo()->delete($entry);
        }
    }

    /**
     * Synchronize the data found in the staging table study_degrees
     * FULL SYNC
     */
    public function syncStudyDegrees() : void
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
     * FULL SYNC
     */
    public function syncStudyEnrolments() : void
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
     * FULL SYNC
     */
    public function syncStudyFields() : void
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
     * FULL SYNC
     */
    public function syncStudyForms() : void
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
     * FULL SYNC
     */
    public function syncStudySchools() : void
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
     * FULL SYNC
     */
    public function syncStudySubjects() : void
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

    /**
     * Synchronize the data found in the staging table study_terms
     * FULL SYNC
     */
    public function syncTerms() : void
    {
        $this->info('syncTerms...');
        $existing = $this->sync->repo()->getAllForSync(Term::model());

        foreach ($this->staging->repo()->getStudyTerms() as $record) {
            $entry = new Term(
                $record->getTermYear(),
                $record->getTermTypeId(),
                $record->getPeriodId()
            );
            if (!isset($existing[$entry->key()]) || $existing[$entry->key()]->hash() != $entry->hash()) {
                $this->study->repo()->save($entry);
            }
            // record is still needed
            unset($existing[$entry->key()]);
        }

        // delete existing records that are no longer needed
        foreach ($existing as $entry) {
            $this->user->repo()->delete($entry);
        }
    }
}

