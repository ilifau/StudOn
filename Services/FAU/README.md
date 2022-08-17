# Service zur Integration von ILIAS an der FAU

## ILIAS-Anpassungen, die den Service nutzen

- **fauService** - Einbindung des Service bei der ILIAS-Initialisierung
- **userData** - Studiengangs- und Organisationsdaten von FAU-Benutzern (Ablösung von studyData)
- **studyCond** - Verwaltung und Prüfung von "weichen" Beitrittsbedingungen für Kurse und Gruppen.
- **preventCampoDelete** - Löschen von Kursen und Gruppen verhindern, die mit campo verbunden sind. Administratoren könenn löschen (die Verbindung zu Campo wird dann aufgehoben). Kurse dürfen verschoben werden, Gruppen nicht.
- **filterMyMem** Filterung der Liste "Meine Mitgliedschaften" nach Semester
- **studySearch** Suchseite nach Lehrveranstaltungen
- **paraSub** Anmeldung zu Parallengruppen bei der Kursanmeldung
- **campoInfo** Anzeigen auf den Info-Seiten
- **campoCheck** Voraussetzungsprüfung bei der Anmeldung
- **modSelect** Modulauswahl bei der Kursanmendung

Nach und nach werden alte Anpassungen, die verstreut liegende Klassen nutzen, auf Nutung dieses Service umgeschrieben.

## Abgelöste Anpassungen

- **idmData** 
- **univisAdmin**

## Struktur

Die Verzeichnisse des Service entsprechen seinen Teil-Services: 

- **Org** - Verwaltung der Organisationsstruktur: Org-Einheiten, Gebäude, Räume. Verknüpfung der Org-Einheiten mit Kategorien in StudOn.
- **Study** - Verwaltung allgemeiner Daten: Studiengänge, Module, Lehrveranstaltungen, Voraussetzungen, Verantwortliche. Verknüpfung dieser Daten mit den Kursen und Gruppen in StudOn.
- **Ilias** - Service zur Anpassung von ILIAS-Funktionen (Kurse, Gruppen, Anmeldung)
- **User** - Verwaltung nutzerbezogener Daten: Benutzergruppe, Rollen, Studienfächer, Leistungen, Qualifikationsstufen. Verknüpfung dieser Daten mit den StudOn-Benuteraccounts.
- **Cond** - "Harte" Beschränkungen (von Campo) für die Belegung von Lehrveranstaltungen. "Weiche" Bedingungen (in StudOn definiert) für den direkten Beitritt zu Kursen oder Gruppen, die mit Aufnahmeantrag gelöst werden können.
- **Staging** - Zugriff auf die Stanging-Datenbank 'IDM', über die Daten mit anderen Systemen synchronisiert werden.
- **Sync** - Synchronisation der Daten zwischen Staging-Datenbank und StudOn. Anlegen und Aktualisieren der Kurse und Gruppen. Aktualisierung der Studiengangsdaten und Rollen von Benutzern.
- **Tools** - Hilfsfunktionen, z.B. zur Datenkonvertierung

Die Teil-Services liegen Unterverzeichnissen von Services/FAU. Der Einstieg erfolgt über eine Service-Klasse, die über den Dependency Injection Container von ILIAS aufgerufen werden kann. Die Service-Klasse dient als Factory für weitere Klassen des Services, z.B. das Repository zum Datenzugriff oder Migration für Änderungen am Datenschema.

````php
global $DIC;
$studyService = $DIC->fau()->study();
$studyRepository = $DIC->fau()->study()->repo();
$matching = $DIC->fau()->study()->matching();
````


## Technik

### Namespaces

Der Service verwendet Namespaces in allen Klassen mit Ausnahme der von ILIAS abgeleiteten Klassen, z.B. `ilSyncWithCampoCron`.

````php
namespace FAU\Sync;
use FAU\User\Data\Education;
````

Die Dateinamen das Klassen im FAU-Namespace entsprechen den Klassennamen ohne Präfix 'class'.
Alle Klassen im Service werden beim ILIAS-Setup oder mit `composer dump-autoload -o` ins Autoload-Feature von PHP aufgenommen.


### Repository Pattern

Die Teil-Services verwenden das [Repository-Pattern](/docs/development/repository-pattern.md) von ILIAS. Daten werden über Immutable Data Objects ausgetauscht, die in den Unterverzeichnissen *Data* der Services definiert sind. Lesen und Schreiben dieser Daten erfolgt nur über Repository-Klassen in den Services. Die Datenklassen haben keine eigenen Lese- und Schreiboperationen und sollten einfach und ohne Abhängigkeiten gehalten werden.

````php
// Example: move educations from one user account to another
global $DIC;
$repo = $DIC->fau()->user()->repo();

foreach ($repo->getEducationsOfUser($old_user_id) as $oldEducation) {

    // save a clone without affecting the original
    $newEducation = $oldEducation->withUserId($old_user_id); 
    $repo->save($newEducation);
    
   // delete the original without affecting the clone
   $repo->delete($oldEducation);
}
````
Die Services und Datenklassen verwenden typisierte Parameter und Rückgabewerte, auch für skalare Typen.

Um das Lesen und Schreiben von Datenobjekten zu erleichtern, die sich auf Datensätze einzelner Tabellen beziehen, können die Datenklassen und Ihr Repository von den folgenden abstrakten Basisklassen abgleitet werden:

- [RecordData](RecordData.php) definiert Funktionen einer Datenklasse, um Werte-Arrays aus Datenbank-Abfragen zu laden oder für sie zu liefern.
- [RecordRepo](RecordRepo.php) enthält generelle Lese, Schreib- und Löschfunktionen für Datenklassen, die RecordData implementieren.

### Query Cache

Die Funktion ``RecordRepo::queryRecords()`` unterstützt standardmäßig ein Caching der Datenbank-Abfragen, d.h. die zurückgegebene Liste der RecordData-Objekte wird bei erneutem Aufruf mit der gleichen Abfrage im selben Request nicht erneut aus der Datenbank gelesen. Sollte das unterdrückt werden, weil aktuelle Daten benötigt werden, oder weil zu viele Ergebnisse zu viel Speicher benötigen würden, muss der letzte Parameter des Aufrufs auf *false* gesetzt werden.  