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

class Tx_Identity_Provider_AbstractUuid implements Tx_Identity_ProviderInterface {

	/**
	 * @var string
	 */
	protected $providerKey;

	/**
	 * @var string
	 */
	protected $identityTable = 'sys_identity';

	/**
	 * @var array
	 */
	protected $configuration = array();

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
	 * Sets the provider key
	 * @param string $providerKey
	 */
	public function __construct($providerKey) {
		$this->providerKey = $providerKey;
	}

	/**
	 * Injector method for the providers configuration
	 *
	 * @param array $configuration
	 */
	public function injectConfiguration(array $configuration) {
		$this->configuration = $configuration;
	}

	/**
	 * Injector method for a t3lib_DB
	 *
	 * @param t3lib_DB $db
	 */
	public function injectDb(t3lib_DB $db) {
		$this->db = $db;
	}

	/**
	 * Checks the given UUID. If it does not have a valid format an
	 * exception is thrown.
	 *
	 * @param	string	UUID.
	 * @return	bool
	 * @throws	InvalidArgumentException	Throws an exception if the given uuid is not valid
	 */
	public function validateIdentifier($identifier) {
		if (!strlen($identifier)) {
			throw new InvalidArgumentException('Empty UUID given.', 1299013185);
			return false;
		}
		if (function_exists('uuid_is_valid') && !uuid_is_valid($identifier)) {
			throw new InvalidArgumentException('Given UUID does not match the UUID pattern.', 1299013329);
			return false;
		}
		if (strlen($identifier) !== 36) {
			throw new InvalidArgumentException('Lenghth of UUID has to be 36 characters.', 1299013335);
			return false;
		}
		$pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
		if (!preg_match($pattern, $identifier)) {
			throw new InvalidArgumentException('Given UUID does not match the UUID pattern.', 1299013339);
			return false;
		}
		return true;
	}

	/**
	 * Returns a resource location for an identifier
	 *
	 * @param mixed $identifier
	 * @return array [tablename, uid] the resource location
	 */
	public function getResourceLocationForIdentifier($uuid) {
		if (!isset($this->uuidMap[$uuid])) {
			$this->loadEntryByUUID($uuid);
		}
		return $this->uuidMap[$uuid];
	}

	/**
	 * Returns a unique identifier for a resource location
	 *
	 * @param string $tablename
	 * @param int $uid
	 * @return mixed the unique identifier
	 */
	public function getIdentifierForResourceLocation($tablename, $uid) {
		$hash = $tablename . '_' . $uid;
		if (!isset($this->tablenameUidMap[$hash])) {
			$this->loadEntryByTablenameAndUid($tablename, $uid);
		}
		return $this->tablenameUidMap[$hash];
	}

	/**
	 * Loads an entry for a given uuid
	 *
	 * @param	string	identifier
	 * @return	void
	 * @throws	InvalidArgumentException	Throws an exception if the given namespace is not valid
	 */
	protected function loadEntryByUUID($uuid) {
		$identityField = $this->configuration[Tx_Identity_Configuration_IdentityProviderInterface::IDENTITY_FIELD];
		$this->validateIdentifier($uuid);
		$row = $this->db->exec_SELECTgetSingleRow(
			'foreign_tablename, foreign_uid',
			$this->identityTable,
				$identityField . ' = ' . $this->db->fullQuoteStr($uuid, $this->identityTable)
		);
		if ($row) {
			$this->addToCache($uuid, $row['foreign_tablename'], $row['foreign_uid']);
		}
	}

