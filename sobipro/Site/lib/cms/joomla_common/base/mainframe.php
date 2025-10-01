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
 * as published by the Free Software Foundation, and under the additional terms according to section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 10-Jan-2009 by Radek Suski
 * @modified 02 September 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Language\Text as JText;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Filesystem\Folder;
use Sobi\C;
use Sobi\Utils\StringUtils;
use Sobi\Input\Input;
use Sobi\FileSystem\FileSystem;
use Sobi\Lib\Factory;

/**
 * Interface between SobiPro and the used CMS.
 *
 * Class SPJoomlaMainFrame
 */
class SPJoomlaMainFrame /*implements SPMainframeInterface*/
{
	/** @var bool */
	public static $cs = false;
	/** @var string */
	public const baseUrl = "index.php?option=com_sobipro";
	/** @var array */
	protected $pathway = [];

	/**
	 * SPJoomlaMainFrame constructor.
	 */
	public function __construct()
	{
		if ( self::$cs ) {
			Sobi::Error( 'mainframe', SPLang::e( 'CRITICAL_SECTION' ), C::ERROR, 500, __LINE__, __CLASS__ );
		}
		else {
			self::$cs = true;
			self::$cs = false;
		}
	}

	/**
	 * @param $path
	 *
	 * @return array|string
	 */
	public function path( $path )
	{
		$path = explode( '.', $path );
		$sp = explode( ':', $path[ 0 ] );
		$type = $sp[ 0 ];
		unset( $sp[ 0 ] );
		$path[ 0 ] = implode( '', $sp );

		switch ( $type ) {
			case 'templates':
				$path = $type . implode( '.', $path );
				break;
		}

		return $path;
	}

	/**
	 * Gets basic data from the CMS (e.g. Joomla) and stores in the #SPConfig instance.
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 * @throws \Exception
	 */
	public function getBasicCfg()
	{
		$cfg = SPFactory::config();
		$cfg->set( 'live_site', Uri::root() );
		$cfg->set( 'live_site_root', Uri::getInstance()->toString( [ 'scheme', 'host', 'port' ] ) );
		$cfg->set( 'tmp_path', $this->getConfigValue( 'config.tmp_path' ) );
		$cfg->set( 'from', $this->getConfigValue( 'config.mailfrom' ), 'mail' );
		$cfg->set( 'mailer', $this->getConfigValue( 'config.mailer' ), 'mail' );
		$cfg->set( 'fromname', $this->getConfigValue( 'config.fromname' ), 'mail' );
		$cfg->set( 'smtpauth', $this->getConfigValue( 'config.smtpauth' ), 'mail' );
		$cfg->set( 'smtphost', $this->getConfigValue( 'config.smtphost' ), 'mail' );
		$cfg->set( 'smtpuser', $this->getConfigValue( 'config.smtpuser' ), 'mail' );
		$cfg->set( 'smtppass', $this->getConfigValue( 'config.smtppass' ), 'mail' );
		$cfg->set( 'smtpsecure', $this->getConfigValue( 'config.smtpsecure' ), 'mail' );
		$cfg->set( 'smtpport', $this->getConfigValue( 'config.smtpport' ), 'mail' );

		$cfg->set( 'unicode', $this->getConfigValue( 'unicodeslugs' ), 'sef' );

		$lang = $this->getConfigValue( 'language' );
		if ( !$lang ) {
			$lang = Input::Cmd( 'language' );
		}
		$cfg->set( 'language', $lang );
		$cfg->set( 'secret', $this->getConfigValue( 'secret' ) );
		$cfg->set( 'site_name', $this->getConfigValue( 'config.sitename' ) );

		$cfg->set( 'media_folder', SOBI_MEDIA );
		$cfg->set( 'media_folder_live', SOBI_MEDIA_LIVE );

		$cfg->set( 'images_folder', SOBI_IMAGES );
		$cfg->set( 'images_folder_live', SOBI_IMAGES_LIVE );
		/* backward compatibility */
		$cfg->set( 'img_folder_live', SOBI_IMAGES_LIVE );

		$cfg->set( 'ftp_mode', $this->getConfigValue( 'config.ftp_enable' ) );
		$cfg->set( 'time_offset', $this->getConfigValue( 'offset' ) );

		$cfg->set( 'root_path', SOBI_PATH );
		$cfg->set( 'cms_root_path', SOBI_ROOT );
		$cfg->set( 'live_path', SOBIPRO_FOLDER );

		$cfg->set( 'temp-directory', SOBI_PATH . '/tmp/' );

		/* this path does not exist, commented 11.5.21, Sigrid */
//		if ( defined( 'SOBIPRO_ADM' ) ) {
//			$cfg->set( 'adm_img_folder_live', FileSystem::FixUrl( JURI::root() .  SOBI_ADM_FOLDER . '/images' ) );
//		}
		///same as media_folder
		/// $cfg->set( 'img_folder_path', SOBI_ROOT . '/media/sobipro' );

		if ( $this->getConfigValue( 'config.ftp_enable' ) ) {
			if ( !file_exists( $this->getConfigValue( 'config.tmp_path' ) . '/SobiPro' ) ) {
				if ( !( @mkdir( $this->getConfigValue( 'config.tmp_path' ) . '/SobiPro' ) ) ) {
					Folder::create( $this->getConfigValue( 'config.tmp_path' ) . '/SobiPro', 0775 );
				}
			}
			$cfg->set( 'temp', $this->getConfigValue( 'config.tmp_path' ) . '/SobiPro', 'fs' );
		}
		else {
			$cfg->set( 'temp', SOBI_PATH . '/tmp', 'fs' );
		}
//		if ( Sobi::Cfg( 'image.jpeg_quality', false ) ) {
//			$cfg->set( 'quality', Sobi::Cfg( 'image.jpeg_quality' ), 'image' );
//		}

		// try mkdir because it's always used by apache
		if ( !Sobi::Cfg( 'cache.store', false ) ) {
			if ( $this->getConfigValue( 'config.ftp_enable' ) ) {
				if ( !file_exists( $this->getConfigValue( 'config.tmp_path' ) . '/SobiPro/Cache' ) ) {
					if ( !mkdir( $this->getConfigValue( 'config.tmp_path' ) . '/SobiPro/Cache' ) ) {
						// really ;)
						if ( !Folder::create( $this->getConfigValue( 'config.tmp_path' ) . '/SobiPro/Cache', 0775 ) ) {
							SPFactory::message()->setSilentSystemMessage( SPLang::e( 'CANNOT_CREATE_CACHE_DIRECTORY' ), C::ERROR_MSG );
						}

					}
				}
				$cfg->set( 'store', $this->getConfigValue( 'config.tmp_path' ) . '/SobiPro/Cache/' );
			}
		}
	}

