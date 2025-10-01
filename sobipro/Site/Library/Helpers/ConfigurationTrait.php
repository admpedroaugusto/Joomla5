<?php
/**
 * @package SobiPro Library
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2023 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 15 July 2021 by Sigrid Suski
 * @modified 02 May 2023 by Sigrid Suski
 */

namespace SobiPro\Helpers;

use Sobi;
use Sobi\C;

/**
 * Trait ConfigurationTrait
 * @package SobiPro\Helpers
 */
trait ConfigurationTrait
{
	/**
	 * Converts the request data to database ready values.
	 *
	 * @param array $data
	 *
	 * @return array
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function prepareConfiguration( array $data ): array
	{
		$fields = [];
		$section = false;

		foreach ( $data as $k => $v ) {
			if ( is_string( $v ) ) {
				$v = htmlspecialchars_decode( $v );
			}
			$k = str_replace( 'spcfg_', C::ES, $k );
			$s = explode( '.', $k );
			$s = $s[ 0 ];
			if ( !( isset( $fields[ $s ] ) ) ) {
				$fields[ $s ] = [];
			}
			$k = str_replace( "$s.", C::ES, $k );
			$c = explode( '_', $k );
			if ( $c[ count( $c ) - 1 ] == 'array' && !is_array( $v ) ) {
				if ( !( strstr( $v, '|' ) ) ) {
					$v = explode( ',', $v );
				}
				else {
					$v = explode( '|', $v );
				}
			}
			$fields[ $s ][ $k ] = $v;
			if ( preg_match( '/^section.*/', $k ) ) {
				$section = true;
			}
		}

		$values = [];
		if ( count( $fields ) ) {
			foreach ( $fields as $sec => $keys ) {
				if ( count( $keys ) ) {
					foreach ( $keys as $k => $v ) {
						$values[] = [ 'sKey' => $k, 'sValue' => $v, 'section' => Sobi::Section(), 'critical' => 0, 'cSection' => $sec ];
					}
				}
			}
		}

		return [ $values, $section ];
	}
}