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
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 10-Jan-2009 by Radek Suski
 * @modified 15 May 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadModel( 'datamodel' );
SPLoader::loadModel( 'dbobject' );

use Sobi\C;
use Sobi\Input\Input;
use Sobi\Lib\Factory;
use Sobi\Utils\StringUtils;

#[AllowDynamicProperties]
/**
 * Class SPEntry
 */
class SPEntry extends SPDBObject implements SPDataModel
{
	/** @var array */
	private static $types = [
		'description'   => 'Html',
		'icon'          => 'String',
		'showIcon'      => 'Int',
		'introtext'     => 'String',
		'showIntrotext' => 'Int',
		'parseDesc'     => 'Int',
		'position'      => 'Int',
		'url' => 'String',
	];
	/** @var string */
	protected $oType = 'entry';
	/** @var array categories where the entry belongs to */
	protected $categories = [];
	/** @var array */
	protected $fields = [];
	/** @var array */
	protected $fieldsNids = [];
	/** @var array */
	protected $fieldsIds = [];
	/** @var string */
	protected $nameFieldNid = C::ES;
	/** @var array */
	private $data = [];
	/** @var bool */
	private $_loaded = false;
	/** @var int */
	public $position = 0;
	/** @var bool */
	protected $valid = true;
	/** @var int */
	public $primary = 0;
	/** @var string */
	public $url = C::ES;

	/** @var bool */
	public $importState = false;

	/** @var bool */
	protected $parentPathSet = true;

	/**
	 * SPEntry constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		if ( Sobi::Cfg( 'entry.publish_limit', 0 ) ) {
			$this->validUntil = date( SPC::DEFAULT_DB_DATE, time() + ( Sobi::Cfg( 'entry.publish_limit', 0 ) * 24 * 3600 ) );
		}
	}

	/**
	 * Full init.
	 *
	 * @param bool $cache
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function loadTable( $cache = false )
	{
		if ( $this->id && !$cache ) {
			/* evaluate the categories of this entry */
			$this->getCategories( true );

			$this->section = SPFactory::config()->getParentPathSection( $this->id );
			$this->parentPathSet = SPFactory::config()->getParentPathState( $this->id );
			// we need to get some information from the object table
			$this->valid = ( count( $this->categories ) > 0 ) && $this->parentPathSet;
			if ( $this->section ) {
				$this->loadFields( $this->section, true );
				Sobi::Trigger( $this->name(), ucfirst( __FUNCTION__ ), [ &$this->fields ] );
			}
			if ( count( $this->fields ) ) {
				foreach ( $this->fields as $field ) {
					/* create field aliases */
					$this->fieldsIds[ $field->get( 'id' ) ] = $field;
					$this->fieldsNids[ $field->get( 'nid' ) ] = $field;
				}
				if ( !is_string( $this->name ) || !strlen( $this->name ) ) {
					$nameFieldFid = SPFactory::config()->nameFieldFid( $this->section );
					if ( $nameFieldFid && isset( $this->fieldsIds[ $nameFieldFid ] ) ) {
						$this->name = $this->fieldsIds[ $nameFieldFid ]->getRaw();
					}
					if ( !is_string( $this->name ) || !strlen( $this->name ) ) {
						$this->name = Sobi::Txt( 'UNDEFINED' );
					}
				}
			}
			if ( !$this->valid || !$this->parentPathSet ) {
				$this->enabled = $this->valid = 0;
			}
			$this->primary =& $this->parent;
			$this->url = Sobi::Url( [
				'title' => Sobi::Cfg( 'sef.alias', true )
					? $this->get( 'nid' )
					: $this->get( 'name' ),
				'pid'   => $this->get( 'primary' ),
				'sid'   => $this->id ],
				false, true, true, true );
			Sobi::Trigger( $this->name(), ucfirst( __FUNCTION__ ), [ &$this->fieldsIds, &$this->fieldsNids ] );
		}

		if ( !strlen( $this->name ) ) {
			$this->name = Sobi::Txt( 'ENTRY_NO_NAME' );
		}
		if ( $this->owner && $this->owner == Sobi::My( 'id' ) ) {
			$stop = true;
			SPFactory::registry()->set( 'break_cache_view', $stop );
		}
		$this->loadCounter();
		$this->translate();