	/**
	 * Gets a configuration value from the used CMS.
	 *
	 * @param $value
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	protected function getConfigValue( $value )
	{
		$value = str_replace( 'config.', C::ES, $value );

		return Factory::Application()->getCfg( $value );
	}

	/**
	 * @return SPJoomlaMainFrame
	 */
	public static function & getInstance()
	{
		static $mainframe = false;
		if ( !$mainframe || !( $mainframe instanceof self ) ) {
			$mainframe = new self();
		}

		return $mainframe;
	}

	/**
	 * @static
	 *
	 * @param string $msg -> The error message, which may also be shown the user if need be.
	 * @param int $code -> The application-internal error code for this error
	 * @param string $info -> Optional: Additional error information (usually only developer-relevant information that the user should never see, like a database DSN).
	 * @param bool $translate
	 *
	 * @throws Exception
	 */
	public function runAway( string $msg, $code = 500, $info = [], bool $translate = false )
	{
		$msg = $translate ? JText::_( $msg ) : $msg;
		$msg = str_replace( SOBI_PATH, C::ES, $msg );
		$msg = str_replace( SOBI_ROOT, C::ES, $msg );

		throw new Exception( $msg, $code );
	}

	/**
	 * @return mixed|string
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 * @throws \Exception
	 */
	public function getBack()
	{
		$backUrl = Sobi::GetUserState( 'back_url', self::url() );
		if ( !$backUrl ) {
			$backUrl = Input::String( 'HTTP_REFERER', 'SERVER', self::url() );
		}

		return $backUrl;
	}

	/**
	 * @param $add
	 * @param string|array $msg -> The message, which may also be shown the user if need be.
	 * @param string $msgtype
	 * @param bool $now
	 * @param int $code
	 *
	 * @throws Exception
	 */
	public function setRedirect( $add, $msg = C::ES, $msgtype = 'message', $now = false, $code = 302 )
	{
		if ( is_array( $msg ) ) {
			$msgtype = $msg[ 'msgtype' ] ?? C::ES;
			$msg = $msg[ 'msg' ];
		}
		$address = [ 'address' => $add, 'msg' => $msg, 'msgtype' => $msgtype ];
		SPFactory::registry()->set( 'redirect', $address );
		if ( $now ) {
			self::redirect( $code );
		}
	}

	/**
	 * @param string|array $msg -> The message, which may also be shown to the user if needs to.
	 * @param string $type
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function msg( $msg, $type = C::ES )
	{
		if ( is_array( $msg ) ) {
			$type = isset( $msg[ 'msgtype' ] ) && strlen( $msg[ 'msgtype' ] ) ? $msg[ 'msgtype' ] : C::ES;
			$msg = isset( $msg[ 'msg' ] ) && strlen( $msg[ 'msg' ] ) ? $msg[ 'msg' ] : C::ES;
		}
		SPFactory::message()->setMessage( $msg, true, $type );
	}

	/**
	 * @throws Exception
	 */
	public function proceedMessageQueue()
	{
		JFactory::getSession()->set( 'application.queue', JFactory::getApplication()->getMessageQueue() );
	}

	/**
	 * @param int $code -> HTTP response code
	 *
	 * @throws Exception
	 */
	public function redirect( $code = 302 )
	{
		$redirectAddress = SPFactory::registry()->get( 'redirect' );
		if ( $redirectAddress && isset( $redirectAddress[ 'address' ] ) ) {
			$redirectAddress[ 'address' ] = str_replace( '&amp;', '&', $redirectAddress[ 'address' ] );
			/** Sat, Oct 4, 2014 15:22:44
			 * Here is something wrong. The method redirect do not get the message and type params
			 * Instead we're sending a "moved" param which results with a 303 redirect code */
//			JFactory::getApplication()
//					->redirect( $redirectAddress[ 'address' ], $msg, $type );
			$msg = isset( $redirectAddress[ 'msg' ] ) && strlen( $redirectAddress[ 'msg' ] ) ? Sobi::Txt( $redirectAddress[ 'msg' ] ) : C::ES;
			if ( $msg ) {
				$type = $redirectAddress[ 'msgtype' ];
			}
			else {
				$type = 'message';
			}
			if ( $msg ) {
				SPFactory::message()->setMessage( $msg, false, $type );
			}
//			JFactory::getApplication()->enqueueMessage( $msg, $type );
			JFactory::getApplication()->redirect( $redirectAddress[ 'address' ], $code );

		}
	}

