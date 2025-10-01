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
 * @modified 10 June 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadController( 'controller' );
SPLoader::loadController( 'field' );

use Sobi\C;
use Sobi\FileSystem\FileSystem;
use Sobi\Input\Input;
use Sobi\Lib\Factory;
use Sobi\Utils\StringUtils;
use SobiPro\Helpers\MenuTrait;

/**
 * Class SPFieldAdmCtrl
 */
final class SPFieldAdmCtrl extends SPFieldCtrl
{
	use MenuTrait {
		setMenuItems as protected;
	}

	/** @var string */
	protected $_type = 'field';
	/*** @var string */
	protected $_fieldType = 'field';
	/*** @var array */
	private $attr = [];
	/** @var bool */
	protected $_category = false;

	/**
	 * Editing/adding a field.
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function edit()
	{
		$fid = Input::Int( 'fid' );
		$this->checkTranslation();

		if ( !$fid ) {
			$this->add();      /* if adding new field - call #add */

			return;
		}

		if ( $this->isCheckedOut( $fid ) ) {
			SPFactory::message()->error( Sobi::Txt( 'FM.IS_CHECKED_OUT' ), false );
		}
		else {
			$this->checkOut( $fid );    /* check it out */
		}

		/* load base data */
		$fieldData = $this->loadField( $fid );

		/** @var SPField $field */
		$field = SPFactory::Model( 'field', true );
		$field->extend( $fieldData );

		$groups = $this->getFieldGroup( $fieldData->fieldType, $fieldData->tGroup );
		$type = $fieldData->fType . ' ( ' . $fieldData->tGroup . ' / ' . $fieldData->fieldType . ' )';
		$this->_fieldType = $fieldData->fieldType;

		$registry = SPFactory::registry();
		$helpTask = 'field.' . $field->get( 'fieldType' );
		$registry->set( 'help_task', $helpTask );

		/* get input filters */
		$filters = SPFactory::filter()->getFilters();
		$filterlist = [ 0 => Sobi::Txt( 'FM.NO_FILTER' ) ];
		if ( count( $filters ) ) {
			foreach ( $filters as $filter => $data ) {
				$filterlist[ $filter ] = Sobi::Txt( $data[ 'value' ] );
			}
		}

		/* get view class */
		/** @var SPFieldAdmView $view */
		$view = SPFactory::View( 'field', true );

		$view
			->addHidden( Input::Int( 'fid' ), 'fid' )
			->addHidden( Input::Sid(), 'sid' )
			->addHidden( $this->_category, 'category-field' );
		if ( $this->_category ) {
			$view
				->addHidden( -1, 'field.adminField' )
				->addHidden( $this->_fieldType, 'field.fieldType' );
		}

		$view
			->assign( $groups, 'types' )
			->assign( $type, 'type' )
			->assign( $filterlist, 'filters' )
			->assign( $field, 'field' )
			->assign( $this->_category, 'category-field' )
			->assign( $this->_task, 'task' );

		$languages = $view->languages();
		$multiLang = Sobi::Cfg( 'lang.multimode', false );
		$styleshint = $this->checkTemplate( $field->getStyleFile() );
		$view
			->assign( $languages, 'languages-list' )
			->assign( $multiLang, 'multilingual' )
			->assign( $styleshint, 'styleshint' );

		$field->onFieldEdit( $view );
		$this->loadTemplate( $field, $view );

		$view->display();
	}

	/**
	 * @param $field
	 * @param $view
	 *
	 * @return bool
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function loadTemplate( $field, $view )
	{
		$nid = '/' . Sobi::Section( 'nid' ) . '/';
		$disableOverrides = null;
		if ( is_array( Sobi::My( 'groups' ) ) ) {
			$disableOverrides = array_intersect( Sobi::My( 'groups' ), Sobi::Cfg( 'templates.disable-overrides', [] ) );
		}
		if ( SPLoader::translatePath( 'field.' . $field->get( 'fieldType' ), 'adm', true, 'xml' ) ) {
			/** Case we have also override  */
			/** section override */
			if ( !$disableOverrides && SPLoader::translatePath( 'field.' . $nid . $field->get( 'fieldType' ), 'adm', true, 'xml' ) ) {
				$view->loadDefinition( 'field.' . $nid . $field->get( 'fieldType' ) );
			}
			/** std override */
			else {
				if ( SPLoader::translatePath( 'field.' . $field->get( 'fieldType' ) . '_override', 'adm', true, 'xml' ) ) {
					$view->loadDefinition( 'field.' . $field->get( 'fieldType' ) . '_override' );
				}
				else {
					$view->loadDefinition( 'field.' . $field->get( 'fieldType' ) );
				}
			}
			if ( SPLoader::translatePath( 'field.templates.' . $field->get( 'fieldType' ) . '_override', 'adm' ) ) {
				$view->setTemplate( 'field.templates.' . $field->get( 'fieldType' ) . '_override' );
			}
			else {
				if ( SPLoader::translatePath( 'field.templates.' . $nid . $field->get( 'fieldType' ), 'adm' ) ) {
					$view->setTemplate( 'field.templates.' . $nid . $field->get( 'fieldType' ) );
				}
				else {
					$view->setTemplate( 'default' );
				}
			}

			return true;
		}

		return false;
	}

	/**
	 * @param string $fType
	 * @param string $group
	 *
	 * @return array
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function getFieldGroup( string $fType, string $group = C::ES ): array
	{
		if ( !$group ) {
			$group = Factory::Db()
				->select( 'tGroup', 'spdb_field_types', [ 'tid' => $fType ] )
				->loadResult();
		}
		$groups = [];
		if ( $group != 'special' ) {
			try {
				$fTypes = Factory::Db()
					->select( '*', 'spdb_field_types', [ 'tGroup' => $group ], 'fPos' )
					->loadObjectList();
			}
			catch ( Sobi\Error\Exception $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'CANNOT_GET_FIELD_TYPES_DB_ERR', $x->getMessage() ), C::WARNING, 500, __LINE__, __FILE__ );
			}

			if ( count( $fTypes ) ) {
				$pre = 'FIELD.TYPE_OPTG_';
				foreach ( $fTypes as $type ) {
					$groups[ str_replace( $pre, C::ES, Sobi::Txt( $pre . $type->tGroup ) ) ][ $type->tid ] = $type->fType;
				}
			}
		}
		else {
			$name = Factory::Db()
				->select( 'fType', 'spdb_field_types', [ 'tid' => $fType ] )
				->loadResult();
			$groups[ Sobi::Txt( 'FIELD.TYPE_OPTG_SPECIAL' ) ][ $fType ] = $name;
		}

		return $groups;
	}


	/**
	 * @param $fType
	 *
	 * @return mixed|string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function getPlainFieldType( $fType )
	{
		$fTypes = [];
		$type = C::ES;
		$group = Factory::Db()
			->select( 'tGroup', 'spdb_field_types', [ 'tid' => $fType ] )
			->loadResult();
		/* get cognate field types */
		if ( $group != 'special' ) {
			try {
				$fTypes = Factory::Db()
					->select( '*', 'spdb_field_types', [ 'tGroup' => $group ], 'fPos' )
					->loadObjectList();
			}
			catch ( Sobi\Error\Exception $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'CANNOT_GET_FIELD_TYPES_DB_ERR', $x->getMessage() ), C::WARNING, 500, __LINE__, __FILE__ );
			}

			if ( count( $fTypes ) ) {
				foreach ( $fTypes as $type ) {
					if ( $type->tid == $fType ) {
						$type = $type->fType . ' ( ' . $type->tGroup . ' / ' . $fType . ' )';
						break;
					}
				}
			}
		}
		else {
			$name = Factory::Db()
				->select( 'fType', 'spdb_field_types', [ 'tid' => $fType ] )
				->loadResult();
			$type = $name . ' ( ' . $group . ' / ' . $fType . ' )';
		}

		return $type;
	}

	/**
	 * @param $fid
	 *
	 * @return \stdClass
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	private function loadField( $fid )
	{
		$db = Factory::Db();
		try {
			$field = $db
				->select( '*',
					$db->join( [ [ 'table' => 'spdb_field', 'as' => 'sField', 'key' => 'fieldType' ],
					             [ 'table' => 'spdb_field_types', 'as' => 'sType', 'key' => 'tid' ] ] ),
					[ 'fid' => $fid ]
				)
				->loadObject();
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 500, __LINE__, __FILE__ );
		}
		if ( $field && $field->adminField == -1 ) {
			$this->_category = true;
		}

		return $field;
	}

	/**
	 * Just when adding new field - first step.
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function add()
	{
		if ( $this->_fieldType ) {
			$groups = $this->getFieldGroup( $this->_fieldType );
			$type = $this->getPlainFieldType( $this->_fieldType );

			/** @var SPField $field */
			$field = SPFactory::Model( 'field', true );
			$field->loadType( $this->_fieldType );
		}
		else {
			$groups = $this->getFieldTypes();
			/* create dummy field with initial values */
			$field = [
				'name'        => C::ES,
				'nid'         => C::ES,
				'notice'      => C::ES,
				'description' => C::ES,
				'adminField'  => 0,
				'enabled'     => 1,
				'fee'         => 0,
				'isFree'      => 1,
				'withLabel'   => 1,
				'version'     => 1,
				'editable'    => 1,
				'required'    => 0,
				'priority'    => 5,
				'showIn'      => 'details',
				'editLimit'   => -1,
				'inSearch'    => 0,
				'cssClass'    => C::ES,
				'fieldType'   => $this->_fieldType,
			];
		}

		$trim = false;
		if ( defined( 'SOBI_TRIMMED' ) ) {
			try {
				$count = Factory::Db()
					->select( 'COUNT(fid)', 'spdb_field' )
					->loadResult();
				$trim = $count > SPC::FC;
			}
			catch ( Sobi\Error\Exception $x ) {
			}
		}

		$registry = SPFactory::registry();
		$helpTask = 'field.' . $field->get( 'fieldType' );
		$registry->set( 'help_task', $helpTask );

		$filters = SPFactory::filter()->getFilters();
		$filterlist = [ 0 => Sobi::Txt( 'FM.NO_FILTER' ) ];
		if ( count( $filters ) ) {
			foreach ( $filters as $filter => $data ) {
				$filterlist[ $filter ] = Sobi::Txt( $data[ 'value' ] );
			}
		}

		/* get view class */
		/** @var SPFieldAdmView $view */

		$view = SPFactory::View( 'field', true );
		$view
			->addHidden( Input::Sid(), 'sid' )
			->addHidden( 0, 'fid' )
			->addHidden( $this->_category, 'category-field' );
		if ( $this->_category ) {
			$view
				->addHidden( -1, 'field.adminField' )
				->addHidden( $this->_fieldType, 'field.fieldType' );
		}

		$task = 'add';
		$view
			->assign( $groups, 'types' )
			->assign( $type, 'type' )
			->assign( $field, 'field' )
			->assign( $this->_category, 'category-field' )
			->assign( $task, 'task' );

		$multiLang = Sobi::Cfg( 'lang.multimode', false );
		$styleshint = $this->checkTemplate( $field->getStyleFile() );

		$view
			->assign( $multiLang, 'multilingual' )
			->assign( $styleshint, 'styleshint' )
			->assign( $trim, 'trim' )
			->assign( $filterlist, 'filters' );

		if ( $this->_fieldType ) {
			$field->onFieldEdit( $view );
		}

		if ( $this->loadTemplate( $field, $view ) ) {
			$view->display();
		}
		else {
			Sobi::Error( $this->name(), SPLang::e( 'NO_FIELD_DEF' ), C::WARNING, 500, __LINE__, __FILE__ );
		}
	}

	/**
	 * @param bool $category
	 *
	 * @return array
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function getFieldTypes( bool $category = false )
	{
		static $fTypes = [];
		if ( !$fTypes ) {
			/* get all existing field types */
			try {
				$fTypes = Factory::Db()
					->select( '*', 'spdb_field_types', C::ES, 'fPos' )
					->loadObjectList();
			}
			catch ( Sobi\Error\Exception $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 500, __LINE__, __FILE__ );
			}
			$groups = [];
		}
		if ( count( $fTypes ) ) {
			$pre = 'FIELD.TYPE_OPTG_';
			$groups = [
				Sobi::Txt( $pre . 'free_single_simple_data' )             => [],
				Sobi::Txt( $pre . 'predefined_multi_data_single_choice' ) => [],
				Sobi::Txt( $pre . 'predefined_multi_data_multi_choice' )  => [],
				Sobi::Txt( $pre . 'special' )                             => [],
			];
			foreach ( $fTypes as $type ) {
				if ( $category ) {
					try {
						$class = SPLoader::loadClass( 'opt.fields.' . $type->tid );
						if ( !property_exists( $class, 'CAT_FIELD' ) ) {
							continue;
						}
					}
					catch ( SPException $x ) {
						continue;
					}
				}
				$groups[ str_replace( $pre, C::ES, Sobi::Txt( $pre . $type->tGroup ) ) ][ $type->tid ] = $type->fType;
			}
			foreach ( $groups as &$group ) {
				asort( $group );
			}
		}

		return $groups;
	}

	/**
	 * @TODO should be moved to the model ????
	 * Adding new field
	 * Saves base data and redirects to the edit function when the field type has been chosen.
	 *
	 * @return mixed
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function saveNew()
	{
		/** @var SPField $field */
		$field = SPFactory::Model( 'field', true );
		$this->getRequest();

		return $field->saveNew( $this->attr );
	}

	/**
	 * Gets data from request.
	 */
	private function getRequest()
	{
		foreach ( $_REQUEST as $key => $v ) {
			if ( strstr( $key, 'field_' ) ) {
				$value = Input::Raw( $key );
				$this->attr[ str_replace( 'field_', C::ES, $key ) ] = $value;
			}
		}
	}

	/**
	 * @param int $id
	 *
	 * @return array
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function delete( $id = 0 )
	{
		$fields = [];
		$messages = [];
		if ( $id ) {
			$fields[] = $id;
		}
		else {
			if ( Input::Int( 'fid' ) ) {
				$fields[] = Input::Int( 'fid' );
			}
			else {
				$fields = Input::Arr( 'p_fid', 'request', [] );
			}
		}
		if ( count( $fields ) ) {
			foreach ( $fields as $id ) {
				/** @var SPField $field */
				$field = SPFactory::Model( 'field', true );
				$field->extend( $this->loadField( $id ) );
				$section = $field->get( 'section' ) ? : 0;
				SPFactory::history()->logAction( SPC::LOG_DELETE, $id, $section, 'field', C::ES, [ 'name' => $field->get( 'name' ), 'type' => $field->get( 'type' ) ] );
				$msg[ 'text' ] = $field->delete();
				$msg[ 'type' ] = C::SUCCESS_MSG;
				$messages[] = $msg;
			}
		}
		else {
			$msg = SPLang::e( 'FMN.STATE_CHANGE_NO_ID' );
			SPFactory::message()->setMessage( $msg, false, C::ERROR_MSG );
		}

		return $messages;
	}

	/**
	 * @param $fid
	 */
	public function checkOut( $fid )
	{
	}

	/**
	 * @param $fid
	 *
	 * @return bool
	 */
	public function isCheckedOut( $fid )
	{
		return false;
	}

	/**
	 * @param $field
	 *
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function validateRequest( $field )
	{
		$type = Input::Cmd( 'field_fieldType' );
		$definition = SPLoader::path( 'field.' . $type, 'adm', true, 'xml' );
		if ( $definition ) {
			$xdef = new DOMXPath( SPFactory::LoadXML( $definition ) );
			$required = $xdef->query( '//field[@required="true"]' );
			if ( $required->length ) {
				for ( $i = 0; $i < $required->length; $i++ ) {
					$node = $required->item( $i );
					$name = $node->attributes->getNamedItem( 'name' )->nodeValue;
					if ( !Input::Raw( str_replace( '.', '_', $name ) ) ) {
						$this->response( Sobi::Url( [ 'task' => 'field.edit', 'fid' => $field->get( 'fid' ), 'sid' => Input::Sid() ] ), Sobi::Txt( 'PLEASE_FILL_IN_ALL_REQUIRED_FIELDS' ), false, 'error', [ 'required' => $name ] );
					}
				}
			}
		}
	}

	/**
	 * @param int $section
	 * @param int $id
	 *
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function copyToSection( int $section = 0, int $id = 0 ): array
	{
		$fields = $messages = [];
		if ( $id ) {
			$fields[] = $id;
		}
		else {
			if ( Input::Int( 'fid', 'request', 0 ) ) {
				$fields[] = Input::Int( 'fid', 'request', 0 );
			}
			else {
				$fields = Input::Arr( 'p_fid', 'request', [] );
			}
		}
		if ( count( $fields ) ) {
			foreach ( $fields as $fid ) {
				/** @var SPField $field */
				$field = SPFactory::Model( 'field', true );
				$attr = $this->loadField( $fid );
				if ( $attr->adminField == -1 ) {
					Input::Set( 'category-field', 1 );
				}

				foreach ( $attr as $key => $value ) {
					$this->attr[ $key ] = $value;
				}
				$field->extend( $this->attr );  // make the model specific

				$params = $field->get( 'params' );  // field specific properties
				foreach ( $params as $key => $value ) {
					$this->attr[ $key ] = $value;
				}

				if ( $section != 0 ) {  // same section
					$field->set( 'section', $section );
				}
				$logAction = $section ? SPC::LOG_COPY : SPC::LOG_DUPLICATE;

				$nid = $field->get( 'nid' );
				if ( !$nid || !strstr( $nid, 'field_' ) ) {
					$nid = strtolower( str_replace( '-', '_',
						StringUtils::Nid( 'field_' . Input::String( 'field_name' ) ) ) );
					Input::Set( 'field_nid', $nid );
				}

				$msgtext = C::ES;
				try {
					$this->attr[ 'name' ] = $field->get( 'name' );
					$this->attr[ 'suffix' ] = $field->get( 'suffix' );
					$this->attr[ 'description' ] = $field->get( 'description' );
					$this->attr[ 'section' ] = $section;

					if ( is_array( $field->get( 'options' ) ) ) {
						$this->attr [ 'options' ] = $field->get( 'options' );
					}
					$fid = $field->saveNew( $this->attr );
					if ( $section == 0 ) {
						$msgtext = Sobi::Txt( 'FM.FIELD_COPIED', [ 'field' => $field->get( 'name' ), 'fid' => $fid ] );
					}
					else {
						$sectionName = SPLang::translateObject( $section, 'name', 'section' );
						$sectionName = StringUtils::Clean( $sectionName[ $section ][ 'value' ] );
						$msgtext = Sobi::Txt( 'FM.FIELD_COPIEDTO', [ 'field' => $field->get( 'name' ), 'fid' => $fid, 'section' => $sectionName ] );
					}
				}
				catch ( SPException $x ) {
					$this->response( Sobi::Url( [ 'task' => 'field.edit', 'fid' => $fid, 'sid' => Input::Sid() ] ), $x->getMessage(), false, C::ERROR_MSG );
				}

				SPFactory::history()->logAction( $logAction, $fid, $section ? : Input::Sid(), 'field', C::ES, [ 'name' => $field->get( 'name' ), 'type' => $field->get( 'type' ), 'from' => Input::Sid() ] );

				$msg[ 'text' ] = $msgtext;
				$msg[ 'type' ] = C::SUCCESS_MSG;
				//SPFactory::message()->setMessage( $msg, false, C::SUCCESS_MSG );
				$messages[] = $msg;
			}
		}
		else {
			$msg = SPLang::e( 'FMN.STATE_CHANGE_NO_ID' );
			SPFactory::message()->setMessage( $msg, false, C::ERROR_MSG );
		}

		return $messages;
	}

	/**
	 * Saves the existing field.
	 *
	 * @param bool $clone
	 * @param bool $apply
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function save( $clone = false, $apply = false )
	{
		if ( !Factory::Application()->checkToken() ) {
			Sobi::Error( 'Token', SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ), C::ERROR, 403, __LINE__, __FILE__ );
		}

		$sets = [];
		$fid = Input::Int( 'fid' );

		/** @var SPField $field */
		$field = SPFactory::Model( 'field', true );
		if ( $fid ) {
			$fieldData = $this->loadField( $fid );
			$field->extend( $fieldData );
		}
		else {
			$field->loadType( Input::Cmd( 'field_fieldType' ) );
		}
		$nid = Input::Cmd( 'field_nid' );
		if ( !$nid || !strstr( $nid, 'field_' ) ) {
			/** give me my spaces back!!! */
			$nid = strtolower( str_replace( '-', '_',
				StringUtils::Nid( 'field_' . Input::String( 'field_name' ) ) ) );
			Input::Set( 'field_nid', $nid );
		}
		$this->getRequest();
		$this->validateRequest( $field );

		if ( $clone || !$fid ) {
			$logAction = $clone ? SPC::LOG_CLONE : SPC::LOG_ADD;
			try {
				$fid = $field->saveNew( $this->attr );
				//$field->save( $this->attr );    // save the changes!!
				SPFactory::history()->logAction( $logAction, $fid, Input::Sid(), 'field', C::ES, [ 'name' => $field->get( 'name' ), 'type' => $field->get( 'type' ) ] );
			}
			catch ( SPException $x ) {
				$this->response( Sobi::Url( [ 'task' => 'field.edit', 'fid' => $fid, 'sid' => Input::Sid() ] ), $x->getMessage(), false, C::ERROR_MSG );
			}
		}
		else {
			$logAction = SPC::LOG_EDIT;
			try {
				$field->save( $this->attr );
				SPFactory::history()->logAction( $logAction, $fid, Input::Sid(), 'field', C::ES, [ 'name' => $field->get( 'name' ), 'type' => $field->get( 'type' ) ] );
			}
			catch ( SPException $x ) {
				$this->response( Sobi::Url( [ 'task' => 'field.edit', 'fid' => $fid, 'sid' => Input::Sid() ] ), $x->getMessage(), false, C::ERROR_MSG );
			}
		}

		$alias = $field->get( 'nid' );
		$fieldSets = $field->get( 'sets' );
		if ( is_array( $fieldSets ) && count( $fieldSets ) ) {
			$sets = array_merge( $fieldSets, $sets );
		}
		$sets[ 'fid' ] = $fid;
		$sets[ 'field.nid' ] = $alias;
		/* in case we are changing the sort by field */
		if ( Sobi::Cfg( 'list.entries_ordering' ) == $alias && $field->get( 'nid' ) != $alias ) {
			SPFactory::config()->saveCfg( 'list.entries_ordering', $field->get( 'nid' ) );
		}

		SPFactory::cache()->cleanSection();
		if ( $this->_task == 'apply' || $clone ) {
			if ( $clone ) {
				$msg = Sobi::Txt( 'FM.FIELD_CLONED' );
				$this->response( Sobi::Url( [ 'task' => 'field.edit', 'fid' => $fid, 'sid' => Input::Sid() ] ), $msg );
			}
			else {
				$msg = Sobi::Txt( 'MSG.ALL_CHANGES_SAVED' );
				$lang = Input::Cmd( 'sp-language', 'request' );
				$url = ( $lang == C::ES || $lang == Sobi::Cfg( 'language' ) ) ?
					Sobi::Url( [ 'task' => 'field.edit',
					             'fid'  => $fid,
					             'sid'  => Input::Sid() ] ) :
					Sobi::Url( [ 'task'        => 'field.edit',
					             'fid'         => $fid,
					             'sp-language' => $lang,
					             'sid'         => Input::Sid() ] );
				$this->response( $url, $msg, true, C::SUCCESS_MSG, [ 'sets' => $sets ] );
			}
		}
		else {
			$this->response( Sobi::Back(), Sobi::Txt( 'MSG.ALL_CHANGES_SAVED' ) );
		}
	}

	/**
	 * Lists all fields in this section.
	 *
	 * @throws \ReflectionException
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 * @throws \Exception
	 */
	protected function listFields()
	{
		$ord = $this->parseFieldsOrdering( 'forder', 'position.asc' );
		SPLoader::loadClass( 'html.input' );
		Sobi::ReturnPoint();

		/* create menu */
		$sid = Sobi::Section();
		$menu = $this->setMenuItems( 'field.list', true );

		$results = $fields = $categoryFields = [];
		try {
			$results = Factory::Db()
				->select( '*', 'spdb_field', [ 'section' => $sid ], $ord )
				->loadObjectList();
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}
		if ( count( $results ) ) {
			foreach ( $results as $result ) {
				/** @var SPField $field */
				$field = SPFactory::Model( 'field', true );
				$field->extend( $result );
				if ( $field->get( 'adminField' ) == -1 ) {
					$categoryFields[] = $field;
				}
				else {
					$fields[] = $field;
				}
			}
		}
		$fieldTypes = $this->getFieldTypes();

		$subMenu = [];
		foreach ( $fieldTypes as $type => $group ) {
			//asort( $group );
			$subMenu[] = [
				'label'   => $type,
				'element' => 'dropdown-header',
			];
			foreach ( $group as $task => $label ) {
				$subMenu[] = [
					'type'    => C::ES,
					'task'    => 'field.add.' . $task,
					'label'   => $label,
					'icon'    => C::ES,
					'element' => 'button',
				];
			}
		}
		$categoryFieldsTypes = $this->getFieldTypes( true );
		$cateSubMenu = [];
		foreach ( $categoryFieldsTypes as $type => $group ) {
			asort( $group );
			$cateSubMenu[] = [
				'label'   => $type,
				'element' => 'dropdown-header',
			];
			foreach ( $group as $task => $label ) {
				$cateSubMenu[] = [
					'type'    => C::ES,
					'task'    => 'field.add.' . $task . '.category',
					'label'   => $label,
					'icon'    => C::ES,
					'element' => 'button',
				];
			}
		}

		$sectionName = Sobi::Section( true );
		$fieldsOrder = Sobi::GetUserState( 'fields.order', 'forder', 'position.asc' );
		/** @var SPFieldAdmView $view */
		$view = SPFactory::View( 'field', true );
		$view
			->addHidden( $sid, 'sid' )
			->assign( $fields, 'fields' )
			->assign( $categoryFields, 'category-fields' )
			->assign( $cateSubMenu, 'categoryFieldTypes' )
			->assign( $subMenu, 'fieldTypes' )
			->assign( $sectionName, 'section' )
			->assign( $menu, 'menu' )
			->assign( $fieldsOrder, 'ordering' )
			->assign( $this->_task, 'task' )
			->determineTemplate( 'field', 'list' )
			->display();
	}

	/**
	 * @param string $col
	 * @param string $def
	 *
	 * @return string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function parseFieldsOrdering( string $col, string $def ): string
	{
		$order = Sobi::GetUserState( 'fields.order', $col, Sobi::Cfg( 'admin.fields-order', $def ) );
		$ord = $order;
		$dir = 'asc';
		/** legacy - why the hell I called it order?! */
		$ord = str_replace( 'order', 'position', $ord );

		if ( strstr( $ord, '.' ) ) {
			$ord = explode( '.', $ord );
			if ( count( $ord ) == 3 ) {
				$dir = $ord[ 1 ] . '.' . $ord[ 2 ];
			}
			else {
				$dir = $ord[ 1 ];
			}
			$ord = $ord[ 0 ];
		}
		$ord = $ord == 'state' ? 'enabled' : $ord;
