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
require_once('../../../include/studienordnung.class.php');
require_once('../../../include/studienplan.class.php');
require_once('../../../include/vertrag.class.php');
require_once('../include/lvinfo.class.php');
require_once('../include/functions.inc.php');
require_once('../vendor/autoload.php');
require_once('../lvinfo.config.inc.php');

if (!$db = new basis_db())
  die('Fehler beim Oeffnen der Datenbankverbindung');

$user = get_uid();

if(!check_lektor($user))
	die('Diese Seite ist nur fuer LektorInnen zugänglich');

$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($user);

$sprache = getSprache();
$p = new phrasen($sprache);

$sprachen_obj = new sprache();
$sprachen_obj->getAll();
$sprachen_arr=array();

foreach($sprachen_obj->result as $row)
{
	if(isset($row->bezeichnung_arr[$sprache]))
		$sprachen_arr[$row->sprache]=$row->bezeichnung_arr[$sprache];
	else
		$sprachen_arr[$row->sprache]=$row->sprache;
}

$orgform_obj = new organisationsform();
$orgform_obj->getAll();
$orgform_arr=array();
foreach($orgform_obj->result as $row)
	$orgform_arr[$row->orgform_kurzbz]=$row->bezeichnung;

$datum_obj = new datum();

?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title><?php echo $p->t('lvinfo/lvinformationen'); ?></title>
	<link rel="stylesheet" href="../../../skin/style.css.php" type="text/css">
    <link rel="stylesheet" href="../../../skin/tablesort.css" type="text/css">

	<script type="text/javascript" src="../../../vendor/jquery/jqueryV1/jquery-1.12.4.min.js"></script>
	<script type="text/javascript" src="../../../vendor/christianbach/tablesorter/jquery.tablesorter.min.js"></script>

	<script type="text/javascript">
	function addInput(sprache, key)
	{
		$('#input_arr_'+sprache+'_'+key).append('<input name="'+sprache+'['+key+'][]" style="width:98%" type="text" value=""><br>');
	}
	function freigabe(lvinfo_id, stg_kz, semester, orgform_kurzbz, studiensemester_kurzbz, studienplan_id, lv_id)
	{
		var url='lvinfo.php?stg_kz='+stg_kz+'&semester='+semester+'&orgform_kurzbz='+orgform_kurzbz+'&studiensemester_kurzbz='+studiensemester_kurzbz+'&studienplan_id='+studienplan_id+'&lv_id='+lv_id;
		$("#data").html('<form action="'+url+'" name="sendform" id="sendform" method="POST"><input type="hidden" name="action" value="freigeben" /><input type="hidden" name="lvinfo_id" value="'+lvinfo_id+'" /></form>');
		document.sendform.submit();
	}
	function bearbeiten(lvinfo_id, stg_kz, semester, orgform_kurzbz, studiensemester_kurzbz, studienplan_id, lv_id)
	{
		var url='lvinfo.php?stg_kz='+stg_kz+'&semester='+semester+'&orgform_kurzbz='+orgform_kurzbz+'&studiensemester_kurzbz='+studiensemester_kurzbz+'&studienplan_id='+studienplan_id+'&lv_id='+lv_id;
		$("#data").html('<form action="'+url+'" name="sendform" id="sendform" method="POST"><input type="hidden" name="action" value="reset" /><input type="hidden" name="lvinfo_id" value="'+lvinfo_id+'" /></form>');
		document.sendform.submit();
	}
	</script>
	<style type="text/css">
	textarea, input
	{
		font-size: 9pt;
	}
	input[readonly], textarea[readonly]
	{
		background-color: #F0F0F0;
		color: #6D6D6D;
	}
	</style>
</head>
<body>
<div id="data"></div>
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
$studienordnung_id = (isset($_GET['studienordnung_id'])?$_GET['studienordnung_id']:'');