	/**
	 * @param $url
	 *
	 * @return string
	 * @throws SPException
	 */
	public function NormalizeUrl( $url )
	{
		$normUrl = C::ES;
		$nonsefUrl = Sobi::Url( $url, false, false, false );
		$query = parse_url( $nonsefUrl );
		if ( is_array( $query ) && count( $query ) ) {
			parse_str( $query[ 'query' ], $vars );
			if ( isset( $vars[ 'option' ] ) ) {
				$normUrl = $query[ 'path' ] . '?';
				$normUrl .= 'option=' . $vars[ 'option' ];

				if ( isset( $vars[ 'task' ] ) ) {
					$normUrl .= '&task=' . $vars[ 'task' ];
				}
				if ( isset( $vars[ 'tag' ] ) ) {
					$normUrl .= '&tag=' . $vars[ 'tag' ];
				}
				if ( isset( $vars[ 'date' ] ) ) {
					$normUrl .= '&date=' . $vars[ 'date' ];
				}
				if ( isset( $vars[ 'sid' ] ) ) {
					$normUrl .= '&sid=' . $vars[ 'sid' ];
				}
				if ( isset( $vars[ 'sptpl' ] ) ) {
					$normUrl .= '&sptpl=' . $vars[ 'sptpl' ];
				}
				if ( isset( $vars[ 'Itemid' ] ) ) {
					$normUrl .= '&Itemid=' . $vars[ 'Itemid' ];
				}
			}
		}

		return $normUrl;
	}

	/**
	 * @param $url
	 *
	 * @return string
	 * @throws Exception
	 */
	public function getMenuLink( $url )
	{
		$path = $this->NormalizeUrl( $url );

		// check if there is a Joomla menu item to the date listing
		$menu = JFactory::getApplication()->getMenu();
		$items = $menu->getMenu();

		$url = preg_replace( '/&Itemid=\d+/', C::ES, Sobi::Url( $url, true, false, false ) );
		foreach ( $items as $i => $item ) {
			if ( $item->link == $url ) {
				$path = $item->link;
				break;
			}
		}

		return $path;
	}

	/**
	 * @param $name
	 * @param $url
	 *
	 * @return $this|SPJoomlaMainFrame
	 * @throws Exception
	 */
	public function & addToPathway( $name, $url ): self
	{
		if ( defined( 'SOBI_ADM_PATH' ) ) {
			//return true;
			return $this;
		}
		$query = parse_url( $url );
		if ( is_array( $query ) && count( $query ) && isset( $query[ 'query' ] ) && strstr( $query[ 'query' ], 'crawl' ) ) {
			parse_str( $query[ 'query' ], $vars );
			unset( $vars[ 'format' ] );
			unset( $vars[ 'crawl' ] );
			$query[ 'query' ] = count( $vars ) ? http_build_query( $vars ) : null;
			if ( $query[ 'query' ] ) {
				$url = $query[ 'path' ] . '?' . $query[ 'query' ];
			}
			else {
				$url = $query[ 'path' ];
			}
		}
		$menu = isset( JFactory::getApplication()->getMenu()->getActive()->link ) ? JFactory::getApplication()->getMenu()->getActive()->link : C::ES;
		$menu = $this->NormalizeUrl( $menu );

//		$a = preg_replace( '/&Itemid=\d+/', null, str_replace( '/', C::ES, $url ) ); // warum alle Slashes entfernen ???
		$a = preg_replace( '/&Itemid=\d+/', C::ES, $url );

// the original code will always get un-equal because 1) comparison is between a sef and a non-sef url and 2) comparison is between an url without and with slashes!!!!!
		if ( $menu != $a ) {    // if equal, only the Joomla menu title is used
			JFactory::getApplication()
				->getPathway()
				->addItem( $name, $url );
			$this->pathway[] = [ 'name' => $name, 'url' => $url ];
		}

		return $this;
	}

	/**
	 * @return array
	 */
	public function getPathway()
	{
		return $this->pathway;
	}

	/**
	 * @param $action
	 * @param $params
	 *
	 * @throws Exception
	 */
	public function trigger( $action, &$params )
	{
		switch ( $action ) {
			case 'ParseContent':
				if ( !defined( 'SOBIPRO_ADM' ) ) {
					$params[ 0 ] = HTMLHelper::_( 'content.prepare', $params[ 0 ] );
				}
				break;
			default:
//				if ( class_exists( 'Joomla\CMS\Factory' ) ) {
				JFactory::getApplication()->triggerEvent( $action, $params );
//				}
//				else {  // for Joomla < 3.8.0
//					JEventDispatcher::getInstance()->trigger( $action, $params );
//				}
				break;
		}
	}

