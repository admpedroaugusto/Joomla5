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
 * @created 05-Jul-2010 by Radek Suski
 * @modified 05 July 2022 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

use Sobi\Lib\Factory;
use Sobi\Error\Exception;

/**
 * Class SPRequirements
 */
class SPRequirements
{
	/**
	 * SPRequirements constructor.
	 * (do not remove)
	 */
	public function __construct()
	{
	}

	/**
	 * @param DOMNodeList $requirements
	 *
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	public function check( $requirements )
	{
		if ( $requirements instanceof DOMNodeList ) {
			for ( $requiredapp = 0; $requiredapp < $requirements->length; $requiredapp++ ) {
				$reqVersion = null;
				if ( $requirements->item( $requiredapp )->attributes->getNamedItem( 'version' ) && $requirements->item( $requiredapp )->attributes->getNamedItem( 'version' )->nodeValue ) {
					$reqVersion = $this->parseVersion( $requirements->item( $requiredapp )->attributes->getNamedItem( 'version' )->nodeValue );
				}
				switch ( $requirements->item( $requiredapp )->nodeName ) {
					case 'core':
						if ( !$this->compareVersion( $reqVersion, Factory::ApplicationHelper()->myVersion() ) ) {
							$myVersion = Factory::ApplicationHelper()->myVersion( false, 'com_sobipro' );
							if ( $myVersion[ 'major' ] != $reqVersion[ 'major' ] ) {
								/* application is not suitable for this SobiPro version */
								throw new SPException( SPLang::e( 'CANNOT_INSTALL_EXT_WRONG_CORE', $reqVersion[ 'major' ], $myVersion[ 'major' ] ) );
							}
							else {
								throw new SPException( SPLang::e( 'CANNOT_INSTALL_EXT_CORE', implode( '.', $reqVersion ), implode( '.', $myVersion ) ) );
							}
						}
						break;
					case 'cms':
						$cms = Factory::ApplicationHelper()->applicationVersion();
						if ( !is_array( $cms ) ) {
							throw new SPException( SPLang::e( 'CANNOT_INSTALL_EXT_REQU_CMS', $requirements->item( $requiredapp )->nodeValue, implode( '.', $reqVersion ), $cms ) );
						}
						if ( !$this->compareVersion( $reqVersion, $cms ) ) {
							throw new SPException( SPLang::e( 'CANNOT_INSTALL_EXT_REQ', $requirements->item( $requiredapp )->nodeValue, implode( '.', $reqVersion ), implode( '.', $cms ) ) );
						}
						break;
					case 'field':
					case 'plugin':
					case 'application':
					$version = $this->extension( $requirements->item( $requiredapp )->nodeValue, $requirements->item( $requiredapp )->nodeName );
					if ( !( $version ) ) {
						// "Cannot install extension. This extension requires %s %s version >= %s, But this %s is not installed."
						if ( $reqVersion ) {
							throw new SPException( SPLang::e( 'CANNOT_INSTALL_EXT_PLG', $requirements->item( $requiredapp )->nodeName, $requirements->item( $requiredapp )->nodeValue, implode( '.', $reqVersion ), $requirements->item( $requiredapp )->nodeName ) );
						}
						else {
							throw new SPException( SPLang::e( 'CANNOT_INSTALL_EXT_PLG_NO_VERSION', $requirements->item( $requiredapp )->nodeName, $requirements->item( $requiredapp )->nodeValue, $requirements->item( $requiredapp )->nodeName ) );
						}
					}
					if ( isset( $reqVersion ) && !$this->compareVersion( $reqVersion, $version ) ) {
						throw new SPException( SPLang::e( 'CANNOT_INSTALL_EXT_FIELD', $requirements->item( $requiredapp )->nodeName, $requirements->item( $requiredapp )->nodeValue, implode( '.', $reqVersion ), implode( '.', $version ) ) );
					}
					break;
					case 'php':
						$version = $this->phpReq( $requirements->item( $requiredapp ) );
						$type = 'PHP';
						if ( ( $requirements->item( $requiredapp )->attributes->getNamedItem( 'type' ) ) && ( $requirements->item( $requiredapp )->attributes->getNamedItem( 'type' )->nodeValue ) ) {
							$type = $requirements->item( $requiredapp )->attributes->getNamedItem( 'type' );
						}
						if ( strlen( $version ) && isset( $reqVersion ) ) {
							if ( isset( $reqVersion ) && !$this->compareVersion( $reqVersion, $version ) ) {
								throw new SPException( SPLang::e( 'CANNOT_INSTALL_EXT_FIELD', $type, $requirements->item( $requiredapp )->nodeValue, implode( '.', $reqVersion ), implode( '.', $version ) ) );
							}
						}
						elseif ( !$version ) {
							throw new SPException( SPLang::e( 'CANNOT_INSTALL_EXT_MISSING', $type, $requirements->item( $requiredapp )->nodeValue, implode( '.', $reqVersion ), implode( '.', $version ) ) );
						}
						break;
				}
			}
		}
	}

	/**
	 * @param $version
	 *
	 * @return array
	 */
	protected function parseVersion( $version ): array
	{
		$version = explode( '.', $version );

		return [ 'major' => $version[ 0 ],
		         'minor' => $version[ 1 ],
		         'build' => ( $version[ 2 ] ?? 0 ),
		         'rev'   => ( $version[ 3 ] ?? 0 ) ];
	}

	/**
	 * @param $node
	 *
	 * @return array|bool
	 * @todo = check disabled functions and classes
	 */
	protected function phpReq( $node )
	{
		if ( ( $node->attributes->getNamedItem( 'version' ) ) && ( $node->attributes->getNamedItem( 'version' )->nodeValue ) ) {
			if ( isset( $node->nodeValue ) && $node->nodeValue ) {
				$version = phpversion( $node->nodeValue );
			}
			else {
				$version = PHP_VERSION;
			}
			if ( !$version ) {
				return false;
			}

			return $this->parseVersion( $version );
		}
		elseif ( $node->attributes->getNamedItem( 'type' ) && ( $node->attributes->getNamedItem( 'type' )->nodeValue ) ) {
			switch ( $node->attributes->getNamedItem( 'type' )->nodeValue ) {
				case 'function':
					$retval = function_exists( $node->nodeValue );
					break;
				case 'class':
					$retval = class_exists( $node->nodeValue );
					break;
				default:
					$retval = false;
					break;
			}

			return $retval;
		}
	}

	/**
	 * @param $eid
	 * @param $type
	 *
	 * @return false|mixed
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function extension( $eid, $type )
	{
		static $extensions = null;
		if ( !$extensions ) {
			try {
				Factory::Db()->select( [ 'version', 'type', 'pid' ], 'spdb_plugins' );
				$exts = Factory::Db()->loadObjectList();
			}
			catch ( Exception $x ) {
				Sobi::Error( 'installer', SPLang::e( 'CANNOT_GET_INSTALLED_EXTS', $x->getMessage() ), SPC::WARNING, 500, __LINE__, __FILE__ );

				return false;
			}
			if ( count( $exts ) ) {
				$extensions = [ 'plugin' => [], 'field' => [], 'payment' => [] ];
				foreach ( $exts as $ext ) {
					$extensions[ $ext->type ][ $ext->pid ] = $this->parseVersion( $ext->version );
				}
			}
		}

		return $extensions[ $type ][ $eid ] ?? false;
	}

	/**
	 * @param $req -> required version
	 * @param $to -> current version
	 *
	 * @return bool -> true, is ok
	 */
	protected function compareVersion( $req, $to ): bool
	{
		/* it needs to have the same major version */
		if ( $req[ 'major' ] != $to[ 'major' ] ) {
			return false;
		}
		if ( $req[ 'minor' ] > $to[ 'minor' ] ) {
			return false;
		}
		elseif ( $req[ 'minor' ] < $to[ 'minor' ] ) {
			return true;
		}
		if ( $req[ 'build' ] > $to[ 'build' ] ) {
			return false;
		}
		elseif ( $req[ 'build' ] < $to[ 'build' ] ) {
			return true;
		}

		if ( $req[ 'rev' ] > $to[ 'rev' ] ) {
			return false;
		}

		return true;
	}
}
