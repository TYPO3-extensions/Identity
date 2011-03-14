<?php

$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_install.php'] = t3lib_extMgm::extPath($_EXTKEY) . 'Classes/Xclass/class.ux_t3lib_install.php';
$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/install/mod/class.tx_install.php'] = t3lib_extMgm::extPath($_EXTKEY) . 'Classes/Xclass/class.ux_tx_install.php';
$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_db.php'] = t3lib_extMgm::extPath($_EXTKEY) . 'Classes/Xclass/class.ux_t3lib_db.php';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][$_EXTKEY] = 'EXT:' . $_EXTKEY . '/Classes/Hooks/class.tx_identity_tcemain_hook.php:tx_identity_tcemain_hook';

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY] = array(
	Tx_Identity_Configuration_IdentityProviderInterface::PROVIDERS_LIST	=> array(
		'uuid'	=> array(
			Tx_Identity_Configuration_IdentityProviderInterface::IDENTITY_FIELD_CREATE_CLAUSE	=>	'varchar(36) NOT NULL default \'\'',
			Tx_Identity_Configuration_IdentityProviderInterface::PROVIDER_CLASS					=>	'Tx_Identity_Provider_Uuid',
		),
	),
	Tx_Identity_Configuration_IdentityProviderInterface::DEFAULT_PROVIDER	=> 'uuid',
);

?>