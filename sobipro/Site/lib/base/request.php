<?php
/**
 * @package: SobiPro Library
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
 * See http://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * @created 20-Jul-2008 by Radek Suski
 * @modified 24 May 2023 by Sigrid Suski
 *
 * @deprecated use Sobi\Input\Input
 */

use Sobi\C;
use Sobi\Lib\Factory;

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

/**
 * Class SPRequest
 */
abstract class SPRequest
{
	/**
	 * @var mixed
	 */
	private static $val = null;
	/**
	 * @var string
	 */
	private static $name = null;
	/**
	 * @var mixed
	 */
	private static $default = null;
	/**
	 * @var string
	 */
	private static $method = 'REQUEST';
	/**
	 * @var string
	 */
	private static $request = null;
	/**
	 * @var bool
	 */
	private static $commentsAllowed = true;
	/**
	 * @var Zend_Filter_StripTags
	 */
	private static $filter = null;

	/**
	 * initialising
	 *
	 * @param string $name
	 * @param string $method
	 *
	 * @throws SPException
	 */
	private static function init( $name, $method )
	{
		if ( !( is_string( $method ) ) ) {
			throw new SPException( 'Wrong method given: ' . get_class( $method ) );
		}
		self::$method = strtoupper( $method );
		$name = ( self::$method == 'COOKIE' ) ? 'SPro_' . $name : $name;
		self::$name = $name;

		switch ( self::$method ) {
			case 'GET':
				// it's seems that Joomla! is storing the decoded variables from menu
				// into $_REQUEST instead of $_GET
				self::$request =& $_REQUEST;
//				self::$request =& $_GET;
				break;
			case 'POST':
				self::$request =& $_POST;
				break;
			case 'FILES':
				self::$request =& $_FILES;
				break;
			case 'COOKIE':
				self::$request =& $_COOKIE;
				break;
			case 'ENV':
				self::$request =& $_ENV;
				break;
			case 'SERVER':
				self::$request =& $_SERVER;
				break;
			case 'REQUESTCACHE':
				self::$request =& SPFactory::registry()->_get( 'requestcache' );
				break;
			default:
				self::$request =& $_REQUEST;
				self::$method = 'REQUEST';
				break;
		}
	}

	/**
	 * Sets the tagsAllowed option
	 *
	 * @param array $tags
	 *
	 * @return mixed
	 */
	public static function setTagsAllowed( $tags )
	{
		self::createFilter();

		return self::$filter->setTagsAllowed( $tags );
	}

	/**
	 * Sets the attributesAllowed option
	 *
	 * @param array $attributes
	 *
	 * @return mixed
	 */
	public static function setAttributesAllowed( $attributes )
	{
		self::createFilter();

		return self::$filter->setAttributesAllowed( $attributes );
	}

	/**
	 * Filter variable from request
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public static function filter( $value )
	{
		if ( class_exists( 'SPFactory' ) ) {
			if ( ( SPFactory::user()->isAdmin() ) ) {
				return stripslashes( $value );
			}
		}
		self::createFilter();
		$a = self::$filter->filter( stripslashes( $value ) );

		return $a;
	}

	/**
	 * Reset filter to the default settings for the current section
	 * @return void
	 */
	public static function resetFilter()
	{
		if ( !self::$filter || !( self::$filter instanceof Zend_Filter_StripTags ) ) {
			self::$filter = new Zend_Filter_StripTags();
		}
		self::$filter->setAttributesAllowed( Sobi::Cfg( 'html.allowed_attributes_array' ) );
		self::$filter->setTagsAllowed( Sobi::Cfg( 'html.allowed_tags_array' ) );
	}

	/**
	 *
	 */
	private static function createFilter()
	{
		if ( !self::$filter || !( self::$filter instanceof Zend_Filter_StripTags ) ) {
			self::$filter = new Zend_Filter_StripTags();
		}
	}

