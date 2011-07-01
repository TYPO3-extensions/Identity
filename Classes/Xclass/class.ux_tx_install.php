<?php

class ux_tx_install extends tx_install {

	/**
	 * Reads the field definitions for the input SQL-file string
	 *
	 * @param	string		Should be a string read from an SQL-file made with 'mysqldump [database_name] -d'
	 * @return	array		Array with information about table.
	 */
	function getFieldDefinitions_fileContent($fileContent) {
		global $TCA;
		// Load TCA only if necessary
		if (!is_array($TCA)) {
			$this->includeTCA();
		}
		$tableDefinitions = parent::getFieldDefinitions_fileContent($fileContent);
		$fieldDefinitionsUtility = t3lib_div::makeInstance('Tx_Identity_Utility_FieldDefinitions');
		$tableDefinitions = $fieldDefinitionsUtility->insertIdentityColumn($tableDefinitions);
		return $tableDefinitions;
	}
}
?>