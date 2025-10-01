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

use Sobi\C;
use Sobi\Input\Input;
use Sobi\Lib\Factory;

SPLoader::loadController( 'controller' );

/**
 * Class SPSectionCtrl
 */
class SPSectionCtrl extends SPController
{
	/**
	 * @var string
	 */
	protected $_defTask = 'view';
	/**
	 * @var string
	 */
	protected $_type = 'section';

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 * @throws \Exception
	 */
	protected function view()
	{
		Sobi::ReturnPoint();

		/* determine template package */
		$tplPackage = Sobi::Cfg( 'section.template', SPC::DEFAULT_TEMPLATE );
		/* load template config */
		$this->template();
		$this->tplCfg( $tplPackage );

		/* get limits - if defined in template config - otherwise from the section config */
		$limit = $eLimit = $this->tKey( $this->template, 'entries_limit', Sobi::Cfg( 'list.entries_limit', 2 ) );
		$eInLine = $this->tKey( $this->template, 'entries_in_line', Sobi::Cfg( 'list.entries_in_line', 2 ) );
		$cInLine = $this->tKey( $this->template, 'categories_in_line', Sobi::Cfg( 'list.categories_in_line', 2 ) );
		$cLim = $this->tKey( $this->template, 'categories_limit', -1 );
		$entriesRecursive = $this->tKey( $this->template, 'entries_recursive', Sobi::Cfg( 'list.entries_recursive', false ) );

		/* get the site to display */
		$site = Input::Int( 'site', 'request', 1 );
		$start = $eLimStart = ( ( $site - 1 ) * $eLimit );

		// if only re-order (ordering = 1) and ajax pagination, re-order all visible entries (instead of shorten them again to $elimit)
		$page = $this->tKey( $this->template, 'pagination', 'std' );
		if ( $page == 'ajax' && Input::String( 'ordering', 'request', 0 ) == 1 ) {
			$start = 0;
			$limit = $site * $eLimit;
		}

		/* get the right ordering */
		$eOrder = $this->parseOrdering( 'entries', 'eorder', $this->tKey( $this->template, 'entries_ordering', Sobi::Cfg( 'list.entries_ordering', 'field_name.asc' ) ) );
		if ( $eOrder == 'disabled' ) {    // legacy: if old setting is set to disabled but template does not provide entries_ordering
			$eOrder = 'updatedTime.desc';
		}
		$cOrder = $this->parseOrdering( 'categories', 'corder', $this->tKey( $this->template, 'categories_ordering', Sobi::Cfg( 'list.categories_ordering', 'name.asc' ) ) );
		$orderings = [ 'entries' => $eOrder, 'categories' => $cOrder ];

		/* get entries */
		$en = $this->getEntries( $eOrder, 0, 0, true, null, $entriesRecursive );
		$eCount = ( is_array( $en ) ) ? count( $en ) : 0;
		$entries = $this->getEntries( $eOrder, $limit, $start, false, null, $entriesRecursive );
		$categories = [];
		if ( $cLim ) {
			$categories = $this->getCats( $cOrder, $cLim );
		}

		/* create page navigation */
		$url = [ 'sid' => Input::Sid(), 'title' => Sobi::Cfg( 'sef.alias', true ) ? $this->_model->get( 'nid' ) : $this->_model->get( 'name' ) ];
		if ( Input::Cmd( 'sptpl' ) ) {
			$url[ 'sptpl' ] = Input::Cmd( 'sptpl' );
		}
		$pnc = SPLoader::loadClass( 'helpers.pagenav_' . $this->tKey( $this->template, 'template_type', 'xslt' ) );

//		if ( Input::Cmd( 'sptpl' ) ) {
//			$url = [ 'sptpl' => Input::Cmd( 'sptpl' ), 'sid' => Input::Sid(), 'title' => Sobi::Cfg( 'sef.alias', true ) ? $this->_model->get( 'nid' ) : $this->_model->get( 'name' ) ];
//		}
//		else {
//			$url = [ 'sid' => Input::Sid(), 'title' => Sobi::Cfg( 'sef.alias', true ) ? $this->_model->get( 'nid' ) : $this->_model->get( 'name' ) ];
//		}
		/* @var SPPageNavXSLT $pn */
		$pn = new $pnc( $eLimit, $eCount, $site, $url );

		$orderFields = $this->orderFields( 'view' );

		$fields = [];
		/* handle meta data */
		if ( $this->_type == 'category' ) {
			$this->_model->loadFields( Sobi::Section(), true );
			$fields = $this->_model->get( 'fields' );
		}
		SPFactory::header()->objMeta( $this->_model );

		/* add pathway */
		if ( $eLimit ) {
			SPFactory::mainframe()->addObjToPathway( $this->_model, [ ceil( $eCount / $eLimit ), $site ] );
		}

		$this->_model->countVisit();
		/* get view class */
		/** @var SPView $view */
		$view = SPFactory::View( $this->_type );
		$visitor = SPFactory::user()->getCurrent();
		$nav = $pn->get();
		$view
			->assign( $eLimit, '$eLimit' )
			->assign( $eLimStart, '$eLimStart' )
			->assign( $eCount, '$eCount' )
			->assign( $cInLine, '$cInLine' )
			->assign( $eInLine, '$eInLine' )
			->assign( $fields, 'fields' )
			->assign( $this->_task, 'task' )
			->assign( $this->_model, $this->_type )
			->setConfig( $this->_tCfg, $this->template )
			->setTemplate( $tplPackage . '.' . $this->templateType . '.' . $this->template )
			->assign( $categories, 'categories' )
			->assign( $nav, 'navigation' )
			->assign( $visitor, 'visitor' )
			->assign( $entries, 'entries' )
			->assign( $orderings, 'orderings' )
			->assign( $orderFields, 'orderFields' );
		Sobi::Trigger( $this->name(), 'View', [ &$view ] );
		$view->display( $this->_type );
	}

