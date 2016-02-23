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
 * Übersichtsliste für Leiter
 */
require_once('../../../config/cis.config.inc.php');
require_once('../../../config/global.config.inc.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/phrasen.class.php');
require_once('../../../include/lehrveranstaltung.class.php');
require_once('../../../include/studiengang.class.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../../../include/organisationsform.class.php');
require_once('../../../include/studiensemester.class.php');
require_once('../../../include/sprache.class.php');
require_once('../../../include/benutzer.class.php');
require_once('../include/lvinfo.class.php');
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
	<title><?php echo $p->t('lvinfo/lehrveranstaltungsinformationenuebersicht'); ?></title>
	<link rel="stylesheet" href="../../../skin/style.css.php" type="text/css">
    <link rel="stylesheet" href="../../../skin/tablesort.css" type="text/css">
    <script src="../../../include/js/jquery1.9.min.js" type="text/javascript"></script>
    <link rel="stylesheet" href="../../../skin/tablesort.css" type="text/css">
    <script type="text/javascript" src="../../../include/js/jquery1.9.min.js"></script>

	<script type="text/javascript">
	function addInput(sprache, key)
	{
		$('#input_arr_'+sprache+'_'+key).append('<input name="'+sprache+'['+key+'][]" size="50" type="text" value=""><br>');
	}

    function freigabe(lvinfo_id, stg_kz, semester, orgform_kurzbz, studiensemester_kurzbz)
    {
        var url='lvinfo_uebersicht.php?stg_kz='+stg_kz+'&semester='+semester+'&orgform_kurzbz='+orgform_kurzbz+'&studiensemester_kurzbz='+studiensemester_kurzbz;
        $("#data").html('<form action="'+url+'" name="sendform" id="sendform" method="POST"><input type="hidden" name="action" value="freigabe" /><input type="hidden" name="lvinfo_id" value="'+lvinfo_id+'" /></form>');
        document.sendform.submit();
    }

    function reset(lvinfo_id, stg_kz, semester, orgform_kurzbz, studiensemester_kurzbz)
    {
        var url='lvinfo_uebersicht.php?stg_kz='+stg_kz+'&semester='+semester+'&orgform_kurzbz='+orgform_kurzbz+'&studiensemester_kurzbz='+studiensemester_kurzbz;
        $("#data").html('<form action="'+url+'" name="sendform" id="sendform" method="POST"><input type="hidden" name="action" value="reset" /><input type="hidden" name="lvinfo_id" value="'+lvinfo_id+'" /></form>');
        document.sendform.submit();
    }
	</script>
</head>
<body>
    <div id="data"></div>
<?php

if(isset($_POST['action']))
{
    switch($_POST['action'])
    {
        case 'freigabe':
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
                    if($lvinfo->setStatus($lvinfo_id,'freigegeben',$user))
                        echo 'Gespeichert '.$lvinfo_id;
                }
                else
                {
            		echo '<span class="error">'.$p->t('global/keineBerechtigungFuerDieseSeite').'</span>';
            	}
            }
            else
            {
                echo '<span class="error">'.$p->t('global/fehlerBeimAktualisierenDerDaten').'</span>';
            }
            break;

        case 'reset':
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
                    if($lvinfo->setStatus($lvinfo_id,'bearbeitung',$user))
                        echo 'Gespeichert '.$lvinfo_id;
                }
                else
                {
                    echo '<span class="error">'.($p->t('global/keineBerechtigungFuerDieseSeite')).'</span>';
                }
            }
            else
            {
                echo '<span class="error">'.$p->t('global/fehlerBeimAktualisierenDerDaten').'</span>';
            }

            break;
    }
}
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

$lv = new lehrveranstaltung();
$lv->load($lv_id);
$stg = new studiengang();
$stg->load($lv->studiengang_kz);

echo '<H1>'.$p->t('lvinfo/lehrveranstaltungsinformationenuebersicht').'</H1>';
echo '<form name="auswahlFrm" action="lvinfo_uebersicht.php" method="GET">';

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

