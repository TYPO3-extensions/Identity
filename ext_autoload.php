<?php
/*
 * Register necessary class names with autoloader
 *
 * $Id: $
 */
return array(
	't3lib_db_preprocessqueryhook' => PATH_t3lib . 'interfaces/interface.t3lib_db_preprocessqueryhook.php',
	't3lib_db_postprocessqueryhook' => PATH_t3lib . 'interfaces/interface.t3lib_db_postprocessqueryhook.php',
	'tx_identity_map' =>  t3lib_extMgm::extPath('identity', 'Classes/Map.php'),
	'tx_identity_configuration_check' => t3lib_extMgm::extPath('identity', 'Classes/Configuration/Check.php'),
	'tx_identity_configuration_identityproviderinterface' => t3lib_extMgm::extPath('identity', 'Classes/Configuration/IdentityProviderInterface.php'),
	'tx_identity_providerinterface' => t3lib_extMgm::extPath('identity', 'Classes/ProviderInterface.php'),
	'tx_identity_provider_abstractuuid' => t3lib_extMgm::extPath('identity', 'Classes/Provider/AbstractUuid.php'),
	'tx_identity_provider_recorduuid' => t3lib_extMgm::extPath('identity', 'Classes/Provider/RecordUuid.php'),
	'tx_identity_provider_staticrecorduuid' => t3lib_extMgm::extPath('identity', 'Classes/Provider/StaticRecordUuid.php'),
	'tx_identity_utility_fielddefinitions' => t3lib_extMgm::extPath('identity', 'Classes/Utility/FieldDefinitions.php'),
	'tx_identity_utility_algorithms' => t3lib_extMgm::extPath('identity', 'Classes/Utility/Algorithms.php'),
);
?>
