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
require_once('../../../include/mail.class.php');
require_once('../../../include/benutzer.class.php');
require_once('../../../include/benutzerfunktion.class.php');
require_once('../../../include/lehreinheitmitarbeiter.class.php');
require_once('../include/lvinfo.class.php');
require_once('../include/functions.inc.php');
require_once('../vendor/autoload.php');
require_once('../lvinfo.config.inc.php');

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

?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title><?php echo $p->t('lvinfo/lvinformationen'); ?></title>
	<link rel="stylesheet" href="../../../skin/style.css.php" type="text/css">
    <link rel="stylesheet" href="../../../skin/tablesort.css" type="text/css">
    <script src="../../../include/js/jquery1.9.min.js" type="text/javascript"></script>
	<script type="text/javascript">
	function addInput(sprache, key)
	{
		$('#input_arr_'+sprache+'_'+key).append('<input name="'+sprache+'['+key+'][]" size="50" type="text" value=""><br>');
	}
	</script>
</head>
<body>
<?php
$lv_id = isset($_REQUEST['lv_id'])?$_REQUEST['lv_id']:'';
//Wenn eine LV_ID übergeben wurde aber keine anderen Parameter, werden diese mit den Daten der LV befüllt
$lv_obj = new lehrveranstaltung();
$lv_obj->load($lv_id);

$stsem = new studiensemester();
$stsem = $stsem->getakt();

$stg_kz = isset($_GET['stg_kz']) && $_GET['stg_kz']!=''?$_GET['stg_kz']:($lv_obj->studiengang_kz!=''?$lv_obj->studiengang_kz:'0');
$semester = isset($_GET['semester']) && $_GET['semester']!=''?$_GET['semester']:($lv_obj->semester!=''?$lv_obj->semester:'1');
$orgform_kurzbz = isset($_GET['orgform_kurzbz']) && $_GET['orgform_kurzbz']!=''?$_GET['orgform_kurzbz']:''; //$lv_obj->orgform_kurzbz
$studiensemester_kurzbz = isset($_REQUEST['studiensemester_kurzbz'])?$_REQUEST['studiensemester_kurzbz']:$stsem;

$errormsg = '';

