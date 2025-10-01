<?php
/**
 * @package SobiPro multi-directory component with content construction support
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006–2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/GPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according to section 7 of GPL v3.
 * See https://www.gnu.org/licenses/gpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * @created 10-Jan-2009 by Radek Suski
 * @modified 19 June 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadClass( 'models.fields.interface' );

use Sobi\C;
use Sobi\Input\Input;
use Sobi\Lib\Factory;
use Sobi\Utils\Arr;
use Sobi\Utils\StringUtils;

/**
 * Class SPFieldType
 */
class SPFieldType extends SPObject
{
	/*** @var SPField */
	private $_field = null;
	/*** @var array */
	protected $_attr = [];
	/*** @var string|array */
	protected $_selected = C::ES;
	/*** @var string|array */
	protected $_rdata = C::ES;
	/** @var array */
	protected $sets = [];

	/* properties for all derived classes (also properties of the model) */
	/*** @var string */
	protected $dType = 'free_single_simple_data';
	/** @var int */
	protected $bsWidth = 6;
	/** @var int */
	protected $bsSearchWidth = 6;
	/** @var string */
	protected $metaSeparator = ' ';
	/*** @var string */
	protected $cssClass = C::ES;
	/** @var bool */
	protected $showEditLabel = true;
	/*** @var bool */
	protected $suggesting = true;
	/** @var string */
	protected $searchMethod = 'general';
	/** @var string */
	protected $helpposition = 'below';
	/** @var string */
	protected $itemprop = C::ES;
	/*** @var bool */
	protected $isOutputOnly = false;

	public const STYLEFILE = C::ES;

	/**
	 * SPFieldType constructor.
	 *
	 * @param $field
	 */
	public function __construct( &$field )
	{
		/* set the field specific values for those properties which are defined in the model, but have a field specific value */
		if ( !$field->get( 'cssClass' ) ) {
			$field->set( 'cssClass', $this->cssClass );
			$field->set( 'searchMethod', $this->searchMethod );
		}
		$this->_field =& $field;
		/* transform params from the basic object to the spec. field properties */
		if ( is_array( $this->params ) && count( $this->params ) ) {
			foreach ( $this->params as $var => $value ) {
				$this->set( $var, $value );
			}
		}
		$this->set( 'cssClass', $field->get( 'cssClass' ) );
	}

	/**
	 * @param string $name
	 * @param $value
	 *
	 * @return void
	 */
	public function __set( string $name, $value )
	{
		if ( !isset( $this->$name ) ) {
			$this->_field->set( $name, $value );
		}
	}

	/**
	 * Proxy pattern.
	 *
	 * @param $property
	 *
	 * @return array|mixed|string|string[]|null
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function __get( $property )
	{
		if ( !isset( $this->$property ) && $this->_field ) {
			return $this->_field->get( $property );
		}
		else {
			return $this->get( $property );
		}
	}

	/**
	 * @param string $var
	 * @param mixed $val
	 *
	 * @return \SPFieldType
	 */
	public function &set( $var, $val )
	{
		if ( isset( $this->$var ) ) {
			$this->$var = $val;
		}

		return $this;
	}

	/**
	 * Proxy pattern.
	 *
	 * @param string $method
	 * @param array $args
	 *
	 * @return mixed
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function __call( $method, $args )
	{
		if ( $this->_field && method_exists( $this->_field, $method ) ) {
			return call_user_func_array( [ $this->_field, $method ], $args );
		}
		else {
			throw new SPException( SPLang::e( 'CALL_TO_UNDEFINED_METHOD_S', $method ) );
		}
	}

	/**
	 * @return string
	 */
	public function getStyleFile()
	{
		return self::STYLEFILE;
	}

