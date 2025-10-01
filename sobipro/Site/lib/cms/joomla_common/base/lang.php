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
 * @created 20-Jun-2009 by Radek Suski
 * @modified 22 August 2024 by Sigrid Suski
 */

use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Factory as JFactory;
use Sobi\Application\Text;
use Sobi\C;
use Sobi\Lib\Factory;
use Sobi\Lib\Instance;
use Sobi\Utils\StringUtils;

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

/**
 * Class SPLang
 */
class SPLang extends Text
{
	use Instance;

	/**
	 * @var string
	 */
	protected $extension = 'com_sobipro';
	/**
	 * @var string
	 */
	protected $prefix = 'SP';

	/**
	 * Translates a given string.
	 *
	 * @return string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function _()
	{
		return self::Instance()->_txt( func_get_args() );
	}

	/**
	 * Translates a given string additionally from the default template .
	 *
	 * @return string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function _tmpl()
	{
		return self::Instance()->_templatetxt( func_get_args() );
	}

	/**
	 * Translates a given string.
	 *
	 * @param array | string $params
	 *
	 * @return string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function _txt( $params ): string
	{
		if ( !$this->_loaded ) {
			$this->_load();
		}
		$arguments = func_get_args();
		if ( is_array( $arguments[ 0 ] ) ) {
			$arguments = $arguments[ 0 ];
		}
		/* check if there are several texts to show at once */
		if ( strpos( $arguments[ 0 ], '+' ) > 0 ) {
			$texts = explode( '+', $arguments[ 0 ] );
			$text = null;
			foreach ( $texts as $item ) {
				$arguments[ 0 ] = $item;
				$text .= $this->translateHelper( $arguments );
			}
		}
		else {
			$text = $this->translateHelper( $arguments );
		}

