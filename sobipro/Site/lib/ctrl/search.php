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
 * as published by the Free Software Foundation, and under the additional terms according to section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 29-March-2010 by Radek Suski
 * @modified 17 December 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadController( 'section' );

use Sobi\C;
use Sobi\Input\Cookie;
use Sobi\Input\Input;
use Sobi\Lib\Factory;
use Sobi\Utils\StringUtils;

/**
 * Class SPSearchCtrl
 */
class SPSearchCtrl extends SPSectionCtrl
{
	/*** @var string */
	protected $_type = 'search';
	/*** @var string */
	protected $_defTask = 'view';
	/*** @var array */
	protected $_request = [];
	/*** @var array */
	protected $_fields = [];
	/*** @var array */
	protected $_results = [];
	/*** @var array */
	protected $_resultsByPriority = [];
	/*** @var int */
	protected $_resultsCount = 0;
	/*** @var array */
	protected $_categoriesResults = [];
	/*** @var Factory:Db() */
	protected $_db = [];
	/*** @var bool */
	protected $_narrowing = true;

	/**
	 * SPSearchCtrl constructor.
	 */
	public function __construct()
	{
		$this->_db = Factory::Db();
		parent::__construct();
		/** because we have always the same URL - disable Joomla! cache */
		SPFactory::cache()->setJoomlaCaching( false );
	}

	/**
	 * @return bool
	 * @throws SPException
	 * @throws \Sobi\Error\Exception|\DOMException
	 */
	public function execute()
	{
		if ( !Sobi::Can( 'section.search' ) ) {
			if ( $this->_task != 'suggest' ) {
				$section = SPFactory::Section( Sobi::Section() );
				if ( Sobi::Cfg( 'redirects.section_search_enabled' ) && strlen( $section->get( 'redirectEntryAddUrl' ) ) ) {
					$this->escape( $section->get( 'redirectSearchUrl' ), SPLang::e( Sobi::Cfg( 'redirects.section_search_msg', 'UNAUTHORIZED_ACCESS' ) ), Sobi::Cfg( 'redirects.section_search_msgtype', C::ERROR_MSG ) );
				}
				else {
					Sobi::Error( $this->name(), SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ), C::ERROR, 403, __LINE__, __FILE__ );
				}
			}
			else {
				exit;
			}
		}

		//SPLoader::loadClass( 'env.cookie' );
		SPLoader::loadClass( 'env.browser' );
		Input::Set( 'task', $this->_type . '.' . $this->_task );

		$retval = true;
		switch ( $this->_task ) {
			case 'view':
				$this->form( true );
				break;
			case 'results':
				$this->form( false );
				break;
			case 'search':
				$this->search();
				break;
			case 'suggest':
				$this->suggest();
				break;
			default:
				if ( !parent::execute() ) {
					Sobi::Error( $this->name(), SPLang::e( 'SUCH_TASK_NOT_FOUND', Input::Task() ), C::NOTICE, 404, __LINE__, __FILE__ );
				}
				$retval = false;
				break;
		}

