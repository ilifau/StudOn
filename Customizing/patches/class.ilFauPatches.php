<?php

use FAU\Study\Data\LostCourse;
use ILIAS\DI\Container;
use FAU\Setup\Setup;
use FAU\Study\Data\Term;
use FAU\Study\Data\ImportId;

/**
 * fau: fauService - patches to handle fau data
 */
class ilFauPatches
{

    protected Container $dic;


    public function __construct()
    {
        global $DIC;
        $this->dic = $DIC;
    }

    public function syncCampoData()
    {
        $service = $this->dic->fau()->sync()->campo();
        $service->synchronize();
    }
    
    public function syncPersonData()
    {
        $service = $this->dic->fau()->sync()->idm();
        $service->synchronize();
    }

    public function syncWithIlias($params = ['orgunit_ids' => null, 'term_ids' => null])
    {
        $service = $this->dic->fau()->sync()->ilias();
        $service->synchronize($params['orgunit_ids'], $params['term_ids']);
    }

    public function syncToCampo()
    {
        $service = $this->dic->fau()->sync()->toCampo();
        $service->synchronize();
    }

    public function syncWithOrg()
    {
        $service = $this->dic->fau()->sync()->org();
        $service->synchronize();
    }

    /**
     * todo: move to cron job when finished
     */
    public function checkOrgUnitRelations()
    {
        $service = $this->dic->fau()->sync()->trees();
        $service->checkOrgUnitRelations();
    }

    /**
     * Create the courses of a term or with specific ids
     */
    public function createCourses($params = ['term' => '20222', 'course_ids' => null, 'test_run' => true])
    {
        $service = $this->dic->fau()->sync()->ilias();
        $service->createCourses(Term::fromString($params['term']), $params['course_ids'], $params['test_run']);
    }

    /**
     * Update the courses of a term or with specific ids
     */
    public function updateCourses($params = ['term' => '20222', 'course_ids' => null, 'test_run' => true])
    {
        $service = $this->dic->fau()->sync()->ilias();
        $service->updateCourses(Term::fromString($params['term']), $params['course_ids'], $params['test_run']);
    }

    /**
     * Move courses from the faculties ot the fallback category to their correct destination, if possible
     */
    public function moveLostCourses($params = ['term' => '20222'])
    {
        $service = $this->dic->fau()->sync()->ilias();
        $service->moveLostCourses(Term::fromString($params['term']));
    }

    /**
     * Create the missing manager and author roles in a category
     */
    public function createMissingOrgRoles($params = ['exclude' => []])
    {
        $service = $this->dic->fau()->sync()->ilias();
        $service->createMissingOrgRoles($params['exclude']);
    }

    /**
     * Initially fill the course roles in parent courses of parallel groups
     */
    public function fillParentCourseRoles() {

        $matching = $this->dic->fau()->sync()->roles();
        
        $query = "
        SELECT r.ref_id, o.import_id 
        FROM object_data o 
        JOIN object_reference r ON r.obj_id = o.obj_id
        WHERE o.`type` = 'crs'
        AND o.import_id LIKE 'FAU%' 
        AND o.import_id NOT LIKE '%Course%' 
        AND r.deleted IS NULL
        ";
        
        $result = $this->dic->database()->query($query);
        while ($row = $this->dic->database()->fetchAssoc($result)) {
            $ref_id = (int) $row['ref_id'];
            $import_id = ImportId::fromString($row['import_id']);
            echo $ref_id . ' ' . $import_id->toString() . "\n";
            $matching->updateRolesInIliasObject($ref_id, null, null, $import_id->getEventId(), Term::fromString($import_id->getTermId()));
        }
    }
    
