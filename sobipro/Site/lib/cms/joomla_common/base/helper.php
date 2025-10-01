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
 * @created 08-Jul-2008 by Radek Suski
 * @modified 02 March 2022 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

use Sobi\FileSystem\FileSystem;
use Sobi\FileSystem\DirectoryIterator;
use Sobi\FileSystem\Directory;
use Sobi\Lib\Factory;
use Sobi\Lib\Instance;

/**
 * Class SPJoomlaCMSHelper
 */
class SPCMSHelper3
{
	use Instance;

	/**
	 * @return false|\SPCMSHelper|\SPCMSHelper3
	 * @deprecated since 2.0
	 * use Instance
	 */
	public static function & getInstance()
	{
		return self::Instance();
	}

	/**
	 * Return min or recommend Joomla! version
	 *
	 * @param $recommended
	 *
	 * @return array
	 * @throws \Sobi\Error\Exception
	 * @deprecated since 2.0
	 */
	public static function minCmsVersion( $recommended = false )
	{
		return Factory::ApplicationHelper()->minVersion( $recommended );
	}

	/**
	 * Returns Joomla! version
	 *
	 * @param bool $version
	 *
	 * @return array | string
	 * @deprecated since 2.0
	 */
	public static function cmsVersion( $version = null )
	{
		return $version ? Factory::ApplicationHelper()->applicationName() : Factory::ApplicationHelper()->applicationVersion();
	}

	/**
	 * Returns specified Joomla! configuration setting
	 *
	 * @param string $setting
	 *
	 * @return string
	 * @deprecated since 2.0
	 */
	public static function cmsSetting( $setting )
	{
		return Factory::ApplicationHelper()->applicationsSetting( $setting );
	}

	/**
	 * Returns SobiPro version.
	 *
	 * @param bool $str
	 *
	 * @return array or string
	 * @deprecated
	 */
	public static function myVersion( $str = false )
	{
		return Factory::ApplicationHelper()->myVersion( $str );
	}

	/**
	 * @param array $files
	 *
	 * @return \DOMDocument
	 * @deprecated since 2.0
	 */
	public function installerFile( array $files ): DOMDocument
	{
		return Factory::ApplicationInstaller()->installerFile( $files, 'SobiPro' );
	}

	/**
	 * Called from App installer if the core installer has no handler for this type.
	 *
	 * @param $files
	 * @param $dir
	 *
	 * @return mixed
	 * @throws SPException
	 */
	public function install( $files, $dir )
	{
		return SPFactory::Instance( 'cms.base.installer' )
			->install( Factory::ApplicationInstaller()->installerFile( $files, 'SobiPro' ), $files, $dir );
	}

	/**
	 * Installs language files.
	 *
	 * @param $lang
	 * @param bool $force
	 * @param false $move
	 *
	 * @return array
	 * @throws \Sobi\Error\Exception
	 * @deprecated
	 */
	public static function installLang( $lang, $force = true, $move = false )
	{
		return Factory::ApplicationInstaller()->installLanguage( $lang, $force, $move );
	}


	/**
	 * @param bool $list
	 *
	 * @return array
	 * @deprecated
	 * use \Sobi\
	 */
	public static function availableLanguages( $list = false )
	{
		return SPLang::availableLanguages();
	}

	/**
	 * Returns Joomla depends on additional path with alternative templates' location.
	 *
	 * @return array
	 * @throws SPException
	 * @deprecated since 2.0
	 */
	public function templatesPath()
	{
		return [ 'name' => Sobi::Txt( 'TP.TEMPLATES_OVERRIDE' ), 'icon' => Sobi::Cfg( 'live_site' ) . 'media/sobipro/tree/joomla.gif', 'data' => Factory::ApplicationInstaller()->templatesPath( 'com_sobipro' ) ];
	}

	/**
	 * @return null
	 * @deprecated since 2.0
	 */
	public function getLanguages()
	{
		return SPLang::availableLanguages();
	}

