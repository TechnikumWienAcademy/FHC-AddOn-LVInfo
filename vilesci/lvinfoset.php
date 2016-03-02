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
require_once('../include/lvinfo.class.php');

echo '<!DOCTYPE HTML>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" href="../../../skin/fhcomplete.css" type="text/css">
	<link rel="stylesheet" href="../../../skin/vilesci.css" type="text/css">
	<link rel="stylesheet" href="../../../skin/tablesort.css" type="text/css">
	<script type="text/javascript" src="../../../include/js/jquery1.9.min.js"></script>
	<title>LV-Information</title>
	<script type="text/javascript">
	function deleteSet(id)
	{
		if(confirm("Wollen Sie diesen Eintrag wirklich löschen"))
		{

			$("#data").html(\'<form action="lvinfoset.php" name="sendform" id="sendform" method="POST"><input type="hidden" name="action" value="deleteSet" /><input type="hidden" name="lvinfo_set_id" value="\'+id+\'" /></form>\');
			document.sendform.submit();
		}
		return false;
	}
	</script>
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

$sprache = new sprache();
$sprache->getAll(true);
$db = new basis_db();


if(isset($_REQUEST['action']))
	$action = $_REQUEST['action'];
else
	$action='';

if(isset($_REQUEST['studiensemester_kurzbz']))
	$studiensemester_kurzbz = $_REQUEST['studiensemester_kurzbz'];
else
{
	$stsem = new studiensemester();
	$studiensemester_kurzbz=$stsem->getaktorNext();
}

switch($action)
{
	case 'deleteSet':

		$set = new lvinfo();
		if($set->deleteSet($_POST['lvinfo_set_id']))
			echo '<span class="ok">Eintrag gelöscht</span>';
		else
			echo '<span class="error">Fehler beim Löschen:'.$set->errormsg.'</span>';

		break;
	case 'saveSet':

		$set = new lvinfo();

		if($_POST['lvinfo_set_id']!='' && !$set->loadSet($_POST['lvinfo_set_id']))
		{
			die($set->errormsg);
		}
		else
		{
			$set->insertamum = date('Y-m-d H:i:s');
			$set->insertvon = $uid;
		}

		$set->lvinfo_set_kurzbz = $_POST['lvinfo_set_kurzbz'];
		$set->lvinfo_set_typ = $_POST['lvinfo_set_kurzbz'];
		$set->oe_kurzbz = $_POST['oe_kurzbz'];
		$set->lvinfo_set_typ = $_POST['lvinfo_set_typ'];
		$set->sort = $_POST['sort'];

		$sprache = new sprache();
		$sprache->getAll(true);

		foreach($sprache->result as $row_sprache)
		{
			if(isset($_POST['bezeichnung'.$row_sprache->sprache]))
				$set->lvinfo_set_bezeichnung[$row_sprache->sprache]=$_POST['bezeichnung'.$row_sprache->sprache];
		}
		$set->gueltigab_studiensemester_kurzbz = $_POST['gueltigab_studiensemester_kurzbz'];
		$set->updateamum = date('Y-m-d H:i:s');
		$set->updatevon = $uid;

		if($set->saveSet())
			echo '<span class="ok">Eintrag gespeichert</span>';
		else
			echo '<span class="error">Fehler beim Speichern:'.$set->errormsg.'</span>';

		break;
	default:
		break;
}

echo '<h1>Übersicht</h1>';

$oe = new organisationseinheit();
$oe->getAll();
$oe_arr=array();
foreach($oe->result as $row)
	$oe_arr[$row->oe_kurzbz]=$row->organisationseinheittyp_kurzbz.' '.$row->bezeichnung;

$stsem = new studiensemester();
$stsem->getAll('desc');

echo '<form action="lvinfoset.php" method="POST">';
echo 'Studiensemester: <select name="studiensemester_kurzbz">';
$studiensemester_arr = array();
foreach($stsem->studiensemester as $row)
{
	if($row->studiensemester_kurzbz==$studiensemester_kurzbz)
		$selected = 'selected';
	else
		$selected ='';
	echo '<option value="'.$db->convert_html_chars($row->studiensemester_kurzbz).'" '.$selected.'>'.$db->convert_html_chars($row->bezeichnung).'</option>';
	$studiensemester_arr[]=$row;
}
echo '</select>
<input type="submit" value="Anzeigen" /></form>';

$set = new lvinfo();
if(!$set-> load_lvinfo_set($studiensemester_kurzbz))
	die($set->errormsg);

echo '<script>
$(document).ready(function()
{
	$("#t1").tablesorter(
	{
		sortList: [[1,0]],
		widgets: ["zebra"]
	});
});
</script>
';

echo '<form action="lvinfoset.php" method="POST">';
echo '<table class="tablesorter" id="t1">
		<thead>
		<tr>
			<th>ID</th>
			<th>Sort</th>
			<th>Typ</th>
			<th>Kurzbz</th>
			<th>Bezeichnung</th>
			<th>Org.Einheit</th>
			<th>Gültig ab</th>
			<th>Aktion</th>
		</tr>
		</thead>
		<tbody>';
$maxsort=0;
foreach($set->result as $row)
{
	if($maxsort<$row->sort)
		$maxsort=$row->sort;
	echo '<tr>';
	echo '<td>'.$db->convert_html_chars($row->lvinfo_set_id).'</td>';
	echo '<td>'.$db->convert_html_chars($row->sort).'</td>';
	echo '<td>'.$db->convert_html_chars($row->lvinfo_set_typ).'</td>';
	echo '<td>'.$db->convert_html_chars($row->lvinfo_set_kurzbz).'</td>';
	echo '<td>'.$db->convert_html_chars($row->lvinfo_set_bezeichnung[DEFAULT_LANGUAGE]).'</td>';
	echo '<td>'.$db->convert_html_chars((isset($oe_arr[$row->oe_kurzbz])?$oe_arr[$row->oe_kurzbz]:$row->oe_kurzbz)).'</td>';
	echo '<td>'.$db->convert_html_chars($row->gueltigab_studiensemester_kurzbz).'</td>';
	echo '<td>
			<a href="#delete" onclick="deleteSet(\''.$row->lvinfo_set_id.'\');return false;"><img src="../../../skin/images/delete.png" height="20px"/></a>
			<a href="lvinfoset.php?action=editSet&studiensemester_kurzbz='.$studiensemester_kurzbz.'&id='.$row->lvinfo_set_id.'"><img src="../../../skin/images/edit.png" height="20px"/></a>
		</td>';
	echo '</tr>';
}

$set = new lvinfo();
if($action=='editSet')
{

	if(!$set->loadSet($_GET['id']))
	{
		die($set->errormsg);
	}
}
else
	$set->sort = ($maxsort+10);

echo '</tbody>
</table>';

echo '
<input type="hidden" name="action" value="saveSet" />
<input type="hidden" name="lvinfo_set_id" value="'.$set->lvinfo_set_id.'" />
<table>
<tr>
	<td>Sort</td>
	<td valign="top">
		<input type="text" name="sort" size="3" value="'.$db->convert_html_chars($set->sort).'" />
	</td>
</tr>
<tr>
	<td>Typ</td>
	<td valign="top">
		<select name="lvinfo_set_typ">
			<option value="text" '.($set->lvinfo_set_typ=='text'?'selected="selected"':'').'>Freitext</option>
			<option value="array" '.($set->lvinfo_set_typ=='array'?'selected="selected"':'').'>Array</option>
			<option value="boolean" '.($set->lvinfo_set_typ=='boolean'?'selected="selected"':'').'>Boolean</option>
		</select>
	</td>
</tr>
<tr>
	<td>Kurzbz</td>
	<td valign="top">
		<input type="text" maxlength="16" name="lvinfo_set_kurzbz" value="'.$db->convert_html_chars($set->lvinfo_set_kurzbz).'" /> Keine Leerzeichen, keine Sonderzeichen
	</td>
</tr>';

foreach($sprache->result as $s)
{
 		echo '<tr><td>Bezeichnung '.$s->sprache.'</td><td>';
 		echo '<input type="text" size="60" maxlength="64" name="bezeichnung'.$s->sprache.'" value="'.(isset($set->lvinfo_set_bezeichnung[$s->sprache])?$db->convert_html_chars($set->lvinfo_set_bezeichnung[$s->sprache]):'').'" /></td></tr>';
}

echo '
<tr>
	<td>Organisationseinheit</td>
	<td valign="top">
		<select name="oe_kurzbz">';

		foreach($oe_arr as $oe_kurzbz=>$bezeichnung)
		{
			if($set->oe_kurzbz == $oe_kurzbz)
				$selected = 'selected';
			else
				$selected = '';
			echo '<option value="'.$db->convert_html_chars($oe_kurzbz).'" '.$selected.'>'.$db->convert_html_chars($bezeichnung).'</option>';
		}
echo '
		</select>
	</td>
</tr>
<tr>
	<td>Gültig ab</td>
	<td valign="top">
		<select name="gueltigab_studiensemester_kurzbz">';
		foreach($studiensemester_arr as $row)
		{
			if($set->gueltigab_studiensemester_kurzbz=='')
				$set->gueltigab_studiensemester_kurzbz = $studiensemester_kurzbz;
			if($set->gueltigab_studiensemester_kurzbz==$row->studiensemester_kurzbz)
				$selected = 'selected';
			else
				$selected = '';
			echo '<option value="'.$db->convert_html_chars($row->studiensemester_kurzbz).'" '.$selected.'>'.$db->convert_html_chars($row->bezeichnung).'</option>';
		}
echo '
		</select>
	</td>
</tr>
<tr>
	<td></td>
	<td valign="top">
		<input type="submit" name="saveSet" value="Speichern" />
	</td>
</tr>
</table><br><br>
</form>';


echo '
</body>
</html>';
?>
