<?php
/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A utility class for various algorithms.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Uuid_Utility_Algorithms {
	
	const TYPO3ORG_UUID = 'c4e6860b-3993-54a9-9c8b-f9bf558b0a77';

	/**
	 * Generates a universally unique identifier (UUID) according to RFC 4122 v4.
	 * The algorithm used here, might not be completely random.
	 *
	 * @return string The universally unique id
	 * @author Unkown
	 */
	static public function generateUUID() {
		return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
			mt_rand( 0, 0x0fff ) | 0x4000,
			mt_rand( 0, 0x3fff ) | 0x8000,
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ) );
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
        self::validateUUID($namespace);

        // Get hexadecimal components of namespace
        $nhex = str_replace(array('-','{','}'), '', $namespace);

        // Binary Value
        $nstr = '';

        // Convert Namespace UUID to bits
        for($i = 0;
        $i < strlen($nhex);
        $i+=2) {
            $nstr .= chr(hexdec($nhex[$i].$nhex[$i+1]));
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
    
	/**
	 * Checks the given UUID. If it does not have a valid format an
	 * exception is thrown.
	 *
	 * @param	string	UUID.
	 * @return	bool
	 * @throws	InvalidArgumentException	Throws an exception if the given uuid is not valid
	 */
	static public function validateUUID($uuid) {  
        if (!strlen($uuid)) { 
            throw new InvalidArgumentException('Empty UUID given.', 1299013185); 
            return false; 
        } 
        if (function_exists('uuid_is_valid') && !uuid_is_valid($uuid)) { 
            throw new InvalidArgumentException('Given UUID does not match the UUID pattern.', 1299013329); 
            return false; 
        } 
        if (strlen($uuid) !== 36) { 
            throw new InvalidArgumentException('Lenghth of UUID has to be 36 characters.', 1299013335); 
            return false; 
        } 
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i'; 
        if (!preg_match($pattern, $uuid)) { 
            throw new InvalidArgumentException('Given UUID does not match the UUID pattern.', 1299013339); 
            return false; 
        } 
        return true; 
	}

	/**
	 * Returns a string of random bytes.
	 *
	 * @param integer $count Number of bytes to generate
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	static public function generateRandomBytes($count) {
		$bytes = '';

		if (file_exists('/dev/urandom')) {
			$bytes = file_get_contents('/dev/urandom', NULL, NULL, NULL, $count);
		}

			// urandom did not deliver (enough) data
		if (strlen($bytes) < $count) {
			$randomState = microtime() . getmypid();
			while (strlen($bytes) < $count) {
				$randomState = md5(microtime() . mt_rand() . $randomState);
				$bytes .= md5(mt_rand() . $randomState, TRUE);
			}
			$bytes = substr($bytes, -$count, $count);
		}
		return $bytes;
	}

}
?>