<?php
/**
 * @package: SobiPro Library
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
 * @created 28-Oct-2010 by Radek Suski
 * @modified 07 April 2023 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

use Sobi\C;
use Sobi\Input\Input;
use Sobi\Lib\Factory;
use Sobi\Utils\StringUtils;
use Joomla\CMS\HTML\HTMLHelper;

/**
 * Class SobiPro
 */
abstract class SobiPro
{
	/**
	 * Passes the given string to the plugins to parse the content.
	 *
	 * @param $content
	 *
	 * @return mixed
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function ParseContent( $content )
	{
		Sobi::Trigger( 'Parse', 'Content', [ &$content ] );

		return $content;
	}

	/**
	 * Translates given string into the current language (main template files (front,back) and accessibility strings).
	 *
	 * @return string
	 */
	public static function Txt()
	{
		$args = func_get_args();

		return call_user_func_array( [ 'Sobi', 'Txt' ], $args );
	}

	/**
	 * Translates given string into the current language (template strings (SpTpl) only).
	 *
	 * @return mixed
	 */
	public static function TemplateTxt()
	{
		$args = func_get_args();

		return call_user_func_array( [ 'Sobi', 'TemplateTxt' ], $args );
	}
	/**
	 * @param $lang
	 *
	 * @throws \Sobi\Error\Exception
	 */
	public static function LoadLang( $lang )
	{
		Factory::Application()->loadLanguage( $lang );
	}

	/**
	 * @return mixed
	 */
	public static function Token()
	{
		return Factory::Application()->token();
	}

	/**
	 * Counts children of a category / section.
	 *
	 * @param $sid
	 * @param string $childs
	 *
	 * @return int
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public static function Count( $sid, $childs = 'entry' )
	{
		static $cache = [];
		if ( !isset( $cache[ $sid ] ) ) {
			$cache[ $sid ] = SPFactory::Model( 'category' );
			$cache[ $sid ]->init( $sid );
		}

		return $cache[ $sid ]->countChilds( $childs, 1 );
	}

	/**
	 * Creates a tooltip with the given title and text.
	 *
	 * @param $tooltip
	 * @param null $title
	 * @param null $img
	 *
	 * @return string
	 */
	public static function Tooltip( $tooltip, $title = null, $img = null )
	{
		return 'Removed! Use standard Bootstrap functions for tooltips.';
	}

	/**
	 * Returns formatted as a currency text with given amount and currency.
	 *
	 * @param $value
	 *
	 * @return string
	 * @throws \Sobi\Error\Exception
	 */
	public static function Currency( $value )
	{
		return StringUtils::Currency( $value );
	}

	/**
	 * @param $format
	 * @param $date
	 *
	 * @return false|string
	 */
	public static function FormatDate( $format, $date )
	{
		return date( $format, strtotime( $date ) );
	}

	/**
	 * Date from Joomla localize (for Persian, Arabic, etc).
	 *
	 * @param $format
	 * @param $date
	 *
	 * @return string
	 */
	public static function JFormatDate( $format, $date )
	{
		return HTMLHelper::date( strtotime( $date ), $format );
	}


	/**
	 * @param $format
	 * @param $date
	 * @param null $locale
	 *
	 * @return string
	 */
	public static function SFormatDate( $format, $date, $locale = null )
	{
		if ( $locale ) {
			setlocale( LC_ALL, $locale );
		}

		return strftime( $format, strtotime( $date ) );
	}

	/**
	 * @param $file
	 * @param null $tplPath
	 *
	 * @throws SPException
	 */
	public static function LoadCssFile( $file, $tplPath = null )
	{
		if ( $tplPath ) {
			$tplPath = str_replace( Sobi::Cfg( 'live_site' ), SOBI_ROOT . '/', $tplPath );
			$file = 'absolute.' . $tplPath . '/css/' . $file;
		}
		SPFactory::header()->addCssFile( $file );
	}

	/**
	 * @param $file
	 * @param null $tplPath
	 *
	 * @throws SPException
	 */
	public static function LoadJsFile( $file, $tplPath = null )
	{
		if ( $tplPath ) {
			$tplPath = str_replace( Sobi::Cfg( 'live_site' ), SOBI_ROOT . '/', $tplPath );
			$file = 'absolute.' . $tplPath . '/js/' . $file;
		}
		SPFactory::header()->addJsFile( $file );
	}

	/**
	 * @param $file
	 *
	 * @throws SPException
	 */
	public static function AddJsFile( $file )
	{
		if ( strstr( $file, ',' ) ) {
			$file = explode( ',', $file );
		}
		SPFactory::header()->addJsFile( $file );
	}

