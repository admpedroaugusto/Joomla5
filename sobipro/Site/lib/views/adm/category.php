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
 * @modified 25 September 2023 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

use Sobi\Input\Input;
use Sobi\FileSystem\FileSystem;

SPLoader::loadView( 'section', true );

/**
 * Class SPCategoryAdmView
 */
class SPCategoryAdmView extends SPSectionAdmView
{
	/**
	 * @throws ReflectionException
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function display()
	{
		switch ( $this->get( 'task' ) ) {
			case 'edit':
				$languages = $this->languages();
				$this->assign( $languages, 'languages-list' );
				$multiLang = Sobi::Cfg( 'lang.multimode', false );
				$this->assign( $multiLang, 'multilingual' );
				/* no break intentionally */
			case 'add':
				$this->edit();
				$this->determineTemplate( 'category', 'edit' );
				break;
			case 'chooser':
				$this->chooser();
				break;
		}
		parent::display();
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function edit()
	{
		$pid = $this->get( 'category.parent' );
		if ( !$pid ) {
			$pid = Input::Int( 'pid' );
		}
		$this->assign( $pid, 'parent' );
		$id = $this->get( 'category.id' );
		if ( $id ) {
			$this->addHidden( $id, 'category.id' );
		}
		if ( $this->get( 'category.icon' ) && !( strstr( $this->get( 'category.icon' ), 'font' ) ) ) {
			if ( $this->get( 'category.icon' ) && FileSystem::Exists( Sobi::Cfg( 'images.category_icons' ) . '/' . $this->get( 'category.icon' ) ) ) {
				$icon = FileSystem::FixUrl( Sobi::Cfg( 'images.category_icons_live' ) . $this->get( 'category.icon' ) );
				$this->assign( $icon, 'category_icon' );
			}
			else {
				$icon = FileSystem::FixUrl( Sobi::Cfg( 'images.category_icons_live' ) . Sobi::Cfg( 'icons.default_selector_image', 'image.png' ) );
				$this->assign( $icon, 'category_icon' );
			}
		}
		/* if editing - get the full path. Otherwise, get the path of the parent element */
		$id = $id ? : $pid;
		if ( $this->get( 'category.id' ) ) {
			$path = $this->parentPath( (int) $id );
			$parentCat = $this->parentPath( (int) $id, false, true );
		}
		else {
			$path = $this->parentPath( Input::Sid() );
			$parentCat = $this->parentPath( Input::Sid(), false, true, 1 );
		}
		$this->assign( $path, 'parent_path' );
		$this->assign( $parentCat, 'parent_cat' );
		if ( Input::Sid() ) {
			$catChooserURL = Sobi::Url( [ 'task' => 'category.chooser', 'sid' => Input::Sid(), 'out' => 'html' ], true );
			$this->assign( $catChooserURL, 'cat_chooser_url' );
		}
		elseif ( Input::Int( 'pid' ) ) {
			$catUrl = Sobi::Url( [ 'task' => 'category.chooser', 'pid' => Input::Int( 'pid' ), 'out' => 'html' ], true );
			$this->assign( $catUrl, 'cat_chooser_url' );
		}
		$iconChooserUrl = Sobi::Url( [ 'task' => 'category.icon', 'out' => 'html' ], true );
		$this->assign( $iconChooserUrl, 'icon_chooser_url' );
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function chooser()
	{
		$pid = $this->get( 'category.parent' );
		$path = null;
		if ( !$pid ) {
			$pid = Input::Sid();
		}
		$this->assign( $pid, 'parent' );
		$id = $this->get( 'category.id' );
		$id = $id ? : $pid;
		if ( $id ) {
			$path = $this->parentPath( (int) $id );
		}
		$this->assign( $path, 'parent_path' );
		$ajaxUrl = Sobi::Url( [ 'task' => 'category.parents', 'out' => 'json', 'format' => 'raw' ], true );
		$this->assign( $ajaxUrl, 'parent_ajax_url' );
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
