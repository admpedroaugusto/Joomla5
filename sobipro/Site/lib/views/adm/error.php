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
 * @created 10-Jun-2010 by Radek Suski
 * @modified 27 November 2023 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadView( 'view', true );

use Sobi\C;

/**
 * Class SPAdmError
 */
class SPAdmError extends SPAdmView
{
	/**
	 * @throws ReflectionException
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function display()
	{
		switch ( $this->get( 'task' ) ) {
			case 'list':
				$this->errors();
				$this->determineTemplate( 'config', 'errors' );
				break;
			case 'details':
				$this->details();
				$this->determineTemplate( 'config', 'error' );
				break;
		}
		parent::display();
	}

	/**
	 * @throws SPException|\Sobi\Error\Exception
	 */
	private function errors()
	{
		$errors = $this->get( 'errors' );
		$levels = $this->get( 'levels' );
		$icons = [
			'error'   => 'shield-alt dk-error',
			'warning' => 'shield-alt dk-warning',
			'notice'  => 'shield-alt dk-notice',
		];
		/* create the header */
		if ( is_array( $errors ) && count( $errors ) ) {
			foreach ( $errors as $i => $error ) {
				$error[ 'errFile' ] = str_replace( SOBI_ADM_PATH, C::ES, $error[ 'errFile' ] );
				$error[ 'errFile' ] = str_replace( SOBI_PATH, C::ES, $error[ 'errFile' ] );
				$error[ 'errFile' ] = str_replace( SOBI_ROOT, C::ES, $error[ 'errFile' ] );
				$error[ 'errFile' ] = $error[ 'errFile' ] . ': ' . $error[ 'errLine' ];
				if ( $error[ 'errReq' ] ) {
					$error[ 'errReq' ] = "<a href=\"{$error[ 'errReq' ]}\" >{$error[ 'errReq' ]}</a>";
				}
				$level = $levels[ $error[ 'errNum' ] ];
				switch ( $error[ 'errNum' ] ) {
					case E_ERROR:
					case E_CORE_ERROR:
					case E_COMPILE_ERROR:
					case E_USER_ERROR:
					case E_RECOVERABLE_ERROR:
						$error[ 'errNum' ] = "<span class=\"fas fa-{$icons[ 'error' ]}\" title=\"$level\" aria-hidden=\"true\"></span><br/>$level";
						break;
					case E_WARNING:
					case E_CORE_WARNING:
					case E_COMPILE_WARNING:
					case E_USER_WARNING:
					$error[ 'errNum' ] = "<span class=\"fas fa-{$icons[ 'warning' ]}\" title=\"$level\" aria-hidden=\"true\"></span><br/>$level";
						break;
					case E_NOTICE:
					case E_USER_NOTICE:
					case E_STRICT:
					case E_DEPRECATED:
					case E_USER_DEPRECATED:
						$error[ 'errNum' ] = "<span class=\"fas fa-{$icons[ 'notice' ]}\" title=\"$level\" aria-hidden=\"true\"></span><br/>$level";
						break;
				}
				$error[ 'errMsg' ] = str_replace( SOBI_ROOT, C::ES, $error[ 'errMsg' ] );
				$error[ 'errMsg' ] = str_replace( 'href=\'function.', 'target="_blank" href=\'https://php.net/manual/en/function.', $error[ 'errMsg' ] );
//				$dh = Sobi::Url( [ 'task' => 'error.details', 'eid' => $error[ 'eid' ] ] );
				$errors[ $i ] = $error;
			}
		}
//		Sobi::Error( 'H', date( DATE_RFC1123 ), SPC::ERROR );
		$this->assign( $errors, 'errors' );
	}

	/**
	 * @throws SPException|\Sobi\Error\Exception
	 */
	private function details()
	{
		$levels = $this->get( 'levels' );
		$error = $this->get( 'error' );
		if ( $error->errReq ) {
			$error->errReq = "<a href=\"$error->errReq\" target=\"_blank\">$error->errReq</a>";
		}
		if ( $error->errRef ) {
			$error->errRef = "<a href=\"$error->errRef\" target=\"_blank\">$error->errRef</a>";
		}
		if ( $error->errNum ) {
			$error->errNum = $levels[ $error->errNum ];
		}
		if ( $error->errBacktrace ) {
			/* remove the call stack data which logs the error ('backtrace', 'getBacktrace'
			 and 'SPExceptionHandler') */
			unset( $error->errBacktrace[ 0 ] );
			unset( $error->errBacktrace[ 1 ] );
			unset( $error->errBacktrace[ 2 ] );
			$error->errBacktrace = '<pre>' . SPConfig::debOut( $error->errBacktrace, false, true ) . '</pre>';
		}
		if ( $error->errCont ) {
			$error->errCont = '<pre>' . SPConfig::debOut( $error->errCont, false, true ) . '</pre>';
		}
		$error->errMsg = str_replace( 'href=\'function.', 'target="_blank" href=\'https://php.net/manual/en/function.', $error->errMsg );

		$this->assign( $error, 'error' );
	}
}