if(isset($_GET['studienplan_id']))
{
	$studienplan_id = $_GET['studienplan_id'];
	$stp_obj = new studienplan();
	if($stp_obj->loadStudienplan($studienplan_id))
	{
		$studienordnung_id = $stp_obj->studienordnung_id;
	}
	else
		die($stp_obj->errormsg);
}
else
{
	if($lv_id!='' && $studiensemester_kurzbz!='')
	{
		$stp_obj = new studienplan();
		$stp_obj->getStudienplanLehrveranstaltung($lv_id, $studiensemester_kurzbz);

		if(isset($stp_obj->result[0]))
		{

			$studienplan_id = $stp_obj->result[0]->studienplan_id;
		}
		else
			$studienplan_id='';
	}
	else
		$studienplan_id='';
}

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
			die($rechte->errormsg);
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

		if(!defined('ADDON_LVINFO_SEND_FREIGABEMAIL') || ADDON_LVINFO_SEND_FREIGABEMAIL)
		{
			$benutzer = new benutzer();
			$benutzer->load($user);

			$stg_obj = new studiengang();
			$stg_obj->getAll('typ, kurzbz',false);

			$text='Die Lehrveranstaltungsinformationen für eine Lehrveranstaltung wurde eingetragen/aktualisiert.<br>
			Name der LV: '.$db->convert_html_chars($lv_obj->bezeichnung)."\n".'<br>
			Studiengang: '.$db->convert_html_chars($stg_obj->kuerzel_arr[$lv_obj->studiengang_kz])."\n".'<br>
			Semester: '.$db->convert_html_chars($lv_obj->semester)."\n".'<br>
			Organisationsform: '.$db->convert_html_chars($lv_obj->orgform_kurzbz)."\n".'<br>
			Studiensemester:'.$db->convert_html_chars($studiensemester_kurzbz)."\n".'<br>
			Änderung durch: '.$db->convert_html_chars($benutzer->vorname.' '.$benutzer->nachname)."\n".'<br><br>
			Bitte bestätigen Sie die Freigabe über folgenden Link:'."\n".'
			<a href="'.APP_ROOT.'addons/lvinfo/cis/lvinfo_uebersicht.php?stg_kz='.urlencode($lv_obj->studiengang_kz).'&semester='.urlencode($lv_obj->semester).'&orgform_kurzbz='.urlencode($lv_obj->orgform_kurzbz).'&studiensemester_kurzbz='.urlencode($studiensemester_kurzbz).'">Zur Freigabe</a><br>
			Folgende Änderungen wurde gegenüber der Vorversion durchgeführt:<br>'."\n".'
			'.$diff;

			// Empfaenger setzen
			// Studiengangsleitung
			$stgleitung = $stg_obj->getLeitung($lv_obj->studiengang_kz);
			$to = array();
			foreach($stgleitung as $rowltg)
				$to[] = $rowltg.'@'.DOMAIN;

			// geschaeftsfuehrende Studiengangsleitung
			$stg_hlp = new studiengang();
			$stg_hlp->load($lv_obj->studiengang_kz);
			$bnf = new benutzerfunktion();
			$bnf->getBenutzerFunktionen('gLtg', $stg_hlp->oe_kurzbz);
			foreach($bnf->result as $rowbnf)
				$to[] = $rowbnf->uid.'@'.DOMAIN;

			$to = array_unique($to);

			// Wenn kein Empfaenger gefunden wurde, an die Mailadresse des Studiengangs schicken
			if (count($to) == 0)
				$to[] = $stg_hlp->email;

			$to = implode(',', $to);
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
}
if(isset($_POST['action']))
{
	$lvinfo_id = $_POST['lvinfo_id'];

	$lvinfo = new lvinfo();
	if($lvinfo->load($lvinfo_id))
	{
		// Berechtigung pruefen
		$lva = new lehrveranstaltung();
		$lva->load($lvinfo->lehrveranstaltung_id);
		$oes = $lva->getAllOe();
		$oes[]=$lva->oe_kurzbz;
		if($rechte->isBerechtigtMultipleOe('addon/lvinfofreigabe',$oes,'s'))
		{
			if ($_POST['action'] == 'reset')
			{
				if(!$lvinfo->setStatus($lvinfo_id,'bearbeitung',$user))
					echo '<span class="error">'.$p->t('lvinfo/freigabeKonnteNichtAufgehobenWerden',array($lvinfo)).'</span>';
			}
			elseif ($_POST['action'] == 'freigeben')
			{
				if(!$lvinfo->setStatus($lvinfo_id,'freigegeben',$user))
					echo '<span class="error">'.$p->t('lvinfo/freigabeKonnteNichtGesetztWerden',array($lvinfo)).'</span>';
			}
		}
		else
		{
			echo '<span class="error">'.$rechte->errormsg.'</span>';
		}
	}
	else
	{
		echo '<span class="error">'.$p->t('global/fehlerBeimAktualisierenDerDaten').'</span>';
	}
}