	/**
	 * Adds the object to the pathway
	 *
	 * @param SPDBObject $obj
	 * @param array $site
	 *
	 * @return $this
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 * @throws \Exception
	 */
	public function & addObjToPathway( $obj, $site = [] )
	{
		if ( defined( 'SOBI_ADM_PATH' ) ) {
			//return true;
			return $this;
		}
		$active = JFactory::getApplication()->getMenu()->getActive();   //get the active Joomla menu item
		$menu = $active->query ?? [];
		$menuSid = $menu[ 'sid' ] ?? 0;
		$resetPathway = false;      // do not remove pathway already add by Joomla
		$fullPathway = Sobi::Cfg( 'pathway.full_pathway', true );

		/* if it is the pathway to an entry */
		if ( $obj->get( 'oType' ) == 'entry' ) {
			// $id = Input::Int( 'pid' );      // unfortunately this is not set;  4.9.19
			/* if we have not entered this entry via category (not longer valid; 4.9.19) */
			//if ( !( $id ) || $id == Sobi::Section() || Sobi::Cfg( 'entry.primary_path_always' ) ) { // 'entry.primary_path_always' no longer necessary
			$id = $obj->get( 'parent' );
			$resetPathway = true;
			//}
			/** if it is linked in the Joomla! menu we have nothing to do (< 1.5) */
//			if ( ( $obj->get( 'id' ) == $menuSid ) && !( $fullPathway ) ) {
			if ( ( $obj->get( 'id' ) == $menuSid ) ) {

				/** OK, here is the weird thing:
				 * When it is accessed via the menu, we have to force the cache to create another version
				 * because the pathway is stored in the cache
				 * @todo find better solution for it
				 */
				$mid = true;
				SPFactory::registry()
					->set( 'cache_view_recreate_request', $mid )
					->set( 'cache_view_add_itemid', JFactory::getApplication()->getMenu()->getActive()->id );

				if ( !$fullPathway ) {
					return $this;   // if it is linked in the Joomla! menu we have nothing to do if $fullPathway id false (old behaviour)
				}
			}
		}

		// it's not the pathway to an entry
		else {
			$id = $obj->get( 'id' );
		}

		$path = [];
		if ( $id ) {
			$path = SPFactory::cache()->getVar( 'parent_path', $id );
			if ( !$path ) {
				$path = SPFactory::config()->getParentPath( $id, true, false, true );
				SPFactory::cache()->addVar( $path, 'parent_path', $id );
			}
		}
		$sectionPath = [];

		if ( is_array( $path ) && count( $path ) ) {
			foreach ( $path as $part ) { // start from the end
				if ( isset( $part[ 'id' ] ) && $part[ 'id' ] == Sobi::Section() ) {
					$sectionPath = $part;
					break;
				}
			}
		}
		$resetPathway = $fullPathway || $resetPathway;
		if ( is_array( $path ) && count( $path ) ) {
			if ( !$fullPathway ) {
				/* skip everything before the linked sid */
				$rpath = array_reverse( $path );    // start evaluating with last item
				$path = [];
				foreach ( $rpath as $part ) { // start from the end
					if ( isset( $part[ 'id' ] ) && $part[ 'id' ] == $menuSid ) {
						break;
					}
					$path[] = $part;
				}
				if ( $obj->get( 'oType' ) == 'entry' && count( $path ) > 1 ) {
					$path[] = $sectionPath;
				}
				$path = array_reverse( $path );     // reverse the order again
				/* ^^ skip everything before the linked sid */
			}
		}

		$title = [];
		// if there was an active menu - add its title to the browser title as well
		if ( $menuSid && !$fullPathway ) {
			$title[] = JFactory::getDocument()->getTitle();
		}

		if ( is_array( $path ) && count( $path ) ) {
			if ( $resetPathway ) {
				/** we have to reset the J! pathway in case:
				 *  - we are entering an entry, and we want to show the pathway corresponding to the main parent id of the entry
				 *    but we have also an Itemid and Joomla! set already the pathway partially so we need to override it
				 *    It wouldn't be normally a problem but when SEF is enabled we do not have the pid so we don't know how it has been entered
				 */
				JFactory::getApplication()
					->getPathway()
					->setPathway( [] );
			}
			foreach ( $path as $data ) {
				if ( !( isset( $data[ 'name' ] ) || isset( $data[ 'id' ] ) ) || !( $data[ 'id' ] ) ) {
					continue;
				}
				/* remove the section name if not the main section view */
				if ( Sobi::Section( true ) != $data[ 'name' ] || count( $path ) == 1 ) {
					$title[] = $data[ 'name' ];
				}
				$this->addToPathway( $data[ 'name' ],
					self::url( [ 'title' => Sobi::Cfg( 'sef.alias', true ) ? $data[ 'alias' ] : $data[ 'name' ],
					             'sid'   => $data[ 'id' ] ]

					)
				);
			}
		}
		if ( $obj->get( 'oType' ) == 'entry' ) {
			$task = Input::Task();
			$name = $obj->get( 'name' );
			if ( $task == 'entry.edit' ) {
				$name = Sobi::Txt( 'EN.EDIT_EXISTING_ENTRY', $name );
			}
			$this->addToPathway( $name,
				self::url( [ 'task'  => $task,
				             'title' => Sobi::Cfg( 'sef.alias', true ) ? $obj->get( 'nid' ) : $obj->get( 'name' ),
				             'sid'   => $obj->get( 'id' ) ]
				)
			);
			$title[] = $obj->get( 'name' );
		}
//		if ( count( $site ) && $site[ 0 ] ) {
//			$title[ ] = Sobi::Txt( 'SITES_COUNTER', $site[ 1 ], $site[ 0 ] );
//		}
		if ( count( $title ) && $title[ 0 ] != null ) {
			SPFactory::header()->addTitle( $title, $site );
		}

		return $this;
	}