	/**
	 * Returns integer value of requested variable
	 *
	 * @param string $name variable name
	 * @param int $default default value
	 * @param string $method request method
	 * @param bool $noZero
	 *
	 * @return int
	 */
	public static function int( $name, $default = 0, $method = 'REQUEST', $noZero = false )
	{
		self::init( $name, $method );
		self::$val = isset( self::$request[ self::$name ] ) ? self::$request[ self::$name ] : $default;
		if ( !is_array( self::$val ) ) {
			self::$val = ( int ) self::$val;
		}
		else {
			self::$val = $default;
		}
		self::$val = $noZero && !( self::$val ) ? $default : self::$val;

		return self::$val;
	}

	/**
	 * Returns double value of requested variable and checks for a valid timestamp
	 * Sun, Jan 5, 2014 11:27:04 changed to double because of 32 bit systems (seriously?!)
	 *
	 * @param string $name variable name
	 * @param int $default default value
	 * @param string $method request method
	 * @param bool $noZero
	 *
	 * @return int
	 */
	public static function timestamp( $name, $default = 0, $method = 'REQUEST', $noZero = false )
	{
		self::$val = self::double( $name, $default, $method, $noZero );

		// JavaScript conversion
		return self::$val > 10000000000 ? self::$val / 1000 : self::$val;
	}

	/**
	 * Set variable
	 *
	 * @param string $name variable name
	 * @param mixed $var value
	 * @param string $method request method
	 */
	public static function set( $name, $var, $method = 'REQUEST' )
	{
		$_REQUEST[ $name ] = $var;
		self::init( $name, $method );
		self::$request[ self::$name ] = $var;
	}

	/**
	 * Returns integer value of the 'sid' variable
	 *
	 * @param string $method request method
	 * @param int $default
	 * @param bool $noZero
	 *
	 * @return int
	 */
	public static function sid( $method = 'REQUEST', $default = 0, $noZero = false )
	{
		return self::int( 'sid', $default, $method, $noZero );
	}

	/**
	 * Returns float value of requested variable
	 *
	 * @param string $name variable name
	 * @param float|int $default default value
	 * @param string $method request method
	 *
	 * @return float
	 */
	public static function float( $name, $default = 0, $method = 'REQUEST' )
	{
		self::init( $name, $method );
		self::$val = self::$request[ self::$name ] ?? $default;
		self::$val = preg_replace( "/[^0-9\.]/", C::ES, self::$val );
		self::$val = ( float ) self::$val;

		return self::$val;
	}

	/**
	 * Returns double value of requested variable
	 *
	 * @param string $name variable name
	 * @param \double|int $default default value
	 * @param string $method request method
	 * @param bool $noZero
	 *
	 * @return double
	 */
	public static function double( $name, $default = 0, $method = 'REQUEST', $noZero = false )
	{
		self::init( $name, $method );
		self::$val = self::$request[ self::$name ] ?? $default;
		self::$val = preg_replace( '/[^0-9\.]/', C::ES, self::$val );
		self::$val = $noZero && !( self::$val ) ? ( double ) $default : ( double ) self::$val;

		return self::$val;
	}

	/**
	 * Search for indexes within the requested method
	 *
	 * @param string $search variable name
	 * @param string $method request method
	 *
	 * @return double
	 */
	public static function search( $search, $method = 'REQUEST' )
	{
		self::init( null, $method );
		self::$val = [];
		if ( count( self::$request ) ) {
			foreach ( self::$request as $name => $value ) {
				if ( strstr( $name, $search ) ) {
					self::$val[ $name ] = $value;
				}
			}
		}

		return self::$val;
	}

	/**
	 * Returns bool value of requested variable
	 *
	 * @param string $name variable name
	 * @param bool $default default value
	 * @param string $method request method
	 *
	 * @return bool
	 */
	public static function bool( $name, $default = false, $method = 'REQUEST' )
	{
		self::init( $name, $method );
		self::$val = self::$request[ self::$name ] ?? $default;
		self::$val = preg_replace( "/[^0-1]/", C::ES, self::$val );
		self::$val = ( bool ) self::$val;

		return self::$val;
	}

	/**
	 * Returns word (alpha numeric) value of requested variable
	 *
	 * @param string $name variable name
	 * @param string $default default value
	 * @param string $method request method
	 *
	 * @return string
	 */
	public static function word( $name, $default = null, $method = 'REQUEST' )
	{
		self::init( $name, $method );
		self::$val = self::$request[ self::$name ] ?? $default;
		self::$val = preg_replace( "/[^a-zA-Z0-9\p{L}\_\-\s]/u", C::ES, self::$val );

		return self::$val;
	}

