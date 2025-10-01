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
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 10-Jan-2009 by Radek Suski
 * @modified 10 August 2022 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

use Sobi\C;
use Sobi\FileSystem\DirectoryIterator;
use Sobi\FileSystem\FileSystem;
use Sobi\Lib\Factory;

SPLoader::loadView( 'view', true );

/**
 * Class SPConfigAdmView
 */
class SPConfigAdmView extends SPAdmView implements SPView
{
	/** @var bool */
	protected $_fout = true;
	/** @var SPConfigAdmCtrl */
	private $_ctrl = true;

	/**
	 * @param $ctrl
	 *
	 * @return $this
	 */
	public function &setCtrl( &$ctrl )
	{
		$this->_ctrl =& $ctrl;

		return $this;
	}

	/**
	 * @param $title
	 *
	 * @deprecated since 2.0
	 */
	public function setTitle( $title )
	{
//		$title = Sobi::Txt( $title, [ 'section' => $this->get( 'section.name' ) ] );
//		Sobi::Trigger( 'setTitle', $this->name(), [ &$title ] );
//		SPFactory::header()->setTitle( $title );
//		$this->set( $title, 'site_title' );
	}

	/**
	 * @param null $params
	 *
	 * @return array
	 * @throws SPException
	 */
	public function templatesList( $params = null )
	{
		$cms = [
			'name' => Sobi::Txt( 'TP.TEMPLATES_OVERRIDE' ),
			'icon' => Sobi::Cfg( 'live_site' ) . 'media/sobipro/tree/joomla.gif',
			'data' => Factory::ApplicationInstaller()->templatesPath( 'com_sobipro' ),
		];
		$dir = new DirectoryIterator( SPLoader::dirPath( 'usr.templates' ) );
		$templates = [];
		foreach ( $dir as $file ) {
			if ( $file->isDir() ) {
				if ( $file->isDot() || in_array( $file->getFilename(), [ 'common', 'front', 'storage' ] ) ) {
					continue;
				}
				if ( FileSystem::Exists( $file->getPathname() . '/' . 'template.xml' ) && ( $tname = $this->templateName( $file->getPathname() . '/' . 'template.xml' ) ) ) {
					$templates[ $file->getFilename() ] = $tname;
				}
				else {
					$templates[ $file->getFilename() ] = $file->getFilename();
				}
			}
		}
		if ( isset( $cms[ 'name' ] ) && isset( $cms[ 'data' ] ) && is_array( $cms[ 'data' ] ) && count( $cms[ 'data' ] ) ) {
			$templates[ $cms[ 'name' ] ] = [];
			foreach ( $cms[ 'data' ] as $name => $path ) {
				$templates[ $cms[ 'name' ] ][ $name ] = [];
				$dir = new DirectoryIterator( $path );
				foreach ( $dir as $file ) {
					if ( $file->isDot() ) {
						continue;
					}
					$fpath = 'cms:' . str_replace( SOBI_ROOT . '/', C::ES, $file->getPathname() );
					$fpath = str_replace( '/', '.', $fpath );
					if ( FileSystem::Exists( $file->getPathname() . '/' . 'template.xml' ) && ( $tname = $this->templateName( $file->getPathname() . '/' . 'template.xml' ) ) ) {
						$templates[ $cms[ 'name' ] ][ $name ][ $fpath ] = $tname;
					}
					else {
						$templates[ $cms[ 'name' ] ][ $name ][ $fpath ] = $file->getFilename();
					}
				}
			}
		}
		if ( $params ) {
			$p = [ 'select', 'spcfg_' . $params[ 1 ], $templates, Sobi::Cfg( 'section.template', SPC::DEFAULT_TEMPLATE ), false, $params[ 3 ] ];
		}
		else {
			$p = $templates;
		}

		return $p;
	}

	/**
	 * @param $file
	 *
	 * @return bool
	 */
	private function templateName( $file )
	{
		$def = new DOMDocument();
		$def->load( $file );
		$xdef = new DOMXPath( $def );
		$name = $xdef->query( '/template/name' )->item( 0 )->nodeValue;

		return strlen( $name ) ? $name : false;
	}

