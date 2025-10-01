<?php
/**
 * @package: SobiPro multi-directory component with content construction support
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2022 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/GPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/gpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * @created 09-Sep-2015 by Sigrid Suski
 * @modified 05 September 2022 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadClass( 'opt.fields.inbox' );

use Sobi\C;

/**
 * Class SPField_Info
 */
class SPField_Info extends SPField_Inbox implements SPFieldInterface
{
	/* add here properties which are different from their initial value (model or derived class)
	   and properties valid only for this class. */

	/* properties with different value */
	/** @var int */
	protected $bsWidth = 10;
	/** @var string */
	protected $cssClass = 'spClassInfo';
	/** @var string */
	protected $cssClassEdit = 'spClassEditInfo';
	/** @var string */
	protected $cssClassView = 'spClassViewInfo';
	/*** @var bool */
	protected $suggesting = false;

	/* properties only for this class */
	/** @var string */
	protected $viewInfo = '';
	/** @var string */
	protected $entryInfo = '';

	/** @var bool */
	private static $CAT_FIELD = true;
	/** @var bool */
	private static $NO_IMEX = true;

	/**
	 * SPField_Info constructor. Get language dependant settings.
	 *
	 * @param $field
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function __construct( &$field )
	{
		parent::__construct( $field );

		$this->viewInfo = SPLang::getValue( $this->nid . '-viewInfo', 'field_' . $this->fieldType, Sobi::Section(), C::ES, C::ES, $this->fid );
		$this->entryInfo = SPLang::getValue( $this->nid . '-entryInfo', 'field_' . $this->fieldType, Sobi::Section(), C::ES, C::ES, $this->fid );
	}

	/**
	 * Returns the parameter list. All properties not set in the model but used in the xml file of the field.
	 * No language dependant values.
	 *
	 * @return array
	 */
	protected function getAttr(): array
	{
		return [ 'helpposition', 'showEditLabel', 'cssClassView', 'cssClassEdit', 'bsWidth' ];
	}

	/**
	 * Returns all properties which are not in the XML file but its default value needs to be set.
	 *
	 * @return array
	 */
	protected function getDefaults()
	{
		return [ 'suggesting' => false ];
	}

	/**
	 * Shows the field in the edit entry or add entry form.
	 *
	 * @param false $return
	 *
	 * @return false|string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function field( $return = false )
	{
		if ( !$this->enabled ) {
			return C::ES;
		}
		$data = SPLang::getValue( $this->nid . '-entryInfo', 'field_' . $this->fieldType, Sobi::Section(), C::ES, C::ES, $this->fid );

		$class = $this->cssClass . ( strlen( $this->cssClassEdit ) ? ' ' . $this->cssClassEdit : '' );
		$html = '<div class="' . $class . '">' . $data . '</div>';

		if ( !$return ) {
			echo $html;
		}
		else {
			return $html;
		}
	}

	/**
	 * Shows the field in dv and vCard.
	 *
	 * @return array
	 * @throws \Sobi\Error\Exception|\SPException
	 */
	public function struct()
	{
		$data = SPLang::getValue( $this->nid . '-viewInfo', 'field_' . $this->fieldType, Sobi::Section(), C::ES, C::ES, $this->fid );

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

		return [
			'_complex'    => 1,
			'_data'       => $data,
			'_attributes' => $attributes,
		];
	}

	/**
	 * Verifies the data and returns them (here nothing to do).
	 *
	 * @param SPEntry $entry
	 * @param string $request
	 */
	protected function verify( $entry, $request )
	{
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
	 */
	public function submit( &$entry, $tsId = C::ES, $request = 'POST' )
	{
		return [];
	}

	/**
	 * Gets the data for a field and save it in the database.
	 *
	 * @param SPEntry $entry
	 * @param string $request
	 */
	public function saveData( &$entry, $request = 'POST' )
	{
	}
}
