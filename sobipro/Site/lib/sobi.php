<?php
/**
 * @package SobiPro Library
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006–2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according to section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 10-Jan-2009 by Radek Suski
 * @modified 25 September 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || defined( '_JEXEC' ) || exit( 'Restricted access' );

use Joomla\CMS\Uri\Uri;
use Sobi\C;
use Sobi\Framework;
use Sobi\Input\Input;
use Sobi\FileSystem\FileSystem;
use Sobi\Lib\Factory;
use Sobi\Utils\ {
	Arr,
	StringUtils,
};

/**
 * Factory alike shortcut class for simple access of frequently used methods.
 *
 * Class Sobi
 */
abstract class Sobi
{
	/** @var string */
	public static $filter = C::ES;

	/**
	 * Creates a URL.
	 *
	 * @param array $var -> array with parameters array( 'sid' => 5, 'task' => 'entry.edit' ).
	 * If not given, returns base URL to Sobi Pro.
	 * Can be also a URL string, in this case replacing all & with &amp;
	 * If string is not a URL - it can be a single task: Sobi::Url( 'entry.add' );
	 * a Special case is Sobi::Url( 'current' ); -> in this case returns currently requested URL
	 * @param bool $js
	 * @param bool $sef
	 * @param bool $live
	 * @param bool $forceItemId
	 *
	 * @return mixed
	 * @throws SPException
	 */
	public static function Url( $var = null, $js = false, $sef = true, $live = false, $forceItemId = false )
	{
		return SPFactory::mainframe()->url( $var, $js, $sef, $live, $forceItemId );
	}

	/**
	 * @param string $section - error section. I.e. Entry controller
	 * @param string $msg - main message
	 * @param int $type - error type
	 * @param int $code - error code
	 * @param int $line - file line
	 * @param string $file - file name
	 * @param string $sMsg - additional message
	 *
	 * @throws SPException
	 */
	public static function Error( $section, $msg, $type = C::NOTICE, $code = 0, $line = 0, $file = C::ES, $sMsg = C::ES )
	{
		if ( $type == 0 ) {
			$type = C::NOTICE;
		}
		if ( $type == E_USER_ERROR ) {
			$rType = E_ERROR;
			$code = $code ? : 500;
		}
		else {
			if ( $type == E_USER_WARNING ) {
				$rType = E_WARNING;
			}
			else {
				$rType = $type;
			}
		}
		if ( Sobi::Cfg( 'debug.level', 0 ) >= $rType ) {
			if ( $file ) {
				$sMsg .= sprintf( 'In file %s at line %d', $file, $line );
			}
			if ( Input::Task() ) {
				$sMsg .= ' [ ' . Input::Task() . ' ]';
			}
			$error = [
				'section' => $section,
				'message' => $msg,
				'code'    => $code,
				'file'    => $file,
				'line'    => $line,
				'content' => $sMsg,
			];
			trigger_error( 'json://' . json_encode( $error ), $type );
		}
		if ( $code ) {
			SPLoader::loadClass( 'base.mainframe' );
			SPLoader::loadClass( 'cms.base.mainframe' );
			SPFactory::mainframe()->runAway( $msg, $code, SPConfig::getBacktrace() );
		}
	}

	/**
	 * Saves return URL - the back point to redirect to after several actions like add a new object etc.
	 *
	 * @throws Exception
	 */
	public static function ReturnPoint()
	{
		if ( !isset( $_POST[ 'option' ] ) || $_POST[ 'option' ] != 'com_sobipro'
			|| ( isset( $_GET[ 'hidemainmenu' ] ) && isset( $_POST[ 'hidemainmenu' ] ) )
		) {
			Sobi::SetUserState( 'back_url', Sobi::Url( 'current' ) );
		}
		else {
			$current = 'index.php?';
			foreach ( $_POST as $key => $value ) {
				if ( is_array( $value ) ) {
					$value = implode( ',', $value );
				}
				$current .= $key . '=' . ( ( string ) $value ) . '&amp;';
			}
			Sobi::SetUserState( 'back_url', $current );
		}
	}


	/**
	 * Returns formatted date.
	 *
	 * @param int|string $time - time or date
	 * @param string $formatkey
	 * @param string $format
	 * @param bool $gmt
	 *
	 * @return string
	 * @throws \SPException
	 */
	public static function Date( $time = 0, string $formatkey = 'date.publishing_format', string $format = C::ES, bool $gmt = false )
	{
		return SPFactory::config()->date( $time, $formatkey, $format, $gmt );
	}

	/**
	 * Sets a redirect.
	 *
	 * @param array|string $address - @see #Url
	 * @param string $msg - message for user
	 * @param string $msgtype - 'message' or 'error'
	 * @param bool $now - if true, redirecting immediately
	 * @param int $code
	 *
	 * @throws SPException
	 */
	public static function Redirect( $address, string $msg = C::ES, string $msgtype = 'message', bool $now = false, $code = 302 )
	{
		SPFactory::mainframe()->setRedirect( $address, $msg, $msgtype, $now );
		if ( $now ) {
			SPFactory::mainframe()->redirect( $code );
		}
	}

	/**
	 * Returns translation of a selected language-dependent string (case 1)
	 * or translates language-dependent properties (case 2).
	 * Only for main language files and accessibility file!
	 *
	 * case 1)
	 * @return mixed case 2)
	 *
	 * case 2)
	 * @internal param array $vars - variables included in the string.
	 *             array( 'username' => $username, 'userid' => $uid ).
	 *         The language label has to be defined like this my_label = "My name is var:[username] and my id is var:[userid]"
	 * @internal param array $sids - array with ids of objects to translate
	 * @internal param array $fields - (optional) array (or string) with property names to translate. If not given, translates all
	 * @internal param string $type - (optional) type of object (section, category, entry). If not given, translates all
	 * @internal param string $lang - (optional) specific language. If not given, use currently set language
	 * @internal param string $txt - string to translate
	 */
	public static function Txt()
	{
		$args = func_get_args();
		if ( is_array( $args[ 0 ] ) && Arr::IsAnInt( $args[ 0 ] ) ) {
			return call_user_func_array( [ 'SPLang', 'translateObject' ], $args );
		}
		else {
			/* don't translate if all letters are lowercase (backend only) */
			if ( defined( 'SOBIPRO_ADM' ) && strtolower( $args[ 0 ] ) == $args[ 0 ] ) {
				return $args[ 0 ];
			}
			else {
				return call_user_func_array( [ 'SPLang', '_' ], $args );
			}
		}
	}