if(isset($_POST['save']) || isset($_POST['saveAndSend']))
{
    // Berechtigungen pruefen
    $lem = new lehreinheitmitarbeiter();
    if(!$lem->existsLV($lv_id, $studiensemester_kurzbz,  $user))
    {
    	$rechte = new benutzerberechtigung();
    	$rechte->getBerechtigungen($user);

    	$lva = new lehrveranstaltung();
    	$lva->load($lv_id);
    	$oes = $lva->getAllOe();
    	$oes[]=$lva->oe_kurzbz; // Institut
    	if(!$rechte->isBerechtigtMultipleOe('addon/lvinfo',$oes,'s'))
    	{
    		die($p->t('global/keineBerechtigungFuerDieseSeite'));
    	}
    }

	$error = false;
	//Formulardaten fuer Json-Encode vorbereiten

	$lvinfo_set = new lvinfo();
	$lvinfo_set->load_lvinfo_set($studiensemester_kurzbz);

	foreach($config_lvinfo_sprachen as $lvinfo_sprache)
	{
		$data = getSet($lvinfo_sprache, $lvinfo_set->result);

		$lvinfo = new lvinfo();

		if(!isset($_POST[$lvinfo_sprache."LVinfo_id"]) || $_POST[$lvinfo_sprache."LVinfo_id"]=='')
		{
			$lvinfo->new=true;
			$lvinfo->insertamum=date('Y-m-d H:i:s');
			$lvinfo->insertvon = $user;
			$lvinfo->updateamum=date('Y-m-d H:i:s');
			$lvinfo->updatevon = $user;
		}
		else
		{
			if($lvinfo->load($_POST[$lvinfo_sprache.'LVinfo_id']))
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

		$lvinfo->sprache = $lvinfo_sprache;
		$lvinfo->lehrveranstaltung_id = $lv_id;
		$lvinfo->studiensemester_kurzbz = $studiensemester_kurzbz;
		$lvinfo->data = $data;

		if(!$lvinfo->save())
		{
			$error = true;
			$errorstr = "Fehler beim Speichern der Daten: $lvinfo->errormsg";
		}
        else
        {
            $lvinfostatus = new lvinfo();
            $lvinfostatus->getLastStatus($lvinfo->lvinfo_id);
            if($lvinfostatus->lvinfostatus_kurzbz=='')
                $lvinfostatus->setStatus($lvinfo->lvinfo_id,'bearbeitung',$user);
        }

		$id_array[$lvinfo->sprache]=$lvinfo->lvinfo_id;
	}

	if(!$error && isset($_POST['saveAndSend']))
	{
		// Abschicken Status setzen und Freigabe Mails verschicken
		$lvinfo = new lvinfo();

		$diff ='';
		foreach($config_lvinfo_sprachen as $lvinfo_sprache)
		{
            $lvinfo->getLastStatus($id_array[$lvinfo_sprache]);
            if($lvinfo->lvinfostatus_kurzbz!='freigegeben')
			    $lvinfo->setStatus($id_array[$lvinfo_sprache],'abgeschickt',$user);

			$sprache_obj = new sprache();
			$sprache_obj->load($lvinfo_sprache);
			$diff.= '<br><u><b>'.$sprache_obj->bezeichnung_arr[DEFAULT_LANGUAGE].'</b></u>';
			$diff .= getDiffPrevious($id_array[$lvinfo_sprache]);
		}

		$benutzer = new benutzer();
		$benutzer->load($user);

		$stg_obj = new studiengang();
		$stg_obj->getAll('typ, kurzbz',false);

		$text='Die Lehrveranstaltungsinformationen für eine Lehrveranstaltung wurde eingetragen/aktualisiert.<br>
		Name der LV: '.$db->convert_html_chars($lv_obj->bezeichnung).'<br>
		Studiengang: '.$db->convert_html_chars($stg_obj->kuerzel_arr[$lv_obj->studiengang_kz]).'<br>
		Semester: '.$db->convert_html_chars($lv_obj->semester).'<br>
		Organisationsform: '.$db->convert_html_chars($lv_obj->orgform_kurzbz).'<br>
        Studiensemester:'.$db->convert_html_chars($studiensemester_kurzbz).'<br>
		Änderung durch: '.$db->convert_html_chars($benutzer->vorname.' '.$benutzer->nachname).'<br><br>
		Bitte bestätigen Sie die Freigabe über folgenden Link:
        <a href="'.APP_ROOT.'addons/lvinfo/cis/lvinfo_uebersicht.php?stg_kz='.urlencode($lv_obj->studiengang_kz).'&semester='.urlencode($lv_obj->semester).'&orgform_kurzbz='.urlencode($lv_obj->orgform_kurzbz).'&studiensemester_kurzbz='.urlencode($studiensemester_kurzbz).'">Zur Freigabe</a><br>
		Folgende Änderungen wurde gegenüber der Vorversion durchgeführt:<br>
		'.$diff;

		// Empfaenger setzen
        // Studiengangsleitung
        $stgleitung = $stg_obj->getLeitung($lv_obj->studiengang_kz);
        $to='';
        foreach($stgleitung as $rowltg)
            $to.=$rowltg.'@'.DOMAIN.',';

        $to = mb_substr($to, 0,-1);
		$from = 'noreply@'.DOMAIN;
		$subject = 'Freigabe LV-Information';

		$mail = new mail($to, $from, $subject, $text);
		$mail->setHTMLContent($text);
		$mail->setReplyTo($user.'@'.DOMAIN);
		if($mail->send())
		{
			echo '<span class="ok">'.$p->t('lvinfo/mailVersandtAn',array($to)).'</span>';
		}
		else
		{
			echo '<span class="error">'.$p->t('lvinfo/mailVersandtFailed',array($to)).'</span>';
		}
	}
}

$lv = new lehrveranstaltung();
$lv->load($lv_id);
$stg = new studiengang();
$stg->load($lv->studiengang_kz);

echo '<H1>'.$p->t('lvinfo/lehrveranstaltungsinformationen').' - '.$db->convert_html_chars($stg->kurzbzlang.'-'.$lv->semester.($lv->orgform_kurzbz!=''?'-'.$lv->orgform_kurzbz:'').' - '.$lv->bezeichnung).'</H1>';

echo '<table width="100%"><tr><td valign="top">';
echo '<form name="auswahlFrm" action="lvinfo.php" method="GET">';

$stg_obj = new studiengang();
echo '<table>';
//Anzeigen des DropDown Menues mit Stg
if($stg_obj->getAll('typ, kurzbzlang',true))
{
	echo '<tr><td>'.$p->t('global/studiengang').'</td><td><SELECT name="stg_kz" onChange="window.document.auswahlFrm.submit();">';

	//DropDown Menue mit den Stg füllen
	foreach($stg_obj->result as $row)
	{
		$selected = '';
		if($row->studiengang_kz==$stg_kz)
			$selected = 'selected';

		echo '<option value="'.$row->studiengang_kz.'" '.$selected.'>'.$db->convert_html_chars($row->kurzbzlang.' ('.$row->bezeichnung.')').'</option>';
	}
	echo '</SELECT></td></tr>';

}
else
{
	$errormsg .= "$stg_obj->errormsg";
}

//Anzeigen des DropDown Menues mit Semester
if($stg_obj->load($stg_kz))
{
	echo '<tr><td>'.$p->t('global/semester').'</td><td> <SELECT name="semester" onChange="window.document.auswahlFrm.submit();">';
	echo '<option value="">*</option>';
	for($i=1;$i<=$stg_obj->max_semester;$i++)
	{
		$selected = '';
		if($i==$semester)
			$selected = 'selected';

		echo '<option value="'.$i.'" '.$selected.'>'.$i.'</option>';

	}
	echo '</SELECT></td></tr>';
}
else
	$errormsg .= "$stg_obj->errormsg";

//Anzeigen des DropDown Menues mit Orgform
$orgform_obj = new organisationsform();
if($orgform_obj->getOrgformLV())
{
	echo '<tr><td>'.$p->t('lvinfo/organisationsform').'</td><td><SELECT name="orgform_kurzbz" onChange="window.document.auswahlFrm.submit();">';
	echo '<option value="">*</option>';
	foreach($orgform_obj->result as $row)
	{
		$selected = '';
		if($row->orgform_kurzbz==$orgform_kurzbz)
			$selected = 'selected';

		echo '<option value="'.$row->orgform_kurzbz.'" '.$selected.'>'.$db->convert_html_chars($row->orgform_kurzbz.' ('.$row->bezeichnung.')').'</option>';
	}
	echo '</SELECT></td></tr>';
}
else
	$errormsg .= "$orgform_obj->errormsg";

//Anzeigen des DropDown Menues mit Studiensemester
$studiensemester = new studiensemester();
$akt_studiensemester = $studiensemester->getakt();
if($studiensemester->getPlusMinus(1,10))
{
	echo '<tr><td>'.$p->t('global/studiensemester').'</td><td><SELECT name="studiensemester_kurzbz" onChange="window.document.auswahlFrm.submit();">';

	foreach($studiensemester->studiensemester as $row)
	{
		$selected = '';
		if($row->studiensemester_kurzbz==$studiensemester_kurzbz)
			$selected = 'selected';
		elseif ($studiensemester_kurzbz=='' && $row->studiensemester_kurzbz==$akt_studiensemester)
			$selected = 'selected';

		echo '<option value="'.$row->studiensemester_kurzbz.'" '.$selected.'>'.$row->studiensemester_kurzbz.'</option>';
	}
	echo '</SELECT></td></tr>';
}
else
	$errormsg .= $studiensemester->errormsg;

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
	echo '<tr><td>'.$p->t('global/lehrveranstaltung').'</td><td><SELECT name="lv_id" onChange="window.document.auswahlFrm.submit();">';

	if(count($lv_obj->lehrveranstaltungen)>0)
	{
		//Wenn die übergebene LV_ID nicht in der Liste der geladenen Objekte ist,
        // Dann die erste LV in der Liste markieren
		$lv_ids = array();
		foreach($lv_obj->lehrveranstaltungen as $row)
		{
			$lv_ids[] .= $row->lehrveranstaltung_id;
		}
		if($lv_id!='' && !in_array($lv_id, $lv_ids))
		{
            /*
			$lv = new lehrveranstaltung();
			$lv->load($lv_id);
			if($lv->semester!='' && $lv->orgform_kurzbz=='')
				$outputstring = $lv->semester.' - ';
			if($lv->semester=='' && $lv->orgform_kurzbz!='')
				$outputstring = $lv->orgform_kurzbz.' - ';
			if($lv->semester!='' && $lv->orgform_kurzbz!='')
				$outputstring = $lv->orgform_kurzbz.' - '.$lv->semester.' - ';
			echo '<option value="'.$lv_id.'" selected>'.$db->convert_html_chars($outputstring.$lv->bezeichnung).'</option>';
            */
            $lv_id = '';
		}
		$outputstring = '';
		foreach($lv_obj->lehrveranstaltungen as $row)
		{
			$selected = '';
            if($lv_id=='')
                $lv_id=$row->lehrveranstaltung_id;
			if($row->lehrveranstaltung_id==$lv_id)
				$selected = 'selected';
			if($semester!='' && $orgform_kurzbz=='' && $row->orgform_kurzbz!='')
				$outputstring = $row->orgform_kurzbz.' - ';
			if($semester=='' && $orgform_kurzbz!='')
				$outputstring = $row->semester.' - ';
			if($semester!='' && $orgform_kurzbz!='')
				$outputstring = '';

			echo '<option value="'.$row->lehrveranstaltung_id.'" '.$selected.'>'.$db->convert_html_chars($outputstring.$row->bezeichnung).'</option>';
		}
	}
	else
		echo '<option value="">'.$p->t('lvinfo/keineLVVorhanden').'</option>';
	echo '</SELECT></td></tr>';
}
else
{
	$errormsg .= $lv_obj->errormsg;
}
echo '</table>';
echo '<input type="submit" value="'.$p->t('global/anzeigen').'">';
echo '</form>';

