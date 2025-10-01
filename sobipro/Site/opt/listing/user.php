<?php
/**
 * @package SobiPro multi-directory component with content construction support
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2023 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/GPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/gpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * @created 16-Aug-2010 by Radek Suski
 * @modified 19 May 2023 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadController( 'listing_interface' );
SPLoader::loadController( 'section' );

use Sobi\Input\Input;

/**
 * Class SPUserListing
 */
class SPUserListing extends SPSectionCtrl implements SPListing
{
	/** @var string */
	protected $_type = 'listing';
	/** @var int */
	protected $uid = 0;
	/** @var stdClass */
	protected $user = 0;

	/**
	 * @return void
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function execute()
	{
		$this->view();
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception|\DOMException
	 */
	protected function view()
	{
		/* determine template package */
		$tplPackage = Sobi::Cfg( 'section.template', SPC::DEFAULT_TEMPLATE );
		Sobi::ReturnPoint();

		$this->_task = 'user';

		if ( !( $this->_model ) ) {
			$this->setModel( 'section' );
			$this->_model->init( Sobi::Section() );
		}
		$this->visible();

		/* load template config */
		$this->template();
		$this->tplCfg( $tplPackage );

		/* get limits - if defined in template config - otherwise from the section config */
		$eLimit = $this->tKey( $this->template, 'entries_limit', Sobi::Cfg( 'list.entries_limit', 2 ) );
		$eInLine = $this->tKey( $this->template, 'entries_in_line', Sobi::Cfg( 'list.entries_in_line', 2 ) );

		$url = [ 'sid' => Input::Sid(), 'task' => 'list.user' ];
		if ( Input::Int( 'uid' ) ) {
			$url[ 'uid' ] = Input::Int( 'uid' );
			$this->uid = Input::Int( 'uid' );
		}
		else {
			$this->uid = (int) Sobi::My( 'id' );
			Input::Set( 'uid', $this->uid );
		}
		$this->user = SPUser::getBaseData( (int) $this->uid );
		if ( !$this->user ) {
			throw new SPException( SPLang::e( 'UNAUTHORIZED_ACCESS' ) );
		}

		// get the template override if any
		if ( !$this->template ) {
			$this->template = 'user';
		}
		$url[ 'sptpl' ] = $this->template;

		/* get the site to display */
		$site = Input::Int( 'site', 'request', 1 );
		$eLimStart = ( ( $site - 1 ) * $eLimit );

		$eOrder = $this->parseOrdering( 'entries', 'eorder', $this->tKey( $this->template, 'entries_ordering', Sobi::Cfg( 'list.entries_ordering', 'name.asc' ) ) );
		$eCount = count( $this->getEntries( $eOrder, 0, 0, true, [ 'spo.owner' => $this->uid ], true, Sobi::Section() ) );
		$entries = $this->getEntries( $eOrder, $eLimit, $eLimStart, true, [ 'spo.owner' => $this->uid ], true, Sobi::Section() );
//		$eCount = count( $this->_getEntries( 0, 0, true ) );
//		$entries = $this->_getEntries( $eLimit, $site );

		/** @var SPPageNavXSLT $pn */
		$pn = SPFactory::Instance( 'helpers.pagenav_' . $this->tKey( $this->template, 'template_type', 'xslt' ), $eLimit, $eCount, $site, $url );
		$navigation = $pn->get();
		if ( Input::Int( 'site', 'request', 0 ) ) {
			$url[ 'site' ] = Input::Int( 'site', 'request', 0 );
		}

		SPFactory::header()->addCanonical( Sobi::Url( SPFactory::mainframe()->NormalizeUrl( $url ), true, true, true ) );

		/* handle meta data */
		SPFactory::header()->objMeta( $this->_model );

		/* add pathway */
		SPFactory::mainframe()->addToPathway( Sobi::Txt( 'UL.PATH_TITLE', [ 'username' => $this->user->username, 'user' => $this->user->name ] ), SPFactory::mainframe()->getMenuLink( $url ) );

		$listingName = Sobi::Txt( 'UL.TITLE', [ 'username' => $this->user->username, 'user' => $this->user->name, 'section' => $this->_model->get( 'name' ) ] );
		SPFactory::header()->addTitle( $listingName, [ ceil( $eCount / $eLimit ), $site ] );

		$visitor = SPFactory::user()->getCurrent();

		/** @var SPListingView $view */
		$view = SPFactory::View( 'listing' )
			->assign( $eLimit, '$eLimit' )
			->assign( $eLimStart, '$eLimStart' )
			->assign( $eCount, '$eCount' )
			->assign( $eInLine, '$eInLine' )
			->assign( $this->_task, 'task' )
			->assign( $this->_model, 'section' )
			->setConfig( $this->_tCfg, $this->template )
			->setTemplate( $tplPackage . '.' . $this->templateType . '.' . $this->template )
			->assign( $navigation, 'navigation' )
			->assign( $visitor, 'visitor' )
			->assign( $entries, 'entries' )
			->assign( $listingName, 'listing_name' );

		Sobi::Trigger( 'UserListing', 'View', [ &$view ] );
		$view->display();
	}

	/**
	 * @param null $field
	 *
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function entries( $field = null )
	{
		return $this->getEntries( 0, 0, true );
	}

//	public function _getEntries( $eLimit, $site, $ids = false )
//	{
//		$conditions = array( 'owner' => $this->uid );
//		$entries = array();
//		$eOrder = 'id';
//		/* get the site to display */
//		$eLimStart = ( ( $site - 1 ) * $eLimit );
//
//		/* check user permissions for the visibility */
//		if ( Sobi::My( 'id' ) ) {
//			$this->userPermissionsQuery( $conditions );
//		}
//		else {
//			$conditions = array_merge( $conditions, array( 'state' => '1', '@VALID' => Factory::Db()->valid( 'validUntil', 'validSince' ) ) );
//		}
//		try {
//			$results = Factory::Db()
//					->select( 'id', 'spdb_object', $conditions, $eOrder, $eLimit, $eLimStart, true )
//					->loadResultArray();
//		} catch ( SPException $x ) {
//			Sobi::Error( 'UserListing', SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), SPC::WARNING, 0, __LINE__, __FILE__ );
//		}
//		if ( $ids ) {
//			return $results;
//		}
//		if ( count( $results ) ) {
//			foreach ( $results as $i => $sid ) {
//				$entries[ $i ] = $sid;
//			}
//		}
//		return $entries;
//	}

	/**
	 * @param $request
	 */
	public function setParams( $request )
	{
	}

	/**
	 * @param $task
	 *
	 * @return void
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function setTask( $task )
	{
		$this->_task = strlen( $task ) ? $task : $this->_defTask;
		$helpTask = $this->_type . '.' . $this->_task;
		Sobi::Trigger( $this->name(), __FUNCTION__, [ &$this->_task ] );
		SPFactory::registry()->set( 'task', $helpTask );
	}
}
