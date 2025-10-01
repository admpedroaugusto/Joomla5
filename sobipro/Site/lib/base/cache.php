<?php
/**
 * @package SobiPro Library
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 16-Aug-2009 by Radek Suski
 * @modified 12 January 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
defined( 'SQLITE_ASSOC' ) || define( 'SQLITE_ASSOC', null );

use Joomla\CMS\Cache\Cache;
use Joomla\CMS\Factory as JFactory;
use Sobi\C;
use Sobi\Input\Input;
use Sobi\FileSystem\FileSystem;
use Sobi\Lib\Factory;
use Sobi\Utils\Arr;

/**
 * Class SPCache
 */
final class SPCache
{
	/* class SQLiteDatabase does no longer exist as of PHP 5.4.
	 New class is SQLite3 which is not compatible.
	To support SQLite3 all method calls need to be revised.
	So at the moment SobiPro supports only PDO. */

	protected $db = null;

	protected string $driver = C::ES;
	protected bool $enabled = true;
	protected bool $apc = true;
	protected string $cachePath = C::ES;
	protected string $cacheFile = C::ES;
	protected int $section = -1;
	protected bool $cachedView = false;
	protected array $check = [];
	protected array $requestStore = [];
	protected array $cacheViewQuery = [];
	protected array $cacheViewRequest = [];
	protected array $view = [ 'xml' => null, 'template' => C::ES, 'data' => [] ];

	protected const disableViewCache = [ 'entry.add', 'entry.edit', 'search.search', 'search.results', 'entry.disable', 'txt.js' ];
	protected const disableObjectCache = [ '.save', '.clone', '.payment', '.submit', '.approve', '.publish', '.icon' ];

	/**
	 * Singleton - returns instance of the config object.
	 *
	 * @param int $sid
	 *
	 * @return mixed
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function & getInstance( $sid = 0 )
	{
		if ( !$sid ) {
			$sid = (int) Sobi::Section();
		}
		if ( !$sid ) {
			$sid = -1;
		}

		static $cache = [];
		if ( !isset( $cache[ $sid ] ) || !( $cache[ $sid ] instanceof self ) ) {
			$cache[ $sid ] = new self( $sid );
		}

		return $cache[ $sid ];
	}

	/**
	 * Clears the database variable and deletes all cache files.
	 *
	 * @return void
	 */
	protected function close()
	{
		switch ( $this->driver ) {
			case 'SQLITE':
				unset( $this->db );
				//sqlite_close( $this->db );
				break;
			case 'PDO':
				break;
		}
		$this->db = null;

		/* delete the cache files if they exist */
		if ( $this->cachePath ) {
			$cache = scandir( $this->cachePath );
			if ( count( $cache ) ) {
				foreach ( $cache as $file ) {
					if ( FileSystem::GetExt( $file ) == 'db' ) {
						$cachefile = FileSystem::FixPath( "$this->cachePath/$file" );
						// we need an exception because these files are owned by Apache probably
						@unlink( $cachefile );
						if ( FileSystem::Exists( $cachefile ) ) {
							FileSystem::Delete( $cachefile );
						}
					}
				}
			}
		}
	}

