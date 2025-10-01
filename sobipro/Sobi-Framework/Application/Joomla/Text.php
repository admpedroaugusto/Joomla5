<?php
/**
 * @package: Sobi Framework
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
 * See http://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created Thu, Feb 25, 2021 10:48:52 by Radek Suski
 * @modified 03 August 2022 by Sigrid Suski
 */
//declare( strict_types=1 );

namespace Sobi\Application\Joomla;

use Joomla\CMS\Factory as JFactory;
use Sobi\C;
use Sobi\Error\Exception;
use Sobi\FileSystem\FileSystem;
use Sobi\Framework;

/**
 * class Text
 */
class Text
{
//	use Instance;

	/*** @var string */
	protected $_lang = null;
	/*** @var bool */
	protected $_loaded = false;
	/*** @var string */
	const defLang = 'en-GB';
	/*** @var string */
	const encoding = 'UTF-8';
	/*** @var string */
	protected $extension = C::ES;
	/*** @var string */
	protected $prefix = C::ES;
	/*** @var \DOMXPath */
	protected $xdef = null;
	/*** @var string */
	protected $xdefLanguage = C::ES;

	/**
	 * @throws \Sobi\Error\Exception
	 * @throws \Exception
	 */
	protected function _load()
	{
		/* load default language file */
		if ( $this->_lang != 'en-GB' && Framework::Cfg( 'lang.engb_preload', true ) ) {
			JFactory::getApplication()
				->getLanguage()
				->load( $this->extension, JPATH_SITE, 'en-GB' );
			JFactory::getApplication()
				->getLanguage()
				->load( $this->extension, JPATH_BASE, 'en-GB' );
		}
		/* load front language file always */
		JFactory::getApplication()
			->getLanguage()
			->load( $this->extension, JPATH_BASE, $this->_lang, true );
		JFactory::getApplication()
			->getLanguage()
			->load( $this->extension, JPATH_SITE, $this->_lang, true );
		$this->_loaded = true;
	}

	/**
	 * Sets the used language/locale.
	 *
	 * @param string $lang
	 */
	protected function _setLang( string $lang ): void
	{
		$this->_lang = $lang;
	}

	/**
	 * Register new language domain.
	 *
	 * @param string $domain
	 *
	 * @throws \Exception
	 * @internal param string $path
	 */
	protected function _registerDomain( string $domain )
	{
		$domain = trim( $domain );
		if ( $domain != 'admin' && $domain != 'site' ) {
			$lang = JFactory::getApplication()
				->getLanguage();
			$lang->load( $this->extension . '.' . $domain );
		}
	}

	/**
	 * Translates a given string.
	 *
	 * @param array | string $params
	 *
	 * @return string
	 */
	protected function _txt( $params ): string
	{
		return C::ES;
	}

	/**
	 * @param string $path
	 * @param string $lang
	 */
	protected function loadTemplateOverride( string $path, string $lang )
	{
		if ( FileSystem::Exists( $path ) ) {
			$this->xdef = new \DOMXPath( FileSystem::LoadXML( $path ) );
		}
		$this->xdefLanguage = $lang;
	}

	/**
	 * @param string $term
	 *
	 * @return string
	 */
	protected function templateOverride( string $term ): string
	{
		if ( $this->xdef instanceof \DOMXPath ) {
			$term = strip_tags( preg_replace( '/[^a-z0-9\-\_\+\.\, ]/i', C::ES, $term ) );
			$transNode = $this->xdef->query( "/translation/term[@value=\"{$term}\"]/value[@lang='{$this->xdefLanguage}']" );
			if ( isset( $transNode->length ) && $transNode->length ) {
				return $transNode->item( 0 )->nodeValue;
			}
			else {
				$transNode = $this->xdef->query( "/translation/term[@value=\"{$term}\"]/value[@default='true']" );
				if ( isset( $transNode->length ) && $transNode->length ) {
					return $transNode->item( 0 )->nodeValue;
				}

			}
		}

		return C::ES;
	}

	/**
	 * @param bool $adm
	 *
	 * @return array
	 * @throws \Sobi\Error\Exception
	 */
	protected function _jsLang( bool $adm ): array
	{
		$front = [];
		$strings = [];
		if ( $adm ) {
			$front = $this->_jsLang( false );
		}
		$path = $adm ? JPATH_ADMINISTRATOR . '/language/en-GB/en-GB.' . $this->extension . '.js' : SOBI_ROOT . '/language/en-GB/en-GB.' . $this->extension . '.js';
		$pathEn = str_replace( 'en-GB', str_replace( '_', '-', $this->_lang ), $path );
		if ( $this->_lang != 'en-GB' && Framework::Cfg( 'lang.engb_preload', true ) ) {
			try {
				$strings = FileSystem::LoadIniFile( $pathEn, false, true );
			}
			catch ( Exception $e ) {
			}
		}
		try {
			$def = FileSystem::LoadIniFile( $path, false, true );
		}
		catch ( Exception $e ) {
			$def = [];
		}

		return array_merge( $front, $def, $strings );
	}

	/**
	 * @param array $m
	 *
	 * @return array
	 */
	protected function translation( array &$m ): array
	{
		$matches = [];
		preg_match( '/translate\:\[([a-zA-Z0-9\.\_\-]*)\]/', $m, $matches );
		$m = str_replace( $matches[ 0 ], $this->_txt( $matches[ 1 ], null, false ), $m );

		return $m;
	}
}