$lv = new lehrveranstaltung();
$lv->load($lv_id);
$stg = new studiengang();
$stg->load($lv->studiengang_kz);

echo '<H1>'.$p->t('lvinfo/lehrveranstaltungsinformationen').' - '.$db->convert_html_chars($stg->kurzbzlang.'-'.$lv->semester.($lv->orgform_kurzbz!=''?'-'.$lv->orgform_kurzbz:'').' - '.$lv->bezeichnung).'</H1>';

echo '<table width="100%"><tr><td valign="top">';

if (defined('ADDON_LVINFO_HIDE_MENU') && ADDON_LVINFO_HIDE_MENU)
    echo '<div style="display: none;">';

echo '<form name="auswahlFrm" action="lvinfo.php" method="GET">';
echo '<table>';
//Anzeigen des DropDown Menues mit Studiensemester
if (defined('ADDON_LVINFO_HIDE_MENU') && ADDON_LVINFO_HIDE_MENU)
{
    echo '<input type="hidden" name="studiensemester_kurzbz" value="' . $studiensemester_kurzbz . '" />';
}
else
{
    $studiensemester = new studiensemester();
    $akt_studiensemester = $studiensemester->getakt();
    if($studiensemester->getPlusMinus(8,10))
    {
        echo '<tr><td>'.$p->t('lvinfo/studiensemester').'</td><td><SELECT name="studiensemester_kurzbz" onChange="window.document.auswahlFrm.submit();">';

        foreach($studiensemester->studiensemester as $row)
        {
            $selected = '';
            if($row->studiensemester_kurzbz==$studiensemester_kurzbz)
                $selected = 'selected';
            elseif ($studiensemester_kurzbz=='' && $row->studiensemester_kurzbz==$akt_studiensemester)
            {
                $selected = 'selected';
                $studiensemester_kurzbz=$akt_studiensemester;
            }

            echo '<option value="'.$row->studiensemester_kurzbz.'" '.$selected.'>'.$row->bezeichnung.'</option>';
        }
        echo '</SELECT></td></tr>';
    }
    else
        $errormsg .= $studiensemester->errormsg;
}

$stg_obj = new studiengang();
$types = new studiengang();
$types->getAllTypes();
$typ = '';

//Anzeigen des DropDown Menues mit Stg
if($stg_obj->loadStudiengangFromStudiensemester($studiensemester_kurzbz))
{
	echo '<tr>
		<td>'.$p->t('global/studiengang').'</td>
		<td><SELECT name="stg_kz" onChange="window.document.auswahlFrm.submit();">';

	//DropDown Menue mit Stg füllen
	foreach($stg_obj->result as $row)
	{
		if ($typ != $row->typ || $typ=='')
		{
			if ($typ!='')
				echo '</optgroup>';
				echo '<optgroup label="'.($types->studiengang_typ_arr[$row->typ]!=''?$types->studiengang_typ_arr[$row->typ]:$row->typ).'">';
		}
		$selected = '';
		if($row->studiengang_kz==$stg_kz)
			$selected = 'selected';

		echo '<option value="'.$row->studiengang_kz.'" '.$selected.'>'.$db->convert_html_chars(strtoupper($row->typ.$row->kurzbz).' ('.$row->bezeichnung.')').'</option>';
		$typ = $row->typ;
	}
	echo '</SELECT></td></tr>';
}
else
{
	$errormsg .= "$stg_obj->errormsg";
}


// Ausbildungssemester anzeigen

$vorhandenesemester=array();

$studienplan_obj = new studienplan();
$studienplan_obj->getStudienplaeneFromSem($stg_kz, $studiensemester_kurzbz);
foreach($studienplan_obj->result as $row_sto)
	$vorhandenesemester[]=$row_sto->semester;

