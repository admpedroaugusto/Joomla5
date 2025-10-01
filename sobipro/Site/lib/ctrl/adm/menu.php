<?php
/**
 * @package SobiPro Library
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006-2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according to section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created Sat, Nov 30, 2013 by Radek Suski
 * @modified 13 November 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadController( 'file' );

use Sobi\C;
use Sobi\Input\Input;
use Sobi\FileSystem\FileSystem;
use Sobi\FileSystem\Directory;
use Sobi\Lib\Factory;

/**
 * Class SPMenuAdm
 * For SobiPro item creation in Joomla Menu.
 */
class SPMenuAdm extends SPController
{
	/**
	 * @var null
	 */
	protected $menu = null;

	/**
	 * @return void
	 * @throws \ReflectionException
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function execute()
	{
		$function = Input::Cmd( 'function' );
		SPFactory::header()->addJsFile( [ 'bootstrap.utilities.typeahead', 'adm.joomla-menu' ] );
		Factory::Application()->loadLanguage( 'com_sobipro.sys' );
		if ( !$function ) {
			$this->listFunctions();
		}
		else {
			$this->loadFunction( $function );
		}
	}

	/**
	 * @param $function
	 *
	 * @throws \ReflectionException
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function loadFunction( $function )
	{
		$xml = new DOMDocument();
		$xml->load( SPLoader::path( "menu.$function", 'adm.templates', true, 'xml' ) );
		$xpath = new DOMXPath( $xml );
		$this->loadLanguage( $xpath );
		$calls = $xpath->query( '/definition/config/calls/call' );

		$this->menu = Factory::Db()
			->select( '*', '#__menu', [ 'id' => Input::Int( 'mid' ) ] )
			->loadObject();
		if ( isset( $this->menu->params ) ) {
			$this->menu->params = json_decode( $this->menu->params );
			if ( isset( $this->menu->params->sobiprosettings ) ) {
				$this->menu->params->sobiprosettings = json_decode( base64_decode( $this->menu->params->sobiprosettings ) );
			}
		}
		/** @var SPAdmView $view */
		$view = SPFactory::View( 'joomla-menu', true );
		$view->assign( $this->menu, 'joomlaMenu' );
		$section = Input::Int( 'section' );
		if ( $calls->length ) {
			foreach ( $calls as $file ) {
				$method = $file->attributes->getNamedItem( 'method' )->nodeValue;
				if ( $file->attributes->getNamedItem( 'static' ) && $file->attributes->getNamedItem( 'static' )->nodeValue == 'true' ) {
					$class = SPLoader::loadClass( $file->attributes->getNamedItem( 'file' )->nodeValue );
					$class::$method( $view, $this->menu );
				}
				else {
					$obj = SPFactory::Instance( $file->attributes->getNamedItem( 'file' )->nodeValue );
					$obj->$method( $view, $this->menu );
				}
			}
		}
		$view->assign( $section, 'sectionId' )
			->determineTemplate( 'menu', $function )
			->display();
	}

	/**
	 * @param $view
	 * @param $menu
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function calendar( &$view, $menu )
	{
		$months = [ null => Sobi::Txt( 'FMN.HIDDEN_OPT' ) ];
		$years = [ null => Sobi::Txt( 'FD.SEARCH_SELECT_LABEL' ) ];
		$days = [ null => Sobi::Txt( 'FMN.HIDDEN_OPT' ) ];

		$monthsNames = Sobi::Txt( 'JS_CALENDAR_MONTHS' );
		$monthsNames = explode( ',', $monthsNames );

		$query = [];
		if ( count( (array) $view->get( 'joomlaMenu' ) ) ) {
			$link = $view->get( 'joomlaMenu' )->link;
			parse_str( $link, $query );
		}
		$selected = [ 'year' => null, 'month' => null, 'day' => null ];
		if ( isset( $query[ 'date' ] ) ) {
			$date = explode( '.', $query[ 'date' ] );
			$selected[ 'year' ] = isset( $date[ 0 ] ) && $date[ 0 ] ? $date[ 0 ] : null;
			$selected[ 'month' ] = isset( $date[ 1 ] ) && $date[ 1 ] ? $date[ 1 ] : null;
			$selected[ 'day' ] = isset( $date[ 2 ] ) && $date[ 2 ] ? $date[ 2 ] : null;
		}
		else {
			$query[ 'date' ] = '';
		}

		for ( $i = 1; $i < 13; $i++ ) {
			$months[ $i ] = $monthsNames[ $i - 1 ];
		}

		for ( $i = 1; $i < 32; $i++ ) {
			$days[ $i ] = $i;
		}

		$exYears = Factory::Db()
			->dselect( 'EXTRACT( YEAR FROM createdTime )', 'spdb_object' )
			->loadResultArray();
		if ( count( $exYears ) ) {
			foreach ( $exYears as $year ) {
				$years[ $year ] = $year;
			}
		}
		$view
			->assign( $years, 'years' )
			->assign( $months, 'months' )
			->assign( $selected, 'date' )
			->assign( $days, 'days' );
		$this->addTemplates( $view, $menu, 'list.date' );

		SPFactory::header()->addJsCode(
			'document.addEventListener( "DOMContentLoaded", function( event )
				{
					SobiPro.jQuery( ".spctrl-save", window.parent.document )
						.click( function( e )
							{
								if( SobiPro.jQuery( "#date-year" ).val() == "" ) {
									e.preventDefault();
									e.stopPropagation();
									alert( "' . Sobi::Txt( 'SOBI_DATE_LISTING_MISSING' ) . '" );
									SobiPro.jQuery( "#spctrl-selected-function", window.parent.document ).html( "' . Sobi::Txt( 'SOBI_SELECT_FUNCTIONALITY' ) . '" );
							}
						} ); 
				} );'
		);
	}

	/**
	 * @param SPAdmJoomlaMenuView $view
	 * @param $menu
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function entryForm( &$view, $menu )
	{
		$section = Input::Int( 'section' );
		$tree = $this->initialiseTree();
		$tree->setId( 'sigsiu_tree_jmenu_entry' );
		$tree->init( $section );
		$view->assign( $tree, 'tree' );
		$this->addTemplates( $view, $menu, 'entry' );
	}

	/**
	 * @param $view
	 * @param $menu
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function entry( &$view, $menu )
	{
		$this->addTemplates( $view, $menu, 'entry' );
		SPFactory::header()->addJsCode(
			'
			SobiCore.Ready( () =>
			{
				window.parent.document.getElementById( "spctrl-modal" ).addEventListener( "hide.bs.modal", ( e ) => {
					if( SobiPro.jQuery( "#SP_function-name" ).val() == "MENU_LINK_TO_SELECTED_ENTRY" ) {
						e.preventDefault();
						alert( "' . Sobi::Txt( 'MENU_LINK_TO_ENTRY_MISSING' ) . '" );
						SobiPro.jQuery( "#spctrl-selected-function", window.parent.document ).html( "' . Sobi::Txt( 'SOBI_SELECT_FUNCTIONALITY' ) . '" );								
					}
				} );
 			} );'
		);
	}


	/**
	 * @param SPAdmJoomlaMenuView $view
	 * @param $menu
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function search( &$view, $menu )
	{
		$this->addTemplates( $view, $menu, 'search' );
	}

	/**
	 * @param SPAdmJoomlaMenuView $view
	 * @param $menu
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function section( &$view, $menu )
	{
		$this->addTemplates( $view, $menu, 'section' );
	}

	/**
	 * @param SPAdmJoomlaMenuView $view
	 * @param $menu
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function user( &$view, $menu )
	{
		$this->addTemplates( $view, $menu, 'list.user' );
	}

	/**
	 *
	 */
	public function calendarLabel()
	{
	}

	/**
	 * @param $sid
	 *
	 * @return mixed|null
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	public function categoryLabel( $sid )
	{
		return $sid ? Sobi::Txt( 'MENU_LINK_TO_SELECTED_CATEGORY', SPFactory::Category( $sid )->get( 'name' ) ) : C::ES;
	}

	/**
	 * @param $sid
	 *
	 * @return mixed
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function entryLabel( $sid )
	{
		return Sobi::Txt( 'MENU_LINK_TO_SELECTED_ENTRY', SPFactory::Entry( $sid )->get( 'name' ) );
	}

	/**
	 * @param $sid
	 * @param $section
	 *
	 * @return mixed
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function entryFormLabel( $sid, $section )
	{
		return Sobi::Txt( 'MENU_LINK_TO_ADD_ENTRY_FORM_SELECTED', $section == $sid ? SPFactory::Section( $sid )->get( 'name' ) : SPFactory::Category( $sid )->get( 'name' ) );
	}

	/**
	 * @param SPAdmJoomlaMenuView $view
	 * @param $menu
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function category( &$view, $menu )
	{
		$section = Input::Int( 'section' );
		$tree = $this->initialiseTree();
		$tree->setId( 'sigsiu_tree_jmenu_category' );
		$tree->disable( $section );
		$tree->init( $section );
		$view->assign( $tree, 'tree' );
		$this->addTemplates( $view, $menu, 'category' );
		SPFactory::header()->addJsCode(
			'
			document.addEventListener( "DOMContentLoaded", function( event )
			{
				SobiPro.jQuery( ".spctrl-save", window.parent.document )
						.click( function( e )
							{
								if( SobiPro.jQuery( "#SP_function-name" ).val() == "MENU_LINK_TO_SELECTED_CATEGORY" ) {
									e.preventDefault();
									e.stopPropagation();
									alert( "' . Sobi::Txt( 'MENU_CAT_FUNCTION_SELECT_CAT_FIRST' ) . '" );
									SobiPro.jQuery( "#spctrl-selected-function", window.parent.document ).html( "' . Sobi::Txt( 'SOBI_SELECT_FUNCTIONALITY' ) . '" );
							}
						} ); 
			} );'
		);
	}

	/**
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function listFunctions()
	{
		$directory = new Directory( SPLoader::dirPath( 'menu', 'adm.templates' ) );
		$files = $directory->searchFile( '.xml', false );
		$functions = [];
		if ( count( $files ) ) {
			foreach ( $files as $file ) {
				$path = $file->getPathInfo();
				$functions[ $path[ 'filename' ] ] = $this->functionDetails( $file );
			}
		}
		/** Mon, Aug 24, 2015 10:24:24 - put the section link on the top */
		$section = $functions[ 'section' ];
		unset( $functions[ 'section' ] );
		$functions = array_merge( [ 'section' => $section ], $functions );
		$functions = array_merge( [ 'null' => Sobi::Txt( 'SOBI_SELECT_FUNCTIONALITY' ) ], $functions );

		/** @var SPAdmView $view */
		SPFactory::View( 'joomla-menu', true )
			->assign( $functions, 'functions' )
			->functions();
	}

	/**
	 * @param $file
	 *
	 * @return mixed
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function functionDetails( $file )
	{
		$xml = new DOMDocument();
		$xml->load( $file->getPathname() );
		$xpath = new DOMXPath( $xml );
		$title = $xpath->query( '/definition/header/title' )
			->item( 0 )
			->attributes
			->getNamedItem( 'value' )
			->nodeValue;
		$this->loadLanguage( $xpath );

		return Sobi::Txt( $title );
	}

	/**
	 * @param $xpath
	 *
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function loadLanguage( $xpath )
	{
		$files = $xpath->query( '/definition/header/file[@type="language"]' );
		if ( $files->length ) {
			foreach ( $files as $file ) {
				Factory::Application()->loadLanguage( $file->attributes->getNamedItem( 'filename' )->nodeValue );
			}
		}
	}

	/**
	 * @return \SigsiuTree
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function initialiseTree(): SigsiuTree
	{
		/** @var SigsiuTree $tree */
		$tree = SPFactory::Instance( 'mlo.tree', Sobi::GetUserState( 'categories.order', 'corder', 'position.asc' ) );
		$tree->setHref( "javascript:SP_selectCat( '{sid}' )" );
		$tree->setTask( 'category.chooser' );

		return $tree;
	}

	/**
	 * @param $type
	 *
	 * @return array
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function getTemplates( $type ): array
	{
		$templates = [];
		$templates[ '' ] = Sobi::Txt( 'SELECT_TEMPLATE_OVERRIDE' );
		$template = Factory::Db()
			->select( 'sValue', 'spdb_config', [ 'section' => Input::Int( 'section' ), 'sKey' => 'template', 'cSection' => 'section' ] )
			->loadResult();
		$templateDir = $this->templatePath( $template );
		$this->listTemplates( $templates, $templateDir, $type );

		return $templates;
	}

	/**
	 * @param $tpl
	 *
	 * @return false|string
	 * @throws \SPException
	 */
	protected function templatePath( $tpl )
	{
		$file = explode( '.', $tpl );
		if ( strstr( $file[ 0 ], 'cms:' ) ) {
			$file[ 0 ] = str_replace( 'cms:', C::ES, $file[ 0 ] );
			$file = SPFactory::mainframe()->path( implode( '.', $file ) );
			$template = SPLoader::path( $file, 'root', false, C::ES );
		}
		else {
			$template = SOBI_PATH . '/usr/templates/' . str_replace( '.', '/', $tpl );
		}

		return $template;
	}

	/**
	 * @param $arr
	 * @param $path
	 * @param $type
	 */
	protected function listTemplates( &$arr, $path, $type )
	{
		$stdTemplates = [ 'view.xsl', 'details.xsl', 'edit.xsl' ];
		switch ( $type ) {
			case 'entry':
			case 'entry.add':
			case 'section':
			case 'category':
			case 'search':
				$path = FileSystem::FixPath( $path . '/' . $type );
				break;
			case 'list.user':
			case 'list.date':
				$path = FileSystem::FixPath( $path . '/listing' );
				break;
			default:
				if ( strstr( $type, 'list' ) ) {
					$path = FileSystem::FixPath( $path . '/listing' );
				}
				break;
		}
		if ( file_exists( $path ) ) {
			$files = scandir( $path );
			if ( count( $files ) ) {
				foreach ( $files as $file ) {
					if ( in_array( $file, $stdTemplates ) ) {
						continue;
					}
					$stack = explode( '.', $file );
					if ( array_pop( $stack ) == 'xsl' ) {
						if ( strpos( $file, 'ajax' ) === false ) {   // files containing the term 'ajax' are no selectable template files
							$arr[ $stack[ 0 ] ] = $file;
						}
					}
				}
			}
		}
	}

	/**
	 * @param $view
	 * @param $menu
	 * @param $type
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function addTemplates( &$view, $menu, $type )
	{
		$templates = $this->getTemplates( $type );
		$view->assign( $templates, 'templates' );
		$query = [];
		if ( isset( $menu->link ) ) {
			$link = $menu->link;
			parse_str( $link, $query );
		}
		if ( isset( $query[ 'sptpl' ] ) ) {
			$view->assign( $query[ 'sptpl' ], 'template' );
		}
	}
}