	/**
	 * Will be called also from Imex import (Image, Gallery, Download).
	 *
	 * @param $entry
	 * @param string $name
	 * @param string $pattern
	 * @param bool $addExt
	 * @param bool $altTag
	 *
	 * @return string|null
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function parseName( $entry, string $name, string $pattern, bool $addExt = false, bool $altTag = false )
	{
		$ext = C::ES;
		if ( !$altTag ) {
			$nameArray = explode( '.', $name );
			$ext = strtolower( array_pop( $nameArray ) );
			$name = implode( '.', $nameArray );
		}
		$user = SPUser::getBaseData( ( int ) $entry->get( 'owner' ) );
		if ( $entry->get( 'oType' ) == 'category' ) {
			$id = $entry->get( 'parent' );
			$category = SPLang::translateObject( [ $id ], [ 'name', 'alias' ] );
			$category = $category[ $id ][ 'nid' ];
		}
		else {
			/* exists only for Imex import, export */
			if ( method_exists( $entry, 'getPlaceholderCategory' ) ) {
				$category = $entry->getPlaceholderCategory();
			}
			else {
				$category = $entry->getPrimary();
			}
			$category = is_array( $category ) && count( $category ) ? $category[ 'alias' ] : C::ES;
		}
		/* in case the image field is before the name field, the entry name is not yet set in the entry object.
		In this case we get the entry name from the name field directly. */
		$entryname = C::ES;
		if ( $entry->get( 'name' ) ) {
			$entryname = StringUtils::Nid( $entry->get( 'name' ) );
		}
		else {
			$nameFieldNid = $entry->get( 'nameFieldNid' );
			$nameFieldNid = strlen( $nameFieldNid ) ? $nameFieldNid : SPFactory::config()->nameFieldNid( (int) Sobi::Section() );
			$namefield = $entry->getField( $nameFieldNid );
			if ( $namefield ) {
				$entryname = $namefield->data();
				$entryname = $entryname ? StringUtils::Nid( $entryname ) : C::ES;
			}
		}
		// @todo change to the global method
		$placeHolders = [ '/{id}/', '/{orgname}/', '/{entryalias}/', '/{entryname}/', '/{oid}/', '/{ownername}/', '/{uid}/', '/{username}/', '/{nid}/', '/{category}/' ];
		$replacements = [ $entry->get( 'id' ),
		                  $name,
		                  $entry->get( 'nid' ),
		                  $entryname,
		                  $user->id ?? null,
		                  isset( $user->name ) ? StringUtils::Nid( $user->name ) : 'Guest',
		                  Sobi::My( 'id' ),
		                  Sobi::My( 'name' ) ? StringUtils::Nid( Sobi::My( 'name' ) ) : C::ES,
		                  $this->nid,
		                  $category ];
		$fileName = preg_replace( $placeHolders, $replacements, $pattern );

		return $addExt ? $fileName . '.' . $ext : $fileName;
	}

	/**
	 * @param string $val
	 *
	 * @return void
	 */
	public function setSelected( $val )
	{
		$this->_selected = $val;
	}

	/**
	 * Adds the field specific attributes as param to the general attributes.
	 *
	 * @param $attr -> general attributes
	 *
	 * @return void
	 */
	public function save( &$attr )
	{
		$this->_attr =& $attr;
		if ( !isset( $attr[ 'params' ] ) ) {
			$attr[ 'params' ] = [];
		}

		$properties = [];
		$fieldAttributes = $this->getAttr();
		if ( count( $fieldAttributes ) ) {
			foreach ( $fieldAttributes as $property ) {
				if ( $property == 'untranslatable' && $attr[ 'adminField' ] == -1 ) {
					$attr[ $property ] = 0;
				}
				$properties[ $property ] = isset( $attr[ $property ] ) ? ( $attr[ $property ] ) : null;
			}
		}
		$defaults = $this->getDefaults();
		if ( is_array( $defaults ) && count( $defaults ) ) {
			foreach ( $defaults as $property => $default ) {
				$properties[ $property ] = isset( $properties[ $property ] ) ? ( $properties[ $property ] ) : $default;
			}
		}
		$attr[ 'params' ] = $properties;
	}

	/**
	 * @param array $params
	 * @param int $version
	 * @param bool $untranslatable
	 * @param bool $log
	 *
	 * @return void|string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function saveToDatabase( array $params, int $version, bool $untranslatable = false, bool $log = false )
	{
		$db = Factory::Db();

		// if field is not translatable, save it for all available languages
		$multiLang = (bool) Sobi::Cfg( 'lang.multimode', false );
		if ( $untranslatable && $multiLang ) {
			$langs = SPLang::availableLanguages();
			if ( $langs ) {
				foreach ( $langs as $lang => $short ) {
					$params[ 'lang' ] = $lang;
					try {
						$db->insertUpdate( 'spdb_field_data', $params );
					}
					catch ( Sobi\Error\Exception $x ) {
						if ( $log ) {
							return SPLang::e( 'CANNOT_SAVE_DATA', $x->getMessage() );
						}
						else {
							Sobi::Error( __CLASS__, SPLang::e( 'CANNOT_SAVE_DATA', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
						}
					}
				}
			}
		}

		/* if field is translatable and on non-multilingual sites, handle it as always */
		else {
			try {
				/* Notices:
					* If it was new entry - insert
					* If it was an edit and the field wasn't filled before - insert
					* If it was an edit and the field was filled before - update
					*     " ... " and changes are not auto-publish it should be inserted of the copy .... but
					* " ... " if a copy already exist it is update again
					* */
				$db->insertUpdate( 'spdb_field_data', $params );
			}
			catch ( Sobi\Error\Exception $x ) {
				if ( $log ) {
					return SPLang::e( 'CANNOT_SAVE_DATA', $x->getMessage() );
				}
				else {
					Sobi::Error( __CLASS__, SPLang::e( 'CANNOT_SAVE_DATA', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
				}
			}

			/* 30 August 2018, Sigrid: if the entry wasn't ADDED in the default language, we have to insert it also for the default language */
			/* in backend we save only in language Sobi::Lang() when multilingual mode is on */
			if ( !( defined( 'SOBIPRO_ADM' ) && $multiLang ) && ( Sobi::Lang() != Sobi::DefLang() ) && $version == 1 ) {
				/* if we are here, it is translatable */
				$params[ 'lang' ] = Sobi::DefLang();
				try {
					$db->insert( 'spdb_field_data', $params, true, true );
				}
				catch ( Sobi\Error\Exception $x ) {
					if ( $log ) {
						return SPLang::e( 'CANNOT_SAVE_DATA', $x->getMessage() );
					}
					else {
						Sobi::Error( __CLASS__, SPLang::e( 'CANNOT_SAVE_DATA', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
					}
				}
			}
		}
		if ( $log ) {
			return C::ES;
		}
	}

	/**
	 * This function is used for the case that a field wasn't used for some reason while saving an entry.
	 * But it has to perform some operation
	 * E.g. Category field is set to be administrative and isn't used,
	 * but it needs to pass the previously selected categories to the entry model.
	 *
	 * @param SPEntry $entry
	 * @param string $request
	 *
	 * @return bool
	 * */
	public function finaliseSave( $entry, string $request = 'post' ): bool
	{
		return true;
	}

	/**
	 * @param \SPEntry|\SPCategory $entry
	 * @param $baseData
	 *
	 * @return void
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function setEditLimit( $entry, $baseData ): void
	{
		$db = Factory::Db();
		if ( Sobi::My( 'id' ) == $entry->get( 'owner' ) && !$this->encryptData ) {
			$oldData = $db
				->select( 'baseData', 'spdb_field_data',
					[ 'sid'  => $entry->get( 'id' ),
					  'lang' => Sobi::Lang(),
					  'fid'  => $this->fid,
					  'copy' => '1' ]
				)
				->loadResult();
			if ( !$oldData ) {
				$oldData = $db
					->select( 'baseData', 'spdb_field_data',
						[ 'sid'  => $entry->get( 'id' ),
						  'fid'  => $this->fid,
						  'lang' => Sobi::Lang(),
						  'copy' => '0' ]
					)
					->loadResult();
			}
			if ( $oldData != $baseData ) {
				--$this->editLimit;
			}
		}
	}

	/**
	 * @return void
	 */
	public function cleanCss()
	{
		$css = explode( ' ', $this->cssClass );
		if ( count( $css ) ) {
			$this->cssClass = implode( ' ', array_unique( $css ) );
		}
	}

	/**
	 * @param string $val
	 *
	 * @return void
	 */
	public function setCSS( string $val = 'sp-field' )
	{
		$this->cssClass = $val;
	}

	/**
	 * Returns meta description.
	 *
	 * @return string
	 */
	public function metaDesc()
	{
		return $this->addToMetaDesc ? $this->data() : C::ES;
	}

	/** Returns meta keys.
	 *
	 * @return string
	 */
	public function metaKeys()
	{
		return $this->addToMetaKeys ? $this->data() : C::ES;
	}

	/**
	 * @return array
	 */
	public function properties()
	{
		return $this->getAttr();
	}

	/**
	 * @param $sid
	 *
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	public function approve( $sid )
	{
		$db = Factory::Db();
		static $lang = C::ES;
		if ( !$lang ) {
			$lang = Sobi::Lang( false );
		}
		try {
			$copy = $db
				->select( 'COUNT( fid )', 'spdb_field_data', [ 'sid' => $sid, 'copy' => '1', 'fid' => $this->fid ] )
				->loadResult();
			if ( $copy ) {
				/**
				 * Fri, Apr 6, 2012
				 * Ok, this is tricky now.
				 * Normally we have such situation:
				 * User is adding an entry and flags are:
				 * approved    | copy  | baseData
				 *    0        |  1    |    Org
				 * When it's just being approved everything works just fine
				 * Problem is when the admin is changing the data then after edit it looks like this
				 * approved    | copy  | baseData
				 *    0        |  1    |    Org         << org user data
				 *    1        |  0    |    Changed     << data changed by the administrator
				 * So in the normal way we'll delete the changed data and approve the old data
				 * Therefore we have to check if the approved data is maybe newer than the non-approved copy
				 */
				$date = $db
					->select( 'copy', 'spdb_field_data', [ 'sid' => $sid, 'fid' => $this->fid ], 'updatedTime.desc', 1 )
					->loadResult();
				/**
				 * If the copy flag of the newer version is 0 - then delete all non-approved versions
				 * and this is our current version
				 */
				if ( $date == 0 ) {
					$db->delete( 'spdb_field_data', [ 'sid' => $sid, 'copy' => '1', 'fid' => $this->fid ] );
				}
				else {
					$params = [ 'sid' => $sid, 'copy' => '1', 'fid' => $this->fid ];
					/**
					 * When we have good multilingual management, we can change it.
					 * For the moment, if an entry is entered in i.e. de_DE
					 * but the admin approves the entry in en_GB and the multilingual mode is enabled
					 * in case it was a new entry - empty data is being displayed.
					 */
					/** Mon, Sep 23, 2013 10:39:37 - I think is should always change the data in the current lang
					 * Since 1.1 we have good multilingual management, so it is probably this issue */
//					if ( !( Sobi::Cfg( 'entry.approve_all_langs', true ) ) ) {
//						$params[ 'lang' ] = array( $lang, C::NO_VALUE );
//					}
					$el = $db
						->select( 'editLimit', 'spdb_field_data', $params )
						->loadResult();
					$cParams = $params;
					/** we need to delete only the entries that have the copy flag set to 1 with the selected language */
					$languages = $db
						->select( 'lang', 'spdb_field_data', [ 'sid' => $sid, 'copy' => '1', 'fid' => $this->fid ] )
						->loadResultArray();
					$cParams[ 'copy' ] = 0;
					if ( $languages[ 0 ] != C::ES ) {
						$cParams[ 'lang' ] = $languages;
					}
					$db->delete( 'spdb_field_data', $cParams );
					$db->update( 'spdb_field_data', [ 'copy' => '0', 'editLimit' => $el ], $params );
				}
			}
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'CANNOT_GET_FIELDS_DATA_DB_ERR', $x->getMessage() ), C::ERROR, 500, __LINE__, __FILE__ );
		}
	}

	/**
	 * @param $sid
	 * @param $state
	 *
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	public function changeState( $sid, $state )
	{
		$db = Factory::Db();
		try {
			$db->update( 'spdb_field_data', [ 'enabled' => $state ], [ 'sid' => $sid, 'fid' => $this->fid ] );
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'CANNOT_CHANGE_FIELD_STATE', $x->getMessage() ), C::ERROR, 500, __LINE__, __FILE__ );
		}
	}

	/**
	 * @param $sid (entry id)
	 *
	 * @throws \Sobi\Error\Exception|\SPException
	 */
	public function rejectChanges( $sid )
	{
		static $deleted = [];
		if ( !isset( $deleted[ $sid ] ) ) {
			$db = Factory::Db();
			try {
				$db->delete( 'spdb_field_data', [ 'sid' => $sid, 'copy' => 1 ] );
			}
			catch ( Sobi\Error\Exception $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'CANNOT_DELETE_FIELD_DATA', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
			}
			$deleted[ $sid ] = true;
		}
	}

	/**
	 * Deletes data of all fields of the entry $sid.
	 *
	 * @param $sid -> entry id
	 *
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	public function deleteData( $sid )
	{
		static $deleted = [];
		if ( !isset( $deleted[ $sid ] ) ) {
			try {
				Factory::Db()->delete( 'spdb_field_data', [ 'sid' => $sid ] );
			}
			catch ( Sobi\Error\Exception $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'CANNOT_DELETE_FIELD_DATA', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
			}
			$deleted[ $sid ] = true;
		}
	}

	/**
	 * Deletes data of a specific field of the entry $sid.
	 *
	 * @param $sid -> entry id
	 *
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	public function deleteFieldData( $sid, $fid )
	{
		try {
			Factory::Db()->delete( 'spdb_field_data', [ 'sid' => $sid, 'fid' => $fid ] );
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'CANNOT_DELETE_FIELD_DATA', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}
	}

	/**
	 * Gets the data for this field from $_FILES and verifies them the first time.
	 * Backend ONLY!!
	 *
	 * @param \SPEntry $entry
	 * @param string $request
	 * @param false $clone
	 */
	public function validate( $entry, $request, $clone = false )
	{
	}

	/**
	 * @param $data
	 * @param $section
	 * @param bool $startWith
	 * @param false $ids
	 *
	 * @return false
	 */
	public function searchSuggest( $data, $section, $startWith = true, $ids = false )
	{
		return false;
	}

	/**
	 * Incoming search request for general search field.
	 *
	 * @param $data -> string to search for
	 * @param $section -> section
	 * @param bool $regex
	 *
	 * @return array
	 */
	public function searchString( $data, $section, $regex = false )
	{
		return [];
	}

	/**
	 * Incoming search request for extended search field.
	 *
	 * @param array|string $data -> string/data to search for
	 * @param $section -> section
	 * @param string $phrase -> search phrase if needed
	 *
	 * @return array
	 */
	public function searchData( $data, $section, $phrase = C::ES )
	{
		return [];
	}

	/* ----------- Protected and private methods ----------- */

	/**
	 * @return array
	 */
	protected function getAttr(): array
	{
		return [];
	}

	/**
	 * @return array -> all properties which are not in the XML file but its default value needs to be set
	 */
	protected function getDefaults()
	{
		return [];
	}

	/**
	 * @param $rawdata
	 * @param string|array $data
	 */
	protected function setData( $rawdata, $data = C::ES )
	{
		$this->_rdata = $rawdata;
		$this->_data = $data ? : $rawdata;
		$this->_field->setRawData( $rawdata );
	}

	/**
	 * @param $values
	 * @param bool $freeInput
	 *
	 * @return string
	 */
	protected function rangeSearch( $values, bool $freeInput = false ): string
	{
		$fw = defined( 'SOBIPRO_ADM' ) ? C::BOOTSTRAP5 : Sobi::Cfg( 'template.framework-style', C::BOOTSTRAP5 );

		$selected[ 'from' ] = $this->_selected[ 'from' ] ?? C::ES;
		$selected[ 'to' ] = $this->_selected[ 'to' ] ?? C::ES;
		$class = $this->cssClass . ( !$this->suffix ? ' sp-nosuffix' : C::ES );

		if ( !$freeInput ) {
			$values = str_replace( [ "\n", "\r", "\t" ], C::ES, $values );
			$values = explode( ',', $values );
			$dataFrom = $dataTo = [];
			if ( count( $values ) ) {
				foreach ( $values as $v ) {
					$dataFrom[ '' ] = Sobi::Txt( 'SH.RANGE_FROM' );
					$dataTo[ '' ] = Sobi::Txt( 'SH.RANGE_TO' );
					$v = trim( $v );
					$dataFrom[ preg_replace( '/[^\d\.\-]/', C::ES, $v ) ] = $v;
					$dataTo[ preg_replace( '/[^\d\.\-]/', C::ES, $v ) ] = $v;
				}
			}
			$class .= $fw == C::BOOTSTRAP2 ? ' w-70' : C::ES;
			$class .= ' ' . Sobi::Cfg( 'search.select_def_css', 'sp-search-select' );

			$from = SPHtml_Input::select( $this->nid . '[from]',
				$dataFrom,
				$selected[ 'from' ],
				false,
				[ 'class' => $class, 'size' => '1', 'numeric' => $this->numeric ]
			);
			$to = SPHtml_Input::select( $this->nid . '[to]',
				$dataTo,
				$selected[ 'to' ],
				false,
				[ 'class' => $class, 'size' => '1', 'numeric' => $this->numeric ]
			);
		}
		else {
			$class .= $fw == C::BOOTSTRAP2 ? ' w-50' : C::ES;
			$class .= ' ' . Sobi::Cfg( 'search.inbox_def_css', 'sp-search-inbox' );

			$from = SPHtml_Input::text( $this->nid . '[from]',
				$selected[ 'from' ],
				[ 'class'       => $class,
				  'placeholder' => Sobi::Txt( 'SH.RANGE_FROM' ),
				  'aria-label'  => Sobi::Txt( 'ACCESSIBILITY.RANGE_FROM' ) ]
			);
			$to = SPHtml_Input::text( $this->nid . '[to]',
				$selected[ 'to' ],
				[ 'class'       => $class,
				  'placeholder' => Sobi::Txt( 'SH.RANGE_TO' ),
				  'aria-label'  => Sobi::Txt( 'ACCESSIBILITY.RANGE_TO' ) ]
			);
		}

		$divs = '<div class="input-group">';
		switch ( $fw ) {
			default:
			case C::BOOTSTRAP5:
				if ( $this->suffix ) {
					$from .= "<span class=\"input-group-text\">$this->suffix</span>";
					$to .= "<span class=\"input-group-text\">$this->suffix</span>";
				}
				break;
			case  C::BOOTSTRAP4:
				if ( $this->suffix ) {
					$from .= "<div class='input-group-append'><span class=\"input-group-text\">$this->suffix</span></div>";
					$to .= "<div class='input-group-append'><span class=\"input-group-text\">$this->suffix</span></div>";
				}
				break;
			case  C::BOOTSTRAP3:
				if ( $this->suffix ) {
					$from = '<div class="input-group sp-range-from">' . $from . "<span class=\"input-group-addon\">$this->suffix</span></div>";
					$to = '<div class="input-group sp-range-to">' . $to . "<span class=\"input-group-addon\">$this->suffix</span></div>";
				}
				$divs = '<div class="sp-range">';
				break;
			case  C::BOOTSTRAP2:
				if ( $this->suffix ) {
					$from = '<div class="input-append sp-range-from w-50">' . $from . "<span class=\"add-on\">$this->suffix</span></div>";
					$to = '<div class="input-append sp-range-to w-50">' . $to . "<span class=\"add-on\">$this->suffix</span></div>";
				}
				$divs = '<div class="sp-range w-100">';
				break;
		}

		return $divs . $from . $to . '</div>';
	}

	/**
	 * @param $request
	 * @param $section
	 *
	 * @return array
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function searchForRange( &$request, $section )
	{
		$sids = [];

		if ( $request[ 'from' ] || $request[ 'to' ] ) {
			/* from has to be lower than to */
			if ( $request[ 'from' ] > $request[ 'to' ] ) {
				$h = $request[ 'from' ];
				$request[ 'from' ] = $request[ 'to' ];
				$request[ 'to' ] = $h;
			}
			$request[ 'from' ] = $request[ 'from' ] ?? SPC::NO_VALUE;
			$request[ 'to' ] = $request[ 'to' ] ?? SPC::NO_VALUE;
			$request[ 'from' ] = strstr( $request[ 'from' ], '.' ) ? ( floatval( $request[ 'from' ] ) ) : (int) $request[ 'from' ];
			$request[ 'to' ] = strstr( $request[ 'to' ], '.' ) ? ( floatval( $request[ 'to' ] ) ) : (int) $request[ 'to' ];
			try {
				$sids = Factory::Db()
					->dselect( 'sid', 'spdb_field_data',
						[ 'fid'      => $this->fid,
						  'copy'     => '0',
						  'baseData' => $request,
						  'section'  => $section ]
					)
					->loadResultArray();
			}
			catch ( Sobi\Error\Exception $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'CANNOT_SEARCH_DB_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
			}
		}

		return $sids;
	}

	/**
	 * Saves the options and values of a field (checkbox, select, multiselect, radio).
	 *
	 * @param $attr
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function saveOptions( &$attr )
	{
		$options = $data = [];

		/* upload of an options-field to read from */
		$file = Input::File( 'spfieldsopts', 'tmp_name' );
		if ( $file ) {
			$data = parse_ini_file( $file, true );
		}
		elseif ( is_string( $attr[ 'options' ] ) ) {
			$data = parse_ini_string( $attr[ 'options' ], true );
		}
		elseif ( is_array( $attr[ 'options' ] ) ) {
			$options = $attr[ 'options' ];
			unset( $attr[ 'options' ] );
		}

		if ( !count( $options ) && $data ) {
			$options = $this->parseOptions( $data );
		}

		if ( count( $options ) ) {
			/* check if the options are syntactically correct and correct them if necessary */
			$newoptions = $newdata = [];
			$this->correctOptions( $options, $newoptions, $newdata );
			$options = $newoptions;
			$data = $newdata;

			if ( count( $options ) ) {
				$values = [ 'dbOptions' => [],
				            'labels'    => [],
				            'optsIds'   => [],
				];

				$this->processOptions( $options, $values );

				/* Write the options to the database */
				$db = Factory::Db();
				/* try to delete the existing labels */
				try {
					$db->delete( 'spdb_field_option', [ 'fid' => $this->id ] );
					$db->delete( 'spdb_language', [ 'oType' => 'field_option', 'fid' => $this->id, '!sKey' => $values[ 'optsIds' ] ] );
				}
				catch ( Sobi\Error\Exception $x ) {
					Sobi::Error( $this->name(), SPLang::e( 'CANNOT_DELETE_SELECTED_OPTIONS', $x->getMessage() ), C::ERROR, 500, __LINE__, __FILE__ );
				}

				/* insert new values */
				try {
					$db->insertArray( 'spdb_field_option', $values[ 'dbOptions' ] );

					/* save the labels in the set language */
					$db->insertArray( 'spdb_language', $values[ 'labels' ], true );

					static $lang, $defLang = C::ES;
					if ( !$this->lang ) {
						$lang = Sobi::Lang();    /* the used language */
						$defLang = Sobi::DefLang();     /* the default language on frontend */
					}

					/* multilingual mode handling */
					if ( Sobi::Cfg( 'lang.multimode', false ) ) {
						/* if we are saving the labels in default language, save them also for other languages if not already set */
						if ( $lang == $defLang ) {
							$languages = SPLang::availableLanguages();
							if ( $languages ) {
								foreach ( $languages as $language => $short ) {
									if ( $language != $defLang ) {
										foreach ( $values[ 'labels' ] as $index => $value ) {
											$values[ 'labels' ][ $index ][ 'language' ] = $language;
										}
										/* save the labels of the default language for all other languages if they have not already a value set */
										$db->insertArray( 'spdb_language', $values[ 'labels' ], false, true );
									}
								}
							}
						}
					}

					/* non multilingual mode */
					else {
						/* if the set lang is not the default lang, save the data also for the default lang if not already set  */
						if ( $lang != $defLang ) {
							foreach ( $values[ 'labels' ] as $index => $value ) {
								$values[ 'labels' ][ $index ][ 'language' ] = $defLang;
							}
							$db->insertArray( 'spdb_language', $values[ 'labels' ], false, true );
						}
					}
				}
				catch ( Sobi\Error\Exception $x ) {
					Sobi::Error( $this->name(), SPLang::e( 'CANNOT_STORE_FIELD_OPTIONS_DB_ERR', $x->getMessage() ), C::ERROR, 500, __LINE__, __FILE__ );
				}
			}


			$arrUtils = new Arr();
			$this->sets[ 'field.options' ] = $arrUtils->toINIString( $data );
		}
	}

	/**
	 * Parses the options and create the array necessary for further operations.
	 *
	 * @param $data
	 *
	 * @return array
	 * @throws \Sobi\Error\Exception|\SPException
	 */
	protected function parseOptions( $data ): array
	{
		$position = 0;
		$options = [];
		if ( is_array( $data ) && count( $data ) ) {
			foreach ( $data as $key => $value ) {
				if ( is_array( $value ) ) {
					if ( !( $this->fieldType == 'radio' || $this->fieldType == 'chbxgroup' ) ) {
						if ( strstr( $key, ',' ) ) {
							$group = explode( ',', $key );
							$gid = StringUtils::Nid( strtolower( $group[ 0 ] ) );
							$groupname = $group[ 1 ];
						}
						else {
							$gid = StringUtils::Nid( strtolower( $key ), true, true );
							$groupname = $key;
						}
						$options[] = [ 'id'         => $gid,
						               'name'       => $groupname,
						               'parent'     => C::ES,
						               'parentname' => C::ES,
						               'position'   => ++$position,
						               'group'      => true ];
					}
					else {
						$groupname = $gid = C::ES;
						SPFactory::message()->warning( Sobi::Txt( 'FIELD_WARN_NOGROUP', $this->fieldType ), false );
					}
					if ( count( $value ) ) {
						foreach ( $value as $id => $name ) {
							if ( is_numeric( $id ) ) {
								$id = $name . '_' . $id;
							}
							$options[] = [ 'id'         => StringUtils::Nid( strtolower( $id ) ),
							               'name'       => $name,
							               'parent'     => $gid,
							               'parentname' => $groupname,
							               'position'   => ++$position,
							               'group'      => false ];
						}
					}
				}
				else {
					$options[] = [ 'id'         => StringUtils::Nid( strtolower( $key ) ),
					               'name'       => $value,
					               'parent'     => C::ES,
					               'parentname' => C::ES,
					               'position'   => ++$position,
					               'group'      => false ];
				}
			}
		}

		return $options;
	}

	/**
	 * @return bool
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function checkCopy()
	{
		return ( in_array( Input::Task(), [ 'entry.approve', 'entry.edit', 'entry.save', 'entry.submit' ] ) || ( Sobi::Can( 'entry.access.unapproved_any' ) ) || Sobi::Can( 'entry.manage.*' ) );
	}

	/**
	 * @param array $options
	 * @param array $newoptions
	 * @param array $newdata
	 */
	protected function correctOptions( array $options, array &$newoptions, array &$newdata )
	{
		static $grouped = false;
		if ( count( $options ) ) {
			$position = 0;
			$newdata = [];
			foreach ( $options as $option ) {
				if ( is_numeric( $option[ 'id' ] ) ) {
					$option[ 'id' ] = $this->nid . '_' . $option[ 'id' ];
				}
				$option[ 'name' ] = $option[ 'name' ] ?? ( $option[ 'label' ] ? : C::ES );
				$option[ 'parentname' ] = isset( $option[ 'parentname' ] ) && $option[ 'parentname' ] ? $option[ 'parentname' ] : C::ES;

				if ( isset( $option[ 'options' ] ) && is_array( $option ) && count( $option ) ) {
					$grouped = true;
					$newoptions[] = [ 'id'         => $option[ 'id' ],
					                  'name'       => $option[ 'name' ],
					                  'parent'     => $option[ 'parent' ],
					                  'parentname' => $option[ 'parentname' ],
					                  'position'   => ++$position,
					                  'group'      => true,
					];

					$newdata[ $option[ 'id' ] ] = $option[ 'name' ];

					$this->correctOptions( $option[ 'options' ], $newoptions, $newdata );
				}

				/* if the group array is already resolved */
				else {
					$newoptions[] = [ 'id'         => $option[ 'id' ],
					                  'name'       => $option[ 'name' ],
					                  'parent'     => $option[ 'parent' ],
					                  'parentname' => $option[ 'parentname' ],
					                  'position'   => ++$position,
					                  'group'      => isset( $option[ 'group' ] ) && $option[ 'group' ] ? $option[ 'group' ] : false,
					];
					$grouped = isset( $option[ 'group' ] ) && $option[ 'group' ] ? $option[ 'group' ] : $grouped;

					if ( $option[ 'parent' ] ) {
						if ( !isset( $newdata[ $option[ 'parentname' ] ] ) ) {
							$newdata[ $option[ 'parentname' ] ] = [];
						}
						$newdata[ $option[ 'parentname' ] ][ $option[ 'id' ] ] = $option[ 'name' ];
					}
					else {
						if ( isset( $option[ 'group' ] ) && $option[ 'group' ] ) {
							$newdata[ $option[ 'name' ] ] = [];
						}
						else {
							$newdata[ $option[ 'id' ] ] = $option[ 'name' ];
						}
					}
				}
			}
		}
	}

	/**
	 * Processes the options and prepare data for saving.
	 * Correct duplicate option ids.
	 *
	 * @param array $options
	 * @param array $values
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function processOptions( array $options, array &$values )
	{
		static $duplicates = false;
		static $lang = C::ES;
		if ( !$lang ) {
			$lang = Sobi::Lang();    /* the used language */
		}

		foreach ( $options as $index => $option ) {
			/* check for doubles */
			foreach ( $options as $pos => $opt ) {
				if ( $index == $pos ) {
					continue;
				}
				if ( $option[ 'id' ] == $opt[ 'id' ] ) {
					$option[ 'id' ] = $option[ 'id' ] . '_' . substr( microtime(), 2, 8 ) . rand( 1, 100 );
					$options[ $index ][ 'id' ] = $option[ 'id' ];   /* change it also in the options array */
					$duplicates = true;
				}
				if ( !$option[ 'position' ] ) {
					$option[ 'position' ] = $index + 1;
				}

				if ( isset( $option[ 'label' ] ) ) {
					$option[ 'name' ] = $option[ 'label' ];
					unset( $option[ 'label' ] );
				}
			}

			$values[ 'dbOptions' ][] = [ 'fid'       => $this->id,
			                             'optValue'  => $option[ 'id' ],
			                             'optPos'    => $option[ 'position' ],
			                             'optParent' => $option[ 'parent' ] ];

			$values[ 'labels' ][] = [ 'sKey'     => $option[ 'id' ],
			                          'sValue'   => $option[ 'name' ],
			                          'language' => $lang,
			                          'oType'    => 'field_option',
			                          'fid'      => $this->id ];

			$values[ 'optsIds' ][] = $option[ 'id' ];

			/* probably no longer needed! */
			if ( array_key_exists( 'options', $option ) && is_array( $option[ 'options' ] ) && count( $option[ 'options' ] ) ) {
				$this->processOptions( $option[ 'options' ], $values );
			}
		}

		if ( $duplicates ) {
			SPFactory::message()->warning( 'FIELD_WARN_DUPLICATE_OPT_ID' );
		}
	}
}
