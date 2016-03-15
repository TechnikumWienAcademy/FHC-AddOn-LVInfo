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

require_once(dirname(__FILE__).'/../../../include/basis_db.class.php');
require_once(dirname(__FILE__).'/../../../include/sprache.class.php');

class lvinfo extends basis_db
{
	public $new=true;
	public $result = array();

	//Tabellenspalten
	public $lvinfo_set_id;						// integer
	public $lvinfo_set_kurzbz;					// varchar(16)
	public $lvinfo_set_bezeichnung;				// varchar(64)[]
	public $einleitungstext;					// varchar(512)[]
	public $sort;								// integer
	public $lvinfo_set_typ;						// varchar(32)
	public $gueltigab_studiensemester_kurzbz;	// varchar(6)
	public $lvinfo_id;							// integer
	public $sprache;							// varchar(16)
	public $lehrveranstaltung_id;				// integer
	public $studiensemester_kurzbz;				// varchar(6)
	public $data;								// jsonb
	public $lvinfostatus_kurzbz;				// varchar(32)
	public $tbl_lvinfostatus_bezeichnung;		// varchar(64)
	public $gesetztamum;						// timestamp
	public $uid;								// varchar(32)
	public $updateamum;							// timestamp
	public $updatevon=0;						// varchar(32)
	public $insertamum;							// timestamp
	public $insertvon=0;						// varchar(32)
	public $bezeichnung;
	public $oe_kurzbz;

	/**
	 * Konstruktor
	 * @param $conn Connection zur DB
	 *        $lvinfo_id ID des zu ladenden Datensatzes
	 */
	public function __construct($lvinfo_id=null)
	{
		parent::__construct();

		if($lvinfo_id != null && is_numeric($lvinfo_id))
			$this->load($lvinfo_id);
	}

