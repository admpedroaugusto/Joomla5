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
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * @created 19-Nov-2009 by Radek Suski
 * @modified 02 October 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

use Sobi\C;
use Sobi\FileSystem\FileSystem;
use Sobi\Lib\Factory;
use Sobi\Utils\Arr;
use Sobi\Input\Input;
use Sobi\Utils\Serialiser;

SPLoader::loadClass( 'opt.fields.fieldtype' );

/**
 * Class SPField_Select
 */
class SPField_Select extends SPFieldType implements SPFieldInterface
{
	/* add here properties which are different from their initial value (model or derived class)
	   and properties valid only for this class. */

	/* properties with different value */
	/** @var string */
	protected $dType = 'predefined_multi_data_single_choice';
	/** @var int */
	protected $bsWidth = 5;
	/** @var int */
	protected $bsSearchWidth = 5;
	/** @var string */
	protected $cssClass = 'spClassSelect';
	/** @var string */
	protected $cssClassView = 'spClassViewSelect';
	/** @var string */
	protected $cssClassEdit = 'spClassEditSelect';
	/** @var string */
	protected $cssClassSearch = 'spClassSearchSelect';
	/*** @var bool */
	protected $suggesting = false;

	/* properties for this and derived classes */
	/** @var array */
	protected $options = [];
	/** @var array */
	protected $optionsById = [];
	/** @var int */
	protected $size = 1;
	/** @var int */
	protected $ssize = 1;
	/** @var string */
	protected $searchOperator = 'and';
	/** @var bool */
	protected $multi = false;
	/** @var string */
	protected $selectLabel = '--- select %s ---';

	/* properties only for this class */
	/** @var bool */
	protected $dependency = false;
	/** @var string */
	protected $dependencyDefinition = C::ES;
	/** @var array */
	protected $path = [];
	/** @var bool */
	protected $allowParents = true;
	/** @var string */
	protected $meaning = 'no';

	/** @var bool */
	private static $CAT_FIELD = true;

	/**
	 * Returns the parameter list (params). All properties which are not set in the model but used in the xml file of the field.
	 * No language dependant values, no values from the model (columns in the database).
	 *
	 * @return array
	 */
	protected function getAttr(): array
	{
		return [ 'searchMethod', 'itemprop', 'helpposition', 'suggesting', 'showEditLabel', 'metaSeparator', 'cssClassView', 'cssClassSearch', 'cssClassEdit', 'bsWidth', 'bsSearchWidth', 'size', 'ssize', 'searchOperator', 'dependencyDefinition', 'dependency', 'allowParents' ];
	}

	/**
	 * Shows the field in the edit entry or add entry form.
	 *
	 * @param bool $return - return or display directly
	 *
	 * @return string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function field( $return = false )
	{
		$html = C::ES;
		if ( $this->enabled ) {
			$fw = defined( 'SOBIPRO_ADM' ) ? C::BOOTSTRAP5 : Sobi::Cfg( 'template.framework-style', C::BOOTSTRAP5 );

			$class = defined( 'SOBIPRO_ADM' ) ?
				( $this->multi ? 'spClassMSelect' : 'spClassSelect' ) : $this->cssClass;
			$class = $this->required ? $class . ' required' : $class;
			$class = $this->dependency ? $class . ' spctrl-dependency-field' : $class;

			if ( !$this->size || !$this->multi ) {
				$this->size = 1;
			}
			$bsClass = $fw == C::BOOTSTRAP2 ? ' w-100' : C::ES;
			$bsClass .= $fw == C::BOOTSTRAP2 && $this->suffix ? ' suffix' : C::ES;

			$params = [ 'id' => $this->nid, 'size' => $this->size, 'class' => $class . $bsClass ];
			if ( $this->dependency ) {
				$params[ 'data' ] = [ 'order' => '1' ];
			}
			$selected = $this->getRaw();

			/* if isset( $selected[ 0 ] )  - then we have the data from edit cache
			 * because it contains the data like in request 0 => value.
			 * Otherwise we need to swap this.
			*/
			if ( is_array( $selected ) && count( $selected ) && !isset( $selected[ 0 ] ) ) {
				$selected = array_keys( $selected );
			}
			if ( ( $selected == null ) && ( $this->defaultValue ) ) {
				if ( $this->multi ) {
					$selected = explode( ',', $this->defaultValue );
					$selected = array_map( 'trim', $selected );
				}
				else {
					$selected = $this->defaultValue;
				}
			}

			$hiddenSelect = $validategroup = C::ES;
			if ( !$this->dependency ) {
				$select = SPHtml_Input::select( $this->nid, $this->getValues(), $selected, $this->multi, $params );
			}
			/* it's a dependency field */
			else {
				$validategroup = 'spctrl-validateselectgroup';
				$subFields = $hiddenValue = C::ES;

				if ( isset( $this->_fData->options ) && strlen( $this->_fData->options ) ) {
					$path = SPConfig::unserialize( $this->_fData->options );
					$subFields = $this->travelDependencyPath( $path, $params );
					$selected = $path[ 1 ];
					$hiddenValue = str_replace( '"', "'", json_encode( (object) $path ) );
				}
				$select = SPHtml_Input::select( $this->nid, $this->getValues(), $selected, $this->multi, $params ) . $subFields;

				$hiddenSelect = SPHtml_Input::hidden( $this->nid . '_path', $hiddenValue, C::ES, [ 'data' => [ 'section' => Sobi::Section() ] ] );
			}

			$html = $this->getHtml( $select, $hiddenSelect, $validategroup, $selected ? 'data-sp-content="1"' : C::ES );