	/**
	 * Returns translation of a selected language dependent string (case 1)
	 *  or translates language dependent properties (case 2).
	 *  For main language files, accessibility file and default template file!
	 *
	 * @return array|mixed
	 */
	public static function TemplateTxt()
	{
		$args = func_get_args();
		if ( is_array( $args[ 0 ] ) && Arr::IsAnInt( $args[ 0 ] ) ) {
			return call_user_func_array( [ 'SPLang', 'translateObject' ], $args );
		}
		else {
			/* don't translate if all letters are lowercase (backend only) */
			if ( defined( 'SOBIPRO_ADM' ) && strtolower( $args[ 0 ] ) == $args[ 0 ] ) {
				return $args[ 0 ];
			}
			else {
				return call_user_func_array( [ 'SPLang', '_tmpl' ], $args );
			}
		}
	}

	/**
	 * Cleaning string for the output.
	 *
	 * @param string $txt
	 *
	 * @return string
	 */
	public static function Clean( string $txt ): string
	{
		return StringUtils::Clean( $txt );
	}

	/**
	 * Triggers plugin action: Sobi::Trigger( 'LoadField', 'Search', array( &$fields ) );
	 *
	 * @param string $action - action to trigger
	 * @param string $subject - subject of this action: e.g. entry, category, search etc
	 * @param array $params - parameters to pass to the plugin
	 *
	 * @return void
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function Trigger( string $action, string $subject = C::ES, array $params = [] )
	{
		SPFactory::plugins()->trigger( $action, $subject, $params );
	}

	/**
	 * @param $action
	 * @param $object
	 *
	 * @throws SPException
	 */
	public static function RegisterHandler( $action, &$object )
	{
		SPFactory::plugins()->registerHandler( $action, $object );
	}

	/**
	 * @param $object
	 *
	 * @throws SPException
	 */
	public static function AttachHandler( &$object )
	{
		SPFactory::plugins()->registerHandler( null, $object );
	}

	/**
	 * Checks the permission for an action.
	 * Can be also used like this:
	 *         Sobi::Can( 'subject.action.ownership' )
	 *         Sobi::Can( 'entry.access.unpublished_own' )
	 *
	 * @param $subject
	 * @param string $action - e.g. edit
	 * @param string $ownership - e.g. xx_own, all or valid
	 * @param int $section - section; If not given, the current section will be used
	 *
	 * @return bool|mixed -> true if authorized
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function Can( $subject, $action = 'access', $ownership = 'valid', $section = null )
	{
		return SPFactory::user()->can( $subject, $action, $ownership, $section );
	}

	/**
	 * Sets the value of a user state variable.
	 *
	 * @param string $key - The path of the state.
	 * @param string $value - The value of the variable.
	 *
	 * @return mixed The previous state, if one existed.
	 * @throws Exception
	 */
	public static function SetUserState( $key, $value )
	{
		return SPFactory::user()->setUserState( $key, $value );
	}

	/**
	 * Gets the value of a user state variable.
	 *
	 * @param string $key - The key of the user state variable.
	 * @param string $request - The name of the variable passed in a request.
	 * @param string $default - The default value for the variable if not found. Optional.
	 * @param string $type - Filter for the variable.
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public static function GetUserState( $key, $request, $default = null, $type = 'none' )
	{
		return SPFactory::user()->getUserState( $key, $request, $default, $type );
	}

	/**
	 * Sets the value of user data.
	 *
	 * @param string $key - The path of the state.
	 * @param string $value - The value of the variable.
	 *
	 * @return mixed    The previous state, if one existed.
	 * @throws Exception
	 */
	public static function SetUserData( $key, $value )
	{
		return SPFactory::user()->setUserState( $key, $value );
	}

	/**
	 * Gets the value of user data stored in session.
	 *
	 * @param string $key - The key of the user state variable.
	 * @param null $default - The default value for the variable if not found. Optional.
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public static function GetUserData( $key, $default = null )
	{
		return SPFactory::user()->getUserData( $key, $default );
	}

	/**
	 * @return mixed
	 * @throws SPException
	 */
	public static function Back()
	{
		return SPFactory::mainframe()->getBack();
	}

	/**
	 * Triggers plugin action.
	 *
	 * @param string $action
	 * @param string $subject
	 * @param array $params
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function TriggerPlugin( string $action, string $subject = C::ES, array $params = [] )
	{
		Sobi::Trigger( $action, $subject, $params );
	}

	/**
	 * Returns copy of a stored config key.
	 * Can be also used like this: Sobi::Cfg( 'config_section.config_key', 'default_value' );
	 *
	 * @param string $key - the config key
	 * @param mixed $def - default value
	 * @param string $section - config section (not the SobiPro section)
	 *
	 * @return mixed|string|null|array
	 * @throws \SPException
	 */
	public static function Cfg( $key, $def = null, string $section = 'general' )
	{
		return SPFactory::config()->key( $key, $def, $section );
	}

	/**
	 * @param $icon
	 * @param null $def
	 * @param string $section
	 *
	 * @return null
	 * @depreacted since SobiPro 2.0 use Sobi::Icon() instead
	 * @throws \SPException
	 */
	public static function Ico( $icon, $def = null, $section = 'general' )
	{
		$ico = self::Icon( $icon );

		return $ico;
		//return SPFactory::config()->icon( $icon, $def, $section );
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
	 * Returns current section id or name. Both as string.
	 *
	 * @param string $name
	 *
	 * @return string
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public static function Section( $name = false )
	{
		static $section = C::ES;
		if ( !$name ) {
			return (string) SPFactory::registry()->get( 'current_section' );
		}
		else {
			if ( ( string ) $name == 'nid' ) {
				if ( !$section ) {
					$section = SPFactory::Section( (string) SPFactory::registry()->get( 'current_section' ) );
				}

				return (string) $section->get( 'nid' );
			}
			else {
				return (string) SPFactory::registry()->get( 'current_section_name' );
			}
		}
	}

	/**
	 * Returns currently used language.
	 *
	 * @param $path
	 *
	 * @return string
	 * @deprecated use \Sobi\FileSystem\FileSystem::FixPath
	 */
	public static function FixPath( $path )
	{
		return FileSystem::Clean( $path );
	}

	/**
	 * Returns currently used language.
	 * Revised. Keep an eye on it!!
	 *
	 * @param bool $storage - force lang of sp-language.
	 * If the $_POST array contains "sp-language" and the $storage param is set, this language will be returned.
	 * In other cases, it is recommended to call this function with $storage = false.
	 * However, because this happens only while receiving data from POST
	 *
	 * @param bool $allowEmpty
	 *
	 * @return string
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public static function Lang( bool $storage = true, bool $allowEmpty = false )
	{
		/* when storing lang depend on values and there was lang in request */
		static $langPost = -1;
		static $langGet = -1;
		if ( $langPost == -1 || $langGet == -1 ) {
			$langPost = Input::Cmd( 'sp-language', 'post' );
			$langGet = Input::Cmd( 'sp-language', 'get' );
		}
		if ( $storage && $langPost ) {
			$lang = Input::Cmd( 'sp-language', 'post' );
		}
		/* Otherwise, we're maybe translating now */
		else {
			if ( $langGet && self::Cfg( 'lang.multimode', false ) ) {
				$lang = Input::Cmd( 'sp-language', 'get' );
			}
//		elseif ( $storage ) {
//			/**
//			 * Mon, Dec 4, 2017 11:27:13 (Radek)
//			 * Not quite sure if this is correct. Anyway it seems to be related to #51
//			 * But it would mean it was wrong the entire time.
//			 */
//			$lang = self::DefLang();
//		}
//	****		Tue, Jul 3, 2018 10:45:22 Removed as it causes #115         ****
			/**
			 * Nov 2, 2018 (Sigrid)
			 * since Dec 4, 2017 (#51) Sobi::Lang(true) does no longer return the default language (#130)
			 * but should return always default site language if administrator edits in backend in non-lingual mode
			 * therefore added again with additional query for multilingual mode and backend edit
			 */
			else {
				if ( $storage && defined( 'SOBIPRO_ADM' ) && !Sobi::Cfg( 'lang.multimode', false ) ) {
					$lang = self::DefLang();
				}

				else {
					static $lang = C::ES;
					if ( !strlen( $lang ) ) {
						$lang = SPFactory::config()->key( 'language' );
						self::Trigger( 'Language', 'Determine', [ &$lang ] );
					}
				}
			}
		}
		$lang = strlen( $lang ) ? $lang : ( $allowEmpty ? self::DefLang() : self::Lang( false, true ) );

		return $lang;
	}

