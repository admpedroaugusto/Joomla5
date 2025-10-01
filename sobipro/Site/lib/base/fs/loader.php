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
 * @created 10-Jan-2009 by Radek Suski
 * @modified 30 May 2023 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

use Sobi\Autoloader\Autoloader;
use Sobi\C;
use Sobi\Framework;
use Sobi\FileSystem\FileSystem;

if ( !class_exists( '\\Sobi\\Framework' ) ) {
	if ( file_exists( SOBI_ROOT . '/libraries/sobi/Framework.php' ) ) {
		include_once SOBI_ROOT . '/libraries/sobi/Framework.php';
	}
	else {
		throw new Exception( 'Cannot initialise Sobi Framework. Ensure that your server has the Sobi Framework installed.' );
	}
	Framework::Init( 'com_sobipro' );
}

try {
	Autoloader::getInstance()->registerClasses( [
		                                            'SPCache'              => SOBI_PATH . '/lib/base/cache.php',
		                                            'SPConfig'             => SOBI_PATH . '/lib/base/config.php',
		                                            'SPFactory'            => SOBI_PATH . '/lib/base/factory.php',
		                                            'SPHeader'             => SOBI_PATH . '/lib/base/header.php',
		                                            'SPMessage'            => SOBI_PATH . '/lib/base/message.php',
		                                            'SPObject'             => SOBI_PATH . '/lib/base/object.php',
		                                            'SPRegistry'           => SOBI_PATH . '/lib/base/registry.php',
		                                            'SPMainframeInterface' => SOBI_PATH . '/lib/base/mainframe.php',
		                                            'SPUserInterface'      => SOBI_PATH . '/lib/base/user.php',
	                                            ]
	);
}
catch ( \Sobi\Error\Exception $x ) {
}


/**
 * Class SPLoader
 */
abstract class SPLoader
{
	/** @var int */
	private static $count = 1;
	/** @var array */
	private static $loaded = [];

