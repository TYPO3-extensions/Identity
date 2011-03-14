<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Thomas Maroschik <tmaroschik@dfau.de>
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
/**
 * Contains the class "t3lib_db" containing functions for building SQL queries
 * and mysql wrappers, thus providing a foundational API to all database
 * interaction.
 * This class is instantiated globally as $TYPO3_DB in TYPO3 scripts.
 *
 * $Id: class.t3lib_db.php 10121 2011-01-18 20:15:30Z ohader $
 *
 * @author	Thomas Maroschik <tmaroschik@dfau.de>
 */
class ux_t3lib_DB extends t3lib_DB {
	
	/**
	 * @var array
	 */
	protected $lastCreatedUUIDs = array();
	
	/**
	 * @var array
	 */
	protected $deletedUUIDs = array();

	/**
	 * Creates and executes an INSERT SQL-statement for $table from the array with field/value pairs $fields_values.
	 * Using this function specifically allows us to handle BLOB and CLOB fields depending on DB
	 * Usage count/core: 47
	 *
	 * @param	string		Table name
	 * @param	array		Field values as key=>value pairs. Values will be escaped internally. Typically you would fill an array like "$insertFields" with 'fieldname'=>'value' and pass it to this function as argument.
	 * @param	string/array		See fullQuoteArray()
	 * @return	pointer		MySQL result pointer / DBAL object
	 */
	function exec_INSERTquery($table, $fields_values, $no_quote_fields = FALSE) {
		$this->lastCreatedUUIDs = array();
		$res = parent::exec_INSERTquery($table, $fields_values, $no_quote_fields);
		if (!$this->sql_error() && count($this->lastCreatedUUIDs)) {
			$row = $this->exec_SELECTgetSingleRow('uid', $table, 'uuid = ' . $this->fullQuoteStr(current($this->lastCreatedUUIDs), $table));
			if ($row) {
				$uuidRegistry = t3lib_div::makeInstance('Tx_Uuid_Registry');
				$uuidRegistry->registerUUID(current($this->lastCreatedUUIDs), $table, $row['uid']);
				$this->lastCreatedUUIDs = array();
			}
		}
		return $res;
	}

	/**
	 * Creates and executes an INSERT SQL-statement for $table with multiple rows.
	 *
	 * @param	string		Table name
	 * @param	array		Field names
	 * @param	array		Table rows. Each row should be an array with field values mapping to $fields
	 * @param	string/array		See fullQuoteArray()
	 * @return	pointer		MySQL result pointer / DBAL object
	 */
	public function exec_INSERTmultipleRows($table, array $fields, array $rows, $no_quote_fields = FALSE) {
		$this->lastCreatedUUIDs = array();
		$res = parent::exec_INSERTmultipleRows($table, $fields, $rows, $no_quote_fields);
		if (!$this->sql_error() && count($this->lastCreatedUUIDs)) {
			$rows = $this->exec_SELECTgetSingleRow('uid,uuid', $table, 'uuid IN ' . implode(',', $this->fullQuoteArray($this->lastCreatedUUIDs, $table)));
			if (count($rows)) {
				$uuidRegistry = t3lib_div::makeInstance('Tx_Uuid_Registry');
				foreach ($rows as $row) {
					$uuidRegistry->registerUUID($row['uuid'], $table, $row['uid']);	
				}
				$this->lastCreatedUUIDs = array();
			}
		}
		return $res;
	}

	/**
	 * Creates and executes a DELETE SQL-statement for $table where $where-clause
	 * Usage count/core: 40
	 *
	 * @param	string		Database tablename
	 * @param	string		WHERE clause, eg. "uid=1". NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself!
	 * @return	pointer		MySQL result pointer / DBAL object
	 */
	function exec_DELETEquery($table, $where) {
		$this->deletedUUIDs = array();
		$res = parent::exec_DELETEquery($table, $where);
		if (!$this->sql_error() && count($this->deletedUUIDs)) {
			$uuidRegistry = t3lib_div::makeInstance('Tx_Uuid_Registry');
			foreach ($this->deletedUUIDs as $tuple) {
				$uuidRegistry->unregisterUUID($tuple['uuid'], $table, $tuple['uid']);
			}
			$this->deletedUUIDs = array();
		}
		return $res;
	}
		
	/**
	 * Creates an INSERT SQL-statement for $table from the array with field/value pairs $fields_values.
	 * Usage count/core: 4
	 *
	 * @param	string		See exec_INSERTquery()
	 * @param	array		See exec_INSERTquery()
	 * @param	string/array		See fullQuoteArray()
	 * @return	string		Full SQL query for INSERT (unless $fields_values does not contain any elements in which case it will be false)
	 */
	function INSERTquery($table, $fields_values, $no_quote_fields = FALSE) {
		t3lib_div::loadTCA($table);
		if (isset($GLOBALS['TCA'][$table]) && !isset($fields_values['uuid'])) {
			$uuid = Tx_Uuid_Utility_Algorithms::generateUUID();
			$fields_values['uuid'] = $uuid;
			$this->lastCreatedUUIDs[] = $uuid;
		}
		return parent::INSERTquery($table, $fields_values, $no_quote_fields);
	}

	/**
	 * Creates an INSERT SQL-statement for $table with multiple rows.
	 *
	 * @param	string		Table name
	 * @param	array		Field names
	 * @param	array		Table rows. Each row should be an array with field values mapping to $fields
	 * @param	string/array		See fullQuoteArray()
	 * @return	string		Full SQL query for INSERT (unless $rows does not contain any elements in which case it will be false)
	 */
	public function INSERTmultipleRows($table, array $fields, array $rows, $no_quote_fields = FALSE) {
		t3lib_div::loadTCA($table);
		if (isset($GLOBALS['TCA'][$table]) && !in_array('uuid', $fields) && count($rows)) {
			$fields[] = 'uuid';
			foreach ($rows as &$row) {
				$uuid = Tx_Uuid_Utility_Algorithms::generateUUID();;
				$row[] = $uuid;
				$this->lastCreatedUUIDs[] = $uuid;
			}
		}
		return parent::INSERTmultipleRows($table, $fields, $rows, $no_quote_fields);
	}

	/**
	 * Creates a DELETE SQL-statement for $table where $where-clause
	 * Usage count/core: 3
	 *
	 * @param	string		See exec_DELETEquery()
	 * @param	string		See exec_DELETEquery()
	 * @return	string		Full SQL query for DELETE
	 */
	function DELETEquery($table, $where) {
		t3lib_div::loadTCA($table);
		if (isset($GLOBALS['TCA'][$table])) {
			$rows = $this->exec_SELECTgetRows('uid,uuid', $table, $where);
			$this->deletedUUIDs = $rows;		
		}
		return parent::DELETEquery($table, $where);
	}

}

?>