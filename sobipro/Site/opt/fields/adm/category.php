<?php
/**
 * @package SobiPro multi-directory component with content construction support
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2023 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/GPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/gpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
 *
 * @created 09-Aug-2012 by Radek Suski
 * @modified 29 December 2023 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadClass( 'opt.fields.category' );

/**
 * Class SPField_CategoryAdm
 */
class SPField_CategoryAdm extends SPField_Category
{
	/**
	 * @param $attr
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function save( &$attr )
	{
		if ( $attr[ 'method' ] == 'fixed' ) {
			if ( !$attr[ 'fixedCid' ] ) {
				throw new SPException( SPLang::e( 'FIELD_FIXED_CID_MISSING' ) );
			}
			else {
				$attr[ 'editable' ] = true;
				$cids = explode( ',', $attr[ 'fixedCid' ] );
				if ( is_array( $cids ) && count( $cids ) ) {
					foreach ( $cids as $cid ) {
						$catId = (int) $cid;
						if ( !$catId ) {
							throw new SPException( SPLang::e( 'FIELD_FIXED_CID_INVALID', $cid ) );
						}
						if ( $catId == Sobi::Section() ) {
							throw new SPException( SPLang::e( 'FIELD_FIXED_CID_INVALID', $cid ) );
						}
						else {
							$section = SPFactory::config()->getParentPathSection( $catId );
							if ( !$section || $section != Sobi::Section() ) {
								throw new SPException( SPLang::e( 'FIELD_FIXED_CID_INVALID_SECTION', $catId ) );
							}
						}
					}
				}
				else {
					throw new SPException( SPLang::e( 'FIELD_FIXED_CID_MISSING' ) );
				}
			}
		}

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
}