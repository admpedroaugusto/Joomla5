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
 * @created 06 January 2014 by Radek Suski
 * @modified 09 September 2024 by Sigrid Suski
 */

define( '_JEXEC', 1 );
/**
 * Wed, Jan 10, 2018 12:44:23
 * This is not a good practice, but Joomla throws some notices on its own,
 * and it screws the output for cronjobs.
 * */
error_reporting( 0 );

use Joomla\CMS\Application\CliApplication;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory as JFactory;

require dirname( __FILE__ ) . '/../../../../libraries/import.php';
if ( !( defined( 'JPATH_BASE' ) ) ) {
	define( 'JPATH_BASE', realpath( dirname( __FILE__ ) . '/../../../../' ) );
}
require_once JPATH_BASE . '/includes/defines.php';
if ( file_exists( JPATH_LIBRARIES . '/import.legacy.php' ) ) {
	require_once JPATH_LIBRARIES . '/import.legacy.php';
}
if ( file_exists( JPATH_LIBRARIES . '/version.php' ) ) {
	require_once JPATH_LIBRARIES . '/cms/version/version.php';
}

if ( file_exists( JPATH_LIBRARIES . '/cms.php' ) ) {
	require_once JPATH_LIBRARIES . '/cms.php';
}


// define everything so the sobiconst file is not being actively used
defined( 'SOBI_DEFLANG' ) || define( 'SOBI_DEFLANG', 'en-GB' );
defined( 'SOBI_DEFADMLANG' ) || define( 'SOBI_DEFADMLANG', 'en-GB' );
defined( 'SOBI_PATH' ) || define( 'SOBI_PATH', JPATH_ROOT . '/components/com_sobipro' );
defined( 'SOBI_MEDIA' ) || define( 'SOBI_MEDIA', JPATH_ROOT . '/media/sobipro' );
defined( 'SOBI_IMAGES' ) || define( 'SOBI_IMAGES', JPATH_ROOT . '/images/sobipro/' );
defined( 'SOBI_IMAGES_LIVE' ) || define( 'SOBI_IMAGES_LIVE', 'images/sobipro/' );
defined( 'SOBI_MEDIA_LIVE' ) || define( 'SOBI_MEDIA_LIVE', 'media/sobipro/' );
define( 'SOBIPRO', true );
define( 'SOBI_ROOT', JPATH_ROOT );
define( 'JDEBUG', false );


if ( !defined( 'JVERSION' ) ) {
	$jVersion = new Version();
	define( 'JVERSION', $jVersion->getShortVersion() );
}

if ( version_compare( JVERSION, '3.4.9999', 'ge' ) ) {
	JFactory::getConfig( JPATH_CONFIGURATION . '/configuration.php' );
}
require_once( JPATH_ROOT . '/components/com_sobipro/lib/sobi.php' );

use Joomla\CMS\Version;
use Sobi\Communication\CURL;
use Sobi\Lib\Factory;

$_SERVER[ 'HTTP_HOST' ] = null;

/**
 * Class SobiProCrawler
 */
