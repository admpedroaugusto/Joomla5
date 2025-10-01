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
 * @modified 14 February 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

use Sobi\C;
use Sobi\Input\Input;

SPLoader::loadView( 'view', true );

/**
 * Class SPSectionAdmView
 */
class SPSectionAdmView extends SPAdmView
{
	/**
	 * @param $title
	 *
	 * @deprecated since 2.0
	 */
	public function setTitle( $title )
	{
	}

	/**
	 * @throws ReflectionException
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function display()
	{
		switch ( $this->get( 'task' ) ) {
			case 'view':
				$this->listSection();
				$this->determineTemplate( 'section', 'list' );
				break;
			case 'entries':
				$this->listSection();
				$this->determineTemplate( 'section', 'entries' );
				break;
		}
		parent::display();
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function listSection()
	{
		$parentPath = $this->parentPath( (int) Input::Sid() );
		$this->assign( $parentPath, 'current_path' );
		$this->_plgSect = '_SectionListTemplate';
		$categoriesList = $this->get( 'categories' );
		$categories = [];
		$entries = [];

		/* get users/authors data first */
		$usersData = [];
		if ( count( $categoriesList ) ) {
			foreach ( $categoriesList as $cat ) {
				$usersData[] = $cat->get( 'owner' );
			}
			reset( $categoriesList );
		}
		$usersData = $this->userData( $usersData );

		/* handle the categories */
		if ( count( $categoriesList ) ) {
			foreach ( $categoriesList as $cat ) {
				$category = [];
				/* data needed to display in the list */
				$category[ 'name' ] = $cat->get( 'name' );
				$category[ 'state' ] = $cat->get( 'state' );
				$category[ 'approved' ] = $cat->get( 'approved' );
				if ( isset( $usersData[ $cat->get( 'owner' ) ] ) ) {
					$userName = $usersData[ $cat->get( 'owner' ) ]->name;
					$userUrl = SPUser::userUrl( $usersData[ $cat->get( 'owner' ) ]->id );
					$category[ 'owner' ] = "<a href=\"$userUrl\">$userName</a>";
				}
				else {
					$category[ 'owner' ] = Sobi::Txt( 'GUEST' );
				}
				/* the rest - in case someone need */
				$category[ 'position' ] = $cat->get( 'position' );
				$category[ 'createdTime' ] = $cat->get( 'createdTime' );
				$category[ 'cout' ] = $cat->get( 'cout' );
				$category[ 'coutTime' ] = $cat->get( 'coutTime' );
				$category[ 'id' ] = $cat->get( 'id' );
				$category[ 'validSince' ] = $cat->get( 'validSince' );
				$category[ 'validUntil' ] = $cat->get( 'validUntil' );
				$category[ 'description' ] = $cat->get( 'description' );
				$category[ 'icon' ] = $cat->get( 'icon' );
				$category[ 'introtext' ] = $cat->get( 'introtext' );
				$category[ 'parent' ] = $cat->get( 'parent' );
				$category[ 'confirmed' ] = $cat->get( 'confirmed' );
				$category[ 'counter' ] = $cat->get( 'counter' );
				$category[ 'nid' ] = $cat->get( 'nid' );
				$category[ 'metaDesc' ] = $cat->get( 'metaDesc' );
				$category[ 'metaKeys' ] = $cat->get( 'metaKeys' );
				$category[ 'metaAuthor' ] = $cat->get( 'metaAuthor' );
				$category[ 'metaRobots' ] = $cat->get( 'metaRobots' );
				$category[ 'ownerIP' ] = $cat->get( 'ownerIP' );
				$category[ 'updatedTime' ] = $cat->get( 'updatedTime' );
				if ( $category[ 'updatedTime' ] == '0000-00-00 00:00:00' ) {
					$category[ 'updatedTime' ] = $category[ 'createdTime' ];
				}
				$category[ 'updater' ] = $cat->get( 'updater' );
				$category[ 'updaterIP' ] = $cat->get( 'updaterIP' );
				$category[ 'version' ] = $cat->get( 'version' );
				$category[ 'object' ] =& $cat;
				$categories[] = $category;
			}
		}
		/* re-assign the categories */
		$this->assign( $categories, 'categories' );

		/* handle the fields in this section for header (only necessary fields) */
		$headerFields = $this->get( 'fields' );

