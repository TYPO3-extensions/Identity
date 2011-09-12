<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Xavier Perseguers <xavier@typo3.org>
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
 * A utility class for the field defintions
 *
 * @author Thomas Maroschik <tmaroschik@dfau.de>
 *
 * @package TYPO3
 * @subpackage identity
 */
class Tx_Identity_Utility_FieldDefinitions {

	/**
	 * Contains ignoreTCA
	 *
	 * @var bool
	 */
	protected $ignoreTCA = FALSE;

	/**
	 * Sets $ignoreTCA
	 *
	 * @param bool $ignoreTCA
	 */
	public function setIgnoreTCA($ignoreTCA) {
		$this->ignoreTCA = $ignoreTCA;
	}

	/**
	 * Returns $ignoreTCA
	 *
	 * @return bool
	 */
	public function getIgnoreTCA() {
		return $this->ignoreTCA;
	}


	/**
	 * Check if a table definition contains an uid and a pid, and insert a uuid column
	 * Returns a modified table definition
	 *
	 * @param array $tableDefinitions
	 * @return array
	 */
	public function insertIdentityColumn(array $tableDefinitions) {
		$identityFieldDefinitions = $this->getIdentityFieldDefintions($tableDefinitions);
		foreach ($identityFieldDefinitions as $table => $defintion) {
				foreach ($defintion['fields'] as $fieldName => $createString) {
					$tableDefinitions[$table]['fields'][$fieldName] = $createString;
				}
				foreach ($defintion['keys'] as $keyName => $createString) {
					$tableDefinitions[$table]['keys'][$keyName] = $createString;
				}
		}
		return $tableDefinitions;
	}

	/**
	 * Check if a table definition contains an uid and a pid, and insert a uuid column
	 * Returns a sql string that inserts the needed indentity fields
	 *
	 * @param array $tableDefinitions
	 * @return string
	 */
	public function getInsertIdentityColumnSql(array $tableDefinitions) {
		$sqlContent = LF . LF . LF;
		$identityFieldDefinitions = $this->getIdentityFieldDefintions($tableDefinitions);
		foreach ($identityFieldDefinitions as $table => $defintion) {
				$sqlRows = array();
				foreach ($defintion['fields'] as $fieldName => $createString) {
					$sqlRows[] = TAB . $fieldName . ' ' . $createString;
				}
				foreach ($defintion['keys'] as $createString) {
					$sqlRows[] = TAB . $createString;
				}
			$sqlContent .= 'CREATE TABLE ' . $table . ' (' . LF;
			$sqlContent .= implode(',' . LF, $sqlRows). LF;
			$sqlContent .= ');'  . LF . LF . LF;
		}
		return $sqlContent;
	}

	/**
	 * @throws InvalidArgumentException
	 * @param array $tableDefinitions
	 * @return array
	 */
	protected function getIdentityFieldDefintions(array $tableDefinitions) {
		$identityFieldDefintions = array();

		$identityConfigurationCheck = t3lib_div::makeInstance('Tx_Identity_Configuration_Check');
		$identityConfiguration = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['identity'];
		$identityProviders = $identityConfiguration[Tx_Identity_Configuration_IdentityProviderInterface::PROVIDERS_LIST];

		foreach ($tableDefinitions as $table => $definition) {
			t3lib_div::loadTCA($table);

			if (
				isset($GLOBALS['TCA'][$table])
				|| (
						$this->ignoreTCA
						&& in_array('uid', array_keys($definition['fields']))
						&& in_array('pid', array_keys($definition['fields']))
						&& (
								t3lib_div::isFirstPartOfStr($table, 'tx_')
								|| t3lib_div::isFirstPartOfStr($table, 'tt_')
								|| t3lib_div::isFirstPartOfStr($table, 'static_')
								|| $table == 'pages'
								|| $table == 'tt_content'
							)
				)
			) {

				if (isset($GLOBALS['TCA'][$table]['ctrl']['EXT']['identity'][Tx_Identity_Configuration_IdentityProviderInterface::KEY])) {

					$identityProviderKey = $GLOBALS['TCA'][$table]['ctrl']['EXT']['identity'][Tx_Identity_Configuration_IdentityProviderInterface::KEY];
					$identityProviderField = $identityProviders[$identityProviderKey][Tx_Identity_Configuration_IdentityProviderInterface::IDENTITY_FIELD];
					$identityConfigurationCheck->checkTableSpecificIdentityProviderConfiguration($table, $identityProviderKey);

				} elseif (isset($identityConfiguration[Tx_Identity_Configuration_IdentityProviderInterface::DEFAULT_PROVIDER])) {

					$identityProviderKey = $identityConfiguration[Tx_Identity_Configuration_IdentityProviderInterface::DEFAULT_PROVIDER];
					$identityProviderField = $identityProviders[$identityProviderKey][Tx_Identity_Configuration_IdentityProviderInterface::IDENTITY_FIELD];
					$identityConfigurationCheck->checkDefaultIdentityProviderConfiguration($identityProviderKey);

				} else {

					throw InvalidArgumentException(
						'There is no default identity provider defined in ' .
						'$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'identity\'][Tx_Identity_Configuration_IdentityProviderInterface::DEFAULT_PROVIDER]',
						1300104461
					);

				}

				// Adds field + index definition
				$identityFieldDefintions[$table]['fields'][$identityProviderField] = $identityProviders[$identityProviderKey][Tx_Identity_Configuration_IdentityProviderInterface::IDENTITY_FIELD_CREATE_CLAUSE];
				$identityFieldDefintions[$table]['keys'][$identityProviderField] = 'KEY ' . $identityProviderField . ' (' . $identityProviderField . ')';

			} elseif ($table === 'sys_identity') {

				foreach ($identityProviders as $identityProviderKey=>$identityProviderConfiguration) {

					$identityConfigurationCheck->checkIdentityProviderConfiguration($identityProviderKey);
					$identityField = $identityProviderConfiguration[Tx_Identity_Configuration_IdentityProviderInterface::IDENTITY_FIELD];
					$identityFieldCreateClause = $identityProviderConfiguration[Tx_Identity_Configuration_IdentityProviderInterface::IDENTITY_FIELD_CREATE_CLAUSE];

					// Adds field + index definition
					$identityFieldDefintions[$table]['fields'][$identityField] = $identityFieldCreateClause;
					$identityFieldDefintions[$table]['keys'][$identityField] = 'KEY ' . $identityField . ' (' . $identityField . ')';

				}

			}

		}

		return $identityFieldDefintions;
	}
}
?>