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
 * Authors: Manfred Kindl <manfred.kindl@technikum-wien.at>
 */
/*
 * Oberfläche für LektorInnen zur Verwaltung der LVInfos
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
require_once('../include/lvinfo.class.php');

/*
require_once('../../../include/benutzer.class.php');
require_once('../../../include/phrasen.class.php');
require_once('../../../include/studiensemester.class.php');
require_once('../../../include/datum.class.php');
require_once('../../../include/mitarbeiter.class.php');
require_once('../../../include/lehreinheitmitarbeiter.class.php');
require_once('../../../include/lehrveranstaltung.class.php');
require_once('../../../include/lehreinheit.class.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../../../include/bisverwendung.class.php');
require_once('../../../include/vertrag.class.php');
require_once('../../../include/stunde.class.php');
*/

if (!$db = new basis_db())
  die('Fehler beim Oeffnen der Datenbankverbindung');

$user = get_uid();

if(!check_lektor($user))
    die('Diese Seite ist nur fuer Lektoren zugänglich');

$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($user);

$sprache = getSprache();
$p = new phrasen($sprache);

$datum_obj = new datum();
//$studiengang = new studiengang();
//$studiengang->getAll(null, false);
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title><?php echo $p->t('lvinfo/lvinformationen'); ?></title>
	<link rel="stylesheet" href="../../../skin/style.css.php" type="text/css">
    <link rel="stylesheet" href="../../../skin/tablesort.css" type="text/css">
    <script src="../../../include/js/jquery1.9.min.js" type="text/javascript"></script>
    <script>
    $(document).ready(function()
    {
    	$("#termine").tablesorter(
    	{
    		sortList: [[1,0]],
    		widgets: ["zebra"]
    	});
    });
    </script>
</head>
<body style="padding: 10px">
<?php
//$stsem = new studiensemester();
//$studiensemester_kurzbz = $stsem->getNearest();

$lv_id = isset($_REQUEST['lv_id'])?$_REQUEST['lv_id']:'';
//Wenn eine LV_ID übergeben wurde aber keine anderen Parameter, werden diese mit den Daten der LV befüllt
$lv_obj = new lehrveranstaltung();
$lv_obj->load($lv_id);

$stsem = new studiensemester();
$stsem = $stsem->getakt();

$stg_kz = isset($_GET['stg_kz']) && $_GET['stg_kz']!=''?$_GET['stg_kz']:($lv_obj->studiengang_kz!=''?$lv_obj->studiengang_kz:'0');
$semester = isset($_GET['semester']) && $_GET['semester']!=''?$_GET['semester']:($lv_obj->semester!=''?$lv_obj->semester:'1');
$orgform_kurzbz = isset($_GET['orgform_kurzbz']) && $_GET['orgform_kurzbz']!=''?$_GET['orgform_kurzbz']:$lv_obj->orgform_kurzbz;
$studiensemester_kurzbz = isset($_REQUEST['studiensemester_kurzbz'])?$_REQUEST['studiensemester_kurzbz']:$stsem;
	
$errormsg = '';
$data_german = '';
$data_english = '';

