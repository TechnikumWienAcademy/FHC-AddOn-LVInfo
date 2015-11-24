<?php
/* Copyright (C) 2013 FH Technikum-Wien
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307, USA.
 *
 */
/**
 * FH-Complete Addon LV-Info Datenbank Check
 *
 * Prueft und aktualisiert die Datenbank
 */
require_once('../../config/system.config.inc.php');
require_once('../../include/basis_db.class.php');
require_once('../../include/functions.inc.php');
require_once('../../include/benutzerberechtigung.class.php');

// Datenbank Verbindung
$db = new basis_db();

echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
		"http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" href="../../skin/fhcomplete.css" type="text/css">
	<link rel="stylesheet" href="../../skin/vilesci.css" type="text/css">
	<title>Addon LV-Info Datenbank Check</title>
</head>
<body>
<h1>Addon LV-Info Datenbank Check</h1>';

$uid = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($uid);

if(!$rechte->isBerechtigt('basis/addon'))
{
	exit('Sie haben keine Berechtigung für die Verwaltung von Addons');
}

echo '<input type="button" onclick="window.location.href=\'dbcheck.php?start\'" value="Aktualisierung starten"><br/><br/>';

if (!isset($_GET['start']))
	exit;

echo '<h2>Aktualisierung der Datenbank</h2>';

// Code fuer die Datenbankanpassungen

// Pruefung, ob Schema addon vorhanden ist
if($result = $db->db_query("SELECT schema_name FROM information_schema.schemata WHERE schema_name = 'addon'"))
{
	if($db->db_num_rows($result)==0)
	{
		$qry = "CREATE SCHEMA addon;
				GRANT USAGE ON SCHEMA addon TO vilesci;
				GRANT USAGE ON SCHEMA addon TO web;
				";

		if(!$db->db_query($qry))
			echo '<strong>Schema addon: '.$db->db_last_error().'</strong><br>';
		else
			echo '<br>Neues Schema addon hinzugefügt';
	}
}

// Anlegen der Tabelle tbl_lvinfo_set
if(!$result = @$db->db_query("SELECT 1 FROM addon.tbl_lvinfo_set"))
{

	$qry = "
			CREATE TABLE addon.tbl_lvinfo_set
			(
				lvinfo_set_id integer NOT NULL,
				lvinfo_set_kurzbz varchar(16),
				lvinfo_set_bezeichnung varchar(64)[],
				sort integer,
				lvinfo_set_typ varchar(32) NOT NULL,
				gueltigab_studiensemester_kurzbz varchar(6) NOT NULL,
				oe_kurzbz varchar(32),
				insertamum timestamp,
				insertvon varchar(32),
				updateamum timestamp,
				updatevon varchar(32)
			);

			ALTER TABLE addon.tbl_lvinfo_set ADD CONSTRAINT pk_lvinfo_set PRIMARY KEY (lvinfo_set_id);
			ALTER TABLE addon.tbl_lvinfo_set ADD CONSTRAINT fk_lvinfo_set_gueltigab_studiensemester_kurzbz FOREIGN KEY (gueltigab_studiensemester_kurzbz) REFERENCES public.tbl_studiensemester(studiensemester_kurzbz) ON DELETE RESTRICT ON UPDATE CASCADE;
			ALTER TABLE addon.tbl_lvinfo_set ADD CONSTRAINT fk_lvinfo_set_oe_kurzbz FOREIGN KEY (oe_kurzbz) REFERENCES public.tbl_organisationseinheit(oe_kurzbz) ON DELETE RESTRICT ON UPDATE CASCADE;
			CREATE SEQUENCE addon.tbl_lvinfo_set_lvinfo_set_id_seq
			INCREMENT BY 1
			NO MAXVALUE
			NO MINVALUE
			CACHE 1;
			
			ALTER TABLE addon.tbl_lvinfo_set ALTER COLUMN lvinfo_set_id SET DEFAULT nextval('addon.tbl_lvinfo_set_lvinfo_set_id_seq');
			
			COMMENT ON TABLE addon.tbl_lvinfo_set IS 'Hier wird definiert, welche Felder es bei den LVInfos geben soll und ab welchem Studiensemester sie fuer welche OE gueltig sind';
			COMMENT ON COLUMN addon.tbl_lvinfo_set.sort IS 'Reihenfolge im Formular';
			COMMENT ON COLUMN addon.tbl_lvinfo_set.lvinfo_set_typ IS 'text, array, boolean, ...';
			COMMENT ON COLUMN addon.tbl_lvinfo_set.gueltigab_studiensemester_kurzbz IS 'Ab welchem Studiensemester gilt dieses Feld';
		
			GRANT SELECT, INSERT, UPDATE, DELETE ON addon.tbl_lvinfo_set TO vilesci;
			GRANT SELECT, INSERT, UPDATE, DELETE ON addon.tbl_lvinfo_set TO web;
			
			GRANT SELECT, INSERT, UPDATE, DELETE ON addon.tbl_lvinfo_set_lvinfo_set_id_seq TO vilesci;
			GRANT SELECT, INSERT, UPDATE, DELETE ON addon.tbl_lvinfo_set_lvinfo_set_id_seq TO web;
		
			";

	if(!$db->db_query($qry))
		echo '<strong>addon.tbl_lvinfo_set: '.$db->db_last_error().'</strong><br>';
	else
		echo '<br>Tabelle addon.tbl_lvinfo_set hinzugefuegt!';

}

