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
 * Hooks for TYPO3 TCEMain.
 *
 * @author Thomas Maroschik <tmaroschik@dfau.de>
 */
class DataHandlerHook {

	/**
	 * Commit the changes to the identity map after all database operations
	 *
	 * @param $parent
	 */
	public function processDatamap_afterAllOperations() {
		/** @var $identityMap \Maroschik\Identity\IdentityMap */
		$identityMap = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Maroschik\Identity\IdentityMap');
		$identityMap->commit();
	}

}