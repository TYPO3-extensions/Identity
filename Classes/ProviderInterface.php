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

interface Tx_Identity_ProviderInterface {

	/**
	 * Sets the provider key on the provider
	 *
	 * @param string $providerKey
	 */
	public function __construct($providerKey);

	/**
	 * Validates the identifier
	 *
	 * @param mixed $identifier
	 */
	public function validateIdentifier($identifier);

	/**
	 * Returns a unique identifier for a resource location
	 *
	 * @param string $tablename
	 * @param int $uid
	 * @return mixed the unique identifier
	 */
	public function getIdentifierForResourceLocation($tablename, $uid);

	/**
	 * Requests a new identifier for a resource location
	 *
	 * @param string $tablename
	 * @return mixed
	 */
	public function getIdentifierForNewResourceLocation($tablename);

	/**
	 * Returns a resource location for an identifier
	 *
	 * @param mixed $identifier
	 * @return array [tablename, uid] the resource location
	 */
	public function getResourceLocationForIdentifier($identifier);
}

?>