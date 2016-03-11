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
 * Authors: Andreas Oesterreicher <andreas.oesterreicher@technikum-wien.at>
 */
/**
 * Initialisierung des Addons
 */
?>
if(typeof addon =='undefined')
	var addon=Array();

addon.push(
{
	init: function(page, params)
	{
		// Diese Funktion wird nach dem Laden der Seite im CIS aufgerufen
		switch(page)
		{
			case 'cis/public/incoming/incoming.php':
				if(params.method=='lehrveranstaltungen')
				{
					$('a').each(function(){
						if($(this).attr('href')=='#Deutsch')
						{
							var lvid = $('td:first-child', $(this).parent().parent()).html();
							this.onclick=function()
							{
								window.open('../../../addons/lvinfo/cis/view.php?lehrveranstaltung_id='+lvid+'&amp;sprache=German','Lehrveranstaltungsinformation','width=700,height=750,resizable=yes,menuebar=no,toolbar=no,status=yes,scrollbars=yes');
								return false;
							}
						}
						if($(this).attr('href')=='#Englisch')
						{
							var lvid = $('td:first-child', $(this).parent().parent()).html();
							this.onclick=function()
							{
								window.open('../../../addons/lvinfo/cis/view.php?lehrveranstaltung_id='+lvid+'&amp;sprache=English','Lehrveranstaltungsinformation','width=700,height=750,resizable=yes,menuebar=no,toolbar=no,status=yes,scrollbars=yes');
								return false;
							}
						}
					});
				}
				break;

			default:
				break;
		}
	}
});
