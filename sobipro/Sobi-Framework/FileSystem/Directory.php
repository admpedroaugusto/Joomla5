<?php
/**
 * @package: Sobi Framework
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2021 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 03 December 2016 by Radek Suski
 * @modified 07 September 2020 by Sigrid Suski
 */

namespace Sobi\FileSystem;

defined( 'SOBI' ) || exit( 'Restricted access' );

use Sobi\FileSystem\DirectoryIterator;
use Sobi\FileSystem\FileSystem;

/**
 * Class Directory
 * @package Sobi\FileSystem
 */
class Directory extends File
{
	/*** @var DirectoryIterator */
	private $_dirIterator = null;

	/**
	 * @param string | array $string - part or full name of the file to search for
	 * @param bool $exact - search for exact string or the file nam can contain this string
	 * @param int $recLevel - recursion level
	 *
	 * @return array
	 */
	public function searchFile( $string, bool $exact = true, int $recLevel = 1 )
	{
		$this->iterator();
		$results = [];
		if ( !( is_array( $string ) ) ) {
			$string = [ $string ];
		}
		foreach ( $string as $search ) {
			$this->searchRecursive( $this->_dirIterator, $search, $exact, $recLevel, $results );
		}

		return $results;
	}

	/**
	 * @return DirectoryIterator
	 */
	public function iterator()
	{
		if ( !$this->_dirIterator ) {
			$this->_dirIterator = new DirectoryIterator( $this->_filename );
		}

		return $this->_dirIterator;
	}

	/**
	 * Moves files from directory to given path.
	 *
	 * @param string $target - target path
	 * @param false $force
	 * @param false $recursive
	 *
	 * @return array
	 */
	public function moveFiles( string $target, bool $force = false, bool $recursive = false )
	{
		$this->iterator();
		$log = [];
		foreach ( $this->_dirIterator as $child ) {
			if ( !( $child->isDot() ) ) {
				if ( ( !( FileSystem::Exists( FileSystem::Clean( $target . '/' . $child->getFileName() ) ) ) ) || $force ) {
					if ( is_dir( $child->getPathname() ) && $recursive ) {
						$fr = new Directory( $child->getPathname() );
						$fr->moveFiles( $target . $child->getFilename() . '/', $force, true );
					}
					elseif ( FileSystem::Move( $child->getPathname(), FileSystem::Clean( $target . '/' . $child->getFileName() ) ) ) {
						$log[] = FileSystem::Clean( $target . '/' . $child->getFileName() );
					}
				}
			}
		}

		return $log;
	}

	/**
	 * Removes all files in directory.
	 *
	 * @throws \Sobi\Error\Exception
	 */
	public function deleteFiles()
	{
		$this->iterator();
		foreach ( $this->_dirIterator as $child ) {
			if ( !( $child->isDot() ) ) {
				FileSystem::Delete( $child->getPathname() );
			}
		}
	}

	/**
	 * @param \Sobi\FileSystem\DirectoryIterator $dir
	 * @param $string
	 * @param $exact
	 * @param $recLevel
	 * @param $results
	 * @param int $level
	 *
	 * @return void
	 */
	private function searchRecursive( DirectoryIterator $dir, $string, $exact, $recLevel, &$results, int $level = 0 )
	{
		$level++;
		if ( $level > $recLevel ) {
			return;
		}
		$r = $dir->searchFile( $string, $exact );
		$results = array_merge( $results, $r );
		foreach ( $dir as $file ) {
			if ( $file->isDir() && !( $file->isDot() ) ) {
				$this->searchRecursive( new DirectoryIterator( $file->getPathname() ), $string, $exact, $recLevel, $results, $level );
			}
		}
	}
}
