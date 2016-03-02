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
require_once('../../../config/vilesci.config.inc.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../../../include/studiensemester.class.php');
require_once('../../../include/organisationseinheit.class.php');
require_once('../../../include/lehrveranstaltung.class.php');
require_once('../include/lvinfo.class.php');

echo '<!DOCTYPE HTML>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" href="../../../skin/fhcomplete.css" type="text/css">
	<link rel="stylesheet" href="../../../skin/vilesci.css" type="text/css">
	<link rel="stylesheet" href="../../../skin/tablesort.css" type="text/css">
	<script type="text/javascript" src="../../../include/js/jquery1.9.min.js"></script>
	<title>LV-Information Copy</title>
</head>
<body>
<div id="data" style="display:none"></div>
';

$uid = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($uid);

if(!$rechte->isBerechtigt('addon/lvinfoAdmin', null, 'suid'))
{
	die($rechte->errormsg);
}

$db = new basis_db();

$quelle_studiengang_kz = filter_input(INPUT_POST, 'quelle_studiengang_kz');
$quelle_semester = filter_input(INPUT_POST, 'quelle_semester');
$quelle_studiensemester = filter_input(INPUT_POST, 'quelle_studiensemester');
$quelle_lehrveranstaltung_id = filter_input(INPUT_POST, 'quelle_lehrveranstaltung_id');

$ziel_studiengang_kz = filter_input(INPUT_POST, 'ziel_studiengang_kz');
$ziel_semester = filter_input(INPUT_POST, 'ziel_semester');
$ziel_studiensemester = filter_input(INPUT_POST, 'ziel_studiensemester');
$ziel_lehrveranstaltung_id = filter_input(INPUT_POST, 'ziel_lehrveranstaltung_id');

if($quelle_studiensemester=='')
{
	$studiensemester = new studiensemester();
	$quelle_studiensemester = $studiensemester->getaktorNext();
}
if($ziel_studiensemester=='')
{
	$studiensemester = new studiensemester();
	$ziel_studiensemester = $studiensemester->getaktorNext();
}

if(isset($_POST['action']))
{
	echo 'Kopiere LVInformation von '.$quelle_lehrveranstaltung_id.' ('.$quelle_studiensemester.') nach '.$ziel_lehrveranstaltung_id.' ('.$ziel_studiensemester.')';
	$lvinfo = new lvinfo();
	if($lvinfo->loadLvinfo($ziel_lehrveranstaltung_id, $ziel_studiensemester))
	{
		if(count($lvinfo->result)>0)
		{
			echo '<span class="error">Fehlgeschlagen: Es sind bereits LV-Infos für die Ziel Lehrveranstaltung vorhanden</span>';
		}
		else
		{
			$lvinfo = new lvinfo();
			if($lvinfo->loadLvinfo($quelle_lehrveranstaltung_id, $quelle_studiensemester))
			{
				foreach($lvinfo->result as $row_lvinfo)
				{
					echo '<br>Kopiere '.$row_lvinfo->sprache.' ';
					$lvinfo_neu = new lvinfo();
					$lvinfo_neu->lehrveranstaltung_id = $ziel_lehrveranstaltung_id;
					$lvinfo_neu->studiensemester_kurzbz = $ziel_studiensemester;
					$lvinfo_neu->sprache = $row_lvinfo->sprache;
					$lvinfo_neu->data = $row_lvinfo->data;
					$lvinfo_neu->insertamum = date('Y-m-d H:i:s');
					$lvinfo_neu->insertvon = $uid;
					if($lvinfo_neu->save())
					{
						// Status kopieren
						$lvinfo_neu_status = new lvinfo();
						if($lvinfo_neu_status->getLastStatus($row_lvinfo->lvinfo_id))
						{
							if($lvinfo_neu_status->setStatus($lvinfo_neu->lvinfo_id,$lvinfo_neu_status->lvinfostatus_kurzbz,$uid))
							{
								echo '<span class="ok">OK</span>';
							}
						}
					}
					else
						echo '<span class="error">Failed:'.$lvinfo_neu->errormsg.'</span>';

				}
			}
		}
	}
}

$studiengang = new studiengang();
$studiengang->getAll('typ, kurzbz');

