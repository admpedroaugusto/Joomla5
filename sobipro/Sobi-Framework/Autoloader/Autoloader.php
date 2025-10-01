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
 * @modified 07 September 2021 by Radek Suski
 */
//declare( strict_types=1 );

namespace Sobi\Autoloader;

defined( 'SOBI' ) || exit( 'Restricted access' );

use Sobi\Error\Exception;

/**
 * Class Autoloader
 * @package Sobi\Autoloader
 */
class Autoloader
{
	/** @var array */
	protected $classes = [];
	/** @var array */
	protected $applicationsPrefixes = [ 'Joomla' => 'J', 'Wordpress' => 'WP' ];

	/**
	 * @return Autoloader
	 */
	public static function getInstance(): Autoloader
	{
		static $self = null;
		if ( !is_object( $self ) ) {
			$self = new self();
		}

		return $self;
	}


	/**
	 * @return $this
	 */
	public function & register(): Autoloader
	{
		spl_autoload_register( [ $this, 'load' ], true );

		return $this;
	}


	/**
	 * @param string $class
	 * @param string $path
	 * @param bool $override
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function registerClass( string $class, string $path, bool $override = false ): bool
	{
		if ( !( isset( $this->classes[ $class ] ) ) || $override ) {
			if ( file_exists( $path ) && is_readable( $path ) ) {
				$this->classes[ $class ] = $path;

				return true;
			}
			else {
				throw new Exception( "Class definition of {$class} doesn't exists" );
			}
		}
		else {
			return false;
		}
	}

	/**
	 * @param array $classes
	 *
	 * @throws \Sobi\Error\Exception
	 */
	public function registerClasses( array $classes )
	{
		foreach ( $classes as $class => $path ) {
			$this->registerClass( $class, $path );
		}
	}

	/**
	 * @return Autoloader
	 */
	public function & unregister(): Autoloader
	{
		spl_autoload_unregister( [ $this, 'load' ] );

		return $this;
	}

	/**
	 * @param string $class
	 *
	 * @throws \Exception
	 */
	protected function load( string $class )
	{
		$path = explode( '\\', $class );
		if ( $path[ 0 ] == 'Sobi' ) {
			unset( $path[ 0 ] );
			$regularPath = implode( '/', $path );
			if ( file_exists( dirname( __DIR__ . '../' ) . '/' . $regularPath . '.php' ) ) {
				include_once dirname( __DIR__ . '../' ) . '/' . $regularPath . '.php';
			}
			else {
				// file names of Application (CMS) specific files are prefixed
				$index = count( $path );
				$path[ $index ] = $this->applicationsPrefixes[ SOBI_APP ] . $path[ $index ];
				$overridePath = implode( '/', $path );
				if ( file_exists( dirname( __DIR__ . '../' ) . '/' . $overridePath . '.php' ) ) {
					include_once dirname( __DIR__ . '../' ) . '/' . $overridePath . '.php';
				}
				else {
					throw new \Exception( "Can't find class {$class} definition" );
				}
			}
		}
		// @todo probably not needed anymore because of the loop detection
		elseif ( isset( $path[ 1 ] ) && isset( $path[ 2 ] ) && file_exists( dirname( __DIR__ . '../' ) . '/ThirdParty/' . $path[ 1 ] . '/' . $path[ 2 ] . '.php' ) ) {
			unset( $path[ 0 ] );
			$path = implode( '/', $path );
			/** @noinspection PhpIncludeInspection */
			include_once dirname( __DIR__ . '../' ) . '/ThirdParty/' . $path . '.php';
		}
		elseif ( isset( $this->classes[ $class ] ) ) {
			/** @noinspection PhpIncludeInspection */
			include_once $this->classes[ $class ];
		}
		elseif ( count( $path ) ) {
			$include = dirname( __DIR__ . '../' ) . '/ThirdParty';
			foreach ( $path as $file ) {
				if ( file_exists( $include . '/' . $file ) ) {
					$include = $include . '/' . $file;
				}
				elseif ( file_exists( $include . '/' . $file . '.php' ) ) {
					/** @noinspection PhpIncludeInspection */
					include_once $include . '/' . $file . '.php';
				}
			}
		}

	}
}
