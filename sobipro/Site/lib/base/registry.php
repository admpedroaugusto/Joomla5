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
 * @created 10-Jan-2009 by Radek Suski
 * @modified 30 November 2022 by Sigrid Suski
 */
defined( 'SOBIPRO' ) || exit( 'Restricted access' );

use Sobi\Lib\Factory;
use Sobi\Lib\Registry;

/**
 * Class SPRegistry
 */
final class SPRegistry extends Registry
{
	/**
	 * @param $section
	 *
	 * @return $this
	 * @throws \SPException
	 */
	public function & loadDBSection( $section ): SPRegistry
	{
		static $loaded = [];
		if ( !in_array( $section, $loaded ) ) {
			try {
				$keys = Factory::Db()
					->select( '*', 'spdb_registry', [ 'section' => $section ], 'value' )
					->loadObjectList();
			}
			catch ( Sobi\Error\Exception $x ) {
				Sobi::Error( __FUNCTION__, SPLang::e( 'Cannot load registry section. Db reports %s.', $x->getMessage() ), SPC::WARNING, 0, __LINE__, __FILE__ );
			}
			if ( count( $keys ) ) {
				foreach ( $keys as $section ) {
					$this->store[ 0 ][ $section->section ][ $section->key ] = [ 'value' => $section->value, 'params' => $section->params, 'description' => $section->description, 'options' => $section->options ];
				}
			}
			$loaded[] = $section;
		}

		return $this;
	}


	/**
	 * Saves whole section in the db registry.
	 *
	 * @param $values
	 * @param $section
	 *
	 * @throws \Sobi\Error\Exception|\SPException
	 */
	public function saveDBSection( $values, $section )
	{
		foreach ( $values as $i => $value ) {
			$value[ 'section' ] = $section;
			$value[ 'params' ] = $value[ 'params' ] ?? null;
			$value[ 'description' ] = $value[ 'description' ] ?? null;
			$value[ 'options' ] = $value[ 'options' ] ?? null;
			$values[ $i ] = $value;
		}
		Sobi::Trigger( 'Registry', 'SaveDb', [ &$values ] );
		try {
			Factory::Db()->delete( 'spdb_registry', [ 'section' => $section ] );
			Factory::Db()->insertArray( 'spdb_registry', $values );
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( __FUNCTION__, SPLang::e( 'Cannot save registry section. Db reports %s.', $x->getMessage() ), SPC::WARNING, 0, __LINE__, __FILE__ );
		}
	}

	/**
	 * Singleton.
	 *
	 * @return SPRegistry
	 */
	public static function & getInstance(): SPRegistry
	{
		static $registry = null;
		if ( !$registry || !( $registry instanceof SPRegistry ) ) {
			$registry = new SPRegistry();
		}

		return $registry;
	}
}