	/**
	 * Returns the default language. Either from Back-end or from front-end.
	 *
	 * @return string
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public static function DefLang()
	{
		if ( self::Cfg( 'lang.ignore_default', false ) ) {
			return self::Lang( false );
		}

		$default = self::Cfg( 'lang.default_lang', C::ES );

		return strlen( $default ) ? $default :
			( defined( 'SOBIPRO_ADM' ) ? SOBI_DEFADMLANG : SOBI_DEFLANG );
	}

	/**
	 * @return string
	 */
	public static function DefAdminLang(): string
	{
		return SOBI_DEFADMLANG;
	}

	/**
	 * Creates the text file for the date/time picker.
	 *
	 * @return void
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function createDatepickerLangFile()
	{
		static $created = false;

		if ( !$created ) {
			$languageStrings = [
				'months'      => Sobi::Txt( 'JS_CALENDAR_MONTHS' ),
				'monthsShort' => Sobi::Txt( 'JS_CALENDAR_MONTHS_SHORT' ),
				'days'        => Sobi::Txt( 'JS_CALENDAR_DAYS' ),
				'daysShort'   => Sobi::Txt( 'JS_CALENDAR_DAYS_SHORT' ),
				'daysMin'     => Sobi::Txt( 'JS_CALENDAR_DAYS_MINI' ),
				'today'       => Sobi::Txt( 'JS_CALENDAR_TODAY' ),
				'buttons'     => Sobi::Txt( 'JS_DATEPICKER_BUTTONS' ),
				'picks'       => Sobi::Txt( 'JS_DATEPICKER_PICKS' ),
				'switches'    => Sobi::Txt( 'JS_DATEPICKER_SWITCHES' ),
				'prevs'       => Sobi::Txt( 'JS_DATEPICKER_PREVIOUS' ),
				'nexts'       => Sobi::Txt( 'JS_DATEPICKER_NEXT' ),
				'countup'     => Sobi::Txt( 'JS_DATEPICKER_COUNTUP' ),
				'countdown'   => Sobi::Txt( 'JS_DATEPICKER_COUNTDOWN' ),
				'states'      => Sobi::Txt( 'JS_DATEPICKER_STATES' ),
			];
			$check = md5( serialize( $languageStrings ) );

			if ( !SPLoader::JsFile( 'locale.' . Sobi::Lang( false ) . '_date_picker', false, true, false )
				|| !stripos( FileSystem::Read( SPLoader::JsFile( 'locale.' . Sobi::Lang( false ) . '_date_picker', false, false, false ) ), $check )
			) {
				foreach ( $languageStrings as $key => $value ) {
					$languageStrings[ $key ] = explode( ',', $value );
				}
				$languageStrings = json_encode( $languageStrings );
				$content = "\nvar spDatePickerLang=$languageStrings";
				$content .= "\n//$check";

				FileSystem::Write( SPLoader::JsFile( 'locale.' . Sobi::Lang( false ) . '_date_picker', false, false, false ), $content );
			}
		}
		$created = true;
	}

	/**
	 * Returns selected property of the currently visiting user.
	 * E.g. Sobi::My( 'id' ); Sobi::My( 'name' );
	 *
	 * @param $property
	 *
	 * @return mixed
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function My( $property )
	{
		static $user = null;
		if ( !$user ) {
			$user =& SPFactory::user();
		}

		return $user->get( $property );
	}

	/**
	 * Gets the first selected Font.
	 *
	 * @param bool $element
	 *
	 * @return string
	 * @throws \SPException
	 */
	public static function getFont( bool $element = false ): string
	{
		if ( defined( 'SOBIPRO_ADM' ) ) {
			$font = 'font-awesome-6';
		}
		else {
			/* this is section-specific */
			$fonts = Sobi::Cfg( 'template.icon_fonts_arr', 'font-awesome-6' );
			if ( is_array( $fonts ) && count( $fonts ) ) {
				$font = $fonts[ 0 ];  /* take the first one */
			}
			else {
				$font = $fonts;
			}
		}

		if ( $element ) {
			switch ( $font ) {
				case 'font-awesome-6':
				case 'font-awesome-5':
				case 'font-google-materials':
					$font = 'span';
					break;
				case 'font-awesome-4':
				case 'font-awesome-3':
					$font = 'i';
					break;
			}
		}

		return $font;
	}

