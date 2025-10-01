<?php
/**
 * @package SobiPro Library
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006–2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license   GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according to section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 10 January 2009 by Radek Suski
 * @modified 15 May 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

use Sobi\C;
use Sobi\Input\Input;
use Sobi\Lib\Factory;
use Sobi\Utils\Type;
use Sobi\Utils\StringUtils;

/**
 * Class SPDBObject
 */
abstract class SPDBObject extends SPObject
{
	/* columns of the object table */
	/** @var int database object id */
	protected $id = 0;
	/** @var string */
	protected $nid = C::ES;
	/** @var string */
	protected $name = C::ES;
	/** @var bool */
	protected $approved = false;
	/** @var bool */
	protected $confirmed = false;
	/** @var int */
	protected $counter = 0;
	/** @var bool */
	protected $cout = false;
	/** @var string */
	protected $coutTime = null;
	/** @var string */
	protected $createdTime = C::ES;
	/** @var string */
	protected $defURL = C::ES;
	/** @var string */
	protected $metaDesc = C::ES;
	/** @var string */
	protected $metaKeys = C::ES;
	/** @var string */
	protected $metaAuthor = C::ES;
	/** @var string */
	protected $metaRobots = C::ES;
	/** @var array */
	protected $options = [];
	/** @var string */
	protected $oType = C::ES;
	/** @var int */
	protected $owner = 0;
	/** @var string */
	protected $ownerIP = C::ES;
	/** @var array */
	protected $params = [];
	/** @var int */
	protected $section = 0;
	/** @var int */
	protected $parent = 0;
	/** @var int */
	protected $state = 0;
	/** @var string */
	protected $stateExpl = C::ES;
	/** @var string */
	protected $updatedTime = C::ES;
	/** @var int */
	protected $updater = 0;
	/** @var string */
	protected $updaterIP = C::ES;
	/** @var string */
	protected $validSince = C::ES;
	/** @var string */
	protected $validUntil = C::ES;
	/** @var int */
	protected $version = 0;

	/* Local attributes */
	/** @var string */
	protected $query = C::ES;
	/** @var string */
	protected $template = C::ES;
	/** @var array */
	protected $properties = [];

	/**
	 * @param string $name
	 * @param array $data
	 */
	public function setProperty( $name, $data )
	{
		$this->properties[ $name ] = $data;
	}

	/**
	 * List of adjustable properties and the corresponding request method for each property.
	 * If a property isn't declared here, it will be ignored in the getRequest method.
	 *
	 * @var array
	 */
	private static $types = [
		'approved'    => 'Bool',
		'state'       => 'Int',
		'confirmed'   => 'Bool',
		'counter'     => 'Int',
		'createdTime' => 'Timestamp',
		'defURL'      => 'String',
		'metaAuthor'  => 'String',
		'metaDesc'    => 'String',
		'metaKeys'    => 'String',
		'metaRobots'  => 'String',
		'name'        => 'String',
		'nameField'   => 'Int',
		'nid'         => 'Cmd',
		'owner'       => 'Int',
		'ownerIP'     => 'Ip4',
		'parent'      => 'Int',
		'stateExpl'   => 'String',
		'validSince'  => 'Timestamp',
		'validUntil'  => 'Timestamp',
		'params'      => 'Arr',
	];

	/**
	 * @var string[]
	 */
	private static $translatable = [ 'nid', 'metaDesc', 'metaKeys' ];

	/**
	 * SPDBObject constructor.
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function __construct()
	{
		$this->validUntil = C::ES;//SPFactory::config()->date( Factory::Db()->getNullDate(), 'date.db_format' );
		$this->validSince = SPFactory::config()->date( gmdate( 'U' ), 'date.db_format', C::ES, true );

		$this->owner = Sobi::My( 'id' );
		$this->ownerIP = Input::Ip4( 'REMOTE_ADDR', 'SERVER', 0 );
		$this->createdTime = SPFactory::config()->date( gmdate( 'U' ), 'date.db_format', C::ES, true );
		$this->updater = Sobi::My( 'id' );
		$this->updaterIP = Input::Ip4( 'REMOTE_ADDR', 'SERVER', 0 );
		$this->updatedTime = SPFactory::config()->date( time(), 'date.db_format' );

		Sobi::Trigger( 'CreateModel', $this->name(), [ &$this ] );
	}

	/**
	 * @param string $attr
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function param( $attr, $default = null )
	{
		if ( isset( $this->params[ $attr ] ) ) {
			if ( is_string( $this->params[ $attr ] ) ) {
				return stripslashes( $this->params[ $attr ] );
			}
			else {
				return $this->params[ $attr ];
			}
		}
		else {
			return $default;
		}
	}

	/**
	 * @param $attr
	 * @param $value
	 *
	 * @return $this
	 */
	public function & setParam( $attr, $value )
	{
		$this->params[ $attr ] = $value;

		return $this;
	}