echo '</td><td>';
echo '<a href="lvinfo_uebersicht.php?stg_kz='.$stg_kz.'&semester='.$semester.'&orgform_kurzbz='.$orgform_kurzbz.'&studiensemester_kurzbz='.$studiensemester_kurzbz.'">'.$p->t('lvinfo/uebersichtsliste').'</a>';
printInfoTable($lv_id, $studiensemester_kurzbz, $sprache);
echo '</td></tr></table>';


// Berechtigungen pruefen
$lem = new lehreinheitmitarbeiter();
if(!$lem->existsLV($lv_id, $studiensemester_kurzbz,  $user))
{
    $rechte = new benutzerberechtigung();
    $rechte->getBerechtigungen($user);

    $lva = new lehrveranstaltung();
    $lva->load($lv_id);
    $oes = $lva->getAllOe();
    $oes[]=$lva->oe_kurzbz; // Institut
    if(!$rechte->isBerechtigtMultipleOe('addon/lvinfo',$oes,'s'))
    {
        die($p->t('global/keineBerechtigungFuerDieseSeite'));
    }
}
// LV Information anzeigen

echo '<form name="editFrm" action="lvinfo.php?lv_id='.$lv_id.'" method="POST">';

//Wenn LV-Info für das gewählte Studiensemester vorhanden, diese laden, sonst leeres Set anzeigen
$lvinfo = new lvinfo();
$lvinfo->loadLvinfo($lv_id, $studiensemester_kurzbz);