	/**
	 * Adds our header data to the document.
	 *
	 * @param array $head
	 * @param bool $afterRender
	 *
	 * @return string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 * @throws \Exception
	 */
	public function addHead( array $head, bool $afterRender = false ): string
	{
		if ( strlen( Input::Cmd( 'format' ) ) && Input::Cmd( 'format' ) != 'html' ) {
			return C::ES;
		}

		$document = JFactory::getApplication()->getDocument();
		$headerOutput = [];
		$linecounter = 0;

		if ( count( $head ) ) {
			if ( $afterRender ) {
				$headerOutput[ 'custom' ][] = ( "\n<!--  SobiPro Head Tags Output  -->\n" );

				$vars = "\n\tvar SobiProUrl = '" . FileSystem::FixUrl( self::url( [ 'task' => '%task%' ], true, false, true ) ) . "';" .
					"\n\tvar SobiProSection = " . ( Sobi::Section() ? Sobi::Section() : 0 ) . ";" .
					"\n\tvar SPLiveSite = '" . Sobi::Cfg( 'live_site' ) . "';\n";
				if ( defined( 'SOBI_ADM_PATH' ) ) {
					$vars .= "\tvar SobiProAdmUrl = '" . FileSystem::FixUrl( Sobi::Cfg( 'live_site' ) . SOBI_ADM_FOLDER . '/' . self::url( [ 'task' => '%task%' ], true, false ) ) . "';\n";
				}
				$headerOutput[ 'custom' ][] = ( "\n<script type=\"text/javascript\">\n/*<![CDATA[*/" . $vars . "/*]]>*/\n</script>" );
				++$linecounter;
			}
			$canonicalSet = false;
			foreach ( $head as $type => $code ) {
				switch ( $type ) {
					default:
					{
						if ( count( $code ) ) {
							foreach ( $code as $html ) {
								++$linecounter;
								$headerOutput[ 'custom' ][] = $html;
								if ( $type == 'links' && strstr( $html, 'canonical' ) ) {
									$canonicalSet = true;
								}
							}
						}
						break;
					}
					case 'robots' :
					case 'author':
					{
						$document->setMetaData( $type, implode( ', ', $code ) );
						break;
					}
					case 'keywords':
					{
						$metaKeys = trim( implode( ', ', $code ) );
						if ( Sobi::Cfg( 'meta.keys_append', true ) ) {
							$metaKeys .= Sobi::Cfg( 'string.meta_keys_separator', ',' ) . $document->getMetaData( 'keywords' );
						}
						$metaKeysArray = explode( Sobi::Cfg( 'string.meta_keys_separator', ',' ), $metaKeys );
						if ( count( $metaKeysArray ) ) {
							$metaKeysArray = array_unique( $metaKeysArray );
							foreach ( $metaKeysArray as $index => $key ) {
								if ( strlen( trim( $key ) ) ) {
									$metaKeysArray[ $index ] = trim( $key );
								}
								else {
									unset( $metaKeysArray[ $index ] );
								}
							}
							$metaKeys = implode( ', ', $metaKeysArray );
						}
						else {
							$metaKeys = C::ES;
						}
						$document->setMetadata( 'keywords', $metaKeys );
						break;
					}
					case 'description':
					{
						$metaDesc = implode( Sobi::Cfg( 'string.meta_desc_separator', ' ' ), $code );
						if ( strlen( $metaDesc ) ) {
							if ( Sobi::Cfg( 'meta.desc_append', true ) ) {
								$metaDesc .= $this->getMetaDescription( $document );
							}
							$metaDescArray = explode( ' ', $metaDesc );
							if ( count( $metaDescArray ) ) {
								foreach ( $metaDescArray as $index => $description ) {
									if ( strlen( trim( $description ) ) ) {
										$metaDescArray[ $index ] = trim( $description );
									}
									else {
										unset( $metaDescArray[ $index ] );
									}
								}
								$metaDesc = implode( ' ', $metaDescArray );
							}
							else {
								$metaDesc = C::ES;
							}
							$document->setDescription( $metaDesc );
						}
						break;
					}
					case 'generator':
					{
						if ( $generator = $document->getMetaData( 'generator' ) ) {
							$document->setMetadata( 'generator', $code . $generator );
						}
					}
				}
			}
			if ( $afterRender ) {
				$jsUrl = FileSystem::FixPath( self::url( [ 'task' => 'txt.js', 'sid' => Sobi::Section(), 'format' => 'json' ], true, false, false ) );
				$headerOutput[ 'custom' ][] = ( "\n<script defer type=\"text/javascript\" src=\"" . str_replace( '&', '&amp;', $jsUrl ) . "\"></script>" );
				$linecounter++;

				$headerOutput[ 'custom' ][] = ( "\n\n<!--  SobiPro ($linecounter) Head Tags Output -->\n" );

				// we would like to set our own canonical please :P
				// https://groups.google.com/forum/?fromgroups=#!topic/joomla-dev-cms/sF3-JBQspQU
				if ( count( $document->_links ) && $canonicalSet ) {
					foreach ( $document->_links as $index => $link ) {
						if ( $link[ 'relation' ] == 'canonical' ) {
							unset( $document->_links[ $index ] );
						}
					}
				}
			}

			if ( $linecounter ) {
//				if ( !$afterRender ) {
//					foreach ( $headerOutput[ 'custom' ] as $customCode ) {
//						$document->addCustomTag( $customCode );
//					}
//				}
//				else {
				return implode( " ", $headerOutput[ 'custom' ] );
//				}
			}
		}

		return C::ES;
	}

	/**
	 * Creating an array of additional variables depends on the CMS.
	 *
	 * @return array
	 */
	public function form(): array
	{
		return [ 'option' => 'com_sobipro', 'Itemid' => Input::Int( 'Itemid' ) ];
	}

