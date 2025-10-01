<?php
/**
 * @package: Sobi Framework
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
 * @created  Thu, Dec 1, 2016 by Radek Suski
 * @modified 28 February 2023 by Sigrid Suski
 */

//declare( strict_types=1 );

namespace Sobi\Application\Joomla;

defined( 'SOBI' ) || exit( 'Restricted access' );

use DOMDocument;
use Sobi\C;
use Sobi\Error\Exception;
use Sobi\Framework;
use Sobi\Interfaces\Application\FileSystemInterface;
use Sobi\Lib\Instance;
use Joomla\CMS\Filesystem\File as JFile;
use Joomla\CMS\Filesystem\Folder as JFolder;

/**
 * Class FileSystem
 * @package Sobi\FileSystem
 */
class FileSystem implements FileSystemInterface
{
	use Instance;

	/**
	 * @param string $file
	 *
	 * @return bool
	 */
	public function exists( string $file ): bool
	{
		return file_exists( $file );
	}

	/**
	 * @param string $file
	 * @param bool $safe
	 *
	 * @return string
	 */
	public function clean( string $file, bool $safe = false ): string
	{
		$file = str_replace( C::DS, '/', $file );
		$file = preg_replace( '|([^:])(//)+([^/]*)|', '\1/\3', $file );
		$file = str_replace( '__BCKSL__', '\\', preg_replace( '|([^:])(\\\\)+([^\\\])|', "$1__BCKSL__$3", $file ) );
		$file = str_replace( '\\', '/', $file );
		if ( $safe ) {
			$file = Jfile::makeSafe( $file );
		}
		if ( !( strstr( $file, ':' ) ) ) {
			while ( strstr( $file, '//' ) ) {
				$file = str_replace( '//', '/', $file );
			}
		}

		return $file;
	}

	/**
	 * @param string $file
	 *
	 * @return mixed|string
	 */
	public function getExt( string $file ): string
	{
		$ext = explode( ".", $file );
		$ext = $ext[ count( $ext ) - 1 ];

		return $ext == $file ? C::ES : $ext;
	}

	/**
	 * @param string $file
	 *
	 * @return mixed|string
	 */
	public function getFileName( string $file ): string
	{
		$ext = explode( '/', $file );

		return $ext[ count( $ext ) - 1 ];
	}

	/**
	 * @param string $source
	 * @param string $destination
	 *
	 * @return mixed
	 * @throws \Sobi\Error\Exception
	 */
	public function copy( string $source, string $destination )
	{
		$destination = $this->clean( str_replace( '\\', '/', $destination ) );
		$path = explode( '/', str_replace( [ C::ROOT, str_replace( '\\', '/', C::ROOT ) ], C::ES, $destination ) );
		$part = C::ROOT;
		$i = count( $path );
		/** clean the path */
		/** @noinspection PhpExpressionResultUnusedInspection */
		for ( $i; $i != 0; $i-- ) {
			if ( isset( $path[ $i ] ) && !( $path[ $i ] ) ) {
				unset( $path[ $i ] );
			}
		}
		array_pop( $path );
		if ( !( is_string( $path ) ) && count( $path ) ) {
			foreach ( $path as $dir ) {
				$part .= "/$dir";
				if ( $dir && !( file_exists( $part ) ) ) {
					$this->mkdir( $part );
				}
			}
		}
		if ( !( is_dir( $source ) ) ) {
			return Jfile::copy( $this->clean( $source ), $this->clean( $destination ) );
		}
		else {
			return Jfolder::copy( $this->clean( $source ), $this->clean( $destination ) );
		}
	}

	/**
	 * @param string $file
	 *
	 * @return bool|void
	 * @throws \Sobi\Error\Exception
	 */
	public function delete( string $file ): bool
	{
		$file = $this->fixPath( $file );
		if ( is_dir( $file ) ) {
			if ( $file == C::ROOT || dirname( $file ) == C::ROOT ) {
				throw new Exception( Framework::Txt( 'Fatal error. Trying to delete not allowed path "%s"', $file ) );
			}

			return Jfolder::delete( $file );
		}
		else {
			return Jfile::delete( $file );
		}
	}