	/**
	 * Loads an entry for a given uuid
	 *
	 * @param	string	identifier
	 * @return	void
	 * @throws	InvalidArgumentException	Throws an exception if the given tablename or uid is not valid
	 */
	protected function loadEntryByTablenameAndUid($tablename, $uid) {
		$identityField = $this->configuration[Tx_Identity_Configuration_IdentityProviderInterface::IDENTITY_FIELD];
		t3lib_div::loadTCA($tablename);
		if (!isset($GLOBALS['TCA'][$tablename])) {
			throw new InvalidArgumentException('The tablename "' . $tablename . '" is not defined in the TCA.', 1299082184);
		}
		if (!t3lib_div::testInt($uid)) {
			throw new InvalidArgumentException('The uid "' . $uid . '" is not an integer.', 1299082236);
		}
		$row = $this->db->exec_SELECTgetSingleRow(
			$identityField,
			$this->identityTable,
				'foreign_tablename = ' . $this->db->fullQuoteStr($tablename, $this->identityTable) . 'AND' .
				'foreign_uid = ' . $this->db->fullQuoteStr($uid, $this->identityTable)
		);
		if (!$row) {
			// Fallback if not in identity table
			$row = $this->db->exec_SELECTgetSingleRow(
				$identityField,
				$tablename,
					'uid = ' . $this->db->fullQuoteStr($uid, $tablename)
			);
			if ($row) {
				$this->insertQueue[$row[$identityField]] = array(
					$identityField => $row[$identityField],
					'foreign_tablename' => $tablename,
					'foreign_uid' => $uid
				);
			}
		}
		if ($row) {
			$this->addToCache($row[$identityField], $tablename, $uid);
		}
	}