//		$a = !( Sobi::Can( 'entry.access.unapproved_any' ) );
//		$b = ( Input::Task() != 'entry.edit' && Input::Task() != 'entry.submit' && Input::Task() != 'entry.save' );
//		$c = !( $this->approved );
//		$d = !( Sobi::Can( 'entry', 'edit', '*', $this->section ) );
//		$z = $a && $b && $c && $d;
//		if ( $z == true ) {
//			$this->approved = 1;
//		}

		/* if the user can't see unapproved entries, we are showing the approved version anyway */
		/* if the user is not allowed to see all unapproved entries and ... */
		if ( !Sobi::Can( 'entry.access.unapproved_any' )
			/* if the user does not edit or save the entry and ... */
			&& ( Input::Task() != 'entry.edit' && Input::Task() != 'entry.submit' && Input::Task() != 'entry.save' )
			/* if the entry is not approved and ...*/
			&& !$this->approved
			/* if the user cannot edit all entries in this section. */
			&& !Sobi::Can( 'entry', 'edit', '*', $this->section )
		) {
			$this->approved = 1;
		}
	}

	/**
	 * Std. getter. Returns a property of the object or the default value if the property is not set.
	 *
	 * @param string $attr
	 * @param null $default
	 * @param bool $object - returns the object instead of data
	 *
	 * @return mixed
	 */
	public function get( $attr, $default = null, $object = false )
	{
		if ( strstr( $attr, 'field_' ) ) {
			if ( isset( $this->fieldsNids[ trim( $attr ) ] ) ) {
				return $object ? $this->fieldsNids[ trim( $attr ) ] : $this->fieldsNids[ trim( $attr ) ]->data();
			}
		}

		return parent::get( $attr, $default );
	}

