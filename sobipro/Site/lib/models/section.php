<?php
/**
 * @package: SobiPro Library
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
 * @created 10-Jan-2009 by Radek Suski
 * @modified 25 October 2023 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadModel( 'datamodel' );
SPLoader::loadModel( 'dbobject' );

use Sobi\C;
use Sobi\Input\Input;
use Sobi\Lib\Factory;
use Sobi\Utils\StringUtils;

/**
 * Class SPSection
 */
final class SPSection extends SPDBObject implements SPDataModel
{
	/** @var bool */
	protected $approved = true;
	/** @var bool */
	protected $confirmed = true;
	/** @var int */
	protected $state = 1;
	/** @var string */
	protected $oType = 'section';
	/** @var string */
	protected $description = C::ES;
	/** @var string */
	protected $sfMetaDesc = C::ES;
	/** @var string */
	protected $sfMetaKeys = C::ES;
	/** @var string */
	protected $efMetaDesc = C::ES;
	/** @var string */
	protected $efMetaKeys = C::ES;
	/** @var string */
	protected $efTitle = C::ES;
	/** @var string */
	protected $sfTitle = C::ES;
	/** @var string */
	protected $efDesc = C::ES;
	/** @var string */
	protected $sfDesc = C::ES;

	/** @var string */
	protected $redirectSectionUrl = 'index.php';
	/** @var string */
	protected $redirectCategoryUrl = 'index.php';
	/** @var string */
	protected $redirectEntryUrl = 'index.php';
	/** @var string */
	protected $redirectEntryAddUrl = 'index.php';
	/** @var string */
	protected $redirectEntrySaveUrl = 'index.php';
	/** @var string */
	protected $redirectSearchUrl = 'index.php';

	/** @var array */
	private static $types = [
		'description'          => 'Html',
		'sfMetaKeys'           => 'String',
		'sfMetaDesc'           => 'String',
		'efMetaKeys'           => 'String',
		'efMetaDesc'           => 'String',
		'efDesc'               => 'String',
		'sfDesc'               => 'String',
		'efTitle'              => 'String',
		'sfTitle'              => 'String',
		'redirectSectionUrl'   => 'String',
		'redirectCategoryUrl'  => 'String',
		'redirectEntryUrl'     => 'String',
		'redirectEntryAddUrl'  => 'String',
		'redirectEntrySaveUrl' => 'String',
		'redirectSearchUrl'    => 'String',
	];
	/** @var array */
	private static $translatable = [ 'description',
	                                 'name',
	                                 'metaKeys',
	                                 'metaDesc',
	                                 'sfMetaKeys',
	                                 'sfMetaDesc',
	                                 'efMetaKeys',
	                                 'efMetaDesc',
	                                 'efDesc',
	                                 'sfDesc',
	                                 'efTitle',
	                                 'sfTitle',
	                                 'redirectSectionUrl',
	                                 'redirectCategoryUrl',
	                                 'redirectEntryUrl',
	                                 'redirectEntryAddUrl',
	                                 'redirectEntrySaveUrl',
	                                 'redirectSearchUrl',
	];

	/**
	 * Deletes a section, its relations, configuration, plugins, permissions, fields.
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function delete()
	{
		Sobi::Trigger( 'Section', 'Delete', [ &$this->id ] );

		$childs = $this->getChilds( 'all', true );
		if ( is_array( $childs ) && count( $childs ) ) {
			SPFactory::message()->setMessage( Sobi::Txt( 'SEC.DEL_WARN' ), false, C::ERROR_MSG );
			Sobi::Redirect( Sobi::GetUserState( 'back_url', Sobi::Url() ), C::ES, C::ES, true );
		}
		else {
			Sobi::Trigger( 'delete', $this->name(), [ &$this ] );
			$db = Factory::Db();
			try {
				$db->delete( 'spdb_relations', "id = $this->id OR pid = $this->id" );
			}
			catch ( Sobi\Error\Exception $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
			}
			try {
				$db
					->delete( 'spdb_config', [ 'section' => $this->id ] )
					->delete( 'spdb_plugin_section', [ 'section' => $this->id ] )
					->delete( 'spdb_permissions_map', [ 'sid' => $this->id ] );

			}
			catch ( Sobi\Error\Exception $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
			}
			try {
				/* delete all fields of this section */
				$fids = $db->select( 'fid', 'spdb_field', [ 'section' => $this->id, 'adminField>' => -1 ] )->loadResultArray();
				if ( is_array( $fids ) && count( $fids ) ) {
					foreach ( $fids as $fid ) {
						try {
							$db->select( '*', $db->join( [ [ 'table' => 'spdb_field', 'as' => 'sField', 'key' => 'fieldType' ], [ 'table' => 'spdb_field_types', 'as' => 'sType', 'key' => 'tid' ] ] ), [ 'fid' => $fid ] );
							$f = $db->loadObject();
						}
						catch ( Sobi\Error\Exception $x ) {
							Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
						}
						/** @var SPField $field */
						$field =& SPFactory::Model( 'field', true );
						$field->extend( $f );
						$field->delete();
					}
				}
			}
			catch ( SPException $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
			}
			parent::delete();
			Sobi::Trigger( 'afterDelete', $this->name(), [ &$this ] );
		}
	}

	/**
	 * @param bool $update
	 * @param bool $init
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function save( $update = false, $init = true )
	{
		$db = Factory::Db();
		/* check nid */
		if ( !strlen( $this->nid ) ) {
			$this->nid = strtolower( StringUtils::Nid( $this->name ) );
//			if ( !$update ) {
			$c = 1;
			while ( $c ) {
				/* section name alias has to be unique */
				try {
					$db->select( 'COUNT(nid)', 'spdb_object', [ 'oType' => 'section', 'nid' => $this->nid ] );
					$c = $db->loadResult();
					if ( $c > 0 ) {
						$this->nid = $this->nid . '_' . rand( 0, 1000 );
					}
				}
				catch ( Sobi\Error\Exception $x ) {
					Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::ERROR, 500, __LINE__, __FILE__ );
				}
			}