	/**
	 * @param string $name
	 * @param bool $adm
	 * @param string $type
	 * @param bool $raiseErr
	 *
	 * @return string
	 * @throws SPException
	 */
	public static function loadClass( string $name, bool $adm = false, string $type = C::ES, bool $raiseErr = true )
	{
		static $types = [ 'sp-root'     => 'sp-root',
		                  'base'        => 'base',
		                  'controller'  => 'ctrl',
		                  'controls'    => 'ctrl',
		                  'ctrl'        => 'ctrl',
		                  'model'       => 'models',
		                  'plugin'      => 'plugins',
		                  'application' => 'plugins',
		                  'view'        => 'views',
		                  'templates'   => 'templates' ];

		$type = trim( strtolower( $type ) );
		$name = trim( $name );
		$type = isset( $types[ $type ] ) ? $types[ $type ] . '/' : C::ES;

		if ( strstr( $name, 'cms' ) !== false ) {
			/* no specific Joomla 4 folders */
			$cmsFolder = SOBI_CMS == 'joomla4' ? 'joomla3' : SOBI_CMS;
			$name = str_replace( 'cms.', 'cms.' . $cmsFolder . '.', $name );
		}
		else {
			if ( strstr( $name, 'html.' ) ) {
				$name = str_replace( 'html.', 'mlo.', $name );
			}
		}
		if ( $adm ) {
			$path = $type == 'view' ? SOBI_ADM_PATH . '/' . $type : SOBI_PATH . "/lib/$type/adm/";
		}
		else {
			if ( $type && strstr( $type, 'plugin' ) ) {
				$path = SOBI_PATH . '/opt/' . $type;
			}
			elseif ( $type && strstr( $type, 'template' ) ) {
				$path = SOBI_PATH . '/' . SPC::TEMPLATE_PATH . Sobi::Cfg( 'section.template', SPC::DEFAULT_TEMPLATE );
			}
			elseif ( $type == 'sp-root/' ) {
				$path = SOBI_PATH . '/';
			}
			elseif ( !strstr( $name, 'opt.' ) ) {
				$path = SOBI_PATH . '/lib/' . $type;
			}
			else {
				$path = SOBI_PATH . '/' . $type;
			}
		}
		$name = str_replace( '.', '/', $name );
		$path .= $name . '.php';
		$path = self::clean( $path );

		/* to prevent double loading of the same class */
		/* class_exist does not work with interfaces */
		if ( isset( self::$loaded[ $path ] ) ) {
			return self::$loaded[ $path ];
		}

		/* fallback */
		if ( ( !file_exists( $path ) || !is_readable( $path ) ) && strstr( $name, 'cms' ) !== false ) {
			$path = preg_replace( '/(components.*)(joomla[0-9]{1})/', '$1joomla_common', $path );
		}
		if ( !file_exists( $path ) || !is_readable( $path ) ) {
			if ( $raiseErr ) {
				/* We had to change it to notice because all these script kiddies are trying to call some not existent file which causes this error here
					 * As a result we have the error log file full of USER_ERRORs, and it looks badly, but it's not really an error.
					 * So we result wit the 500 response code, but we log a notice for the logfile
					 * */
				if ( !strstr( $path, 'index.php' ) ) {
					if ( class_exists( 'Sobi', false ) ) {
						Sobi::Error( 'Class Load', sprintf( 'Cannot load file at %s. File does not exist or is not readable.', str_replace( SOBI_ROOT . '/', C::ES, $path ) ), C::NOTICE, 0 );
						throw new SPException( sprintf( 'Cannot load file at %s. File does not exist or is not readable.', str_replace( SOBI_ROOT . '/', C::ES, $path ) ) );
					}
				}
			}

			return false;
		}
		else {
			ob_start();
			$content = file_get_contents( $path );
			$nameSpace = [];
			preg_match( '/\s(namespace)(.*)/', $content, $nameSpace );
			$spaceName = isset( $nameSpace[ 2 ] ) ? trim( str_replace( ';', '\\', $nameSpace[ 2 ] ) ) : C::ES;
			$class = [];
			preg_match( '/\s*(class|interface)\s+(\w+)/', $content, $class );
			if ( isset( $class[ 2 ] ) ) {
				$className = $class[ 2 ];
			}
			else {
				Sobi::Error( 'Class Load', sprintf( 'Cannot determine class name in file %s.', str_replace( SOBI_ROOT . '/', C::ES, $path ) ), C::ERROR, 500 );

				return false;
			}
			require_once( $path );
			self::$count++;
			ob_end_clean();
			self::$loaded[ $path ] = $className;

			return $spaceName . $className;
		}
	}

	/**
	 * @param $file
	 *
	 * @return array|string|string[]|null
	 */
	private static function clean( $file )
	{
		// double slashes
		$file = preg_replace( '|([^:])(//)+([^/])|', '\1/\3', $file );
		// clean
		//$file = preg_replace( "|[^a-zA-Z\\\\0-9\.\-\_\/\|]|", null, $file );
		$file = preg_replace( "|[^a-zA-Z\\\\0-9\.\-\_\/\|\: ]|", C::ES, $file );

		return str_replace( '__BCKSL__', '\\', preg_replace( '|([^:])(\\\\)+([^\\\])|', "$1__BCKSL__$3", $file ) );
	}

	/**
	 * Loads classes from an array - used for the cache/un-serialise.
	 *
	 * @param array $arr array with file names
	 *
	 * @return void
	 */
	public static function wakeUp( $arr )
	{
		foreach ( $arr as $file => $class ) {
			if ( !( class_exists( $class, false ) ) ) {
				if ( file_exists( $file ) && is_readable( $file ) ) {
					require_once( $file );
					self::$count++;
					self::$loaded[ $file ] = $class;
				}
			}
		}
	}

	/**
	 * @return array - array with all loaded classes
	 */
	public static function getLoaded()
	{
		return self::$loaded;
	}

	/**
	 * @return int
	 */
	public static function getCount()
	{
		return self::$count;
	}