    /**
     *
     */
    public function sendMailsToSolveConflicts()
    {
        $t_subject = "StudOn: Konflikt bei Campo-Verbindung des Kurses %s";
        $t_body = "
Liebe Kursversantwortliche,

Sie erhalten diese automatisch generierte Nachricht, da StudOn einen Fehler in der Campo-Verbindung ihres Kurses entdeckt hat. Sie betrifft den folgenden Kurs:

%s
%s

Mit der Veranstaltung in Campo wurden durch ein mittlerweile behobenes Problems mehrere Kurse in StudOn verbunden. Dadurch ist die Verlinkung von Campo nicht eindeutig und Anmeldungen erfolgen möglicherweise im falschen Kurs. Da StudOn nicht erkennen kann, welcher der Kurse aktiv verwendet werden soll, haben wir eine Funktion umgesetzt, mit der Sie die Entscheidung selbst treffen können:

1. Rufen Sie den obigen Kurslink auf
2. Klicken Sie auf der Inhaltsseite den grünen Button „Campo-Konflikt lösen“
3. Wählen Sie den Kurs aus der mit Campo verbunden werden soll.

Nach dem Aufruf werden Sie zum Kurs weitergeleitet, der nun als einziger verbunden ist. Bitte kontrollieren sie hier die Mitglieder- und Warteliste, die von den anderen Kursen auf diesen Kurs übertragen wurden. Die anderen Kurse werden automatisch offline gesetzt, damit sie nicht versehentlich von den Studierenden gefunden werden. Sollte der grüne Button nicht erscheinen, wurde die der Konflikt bereits gelöst.

Vielen Dank für Ihr Verständnis und Ihre Mithilfe,
Ihre StudOn-Administratoren
        ";


        $query = "
SELECT r2.ref_id, CONCAT('https://www.studon.fau.de/', r2.ref_id) AS link, o2.title
FROM fau_study_courses c
JOIN object_data o1 ON o1.import_id = CONCAT('FAU/Term=20222/Event=',c.event_id,'/Course=',c.course_id) AND c.ilias_obj_id <> o1.obj_id
JOIN object_reference r1 ON r1.obj_id = o1.obj_id AND r1.deleted IS NULL
JOIN object_data o2 ON o2.obj_id = c.ilias_obj_id AND o2.type = 'crs'
JOIN object_reference r2 ON r2.obj_id = o2.obj_id AND r2.deleted IS NULL
        ";

        $result = $this->dic->database()->query($query);
        $count = 1;
        while ($row = $this->dic->database()->fetchAssoc($result)) {
            $ref_id = (int) $row['ref_id'];
            $link = (string) $row['link'];
            $title = (string) $row['title'];

            $subject = sprintf($t_subject, $title);
            $body = sprintf($t_body, $title, $link);

            $mail = new ilMail(ANONYMOUS_USER_ID);

            $address = '#il_crs_admin_' . $ref_id;
            $errors = $mail->sendMail($address, '', '', $subject, $body, [], false);

            if (!empty($errors)) {
                echo $count++ . ': ' . $address . " (ERROR)\n";
            }
            else {
                echo $count++ . ': ' . $address . " (ok)\n";
            }
        }
    }


    public function cleanupDoubleAccounts()
    {
        $table = '_double_ext_accounts';

        $mail_student = file_get_contents(__DIR__ . '/cleanup_double_account_student.html');
        $mail_other = file_get_contents(__DIR__ . '/cleanup_double_account_other.html');

        $query = "SELECT * FROM $table WHERE processed = 0";

        $result = $this->dic->database()->query($query);
        while ($row = $this->dic->database()->fetchAssoc($result)) {

            $ext_account = $row['ext_account'];
            $is_student = !empty($row['fau_student']);
            $login1 = $row['login1'];
            $login2 = $row['login2'];
            $email1 = $row['email1'];
            $email2 = $row['email2'];

            echo "\n". $ext_account;


            $user_id1 = ilObjUser::getUserIdByLogin($login1);
            $user_id2 = ilObjUser::getUserIdByLogin($login2);

            if (empty($user_id1) || empty($user_id2)) {
                echo ' - empty';
                continue;
            }

            $user1 = new ilObjUser($user_id1);
            $user2 = new ilObjUser($user_id2);

            if ($is_student) {
                echo ' - student';
                $user2->setAuthMode('local');
                $user2->setExternalAccount(null);
                $user2->update();

                $user1->setAuthMode('shibboleth');
                $user1->setExternalAccount($ext_account);
                $user1->update();

                $body = $mail_student;
                $salutation = ilMail::getSalutation($user_id1);
            }
            else {
                echo '- other';
                $user1->setAuthMode('local');
                $user1->setExternalAccount(null);
                $user1->update();
                $user1->updateLogin($login1 . '-lokal');

                $user2->setAuthMode('shibboleth');
                $user2->setExternalAccount($ext_account);
                $user2->update();

                $body = $mail_other;
                $salutation = ilMail::getSalutation($user_id2);
            }

            $emails = [];
            if (!empty($email1)) {
                $emails[] = $email1;
            }
            if (!empty($email2)) {
                $emails[] = $email2;
            }
            $to = implode(', ', array_unique($emails));

            $body = str_replace('{salutation}', $salutation, $body);
            $body = str_replace('{ext_account}', $ext_account, $body);
            $body = str_replace('{login1}', $login1, $body);
            $body = str_replace('{login2}', $login2, $body);
            $body = str_replace('{email1}', $email1, $body);
            $body = str_replace('{email2}', $email2, $body);

            $mail = new ilMimeMail();
            $mail->To($to);
            $mail->Subject('IDM-Verknüpfung Ihrer StudOn-Accounts');
            $mail->Body($body);
            $mail->From(new ilMailMimeSenderSystem($this->dic->settings()));
            $mail->send();

            $query2 = "UPDATE $table SET processed = 1 WHERE ext_account = " . $this->dic->database()->quote($ext_account, 'text');
            $this->dic->database()->manipulate($query2);
        }
    }
    
