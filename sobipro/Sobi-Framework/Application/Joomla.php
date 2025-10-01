<?php
/**
 * @package: Sobi Framework
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2021 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created Tue, Mar 28, 2017 by Radek Suski
 * @modified 19 May 2021 by Sigrid Suski
 */
//declare( strict_types=1 );

namespace Sobi\Application;

use Joomla\CMS\{
	Factory as JFactory,
	Session\Session as JSession,
	Language\Text as JText
};
use Sobi\{
	C,
	Framework,
	FileSystem\FileSystem,
	Input\Input,
	Lib\Instance,
	Utils\StringUtils
};

/**
 * Class Joomla
 * @package Sobi\Application
 */
class Joomla
{
	use Instance;

	/**
	 * @param string $title
	 * @param bool $forceAdd
	 *
	 * @throws \Sobi\Error\Exception
	 */
	public function setTitle( string $title, bool $forceAdd = false )
	{
		$document = JFactory::getApplication()->getDocument();
		if ( !( is_array( $title ) ) && ( Framework::Cfg( 'browser.add_title', true ) || $forceAdd ) ) {
			$title = [ $title ];
		}
		if ( is_array( $title ) ) {
			//browser.add_title = true: adds the Joomla part (this is normally the menu item) in front of it (works only if full_title is also set to true)
			$jTitle = $document->getTitle(); //get the title Joomla has set
			if ( Framework::Cfg( 'browser.add_title', true ) || $forceAdd ) {
				if ( $title[ 0 ] != $jTitle ) {
					array_unshift( $title, $jTitle );
				}
			}
			else {
				if ( $title[ 0 ] == $jTitle ) {
					array_shift( $title );
				}
			}
			//if ( Sobi::Cfg( 'browser.full_title', true ) || true ) {
			//browser.full_title = true: if title is array, use only the last. That's e.g. the entry name without categories for SobiPro standard title
			if ( is_array( $title ) && count( $title ) ) {
					if ( Framework::Cfg( 'browser.reverse_title', false ) ) {
						$title = array_reverse( $title );
					}
					$title = implode( Framework::Cfg( 'browser.title_separator', ' - ' ), $title );
				}
			else {
				$title = null;
			}

		}
		if ( strlen( $title ) ) {
			if ( !( defined( 'SOBIPRO_ADM' ) ) ) {
				if ( JFactory::getApplication()->get( 'sitename_pagetitles', 0 ) == 1 ) {
					$title = JText::sprintf( 'JPAGETITLE', JFactory::getApplication()->get( 'sitename' ), $title );
				}
				elseif ( JFactory::getApplication()->get( 'sitename_pagetitles', 0 ) == 2 ) {
					$title = JText::sprintf( 'JPAGETITLE', $title, JFactory::getApplication()->get( 'sitename' ) );
				}
			}
			$document->setTitle( StringUtils::Clean( html_entity_decode( $title ) ) );
		}
	}