	/**
	 * Laedt eine LVInfo
	 * @param integer $lvinfo_id
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function load($lvinfo_id)
	{
		if($lvinfo_id == '' || !is_numeric($lvinfo_id))
		{
			$this->errormsg = 'lvinfo_id ist ungültig';
			return false;
		}
		$qry = "SELECT * FROM addon.tbl_lvinfo
				WHERE lvinfo_id = ".$this->db_add_param($lvinfo_id, FHC_INTEGER).";";

		if(!$this->db_query($qry))
		{
			$this->errormsg = 'Fehler beim Laden des Datensatzes';
			return false;
		}

		if($row = $this->db_fetch_object())
		{
			$this->lvinfo_id				= $row->lvinfo_id;
			$this->sprache 					= $row->sprache;
			$this->lehrveranstaltung_id		= $row->lehrveranstaltung_id;
			$this->studiensemester_kurzbz	= $row->studiensemester_kurzbz;
			$this->data						= json_decode($row->data,true);
			$this->insertamum 				= $row->insertamum;
			$this->insertvon 				= $row->insertvon;
			$this->updateamum 				= $row->updateamum;
			$this->updatevon     			= $row->updatevon;
			return true;
		}
		else
		{
			$this->errormsg = 'Es ist kein Datensatz mit dieser ID ('.$lvinfo_id.') vorhanden';
			return false;
		}
	}

	/**
	 * Laedt eine LVInfo nach lehrveranstaltung und studiensemester
	 * @param integer $lehrveranstaltung_id
	 * @param string $studiensemester_kurzbz
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function loadLvinfo($lehrveranstaltung_id, $studiensemester_kurzbz, $sprache=null, $freigegeben=null)
	{
		if($lehrveranstaltung_id == '' || !is_numeric($lehrveranstaltung_id))
		{
			$this->errormsg = 'Lehrveranstaltung_id ist ungültig';
			return false;
		}
		$qry = "SELECT
					*
				FROM
					addon.tbl_lvinfo
				WHERE
					lehrveranstaltung_id=".$this->db_add_param($lehrveranstaltung_id, FHC_INTEGER)."
					AND studiensemester_kurzbz=".$this->db_add_param($studiensemester_kurzbz, FHC_STRING);

		if(!is_null($sprache))
		{
			$qry.=" AND sprache=".$this->db_add_param($sprache);
		}

		if(!is_null($freigegeben) && $freigegeben===true)
		{
			$qry.=" AND EXISTS(SELECT * FROM addon.tbl_lvinfostatus_zuordnung WHERE lvinfo_id=tbl_lvinfo.lvinfo_id AND lvinfostatus_kurzbz='freigegeben')";
		}

		if($result = $this->db_query($qry))
		{
			while($row = $this->db_fetch_object($result))
			{
				$lv = new lvinfo();

				$lv->lvinfo_id = $row->lvinfo_id;
				$lv->sprache = $row->sprache;
				$lv->lehrveranstaltung_id = $row->lehrveranstaltung_id;
				$lv->studiensemester_kurzbz = $row->studiensemester_kurzbz;
				$lv->data = json_decode($row->data,true);
				$lv->insertamum = $row->insertamum;
				$lv->insertvon = $row->insertvon;
				$lv->updateamum = $row->updateamum;
				$lv->updatevon = $row->updatevon;

				$this->result[] = $lv;
			}
			return true;
		}
		else
		{
			$this->errormsg = 'Fehler beim Laden der Daten';
			return false;
		}
	}

	/**
	 * Die die letzte Version der LVInformationen zu einer Lehrveranstaltung
	 * @param integer $lehrveranstaltung_id
	 * @param string $studiensemester_kurzbz
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function loadLastLvinfo($lehrveranstaltung_id, $freigegeben=null)
	{
		if($lehrveranstaltung_id == '' || !is_numeric($lehrveranstaltung_id))
		{
			$this->errormsg = 'Lehrveranstaltung_id ist ungültig';
			return false;
		}
		$qry = "SELECT
					*
				FROM
					addon.tbl_lvinfo
				WHERE
					lehrveranstaltung_id=".$this->db_add_param($lehrveranstaltung_id, FHC_INTEGER)."
					AND studiensemester_kurzbz=(SELECT studiensemester_kurzbz
						FROM
							addon.tbl_lvinfo
							JOIN public.tbl_studiensemester USING(studiensemester_kurzbz)
						WHERE lehrveranstaltung_id=".$this->db_add_param($lehrveranstaltung_id, FHC_INTEGER);
		if(!is_null($freigegeben) && $freigegeben===true)
		{
			$qry.=" AND EXISTS(SELECT * FROM addon.tbl_lvinfostatus_zuordnung WHERE lvinfo_id=tbl_lvinfo.lvinfo_id AND lvinfostatus_kurzbz='freigegeben')";
		}
		$qry.="ORDER BY tbl_studiensemester.start desc LIMIT 1)";

		if(!is_null($freigegeben) && $freigegeben===true)
		{
			$qry.=" AND EXISTS(SELECT * FROM addon.tbl_lvinfostatus_zuordnung WHERE lvinfo_id=tbl_lvinfo.lvinfo_id AND lvinfostatus_kurzbz='freigegeben')";
		}

		if($result = $this->db_query($qry))
		{
			while($row = $this->db_fetch_object($result))
			{
				$lv = new lvinfo();

				$lv->lvinfo_id = $row->lvinfo_id;
				$lv->sprache = $row->sprache;
				$lv->lehrveranstaltung_id = $row->lehrveranstaltung_id;
				$lv->studiensemester_kurzbz = $row->studiensemester_kurzbz;
				$lv->data = json_decode($row->data,true);
				$lv->insertamum = $row->insertamum;
				$lv->insertvon = $row->insertvon;
				$lv->updateamum = $row->updateamum;
				$lv->updatevon = $row->updatevon;

				$this->result[] = $lv;
			}
			return true;
		}
		else
		{
			$this->errormsg = 'Fehler beim Laden der Daten';
			return false;
		}
	}

	/**
	 * Laedt die vorhergehende Version einer LVInfo
	 * @param integer $lvinfo_id
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function loadPreviousLvinfo($lvinfo_id)
	{
		if($lvinfo_id == '' || !is_numeric($lvinfo_id))
		{
			$this->errormsg = 'lvinfo_id ist ungültig';
			return false;
		}
		$qry = "SELECT
					tbl_lvinfo.*
				FROM
					addon.tbl_lvinfo
					JOIN public.tbl_studiensemester USING(studiensemester_kurzbz)
				WHERE
					(lehrveranstaltung_id,sprache)=(SELECT lehrveranstaltung_id,sprache FROM addon.tbl_lvinfo
						WHERE lvinfo_id=".$this->db_add_param($lvinfo_id, FHC_INTEGER).")
					AND EXISTS(SELECT 1 FROM addon.tbl_lvinfostatus_zuordnung
						WHERE lvinfo_id=tbl_lvinfo.lvinfo_id AND lvinfostatus_kurzbz='freigegeben')
					AND tbl_studiensemester.start<(SELECT start FROM public.tbl_studiensemester
						WHERE studiensemester_kurzbz=(SELECT studiensemester_kurzbz FROM addon.tbl_lvinfo
							WHERE lvinfo_id=".$this->db_add_param($lvinfo_id, FHC_INTEGER).'))
				ORDER BY tbl_studiensemester.start DESC LIMIT 1;';

		if($result = $this->db_query($qry))
		{
			if($row = $this->db_fetch_object($result))
			{
				$this->lvinfo_id = $row->lvinfo_id;
				$this->sprache = $row->sprache;
				$this->lehrveranstaltung_id = $row->lehrveranstaltung_id;
				$this->studiensemester_kurzbz = $row->studiensemester_kurzbz;
				$this->data = json_decode($row->data,true);
				$this->insertamum = $row->insertamum;
				$this->insertvon = $row->insertvon;
				$this->updateamum = $row->updateamum;
				$this->updatevon = $row->updatevon;
				return true;
			}
			else
			{
				$this->errormsg = 'Kein Eintrag gefunden';
				return false;
			}
		}
		else
		{
			$this->errormsg = 'Fehler beim Laden der Daten';
			return false;
		}
	}

	/**
	 * Laedt das gültige Studiensemester aus dem LV-Info-Set für das uebergebene Studiensemester
	 * @param string $studiensemester_kurbz
	 * @return $gueltigab_studiensemester_kurzbz
	 */
	public function getGueltigesStudiensemester($studiensemester_kurbz)
	{
		$qry = "	SELECT
						gueltigab_studiensemester_kurzbz
					FROM
						addon.tbl_lvinfo_set
					JOIN
						public.tbl_studiensemester ON(gueltigab_studiensemester_kurzbz=studiensemester_kurzbz)
					WHERE
						gueltigab_studiensemester_kurzbz IN (
							SELECT
								studiensemester_kurzbz
							FROM
								public.tbl_studiensemester
							WHERE
								ende<=(
									SELECT
										ende
									FROM
										public.tbl_studiensemester
									WHERE
										studiensemester_kurzbz=".$this->db_add_param($studiensemester_kurbz, FHC_STRING)."
									)
							ORDER BY start DESC)
					ORDER BY ende DESC LIMIT 1";

		if($result = $this->db_query($qry))
		{
			if($row = $this->db_fetch_object($result))
			{
				$this->gueltigab_studiensemester_kurzbz = $row->gueltigab_studiensemester_kurzbz;
				return $this->gueltigab_studiensemester_kurzbz;
			}
			else
			{
				$this->errormsg = 'Fehler beim Laden des gueltigen Studiensemesters';
				return false;
			}
		}
		else
		{
			$this->errormsg = 'Es konnte kein gueltige Studiensemester geladen werden';
			return false;
		}
	}

