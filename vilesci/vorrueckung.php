<?php
/* Copyright (C) 2016 fhcomplete.org
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
 * Authors: Andreas Österreicher <andreas.oesterreicher@technikum-wien.at>
 */
 /**
  * Script zur Vorrückung von LV-Informationen in das Folgesemester
  */
require_once('../../../config/vilesci.config.inc.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../../../include/studiensemester.class.php');
require_once('../../../include/organisationseinheit.class.php');
require_once('../../../include/lehrveranstaltung.class.php');
require_once('../../../include/studienplan.class.php');
require_once('../include/lvinfo.class.php');

echo '<!DOCTYPE HTML>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" href="../../../skin/fhcomplete.css" type="text/css">
	<link rel="stylesheet" href="../../../skin/vilesci.css" type="text/css">
	<link rel="stylesheet" href="../../../skin/tablesort.css" type="text/css">
	<script type="text/javascript" src="../../../include/js/jquery1.9.min.js"></script>
	<title>LV-Information Vorrückung</title>
</head>
<body>
<h1>LV Informationen vorrücken</h1>
';

$uid = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($uid);

if(!$rechte->isBerechtigt('addon/lvinfoAdmin', null, 'suid'))
{
	die($rechte->errormsg);
}

$db = new basis_db();
$studiensemester_kurzbz_from = (isset($_POST['studiensemester_kurzbz_from'])?$_POST['studiensemester_kurzbz_from']:'');
$studiensemester_kurzbz_to = (isset($_POST['studiensemester_kurzbz_to'])?$_POST['studiensemester_kurzbz_to']:'');
$studiengang_kz = (isset($_POST['studiengang_kz'])?$_POST['studiengang_kz']:'');
$anzahl_kopiert = 0;

if($studiensemester_kurzbz_from == '')
{
	$stsem = new studiensemester();
	$studiensemester_kurzbz_from = $stsem->getPrevious();
}
if($studiensemester_kurzbz_to == '')
{
	$stsem = new studiensemester();
	$studiensemester_kurzbz_to = $stsem->jump($studiensemester_kurzbz_from, 2);
}

echo '<form action="vorrueckung.php" method="POST">
Studiengang:
<select name="studiengang_kz">';

$stg = new studiengang();
$stg->getAll('typ, kurzbz');

foreach($stg->result as $row)
{
	if($row->studiengang_kz == $studiengang_kz)
		$selected = 'selected';
	else
		$selected = '';
	echo '<option value="'.$db->convert_html_chars($row->studiengang_kz).'" '.$selected.'>'.
			$row->kuerzel.' '.$row->bezeichnung.
		'</option>';
}
echo '</select>';

echo ' Quelle: <select name="studiensemester_kurzbz_from" />';

$stsem = new studiensemester();
$stsem->getAll();

foreach($stsem->studiensemester as $row)
{
	if($row->studiensemester_kurzbz == $studiensemester_kurzbz_from)
		$selected = 'selected';
	else
		$selected = '';
	echo '<option value="'.$db->convert_html_chars($row->studiensemester_kurzbz).'" '.$selected.'>'.
			$db->convert_html_chars($row->studiensemester_kurzbz).
		'</option>';
}
echo '</select>';

echo ' Ziel:<select name="studiensemester_kurzbz_to" />';

$stsem = new studiensemester();
$stsem->getAll();

foreach($stsem->studiensemester as $row)
{
	if($row->studiensemester_kurzbz == $studiensemester_kurzbz_to)
		$selected = 'selected';
	else
		$selected = '';
	echo '<option value="'.$db->convert_html_chars($row->studiensemester_kurzbz).'" '.$selected.'>'.
			$db->convert_html_chars($row->studiensemester_kurzbz).
		'</option>';
}
echo '</select>';
echo '<input type="submit" value="Vorruecken" name="copy" />';
echo '</form>';

if(isset($_POST['copy']) && $studiengang_kz != '' && $studiensemester_kurzbz_from != '' && $studiensemester_kurzbz_to != '')
{
	echo '<br><br>
			Kopiere LV-Informationen von Studiengang '.$studiengang_kz.'
			von Studiensemester '.$studiensemester_kurzbz_from.' nach '.$studiensemester_kurzbz_to;

	$studienplan = new studienplan();
	$lvs = array();
	if($studienplan->getStudienplaeneFromSem($studiengang_kz, $studiensemester_kurzbz_to))
	{
		foreach($studienplan->result as $row_studienplan)
		{
			$studienplan_lv = new studienplan();
			if($studienplan_lv->loadStudienplanLV($row_studienplan->studienplan_id))
			{
				foreach($studienplan_lv->result as $row_studienplan_lv)
				{
					$lvs[] = $row_studienplan_lv->lehrveranstaltung_id;
				}
			}
		}
	}
	$lvs = array_unique($lvs);
	foreach($lvs as $lehrveranstaltung_id)
	{
		$lvinfo = new lvinfo();
		if($lvinfo->loadLvinfo($lehrveranstaltung_id, $studiensemester_kurzbz_from))
		{
			// alle vorhandenen Sprachen durchlaufen
			foreach($lvinfo->result as $row_lvinfo)
			{
				// Pruefen ob schon ein Eintrag vorhanden ist
				$lvinfo_test = new lvinfo();
				if(!$lvinfo_test->exists($lehrveranstaltung_id, $studiensemester_kurzbz_to, $row_lvinfo->sprache))
				{
					echo '<br>Kopiere '.$row_lvinfo->sprache.' von LV '.$lehrveranstaltung_id;
					$lvinfo_neu = new lvinfo();
					$lvinfo_neu->lehrveranstaltung_id = $lehrveranstaltung_id;
					$lvinfo_neu->studiensemester_kurzbz = $studiensemester_kurzbz_to;
					$lvinfo_neu->sprache = $row_lvinfo->sprache;
					$lvinfo_neu->data = $row_lvinfo->data;
					$lvinfo_neu->insertamum = date('Y-m-d H:i:s');
					$lvinfo_neu->insertvon = $uid;

					if($lvinfo_neu->save())
					{
						$lvinfo_neu_status = new lvinfo();
						if($lvinfo_neu_status->setStatus($lvinfo_neu->lvinfo_id, 'bearbeitung', $uid))
						{
							echo ' <span class="ok">OK</span>';
						}
					}
					else
						echo ' <span class="error">Failed:'.$lvinfo_neu->errormsg.'</span>';

					$anzahl_kopiert++;
				}
				else
				{
					echo '<br>
						<span>
							'.$row_lvinfo->sprache.' von LV '.$lehrveranstaltung_id.'
							wird nicht kopiert da bereits eine Version vorhanden ist
						</span>';
				}
			}
		}
	}
	echo '<br><br>Kopierte Einträge:'.$anzahl_kopiert;
}

echo '</body>
</html>';
