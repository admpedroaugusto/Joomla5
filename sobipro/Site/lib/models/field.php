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
 * @created 09-Mar-2009 by Radek Suski
 * @modified 11 August 2023 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadModel( 'field' );

use Sobi\C;
use Sobi\Lib\Factory;
use Sobi\Utils\Encryption;
use Sobi\Utils\Serialiser;
use Sobi\Utils\StringUtils;
use Sobi\Input\Input;

/**
 * Class SPField
 */
class SPField extends SPObject
{
	/* properties available for all fields (columns in spdb_field) */
	/** @var int */
	protected $fid = 0;
	/** @var string */
	protected $nid = C::ES;
	/** @var int */
	protected $adminField = 0;
	/** @var bool */
	protected $admList = false;
	/** @var string */
	protected $dataType = C::ES;
	/** @var bool */
	protected $enabled = true;
	/** @var double */
	protected $fee = 0;
	/** @var string */
	protected $fieldType = C::ES;
	/** @var null */
	protected $filter = C::ES;
	/** @var bool */
	protected $isFree = true;
	/** @var int */
	protected $position = 0;
	/** @var int */
	protected $priority = 5;
	/** @var bool */
	protected $required = false;
	/** @var int */
	protected $section = 0;
	/** @var bool */
	protected $multiLang = 0;
	/** @var bool */
	protected $uniqueData = 0;
	/** @var bool */
	protected $addToMetaDesc = 0;
	/** @var bool */
	protected $addToMetaKeys = 0;
	/** @var int */
	protected $editLimit = -1;
	/** @var bool */
	protected $editable = true;
	/** @var string */
	protected $showIn = 'details';
	/** @var array */
	protected $allowedAttributes = [];
	/** * @var bool */
	protected $editor = 0;
	/** @var string */
	protected $inSearch = 1;
	/** @var bool */
	protected $withLabel = 1;
	/** * @var string */
	protected $cssClass = C::ES;
	/** @var bool */
	protected $parse = 0;
	/** @var string */
	protected $template = C::ES;
	/** @var string */
	protected $notice = C::ES;
	/** @var array */
	protected $params = []; /* contains the field type specific properties */
	/** @var string */
	protected $defaultValue = C::ES;
	/** @var int */
	protected $version = 1;

	/** @var string */
	protected $name = C::ES;
	/** @var string */
	protected $description = C::ES;
	/** @var int */
	protected $id = 0;
	/** @var string */
	protected $type = C::ES;

	/* general properties for all fields */
	/** @var stdClass */
	protected $_fData = null;
	/** @var mixed */
	protected $_rawData = null; /* contains the raw data; means if data are encoded this is also encoded */
	/** @var string */
	protected $_data = null;    /* contains the plain data; means if data are encoded, this is decoded or these are the values for options */
	/** @var SPFieldInterface */
	protected $_type = null;
	/** @var bool */
	protected $_off = false;

	/** @var string */
	private $_loaded = false;
	/** @var bool */
	private $_class = null;
	/** @var bool */
//	private $_rawDataChanged = false;
	/** @var array */
	private $_translatable = [ 'name', 'description', 'suffix' ];

	/** @var string */
	protected $lang = C::ES;
	/** @var int */
	protected $sid = 0;
	/** @var string */
	protected $suffix = C::ES;

	/* properties not used in all fields */
	/** @var array */
	protected $allowedTags = [];
	/** @var bool */
	protected $validate = 0;


	/** @var string */
	protected $note = C::ES;
	/** @var string */
	protected $label = C::ES;
	/** @var string */
	protected $currentView = 'undefined';
	/** @var bool */
	protected $revisionChange = null;

	/**
	 * SPField constructor.
	 */
	public function __construct()
	{
		$this->id =& $this->fid;
		$this->fieldType =& $this->type;
	}

	/**
	 * @param $obj
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function extend( $obj )
	{
		Sobi::Trigger( 'Field', ucfirst( __FUNCTION__ ), [ &$obj ] );
		if ( !empty( $obj ) ) {
			foreach ( $obj as $k => $v ) {
				$this->_set( $k, $v );
			}
		}
		$this->getClass();
		$this->loadTables();
	}

	/**
	 * @param $id
	 *
	 * @return $this
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function & init( $id )
	{
		$this->fid = $id;
		try {
			$field = Factory::Db()
				->select( '*', 'spdb_field', [ 'fid' => $id ] )
				->loadObject();
			Sobi::Trigger( 'Field', ucfirst( __FUNCTION__ ), [ &$field ] );
		}
		catch ( SPException $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}
		if ( count( (array) $field ) ) {
			$this->extend( $field );
			$this->fromCache();
		}

		return $this;
	}

	/**
	 * @param $rawdata
	 */
	public function setRawData( $rawdata )
	{
		$this->_rawData = $rawdata;
		//$this->_data = $data;
		//$this->_rawDataChanged = true;
	}