	/**
	 * Translates an icon term into the right HTML elements for a specific font.
	 *
	 * @param string $icon
	 * @param string $font
	 * @param bool $full
	 * @param string $class
	 *
	 * @return string
	 * @throws \SPException
	 */
	public static function Icon( string $icon, string $font = C::ES, bool $full = true, string $class = C::ES ): string
	{
		$iconString = C::ES;
		/* convert parameter to bool if it is a string from template */
		$full = is_string( $full ) ? $full == 'true' : $full;
		if ( !$font ) {
			$font = self::getFont();
		}
		switch ( $font ) {
			case 'font-awesome-6':
			case 'fa6':
				$iconArray = [ 'ban'                      => 'fa-solid fa-ban',
				               'bars'                     => 'fa-solid fa-bars',
				               'blank-circle'             => 'fa-regular fa-circle',
				               'calendar'                 => 'fa-solid fa-calendar-days',
				               'checked'                  => 'fa-regular fa-square-check',
				               'arrow-down'               => 'fa-solid fa-arrow-down',
				               'arrow-left'               => 'fa-solid fa-arrow-left',
				               'arrow-right'              => 'fa-solid fa-arrow-right',
				               'arrow-up'                 => 'fa-solid fa-arrow-up',
				               'arrow-circle-down'        => 'fa-solid fa-circle-down',
				               'arrow-circle-left'        => 'fa-solid fa-circle-left',
				               'arrow-circle-right'       => 'fa-solid fa-circle-right',
				               'arrow-circle-up'          => 'fa-solid fa-circle-up',
				               'caret-down'               => 'fa-solid fa-caret-down',
				               'caret-left'               => 'fa-solid fa-caret-left',
				               'caret-right'              => 'fa-solid fa-caret-right',
				               'caret-up'                 => 'fa-solid fa-caret-up',
				               'caret-square-down'        => 'fa-solid fa-square-caret-down',
				               'caret-square-left'        => 'fa-solid fa-square-caret-left',
				               'caret-square-right'       => 'fa-solid fa-square-caret-right',
				               'caret-square-up'          => 'fa-solid fa-square-caret-up',
				               'chevron-down'             => 'fa-solid fa-chevron-down',
				               'chevron-left'             => 'fa-solid fa-chevron-left',
				               'chevron-right'            => 'fa-solid fa-chevron-right',
				               'chevron-up'               => 'fa-solid fa-chevron-up',
				               'chevron-circle-down'      => 'fa-solid fa-circle-chevron-down',
				               'chevron-circle-left'      => 'fa-solid fa-circle-chevron-left',
				               'chevron-circle-right'     => 'fa-solid fa-circle-chevron-right',
				               'chevron-circle-up'        => 'fa-solid fa-circle-chevron-up',
				               'clone'                    => 'fa-solid fa-clone',
				               'download'                 => 'fa-solid fa-download',
				               'edit'                     => 'fa-solid fa-pen-to-square',
				               'envelope'                 => 'fa-solid fa-envelope',
				               'eye'                      => 'fa-regular fa-eye',
				               'eye-close'                => 'fa-regular fa-eye-slash',
				               'exclamation-circle'       => 'fa-solid fa-circle-exclamation',
				               'exclamation-circle-large' => 'fa-solid fa-circle-exclamation fa-lg',
				               'exclamation-triangle'     => 'fa-solid fa-triangle-exclamation',
				               'folder-close'             => 'fa-solid fa-folder',
				               'laquo'                    => 'fa-solid fa-angles-left',
				               'lsaquo'                   => 'fa-solid fa-angle-left',
				               'list'                     => 'fa-solid fa-list',
				               'locate'                   => 'fa-solid fa-location-arrow',
				               'lock'                     => 'fa-solid fa-lock',
				               'marker'                   => 'fa-solid fa-location-dot',
				               'minus'                    => 'fa-solid fa-minus',
				               'move'                     => 'fa-solid fa-up-down-left-right',
				               'ok'                       => 'fa-solid fa-check',
				               'ok-circle'                => 'fa-solid fa-circle-check',
				               'plus'                     => 'fa-solid fa-plus',
				               'plus-circle'              => 'fa-solid fa-circle-plus',
				               'print' => 'fa-solid fa-print',
				               'question'                 => 'fa-solid fa-question',
				               'raquo'                    => 'fa-solid fa-angles-right',
				               'rsaquo'                   => 'fa-solid fa-angle-right',
				               'redo'                     => 'fa-solid fa-redo',
				               'refresh'                  => 'fa-solid fa-rotate',
				               'refresh-spin'             => 'fa-solid fa-rotate fa-spin',
				               'remove'                   => 'fa-solid fa-xmark',
				               'remove-circle'            => 'fa-solid fa-circle-xmark',
				               'save'                     => 'fa-solid fa-floppy-disk',
				               'search'                   => 'fa-solid fa-magnifying-glass',
				               'share'                    => 'fa-solid fa-share-nodes',
				               'signin'                   => 'fa-solid fa-right-to-bracket',
				               'signout'                  => 'fa-solid fa-sign-out-alt',
				               'spinner'                  => 'fa-solid fa-spinner fa-spin',
				               'spinner-large'            => 'fa-solid fa-spinner fa-spin fa-lg',
				               'square'                   => 'fa-solid fa-square',
				               'star'                     => 'fa-solid fa-star',
				               'thumbs-down'              => 'fa-solid fa-thumbs-down',
				               'thumbs-up'                => 'fa-solid fa-thumbs-up',
				               'time'                     => 'fa-solid fa-clock',
				               'trash'                    => 'fa-solid fa-trash',
				               'unchecked'                => 'fa-regular fa-square',
				               'unlock'                   => 'fa-solid fa-unlock',
				];
				if ( !array_key_exists( $icon, $iconArray ) ) {
					$icon = 'question';
				}
			$iconString = $iconArray[ $icon ];
				if ( $full ) {
					$class = $class ? ' ' . $class : C::ES;
					$iconString = "<span class=\"$iconString$class\" aria-hidden=\"true\"></span>";
				}
				break;

			case 'font-awesome-5':
			case 'fa5':
				$iconArray = [ 'ban'                      => 'fas fa-ban',
				               'bars'                     => 'fas fa-bars',
				               'blank-circle'             => 'far fa-circle',
				               'calendar'                 => 'fas fa-calendar-alt',
				               'checked'                  => 'far fa-check-square',
				               'arrow-down'               => 'fas fa-arrow-down',
				               'arrow-left'               => 'fas fa-arrow-left',
				               'arrow-right'              => 'fas fa-arrow-right',
				               'arrow-up'                 => 'fas fa-arrow-up',
				               'arrow-circle-down'        => 'fas fa-arrow-alt-circle-down',
				               'arrow-circle-left'        => 'fas fa-arrow-alt-circle-left',
				               'arrow-circle-right'       => 'fas fa-arrow-alt-circle-right',
				               'arrow-circle-up'          => 'fas fa-arrow-alt-circle-up',
				               'caret-down'               => 'fas fa-caret-down',
				               'caret-left'               => 'fas fa-caret-left',
				               'caret-right'              => 'fas fa-caret-right',
				               'caret-up'                 => 'fas fa-caret-up',
				               'caret-square-down'        => 'fas fa-caret-square-down',
				               'caret-square-left'        => 'fas fa-caret-square-left',
				               'caret-square-right'       => 'fas fa-caret-square-right',
				               'caret-square-up'          => 'fas fa-caret-square-up',
				               'chevron-down'             => 'fas fa-chevron-down',
				               'chevron-left'             => 'fas fa-chevron-left',
				               'chevron-right'            => 'fas fa-chevron-right',
				               'chevron-up'               => 'fas fa-chevron-up',
				               'chevron-circle-down'      => 'fas fa-chevron-circle-down',
				               'chevron-circle-left'      => 'fas fa-chevron-circle-left',
				               'chevron-circle-right'     => 'fas fa-chevron-circle-right',
				               'chevron-circle-up'        => 'fas fa-chevron-circle-up',
				               'clone'                    => 'fas fa-clone',
				               'download'                 => 'fas fa-download',
				               'edit'                     => 'fas fa-edit',
				               'envelope'                 => 'fas fa-envelope',
				               'eye'                      => 'far fa-eye',
				               'eye-close'                => 'far fa-eye-slash',
				               'exclamation-circle'       => 'fas fa-exclamation-circle',
				               'exclamation-circle-large' => 'fas fa-exclamation-circle fa-lg',
				               'exclamation-triangle'     => 'fas fa-exclamation-triangle',
				               'folder-close'             => 'fas fa-folder',
				               'laquo'                    => 'fas fa-angle-double-left',
				               'lsaquo'                   => 'fas fa-angle-left',
				               'list'                     => 'fas fa-list',
				               'locate'                   => 'fas fa-location-arrow',
				               'lock'                     => 'fas fa-lock',
				               'marker'                   => 'fas fa-map-marker-alt',
				               'minus'                    => 'fas fa-minus',
				               'move'                     => 'fas fa-arrows-alt',
				               'ok'                       => 'fas fa-check',
				               'ok-circle'                => 'fas fa-check-circle',
				               'plus'                     => 'fas fa-plus',
				               'plus-circle'              => 'fas fa-plus-circle',
				               'print' => 'fas fa-print',
				               'question'                 => 'fas fa-question',
				               'raquo'                    => 'fas fa-angle-double-right',
				               'rsaquo'                   => 'fas fa-angle-right',
				               'redo'                     => 'fas fa-redo',
				               'refresh'                  => 'fas fa-sync-alt',
				               'refresh-spin'             => 'fas fa-sync-alt fa-spin',
				               'remove'                   => 'fas fa-times',
				               'remove-circle'            => 'fas fa-times-circle',
				               'save'                     => 'fas fa-save',
				               'search'                   => 'fas fa-search',
				               'share'                    => 'fas fa-share-alt',
				               'signin'                   => 'fas fa-sign-in-alt',
				               'signout'                  => 'fas fa-sign-out-alt',
				               'spinner'                  => 'fas fa-spinner fa-spin',
				               'spinner-large'            => 'fas fa-spinner fa-spin fa-lg',
				               'square'                   => 'fas fa-square',
				               'star'                     => 'fas fa-star',
				               'thumbs-down'              => 'fas fa-thumbs-down',
				               'thumbs-up'                => 'fas fa-thumbs-up',
				               'time'                     => 'fas fa-clock',
				               'trash'                    => 'fas fa-trash',
				               'unchecked'                => 'far fa-square',
				               'unlock'                   => 'fas fa-unlock',
				];
				if ( !array_key_exists( $icon, $iconArray ) ) {
					$icon = 'question';
				}
			$iconString = $iconArray[ $icon ];
				if ( $full ) {
					$class = $class ? ' ' . $class : C::ES;
					$iconString = "<span class=\"$iconString$class\" aria-hidden=\"true\"></span>";
				}
				break;

			case 'font-awesome-4':
			case 'fa4':
				$iconArray = [
					'ban'                      => 'fa fa-ban',
					'bars'                     => 'fa fa-bars',
					'blank-circle'             => 'fa fa-circle-o',
					'calendar'                 => 'fa fa-calendar',
					'checked'                  => 'fa fa-check-square-o',
					'arrow-down'               => 'fa fa-arrow-down',
					'arrow-left'               => 'fa fa-arrow-left',
					'arrow-right'              => 'fa fa-arrow-right',
					'arrow-up'                 => 'fa fa-arrow-up',
					'arrow-circle-down'        => 'fa fa-arrow-circle-down',
					'arrow-circle-left'        => 'fa fa-arrow-circle-left',
					'arrow-circle-right'       => 'fa fa-arrow-circle-right',
					'arrow-circle-up'          => 'fa fa-arrow-circle-up',
					'caret-down'               => 'fa fa-caret-down',
					'caret-left'               => 'fa fa-caret-left',
					'caret-right'              => 'fa fa-caret-right',
					'caret-up'                 => 'fa fa-caret-up',
					'caret-square-down'        => 'fa fa-caret-square-o-down',
					'caret-square-left'        => 'fa fa-caret-square-o-left',
					'caret-square-right'       => 'fa fa-caret-square-o-right',
					'caret-square-up'          => 'fa fa-caret-square-o-up',
					'chevron-down'             => 'fa fa-chevron-down',
					'chevron-left'             => 'fa fa-chevron-left',
					'chevron-right'            => 'fa fa-chevron-right',
					'chevron-up'               => 'fa fa-chevron-up',
					'chevron-circle-down'      => 'fa fa-chevron-circle-down',
					'chevron-circle-left'      => 'fa fa-chevron-circle-left',
					'chevron-circle-right'     => 'fa fa-chevron-circle-right',
					'chevron-circle-up'        => 'fa fa-chevron-circle-up',
					'clone'                    => 'fa fa-clone',
					'download'                 => 'fa fa-download',
					'edit'                     => 'fa fa-edit',
					'envelope'                 => 'fa fa-envelope',
					'eye'                      => 'fa fa-eye',
					'eye-close'                => 'fa fa-eye-slash',
					'exclamation-circle'       => 'fa fa-exclamation-circle',
					'exclamation-circle-large' => 'fa fa-exclamation-circle fa-lg',
					'exclamation-triangle'     => 'fa fa-exclamation-triangle',
					'laquo'                    => 'fa fa-angle-double-left',
					'folder-close'             => 'fa fa-folder',
					'lsaquo'                   => 'fa fa-angle-left',
					'list'                     => 'fa fa-th-list',
					'locate'                   => 'fa fa-location-arrow',
					'lock'                     => 'fa fa-lock',
					'marker'                   => 'fa fa-map-marker',
					'minus'                    => 'fa fa-minus',
					'move'                     => 'fa fa-arrows',
					'ok'                       => 'fa fa-check',
					'ok-circle'                => 'fa fa-check-circle',
					'plus'                     => 'fa fa-plus',
					'plus-circle'              => 'fa fa-plus-circle',
					'print' => 'fa fa-print',
					'question'                 => 'fa fa-question',
					'raquo'                    => 'fa fa-angle-double-right',
					'rsaquo'                   => 'fa fa-angle-right',
					'redo'                     => 'fa fa-repeat',
					'refresh'                  => 'fa fa-refresh',
					'refresh-spin'             => 'fa fa-refresh fa-spin',
					'remove'                   => 'fa fa-times',
					'remove-circle'            => 'fa fa-times-circle',
					'save'                     => 'fa fa-save',
					'search'                   => 'fa fa-search',
					'share'                    => 'fa fa-share-alt',
					'signin'                   => "fa fa-sign-in",
					'signout'                  => "fa fa-sign-out",
					'spinner'                  => 'fa fa-spinner fa-spin',
					'spinner-large'            => 'fa fa-spinner fa-spin fa-lg',
					'square'                   => 'fa fa-square',
					'star'                     => 'fa fa-star',
					'thumbs-down'              => 'fa fa-thumbs-down',
					'thumbs-up'                => 'fa fa-thumbs-up',
					'time'                     => 'fa fa-clock-o',
					'trash'                    => 'fa fa-trash',
					'unchecked'                => 'fa fa-square-o',
					'unlock'                   => 'fa fa-unlock',
				];

				if ( !array_key_exists( $icon, $iconArray ) ) {
					$icon = 'question';
				}
			$iconString = $iconArray[ $icon ];
				if ( $full ) {
					$iconString = "<i class=\"$iconString $class\" aria-hidden=\"true\"></i>";
				}
				break;

			case 'font-awesome-3':
			case 'fa3':
				$iconArray = [ 'ban'                      => 'icon-ban-circle',
				               'bars'                     => 'icon-reorder',
				               'blank-circle'             => 'icon-circle-blank',
				               'calendar'                 => 'icon-calendar',
				               'checked'                  => 'icon-check',
				               'arrow-down'               => 'icon-arrow-down',
				               'arrow-left'               => 'icon-arrow-left',
				               'arrow-right'              => 'icon-arrow-right',
				               'arrow-up'                 => 'icon-arrow-up',
				               'arrow-circle-down'        => 'icon-circle-arrow-down',
				               'arrow-circle-left'        => 'icon-circle-arrow-left',
				               'arrow-circle-right'       => 'icon-circle-arrow-right',
				               'arrow-circle-up'          => 'icon-circle-arrow-up',
				               'caret-down'               => 'icon-caret-down',
				               'caret-left'               => 'icon-caret-left',
				               'caret-right'              => 'icon-caret-right',
				               'caret-up'                 => 'icon-caret-up',
				               'caret-square-down'        => 'icon-caret-down',
				               'caret-square-left'        => 'icon-caret-left',
				               'caret-square-right'       => 'icon-caret-right',
				               'caret-square-up'          => 'icon-caret-up',
				               'chevron-down'             => 'icon-chevron-down',
				               'chevron-left'             => 'icon-chevron-left',
				               'chevron-right'            => 'icon-chevron-right',
				               'chevron-up'               => 'icon-chevron-up',
				               'chevron-circle-down'      => 'icon-chevron-sign-down',
				               'chevron-circle-left'      => 'icon-chevron-sign-left',
				               'chevron-circle-right'     => 'icon-chevron-sign-right',
				               'chevron-circle-up'        => 'icon-chevron-sign-up',
				               'clone'                    => 'icon-copy',
				               'download'                 => 'icon-download-alt',
				               'edit'                     => 'icon-edit',
				               'envelope'                 => 'icon-envelope',
				               'eye'                      => 'icon-eye-open',
				               'eye-close'                => 'icon-eye-close',
				               'exclamation-circle'       => 'icon-exclamation-sign',
				               'exclamation-circle-large' => 'icon-exclamation-sign icon-large',
				               'exclamation-triangle'     => 'icon-warning-sign',
				               'folder-close'             => 'icon-folder-close',
				               'laquo'                    => 'icon-double-angle-left',
				               'lsaquo'                   => 'icon-angle-left',
				               'list'                     => 'icon-th-list',
				               'locate'                   => 'icon-location-arrow',
				               'lock'                     => 'icon-lock',
				               'marker'                   => 'icon-map-marker',
				               'minus'                    => 'icon-minus',
				               'move'                     => 'icon-move',
				               'ok'                       => 'icon-ok',
				               'ok-circle'                => 'icon-ok-circle',
				               'plus'                     => 'icon-plus',
				               'plus-circle'              => 'icon-plus-sign',
				               'print' => 'icon-print',
				               'question'                 => 'icon-question',
				               'raquo'                    => 'icon-double-angle-right',
				               'rsaquo'                   => 'icon-angle-right',
				               'redo'                     => 'icon-repeat',
				               'refresh'                  => 'icon-refresh',
				               'refresh-spin'             => 'icon-refresh icon-spin',
				               'remove'                   => 'icon-remove',
				               'remove-circle'            => 'icon-remove-sign',
				               'save'                     => 'icon-save',
				               'search'                   => 'icon-search',
				               'share'                    => 'icon-share-alt',
				               'signin'                   => 'icon-sign-in',
				               'signout'                  => 'icon-sign-out',
				               'spinner'                  => 'icon-spinner icon-spin',
				               'spinner-large'            => 'icon-spinner icon-spin icon-large',
				               'square'                   => 'icon-sign-blank',
				               'star'                     => 'icon-star',
				               'thumbs-down'              => 'icon-thumbs-down',
				               'thumbs-up'                => 'icon-thumbs-up',
				               'time'                     => 'icon-time',
				               'trash'                    => 'icon-trash',
				               'unchecked'                => 'icon-check-empty',
				               'unlock'                   => 'icon-unlock',
				];
				if ( !array_key_exists( $icon, $iconArray ) ) {
					$icon = 'question';
				}
			$iconString = $iconArray[ $icon ];
				if ( $full ) {
					$iconString = "<i class=\"$iconString $class\" aria-hidden=\"true\"></i>";
				}
				break;

			case 'gm':
			case 'font-google-materials':
			case 'material':
				$sizeArray = [ 'exclamation-circle-large' => ' md-36',
				               'spinner-large'            => ' md-36',
				];
				$iconArray = [ 'ban'                      => 'block',
				               'bars'                     => 'reorder',
				               'blank-circle'             => 'radio_button_unchecked',
				               'checked'                  => 'check_box',
				               'calendar'                 => 'calendar_today',
				               'arrow-down'               => 'arrow_downward',
				               'arrow-left'               => 'arrow_back',
				               'arrow-right'              => 'arrow_forward',
				               'arrow-up'                 => 'arrow_upward',
				               'arrow-circle-down'        => 'arrow_circle_down',
				               'arrow-circle-left'        => 'arrow_circle_left',
				               'arrow-circle-right'       => 'arrow_circle_right',
				               'arrow-circle-up'          => 'arrow_circle_up',
				               'caret-down'               => 'arrow_drop_down',
				               'caret-left'               => 'arrow_left',
				               'caret-right'              => 'arrow_right',
				               'caret-up'                 => 'arrow_drop_up',
				               'caret-square-down'        => 'arrow_drop_down',
				               'caret-square-left'        => 'arrow_left',
				               'caret-square-right'       => 'arrow_right',
				               'caret-square-up'          => 'arrow_drop_up',
				               'chevron-down'             => 'expand_more',
				               'chevron-left'             => 'chevron_left',
				               'chevron-right'            => 'chevron_right',
				               'chevron-up'               => 'expand_less',
				               'chevron-circle-down'      => 'expand_more',
				               'chevron-circle-left'      => 'chevron_left',
				               'chevron-circle-right'     => 'chevron_right',
				               'chevron-circle-up'        => 'expand_less',
				               'clone'                    => 'filter_none',
				               'download'                 => 'download',
				               'edit'                     => 'edit',
				               'envelope'                 => 'email',
				               'eye'                      => 'visibility',
				               'eye-close'                => 'visibility_off',
				               'exclamation-circle'       => 'error_outline',
				               'exclamation-circle-large' => 'error_outline',
				               'exclamation-triangle'     => 'warning',
				               'folder-close'             => 'folder',
				               'laquo'                    => 'skip_previous',
				               'lsaquo'                   => 'navigate_before',
				               'list'                     => 'list',
				               'locate'                   => 'near_me',
				               'lock'                     => 'lock',
				               'marker'                   => 'fmd-good',
				               'minus'                    => 'remove',
				               'move'                     => 'open_with',
				               'ok'                       => 'done',
				               'ok-circle'                => 'check_circle_outline',
				               'plus'                     => 'add',
				               'plus-circle'              => 'add_circle_outline',
				               'print' => 'print',
				               'question'                 => 'help',
				               'raquo'                    => 'skip_next',
				               'rsaquo'                   => 'navigate_next',
				               'redo'                     => 'redo',
				               'refresh'                  => 'refresh',
				               'refresh-spin'             => 'refresh',
				               'remove'                   => 'cancel',
				               'remove-circle'            => 'remove_circle_outline',
				               'save'                     => 'save',
				               'search'                   => 'search',
				               'share'                    => 'share',
				               'signin'                   => 'login',
				               'signout'                  => 'logout',
				               'spinner'                  => 'cached',
				               'spinner-large'            => 'cached',
				               'square'                   => 'check_box_outline_blank',
				               'star'                     => 'star',
				               'thumbs-down'              => 'thumb_down',
				               'thumbs-up'                => 'thumb_up',
				               'time'                     => 'access_time',
				               'trash'                    => 'delete',
				               'unchecked'                => 'check_box_outline_blank',
				               'unlock'                   => 'lock_open',
				];

				if ( !array_key_exists( $icon, $iconArray ) ) {
					$icon = 'question';
				}
			$iconString = $iconArray[ $icon ];
				if ( $full ) {
					$size = C::ES;
					if ( array_key_exists( $icon, $sizeArray ) ) {
						$size = $sizeArray[ $icon ];
					}
					$iconString = "<i class=\"material-icons$size $class\" aria-hidden=\"true\">$iconString</i>";
				}
				break;
		}

		//SPConfig::debOut( $iconString );

		return $iconString;
	}

