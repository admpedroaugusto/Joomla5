<?php
/**
 * @package SobiPro Library
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 10-Jan-2009 by Radek Suski
 * @modified 19 February 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadView( 'view' );

use Sobi\C;
use Sobi\Input\Input;
use Sobi\FileSystem\FileSystem;
use Sobi\Utils\StringUtils;

/**
 * Class SPSectionView
 */
class SPSectionView extends SPFrontView implements SPView
{
	/**
	 * @param $category
	 * @param bool $fields
	 *
	 * @return array|bool|mixed
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function category( $category, bool $fields = true )
	{
		$cat = [];
		if ( is_numeric( $category ) ) {
			$cat = $this->cachedCategory( $category, $fields );
		}
		if ( !count( $cat ) ) {
			if ( is_numeric( $category ) ) {
				$category = SPFactory::Category( $category );
			}
			$cat[ 'id' ] = $category->get( 'id' );
			$cat[ 'nid' ] = $category->get( 'nid' );
			$cat[ 'name' ] = [
				'_complex'    => 1,
				'_data'       => $category->get( 'name' ),
				'_attributes' => [ 'lang' => Sobi::Lang( false ) ],
			];

			if ( Sobi::Cfg( 'list.cat_desc', false ) ) {
				$cat[ 'description' ] = [
					'_complex'    => 1,
					'_cdata'      => 1,
					'_data'       => $category->get( 'description' ),
					'_attributes' => [ 'lang' => Sobi::Lang( false ) ],
				];
			}
			$cat[ 'created_time' ] = $category->get( 'createdTime' );
			$cat[ 'updated_time' ] = $category->get( 'updatedTime' );
			$cat[ 'valid_since' ] = $category->get( 'validSince' );
			$cat[ 'valid_until' ] = $category->get( 'validUntil' );
			$this->fixTimes( $cat );

			$showIntro = $category->get( 'showIntrotext' );
			if ( $showIntro == C::GLOBAL ) {
				$showIntro = Sobi::Cfg( 'category.show_intro', true );
			}
			if ( $showIntro ) {
				$cat[ 'introtext' ] = [
					'_complex'    => 1,
					'_cdata'      => 1,
					'_data'       => $category->get( 'introtext' ),
					'_attributes' => [ 'lang' => Sobi::Lang( false ) ],
				];
			}
			$showIcon = $category->get( 'showIcon' );
			if ( $showIcon == C::GLOBAL ) {
				$showIcon = Sobi::Cfg( 'category.show_icon', true );
			}
			if ( $showIcon && $category->get( 'icon' ) ) {
				if ( strstr( $category->get( 'icon' ), 'font-' ) ) {
					$icon = json_decode( str_replace( "'", '"', $category->get( 'icon' ) ), true );

					$fontclass = C::ES;
					$iconsize = Sobi::Cfg( 'category.iconsize', 3 );
					switch ( $iconsize ) {
						case 1:
							switch ( $icon[ 'font' ] ) {
								default:
								case 'font-awesome-3':
									$fontclass = 'icon-large';
									break;
								case 'font-awesome-4':
								case 'font-awesome-5':
								case 'font-awesome-6':
									$fontclass = 'fa-lg';
									break;
								case 'font-google-materials':
									$fontclass = 'ma-lg';
									break;
							}
							break;
						case 2:
						case 3:
						case 4:
						case 5:
							if ( $icon ) {
								switch ( $icon[ 'font' ] ) {
									default:
									case 'font-awesome-3':
										$fontclass = 'icon-' . $iconsize . 'x';
										break;
									case 'font-awesome-4':
									case 'font-awesome-5':
									case 'font-awesome-6':
										$fontclass = 'fa-' . $iconsize . 'x';
										break;
									case 'font-google-materials':
										$fontclass = 'ma-' . $iconsize . 'x';
										break;
								}
							}
							break;
					}

					if ( $fontclass != C::ES && !empty( $icon ) ) {
						$icon[ 'class' ] .= ' ' . $fontclass;
					}
					if ( $category->param( 'icon-font-add-class' ) ) {
						$icon[ 'class' ] .= ' ' . $category->param( 'icon-font-add-class' );
					}
					$cat[ 'icon' ] = [
						'_complex'    => 1,
						'_data'       => '',
						'_attributes' => $icon,
					];
				}
				else {
					if ( FileSystem::Exists( Sobi::Cfg( 'images.category_icons' ) . '/' . $category->get( 'icon' ) ) ) {
						$cat[ 'icon' ] = FileSystem::FixUrl( Sobi::Cfg( 'images.category_icons_live' ) . $category->get( 'icon' ) );
					}
				}
			}
			$cat[ 'url' ] = Sobi::Url( [ 'title' => Sobi::Cfg( 'sef.alias', true ) ? $category->get( 'nid' ) : $category->get( 'name' ), 'sid' => $category->get( 'id' ) ] );
			$cat[ 'position' ] = $category->get( 'position' );
			$cat[ 'author' ] = $category->get( 'owner' );
			if ( $category->get( 'state' ) == 0 ) {
				$cat[ 'state' ] = 'unpublished';
			}
			else {
				if ( $category->get( 'validUntil' )
					&& strtotime( $category->get( 'validUntil' ) ) != 0
					&& strtotime( $category->get( 'validUntil' ) ) < time()
				) {
					$cat[ 'state' ] = 'expired';
				}
				else {
					if ( $category->get( 'validSince' )
						&& strtotime( $category->get( 'validSince' ) ) != 0
						&& strtotime( $category->get( 'validSince' ) ) > time()
					) {
						$cat[ 'state' ] = 'pending';
					}
					else {
						$cat[ 'state' ] = 'published';
					}
				}
			}
			if ( Sobi::Cfg( 'list.cat_meta', false ) ) {
				$cat[ 'meta' ] = [
					'description' => $category->get( 'metaDesc' ),
					'keys'        => $this->metaKeys( $category ),
					'author'      => $category->get( 'metaAuthor' ),
					'robots'      => $category->get( 'metaRobots' ),
				];
			}
			if ( $fields ) {
				$category->loadFields( Sobi::Section(), true );
				$fields = $category->get( 'fields' );
				$this->categoryFields( $cat, $fields );
			}

			/* processing subcategories */
			if ( Sobi::Cfg( 'list.subcats', true ) ) {
				$subcats = $category->getChilds( 'category', false, 1, true, Sobi::Cfg( 'list.subcats_ordering', 'position.desc' ) );
				$sc = [];
				if ( count( $subcats ) ) {
					foreach ( $subcats as $id => $name ) {
						if ( $name[ 'state' ] || Sobi::Can( 'category.access.unpublished' ) ) {
							$sc[] = [
								'_complex'    => 1,
								'_data'       => $name[ 'name' ] ? StringUtils::Clean( $name[ 'name' ] ) : C::ES,
								'_attributes' => [ 'lang'  => Sobi::Lang( false ),
								                   'nid'   => $name[ 'alias' ],
								                   'id'    => $id,
								                   'state' => $name[ 'state' ] ? 'published' : 'unpublished',
								                   'url'   => Sobi::Url( [ 'title' => Sobi::Cfg( 'sef.alias', true ) ? $name[ 'alias' ] : $name[ 'name' ], 'sid' => $id, ] ) ],
							];
						}
					}
				}
				$cat[ 'subcategories' ] = $sc;
			}
			$ident = $fields ? 'category_full_struct' : 'category_struct';
			SPFactory::cache()->addObj( $cat, $ident, $category->get( 'id' ) );
			unset( $category );
		}
		$cat[ 'counter' ] = $this->getNonStaticData( $cat[ 'id' ], 'counter' );
		Sobi::Trigger( 'List', ucfirst( __FUNCTION__ ), [ &$cat ] );

		return $cat;
	}

	/**
	 * @param $category
	 * @param bool $fields
	 *
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function cachedCategory( $category, bool $fields = false ): array
	{
		$ident = $fields ? 'category_full_struct' : 'category_struct';
		$cat = SPFactory::cache()->getObj( $ident, $category );

		return is_array( $cat ) && count( $cat ) ? $cat : [];
	}

	/**
	 * @param $entry
	 * @param $manager
	 * @param bool $noId
	 * @param string $viewOverride
	 *
	 * @return array
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function cachedEntry( $entry, $manager, bool $noId = false, string $viewOverride = C::ES )
	{
		/* first get the list of non-statics */
		static $nonStatic = C::ES;
		if ( !$nonStatic ) {
			$nonStatic = explode( ',', Sobi::Cfg( 'cache.non_static', 'counter, id' ) );
			if ( count( $nonStatic ) ) {
				foreach ( $nonStatic as $index => $value ) {
					$value = trim( $value );
					if ( $value == 'id' ) {
						unset( $nonStatic[ $index ] );
					}
//					else {
//						$nonStatic[ $index ] = $value;
//					}
				}
			}
		}

		/* get the entries from the cache */
		$cachedEntry = SPFactory::cache()->getObj( $viewOverride . 'entry_struct', $entry );

		if ( is_array( $cachedEntry ) && count( $cachedEntry ) ) {
			$section = Sobi::Section();
			if ( !strstr( Input::Task(), 'search' ) && !$noId
				&& ( !Sobi::Cfg( 'section.force_category_id', false ) || Input::Sid() != $section )
			) {
				$cachedEntry[ 'url_array' ][ 'pid' ] = Input::Sid();
			}
			$cachedEntry[ 'url' ] = Sobi::Url( $cachedEntry[ 'url_array' ] );
			unset( $cachedEntry[ 'url_array' ] );

			/* remove the edit url if the user may not edit the entry */
			if ( $manager
				|| ( ( Sobi::My( 'id' )
					&& ( Sobi::My( 'id' ) == $cachedEntry[ 'author' ] )
					&& Sobi::Can( 'entry', 'edit', 'own', $section ) ) )
			) {
				$cachedEntry[ 'edit_url' ] = Sobi::Url( $cachedEntry[ 'edit_url_array' ] );
			}
			else {
				if ( isset( $cachedEntry[ 'edit_url' ] ) ) {
					unset( $cachedEntry[ 'edit_url' ] );
				}
			}
			unset( $cachedEntry[ 'edit_url_array' ] );

			/* get the non-static data */
			foreach ( $nonStatic as $value ) {
				$cachedEntry[ $value ] = $this->getNonStaticData( $entry, $value );
			}
			/* check the fields */
			if ( isset( $cachedEntry[ 'fields' ] ) && count( $cachedEntry[ 'fields' ] ) ) {
				$this->validateFields( $cachedEntry[ 'fields' ] );
			}

			return $cachedEntry;
		}

		return [];
	}

	/**
	 * @param $entry
	 * @param $manager
	 * @param bool $noId
	 * @param string $viewOverride
	 *
	 * @return array
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function entry( $entry, $manager, bool $noId = false, string $viewOverride = C::ES )
	{
		$en = [];
		if ( is_numeric( $entry ) ) {
			$en = $this->cachedEntry( $entry, $manager, $noId, $viewOverride );
		}
		if ( !is_array( $en ) || !count( $en ) ) {
			$currentSid = Input::Sid();
			if ( is_numeric( $entry ) ) {
				$entry = SPFactory::Entry( $entry );
				/* don't show invalid entries on frontend */
				if ( !$entry->get( 'valid' ) ) {
					return $en;
				}
			}
			$en[ 'id' ] = $entry->get( 'id' );
			$en[ 'nid' ] = $entry->get( 'nid' );
			$en[ 'name' ] = [
				'_complex'    => 1,
				'_data'       => $entry->get( 'name' ),
				'_attributes' => [ 'lang' => Sobi::Lang( false ), 'type' => 'inbox', 'alias' => $entry->get( 'nameField' ) ],
			];
			$ownership = 'valid';
			if ( Sobi::My( 'id' ) && Sobi::My( 'id' ) == $entry->get( 'owner' ) ) {
				$ownership = 'own';
			}
			// don't ask
			Input::Set( 'sid', $entry->get( 'id' ) );
			$en[ 'acl' ] = [
				'_complex'    => 1,
				'_data'       => null,
				'_attributes' => [ 'accessible' => Sobi::Can( 'entry', 'access', $ownership ) ? 'true' : 'false' ],
			];
			Input::Set( 'sid', $currentSid );