	/**
	 * @return array|mixed|string|string[]
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function getRaw()
	{
		if ( is_string( $this->_rawData ) ) {
			$this->_rawData = stripslashes( $this->_rawData );
		}
		$r = $this->_rawData;
		$this->checkMethod( 'getRawData' );
		if ( $this->_type && method_exists( $this->_type, 'getRawData' ) ) {
			$data = $this->_type->getRawData( $this->_rawData );
			$r =& $data;
		}
		else {
			if ( is_string( $r ) && strstr( $r, 'encrypted://' ) ) {
				$r = Serialiser::StructuralData( $r );
			}
		}
		Sobi::Trigger( 'Field', ucfirst( __FUNCTION__ ), [ &$r ] );

		return $r;
	}

	/**
	 * Std. getter. Returns a property of the object or the default value if the property is not set.
	 *
	 * @param string $attr
	 * @param null $default
	 *
	 * @return array|mixed|string|string[]|null
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function get( $attr, $default = null )
	{
		if ( $attr == 'value' ) {
			return $this->getRaw();
		}
		if ( isset( $this->$attr ) ) {
			return is_string( $this->$attr ) ? StringUtils::Clean( $this->$attr ) : $this->$attr;
		}
		if ( !$this->_type && !$this->_off ) {
			$this->fullInit();
		}
		if ( $this->_type && $this->_type->has( $attr ) && $this->_type->get( $attr ) ) {
			return $this->_type->get( $attr );
		}
		else {
			return $default;
		}
	}

	/**
	 * @param $var
	 * @param $val
	 *
	 * @return $this|\SPField
	 */
	public function & set( $var, $val )
	{
		if ( isset( $this->$var ) ) {
			$this->$var = $val;
		}
		if ( $this->_type && method_exists( $this->_type, 'set' ) ) {
			$this->_type->set( $var, $val );
		}

		return $this;
	}

	/**
	 * @param string $attr
	 *
	 * @return bool
	 */
	public function has( $attr )
	{
		return parent::has( $attr ) || ( $this->_type && $this->_type->get( $attr ) );
	}

	/**
	 * Checks if the field should be displayed or not.
	 *
	 * @param $view
	 * @param false $new
	 *
	 * @return bool
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function enabled( $view, $new = false )
	{
		// why checking this first at the end after doing a full init although the enabled information is already available?
		// Disabled fields also can't be seen by administrators
		if ( $this->enabled ) {
			if ( $view == 'form' ) {
				// while editing an entry we have to get the real data
				$this->fullInit();
				if ( $this->get( 'isOutputOnly' ) ) {
					return false;
				}
				if ( !Sobi::Can( 'entry.adm_fields.edit' ) ) {
					if ( $this->adminField ) {
						return false;
					}
					/*
					 * When the user is adding the entry very first time this should not affect because
					 * the field is not editable but the user has to be able to add data for the first time
					 */
					if ( !$this->editable && Input::Task() != 'entry.add' && !( $new && in_array( Input::Task(), [ 'entry.submit', 'entry.save' ] ) ) ) {
						//if ( !( $this->editable ) && !( $new && in_array( Input::Task(), array( 'entry.add', 'entry.submit', 'entry.save' ) ) ) ) {
						return false;
					}
					if ( !$this->editLimit ) {
						return false;
					}
				}
			}
			else {
				if ( $this->get( 'isInputOnly' ) ) {
					return false;
				}
			}
			$this->currentView = $view;