	/**
	 * @return void
	 */
	public function formatDatesToEdit()
	{
		if ( $this->validUntil ) {
			$this->validUntil = SPFactory::config()->date( $this->validUntil, 'date.db_format' );
		}
		$this->createdTime = SPFactory::config()->date( $this->createdTime, 'date.db_format' );
		$this->validSince = SPFactory::config()->date( $this->validSince, 'date.db_format' );
		$this->updatedTime = SPFactory::config()->date( $this->updatedTime, 'date.db_format' );
	}

	/**
	 * @return void
	 */
	public function formatDatesToDisplay()
	{
		if ( $this->validUntil ) {
			$this->validUntil = SPFactory::config()->date( $this->validUntil, 'date.publishing_format' );
		}
		$this->createdTime = SPFactory::config()->date( $this->createdTime, 'date.publishing_format' );
		$this->validSince = SPFactory::config()->date( $this->validSince, 'date.publishing_format' );
		$this->updatedTime = SPFactory::config()->date( $this->updatedTime, 'date.publishing_format' );
	}

	/**
	 * @param $state
	 * @param null $reason
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function changeState( $state, $reason = null )
	{
		try {
			Factory::Db()->update( 'spdb_object', [ 'state' => ( int ) $state, 'stateExpl' => $reason ], [ 'id' => $this->id ] );
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::ERROR, 500, __LINE__, __FILE__ );
		}
		SPFactory::cache()
			->purgeSectionVars()
			->deleteObj( $this->type(), $this->id )
			->deleteObj( 'category', $this->parent );
	}

	/**
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	public function checkIn()
	{
		if ( $this->id ) {
			$this->cout = 0;
			$this->coutTime = null;
			try {
				Factory::Db()->update( 'spdb_object', [ 'coutTime' => $this->coutTime, 'cout' => $this->cout ], [ 'id' => $this->id ] );
			}
			catch ( Sobi\Error\Exception $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
			}
		}
	}

	/**
	 * Checking out current object.
	 *
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function checkOut()
	{
		if ( $this->id ) {
			$config =& SPFactory::config();
			$this->cout = Sobi::My( 'id' );
			/* release the entry after one hour automatically */
			$this->coutTime = $config->date( ( time() + $config->key( 'editing.def_cout_time', 3600 ) ), 'date.db_format' );
			try {
				Factory::Db()->update( 'spdb_object', [ 'coutTime' => $this->coutTime, 'cout' => $this->cout ], [ 'id' => $this->id ] );
			}
			catch ( Sobi\Error\Exception $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
			}
		}
	}

	/**
	 * Deletes an object (entry, category, section), its relations and language dependant data.
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function delete()
	{
		Sobi::Trigger( $this->name(), ucfirst( __FUNCTION__ ), [ $this->id ] ); // trigger before everything will be deleted
		try {
			Factory::Db()->delete( 'spdb_object', [ 'id' => $this->id ] );
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}
		try {
			Factory::Db()->delete( 'spdb_relations', [ 'id' => $this->id, 'oType' => $this->type() ] );
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}
		try {
			Factory::Db()->delete( 'spdb_language', [ 'id' => $this->id, 'oType' => $this->type() ] );
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}
		/* log the deletion */
		SPFactory::history()->logAction( SPC::LOG_DELETE, $this->id, $this->section ? : 0, $this->type(), C::ES, [ 'name' => $this->name ] );
	}

	/**
	 * Gets all entries and/or (sub-)categories of a specific category.
	 *
	 * @param string $type -> 'entry', 'category, 'all'
	 * @param bool $recursive
	 * @param int $state -> '1' -> only published and approved, '0' -> published and unpublished
	 * @param bool $name ->  '0' -> get only ids, -> '1' -> get also name and alias
	 * @param string $order -> ordering
	 *
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function getChilds( string $type = 'entry', bool $recursive = false, int $state = 0, bool $name = false, string $order = C::ES )
	{
		static $lang = C::ES;
		if ( !$lang ) {
			$lang = Sobi::Lang( false );
		}
		$cachesection = $this instanceof SPSection ? $this->id : Sobi::Section();
		$cacheorder = $order ? '_' . $order : C::ES;
		$cachetype = $type == 'all' ? C::ES : $type . '_';
		$cachename = 'childs_' . $lang . '_' . $cachetype . $state . $cacheorder . ( $recursive ? '_recursive' : C::ES ) . ( $name ? '_full' : C::ES );
		$childs = SPFactory::cache( $cachesection )->getVar( $cachename, $this->id );
		if ( $childs ) {
			return $childs == SPC::NO_VALUE ? [] : $childs;
		}

		/* data are not in the cache, rebuild them */
		$db = Factory::Db();
		$childs = [];

		try {
			$order = trim( $order );
			$conditions = [ 'sprl.pid' => $this->id ];
			$conditions[ 'spo.oType' ] = $type;

			$oPrefix = 'spo.';

			switch ( $order ) {
				case 'name.asc':
				case 'name.desc':
					$table = $db->join( [
							[ 'table' => 'spdb_language', 'as' => 'splang', 'key' => 'id' ],
							[ 'table' => 'spdb_object', 'as' => 'spo', 'key' => 'id' ],
							[ 'table' => 'spdb_relations', 'as' => 'sprl', 'key' => 'id' ],
						]
					);
					$conditions[ 'splang.sKey' ] = 'name';
					$conditions[ 'splang.language' ] = [ Sobi::Lang( false ), Sobi::DefLang(), 'en-GB' ];
					if ( strstr( $order, '.' ) ) {
						$order = explode( '.', $order );
						$order = 'sValue.' . $order[ 1 ];
					}
					break;
				case 'updatedTime.asc':
				case 'updatedTime.desc':
				case 'createdTime.asc':
				case 'createdTime.desc':
				case 'position.asc':
				case 'position.desc':
					$table = $db->join( [
							[ 'table' => 'spdb_relations', 'as' => 'sprl', 'key' => 'id' ],
							[ 'table' => 'spdb_object', 'as' => 'spo', 'key' => 'id' ],
						]
					);
					break;
				case 'counter.asc':
				case 'counter.desc':
					$table = $db->join( [
							[ 'table' => 'spdb_relations', 'as' => 'sprl', 'key' => 'id' ],
							[ 'table' => 'spdb_object', 'as' => 'spo', 'key' => 'id' ],
							[ 'table' => 'spdb_counter', 'as' => 'spcounter', 'key' => [ 'spcounter.sid', 'sprl.id' ] ],
						]
					);
					if ( strstr( $order, '.' ) ) {
						$order = explode( '.', $order );
						$order = 'spcounter.counter.' . $order[ 1 ];
					}
					break;
				default:
					$order = C::ES;
					$conditions = [ 'pid' => $this->id ];
					if ( $state ) {
						$conditions[ 'spo.state' ] = $state;
						$conditions[ 'spo.approved' ] = $state;
						$table = $db->join(
							[
								[ 'table' => 'spdb_object', 'as' => 'spo', 'key' => 'id' ],
								[ 'table' => 'spdb_relations', 'as' => 'sprl', 'key' => 'id' ],
							]
						);
						$oPrefix = 'sprl.';
					}
					else {
						$oPrefix = C::ES;
						$table = 'spdb_relations';
					}
					//$conditions[ 'oType' ] = $type;
					break;
			}
			$results = $db
				->select( [ $oPrefix . 'id', $oPrefix . 'oType' ], $table, $conditions, $order, 0, 0, true )
				->loadAssocList( 'id' );

			/* If no entry/category is in the relations table, check if it is the case of unassigned entries/categories. */
			/* It seems so if the entries/categories are in the object table with parent as section */

			/* DO NOT RELY ON 'PARENT' as it is set only if an entry has a primary category set by category field */
//			if ( is_array( $results ) && !count( $results ) && $table == 'spdb_relations' && Sobi::Section() == $this->id ) {
//				$conditions = [ 'parent' => $this->id ];
//				$conditions[ 'oType' ] = $type;
//				$results = $db->select( [ 'id', 'oType' ], 'spdb_object', $conditions, $order, 0, 0, true )
//					->loadAssocList( 'id' );
//			}
		}
		catch ( SPException $x ) {
			$results = [];
			Sobi::Error( $this->name(), SPLang::e( 'CANNOT_GET_CHILDS_DB_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}

		/* got entries and categories as result, now filter according 'type' */
		if ( $recursive && count( $results ) ) {
			foreach ( $results as $cid ) {
				$this->getChildsRecursive( $results, $cid, $type );
			}
		}
		if ( count( $results ) ) {
			if ( $type == 'all' ) {
				foreach ( $results as $id => $r ) {
					$childs[ $id ] = $r[ 'id' ];
				}
			}
			else {
				foreach ( $results as $id => $r ) {
					if ( $r[ 'oType' ] == $type ) {
						$childs[ $id ] = $id;
					}
				}
			}
		}
		if ( $name && count( $childs ) ) {
			$names = SPLang::translateObject( $childs, [ 'name', 'alias', 'state' ], $type );
			if ( is_array( $names ) && !empty( $names ) ) {
				foreach ( $childs as $i => $id ) {
					$childs[ $i ] =
						[ 'name'  => $names[ $id ][ 'value' ] ?? $names[ $id ][ 'name' ],
						  'alias' => $names[ $id ][ 'alias' ],
						  'state' => $names[ $id ][ 'state' ] ];
				}
			}
		}

		if ( is_array( $childs ) && count( $childs ) ) {
			SPFactory::cache( $cachesection )->addVar( $childs, $cachename, $this->id );
			SPFactory::cache()->addObj( $childs, $type, $this->id );
		}

		return $childs;
	}

	/**
	 * @param $results
	 * @param $id
	 * @param string $type
	 *
	 * @throws SPException|\Sobi\Error\Exception
	 */
	private function getChildsRecursive( &$results, $id, $type = 'entry' )
	{
		if ( is_array( $id ) ) {
			$id = $id[ 'id' ];
		}
		$db = Factory::Db();
		try {
			$conditions = [ 'pid' => $id ];
			if ( $type == 'category' ) {
				$conditions[ 'oType' ] = $type;
			}
			$r = $db->select( [ 'id', 'oType' ], 'spdb_relations', $conditions )
				->loadAssocList( 'id' );
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'CANNOT_GET_CHILDS_DB_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}
		if ( count( $r ) ) {
			foreach ( $r as $id => $rs ) {
				$results[ $id ] = $rs;
				$this->getChildsRecursive( $results, $id, $type );
			}
		}
	}

	/**
	 * @return void
	 */
	public function loadCounter(): void
	{
		try {
			/* the counter have to be stored and handled as integer! */
			$this->counter = (int) Factory::Db()
				->select( 'counter', 'spdb_counter', [ 'sid' => $this->id ] )
				->loadResult();
		}
		catch ( Exception $x ) {
		}
	}

	/**
	 * @return string
	 * @throws \Sobi\Error\Exception
	 */
	protected function createAlias()
	{
		/* check nid */
		$counter = 1;
		static $add = 0;
		$suffix = C::ES;
		if ( !strlen( (string) $this->nid ) ) {
			$this->nid = strtolower( StringUtils::Nid( $this->name, true ) );
		}
		/* nid for object table may not exceed 190 characters */
		$this->nid = substr( $this->nid, 0, 190 );

		while ( $counter ) {
			try {
				$condition = [ 'oType' => $this->oType, 'nid' => $this->nid . $suffix ];
				if ( $this->id ) {
					$condition[ '!id' ] = $this->id;
				}
				$counter = Factory::Db()
					->select( 'COUNT( nid )', 'spdb_object', $condition )
					->loadResult();
				if ( $counter > 0 ) {
					$suffix = '-' . ++$add;
				}
			}
			catch ( Sobi\Error\Exception $x ) {
			}
		}

		return $this->nid . $suffix;
	}

	/**
	 * Getting data from request for this object.
	 *
	 * @param string $prefix
	 * @param string $request
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function getRequest( $prefix = C::ES, $request = 'POST' )
	{
		$prefix = $prefix ? $prefix . '_' : C::ES;
		/* get data types of my  properties */
		$properties = get_object_vars( $this );
		Sobi::Trigger( $this->name(), ucfirst( __FUNCTION__ ) . 'Start', [ &$properties ] );
		/* and of the parent properties */
		$types = array_merge( $this->types(), self::$types );
		foreach ( $properties as $property => $values ) {
			/* if this is an internal variable */
			if ( substr( $property, 0, 1 ) == '_' ) {
				continue;
			}
			/* if no data type has been declared */
			if ( !isset( $types[ $property ] ) ) {
				continue;
			}
			/* if there was no data for this property ( not if it was just empty ) */
			if ( !Input::Search( $prefix . $property, $request ) ) {
				continue;
			}
			/* if the declared data type has no handler in request class */
			$method = $types[ $property ];
			if ( !method_exists( 'Sobi\Input\Input', $method ) ) {
				Sobi::Error( $this->name(), SPLang::e( 'Method Sobi\Input\Input\%s does not exist!', $types[ $property ] ), C::WARNING, 0, __LINE__, __FILE__ );
				continue;
			}
			/* now we get it ;) */
			$this->$property = Input::$method( $prefix . $property, $request );
			$this->$property = $method == 'String' ? StringUtils::Clean( $this->$property ) : $this->$property;
			//$this->$property = SPRequest::$method( $prefix . $property, null, $request );
			//$method = $method;
		}
		/* trigger plugins */
		Sobi::Trigger( 'getRequest', $this->name(), [ &$this ] );
	}

	/**
	 * @param string $type
	 * @param int $state
	 *
	 * @return int
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function countChilds( string $type = C::ES, int $state = 0 ): int
	{
		return count( $this->getChilds( $type, true, $state ) );
	}

	/**
	 * @return string
	 */
	public function type()
	{
		return $this->oType;
	}

	/**
	 * @param bool $reset
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function countVisit( $reset = false )
	{
		$count = true;
		Sobi::Trigger( 'CountVisit', ucfirst( $this->type() ), [ &$count, &$reset, $this->id ] );

		if ( $this->id && $count ) {
			try {
				Factory::Db()->insertUpdate( 'spdb_counter', [ 'sid'        => $this->id,
				                                               'counter' => ( $reset ? 0 : ++$this->counter ),
				                                               'lastUpdate' => 'FUNCTION:NOW()' ] );
			}
			catch ( Sobi\Error\Exception $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'CANNOT_INC_COUNTER_DB', $x->getMessage() ), C::ERROR, 0, __LINE__, __FILE__ );
			}
		}
	}

	/**
	 * @param string $request
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function save( $request = 'post' )
	{
		$this->version++;
		/* get current data */
		$this->updatedTime = Input::Now();
		$this->updaterIP = Input::Ip4();
		$this->updater = Sobi::My( 'id' );
		$this->nid = StringUtils::Nid( $this->nid, true );
		if ( !$this->nid ) {
			$this->nid = StringUtils::Nid( $this->name, true );
		}
		/* get THIS class properties */
		$properties = get_class_vars( __CLASS__ );

		/* if new object */
		if ( !$this->id ) {
			/** the notification App is using it to recognise if it is a new entry or an update */
			$this->createdTime = $this->updatedTime;
			$this->validSince = $this->updatedTime;
			$this->owner = $this->owner ? : $this->updater;
			$this->ownerIP = $this->updaterIP;
		}

		/* just a security check to avoid mistakes */
		else {
			$this->createdTime = $this->createdTime && is_numeric( $this->createdTime ) ?
				gmdate( Sobi::Cfg( 'date.db_format', SPC::DEFAULT_DB_DATE ), (int) $this->createdTime ) :
				$this->createdTime;
			$obj = SPFactory::object( $this->id );
			if ( $obj->oType != $this->oType ) {
				Sobi::Error( 'Object Save', sprintf( 'Serious security violation. Trying to save an object which claims to be a %s, but it is a %s. Task was %s.', $this->oType, $obj->oType, Input::Task() ), C::ERROR, 403, __LINE__, __FILE__ );
				exit;
			}

		}
		if ( is_numeric( $this->validUntil ) ) {
			$this->validUntil = $this->validUntil ?
				gmdate( Sobi::Cfg( 'date.db_format', SPC::DEFAULT_DB_DATE ), (int) $this->validUntil ) :
				Factory::Db()->getNullDate();
		}
		if ( is_numeric( $this->validSince ) ) {
			$this->validSince = $this->validSince ?
				gmdate( Sobi::Cfg( 'date.db_format', SPC::DEFAULT_DB_DATE ), (int) $this->validSince ) :
				Factory::Db()->getNullDate();
		}

		/* if the entry has the section as primary, reset is to 0 -> variable primary (first category wins) */
		if ( ( $this->oType == 'entry' ) && ( $this->parent == $this->section ) && is_array( $this->categories ) && count( $this->categories ) ) {
			$this->primary = $this->parent = intval( 0 );
		}

		$db = Factory::Db();
		//$db->transaction();

		/* get database columns and their ordering */
		$cols = $db->getColumns( 'spdb_object', true );
		$values = [];

		/* if not published, check if user can publish own and if yes, publish it */
		if ( !defined( 'SOBIPRO_ADM' ) ) {
			if ( !$this->state ) {
				$this->state = Sobi::Can( $this->type(), 'publish', 'own' );
			}
			// check if the user has rights to approve his entry
			/***
			 * Wed, Feb 28, 2018 11:43:35
			 * So if it was already approved then it will stay approved. WTF is wrong with you Radek?
			 */
//			if ( !( $this->approved ) ) {
			$this->approved = Sobi::Can( $this->type(), 'manage', 'own' );
//			}
		}
//		elseif ( defined( 'SOBIPRO_ADM' ) ) {
//			$this->approved = Sobi::Can( $this->type(), 'publish', 'own' );
//		}

		/* and sort the properties in the same order */
		foreach ( $cols as $col => $props ) {
			$values[ $col ] = array_key_exists( $col, $properties ) && $this->$col ? $this->$col : Type::SQLNull( $props[ 'Type' ] );
		}

		/* trigger plugins */
		Sobi::Trigger( 'save', $this->name(), [ &$this ] );
		/* try to save */
		try {
			$logAction = C::ES;
			/* do not save the name in the object table */
			if ( isset( $values[ 'name' ] ) ) {
				$values[ 'name' ] = C::ES;
			}
			if ( $values[ 'section' ] == 0 && $values[ 'oType' ] != 'section' ) {
				$values[ 'section' ] = (int) Sobi::Section();
			}

			/* if new object */
			if ( !$this->id ) {
				$db->insert( 'spdb_object', $values );
				$this->id = $db->insertId();
				if ( !( Input::Task() == 'imex.doImport' || Input::Task() == 'imex.doCImport' ) ) {
					$logAction = SPC::LOG_ADD;
				}
			}
			/* if update */
			else {
				$db->update( 'spdb_object', $values, [ 'id' => $this->id ] );
				if ( !( Input::Task() == 'imex.doImport' || Input::Task() == 'imex.doCImport' ) ) {
					$logAction = SPC::LOG_EDIT;
				}
			}
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'CANNOT_SAVE_OBJECT_DB_ERR', $x->getMessage() ), C::ERROR, 500, __LINE__, __FILE__ );
			$logAction = C::ES;
		}

		/* get translatable properties */
		$attributes = array_merge( $this->translatable(), self::$translatable );
		$labels = [];
		$defLabels = [];
		foreach ( $attributes as $attr ) {
			if ( $this->has( $attr ) ) {
				$labels[] = [ 'sKey' => $attr, 'sValue' => $this->$attr, 'language' => Sobi::Lang(), 'id' => $this->id, 'oType' => $this->type(), 'fid' => 0 ];
				/* in backend we save only in language Sobi::Lang() if multilingual mode is on */
				if ( !( defined( 'SOBIPRO_ADM' ) && Sobi::Cfg( 'lang.multimode', false ) ) && Sobi::Lang() != Sobi::DefLang() ) {
					$defLabels[] = [ 'sKey' => $attr, 'sValue' => $this->$attr, 'language' => Sobi::DefLang(), 'id' => $this->id, 'oType' => $this->type(), 'fid' => 0 ];
				}
			}
		}

		/* save translatable properties */
		if ( count( $labels ) ) {
			try {
				/* in backend we save only in language Sobi::Lang() if multilingual mode is on */
				if ( !( defined( 'SOBIPRO_ADM' ) && Sobi::Cfg( 'lang.multimode', false ) ) && Sobi::Lang() != Sobi::DefLang() ) {
					$db->insertArray( 'spdb_language', $defLabels, false, true );
				}
				$db->insertArray( 'spdb_language', $labels, true );
			}
			catch ( SPException $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'CANNOT_SAVE_OBJECT_DB_ERR', $x->getMessage() ), C::ERROR, 500, __LINE__, __FILE__ );
			}
		}
		$db->commit();
		$this->checkIn();

		/* log what was done */
		if ( $logAction ) {
			$nid = C::ES;
			if ( $this->type() == 'entry' ) {
				$nid = SPFactory::config()->nameFieldNid( (int) Sobi::Section() );
			}
			$name = $this->name ? : ( $this->type() == 'entry' ? Input::String( $nid, 'post', C::ES ) : C::ES );
			$params = [];
			if ( $name ) {
				$params[ 'name' ] = $name;
			}
			SPFactory::history()->logAction( $logAction, $this->id, $this->section ? : Sobi::Section(), $this->type(), C::ES, $params );
		}
	}

	/**
	 * Dummy function.
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function update()
	{
		$this->save();
	}

	/**
	 * @param $obj
	 * @param bool $cache
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function extend( $obj, $cache = false )
	{
		if ( !empty( $obj ) ) {
			foreach ( $obj as $key => $value ) {
				$this->_set( $key, $value );
			}
		}
		Sobi::Trigger( $this->name(), ucfirst( __FUNCTION__ ), [ &$obj ] );
		$this->loadTable( $cache );
		$this->validUntil = SPFactory::config()->date( $this->validUntil );
	}

	/**
	 * @param $id
	 *
	 * @return $this|null
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function & init( $id = 0 )
	{
		Sobi::Trigger( $this->name(), ucfirst( __FUNCTION__ ) . 'Start', [ &$id ] );

		$this->id = $id ? : $this->id;
		if ( $this->id ) {
			try {
				$obj = SPFactory::object( $this->id );
				if ( $obj && count( (array) $obj ) ) {
					/* ensure that the id was right */
					if ( $obj->oType == $this->oType ) {
						$this->extend( $obj );
						if ( $this->oType == 'section' ) {
							$this->section = $this->id;
						}
						else {
							if ( $this->oType == 'entry' && !count( $this->categories ) ) {
//								$this->id = 0;
								$this->valid = $this->enabled = false;
							}
						}
					}
					else {
						$this->id = 0;
					}

					$this->createdTime = SPFactory::config()->date( $this->createdTime );
					$this->validSince = SPFactory::config()->date( $this->validSince );
					if ( $this->validUntil ) {
						$this->validUntil = SPFactory::config()->date( $this->validUntil );
					}
					$this->loadCounter();
				}
				else {
					$result = null;

					return $result;
				}
			}
			catch ( SPException $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'CANNOT_GET_OBJECT_DB_ERR', $x->getMessage() ), C::ERROR, 500, __LINE__, __FILE__ );
			}
			/** Wed, Feb 3, 2016 14:24:09 The extended method calls already the loadTable method */
