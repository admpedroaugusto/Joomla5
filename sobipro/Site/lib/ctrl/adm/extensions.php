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
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 22-Jun-2010 by Radek Suski
 * @modified 01 March 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadController( 'config', true );

use Joomla\Filesystem\File as JFile;
use Sobi\C;
use Sobi\Error\Exception;
use Sobi\FileSystem\File;
use Sobi\FileSystem\FileSystem;
use Sobi\Input\Input;
use Sobi\FileSystem\Directory;
use Sobi\FileSystem\Archive;
use Sobi\Communication\CURL;
use Sobi\Lib\Factory;
use Sobi\Utils\Arr;
use SobiPro\Helpers\MenuTrait;

/**
 * Class SPExtensionsCtrl
 */
class SPExtensionsCtrl extends SPConfigAdmCtrl
{
	use MenuTrait {
		setMenuItems as protected;
	}

	/** @var string */
	protected $_type = 'extensions';
	/** @var string */
	protected $_defTask = 'installed';

	protected const installPath = 'tmp/install/';
	protected const repoPath = 'etc/repos/';
	protected const repoFile = 'repository.xml';
	protected const repoFileOriginal = 'repository.2.0.xml';
	protected const updatesFile = 'etc/updates';
	protected const messageFile = 'tmp/message';

	/* 12 hours */
	protected const checkInterval = 12;

	/**
	 * SPExtensionsCtrl constructor.
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function __construct()
	{
		if ( !Sobi::Can( 'cms.admin' ) ) {
			if ( Sobi::Section() ) {
				if ( !Sobi::Can( 'section.configure' ) ) {
					Sobi::Error( $this->name(), SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ), C::ERROR, 403, __LINE__, __FILE__ );
					exit();
				}
			}
			elseif ( !Sobi::Can( 'cms.apps' ) ) {
				Sobi::Error( $this->name(), SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ), C::ERROR, 403, __LINE__, __FILE__ );
				exit();
			}
		}
	}

	/**
	 * @return void
	 * @throws Exception
	 * @throws ReflectionException
	 * @throws SPException|\DOMException
	 * @throws \Exception
	 */
	public function execute()
	{
		$this->_task = strlen( $this->_task ) ? $this->_task : $this->_defTask;
		switch ( $this->_task ) {
			case 'installed':
				$this->installed();
				Sobi::ReturnPoint();
				break;
			case 'install':
				$this->install();
				break;
			case 'repositories':
				$this->repos();
				Sobi::ReturnPoint();
				break;
			case 'addRepo':
				$this->addRepo();
				break;
			case 'delRepo':
				$this->delRepo();
				break;
			case 'confirmRepo':
				$this->confirmRepo();
				break;
			case 'fetch':
				$this->fetch();
				break;
			case 'registerRepo':
				$this->registerRepo();
				break;
			case 'publish':
			case 'unpublish':
				$this->publish( ( $this->_task == 'publish' ) );
				break;
			case 'toggle':
				$this->toggle();
				break;
			case 'delete':
				$this->delete();
				break;
			case 'browse':
				$this->browse();
				break;
			case 'manage':
				$this->section();
				break;
			case 'updates':
				$this->updates();
				break;
			case 'download':
				$this->download();
				break;
			default:
				/* case plugin didn't register this task, it was an error */
				if ( !( Sobi::Trigger( 'Execute', $this->name(), [ &$this ] ) ) ) {
					Sobi::Error( $this->name(), SPLang::e( 'SUCH_TASK_NOT_FOUND', Input::Task() ), C::NOTICE, 404, __LINE__, __FILE__ );
				}
				break;
		}
	}

