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
 * @created 01 May 2023 by Sigrid Suski
 * @modified 15 September 2023 by Sigrid Suski
 */

namespace SobiPro\Helpers;

use Sobi;
use Sobi\C;
use Sobi\Error\Exception;
use Sobi\FileSystem\FileSystem;
use Sobi\Lib\Factory;

/**
 * Trait MenuTrait
 * @package SobiPro\Helpers
 */
trait MenuTrait
{
	protected array $coretasks = [
		'MENU.GLOBAL.CONFIG'      => [ 'config.global', 'filter', 'logs' ],
		'MENU.GLOBAL.ACL'         => [ 'acl' ],
		'MENU.GLOBAL.APPS'        => [ 'extensions.installed', 'extensions.browse' ],
		'MENU.GLOBAL.MAINTENANCE' => [ 'error', 'cleanup', 'requirements' ],
		'MENU.SECTION.ENT_CAT'    => [ 'section.entries' ],
		'MENU.SECTION.CONFIG'     => [ 'field.list', 'config.general', 'config.crawler' ],
		'MENU.SECTION.APPS'       => [ 'extensions.manage', 'bank_transfer', 'paypal' ],
	];

	protected array $globalCompatibilityTasks = [
		'MENU.GLOBAL.CONFIG'      => 'GB.CFG.GLOBAL_CONFIG',
		'MENU.GLOBAL.ACL'         => 'GB.ACL',
		'MENU.GLOBAL.APPS'        => 'GB.APPS',
		'MENU.GLOBAL.TEMPLATES'   => 'GB.CFG.GLOBAL_TEMPLATES',
		'MENU.GLOBAL.MAINTENANCE' => 'GB.MAINTENANCE',
	];
	protected array $sectionCompatibilityTasks = [
		'MENU.SECTION.ENT_CAT'   => 'AMN.ENT_CAT',
		'MENU.SECTION.CONFIG'    => 'AMN.SEC_CFG',
		'MENU.SECTION.IMEX-APP'  => 'AIMEX.MENU_HEAD',
		'MENU.SECTION.APPS'      => 'AMN.APPS_SECTION_HEAD',
		'MENU.SECTION.TEMPLATES' => 'AMN.APPS_SECTION_TPL',
	];

	/**
	 * Adds the core tasks to the menus and triggers the applications.
	 *
	 * @param string $task
	 * @param bool $addCorder
	 * @param int $currentCategory
	 *
	 * @return mixed|\SPDBObject
	 * @throws \SPException
	 * @throws Exception
	 */
	protected function & setMenuItems( string $task, bool $addCorder = false, int $currentCategory = 0 )
	{
		$section = Sobi::Section();
		/* load the menu definition */
		$definition = $section ? FileSystem::LoadIniFile( SOBI_PATH . '/etc/adm/section_menu' ) :
			FileSystem::LoadIniFile( SOBI_PATH . '/etc/adm/config_menu' );

		/* Add all menu items from applications (new menu structure) */
		Sobi::Trigger( 'Create', 'AdmMenu', [ &$definition ] );

		/* compatibility; can be removed if all apps use the new menu structure */
		$olddefinition = [];
		$compatibility = $section ? $this->sectionCompatibilityTasks : $this->globalCompatibilityTasks;
		foreach ( $definition as $index => $value ) {
			$olddefinition[ $compatibility[ $index ] ] = $value;
		}
		Sobi::Trigger( 'Create', 'AdmMenu', [ &$olddefinition ] );
		if ( count( $olddefinition ) ) {
			$definition = [];
			foreach ( $compatibility as $index => $value ) {
				if ( array_key_exists( $value, $olddefinition ) ) {
					$definition[ $index ] = $olddefinition[ $value ];
				}
			}
		}
		/* compatibility end */

		/** @var \SPAdmSiteMenu $menu */
		/* create the menu for a section */
		if ( $section ) {
			$menu =& \SPFactory::Instance( 'views.adm.menu', $task, Sobi::Section() );

			if ( count( $definition ) ) {
				foreach ( $definition as $root => $keys ) {
					$addRoot = false;
					switch ( $root ) {
						case 'MENU.SECTION.ENT_CAT':
							$addRoot = true;
							break;
						case 'MENU.SECTION.CONFIG':
						case 'MENU.SECTION.IMEX-APP':
						case 'MENU.SECTION.APPS':
						case 'MENU.SECTION.TEMPLATES':
							$addRoot = Sobi::Can( 'section.configure', '*' ) || Sobi::Can( 'cms.admin' );
							break;
					}
					if ( $addRoot ) {
						$menu->addSection( $root, $keys );
					}
				}
			}

			/* create new SigsiuTree */
			/** @var \SigsiuTree $tree */
			if ( $addCorder ) {
				$tree = \SPLoader::loadClass( 'mlo.tree' );
				$tree = new $tree( Sobi::GetUserState( 'categories.order', 'corder', 'position.asc' ) );
			}
			else {
				$tree = \SPFactory::Instance( 'mlo.tree' );
			}
			/* set link */
			$tree->setHref( Sobi::Url( [ 'sid' => '{sid}' ] ) );
			$tree->setId( 'sigsiu_tree_menu' );
			/* set the task to expand the tree */
			$tree->setTask( 'category.expand' );
			$tree->init( $section, $currentCategory, false );

			/* add the tree into the menu */
			$menu->addCustom( 'MENU.SECTION.ENT_CAT', $tree->getTree() );
		}
		else {
			/* create the global menu */
			$menu =& \SPFactory::Instance( 'views.adm.menu', $task );
			if ( count( $definition ) ) {
				foreach ( $definition as $section => $keys ) {
					switch ( $section ) {
						case 'MENU.GLOBAL.CONFIG': /* Global Configuration */
						case 'MENU.GLOBAL.TEMPLATES': /* Templates */
							if ( !( Sobi::Can( 'cms.admin' ) || Sobi::Can( 'cms.options' ) ) ) {
								continue 2;
							}
							break;
						case 'MENU.GLOBAL.ACL': /* ACL */
						case 'MENU.GLOBAL.MAINTENANCE':
							if ( !Sobi::Can( 'cms.admin' ) ) {
								continue 2;
							}
							break;
						case 'MENU.GLOBAL.APPS': /* Applications */
							if ( !( Sobi::Can( 'cms.admin' ) || Sobi::Can( 'cms.apps' ) ) ) {
								continue 2;
							}
							break;
					}
					$menu->addSection( $section, $keys );

					if ( ( $section == 'MENU.GLOBAL.TEMPLATES' ) && ( Sobi::Can( 'cms.admin' ) || Sobi::Can( 'cms.apps' ) ) ) {
						$menu->addCustom( 'MENU.GLOBAL.TEMPLATES', $this->listTemplates() );
					}
				}
			}
		}
		Sobi::Trigger( 'AfterCreate', 'AdmMenu', [ &$menu ] );

		return $menu;
	}

