<?php
/**
 * @package SobiPro Library
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
 * @created 06-Aug-2010 by Radek Suski
 * @modified 27 November 2023 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

use Sobi\C;
use Sobi\FileSystem\FileSystem;
use Sobi\Input\Input;
use Sobi\Lib\Factory;

SPLoader::loadController( 'config', true );

/**
 * Class SPError
 */
class SPError extends SPConfigAdmCtrl
{
	/**
	 * @var string
	 */
	protected $_defTask = 'list';

	/**
	 * @return void
	 * @throws \ReflectionException
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception|\DOMException
	 */
	public function execute()
	{
		$this->_task = strlen( $this->_task ) ? $this->_task : $this->_defTask;
		switch ( $this->_task ) {
			case 'list':
				$this->screen();
				Sobi::ReturnPoint();
				break;
			case 'purge':
				$this->purge();
				break;
			case 'download':
				$this->download();
				break;
			case 'details':
				$this->details();
				break;
			default:
				/* case plugin didn't register this task, it was an error */
				if ( !parent::execute() ) {
					Sobi::Error( 'error_ctrl', 'Task not found', C::WARNING, 404, __LINE__, __FILE__ );
				}
				break;
		}
	}

	/**
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception|\DOMException
	 */
	private function download()
	{
		$errorDocument = new DOMDocument( '1.0', 'utf-8' );
		$errorDocument->formatOutput = true;

		$rootElement = $errorDocument->createElement( 'errorLog' );
		$dateAttribute = $errorDocument->createAttribute( 'createdAt' );
		$dateAttribute->appendChild( $errorDocument->createTextNode( date( DATE_RFC822 ) ) );
		$rootElement->appendChild( $dateAttribute );

		$siteAttribute = $errorDocument->createAttribute( 'site' );
		$siteAttribute->appendChild( $errorDocument->createTextNode( Sobi::Cfg( 'live_site' ) ) );
		$rootElement->appendChild( $siteAttribute );

		$errorDocument->appendChild( $rootElement );
		$levels = $this->levels();

		$errors = [];
		try {
			$errors = Factory::Db()->select( '*', 'spdb_errors', C::ES, 'eid.desc' )->loadAssocList();
		}
		catch ( Sobi\Error\Exception $x ) {
		}

		$counter = 0;
		if ( count( $errors ) ) {
			foreach ( $errors as $i => $err ) {
				$counter++;
				if ( $counter > Sobi::Cfg( 'err_log.limit', 50 ) ) {
					break;
				}
				$err[ 'errNum' ] = $levels[ $err[ 'errNum' ] ];
				$errorElement = $errorDocument->createElement( 'error' );

				$dateAttribute = $errorDocument->createAttribute( 'date' );
				$dateAttribute->appendChild( $errorDocument->createTextNode( $err[ 'date' ] ) );
				$errorElement->appendChild( $dateAttribute );

				$levelAttribute = $errorDocument->createAttribute( 'level' );
				$levelAttribute->appendChild( $errorDocument->createTextNode( $err[ 'errNum' ] ) );
				$errorElement->appendChild( $levelAttribute );

				$codeAttribute = $errorDocument->createAttribute( 'returnCode' );
				$codeAttribute->appendChild( $errorDocument->createTextNode( $err[ 'errCode' ] ) );
				$errorElement->appendChild( $codeAttribute );

				$sectionAttribute = $errorDocument->createAttribute( 'section' );
				$sectionAttribute->appendChild( $errorDocument->createTextNode( $err[ 'errSect' ] ) );
				$errorElement->appendChild( $sectionAttribute );

				$err[ 'errBacktrace' ] = unserialize( gzuncompress( base64_decode( $err[ 'errBacktrace' ] ) ) );
				foreach ( $err[ 'errBacktrace' ] as $index => $error ) {
					$err[ 'errBacktrace' ][ $index ] = str_replace( SOBI_ROOT, C::ES, $error );
				}
//				$err[ 'Backtrace' ] = str_replace( SOBI_ROOT, C::ES, $err[ 'errBacktrace' ] );

				$err[ 'errMsg' ] = str_replace( SOBI_ROOT, C::ES, $err[ 'errMsg' ] );

				$err[ 'errCont' ] = unserialize( gzuncompress( base64_decode( $err[ 'errCont' ] ) ) );
				if ( is_array( $err[ 'errCont' ] ) ) {
					foreach ( $err[ 'errCont' ] as $index => $error ) {
						$err[ 'errCont' ][ $index ] = str_replace( SOBI_ROOT, C::ES, $error );
					}
				}
				else {
					$err[ 'errCont' ] = str_replace( SOBI_ROOT, C::ES, $err[ 'errCont' ] );
				}

				$msgElement = $errorDocument->createElement( 'message', $err[ 'errMsg' ] );
				$errorElement->appendChild( $msgElement );

				$err[ 'errFile' ] = str_replace( SOBI_ROOT, C::ES, $err[ 'errFile' ] );
				$msgElement = $errorDocument->createElement( 'file', $err[ 'errFile' ] . ':' . $err[ 'errLine' ] );
				$errorElement->appendChild( $msgElement );

				$userElement = $errorDocument->createElement( 'user' );
				$uidAttribute = $errorDocument->createAttribute( 'uid' );
				$uidAttribute->appendChild( $errorDocument->createTextNode( $err[ 'errUid' ] ) );
				$userElement->appendChild( $uidAttribute );

				$ipElement = $errorDocument->createElement( 'ip', $err[ 'errIp' ] );
				$userElement->appendChild( $ipElement );

				$agentElement = $errorDocument->createElement( 'userAgent', $err[ 'errUa' ] );
				$userElement->appendChild( $agentElement );

				$uriElement = $errorDocument->createElement( 'requestedUri', str_replace( Sobi::Cfg( 'live_site' ), C::ES, htmlentities( $err[ 'errReq' ] ) ) );
				$userElement->appendChild( $uriElement );

//				$uriElement = $errorDocument->createElement( 'requestedUri', htmlentities( $err[ 'errReq' ] ) );
//				$userElement->appendChild( $uriElement );

				$refElement = $errorDocument->createElement( 'referrerUri', str_replace( Sobi::Cfg( 'live_site' ), C::ES, htmlentities( $err[ 'errRef' ] ) ) );
				$userElement->appendChild( $refElement );

				$errorElement->appendChild( $userElement );

				$stackElement = $errorDocument->createElement( 'callStack' );
				$stackElement->appendChild( $errorDocument->createCDATASection( "\n" . stripslashes( var_export( $err[ 'errCont' ], true ) ) . "\n" ) );
				$errorElement->appendChild( $stackElement );

				$traceElement = $errorDocument->createElement( 'callTrace' );
				$traceElement->appendChild( $errorDocument->createCDATASection( "\n" . stripslashes( var_export( $err[ 'errBacktrace' ], true ) ) . "\n" ) );
				$errorElement->appendChild( $traceElement );

				$rootElement->appendChild( $errorElement );
			}
		}

		$file = SPLoader::path( 'var/log/errors', 'front', false, 'xml' );
		$saveXML = $errorDocument->saveXML();
		FileSystem::Write( $file, $saveXML );
		$fp = FileSystem::Read( $file );

		SPFactory::mainframe()->cleanBuffer();
		header( "Content-type: application/xml" );
		header( 'Content-Disposition: attachment; filename=error.xml' );
		echo $fp;
		flush();

		exit;
	}

