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
 * @created 10-Jan-2009 by Radek Suski
 * @modified 22 January 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

use Sobi\C;
use Sobi\Framework;
use Sobi\Input\Input;
use Sobi\Lib\Factory;
use Sobi\Utils\StringUtils;

/**
 * Class SobiProAdmCtrl
 */
final class SobiProAdmCtrl
{
	/*** @var SPMainFrame */
	private $_mainframe = null;
	/*** @var SPConfig */
	private $_config = null;
	/*** @var int */
	private $_mem = 0;
	/*** @var int */
	private $_time = 0;
	/*** @var int */
	private $_section = 0;
	/*** @var string */
	private $_task = C::ES;
	/*** @var int */
	private $_sid = 0;
	/*** @var SPUser */
	private $_user = null;
	/*** @var SPController - could be also array of */
	private $_ctrl = null;
	/*** @var mixed */
	private $_model = null;
	/*** @var string */
	private $_type = 'component';
	/*** @var int */
	private $_deb;

	/**
	 * @param string $task
	 *
	 * @throws Exception
	 */
	public function __construct( $task )
	{
		SPLoader::loadClass( 'base.exception' );
		set_error_handler( 'SPExceptionHandler' );
		$error = ini_set( 'display_errors', 'on' );
		$this->_mem = memory_get_usage();
		$this->_time = microtime( true );
		$this->_task = $task;
		/* load all needed classes */
		SPLoader::loadClass( 'base.factory' );
		SPLoader::loadClass( 'base.object' );
		SPLoader::loadClass( 'base.const' );
		SPLoader::loadClass( 'base.filter' );
		SPLoader::loadClass( 'base.request' );
		SPLoader::loadClass( 'sobi' );
		SPLoader::loadClass( 'base.config' );

		/* Initialise the framework messages */
		Framework::SetTranslator( [ 'SPlang', 'txt' ] );
		Framework::SetErrorTranslator( [ 'SPlang', 'error' ] );
		Framework::SetConfig( [ 'Sobi', 'Cfg' ] );

		/* authorise access */
		$this->checkAccess();
		/* initialise mainframe interface to CMS */
		$this->_mainframe = SPFactory::mainframe();

		/* get sid if any */
		$this->_sid = Input::Sid();
		/* determine section */
		$this->getSection();
		/* initialise config */
		$this->createConfig();

		ini_set( 'display_errors', Sobi::Cfg( 'debug.display_errors', false ) );
		$this->_deb = error_reporting( (int) Sobi::Cfg( 'debug.level', 0 ) );

		/* trigger plugin */
		Sobi::Trigger( 'AdminStart' );

		/* initialise translator and load language files */
		SPLoader::loadClass( 'cms.base.lang' );
		SPLang::setLang( Sobi::Lang( false ) );
		try {
			SPLang::registerDomain( 'admin' );
		}
		catch ( SPException $x ) {
			Sobi::Error( 'CoreCtrl', SPLang::e( 'Cannot register language domain: %s.', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}
		/* load admin html files */
		SPFactory::header()->initBase( true );

		/** @noinspection PhpParamsInspection */
		if ( $this->_section ) {
			$name = C::ES;
			$sectionName = SPLang::translateObject( $this->_section, 'name', 'section' );
			if ( is_array( $sectionName ) && count( $sectionName ) ) {
				$name = StringUtils::Clean( $sectionName[ $this->_section ][ 'value' ] );
			}
			SPFactory::registry()->set( 'current_section_name', $name );
		}
		if ( $this->_section && !Sobi::Cfg( 'section.template' ) ) {
			SPFactory::config()->set( 'template', SPC::DEFAULT_TEMPLATE, 'section' );
		}
		/* check if it wasn't plugin custom task */
		if ( !( Sobi::Trigger( 'custom', 'task', [ $this, Input::Task() ] ) ) ) {
			/* if not, start to route */
			try {
				$this->route();
			}
			catch ( SPException $x ) {
				Sobi::Error( 'CoreCtrl', SPLang::e( 'Cannot route: %s.', $x->getMessage() ), C::ERROR, 500, __LINE__, __FILE__ );
			}
		}

		return true;
	}

	/**
	 * Just few debug messages if enabled.
	 */
	public function __destruct()
	{
		$this->_mem = number_format( ( ( memory_get_usage() - $this->_mem ) / 1024 / 1024 ), 2 );
		$this->_time = number_format( ( ( microtime( true ) - $this->_time ) ), 2 );
//		$db = Sobi\Lib\Factory::Db();
//		SPConfig::debOut( "Number of Queries: " . $db->getCount() . " / Memory: {$this->_mem} MB / Time: {$this->_time} Seconds / Loaded files " . SPLoader::getCount(), true );
//		exit;
	}

	/**
	 * Checks access permissions.
	 *
	 * @throws SPException|\Sobi\Error\Exception
	 */
	private function checkAccess()
	{
		/* authorise access permissions */
		if ( !Sobi::Can( 'cms.manage' ) ) {
			Sobi::Error( 'CoreCtrl', ( 'Unauthorised Access.' ), C::WARNING, 403, __LINE__, __FILE__ );
		}
	}

	/**
	 * Initialises the config object.
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function createConfig()
	{
		$this->_config = &SPFactory::config();
		/* load basic configuration settings */
		$this->_config->addIniFile( 'etc.config', true );
		$this->_config->addIniFile( 'etc.base', true );
		$this->_config->addIniFile( 'etc.adm.config', true );
		$this->_config->addTable( 'spdb_config', $this->_section );
		/* initialise interface config setting */
		$this->_mainframe->getBasicCfg();
		/* initialise config */
		$this->_config->init();
	}

	/**
	 * Gets the right section.
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function getSection()
	{
		/* get the parent id if available */
		$pid = Input::Pid();
		/* if not available, get the stored sid */
		$pid = $pid ? : $this->_sid;

		if ( $pid ) {
			/* if $pid is already a section */
			$this->_model = SPFactory::object( $pid );
			if ( $this->_model && $this->_model->oType == 'section' ) {
				$this->_section = $this->_model->id;
			}

			/* the object table does not yet contain the section id */
			else {
				/* get the section id from the relations path.
				For entries, this is available only if entry is assigned to a category. */
				$sid = SPFactory::config()->getParentPathSection( $pid );
				if ( $sid ) {
					$this->_section = $sid;
				}

				/* Fallback: if we still not have a section id, try the old way */
				else {
					$this->_section = SPFactory::config()->getSectionLegacy( $pid );
				}
			}
		}
		else {
			$this->_section = 0;
		}
		SPFactory::registry()->set( 'current_section', $this->_section );
	}