	/**
	 * Creates a (json encoded) array of all defined icons to be used in javascript.
	 *
	 * @param string $font
	 * @param bool $json
	 *
	 * @return array|false|string
	 * @throws \SPException
	 */
	public static function getIcons( string $font = C::ES, bool $json = true )
	{
		if ( !$font ) {
			$font = self::getFont();
		}

		$allIcons = [ 'ban', 'bars', 'blank-circle', 'calendar', 'checked', 'arrow-down', 'arrow-left', 'arrow-right', 'arrow-up', 'arrow-circle-down', 'arrow-circle-left', 'arrow-circle-right', 'arrow-circle-up', 'caret-down', 'caret-left', 'caret-right', 'caret-up', 'caret-square-down', 'caret-square-left', 'caret-square-right', 'caret-square-up', 'chevron-down', 'chevron-left', 'chevron-right', 'chevron-up', 'chevron-circle-down', 'chevron-circle-left', 'chevron-circle-right', 'chevron-circle-up', 'clone', 'download', 'edit', 'envelope', 'eye', 'eye-close', 'exclamation-circle-large', 'exclamation-circle', 'exclamation-triangle', 'folder', 'laquo', 'lsaquo', 'list', 'locate', 'lock', 'marker', 'minus', 'move', 'ok', 'ok-circle', 'plus', 'plus-circle', 'print', 'question', 'raquo', 'rsaquo', 'redo', 'refresh', 'refresh-spin', 'remove', 'remove-circle', 'save', 'search', 'share', 'signin', 'signout', 'spinner', 'spinner-large', 'square', 'star', 'thumbs-down', 'thumbs-up', 'time', 'trash', 'unchecked', 'unlock' ];

		foreach ( $allIcons as $icon ) {
			$icons[ $icon ] = self::Icon( $icon, $font );
		}

		return $json ? json_encode( $icons ) : $icons;
	}