	/**
	 * Checks for updated apps, but wait 12 hours between the checks.
	 * AJAX if called from the dashboard updates pane, otherwise called after admin logged into backend.
	 *
	 * @param bool $ajax
	 *
	 * @return array
	 * @throws Exception
	 * @throws SPException|\DOMException
	 */
	public function updates( bool $ajax = true ): array
	{
		if ( $this->updatesTime() ) {
			$repos = new Directory( SPLoader::dirPath( SPC::REPO_PATH, 'front' ) );
			$repos = $repos->searchFile( self::repoFile, true, 2 );
			$repos = array_keys( $repos );

			//are there any repositories installed?
			$repositoryNumbers = count( $repos );
			if ( !$repositoryNumbers ) {   //no
				SPFactory::mainframe()->cleanBuffer();
				// best is to be quiet in this case
				//echo json_encode( [ 'err' => SPLang::e( 'UPD_NO_REPOS_FOUND' ) ] );
				return [];
			}

			$list = [];
			/** @var SPRepository $repository */
			$repository = SPFactory::Instance( 'services.installers.repository' );
			try {
				/* get data of all installed apps */
				$installed = Factory::Db()
					->select( [ 'name', 'type', 'pid', 'version' ], 'spdb_plugins' )
					->loadAssocList();
				/* add SobiPro */
				array_unshift( $installed, [ 'name'    => 'SobiPro',
				                             'type'    => 'core',
				                             'pid'     => 'SobiPro',
				                             'version' => implode( '.', Factory::ApplicationHelper()->myVersion() ) ] );
			}
			catch ( Exception $x ) {
				if ( !$ajax ) {
					throw new Exception( SPLang::e( 'REPO_ERR', $x->getMessage() ) );
				}
				Sobi::Error( 'extensions', SPLang::e( 'CANNOT_GET_UPDATES', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
				SPFactory::mainframe()->cleanBuffer();
				echo json_encode( [ 'err' => SPLang::e( 'REPO_ERR', $x->getMessage() ) ] );
				exit;
			}

			for ( $index = 0; $index < $repositoryNumbers; $index++ ) {
				$repository->loadDefinition( $repos[ $index ] );
				try {
					$repository->connect();
					$repositoryList = $repository->updates( $installed, 2 ); /* for SobiPro 2.x */
				}
				catch ( SPException $x ) {
					SPFactory::message()
						->error( SPLang::e( '%s', $x->getMessage() ), false, false )
						->setSystemMessage();

					if ( !$ajax ) {
						throw new Exception( $x->getMessage() );
					}
					SPFactory::mainframe()->cleanBuffer();
					//echo json_encode( array( 'err' => SPLang::e( '%s Repository: %s', $x->getMessage(), $repository->get( 'id' ) ) ) );
					echo json_encode( [ 'err' => SPLang::e( '%s', $x->getMessage() ) ] );
					exit;
				}
				if ( is_array( $repositoryList ) ) {
					if ( count( $repositoryList ) ) {
						$pid = $repository->get( 'id' );
						foreach ( $repositoryList as $eid => $values ) {
							$values[ 'repository' ] = $pid;
							$repositoryList[ $eid ] = $values;
						}
						$r[ $pid ] = $repository->get( 'url' );
					}
					$list = array_merge( $list, $repositoryList );
				}
			}
			if ( count( $list ) ) {
				$updates = [];
				$updates [ 'created' ] = time();
				$updates [ 'createdBy' ] = [ 'id' => Sobi::My( 'id' ), 'name' => Sobi::My( 'name' ) ];
				$updates [ 'repositories' ] = $r;
				$updates [ 'updates' ] = $list;
				$file = new File( SPLoader::path( self::updatesFile, 'front', false, 'xml' ) );
				$arrUtils = new Arr();
				$file->content( $arrUtils->toXML( $updates, 'updatesList' ) );
				$file->save();
			}
		}

		return $this->parseUpdates( $ajax );
	}

	/**
	 * Checks the file date if it is older than 12 hours.
	 *
	 * @return bool
	 */
	public function updatesTime(): bool
	{
		$file = SPLoader::path( self::updatesFile, 'front', false, 'xml' );
		if ( FileSystem::Exists( $file ) ) {
			/* wait 12 hours until we check again for updated apps */
			return time() - filemtime( $file ) > ( 60 * 60 * self::checkInterval );
		}

		return true;
	}

	/**
	 * Called from update SobiPro: takes the new repository file and add the token from the old.
	 *
	 * @param $repo -> the new repo file, e.g. repository.1.6.xml
	 *
	 * @throws \SPException
	 * @throws \Exception
	 */
	public function updateRepository( $repo )
	{
		$repodir = new Directory( SPLoader::dirPath( SPC::REPO_PATH, 'front' ) );
		$oldRepo = $repodir->searchFile( self::repoFile, true, 2 );
		$newRepo = $repodir->searchFile( $repo, true, 2 );

		/* if both repository files exist */
		if ( $oldRepo && $newRepo ) {
			$oldRepofile = array_keys( $oldRepo )[ 0 ];
			$newRepoFile = array_keys( $newRepo )[ 0 ];

			/** @var SPRepository $repositoryOld */
			$repositoryOld = SPFactory::Instance( 'services.installers.repository' );
			$repositoryOld->loadDefinition( $oldRepofile );
			$oldDefinition = $repositoryOld->getDef();

			/** @var SPRepository $repositoryNew */
			$repositoryNew = SPFactory::Instance( 'services.installers.repository' );
			$repositoryNew->loadDefinition( $newRepoFile );
			$newDefinition = $repositoryNew->getDef();

			if ( array_key_exists( 'repository', $oldDefinition ) && array_key_exists( 'repository', $newDefinition ) ) {
				if ( array_key_exists( 'token', $oldDefinition[ 'repository' ] ) ) {
					/* get the token from the old repository file and add it to the new one */
					$newDefinition[ 'repository' ][ 'token' ] = $oldDefinition[ 'repository' ][ 'token' ];
					$newDefinition[ 'repository' ][ 'description' ] = trim( $newDefinition[ 'repository' ][ 'description' ] );
					/* save the new file content under the old name */
					$file = new File( $oldRepofile );
					$arrUtils = new Arr();
					$file->content( $arrUtils->toXML( $newDefinition[ 'repository' ], 'repository' ) );
					$file->save();
				}
				$file = new File( $newRepoFile );
				$file->delete();
			}
		}
		elseif ( $newRepo ) {
			$newRepoFile = array_keys( $newRepo )[ 0 ];
			JFile::move( $newRepoFile, SOBI_PATH . '/' . SPC::REPO_PATH . 'sobipro_core/' . self::repoFile );
		}
	}

	/**
	 * @param $ajax
	 *
	 * @return array
	 * @throws \SPException
	 */
	protected function parseUpdates( $ajax ): array
	{
		$file = SPLoader::path( self::updatesFile, 'front', true, 'xml' );
		if ( $file ) {
			$arrUtils = new Arr();
			$doc = new DOMDocument();
			$doc->load( $file );
			$list = $arrUtils->fromXML( $doc, 'updateslist' );
			if ( count( $list[ 'updateslist' ][ 'updates' ] ) ) {
				foreach ( $list[ 'updateslist' ][ 'updates' ] as $id => $upd ) {
					if ( $upd[ 'update' ] == 'true' ) {
						$list[ 'updateslist' ][ 'updates' ][ $id ][ 'update_txt' ] = Sobi::Txt( 'UPD.UPDATE_AVAILABLE', $list[ 'updateslist' ][ 'updates' ][ $id ][ 'current' ] );
					}
					else {
						$list[ 'updateslist' ][ 'updates' ][ $id ][ 'update_txt' ] = Sobi::Txt( 'UPD.UP_TO_DATE' );
					}
				}
			}

			return $this->ajaxExit( $list[ 'updateslist' ][ 'updates' ], $ajax );
		}

		return $this->ajaxExit( [], $ajax );
	}

	/**
	 * @throws Exception
	 * @throws ReflectionException
	 * @throws SPException
	 * @throws \Exception
	 */
	protected function section()
	{
		Sobi::ReturnPoint();

		/* create menu */
		//$menu = $this->createMenu( 'extensions.manage' );
		$menu = $this->setMenuItems( 'extensions.manage' );

		/* section model */
		$sid = Sobi::Section();
		/** @var \SPSection $cSec */
		$cSec = SPFactory::Model( 'section' );
		$cSec->init( $sid );

		$db = Factory::Db();
		$all = $db
			->select( '*', 'spdb_plugins', [ '!type' => Sobi::Cfg( 'apps.global_types_array' ), 'enabled' => 1 ] )
			->loadAssocList( 'pid' );
		$list = $db
			->select( '*', 'spdb_plugin_section', [ 'section' => Sobi::Section() ] )
			->loadAssocList( 'pid' );
		if ( count( $all ) ) {
			foreach ( $all as $id => $app ) {
				if ( isset( $list[ $id ] ) ) {
					$all[ $id ][ 'enabled' ] = $list[ $id ][ 'enabled' ];
					$all[ $id ][ 'position' ] = $list[ $id ][ 'position' ];
				}
				else {
					$all[ $id ][ 'enabled' ] = false;
					$all[ $id ][ 'position' ] = 9999;
				}
				$all[ $id ][ 'repository' ] = null;
			}
		}

		/** @var SPExtensionsView $view */
		$view = SPFactory::View( 'extensions', true );
		$view
			->assign( $this->_task, 'task' )
			->assign( $menu, 'menu' )
			->assign( $sid, 'sid' )
			->assign( $all, 'applications' );

		Sobi::Trigger( $this->_task, $this->name(), [ &$view ] );
		$view->display();
		Sobi::Trigger( 'After' . ucfirst( $this->_task ), $this->name(), [ &$view ] );
	}

	/**
	 * @return array
	 * @throws Exception
	 * @throws SPException
	 *
	 * @deprecated as of SobiPro 2.3
	 */
	public function appsMenu(): array
	{
		$links = [];
		$db = Factory::Db();
		$enabled = $db
			->select( 'pid', 'spdb_plugins', [ 'enabled' => 1 ] )
			->loadResultArray();
		$all = $db
			->select( 'pid', 'spdb_plugin_task', [ 'onAction' => 'adm_menu', 'pid' => $enabled ] )
			->loadResultArray();
		if ( count( $all ) ) {
			if ( Sobi::Section() ) {
				$list = $db
					->select( 'pid', 'spdb_plugin_section', [ 'section' => Sobi::Section(), 'pid' => $all, 'enabled' => 1 ] )
					->loadResultArray();
			}
			else {
				$list = $db
					->select( 'pid', 'spdb_plugins', [ 'pid' => $all, 'enabled' => 1 ] )
					->loadResultArray();
			}
			if ( count( $list ) ) {
				foreach ( $list as $app ) {
					if ( SPLoader::translatePath( 'opt/plugins/' . $app . '/init' ) ) {
						$pc = SPLoader::loadClass( $app . '/init', false, 'plugin' );

						/* as of SobiPro 2.3 this method will no longer be used */
						/* the whole method can be removed */
						if ( method_exists( $pc, 'admMenu' ) ) {
							call_user_func_array( [ $pc, 'admMenu' ], [ &$links ] );
						}
					}
					else {
						Sobi::Error( 'Class Load', sprintf( 'Cannot load application file at %s. File does not exist or is not readable.', $app ), C::WARNING, 0 );
					}
				}
			}
		}

		return array_flip( $links );
	}

	/**
	 * @throws Exception
	 * @throws ReflectionException
	 * @throws SPException|\DOMException
	 */
	protected function download()
	{
		if ( Input::Word( 'callback' ) ) {
			$this->downloadRequest();

			return;
		}

		$pid = str_replace( '-', '_', Input::Cmd( 'exid' ) );
		/** @var \SPProgressCtrl $msg */
		$msg = SPFactory::Controller( 'progress' );
		if ( strstr( $pid, '.disabled' ) ) {
			$msg->error( SPLang::e( 'REPO_ERR_APP_DISABLED' ) );
			exit;
		}
		if ( !Factory::Application()->checkToken( 'get' ) ) {
			Sobi::Error( 'Token', SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ), C::WARNING, 0, __LINE__, __FILE__ );
			$msg->error( SPLang::e( 'REPO_ERR', SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ) ) );
			exit;
		}
		$msg->progress( 5, Sobi::Txt( 'EX.CONNECTING_TO_REPO' ) );
		if ( !strlen( $pid ) ) {
			$msg->progress( 100, Sobi::Txt( 'EX.SELECT_EXT_FROM_LIST' ) );
			exit;
		}
		$pid = explode( '.', $pid );
		$rid = $pid[ 0 ];
		$tid = $pid[ 1 ];
		$pid = $pid[ 2 ];

		/** @var SPRepository $repository */
		$repository = SPFactory::Instance( 'services.installers.repository' );
		$repository->loadDefinition( SPLoader::path( SPC::REPO_PATH . "$rid.repository", 'front', true, 'xml' ) );
		$msg->progress( 15, Sobi::Txt( 'EX.CONNECTING_TO_REPO_NAME', [ 'repo' => $repository->get( 'name' ) ] ) );
		try {
			$repository->connect( $msg );
			sleep( 1 );
		}
		catch ( SPException $x ) {
			$msg->error( SPLang::e( 'REPO_ERR', $x->getMessage() ) );
			exit;
		}
		try {
			$response = $repository->request( $repository->get( 'token' ), $tid, $pid, 2 );  /* SobiPro 2.x */
//			sleep( 1 );
		}
		catch ( SPException $x ) {
			$msg->error( SPLang::e( 'REPO_ERR', $x->getMessage() ) );
			exit;
		}
		$msg->progress( 50, Sobi::Txt( 'EX.SENDING_REQUEST_TO', [ 'repo' => $repository->get( 'name' ) ] ), 2000 );
		$this->downloadResponse( $response, $repository, $msg );
	}