		$entriesOrdering = [
			Sobi::Txt( 'ORDER_BY' )                 => [],
			'e_sid.asc'                             => Sobi::Txt( 'ORDER_BY_ID_ASC' ),
			'e_sid.desc'                            => Sobi::Txt( 'ORDER_BY_ID_DESC' ),
			$this->get( 'entries_field' ) . '.asc'  => Sobi::Txt( 'ORDER_BY_NAME_ASC' ),
			$this->get( 'entries_field' ) . '.desc' => Sobi::Txt( 'ORDER_BY_NAME_DESC' ),
			'state.asc'                             => Sobi::Txt( 'ORDER_BY_STATE_ASC' ),
			'state.desc'                            => Sobi::Txt( 'ORDER_BY_STATE_DESC' ),
			'createdTime.asc'                       => Sobi::Txt( 'ORDER_BY_CREATION_DATE_ASC' ),
			'createdTime.desc'                      => Sobi::Txt( 'ORDER_BY_CREATION_DATE_DESC' ),
			'updatedTime.asc'                       => Sobi::Txt( 'ORDER_BY_UPDATE_DATE_ASC' ),
			'updatedTime.desc'                      => Sobi::Txt( 'ORDER_BY_UPDATE_DATE_DESC' ),
			'approved.asc'                          => Sobi::Txt( 'ORDER_BY_APPROVAL_ASC' ),
			'approved.desc'                         => Sobi::Txt( 'ORDER_BY_APPROVAL_DESC' ),
		];
		if ( $this->get( 'task' ) == 'view' ) {
			$entriesOrdering[ 'position.asc' ] = Sobi::Txt( 'ORDER_BY_POSITION_ASC' );
			$entriesOrdering[ 'position.desc' ] = Sobi::Txt( 'ORDER_BY_POSITION_DESC' );
		}
		$customFields = [];
		$customHeader = [];
		if ( count( $headerFields ) ) {
			/* @var SPField $field */
			foreach ( $headerFields as $field ) {
				if ( $field->get( 'enabled' ) ) {   /* only if field is enabled */
					if ( isset( $field->get( 'params' )[ 'numeric' ] ) && $field->get( 'params' )[ 'numeric' ] ) {
						$entriesOrdering[ Sobi::Txt( 'EMN.ORDER_BY_FIELD' ) ][ $field->get( 'nid' ) . '.num.asc' ] = '\'' . $field->get( 'name' ) . '\' ' . Sobi::Txt( 'ORDER_BY_ASC' );
						$entriesOrdering[ Sobi::Txt( 'EMN.ORDER_BY_FIELD' ) ][ $field->get( 'nid' ) . '.num.desc' ] = '\'' . $field->get( 'name' ) . '\' ' . Sobi::Txt( 'ORDER_BY_DESC' );
					}
					else {
						$entriesOrdering[ Sobi::Txt( 'EMN.ORDER_BY_FIELD' ) ][ $field->get( 'nid' ) . '.asc' ] = '\'' . $field->get( 'name' ) . '\' ' . Sobi::Txt( 'ORDER_BY_ASC' );
						$entriesOrdering[ Sobi::Txt( 'EMN.ORDER_BY_FIELD' ) ][ $field->get( 'nid' ) . '.desc' ] = '\'' . $field->get( 'name' ) . '\' ' . Sobi::Txt( 'ORDER_BY_DESC' );
					}
					$customFields[] = $field->get( 'nid' );
					$length = (int) Sobi::Cfg( 'entry.listfieldlength', 25 );
					$content = strlen( $field->get( 'name' ) ) > $length ? substr( $field->get( 'name' ), 0, $length ) . '...' : $field->get( 'name' );
					$customHeader[] = [
						'content' => $content,
						'attributes' => [ 'type' => 'text' ],
					];
				}
			}
		}
		$entriesOrdering[ 'owner.desc' ] = Sobi::Txt( 'ORDER_BY_OWNER' );
		$this->assign( $customHeader, 'customHeader' );
		$this->assign( $customFields, 'custom_fields' );
		$this->assign( $entriesOrdering, 'entriesOrdering' );