	/**
	 * SPCache constructor.
	 *
	 * @param int $sid
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function __construct( $sid )
	{
		$this->section = $sid;
		$this->enabled = Sobi::Cfg( 'cache.l3_enabled', true );    // data accelerator
		$this->requestStore = $_REQUEST;

		$this->apc = Sobi::Cfg( 'cache.apc_enabled', false );
		if ( $this->apc ) {
			$this->apc = extension_loaded( 'apc' ) && function_exists( 'apc_fetch' );
		}

		$this->initialise();
	}

	/**
	 * Initializes the Data Accelerator.
	 *
	 * @param int $cache
	 *
	 * @return void
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function initialise( $cache = 0 )
	{
		if ( $this->enabled ) {    // if data accelerator enabled
			if ( FileSystem::Exists( SOBI_PATH . '/var/reset' ) ) {
				$this->cleanAll();
				FileSystem::Delete( SOBI_PATH . '/var/reset' );
				if ( SPLoader::path( 'etc/extensions', 'front', true, 'xml' ) ) {
					FileSystem::Delete( SPLoader::path( 'etc/extensions', 'front', false, 'xml' ) );
				}
			}

			$this->cachePath = Sobi::Cfg( 'cache.store', SOBI_PATH . '/var/cache/' );
			if ( !strlen( $this->cachePath ) ) {
				$this->cachePath = SOBI_PATH . '/var/cache/';
			}
			$cache = $cache ? : $this->section;
			$this->cacheFile = '.htCache_' . $cache . '.db';

			$init = !FileSystem::Exists( $this->cachePath . $this->cacheFile );
			if ( class_exists( 'SQLiteDatabase' ) ) {
				$msg = C::ES;
				$this->driver = 'SQLITE';
				try {
					$this->db = new SQLiteDatabase( $this->cachePath . $this->cacheFile, 0400, $msg );
					if ( strlen( $msg ) ) {
						Sobi::Error( 'cache', sprintf( 'SQLite error: %s', $msg ), C::WARNING, 0, __LINE__, __FILE__ );
						$this->enabled = false;
						$this->cleanAll();
					}
				}
				catch ( SQLiteException $e ) {
					Sobi::Error( 'cache', sprintf( 'SQLite error: %s', $e->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
					$this->enabled = false;
					$this->cleanAll();
				}
			}
			else {
				if ( class_exists( 'PDO' ) ) {
					try {
						$this->driver = 'PDO';
						$this->db = new PDO( 'sqlite:' . $this->cachePath . $this->cacheFile );
					}
					catch ( PDOException $e ) {
						Sobi::Error( 'cache', sprintf( 'SQLite database not supported. %s', $e->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
						$this->enabled = false;
						$this->cleanAll();
					}
				}

				/* neither SQLiteDatabase nor PDO drivers exist */
				else {
					Sobi::Error( 'cache', sprintf( 'SQLite database not supported' ), C::WARNING, 0, __LINE__, __FILE__ );
					$this->enabled = false;

					/* Disable the Data Accelerator */
					if ( defined( 'SOBIPRO_ADM' ) ) {
						SPFactory::config()->saveCfg( 'cache.l3_enabled', false );
					}
				}
			}
			if ( $init && $this->enabled ) {
				$this->init();
			}
		}
	}

	/**
	 * @param string $query
	 *
	 * @return bool
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function Query( string $query )
	{
		$retval = false;
		switch ( $this->driver ) {
			case 'SQLITE':
				try {
					if ( $sqlite = $this->db->query( $query, SQLITE_ASSOC ) ) {
						$retval = $sqlite->fetch();
					}
					else {
						Sobi::Error( 'cache', sprintf( 'SQLite error on query: %s', $query ), C::WARNING, 0, __LINE__, __FILE__ );

						return false;
					}
				}
				catch ( SQLiteException $x ) {
					Sobi::Error( 'cache', sprintf( 'SQLite error: %s', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
					$this->enabled = false;
					$this->cleanAll();

					return false;
				}
				break;
			case 'PDO':
				if ( $sqlite = $this->db->prepare( $query ) ) {
					try {
						$sqlite->execute();
						$retval = $sqlite->fetch( PDO::FETCH_ASSOC );
					}
						/* sometimes the execute() fails once */
					catch ( \Sobi\Error\Exception $x ) {
						$this->cleanAll();

						return false;
					}
				}
				else {
					Sobi::Error( 'cache', sprintf( 'SQLite error on query: %s. Error %s', $query, implode( "\n", $this->db->errorInfo() ) ), C::WARNING, 0, __LINE__, __FILE__ );
					$this->enabled = false;
					$this->cleanAll();

					return false;
				}
				break;
		}

		return $retval;
	}

	/**
	 * @param $query
	 *
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function Exec( $query )
	{
		switch ( $this->driver ) {
			case 'SQLITE':
				try {
					$this->db->queryExec( $query );
				}
				catch ( SQLiteException $x ) {
					Sobi::Error( 'cache', sprintf( 'SQLite error: %s', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
				}
				break;
			case 'PDO':
				try {
					$this->db->exec( $query );
				}
				catch ( RuntimeException $x ) {
					$this->close();
					$this->initialise();
				}
				break;
		}
	}

	/**
	 * Cleans the cache of a section.
	 *
	 * @param int $section - section id; if not given, current section will be used
	 * @param bool $system
	 *
	 * @return $this
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function & cleanSection( int $section = 0, bool $system = true ): self
	{
		$sid = $section ? : $this->section;

		/* clean the Joomla page cache */
		$this->cleanJoomlaCache();
		/* empty the vars and object caches from sqlite cache = empty the whole cache */
		if (/* $section != -1 &&*/ $this->enabled() ) {
			$this->Exec( "BEGIN; DELETE FROM vars; COMMIT;" );
			$this->Exec( "BEGIN; DELETE FROM objects; COMMIT;" );
		}
//		else {
//			if ( FileSystem::Exists( $this->cachePath . '.htCache_' . $sid . '.db' ) ) {
//				// we need an exception because these files are owned by Apache probably
//				@unlink( $this->cachePath . '.htCache_' . $sid . '.db' );
//				if ( FileSystem::Exists( $this->cachePath . '.htCache_' . $sid . '.db' ) ) {
//					FileSystem::Delete( $this->cachePath . '.htCache_' . $sid . '.db' );
//				}
//			}
//		}

		if ( $sid > 0 ) {
//			$this->cleanSection( -1 );
			$this->cleanApc();
			$this->cleanCategories( $sid );
		}
		if ( $system ) {
			SPFactory::message()->resetSystemMessages();
		}

		$this->cleanSectionXML( $this->section );

		$this->cleanDir( SPLoader::dirPath( 'var/js' ), 'js', true );
		$this->cleanDir( SPLoader::dirPath( 'var/css' ), 'css', true );
