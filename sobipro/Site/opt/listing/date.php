<?php
/**
 * @package SobiPro multi-directory component with content construction support
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2022 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/GPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/gpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * @created 16-Aug-2010 by Radek Suski
 * @modified 13 May 2022 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

SPLoader::loadController( 'listing_interface' );
SPLoader::loadController( 'section' );

use Sobi\Input\Input;

/**
 * Class SPDateListing
 */
class SPDateListing extends SPSectionCtrl implements SPListing
{
	/** @var string */
	protected $_type = 'listing';
	/** @var array */
	protected $date = [ 'year' => null, 'month' => null, 'day' => null ];

	/**
	 * @return void
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function execute()
	{
		$this->view();
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function view()
	{
		/* determine template package */
		$tplPackage = Sobi::Cfg( 'section.template', SPC::DEFAULT_TEMPLATE );
		Sobi::ReturnPoint();
		$this->_task = 'date';

		if ( !$this->_model ) {
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

		$date = explode( '.', Input::Cmd( 'date' ) );
		$this->date[ 'year' ] = isset( $date[ 0 ] ) && $date[ 0 ] ? $date[ 0 ] : null;
		$this->date[ 'month' ] = isset( $date[ 1 ] ) && $date[ 1 ] ? $date[ 1 ] : null;
		$this->date[ 'day' ] = isset( $date[ 2 ] ) && $date[ 2 ] ? $date[ 2 ] : null;

		if ( !( $this->date[ 'year' ] ) || !( (int) $this->date[ 'year' ] ) ) {
			throw new SPException( SPLang::e( 'INVALID_DATE_GIVEN' ) );
		}

		/* get the site to display */
		$site = Input::Int( 'site', 'request', 1 );
		$eLimStart = ( ( $site - 1 ) * $eLimit );

		$conditions = [ 'spo.oType' => 'entry', 'year(createdTime)' => $this->date[ 'year' ] ];
		$listing = 'year';
		if ( $this->date[ 'month' ] && $this->date[ 'month' ] < 13 && $this->date[ 'month' ] > 0 ) {
			$conditions[ 'month(createdTime)' ] = $this->date[ 'month' ];
			$listing = 'month';
		}
		if ( $this->date[ 'day' ] && $this->date[ 'day' ] < 13 && $this->date[ 'day' ] > 0 ) {
			$conditions[ 'day(createdTime)' ] = $this->date[ 'day' ];
			$listing = 'day';
		}

		$eOrder = 'createdTime';
		$eCount = count( $this->getEntries( $eOrder, 0, 0, true, $conditions, true, Sobi::Section() ) );
		$entries = $this->getEntries( $eOrder, $eLimit, $eLimStart, true, $conditions, true, Sobi::Section() );

		$url = [ 'sid' => Input::Sid(), 'task' => 'list.date', 'date' => Input::Cmd( 'date' ) ];

		/** @var SPPageNavXSLT $pn */
		$pn = SPFactory::Instance( 'helpers.pagenav_' . $this->tKey( $this->template, 'template_type', 'xslt' ), $eLimit, $eCount, $site, $url );
		$navigation = $pn->get();

		// get the template override if any
		if ( !$this->template ) {
			$this->template = 'date';
		}
		$url[ 'sptpl' ] = $this->template;

		// get the template override if any
		if ( Input::Int( 'site', 'request', 0 ) ) {
			$url[ 'site' ] = Input::Int( 'site', 'request', 0 );
		}
		SPFactory::header()->addCanonical( Sobi::Url( $url, true, true, true ) );

		/* handle meta data */
		SPFactory::header()->objMeta( $this->_model );

		$date = $this->date;
		$monthsNames = explode( ',', Sobi::Txt( 'JS_CALENDAR_MONTHS' ) );
		$date[ 'month' ] = isset( $monthsNames[ $date[ 'month' ] - 1 ] ) ? trim( $monthsNames[ $date[ 'month' ] - 1 ] ) : null;
		if ( $listing == 'day' ) {
			$date[ 'date' ] = date( Sobi::Cfg( 'date.date_format' ), strtotime( $date[ 'month' ] . ' ' . $date[ 'day' ] . ',' . $date[ 'year' ] ) );
		}

		SPFactory::mainframe()->addToPathway( Sobi::Txt( 'DL.PATH_TITLE_' . strtoupper( $listing ), $date ), SPFactory::mainframe()->getMenuLink( $url ) );
		$listingName = Sobi::Txt( 'DL.TITLE_' . strtoupper( $listing ), $date );
		SPFactory::header()->addTitle( $listingName, [ ceil( $eCount / $eLimit ), $site ] );

		$visitor = SPFactory::user()->getCurrent();

		/** @var SPListingView $view */
		$view = SPFactory::View( 'listing' );
		$view
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

	/**
	 * @param $request
	 */
	public function setParams( $request )
	{
	}

	/**
	 * @param $task
	 *
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