		/* handle the entries */
		$entriesList = $this->get( 'entries' );
		if ( count( $entriesList ) ) {
			/* get users/authors data first */
			$usersData = [];
			foreach ( $entriesList as $index => $sid ) {
				$entriesList[ $index ] = SPFactory::EntryRow( $sid );
				$usersData[] = $entriesList[ $index ]->get( 'owner' );
			}
			reset( $entriesList );
			$usersData = $this->userData( $usersData );
			foreach ( $entriesList as $sentry ) {
				/* @var SPEntryAdm $sentry */
				$entry = [];
				$entry[ 'state' ] = $sentry->get( 'state' );
				$entry[ 'approved' ] = $sentry->get( 'approved' );

				if ( isset( $usersData[ $sentry->get( 'owner' ) ] ) ) {
					$userName = $usersData[ $sentry->get( 'owner' ) ]->name;
					$userUrl = SPUser::userUrl( $usersData[ $sentry->get( 'owner' ) ]->id );
					$entry[ 'owner' ] = "<a href=\"$userUrl\">$userName</a>";
				}
				else {
					$entry[ 'owner' ] = Sobi::Txt( 'GUEST' );
				}
				$catPosition = $sentry->getCategories();
				if ( Input::Sid() && isset( $catPosition[ Input::Sid() ] ) ) {
					$sentry->position = $catPosition[ Input::Sid() ][ 'position' ];
				}
				/* the remaining data - case someone need */
				$primary = $sentry->getPrimary();
				$entry[ 'position' ] = $sentry->get( 'position' );
				$entry[ 'createdTime' ] = $sentry->get( 'createdTime' );
				$entry[ 'cout' ] = $sentry->get( 'cout' );
				$entry[ 'coutTime' ] = $sentry->get( 'coutTime' );
				$entry[ 'id' ] = $sentry->get( 'id' );
				$entry[ 'validSince' ] = $sentry->get( 'validSince' );
				$entry[ 'validUntil' ] = $sentry->get( 'validUntil' );
				$entry[ 'description' ] = $sentry->get( 'description' );
				$entry[ 'icon' ] = $sentry->get( 'icon' );
				$entry[ 'introtext' ] = $sentry->get( 'introtext' );
				$entry[ 'parent' ] = $sentry->get( 'parent' );
				$entry[ 'confirmed' ] = $sentry->get( 'confirmed' );
				$entry[ 'counter' ] = $sentry->get( 'counter' );
				$entry[ 'nid' ] = $sentry->get( 'nid' );
				$entry[ 'metaDesc' ] = $sentry->get( 'metaDesc' );
				$entry[ 'metaKeys' ] = $sentry->get( 'metaKeys' );
				$entry[ 'metaAuthor' ] = $sentry->get( 'metaAuthor' );
				$entry[ 'metaRobots' ] = $sentry->get( 'metaRobots' );
				$entry[ 'ownerIP' ] = $sentry->get( 'ownerIP' );
				$entry[ 'updatedTime' ] = $sentry->get( 'updatedTime' );
				if ( $entry[ 'updatedTime' ] == '0000-00-00 00:00:00' ) {
					$entry[ 'updatedTime' ] = $entry[ 'createdTime' ];
				}
				$entry[ 'updater' ] = $sentry->get( 'updater' );
				$entry[ 'updaterIP' ] = $sentry->get( 'updaterIP' );
				$entry[ 'version' ] = $sentry->get( 'version' );
				$entry[ 'valid' ] = $sentry->get( 'valid' ) ? 'valid' : 'is-invalid';
				$entry[ 'primary' ] = ( $primary != 0 ) ? ( $primary[ 'pid' ] == Input::Sid() ? 'primary' : '' ) : '';
				$entry[ 'object' ] =& $sentry;
				$entry[ 'name' ] = $sentry->get( 'name' );
				$entry[ 'name' ] .= !$sentry->get( 'valid' ) ? ' ' . Sobi::Txt( 'ENTRY_INVALID' ) : C::ES;

				$fields = $sentry->getFields();
				$entry[ 'fields' ] = $fields;

				/* fields data init */
				if ( count( $headerFields ) ) {
					foreach ( $headerFields as $field ) {
						$entry[ $field->get( 'nid' ) ] = null;
					}
				}
				/* now fill with the real data if any */
				if ( count( $fields ) ) {
					foreach ( $fields as $field ) {
						$entry[ $field->get( 'nid' ) ] = $field->data();
					}
				}
				if ( count( ( $customFields ) ) ) {
					foreach ( $customFields as $customField ) {
						$entry[ 'customFields' ][ $customField ] = $entry[ $customField ];
					}
				}
				$entries[] = $entry;
			}
		}

		$this->assign( $entries, 'entries' );
	}
}