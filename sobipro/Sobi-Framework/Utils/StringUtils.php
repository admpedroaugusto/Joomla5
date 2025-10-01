<?php
/**
 * @package: Sobi Framework
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
 * @created Thu, Dec 1, 2016 by Radek Suski
 * @modified 27 April 2023 by Sigrid Suski
 */

//declare( strict_types=1 );

namespace Sobi\Utils;

defined( 'SOBI' ) || exit( 'Restricted access' );

use Joomla\CMS\Factory as JFactory;
use Sobi\{C, Framework};

/**
 * Class StringUtils
 * @package Sobi\Utils
 */
abstract class StringUtils
{
	/**
	 * Removes slashes from string
	 *
	 * @param string $txt
	 *
	 * @return string
	 */
	public static function Clean( string $txt ): string
	{
		while ( strstr( $txt, "\'" ) || strstr( $txt, '\"' ) || strstr( $txt, '\\\\' ) ) {
			$txt = stripslashes( $txt );
		}

		return $txt;
	}

	/**
	 * @param string $txt
	 * @param bool $unicode
	 * @param bool $forceUnicode
	 *
	 * @param bool $null - nullify
	 *
	 * @return string
	 * @throws \Sobi\Error\Exception
	 * @throws \Exception
	 */
	public static function Nid( string $txt, bool $unicode = false, bool $forceUnicode = false, bool $null = false ): string
	{
		$txt = trim( str_replace( [ '.', '_' ], ( $null ? C::ES : '-' ), $txt ) );

		return ( Framework::Cfg( 'sef.unicode' ) && $unicode ) || $forceUnicode ? self::UrlSafe( $txt ) : trim( preg_replace( '/(\s|[^A-Za-z0-9\-])+/', '-', JFactory::getApplication()->getLanguage()->transliterate( $txt ) ), '_-\[\]\(\)' );
	}

	/**
	 * Makes a nid for fields -> _ won't be replaced with -
	 *
	 * @param string $txt
	 *
	 * @return string
	 */
	public static function FieldNid( string $txt ): string
	{
		$txt = strtolower( trim( str_replace( [ '.' ], '-', $txt ) ) );

		return $txt;
	}

	/**
	 * Creates URL safe string
	 *
	 * @param string $str
	 *
	 * @return string
	 */
	public static function UrlSafe( string $str ): string
	{
		// copy of Joomla! stringURLUnicodeSlug
		// we don't want to have it lowercased
		// @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
		// Replace double byte whitespaces by single byte (East Asian languages)
		$str = preg_replace( '/\xE3\x80\x80/', ' ', $str );
		// Remove any '-' from the string as they will be used as concatenator.
		// Would be great to let the spaces in but only Firefox is friendly with this
		$str = str_replace( '-', ' ', $str );
		// Replace forbidden characters by whitespaces
		$str = preg_replace( '/[:#*"@+=;!><&.%()\]\/\'\\\\|\[]/', "\x20", $str );
		// Delete all '?'
		$str = str_replace( '?', C::ES, $str );
		// Remove any duplicate whitespace and replace whitespaces by hyphens
		$str = preg_replace( '/\x20+/', '-', $str );
		$str = preg_replace( [ '/\s+/', '/[^A-Za-z0-9\p{L}\-\_]/iu' ], [ '-', C::ES ], $str );

		return trim( $str, '_-\[\]\(\)' );
	}

