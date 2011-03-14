<?php

class Tx_Identity_Utility_FieldDefinitions {

	/**
	 * Check if a table definition contains an uid and a pid, and insert a uuid column
	 *
	 * @param array $tableDefinitions
	 * @return array
	 */
	public function insertUuidColumn($tableDefinitions) {
		foreach ($tableDefinitions as $table => &$definition) {
			if (isset($GLOBALS['TCA'][$table])) {
				$definition['fields']['uuid'] = 'varchar(36) NOT NULL default \'\'';
			}
		}
		return $tableDefinitions;
	}
}
?>