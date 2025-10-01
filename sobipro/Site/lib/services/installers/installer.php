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
 * @created 17-Jun-2010 12:35:23 by Radek Suski
 * @modified 26 October 2022 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

use Sobi\C;
use Sobi\FileSystem\File;
use Sobi\FileSystem\FileSystem;
use Sobi\Communication\CURL;

/**
 * Class SPInstaller
 */
class SPInstaller extends SPObject
{
	/**
	 * @var string
	 */
	protected $type = C::ES;
	/**
	 * @var string
	 */
	protected $xmlFile = C::ES;
	/**
	 * @var string
	 */
	protected $root = C::ES;
	/**
	 * @var DOMDocument
	 */
	protected $definition = null;
	/**
	 * @var DOMXPath
	 */
	protected $xdef = null;

	protected const schemaPath = 'lib/services/installers/schemas/';

	/**
	 * SPInstaller constructor.
	 *
	 * @param string $definition
	 * @param string $type
	 */
	public function __construct( $definition, $type = C::ES )
	{
		$this->type = $type;
		$this->xmlFile = $definition;
		$this->definition = new DOMDocument( Sobi::Cfg( 'xml.version', '1.0' ), Sobi::Cfg( 'xml.encoding', 'UTF-8' ) );
		$this->definition->load( $this->xmlFile );
		$this->xdef = new DOMXPath( $this->definition );
		$this->root = dirname( $this->xmlFile );
	}

	/**
	 * @param string $key
	 *
	 * @return string|null
	 */
	protected function xGetString( $key )
	{
		$node = $this->xGetChilds( $key )->item( 0 );

		return isset( $node ) ? trim( $node->nodeValue ) : null;
	}

	/**
	 * @param string $key
	 *
	 * @return DOMNodeList|null
	 */
	protected function xGetChilds( $key )
	{
		return $this->xdef->query( "/$this->type/$key" );
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function validate()
	{
		$type = $this->type == 'SobiProApp' ? 'application' : $this->type;
		$schemaDef = SPLoader::path( self::schemaPath . $type, 'front', false, 'xsd' );
		$def = "https://xml.sigsiu.net/SobiPro/$type.xsd";

		// get new file only if existing one is at least 7 days old
		if ( !Sobi::Cfg( 'schemas.uselocal', false ) && ( !FileSystem::Exists( $schemaDef ) || ( time() - filemtime( $schemaDef ) > ( 60 * 60 * 24 * 7 ) ) ) ) {
			$connection = new CURL();
			$errno = $connection->error( false, true );
			$status = $connection->status( false, true );

			/* if CURL initialisation failed (CURL not installed) */
			if ( $status || $errno ) {
				throw new SPException( 'Code ' . $status ? $connection->status() : $connection->error() );
			}
			else {
				$connection->setOptions(
					[
						'url'            => $def,
						'connecttimeout' => 10,
						'header'         => false,
						'returntransfer' => true,
						'ssl_verifypeer' => false,
						'ssl_verifyhost' => 2,
					]
				);
				$schema = new File( SPLoader::path( self::schemaPath . $type, 'front', false, 'xsd' ) );
				$file = $connection->exec();
				if ( !strlen( $file ) ) {
					throw new SPException( SPLang::e( 'CANNOT_ACCESS_SCHEMA_DEF', $def ) );
				}
				$schema->content( $file );
				$schema->save();
				$schemaDef = $schema->getName();
			}
		}

		// Enable user error handling
		libxml_use_internal_errors( true );
		if ( !$this->definition->schemaValidate( $schemaDef ) ) {
			$message = $this->libxml_display_errors();
			throw new SPException( SPLang::e( 'CANNOT_VALIDATE_SCHEMA_DEF_AT', str_replace( SOBI_ROOT, C::ES, $this->xmlFile ), $def, $message ) );
		}
	}

	/**
	 * @param $error
	 *
	 * @return string
	 */
	function libxml_display_error( $error )
	{
		$return = "<br/>";
		switch ( $error->level ) {
			case LIBXML_ERR_WARNING:
				$return .= "<strong>Warning $error->code</strong>: ";
				break;
			case LIBXML_ERR_ERROR:
				$return .= "<strong>Error $error->code</strong>: ";
				break;
			case LIBXML_ERR_FATAL:
				$return .= "<strong>Fatal Error $error->code</strong>: ";
				break;
		}
		$return .= trim( $error->message );
		if ( $error->file ) {
			$return .= " In <strong>$error->file</strong>";
		}
		$return .= " on line <strong>$error->line</strong>.";

		return $return;
	}

	/**
	 * @return string
	 */
	function libxml_display_errors()
	{
		$return = '';
		$errors = libxml_get_errors();
		foreach ( $errors as $error ) {
			$return .= $this->libxml_display_error( $error ) . '"<br/>';
		}
		libxml_clear_errors();

		return $return;
	}
}

