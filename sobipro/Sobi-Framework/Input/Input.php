<?php
/**
 * @package Sobi Framework
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006-2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according to section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created Thu, Dec 1, 2016 by Radek Suski
 * @modified 05 November 2024 by Sigrid Suski
 */

//declare( strict_types=1 );

namespace Sobi\Input;

defined( 'SOBI' ) || exit( 'Restricted access' );

use AthosHun\HTMLFilter\{Configuration, HTMLFilter};
use Sobi\{
	C,
	Framework,
	Error\Exception,
	FileSystem\FileSystem,
	Utils\Serialiser
};

/**
 * @method      integer  Sid()       public static Sid( $request = 'request', $default = 0 )
 * @method      integer  Cid()       public static Cid( $request = 'request', $default = 0 )
 * @method      integer  Pid()       public static Cid( $request = 'request', $default = 0 )
 * @method      integer  Rid()       public static Rid( $request = 'request', $default = 0 )
 * @method      integer  Eid()       public static Eid( $request = 'request', $default = 0 )
 * @method      integer  Fid()       public static Fid( $request = 'request', $default = 0 )
 */
abstract class Input
{
	/**
	 * @param string $name
	 * @param string $request
	 * @param int $default
	 * @param false $noZero
	 *
	 * @return int
	 */
	public static function Int( string $name, string $request = 'request', int $default = 0, bool $noZero = false ): int
	{
		if ( isset( $_REQUEST[ $name ] ) && !( is_array( $_REQUEST[ $name ] ) ) ) {
//			$int = (int) Factory::getApplication()->getInput()->{$request}->getInt( $name, $default );
			$int = (int) Request::Instance()->{$request}->getInt( $name, $default );
			$int = (int) Request::Instance()->{$request}->getString( $name, $default );

			return (int) $noZero && !( $int ) ? $default : $int;
		}
		else {
			return $default;
		}
	}

	/**
	 * @param string $name
	 * @param array $default
	 * @param string $request
	 *
	 *
	 * @return array
	 */
	public static function Arr( string $name, string $request = 'request', array $default = [] ): array
	{
		/** No need for cleaning - Joomla is doing it already */
		$arr = Request::Instance()->{$request}->get( $name, $default, 'array' );

		/* if we use the 'array' filter Joomla! will automatically convert it into an array,
		 * so we need to check the original request for its state */
		return isset( $_REQUEST[ $name ] ) && is_array( $_REQUEST[ $name ] ) ? $arr : $default;
	}


	/**
	 * Search for indexes within the requested method.
	 *
	 * @param string $search variable name
	 * @param string $request request method
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function Search( string $search, string $request = 'request' ): array
	{
		$var = [];
		$input = 'request';
		switch ( strtolower( $request ) ) {
			case 'post':
				$input = 'post';
				$request = $_POST;
				break;
			case 'get':
				$request = $_GET;
				$input = 'get';
				break;
			default:
				$request = $_REQUEST;
				break;
		}
		if ( count( $request ) ) {
			foreach ( $request as $name => $value ) {
				if ( strstr( $name, $search ) ) {
					switch ( gettype( $value ) ) {
						case 'boolean':
							$var[ $name ] = self::Bool( $name, $input );
							break;
						case 'integer':
							$var[ $name ] = self::Int( $name, $input );
							break;
						case 'double':
							$var[ $name ] = self::Double( $name, $input );
							break;
						case 'string':
							$var[ $name ] = self::Html( $name, $input );
							break;
						case 'array':
							$var[ $name ] = self::Arr( $name, $input );
							break;
					}
					//break;  // nur ein Vorkommen suchen??
				}
			}
		}

		return $var;
	}

	/**
	 * @param string $name
	 * @param string $default
	 * @param string $request
	 *
	 * @return string
	 */
	public static function Base64( string $name, string $request = 'request', string $default = C::ES ): string
	{
		return preg_replace( '/[^A-Za-z0-9\/+=]/', C::ES, Request::Instance()->{$request}->getString( $name, $default ) );
	}

