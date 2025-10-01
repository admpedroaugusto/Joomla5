<?php
/**
 * @package: SobiPro Library
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET
 * @copyright Copyright (C) 2006 - 2023 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See http://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * @created 010-Jan-2009 by Radek Suski
 * @modified 17 March 2023 by Sigrid Suski
 */

use Sobi\C;

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

/**
 *
 */
interface SPMainframeInterface
{
	/**
	 *
	 */
	public function __construct();

	/**
	 * @param $path
	 *
	 * @return mixed
	 */
	public function path( $path );

	/**
	 * Gets basic data from the CMS (e.g Joomla); and stores in the #SPConfig instance
	 */
	public function getBasicCfg();

	/**
	 * @return SPMainFrame
	 */
	public static function & getInstance();

	/**
	 * @static
	 *
	 * @param string $msg The error message, which may also be shown the user if need be.
	 * @param int|string $code The application-internal error code for this error
	 * @param array $info Optional: Additional error information (usually only developer-relevant information that the user should never see, like a database DSN);.
	 * @param bool $translate
	 *
	 * @return    object    $error    The configured JError object
	 */
	public function runAway( string $msg, $code = 500, $info = [], bool $translate = false );

	/**
	 * @return string
	 */
	public function getBack();

	/**
	 * @static
	 *
	 * @param $add
	 * @param null $msg The message, which may also be shown the user if need be.
	 * @param string $msgtype
	 * @param bool $now
	 */
	public function setRedirect( $add, $msg = C::ES, $msgtype = 'message', $now = false );

	/**
	 * @static
	 *
	 * @param string $msg The message, which may also be shown the user if need be.
	 * @param null $type
	 */
	public function msg( $msg, $type = null );

	/**
	 * @static
	 */
	public function redirect();

	/**
	 * @param SPDBObject $obj
	 * @param array $site
	 */
	public function & addObjToPathway( $obj, $site = [] );

	/**
	 * @param array $head
	 * @param bool $afterRender
	 *
	 * @return string
	 */
	public function addHead( array $head, bool $afterRender = false ): string;

	/**
	 * Creating array of additional variables depend on the CMS
	 * @return string
	 * @internal param array $var
	 */
	public function form();

	/**
	 * Creating URL from a array for the current CMS
	 *
	 * @param array $var
	 * @param bool $js
	 *
	 * @return string
	 */
	public static function url( $var = null, $js = false );

	/**
	 * @return mixed
	 */
	public function endOut();

	/**
	 * @param int $id
	 *
	 * @return
	 * @internal param $id
	 */
	public function & getUser( $id = 0 );

	/**
	 * Switching error reporting and displaying of errors compl. off
	 * For e.g JavaScript, or XML output where the document structure is very sensible
	 *
	 */
	public function & cleanBuffer();

	/**
	 * @param string $title
	 */
	public function setTitle( $title );
}
