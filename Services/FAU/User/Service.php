<?php declare(strict_types=1);

namespace FAU\User;

use ILIAS\DI\Container;
use ilLanguage;
use ilDatePresentation;

/**
 * Service for FAU user related data
 */
class Service
{
    protected Container $dic;
    protected ilLanguage $lng;
    protected Repository $repository;


    /**
     * Constructor
     */
    public function __construct(Container $dic)
    {
        $this->dic = $dic;
        $this->lng = $dic->language();
    }


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
     * Get the handler for date scheme migrations (no caching needed)
     */
    public function migration() : Migration
    {
        return new Migration($this->dic->database());
    }

    /**
     * Get the educations of a user as text
     * An education is given as Title: Text
     * Educations are separated by newlines
     */
    public function getEducationsAsText(int $user_id) : string
    {
        $texts = [];
        foreach ($this->repo()->getEducationsOfUser($user_id) as $education) {
            $texts[] = $education->getTitle() . ': ' . $education->getText();
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
        if (empty($studies = $person->getStudiesOfTerm($this->dic->fau()->study()->getCurrentTerm()))) {
            $studies = $studies($person->getStudiesOfTerm($person->getMaxTerm()));
        }
        foreach ($studies as $study) {
            $text = $this->dic->fau()->study()->getReferenceTermText($study->getTerm());
            $text .= empty($study->getEnrollmentName()) ? '' : ' (' . $study->getEnrollmentName() . ')';
            $text .= ':';

            $subject_texts = [];
            foreach ($study->getSubjects() as $subject) {
                $subject_texts[] = $subject->getSubjectName()
                . sprintf($this->lng->txt('studydata_semester_text'), $subject->getStudySemester());
            }
            $text .= empty($subject_texts) ? '' : " \n" . implode(', ', $subject_texts);

            $text .= empty($study->getDegreeName()) ? '' : " \n" . $study->getDegreeName();
            $texts[] = $text;
        }

        // Promotion data
        $text = '';
        if (!empty($person->getDocProgrammesText())) {
            $text = $person->getDocProgrammesText();
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
}