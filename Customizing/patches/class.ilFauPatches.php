<?php

use ILIAS\DI\Container;
use FAU\Setup\Setup;
use FAU\Study\Data\Term;

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

    public function syncToCampo()
    {
        $service = $this->dic->fau()->sync()->toCampo();
        $service->synchronize();
    }


    public function syncRestrictions()
    {
        $service = $this->dic->fau()->sync()->campo();
        $service->syncModuleRestrictions();
        $service->syncEventRestrictions();
    }

    /**
     * todo: move to cron job if performance is ok
     */
    public function syncPersonData()
    {
        $service = $this->dic->fau()->sync()->idm();
        $service->synchronize();
    }

    /**
     * Migrate the conditions from the old study tables to the new fau_study tables
     */
    public function migrateConditions()
    {
        Setup::instance($this->dic->database())->cond()->fillCosConditionsFromStudydata($this->dic->fau()->staging()->database());
        Setup::instance($this->dic->database())->cond()->fillDocConditionsFromStudydata();
    }


    public function syncWithIlias($params = ['orgunit_id' => null, 'negate' => false])
    {
        $service = $this->dic->fau()->sync()->ilias();
        $service->synchronize($params['orgunit_id'], $params['negate']);
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
     * Create the courses of a term or with specific ids
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
     * Create the emissing manager and author roles in a category
     */
    public function createMissingOrgRoles($params = ['exclude' => []])
    {
        $service = $this->dic->fau()->sync()->ilias();
        $service->createMissingOrgRoles($params['exclude']);
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
}