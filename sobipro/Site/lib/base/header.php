<?php
/**
 * @package SobiPro Library
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006–2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license   GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 08-Jul-2008 by Radek Suski
 * @modified 03 May 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory as JFactory;
use Sobi\C;
use Sobi\Input\Input;
use Sobi\FileSystem\FileSystem;
use Sobi\FileSystem\File;
use Sobi\Lib\Instance;
use Sobi\Lib\ParamsByName;
use Sobi\Utils\Arr;
use Sobi\Utils\StringUtils;

/**
 * Class SPHeader
 */
final class SPHeader
{
	use ParamsByName;
	use Instance;

	/*** @var array */
	private $head = [];
	/*** @var array */
	private $css = [];
	/*** @var array */
	private $cssFiles = [];
	/*** @var array */
	private $js = [];
	/*** @var array */
	private $links = [];
	/*** @var array */
	private $jsFiles = [];
	/*** @var array */
	private $author = [];
	/*** @var array */
	private $title = [];
	/*** @var array */
	private $robots = [];
	/*** @var array */
	private $description = [];
	/*** @var array */
	private $keywords = [];
	/*** @var array */
	private $raw = [];
	/*** @var int */
	private $count = 0;
	/*** @var array */
	private $_cache = [ 'js' => [], 'css' => [] ];
	/** @var array */
	private $_store = [];
	/** @var array */
	private $_checksums = [];

	/**
	 * @return \Sobi\Lib\Instance|\SPHeader
	 */
	public static function & getInstance(): self
	{
		return self::Instance();
	}

	/**
	 * Loads necessary utilities, Bootstrap, Font and jQuery files.
	 *
	 * @param bool $adm
	 *
	 * @return $this|SPHeader
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function & initBase( bool $adm = false ): self
	{
		static $initialised = false;

		if ( !$initialised && Input::Task() != 'txt.js' ) {
			$initialised = true;
			/* these files must always be loaded for front- and back-end!! */
			$this->addJsFile( [ 'core', 'bootstrap.utilities.bsversion', 'bootstrap.utilities.SigsiuModalBox', 'Tps.autoComplete', 'Tps.html5sortable' ] );

			/* for the back-end ... */
			if ( $adm ) {
				$this->addJsFile( [ 'Jquery.jquery-371-min', 'Jquery.jquery-migrate-341-min' ] );

				if ( SOBI_ORICMS == 'joomla5' ) {
					/* add here all Bootrap modules Joomla does no longer load */
					HTMLHelper::_( 'bootstrap.modal' );
					HTMLHelper::_( 'bootstrap.dropdown' );
				}

				/* for Joomla 3 ... */
				if ( SOBI_CMS != 'joomla4' ) {
					/* ... load Bootstrap 5 javascripts */
					$this->addJsFile( 'bootstrap.bootstrap5-bundle-min' );

					/* ... load Bootstrap 5 CSS file for Joomla 3 */
					JFactory::getApplication()->getLanguage()->isRtl() ? $this->addCssFile( 'adm.dirgiskedar-j3-rtl' ) : $this->addCssFile( 'adm.dirgiskedar-j3' );

					/* ... load the font CSS files (Font Awesome 6) */
					//$this->addCssFile( [ 'adm.dirgiskedarfont', 'adm.roboto' ] );
					$this->addCssFile( [ 'fonts.fontawesome.css.fontawesome', 'fonts.fontawesome.css.all', 'adm.roboto' ] );
				}

				/* for Joomla 4 ... */
				else {
					/* ... load Bootstrap 5 CSS file for Joomla 4 and 5 */
					JFactory::getApplication()->getLanguage()->isRtl() ? $this->addCssFile( 'adm.dirgiskedar-j4-rtl' ) : $this->addCssFile( 'adm.dirgiskedar-j4' );

					if ( SOBI_ORICMS == 'joomla4' ) {
						/* ... load the font CSS files (Font Awesome 6) for Joomla 4 */
						$this->addCssFile( [ 'fonts.fontawesome.css.fontawesome', 'fonts.fontawesome.css.all', 'adm.roboto' ] );
					}
				}

				/* load other necessary javascript files */
				$this->addJsFile( [ 'sobipro', 'adm.sobipro', 'Jquery.jqnc', 'adm.interface', 'adm.sigsiubox' ] );
			}

