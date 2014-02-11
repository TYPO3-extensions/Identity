<?php
namespace Maroschik\Identity\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Thomas Maroschik <tmaroschik@dfau.de>
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

use Maroschik\Identity\Configuration\IdentityProviderConfigurationInterface as ProviderConfiguration;

/**
 * A utility class for the field defintions
 *
 * @author Thomas Maroschik <tmaroschik@dfau.de>
 */
class FieldDefinitions {

	/**
	 * Create SQL for identity fields
	 *
	 * @return string
	 */
	protected function getInsertIdentityColumnSql() {
		$sqlContent = LF . LF . LF;
		$identityFieldDefinitions = $this->getIdentityFieldDefintions();
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
	 * @throws \InvalidArgumentException
	 * @return array
	 */
	protected function getIdentityFieldDefintions() {
		$identityFieldDefintions = array();
		/** @var $identityConfigurationCheck \Maroschik\Identity\Configuration\ConfigurationCheck */
		$identityConfigurationCheck = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Maroschik\Identity\Configuration\ConfigurationCheck');
		/** @var $identityMap \Maroschik\Identity\IdentityMap */
		$identityMap = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Maroschik\Identity\IdentityMap');
		$identityConfiguration = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['identity'];
		$identityProviders = $identityConfiguration[ProviderConfiguration::PROVIDERS_LIST];
		foreach ($GLOBALS['TCA'] as $table => $definition) {
			if ($identityMap->isApplicable($table)) {
				if (isset($definition['ctrl']['EXT']['identity'][ProviderConfiguration::KEY])) {
					$identityProviderKey = $definition['ctrl']['EXT']['identity'][ProviderConfiguration::KEY];
					$identityProviderField = $identityProviders[$identityProviderKey][ProviderConfiguration::IDENTITY_FIELD];
					$identityConfigurationCheck->checkTableSpecificIdentityProviderConfiguration($table, $identityProviderKey);
				} elseif (isset($identityConfiguration[ProviderConfiguration::DEFAULT_PROVIDER])) {
					$identityProviderKey = $identityConfiguration[ProviderConfiguration::DEFAULT_PROVIDER];
					$identityProviderField = $identityProviders[$identityProviderKey][ProviderConfiguration::IDENTITY_FIELD];
					$identityConfigurationCheck->checkDefaultIdentityProviderConfiguration($identityProviderKey);
				} else {
					throw new \InvalidArgumentException(
						'There is no default identity provider defined in ' .
						'$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'identity\'][ProviderConfiguration::DEFAULT_PROVIDER]',
						1300104461
					);
				}
				// Adds field + index definition
				$identityFieldDefintions[$table]['fields'][$identityProviderField] = $identityProviders[$identityProviderKey][ProviderConfiguration::IDENTITY_FIELD_CREATE_CLAUSE];
				$identityFieldDefintions[$table]['keys'][$identityProviderField] = 'KEY ' . $identityProviderField . ' (' . $identityProviderField . ')';
			}
		}
		foreach ($identityProviders as $identityProviderKey=>$identityProviderConfiguration) {
			$identityConfigurationCheck->checkIdentityProviderConfiguration($identityProviderKey);
			$identityField = $identityProviderConfiguration[ProviderConfiguration::IDENTITY_FIELD];
			$identityFieldCreateClause = $identityProviderConfiguration[ProviderConfiguration::IDENTITY_FIELD_CREATE_CLAUSE];
			// Adds field + index definition
			$identityFieldDefintions['sys_identity']['fields'][$identityField] = $identityFieldCreateClause;
			$identityFieldDefintions['sys_identity']['keys'][$identityField] = 'KEY ' . $identityField . ' (' . $identityField . ')';
		}
		return $identityFieldDefintions;
	}


	/**
	 * A slot method to inject the identity database fields to the
	 * tables defintion string
	 *
	 * @param array $sqlString
	 * @return array
	 */
	public function addIdentityFieldsToTablesDefintion(array $sqlString) {
		$sqlString[] = $this->getInsertIdentityColumnSql();
		return array('sqlString' => $sqlString);
	}

	/**
	 * A slot method to inject the identity database fields of an
	 * extension to the tables defintion string
	 *
	 * @param array $sqlString
	 * @param string $extensionKey
	 * @return array
	 */
	public function addExtensionIdentityFieldsToTablesDefintion(array $sqlString, $extensionKey) {
		$sqlString[] = $this->getInsertIdentityColumnSql($extensionKey);
		return array('sqlString' => $sqlString, 'extensionKey' => $extensionKey);
	}

}