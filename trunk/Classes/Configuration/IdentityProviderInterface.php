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
 * An interface that supports the configuration of ext:identity by delivering some
 * constants, that can be used in configuration arrays as keys.
 *
 * @author Thomas Maroschik <tmaroschik@dfau.de>
 * @api
 * @package TYPO3
 * @subpackage identity
 */
interface Tx_Identity_Configuration_IdentityProviderInterface {

	/**
	 * A configuration key that can be used to define table specific identity providers
	 *
	 * @api
	 */
	const KEY							= 'identityProvider';

	/**
	 * A configuration key for the list of identity providers
	 *
	 * @api
	 */
	const PROVIDERS_LIST				= 'identityProviders';

	/**
	 * A configuration key for the default identity provider
	 *
	 * @api
	 */
	const DEFAULT_PROVIDER				= 'defaultProvider';

	/**
	 * A configuration key for the field name of an identity provider
	 *
	 * @api
	 */
	const IDENTITY_FIELD				= 'identityField';

	/**
	 * A configuration key for the sql create clause for identity fields of an identity provider
	 *
	 * @api
	 */
	const IDENTITY_FIELD_CREATE_CLAUSE	= 'identityFieldCreateClause';

	/**
	 * A configuration key for the classname of an identity provider
	 *
	 * @api
	 */
	const PROVIDER_CLASS				= 'providerClass';

}