	/**
	 * @param $response
	 * @param $repository
	 * @param $msg
	 *
	 * @throws Exception
	 * @throws ReflectionException
	 * @throws SPException|\DOMException
	 */
	protected function downloadResponse( $response, $repository, $msg )
	{
		if ( is_array( $response ) && isset( $response[ 'callback' ] ) ) {
			$progress = $response[ 'progress' ] ?? 45;
			$msg->progress( $progress, Sobi::Txt( 'EX.REPO_FEEDBACK_REQ', [ 'repo' => $repository->get( 'name' ) ] ) );

			$this->parseSoapRequest( $response, null, Input::Cmd( 'plid' ) );
		}
		elseif ( is_array( $response ) && isset( $response[ 'message' ] ) ) {
			$type = $response[ 'message-type' ] ?? C::ERROR_MSG;
			$msg->message( $response[ 'message' ], $type );
			exit;
		}
		elseif ( $response === true || isset( $response[ 'package' ] ) ) {
			$progress = $response[ 'progress' ] ?? 60;
			$msg->progress( $progress, Sobi::Txt( 'EX.REC_PACKAGE_WITH_TYPE_NAME', [ 'type' => Sobi::Txt( strtoupper( $response[ 'type' ] ) ), 'name' => $response[ 'name' ] ] ) );
//			sleep( 1 );
			if ( !$response[ 'package' ] ) {
				$msg->error( SPLang::e( 'PACKAGE_ERR' ) );
			}
			$package = $this->packageToFile( $response[ 'package' ], $response[ 'checksum' ], $response[ 'filename' ], $msg );
			try {
				$result = $this->install( $package );
				$msg->progress( 95, $result[ 'msg' ] );
				$msg->progress( 100, $result[ 'msg' ], $result[ 'msgtype' ] );

				SPFactory::history()->logAction( SPC::LOG_DOWNLOAD,
					0, 0,
					'application',
					C::ES,
					[ 'name' => $response[ 'filename' ], 'type' => $response[ 'type' ] ]
				);
			}
			catch ( SPException $x ) {
				$msg->error( SPLang::e( 'REPO_ERR', $x->getMessage() ) );
				exit;
			}
			exit;
		}
	}

	/**
	 * @throws Exception
	 * @throws ReflectionException
	 * @throws SPException|\DOMException
	 */
	protected function downloadRequest()
	{
		$pid = Input::Cmd( 'exid' );

		/** @var \SPProgressCtrl $msg */
		$msg = SPFactory::Controller( 'progress' );
		$msg->progress( 50, Sobi::Txt( 'EX.CONNECTING_TO_REPO' ) );
		$pid = explode( '.', $pid );
		$repo = $pid[ 0 ];
		$tid = $pid[ 1 ];
		$pid = $pid[ 2 ];
		$data = Input::Search( 'sprpfield_' );
		$answer = [];
		$msg->progress( 55, Sobi::Txt( 'EX.PARSING_RESPONSE' ) );
		if ( count( $data ) ) {
			foreach ( $data as $k => $v ) {
				$v = ( strlen( $v ) && $v != '' ) ? $v : C::NO_VALUE;
				$answer[ str_replace( 'sprpfield_', C::ES, $k ) ] = $v;
			}
		}
		$defFile = SPLoader::path( SPC::REPO_PATH . "$repo.repository", 'front', true, 'xml' );
		/** @var SPRepository $repository */
		$repository = SPFactory::Instance( 'services.installers.repository' );
		$repository->loadDefinition( $defFile );
		try {
			$repository->connect();
		}
		catch ( SPException $x ) {
			$msg->error( SPLang::e( 'REPO_ERR', $x->getMessage() ) );
			exit;
		}
		$callback = Input::Word( 'callback' );
		try {
			array_unshift( $answer, $pid );
			array_unshift( $answer, $tid );
			array_unshift( $answer, $repository->get( 'token' ) );
			$msg->progress( 60, Sobi::Txt( 'EX.SENDING_REQUEST_TO', [ 'repo' => $repository->get( 'name' ) ] ) );
			$response = call_user_func_array( [ $repository, $callback ], $answer );
//			sleep( 2 );
		}
		catch ( SPException $x ) {
			$msg->error( SPLang::e( 'REPO_ERR', $x->getMessage() ) );
			exit;
		}
		$this->downloadResponse( $response, $repository, $msg );
	}

