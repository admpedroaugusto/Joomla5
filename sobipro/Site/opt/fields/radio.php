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
 * @created 26-Nov-2009 by Radek Suski
 * @modified 19 June 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadClass( 'opt.fields.select' );

use Sobi\C;

/**
 * Class SPField_Radio
 */
class SPField_Radio extends SPField_Select implements SPFieldInterface
{
	/* add here properties which are different from their initial value (model or derived class)
	   and properties valid only for this class. */

	/* properties with different value */
	/** @var string */
	protected $dType = 'predefined_multi_data_single_choice';
	/** @var int */
	protected $bsWidth = 10;
	/** @var int */
	protected $bsSearchWidth = 9;
	/** @var string */
	protected $cssClass = 'spClassRadio';
	/** @var string */
	protected $cssClassView = 'spClassViewRadio';
	/** @var string */
	protected $cssClassEdit = 'spClassEditRadio';
	/** @var string */
	protected $cssClassSearch = 'spClassSearchRadio';

	/* properties for this and derived classes */
	/** @var int */
	protected $ssize = 1;
	/** @var string */
	protected $defSel = C::ES; /* remove in SobiPro 3.0. It is defaultValue*/

	/* properties only for this class */
	/** @var int */
	protected $optInLine = true;
	/** @var int */
	protected $optWidth = 150;

	/** @var bool */
	private static $CAT_FIELD = true;

	/**
	 * SPField_Radio constructor. Get language dependant settings.
	 *
	 * @param $field
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function __construct( &$field )
	{
		parent::__construct( $field );

		/* get it from old property defSel; remove in SobiPro 3.0 */
		if ( $this->defSel ) {
			$this->defaultValue = $this->defSel;
			$this->defSel = C::ES;
		}
	}

	/**
	 * Returns the parameter list (params). All properties not set in the model but used in the xml file of the field.
	 * No language dependant values, no values from the model (columns in the database).
	 *
	 * @return array
	 */
	protected function getAttr(): array
	{
		return [ 'searchMethod', 'itemprop', 'helpposition', 'suggesting', 'showEditLabel', 'metaSeparator', 'cssClassView', 'cssClassSearch', 'cssClassEdit', 'bsWidth', 'bsSearchWidth', 'ssize', 'optInLine', 'optWidth', 'defSel' ];    /* remove 'defSel' in SobiPro 3.0 */
	}

	/**
	 * Shows the field in the edit entry or add entry form.
	 *
	 * @param false $return
	 *
	 * @return string
	 */
	public function field( $return = false )
	{
		if ( $this->enabled ) {
			$class = defined( 'SOBIPRO_ADM' ) ? 'spClassRadio' : $this->cssClass;
			$class = $this->required ? $class . ' required' : $class;
			$html = $this->getField( $class );

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
	 * @param string $class
	 * @param mixed $selected
	 *
	 * @return string
	 */
	private function getField( string $class, $selected = C::ES ): string
	{
		$params = [ 'class' => $class ];
		if ( $this->optWidth ) {
			$params[ 'style' ] = "width:{$this->optWidth}px;";
		}

		$selected = $selected ? : $this->getRaw();
		if ( ( $selected == null ) && ( $this->defaultValue ) ) {
			$selected = $this->defaultValue;
		}
		$dc = $selected ? 'data-sp-content="1"' : C::ES;

		$appearance = $this->optInLine ? 'inline' : 'block';
		$values = $this->getValues();

		$fieldlist = C::ES;
		$list = SPHtml_Input::radioList( $this->nid, $values, $this->nid, $selected, $params, $appearance, true );
		if ( is_array( $list ) && count( $list ) ) {
			$fieldlist = implode( C::ES, $list );
			$fieldlist = "<div id=\"$this->nid\" class=\"sp-field-radio spctrl-validategroup\" $dc>$fieldlist</div>";
		}

		$field = C::ES;
		$field .= $fieldlist;

		return $field;
	}

	/**
	 * @return array
	 */
	private function getValues(): array
	{
		$values = [];
		if ( count( $this->options ) ) {
			foreach ( $this->options as $option ) {
				$values[ $option[ 'id' ] ] = $option[ 'label' ] . ( strlen( $this->suffix ) ? ' ' . $this->suffix : C::ES );
			}
		}

		return $values;
	}

	/**
	 * Shows the field in the search form.
	 *
	 * @param false $return
	 *
	 * @return string
	 */
	public function searchForm( $return = false )
	{
		$fw = defined( 'SOBIPRO_ADM' ) ? C::BOOTSTRAP5 : Sobi::Cfg( 'template.framework-style', C::BOOTSTRAP5 );

		$html = C::ES;
		$data = $this->getValues();
		switch ( $this->searchMethod ) {
			default:
			case 'general':
				break;
			case 'chbx':
			case 'switch':
				$params = [ 'class' => $this->cssClass ];
				if ( $this->optWidth ) {
					$params[ 'style' ] = "width:{$this->optWidth}px;";
				}
				$appearance = $this->optInLine ? 'inline' : 'block';
				$params[ 'switch' ] = $this->searchMethod == 'switch';
				$list = SPHtml_Input::checkBoxGroup( $this->nid, $data, $this->nid, $this->_selected, $params, $appearance, true );

				if ( is_array( $list ) && count( $list ) ) {
					$html = implode( '', $list );
					$html = "<div  id=\"$this->nid\" class=\"sp-field-checkbox\">$html</div>";
					$html .= '<div class="clearfix"></div>';
				}
				break;
			case 'radio':
				$html = $this->getField( $this->cssClass . ' ' . Sobi::Cfg( 'search.radio_def_css', 'sp-search-radio' ), $this->_selected );
				$html .= '<div class="clearfix"></div>';
				break;
			case 'select':
			case 'mselect':
				$class = $this->cssClass;
				if ( $fw == C::BOOTSTRAP2 ) {
					$class .= ' w-100';
				}
				$label = ( $this->selectLabel ) ? Sobi::Txt( $this->selectLabel, $this->name ) : Sobi::Txt( 'FMN.SEARCH_SELECT_LIST', [ 'name' => $this->name ] );
			$params = [ 'id' => $this->nid, 'size' => $this->ssize, 'class' => $class . ' ' . Sobi::Cfg( 'search.select_def_css', 'sp-search-select' ) ];
				$data = array_merge( [ '' => $label ], $data );

			$html = SPHtml_Input::select( $this->nid, $data, $this->_selected, ( $this->searchMethod == 'mselect' ), $params );
				break;
		}

		return $html;
	}
}