	/**
	 * @param $name
	 * @param bool $sections
	 * @param false $adm
	 * @param false $try
	 * @param false $absolute
	 * @param false $fixedPath
	 *
	 * @return array|false
	 * @deprecated
	 */
	public static function loadIniFile( $name, $sections = true, $adm = false, $try = false, $absolute = false, $fixedPath = false )
	{
		$path = $absolute ? null : ( $adm ? SOBI_ADM_PATH . '/' : SOBI_PATH . '/' );
		$name = str_replace( '.', '/', $name );
		if ( !$fixedPath ) {
			$path = FileSystem::FixPath( $path . $name );
		}

		return FileSystem::LoadIniFile( $path, $sections );
	}

	/**
	 * @param null $file
	 *
	 * @return null
	 * @deprecated
	 */
	public static function iniStorage( $file = null )
	{
		static $name = null;
		if ( $file ) {
			$name = $file;
		}
		else {
			return $name;
		}
	}

	/**
	 * @param $name
	 * @param bool $adm
	 * @param bool $redirect
	 *
	 * @return string
	 * @throws SPException
	 */
	public static function loadController( $name, $adm = false, $redirect = true )
	{
		return self::loadClass( $name, $adm, 'ctrl', $redirect );
	}

	/**
	 * @param $name
	 * @param bool $adm
	 * @param bool $redirect
	 *
	 * @return string
	 * @throws SPException
	 */
	public static function loadModel( $name, $adm = false, $redirect = true )
	{
		if ( strstr( $name, 'field' ) ) {
			self::loadClass( 'fields.interface', false, 'model', $redirect );
		}

		return self::loadClass( $name, $adm, 'model', $redirect );
	}

	/**
	 * @param $path
	 * @param string $type
	 * @param bool $check
	 *
	 * @return string
	 */
	public static function loadTemplate( $path, $type = 'xslt', $check = true )
	{
		return self::translatePath( $path, 'absolute', $check, $type );
	}

	/**
	 * @param $name
	 * @param bool $adm
	 * @param bool $redirect
	 *
	 * @return string
	 * @throws SPException
	 */
	public static function loadView( $name, $adm = false, $redirect = true )
	{
		return self::loadClass( $name, $adm, 'view', $redirect );
	}

	/**
	 * @param string $path
	 * @param string $root
	 * @param bool $checkExist
	 * @param string $ext
	 * @param bool $count
	 *
	 * @return string
	 */
	public static function path( $path, string $root = 'front', bool $checkExist = true, $ext = 'php', $count = true )
	{
		return self::translatePath( $path, $root, $checkExist, $ext, $count );
	}

	/**
	 * @param string $domain
	 * @param bool $checkExist
	 * @param bool $count
	 *
	 * @return string
	 */
	public static function langFile( $domain, bool $checkExist = true, bool $count = true )
	{
		return self::translatePath( $domain, 'locale', $checkExist, 'mo', $count );
	}

	/**
	 * @param string $path
	 * @param bool $adm
	 * @param bool $checkExist
	 * @param bool $toLive
	 * @param string $ext
	 * @param bool $count
	 *
	 * @return string
	 * @throws \SPException
	 */
	public static function JsFile( string $path, bool $adm = false, bool $checkExist = false, bool $toLive = true, string $ext = 'js', bool $count = false ): string
	{
		if ( strstr( $path, 'root.' ) ) {
			$file = self::translatePath( str_replace( 'root.', C::ES, $path ), 'root', $checkExist, $ext, $count );
		}
		elseif ( strstr( $path, 'front.' ) ) {
			$file = self::translatePath( str_replace( 'front.', C::ES, $path ), 'front', $checkExist, $ext, $count );
		}
		elseif ( strstr( $path, 'storage.' ) ) {
			$file = self::translatePath( str_replace( 'storage.', C::ES, $path ), 'storage', $checkExist, $ext, $count );
		}
		elseif ( strstr( $path, 'absolute.' ) ) {
			$file = self::translatePath( str_replace( 'absolute.', C::ES, $path ), 'absolute', $checkExist, $ext, $count );
		}
		else {
			$root = $adm ? 'adm.' : C::ES;
			$file = self::translatePath( $root . $path, 'js', $checkExist, $ext, $count );
		}
		if ( $toLive ) {
			$file = str_replace( SOBI_ROOT, SPFactory::config()->get( 'live_site' ), $file );

			return FileSystem::FixUrl( $file );
		}

		return FileSystem::FixPath( $file );
	}