	/**
	 * Creating URL from an array for the current CMS.
	 *
	 * @param null $var
	 * @param bool $js
	 * @param bool $sef
	 * @param bool $live
	 * @param bool $forceItemId
	 *
	 * @return mixed
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function url( $var = null, $js = false, $sef = true, $live = false, $forceItemId = false )
	{
		$url = self::baseUrl;
		if ( $var == 'current' ) {
			return Input::Raw( 'REQUEST_URI', 'server', $url );
		}
		// don't remember why :(
		// Nevertheless it is generating &amp; in URL fro ImEx
//		$sef = Sobi::Cfg( 'disable_sef_globally', false ) ? false : ( defined( 'SOBIPRO_ADM' ) && !( $forceItemId ) ? false : $sef );
		$sef = !Sobi::Cfg( 'disable_sef_globally', false ) && $sef;
		Sobi::Trigger( 'Create', 'Url', [ &$var, $js ] );
		if ( is_array( $var ) && !empty( $var ) ) {
			if ( isset( $var[ 'option' ] ) ) {
				$url = str_replace( 'com_sobipro', $var[ 'option' ], $url );
				unset( $var[ 'option' ] );
			}
			if ( ( isset( $var[ 'sid' ] ) && ( !defined( 'SOBIPRO_ADM' ) || $forceItemId ) ) || ( defined( 'SOBIPRO_ADM' ) && $sef && $live ) ) {
				if ( !( isset( $var[ 'Itemid' ] ) ) || !( $var[ 'Itemid' ] ) ) {
					SPFactory::mainframe()->getItemid( $var );
				}
			}
			if ( isset( $var[ 'title' ] ) ) {
				if ( Sobi::Cfg( 'url.title', true ) ) {
					$var[ 'title' ] = trim( StringUtils::UrlSafe( $var[ 'title' ] ) );
					$var[ 'sid' ] = $var[ 'sid' ] . ':' . $var[ 'title' ];
				}
				unset( $var[ 'title' ] );
			}
			if ( isset( $var[ 'format' ] ) && $var[ 'format' ] == 'raw' && $sef ) {
				unset( $var[ 'format' ] );
			}
			foreach ( $var as $key => $value ) {
				if ( $key == 'out' ) {
					switch ( $value ) {
						case 'html':
							$var[ 'tmpl' ] = 'component';
							unset( $var[ 'out' ] );
							break;
						case 'xml':
//							$var[ 'tmpl' ] = 'component';
//							$var[ 'format' ] = 'raw';
						case 'raw':
							$var[ 'tmpl' ] = 'component';
							$var[ 'format' ] = 'raw';
							break;
						case 'json':
							$var[ 'out' ] = 'json';
							$var[ 'format' ] = 'raw';
							$var[ 'tmpl' ] = 'component';
							break;
					}
				}
			}
			$sid = $itemid = $sptpl = null;
			foreach ( $var as $key => $value ) {
				if ( $key == 'sid' ) {
					$sid = $value;
					continue;
				}
				if ( $key == 'Itemid' ) {
					$itemid = $value;
					continue;
				}
				if ( $key == 'sptpl' ) {
					$sptpl = $value;
					continue;
				}
				$url .= "&amp;$key=$value";
			}
			// move sid and Itemid to the end (first sptpl, then sid, then Itemid)
			if ( $sid ) {
				$url .= "&amp;sid=$sid";
			}
			if ( $sptpl ) {
				$url .= "&amp;sptpl=$sptpl";
			}
			if ( $itemid ) {
				$url .= "&amp;Itemid=$itemid";
			}
		}
		else {
			if ( is_string( $var ) ) {
				if ( strstr( $var, 'index.php?' ) ) {
					$url = null;
				}
				else {
					$url .= '&amp;';
				}
				if ( strstr( $var, '=' ) ) {
					$var = str_replace( '&amp;', '&', $var );
					$var = str_replace( '&', '&amp;', $var );
					$url .= $var;
				}
				else {
					$url .= SOBI_TASK . '=';
					$url .= $var;
				}
			}
		}
		if ( $sef && !( $live ) ) {
			$url = Route::_( $url, false );
		}
		else {
			$url = preg_replace( '/&(?![#]?[a-z0-9]+;)/i', '&amp;', $url );
		}
		if ( $live ) {
			/*
			 * SubDir Issues:
			 * when using SEF Joomla! router returns also the sub dir
			 * and JURI::base returns the sub dir too
			 * So if the URL should be SEF we have to remove the subdirectory once
			 * Otherwise it doesn't pass the JRoute::_ method so there is no subdir included
			* */
			if ( $sef ) {
				$base = Uri::base( true );
				$root = str_replace( $base, C::ES, Sobi::Cfg( 'live_site' ) );
				$url = explode( '/', $url );
				$url = $url[ count( $url ) - 1 ];
				//                if ( defined( 'SOBIPRO_ADM' ) ) {
				//                    $router = JApplication::getInstance( 'site' )->getRouter();
				//                    $a = $router->build( $url );
				//                    $url = $router->build( $url )->toString();
				//                }
				if ( !defined( 'SOBIPRO_ADM' ) ) {
					$url = Route::_( $url, false );
				}
				$url = FileSystem::FixUrl( "$root$url" );
			}
			else {
				$adm = defined( 'SOBIPRO_ADM' ) ? SOBI_ADM_FOLDER : null;
				$url = FileSystem::FixUrl( Sobi::Cfg( 'live_site' ) . $adm . '/' . $url );
			}
		}
		$url = str_replace( '%3A', ':', $url );
		// all urls in front are passed to the XML/XSL template are going to be encoded anyway
		$o = Input::Cmd( 'format', 'request', Input::Cmd( 'out' ) );
		if ( !( in_array( $o, [ 'raw', 'xml' ] ) ) && !( defined( 'SOBI_ADM_PATH' ) ) ) {
			$url = html_entity_decode( $url );
		}
		$url = str_replace( ' ', '%20', urldecode( $url ) );

		return $js ? str_replace( 'amp;', C::ES, $url ) : $url;
	}

	/**
	 * @param $url
	 *
	 * @return bool
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 * @throws \Exception
	 */
	protected function getItemid( &$url )
	{
		$sid = isset( $url[ 'pid' ] ) && $url[ 'pid' ] ? $url[ 'pid' ] : $url[ 'sid' ];
		if ( !( ( int ) $sid ) ) {
			return false;
		}
		$url[ 'Itemid' ] = 0;

		$menu = JFactory::getApplication()->getMenu( 'site' );
		if ( isset( $url[ 'task' ] ) ) {
			$task = ( $url[ 'task' ] == 'search.results' ) ? 'search' : $url[ 'task' ];
			$link = 'index.php?option=com_sobipro&task=' . $task . '&sid=' . $sid;
		}
		else {
			if ( isset( $url[ 'sptpl' ] ) ) {
				$link = 'index.php?option=com_sobipro&sid=' . $sid . '&sptpl=' . $url[ 'sptpl' ];
			}
			else {
				$link = 'index.php?option=com_sobipro&sid=' . $sid;
			}
		}
		/** Fri, Feb 17, 2017 10:46:34 - check a direct link first - for e.g. linked entries */
		if ( isset( $url[ 'sid' ] ) ) {
			$tk = isset( $task ) ? "&task=" . $task : C::ES;   // include the task in the url if available (17.12.19)
			$item = $menu->getItems( 'link', 'index.php?option=com_sobipro' . $tk . '&sid=' . $url[ 'sid' ], true );
			if ( isset( $item ) && isset( $item->id ) ) {
				$url[ 'Itemid' ] = $item->id;
			}
		}
		if ( !$url[ 'Itemid' ] ) {
			$item = $menu->getItems( 'link', $link, true );
			if ( is_array( $item ) && count( $item ) ) {
				if ( isset( $url[ 'sptpl' ] ) ) {
					unset( $url[ 'sptpl' ] );
				}
				$url[ 'Itemid' ] = $item->id;
			}

			$item = $menu->getItems( 'link', $link, true );
			if ( isset( $item->id ) ) {
				$url[ 'Itemid' ] = $item->id;
			}

			else {
				$path = SPFactory::config()->getParentPath( $sid );
				if ( count( $path ) ) {
					foreach ( $path as $sid ) {
						$item = $menu->getItems( 'link', 'index.php?option=com_sobipro&sid=' . $sid, true );
						if ( isset( $item->id ) ) {
							$url[ 'Itemid' ] = $item->id;
						}
					}
				}
				else {
					/* try it with the stored section */
					$item = $menu->getItems( 'link', 'index.php?option=com_sobipro&sid=' . (int) Sobi::Section(), true );
					if ( isset( $item->id ) ) {
						$url[ 'Itemid' ] = $item->id;
					}
				}
			}
		}
		if ( !$url[ 'Itemid' ] && !defined( 'SOBIPRO_ADM' ) ) {
			$url[ 'Itemid' ] = Sobi::Cfg( 'itemid.' . Sobi::Section( 'nid' ), 0 );
		}
		// if we still don't have an Itemid it means that there is no link to SobiPro section
		if ( !$url[ 'Itemid' ] && !defined( 'SOBIPRO_ADM' ) ) {
			SPFactory::message()
				->warning( Sobi::Txt( 'ITEMID_MISSING_WARN', 'https://www.sigsiu.net/help_screen/joomla.menu', $sid ), false, false )
				->setSystemMessage( 'SEF-URL' );
		}
	}

	/**
	 *
	 */
	public function endOut()
	{
//		if ( ( !strlen( Input::Cmd( 'format' ) ) || Input::Cmd( 'format' ) == 'html' ) ) {
		/* something like 'onDomReady' but it should be a bit faster */
//			echo '<script type="text/javascript">SobiPro.Ready();</script>';
//		}
	}

	/**
	 * @param $id
	 *
	 * @return \Joomla\CMS\User\User
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function & getUser( $id = 0 )
	{
		$user = SPFactory::user()->getInstance( $id );

		return $user;
	}

	/**
	 * Switching error reporting and display errors completely off for e.g. JavaScript or XML output where the document structure is very sensitive.
	 *
	 * @return $this
	 */
	public function & cleanBuffer()
	{
		error_reporting( 0 );
		ini_set( 'display_errors', 'off' );
		Input::Set( 'tmpl', 'component' );

		while ( ob_get_length() ) {
			ob_end_clean();
		}

		return $this;
	}

	/**
	 * @param string $type
	 * @param int $code
	 *
	 * @return $this
	 * @see Sobi\Lib\Factory::Application()
	 * @see Sobi\Application\Joomla::customHeader()
	 * @deprecated
	 */
	public function & customHeader( $type = 'application/json', $code = 0 )
	{
		Factory::Application()->customHeader( $type, $code );

		return $this;
	}

	/**
	 * Method to determine a hash for anti-spoofing variable names.
	 *
	 * @return string
	 * @deprecated
	 */
	public function token(): string
	{
		return Factory::Application()->token();
	}

	/**
	 * Checks for a form token in the request.
	 *
	 * @param string $method
	 *
	 * @return bool
	 * @deprecated
	 */
	public function checkToken( string $method = 'post' ): bool
	{
		return Factory::Application()->checkToken( $method );
	}

	/**
	 * @param $name
	 * @param $value
	 * @param int $expire
	 * @param false $httponly
	 * @param false $secure
	 * @param string $path
	 * @param null $domain
	 *
	 * @throws \Exception
	 * @deprecated
	 * @see \Sobi\Lib\Factory::Application()
	 * @see \Sobi\Application\Joomla::setCookie()
	 * @use \Sobi\Lib\Factory::Application()->setCookie
	 */
	public function setCookie( $name, $value, $expire = 0, $httponly = false, $secure = false, $path = '/', $domain = null )
	{
		Factory::Application()->setCookie( $name, $value, $expire, $httponly, $secure, $path, $domain );
	}

	/**
	 * Sets the browser title on frontend.
	 *
	 * @param $title
	 * @param bool $forceAdd
	 *
	 * @throws \Exception
	 */
	public function setTitle( $title, bool $forceAdd = false )
	{
		$document = JFactory::getApplication()->getDocument();
		$jTitle = $document->getTitle(); //get the title Joomla has set
		$delimiter = Sobi::Cfg( 'browser.title_separator', ' - ' );

		if ( strpos( $jTitle, $delimiter ) === false ) {
			/* if the title is not already set in Joomla */
			if ( Sobi::Cfg( 'browser.add_title', true ) || Sobi::Cfg( 'browser.add_section', true ) || $forceAdd ) {
				if ( !is_array( $title ) ) {
					if ( strpos( $title, $delimiter ) !== false ) {
						$title = explode( $delimiter, $title );   // contains already a separator
					}
					else {
						$title = [ $title ];    // make an array from it because we need to add items
					}
				}
				else {
					if ( ( count( $title ) == 1 ) && ( strpos( $title[ 0 ], $delimiter ) !== false ) ) {
						$title = explode( $delimiter, $title[ 0 ] );   // contains already a separator in only and first item
					}
				}
			}
			if ( is_array( $title ) ) { // it is an array, so probably add or remove items

				//browser.add_title = true: adds the Joomla part (this is normally the menu item) in front of it
				if ( Sobi::Cfg( 'browser.add_title', true ) || $forceAdd ) {    // if Joomla item has to be added
					if ( ( count( $title ) && $title[ 0 ] != $jTitle ) || ( !count( $title ) ) ) { // if first element is not already the Joomla item
						array_unshift( $title, $jTitle ); // add the Joomla item at the beginning
					}
				}
				else {  // don't add the Joomla item
					if ( $title[ 0 ] == $jTitle ) { // if first element is the Joomla title
						array_shift( $title );  // remove it
					}
				}

				if ( Sobi::Cfg( 'browser.add_section', true ) ) {   // add the section name
					$jRemoved = false;
					if ( count( $title ) && $title[ 0 ] == $jTitle ) { // if first element is the Joomla title
						array_shift( $title );   // remove it
						$jRemoved = true;
					}
					// add the section in front of it

					$sectionName = SPFactory::registry()->get( 'current_section_name' );
					if ( ( count( $title ) && $title[ 0 ] != $sectionName ) || ( !( count( $title ) ) ) ) {
						array_unshift( $title, $sectionName );
					}
					if ( $jRemoved ) {
						array_unshift( $title, $jTitle );   // add Joomla title if previously removed
					}
				}


				//if ( Sobi::Cfg( 'browser.full_title', true ) || true ) {
				//browser.full_title = true: if title is array, use only the last. That's e.g. the entry name without categories for SobiPro standard title
				if ( is_array( $title ) && count( $title ) ) {        // we have several elements in the title such as a category path
					if ( Sobi::Cfg( 'browser.reverse_title', false ) ) {
						$title = array_reverse( $title );
					}
					$title = implode( $delimiter, $title );
				}
				else {
					$title = C::ES;  // if we have an empty array, there is no browser title (yet)
				}
//			}
			}
			else {  // it is only one item
				if ( $title && Sobi::Cfg( 'browser.add_section', true ) ) {
					$title = SPFactory::registry()->get( 'current_section_name' ) . Sobi::Cfg( 'browser.title_separator', ' - ' ) . $title;
				}
			}

			// if we have a browser title, check if and where we should add the site title (it's a Joomla setting)
			if ( strlen( $title ) ) {
				if ( !defined( 'SOBIPRO_ADM' ) ) {
					if ( JFactory::getConfig()->get( 'sitename_pagetitles', 0 ) == 1 ) {    // Site mname in page title before
						$title = JText::sprintf( 'JPAGETITLE', JFactory::getConfig()->get( 'sitename' ), $title );
					}
					else {
						if ( JFactory::getConfig()->get( 'sitename_pagetitles', 0 ) == 2 ) {    // Site name in page title after
							$title = JText::sprintf( 'JPAGETITLE', $title, JFactory::getConfig()->get( 'sitename' ) );
						}
					}
				}
				/* sets the browser title */
				$document->setTitle( StringUtils::Clean( html_entity_decode( $title ) ) );
			}
		}

		/* the title is already set, overwrite it
		(comes from the frontend template.php: SPFactory::header()->setTitle()) */
		else {
			/* sets the browser title */
			if ( is_array( $title ) ) {
				$title = implode( $delimiter, $title );
			}
			$document->setTitle( $title );
		}
	}
}