// Anlegen der Tabelle tbl_lvinfo
if(!$result = @$db->db_query("SELECT 1 FROM addon.tbl_lvinfo"))
{

	$qry = "
			CREATE TABLE addon.tbl_lvinfo
			(
				lvinfo_id integer NOT NULL,
				sprache varchar(16),
				lehrveranstaltung_id integer NOT NULL,
				studiensemester_kurzbz varchar(6),
				data jsonb,
				insertamum timestamp,
				insertvon varchar(32),
				updateamum timestamp,
				updatevon varchar(32)
			);
		
			ALTER TABLE addon.tbl_lvinfo ADD CONSTRAINT pk_addon_lvinfo PRIMARY KEY (lvinfo_id);
			ALTER TABLE addon.tbl_lvinfo ADD CONSTRAINT fk_lvinfo_sprache FOREIGN KEY (sprache) REFERENCES public.tbl_sprache(sprache) ON DELETE RESTRICT ON UPDATE CASCADE;
			ALTER TABLE addon.tbl_lvinfo ADD CONSTRAINT fk_lvinfo_lehrveranstaltung FOREIGN KEY (lehrveranstaltung_id) REFERENCES lehre.tbl_lehrveranstaltung (lehrveranstaltung_id) ON UPDATE CASCADE ON DELETE RESTRICT;
			ALTER TABLE addon.tbl_lvinfo ADD CONSTRAINT fk_lvinfo_studiensemester FOREIGN KEY (studiensemester_kurzbz) REFERENCES public.tbl_studiensemester(studiensemester_kurzbz) ON DELETE RESTRICT ON UPDATE CASCADE;
			
			CREATE SEQUENCE addon.tbl_lvinfo_lvinfo_id_seq
			INCREMENT BY 1
			NO MAXVALUE
			NO MINVALUE
			CACHE 1;
			
			ALTER TABLE addon.tbl_lvinfo ALTER COLUMN lvinfo_id SET DEFAULT nextval('addon.tbl_lvinfo_lvinfo_id_seq');
			
			GRANT SELECT, INSERT, UPDATE, DELETE ON addon.tbl_lvinfo TO vilesci;
			GRANT SELECT, INSERT, UPDATE, DELETE ON addon.tbl_lvinfo TO web;
			
			GRANT SELECT, INSERT, UPDATE, DELETE ON addon.tbl_lvinfo_lvinfo_id_seq TO vilesci;
			GRANT SELECT, INSERT, UPDATE, DELETE ON addon.tbl_lvinfo_lvinfo_id_seq TO web;
			";

	if(!$db->db_query($qry))
		echo '<strong>tbl_lvinfo: '.$db->db_last_error().'</strong><br>';
	else
		echo '<br>Tabelle addon.tbl_lvinfo hinzugefuegt!';

}

// Anlegen der Tabelle tbl_lvinfostatus
if(!$result = @$db->db_query("SELECT 1 FROM addon.tbl_lvinfostatus"))
{

	$qry = "
			CREATE TABLE addon.tbl_lvinfostatus
			(
				lvinfostatus_kurzbz varchar(32) NOT NULL,
				tbl_lvinfostatus_bezeichnung varchar(64)[],
				insertamum timestamp,
				insertvon varchar(32),
				updateamum timestamp,
				updatevon varchar(32)
			);

			ALTER TABLE addon.tbl_lvinfostatus ADD CONSTRAINT pk_lvinfostatus PRIMARY KEY (lvinfostatus_kurzbz);
		
			GRANT SELECT, INSERT, UPDATE, DELETE ON addon.tbl_lvinfostatus TO vilesci;
			GRANT SELECT, INSERT, UPDATE, DELETE ON addon.tbl_lvinfostatus TO web;
			";

	if(!$db->db_query($qry))
		echo '<strong>tbl_lvinfostatus: '.$db->db_last_error().'</strong><br>';
	else
		echo '<br>Tabelle addon.tbl_lvinfostatus hinzugefügt!';

}

