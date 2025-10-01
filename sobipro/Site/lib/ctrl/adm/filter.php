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
 * @created 06-Aug-2010 by Radek Suski
 * @modified 06 January 2023 by Sigrid Suski
 */

use Sobi\C;
use Sobi\Input\Input;
use Sobi\Lib\Factory;
use Sobi\Utils\StringUtils;

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadController( 'config', true );

/**
 * Class SPFilter
 */
class SPFilter extends SPConfigAdmCtrl
{
	/**
	 * @var string
	 */
	protected $_defTask = 'list';
	/**
	 * @var string
	 */
	protected $_type = 'filter';

	/**
	 * @return \SPFilter
	 */
	public static function & getInstance()
	{
		static $filter = null;
		if ( !$filter || !( $filter instanceof SPFilter ) ) {
			$filter = new self();
		}

		return $filter;
	}

	/**
	 * @return bool|void
	 * @throws \ReflectionException
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function execute()
	{
		$this->_task = strlen( $this->_task ) ? $this->_task : $this->_defTask;
		switch ( $this->_task ) {
			case 'list':
				$this->screen();
				Sobi::ReturnPoint();
				break;
			case 'edit':
			case 'add':
				$this->edit();
				break;
			case 'delete':
				$this->delete();
				break;
			case 'save':
				$this->save( false );
				break;
			default:
				/* case plugin didn't register this task, it was an error */
				if ( !parent::execute() ) {
					Sobi::Error( 'filter_ctrl', 'Task not found', SPC::WARNING, 404, __LINE__, __FILE__ );
				}
				break;
		}
	}

	/**
	 * Deletes a filter (in all languages).
	 *
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function delete()
	{
		$filters = $this->getFilters();
		$id = Input::Cmd( 'filter_id' );
		if ( $id && isset( $filters[ $id ] ) && ( strlen( $filters[ $id ][ 'options' ] ) ) ) {
			Factory::Db()->delete( 'spdb_language', [ 'sKey' => 'filter-' . $id, 'oType' => 'fields_filter' ] );
			unset( $filters[ $id ] );
			SPFactory::registry()->saveDBSection( $filters, 'fields_filter' );
			$this->response( Sobi::Url( 'filter' ), Sobi::Txt( 'FLR.MSG_FILTER_DELETED' ), true, SPC::SUCCESS_MSG );
		}
		else {
			$this->response( Sobi::Url( 'filter' ), SPLang::e( 'FILTER_NOT_FOUND' ), true, SPC::ERROR_MSG );
		}
	}

	/**
	 * Saves a filter.
	 *
	 * @param bool $apply
	 * @param false $clone
	 *
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function save( $apply = false, $clone = false )
	{
		if ( !( Factory::Application()->checkToken() ) ) {
			Sobi::Error( 'Token', SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ), C::ERROR, 403, __LINE__, __FILE__ );
		}

		$db = Factory::Db();
		$id = Input::Cmd( 'filter_id' );
		if ( $id ) {
			$this->validate( 'field.filter', 'filter' );
			$filters = $this->getFilters();
			$name = Input::String( 'filter_name', 'request', 'Filter Name' );
			$msg = str_replace( [ "\n", "\t", "\r" ], C::ES, StringUtils::Clean( Input::String( 'filter_message', 'request', 'The data, entered in the $field field, contains illegal characters!' ) ) );
			$regex = StringUtils::Clean( SPRequest::raw( 'filter_regex', '/^[\.*]+$/' ) );
			$regex = str_replace( '[:apostrophes:]', '\"' . "\'", $regex );
			$regex = base64_encode( str_replace( [ "\n", "\t", "\r" ], C::ES, $regex ) );
			$custom = 'custom';

			if ( isset( $filters[ $id ] ) && !( strlen( $filters[ $id ][ 'options' ] ) ) ) {
				$regex = $filters[ $id ][ 'params' ];
				$custom = null;
			}

			try {
				$filters[ $id ] = [
					'params'  => $regex,
					'key'     => $id,
					'value'   => $name,
					'options' => $custom,
				];

				SPFactory::registry()->saveDBSection( $filters, 'fields_filter' );

				/* save the message in the language table */
				$filter = [ 'sKey'        => 'filter-' . $id,
				            'sValue'      => $msg,
				            'section'     => 0,
				            'language'    => Sobi::Lang(),
				            'oType'       => 'fields_filter',
				            'fid'         => 0,
				            'id'          => 0,
				            'params'      => C::ES,
				            'options'     => C::ES,
				            'explanation' => C::ES ];

				$db->insertUpdate( 'spdb_language', $filter );
			}
			catch ( Exception $x ) {
			}

			$this->response( Sobi::Url( 'filter' ), Sobi::Txt( 'FLR.MSG_FILTER_SAVED' ), false, 'success' );
		}
		else {
			$this->response( Sobi::Url( 'filter' ), SPLang::e( 'PLEASE_FILL_IN_ALL_REQUIRED_FIELDS' ), true, C::ERROR_MSG );
		}
	}

	/**
	 * Gets the filters from the database.
	 *
	 * @return array
	 * @throws \SPException
	 */
	public function getFilters(): array
	{
		$registry =& SPFactory::registry();
		$registry->loadDBSection( 'fields_filter' );
		$filters = $registry->get( 'fields_filter' );

		try {
			$messages = Factory::Db()
				->select( [ 'sKey', 'sValue' ], 'spdb_language', [ 'oType' => 'fields_filter', 'language' => Sobi::Lang() ] )
				->loadAssocList();

			$messageList = $messageListGB = [];
			foreach ( $messages as $message ) {
				$messageList[ $message[ 'sKey' ] ] = $message[ 'sValue' ];
			}

			/* if the language is not en-GB, get the english texts in case the set language is missing them */
			if ( Sobi::Lang() != 'en-GB' ) {
				$messagesGB = Factory::Db()
					->select( [ 'sKey', 'sValue' ], 'spdb_language', [ 'oType' => 'fields_filter', 'language' => 'en-GB' ] )
					->loadAssocList();
				foreach ( $messagesGB as $message ) {
					$messageListGB[ $message[ 'sKey' ] ] = $message[ 'sValue' ];
				}
			}
			$messageList = array_merge( $messageListGB, $messageList );
		}
		catch ( Exception $x ) {
		}

		$filterList = [];
		foreach ( $filters as $fid => $filter ) {
			$filterList[ $fid ] = [
				'params'  => $filter[ 'params' ],
				'key'     => $fid,
				'value'   => $filter[ 'value' ],
				'message' => $messageList[ 'filter-' . $fid ],
				'options' => $filter[ 'options' ],
			];
		}
		ksort( $filterList );

		return $filterList;
	}

	/**
	 * Edits a filter.
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception|\ReflectionException
	 */
	protected function edit()
	{
		$this->checkTranslation();

		$id = Input::Cmd( 'fid' );
		$filters = $this->getFilters();
		if ( count( $filters ) && isset( $filters[ $id ] ) ) {
			$filterList = [
				'id'       => $id,
				'regex'    => str_replace( '\"' . "\'", '[:apostrophes:]', base64_decode( $filters[ $id ][ 'params' ] ) ),
				'name'     => $filters[ $id ][ 'value' ],
				'message'  => $filters[ $id ][ 'message' ],
				'editable' => strlen( $filters[ $id ][ 'options' ] ),
				'readonly' => !( strlen( $filters[ $id ][ 'options' ] ) ),
			];
		}
		else {
			$filterList = [ 'id' => '', 'regex' => '', 'name' => '', 'message' => '', 'editable' => true, 'readonly' => false ];
		}
		$multiLang = Sobi::Cfg( 'lang.multimode', false );

		/** @var SPAdmView $view */
		$view = SPFactory::View( 'view', true );
		$view->assign( $this->_task, 'task' )
			->assign( $filterList, 'filter' )
			->assign( $multiLang, 'multilingual' )
			->determineTemplate( 'field', 'filter' )
			->setTemplate( 'default' );
		$view->display();
	}

	/**
	 * Shows the filters list.
	 *
	 * @throws \ReflectionException
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function screen()
	{
		$this->checkTranslation();

		$filters = $this->getFilters();
		$filterList = [];
		if ( count( $filters ) ) {
			foreach ( $filters as $name => $filter ) {
				$filterList[] = [
					'id'       => $name,
					'regex'    => str_replace( '\"' . "\'", '[:apostrophes:]', base64_decode( $filter[ 'params' ] ) ),
					'name'     => $filter[ 'value' ],
					'message'  => $filter[ 'message' ],
					'editable' => strlen( $filter[ 'options' ] ),
				];
			}
		}
		$menu = $this->createMenu( 'filter' );
//		$menuOut = $this->createMenu();
		$url = Sobi::Url( [ 'task' => 'filter.edit', 'out' => 'html' ], true );

		/** @var SPAdmView $view */
		$view = SPFactory::View( 'view', true );
		$languages = $view->languages();
		$multiLang = Sobi::Cfg( 'lang.multimode', false );

		$view->assign( $this->_task, 'task' )
//			->assign( $menuOut, 'menu' )
			->assign( $url, 'edit_url' )
			->assign( $filterList, 'filters' )
			->assign( $menu, 'menu' )
			->assign( $languages, 'languages-list' )
			->assign( $multiLang, 'multilingual' )
			->determineTemplate( 'field', 'filters' );
		$view->display();
	}
}
