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
 * @created 10-Jan-2009 by Radek Suski
 * @modified 10 November 2023 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadController( 'controller' );
SPLoader::loadController( 'category' );

use Sobi\C;
use Sobi\FileSystem\FileSystem;
use Sobi\Input\Input;
use Sobi\Lib\Factory;
use Sobi\Utils\StringUtils;
use SobiPro\Helpers\ControllerTrait;
use SobiPro\Helpers\MenuTrait;

/**
 * Class SPCategoryAdmCtrl
 */
class SPCategoryAdmCtrl extends SPCategoryCtrl
{
	use ControllerTrait {
		customCols as protected; parseObject as protected;
	}
	use MenuTrait {
		setMenuItems as protected;
	}

	/**
	 * @return bool
	 * @throws SPException
	 * @throws \Sobi\Error\Exception|\ReflectionException
	 */
	public function execute()
	{
		/* parent class executes the plugins */
		$retval = true;
		switch ( $this->_task ) {
			case 'edit':
			case 'add':
				SPLoader::loadClass( 'html.input' );
				$this->editForm();
//				$this->authorise( $this->_task );
				break;
			case 'view':
				Sobi::ReturnPoint();
				SPLoader::loadClass( 'html.input' );
				$this->view();
				break;
			case 'clone':
				$this->authorise( 'edit' );
				$this->checkIn( Input::Int( 'category_id' ) );
				$this->_model = null;
				Input::Set( 'sid', Input::Int( 'category_id' ) );
				Input::Set( 'category_id', 0 );
				Input::Set( 'category_state', 0 );
				$this->save( false, true );
				break;
			case 'reorder':
				$this->authorise( 'edit' );
				SPFactory::cache()->cleanCategories();
				$this->reorder();
				break;
			case 'up':
			case 'down':
				SPFactory::cache()->cleanCategories();
				$this->authorise( 'edit' );
				$this->singleReorder( $this->_task == 'up' );
				break;
			case 'approve':
			case 'unapprove':
				$this->authorise( 'edit' );
				$this->approval( $this->_task == 'approve' );
				break;
			case 'hide':
			case 'publish':
				$this->authorise( 'edit' );
				SPFactory::cache()->cleanCategories();
				$this->state( $this->_task == 'publish' );
				break;
			case 'toggle.enabled':
			case 'toggle.approval':
				SPFactory::cache()->cleanCategories();
				$this->authorise( 'edit' );
				$this->toggleState();
				break;
			case 'delete':
				/** Wed, Jan 15, 2014 11:05:28
				 * in the administrator are we can delete category only from the list
				 * Preventing deletion of THIS category (Issue #1162)
				 * Basically if there was no array of $cids something went wrong
				 */
				$cids = Input::Arr( 'c_sid', 'request', [] );
				if ( count( $cids ) ) {
					SPFactory::cache()->cleanCategories();
					parent::execute();  /* let the parent delete the category */
				}
				else {
					$this->response( Sobi::Back(), SPLang::e( 'DELETE_CAT_NO_ID' ), false, C::ERROR_MSG );
				}
				break;
			default:
				/* case plugin didn't register this task, it was an error */
				if ( !parent::execute() ) {
					Sobi::Error( $this->name(), SPLang::e( 'SUCH_TASK_NOT_FOUND', Input::Task() ), C::NOTICE, 404, __LINE__, __FILE__ );
					$retval = false;
				}
				break;
		}

		return $retval;
	}