//		if ( $section == Sobi::Section() && $this->enabled() ) {
//			$this->initialise();
//		}

		return $this;
	}

	/**
	 * Cleans the category cache file and sqlite cache (if exists).
	 * Cleans either for all sections ($section = 0 and $this->section = 0 or -1) or
	 * for the given section ($section > 0 or $this->section > 0 (= cache is initialised))
	 *
	 * @param int $section
	 *
	 * @return $this
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function & cleanCategories( int $section = 0 ): self
	{
		$sid = $section ? : $this->section;

		if ( $sid > 0 ) {
			/* delete only the category tree file for the given section */
			$catFilename = SOBI_PATH . '/etc/categories/categories_' . Sobi::Lang( false ) . '_' . $sid . '.json';
			if ( FileSystem::Exists( $catFilename ) ) {
				FileSystem::Delete( $catFilename );
			}
			/* delete the cache vars only if the sqlite cache exists */
			SPFactory::cache()->deleteVar( 'categories_tree_adm', $sid );
			SPFactory::cache()->deleteVar( 'categories_tree_front', $sid );
			//SPFactory::cache()->addVar( null, 'categories_tree', (int) Sobi::Section() );
		}
		else {
			/* delete category tree files for all sections */
			$this->cleanDir( SPLoader::dirPath( 'etc/categories' ), -1, true );
			/* the cache vars do not need to be deleted as the cache file does no longer exist */
		}

		return $this;
	}

	/**
	 * Cleans cached variables of a section.
	 *
	 * @param int $section - section id; if not given, current section will be used
	 *
	 * @return $this
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function & purgeSectionVars( int $section = 0 ): self
	{
		$this->cleanFiles();

		$section = $section ? : $this->section;
		if ( $this->enabled() ) {
			$this->Exec( "BEGIN; DELETE FROM vars WHERE( section = '$section' ); COMMIT;" );
		}
		$this->cleanXMLLists( $section );
		$this->cleanApc();

		return $this;
	}

	/**
	 * Stores a variable in the cache.
	 *
	 * @param mixed $var - variable to store
	 * @param string $id - identifier
	 * @param int $sid - id of an object
	 * @param string $lang - language
	 * @param int $section - section id
	 *
	 * @return $this
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function & addVar( $var, string $id, $sid = 0, string $lang = C::ES, $section = 0 ): self
	{
		if ( $this->enabled() ) {
			if ( !$var ) {
				$var = C::NO_VALUE;
			}
			$section = $section ? : $this->section;
			$sid = $sid ? : $section;
			$sid = ( int ) $sid;

			$lang = $lang ? : Sobi::Lang( false );

			$checksum = null; //md5( serialize( $var ) );
			if ( $this->apc ) {
				apc_store( "com_sobipro_var_{$sid}_{$id}_$lang", $var );
			}
			$var = SPConfig::serialize( $var );
			$md5string = md5( $var );

			$this->Exec( "BEGIN; REPLACE INTO vars ( name, validtime, section, sid, lang, params, checksum, md5, data ) VALUES( '$id', '0', '$section', '$sid', '$lang', NULL, '$checksum', '$md5string', '$var' ); COMMIT;" );
		}

		return $this;
	}

	/**
	 * Returns a variable stored in the cache.
	 *
	 * @param string $id
	 * @param int $sid
	 * @param string $lang
	 * @param int $section
	 *
	 * @return bool|mixed - variable on success or false if not found
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function getVar( string $id, int $sid = 0, string $lang = C::ES, int $section = 0 )
	{
		if ( $this->enabled() ) {
			$section = $section ? : $this->section;
			$sid = $sid ? : $section;
			$sid = ( int ) $sid;

			$lang = $lang ? : Sobi::Lang( false );

			$apc = $var = false;
			if ( $this->apc ) {
				$var = apc_fetch( "com_sobipro_var_{$sid}_{$id}_$lang", $apc );
			}
			if ( !$apc ) {
				$result = $this->Query( "SELECT * FROM vars WHERE( name = '$id' AND lang = '$lang' AND section = '$section' AND sid = '$sid' )" );
				if ( !is_array( $result ) || !count( $result ) || !strlen( $result[ 'data' ] ) ) {
					return false;
				}
				if ( $result[ 'md5' ] != md5( $result[ 'data' ] ) ) {
					Sobi::Error( 'cache', SPLang::e( 'Checksum of the encoded variable does not match' ), C::WARNING, 0, __LINE__, __FILE__ );

					return false;
				}
				$var = SPConfig::unserialize( $result[ 'data' ] );
			}

			return $var;
		}
		else {
			return false;
		}
	}

	/**
	 * Removes a stored variable from the cache.
	 *
	 * @param string $id - identifier
	 * @param int $section - section id
	 * @param string $lang
	 * @param int $sid
	 *
	 * @return $this
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 * @internal param string $lang - language
	 */
	public function & deleteVar( string $id, int $section = 0, string $lang = C::ES, int $sid = 0 ): self
	{
		$this->cleanJoomlaCache();
		if ( $this->enabled() ) {
			$lang = $lang ? : Sobi::Lang( false );
			/* if the var should be deleted in the global cache, but we are in a section cache, then reinit */
			$reinit = false;
			if ( $section == -1 && $this->section > 0 ) {
				$this->initialise( -1 );
				$reinit = true;
			}
			else {
				$section = $section ? : $this->section;
			}
			if ( $sid ) {
				$this->Exec( "BEGIN; DELETE FROM vars WHERE( name LIKE '$id%' AND section = '$section' AND sid = '$sid' AND lang = '$lang' ); COMMIT;" );
			}
			else {
				$this->Exec( "BEGIN; DELETE FROM vars WHERE( name = '$id' AND section = '$section' AND lang = '$lang' ); COMMIT;" );
			}
			/* set if back to section cache */
			if ( $reinit ) {
				$this->initialise();
			}
		}

		return $this;
	}

	/**
	 * Stores an object in to the cache.
	 *
	 * @param mixed $obj - object to store
	 * @param string $type - type of object entry/category/section
	 * @param int $id - id of the object
	 * @param int $sid
	 * @param bool $force
	 *
	 * @return $this
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function & addObj( $obj, string $type, int $id, int $sid = 0, bool $force = false ): self
	{
		if ( $this->enabled( !$force ) ) {
			static $startTime = 0;
			if ( !$startTime && class_exists( 'Sobi' ) ) {
				$start = Sobi::Reg( 'start' );
				if ( $start ) {
					$startTime = $start[ 1 ];
				}
			}
			// storing need time - if we are over five seconds - skip
			$time = microtime( true ) - $startTime;
			if ( !defined( 'SOBIPRO_ADM' ) && !$force && $time > 50 ) {
				return $this;
			}

			/* Radek: it was the idea that if entry has been taken from cache, and do not report any changes - it doesn't have to be stored again. But I'm not so sure if this is a good idea any longer
			so let's skip it and see what's going to happen.
			Tue, Feb 19, 2013 14:09:52
			it makes sense - otherwise the cache is being invalidated again and again
			anyway stupid solution -  I have to reconsider it therefore @todo */
			if ( $type == 'entry' ) {
				// entry has to report if it should be re-validate
				if ( !isset( $this->check[ $type ][ $id ] ) || !$this->check[ $type ][ $id ] ) {
					return $this;
				}
			}

			$id = ( int ) $id;
			$sid = $sid ? : $this->section;
			$sid = ( int ) $sid;
			$loaded = serialize( SPLoader::getLoaded() );
			$lang = Sobi::Lang( false );
			$checksum = C::ES; //md5( serialize( $obj ) );
			if ( $this->apc ) {
				$var = [ 'obj' => $obj, 'classes' => $loaded ];
				apc_store( "com_sobipro_{$sid}_{$id}_{$type}_$lang", $var );
			}

			$obj = SPConfig::serialize( $obj );
			$md5string = md5( $obj );
			// the command is a "REPLACE" so there is actually no reason for deleting it anyway
			// the "deleteObj" causing however a chain reaction which would delete a lot of other things, so it doesn't make any sense here
