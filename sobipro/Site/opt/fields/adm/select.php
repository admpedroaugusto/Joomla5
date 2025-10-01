<?php
/**
 * @package SobiPro multi-directory component with content construction support
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/GPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/gpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * @created 19-Nov-2009 by Radek Suski
 * @modified 04 January 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

use Sobi\C;
use Sobi\Input\Input;
use Sobi\FileSystem\Archive;
use Sobi\FileSystem\FileSystem;
use Sobi\Lib\Factory;
use Sobi\Utils\Arr;
use Sobi\Utils\StringUtils;

SPLoader::loadClass( 'opt.fields.select' );

/**
 * Class SPField_SelectAdm
 */
class SPField_SelectAdm extends SPField_Select
{
	public const SELECTLIST_PATH = SOBI_PATH . '/etc/fields/select-list/';

	/**
	 * @param $attr
	 *
	 * @throws \Sobi\Error\Exception
	 */
	public function save( &$attr )
	{
		/* add the field specific attributes as param to the general attributes. */
		$options = $attr[ 'options' ];
		unset( $attr[ 'options' ] );    /* temporary remove the options */
		$attr[ 'defaultValue' ] = $attr[ 'defaultValue' ] ? StringUtils::Nid( $attr[ 'defaultValue' ] ) : C::ES;

		parent::save( $attr );
		$attr[ 'options' ] = $options;
	}

	/**
	 * Saves the field specific data for a new or duplicated field.
	 *
	 * @param $attr
	 * @param $fid
	 *
	 * @throws \Sobi\Error\Exception|\SPException
	 */
	public function saveNew( &$attr, $fid = 0 )
	{
		if ( $fid ) {
			$this->id = $this->fid = $fid;
		}
		$this->save( $attr );
	}

	/**
	 * Saves options and language dependent data to the database.
	 *
	 * @param array $attr
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function saveLanguageData( $attr ): void
	{
		if ( array_key_exists( 'mselect', $attr ) && $attr[ 'searchMethod' ] == 'mselect' && array_key_exists( 'dependency', $attr ) && $attr[ 'dependency' ] ) {
			throw new SPException( SPLang::e( 'SELECT_FIELD_MULTIPLE_DEPENDENCY' ) );
		}

		/** handle upload of a dependency definition file */
		$XMLFile = Input::File( 'select-list-dependency', 'tmp_name' );
		if ( $XMLFile && FileSystem::Exists( $XMLFile ) ) {
			$XMLFileName = Input::File( 'select-list-dependency', 'name' );
			if ( FileSystem::GetExt( $XMLFileName ) == 'zip' ) {
				$arch = new Archive();
				$name = str_replace( '.zip', C::ES, $XMLFileName );
				$path = SPLoader::dirPath( SPC::INSTALL_PATH . $name, 'front', false );
				$filecounter = 0;
				while ( FileSystem::Exists( $path ) ) {
					$path = SPLoader::dirPath( SPC::INSTALL_PATH . $name . '_' . ++$filecounter, 'front', false );
				}
				$arch->upload( $XMLFile, $path . '/' . $XMLFileName );
				$arch->extract( $path );
				$files = scandir( $path );
				if ( is_array( $files ) && count( $files ) ) {
					foreach ( $files as $defFile ) {
						switch ( FileSystem::GetExt( $defFile ) ) {
							case 'xml':
								$attr[ 'dependencyDefinition' ] = $defFile;
								FileSystem::Move( $path . '/' . $defFile, self::SELECTLIST_PATH . $defFile );
								break;
							case 'ini':
								$defLang = explode( '.', $defFile );
								$defLang = $defLang[ 0 ];
								if ( FileSystem::Exists( SOBI_ROOT . '/language/' . $defLang ) ) {
									FileSystem::Move( $path . '/' . $defFile, SOBI_ROOT . '/language/' . $defLang . '/' . $defFile );
								}
								break;
						}
					}
				}
			}
			else {
				if ( FileSystem::GetExt( $XMLFileName ) == 'xml' ) {
					if ( FileSystem::Upload( $XMLFile, self::SELECTLIST_PATH . $XMLFileName ) ) {
						$attr[ 'dependencyDefinition' ] = $XMLFileName;
					}
				}
			}
		}

		/* if a dependency file should be used, transform it */
		if ( $attr[ 'dependency' ] && $attr[ 'dependencyDefinition' ] ) {
			$this->parseDependencyDefinition( $attr[ 'dependencyDefinition' ] );
			unset( $attr[ 'options' ] );
		}

		/* no dependency select list */
		else {
			/* save the options and language dependent values to the database */
			$this->saveOptions( $attr );
		}

		/* save the language dependent select labels */
		$this->saveSelectLabel( $attr );
	}

	/**
	 * @param $file
	 */
	protected function parseDependencyDefinition( $file )
	{
		$dom = new DOMDocument();
		$dom->load( self::SELECTLIST_PATH . $file );
		$xpath = new DOMXPath( $dom );
		$definition = [];
		$root = $xpath->query( '/definition' );

		/* only if it is a real definition file */
		if ( $root->length ) {
			$definition[ 'prefix' ] = $root->item( 0 )->attributes->getNamedItem( 'prefix' )->nodeValue;
			$definition[ 'translation' ] = $root->item( 0 )->attributes->getNamedItem( 'translation' )->nodeValue;
			$definition[ 'options' ] = [];
			$this->_parseXML( $xpath->query( '/definition/option' ), $definition[ 'options' ] );

			$buffer = json_encode( $definition );
			FileSystem::Write( self::SELECTLIST_PATH . 'definitions/' . str_replace( '.xml', '.json', $file ), $buffer );
		}
	}

	/**
	 * @param DOMNodeList $nodes
	 * @param $definition
	 */
	protected function _parseXML( DOMNodeList $nodes, &$definition )
	{
		foreach ( $nodes as $node ) {
			if ( !$node->attributes ) {
				continue;
			}
			$option = [
				'id'     => $node->attributes->getNamedItem( 'id' )->nodeValue,
				'childs' => [],
			];
			if ( $node->attributes->getNamedItem( 'child-type' ) ) {
				$option[ 'child-type' ] = $node->attributes->getNamedItem( 'child-type' )->nodeValue;
			}
			if ( $node->hasChildNodes() ) {
				$this->_parseXML( $node->childNodes, $option[ 'childs' ] );
			}
			$definition[ $option[ 'id' ] ] = $option;
		}
	}

	/**
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	public function delete()
	{
		try {
			Factory::Db()->delete( 'spdb_field_option', [ 'fid' => $this->id ] );
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}
	}
}