//			}
		}
		else {
			$this->nid = strtolower( StringUtils::Nid( $this->nid ) );
		}

		SPFactory::registry()->set( 'current_section', $this->id );
		//$db->transaction();
		parent::save();

		/* case adding new section, define the default title field */
		if ( !$update && $init ) {
			/** @var SPField $field */
			$field = SPFactory::Model( 'field', true );
			$fid = $field->saveNew(
				[
					'enabled'        => 1,
					'name'           => 'Name',
					'nid'            => 'field_name',
					'fieldType'      => 'inbox',
					'suffix'         => C::ES,
					'cssClass'       => 'spClassInbox',
					'bsWidth'        => 5,
					'maxLength'      => 150,
					'helpposition'   => 'below',
					'showEditLabel'  => 1,
					'cssClassEdit'   => 'spClassEditInbox',
					'required'       => 1,
					'editable'       => 1,
					'editLimit'      => -1,
					'isFree'         => 1,
					'showIn'         => 'both',
					'withLabel'      => 1,
					'cssClassView'   => 'spClassViewInbox',
					'inSearch'       => 1,
					'bsSearchWidth'  => 5,
					'searchMethod'   => 'general',
					'cssClassSearch' => 'spClassSearchInbox',
					'section'        => $this->id,
				]
			);
			/** @var SPField $field */
			$field = SPFactory::Model( 'field', true );
			$field->saveNew(
				[
					'enabled'           => 1,
					'name'              => 'Category',
					'nid'               => 'field_category',
					'cssClass'          => 'spClassCategory',
					'fieldType'         => 'category',
					'showIn'            => 'hidden',
					'helpposition'      => 'below',
					'showEditLabel'     => 1,
					'cssClassEdit'      => 'spClassEditCategory',
					'method'            => 'select',
					'bsWidth'           => 5,
					'orderCatsBy'       => 'name.asc',
					'isPrimary'         => true,
					'required'          => 1,
					'editable'          => 1,
					'editLimit'         => -1,
					'isFree'            => 1,
					'inSearch'          => 1,
					'bsSearchWidth'     => 5,
					'withLabel'         => 1,
					'searchMethod'      => 'select',
					'searchOrderCatsBy' => 'name.asc',
					'cssClassSearch'    => 'spClassSearchCategory',
					'section'           => $this->id,
				]
			);
			SPFactory::config()
				->saveCfg( 'entry.name_field', $fid )
				->saveCfg( 'list.entries_ordering', 'field_name' )
				->saveCfg( 'template.icon_fonts_arr', [ 'font-awesome-5' ] );

			$permissions = [
				'section.access.valid',
				//				'section.search',
				'category.access.valid',
				'entry.access.valid',
				'entry.add.own',
				'section.search.*',
				'section.*.*.adm',
				'category.*.*.adm',
				'entry.*.*.adm',
			];
			$myGroups = Sobi::My( 'groups' );
			$gids = SPUser::availableGroups();
			$userGroups = [ 'visitor', 'Registered' ];
			foreach ( $myGroups as $gid ) {
				if ( $gids[ $gid ] != 'Super Users' ) {
					$userGroups[] = $gids[ $gid ];
				}
			}

			/** @var SPAclCtrl $aclController */
			$aclController = SPFactory::Controller( 'acl', true );
			$aclController->addNewRule( $this->get( 'name' ), [ $this->id ], $permissions, $userGroups, 'Default permissions for the section "' . $this->get( 'name' ) . '"' );
		}
		/* insert relation */
		try {
			$db->insertUpdate( 'spdb_relations', [ 'id' => $this->id, 'pid' => 0, 'oType' => 'section', 'position' => 1, 'validSince' => $this->validSince, 'validUntil' => $this->validUntil ] );
		}
		catch ( Sobi\Error\Exception $x ) {
			//$db->rollback();
			Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::ERROR, 500, __LINE__, __FILE__ );
		}
		/* if there were no errors, commit the database changes */
		//$db->commit();
//		if( !$update ) {
//			SPFactory::mainframe()->msg( Sobi::Txt( 'SEC.CREATED' ) );
//		}
		SPFactory::cache()->cleanSection();
		/* trigger plugins */
		Sobi::Trigger( 'afterSave', $this->name(), [ &$this ] );
	}

	/**
	 * @return array
	 */
	protected function types()
	{
		return self::$types;
	}

	/**
	 * @param int $id
	 *
	 * @return mixed|\SPSection|static
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function & getInstance( $id = 0 )
	{
		static $instances = [];
		$id = $id ? : Sobi::Reg( 'current_section' );
		if ( !isset( $instances[ $id ] ) || !( $instances[ $id ] instanceof self ) ) {
			$instances[ $id ] = new self();
			$instances[ $id ]->extend( SPFactory::object( $id ) );
		}

		return $instances[ $id ];
	}

	/**
	 * @return array
	 */
	protected function translatable()
	{
		return self::$translatable;
	}
}
