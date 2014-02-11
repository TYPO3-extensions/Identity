<?php

########################################################################
# Extension Manager/Repository config file for ext "identity".
#
# Auto generated 15-03-2012 15:27
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Identity',
	'description' => 'Identity brings Universally Unique Identifier (UUID) in TYPO3. A UUID is an identifier that is immutable and unique across time and space. The extension hooks into the TYPO3 database class and inserts identifiers to TCA defined records transparently. Further it enables you to register, unregister and lookup identifiers in the identity map via an easy API. ',
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
	'version' => '0.2.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '6.2.0-6.2.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'suggests' => array(
	),
	'_md5_values_when_last_written' => 'a:23:{s:20:"class.ext_update.php";s:4:"9073";s:16:"ext_autoload.php";s:4:"0935";s:21:"ext_conf_template.txt";s:4:"a36c";s:17:"ext_localconf.php";s:4:"34fa";s:14:"ext_tables.php";s:4:"ab1d";s:14:"ext_tables.sql";s:4:"da8f";s:15:"Classes/Map.php";s:4:"6faf";s:29:"Classes/ProviderInterface.php";s:4:"530b";s:31:"Classes/Configuration/Check.php";s:4:"df54";s:51:"Classes/Configuration/IdentityProviderConfigurationInterface.phpInterface.php";s:4:"8be3";s:43:"Classes/Hooks/class.tx_identity_em_hook.php";s:4:"4ad1";s:55:"Classes/Hooks/class.tx_identity_t3lib_db_preprocess.php";s:4:"6790";s:48:"Classes/Hooks/class.tx_identity_tcemain_hook.php";s:4:"dc3c";s:29:"Classes/Install/Installer.php";s:4:"6be5";s:33:"Classes/Provider/AbstractUuid.php";s:4:"3605";s:31:"Classes/Provider/RecordUuid.php";s:4:"af00";s:37:"Classes/Provider/StaticRecordUuid.php";s:4:"9ce8";s:29:"Classes/Tasks/RebuildTask.php";s:4:"a1c1";s:30:"Classes/Utility/Algorithms.php";s:4:"8b96";s:36:"Classes/Utility/ExtensionManager.php";s:4:"2187";s:36:"Classes/Utility/FieldDefinitions.php";s:4:"a73f";s:40:"Resources/Private/Language/locallang.xml";s:4:"4128";s:14:"doc/manual.sxw";s:4:"32d0";}',
);

?>
