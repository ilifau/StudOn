<?php
/* Copyright (c) 1998-2011 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* fim: [vhb] vhb-Admin-Skript f�r ILIAS 4.1
* @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
*/

// Fehlermeldungsausgabe setzen
// ini_set("display_errors", "ON");
// ini_set("error_reporting", E_ALL ^ E_NOTICE);

// Initialisieren und Rechte pr�fen
require_once("./Customizing/Vhb/classes/class.ilVhbAdmin.php");
$vhbadmin = new ilVhbAdmin();
$vhbadmin->initIlias();
$vhbadmin->checkAdminPermission();


// Admin-Kommandos ausf�hren
switch ($_GET["cmd"])
{
	case "anonymize":

		// Benennt Accounts um, die das Kurslogin der vhb als Benutzernamen haben.
		// Das Kurslogin entspricht dem Schema 1234567X00
		// - 1234567 ist die Matrikelnummer der Heimathochschule
		//           oder eine Kennung f�r G�ste (kann auch Buchstaben enthalten)
		// - X00 ist die Kennung der Heimathochschule
		// Das neue Login hat das Schema pr�fix12345
		// - pr�fix ist f�r die eigene und andere Hochschulen getrennt w�hlbar
		// - 12345 entspricht der internen user-id in ILIAS

		$vhbadmin->anonymize(
			"user.",             // neues Pr�fix f�r Studenten der eigenen Hochschule
            "vhb.",              // neues Pr�fix f�r Studenten anderer Hochschulen
            "X7"                 // Kennung f�r die eigene Hochschule im alten Login
		);
		exit;

	case "resetPrefs":

		// Setzt die Voreinstellungen von Nutzern zur�ck,
		// deren Login mit einem bestimmten Pr�fix beginnt.
		// Gesetzt werden:
		// - keine Anzeige eingeloggter Nutzer
		// - keine Anzeige des eigenen Login-Status f�r andere
		// - kein �ffentliches Profil

		$vhbadmin->resetPrefs(
			"user.",            // Pr�fix f�r Studenten der eigenen Hochschule
			"vhb."             	// Pr�fix f�r Studenten anderer Hochschulen
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
