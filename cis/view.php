<?php
/* Copyright (C) 2015 fhcomplete.org
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
 * Authors: Manfred Kindl <manfred.kindl@technikum-wien.at>,
 * 			Andreas Österreicher <andreas.oesterreicher@technikum-wien.at>
 */
/*
 * Zeigt die Unterschiede zwischen der Uebergebenen und
 * der zuletzt freigegebenen Version an
 */
require_once('../../../config/cis.config.inc.php');
require_once('../../../config/global.config.inc.php');
require_once('../../../include/globals.inc.php');
require_once('../../../include/basis_db.class.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/phrasen.class.php');
require_once('../../../include/lehrveranstaltung.class.php');
require_once('../../../include/studiengang.class.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../../../include/organisationsform.class.php');
require_once('../../../include/studiensemester.class.php');
require_once('../../../include/sprache.class.php');
require_once('../../../include/mail.class.php');
require_once('../../../include/benutzer.class.php');
require_once('../../../include/benutzerfunktion.class.php');
require_once('../../../include/lehreinheitmitarbeiter.class.php');
require_once('../../../include/vertrag.class.php');
require_once('../include/lvinfo.class.php');
require_once('../include/functions.inc.php');
require_once('../vendor/autoload.php');
require_once('../lvinfo.config.inc.php');

// $uid = get_uid(); Fuehrt zu Problemen bei der Incoming-Plattform, weil diese noch keinen Benutzer haben

$sprache = getSprache();
$p = new phrasen($sprache);
$db = new basis_db();

$lehrveranstaltung_id = filter_input(INPUT_GET, 'lehrveranstaltung_id');
$studiensemester_kurzbz = filter_input(INPUT_GET, 'studiensemester_kurzbz');
$lvinfo_sprache = filter_input(INPUT_GET, 'sprache');
$lvinfo_id = filter_input(INPUT_GET, 'lvinfo_id');

if($lvinfo_sprache=='')
	$lvinfo_sprache=$sprache;
$lvinfo = new lvinfo();

if($lvinfo_id=='' && $studiensemester_kurzbz!='')
{
	$lvinfo->loadLvinfo($lehrveranstaltung_id, $studiensemester_kurzbz, $lvinfo_sprache, true);
	if(isset($lvinfo->result[0]))
		$lvinfo = $lvinfo->result[0];
	else
	{
		die('Derzeit sind keine Informationen verfügbar');
	}
}
elseif($lvinfo_id=='' && $lehrveranstaltung_id!='')
{
	if($lvinfo->loadLastLvinfo($lehrveranstaltung_id, true))
	{
		if(isset($lvinfo->result[0]))
			$lvinfo = $lvinfo->result[0];
		else
			die('Derzeit sind keine Informationen verfügbar');
	}
	else
	{
		die('Derzeit sind keine Informationen verfügbar');
	}
}
else
{
	if(!$lvinfo->load($lvinfo_id))
	{
		die('Eintrag wurde nicht gefunden');
	}
}

echo '<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>'.$p->t('lvinfo/lvinformationen').'</title>
	<link rel="stylesheet" href="../../../skin/style.css.php" type="text/css">
	<link rel="stylesheet" href="../../../skin/tablesort.css" type="text/css">
	<link rel="stylesheet" href="../skin/lvinfo.css" type="text/css">

	<script type="text/javascript" src="../../../vendor/jquery/jqueryV1/jquery-1.12.4.min.js"></script>
	<script type="text/javascript" src="../../../vendor/christianbach/tablesorter/jquery.tablesorter.min.js"></script>
</head>
<body>
';
echo '<h1>'.$p->t('lvinfo/lehrveranstaltungsinformationen').'</h1>';

// Link zu den unterschiedlichen Sprachen anzeigen
$sprachenlinks='';
$sprache_obj = new sprache();
$sprache_obj->getAll(true);
foreach($sprache_obj->result as $row)
{
	$sprache_arr[$row->sprache]=$row->bezeichnung_arr[$sprache];
}

$lvinfosprachen = new lvinfo();
if($lvinfosprachen->loadLvinfo($lvinfo->lehrveranstaltung_id, $lvinfo->studiensemester_kurzbz, null, true))
{
	foreach($lvinfosprachen->result as $row)
	{
		$sprachenlinks .= ' <a href="view.php?lehrveranstaltung_id='.$lvinfo->lehrveranstaltung_id.'&studiensemester_kurzbz='.$lvinfo->studiensemester_kurzbz.'&sprache='.$row->sprache.'">'.$sprache_arr[$row->sprache].'</a>';
	}
}
if($sprachenlinks!='')
	echo $p->t('lvinfo/verfuegbareSprachen').':'.$sprachenlinks;

// Tabelle mit Informationen zur LV anzeigen
printInfoTable($lvinfo->lehrveranstaltung_id, $lvinfo->studiensemester_kurzbz, $sprache);

$lvinfo_set = new lvinfo();
$lvinfo_set->load_lvinfo_set($lvinfo->studiensemester_kurzbz);

// Ausgabe der Felder
echo '<div class="lvinfo">';
foreach($lvinfo_set->result as $row_set)
{
	$key = $row_set->lvinfo_set_kurzbz;
	if(!isset($lvinfo->data[$key]))
		continue;

	$header='<h2>'.$row_set->lvinfo_set_bezeichnung[$lvinfo->sprache].'</h2>';
	if(isset($row_set->einleitungstext[$lvinfo->sprache]))
		$header.=$row_set->einleitungstext[$lvinfo->sprache];

	$body='';
	switch($row_set->lvinfo_set_typ)
	{
		case 'boolean':
			$p1 = new phrasen($lvinfo->sprache);

			if(isset($lvinfo->data[$key]) && $lvinfo->data[$key]===true)
				$body.= $p1->t('global/ja');
			else
				$body.= $p1->t('global/nein');
			break;

		case 'array':
			if(isset($lvinfo->data[$key]))
				$value=$lvinfo->data[$key];
			else
				$value=array();

			$body.= '<ul>';
			foreach($value as $val)
				$body.= '<li>'.$db->convert_html_chars($val).'</li>';
			$body.= '</ul>';
			break;

		case 'editor':
			if(isset($lvinfo->data[$key]))
				$body.= $lvinfo->data[$key];
			break;

		case 'text':
		default:
			if(isset($lvinfo->data[$key]))
				 $body.= $db->convert_html_chars($lvinfo->data[$key]);
	}

	if($body!='')
	{
		echo $header;
		echo '<div class="lvinfo_data">';
		echo $body;
		echo '</div>';
	}
}
echo '</div>';


?>
