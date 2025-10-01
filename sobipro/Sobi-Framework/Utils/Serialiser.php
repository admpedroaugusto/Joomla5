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
 * @created Thu, Dec 1, 2016 by Radek Suski
 * @modified 19 May 2021 by Sigrid Suski
 */
//declare( strict_types=1 );

namespace Sobi\Utils;
defined( 'SOBI' ) || exit( 'Restricted access' );

use Sobi\{
	C,
	Framework,
	Error\Exception
};

/**
 * Class Serialiser
 * @package Sobi\Utils
 */
class Serialiser
{
	/**
	 * @param string $var
	 * @param string $name
	 *
	 * @return mixed
	 * @throws \Sobi\Error\Exception
	 */
	public static function Unserialise( string $var, string $name = C::ES )
	{
		$r = null;
		if ( is_string( $var ) && strlen( $var ) > 2 ) {
			if ( ( $var2 = base64_decode( $var, true ) ) ) {
				if ( function_exists( 'gzinflate' ) ) {
					if ( ( $r = @gzinflate( $var2 ) ) ) {
						if ( !$r = @unserialize( $r ) ) {
							throw new Exception( sprintf( 'Cannot unserialize compressed variable %s', $name ) );
						}
					}
					else {
						if ( !( $r = @unserialize( $var2 ) ) ) {
							throw new Exception( sprintf( 'Cannot unserialize raw (?) encoded variable %s', $name ) );
						}
					}
				}
				else {
					if ( !( $r = @unserialize( $var2 ) ) ) {
						throw new Exception( sprintf( 'Cannot unserialize raw encoded variable %s', $name ) );
					}
				}
			}
			else {
				if ( !( $r = @unserialize( $var ) ) ) {
					throw new Exception( sprintf( 'Cannot unserialize raw variable %s', $name ) );
				}
			}
		}
		return $r;
	}

	/**
	 * @param mixed $var
	 *
	 * @return string
	 */
	public static function Serialise( $var ): string
	{
		if ( !( is_string( $var ) ) && ( is_array( $var ) && count( $var ) ) || is_object( $var ) ) {
			$var = serialize( $var );
		}
		if ( is_string( $var ) && function_exists( 'gzdeflate' ) && ( strlen( $var ) > 500 ) ) {
			$var = gzdeflate( $var, 9 );
		}
		if ( is_string( $var ) && strlen( $var ) > 2 ) {
			$var = base64_encode( $var );
		}
		return is_string( $var ) ? $var : C::ES;
	}


	/**
	 * @param string $data
	 * @param bool $force
	 *
	 * @return array|mixed
	 * @throws \Sobi\Error\Exception
	 */
	public static function StructuralData( string $data, bool $force = false )
	{
		if ( is_string( $data ) && strstr( $data, '://' ) ) {
			$struct = explode( '://', $data );
			switch ( $struct[ 0 ] ) {
				case 'json':
					if ( strstr( $struct[ 1 ], "':" ) || strstr( $struct[ 1 ], "{'" ) || strstr( $struct[ 1 ], "['" ) ) {
						$struct[ 1 ] = str_replace( "'", '"', $struct[ 1 ] );
					}
					$data = json_decode( $struct[ 1 ], true );
					break;
				case 'serialized':
					if ( strstr( $struct[ 1 ], "':" ) || strstr( $struct[ 1 ], ":'" ) || strstr( $struct[ 1 ], "['" ) ) {
						$struct[ 1 ] = str_replace( "'", '"', $struct[ 1 ] );
					}
					$data = unserialize( $struct[ 1 ] );
					break;
				case 'csv':
					if ( function_exists( 'str_getcsv' ) ) {
						$data = str_getcsv( $struct[ 1 ] );
					}
					break;
				case 'encrypted':
					$data = Encryption::Decrypt( $struct[ 1 ], Framework::Cfg( 'encryption.key' ) );
					break;
			}
		}
		elseif ( is_string( $data ) && $force ) {
			if ( strstr( $data, '|' ) ) {
				$data = explode( '|', $data );
			}
			elseif ( strstr( $data, ',' ) ) {
				$data = explode( ',', $data );
			}
			elseif ( strstr( $data, ';' ) ) {
				$data = explode( ';', $data );
			}
			else {
				$data = [ $data ];
			}
		}
		return $data;
	}

	/**
	 * @param $var
	 * @param string $name
	 *
	 * @return mixed|null
	 * @throws \Sobi\Error\Exception
	 */
	public static function Unserialize( $var, string $name = C::ES )
	{
		return self::Unserialise( $var, $name );
	}

	/**
	 * @param $var
	 *
	 * @return string|null
	 */
	public static function Serialize( $var ): ?string
	{
		return self::Serialise( $var );
	}
}