	/**
	 * @param $cOrder
	 * @param int $cLim
	 *
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function getCats( $cOrder, $cLim = 0 )
	{
		$categories = [];
		$cOrder = trim( $cOrder );
		$cLim = max( $cLim, 0 );
		if ( $this->_model->getChilds( 'category' ) ) {
			$db = Factory::Db();
			$oPrefix = C::ES;

			/* load needed definitions */
			SPLoader::loadClass( 'models.dbobject' );
			$conditions = [];

			switch ( $cOrder ) {
				case 'name.asc':
				case 'name.desc':
					$table = $db->join( [
							[ 'table' => 'spdb_language', 'as' => 'splang', 'key' => 'id' ],
							[ 'table' => 'spdb_object', 'as' => 'spo', 'key' => 'id' ],
						]
					);
					$oPrefix = 'spo.';
					$conditions[ 'spo.oType' ] = 'category';
					$conditions[ 'splang.sKey' ] = 'name';
					$conditions[ 'splang.language' ] = [ Sobi::Lang( false ), Sobi::DefLang(), 'en-GB' ];
					if ( strstr( $cOrder, '.' ) ) {
						$cOrder = explode( '.', $cOrder );
						$cOrder = 'sValue.' . $cOrder[ 1 ];
					}
					break;
				case 'position.asc':
				case 'position.desc':
					$table = $db->join( [
							[ 'table' => 'spdb_relations', 'as' => 'sprl', 'key' => 'id' ],
							[ 'table' => 'spdb_object', 'as' => 'spo', 'key' => 'id' ],
						]
					);
					$conditions[ 'spo.oType' ] = 'category';
					$oPrefix = 'spo.';
					break;
				case 'counter.asc':
				case 'counter.desc':
					$table = $db->join( [
							[ 'table' => 'spdb_counter', 'as' => 'spcounter', 'key' => 'sid' ],
							[ 'table' => 'spdb_object', 'as' => 'spo', 'key' => 'id' ],
						]
					);
					$oPrefix = 'spo.';
					$conditions[ 'spo.oType' ] = 'category';
					if ( strstr( $cOrder, '.' ) ) {
						$cOrder = explode( '.', $cOrder );
						$cOrder = 'spcounter.counter.' . $cOrder[ 1 ];
					}
					break;
				default:
					$table = 'spdb_object';
					break;
			}

