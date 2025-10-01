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
SPLoader::loadClass( 'opt.fields.inbox' );

use AthosHun\HTMLFilter\Configuration;
use AthosHun\HTMLFilter\HTMLFilter;
use Sobi\C;
use Sobi\Input\Input;
use Sobi\Utils\Encryption;
use Sobi\Lib\Factory;

/**
 * Class SPField_Textarea
 */
class SPField_Textarea extends SPField_Inbox implements SPFieldInterface
{
	/* add here properties which are different from their initial value (model or derived class)
	   and properties valid only for this class. */

	/* properties with different value */
	/** @var int */
	protected $maxLength = 0;
	/** @var int */
	protected $height = 100;
	/** @var string */
	protected $cssClass = 'spClassText';
	/** @var string */
	protected $cssClassView = 'spClassViewText';
	/** @var string */
	protected $cssClassEdit = 'spClassEditText';
	/** @var string */
	protected $cssClassSearch = 'spClassSearchText';
	/*** @var bool */
	protected $suggesting = false;
	/** @var bool */
	protected $untranslatable = false;

	/* properties only for this class */
	/** @var bool */
	protected $allowHtml = 2;

	/** @var bool */
	private static $CAT_FIELD = true;

	/**
	 * Returns the parameter list (params). All properties not set in the model but used in the xml file of the field.
	 * No language dependant values, no values from the model (columns in the database).
	 *
	 * @return array
	 */
	protected function getAttr(): array
	{
		return [ 'searchMethod', 'itemprop', 'helpposition', 'metaSeparator', 'maxLength', 'height', 'cssClassView', 'cssClassSearch', 'cssClassEdit', 'showEditLabel', 'labelAsPlaceholder', 'bsWidth', 'encryptData', 'floating', 'allowHtml', 'untranslatable' ];
	}