foreach($studiengang->result as $row)
{
	$stg_arr[$row->studiengang_kz]['max_semester']=$row->max_semester;
}
echo '<h1>Kopieren von LV-Informationen</h1>';
echo '
<form action="copy.php" method="POST" name="auswahlFrm">';

echo '<h2>Quell Lehrveranstaltung</h2>';

printAuswahl('quelle',$quelle_studiengang_kz, $quelle_semester,$quelle_studiensemester,$quelle_lehrveranstaltung_id);


echo '<h2>Ziel Lehrveranstaltung</h2>';
printAuswahl('ziel',$ziel_studiengang_kz, $ziel_semester,$ziel_studiensemester,$ziel_lehrveranstaltung_id);

echo '
<br />
<br />
<input type="submit" name="action" value="LV-Information kopieren" />
</form>
';

echo '
</body>
</html>';

/**
 * Erstellt das Auswahlmenue fuer die Lehrveranstaltungen
 */
function printAuswahl($typ, &$studiengang_kz, &$semester, &$studiensemester, &$lehrveranstaltung_id)
{
	global $studiengang, $stg_arr, $db;
	echo '
	<table>
	<tr>
		<td>Studiengang:</td>
		<td><select name="'.$typ.'_studiengang_kz" onchange="window.document.auswahlFrm.submit()">';
	foreach($studiengang->result as $row_stg)
	{
		if($studiengang_kz=='')
			$studiengang_kz = $row_stg->studiengang_kz;
		if($studiengang_kz==$row_stg->studiengang_kz)
			$selected='selected';
		else
			$selected='';
		echo '<option value="'.$row_stg->studiengang_kz.'" '.$selected.'>'.$row_stg->kuerzel.' ('.$row_stg->bezeichnung.')</option>';
	}
	echo '
	</select></td>
	</tr>
	<tr>
		<td>Semester:</td>
		<td><select name="'.$typ.'_semester" onchange="window.document.auswahlFrm.submit()">';

	for($i=1;$i<=$stg_arr[$studiengang_kz]['max_semester'];$i++)
	{
		if($semester=='')
			$semester=$i;

		if($i==$semester)
			$selected='selected';
		else
			$selected='';

		echo '<option value="'.$i.'" '.$selected.'>'.$i.'</option>';
	}

	echo '
	</select></td>
	</tr>
	<tr>
		<td>Studiensemester:</td>
		<td><select name="'.$typ.'_studiensemester" onchange="window.document.auswahlFrm.submit()">';

	$studiensemester_obj = new studiensemester();
	$studiensemester_obj->getAll('desc');

	foreach($studiensemester_obj->studiensemester as $row_stsem)
	{
		if($studiensemester==$row_stsem->studiensemester_kurzbz)
			$selected = 'selected';
		else
			$selected = '';

		echo '<option value="'.$row_stsem->studiensemester_kurzbz.'" '.$selected.'>'.$row_stsem->studiensemester_kurzbz.'</option>';
	}

	echo '
	</select></td>
	</tr>
	<tr>
		<td>Lehrveranstaltung</td>
		<td><select name="'.$typ.'_lehrveranstaltung_id" onchange="window.document.auswahlFrm.submit()">';
	$lv_obj = new lehrveranstaltung();
	if($lv_obj->load_lva($studiengang_kz,$semester,null,true,true))
	{
		foreach($lv_obj->lehrveranstaltungen as $row_lv)
		{
			if($lehrveranstaltung_id=='')
				$lehrveranstaltung_id=$row_lv->lehrveranstaltung_id;

			if($row_lv->lehrveranstaltung_id==$lehrveranstaltung_id)
				$selected = 'selected';
			else
				$selected = '';

			echo '<option value="'.$row_lv->lehrveranstaltung_id.'" '.$selected.'>'.$db->convert_html_chars($row_lv->bezeichnung).' '.$db->convert_html_chars($row_lv->orgform_kurzbz).' ('.$row_lv->lehrveranstaltung_id.')</option>';
		}
	}
	echo '</select></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>';

	$lvinfo = new lvinfo();
	if($lvinfo->loadLvinfo($lehrveranstaltung_id, $studiensemester))
	{
		if(count($lvinfo->result)>0)
		{
			echo '<span class="ok">LVinfo gefunden</span>';
		}
		else
		{
			echo '<span class="error">Keine LVInformation für diese LV gefunden</span>';
		}
	}
	echo '</td>
	</tr>
	</table>';
}
?>
