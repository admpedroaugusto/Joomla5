<?php
/**
 * @package: SobiPro Library
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
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 09-Mar-2009 by Radek Suski
 * @modified 14 March 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadModel( 'field' );

use Sobi\C;
use Sobi\Input\Input;
use Sobi\Lib\Factory;
use Sobi\Utils\StringUtils;
use Sobi\Utils\Type;
use Sobi\Error\Exception;

/**
 * Class SPAdmField
 */
final class SPAdmField extends SPField
{
	/** @var array */
	private $_translatable = [ 'name', 'description', 'suffix' ];

	/**
	 * @param $base
	 *
	 * @TODO: this method is idiotic. It has to be automated Tue, Oct 13, 2020 14:36:09
	 * @throws SPException
	 */
	protected function checkAttr( &$base )
	{
		$base[ 'name' ] = isset( $base[ 'name' ] ) ? Factory::Db()->escape( $base[ 'name' ] ) : 'missing name - something went wrong';

		/* Joomla destroys the new lines */
		$base[ 'description' ] = isset( $base[ 'description' ] ) ? Factory::Db()->escape( $base[ 'description' ] ) : C::ES;
		/* Correct the newlines again! */
		$base[ 'description' ] = stripcslashes( $base[ 'description' ] );

		$base[ 'suffix' ] = isset( $base[ 'suffix' ] ) ? Factory::Db()->escape( $base[ 'suffix' ] ) : C::ES;
		$base[ 'cssClass' ] = isset( $base[ 'cssClass' ] ) ? Factory::Db()->escape( preg_replace( '/[^[:alnum:]\-\_ ]/', C::ES, $base[ 'cssClass' ] ) ) : C::ES;
		$base[ 'showIn' ] = isset( $base[ 'showIn' ] ) ? Factory::Db()->escape( preg_replace( '/[^[:alnum:]\.\-\_]/', C::ES, $base[ 'showIn' ] ) ) : 'hidden';

		if ( isset( $base[ 'notice' ] ) ) {
			$base[ 'notice' ] = Factory::Db()->escape( $base[ 'notice' ] );
			/* Correct the newlines */
			$base[ 'notice' ] = stripcslashes( $base[ 'notice' ] );
		}

		if ( isset( $base[ 'filter' ] ) ) {
			$base[ 'filter' ] = preg_replace( '/[^[:alnum:]\.\-\_]/', C::ES, $base[ 'filter' ] );
		}

		if ( isset( $base[ 'fieldType' ] ) ) {
			$base[ 'fieldType' ] = preg_replace( '/[^[:alnum:]\.\-\_]/', C::ES, $base[ 'fieldType' ] );
		}
		if ( isset( $base[ 'type' ] ) ) {
			$base[ 'fieldType' ] = preg_replace( '/[^[:alnum:]\.\-\_]/', C::ES, $base[ 'type' ] );
		}
		if ( isset( $base[ 'enabled' ] ) ) {
			$base[ 'enabled' ] = ( int ) $base[ 'enabled' ];
		}
		if ( isset( $base[ 'required' ] ) ) {
			$base[ 'required' ] = ( int ) $base[ 'required' ];
		}
//		if ( isset( $base[ 'showLabel' ] ) ) {
//			$base[ 'showLabel' ] = ( int ) $base[ 'showLabel' ];
//		}
		if ( isset( $base[ 'withLabel' ] ) ) {
			$base[ 'withLabel' ] = ( int ) $base[ 'withLabel' ];
		}
		if ( isset( $base[ 'showEditLabel' ] ) ) {
			$base[ 'showEditLabel' ] = ( int ) $base[ 'showEditLabel' ];
		}

		if ( isset( $base[ 'adminField' ] ) ) {
			$base[ 'adminField' ] = ( int ) $base[ 'adminField' ];
		}
		if ( isset( $base[ 'adminField' ] ) && $base[ 'adminField' ] ) {
			$base[ 'required' ] = false;
		}

		if ( isset( $base[ 'editable' ] ) ) {
			$base[ 'editable' ] = ( int ) $base[ 'editable' ];
		}
		if ( isset( $base[ 'inSearch' ] ) ) {
			$base[ 'inSearch' ] = ( int ) $base[ 'inSearch' ];
		}

		$base[ 'editLimit' ] = isset( $base[ 'editLimit' ] ) && $base[ 'editLimit' ] > 0 ? $base[ 'editLimit' ] : -1;
		if ( isset( $base[ 'editLimit' ] ) ) {
			$base[ 'editLimit' ] = ( int ) $base[ 'editLimit' ];
		}

		if ( isset( $base[ 'isFree' ] ) ) {
			$base[ 'isFree' ] = ( int ) $base[ 'isFree' ];
		}
		if ( isset( $base[ 'admList' ] ) ) {
			$base[ 'admList' ] = ( int ) $base[ 'admList' ];
		}
		if ( isset( $base[ 'fee' ] ) ) {
			$base[ 'fee' ] = ( double ) str_replace( ',', '.', $base[ 'fee' ] );
		}
		if ( isset( $base[ 'uniqueData' ] ) ) {
			$base[ 'uniqueData' ] = ( int ) $base[ 'uniqueData' ];
		}

		if ( isset( $base[ 'addToMetaDesc' ] ) ) {
			$base[ 'addToMetaDesc' ] = ( int ) $base[ 'addToMetaDesc' ];
		}
		if ( isset( $base[ 'addToMetaKeys' ] ) ) {
			$base[ 'addToMetaKeys' ] = ( int ) $base[ 'addToMetaKeys' ];
		}
		$base[ 'adminField' ] = $base[ 'adminField' ] ?? 0;
		$base[ 'adminList' ] = $base[ 'adminList' ] ?? 0;
		$base[ 'admList' ] = $base[ 'admList' ] ?? 0;
		$base[ 'uniqueData' ] = $base[ 'uniqueData' ] ?? 0;
		$base[ 'addToMetaDesc' ] = $base[ 'addToMetaDesc' ] ?? 0;
		$base[ 'withLabel' ] = $base[ 'withLabel' ] ?? 0;

		/* both strpos are removed because it does not allow to have one parameter only */
//      if( isset( $base[ 'allowedAttributes' ] ) && strpos( $base[ 'allowedAttributes' ], '|' ) )
		if ( isset( $base[ 'allowedAttributes' ] ) ) {
			$att = SPFactory::config()->structuralData( $base[ 'allowedAttributes' ], true );
			if ( is_array( $att ) && count( $att ) ) {
				foreach ( $att as $i => $k ) {
					$att[ $i ] = trim( $k );
				}
			}
			$base[ 'allowedAttributes' ] = SPConfig::serialize( $att );
		}
		if ( isset( $base[ 'allowedTags' ] ) ) {
			$tags = SPFactory::config()->structuralData( $base[ 'allowedTags' ], true );
			if ( is_array( $tags ) && count( $tags ) ) {
				foreach ( $tags as $i => $k ) {
					$tags[ $i ] = trim( $k );
				}
			}
			$base[ 'allowedTags' ] = SPConfig::serialize( $tags );
		}

		/* bind attributes to this object */
//		foreach ( $base as $a => $v ) {
//			$a = trim( $a );
//			if ( $this->has( $a ) ) {
//				$this->$a = $v;
//			}
//		}
	}

