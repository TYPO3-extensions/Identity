<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Thomas Maroschik <tmaroschik@dfau.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/


class Tx_Uuid_Registry implements t3lib_Singleton {
	
	/**
	 * @var t3lib_DB
	 */
	protected $db;

	/**
	 * @var	array
	 */
	protected $uuidMap = array();

	/**
	 * @var	array
	 */
	protected $tablenameUidMap = array();
	
	/**
	 * @var array
	 */
	protected $insertQueue = array();
	
	/**
	 * @var array
	 */
	protected $deleteQueue = array();
	
	/**
	 * Constructor method for the uuid registry
	 */
	public function __construct() {
		$this->db = $GLOBALS['TYPO3_DB'];
	}

	/**
	 * Returns a tablename for a given uuid.
	 *
	 * @param	string	UUID
	 * @return	mixed	The tablename of the entry or null.
	 * @throws	InvalidArgumentException	Throws an exception if the given uuid is not valid
	 */
	public function getTablename($uuid) {
		if (!isset($this->uuidMap[$uuid]['tablename'])) {
			$this->loadEntryByUUID($uuid);
		}
		return $this->uuidMap[$uuid]['tablename'];
	}
	
	/**
	 * Returns a uid for a given uuid.
	 *
	 * @param	string	UUID
	 * @return	int	The uid of the entry or null.
	 * @throws	InvalidArgumentException	Throws an exception if the given uuid is not valid
	 */
	public function getUid($uuid) {
		if (!isset($this->uuidMap[$uuid]['uid'])) {
			$this->loadEntryByUUID($uuid);
		}
		return $this->uuidMap[$uuid]['uid'];
	}
	
	/**
	 * Returns a uuid for a given tablename and uid
	 * @param string $tablename
	 * @param int $uid
	 * @return string The universally unique identifier.
	 */
	public function getUuid($tablename, $uid) {
		$hash = $tablename . '_' . $uid;
		if (!isset($this->tablenameUidMap[$hash])) {
			$this->loadEntryByTablenameAndUid($tablename, $uid);
		}
		return $this->tablenameUidMap[$hash];
	}
	
	/**
	 * Loads an entry for a given uuid
	 *
	 * @param	string	UUID
	 * @return	void
	 * @throws	InvalidArgumentException	Throws an exception if the given namespace is not valid
	 */
	protected function loadEntryByUUID($uuid) {
		Tx_Uuid_Utility_Algorithms::validateUUID($uuid);
		$row = $this->db->exec_SELECTgetSingleRow(
			'foreign_tablename, foreign_uid',
			'sys_uuid',
			'uuid = ' . $this->db->fullQuoteStr($uuid, 'sys_uuid')
		);
		if ($row) {
			$this->addToCache($uuid, $row['foreign_tablename'], $row['foreign_uid']);
		}
	}
	
	/**
	 * Loads an entry for a given uuid
	 *
	 * @param	string	UUID
	 * @return	void
	 * @throws	InvalidArgumentException	Throws an exception if the given tablename or uid is not valid
	 */
	protected function loadEntryByTablenameAndUid($tablename, $uid) {
		t3lib_div::loadTCA($tablename);
		if (!isset($GLOBALS['TCA'][$tablename])) {
			throw new InvalidArgumentException('The tablename "' . $tablename . '" is not defined in the TCA.', 1299082184);
		}
		if (!t3lib_div::testInt($uid)) {
			throw new InvalidArgumentException('The uid "' . $uid . '" is not an integer.', 1299082236);
		}
		$row = $this->db->exec_SELECTgetSingleRow(
			'uuid',
			'sys_uuid',
			'foreign_tablename = ' . $this->db->fullQuoteStr($tablename, 'sys_uuid') . 'AND' .
			'foreign_uid = ' . $this->db->fullQuoteStr($uid, 'sys_uuid')
		);
		if ($row) {
			$this->addToCache($row['uuid'], $tablename, $uid);
		}
	}

	/**
	 * Sets a persistent entry.
	 * Do not store binary data into the registry, it's not build to do that,
	 * instead use the proper way to store binary data: The filesystem.
	 *
	 * @param	string	Extension key for extensions starting with 'tx_' / 'user_' or 'core' for core registry entries.
	 * @param	string	The key of the entry to set.
	 * @param	mixed	The value to set. This can be any PHP data type; this class takes care of serialization if necessary.
	 * @return	void
	 * @throws	InvalidArgumentException	Throws an exception if the given namespace is not valid
	 */
	public function registerUUID($uuid, $tablename, $uid) {
		Tx_Uuid_Utility_Algorithms::validateUUID($uuid);
		t3lib_div::loadTCA($tablename);
		if (isset($GLOBALS['TCA'][$tablename]) && t3lib_div::testInt($uid)) {
			$this->insertQueue[$uuid] = array(
				 'uuid' => $uuid,
				 'foreign_tablename' => $tablename,
				 'foreign_uid' => $uid
			);
			if (!$this->db->sql_error()) {
				$this->addToCache($uuid, $tablename, $uid);
			}
		}
	}
	
	/**
	 * Adds a uuid, tablename, uid triple to the local object cache
	 * 
	 * @param string $uuid
	 * @param string $tablename
	 * @param int $uid
	 * @return void
	 */
	protected function addToCache($uuid, $tablename, $uid) {
		if ($uuid && $tablename && $uid) {
			$this->uuidMap[$uuid] = array(
					'tablename'	=> $tablename,
					'uid'		=> $uid,
				);
			$hash = $tablename . '_' . $uid; 
			$this->tablenameUidMap[$hash] = $uuid;
		}
	}
	