	/**
	 * Replaces HTML entities to valid XML entities
	 *
	 * @param string $txt
	 * @param bool $amp
	 *
	 * @return string
	 */
	public static function Entities( string $txt, bool $amp = false ): string
	{
		$txt = str_replace( '&', '&#38;', $txt );
		if ( $amp ) {
			return $txt;
		}
		//		$txt = htmlentities( $txt, ENT_QUOTES, 'UTF-8' );
		$entities = [ 'auml' => '&#228;', 'ouml' => '&#246;', 'uuml' => '&#252;', 'szlig' => '&#223;', 'Auml' => '&#196;', 'Ouml' => '&#214;', 'Uuml' => '&#220;', 'nbsp' => '&#160;', 'Agrave' => '&#192;', 'Egrave' => '&#200;', 'Eacute' => '&#201;', 'Ecirc' => '&#202;', 'egrave' => '&#232;', 'eacute' => '&#233;', 'ecirc' => '&#234;', 'agrave' => '&#224;', 'iuml' => '&#239;', 'ugrave' => '&#249;', 'ucirc' => '&#251;', 'ccedil' => '&#231;', 'AElig' => '&#198;', 'aelig' => '&#330;', 'OElig' => '&#338;', 'oelig' => '&#339;', 'angst' => '&#8491;', 'cent' => '&#162;', 'copy' => '&#169;', 'Dagger' => '&#8225;', 'dagger' => '&#8224;', 'deg' => '&#176;', 'emsp' => '&#8195;', 'ensp' => '&#8194;', 'ETH' => '&#208;', 'eth' => '&#240;', 'euro' => '&#8364;', 'half' => '&#189;', 'laquo' => '&#171;', 'ldquo' => '&#8220;', 'lsquo' => '&#8216;', 'mdash' => '&#8212;', 'micro' => '&#181;', 'middot' => '&#183;', 'ndash' => '&#8211;', 'not' => '&#172;', 'numsp' => '&#8199;', 'para' => '&#182;', 'permil' => '&#8240;', 'puncsp' => '&#8200;', 'raquo' => '&#187;', 'rdquo' => '&#8221;', 'rsquo' => '&#8217;', 'reg' => '&#174;', 'sect' => '&#167;', 'THORN' => '&#222;', 'thorn' => '&#254;', 'trade' => '&#8482;' ];
		foreach ( $entities as $ent => $repl ) {
			$txt = preg_replace( '/&' . $ent . ';?/m', $repl, $txt );
		}

		return $txt;
	}

	/**
	 * Creates JS friendly script.
	 *
	 * @param string $txt
	 *
	 * @return string
	 */
	public static function Js( string $txt ): string
	{
		return addslashes( $txt );
	}

	/**
	 * Used for XML nodes creation.
	 * Creates singular form from plural.
	 *
	 * @param string $txt
	 *
	 * @return string
	 */
	public static function Singular( string $txt ): string
	{
		/* entries <=> entry */
		if ( substr( $txt, -3 ) == 'ies' ) {
			$txt = substr( $txt, 0, -3 ) . 'y';
		}
		/* buses <=> bus */
		else {
			if ( substr( $txt, -3 ) == 'ses' ) {
				$txt = substr( $txt, 0, -3 );
			}
			/* sections <=> section */
			else {
				if ( substr( $txt, -1 ) == 's' ) {
					$txt = substr( $txt, 0, -1 );
				}
			}
		}

		return $txt;
	}

	/**
	 * Returns correctly formatted currency amount.
	 *
	 * @param float $value - amount
	 * @param bool $currency
	 *
	 * @return string
	 * @throws \Sobi\Error\Exception
	 */
	public static function Currency( float $value, bool $currency = true ): string
	{
		$dp = html_entity_decode( Framework::Cfg( 'payments.dec_point', ',' ), ENT_QUOTES );
		$value = number_format( $value, (int) Framework::Cfg( 'payments.decimal', 2 ), $dp, Framework::Cfg( 'payments.thousands_sep', ' ' ) );
		if ( $currency ) {
			$value = str_replace( [ '%value', '%currency' ], [ $value, Framework::Cfg( 'payments.currency', 'EUR' ) ], Framework::Cfg( 'payments.format', '%value %currency' ) );
		}

		return $value;
	}

	/**
	 * Creates alias/nid suitable string.
	 *
	 * @param $txt
	 *
	 * @return string
	 * @throws \Sobi\Error\Exception
	 */
	public static function VarName( $txt ): string
	{
		$pieces = explode( ' ', $txt );
		$txt = null;
		for ( $i = 0; $i < count( $pieces ); $i++ ) {
			$pieces[ $i ] = self::Nid( $pieces[ $i ] );
			if ( $i > 0 ) {
				$pieces[ $i ] = ucfirst( $pieces[ $i ] );
			}
			$txt .= $pieces[ $i ];
		}

		return $txt ?? C::ES;
	}
}