			/* check user permissions for the visibility */
			if ( Sobi::My( 'id' ) ) {
				if ( !( Sobi::Can( 'category.access.*' ) ) ) {
					if ( Sobi::Can( 'category.access.unapproved_own' ) ) {
						$conditions[] = $db->argsOr( [ 'approved' => '1', 'owner' => Sobi::My( 'id' ) ] );
					}
					else {
						$conditions[ $oPrefix . 'approved' ] = '1';
					}
				}
				if ( !( Sobi::Can( 'category.access.unpublished' ) ) ) {
					if ( Sobi::Can( 'category.access.unpublished_own' ) ) {
						$conditions[] = $db->argsOr( [ 'state' => '1', 'owner' => Sobi::My( 'id' ) ] );
					}
					else {
						$conditions[ $oPrefix . 'state' ] = '1';
					}
				}
				if ( !( Sobi::Can( 'category.access.*' ) ) ) {
					if ( Sobi::Can( 'category.access.expired_own' ) ) {
						$conditions[ '@VALID' ] = $db->argsOr( [ '@VALID' => $db->valid( $oPrefix . 'validUntil', $oPrefix . 'validSince' ), 'owner' => Sobi::My( 'id' ) ] );
					}
					else {
						$conditions[ 'state' ] = '1';
						$conditions[ '@VALID' ] = $db->valid( $oPrefix . 'validUntil', $oPrefix . 'validSince' );
					}
				}
			}
			else {
				$conditions = array_merge( $conditions, [ $oPrefix . 'state' => '1', $oPrefix . 'approved' => '1', '@VALID' => $db->valid( $oPrefix . 'validUntil', $oPrefix . 'validSince' ) ] );
			}
			$conditions[ $oPrefix . 'id' ] = $this->_model->getChilds( 'category' );
			try {
				$results = $db
					->select( $oPrefix . 'id', $table, $conditions, $cOrder, $cLim, 0, true )
					->loadResultArray();
			}
			catch ( Sobi\Error\Exception $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), SPC::WARNING, 0, __LINE__, __FILE__ );
			}
			Sobi::Trigger( $this->name(), 'AfterGetCategories', [ &$results ] );
			if ( $results && count( $results ) ) {
				foreach ( $results as $i => $cid ) {
					$categories[ $i ] = $cid; // new $cClass();
					//$categories[ $i ]->init( $cid );
				}
			}
		}

		return $categories;
	}

	/**
	 * @param array $conditions
	 * @param string $oPrefix
	 *
	 * @return mixed
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function userPermissionsQuery( &$conditions, string $oPrefix = C::ES )
	{
		//note: unapproved_own rule does not exist due to copy flag problem for newly created entries

		$db = Factory::Db();
		if ( !Sobi::Can( 'entry.*.*' ) ) {
			if ( !Sobi::Can( 'entry.access.*' ) ) {
				if ( Sobi::Can( 'entry.access.unpublished_own' ) ) {
					if ( !Sobi::Can( 'entry.access.unpublished_any' ) ) {
						$conditions[] = $db->argsOr( [ $oPrefix . 'state' => '1',
						                               $oPrefix . 'owner' => Sobi::My( 'id' ) ] );
					}
					//else no state is given to have both states
				}
				elseif ( !Sobi::Can( 'entry.access.unpublished_any' ) ) {
					$conditions[ $oPrefix . 'state' ] = '1';
				}
				//else no state is given to have both states

				/**
				 * Tue, Jul 24, 2018 10:38:58
				 * Ok, how the hell? I mean this shouldn't be handled here at all.
				 * All approved or unapproved entries should be visible.
				 * The model should handle which data should be displayed
				 */

