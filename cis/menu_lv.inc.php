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
 */
/**
 * Hinzufuegen von neuen Menuepunkten bei CIS Lehrveranstaltungen
 */
require_once(dirname(__FILE__).'/../include/lvinfo.class.php');

// LV-Info aus Core ausblenden
$addon_lvinfo_col = array_column($menu,'id');
unset($menu[array_search('core_menu_lvinfo', $addon_lvinfo_col)]);

// LVINFO Menuepunkt aus Addon einblenden
if(!defined('CIS_LEHRVERANSTALTUNG_LVINFO_ANZEIGEN') || CIS_LEHRVERANSTALTUNG_LVINFO_ANZEIGEN)
{
	$text='';
    $link='';
    $onclick='';

    $lvinfo = new lvinfo();
    $lvinfo->loadLvinfo($lvid, $angezeigtes_stsem, null, true);

	if(count($lvinfo->result)>0)
    {
	       $link="../../../addons/lvinfo/cis/view.php?lehrveranstaltung_id=".urlencode($lvid)."&studiensemester_kurzbz=".urlencode($angezeigtes_stsem);
           $onclick="javascript:window.open('".$link."','Lehrveranstaltungsinformation','width=700,height=750,resizable=yes,menuebar=no,toolbar=no,status=yes,scrollbars=yes');";
           $link='#';
	}

	// Bearbeiten Button anzeigen wenn Lektor der LV und bearbeiten fuer Lektoren aktiviert ist
	// Oder Berechtigung zum Bearbeiten eingetragen ist
	if((!defined('CIS_LEHRVERANSTALTUNG_LVINFO_LEKTOR_EDIT') && $lektor_der_lv)
	  || (defined('CIS_LEHRVERANSTALTUNG_LVINFO_LEKTOR_EDIT') && CIS_LEHRVERANSTALTUNG_LVINFO_LEKTOR_EDIT==true && $lektor_der_lv)
	  || $rechte->isBerechtigt('addon/lvinfo',$studiengang_kz)
	  || $rechte->isBerechtigtMultipleOe('addon/lvinfo', $lehrfach_oe_kurzbz_arr)
	  )
	{
		$text.= "<a href='../../../addons/lvinfo/cis/lvinfo.php?lv_id=$lvid&studiensemester_kurzbz=$angezeigtes_stsem' target='_blank' class='Item'>".$p->t('lehre/lvInfoBearbeiten')."</a>";
	}
	elseif ($is_lector)
	{
		$text.= "Bearbeiten der LV-Infos derzeit gesperrt";
	}

	$menu[]=array
	(
		'id'=>'addon_lvinfo_menu_lvinfo',
		'position'=>'5',
		'name'=>$p->t('lvinfo/lehrveranstaltungsinformationen'),
		'icon'=>'../../../skin/images/button_lvinfo.png',
		'link'=>$link,
        'link_onclick'=>$onclick,
		'text'=>$text
	);
}

?>
