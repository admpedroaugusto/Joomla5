<?php
/**
 * @package SobiPro Library
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 01 December 2012 by Radek Suski
 * @modified 08 February 2024 by Sigrid Suski
 */

use Sobi\C;
use Sobi\Input\Input;
use Sobi\Lib\Factory;

/**
 * Class SPMessage
 */
class SPMessage
{
	/** @var array */
	protected $messages = [];
	/** @var bool */
	protected $reset = false;
	/** @var bool */
	protected $langLoadedAdm = false;
	/** @var bool */
	protected $langLoadedSite = false;
	/** @var array */
	protected $store = [];
	/** @var array */
	protected $reports = [];
	/** @var array */
	protected $current = [];

	/**
	 * SPMessage constructor.
	 */
	private function __construct()
	{
		$this->messages = Sobi::GetUserData( 'messages-queue', $this->messages );
		$registry = SPFactory::registry()
			->loadDBSection( 'messages' )
			->get( 'messages.queue.params' );
		if ( $registry ) {
			try {
				$this->store = SPConfig::unserialize( $registry );
			}
			catch ( SPException $x ) {
				Sobi::Error( 'Message', 'Cannot uncompress messages', C::WARNING );
			}
		}
		$reports = SPFactory::registry()
			->loadDBSection( 'reports' )
			->get( 'reports.queue.params' );

		if ( $reports ) {
			try {
				$this->reports = SPConfig::unserialize( $reports );
			}
			catch ( SPException $x ) {
				Sobi::Error( 'Message', 'Cannot uncompress reports', C::WARNING );
			}
		}
	}

	/**
	 * @throws \Exception
	 */
	public function storeMessages()
	{
		Sobi::SetUserData( 'messages-queue', $this->messages );
	}

	/**
	 * @param bool $reset
	 *
	 * @return array|mixed
	 * @throws \Exception
	 */
	public function getMessages( bool $reset = true )
	{
		$r = $this->messages;
		if ( $reset ) {
			$this->reset();
		}

		return $r;
	}

	/**
	 * @return $this
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 * @throws \Exception
	 */
	public function resetSystemMessages()
	{
		$this->store = [];
		$this->storeMessages();
		$store = [
			'params'      => [],
			'key'         => 'queue',
			'value'       => date( DATE_RFC822 ),
			'description' => C::ES,
			'options'     => null,
		];
		SPFactory::registry()->saveDBSection( [ 'messages' => $store ], 'messages' );
		SPFactory::cache()->cleanSection( -1, false );
		SPFactory::cache()->deleteVar( 'system_state', -1, Sobi::Lang( false ), -1 );

		return $this;
	}

	/**
	 * @return SPMessage
	 * @throws \Exception
	 */
	public function reset()
	{
		$this->messages = [];
		$this->reset = true;
		$this->storeMessages();

		return $this;
	}

	/**
	 * @return SPMessage
	 */
	public static function & getInstance()
	{
		static $message = null;
		if ( !$message || !( $message instanceof SPMessage ) ) {
			$message = new self();
		}

		return $message;
	}

	/**
	 * @param string|array $message
	 * @param bool $translate
	 * @param string $type
	 * @param bool $display
	 *
	 * @return $this|SPMessage
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 * @throws \Exception
	 */
	public function & setMessage( $message, bool $translate = true, string $type = C::WARN_MSG, bool $display = true ): self
	{
		if ( is_array( $message ) ) {
			/* there are several messages to show */
			foreach ( $message as $index => $msg ) {
				$text = $index < ( count( $message ) - 1 ) ? $msg[ 'text' ] . '<br/>' : $msg[ 'text' ];
				$this->setMessage( $text, $translate, $msg[ 'type' ], $display );
			}

			return $this;
		}

		/* handle the message */
		else {
			if ( $translate ) {
				if ( defined( 'SOBIPRO_ADM' ) && !$this->langLoadedAdm ) {
					$this->langLoadedAdm = Factory::Application()->loadLanguage( 'com_sobipro.messages' );
				}
				else {
					if ( !$this->langLoadedSite ) {
						$this->langLoadedSite = Factory::Application()->loadLanguage( 'com_sobipro.err' );
					}
				}
			}

			$type = $type == 'message' ? C::INFO_MSG : $type;

			$messageText = $translate ? Sobi::Txt( strtoupper( $type ) . '.' . $message ) : $message;
			if ( $display ) {
				$this->messages[ $type ][ preg_replace( "/[^a-zA-Z0-9]/", C::ES, $message ) ] = $messageText;
			}
			$this->current = [ 'message' => $messageText,
			                   'type'    => $type,
			                   'section' => [ 'id' => Sobi::Section(), 'name' => Sobi::Section( true ) ] ];
			$this->storeMessages();
		}

		return $this;
	}

