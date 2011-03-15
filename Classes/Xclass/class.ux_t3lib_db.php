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
 * Just a fallback, if identity extension is used in older typo3 versions
 *
 *
 * @author	Thomas Maroschik <tmaroschik@dfau.de>
 */
class ux_t3lib_DB extends t3lib_DB {

	/**
	 * @var t3lib_DB_preProcessQueryHook[]
	 */
	protected $preProcessHookObjects = array();

	/**
	 * @var t3lib_DB_postProcessQueryHook[]
	 */
	protected $postProcessHookObjects = array();

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
		$res = parent::exec_INSERTquery($table, $fields_values, $no_quote_fields);
		foreach ($this->postProcessHookObjects as $hookObject) {
			$hookObject->exec_INSERTquery_postProcessAction($table, $fields_values, $no_quote_fields, $this);
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
		$res = parent::exec_INSERTmultipleRows($table, $fields, $rows, $no_quote_fields);
		foreach ($this->postProcessHookObjects as $hookObject) {
			$hookObject->exec_INSERTmultipleRows_postProcessAction($table, $fields, $rows, $no_quote_fields, $this);
		}
		return $res;
	}

	/**
	 * Creates and executes an UPDATE SQL-statement for $table where $where-clause (typ. 'uid=...') from the array with field/value pairs $fields_values.
	 * Using this function specifically allow us to handle BLOB and CLOB fields depending on DB
	 * Usage count/core: 50
	 *
	 * @param	string		Database tablename
	 * @param	string		WHERE clause, eg. "uid=1". NOTICE: You must escape values in this argument with $this->fullQuoteStr() yourself!
	 * @param	array		Field values as key=>value pairs. Values will be escaped internally. Typically you would fill an array like "$updateFields" with 'fieldname'=>'value' and pass it to this function as argument.
	 * @param	string/array		See fullQuoteArray()
	 * @return	pointer		MySQL result pointer / DBAL object
	 */
	function exec_UPDATEquery($table, $where, $fields_values, $no_quote_fields = FALSE) {
		$res = parent::exec_UPDATEquery($table, $where, $fields_values, $no_quote_fields);
		foreach ($this->postProcessHookObjects as $hookObject) {
			$hookObject->exec_UPDATEquery_postProcessAction($table, $where, $fields_values, $no_quote_fields, $this);
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
		$res = parent::exec_DELETEquery($table, $where);
		foreach ($this->postProcessHookObjects as $hookObject) {
			$hookObject->exec_DELETEquery_postProcessAction($table, $where, $this);
		}
		return $res;
	}

	/**
	 * Truncates a table.
	 *
	 * @param	string		Database tablename
	 * @return	mixed		Result from handler
	 */
	public function exec_TRUNCATEquery($table) {
		$res = parent::exec_TRUNCATEquery($table);
		foreach ($this->postProcessHookObjects as $hookObject) {
			$hookObject->exec_TRUNCATEquery_postProcessAction($table, $this);
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
	public function INSERTquery($table, $fields_values, $no_quote_fields = FALSE) {
		if (is_array($fields_values) && count($fields_values)) {
			foreach ($this->preProcessHookObjects as $hookObject) {
				$hookObject->INSERTquery_preProcessAction($table, $fields_values, $no_quote_fields, $this);
			}
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
			// Table and fieldnames should be "SQL-injection-safe" when supplied to this
			// function (contrary to values in the arrays which may be insecure).
		if (count($rows)) {
			foreach ($this->preProcessHookObjects as $hookObject) {
				$hookObject->INSERTmultipleRows_preProcessAction($table, $fields, $rows, $no_quote_fields, $this);
			}
		}
		return parent::INSERTmultipleRows($table, $fields, $rows, $no_quote_fields);
	}

	/**
	 * Creates an UPDATE SQL-statement for $table where $where-clause (typ. 'uid=...') from the array with field/value pairs $fields_values.
	 * Usage count/core: 6
	 *
	 * @param	string		See exec_UPDATEquery()
	 * @param	string		See exec_UPDATEquery()
	 * @param	array		See exec_UPDATEquery()
	 * @param	array		See fullQuoteArray()
	 * @return	string		Full SQL query for UPDATE
	 */
	public function UPDATEquery($table, $where, $fields_values, $no_quote_fields = FALSE) {
		if (is_string($where)) {
			foreach ($this->preProcessHookObjects as $hookObject) {
				$hookObject->UPDATEquery_preProcessAction($table, $where, $fields_values, $no_quote_fields, $this);
			}
		}
		return parent::UPDATEquery($table, $where, $fields_values, $no_quote_fields);
	}

	/**
	 * Creates a DELETE SQL-statement for $table where $where-clause
	 * Usage count/core: 3
	 *
	 * @param	string		See exec_DELETEquery()
	 * @param	string		See exec_DELETEquery()
	 * @return	string		Full SQL query for DELETE
	 */
	public function DELETEquery($table, $where) {
		if (is_string($where)) {
			foreach ($this->preProcessHookObjects as $hookObject) {
				$hookObject->DELETEquery_preProcessAction($table, $where, $this);
			}
		}
		return parent::DELETEquery($table, $where);
	}

	/**
	 * Creates a TRUNCATE TABLE SQL-statement
	 *
	 * @param	string		See exec_TRUNCATEquery()
	 * @return	string		Full SQL query for TRUNCATE TABLE
	 */
	public function TRUNCATEquery($table) {
		foreach ($this->preProcessHookObjects as $hookObject) {
			$hookObject->TRUNCATEquery_preProcessAction($table, $this);
		}
		return parent::TRUNCATEquery($table);
	}

	/**
	 * Connects to database for TYPO3 sites:
	 *
	 * @param string $host
	 * @param string $user
	 * @param string $password
	 * @param string $db
	 * @return	void
	 */
	function connectDB($host = TYPO3_db_host, $user = TYPO3_db_username, $password = TYPO3_db_password, $db = TYPO3_db) {
		parent::connectDB($host, $user, $password);
			// Prepare user defined objects (if any) for hooks which extend query methods
		$this->preProcessHookObjects = array();
		$this->postProcessHookObjects = array();
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_db.php']['queryProcessors'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_db.php']['queryProcessors'] as $classRef) {
				$hookObject = t3lib_div::getUserObj($classRef);

				if (!($hookObject instanceof t3lib_DB_preProcessQueryHook || $hookObject instanceof t3lib_DB_postProcessQueryHook)) {
					throw new UnexpectedValueException('$hookObject must either implement interface t3lib_DB_preProcessQueryHook or interface t3lib_DB_postProcessQueryHook', 1299158548);
				}
				if ($hookObject instanceof t3lib_DB_preProcessQueryHook) {
					$this->preProcessHookObjects[] = $hookObject;
				}
				if ($hookObject instanceof t3lib_DB_postProcessQueryHook) {
					$this->postProcessHookObjects[] = $hookObject;
				}
			}
		}
	}
}

?>