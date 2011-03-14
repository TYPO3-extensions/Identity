<?php
/* *
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
class Tx_Uuid_Utility_ExtensionManager {

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	public function displayMessage(&$params, &$tsObj) {
		$out = '';

		if (t3lib_div::int_from_ver(TYPO3_version) < 4003000) {
			// 4.3.0 comes with flashmessages styles. For older versions we include the needed styles here
			$cssPath = $GLOBALS['BACK_PATH'] . t3lib_extMgm::extRelPath('tt_news');
			$out .= '<link rel="stylesheet" type="text/css" href="' . $cssPath . 'compat/flashmessages.css" media="screen" />';
		}

		$out .= '
		<div style="position:absolute;top:10px;right:10px; width:300px;">
			<div class="typo3-message message-information">
   				<div class="message-header">' . $GLOBALS['LANG']->sL('LLL:EXT:uuid/Resources/Private/Language/locallang.xml:updater_header') . '</div>
  				<div class="message-body">
  					' . $GLOBALS['LANG']->sL('LLL:EXT:uuid/Resources/Private/Language/locallang.xml:updater_message') . '<br />
  					<a style="text-decoration:underline;" href="index.php?&amp;id=0&amp;CMD[showExt]=tt_news&amp;SET[singleDetails]=updateModule">
  					' . $GLOBALS['LANG']->sL('LLL:EXT:tt_news/locallang.xml:extmng.updatermsgLink') . '</a>
  				</div>
  			</div>
  		</div>
  		';

		return $out;
	}

}

?>