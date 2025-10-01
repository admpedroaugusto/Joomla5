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
 * @created 4-Nov-2010 by Radek Suski
 * @modified 14 May 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

use Sobi\C;
use Sobi\FileSystem\FileSystem;

/**
 * Class SPFileInfo
 */
class SPFileInfo
{
	/**
	 * @var string - file path
	 */
	private $_path = C::ES;
	/**
	 * @var string
	 */
	private $_mime = C::ES;
	/**
	 * @var string
	 */
	private $_charset = C::ES;
	/**
	 * @var array
	 */
	private static $_exts = [];

	/**
	 * SPFileInfo constructor.
	 *
	 * @param $file
	 */
	public function __construct( $file )
	{
		$this->_path = $file;
	}

	/**
	 * Returns the mime type of a given file.
	 *
	 * @return string
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function mimeType(): string
	{
		if ( !strlen( $this->_mime ) ) {
			if ( !$this->mimeFromFinfo() || !strlen( $this->_mime ) ) {
				if ( !$this->mimeFromShell() || !strlen( $this->_mime ) ) {
					if ( $this->mimeFromExt() ) {
						Sobi::Error( 'FileInfo', SPLang::e( 'There is no reliable method to determine the right file type. Fallback to file extension.' ), SPC::WARNING, 0 );
					}
				}
			}
		}

		return $this->_mime;
	}

	/**
	 * Returns charset of a given file.
	 *
	 * @return string
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function charset(): string
	{
		if ( !strlen( $this->_mime ) ) {
			$this->mimeType();
		}

		return $this->_charset;
	}

	/**
	 * @return bool
	 */
	private function mimeFromShell(): bool
	{
		if ( strtolower( PHP_OS ) == 'darwin' ) {
			if ( ( $this->_mime = exec( 'file -bI ' . escapeshellarg( $this->_path ) ) ) && strlen( $this->_mime ) ) {   //instead -bi
				$this->parseMime();

				return true;
			}
		}
		elseif ( !strstr( strtolower( PHP_OS ), 'win' ) ) {
			if ( ( $this->_mime = exec( 'file -bi ' . escapeshellarg( $this->_path ) ) ) && strlen( $this->_mime ) ) {
				/*
				 * it's a stupid exception for MS docs files
				 * The linux command "file -bi" returns then this: application/msword application/msword
				 * which sucks totally :(
				 */
//				if ( strstr( $this->_mime, ' ' ) && !( strstr( $this->_mime, ';' ) ) ) {
//					$this->_mime = explode( ' ', $this->_mime );
//					if ( trim( $this->_mime[ 0 ] ) == ( $this->_mime[ 1 ] ) ) {
//						$this->_mime = $this->_mime[ 0 ];
//					}
//				}
				$this->parseMime();

				return true;
			}
		}

		return false;
	}

	/**
	 * @return bool
	 */
	protected function mimeFromFinfo(): bool
	{
		if ( function_exists( 'finfo_file' ) && $this->_path && FileSystem::Exists( $this->_path ) ) {
			$finfo = new finfo( FILEINFO_MIME );
			$this->_mime = $finfo->file( $this->_path );
			$this->parseMime();

			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * @return void
	 */
	protected function parseMime()
	{
		$this->_mime = preg_split( '/[;=]/', $this->_mime );
		$this->_charset = $this->_mime[ 2 ] ?? C::ES;
		$this->_mime = $this->_mime[ 0 ];
	}

	/**
	 * @return bool
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function mimeFromExt(): bool
	{
		$ext = FileSystem::GetExt( $this->_path );
		if ( !count( self::$_exts ) ) {
			self::$_exts = FileSystem::LoadIniFile( SOBI_PATH . '/etc/adm/mime', false );
		}
		if ( !isset( self::$_exts[ $ext ] ) ) {
			Sobi::Error( 'FileInfo', SPLang::e( 'Cannot determine the right file type from the file extension.' ), C::WARNING, 0 );

			return false;
		}
		else {
			$this->_mime = self::$_exts[ $ext ];

			return true;
		}
	}
}
