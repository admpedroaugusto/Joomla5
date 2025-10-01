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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
 *
 * @created 12-Sep-2009 by Radek Suski
 * @modified 02 August 2022 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadClass( 'opt.fields.textarea' );

/**
 * Class SPField_TextareaAdm
 */
class SPField_TextareaAdm extends SPField_Textarea
{
	/**
	 * @param $attr
	 */
	public function save( &$attr )
	{
		if ( $this->nid ) {
			$this->nid = $attr[ 'nid' ];
		}
		if ( $attr[ 'editor' ] ) {
			$attr[ 'floating' ] = false;
			$attr[ 'labelAsPlaceholder' ] = false;
		}
		/* generate the params (field specific parameters) */
		parent::save( $attr );
	}

	/**
	 * Saves the field specific data for a new or duplicated field.
	 *
	 * @param $attr
	 * @param int $fid
	 */
	public function saveNew( &$attr, $fid = 0 )
	{
		if ( $fid ) {
			$this->id = $this->fid = $fid;
		}
		$this->save( $attr );
	}

	/**
	 * @param $attr
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function saveLanguageData( $attr ): void
	{
		/* generate and save the language specific field parameters */
		if ( !isset( $attr[ 'placeholder' ] ) ) {
			$attr[ 'placeholder' ] = $this->placeholder;
		}
		$data = [
			'key'     => $this->nid . '-placeholder',
			'value'   => $attr[ 'placeholder' ],
			'type'    => 'field_' . $this->fieldType,
			'fid'     => $this->fid,
			'id'      => $attr[ 'section' ],
			'section' => $attr[ 'section' ],
		];
		SPLang::saveValues( $data );
	}
}