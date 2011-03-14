<?php

class ux_t3lib_install extends t3lib_install {
	
	/**
	 * Reads the field definitions for the input SQL-file string
	 *
	 * @param	string		Should be a string read from an SQL-file made with 'mysqldump [database_name] -d'
	 * @return	array		Array with information about table.
	 */
	function getFieldDefinitions_fileContent($fileContent) {
		$tableDefintions = parent::getFieldDefinitions_fileContent($fileContent);
		$fieldDefinitionsUtility = t3lib_div::makeInstance('Tx_Uuid_Utility_FieldDefinitions');
		$tableDefintions = $fieldDefinitionsUtility->insertUuidColumn($tableDefintions);
		return $tableDefintions;
	}
}
?>