class SobiProCrawler extends JApplicationCli
{
	public const USER_AGENT = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:76.0) Gecko/20100101 Firefox/76.0";
	/** @var bool */
	protected $silent = true;
	/** @var int */
	protected $section = 0;
	/** @var array */
	protected $sections = [];
	/** @var int */
	protected $timeLimit = 3600;
	/** @var int */
	protected $start = 0;
	/** @var bool */
	protected $cleanCache = true;
	/** @var string */
	protected $liveURL = '';
	/** @var int */
	protected $loopTimeLimit = 15;
	/** @var array */
	protected $args = [];

	protected function doExecute()
	{
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return '';
	}

	/**
	 * @param $args
	 *
	 * @return $this
	 */
	public function & setArgs( $args )
	{
		$this->args = $args;

		return $this;
	}

	/**
	 * @throws \Sobi\Error\Exception
	 */
	public function execute()
	{
		try {
			JFactory::getApplication();
		}
		catch ( Exception $x ) {
			$this->out( "Initializing Application" );
			// Initialise application
			$container = JFactory::getContainer();
			$app = $container->get( SiteApplication::class );
			// Set the application as global app
			JFactory::$application = $app;
		}

		Sobi::Initialise();

		$continue = $this->parseParameters( $this->args );
		$_SERVER[ 'HTTP_HOST' ] = $this->liveURL;

		if ( $continue ) {
			if ( !$this->section ) {
				$this->sections = Factory::Db()
					->select( 'id', 'spdb_object', [ 'oType' => 'section', 'state' => '1', '@VALID' => Factory::Db()->valid( 'validUntil', 'validSince' ) ] )
					->loadResultArray();
			}
			else {
				$this->sections = Factory::Db()
					->select( 'id', 'spdb_object',
						[ 'id'     => $this->section,
						  'oType'  => 'section',
						  'state'  => '1',
						  '@VALID' => Factory::Db()->valid( 'validUntil', 'validSince' ) ]
					)
					->loadResultArray();
			}
			if ( !$this->liveURL || !preg_match( '/http[s]?:\/\/.*/i', $this->liveURL ) ) {
				$this->out( '[ERROR] A valid live URL address is required' );
			}
			if ( count( $this->sections ) ) {
				$this->start = time();
				foreach ( $this->sections as $sid ) {
					$this->crawlSobiSection( $sid );
				}
			}
			else {
				$this->out( '[ERROR] No valid sections found' );
			}
		}
		exit( 1 );
	}

	/**
	 * @param $sid
	 */
	protected function crawlSobiSection( $sid )
	{
		$done = false;
		$task = $this->cleanCache ? 'crawler.restart' : 'crawler.init';
		$connection = new CURL();
		while ( !$done && ( time() - $this->start ) < $this->timeLimit ) {
			$url = $this->liveURL . "/index.php?option=com_sobipro&task=$task&sid=$sid&format=raw&tmpl=component&timeLimit=$this->loopTimeLimit&fullFormat=1";
			[ $content, $response ] = $this->SpConnect( $connection, trim( $url ) );
			$task = 'crawler';
			if ( $response[ 'http_code' ] == 303 ) {
				preg_match( '/Location: (http.*)/', $content, $newUrl );
				[ $content, $response ] = $this->SpConnect( $connection, $newUrl[ 1 ] );
			}
			if ( $response[ 'http_code' ] == 200 ) {
				$content = substr( $content, $response[ 'header_size' ] );
				$data = json_decode( $content );
				$done = $data->status == 'done';
				$this->SpOut( '' );
				$this->SpOut( '============' );
				$this->SpOut( "[ " . date( DATE_RFC2822 ) . " ] $data->message" );
				$this->SpOut( '============' );
				foreach ( $data->data as $row ) {
					$u = strip_tags( $row->url );
					$this->SpOut( "$u\t$row->count\t$row->code\t$row->time" );
				}
			}
			else {
				$done = true;
				$this->out( "[\033[31m ERROR \033[0m] Invalid return code: " . $response[ 'http_code' ] );
				$this->out( "[\033[31m ERROR \033[0m] Returned Error : " . $connection->error() );
				$this->out( "[\033[31m ERROR \033[0m] While accessing the following URL:" );
				$this->out( "[\033[31m ERROR \033[0m] >\e[34m" . $url . "\e[0m<" );
			}
		}
	}

	/**
	 * @param $args
	 *
	 * @return bool
	 */
	protected function parseParameters( $args )
	{
		if ( count( $args ) ) {
			foreach ( $args as $param ) {
				if ( $param == '--help' || $param == '-h' ) {
					$this->SobiCrawlerHelpScreen();

					return false;
				}
				if ( strstr( $param, '=' ) ) {
					$param = explode( '=', $param );
					$name = trim( $param[ 0 ] );
					$set = trim( $param[ 1 ] );
					if ( $set == 'yes' ) {
						$set = true;
					}
					elseif ( $set == 'no' ) {
						$set = false;
					}
					$this->$name = $set;
				}
			}
		}

		return true;
	}

	/**
	 * @return void
	 */
	protected function SobiCrawlerHelpScreen()
	{
		$this->out( '============' );
		$this->out( 'SobiPro Crawler v2.1' );
		$this->out( 'Copyright (C) 2006–2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.' );
		$this->out( 'License GNU/GPL Version 3' );
		$this->out( '============' );
		$this->out( '' );
		$this->out( 'The SobiPro crawler allows you to set up a cronjob to check all SobiPro links and build up the SobiPro caches which are useful to speed up your site significantly' );
		$this->out( '' );
		$this->out( 'Parameter list:' );
		$this->out( '' );
		$this->out( "\t liveURL - The URL address of your website/Joomla! root. Defines like liveURL=https://demo.sobi.pro This is a required parameter!" );
		$this->out( "\t silent - yes/no. In case set to 'yes' only error messages are going to be displayed. Default set to 'yes'" );
		$this->out( "\t section - section id of the section you want to crawl. If not given, all valid sections are going to be crawled" );
		$this->out( "\t timeLimit - time limit (in seconds - default 3600). If the limit has been reached while crawling, the crawler will stop any actions" );
		$this->out( "\t cleanCache - yes/no If set to 'yes' the cache will be invalidated first. Default set to 'yes'" );
		$this->out( "\t loopTimeLimit - time limit (in seconds - default 15) for a loop. When reached, a new loop is going to be started" );
		$this->out( '' );
		$this->out( "All settings are defined like name=value (timeLimit=100). The order doesn't matter" );
		$this->out( '' );
		$this->out( '' );
	}

	/**
	 * @param $txt
	 */
	protected function SpOut( $txt )
	{
		if ( !$this->silent ) {
			$this->out( $txt );
		}
	}

	/**
	 * @param CURL $connection
	 * @param string $url
	 *
	 * @return array
	 */
	protected function SpConnect( $connection, $url )
	{
		$connection->setOptions( [ 'url' => $url, 'connecttimeout' => 10, 'returntransfer' => true, 'useragent' => self::USER_AGENT, 'header' => true, 'verbose' => false ] );
		$content = $connection->exec();
		$response = $connection->info();

		return [ $content, $response ];
	}
}

CliApplication::getInstance( 'SobiProCrawler' )
	->setArgs( $argv )
	->execute();