	/**
	 * @param string $name
	 * @param bool $default
	 * @param string $request
	 *
	 * @return bool
	 */
	public static function Bool( string $name, string $request = 'request', bool $default = false ): bool
	{
		return (bool) Request::Instance()->{$request}->getBool( $name, $default );
	}

	/**
	 * @param string $name
	 * @param string $default
	 * @param string $request
	 *
	 * @return string
	 */
	public static function Cmd( string $name, string $request = 'request', string $default = C::ES ): string
	{
		return preg_replace( '/[^a-zA-Z0-9\p{L}.\-_:]/u', C::ES, Request::Instance()->{$request}->getString( $name, $default ) );
	}

	/**
	 * @param string $name
	 * @param float $default
	 * @param string $request
	 *
	 * @return float
	 */
	public static function Double( string $name, string $request = 'request', float $default = 0.0 ): float
	{
		return (float) Request::Instance()->{$request}->getFloat( $name, $default );
	}

	/**
	 * @param string $name
	 * @param float $default
	 * @param string $request
	 *
	 * @return float
	 */
	public static function Float( string $name, string $request = 'request', float $default = 0.0 ): float
	{
		return (float) Request::Instance()->{$request}->getFloat( $name, $default );
	}

	/**
	 * @param $name
	 * @param string $request
	 * @param string $default
	 *
	 * @return string|string[]
	 * @throws Exception|\Exception
	 */
	public static function Html( $name, string $request = 'request', string $default = C::ES )
	{
		static $config = null;
		static $filter = null;
		if ( !$config ) {
			$tags = Framework::Cfg( 'html.allowed_tags_array', [] );
			$attributes = Framework::Cfg( 'html.allowed_attributes_array', [] );

			$config = new Configuration();
			$filter = new HTMLFilter();

			if ( is_array( $tags ) && count( $tags ) ) {
				foreach ( $tags as $tag ) {
					$config->allowTag( $tag );
					if ( is_array( $attributes ) && count( $attributes ) ) {
						foreach ( $attributes as $attribute ) {
							$config->allowAttribute( $tag, $attribute );
						}
					}
				}
			}
		}
		$html = Request::Instance()->{$request}->getRaw( $name, $default );

		// @todo still need the line below ??
		//$html = str_replace( '&#13;', "\n", $filter->filter( $config, $html ) );
		$html = str_replace( '%7Bentry.url%7D', '{entry.url}', $filter->filter( $config, $html ) );

		return $html;
	}

	/**
	 * @param string $name
	 * @param string $default
	 * @param string $request
	 *
	 * @return string
	 */
	public static function String( string $name, string $request = 'request', string $default = C::ES ): string
	{
		$value = Request::Instance()->{$request}->getString( $name, $default );

		if ( version_compare( PHP_VERSION, '7.4.0' ) >= 0 ) {
			return filter_var( $value, FILTER_SANITIZE_ADD_SLASHES );
		}
		else {
			return filter_var( $value, FILTER_SANITIZE_MAGIC_QUOTES );
		}
	}

	/**
	 * @param string $name
	 * @param string $default
	 * @param string $request
	 *
	 * @return string
	 */
	public static function Ip4( string $name = 'REMOTE_ADDR', string $request = 'server', string $default = C::ES ): string
	{
		return preg_replace( '/[^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}]/', C::ES, Request::Instance()->{$request}->getString( $name, $default ) );
	}

	/**
	 * @return string
	 */
	public static function Now(): string
	{
		return gmdate( 'Y-m-d H:i:s' );
	}

	/**
	 * @param string $name
	 * @param mixed $defaultHi Tim,
	 * @param string $request
	 *
	 * @return mixed
	 */
	public static function Raw( string $name, string $request = 'request', $default = null )
	{
		switch ( $request ) {
			case 'post':
				$r = $_POST[ $name ];
				break;
			case 'get':
				$r = $_GET[ $name ];
				break;
			case 'server':
				$r = $_SERVER[ $name ];
				break;
			default:
				$r = $_REQUEST[ $name ];
				break;
		}

		return $r ?? $default;
	}