	/**
	 * Method to initialize SobiPro from outside.
	 *
	 * @param int $sid - section id
	 *
	 * @throws Exception
	 */
	public static function Initialise( $sid = 0, $adm = false )
	{
		static $loaded = false;

		if ( !$loaded ) {
			if ( $adm ) {
				require_once( JPATH_ADMINISTRATOR . '/components/com_sobipro/sobiconst.php' );
			}
			else {
				require_once( JPATH_SITE . '/components/com_sobipro/sobiconst.php' );
			}
			require_once( SOBI_PATH . '/lib/base/fs/loader.php' );
			include_once SOBI_PATH . '/Library/Autoloader.php';
			SobiPro\Autoloader::Instance()->register();

			SPLoader::loadController( 'sobipro' );
			SPLoader::loadController( 'interface' );
			SPLoader::loadClass( 'base.exception' );
			SPLoader::loadClass( 'base.const' );
			SPLoader::loadClass( 'base.object' );
			SPLoader::loadClass( 'base.filter' );
			SPLoader::loadClass( 'base.request' );
			SPLoader::loadClass( 'cms.base.lang' );
			SPLoader::loadClass( 'models.dbobject' );
			SPLoader::loadClass( 'base.factory' );
			SPLoader::loadClass( 'base.config' );
//			SPLoader::loadClass( 'cms.base.fs' );

			// in case it is a CLI call
			if ( isset( $_SERVER[ 'REQUEST_URI' ] ) ) {
				SPFactory::config()->set( 'live_site', Uri::root() );
			}
			/* Initialize the framework messages */
			Framework::SetTranslator( [ 'SPlang', 'txt' ] );
			Framework::SetErrorTranslator( [ 'SPlang', 'error' ] );
			Framework::SetConfig( [ 'Sobi', 'Cfg' ] );
			$loaded = true;
		}

		if ( $sid ) {
			$section = null;
			try {
				$relations = SPFactory::getCategoryRelations( $sid );
				if ( count( $relations ) ) {
					SPFactory::registry()->set( 'current_relations', $relations );
					foreach ( $relations as $pid => $path ) {
						if ( count( $path ) ) {
							foreach ( $path as $parent ) {
								if ( $parent[ 'type' ] == 'section' ) {
									$section = SPFactory::object( $parent[ 'id' ] );
									// when a section found, jump out of both loops
									break 2;
								}
							}
						}
					}
				}
				else {
					$type = Factory::Db()
						->select( 'oType', 'spdb_object', [ 'id' => $sid ] )
						->loadResult();
					if ( $type == 'section' ) {
						SPFactory::registry()->set( 'current_section', $sid );
					}
					elseif ( Sobi::Cfg( 'debug', false ) ) {
						trigger_error( sprintf( 'Object with the id %s does not return any results. Most likely an invalid relation.', $sid ), C::NOTICE );
					}
				}
			}
			catch ( Sobi\Error\Exception $x ) {
				$sectionId = SPFactory::config()->getSectionLegacy( $pid );
				if ( $sectionId ) {
					$section = SPFactory::object( $sectionId );
				}
			}

			/* set the current section in the registry */
			if ( $section ) {
				SPFactory::registry()->set( 'current_section', $section->id );
			}
		}

		/* load basic configuration settings */
		$config = &SPFactory::config();
		$config->addIniFile( 'etc.config', true );
		$config->addIniFile( 'etc.base', true );

		$config->addTable( 'spdb_config', $sid );

		/* initialise interface config setting */
		SPFactory::mainframe()->getBasicCfg();

		/* initialise config */
		$config->init();
	}

