<?php
namespace Maroschik\Identity\Configuration;

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

use Maroschik\Identity\Configuration\IdentityProviderConfigurationInterface as ProviderConfiguration;

/**
 * A class that checks the configuration of the identity extension.
 *
 * @author Thomas Maroschik <tmaroschik@dfau.de>
 */
class ConfigurationCheck implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * Checks the configuration for an individual table
	 *
	 * @param string $table
	 * @param string $identityProvider
	 * @throws \InvalidArgumentException when there is an error in the table specific configuration
	 */
	public function checkTableSpecificIdentityProviderConfiguration($table, $identityProvider) {
		$this->checkIdentityProviderConfiguration($identityProvider);
		if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['identity'][ProviderConfiguration::PROVIDERS_LIST][$identityProvider])) {
			throw new \InvalidArgumentException(
				'The identity provider "' . $identityProvider .
				'" defined in $GLOBALS[\'TCA\'][\'' . $table . '\'][\'ctrl\'][\'EXT\'][\'identity\'][ProviderConfiguration::KEY]' .
				' is not in the list of available providers ' .
				'$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'identity\'][ProviderConfiguration::PROVIDERS_LIST]',
				1300103324
			);
		}
		if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['identity'][ProviderConfiguration::PROVIDERS_LIST][$identityProvider][ProviderConfiguration::IDENTITY_FIELD_CREATE_CLAUSE])) {
			throw new \InvalidArgumentException(
				'The identity provider "' . $identityProvider .
				'" defined in $GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'identity\'][ProviderConfiguration::PROVIDERS_LIST][\'' . $identityProvider . '\']' .
				' has no identity field create clause defined at ' .
				'$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'identity\'][ProviderConfiguration::PROVIDERS_LIST][\'' . $identityProvider . '\'][ProviderConfiguration::IDENTITY_FIELD_CREATE_CLAUSE]'
				,
				1300103750
			);
		}
	}

	/**
	 * Checks the configuration of the default identity provider
	 * @param string $defaultProvider
	 * @throws \InvalidArgumentException when there is an errer in the default configuration
	 */
	public function checkDefaultIdentityProviderConfiguration($defaultProvider) {
		$this->checkIdentityProviderConfiguration($defaultProvider);
		if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['identity'][ProviderConfiguration::PROVIDERS_LIST][$defaultProvider])) {
			throw new \InvalidArgumentException(
				'The default identity provider "' . $defaultProvider .
				'" defined in $GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'identity\'][ProviderConfiguration::DEFAULT_PROVIDER]' .
				' is not in the list of available providers ' .
				'$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'identity\'][ProviderConfiguration::PROVIDERS_LIST]',
				1300104323
			);
		}
	}

	/**
	 * Checks the configuration of an individual provider
	 *
	 * @param string $identityProvider
	 * @throws \InvalidArgumentException when there is an error in the provider configuration
	 */
	public function checkIdentityProviderConfiguration($identityProvider) {
		if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['identity'][ProviderConfiguration::PROVIDERS_LIST][$identityProvider])) {
			throw new \InvalidArgumentException(
				'The identity provider "' . $identityProvider .
				'" is not defined in $GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'identity\'][ProviderConfiguration::PROVIDERS_LIST][\'' . $identityProvider . '\']',
				1300109077
			);
		}
		if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['identity'][ProviderConfiguration::PROVIDERS_LIST][$identityProvider][ProviderConfiguration::IDENTITY_FIELD])) {
			throw new \InvalidArgumentException(
				'The identity provider "' . $identityProvider .
				'" defined in $GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'identity\'][ProviderConfiguration::PROVIDERS_LIST][\'' . $identityProvider . '\']' .
				' has no identity field defined at ' .
				'$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'identity\'][ProviderConfiguration::PROVIDERS_LIST][\'' . $identityProvider . '\'][ProviderConfiguration::IDENTITY_FIELD]'
				,
				1300110713
			);
		}
		if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['identity'][ProviderConfiguration::PROVIDERS_LIST][$identityProvider][ProviderConfiguration::IDENTITY_FIELD_CREATE_CLAUSE])) {
			throw new \InvalidArgumentException(
				'The identity provider "' . $identityProvider .
				'" defined in $GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'identity\'][ProviderConfiguration::PROVIDERS_LIST][\'' . $identityProvider . '\']' .
				' has no identity field create clause defined at ' .
				'$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'identity\'][ProviderConfiguration::PROVIDERS_LIST][\'' . $identityProvider . '\'][ProviderConfiguration::IDENTITY_FIELD_CREATE_CLAUSE]'
				,
				1300103750
			);
		}
		if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['identity'][ProviderConfiguration::PROVIDERS_LIST][$identityProvider][ProviderConfiguration::PROVIDER_CLASS])) {
			throw new \InvalidArgumentException(
				'The identity provider "' . $identityProvider .
				'" defined in $GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'identity\'][ProviderConfiguration::PROVIDERS_LIST][\'' . $identityProvider . '\']' .
				' has no provider class defined at ' .
				'$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'identity\'][ProviderConfiguration::PROVIDERS_LIST][\'' . $identityProvider . '\'][ProviderConfiguration::PROVIDER_CLASS]'
				,
				1300109191
			);
		}
	}

}