    public function remindExmatriculation($params = ['login' => null])
    {
        global $DIC;
        $studyService = $this->dic->fau()->study();
        
        $mail_template = file_get_contents(__DIR__ . '/remind_exmatriculation.html');

        $switch_time = $studyService->getTermEndTime($studyService->getCurrentTerm());
        $switch_date = new ilDate($this->dic->fau()->tools()->convert()->unixToDbDate($switch_time), IL_CAL_DATE);
        $switch_date_display = ilDatePresentation::formatDate($switch_date);
        $next_term_string = $studyService->getNextTerm()->toString();
        
        $query = "
SELECT usr_id, login, firstname, lastname, ext_account, email
FROM usr_data u
JOIN fau_user_persons p ON p.user_id = u.usr_id
WHERE u.auth_mode = 'shibboleth'
AND u.active = 1 
AND (u.time_limit_unlimited = 1 OR u.time_limit_until > $switch_time)
AND u.ext_account IS NOT NULL
AND p.employee IS NULL
AND p.guest IS NULL
AND p.student IS NOT NULL
AND p.studydata NOT LIKE '%\"P$next_term_string\":%'
ORDER BY lastname, firstname, login
        ";
        
        echo $query;        
        
        if (!empty($params['login'])) {
            $query = "
                SELECT usr_id, login, firstname, lastname, ext_account, email 
                FROM usr_data u WHERE login = " 
                . $this->dic->database()->quote($params['login'], 'text');
            
            echo "\n". $query;
        }

        $result = $this->dic->database()->query($query);
        $count = 0;
        while ($row = $this->dic->database()->fetchAssoc($result)) {
            $count++;

            $lastname = $row['lastname'];
            $firstname = $row['firstname'];
            $usr_id = $row['usr_id'];
            $login = $row['login'];
            $ext_account = $row['ext_account'];
            $email = $row['email'];
            
            echo "\n". $lastname . ',' . $firstname . ', ' . $login . ' => ' . $ext_account;
            
            $salutation = ilMail::getSalutation($usr_id);
            $to = $email;

            $body = $mail_template;
            $body = str_replace('{date}', $switch_date_display, $body);
            $body = str_replace('{salutation}', $salutation, $body);
            $body = str_replace('{login}', $login, $body);

            $mail = new ilMimeMail();
            $mail->To($to);
            $mail->Subject('StudOn-Nutzung nach dem ' . $switch_date_display);
            $mail->Body($body);
            $mail->From(new ilMailMimeSenderSystem($this->dic->settings()));
            $mail->send();
        }
        
        echo "\nTotal:" . $count;
    }