	/**
	 * @param string $section
	 *
	 * @return SPMessage
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function & setSystemMessage( string $section = 'configuration' ): self
	{
		$change = count( $this->store );
		$this->current[ 'issue-type' ] = $section;
		$this->store[ md5( serialize( $this->current ) ) ] = $this->current;
		if ( count( $this->store ) > $change ) {
			$messages = SPConfig::serialize( $this->store );
			$store = [
				'params'      => $messages,
				'key'         => 'queue',
				'value'       => date( DATE_RFC822 ),
				'description' => C::ES,
				'options'     => null,
			];
			SPFactory::registry()->saveDBSection( [ 'messages' => $store ], 'messages' );
			SPFactory::cache()->deleteVar( 'system_state', -1, Sobi::Lang( false ), -1 );
			//SPFactory::cache()->cleanSection( -1, false );
		}

		return $this;
	}

	/**
	 * @param $message
	 * @param string $type
	 * @param string $section
	 *
	 * @return SPMessage
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function & setSilentSystemMessage( $message, string $type = C::NOTICE_MSG, string $section = 'configuration' )
	{
		$this->current = [ 'message' => $message, 'type' => $type, 'section' => [ 'id' => Sobi::Section(), 'name' => Sobi::Section( true ) ] ];
		$this->current[ 'issue-type' ] = $section;
		$this->store[ md5( serialize( $this->current ) ) ] = $this->current;
		if ( count( $this->store ) ) {
			$messages = SPConfig::serialize( $this->store );
			$store = [
				'params'      => $messages,
				'key'         => 'queue',
				'value'       => date( DATE_RFC822 ),
				'description' => C::ES,
				'options'     => null,
			];
			SPFactory::registry()->saveDBSection( [ 'messages' => $store ], 'messages' );
			//SPFactory::cache()->cleanSection( -1, false );
		}

		return $this;
	}

	/**
	 * @param $message
	 * @param $spsid
	 * @param string $type
	 *
	 * @return $this
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function & setReport( $message, $spsid, string $type = C::INFO_MSG )
	{
		$this->reports[ $spsid ][ $type ][] = $message;
		if ( count( $this->reports ) ) {
			$messages = SPConfig::serialize( $this->reports );
			$store = [
				'params'      => $messages,
				'key'         => 'queue',
				'value'       => date( DATE_RFC822 ),
				'description' => C::ES,
				'options'     => null,
			];
			SPFactory::registry()->saveDBSection( [ 'reports' => $store ], 'reports' );
		}

		return $this;
	}

	/**
	 * @param $spsid
	 *
	 * @return array
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function getReports( $spsid ): array
	{
		$reports = [];
		if ( array_key_exists( $spsid, $this->reports ) && $this->reports[ $spsid ] ) {
//			$messages = SPConfig::serialize( $this->reports );
			$reports = $this->reports[ $spsid ];
			unset( $this->reports[ $spsid ] );
			/** Thu, Jul 31, 2014 11:12:02
			 * Why the hell we are setting these messages into the db again?
			 */
			$store = [
				'params'      => [],//$messages,
				'key'         => 'queue',
				'value'       => date( DATE_RFC822 ),
				'description' => C::ES,
				'options'     => null,
			];
			SPFactory::registry()->saveDBSection( [ 'reports' => $store ], 'reports' );
		}

		return $reports;
	}

	/**
	 * @param string $spsid
	 *
	 * @return array
	 */
	public function getSystemMessages( $spsid = C::ES )
	{
		return $spsid ? ( $this->reports[ $spsid ] ?? [] ) : $this->store;
	}

//	/**
//	 * @param $id string
//	 * @return SPMessage
//	 */
//	public function & addSystemMessage( $id )
//	{
//		$change = count( $this->store );
//		$this->current[ 'issue-type' ] = $id;
//		$this->store[ md5( serialize( $this->current ) ) ] = $this->current;
//		if ( count( $this->store ) > $change ) {
//			$messages = SPConfig::serialize( $this->store );
//			$store = array(
//				'params' => $messages,
//				'key' => 'queue',
//				'value' => date( DATE_RFC822 ),
//				'description' => null,
//				'options' => null
//			);
//			SPFactory::registry()->saveDBSection( array( 'messages' => $store ), 'messages' );
//			SPFactory::cache()->cleanSection( -1, false );
//		}
//		return $this;
//	}

	/**
	 * @param string|array $message
	 * @param bool $translate
	 * @param bool $display
	 *
	 * @return $this|\SPMessage
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function & info( $message, bool $translate = true, bool $display = true )
	{
		return $this->setMessage( $message, $translate, C::INFO_MSG, $display );
	}

	/**
	 * @param string|array $message
	 * @param bool $translate
	 * @param bool $display
	 *
	 * @return $this|\SPMessage
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function & warning( $message, bool $translate = true, bool $display = true )
	{
		return $this->setMessage( $message, $translate, C::WARN_MSG, $display );
	}

	/**
	 * @param string|array $message
	 * @param bool $translate
	 * @param bool $display
	 *
	 * @return $this|\SPMessage
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function & error( $message, bool $translate = true, bool $display = true )
	{
		return $this->setMessage( $message, $translate, C::ERROR_MSG, $display );
	}

	/**
	 * @param string|array $message
	 * @param bool $translate
	 * @param bool $display
	 *
	 * @return $this|\SPMessage
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function & success( $message, bool $translate = true, bool $display = true )
	{
		return $this->setMessage( $message, $translate, C::SUCCESS_MSG, $display );
	}
}
