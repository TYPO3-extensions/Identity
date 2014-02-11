<?php
namespace Maroschik\Identity\Hooks;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Thomas Maroschik <tmaroschik@dfau.de>
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
 * Hooks for TYPO3 DB Preprocessing.
 *
 * @author Thomas Maroschik <tmaroschik@dfau.de>
 */
class DatabasePreProcessQueryHook implements \TYPO3\CMS\Core\Database\PreProcessQueryHookInterface {

	/**
	 * @var \Maroschik\Identity\IdentityMap
	 */
	protected $identityMap;

	/**
	 * Constructor method for the t3lib_DB posprocess hook
	 */
	public function __construct() {
		$this->identityMap = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Maroschik\Identity\IdentityMap');
	}

	/**
	 * Pre-processor for the INSERTquery method.
	 *
	 * @param string $table Database table name
	 * @param array $fieldsValues Field values as key => value pairs
	 * @param string|array $noQuoteFields List/array of keys NOT to quote
	 * @param \TYPO3\CMS\Core\Database\DatabaseConnection $parentObject
	 * @return void
	 */
	public function INSERTquery_preProcessAction(&$table, array &$fieldsValues, &$noQuoteFields, \TYPO3\CMS\Core\Database\DatabaseConnection $parentObject) {
		// Check if applicable
		$identifier = $this->identityMap->getIdentifierForNewResourceLocation($table);
		if ($identifier !== NULL) {
			$identityField = $this->identityMap->getIdentifierFieldForResourceLocation($table);
			$fieldsValues[$identityField] = $identifier;
		}
	}

	/**
	 * Pre-processor for the INSERTmultipleRows method.
	 *
	 * @param string $table Database table name
	 * @param array $fields Field names
	 * @param array $rows Table rows
	 * @param string/array $noQuoteFields List/array of keys NOT to quote
	 * @param \TYPO3\CMS\Core\Database\DatabaseConnection $parentObject
	 * @return void
	 */
	public function INSERTmultipleRows_preProcessAction(&$table, array &$fields, array &$rows, &$noQuoteFields, \TYPO3\CMS\Core\Database\DatabaseConnection $parentObject) {
		// Check if applicable
		$identifier = $this->identityMap->getIdentifierForNewResourceLocation($table);
		if ($identifier !== NULL) {
			$identityField = $this->identityMap->getIdentifierFieldForResourceLocation($table);
			foreach ($rows as &$row) {
				$fieldsValues[$identityField] = $identifier;
				$identifier = $this->identityMap->getIdentifierForNewResourceLocation($table);
			}
		}
	}

	/**
	 * Pre-processor for the SELECTquery method.
	 *
	 * @param string $select_fields Fields to be selected
	 * @param string $from_table Table to select data from
	 * @param string $where_clause Where clause
	 * @param string $groupBy Group by statement
	 * @param string $orderBy Order by statement
	 * @param integer $limit Database return limit
	 * @param \TYPO3\CMS\Core\Database\DatabaseConnection $parentObject
	 * @return void
	 */
	public function SELECTquery_preProcessAction(&$select_fields, &$from_table, &$where_clause, &$groupBy, &$orderBy, &$limit, \TYPO3\CMS\Core\Database\DatabaseConnection $parentObject) {
		// TODO: Implement SELECTquery_preProcessAction() method.
	}

	/**
	 * Pre-processor for the UPDATEquery method.
	 *
	 * @param string $table Database table name
	 * @param string $where WHERE clause
	 * @param array $fieldsValues Field values as key => value pairs
	 * @param string/array $noQuoteFields List/array of keys NOT to quote
	 * @param \TYPO3\CMS\Core\Database\DatabaseConnection $parentObject
	 * @return void
	 */
	public function UPDATEquery_preProcessAction(&$table, &$where, array &$fieldsValues, &$noQuoteFields, \TYPO3\CMS\Core\Database\DatabaseConnection $parentObject) {
		// TODO: Implement UPDATEquery_preProcessAction() method.
	}

	/**
	 * Pre-processor for the DELETEquery method.
	 *
	 * @param string $table Database table name
	 * @param string $where WHERE clause
	 * @param \TYPO3\CMS\Core\Database\DatabaseConnection $parentObject
	 * @return void
	 */
	public function DELETEquery_preProcessAction(&$table, &$where, \TYPO3\CMS\Core\Database\DatabaseConnection $parentObject) {
		// TODO: Implement DELETEquery_preProcessAction() method.
	}

	/**
	 * Pre-processor for the TRUNCATEquery method.
	 *
	 * @param string $table Database table name
	 * @param \TYPO3\CMS\Core\Database\DatabaseConnection $parentObject
	 * @return void
	 */
	public function TRUNCATEquery_preProcessAction(&$table, \TYPO3\CMS\Core\Database\DatabaseConnection $parentObject) {
		// TODO: Implement TRUNCATEquery_preProcessAction() method.
	}
}