	/**
	 * ================================================
	 * Fri, Feb 26, 2021 13:06:23 by Radek Suski
	 * This method seems not to be used anywhere anymore. And to be honest I don't quite remember for what it was
	 * It can probably be removed as well.
	 * ================================================
	 * This method is adding new tasks to the XML files used for Joomla! menu definition.
	 *
	 * @param $tasks - list of tasks to add
	 * @param $controlString - a single string to check for if it has not been already added
	 * @param $languageFile - language file where the translation for tasks can be found
	 * @param array $additionalStrings - optional list of additional strings to add to the sys ini files
	 * @param bool $force - force even if it has been already done - forcing only language files redefinition
	 *
	 */
	public function updateXMLDefinition( $tasks, $controlString, $languageFile, $additionalStrings = [], $force = false )
	{
		trigger_error( 'Deprecated. Do not use it!', E_USER_WARNING );
		$file = SPLoader::translatePath( 'metadata', 'front', true, 'xml' );
		$run = false;
		$strings = [];
		foreach ( $tasks as $label ) {
			$strings[] = $label;
			$strings[] = $label . '_EXPL';
		}
		if ( count( $additionalStrings ) ) {
			foreach ( $additionalStrings as $additionalString ) {
				$strings[] = $additionalString;
			}
		}
		/** check if it hasn't been already added */
		if ( !( strstr( FileSystem::Read( $file ), $controlString ) ) ) {
			$run = true;
			$doc = new DOMDocument();
			$doc->load( $file );
			$options = $doc->getElementsByTagName( 'options' )->item( 0 );
			foreach ( $tasks as $task => $label ) {
				$node = $doc->createElement( 'option' );
				$attribute = $doc->createAttribute( 'value' );
				$attribute->value = $task;
				$node->appendChild( $attribute );
				$attribute = $doc->createAttribute( 'name' );
				$attribute->value = 'SP.' . $label;
				$node->appendChild( $attribute );
				$attribute = $doc->createAttribute( 'msg' );
				$attribute->value = 'SP.' . $label . '_EXPL';
				$node->appendChild( $attribute );
				$options->appendChild( $node );
			}
			$doc->save( $file );
		}
		if ( $run || $force ) {
			$dirPath = SPLoader::dirPath( 'administrator.language', 'root' );
			$dir = new Directory( $dirPath );
			$files = $dir->searchFile( 'com_sobipro.sys.ini', false, 2 );
			$default = [];
			$defaultLangDir = SPLoader::dirPath( "language.en-GB", 'root', true );
			$defaultLang = parse_ini_file( $defaultLangDir . 'en-GB.' . $languageFile . '.ini' );
			foreach ( $strings as $string ) {
				$default[ 'SP.' . $string ] = $defaultLang[ 'SP.' . $string ];
			}
			$file = null;
			foreach ( $files as $file ) {
				$fileName = $file->getFileName();
				[ $language ] = explode( '.', $fileName );
				$nativeLangDir = SPLoader::dirPath( "language.{$language}", 'root', true );
				$nativeStrings = [];
				if ( $nativeLangDir ) {
					$nativeLangFile = $nativeLangDir . $language . '.' . $languageFile . '.ini';
					if ( file_exists( $nativeLangFile ) ) {
						$nativeLang = @parse_ini_file( $nativeLangFile );
						foreach ( $strings as $string ) {
							if ( isset( $nativeLang[ 'SP.' . $string ] ) ) {
								$nativeStrings[ 'SP.' . $string ] = $nativeLang[ 'SP.' . $string ];
							}
						}
					}
				}
				$add = null;
				foreach ( $strings as $string ) {
					if ( isset( $nativeStrings[ 'SP.' . $string ] ) ) {
						$add .= "\nSP.{$string}=\"{$nativeStrings['SP.' . $string]}\"";
					}
					else {
						$add .= "\nSP.{$string}=\"{$default['SP.' . $string]}\"";
					}
				}
				$add .= "\n";
				$content = FileSystem::Read( $file->getPathname() );
				$add = $content . $add;
				FileSystem::Write( $file->getPathname(), $add );
			}
		}
	}
}
