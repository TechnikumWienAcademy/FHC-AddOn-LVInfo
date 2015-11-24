<?php
/* Copyright (C) 2006 Technikum-Wien
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
 * Authors: Manfred Kindl <manfred.kindl@technikum-wien.at>.
 */

require_once(dirname(__FILE__).'/../../../include/basis_db.class.php');

class lvinfo extends basis_db
{
	public $new;
	public $result = array();

	//Tabellenspalten
	public $lvinfo_set_id;						// integer
	public $lvinfo_set_kurzbz;					// varchar(16)
	public $lvinfo_set_bezeichnung;				// varchar(64)
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
			$this->data						= json_decode($row->data);
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
	 * @param integer $studiensemester_kurzbz
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function loadLvinfo($lehrveranstaltung_id,$studiensemester_kurzbz)
	{
		if($lehrveranstaltung_id == '' || !is_numeric($lehrveranstaltung_id))
		{
			$this->errormsg = 'Lehrveranstaltung_id ist ungültig';
			return false;
		}
		$qry = "SELECT * FROM addon.tbl_lvinfo
				WHERE lehrveranstaltung_id=".$this->db_add_param($lehrveranstaltung_id, FHC_INTEGER)." AND studiensemester_kurzbz=".$this->db_add_param($studiensemester_kurzbz, FHC_STRING).";";
		
		if(!$this->db_query($qry))
		{
			$this->errormsg = 'Fehler beim Laden des Datensatzes';
			return false;
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
			$this->errormsg = 'loadLvinfo: Es ist kein Datensatz vorhanden';
			return false;
		}
	}
	
	/**
	 * Laedt ein LVInfo Set mit Gueltigkeit fuer das Uebergebene Studiensemester
	 * @param integer $lvinfo_id
	 * @return true wenn ok, false im Fehlerfall
	 */
	/*public function load_lvinfo_set($studiensemester_kurbz)
	{
		if($lvinfo_set_id == '' || !is_numeric($lvinfo_set_id))
		{
			$this->errormsg = 'lvinfo_set_id ist ungültig';
			return false;
		}
		$sprache = new sprache();
		$lvinfo_set_bezeichnung = $sprache->getSprachQuery('lvinfo_set_bezeichnung');
		
		$qry = "SELECT *,".$lvinfo_set_bezeichnung." FROM addon.tbl_lvinfo_set
		WHERE lvinfo_set_id = ".$this->db_add_param($lvinfo_set_id, FHC_INTEGER).";";
		
		if($result = $this->db_query($qry))
		{
			while($row = $this->db_fetch_object($result))
			{
				$set = new lvinfo();
				
				$set->lvinfo_set_id = $row->lvinfo_set_id;
				$set->lvinfo_set_kurzbz = $row->lvinfo_set_kurzbz;
				$set->lvinfo_set_bezeichnung = $sprache->parseSprachResult('lvinfo_set_bezeichnung', $row);
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
			$this->errormsg="Fehler bei der Abfrage aufgetreten";
			return false;
		}	
		return true;
	}*/
	