	/**
	 * @return array
	 */
	private function levels(): array
	{
		$levels = get_defined_constants();
		foreach ( $levels as $level => $v ) {
			if ( !preg_match( '/^E_/', $level ) ) {
				unset( $levels[ $level ] );
			}
		}

		return array_flip( $levels );
	}

	/**
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function details()
	{
		$id = Input::Int( 'eid' );
		try {
			$error = Factory::Db()
				->select( '*', 'spdb_errors', [ 'eid' => $id ] )
				->loadObject();
		}
		catch ( Sobi\Error\Exception $x ) {
		}

		$error->errCont = unserialize( gzuncompress( base64_decode( $error->errCont ) ) );
		$error->errBacktrace = unserialize( gzuncompress( base64_decode( $error->errBacktrace ) ) );
		$levels = $this->levels();

		/** @var SPAdmView $view */
		$view = SPFactory::View( 'error', true );
		$view
			->assign( $this->_task, 'task' )
			->assign( $levels, 'levels' )
			->assign( $error, 'error' );

		$view->display();
	}

	/**
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	private function purge()
	{
		try {
			Factory::Db()->truncate( 'spdb_errors' );
		}
		catch ( Sobi\Error\Exception $x ) {
			$this->response( Sobi::Url( 'error' ), Sobi::Txt( 'ERR.ERROR_LOG_NOT_DELETED', [ 'error' => $x->getMessage() ] ), false, C::ERROR_MSG );
		}
		if ( FileSystem::Exists( SOBI_PATH . '/var/log/error.log' ) ) {
			FileSystem::Delete( SOBI_PATH . '/var/log/error.log' );
		}
		$this->response( Sobi::Url( 'error' ), Sobi::Txt( 'ERR.ERROR_LOG_DELETED' ), false, SPC::SUCCESS_MSG );
	}

	/**
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function screen()
	{
		$eLimit = Sobi::GetUserState( 'adm.errors.limit', 'elimit', Sobi::Cfg( 'adm_list.entries_limit', 25 ) );
		$eLimStart = Input::Int( 'errSite', 'request', 1 );
		$LimStart = $eLimStart ? ( ( $eLimStart - 1 ) * $eLimit ) : $eLimStart;
		$eCount = 0;
		try {
			$eCount = Factory::Db()
				->select( 'COUNT(eid)', 'spdb_errors' )
				->loadResult();
		}
		catch ( Sobi\Error\Exception $x ) {
		}
		if ( $eLimit == -1 ) {
			$eLimit = $eCount;
		}
		try {
			$errors = Factory::Db()
				->select( [ 'eid', 'date', 'errNum', 'errCode', 'errFile', 'errLine', 'errMsg', 'errUid', 'errSect', 'errReq' ], 'spdb_errors', C::ES, 'eid.desc', $eLimit, $LimStart )
				->loadAssocList();
		}
		catch ( Sobi\Error\Exception $x ) {
		}
		$levels = $this->levels();

		$menu = $this->createMenu( 'error' );

		/** @var SPAdmView $view */
		$view = SPFactory::View( 'error', true );
		$view
			->assign( $this->_task, 'task' )
			->assign( $menu, 'menu' )
			->assign( $errors, 'errors' )
			->assign( $levels, 'levels' )
			->assign( $eLimit, 'errors-limit' )
			->assign( $eCount, 'errors-count' )
			->assign( $eLimStart, 'errors-site' );

		$view->display();
	}
}
