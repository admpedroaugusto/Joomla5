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
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 29-March-2010 by Radek Suski
 * @modified 03 April 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadView( 'section' );

use Sobi\C;

/**
 * Class SPSearchView
 */
class SPSearchView extends SPSectionView implements SPView
{
	/**
	 * @param string $type
	 * @param string $out
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception|\DOMException
	 */
	public function display( string $type = 'section', string $out = C::ES )
	{
		$this->_type = 'search';
		$type = $this->key( 'template_type', 'xslt' );
		if ( $type != 'php' && Sobi::Cfg( 'global.disable_xslt', false ) ) {
			$type = 'php';
		}
		if ( $type == 'xslt' ) {
			$searchData = [];
			$fields = $this->get( 'fields' );
			$visitor = $this->get( 'visitor' );
			$p = $this->get( 'priorities' );
			$priorities = [];
			if ( is_array( $p ) && count( $p ) ) {
				foreach ( $p as $priority => $eids ) {
					if ( is_array( $eids ) && count( $eids ) ) {
						foreach ( $eids as $sid ) {
							$priorities[ $sid ] = $priority;
						}
					}
				}
			}
			$entries = $this->get( 'entries' );
			$searchData[ 'section' ] = [
				'_complex'    => 1,
				'_data'       => Sobi::Section( true ),
				'_attributes' => [ 'id' => Sobi::Section(), 'lang' => Sobi::Lang( false ) ],
			];
			$section = SPFactory::Section( Sobi::Section() );
			$searchData[ 'name' ] = [
				'_complex'    => 1,
				'_data'       => $section->get( 'sfTitle' ),
				'_attributes' => [ 'lang' => Sobi::Lang( false ) ],
			];
			$searchData[ 'description' ] = [
				'_complex'    => 1,
				'_data'       => $section->get( 'sfDesc' ),
				'_attributes' => [ 'lang' => Sobi::Lang( false ) ],
			];

			$searchPhrase = $this->get( 'search_for' ) ? $this->get( 'search_for' ) : C::ES;
			$phrase = $this->get( 'search_phrase' ) ? $this->get( 'search_phrase' ) : C::ES;
//			$searchPhrase = strlen( $searchPhrase ) ? $searchPhrase : Sobi::Txt( 'SH.SEARCH_FOR_BOX' );

//			SPFactory::header()->addJsCode( 'let spSearchDefStr = "' . Sobi::Txt( 'SH.SEARCH_FOR_BOX' ) . '"' );
			SPFactory::header()->addJsCode( 'let spSearchDefStr = ""' );
			if ( $this->get( '$eInLine' ) ) {
				$searchData[ 'entries_in_line' ] = $this->get( '$eInLine' );
			}
			$scount = $this->get( '$eCount' );
			$searchData[ 'count' ] = $scount;
			if ( $scount >= 0 ) {
				$sterm = $this->get( 'search_for' );
				if ( isset( $sterm ) && ( $sterm != '*' ) && ( $sterm != Sobi::Txt( 'SH.SEARCH_FOR_BOX' ) && !( empty( $sterm ) ) ) ) {
					$searchData[ 'message' ] = Sobi::Txt( 'SH.SEARCH_FOUND_TERM_RESULTS', [ 'count' => $scount, 'sterm' => $sterm ] );
				}
				elseif ( isset( $sterm ) && $sterm == '*' ) {
					$searchData[ 'message' ] = Sobi::Txt( 'SH.SEARCH_FOUND_ALL', [ 'count' => $scount ] );
				}
				else {
					$searchData[ 'message' ] = Sobi::Txt( 'SH.SEARCH_FOUND_RESULTS', [ 'count' => $scount ] );
				}
			}
			$searchData[ 'search_order' ] = $this->get( 'orderings' );
			$this->menu( $searchData );
			$this->alphaMenu( $searchData );
			$fData = [];
			if ( Sobi::Cfg( 'search.show_searchbox', true ) ) {
				$fData[ 'searchbox' ] = [
					'_complex'    => 1,
					'_data'       => [
						'label' => [
							'_complex'    => 1,
							'_data'       => Sobi::Txt( 'SH.SEARCH_FOR' ),
							'_attributes' => [ 'lang' => Sobi::Lang( false ) ],
						],
						'data'  => [
							'_complex' => 1,
							'_xml'     => 1,
							'_data'    => SPHtml_Input::text( 'sp_search_for', $searchPhrase, [ 'class' => Sobi::Cfg( 'search.form_box_def_css', 'sp-search-inbox' ), 'id' => 'spctrl-searchbox' ] ),
						],
					],
					'_attributes' => [ 'position' => 1, 'css_class' => 'sp-search-inbox' ],
				];
			}
			if ( Sobi::Cfg( 'search.top_button', true ) ) {
				$fData[ 'top_button' ] = [
					'_complex'    => 1,
					'_data'       => [
						'label' => [
							'_complex'    => 1,
							'_data'       => Sobi::Txt( 'SH.SEARCH_START' ),
							'_attributes' => [ 'lang' => Sobi::Lang() ],
						],
						'data'  => [
							'_complex' => 1,
							'_xml'     => 1,
							'_data'    => SPHtml_Input::submit( 'search', Sobi::Txt( 'SH.START' ), [ 'id' => 'top_button' ] ),
						],
					],
					'_attributes' => [ 'position' => 1, 'css_class' => 'sp-search-button' ],
				];
			}
			if ( Sobi::Cfg( 'search.show_phrase', true ) ) {
				$fData[ 'phrase' ] = [
					'_complex'    => 1,
					'_data'       => [
						'label' => [
							'_complex'    => 1,
							'_data'       => Sobi::Txt( 'SH.FIND_ENTRIES_THAT_HAVE' ),
							'_attributes' => [ 'lang' => Sobi::Lang( false ) ],
						],
						'data'  => [
							'_complex' => 1,
							'_xml'     => 1,
							'_data'    => SPHtml_Input::radioList(
								'spsearchphrase',
								[
									'all'   => Sobi::Txt( 'SH.FIND_ENTRIES_THAT_HAVE_ALL_WORDS' ),
									'any'   => Sobi::Txt( 'SH.FIND_ENTRIES_THAT_HAVE_ANY_WORDS' ),
									'exact' => Sobi::Txt( 'SH.FIND_ENTRIES_THAT_HAVE_EXACT_PHRASE' ),
								],
								'spsearchphrase',
								strlen( $phrase ) ? $phrase : Sobi::Cfg( 'search.form_searchphrase_def', 'all' ),
								[ 'aria-label' => Sobi::Txt( 'ACCESSIBILITY.FIND_ENTRIES_THAT_HAVE' ) ], 'group', false
							),
						],
					],
					'_attributes' => [ 'position' => 1, 'css_class' => 'sp-search-phrase' ],
				];
			}
			if ( count( $fields ) ) {
				foreach ( $fields as $field ) {
					$data = $field->searchForm();
					$suffix = $field->get( 'searchMethod' ) != 'range' ? $field->get( 'suffix' ) : C::ES;
					if ( strlen( $data ) ) {
						$fData[ $field->get( 'nid' ) ] = [
							'_complex'    => 1,
							'_data'       => [
								'label' => [
									'_complex'    => 1,
									'_data'       => $field->get( 'name' ),
									'_attributes' => [ 'lang' => Sobi::Lang() ],
								],
								'data'  => [
									'_complex' => 1,
									'_xml'     => 1,
									'_data'    => $data,
								],
							],
							'_attributes' => [ 'id'         => $field->get( 'id' ),
							                   'type'       => $field->get( 'type' ),
							                   'suffix'     => $suffix,
							                   'position'   => $field->get( 'position' ),
							                   'css_search' => $field->get( 'cssClassSearch' ),
							                   'width'      => $field->get( 'bsSearchWidth' ),
							                   'css_class'  => ( strlen( $field->get( 'cssClass' ) ) ? $field->get( 'cssClass' ) : 'sp-field' ),
							],
						];
					}
				}
			}
			if ( Sobi::Cfg( 'search.bottom_button', false ) ) {
				$fData[ 'bottom_button' ] = [
					'_complex'    => 1,
					'_data'       => [
						'label' => [
							'_complex'    => 1,
							'_data'       => Sobi::Txt( 'SH.SEARCH_START' ),
							'_attributes' => [ 'lang' => Sobi::Lang( false ) ],
						],
						'data'  => [
							'_complex' => 1,
							'_xml'     => 1,
							'_data'    => SPHtml_Input::submit( 'search', Sobi::Txt( 'SH.START' ) ),
						],
					],
					'_attributes' => [ 'position' => 1, 'css_class' => 'sp-search-button' ],
				];
			}
			$searchData[ 'fields' ] = $fData;

			$this->switchOrderingMenu( $searchData, $this->get( 'orderings' ), Sobi::Cfg( 'sordering.fields_array' ) );

			if ( is_array( $entries ) && count( $entries ) ) {
				$this->loadNonStaticData( $entries );
				$manager = (bool) Sobi::Can( 'entry', 'edit', '*', Sobi::Section() );
				foreach ( $entries as $entry ) {
					$en = $this->entry( $entry, $manager );
					if ( count( $en ) ) {
						$searchData[ 'entries' ][] = [
							'_complex'    => 1,
							'_attributes' => [ 'id'              => $en[ 'id' ],
							                   'search-priority' => $priorities[ $en[ 'id' ] ] ?? 'undefined' ],
							'_data'       => $en,
						];
					}
				}
				$this->navigation( $searchData );
			}
			$searchData[ 'visitor' ] = $this->visitorArray( $visitor );
			$this->_attr = $searchData;
		}
		SPFactory::config()->nameField();   // Check for the status of name field and output a warning if field does not exist or is disabled
		Sobi::Trigger( $this->_type, ucfirst( __FUNCTION__ ), [ &$this->_attr ] );
		parent::display( $this->_type );
	}
}