//		$ord = ( $ord == 'position' ) ? 'position' : $ord;
		if ( $ord == 'name' ) {
			$db = Factory::Db();
			$fields = $db
				->select( 'fid', 'spdb_language', [ 'oType' => 'field', 'sKey' => 'name', 'language' => Sobi::Lang() ], 'sValue.' . $dir )
				->loadResultArray();
			if ( !count( $fields ) && Sobi::Lang() != Sobi::DefLang() ) {
				$fields = $db
					->select( 'id', 'spdb_language', [ 'oType' => 'field', 'sKey' => 'name', 'language' => Sobi::DefLang() ], 'sValue.' . $dir )
					->loadResultArray();
			}
			if ( count( $fields ) ) {
				$fields = implode( ',', $fields );
				$ord = "field( fid, $fields )";
			}
			else {
				$ord = 'fid.' . $dir;
			}
		}
		else {
			$ord = $ord . '.' . $dir;
		}
		Sobi::SetUserState( 'fields.order', $order );

		return $ord;
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function reorder()
	{
		$this->_reorder( Input::Arr( 'fid', 'request', [] ) );
		$this->_reorder( Input::Arr( 'cfid', 'request', [] ) );
		SPFactory::cache()->cleanSection();
		$this->response( Sobi::Url( [ 'task' => 'field.list', 'pid' => Sobi::Section() ] ), Sobi::Txt( 'NEW_FIELDS_ORDERING_HAS_BEEN_SAVED' ), true, C::SUCCESS_MSG );
	}

	/**
	 * @param $up
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function singleReorder( $up )
	{
		$up = ( bool ) $up;
		$db = Factory::Db();
		$fid = Input::Int( 'fid' );
		$category = false;
		if ( !$fid ) {
			$fid = Input::Int( 'cfid' );
			$category = true;
		}
		$fClass = SPLoader::loadModel( 'field', true );
		$fdata = $this->loadField( $fid );
		$field = new $fClass();
		$field->extend( $fdata );
		$eq = $up ? '<' : '>';
		$dir = $up ? 'position.desc' : 'position.asc';
		$current = $field->get( 'position' );
		try {
			$condition = [ 'position' . $eq => $current, 'section' => Input::Int( 'sid' ) ];
			if ( !$category ) {
				$condition[ 'adminField>' ] = -1;
			}
			else {
				$condition[ 'adminField' ] = -1;
			}
			$interchange = $db
				->select( 'position, fid', 'spdb_field', $condition, $dir, 1 )
				->loadAssocList();
			if ( $interchange && count( $interchange ) ) {
				$db->update( 'spdb_field', [ 'position' => $interchange[ 0 ][ 'position' ] ], [ 'section' => Input::Int( 'sid' ), 'fid' => $field->get( 'fid' ) ], 1 );
				$db->update( 'spdb_field', [ 'position' => $current ], [ 'section' => Input::Int( 'sid' ), 'fid' => $interchange[ 0 ][ 'fid' ] ], 1 );
			}
			else {
				$current = $up ? $current-- : $current++;
				$db->update( 'spdb_field', [ 'position' => $current ], [ 'section' => Input::Int( 'sid' ), 'fid' => $field->get( 'fid' ) ], 1 );
			}
		}
		catch ( SPException $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 500, __LINE__, __FILE__ );
		}
		SPFactory::cache()->cleanSection();
		$this->response( Sobi::Url( [ 'task' => 'field.list', 'pid' => Sobi::Section() ] ), Sobi::Txt( 'NEW_FIELDS_ORDERING_HAS_BEEN_SAVED' ), true, C::SUCCESS_MSG );
	}

	/**
	 * @param $task
	 *
	 * @return array|string|null
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function changeState( $task )
	{
		$actions = [ 'enabled'  => 'PUBLISH',
		             'editable' => 'EDITABLE',
		             'required' => 'REQUIRED',
		             'isFree'   => 'FREE',
		];

		$fIds = Input::Arr( 'p_fid' );
		if ( !$fIds ) {
			if ( Input::Int( 'fid' ) ) {
				$fIds = [ Input::Int( 'fid' ) ];
			}
			else {
				$fIds = [];
			}
		}
		if ( !count( $fIds ) ) {
			return [ 'text' => Sobi::Txt( 'FMN.STATE_CHANGE_NO_ID' ), 'type' => C::ERROR_MSG ];
		}

		$msg = C::ES;
		$state = '0';
		$col = 'enabled';
		switch ( $task ) {
			case 'hide':
			case 'publish':
				$state = $task == 'publish' ? 1 : 0;
				break;
			case 'setRequired':
			case 'setNotRequired':
				$col = 'required';
				$state = $task == 'setRequired' ? 1 : 0;
				break;
			case 'setEditable':
			case 'setNotEditable':
				$col = 'editable';
				$state = $task == 'setEditable' ? 1 : 0;
				break;
			case 'setFee':
			case 'setFree':
				$col = 'isFree';
				$state = $task == 'setFree' ? 1 : 0;
				break;
			case 'toggle':
				$fIds = [];
				$fid = Input::Int( 'fid' );
				$attribute = explode( '.', Input::Task() );
				$attribute = in_array( $attribute[ 2 ], [ 'editable', 'enabled', 'required' ] ) ? $attribute[ 2 ] : 'is' . ucfirst( $attribute[ 2 ] );
				$this->_model = SPFactory::Model( 'field', true )->init( $fid );
				try {
					$current = $this->_model->get( $attribute );
					Factory::Db()->update( 'spdb_field', [ $attribute => !$current ], [ 'fid' => $fid ], 1 );

					$logAction = !$current ? constant( 'SPC::LOG_' . $actions[ $attribute ] ) : constant( 'SPC::LOG_UN' . $actions[ $attribute ] );
					SPFactory::history()->logAction( $logAction, $fid, $this->_model->get( 'section' ), 'field', C::ES, [ 'name' => $this->_model->get( 'name' ), 'type' => $this->_model->get( 'type' ) ] );

					$field = $this->_model->get( 'name' ) ? $this->_model->get( 'name' ) : $fid;
					$msg = Sobi::Txt( 'FM.STATE_CHANGED', [ 'field' => $field ] );
				}
				catch ( SPException $x ) {
					Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 500, __LINE__, __FILE__ );
					$field = $this->_model->get( 'name' ) ? $this->_model->get( 'name' ) : $fid;
					$msg = Sobi::Txt( 'FM.STATE_NOT_CHANGED', [ 'field' => $field ] );
				}
				break;
		}

		if ( count( $fIds ) ) {
			$msg = [];
			foreach ( $fIds as $fid ) {
				$this->_model = SPFactory::Model( 'field', true )->init( $fid );
				try {
					$logAction = $state ? constant( 'SPC::LOG_' . $actions[ $col ] ) : constant( 'SPC::LOG_UN' . $actions[ $col ] );
					SPFactory::history()->logAction( $logAction, $fid, $this->_model->get( 'section' ), 'field', C::ES, [ 'name' => $this->_model->get( 'name' ), 'type' => $this->_model->get( 'type' ) ] );

					Factory::Db()->update( 'spdb_field', [ $col => $state ], [ 'fid' => $fid ], 1 );

					$field = $this->_model->get( 'name' ) ? $this->_model->get( 'name' ) : $fid;
					$msg[] = [ 'text' => Sobi::Txt( 'FM.STATE_CHANGED', [ 'field' => $field ] ), 'type' => C::SUCCESS_MSG ];
				}
				catch ( SPException $x ) {
					Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 500, __LINE__, __FILE__ );
					$field = $this->_model->get( 'name' ) ? $this->_model->get( 'name' ) : $fid;
					$msg[] = [ 'text' => Sobi::Txt( 'FM.STATE_NOT_CHANGED', [ 'field' => $field ] ), 'type' => C::ERROR_MSG ];
				}
			}
		}
		SPFactory::cache()->cleanSection( (int) Sobi::Section() );

		return $msg;
	}

	/**
	 * Route task.
	 *
	 * @return bool
	 * @throws \ReflectionException
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function execute()
	{
		/* parent class executes the plugins */
		$retval = false;
		$task = $this->_task;
		if ( strstr( $this->_task, '.' ) ) {
			$task = explode( '.', $this->_task );
			if ( !is_numeric( $task[ 1 ] ) ) {
				$this->_fieldType = $task[ 1 ];
			}
			if ( isset( $task[ 2 ] ) && $task[ 2 ] == 'category' ) {
				$this->_category = true;
			}
			if ( isset ( $task[ 1 ] ) && is_numeric( $task[ 1 ] ) ) {
				$section = $task[ 1 ];
			}
			$task = $task[ 0 ];
		}

		switch ( $task ) {
			case 'list':
				$retval = true;
				$this->listFields();
				break;
			case 'add':
			case 'edit':
				$retval = true;
				$this->edit();
				break;
			case 'cancel':
				$retval = true;
				$this->response( Sobi::Back() );
				break;
			case 'addNew':
				$retval = true;
				Sobi::Redirect( Sobi::Url( [ 'task' => 'field.edit', 'fid' => $this->saveNew(), 'sid' => Input::Sid() ] ) );
				break;
			case 'apply':
			case 'save':
				$retval = true;
				$this->save();
				break;
			case 'clone':
				$retval = true;
				$this->save( true );
				break;
			case 'copyto':
				$retval = true;
				$this->response( Sobi::Back(), $this->copyToSection( $section ), true );
				break;
			case 'copy':
				$retval = true;
				$this->response( Sobi::Back(), $this->copyToSection( 0 ), true );
				break;
			case 'delete':
				$retval = true;
				SPFactory::cache()->cleanSection();
				$this->response( Sobi::Url( [ 'task' => 'field.list', 'pid' => Sobi::Section() ] ), $this->delete(), true );
				break;
			case 'reorder':
				$retval = true;
				$this->reorder();
				break;
			case 'revisions':
				$retval = true;
				$this->revisions(); //???
				break;
			case 'up':
			case 'down':
				$retval = true;
				$this->singleReorder( $this->_task == 'up' );
				break;
			case 'hide':
			case 'publish':
			case 'setRequired':
			case 'setNotRequired':
			case 'setEditable':
			case 'setNotEditable':
			case 'setFee':
			case 'setFree':
			case 'toggle':
				$retval = true;
				$this->_type = 'section';
				$this->authorise( 'configure' );
				SPFactory::cache()->cleanSection();
				$this->response( Sobi::Back(), $this->changeState( $task ), true );
				break;
			default:
				/* case plugin didn't register this task, it was an error */
				if ( !Sobi::Trigger( 'Execute', $this->name(), [ &$this ] ) ) {
					$fid = Input::Int( 'fid' );
					$method = $this->_task;
					if ( $fid ) {
						SPLoader::loadModel( 'field', true );
						$fdata = $this->loadField( $fid );
						$field = new SPAdmField();
						$field->extend( $fdata );
						try {
							$field->$method();
						}
						catch ( SPException $x ) {
							Sobi::Error( $this->name(), SPLang::e( 'SUCH_TASK_NOT_FOUND', Input::Task() ), C::NOTICE, 404, __LINE__, __FILE__ );
						}
					}
					else {
						if ( !parent::execute() ) {
							Sobi::Error( $this->name(), SPLang::e( 'SUCH_TASK_NOT_FOUND', Input::Task() ), C::NOTICE, 404, __LINE__, __FILE__ );
						}
					}
				}
				break;
		}

		return $retval;
	}

	/**
	 * @param $fIds
	 *
	 * @return bool
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	private function _reorder( $fIds )
	{
		if ( !count( $fIds ) ) {
			return true;
		}
		asort( $fIds );
		$c = 0;
		foreach ( $fIds as $fid => $pos ) {
			$c++;
			try {
				Factory::Db()->update( 'spdb_field', [ 'position' => $c ], [ 'fid' => $fid ] );
			}
			catch ( Sobi\Error\Exception $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 500, __LINE__, __FILE__ );
			}
		}
	}

	/**
	 * @param string $stylefile
	 *
	 * @return string
	 */
	protected function checkTemplate( string $stylefile ): string
	{
		$hint = C::ES;
		if ( $stylefile ) {
			$template = Sobi::Cfg( 'section.template', SPC::DEFAULT_TEMPLATE );
			if ( !FileSystem::Exists( SOBI_PATH . '/usr/templates/' . $template . '/css/helper/_applications.linc' ) ) {
				$hint = Sobi::Txt( 'TEMPLATE_COMPATIBILITY', $stylefile );
			}
		}

		return $hint;
	}
}