//Arrays nach unterschieden vergleichen
$lvinfo_array = array();
$lvinfo_compare = array();
$laststatus_arr = array();
foreach($lvinfo->result AS $row)
{
	$lang=$row->sprache;
	foreach($row->data AS $key=>$value)
	{
		if(!isset($lvinfo_array[$lang][$key]))
			$lvinfo_array[$lang][$key]='';
		$lvinfo_array[$lang][$key] = $value;
		$lvinfo_compare[] = $key;
	}
	$lvinfo_lock = new lvinfo();
	$lvinfo_lock->getLastStatus($row->lvinfo_id);
	$laststatus_arr[$lang]=$lvinfo_lock;
	echo '<input type="hidden" name="'.$lang.'LVinfo_id" value="'.$row->lvinfo_id.'" />';
}

$lvinfo_compare=array_unique($lvinfo_compare);

$lvinfo_set = new lvinfo();
$lvinfo_set->load_lvinfo_set($studiensemester_kurzbz);

$set_compare = array();

foreach($lvinfo_set->result as $row)
{
	$set_compare[] = $row->lvinfo_set_kurzbz;
}

$imSetAberNichtInInfo = array();
$imSetAberNichtInInfo = array_diff($set_compare, $lvinfo_compare);
$inInfoAberNichtImSet = array();
$inInfoAberNichtImSet = array_diff($lvinfo_compare, $set_compare);


