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
  * Exportiert die Lehrveranstaltungsinformationen als JSON
  *
  * Aufruf: export.php?studiengang_kz=227
  *
  * zusätzliche Parameter:
  * &prettyprint=true  Daten werden in Menschen lesbarer Form angezeigt
  * &orgform_kurzbz=BB Zeigt nur die Einträge einer Organisationsform an
  */
require_once('../../../config/vilesci.config.inc.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../../../include/studiensemester.class.php');
require_once('../../../include/organisationseinheit.class.php');
require_once('../../../include/lehrveranstaltung.class.php');
require_once('../../../include/studienplan.class.php');
require_once('../../../include/datum.class.php');
require_once('../include/lvinfo.class.php');

$studiengang_kz = filter_input(INPUT_GET, 'studiengang_kz');
$orgform_kurzbz = filter_input(INPUT_GET, 'orgform_kurzbz');
$prettyprint = filter_input(INPUT_GET, 'prettyprint');
$datum_obj = new datum();

if($orgform_kurzbz=='')
    $orgform_kurzbz=null;

$studiengang = new studiengang();
$studiengang->load($studiengang_kz);
$data = array();
for($semester=1;$semester<=$studiengang->max_semester;$semester++)
{
    $studiensemester_obj = new studiensemester();
    $studiensemester_kurzbz = $studiensemester_obj->getNearest($semester);

    $studienplan = new studienplan();
    $studienplan->getStudienplaeneFromSem($studiengang_kz, $studiensemester_kurzbz, $semester, $orgform_kurzbz);
    if(!isset($studienplan->result[0]))
        die('Es wurde kein eindeutiger Studienplan gefunden');
    $studienplan_id = $studienplan->result[0]->studienplan_id;
    $lehrveranstaltung = new lehrveranstaltung();

    $lehrveranstaltung->loadLehrveranstaltungStudienplan($studienplan_id, $semester);

    $tree = $lehrveranstaltung->getLehrveranstaltungTree();

    $data[$semester] = bauen($tree);
}

function bauen($tree)
{
    global $studiensemester_kurzbz, $sprache, $datum_obj;
    $db = new basis_db();
    $data = array();
    $i=0;
    $lastupdate='';

    foreach($tree as $row)
    {
        $data[$i]['lehrveranstaltung_id']=$row->lehrveranstaltung_id;
        $data[$i]['semester']=$row->semester;
        $data[$i]['bezeichnung']=$row->bezeichnung;
        $data[$i]['bezeichnung_englisch']=$row->bezeichnung_english;
        $data[$i]['unterrichtssprache']=$row->sprache;
        $data[$i]['ects']=$row->ects;
        $data[$i]['organisationsform']=$row->orgform_kurzbz;

        $lvinfo = new lvinfo();
        $lvinfo->loadLvinfo($row->lehrveranstaltung_id, $studiensemester_kurzbz, null, true);

        $lvinfo_set = new lvinfo();
        $setstsem = $lvinfo_set->getGueltigesStudiensemester($studiensemester_kurzbz);
        $lvinfo_set->load_lvinfo_set($setstsem);

        foreach($lvinfo->result as $row_lvinfo)
        {
            $lvinfodata = '';
            // Ausgabe der Felder
            foreach($lvinfo_set->result as $row_set)
            {
                $lvinfodataelem='<h2>'.$row_set->lvinfo_set_bezeichnung[$row_lvinfo->sprache].'</h2>';
                if(isset($row_set->einleitungstext[$row_lvinfo->sprache]))
                    $lvinfodataelem.=$row_set->einleitungstext[$row_lvinfo->sprache].'<br><br>';

                $key = $row_set->lvinfo_set_kurzbz;
                $lvinfodataelembody='';
                switch($row_set->lvinfo_set_typ)
                {
                    case 'boolean':
                        $p1 = new phrasen($lvinfo->sprache);

                        if(isset($row_lvinfo->data[$key]) && $row_lvinfo->data[$key]===true)
                            $lvinfodataelembody.= $p1->t('global/ja');
                        else
                            $lvinfodataelembody.= $p1->t('global/nein');
                        break;

                    case 'array':
                        if(isset($row_lvinfo->data[$key]))
                            $value=$row_lvinfo->data[$key];
                        else
                            $value=array();

                        $lvinfodataelembody.= '<ul>';
                        foreach($value as $val)
                            $lvinfodataelembody.= '<li>'.$db->convert_html_chars($val).'</li>';
                        $lvinfodataelembody.= '</ul>';
                        break;

                    case 'text':
                    default:
                        if(isset($row_lvinfo->data[$key]))
                             $lvinfodataelembody.= $db->convert_html_chars($row_lvinfo->data[$key]);
                }
                if($lvinfodataelembody!='')
                    $lvinfodata.=$lvinfodataelem.$lvinfodataelembody;

            }

            $data[$i]['lvinfo'][$row_lvinfo->sprache]=$lvinfodata;
            $lastupdate = $row_lvinfo->updateamum;
        }
        $data[$i]['lastupdate']=$datum_obj->formatDatum($lastupdate,'Y-m-d H:i:s');
        if(isset($row->childs) && count($row->childs)>0)
        {
            $data[$i]['childs']=bauen($row->childs);
        }
        $i++;
    }
    return $data;
}


if($prettyprint)
{
    echo '<pre>';
    echo $lehrveranstaltung->convert_html_chars(json_encode($data,JSON_PRETTY_PRINT));
    echo '</pre>';
}
else
    echo json_encode($data);

 ?>