		return $retval;
	}

	/**
	 * Suggest-list for search.
	 * AJAX
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function suggest()
	{
		$this->_request[ 'search_for' ] = str_replace( '*', '%', Input::String( 'term' ) );
		$fieldNid = Input::String( 'fid' );
		$fieldNids = [];
		if ( strlen( $fieldNid ) ) {
			$fieldNids = SPFactory::config()->structuralData( $fieldNid, true );
		}
		$results = [];
		if ( strlen( $this->_request[ 'search_for' ] ) >= Sobi::Cfg( 'search.suggest_min_chars', 1 ) ) {
			Sobi::Trigger( 'OnSuggest', 'Search', [ &$this->_request[ 'search_for' ] ] );
			$this->_fields = $this->loadFields();
			$search = str_replace( '.', '\.', $this->_request[ 'search_for' ] );
			if ( count( $this->_fields ) ) {
				foreach ( $this->_fields as $field ) {
					if ( !$field->get( 'suggesting' ) || ( count( $fieldNids ) && !in_array( $field->get( 'nid' ), $fieldNids ) ) ) {
						continue;
					}
					else {
						$suggests = $field->searchSuggest( $search, Sobi::Section(), (bool) Sobi::Cfg( 'search.suggest_start_with', false ) );
						if ( is_array( $suggests ) && count( $suggests ) ) {
							$results = array_merge( $results, $suggests );
						}
					}
				}
			}
		}
		$results = array_unique( $results );
		if ( count( $results ) ) {
			foreach ( $results as $key => $value ) {
				$value = strip_tags( $value );
				if ( Sobi::Cfg( 'search.suggest_split_words', true ) && strstr( $value, ' ' ) ) {
					$value = explode( ' ', $value );
					$value = $value[ 0 ];
				}
				$results[ $key ] = $value;
			}
		}
//		usort( $results, [ 'self', 'sortByLen' ] );
		if ( class_exists( 'Collator' ) ) {
			$collator = new Collator( Sobi::Cfg( 'language' ) );
			$collator->sort( $results );
		}
		else {
			natcasesort( $results );
		}

		Sobi::Trigger( 'AfterSuggest', 'Search', [ &$results ] );
		if ( count( $results ) ) {
			foreach ( $results as $i => $term ) {
				$results[ $i ] = StringUtils::Clean( $term );
			}
		}
		SPFactory::mainframe()
			->cleanBuffer()
			->customHeader();
		echo json_encode( (array) array_values( $results ) );
		exit();
	}

	/**
	 * @param $from
	 * @param $to
	 *
	 * @return int
	 */
	protected function sortByLen( $from, $to )
	{
		return strlen( $to ) - strlen( $from );
	}

	/**
	 * @param $nid
	 * @param $extended
	 */
	protected function checkFilterField( $nid, &$extended )
	{
		foreach ( $this->_fields as $field ) {
			if ( $field->get( 'nid' ) == $nid ) {
				if ( property_exists( $field->get( '_type' ), 'FILTER_FIELD' ) ) {
					break;
				}
				else {
					$extended = true;
				}
			}
		}
	}

	/**
	 * Main search method.
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function search()
	{
		$ssid = $this->getSsid();
		$mainOrder = Sobi::Cfg( 'search.search_ordering', 'priority' );

		$this->_request = Input::Search( 'field_' );

		/* if we have automatic wildcard search */
		if ( Sobi::Cfg( 'search.wildcard-search', false ) ) {
			$this->_request[ 'search_for' ] = str_replace(
				[ '*', Sobi::Txt( 'SH.SEARCH_FOR_BOX' ), C::ES ],
				'%', Input::String( 'sp_search_for' ) );
			if ( !$this->_request[ 'search_for' ] ) {
				$this->_request[ 'search_for' ] = '%';
			}
		}
		else {
			$this->_request[ 'search_for' ] = str_replace(
				[ '*', Sobi::Txt( 'SH.SEARCH_FOR_BOX' ) ],
				[ '%', C::ES ],
				Input::String( 'sp_search_for' ) );
		}

		$this->_request[ 'phrase' ] = Input::String( 'spsearchphrase', 'request', Sobi::Cfg( 'search.form_searchphrase_def', 'all' ) );
		$this->_request[ 'phrase' ] = strlen( $this->_request[ 'phrase' ] ) ? $this->_request[ 'phrase' ] : Sobi::Cfg( 'search.form_searchphrase_def', 'all' );

		$this->_fields = $this->loadFields();

		/* clean request */
		$extended = false;
		if ( count( $this->_request ) ) {
			foreach ( $this->_request as $index => $value ) {
				if ( is_array( $value ) ) {
					foreach ( $value as $index2 => $value2 ) {
						$value[ $index2 ] = htmlspecialchars_decode( $value2, ENT_QUOTES );
					}
					$this->_request[ $index ] = SPRequest::cleanArray( $value, true );
					if ( count( $this->_request[ $index ] ) ) {    /* if not empty */
						if ( strpos( $index, 'field_' ) === 0 ) {
							$this->checkFilterField( $index, $extended );
						}
					}
					else {
						unset( $this->_request[ $index ] );
					}
				}
				else {
					$this->_request[ $index ] = $this->_db->escape( $value );
					if ( ( strpos( $index, 'field_' ) === 0 ) && ( $value != C::ES ) ) {
						$this->checkFilterField( $index, $extended );
					}
					elseif ( $value == C::ES && $index != 'search_for' ) {
						unset( $this->_request[ $index ] );
					}
				}
			}
		}

		// if no extended search and no input in the keyword field, then add an asterix in the keyword field
