<?php
/**
 * @package SobiPro multi-directory component with content construction support
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/GPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/gpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * @created 16-Aug-2010 by Radek Suski
 * @modified 08 February 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadController( 'listing_interface' );
SPLoader::loadController( 'section' );

use Sobi\C;
use Sobi\Input\Input;
use Sobi\Lib\Factory;

/**
 * Class SPAlphaListing
 */
class SPAlphaListing extends SPSectionCtrl implements SPListing
{
	/**
	 * @var string
	 */
	private $_letter = C::ES;
	/**
	 * @var string
	 */
	private $_field = null;
	/**
	 * @var string
	 */
	private $_nid = C::ES;
	/**
	 * @var string
	 */
	private $_fieldType = C::ES;
	/**
	 * @var string
	 */
	protected $_type = 'listing';

	/**
	 * @return void
	 * @throws SPException
	 * @throws \Sobi\Error\Exception|\DOMException
	 */
	public function execute()
	{
		Input::Set( 'task', strtolower( 'list.' . $this->_task ) );
		$task = str_replace( ':', '-', Input::Task() );
		$task = explode( '.', $task );
		if ( isset( $task[ 2 ] ) && $task[ 2 ] == 'switch' && isset( $task[ 3 ] ) ) {
			$this->switchIndex( $task[ 3 ] );
		}
		else {
			if ( Input::Cmd( 'letter' ) ) {
				$this->_letter = urldecode( Input::Cmd( 'letter' ) );
			}
			else {
				$this->_letter = urldecode( $task[ 2 ] );
				Input::Set( 'letter', strtoupper( $this->_letter ) );
				if ( isset( $task[ 3 ] ) ) {
					$this->determineFid( $task[ 3 ] );
				}
				else {
					$this->determineFid( Sobi::Cfg( 'alphamenu.primary_field' ) );
				}
			}
			if ( !strlen( $this->_letter ) || !Sobi::Section() ) {
				Sobi::Error( $this->name(), SPLang::e( 'SITE_NOT_FOUND_MISSING_PARAMS' ), C::NOTICE, 404, __LINE__, __FILE__ );
			}
			if ( !( preg_match( '/^[\x20-\x7f]*$/D', $this->_letter ) ) && function_exists( 'mb_strtoupper' ) ) {
				$this->_letter = mb_strtoupper( $this->_letter );
			}
			else {
				$this->_letter = strtoupper( $this->_letter );
			}
			$this->view();
		}
	}

	/**
	 * @param $field
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception|\DOMException
	 */
	private function switchIndex( $field )
	{
		$tplPckg = Sobi::Cfg( 'section.template', SPC::DEFAULT_TEMPLATE );
		$letters = explode( ',', Sobi::Cfg( 'alphamenu.letters' ) );
		$letterData = [];

		if ( Sobi::Cfg( 'alphamenu.verify' ) ) {
			$cat = Input::Int( 'cat' ) ? Input::Int( 'cat' ) : Input::Sid();
			if ( Sobi::Cfg( 'alphamenu.catdependent', false ) ) {
				$entries = SPFactory::cache()->getVar( 'alpha_entries_' . $field . $cat );
			}
			else {
				$entries = SPFactory::cache()->getVar( 'alpha_entries_' . $field );
			}
			if ( !$entries ) {
				$entries = [];
				foreach ( $letters as $ltr ) {
					$params = [ 'letter' => $ltr ];
					if ( $field ) {
						$params[ 'field' ] = $field;
					}
					$this->setParams( $params );
					$entries[ $ltr ] = $this->entries( $field );
				}
				if ( Sobi::Cfg( 'alphamenu.catdependent', false ) ) {
					SPFactory::cache()->addVar( $entries, 'alpha_entries_' . $field . $cat );
				}
				else {
					SPFactory::cache()->addVar( $entries, 'alpha_entries_' . $field );
				}

			}
			foreach ( $letters as $ltr ) {
				$letter = [ '_complex' => 1, '_data' => trim( $ltr ) ];
				if ( count( $entries[ $ltr ] ) ) {
					$task = 'list.alpha.' . trim( strtolower( $ltr ) ) . '.' . $field;
					$letter[ '_attributes' ] = [ 'url' => Sobi::Url( [ 'sid'  => Sobi::Section(),
					                                                   'task' => $task ] ) ];
				}
				$letterData[] = $letter;
			}
		}
		else {
			foreach ( $letters as $ltr ) {
				$task = 'list.alpha.' . trim( strtolower( $ltr ) ) . '.' . $field;
				$letterData[] = [
					'_complex'    => 1,
					'_data'       => trim( $ltr ),
					'_attributes' => [ 'url' => Sobi::Url( [ 'sid' => Sobi::Section(), 'task' => $task ] ) ],
				];
			}
		}
		$data = [ '_complex' => 1, '_data' => [ 'letters' => $letterData ] ];

		/** @var SPListingView $view */
		$view = SPFactory::View( 'listing' );
		$view->setTemplate( $tplPckg . '.common.alphaindex' );
		$view->assign( $data, 'alphaMenu' );
		ob_start();
		$view->display( 'menu', 'raw' );
		$out = ob_get_contents();

		SPFactory::mainframe()
			->cleanBuffer()
			->customHeader();
		echo json_encode( [ 'index' => $out ] );

		exit;
	}