	/**
	 * @param string $request
	 *
	 * @return string
	 */
	public static function Task( string $request = 'request' ): string
	{
		return self::Cmd( 'task', $request );
	}

	/**
	 * @param string $name
	 * @param array $arguments
	 *
	 * @return int
	 * @throws Exception
	 */
	public static function __callStatic( string $name, array $arguments = [] ): int
	{
		if ( strstr( $name, 'id' ) ) {
			if ( !( count( $arguments ) ) ) {
				$arguments = [ 0 => 'request', 1 => 0 ];
			}

			return self::Int( strtolower( $name ), $arguments[ 0 ], $arguments[ 1 ] );
		}
		else {
			throw new Exception( "Call to undefined method {$name} of class " . __CLASS__ );
		}
	}

	/**
	 * Returns double value of requested variable and checks for a valid timestamp
	 * Sun, Jan 5, 2014 11:27:04 changed to double because of 32 bit systems (seriously?!)
	 *
	 * @param string $name variable name
	 * @param float $default default value
	 * @param string $method request method
	 *
	 * @return int
	 */
	public static function Timestamp( string $name, string $method = 'request', float $default = 0.0 )
	{
		$val = self::Double( $name, $method, $default );

		// JavaScript conversion
		return $val > 10000000000 ? $val / 1000 : $val;
	}


	/**
	 * @param string $name
	 * @param string $default
	 * @param string $request
	 *
	 * @return string
	 */
	public static function Word( string $name, string $request = 'request', string $default = C::ES ): string
	{
		return preg_replace( '/[^a-zA-Z0-9\p{L}_\-\s]/u', C::ES, Request::Instance()->{$request}->getString( $name, $default ) );
	}

	/**
	 * @param string $name
	 * @param $value
	 * @param string $request
	 */
	public static function Set( string $name, $value, string $request = 'request' )
	{
		Request::Instance()->{$request}->set( $name, $value );
		switch ( $request ) {
			case 'post':
				$_POST[ $name ] = $value;
				break;
			case 'get':
				$_GET[ $name ] = $value;
				break;
			case 'files':
				$_FILES[ $name ] = $value;
				break;
		}
		$_REQUEST[ $name ] = $value;
	}

	/**
	 * @param string $name variable name
	 * @param string $property
	 * @param string $request request method
	 *
	 * @return array|mixed|null
	 * @throws Exception
	 */
	public static function File( string $name, string $property = C::ES, string $request = 'files' )
	{
		$data = C::ES;
		if ( $request == 'files' ) {
			/** check for Ajax uploaded files */
			$check = self::String( $name );
			if ( $check ) {
				$secret = md5( Framework::Cfg( 'secret' ) );
				$fileName = str_replace( 'file://', C::ES, $check );
				$path = Framework::Cfg( 'temp-directory' ) . "/files/{$secret}/{$fileName}";
				if ( file_exists( "{$path}.var" ) ) {
					$cfg = FileSystem::Read( "{$path}.var" );
					$data = Serialiser::Unserialise( $cfg );
					$_FILES[ $name ] = $data;
					Request::Instance()->setRequest( $name, $data );
				}
			}
			else {
				// Thu, Sep 6, 2018 08:58:48
				// can't be used as Joomla applies own security filters and the result is unpredictable
				// yes, it does not work for big files (Sigrid, 9.1.20)
				$data = Request::Instance()->files->get( $name, C::ES );
			}
		}

		return ( $property && isset( $data[ $property ] ) ) ? $data[ $property ] : $data;
	}

	/**
	 * Transform data received via PUT/PATCH/etc to $_REQUEST
	 * So ir can be filtered using Joomla's validation methods.
	 * The method assumes that we are getting all those params as a JSON string
	 */
	public static function TransformToRequest()
	{
		$data = json_decode( file_get_contents( 'php://input' ), true );
		if ( is_array( $data ) && count( $data ) ) {
			foreach ( $data as $index => $value ) {
				self::Set( $index, $value );
			}
		}
	}
}