	/**
	 *     *
	 * @param string $source
	 * @param string $destination
	 *
	 * @return bool
	 */
	public function move( string $source, string $destination ): bool
	{
		return Jfile::move( $source, $destination );
	}

	/**
	 * @param string $file
	 *
	 * @return string
	 */
	public function read( string $file ): string
	{
		return file_get_contents( $file );
	}

	/**
	 * @param string $path
	 *
	 * @return string
	 */
	public function fixPath( string $path ): string
	{
		$sep = explode( '://', $path );
		if ( is_array( $sep ) && isset( $sep[ 1 ] ) ) {
			return $this->fixUrl( $path );
		}

		// remove double and triple slashes
		return str_replace( C::DS . C::DS, C::DS, str_replace( C::DS . C::DS, C::DS, str_replace( '\\', '/', $path ) ) );
	}

	/**
	 * @param string $url
	 *
	 * @return string
	 */
	public function fixUrl( string $url ): string
	{
		$sep = explode( '://', $url );
		if ( is_array( $sep ) && isset( $sep[ 1 ] ) ) {
			return $sep[ 0 ] . '://' . str_replace( C::DS . C::DS, C::DS, str_replace( C::DS . C::DS, C::DS, str_replace( '\\', '/', $sep[ 1 ] ) ) );

		}

		return $this->fixPath( $url );
	}

	/**
	 * @param string $file
	 * @param string $buffer
	 * @param bool $append
	 *
	 * @return bool
	 * @throws \Sobi\Error\Exception
	 */
	public function write( string $file, string $buffer, bool $append = false ): bool
	{
		if ( $append ) {
			$content = $this->read( $file );
			$buffer = $content . $buffer;
		}
		$return = Jfile::write( $file, $buffer );
		if ( $return === false ) {
			throw new Exception( Framework::Error( 'CANNOT_WRITE_TO_FILE_AT', $file ) );
		}
		else {
			return true;
		}
	}

	/**
	 * @param string $name
	 * @param string $destination
	 *
	 * @return bool
	 * @throws \Sobi\Error\Exception
	 */
	public function upload( string $name, string $destination ): bool
	{
		if ( !( file_exists( dirname( $destination ) ) ) ) {
			$this->mkdir( dirname( $destination ) );
		}
		/** Ajax uploader exception
		 * @todo: have to be moved to component
		 */
		if ( strstr( $name, str_replace( '\\', '/', SOBI_PATH ) ) ) {
			return $this->move( $name, $destination );
		}

		return Jfile::upload( $name, $destination, false, true );
	}

	/**
	 * @param string $path
	 * @param string $hex
	 *
	 * @return bool
	 */
	public function chmod( string $path, int $hex ): bool
	{
		return chmod( $path, $hex );
	}

	/**
	 * @param string $path
	 * @param int $mode
	 *
	 * @return bool
	 * @throws \Sobi\Error\Exception
	 */
	public function mkdir( string $path, int $mode = 0755 ): bool
	{
		$path = $this->clean( $path );
		if ( !( JFolder::create( $path, $mode ) ) ) {
			throw new Exception( Framework::Error( 'CANNOT_CREATE_DIR', str_replace( C::ROOT, C::ES, $path ) ) );
		}
		else {
			return true;
		}
	}

	/**
	 * @param string $path
	 *
	 * @return bool
	 */
	public function rmdir( string $path ): bool
	{
		return JFolder::delete( $path );
	}

	/**
	 * @param string $path
	 *
	 * @return bool
	 */
	public function readable( string $path ): bool
	{
		return is_readable( $path );
	}