	/**
	 * Removes a uuid, tablename, uid triple from the local object cache
	 * 
	 * @param string $uuid
	 * @param string $tablename
	 * @param int $uid
	 * @return void
	 */
	protected function removeFromCache($uuid, $tablename, $uid) {
		if ($uuid && $tablename && $uid) {
			unset($this->uuidMap[$uuid]);
			$hash = $tablename . '_' . $uid; 
			unset($this->tablenameUidMap[$hash]);
		}
	}

	/**
	 * Unregisters an uuid triple
	 *
	 * @param	string	Namespace. extension key for extensions or 'core' for core registry entries
	 * @param	string	The key of the entry to unset.
	 * @return	void
	 * @throws	InvalidArgumentException	Throws an exception if the given namespace is not valid
	 */
	public function unregisterUUID($uuid, $tablename, $uid) {
		Tx_Uuid_Utility_Algorithms::validateUUID($uuid);
		t3lib_div::loadTCA($tablename);
		if (isset($GLOBALS['TCA'][$tablename]) && t3lib_div::testInt($uid)) {
			if (isset($this->insertQueue[$uuid])) {
				unset($this->insertQueue[$uuid]);
			}
			$this->deleteQueue[$uuid] = array(
				 'uuid' => $uuid,
				 'foreign_tablename' => $tablename,
				 'foreign_uid' => $uid
			);
			if (!$this->db->sql_error()) {
				$this->removeFromCache($uuid, $tablename, $uid);
			}
		}
	}
	
	/**
	 * Rebuilds the registry
	 */
	public function rebuild() {
		$this->insertMissingUUIDs();
		$this->removeNeedlessUUIDs();
		$this->registerUnregisteredUUIDs();
	}
	
	/**
	 * Walks through all tables and registers uuids of records with uuid not stored in the registry
	 */
	protected function registerUnregisteredUUIDs() {
		foreach ($GLOBALS['TCA'] as $tablename=>$configuration) {
			$rows = $this->db->exec_SELECTgetRows(
				$tablename . '.uuid, ' . $tablename . '.uid', 
				'sys_uuid RIGHT JOIN ' . $tablename . ' ON (sys_uuid.uuid = ' . $tablename . '.uuid)',
				'sys_uuid.uid IS NULL'
			);
			foreach ($rows as $row) {
				$this->registerUUID($row['uuid'], $tablename, $row['uid']);
			}
		}
	}
	
	/**
	 * Walks through all tables and unregisters all uuid mappings that have no target
	 */
	protected function removeNeedlessUUIDs() {
		foreach ($GLOBALS['TCA'] as $tablename=>$configuration) {
			$rows = $this->db->exec_SELECTgetRows(
				'sys_uuid.uuid, sys_uuid.foreign_uid',
				$tablename . ' RIGHT JOIN sys_uuid ON (sys_uuid.uuid = ' . $tablename . '.uuid AND sys_uuid.foreign_uid = ' . $tablename . '.uid)',
				'foreign_tablename LIKE ' . $tablename . ' AND ' . $tablename . '.uid IS NULL'
			);
			foreach ($rows as $row) {
				$this->unregisterUUID($row['uuid'], $tablename, $row['uid']);
			}
		}
	}
	
	/**
	 * Walks through all tables and inserts an uuid to a record that has any
	 */
	protected function insertMissingUUIDs() {
		foreach ($GLOBALS['TCA'] as $tablename=>$configuration) {
			if (isset($GLOBALS['TCA'][$tablename]['ctrl']['is_static']) && $GLOBALS['TCA'][$tablename]['ctrl']['is_static']) {
				$isStatic = true;
			} else {
				$isStatic = false;
			}
			$rows = $this->db->exec_SELECTgetRows('uid', $tablename, "uuid LIKE ''");
			if (count($rows)) {
				foreach ($rows as &$row) {
					if ($isStatic) {
						$uuid = Tx_Uuid_Utility_Algorithms::generateUUIDforStaticTable($tablename, $row['uid']);
					} else {
						$uuid = Tx_Uuid_Utility_Algorithms::generateUUID();
					}
					$this->db->exec_UPDATEquery($tablename, 'uid = ' .$row['uid'], array('uuid' => $uuid));
					$this->registerUUID($uuid, $tablename, $row['uid']);
				}
			}
		}
	}
	
	/**
	 * Persist the registry to the database
	 */
	public function commit() {
		if (count($this->insertQueue)) {
			$this->db->exec_INSERTmultipleRows(
				'sys_uuid',
				array_keys(current($this->insertQueue)),
				$this->insertQueue
			);
			$this->insertQueue = array();
		}
		if (count($this->deleteQueue)) {
			if (count($this->deleteQueue)) {
				foreach ($this->deleteQueue as $deletableEntries) {
					$this->db->exec_DELETEquery(
						'sys_uuid',
						'uuid = ' . $this->db->fullQuoteStr($deletableEntries['uuid'], 'sys_uuid')
						. ' OR ( foreign_tablename = ' . $this->db->fullQuoteStr($deletableEntries['tablename'], 'sys_uuid') . ' AND '
						. ' foreign_uid = ' . $this->db->fullQuoteStr($deletableEntries['uid'], 'sys_uuid') . ')'
					);
				}
			}
		}
	}

}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['EXT:uuid/Class/Registry.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['EXT:uuid/Class/Registry.php']);
}

?>