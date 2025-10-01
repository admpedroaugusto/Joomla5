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
 * @created 09-Sep-2009 by Radek Suski
 * @modified 19 June 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadClass( 'opt.fields.fieldtype' );

use Sobi\C;
use Sobi\Input\Input;
use Sobi\Lib\Factory;
use Sobi\Utils\Encryption;
use Sobi\Utils\StringUtils;

/**
 * Class SPField_Inbox
 */
class SPField_Inbox extends SPFieldType implements SPFieldInterface
{
	/* add here properties which are different from their initial value (model or derived class)
	   and properties valid only for this class. */

	/* properties with different value */
	/** @var int */
	protected $maxLength = 150;
	/** @var int */
	protected $bsWidth = 6;
	/** @var int */
	protected $bsSearchWidth = 6;
	/** @var string */
	protected $cssClass = 'spClassInbox';
	/** @var string */
	protected $cssClassView = 'spClassViewInbox';
	/** @var string */
	protected $cssClassEdit = 'spClassEditInbox';
	/** @var string */
	protected $cssClassSearch = 'spClassSearchInbox';

	/* properties for this and derived classes */
	/** @var string */
	protected $placeholder = ' ';
	/** @var bool */
	protected $labelAsPlaceholder = false;
	/*** @var bool */
	protected $encryptData = false;
	/** @var bool */
	protected $floating = false;

	/* properties only for this class */
	/** @var string */
	protected $searchRangeValues = C::ES;
	/** @var bool */
	protected $freeRange = false;
	/** @var bool */
	protected $untranslatable = false;
	/** @var bool */
	protected $numeric = false;

	/** @var bool */
	private static $CAT_FIELD = true;

	/**
	 * SPField_Inbox constructor. Get language dependant settings.
	 *
	 * @param $field
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function __construct( &$field )
	{
		parent::__construct( $field );

		$this->placeholder = SPLang::getValue( $this->nid . '-placeholder',
			'field_' . $this->fieldType,
			Sobi::Section(),
			C::ES,
			C::ES,
			$this->fid
		);
	}

	/**
	 * Returns the parameter list (params). All properties not set in the model but used in the xml file of the field.
	 * No language dependant values, no values from the model (columns in the database).
	 *
	 * @return array
	 */
	protected function getAttr(): array
	{
		return [
			'searchMethod', 'itemprop', 'helpposition', 'suggesting', 'showEditLabel', 'metaSeparator', 'maxLength', 'cssClassView', 'cssClassSearch', 'cssClassEdit', 'bsWidth', 'bsSearchWidth', 'labelAsPlaceholder', 'encryptData', 'floating', 'searchRangeValues', 'freeRange', 'untranslatable', 'numeric',
		];
	}

