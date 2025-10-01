<?php
/**
 * @package: Sobi Framework
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2022 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created Fri, Oct 30, 2020 by Radek Suski
 * @modified 03 August 2022 by Sigrid Suski
 */

//declare( strict_types=1 );

namespace Sobi\Utils;

defined( 'SOBI' ) || exit( 'Restricted access' );

use Sobi\{C, Lib\Factory, Error\Exception};


/**
 * Class Type
 * @package Sobi\Utils
 */
abstract class Type
{
	/**
	 * @param $value
	 *
	 * @return array|false|float|int|string
	 */
	public static function Null( $value )
	{
		switch ( gettype( $value ) ) {
			case 'boolean':
			case 'integer':
				return 0;
			case 'double':
				return 0.0;
			case 'string':
				return C::ES;
			case 'array':
				return [];
			default:
				return false;
		}
	}

	/**
	 * @param $type
	 *
	 * @return float|int|string
	 */
	public static function SQLNull( $type )
	{
		switch ( preg_replace( '/\s*\([^)]*\)/', C::ES, $type ) ) {
			case 'int':
			case 'tinyint':
			case 'smallint':
			case 'mediumint':
			case 'bigint':
			case 'timestamp':
				return 0;
			case 'double':
			case 'decimal':
			case 'float':
			case 'real':
				return 0.0;
			case 'datetime':
				return Factory::Db()->getNullDate();
			default:
				return C::ES;
		}
	}

	/**
	 * @param array $array
	 */
	public static function TypecastArray( array &$array )
	{
		if ( count( $array ) ) {
			foreach ( $array as $index => $element ) {
				is_array( $element ) ? self::TypecastArray( $array[ $index ] ) : self::TypecastVariable( $array[ $index ] );
			}
		}
	}

	/**
	 * @param $variable
	 */
	public static function TypecastVariable( &$variable )
	{
		if ( is_string( $variable ) && ctype_digit( trim( $variable ) ) ) {
			$variable = (int) $variable;
		}
		elseif ( is_string( $variable ) && is_numeric( $variable ) && substr_count( trim( $variable ), '.' ) == 1 ) {
			$variable = (float) trim( $variable );
		}
		else {
			switch ( gettype( $variable ) ) {
				case 'boolean':
				case 'bool':
					$variable = (bool) $variable;
					break;
				case 'integer':
					$variable = (int) $variable;
					break;
				case 'string':
					$variable = (string) $variable;
					break;
				case 'double':
				case 'float':
					$variable = (float) $variable;
			}
		}
	}

	/**
	 * @param $variable
	 * @param string $type
	 *
	 * @throws \Exception
	 */
	public static function Cast( &$variable, string $type )
	{
		if ( gettype( $variable ) == 'array' && $type == 'string' ) {
			$variable = implode( ', ', $variable );
		}
		if ( !settype( $variable, $type ) ) {
			throw new Exception( sprintf( 'Cannot cast variable of type "%s" to "%s"', gettype( $variable ), $type ) );
		};
	}
}