// Daten umformatieren um die einzelen Sprachen
// leichter ansprechen zu koennen
$data_set=array();
$data_obj=array();
foreach($lvinfo->result AS $row)
{
	$data_set[$row->sprache]=$row->data;
	$data_obj[$row->sprache]=$row;
}

echo '

    <table width="100%" id="tablelvinfo" class="tablesorter">
        <thead>
		<tr>';

foreach($config_lvinfo_sprachen as $lvinfo_sprache)
{
	$sprachen_obj = new sprache();
	$sprachen_obj->load($lvinfo_sprache);

    // Aktuellen Status anzeigen
    if(isset($laststatus_arr[$lvinfo_sprache])
        && isset($laststatus_arr[$lvinfo_sprache]->bezeichnung[$lvinfo_sprache])
        && $laststatus_arr[$lvinfo_sprache]->bezeichnung[$lvinfo_sprache]!='')
    {
        $aktuellerStatus = '('.$p->t('lvinfo/status').' '.$laststatus_arr[$lvinfo_sprache]->bezeichnung[$sprache].')';
    }
    else
    {
        $aktuellerStatus ='';
    }
	echo '
		<th colspan="2">
			'.$sprachen_obj->bezeichnung_arr[$sprache].' '.$aktuellerStatus.' '.(isset($data_obj[$lvinfo_sprache])?$data_obj[$lvinfo_sprache]->lvinfo_id:'').'
		</th>';
}
echo '</tr></thead>
<tbody>';

$locked=false;
$i=0;
// Ausgabe der Felder
foreach($lvinfo_set->result as $row_set)
{
    $i++;
	echo '<tr class="'.(($i%2==0)?'even':'odd').'">';

	foreach($config_lvinfo_sprachen as $lvinfo_sprache)
	{
		if(isset($laststatus_arr[$lvinfo_sprache]) && in_array($laststatus_arr[$lvinfo_sprache]->lvinfostatus_kurzbz,array('freigegeben','abgeschickt')))
			$locked=true;
		else
			$locked=false;

		echo '<td valign="top">'.$row_set->lvinfo_set_bezeichnung[$lvinfo_sprache].'</td>
		<td valign="top">';
        if(isset($row_set->einleitungstext[$lvinfo_sprache]))
            echo $row_set->einleitungstext[$lvinfo_sprache].'<br><br>';
		printData($lvinfo_sprache, $row_set->lvinfo_set_typ, $row_set->lvinfo_set_kurzbz, (isset($data_set[$lvinfo_sprache])?$data_set[$lvinfo_sprache]:array()), $locked);
		echo '</td>';
	}

	echo '</tr>';
}

// Alle Eintraege Anzeigen die nicht im aktuellen Set sind und
// beim Speichern geloescht werden
if(count($inInfoAberNichtImSet)>0)
{
	echo '<tr><td colspan="6" align="center">'.$p->t('lvinfo/alteDatenLoeschen').'</td></tr>';
	foreach($inInfoAberNichtImSet as $row_nichtimset)
	{
		$set = new lvinfo();
		$set->load_lvinfo_set_kurzbz_nearest($row_nichtimset, $row->studiensemester_kurzbz);

		echo '<tr>';

		foreach($config_lvinfo_sprachen as $lvinfo_sprache)
		{
			echo '
			<td>'.$db->convert_html_chars($set->lvinfo_set_bezeichnung[$lvinfo_sprache]).'</td>
			<td>'.$db->convert_html_chars($data_set[$lvinfo_sprache][$row_nichtimset]).'</td>';
		}
		echo '</tr>';
	}
}
$locked=true;
foreach($config_lvinfo_sprachen as $lvinfo_sprache)
    if(!(isset($laststatus_arr[$lvinfo_sprache]) && in_array($laststatus_arr[$lvinfo_sprache]->lvinfostatus_kurzbz,array('freigegeben','abgeschickt'))))
        $locked=false;
