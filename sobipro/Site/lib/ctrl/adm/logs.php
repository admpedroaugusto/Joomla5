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
 * @created 19 July 2021 by Sigrid Suski
 * @modified 18 April 2023 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadController( 'config', true );

use Sobi\C;
use Sobi\FileSystem\FileSystem;
use Sobi\Input\Input;
use Sobi\Lib\Factory;

/**
 * Class SPLogs
 */
class SPLogs extends SPConfigAdmCtrl
{
	/**
	 * @var string
	 */
	protected $_defTask = 'list';

	/**
	 * @return void
	 * @throws \ReflectionException
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function execute()
	{
		$this->_task = strlen( $this->_task ) ? $this->_task : $this->_defTask;
		switch ( $this->_task ) {
			case 'list':
				$this->screen( Sobi::GetUserState( 'entries_filter', 'sp_log_filter' ) );
				Sobi::ReturnPoint();
				break;
			case 'deleteall':
				$this->delete( 'all' );
				break;
			default:
				/* case plugin didn't register this task, it was an error */
				if ( !parent::execute() ) {
					Sobi::Error( 'error_ctrl', 'Task not found', SPC::WARNING, 404, __LINE__, __FILE__ );
				}
				break;
		}
	}

	/**
	 * @param $filterTerm
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function screen( $filterTerm )
	{
		$logs = null;
		$logCount = 0;
		if ( Sobi::Cfg( 'logging.action', true ) ) {
			$logLimit = Sobi::GetUserState( 'logs.limit', 'llimit', Sobi::Cfg( 'adm_list.logs_limit', 25 ) );
			$logLimitStart = Input::Int( 'logsSite', 'request', 1 );
			$limitStart = $logLimitStart ? ( ( $logLimitStart - 1 ) * $logLimit ) : $logLimitStart;
			$logOrder = Sobi::GetUserState( 'logs.order', 'lorder', 'changedAt.desc' );
			/* if order is other than date, add desc date additionally */
			if ( $logOrder != 'changedAt.desc' && $logOrder != 'changedAt.asc' ) {
				$logOrder .= ',changedAt.desc';
			}

			$where = [ '!changeAction' => SPC::LOG_SAVE ];
			if ( $filterTerm ) {
				$where[ '@VALID' ] = Factory::Db()->argsOr( [ 'type'         => "%$filterTerm%",
				                                              'changeAction' => "%$filterTerm%",
				                                              'userName'     => "%$filterTerm%" ] );
			}
			try {
				$logCount = Factory::Db()
					->dselect( 'COUNT(revision)', 'spdb_history', $where )
					->loadResult();
			}
			catch ( Sobi\Error\Exception $x ) {
			}
			if ( $logLimit == -1 ) {
				$logLimit = $logCount;
			}
			try {
				$logs = Factory::Db()
					->select( '*', 'spdb_history', $where, $logOrder, $logLimit, $limitStart )
					->loadAssocList();
			}
			catch ( Sobi\Error\Exception $x ) {
			}
		}
		$menu = $this->createMenu( 'logs' );
		$showhint = $logCount == 0 && !$filterTerm;

		/** @var SPAdmView $view */
		$view = SPFactory::View( 'logs', true );
		$view
			->assign( $logOrder, 'ordering' )
			->assign( $this->_task, 'task' )
			->assign( $menu, 'menu' )
			->assign( $logs, 'logs' )
			->assign( $filterTerm, 'filter' )
			->assign( $showhint, 'showhint' )
			->assign( $logLimit, 'logs-limit' )
			->assign( $logCount, 'logs-count' )
			->assign( $logLimitStart, 'logs-site' );

		Sobi::Trigger( 'Logs', 'View', [ &$view ] );

		$view->display();
	}

	/**
	 * @param string $what
	 *
	 * @return void
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function delete( string $what = 'all' )
	{
		if ( $what == 'all' ) {
			try {
				Factory::Db()->truncate( 'spdb_history' );
			}
			catch ( Sobi\Error\Exception $x ) {
			}

			SPFactory::cache()->cleanSection( -1 );
			SPFactory::history()->logAction( SPC::LOG_DELETE, 0, 0, 'config', C::ES, [ 'name' => C::ES, 'type' => 'action-logging-' . $what ] );
		}
		$this->response( Sobi::Url( [ 'task' => 'logs' ] ), Sobi::Txt( 'MSG.ACTIONLOG_DELETED' ), false );
	}
}