	/**
	 * Try to find out what we have to do
	 *  - If we have a task - parse task
	 *  - If we don't have a task, but sid, we are going via default object task
	 *  - Otherwise it could be only the frontpage.
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function route()
	{
		/* if we have a task */
		if ( $this->_task && $this->_task != 'panel' ) {
			if ( !$this->routeTask() ) {
				throw new SPException( SPLang::e( 'Cannot interpret task "%s"', $this->_task ) );
			}
		}
		/* if there is no task - execute default task for object */
		else {
			if ( $this->_sid ) {
				if ( !$this->routeObj() ) {
					throw new SPException( SPLang::e( 'Cannot route object with id "%d"', $this->_sid ) );
				}
			}
			/* otherwise show the frontpage */
			else {
				$this->frontpage();
			}
		}
	}

	/**
	 * Routes by task.
	 *
	 * @return bool
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function routeTask()
	{
		$r = true;
		if ( strstr( $this->_task, '.' ) ) {
			/* task consist of the real task and the object type */
			$task = explode( '.', $this->_task );
			$obj = trim( array_shift( $task ) );
			$task = trim( implode( '.', $task ) );

			/* load the controller class definition and get the class name */
			$ctrl = SPLoader::loadController( $obj, true );

			/* route task for multiple objects - e.g removing or publishing elements from a list */
			$sids = Input::Arr( 'sid' );
			$csids = Input::Arr( 'c_sid' );
			$esids = Input::Arr( 'e_sid' );
			if ( count( $sids ) || count( $csids ) || count( $esids ) ) {
				$sid = array_key_exists( 'sid', $_REQUEST ) && is_array( $_REQUEST[ 'sid' ] ) ? 'sid' : ( array_key_exists( 'c_sid', $_REQUEST ) ? 'c_sid' : 'e_sid' );
				if ( count( Input::Arr( $sid ) ) ) {
					$db = Factory::Db();
					$objects = null;
					try {
						$db->select( '*', 'spdb_object', [ 'id' => Input::Arr( $sid ) ] );
						$objects = $db->loadObjectList();
					}
					catch ( Sobi\Error\Exception $x ) {
						Sobi::Error( 'CoreCtrl', SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), SPC::ERROR, 500, __LINE__, __FILE__ );
						$r = false;
					}
					if ( count( $objects ) ) {
						$this->_ctrl = [];
						foreach ( $objects as $object ) {
							$o = $this->extendObj( $object, $obj, $ctrl, $task );
							if ( $o ) {
								$this->_ctrl[] = $o;
							}
						}
						if ( !count( $this->_ctrl ) ) {
							Sobi::Error( 'CoreCtrl', SPLang::e( 'IDENTIFIER_EXPECTED' ), SPC::WARNING, 0, __LINE__, __FILE__ );
							Sobi::Redirect( Sobi::GetUserState( 'back_url', Sobi::Url() ), SPLang::e( 'IDENTIFIER_EXPECTED' ) . ' ' . SPLang::e( 'IDENTIFIER_EXPECTED_DESC' ), SPC::ERROR_MSG );
						}
					}
					else {
						Sobi::Error( 'CoreCtrl', SPLang::e( 'IDENTIFIER_EXPECTED' ), SPC::WARNING, 0, __LINE__, __FILE__ );
						Sobi::Redirect( Sobi::GetUserState( 'back_url', Sobi::Url() ), SPLang::e( 'IDENTIFIER_EXPECTED' ) . ' ' . SPLang::e( 'IDENTIFIER_EXPECTED_DESC' ), SPC::ERROR_MSG );
						$r = false;
						//break;
					}
				}
				else {
					Sobi::Error( 'CoreCtrl', SPLang::e( 'IDENTIFIER_EXPECTED' ), SPC::WARNING, 0, __LINE__, __FILE__ );
					Sobi::Redirect( Sobi::GetUserState( 'back_url', Sobi::Url() ), SPLang::e( 'IDENTIFIER_EXPECTED' ) . ' ' . SPLang::e( 'IDENTIFIER_EXPECTED_DESC' ), SPC::ERROR_MSG );
					$r = false;
					//break;
				}
			}
			else {
				/* set controller and model */
				try {
					$ctrl = new $ctrl();
					$this->setController( $ctrl );
					if ( $ctrl instanceof SPController ) {
						$model = SPLoader::loadModel( $obj, false, false );
						if ( $model ) {
							$this->_ctrl->setModel( $model );
						}
					}
				}
				catch ( SPException $x ) {
					Sobi::Error( 'CoreCtrl', SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), SPC::ERROR, 500, __LINE__, __FILE__ );
				}
				if ( $this->_sid ) {
					$this->_model = SPFactory::object( $this->_sid );
				}
				/* if the basic object we got from the #getSection method is the same one ... */
				if ( ( $this->_model instanceof stdClass )
					&& ( count( (array) $this->_model ) )
					&& ( $this->_model->oType == $obj )
				) {
					/*... extend the empty model of these data we've already got */
					/** @noinspection PhpParamsInspection */
					$this->_ctrl->extend( $this->_model );
				}
				/* ... and so on... */
				$this->_ctrl->setTask( $task );
			}
		}
		else {
			/** Special controllers not inherited from object and without model */
			$task = $this->_task;
			$ctrl = SPLoader::loadController( $task, true );
			try {
				$ctrl = new $ctrl();
				$this->setController( $ctrl );
			}
			catch ( Exception $x ) {
				Sobi::Error( 'CoreCtrl', SPLang::e( 'Cannot set controller. %s.', $x->getMessage() ), SPC::ERROR, 500, __LINE__, __FILE__ );
			}
		}

		return $r;
	}

	/**
	 * @return bool
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function routeObj()
	{
		if ( $this->_model instanceof stdClass && isset( $this->_model->id ) ) {
			if ( $this->_sid && $this->_model->id != $this->_sid ) {
				$this->_model = SPFactory::object( $this->_sid );
			}
		}
		try {
			if ( $this->_model ) {
				$ctrl = SPFactory::Controller( $this->_model->oType, true );
				if ( $ctrl instanceof SPController ) {
					$this->setController( $ctrl );
					$this->_ctrl->setModel( SPLoader::loadModel( $this->_model->oType ) );
					/** @noinspection PhpParamsInspection */
					$this->_ctrl->extend( $this->_model );
					$this->_ctrl->setTask( $this->_task );
				}
			}
		}
		catch ( SPException $x ) {
			Sobi::Error( 'CoreCtrl', SPLang::e( 'Cannot route object: %s.', $x->getMessage() ), SPC::ERROR, 500, __LINE__, __FILE__ );
		}

		return true;
	}

	/**
	 * @param stdClass $obj
	 * @param $objType
	 * @param $ctrlClass
	 * @param null $task
	 *
	 * @return SPControl
	 * @throws SPException|\Sobi\Error\Exception
	 */
	private function & extendObj( $obj, $objType, $ctrlClass, $task = null )
	{
		if ( $objType == $obj->oType ) {
			if ( $ctrlClass ) {
				/* create controller */
				$ctrl = new $ctrlClass();
				/* set model */
				/** @noinspection PhpUndefinedMethodInspection */
				$ctrl->setModel( SPLoader::loadModel( $objType ) );
				/* extend model of basic data */
				$ctrl->extend( $obj );
				/* set task */
				if ( strlen( $task ) ) {
					$ctrl->setTask( $task );
				}
			}
			else {
				Sobi::Error( 'CoreCtrl', SPLang::e( 'SUCH_TASK_NOT_FOUND', Input::Task() ), SPC::NOTICE, 404, __LINE__, __FILE__ );
			}
		}

		return $ctrl;
	}

	/**
	 * @throws SPException|\Sobi\Error\Exception
	 */
	private function frontpage()
	{
		SPLoader::loadController( 'front', true );
		$dashboard = new SPAdminPanel();
		$this->setController( $dashboard );
		Sobi::ReturnPoint();
		$this->_ctrl->setTask( Input::Task() );
	}

	/**
	 * Executes the controller task.
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function execute()
	{
		try {
			if ( is_array( $this->_ctrl ) ) {
				foreach ( $this->_ctrl as &$c ) {
					$c->execute();
				}
			}
			else {
				if ( $this->_ctrl instanceof SPControl ) {
					$this->_ctrl->execute();
				}
				else {
					Sobi::Error( 'CoreCtrl', SPLang::e( 'No controller to execute' ), SPC::ERROR, 500, __LINE__, __FILE__ );
				}
			}
		}
		catch ( SPException $x ) {
			Sobi::Error( 'CoreCtrl', SPLang::e( 'No controller to execute %s', $x->getMessage() ), SPC::ERROR, 500, __LINE__, __FILE__ );
//			Sobi::Error( 'CoreCtrl', SPLang::e( 'No controller to execute. %s', $x->getMessage() ), SPC::WARNING, 0, __LINE__, __FILE__ );
//			Sobi::Redirect( Sobi::GetUserState( 'back_url', Sobi::Url() ), $x->getMessage(), SPC::ERROR_MSG );
		}
		/* send header data etc ...*/
		SPFactory::mainframe()->endOut();
		/* redirect if any redirect has been set */
		SPFactory::mainframe()->redirect();
		error_reporting( (int) $this->_deb );
		restore_error_handler();
	}

	/**
	 * @param $ctrl
	 */
	public function setController( &$ctrl )
	{
		$this->_ctrl = &$ctrl;
	}
}