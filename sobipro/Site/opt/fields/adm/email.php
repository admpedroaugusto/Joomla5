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
 * @created 15-Jan-2009 by Radek Suski
 * @modified 02 August 2022 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadClass( 'opt.fields.email' );

/**
 * Class SPField_EmailAdm
 */
class SPField_EmailAdm extends SPField_Email
{
	/**
	 * @param $attr
	 */
	public function save( &$attr )
	{
		if ( $this->nid ) {
			$this->nid = $attr[ 'nid' ];
		}

		/* add the field specific attributes as param to the general attributes. */
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
		if ( !isset( $attr[ 'labelsLabel' ] ) ) {
			$attr[ 'labelsLabel' ] = $this->labelsLabel;
		}
		$data = [
			'key'     => $this->nid . '-labels-label',
			'value'   => $attr[ 'labelsLabel' ],
			'type'    => 'field_' . $this->fieldType,
			'fid'     => $this->fid,
			'id'      => $attr[ 'section' ],
			'section' => $attr[ 'section' ],
		];
		SPLang::saveValues( $data );
	}
}