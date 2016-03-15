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
 * Zeigt die Unterschiede zwischen der Uebergebenen und
 * der zuletzt freigegebenen Version an
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

$uid = get_uid();

if(!check_lektor($uid))
    die('Sie haben keine Berechtigung für diese Seite');

$sprache = getSprache();
$p = new phrasen($sprache);
$lvinfo_id = filter_input(INPUT_GET, 'lvinfo_id');

echo '<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>'.$p->t('lvinfo/lvinformationen').'</title>
	<link rel="stylesheet" href="../../../skin/style.css.php" type="text/css">
    <link rel="stylesheet" href="../../../skin/tablesort.css" type="text/css">
    <link rel="stylesheet" href="../skin/lvinfo.css" type="text/css">
    <script src="../../../include/js/jquery1.9.min.js" type="text/javascript"></script>
</head>
<body>
';
echo '<h1>Unterschiede zur Vorversion</h1>';

$data = getDiffPreviousData($lvinfo_id);

printInfoTable($data['new']->lehrveranstaltung_id, $data['new']->studiensemester_kurzbz, $sprache);

echo'<div class="lvinfo">';
foreach($data['diff'] as $key=>$row_data)
{
    echo '<h2>'.$row_data['bezeichnung'].'</h2>';

    echo'<div class="lvinfo_data">';

    if(is_array($row_data['diff']))
    {
        echo'<ul>';
        foreach($row_data['diff'] as $item)
            echo'<li>'.$item.'</li>';
        echo'</ul>';
    }
    else
    {
        echo$row_data['diff'];
    }
    echo'</div>';
}
echo'</div>';

?>