	/**
	 * @param $stream
	 * @param $checksum
	 * @param $name
	 * @param $msg
	 *
	 * @return string|void
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function packageToFile( $stream, $checksum, $name, $msg )
	{
		$path = SPLoader::dirPath( SPC::INSTALL_PATH, 'front', false );
		$stream = base64_decode( $stream );
		$msg->progress( 65, Sobi::Txt( 'EX.EXAMINING_CHECKSUM' ), 1000 );
		try {
			FileSystem::Write( $path . $name, $stream );
		}
		catch ( Exception $x ) {
			$msg->error( SPLang::e( 'REPO_ERR', $x->getMessage() ) );
			exit;
		}
		if ( md5_file( $path . $name ) != $checksum ) {
			$msg->error( SPLang::e( 'EX.CHECKSUM_NOK' ) );
			exit;
		}
//		sleep( 1 );
		$msg->progress( 75, Sobi::Txt( 'EX.CHECKSUM_OK' ) );

		return $path . $name;
	}

	/**
	 * Fetches the current list from the repositories.
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception|\DOMException
	 */
	protected function fetch()
	{
		static $moved = false;

		/** @var SPAdmProgressCtrl $msg */
		$msg = SPFactory::Controller( 'progress' );
		$repofile = self::repoFile;
		if ( !Factory::Application()->checkToken( 'get' ) ) {
			Sobi::Error( 'Token', SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ), C::WARNING, 0, __LINE__, __FILE__ );
			$msg->error( SPLang::e( 'REPO_ERR', SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ) ) );
			exit;
		}
		$msg->progress( 0, Sobi::Txt( 'EX.GETTING_REPOS' ) );
		$repos = new Directory( SPLoader::dirPath( SPC::REPO_PATH, 'front' ) );
		$repos = $repos->searchFile( $repofile, true, 2 );
		$repos = array_keys( $repos );
		$repositoryNumbers = count( $repos );
		if ( $repositoryNumbers == 0 ) {
			$repoFileOriginal = SPLoader::dirPath( SPC::REPO_PATH, 'front' ) . 'sobipro_core/' . self::repoFileOriginal;
			if ( FileSystem::Exists( $repoFileOriginal ) && !$moved ) {
				if ( FileSystem::Move( $repoFileOriginal, SOBI_PATH . '/' . SPC::REPO_PATH . 'sobipro_core/' . self::repoFile ) ) {
					$moved = true;
					$this->fetch();
				}
			}
			$msg->error( SPLang::e( 'REPO_NOTAVAILABLE', SPC::REPO_PATH . 'sobipro_core/' . $repofile ) );
			exit;
		}
		$progress = 5;
		$msg->progress( $progress, Sobi::Txt( 'EX.FOUND_NUM_REPOS', [ 'count' => $repositoryNumbers ] ) );

		/** @var SPRepository $repository */
		$repository = SPFactory::Instance( 'services.installers.repository' );

		$steps = 2;
		$pstep = ( 80 / $repositoryNumbers ) / $steps;

		$list = $repo = [];
		for ( $index = 0; $index < $repositoryNumbers; $index++ ) {
			$repository->loadDefinition( $repos[ $index ] );
			$progress += ( $pstep / $steps );
			$msg->progress( $progress, Sobi::Txt( 'EX.CON_TO_REPO_D_D', [ 'num' => ( $index + 1 ), 'from' => $repositoryNumbers ] ) );
			try {
				$repository->connect( $msg );
				sleep( 1 );
			}
			catch ( SPException $x ) {
				$msg->error( SPLang::e( 'REPO_ERR', $x->getMessage() ) );
				exit;
			}

			$progress += ( $pstep / $steps );
			$msg->progress( $progress, Sobi::Txt( 'EX.FETCHING_FROM_REPO_D_D', [ 'num' => ( $index + 1 ), 'from' => $repositoryNumbers ] ) );
			try {
				$version = Factory::ApplicationHelper()->applicationVersion();
				$repositoryList = $repository->fetchList( $repository->get( 'token' ), 'Joomla ' . $version[ 'major' ] . '.' . $version[ 'minor' ], 2 ); /* for SobiPro 2.x */
			}
			catch ( SPException $x ) {
				$msg->error( SPLang::e( 'REPO_ERR', $x->getMessage() ) );
			}
			if ( is_array( $repositoryList ) ) {
				if ( count( $repositoryList ) ) {
					$pid = $repository->get( 'id' );
					foreach ( $repositoryList as $eid => $values ) {
						$eid = str_replace( [ '.', '_' ], '-', $eid );
						$values[ 'repository' ] = $pid;
						$repositoryList[ $eid ] = $values;
					}
					$repo[ $pid ] = $repository->get( 'url' );
				}
				$list = array_merge( $list, $repositoryList );
			}
			$progress += ( $pstep / $steps );
			$msg->progress( $progress, Sobi::Txt( 'EX.FETCHED_LIST_FROM_REPOSITORY', [ 'count' => count( $repositoryList ), 'num' => ( $index + 1 ), 'from' => $repositoryNumbers ] ) );
		}
		$progress += 5;
		$extensions = [];
		if ( count( $list ) ) {
			$msg->progress( $progress, Sobi::Txt( 'EX.FETCHED_D_EXTENSIONS', [ 'count' => count( $list ) ] ) );
			$extensions[ 'created' ] = time();
			$extensions[ 'createdBy' ] = [ 'id' => Sobi::My( 'id' ), 'name' => Sobi::My( 'name' ) ];
			$extensions[ 'repositories' ] = $repo;
			$extensions[ 'extensions' ] = $list;
		}
		$progress += 10;
		$msg->progress( $progress );

		$arrUtils = new Arr();
		$file = new File( SPLoader::path( 'etc.extensions', 'front', false, 'xml' ) );
		$file->content( $arrUtils->toXML( $extensions, 'extensionsList' ) );
		$msg->progress( $progress, $arrUtils->toXML( $extensions, 'extensionsList' ) );
		try {
			$file->save();
		}
		catch ( Exception $x ) {
			$msg->progress( $progress, $x->getMessage() );
		}

		$msg->progress( 100, Sobi::Txt( 'EX.EXT_LIST_UPDATED' ), C::SUCCESS_MSG );
		SPFactory::history()->logAction( SPC::LOG_REPOFETCH, 0, 0, 'application' );

		exit;
	}

	/**
	 * @throws Exception
	 * @throws ReflectionException
	 * @throws SPException
	 */
	protected function browse()
	{
		/** @var SPExtensionsView $view */
		$view = SPFactory::View( 'extensions', true );
		$arrUtils = new Arr();
		$list = $apps = [];
		if ( FileSystem::Exists( SPLoader::path( 'etc/extensions', 'front', false, 'xml' ) ) ) {
			$list = $arrUtils->fromXML( SPFactory::LoadXML( SPLoader::path( 'etc/extensions', 'front', false, 'xml' ) ), 'extensionslist' );
		}
		if ( !count( $list ) ) {
			//SPFactory::message()->warning( 'EX.MSG_UPDATE_FIRST' );
			$status = [ 'label' => Sobi::Txt( 'EX.LAST_UPDATED_UNKNOWN' ), 'type' => C::INFO_MSG ];
			$view->assign( $status, 'last-update' );
		}
		else {
			$installed = [];
			try {
				$installed = Factory::Db()
					->select( '*', 'spdb_plugins' )
					->loadAssocList();
			}
			catch ( Exception $x ) {
			}
			if ( is_array( $list[ 'extensionslist' ] ) && count( $list[ 'extensionslist' ] ) ) {
				$status = [ 'label' => Sobi::Txt( 'EX.LAST_UPDATED', SPFactory::config()->date( $list[ 'extensionslist' ][ 'created' ], 'date.publishing_format' ) ), 'type' => C::INFO_MSG ];
				$view->assign( $status, 'last-update' );
				$list = $list[ 'extensionslist' ][ 'extensions' ];
				if ( is_array( $list ) && count( $list ) ) {
					foreach ( $list as $pid => $plugin ) {
						$plugin[ 'installed' ] = -1;
						$plugin[ 'action' ] = [ 'text' => Sobi::Txt( 'EX.INSTALL_APP' ), 'class' => 'install' ];
						$eid = $pid;
						if ( $plugin[ 'type' ] == 'language' ) {
							$eid = explode( '-', $pid );
							$eid[ 0 ] = strtolower( $eid[ 0 ] );
							$eid[ 1 ] = strtoupper( $eid[ 1 ] );
							$eid = implode( '-', $eid );
							unset( $list[ $pid ] );
						}
						if ( count( $installed ) ) {
							foreach ( $installed as $ex ) {
								if ( $eid == $ex[ 'pid' ] || str_replace( '_', '-', $ex[ 'pid' ] ) == $eid ) {
									$plugin[ 'installed' ] = -2;
									$plugin[ 'action' ] = [ 'text' => Sobi::Txt( 'EX.REINSTALL_APP' ), 'class' => 'reinstall' ];
									if ( version_compare( $plugin[ 'version' ], $ex[ 'version' ], '>' ) ) {
										$plugin[ 'installed' ] = -3;
										$plugin[ 'action' ] = [ 'text' => Sobi::Txt( 'EX.UPDATE_APP' ), 'class' => 'update' ];
									}
								}
							}
						}
						if ( $plugin[ 'type' ] == 'update' ) {
							$compare = version_compare( $plugin[ 'version' ], implode( '.', Factory::ApplicationHelper()->myVersion() ) );
							if ( $compare <= 0 ) {
								$plugin[ 'installed' ] = -1;
								$eid = $eid . '.disabled';
								$plugin[ 'action' ] = [ 'text' => Sobi::Txt( 'EX.APP_UPDATE_DISABLED' ), 'class' => 'disabled' ];
							}
							else {
								$plugin[ 'installed' ] = -3;
								$plugin[ 'action' ] = [ 'text' => Sobi::Txt( 'EX.UPDATE_CORE' ), 'class' => 'update' ];
							}
						}
						$plugin[ 'pid' ] = $eid;
						$plugin[ 'eid' ] = $plugin[ 'repository' ] . '.' . $plugin[ 'type' ] . '.' . $plugin[ 'pid' ];
						$list[ $eid ] = $plugin;
						$index = in_array( $plugin[ 'type' ], [ 'application', 'field', 'update', 'template', 'language' ] ) ? $plugin[ 'type' ] . 's' : 'others';
						$apps[ $index ][] = $plugin;
					}
					if ( isset( $apps[ 'updates' ] ) ) {
						usort( $apps[ 'updates' ], function ( $from, $to ) {
							return version_compare( $to[ 'version' ], $from[ 'version' ] ) > 0;
						}
						);
					}
				}
			}
			else {
				$status = [ 'label' => Sobi::Txt( 'EX.LIST_EMPTY' ), 'type' => C::INFO_MSG ];
				$view->assign( $status, 'last-update' );
			}
		}

		$repos = [];
		$directory = new Directory( SPLoader::dirPath( SPC::REPO_PATH ) );
		$xml = array_keys( $directory->searchFile( self::repoFile, false, 2 ) );
		foreach ( $xml as $definition ) {
			/** @var SPRepository $repository */
			$repository = SPFactory::Instance( 'services.installers.repository' );
			$repository->loadDefinition( $definition );
			$repos[] = $repository->getDef();
		}

		/* create menu */
		$menu = $this->setMenuItems( 'extensions.' . $this->_task );
		$defaultrepo = 'repository.sigsiu.net';

		$view
			->assign( $this->_task, 'task' )
			->assign( $menu, 'menu' )
			->assign( $apps, 'extensions' )
			->assign( $repos, 'repositories' )
			->assign( $list, 'full-list' )
			->assign( $defaultrepo, 'default-repo' )
			->determineTemplate( 'extensions', $this->_task );

		Sobi::Trigger( $this->_task, $this->name(), [ &$view ] );
		$view->display();

		Sobi::Trigger( 'After' . ucfirst( $this->_task ), $this->name(), [ &$view ] );
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function delete()
	{
		$application = Input::Cmd( 'eid' );
		if ( !strlen( $application ) ) {
			$this->response( Sobi::Url( 'extensions.installed' ), Sobi::Txt( 'EX.SELECT_TO_DELETE_ERR' ), true, C::ERROR_MSG );
		}
		$application = explode( '.', $application );
		$appType = $application[ 0 ];
		$application = $application[ 1 ];
		$definition = SPLoader::path( "etc.installed.{$appType}s.$application", 'front', true, 'xml' );
		if ( !$definition ) {
			Sobi::Error( 'extensions', SPLang::e( 'CANNOT_DELETE_PLUGIN_FILE_NOT_EXISTS', SPLoader::path( "etc.installed.{$appType}s.$application", 'front', false, 'xml' ) ), C::WARNING, 0, __LINE__, __FILE__ );
			$this->response( Sobi::Url( 'extensions.installed' ), Sobi::Txt( 'EX.CANNOT_LOAD_PLUGIN_DEF_ERR' ), true, C::ERROR_MSG );
		}
		/** @var SPAppInstaller $installer */
		$installer = SPFactory::Instance( 'services.installers.sobiproapp', $definition, 'SobiProApp' );
		$this->response( Sobi::Url( 'extensions.installed' ), $installer->remove(), true, C::SUCCESS_MSG );
	}

	/**
	 * @param $state
	 *
	 * @deprecated
	 */
	protected function publish( $state )
	{
		exit( 'Deprecated ' . __FILE__ . ' ' . __LINE__ );
	}

	/**
	 * @throws Exception
	 * @throws SPException
	 */
	protected function toggle()
	{
		$plugin = Input::Cmd( 'eid' );
		$plugin = explode( '.', $plugin );
		$ptype = $plugin[ 0 ];
		$plugin = $plugin[ 1 ];

		if ( Input::Sid() ) {
			try {
				$app = Factory::Db()
					->select( 'name', 'spdb_plugins', [ 'pid' => $plugin, 'type' => $ptype, ] )
					->loadResult();
				$state = !( Factory::Db()
					->select( 'enabled', 'spdb_plugin_section', [ 'section' => Input::Sid(), 'pid' => $plugin, 'type' => $ptype, ] )
					->loadResult() );
				Factory::Db()->replace( 'spdb_plugin_section', [ 'section' => Input::Sid(), 'pid' => $plugin, 'type' => $ptype, 'enabled' => $state, 0 ] );

				SPFactory::history()->logAction( ( $state ? SPC::LOG_ENABLE : SPC::LOG_DISABLE ),
					0,
					Input::Sid(),
					'application',
					C::ES,
					[ 'name' => $app, 'type' => $ptype ]
				);

				$message = $state ? Sobi::Txt( 'EX.APP_ENABLED', $app ) : Sobi::Txt( 'EX.APP_DISABLED', $app );
				$messageType = $state ? 'success' : 'warning';
			}
			catch ( SPException $x ) {
				$message = Sobi::Txt( 'EX.CANNOT_CHANGE_STATE_ERR', 'error' );
				$messageType = 'error';
				Sobi::Error( 'extensions', SPLang::e( 'CANNOT_UPDATE_PLUGIN', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
			}
		}
		else {
			try {
				$app = Factory::Db()
					->select( [ 'enabled', 'name' ], 'spdb_plugins', [ 'pid' => $plugin, 'type' => $ptype, ] )
					->loadObject();

				Factory::Db()->update( 'spdb_plugins', [ 'enabled' => !$app->enabled ], [ 'type' => $ptype, 'pid' => $plugin ] );
				SPFactory::history()->logAction( ( !$app->enabled ? SPC::LOG_PUBLISH : SPC::LOG_UNPUBLISH ),
					0,
					0,
					'application',
					C::ES,
					[ 'name' => $app->name, 'type' => $ptype ]
				);

				$message = !$app->enabled ? Sobi::Txt( 'EX.APP_ENABLED', $app->name ) : Sobi::Txt( 'EX.APP_DISABLED', $app->name );
				$messageType = !$app->enabled ? 'success' : 'warning';
			}
			catch ( SPException $x ) {
				$message = Sobi::Txt( 'EX.CANNOT_CHANGE_STATE_ERR', 'error' );
				$messageType = 'error';
				Sobi::Error( 'extensions', SPLang::e( 'CANNOT_UPDATE_PLUGIN', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
			}
		}
		$this->response( Sobi::Back(), $message, false, $messageType );
	}

	/**
	 * @throws Exception
	 * @throws ReflectionException
	 * @throws SPException
	 */
	protected function registerRepo()
	{
		$repo = trim( preg_replace( '/[^a-zA-Z0-9\.\-\_]/', C::ES, Input::String( 'repository' ) ) );
		$data = Input::Arr( 'RepositoryResponse' );
		$answer = [];
		if ( count( $data ) ) {
			foreach ( $data as $k => $v ) {
				$v = ( strlen( $v ) && $v != '' ) ? $v : C::NO_VALUE;
				$answer[ $k ] = $v;
			}
		}
		$defFile = SPLoader::path( SPC::REPO_PATH . "$repo.repository", 'front', true, 'xml' );
		/** @var SPRepository $repository */
		$repository = SPFactory::Instance( 'services.installers.repository' );
		$repository->loadDefinition( $defFile );
		try {
			$repository->connect();
		}
		catch ( SPException $x ) {
			$this->ajaxResponse( true, SPLang::e( 'REPO_ERR', $x->getMessage() ), Sobi::Url( 'extensions.browse' ), C::ERROR_MSG );
		}
		$callback = Input::Word( 'callback' );
		$response = call_user_func_array( [ $repository, $callback ], $answer );
		if ( is_array( $response ) && isset( $response[ 'callback' ] ) ) {
			$this->parseSoapRequest( $response, $repo );
		}
		elseif ( $response === true || isset( $response[ 'welcome_msg' ] ) ) {
			if ( isset( $response[ 'token' ] ) ) {
				$repository->saveToken( $response[ 'token' ] );

				SPFactory::history()->logAction( SPC::LOG_REPOREGISTER, 0, 0, 'application', C::ES, [ 'name' => $answer[ 'orderid' ] ] );

				/* reset system messages after successful repository update */
				SPFactory::message()->resetSystemMessages();
			}

			if ( isset( $response[ 'welcome_msg' ] ) && $response[ 'welcome_msg' ] ) {
				$message = [ 'message'  => [ 'type'     => C::SUCCESS_MSG,
				                             'response' => $response[ 'welcome_msg' ] ],
				             'callback' => null,
				             'redirect' => true ];
			}
			else {
				$message = [ 'message'  => [ 'type'     => C::SUCCESS_MSG,
				                             'response' => Sobi::Txt( 'EX.REPO_HAS_BEEN_ADDED', [ 'location' => $repo ] ) ],
				             'callback' => null,
				             'redirect' => true ];
			}
			$this->ajaxExit( $message );
		}
		elseif ( isset( $response[ 'error' ] ) ) {
			$this->ajaxResponse( true, SPLang::e( 'REPO_ERR', $response[ 'msg' ] ), Sobi::Url( 'extensions.browse' ), C::ERROR_MSG, false );
		}
		else {
			$this->ajaxResponse( true, SPLang::e( 'UNKNOWN_ERR' ), Sobi::Url( 'extensions.browse' ), C::ERROR_MSG, false );
		}
	}

	/**
	 * @param $response
	 * @param null $repositoryId
	 * @param null $applicationId
	 *
	 * @throws Exception
	 * @throws ReflectionException
	 * @throws SPException
	 */
	protected function parseSoapRequest( $response, $repositoryId = null, $applicationId = null )
	{
		if ( !Factory::Application()->checkToken() ) {
			Sobi::Error( 'Token', SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ), C::WARNING, 0, __LINE__, __FILE__ );
			$this->response( Sobi::Url( 'extensions.browse' ), SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ), false, C::ERROR_MSG );
			exit;
		}

		/** @var SPExtensionsView $view */
		$view = SPFactory::View( 'extensions', true );
		$callback = $response[ 'callback' ];
		unset( $response[ 'callback' ] );
		if ( isset( $response[ 'message' ] ) ) {
			$message = Sobi::Txt( $response[ 'message' ] );
			$view->assign( $message, 'message' );
			unset( $response[ 'message' ] );
		}
		$fields = [];
		foreach ( $response as $values ) {
			if ( isset( $values[ 'params' ][ 'style' ] ) ) {
				unset( $values[ 'params' ][ 'style' ] );
			}
			if ( isset( $values[ 'params' ][ 'class' ] ) ) {
				unset( $values[ 'params' ][ 'class' ] );
			}
			$values[ 'name' ] = 'RepositoryResponse[' . $values[ 'params' ][ 'id' ] . ']';
			$fields[] = $values;
		}
		$fields[] = [ 'label'    => 'Website URL',
		              'value'    => Sobi::Cfg( 'live_site' ),
		              'name'     => 'RepositoryResponse[url]',
		              'type'     => 'text',
		              'required' => true,
		              'params'   => [ 'id'       => 'url', /*'size' => 30,*/
		                              'readonly' => 'readonly' ] ];
		$request = [ 'fields' => $fields ];
		$view->assign( $request, 'request' );
		$view->determineTemplate( 'extensions', 'soap-request' );
		ob_start();
		$view->display();

		$response = ob_get_contents();
		$response = str_replace( [ 'id="SobiPro"', 'id="SPAdminForm"' ], [ 'id="spctrl-repo-modal"', 'id="spctrl-form-modal"' ], $response );

		if ( $repositoryId ) {
			$resp = [ 'message'    => [ 'type' => C::INFO_MSG, 'response' => $response ],
			          'repository' => $repositoryId,
			          'callback'   => $callback ];
		}
		else {
			$resp = [ 'message'   => [ 'type' => C::INFO_MSG, 'response' => $response ],
			          'extension' => $applicationId,
			          'callback'  => $callback ];
		}
		$this->ajaxExit( $resp );
	}

	/**
	 * AJAX
	 *
	 * @return array|void
	 * @throws Exception
	 * @throws ReflectionException
	 * @throws SPException|\DOMException
	 */
	protected function confirmRepo()
	{
		$repositoryId = trim( preg_replace( '/[^a-zA-Z0-9\.\-\_]/', C::ES, Input::String( 'repository' ) ) );
		$connection = new CURL();
		$errno = $connection->error( false, true );
		$status = $connection->status( false, true );

		/* if CURL initialisation failed (CURL not installed) */
		if ( $status || $errno ) {
			$this->ajaxExit( [ 'message' => [ 'type' => C::ERROR_MSG,
			                                  'text' => 'Code ' . $status ? $connection->status() : $connection->error() ] ] );

			return;
		}

		$connection->setOptions(
			[
				'url' => "https://$repositoryId/repository.xml",
				'connecttimeout' => 10,
				'header'         => false,
				'returntransfer' => true,
				'ssl_verifypeer' => false,
				'ssl_verifyhost' => 2,
			]
		);
		$path = SPLoader::path( SPC::REPO_PATH . str_replace( '.', '_', $repositoryId ), 'front', false, 'xml' );
		$file = new File( $path );

		$info = $connection->exec();
		$connectionInfo = $connection->info();
		if ( isset( $connectionInfo[ 'http_code' ] ) && $connectionInfo[ 'http_code' ] != 200 ) {
			$response = SPLang::e( 'Error (%d) has occurred and the repository at "%s" could not be added.', $connectionInfo[ 'http_code' ], "https://$repositoryId" );
			$this->ajaxExit( [ 'message' => [ 'type' => C::ERROR_MSG, 'text' => $response ] ] );
		}
		else {
			$arrUtils = new Arr();

			$remoteDef = new DOMDocument( '1.0' );
			$remoteDef->loadXML( $info );
			if ( !$remoteDef->schemaValidate( $this->repoSchema() ) ) {
				$this->ajaxResponse( true, SPLang::e( 'SCHEME_ERR', "https://$repositoryId/repository.xml", "https://xml.sigsiu.net/SobiPro/repository.xsd" ), Sobi::Url( 'extensions.browse' ), C::ERROR_MSG );
			}

			$definitionFile = new DOMDocument( '1.0' );
			$definitionFile->load( $path );
			$definition = $arrUtils->fromXML( $definitionFile, 'repository' );
			$remoteDefinition = $arrUtils->fromXML( $remoteDef, 'repository' );

			$repoDef = [];
			$repoDef[ 'name' ] = $remoteDefinition[ 'repository' ][ 'name' ];
			$repoDef[ 'id' ] = $remoteDefinition[ 'repository' ][ 'id' ];
			$repoDef[ 'url' ] = $definition[ 'repository' ][ 'url' ] . '/' . $remoteDefinition[ 'repository' ][ 'repositoryLocation' ];
			$repoDef[ 'certificate' ] = $definition[ 'repository' ][ 'certificate' ];
			$repoDef[ 'description' ] = $remoteDefinition[ 'repository' ][ 'description' ];
			$repoDef[ 'maintainer' ] = $remoteDefinition[ 'repository' ][ 'maintainer' ];

			$file->delete();
			$directory = SPLoader::dirPath( SPC::REPO_PATH . str_replace( '.', '_', $repoDef[ 'id' ] ), 'front', false );
			try {
				if ( Sobi::Cfg( 'ftp_mode' ) ) {
					FileSystem::Mkdir( $directory, 0777 );
				}
				else {
					FileSystem::Mkdir( $directory );
				}
			}
			catch ( Exception $x ) {
				return $this->ajaxResponse( true, $x->getMessage(), false, C::ERROR_MSG );
			}

			$file = new File( $directory . '/repository.xml' );
			$file->content( $arrUtils->toXML( $repoDef, 'repository' ) );
			$file->save();

			/** @var SPRepository $repository */
			$repository = SPFactory::Instance( 'services.installers.repository' );
			$repository->loadDefinition( $file->getName() );
			try {
				$repository->connect();
			}
			catch ( SPException $x ) {
				$this->ajaxResponse( true, SPLang::e( 'REPO_ERR', $x->getMessage() ), Sobi::Url( 'extensions.browse' ), C::ERROR_MSG );
			}
			$response = $repository->register();
			SPFactory::history()->logAction( SPC::LOG_REPOINSTALL, 0, 0, 'application', C::ES, [ 'name' => $repositoryId ] );

			if ( is_array( $response ) && isset( $response[ 'callback' ] ) ) {
				$this->parseSoapRequest( $response, $repoDef[ 'id' ] );
			}
			else {
				if ( $response === true || isset( $response[ 'welcome_msg' ] ) ) {
					if ( isset( $response[ 'welcome_msg' ] ) && $response[ 'welcome_msg' ] ) {
						$this->ajaxResponse( true, Sobi::Txt( 'EX.REPO_HAS_BEEN_ADDED_WITH_MSG', [ 'location' => $repositoryId, 'msg' => $response[ 'welcome_msg' ] ] ), Sobi::Url( 'extensions.browse' ), C::SUCCESS_MSG );
					}
					else {
						$this->ajaxResponse( true, Sobi::Txt( 'EX.REPO_HAS_BEEN_ADDED_WITH_MSG', [ 'location' => $repositoryId ] ), Sobi::Url( 'extensions.browse' ), C::SUCCESS_MSG );
					}
				}
				else {
					if ( isset( $response[ 'error' ] ) ) {
						$this->ajaxResponse( true, SPLang::e( 'REPO_ERR', $response[ 'msg' ] ), Sobi::Url( 'extensions.browse' ), C::ERROR_MSG );
					}
					else {
						$this->ajaxResponse( true, SPLang::e( 'UNKNOWN_ERR' ), Sobi::Url( 'extensions.browse' ), C::ERROR_MSG );
						exit;
					}
				}
			}
		}
	}

	/**
	 * @return string|void
	 * @throws \Sobi\Error\Exception
	 */
	protected function repoSchema()
	{
		$connection = new CURL();
		$errno = $connection->error( false, true );
		$status = $connection->status( false, true );
		/* if CURL initialisation failed (CURL not installed) */
		if ( $status || $errno ) {
			$errMessage = 'Code ' . $status ? $connection->status() : $connection->error();
			echo json_encode( [ 'message' => [ 'type' => C::ERROR_MSG, 'text' => $errMessage ] ] );
			exit;
		}
		$connection->setOptions(
			[
				'url' => 'https://xml.sigsiu.net/SobiPro/repository.xsd',
				'connecttimeout' => 10,
				'header'         => false,
				'returntransfer' => true,
				'ssl_verifypeer' => false,
				'ssl_verifyhost' => 2,
			]
		);
		$schema = new File( SPLoader::path( 'lib.services.installers.schemas.repository', 'front', false, 'xsd' ) );
		$file = $connection->exec();
		$schema->content( $file );
		$schema->save();

		return $schema->getName();
	}

	/**
	 * AJAX
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function delRepo()
	{
		if ( !Factory::Application()->checkToken() ) {
			Sobi::Error( 'Token', SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ), C::WARNING, 0, __LINE__, __FILE__ );
			$this->response( Sobi::Url( 'extensions.browse' ), Sobi::Txt( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ), true, C::ERROR_MSG );
		}
		$repository = Input::Cmd( 'repository' );
		if ( $repository ) {
			if ( FileSystem::Rmdir( SPLoader::dirPath( SPC::REPO_PATH . $repository ) ) ) {
				$this->response( Sobi::Url( 'extensions.browse' ), Sobi::Txt( 'EX.REPOSITORY_DELETED' ), true, C::SUCCESS_MSG );
			}
			else {
				$this->response( Sobi::Url( 'extensions.browse' ), Sobi::Txt( 'EX.DEL_REPO_ERROR' ), true, C::ERROR_MSG );
			}
		}
	}

	/**
	 * AJAX
	 *
	 * @throws Exception
	 * @throws ReflectionException
	 * @throws SPException|\DOMException
	 */
	protected function addRepo()
	{
		if ( !Factory::Application()->checkToken() ) {
			Sobi::Error( 'Token', SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ), C::WARNING, 0, __LINE__, __FILE__ );
			$this->response( Sobi::Url( 'extensions.browse' ), SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ), false, C::ERROR_MSG );
			exit;
		}

		$connection = new CURL();
		$errno = $connection->error( false, true );
		$status = $connection->status( false, true );
		/* if CURL initialisation failed (CURL not installed) */
		if ( $status || $errno ) {
			echo json_encode( [ 'message' => [ 'type' => C::ERROR_MSG, 'text' => 'Code ' . $status ? $connection->status() : $connection->error() ] ] );
			exit;
		}

		$repo = trim( preg_replace( '/[^a-zA-Z0-9\.\-\_]/', C::ES, Input::String( 'repository' ) ) );
		$ssl = $connection->certificate( $repo );
		if ( isset( $ssl[ 'err' ] ) ) {
			$response = SPLang::e( 'NOT_VALIDATED', $ssl[ 'err' ], $ssl[ 'msg' ] );
			$this->ajaxExit( [ 'message' => [ 'type' => C::ERROR_MSG, 'text' => $response ] ] );
		}
		else {
			$cert = [];
			$file = new File( SPLoader::path( SPC::REPO_PATH . str_replace( '.', '_', $repo ), 'front', false, 'xml' ) );
			$cert[ 'url' ] = 'https://' . $repo;
			$cert[ 'certificate' ][ 'serialNumber' ] = '0x' . $ssl[ 'serialNumberHex' ];
			$cert[ 'certificate' ][ 'validFrom' ] = SPFactory::config()->date( $ssl[ 'validFrom_time_t' ], 'date.publishing_format' );
			$cert[ 'certificate' ][ 'validTo' ] = SPFactory::config()->date( $ssl[ 'validTo_time_t' ], 'date.publishing_format' );
			$cert[ 'certificate' ][ 'subject' ] = $ssl[ 'subject' ];
			$cert[ 'certificate' ][ 'issuer' ] = $ssl[ 'issuer' ];
			$cert[ 'certificate' ][ 'hash' ] = $ssl[ 'hash' ];

			$arrUtils = new Arr();
			$file->content( $arrUtils->toXML( $cert, 'repository' ) );
			$file->save();

			/** @var SPExtensionsView $view */
			$view =& SPFactory::View( 'extensions', true );
			$view
				->assign( $this->_task, 'task' )
				->assign( $cert[ 'certificate' ], 'certificate' )
				->determineTemplate( 'extensions', 'certificate' );
			ob_start();
			$view->display();

			$response = ob_get_contents();
			$response = str_replace( [ 'id="SobiPro"', 'id="SPAdminForm"' ], [ 'id="spctrl-repo-modal"', 'id="spctrl-form-modal"' ], $response );

			$this->ajaxExit( [ 'message' => [ 'type' => C::INFO_MSG, 'response' => $response ] ] );
		}
	}

	/**
	 * @throws \SPException
	 */
	protected function repos()
	{
		Sobi::Redirect( Sobi::Url( 'extensions.browse' ), C::ES, C::ES, true );
	}

	/**
	 * @param string $file
	 *
	 * @return array
	 * @throws Exception
	 * @throws SPException|\DOMException
	 */
	protected function install( $file = C::ES )
	{
		$arch = new Archive();
		$ajax = strlen( Input::Cmd( 'ident', 'post', C::ES ) );
		if ( !$file && Input::String( 'root' ) ) {
			$file = str_replace( '.xml', C::ES, Input::String( 'root' ) );
			$file = SPLoader::path( SPC::INSTALL_PATH . $file, 'front', true, 'xml' );
		}
		if ( !$file ) {
			$ident = Input::Cmd( 'ident', 'post', C::ES );
			$data = SPRequest::file( $ident );  //!!! Input::File() funktioniert nicht!!!
			$name = $path = C::ES;
			if ( $data ) {
				$name = str_replace( [ '.' . FileSystem::GetExt( $data[ 'name' ] ), '.' ], C::ES, $data[ 'name' ] );
				$path = SPLoader::dirPath( SPC::INSTALL_PATH . $name, 'front', false );
			}
			$counter = 0;
			while ( FileSystem::Exists( $path ) ) {
				$path = SPLoader::dirPath( SPC::INSTALL_PATH . $name . '_' . ++$counter, 'front', false );
			}
			/** temp directory - will be removed later, but it needs to be writable for apache and Joomla! fs (FTP mode)*/
			try {
				if ( Sobi::Cfg( 'ftp_mode' ) ) {
					FileSystem::Mkdir( $path, 0777 );
				}
				else {
					FileSystem::Mkdir( $path );
				}
			}
			catch ( \Exception $x ) {
				return $this->ajaxResponse( $ajax, $x->getMessage(), false, C::ERROR_MSG );
			}
			$file = $path . '/' . $data[ 'name' ];
			try {
				$arch->upload( $data[ 'tmp_name' ], $file );
			}
			catch ( Exception $x ) {
				return $this->ajaxResponse( $ajax, $x->getMessage(), false, C::ERROR_MSG );
			}
		}
		// force update
		else {
			if ( Input::String( 'root' ) && $file ) {
				$path = dirname( $file );
			}
			else {
				$arch->setFile( $file );
				$name = str_replace( [ '.' . FileSystem::GetExt( $file ), '.' ], C::ES, basename( $file ) );
				$path = SPLoader::dirPath( SPC::INSTALL_PATH . $name, 'front', false );
				$counter = 0;
				while ( FileSystem::Exists( $path ) ) {
					$path = SPLoader::dirPath( SPC::INSTALL_PATH . $name . '_' . ++$counter, 'front', false );
				}
				/*
				 * temp directory - will be removed later, but it needs to  writable for apache and Joomla! fs (FTP mode)
				 */
				try {
					if ( Sobi::Cfg( 'ftp_mode' ) ) {
						FileSystem::Mkdir( $path, 0777 );
					}
					else {
						FileSystem::Mkdir( $path );
					}
				}
				catch ( \Exception $x ) {
					return $this->ajaxResponse( $ajax, $x->getMessage(), false, C::ERROR_MSG );
				}

			}
		}
		if ( $path ) {
			if ( !Input::String( 'root' ) ) {
				if ( !$arch->extract( $path ) ) {
					return $this->ajaxResponse( $ajax, SPLang::e( 'CANNOT_EXTRACT_ARCHIVE', basename( $file ), $path ), false, C::ERROR_MSG );
				}
			}
			$directory = new Directory( $path );
			$xml = array_keys( $directory->searchFile( '.xml', false, 2 ) );
			if ( !count( $xml ) ) {
				return $this->ajaxResponse( $ajax, SPLang::e( 'NO_INSTALL_FILE_IN_PACKAGE' ), false, C::ERROR_MSG );
			}
			$definition = $this->searchInstallFile( $xml );

			// Joomla package (module or plugin)
			if ( !$definition ) {
				$installerFile = Factory::ApplicationInstaller()->installerFile( $xml, 'SobiPro' );
				if ( $installerFile ) {
					try {
						$message = SPFactory::Instance( 'cms.base.installer' )->install( $installerFile, $xml, $path );
						$this->clearUpdatesfile();

						return $this->ajaxResponse( $ajax, $message[ 'msg' ], $ajax, $message[ 'msgtype' ] );
					}
					catch ( SPException $x ) {
						return $this->ajaxResponse( $ajax, $x->getMessage(), $ajax, C::ERROR_MSG );
					}
				}
				else {
					return $this->ajaxResponse( $ajax, SPLang::e( 'NO_INSTALL_FILE_IN_PACKAGE' ), false, C::ERROR_MSG );
				}
			}

			// SobiPro application (field, application)
			$installer =& SPFactory::Instance( 'services.installers.' . trim( strtolower( $definition->documentElement->tagName ) ), $xml[ 0 ], trim( $definition->documentElement->tagName ) );
			try {
				/** @var SPAppInstaller $installer */
				$installer->validate();
				$msg = $installer->install();
				$this->clearUpdatesfile();

				return $this->ajaxResponse( $ajax, $msg, true, C::SUCCESS_MSG );
			}
			catch ( SPException $x ) {
				return $this->ajaxResponse( $ajax, $x->getMessage(), false, C::ERROR_MSG );
			}
		}
		else {
			return $this->ajaxResponse( $ajax, SPLang::e( 'NO_FILE_HAS_BEEN_UPLOADED' ), false, C::ERROR_MSG );
		}
	}

	/**
	 * @param $xml
	 *
	 * @return bool|DOMDocument
	 */
	protected function searchInstallFile( &$xml )
	{
		foreach ( $xml as $file ) {
			$definition = new DOMDocument();
			$definition->load( $file );
			if ( in_array( trim( $definition->documentElement->tagName ), [ 'template', 'SobiProApp' ] ) ) {
				$xml = [ $file ];

				return $definition;
			}
		}

		return false;
	}

	/**
	 * @return void
	 */
	protected function clearUpdatesfile()
	{
		if ( SPLoader::path( self::messageFile, 'front', true, 'json' ) ) {
			FileSystem::Delete( SPLoader::path( self::messageFile, 'front', true, 'json' ) );
		}
		if ( SPLoader::path( self::updatesFile, 'front', true, 'xml' ) ) {
			FileSystem::Delete( SPLoader::path( self::updatesFile, 'front', true, 'xml' ) );
		}
	}

	/**
	 * @throws Exception
	 * @throws ReflectionException
	 * @throws SPException
	 */
	protected function installed()
	{
		$list = [];
		try {
			Factory::Db()->select( '*', 'spdb_plugins', C::ES, 'name' );
			$list = Factory::Db()->loadAssocList();
		}
		catch ( Exception $x ) {
		}

		$listcount = count( $list );
		for ( $index = 0; $index < $listcount; $index++ ) {
			$list[ $index ][ 'locked' ] = !SPLoader::path( "etc/installed/{$list[$index]['type']}s/{$list[$index]['pid']}", 'front', true, 'xml' );
			$list[ $index ][ 'eid' ] = $list[ $index ][ 'type' ] . '.' . $list[ $index ][ 'pid' ];
			if ( ( $list[ $index ][ 'pid' ] == 'router' ) || ( in_array( $list[ $index ][ 'type' ], [ 'field', 'language', 'module', 'plugin' ] ) ) ) {
				$list[ $index ][ 'enabled' ] = -1;
			}
		}

		/** @var SPExtensionsView $view */
		$trim = defined( 'SOBI_TRIMMED' );
		$view = SPFactory::View( 'extensions', true );

		/* create menu */
		$menu = $this->setMenuItems( 'extensions.' . $this->_task );

		$view
			->assign( $this->_task, 'task' )
			->assign( $menu, 'menu' )
			->assign( $trim, 'trim' )
			->assign( $list, 'applications' )
			->determineTemplate( 'extensions', $this->_task );

		Sobi::Trigger( $this->_task, $this->name(), [ &$view ] );
		$view->display();
		Sobi::Trigger( 'After' . ucfirst( $this->_task ), $this->name(), [ &$view ] );
	}

	/**
	 * @param array $response
	 * @param bool $ajax
	 *
	 * @return array
	 * @throws \SPException
	 */
	protected function ajaxExit( array $response, bool $ajax = true ): array
	{
		if ( $ajax ) {
			SPFactory::mainframe()
				->cleanBuffer()
				->customHeader();

			echo json_encode( $response );
			exit;
		}

		return $response;
	}

	/**
	 * @param $ajax
	 * @param $message
	 * @param $redirect
	 * @param string $type
	 * @param string $callback
	 *
	 * @return array
	 * @throws \SPException
	 * @throws \Exception
	 */
	protected function ajaxResponse( $ajax, $message, $redirect, $type, string $callback = 'SPExtensionInstaller' ): array
	{
		if ( $ajax ) {
			if ( $redirect ) {
				SPFactory::message()->setMessage( $message, false, $type );
			}
			$response = [
				'type'     => $type,
				'text'     => $message,
				'redirect' => $redirect ? Sobi::Url( 'extensions.installed' ) : false,
				'callback' => $type == C::SUCCESS_MSG ? $callback : false,
			];
			$this->ajaxExit( $response );

			return [];
		}
		else {
			if ( $redirect ) {
				SPFactory::message()->setMessage( $message, false, $type );
				Sobi::Redirect( Sobi::Url( 'extensions.installed' ) );
			}

			return [ 'msg' => $message, 'msgtype' => $type ];
		}
	}
}