//		if ( !( $this->enabled ) /*&& !( Sobi::Can( 'field', 'see_disabled', 'all' ) )*/ ) {
//			return false;
//		}

			/* view = special shows all fields besides 'hidden' */
			if ( $view != 'form' && !( $this->showIn == $view || $this->showIn == 'both' ) && !( $view == 'special' && $this->showIn != 'hidden' ) ) {
				return false;
			}
			/* not every field has the same raw data */
			if ( isset( $this->_fData->publishDown ) ) {
				if ( ( is_array( $this->_fData ) && count( $this->_fData ) ) && ( !( strtotime( $this->_fData->publishUp ) < time() ) || ( ( ( strtotime( $this->_fData->publishDown ) > 0 ) && strtotime( $this->_fData->publishDown ) <= time() ) ) )
				) {
					return false;
				}
			}

			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * Proxy pattern.
	 *
	 * @param $method
	 * @param $args
	 *
	 * @return mixed
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function __call( $method, $args )
	{
		$this->checkMethod( $method );
		if ( $this->_type && method_exists( $this->_type, $method ) ) {
			$Args = [];
			// http://www.php.net/manual/en/function.call-user-func-array.php#91503
			foreach ( $args as $k => &$arg ) {
				$Args[ $k ] =& $arg;
			}
			Sobi::Trigger( 'Field', ucfirst( $method ), [ &$Args ] );

			return call_user_func_array( [ $this->_type, $method ], $Args );
		}
		else {
			if ( $this->_off ) {
				Sobi::Error( 'Field', SPLang::e( 'CALL_TO_UNDEFINED_CLASS_METHOD', $this->fieldType, $method ), C::WARNING );
			}
			else {
				throw new SPException( SPLang::e( 'CALL_TO_UNDEFINED_CLASS_METHOD', get_class( $this ), $method ) );
			}
		}
	}

	/**
	 * @return $this
	 */
	public function & revisionChanged()
	{
		$this->revisionChange = true;

		return $this;
	}

	/**
	 * @param false $html
	 * @param false $raw
	 *
	 * @return array|mixed|string|string[]|null
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function data( $html = false, $raw = false )
	{
		if ( $this->_off ) {
			return null;
		}
		Sobi::Trigger( 'Field', ucfirst( __FUNCTION__ ), [ &$this->_data ] );
		if ( !$raw ) {
			$this->checkMethod( 'cleanData' );
		}
		if ( $this->_type && method_exists( $this->_type, 'cleanData' ) ) {
			$numberOfParams = new ReflectionMethod( $this->_type, 'cleanData' );
			/* compatibility code for older fields */
			if ( $numberOfParams->getNumberOfRequiredParameters() ) {
				$rawvalue = $this->_type->cleanData( $html );
			}
			else {
				$rawvalue = $this->_type->cleanData();
			}
		}
		else {
			$rawvalue =& $this->_data;     // this is empty for new entries
//			$rawvalue =& $this->_rawData;
		}
		/** Wed, Aug 31, 2016 10:26:08  - Profile field overrides this data but also expect this data to be serialised
		 * @todo Need to be fixed in Profile Field and then we can remove it here
		 * */
		/*		if ( ( is_string( $rawvalue ) && !strlen( $rawvalue ) && !$this->_rawDataChanged ) || ( !$rawvalue && !$this->_rawDataChanged ) ) {*/
		if ( ( is_string( $rawvalue ) && !strlen( $rawvalue ) ) || !$rawvalue ) {
			$rawvalue =& $this->_rawData;
		}
		if ( $this->parse ) {
			Sobi::Trigger( 'Parse', 'Content', [ &$rawvalue ] );
		}
		if ( is_string( $rawvalue ) && strstr( $rawvalue, 'encrypted://' ) ) {
			$rawvalue = Serialiser::StructuralData( $rawvalue );
		}

		return is_string( $rawvalue ) ? StringUtils::Clean( $rawvalue ) : $rawvalue;
	}

	/**
	 * @return array|mixed|string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function field( $return = false )
	{
		$html = [];
		if ( !$this->_off ) {
			$this->fromCache();
			$this->checkMethod( 'field' );
			/* convert double line breaks to br -> better use only HTML if you want to format the text */
			$this->description = $this->description ? strtr( $this->description, [ "\r\n\r\n" => '<br />', "\r\r" => '<br />', "\n\n" => '<br />' ] ) : C::ES;
			if ( $this->_type && method_exists( $this->_type, 'field' ) ) {
				$html = $this->_type->field( $return );
				Sobi::Trigger( 'Field', ucfirst( __FUNCTION__ ), [ &$html ] );
			}
		}

		return $html;
	}

	/**
	 * @param $skipNative
	 *
	 * @return array|mixed
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function struct( $skipNative = false )
	{
		$xml = [];
		if ( !$this->_off ) {
			if ( !$skipNative ) {
				$this->checkMethod( 'struct' );
			}
			if ( !$skipNative && $this->_type && method_exists( $this->_type, 'struct' ) ) {
				$xml = $this->_type->struct();
			}
			else {
				$attributes = [];
				if ( is_string( $this->data() ) && strlen( $this->data() ) ) {
					$this->cssClass = strlen( $this->cssClass ) ? $this->cssClass : 'sp-field-data';
					$this->cssClass = $this->cssClass . ' ' . $this->nid;
					$css = explode( ' ', $this->cssClass );
					if ( count( $css ) ) {
						$this->cssClass = implode( ' ', array_unique( $css ) );
					}
					if ( $this->_type && method_exists( $this->_type, 'setCSS' ) ) {
						$this->_type->setCSS( $this->cssClass );
					}

					$attributes = [
						'lang'  => Sobi::Lang( false ),
						'class' => $this->cssClass,
					];
				}
				$xml = [
					'_complex'    => 1,
					'_data'       => $this->data(),
					'_attributes' => $attributes,
				];
			}
			Sobi::Trigger( 'Field', ucfirst( __FUNCTION__ ), [ &$xml ] );
		}

		return $xml;
	}

	/**
	 * @param bool $return
	 *
	 * @return string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function searchForm( $return = false )
	{
		if ( !$this->_off ) {
			$this->checkMethod( 'searchForm' );
			if ( $this->_type && method_exists( $this->_type, 'searchForm' ) ) {
				$html = $this->_type->searchForm( $return );
				Sobi::Trigger( 'Field', ucfirst( __FUNCTION__ ), [ &$html ] );

				return $html;
			}
		}

		return C::ES;
	}

	/**
	 * Proxy pattern for the particular method.
	 * Std proxy does not work well because of references.
	 *
	 * @param mixed $request
	 * @param array $results
	 * @param array $priorities
	 *
	 * @throws \SPException
	 */
	public function searchNarrowResults( $request, &$results, &$priorities )
	{
		if ( $this->_type && method_exists( $this->_type, 'searchNarrowResults' ) ) {
			$this->fullInit();
			$this->_type->searchNarrowResults( $request, $results, $priorities );
		}
	}

	/**
	 * @return mixed
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function delete()
	{
		Sobi::Trigger( 'Field', ucfirst( __FUNCTION__ ), [ $this->id ] );
		if ( !$this->_type ) {
			$this->loadType();
		}
		if ( $this->_type && method_exists( $this->_type, 'delete' ) ) {
			$this->_type->delete();
		}

		$db = Factory::Db();
		try {
			$db->delete( 'spdb_field', [ 'fid' => $this->id ] );
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}
		try {
			$db->delete( 'spdb_field_data', [ 'fid' => $this->id ] );
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}
		try {
			$db->delete( 'spdb_language', [ 'fid' => $this->id, 'oType' => 'field' ] );
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}

		return Sobi::Txt( 'FD.DELETED', [ 'field' => $this->name ] );
	}

	/**
	 * Creates the field type object (Proxy pattern).
	 *
	 * @throws SPException
	 */
	public function loadType()
	{
		if ( $this->type && $this->_class && class_exists( $this->_class ) ) {
			$implements = class_implements( $this->_class );
			if ( is_array( $implements ) && in_array( 'SPFieldInterface', $implements ) ) {
				$this->_type = new $this->_class( $this );
			}
		}
		elseif ( $this->type ) {
			$this->enabled = false;
			$this->_off = true;
			Sobi::Error( 'Field', sprintf( 'Field type %s does not exist!', $this->fieldType ), C::WARNING );
		}
	}

	/**
	 * Returns attributes of this class.
	 *
	 * @return array
	 */
	public function getAttributes()
	{
		$attr = get_class_vars( __CLASS__ );
		$ret = [];
		foreach ( $attr as $k => $v ) {
			if ( !( strstr( $k, '_' ) && strpos( $k, '_' ) == 0 ) ) {
				$ret[] = $k;
			}
		}

		return $ret;
	}

	/**
	 * @param $sid
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function loadData( $sid )
	{
		if ( $this->_off ) {    // what does 'off' mean?
			return;
		}

		//why loading the data of disabled fields?
		if ( !$this->enabled ) {
			return;
		}

		if ( !$this->_fData ) {
			$this->_fData = new stdClass();
			$this->_fData->baseData = new stdClass();
			$this->lang = Sobi::Lang( false );
		}
		$this->sid = $sid;
		$fdata = Sobi::Reg( 'fields_data_' . $sid, [] );
		!$this->suffix ? : StringUtils::Clean( $this->suffix );
		if ( $sid && count( $fdata ) && isset( $fdata[ $this->id ] ) ) {
			$this->_fData = $fdata[ $this->id ];
			$this->lang = $this->_fData->lang;
			$this->_rawData = $this->_fData->baseData;
			$this->_data = $this->_fData->baseData;

			// if the field has own method we have to re-init
			// ToDo check double call to specific loadData function in FullInit
			$this->checkMethod( 'loadData' );
			if ( $this->_type && method_exists( $this->_type, 'loadData' ) ) {
				$this->_type->loadData( $sid, $this->_fData, $this->_rawData, $this->_data );
			}
			if ( $this->editLimit > 0 && is_numeric( $this->_fData->editLimit ) ) {
				$this->editLimit = $this->_fData->editLimit;
			}
			elseif ( $this->editLimit < 0 ) {
				$this->editLimit = 2;
			}
			else {
				$this->editLimit = 2;
			}
			// if the limit has been reached, this field cannot be required
			if ( !( Sobi::Can( 'entry.manage.*' ) ) && $this->editLimit < 1 && in_array( Input::Task(), [ 'entry.save', 'entry.edit', 'entry.submit' ] ) ) {
				$this->required = false;
				$this->enabled = false;
				$this->_off = true;
			}
		}
		else {
			if ( !$this->fromCache() ) {
				$this->checkMethod( 'loadData' );
				if ( $this->_type && method_exists( $this->_type, 'loadData' ) ) {
					$this->_type->loadData( $sid, $this->_fData, $this->_rawData, $this->_data );
				}
				else {
					$this->_rawData = Factory::Db()
						->select( 'baseData', 'spdb_field_data', [ 'sid' => $this->sid, 'fid' => $this->fid, 'lang' => Sobi::Lang() ] )
						->loadResult();
				}
			}
		}
		if ( in_array( Input::Task(), [ 'entry.save', 'entry.edit', 'entry.submit' ] ) ) {
			if ( !$this->isFree && Input::Task() == 'entry.edit' ) {
				/* in case we are editing - check if this field wasn't paid already */
				SPLoader::loadClass( 'services.payment' );
				if ( SPPayment::check( $sid, $this->id ) ) {
					$this->fee = 0;
					$this->isFree = true;
				}
			}
			if ( !$this->editable && $this->_fData ) {
				$this->required = false;
			}
		}
	}

	/**
	 * @param $method
	 *
	 * @throws \SPException
	 */
	protected function checkMethod( $method )
	{
		if ( !$this->_type && $this->_class && class_exists( $this->_class ) && in_array( $method, get_class_methods( $this->_class ) ) ) {
			$this->fullInit();
		}
	}

	/**
	 * @return void
	 * @throws \SPException
	 */
	protected function fullInit()
	{
		if ( !$this->_loaded ) {
			$this->_loaded = true;
			$this->loadType();
			if ( $this->sid ) {
				if ( $this->_type && method_exists( $this->_type, 'loadData' ) ) {
					$this->_type->loadData( $this->sid, $this->_fData, $this->_rawData, $this->_data );
				}
			}
		}
	}

	/**
	 * @return bool|string
	 * @throws SPException
	 */
	protected function getClass()
	{
		if ( !$this->_class ) {
			$this->type =& $this->fieldType;
			if ( SPLoader::translatePath( 'opt/fields/' . $this->fieldType ) ) {
				SPLoader::loadClass( 'opt.fields.fieldtype' );
				$this->_class = SPLoader::loadClass( 'opt.fields.' . $this->fieldType );
			}
			if ( !$this->_class ) {
				$this->_off = true;
				Sobi::Error( 'Field', sprintf( 'Field type %s does not exist!', $this->fieldType ), C::WARNING );
			}
		}

		return $this->_class;
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function loadTables()
	{
		try {
			$lang = Sobi::Lang();
			$labels = Factory::Db()
				->select( [ 'sValue', 'sKey' ], 'spdb_language',
					[ 'fid'   => $this->id,
					  'sKey'  => $this->_translatable,
					  'oType' => 'field' ],
					"FIELD( language, '$lang', '%' ) ASC" )
				->loadAssocList( 'sKey' );
			if ( !count( $labels ) ) {
				// last fallback
				$labels = Factory::Db()
					->select( [ 'sValue', 'sKey' ], 'spdb_language',
						[ 'fid'      => $this->id,
						  'sKey'     => $this->_translatable,
						  'language' => 'en-GB',
						  'oType'    => 'field' ] )
					->loadAssocList( 'sKey' );
			}
			if ( Sobi::Lang() != Sobi::DefLang() ) {
				$labels2 = Factory::Db()
					->select( [ 'sValue', 'sKey' ], 'spdb_language',
						[ 'fid'      => $this->id,
						  'sKey'     => $this->_translatable,
						  'language' => Sobi::DefLang(), 'oType' => 'field' ] )
					->loadAssocList( 'sKey' );
				$labels = array_merge( $labels2, $labels );
			}
		}
		catch ( SPException $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}
		if ( count( $labels ) ) {
			Sobi::Trigger( 'Field', ucfirst( __FUNCTION__ ), [ &$labels ] );
			foreach ( $labels as $k => $v ) {
				$this->_set( $k, $v[ 'sValue' ] );
			}
		}
		$this->priority = $this->priority ? : 5;
		/* if field is an admin filed - it cannot be required */
		if ( ( $this->adminField || !$this->enabled ) && !defined( 'SOBIPRO_ADM' ) ) {
			$this->required = false;
		}
	}

	/**
	 * @param $key
	 *
	 * @return string|string[]
	 * @throws \SPException
	 */
	protected function cgf( $key )
	{
		if ( Input::Task() != 'field.edit' && strstr( $key, 'cfg:' ) ) {
			preg_match_all( '/\[cfg:([^\]]*)\]/', $key, $matches );
			if ( !( isset( $matches[ 1 ] ) ) || !( count( $matches[ 1 ] ) ) ) {
				preg_match_all( '/\{cfg:([^}]*)\}/', $key, $matches );
			}
			if ( count( $matches[ 1 ] ) ) {
				foreach ( $matches[ 1 ] as $i => $replacement ) {
					$key = str_replace( $matches[ 0 ][ $i ], Sobi::Cfg( $replacement ), $key );
				}
			}
		}

		return $key;
	}

	/**
	 * @param $var
	 * @param $val
	 *
	 * @throws SPException
	 */
	protected function _set( $var, $val )
	{
		if ( $this->has( $var ) ) {
			if ( is_array( $this->$var ) && is_string( $val ) ) {
				try {
					$val = SPConfig::unserialize( $val, $var );
				}
				catch ( SPException $x ) {
					Sobi::Error( $this->name(), sprintf( 'Cannot unserialize: %s.', $x->getMessage() ), C::NOTICE, 0, __LINE__, __FILE__ );
				}
			}
			if ( is_string( $val ) ) {
				$val = $this->cgf( $val );
			}
			$this->$var = $val;
		}
	}

	/**
	 * Gets the fields data for the edit form from the cache.
	 * The data are stored in the cache when user submits an entry.
	 * They are restored if the user comes back (later or from payment screen).
	 *
	 * @return bool
	 */
	protected function fromCache()
	{
		$fdata = Sobi::Reg( 'editcache' );
		if ( is_array( $fdata ) && isset( $fdata[ $this->nid ] ) ) {
			if ( is_array( $fdata[ $this->nid ] ) ) {
				$this->cleanEmptyValues( $fdata[ $this->nid ] );
			}
			if ( $fdata[ $this->nid ] ) {
				$this->_data = $fdata[ $this->nid ];
				$this->_rawData = $fdata[ $this->nid ];

				return true;
			}
		}

		return false;
	}

	/**
	 * @param $array
	 * When getting previously not filled (i.e) calendar field we will have something like:
	 * ['start' => ''] that will cause issue in calendar field later
	 * Actually should be handled in calendar field itself but as we're restoring the "get previously submitted data into a form"
	 * that vanished somehow it would cause kind of incompatibility later
	 * Thu, May 10, 2018 11:15:12
	 */
	protected function cleanEmptyValues( &$array )
	{
		foreach ( $array as $value ) {
			if ( is_array( $value ) ) {
				$this->cleanEmptyValues( $value );
			}
			else {
				if ( !$value ) {
					$array = null;
				}
			}
		}
	}
}
