<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Thomas Maroschik <tmaroschik@dfau.de>
 *  All rights reserved
 *
 *  This class is a backport of the corresponding class of FLOW3.
 *  All credits go to the v5 team.
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
 * A utility class for various algorithms.
 *
 * @author Thomas Maroschik <tmaroschik@dfau.de>
 *
 * @package TYPO3
 * @subpackage identity
 */
class Tx_Identity_Utility_Algorithms {

	const TYPO3ORG_UUID = 'c4e6860b-3993-54a9-9c8b-f9bf558b0a77';

	/**
	 * Generates a universally unique identifier (UUID) according to RFC 4122 v4.
	 * The algorithm used here, might not be completely random.
	 *
	 * @return string The universally unique id
	 * @author Unknown
	 */
	static public function generateUUID() {
		return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0x0fff) | 0x4000,
			mt_rand(0, 0x3fff) | 0x8000,
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff));
	}

	/**
	 * Generates a universally unique identifier (UUID) according to RFC 4122 for static tables.
	 * @param string $tablename
	 * @param int $uid
	 * @return string The universally unique id
	 * @author Thomas Maroschik <tmaroschik@dfau.de>
	 * @throws
	 */
	static public function generateUUIDforStaticTable($tablename, $uid) {
		if (!isset($GLOBALS['TCA'][$tablename]['ctrl']['is_static']) && !$GLOBALS['TCA'][$tablename]['ctrl']['is_static']) {
			throw new InvalidArgumentException('The given tablename "' . $tablename . '" is not defined as is_static in TCA.', 1299074512);
		}
		return self::generateUUIDv5(self::TYPO3ORG_UUID, $tablename . '_' . $uid);
	}

	/**
	 * Generate an universally unique identifier (UUID) according to RFC 4122 v5.
	 * @param string $namespace
	 * @param string $name
	 * @author ranskills.com
	 * @return string The unversally unique id
	 * @throws InvalidArgumentException Throws an invalid argument exception, if the given namespace is not an uuid
	 */
	static public function generateUUIDv5($namespace, $name) {
		// Get hexadecimal components of namespace
		$nhex = str_replace(array('-', '{', '}'), '', $namespace);
		// Binary Value
		$nstr = '';
		// Convert Namespace UUID to bits
		for ($i = 0;
			 $i < strlen($nhex);
			 $i += 2) {
			$nstr .= chr(hexdec($nhex[$i] . $nhex[$i + 1]));
		}
		// Calculate hash value
		$hash = sha1($nstr . $name);
		return sprintf('%08s-%04s-%04x-%04x-%12s',
			// 32 bits for "time_low"
			substr($hash, 0, 8),
			// 16 bits for "time_mid"
			substr($hash, 8, 4),
			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 5
				(hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000,
			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
				(hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
			// 48 bits for "node"
			substr($hash, 20, 12)
		);
	}

}