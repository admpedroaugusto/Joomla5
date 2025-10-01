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
 * @created Mon, Jun 18, 2018 by Radek Suski
 */
//declare( strict_types=1 );

namespace Sobi\Utils;


/**
 * Class Encryption
 * @package Sobi\Utils
 */
class Encryption
{

	/**
	 * @param string $data
	 * @param string $key
	 *
	 * @return string
	 */
	public static function Encrypt( string $data, string $key ): string
	{
		$cipher = 'aes-128-gcm';
		if ( !( in_array( $cipher, openssl_get_cipher_methods() ) ) ) {
			$cipher = 'AES-128-CBC';
		}
		$ivLength = openssl_cipher_iv_length( $cipher );
		$iv = openssl_random_pseudo_bytes( $ivLength );
		$data = openssl_encrypt( $data, $cipher, $key, 0, $iv, $tag );
		$r = [ 'data' => base64_encode( $data ), 'cipher' => $cipher, 'iv' => base64_encode( $iv ), 'tag' => base64_encode( $tag ) ];
		return base64_encode( json_encode( $r ) );
	}

	/**
	 * @param string $input
	 * @param string $key
	 *
	 * @return false|string
	 */
	public static function Decrypt( string $input, string $key )
	{
		$data = json_decode( base64_decode( $input ), true );
		if ( is_array( $data ) && isset( $data[ 'cipher' ] ) ) {
			return openssl_decrypt( base64_decode( $data[ 'data' ] ), $data[ 'cipher' ], $key, 0, base64_decode( $data[ 'iv' ] ), base64_decode( $data[ 'tag' ] ) );
		}
		else {
			return $input;
		}
	}
}
