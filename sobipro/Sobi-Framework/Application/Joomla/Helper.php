<?php
/**
 * @package Sobi Framework
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2011-2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according to section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * @created Wed, Feb 24, 2021 14:21:49 by Radek Suski
 * @modified 23 August 2024 by Sigrid Suski
 */

//declare( strict_types=1 );

namespace Sobi\Application\Joomla;

use DOMDocument;
use JConfig;
use JLanguageHelper;
use Joomla\CMS\Version as JVERSION;
use Sobi\FileSystem\FileSystem;
use Sobi\Lib\Factory;
use Sobi\Lib\Instance;
use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Updater\Updater;

/**
 * class Helper
 */
class Helper
{
	use Instance;

	/**
	 * Return min or recommend Joomla! version
	 *
	 * @param bool $recommended
	 *
	 * @return array
	 * @throws \Sobi\Error\Exception
	 */
	public function minVersion( bool $recommended = false ): array
	{
		$updater = Updater::getInstance();
		$updater->findUpdates( 700, 0 );
		$version = Factory::Db()
			->select( 'version', '#__updates', [ 'extension_id' => 700 ] )
			->loadResult();
		$recommendedVersion = [ 'major' => 3, 'minor' => 2, 'build' => 3 ];
		if ( $version ) {
			$version = explode( '.', $version );
			$recommendedVersion = [ 'major' => $version[ 0 ], 'minor' => $version[ 1 ], 'build' => $version[ 2 ] ];
		}

		return $recommended ? $recommendedVersion : [ 'major' => 3, 'minor' => 2, 'build' => 0 ];
	}

	/**
	 * Returns SobiPro version.
	 *
	 * @param bool $str
	 * @param string $extension
	 *
	 * @return array or string
	 */
	public function myVersion( bool $str = false, string $extension = 'com_sobipro' )
	{
		static $ver = [];
		if ( !isset( $ver[ $str ] ) ) {
			$def = $extension . '.xml';
			$doc = new DOMDocument();
			$doc->load( FileSystem::FixPath( JPATH_ADMINISTRATOR . '/components/' . $extension . '/' . $def ) );
			if ( $str ) {
				$ver[ $str ] = $doc->getElementsByTagName( 'version' )->item( 0 )->nodeValue;
				$codename = $doc->getElementsByTagName( 'codename' )->item( 0 )->nodeValue;
				$ver[ $str ] = $ver[ $str ] . ' [ ' . $codename . ' ]';
			}
			else {
				$v = explode( '.', $doc->getElementsByTagName( 'version_number' )->item( 0 )->nodeValue );
				$ver[ $str ] = [ 'major' => $v[ 0 ], 'minor' => ( $v[ 1 ] ?? 0 ), 'build' => ( $v[ 2 ] ?? 0 ), 'rev' => ( $v[ 3 ] ?? 0 ) ];
			}
		}

		return $ver[ $str ];
	}

	/**
	 * Returns Joomla's used languages
	 * @return array
	 */
	public function getLanguages(): array
	{
		static $return = [];
		if ( !( count( $return ) ) ) {
			$langs = LanguageHelper::getLanguages();
			$return = [];
			foreach ( $langs as $lang ) {
				$return[ $lang->lang_code ] = $lang->sef;
			}
		}

		return $return;
	}

	/**
	 * Returns Joomla's available languages
	 *
	 * @param bool $list
	 *
	 * @return array
	 */
	public function availableLanguages( bool $list = false ): array
	{
		$langs = LanguageHelper::getKnownLanguages();
		if ( $list ) {
			$langList = [];
			foreach ( $langs as $i => $value ) {
				$langList[ $i ] = $value[ 'name' ];
			}

			return $langList;
		}

		return $langs;
	}

	/**
	 * Returns specified Joomla! configuration setting.
	 *
	 * @param string $setting
	 *
	 * @return mixed
	 */
	public function applicationsSetting( string $setting )
	{
		static $cfg;
		if ( !$cfg ) {
			$cfg = new JConfig();
		}
		switch ( $setting ) {
			case 'charset':
				$r = JFactory::getDocument()->getCharset();
				break;
			default:
				$r = $cfg->$setting ?? '';
				break;
		}

		return $r;
	}


	/**
	 * Returns Joomla! version
	 * @return array
	 */
	public function applicationVersion(): array
	{
		return [ 'major' => JVERSION::MAJOR_VERSION, 'minor' => JVERSION::MINOR_VERSION, 'build' => JVERSION::PATCH_VERSION, 'rev' => 0 ];
	}

	public function applicationName(): string
	{
		$v = $this->applicationVersion();
		return 'Joomla ' . $v[ 'major' ];
	}
}
