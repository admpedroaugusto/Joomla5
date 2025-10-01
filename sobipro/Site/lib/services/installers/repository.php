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
 * @created 22-Jun-2010 by Radek Suski
 * @modified 07 October 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

use Sobi\C;
use Sobi\FileSystem\File;
use Sobi\Communication\CURL;
use Sobi\Utils\Arr;

SPLoader::loadClass( 'services.installers.installer' );

/**
 * Class SPRepository
 */
class SPRepository extends SPInstaller
{

	/*** @var SPSoapClient */
	private $_server = null;
	/** @var array */
	protected $_repoDefArr = [];

	/**
	 * SPRepository constructor.
	 */
	public function __construct()
	{
	}

	/**
	 * @param $path
	 */
	public function loadDefinition( $path )
	{
		$this->xmlFile = $path;
		$this->definition = new DOMDocument();
		$this->definition->load( $this->xmlFile );
		$this->xdef = new DOMXPath( $this->definition );
		$this->root = dirname( $this->xmlFile );
		$this->type = 'repository';
	}

	/**
	 * @param $token
	 *
	 * @throws \Sobi\Error\Exception|\DOMException
	 */
	public function saveToken( $token )
	{
		$arrUtils = new Arr();
		$def = $arrUtils->fromXML( $this->definition, 'repository' );
		$ndef = [];
		$u = false;
		$rid = null;
		foreach ( $def[ 'repository' ] as $k => $v ) {
			if ( $u ) {
				$ndef[ 'token' ] = $token;
			}
			if ( $k == 'id' ) {
				$rid = $v;
			}
			if ( $k == 'url' ) {
				$u = true;
			}
			$ndef[ $k ] = $v;
		}
		$path = SPLoader::path( SPC::REPO_PATH . "$rid.repository", 'front', true, 'xml' );
		$file = new File( $path );
		$file->content( $arrUtils->toXML( $ndef, 'repository' ) );
		$file->save();
	}

	/**
	 * @return array
	 */
	public function getDef()
	{
		if ( empty( $this->_repoDefArr ) ) {
			$arrUtils = new Arr();
			$this->_repoDefArr = $arrUtils->fromXML( $this->definition, 'repository' );
		}

		return $this->_repoDefArr;
	}

	/**
	 * @param string $attr
	 * @param null $default
	 *
	 * @return mixed|string|null
	 */
	public function get( $attr, $default = null )
	{
		return $this->xGetString( $attr );
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function connect()
	{
		if ( ( $this->definition instanceof DOMDocument ) && $this->xGetString( 'url' ) ) {
			$connection = new CURL();
			$errno = $connection->error( false, true );
			$status = $connection->status( false, true );

			/* if CURL initialization failed (CURL not installed) */
			if ( $status || $errno ) {
				throw new SPException( 'Code ' . $status ? $connection->status() : $connection->error() );
			}
			$ssl = $connection->certificate( $this->xGetString( 'url' ) );
			if ( isset( $ssl[ 'err' ] ) ) {
				throw new SPException( $ssl[ 'msg' ] );
			}
			$certnumber = $this->xGetString( 'certificate/serialnumber' );
			if ( strpos( $certnumber, '0x' ) === 0 ) {   // if 0x at the beginning
				$certnumber = str_replace( '0x', C::ES, $certnumber );
			}
			if ( $ssl[ 'serialNumberHex' ] != $certnumber ) {
				throw new SPException(
					SPLang::e(
						'SSL validation error: stored serial number is %s but the serial number for the repository at %s has the number %s.',
						$certnumber,
						$this->xGetString( 'url' ),
						$ssl[ 'serialNumberHex' ]
					)
				);
			}
			// for some reason on some servers the hash is being indeed modified,
			// although it has been correctly transferred
			// it seems that it is depended on the protocol used (TSL/SSL)
//			if ( $ssl[ 'hash' ] != $this->xGetString( 'certificate/hash' ) ) {
//				throw new SPException(
//					SPLang::e(
//						'SSL validation error: stored hash does not accord the hash for the repository at %s. %s != %s',
//						$this->xGetString( 'url' ), $ssl[ 'hash' ], $this->xGetString( 'certificate/hash' )
//					)
//				);
//			}
			if ( $ssl[ 'validTo' ] < time() ) {
				throw new SPException(
					SPLang::e(
						'SSL validation error: SSL certificate for %s is expired.',
						$this->xGetString( 'url' )
					)
				);
			}
			$this->_server = SPFactory::Instance( 'services.soap', null, [ 'location' => $this->xGetString( 'url' ) ] );
		}
		else {
			throw new SPException( SPLang::e( 'No repository definition file %s or the definition is invalid.', $this->xmlFile ) );
		}
	}

	/**
	 * @param $fn
	 * @param $args
	 *
	 * @return array|mixed
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function __call( $fn, $args )
	{
		$return = [ 'error' => 500 ];
		array_unshift( $args, Sobi::Lang( false ) );
		array_unshift( $args, Sobi::Cfg( 'live_site' ) );
		if ( $this->_server instanceof SPSoapClient ) {
			try {
				$return = $this->_server->__soapCall( $fn, $args );
			}
			catch ( SoapFault $x ) {
				throw new SPException( $x->getMessage() );
			}
			/* what the hell ???!!!!!*/
			if ( $return instanceof SoapFault ) {
				throw new SPException( $return->getMessage() );
			}
		}

		return $return;
	}
}
