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
SPLoader::loadController( 'section' );

use Sobi\C;
use Sobi\Input\Input;
use Sobi\FileSystem\FileSystem;
use Sobi\Lib\Factory;
use Sobi\Utils\StringUtils;
use SobiPro\Helpers\ControllerTrait;

/**
 * Class SPSectionAdmCtrl
 */
class SPSectionAdmCtrl extends SPSectionCtrl
{
	use ControllerTrait {
		customCols as protected; parseObject as protected;
	}
	use \SobiPro\Helpers\MenuTrait {
		setMenuItems as protected;
	}

	/**
	 * @return void
	 * @throws \ReflectionException
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function execute()
	{
		switch ( $this->_task ) {
			case 'add':
				$this->setModel( SPLoader::loadModel( 'section' ) );
				$this->editForm();
				break;
			case 'edit':
				Sobi::Redirect( Sobi::Url( [ 'task' => 'config', 'sid' => Input::Sid() ] ), C::ES, C::ES, true );
				break;
			case 'view':
			case 'entries':
				Sobi::ReturnPoint();
				$this->viewSection( $this->_task == 'entries', Sobi::GetUserState( 'entries_filter', 'sp_entries_filter', C::ES ) );
				break;
			case 'toggle.enabled':
			case 'toggle.approval':
				$this->toggleState();
				break;
			default:
				/* case plugin didn't register this task, it was an error */
				if ( !parent::execute() ) {
					Sobi::Error( $this->name(), SPLang::e( 'SUCH_TASK_NOT_FOUND', Input::Task() ), SPC::NOTICE, 404, __LINE__, __FILE__ );
				}
				break;
		}
	}

	/**
	 * @param $allEntries
	 * @param string $filterTerm
	 *
	 * @throws \ReflectionException
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function viewSection( $allEntries, string $filterTerm = C::ES )
	{
		if ( !Sobi::Section() ) {
			Sobi::Error( 'Section', SPLang::e( 'Missing section identifier' ), C::ERROR, 500, __LINE__, __FILE__ );
		}

		if ( $allEntries ) {
			Input::Set( 'task', 'section.entries' );
		}
		else {
			Input::Set( 'task', 'section.view' );
		}

		$db = Factory::Db();
		$section = StringUtils::Nid( Sobi::Section( true ) );

		$this->_model->init( Sobi::Section() );

		/* get the lists ordering and limits */
		$eLimit = Sobi::GetUserState( 'entries.limit', 'elimit', Sobi::Cfg( "admin.entries-limit.$section", 25 ) );
		$cLimit = Sobi::GetUserState( 'categories.limit', 'climit', Sobi::Cfg( "admin.categories-limit.$section", 15 ) );

		$eLimStart = Input::Int( 'eSite', 'request', 0 );
		$cLimStart = Input::Int( 'cSite', 'request', 0 );

		/* get child categories and entries */
		/* @todo: need better method - the query can be very large with lot of entries */
		$categoryChilds = $entryChilds = [];
		if ( !$allEntries ) {
			$entryChilds = $this->_model->getChilds();
			$categoryChilds = $this->_model->getChilds( 'category' );
		}
		/** yes - this is needed. In case we have entries without data in the name field */
		else {
			if ( !$filterTerm ) {
				$categoryChilds = $this->_model->getChilds( 'category', true );
				$categoryChilds[] = (int) Sobi::Section();
				if ( count( $categoryChilds ) ) {
					try {
						$entryList1 = $db
							->dselect( 'id', 'spdb_relations', [ 'pid' => $categoryChilds, 'oType' => 'entry' ] )
							->loadResultArray();
						$entryList2 = $db
							->dselect( 'sid', 'spdb_field_data', [ 'section' => Sobi::Section(), 'fid' => Sobi::Cfg( 'entry.name_field' ) ] )
							->loadResultArray();
						$entryChilds = array_merge( $entryList1, $entryList2 );
						//$entryChilds = array_unique( $entryChilds );
					}
					catch ( SPException $x ) {
						Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
					}
				}
			}
			else {
				try {
					$entryChilds = $db
						->dselect( 'sid', 'spdb_field_data',
							[ 'section'  => Sobi::Section(),
							  'fid'      => Sobi::Cfg( 'entry.name_field' ),
							  'baseData' => "%$filterTerm%" ]
						)
						->loadResultArray();
				}
				catch ( SPException $x ) {
					Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
				}
			}
		}
		$entryChilds = array_unique( $entryChilds );
		// just in case the given site is greater than all existing sites
		$cCount = count( $categoryChilds );
		$cPages = ceil( $cCount / $cLimit );
		if ( $cLimStart > $cPages ) {
			$cLimStart = $cPages;
			Input::Set( 'cSite', $cPages );
		}
		$eCount = count( $entryChilds );
		$ePages = ceil( $eCount / $eLimit );
		if ( $eLimStart > $ePages ) {
			$eLimStart = $ePages;
			Input::Set( 'eSite', $ePages );
		}

		$entries = $categories = $results = [];
		/* if there are entries in the root */
		if ( count( $entryChilds ) ) {
			try {
				$limit = max( $eLimit, 0 );
				$limitStart = $eLimStart ? ( $eLimStart - 1 ) * $eLimit : $eLimStart;
				$eOrder = $this->parseObject( 'entries', 'eorder', 'position.asc', $limit, $limitStart, $entryChilds );
				$results = $db
					->select( 'id', 'spdb_object', [ 'id' => $entryChilds, 'oType' => 'entry' ], $eOrder, $limit, $limitStart )
					->loadResultArray();
			}
			catch ( SPException $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
			}
			foreach ( $results as $index => $entry ) {
				$entries[ $index ] = $entry;
			}
		}

		/* if there are categories in the root */
		if ( is_array( $categoryChilds ) && count( $categoryChilds ) ) {
			try {
				$limitStart = $cLimStart ? ( $cLimStart - 1 ) * $cLimit : $cLimStart;
				$limit = max( $cLimit, 0 );
				$cOrder = $this->parseObject( 'categories', 'corder', 'order.asc', $limit, $limitStart, $categoryChilds );
				$results = $db
					->select( 'id', 'spdb_object', [ 'id' => $categoryChilds, 'oType' => 'category' ], $cOrder, $limit, $limitStart )
					->loadResultArray();
			}
			catch ( SPException $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
			}
			foreach ( $results as $index => $category ) {
				$categories[ $index ] = SPFactory::Category( $category );
			}
		}

		/* create menu */
		$menu = $this->setMenuItems( 'section.' . $this->_task, true );
		$eSite = Input::Int( 'eSite', 'request', 1 );
		$cSite = Input::Int( 'cSite', 'request', 1 );
		$customCols = $this->customCols();
		//$entriesOrdering = Sobi::GetUserState( 'entries.eorder', 'eorder', 'order.asc' );
		$entriesOrdering = Sobi::GetUserState( 'entries.order', 'eorder',
			Sobi::Cfg( "admin.entries-order.$section", 'order.asc' ) );
		//$categoriesOrdering = Sobi::GetUserState( 'categories.corder', 'corder', 'order.asc' );
		$categoriesOrdering = Sobi::GetUserState( 'categories.order', 'corder',
			Sobi::Cfg( "admin.categories-order.$section", 'order.asc' ) );

		$nameField = SPFactory::config()->nameField();
		$nameFieldNid = $nameField->get( 'nid' );
		$nameFieldName = $nameField->get( 'name' );

		$sectionName = Sobi::Section( true );
		$sectionId = (int) Sobi::Section();
		$sid = Input::Sid() ? Input::Sid() : $sectionId;    /* $sid == 0 -> All Entries screen */
		$isRoot = $sectionId == $sid;
		$isSubcategory = $sectionId != $sid && $sid != 0;

		$showhint = $allEntries ? $eCount == 0 && !$filterTerm : $cCount == 0 && !$filterTerm;

		/** @var SPSectionAdmView $view */
		$view = SPFactory::View( 'section', true );
		$view
			->assign( $nameFieldNid, 'entries_field' )
			->assign( $eLimit, 'entries-limit' )
			->assign( $cLimit, 'categories-limit' )
			->assign( $eSite, 'entries-site' )
			->assign( $cSite, 'categories-site' )
			->assign( $cCount, 'categories-count' )
			->assign( $showhint, 'showhint' )
			->assign( $eCount, 'entries-count' )
			->assign( $this->_task, 'task' )
			->assign( $filterTerm, 'filter' )
			->assign( $customCols, 'fields' )
			->assign( $this->_model, 'section' )
			->assign( $isRoot, 'root' )
			->assign( $isSubcategory, 'subcategory' )
			->assign( $categories, 'categories' )
			->assign( $entries, 'entries' )
			->assign( $nameFieldName, 'entries_name' )
			->assign( $menu, 'menu' )
			->assign( $entriesOrdering, 'ordering' )
			->assign( $categoriesOrdering, 'corder' )
			->assign( $sectionName, 'category' )
			->addHidden( $sectionId, 'pid' )
			->addHidden( $sid, 'sid' );

		Sobi::Trigger( 'Section', 'View', [ &$view ] );
		$view->display();
	}

	/**
	 * @throws ReflectionException
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function editForm()
	{
		$this->checkTranslation();
		$this->_model->formatDatesToEdit();

		/** @var SPSectionAdmView $view */
		$view = SPFactory::View( 'section', true );
		$trim = defined( 'SOBI_TRIMMED' );
		$multiLang = Sobi::Cfg( 'lang.multimode', false );
		$languages = $view->languages();
		$view
			->assign( $this->_task, 'task' )
			->assign( $trim, 'trim' )
			->assign( $multiLang, 'multilingual' )
			->assign( $this->_model, 'section' )
			->assign( $languages, 'languages-list' )
			->determineTemplate( 'section', 'edit' );

		Sobi::Trigger( 'Section', 'EditView', [ &$view ] );
		$view->display();
	}

	/**
	 * @param string $action
	 * @param string $ownership
	 *
	 * @return bool
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function authorise( $action = 'access', $ownership = 'valid' )
	{
		$manage = [ 'add' => true, 'manage' => true, 'delete' => true ];
		if ( isset( $manage[ $action ] ) && ( Sobi::Can( 'cms.admin' ) || Sobi::Can( 'cms.options' ) ) ) {
			return true;
		}

		return parent::authorise( $action, $ownership );
	}
}