	/**
	 * Shows the field in the edit entry or add entry form.
	 *
	 * @param false $return
	 *
	 * @return string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function field( $return = false )
	{
		if ( $this->enabled ) {
			$framework = defined( 'SOBIPRO_ADM' ) ? C::BOOTSTRAP5 : Sobi::Cfg( 'template.framework-style', C::BOOTSTRAP5 );

			$class = defined( 'SOBIPRO_ADM' ) ? 'spClassInbox' : $this->cssClass;
			$class = $this->required ? $class . ' required' : $class;
			$class .= $framework == C::BOOTSTRAP2 ? ' w-100' : C::ES;
			$class .= $framework == C::BOOTSTRAP2 && $this->suffix ? ' suffix' : C::ES;

			$params = [ 'id' => $this->nid, 'class' => $class ];

			if ( $this->maxLength ) {
				$params[ 'maxlength' ] = $this->maxLength;
			}
			$placeholder = C::ES;
			if ( $this->labelAsPlaceholder ) {  /* show placeholder */
				$placeholder = $this->placeholder ? : $this->__get( 'name' );
				$params[ 'placeholder' ] = $placeholder;
			}
			$params[ 'aria-label' ] = $this->placeholder ? : $this->__get( 'name' );

			$value = (string) $this->getRaw();
			$value = strlen( $value ) ? $value : $this->defaultValue;

			/* set if element contains data */
			$datacontent = $value ? ' data-sp-content="1"' : C::ES;

			$groupstart = $groupend = $suffix = C::ES;
			$label = C::ES;
			switch ( $framework ) {
				case C::BOOTSTRAP5:
					$suffix = "<span class=\"input-group-text\">$this->suffix</span>";
					$groupstart = '<div class="sp-field-inbox input-group"' . $datacontent . '>';
					$groupend = '</div>';
					if ( $this->floating && !$this->suffix ) {
						$groupstart = '<div class="form-floating sp-field-inbox">';  /* suffix and floating labels do not work together as of BS5 Beta2*/
						$label = "<label for=\"$this->nid\">" . $placeholder . '</label>';
						$params[ 'placeholder' ] = '';  /* we always need that for floating labels */
					}
					break;
				case C::BOOTSTRAP4:
					$suffix = "<div class='input-group-append'><span class=\"input-group-text\">$this->suffix</span></div>";
					$groupstart = '<div class="sp-field-inbox input-group"' . $datacontent . '>';
					$groupend = '</div>';
					break;
				case C::BOOTSTRAP3:
					$suffix = "<div class='input-group-addon'><span>$this->suffix</span></div>";
					if ( $this->suffix ) {
						$groupstart = '<div class="sp-field-inbox input-group"' . $datacontent . '>';
						$groupend = '</div>';
					}
					break;
				case  C::BOOTSTRAP2:  /* Bootstrap 2 */
					$suffix = "<span class='add-on'><span>$this->suffix</span></span>";
					if ( $this->suffix ) {
						$groupstart = '<div class="sp-field-inbox input-append"' . $datacontent . '>';
						$groupend = '</div>';
					}
					break;
			}

			/* Construct the HTML */
			$html = $groupstart;
			$html .= SPHtml_Input::text( $this->nid, $value, $params );
			$html .= $label;
			if ( $this->suffix ) {
				$html .= $suffix;
			}
			$html .= $groupend;

			if ( !$return ) {
				echo $html;
			}
			else {
				return $html;
			}
		}

		return C::ES;
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
		if ( strlen( $data ) ) {
			$return[ $this->nid ] = $data;
			//return Input::Search( $this->nid );
		}

		return $return;
	}

	/**
	 * Verifies the data and returns them.
	 *
	 * @param $entry
	 * @param $request
	 *
	 * @return string
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function verify( $entry, $request )
	{
		$data = Input::Html( $this->nid, 'post' );
		$dexs = strlen( $data );

		/* check if it was required */
		if ( $this->required && !$dexs ) {
			throw new SPException( SPLang::e( 'FIELD_REQUIRED_ERR', $this->name ) );
		}

		if ( $dexs ) {
			/* check if there was a filter */
			if ( $this->filter ) {
				$filters = SPFactory::filter()->getFilters();
				$filter = $filters[ $this->filter ] ?? [];
				if ( !count( $filter ) ) {
					throw new SPException( SPLang::e( 'FIELD_FILTER_ERR', $this->filter ) );
				}
				else {
					if ( $this->filter == 'email' ) {
						$data = trim( $data );
					}
					if ( !preg_match( base64_decode( $filter[ 'params' ] ), $data ) ) {
						throw new SPException( str_replace( '$field', $this->name, SPLang::e( $filter[ 'message' ] ) ) );
					}
				}
			}
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
			/* check if it should contains unique data */
			if ( $this->uniqueData ) {
				$matches = $this->searchData( $data, Sobi::Reg( 'current_section' ), C::ES );
				if ( count( $matches ) > 1 || ( ( count( $matches ) == 1 ) && ( $matches[ 0 ] != $entry->get( 'id' ) ) ) ) {
					throw new SPException( SPLang::e( 'FIELD_NOT_UNIQUE', $this->name ) );
				}
			}
			/* check if it was editLimit */
			if ( $this->editLimit == 0 && !Sobi::Can( 'entry.adm_fields.edit' ) ) {
				throw new SPException( SPLang::e( 'FIELD_NOT_AUTH_EXP', $this->name ) );
			}
			/* check if it was editable */
			if ( !$this->editable && !Sobi::Can( 'entry.adm_fields.edit' ) && $entry->get( 'version' ) > 1 ) {
				throw new SPException( SPLang::e( 'FIELD_NOT_AUTH_NOT_ED', $this->name ) );
			}
			$this->setData( $data );
		}

		return $data;
	}

	/**
	 * Gets the data for this field and verifies them the first time.
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
		$this->verify( $entry, $request );
	}

	/**
	 * Gets the data for a field and save it in the database.
	 *
	 * @param SPEntry $entry
	 * @param string $request
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function saveData( &$entry, $request = 'POST' )
	{
		if ( $this->enabled ) {
			$data = $this->verify( $entry, $request );

			/* if we are here, we can save these data */
			$db = Factory::Db();
			$data = $db->escape( trim( strip_tags( $data ) ) );
			if ( $this->encryptData ) {
				$data = 'encrypted://' . Encryption::Encrypt( $data, Sobi::Cfg( 'encryption.key' ) );
			}
			$time = Input::Now();
			$ipAddress = Input::Ip4( 'REMOTE_ADDR' );
			$uid = Sobi::My( 'id' );


			/* collect the needed params */
			$params = [];
			$params[ 'publishUp' ] = $entry->get( 'publishUp' ) ?? $db->getNullDate();
			$params[ 'publishDown' ] = $entry->get( 'publishDown' ) ?? $db->getNullDate();
			$params[ 'fid' ] = $this->fid;
			$params[ 'sid' ] = $entry->get( 'id' );
			$params[ 'section' ] = Sobi::Section();
			$params[ 'lang' ] = Sobi::Lang();
			$params[ 'enabled' ] = $entry->get( 'state' );
			$params[ 'params' ] = C::ES;
			$params[ 'options' ] = null;
			$params[ 'baseData' ] = $data;
			$params[ 'approved' ] = $entry->get( 'approved' );
			$params[ 'confirmed' ] = $entry->get( 'confirmed' );
			/* if it is the first version, it is new entry */
			if ( $entry->get( 'version' ) == 1 ) {
				$params[ 'createdTime' ] = $time;
				$params[ 'createdBy' ] = $uid;
				$params[ 'createdIP' ] = $ipAddress;
			}
			$params[ 'updatedTime' ] = $time;
			$params[ 'updatedBy' ] = $uid;
			$params[ 'updatedIP' ] = $ipAddress;
			$params[ 'copy' ] = (int) !$entry->get( 'approved' );

			$this->setEditLimit( $entry, $params[ 'baseData' ] );
			$params[ 'editLimit' ] = $this->editLimit;

			/* save it to the database */
			$this->saveToDatabase( $params, $entry->get( 'version' ), $this->untranslatable ? : false );
		}
	}

	/**
	 * Shows the field in the search form.
	 *
	 * @param false $return -> return or display directly
	 *
	 * @return string
	 * @throws \Sobi\Error\Exception|\SPException
	 */
	public function searchForm( $return = false )
	{
		if ( $this->searchMethod == 'general' || $this->searchMethod == C::ES ) {
			return C::ES;
		}
		if ( $this->searchMethod == 'range' ) {
			return $this->rangeSearch( $this->searchRangeValues, $this->freeRange );
		}
		if ( $this->searchMethod == 'inbox' ) {
			$label = C::ES;
			$suffix = "<span class=\"input-group-text\">$this->suffix</span>";
			$groupstart = '<div class="sp-field-inbox input-group">';
			$groupend = '</div>';
			if ( !$this->suffix ) {
				$groupstart = '<div class="sp-field-inbox">';
				$label = "<label for=\"$this->nid\"></label>";
			}

			/* Construct the HTML */
			$html = $groupstart;
			$html .= SPHtml_Input::text( $this->nid, $this->_selected, [ 'class' => $this->cssClass . ' ' . Sobi::Cfg( 'search.inbox_def_css', 'sp-search-inbox' ) ] );
			$html .= $label;
			if ( $this->suffix ) {
				$html .= $suffix;
			}
			$html .= $groupend;

			return $html;
		}

		/* search field type = select list */
		$fdata = [];
		$languages = $output = [];
		try {
			$data = Factory::Db()
				->dselect( [ 'baseData', 'sid', 'lang' ], 'spdb_field_data', [ 'fid' => $this->fid, 'copy' => '0', 'enabled' => 1 ], 'field( lang, \'' . Sobi::Lang() . '\'), baseData', 0, 0, 'baseData' )
				->loadAssocList();

			$lang = Sobi::Lang( false );
			$defLang = Sobi::DefLang();
			if ( count( $data ) ) {
				foreach ( $data as $row ) {
					$languages[ $row[ 'lang' ] ][ $row[ 'sid' ] ] = $row[ 'baseData' ];
				}
			}
			if ( isset( $languages[ $lang ] ) ) {
				foreach ( $languages[ $lang ] as $sid => $fieldData ) {
					$output[ $sid ] = $fieldData;
				}
				unset( $languages[ $lang ] );
			}
			if ( isset( $languages[ $defLang ] ) ) {
				foreach ( $languages[ $defLang ] as $sid => $fieldData ) {
					if ( !isset( $output[ $sid ] ) ) {
						$output[ $sid ] = $fieldData;
					}
				}
				unset( $languages[ $defLang ] );
			}
			if ( count( $languages ) ) {
				foreach ( $languages as $language => $langData ) {
					foreach ( $langData as $sid => $fieldData ) {
						if ( !isset( $output[ $sid ] ) ) {
							$output[ $sid ] = $fieldData;
						}
					}
					unset( $languages[ $language ] );
				}
			}
		}
		catch ( SPException $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'CANNOT_GET_FIELDS_DATA_DB_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}

		$data = ( ( array ) $output );
		if ( count( $data ) ) {
			$fdata[ '' ] = Sobi::Txt( 'FD.INBOX_SEARCH_SELECT', [ 'name' => $this->name ] );
			foreach ( $data as $index => $d ) {
				if ( strlen( $d ) ) {
					$fdata[ strip_tags( $d ) ] = strip_tags( $d );
				}
			}
		}
		if ( function_exists( 'iconv' ) ) {
			uasort( $fdata, function ( $a, $b ) {
				return strcmp( iconv( 'UTF-8', 'ASCII//TRANSLIT', strtolower( $a ) ), iconv( 'UTF-8', 'ASCII//TRANSLIT', strtolower( $b ) ) );
			}
			);
		}
		else {
			asort( $fdata );
		}

		return SPHtml_Input::select( $this->nid,
			$fdata,
			$this->_selected,
			false,
			[ 'class' => $this->cssClass . ' ' . Sobi::Cfg( 'search.select_def_css', 'sp-search-select' ),
			  'size'    => '1',
			  'id'      => $this->nid,
			  'numeric' => $this->numeric ]
		);
	}

	/**
	 * @param $data
	 * @param $section
	 * @param bool $startWith
	 * @param bool $ids
	 *
	 * @return array
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	public function searchSuggest( $data, $section, $startWith = true, $ids = false )
	{
		$terms = [];
		$data = StringUtils::Clean( $data );
		$data = $startWith ? "$data%" : "%$data%";
		$request = [ 'baseData' ];
		if ( $ids ) {
			$request[] = 'sid';
		}
		try {
			$db = Factory::Db();
			if ( $ids ) {
				$conditions = [ 'fid' => $this->fid, 'baseData' => $data, 'section' => $section ];
				if ( !( defined( 'SOBIPRO_ADM' ) ) ) {
					$conditions[ 'copy' ] = 0;
					$conditions[ 'enabled' ] = 1;
				}
				$result = $db
					->dselect( $request, 'spdb_field_data', $conditions )
					->loadAssocList();
				if ( count( $result ) ) {
					foreach ( $result as $row ) {
						$terms[] = [ 'id' => $row[ 'sid' ], 'name' => StringUtils::Clean( $row[ 'baseData' ] ) ];
					}
				}
			}
			else {
				$terms = $db
					->select( $request, 'spdb_field_data', [ 'fid' => $this->fid, 'copy' => '0', 'enabled' => 1, 'baseData' => $data, 'section' => $section ], 'baseData' )
					->loadResultArray();
			}
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'CANNOT_SEARCH_DB_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}

		return $terms;
	}

	/**
	 * Performs the search on the field.
	 *
	 * @param $data -> search data
	 * @param $section -> section
	 *
	 * @return array
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function search( $data, $section ): array
	{
		$sids = [];
		try {
			if ( Sobi::Cfg( 'search.fulltext', false ) ) {
				$sids = Factory::Db()
					->selectFullText( 'sid', 'spdb_field_data',
						[ 'fid'     => $this->fid,
						  'copy'    => '0',
						  'section' => $section ],
						'baseData',
						$data,
					)
					->loadResultArray();
			}
			else {
				$sids = Factory::Db()
					->dselect( 'sid', 'spdb_field_data',
						[ 'fid'      => $this->fid,
						  'copy'     => '0',
						  'baseData' => $data,
						  'section'  => $section ]
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
		return $this->search( ( $regex || $data == '%' ? $data : "%$data%" ), $section );
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
		if ( is_array( $data ) ) {   /* array -> Range Search */
			/* both set to 0 -> not selected */
			if ( ( isset( $data[ 'from' ] ) || isset( $data[ 'to' ] ) ) && ( $data[ 'from' ] || $data[ 'to' ] ) ) {
				return $this->searchForRange( $data, $section );
			}

			return [];
		}
		else {
			if ( $this->searchMethod == 'inbox' ) {
				return $this->search( $data == ' % ' ? ' % ' : "%$data%", $section );
			}
			else {
				$req = preg_quote( $data );

				return $this->search( "REGEXP:^$req$", $section );
			}
		}
	}
}
