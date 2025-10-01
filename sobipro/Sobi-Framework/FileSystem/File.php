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
 * @created Thu, Dec 1, 2016 by Radek Suski
 * @modified Tue, Apr 6, 2021 13:18:30 by Radek Suski
 */
//declare( strict_types=1 );

namespace Sobi\FileSystem;

defined( 'SOBI' ) || exit( 'Restricted access' );

use Sobi\{
	C,
	Framework,
	Error\Exception
};

/**
 * Class File
 * @package Sobi\FileSystem
 */
class File
{
	/*** @var string */
	protected $_filename = C::ES;
	/*** @var string */
	protected $_content = C::ES;
	/*** @var string */
	protected $_pathinfo = C::ES;

	/**
	 * @param string|null $filename
	 */
	public function __construct( string $filename = C::ES )
	{
		$this->_filename = $filename;
		if ( $this->_filename ) {
			$this->_pathinfo = pathinfo( $this->_filename );
		}
	}

	/**
	 * @return array
	 */
	public function getPathInfo()
	{
		return $this->_pathinfo;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->_filename;
	}

	/**
	 * @return string - full path to current file
	 */
	public function getPathname(): string
	{
		return $this->getName();
	}

	/**
	 * @return string - name of the current file
	 */
	public function getFileName(): string
	{
		return $this->_pathinfo[ 'basename' ];
	}

	/**
	 * @return bool
	 */
	public function isDot(): bool
	{
		return in_array( $this->getFileName(), [ '.', '..' ] );
	}

	/**
	 * Check if file is a directory
	 * @return bool
	 */
	public function isDir(): bool
	{
		return is_dir( $this->_filename );
	}

	/**
	 * Check if file is file
	 * @return bool
	 */
	public function isFile(): bool
	{
		return is_file( $this->_filename );
	}

	/**
	 * @param int $mode
	 *
	 * @return bool
	 */
	public function chmod( int $mode ): bool
	{
		return FileSystem::Chmod( $this->_filename, $mode );
	}

	/**
	 * Copy file
	 *
	 * @param string $destination - path
	 *
	 * @return bool
	 */
	public function copy( string $destination ): bool
	{
		return FileSystem::Copy( $this->_filename, $destination );
	}

	/**
	 * Get file from the request and upload to the given path
	 *
	 * @param string $name - file name from the request
	 * @param string $destination - destination path
	 *
	 * @return string
	 * @throws Exception
	 */
	public function upload( string $name, string $destination ): string
	{
		$destination = FileSystem::Clean( $destination );

		if ( FileSystem::Upload( $name, $destination ) ) {
			$this->_filename = $destination;
			return $this->_filename;
		}
		else {
			// Sun, Jan 18, 2015 20:41:09
			// stupid windows exception. I am not going to waste my time trying to find why the hell it doesn't work as it should
			if ( FileSystem::Upload( FileSystem::Clean( $name ), $destination ) ) {
				$this->_filename = $destination;
				return $this->_filename;
			}
			else {
				throw new Exception( Framework::Error( 'CANNOT_UPLOAD_FILE_TO', str_replace( C::ROOT, C::ES, $destination ) ) );
			}
		}
	}

	/**
	 * Deletes a file.
	 *
	 * @return bool|void
	 */
	public function delete()
	{
		return FileSystem::Delete( $this->_filename );
	}

	/**
	 * Moves file to new location.
	 *
	 * @param $target
	 *
	 * @return $this
	 * @throws \Sobi\Error\Exception
	 */
	public function move( $target ): File
	{
		$f = explode( '/', $target );
		$path = str_replace( $f[ count( $f ) - 1 ], C::ES, $target );
		if ( !( FileSystem::Exists( $path ) ) ) {
			FileSystem::Mkdir( $path );
		}
		if ( FileSystem::Move( $this->_filename, $target ) ) {
			$this->_filename = $target;
		}
		else {
			throw new Exception( Framework::Error( 'CANNOT_MOVE_FILE_TO', str_replace( C::ROOT, C::ES, $target ) ) );
		}
		return $this;
	}

	/**
	 * Reads file and returns the content of it.
	 *
	 * @return string
	 */
	public function & read(): string
	{
		$this->_content = FileSystem::Read( $this->_filename );
		return $this->_content;
	}

	/**
	 * Sets the file content.
	 *
	 * @param $content - string
	 * @return File
	 */
	public function content( string $content ): File
	{
		$this->_content = $content;
		return $this;
	}

	/**
	 * Writes the content to the file.
	 *
	 * @return bool
	 */
	public function write(): bool
	{
		return FileSystem::Write( $this->_filename, $this->_content );
	}

	/**
	 * alias for @see SPFile#write()
	 * @return bool
	 */
	public function save(): bool
	{
		return $this->write();
	}

	/**
	 * Saves the file as a copy.
	 *
	 * @param string $path
	 *
	 * @return bool
	 */
	public function saveAs( string $path ): bool
	{
		return FileSystem::Write( $path, $this->_content );
	}

	/**
	 * @deprecated
	 * @return string
	 */
	public function filename(): string
	{
		return $this->_filename;
	}

	/**
	 * @param string $filename
	 * @return File
	 */
	public function & setFile( string $filename ): File
	{
		$this->_filename = $filename;
		$this->_pathinfo = pathinfo( $this->_filename );
		return $this;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function rename( string $name ): bool
	{
		$filename = FileSystem::GetFileName( $this->_filename );
		$new = str_replace( $filename, $name, $this->_filename );
		if ( FileSystem::Move( $this->_filename, $new ) ) {
			$this->_filename = $new;
			return true;
		}
		else {
			return false;
		}
	}
}
