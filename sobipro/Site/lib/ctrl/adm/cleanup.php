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
 * @created 04 January 2023 by Sigrid Suski
 * @modified 19 September 2023 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadController( 'config', true );

use Sobi\C;
use Sobi\FileSystem\FileSystem;
use Sobi\Input\Input;
use Sobi\Lib\Factory;

/**
 * Class SPCleanUp
 */
class SPCleanUp extends SPConfigAdmCtrl
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
				$this->screen();
				Sobi::ReturnPoint();
				break;
			case 'checks':
				$this->cleanup( 'all', true );
				break;
			case 'all':
				$this->cleanup( 'all' );
				break;
			case 'files':
				$this->cleanup( 'files' );
				break;
			case 'database':
				$this->cleanup( 'db' );
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
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function screen()
	{
		$menu = $this->createMenu( 'cleanup' );
		$trim = defined( 'SOBI_TRIMMED' );

		/** @var SPAdmView $view */
		$view = SPFactory::View( 'cleanup', true );
		$view
			->assign( $trim, 'trim' )
			->assign( $this->_task, 'task' )
			->assign( $menu, 'menu' );

		Sobi::Trigger( 'CleanUp', 'View', [ &$view ] );

		$view->display();
	}

	/**
	 * @param string $what
	 * @param bool $checkonly
	 *
	 * @return void
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function cleanup( string $what, bool $checkonly = false )
	{
		$msg = $deleted = [];
		if ( $what == 'db' || $what == 'all' ) {
			/* remove all items from object table which do not have an oType set */
			$lostItems = Factory::Db()
				->select( 'id', 'spdb_object', [ 'oType' => C::ES ] )
				->loadResultArray();
			$counter = count( $lostItems );

			if ( $counter ) {
				$list = ' ';
				foreach ( $lostItems as $item ) {
					$list .= $item . ', ';
				}
				$msg[] = [ 'text' => Sobi::Txt( 'CLEANUP.CLEANUP_CHECK_MSG_DBERROR', $counter, 'spdb_object', $list ) . '<br/>', 'type' => C::INFO_MSG ];
				if ( !$checkonly ) {
					try {
						Factory::Db()->delete( 'spdb_object', [ 'oType' => C::ES ] );
						$deleted[] = [ 'text' => Sobi::Txt( 'CLEANUP.CLEANUP_COUNTER_DELETED', $counter, 'spdb_object' ) . '<br/>', 'type' => C::SUCCESS_MSG ];
					}
					catch ( Exception $exception ) {
					}
				}
			}

			/* remove the items without oType also from the relations table if available */
			$categories = SPFactory::Model( 'section' )->getChilds( 'category', true );
			$entries = Factory::Db()
				->dselect( 'id', 'spdb_relations', [ 'pid' => $categories, 'oType' => 'entry' ] )
				->loadResultArray();
			$lostRelations = array_intersect( $entries, $lostItems );
			$counter = count( $lostRelations );

			if ( $counter ) {
				$list = ' ';
				foreach ( $lostItems as $item ) {
					$list .= $item . ', ';
				}
				$msg[] = [ 'text' => Sobi::Txt( 'CLEANUP.CLEANUP_CHECK_MSG_LOSTRELATIONS', $counter, 'spdb_relations', $list ) . '<br/>', 'type' => C::INFO_MSG ];
				if ( !$checkonly ) {
					try {
						Factory::Db()->delete( 'spdb_relations', [ 'id' => $lostRelations ] );
						$deleted[] = [ 'text' => Sobi::Txt( 'CLEANUP.CLEANUP_COUNTER_DELETED', $counter, 'spdb_relations' ) . '<br/>', 'type' => C::SUCCESS_MSG ];
					}
					catch ( Exception $exception ) {
					}
				}
			}

			$entries = Factory::Db()
				->dselect( 'id', 'spdb_language', [ 'oType' => '' ] )
				->loadResultArray();
			$lostLanguageData = array_intersect( $entries, $lostItems );
			$counter = count( $lostLanguageData );

			if ( $counter ) {
				$list = ' ';
				foreach ( $lostLanguageData as $item ) {
					$list .= $item . ', ';
				}
				$msg[] = [ 'text' => Sobi::Txt( 'CLEANUP.CLEANUP_CHECK_MSG_DBERROR', $counter, 'spdb_language', $list ) . '<br/>', 'type' => C::INFO_MSG ];
				if ( !$checkonly ) {
					try {
						Factory::Db()->delete( 'spdb_language', [ 'id' => $lostLanguageData ] );
						$deleted[] = [ 'text' => Sobi::Txt( 'CLEANUP.CLEANUP_COUNTER_DELETED', $counter, 'spdb_language' ) . '<br/>', 'type' => C::SUCCESS_MSG ];
					}
					catch ( Exception $exception ) {
					}
				}
			}

			$fieldData = Factory::Db()
				->dselect( 'sid', 'spdb_field_data' )
				->loadResultArray();

			$allObjects = Factory::Db()
				->select( 'id', 'spdb_object' )
				->loadResultArray();

			$lostFieldData = array_diff( $fieldData, $allObjects );
			$counter = count( $lostFieldData );
			if ( $counter ) {
				$list = ' ';
				foreach ( $lostFieldData as $item ) {
					$list .= $item . ', ';
				}
				$msg[] = [ 'text' => Sobi::Txt( 'CLEANUP.CLEANUP_CHECK_MSG_MATCH', $counter, 'spdb_field_data', 'spdb_field_object', $list ) . '<br/>', 'type' => C::INFO_MSG ];
				if ( !$checkonly ) {
					try {
						Factory::Db()->delete( 'spdb_field_data', [ 'sid' => $lostFieldData ] );
						$deleted[] = [ 'text' => Sobi::Txt( 'CLEANUP.CLEANUP_COUNTER_DELETED', $counter, 'spdb_field_data' ) . '<br/>', 'type' => C::SUCCESS_MSG ];
					}
					catch ( Exception $exception ) {
					}
				}
			}

			if ( $checkonly ) {
				/* looking for 'no name' fields */
				$namefids = SPFactory::config()->nameFieldFids();
				if ( count( $namefids ) ) {
					$namefields = Factory::Db()
						->select( [ 'sid', 'baseData' ], 'spdb_field_data', [ 'fid' => $namefids ] )
						->loadObjectList( 'sid' );

					if ( count( $namefields ) ) {
						$nonamelist = ' ';
						foreach ( $namefields as $sid => $name ) {
							if ( !$name->baseData ) {
								$nonamelist .= $sid . ', ';
							}
						}
						if ( $nonamelist != ' ' ) {
							$msg[] = [ 'text' => Sobi::Txt( 'CLEANUP.CLEANUP_CHECK_MSG_NONAME', $nonamelist ) . '<br/>', 'type' => C::INFO_MSG ];

						}
					}
				}
			}

			if ( !count( $msg ) ) {
				$msg[] = [ 'text' => Sobi::Txt( 'CLEANUP.CHECK_OK' ), 'type' => C::SUCCESS_MSG ];
			}
			else {
				if ( count( $deleted ) ) {
					$msg = array_merge( $msg, $deleted );
				}
			}

			$this->response( Sobi::Url( [ 'task' => 'cleanup' ] ), $msg, false );
		}
	}
}