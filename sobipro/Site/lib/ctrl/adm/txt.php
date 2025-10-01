<?php
/**
 * @package: SobiPro Library
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2022 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 15-Jul-2010 by Radek Suski
 * @modified 02 August 2022 by Sigrid Suski
 */

use Sobi\C;
use Sobi\Input\Input;

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadController( 'txt' );

/**
 * Class SPJsTxtAdm
 */
class SPJsTxtAdm extends SPJsTxt
{
	/**
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function js()
	{
		$lang = SPLang::jsLang( true );
		if ( is_array( $lang ) && count( $lang ) ) {
			foreach ( $lang as $term => $text ) {
				unset( $lang[ $term ] );
				$term = str_replace( 'SP.JS_', C::ES, $term );
				$lang[ $term ] = $text;
			}
		}
		if ( !( Input::Int( 'deb' ) ) ) {
			SPFactory::mainframe()->cleanBuffer();
			header( 'Content-type: text/javascript' );
		}
		echo 'SobiPro.setLang( ' . json_encode( $lang ) . ' );';
		exit( 'SobiPro.setIcons( ' . Sobi::getIcons() . ' );' );
	}
}
