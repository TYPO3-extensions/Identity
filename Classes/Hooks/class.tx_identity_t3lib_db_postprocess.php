<?php

class tx_identity_t3lib_db_postprocess implements t3lib_DB_preProcessQueryHook {

	/**
	 * @var Tx_Identity_Map
	 */
	protected $identityMap;

	/**
	 * Constructor method for the t3lib_DB posprocess hook
	 */
	public function __construct() {
		$this->identityMap = t3lib_div::makeInstance('Tx_Identity_Map');
	}

	/**
	 * Pre-processor for the INSERTquery method.
	 *
	 * @param string $table Database table name
	 * @param array $fieldsValues Field values as key => value pairs
	 * @param string/array $noQuoteFields List/array of keys NOT to quote
	 * @param t3lib_DB $parentObject
	 * @return void
	 */
	public function INSERTquery_preProcessAction(&$table, array &$fieldsValues, &$noQuoteFields, t3lib_DB $parentObject) {
		$identityField = $this->identityMap->getIdentifierFieldForResourceLocation($table);
		if ($identityField) {
			$fieldsValues[$identityField] = $this->identityMap->getIdentifierForNewResourceLocation($table);
		}
	}

	/**
	 * Pre-processor for the INSERTmultipleRows method.
	 *
	 * @param string $table Database table name
	 * @param array $fields Field names
	 * @param array $rows Table rows
	 * @param string/array $noQuoteFields List/array of keys NOT to quote
	 * @param t3lib_DB $parentObject
	 * @return void
	 */
	public function INSERTmultipleRows_preProcessAction(&$table, array &$fields, array &$rows, &$noQuoteFields, t3lib_DB $parentObject) {
		$identityField = $this->identityMap->getIdentifierFieldForResourceLocation($table);
		if ($identityField) {
			foreach ($rows as &$row) {
				$row[$identityField] = $this->identityMap->getIdentifierForNewResourceLocation($table);
			}
		}
	}

	/**
	 * Pre-processor for the UPDATEquery method.
	 *
	 * @param string $table Database table name
	 * @param string $where WHERE clause
	 * @param array $fieldsValues Field values as key => value pairs
	 * @param string/array $noQuoteFields List/array of keys NOT to quote
	 * @param t3lib_DB $parentObject
	 * @return void
	 */
	public function UPDATEquery_preProcessAction(&$table, &$where, array &$fieldsValues, &$noQuoteFields, t3lib_DB $parentObject) {
		// Do nothing
	}

	/**
	 * Pre-processor for the DELETEquery method.
	 *
	 * @param string $table Database table name
	 * @param string $where WHERE clause
	 * @param t3lib_DB $parentObject
	 * @return void
	 */
	public function DELETEquery_preProcessAction(&$table, &$where, t3lib_DB $parentObject) {
		// Do nothing
	}

	/**
	 * Pre-processor for the TRUNCATEquery method.
	 *
	 * @param string $table Database table name
	 * @param t3lib_DB $parentObject
	 * @return void
	 */
	public function TRUNCATEquery_preProcessAction(&$table, t3lib_DB $parentObject) {
		// Do nothing
	}
}
?>