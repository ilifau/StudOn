# Setup zum FAU-Service

Die Datenbank-Update-Schritte zu den Teil-Services sind in separaten Steps-Klassen enthalten.

IN ILIAS 7 werden sie aus setup/sql/db_update_custom.php aufgerufen (Funktionen custom_step_x).
Ab ILIAS 8 können die Klassen direkt ins Setup eingebunden werden:

https://github.com/ILIAS-eLearning/ILIAS/blob/trunk/docs/development/database-updates.md