	/**
	 * Returns commmand of requested variable
	 *
	 * @param string $name variable name
	 * @param string $default default value
	 * @param string $method request method
	 *
	 * @return string
	 */
	public static function cmd( $name, $default = null, $method = 'REQUEST' )
	{
		self::init( $name, $method );
		self::$val = self::$request[ self::$name ] ?? $default;
		self::$val = preg_replace( "/[^a-zA-Z0-9\p{L}\.\-\_\:]/u", C::ES, self::$val );

		return self::$val;
	}

	/**
	 * Returns base64 encoded value of requested variable
	 *
	 * @param string $name variable name
	 * @param string $default default value
	 * @param string $method request method
	 *
	 * @return string
	 */
	public static function base64( $name, $default = null, $method = 'REQUEST' )
	{
		self::init( $name, $method );
		self::$val = self::$request[ self::$name ] ?? $default;
		self::$val = preg_replace( "/[^A-Za-z0-9\/+=]/", C::ES, self::$val );

		return self::$val;
	}

	/**
	 * Returns string value of requested variable
	 *
	 * @param string $name variable name
	 * @param bool $html allow html tags
	 * @param string $default default value
	 * @param string $method request method
	 *
	 * @return string
	 */
	public static function string( $name, $default = C::ES, $html = false, $method = 'REQUEST' )
	{
		self::init( $name, $method );
		self::$val = self::$request[ self::$name ] ?? $default;
		$back = [];
		if ( $html == 1 ) {
			$val = self::$val;
			if ( preg_match_all( '/(<pre((?!>).)*>*)(((?!<\/pre|<pre).)+)\s*<\/pre>/s', $val, $matches ) ) {
				self::createFilter();
				$allowed = self::$filter->getTagsAllowed();
				if ( isset( $allowed[ 'pre' ] ) ) {
					foreach ( $matches[ 3 ] as $i => $pre ) {
						$id = '[%pre%]' . $i . '[%pre%]';
						$back[ $id ] = [ 'content' => $pre, 'tag' => $matches[ 1 ][ $i ] ];
						$val = str_replace( $matches[ 1 ][ $i ] . $pre, $id, $val );
					}
				}
			}
			$val = self::filter( $val );
			$conv = Sobi::Cfg( 'html.pre_to_entities', true );
			if ( count( $back ) ) {
				foreach ( $back as $id => $pre ) {
					if ( $conv ) {
						$pre[ 'content' ] = htmlentities( $pre[ 'content' ] );
					}
					$val = str_replace( $id, $pre[ 'tag' ] . $pre[ 'content' ], $val );
				}
			}
			self::$val = $val;
		}
		elseif ( !$html ) {
			self::$val = strip_tags( self::$val );
		}
		if ( version_compare( PHP_VERSION, '7.4.0' ) >= 0 ) {
			return filter_var( self::$val, FILTER_SANITIZE_ADD_SLASHES );
		}
		else {
			return filter_var( self::$val, FILTER_SANITIZE_MAGIC_QUOTES );
		}
	}

	/**
	 * Returns string value of requested variable
	 *
	 * @param string $name variable name
	 * @param string $default default value
	 * @param string $method request method
	 *
	 * @return string
	 */
	public static function html( $name, $default = null, $method = 'REQUEST' )
	{
		return self::string( $name, $default, true, $method );
	}

	/**
	 * Returns ip value of requested variable
	 *
	 * @param string $name variable name
	 * @param string $default default value
	 * @param string $method request method
	 *
	 * @return string
	 */
	public static function ip( $name, $default = null, $method = 'REQUEST' )
	{
		self::$val = self::string( $name, $default, false, $method );
		self::$val = preg_replace( "/[^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}]/", C::ES, self::$val );

		return self::$val;
	}