	/**
	 * @param string $path
	 * @param bool $adm
	 * @param bool $checkExist
	 * @param bool $toLive
	 * @param string $ext
	 * @param bool $count
	 *
	 * @return string
	 */
	public static function CssFile( string $path, bool $adm = false, bool $checkExist = true, bool $toLive = true, string $ext = 'css', bool $count = false ): string
	{
		if ( strstr( $path, 'root.' ) ) {
			$file = self::translatePath( str_replace( 'root.', C::ES, $path ), 'root', $checkExist, $ext, $count );
		}
		elseif ( strstr( $path, 'front.' ) ) {
			$file = self::translatePath( str_replace( 'front.', C::ES, $path ), 'front', $checkExist, $ext, $count );
		}
		elseif ( strstr( $path, 'storage.' ) ) {
			$file = self::translatePath( str_replace( 'storage.', C::ES, $path ), 'storage', $checkExist, $ext, $count );
		}
		elseif ( strstr( $path, 'absolute.' ) ) {
			$file = self::translatePath( str_replace( 'absolute.', C::ES, $path ), 'absolute', $checkExist, $ext, $count );
		}
		else {
			$root = $adm ? 'adm.' : C::ES;
			$file = self::translatePath( $root . $path, 'css', $checkExist, $ext, $count );
		}
		if ( $toLive ) {
			$file = str_replace( SOBI_ROOT, SPFactory::config()->get( 'live_site' ), $file );

//			$file = str_replace( '\\', '/', $file );
			return FileSystem::FixUrl( $file );
		}

		return FileSystem::FixPath( $file );
	}

	/**
	 * @param string $path
	 * @param string $start
	 * @param bool $existCheck
	 * @param string $ext
	 * @param bool $count
	 *
	 * @return string
	 */
	public static function translatePath( $path, $start = 'front', $existCheck = true, $ext = 'php', $count = false )
	{
		$start = $start ? : 'front';
		switch ( $start ) {
			case 'root':
				$spoint = SOBI_ROOT . '/';
				break;
			case 'front':
				$spoint = SOBI_PATH . '/';
				break;
			case 'Library':
				$spoint = SOBI_PATH . '/Library/';
				break;
			case 'lib':
				$spoint = SOBI_PATH . '/lib/';
				break;
			case 'lib.base':
			case 'base':
				$spoint = SOBI_PATH . '/lib/base/';
				break;
			case 'lib.ctrl':
			case 'ctrl':
				$spoint = SOBI_PATH . '/lib/ctrl/';
				break;
			case 'lib.html':
				$spoint = SOBI_PATH . '/lib/mlo/';
				break;
			case 'lib.model':
			case 'lib.models':
			case 'model':
			case 'models':
				$spoint = SOBI_PATH . '/lib/models/';
				break;
			case 'lib.views':
			case 'lib.view':
			case 'views':
			case 'view':
				$spoint = SOBI_PATH . '/lib/views/';
				break;
			case 'js':
			case 'lib.js':
				$spoint = SOBI_PATH . '/lib/js/';
				break;
			case 'css':
			case 'media.css':
				$spoint = SOBI_MEDIA . '/css/';
				break;
			case 'less':
			case 'media.less':
				$spoint = SOBI_MEDIA . '/less/';
				break;
			case 'icons':
			case 'media.icons':
				$spoint = SOBI_MEDIA . '/icons/';
				break;
			case 'media':
				$spoint = SOBI_MEDIA . '/';
				break;
			case 'locale':
			case 'lang':
				$spoint = SOBI_PATH . '/usr/locale/';
				break;
			case 'templates':
				$spoint = SOBI_PATH . '/usr/templates/';
				break;
			case 'img':
			case 'media.img':
				$spoint = SOBI_IMAGES . '/img/'; //does not exist
				break;
			case 'media.categories':
				$spoint = SOBI_IMAGES . '/categories/';
				break;
			case 'adm':
			case 'administrator':
			case 'adm.template':
			case 'adm.templates':
				if ( defined( 'SOBI_ADM_PATH' ) ) {
					$spoint = SOBI_ADM_PATH . '/';
				}
				else {
					return false;
				}
				break;
			case 'storage':
				$spoint = SOBI_PATH . '/usr/templates/storage/';
				break;
			case 'absolute':
			default:
				$spoint = null;
				break;
		}
		$path = str_replace( '|', '/', $path );
		if ( $ext ) {
			$path = $spoint ? $spoint . '/' . $path . '|' . $ext : $path . '|' . $ext;
		}
		else {
			$path = $spoint ? $spoint . '/' . $path : $path;
		}
		$path = self::fixPath( $path );
		if ( $ext ) {
			$path = str_replace( '|', '.', $path );
		}
		if ( $existCheck ) {
			if ( !file_exists( $path ) || !is_readable( $path ) ) {
				return false;
			}
			else {
				if ( $count ) {
					self::$count++;
				}

				return $path;
			}
		}
		else {
			if ( $count ) {
				self::$count++;
			}

			return $path;
		}
	}