//			$this->deleteObj( $type, $id, $sid );
			$this->Exec( "BEGIN; REPLACE INTO objects ( type, validtime, id, sid, lang, params, checksum, md5, data, classes ) VALUES( '$type', '0', '$id', '$sid', '$lang', NULL, '$checksum', '$md5string', '$obj', '$loaded' ); COMMIT;" );

			$this->cleanJoomlaCache();
		}

		return $this;
	}

	/**
	 * Removes a stored object from the cache.
	 *
	 * @param string $type - type of object entry/category/section
	 * @param $id
	 * @param int $sid
	 * @param string $lang
	 *
	 * @return $this
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function & deleteObj( string $type, $id, int $sid = 0, string $lang = C::ES ): self
	{
		$this->cleanJoomlaCache();

		$reinit = false;
		if ( $this->enabled() ) {
			if ( $id && $this->section == -1 ) {
				$this->section = $id == -1 ? $id : SPFactory::config()->getParentPathSection( $id );
//				$this->section = count( $section ) ? $section[ 0 ] : $id;
				$this->initialise();
				$reinit = true;
			}
			$lang = $lang ? : Sobi::Lang( false );
			$sid = $sid ? : $this->section;
			$sid = ( int ) $sid;

			$this->Exec( "BEGIN; DELETE FROM objects WHERE( type LIKE '$type%' AND id = '$id' AND sid = '$sid' AND lang = '$lang' ); COMMIT;" );
			if ( $type == 'entry' ) {
				$this->Exec( "
					BEGIN;
						DELETE FROM objects WHERE( type = 'field_data' AND id = '$id' AND lang = '$lang' );
						DELETE FROM objects WHERE( type = 'entry_row' AND id = '$id' );
					COMMIT;
				"
				);
//				$a = "BEGIN; DELETE FROM objects WHERE( type = 'entry_row' AND id = '{$id}' ); COMMIT;";
			}
		}
		$this->cleanXMLRelations( $id );

		if ( $reinit ) {
			$this->section = -1;
			$this->initialise();
		}

		return $this;
	}

	/**
	 * @throws \Sobi\Error\Exception
	 * @throws \SPException
	 */
	public function & deleteGlobalObjs( string $lang = C::ES ): self
	{
		if ( $this->enabled() ) {
			$this->initialise( -1 );
			$lang = $lang ? : Sobi::Lang( false );

			$this->Exec( "BEGIN; DELETE FROM objects WHERE( sid = '-1' AND lang = '$lang' ); COMMIT;" );

			$this->initialise();
		}

		return $this;
	}

	/**
	 * @param string $type
	 * @param int $id
	 * @param int $sid
	 * @param bool $force
	 *
	 * @return bool|mixed
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function getObj( string $type, int $id, int $sid = 0, bool $force = false )
	{
		if ( $this->enabled( !$force ) ) {
			$sid = $sid ? : $this->section;
			$sid = ( int ) $sid;
			$id = ( int ) $id;
			$lang = Sobi::Lang( false );

			$apc = false;
			if ( $this->apc ) {
				$var = apc_fetch( "com_sobipro_{$sid}_{$id}_{$type}_$lang", $apc );
				if ( isset( $var[ 'classes' ] ) ) {
					SPLoader::wakeUp( unserialize( $var[ 'classes' ] ) );
				}
			}
			if ( !$apc ) {
				$result = $this->Query( "SELECT * FROM objects WHERE( type = '$type' AND id = '$id' AND lang = '$lang' AND sid = '$sid' )" );
				if ( !is_array( $result ) || !count( $result ) ) {
					return false;
				}
				if ( $result[ 'classes' ] ) {
					SPLoader::wakeUp( unserialize( $result[ 'classes' ] ) );
				}
				if ( $result[ 'md5' ] != md5( $result[ 'data' ] ) ) {
					Sobi::Error( 'cache', SPLang::e( 'Checksum of the encoded data does not match' ), C::WARNING, 0, __LINE__, __FILE__ );

					return false;
				}
				$var = SPConfig::unserialize( $result[ 'data' ] );
			}
			else {
				$var = $var[ 'obj' ];
			}
			$this->check[ $type ][ $id ] = false;

			return $var;
		}
		else {
			return false;
		}
	}

	/**
	 * @param $id
	 * @param string $type
	 */
	public function revalidate( $id, string $type = 'entry' )
	{
		$this->check[ $type ][ $id ] = true;
	}

	/**
	 * @param bool $obj
	 *
	 * @return bool
	 * @throws \SPException
	 */
	protected function enabled( bool $obj = false ): bool
	{
		if ( $obj ) {
			if ( $this->enabled && $this->driver && class_exists( 'SPConfig' ) && Sobi::Cfg( 'cache.l3_enabled' ) ) {
				$currentTask = Input::Task();
				foreach ( self::disableObjectCache as $task ) {
					if ( strstr( $currentTask, $task ) ) {
						return false;
					}
				}

				return true;
			}
			else {
				return false;
			}
		}
		else {
			return $this->enabled && $this->driver && class_exists( 'SPConfig' );
		}
	}

	/**
	 * @return void
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function init()
	{
		$this->Exec(
			"
				BEGIN;
				CREATE TABLE vars ( name CHAR(150), validtime int(11), section int(11) default NULL, sid int(11) default NULL, lang CHAR(50) default NULL, params text, checksum CHAR(150) default NULL, md5 CHAR(150) default NULL, data blob, PRIMARY KEY( name, section, sid, lang ) );
				CREATE INDEX vars_name on vars( name );
				CREATE INDEX vars_section on vars( section );
				CREATE INDEX vars_sid on vars( sid );
				CREATE TABLE objects ( type CHAR(150), validtime int(11), id int(11) default NULL, sid int(11) default NULL, lang CHAR(50) default NULL, params text, checksum CHAR(150) default NULL, md5 CHAR(150) default NULL, data blob, classes text, PRIMARY KEY( type, id, sid, lang ) );
				CREATE INDEX objects_name on objects( type );
				CREATE INDEX objects_section on objects( id );
				CREATE INDEX objects_sid on objects( sid );
				COMMIT;
				"
		);
	}

	/**
	 * Removes all sqlite caches.
	 *
	 * @return $this
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function & cleanAll(): self
	{
		/* remove Sqlite files (data accelerator) */
		$this->close();

		/* clean the XML cache */
		if ( Sobi::Cfg( 'cache.xml_enabled' ) ) {
			Factory::Db()
				->truncate( 'spdb_view_cache_relation' )
				->truncate( 'spdb_view_cache' );

			$this->cleanDir( SPLoader::dirPath( 'var/xml', 'front' ), 'xml', true );
		}

		/* clean the Joomla page cache */
		$this->cleanJoomlaCache();

		/* delete all temporary, minified js and css files */
		$this->cleanFiles( true );

		/* delete the category files */
		$this->cleanCategories();
		$this->cleanApc();

		return $this;
	}

	/**
	 * Cleans all temporary files.
	 *
	 * @param $dir
	 * @param $ext -> the extension of the files to delete. -1 = all files
	 * @param bool $force
	 * @param bool $recursive
	 */
	protected function cleanDir( $dir, $ext, bool $force = false, bool $recursive = false )
	{
		if ( $dir ) {
			$files = scandir( $dir );
			if ( count( $files ) ) {
				foreach ( $files as $file ) {
					$fixedFile = FileSystem::FixPath( "$dir/$file" );
					if ( $file != '.' && $file != '..'
						&& ( FileSystem::GetExt( $file ) == $ext || $ext == -1 )
						&& ( $force || ( time() - filemtime( $fixedFile ) > ( 60 * 60 * 24 * 7 ) ) )
					) {
						if ( is_file( $fixedFile ) ) {
							FileSystem::Delete( $fixedFile );
						}
						else {
							if ( $recursive ) {
								$this->cleanDir( "$dir/$file", $ext, $force );
								FileSystem::Delete( $fixedFile );
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Cleans the Joomla cache, temporary files, stored searches older than 7 days,
	 * and the files with applications to update.
	 *
	 * @param bool $force
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function cleanFiles( bool $force = false )
	{
		$this->cleanJoomlaCache();

		$this->cleanDir( SPLoader::dirPath( 'var/js' ), 'js', $force );
		$this->cleanDir( SPLoader::dirPath( 'var/css' ), 'css', $force );
		$this->cleanDir( SPLoader::dirPath( 'tmp/edit' ), -1, $force, true );
		$this->cleanDir( SPLoader::dirPath( 'tmp/img' ), -1, $force, true );
		$this->cleanDir( SPLoader::dirPath( 'tmp/files' ), -1, $force, true );
		$this->cleanDir( SPLoader::dirPath( 'tmp/install' ), -1, $force, true );
		$this->cleanDir( SPLoader::dirPath( 'tmp' ), -1, $force );

		try {
			Factory::Db()->delete( 'spdb_search', [ 'lastActive<' => 'FUNCTION:DATE_SUB( CURDATE() , INTERVAL 7 DAY )' ] );
		}
		catch ( Exception $x ) {
			Sobi::Error( 'cache', SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}

		$updatesDef = SPLoader::path( 'etc/updates', 'front', false, 'xml' );
		if ( FileSystem::Exists( $updatesDef ) ) {
			FileSystem::Delete( $updatesDef );
		}
	}

	/**
	 * Shows the XML cache if available.
	 *
	 * @return array|bool
	 * @throws \Sobi\Error\Exception|\SPException
	 */
	public function view()
	{
		if ( !Sobi::Cfg( 'cache.xml_enabled' ) || Sobi::Reg( 'break_cache_view' ) || ( Sobi::My( 'id' ) && Sobi::Cfg( 'cache.xml_no_reg' ) ) ) {
			return false;
		}
		if ( !in_array( Input::Task(), self::disableViewCache ) ) {
			$cacheFile = null;
			$file = null;
			foreach ( self::disableObjectCache as $task ) {
				if ( strstr( Input::Task(), $task ) ) {
					return false;
				}
			}
			$query = $this->viewRequest();
			/** here comes an exception for the linked entries */
			$link = [];
			if ( isset( JFactory::getApplication()->getMenu()->getActive()->link ) ) {
				parse_str( JFactory::getApplication()->getMenu()->getActive()->link, $link );
			}

			/** now we know that it is directly linked but not if it is an entry link */
			if ( isset( $link[ 'sid' ] ) && $link[ 'sid' ] == Input::Sid() ) {
				$request = $this->cacheViewRequest;
				$request[ 'Itemid' ] = Input::Int( 'Itemid' );
				$query[ 'request' ] = str_replace( '"', C::ES, json_encode( $request ) );
				$query[ 'task' ] = 'entry.details';
				$file = Factory::Db()
					->select( [ 'fileName', 'template', 'configFile', 'cid' ], 'spdb_view_cache', $query )
					->loadRow();
			}
			if ( !$file ) {
				$query = $this->viewRequest();
				$file = Factory::Db()
					->select( [ 'fileName', 'template', 'configFile', 'cid' ], 'spdb_view_cache', $query )
					->loadRow();
			}
			if ( $file ) {
				$cacheFile = SPLoader::path( 'var/xml/' . $file[ 0 ], 'front', true, 'xml' );
			}
			if ( !$cacheFile ) {
				return false;
			}
			$ini = [];
			if ( $file[ 2 ] ) {
				$configs = json_decode( str_replace( "'", '"', $file[ 2 ] ) );
				if ( count( $configs ) ) {
					$template = SPLoader::translateDirPath( Sobi::Cfg( 'section.template', SPC::DEFAULT_TEMPLATE ), 'templates' );
					foreach ( $configs as $config ) {
						$configFile = $template . $config->file;
						if ( file_exists( $configFile ) ) {
							if ( md5_file( $configFile ) != $config->checksum ) {
								return false;
							}
							$ini[] = $configFile;
						}
						else {
							return false;
						}
					}
				}
			}
			$xml = new DOMDocument();
			if ( !$xml->load( $cacheFile ) ) {
				return false;
			}
			$this->cachedView = true;

			return [ 'xml' => $xml, 'template' => $file[ 1 ], 'config' => $ini, 'cid' => $file[ 3 ] ];
		}
		else {
			return false;
		}
	}

	/**
	 * @return array
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function viewRequest(): array
	{
		if ( !count( $this->cacheViewQuery ) || Sobi::Reg( 'cache_view_recreate_request' ) ) {
			$request = [];
			if ( count( $this->requestStore ) ) {
				$keys = array_keys( $this->requestStore );
				foreach ( $keys as $k ) {
					if ( !is_array( $_REQUEST[ $k ] ) ) {
						$request[ $k ] = Input::String( $k );
					}
				}
			}

			$reserved = [ 'site', 'task', 'sid', 'dbg', 'Itemid', 'option', 'tmpl', 'format', 'crawl', 'language', 'lang' ];
			if ( Sobi::Reg( 'cache_view_add_itemid' ) ) {
				unset( $reserved[ array_search( 'Itemid', $reserved ) ] );
			}
			foreach ( $reserved as $var ) {
				if ( isset( $request[ $var ] ) ) {
					unset( $request[ $var ] );
				}
			}
			$this->cacheViewRequest = $request;
			$this->cacheViewQuery = [
				'section'    => Sobi::Section(),
				'sid'        => Input::Sid(),
				'task'       => Input::Task(),
				'site'       => Input::Int( 'site', 'request', 1 ),
				'request'    => str_replace( '"', C::ES, json_encode( $request ) ),
				'language'   => Sobi::Lang(),
				'userGroups' => str_replace( '"', C::ES, json_encode( Sobi::My( 'groups' ) ) ),
			];
		}

		return $this->cacheViewQuery;
	}

	/**
	 * @param int $section
	 *
	 * @throws \Sobi\Error\Exception
	 */
	protected function cleanXMLLists( int $section )
	{
		$section = $section ? : $this->section;
		if ( Sobi::Cfg( 'cache.xml_enabled' ) ) {
			$xml = Factory::Db()
				->select( [ 'cid', 'fileName' ], 'spdb_view_cache', [ 'section' => $section, 'task' => '%list.%' ] )
				->loadAssocList();
			$this->cleanXML( $xml );

			$xml = Factory::Db()
				->select( [ 'cid', 'fileName' ], 'spdb_view_cache', [ 'sid' => $section ] )
				->loadAssocList();
			$this->cleanXML( $xml );
		}
	}

	/**
	 * @param $section
	 * @param string $task
	 *
	 * @throws \Sobi\Error\Exception
	 */
	public function cleanXMLTask( $section, string $task )
	{
		if ( Sobi::Cfg( 'cache.xml_enabled' ) ) {
			$xml = Factory::Db()
				->select( [ 'cid', 'fileName' ], 'spdb_view_cache', [ 'section' => $section, 'task' => $task ] )
				->loadAssocList();
			$this->cleanXML( $xml );
		}
	}

	/**
	 * @param $section
	 *
	 * @throws \Sobi\Error\Exception|\SPException
	 */
	public function cleanSectionXML( $section )
	{
		if ( Sobi::Cfg( 'cache.xml_enabled' ) ) {
			$xml = Factory::Db()
				->select( [ 'cid', 'fileName' ], 'spdb_view_cache', [ 'section' => $section ] )
				->loadAssocList();
			$this->cleanXML( $xml );
		}
	}

	/**
	 * @param array $xml
	 *
	 * @throws \Sobi\Error\Exception
	 */
	protected function cleanXML( array $xml )
	{
		$this->cleanJoomlaCache();

		if ( count( $xml ) ) {
			$relations = [];
			foreach ( $xml as $cache ) {
				$file = SPLoader::path( 'var/xml/' . $cache[ 'fileName' ], 'front', true, 'xml' );
				if ( $file ) {
					FileSystem::Delete( $file );
				}
				$relations[] = $cache[ 'cid' ];
			}
			if ( count( $relations ) ) {
				Factory::Db()
					->delete( 'spdb_view_cache_relation', [ 'cid' => $relations ] )
					->delete( 'spdb_view_cache', [ 'cid' => $relations ] );
			}
		}
	}

	/**
	 * @param array|int $sid
	 *
	 * @return $this
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function & cleanXMLRelations( $sid ): self
	{
		if ( Sobi::Cfg( 'cache.xml_enabled' ) ) {
			if ( is_array( $sid ) ) {
				foreach ( $sid as $id ) {
					$this->cleanXMLRelations( $id );
				}
			}
			else {
				$xml = Factory::Db()
					->select( 'cid', 'spdb_view_cache_relation', [ 'sid' => $sid ] )
					->loadResultArray();
				if ( count( $xml ) ) {
					$lang = Sobi::Lang( false );
					$files = Factory::Db()
						->select( 'fileName', 'spdb_view_cache', [ 'cid' => $xml, 'language' => $lang ] )
						->loadResultArray();
					foreach ( $files as $file ) {
						$file = SPLoader::path( 'var.xml.' . $file, 'front', false, 'xml' );
						if ( $file ) {
							FileSystem::Delete( $file );
						}
					}
					Factory::Db()
						->delete( 'spdb_view_cache_relation', [ 'cid' => $xml ] )
						->delete( 'spdb_view_cache', [ 'cid' => $xml ] );
				}
			}
		}

		return $this;
	}

	/**
	 * Adds view to the XML cache.
	 *
	 * @param $xml
	 * @param $template
	 * @param array $data
	 *
	 * @return void
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function addXMLView( $xml, $template, array $data = [] )
	{
		if ( !Sobi::Cfg( 'cache.xml_enabled' ) || $this->cachedView || Sobi::Reg( 'break_cache_view' ) || ( Sobi::My( 'id' ) && Sobi::Cfg( 'cache.xml_no_reg' ) ) ) {
			return;
		}

		if ( !in_array( Input::Task( 'get' ), self::disableViewCache ) ) {
			foreach ( self::disableObjectCache as $task ) {
				if ( strstr( Input::Task(), $task ) ) {
					return;
				}
			}
			if ( count( $_REQUEST ) ) {
				foreach ( $_REQUEST as $key => $value ) {
					if ( !isset( $this->requestStore[ $key ] ) ) {
						$data[ 'request' ][ $key ] = Input::String( $key );
					}
				}
			}
			$data[ 'pathway' ] = SPFactory::mainframe()->getPathway();
			$this->view[ 'xml' ] = $xml;
			$this->view[ 'template' ] = $template;
			$this->view[ 'data' ] = $data;
		}
	}

	/**
	 * XML Cache.
	 *
	 * @param array $head
	 *
	 * @return void
	 * @throws SPException
	 * @throws \Sobi\Error\Exception|\DOMException
	 */
	public function storeXMLView( array $head )
	{
		if ( !Sobi::Cfg( 'cache.xml_enabled' ) || $this->cachedView || ( Sobi::My( 'id' ) && Sobi::Cfg( 'cache.xml_no_reg' ) ) ) {
			return;
		}

		if ( $this->view[ 'xml' ] ) {
			$xml = $this->view[ 'xml' ];
			$template = Sobi::Reg( 'cache_view_template' );
			if ( !$template ) {
				$template = $this->view[ 'template' ];
				$template = str_replace( SPLoader::translateDirPath( Sobi::Cfg( 'section.template', SPC::DEFAULT_TEMPLATE ), 'templates' ), C::ES, $template );
			}
			$root = $xml->documentElement;
			$root->removeChild( $root->getElementsByTagName( 'visitor' )->item( 0 ) );
			if ( $root->getElementsByTagName( 'messages' )->length ) {
				$root->removeChild( $root->getElementsByTagName( 'messages' )->item( 0 ) );
			}
			/** @var DOMDocument $header */
			$arrUtils = new Arr();
			$header = $arrUtils->toXML( $head, 'header', true );
			$root->appendChild( $xml->importNode( $header->documentElement, true ) );
			if ( $this->view[ 'data' ] && count( $this->view[ 'data' ] ) ) {
				$data = $arrUtils->toXML( $this->view[ 'data' ], 'cache-data', true );
				$root->appendChild( $xml->importNode( $data->documentElement, true ) );
			}
			$request = $this->viewRequest();
			$request[ 'template' ] = $template;
			$configFiles = SPFactory::registry()->get( 'template_config' );
			$request[ 'configFile' ] = str_replace( '"', "'", json_encode( $configFiles ) );
			$request[ 'cid' ] = 0;
			$request[ 'created' ] = 'FUNCTION:NOW()';
			$fileName = md5( serialize( $request ) );
			$request[ 'fileName' ] = $fileName;

			$filePath = SPLoader::path( 'var/xml/' . $fileName, 'front', false, 'xml' );
			$content = $xml->saveXML();
			$content = str_replace( '&nbsp;', '&#160;', $content );
			$content = preg_replace( '/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', C::ES, $content );
			$matches = [];
			preg_match_all( '/<(category|entry|subcategory)[^>]*id="(\d{1,})"/', $content, $matches );
			try {
				$cid = Factory::Db()
					->insert( 'spdb_view_cache', $request, false, true )
					->insertId();
				$relations = [ Input::Sid() => [ 'cid' => $cid, 'sid' => Input::Sid() ] ];
				if ( isset( $matches[ 2 ] ) ) {
					$ids = array_unique( $matches[ 2 ] );
					foreach ( $ids as $sid ) {
						$relations[ $sid ] = [ 'cid' => $cid, 'sid' => $sid ];
					}
				}
				Factory::Db()->insertArray( 'spdb_view_cache_relation', $relations );
				FileSystem::Write( $filePath, $content );
			}
			catch ( Sobi\Error\Exception $x ) {
				Sobi::Error( 'XML-Cache', $x->getMessage() );
			}
		}
	}

	/**
	 * @param bool $enabled
	 *
	 * @return $this
	 * @throws \Exception
	 */
	public function & setJoomlaCaching( bool $enabled ): self
	{
		JFactory::getCache()->cache->setCaching( $enabled );
		if ( !$enabled && ( SOBI_CMS == 'joomla3' || SOBI_CMS == 'joomla4' ) ) {
			JFactory::getApplication()->allowCache( false );
		}

		return $this;
	}

	/**
	 * Cleans the Joomla cache.
	 */
	protected function cleanJoomlaCache()
	{
		static $go = true;
		if ( $go ) {
			$go = false;
			$options = [
				'defaultgroup' => 'page',
				'storage'      => JFactory::getConfig()->get( 'cache_handler', '' ),
				'caching'      => true,
				'cachebase'    => JFactory::getConfig()->get( 'cache_path', JPATH_SITE . '/cache' ),
			];
			Cache::getInstance( C::ES, $options )->cache->clean( 'page' );
		}
	}

	/**
	 * @return void
	 */
	protected function cleanApc()
	{
		if ( $this->apc ) {
			$info = apc_cache_info( 'user' );
			foreach ( $info[ 'cache_list' ] as $obj ) {
				if ( isset( $obj[ 'key' ] ) && strstr( $obj[ 'key' ], 'com_sobipro' ) ) {
					apc_delete( $obj[ 'key' ] );
				}
				else {
					if ( isset( $obj[ 'info' ] ) && strstr( $obj[ 'info' ], 'com_sobipro' ) ) {
						apc_delete( $obj[ 'info' ] );
					}
				}
			}
		}
	}
}