	/**
	 * @param $state
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function state( $state )
	{
		if ( $this->_model->get( 'id' ) ) {
			$this->authorise( $this->_task );
			$this->_model->changeState( $state );
			SPFactory::cache()
				->purgeSectionVars()
				->deleteObj( 'category', $this->_model->get( 'id' ) )
				->deleteObj( 'category', $this->_model->get( 'parent' ) );

			SPFactory::history()->logAction( $state ? SPC::LOG_PUBLISH : SPC::LOG_UNPUBLISH,
				$this->_model->get( 'id' ),
				$this->_model->get( 'section' ),
				$this->type(),
				C::ES,
				[ 'name' => $this->_model->get( 'name' ) ]
			);
			$this->response( Sobi::Back(), Sobi::Txt( $state ? 'CAT.PUBLISHED' : 'CAT.UNPUBLISHED' ), false );
		}
		else {
			$this->response( Sobi::Back(), Sobi::Txt( 'CHANGE_NO_ID' ), false, C::ERROR_MSG );
		}
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function toggleState()
	{
		if ( $this->_task == 'toggle.enabled' ) {
			$this->state( !$this->_model->get( 'state' ) );
		}
		else {
			$this->approval( !$this->_model->get( 'approved' ) );
		}
	}

	/**
	 * @param $approve
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function approval( $approve )
	{
		$sids = Input::Arr( 'c_sid', 'request', [] );
		if ( !count( $sids ) ) {
			if ( $this->_model->get( 'id' ) ) {
				$sids = [ $this->_model->get( 'id' ) ];
			}
			else {
				$sids = [];
			}
		}
		if ( !count( $sids ) ) {
			$this->response( Sobi::Back(), Sobi::Txt( 'CHANGE_NO_ID' ), false, C::ERROR_MSG );
		}
		else {
			foreach ( $sids as $sid ) {
				try {
					Factory::Db()->update( 'spdb_object', [ 'approved' => $approve ? 1 : 0 ], [ 'id' => $sid, 'oType' => 'category' ] );
					SPFactory::cache()->deleteObj( 'category', $sid );
				}
				catch ( SPException $x ) {
					Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
				}
			}
			SPFactory::cache()->purgeSectionVars();
			SPFactory::history()->logAction( $approve ? SPC::LOG_APPROVE : SPC::LOG_UNAPPROVE,
				$this->_model->get( 'id' ),
				$this->_model->get( 'section' ),
				$this->type(),
				C::ES,
				[ 'name' => $this->_model->get( 'name' ) ]
			);
			$this->response( Sobi::Back(), Sobi::Txt( $approve ? 'CAT.APPROVED' : 'CAT.UNAPPROVED' ), false );
		}
	}

	/**
	 * @throws SPException|\Sobi\Error\Exception
	 */
	private function reorder()
	{
		/* get the requested ordering */
		$sids = Input::Arr( 'cp_sid', 'request', [] );
		/* re-order it to the valid ordering */
		$order = [];
		asort( $sids );

		$cLimStart = Input::Int( 'cLimStart', 'request', 0 );
		$cLimit = Sobi::GetUserState( 'adm.categories.limit', 'climit', Sobi::Cfg( 'adm_list.cats_limit', 15 ) );
		$LimStart = $cLimStart ? ( ( $cLimStart - 1 ) * $cLimit ) : $cLimStart;
		if ( count( $sids ) ) {
			$c = 0;
			foreach ( $sids as $sid => $pos ) {
				$order[ ++$c ] = $sid;
			}
		}

		$db = Factory::Db();
		foreach ( $order as $sid ) {
			try {
				$db->update( 'spdb_relations', [ 'position' => ++$LimStart ], [ 'id' => $sid, 'oType' => 'category' ] );
			}
			catch ( Sobi\Error\Exception $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
			}
		}
		$this->response( Sobi::Back(), Sobi::Txt( 'CATEGORIES_ARE_RE_ORDERED' ), true, C::SUCCESS_MSG );
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception|\ReflectionException
	 */
	protected function view()
	{
		Input::Set( 'task', 'category.view' );
		$section = StringUtils::Nid( Sobi::Section( true ) );

		/* get the lists ordering and limits */
		$eLimit = Sobi::GetUserState( 'entries.limit', 'elimit', Sobi::Cfg( "admin.entries-limit.$section", 25 ) );
		$cLimit = Sobi::GetUserState( 'categories.limit', 'climit', Sobi::Cfg( "admin.categories-limit.$section", 15 ) );

		$eLimStart = Input::Int( 'eSite', 'request', 0 );
		$cLimStart = Input::Int( 'cSite', 'request', 0 );

		/* get child categories and entries */
		$e = $this->_model->getChilds();
		$c = $this->_model->getChilds( 'category' );

		// just in case the given page is bigger than all existing pages
		$cCount = count( $c );
		$cPages = ceil( $cCount / $cLimit );
		if ( $cLimStart > $cPages ) {
			$cLimStart = $cPages;
			Input::Set( 'cSite', $cPages );
		}
		$eCount = count( $e );
		$ePages = ceil( $eCount / $eLimit );
		if ( $eLimStart > $ePages ) {
			$eLimStart = $ePages;
			Input::Set( 'eSite', $ePages );
		}

		$entries = $categories = [];
		SPLoader::loadClass( 'models.dbobject' );

		/* if there are categories in the root */
		$db = Factory::Db();
		if ( count( $c ) ) {
			$results = [];
			try {
				$limitStart = $cLimStart ? ( $cLimStart - 1 ) * $cLimit : $cLimStart;
				$limit = max( $cLimit, 0 );
				$cOrder = $this->parseObject( 'categories', 'corder', 'position.asc', $limit, $limitStart, $c );

				$results = $db
					->select( '*', 'spdb_object', [ 'id' => $c, 'oType' => 'category' ], $cOrder, $limit, $limitStart )
					->loadResultArray();
			}
			catch ( SPException $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
			}
			foreach ( $results as $i => $category ) {
				$categories[ $i ] = SPFactory::Category( $category ); // new $cClass();
				//$categories[ $i ]->extend( $category );
			}
		}

		/* if there are entries */
		if ( count( $e ) ) {
			try {
				$limitStart = $eLimStart ? ( ( $eLimStart - 1 ) * $eLimit ) : $eLimStart;
				$limit = max( $eLimit, 0 );
				$eOrder = $this->parseObject( 'entries', 'eorder', 'position.asc', $limit, $limitStart, $e );
				$entries = $db
					->select( '*', 'spdb_object', [ 'id' => $e, 'oType' => 'entry' ], $eOrder, $limit, $limitStart )
					->loadResultArray();
			}
			catch ( SPException $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
			}
		}

		$nameField = SPFactory::config()->nameField();
		$nameFieldNid = $nameField->get( 'nid' );
		$nameFieldName = $nameField->get( 'name' );

		/* create menu */
		$menu = $this->setMenuItems( 'section.' . $this->_task, true, Input::Sid() );

//		SPLoader::loadClass( 'views.adm.menu' );
//		$menu = new SPAdmSiteMenu( 'section.' . $this->_task, Input::Sid() );
//		$tree->init( Sobi::Reg( 'current_section' ), Input::Sid() );

		/* get view class */
		/** @var SPCategoryAdmView $view */
		$view = SPFactory::View( 'category', true );
		$eSite = Input::Int( 'eSite', 'request', 1 );
		$cSite = Input::Int( 'cSite', 'request', 1 );
		$customCols = $this->customCols();
		$userStateEOrder = Sobi::GetUserState( 'entries.order', 'eorder', 'position.asc' );
		$userStateCOrder = Sobi::GetUserState( 'categories.order', 'corder', 'position.asc' );
		$catName = $this->_model->get( 'name' );
		$pid = Sobi::Section();
		$sid = Input::Sid();

		$showhint = !( $cCount > 0 || $eCount > 0 );
		$isRoot = false;
		$isSubcategory = true;

		$view
			->assign( $eLimit, '$eLimit' )
			->assign( $eLimit, 'entries-limit' )
			->assign( $cLimit, 'categories-limit' )
			->assign( $eSite, 'entries-site' )
			->assign( $cSite, 'categories-site' )
			->assign( $cCount, 'categories-count' )
			->assign( $showhint, 'showhint' )
			->assign( $eCount, 'entries-count' )
			->assign( $this->_task, 'task' )
			->assign( $this->_model, 'category' )
			->assign( $isRoot, 'root' )
			->assign( $isSubcategory, 'subcategory' )
			->assign( $categories, 'categories' )
			->assign( $entries, 'entries' )
			->assign( $customCols, 'fields' )
			->assign( $nameFieldName, 'entries_name' )
			->assign( $nameFieldNid, 'entries_field' )
			->assign( $menu, 'menu' )
			->assign( $userStateEOrder, 'eorder' )
			->assign( $userStateCOrder, 'corder' )
			->assign( $catName, 'category_name' )
			->addHidden( $pid, 'pid' )
			->addHidden( $sid, 'sid' );

		Sobi::Trigger( 'Category', 'View', [ &$view ] );
		$view->display();
	}

	/**
	 * @throws \ReflectionException
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function editForm()
	{
		/* if adding new */
		if ( !$this->_model || $this->_task == 'add' ) {
			$this->setModel( SPLoader::loadModel( 'category' ) );
		}

		$this->checkTranslation();
		$settings = SPFactory::config()->getSettings();
		$fonts = $settings[ 'icon-fonts' ];
		$selectedFonts = $settings[ 'template' ][ 'icon_fonts_arr' ];
		if ( $selectedFonts ) {
			foreach ( $fonts as $font => $f ) {
				if ( in_array( $font, $selectedFonts ) ) {
					SPFactory::header()->addHeadLink( $f, C::ES, C::ES, 'stylesheet' );
				}
			}
		}
		$this->_model->formatDatesToEdit();
		$id = $this->_model->get( 'id' );
		if ( !$id ) {
			$this->_model->set( 'state', 1 );
			$this->_model->set( 'parent', Input::Sid() );
		}
		if ( $this->_model->isCheckedOut() ) {
			SPFactory::message()->error( Sobi::Txt( 'CAT.IS_CHECKED_OUT' ), false );
		}
		else {
			$this->_model->checkOut();
		}
		$this->_model->loadFields( Sobi::Section(), true );
		$eFields = SPConfig::fields( Sobi::Section() );
		unset( $eFields[ Sobi::Cfg( 'entry.name_field' ) ] );
		$entryFields = [];
		$selectedEntryFields = $this->_model->get( 'entryFields' );
		if ( !is_array( $selectedEntryFields ) ) {
			$selectedEntryFields = [];
		}
		$all = $this->_model->get( 'allFields' );
		foreach ( $eFields as $id => $field ) {
			$entryFields[] = [ 'id' => $id, 'name' => $field, 'included' => $all || in_array( $id, $selectedEntryFields ) ];
		}
		/* @var SPEntry $this - >_model */
		$fields = $this->_model->get( 'fields' );

		// we need it for the icons' fonts
		SPFactory::header()->initBase( true );   /* should have the parameter true as this is adm */

		/** @var SPCategoryAdmView $view */
		$view = SPFactory::View( 'category', true );
		$view
			->assign( $this->_model, 'category' )
			->assign( $this->_task, 'task' )
			->assign( $id, 'cid' )
			->assign( $fields, 'fields' )
			->assign( $entryFields, 'entryFields' )
			->addHidden( Sobi::Section(), 'pid' );

		Sobi::Trigger( 'Category', 'EditView', [ &$view ] );
		$view->display();
	}


