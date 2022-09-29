<?php declare(strict_types=1);

namespace FAU\User;

use ilDatePresentation;
use FAU\SubService;
use FAU\User\Data\Member;
use FAU\Staging\Data\StudonChange;
use FAU\Study\Data\ImportId;

/**
 * Service for FAU user related data
 */
class Service extends SubService
{
    protected Repository $repository;

    /**
     * Get the repository for user data
     */
    public function repo() : Repository
    {
        if(!isset($this->repository)) {
            $this->repository = new Repository($this->dic->database(), $this->dic->logger()->fau());
        }
        return $this->repository;
    }


    /**
     * Get the educations of a user as text
     * An education is given as Title: Text
     * Educations are separated by newlines
     */
    public function getEducationsAsText(int $user_id) : string
    {
        $texts = [];
        if (!empty($person = $this->dic->fau()->user()->repo()->getPersonOfUser($user_id))) {
            foreach ($this->repo()->getEducationsOfPerson($person->getPersonId()) as $education) {
                $texts[] = $education->getOrgunit() . ': ' . $education->getExamname() . ' ('
                    . $education->getDateOfWork()
                    . (empty($education->getGrade()) ? '' : ', ' . $this->lng->txt('fau_grade') . ' ' . $education->getGrade())
                    . ')'
                    . (empty($education->getAdditionalText()) ? '' : ' - ' . $education->getAdditionalText());
            }
        }
        return implode("\n", $texts);
    }

    /**
     * Get the studies of a user as text
     * Studies are separated by newlines
     * @param int $user_id
     * @return string
     */
    public function getStudiesAsText(int $user_id) : string
    {
        if (empty($person = $this->repo()->getPersonOfUser($user_id))) {
            return '';
        }

        $texts = [];

        // Study data
        $studies = array_merge(
            $person->getStudiesOfTerm($this->dic->fau()->study()->getCurrentTerm()),
            $person->getStudiesOfTerm($this->dic->fau()->study()->getNextTerm())
        );

        if (empty($studies)) {
            $studies = $person->getStudiesOfTerm($person->getMaxTerm());
        }

//        if ($user_id == 28442) {
//            echo "<pre>";
//            echo "Current: ". $this->dic->fau()->study()->getCurrentTerm()->toString();
//            echo " Max: ". ($person->getMaxTerm() ? $person->getMaxTerm()->toString() : '');
//            echo " Person: ";
//            print_r($person);
//            exit;
//        }

        foreach ($studies as $study) {
            $text = $this->dic->fau()->study()->getReferenceTermText($study->getTerm());
            $text .= empty($study->getEnrollmentName()) ? '' : ' (' . $study->getEnrollmentName() . ')';
            $text .= ':';

            $subject_texts = [];
            $faculty_texts = [];
            foreach ($study->getSubjects() as $subject) {
                $subject_texts[] = $subject->getSubjectName() . ' [' . $subject->getSubjectId() .'] '
                .sprintf($this->lng->txt('studydata_semester_text'), $subject->getStudySemester());
                $faculty_texts[] = $subject->getFacultyName() . ' [' . $subject->getCalculatedSchoolId() . ']';
            }
            $text .= empty($subject_texts) ? '' : (" \n" . implode(', ', $subject_texts));
            $text .= empty($study->getDegreeName()) ? '' : (" \n" . $study->getDegreeName() . ' [' . $study->getDegreeId() .']');
            $text .= empty($faculty_texts) ? '' : (" \n" . implode(', ', array_unique($faculty_texts)));

            $texts[] = $text;
        }

        // Promotion data
        $text = '';
        if (!empty($person->getDocProgrammesText())) {
            $text = $person->getDocProgrammesText() . ' [' . $person->getDocProgrammesCode() . ']';
        }
        if (!empty($date = $person->getDocApprovalDateObject())) {
            if (empty($text)) {
                $text = $this->lng->txt('studydata_promotion');
            }
            $text .= ', ' . $this->lng->txt('studydata_promotion_approval') . ' ';
            $text .= ilDatePresentation::formatDate($date);
            $texts[] = $text;
        }

        return implode(" \n\n", $texts);
    }


    /**
     * Find the Id of a studOn user by the IDM id
     */
    public function findUserIdByIdmUid(string $idm_uid) : ?int
    {
       if ($id = \ilObjUser::_findUserIdByAccount($idm_uid)) {
           return (int) $id;
       }
       return null;
    }

    /**
     * Check if a user can delete courses or groups for campo courses
     */
    public function canDeleteObjectsForCourses(int $user_id)
    {
        // only system administrators
        return $this->dic->rbac()->system()->checkAccessOfUser($user_id, "visible", SYSTEM_FOLDER_ID);
    }

    /**
     * Save the membership of a user
     * - omit the module id, if en existiong should not be changed
     * - use 0 for the module if it should be deleted
     */
    public function saveMembership(int $obj_id, int $user_id, ?int $module_id = null)
    {
        $importId = ImportId::fromString(\ilObject::_lookupImportId($obj_id));
        $course_id = $importId->getCourseId();
        $person = $this->repo()->getPersonOfUser($user_id);
        $stagingRepo = $this->dic->fau()->staging()->repo();

        if (empty($course_id) || empty($person) || empty($stagingRepo)) {
            // not a relevant course or user or not connected
            return;
        }

        // get an existing member record
        $member = $this->repo()->getMember($obj_id, $user_id);

        $change = false;
        if (!isset($member)) {
            // new membership
            $change = true;
            $member = new Member($obj_id, $user_id);
        }
        elseif (isset($module_id) && $module_id != (int) $member->getModuleId()) {
            // module id should be changed or reset
            $change = true;
        }
        $member = $member->withModuleId($module_id == 0 ? null : $module_id);


        // changes should be saved
        if ($change) {
            $time = $this->dic->fau()->tools()->convert()->unixToDbTimestamp(time());

            // ensure that a change from registration page with module_id
            // is later than the change from the event handler without module_id
            if (isset($module_id)) {
                $time = $this->dic->fau()->tools()->convert()->unixToDbTimestamp(time() + 1);
            }

            if (!empty($person->getPersonId())) {
                $stagingRepo->saveChange(new StudonChange(
                    null,
                    $person->getPersonId(),
                    $course_id,
                    $member->getModuleId(),
                    StudonChange::TYPE_REGISTERED,
                    null,
                    $time,
                    $time,
                    null
                ));
            }

            $this->repo()->save($member);
        }
    }

    /**
     * Delete the membership of a user
     */
    public function deleteMembership(int $obj_id, int $user_id)
    {
        $importId = ImportId::fromString(\ilObject::_lookupImportId($obj_id));
        $course_id = $importId->getCourseId();
        $person = $this->repo()->getPersonOfUser($user_id);
        $stagingRepo = $this->dic->fau()->staging()->repo();

        if (empty($course_id) || empty($person) || empty($stagingRepo)) {
            // not a relevant course or user or not connected
            return;
        }

        $member = $this->repo()->getMember($obj_id, $user_id);

        // simple membership has been transmitted => delete
        if (isset($member) && !$member->hasAnyRole()) {

            $time = $this->dic->fau()->tools()->convert()->unixToDbTimestamp(time());

            if (!empty($person->getPersonId())) {
                $stagingRepo->saveChange(new StudonChange(
                    null,
                    $person->getPersonId(),
                    $course_id,
                    $member->getModuleId(),
                    StudonChange::TYPE_NOT_REGISTERED,
                    null,
                    $time,
                    $time,
                    null
                ));
            }

            $this->repo()->delete($member);
        }
    }
}