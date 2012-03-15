<?php

########################################################################
# Extension Manager/Repository config file for ext "identity".
#
# Auto generated 30-01-2012 13:04
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Identity',
	'description' => '',
	'category' => 'plugin',
	'author' => 'Thomas Maroschik, Fabien Udriot',
	'author_email' => 'tmaroschik@dfau.de, fabien.udriot@ecodev.ch',
	'author_company' => 'DFAU, Ecodev',
	'shy' => '',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'version' => '0.1.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '4.6.0-4.7.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'suggests' => array(
	),
	'_md5_values_when_last_written' => 'a:21:{s:20:"class.ext_update.php";s:4:"9073";s:16:"ext_autoload.php";s:4:"2c9b";s:21:"ext_conf_template.txt";s:4:"0608";s:17:"ext_localconf.php";s:4:"d3f6";s:14:"ext_tables.php";s:4:"ab1d";s:14:"ext_tables.sql";s:4:"da8f";s:15:"Classes/Map.php";s:4:"87e1";s:29:"Classes/ProviderInterface.php";s:4:"7b19";s:31:"Classes/Configuration/Check.php";s:4:"df54";s:51:"Classes/Configuration/IdentityProviderInterface.php";s:4:"41a2";s:43:"Classes/Hooks/class.tx_identity_em_hook.php";s:4:"23f9";s:55:"Classes/Hooks/class.tx_identity_t3lib_db_preprocess.php";s:4:"6790";s:48:"Classes/Hooks/class.tx_identity_tcemain_hook.php";s:4:"dc3c";s:29:"Classes/Install/Installer.php";s:4:"6be5";s:33:"Classes/Provider/AbstractUuid.php";s:4:"d378";s:31:"Classes/Provider/RecordUuid.php";s:4:"af00";s:37:"Classes/Provider/StaticRecordUuid.php";s:4:"6b5b";s:30:"Classes/Utility/Algorithms.php";s:4:"8b96";s:36:"Classes/Utility/ExtensionManager.php";s:4:"8af1";s:36:"Classes/Utility/FieldDefinitions.php";s:4:"2798";s:40:"Resources/Private/Language/locallang.xml";s:4:"1032";}',
);

?>