//			$en[ 'acl' ] = array(
//					'_complex' => 1,
//					'_data' => null,
//					'_attributes' => array( 'accessible' => Sobi::Can( 'entry', 'access', $ownership ) ? 'true' : 'false' )
//			);
//			Input::Set( 'sid', $entry->get( 'id' ) );
			$en[ 'url_array' ] = [ 'title' => Sobi::Cfg( 'sef.alias', true )
				? $entry->get( 'nid' )
				: $entry->get( 'name' ),
			                       'pid'   => $entry->get( 'primary' ),
			                       'sid'   => $entry->get( 'id' ) ];
			if ( strstr( Input::Task(), 'search' ) || $noId || ( Sobi::Cfg( 'section.force_category_id', false ) && Input::Sid() == Sobi::Section() ) ) {
				$en[ 'url' ] = Sobi::Url( [ 'title' => Sobi::Cfg( 'sef.alias', true )
					? $entry->get( 'nid' )
					: $entry->get( 'name' ),
				                            'pid'   => $entry->get( 'primary' ),
				                            'sid'   => $entry->get( 'id' ) ] );
			}
			else {
				$en[ 'url' ] = Sobi::Url( [ 'title' => Sobi::Cfg( 'sef.alias', true )
						? $entry->get( 'nid' )
						: $entry->get( 'name' ),
				                            'pid'   => Input::Sid(),
				                            'sid'   => $entry->get( 'id' ) ]
				);
			}
			if ( Sobi::Cfg( 'list.entry_meta', true ) ) {
				$en[ 'meta' ] = [
					'description' => $entry->get( 'metaDesc' ),
					'keys'        => $this->metaKeys( $entry ),
					'author'      => $entry->get( 'metaAuthor' ),
					'robots'      => $entry->get( 'metaRobots' ),
				];
			}
			$en = $this->getEntryUrls( $entry, $en );

			$en[ 'edit_url_array' ] = [ 'task' => 'entry.edit', 'pid' => Input::Sid(), 'sid' => $entry->get( 'id' ) ];
			$en[ 'created_time' ] = $entry->get( 'createdTime' );
			$en[ 'updated_time' ] = $entry->get( 'updatedTime' );
			$en[ 'valid_since' ] = $entry->get( 'validSince' );
			$en[ 'valid_until' ] = $entry->get( 'validUntil' );
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
				else {
					if ( $entry->get( 'validSince' )
						&& strtotime( $entry->get( 'validSince' ) ) != 0
						&& strtotime( $entry->get( 'validSince' ) ) > time()
					) {
						$en[ 'state' ] = 'pending';
					}
					else {
						$en[ 'state' ] = 'published';
					}
				}
			}

			$en[ 'author' ] = $entry->get( 'owner' );
			$en[ 'counter' ] = $entry->get( 'counter' );
			$en[ 'approved' ] = $entry->get( 'approved' );
			//		$en[ 'confirmed' ] = $entry->get( 'confirmed' );
			if ( Sobi::Cfg( 'list.entry_cats', true ) ) {
				$cats = $entry->get( 'categories' );
				$categories = [];
				$cn = [];
				if ( is_array( $cats ) && count( $cats ) ) {
					$cn = SPLang::translateObject( array_keys( $cats ), [ 'name', 'alias' ] );
				}
				$primaryCat = $entry->get( 'parent' );
				foreach ( $cats as $cid => $cat ) {
					$categoryAttributes = [ 'lang'     => Sobi::Lang( false ),
					                        'id'       => $cat[ 'pid' ],
					                        'position' => $cat[ 'position' ],
					                        'url'      => Sobi::Url( [ 'sid'   => $cat[ 'pid' ],
					                                                   'title' => Sobi::Cfg( 'sef.alias', true ) ? $cat[ 'alias' ] : $cat[ 'name' ] ] ),
					];
					if ( $cat[ 'pid' ] == $primaryCat ) {
						$categoryAttributes[ 'primary' ] = 'true';
					}

					$value = count( $cn ) ? ( isset( $cn[ $cid ][ 'value' ] ) ? StringUtils::Clean( $cn[ $cid ][ 'value' ] ) : C::ES ) : C::ES;
					$categories[] = [
						'_complex'    => 1,
						'_data'       => $value,
						'_attributes' => $categoryAttributes,
					];
				}
				$en[ 'categories' ] = $categories;
			}
			$fields = $entry->getFields();
			if ( count( $fields ) ) {
				$fieldsToDisplay = $this->getFieldsToDisplay( $entry );
				if ( $fieldsToDisplay ) {
					foreach ( $fields as $i => $field ) {
						if ( !( in_array( $field->get( 'id' ), $fieldsToDisplay ) ) ) {
							unset( $fields[ $i ] );
						}
					}
				}
				$view = $viewOverride ? : 'vcard';
				$en[ 'fields' ] = $this->fieldStruct( $fields, $view );
			}

			SPFactory::cache()
				->addObj( $entry, 'entry', $entry->get( 'id' ) )
				->addObj( $en, $viewOverride . 'entry_struct', $entry->get( 'id' ) );
			unset( $en[ 'url_array' ] );
			unset( $en[ 'edit_url_array' ] );
			unset( $entry );
			$en[ 'counter' ] = $this->getNonStaticData( $en[ 'id' ], 'counter' );
		}

		/*
		   * this is te special case:
		   * no matter what task we currently have - if someone called this we need the data for the V-Card
		   * Soe we have to trigger all these plugins we need and therefore also fake the task
		   */
		$task = 'list.custom';
		SPFactory::registry()->set( 'task', $task );
		Sobi::Trigger( 'List', ucfirst( __FUNCTION__ ), [ &$en ] );

		return $en;
	}

	/**
	 * @param $data
	 *
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function navigation( &$data )
	{
		$navigation = $this->get( 'navigation' );
		if ( is_array( $navigation ) && count( $navigation ) ) {
			$data[ 'navigation' ] = [ '_complex'    => 1,
			                          '_data'       => $navigation,
			                          '_attributes' => [ 'lang' => Sobi::Lang( false ) ] ];
		}
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function view( $override = C::ES )
	{
		$type = $this->key( 'template_type', 'xslt' );
		if ( $type != 'php' && Sobi::Cfg( 'global.disable_xslt', false ) ) {
			$type = 'php';
		}
		if ( $type == 'xslt' ) {
			$visitor = $this->get( 'visitor' );
			$current = $this->get( $this->_type );

			$orderings = $this->get( 'orderings' );
			/* in case an app does not set this value */
			$orderings[ 'entries' ] = $orderings[ 'entries' ] ?? Sobi::Cfg( 'list.entries_ordering', 'field_name.asc' );

			$categories = $this->get( 'categories' );
			$entries = $this->get( 'entries' );

			if ( !$override ) {
				$cUrl = [ 'title' => Sobi::Cfg( 'sef.alias', true ) ? $current->get( 'nid' ) : $current->get( 'name' ), 'sid' => $current->get( 'id' ) ];
				if ( Input::Int( 'site' ) ) {
					$cUrl[ 'site' ] = Input::Int( 'site' );
				}
				SPFactory::header()->addCanonical( Sobi::Url( $cUrl, true, true, true ) );
			}
			$data = [];
			$data[ 'id' ] = $current->get( 'id' );

			if ( $current->get( 'oType' ) != 'section' ) {
				$data[ 'counter' ] = $current->get( 'counter' );
			}
			$data[ 'section' ] = [
				'_complex'    => 1,
				'_data'       => Sobi::Section( true ),
				'_attributes' => [ 'id' => Sobi::Section(), 'lang' => Sobi::Lang( false ) ],
			];
			$data[ 'name' ] = [
				'_complex'    => 1,
				'_data'       => $current->get( 'name' ),
				'_attributes' => [ 'lang' => Sobi::Lang( false ) ],
			];
			if ( Sobi::Cfg( 'template.development', true ) && !defined( 'SOBIPRO_ADM' ) ) {
				$data[ 'development' ] = true;
			}
			$this->menuOptions( $data );

			$data[ 'created_time' ] = $current->get( 'createdTime' );
			$data[ 'updated_time' ] = $current->get( 'updatedTime' );
			$data[ 'valid_since' ] = $current->get( 'validSince' );
			$data[ 'valid_until' ] = $current->get( 'validUntil' );

			$data[ 'author' ] = $current->get( 'owner' );
			if ( $current->get( 'state' ) == 0 ) {
				$data[ 'state' ] = 'unpublished';
			}
			else {
				if ( $current->get( 'validUntil' )
					&& strtotime( $current->get( 'validUntil' ) ) != 0
					&& strtotime( $current->get( 'validUntil' ) ) < time()
				) {
					$data[ 'state' ] = 'expired';
				}
				else {
					if ( $current->get( 'validSince' )
						&& strtotime( $current->get( 'validSince' ) ) != 0
						&& strtotime( $current->get( 'validSince' ) ) > time()
					) {
						$data[ 'state' ] = 'pending';
					}
					else {
						$data[ 'state' ] = 'published';
					}
				}
			}
			$data[ 'url' ] = Sobi::Url( [ 'title' => Sobi::Cfg( 'sef.alias', true ) ? $current->get( 'nid' ) : $current->get( 'name' ), 'sid' => $current->get( 'id' ) ], true, true, true );

			if ( Sobi::Cfg( 'category.show_desc' ) || $current->get( 'oType' ) == 'section' ) {
				$desc = $current->get( 'description' );
				if ( Sobi::Cfg( 'category.parse_desc' ) ) {
					Sobi::Trigger( 'prepare', 'Content', [ &$desc, $current ] );
				}
				$data[ 'description' ] = [
					'_complex'    => 1,
					'_cdata'      => 1,
					'_data'       => $desc,
					'_attributes' => [ 'lang' => Sobi::Lang( false ) ],
				];
			}
			$showIcon = $current->get( 'showIcon' );
			if ( $showIcon == C::GLOBAL ) {
				$showIcon = Sobi::Cfg( 'category.show_icon', true );
			}
			if ( $showIcon && $current->get( 'icon' ) ) {
				if ( strstr( $current->get( 'icon' ), 'font-' ) ) {
					$icon = json_decode( str_replace( "'", '"', $current->get( 'icon' ) ), true );
					if ( $current->param( 'icon-font-add-class' ) ) {
						$icon[ 'class' ] .= ' ' . $current->param( 'icon-font-add-class' );
					}
					$data[ 'icon' ] = [
						'_complex'    => 1,
						'_data'       => '',
						'_attributes' => $icon,
					];
				}
				else {
					if ( FileSystem::Exists( Sobi::Cfg( 'images.category_icons' ) . '/' . $current->get( 'icon' ) ) ) {
						$data[ 'icon' ] = FileSystem::FixUrl( Sobi::Cfg( 'images.category_icons_live' ) . $current->get( 'icon' ) );
					}
				}
			}
			$data[ 'meta' ] = [
				'description' => $current->get( 'metaDesc' ),
				'keys'        => $this->metaKeys( $current ),
				'author'      => $current->get( 'metaAuthor' ),
				'robots'      => $current->get( 'metaRobots' ),
			];
			$this->categoryFields( $data );
			$data[ 'entries_in_line' ] = $this->get( '$eInLine' );
			$data[ 'categories_in_line' ] = $this->get( '$cInLine' );
			$data[ 'number_of_subcats' ] = Sobi::Cfg( 'list.num_subcats', 6 );

			$this->menu( $data );
			$this->alphaMenu( $data );
			$this->switchOrderingMenu( $data, $orderings[ 'entries' ], Sobi::Cfg( 'ordering.fields_array' ) );
			$data[ 'visitor' ] = $this->visitorArray( $visitor );

			if ( is_array( $categories ) && count( $categories ) ) {
				$this->loadNonStaticData( $categories );
				foreach ( $categories as $category ) {
					$cat = $this->category( $category );
					$data[ 'categories' ][] = [
						'_complex'    => 1,
						'_attributes' => [ 'id' => $cat[ 'id' ], 'nid' => $cat[ 'nid' ] ],
						'_data'       => $cat,
					];
				}
				if ( strstr( $orderings[ 'categories' ], 'name' ) && Sobi::Cfg( 'lang.multimode', false ) ) {
					usort( $data[ 'categories' ], function ( $from, $to ) {
						return strcasecmp( $from[ '_data' ][ 'name' ][ '_data' ], $to[ '_data' ][ 'name' ][ '_data' ] );
					} );
					if ( $orderings[ 'categories' ] == 'name.desc' ) {
						$data[ 'categories' ] = array_reverse( $data[ 'categories' ] );
					}
				}
			}

			$task = 'list.custom';
			SPFactory::registry()->set( 'task', $task );

			if ( is_array( $entries ) && count( $entries ) ) {
				$this->loadNonStaticData( $entries );
				$manager = (bool) Sobi::Can( 'entry', 'edit', '*', Sobi::Section() );

				foreach ( $entries as $eid ) {
					$en = $this->entry( $eid, $manager, false, $override );
					if ( count( $en ) ) {
						$data[ 'entries' ][] = [
							'_complex'    => 1,
							'_attributes' => [ 'id' => $en[ 'id' ], 'nid' => $en[ 'nid' ] ],
							'_data'       => $en,
						];
					}
				}
				/* @deprecated: name field is evaluated by its field name now and already sorted correctly */
				if ( ( strpos( $orderings[ 'entries' ], 'name.' ) === 0 ) && Sobi::Cfg( 'lang.multimode', false ) ) {
					usort( $data[ 'entries' ], function ( $from, $to ) {
						return strcasecmp( $from[ '_data' ][ 'name' ][ '_data' ], $to[ '_data' ][ 'name' ][ '_data' ] );
					} );
					if ( $orderings[ 'entries' ] == 'name.desc' ) {
						$data[ 'entries' ] = array_reverse( $data[ 'entries' ] );
					}
				}

				$this->navigation( $data );
			}
			$this->fixTimes( $data );
			$this->_attr = $data;
		}

		/* Check for status of name field and output a warning if field does not exist or is disabled */
		SPFactory::config()->nameField();

		Sobi::Trigger( $this->_type, ucfirst( __FUNCTION__ ), [ &$this->_attr ] );
	}

	/**
	 * @param $from
	 * @param $to
	 *
	 * @return int
	 */
	protected function orderByName( $from, $to ): int
	{
		return strcasecmp( $from[ '_data' ][ 'name' ][ '_data' ], $to[ '_data' ][ 'name' ][ '_data' ] );
	}

	/**
	 * @param $data
	 * @param array $fields
	 *
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function categoryFields( &$data, $fields = [] )
	{
		if ( !( is_array( $fields ) && count( $fields ) ) ) {
			$fields = $this->get( 'fields' );
		}
		if ( is_array( $fields ) && count( $fields ) ) {
			foreach ( $fields as $field ) {
				$field->set( 'currentView', 'category' );
				$struct = $field->struct();
				$options = null;
				if ( isset( $struct[ '_options' ] ) ) {
					$options = $struct[ '_options' ];
					unset( $struct[ '_options' ] );
				}
				$data[ 'fields' ][ $field->get( 'nid' ) ] = [
					'_complex'    => 1,
					'_data'       => [
						'label' => [
							'_complex'    => 1,
							'_data'       => $field->get( 'name' ),
							'_attributes' => [ 'lang' => Sobi::Lang( false ), 'show' => $field->get( 'withLabel' ) ],
						],
						'data'  => $struct,
					],
					'_attributes' => [ 'id'             => $field->get( 'id' ),
					                   'itemprop'       => $field->get( 'itemprop' ),
					                   'type'           => $field->get( 'type' ),
					                   'suffix'         => $field->get( 'suffix' ),
					                   'position'       => $field->get( 'position' ),
					                   'numeric'        => $field->get( 'numeric' ) == 1 ? 1 : 0,
					                   'untranslatable' => $field->get( 'untranslatable' ) == 1 ? 1 : 0,
					                   'css_view'       => $field->get( 'cssClassView' ),
					                   'css_class'      => strlen( $field->get( 'cssClass' ) ? $field->get( 'cssClass' ) : 'sp-field' ),
					],
				];
				if ( Sobi::Cfg( 'category.field_description', false ) ) {
					$data[ 'fields' ][ $field->get( 'nid' ) ][ '_data' ][ 'description' ] = [ '_complex' => 1, '_xml' => 1, '_data' => $field->get( 'description' ) ];
				}
				if ( $options ) {
					$data[ 'fields' ][ $field->get( 'nid' ) ][ '_data' ][ 'options' ] = $options;
				}
				if ( isset( $struct[ '_xml_out' ] ) && count( $struct[ '_xml_out' ] ) ) {
					foreach ( $struct[ '_xml_out' ] as $key => $value )
						$data[ 'fields' ][ $field->get( 'nid' ) ][ '_data' ][ $key ] = $value;
				}
			}
		}
	}

	/**
	 * @param string $type
	 * @param string $out
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception|\DOMException
	 */
	public function display( string $type = 'section', string $out = C::ES )
	{
		$override = Input::String( 'override' ) ? Input::String( 'override' ) : C::ES;
		$this->_type = $type;
		switch ( $this->get( 'task' ) ) {
			case 'view':
				$this->view( $override );
				break;
		}
		parent::display( $out );
	}
}
