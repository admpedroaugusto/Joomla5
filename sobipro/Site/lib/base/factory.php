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
 * @created 11-Jan-2009 by Radek Suski
 * @modified 25 September 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadClass( 'base.registry' );
SPLoader::loadClass( 'base.mainframe' );
SPLoader::loadClass( 'base.config' );
SPLoader::loadClass( 'base.cache' );
//SPLoader::loadClass( 'base.database' );
SPLoader::loadClass( 'base.user' );
SPLoader::loadClass( 'cms.base.user' );
SPLoader::loadClass( 'cms.base.lang' );
SPLoader::loadClass( 'base.header' );

use Sobi\C;
use Sobi\Lib\Factory;
use SobiPro\Controllers\Entry as EntryCtrl;
use SobiPro\Models\Entry as EntryModel;
use Sobi\Error\Exception;

/**
 * Class SPFactory
 */
abstract class SPFactory
{
	/**
	 * @return mixed
	 * @throws SPException
	 */
	public static function & mainframe()
	{
		static $class = null;
		if ( !$class ) {
			$class = SPLoader::loadClass( 'cms.base.mainframe' );
		}

		return $class::getInstance();
	}

	/**
	 * @param int $sid
	 *
	 * @return mixed
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function & cache( $sid = 0 )
	{
		if ( !Sobi::Section() && $sid ) {
			try {
				$relations = self::getCategoryRelations( $sid );
				if ( count( $relations ) ) {
					SPFactory::registry()->set( 'current_relations', $relations );
					foreach ( $relations as $pid => $path ) {
						if ( count( $path ) ) {
							foreach ( $path as $parent ) {
								if ( $parent[ 'type' ] == 'section' ) {
									SPFactory::registry()->set( 'current_section', $parent[ 'id' ] );
									// when section found, jump out of both loops
									break 2;
								}
							}
						}
					}
				}
				else {
					$type = Factory::Db()
						->select( 'oType', 'spdb_object', [ 'id' => $sid ] )
						->loadResult();
					if ( $type == 'section' ) {
						SPFactory::registry()->set( 'current_section', $sid );
					}
					else {
						if ( Sobi::Cfg( 'debug', false ) ) {
							trigger_error( sprintf( 'Object with the id %s does not return any results. Most likely an invalid relation.', $sid ), C::NOTICE );
						}
					}
				}
			}
			catch ( Sobi\Error\Exception $x ) {
				if ( $section = SPFactory::config()->getSectionLegacy( $sid ) ) {
					SPFactory::registry()->set( 'current_section', $section );
				}
			}
		}

		return SPCache::getInstance( $sid );
	}

	/**
	 * @param int $id
	 *
	 * @return array
	 * @throws \Sobi\Error\Exception
	 */
	public static function getCategoryRelations( int $id ): array
	{
		$relations = Factory::Db()->procedure( 'SPGetRelationsPath', [ $id ] );
		/* Sort the relations by index 'spParent' as it is possible that a parent category id may be higher than a subcategory id. */
		if ( count( $relations ) ) {
			/* this also moves the section on top of the array */
			foreach ( $relations as $index2 => $relation ) {
				$list = Sobi::sortByIndex( $relation, 'spParent', SORT_ASC );
				$relations[ $index2 ] = $list;
			}
		}

		return $relations;
	}

	/**
	 * @return mixed
	 * @throws SPException
	 */
	public static function CmsHelper()
	{
		static $class = null;
		if ( !$class ) {
			$class = SPLoader::loadClass( 'cms.base.helper' );
		}

		return $class::getInstance();
	}

	/**
	 * @return SPConfig
	 * @throws \SPException
	 */
	public static function & config()
	{
		return SPConfig::getInstance();
	}

	/**
	 * @deprecated SINCE 2.0
	 * @use Sobi\Lib\Factory::Db()
	 */
	public static function & db()
	{
		return Factory::Db();
	}

	/**
	 * @return \SPUser
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function & user()
	{
		return SPUser::getCurrent();
	}

	/**
	 * @return SPRegistry
	 */
	public static function & registry()
	{
		return SPRegistry::getInstance();
	}