//				if ( Sobi::Can( 'entry.access.unapproved_own' ) ) {
//					if ( !( Sobi::Can( 'entry.access.unapproved_any' ) ) ) {
//						$conditions[] = $db->argsOr( [ $oPrefix . 'approved' => '1', $oPrefix . 'owner' => Sobi::My( 'id' ) ] );
//					}
//				}
//				elseif ( !( Sobi::Can( 'entry.access.unapproved_any' ) ) ) {
//					$conditions[ $oPrefix . 'approved' ] = '1';
//				}
				//else no approval is given to have both approval states

				if ( Sobi::Can( 'entry.access.expired_own' ) ) {
					if ( !( Sobi::Can( 'entry.access.expired_any' ) ) ) {
						$conditions[ '@VALID' ] = $db->argsOr( [ '@VALID' => $db->valid( $oPrefix . 'validUntil', $oPrefix . 'validSince' ), 'owner' => Sobi::My( 'id' ) ] );
					}
				}
				elseif ( !Sobi::Can( 'entry.access.expired_any' ) ) {
					$conditions[ '@VALID' ] = $db->valid( $oPrefix . 'validUntil', $oPrefix . 'validSince' );
				}
				//else no valid until given at all
			}
		}

		return $conditions;
	}

	/**
	 * @param $eOrder
	 * @param null $eLimit
	 * @param null $eLimStart
	 * @param bool $count
	 * @param array $conditions
	 * @param bool $entriesRecursive
	 * @param int $pid
	 *
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function getEntries($eOrder, $eLimit = null, $eLimStart = null, $count = false, $conditions = [], $entriesRecursive = false, $pid = 0)
		{
			// Inicialização do banco de dados
			$db = Factory::Db();
			$entries = [];
			$eDir = 'asc';
			$conditions = is_array($conditions) ? $conditions : [];
		
			// Processar ordenação e direção
			if (strstr($eOrder, '.')) {
				$eOr = explode('.', $eOrder);
				$eOrder = array_shift($eOr);
				$eDir = implode('.', $eOr);
			}
		
			// Obter o PID
			$pid = $pid ?: Input::Sid();
		
			// Verificar ordenação por nome
			if ($eOrder == 'name') {
				$eOrder = SPFactory::config()->nameFieldFid();
			}
		
			// Verificar entradas recursivas
			if ($entriesRecursive) {
				$pids = $this->_model->getChilds('category', true);
				if (is_array($pids)) {
					$pids = array_keys($pids);
				}
				$pids[] = Input::Sid();
				$conditions['sprl.pid'] = $pids;
			} else {
				$conditions['sprl.pid'] = $pid;
			}
		
			// Remover PID se for -1
			if ($pid == -1) {
				unset($conditions['sprl.pid']);
			}
		
			// Ordenação por campo ou contador
			if (strstr($eOrder, 'field_')) {
				static $field = null;
				$specificMethod = false;
				if (!$field) {
					try {
						$fType = $db->select('fieldType', 'spdb_field', [
							'nid' => $eOrder,
							'section' => Sobi::Section(),
							'adminField>' => -1
						])->loadResult();
					} catch (SPException $x) {
						Sobi::Error( $this->name(), SPLang::e( 'CANNOT_DETERMINE_FIELD_TYPE', $x->getMessage() ), SPC::WARNING, 0, __LINE__, __FILE__ );
					}
					if ($fType) {
						$field = SPLoader::loadClass('opt.fields.' . $fType);
					}
				}
				if ($field && method_exists($field, 'customOrdering')) {
					$table = $oPrefix = C::ES;
					$specificMethod = call_user_func_array([$field, 'customOrdering'], [&$table, &$conditions, &$oPrefix, &$eOrder, &$eDir]);
				} elseif ($field && method_exists($field, 'sortBy')) {
					$table = $oPrefix = C::ES;
					$specificMethod = call_user_func_array([$field, 'sortBy'], [&$table, &$conditions, &$oPrefix, &$eOrder, &$eDir]);
				}
				if (!$specificMethod) {
					$table = $db->join([
						['table' => 'spdb_field', 'as' => 'fdef', 'key' => 'fid'],
						['table' => 'spdb_field_data', 'as' => 'fdata', 'key' => 'fid'],
						['table' => 'spdb_object', 'as' => 'spo', 'key' => ['fdata.sid', 'spo.id']],
						['table' => 'spdb_relations', 'as' => 'sprl', 'key' => ['fdata.sid', 'sprl.id']],
					]);
					$oPrefix = 'spo.';
					$conditions['spo.oType'] = 'entry';
					$conditions['fdef.nid'] = $eOrder;
					$eOrder = ($conditions['fdef.nid'] === 'field_processo_licitatorio_n') ? "CAST(SUBSTRING_INDEX(baseData, '/', -1) AS UNSIGNED) $eDir, CAST(SUBSTRING_INDEX(baseData, '/', 1) AS UNSIGNED) $eDir" : 'baseData.' . $eDir;
				}
			} elseif (strstr($eOrder, 'counter')) {
				$table = $db->join([
					['table' => 'spdb_object', 'as' => 'spo', 'key' => 'id'],
					['table' => 'spdb_relations', 'as' => 'sprl', 'key' => ['spo.id', 'sprl.id']],
					['table' => 'spdb_counter', 'as' => 'spcounter', 'key' => ['spo.id', 'spcounter.sid']],
				]);
				$oPrefix = 'spo.';
				$conditions['spo.oType'] = 'entry';
				if (strstr($eOrder, '.')) {
					$cOrder = explode('.', $eOrder);
					$eOrder = 'spcounter.counter.' . $cOrder[1];
				} else {
					$eOrder = 'spcounter.counter.' . $eDir;
				}
			} else {
				$table = $db->join([
					['table' => 'spdb_relations', 'as' => 'sprl', 'key' => 'id'],
					['table' => 'spdb_object', 'as' => 'spo', 'key' => 'id'],
				]);
				$conditions['spo.oType'] = 'entry';
				$eOrder = $eOrder . '.' . $eDir;
				$oPrefix = 'spo.';
				if (strstr($eOrder, 'valid')) {
					$eOrder = $oPrefix . $eOrder;
				}
			}
		
			// Verificar permissões
			if (Sobi::My('id')) {
				$this->userPermissionsQuery($conditions, $oPrefix);
				if (isset($conditions[$oPrefix . 'state']) && $conditions[$oPrefix . 'state']) {
					$conditions['sprl.copy'] = 0;
				}
			} else {
				$conditions = array_merge($conditions, [
					$oPrefix . 'state' => '1',
					'@VALID' => $db->valid($oPrefix . 'validUntil', $oPrefix . 'validSince'),
				]);
				$conditions['sprl.copy'] = '0';
			}
		
			// Executar consulta
			try {
				$results = $db
					->select($oPrefix . 'id', $table, $conditions, $eOrder, $eLimit, $eLimStart, true)
					->loadResultArray();
			} catch (Sobi\Error\Exception $x) {
				Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), SPC::WARNING, 0, __LINE__, __FILE__ );
			}
		
			Sobi::Trigger( $this->name(), 'AfterGetEntries', [ &$results, $count ] );
			if ( is_array( $results ) && count( $results ) && !$count ) {
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
	 * Returns an array with field object of field type which is possible to use as sorting field.
	 *
	 * @param array $types
	 *
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function getOrderFields( $types = [] )
	{
		/*static */
		$cache = [ 'pos' => null ];

		if ( $cache[ 'pos' ] ) {
			return null;
		}
		if ( !( is_array( $types ) && ( count( $types ) ) ) ) {
			// field types for ordering by field (needs corresponding sortBy method)
			$types = explode( ', ', Sobi::Cfg( 'field_types_for_ordering', 'inbox, select, radio, calendar, geomap' ) );
		}

		try {
			$fids = Factory::Db()
				->select( 'fid', 'spdb_field', [ 'fieldType' => $types, 'enabled' => 1, 'section' => Sobi::Reg( 'current_section' ), 'adminField>' => -1 ] )
				->loadResultArray();
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'CANNOT_GET_FIELD_FOR_NAMES', $x->getMessage() ), SPC::WARNING, 0, __LINE__, __FILE__ );
		}

		$fields = [];
		if ( count( $fids ) ) {
			foreach ( $fids as $fid ) {
				/** @var SPField $f */
				$f = SPFactory::Model( 'field', true );
				$f->init( $fid );
				try {
					$f->setCustomField( $fields );
				}
				catch ( SPException $x ) {
					$fields[ $fid ] = $f;
				}
			}
		}
		$cache[ 'pos' ] = $fields;

		return $fields;
	}

	/**
	 * @param string $view
	 *
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function orderFields( $view = 'view' )
	{
		$fields = $this->getOrderFields();
		$fData = [];

		if ( count( $fields ) ) {
			foreach ( $fields as $fid => $field ) {
				// check for encrypted fields; these cannot be used
				$params = $field->get( 'params' );
				$encrypted = false;
				if ( isset ( $params[ 'encryptData' ] ) ) {
					if ( $params[ 'encryptData' ] == 1 ) {
						$encrypted = true;
					}
				}
				if ( $encrypted ) {
					unset( $fields[ $fid ] );
				}
				else {
					try {
						$fData = $field->setCustomOrdering( $fData, $view );
					}
					catch ( SPException $x ) {
						$asc = $params && array_key_exists( 'numeric', $params ) && $params[ 'numeric' ] ? '.num.asc' : '.asc';
						$desc = $params && array_key_exists( 'numeric', $params ) && $params[ 'numeric' ] ? '.num.desc' : '.desc';
						$fData[ $field->get( 'nid' ) . $asc ] = '\'' . $field->get( 'name' ) . '\' ' . Sobi::Txt( 'ORDER_BY_ASC' );
						$fData[ $field->get( 'nid' ) . $desc ] = '\'' . $field->get( 'name' ) . '\' ' . Sobi::Txt( 'ORDER_BY_DESC' );
					}
				}
			}
		}
		$oData = [
			'counter.asc'      => Sobi::Txt( 'ORDER_BY_POPULARITY_ASC' ),
			'counter.desc'     => Sobi::Txt( 'ORDER_BY_POPULARITY_DESC' ),
			'createdTime.asc'  => Sobi::Txt( 'ORDER_BY_CREATION_DATE_ASC' ),
			'createdTime.desc' => Sobi::Txt( 'ORDER_BY_CREATION_DATE_DESC' ),
			'updatedTime.asc'  => Sobi::Txt( 'ORDER_BY_UPDATE_DATE_ASC' ),
			'updatedTime.desc' => Sobi::Txt( 'ORDER_BY_UPDATE_DATE_DESC' ),
			'validUntil.asc'   => Sobi::Txt( 'ORDER_BY_EXPIRATION_DATE_ASC' ),
			'validUntil.desc'  => Sobi::Txt( 'ORDER_BY_EXPIRATION_DATE_DESC' ),
			'random'           => Sobi::Txt( 'ORDER_BY_RANDOM' ),
		];
		$oData = array_merge( $oData, $fData );

		return $oData;
	}

	/**
	 * Sort entries. To be called from the GetEntries() of specific views with the entries to be shown already evaluated in $ids. Returns the entry ids in sorted order.
	 *
	 * @param $eOrder
	 * @param $ids
	 *
	 * @return array
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function sortEntries( $eOrder, $ids ): array
	{
		$sortedEntries = [];
		if ( count( $ids ) > 0 ) {
			$sDir = 'asc';
			if ( strpos( $eOrder, '.' ) !== false ) {
				$sOr = explode( '.', $eOrder );
				$eOrder = array_shift( $sOr );
				$sDir = implode( '.', $sOr );
			}
			if ( $eOrder == 'random' ) {
				shuffle( $ids );
			}
			elseif ( $eOrder == 'counter' ) {
				try {
					$sortedEntries = Factory::Db()
						->select( 'sid', 'spdb_counter', [ 'sid' => $ids ], $eOrder . ' ' . $sDir )
						->loadResultArray();
				}
				catch ( Sobi\Error\Exception $x ) {
					Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
				}

			}
			/* sorting by field */
			elseif ( strstr( $eOrder, 'field_' ) ) {
				$sortedEntries = $this->sortEntriesByField( $eOrder, $sDir, $ids );
			}
			else {
				$table = Factory::Db()->join( [
						[ 'table' => 'spdb_relations', 'as' => 'sprl', 'key' => 'id' ],
						[ 'table' => 'spdb_object', 'as' => 'spo', 'key' => 'id' ],
					]
				);
				if ( strstr( $eOrder, 'valid' ) ) {
					$eOrder = 'spo.' . $eOrder;
				}
				try {
					$sortedEntries = Factory::Db()
						->dselect( 'spo.id', $table, [ 'spo.id' => $ids ], $eOrder . ' ' . $sDir )
						->loadResultArray();

				}
				catch ( Sobi\Error\Exception $x ) {
					Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
				}

			}
		}

		return $sortedEntries;
	}

	/**
	 * Sub method for sortEntries() to sort the given ids by a specific field.
	 * Returns the entry ids in sorted order.
	 *
	 * @param $order
	 * @param $dir
	 * @param $ids
	 *
	 * @return array
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function sortEntriesByField( $order, $dir, $ids ): array
	{
		static $field = null;
		$oPrefix = 'spo.';
		$conditions = [];
		$conditions[ 'spo.oType' ] = 'entry';
		$conditions[ 'spo.id' ] = $ids; /* limit to the given entries */

		if ( !$field ) {
			$fType = null;
			try {
				$fType = Factory::Db()
					->select( 'fieldType', 'spdb_field',
						[ 'nid' => $order, 'section' => Sobi::Section(), 'adminField>' => -1 ]
					)
					->loadResult();
			}
			catch ( Sobi\Error\Exception $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'CANNOT_DETERMINE_FIELD_TYPE', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
			}
			if ( $fType ) {
				$field = SPLoader::loadClass( 'opt.fields.' . $fType );
			}
		}

		$results = [];
		if ( $field && method_exists( $field, 'sortBy' ) ) {
			$table = null;
			/* check if the field sets the parameters by itself (= $specificMethod=true)
			  $specificMethod = false is deprecated (if field has the method, it has to set the parameters) */
			$specificMethod = call_user_func_array( [ $field, 'sortBy' ], [ &$table, &$conditions, &$oPrefix, &$order, &$dir ] );
			if ( !$specificMethod ) {
				$table = Factory::Db()->join(
					[
						[ 'table' => 'spdb_field', 'as' => 'fdef', 'key' => 'fid' ],
						[ 'table' => 'spdb_field_data', 'as' => 'fdata', 'key' => 'fid' ],
						[ 'table' => 'spdb_object', 'as' => 'spo', 'key' => [ 'fdata.sid', 'spo.id' ] ],
						[ 'table' => 'spdb_relations', 'as' => 'sprl', 'key' => [ 'fdata.sid', 'sprl.id' ] ],
					]
				);
				$conditions[ 'fdef.nid' ] = $order;
				$order = 'baseData.' . $dir;
			}
			unset ( $conditions[ 'sprl.pid' ] );  // not suitable for search

			try {
				$results = Factory::Db()
					->dselect( $oPrefix . 'id', $table, $conditions, $order )
					->loadResultArray();
			}
			catch ( Sobi\Error\Exception $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
			}
		}
		else {
			$table = Factory::Db()->join(
				[
					[ 'table' => 'spdb_field', 'as' => 'fdef', 'key' => 'fid' ],
					[ 'table' => 'spdb_field_data', 'as' => 'fdata', 'key' => 'fid' ],
					[ 'table' => 'spdb_object', 'as' => 'spo', 'key' => [ 'fdata.sid', 'spo.id' ] ],
				]
			);
			$conditions[ 'fdef.nid' ] = $order;

			try {
				$results = Factory::Db()
					->dselect( $oPrefix . 'id', $table, $conditions, 'baseData.' . $dir )
					->loadResultArray();
			}
			catch ( Sobi\Error\Exception $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
			}
		}

		/* attach those to the result which can't be sorted by selected ordering */

		return array_unique( array_merge( $results, $ids ) );
	}
}
