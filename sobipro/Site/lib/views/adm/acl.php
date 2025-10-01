<?php
/**
 * @package: SobiPro Library
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2020 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 14-Jan-2009 by Radek Suski
 * @modified 23 July 2021 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadView( 'view', true );

/**
 * Class SPAclView
 */
class SPAclView extends SPAdmView
{
	/**
	 * @throws ReflectionException
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function display()
	{
		switch ( $this->get( 'task' ) ) {
			case 'add':
			case 'edit':
				$this->edit();
				$this->determineTemplate( 'acl', 'edit' );
				break;
		}
		parent::display();
	}

	/**
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function edit()
	{
		$put = [];
		$get = $this->get( 'groups' );
		foreach ( $get as $group ) {
			$put[ $group[ 'value' ] ] = $group[ 'text' ];
		}
		$this->set( $put, 'groups' );
		$put = [];
		$get = $this->get( 'sections' );
		if ( is_array( $get ) && count( $get ) ) {
			foreach ( $get as $section ) {
				$put[] = $section->id;
			}
			$put = Sobi::Txt( $put, 'name', 'section' );
			foreach ( $put as $id => $vals ) {
				$put[ $id ] = $vals[ 'value' ];
			}
		}
		$this->set( $put, 'sections' );
		$put = [];
		$get = $this->get( 'adm_permissions' );
		if ( is_array( $get ) && count( $get ) ) {
			foreach ( $get as $permission ) {
				$subject = ucfirst( $permission->subject );
				if ( !isset( $put[ $subject ] ) ) {
					$put[ $subject ] = [];
				}
				$k = $permission->action . '_' . $permission->value;
				$put[ $subject ][ $permission->pid ] = Sobi::Txt( 'ADM_PERMISSIONS.' . strtoupper( $k ) );
			}
		}
		$put = array_reverse( $put );
		$this->set( $put, 'adm_permissions' );
		$put = [];
		$rule = $this->get( 'set' );
		$get = $this->get( 'permissions' );

		//Sorting of the entry permissions
		$ePerms = [];
		$perms = [ 'all', 'access', 'add', 'edit', 'adm_fields', 'delete', 'publish', 'manage', 'payment' ];
		foreach ( $perms as $perm ) {
			foreach ( $get as $key => $permission ) {
				if ( $permission->subject == 'entry' ) {
					if ( $permission->action == $perm ) {  //first select all access permissions
						$ePerms[] = $permission;
						unset( $get[ $key ] );
					}
				}
			}
		}
		//Sorting of the review permissions
		$rPerms = [];
		$perms = [ 'see', 'add', 'edit', 'delete', 'autopublish', 'manage' ];
		foreach ( $perms as $perm ) {
			foreach ( $get as $key => $permission ) {
				if ( $permission->subject == 'review' ) {
					if ( $permission->action == $perm ) {  //first select all access permissions
						$rPerms[] = $permission;
						unset( $get[ $key ] );
					}
				}
			}
		}

		foreach ( $get as $permission ) {
			$subject = ucfirst( $permission->subject );
			if ( !isset( $put[ $subject ] ) ) {
				$put[ $subject ] = [];
			}
			$k = $permission->action . '_' . $permission->value;
			$put[ $subject ][ $permission->pid ] = Sobi::Txt( 'PERMISSIONS.' . strtoupper( $k ) );
		}
		foreach ( $ePerms as $permission ) {
			if ( !isset( $put[ 'Entry' ] ) ) {
				$put[ 'Entry' ] = [];
			}
			$k = $permission->action . '_' . $permission->value;
			$put[ 'Entry' ][ $permission->pid ] = Sobi::Txt( 'PERMISSIONS.' . strtoupper( $k ) );
		}
		foreach ( $rPerms as $permission ) {
			if ( !isset( $put[ 'Review' ] ) ) {
				$put[ 'Review' ] = [];
			}
			$k = $permission->action . '_' . $permission->value;
			$put[ 'Review' ][ $permission->pid ] = Sobi::Txt( 'PERMISSIONS.' . strtoupper( $k ) );
		}

		// default ordering for section and category
		$permissionsOrder = [
			'Section'  => [ 3, 4 ],
			'Category' => [ 8, 7 ]
			//			'Entry'    => [ 9, 11, 10, 14, 15, 12, 16, 17, 18, 20, 21, 19, 24, 25 ]
		];

		// to show current
		$permissions = [];
		foreach ( $permissionsOrder as $subject => $ordering ) {
			foreach ( $ordering as $pid ) {
				$permissions[ $subject ][ $pid ] = $put[ $subject ][ $pid ];
				unset( $put[ $subject ][ $pid ] );
			}
			// if still something left - add this too
			if ( is_array( $put[ $subject ] ) && count( $put[ $subject ] ) ) {
				foreach ( $put[ $subject ] as $pid => $label ) {
					$permissions[ $subject ][ $pid ] = $label;
				}
			}
			unset( $put[ $subject ] );
		}
		// if still something left - add this too (subjects)
		if ( is_array( $put ) && count( $put ) ) {
			foreach ( $put as $subject => $perms ) {
				$permissions[ $subject ] = $perms;
			}
		}
		$this->set( $permissions, 'permissions' );
		$sections = [];
		$perms = [];
		if ( count( $rule[ 'permissions' ] ) ) {
			foreach ( $rule[ 'permissions' ] as $keys ) {
				$sections[] = $keys[ 'sid' ];
				$perms[] = $keys[ 'pid' ];
			}
		}
		$rule[ 'sections' ] = $sections;
		$rule[ 'permissions' ] = $perms;
		$this->set( $rule, 'set' );
	}

	/**
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function section()
	{
		$permissions = $this->get( 'permissions' );
		$table = [];
		foreach ( $permissions as $subject => $rules ) {
			$table[] = [ 'label' => $subject ];
			foreach ( $rules as $rule ) {
				$table[] = [ 'label' => $rule ];
			}
		}
		$this->assign( $table, 'table' );
	}

	/**
	 * @param $title
	 *
	 * @deprecated since 2.0
	 */
	public function setTitle( $title )
	{
	}
}