	/**
	 * @return SPPayment
	 * @throws SPException
	 */
	public static function & payment()
	{
		SPLoader::loadClass( 'services.payment' );

		return SPPayment::getInstance();
	}

	/**
	 * @return SpAdmToolbar|null
	 * @throws SPException
	 */
	public static function & AdmToolbar()
	{
		SPLoader::loadClass( 'views.adm.toolbar' );

		return SpAdmToolbar::getInstance();
	}

	/**
	 * @return SPLang
	 */
	public static function & lang()
	{
		return SPLang::getInstance();
	}

	/**
	 * @return SPHeader
	 */
	public static function & header()
	{
		return SPHeader::getInstance();
	}

	/**
	 * @return SPMessage
	 * @throws SPException
	 */
	public static function & message()
	{
		SPLoader::loadClass( 'base.message' );

		return SPMessage::getInstance();
	}

	/**
	 * @return \SPHistory
	 * @throws \SPException
	 */
	public static function & history()
	{
		SPLoader::loadClass( 'base.history' );

		return SPHistory::getInstance();
	}

	/**
	 * @return \SPFilter
	 * @throws \SPException
	 */
	public static function & filter()
	{
		SPLoader::loadClass( 'ctrl.adm.filter' );

		return SPFilter::getInstance();
	}

	/**
	 * @return mixed
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public static function & currentSection()
	{
		SPLoader::loadModel( 'section' );

		return SPSection::getInstance();
	}

	/**
	 * @param $file
	 * @param null $options
	 *
	 * @return DOMDocument
	 */
	public static function & LoadXML( $file, $options = 0 )
	{
		$d = new DOMDocument();
		$d->load( realpath( $file ), $options );

		return $d;
	}

	/**
	 * @param $id
	 *
	 * @return bool|mixed
	 * @throws \SPException
	 */
	public static function object( $id )
	{
		static $instances = [];
		if ( !isset( $instances[ $id ] ) ) {
			try {
				$instances[ $id ] = Factory::Db()
					->select( '*', 'spdb_object', [ 'id' => $id ] )
					->loadObject();
			}
			catch ( Sobi\Error\Exception $x ) {
				Sobi::Error( 'Factory', 'cannot_get_object', C::WARNING, 500, __LINE__, __CLASS__, $x->getMessage() );
			}
		}

		return count( (array) $instances[ $id ] ) ? $instances[ $id ] : false;
	}

