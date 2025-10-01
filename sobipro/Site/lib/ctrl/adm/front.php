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
 * @created 10-Jan-2009 by Radek Suski
 * @modified 07 October 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadController( 'controller' );

use Sobi\C;
use Sobi\Input\Input;
use Sobi\FileSystem\File;
use Sobi\FileSystem\FileSystem;
use Sobi\Communication\CURL;
use Sobi\Lib\Factory;

/**
 * Class SPAdminPanel
 */
class SPAdminPanel extends SPController
{
	use SobiPro\Helpers\ConfigurationTrait;

	/**
	 * @var array
	 */
	private $_sections = [];
	/**
	 * @var string
	 */
	protected $_defTask = 'panel';
	/**
	 * @var string
	 */
	protected $_type = 'front';

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function getSections()
	{
		$sections = [];
		$order = $this->getOrdering();
		try {
			$sections = Factory::Db()
				->select( '*', 'spdb_object', [ 'oType' => 'section' ], $order )
				->loadObjectList();
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 500, __LINE__, __FILE__ );
		}
		if ( count( $sections ) ) {
			SPLoader::loadClass( 'models.datamodel' );
			SPLoader::loadClass( 'models.dbobject' );
			SPLoader::loadModel( 'section' );
			foreach ( $sections as $section ) {
				if ( Sobi::Can( 'section', 'access', '*', $section->id ) || Sobi::Can( 'section', 'configure', '*', $section->id ) ) {
					$s = new SPSection();
					$s->extend( $section );
					$this->_sections[] = $s;
				}
			}
		}
	}

	/**
	 * @return false|mixed|string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function getOrdering()
	{
		$order = Sobi::GetUserState( 'sections.order', 'order', 'name.asc' );

		$direction = 'asc';
		if ( strstr( $order, '.' ) ) {
			$order = explode( '.', $order );
			if ( count( $order ) == 3 ) {
				$direction = $order[ 1 ] . '.' . $order[ 2 ];
			}
			else {
				$direction = $order[ 1 ];
			}
			$order = $order[ 0 ];
		}

		switch ( $order ) {
			case 'position':
			case 'name':
				$db = Factory::Db();
				try {
					$fields = $db
						->select( 'id', 'spdb_language', [
							'oType'    => 'section',
							'sKey'     => 'name',
							'language' => Sobi::Lang() ], "sValue.$direction" )
						->loadResultArray();

					if ( !count( $fields ) && Sobi::Lang() != Sobi::DefLang() ) {
						$fields = $db
							->select( 'id', 'spdb_language', [
								'oType'    => 'section',
								'sKey'     => 'name',
								'language' => Sobi::DefLang() ], "sValue.$direction" )
							->loadResultArray();
					}
				}
				catch ( SPException $x ) {
					Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 500, __LINE__, __FILE__ );

					return false;
				}

				if ( count( $fields ) ) {
					$fields = implode( ',', $fields );
					$order = "field( id, $fields )";
				}
				else {
					$order = "id.$direction";
				}
				break;
			default:
				$order = isset( $direction ) && strlen( $direction ) ? "$order.$direction" : $order;
				break;
		}
		SPFactory::user()->setUserState( 'sections.order', $order );

		return $order;
	}

	/**
	 * @return bool
	 * @throws \ReflectionException
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 * @throws \Exception
	 */
	public function execute()
	{
		switch ( $this->_task ) {
			case 'panel':
				$this->getSections();

				/* for some reason, it does not work with SPC::MESSAGE_FILE */
				//$updatesFile = SOBI_PATH . '/' . SPC::MESSAGE_FILE;
				$updatesFile = SOBI_PATH . '/tmp/message.json';
				if ( file_exists( $updatesFile ) ) {
					$data = json_decode( file_get_contents( $updatesFile ), true );
					$content = "{$data[ 'count' ]} {$data[ 'text' ]}";
					SPFactory::message()
						->error( $content, false, true )
						->setSystemMessage();

					/* open the 'Version and Updates' pane */
					SPFactory::header()->addJsCode( 'SobiCore.Ready( () => { document.getElementById(\'spctrl-updates\').dispatchEvent( new Event( \'click\' ) ) } );' );
				}
				/** @var SPAdmPanelView $view */
				$news = $this->getNews();
				$ordering = Sobi::GetUserState( 'sections.order', 'order', 'name.asc' );
				$myVersion = Factory::ApplicationHelper()->myVersion( true );
				$cfg = Sobi::Cfg( 'cpanel.show_entries', true );
				$cfgCats = Sobi::Cfg( 'cpanel.show_categories', false );
				$cfgHistory = Sobi::Cfg( 'cpanel.show_history', true ) && Sobi::Cfg( 'logging.action', true );

				$fVersion = C::VERSION;
				$dirgiskedar = null;    /* do not change!! */

				$trim = defined( 'SOBI_TRIMMED' );
				$state = $this->getState();

				$view = SPFactory::View( 'front', true );
				$view
					->assign( $this->_sections, 'sections' )
					->assign( $trim, 'trim' )
					->assign( $news, 'news' )
					->assign( $ordering, 'order' )
					->assign( $myVersion, 'sobiproversion' )
					->assign( $fVersion, 'frameworkversion' )
					->assign( $dirgiskedar, 'dirgiskedarversion' )
					->assign( $cfg, 'show-entries' )
					->assign( $cfgCats, 'show-categories' )
					->assign( $cfgHistory, 'show-history' )
					->assign( $state, 'system-state' );

				if ( $cfg ) {
					$entries = $this->getEntries();
					$view->assign( $entries, 'entries' );
				}
				if ( $cfgCats ) {
					$categories = $this->getCategories();
					$view->assign( $categories, 'categories' );
				}
				if ( $cfgHistory ) {
					$history[ 'items' ] = $this->getHistory();
					if ( count( $history[ 'items' ] ) ) {
						$view->assign( $history, 'history' );
					}
				}

				Factory::Application()->loadLanguage( 'com_sobipro.about' );

				Sobi::Trigger( 'Dashboard', 'AdmView', [ &$view ] );

				$view
					->determineTemplate( 'front', 'cpanel' )
					->display();

				Sobi::Trigger( 'AfterDashboard', 'AdmView', [ &$view ] );
				break;

			case 'config':
				$IP = Input::Ip4( 'REMOTE_ADDR', 'SERVER', 0 );
				$trim = defined( 'SOBI_TRIMMED' );
				$setting = [
					'show_entries'      => Sobi::Cfg( 'cpanel.show_entries', true ),
					'number_entries'    => Sobi::Cfg( 'cpanel.number_entries', 5 ),
					'show_categories'   => Sobi::Cfg( 'cpanel.show_categories', false ),
					'number_categories' => Sobi::Cfg( 'cpanel.number_categories', 5 ),
					'show_history'      => Sobi::Cfg( 'cpanel.show_history', true ),
					'number_history'    => Sobi::Cfg( 'cpanel.number_history', 5 ),
					'show_faulty'       => Sobi::Cfg( 'cpanel.show_faulty', true ),
				];
				$admintemplates = [
					'entry_labelwidth' => Sobi::Cfg( 'admintemplate.entry_labelwidth', 3 ),
				];

				/** @var SPAdmView $view */
				$view = SPFactory::View( 'front', true );
				$view
					->addHidden( $IP, 'current-ip' )
					->assign( $trim, 'trim' )
					->assign( $setting, 'cpanel' )
					->assign( $admintemplates, 'admintemplate' );

				Sobi::Trigger( 'DashboardConfig', 'AdmView', [ &$view ] );

				$view
					->determineTemplate( 'front', 'config' )
					->display();

				Sobi::Trigger( 'AfterDashboardConfig', 'AdmView', [ &$view ] );
				break;

			case 'save':
			case 'apply':
				/* save the Dashboard configuration */
				$this->save( $this->_task == 'apply' );
				break;

			case 'clearmessages':
				SPFactory::message()->resetSystemMessages();
				$this->response( Sobi::Url( [ 'task' => 'panel' ] ), Sobi::Txt( 'CPANEL_ISSUES_CLEARED' ), false, C::SUCCESS_MSG );
				break;

			default:
				/* case plugin didn't register this task, it was an error */
				if ( !parent::execute() ) {
					Sobi::Error( $this->name(), SPLang::e( 'SUCH_TASK_NOT_FOUND', Input::Task() ), C::NOTICE, 404, __LINE__, __FILE__ );
				}
				break;
		}

		return true;
	}

	/**
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function getNews(): array
	{
		$out = [];
		$path = SPLoader::path( 'etc/news', 'front', false, 'xml' );
		if ( FileSystem::Exists( $path ) && ( time() - filemtime( $path ) < SPC::UPDATES_INTERVALL ) ) {
			$content = FileSystem::Read( $path );
		}
		else {
			try {
				$connection = new CURL();
				$errno = $connection->error( false, true );
				$status = $connection->status( false, true );

				/* if CURL initialisation failed (CURL not installed) */
				if ( $status || $errno ) {
					throw new SPException( 'Code ' . $status ? $connection->status() : $connection->error() );
				}

				$news = 'https://rss.sigsiu.net';
				$connection->setOptions(
					[
						'url'            => $news,
						'connecttimeout' => 10,
						'header'         => false,
						'returntransfer' => true,
					]
				);
				$file = new File( $path );
				$content = $connection->exec();
				$cinf = $connection->info();
				if ( isset( $cinf[ 'http_code' ] ) && $cinf[ 'http_code' ] != 200 ) {
					Sobi::Error( 'about', sprintf( 'CANNOT_GET_NEWS', $news, $cinf[ 'http_code' ] ), C::WARNING, 0, __LINE__, __FILE__ );

					return $out;

				}
				$file->content( $content );
				$file->save();
			}
			catch ( SPException $x ) {
				Sobi::Error( 'about', SPLang::e( 'CANNOT_LOAD_NEWS', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );

				return $out;
			}
		}
		try {
			$open = false;
			if ( strpos( $content, "DOCTYPE html" ) > 0 ) { // if no XML format (e.g. if SSL error)
				$content = C::ES;
			}
			if ( strlen( $content ) ) {
				$document = new DOMDocument();
				$document->loadXML( $content );
				$news = new DOMXPath( $document );

				$atom = false;  // to show an image, our RSS feeds are RSS 2.0 and not Atom!
				if ( $atom ) {    //Atom
					$news->registerNamespace( 'atom', 'http://www.w3.org/2005/Atom' );
					$out[ 'title' ] = $news->query( '/atom:feed/atom:title' )->item( 0 )->nodeValue;
					$items = $news->query( '/atom:feed/atom:entry[*]' );
					$counter = 5;
					foreach ( $items as $item ) {
						$date = $item->getElementsByTagName( 'updated' )->item( 0 )->nodeValue;
						if ( !$open && time() - strtotime( $date ) < ( 60 * 60 * 24 ) ) {
							$open = true;
						}
						$feed = [
							'url'     => $item->getElementsByTagName( 'link' )->item( 0 )->nodeValue,
							'title'   => $item->getElementsByTagName( 'title' )->item( 0 )->nodeValue,
							'content' => $item->getElementsByTagName( 'content' )->item( 0 )->nodeValue,
						];
						if ( !$counter-- ) {
							break;
						}
						$out[ 'feeds' ][] = $feed;
					}
				}
				else {  //RSS
					$out[ 'title' ] = $news->query( '/rss/channel/title' )->item( 0 )->nodeValue;
					$items = $news->query( '/rss/channel/item[*]' );
					$counter = 5;
					foreach ( $items as $item ) {
						/* if there is a news item younger than 3 days open the new pane automatically */
						$date = $item->getElementsByTagName( 'pubDate' )->item( 0 )->nodeValue;
						if ( !$open && time() - strtotime( $date ) < ( 60 * 60 * 72 ) ) {
							$open = true;
						}
						$feed = [
							'url'     => $item->getElementsByTagName( 'link' )->item( 0 )->nodeValue,
							'title'   => $item->getElementsByTagName( 'title' )->item( 0 )->nodeValue,
							'content' => $item->getElementsByTagName( 'description' )->item( 0 )->nodeValue,
							'image'   => $item->getElementsByTagName( 'enclosure' )->item( 0 )->attributes->getNamedItem( 'url' )->nodeValue,
						];
						if ( !$counter-- ) {
							break;
						}
						$out[ 'feeds' ][] = $feed;
					}
				}
			}
			if ( $open && !file_exists( SOBI_PATH . '/tmp/message.json' ) ) {
				SPFactory::header()->addJsCode( 'SobiCore.Ready( () => { document.getElementById(\'spctrl-news\').dispatchEvent( new Event( \'click\' ) ) } );' );
			}
		}
		catch ( DOMException $x ) {
			Sobi::Error( 'about', SPLang::e( 'CANNOT_LOAD_NEWS', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}

		return $out;
	}

	/**
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function getEntries(): array
	{
		$entries = SPFactory::cache()->getObj( 'cpanel_entries', -1 );
		if ( $entries && is_array( $entries ) ) {
			return $entries;
		}
		$entries = [];
		$count = 20;
		$popular = Factory::Db()
			->select( 'id', 'spdb_object', [ 'oType' => 'entry' ], 'counter.desc', $count )
			->loadResultArray();
		$entries[ 'popular' ] = $this->addEntries( $popular );
		$latest = Factory::Db()
			->select( 'id', 'spdb_object', [ 'oType' => 'entry' ], 'createdTime.desc', $count )
			->loadResultArray();
		$entries[ 'latest' ] = $this->addEntries( $latest );
		$unapproved = Factory::Db()
			->select( 'id', 'spdb_object', [ 'oType' => 'entry', 'approved' => 0 ], 'createdTime.desc', $count )
			->loadResultArray();
		$entries[ 'unapproved' ] = $this->addEntries( $unapproved );
		SPFactory::cache()->addObj( $entries, 'cpanel_entries', -1 );

		return $entries;
	}

	/**
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function getCategories(): array
	{
		$categories = SPFactory::cache()->getObj( 'cpanel_categories', -1 );
		if ( $categories && is_array( $categories ) ) {
			return $categories;
		}
		$categories = [];
		$count = 20;
		$popular = Factory::Db()
			->select( 'id', 'spdb_object', [ 'oType' => 'category' ], 'counter.desc', $count )
			->loadResultArray();
		$categories[ 'popular' ] = $this->addCategories( $popular );
		$latest = Factory::Db()
			->select( 'id', 'spdb_object', [ 'oType' => 'category' ], 'createdTime.desc', $count )
			->loadResultArray();
		$categories[ 'latest' ] = $this->addCategories( $latest );
		SPFactory::cache()->addObj( $categories, 'cpanel_categories', -1 );

		return $categories;
	}

	/**
	 * Displays the last history items in Dashboard.
	 *
	 * @return array
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function getHistory(): array
	{
		$history = SPFactory::cache()->getObj( 'cpanel_history', -1 );
		if ( $history && is_array( $history ) ) {
			return $history;
		}
		$history = SPFactory::history()->getHistorySentence( Sobi::Cfg( 'cpanel.number_history', 5 ) );
		SPFactory::cache()->addObj( $history, 'cpanel_history', -1 );

		return $history;
	}

	/**
	 * @param $ids
	 *
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function addEntries( $ids ): array
	{
		static $sections = [];
		$entries = [];
		$count = Sobi::Cfg( 'cpanel.number_entries', 5 );
		if ( count( $ids ) ) {
			$counter = 0;
			foreach ( $ids as $sid ) {
				$counter++;
				if ( $counter > $count ) {
					break;
				}
				$entry = SPFactory::EntryRow( $sid );
				$section = $entry->get( 'section' );
				if ( !$section ) {
					$counter--;
					continue;
				}

				/* Check if user has right to the sections and the entry is valid (has categories assigned and a name) */
				if ( !( Sobi::Can( 'section', 'access', 'any', $section ) && $entry->get( 'valid' ) ) ) {
					$counter--;
					continue;
				}

				if ( !isset( $sections[ $section ] ) ) {
					$sections[ $section ] = SPFactory::Section( $section );
				}
				$entry->setProperty( 'section', $sections[ $section ] );
				$entries[] = $entry;
			}
		}

		return $entries;
	}

	/**
	 * @param $ids
	 *
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function addCategories( $ids ): array
	{
		static $sections = [];
		$categories = [];
		$count = Sobi::Cfg( 'cpanel.number_categories', 5 );
		if ( count( $ids ) ) {
			$counter = 0;
			foreach ( $ids as $sid ) {
				$counter++;
				if ( $counter > $count ) {
					break;
				}
				$section = SPFactory::config()->getParentPathSection( $sid );
				if ( !$section ) {
					$counter--;
					continue;
				}
				if ( !Sobi::Can( 'section', 'access', 'any', $section ) ) {
					$counter--;
					continue;
				}
				$category = SPFactory::Category( $sid );
				if ( !isset( $sections[ $section ] ) ) {
					$sections[ $section ] = SPFactory::Section( $section );
				}
				$category->setProperty( 'section', $sections[ $section ] );
				$categories[] = $category;
			}
		}


		return $categories;
	}

	/**
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function getState(): array
	{
		$state = SPFactory::cache()->getVar( 'system_state' );
		if ( !$state ) {
			Factory::Application()->loadLanguage( 'com_sobipro.messages' );
			$state = [];
			$state[ 'accelerator' ] = [
				'type'  => Sobi::Cfg( 'cache.l3_enabled', true ) ? 'success' : 'danger',
				'label' => Sobi::Cfg( 'cache.l3_enabled', true ) ? Sobi::Txt( 'ACCELERATOR_ENABLED' ) : Sobi::Txt( 'ACCELERATOR_DISABLED' ),
			];
			$state[ 'xml-optimiser' ] = [
				'type'  => Sobi::Cfg( 'cache.xml_enabled', true ) ? 'success' : 'danger',
				'label' => Sobi::Cfg( 'cache.xml_enabled', true ) ? Sobi::Txt( 'XML_CACHE_ENABLED' ) : Sobi::Txt( 'XML_CACHE_DISABLED' ),
			];
			$state[ 'javascript-cache' ] = [
				'type'  => Sobi::Cfg( 'cache.include_js_files', false ) ? 'success' : 'warning',
				'label' => Sobi::Cfg( 'cache.include_js_files', false ) ? Sobi::Txt( 'JS_CACHE_ENABLED' ) : Sobi::Txt( 'JS_CACHE_DISABLED' ),
			];
			$state[ 'css-cache' ] = [
				'type'  => Sobi::Cfg( 'cache.include_css_files', false ) ? 'success' : 'warning',
				'label' => Sobi::Cfg( 'cache.include_css_files', false ) ? Sobi::Txt( 'CSS_CACHE_ENABLED' ) : Sobi::Txt( 'CSS_CACHE_DISABLED' ),
			];
			$state[ 'js-compress' ] = [
				'type'  => Sobi::Cfg( 'cache.compress_js', false ) ? 'success' : 'warning',
				'label' => Sobi::Cfg( 'cache.compress_js', false ) ? Sobi::Txt( 'JS_COMPRESS_ENABLED' ) : Sobi::Txt( 'JS_COMPRESS_DISABLED' ),
			];
			$state[ 'display-errors' ] = [
				'type'  => Sobi::Cfg( 'debug.display_errors', false ) ? 'danger' : 'success',
				'label' => Sobi::Cfg( 'debug.display_errors', false ) ? Sobi::Txt( 'DISPLAY_ERRORS_ENABLED' ) : Sobi::Txt( 'DISPLAY_ERRORS_DISABLED' ),
			];
			$state[ 'debug-level' ] = [
				'type'  => Sobi::Cfg( 'debug.level', 0 ) > 2 ? 'warning' : 'success',
				'label' => Sobi::Cfg( 'debug.level', 0 ) > 2 ? Sobi::Txt( 'DEBUG_LEVEL_TOO_HIGH' ) : Sobi::Txt( 'DEBUG_LEVEL_OK' ),
			];
			$xmlraw = Factory::Db()
				->select( 'COUNT(section)', 'spdb_config', [ 'sKey' => 'xml_raw', 'sValue' => 1, 'section>' => 0 ] )
				->loadResult();
			$state[ 'debug-xml' ] = [
				'type'  => $xmlraw ? 'danger' : 'success',
				'label' => $xmlraw ? Sobi::Txt( 'DEBUG_XML_ENABLED' ) : Sobi::Txt( 'DEBUG_XML_DISABLED' ),
			];
			if ( $xmlraw > 0 ) {
				$xmlraw = Factory::Db()
					->select( 'section', 'spdb_config', [ 'sKey' => 'xml_raw', 'sValue' => 1, 'section>' => 0 ] )
					->loadResultArray();
				if ( count( $xmlraw ) ) {
					foreach ( $xmlraw as $index => $section ) {
						if ( Sobi::Can( 'section', 'configure', '*', $section ) ) {
							$xmlraw[ $index ] = "<a href=\"index.php?option=com_sobipro&task=config.general&pid=$section#secn-cfg-template-data!#secn-cfg-general-settings!#spcfg-debug-xml-raw\">$section</a>";
						}
						else {
							if ( !( Sobi::Can( 'section', 'access', 'any', $section ) ) ) {
								unset( $xmlraw[ $index ] );
							}
						}
					}
				}
				$state[ 'debug-xml' ][ 'label' ] .= ' ' . Sobi::Txt( 'CPANEL_SECTONS' ) . ': ' . implode( ', ', $xmlraw );
			}

			if ( !Sobi::Cfg( 'cpanel.show_faulty', false ) ) {
				foreach ( $state as $type => $status ) {
					if ( $status[ 'type' ] == 'success' ) {
						unset( $state[ $type ] );
					}
				}
			}

//			uasort( $state, array( $this, 'sortMessages' ) );
			$messages = SPFactory::message()->getSystemMessages();
			if ( count( $messages ) ) {
				foreach ( $messages as $message ) {
					if ( Sobi::Can( 'section', 'configure', '*', $message[ 'section' ][ 'id' ] ) ) {
						$url = Sobi::Url( [ 'sid' => $message[ 'section' ][ 'id' ] ] );
						$url = "<a href=\"$url\">{$message['section']['name']}</a> ";
					}
					else {
						$url = $message[ 'section' ][ 'name' ];
					}
					$message[ 'section' ][ 'link' ] = $url;
					$message[ 'type-text' ] = ucfirst( Sobi::Txt( $message[ 'type' ] ) );
					$state[ 'messages' ][] = $message;
				}
			}
			SPFactory::cache()->addVar( $state, 'system_state' );
		}

		return $state;
	}

	/**
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function save( $apply = false, $clone = false )
	{
		if ( !( Sobi::Can( 'cms.admin' ) || Sobi::Can( 'cms.options' ) ) ) {
			Sobi::Error( $this->name(), SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ), C::ERROR, 403, __LINE__, __FILE__ );
			exit;
		}
		//$this->validate( 'config.global', [ 'task' => 'config.global' ] );
		$data = Input::Arr( 'spcfg', 'request', [] );

		[ $values, $section ] = $this->prepareConfiguration( $data );

		Sobi::Trigger( 'SaveConfig', $this->name(), [ &$values ] );
		try {
			Factory::Db()->insertArray( 'spdb_config', $values, true );
		}
		catch ( Sobi\Error\Exception $x ) {
			$this->response( Sobi::Back(), $x->getMessage(), false, C::ERROR_MSG );
		}

		Sobi::Trigger( 'After', 'SaveConfig', [ &$values ] );
		SPFactory::cache()->cleanSection( -1 );

		$this->response( Sobi::Back(), Sobi::Txt( 'MSG.CONFIG_SAVED' ), !$apply, C::SUCCESS_MSG );
	}

//	private function sortMessages( $first, $second )
//	{
//		$return = 0;
//		if ( $first[ 'type' ] != $second[ 'type' ] ) {
//			switch( $first[ 'type' ] ) {
//				case 'danger':
//					$return = -1;
//					break;
//				case 'warning':
//					$return = $second[ 'type' ] == 'danger' ? 1 : -1;
//					break;
//				case 'success':
//					$return = 1;
//			}
//		}
//		return $return;
//	}
}
