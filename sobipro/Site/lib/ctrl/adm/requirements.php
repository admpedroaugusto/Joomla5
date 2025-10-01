<?php
/**
 * @package SobiPro Library
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006–2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according to section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 21-Jul-2010 by Radek Suski
 * @modified 24 September 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadController( 'controller' );

use Sobi\C;
use Sobi\Error\Exception as InnerException;
use Sobi\Input\Input;
use Sobi\FileSystem\FileSystem;
use Sobi\Lib\Factory;
use Sobi\Utils\ {
	Arr,
	StringUtils
};
use Joomla\CMS\Installer\Installer;

/**
 * Class SPRequirements
 */
class SPRequirements extends SPController
{
	/** @var string */
	protected $_defTask = 'view';
	public const langFile = '/language/en-GB/en-GB.com_sobipro.check.ini';

	/**
	 * @return void
	 * @throws SPException
	 * @throws \Sobi\Error\Exception|\ReflectionException|\DOMException
	 */
	public function execute()
	{
		Factory::Application()->loadLanguage( 'com_sobipro.check' );

		$task = $this->_task = strlen( $this->_task ) ? $this->_task : $this->_defTask;
		// this is needed to delete all old caches after installation
		if ( Input::Int( 'init' ) ) {
			SPFactory::cache()->cleanAll();
		}

		switch ( $this->_task ) {
			case 'view':
				$this->view();
				break;
			case 'download':
				$this->download();
				break;
			case 'samples':
				$this->installSamples();
				break;
			default:
				if ( method_exists( $this, $this->_task ) ) {
					SPFactory::mainframe()
						->cleanBuffer()
						->customHeader();
					$this->$task();
					exit;
				}
				else {
					Sobi::Error( 'requirements', 'Task not found', C::WARNING, 404, __LINE__, __FILE__ );
					exit;
				}
				break;
		}
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function mySQLCache()
	{
		$version = Factory::Db()->getVersion();
		if ( strpos( $version, 'MariaDB' ) ) {
			try {
				Factory::Db()->exec( 'SHOW VARIABLES LIKE "have_query_cache"' );
				$cache = Factory::Db()->loadRow();
				if ( $cache[ 1 ] == 'YES' ) {
					echo $this->ok( $this->txt( 'REQ.MYSQL_CACHE_AVAILABLE' ), __FUNCTION__ );
				}
				else {
					echo $this->warning( $this->txt( 'REQ.MYSQL_CACHE_NOT_AVAILABLE' ), __FUNCTION__ );
				}
			}
			catch ( SPException $x ) {
				echo $this->warning( $this->txt( 'REQ.MYSQL_CACHE_CANNOT_CHECK' ), __FUNCTION__ );
			}
		}
	}

	/**
	 * @throws \SPException
	 */
	protected function createView()
	{
		$db = Factory::Db();
		try {
			$db->exec( 'DROP VIEW IF EXISTS spView' );
		}
		catch ( Sobi\Error\Exception $x ) {
		}
		try {
			$db->exec( 'CREATE VIEW spView AS SELECT * FROM spdb_category' );
		}
		catch ( Sobi\Error\Exception $x ) {
			echo $this->warning( $this->txt( 'REQ.MYSQL_VIEWS_NOT_AVAILABLE' ), __FUNCTION__ );
			exit;
		}
		try {
			$db->exec( 'DROP VIEW IF EXISTS spView' );
		}
		catch ( Sobi\Error\Exception $x ) {
		}
		echo $this->ok( $this->txt( 'REQ.MYSQL_VIEWS_AVAILABLE' ), __FUNCTION__ );
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function createFunction()
	{
		$db = Factory::Db();
		try {
			$db->exec( 'DROP FUNCTION IF EXISTS SpStatFunc' );
			$db->commit();
		}
		catch ( Sobi\Error\Exception $x ) {
		}
		try {
			$db->exec( '
				CREATE FUNCTION SpStatFunc ( msg VARCHAR( 20 ) ) returns VARCHAR( 50 )
				BEGIN
					RETURN ( "Hello in SQL Function" );
				END
			'
			);
		}
		catch ( Sobi\Error\Exception $x ) {
			echo $this->warning( $this->txt( 'REQ.MYSQL_FUNCTIONS_NOT_AVAILABLE' ), __FUNCTION__ );
			exit;
		}
		$db->exec( 'DROP FUNCTION IF EXISTS SpStatFunc' );
		echo $this->ok( $this->txt( 'REQ.MYSQL_FUNCTIONS_AVAILABLE' ), __FUNCTION__ );
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function createProcedure()
	{
		Factory::Db()
			->realExec( 'DROP PROCEDURE IF EXISTS SpStatProc' );
		Factory::Db()
			->realExec( '
				CREATE PROCEDURE SpStatProc ( OUT resp INT )
				BEGIN
					SET resp = ( SELECT COUNT(*) FROM spdb_language );
				END
			'
			);
		try {
			Factory::Db()->realExec( 'CALL SpStatProc( @resp ) ' );
			$r = Factory::Db()
				->setQuery( 'SELECT @resp' )
				->loadResult();
			if ( !( $r ) ) {
				throw new InnerException();
			}
			echo $this->ok( $this->txt( 'REQ.MYSQL_PROCEDURES_AVAILABLE' ), __FUNCTION__ );
		}
		catch ( Sobi\Error\Exception $x ) {
			echo $this->warning( $this->txt( 'REQ.MYSQL_PROCEDURES_NOT_AVAILABLE' ), __FUNCTION__ );
		} finally {
			Factory::Db()->realExec( 'DROP PROCEDURE IF EXISTS SpStatProc' );
		}
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function mySQLcharset()
	{
		Factory::Db()->exec( 'SELECT collation( "spdb_object" )' );
		$col = Factory::Db()->loadResult();
		if ( !( strstr( $col, 'utf8' ) ) ) {
			echo $this->error( $this->txt( 'REQ.MYSQL_WRONG_COLL', [ 'collation' => $col ] ), __FUNCTION__ );
		}
		else {
			echo $this->ok( $this->txt( 'REQ.MYSQL_COLL_OK', [ 'collation' => $col ] ), __FUNCTION__ );
		}
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function mySQLname()
	{
		$version = Factory::Db()->getVersion();
		$name = strpos( $version, 'MariaDB' ) ? 'MariaDB' : 'MySQL';
		echo $this->ok( $this->txt( 'REQ.MYSQL_NAME_IS', [ 'name' => $name ] ), __FUNCTION__ );
	}

	/**
	 * @throws SPException
	 */
	protected function mySQLversion()
	{
		$version = Factory::Db()->getVersion();
		$mariaDB = strpos( $version, 'MariaDB' ) == true;
		$version = preg_replace( '/[^0-9\.]/i', C::ES, $version );
		$version = explode( '.', $version );
		$version = [ 'major' => $version[ 0 ], 'minor' => $version[ 1 ], 'build' => ( isset( $version[ 2 ] ) ? substr( $version[ 2 ], 0, 2 ) : 0 ) ];

		if ( $mariaDB ) {
			$minVersion = [ 'major' => 10, 'minor' => 0, 'build' => 14 ];
			$recommendedVersion = [ 'major' => 11, 'minor' => 1, 'build' => 0 ];
		}
		else {
			$minVersion = [ 'major' => 5, 'minor' => 7, 'build' => 0 ];
			$recommendedVersion = [ 'major' => 8, 'minor' => 1, 'build' => 0 ];
		}

		if ( !$this->compareVersion( $minVersion, $version ) ) {
			echo $this->error( $this->txt( 'REQ.MYSQL_WRONG_VER', [ 'required' => implode( '.', $minVersion ), 'installed' => implode( '.', $version ) ] ), __FUNCTION__ );
		}
		else {
			if ( !$this->compareVersion( $recommendedVersion, $version ) ) {
				echo $this->warning( $this->txt( 'REQ.MYSQL_NOT_REC_VER', [ 'recommended' => implode( '.', $recommendedVersion ), 'installed' => implode( '.', $version ) ] ), __FUNCTION__ );
			}
			else {
				echo $this->ok( $this->txt( 'REQ.MYSQL_VERSION_OK', [ 'installed' => implode( '.', $version ) ] ), __FUNCTION__ );
			}
		}
	}

	/**
	 * @throws SPException
	 */
	protected function PEAR()
	{
		@include_once( 'PEAR.php' );
		$value = class_exists( 'PEAR' );
		if ( $value ) {
			echo $this->ok( $this->txt( 'REQ.PEAR_AVAILABLE' ), __FUNCTION__ );
		}
		else {
			echo $this->warning( $this->txt( 'REQ.PEAR_NOT_AVAILABLE' ), __FUNCTION__ );
		}
	}

	/**
	 * @throws SPException
	 */
	protected function memoryLimit()
	{
		$value = ini_get( 'memory_limit' );
		$value = preg_replace( '/[^0-9\-]/i', C::ES, $value );
		if ( $value >= 48 || $value == -1 ) {
			echo $this->ok( $this->txt( 'REQ.MEM_LIM_IS', [ 'memory' => $value ] ), __FUNCTION__ );
		}
		else {
			if ( $value >= 32 ) {
				echo $this->warning( $this->txt( 'REQ.MEM_LIM_IS_LOW', [ 'memory' => $value ] ), __FUNCTION__ );
			}
			else {
				echo $this->error( $this->txt( 'REQ.MEM_LIM_IS_TOO_LOW', [ 'memory' => $value ] ), __FUNCTION__ );
			}
		}
	}

	/**
	 * @throws SPException
	 */
	protected function maxExecutionTime()
	{
		$value = ini_get( 'max_execution_time' );
		$value = preg_replace( '/[^0-9]/i', C::ES, $value );
		if ( $value == 0 ) {
			$options = ini_get_all();
			$value = $options[ 'max_execution_time' ][ 'global_value' ];
		}

		if ( $value >= 30 ) {
			echo $this->ok( $this->txt( 'REQ.MAX_EXEC_IS', [ 'limit' => $value ] ), __FUNCTION__ );
		}
		else {
			echo $this->warning( $this->txt( 'REQ.MAX_EXEC_IS_LOW', [ 'limit' => $value ] ), __FUNCTION__ );
		}
	}

	/**
	 * @throws SPException
	 */
	protected function iniParse()
	{
		$value = function_exists( 'parse_ini_file' );
		if ( $value ) {
			echo $this->ok( $this->txt( 'REQ.PARSE_INI_AVAILABLE' ), __FUNCTION__ );
		}
		else {
			echo $this->error( $this->txt( 'REQ.PARSE_INI_NOT_AVAILABLE' ), __FUNCTION__ );
		}
	}

	/**
	 * @throws SPException
	 */
	protected function exec()
	{
		$value = function_exists( 'exec' );
		$disabled = explode( ', ', ini_get( 'disable_functions' ) );
		if ( $value && ( !in_array( 'exec', $disabled ) ) ) {
			echo $this->ok( $this->txt( 'REQ.EXEC_ENABLED' ), __FUNCTION__ );
		}
		else {
			echo $this->warning( $this->txt( 'REQ.EXEC_NOT_ENABLED' ), __FUNCTION__ );
		}
	}

	/**
	 * @throws SPException
	 */
	protected function PSpell()
	{
		$value = function_exists( 'pspell_check' );
		if ( $value ) {
			echo $this->ok( $this->txt( 'REQ.PSPELL_AVAILABLE' ), __FUNCTION__ );
		}
		else {
			echo $this->warning( $this->txt( 'REQ.PSPELL_NOT_AVAILABLE' ), __FUNCTION__ );
		}
	}

	/**
	 * @throws SPException
	 */
	protected function Calendar()
	{
		$value = function_exists( 'cal_days_in_month' );
		if ( $value ) {
			echo $this->ok( $this->txt( 'REQ.CALENDAR_AVAILABLE' ), __FUNCTION__ );
		}
		else {
			echo $this->warning( $this->txt( 'REQ.CALENDAR_NOT_AVAILABLE' ), __FUNCTION__ );
		}
	}

	/**
	 * @throws SPException
	 */
	protected function reflection()
	{
		$value = class_exists( 'ReflectionClass' );
		if ( $value ) {
			echo $this->ok( $this->txt( 'REQ.REFLECTION_AVAILABLE' ), __FUNCTION__ );
		}
		else {
			echo $this->error( $this->txt( 'REQ.REFLECTION_NOT_AVAILABLE' ), __FUNCTION__ );
		}
	}

	/**
	 * @throws SPException
	 */
	protected function filterFunctions()
	{
		$value = function_exists( 'filter_var' );
		if ( $value ) {
			echo $this->ok( $this->txt( 'REQ.FILTER_AVAILABLE' ), __FUNCTION__ );
		}
		else {
			echo $this->error( $this->txt( 'REQ.FILTER_NOT_AVAILABLE' ), __FUNCTION__ );
		}
	}

	/**
	 * @throws SPException
	 */
	protected function tidy()
	{
		$value = class_exists( 'tidy' );
		if ( $value ) {
			echo $this->ok( $this->txt( 'REQ.TIDY_AVAILABLE' ), __FUNCTION__ );
		}
		else {
			echo $this->warning( $this->txt( 'REQ.TIDY_NOT_AVAILABLE' ), __FUNCTION__ );
		}
	}

	/**
	 * @throws SPException
	 */
	protected function ZipArchive()
	{
		$value = class_exists( 'ZipArchive' );
		if ( $value ) {
			echo $this->ok( $this->txt( 'REQ.ZIP_AVAILABLE' ), __FUNCTION__ );
		}
		else {
			echo $this->error( $this->txt( 'REQ.ZIP_NOT_AVAILABLE' ), __FUNCTION__ );
		}
	}

	// @todo: ROTFL ;)

	/**
	 * @throws SPException
	 */
	protected function json()
	{
		$value = function_exists( 'json_encode' );
		if ( $value ) {
			echo $this->ok( $this->txt( 'REQ.JSON_AVAILABLE' ), __FUNCTION__ );
		}
		else {
			echo $this->error( $this->txt( 'REQ.JSON_NOT_AVAILABLE' ), __FUNCTION__ );
		}
	}

	/**
	 * @throws SPException
	 */
	protected function OpenSSL()
	{
		$value = function_exists( 'openssl_x509_parse' );
		if ( $value ) {
			echo $this->ok( $this->txt( 'REQ.OPENSSL_AVAILABLE' ), __FUNCTION__ );
		}
		else {
			echo $this->error( $this->txt( 'REQ.OPENSSL_NOT_AVAILABLE' ), __FUNCTION__ );
		}
	}

	/**
	 * @throws SPException
	 */
	protected function SOAP()
	{
		$value = class_exists( 'SoapClient' );
		if ( $value ) {
			echo $this->ok( $this->txt( 'REQ.SOAP_AVAILABLE' ), __FUNCTION__ );
		}
		else {
			echo $this->error( $this->txt( 'REQ.SOAP_NOT_AVAILABLE' ), __FUNCTION__ );
		}
	}

	/**
	 * @throws SPException
	 */
	protected function CURL()
	{
		$value = function_exists( 'curl_init' );
		if ( $value ) {
			$cfg = $this->curlFull();
			if ( $cfg[ 'available' ] && $cfg[ 'response' ][ 'http_code' ] == 200 ) {
				echo $this->ok( $this->txt( 'REQ.CURL_INSTALLED' ), __FUNCTION__ );
			}
			else {
				echo $this->warning( $this->txt( 'REQ.CURL_NOT_USABLE' ), __FUNCTION__ );
			}
		}
		else {
			echo $this->error( $this->txt( 'REQ.CURL_NOT_INSTALLED' ), __FUNCTION__ );
		}
	}

	/**
	 * @throws SPException
	 */
	protected function PCRE()
	{
		$value = function_exists( 'preg_grep' );
		if ( $value ) {
			echo $this->ok( $this->txt( 'REQ.REPC_AVAILABLE' ), __FUNCTION__ );
		}
		else {
			echo $this->error( $this->txt( 'REQ.REPC_NOT_AVAILABLE' ), __FUNCTION__ );
		}
	}

	/**
	 * @throws SPException
	 */
	protected function SPL()
	{
		$value = class_exists( 'DirectoryIterator' );
		if ( $value ) {
			echo $this->ok( $this->txt( 'REQ.SPL_AVAILABLE' ), __FUNCTION__ );
		}
		else {
			echo $this->error( $this->txt( 'REQ.SPL_NOT_AVAILABLE' ), __FUNCTION__ );
		}
	}

	/**
	 * @throws SPException
	 */
	protected function XPath()
	{
		$value = class_exists( 'DOMXPath' );
		if ( $value ) {
			echo $this->ok( $this->txt( 'REQ.DOMXPATH_AVAILABLE' ), __FUNCTION__ );
		}
		else {
			echo $this->error( $this->txt( 'REQ.DOMXPATH_NOT_AVAILABLE' ), __FUNCTION__ );
		}
	}

	/**
	 * @throws SPException
	 */
	protected function XSL()
	{
		$value = class_exists( 'XSLTProcessor' );
//		$value = false;
		if ( $value ) {
			echo $this->ok( $this->txt( 'REQ.XSL_AVAILABLE' ), __FUNCTION__ );
		}
		else {
			echo $this->error( $this->txt( 'REQ.XSL_NOT_AVAILABLE' ), __FUNCTION__ );
		}
	}

	/**
	 * @throws SPException
	 */
	protected function DOM()
	{
		$value = class_exists( 'DOMDocument' );
		if ( $value ) {
			echo $this->ok( $this->txt( 'REQ.DOM_AVAILABLE' ), __FUNCTION__ );
		}
		else {
			echo $this->error( $this->txt( 'REQ.DOM_NOT_AVAILABLE' ), __FUNCTION__ );
		}
	}

	/**
	 * @throws SPException
	 */
	protected function exif()
	{
		$value = function_exists( 'exif' );
		if ( $value ) {
			echo $this->ok( $this->txt( 'REQ.EXIF_AVAILABLE' ), __FUNCTION__ );
		}
		else {
			echo $this->warning( $this->txt( 'REQ.EXIF_NOT_AVAILABLE' ), __FUNCTION__ );
		}
	}

	/**
	 * @throws SPException
	 */
	protected function SQLite()
	{
		$value = false;
		if ( class_exists( 'SQLiteDatabase' ) ) {
			$value = true;  /* driver exists */
		}
		else {
			if ( class_exists( 'PDO' ) ) {
				try {
					$db = new PDO( 'sqlite:' . Sobi::Cfg( 'cache.store', SOBI_PATH . '/var/cache/' ) . '.htCache.db' );
					$value = true;  /* driver exists */
				}
				catch ( PDOException $e ) {
				}
			}
		}
		if ( $value ) {
			echo $this->ok( $this->txt( 'REQ.SQLITE_AVAILABLE' ), __FUNCTION__ );
		}
		else {
			echo $this->warning( $this->txt( 'REQ.SQLITE_NOT_AVAILABLE' ), __FUNCTION__ );
		}
	}

	/**
	 * @throws SPException
	 */
	protected function gDlib()
	{
		$value = function_exists( 'gd_info' );
		$info = gd_info();
		$version = $info[ 'GD Version' ];
		if ( $value ) {
			echo $this->ok( $this->txt( 'REQ.GD_AVAILABLE', [ 'version' => $version ] ), __FUNCTION__ );
		}
		else {
			echo $this->warning( $this->txt( 'REQ.GD_NOT_AVAILABLE' ), __FUNCTION__ );
		}
	}

	/**
	 * @throws SPException
	 */
	/*	protected function registerGlobals()
		{
			$value = ini_get( 'register_globals' );
			if ( !( $value ) || strtolower( $value ) == 'off' ) {
				echo $this->ok( $this->txt( 'REQ.RG_DISABLED' ), __FUNCTION__ );
			}
			else {
				echo $this->warning( $this->txt( 'REQ.RG_ENABLED' ), __FUNCTION__ );
			}
		}*/

	/**
	 * @throws SPException
	 */
	protected function webp()
	{
		if ( function_exists( 'imagewebp' ) ) {
			echo $this->ok( $this->txt( 'REQ.WEBP_AVAILABLE' ), __FUNCTION__ );
		}
		else {
			echo $this->warning( $this->txt( 'REQ.WEBP_NOT_AVAILABLE' ), __FUNCTION__ );
		}
	}


	/**
	 * @throws SPException
	 */
//	protected function safeMode()
//	{
//		$value = ini_get( 'safe_mode' );
//		if ( !( $value ) || strtolower( $value ) == 'off' ) {
//			echo $this->ok( $this->txt( 'REQ.PHP_SAFE_MODE_DISABLED' ), __FUNCTION__ );
//		}
//		else {
//			echo $this->error( $this->txt( 'REQ.PHP_SAFE_MODE_ENABLED' ), __FUNCTION__ );
//		}
//	}

	/**
	 * @throws SPException
	 */
	protected function phpVersion()
	{
		$phpVer = $version = preg_replace( '/[^0-9\.]/i', C::ES, substr( PHP_VERSION, 0, 6 ) );
		$version = explode( '.', $phpVer );
		$version = [ 'major' => $version[ 0 ], 'minor' => $version[ 1 ], 'build' => ( $version[ 2 ] ?? 0 ) ];
		$minVersion = [ 'major' => 8, 'minor' => 1, 'build' => 0 ];
		$recommendedVersion = [ 'major' => 8, 'minor' => 2, 'build' => 0 ];
		if ( !( $this->compareVersion( $minVersion, $version ) ) ) {
			echo $this->error( $this->txt( 'REQ.PHP_WRONG_VER', [ 'required' => implode( '.', $minVersion ), 'installed' => implode( '.', $version ) ] ), __FUNCTION__ );
		}
		else {
			if ( !( $this->compareVersion( $minVersion, $version ) ) ) {
				echo $this->warning( $this->txt( 'REQ.PHP_NOT_REC_VER', [ 'recommended' => implode( '.', $recommendedVersion ), 'installed' => implode( '.', $version ) ] ), __FUNCTION__ );
			}
			else {
				echo $this->ok( $this->txt( 'REQ.PHP_VERSION_OK', [ 'installed' => implode( '.', $version ) ] ), __FUNCTION__ );
			}
		}
	}

	/**
	 * @throws SPException
	 */
	protected function webServer()
	{
		$server = Input::String( 'SERVER_SOFTWARE', 'server', getenv( 'SERVER_SOFTWARE' ) );
//		$server = 'Apache';
		$server = preg_split( '/[\/ ]/', $server );
		$soft = $server[ 0 ] ?? 'Unknown';
		$version = isset( $server[ 1 ] ) ? preg_replace( '/[^0-9\.]/i', C::ES, $server[ 1 ] ) : '0.0.0';
		$version = explode( '.', $version );
		$sapi = function_exists( 'php_sapi_name' ) ? php_sapi_name() : 'Unknown';
		if ( strtolower( $soft ) != 'apache' ) {
			echo $this->warning( $this->txt( 'REQ.WS_WRONG_SOFTWARE', [ 'webserver' => Input::String( 'SERVER_SOFTWARE', 'server', getenv( 'SERVER_SOFTWARE' ) ) ] ), __FUNCTION__ );
		}
		else {
			$minVersion = [ 'major' => 2, 'minor' => 0, 'build' => 0 ];
//			$recommendedVersion = array( 'major' => 2, 'minor' => 2, 'build' => 0 );
			if ( !( isset( $version[ 0 ] ) && isset( $version[ 1 ] ) && isset( $version[ 2 ] ) ) || !( $version[ 0 ] && $version[ 1 ] ) ) {
				echo $this->warning( $this->txt( 'REQ.WS_NO_APACHE_VER', [ 'required' => implode( '.', $minVersion ), 'sapi' => $sapi ] ), __FUNCTION__ );
				exit;
			}
			$version = [ 'major' => $version[ 0 ], 'minor' => $version[ 1 ], 'build' => ( $version[ 2 ] ?? 0 ) ];
			if ( !$this->compareVersion( $minVersion, $version ) ) {
				echo $this->error( $this->txt( 'REQ.WS_WRONG_VER', [ 'required' => implode( '.', $minVersion ), 'installed' => implode( '.', $version ), 'sapi' => $sapi ] ), __FUNCTION__ );
			}
//			elseif ( !( $this->compareVersion( $recommendedVersion, $version ) ) ) {
//				echo $this->warning( $this->txt( 'REQ.WS_NOT_REC_VER', array( 'recommended' => implode( '.', $rminVer ), 'installed' => implode( '.', $version ), 'sapi' => $sapi ) ), __FUNCTION__ );
//			}
			else {
				echo $this->ok( $this->txt( 'REQ.WS_VERSION_OK', [ 'installed' => implode( '.', $version ), 'sapi' => $sapi ] ), __FUNCTION__ );
			}
		}
	}

	/**
	 * @throws SPException
	 */
	protected function cmsEncoding()
	{
		$value = strtolower( Factory::ApplicationHelper()->applicationsSetting( 'charset' ) );
		if ( $value != 'utf-8' ) {
			echo $this->error( $this->txt( 'REQ.CMS_ENCODING_NOK', [ 'encoding' => $value ] ), __FUNCTION__ );
		}
		else {
			echo $this->ok( $this->txt( 'REQ.CMS_ENCODING_OK', [ 'encoding' => $value ] ), __FUNCTION__ );
		}
	}

	/**
	 * @throws SPException
	 */
//	protected function cmsFtp()
//	{
//		$value = Factory::ApplicationHelper()->applicationsSetting( 'ftp_enable' );
//		if ( $value && $value != 'disabled' ) {
//			echo $this->warning( $this->txt( 'REQ.CMS_FTP_NOK' ), __FUNCTION__ );
//		}
//		else {
//			echo $this->ok( $this->txt( 'REQ.CMS_FTP_OK' ), __FUNCTION__ );
//		}
//	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function cms()
	{
		$cmsVer = Factory::ApplicationHelper()->applicationVersion();
		$cmsName = Factory::ApplicationHelper()->applicationName();
		$minVersion = Factory::ApplicationHelper()->minVersion();
		$rminVer = Factory::ApplicationHelper()->minVersion( true );
		unset( $cmsVer[ 'rev' ] );
		unset( $minVersion[ 'rev' ] );

		if ( !$this->compareVersion( $minVersion, $cmsVer ) ) {
			echo $this->error( $this->txt( 'REQ.CMS_WRONG_VER', [ 'cms' => $cmsName, 'required' => implode( '.', $minVersion ), 'installed' => implode( '.', $cmsVer ) ] ), __FUNCTION__ );
		}
		else {
			if ( !$this->compareVersion( $rminVer, $cmsVer ) ) {
				echo $this->warning( $this->txt( 'REQ.CMS_NOT_REC_VER', [ 'cms' => $cmsName, 'recommended' => implode( '.', $rminVer ), 'installed' => implode( '.', $cmsVer ) ] ), __FUNCTION__ );
			}
			else {
				echo $this->ok( $this->txt( 'REQ.CMS_VERSION_OK', [ 'cms' => $cmsName, 'installed' => implode( '.', $cmsVer ) ] ), __FUNCTION__ );
			}
		}
	}

	/**
	 * @param $key
	 * @param $value
	 * @param string|array $msg
	 *
	 * @throws SPException|\Exception
	 */
	protected function store( $key, $value, $msg = C::ES )
	{
		// let's try to create kinda mutex here
		$file = SPLoader::path( 'tmp.info', 'front', false, 'txt' );
		while ( FileSystem::Exists( $file ) ) {
			usleep( 100000 );
		}
		$c = date( DATE_RFC822 );
		FileSystem::Write( $file, $c );
		$store = Sobi::GetUserData( 'requirements', [] );
		$store[ $key ] = [ 'value' => $value, 'message' => $msg ];
		Sobi::SetUserData( 'requirements', $store );
		FileSystem::Delete( $file );

//		$msg = $msg ? $msg[ 'org' ][ 'label' ] : null;
//		$file = SPLoader::path( 'tmp.info', 'front', false, 'txt' );
//		$cont = null;
//		if ( FileSystem::Exists( $file ) ) {
//			$cont = FileSystem::Read( $file );
//		}
//		$txt = "{$cont}\n{$key}={$msg};{$value}";
//		FileSystem::Write( $file, $txt );
	}

	/**
	 * @param $settings
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function prepareStoredData( &$settings )
	{
		$store = Sobi::GetUserData( 'requirements', [] );
		if ( Sobi::Lang() != 'en-GB' && file_exists( JPATH_ADMINISTRATOR . self::langFile ) ) {
			$file = parse_ini_file( JPATH_ADMINISTRATOR . self::langFile );
		}
		if ( count( $store ) ) {
			foreach ( $store as $key => $data ) {
				if ( Sobi::Lang() != 'en-GB' ) {
					$translate = $file[ 'SP.' . $data[ 'message' ][ 'org' ][ 'label' ] ];
					if ( count( $data[ 'message' ][ 'org' ][ 'params' ] ) ) {
						foreach ( $data[ 'message' ][ 'org' ][ 'params' ] as $param => $value ) {
							$translate = str_replace( "var:[$param]", $value, $translate );
						}
					}
					$settings[ $key ] = [ 'key' => $key, 'response' => [ 'en-GB' => $translate, Sobi::Lang() => $data[ 'message' ][ 'current' ] ], 'status' => $data[ 'value' ] ];
				}
				else {
					$settings[ $key ] = [ 'key' => $key, 'response' => [ 'en-GB' => $data[ 'message' ][ 'current' ] ], 'status' => $data[ 'value' ] ];
				}
			}
		}
	}

	/**
	 * Installs the sample data.
	 *
	 * @param false $return
	 *
	 * @return bool
	 */
	public function installSamples( $return = false )
	{
		$result = true;
		$file = FileSystem::FixPath( SOBI_ADM_PATH . SPC::DEFAULT_SAMPLES . '.sql' );
		if ( FileSystem::Exists( $file ) ) {

			/* Execute SQL */
			try {
				$installer = new Installer();
				$script = new SimpleXMLElement( "<sql><file charset=\"utf8\" driver=\"mysql\">$file</file></sql>" );
				$installer->parseSQLFiles( $script );
			}
			catch ( Exception $x ) {
				$result = false;
			}

			/* Handling sample data */
			if ( FileSystem::Exists( SOBI_PATH . '/tmp/SampleData/entries/' ) ) {
				$sampleImages = JPATH_ROOT . '/images/sobipro/';
				if ( !FileSystem::Exists( $sampleImages ) ) {
					FileSystem::Mkdir( $sampleImages );
				}
				try {
					FileSystem::Move( SOBI_PATH . '/tmp/SampleData/entries/', $sampleImages . '/entries/' );
				}
				catch ( Exception $x ) {
					$result = false;
				}
			}
			if ( $result ) {
				FileSystem::Delete( $file );
			}
		}

		if ( $return ) {
			return $result;
		}
		else {
			echo json_encode( $result );
			exit;
		}
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception|\DOMException
	 */
	protected function download()
	{
		$settings = [];
		$settings[ 'SobiPro' ] = [ 'Version' => Factory::ApplicationHelper()->applicationName(), 'Version_Num' => implode( '.', Factory::ApplicationHelper()->myVersion() ) ];
		$this->prepareStoredData( $settings );
		$settings[ 'env' ] = [
			'PHP_OS'         => PHP_OS,
			'php_uname'      => php_uname(),
			'PHP_VERSION_ID' => PHP_VERSION_ID,
		];
//		$settings[ 'ftp' ] = $this->ftp();
		$settings[ 'curl' ] = $this->curlFull();
		$settings[ 'exec' ][ 'response' ] = $this->execResp();
		$settings[ 'SOBI_SETTINGS' ] = SPFactory::config()->getSettings();
		$c = Factory::Db()->select( '*', 'spdb_config' )->loadObjectList();
		$sections = Factory::Db()
			->select( [ 'nid', 'id' ], 'spdb_object', [ 'oType' => 'section' ] )
			->loadAssocList( 'id' );
		$as = [];
		foreach ( $c as $key ) {
			if ( $key->section == 0 || !( isset( $sections[ $key->section ] ) ) ) {
				continue;
			}
			$key->section = $sections[ $key->section ][ 'nid' ];
			if ( !( isset( $as[ $key->section ] ) ) ) {
				$as[ $key->section ] = [];
			}
			if ( !( isset( $as[ $key->section ][ $key->cSection ] ) ) ) {
				$as[ $key->section ][ $key->cSection ] = [];
			}
			$_c = explode( '_', $key->sKey );
			if ( $_c[ count( $_c ) - 1 ] == 'array' ) {
				$key->sValue = SPConfig::unserialize( $key->sValue );
			}
			$as[ $key->section ][ $key->cSection ][ $key->sKey ] = $key->sValue;
		}
		$settings[ 'SOBI_SETTINGS' ][ 'sections' ] = $as;
		$apps = Factory::Db()->select( '*', 'spdb_plugins' )->loadObjectList();
		foreach ( $apps as $app ) {
			$settings[ 'Apps' ][ $app->pid ] = get_object_vars( $app );
		}
		$settings[ 'SOBI_SETTINGS' ][ 'mail' ][ 'smtphost' ] = $settings[ 'SOBI_SETTINGS' ][ 'mail' ][ 'smtphost' ] ? 'SET' : 0;
		$settings[ 'SOBI_SETTINGS' ][ 'mail' ][ 'smtpuser' ] = $settings[ 'SOBI_SETTINGS' ][ 'mail' ][ 'smtpuser' ] ? 'SET' : 0;
		$settings[ 'SOBI_SETTINGS' ][ 'mail' ][ 'smtppass' ] = $settings[ 'SOBI_SETTINGS' ][ 'mail' ][ 'smtppass' ] ? 'SET' : 0;

		$php = ini_get_all();
		unset( $php[ 'extension_dir' ] );
		unset( $php[ 'include_path' ] );
		unset( $php[ 'mysql.default_user' ] );
		unset( $php[ 'mysql.default_password' ] );
		unset( $php[ 'mysqli.default_pw' ] );
		unset( $php[ 'mysqli.default_user' ] );
		unset( $php[ 'open_basedir' ] );
		unset( $php[ 'pdo_mysql.default_socket' ] );
		unset( $php[ 'sendmail_path' ] );
		unset( $php[ 'session.name' ] );
		unset( $php[ 'session.save_path' ] );
		unset( $php[ 'soap.wsdl_cache_dir' ] );
		unset( $php[ 'upload_tmp_dir' ] );
		unset( $php[ 'doc_root' ] );
		unset( $php[ 'docref_ext' ] );
		unset( $php[ 'docref_root' ] );
		unset( $php[ 'mysql.default_socket' ] );
		$settings[ 'PHP_SETTINGS' ] = $php;
		$php = get_loaded_extensions();
		$settings[ 'PHP_EXT' ] = $php;

		$arrUtils = new Arr();
		$data = $arrUtils->toXML( $settings, 'settings' );
		$data = str_replace( [ SOBI_ROOT, '></' ], [ 'REMOVED', '>0</' ], $data );
		$f = StringUtils::Nid( $settings[ 'SOBI_SETTINGS' ][ 'general' ][ 'site_name' ] . '-' . date( DATE_RFC822 ) );

		SPFactory::mainframe()->cleanBuffer();
		header( 'Cache-Control: no-cache, must-revalidate' );
		header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' );
		header( "Content-type: application/xml" );
		header( "Content-Disposition: attachment; filename=\"sobipro_system_$f.xml\"" );
		header( 'Content-Length: ' . strlen( $data ) );

		ob_clean();
		flush();
		echo( $data );

		exit;
	}

	/**
	 * @return array
	 */
	protected function execResp(): array
	{
		$cmd = 'date';
		$cfg = [ 'shell' => [] ];
		$n = null;
		if ( function_exists( 'exec' ) ) {
			set_time_limit( 15 );
			$cfg[ 'shell' ][ 'exec' ] = trim( exec( $cmd, $n ) );
		}
		if ( function_exists( 'shell_exec' ) ) {
			set_time_limit( 15 );
			$cfg[ 'shell' ][ 'shell_exec' ] = trim( shell_exec( $cmd ) );
		}
		if ( function_exists( 'system' ) ) {
			set_time_limit( 15 );
			$cfg[ 'shell' ][ 'system' ] = trim( system( $cmd, $n ) );
		}

		return $cfg;
	}

	/**
	 * @return array
	 * @depreacted since 2.0
	 */
	protected function ftp()
	{
		$cfg = [];
		if ( function_exists( 'ftp_connect' ) ) {
			$cfg[ 'available' ] = 'available';
			$address = 'sigsiu-net.de';
			set_time_limit( 15 );
			if ( ( $ftp = ftp_connect( $address ) ) !== false ) {
				$cfg[ 'connected' ] = 'created';
				if ( ( $login = @ftp_login( $ftp, 'ftp', '' ) ) !== false ) {
				}
				else {
					$cfg[ 'available' ] = 'available but seems not usable';
				}
			}
			else {
				$cfg[ 'available' ] = 'available but seems not usable';
			}
		}
		else {
			$cfg[ 'available' ] = 'disabled';
		}

		return $cfg;
	}

	/**
	 * @return array
	 */
	protected function curlFull()
	{
		if ( function_exists( 'curl_init' ) ) {
			$cfg[ 'available' ] = 'available';
			set_time_limit( 15 );
			$cfg[ 'version' ] = curl_version();
			$c = curl_init( "https://www.sigsiu.net/sobipro-check/testcurl" );

			if ( $c !== false ) {
				$fp = fopen( "temp.txt", "w" );
				// 'ssl_verifypeer' => false,
				// 'ssl_verifyhost' => 2,
				curl_setopt( $c, CURLOPT_SSL_VERIFYPEER, false );
				curl_setopt( $c, CURLOPT_SSL_VERIFYHOST, 2 );
				curl_setopt( $c, CURLOPT_FILE, $fp );
				curl_setopt( $c, CURLOPT_HEADER, 0 );
				curl_exec( $c );

				$cfg[ 'response' ] = curl_getinfo( $c );
				$c = curl_init( "http://ip.sobi.pro" );
				if ( $c !== false ) {
					curl_setopt( $c, CURLOPT_FILE, $fp );
					curl_setopt( $c, CURLOPT_HEADER, 0 );
					curl_exec( $c );
					$cfg[ 'mip' ] = curl_getinfo( $c );
					$cfg[ 'mip' ][ 'content' ] = file_get_contents( 'temp.txt' );
				}
				fclose( $fp );
				unlink( "temp.txt" );
			}
			else {
				$cfg[ 'response' ] = C::ES; //curl_getinfo( $c );
				$cfg[ 'error' ] = C::ES; //curl_error( $c );
				$cfg[ 'available' ] = 'available but not usable';
			}
		}
		else {
			$cfg[ 'available' ] = 'not available';
		}

		return $cfg;
	}

	/**
	 * @param string $text
	 * @param array $params
	 *
	 * @return array
	 */
	protected function txt( string $text, array $params = [] )
	{
		return [ 'current' => Sobi::Txt( $text, $params ), 'org' => [ 'label' => $text, 'params' => $params ] ];
	}

	/**
	 * @param $msg
	 * @param $key
	 * @param bool $storeOnly
	 *
	 * @return false|string
	 * @throws SPException
	 */
	protected function ok( $msg, $key, bool $storeOnly = false )
	{
		$this->store( $key, __FUNCTION__, $msg );
		if ( !$storeOnly ) {
			return $this->out( $msg );
		}

		return C::ES;
	}

	/**
	 * @param $msg
	 * @param $key
	 * @param bool $storeOnly
	 *
	 * @return false|string
	 * @throws SPException
	 */
	protected function warning( $msg, $key, bool $storeOnly = false )
	{
		$this->store( $key, __FUNCTION__, $msg );
		if ( !$storeOnly ) {
			return $this->out( $msg, C::WARN_MSG );
		}

		return C::ES;
	}

	/**
	 * @param $msg
	 * @param $key
	 * @param bool $storeOnly
	 *
	 * @return false|string
	 * @throws SPException
	 */
	protected function error( $msg, $key, bool $storeOnly = false )
	{
		$this->store( $key, __FUNCTION__, $msg );
		if ( !$storeOnly ) {
			return $this->out( $msg, C::ERROR_MSG );
		}

		return C::ES;
	}

	/**
	 * @param $message
	 * @param string $type
	 *
	 * @return false|string
	 */
	protected function out( $message, string $type = C::SUCCESS_MSG )
	{
		return json_encode( [ 'type' => $type, 'message' => $message[ 'current' ], 'textType' => Sobi::Txt( 'STATUS_' . $type ) ] );
	}

	/**
	 * @return void
	 * @throws \ReflectionException
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 * @throws \Exception
	 */
	protected function view()
	{
		$store = C::ES;
		Sobi::SetUserData( 'requirements', $store );
		$home = Input::Int( 'init' ) ? Sobi::Url( C::ES, true ) : Sobi::Url( 'error', true );
//		header( 'Cache-Control: no-cache, must-revalidate' );
//		header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' );
		$init = Input::Int( 'init' );
		$samples = $init && !( Input::Int( 'update' ) ) && !defined( 'SOBI_TRIMMED' );

		/** @var SPAdmView $view */
		$view = SPFactory::View( 'view', true );
		$view
			->assign( $init, 'init' )
			->assign( $samples, 'samples' )
			->addHidden( $home, 'redirect' )
			->determineTemplate( 'config', 'requirements' );

		$view->display();
	}

	/**
	 * @param $from
	 * @param $to
	 *
	 * @return bool
	 */
	protected function compareVersion( $from, $to ): bool
	{
		if ( $from[ 'major' ] > $to[ 'major' ] ) {
			return false;
		}
		else {
			if ( $from[ 'major' ] < $to[ 'major' ] ) {
				return true;
			}
		}
		if ( $from[ 'minor' ] > $to[ 'minor' ] ) {
			return false;
		}
		else {
			if ( $from[ 'minor' ] < $to[ 'minor' ] ) {
				return true;
			}
		}
		if ( $from[ 'build' ] > $to[ 'build' ] ) {
			return false;
		}

		return true;
	}
}
