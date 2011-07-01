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

					$identityProviderKey = $GLOBALS['TCA'][$table]['ctrl']['EXT']['identity'][Tx_Identity_Configuration_IdentityProviderInterface::KEY];
					$identityProviderField = $identityProviders[$identityProviderKey][Tx_Identity_Configuration_IdentityProviderInterface::IDENTITY_FIELD];
					$identityConfigurationCheck->checkTableSpecificIdentityProviderConfiguration($table, $identityProviderKey);
					
						// Adds field + index definition
					$definition['fields'][$identityProviderField] = $identityProviders[$identityProviderKey][Tx_Identity_Configuration_IdentityProviderInterface::IDENTITY_FIELD_CREATE_CLAUSE];
					$definition['keys'][$identityProviderField] = 'KEY ' . $identityProviderField . ' (' . $identityProviderField . ')';

				} elseif (isset($identityConfiguration[Tx_Identity_Configuration_IdentityProviderInterface::DEFAULT_PROVIDER])) {

					$defaultProviderKey = $identityConfiguration[Tx_Identity_Configuration_IdentityProviderInterface::DEFAULT_PROVIDER];
					$defaultProviderField = $identityProviders[$defaultProviderKey][Tx_Identity_Configuration_IdentityProviderInterface::IDENTITY_FIELD];
					$identityConfigurationCheck->checkDefaultIdentityProviderConfiguration($defaultProviderKey);
					
						// Adds field + index definition
					$definition['fields'][$defaultProviderField] = $identityProviders[$defaultProviderKey][Tx_Identity_Configuration_IdentityProviderInterface::IDENTITY_FIELD_CREATE_CLAUSE];
					$definition['keys'][$defaultProviderField] = 'KEY ' . $defaultProviderField . ' (' . $defaultProviderField . ')';

				} else {

					throw InvalidArgumentException(
						'There is no default identity provider defined in ' .
						'$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'identity\'][Tx_Identity_Configuration_IdentityProviderInterface::DEFAULT_PROVIDER]',
						1300104461
					);

				}
			} elseif ($table == 'sys_identity') {

				foreach ($identityProviders as $identityProviderKey=>$identityProviderConfiguration) {
					$identityConfigurationCheck->checkIdentityProviderConfiguration($identityProviderKey);
					$identityField = $identityProviderConfiguration[Tx_Identity_Configuration_IdentityProviderInterface::IDENTITY_FIELD];
					$identityFieldCreateClause = $identityProviderConfiguration[Tx_Identity_Configuration_IdentityProviderInterface::IDENTITY_FIELD_CREATE_CLAUSE];
					
						// Adds field + index definition
					$definition['fields'][$identityField] = $identityFieldCreateClause;
					$definition['keys'][$identityField] = 'KEY ' . $identityField . ' (' . $identityField . ')';
					
				}

			}

		}

		return $tableDefinitions;
	}

}
?>