	/**
	 * @param string|array $icon
	 * @param string $replace
	 */
	public static function cleanCategoryIcon( &$icon, string $replace = C::ES )
	{
		if ( is_array( $icon ) ) {
			foreach ( $icon as $index => $element ) {
				$element = str_replace( "fa-3x", $replace, $element );
				$element = str_replace( "3x", $replace, $element );
				$element = str_replace( "fa-4x", $replace, $element );
				$element = str_replace( "4x", $replace, $element );
				$element = str_replace( "fa-2x", $replace, $element );
				$element = str_replace( "2x", $replace, $element );
				$element = str_replace( "lg", $replace, $element );
				$icon[ $index ] = $element;
			}

			array_walk( $icon, 'Sobi::trimAll' );
		}
		else {
			$icon = str_replace( "fa-3x", $replace, $icon );
			$icon = str_replace( "3x", $replace, $icon );
			$icon = str_replace( "fa-4x", $replace, $icon );
			$icon = str_replace( "4x", $replace, $icon );
			$icon = str_replace( "fa-2x", $replace, $icon );
			$icon = str_replace( "2x", $replace, $icon );
			$icon = str_replace( "lg", $replace, $icon );
			$icon = trim( $icon );
		}
	}

	/**
	 * Callback to trim all elements of an array.
	 *
	 * @param string $value
	 */
	public static function trimAll( string &$value )
	{
		$value = trim( $value );
	}