	/**
	 * @param $file
	 *
	 * @throws SPException
	 */
	public static function AddCSSFile( $file )
	{
		if ( strstr( $file, ',' ) ) {
			$file = explode( ',', $file );
		}
		SPFactory::header()->addJsFile( $file );
	}

	/**
	 * @param $key
	 * @param null $def
	 * @param string $section
	 *
	 * @return array|mixed|string|null
	 */
	public static function Cfg( $key, $def = null, $section = 'general' )
	{
		return Sobi::Cfg( $key, $def, $section );
	}

	/**
	 * Creates URL to the internal SobiPro function.
	 *
	 * @param null $var
	 * @param false $js
	 * @param bool $sef
	 * @param false $live
	 * @param false $forceItemId
	 *
	 * @return mixed
	 * @throws SPException
	 */
	public static function Url( $var = null, $js = false, $sef = true, $live = false, $forceItemId = false )
	{
		if ( $var == 'current' ) {
			return Sobi::Url( $var, $js, $sef, $live, $forceItemId );
		}
		$url = json_decode( $var, true );
		if ( count( $url ) ) {
			foreach ( $url as $key => $value ) {
				$url[ $key ] = trim( $value );
			}
		}

		return Sobi::Url( $url, $js, $sef, $live, $forceItemId );
	}

	/**
	 * Adds an alternate link to the header section.
	 *
	 * @param $url
	 * @param $type
	 * @param null $title
	 *
	 * @throws SPException
	 */
	public static function AlternateLink( $url, $type, $title = null )
	{
		SPFactory::header()->addHeadLink( self::Url( $url ), $type, $title );
	}

	/**
	 * Triggers plugin for the given content.
	 *
	 * @param $name
	 * @param $sid
	 * @param int $section
	 *
	 * @return mixed|null
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function Application( $name, $sid, $section = 0 )
	{
		$section = $section ? : Sobi::Section();
		$content = null;
		Sobi::Trigger( $name, 'TemplateDisplay', [ &$content, $sid, $section ] );

		return $content;
	}

	/**
	 * Checks the permission for an action.
	 * Can be also used like this: SobiPro::Can( 'subject.action.ownership' )
	 *
	 * @param $subject
	 * @param string $action - e.g. edit
	 * @param int $section - section. If not given, the current section will be used
	 * @param string $ownership - e.g. own, all or valid
	 *
	 * @return bool|mixed - true if authorized
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function Can( $subject, $action = 'access', $section = null, $ownership = 'valid' )
	{
		return SPFactory::user()->can( $subject, $action, $ownership, $section );
	}

	/**
	 * Returns copy of stored registry value key.
	 *
	 * @param string $key - stored key
	 * @param mixed $def - default value
	 *
	 * @return mixed
	 */
	public static function Reg( $key, $def = null )
	{
		return SPFactory::registry()->get( $key, $def );
	}

	/**
	 * Returns copy of stored registry value key.
	 *
	 * @param string $key - stored key
	 * @param mixed $def - default value
	 *
	 * @return string
	 */
	public static function Request( $key, $def = C::ES )
	{
		return Input::String( $key, 'request', $def );
	}

	/**
	 * Returns selected property of the currently visiting user.
	 * e.g SobiPro::My( 'id' ); SobiPro::My( 'name' );
	 *
	 * @param string $property
	 *
	 * @return false
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function My( $property )
	{
		if ( in_array( $property, [ 'password', 'block', 'sendEmail', 'activation', 'params' ] ) ) {
			return false;
		}
		static $user = null;
		if ( !$user ) {
			$user =& SPFactory::user();
		}

		return $user->get( $property );
	}

	/**
	 * Returns selected property of the a selected user.
	 * e.g. SobiPro::User( 'id' ); SobiPro::User( 'name' );
	 *
	 * @param $id
	 * @param $property
	 *
	 * @return bool|null
	 * @throws \SPException
	 */
	public static function User( $id, $property )
	{
		$property = trim( $property );
		if ( in_array( $property, [ 'password', 'block', 'sendEmail', 'activation', 'params' ] ) ) {
			return false;
		}
		$id = ( int ) $id;
		static $loaded = [];
		if ( !isset( $loaded[ $id ] ) ) {
			$loaded[ $id ] = SPUser::getBaseData( $id );
		}

		return $loaded[ $id ]->$property ?? null;
	}


	/**
	 * Returns the icon for a specific font.
	 * e.g. SobiPro::Icon( 'list', 'fa5', 'true' );
	 *
	 * @param $icon
	 * @param string $font
	 * @param bool $full
	 * @param null $class
	 *
	 * @return string|null
	 */
	public static function Icon( $icon, $font = 'fa5', $full = true, $class = null )
	{
		$args = func_get_args();

		return call_user_func_array( [ 'Sobi', 'Icon' ], $args );
	}
}