echo '</table>';
echo '<input type="submit" value="'.$p->t('global/anzeigen').'">';
echo '</form>';



//Liste der LVs anzeigen

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

	if(count($lv_obj->lehrveranstaltungen)>0)
	{
        echo '
        <script type="text/javascript">
        $(document).ready(function()
        {
            $("#t1").tablesorter(
            {
                sortList: [[0,0]],
                widgets: ["zebra"]
            });
        });
        </script>
        <table id="t1" class="tablesorter" style="width:auto;">
            <thead>
            <tr>
                <th>Lehrveranstaltung</th>';
        foreach($config_lvinfo_sprachen as $row_sprache)
        {
            $sprache_obj = new sprache();
            $sprache_obj->load($row_sprache);
            echo '<th>'.$sprache_obj->bezeichnung_arr[$sprache].'</th>';
        }
        echo '</tr>';

        echo '
            </thead>
            <tbody>
            ';

		foreach($lv_obj->lehrveranstaltungen as $row)
		{
            echo '
            <tr>
                <td valign="top">'.$db->convert_html_chars($outputstring.$row->bezeichnung).'</td>
                ';
            $lvinfo = new lvinfo();
            $lvinfo->loadLvinfo($row->lehrveranstaltung_id, $studiensemester_kurzbz);

            foreach($config_lvinfo_sprachen as $row_sprachen)
            {
                $found=false;
                foreach($lvinfo->result as $row_lvinfo)
                {
                    if($row_sprachen != $row_lvinfo->sprache)
                        continue;

                    $status = new lvinfo();
                    $status->getLastStatus($row_lvinfo->lvinfo_id);

                    $found=true;

                    echo '
                    <td>
                        <a href="lvinfo.php?lv_id='.$row_lvinfo->lehrveranstaltung_id.'&studiensemester_kurzbz='.$studiensemester_kurzbz.'"><img src="../../../skin/images/edit.png" height="20px" title="bearbeiten"></a>
                        <a href="view.php?lvinfo_id='.$row_lvinfo->lvinfo_id.'"><img src="../../../skin/images/eye.png" height="20px" title="anzeigen"></a>
                        <a href="diff.php?lvinfo_id='.$row_lvinfo->lvinfo_id.'"><img src="../../../skin/images/merge.png" height="20px" title="Unterschiede zur Vorversion anzeigen"/></a>
                    ';

                    if(isset($status->bezeichnung[$sprache]))
                        echo $status->bezeichnung[$sprache];

                    switch($status->lvinfostatus_kurzbz)
                    {
                        case 'abgeschickt':
                            // Freigabe oder Reject
                            echo ' Freigeben:';
                            echo ' <a href="#freigabe" onclick="freigabe(\''.$row_lvinfo->lvinfo_id.'\',\''.$stg_kz.'\',\''.$semester.'\',\''.$orgform_kurzbz.'\',\''.$studiensemester_kurzbz.'\'); return false;"><img src="../../../skin/images/true.png" /></a>';
                            echo ' <a href="#zuruecksetzen" onclick="reset(\''.$row_lvinfo->lvinfo_id.'\',\''.$stg_kz.'\',\''.$semester.'\',\''.$orgform_kurzbz.'\',\''.$studiensemester_kurzbz.'\'); return false;"><img src="../../../skin/images/false.png" /></a>';
                            break;
                        case 'freigegeben':
                            // freigabe aufheben
                            echo ' <a href="#zuruecksetzen"  onclick="reset(\''.$row_lvinfo->lvinfo_id.'\',\''.$stg_kz.'\',\''.$semester.'\',\''.$orgform_kurzbz.'\',\''.$studiensemester_kurzbz.'\'); return false;">Sperre aufheben</a>';
                            break;
                        case 'bearbeitung':
                        default:

                            break;
                    }
                    echo '</td>';
                }
                //echo '</td>';
                if(!$found)
                {
                    echo '<td></td>';
                }
            }
            echo '</tr>';
		}
        echo '</tbody></table>';
	}
}
else
{
	$errormsg .= $lv_obj->errormsg;
}


?><br><br><br><br><br>
</body>
</html>
