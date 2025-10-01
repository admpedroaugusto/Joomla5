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
 * @created 10-Jan-2009 by Radek Suski
 * @modified 13 August 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

use Sobi\C;
use Sobi\Input\Input;
use Sobi\Lib\Factory;
use SobiPro\Models\Entry;

SPLoader::loadModel( 'entry' );

/**
 * Class SPEntryAdm
 */
class SPEntryAdm extends Entry implements SPDataModel
{
	/**
	 * @var bool
	 */
	private $_loaded = false;

	/**
	 * @param int $sid
	 * @param false $enabled
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function loadFields( $sid = 0, $enabled = false )
	{
		$sid = $sid ? : $this->section;

		static $lang = C::ES;
		$lang = $lang ? : Sobi::Lang( !Sobi::Cfg( 'lang.multimode', false ) );

		static $fields = [];
		//$nameFieldFid = $this->nameFieldFid();
		$nameFieldFid = SPFactory::config()->nameFieldFid( (int) $sid );

		$db = Factory::Db();

		if ( !isset( $fields[ $sid ] ) ) {
			/* get fields */
			try {
				$field = $db
					->select( '*', 'spdb_field',
						[ 'section' => $sid,
						  $db->argsOr( [ 'admList' => 1, 'fid' => $nameFieldFid ] ) ],
						'position'
					)
					->loadObjectList();

				if ( count( $field ) ) {
					$fields[ $sid ] = $field;
				}
				Sobi::Trigger( $this->name(), ucfirst( __FUNCTION__ ), [ &$fields ] );
			}
			catch ( SPException $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'CANNOT_GET_FIELDS_DB_ERR', $x->getMessage() ), C::ERROR, 500, __LINE__, __FILE__ );
			}
		}

		if ( !$this->_loaded ) {
			if ( $sid && count( $fields ) && array_key_exists( $sid, $fields ) && count( $fields[ $sid ] ) ) {
				/* if it is an entry - prefetch the basic fields data */
				if ( $this->id ) {
					$noCopy = $this->checkCopy();
					/* in case the entry is approved, or we are editing an entry, or the user can see unapproved changes */
					if ( $this->approved || $noCopy ) {
						$ordering = 'copy.desc';
					}
					/* otherwise - if the entry is not approved, get the non-copies first */
					else {
						$ordering = 'copy.asc';
					}
					try {
						$fdata = $db
							->select( '*', 'spdb_field_data', [ 'sid' => $this->id ], $ordering )
							->loadObjectList();
						$fieldsdata = [];
						if ( count( $fdata ) ) {
							foreach ( $fdata as $data ) {
								/* if it has been already set - check if it is not better language choose */
								if ( isset( $fieldsdata[ $data->fid ] ) ) {
									/*
									 * I know - the whole thing could be shorter
									 * but it is better to understand and debug this way
									 */
									if ( $data->lang == $lang ) {
										if ( $noCopy ) {
											if ( !$data->copy ) {
												$fieldsdata[ $data->fid ] = $data;
											}
										}
										else {
											$fieldsdata[ $data->fid ] = $data;
										}
									}
									/* set for cache other lang */
									else {
										$fieldsdata[ 'langs' ][ $data->lang ][ $data->fid ] = $data;
									}
								}
								else {
									if ( $noCopy ) {
										if ( !$data->copy ) {
											$fieldsdata[ $data->fid ] = $data;
										}
									}
									else {
										$fieldsdata[ $data->fid ] = $data;
									}
								}
							}
						}
						unset( $fdata );
						SPFactory::registry()->set( 'fields_data_' . $this->id, $fieldsdata );
					}
					catch ( Sobi\Error\Exception $x ) {
						Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
					}
				}
				foreach ( $fields[ $sid ] as $fieldClass ) {
					/* @var SPField $field */
					$field = SPFactory::Model( 'field', defined( 'SOBIPRO_ADM' ) );
					$field->extend( $fieldClass );
					$field->loadData( $this->id );
					$this->fields[] = $field;
					$this->fieldsNids[ $field->get( 'nid' ) ] = $this->fields[ count( $this->fields ) - 1 ];
					$this->fieldsIds[ $field->get( 'fid' ) ] = $this->fields[ count( $this->fields ) - 1 ];
					/* case it was the name field */
					if ( $field->get( 'fid' ) == $nameFieldFid ) {
						/* get the content of the name field (entry name) */
						$this->name = $field->getRaw();
						/* get the nid of the name field (name field alias) */
						$this->nameField = $field->get( 'nid' );
					}
				}
				$this->_loaded = true;
			}
		}

		/* if we do not have content for the name field */
		if ( !$this->name ) {
			$this->name = Sobi::Txt( 'ENTRY_NO_NAME' );
			// well, yeah - screw the pattern :-/
			SPFactory::message()
				->warning( 'ENTRIES_BASE_DATA_INCOMPLETE' )
				->setSystemMessage();
			$this->valid = false;
		}
		else {
			$this->valid = true;
		}
	}

	/**
	 * @return int
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
//	private function nameFieldFid(): int
//	{
//		/* get the field id of the field that contains the entry name */
//		$fid = SPFactory::config()->nameFieldFid( $this->section );
//
////		if ( $this->section == Sobi::Section() || !( $this->section ) ) {
////			$fid = (int) Sobi::Cfg( 'entry.name_field', 0 );
////		}
////		else {
////			$fid = (int) Factory::Db()
////				->select( 'sValue', 'spdb_config', [ 'section' => $this->section, 'sKey' => 'name_field', 'cSection' => 'entry' ] )
////				->loadResult();
////		}
//
//		return $fid ? : (int) Sobi::Cfg( 'entry.name_field', 0 );
//	}

	/**
	 * @return bool
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function checkCopy(): bool
	{
		return !(
			in_array( Input::Task(), [ 'entry.approve', 'entry.edit', 'entry.save', 'entry.submit' ] )
			|| Sobi::Can( 'entry.access.unapproved_any' )
			|| ( $this->owner == Sobi::My( 'id' ) && Sobi::Can( 'entry.manage.own' ) )
			|| Sobi::Can( 'entry.manage.*' )
		);
	}
}
