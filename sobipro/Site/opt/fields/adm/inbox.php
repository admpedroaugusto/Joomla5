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
 * @created 09-Sep-2009 by Radek Suski
 * @modified 31 October 2022 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadClass( 'opt.fields.inbox' );

/**
 * Class SPField_InboxAdm
 */
class SPField_InboxAdm extends SPField_Inbox
{
	/**
	 * @param $attr
	 */
	public function save( &$attr )
	{
		if ( $this->nid ) {
			$this->nid = $attr[ 'nid' ];
		}
		if ( $attr[ 'suffix' ] ) {
			$attr[ 'floating' ] = false;
		}
		if ( $attr[ 'encryptData' ] ) {
			$attr[ 'inSearch' ] = 0;
		}

		/* add the field specific attributes as param to the general attributes. */
		parent::save( $attr );
	}

	/**
	 * Saves the field specific data for a new or duplicated field.
	 *
	 * @param $attr
	 * @param $fid
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