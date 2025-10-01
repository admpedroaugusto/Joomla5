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
 * @created 10-Jan-2009 by Radek Suski
 * @modified 16 January 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadModel( 'datamodel' );
SPLoader::loadModel( 'dbobject' );

use Sobi\C;
use Sobi\Input\Input;
use Sobi\Lib\Factory;
use Sobi\Utils\StringUtils;

/**
 * Class SPCategory
 */
class SPCategory extends SPDBObject implements SPDataModel
{
	/*** @var string */
	protected $description = C::ES;
	/*** @var string */
	protected $icon = C::ES;
	/*** @var int */
	protected $showIcon = C::GLOBAL;
	/*** @var string */
	protected $introtext = C::ES;
	/*** @var int */
	protected $showIntrotext = C::GLOBAL;
	/*** @var int */
	protected $parseDesc = C::GLOBAL;
	/*** @var int */
	protected $parent = 0;
	/** @var array */
	protected $entryFields = [];
	/** @var bool */
	protected $allFields = true;
	/*** @var string */
	public $url = C::ES;
	/*** @var array */
	private static $types = [
		'description'   => 'Html',
		'icon'          => 'String',
		'showIcon' => 'Int',
		'introtext'     => 'String',
		'showIntrotext' => 'Int',
		'parseDesc'     => 'Int',
		'entryFields'   => 'Int',
		'allFields'     => 'Int',
		'url'      => 'String',
	];
	/*** @var array */
	private static $translatable = [ 'description', 'introtext', 'name', 'metaKeys', 'metaDesc' ];
	/** @var string */
	/*** @var int */
	protected $position = 0;
	/*** @var int */
	protected $section = 0;
	/*** @var string */
	protected $oType = 'category';
	protected $_dbTable = 'spdb_category';
	/** @var array */
	protected $fields = [];

	/**
	 * @param string $request
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function save( $request = 'post' )
	{
		/* initial org settings */
		$db = Factory::Db();

		$this->approved = Sobi::Can( $this->type(), 'publish', 'own' );
		$this->section = Sobi::Section();

		$clone = Input::Task() == 'category.clone';
		if ( $clone ) {
			$this->id = 0;
		}
		$this->nid = $this->createAlias();
		parent::save();

		$properties = get_class_vars( __CLASS__ );

		/* get database columns and their ordering */
		$cols = $db->getColumns( $this->_dbTable );
		$values = [];
		$values[ 'parseDesc' ] = $values[ 'parseDesc' ] ? '2' : '0';
		/* and sort the properties in the same order along with their values */
		foreach ( $cols as $col ) {
			$values[ $col ] = array_key_exists( $col, $properties ) ? $this->$col : C::ES;
		}
		if ( !Input::Int( 'category_allFields' ) && Input::Arr( 'fid' ) ) {
			$values[ 'entryFields' ] = Input::Arr( 'fid' );
			$values[ 'entryFields' ][] = Sobi::Cfg( 'entry.name_field' );
		}
		Sobi::Trigger( $this->name(), ucfirst( __FUNCTION__ ), [ &$values ] );
		/* try to save */
		try {
			/* do not save language dependent values in the category table */
//			if ( array_key_exists( 'description', $values ) ) {
//				$values[ 'description' ] = null;
//			}
//			if ( array_key_exists( 'introtext', $values ) ) {
//				$values[ 'introtext' ] = null;
//			}
			$db->insertUpdate( $this->_dbTable, $values );
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'CANNOT_SAVE_CATEGORY_DB_ERR', $x->getMessage() ), C::ERROR, 500, __LINE__, __FILE__ );
		}

		/* insert relation */
		try {
			$db->delete( 'spdb_relations', [ 'id' => $this->id, 'oType' => 'category' ] );
			if ( !$this->position ) {
				$this->position = ( int ) $db
					->select( 'MAX( position ) + 1', 'spdb_relations', [ 'pid' => $this->parent, 'oType' => 'category' ] )
					->loadResult();
				if ( !$this->position ) {
					$this->position = 1;
				}
			}
			$db->insertUpdate( 'spdb_relations',
				[ 'id'         => $this->id,
				  'pid'        => $this->parent,
				  'oType'      => 'category',
				  'position'   => $this->position,
				  'validSince' => $this->validSince,
				  'validUntil' => $this->validUntil ]
			);
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'CANNOT_SAVE_CATEGORY_DB_ERR', $x->getMessage() ), C::ERROR, 500, __LINE__, __FILE__ );
		}
		$this->loadFields( Sobi::Section(), true );
		foreach ( $this->fields as $field ) {
			/* @var SPField $field */
			try {
				if ( $field->enabled( 'form' ) ) {
					$field->saveData( $this, $request, $clone );
				}
				else {
					$field->finaliseSave( $this, $request, $clone );
				}
			}
			catch ( SPException $x ) {
				throw $x;
			}
		}

		/* Clean the cache */
		SPFactory::cache()
			->cleanCategories()
			->deleteGlobalObjs()
			->purgeSectionVars()
			/* instead of purging all vars, delete only the parent and itself -> did not work */
