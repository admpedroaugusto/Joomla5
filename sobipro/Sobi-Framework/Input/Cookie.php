<?php
/**
 * @package: Sobi Framework
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET
 * @copyright Copyright (C) 2006 - 2021 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See http://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * @created Tue, Feb 23, 2021 13:27:36 by Radek Suski
 * @modified
 */
//declare( strict_types=1 );

namespace Sobi\Input;

use Sobi\{C, Lib\Factory};

abstract class Cookie
{
	const prefix = 'SPro_';

	/**
	 * @param string $name - The name of the cookie.
	 * @param string $value - The value of the cookie
	 * @param int $expire - The time the cookie expires. This is a Unix timestamp so is in number of seconds since the epoch
	 * @param bool $httponly - When true the cookie will be made accessible only through the HTTP protocol.
	 * @param bool $secure - Indicates that the cookie should only be transmitted over a secure HTTPS connection from the client
	 * @param string $path - The path on the server in which the cookie will be available on
	 * @param string $domain - The domain that the cookie is available
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public static function Set( string $name, string $value, int $expire = 0, bool $httponly = false, bool $secure = false, string $path = '/', string $domain = C::ES ): bool
	{
		$name = self::prefix . $name;
		$expire = ( $expire == 0 ) ? $expire : time() + $expire;

		return Factory::Application()->setCookie( $name, $value, $expire, $httponly, $secure, $path, $domain ) && Input::String( $name, 'cookie' );
	}

	/**
	 * Delete cookie
	 *
	 * @param string $name - The name of the cookie.
	 *
	 * @return void
	 * @throws \Exception
	 */
	public static function Delete( string $name )
	{
		$name = self::prefix . $name;
		Factory::Application()->setCookie( $name, C::ES, ( time() - 36000 ) );
	}

	/**
	 * convert hours to minutes
	 *
	 * @param int $time number of minutes
	 *
	 * @return int
	 */
	public static function Minutes( int $time )
	{
		return $time * 60;
	}

	/**
	 * convert hours to seconds
	 *
	 * @param int $time number of hours
	 *
	 * @return int
	 */
	public static function Hours( int $time )
	{
		return self::Minutes( $time ) * 60;
	}

	/**
	 * convert days to seconds
	 *
	 * @param int $time number of days
	 *
	 * @return int
	 */
	public static function Days( int $time )
	{
		return self::Hours( $time ) * 24;
	}
}