if(!in_array($semester, $vorhandenesemester))
	$semester='';
$vorhandenesemester = array_unique($vorhandenesemester);
sort($vorhandenesemester);


//Anzeigen des DropDown Menues mit Ausbildungssemester
if($stg_obj->load($stg_kz))
{
	echo '<tr><td>'.$p->t('global/semester').'</td><td> <SELECT name="semester" onChange="window.document.auswahlFrm.submit();">';
	foreach($vorhandenesemester as $i)
	{
		$selected = '';
		if($semester=='')
			$semester = $i;
		if($i==$semester)
			$selected = 'selected';

		echo '<option value="'.$i.'" '.$selected.'>'.$i.'. '.$p->t('global/semester').'</option>';
	}
	echo '</SELECT></td></tr>';
}
else
	$errormsg .= "$stg_obj->errormsg";

echo '<tr>
		<td>'.$p->t('lehre/studienplan').'</td>
		<td><SELECT name="studienplan_id" onChange="window.document.auswahlFrm.submit();">';

$last_sto = '';

$studienplan_obj = new studienplan();
$studienplan_obj->getStudienplaeneFromSem($stg_kz, $studiensemester_kurzbz, $semester);
$studienordnung_arr = array();
$studienplan_arr = array();
foreach($studienplan_obj->result as $row_sto)
{
	$studienordnung_arr[$row_sto->studienordnung_id]['bezeichnung']=$row_sto->bezeichnung_studienordnung;
	$studienplan_arr[$row_sto->studienordnung_id][$row_sto->studienplan_id]['bezeichnung']=$row_sto->bezeichnung_studienplan;
	$studienplan_arr[$row_sto->studienordnung_id][$row_sto->studienplan_id]['orgform_kurzbz']=$row_sto->orgform_kurzbz;
	$studienplan_arr[$row_sto->studienordnung_id][$row_sto->studienplan_id]['sprache']=$sprachen_arr[$row_sto->sprache];
}
// Pruefen ob uebergebene StudienplanID in Auswahl enthalten
// ist und ggf auf leer setzen
if($studienplan_id!='')
{
	$studienplan_found=false;
	foreach($studienplan_arr as $stoid=>$row_sto)
	{
		if(array_key_exists($studienplan_id, $studienplan_arr[$stoid]))
		{
			$studienplan_found=true;
			break;
		}
	}
	if(!$studienplan_found)
	{
		$studienplan_id='';
	}
}
foreach($studienordnung_arr as $stoid=>$row_sto)
{
	$selected='';

	if($studienordnung_id=='')
		$studienordnung_id=$stoid;

	echo '<option value="" disabled>'.$p->t('lehre/studienordnung').': '.$db->convert_html_chars($row_sto['bezeichnung']).'</option>';

	foreach($studienplan_arr[$stoid] as $stpid=>$row_stp)
	{
		$selected='';
		if($studienplan_id=='')
			$studienplan_id=$stpid;
		if($stpid == $studienplan_id)
			$selected='selected';

		echo '<option value="'.$stpid.'" '.$selected.'>'.$db->convert_html_chars($row_stp['bezeichnung']).' ( '.$orgform_arr[$row_stp['orgform_kurzbz']].', '.$row_stp['sprache'].' )</option>';
	}
}
echo '</select></td></tr>';

$studienplan = new studienplan();
$studienplan->loadStudienplan($studienplan_id);