	/**
	 * @param $up
	 *
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	private function singleReorder( $up )
	{
		$db = Factory::Db();
		$eq = $up ? '<' : '>';
		$dir = $up ? 'position.desc' : 'position.asc';
		$current = $this->_model->get( 'position' );
		try {
			$interchange = $db
				->select( 'position, id', 'spdb_relations', [ 'position' . $eq => $current, 'oType' => $this->_model->type(), 'pid' => Input::Int( 'pid' ) ], $dir, 1 )
				->loadAssocList();
			if ( $interchange && count( $interchange ) ) {
				$db->update( 'spdb_relations', [ 'position' => $interchange[ 0 ][ 'position' ] ], [ 'oType' => $this->_model->type(), 'pid' => Input::Int( 'pid' ), 'id' => $this->_model->get( 'id' ) ], 1 );
				$db->update( 'spdb_relations', [ 'position' => $current ], [ 'oType' => $this->_model->type(), 'pid' => Input::Int( 'pid' ), 'id' => $interchange[ 0 ][ 'id' ] ], 1 );
			}
			else {
				$current = $up ? $current-- : $current++;
				$db->update( 'spdb_relations', [ 'position' => $current ], [ 'oType' => $this->_model->type(), 'pid' => Input::Int( 'pid' ), 'id' => $this->_model->get( 'id' ) ], 1 );
			}
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}
		$this->response( Sobi::Back(), Sobi::Txt( 'CATEGORY_POSITION_CHANGED' ), true, C::SUCCESS_MSG );
	}
}