	/**
	 * @param array $menu
	 * @param string $newtask
	 * @param string $name
	 *
	 * @return void
	 */
	public function updateSectionEnCatMenu( array &$menu, string $newtask = C::ES, string $name = C::ES )
	{
		$this->updateMenu( $menu, [ 'menu' => 'MENU.SECTION.ENT_CAT', 'task' => $newtask, 'name' => $name ] );
	}

	/**
	 * @param array $menu
	 * @param string $newtask
	 * @param string $name
	 *
	 * @return void
	 */
	public function updateSectionConfigMenu( array &$menu, string $newtask = C::ES, string $name = C::ES )
	{
		$this->updateMenu( $menu, [ 'menu' => 'MENU.SECTION.CONFIG', 'task' => $newtask, 'name' => $name ] );
	}

	/**
	 * @param array $menu
	 * @param string $newtask
	 * @param string $name
	 *
	 * @return void
	 */
	public function updateSectionAppMenu( array &$menu, string $newtask = C::ES, string $name = C::ES )
	{
		$this->updateMenu( $menu, [ 'menu' => 'MENU.SECTION.APPS', 'task' => $newtask, 'name' => $name ] );
	}

	/**
	 * @param string $menu
	 * @param string $task
	 *
	 * @return bool
	 */
	public function isCoreTask( string $menu, string $task ): bool
	{
		foreach ( $this->coretasks as $menuId => $menutasks ) {
			if ( $menu == $menuId ) {
				foreach ( $menutasks as $menutask ) {
					if ( $task == $menutask ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * @param array $menu
	 * @param array $data
	 *
	 * @return void
	 */
	private function updateMenu( array &$menu, array $data )
	{
		/* if the Entries & Categories menu exists */
		if ( isset( $menu[ $data[ 'menu' ] ] ) ) {
			$menuItems = $menu[ $data[ 'menu' ] ];
			$newMenuItems = [];

			/* all SobiPro core items should be at the beginning */
			foreach ( $menuItems as $task => $name ) {
				/* list all SobiPro core items here */
				foreach ( $this->coretasks as $menuId => $menutasks ) {
					if ( $data[ 'menu' ] == $menuId ) {
						foreach ( $menutasks as $menutask ) {
							if ( $task == $menutask ) {
								$newMenuItems[ $task ] = $name;
								unset ( $menuItems[ $task ] );
							}
						}
					}
				}
			}
			/* add the new task to the list */
			$menuItems[ $data[ 'task' ] ] = $data[ 'name' ];

			/* sort all items */
			asort( $menuItems, SORT_STRING );

			/* add the SobiPro on top */
			$newMenuItems = array_merge( $newMenuItems, $menuItems );
			$menu[ $data[ 'menu' ] ] = $newMenuItems;
		}
	}
}
