<?php

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Fabien Udriot <fabien.udriot@ecodev.ch>
 *  All rights reserved
 *
 *  This script is part of the Typo3 project. The Typo3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 * ************************************************************* */

require_once(t3lib_extMgm::extPath('install') . 'mod/class.tx_install.php');

/**
 * Class for updating identity
 *
 * @author		Fabien Udriot <fabien.udriot@ecodev.ch>
 * @package		TYPO3
 * @subpackage	tx_templatedisplay
 *
 * $Id: class.ext_update.php 567 2010-12-28 16:22:01Z fudriot $
 */
class ext_update {

	/**
	 * Defines whether the UPDATE! menu should be displayed or not.
	 *
	 * @return boolean
	 */
	public function access() {
		return TRUE;
	}
	
	/**
	 * Remove statements that contains not a uuid statement
	 *
	 * @return boolean
	 */
	protected function cleanUp($statements) {
		
		$result = array();
		foreach ($statements as $key => $statement) {
			if (strpos($statement, 'ADD uuid ') !== FALSE) {
				$result[$key] = $statement;
			}
		}
		
		return $result;
	}

	/**
	 * Main function, returning the HTML content of the update wizard
	 *
	 * @return	string	HTML to display
	 */
	public function main() {

			// instantiate the installer
		$installer = t3lib_div::makeInstance('tx_install');

			// load the SQL files
		$tblFileContent = t3lib_div::getUrl(PATH_t3lib . 'stddb/tables.sql');
		foreach ($GLOBALS['TYPO3_LOADED_EXT'] as $loadedExtConf) {
			if (is_array($loadedExtConf) && $loadedExtConf['ext_tables.sql']) {
				$tblFileContent .= LF . LF . LF . LF . t3lib_div::getUrl($loadedExtConf['ext_tables.sql']);
			}
		}


		$fileContent = implode(
				LF, $installer->getStatementArray($tblFileContent, 1, '^CREATE TABLE ')
		);

			// get the table definitions
		$FDfile = $installer->getFieldDefinitions_fileContent($fileContent);
		if (!count($FDfile)) {
			die("Error: There were no 'CREATE TABLE' definitions in the provided file");
		}


			// Execute the statement if button submit has been pressed
		if (t3lib_div::_GP('update') == 'doUpdate') {

			$statement = t3lib_div::_GP('TYPO3_INSTALL');
			if (is_array($statement['database_update'])) {
				$FDdb = $installer->getFieldDefinitions_database();
				$diff = $installer->getDatabaseExtra($FDfile, $FDdb);
				$update_statements = $installer->getUpdateSuggestions($diff);

				$results = array();
				$results[] = $installer->performUpdateQueries($update_statements['add'], $statement['database_update']);
			}
		}

			// get the current database definition
		$FDdb = $installer->getFieldDefinitions_database();

			// get a diff and check if a field uuid is missing somewhere
		$diff = $installer->getDatabaseExtra($FDfile, $FDdb);
		$update_statements = $installer->getUpdateSuggestions($diff);
		$update_statements['add'] = $this->cleanUp($update_statements['add']);
		

		if (!empty($update_statements['add'])) {
			$content = '
				<style>
					fieldset {
						border: 0;
					}
					legend {
						font-weight: bold;
						margin-left: 1em;
					}
					fieldset li {
						clear: left;
						float: left;
						margin-bottom: 0.5em;
						width: 100%;
					}
					.t3-install-form-label-after label {
						padding-left: 1em;
					}
					genera...1016810 (line 117)
					.t3-install-form-label-after label, .t3-install-form-label-above label {
						display: block;
						float: none;
						margin-right: 0;
						width: auto;
					}
				</style>
			';
			$content .= '
				<p style="margin-bottom: 10px">
					There seems to be a number of differencies
					between the database and the selected
					SQL-file.
					<br />
					Please select which statements you want to
					execute in order to update your database:
				</p>
				';
			$content .=	'<form action="mod.php?&id=0&M=tools_em&CMD[showExt]=identity&SET[singleDetails]=updateModule" method="post">';
			$content .= $installer->generateUpdateDatabaseForm_checkboxes($update_statements['add'],'Add fields');
			$content .= '<input type="hidden" name="update" value ="doUpdate">';
			$content .= '<p><input type="submit" name="submitButton" value ="Update"></p>';
			$content .= '</form>';


		}
		else {
			$content .= '
			<div style="width: 600px; margin-top: 20px">
				<div class="typo3-message message-ok">
					<div class="message-header">'
						. $GLOBALS['LANG']->sL('LLL:EXT:identity/Resources/Private/Language/locallang.xml:ok_table_header') .
					'</div>
					<div class="message-body">
				</div>
			</div>
			';

		}

			// display a notification also if missing table are found
		if (!empty($update_statements['create_table'])) {
			$content .= '
			<div style="width: 600px; margin-top: 20px">
				<div class="typo3-message message-information">
					<div class="message-header">'
						. $GLOBALS['LANG']->sL('LLL:EXT:identity/Resources/Private/Language/locallang.xml:information_table_header') .
					'</div>
					<div class="message-body">
						' . $GLOBALS['LANG']->sL('LLL:EXT:identity/Resources/Private/Language/locallang.xml:information_table_message') .
				'</div>
			</div>
			';

		}
		return $content;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templatedisplay/class.ext_update.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templatedisplay/class.ext_update.php']);
}
?>