	/**
	 * @param string $path
	 *
	 * @return array|string|string[]|null
	 */
	private static function fixPath( string $path )
	{
		$start = C::ES;
		/* don't play with the constant parts of the path */
		if ( defined( 'SOBI_ADM_PATH' ) && strstr( $path, SOBI_ADM_PATH ) ) {
			$path = str_replace( SOBI_ADM_PATH, C::ES, $path );
			$start = SOBI_ADM_PATH;
		}
		elseif ( defined( 'SOBI_ADM_PATH' ) && strstr( $path, str_replace( '/', '.', SOBI_ADM_PATH ) ) ) {
			$path = str_replace( str_replace( '/', '.', SOBI_ADM_PATH ), C::ES, $path );
			$start = SOBI_ADM_PATH;
		}
		elseif ( strstr( $path, SOBI_PATH ) ) {
			$path = str_replace( SOBI_PATH, C::ES, $path );
			$start = SOBI_PATH;
		}
		elseif ( strstr( $path, str_replace( '/', '.', SOBI_PATH ) ) ) {
			$path = str_replace( str_replace( '/', '.', SOBI_PATH ), C::ES, $path );
			$start = SOBI_PATH;
		}
		elseif ( strstr( $path, SOBI_ROOT ) ) {
			$path = str_replace( SOBI_ROOT, C::ES, $path );
			$start = SOBI_ROOT;
		}
		elseif ( strstr( $path, str_replace( '/', '.', SOBI_ROOT ) ) ) {
			$path = str_replace( str_replace( '/', '.', SOBI_ROOT ), C::ES, $path );
			$start = SOBI_ROOT;
		}

		$path = str_replace( '.', '/', $path );

		return self::clean( $start . $path );
	}

	/**
	 * @param string $path
	 * @param string $start
	 * @param bool $existCheck
	 *
	 * @return string
	 */
	public static function translateDirPath( $path, $start = 'front', bool $existCheck = true )
	{
		return self::translatePath( str_replace( '.', '/', $path ), $start, $existCheck, C::ES, false );
	}

	/**
	 * @param string $path
	 * @param string $root
	 * @param bool $checkExist
	 *
	 * @return string
	 */
	public static function dirPath( $path, $root = 'front', $checkExist = true )
	{
		$path = self::translatePath( str_replace( '.', '/', $path ), $root, $checkExist, C::ES, false );

		return strlen( $path ) ? FileSystem::FixPath( self::clean( $path . '/' ) ) : $path;
	}

	/**
	 * @param string $path
	 * @param string $root
	 *
	 * @return string
	 */
	public static function newDir( $path, string $root = 'front' )
	{
		return self::translatePath( $path, $root, false, C::ES, false );
	}
}
