<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Thomas Maroschik <tmaroschik@dfau.de>
 *
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
 *
 * This is the base of the identity extension.
 * @api
 */
class Tx_Identity_Map implements t3lib_Singleton {

	/**
	 * @var bool
	 */
	protected $isInitialized = false;

	/**
	 * @var Tx_Identity_ProviderInterface
	 */
	protected $defaultIdentityProvider;

	/**
	 * @var array
	 */
	protected $identityProviders = array();

	/**
	 * @var array
	 */
	protected $tableSpecificIdentityProviders = array();

	/**
	 * Constructor method for the identifier registry
	 * @api
	 */
	public function initializeObject() {
		if (!$this->isInitialized) {
			$this->initializeIdentityProviders();
			$this->initializeDefaultIdentityProvider();
			$this->initializeTableSpecificIdentityProviders();
			$this->isInitialized = true;
		}
	}

	/**
	 * Initialize all defined identity providers
	 */
	protected function initializeIdentityProviders() {
		$identityConfigurationCheck = t3lib_div::makeInstance('Tx_Identity_Configuration_Check');
		$identityConfiguration = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['identity'];
		$identityProviders = $identityConfiguration[Tx_Identity_Configuration_IdentityProviderInterface::PROVIDERS_LIST];

		foreach ($identityProviders as $identityProviderKey=>$identityProviderConfiguration) {
			$identityConfigurationCheck->checkIdentityProviderConfiguration($identityProviderKey);
			$identityProvider = t3lib_div::makeInstance($identityProviderConfiguration[Tx_Identity_Configuration_IdentityProviderInterface::PROVIDER_CLASS], $identityProviderKey);
			if (!$identityProvider) {
				throw InvalidArgumentException('The provider class "' . $identityProviderConfiguration[Tx_Identity_Configuration_IdentityProviderInterface::PROVIDER_CLASS] . '" could not be loaded.', 1300109265);
			}
			if (!$identityProvider instanceof Tx_Identity_ProviderInterface) {
				throw InvalidDataType('The provider class "' . $identityProviderConfiguration[Tx_Identity_Configuration_IdentityProviderInterface::PROVIDER_CLASS] . '" does not implement the "Tx_Identity_ProviderInterface".' , 1300110062);
			}
			if (method_exists($identityProvider, 'injectDb')) {
				$identityProvider->injectDb($GLOBALS['TYPO3_DB']);
			}
			if (method_exists($identityProvider, 'injectConfiguration')) {
				$identityProvider->injectConfiguration($identityProviderConfiguration);
			}
			$this->identityProviders[$identityProviderKey] = array(
				'field'		=> $identityProviderConfiguration[Tx_Identity_Configuration_IdentityProviderInterface::IDENTITY_FIELD],
				'provider'	=>	$identityProvider,
			);
		}
	}

