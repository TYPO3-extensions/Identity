<?php
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

if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

use Maroschik\Identity\Configuration\IdentityProviderConfigurationInterface as ProviderConfiguration;

// Configure the default identity providers.
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['identity'] = array(
	ProviderConfiguration::PROVIDERS_LIST => array(
		'recordUuid' => array(
			ProviderConfiguration::IDENTITY_FIELD => 'uuid',
			ProviderConfiguration::IDENTITY_FIELD_CREATE_CLAUSE => 'char(36) NOT NULL default \'\'',
			ProviderConfiguration::PROVIDER_CLASS => 'Maroschik\Identity\Provider\RecordUuidProvider',
		),
		'staticRecordUuid' => array(
			ProviderConfiguration::IDENTITY_FIELD => 'uuid',
			ProviderConfiguration::IDENTITY_FIELD_CREATE_CLAUSE => 'char(36) NOT NULL default \'\'',
			ProviderConfiguration::PROVIDER_CLASS => 'Maroschik\Identity\Provider\StaticRecordUuidProvider',
		),
	),
	ProviderConfiguration::DEFAULT_PROVIDER => 'recordUuid',
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['identity'] = 'Maroschik\\Identity\\Hooks\\DataHandlerHook';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_db.php']['queryProcessors']['identity'] = 'Maroschik\\Identity\\Hooks\\DatabasePreProcessQueryHook';

$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');
$signalSlotDispatcher->connect(
	'TYPO3\\CMS\\Install\\Service\\SqlExpectedSchemaService',
	'tablesDefinitionIsBeingBuilt',
	'Maroschik\\Identity\\Utility\\FieldDefinitions',
	'addIdentityFieldsToTablesDefintion'
);
$signalSlotDispatcher->connect(
	'TYPO3\\CMS\\Extensionmanager\\Utility\\InstallUtility',
	'tablesDefinitionIsBeingBuilt',
	'Maroschik\\Identity\\Utility\\FieldDefinitions',
	'addExtensionIdentityFieldsToTablesDefintion'
);

if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('scheduler')) {
	// Register extension list update task
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Maroschik\\Identity\\Tasks\\RebuildTask'] = array(
		'extension' => 'identity',
		'title' => 'LLL:EXT:identity/Resources/Private/Language/locallang.xml:tasks_rebuildTask.name',
		'description' => 'LLL:EXT:identity/Resources/Private/Language/locallang.xml:tasks_rebuildTask.description',
		'additionalFields' => '',
	);
}

?>