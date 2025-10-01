<?php
/**
 * @package: SobiPro Library
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
 * @created 15-Jul-2010 18:17:28 by Radek Suski
 * @modified 07 April 2023 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

use Sobi\C;
use Sobi\Input\Input;
use Sobi\FileSystem\FileSystem;
use Sobi\Communication\CURL;
use Sobi\Lib\Factory;

SPLoader::loadController( 'controller' );

/**
 * Class SPCrawler
 */
class SPCrawler extends SPController
{
	public const TIME_LIMIT = 2;
	public const USER_AGENT = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:76.0) Gecko/20100101 Firefox/76.0";
	public const DB_TABLE = 'spdb_crawler';
	public const FORMAT = 'tmpl=component&crawl=1';
	public const FORMAT_FULL = 'crawl=1';

	/** @var int */
	protected $start = 0;
	/** @var null */
	private $format = null;
	/** @var array */
	private $skipTasks = [ 'entry.edit', 'entry.disable', 'entry.save', 'entry.clone', 'entry.payment', 'entry.submit', 'entry.approve', 'entry.publish', 'entry.delete' ];

	/**
	 * @return void
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function execute()
	{
		$this->start = microtime( true );
		$sites = $this->getSites();
		$responses = [];
		$errMessage = null;
		$status = 'working';
		$message = null;
		$this->format = Input::Bool( 'fullFormat' ) ? self::FORMAT_FULL : self::FORMAT;
		$task = Input::Task();
		if ( in_array( $task, [ 'crawler.init', 'crawler.restart' ] ) ) {
			if ( $task == 'crawler.restart' ) {
				SPFactory::cache()->cleanSection( (int) Sobi::Section() );
			}
			Factory::Db()->truncate( self::DB_TABLE );
			$multiLang = Sobi::Cfg( 'lang.multimode', false );
			if ( $multiLang ) {
				$langs = SPLang::availableLanguages();
				if ( $langs ) {
					foreach ( $langs as $lang ) {
						$responses[] = $this->getResponse( Sobi::Cfg( 'live_site' ) . 'index.php?option=com_sobipro&sid=' . Sobi::Section() . '&lang=' . $lang[ 'tag' ], $errMessage );
						if ( $errMessage ) {
							break;
						}
					}
				}
			}
			$responses[] = $this->getResponse( Sobi::Cfg( 'live_site' ) . 'index.php?option=com_sobipro&sid=' . Sobi::Section(), $errMessage );
			if ( $errMessage ) {    /* fatal error, no further action can be taken */
				$this->out( [ 'status' => 'done', 'data' => $responses, 'message' => $errMessage ] );  /* output and exit */
			}

			$sites = $this->getSites();
		}
		if ( !count( $sites ) && !in_array( $task, [ 'crawler.init', 'crawler.restart' ] ) ) {
			$message = Sobi::Txt( 'CRAWL_URL_PARSED_DONE', Factory::Db()->select( 'count(*)', self::DB_TABLE )->loadResult() );
			Factory::Db()->truncate( self::DB_TABLE );
			$this->out( [ 'status' => 'done', 'data' => [], 'message' => $message ] );  /* exit */
		}
		if ( count( $sites ) ) {
			$i = 0;
			//$timeLimit = SPRequest::int( 'timeLimit', self::TIME_LIMIT, 'get', true );
			$timeLimit = Input::Int( 'timeLimit', 'get', self::TIME_LIMIT );
			$timeLimit = $timeLimit ? $timeLimit : self::TIME_LIMIT;

			foreach ( $sites as $site ) {
				if ( !( strlen( $site ) ) ) {
					continue;
				}
				$responses[] = $this->getResponse( $site, $errMessage );
				$i++;
				if ( microtime( true ) - $this->start > $timeLimit ) {
					break;
				}
			}
			$message = Sobi::Txt( 'CRAWL_URL_PARSED_WORKING', $i, count( $sites ) );
		}
		$this->out( [ 'status' => $status, 'data' => $responses, 'message' => $message ] );
	}

	/**
	 * @param $status
	 *
	 * @throws SPException
	 */
	protected function out( $status )
	{
		SPFactory::mainframe()
			->cleanBuffer()
			->customHeader();
		echo json_encode( $status );
		exit;
	}

	/**
	 * @param $url
	 * @param $errMessage
	 *
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function getResponse( $url, &$errMessage )
	{
		$errMessage = [];

		$request = str_replace( 'amp;', C::ES, $url );
//		$request = parse_url( $url );
//		$request[ 'query' ] =  urlencode( $request[ 'query' ] );
//		$request = $request[ 'scheme' ].'://'.$request[ 'host' ].$request[ 'path' ].'?'.$request[ 'query' ];
		$request .= ( !( strstr( $request, '?' ) ) ) ? '?' : '&';
		$request .= $this->format . uniqid();

		$connection = new CURL(); /* Init CURL */

		/* check if initialisation failed */
		$errno = $connection->error( false, true );
		$status = $connection->status( false, true );

		/* if CURL initialisation failed */
		if ( $status || $errno ) {
			$errMessage = 'Code ' . $status ? $connection->status() : $connection->error();

			return [
				'url'   => "<a href=\"$url\">$url</a>",
				'count' => 0,
				'code'  => $status ? : $errno,
				'time'  => 0,
			];
		}
		else {
			$connection->setOptions(  /* set CURL options */
				[
					'url'            => $request,
					'connecttimeout' => 10,
					'returntransfer' => true,
					'useragent'      => self::USER_AGENT,
					'header'         => true,
					'verbose'        => true,
				]
			);

			/* execute CURL and check error status */
			$content = $connection->exec();
			$errno = $connection->error( false, true );

			/* if CURL execution failed, we set only the error message */
			if ( $errno ) {
				$errMessage = 'Code ' . $connection->error();
			}

			$response = $connection->info();
			$urls = [];
			if ( $response[ 'http_code' ] == 200 ) {
				$urls = $this->parseResponse( $content );
				if ( is_numeric( $urls ) ) {
					$response[ 'http_code' ] = $urls;
				}
			}
			if ( $response[ 'http_code' ] == 303 ) {
				preg_match( '/Location: (http.*)/', $content, $newUrl );
				$urls[] = str_replace( [ '?' . $this->format, '&' . $this->format ], C::ES, trim( $newUrl[ 1 ] ) );
			}
			if ( is_array( $urls ) && count( $urls ) ) {
				$this->insertUrls( $urls );
			}
			$this->removeUrl( $url );

			return [
				'url'   => "<a href=\"$url\">$url</a>",
				'count' => is_array( $urls ) ? count( $urls ) : 0,
				'code'  => $response[ 'http_code' ],
				'time'  => $response[ 'total_time' ],
			];
		}
	}

	/**
	 * @param $url
	 *
	 * @throws \Sobi\Error\Exception
	 */
	protected function removeUrl( $url )
	{
		Factory::Db()->update( self::DB_TABLE, [ 'state' => 1 ], [ 'url' => $url ] );
	}

	/**
	 * @param $urls
	 *
	 * @return void
	 * @throws \Sobi\Error\Exception
	 */
	protected function insertUrls( $urls )
	{
		$rows = [];
		foreach ( $urls as $url ) {
			$url = str_replace( '&amp;', '&', $url );
			if ( !( strlen( $url ) ) ) {
				continue;
			}
			foreach ( $this->skipTasks as $task ) {
				if ( strstr( $url, $task ) ) {
					break 2;
				}
			}
			$schema = parse_url( $url );
			if ( isset( $schema[ 'query' ] ) ) {
				parse_str( $schema[ 'query' ], $query );
				if ( isset( $query[ 'format' ] ) ) {
					continue;
				}
				if ( isset( $query[ 'date' ] ) ) {
					$query[ 'date' ] = explode( '.', $query[ 'date' ] );
					$year = $query[ 'date' ][ 0 ];
					if ( $year > ( date( 'Y' ) + 2 ) || $year < ( date( 'Y' ) - 2 ) ) {
						continue;
					}
				}
				if ( isset( $query[ 'task' ] ) && in_array( $query[ 'task' ], $this->skipTasks ) ) {
					continue;
				}
			}
			if ( preg_match( '/(\d{4}\.\d{1,2})/', $url, $matches ) ) {
				if ( isset( $matches[ 0 ] ) ) {
					if ( $matches[ 0 ] > ( date( 'Y' ) + 2 ) || $matches[ 0 ] < ( date( 'Y' ) - 2 ) ) {
						continue;
					}
				}
			}
			if ( strstr( $url, 'favicon.ico' ) ) {
				continue;
			}
			if ( strstr( $url, '.css' ) ) {
				continue;
			}
			if ( strstr( $url, 'media/system' ) ) {
				continue;
			}
			if ( strstr( $url, 'javascript:' ) ) {
				continue;
			}
			$rows[] = [ 'crid' => 0, 'url' => $url, 'state' => 0 ];
//			if ( $multiLang && $langs ) {
//				foreach ( $langs as $lang ) {
//					if ( $lang != $language ) {
//						$url = preg_replace( '|(?<!:/)/' . $langs[ $language ] . '(/)?|', '/' . $lang . '\1', $url );
//						$url = str_replace( 'lang=' . $langs[ $language ], 'lang=' . $lang, $url );
//						$rows[ ] = array( 'crid' => 'NULL', 'url' => $url, 'state' => 0 );
//					}
//				}
//			}
		}
		if ( count( $rows ) ) {
			Factory::Db()->insertArray( self::DB_TABLE, $rows, false, true );
		}
	}

	/**
	 * @return array
	 * @throws \Sobi\Error\Exception
	 */
	protected function getSites()
	{
		return Factory::Db()
			->select( 'url', self::DB_TABLE, [ 'state' => 0 ] )
			->loadResultArray();
	}

	/**
	 * @param $response
	 *
	 * @return array|int
	 * @throws \SPException
	 */
	protected function parseResponse( $response )
	{
		if ( !( strlen( $response ) ) ) {
			return 204;
		}
		$links = [];
		if ( strstr( $response, 'SobiPro' ) ) {
			// we need to limit the "explode" to two pieces only because otherwise
			// if the separator is used somewhere in the <body> it will be split into more pieces
			[ $header, $response ] = explode( "\r\n\r\n", $response, 2 );
			$header = explode( "\n", $header );
			$SobiPro = false;
			foreach ( $header as $line ) {
				if ( strstr( $line, 'SobiPro' ) ) {
					$line = explode( ':', $line );
					if ( trim( $line[ 0 ] ) == 'SobiPro' ) {
						$sid = trim( $line[ 1 ] );
						if ( $sid != Sobi::Section() ) {
							return 412;
						}
						else {
							$SobiPro = true;
						}
					}
				}
			}
			if ( !( $SobiPro ) ) {
				return 412;
			}
			preg_match_all( '/href=[\'"]?([^\'" >]+)/', $response, $links, PREG_PATTERN_ORDER );
			if ( isset( $links[ 1 ] ) && $links[ 1 ] ) {
				$liveSite = Sobi::Cfg( 'live_site' );
				$host = Sobi::Cfg( 'live_site_root' );
				$links = array_unique( $links[ 1 ] );
				foreach ( $links as $index => $link ) {
					$link = trim( $link );
					$http = preg_match( '/http[s]?:\/\/.*/i', $link );
					if ( !( strlen( $link ) ) ) {
						unset( $links[ $index ] );
					}
					else {
						if ( strstr( $link, '#' ) ) {
							$link = explode( '#', $link );
							if ( strlen( $link[ 0 ] ) ) {
								$links[ $index ] = FileSystem::FixUrl( $host . '/' . $link[ 0 ] );
							}
							else {
								unset( $links[ $index ] );
							}
						}
						else {
							if ( $http && !( strstr( $link, $liveSite ) ) ) {
								unset( $links[ $index ] );
							}
							else {
								if ( !( $http ) ) {
									$links[ $index ] = FileSystem::FixUrl( $host . '/' . $link );
								}
							}
						}
					}
				}
			}

			return $links;
		}
		else {
			return 501;
		}
	}
}