		return $text;
	}

	/**
	 * Translates a given string additionally from the default template file.
	 *
	 * @param $params
	 *
	 * @return string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function _templatetxt( $params ): string
	{
		$this->_loadtemplate();

		return $this->_txt( $params );
	}

	/**
	 * @param $arguments
	 *
	 * @return string|string[]
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function translateHelper( $arguments )
	{
		if ( ( strpos( $arguments[ 0 ], "'" ) !== false && strpos( $arguments[ 0 ], "'" ) == 0 ) ) {
			$arguments[ 0 ] = substr( substr( $arguments[ 0 ], 0, -1 ), 1 );
		}
		$arguments[ 0 ] = $arguments[ 0 ] ? : C::ES;
		$in = $arguments[ 0 ];
		$over = $this->tplOverride( $arguments[ 0 ] );
		if ( !$over ) {
			$arguments[ 0 ] = 'SP.' . $arguments[ 0 ];
			$text = call_user_func_array( [ 'Joomla\CMS\Language\Text', '_' ], [ $arguments[ 0 ] ] );
			if ( $text == $arguments[ 0 ] || $text == 'SP.' ) {
				$text = $in;
			}
			$test = $this->tplOverride( $text );
			if ( $test ) {
				$text = $test;
			}
		}
		else {
			$text = $arguments[ 0 ] = $over;
		}

		/* if there were some parameters */
		if ( count( $arguments ) > 1 ) {
			if ( is_array( $arguments[ 1 ] ) ) {
				foreach ( $arguments[ 1 ] as $key => $value ) {
					if ( !strlen( (string) $value ) ) {
						$value = C::ES;
					}
					$text = str_replace( "var:[$key]", $value, $text );
				}
			}
			else {
				$arguments[ 0 ] = $text;
				$text = call_user_func_array( 'sprintf', $arguments );
			}
		}
		if ( strstr( $text, 'translate:' ) ) {
			$this->translation( $text );
		}
		if ( strstr( $text, '[JS]' ) || strstr( $in, '[JS]' ) ) {
			$text = str_replace( "\n", '\n', $text );
		}
		$text = str_replace( '\_QQ_', '"', $text );
		$text = str_replace( '_QQ_', '"', $text );

		return str_replace( [ '[JS]', '[MSG]', '[URL]' ], C::ES, $text );
	}

	/**
	 * @param string $term
	 *
	 * @return string|false
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function tplOverride( string $term )
	{
		if ( !class_exists( 'Sobi' ) || !Sobi::Section() ) {
			return false;
		}
		static $loaded = false;
		/* try this once */
		if ( !$loaded ) {
			$loaded = true;
			if ( Sobi::Cfg( 'section.template' ) ) {
				$template = SPLoader::translatePath( SPC::TEMPLATE_PATH . Sobi::Cfg( 'section.template', SPC::DEFAULT_TEMPLATE ) . '/translation', 'front', true, 'xml' );
				/* if the template provide it */
				if ( $template ) {
					$this->loadTemplateOverride( $template, Sobi::Lang( false ) );
				}
			}
		}

		return $this->templateOverride( $term );
	}

	/**
	 * Error messages.
	 *
	 * @return mixed
	 * @throws \Sobi\Error\Exception
	 */
	public static function e()
	{
		static $loaded = false;
		if ( !$loaded ) {
			Factory::Application()->loadLanguage( 'com_sobipro.err' );
		}
		$arguments = func_get_args();

		return call_user_func_array( [ self::Instance(), '_txt' ], $arguments );
	}

	/**
	 * @return void
	 */
	protected function _eload()
	{
		if ( $this->_lang != 'en-GB' && Sobi::Cfg( 'lang.engb_preload', true ) ) {
			JFactory::getApplication()->getLanguage()->load( 'com_sobipro.err', JPATH_SITE, 'en-GB' );
		}
		JFactory::getApplication()->getLanguage()->load( 'com_sobipro.err', JPATH_SITE );
	}

	/**
	 * Loads the language files always needed, which are:
	 * Front-end and back-end main language files and the accessibility language file
	 */
	protected function _load()
	{
		/* load default language files */
		if ( $this->_lang != 'en-GB' && Sobi::Cfg( 'lang.engb_preload', true ) ) {
			JFactory::getApplication()->getLanguage()->load( 'com_sobipro', JPATH_SITE, 'en-GB' );
			JFactory::getApplication()->getLanguage()->load( 'com_sobipro', JPATH_BASE, 'en-GB' );
			JFactory::getApplication()->getLanguage()->load( 'com_sobipro.accessibility', JPATH_SITE, 'en-GB' );
		}
		JFactory::getApplication()->getLanguage()->load( 'com_sobipro', JPATH_BASE, $this->_lang, true );
		JFactory::getApplication()->getLanguage()->load( 'com_sobipro', JPATH_SITE, $this->_lang, true );
		JFactory::getApplication()->getLanguage()->load( 'com_sobipro.accessibility', JPATH_SITE, $this->_lang, true );
		$this->_loaded = true;
	}

	/**
	 * Loads the language files always needed, which are:
	 * Front-end and back-end main language files and the accessibility language file
	 * and the default template file.
	 *
	 * @throws \SPException
	 */
	protected function _loadtemplate()
	{
		/* first load the standard files */
		if ( !$this->_loaded ) {
			$this->_load();
		}
		/* then load the default template language */
		if ( $this->_lang != 'en-GB' && Sobi::Cfg( 'lang.engb_preload', true ) ) {
			JFactory::getApplication()->getLanguage()->load( 'SpTpl.default', JPATH_ADMINISTRATOR, 'en-GB' );
		}
		JFactory::getApplication()->getLanguage()->load( 'SpTpl.default', JPATH_ADMINISTRATOR, $this->_lang, true );
	}

	/**
	 * Loads an additional language file.
	 *
	 * @param string $file
	 * @param string $lang
	 *
	 * @return bool
	 * @throws \Sobi\Error\Exception
	 */
	public static function load( string $file, string $lang = C::ES ): bool
	{
		return Factory::Application()->loadLanguage( $file, $lang );
	}

	/**
	 * Saves language-dependent data into the database.
	 * General method.
	 *
	 * @param array $values
	 * @param string $lang
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function saveValues( array $values, string $lang = C::ES )
	{
		$lang = $lang ? : Sobi::Lang();
		$defLang = Sobi::DefLang();

		if ( $values[ 'type' ] == 'plugin' ) {
			$values[ 'type' ] = 'application';
		}
		$data = [
			'sKey'        => $values[ 'key' ],
			'sValue'      => $values[ 'value' ],
			'section'     => $values[ 'section' ] ?? 0,
			'language'    => $lang,
			'oType'       => $values[ 'type' ],
			'fid'         => $values[ 'fid' ] ?? 0,
			'id'          => $values[ 'id' ] ?? 0,
			'params'      => $values[ 'params' ] ?? C::ES,
			'options'     => $values[ 'options' ] ?? C::ES,
			'explanation' => $values[ 'explanation' ] ?? C::ES,
		];

		try {
			/* save the data in the set language */
			Factory::Db()->replace( 'spdb_language', $data );

			/* multilingual mode handling in backend  */
			if ( Sobi::Cfg( 'lang.multimode', false ) && defined( 'SOBIPRO_ADM' ) ) {
				/* if we are saving the data in default language */
				if ( $lang == $defLang ) {
					$languages = SPLang::availableLanguages();
					if ( $languages ) {
						foreach ( $languages as $language => $short ) {
							if ( $language != $defLang ) {
								/* save the data of the default language for all other languages if they have not already a value set */
								$data[ 'language' ] = $language;
								Factory::Db()->insert( 'spdb_language', $data, true );
							}
						}
					}
				}
			}

			/* non multilingual mode or saving from frontend */
			else {
				/* if the data was not saved in the default language, save it also in default language  */
				if ( $lang != $defLang ) {
					$data[ 'language' ] = $defLang;
					Factory::Db()->insert( 'spdb_language', $data, true );
				}
			}
		}
		catch ( SPException $x ) {
			throw new SPException( sprintf( 'Cannot save language data. Error: %s', $x->getMessage() ) );
		}
	}

	/**
	 * Parses text and replaces placeholders.
	 *
	 * @param string $text
	 * @param null $obj
	 * @param bool $html
	 * @param bool $dropEmpty
	 *
	 * @return string|string[]
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function replacePlaceHolders( string $text, $obj = null, bool $html = false, bool $dropEmpty = false )
	{
		preg_match_all( '/{([a-zA-Z0-9\-_\:\.\%\s]+)}/', $text, $placeHolders );
		if ( count( $placeHolders[ 1 ] ) ) {
			foreach ( $placeHolders[ 1 ] as $placeHolder ) {
				$replacement = null;
				switch ( $placeHolder ) {
					case 'section':
					case 'section.id':
					case 'section.name':
						$replacement = Sobi::Section( ( $placeHolder == 'section' || $placeHolder == 'section.name' ) );
						break;
					case 'token':
						$replacement = Factory::Application()->token();
						break;
					default:
						if ( strstr( $placeHolder, 'date%' ) ) {
							$date = explode( '%', $placeHolder );
							$replacement = date( $date[ 1 ] );
							break;
						}
						if ( strstr( $placeHolder, 'cfg:' ) ) {
							$replacement = Sobi::Cfg( str_replace( 'cfg:', C::ES, $placeHolder ) );
							break;
						}
						else {
							if ( strstr( $placeHolder, 'messages' ) ) {
								$obj = SPFactory::registry()->get( 'messages' );
							}
							$replacement = self::parseVal( $placeHolder, $obj, $html );
						}
				}
				if ( $replacement && ( is_string( $replacement ) || is_numeric( $replacement ) ) ) {
					$text = str_replace( '{' . $placeHolder . '}', ( string ) $replacement, $text );
				}
				else {
					if ( $replacement && ( is_array( $replacement ) ) ) {
						$replacement = implode( ',', $replacement );
						$text = str_replace( '{' . $placeHolder . '}', ( string ) $replacement, $text );
					}
					else {
						if ( $dropEmpty ) {
							$text = str_replace( '{' . $placeHolder . '}', C::ES, $text );
						}
					}
				}
			}
		}

		return $text;
	}

	/**
	 * @param $label
	 * @param $obj
	 * @param bool $html
	 *
	 * @return mixed|SPField|string|null
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected static function parseVal( $label, $obj, bool $html = false )
	{
		if ( strstr( $label, '.' ) ) {
			$properties = explode( '.', $label );
		}
		else {
			$properties[ 0 ] = $label;
		}
		$var =& $obj;
		foreach ( $properties as $property ) {
			if ( ( $var && !is_array( $var ) && ( ( $var instanceof SPDBObject ) || ( method_exists( $var, 'get' ) ) ) ) ) {
				if ( strstr( $property, 'field_' ) && $var instanceof SPEntry ) {
					$field = $var->getField( $property );
					if ( $field && method_exists( $field, 'data' ) ) {
						$var = $field->data();
						if ( $field->get( 'type' ) == "category" ) {
							$cats = '<ul>';
							foreach ( $var as $cat ) {
								$cats .= "<li>" . htmlentities( SPLang::translateObject( $cat, 'name', 'category' )[ $cat ][ 'name' ], ENT_SUBSTITUTE ) . "</li>";
							}
							$var = $cats . '</ul>';
						}
					}
					else {
						return null;
					}
				}
				// after an entry has been saved this attribute is being emptied
				else {
					if ( ( $property == 'name' ) && ( $var instanceof SPEntry ) && !( strlen( $var->get( $property ) ) ) ) {
						$var = $var->getField( ( int ) Sobi::Cfg( 'entry.name_field' ) );
						if ( $var ) {
							$var = $var->data( $html );
						}
					}
					/** For the placeholder we need for sure the full URL */
					else {
						if ( ( $property == 'url' ) && ( $var instanceof SPEntry ) ) {
							$var = Sobi::Url( [ 'title' => Sobi::Cfg( 'sef.alias', true ) ? $var->get( 'nid' ) : $var->get( 'name' ), 'pid' => $var->get( 'primary' ), 'sid' => $var->get( 'id' ) ], false, true, true );
						}
						else {
							$var = $var->get( $property );
						}
					}
				}
			}
			else {
				if ( is_array( $var ) && isset( $var[ $property ] ) ) {
					$var = $var[ $property ];
				}
				else {
					if ( $var instanceof stdClass ) {
						$var = $var->$property;
					}
					else {
						$var = null;    // in case the placeholder is set wrongly
					}
				}
			}
		}

		return $var;
	}

	/**
	 * Gets a translatable values from the language DB.
	 *
	 * @param string $key
	 * @param string $type
	 * @param $sid
	 * @param string $select
	 * @param string $lang
	 * @param $fid
	 * @param $id
	 *
	 * @return array|mixed|string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function getValue( $key, $type, $sid = 0, $select = 'sValue', $lang = C::ES, $fid = 0, $id = 0 )
	{
		$value = C::ES;
		$select = $select ? : 'sValue';
		$lang = $lang ? : Sobi::Lang( false );
		if ( $type == 'plugin' ) {
			$type = 'application';
		}
		if ( !is_array( $select ) ) {
			$toSselect = [ $select ];
		}
		try {
			$toSselect[] = 'language';
			$params = [
				'sKey'     => $key,
				'oType'    => $type,
				'language' => array_unique( [ $lang, Sobi::DefLang(), 'en-GB' ] ),
			];
			if ( $sid ) {
				$params[ 'section' ] = $sid;
			}
			if ( $fid ) {
				$params[ 'fid' ] = $fid;
			}
			if ( $id ) {
				$params[ 'id' ] = $id;
			}
			$value = Factory::Db()->select( $toSselect, 'spdb_language', $params )->loadAssocList( 'language' );
			if ( isset( $value[ $lang ] ) ) {
				$value = $value[ $lang ][ $select ];
			}
			else {
				if ( isset( $value[ Sobi::DefLang() ] ) ) {
					$value = $value[ Sobi::DefLang() ][ $select ];
				}
				else {
					if ( isset( $value[ 'en-GB' ] ) ) {
						$value = $value[ 'en-GB' ][ $select ];
					}
					else {
						if ( isset( $value[ 0 ] ) ) {
							$value = $value[ 0 ][ $select ];
						}
						else {
							$value = C::ES;
						}
					}
				}
			}
		}
		catch ( SPException $x ) {
			Sobi::Error( 'language', SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __CLASS__ );
		}

		return $value;
	}

	/**
	 * Loads the java script language file.
	 *
	 * @param bool $adm
	 *
	 * @return array
	 * @throws \Sobi\Error\Exception|\SPException
	 */
	public static function jsLang( $adm = false )
	{
		return self::Instance()->_jsLang( $adm );
	}

	/**
	 * Translates a given string.
	 * This function is used mostly from the admin templates and the config ini-files interpreter.
	 * Callback function for Framework::Txt()
	 *
	 * @return string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 * @internal param array $params
	 * @internal param string $message
	 *
	 */
	public static function txt()
	{
		return self::Instance()->_txt( func_get_args() );
	}

	/**
	 * Translates a given error string from the error file.
	 * Callback function for Framework::Error()
	 *
	 * @return mixed
	 * @throws \Sobi\Error\Exception|\SPException
	 */
	public static function error()
	{
		return self::Instance()->e( func_get_args() );
	}

	/**
	 * Register new language domain.
	 *
	 * @param string $domain
	 *
	 * @throws \Exception
	 */
	public static function registerDomain( $domain )
	{
		self::Instance()->_registerDomain( $domain );
	}

	/**
	 * Set the used language/locale.
	 *
	 * @param string $lang
	 *
	 * @return void
	 */
	public static function setLang( $lang )
	{
		self::Instance()->_setLang( $lang );
	}

	/**
	 * Returns all available languages.
	 *
	 * @return array
	 */
	public static function availableLanguages()
	{
		return LanguageHelper::getKnownLanguages();
	}

	/**
	 * Returns an alternative language (not Sobi::Lang() or Sobi::DefLang()) if available, otherwise Sobi::Lang().
	 *
	 * @return string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function alternativeLanguage()
	{
		$languages = LanguageHelper::getKnownLanguages();
		foreach ( $languages as $language ) {
			if ( ( $language[ 'tag' ] != Sobi::DefLang() ) && ( $language[ 'tag' ] != Sobi::Lang() ) ) {
				return $language[ 'tag' ];
			}
		}

		return Sobi::Lang();
	}

	/**
	 * Removes slashes from string.
	 *
	 * @param string $txt
	 *
	 * @return string
	 * @deprecated since 2.0
	 */
	public static function clean( string $txt ): string
	{
		return StringUtils::Clean( $txt );
	}

	/**
	 * Creates JS friendly script.
	 *
	 * @param string $txt
	 *
	 * @return string
	 * @deprecated since 2.0
	 */
	public static function js( $txt )
	{
		return StringUtils::Js( $txt );
	}

	/**
	 * Singleton.
	 * @return mixed
	 * @deprecated since 2.0
	 * @use SPLang::instance();
	 */
	public static function & getInstance()
	{
		return self::Instance();
	}

	/**
	 * Returns correctly formatted currency amount.
	 *
	 * @param double $value - amount
	 * @param bool $currency
	 *
	 * @return array|mixed|string|string[]|null
	 * @throws \Sobi\Error\Exception
	 * @deprecated since 2.0
	 */
	public static function currency( $value, $currency = true )
	{
		return StringUtils::Currency( $value, $currency );
	}

	/**
	 * Used for XML nodes creation.
	 * Creates singular form from plural.
	 *
	 * @param string $txt
	 *
	 * @return string
	 * @deprecated since 2.0
	 */
	public static function singular( $txt )
	{
		return StringUtils::Singular( $txt );
	}

	/**
	 * Replaces HTML entities to valid XML entities.
	 *
	 * @param $txt
	 * @param bool $amp
	 *
	 * @return string|string[]|null
	 * @deprecated since 2.0
	 */
	public static function entities( $txt, $amp = false )
	{
		return StringUtils::Entities( $txt, $amp );
	}

	/**
	 * Creates URL saf string.
	 *
	 * @param $str
	 *
	 * @return string
	 * @deprecated since 2.0
	 * @use \Sobi\Utils\StringUtils::UrlSafe
	 */
	public static function urlSafe( $str )
	{
		return StringUtils::UrlSafe( $str );
	}

	/**
	 * Creates alias/nid suitable string.
	 *
	 * @param $txt
	 *
	 * @return string|null
	 * @throws \Sobi\Error\Exception
	 * @deprecated since 2.0
	 */
	public static function varName( $txt )
	{
		return StringUtils::VarName( $txt );
	}

	/**
	 * @param $txt
	 * @param bool $unicode
	 * @param bool $forceUnicode
	 *
	 * @return string
	 * @throws \Sobi\Error\Exception
	 * @deprecated since 2.0
	 */
	public static function nid( $txt, $unicode = false, $forceUnicode = false ): string
	{
		return StringUtils::Nid( $txt, $unicode, $forceUnicode );
	}

	/**
	 * Translates language dependent attributes of objects.
	 *
	 * @param array|int $sids - array with ids or id of objects to translate
	 * @param array|int $fields - (optional) array (or string) with properties names to translate. If not given, translates all
	 * @param string $type - (optional) type of object (section, category, entry). If not given, translates all
	 * @param string $lang - (optional) specific language. If not given, use currently set language
	 * @param string $ident
	 *
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function translateObject( $sids, $fields = [], $type = C::ES, $lang = C::ES, $ident = 'id' )
	{
		/** @todo multiple attr does not work because the id is the object id */
		$fields = is_array( $fields ) ? $fields : ( strlen( $fields ) ? [ $fields ] : null );
		$lang = $lang ? : Sobi::Lang( false );
		// we don't need to specify the language as we want to have all of them and then order it right
		// when an object name has been entered in a particular language but this language isn't used later
		// we won't have any label for this certain object
		// Wed, Dec 18, 2013 09:57:04
		//$params = array( 'id' => $sids, 'language' => array( $lang, Sobi::DefLang(), 'en-GB' ) );

		$params = [ $ident => $sids ];
		if ( $type ) {
			$params[ 'oType' ] = $type;
		}
		if ( in_array( 'alias', $fields ) ) {
			$fields[] = 'nid';
		}
		if ( $fields && count( $fields ) ) {
			$params[ 'sKey' ] = $fields;
		}

		//$params[ 'language' ] =  "'$lang', '" . Sobi::DefLang() . "'";
		static $store = [];
		if ( isset( $store[ $lang ][ json_encode( $params ) ][ $ident ] ) ) {
			return $store[ $lang ][ json_encode( $params ) ][ $ident ];
		}

		$result = [];
		try {
			/* get all labels in all languages */
			$labels = Factory::Db()
				->select( $ident . ' AS id, sKey AS label, sValue AS value, language',
					'spdb_language',
					$params,
					"FIELD( language, '$lang', '" . Sobi::DefLang() . "' )" )
				->loadAssocList();

			if ( count( $labels ) ) {
				$aliases = [];
				if ( in_array( 'alias', $fields ) ) {
					$aliases = Factory::Db()
						->select( [ 'nid', 'id', 'state' ], 'spdb_object', [ 'id' => $sids ] )
						->loadAssocList( 'id' );
				}
				foreach ( $labels as $label ) {
					if ( $label[ 'label' ] == 'nid' && ( !isset( $result[ $label[ 'id' ] ][ 'alias' ] ) || $label[ 'language' ] == $lang ) ) {
						$result[ $label[ 'id' ] ][ 'alias' ] = $label[ 'value' ];
					}
					else {
						if ( !isset( $result[ $label[ 'id' ] ] ) || $label[ 'language' ] == Sobi::Lang( false ) ) {
							$result[ $label[ 'id' ] ] = $label;
						}
					}
					if ( in_array( 'nid', $fields ) ) {
						if ( !isset( $result[ $label[ 'id' ] ][ 'alias' ] ) ) {
							$result[ $label[ 'id' ] ][ 'alias' ] = isset( $aliases[ $label[ 'id' ] ] ) ? $aliases[ $label[ 'id' ] ][ 'nid' ] : null;
						}
					}
					if ( in_array( 'state', $fields ) ) {
						$result[ $label[ 'id' ] ][ 'state' ] = isset( $aliases[ $label[ 'id' ] ] ) ? $aliases[ $label[ 'id' ] ][ 'state' ] : null;
					}
				}
				foreach ( $labels as $label ) {
					if ( !isset( $result[ $label[ 'id' ] ][ $label[ 'label' ] ] ) || $label[ 'language' ] == Sobi::Lang( false ) ) {
						$result[ $label[ 'id' ] ][ $label[ 'label' ] ] = $label[ 'value' ];
					}
				}
			}
			$store[ $lang ][ json_encode( $params ) ][ $ident ] = $result;
		}
		catch ( SPException $x ) {
			Sobi::Error( 'language', SPLang::e( 'CANNOT_TRANSLATE_OBJECT', $x->getMessage() ), C::WARNING, 500, __LINE__, __CLASS__ );
		}

		return $result;
	}
}