	/**
	 * @param string $path
	 *
	 * @return bool
	 */
	public function writable( string $path ): bool
	{
		return is_writable( $path );
	}

	/**
	 * @param string $path
	 *
	 * @return int
	 */
	public function owner( string $path ): int
	{
		return fileowner( $path );
	}

	/**
	 * @param string $source
	 * @param string $destination
	 *
	 * @return bool
	 */
	public function rename( string $source, string $destination ): bool
	{
		return $this->move( $source, $destination );
	}

	/**
	 * @param string $file
	 * @param int $options
	 *
	 * @return DOMDocument
	 */
	public function & loadXML( string $file, int $options = 0 ): DOMDocument
	{
		$d = new DOMDocument();
		$d->load( realpath( $file ), $options );

		return $d;
	}

	/**
	 * @param string $path
	 * @param bool $sections
	 * @param bool $skipCustom - do not try to find overrides
	 *
	 * @return array
	 * @throws \Sobi\Error\Exception
	 */
	public function loadIniFile( string $path, bool $sections = true, bool $skipCustom = false, $mode = INI_SCANNER_NORMAL ): array
	{
		if ( !( $skipCustom ) ) {
			$customIni = $this->loadIniFile( $path . '_override', $sections, true, $mode );
			if ( $customIni && is_array( $customIni ) ) {
				return $customIni;
			}
		}
		$path .= '.ini';
		if ( !file_exists( $path ) || !is_readable( $path ) ) {
			// if we tried to load override, and it does not exist - do not rise any errors
			if ( !( $skipCustom ) ) {
				throw new Exception( sprintf( 'Cannot load file at %s', str_replace( JPATH_ROOT . '/', C::ES, $path ) ) );
			}
			else {
				return [];
			}
		}
		else {
			ob_start();
			$ini = parse_ini_file( $path, $sections, $mode );
			ob_end_clean();

			return is_array( $ini ) ? $ini : [];
		}
	}

	/**
	 * @param string $path
	 * @param array $values
	 *
	 * @return bool
	 * @throws \Sobi\Error\Exception
	 */
	public function writeIniFile( string $path, array $values ): bool
	{
		$data = null;
		foreach ( $values as $key => $val ) {
			if ( is_array( $val ) ) {
				$data[] = "[$key]";
				foreach ( $val as $skey => $sval ) {
					if ( is_array( $sval ) ) {
						foreach ( $sval as $_skey => $_sval ) {
							if ( is_numeric( $_skey ) ) {
								$data[] = $skey . '[] = ' . ( is_numeric( $_sval ) ? $_sval : ( ctype_upper( $_sval ) ? $_sval : '"' . $_sval . '"' ) );
							}
							else {
								$data[] = $skey . '[' . $_skey . '] = ' . ( is_numeric( $_sval ) ? $_sval : ( ctype_upper( $_sval ) ? $_sval : '"' . $_sval . '"' ) );
							}
						}
					}
					else {
						$data[] = $this->createValue( $skey, $sval );
					}
				}
			}
			else {
				$data[] = $this->createValue( $skey, $sval );
			}
			// empty line
			$data[] = null;
		}

		$data = implode( PHP_EOL, $data ) . PHP_EOL;

		return $this->write( $path, $data );
	}

	/**
	 * @param $skey
	 * @param $sval
	 *
	 * @return string
	 */
	protected function createValue( $skey, $sval ): string
	{
		/* check if indicator for boolean type is set */
		if ( strpos( $skey, 'bool.' ) === 0 ) {
			/* remove indicator */
			$skey = str_replace( 'bool.', '', $skey );
			/* convert 0/1 to false/true */
			$sval = $sval == 0 ? 'false' : 'true';
			$value = $skey . ' = ' . $sval;
		}
		else {
			$value = $skey . ' = ' . ( is_numeric( $sval ) ? $sval : ( ctype_upper( $sval ) ? $sval : '"' . $sval . '"' ) );
		}

		return $value;
	}
}