    /**
     * Remove courses that were automatically created because the old courses lost their campo connection
     * This happened because up to March 3, 2023 all courses were transferred to studon, after that date only the released ones
     */
    public function removeUntouchedDoubleCourses()
    {
        $query = "
            SELECT c.course_id, r.ref_id, o.obj_id, o.`type`, o.title,
            CONCAT('https://www.studon.fau.de/', r.ref_id) link
            FROM fau_study_courses c
            JOIN fau_study_lost_courses cc ON cc.course_id = c.course_id
            JOIN object_data o ON o.obj_id = c.ilias_obj_id
            JOIN object_reference r ON r.obj_id = o.obj_id AND r.deleted IS NULL
            JOIN object_data oc ON oc.obj_id = cc.ilias_obj_id
            JOIN object_reference rc ON rc.obj_id = oc.obj_id AND rc.deleted IS NULL
            WHERE c.ilias_obj_id IS NOT NULL
            AND o.create_date = o.last_update
            AND c.ilias_obj_id <> cc.ilias_obj_id
            AND oc.import_id IS null
            ORDER BY o.title, c.course_id
        ";

        $result = $this->dic->database()->query($query);
        while ($row = $this->dic->database()->fetchAssoc($result)) {

            echo "\n\n[" . $row['type'] . '] ' . $row['link'] . ' ' . $row['title'] . '...';

            $ref_id = $row['ref_id'];
            $obj_id = $row['obj_id'];
            $course = $this->dic->fau()->study()->repo()->getCourse($row['course_id']);

            // get the reference of a parent course
            $parent_course = null;
            if (!empty($parent_ref = $this->dic->fau()->ilias()->objects()->findParentIliasCourse($ref_id))) {
                $parent_course = new ilObjCourse($parent_ref);
            }

            // get the object, correct type is already checked in the caller
            if (ilObject::_lookupType($ref_id, true) == 'crs') {
                $object = new ilObjCourse($ref_id);
                $object->setDescription($this->dic->language()->txt('fau_campo_course_is_missing_for_ilias_course'));
            }
            elseif (ilObject::_lookupType($ref_id, true) == 'grp') {
                $object = new ilObjGroup($ref_id);
                $object->setDescription($this->dic->language()->txt('fau_campo_course_is_missing_for_ilias_group'));
            }
            else {
                continue; // next row
            }

            // always provide the info and delete the import id
            // do not yet update to allow a check for manual changes
            $object->setTitle($this->dic->language()->txt('fau_campo_course_is_missing_prefix') . ' ' . $object->getTitle());
            $object->setImportId(null);

//            echo "\nManually changed: " . $this->dic->fau()->sync()->ilias()->isObjectManuallyChanged($object) . "\n";
//            echo "\nUndeletedContents: " . $this->dic->fau()->ilias()->objects()->hasUndeletedContents($ref_id) . "\n";
//            echo "\nLocalMemberChanges:" . $this->dic->fau()->sync()->roles()->hasLocalMemberChanges($ref_id) . "\n";

            if ($this->dic->fau()->sync()->ilias()->isObjectManuallyChanged($object)
                || $this->dic->fau()->ilias()->objects()->hasUndeletedContents($ref_id)
                || $this->dic->fau()->sync()->roles()->hasLocalMemberChanges($ref_id)
            ) {
                echo "\nObject is touched.";
                continue;
            }

            // save the changes, even if object will be moved to trash
            echo "\n Update Object";
            $object->update();
            try {
                // this checks delete permission on all objects
                // so the cron job user needs the global admin role!
                echo "\n Delete Object";
                ilRepUtil::deleteObjects($this->dic->repositoryTree()->getParentId($ref_id), [$ref_id]);

                // delete the parent course of a group if it is empty and not yet touched
                // member changes can't be detected for the parent course
                if (!empty($parent_course)
                    && !$this->dic->fau()->sync()->ilias()->isObjectManuallyChanged($parent_course)
                    && !$this->dic->fau()->ilias()->objects()->hasUndeletedContents($parent_ref)
                ) {
                    echo "\n Delete Parent";
                    ilRepUtil::deleteObjects($this->dic->repositoryTree()->getParentId($parent_ref), [$parent_ref]);
                    $parent_course = null;
                }
            }
            catch (Exception $e) {
                echo "\n". $e->getMessage();
                continue;
            }

            // always delete the course record, the staging record is already deleted
            // has to be done before calling findChildParallelGroups() of the parent

            $this->dic->fau()->study()->repo()->save($course->withIliasObjId(null)->asChanged(false));


            // check if parent course should loose the campo connection
            if (!empty($parent_course)) {
                if (empty($this->dic->fau()->ilias()->objects()->findChildParallelGroups($parent_ref, false))) {
                    // no other parallel groups are connected in the parent
                    // delete the campo connection of the parent course
                    $parent_course->setTitle($this->dic->language()->txt('fau_campo_course_is_missing_prefix') . ' ' . $parent_course->getTitle());
                    $parent_course->setDescription($this->dic->language()->txt('fau_campo_course_is_missing_for_ilias_course'));
                    $parent_course->setImportId(null);
                    echo "\n Update Parent";
                    $parent_course->update();
                }
            }
        }
    }
}