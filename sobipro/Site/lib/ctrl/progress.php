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
 * @created 12-Jul-2010 by Radek Suski
 * @modified 13 April 2023 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadController( 'controller' );

use Sobi\C;
use Sobi\FileSystem\FileSystem;
use Sobi\Input\Input;

/**
 * Progress for fetch updates, etc.
 *
 * Class SPProgressCtrl
 */
class SPProgressCtrl extends SPController
{
	/** * @var string */
	private $file;
	/** * @var string */
	private $message = C::ES;
	/** * @var string */
	private $msg = C::ES;
	/** * @var string */
	private $type = C::ES;
	/** * @var int */
	private $progress = 0;
	/** * @var int */
	private $id = 0;
	/** * @var int */
	private $interval = 0;

	/**
	 * SPProgressCtrl constructor.
	 */
	public function __construct()
	{
		$ident = Input::Cmd( 'session' ) ? Input::Cmd( 'SPro_ProgressMsg' . Input::Cmd( 'session' ), 'cookie' ) : Input::Cmd( 'SPro_ProgressMsg', 'cookie' );
		$this->file = SPLoader::path( 'tmp/' . $ident, 'front', false, 'tmp' );
		if ( FileSystem::Exists( $this->file ) ) {
			$content = json_decode( FileSystem::Read( $this->file ), true );
			$this->message = $content[ 'message' ];
			$this->type = $content[ 'type' ];
			$this->progress = $content[ 'progress' ];
			$this->interval = $content[ 'interval' ];
		}
	}

	/**
	 * @return void
	 * @throws SPException
	 */
	public function execute()
	{
		SPFactory::mainframe()
			->cleanBuffer()
			->customHeader();

		if ( FileSystem::Exists( $this->file ) ) {
			echo FileSystem::Read( $this->file );
		}
		else {
			echo json_encode( [ 'progress' => 0, 'message' => C::ES, 'interval' => 100, 'type' => C::ES ] );
		}
		exit;
	}

	/**
	 * @param string $message
	 * @param int $progress
	 * @param int $interval
	 * @param string $type
	 *
	 * @return void
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function status( string $message, int $progress = 0, int $interval = 0, string $type = C::INFO_MSG )
	{
		if ( !strlen( $message ) ) {
			$message = Sobi::Txt( 'PROGRESS_WORKING' );
		}
		$progress = $progress ? : $this->progress;
		$interval = $interval ? : $this->interval;
		$type = $type ? : $this->type;

		$this->progress = $progress;
		$this->message = $message;
		$this->interval = $interval;
		$this->type = $type;

		$typeText = Sobi::Txt( 'STATUS_' . $type );
		$out = json_encode( [ 'progress' => $progress, 'message' => $message, 'interval' => $interval, 'type' => $type, 'typeText' => $typeText ] );

		if ( $type == C::ERROR_MSG ) {
			SPFactory::message()
				->error( $message, false, false )
				->setSystemMessage();
		}
		FileSystem::Write( $this->file, $out );
	}

	/**
	 * @param string $message
	 * @param string $type
	 *
	 * @return void
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function message( string $message, string $type = C::INFO_MSG )
	{
		$this->status( $message, 0, 0, $type );
	}

	/**
	 * @param $message
	 *
	 * @return void
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function error( $message )
	{
		$this->status( $message, 0, 0, C::ERROR_MSG );
	}

	/**
	 * @param int $percent
	 * @param string $message
	 * @param string $type
	 * @param int $interval
	 *
	 * @return void
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function progress( int $percent, string $message = C::ES, string $type = C::INFO_MSG, int $interval = 1000 )
	{
		$this->id = Input::Cmd( 'SPro_ProgressMsg', 'cookie' );
		$percent = ceil( $percent );
		$this->msg = strlen( $message ) ? $message : $this->msg;
		$this->status( $message, $percent, $interval, $type );
	}
}
