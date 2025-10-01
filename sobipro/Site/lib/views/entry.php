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
 * @created 10-Jan-200 by Radek Suski
 * @@modified 01 April 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadView( 'view' );

use Sobi\C;
use Sobi\Utils\StringUtils;

/**
 * Class SPEntryView
 */
class SPEntryView extends SPFrontView implements SPView
{
	/**
	 * @param string $out
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception|\DOMException
	 */
	public function display( string $out = C::ES )
	{
		$this->_task = $this->get( 'task' );
		switch ( $this->get( 'task' ) ) {
			case 'edit':
			case 'add':
				$this->edit();
				break;
			case 'details':
				$this->details();
				break;
		}
		parent::display();
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function edit()
	{
		$this->_type = 'entry_form';
		$id = $this->get( 'entry.id' );
		if ( $id ) {
			$this->addHidden( $id, 'entry.id' );
		}

		$type = $this->key( 'template_type', 'xslt' );
		if ( $type != 'php' && Sobi::Cfg( 'global.disable_xslt', false ) ) {
			$type = 'php';
		}
		if ( $type == 'xslt' ) {
			$data = $this->entryData( false );
			$fields = $this->get( 'fields' );
			$html = [];
			if ( is_array( $fields ) && count( $fields ) ) {
				foreach ( $fields as $field ) {
					if ( $field->enabled( 'form' ) ) {
						$paymentFee = C::ES;
						$paymentMsg = C::ES;
						if ( !$field->get( 'isFree' ) && $field->get( 'fee' ) && !Sobi::Can( 'entry.payment.free' ) ) {
							$paymentFee = StringUtils::Currency( $field->get( 'fee' ) );
							$paymentMsg = Sobi::Txt( 'EN.FIELD_NOT_FREE_MSG', [ 'fee' => $paymentFee, 'fieldname' => $field->get( 'name' ) ] );
						}
						$administrative = ( $field->get( 'adminField' ) == 1 ) ? 1 : 0;

						$helptext = C::ES;
						$fielddata = $field->field( true );
						if ( $fielddata ) { /* helptext only if the field returns data */
							$helptext = ( $field->get( 'description' ) ) ? '<div>' . $field->get( 'description' ) . '</div>' : C::ES;
						}
						$html[ $field->get( 'nid' ) ] = [
							'_complex'    => 1,
							'_data'       => [
								'label'       => [
									'_complex'    => 1,
									'_data'       => $field->get( 'name' ),
									'_attributes' => [ 'lang' => Sobi::Lang( false ), 'show' => $fielddata ? $field->__get( 'showEditLabel' ) : C::ES ],
								],
								'data'        => [ '_complex' => 1, '_xml' => 1, '_data' => $fielddata ],
								'description' => [ '_complex' => 1, '_xml' => 1, '_data' => $helptext, '_attributes' => [ 'position' => $field->get( 'helpposition' ) ] ],
								'fee'         => $paymentFee,
								'fee_msg'     => $paymentMsg,
							],
							'_attributes' => [ 'id'                  => $field->get( 'id' ),
							                   'type'                => $field->get( 'type' ),
							                   'suffix'              => $field->get( 'suffix' ),
							                   'position'            => $field->get( 'position' ),
							                   'required'            => $field->get( 'required' ),
							                   'css_edit'            => $field->get( 'cssClassEdit' ),
							                   'width'               => $field->get( 'bsWidth' ),
							                   'css_class'           => ( strlen( $field->get( 'cssClass' ) ) ? $field->get( 'cssClass' ) : 'sp-field' ),
							                   'data-administrative' => $administrative,
							                   'data-meaning'        => ( $field->get( 'fieldType' ) == 'chbxgroup' ?
								                   $field->get( 'meaning' ) : C::ES ),
							],
						];
					}
				}
			}
			$html[ 'save_button' ] = [
				'_complex' => 1,
				'_data'    => [
					'data' => [
						'_complex' => 1,
						'_xml'     => 1,
						'_data'    => SPHtml_Input::submit( 'save', Sobi::Txt( 'EN.SAVE_ENTRY_BT' ) ),
					],
				],
			];
			$html[ 'cancel_button' ] = [
				'_complex' => 1,
				'_data'    => [
					'data' => [
						'_complex' => 1,
						'_xml'     => 1,
						'_data'    => SPHtml_Input::button( 'cancel', Sobi::Txt( 'EN.CANCEL_BT' ), [ 'data-role' => 'cancel', 'class' => 'spctrl-cancel' ] ),
					],
				],
			];

			$data[ 'entry' ][ '_data' ][ 'fields' ] = [
				'_complex'    => 1,
				'_data'       => $html,
				'_attributes' => [ 'lang' => Sobi::Lang( false ) ],
			];

			$this->_attr = $data;
			Sobi::Trigger( $this->_type, ucfirst( __FUNCTION__ ), [ &$this->_attr ] );
		}
	}

	/**
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function details()
	{
		$this->_type = 'entry_details';
		$type = $this->key( 'template_type', 'xslt' );
		if ( $type != 'php' && Sobi::Cfg( 'global.disable_xslt', false ) ) {
			$type = 'php';
		}
		if ( $type == 'xslt' ) {
			$this->_attr = $this->entryData();
			SPFactory::header()->addCanonical( $this->_attr[ 'entry' ][ '_data' ][ 'url' ] );
			Sobi::Trigger( 'EntryView', ucfirst( __FUNCTION__ ), [ &$this->_attr ] );
		}
	}

	/**
	 * Collect all entry data and create XML data from it for XSL template.
	 *
	 * @param bool $getFields
	 *
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function entryData( bool $getFields = true ): array
	{
		/** @var SPEntry $entry */
		$entry = $this->get( 'entry' );
		$visitor = $this->get( 'visitor' );
		$data = [];
		$section = SPFactory::Section( Sobi::Section() );
		$data[ 'section' ] = [
			'_complex'    => 1,
			'_data'       => Sobi::Section( true ),
			'_attributes' => [ 'id' => Sobi::Section(), 'lang' => Sobi::Lang( false ) ],
		];
		if ( $this->_task != 'details' ) {
			$data[ 'name' ] = [
				'_complex'    => 1,
				'_data'       => $section->get( 'efTitle' ),
				'_attributes' => [ 'lang' => Sobi::Lang( false ) ],
			];
			$data[ 'description' ] = [
				'_complex'    => 1,
				'_data'       => $section->get( 'efDesc' ),
				'_attributes' => [ 'lang' => Sobi::Lang( false ) ],
			];
		}
		$en = [];
		$en[ 'name' ] = [
			'_complex'    => 1,
			'_data'       => $entry->get( 'name' ),
			'_attributes' => [ 'lang' => Sobi::Lang( false ), 'type' => 'inbox', 'alias' => $entry->get( 'nameField' ) ],
		];
		$en[ 'created_time' ] = $entry->get( 'createdTime' );
		$en[ 'updated_time' ] = $entry->get( 'updatedTime' );
		$en[ 'valid_since' ] = $entry->get( 'validSince' );
		$en[ 'valid_until' ] = $entry->get( 'validUntil' );
		$en[ 'author' ] = $entry->get( 'owner' );
		$en[ 'counter' ] = $entry->get( 'counter' );
		$en[ 'approved' ] = $entry->get( 'approved' );

		$this->fixTimes( $en );

		if ( $entry->get( 'state' ) == 0 ) {
			$en[ 'state' ] = 'unpublished';
		}
		else {
			if ( $entry->get( 'validUntil' )
				&& strtotime( $entry->get( 'validUntil' ) ) != 0
				&& strtotime( $entry->get( 'validUntil' ) ) < time()
			) {
				$en[ 'state' ] = 'expired';
			}
			elseif ( strtotime( $entry->get( 'validSince' ) )
				&& strtotime( $entry->get( 'validSince' ) ) != 0
				&& strtotime( $entry->get( 'validSince' ) ) > time()
			) {
				$en[ 'state' ] = 'pending';
			}
			else {
				$en[ 'state' ] = 'published';
			}
		}
		$en[ 'url' ] = Sobi::Url( [ 'pid'   => $entry->get( 'parent' ),
		                            'sid'   => $entry->get( 'id' ),
		                            'title' => Sobi::Cfg( 'sef.alias', true ) ? $entry->get( 'nid' ) : $entry->get( 'name' ) ],
			true, true, true );

		$en = $this->getEntryUrls( $entry, $en );
		$cats = $entry->get( 'categories' );
		$primaryCat = $entry->get( 'parent' );

		$categories = $cn = [];
		if ( count( $cats ) ) {
			$cn = SPLang::translateObject( array_keys( $cats ), [ 'name', 'alias' ], 'category' );
		}

		/* create XML output for XSL template */
		foreach ( $cats as $cid => $cat ) {
			$categoryAttributes = [ 'lang'     => Sobi::Lang( false ),
			                        'id'       => $cat[ 'pid' ],
			                        'alias'    => $cat [ 'alias' ],
			                        'position' => $cat[ 'position' ],
			                        'url'      => Sobi::Url( [ 'sid'   => $cat[ 'pid' ],
			                                                   'title' => Sobi::Cfg( 'sef.alias', true ) ? $cat[ 'alias' ] : $cat[ 'name' ] ]
			                        ),
			];
			if ( $cat[ 'pid' ] == $primaryCat ) {
				$categoryAttributes[ 'primary' ] = 'true';
			}

			/* if show all category data */
			if ( Sobi::Cfg( 'list.cat_full', false ) ) {
				$category = SPFactory::Category( $cat[ 'pid' ] );
				$catdata = [
					'_complex' => 1,
					'_data'    => [
						'description' => [
							'_complex'    => 1,
							//							'_cdata'      => 1,
							'_data'       => $category->get( 'description' ),
							'_attributes' => [ 'lang' => Sobi::Lang( false ) ],
						],
						'introtext'   => [
							'_complex'    => 1,
							//							'_cdata'      => 1,
							'_data'       => $category->get( 'introtext' ),
							'_attributes' => [ 'lang' => Sobi::Lang( false ) ],
						],
					],
				];

				$categories[] = [
					'_complex'    => 1,
					'_data'       => [
						'name' => [
							'_complex' => 1,
							'_data'    => $cn[ $cid ] ? ( StringUtils::Clean( $cn[ $cid ][ 'value' ] ?? $cn[ $cid ][ 'name' ] ) ) : 0,
						],
						$catdata,
					],
					'_attributes' => $categoryAttributes,
				];
			}
			else {
				$categories[] = [
					'_complex'    => 1,
					'_data'       => $cn[ $cid ] ? ( StringUtils::Clean( $cn[ $cid ][ 'value' ] ?? $cn[ $cid ][ 'name' ] ) ) : 0,
					'_attributes' => $categoryAttributes,
				];
			}
		}
		$en[ 'categories' ] = $categories;

		$en[ 'meta' ] = [
			'description' => $entry->get( 'metaDesc' ),
			'keys'        => $this->metaKeys( $entry ),
			'author'      => $entry->get( 'metaAuthor' ),
			'robots'      => $entry->get( 'metaRobots' ),
		];
		if ( $getFields ) {
			$fields = $entry->getFields();
			if ( is_array( $fields ) && count( $fields ) ) {
				$fieldsToDisplay = $this->getFieldsToDisplay( $entry );
				if ( $fieldsToDisplay ) {
					foreach ( $fields as $i => $field ) {
						if ( !in_array( $field->get( 'id' ), $fieldsToDisplay ) ) {
							unset( $fields[ $i ] );
						}
					}
				}
				$en[ 'fields' ] = $this->fieldStruct( $fields, 'details' );
			}
		}
		$this->menu( $data );

		if ( !Sobi::Cfg( 'alphamenu.catdependent', false ) ) {
			$this->alphaMenu( $data );
		}
		$data[ 'entry' ] = [
			'_complex'    => 1,
			'_data'       => $en,
			'_attributes' => [ 'id' => $entry->get( 'id' ), 'nid' => $entry->get( 'nid' ), 'version' => $entry->get( 'version' ) ],
		];
		$data[ 'visitor' ] = $this->visitorArray( $visitor );

		return $data;
	}
}