			if ( !$return ) {
				echo $html;
			}
		}

		return $html;
	}

	/**
	 * @param bool $required
	 *
	 * @return array
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function getValues( bool $required = true )
	{
		$values = [];
		if ( $this->dependency ) {
			SPFactory::header()->addJsFile( 'opt.field_select' );

			$options = json_decode( FileSystem::Read( SOBI_PATH . '/etc/fields/select-list/definitions/' . ( str_replace( '.xml', '.json', $this->dependencyDefinition ) ) ), true );
			if ( isset( $options[ 'translation' ] ) ) {
				Factory::Application()->loadLanguage( $options[ 'translation' ] );
				$values = [ '' => $this->getLabelText( $this->name ) ];
				foreach ( $options[ 'options' ] as $option ) {
					$values[ $option[ 'id' ] ] = Sobi::Txt( strtoupper( $options[ 'prefix' ] ) . '.' . strtoupper( $option[ 'id' ] ) );
				}
			}
			else {
				foreach ( $options[ 'options' ] as $option ) {
					$values[ $option[ 'id' ] ] = $option[ 'id' ];
				}
			}
		}

		elseif ( is_array( $this->options ) && count( $this->options ) ) {
			if ( $required ) {
				$this->required( $values );
			}
			foreach ( $this->options as $option ) {
				if ( isset( $option[ 'options' ] ) && is_array( $option[ 'options' ] ) && count( $option[ 'options' ] ) ) {
					$values[ $option[ 'label' ] ] = [];
					foreach ( $option[ 'options' ] as $subOption ) {
						$values[ $option[ 'label' ] ][ $subOption[ 'id' ] ] = $subOption[ 'label' ];
					}
				}
				else {
					$values[ $option[ 'id' ] ] = $option[ 'label' ];
				}
			}
		}

		return $values;
	}

	/**
	 * @param $values
	 */
	protected function required( &$values )
	{
		if ( $this->required || strlen( $this->selectLabel ) ) {
			if ( $this->required && strlen( $this->selectLabel ) < 1 ) {
				$this->selectLabel = Sobi::Txt( 'FD.SEARCH_SELECT_LABEL' );
			}

			$values [ 0 ] = $this->getLabelText( $this->name );
		}
	}

	/**
	 * SPField_Select constructor.
	 *
	 * @param $field
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function __construct( &$field )
	{
		parent::__construct( $field );

		$this->getSelectLabel();

		$db = Factory::Db();
		$options = $optionsList = [];

		static $lang, $defLang = C::ES;
		if ( !$lang ) {
			$lang = Sobi::Lang( false );    /* the used language */
			$defLang = Sobi::DefLang();     /* the default language on frontend */
		}

		try {
			/* load the option values */
			$optionsList = $db
				->select( '*', 'spdb_field_option', [ 'fid' => $this->fid ] )
				->loadObjectList();

			/* load the option labels */
			$params = [ 'fid' => $this->fid, 'oType' => 'field_option', 'language' => $lang ];
			$langList = $db
				->select( [ 'sValue', 'sKey' ], 'spdb_language', $params )
				->loadObjectList();

			/* if the labels are not available in the used language, load them from the default language */
			if ( !count( $langList ) ) {
				$params[ 'language' ] = $defLang;
				$langList = $db
					->select( [ 'sValue', 'sKey' ], 'spdb_language', $params )
					->loadObjectList();

				/* if the labels are also not available in the default language, get the first available language */
				if ( !count( $langList ) ) {
					$params[ 'language' ] = SPLang::alternativeLanguage();
					$langList = $db
						->select( [ 'sValue', 'sKey' ], 'spdb_language', $params )
						->loadObjectList();
				}
			}
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'CANNOT_GET_FIELD_POSITION_DB_ERR', $x->getMessage() ), C::ERROR, 500, __LINE__, __FILE__ );
		}

		/* if there are options */
		if ( is_array( $optionsList ) && count( $optionsList ) ) {
			if ( is_array( $langList ) && count( $langList ) ) {
				foreach ( $langList as $label ) {
					$label->sKey = strtolower( $label->sKey );
					$labels[ $label->sKey ] = $label->sValue;
				}
			}
			/* re-label */
			foreach ( $optionsList as $opt ) {
				$option = [];
				$option[ 'id' ] = $opt->optValue;

				// unfortunately we need to exclude the Aggregation field from the correct behaviour as it saves its data not like other select fields
				if ( $field->get( 'fieldType' ) == 'aggregation' ) {
					$option[ 'label' ] = $labels[ $opt->optValue ] ?? $opt->optValue;
				}
				else {
					/* is the option name in the just used language available? */
					if ( isset( $labels[ $opt->optValue ] ) ) {
						$option[ 'label' ] = $labels[ $opt->optValue ];
					}
					else {
						$option[ 'label' ] = $opt->optValue;
					}
				}
				$option[ 'position' ] = $opt->optPos;
				$option[ 'parent' ] = $opt->optParent;
				if ( $option[ 'parent' ] ) {
					if ( !( isset( $options[ $option[ 'parent' ] ] ) ) ) {
						$options[ $option[ 'parent' ] ] = [];
					}

					$options[ $option[ 'parent' ] ][ 'options' ][ $option[ 'id' ] ] = $option;
					$this->optionsById[ $option[ 'id' ] ] = $option;
				}
				else {
					if ( !( isset( $options[ $option[ 'id' ] ] ) ) ) {
						$options[ $option[ 'id' ] ] = [];
					}
					$options[ $option[ 'id' ] ] = array_merge( $options[ $option[ 'id' ] ], $option );
					$this->optionsById[ $option[ 'id' ] ] = $options[ $option[ 'id' ] ];
				}
			}
			$this->options = $this->sortOpt( $options );
		}
		else {
			$this->options[ 0 ][ 'id' ] = 'option-id';
			$this->options[ 0 ][ 'label' ] = Sobi::Txt( 'FD.SELECT_OPTION_NAME' );
			$this->options[ 0 ][ 'position' ] = 1;
			$this->options[ 0 ][ 'parent' ] = C::ES;
		}
	}

	/**
	 * Gets field specific values if these are in another table.
	 *
	 * @param $sid - id of the entry
	 * @param $fullData - the database row form the spdb_field_data table
	 * @param $rawData - raw data of the field content
	 * @param $fData - full formatted data of the field content
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function loadData( $sid, &$fullData, &$rawData, &$fData )
	{
		$db = Factory::Db();
		$table = $db->join(
			[
				[ 'table' => 'spdb_field_option_selected', 'as' => 'sdata', 'key' => 'fid' ],
				[ 'table' => 'spdb_field_data', 'as' => 'fdata', 'key' => 'fid' ],
				[ 'table' => 'spdb_language', 'as' => 'ldata', 'key' => [ 'sdata.optValue', 'ldata.sKey' ] ],
			]
		);
		try {
			$order = $this->checkCopy() ? 'scopy.asc' : 'scopy.desc';
			$where = [
				'sdata.fid'   => $this->id,
				'sdata.sid'   => $sid,
				'fdata.sid'   => $sid,
				'ldata.oType' => 'field_option',
				'ldata.fid'   => $this->id,
			];
			if ( $this->dependency ) {
				$where[ 'ldata.sKey' ] = $rawData;
			}
			$db->select( '*, sdata.copy as scopy', $table, $where, $order, 0, 0, true /*, 'sdata.copy' */ );
			$data = $db->loadObjectList( 'language' );
			if ( $data ) {
				if ( isset( $data[ Sobi::Lang() ] ) ) {
					$data = $data[ Sobi::Lang() ];
				}
				elseif ( isset( $data[ Sobi::DefLang() ] ) ) {
					$data = $data[ Sobi::DefLang() ];
				}
				else {
					foreach ( $data as $k => $v ) {
						$data = $v;
					}
				}
				$rawData = $data->sKey ?? null;
				$fullData->baseData = $data->sValue ?? null;
				$fData = $data->sValue ?? null;
			}
		}
		catch ( SPException $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'CANNOT_GET_SELECTED_OPTIONS', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}
	}

	/**
	 * @param $options
	 *
	 * @return array
	 */
	protected function sortOpt( $options )
	{
		$sorted = [];
		if ( count( $options ) ) {
			foreach ( $options as $option ) {
				if ( isset( $option[ 'options' ] ) ) {
					$option[ 'options' ] = $this->sortOpt( $option[ 'options' ] );
				}
				if ( isset( $sorted[ $option[ 'position' ] ] ) ) {
					$option[ 'position' ] = +rand( 1000, 9999 );
				}
				$sorted[ $option[ 'position' ] ] = $option;
			}
		}
		ksort( $sorted );

		return $sorted;
	}

	/**
	 * @param $data
	 * @param string $request
	 *
	 * @return array|mixed|null
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function fetchData( $data, $request = 'post' )
	{
		if ( ( $data !== null ) && strlen( $data ) || $this->dependency ) {
			if ( $this->dependency ) {
				$path = json_decode( str_replace( "'", '"', Sobi::Clean( Input::String( $this->nid . '_path', $request ) ) ), true );
				if ( is_array( $path ) && count( $path ) ) {
					$options = json_decode( FileSystem::Read( SOBI_PATH . '/etc/fields/select-list/definitions/' . ( str_replace( '.xml', '.json', $this->dependencyDefinition ) ) ), true );
					$selected = $options[ 'options' ];
					foreach ( $path as $part ) {
						if ( isset( $selected[ $part ] ) && isset( $selected[ $part ][ 'childs' ] ) && count( $selected[ $part ][ 'childs' ] ) ) {
							$selected = $selected[ $part ][ 'childs' ];
						}
						elseif ( isset( $selected[ $part ] ) ) {
							$selected = $selected[ $part ][ 'id' ];
						}
						elseif ( $part != 0 && count( $selected ) ) {
							throw new SPException( SPLang::e( 'FIELD_NO_SUCH_OPT', $data, $this->name ) );
						}
					}
					if ( is_array( $selected ) && count( $selected ) && !( $this->allowParents ) ) {
						throw new SPException( SPLang::e( 'SELECT_FIELD_NO_PARENT', $this->name ) );
					}
				}
				$this->path = $path;

				return $path;
			}
			/* check if such an option exists at all */
			elseif ( $data && strlen( $data ) && !( isset( $this->optionsById[ $data ] ) ) ) {
				throw new SPException( SPLang::e( 'FIELD_NO_SUCH_OPT', $data, $this->name ) );
			}

			return $data == "0" ? [] : [ $data ];
		}
		else {
			return [];
		}
	}

	/**
	 * Gets the data for this field, verifies them the first time.
	 * Frontend ONLY!!
	 *
	 * @param SPEntry $entry
	 * @param string $tsId
	 * @param string $request
	 *
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function submit( &$entry, $tsId = C::ES, $request = 'POST' )
	{
		$data = $this->verify( $entry, $request );

		$return = [];
		if ( $data && count( $data ) ) {
			if ( $this->multi ) {
				$return[ $this->nid ] = $data;
				//return Input::Search( $this->nid );
			}
			else {
				if ( !array_key_exists( 0, $data ) ) {
					$data = array_reverse( $data );
				}
				$return[ $this->nid ] = $data[ 0 ];
			}
		}

		return $return;
	}

	/**
	 * Verifies the data.
	 *
	 * @param SPEntry $entry
	 * @param string $request
	 *
	 * @return array
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function verify( $entry, $request )
	{
		$data = $this->fetchData( $this->multi ? Input::Arr( $this->nid ) : Input::Word( $this->nid ) );

		if ( isset( $data[ 0 ] ) && $data[ 0 ] == '0' ) {
			$data = [];
		}
		$dexs = is_array( $data ) && count( $data );

		/* no data are given */
		if ( !$dexs ) {
			if ( ( $this->meaning && !( $this->meaning == 'no' ) ) && !defined( 'SOBIPRO_ADM' ) ) {
				switch ( $this->meaning ) {
					case 'price':
						/* Special handling for price fields as there can be several price fields.
						Need to check only the current one (set by the template) */
						if ( Input::String( 'pricefield', 'request', C::ES ) == $this->nid ) {
							throw new SPException( SPLang::e( 'FIELD_REQUIRED_CONFIRM_PRICE', $this->name ) );
						}
						break;

					case 'terms':
						throw new SPException( SPLang::e( 'FIELD_REQUIRED_CONFIRM_TERMS', $this->name ) );
				}
			}

			/* check if it was required */
			if ( $this->required && ( !$this->meaning || $this->meaning == 'no' ) ) {
				throw new SPException( SPLang::e( 'FIELD_REQUIRED_ERR_OPT', $this->name ) );
			}
		}

		/* data are given */
		else {
			/* check if there was an adminField */
			if ( $this->adminField ) {
				if ( !Sobi:: Can( 'entry.adm_fields.edit' ) ) {
					throw new SPException( SPLang::e( 'FIELD_NOT_AUTH', $this->name ) );
				}
			}
			/* check if it was free */
			if ( !$this->isFree && $this->fee ) {
				SPFactory::payment()->add( $this->fee, $this->name, $entry->get( 'id' ), $this->fid );
			}

			/* check if it was editLimit */
			if ( $this->editLimit == 0 && !Sobi::Can( 'entry.adm_fields.edit' ) ) {
				throw new SPException( SPLang::e( 'FIELD_NOT_AUTH_EXP', $this->name ) );
			}

			/* check if it was editable */
			if ( !$this->editable && !Sobi::Can( 'entry.adm_fields.edit' ) && $entry->get( 'version' ) > 1 ) {
				throw new SPException( SPLang::e( 'FIELD_NOT_AUTH_NOT_ED', $this->name ) );
			}
		}

