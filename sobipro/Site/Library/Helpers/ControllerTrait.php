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
 * @created 04 December 2020 by Sigrid Suski
 * @modified 10 November 2023 by Sigrid Suski
 */

namespace SobiPro\Helpers;

use JFactory;
use Sobi;
use Sobi\C;
use Sobi\FileSystem\FileSystem;
use Sobi\Lib\Factory;
use Sobi\Utils\StringUtils;

/**
 * Trait ControllerTrait
 * @package SobiPro\Helpers
 */
trait ControllerTrait
{
	/**
	 * @return array
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function customCols(): array
	{
		/* get fields for header */
		$fields = [];
		try {
			$fieldsData = Factory::Db()
				->select( '*', 'spdb_field', [ 'admList' => 1, 'section' => Sobi::Reg( 'current_section' ) ], 'admList' )
				->loadObjectList();
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( $this->name(), \SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}
		if ( is_array( $fieldsData ) && count( $fieldsData ) ) {
			$fModel = \SPLoader::loadModel( 'field', true );
			foreach ( $fieldsData as $field ) {
				$fit = new $fModel();
				/* @var \SPField $fit */
				$fit->extend( $field );
				$fields[] = $fit;
			}
		}

		return $fields;
	}

	/**
	 * Evaluates the sorting ordering.
	 *
	 * @param $subject -> entries or categories
	 * @param $request -> eorder or corder
	 * @param $default
	 * @param $limit
	 * @param $limitStart
	 * @param $sids
	 *
	 * @return string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 * @throws \Exception
	 */
	public function parseObject( $subject, $request, $default, &$limit, &$limitStart, &$sids )
	{
//		$session = JFactory::getSession();
//		$registry = $session->get( 'registry' );
		$section = StringUtils::Nid( Sobi::Section( true ) );

		$ordering = Sobi::GetUserState( "$subject.order", $request,
			Sobi::Cfg( "admin.$subject-order.$section", $default ) );

		/* legacy - why the hell I called it order?! use only position now */
		$ordering = str_replace( 'order', 'position', $ordering );
		$ordering = str_replace( [ 'e_s', 'c_s' ], C::ES, $ordering );

		$db = Factory::Db();
		$direction = 'asc';

		if ( strstr( $ordering, '.' ) ) {
			$ordering = explode( '.', $ordering );
			$direction = count( $ordering ) == 3 ? $ordering[ 1 ] . '.' . $ordering[ 2 ] : $ordering[ 1 ];
			$ordering = $ordering[ 0 ];
		}
		/* ordering by position */
		if ( $ordering == 'position' ) {
			$subject = $subject == 'categories' ? 'category' : 'entry';
			$entries = $db
				->select( 'id', 'spdb_relations', [
					'oType' => $subject,
					'pid'   => $this->_model->get( 'id' ), ],
					"position.$direction", $limit, $limitStart )
				->loadResultArray();

			if ( count( $entries ) ) {
				$sids = $entries;
				$entries = implode( ',', $entries );
				$ordering = "field( id, $entries )";
				$limitStart = $limit = 0;
			}
			else {
				$ordering = "id.$direction";
			}
		}

		/* ordering by name */
		elseif ( $ordering == 'name' ) {
			$subject = $subject == 'categories' ? 'category' : 'entry';
			$entries = $db
				->select( 'id', 'spdb_language', [
					'oType'    => $subject,
					'sKey'     => 'name',
					'language' => Sobi::Lang() ], "sValue.$direction" )
				->loadResultArray();

			if ( !count( $entries ) && Sobi::Lang() != Sobi::DefLang() ) {
				$entries = $db
					->select( 'id', 'spdb_language', [
						'oType'    => $subject,
						'sKey'     => 'name',
						'language' => Sobi::DefLang() ], "sValue.$direction" )
					->loadResultArray();
			}
			if ( is_array( $entries ) && count( $entries ) ) {
				$entries = implode( ',', $entries );
				$ordering = "field( id, $entries )";
			}
			else {
				$ordering = "id.$direction";
			}
		}

		/* ordering by state */
		elseif ( $ordering == 'state' ) {
			$ordering = "$ordering.$direction, validSince.$direction, validUntil.$direction";
		}

		/* ordering by field */
		elseif ( strstr( $ordering, 'field_' ) ) {
			static $field = null;
			if ( !$field ) {
				$fieldType = null;
				try {
					$fieldType = $db
						->select( 'fieldType', 'spdb_field', [
							'nid'     => $ordering,
							'section' => Sobi::Section() ] )
						->loadResult();
				}
				catch ( \SPException $x ) {
					Sobi::Error( $this->name(), \SPLang::e( 'CANNOT_DETERMINE_FIELD_TYPE', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
				}
				if ( $fieldType ) {
					$field = \SPLoader::loadClass( "opt.fields.$fieldType" );
				}
			}

			$table = $oPrefix = C::ES;
			$conditions = [];
			if ( $field && method_exists( $field, 'sortBy' ) ) {
				$conditions[ 'ids' ] = $sids;
				$entries = call_user_func_array( [ $field, 'sortBy' ], [ &$table, &$conditions, &$oPrefix, &$ordering, &$direction ] );
			}
			else {
				$table = $db->join(
					[
						[ 'table' => 'spdb_field', 'as' => 'fdef', 'key' => 'fid' ],
						[ 'table' => 'spdb_field_data', 'as' => 'fdata', 'key' => 'fid' ],
					]
				);
				$conditions[ 'fdata.sid' ] = $sids;
				$conditions[ 'fdef.nid' ] = $ordering;
				$conditions[ 'lang' ] = Sobi::Lang();

				$order = ($conditions['fdef.nid'] === 'field_processo_licitatorio_n') ? "CAST(SUBSTRING_INDEX(baseData, '/', -1) AS UNSIGNED) $direction, CAST(SUBSTRING_INDEX(baseData, '/', 1) AS UNSIGNED) $direction" : "baseData.$direction";
				$entries = $db
					->select( 'sid', $table, $conditions, $order )
					->loadResultArray();
			}

			/* if the resulting number of entries is lower than the overall number of entries (e.g. calendar field) */
			if ( is_array( $entries ) && count( $entries ) < count( $sids ) ) {
				$noValueEntries = array_diff( $sids, $entries );
				/* append the missing entries to the sorted entries */
				$entries = array_merge( $entries, $noValueEntries );
			}

			if ( is_array( $entries ) && count( $entries ) ) {
				$entries = implode( ',', $entries );
				$ordering = "field( id, $entries )";
			}
			else {
				$ordering = "id.$direction";
			}
		}
		else {
			$ordering = "$ordering.$direction";
		}

		return $ordering;
	}
}
