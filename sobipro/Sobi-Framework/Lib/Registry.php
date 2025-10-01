<?php
/**
 * @package: Sobi Framework
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2021 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created Wed, Jan 27, 2021 11:43:11 by Radek Suski
 * @modified 07 September 2021 by Radek Suski
 */

//declare( strict_types=1 );
namespace Sobi\Lib;
defined( 'SOBI' ) || exit( 'Restricted access' );

/**
 * Class Registry
 * @package Sobi\Lib
 */
class Registry
{
	use Instance;

	/**
	 * @var array[]
	 */
	protected $store = [ [] ];

	/**
	 * Stores variable.
	 *
	 * @param string $label
	 * @param mixed $object
	 *
	 * @return Registry
	 */
	public function & set( string $label, &$object ): Registry
	{
		$this->store[ 0 ][ $label ] =& $object;

		return $this;
	}

	/**
	 * Deleting stored variable.
	 *
	 * @param string $label
	 */
	public function _unset( string $label )
	{
		if ( isset( $this->store[ 0 ][ $label ] ) ) {
			unset( $this->store[ 0 ][ $label ] );
		}
		else {
			// @TODO
			//Sobi::Error( 'registry', SPLang::e( 'ENTRY_DOES_NOT_EXIST', $label ), SPC::NOTICE, 0, __LINE__, __FILE__ );
		}
	}

	/**
	 * Returns copy of stored object
	 *
	 * @param string $label
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function get( string $label, $default = null )
	{
		if ( strstr( $label, '.' ) ) {
			return $this->parseVal( $label );
		}
		else {
			return $this->store[ 0 ][ $label ] ?? $default;
		}
	}

	/**
	 * Returns reference to a stored object
	 *
	 * @param string $label
	 *
	 * @return mixed
	 */
	public function & _get( string $label )
	{
		return $this->store[ 0 ][ $label ];
	}

	/**
	 * Checks if variable is already stored.
	 *
	 * @param string $label
	 *
	 * @return bool
	 */
	public function _isset( string $label ): bool
	{
		return isset( $this->store[ 0 ][ $label ] );
	}

	/**
	 * Restores saved registry.
	 */
	public function & restore(): Registry
	{
		array_shift( $this->store );

		return $this;
	}

	/**
	 * @param $label
	 * @param null $default
	 *
	 * @return mixed
	 */
	protected function parseVal( $label, $default = null )
	{
		$label = explode( '.', $label );
		$var =& $this->store[ 0 ];
		foreach ( $label as $part ) {
			if ( isset( $var[ $part ] ) ) {
				$var =& $var[ $part ];
			}
			else {
				return $default;
			}
		}

		return $var;
	}

	/**
	 * saving copy of current registry state
	 *
	 */
	public function & save(): Registry
	{
		array_unshift( $this->store, [] );
		if ( !count( $this->store ) ) {
			// @TODO
			//Sobi::Error( 'registry', SPLang::e( 'Registry lost' ), SPC::NOTICE, 0, __LINE__, __CLASS__ );
		}

		return $this;
	}
}