	/**
	 * Sets the browser title from template on frontend.
	 *
	 * @param string|array $title
	 * @param bool $add
	 *
	 * @return void
	 * @throws \SPException
	 */
	public static function Title( $title, bool $add = true )
	{
		if ( $add ) {
			SPFactory::header()->addTitle( $title, [], true );
		}
		else {
			SPFactory::header()->setTitle( $title );
		}
	}

	/**
	 * @param $childs
	 */
	public static function sort( &$childs )
	{
		foreach ( $childs as &$array ) {
			if ( count( $array[ 'childs' ] ) ) {
				self::sort( $array[ 'childs' ] );
				usort( $array[ 'childs' ], [ 'Sobi', 'reorder' ] );
			}
		}
	}

	/**
	 * @param $from
	 * @param $to
	 *
	 * @return int
	 */
	protected static function reorder( $from, $to )
	{
		if ( self::$filter != C::ES ) {
			if ( self::$filter[ 1 ] == 'asc' ) {
				if ( !is_numeric( $from[ self::$filter[ 0 ] ] ) ) {
					return strcmp( $from[ self::$filter[ 0 ] ], $to[ self::$filter[ 0 ] ] );
				}
				else {
					return $from[ self::$filter[ 0 ] ] - $to[ self::$filter[ 0 ] ];
				}
			}
			else {
				if ( !is_numeric( $to[ self::$filter[ 0 ] ] ) ) {
					return strcmp( $to[ self::$filter[ 0 ] ], $from[ self::$filter[ 0 ] ] );
				}
				else {
					return $to[ self::$filter[ 0 ] ] - $from[ self::$filter[ 0 ] ];
				}
			}
		}

		return 0;
	}

	/**
	 * @param array $oldArray
	 * @param $index
	 * @param int $order
	 *
	 * @return array
	 */
	public static function sortByIndex( array $oldArray, $index, int $order = SORT_ASC ): array
	{
		$newArray = $sortedArray = [];
		if ( count( $oldArray ) > 0 ) {
			foreach ( $oldArray as $key => $value ) {
				if ( is_array( $value ) ) {
					foreach ( $value as $key2 => $value2 ) {
						if ( $key2 == $index ) {
							$sortedArray[ $key ] = $value2;
						}
					}
				}
				else {
					$sortedArray[ $key ] = $value;
				}
			}

			switch ( $order ) {
				case SORT_ASC:
					asort( $sortedArray );
					break;
				case SORT_DESC:
					arsort( $sortedArray );
					break;
			}

			foreach ( $sortedArray as $key => $value ) {
				$newArray[ $key ] = $oldArray[ $key ];
			}
		}

		return $newArray;
	}

	/**
	 * @param $val
	 *
	 * @return int
	 */
	public static function getBytes( $val ): int
	{
		if ( empty( $val ) ) {
			return 0;
		}

		$val = trim( $val );

		preg_match( '#([0-9]+)[\s]*([a-z]+)#i', $val, $matches );

		$last = C::ES;

		if ( isset( $matches[ 2 ] ) ) {
			$last = $matches[ 2 ];
		}

		if ( isset( $matches[ 1 ] ) ) {
			$val = (int) $matches[ 1 ];
		}

		/* intentionally no breaks! */
		switch ( strtolower( $last ) ) {
			case 'g':
			case 'gb':
				$val *= 1024;
			case 'm':
			case 'mb':
				$val *= 1024;
			case 'k':
			case 'kb':
				$val *= 1024;
		}

		return (int) $val;
	}

	/**
	 * @param array $params
	 * @param string $param
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public static function getParam( array $params, string $param, $default = C::ES )
	{
		return $params && array_key_exists( $param, $params ) && $params[ $param ] ? $params[ $param ] : ( $default ? : C::ES );
	}
}