	/**
	 * @return array -> all properties which are not in the XML file but its default value needs to be set
	 */
	protected function getDefaults()
	{
		$attr[ 'suggesting' ] = $this->suggesting;
		$attr[ 'searchMethod' ] = $this->searchMethod;

		return $attr;
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
			$this->suffix = C::ES;

			$fw = defined( 'SOBIPRO_ADM' ) ? C::BOOTSTRAP5 : Sobi::Cfg( 'template.framework-style', C::BOOTSTRAP5 );

			$class = defined( 'SOBIPRO_ADM' ) ? 'spClassText' : $this->cssClass;
			$class = $this->required ? $class . ' required' : $class;
			if ( $fw == C::BOOTSTRAP2 ) {
				$class .= ' w-100';
			}
			$params = [ 'id' => $this->nid, 'class' => $class ];

			if ( $this->maxLength ) {
				$params[ 'maxlength' ] = $this->maxLength;
			}
			$this->height = ( $this->height ) ? : 100;
			$placeholder = C::ES;
			if ( $this->labelAsPlaceholder ) {  /* show placeholder */
				$placeholder = $this->placeholder ? : $this->__get( 'name' );
				$params[ 'placeholder' ] = $placeholder;
			}
			$params[ 'aria-label' ] = $this->placeholder ? : $this->__get( 'name' );

			//$value = (string) $this->getRaw();
			$value = (string) $this->data();
			$value = strlen( $value ) ? $value : $this->defaultValue;

			$fw = defined( 'SOBIPRO_ADM' ) ? C::BOOTSTRAP5 : Sobi::Cfg( 'template.framework-style', C::BOOTSTRAP5 );

			$this->editor = $this->editor == "1" || (bool) $this->editor;
			$gs = '<div class="sp-field-text' . ( $this->required ? ' required' : C::ES ) . ( $this->editor ? ' wysiwyg' : C::ES ) . '"' . ( $value ? ' data-sp-content="1"' : C::ES ) . '>';
			$ge = '</div>';
			$label = C::ES;

			switch ( $fw ) {
				case C::BOOTSTRAP5:
					if ( $this->floating && !$this->editor ) {
						$gs = '<div class="sp-field-text form-floating' . ( $this->required ? ' required' : C::ES ) . '">';
						$label = "<label for=\"$this->nid\">" . $placeholder . '</label>';
						$params[ 'placeholder' ] = C::ES; /* we always need that for floating labels */
					}
					break;
				case C::BOOTSTRAP4:
				case C::BOOTSTRAP3:
				case C::BOOTSTRAP2:
					break;
			}

			/* Construct the HTML */
			$html = $gs;
			/* textarea width set to 100% if WYSIWYG is used */
			$html .= SPHtml_Input::textarea( $this->nid, $value, $this->editor, '100%', $this->height, $params );
			$html .= $label;
			$html .= $ge;

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
	 * @return array
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	public function struct()
	{
		$data = (string) $this->data();
		$attributes = [];
		if ( strlen( $data ) ) {
			$this->cssClass = strlen( $this->cssClass ) ? $this->cssClass : 'sp-field-data';
			$this->cssClass = $this->cssClass . ' ' . $this->nid;
			$this->cleanCss();
			$attributes = [
				'lang'  => Sobi::Lang(),
				'class' => $this->cssClass,
			];
		}
		else {
			$this->cssClass = strlen( $this->cssClass ) ? $this->cssClass : 'sp-field';
		}
		if ( !( $this->editor || $this->allowHtml ) ) {
			$data = nl2br( $data );
		}

		return [
			'_complex'    => 1,
			'_data'       => $data,
			'_attributes' => $attributes,
		];
	}

	/**
	 * Verifies the data and returns them.
	 *
	 * @param $entry
	 * @param $request
	 *
	 * @return string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function verify( $entry, $request )
	{
		$data = (string) Input::Raw( $this->nid );
		$dexs = strlen( $data );
		/* check if it was required */
		if ( $this->required && !$dexs ) {
			throw new SPException( SPLang::e( 'FIELD_REQUIRED_ERR', $this->name ) );
		}
		if ( $dexs ) {
			/* check if there was an adminField */
			if ( $this->adminField ) {
				if ( !Sobi:: Can( 'entry.adm_fields.edit' ) ) {
					throw new SPException( SPLang::e( 'FIELD_NOT_AUTH', $this->get( 'name' ) ) );
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
			if ( $this->allowHtml ) {
				$checkMethod = function_exists( 'mb_strlen' ) ? 'mb_strlen' : 'strlen';
				$check = $checkMethod( str_replace( [ "\n", "\r", "\t" ], C::ES, strip_tags( $data ) ) );
				if ( $this->maxLength && $check > $this->maxLength ) {
					throw new SPException( SPLang::e( 'FIELD_TEXTAREA_LIMIT', $this->maxLength, $this->name, $check ) );
				}
			}
			else {
				if ( $this->maxLength && $dexs > $this->maxLength ) {
					throw new SPException( SPLang::e( 'FIELD_TEXTAREA_LIMIT', $this->maxLength, $this->name, $dexs ) );
				}
			}
		}

		return $data;
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
	 * Gets the data for a field and save it in the database.
	 *
	 * @param SPEntry $entry
	 * @param string $request
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function saveData( &$entry, $request = 'POST' )
	{
		if ( $this->enabled ) {
			$data = $this->verify( $entry, $request );
			$time = Input::Now();
			$IP = Input::Ip4();
			$uid = Sobi::My( 'id' );

			/* if we are here, we can save these data */
			$db = Factory::Db();

			if ( $this->allowHtml ) {
				$config = new Configuration();
				$filter = new HTMLFilter();
				$data = Input::Raw( $this->nid );
				if ( is_array( $this->allowedTags ) && count( $this->allowedTags ) ) {
					foreach ( $this->allowedTags as $tag ) {
						if ( $tag ) {
							$config->allowTag( $tag );
							if ( is_array( $this->allowedAttributes ) && count( $this->allowedAttributes ) ) {
								foreach ( $this->allowedAttributes as $attribute ) {
									$config->allowAttribute( $tag, $attribute );
								}
							}
						}
					}
				}
				if ( $this->allowHtml == 2 ) {    // do not filter
					$data = str_replace( '&#13;', "\n", (string) $data );
				}
				else {  //do filter
					$data = str_replace( '&#13;', "\n", $filter->filter( $config, $data ) );
				}
				$data = str_replace( "\n\n", "\n", $data );
				if ( !$this->editor && $this->maxLength && ( strlen( $data ) > $this->maxLength ) ) {
					$data = substr( $data, 0, $this->maxLength );
				}
			}
			else {  // no HTML tags
				$data = strip_tags( $data );
			}

			$this->setData( $data );

			if ( $this->encryptData ) {
				$data = 'encrypted://' . Encryption::Encrypt( $data, Sobi::Cfg( 'encryption.key' ) );
			}

			/* collect the needed params */
			$params = [];
			$params[ 'publishUp' ] = $entry->get( 'publishUp' ) ?? $db->getNullDate();
			$params[ 'publishDown' ] = $entry->get( 'publishDown' ) ?? $db->getNullDate();
			$params[ 'fid' ] = $this->fid;
			$params[ 'sid' ] = $entry->get( 'id' );
			$params[ 'section' ] = Sobi::Reg( 'current_section' );
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
				$params[ 'createdIP' ] = $IP;
			}
			$params[ 'updatedTime' ] = $time;
			$params[ 'updatedBy' ] = $uid;
			$params[ 'updatedIP' ] = $IP;
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
	 * @param false $return
	 *
	 * @return string
	 * @throws \SPException
	 */
	public function searchForm( $return = false )
	{
//		if ( $this->searchMethod == 'general' || $this->searchMethod == C::ES ) {
//			return C::ES;
//		}
		if ( $this->searchMethod == 'inbox' ) {
			return SPHtml_Input::text( $this->nid, $this->_selected,
				[ 'class' => $this->cssClass . ' ' . Sobi::Cfg( 'search.inbox_def_css', 'sp-search-inbox' ) ]
			);
		}

		return C::ES;
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
		if ( $this->searchMethod == 'inbox' ) {
			return $this->search( $data == '%' ? '%' : "%$data%", $section );
		}
		else {
			return [];
		}
	}
}