	/**
	 * Laedt das LVInfo Set anhand der ID
	 * @param integer $lvinfo_set_id
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function loadSet($lvinfo_set_id)
	{
		$qry = "SELECT
					*
				FROM
					addon.tbl_lvinfo_set
				WHERE
					lvinfo_set_id=".$this->db_add_param($lvinfo_set_id, FHC_INTEGER);

		if($result = $this->db_query($qry))
		{
			if($row = $this->db_fetch_object($result))
			{
				$this->lvinfo_set_id = $row->lvinfo_set_id;
				$this->lvinfo_set_kurzbz = $row->lvinfo_set_kurzbz;
				$this->lvinfo_set_bezeichnung = $this->db_parse_lang_array($row->lvinfo_set_bezeichnung);
				$this->einleitungstext = $this->db_parse_lang_array($row->einleitungstext);
				$this->sort = $row->sort;
				$this->lvinfo_set_typ = $row->lvinfo_set_typ;
				$this->gueltigab_studiensemester_kurzbz = $row->gueltigab_studiensemester_kurzbz;
				$this->oe_kurzbz = $row->oe_kurzbz;
				$this->insertamum = $row->insertamum;
				$this->insertvon = $row->insertvon;
				$this->updateamum = $row->updateamum;
				$this->updatevon = $row->updatevon;
				$this->new=false;

				return true;
			}
			else
			{
				$this->errormsg = 'Eintrag wurde nicht gefunden';
				return false;
			}
		}
		else
		{
			$this->errormsg = 'Fehler beim Laden der Daten';
			return false;
		}
	}

	/**
	 * Laedt das LVInfo Set mit Gueltigkeit fuer das Uebergebene Studiensemester
	 * @param string $studiensemester_kurbz
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function load_lvinfo_set($studiensemester_kurbz)
	{
		$studiensemester = $this->getGueltigesStudiensemester($studiensemester_kurbz);
		$qry = "SELECT
					*
				FROM
					addon.tbl_lvinfo_set
				WHERE
					gueltigab_studiensemester_kurzbz=".$this->db_add_param($studiensemester, FHC_STRING)."
				ORDER BY sort";

		if($result = $this->db_query($qry))
		{
			while($row = $this->db_fetch_object($result))
			{
				$set = new lvinfo();

				$set->lvinfo_set_id = $row->lvinfo_set_id;
				$set->lvinfo_set_kurzbz = $row->lvinfo_set_kurzbz;
				$set->lvinfo_set_bezeichnung = $this->db_parse_lang_array($row->lvinfo_set_bezeichnung);
				$set->einleitungstext = $this->db_parse_lang_array($row->einleitungstext);
				$set->sort = $row->sort;
				$set->lvinfo_set_typ = $row->lvinfo_set_typ;
				$set->gueltigab_studiensemester_kurzbz = $row->gueltigab_studiensemester_kurzbz;
				$set->oe_kurzbz = $row->oe_kurzbz;
				$set->insertamum = $row->insertamum;
				$set->insertvon = $row->insertvon;
				$set->updateamum = $row->updateamum;
				$set->updatevon = $row->updatevon;

				$this->result[] = $set;
			}
			return true;
		}
		else
		{
			$this->errormsg = 'Es ist kein gueltiges LVInfo-Set fuer das uebergebene Studiensememster ('.$studiensemester_kurbz.') vorhanden';
			return false;
		}
	}

	/**
	 * Laedt das LVInfo Set einer bestimmten kurzbezeichnung und eines bestimmten Studiensemesters
	 * @param string $lvinfo_set_kurzbz
	 * @param string $studiensemester_kurbz
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function load_lvinfo_set_kurzbz($lvinfo_set_kurzbz, $studiensemester_kurbz)
	{
		$sprache = new sprache();
		$lvinfo_set_bezeichnung = $sprache->getSprachQuery('lvinfo_set_bezeichnung');

		$studiensemester = $this->getGueltigesStudiensemester($studiensemester_kurbz);
		$qry = "SELECT
					*, $lvinfo_set_bezeichnung
				FROM
					addon.tbl_lvinfo_set
				WHERE
					lvinfo_set_kurzbz=".$this->db_add_param($lvinfo_set_kurzbz, FHC_STRING)."
				AND
					gueltigab_studiensemester_kurzbz=".$this->db_add_param($studiensemester, FHC_STRING)."
				ORDER BY sort";

		if($result = $this->db_query($qry))
		{
			if($row = $this->db_fetch_object($result))
			{
				$this->lvinfo_set_id = $row->lvinfo_set_id;
				$this->lvinfo_set_kurzbz = $row->lvinfo_set_kurzbz;
				$this->lvinfo_set_bezeichnung = $sprache->parseSprachResult('lvinfo_set_bezeichnung', $row);
				$this->sort = $row->sort;
				$this->lvinfo_set_typ = $row->lvinfo_set_typ;
				$this->gueltigab_studiensemester_kurzbz = $row->gueltigab_studiensemester_kurzbz;
				$this->oe_kurzbz = $row->oe_kurzbz;
				$this->insertamum = $row->insertamum;
				$this->insertvon = $row->insertvon;
				$this->updateamum = $row->updateamum;
				$this->updatevon = $row->updatevon;
				return true;
			}
			else
			{
				$this->errormsg = 'Fehler beim Laden des gueltigen Studiensemesters';
				return false;
			}
		}
		else
		{
			$this->errormsg = 'Es konnte kein gueltige Studiensemester geladen werden';
			return false;
		}
	}

	/**
	 * Laedt das LVInfo Set einer bestimmten kurzbezeichnung das am naehesten zum Studiensemesters passt
	 * @param string $lvinfo_set_kurzbz
	 * @param string $studiensemester_kurbz
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function load_lvinfo_set_kurzbz_nearest($lvinfo_set_kurzbz, $studiensemester_kurzbz)
	{
		$sprache = new sprache();
		$lvinfo_set_bezeichnung = $sprache->getSprachQuery('lvinfo_set_bezeichnung');

		$studiensemester = $this->getGueltigesStudiensemester($studiensemester_kurzbz);
		$qry = "SELECT
					tbl_lvinfo_set.*, $lvinfo_set_bezeichnung
				FROM
					addon.tbl_lvinfo_set
					JOIN public.tbl_studiensemester ON(tbl_studiensemester.studiensemester_kurzbz=gueltigab_studiensemester_kurzbz)
				WHERE
					lvinfo_set_kurzbz=".$this->db_add_param($lvinfo_set_kurzbz, FHC_STRING)."
				ORDER BY tbl_studiensemester.start LIMIT 1";

		if($result = $this->db_query($qry))
		{
			if($row = $this->db_fetch_object($result))
			{
				$this->lvinfo_set_id = $row->lvinfo_set_id;
				$this->lvinfo_set_kurzbz = $row->lvinfo_set_kurzbz;
				$this->lvinfo_set_bezeichnung = $sprache->parseSprachResult('lvinfo_set_bezeichnung', $row);
				$this->sort = $row->sort;
				$this->lvinfo_set_typ = $row->lvinfo_set_typ;
				$this->gueltigab_studiensemester_kurzbz = $row->gueltigab_studiensemester_kurzbz;
				$this->oe_kurzbz = $row->oe_kurzbz;
				$this->insertamum = $row->insertamum;
				$this->insertvon = $row->insertvon;
				$this->updateamum = $row->updateamum;
				$this->updatevon = $row->updatevon;
				return true;
			}
			else
			{
				$this->errormsg = 'Fehler beim Laden des gueltigen Studiensemesters';
				return false;
			}
		}
		else
		{
			$this->errormsg = 'Es konnte kein gueltige Studiensemester geladen werden';
			return false;
		}
	}

	/**
	 * Speichert den aktuellen Datensatz
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function save()
	{
		//Gueltigkeit der Variablen pruefen
		//if(!$this->validate())
		//	return false;

		if($this->new)
		{
			//Neuen Datensatz anlegen
			$qry = 'BEGIN;INSERT INTO addon.tbl_lvinfo (sprache, lehrveranstaltung_id, studiensemester_kurzbz,
				data, insertamum, insertvon, updateamum, updatevon) VALUES ('.
				$this->db_add_param($this->sprache).', '.
				$this->db_add_param($this->lehrveranstaltung_id, FHC_INTEGER).','.
				$this->db_add_param($this->studiensemester_kurzbz).', '.
				$this->db_add_param(json_encode($this->data)).', '.
				$this->db_add_param($this->insertamum).', '.
				$this->db_add_param($this->insertvon).', '.
				$this->db_add_param($this->updateamum).', '.
				$this->db_add_param($this->updatevon).');';
		}
		else
		{
			//bestehenden Datensatz akualisieren

			//Pruefen ob lvinfo_id gueltig ist
			if($this->lvinfo_id == '' || !is_numeric($this->lvinfo_id))
			{
				$this->errormsg = 'lvinfo_id ist ungültig';
				return false;
			}

			$qry = 'UPDATE addon.tbl_lvinfo SET '.
				'sprache='.$this->db_add_param($this->sprache).','.
				'lehrveranstaltung_id='.$this->db_add_param($this->lehrveranstaltung_id).','.
				'studiensemester_kurzbz='.$this->db_add_param($this->studiensemester_kurzbz).', '.
				'data='.$this->db_add_param(json_encode($this->data)).', '.
				'insertamum='.$this->db_add_param($this->insertamum).', '.
				'insertvon='.$this->db_add_param($this->insertvon).', '.
				'updateamum='.$this->db_add_param($this->updateamum).', '.
				'updatevon='.$this->db_add_param($this->updatevon).' '.
				'WHERE lvinfo_id = '.$this->db_add_param($this->lvinfo_id, FHC_INTEGER).";";
		}

		if($this->db_query($qry))
		{
			$this->lastqry=$qry;
			if($this->new)
			{
				$qry = "SELECT currval('addon.tbl_lvinfo_lvinfo_id_seq') as id";
				if($result = $this->db_query($qry))
				{
					if($row = $this->db_fetch_object($result))
					{
						$this->lvinfo_id = $row->id;
						$this->db_query('COMMIT;');
						return true;
					}
					else
					{
						$this->db_query('ROLLBACK;');
						$this->errormsg = 'Fehler beim Auslesen der Sequence';
						return false;
					}
				}
				else
				{
					$this->db_query('ROLLBACK;');
					$this->errormsg = 'Fehler beim Auslesen der Sequence';
					return false;
				}
			}
			else
			{
				return true;
			}
		}
		else
		{
			$this->errormsg = 'Fehler beim Speichern des Datensatzes';
			return false;
		}
	}

	/**
	 * Prueft die Daten des Sets vor dem Speichern
	 * @return true wenn ok, false im Fehlerfall
	 */
	private function validateSet()
	{
		if(!is_numeric($this->sort))
		{
			$this->errormsg = 'Sort muss eine gültige Zahl sein';
			return false;
		}
		if(mb_strlen($this->lvinfo_set_kurzbz)>16)
		{
			$this->errormsg = 'Kurzbezeichung darf nicht länger als 16 Zeichen sein';
			return false;
		}
		if($this->lvinfo_set_kurzbz=='')
		{
			$this->errormsg = 'Kurzbezeichnung darf nicht leer sein';
			return false;
		}
		if($this->lvinfo_set_typ=='')
		{
			$this->errormsg = 'Typ darf nicht leer sein';
			return false;
		}
		if(!preg_match('/^[A-Za-z0-9_]*$/',$this->lvinfo_set_kurzbz))
		{
			$this->errormsg = 'Kurzbezeichnung darf keine Sonderzeichen und Leerzeichen enthalten';
			return false;
		}
		return true;
	}