	/**
	 * Returns ip value of requested variable.
	 *
	 * @param $name
	 * @param null $default
	 * @param string $method
	 *
	 * @return false|mixed|string|null
	 * @throws \SPException
	 */
	public static function datetime( $name, $default = null, $method = 'REQUEST' )
	{
		$config =& SPFactory::config();
		$config->addIniFile( 'etc.calendar' );
		self::$val = self::string( $name, $default, false, $method );
		if ( self::$val ) {
			self::$val = SPFactory::config()->rdate( self::$val );
			self::$val = date( SPC::DEFAULT_DATE, self::$val );
		}
		else {
			self::$val = Factory::Db()->getNullDate();
		}

		return self::$val;
	}

	/**
	 * Returns ip value of requested variable
	 * @return string
	 */
	public static function now()
	{
		self::$val = gmdate( SPC::DEFAULT_DATE );

		return self::$val;
	}

	/**
	 * Returns array value of requested variable
	 *
	 * @param string $name variable name
	 * @param array $default default value
	 * @param string $method request method
	 *
	 * @return array
	 */
	public static function arr( $name, $default = [], $method = 'REQUEST' )
	{
		self::init( $name, $method );
		self::$val = isset( self::$request[ self::$name ] ) ? self::$request[ self::$name ] : $default;
		if ( is_array( self::$val ) ) {
			self::$val = ( array ) self::$val;
			self::$val = isset( self::$request[ self::$name ] ) ? self::$request[ self::$name ] : $default;
			self::$val = self::cleanArray( self::$val );
		}
		else {
			self::$val = $default;
		}

		return self::$val;
	}

	/**
	 * Returns raw value of requested variable
	 *
	 * @param string $name variable name
	 * @param string $default default value
	 * @param string $method request method
	 *
	 * @return string
	 */
	public static function raw( $name, $default = null, $method = 'REQUEST' )
	{
		self::init( $name, $method );
		self::$val = isset( self::$request[ self::$name ] ) ? self::$request[ self::$name ] : $default;

		return self::$val;
	}

	/**
	 * @param string $name variable name
	 * @param string $property
	 * @param string $request request method
	 *
	 * @return string
	 */
	public static function file( $name, $property = null, $request = 'files' )
	{
		if ( $request == 'files' ) {
			/** check for Ajax uploaded files */
			$check = self::string( $name );
			if ( $check ) {
				$secret = md5( Sobi::Cfg( 'secret' ) );
				$fileName = str_replace( 'file://', C::ES, $check );
				$path = SPLoader::dirPath( "tmp.files.$secret", 'front', false ) . '/' . $fileName;
				if ( file_exists( "{$path}.var" ) ) {
					$cfg = \Sobi\FileSystem\FileSystem::Read( "{$path}.var" );
					$data = SPConfig::unserialize( $cfg );
					$_FILES[ $name ] = $data;
				}
			}
		}
		self::init( $name, $request );
		self::$val = self::$request[ self::$name ] ?? null;

		return ( $property && isset( self::$val[ $property ] ) ) ? self::$val[ $property ] : self::$val;
	}

	/**
	 * Returns task value
	 *
	 * @param string $method request method
	 *
	 * @return bool
	 */
	public static function task( $method = 'REQUEST' )
	{
		return self::cmd( SOBI_TASK, null, $method );
	}

	/**
	 * checks if index exist within the request
	 *
	 * @param string $name variable name
	 * @param string $method
	 *
	 * @return string
	 */
	public static function exists( $name, $method = 'REQUEST' )
	{
		self::init( $name, $method );

		return isset( self::$request[ self::$name ] ) ? true : false;
	}

	/**
	 * Clean array
	 *
	 * @param array $arr array to clean
	 * @param bool $delEmpty
	 *
	 * @return array
	 */
	public static function cleanArray( $arr, $delEmpty = false )
	{
		if ( !empty( $arr ) ) {
			foreach ( $arr as $k => $v ) {
				if ( is_array( $v ) ) {
					$arr[ $k ] = self::cleanArray( $v, $delEmpty );
				}
				else {
					$arr[ $k ] = self::filter( $v );
					if ( $delEmpty && !( strlen( $v ) ) ) {
						unset( $arr[ $k ] );
					}
				}
			}
		}

		return $arr;
	}
}
