<?php
/**
 * @package: SobiPro Library

 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET

 * @copyright Copyright (C) 2006 - 2015 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See http://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

/**
 * @author Radek Suski
 * @version 1.0
 * @created 21-Jan-2009 1:35:29 PM
 * @deprecated 2.0
 */
class SPData_String
{
	/**
	 * @var string
	 */
	protected $string = "";

	/**
	 * @return void
	 */
	public function isEmpty()
	{
	}

	/**
	 * @param $string
	 */
	public function __construct( $string )
	{
		$this->string = $string;
	}

	/**
	 * @return $this
	 * @throws \SPException
	 */
	public function & toLower()
	{
		if ( $this->isUnicode() ) {
			if ( function_exists( 'mb_strtolower' ) ) {
				self::_setUnicode();
				$this->string = mb_strtolower( $this->string );
			}
			else {
				Sobi::Error( 'String', 'String is a non-latin but we don\'t have unicode handler' );
			}
		}

		return $this;
	}

	/**
	 * @return void
	 */
	private static function _setUnicode()
	{
		static $set = false;
		if ( !( $set ) ) {
			mb_internal_encoding( 'UTF-8' );
			$set = true;
		}
	}

	/**
	 * @return $this
	 * @throws \SPException
	 */
	public function & toUpper()
	{
		if ( $this->isUnicode() ) {
			if ( function_exists( 'mb_strtoupper' ) ) {
				self::_setUnicode();
				$this->string = mb_strtoupper( $this->string );
			}
			else {
				Sobi::Error( 'String', 'String is a non-latin but we don\'t have unicode handler!' );
			}
		}

		return $this;
	}

	/**
	 * @param $method
	 * @param $params
	 *
	 * @return $this
	 * @throws \SPException
	 */
	public function __call( $method, $params )
	{
		if ( function_exists( $method ) ) {
			$this->string = $method( $this->string );
		}
		else {
			Sobi::Error( 'String', "Function $method does not exist!", SPC::WARNING );
		}

		return $this;
	}

	/**
	 * @return bool
	 */
	protected function isUnicode()
	{
		return !preg_match( '/^[\x20-\x7f]*$/D', $this->string );
	}

	/**
	 * @return string
	 */
	public function get()
	{
		return $this->string;
	}
}