	/**
	 * Speichert ein LVInfoSet
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function saveSet()
	{
		if(!$this->validateSet())
			return false;

		if($this->new)
		{
			//Neuen Datensatz anlegen
			$qry = 'BEGIN;INSERT INTO addon.tbl_lvinfo_set (lvinfo_set_kurzbz, lvinfo_set_bezeichnung,
			 	einleitungstext, sort, lvinfo_set_typ,
				gueltigab_studiensemester_kurzbz, oe_kurzbz, insertamum, insertvon, updateamum, updatevon) VALUES ('.
				$this->db_add_param($this->lvinfo_set_kurzbz).', '.
				$this->db_add_param($this->lvinfo_set_bezeichnung, FHC_LANG_ARRAY).','.
				$this->db_add_param($this->einleitungstext, FHC_LANG_ARRAY).','.
				$this->db_add_param($this->sort).', '.
				$this->db_add_param($this->lvinfo_set_typ).', '.
				$this->db_add_param($this->gueltigab_studiensemester_kurzbz).', '.
				$this->db_add_param($this->oe_kurzbz).', '.
				$this->db_add_param($this->insertamum).', '.
				$this->db_add_param($this->insertvon).', '.
				$this->db_add_param($this->updateamum).', '.
				$this->db_add_param($this->updatevon).');';
		}
		else
		{
			//Pruefen ob lvinfo_id gueltig ist
			if($this->lvinfo_set_id == '' || !is_numeric($this->lvinfo_set_id))
			{
				$this->errormsg = 'lvinfo_set_id ist ungültig';
				return false;
			}

			$qry = 'UPDATE addon.tbl_lvinfo_set SET '.
				'lvinfo_set_kurzbz='.$this->db_add_param($this->lvinfo_set_kurzbz).','.
				'lvinfo_set_bezeichnung='.$this->db_add_param($this->lvinfo_set_bezeichnung, FHC_LANG_ARRAY).','.
				'einleitungstext='.$this->db_add_param($this->einleitungstext, FHC_LANG_ARRAY).','.
				'sort='.$this->db_add_param($this->sort, FHC_INTEGER).', '.
				'lvinfo_set_typ='.$this->db_add_param($this->lvinfo_set_typ).', '.
				'gueltigab_studiensemester_kurzbz='.$this->db_add_param($this->gueltigab_studiensemester_kurzbz).', '.
				'oe_kurzbz='.$this->db_add_param($this->oe_kurzbz).', '.
				'updateamum='.$this->db_add_param($this->updateamum).', '.
				'updatevon='.$this->db_add_param($this->updatevon).' '.
				'WHERE lvinfo_set_id = '.$this->db_add_param($this->lvinfo_set_id, FHC_INTEGER).";";
		}

		if($this->db_query($qry))
		{
			$this->lastqry=$qry;
			if($this->new)
			{
				$qry = "SELECT currval('addon.tbl_lvinfo_set_lvinfo_set_id_seq') as id";
				if($result = $this->db_query($qry))
				{
					if($row = $this->db_fetch_object($result))
					{
						$this->lvinfo_set_id = $row->id;
						$this->db_query('COMMIT;');
						return true;
					}
					else
					{
						$this->db_query('ROLLBACK;');
						$this->errormsg = 'Fehler beim Auslesen der Sequence';
						return false;
					}
				}
				else
				{
					$this->db_query('ROLLBACK;');
					$this->errormsg = 'Fehler beim Auslesen der Sequence';
					return false;
				}
			}
			else
			{
				return true;
			}
		}
		else
		{
			$this->errormsg = 'Fehler beim Speichern des Datensatzes';
			return false;
		}
	}

	/**
	 * Setzt den Status eines LVInfo Eintrages
	 * @param $lvinfo_id
	 * @param $status
	 * @param $user
	 * @return boolean true wenn ok, false im Fehlerfall
	 */
	public function setStatus($lvinfo_id,$status,$user)
	{
		$qry = "INSERT INTO addon.tbl_lvinfostatus_zuordnung(lvinfo_id,lvinfostatus_kurzbz,gesetztamum,uid,insertamum,insertvon) VALUES(".
				$this->db_add_param($lvinfo_id, FHC_INTEGER).','.
				$this->db_add_param($status).','.
				'now(),'.
				$this->db_add_param($user).','.
				'now(),'.
				$this->db_add_param($user).');';

		if($this->db_query($qry))
			return true;
		else
		{
			$this->errormsg = 'Fehler beim Speichern der Daten';
			return false;
		}
	}