	/**
	 * @param $attr
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function save( $attr )
	{
		$base = $attr;
		$this->loadType();

		$base[ 'section' ] = ( isset( $base[ 'section' ] ) && $base[ 'section' ] ) ? $base[ 'section' ] : Input::Sid();
		$this->version++;
		$base[ 'version' ] = $this->version;

		if ( isset( $base[ 'nid' ] ) ) {
			$base[ 'nid' ] = $this->nid( Factory::Db()->escape( preg_replace( '/[^[:alnum:]\-\_]/', C::ES, $base[ 'nid' ] ) ), false, $base[ 'section' ] );
		}

		$this->checkAttr( $base );

		if ( $this->_type && method_exists( $this->_type, 'save' ) ) {
			$this->_type->save( $base );
		}

		/* get database columns and their ordering and sort the properties in the same order */
		$values = [];
		$cols = Factory::Db()->getColumns( 'spdb_field' );
		foreach ( $cols as $col ) {
			if ( array_key_exists( $col, $base ) ) {
				$values[ $col ] = $base[ $col ];
			}
		}

		/* save field */
		try {
			Factory::Db()->update( 'spdb_field', $values, [ 'fid' => $this->fid ] );
		}
		catch ( Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::ERROR, 500, __LINE__, __FILE__ );
		}

		/* save language dependant properties */
		$this->saveLanguageData( $base );
		if ( $this->_type && method_exists( $this->_type, 'saveLanguageData' ) ) {
			$this->_type->saveLanguageData( $base );
		}

		SPFactory::cache()->cleanSection();
	}

	/**
	 * Adds new field - Saves base data.
	 *
	 * @param array $attr
	 *
	 * @return int
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function saveNew( $attr )
	{
		$base = $attr;
		unset ( $base[ 'fid' ] );
		$this->loadType( $base[ 'fieldType' ] );

		$base[ 'section' ] = isset( $base[ 'section' ] ) && $base[ 'section' ] ? $base[ 'section' ] : Input::Sid();
		$this->version = 1;
		$base[ 'version' ] = $this->version;

		if ( isset( $base[ 'nid' ] ) ) {
			$base[ 'nid' ] = $this->nid( Factory::Db()->escape( preg_replace( '/[^[:alnum:]\-\_]/', C::ES, $base[ 'nid' ] ) ), true, $base[ 'section' ] );
		}

		$this->checkAttr( $base );

		/* determine the right field position */
		try {
			$condition = [ 'section' => $base[ 'section' ] ];
			if ( !Input::Int( 'category-field' ) ) {
				$condition[ 'adminField>' ] = -1;
			}
			else {
				$condition[ 'adminField' ] = -1;
			}
			$base[ 'position' ] = ( int ) Factory::Db()->select( 'MAX( position )', 'spdb_field', $condition )->loadResult() + 1;
			if ( !$base[ 'position' ] ) {
				$base[ 'position' ] = 1;
			}
		}
		catch ( Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'CANNOT_GET_FIELD_POSITION_DB_ERR', $x->getMessage() ), C::ERROR, 500, __LINE__, __FILE__ );
		}

		/* save option values if available and get the params */
		if ( $this->_type && method_exists( $this->_type, 'saveNew' ) ) {
			$this->_type->saveNew( $base );
		}

		/* get database columns and their ordering and sort the properties in the same order */
		$values = [];
		$cols = Factory::Db()->getColumns( 'spdb_field', true );
		foreach ( $cols as $col => $props ) {
			$values[ $col ] = array_key_exists( $col, $base ) ? $base[ $col ] : Type::SQLNull( $props[ 'Type' ] );
		}

		/* save new field */
		try {
			Factory::Db()->insert( 'spdb_field', $values );
			$this->fid = Factory::Db()->insertId();
			$base[ 'fid' ] = $this->fid;
		}
		catch ( Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::ERROR, 500, __LINE__, __FILE__ );
		}

		/* save language dependant properties */
		$this->saveLanguageData( $base );
		if ( $this->_type && method_exists( $this->_type, 'saveLanguageData' ) ) {
			$this->_type->saveLanguageData( $base );
		}

		SPFactory::cache()->cleanSection();

		return $this->fid;
	}

	/**
	 * @param $nid
	 * @param $new
	 * @param $section
	 *
	 * @return string
	 */
	protected function nid( $nid, $new, $section )
	{
		$counter = 1;
		$add = 2;
		$suffix = C::ES;
		while ( $counter ) {
			/* field alias has to be unique */
			try {
				$condition = [ 'nid' => $nid . $suffix, 'section' => $section ];
				if ( !$new ) {
					$condition[ '!fid' ] = $this->id;
				}
				$counter = Factory::Db()
					->select( 'COUNT( nid )', 'spdb_field', $condition )
					->loadResult();
				if ( $counter > 0 ) {
					$suffix = '_' . $add++;
				}
			}
			catch ( Exception $x ) {
			}
		}

		return $nid . $suffix;
	}

	/**
	 * @param null $type
	 *
	 * @throws SPException
	 */
	public function loadType( $type = null )
	{
		if ( $type ) {
			$this->type = $type;
		}
		else {
			$this->type =& $this->fieldType;
		}
		if ( $this->type && SPLoader::translatePath( 'opt.fields.adm.' . $this->type ) ) {
			SPLoader::loadClass( 'opt.fields.fieldtype' );
			$fType = SPLoader::loadClass( 'opt.fields.adm.' . $this->type );
			$this->_type = new $fType( $this );
		}
		else {
			if ( $this->type && SPLoader::translatePath( 'opt.fields.' . $this->type ) ) {
				SPLoader::loadClass( 'opt.fields.fieldtype' );
				$fType = SPLoader::loadClass( 'opt.fields.' . $this->type );
				$this->_type = new $fType( $this );
			}
			else {
				parent::loadType();
			}
		}
	}

	/**
	 * @param $view
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function onFieldEdit( &$view )
	{
		$this->loadType();
		$this->editLimit = max( $this->editLimit, 0 );  //$this->editLimit > 0 ? $this->editLimit : 0;
		$this->fee = StringUtils::Currency( $this->fee, false );
		if ( is_array( $this->allowedAttributes ) ) {
			$this->allowedAttributes = implode( ', ', $this->allowedAttributes );
		}
		if ( is_array( $this->allowedTags ) ) {
			$this->allowedTags = implode( ', ', $this->allowedTags );
		}
		if ( $this->_type && method_exists( $this->_type, 'onFieldEdit' ) ) {
			$this->_type->onFieldEdit( $view );
		}
	}

	/**
	 * @param array $base
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function saveLanguageData( $base ): void
	{
		static $lang, $defLang = C::ES;
		if ( !$lang ) {
			$lang = Sobi::Lang();    /* the used language */
			$defLang = Sobi::DefLang();     /* the default language on frontend */
		}

		$labels = [];
		$labels[] = [ 'sKey'     => 'name', 'sValue' => $base[ 'name' ],
		              'language' => $lang,
		              'id'       => 0,
		              'oType'    => 'field',
		              'fid'      => $this->fid ];
		$labels[] = [ 'sKey'     => 'description', 'sValue' => $base[ 'description' ],
		              'language' => $lang,
		              'id'       => 0,
		              'oType'    => 'field',
		              'fid'      => $this->fid ];

		if ( isset ( $base[ 'suffix' ] ) ) {
			$labels[] = [ 'sKey'     => 'suffix', 'sValue' => $base[ 'suffix' ],
			              'language' => $lang,
			              'id'       => 0,
			              'oType'    => 'field',
			              'fid'      => $this->fid ];
		}
		if ( is_array( $labels ) && count( $labels ) ) {
			try {
				/* save it in the set language */
				Factory::Db()->insertArray( 'spdb_language', $labels, true );

				/* multilingual mode handling */
				if ( Sobi::Cfg( 'lang.multimode', false ) ) {
					/* if we are saving the data in the default language, save them also for other languages if not already set */
					if ( $lang == $defLang ) {
						$languages = SPLang::availableLanguages();
						if ( $languages ) {
							foreach ( $languages as $language => $short ) {
								if ( $language != $defLang ) {
									foreach ( $labels as $index => $value ) {
										$labels[ $index ][ 'language' ] = $language;
									}
									/* save the value of the default language for all other languages if they have not already a value set */
									Factory::Db()->insertArray( 'spdb_language', $labels, false, true );
								}
							}
						}
					}
				}

				/* non multilingual mode */
				else {
					/* if the set lang is not the default lang, save the data also for the default lang if not already set  */
					if ( $lang != $defLang ) {
						foreach ( $labels as $index => $value ) {
							$labels[ $index ][ 'language' ] = $defLang;
						}
						Factory::Db()->insertArray( 'spdb_language', $labels, false, true );
					}
				}
			}
			catch ( Exception $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'CANNOT_SAVE_FIELD_DB_ERR', $x->getMessage() ), C::ERROR, 500, __LINE__, __FILE__ );
			}
		}
	}
}
