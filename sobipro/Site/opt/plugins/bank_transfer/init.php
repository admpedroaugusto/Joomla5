<?php
/**
 * @package SobiPro multi-directory component with content construction support
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2023 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/GPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See http://www.gnu.org/licenses/gpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
 *
 * @created 27-Nov-2009 by Radek Suski
 * @modified 02 May 2023 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

use Sobi\Utils\StringUtils;

/**
 * Class SPPBankTransfer.
 */
class SPPBankTransfer extends SPPlugin
{
	use \SobiPro\Helpers\MenuTrait;

	private static array $methods = [ 'CreateAdmMenu', 'PaymentMethodView', 'AppPaymentMessageSend' ];

	/**
	 * @param $action
	 *
	 * @return bool
	 */
	public function provide( $action )
	{
		return in_array( $action, self::$methods );
	}

	/**
	 * @param $menu
	 *
	 * @return void
	 */
	public function CreateAdmMenu( &$menu )
	{
		$this->updateSectionAppMenu( $menu, 'bank_transfer', Sobi::Txt( 'APP.BANK_TRANSFER' ) );
	}

	/**
	 * @param $methods
	 * @param $entry
	 * @param $payment
	 *
	 * @return void
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function AppPaymentMessageSend( &$methods, $entry, &$payment )
	{
		$this->PaymentMethodView( $methods, $entry, $payment );
	}

	/**
	 * This function has to add own string into the given array
	 * Basically: $methods[ $this->id ] = "Some String To Output";
	 * Optionally the value can be also SobiPro Arr2XML array.
	 * Check the documentation for more information
	 *
	 * @param $methods
	 * @param $entry
	 * @param $payment
	 *
	 * @return void
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function PaymentMethodView( &$methods, $entry, &$payment )
	{
		$bankdata = SPLang::getValue( 'bankdata', 'plugin', Sobi::Section() );
		$bankdata = SPLang::replacePlaceHolders( $bankdata, [ 'entry' => $entry ] );
		$methods[ $this->id ] = [
			'content' => StringUtils::Clean( $bankdata ),
			'title'   => Sobi::Txt( 'APP.PBT.PAY_TITLE' ),
		];
	}
}