	/**
	 * Laedt die aktuellste Statuszuordnung
	 * @param $lvinfo_id
	 * @return boolean true wenn ok, false im Fehlerfall
	 */
	public function getLastStatus($lvinfo_id)
	{
		$sprache = new sprache();
		$qry = "SELECT
					tbl_lvinfostatus_zuordnung.*,".
					$sprache->getSprachQuery('bezeichnung')."
				FROM
					addon.tbl_lvinfostatus_zuordnung
					JOIN addon.tbl_lvinfostatus USING(lvinfostatus_kurzbz)
				WHERE lvinfo_id=".$this->db_add_param($lvinfo_id)."
				ORDER BY gesetztamum DESC limit 1";

		if($result = $this->db_query($qry))
		{
			if($row = $this->db_fetch_object($result))
			{
				$this->lvinfostatus_zuordnung_id = $row->lvinfostatus_zuordnung_id;
				$this->lvinfostatus_kurzbz = $row->lvinfostatus_kurzbz;
				$this->uid = $row->uid;
				$this->gesetztamum = $row->gesetztamum;
				$this->lvinfo_id = $row->lvinfo_id;
				$this->insertamum = $row->insertamum;
				$this->updateamum = $row->updateamum;
				$this->bezeichnung = $sprache->parseSprachResult('bezeichnung',$row);

				return true;
			}
			else
			{
				$this->errormsg = 'Eintrag nicht gefunden';
				return false;
			}
		}
		else
		{
			$this->errormsg = 'Fehler beim Laden der Daten';
			return false;
		}
	}

	/**
	 * Loescht ein LVInfo Set
	 * @param integer $lvinfo_set_id
	 * @return boolean true wenn ok, false im Fehlerfall
	 */
	public function deleteSet($lvinfo_set_id)
	{
		$qry = "DELETE FROM addon.tbl_lvinfo_set WHERE lvinfo_set_id=".$this->db_add_param($lvinfo_set_id, FHC_INTEGER);

		if($this->db_query($qry))
			return true;
		else
		{
			$this->errormsg = 'Fehler beim Löschen der Daten';
			return false;
		}
	}
}
?>
