<?php
/**
 * @package SobiPro multi-directory component with content construction support
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006-2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/GPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according to section 7 of GPL v3.
 * See https://www.gnu.org/licenses/gpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * @created 28-Nov-2009 by Radek Suski
 * @modified 25 October 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadClass( 'opt.fields.image' );

/**
 * Class SPField_ImageAdm
 */
class SPField_ImageAdm extends SPField_Image
{
	/**
	 * @param $attr
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function save( &$attr )
	{
		if ( $this->nid ) {
			$this->nid = $attr[ 'nid' ];
		}
		if ( ( $attr[ 'resize' ] || $attr[ 'crop' ] ) && !( $attr[ 'resizeWidth' ] && $attr[ 'resizeHeight' ] ) ) {
			throw new SPException( SPLang::e( 'IMG_FIELD_RESIZE_NO_SIZE' ) );
		}
		if ( $attr[ 'convert' ] && !function_exists( 'imagewebp' ) ) {
			SPFactory::message()->warning( Sobi::Txt( 'IMG_FIELD_NOWEBP' ), true );
		}

		/* some users set this to true in the past */
		$attr[ 'admList' ] = false;

		/* add the field specific attributes as param to the general attributes. */
		parent::save( $attr );
	}

	/**
	 * Saves the field specific data for a new or duplicated field.
	 *
	 * @param $attr
	 * @param $fid
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function saveNew( &$attr, $fid = 0 )
	{
		if ( $fid ) {
			$this->id = $this->fid = $fid;
		}
		$this->save( $attr );
	}

	/**
	 * Saves the translatable elements.
	 *
	 * @param $attr
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function saveLanguageData( $attr ): void
	{
		/* generate and save the language specific field parameters */
		if ( !isset( $attr[ 'altPattern' ] ) ) {
			$attr[ 'altPattern' ] = $this->altPattern;
		}
		$data = [
			'key'     => $this->nid . '-alt',
			'value'   => $attr[ 'altPattern' ],
			'type'    => 'field_' . $this->fieldType,
			'fid'     => $this->fid,
			'id'      => $attr[ 'section' ],
			'section' => $attr[ 'section' ],
		];
		SPLang::saveValues( $data );
	}

	/**
	 * @param $field
	 *
	 * @return bool
	 */
	public function getFieldData( $field )
	{
		return false;
	}

	/**
	 * @param $field
	 *
	 * @return bool
	 */
	public function setFieldData( $field )
	{
		return false;
	}
}