	/**
	 * @param $class
	 *
	 * @return SPDBObject
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public static function & Instance( $class )
	{
		static $loaded = [];
		if ( !( isset( $loaded[ $class ] ) ) ) {
			$c = SPLoader::loadClass( $class, false, C::ES, false );
			if ( !strlen( $c ) ) {
				$c = SPLoader::loadClass( $class, defined( 'SOBIPRO_ADM' ) );
			}
			if ( !strlen( $c ) ) {
				throw new SPException( SPLang::e( 'Cannot create instance of "%s". Class file does not exist', $class ) );
			}
			$loaded[ $class ] = $c;
		}
		$args = func_get_args();
		unset( $args[ 0 ] );
		$args = array_values( $args );
		try {
			$refMethod = new ReflectionMethod( $loaded[ $class ], '__construct' );
			$params = $refMethod->getParameters();
			$argsProcessed = [];
			if ( count( $args ) ) {
				foreach ( $params as $key => $param ) {
					if ( array_key_exists( $key, $args ) ) {
						if ( $param->isPassedByReference() ) {
							$argsProcessed[ $key ] = &$args[ $key ];
						}
						else {
							$argsProcessed[ $key ] = $args[ $key ];
						}
					}
				}
			}
			$obj = new ReflectionClass( $loaded[ $class ] );
			$instance = $obj->newInstanceArgs( $argsProcessed );
		}
		catch ( LogicException|ReflectionException $Exception ) {
			throw new SPException( SPLang::e( 'Cannot create instance of "%s". Error: %s', $class, $Exception->getMessage() ) );
		}
		catch ( Exception $Exception ) {
			throw new SPException( SPLang::e( 'Cannot create instance of "%s". Error: %s', $class, $Exception->getMessage() ) );
		}

		return $instance;
	}

	/**
	 * @param string $name
	 * @param bool $adm
	 *
	 * @return SPDBObject
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public static function & View( string $name, bool $adm = false )
	{
		return self::Instance( self::instancePath( $name, 'views', $adm ) );
	}

	/**
	 * @param string $name
	 * @param bool $adm
	 *
	 * @return SPDBObject
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public static function & Model( string $name, bool $adm = false )
	{
		return self::Instance( self::instancePath( $name, 'models', $adm ) );
	}

	/**
	 * Factory method for entries models.
	 *
	 * @param $sid
	 *
	 * @return SPEntry
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function & Entry( $sid )
	{
		$cached = SPFactory::cache( Sobi::Section() )->getObj( 'entry', $sid );
		if ( $cached && is_object( $cached ) ) {
			$cached->validateCache();

			return $cached;
		}
		else {
			$entry = new EntryModel();
			$entry = $entry->init( $sid );

			return $entry;
		}
	}

	/**
	 * Factory method for entries models.
	 *
	 * @param int $sid
	 *
	 * @return \SPEntry
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function & EntryRow( $sid )
	{
		$cached = SPFactory::cache()->getObj( 'entry_row', $sid );
		if ( $cached && is_object( $cached ) ) {
			return $cached;
		}
		else {
			/** @var SPEntry $entry */
			$entry = self::Model( 'entry', true );
			$entry = $entry->init( $sid );
			SPFactory::cache()->addObj( $entry, 'entry_row', $sid );

			return $entry;
		}
	}

	/**
	 * Factory method for category models.
	 *
	 * @param $sid
	 *
	 * @return mixed
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public static function & Category( $sid )
	{
		static $cats = [];
		if ( !isset( $cats[ $sid ] ) ) {
			$cats[ $sid ] = self::Model( 'category' );
			$cats[ $sid ] = $cats[ $sid ]->init( $sid );
		}

		return $cats[ $sid ];
	}

	/**
	 * Factory method for category models.
	 *
	 * @param $sid
	 *
	 * @return mixed
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public static function & Section( $sid )
	{
		static $sections = [];
		if ( !isset( $sections[ $sid ] ) ) {
			/* instantiate the section object */
			$sections[ $sid ] = self::Model( 'section' );
			$sections[ $sid ] = $sections[ $sid ]->init( $sid );
		}

		return $sections[ $sid ];
	}


	/**
	 * @param $sid
	 *
	 * @return string
	 * @throws \Sobi\Error\Exception
	 */
	public static function ObjectType( $sid )
	{
		return Factory::Db()
			->select( 'oType', 'spdb_object', [ 'id' => $sid ] )
			->loadResult();
	}

	/**
	 * @param $name
	 * @param bool $adm
	 *
	 * @return \SPDBObject|\SobiPro\Controllers\Entry
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public static function & Controller( $name, $adm = false )
	{
		if ( $name == 'entry' && !( $adm ) ) {
			$entryController = new EntryCtrl();

			return $entryController;
		}

		return self::Instance( self::instancePath( $name, 'ctrl', $adm ) );
	}

	/**
	 * @param string|null $name
	 * @param string $type
	 * @param bool $adm
	 *
	 * @return string
	 */
	private static function instancePath( ?string $name, string $type, bool $adm )
	{
		$adm = defined( 'SOBIPRO_ADM' ) ? $adm : false;

		return $adm ? "$type.adm.$name" : "$type.$name";
	}

	/**
	 * @return bool|mixed|SPPlugins
	 * @throws SPException
	 */
	public static function & plugins()
	{
		$r =& self::registry();
		if ( !$r->_isset( 'plugins' ) ) {
			SPLoader::loadClass( 'plugins.interface' );
			$plugins =& SPPlugins::getInstance();
			$r->set( 'plugins', $plugins );
		}
		else {
			$plugins =& $r->_get( 'plugins' );
		}

		return $plugins;
	}
}