// Anlegen der Tabelle tbl_lvinfostatus_zuordnung
if(!$result = @$db->db_query("SELECT 1 FROM addon.tbl_lvinfostatus_zuordnung"))
{

	$qry = "
			CREATE TABLE addon.tbl_lvinfostatus_zuordnung
			(
				lvinfo_id integer NOT NULL,
				lvinfostatus_kurzbz varchar(32) NOT NULL,
				gesetztamum timestamp NOT NULL,
				uid varchar(32) NOT NULL,
				insertamum timestamp,
				insertvon varchar(32),
				updateamum timestamp,
				updatevon varchar(32)
			);

			ALTER TABLE addon.tbl_lvinfostatus_zuordnung ADD CONSTRAINT pk_lvinfostatus_zuordnung PRIMARY KEY (lvinfo_id);
			ALTER TABLE addon.tbl_lvinfostatus_zuordnung ADD CONSTRAINT fk_lvinfo FOREIGN KEY (lvinfo_id) REFERENCES addon.tbl_lvinfo(lvinfo_id) ON DELETE RESTRICT ON UPDATE CASCADE;
			ALTER TABLE addon.tbl_lvinfostatus_zuordnung ADD CONSTRAINT fk_lvinfostatus_kurzbz FOREIGN KEY (lvinfostatus_kurzbz) REFERENCES addon.tbl_lvinfostatus(lvinfostatus_kurzbz) ON DELETE RESTRICT ON UPDATE CASCADE;
			ALTER TABLE addon.tbl_lvinfostatus_zuordnung ADD CONSTRAINT fk_uid FOREIGN KEY (uid) REFERENCES public.tbl_benutzer(uid) ON DELETE RESTRICT ON UPDATE CASCADE;

			GRANT SELECT, INSERT, UPDATE, DELETE ON addon.tbl_lvinfostatus_zuordnung TO vilesci;
			GRANT SELECT, INSERT, UPDATE, DELETE ON addon.tbl_lvinfostatus_zuordnung TO web;
			";

	if(!$db->db_query($qry))
		echo '<strong>tbl_lvinfostatus_zuordnung: '.$db->db_last_error().'</strong><br>';
	else
		echo '<br>Tabelle addon.tbl_lvinfostatus_zuordnung hinzugefügt!';

}

echo '<br>Aktualisierung abgeschlossen<br><br>';
echo '<h2>Gegenprüfung</h2>';

$error=false;
// Liste der verwendeten Tabellen / Spalten des Addons
$tabellen=array(
	"addon.tbl_lvinfo_set"  => array("lvinfo_set_id","lvinfo_set_kurzbz","lvinfo_set_bezeichnung","sort","lvinfo_set_typ","gueltigab_studiensemester_kurzbz","oe_kurzbz","insertamum","insertvon","updateamum","updatevon"),
	"addon.tbl_lvinfo"  => array("lvinfo_id","sprache","lehrveranstaltung_id","studiensemester_kurzbz","data","insertamum","insertvon","updateamum","updatevon"),
	"addon.tbl_lvinfostatus"  => array("lvinfostatus_kurzbz","tbl_lvinfostatus_bezeichnung","insertamum","insertvon","updateamum","updatevon"),
	"addon.tbl_lvinfostatus_zuordnung"  => array("lvinfo_id","lvinfostatus_kurzbz","gesetztamum","uid","insertamum","insertvon","updateamum","updatevon"),
);

$tabs=array_keys($tabellen);
$i=0;
foreach ($tabellen AS $attribute)
{
	$sql_attr='';
	foreach($attribute AS $attr)
		$sql_attr.=$attr.',';
	$sql_attr=substr($sql_attr, 0, -1);

	if (!@$db->db_query('SELECT '.$sql_attr.' FROM '.$tabs[$i].' LIMIT 1;'))
	{
		echo '<BR><strong>'.$tabs[$i].': '.$db->db_last_error().' </strong><BR>';
		$error=true;
	}
	else
		echo $tabs[$i].': OK - ';
	flush();
	$i++;
}
if($error==false)
	echo '<br>Gegenpruefung fehlerfrei';
?>
