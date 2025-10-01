<?php
/**
 * @package SobiPro Library
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2023 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 09-Mar-2009 by Radek Suski
 * @modified 19 September 2023 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

use Sobi\C;

/**
 * Interface SPFieldInterface
 */
interface SPFieldInterface
{
	/**
	 * Shows the field in the edit entry or add entry form
	 *
	 * @param bool $return return or display directly
	 *
	 * @return string
	 */
	public function field( $return = false );

	/**
	 * Gets the data for a field, verify it and pre-save it.
	 *
	 * @param SPEntry $entry
	 * @param string $tsId
	 * @param string $request
	 *
	 * @return void
	 */
	public function submit( &$entry, $tsId = C::ES, $request = 'POST' );

	/**
	 * Gets the data for a field and save it in the database
	 *
	 * @param SPEntry $entry
	 * @param string $request
	 *
	 * @return bool
	 */
//	public function saveData( &$entry, $request = 'POST' );

	/**
	 * @param array $params
	 * @param int $version
	 * @param bool $untranslatable
	 * @param bool $log
	 *
	 * @return void|string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function saveToDatabase( array $params, int $version, bool $untranslatable = false, bool $log = false );

	/**
	 * @param $sid
	 *
	 * @return void
	 */
	public function approve( $sid );

	/**
	 * Shows the field in the search form
	 *
	 * @param bool $return return or display directly
	 *
	 * @return string
	 */
	public function searchForm( $return = false );

	/**
	 * @param $sid
	 *
	 * @return mixed
	 */
	public function deleteData( $sid );

	/**
	 * @param $attr
	 */
	public function save( &$attr );

	/**
	 * @param $sid
	 * @param $state
	 *
	 * @return mixed
	 */
	public function changeState( $sid, $state );

	/**
	 * Incoming search request for general search field.
	 *
	 * @param $data -> string to search for
	 * @param $section -> section
	 * @param bool $regex -> as regex
	 *
	 * @return array
	 */
	public function searchString( $data, $section, $regex = false );

	/**
	 * Incoming search request for extended search field.
	 *
	 * @param array|string $data -> string/data to search for
	 * @param $section -> section
	 * @param string $phrase -> search phrase if needed
	 *
	 * @return array
	 */
	//public function searchData( $data, $section, $phrase = C::ES );

	/**
	 * @param $val
	 *
	 * @return mixed
	 */
	public function setSelected( $val );

	/**
	 * @return mixed
	 */
	public function metaDesc();

	/**
	 * @return mixed
	 */
	public function metaKeys();
}
