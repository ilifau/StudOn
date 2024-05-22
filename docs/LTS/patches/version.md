# Patch ILIAS Version

Dieser Patch inkrementiert die ILIAS Version um eine Patch-Release-Stelle.

## Patch-Markierungen

Patches wurden mit `lts-patch: begin version` und `lts-patch: end version` markiert.

## Änderungen

Angepasst wurden im Rahmen der Funktionalität folgende Dateien:

* include/inc.ilias_version.php

### Angepasste Werte

- `ILIAS_VERSION` Wird zur Darstellung in der UI verwendet.
- `ILIAS_VERSION_NUMERIC` Wird zur Abgleich von Versionen und Distribution von Ressourcen verwendet.

## Spezifikation

Die Versionsnummern folgen dem globalen Schema `[MAJOR].[MINOR].[PATCH]` und werden mit jedem LTS-Release
um 1 auf `PATCH` inkrementiert. 

**`MAJOR` und `MINOR` dürfen nicht verändert werden!**