//		$this->setData( $data );
		return $data;
	}


	/**
	 * @param $entry
	 * @param $data
	 * @param $request
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function saveDependencyField( &$entry, $data, $request )
	{
		$db = Factory::Db();

		$time = Input::Now();
		$uid = Sobi::My( 'id' );
		$IP = Input::Ip4();

		$params = [];
		$params[ 'publishUp' ] = $entry->get( 'publishUp' ) ?? $db->getNullDate();
		$params[ 'publishDown' ] = $entry->get( 'publishDown' ) ?? $db->getNullDate();
		$params[ 'fid' ] = $this->fid;
		$params[ 'sid' ] = $entry->get( 'id' );
		$params[ 'section' ] = Sobi::Reg( 'current_section' );
		$params[ 'lang' ] = Sobi::Lang();
		$params[ 'enabled' ] = $entry->get( 'state' );
		$params[ 'approved' ] = $entry->get( 'approved' );
		$params[ 'confirmed' ] = $entry->get( 'confirmed' );

		/* if it is the first version, it is new entry */
		if ( $entry->get( 'version' ) == 1 ) {
			$params[ 'createdTime' ] = $time;
			$params[ 'createdBy' ] = $uid;
			$params[ 'createdIP' ] = $IP;
		}
		$params[ 'updatedTime' ] = $time;
		$params[ 'updatedBy' ] = $uid;
		$params[ 'updatedIP' ] = $IP;
		$params[ 'options' ] = $data;

		/* get the last element */
		$params[ 'baseData' ] = C::ES;
		foreach ( $this->path as $element ) {
			if ( $element ) {
				/* escape it although not necessary */
				$params[ 'baseData' ] = Factory::Db()->escape( strip_tags( $element ) );
			}
		}
		$params[ 'copy' ] = ( int ) !$entry->get( 'approved' );

		$this->setEditLimit( $entry, $params[ 'baseData' ] );
		$params[ 'editLimit' ] = $this->editLimit;

		/* save it to the database */
		$this->saveToDatabase( $params, $entry->get( 'version' ), $this->untranslatable ? : false );

		$selectedOption = C::ES;
		$options = [];
		/* collect the needed params */
		foreach ( $data as $selected ) {
			/* escape it although not necessary */
			$selectedOption = $selected = Factory::Db()->escape( strip_tags( $selected ) );
			$options[] = [ 'fid'      => $this->fid,
			               'sid'      => $entry->get( 'id' ),
			               'optValue' => $selected,
			               'copy'     => $params[ 'copy' ],
			               'params'   => C::ES ];
		}

		/* delete old selected values */
		try {
			$db->delete( 'spdb_field_option_selected', [ 'fid' => $this->fid, 'sid' => $entry->get( 'id' ), 'copy' => $params[ 'copy' ] ] );
		}
		catch ( SPException $x ) {
			Sobi::Error( __CLASS__, SPLang::e( 'CANNOT_DELETE_PREVIOUS_DATA', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}

		/* insert new selected value */
		try {
			if ( $selectedOption ) {
				$db->insertArray( 'spdb_field_option_selected', $options );
			}
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( __CLASS__, SPLang::e( 'CANNOT_SAVE_SELECTED_DATA', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}
	}

	/**
	 * FRONTEND:
	 * Gets the data for a field and save it in the database.
	 *
	 * @param \SPEntry $entry
	 * @param string $request
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function saveData( &$entry, $request = 'POST' )
	{
		if ( $this->enabled ) {
			$data = $this->verify( $entry, $request );

			/* if we are here, we can save these data */
			$db = Factory::Db();
			if ( is_array( $data ) && count( $data ) ) {
				if ( $this->dependency ) {
					try {
						$this->saveDependencyField( $entry, $data, $request );

						return;
					}
					catch ( SPException|Exception $e ) {
					}
				}
				$options = $params = [];
				$time = Input::Now();
				$IP = Input::Ip4();
				$uid = Sobi::My( 'id' );

				$params[ 'publishUp' ] = $entry->get( 'publishUp' ) ?? $db->getNullDate();
				$params[ 'publishDown' ] = $entry->get( 'publishDown' ) ?? $db->getNullDate();
				$params[ 'fid' ] = $this->fid;
				$params[ 'sid' ] = $entry->get( 'id' );
				$params[ 'section' ] = Sobi::Reg( 'current_section' );
				$params[ 'lang' ] = Sobi::Lang();
				$params[ 'enabled' ] = $entry->get( 'state' );
				$params[ 'approved' ] = $entry->get( 'approved' );
				$params[ 'confirmed' ] = $entry->get( 'confirmed' );
				/* if it is the first version, it is new entry */
				if ( $entry->get( 'version' ) == 1 ) {
					$params[ 'createdTime' ] = $time;
					$params[ 'createdBy' ] = $uid;
					$params[ 'createdIP' ] = $IP;
				}
				$params[ 'updatedTime' ] = $time;
				$params[ 'updatedBy' ] = $uid;
				$params[ 'updatedIP' ] = $IP;
				$params[ 'baseData' ] = Serialiser::Serialise( $data );
				$params[ 'copy' ] = ( int ) !( $entry->get( 'approved' ) );

				$this->setEditLimit( $entry, $params[ 'baseData' ] );
				$params[ 'editLimit' ] = $this->editLimit;

				/* save it to the database */
				$this->saveToDatabase( $params, $entry->get( 'version' ), $this->untranslatable ? : false );

				/* delete old selected values */
				try {
					$db->delete( 'spdb_field_option_selected', [ 'fid' => $this->fid, 'sid' => $entry->get( 'id' ), 'copy' => $params[ 'copy' ] ] );
				}
				catch ( Sobi\Error\Exception $x ) {
					Sobi::Error( __CLASS__, SPLang::e( 'CANNOT_DELETE_PREVIOUS_DATA', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
				}

				/* insert new selected value */
				foreach ( $data as $selected ) {
					/* collect the needed params */
					$options[] = [ 'fid'      => $this->fid,
					               'sid'      => $entry->get( 'id' ),
					               'optValue' => $db->escape( strip_tags( $selected ) ),
					               'copy'     => $params[ 'copy' ],
					               'params'   => C::ES ];
				}
				try {
					$db->insertArray( 'spdb_field_option_selected', $options );
				}
				catch ( Sobi\Error\Exception $x ) {
					Sobi::Error( __CLASS__, SPLang::e( 'CANNOT_SAVE_SELECTED_DATA', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
				}
			}
			elseif ( $entry->get( 'version' ) > 1 ) {
				if ( !$entry->get( 'approved' ) ) {
					try {
						$db->update( 'spdb_field_option_selected', [ 'copy' => 1 ], [ 'fid' => $this->fid, 'sid' => $entry->get( 'id' ) ] );
					}
					catch ( Sobi\Error\Exception $x ) {
						Sobi::Error( __CLASS__, SPLang::e( 'CANNOT_UPDATE_PREVIOUS_DATA', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
					}
				}
				else {
					/* delete old selected values */
					try {
						$db->delete( 'spdb_field_option_selected', [ 'fid' => $this->fid, 'sid' => $entry->get( 'id' ) ] );
					}
					catch ( Sobi\Error\Exception $x ) {
						Sobi::Error( __CLASS__, SPLang::e( 'CANNOT_DELETE_PREVIOUS_DATA', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
					}
				}
			}

			/* Evaluate rawdata and data */
			if ( $this->multi ) {
				$rawdata = [];
			}
			else {
				$rawdata = C::ES;
			}
			if ( $data ) {
				foreach ( $data as $selected ) {
					if ( $this->multi ) {
						$rawdata[ $selected ] = $this->optionsById[ $selected ][ 'label' ];
					}
					else {
						$rawdata = $selected;
					}
				}

				if ( $this->multi ) {
					$data = implode( '</li>\n<li>', $rawdata );
					$data = "<ul id=\"$this->nid\" class=\"$this->cssClass\">\n<li>$data</li>\n</ul>\n";
				}
				else {
					$data = $this->optionsById[ $rawdata ][ 'label' ];
				}
			}
			$this->setData( $rawdata, $data );
		}
	}

	/**
	 * Static function to create the right SQL-Query if an entry list should be sorted by this field (select or radio button).
	 *
	 * @param $table
	 * @param $conditions
	 * @param $oPrefix
	 * @param $eOrder
	 * @param $eDir
	 *
	 * @return array|bool -> Frontend:  true -> all parameters are set by the local function
	 *                                  false -> let the calling routine set the tables etc (deprecated).
	 *                       Backend: return the sorted entries itself
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function sortBy( &$table, &$conditions, &$oPrefix, &$eOrder, $eDir )
	{
		$db = Factory::Db();
		$table = $db->join(
			[
				[ 'table' => 'spdb_field_option_selected', 'as' => 'sdata', 'key' => 'fid' ],
				[ 'table' => 'spdb_object', 'as' => 'spo', 'key' => [ 'sdata.sid', 'spo.id' ] ],
				[ 'table' => 'spdb_field_data', 'as' => 'fdata', 'key' => [ 'fdata.fid', 'sdata.fid' ] ],
				[ 'table' => 'spdb_field', 'as' => 'fdef', 'key' => [ 'fdef.fid', 'sdata.fid' ] ],
				[ 'table' => 'spdb_language', 'as' => 'ldata', 'key' => [ 'sdata.optValue', 'ldata.sKey' ] ],
				[ 'table' => 'spdb_relations', 'as' => 'sprl', 'key' => [ 'spo.id', 'sprl.id' ] ],
			]
		);
		$oPrefix = 'spo.';
		$conditions[ 'spo.oType' ] = 'entry';

		if ( !defined( 'SOBIPRO_ADM' ) ) {
			if ( !isset( $conditions[ 'sprl.pid' ] ) ) {
				$conditions[ 'sprl.pid' ] = Input::Sid();
			}
		}
		if ( isset( $conditions[ 'ids' ] ) ) {
			if ( defined( 'SOBIPRO_ADM' ) ) {
				$conditions[ 'spo.id' ] = $conditions[ 'ids' ];
			}
			unset( $conditions[ 'ids' ] );
		}
		$conditions[ 'ldata.oType' ] = 'field_option';
		$conditions[ 'fdef.nid' ] = $eOrder;
		$eOrder = 'sValue.' . $eDir . ", field( language, '" . Sobi::Lang( false ) . "', '" . Sobi::DefLang() . "' )";

		if ( defined( 'SOBIPRO_ADM' ) ) {
			$fields = $db
				->dselect( 'sdata.sid', $table, $conditions, $eOrder )
				->loadResultArray();

			return $fields;
		}

		return true;
	}

	/**
	 * @param $sid
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function approve( $sid )
	{
		parent::approve( $sid );

		$db = Factory::Db();
		if ( $db->select( 'COUNT(*)', 'spdb_field_option_selected', [ 'sid' => $sid, 'copy' => '1', 'fid' => $this->fid ] )->loadResult() ) {
			try {
				$db->delete( 'spdb_field_option_selected', [ 'sid' => $sid, 'copy' => '0', 'fid' => $this->fid ] );
				$db->update( 'spdb_field_option_selected', [ 'copy' => '0' ], [ 'sid' => $sid, 'copy' => '1', 'fid' => $this->fid ] );
			}
			catch ( Sobi\Error\Exception $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::ERROR, 500, __LINE__, __FILE__ );
			}
		}
	}

	/**
	 * @param int $sid - entry id
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function rejectChanges( $sid )
	{
		parent::rejectChanges( $sid );
		try {
			Factory::Db()->delete( 'spdb_field_option_selected', [ 'sid' => $sid, 'fid' => $this->fid, 'copy' => '1', ] );
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}
	}

	/**
	 * @param $sid
	 *
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function deleteData( $sid )
	{
		parent::deleteData( $sid );
		try {
			Factory::Db()->delete( 'spdb_field_option_selected', [ 'sid' => $sid, 'fid' => $this->fid ] );
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}
	}

	/**
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	public function delete()
	{
		$db = Factory::Db();
		try {
			$db->delete( 'spdb_field_option_selected', [ 'fid' => $this->fid ] );
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}
		try {
			$db->delete( 'spdb_field_option', [ 'fid' => $this->fid ] );
			$db->delete( 'spdb_language', [ 'oType' => 'field_option', 'fid' => $this->id ] );
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}
	}

	/**
	 * @param $options
	 * @param $arr
	 */
	protected function _parseOptions( $options, &$arr )
	{
		foreach ( $options as $value ) {
			if ( isset( $value[ 'options' ] ) && is_array( $value[ 'options' ] ) ) {
				$arr[ $value[ 'label' ] ] = [];
				$this->_parseOptions( $value[ 'options' ], $arr[ $value[ 'label' ] ] );
			}
			else {
				$arr[ $value[ 'id' ] ] = $value[ 'label' ];
			}
		}
	}

	/**
	 * Gets the data for this field from $_FILES and verifies them the first time.
	 * Backend ONLY!!
	 *
	 * @param \SPEntry $entry
	 * @param string $request
	 * @param false $clone
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function validate( $entry, $request, $clone = false )
	{
		/** it can be for core files only at the moment because a stupid developer (yes, we all know which one) declared too many private methods and inherited classes returning always wrong results */
		$class = strtolower( get_class( $this ) );
		if ( strstr( $class, 'select' ) || strstr( $class, 'radio' ) || strstr( $class, 'chbxgr' ) ) {
			$this->verify( $entry, $request );
		}
	}

	/**
	 * @param $view
	 */
	public function onFieldEdit( &$view )
	{
		$dependencyDefinitions = scandir( SOBI_PATH . '/etc/fields/select-list/' );
		if ( count( $dependencyDefinitions ) ) {
			$set = [];
			foreach ( $dependencyDefinitions as $file ) {
				if ( !is_dir( SOBI_PATH . '/etc/fields/select-list/' . $file ) ) {
					$set[ $file ] = $file;
				}
			}
			$view->assign( $set, 'dependencyDefinition' );
		}
		$options = [];
		$this->_parseOptions( $this->options, $options );
		$arrUtils = new Arr();
		$options = $arrUtils->toINIString( $options );
		$view->assign( $options, 'options' );
	}

	/**
	 * @return array|null
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function struct()
	{
		$selected = $this->getRaw();
		$data = $this->data();
		$_options = [];
		if ( $this->dependency ) {
			if ( isset( $this->_fData->options ) ) {
				$path = SPConfig::unserialize( $this->_fData->options );
			}
			else {
				return null;
			}
			$selectedPath = [];
			$options = json_decode( FileSystem::Read( SOBI_PATH . '/etc/fields/select-list/definitions/' . ( str_replace( '.xml', '.json', $this->dependencyDefinition ) ) ), true );
			if ( isset( $options[ 'translation' ] ) ) {
				Factory::Application()->loadLanguage( $options[ 'translation' ] );
				$data = Sobi::Txt( strtoupper( $options[ 'prefix' ] ) . '.' . strtoupper( $selected ) );
			}
			if ( count( $path ) && isset( $options[ 'translation' ] ) ) {
				foreach ( $path as $step ) {
					$selectedPath[ $step ] = Sobi::Txt( strtoupper( $options[ 'prefix' ] ) . '.' . strtoupper( $step ) );
				}
			}
			$_options = [ 'path' => count( $selectedPath ) ? $selectedPath : $path ];
		}
		else {
			foreach ( $this->options as $opt ) {
				if ( isset( $opt[ 'options' ] ) && is_array( $opt[ 'options' ] ) ) {
					foreach ( $opt[ 'options' ] as $sub ) {
						$_options[] = [
							'_complex'    => 1,
							'_data'       => $sub[ 'label' ],
							'_attributes' => [ 'group' => $opt[ 'id' ], 'selected' => $selected == $sub[ 'id' ] ? 'true' : 'false', 'id' => $sub[ 'id' ], 'position' => $sub[ 'position' ] ],
						];
					}
				}
				else {
					$_options[] = [
						'_complex'    => 1,
						'_data'       => $opt[ 'label' ],
						'_attributes' => [ 'selected' => $selected == $opt[ 'id' ] ? 'true' : 'false', 'id' => $opt[ 'id' ], 'position' => $opt[ 'position' ] ],
					];
				}
			}
		}
		$this->cleanCss();

		return [
			'_complex'    => 1,
			'_data'       => $data,
			'_attributes' => [
				'class'    => $this->cssClass,
				'selected' => $this->getRaw(),
			],
			'_options'    => $_options,
		];

	}

	/**
	 * @param $path
	 *
	 * @return array
	 * @throws \Sobi\Error\Exception|\SPException
	 */
	protected function loadDependencyDefinition( $path )
	{
		static $options = [];
		if ( !count( $options ) ) {
			$options = json_decode( FileSystem::Read( SOBI_PATH . '/etc/fields/select-list/definitions/' . ( str_replace( '.xml', '.json', $this->dependencyDefinition ) ) ), true );
		}
		if ( isset( $options[ 'translation' ] ) ) {
			Factory::Application()->loadLanguage( $options[ 'translation' ] );
		}
		$type = C::ES;
		$selected = $options[ 'options' ];
		foreach ( $path as $option ) {
			$type = isset( $selected[ $option ][ 'child-type' ] ) ? Sobi::Txt( strtoupper( $options[ 'prefix' ] ) . '.' . strtoupper( $selected[ $option ][ 'child-type' ] ) ) : C::ES;
			$selected = array_key_exists( $option, $selected ) ? $selected[ $option ][ 'childs' ] : C::ES;
		}

		$values = [];
		if ( is_array( $selected ) && count( $selected ) ) {
			$values[ 0 ] = $this->getLabelText( strlen( $type ) ? $type : $this->name );

			foreach ( $selected as $child ) {
				if ( isset( $options[ 'translation' ] ) ) {
					$values[ $child[ 'id' ] ] = Sobi::Txt( strtoupper( $options[ 'prefix' ] ) . '.' . strtoupper( $child[ 'id' ] ) );
				}
				else {
					$values[ $child[ 'id' ] ] = $child[ 'id' ];
				}
			}

			return $values;
		}

		return $values;
	}

	/**
	 * @param $path
	 * @param $subParams
	 *
	 * @return string
	 * @throws \Sobi\Error\Exception|\SPException
	 */
	protected function travelDependencyPath( $path, $subParams ): string
	{
		$subFields = C::ES;
		if ( $path && count( $path ) ) {
			$progress = [];
			foreach ( $path as $index => $step ) {
				$progress[] = $step;

				$subParams[ 'data' ][ 'order' ] = $index + 1;
				$subParams[ 'id' ] = $this->nid . '_' . $index;

				$lists = $this->loadDependencyDefinition( $progress );
				$selected = $path[ $index + 1 ] ?? C::ES;
				if ( count( $lists ) ) {
					$subFields .= SPHtml_Input::select( $this->nid, $lists, $selected, false, $subParams );
				}
			}
		}

		return $subFields;
	}

	/**
	 * AJAX call.
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function ProxyDependency()
	{
		$path = json_decode( Sobi::Clean( Input::String( 'path' ) ), true );
		$values = $this->loadDependencyDefinition( $path );
		SPFactory::mainframe()
			->cleanBuffer()
			->customHeader();
		exit( json_encode( [ 'options' => $values, 'path' => ( json_encode( $path ) ) ] ) );
	}

	/**
	 * Shows the field in the search form.
	 *
	 * @param false $return return or display directly
	 *
	 * @return string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function searchForm( $return = false )
	{
		if ( $this->searchMethod == 'general' || $this->searchMethod == C::ES ) {
			return C::ES;
		}

		$values = [];
		$tmpdata = $this->getValues( false );
		if ( !$this->dependency ) {
			if ( $this->selectLabel ) {
				$values = [ '' => $this->getLabelText( $this->name ) ];
			}
			elseif ( $this->searchMethod == 'select' ) {
				$values = [ '' => Sobi::Txt( 'FMN.SEARCH_SELECT_LIST', [ 'name' => $this->name ] ) ];
			}
		}
		$values = array_merge( $values, $tmpdata );

		$fw = defined( 'SOBIPRO_ADM' ) ? C::BOOTSTRAP5 : Sobi::Cfg( 'template.framework-style', C::BOOTSTRAP5 );
		$bsClass = $fw == C::BOOTSTRAP2 ? ' w-100' : C::ES;
		$bsClass .= $fw == C::BOOTSTRAP2 && $this->suffix ? ' suffix' : C::ES;
		$class = $this->cssClassSearch . ' ' . $bsClass;

		if ( !$this->ssize || $this->searchMethod == 'select' ) {
			$this->ssize = 1;
		}

		$params = [ 'id' => $this->nid, 'size' => $this->ssize, 'class' => $class ];
		if ( !$this->dependency ) {
			$hiddenSelect = C::ES;
			$select = SPHtml_Input::select( $this->nid, $values, $this->_selected, ( $this->searchMethod == 'mselect' ), $params );
		}
		/* it's a dependency field */
		else {
			SPFactory::header()->addJsFile( 'opt.field_select' );

			$params[ 'class' ] .= ' spctrl-dependency-field';
			$params[ 'data' ] = [ 'order' => '1' ];

			$path = json_decode( Sobi::Clean( Input::String( $this->nid . '_path' ) ), true );
			$subFields = $this->travelDependencyPath( $path, $params );
			$this->_selected = $path[ 1 ] ?? $this->_selected;

			$select = SPHtml_Input::select( $this->nid, $values, $this->_selected, ( $this->searchMethod == 'mselect' ), $params ) . $subFields;

			$hiddenValue = $path ? str_replace( '"', "&quot;", json_encode( (object) $path ) ) : C::ES;
			$hiddenSelect = SPHtml_Input::hidden( $this->nid . '_path', $hiddenValue, C::ES, [ 'data' => [ 'selected' => C::ES, 'section' => Sobi::Section() ] ] );
		}

		return $this->getHtml( $select, $hiddenSelect, Sobi::Cfg( 'search.select_def_css', 'sp-search-select' ) );
	}

	/**
	 * @param string $data
	 * @param int $section
	 * @param bool $startWith
	 *
	 * @return array|bool
	 * @throws \Sobi\Error\Exception|\SPException
	 */
	public function searchSuggest( $data, $section, $startWith = true, $ids = false )
	{
		if ( $this->dependency ) {
			return parent::searchSuggest( $data, $section, $startWith, $ids );
		}
		$terms = [];
		$data = $startWith ? "$data%" : "%$data%";
		try {
			$fids = Factory::Db()
				->dselect( [ 'sKey', 'sValue' ], 'spdb_language', [ 'oType' => 'field_option', 'fid' => $this->fid, 'sValue' => $data ] )
				->loadAssocList();
			if ( count( $fids ) ) {
				foreach ( $fids as $opt ) {
					$count = Factory::Db()
						->dselect( 'COUNT(*)', 'spdb_field_option_selected', [ 'copy' => '0', 'fid' => $this->fid, 'optValue' => $opt[ 'sKey' ] ] )
						->loadResult();
					if ( $count ) {
						$terms[] = $opt[ 'sValue' ];
					}
				}
			}
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'CANNOT_SEARCH_DB_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}

		return $terms;
	}

	/**
	 * Incoming search request for general search field.
	 *
	 * @param $data -> string to search for
	 * @param $section -> section
	 * @param false $regex -> as regex
	 *
	 * @return array
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	public function searchString( $data, $section, $regex = false )
	{
		if ( $this->dependency ) {
			return parent::searchString( $data, $section, $regex );
		}

		$db = Factory::Db();
		$sids = [];
		try {
			$query = [ 'oType'  => 'field_option',
			           'fid'    => $this->fid,
			           'sValue' => $regex || $data == '%' ? $data : "%$data%" ];
			$values = $db
				->select( 'sKey', 'spdb_language', $query )
				->loadResultArray();

			if ( count( $values ) ) {
//				foreach ( $values as $opt ) {
//					$ids = $db
//						->dselect( 'sid', 'spdb_field_option_selected', [ 'copy' => '0', 'fid' => $this->fid, 'optValue' => $opt ] )
//						->loadResultArray();
//
//					if ( is_array( $ids ) && count( $ids ) ) {
//						$sids = array_unique( array_merge( $ids, $sids ) );
//					}
//				}
				$sids = $db
					->dselect( 'sid', 'spdb_field_option_selected',
						[ 'copy' => '0', 'fid' => $this->fid, 'optValue' => $values ]
					)
					->loadResultArray();
			}
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'CANNOT_SEARCH_DB_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}

		return $sids;
	}

	/**
	 * Incoming search request for extended search field.
	 *
	 * @param array|string $data -> string/data to search for
	 * @param $section -> section
	 * @param string $phrase -> search phrase if needed
	 *
	 * @return array
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	public function searchData( $data, $section, $phrase = C::ES )
	{
		if ( $this->dependency ) {
			$path = json_decode( Sobi::Clean( Input::String( $this->nid . '_path' ) ), true );
			if ( is_array( $path ) && count( $path ) ) {
				$data = array_pop( $path );
			}
		}
		/** We need an extremely stupid workaround for that */
		if ( is_numeric( $data ) && $data == 0 ) {
			return [];
		}

		$sids = [];
		/* check if there was something to search for */
		if ( ( is_array( $data ) && count( $data ) ) || ( is_string( $data ) && strlen( $data ) ) ) {
			$db = Factory::Db();
			try {
				/* if we are searching for multiple options
				 * and the field contains 'predefined_multi_data_multi_choice'
				 * - we have to find entries matches all these options */
				if ( is_array( $data ) && $this->multi ) {
					$results = [];
					foreach ( $data as $opt ) {
						$db->select( 'sid', 'spdb_field_option_selected', [ 'copy' => '0', 'fid' => $this->fid, 'optValue' => $opt ] );
						if ( !count( $results ) ) {
							$results = $db->loadResultArray();
						}
						else {
							$cids = $db->loadResultArray();
							if ( $this->searchOperator === 'and' ) {
								$results = array_intersect( $results, $cids );
							}
							else {
								$results = array_merge( $results, $cids );
							}
						}
					}
					$sids = $results;
				}
				else {
					$sids = $db
						->select( 'sid', 'spdb_field_option_selected', [ 'copy' => '0', 'fid' => $this->fid, 'optValue' => $data ] )
						->loadResultArray();
				}
			}
			catch ( Sobi\Error\Exception $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'CANNOT_SEARCH_DB_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
			}
		}

		return $sids;
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function getSelectLabel()
	{
		$data = SPLang::getValue( $this->nid . '-select-label', 'field_' . $this->fieldType, Sobi::Section(), C::ES, C::ES, $this->fid );

		/* get it with old wrong type; remove in SobiPro 3.0 */
		if ( !$data ) {
			$data = SPLang::getValue( $this->nid . '-select-label', 'field_select', Sobi::Section(), C::ES, C::ES, $this->fid );
		}

		if ( $data ) {
			$this->selectLabel = $data;
		}
	}

	/**
	 * @param $attr
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function saveSelectLabel( &$attr )
	{
		$data = [
			'key'     => $this->nid . '-select-label',
			'value'   => array_key_exists( 'selectLabel', $attr ) ? $attr[ 'selectLabel' ] : $this->selectLabel,
			'type'    => 'field_' . $this->fieldType,
			'fid'     => $this->fid,
			'id'      => $this->section,
			'section' => $this->section,
		];
		SPLang::saveValues( $data );
	}

	/**
	 * @param $revision
	 * @param $current
	 *
	 * @return array
	 */
	public function compareRevisions( $revision, $current )
	{
		$cur = $current;
		$rev = $revision;
		if ( is_array( $revision ) || is_array( $current ) ) {
			if ( is_array( $current ) ) {
				//ksort( $current );
				$cur = implode( "\n", ( $current ) );
			}
			if ( is_array( $revision ) ) {
				//ksort( $revision );
				$rev = implode( "\n", ( $revision ) );
			}

		}

		return [ 'current' => $cur, 'revision' => $rev ];
	}

	/**
	 * Returns the formatted data from raw data.
	 *
	 * @return array|string
	 */
	public function getData( $data = null )
	{
		if ( !$data ) {
			$data = $this->getRaw();
		}
		if ( !$this->multi ) {
			if ( $data ) {
				$rawdata[ $data ] = $this->optionsById[ $data ][ 'label' ];
				$data = $rawdata;
			}
		}

		return $data;
	}

	/**
	 * @param $argument
	 *
	 * @return array|false|mixed
	 */
	protected function getLabelText( $argument )
	{
		if ( defined( 'SOBIPRO_ADM' ) && strtolower( $this->selectLabel ) == $this->selectLabel ) {
			return call_user_func_array( [ 'SPLang', '_' ], [ $this->selectLabel, $argument ] );
		}
		else {
			return Sobi::Txt( $this->selectLabel, $argument );
		}
	}

	/**
	 * @param string $select
	 * @param string $html
	 * @param string $class
	 * @param string $datacontent
	 *
	 * @return string
	 * @throws \SPException
	 */
	protected function getHtml( string $select, string $html = C::ES, string $class = 'sp-field-select', string $datacontent = C::ES ): string
	{
		$fw = defined( 'SOBIPRO_ADM' ) ? C::BOOTSTRAP5 : Sobi::Cfg( 'template.framework-style', C::BOOTSTRAP5 );
		$suffix = C::ES;

		if ( $fw == C::BOOTSTRAP5 ) {
			if ( $this->suffix ) {
				$suffix = "<span class=\"input-group-text\">$this->suffix</span>";
			}
			$html .= "<div class=\"input-group $class\" $datacontent>$select $suffix</div>";
		}
		elseif ( $fw == C::BOOTSTRAP4 ) {
			if ( $this->suffix ) {
				$suffix = "<div class='input-group-append'><span class=\"input-group-text\">$this->suffix</span></div>";
			}
			$html .= "<div class=\"input-group $class\" $datacontent>$select $suffix</div>";
		}
		elseif ( $fw == C::BOOTSTRAP3 ) {
			if ( $this->suffix ) {
				$suffix = "<div class='input-group-addon'><span>$this->suffix</span></div>";
				$html .= "<div class=\"input-group $class\" $datacontent>$select $suffix</div>";
			}
			else {
				$html .= "<div class=\"$class\" $datacontent>$select</div>";
			}
		}
		else {  /* Bootstrap 2 */
			if ( $this->suffix ) {
				$suffix = "<span class='add-on'><span>$this->suffix</span></span>";
			}
			$html .= $this->suffix ? "<div class=\"input-append $class\" $datacontent>$select $suffix</div>" : "<div class=\"$class\" $datacontent>$select</div>";
		}

		return $html;
	}
}