//		if ( !$extended ) {
//			$this->_request[ 'search_for' ] = str_replace( Sobi::Txt( 'SH.SEARCH_FOR_BOX' ), '%', $this->_request[ 'search_for' ] );
//			if ( empty( $this->_request[ 'search_for' ] ) ) {
//				$this->_request[ 'search_for' ] = '%';
//			}
//		}

		/* sort fields by priority */
		if ( $mainOrder == 'priority' ) {
			usort( $this->_fields, function ( $obj, $to ) {
				return ( $obj->get( 'priority' ) == $to->get( 'priority' ) ) ? 0 : ( ( $obj->get( 'priority' ) < $to->get( 'priority' ) ) ? -1 : 1 );
			} );
		}

		$searchForString = false;
		Sobi::Trigger( 'OnRequest', 'Search', [ &$this->_request ] );
		$maxLimit = Sobi::Cfg( 'search.max_entries', 20000 );    // maximum entries to sort

		$this->_resultsByPriority = [];
		if ( $mainOrder == 'priority' ) {
			for ( $index = 1; $index < 11; $index++ ) {
				$this->_resultsByPriority[ $index ] = [];
			}
		}

		// if the visitor wasn't on the search page first
		if ( !$ssid || Input::Int( 'reset', 'request', 0 ) ) {
			/**
			 * Tue, Mar 12, 2019 08:47:48
			 * If we are already in the search and there was no cookie,
			 * we shouldn't set a new one because it may mean that we can set cookie within PHP
			 * but the server won't send it through.
			 * So if we are here and there is no cookie the only logical explanation is
			 * that it simply wasn't able to send it
			 */
//			$this->session( $ssid, false );

			/* 16 December 2024, Sigrid Suski
			Do not understand that. If no cookie is set, we should try to set it anyway. */
			$this->session( $ssid );
		}

		/* First, the basic search … */
		/* if we have a string to search */
		if ( strlen( $this->_request[ 'search_for' ] ) && $this->_request[ 'search_for' ] != Sobi::Txt( 'SH.SEARCH_FOR_BOX' ) ) {
			$searchForString = true;
			$this->_narrowing = true;
			switch ( $this->_request[ 'phrase' ] ) {
				case 'exact':
					$this->searchPhrase();
					break;
				default:
				case 'all':
				case 'any':
					$this->searchWords( ( $this->_request[ 'phrase' ] == 'all' ) );
					break;
			}
			if ( !is_null( $this->_results ) ) {
				$this->_results = array_unique( $this->_results );
			}
		}
		Sobi::Trigger( 'AfterBasic', 'Search', [ &$this->_results, &$this->_resultsByPriority ] );

		/* ... now the extended search. Check which data we've received */
		if ( $extended && count( $this->_fields ) ) {
			$results = [];
			foreach ( $this->_fields as $field ) {
				if ( isset( $this->_request[ $field->get( 'nid' ) ] ) && ( $this->_request[ $field->get( 'nid' ) ] != null ) ) {
					$this->_narrowing = true;
					$fr = $field->searchData( $this->_request[ $field->get( 'nid' ) ], Sobi::Section(), $this->_request[ 'phrase' ] );

					if ( $mainOrder == 'priority' ) {
						$priority = $field->get( 'priority' );
						if ( is_array( $fr ) ) {
							$this->_resultsByPriority[ $priority ] = array_merge( $this->_resultsByPriority[ $priority ], $fr );
						}
					}
					/* if we didn't get any results before this array contains the results */
					if ( !count( $results ) ) {
						$results = $fr;
					}
					/* otherwise intersect these two arrays */
					else {
						if ( count( $fr ) ) {
							$results = array_intersect( $results, $fr );
						}
					}
				}
			}
			/* if we had also a string to search we have to get the intersection */
			if ( $searchForString ) {
				$this->_results = array_intersect( $this->_results, $results );
			}
			/* otherwise THESE are the results */
			else {
				$this->_results = $results;
			}
		}

		/** @since 1.1 - a method to narrow down the search results */
		if ( count( $this->_fields ) ) {
			/* If we have any results already, then we are limiting results down. If we don't have results, but we were already searching, then skip because there is nothing to narrow down. If we don't have results, but we weren't searching for anything else, then we are narrowing down everything */
			if ( count( $this->_results ) || !$this->_narrowing ) {
				foreach ( $this->_fields as &$field ) {
					$request = isset( $this->_request[ $field->get( 'nid' ) ] ) ? $this->_request[ $field->get( 'nid' ) ] : null;
					if ( $request ) {
						$field->searchNarrowResults( $request, $this->_results, $this->_resultsByPriority );
					}
				}
			}
		}
		$this->_request[ 'search_for' ] = str_replace( '%', '*', $this->_request[ 'search_for' ] );
		Sobi::Trigger( 'AfterExtended', 'Search', [ &$this->_results, &$this->_resultsByPriority ] );


		if ( count( $this->_results ) > $maxLimit ) {
			SPFactory::message()->error( Sobi::Txt( 'SH.SEARCH_TOO_MANY_RESULTS', count( $this->_results ), $maxLimit ), false );
			$this->_results = array_slice( $this->_results, 0, $maxLimit );
		}
		$order = $mainOrder;
		if ( $mainOrder != 'priority' ) {
			$order = 'random';  // as we do the sorting thing again when showing the form, we use here the simplest search if not priority search
		}
		$this->sortResults( $order );

		$req = ( is_array( $this->_request ) && count( $this->_request ) ) ? SPConfig::serialize( $this->_request ) : C::ES;
		$res = ( is_array( $this->_results ) && count( $this->_results ) ) ? implode( ',', $this->_results ) : C::ES;
		$cre = ( is_array( $this->_categoriesResults ) && count( $this->_categoriesResults ) ) ? implode( ',', $this->_categoriesResults ) : C::ES;
		/* determine the search parameters */
		$attr = [
			'entriesResults' => [ 'results' => $res, 'resultsByPriority' => $this->_resultsByPriority ],
			'catsResults'    => $cre,
			'uid'            => Sobi::My( 'id' ),
			'browserData'    => SPConfig::serialize( SPBrowser::getInstance() ),
			'eorder'         => $mainOrder,
		];
		if ( strlen( $req ) ) {
			$attr[ 'requestData' ] = $req;
		}

		/* finally, save the search result into the database */
		try {
			Sobi::Trigger( 'OnSave', 'Search', [ &$attr, &$ssid ] );
			$this->verify( $attr[ 'entriesResults' ][ 'results' ] );
			unset( $attr[ 'eorder' ] );
			$this->_db->update( 'spdb_search', $attr, [ 'ssid' => $ssid ] );    // update serializes the array
		}
		catch ( SPException $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'CANNOT_CREATE_SESSION_DB_ERR', $x->getMessage() ), C::ERROR, 500, __LINE__, __FILE__ );
		}
		$url = [ 'task' => 'search.results', 'sid' => Sobi::Section() ];
		// For Peter's Components Anywhere extension and other
		$params = Sobi::Cfg( 'search.params_to_pass' );
		if ( count( $params ) ) {
			foreach ( $params as $param ) {
				$val = Input::Raw( $param );
				if ( $val ) {
					$url[ $param ] = Input::Raw( $param );
				}
			}
		}

		/* if we cannot transfer the search id in cookie */
		if ( !$this->getCookie() ) {
			$url[ 'ssid' ] = $ssid;
		}

		if ( Sobi::Cfg( 'cache.unique_search_url' ) ) {
			$url[ 't' ] = microtime( true );
		}
		Sobi::Redirect( Sobi::Url( $url ) );
	}

	/**
	 * @param $fields
	 * @param $order
	 * @param $dir
	 * @param $ids
	 *
	 * @return array
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function sortByField( $fields, $order, $dir, $ids ): array
	{
		static $field = null;
		$oPrefix = 'spo.';
		$conditions = $results = [];
		$conditions[ 'spo.oType' ] = 'entry';
		$conditions[ 'spo.id' ] = $ids; /* limit to the search results */

		if ( !$field ) {
			if ( count( $fields ) ) {
				foreach ( $fields as $f ) {
					if ( $f->get( 'nid' ) == $order ) {
						//$field = $f;
						$field = SPLoader::loadClass( 'opt.fields.' . $f->get( 'fieldType' ) );
						break;
					}
				}
			}

			if ( !$field ) {
				try {
					$fType = $this->_db
						->select( 'fieldType', 'spdb_field', [ 'nid' => $order, 'section' => Sobi::Section(), 'adminField>' => -1 ] )
						->loadResult();
				}
				catch ( SPException $x ) {
					Sobi::Error( $this->name(), SPLang::e( 'CANNOT_DETERMINE_FIELD_TYPE', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
				}
				if ( $fType ) {
					$field = SPLoader::loadClass( 'opt.fields.' . $fType );
				}
			}
		}

		/* check if the field sets the parameters by itself (= $specificMethod=true)
		  $specificMethod = false is deprecated (if field has the method, it has to set the parameters) */
		if ( $field && method_exists( $field, 'sortBy' ) ) {
			$table = C::ES;
			$specificMethod = call_user_func_array( [ $field, 'sortBy' ], [ &$table, &$conditions, &$oPrefix, &$order, &$dir ] );
			if ( !$specificMethod ) {
				$table = $this->_db->join(
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
				$results = $this->_db
					->dselect( $oPrefix . 'id', $table, $conditions, $order )
					->loadResultArray();
			}
			catch ( Sobi\Error\Exception $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
			}
		}
		else {
			$table = $this->_db->join(
				[
					[ 'table' => 'spdb_field', 'as' => 'fdef', 'key' => 'fid' ],
					[ 'table' => 'spdb_field_data', 'as' => 'fdata', 'key' => 'fid' ],
					[ 'table' => 'spdb_object', 'as' => 'spo', 'key' => [ 'fdata.sid', 'spo.id' ] ],
				]
			);
			$conditions[ 'fdef.nid' ] = $order;

			try {
				$results = $this->_db
					->dselect( $oPrefix . 'id', $table, $conditions, 'baseData.' . $dir )
					->loadResultArray();
			}
			catch ( Sobi\Error\Exception $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
			}
		}

		/* attach those which can't be sorted by selected ordering */

		return array_unique( array_merge( $results, $ids ) );
	}

	/**
	 * Apply main and second search ordering
	 *
	 * @param $mainOrder
	 * @param array $fields
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function sortResults( $mainOrder, $fields = [] )
	{
		if ( count( $this->_results ) > 0 ) {
			if ( $mainOrder == 'priority' ) {
				$sOrder = Sobi::Cfg( 'search.entries_ordering', 'disabled' );
				if ( $sOrder != 'disabled' ) {
					// second ordering is set

					// leave only unique results which are available in the overall results array $this->_results in the priority array $this->_resultsByPriority
					foreach ( $this->_resultsByPriority as $prio => $ids ) {
						$this->_resultsByPriority[ $prio ] = array_unique( $ids );
						foreach ( $ids as $index => $sid ) {
							if ( !in_array( $sid, $this->_results ) ) {
								unset( $this->_resultsByPriority[ $prio ][ $index ] );
							}
						}
					}
					// remove duplicates from the priority array $this->_resultsByPriority
					foreach ( $this->_resultsByPriority as $prio => $ids ) {
						foreach ( $ids as $id ) {
							foreach ( $this->_resultsByPriority as $p => $sids ) {
								if ( $p <= $prio ) {
									continue;
								}
								foreach ( $sids as $index => $sid ) {
									if ( $sid == $id ) {
										unset( $this->_resultsByPriority[ $p ][ $index ] );
									}
								}
							}
						}
					}
					// remove duplicates (not necessary)
					foreach ( $this->_resultsByPriority as $prio => $ids ) {
						if ( is_array( $ids ) && count( $ids ) ) {
							$this->_resultsByPriority[ $prio ] = array_unique( $ids );
						}
					}

					// Specific sorting orders
					$sDir = 'asc';
					if ( strpos( $sOrder, '.' ) !== false ) {
						$sOr = explode( '.', $sOrder );
						$sOrder = array_shift( $sOr );
						$sDir = implode( '.', $sOr );
					}

					$this->_results = [];
					foreach ( $this->_resultsByPriority as $prio => $ids ) {
						if ( is_array( $ids ) && count( $ids ) ) {
							if ( $sOrder == 'random' ) {
								shuffle( $this->_resultsByPriority[ $prio ] );
							}
							elseif ( $sOrder == 'counter' ) {
								try {
									$this->_resultsByPriority[ $prio ] = $this->_db
										->select( 'sid', 'spdb_counter', [ 'sid' => $ids ], $sOrder . ' ' . $sDir )
										->loadResultArray();
								}
								catch ( Sobi\Error\Exception $x ) {
									Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
								}
							}

							/* sorting by field */
							elseif ( strstr( $sOrder, 'field_' ) ) {
								$this->_resultsByPriority[ $prio ] = $this->sortByField( $fields, $sOrder, $sDir, $ids );
							}
							else {

								try {
									$this->_resultsByPriority[ $prio ] = $this->_db
										->select( 'id', 'spdb_object', [ 'id' => $ids ], $sOrder . ' ' . $sDir )
										->loadResultArray();
								}
								catch ( Sobi\Error\Exception $x ) {
									Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
								}
							}

							$this->_results = array_merge( $this->_results, $this->_resultsByPriority[ $prio ] );
						}
					}
				}

				// no second order
				// 18.9.2019
				// the array $this->_results contains all results. If searching for e.g. 2 different terms, the array contains first all results for the first term and then all results for the second term.
				// This is wrong. It should contain the results ordered by priority. The priority is in the array $this->_resultsByPriority. So we need the content of $this->_results ordered by
				// $this->_resultsByPriority.
				else {
					$results = [];
					foreach ( $this->_resultsByPriority as $prio => $ids ) {
						if ( is_array( $ids ) && count( $ids ) ) {
							$results = array_merge( $results, $this->_resultsByPriority[ $prio ] );
						}
					}
					$results = array_unique( $results );
					$erg = array_intersect( $results, $this->_results ); // only results which are also in the overall results array $this->_results
					$this->_results = $erg;
					//$this->_resultsByPriority = [];
				}
			}

			// main search ordering is not priority
			else {
				$sDir = 'asc';
				if ( strpos( $mainOrder, '.' ) !== false ) {
					$sOr = explode( '.', $mainOrder );
					$mainOrder = array_shift( $sOr );
					$sDir = implode( '.', $sOr );
				}
				if ( $mainOrder == 'random' ) {
					shuffle( $this->_results );
				}
				elseif ( $mainOrder == 'counter' ) {
					try {
						$this->_results = $this->_db
							->select( 'sid', 'spdb_counter', [ 'sid' => $this->_results ], $mainOrder . ' ' . $sDir )
							->loadResultArray();
					}
					catch ( Sobi\Error\Exception $x ) {
						Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
					}

				}
				/* sorting by field */
				elseif ( strstr( $mainOrder, 'field_' ) ) {
					$this->_results = $this->sortByField( $fields, $mainOrder, $sDir, $this->_results );
				}
				else {

					try {
						$this->_results = $this->_db
							->select( 'id', 'spdb_object', [ 'id' => $this->_results ], $mainOrder . ' ' . $sDir )
							->loadResultArray();

					}
					catch ( Sobi\Error\Exception $x ) {
						Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
					}
				}
			}
		}
	}

	/**
	 * @param $entries
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function verify( &$entries )
	{
		if ( $entries ) {
			$entries = explode( ',', $entries );
			$conditions = [];
			if ( Sobi::My( 'id' ) ) {
				$this->userPermissionsQuery( $conditions );
			}
			else {
				$conditions = [ 'state' => '1', '@VALID' => $this->_db->valid( 'validUntil', 'validSince' ) ];
			}
			$conditions[ 'id' ] = $entries;
			$conditions[ 'oType' ] = 'entry';
			try {
				$results = $this->_db
					->select( 'id', 'spdb_object', $conditions )
					->loadResultArray();
				foreach ( $entries as $index => $sid ) {
					if ( !in_array( $sid, $results ) ) {
						unset( $entries[ $index ] );
					}
				}
			}
			catch ( Sobi\Error\Exception $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::ERROR, 500, __LINE__, __FILE__ );
			}
			Sobi::Trigger( 'OnVerify', 'Search', [ &$entries ] );
		}
	}

	/**
	 * @return void
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function searchPhrase()
	{
		/* @TODO categories */
		$search = str_replace( '.', '\.', $this->_request[ 'search_for' ] );
