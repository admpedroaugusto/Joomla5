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
 * @created Thu, Dec 1, 2016 12:04:19 by Radek Suski
 * @modified 18 July 2022 by Sigrid Suski
 */

//declare( strict_types=1 );

namespace Sobi;

defined( '_JEXEC' ) || exit( 'Restricted access' );
define( 'SOBI', true );

use Joomla\CMS\Factory as JFactory;
use Sobi\{
	Autoloader\Autoloader,
	Error\Exception,
	Input\Input
};

/**
 * Class Framework
 * @package Sobi
 */
abstract class Framework
{
	/** @var array */
	protected static $translator = [];
	/*** @var array */
	protected static $errorTranslator = [];
	/** @var array */
	protected static $config;
	/** @var array */
	protected static $configs;
	/** @var string */
	protected static $extension;

	/**
	 * @param array $callback
	 */
	public static function SetTranslator( array $callback )
	{
		self::$translator = $callback;
	}


	/**
	 * @param array $callback
	 */
	public static function SetErrorTranslator( array $callback )
	{
		self::$errorTranslator = $callback;
	}

	/**
	 * @param string|null $extension
	 *
	 * @throws \Exception
	 */
	public static function Init( ?string $extension = null )
	{
		!$extension ? : self::$extension = $extension;
		defined( 'SOBI_APP' ) || define( 'SOBI_APP', 'Joomla' );
		include_once dirname( __FILE__ ) . '/Autoloader/Autoloader.php';
		Autoloader::getInstance()->register();

		/** Don't ask, just don't ask please!! */
		$input = (array) JFactory::getApplication()->input;
		$prefix = chr( 0 ) . '*' . chr( 0 ) . 'data';
		if ( isset( $input[ $prefix ] ) && count( $input[ $prefix ] ) ) {
			foreach ( $input[ $prefix ] as $variable => $value ) {
				Input::Set( $variable, $value );
			}
		}
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	public static function Txt(): string
	{
		if ( is_array( self::$translator ) && count( self::$translator ) == 2 ) {
			$args = func_get_args();

			return call_user_func_array( self::$translator, $args );
		}
		else {
			throw new Exception( 'Translator has not been set' );
		}
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	public static function Error(): string
	{
		if ( is_array( self::$errorTranslator ) && count( self::$errorTranslator ) == 2 ) {
			$args = func_get_args();

			return call_user_func_array( self::$errorTranslator, $args );
		}
		else {
			throw new Exception( 'Error Translator has not been set' );
		}
	}

	/**
	 * @param string $key
	 * @param null $def
	 * @param string $section
	 *
	 * @return string | array
	 * @throws \Sobi\Error\Exception
	 */
	public static function Cfg( string $key, $def = null, string $section = 'general' )
	{
		if ( is_array( self::$config ) && count( self::$config ) == 2 ) {
			$setting = call_user_func_array( self::$config, [ $key, C::NO_VALUE, $section ] );
			// If the current config handler returns no value go through all handlers until once return something
			if ( $setting == C::NO_VALUE && count( self::$configs ) > 1 ) {
				foreach ( self::$configs as $config ) {
					$setting = call_user_func_array( $config, [ $key, C::NO_VALUE, $section ] );
					if ( $setting != C::NO_VALUE ) {
						break;
					}
				}
			}

			return $setting == C::NO_VALUE ? $def : $setting;
		}
		else {
			throw new Exception( 'Config has not been set' );
		}
	}

	/**
	 * @param array $config
	 */
	public static function SetConfig( array $config )
	{
		self::$configs[] = $config;
		self::$config = end( self::$configs );
	}
}
