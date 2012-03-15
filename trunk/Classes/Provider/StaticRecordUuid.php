<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Thomas Maroschik <tmaroschik@dfau.de>
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
/**
 * This class is the concrete implementation of the abstract uuid class for all static records
 *
 * @author Thomas Maroschik <tmaroschik@dfau.de>
 *
 * @package TYPO3
 * @subpackage identity
 */
class Tx_Identity_Provider_StaticRecordUuid extends Tx_Identity_Provider_AbstractUuid {

	/**
	 * Rebuilds the registry
	 */
	public function rebuild() {
		$this->insertMissingUUIDs();
	}

	/**
	 * Walks through all tables and inserts an uuid to a record that has any
	 */
	protected function insertMissingUUIDs() {
		$identityField =  $this->configuration[Tx_Identity_Configuration_IdentityProviderInterface::IDENTITY_FIELD];
		foreach ($GLOBALS['TCA'] as $tablename=>$configuration) {
			$rows = $this->db->exec_SELECTgetRows('uid', $tablename, $identityField . " LIKE ''");
			if (count($rows)) {
				foreach ($rows as &$row) {
					$uuid = Tx_Identity_Utility_Algorithms::generateUUIDforStaticTable($tablename, $row['uid']);
					$this->db->exec_UPDATEquery($tablename, 'uid = ' .$row['uid'], array($identityField => $uuid));
					$this->insertQueue[$uuid] = array(
						 $identityField => $uuid,
						 'foreign_tablename' => $tablename,
						 'foreign_uid' => $row['uid']
					);
					$this->addToCache($uuid, $tablename, $row['uid']);
				}
			}
		}
	}

}