	/**
	 * Laedt das gültige Studiensemester aus dem LV-Info-Set für das uebergebene Studiensemester
	 * @param string $studiensemester_kurbz
	 * @return $gueltigab_studiensemester_kurzbz
	 */
	public function getGueltigesStudiensemester($studiensemester_kurbz)
	{
		$sprache = new sprache();
		$lvinfo_set_bezeichnung = $sprache->getSprachQuery('lvinfo_set_bezeichnung');
	
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
	 * Laedt das LVInfo Set mit Gueltigkeit fuer das Uebergebene Studiensemester
	 * @param string $studiensemester_kurbz
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function load_lvinfo_set($studiensemester_kurbz)
	{
		$sprache = new sprache();
		$lvinfo_set_bezeichnung = $sprache->getSprachQuery('lvinfo_set_bezeichnung');
		
		$studiensemester = $this->getGueltigesStudiensemester($studiensemester_kurbz);
		$qry = "SELECT 
					*, $lvinfo_set_bezeichnung
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
				$set->lvinfo_set_bezeichnung = $sprache->parseSprachResult('lvinfo_set_bezeichnung', $row);
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
	 * Loescht einen Datensatz
	 * @param $lvinfo_id ID des Datensatzes der geloescht werden soll
	 * @return true wenn ok, false im Fehlerfall
	 */
	/*public function delete($lvinfo_id)
	{
		if(!is_numeric($lvinfo_id))
		{
			$this->errormsg = 'Lvinfo_id muss eine gueltige Zahl sein';
			return false;
		}

		$qry = "DELETE FROM campus.tbl_lvinfo WHERE lehrveranstaltung_id=".$this->db_add_param($lvinfo_id, FHC_INTEGER);

		if($this->db_query($qry))
		{
			$this->lastqry = $qry;
			return true;
		}
		else
		{
			$this->errormsg = 'Fehler beim Löschen der Daten';
			return false;
		}
	}*/
	
	/**
	 * Prueft die Gueltigkeit der Variablen
	 * @return true wenn ok, false im Fehlerfall
	 */
	/*protected function validate()
	{
		//Laenge Pruefen
		if(mb_strlen($this->sprache)>16)
		{
			$this->errormsg = 'Sprache darf nicht laenger als 16 Zeichen sein';
			return false;
		}
		if(!is_numeric($this->lehrveranstaltung_id))
		{
			$this->errormsg = 'Lehrveranstaltung_id muss eine gueltige Zahl sein';
			return false;
		}
		return true;
	}*/
	
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
			$qry = 'INSERT INTO addon.tbl_lvinfo (sprache, lehrveranstaltung_id, studiensemester_kurzbz, data, insertamum, insertvon, updateamum, updatevon) VALUES ('.
				
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
			/*if($this->lehrveranstaltung_id == '' || !is_numeric($this->lehrveranstaltung_id))
			{
				$this->errormsg = 'lehrveranstaltung_id '.$this->lehrveranstaltung_id.' ungültig';
				return false;
			}*/

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
		echo $qry;
		if($this->db_query($qry))
		{
			$this->lastqry=$qry;
			return true;
		}
		else
		{
			$this->errormsg = 'Fehler beim Speichern des Datensatzes';
			return false;
		}
	}

	/**
	 * Prueft ob bereits eine LV-Info angelegt ist
	 *
	 * @param $lehrveranstaltung_id
	 * @param $sprache
	 * @return boolean
	 */
	public function exists($lehrveranstaltung_id, $sprache=null)
	{
		if(!is_numeric($lehrveranstaltung_id))
		{
			$this->errormsg = 'Lehrveranstaltung_id muss eine gueltige Zahl sein';
			return false;
		}

		$qry = "SELECT count(*) as anzahl FROM campus.tbl_lvinfo WHERE lehrveranstaltung_id=".$this->db_add_param($lehrveranstaltung_id, FHC_INTEGER);
		
		if(!is_null($sprache))
			$qry .= " AND sprache=".$this->db_add_param($sprache);
        
        $qry.=';';

		if($this->db_query($qry))
		{
			if($row = $this->db_fetch_object())
			{
				if($row->anzahl>0)
					return true;
				else
					return false;
			}
			else
			{
				$this->errormsg ='Fehler bei einer Abfrage';
				return false;
			}
		}
		else
		{
			$this->errormsg = 'Fehler bei einer Abfrage';
			return false;
		}
	}
	
	/**
	 * Kopiert eine LVInfo von einer LV in eine andere
	 *
	 * @param $source ID der Lehrveranstaltung von der wegkopiert wird
	 * @param $target ID der Lehrveranstaltung zu der die LV-Info kopiert werden soll
	 * @return true wenn ok, false wenn Fehler
	 */
	public function copy($source, $target)
	{
		if(!is_numeric($source) || $source=='')
		{
			$this->errormsg ='source muss eine gueltige Zahl sein';
			return false;
		}
		
		if(!is_numeric($target) || $target=='')
		{
			$this->errormsg ='target muss eine gueltige Zahl sein';
			return false;
		}
		
		$qry = "
		INSERT INTO campus.tbl_lvinfo(lehrveranstaltung_id, sprache, titel, lehrziele,
			lehrinhalte, methodik, voraussetzungen, unterlagen, pruefungsordnung, anmerkung, kurzbeschreibung, anwesenheit, genehmigt,
			aktiv, updateamum, updatevon, insertamum, insertvon) 
		SELECT $target, sprache, titel, lehrziele,
		lehrinhalte, methodik, voraussetzungen, unterlagen, pruefungsordnung, anmerkung, kurzbeschreibung, anwesenheit, genehmigt,
		aktiv, updateamum, updatevon, insertamum, insertvon FROM campus.tbl_lvinfo WHERE lehrveranstaltung_id=".$this->db_add_param($source).';';
		
		if($this->db_query($qry))
		{
			return true;
		}
		else 
		{
			$this->errormsg = 'Fehler beim Kopieren der LVInfo';
			return false;
		}
		
	}
}
?>