//			->deleteVar( 'childs_' . $lang . '_category', $this->section, $lang, $this->id )
//			->deleteVar( 'childs_' . $lang . '_category', $this->section, $lang, $this->parent )
			->deleteObj( 'category', $this->id )
			->deleteObj( 'category', $this->parent );

		/* trigger plugins */
		Sobi::Trigger( 'afterSave', $this->name(), [ &$this ] );
	}

	/**
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function loadTable()
	{
		parent::loadTable();
		!$this->icon ? : StringUtils::Clean( $this->icon );
//		$this->icon = StringUtils::Clean( $this->icon );
		try {
			$result = Factory::Db()
				->select( [ 'position', 'pid' ], 'spdb_relations', [ 'id' => $this->id ] )
				->loadObject();

			Sobi::Trigger( $this->name(), ucfirst( __FUNCTION__ ), [ &$result ] );

			if ( count( (array) $result ) ) {
				$this->position = $result->position;
				$this->parent = $result->pid;
			}
		}
		catch ( SPException $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}

		if ( Input::Task() != 'category.edit' ) {
			if ( $this->parseDesc == C::GLOBAL ) {
				$this->parseDesc = Sobi::Cfg( 'category.parse_desc', true );
			}
			if ( $this->parseDesc ) {
				Sobi::Trigger( 'Parse', 'Content', [ &$this->description ] );
			}
		}
	}

	/**
	 * @param bool $childs -> true = update child entries parent
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function delete( bool $childs = true )
	{
		parent::delete();   /* delete the category from the object table */

		static $lang = C::ES;
		if ( !$lang ) {
			$lang = Sobi::Lang( false );
		}

		/* clean the content!! of the whole section. Later try to get single clean working */
		//SPFactory::cache()->cleanSection( (int) Sobi::Section(), false );
		//SPFactory::cache()->cleanCategories();
		try {
			/* get all child cats and delete these too (has to be called first as it recreates the cache) */
			$childs = $this->getChilds( 'category', true );
			if ( count( $childs ) ) {
				foreach ( $childs as $child ) {
					$cat = new self();
					$cat->init( $child );
					$cat->delete( false );
				}
			}
			$childs[ $this->id ] = $this->id;
			Factory::Db()->delete( 'spdb_category', [ 'id' => $this->id ] );

			/* Clean the cache */
			SPFactory::cache()
				->purgeSectionVars( (int) Sobi::Section() )
				->deleteGlobalObjs()
				->deleteObj( 'category', $this->id );

			$childs = $this->getChilds( 'category', true );
			if ( $childs ) {
				Factory::Db()->update( 'spdb_object', [ 'parent' => Sobi::Section() ], [ 'parent' => $childs ] );
			}

			/* delete all field data for this category */
			$this->loadFields( Sobi::Section() );
			foreach ( $this->fields as $field ) {
				$field->deleteData( $this->id );
			}
			Factory::Db()->delete( 'spdb_counter', [ 'sid' => $this->id ] );
		}
		catch ( SPException $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'CANNOT_DELETE_CATEGORY_DB_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}
	}

	/**
	 * @return array
	 */
	protected function types()
	{
		return self::$types;
	}

	/**
	 * @return array
	 */
	protected function translatable()
	{
		return self::$translatable;
	}

	/**
	 * @param string $by
	 *
	 * @return array
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function & getFields( $by = 'name' )
	{
		Sobi::Trigger( $this->name(), ucfirst( __FUNCTION__ ), [ &$this->fields ] );

		return $this->fields;
	}

	/**
	 * @param int $sid
	 * @param bool $enabled
	 *
	 * @return $this
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function & loadFields( $sid = 0, bool $enabled = false )
	{
		$sid = $sid ? : $this->section;
		$db = Factory::Db();

		static $fields = [];
		if ( !isset( $fields[ $sid ] ) ) {
			/* get fields */
			try {
				if ( $enabled ) {
					$db->select( '*', 'spdb_field', [ 'section' => $sid, 'enabled' => 1, 'adminField' => -1 ], 'position' );
				}
				else {
					$db->select( '*', 'spdb_field', [ 'section' => $sid, 'adminField' => -1 ], 'position' );
				}
				$fields[ $sid ] = $db->loadObjectList();
				Sobi::Trigger( $this->name(), ucfirst( __FUNCTION__ ), [ &$fields ] );
			}
			catch ( SPException $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'CANNOT_GET_FIELDS_DB_ERR', $x->getMessage() ), C::ERROR, 500, __LINE__, __FILE__ );
			}
		}
		if ( !count( $this->fields ) ) {
			foreach ( $fields[ $sid ] as $f ) {
				/** @var SPField $field */
				$field = SPFactory::Model( 'field', defined( 'SOBIPRO_ADM' ) );
				$field->extend( $f );
				$field->loadData( $this->id );
				$this->fields[ $f->fid ] = $field;
			}
		}

		return $this;
	}
}