/** Legacy class - @deprecated */
class SPMainFrame
{
	/**
	 * @param string $msg
	 * @param int $code
	 * @param array $info
	 * @param bool $translate
	 *
	 * @return mixed
	 * @throws SPException
	 *
	 * @deprecated
	 */
	public static function runAway( string $msg, $code = 500, $info = [], bool $translate = false )
	{
		return SPFactory::mainframe()->runAway( $msg, $code, $info, $translate );
	}

	/**
	 * @return mixed|string
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 *
	 * @deprecated
	 */
	public static function getBack()
	{
		return SPFactory::mainframe()->getBack();
	}

	/**
	 * @param $add
	 * @param null $msg
	 * @param string $msgtype
	 * @param bool $now
	 * @param int $code
	 *
	 * @return mixed
	 * @throws SPException
	 *
	 * @deprecated
	 */
	public static function setRedirect( $add, $msg = null, $msgtype = 'message', $now = false, $code = 302 )
	{
		return SPFactory::mainframe()->setRedirect( $add, $msg, $msgtype, $now, $code );
	}

	/**
	 * @param $msg
	 * @param null $type
	 *
	 * @return mixed|string
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 *
	 * @deprecated
	 */
	public static function msg( $msg, $type = null )
	{
		return SPFactory::mainframe()->getBack( $msg, $type );
	}

	/**
	 * @deprecated
	 */
	public static function redirect()
	{
		return SPFactory::mainframe()->redirect();
	}

	/**
	 * @return mixed
	 * @throws SPException
	 *
	 * @deprecated
	 */
	public static function form()
	{
		return SPFactory::mainframe()->form();
	}

	/**
	 * @param null $var
	 * @param bool $js
	 * @param bool $sef
	 * @param bool $live
	 * @param bool $forceItemId
	 *
	 * @return mixed
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 *
	 * @deprecated
	 *
	 */
	public static function url( $var = null, $js = false, $sef = true, $live = false, $forceItemId = false )
	{
		return SPFactory::mainframe()->url( $var, $js, $sef, $live, $forceItemId );
	}

	/**
	 * @deprecated
	 */
	public static function endOut()
	{
		return SPFactory::mainframe()->endOut();
	}
}