if(isset($_POST['save']) || isset($_POST['save_new']))
{
	//if(!$rechte->isBerechtigt('lehre/lehrveranstaltung',null,'sui')) @todo Rechte setzen;
	//	die('Sie haben keine Berechtigung fuer diese Aktion');
			
	//Formulardaten fuer Json-Encode vorbereiten
	$data_german = $_POST['german'];
	$data_english = $_POST['english'];
	//echo json_encode($data_german).'<br>';
	//echo json_encode($data_english);
	
	if($data_german!='')
	{
		$lvinfo = new lvinfo();
		
		if(isset($_POST["save_new"]))
		{
			$lvinfo->new=true;
			$lvinfo->insertamum=date('Y-m-d H:i:s');
			$lvinfo->insertvon = $user;
			$lvinfo->updateamum=date('Y-m-d H:i:s');
			$lvinfo->updatevon = $user;
		}
		elseif(isset($_POST['lvinfo_id']) && $_POST['lvinfo_id']!='')
		{
			if($lvinfo->load($_POST['lvinfo_id']))
			{
				$lvinfo->new=false;
				$lvinfo->updateamum=date('Y-m-d H:i:s');
				$lvinfo->updatevon = $user;
			}
			else
			{
				die('Fehler beim Laden der LV-Info');
			}
		}
		else
		{
			$lvinfo->new=true;
			$lvinfo->insertamum=date('Y-m-d H:i:s');
			$lvinfo->insertvon = $user;
			$lvinfo->updateamum=date('Y-m-d H:i:s');
			$lvinfo->updatevon = $user;
		}
		
		$lvinfo->sprache = 'German';
		$lvinfo->lehrveranstaltung_id = $lv_id;
		$lvinfo->studiensemester_kurzbz = $studiensemester_kurzbz;
		$lvinfo->data = $data_german;
		
		if(!$lvinfo->save())
			$errorstr = "Fehler beim Speichern der Daten: $lvinfo->errormsg";
		else
		{
		
		}
	}
	if($data_english!='')
	{
		$lvinfo = new lvinfo();
		
		if(isset($_POST["save_new"]))
		{
			$lvinfo->new=true;
			$lvinfo->insertamum=date('Y-m-d H:i:s');
			$lvinfo->insertvon = $user;
			$lvinfo->updateamum=date('Y-m-d H:i:s');
			$lvinfo->updatevon = $user;
		}
		elseif(isset($_POST['lvinfo_id']) && $_POST['lvinfo_id']!='')
		{
			if($lvinfo->load($_POST['lvinfo_id']))
			{
				$lvinfo->new=false;
				$lvinfo->updateamum=date('Y-m-d H:i:s');
				$lvinfo->updatevon = $user;
			}
			else
			{
				die('Fehler beim Laden der LV-Info');
			}
		}
		else
		{
			$lvinfo->new=true;
			$lvinfo->insertamum=date('Y-m-d H:i:s');
			$lvinfo->insertvon = $user;
			$lvinfo->updateamum=date('Y-m-d H:i:s');
			$lvinfo->updatevon = $user;
		}
		
		$lvinfo->sprache = 'English';
		$lvinfo->lehrveranstaltung_id = $lv_id;
		$lvinfo->studiensemester_kurzbz = $studiensemester_kurzbz;
		$lvinfo->data = $data_english;
		
		if(!$lvinfo->save())
			$errorstr = "Fehler beim Speichern der Daten: $lvinfo->errormsg";
		else
		{
		
		}
	}
}

$lv = new lehrveranstaltung();
$lv->load($lv_id);
$stg = new studiengang();
$stg->load($lv->studiengang_kz);

echo '<H1>'.$p->t('lvinfo/lehrveranstaltungsinformationen').' - '.$stg->kurzbzlang.'-'.$lv->semester.($lv->orgform_kurzbz!=''?'-'.$lv->orgform_kurzbz:'').' - '.$lv->bezeichnung.'</H1>';
echo '<form name="auswahlFrm" action="'.$_SERVER['PHP_SELF'].'" method="GET">';

$stg_obj = new studiengang();

//Anzeigen des DropDown Menues mit Stg
if($stg_obj->getAll('typ, kurzbzlang',true))
{
	echo $p->t('global/studiengang').' <SELECT name="stg_kz" onChange="window.document.auswahlFrm.submit();">';

	//DropDown Menue mit den Stg füllen
	foreach($stg_obj->result as $row)
	{
		$selected = '';
		if($row->studiengang_kz==$stg_kz)
			$selected = 'selected';
		
		echo '<option value="'.$row->studiengang_kz.'" '.$selected.'>'.$row->kurzbzlang.' ('.$row->bezeichnung.')</option>';
	}
	echo '</SELECT><br/>';

}
else
{
	$errormsg .= "$stg_obj->errormsg";
}

//Anzeigen des DropDown Menues mit Semester
if($stg_obj->load($stg_kz))
{
	echo $p->t('global/semester').' <SELECT name="semester" onChange="window.document.auswahlFrm.submit();">';
	echo '<option value="">*</option>';
	for($i=1;$i<=$stg_obj->max_semester;$i++)
	{
		$selected = '';
		if($i==$semester)
			$selected = 'selected';
		
		echo '<option value="'.$i.'" '.$selected.'>'.$i.'</option>';

	}
	echo '</SELECT><br/>';
}
else
	$errormsg .= "$stg_obj->errormsg";
	
//Anzeigen des DropDown Menues mit Orgform
$orgform_obj = new organisationsform();
if($orgform_obj->getOrgformLV())
{
	echo $p->t('lvinfo/organisationsform').' <SELECT name="orgform_kurzbz" onChange="window.document.auswahlFrm.submit();">';
	echo '<option value="">*</option>';
	foreach($orgform_obj->result as $row)
	{
		$selected = '';
		if($row->orgform_kurzbz==$orgform_kurzbz)
			$selected = 'selected';
		
		echo '<option value="'.$row->orgform_kurzbz.'" '.$selected.'>'.$row->orgform_kurzbz.' ('.$row->bezeichnung.')</option>';
	}
	echo '</SELECT><br/>';
}
else
	$errormsg .= "$orgform_obj->errormsg";
	
