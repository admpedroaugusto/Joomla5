<?php
/**
 * @package: Sobi Framework
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET
 * @copyright Copyright (C) 2006 - 2021 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See http://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * @created Thu, Feb 25, 2021 10:48:52 by Radek Suski
 * @modified 14 July 2021 by Radek Suski
 */
//declare( strict_types=1 );

namespace Sobi\Application\Joomla;

use Sobi\FileSystem\FileSystem;
use Sobi\Lib\Instance;

class Installer
{
	use Instance;

	/**
	 * Installs language files.
	 *
	 * @param array $lang
	 * @param bool $force
	 * @param false $move
	 *
	 * @return array
	 * @throws \Sobi\Error\Exception
	 */
	public function installLanguage( array $lang, bool $force = true, bool $move = false ): array
	{
		$log = [];
		if ( count( $lang ) ) {
			foreach ( $lang as $language => $files ) {
				$language = str_replace( '_', '-', $language );
				if ( count( $files ) ) {
					foreach ( $files as $file ) {
						$target = $file[ 'adm' ] ? implode( '/', [ JPATH_ADMINISTRATOR, 'language', $language ] ) : implode( '/', [ SOBI_ROOT, 'language', $language ] );
						if ( $force || FileSystem::Exists( $target ) ) {
							$iFile = $target . '/' . trim( $file[ 'name' ] );
							$log[] = $iFile;
							$move ? FileSystem::Move( FileSystem::FixPath( $file[ 'path' ] ), $iFile ) : FileSystem::Copy( FileSystem::FixPath( $file[ 'path' ] ), $iFile );
						}
					}
				}
			}
		}

		return $log;
	}

	/**
	 * @param array $files
	 * @param string $node
	 *
	 * @return \DOMDocument
	 */
	public function installerFile( array $files, string $node ): \DOMDocument
	{
		foreach ( $files as $file ) {
			$def = FileSystem::LoadXML( $file, LIBXML_NOERROR );
			if ( in_array( trim( $def->documentElement->tagName ), [ 'install', 'extension' ] ) ) {
				if ( $def->getElementsByTagName( $node )->length ) {
					if ( in_array( trim( $def->documentElement->getAttribute( 'type' ) ), [ 'language', 'module', 'plugin', 'component' ] ) ) {
						return $def;
					}
				}
			}
		}
	}

	/**
	 * Returns Joomla depend additional path with alternative templates location overrides.
	 *
	 * @param string $extension example = 'com_sobipro'
	 *
	 * @return array
	 */
	public function templatesPath( string $extension ): array
	{
		$jTemplates = new \DirectoryIterator( JPATH_ROOT . '/templates/' );
		$tr = [];
		foreach ( $jTemplates as $template ) {
			if ( $template->isDot() ) {
				continue;
			}
			if ( $template->isDir() ) {
				if ( file_exists( implode( '/', [ $template->getPathname(), 'html', $extension ] ) ) && file_exists( implode( '/', [ $template->getPathname(), 'templateDetails.xml' ] ) ) ) {
					$data = FileSystem::LoadXML( $template->getPathname() . '/templateDetails.xml' );
					$name = $data->getElementsByTagName( 'name' )->item( 0 )->nodeValue;
					$tr[ $name ] = FileSystem::FixPath( implode( '/', [ $template->getPathname(), 'html', $extension ] ) );
				}
			}
		}

		return $tr;
	}
}
