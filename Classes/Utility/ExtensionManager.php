<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Fabien Udriot <fabien.udriot@ecodev.ch>
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
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Identity_Utility_ExtensionManager {

	/**
	 * Display a message to the Extension Manager whether the database needs to be updated or not.
	 *
	 * @return string the HTML message
	 */
	public function displayMessage(&$params, &$tsObj) {
		$out = '';

		if ($this->needsUpdate()) {
			$out .= '
			<div style="">
				<div class="typo3-message message-warning">
					<div class="message-header">'
						. $GLOBALS['LANG']->sL('LLL:EXT:identity/Resources/Private/Language/locallang.xml:updater_header') .
					'</div>
					<div class="message-body">
						' . $GLOBALS['LANG']->sL('LLL:EXT:identity/Resources/Private/Language/locallang.xml:updater_message') . '
						<a target="_blank"
							style="text-decoration:underline;"
							href="mod.php?&amp;id=0&amp;M=tools_em&amp;CMD[showExt]=identity&amp;SET[singleDetails]=updateModule">
						' . $GLOBALS['LANG']->sL('LLL:EXT:identity/Resources/Private/Language/locallang.xml:open_updater') . '</a>.
					</div>
				</div>
			</div>
			';

		}
		else {
			$out .= '
			<div style="">
				<div class="typo3-message message-ok">
					<div class="message-header">'
						. $GLOBALS['LANG']->sL('LLL:EXT:identity/Resources/Private/Language/locallang.xml:ok_header') .
					'</div>
					<div class="message-body">
						' . $GLOBALS['LANG']->sL('LLL:EXT:identity/Resources/Private/Language/locallang.xml:ok_message') . '
					</div>
				</div>
			</div>
			';
		}

		return $out;
	}
	
	/**
	 * Check the database and tells whether it needs update
	 *
	 * @return boolean
	 */
	protected function needsUpdate() {
		
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

			// get the current database definition
		$FDdb = $installer->getFieldDefinitions_database();

			// get a diff and check if a field uuid is missing somewhere
		$diff = $installer->getDatabaseExtra($FDfile, $FDdb);
		$update_statements = $installer->getUpdateSuggestions($diff);
		$update_statements['add'] = $this->cleanUp($update_statements['add']);

		return ! empty($update_statements['add']);
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
}

?>