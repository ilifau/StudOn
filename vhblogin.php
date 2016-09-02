<?php
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* vhb-Login-Skript fuer ILIAS 5.0
* @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
*
* In Anlehnung an das login-Script fuer wuecampus/moodle von Martin Schuhmann
*/

// Korrekten Client ermitteln (siehe index.php)
if (isset($_GET["client_id"]))
{
	$cookie_domain = $_SERVER['SERVER_NAME'];
	$cookie_path = dirname( $_SERVER['PHP_SELF'] );
	$cookie_path .= (!preg_match("/[\/|\\\\]$/", $cookie_path)) ? "/" : "";
	if($cookie_path == "\\") $cookie_path = '/';
	$cookie_domain = ''; // Temporary Fix
	setcookie("ilClientId", $_GET["client_id"], 0, $cookie_path, $cookie_domain);
	$_COOKIE["ilClientId"] = $_GET["client_id"];
}

// Authentifizierung durch ILIAS verhindern
// die wuerde einen redirect zum Login-Formular verursachen
// siehe ilInitialisation::blockedAuthentication() fuer Details
$_REQUEST["baseClass"] = "ilStartUpGUI";
$_REQUEST["cmdClass"] = "ilaccountregistrationgui";


// ILIAS initialisieren
require_once("./Services/Init/classes/class.ilInitialisation.php");
ilInitialisation::initILIAS();

// vhb-Klasse initialisieren
require_once("./Customizing/Vhb/classes/class.ilVhbAuth.php");
$vhb = new ilVhbAuth();

// vhb-Bezeichnung der eigenen Hochschule
// Wenn der vhb-Acount nicht auf lokalen Account abgebildet werden soll/kann,
// dann hier einfach etwas unmoegliches, z.B. "XX" eintragen
$vhb->setAcademy("Uni Erlangen-Nürnberg (FAU)");

// SALT-Wert zur Pruefsummenberechnung
// Der plattformspezifische Salt-Wert kann bei der vhb-Technik angeftagt werden
// Wenn leer, wird die Pruefsumme nicht getestet
$vhb->setCheckingSalt('dxr3JbEMX9Tj');

// Fallback-Password
// Dieses Passwort kann in der ILIAS-Benutzerverwaltung vergeben werden,
// wenn ein Student sein Passwort selbst geaendert und vergessen hat.
// Beim Zugriff ueber das vhb-Portal wird dann als Fallback diese Passwort getestet.
// Falls es stimmt, wird das Kurspasswort der vhb neu eingetragen.
$vhb->SetFallbackPassword('lostinspace');

// Anhang der Fehlerausgaben definieren
$vhb->setErrorSuffix(
'Wenden Sie sich bitte an den Betreuer, der in der Kursbeschreibung des vhb-Portals unter "Verantwortlich" genannt ist.'
);

// von der vhb per POST uebergebene Daten setzen
$vhb->setPostedData();

// Testausgabe der Daten
// $vhb->showData();
// exit;

// Uebergebene Daten testen (bricht evtl. mit Fehlermeldung ab)
// Hier wird auch der Hash-Wert getestet, falls ein salt gesetzt ist
$vhb->checkData();

// (Optional) Vergleiche die aufgerufene URL mit der in ilias.ini.php konfigurierten
// Speichere ggf. die übergebenen Daten und leite an die richtige URL weiter
// muss nach dem Datencheck aufgerufen werden da Daten nun utf8-codiert sind
$vhb->checkUrl();

// Suche nach vorhandenen Nutzern per Matrikelnummer
// Wenn eigene Unikennung, dann ohne X-Kennung, sonst mit
$vhb->findUser();

// Kurs finden (bricht evtl. mit Fehlermeldung ab)
// - Der Kurs muss folgenden Metadaten-Eintrag haben:
// 		- Katalog: vhb (Kleinschreibung!)
// 		- Eintrag: LV-Nummer, wie von der vhb uebergeben (genaue Schreibweise).
// - Der Kurs muss verfuegbar sein, aber der Beitritt kann ausgeschalten sein.
$vhb->findCourse();