	/**
	 * @param $nid
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function determineFid( $nid )
	{
		if ( is_numeric( $nid ) ) {
			$field = Factory::Db()
				->select( [ 'fid', 'fieldType', 'nid' ], 'spdb_field', [ 'section' => Sobi::Section(), 'fid' => $nid ] )
				->loadObject();
		}
		else {
			$field = Factory::Db()
				->select( [ 'fid', 'fieldType', 'nid' ], 'spdb_field', [ 'section' => Sobi::Section(), 'nid' => $nid ] )
				->loadObject();
		}
		$this->_field = $field->fid;
		$this->_nid = $field->nid;
		$this->_fieldType = $field->fieldType;

		Input::Set( 'alpha_field', strtolower( $this->_nid ) );
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
		$this->_task = 'alpha';

		if ( !$this->_model ) {
			$this->setModel( 'section' );
			$this->_model->init( Sobi::Section() );
		}
		$this->visible();
		/* load template config */
		$this->template();
		$this->tplCfg( $tplPackage );

		/* get limits - if defined in template config - otherwise from the section config */
		$eLimit = (int) $this->tKey( $this->template, 'entries_limit', Sobi::Cfg( 'list.entries_limit', 2 ) );
		$eInLine = $this->tKey( $this->template, 'entries_in_line', Sobi::Cfg( 'list.entries_in_line', 2 ) );

		/* get the site to display */
		$site = Input::Int( 'site', 'request', 1 );
		$eLimStart = ( $site - 1 ) * $eLimit;

		$entriesRecursive = (bool) $this->tKey( $this->template, 'entries_recursive', Sobi::Cfg( 'category.entries_recursive', false ) );
		$categories = $this->getCategories( $entriesRecursive );

		$cat = Input::Int( 'cat' );
		$cat = $cat ? : Sobi::Section();
		$category = (int) Sobi::Cfg( 'alphamenu.catdependent', false ) ? $cat : Sobi::Section();
		$eCount = count( $this->getAlphaEntries( 0, 0, true, $categories, $category ) );
		$entries = $this->getAlphaEntries( $eLimit, $site, false, $categories, $category );
		$catname = SPFactory::Category( $cat )->get( 'name' );

		$compare = $this->_field ? : $this->_nid;
		if ( strlen( $compare ) && $compare != Sobi::Cfg( 'alphamenu.primary_field' ) ) {
			$task = 'list.alpha.' . strtolower( $this->_letter ) . '.' . $this->_nid;
		}
		else {
			$task = 'list.alpha.' . strtolower( $this->_letter );
		}