	/**
	 * @param array $head
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function addHead( array $head ): bool
	{
		if ( strlen( Input::Cmd( 'format' ) ) && Input::Cmd( 'format' ) != 'html' ) {
			return true;
		}
		$document = JFactory::getApplication()->getDocument();
		$c = 0;
		if ( count( $head ) ) {
			foreach ( $head as $type => $code ) {
				switch ( $type ) {
					default:
						if ( count( $code ) ) {
							foreach ( $code as $html ) {
								++$c;
								$document->addCustomTag( $html );
							}
						}
						break;
					case 'robots' :
					case 'author':
						if ( !( defined( 'SOBI_ADM_PATH' ) ) ) {
							$document->setMetadata( $type, implode( ', ', $code ) );
						}
						break;
					case 'keywords':
						if ( !( defined( 'SOBI_ADM_PATH' ) ) ) {
							$metaKeys = trim( implode( ', ', $code ) );
							if ( Framework::Cfg( 'meta.keys_append', true ) ) {
								$metaKeys .= Framework::Cfg( 'string.meta_keys_separator', ',' ) . $document->getMetaData( 'keywords' );
							}
							$metaKeys = explode( Framework::Cfg( 'string.meta_keys_separator', ',' ), $metaKeys );
							if ( count( $metaKeys ) ) {
								foreach ( $metaKeys as $i => $p ) {
									if ( strlen( trim( $p ) ) ) {
										$metaKeys[ $i ] = trim( $p );
									}
									else {
										unset( $metaKeys[ $i ] );
									}
								}
								$metaKeys = implode( ', ', $metaKeys );
							}
							else {
								$metaKeys = null;
							}
							$document->setMetadata( 'keywords', $metaKeys );
						}
						break;
					case 'description':
						$metaDesc = implode( Framework::Cfg( 'string.meta_desc_separator', ' ' ), $code );
						if ( strlen( $metaDesc ) && !( defined( 'SOBI_ADM_PATH' ) ) ) {
							if ( Framework::Cfg( 'meta.desc_append', true ) ) {
								$metaDesc .= ' ' . $document->get( 'description' );
							}
							$metaDesc = explode( ' ', $metaDesc );
							if ( count( $metaDesc ) ) {
								foreach ( $metaDesc as $i => $p ) {
									if ( strlen( trim( $p ) ) ) {
										$metaDesc[ $i ] = trim( $p );
									}
									else {
										unset( $metaDesc[ $i ] );
									}
								}
								$metaDesc = implode( ' ', $metaDesc );
							}
							else {
								$metaDesc = null;
							}
							$document->setDescription( $metaDesc );
						}
						break;
				}
			}
			$jsUrl = FileSystem::FixPath( Framework::Cfg( 'live_site' ) . ( defined( 'SOBI_ADM_FOLDER' ) ? SOBI_ADM_FOLDER . '/' : '' ) . self::Url( [ 'task' => 'txt.js', 'format' => 'json' ], true, false ) );
			$document->addCustomTag( "\n\t<script type=\"text/javascript\" src=\"" . str_replace( '&', '&amp;', $jsUrl ) . "\"></script>\n" );
			$c++;
			$document->addCustomTag( "\n\t<!--  SobiPro ({$c}) Head Tags Output -->\n" );
		}
	}

	public function token(): string
	{
		return JSession::getFormToken();
	}

	public function checkToken( $method = 'post' ): bool
	{
		return JSession::checkToken( $method );
	}

	/**
	 * @param string $type
	 * @param int $code
	 *
	 * @return $this
	 */
	public function & customHeader( string $type = 'application/json', int $code = 0 ): Joomla
	{
		header( 'Content-type: ' . $type );
		header( 'Cache-Control: no-cache, must-revalidate' );
		header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' );
		if ( $code ) {
			http_response_code( $code );
		}

		return $this;
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
	 */
	public function setCookie( $name, $value, int $expire = 0, bool $httponly = false, bool $secure = false, string $path = '/', $domain = null )
	{
		JFactory::getApplication()->input->cookie->set( $name, $value, $expire, $path, $domain, $secure, $httponly );
	}


	/**
	 * Loads an additional language file.
	 *
	 * @param string $file
	 * @param string $lang
	 *
	 * @return bool
	 * @throws \Sobi\Error\Exception
	 * @throws \Exception
	 */
	public function loadLanguage( string $file, string $lang = C::ES ): bool
	{
		// at first always load the default language file
		if ( $lang != 'en-GB' && Framework::Cfg( 'lang.engb_preload', true ) ) {
			$this->loadLanguage( $file, 'en-GB' );
		}
		// to load the lang files we are always need the current user language (multilang mode switch ignored here)
		if ( JPATH_SITE != JPATH_BASE ) {
			JFactory::getApplication()->getLanguage()->load( $file, JPATH_SITE, $lang, true );
		}

		return JFactory::getApplication()->getLanguage()->load( $file, JPATH_BASE, $lang, true );
	}

	/**
	 * @param string $key
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function getCfg( string $key )
	{
		return JFactory::getApplication()->get( $key );
	}
}
