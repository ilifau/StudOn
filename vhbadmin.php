<?php
/* Copyright (c) 1998-2011 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* fim: [vhb] vhb-Admin-Skript für ILIAS 4.1
* @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
*/

// Fehlermeldungsausgabe setzen
// ini_set("display_errors", "ON");
// ini_set("error_reporting", E_ALL ^ E_NOTICE);

// Initialisieren und Rechte prüfen
require_once("./Customizing/Vhb/classes/class.ilVhbAdmin.php");
$vhbadmin = new ilVhbAdmin();
$vhbadmin->initIlias();
$vhbadmin->checkAdminPermission();


// Admin-Kommandos ausführen
switch ($_GET["cmd"])
{
	case "anonymize":

		// Benennt Accounts um, die das Kurslogin der vhb als Benutzernamen haben.
		// Das Kurslogin entspricht dem Schema 1234567X00
		// - 1234567 ist die Matrikelnummer der Heimathochschule
		//           oder eine Kennung für Gäste (kann auch Buchstaben enthalten)
		// - X00 ist die Kennung der Heimathochschule
		// Das neue Login hat das Schema präfix12345
		// - präfix ist für die eigene und andere Hochschulen getrennt wählbar
		// - 12345 entspricht der internen user-id in ILIAS

		$vhbadmin->anonymize(
			"user.",             // neues Präfix für Studenten der eigenen Hochschule
            "vhb.",              // neues Präfix für Studenten anderer Hochschulen
            "X7"                 // Kennung für die eigene Hochschule im alten Login
		);
		exit;

	case "resetPrefs":

		// Setzt die Voreinstellungen von Nutzern zurück,
		// deren Login mit einem bestimmten Präfix beginnt.
		// Gesetzt werden:
		// - keine Anzeige eingeloggter Nutzer
		// - keine Anzeige des eigenen Login-Status für andere
		// - kein öffentliches Profil

		$vhbadmin->resetPrefs(
			"user.",            // Präfix für Studenten der eigenen Hochschule
			"vhb."             	// Präfix für Studenten anderer Hochschulen
		);
		exit;

	default:
		echo "<pre>\n";
		echo "Bitte geben Sie ein Kommando an:\n";
		echo "vhbadmin?cmd=anonymize\n";
		echo "vhbadmin?cmd=resetPrefs\n";
		echo "</pre>\n";
		exit;
}
?>