	/**
	 * Initialize the default provider
	 */
	protected function initializeDefaultIdentityProvider() {
		$identityConfigurationCheck = t3lib_div::makeInstance('Tx_Identity_Configuration_Check');

		if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['identity'][Tx_Identity_Configuration_IdentityProviderInterface::DEFAULT_PROVIDER])) {
			throw InvalidArgumentException(
				'There is no default identity provider defined in ' .
				'$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'identity\'][Tx_Identity_Configuration_IdentityProviderInterface::DEFAULT_PROVIDER]',
				1300104461
			);
		}

		$defaultProviderKey = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['identity'][Tx_Identity_Configuration_IdentityProviderInterface::DEFAULT_PROVIDER];
		$identityConfigurationCheck->checkDefaultIdentityProviderConfiguration($defaultProviderKey);
		$this->defaultIdentityProvider = $this->identityProviders[$defaultProviderKey];
	}

	/**
	 * Initialize the table specific providers
	 */
	protected function initializeTableSpecificIdentityProviders() {
		$identityConfigurationCheck = t3lib_div::makeInstance('Tx_Identity_Configuration_Check');

		if (isset($GLOBALS['TCA'])) {
			foreach ($GLOBALS['TCA'] as $table=>$configuration) {
				t3lib_div::loadTCA($table);
				$configuration = $GLOBALS['TCA'][$table];

				if (isset($GLOBALS['TCA'][$table]['ctrl']['EXT']['identity'][Tx_Identity_Configuration_IdentityProviderInterface::KEY])) {

					$identityProviderKey = $GLOBALS['TCA'][$table]['ctrl']['EXT']['identity'][Tx_Identity_Configuration_IdentityProviderInterface::KEY];;
					$identityConfigurationCheck->checkTableSpecificIdentityProviderConfiguration($table, $identityProviderKey);

					$this->tableSpecificIdentityProviders[$table] = $this->identityProviders[$identityProviderKey];
				}
			}
		} else {
			throw new Exception('TCA is not available at the moment.', 1300109740);
		}
	}

	/**
	 * Returns the identifier field for a resource location
	 *
	 * @param string $tablename
	 */
	public function getIdentifierFieldForResourceLocation($tablename) {
		$this->initializeObject();
		if (isset($this->tableSpecificIdentityProviders[$tablename])) {
			// Look for a more specific identity provider first
			return $this->tableSpecificIdentityProviders[$tablename]['field'];
		} else {
			// else take the default provider
			return $this->defaultIdentityProvider['field'];
		}
	}

	/**
	 * Returns a unique identifier for a resource location
	 *
	 * @param string $tablename
	 * @param int $uid
	 * @return mixed the unique identifier
	 * @api
	 */
	public function getIdentifierForResourceLocation($tablename, $uid) {
		$this->initializeObject();
		if (isset($this->tableSpecificIdentityProviders[$tablename])) {
			// Look for a more specific identity provider first
			return $this->tableSpecificIdentityProviders[$tablename]['provider']->getIdentifierForResourceLocation($tablename, $uid);
		} else {
			// else take the default provider
			return $this->defaultIdentityProvider['provider']->getIdentifierForResourceLocation($tablename, $uid);
		}
	}

	/**
	 * Requests a new identifier for a resource location
	 *
	 * @param string $tablename
	 * @return void
	 */
	public function getIdentifierForNewResourceLocation($tablename) {
		$this->initializeObject();
		if (isset($this->tableSpecificIdentityProviders[$tablename])) {
			return $this->tableSpecificIdentityProviders[$tablename]['provider']->getIdentifierForNewResourceLocation($tablename);
		} else {
			return $this->defaultIdentityProvider['provider']->getIdentifierForNewResourceLocation($tablename);
		}
	}

	/**
	 * Returns a resource location for an identifier
	 *
	 * @param mixed $identifier
	 * @return array [tablename, uid] the resource location
	 * @api
	 */
	public function getResourceLocationForIdentifier($identifier) {
		$this->initializeObject();
		// Ask each table specific provider first
		foreach ($this->tableSpecificIdentityProviders as $tablename=>$providerArray) {
			try {
				if ($providerArray['provider']->validateIdentifier($identifier)) {
					$resouceLocation = $providerArray['provider']->getResourceLocationForIdentifier($identifier);
					if ($resouceLocation) {
						return $resouceLocation;
					}
				}
			} catch (Exception $e) {
				// TODO implement logging
			}
		}
		// else the default provider
		return $this->defaultIdentityProvider['provider']->getResourceLocationForIdentifier($identifier);
	}


	/**
	 * Give all providers the chance to perform some kind of rebuild
	 * @api
	 */
	public function rebuild() {
		$this->initializeObject();
		foreach ($this->identityProviders as $providerArray) {
			if (method_exists($providerArray['provider'], 'rebuild')) {
				$providerArray['provider']->rebuild();
			}
		}
	}

	/**
	 * Give all providers the chance to perform some kind of persistence
	 * @api
	 */
	public function commit() {
		$this->initializeObject();
		foreach ($this->identityProviders as $providerArray) {
			if (method_exists($providerArray['provider'], 'commit')) {
				$providerArray['provider']->commit();
			}
		}
	}

}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['EXT:uuid/Class/Registry.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['EXT:uuid/Class/Registry.php']);
}

?>