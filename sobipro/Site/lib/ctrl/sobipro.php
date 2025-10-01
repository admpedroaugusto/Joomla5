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
 * @modified 14 May 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

use Sobi\C;
use Sobi\Framework;
use Sobi\Input\Input;
use Sobi\Lib\Factory;
use Sobi\Utils\Serialiser;
use Sobi\Utils\StringUtils;

/**
 * Class SobiProCtrl
 */
final class SobiProCtrl
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
	/*** @var SPController - can be also array of */
	private $_ctrl = null;
	/*** @var mixed */
	private $_model = null;
	/** * @var int */
	private $_err = 0;
	/** * @var int */
	private $_deb = 0;
	/*** @var array */
	private $_cache = [];

	/**
	 * @param string $task
	 *
	 * @throws Exception
	 */
	public function __construct( $task )
	{
		$this->_mem = memory_get_usage();
		$this->_time = microtime( true );

		SPLoader::loadClass( 'base.exception' );
		set_error_handler( 'SPExceptionHandler' );
		$this->_err = ini_set( 'display_errors', 'on' );
		$this->_task = $task;

		/* load all needed classes */
		SPLoader::loadClass( 'base.const' );
		SPLoader::loadClass( 'base.factory' );
		SPLoader::loadClass( 'base.object' );
		SPLoader::loadClass( 'base.filter' );
		SPLoader::loadClass( 'base.request' );
		SPLoader::loadClass( 'sobi' );
		SPLoader::loadClass( 'base.config' );
		SPLoader::loadClass( 'cms.base.lang' );

		/* Initialise the framework messages */
		Framework::SetTranslator( [ 'SPlang', 'txt' ] );
		Framework::SetErrorTranslator( [ 'SPlang', 'error' ] );
		Framework::SetConfig( [ 'Sobi', 'Cfg' ] );

		/* get sid if any */
		$this->_sid = Input::Sid();

//		if ( $this->_sid ) {
		/* determine section */
		$haveSection = $this->getSection();
//		}
//		else {
//			$access = strstr( Input::Task(), 'api.' );
//		}

		/* initialise mainframe interface to CMS */
		$this->_mainframe = SPFactory::mainframe();

		/* initialise config */
		$this->createConfig();

		ini_set( 'display_errors', Sobi::Cfg( 'debug.display_errors', false ) );
		$this->_deb = error_reporting( (int) Sobi::Cfg( 'debug.level', 0 ) );

		/* trigger plugin */
		Sobi::Trigger( 'Start' );
		/* initialise translator and load language files */
		SPLang::setLang( Sobi::Lang( false ) );
		try {
			SPLang::registerDomain( 'site' );
		}
		catch ( SPException $x ) {
			Sobi::Error( 'CoreCtrl', SPLang::e( 'Cannot register language domain: %s.', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}

		/* if no section */
		if ( !$haveSection ) {
			$section = SPFactory::Section( Sobi::Section() );
			/* if we now have a section (failsafe) */
			if ( $section->get( 'id' ) ) {
				$redirect = $section->get( 'redirectSectionUrl' );
				if ( Sobi::Cfg( 'redirects.section_access_enabled', false ) && strlen( $redirect ) ) {
					$msg = Sobi::Cfg( 'redirects.section_access_msg', SPLang::e( 'UNAUTHORIZED_ACCESS', Input::Task() ) );
					$msgtype = Sobi::Cfg( 'redirects.section_access_msgtype', 'message' );

					if ( !preg_match( '/http[s]?:\/\/.*/', $redirect ) && $redirect != 'index.php' ) {
						$redirect = Sobi::Url( $redirect );
					}
					if ( $msgtype != 'none' ) {
						Sobi::Redirect( $redirect, Sobi::Txt( $msg ), $msgtype, true );
					}
					else {
						Sobi::Redirect( $redirect, C::ES, C::ES, true );
					}
				}
				else {
					SPFactory::mainframe()->runAway( 'You have no permission to access this site', 403, [], true );
				}
			}

			/* We cannot evaluate a section, seems to be a deleted page */
			else {
				SPFactory::mainframe()->runAway( 'Page not found!', 404, [], true );
			}
		}

		/* load css and js files for front-end */
		SPFactory::header()->initBase();

		$sectionName = SPLang::translateObject( $this->_section, 'name', 'section' );
		if ( $this->_section && count( $sectionName ) ) {
			$set = StringUtils::Clean( $sectionName[ $this->_section ][ 'value' ] );
			SPFactory::registry()->set( 'current_section_name', $set );
		}

		$start = [ $this->_mem, $this->_time ];
		SPFactory::registry()->set( 'start', $start );
		/* check if it wasn't plugin custom task */
		if ( !( Sobi::Trigger( 'custom', 'task', [ &$this, Input::Task() ] ) ) ) {
			/* if not, start to route */
			try {
				$this->route();
			}
			catch ( SPException $x ) {
				$msg = SPLang::e( 'PAGE_NOT_FOUND' ) . ' ' . SPLang::e( 'Cannot route: %s.', $x->getMessage() );
//				if ( SOBI_TESTS ) {
//					Sobi::Error( 'CoreCtrl', SPLang::e( 'Cannot route: %s.', $x->getMessage() ), C::ERROR, 500, __LINE__, __FILE__ );
//				}
//				else {
				Sobi::Error( 'CoreCtrl', $msg, C::ERROR, 404, __LINE__, __FILE__ );
//				}
			}
		}

		return true;
	}

	/**
	 * Initialises the config object.
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function createConfig()
	{
		$this->_config = SPFactory::config();
		/* load basic configuration settings */
		$this->_config->addIniFile( 'etc.config', true );
		$this->_config->addIniFile( 'etc.base', true );
		$this->_config->addTable( 'spdb_config', $this->_section );
		/* initialise interface config setting */
		$this->_mainframe->getBasicCfg();
		/* initialise config */
		$this->_config->init();
	}

	/**
	 * Gets the right section in $this->_model and $this->_section.
	 * Returns true if the section is set.
	 *
	 * @return bool
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function getSection(): bool
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
			}

			/* if we have a section id */
			if ( $this->_section ) {
				$section = SPFactory::object( $this->_section );

				$state = $section->state;
				if ( !isset( $state ) ) {
					$state = false;
					$section = SPFactory::object( $pid );
					if ( isset( $section->state ) ) {
						$state = $section->state;
					}
				}
				/* if this section isn't published */
				if ( !$state ) {
					if ( !Sobi::Can( 'section.access.any' ) ) {
						return false;
					}
				}
				$sid = Input::Sid();
				if ( $section->id == $sid ) {
					$this->_model = $section;
				}
				else {
					if ( $sid ) {
						$this->_model = SPFactory::object( $sid );
					}
				}
			}
		}
		else {
			$this->_section = 0;
		}

		/* set current section in the registry */
		if ( $this->_section ) {
			SPFactory::registry()->set( 'current_section', $this->_section );
		}
		else {
			return false;
		}

		return true;
	}

	/**
	 * Try to find out what we have to do
	 *     - If we have a task - parse task
	 *  - If we don't have a task, but sid, we are going via default object task
	 *  - Otherwise it could be only the frontpage.
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function route()
	{
		$cache = true;
		if ( Sobi::Cfg( 'cache.xml_enabled' ) ) {
			if ( ( $this->_model instanceof stdClass ) && !( ( $this->_model instanceof stdClass ) && $this->_model->owner == Sobi::My( 'id' ) ) ) {
				if ( in_array( $this->_model->owner, [ 'entry' ] ) ) {
					$cache = false;
				}
			}
		}
		if ( $cache && Sobi::Cfg( 'cache.xml_enabled' ) ) {
			$this->_cache = SPFactory::cache()->view();
		}
		if ( !$this->_cache ) {
			/* if we have a task */
			if ( $this->_task && $this->_task != 'panel' ) {
				if ( !( $this->routeTask() ) ) {
					throw new SPException( SPLang::e( 'Cannot interpret task "%s"', $this->_task ) );
				}
			}
			/* if there is no task - execute default task for object */
			elseif ( $this->_sid ) {
				if ( !$this->routeObj() ) {
					throw new SPException( SPLang::e( 'Cannot route object with id "%d"', $this->_sid ) );
				}
			}
			/* otherwise show the frontpage */
			else {
				$this->frontpage();
			}
		}
		else {
			try {
				$task = $this->_task;
				if ( !$task && $this->_sid ) {
					$ctrl = SPFactory::Controller( $this->_model->oType );
					$this->setController( $ctrl );
					$this->_model = SPFactory::object( $this->_sid );
					$model = SPLoader::loadModel( $this->_model->oType, false, false );
					if ( $model ) {
						$this->_ctrl->setModel( $model );
						if ( ( $this->_model instanceof stdClass ) ) {
							$this->_ctrl->extend( $this->_model, true );
						}
					}
				}
				if ( strstr( $task, '.' ) ) {
					$task = explode( '.', $task );
					$obj = trim( array_shift( $task ) );
					if ( $obj == 'list' || $obj == 'ls' ) {
						$obj = 'listing';
					}
					$task = trim( implode( '.', $task ) );
					$ctrl = SPFactory::Controller( $obj );
					$this->setController( $ctrl );
					$model = SPLoader::loadModel( $obj, false, false );
					if ( $model ) {
						$this->_ctrl->setModel( $model );
						if ( ( $this->_model instanceof stdClass ) ) {
							$this->_ctrl->extend( $this->_model, true );
						}
					}
					else {
						$this->_ctrl->setModel( SPFactory::Section( $this->_section ) );
						if ( ( $this->_model instanceof stdClass ) ) {
							$this->_ctrl->extend( $this->_model, true );
						}
					}
				}
				elseif ( $task ) {
					/** Special controllers not inherited from object and without model */
					$ctrl = SPFactory::Controller( $task );
					$this->setController( $ctrl );
					$this->_ctrl->setModel( SPFactory::Section( $this->_section ) );
					if ( $this->_model instanceof stdClass ) {
						$this->_ctrl->extend( $this->_model, true );
					}
				}
				$this->_ctrl->setTask( $task );
				$this->_ctrl->visible();
			}
			catch ( SPException $x ) {
				Sobi::Error( 'CachedView', $x->getMessage() );
				$this->_cache = null;
				$this->route();
			}
		}
	}

	/**
	 * Routes by task.
	 *
	 * @return bool
	 * @throws \Sobi\Error\Exception|\SPException
	 */
	private function routeTask()
	{
		$retval = true;
		if ( strstr( $this->_task, '.' ) ) {
			/* task consist of the real task and the object type */
			$task = explode( '.', $this->_task );
			$obj = trim( array_shift( $task ) );
			$task = trim( implode( '.', $task ) );

			if ( $obj == 'list' || $obj == 'ls' ) {
				$obj = 'listing';
			}
			/* load the controller class definition and get the class name */
			$ctrl = SPLoader::loadController( $obj );

			/* route task for multiple objects - e.g removing or publishing elements from a list */
			/* and there was some multiple sids */
			if ( count( Input::Arr( 'sid' ) ) || count( Input::Arr( 'c_sid' ) ) || count( Input::Arr( 'e_sid' ) ) ) {
				$sid = array_key_exists( 'sid', $_REQUEST ) ? 'sid' : ( array_key_exists( 'c_sid', $_REQUEST ) ? 'c_sid' : 'e_sid' );
				if ( count( Input::Arr( $sid ) ) ) {
					$db = Factory::Db();
					try {
						$objects = $db
							->select( '*', 'spdb_object', [ 'id' => Input::Arr( $sid ) ] )
							->loadObjectList();
					}
					catch ( Exception $x ) {
						Sobi::Error( 'CoreCtrl', SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), SPC::ERROR, 500, __LINE__, __FILE__ );
						$retval = false;
					}
					if ( count( $objects ) ) {
						$this->_ctrl = [];
						foreach ( $objects as $object ) {
							$this->_ctrl[] = $this->extendObj( $object, $obj, $ctrl, $task );
						}
					}
				}
				else {
					Sobi::Error( 'CoreCtrl', SPLang::e( 'IDENTIFIER_EXPECTED' ), SPC::ERROR, 500, __LINE__, __FILE__ );
					$retval = false;
				}
			}
			else {
				/* set controller and model */
				try {
					$ctrl = new $ctrl( null );
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
				if ( ( $this->_model instanceof stdClass ) && ( $this->_model->oType == $obj ) ) {
					/*... extend the empty model of these data we've already got */
					$this->_ctrl->extend( $this->_model );
				}
				/* ... and so on... */
				$this->_ctrl->setTask( $task );
			}
		}
		else {
			/** Special controllers not inherited from object and without model */
			$task = $this->_task;
			try {
				$ctrl = SPLoader::loadController( $task );
			}
			catch ( SPException $x ) {
				Sobi::Error( 'CoreCtrl', SPLang::e( 'PAGE_NOT_FOUND' ), SPC::NOTICE, 404 );
			}
			try {
				$ctrl = new $ctrl( null );
				$this->setController( $ctrl );
				if ( method_exists( $this->_ctrl, 'setTask' ) ) {
					$this->_ctrl->setTask( C::ES );
				}
			}
			catch ( SPException $x ) {
				Sobi::Error( 'CoreCtrl', SPLang::e( 'Cannot set controller. %s.', $x->getMessage() ), SPC::ERROR, 500, __LINE__, __FILE__ );
			}
		}

		return $retval;
	}

	/**
	 * @return bool
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function routeObj()
	{
		try {
			$ctrl = SPFactory::Controller( $this->_model->oType );
			if ( $ctrl instanceof SPController ) {
				$this->setController( $ctrl );
				if ( $this->_model->id == Input::Sid() ) {
					// just to use the pre-fetched entry
					if ( $this->_model->oType == 'entry' ) {
						$entry = SPFactory::Entry( $this->_model->id );
						// object has been stored anyway in the SPFactory::object method
						// and also already initialized
						// therefore it will be now rewritten and because the init flag is set to true
						// the name is getting lost
						// $entry->extend( $this->_model );
						$this->_ctrl->setModel( $entry );
					}
					else {
						$this->_ctrl->setModel( SPLoader::loadModel( $this->_model->oType ) );
						$this->_ctrl->extend( $this->_model );
					}
				}
				else {
					$this->_ctrl->extend( SPFactory::object( Input::Sid() ) );
				}
				$this->_ctrl->setTask( $this->_task );
			}
		}
		catch ( SPException $x ) {
			if ( defined( 'SOBI_TESTS' ) ) {
				Sobi::Error( 'CoreCtrl', SPLang::e( 'Cannot set controller. %s.', $x->getMessage() ), SPC::ERROR, 500, __LINE__, __FILE__ );
			}
			else {
				SPFactory::mainframe()->setRedirect( Sobi::Reg( 'live_site' ), SPLang::e( 'PAGE_NOT_FOUND' ), SPC::ERROR_MSG, true );
			}
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
				$ctrl->setModel( SPLoader::loadModel( $objType ) );
				/* extend model of basic data */
				$ctrl->extend( $obj );
				/* set task */
				if ( strlen( $task ) ) {
					$ctrl->setTask( $task );
				}
			}
			else {
				Sobi::Error( 'CoreCtrl', SPLang::e( 'SUCH_TASK_NOT_FOUND', Input::Task() ), C::NOTICE, 404, __LINE__, __FILE__ );
			}
		}

		return $ctrl;
	}

	/**
	 * @throws SPException|\Sobi\Error\Exception
	 */
	private function frontpage()
	{
		$ctrl = SPLoader::loadController( 'front' );
		$ctrl1 = new $ctrl();
		$this->setController( $ctrl1 );
		$this->_ctrl->setTask( Input::Task() );
	}

	/**
	 * Executes the controller task.
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception|\DOMException
	 */
	public function execute()
	{
		if ( !$this->_cache ) {
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
				Sobi::Error( 'CoreCtrl', SPLang::e( '%s', $x->getMessage() ), SPC::ERROR, 500, __LINE__, __FILE__ );
			}
		}
		else {
			/** @var SPFrontView $view */
			$view = SPFactory::View( 'cache' );
			$view->cachedView( $this->_cache[ 'xml' ], $this->_cache[ 'template' ], $this->_cache[ 'cid' ], $this->_cache[ 'config' ] );
			$view->display();
		}
		/* send header data etc ...*/
		if ( Input::Cmd( 'format' ) == 'raw' && Input::Bool( 'xmlc' ) ) {
			SPFactory::cache()->storeXMLView( [] );
		}
		SPFactory::mainframe()->endOut();
		Sobi::Trigger( 'End' );
		/* redirect if any redirect has been set */
		SPFactory::mainframe()->redirect();
		ini_set( 'display_errors', $this->_err );
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
