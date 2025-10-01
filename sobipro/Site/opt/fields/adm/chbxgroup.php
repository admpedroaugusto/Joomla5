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
 * @created 27-Nov-2009 by Radek Suski
 * @modified 01 March 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadClass( 'opt.fields.chbxgroup' );

use Sobi\C;
use Sobi\Input\Input;
use Sobi\Utils\Arr;
use Sobi\Utils\StringUtils;

/**
 * Class SPField_ChbxGrAdm
 */
class SPField_ChbxGrAdm extends SPField_ChbxGr
{
	/**
	 * @param $attr
	 *
	 * @throws \Sobi\Error\Exception
	 */
	public function save( &$attr )
	{
		/* add the field specific attributes as param to the general attributes. */
		$options = $attr[ 'options' ];
		unset( $attr[ 'options' ] );    /* temporary remove the options */
		$attr[ 'defaultValue' ] = $attr[ 'defaultValue' ] ? StringUtils::Nid( $attr[ 'defaultValue' ] ) : C::ES;

		//$attr[ 'required' ] = $attr[ 'meaning' ] == 'price' || $attr[ 'meaning' ] == 'terms' ? false : $attr[ 'required' ];
		parent::save( $attr );
		$attr[ 'options' ] = $options;
	}

	/**
	 * Saves the field specific data for a new or duplicated field.
	 *
	 * @param $attr
	 * @param $fid
	 *
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
	 * Saves options and language-dependent data to the database.
	 *
	 * @param array $attr
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function saveLanguageData( $attr ): void
	{
		/* save the options to the database */
		$this->saveOptions( $attr );

		/* save the language dependent select labels */
		$this->saveSelectLabel( $attr );
	}
}