//Anzeigen des DropDown Menues mit Studiensemester
$studiensemester = new studiensemester();
$akt_studiensemester = $studiensemester->getakt();
if($studiensemester->getPlusMinus(1,10))
{
	echo $p->t('global/studiensemester').' <SELECT name="studiensemester_kurzbz" onChange="window.document.auswahlFrm.submit();">';

	foreach($studiensemester->studiensemester as $row)
	{
		$selected = '';
		if($row->studiensemester_kurzbz==$studiensemester_kurzbz)
			$selected = 'selected';
		elseif ($studiensemester_kurzbz=='' && $row->studiensemester_kurzbz==$akt_studiensemester)
			$selected = 'selected';

		echo '<option value="'.$row->studiensemester_kurzbz.'" '.$selected.'>'.$row->studiensemester_kurzbz.'</option>';
	}
	echo '</SELECT><br/>';
}
else
	$errormsg .= "$studiensemester->errormsg";
	


//Anzeigen des DropDown Menues mit Lehrveranstaltungen

$order = 'orgform_kurzbz,semester,bezeichnung';
$outputstring = '';
if($semester!='' && $orgform_kurzbz=='')
	$order = 'orgform_kurzbz,bezeichnung';
if($semester=='' && $orgform_kurzbz!='')
	$order = 'semester,bezeichnung';
if($semester!='' && $orgform_kurzbz!='')
	$order = 'bezeichnung';

$lv_obj = new lehrveranstaltung();
if($lv_obj->load_lva($stg_kz,$semester,null,true,true,$order,null,null,$orgform_kurzbz))
{
	echo $p->t('global/lehrveranstaltung').' <SELECT name="lv_id" onChange="window.document.auswahlFrm.submit();">';

	if(count($lv_obj->lehrveranstaltungen)>0)
	{
		//Wenn die übergebene LV_ID nicht in der Liste der geladenen Objekte ist, zusätzlich anzeigen
		$lv_ids = array();
		foreach($lv_obj->lehrveranstaltungen as $row)
		{
			$lv_ids[] .= $row->lehrveranstaltung_id; 
		}
		if($lv_id!='' && !in_array($lv_id, $lv_ids))
		{
			$lv = new lehrveranstaltung();
			$lv->load($lv_id);
			if($lv->semester!='' && $lv->orgform_kurzbz=='')
				$outputstring = $lv->semester.' - ';
			if($lv->semester=='' && $lv->orgform_kurzbz!='')
				$outputstring = $lv->orgform_kurzbz.' - ';
			if($lv->semester!='' && $lv->orgform_kurzbz!='')
				$outputstring = $lv->orgform_kurzbz.' - '.$lv->semester.' - ';
			echo '<option value="'.$lv_id.'" selected>'.$outputstring.$lv->bezeichnung.'</option>';
		}
		$outputstring = '';
		foreach($lv_obj->lehrveranstaltungen as $row)
		{
			$selected = '';
			if($row->lehrveranstaltung_id==$lv_id)
				$selected = 'selected';
			if($semester!='' && $orgform_kurzbz=='' && $row->orgform_kurzbz!='')
				$outputstring = $row->orgform_kurzbz.' - ';
			if($semester=='' && $orgform_kurzbz!='')
				$outputstring = $row->semester.' - ';
			if($semester!='' && $orgform_kurzbz!='')
				$outputstring = '';
			
			echo '<option value="'.$row->lehrveranstaltung_id.'" '.$selected.'>'.$outputstring.$row->bezeichnung.'</option>';
		}
	}
	else 
		echo '<option value="">Keine Lehrveranstaltungen für diese Auswahl vorhanden</option>';
	echo '</SELECT><br/>';
}
else
{
	$errormsg .= "$lv_obj->errormsg";
}

echo '<input type="submit" value="'.$p->t('global/anzeigen').'">';
echo '</form>';

echo '<form name="editFrm" action="'.$_SERVER['PHP_SELF'].'?lv_id='.$lv_id.'" method="POST">';
echo '<table width="100%">
		<tr>';
echo '<td colspan="2">Deutsch</td>';
echo '<td>&nbsp;</td>';
echo '<td colspan="2">Englisch</td>';
echo '</tr>';

//Wenn LV-Info für das gewählte Studiensemester vorhanden, diese laden, sonst leeres Set anzeigen
$lvinfo = new lvinfo();
$lvinfo->loadLvinfo($lv_id, $studiensemester_kurzbz);

//Arrays nach unterschieden vergleichen
$lvinfo_array = array();
$lvinfo_compare = array();
foreach($lvinfo->result AS $row)
{
	$lang=$row->sprache;
	foreach($row->data AS $key=>$value)
	{
		@$lvinfo_array[$lang][$key] .= $value; //@todo: undefined index notice, wenn @ weg
		$lvinfo_compare[] .= $key;
	}
}
$lvinfo_compare=array_unique($lvinfo_compare);
//var_dump($lvinfo_compare);


$lvinfo_set = new lvinfo();
$lvinfo_set->load_lvinfo_set($studiensemester_kurzbz);

$set_compare = array();