	/**
	 * Requests a new identifier for a resource location
	 *
	 * @param string $tablename
	 * @return string
	 */
	public function getIdentifierForNewResourceLocation($tablename) {
		$identityField = $this->configuration[Tx_Identity_Configuration_IdentityProviderInterface::IDENTITY_FIELD];
		$uuid = Tx_Identity_Utility_Algorithms::generateUUID();
		t3lib_div::loadTCA($tablename);
		if (isset($GLOBALS['TCA'][$tablename])) {
			$this->insertQueue[$uuid] = array(
				$identityField => $uuid,
				'foreign_tablename' => $tablename,
			);
			return $uuid;
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
				'tablename' => $tablename,
				'uid' => $uid,
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
	 * @param	string	identifier
	 * @param	string	The key of the entry to unset.
	 * @return	void
	 * @throws	InvalidArgumentException	Throws an exception if the given namespace is not valid
	 */
	protected function unregisterUUID($uuid, $tablename, $uid) {
		$identityField = $this->configuration[Tx_Identity_Configuration_IdentityProviderInterface::IDENTITY_FIELD];
		$this->validateIdentifier($uuid);
		t3lib_div::loadTCA($tablename);
		if (isset($GLOBALS['TCA'][$tablename]) && t3lib_div::testInt($uid)) {
			if (isset($this->insertQueue[$uuid])) {
				unset($this->insertQueue[$uuid]);
			}
			$this->deleteQueue[$uuid] = array(
				$identityField => $uuid,
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
		$identityField = $this->configuration[Tx_Identity_Configuration_IdentityProviderInterface::IDENTITY_FIELD];
		foreach ($GLOBALS['TCA'] as $tablename => $configuration) {
			$rows = $this->db->exec_SELECTgetRows(
				$tablename . '.' . $identityField . ', ' . $tablename . '.uid',
					$this->identityTable . ' RIGHT JOIN ' . $tablename . ' ON ' . $this->identityTable . '.' . $identityField . ' = ' . $tablename . '.' . $identityField,
					$this->identityTable . '.uid IS NULL'
			);
			foreach ($rows as $row) {
				$this->insertQueue[$row[$identityField]] = array(
					$identityField => $row[$identityField],
					'foreign_tablename' => $tablename,
					'foreign_uid' => $row['uid']
				);
				$this->addToCache($row[$identityField], $tablename, $row['uid']);
			}
		}
	}

	/**
	 * Walks through all tables and unregisters all uuid mappings that have no target
	 */
	protected function removeNeedlessUUIDs() {
		$identityField = $this->configuration[Tx_Identity_Configuration_IdentityProviderInterface::IDENTITY_FIELD];
		foreach ($GLOBALS['TCA'] as $tablename => $configuration) {
			$rows = $this->db->exec_SELECTgetRows(
				$this->identityTable . '.' . $identityField . ', ' . $this->identityTable . '.foreign_uid',
					$tablename . ' RIGHT JOIN ' . $this->identityTable . ' ON ' .
					$this->identityTable . '.' . $identityField .
					' = ' .
					$tablename . '.' . $identityField . ' AND ' . $this->identityTable . '.foreign_uid = ' . $tablename . '.uid',
					'foreign_tablename LIKE ' . $tablename . ' AND ' . $tablename . '.uid IS NULL'
			);
			foreach ($rows as $row) {
				$this->unregisterUUID($row[$identityField], $tablename, $row['uid']);
			}
		}
	}

	/**
	 * Walks through all tables and inserts an uuid to a record that has any
	 */
	protected function insertMissingUUIDs() {
		$identityField = $this->configuration[Tx_Identity_Configuration_IdentityProviderInterface::IDENTITY_FIELD];
		foreach ($GLOBALS['TCA'] as $tablename => $configuration) {
			$rows = $this->db->exec_SELECTgetRows('uid', $tablename, $identityField . " LIKE ''");
			if (count($rows)) {
				foreach ($rows as &$row) {
					$uuid = Tx_Identity_Utility_Algorithms::generateUUID();
					$this->db->exec_UPDATEquery($tablename, 'uid = ' . $row['uid'], array($identityField => $uuid));
					$this->insertQueue[$uuid] = array(
						$identityField => $uuid,
						'foreign_tablename' => $tablename,
						'foreign_uid' => $row['uid']
					);
					$this->addToCache($uuid, $tablename, $row['uid']);
				}
			}
		}
	}

	/**
	 * Check the insert queue for incomplete record locations (lastInsertId for example)
	 */
	protected function completeInsertQueue() {
		$identityField = $this->configuration[Tx_Identity_Configuration_IdentityProviderInterface::IDENTITY_FIELD];
		foreach ($this->insertQueue as $uuid => $recordLocation) {
			$newRecordLocation = array();
			if (isset($recordLocation[$identityField])) {
				$newRecordLocation[$identityField] = $recordLocation[$identityField];
			}
			if (isset($recordLocation['foreign_tablename'])) {
				$newRecordLocation['foreign_tablename'] = $recordLocation['foreign_tablename'];
			}
			if (!isset($recordLocation['foreign_uid'])) {
				$foreignUidRecord = $this->db->exec_SELECTgetSingleRow('uid', $recordLocation['foreign_tablename'], $identityField . ' LIKE \'' . $recordLocation[$identityField] . '\'');
				if (isset($foreignUidRecord['uid'])) {
					$newRecordLocation['foreign_uid'] = $foreignUidRecord['uid'];
				}
			} else {
				$newRecordLocation['foreign_uid'] = $recordLocation['foreign_uid'];
			}
			if (isset($newRecordLocation[$identityField]) && isset($newRecordLocation['foreign_tablename']) && isset($newRecordLocation['foreign_uid'])) {
				$this->insertQueue[$uuid] = $newRecordLocation;
			} else {
				unset($this->insertQueue[$uuid]);
			}
		}
	}

	/**
	 * Persist the registry to the database
	 */
	public function commit() {
		$identityField = $this->configuration[Tx_Identity_Configuration_IdentityProviderInterface::IDENTITY_FIELD];
		$this->completeInsertQueue();
		if (count($this->insertQueue)) {
			$this->db->exec_INSERTmultipleRows(
				$this->identityTable,
				array_keys(current($this->insertQueue)),
				$this->insertQueue
			);
			$this->insertQueue = array();
		}
		if (count($this->deleteQueue)) {
			if (count($this->deleteQueue)) {
				foreach ($this->deleteQueue as $deletableEntries) {
					$this->db->exec_DELETEquery(
						$this->identityTable,
							$identityField . ' = ' . $this->db->fullQuoteStr($deletableEntries[$identityField], $this->identityTable)
							. ' OR ( foreign_tablename = ' . $this->db->fullQuoteStr($deletableEntries['tablename'], $this->identityTable) . ' AND '
							. ' foreign_uid = ' . $this->db->fullQuoteStr($deletableEntries['uid'], $this->identityTable) . ')'
					);
				}
			}
		}
	}


}

?>