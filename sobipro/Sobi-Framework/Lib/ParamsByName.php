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
 * @created Mon, Dec 21, 2020 by Radek Suski
 */
//declare( strict_types=1 );

namespace Sobi\Lib;

use ReflectionMethod;

defined( 'SOBI' ) || exit( 'Restricted access' );

trait ParamsByName
{
	/**
	 * @param string $called
	 * @param array $args
	 *
	 * @return mixed
	 * @throws \ReflectionException
	 */
	public function & __invoke( string $called, array $args )
	{
		$method = new ReflectionMethod( $this, $called );
		$params = $method->getParameters();
		$argsProcessed = [];
		if ( count( $params ) ) {
			foreach ( $params as $key => $param ) {
				if ( array_key_exists( $param->name, $args ) ) {
					if ( $param->isPassedByReference() ) {
						$argsProcessed[] = &$args[ $param->name ];
					}
					else {
						$argsProcessed[] = $args[ $param->name ];
					}
				}
				else {
					$argsProcessed[] = $param->getDefaultValue();
				}
			}
		}
		$return = call_user_func_array( [ $this, $called ], $argsProcessed );

		return $return;
	}
}