		/** @var SPPageNavXSLT $pn */
		$pn = SPFactory::Instance(
			'helpers.pagenav_' . $this->tKey( $this->template, 'template_type', 'xslt' ),
			$eLimit, $eCount, $site,
			[ 'sid' => Input::Sid(), 'task' => $task ]
		);
		$url = [ 'sid' => Input::Sid(), 'task' => $task ];
		if ( Input::Int( 'site', 'request', 0 ) ) {
			$url[ 'site' ] = Input::Int( 'site', 'request', 0 );
		}
		// get the template override if any
		if ( !$this->template ) {
			$this->template = 'alpha';
		}
		$url[ 'sptpl' ] = $this->template;

		SPFactory::header()->addCanonical( Sobi::Url( $url, true, true, true ) );

		/* handle meta data */
		SPFactory::header()->objMeta( $this->_model );
		$letter = urldecode( Input::Cmd( 'letter' ) );

		/* add pathway */
		if ( !$this->_fieldType ) {     // under which circumstances we are here?
			if ( Sobi::Cfg( 'alphamenu.catdependent', false ) && isset( $catname ) ) {
				SPFactory::mainframe()->addToPathway( Sobi::Txt( 'AL.PATH_TITLE_CAT', [ 'letter' => $letter, 'category' => $catname ] ), SPFactory::mainframe()->getMenuLink( $url ) );
				SPFactory::header()->addTitle( Sobi::Txt( 'AL.PATH_TITLE_CAT', [ 'letter' => $letter, 'category' => $catname ] ), [ ceil( $eCount / $eLimit ), $site ] );
			}
			else {
				SPFactory::mainframe()->addToPathway( Sobi::Txt( 'AL.PATH_TITLE', [ 'letter' => $letter ] ), SPFactory::mainframe()->getMenuLink( $url ) );
				SPFactory::header()->addTitle( Sobi::Txt( 'AL.PATH_TITLE', [ 'letter' => $letter ] ), [ ceil( $eCount / $eLimit ), $site ] );
			}
			$listingName = C::ES;
		}
		else {
			/** @var SPField $field */
			$field = SPFactory::Model( 'field' );
			$field->init( $this->_field );
			if ( Sobi::Cfg( 'alphamenu.catdependent', false ) && isset( $catname ) ) {
				SPFactory::mainframe()->addToPathway( Sobi::Txt( 'AL.PATH_TITLE_FIELD_CAT', [ 'letter' => $letter, 'field' => $field->get( 'name' ), 'category' => $catname ] ), SPFactory::mainframe()->getMenuLink( $url ) );
				SPFactory::header()->addTitle( Sobi::Txt( 'AL.PATH_TITLE_CAT', [ 'letter' => $letter, 'field' => $field->get( 'name' ), 'category' => $catname ] ), [ ceil( $eCount / $eLimit ), $site ] );
				$listingName = Sobi::Txt( 'AL.PATH_TITLE_CAT', [ 'letter' => $this->_letter, 'field' => $field->get( 'name' ), 'category' => $catname ] );
			}
			else {
				SPFactory::mainframe()->addToPathway( Sobi::Txt( 'AL.PATH_TITLE_FIELD', [ 'letter' => $letter, 'field' => $field->get( 'name' ) ] ), SPFactory::mainframe()->getMenuLink( $url ) );
				SPFactory::header()->addTitle( Sobi::Txt( 'AL.PATH_TITLE', [ 'letter' => $letter, 'field' => $field->get( 'name' ) ] ), [ ceil( $eCount / $eLimit ), $site ] );
				$listingName = Sobi::Txt( 'AL.PATH_TITLE', [ 'letter' => $this->_letter, 'field' => $field->get( 'name' ) ] );
			}
		}

		$visitor = SPFactory::user()->getCurrent();
		$navigation = $pn->get();

		/** @var SPListingView $view */
		$view = SPFactory::View( 'listing' );
		$view
			->assign( $eLimit, '$eLimit' )
			->assign( $eLimStart, '$eLimStart' )
			->assign( $eCount, '$eCount' )
			->assign( $eInLine, '$eInLine' )
			->assign( $this->_task, 'task' )
			->assign( $this->_model, 'section' )
			->assign( $listingName, 'listing_name' )
			->setConfig( $this->_tCfg, $this->template )
			->setTemplate( $tplPackage . '.' . $this->templateType . '.' . $this->template )
			->assign( $navigation, 'navigation' )
			->assign( $visitor, 'visitor' )
			->assign( $entries, 'entries' );
		Sobi::Trigger( 'AlphaListing', 'View', [ &$view ] );

