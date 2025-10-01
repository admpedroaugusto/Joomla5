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
 * @modified 19 September 2023 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadController( 'section' );

use Sobi\C;
use Sobi\Input\Input;
use Sobi\FileSystem\FileSystem;

/**
 * Class SPCategoryCtrl
 */
class SPCategoryCtrl extends SPSectionCtrl
{
	/*** @var string */
	protected $_defTask = 'view';
	/*** @var string */
	protected $_type = 'category';

	/**
	 * @return bool
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function execute()
	{
		$retval = true;
		switch ( $this->_task ) {
			case 'chooser':
			case 'expand':
				SPLoader::loadClass( 'html.input' );
				$this->chooser( ( $this->_task == 'expand' ) );
				break;
			case 'parents':
				$this->parents();
				break;
			case 'icon':
				$this->iconChooser();
				break;
			case 'iconFonts':
				$this->iconFonts();
				break;
			default:
				/* in case parent hasn't registered this task, it is an error */
				/* now let the controller do the job */
				if ( !parent::execute() && $this->name() == __CLASS__ ) {
					Sobi::Error( $this->name(), SPLang::e( 'SUCH_TASK_NOT_FOUND', Input::Task() ), C::NOTICE, 404, __LINE__, __FILE__ );
					$retval = false;
				}
				break;
		}

		return $retval;
	}

	/**
	 * Image icon Chooser Popup in Category edit screen.
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function iconChooser()
	{
		if ( !Sobi::Can( 'category.edit' ) ) {
			Sobi::Error( 'category', 'You have no (longer) permission to access this site', C::ERROR, 403, __LINE__, __FILE__ );
		}
		if ( strlen( Input::Cmd( 'font' ) ) ) {
			$this->iconFont();

			return;
		}

		$folder = Input::String( 'iconFolder', 'request', C::ES );
		$directory = FileSystem::FixPath( Sobi::Cfg( 'images.category_icons' ) );
		$callback = Input::Cmd( 'callback', 'request', 'SPSelectIcon' );
		$directory = $folder ? $directory . str_replace( '.', '/', $folder ) . '/' : $directory;
		$showdir = str_replace( SOBI_ROOT, C::ES, $directory );
		$files = $dirs = [];
		if ( $folder ) {
			$up = explode( '.', $folder );
			unset( $up[ count( $up ) - 1 ] );
			$dirs[] = [
				'name'  => Sobi::Txt( 'FOLEDR_UP' ),
				'count' => count( scandir( $directory . '..' ) ) - 2,
				'url'   => Sobi::Url( [ 'task' => 'category.icon', 'out' => 'html', 'iconFolder' => ( count( $up ) ? implode( '.', $up ) : C::ES ) ] ),
			];
		}
		$ext = [ 'png', 'jpg', 'jpeg', 'gif', 'webp' ];
		if ( is_dir( $directory ) && ( $dh = opendir( $directory ) ) ) {
			while ( ( $file = readdir( $dh ) ) !== false ) {
				if ( ( filetype( $directory . $file ) == 'file' ) && in_array( strtolower( FileSystem::GetExt( $file ) ), $ext ) ) {
					$files[] = [
						'name' => $folder ? str_replace( '.', '/', $folder ) . '/' . $file : $file,
						'path' => str_replace( '\\', '/',
							str_replace( SOBI_ROOT, Sobi::Cfg( 'live_site' ),
								str_replace( '//', '/', $directory . $file )
							)
						),
					];
				}
				elseif ( filetype( $directory . $file ) == 'dir' && !( $file == '.' || $file == '..' ) ) {
					$dirs[] = [
						'name'  => $file,
						'count' => count( scandir( $directory . $file ) ) - 2,
						'path'  => str_replace( '\\', '/',
							str_replace( SOBI_ROOT, Sobi::Cfg( 'live_site' ),
								str_replace( '//', '/', $directory . $file )
							)
						),
						'url'   => Sobi::Url( [ 'task' => 'category.icon', 'out' => 'html', 'iconFolder' => ( $folder ? $folder . '.' . $file : $file ) ] ),
					];
				}
			}
			closedir( $dh );
		}
		sort( $files );
		sort( $dirs );
		$symbol = Sobi::Icon( 'folder-close', C::ES, false );

		/** @var SPCategoryView $view */
		$view = SPFactory::View( 'category' );
		$view
			->setTemplate( 'category.icon' )
			->assign( $this->_task, 'task' )
			->assign( $callback, 'callback' )
			->assign( $files, 'files' )
			->assign( $showdir, 'folder' )
			->assign( $symbol, 'symbol' )
			->assign( $dirs, 'directories' )
			->icon();
	}

	/**
	 * @throws SPException
	 */
	protected function iconFonts()
	{
		SPFactory::mainframe()
			->cleanBuffer()
			->customHeader();
		$fonts = Sobi::Cfg( 'template.icon_fonts_arr', [] );

		if ( count( $fonts ) ) {
			foreach ( $fonts as $i => $font ) {
				if ( strstr( $font, '-local' ) ) {
					$fonts[ $i ] = str_replace( '-local', C::ES, $font );
				}
			}
		}
		exit( json_encode( $fonts ) );
	}

	/**
	 * @throws \SPException
	 */
	protected function iconFont()
	{
		$font = Input::Cmd( 'font' );
		if ( strstr( $font, 'font-' ) ) {
			SPFactory::mainframe()
				->cleanBuffer()
				->customHeader();
			exit( FileSystem::Read( SPLoader::translatePath( 'etc.fonts.' . $font, 'front', true, 'json' ) ) );
		}
	}

	/**
	 * Shows the category chooser to select a parent category for a category.
	 *
	 * @param bool $menu
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function chooser( bool $menu = false )
	{
		$out = Input::Cmd( 'out' );
		$exp = Input::Int( 'expand', 'request', 0 );
		$multi = Input::Int( 'multiple', 'request', 0 );
		$tpl = Input::Word( 'treetpl', 'request' );
		/* load the SigsiuTree class */
		$tree = SPLoader::loadClass( 'mlo.tree' );
		$ordering = defined( 'SOBI_ADM_PATH' ) ? Sobi::GetUserState( 'categories.order', 'corder', 'position.asc' ) : Sobi::Cfg( 'list.categories_ordering' );
		/* create new instance */
		$tree = new $tree( $ordering );

		/* set link */
		if ( $menu ) {
			$tree->setId( 'sigsiu_tree_menu' );
			if ( defined( 'SOBIPRO_ADM' ) ) {
				$link = Sobi::Url( [ 'sid' => '{sid}' ], false, false, true );
			}
			else {
				$link = Sobi::Url( [ 'sid' => '{sid}' ] );
			}
		}
		else {
			$link = "javascript:SP_selectCat( '{sid}' )";
		}
		$tree->setHref( $link );

		/* set the task to expand the tree */
		$tree->setTask( 'category.chooser' );

		/* disable the category which is currently edited - category cannot be within itself */
		if ( !$multi ) {
			if ( Input::Sid() != Sobi::Section() ) {
				$tree->disable( Input::Sid() );
			}
			$tree->setPid( Input::Sid() );
		}
		else {
			$tree->disable( Sobi::Reg( 'current_section' ) );
		}

		/* case we are extending the existing tree */
		if ( $out == 'xml' && $exp ) {
			$pid = Input::Int( 'pid', 'request', 0 );
			$pid = $pid ? $pid : Input::Sid();
			$tree->setPid( $pid );
			$tree->disable( $pid );
			$tree->extend( $exp );
		}

		/* otherwise we are creating a new tree */
		else {
			/* init the tree for the current section */
			$tree->init( Sobi::Reg( 'current_section' ) );
			/* load model */
			if ( !$this->_model ) {
				$this->setModel( SPLoader::loadModel( 'category' ) );
			}
			/* create new view */
			$class = SPLoader::loadView( 'category' );
			$view = new $class();
			/* assign the task and the tree */
			$view->assign( $this->_task, 'task' );
			$view->assign( $tree, 'tree' );
			$view->assign( $this->_model, 'category' );
			/* select template to show */
			if ( $tpl ) {
				$view->setTemplate( 'category.' . $tpl );
			}
			elseif ( $multi ) {
				$view->setTemplate( 'category.mchooser' );
			}
			else {
				$view->setTemplate( 'category.chooser' );
			}

			Sobi::Trigger( 'Category', 'ChooserView', [ &$view ] );
			$view->chooser();
		}
	}

	/**
	 * AJAX (joomla-menu.js)
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function parents()
	{
//		sleep( 5 );

		$cats = [];
		$sid = Input::Sid();
		$path = SPFactory::config()->getParentPath( $sid, true, false, true );
		if ( count( $path ) ) {
			$childs = 0;
			foreach ( $path as $category ) {
				if ( $category[ 'id' ] == $sid ) {
					$childs = count( SPFactory::Category( $sid )->getChilds( 'category', false, 1 ) );
				}
				$cats[] = [ 'id' => $category[ 'id' ], 'name' => $category[ 'name' ], 'childsCount' => $childs ];
			}
		}

		switch ( Input::Cmd( 'out', 'request', 'json' ) ) {
			case 'json':
				SPFactory::mainframe()
					->cleanBuffer()
					->customHeader();
				echo json_encode( [ 'id' => $sid, 'categories' => $cats ] );

				exit;
		}
	}

	/**
	 * @param $sid
	 * @param bool $redirect
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function checkIn( $sid, $redirect = true )
	{
		parent::checkIn( Input::Int( 'category_id', 'request', $sid ), $redirect );
	}
}