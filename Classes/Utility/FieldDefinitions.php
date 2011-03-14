<?php

class Tx_Identity_Utility_FieldDefinitions {

	/**
	 * Check if a table definition contains an uid and a pid, and insert a uuid column
	 *
	 * @param array $tableDefinitions
	 * @return array
	 */
	public function insertIdentityColumn($tableDefinitions) {
		$identityConfigurationCheck = t3lib_div::makeInstance('Tx_Identity_Configuration_Check');
		$identityConfiguration = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['identity'];
		$identityProviders = $identityConfiguration[Tx_Identity_Configuration_IdentityProviderInterface::PROVIDERS_LIST];

		foreach ($tableDefinitions as $table => &$definition) {
			t3lib_div::loadTCA($table);

			if (isset($GLOBALS['TCA'][$table])) {

				if (isset($GLOBALS['TCA'][$table]['ctrl']['EXT']['identity'][Tx_Identity_Configuration_IdentityProviderInterface::KEY])) {

					$identityProvider = $GLOBALS['TCA'][$table]['ctrl']['EXT']['identity'][Tx_Identity_Configuration_IdentityProviderInterface::KEY];
					$identityConfigurationCheck->checkTableSpecificIdentityProviderConfiguration($table, $identityProvider);
					$definition['fields'][$identityProvider] = $identityProviders[$identityProvider][Tx_Identity_Configuration_IdentityProviderInterface::IDENTITY_FIELD_CREATE_CLAUSE];

				} elseif (isset($identityConfiguration[Tx_Identity_Configuration_IdentityProviderInterface::DEFAULT_PROVIDER])) {

					$defaultProvider = $identityConfiguration[Tx_Identity_Configuration_IdentityProviderInterface::DEFAULT_PROVIDER];
					$identityConfigurationCheck->checkDefaultIdentityProviderConfiguration();
					$definition['fields'][$defaultProvider] = $identityProviders[$defaultProvider][Tx_Identity_Configuration_IdentityProviderInterface::IDENTITY_FIELD_CREATE_CLAUSE];

				} else {

					throw InvalidArgumentException(
						'There is no default identity provider defined in ' .
						'$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'identity\'][Tx_Identity_Configuration_IdentityProviderInterface::DEFAULT_PROVIDER]',
						1300104461
					);

				}
			}
		}

		return $tableDefinitions;
	}

}
?>