		$view->display();
	}

	/**
	 * @param null $field
	 * @param array $categories
	 *
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function entries( $field = null, $categories = [] )
	{
		if ( $field ) {
			$this->determineFid( $field );
		}
		else {
			$this->_field = Sobi::Cfg( 'alphamenu.primary_field', SPFactory::config()->nameField()->get( 'id' ) );
		}

		return $this->getAlphaEntries( 0, 0, true, $categories );
	}

	/**
	 * @param bool $entriesRecursive
	 *
	 * @return array|bool|int|mixed
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function categories( bool $entriesRecursive )
	{
		return $this->getCategories( $entriesRecursive );
	}

	/**
	 * @param bool $entriesRecursive
	 *
	 * @return array|bool|int|mixed
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function getCategories( bool $entriesRecursive = false )
	{
		$categories = [];
		if ( Sobi::Cfg( 'alphamenu.catdependent', false ) ) {
			$pid = Input::Int( 'cat' ) ? Input::Int( 'cat' ) : Input::Sid();
			if ( $entriesRecursive || ( $pid == Sobi::Section() ) ) {

				if ( $pid == Sobi::Section() ) {
					$categories = SPFactory::Section( $pid )->getChilds( 'category', true );
				}
				else {
					$oType = SPFactory::ObjectType( $pid );

					switch ( $oType ) {
						case 'category':
							$this->setModel( 'category' );
							$this->_model->init( $pid );
							$categories = SPFactory::Category( $pid )->getChilds( 'category', true );
							break;
						case 'entry':
							$categories = -1;
							break;
					}
				}
				if ( is_array( $categories ) ) {
					$categories = array_keys( $categories );
					$categories[] = $pid;   // add the recent category as it is not in the list of children
				}
			}
			else {
				$categories = $pid;
			}
			if ( is_array( $categories ) && !count( $categories ) ) {
				$categories = Input::Int( 'cat' );
			}
		}

		// not category dependent
		else {
			$categories = SPFactory::Section( Sobi::Section() )->getChilds( 'category', true );
			if ( is_array( $categories ) ) {
				$categories = array_keys( $categories );
			}
		}

		return $categories;
	}

	/**
	 * @param int $eLimit
	 * @param int $site
	 * @param bool $count
	 * @param array $categories
	 * @param int $pid
	 *
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 * @throws \Exception
	 */
	public function getAlphaEntries( int $eLimit, int $site, bool $count = false, $categories = [], int $pid = 0 )
	{
		$conditions = $entries = [];

		if ( isset( $this->_letter[ 1 ] ) && $this->_letter[ 1 ] == '-' ) {
			$this->_letter = "[{$this->_letter[0]}-{$this->_letter[2]}]";
		}
		$db = Factory::Db();
		/*
		 * Don't know exactly why but on Windows servers there seems to be some problem with unicode chars
		 *     - strtolower/strtoupper is destroying these chars completely
		 *     - MySQL seems to be suddenly case-sensitive with non-latin characters, so we need to ask both
		 * Wed, Apr 4, 2012: Apparently it's not only Windows related
		 */
		if ( !preg_match( '/^[\x20-\x7f]*$/D', $this->_letter ) && function_exists( 'mb_strtolower' ) ) {
			// if we have multibyte string support - ask both cases ...
			$baseCondition = "REGEXP:^$this->_letter|^" . mb_strtoupper( $this->_letter );
		}
		else {
			// if no unicode - great, it'll work.
			// if we don't have MB - shit happens
			$baseCondition = "REGEXP:^$this->_letter";
		}
		switch ( $this->_fieldType ) {
			case 'chbxgroup':
			case 'select':
			case 'multiselect':
				$eOrder = 'sValue';
				$table = $db->join(
					[
						[ 'table' => 'spdb_field_option_selected', 'as' => 'opts' ],
						[ 'table' => 'spdb_language', 'as' => 'lang', 'key' => [ 'opts.optValue', 'lang.sKey' ] ],
						[ 'table' => 'spdb_object', 'as' => 'spo', 'key' => [ 'opts.sid', 'spo.id' ] ],
						[ 'table' => 'spdb_relations', 'as' => 'sprl', 'key' => [ 'opts.sid', 'sprl.id' ] ],
					]
				);
				$oPrefix = 'spo.';
				$conditions[ 'spo.oType' ] = 'entry';
				$conditions[ 'opts.fid' ] = $this->_field;
				$conditions[ 'lang.sValue' ] = $baseCondition;
				break;
			default:
				$eOrder = 'baseData';
				$table = $db->join(
					[
						[ 'table' => 'spdb_field', 'as' => 'fdef', 'key' => 'fid' ],
						[ 'table' => 'spdb_field_data', 'as' => 'fdata', 'key' => 'fid' ],
						[ 'table' => 'spdb_object', 'as' => 'spo', 'key' => [ 'fdata.sid', 'spo.id' ] ],
						[ 'table' => 'spdb_relations', 'as' => 'sprl', 'key' => [ 'fdata.sid', 'sprl.id' ] ],
					]
				);
				$oPrefix = 'spo.';
				$conditions[ 'spo.oType' ] = 'entry';
				$conditions[ 'fdef.fid' ] = $this->_field;
				$conditions[ 'fdata.baseData' ] = $baseCondition;
				$conditions[ '!fdata.baseData' ] = "RLIKE:^Encrypted";
				break;
		}
		$this->_field = $this->_field ? : Sobi::Cfg( 'alphamenu.primary_field', SPFactory::config()->nameField()->get( 'id' ) );

		$pid = $pid ? : Input::Sid();
		if ( !is_array( $categories ) || count( $categories ) ) {
			$conditions[ 'sprl.pid' ] = $categories;
		}
		if ( $pid == -1 ) {
			unset( $conditions[ 'sprl.pid' ] );
		}

		/* check user permissions for the visibility */
		if ( Sobi::My( 'id' ) ) {
			$this->userPermissionsQuery( $conditions, $oPrefix );
			if ( isset( $conditions[ $oPrefix . 'state' ] ) && $conditions[ $oPrefix . 'state' ] ) {    //if state=1, only approved entries
				$conditions[ 'sprl.copy' ] = 0;
			}
		}
		else {
			$conditions = array_merge( $conditions, [ $oPrefix . 'state' => '1', '@VALID' => $db->valid( $oPrefix . 'validUntil', $oPrefix . 'validSince' ) ] );
			$conditions[ 'sprl.copy' ] = '0';
		}

		/* get the site to display */
		$eLimStart = ( ( $site - 1 ) * $eLimit );

		try {
//			$db->select( [$oPrefix . 'id', 'fdata.baseData'], $table, $conditions, $eOrder, $eLimit, $eLimStart, true );

			$db->select( $oPrefix . 'id', $table, $conditions, $eOrder, $eLimit, $eLimStart, true );
			$results = $db->loadResultArray();
		}
		catch ( Exception $x ) {
			Sobi::Error( 'AlphaListing', SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}

		Sobi::Trigger( $this->name(), 'AfterGetEntries', [ &$results, $count ] );
		if ( count( $results ) && !$count ) {
			foreach ( $results as $i => $sid ) {
				// it needs too much memory moving the object creation to the view
				//$entries[ $i ] = SPFactory::Entry( $sid );
				$entries[ $i ] = $sid;
			}
		}
		if ( $count ) {
			Sobi::SetUserData( 'currently-displayed-entries', $results );

			return $results;
		}

		return $entries;
	}

	/**
	 * @param $request
	 *
	 * @throws \Sobi\Error\Exception|\SPException
	 */
	public function setParams( $request )
	{
		if ( isset( $request[ 'letter' ] ) ) {
			$this->_letter = $request[ 'letter' ];
		}
		if ( isset( $request[ 'field' ] ) ) {
			$this->determineFid( $request[ 'field' ] );
		}
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
