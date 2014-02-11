<?php
namespace Maroschik\Identity;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Thomas Maroschik <tmaroschik@dfau.de>
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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use Maroschik\Identity\Configuration\IdentityProviderConfigurationInterface as ProviderConfiguration;

/**
 * This is the base of the identity extension.
 *
 * @author Thomas Maroschik <tmaroschik@dfau.de>
 * @api
 */
class IdentityMap implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var bool
	 */
	protected $isInitialized = FALSE;

	/**
	 * @var Provider\ProviderInterface
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
	 *
	 * @api
	 */
	public function initializeObject() {
		if (!$this->isInitialized) {
			$this->initializeIdentityProviders();
			$this->initializeDefaultIdentityProvider();
			$this->initializeTableSpecificIdentityProviders();
			$this->isInitialized = TRUE;
		}
	}

	/**
	 * Initialize all defined identity providers
	 */
	protected function initializeIdentityProviders() {
		/** @var $identityConfigurationCheck \Maroschik\Identity\Configuration\ConfigurationCheck */
		$identityConfigurationCheck = GeneralUtility::makeInstance('Maroschik\Identity\Configuration\ConfigurationCheck');
		$identityConfiguration = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['identity'];
		$identityProviders = $identityConfiguration[ProviderConfiguration::PROVIDERS_LIST];
		foreach ($identityProviders as $identityProviderKey => $identityProviderConfiguration) {
			$identityConfigurationCheck->checkIdentityProviderConfiguration($identityProviderKey);
			/** @var $identityProvider Provider\ProviderInterface */
			$identityProvider = GeneralUtility::makeInstance($identityProviderConfiguration[ProviderConfiguration::PROVIDER_CLASS], $identityProviderKey);
			if (!$identityProvider) {
				throw new \InvalidArgumentException('The provider class "' . $identityProviderConfiguration[ProviderConfiguration::PROVIDER_CLASS] . '" could not be loaded.', 1300109265);
			}
			if (!$identityProvider instanceof Provider\ProviderInterface) {
				throw new \InvalidArgumentException('The provider class "' . $identityProviderConfiguration[ProviderConfiguration::PROVIDER_CLASS] . '" does not implement the "ProviderInterface".', 1300110062);
			}
			if (method_exists($identityProvider, 'injectDatabaseConnection')) {
				$identityProvider->injectDatabaseConnection($GLOBALS['TYPO3_DB']);
			}
			if (method_exists($identityProvider, 'injectConfiguration')) {
				$identityProvider->injectConfiguration($identityProviderConfiguration);
			}
			$this->identityProviders[$identityProviderKey] = array(
				'field' => $identityProviderConfiguration[ProviderConfiguration::IDENTITY_FIELD],
				'provider' => $identityProvider,
			);
		}
	}

	/**
	 * Initialize the default provider
	 */
	protected function initializeDefaultIdentityProvider() {
		/** @var $identityConfigurationCheck \Maroschik\Identity\Configuration\ConfigurationCheck */
		$identityConfigurationCheck = GeneralUtility::makeInstance('Maroschik\Identity\Configuration\ConfigurationCheck');
		if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['identity'][ProviderConfiguration::DEFAULT_PROVIDER])) {
			throw new \InvalidArgumentException(
				'There is no default identity provider defined in ' .
				'$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'identity\'][ProviderConfiguration::DEFAULT_PROVIDER]',
				1300104461
			);
		}
		$defaultProviderKey = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['identity'][ProviderConfiguration::DEFAULT_PROVIDER];
		$identityConfigurationCheck->checkDefaultIdentityProviderConfiguration($defaultProviderKey);
		$this->defaultIdentityProvider = $this->identityProviders[$defaultProviderKey];
	}

	/**
	 * Initialize the table specific providers
	 */
	protected function initializeTableSpecificIdentityProviders() {
		/** @var $identityConfigurationCheck \Maroschik\Identity\Configuration\ConfigurationCheck */
		$identityConfigurationCheck = GeneralUtility::makeInstance('Maroschik\Identity\Configuration\ConfigurationCheck');
		if (isset($GLOBALS['TCA'])) {
			foreach ($GLOBALS['TCA'] as $table => $configuration) {
				if (isset($GLOBALS['TCA'][$table]['ctrl']['EXT']['identity'][ProviderConfiguration::KEY])) {
					$identityProviderKey = $GLOBALS['TCA'][$table]['ctrl']['EXT']['identity'][ProviderConfiguration::KEY];;
					$identityConfigurationCheck->checkTableSpecificIdentityProviderConfiguration($table, $identityProviderKey);
					$this->tableSpecificIdentityProviders[$table] = $this->identityProviders[$identityProviderKey];
				}
			}
		}
	}

	/**
	 * Returns the identifier field for a resource location
	 *
	 * @param string $tablename
	 * @return string
	 */
	public function getIdentifierFieldForResourceLocation($tablename) {
		$this->initializeObject();
		return isset($this->tableSpecificIdentityProviders[$tablename])
			? $this->tableSpecificIdentityProviders[$tablename]['field']
			: $this->defaultIdentityProvider['field'];
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
		if (!$this->isApplicable($tablename)) {
			return NULL;
		}
		/** @var Provider\ProviderInterface $provider */
		$provider = isset($this->tableSpecificIdentityProviders[$tablename])
				? $this->tableSpecificIdentityProviders[$tablename]['provider']
				: $this->defaultIdentityProvider['provider'];
		return $provider->getIdentifierForResourceLocation($tablename, $uid);
	}

	/**
	 * Requests a new identifier for a resource location
	 *
	 * @param string $tablename
	 * @return mixed
	 */
	public function getIdentifierForNewResourceLocation($tablename) {
		if (!$this->isApplicable($tablename)) {
			return NULL;
		}
		/** @var Provider\ProviderInterface $provider */
		$provider = isset($this->tableSpecificIdentityProviders[$tablename])
				? $this->tableSpecificIdentityProviders[$tablename]['provider']
				: $this->defaultIdentityProvider['provider'];
		return $provider->getIdentifierForNewResourceLocation($tablename);
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
		foreach ($this->tableSpecificIdentityProviders as $providerArray) {
			try {
				/** @var Provider\ProviderInterface $provider */
				$provider = $providerArray['provider'];
				if ($provider->validateIdentifier($identifier)) {
					$resouceLocation = $provider->getResourceLocationForIdentifier($identifier);
					if ($resouceLocation) {
						return $resouceLocation;
					}
				}
			}
			catch (\Exception $e) {
				//@TODO implement logging
			}
		}
		// else the default provider
		/** @var Provider\ProviderInterface $defaultProvider */
		$defaultProvider = $this->defaultIdentityProvider['provider'];
		return $defaultProvider->getResourceLocationForIdentifier($identifier);
	}

	/**
	 * Returns if the tablename can have an identifier
	 *
	 * @param string $tablename
	 * @return bool
	 * @api
	 */
	public function isApplicable($tablename) {
		$this->initializeObject();
		/** @var Provider\ProviderInterface $provider */
		$provider = isset($this->tableSpecificIdentityProviders[$tablename])
				? $this->tableSpecificIdentityProviders[$tablename]['provider']
				: $this->defaultIdentityProvider['provider'];
		return $provider->isApplicable($tablename);
	}

	/**
	 * Give all providers the chance to perform some kind of rebuild
	 *
	 * @api
	 */
	public function rebuild() {
		$this->initializeObject();
		foreach ($this->identityProviders as $providerArray) {
			/** @var Provider\ProviderInterface $provider */
			$provider = $providerArray['provider'];
			if (method_exists($provider, 'rebuild')) {
				$provider->rebuild();
			}
		}
	}

	/**
	 * Give all providers the chance to perform some kind of persistence
	 *
	 * @api
	 */
	public function commit() {
		$this->initializeObject();
		foreach ($this->identityProviders as $providerArray) {
			/** @var Provider\ProviderInterface $provider */
			$provider = $providerArray['provider'];
			if (method_exists($provider, 'commit')) {
				$provider->commit();
			}
		}
	}

}