echo '</tbody>
<tfoot>
    <tr>
	<td colspan="5">
        <input type="submit" name="save" '.($locked?'disabled="disabled"':'').' value="'.$p->t('global/speichern').'">
		<input type="submit" name="saveAndSend" '.($locked?'disabled="disabled"':'').' value="'.$p->t('lvinfo/speichernUndFreigeben').'">
	</td>
	</tr>';
echo '</tfoot></table>';

echo '<input type="hidden" name="studiensemester_kurzbz" value="'.$db->convert_html_chars($studiensemester_kurzbz).'">';
echo '</form>';

/**
 * Zeichnet die einzelnen Inputfelder
 *
 * @param $sprache
 * @param $typ Typ des Feldes (text,array,boolean)
 * @param $key kurzbz des Feldes
 * @param $data Array mit den Daten in dieser Sprache
 */
function printData($sprache, $typ, $key, $data, $locked)
{
	$db = new basis_db();
	switch($typ)
	{
		case 'text':
			if(isset($data[$key]))
				$value=$data[$key];
			else
				$value='';
			echo '<textarea name="'.$sprache.'['.$key.']" rows="5" cols="50" '.($locked?'readonly="readonly"':'').'>'.$db->convert_html_chars($value).'</textarea>';
			break;

		case 'boolean':
			if(isset($data[$key]))
				$value=$data[$key];
			else
				$value='';

			echo '<input name="'.$sprache.'['.$key.']" type="checkbox" '.($value?'checked="checked"':'').' '.($locked?'readonly="readonly"':'').'>';
			break;

		case 'array':
			if(isset($data[$key]))
				$value=$data[$key];
			else
				$value=array();

			echo '<div id="input_arr_'.$sprache.'_'.$key.'">';
			foreach($value as $val)
				echo '<input name="'.$sprache.'['.$key.'][]" size="50" type="text" value="'.$db->convert_html_chars($val).'" '.($locked?'readonly="readonly"':'').'><br>';
			echo '</div>';
			if(!$locked)
				echo '<a href="#add" onclick="addInput(\''.$sprache.'\',\''.$key.'\');return false;"><img src="../../../skin/images/plus.png" height="20px" alt="Eintrag hinzufügen"/></a>';
			break;

		default:
			echo '<input name="'.$sprache.'['.$key.']" type="text" '.($locked?'readonly="readonly"':'').' value="'.$db->convert_html_chars($data[$key]).'">';
	}
}

/**
 * Liefert die Daten zum Speichern
 * @param $sprache Sprache die geliefert werden soll
 * @param $set LVInfo SET das gespeichert werden soll
 * @return array mit den Daten
 */
function getSet($sprache, $set)
{
	$data = array();

	foreach($set as $row)
	{
		switch($row->lvinfo_set_typ)
		{
			case 'boolean':
				if(isset($_POST[$sprache][$row->lvinfo_set_kurzbz]))
					$data[$row->lvinfo_set_kurzbz]=true;
				else
					$data[$row->lvinfo_set_kurzbz]=false;
				break;

			case 'array':
				// Leere Array Elemente werden entfernt
				if(isset($_POST[$sprache][$row->lvinfo_set_kurzbz]) && is_array($_POST[$sprache][$row->lvinfo_set_kurzbz]))
				{
					foreach($_POST[$sprache][$row->lvinfo_set_kurzbz] as $item)
					{
						if($item!='')
							$data[$row->lvinfo_set_kurzbz][] = $item;
					}
				}
				break;

			case 'text':
			default:
				$data[$row->lvinfo_set_kurzbz] = $_POST[$sprache][$row->lvinfo_set_kurzbz];
				break;

		}
	}
	return $data;
}


?><br><br><br><br><br>
</body>
</html>
