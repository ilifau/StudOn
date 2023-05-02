<?php declare(strict_types=1);

namespace FAU\User;

use ilDatePresentation;
use FAU\SubService;
use FAU\User\Data\Member;
use FAU\User\Data\Education;
use FAU\User\Data\Study;
use FAU\User\Data\Person;
use FAU\Study\Data\Term;
use FAU\User\Data\UserData;

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
     * Generate a unique login name
     */
    public function generateLogin(string $login_base) : string
    {
        $login = $login_base;
        $appendix = 0;
        while (\ilObjUser::_loginExists($login)) {
            $appendix++;
            $login = $login_base . $appendix;
        }
        return $login;
    }

    /**
     * Get errors found with the authentiction settings
     *
     * @return array    validation errors - empty if ok
     */
    public function getAuthSettingErrors(\ilObjUser $user) : array
    {
        $errors = [];
        if ($user->getAuthMode() == 'shibboleth' && empty($user->getExternalAccount())) {
            $errors[] = $this->lng->txt('user_error_shib_with_empty_ext_account');
        }

        if (($user->getAuthMode() == 'local' || $user->getAuthMode() == 'default')
            && !empty($user->getExternalAccount())) {
            $errors[] = $this->lng->txt('user_error_local_with_ext_account');
        }

        if ($user->getAuthMode() != 'local' && $user->getAuthMode() != 'default'
            && !empty($user->getIdleExtAccount())) {
            $errors[] = $this->lng->txt('user_error_idle_ext_account_not_local');
        }


        if (!empty($identifier = $user->getLogin())
            && !empty($login = $this->repo()->findLoginWithUsedIdentifier($identifier, (int) $user->getId()))
        ) {
            $errors[] = sprintf($this->lng->txt('user_error_identifier_is_used'), $identifier, $login);
        }
        elseif (!empty($identifier = $user->getExternalAccount())
            && !empty($login = $this->repo()->findLoginWithUsedIdentifier($identifier, (int) $user->getId()))
        ) {
            $errors[] = sprintf($this->lng->txt('user_error_identifier_is_used'), $identifier, $login);
        }
        elseif (!empty($identifier = $user->getIdleExtAccount())
            && !empty($login = $this->repo()->findLoginWithUsedIdentifier($identifier, (int) $user->getId()))
        ) {
            $errors[] = sprintf($this->lng->txt('user_error_identifier_is_used'), $identifier, $login);
        }

        return $errors;
    }


    /**
     * Get the data of users given by their ids
     * @param array    $user_ids
     * @param int|null $ref_id_for_educations
     * @return array
     */
    public function getUserData(array $user_ids, ?int $ref_id_for_educations) : array
    {
        $users = $this->repo()->getUserData($user_ids);
        $persons = $this->repo()->getPersonsOfUsers($user_ids);

        $persons_by_person_id = [];
        foreach ($persons as $user_id => $person) {
            if (!empty($user = $users[$user_id])) {
                $users[$user_id] = $user->withPerson($person);
            }
            if (!empty($person->getPersonId())) {
                $persons_by_person_id[$person->getPersonId()] = $person;
            }
        }

        $orgunits = null;
        if (!empty($ref_id_for_educations)) {
            $orgunits = $this->dic->fau()->org()->getLongtextsOnIliasPath($ref_id_for_educations);
        }
        $educations = $this->repo()->getEducationsOfPersons(array_keys($persons_by_person_id), $orgunits);
        foreach ($educations as $education) {
            if (!empty($person = $persons_by_person_id[$education->getPersonId()]) && !empty($user = $users[$person->getUserId()])) {
                $users[$user->getUserId()] = $user->withEducation($education);
            }
        }

        return $users;
    }

    /**
     * Get the user data of users with person_ids
     * This will not add person or education records
     * @param array $person_ids
     * @return UserData[]
     */
    public function getShortUserDataOfPersons(array $person_ids) : array
    {
        $users = $this->repo()->getUserDataOfPersons($person_ids);
        return $this->repo()->addPublicProfile($users);
    }


    /**
     * Get the textual representation of a user
     * If the person has a public profile and html is true, then the profile is linked
     */
    public function getUserText(UserData $user, bool $with_profile = true) : string
    {
        if ($with_profile) {
            $login = $user->getLogin();
            if ($user->hasPublicProfile()) {
                $pgui = new \ilPublicUserProfileGUI($user->getUserId());
                $pgui->setEmbedded(true);
                $content = $this->dic->ui()->factory()->legacy($pgui->getHTML());
                $popover = $this->dic->ui()->factory()->popover()->standard($content)->withResetSignals();
                $button = $this->dic->ui()->factory()->button()->shy($login, '#')
                                   ->withResetTriggeredSignals()
                                   ->withOnClick($popover->getShowSignal());
                $login = $this->dic->ui()->renderer()->render([$popover, $button]);
            }
            return $user->getFirstname() . ' ' . $user->getLastname(). ' (' . $login . ')';
        }
        else {
            return $user->getFirstname() . ' ' . $user->getLastname();
        }
    }


    /**
     * Get a textual representation of educations
     * @param Education[] $educations
     */
    public function getEducationsText(array $educations) : string
    {
        $texts = [];
        foreach ($educations as $education) {
            $texts[$education->getOrgunit()][] = $education->getExamname()
                . ' (' . $education->getDateOfWork()
                . (empty($education->getGrade()) ? '' : ', ' . $this->lng->txt('fau_grade') . ' ' . $education->getGrade())
                . ')'
                . (empty($education->getAdditionalText()) ? '' : ' - ' . $education->getAdditionalText());
        }

        $alltexts = [];
        foreach ($texts as $orgunit => $unittexts) {
            sort($unittexts);
            $alltexts[] = (count($texts) > 1 ? $orgunit . ": \n" : '')
                . implode("\n", $unittexts);
        }

        return implode("\n", $alltexts);
    }


    /**
     * Get the educations of a user as text
     * An education is given as Title: Text
     * Educations are separated by newlines
     * @param int $user_id
     * @param int|null $ref_id  filter educations from orgunits along the ilias path to the ref_id
     * @return string
     */
    public function getEducationsAsText(int $user_id, ?int $ref_id = null) : string
    {
        if (!empty($person = $this->dic->fau()->user()->repo()->getPersonOfUser($user_id))) {
            $orgunits = null;
            if (!empty($ref_id)) {
                $orgunits = $this->dic->fau()->org()->getLongtextsOnIliasPath($ref_id);
            }
            return $this->getEducationsText($this->repo()->getEducationsOfPerson($person->getPersonId(), $orgunits));
         }
        return '';
    }

    /**
     * Get a textual representation of a person's studies
     * Studies are separated by newlines
     */
    public function getStudiesText(?Person $person = null, ?Term $term = null) : string
    {
        if (empty($person)) {
            return '';
        }

        // Study data
        if (isset($term)) {
            $studies = $person->getStudiesOfTerm($term);
        }
        else {
            $studies = array_merge(
                $person->getStudiesOfTerm($this->dic->fau()->study()->getCurrentTerm()),
                $person->getStudiesOfTerm($this->dic->fau()->study()->getNextTerm())
            );
        }
        if (empty($studies)) {
            $studies = $person->getStudiesOfTerm($person->getMaxTerm());
        }

        $texts = [];
        foreach ($studies as $study) {
            $text = $this->dic->fau()->study()->getReferenceTermText($study->getTerm());
            $text .= empty($study->getEnrollmentName()) ? '' : ' (' . $study->getEnrollmentName() . ')';
            $text .= ':';

            $subject_texts = [];
            $faculty_texts = [];
            foreach ($study->getSubjects() as $subject) {
                $subject_texts[] = $subject->getSubjectName() . ' [' . $subject->getSubjectId() .'] '
                    .sprintf($this->lng->txt('studydata_semester_text'), $subject->getStudySemester())
                    . (empty($subject->getClinicalSemester()) ? '' : ', ' . sprintf($this->lng->txt('studydata_clinical_semester_text'), $subject->getClinicalSemester()));
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
     * Get the studies of a user as text
     * Studies are separated by newlines
     */
    public function getStudiesAsText(int $user_id) : string
    {
        return $this->getStudiesText($this->repo()->getPersonOfUser($user_id));
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
     * - omit the module id, if an existing should not be changed
     * - use 0 for the module if it should be deleted
     */
    public function saveMembership(int $obj_id, int $user_id, ?int $module_id = null, bool $force = false)
    {
        // these queries are cached
        $course_id = $this->dic->fau()->study()->repo()->getImportId($obj_id)->getCourseId();
        $person = $this->repo()->getPersonOfUser($user_id);
        $stagingRepo = $this->dic->fau()->staging()->repo();

        if (empty($course_id) || empty($person) || empty($stagingRepo)) {
            // not a relevant course or user or not connected
            return;
        }

        // get an existing member record
        $member = $this->repo()->getMember($obj_id, $user_id);

        if (!isset($member)) {
            // new membership
            $change = true;
            $member = new Member($obj_id, $user_id);
            $member = $member->withModuleId($module_id == 0 ? null : $module_id);
        }
        elseif (isset($module_id) && $module_id != (int) $member->getModuleId()) {
            // module id should be changed or reset
            $change = true;
            $member = $member->withModuleId($module_id == 0 ? null : $module_id);
        }
        else {
            $change= false;
        }

        // changes should be saved
        if ($change || $force) {
            $this->repo()->save($member);
        }
    }

    /**
     * Delete the membership of a user
     */
    public function deleteMembership(int $obj_id, int $user_id)
    {
        // these queries are cached
        $course_id = $this->dic->fau()->study()->repo()->getImportId($obj_id)->getCourseId();
        $person = $this->repo()->getPersonOfUser($user_id);
        $stagingRepo = $this->dic->fau()->staging()->repo();

        if (empty($course_id) || empty($person) || empty($stagingRepo)) {
            // not a relevant course or user or not connected
            return;
        }

        $member = $this->repo()->getMember($obj_id, $user_id);

        // simple membership has been transmitted => delete
        if (isset($member) && !$member->hasAnyRole()) {
            $this->repo()->delete($member);
        }
    }

    /**
     * Force a saving of course or group members for campo
     * Only members that have a person id from campo will be saved
     * @param int[] $obj_ids  course or group ids for which the members should be saved
     * @return array [ int[], int[] ]   saved and ignored user_ids
     */
    public function saveMembershipsForced(array $obj_ids) : array
    {
        $saved = [];
        $ignored = [];

        foreach ($obj_ids as $obj_id) {
            switch (\ilObject::_lookupType($obj_id)) {
                case 'crs':
                    $participants = new \ilCourseParticipants($obj_id);
                    break;
                case 'grp':
                    $participants = new \ilGroupParticipants($obj_id);
                    break;
                default:
                    continue 2;
            }

            foreach ($participants->getMembers() as $user_id) {
                $person = $this->repo()->getPersonOfUser((int) $user_id);
                if (empty($person) || empty($person->getPersonId())) {
                    $ignored[] = $user_id;
                }
                else {
                    $this->saveMembership($obj_id, (int) $user_id, null, true);
                    $saved[] = $user_id;
                }
            }

            // directly write the members back to the staging database
            $this->dic->fau()->sync()->toCampo()->syncMembersOfObject($obj_id);
        }
        return [array_unique($saved), array_unique($ignored)];
    }
}