//Anzeigen des DropDown Menues mit Lehrveranstaltungen
if (defined('ADDON_LVINFO_HIDE_MENU') && ADDON_LVINFO_HIDE_MENU)
{
    echo '<input type="hidden" name="lv_id" value="' . $lv_id . '" />';
}
else
{
    $lv_obj = new lehrveranstaltung();

    if($semester=='')
        $semester=null;
    if($lv_obj->loadLehrveranstaltungStudienplan($studienplan_id, $semester,'bezeichnung'))
    {
        echo '<tr>
		<td>'.$p->t('lvinfo/lvmodul').'</td>
		<td><SELECT name="lv_id" onChange="window.document.auswahlFrm.submit();">';

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
                $lv_id = '';
            }
            $outputstring = '';
            foreach($lv_obj->lehrveranstaltungen as $row)
            {
                // Wenn LV-Info deaktiviert dann ueberspringen
                if(!$row->lvinfo)
                    continue;

                $selected = '';
                if($lv_id=='')
                    $lv_id=$row->lehrveranstaltung_id;
                if($row->lehrveranstaltung_id==$lv_id)
                    $selected = 'selected';

                $outputstring = '( '.$row->lehrform_kurzbz.' )';
                echo '<option value="'.$row->lehrveranstaltung_id.'" '.$selected.'>'.$db->convert_html_chars($row->bezeichnung.' '.$outputstring).'</option>';
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
}

echo '</table>';
echo '<input type="submit" value="'.$p->t('global/anzeigen').'">';

if (defined('ADDON_LVINFO_HIDE_MENU') && ADDON_LVINFO_HIDE_MENU)
    echo '</div>';

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
		die($rechte->errormsg);
	}
}
// LV Information anzeigen

//Wenn LV-Info für das gewählte Studiensemester vorhanden, diese laden, sonst leeres Set anzeigen
$lvinfo = new lvinfo();
$lvinfo->loadLvinfo($lv_id, $studiensemester_kurzbz);

//Arrays nach unterschieden vergleichen
$lvinfo_array = array();
$lvinfo_compare = array();
$laststatus_arr = array();
foreach($lvinfo->result AS $row)
{
	$lang = $row->sprache;
	foreach($row->data AS $key=>$value)
	{
		if(!isset($lvinfo_array[$lang][$key]))
			$lvinfo_array[$lang][$key]='';
		$lvinfo_array[$lang][$key] = $value;
		$lvinfo_compare[] = $key;
	}
	$lvinfo_lock = new lvinfo();
	$lvinfo_lock->getLastStatus($row->lvinfo_id);
	$laststatus_arr[$lang] = $lvinfo_lock;
	//echo '<input type="hidden" name="'.$lang.'LVinfo_id" value="'.$row->lvinfo_id.'" />';
}