//			$this->loadTable();
		}

		return $this;
	}

	/**
	 * @param $id
	 *
	 * @return $this
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function load( $id )
	{
		return $this->init( $id );
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function loadTable()
	{
		/* only if data are not cached */
		if ( $this->has( '_dbTable' ) && $this->_dbTable ) {
			try {
				$obj = Factory::Db()
					->select( '*', $this->_dbTable, [ 'id' => $this->id ] )
					->loadObject();

				Sobi::Trigger( $this->name(), ucfirst( __FUNCTION__ ), [ &$obj ] );
			}
			catch ( SPException $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
			}

			if ( count( (array) $obj ) ) {
				foreach ( $obj as $key => $value ) {
					$this->_set( $key, $value );
				}
			}
//			else {
//				Sobi::Error( $this->name(), SPLang::e( 'NO_ENTRIES' ), C::WARNING, 0, __LINE__, __FILE__ );
//			}
		}
		$this->loadCounter();
		$this->translate();
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function translate()
	{
		$attributes = array_merge( $this->translatable(), self::$translatable );
		Sobi::Trigger( $this->name(), ucfirst( __FUNCTION__ ) . 'Start', [ &$attributes ] );
		$labels = SPLang::translateObject( $this->id, $attributes );
		if ( is_array( $labels ) && count( $labels ) ) {
			foreach ( $labels[ $this->id ] as $key => $label ) {
				if ( in_array( $key, $attributes ) ) {
					$this->_set( $key, $label );
				}
			}
		}
//		$db =& Factory::Db();
//		try {
//			$labels = $db
//					->select( 'sValue, sKey', 'spdb_language', array( 'id' => $this->id, 'sKey' => $attributes, 'language' => Sobi::Lang(), 'oType' => $this->type() ) )
//					->loadAssocList( 'sKey' );
//			/* get labels in the default lang first */
//			if ( Sobi::Lang( false ) != Sobi::DefLang() ) {
//				$dlabels = $db
//						->select( 'sValue, sKey', 'spdb_language', array( 'id' => $this->id, 'sKey' => $attributes, 'language' => Sobi::DefLang(), 'oType' => $this->type() ) )
//						->loadAssocList( 'sKey' );
//				if ( count( $dlabels ) ) {
//					foreach ( $dlabels as $key => $value ) {
//					foreach ( $dlabels as $key => $value ) {
//						if ( !( isset( $labels[ $key ] ) ) || !( $labels[ $key ] ) ) {
//							$labels[ $key ] = $value;
//						}
//					}
//				}
//			}
//		} catch ( SPException $x ) {
//			Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
//		}
//		if ( count( $labels ) ) {
//			foreach ( $labels as $key => $value ) {
//				$this->_set( $key, $value[ 'sValue' ] );
//			}
//		}
		Sobi::Trigger( $this->name(), ucfirst( __FUNCTION__ ), [ &$labels ] );
	}

	/**
	 * @param $var
	 * @param $val
	 *
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function _set( $var, $val )
	{
		if ( $this->has( $var ) ) {
			if ( is_array( $this->$var ) && is_string( $val ) && strlen( $val ) > 2 ) {
				try {
					$val = SPConfig::unserialize( $val, $var );
				}
				catch ( SPException $x ) {
					Sobi::Error( $this->name(), SPLang::e( '%s.', $x->getMessage() ), C::NOTICE, 0, __LINE__, __FILE__ );
				}
			}
			$this->$var = $val;
		}
	}

	/**
	 * @return bool
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function isCheckedOut(): bool
	{
		return ( $this->cout
			&& $this->cout != Sobi::My( 'id' )
			&& strtotime( $this->coutTime ) > time()
		);
	}

	/**
	 * @param string $var
	 * @param mixed $val
	 *
	 * @return $this|SPDBObject
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function & set( $var, $val )
	{
		$types = array_merge( $this->types(), self::$types );
		if ( $this->has( $var ) && isset( $types[ $var ] ) ) {
			if ( is_array( $this->$var ) && is_string( $val ) && strlen( $val ) > 2 ) {
				try {
					$val = SPConfig::unserialize( $val, $var );
				}
				catch ( SPException $x ) {
					Sobi::Error( $this->name(), SPLang::e( '%s.', $x->getMessage() ), C::NOTICE, 0, __LINE__, __FILE__ );
				}
			}
			$this->$var = $val;
		}

		return $this;
	}

	/**
	 * @return array
	 */
	protected function translatable()
	{
		return [];
	}
}