//		$this->_results = $this->travelFields( "REGEXP:[[:<:]]{$search}[[:>:]]", true );
		$this->_results = $this->travelFields( "REGEXP:\b{$search}\b", true );
	}

	/**
	 * @param $all
	 *
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function searchWords( $all )
	{
		/* @TODO categories */
		$matches = [];

		/* extrapolate single words */
		/*   '/((\b[^\s]+\b)((?<=\.\w).)?)/iu' scheint besser zu sein; nope is not!! (21 June 24)  */
		$worked = preg_match_all( Sobi::Cfg( 'search.word_filter', '/\p{L}+|\d+|%/iu' ), $this->_request[ 'search_for' ], $matches );
		if ( $worked && count( $matches ) && isset( $matches[ 0 ] ) ) {
			$wordResults = [];
			$results = null;
			/* search all fields for this word */
			foreach ( $matches[ 0 ] as $word ) {
				$wordResults[ $word ] = $this->travelFields( $word );
			}
			if ( count( $wordResults ) ) {
				foreach ( $wordResults as $wordResult ) {
					if ( is_null( $results ) ) {
						$results = $wordResult;
					}
					else {
						if ( $all ) {
							if ( is_array( $wordResult ) ) {
								$results = array_intersect( $results, $wordResult );
							}
						}
						else {
							if ( is_array( $wordResult ) ) {
								$results = array_merge( $results, $wordResult );
							}
						}
					}
				}
			}
			$this->_results = $results;
		}
	}

	/**
	 * @param $word
	 * @param bool $regex
	 *
	 * @return array
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function travelFields( $word, bool $regex = false ): array
	{
		$results = [];
		if ( count( $this->_fields ) ) {
			foreach ( $this->_fields as $field ) {
				$priority = $field->get( 'priority' );
				$fr = $field->searchString( $word, Sobi::Section(), $regex );
				if ( is_array( $fr ) && count( $fr ) ) {
					$results = array_unique( array_merge( $results, $fr ) );
					if ( count( $this->_resultsByPriority ) && is_array( $this->_resultsByPriority[ $priority ] ) ) {
						$this->_resultsByPriority[ $priority ] = array_unique( array_merge( $this->_resultsByPriority[ $priority ], $fr ) );
					}
				}
			}
		}

		return $results;
	}

	/**
	 * Search results form.
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception|\DOMException
	 */
	protected function form( $reset )
	{
		$ssid = 0;
		/* determine template package */
		$tplPackage = Sobi::Cfg( 'section.template', SPC::DEFAULT_TEMPLATE );

		/* load template config */
		$this->template();
		$this->tplCfg( $tplPackage, 'search' );
		if ( $this->template == 'results' ) {
			$this->template = 'view';
		}
		if ( !$this->_model ) {
			$this->setModel( 'section' );
			$this->_model->init( Sobi::Section() );
		}

		/* handle meta data */
		if ( Sobi::Cfg( 'meta.always_add_section' ) ) {
			SPFactory::header()->objMeta( $this->_model );
		}
		$section = SPFactory::Section( Sobi::Section() );
		SPFactory::header()->addKeyword( $section->get( 'sfMetaKeys' ) );

		$desc = $section->get( 'sfMetaDesc' );
		if ( $desc ) {
			$separator = Sobi::Cfg( 'meta.separator', '.' );
			$desc .= $separator;
			SPFactory::header()->addDescription( $desc );
		}

		if ( Sobi::Cfg( 'search.highlight-search' ) ) {
			SPFactory::header()->addJsFile( [ 'Jquery.jquery-highlight', 'search-highlight' ] );
		}

		Sobi::Trigger( 'OnFormStart', 'Search' );
		SPLoader::loadClass( 'mlo.input' );

		if ( !Sobi::Cfg( 'browser.no_title', false ) ) {
//			SPFactory::mainframe()->setTitle( Sobi::Txt( 'SH.TITLE', [ 'section' => $this->_model->get( 'name' ) ] ) );
			// as Sobi does not recognize Joomla search menu links, we cannot remove it as the menu link and the link in SobiPro's top menu will be treated as different links
//			$title = [];
//			if ( !( Sobi::Cfg( 'browser.add_title', false ) ) ) {
			$title = Sobi::Txt( 'SH.TITLE_SHORT' );
//			}
			SPFactory::mainframe()->setTitle( $title );
		}
		/** @var SPSearchView $view */
		$view = SPFactory::View( 'search' );

		/* if we cannot transfer the search id in cookie */
		/* to be sure that it works, transfer the $ssid into a general request variable and a section specific request variable :) */
		if ( !$this->session( $ssid ) ) {
			$view->addHidden( $ssid, 'ssid_' . Sobi::Section() );
			$view->addHidden( $ssid, 'ssid' );
		}

		$eOrder = Sobi::Cfg( 'search.search_ordering', 'priority' );
		if ( $reset ) {
			$this->setOrdering( 'search', $eOrder );   // overwrite the template ordering on search reload to the default main ordering
		}
		$orderFields = null;
		if ( $this->_task == 'results' && $ssid ) {
			if ( $eOrder != 'priority'
				&& ( ( array_key_exists( 'searchorderlist', $this->_tCfg[ 'general' ] ) && $this->_tCfg[ 'general' ][ 'searchorderlist' ] == 1 )
					|| ( array_key_exists( 'orderingfromrequest', $this->_tCfg[ 'general' ] ) && $this->_tCfg[ 'general' ][ 'orderingfromrequest' ] == 1 )
				)
			) {
				$eOrder = $this->parseOrdering( 'search', 'eorder', Sobi::Cfg( 'search.search_ordering', 'priority' ) );
				$orderFields = $this->orderFields( 'search' );
			}
			/* add pathway */
			SPFactory::mainframe()->addToPathway( Sobi::Txt( 'SH.PATH_TITLE_RESULT' ), Sobi::Url( 'current' ) );

			/* get limits - if defined in template config - otherwise from the section config */
			$limit = $eLimit = (int) $this->tKey( $this->template, 'entries_limit', Sobi::Cfg( 'search.entries_limit', Sobi::Cfg( 'list.entries_limit', 2 ) ) );
			$eInLine = (int) $this->tKey( $this->template, 'entries_in_line', Sobi::Cfg( 'search.entries_in_line', Sobi::Cfg( 'list.entries_in_line', 2 ) ) );

			/* get the site to display */
			$site = Input::Int( 'site', 'request', 1 );
			$start = $eLimStart = ( ( $site - 1 ) * $eLimit );

			// if only re-order (ordering = 1) and ajax pagination, re-order all visible entries (instead of shorten them again to $elimit)
			$page = $this->tKey( $this->template, 'pagination', 'std' );
			if ( $page == 'ajax' && Input::String( 'ordering', 'request', 0 ) == 1 ) {
				$start = 0;
				$limit = $site * $eLimit;
			}
			$view->assign( $eLimit, '$eLimit' )
				->assign( $eLimStart, '$eLimStart' )
				->assign( $eInLine, '$eInLine' );

			// handle the results
			$entries = $this->getResults( $ssid, $this->template, $eOrder, $start, $limit );    // results of the site
			$count = count( $this->_results );  // overall results

			$view->assign( $count, '$eCount' )
				->assign( $this->_resultsByPriority, 'priorities' )
				->assign( $entries, 'entries' );

			/* create page navigation */
			$pnc = SPLoader::loadClass( 'helpers.pagenav_' . $this->tKey( $this->template, 'template_type', 'xslt' ) );
			$url = [ 'task' => 'search.results', 'sid' => Input::Sid() ];
			if ( !$this->getCookie() ) {
				$url[ 'ssid' ] = $ssid;
			}
			/* @var SPPageNavXSLT $pn */
			$pn = new $pnc( $eLimit, $this->_resultsCount, $site, $url );
			$nav = $pn->get();
			$view->assign( $nav, 'navigation' );
			/**
			 * this is the special case:
			 * no matter what task we currently have - if someone called this, we need the data for the V-Card
			 * So we have to trigger all these plugins we need and therefore also fake the task
			 */
			$task = 'list.custom';
			SPFactory::registry()->set( 'task', $task );
		}
		else {
			/* add pathway */
			SPFactory::mainframe()->addToPathway( Sobi::Txt( 'SH.PATH_TITLE' ), Sobi::Url( 'current' ) );
			$eLimit = -1;
			$view->assign( $eLimit, '$eCount' );
		}
		/* load all fields */
		if ( !count( $this->_fields ) ) {
			$this->_fields = $this->loadFields();
		}
		if ( isset( $this->_request[ 'search_for' ] ) ) {
			$view
				->assign( $this->_request[ 'search_for' ], 'search_for' )
				->assign( $this->_request[ 'phrase' ], 'search_phrase' );
		}
		$visitor = SPFactory::user();
		$sid = Sobi::Section();

		$view
			->assign( $this->_fields, 'fields' )
			->assign( $visitor, 'visitor' )
			->assign( $this->_task, 'task' )
			->assign( $eOrder, 'orderings' )
			->assign( $orderFields, 'orderFields' )
			->addHidden( $sid, 'sid' )
			->addHidden( 'search.search', 'task' )
			->setConfig( $this->_tCfg, $this->template )
			->setTemplate( $tplPackage . '.' . $this->templateType . '.' . $this->template );

		Sobi::Trigger( 'OnCreateView', 'Search', [ &$view ] );

		$view->display();
	}

	/**
	 * @param string $ssid
	 * @param string $template
	 * @param string $eOrder
	 * @param int $start
	 * @param int $limit
	 *
	 * @return array
	 * @throws \Sobi\Error\Exception|\SPException
	 * @throws \Exception
	 */
	protected function getResults( string $ssid, string $template, string $eOrder, int $start, int $limit ): array
	{
		$results = [];
		/* case some plugin overwrites this method */
		Sobi::Trigger( 'GetResults', 'Search', [ &$results, &$ssid, &$template ] );
		if ( count( $results ) ) {  /* if someone else handled the results */
			return $results;
		}

		try {
			$r = $this->_db
				->select( [ 'entriesResults', 'requestData' ], 'spdb_search', [ 'ssid' => $ssid ] )
				->loadAssocList();
			$this->_request = SPConfig::unserialize( $r[ 0 ][ 'requestData' ] );
			if ( !$this->_fields ) {
				$this->_fields = $this->loadFields();
			}
			if ( $r[ 0 ] && isset( $r[ 0 ][ 'entriesResults' ] ) && strlen( $r[ 0 ][ 'entriesResults' ] ) ) {
				$store = SPConfig::unserialize( $r[ 0 ][ 'entriesResults' ] );
				if ( $store[ 'results' ] && is_array( $this->_fields ) && count( $this->_fields ) ) {
					/* ... if extended search, trigger sort function of fields.  */
					foreach ( $this->_fields as $field ) {
						// need to call it even if it is empty to give the field the chance to clear its data
						try {
							if ( isset( $this->_request[ $field->get( 'nid' ) ] ) && ( $this->_request[ $field->get( 'nid' ) ] != null ) ) {
								$request = $this->_request[ $field->get( 'nid' ) ];
							}
							else {
								$request = C::ES;
							}
							$field->sortData( $request, $ssid );// dynamic registration (fields)
						}
						catch ( SPException $x ) {
						}
					}
					$this->_results = array_unique( $store[ 'results' ] );
					$this->_resultsByPriority = $store[ 'resultsByPriority' ];
					$this->sortResults( $eOrder, $this->_fields );

					$this->_results = implode( ',', $this->_results );
					$attr = [
						'entriesResults' => [ 'results' => $this->_results, 'resultsByPriority' => $this->_resultsByPriority ],
						'catsResults'    => $this->_categoriesResults,
						'uid'            => Sobi::My( 'id' ),
						'browserData'    => SPConfig::serialize( SPBrowser::getInstance() ),
						'eorder'         => $eOrder,
					];
					if ( $r[ 0 ][ 'requestData' ] ) {
						$attr[ 'requestData' ] = $r[ 0 ][ 'requestData' ];
					}

					Sobi::Trigger( 'OnSort', 'Search', [ &$attr, &$ssid ] );
					$this->_results = ( empty( $attr[ 'entriesResults' ][ 'results' ] ) ) ? [] : explode( ',', $attr[ 'entriesResults' ][ 'results' ] );

					$searchLimit = Sobi::Cfg( 'search.result_limit', 1000 );
					if ( count( $this->_results ) > $searchLimit ) {
						SPFactory::message()->error( Sobi::Txt( 'SH.SEARCH_TOO_MANY_RESULTS', count( $this->_results ), $searchLimit ), false );
//			$this->_resultsByPriority = [];
						$this->_results = array_slice( $this->_results, 0, $searchLimit );
					}
				}
				$this->_resultsCount = count( $this->_results );
			}
			SPFactory::registry()->set( 'requestcache', $this->_request );

			if ( count( $this->_results ) ) {
				$r = array_slice( $this->_results, $start, $limit );
				/* so we have a results */
				foreach ( $r as $index => $sid ) {
					$results[ $index ] = ( int ) $sid;
					//$results[ $index ] = new $eClass();
					//$results[ $index ]->init( $sid );
				}
			}
		}
		catch ( SPException $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'CANNOT_GET_SESSION_DB_ERR', $x->getMessage() ), C::ERROR, 500, __LINE__, __FILE__ );
		}

		Sobi::SetUserData( 'currently-displayed-entries', $results );

		return $results;
	}

	/**
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function loadFields(): array
	{
		$fields = [];
		$fmod = SPLoader::loadModel( 'field' );
		/* get fields */
		try {
			$fields = $this->_db
				->select( '*', 'spdb_field', [ 'section' => Sobi::Section(), 'inSearch' => 1, 'enabled' => 1, 'adminField>' => -1 ], 'position' )
				->loadObjectList();
		}
		catch ( SPException $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'CANNOT_GET_FIELDS_DB_ERR', $x->getMessage() ), C::ERROR, 500, __LINE__, __FILE__ );
		}
		if ( count( $fields ) ) {
			foreach ( $fields as $index => $f ) {
				$field = new $fmod();
				$field->extend( $f );
				if ( is_array( $this->_request ) && count( $this->_request ) && isset( $this->_request[ $field->get( 'nid' ) ] ) ) {
					$field->setSelected( $this->_request[ $field->get( 'nid' ) ] );
				}
				$fields[ $index ] = $field;
			}
		}
		Sobi::Trigger( 'LoadFields', 'Search', [ &$fields ] );

		return $fields;
	}

	/**
	 * Gets the ssid. If not set, try to set it in the cookie if allowed ($setCookie).
	 *
	 * @param string $ssid
	 * @param bool $setCookie
	 *
	 * @return bool -> returns if the cookie could be set (true) or not (false)
	 * @throws \Sobi\Error\Exception
	 * @throws \Exception
	 */
	protected function session( string &$ssid, bool $setCookie = true ): bool
	{
		/* if it wasn't new search, there is a ssid set (only if cookies could be set) */
		$ssid = $this->getSsid();
		$new = false;

		/* if it was a new search or cookies could not be set, create new ssid */
		$ssidToRequest = false;
		if ( !strlen( $ssid ) && $setCookie ) {
			$new = true;
			$ssid = ( microtime( true ) * 100 ) . '.' . rand( 0, 99 );
			Cookie::Set( 'ssid_' . Sobi::Section(), $ssid, Cookie::Days( 7 ) );

			/* as Joomla does no longer return the result of setting the cookie, check if we have set the cookie. in case we were not able for some reason to set the cookie, we are going to pass the ssid into the URL */
			/* mostly setting the cookie works, but Joomla does not return the result :( */
			/* also, trying to get the cookie via Input:String or Input::Cmd also does not work,
			because Joomla does not refresh its internal data array directly after setting the cookie */
			$cookie_ssid = $_COOKIE[ 'SPro_ssid_' . Sobi::Section() ];
			$ssidToRequest = !( $cookie_ssid == $ssid );
		}

		$attr = [
			'ssid'        => $ssid,
			'uid'         => Sobi::My( 'id' ),
			'browserData' => SPConfig::serialize( SPBrowser::getInstance() ),
		];

		/* get search request */
		if ( !count( $this->_request ) ) {
			$requestData = Input::Search( 'field_' );
			if ( count( $requestData ) ) {
				$attr[ 'requestData' ] = SPConfig::serialize( $requestData );
			}
		}
		/* determine the search parameters */
		if ( $new ) {
			$attr[ 'searchCreated' ] = 'FUNCTION:NOW()';
		}
		/* finally, save */
		try {
			$this->_db->insertUpdate( 'spdb_search', $attr );
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'CANNOT_CREATE_SESSION_DB_ERR', $x->getMessage() ), C::ERROR, 500, __LINE__, __FILE__ );
		}

		return !$setCookie || !$ssidToRequest;
	}

	/**
	 * Gets the ssid from the request if available. If not, get it from a cookie.
	 *
	 * @return string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function getSsid(): string
	{
		/* first check the request array, defaults to the cookie array */
		$ssid = Input::Cmd( 'ssid_' . Sobi::Section(), 'request', $this->getCookie() );
		if ( !$ssid ) {
			/* to be sure that it works, transfer the $ssid into a general request variable and a section specific request variable :) */
			$ssid = Input::String( 'ssid', 'request', C::ES );
		}

		return $ssid;
	}

	/**
	 * Gets the cookie via Joomla method, if not set, get it directly from the cookie array.
	 *
	 * @return string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function getCookie(): string
	{
		$ssid = Input::Cmd( 'SPro_ssid_' . Sobi::Section(), 'cookie' );

		return $ssid ? : ( array_key_exists( 'SPro_ssid_' . Sobi::Section(), $_COOKIE ) ?
			$_COOKIE[ 'SPro_ssid_' . Sobi::Section() ] : C::ES );
	}
}