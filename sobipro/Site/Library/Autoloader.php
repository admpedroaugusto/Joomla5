<?php
/**
 * @package: SobiPro Library
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
 * @created 25.04.21 by Radek Suski
 * @modified 11 February 2022 by Sigrid Suski
 */

declare( strict_types=1 );

namespace SobiPro;

use Sobi\Lib\Instance;

/**
 *
 */
class Autoloader
{
	use Instance;

	/**
	 * @return Autoloader
	 */
	public function & unregister(): Autoloader
	{
		\spl_autoload_unregister( [ $this, 'load' ] );

		return $this;
	}

	/**
	 * @return Autoloader
	 */
	public function & register(): Autoloader
	{
		\spl_autoload_register( [ $this, 'load' ], true );

		return $this;
	}

	/**
	 * @param $class
	 *
	 * @throws \Exception
	 */
	protected function load( $class )
	{
		$path = explode( '\\', $class );
		if ( $path[ 0 ] == 'SobiPro' ) {
			unset( $path[ 0 ] );
			$path = implode( '/', $path );
			if ( file_exists( __DIR__ . '/' . $path . '.php' ) ) {
				/** @noinspection PhpIncludeInspection */
				include_once __DIR__ . '/' . $path . '.php';
			}
			else {
				throw new \Exception( "Can't find class $class definition" );
			}
		}
	}
}
