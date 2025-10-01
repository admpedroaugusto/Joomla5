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
 * @created 3 December 2012 by Radek Suski
 * @modified 23 November 2022 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadController( 'controller' );

use Sobi\C;
use Sobi\Input\Input;
use Sobi\FileSystem\File;
use Sobi\FileSystem\FileSystem;

/**
 * Class SPFileUploader
 */
class SPFileUploader extends SPController
{
	/**
	 * @return void
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function execute()
	{
		$this->_task = strlen( $this->_task ) ? $this->_task : $this->_defTask;
		switch ( $this->_task ) {
			case 'upload':
				$this->upload();
				break;
		}
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function upload()
	{
		$ident = Input::Cmd( 'ident', 'post' );
		$data = SPRequest::file( $ident, 'tmp_name' );  //Input::File() does not work for big files
		$secret = md5( Sobi::Cfg( 'secret' ) );
		if ( $data ) {
			$properties = SPRequest::file( $ident );
			$fileName = md5( SPRequest::file( $ident, 'name' ) . time() . $secret );
			$path = SPLoader::dirPath( "tmp/files/$secret", 'front', false ) . '/' . $fileName;
			$file = new File();
			if ( !$file->upload( $data, $path ) ) {
				$this->message( [ 'type' => 'error', 'text' => SPLang::e( 'CANNOT_UPLOAD_FILE' ), 'id' => C::ES ] );
			}
			$path = $file->getPathname();
			$type = $this->check( $path );
			$properties[ 'tmp_name' ] = $path;
			$buffer = SPConfig::serialize( $properties );
			FileSystem::Write( $path . '.var', $buffer );
			$response = [
				'type' => 'success',
				'text' => Sobi::Txt( 'FILE_UPLOADED', $properties[ 'name' ], $type ),
				'id'   => 'file://' . $fileName,
				'data' => [ 'name' => $properties[ 'name' ], 'type' => $properties[ 'type' ], 'size' => $properties[ 'size' ] ],
			];
		}
		else {
			$response = [
				'type' => 'error',
				'text' => SPLang::e( 'CANNOT_UPLOAD_FILE_NO_DATA' ),
				'id'   => C::ES,
			];
		}
		$this->message( $response );
	}

	/**
	 * Checks an uploaded file of its correct mime type (not by file extension).
	 * For e.g. image and download fields
	 *
	 * @param $file
	 *
	 * @return mixed
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function check( $file )
	{
		$allowed = FileSystem::LoadIniFile( SOBI_PATH . '/etc/files' );
		$mime = SPFactory::Instance( 'services.fileinfo', $file )->mimeType();
		if ( !$mime || ( strlen( $mime ) && !in_array( $mime, $allowed ) ) ) {
			FileSystem::Delete( $file );
			$this->message( [ 'type' => 'error', 'text' => SPLang::e( 'FILE_WRONG_TYPE', $mime ), 'id' => C::ES ] );
		}

		return $mime;
	}

	/**
	 * @param $response
	 *
	 * @throws SPException
	 */
	protected function message( $response )
	{
		SPFactory::mainframe()
			->cleanBuffer()
			->customHeader();
		echo json_encode( $response );

		exit;
	}
}
