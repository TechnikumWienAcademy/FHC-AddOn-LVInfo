# FHC-AddOn-LVInfo

Addon zur Verwaltung von Lehrveranstaltungsinformationen.

Features:

  * Pro Lehrveranstaltung und Studiensemester können eigene Versionen erfasst werden
  * Freigabe der Lehrveranstaltungsinformationen durch Studiengangsleitung
  * Anzeige der Unterschiede zu Vorversionen
  * Unterschiedliche LVInfo Sets für Studiensemester
  * Kopieren von LVInformationen
  * JSON Export

## Systemvoraussetzungen

  * Postgresql >= 9.4
  * PHP >= 5.3.0
  * Composer

## Installation

  * Addon in Ordner /addons/lvinfo/ entpacken
  * Datenbank Update starten addons/lvinfo/dbcheck.php
  * LVInfos aus Core übernehmen addons/lvinfo/system/migrate.php
  * Addon im Config aktivieren