$lvinfo_compare = array_unique($lvinfo_compare);

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
$data_set = array();
$data_obj = array();
foreach($lvinfo->result AS $row)
{
	$data_set[$row->sprache] = $row->data;
	$data_obj[$row->sprache] = $row;
}
// Ausgabe der Felder wenn Set vorhanden, sonst Hinweis anzeigen
if (!$lvinfo_set->result == '')
{
	$lva = new lehrveranstaltung();
	$lva->load($lv_id);
	$oes = $lva->getAllOe();
	$oes[]=$lva->oe_kurzbz; // Institut

	echo '<form name="editFrm" action="lvinfo.php?lv_id='.$lv_id.'" method="POST">';
	echo '

		<table width="100%" id="tablelvinfo" class="tablesorter">
			<thead>
			<tr>';

	foreach($config_lvinfo_sprachen as $lvinfo_sprache)
	{
		$sprachen_obj = new sprache();
		$sprachen_obj->load($lvinfo_sprache);
		$lvinfo_id = isset($data_obj[$lvinfo_sprache])?$data_obj[$lvinfo_sprache]->lvinfo_id:'';

		// Aktuellen Status anzeigen
		if(isset($laststatus_arr[$lvinfo_sprache])
			&& isset($laststatus_arr[$lvinfo_sprache]->bezeichnung[$lvinfo_sprache])
			&& $laststatus_arr[$lvinfo_sprache]->bezeichnung[$lvinfo_sprache]!='')
		{
			//$aktuellerStatus = '('.$p->t('lvinfo/status').' '.$laststatus_arr[$lvinfo_sprache]->bezeichnung[$sprache].')';
			$aktuellerStatus = $laststatus_arr[$lvinfo_sprache]->lvinfostatus_kurzbz;
		}
		else
		{
			$aktuellerStatus = '';
		}
		$status = new lvinfo();
		$status = $status->getAllStatus();


		echo '
			<th style="text-align: center;" colspan="2">
				<h3>'.$sprachen_obj->bezeichnung_arr[$sprache].' (ID '.$lvinfo_id.')</h3>
				<a href="#" onClick="javascript:window.open(\'view.php?lvinfo_id='.$lvinfo_id.'\',\'Lehrveranstaltungsinformation\',\'width=700,height=750,resizable=yes,menuebar=no,toolbar=no,status=yes,scrollbars=yes\');"><img src="../../../skin/images/system-index-search.png" height="20px" title="anzeigen"></a><br>
				<table style="width: 80%; margin-left: auto; margin-right: auto;">
					<tr>
						<td '.($aktuellerStatus == 'bearbeitung' || $aktuellerStatus == ''?'style="width: 33.33%; background-color: #5cb85c;"':'style="width: 33.33%;"').'>'.$status['bearbeitung'][$sprache].'</td>
						<td '.($aktuellerStatus == 'abgeschickt'?'style="width: 33.33%; background-color: #f0ad4e;"':'style="width: 33.33%;"').'>'.$status['abgeschickt'][$sprache].'</td>
						<td '.($aktuellerStatus == 'freigegeben'?'style="width: 33.33%; background-color: #d9534f;"':'style="width: 33.33%;"').'>'.$status['freigegeben'][$sprache].'</td>
					</tr>
					<tr>
						<td style="font-weight: normal; font-size: 8pt; text-align: center">';
							if ($aktuellerStatus == 'bearbeitung' || $aktuellerStatus == '')
							{
								if ($rechte->isBerechtigtMultipleOe('addon/lvinfofreigabe',$oes,'s') && $aktuellerStatus != '')
									echo '<button type="button" name="action" value="freigeben" onclick="freigabe(\''.$lvinfo_id.'\',\''.$stg_kz.'\',\''.$semester.'\',\''.$orgform_kurzbz.'\',\''.$studiensemester_kurzbz.'\',\''.$studienplan_id.'\',\''.$lv_id.'\'); return false;">'.$p->t('lvinfo/freigeben').'</button>';
								else
									echo $p->t('lvinfo/erklaerungstextBearbeitung');
							}
						echo '</td><td style="font-weight: normal; font-size: 8pt; text-align: center">';
							if ($aktuellerStatus == 'abgeschickt')
							{
								if ($rechte->isBerechtigtMultipleOe('addon/lvinfofreigabe',$oes,'s'))
									echo '<button type="button" name="action" value="freigeben" onclick="freigabe(\''.$lvinfo_id.'\',\''.$stg_kz.'\',\''.$semester.'\',\''.$orgform_kurzbz.'\',\''.$studiensemester_kurzbz.'\',\''.$studienplan_id.'\',\''.$lv_id.'\'); return false;">'.$p->t('lvinfo/freigeben').'</button>';
								else
									echo $p->t('lvinfo/erklaerungstextAbgeschickt');
							}
						echo '</td><td style="font-weight: normal; font-size: 8pt; text-align: center">';
							if ($aktuellerStatus == 'freigegeben')
							{
								if ($rechte->isBerechtigtMultipleOe('addon/lvinfofreigabe',$oes,'s'))
									echo '<button type="button" name="action" value="reset" onclick="bearbeiten(\''.$lvinfo_id.'\',\''.$stg_kz.'\',\''.$semester.'\',\''.$orgform_kurzbz.'\',\''.$studiensemester_kurzbz.'\',\''.$studienplan_id.'\',\''.$lv_id.'\'); return false;">'.$p->t('lvinfo/freigabeAufheben').'</button>';
								else
									echo $p->t('lvinfo/erklaerungstextFreigabe');
							}
						echo '</td>';
					echo '</tr>
				</table>
			</th>';
	}
	echo '</tr></thead>
	<tbody>';

	$locked = false;
	$i = 0;

	foreach($lvinfo_set->result as $row_set)
	{
		$i++;
		echo '<tr class="'.(($i%2 == 0)?'even':'odd').'">';

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

	// Freigabeempfänger auslesen
	// Studiengangsleitung
	$stgleitung = $stg_obj->getLeitung($stg_kz);
	$empfaenger_uid = array();
	$empfaenger = array();
	foreach($stgleitung as $rowltg)
		$empfaenger_uid[] = $rowltg;

	// geschaeftsfuehrende Studiengangsleitung
	$stg_hlp = new studiengang();
	$stg_hlp->load($stg_kz);
	$bnf = new benutzerfunktion();
	$bnf->getBenutzerFunktionen('gLtg', $stg_hlp->oe_kurzbz);
	foreach($bnf->result as $rowbnf)
		$empfaenger_uid[] = $rowbnf->uid;

	$empfaenger_uid = array_unique($empfaenger_uid);

	foreach($empfaenger_uid as $uid)
	{
		$benutzer = new benutzer();
		$benutzer->load($uid);

		$empfaenger[] = '<a href="mailto:'.($benutzer->alias != ''?$benutzer->alias.'@'.DOMAIN:$uid.'@'.DOMAIN).'" title="Mail to">'.$db->convert_html_chars(trim($benutzer->titelpre.' '.$benutzer->vorname.' '.$benutzer->nachname.' '.$benutzer->titelpost)).'</a>';
	}
	$lockSend = false;
	// Wenn kein Empfaenger gefunden wurde, an die Mailadresse des Studiengangs schicken
	if (count($empfaenger) == 0)
	{
		// Wenn keine Mailadresse beim Studiengang hinterlegt ist, Hinweis ausgeben und Freigabebutton sperren
		if ($stg_hlp->email != '')
			$empfaenger[] = $stg_hlp->email;
		else
		{
			$empfaenger[] = '<span style="color: red">'.$p->t('lvinfo/keinEmpfaengerGefunden', array(MAIL_CIS)).'</span>';
			$lockSend = true;
		}
	}
	$empfaenger = implode('<br/>', $empfaenger);

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
	$locked = true;
	foreach($config_lvinfo_sprachen as $lvinfo_sprache)
		if(!(isset($laststatus_arr[$lvinfo_sprache]) && in_array($laststatus_arr[$lvinfo_sprache]->lvinfostatus_kurzbz,array('freigegeben','abgeschickt'))))
			$locked = false;

	echo '</tbody>
	<tfoot>
		<tr>
		<td colspan="5" style="text-align: center"><br/>
			<input type="submit" name="save" '.($locked?'disabled="disabled"':'').' value="'.$p->t('global/speichern').'"><br/><br/>
			<input type="submit" name="saveAndSend" '.($locked || $lockSend?'disabled="disabled"':'').' value="'.$p->t('lvinfo/speichernUndFreigeben').'"><br/><br/>
			'.$p->t('lvinfo/freigabeberechtigt').'<br/>
			'.$empfaenger.'
		</td>
		</tr>';
	echo '</tfoot></table>';

	echo '<input type="hidden" name="studiensemester_kurzbz" value="'.$db->convert_html_chars($studiensemester_kurzbz).'">';
	foreach($lvinfo->result AS $row)
	{
		echo '<input type="hidden" name="'.$row->sprache.'LVinfo_id" value="'.$row->lvinfo_id.'" />';
	}
	echo '</form>';
}
else
{
	echo '<table width="100%" id="tableNoSet" class="tablesorter">
		<tr>
			<td style="text-align: center; background-color: #f0ad4e">'.$p->t('lvinfo/keinSetHinterlegt', array($studiensemester_kurzbz)).'</td>
		</tr>
		</table>';
}

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
			echo '<textarea name="'.$sprache.'['.$key.']" style="width:98%" rows="5" cols="50" '.($locked?'readonly="readonly"':'').'>'.$db->convert_html_chars($value).'</textarea>';
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
				echo '<input name="'.$sprache.'['.$key.'][]" style="width:98%" type="text" value="'.$db->convert_html_chars($val).'" '.($locked?'readonly="readonly"':'').'><br>';
			echo '</div>';
			if(!$locked)
				echo '<a href="#add" onclick="addInput(\''.$sprache.'\',\''.$key.'\');return false;"><img src="../../../skin/images/list-add.png" height="20px" alt="Eintrag hinzufügen"/></a>';
			break;

		default:
			echo '<input name="'.$sprache.'['.$key.']" style="width:98%" type="text" '.($locked?'readonly="readonly"':'').' value="'.$db->convert_html_chars($data[$key]).'">';
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