// Daten fuer die nachfolgende Verarbeitung holen
$data = $vhb->getData();

// Pruefe, ob Nutzer gefunden
if ($vhb->getUserId())
{
	// Nutzeraccount ist schon vorhanden ...
	
	// Passwort fuer externe Studenten neu setzen,
	// um Konflikte mit Registrierungen frueherer Semester zu vermeiden.
	// Zur Absicherung wird dieser Workaround nur angewendet,	
	// wenn die Daten der vhb per Hash-Wert ueberprueft wurden.
	// Dann kann davon ausgegangen werden, dass es sich um eine sichere
	// Weiterleitung aus dem vhb-Portal handelt.
	if ($vhb->isExternal() and $vhb->isCheckedByHash())
	{
		$vhb->activateUser();		
		$vhb->assignPassword($data["passwort"]);
	}
	
	// Wurde das Passwort auf ein Fallback-Passwort zurueckgesetzt?
	elseif ($vhb->checkFallbackPassword())
	{
		// Denn wird das vhb-Passwort gesetzt und zum Kurs weitergeleitet
		$vhb->activateUser();		
		$vhb->assignPassword($data["passwort"]);
	}
}
else
{
	// Nutzeraccount muss neu angelegt werden ...
	
	if ($vhb->isExternal())
	{
		// Externe Studenten anlegen:
		// - werden ueber die ILIAS-Datenbank authentifiziert
		// - Login und Passwort werden wie uebergeben gesetzt
		// - Als Matrikelnummer wird das vhb-Login genommen (um Kollisionen zu vermeiden)
		$vhb->createUser(
			"vhb.",     			// Login-Praefix fuer externe vhb-Studenten
			$data["passwort"],  	// vhb-Passwort als Passwort
			$data["login"],     	// Matrikelnummer (mit Uni-Kennung X*)
			"local"             	// Authentifizierungsmodus (Datenbank)
		);
		
		$vhb->assignRole("Gast");   // Globale Rolle zuweisen
		//$vhb->assignRole("");     // Weitere moeglich
	}
	else
	{
		// Lokale Studenten mit vhb-Login anlegen:
		// - Der Authentifizierungsmodus koennte hier auch "ldap" sein
		// - Dass Passwort sollte in dem Fall leer sein
		// - Das Login koennte vorher per LDAP-Suche nach der Matrikelnummer bestimmt werden

		$vhb->createUser(
			"user.",     								// Login-Praefix fuer lokale Studenten
			$data["passwort"],  						// vhb-Passwort oder leer
			$data["matrikelnummer"], 					// Matrikelnummer (ohne Uni-Kennung X*)
			"local"             						// Authentifizierungsmodus (Datenbank)
		);		
		
		$vhb->assignRole("Nutzer");  	// Rolle zuweisen
		//$vhb->assignRole("");     	// Weitere Rollen moeglich
	}
}

// Account pruefen
if ($vhb->getUserId() == 0)
{
	$vhb->showError("Ihr Account konnte nicht gefunden oder angelegt werden!");	
}

// Kurs zuweisen
// Der Kurs wurde bereits mit findCourse() ermittelt
$vhb->assignCourse();


//////////////////////////////////////////////////////////
// Weiterleitung abhängig vom Status der Authentifizierung
//////////////////////////////////////////////////////////


// Aktive Nutzer mit Passwort kommen in den Kurs
if ($vhb->isUserActive() and $vhb->checkPassword())
{
	$vhb->enterCourse();
}
// Aktive Nutzer mit Hash-Absicherung kommen in den Kurs
elseif ($vhb->isUserActive() and $vhb->isCheckedByHash())
{
	$vhb->enterCourse();	
}
// Aktive Nutzer ohne Authentifizierung bekommen ein Login-Formular
elseif ($vhb->isUserActive())
{
	$vhb->showLoginForm();
}
// Inaktive Nutzer bekommen eine Fehlermeldung
else
{
	$vhb->showError("Ihr Account ist in der Lernplattform nicht mehr aktiv!");	
}
?>