//	public function set( $var, $val )
//	{
//		if ( isset( $this->$var ) || property_exists( $this, $var ) ) {
//			if ( is_array( $this->$var ) && is_string( $val ) && strlen( $val ) > 2 ) {
//				try {
//					$val = SPConfig::unserialize( $val, $var );
//				}
//				catch ( SPException $x ) {
//					Sobi::Error( $this->name(), SPLang::e( '%s.', $x->getMessage() ), SPC::NOTICE, 0, __LINE__, __FILE__ );
//				}
//			}
//			$this->$var = $val;
//		}
//
//		return $this;
//	}

	/**
	 * External method to publish and approve an entry.
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function publish()
	{
		Factory::Db()->update( 'spdb_object', [ 'approved' => 1 ], [ 'id' => $this->id, 'oType' => 'entry' ] );
		$this->changeState( true );
		$this->approveFields( true );
	}

	/**
	 * External method to un-publish and revoke approval of an entry.
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function unpublish()
	{
		Factory::Db()->update( 'spdb_object', [ 'approved' => 0 ], [ 'id' => $this->id, 'oType' => 'entry' ] );
		$this->changeState( false );
	}

	/**
	 * @param $approve
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function approveFields( $approve )
	{
		Sobi::Trigger( $this->name(), 'Approve', [ $this->id, $approve, &$this->fields ] );

//		SPFactory::cache()
//			->purgeSectionVars()
//			->deleteObj( 'entry', $this->id );

		foreach ( $this->fields as $field ) {
			//$field->enabled( 'form' );
			$field->approve( $this->id );
		}
		if ( $approve ) {
			$db = Factory::Db();
			try {
				$count = $db
					->select( 'COUNT(id)', 'spdb_relations', [ 'id' => $this->id, 'copy' => '1', 'oType' => 'entry' ] )
					->loadResult();
				if ( $count ) {
					/** Thu, Jun 19, 2014 11:24:05: here is the question: why are we deleting the 1 status when the list of categories is re-generating each time anyway
					 *   So basically there should not be a situation that there is any relation which should be removed while approving an entry */
					// $db->delete( 'spdb_relations', array( 'id' => $this->id, 'copy' => '0', 'oType' => 'entry' ) );
					$db->update( 'spdb_relations', [ 'copy' => '0' ], [ 'id' => $this->id, 'copy' => '1', 'oType' => 'entry' ] );
				}
			}
			catch ( Sobi\Error\Exception $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::ERROR, 500, __LINE__, __FILE__ );
			}
		}

		Sobi::Trigger( $this->name(), 'AfterApprove', [ $this->id, $approve ] );

		SPFactory::cache()
			->purgeSectionVars()
			->deleteObj( 'entry', $this->id );
	}

	/**
	 * @param bool $trigger
	 *
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function discard( bool $trigger = true )
	{
		$data = $this->getCurrentBaseData();
		if ( $trigger ) {
			Sobi::Trigger( 'Entry', 'Unapprove', [ $this->_model, 0 ] );
		}
		// check if the entry was ever approved.
		// if it wasn't we would delete current data and there would be no other data at all
		// See #1221 - Thu, May 8, 2014 11:18:20
		// and what if logging will be switched on first after the entry was already approved?? (Sigrid)
		$count = Factory::Db()
			->select( 'COUNT(*)', 'spdb_history', [ 'sid' => $this->id, 'changeAction' => [ SPC::LOG_APPROVE, 'approved' ] ] )
			->loadResult();
		if ( $count ) {
			// restore previous version
			foreach ( $this->fields as $field ) {
				$field->rejectChanges( $this->id );
			}
		}
		// reload fields
		$this->loadFields( $this->id );
		// store data
		foreach ( $this->fields as $field ) {
			$field->loadData( $this->id );
			$data[ 'fields' ][ $field->get( 'nid' ) ] = $field->getRaw();
		}
		if ( $count ) {
			Factory::Db()->delete( 'spdb_relations', [ 'id' => $this->id, 'copy' => '1', 'oType' => 'entry' ] );
		}

		if ( $trigger ) {
			Sobi::Trigger( 'Entry', 'AfterUnapprove', [ $this->_model, 0 ] );
		}

		SPFactory::cache()
			->purgeSectionVars()
			->deleteObj( 'entry', $this->id )
			->cleanXMLRelations( $this->categories );

		return $data;
	}

	/**
	 * @param int $state
	 * @param string $reason
	 * @param bool $trigger
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function changeState( $state, $reason = C::ES, bool $trigger = true )
	{
		if ( $trigger ) {
			Sobi::Trigger( $this->name(), 'ChangeState', [ $this->id, $state ] );
		}

		try {
			Factory::Db()->update( 'spdb_object', [ 'state' => ( int ) $state, 'stateExpl' => $reason ], [ 'id' => $this->id ] );
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::ERROR, 500, __LINE__, __FILE__ );
		}
		foreach ( $this->fields as $field ) {
			$field->changeState( $this->id, $state );
		}
		if ( $trigger ) {
			Sobi::Trigger( $this->name(), 'AfterChangeState', [ $this->id, $state ] );
		}

		/* Clean the cache */
		SPFactory::cache()
			->purgeSectionVars()
			->deleteObj( 'entry', $this->id );
	}

	/**
	 * @param $ident
	 *
	 * @return mixed|null
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function & getField( $ident )
	{
		$field = null;
		if ( is_int( $ident ) ) {
			if ( isset( $this->fieldsIds[ $ident ] ) ) {
				$field =& $this->fieldsIds[ $ident ];
			}
		}
		else {
			if ( isset( $this->fieldsNids[ $ident ] ) ) {
				$field =& $this->fieldsNids[ $ident ];
			}
		}
		Sobi::Trigger( $this->name(), ucfirst( __FUNCTION__ ), [ $ident, &$this->fieldsIds, &$this->fieldsNids ] );

		return $field;
	}

	/**
	 * @param string $by
	 *
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function & getFields( $by = 'name' )
	{
		$fields =& $this->fields;
		switch ( $by ) {
			case 'name':
			case 'nid':
				$fields =& $this->fieldsNids;
				break;
			case 'id':
			case 'fid':
				$fields =& $this->fieldsIds;
				break;
		}
		Sobi::Trigger( $this->name(), ucfirst( __FUNCTION__ ), [ &$fields ] );

		return $fields;
	}

	/**
	 * @param $cid
	 *
	 * @return int|mixed
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function getPosition( $cid )
	{
		if ( $this->id && !count( $this->categories ) ) {
			$this->getCategories();
		}

		return $this->categories[ $cid ][ 'position' ] ?? 0;
	}

	/**
	 * Returns the primary category for this entry.
	 *
	 * @return int|mixed
	 * @throws \Sobi\Error\Exception|\SPException
	 */
	public function getPrimary()
	{
		if ( !count( $this->categories ) ) {
			$this->getCategories();
		}

		return $this->categories[ $this->primary ] ?? 0;
	}

	/**
	 * Evaluates the categories for an entry and stores them in the global var $this->categories.
	 * Additionally, it returns the categories array either associative or indexed.
	 *
	 * @param bool $indexed
	 *
	 * @return array|int[]|string[]
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function getCategories( bool $indexed = false ): array
	{
		if ( $this->id ) {
			/* if the global categories are not yet set */
			if ( !count( $this->categories ) ) {
				try {
					$conditions = [ 'id' => $this->id, 'oType' => 'entry' ];
					if ( !( $this->approved || Input::Task() == 'entry.edit' || Sobi::Can( 'entry.access.unapproved_any' ) ) ) {
						$conditions[ 'copy' ] = '0';
					}

					$categories = Factory::Db()
						->select( [ 'pid', 'position', 'validSince', 'validUntil' ], 'spdb_relations', $conditions, 'position' )
						->loadAssocList( 'pid' );

					/* validate categories - case some of them has been deleted */
					$cats = array_keys( $categories );
					if ( count( $cats ) ) {
						$cats = Factory::Db()
							->select( 'id', 'spdb_object', [ 'id' => $cats ] )
							->loadResultArray();
					}

					if ( count( $categories ) ) {
						foreach ( $categories as $pid => $categoryData ) {
							if ( !$this->parent ) {
								$this->parent = $pid;
							}
							if ( !in_array( $pid, $cats ) ) {
								unset( $categories[ $pid ] );
							}
						}
					}

					/* push the main category to the top of this array */
					if ( isset( $categories [ $this->parent ] ) ) {
						$main = $categories [ $this->parent ];
						unset( $categories[ $this->parent ] );
						$categories[ $this->parent ] = $main;
					}

					foreach ( $categories as $pid => $categoryData ) {
						$this->categories[ $pid ] = $categoryData;
					}

					if ( $this->categories ) {
						$labels = SPLang::translateObject( array_keys( $this->categories ), [ 'name', 'alias' ], 'category' );
						foreach ( $labels as $id => $t ) {
							$this->categories[ $id ][ 'name' ] = $t[ 'value' ] ?? $t[ 'name' ];
							$this->categories[ $id ][ 'alias' ] = $t[ 'alias' ];
						}
					}
					Sobi::Trigger( $this->name(), ucfirst( __FUNCTION__ ), [ &$this->categories ] );
				}
				catch ( SPException $x ) {
					Sobi::Error( $this->name(), SPLang::e( 'CANNOT_GET_RELATIONS_DB_ERR', $x->getMessage() ), C::ERROR, 500, __LINE__, __FILE__ );
				}
			}
			if ( $indexed ) {
				/* return an indexed array without key */
				return array_keys( $this->categories );
			}
			else {
				/* return an associative array */
				return $this->categories;
			}
		}
		else {
			return [];
		}
	}

	/**
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	public function validateCache()
	{
		static $remove = [ 'name', 'nid', 'metaDesc', 'metaKeys', 'metaRobots', 'options', 'oType', 'parent' ];
		$core = SPFactory::object( $this->id );
		foreach ( $core as $a => $v ) {
			if ( !in_array( $a, $remove ) ) {
				$this->_set( $a, $v );
			}
		}
	}

	/**
	 * @param int $sid
	 * @param bool $enabled
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function loadFields( $sid = 0, $enabled = false )
	{
		$sid = $sid ? : $this->section;

		if ( $sid == 0 ) {
			return;     // no section, don't know why :(
		}

		static $lang = C::ES;
		/**
		 * Wed, Dec 6, 2017 10:57:28
		 * When multilingual mode is disabled, and we are editing in administrator area
		 * Use the default frontend language
		 * Nov 2, 2018 (Sigrid)
		 * Sobi::Lang( true ) returns the default language if no multilingual mode and we are editing in administrator area
		 * 06 January 2022 by Sigrid Suski: no, no longer
		 */
		$lang = $lang ? : Sobi::Lang();

		static $fields = [];

		/* get the field id (fid) of the field which contains the entry name */
		$nameFieldFid = SPFactory::config()->nameFieldFid( $sid );

		$db = Factory::Db();
		if ( !isset( $fields[ $sid ] ) ) {
			/* get fields */
			try {
				if ( $enabled ) {
					$db->select( '*', 'spdb_field', [ 'section' => $sid, 'enabled' => 1, 'adminField>' => -1 ], 'position' );
				}
				else {
					$db->select( '*', 'spdb_field', [ 'section' => $sid, 'adminField>' => -1 ], 'position' );
				}
				$field = $db->loadObjectList();
				if ( count( $field ) ) {
					$fields[ $sid ] = $field;
				}
				Sobi::Trigger( $this->name(), ucfirst( __FUNCTION__ ), [ &$fields ] );
			}
			catch ( SPException $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'CANNOT_GET_FIELDS_DB_ERR', $x->getMessage() ), C::ERROR, 500, __LINE__, __FILE__ );
			}
		}

		if ( !$this->_loaded && count( $fields ) ) {
			if ( count( $fields[ $sid ] ) ) {
				/* if it is an entry - prefetch the basic fields data */
				if ( $this->id ) {
					$noCopy = $this->checkCopy();
					/* in case the entry is approved, or we are editing an entry, or the user can see unapproved changes */
					if ( $this->approved || $noCopy ) {
						$ordering = 'copy.desc';
					} /* otherwise - if the entry is not approved, get the non-copies first */
					else {
						$ordering = 'copy.asc';
					}
					try {
						/* get the content of all fields for this entry in $fieldsdata */
						$fdata = $db
							->select( '*', 'spdb_field_data', [ 'sid' => $this->id ], $ordering )
							->loadObjectList();

						$fieldsdata = [];
						if ( count( $fdata ) ) {
							foreach ( $fdata as $data ) {
								/* if it has been already set - check if it is not better language choose */
								if ( isset( $fieldsdata[ $data->fid ] ) ) {
									/*
									 * I know - the whole thing could be shorter
									 * but it is better to understand and debug this way
									 */
									if ( $data->lang == $lang ) {
										if ( $noCopy ) {
											if ( !$data->copy ) {
												$fieldsdata[ $data->fid ] = $data;
											}
										}
										else {
											$fieldsdata[ $data->fid ] = $data;
										}
									} /* set for cache other lang */
									else {
										$fieldsdata[ 'langs' ][ $data->lang ][ $data->fid ] = $data;
									}
								}
								else {
									if ( $noCopy ) {
										if ( !$data->copy ) {
											$fieldsdata[ $data->fid ] = $data;
										}
									}
									else {
										$fieldsdata[ $data->fid ] = $data;
									}
								}
							}
						}
						unset( $fdata );
						SPFactory::registry()->set( 'fields_data_' . $this->id, $fieldsdata );
					}
					catch ( Sobi\Error\Exception $x ) {
						Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
					}
				}
				foreach ( $fields[ $sid ] as $field ) {
					/* @var SPField $fieldObj */
					$fieldObj = SPFactory::Model( 'field', defined( 'SOBIPRO_ADM' ) );
					$fieldObj->extend( $field );
					if ( $fieldObj->get( 'enabled' ) ) {
						if ( isset( $fieldsdata[ $field->fid ] ) ) {
							$fieldObj->loadData( $this->id );
						}
						$this->fields[] = $fieldObj;
						$this->fieldsNids[ $fieldObj->get( 'nid' ) ] = $this->fields[ count( $this->fields ) - 1 ];
						$this->fieldsIds[ $fieldObj->get( 'fid' ) ] = $this->fields[ count( $this->fields ) - 1 ];
						/* case it was the name field */
						if ( $fieldObj->get( 'fid' ) == $nameFieldFid ) {
							/* get the content of the name field (entry name) */
							$this->name = $fieldObj->getRaw();
							/* get the nid of the name field (name field alias) */
							$this->nameFieldNid = $fieldObj->get( 'nid' );
						}
					}
				}
				$this->_loaded = true;
			}
		}
	}

	/**
	 * @return bool
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function checkCopy(): bool
	{
		return !(
			in_array( Input::Task(), [ 'entry.approve', 'entry.edit', 'entry.save', 'entry.submit', 'entry.payment' ] )
			|| Sobi::Can( 'entry.access.unapproved_any' )
			|| ( $this->owner == Sobi::My( 'id' ) && Sobi::Can( 'entry.manage.own' ) )
			|| ( $this->owner == Sobi::My( 'id' ) && Sobi::Can( 'entry.access.unpublished_own' ) )
			|| Sobi::Can( 'entry.manage.*' )
		);
	}

	/**
	 * @return array
	 */
	protected function types()
	{
		return self::$types;
	}

	/**
	 * Deletes the field's data, history changes, payments, counters of an entry and its cache.
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function delete()
	{
		parent::delete(); /* this throws a trigger before deleting anything */

		/* delete all field data */
		foreach ( $this->fields as $field ) {
			$field->deleteData( $this->id );
		}
		/* 23 Juli 2021 -  delete the stored changes, but not the logs */
		Factory::Db()
			->delete( 'spdb_history', [ 'sid' => $this->id, 'changeAction' => SPC::LOG_SAVE ] )
			->delete( 'spdb_counter', [ 'sid' => $this->id ] );

		SPFactory::payment()->deletePayments( $this->id );

		Sobi::Trigger( $this->name(), 'AfterDelete' ); // trigger after everything has been deleted

		/* Clean the cache */
		SPFactory::cache()
			->purgeSectionVars()
			->deleteGlobalObjs()
			->deleteObj( 'entry', $this->id );
	}

	/**
	 * Checks an entry with its fields before saving it.
	 * Called from backend only (frontend => submit())
	 *
	 * @param string $request
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function validate( $request = 'post', $clone = false )
	{
		$this->loadFields( Sobi::Section() );
		$error = false;
		foreach ( $this->fields as $field ) {
			/* @var SPField $field */
			if ( $field->enabled( 'form', !$this->id ) ) {
				try {
					$field->validate( $this, $request, $clone );
				}
				catch ( SPException $x ) {
					$error = true;
					$msgs[ $field->get( 'nid' ) ] = $x->getMessage();
				}
			}
		}
		if ( $error ) {
			$exception = new SPException( Sobi::Txt( 'ERRORS_OCCURRED' ) );
			$exception->setData( [ 'messages' => $msgs ] );
//				$exception->setData( [ 'field' => $field->get( 'nid' ) ] );
			throw $exception;
		}
	}

	/**
	 * Save entry specific values.
	 *
	 * @param string $request
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 * @throws \ReflectionException
	 */
	public function save( $request = 'post' )
	{
		$this->loadFields( Sobi::Section(), true );
		Sobi::Trigger( $this->name(), 'BeforeSave', [ &$this->id ] );

		/* save the base object data */
		$db = Factory::Db();
		//$db->transaction();
		$clone = Input::Task() == 'entry.clone';

		if ( !$this->nid || $clone ) {
			$nid = Input::String( $this->nameFieldNid ) ? Input::String( $this->nameFieldNid ) : uniqid( 'temp_alias_' );
			$this->nid = strtolower( StringUtils::Nid( $nid, true ) );
			$this->nid = $this->createAlias();
		}
		/* in case the alias was entered manually */
		else {
			$this->nid = substr( $this->nid, 0, 190 );
		}
		if ( !$this->id && Sobi::Cfg( 'entry.publish_limit', 0 ) && !defined( 'SOBI_ADM_PATH' ) ) {
			Input::Set( 'entry_createdTime', 0 );
			Input::Set( 'entry_validSince', 0 );
			Input::Set( 'entry_validUntil', 0 );
			$this->validUntil = gmdate( Sobi::Cfg( 'date.db_format', SPC::DEFAULT_DB_DATE ), time() + ( Sobi::Cfg( 'entry.publish_limit', 0 ) * 24 * 3600 ) );
		}
		$preState = Sobi::Reg( 'object_previous_state' );
		parent::save( $request );
		if ( $clone ) {
			$this->changeState( 0, C::ES, false );
		}

		/* get the field id of the field contains the entry name */
		$nameFieldFid = SPFactory::config()->nameFieldFid( $this->section );

		/* get the fields for this section */
		foreach ( $this->fields as $field ) {
			/* @var SPField $field */
			try {
				if ( $field->enabled( 'form', $preState[ 'new' ] ) ) {
					$field->saveData( $this, $request, $clone );
				}
				else {
					$field->finaliseSave( $this, $request, $clone );
				}

				/* if we just process the name field */
				if ( $field->get( 'id' ) == $nameFieldFid ) {
					/* get the entry name */
					$this->name = $field->getRaw();
					/* save the nid (name id) of the field where the entry name is saved */
					$this->nameFieldNid = $field->get( 'nid' );
				}
			}
			catch ( Exception|SPException $x ) {
				if ( Input::Task() != 'entry.clone' ) {
					$db->rollback();
					throw new SPException( SPLang::e( 'CANNOT_SAVE_FIELS_DATA', $x->getMessage() ) );
				}
				else {
					Sobi::Error( $this->name(), SPLang::e( 'CANNOT_SAVE_FIELS_DATA', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
				}
			}
		}
		$values = [];
		/* get categories */
		$cats = Sobi::Reg( 'request_categories', [] );
		if ( !count( $cats ) ) {
			$cats = Input::Arr( 'entry_parent', 'request', SPFactory::registry()->get( 'request_categories', [] ) );
		}
		/* by default, it should be comma-separated string */
		if ( !count( $cats ) ) {
			$cats = Input::String( 'entry_parent' );
			if ( strlen( $cats ) && strpos( $cats, ',' ) ) {
				$cats = explode( ',', $cats );
				foreach ( $cats as $index => $cat ) {
					$category = ( int ) trim( $cat );
					if ( $category ) {
						$cats[ $index ] = $category;
					}
					else {
						unset( $cats[ $index ] );
					}
				}
			}
			else {
				if ( strlen( $cats ) ) {
					$cats = [ ( int ) $cats ];
				}
			}
		}
		if ( is_array( $cats ) && count( $cats ) ) {
			foreach ( $cats as $index => $value ) {
				if ( !$value ) {
					unset( $cats[ $index ] );
				}
			}
		}
		if ( is_array( $cats ) && count( $cats ) ) {
			/* get the ordering in these categories */
			try {
//				$db->select( 'pid, MAX(position)', 'spdb_relations', [ 'pid' => $cats, 'oType' => 'entry' ], null, 0, 0, false, 'pid' );
//				$cPos = $db->loadAssocList( 'pid' );
				$cPos = $db( 'select', [
						'toSelect' => 'pid, MAX(position)',
						'tables'   => 'spdb_relations',
						'where'    => [ 'pid' => $cats, 'oType' => 'entry' ],
						'groupBy'  => 'pid',
					]
				)->loadAssocList( 'pid' );
				$currPos = $db
					->select( [ 'pid', 'position' ], 'spdb_relations', [ 'id' => $this->id, 'oType' => 'entry' ] )
					->loadAssocList( 'pid' );
			}
			catch ( SPException $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::ERROR, 500, __LINE__, __FILE__ );
			}
			/* set the right position */
			foreach ( $cats as $index => $cat ) {
				$copy = 0;
				if ( !$this->approved ) {
					$copy = isset( $this->categories[ $cat ] ) ? 0 : 1;
				}
				else {
					$db->delete( 'spdb_relations', [ 'id' => $this->id, 'oType' => 'entry' ] );
				}
				if ( isset( $currPos[ $cat ] ) ) {
					$pos = $currPos[ $cat ][ 'position' ];
				}
				else {
					$pos = isset( $cPos[ $cat ] ) ? $cPos[ $cat ][ 'MAX(position)' ] : 0;
					$pos++;
				}
				$values[] = [ 'id'         => $this->id,
				              'pid' => $cat,
				              'oType'      => 'entry',
				              'position'   => $pos,
				              'validSince' => $this->validSince ? : $db->getNullDate(),
				              'validUntil' => $this->validUntil ? : $db->getNullDate(),
				              'copy'       => $copy ];
			}
			try {
				$db->insertArray( 'spdb_relations', $values, true );
			}
			catch ( Sobi\Error\Exception $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::ERROR, 500, __LINE__, __FILE__ );
			}
		}
		else {
			$db->delete( 'spdb_relations', [ 'id' => $this->id, 'oType' => 'entry' ] );
//			if ( !count( $this->categories ) ) {
//				throw new SPException( SPLang::e( 'MISSING_CAT' ) );
//			}
		}
		if ( $preState[ 'new' ] ) {
			$this->countVisit( true );
		}
		/* trigger possible state changes */
		if ( $preState[ 'approved' ] != $this->approved ) {
			if ( $this->approved ) {
				$this->approveFields( true );
				// it's being done by the method above - removing
				//Sobi::Trigger( $this->name(), 'AfterApprove', array( $this->id, $this->approved ) );
			}
		}
		if ( $preState[ 'state' ] != $this->state ) {
			Sobi::Trigger( $this->name(), 'AfterChangeState', [ $this->id, $this->state ] );
		}

		if ( !$preState[ 'new' ] ) {
			Sobi::Trigger( $this->name(), 'AfterUpdate', [ &$this ] );
		}
		else {
			Sobi::Trigger( $this->name(), 'AfterSave', [ &$this ] );
		}

		/* Clean the cache */
		if ( is_array( $cats ) && count( $cats ) ) {
			foreach ( $cats as $cat ) {
				SPFactory::cache()->deleteObj( 'category', $cat );
			}
		}
		SPFactory::cache()
			->purgeSectionVars()
			->deleteGlobalObjs()
			->deleteObj( 'entry', $this->id );
	}

	/**
	 * @return array
	 */
	public function getCurrentBaseData(): array
	{
		$data = [];
		$data[ 'owner' ] = $this->owner;
		$data[ 'categories' ] = $this->categories;
		$data[ 'position' ] = $this->position;
		$data[ 'createdTime' ] = $this->createdTime;
		$data[ 'updatedTime' ] = $this->updatedTime;
		$data[ 'updater' ] = $this->updater;
		$data[ 'updaterIP' ] = $this->updaterIP;
		$data[ 'counter' ] = $this->counter;
		$data[ 'nid' ] = $this->nid;
		$data[ 'ownerIP' ] = $this->ownerIP;
		$data[ 'parent' ] = $this->parent;
		$data[ 'state' ] = $this->state;
		$data[ 'approved' ] = $this->approved;
		$data[ 'validSince' ] = $this->validSince;
		$data[ 'validUntil' ] = $this->validUntil;
		$data[ 'version' ] = $this->version;
		$data[ 'metaDesc' ] = $this->metaDesc;
		$data[ 'metaKeys' ] = $this->metaKeys;
		$data[ 'metaAuthor' ] = $this->metaAuthor;
		$data[ 'metaRobots' ] = $this->metaRobots;

		return $data;
	}

	/**
	 * @param $attr
	 * @param $value
	 */
	public function setRevData( $attr, $value )
	{
		$this->$attr = $value;
	}
}