foreach($lvinfo_set->result as $row)
{
	$set_compare[] .= $row->lvinfo_set_kurzbz;
}

//var_dump($set_compare);

$imSetAberNichtInInfo = array();
$imSetAberNichtInInfo = array_diff($set_compare, $lvinfo_compare);
$inInfoAberNichtImSet = array();
$inInfoAberNichtImSet = array_diff($lvinfo_compare, $set_compare);

//echo 'imSetAberNichtInInfo';
//var_dump($imSetAberNichtInInfo);
//echo 'inInfoAberNichtImSet';
//var_dump($inInfoAberNichtImSet);

// Datena ausgeben
var_dump($lvinfo->result);

foreach($lvinfo->result AS $row)
{
	
	//$stdsem=$row->studiensemester_kurzbz;
	//Elementnamen werden dem Formular als Arrays uebergeben
	//echo '<tr>';
	//echo '<td>'.$row->sprache.'</td><td>';
	foreach($row->data AS $key=>$value)
	{
		$setInfo = new lvinfo();
		$setInfo->load_lvinfo_set_kurzbz($key,$row->studiensemester_kurzbz);
		
		echo '<tr><td>'.$setInfo->lvinfo_set_bezeichnung[$row->sprache].'</td><td>';
		
		if($setInfo->lvinfo_set_typ=='text')
			echo '<textarea name="german['.$key.']" rows="5" cols="50">'.$value.'</textarea>';
		elseif($setInfo->lvinfo_set_typ=='boolean')
			echo '<input name="german['.$key.']" type="checkbox">'; //@todo checked?
		elseif($setInfo->lvinfo_set_typ=='array')
			echo '<input name="german['.$key.']" type="text" value="'.$value.'">';
		
		echo '</td></tr>';
	}
	/*
	if($row->lvinfo_set_typ=='text')
		echo '<textarea name="german['.$row->lvinfo_set_kurzbz.']" rows="5" cols="50"></textarea>';
	elseif($row->lvinfo_set_typ=='boolean')
		echo '<input name="german['.$row->lvinfo_set_kurzbz.']" type="checkbox">';
	elseif($row->lvinfo_set_typ=='array')
		echo '<input name="german['.$row->lvinfo_set_kurzbz.']" type="text">';
	echo '</td>';
	
	echo '<td>&nbsp;</td>';
	echo '<td>'.$row->sprache.'</td><td>';
	if($row->lvinfo_set_typ=='text')
		echo '<textarea name="english['.$row->lvinfo_set_kurzbz.']" rows="5" cols="50"></textarea>';
	elseif($row->lvinfo_set_typ=='boolean')
		echo '<input name="english['.$row->lvinfo_set_kurzbz.']" type="checkbox">';
	elseif($row->lvinfo_set_typ=='array')
		echo '<input name="english['.$row->lvinfo_set_kurzbz.']" type="text">';*/
	echo '</td>';
	echo '</tr>';
}

exit;

foreach($lvinfo_set->result as $row)
{
	//Elementnamen werden dem Formular als Arrays uebergeben
	echo '<tr>';
	echo '<td>'.$row->lvinfo_set_bezeichnung['German'].'</td><td>';
	if($row->lvinfo_set_typ=='text')
		echo '<textarea name="german['.$row->lvinfo_set_kurzbz.']" rows="5" cols="50"></textarea>';
	elseif($row->lvinfo_set_typ=='boolean')
		echo '<input name="german['.$row->lvinfo_set_kurzbz.']" type="checkbox">';
	elseif($row->lvinfo_set_typ=='array')
		echo '<input name="german['.$row->lvinfo_set_kurzbz.']" type="text">';
	echo '</td>';
	
	echo '<td>&nbsp;</td>';
	echo '<td>'.$row->lvinfo_set_bezeichnung['English'].'</td><td>';
	if($row->lvinfo_set_typ=='text')
		echo '<textarea name="english['.$row->lvinfo_set_kurzbz.']" rows="5" cols="50"></textarea>';
	elseif($row->lvinfo_set_typ=='boolean')
		echo '<input name="english['.$row->lvinfo_set_kurzbz.']" type="checkbox">';
	elseif($row->lvinfo_set_typ=='array')
		echo '<input name="english['.$row->lvinfo_set_kurzbz.']" type="text">';
	echo '</td>';
	echo '</tr>';
}

echo '</table>';
if (isset($_GET['lvinfo_id']))
	echo '<input type="submit" name="save" value="'.$p->t('global/speichern').'">';
else
	echo '<input type="submit" name="save_new" value="'.$p->t('global/speichern').'">';
//echo '<input type="hidden" name="lv_id" value="'.$lv_id.'">';
echo '<input type="hidden" name="studiensemester_kurzbz" value="'.$studiensemester_kurzbz.'">';
echo '</form>';

echo $errormsg;

?>
</body>
</html>