			/* for the front-end ... */
			else {
				$this->addCssFile( 'sobipro' );

				$fwLoad = Sobi::Cfg( 'template.framework-load', C::BOOTSTRAP_LOCAL );
				$fwStyle = Sobi::Cfg( 'template.framework-style', C::BOOTSTRAP5 );

				if ( $fwStyle == C::BOOTSTRAP5 ) {
					if ( Sobi::Cfg( 'template.jquery-load', false ) ) {
						/* Joomla 3 and 4 are loading jquery the same way */
						HTMLHelper::_( 'jquery.framework' );
						//$this->addJsFile( [ 'Jquery.jquery-360-min', 'Jquery.jquery-migrate-332-min' ] );
					}
					switch ( $fwLoad ) {
						case C::BOOTSTRAP_LOCAL:
							$this->addCssFile( 'bootstrap5' );
							$this->addJsFile( 'bootstrap.bootstrap5-bundle-min' );
							break;
						case C::BOOTSTRAP_CDN:
							$default = Sobi::Cfg( 'template.bs5_version' );
							$cdnversion = Sobi::Cfg( 'template.cdn-version' );
							$cdnversion = substr( $cdnversion, 0, 1 ) == '5' ? $cdnversion : $default;
							$link = str_replace( '{version}', $cdnversion, Sobi::Cfg( 'template.bootstrap_cdn_server', 'https://cdn.jsdelivr.net/npm/bootstrap@{version}/dist/' ) );
							$this->addHeadLink( $link . 'css/bootstrap.min.css', C::ES, C::ES, 'stylesheet' );
							$this->addJsUrl( $link . 'js/bootstrap.bundle.min.js' );
							break;
						case C::BOOTSTRAP_STYLES:
							$this->addCssFile( 'bootstrap5' );
							break;
					}
				}

				if ( $fwStyle == C::BOOTSTRAP4 ) {
					if ( Sobi::Cfg( 'template.jquery-load', false ) ) {
						/* Joomla 3 and 4 are loading jquery the same way */
						HTMLHelper::_( 'jquery.framework' );
						//$this->addJsFile( [ 'Jquery.jquery-360-min', 'Jquery.jquery-migrate-332-min' ] );
					}
					switch ( $fwLoad ) {
						case C::BOOTSTRAP_LOCAL:
							$this->addCssFile( 'bootstrap4' );
							$this->addJsFile( 'bootstrap.bootstrap4-bundle-min' );
							break;
						case C::BOOTSTRAP_CDN:
							$default = Sobi::Cfg( 'template.bs4_version' );
							$cdnversion = Sobi::Cfg( 'template.cdn-version' );
							$cdnversion = substr( $cdnversion, 0, 1 ) == '4' ? $cdnversion : $default;
							$link = str_replace( '{version}', $cdnversion, Sobi::Cfg( 'template.bootstrap_cdn_server', 'https://cdn.jsdelivr.net/npm/bootstrap@{version}/dist/' ) );
							$this->addHeadLink( $link . 'css/bootstrap.min.css', C::ES, C::ES, 'stylesheet' );
							$this->addJsUrl( $link . 'js/bootstrap.bundle.min.js' );
							break;
						case C::BOOTSTRAP_STYLES:
							$this->addCssFile( 'bootstrap4' );
							break;
					}
				}

				if ( $fwStyle == C::BOOTSTRAP3 ) {
					if ( Sobi::Cfg( 'template.jquery-load', false ) ) {
						$this->addJsFile( [ 'Jquery.jquery', 'Jquery.jquery-migrate-141-min' ] );
					}
					switch ( $fwLoad ) {
						case C::BOOTSTRAP_LOCAL:
							$this->addCssFile( 'bootstrap3' );
							$this->addJsFile( 'bootstrap.bootstrap3' );
							break;
						case C::BOOTSTRAP_CDN:
							$default = Sobi::Cfg( 'template.bs3_version' );
							$cdnversion = Sobi::Cfg( 'template.cdn-version' );
							$cdnversion = substr( $cdnversion, 0, 1 ) == '3' ? $cdnversion : $default;
							$link = str_replace( '{version}', $cdnversion, Sobi::Cfg( 'template.bootstrap_cdn_server', 'https://cdn.jsdelivr.net/npm/bootstrap@{version}/dist/' ) );
							$this->addHeadLink( $link . 'css/bootstrap.min.css', C::ES, C::ES, 'stylesheet' );
							$this->addJsUrl( $link . 'js/bootstrap.min.js' );
							break;
						case C::BOOTSTRAP_STYLES:
							$this->addCssFile( 'bootstrap3' );
							break;
					}
				}

				if ( $fwStyle == C::BOOTSTRAP2 ) {
					if ( Sobi::Cfg( 'template.jquery-load', false ) ) {
						$this->addJsFile( [ 'Jquery.jquery', 'Jquery.jquery-migrate-141-min' ] );
					}
					/* Bootstrap 2 CDN is not available, local used */
					if ( $fwLoad == C::BOOTSTRAP_LOCAL || C::BOOTSTRAP_CDN ) {
						$this->addCssFile( 'bootstrap2' );
						$this->addJsFile( 'bootstrap.bootstrap2' );
					}
					if ( $fwLoad == C::BOOTSTRAP_STYLES ) {
						$this->addCssFile( 'bootstrap2' );
					}
				}
				$this->addJsFile( [ 'sobipro', 'Jquery.jqnc' ] );

				/* load Font Awesome */
				$fonts = Sobi::Cfg( 'template.icon_fonts_arr', [] );
				if ( is_array( $fonts ) && count( $fonts ) ) {
					if ( Sobi::Cfg( 'template.icon_fonts_load', '1' ) == '1' && ( !defined( 'SOBIPRO_ADM' ) ) ) {
						foreach ( $fonts as $font ) {
							/* Font Awesome 6 not available in CDN, only as self-hosting */
							if ( $font == 'font-awesome-6' ) {
								$this->addCssFile( [ 'fonts.fontawesome.css.fontawesome', 'fonts.fontawesome.css.all' ] );
							}
							else {
								$fontlink = Sobi::Cfg( 'icon-fonts.' . $font );
								if ( $fontlink ) {
									$this->addHeadLink( $fontlink, C::ES, C::ES, 'stylesheet' );
								}
							}
						}
					}
				}
			}
		}

		return $this;
	}

	/**
	 * @param $args
	 * @param $id
	 */
	protected function store( $args, $id )
	{
		if ( isset( $args[ 'this' ] ) ) {
			unset( $args[ 'this' ] );
		}
		$this->_store[ $id ][] = $args;
	}

	/**
	 * Adds raw code to the site header.
	 *
	 * @param string $html
	 *
	 * @return SPHeader
	 */
	public function & add( string $html ): self
	{
		$checksum = md5( $html );
		if ( !isset( $this->_checksums[ __FUNCTION__ ][ $checksum ] ) ) {
			$this->_checksums[ __FUNCTION__ ][ $checksum ] = true;
			$this->raw[ ++$this->count ] = $html;
			$this->store( get_defined_vars(), __FUNCTION__ );
		}

		return $this;
	}

	/**
	 * @param string $name
	 * @param string $content
	 * @param array $attributes
	 *
	 * @return SPHeader
	 * @deprecated @see SPHeader::meta
	 */
	public function & addMeta( string $name, string $content, array $attributes = [] ): self
	{
//		$checksum = md5( json_encode( get_defined_vars() ) );
//		if ( !( isset( $this->_checksums[ __FUNCTION__ ][ $checksum ] ) ) ) {
//			$this->_checksums[ __FUNCTION__ ][ $checksum ] = true;
//			$this->store( get_defined_vars(), __FUNCTION__ );
//			$custom = C::ES;
//			if ( count( $attributes ) ) {
//				foreach ( $attributes as $attribute => $value ) {
//					$custom .= $attribute . '="' . $value . '"';
//				}
//			}
//			if ( strlen( $name ) ) {
//				$name = " name=\"$name\" ";
//			}
//			$this->raw[ ++$this->count ] = "<meta$name content=\"$content\" $custom/>";
//		}

		return $this;
	}

	/**
	 * Adds JavaScript code to the site header.
	 *
	 * @param string $content
	 * @param string $name
	 * @param array $attributes
	 *
	 * @return $this|SPHeader
	 * @internal param string $js
	 */
	public function & meta( string $content, string $name = C::ES, array $attributes = [] ): self
	{
		$checksum = md5( json_encode( get_defined_vars() ) );
		if ( !isset( $this->_checksums[ __FUNCTION__ ][ $checksum ] ) ) {
			$this->_checksums[ __FUNCTION__ ][ $checksum ] = true;
			$this->store( get_defined_vars(), __FUNCTION__ );
			$custom = C::ES;
			if ( strlen( $name ) ) {
				$name = "name=\"$name\"";
			}
			if ( count( $attributes ) ) {
				foreach ( $attributes as $attr => $value ) {
					$custom .= $attr . '="' . $value . '"';
				}
			}
			$this->raw[ ++$this->count ] = "\n<meta $name content=\"$content\" $custom/>";
		}

		return $this;
	}

	/**
	 * Adds JavaScript code to the site header.
	 *
	 * @param string $js
	 *
	 * @return SPHeader
	 */
	public function & addJsCode( string $js ): self
	{
		$checksum = md5( json_encode( get_defined_vars() ) );
		if ( !isset( $this->_checksums[ __FUNCTION__ ][ $checksum ] ) ) {
			$this->_checksums[ __FUNCTION__ ][ $checksum ] = true;
			$this->store( get_defined_vars(), __FUNCTION__ );
			$this->js[ ++$this->count ] = $js;
		}

		return $this;
	}

	/**
	 * Adds a JavaScript file to the site header.
	 *
	 * @param string|array $script
	 * @param bool $adm
	 * @param string $params
	 * @param bool $force
	 * @param string $ext
	 * @param string $defer
	 *
	 * @return $this|SPHeader
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	public function & addJsFile( $script, bool $adm = false, string $params = C::ES, bool $force = false, string $ext = 'js', string $defer = 'defer' ): self
	{
		if ( is_array( $script ) && count( $script ) ) {
			foreach ( $script as $f ) {
				$this->addJsFile( $f, $adm, $params, $force, $ext, $defer );
			}
		}
		else {
			$checksum = md5( json_encode( get_defined_vars() ) );
			if ( !isset( $this->_checksums[ __FUNCTION__ ][ $checksum ] ) ) {
				$this->_checksums[ __FUNCTION__ ][ $checksum ] = true;
				$this->store( get_defined_vars(), __FUNCTION__ );
//				if ( SOBI_CMS == 'joomla3' ) {
//					if ( $script == 'jquery' ) {
//						HTMLHelper::_( 'jquery.framework' );
//
//						return $this;
//					}
//					if ( $script == 'bootstrap.bootstrap2' ) {
//						JHtml::_( 'bootstrap.framework' );
//
//						return $this;
//					}
//				}
				if ( $script == 'core' ) {
					$defer = C::ES;
				}
				$jsFile = SPLoader::JsFile( $script, $adm, true, false, $ext );
				if ( $jsFile ) {
					$override = false;
					$index = ++$this->count;
					// if this is a template JavaScript file - ensure it will be loaded after all others JavaScript files
					if ( Sobi::Reg( 'current_template' ) && ( strstr( dirname( $jsFile ), Sobi::Reg( 'current_template' ) ) ) ) {
						$index *= 100;
					}
					if (
						/* If there is already template defined */
						Sobi::Reg( 'current_template' )
						&& /* and we are NOT including js file from the template  */
						!strstr( dirname( $jsFile ), Sobi::Reg( 'current_template' ) )
						&& /* but there is such file (with the same name) in the template package  */
						FileSystem::Exists( Sobi::Reg( 'current_template' ) . '/js/' . basename( $jsFile ) )
						&& !strstr( dirname( $jsFile ), 'templates' )
					) {
						$jsFile = explode( '.', basename( $jsFile ) );
						$ext = $jsFile[ count( $jsFile ) - 1 ];
						unset( $jsFile[ count( $jsFile ) - 1 ] );
						$f = implode( '.', $jsFile );
						$jsFile = FileSystem::FixPath( SPLoader::JsFile( 'absolute.' . Sobi::Reg( 'current_template' ) . '/js/' . $f, $adm, true, true, $ext ) );
						$override = true;
						$index *= 100;
					}
					else {
						$jsFile = SPLoader::JsFile( $script, $adm, true, true, $ext );
					}
					if ( Sobi::Cfg( 'cache.include_js_files', false ) && !( $params || $force || $adm || defined( 'SOBIPRO_ADM' ) ) && $defer ) {
						if ( !$override ) {
							$jsFile = SPLoader::JsFile( $script, $adm, true, false, $ext );
						}
						if ( !in_array( $jsFile, $this->_cache[ 'js' ] ) || $force ) {
							$this->_cache[ 'js' ][ $index ] = $jsFile;
							ksort( $this->_cache[ 'js' ] );
						}
					}
					else {
						$params = $params ? '?' . $params : C::ES;
						$file = "\n<script $defer src=\"$jsFile$params\"></script>";
						if ( !in_array( $file, $this->jsFiles ) || $force ) {
							$this->jsFiles[ $index ] = $file;
							ksort( $this->jsFiles );
						}
					}
				}
				else {
					$file = SPLoader::JsFile( $script, $adm, false, true, $ext );
					Sobi::Error( 'add_js_file', SPLang::e( 'FILE_DOES_NOT_EXIST', $file ), C::NOTICE, 0, __LINE__, __CLASS__ );
				}
			}
		}

		return $this;
	}

	/**
	 * Adds external JavaScript file to the site header.
	 *
	 * @param string|array $file
	 * @param string $params
	 *
	 * @return SPHeader
	 */
	public function & addJsUrl( $file, string $params = C::ES ): self
	{
		if ( is_array( $file ) && count( $file ) ) {
			foreach ( $file as $f ) {
				$this->addJsUrl( $f );
			}
		}
		else {
			$checksum = md5( json_encode( get_defined_vars() ) );
			if ( !isset( $this->_checksums[ __FUNCTION__ ][ $checksum ] ) ) {
				$this->_checksums[ __FUNCTION__ ][ $checksum ] = true;
				$this->store( get_defined_vars(), __FUNCTION__ );

				$params = $params ? '?' . $params : C::ES;
				$file = "\n<script defer src=\"$file$params\"></script>";
				if ( !in_array( $file, $this->jsFiles ) ) {
					$this->jsFiles[ ++$this->count ] = $file;
				}
			}
		}

		return $this;
	}

	/**
	 * Creates temporary (variable) JavaScript file.
	 *
	 * @param $script
	 * @param $id
	 * @param $params
	 * @param bool $adm
	 *
	 * @return $this|SPHeader
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function & addJsVarFile( $script, $id, $params, bool $adm = false ): self
	{
		$this->store( get_defined_vars(), __FUNCTION__ );
		$checksum = md5( json_encode( get_defined_vars() ) );
		if ( !isset( $this->_checksums[ __FUNCTION__ ][ $checksum ] ) ) {
			$this->_checksums[ __FUNCTION__ ][ $checksum ] = true;
			$varFile = SPLoader::translatePath( "var/js/{$script}_$id", 'front', true, 'js' );
			if ( !$varFile ) {
				$file = SPLoader::JsFile( $script, $adm, true, false );
				if ( $file ) {
					$file = new File( $file );
					$fc =& $file->read();
					foreach ( $params as $key => $value ) {
						$fc = str_replace( "__{$key}__", $value, $fc );
					}
					$fc = str_replace( '__CREATED__', date( SPFactory::config()->key( 'date.log_format', 'D M j G:i:s T Y' ) ), $fc );
					$varFile = SPLoader::translatePath( "var/js/{$script}_$id", 'front', false, 'js' );
					$file->saveAs( $varFile );
				}
				else {
					Sobi::Error( __FUNCTION__, SPLang::e( 'CANNOT_LOAD_FILE_AT', $file ), C::NOTICE, 0, __LINE__, __FILE__ );
				}
			}
			if ( Sobi::Cfg( 'cache.include_js_files', false ) && !( $adm || defined( 'SOBIPRO_ADM' ) ) ) {
				$this->_cache[ 'js' ][ ++$this->count ] = $varFile;
			}
			else {
				$varFile = str_replace( SOBI_ROOT, SPFactory::config()->get( 'live_site' ), $varFile );
				$varFile = str_replace( '\\', '/', $varFile );
				$varFile = preg_replace( '|(\w)(//)(\w)|', '$1/$3', $varFile );
				$varFile = "\n<script defer type=\"text/javascript\" src=\"$varFile\"></script>";
				if ( !in_array( $varFile, $this->jsFiles ) ) {
					$this->jsFiles[ ++$this->count ] = $varFile;
				}
			}
		}

		return $this;
	}

	/**
	 * Adds CSS code to the site header.
	 *
	 * @param string $css
	 *
	 * @return SPHeader
	 */
	public function & addCSSCode( $css ): self
	{
		$checksum = md5( $css );
		if ( !isset( $this->_checksums[ __FUNCTION__ ][ $checksum ] ) ) {
			$this->_checksums[ __FUNCTION__ ][ $checksum ] = true;
			$this->store( get_defined_vars(), __FUNCTION__ );
			$this->css[ ++$this->count ] = $css;
		}

		return $this;
	}

	/**
	 * Adds CSS file to the site header.
	 *
	 * @param string|array $file
	 * @param false $adm
	 * @param string $media
	 * @param false $force
	 * @param string $ext
	 * @param string $params
	 *
	 * @return $this|SPHeader
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function & addCssFile( $file, bool $adm = false, string $media = C::ES, bool $force = false, string $ext = 'css', string $params = C::ES ): self
	{
		if ( is_array( $file ) && count( $file ) ) {
			foreach ( $file as $f ) {
				$this->addCssFile( $f, $adm, $media, $force, $ext, $params );
			}
		}
		else {
			$checksum = md5( json_encode( get_defined_vars() ) );
			if ( !isset( $this->_checksums[ __FUNCTION__ ][ $checksum ] ) ) {
				$this->_checksums[ __FUNCTION__ ][ $checksum ] = true;
				$this->store( get_defined_vars(), __FUNCTION__ );
				$cssFile = SPLoader::CssFile( $file, $adm, true, false, $ext );
				$index = ++$this->count;
				// if this is a template CSS file - ensure it will be loaded after all others CSS files
				if ( Sobi::Reg( 'current_template' ) && ( strstr( dirname( $cssFile ), Sobi::Reg( 'current_template' ) ) ) ) {
					$index *= 100;
				}
				if ( $file == 'Bootstrap2' ) { /* Bootstrap 2 */
					$fwLoad = Sobi::Cfg( 'template.framework-load' );
					if ( ( $fwLoad != C::BOOTSTRAP_NONE ) ) {
						/** we want bootstrap loaded as the very first because we have to override some things */
						$index = -100;
					}
					else {
						/** Not nice but it's just easier like this :/ */
						return $this;
					}
				}
				if ( $cssFile ) {
					$override = false;
					if (
						/* If there is already template defined */
						Sobi::Reg( 'current_template' )
						&& /* and we are NOT including css file from the template  */
						!strstr( dirname( $cssFile ), Sobi::Reg( 'current_template' ) )
						&& /* but there is such file (with the same name) in the template package  */
						FileSystem::Exists( Sobi::Reg( 'current_template' ) . '/css/' . basename( $cssFile ) )
						&& !strstr( dirname( $cssFile ), 'templates' )
					) {
						$cssFile = explode( '.', basename( $cssFile ) );
						$ext = $cssFile[ count( $cssFile ) - 1 ];
						unset( $cssFile[ count( $cssFile ) - 1 ] );
						$f = implode( '.', $cssFile );
						$cssFile = SPLoader::CssFile( 'absolute.' . Sobi::Reg( 'current_template' ) . '/css/' . $f, $adm, true, !( Sobi::Cfg( 'cache.include_css_files', false ) ), $ext );
						$override = true;
						$index *= 100;
					}
					else {
						$cssFile = SPLoader::CssFile( $file, $adm, true, true, $ext );
					}
					if ( Sobi::Cfg( 'cache.include_css_files', false ) && !( $params || $force || $adm || defined( 'SOBIPRO_ADM' ) ) ) {
						if ( !$override ) {
							$cssFile = SPLoader::CssFile( $file, $adm, true, false, $ext );
						}
						if ( !in_array( $cssFile, $this->_cache[ 'css' ] ) || $force ) {
							$this->_cache[ 'css' ][ $index ] = $cssFile;
							ksort( $this->_cache[ 'css' ] );
						}
					}
					else {
						$params = $params ? '?' . $params : C::ES;
						$media = $media ? "media=\"$media\"" : C::ES;
						$file = "\n<link rel=\"stylesheet\" href=\"$cssFile$params\" type=\"text/css\" $media />";
						if ( !in_array( $file, $this->cssFiles ) || $force ) {
							$this->cssFiles[ $index ] = $file;
							ksort( $this->cssFiles );
						}
					}
				}
				else {
					$file = SPLoader::CssFile( $file, $adm, false, false, $ext );
					Sobi::Error( 'add_css_file', SPLang::e( 'FILE_DOES_NOT_EXIST', $file ), C::NOTICE, 0, __LINE__, __CLASS__ );
				}
			}
		}

		return $this;
	}

	/**
	 * Adds alternate link to the site header.
	 *
	 * @param string $href
	 * @param string $type
	 * @param string $title
	 * @param string $rel
	 * @param string $relType
	 * @param string|array $params
	 *
	 * @return $this|SPHeader
	 */
	public function & addHeadLink( string $href, string $type = C::ES, string $title = C::ES, string $rel = 'alternate', string $relType = 'rel', $params = C::ES ): self
	{
		$checksum = md5( json_encode( get_defined_vars() ) );
		if ( !isset( $this->_checksums[ __FUNCTION__ ][ $checksum ] ) ) {
			$this->_checksums[ __FUNCTION__ ][ $checksum ] = true;
			$this->store( get_defined_vars(), __FUNCTION__ );
			$title = $title ? " title=\"$title\" " : C::ES;
			if ( $params && count( $params ) ) {
				$arrUtils = new Arr();
				$params = $arrUtils->ToString( $params );
			}
			if ( $type ) {
				$type = "type=\"$type\" ";
			}
			$href = preg_replace( '/&(?![#]?[a-z0-9]+;)/i', '&amp;', $href );
			$title = preg_replace( '/&(?![#]?[a-z0-9]+;)/i', '&amp;', $title );
			$this->links[] = "\n<link $relType=\"$rel\" href=\"$href\" $type$params$title/>";
			$this->links = array_unique( $this->links );
		}

		return $this;
	}

	/**
	 * Adds a canonical URL to the site header.
	 *
	 * @param $url
	 *
	 * @return SPHeader
	 */
	public function & addCanonical( $url ): self
	{
		$checksum = md5( $url );
		if ( !isset( $this->_checksums[ __FUNCTION__ ][ $checksum ] ) ) {
			$this->_checksums[ __FUNCTION__ ][ $checksum ] = true;
			$this->store( get_defined_vars(), __FUNCTION__ );

			return $this->addHeadLink( $url, C::ES, C::ES, 'canonical' );
		}

		return $this;
	}

	/**
	 * Creates a new array for the title after checking the existing array.
	 *
	 * @param array $site -> pagination
	 * @param $title
	 *
	 * @throws \SPException
	 */
	protected function createTitle( $title, $site = [] ): void
	{
		if ( count( $site ) && $site[ 0 ] > 1 ) {
			if ( !is_array( $title ) ) {
				$title = [ $title ];
			}
			if ( $site[ 1 ] > 1 ) { // no page counter when on page 1
				$title[] = Sobi::Txt( 'SITES_COUNTER', $site[ 1 ], $site[ 0 ] );
			}
		}
		if ( is_array( $title ) ) {
			foreach ( $title as $segment ) {
				if ( $segment ) {
					$this->createTitle( $segment );
				}
			}
		}
		else {
			$checksum = md5( $title );
			if ( !isset( $this->_checksums[ __FUNCTION__ ][ $checksum ] ) ) {
				$this->_checksums[ __FUNCTION__ ][ $checksum ] = true;
				$args = get_defined_vars();
				unset( $args[ 'site' ] );
				$this->store( $args, __FUNCTION__ );

				$this->title[] = $title;
			}
		}
	}

	/**
	 * Sets the site title.
	 *
	 * @param $title
	 * @param array $site
	 * @param bool $forceAdd
	 *
	 * @return $this|SPHeader
	 * @throws \SPException
	 */
	public function & addTitle( $title, array $site = [], bool $forceAdd = false ): self
	{
		$this->createTitle( $title, $site );

		/* set the title into the document */
		if ( count( $this->title ) && ( defined( 'SOBIPRO_ADM' ) || !Sobi::Cfg( 'browser.no_title', false ) ) ) {
			SPFactory::mainframe()->setTitle( $this->title, $forceAdd );
		}

		return $this;
	}

	/**
	 * Adds meta description to the site header.
	 *
	 * @param string $desc
	 *
	 * @return SPHeader
	 */
	public function & addDescription( $desc ): self
	{
		if ( is_string( $desc ) ) {
			$checksum = md5( $desc );
			if ( !isset( $this->_checksums[ __FUNCTION__ ][ $checksum ] ) ) {
				$this->_checksums[ __FUNCTION__ ][ $checksum ] = true;
				$this->store( get_defined_vars(), __FUNCTION__ );
				if ( strlen( $desc ) ) {
					$this->description[] = strip_tags( str_replace( '"', "'", StringUtils::Entities( $desc, true ) ) );
				}
			}
		}

		return $this;
	}

	/**
	 * Sets (overwrites) the (existing) site title.
	 * Will be called only from frontend template (since SobiPro 2.0).
	 *
	 * @param string|array $title
	 *
	 * @throws SPException
	 */
	public function & setTitle( $title = C::ES ): self
	{
		if ( !defined( 'SOBIPRO_ADM' ) ) {
			if ( is_array( $title ) ) {
				foreach ( $title as $index => $value ) {
					$title[ $index ] = html_entity_decode( StringUtils::Clean( $value ) );
				}
			}
			else {
				$title = html_entity_decode( StringUtils::Clean( $title ) );
			}
			SPFactory::mainframe()->setTitle( $title );
		}

		return $this;
	}

	/**
	 * Gets meta keys and met description from the given object and adds to the site header.
	 *
	 * @param SPDBObject|null $obj
	 *
	 * @return $this|SPHeader
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function & objMeta( $obj ): self
	{
		$task = Input::Task();
		if ( Sobi::Cfg( 'meta.always_add_section' ) ) {
			if ( strpos( $task, 'search' ) || ( ( $obj->get( 'oType' ) != 'section' ) ) ) {
				$this->objMeta( SPFactory::currentSection() );
			}
		}
		if ( $obj->get( 'metaDesc' ) ) {
			$separator = Sobi::Cfg( 'meta.separator', '.' );
			$desc = $obj->get( 'metaDesc' );
			$desc .= $separator;
			$this->addDescription( $desc );
		}
		if ( $obj->get( 'metaKeys' ) ) {
			$this->addKeyword( $obj->get( 'metaKeys' ) );
		}
		if ( $obj->get( 'metaAuthor' ) ) {
			$this->addAuthor( $obj->get( 'metaAuthor' ) );
		}
		if ( $obj->get( 'metaRobots' ) ) {
			$this->addRobots( $obj->get( 'metaRobots' ) );
		}
		if ( $obj->get( 'oType' ) == 'entry' || $obj->get( 'oType' ) == 'category' ) {
			$fields = $obj->getFields();
			if ( count( $fields ) ) {
				$fields = array_reverse( $fields );
				foreach ( $fields as $field ) {
					if ( !$field->get( '_off' ) ) {
						$separator = $field->get( 'metaSeparator' );
						$desc = $field->metaDesc();
						if ( is_string( $desc ) && strlen( $desc ) ) {
							$desc .= $separator;
						}
						$this->addDescription( $desc );
						$this->addKeyword( $field->metaKeys() );
					}
				}
			}
		}

		return $this;
	}

	/**
	 * @param $robots
	 */
	public function addRobots( $robots )
	{
		$this->robots = [ $robots ];
	}

	/**
	 * @param $author
	 */
	public function addAuthor( $author )
	{
		$checksum = md5( $author );
		if ( !isset( $this->_checksums[ __FUNCTION__ ][ $checksum ] ) ) {
			$this->_checksums[ __FUNCTION__ ][ $checksum ] = true;
			$this->store( get_defined_vars(), __FUNCTION__ );
			$this->author[] = $author;
		}
	}

	/**
	 * Adds a keywords to the site header.
	 *
	 * @param $keys
	 *
	 * @return $this|SPHeader
	 * @internal param string $key
	 */
	public function & addKeyword( $keys ): self
	{
		if ( is_string( $keys ) ) {
			$checksum = md5( $keys );
			if ( !isset( $this->_checksums[ __FUNCTION__ ][ $checksum ] ) ) {
				$this->_checksums[ __FUNCTION__ ][ $checksum ] = true;
				$this->store( get_defined_vars(), __FUNCTION__ );
				if ( strlen( $keys ) ) {
					$keys = explode( Sobi::Cfg( 'string.meta_keys_separator', ',' ), $keys );
					if ( !empty( $keys ) ) {
						$this->count++;
						foreach ( $keys as $key ) {
							$this->keywords[] = strip_tags( trim( StringUtils::Entities( $key, true ) ) );
						}
					}
				}
			}
		}

		return $this;
	}

	/**
	 * @param $index
	 *
	 * @return mixed
	 */
	public function getData( $index )
	{
		if ( isset( $this->$index ) ) {
			return $this->$index;
		}
	}

	/**
	 * @return $this|SPHeader
	 */
	public function & reset(): self
	{
		$this->keywords = [];
		$this->author = [];
		$this->robots = [];
		$this->description = [];
		$this->cssFiles = [];
		$this->jsFiles = [];
		$this->css = [];
		$this->js = [];
		$this->raw = [];
		$this->head = [];
		$this->_store = [];

		return $this;
	}

	/**
	 * @return array
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	private function _cssFiles()
	{
		if ( Sobi::Cfg( 'cache.include_css_files', false ) && !defined( 'SOBIPRO_ADM' ) ) {
			if ( count( $this->_cache[ 'css' ] ) ) {
				/* * create the right checksum */
				$check = [ 'section' => Sobi::Section() ];
				foreach ( $this->_cache[ 'css' ] as $file ) {
					if ( file_exists( $file ) ) {
						$check[ $file ] = filemtime( $file );
					}
				}
				$check = md5( serialize( $check ) );
				if ( !FileSystem::Exists( SOBI_PATH . "/var/css/$check.css" ) ) {
					$cssContent = "/* Created at: " . date( SPFactory::config()->key( 'date.log_format', 'D M j G:i:s T Y' ) ) . " */\n";
					foreach ( $this->_cache[ 'css' ] as $file ) {
						$fName = str_replace( FileSystem::FixPath( SOBI_ROOT ), C::ES, $file );
						$cssContent .= "/** ==== File: $fName ==== */\n";
						$fc = FileSystem::Read( $file );
						preg_match_all( '/[^\(]*url\(([^\)]*)/', $fc, $matches );
						// we have to replace url relative path
						$fPath = str_replace( FileSystem::FixPath( SOBI_ROOT . '/' ), SPFactory::config()->get( 'live_site' ), $file );
						$fPath = str_replace( '\\', '/', $fPath );
						$fPath = explode( '/', $fPath );
						if ( count( $matches[ 1 ] ) ) {
							foreach ( $matches[ 1 ] as $url ) {
								// if it is already absolute - skip or from root
								if ( preg_match( '|http(s)?://|', $url ) || preg_match( '|url\(["\s]*/|', $url ) ) {
									continue;
								}
								elseif ( strpos( $url, '/' ) === 0 ) {
									continue;
								}
								$c = preg_match_all( '|\.\./|', $url, $c ) + 1;
								$tempFile = array_reverse( $fPath );
								for ( $index = 0; $index < $c; $index++ ) {
									unset( $tempFile[ $index ] );
								}
								$rPath = FileSystem::FixPath( implode( '/', array_reverse( $tempFile ) ) );
								if ( $c > 1 ) {
									//WHY?!!
									//$realUrl = FileSystem::FixPath( str_replace( '..', $rPath, $url ) );
									$realUrl = FileSystem::FixPath( $rPath . '/' . str_replace( '../', C::ES, $url ) );
								}
								else {
									$realUrl = FileSystem::FixPath( $rPath . '/' . $url );
								}
								$realUrl = str_replace( [ '"', "'", ' ' ], C::ES, $realUrl );
								$fc = str_replace( $url, $realUrl, $fc );
							}
						}
						// and add to content
						$cssContent .= $fc;
						$cssContent .= "\n";
					}
					FileSystem::Write( SOBI_PATH . "/var/css/$check.css", $cssContent );
				}
				$cfile = SPLoader::CssFile( 'front.var.css.' . $check, false, true, true );
				$this->cssFiles[ ++$this->count ] = "\n<link rel=\"stylesheet\" href=\"$cfile\" media=\"all\" type=\"text/css\" />";
			}
		}

		return $this->cssFiles;
	}

	/**
	 * @return array
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	private function _jsFiles()
	{
		if ( Sobi::Cfg( 'cache.include_js_files', false ) && !defined( 'SOBIPRO_ADM' ) ) {
			if ( count( $this->_cache[ 'js' ] ) ) {
				$compression = Sobi::Cfg( 'cache.compress_js', false );
				$comprLevel = Sobi::Cfg( 'cache.compress_level', 0 );
				$check = [ 'section' => Sobi::Section(), 'compress_level' => $comprLevel, 'compress_js' => $compression ];
				foreach ( $this->_cache[ 'js' ] as $file ) {
					$check[ $file ] = filemtime( $file );
				}
				$check = md5( serialize( $check ) );
				if ( !FileSystem::Exists( SOBI_PATH . "/var/js/$check.js" ) ) {
					$noCompress = explode( ',', Sobi::Cfg( 'cache.js_compress_exceptions' ) );
					$jsContent = "/* Created at: " . date( SPFactory::config()->key( 'date.log_format', 'D M j G:i:s T Y' ) ) . " */\n";
					foreach ( $this->_cache[ 'js' ] as $file ) {
						$fName = str_replace( SOBI_ROOT, C::ES, $file );
						$folder = str_replace( SOBI_PATH, C::ES, dirname( $file ) );
						if ( $compression && !in_array( basename( $file ), $noCompress ) && strpos( $folder, 'usr/templates' ) == 1 ) {
							$jsContent .= "// ==== Minified: $fName ==== \n";
							$compressor = SPFactory::Instance( 'env.jspacker', FileSystem::Read( $file ), $comprLevel, false, true );
							$jsContent .= $compressor->pack();
						}
						else {
							$jsContent .= "// ==== Read: $fName ==== \n";
							$jsContent .= FileSystem::Read( $file );
						}
						$jsContent .= ";\n";
					}
					FileSystem::Write( SOBI_PATH . "/var/js/$check.js", $jsContent );
				}
				$cfile = SPLoader::JsFile( 'front.var.js.' . $check, false, true, true );
				$this->jsFiles[ ++$this->count ] = "\n<script defer src=\"$cfile\"></script>";
			}
		}

		return $this->jsFiles;
	}

	/**
	 * Creates the SobiPro header content the header via the mainframe interface.
	 *
	 * @param bool $afterRender
	 *
	 * @return string
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function createHeaderContent( bool $afterRender = false ): string
	{
		if ( count( $this->_store ) ) {
			if ( $afterRender ) {
				if ( count( $this->js ) ) {
					$jsCode = C::ES;
					foreach ( $this->js as $js ) {
						$jsCode .= "\n\t" . str_replace( "\n", "\n\t", $js );
					}
					$this->js = [ "\n<script defer type=\"text/javascript\">\n/*<![CDATA[*/$jsCode\n/*]]>*/\n</script>\n" ];
				}
				if ( count( $this->css ) ) {
					$cssCode = C::ES;
					foreach ( $this->css as $css ) {
						$cssCode .= "\n\t" . str_replace( "\n", "\n\t", $css );
					}
					$this->css = [ "<style>$cssCode</style>" ];
				}
				$this->head[ 'links' ] = $this->links;
				$this->head[ 'css' ] = $this->_cssFiles();
				$this->head[ 'js' ] = $this->_jsFiles();
				$this->head[ 'css' ] = array_merge( $this->head[ 'css' ], $this->css );
				$this->head[ 'js' ] = array_merge( $this->head[ 'js' ], $this->js );
				$this->head[ 'raw' ] = $this->raw;

				Sobi::Trigger( 'Header', 'Send', [ &$this->head ] );
				$body = SPFactory::mainframe()->addHead( $this->head, $afterRender );
				SPFactory::cache()->storeXMLView( $this->_store );
				$this->reset();

				return $body;
			}
			else {
				$this->head[ 'keywords' ] = array_reverse( $this->keywords );
				$this->head[ 'author' ] = $this->author;
				$this->head[ 'robots' ] = $this->robots;
				$this->head[ 'description' ] = array_reverse( $this->description );
				$this->head[ 'generator' ] = 'SobiPro on ';
				SPFactory::mainframe()->addHead( $this->head, $afterRender );
			}
		}

		return C::ES;
	}
}