	/**
	 * @param null $unsets
	 * @param false $ordering
	 * @param string $view
	 *
	 * @return array
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function namesFields( $unsets = null, $ordering = false, $view = 'view' )
	{
		$fields = $this->_ctrl->getNameFields( $ordering );
		$fData = [ 0 => Sobi::Txt( 'SEC.CFG.ENTRY_TITLE_FIELD_SELECT' ) ];

		if ( count( $fields ) ) {
			foreach ( $fields as $fid => $field ) {
				if ( $ordering ) {
					$params = $field->get( 'params' );
					$encrypted = false;
					if ( isset ( $params[ 'encryptData' ] ) ) {
						if ( $params[ 'encryptData' ] == 1 ) {
							$encrypted = true;
						}
					}
					if ( $encrypted ) {
						unset( $fields[ $fid ] );
					}
					else {
						try {
							$fData = $field->setCustomOrdering( $fData, $view );
						}
						catch ( SPException $x ) {
							$asc = '.asc';
							$desc = '.desc';
							if ( $field->get( 'numeric' ) ) {
								$asc = '.num.asc';
								$desc = '.num.desc';
							}
							$fData[ $field->get( 'nid' ) . $asc ] = '\'' . $field->get( 'name' ) . '\' ' . Sobi::Txt( 'ORDER_BY_ASC' );
							$fData[ $field->get( 'nid' ) . $desc ] = '\'' . $field->get( 'name' ) . '\' ' . Sobi::Txt( 'ORDER_BY_DESC' );
						}
					}
				}
				else {
					$fData[ $fid ] = $field->get( 'name' );
				}
			}
		}
		if ( $ordering ) {
			unset( $fData[ 0 ] );
			$fData = [
				'position.asc'                 => Sobi::Txt( 'ORDER_BY_POSITION_ASC' ),
				'position.desc'                => Sobi::Txt( 'ORDER_BY_POSITION_DESC' ),
				'counter.asc'                  => Sobi::Txt( 'ORDER_BY_POPULARITY_ASC' ),
				'counter.desc'                 => Sobi::Txt( 'ORDER_BY_POPULARITY_DESC' ),
				'createdTime.asc'              => Sobi::Txt( 'ORDER_BY_CREATION_DATE_ASC' ),
				'createdTime.desc'             => Sobi::Txt( 'ORDER_BY_CREATION_DATE_DESC' ),
				'updatedTime.asc'              => Sobi::Txt( 'ORDER_BY_UPDATE_DATE_ASC' ),
				'updatedTime.desc'             => Sobi::Txt( 'ORDER_BY_UPDATE_DATE_DESC' ),
				'validUntil.asc'               => Sobi::Txt( 'ORDER_BY_EXPIRATION_DATE_ASC' ),
				'validUntil.desc'              => Sobi::Txt( 'ORDER_BY_EXPIRATION_DATE_DESC' ),
				'RAND()'                       => Sobi::Txt( 'ORDER_BY_RANDOM' ),
				Sobi::Txt( 'ORDER_BY_FIELDS' ) => $fData,
			];
		}
		// params will not be used for a long time
		// 11.2.19: reactivated with different meaning
		if ( $unsets && is_array( $unsets ) ) {
			foreach ( $unsets as $unset ) {
				unset ( $fData[ $unset ] );
			}
		}

		return $fData;
	}

	/**
	 * @param $multi
	 *
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function entryFields( $multi )
	{
		$fData = [];
		$fields = $this->_ctrl->getTypedFields( [] );

		if ( !$multi ) {
			$fData = [ 0 => Sobi::Txt( 'SEC.CFG.ENTRY_TITLE_FIELD_SELECT' ) ];
		}
		if ( count( $fields ) ) {
			foreach ( $fields as $fid => $field ) {
				$fData[ $field->get( 'nid' ) ] = $field->get( 'name' );
			}
		}

		return $fData;
	}

	/**
	 * @param $multi
	 *
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function categoryFields( $multi )
	{
		$fData = [];
		$fields = $this->_ctrl->getTypedFields( [], true );

		if ( !$multi ) {
			$fData = [ 0 => Sobi::Txt( 'SEC.CFG.ENTRY_TITLE_FIELD_SELECT' ) ];
		}
		if ( count( $fields ) ) {
			foreach ( $fields as $fid => $field ) {
				$fData[ $field->get( 'nid' ) ] = $field->get( 'name' );
			}
		}

		return $fData;
	}

	/**
	 * @param string $attr
	 * @param int $index
	 *
	 * @return array|int|mixed|string|null
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function & get( $attr, $index = -1 )
	{
		$config = SPFactory::config();
		if ( !$config->key( $attr, false ) ) {
			return parent::get( $attr, $index );
		}
		else {
			$value = $config->key( $attr );
			// Tue, Jun 4, 2013 15:21:19 : we have some arrays that have to be displayed as a string while editing config
			// see also bug #894
//			if ( is_array( $attr ) ) {
//				$attr = implode( ',', $attr );
//			}
			// ...  let's fix it ;)
			if ( is_array( $value ) && strstr( $attr, '_array' ) ) {
				$value = implode( ',', $value );
			}

			return $value;
		}
	}
}
