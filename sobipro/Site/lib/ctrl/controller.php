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
 * @created 13-Jan-2009 by Radek Suski
 * @modified 14 November 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

use Sobi\C;
use Sobi\Input\Input;
use Sobi\Lib\Factory;
use Sobi\FileSystem\FileSystem;
use Sobi\Utils\StringUtils;

/**
 * Class SPController
 */
abstract class SPController extends SPObject implements SPControl
{
	/*** @var string */
	protected $_task = C::ES;
	/*** @var string */
	protected $_defTask = C::ES;
	/*** @var array */
	protected $_tCfg = [];
	/*** @var SPDataModel */
	protected $_model = null;
	/*** @var string */
	protected $_type = C::ES;
	/*** @var string */
	protected $templateType = C::ES;
	/*** @var string */
	protected $template = C::ES;

	/**
	 * @param $model
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function setModel( $model )
	{
		if ( is_string( $model ) ) {
			if ( !class_exists( $model ) && !( $model = SPLoader::loadModel( $model ) ) ) {
				throw new SPException( SPLang::e( 'Cannot instantiate model for "%s" controller. Missing class definition', $this->name() ) );
			}
			$this->_model = new $model();
		}
		else {
			$this->_model = $model;
		}

		Sobi::Trigger( $this->name(), __FUNCTION__, [ &$model ] );
	}

	/**
	 * @param $obj
	 * @param bool $cache
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function extend( $obj, $cache = false )
	{
		Sobi::Trigger( $this->name(), __FUNCTION__, [ &$obj ] );
		$this->_model->extend( $obj, $cache );
		if ( $cache ) {
			$this->_model->countVisit();
		}
	}

	/**
	 * SPController constructor.
	 */
	public function __construct()
	{
		Sobi::Trigger( 'CreateController', $this->name(), [ &$this ] );
	}

	/**
	 * Authorizes an action.
	 *
	 * @param string $action
	 * @param string $ownership
	 *
	 * @return bool
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function authorise( $action = 'access', $ownership = 'valid' )
	{
		if ( !Sobi::Can( $this->_type, $action, $ownership, Sobi::Section() ) ) {
			Sobi::Error( $this->name(), SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ), C::ERROR, 403, __LINE__, __FILE__ );
			exit;
		}

		return true;
	}

	/**
	 * @param string $redirect
	 * @param string $msg
	 * @param string $msgType
	 *
	 * @throws \SPException
	 */
	protected function escape( string $redirect, string $msg, string $msgType )
	{
		/* check if the redirect string is already a URL (starts with http or with index.php)
		If it is not already a URL we first need to create it. */
		if ( !preg_match( '/http[s]?:\/\/.*/', $redirect ) && !str_starts_with( $redirect, 'index.php' ) ) {
			$redirect = Sobi::Url( $redirect );
		}
		if ( $msgType != 'none' ) {
			Sobi::Redirect( $redirect, Sobi::Txt( $msg ), $msgType, true );
		}
		else {
			Sobi::Redirect( $redirect, C::ES, C::ES, true );
		}
		exit;
	}

	/**
	 * @return bool|void
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 * @throws \Exception
	 */
	public function execute()
	{
		$retval = true;
		Input::Set( 'task', $this->_type . '.' . $this->_task );
		switch ( $this->_task ) {
			/* if someone wants to edit an object - just check if it is not checked out */
			case 'edit':
				if ( $this->_model && $this->_model->isCheckedOut() ) {
					Sobi::Redirect( Sobi::GetUserState( 'back_url', Sobi::Url() ), Sobi::Txt( 'MSG.OBJ_CHECKED_OUT', [ 'type' => Sobi::Txt( strtoupper( $this->_type ) ) ] ), C::ERROR_MSG, true );
					exit();
				}
				break;

			case 'hide':
			case 'publish':
				$this->state( $this->_task == 'publish' );
				break;

			case 'toggle.enabled':
			case 'toggle.approval':
				$this->toggleState();
				break;

			case 'apply':
			case 'save':
			case 'saveAndNew':
				$this->save( $this->_task == 'apply' );
				break;

			case 'cancel':
				if ( defined( 'SOBI_ADM_PATH' ) ) {
					$this->checkIn( Input::Sid(), false );
					$this->response( Sobi::Back() );
				}
				$this->checkIn( Input::Int( 'sid' ) );
				if ( Input::Sid() ) {
					$url = Sobi::Url( [ 'sid' => Input::Sid() ] );
				}
				else {
					$url = Input::Int( 'pid' ) ? Sobi::Url( [ 'sid' => Input::Int( 'pid' ) ] ) : Sobi::Url( [ 'sid' => Sobi::Section() ] );
				}
				$this->response( $url );
				break;

			case 'delete':
				if ( ( $this->_model->get( 'owner' ) == Sobi::My( 'id' ) && $this->authorise( 'delete', 'own' ) ) || $this->authorise( 'delete', '*' ) ) {
					if ( $this->_model->get( 'id' ) ) {
						/* the model should delete the object (entry/category) */
						$this->_model->delete();
						if ( $this->_type == 'entry' && !defined( 'SOBIPRO_ADM' ) ) {
							if ( Input::Int( 'pid' ) ) {
								$url = Sobi::Url( [ 'sid' => Input::Int( 'pid' ) ] );
							}
							else {
								$url = Sobi::Url( [ 'sid' => Sobi::Section() ] );
							}
						}
						else {
							$url = Sobi::Back();
						}
						switch ( $this->_type ) {
							case "category":
								$msg = Sobi::Txt( 'MSG.OBJ_DELETED_CATEGORY' );
								break;
							case "entry":
								$msg = defined( 'SOBIPRO_ADM' ) ? Sobi::Txt( 'MSG.OBJ_DELETED_ENTRY' ) : Sobi::Txt( 'MSG.ENTRY_DELETED', $this->_model->get( 'name' ) );
								break;
							default:
								$msg = Sobi::Txt( 'MSG.OBJ_DELETED', [ 'type' => Sobi::Txt( strtoupper( $this->_type ) ) ] );
								break;
						}
						$this->response( $url, $msg, false, C::SUCCESS_MSG );
					}
					else {
						$this->response( Sobi::Back(), Sobi::Txt( 'CHANGE_NO_ID' ), false, C::ERROR_MSG );
					}
				}
				break;

			case 'view':
				$this->visible();
				$this->view();
				break;

			case 'resetCounter':
				if ( $this->authorise( 'edit', '*' ) ) {
					$this->_model->countVisit( true );
					exit( true );
				}
				break;

			default:
				$retval = Sobi::Trigger( 'Execute', $this->name(), [ &$this ] );
				break;
		}

		return $retval;
	}

	/**
	 * @return void
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function checkTranslation()
	{
		$lang = Input::Cmd( 'sp-language', 'get' );
		if ( $lang && $lang != 'null' && $lang != Sobi::Cfg( 'language' ) ) {
			$languages = SPLang::availableLanguages();
			if ( array_key_exists( $lang, $languages ) ) {
				$name = $languages[ $lang ][ 'nativeName' ] ? : $languages[ $lang ][ 'name' ];
				SPFactory::message()->info( Sobi::Txt( 'INFO_DIFFERENT_LANGUAGE', $this->_type, '"' . $name . '"' ), false );
			}
		}
	}

	/**
	 * @param $state
	 *
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function state( $state )
	{
		if ( $this->_model->get( 'id' ) ) {
			if ( $this->authorise( 'manage' ) ) {
				$this->_model->changeState( $state );
				$state = ( int ) ( $this->_task == 'publish' ) ? true : $state;
				SPFactory::history()->logAction( $state ? SPC::LOG_PUBLISH : SPC::LOG_UNPUBLISH,
					$this->_model->get( 'id' ),
					$this->_model->get( 'oType' ) == 'section' ? $this->_model->get( 'id' ) : $this->_model->get( 'section' ),
					$this->_model->get( 'oType' ),
					C::ES,
					[ 'name' => $this->_model->get( 'name' ) ]
				);
				$this->response( Sobi::Back(), Sobi::Txt( $state ? 'OBJ_PUBLISHED' : 'OBJ_UNPUBLISHED', [ 'type' => Sobi::Txt( strtoupper( $this->_type ) ) ] ) . ' ', false );
			}
		}
		else {
			$this->response( Sobi::Back(), Sobi::Txt( 'CHANGE_NO_ID' ), true, C::ERROR_MSG );
		}
	}

	/**
	 * @return SPDataModel
	 */
	public function getModel()
	{
		return $this->_model;
	}

	/**
	 * @param string $task
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function setTask( $task )
	{
		$this->_task = strlen( $task ) ? $task : $this->_defTask;
		$helpTask = $this->_type . '.' . $this->_task;

		Sobi::Trigger( $this->name(), __FUNCTION__, [ &$this->_task ] );
		SPFactory::registry()->set( 'task', $helpTask );
	}

	/**
	 * Returns current object type.
	 *
	 * @return string
	 */
	public function type()
	{
		return $this->_type;
	}

	/**
	 * Saves an object.
	 *
	 * @param $apply
	 * @param false $clone
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function save( $apply = false, $clone = false )
	{
		if ( !Factory::Application()->checkToken() ) {
			Sobi::Error( 'Token', SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ), C::ERROR, 403, __LINE__, __FILE__ );
		}

		try {
			$sets = [];
			$this->validate( $this->_type . '.edit', $this->_type );
			$apply = ( int ) $apply;
			if ( !$this->_model ) {
				$this->setModel( SPLoader::loadModel( $this->_type ) );
			}
			$sid = Input::Sid() ? Input::Sid() : Input::Int( $this->_type . '_id' );
			if ( $sid ) {
				$this->_model->init( $sid );
			}
			/** store previous state for possible triggers */
			$preState = [
				'approved' => $this->_model->get( 'approved' ),
				'state'    => $this->_model->get( 'state' ),
				'new'      => !$this->_model->get( 'id' ),
			];
			SPFactory::registry()->set( 'object_previous_state', $preState );
			$this->_model->getRequest( $this->_type );

			if ( $this->_model->get( 'id' ) ) {
				if ( Sobi::My( 'id' ) && Sobi::My( 'id' ) == $this->_model->get( 'owner' ) ) {
					$this->authorise( 'edit', 'own' );
				}
				else {
					$this->authorise( 'edit', '*' );
				}
			}
			else {
				$this->authorise( 'add', 'own' );
			}
			$this->_model->save();
			$sid = $this->_model->get( 'id' );
			$sets[ 'sid' ] = $sid;
			$sets[ $this->_type . '.nid' ] = $this->_model->get( 'nid' );
			$sets[ $this->_type . '.id' ] = $sid;
			if ( $apply || $clone ) {
				if ( $clone ) {
					switch ( $this->_type ) {
						case "category":
							$msg = Sobi::Txt( 'MSG.OBJ_CLONED_CATEGORY' );
							break;
						default:
							$msg = Sobi::Txt( 'MSG.OBJ_CLONED', [ 'type' => Sobi::Txt( strtoupper( $this->_type ) ) ] );
							break;
					}
					$this->response( Sobi::Url( [ 'task' => $this->_type . '.edit', 'sid' => $sid ] ), $msg, false, C::SUCCESS_MSG, [ 'sets' => $sets ] );
				}
				else {
					$msg = Sobi::Txt( 'MSG.ALL_CHANGES_SAVED' );
					$this->response( Sobi::Url(
						[ 'task' => $this->_type . '.edit',
						  'sid'  => $sid ] ), $msg, $this->_type == 'section', C::SUCCESS_MSG, [ 'sets' => $sets ] );
				}
			}
			else {
				if ( $this->_task == 'saveAndNew' ) {
					$msg = Sobi::Txt( 'MSG.ALL_CHANGES_SAVED' );
					$sid = $this->_model->get( 'parent' );
					$sid = $sid ? : Sobi::Section();

					$this->response( Sobi::Url( [ 'task' => $this->_type . '.add', 'sid' => $sid ] ), $msg, true, C::SUCCESS_MSG, [ 'sets' => $sets ] );

				}
				else {
					$this->response( Sobi::Back(), Sobi::Txt( 'MSG.OBJ_SAVED', [ 'type' => Sobi::Txt( strtoupper( $this->_type ) ) ] ), true, C::SUCCESS_MSG );
				}
			}
		}
		catch ( SPException $x ) {
			$this->response( Sobi::Back(), $x->getMessage(), false, C::ERROR_MSG );
		}
	}

	/**
	 * Toggles the state or approves it (with enabling if configured).
	 * Called for entries (state and approval) and sections (state only).
	 *
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function toggleState()
	{
		if ( $this->authorise( 'manage' ) ) {
			if ( $this->_task == 'toggle.enabled' ) {
				$this->state( !$this->_model->get( 'state' ) );
			}
			else {
				/* if approval should also publish the entry */
				if ( Sobi::Cfg( 'entry.approval_publish', true ) ) {
					if ( !$this->_model->get( 'approved' ) && !$this->_model->get( 'state' ) ) {
						$this->state( true );
					}
				}
				$this->approval( !$this->_model->get( 'approved' ) );
			}
		}
	}

	/**
	 * @return bool
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function visible()
	{
		$type = $this->_model->get( 'oType' );
		if ( Sobi::Can( $type, '*', '*' ) ) {
			return true;
		}
		$error = false;
		$owner = $this->_model->get( 'owner' );
		$state = $this->_model->get( 'state' );
		Sobi::Trigger( $type, 'CheckVisibility', [ &$state, &$owner ] );

		/* if it's unpublished */
		if ( !$state ) {
			if ( $owner && ( $owner == Sobi::My( 'id' ) ) ) {   //if the owner of an entry
				if ( !( Sobi::Can( $type, 'access', 'unpublished_own' ) || Sobi::Can( $type, 'access', 'unpublished_any' ) ) ) {
					$error = true;
				}
			}
			else { //not the owner of an entry
				if ( !Sobi::Can( $type, 'access', 'unpublished_any' ) ) {
					$error = true;
				}
			}
		}
		else {
			if ( !Sobi::Can( $type, 'access', 'valid' ) ) {
				$error = true;
			}
		}
		/* if not approved */
		/* and unapproved entry can be accessed because then the previously created version should be displayed */
		if ( $type == 'category' ) {
			$approved = $this->_model->get( 'approved' );
			if ( !$approved ) {
				if ( !Sobi::Can( $type, 'access', 'unapproved_any' ) ) {
					$error = true;
				}
			}
		}
		/* if it's expired or not valid yet  */
		$va = $this->_model->get( 'validUntil' );
		$va = $va ? strtotime( $va . ' UTC' ) : 0;
		if ( !$error ) {
			/* pending entries */
			if ( strtotime( $this->_model->get( 'validSince' ) . ' UTC' ) > gmdate( 'U' ) ) {
				if ( $owner && ( $owner == Sobi::My( 'id' ) ) ) {
					if ( !Sobi::Can( $type, 'access', 'unpublished_own' ) ) {
						$error = true;
					}
				}
				else {
					if ( !Sobi::Can( $type, 'access', 'unpublished_any' ) ) {
						$error = true;
					}
				}
			}
			/* expired entries */
			else {
				if ( $va > 0 && $va < gmdate( 'U' ) ) {
					if ( $owner && ( $owner == Sobi::My( 'id' ) ) ) {
						if ( !Sobi::Can( $type, 'access', 'expired_own' ) && !Sobi::Can( $type, 'access', 'expired_any' ) ) {
							$error = true;
						}
					}
					else {
						if ( !Sobi::Can( $type, 'access', 'expired_any' ) ) {
							$error = true;
						}
					}
				}
			}
		}

		if ( $error ) {
			$section = SPFactory::Section( Sobi::Section() );
			$redirect = $section->get( 'redirect' . ucfirst( $type ) . 'Url' );
			if ( Sobi::Cfg( 'redirects.' . $type . '_access_enabled', false ) && strlen( $redirect ) ) {
				$this->escape( $redirect, Sobi::Cfg( 'redirects.' . $type . '_access_msg', SPLang::e( 'UNAUTHORIZED_ACCESS', Input::Task() ) ), Sobi::Cfg( 'redirects.' . $type . '_access_msgtype', 'message' )
				);
				exit;
			}
			else {
				Sobi::Error( $this->name(), SPLang::e( 'UNAUTHORIZED_ACCESS', Input::Task() ), C::ERROR, 403, __LINE__, __FILE__ );
			}
		}
	}

	/**
	 * @return void
	 */
	protected function template()
	{
		/* determine template file */
		$template = Input::Cmd( 'sptpl', 'request', $this->_task );
		if ( strstr( $template, '.' ) ) {
			$template = explode( '.', $template );
			$this->templateType = $template[ 0 ];
			$this->template = $template[ 1 ];
		}
		else {
			$this->templateType = $this->_type;
			$this->template = $template ? : $this->_task;
		}
		if ( strlen( $template && $template != $this->_task ) && !Input::Bool( 'xmlc' ) ) {
			$template = "/$this->templateType/$this->template.xsl";
			SPFactory::registry()->set( 'cache_view_template', $template );
		}
		SPFactory::registry()->set( 'template_type', $this->templateType );
	}

	/**
	 * Read a template JSON file and add the settings to the global $this->_tCfg
	 *
	 * @param string $path
	 * @param string $file
	 * @param array $sections
	 *
	 * @return void
	 */
	protected function ReadJSONFile( string $path, string $file, array $sections = [] )
	{
		$settings = $fileContent = [];

		if ( FileSystem::Exists( "$path/$file.json" ) ) {
			$fileContent = json_decode( FileSystem::Read( "$path/$file.json" ), true );
			if ( is_array( $fileContent ) && count( $fileContent ) ) {
				foreach ( $fileContent as $section => $keys ) {
					foreach ( $keys as $key => $value ) {
						$settings[ $section ][ $key ] = $value;
					}
				}
			}
		}

		$fileSections = [];
		if ( count( $fileContent ) ) {
			if ( count( $sections ) ) {
				foreach ( $sections as $section ) {
					$section = str_replace( '.', '-', $section );
					foreach ( $fileContent as $index => $content ) {
						if ( $index == $section ) {
							$fileSections[] = $section;
						}
					}
				}
			}
			else {
				foreach ( $fileContent as $index => $content ) {
					$fileSections[] = $index;
				}
			}
		}

		foreach ( $fileSections as $section ) {
			if ( isset( $settings[ $section ] ) ) {
				foreach ( $settings[ $section ] as $key => $value ) {
					if ( is_array( $value ) ) { /* to be able to use multiselect lists in template settings */
						foreach ( $value as $kk => $vv ) {
							$this->_tCfg[ 'general' ][ $key . '-' . $kk ] = $vv;
						}
					}
					else {
						$this->_tCfg[ 'general' ][ $key ] = $value;
					}
				}
			}
		}
	}

	/**
	 * Loads the templates ini and json files (fills $this->_tCfg).
	 *
	 * @param string $path
	 * @param string $task
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function tplCfg( string $path, string $task = C::ES )
	{
		$files = [];
		$path = SOBI_PATH . "/usr/templates/$path";
		$this->_tCfg = FileSystem::LoadIniFile( "$path/config" );
		$files[] = "$path/config";

		if ( !$task ) {
			$task = ( $this->_task == 'add' || $this->_task == 'submit' || $this->_task == 'edit' ) ? 'edit' : $this->_defTask;
		}

		$file = Input::Cmd( 'sptpl' ) ? Input::String( 'sptpl' ) : $task;

		/* Read the necessary ini files */
		if ( isset( $this->templateType ) && $this->templateType && strstr( $file, $this->templateType ) ) {
			$file = str_replace( $this->templateType . '.', C::ES, $file );
		}

		if ( $file != $task ) {
			if ( !SPLoader::translatePath( "$path.$this->templateType.$file", 'absolute', true, 'ini' ) ) {
				$file = $task;
			}
		}
		/* replace dots into slashes (tasks may contain dots!) */
		$file = str_replace( '.', '/', $file );

		if ( FileSystem::Exists( FileSystem::FixPath( "$path/$this->templateType/$file.ini" ) ) ) {
			$taskCfg = FileSystem::LoadIniFile( "$path/$this->templateType/$file" );
			$files[] = "$path/$this->templateType/$file";
			foreach ( $taskCfg as $section => $keys ) {
				if ( isset( $this->_tCfg[ $section ] ) ) {
					$this->_tCfg[ $section ] = array_merge( $this->_tCfg[ $section ], $keys );
				}
				else {
					$this->_tCfg[ $section ] = $keys;
				}
			}
		}
		if ( count( $files ) ) {
			foreach ( $files as $i => $oneFile ) {
				$files[ $i ] = [ 'file' => str_replace( $path . '/', C::ES, $oneFile ), 'checksum' => md5_file( "$oneFile.ini" ) ];
			}
			SPFactory::registry()->set( 'template_config', $files );
		}

		/* Read the necessary json files (revised in SobiPro 2.4.1) */
		if ( FileSystem::Exists( "$path/config.json" ) ) {
			/* If edit.ini has been read (for 'entry.edit', 'entry.add','entry.submit'), set the settings section to 'entry.edit' */
			$section = ( $file == 'edit' ) ? 'entry.edit' : Input::Task();

			$this->ReadJSONFile( $path, 'config', [ 'general', $section ] );

			/* if the entry is saved, additionally read the edit.json file */
			if ( Input::Task() == 'entry.save' ) {
				$this->ReadJSONFile( "$path/$this->templateType", 'edit', [ 'entry.edit' ] );
			}
			$this->ReadJSONFile( "$path/$this->templateType", $file );
		}

		Sobi::Trigger( $this->name(), __FUNCTION__, [ &$this->_tCfg ] );
		SPFactory::registry()->set( 'current_template', $path );
	}

	/**
	 * @param $section
	 * @param $key
	 * @param null $default
	 *
	 * @return null|array|string|int
	 */
	protected function tKey( $section, $key, $default = null )
	{
		return $this->_tCfg[ $section ][ $key ] ?? ( $this->_tCfg[ 'general' ][ $key ] ?? $default );
	}

	/**
	 * Adds the ordering (entries or categories) on frontend to the Joomla Registry.
	 *
	 * @param $subject
	 * @param $request
	 * @param $default
	 *
	 * @return mixed
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 * @throws \Exception
	 */
	protected function parseOrdering( $subject, $request, $default )
	{
//		$session = JFactory::getSession();
//		$registry = $session->get( 'registry' );

		/* Do not ask. It seems I haven't finished the idea of order-front.
		   The whole sorting thing should be revised. */
		$key = $subject == 'search' ? 'order-front' : 'ordering';
		$state = Sobi::GetUserState( "$subject.$key." . StringUtils::Nid( Sobi::Section( true ) ), $request, $default );

		return $state;
	}

	/**
	 * @param $subject
	 * @param $value
	 *
	 * @return mixed
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 * @throws \Exception
	 */
	protected function setOrdering( $subject, $value )
	{
		return Sobi::SetUserState( "$subject.order-front." . StringUtils::Nid( Sobi::Section( true ) ), $value );
	}

	/**
	 * @param $sid
	 * @param bool $redirect
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function checkIn( $sid, $redirect = true )
	{
		if ( $sid ) {
			$this->setModel( SPLoader::loadModel( $this->_type ) );
			$this->_model->load( $sid );
			$this->_model->checkIn();
		}
		if ( $redirect ) {
			Sobi::Redirect( Sobi::GetUserState( 'back_url', Sobi::Url() ) );
		}
	}

	/**
	 * @param string $url
	 * @param string|array $message
	 * @param bool $redirect
	 * @param string $type
	 * @param array $data
	 * @param string $request
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function response( string $url, $message = C::ES, bool $redirect = true, string $type = C::INFO_MSG, array $data = [], string $request = 'post' )
	{
		if ( is_array( $message ) && isset( $message[ 'text' ] ) && isset( $message[ 'type' ] ) ) {
			$type = $message[ 'type' ];
			$message = $message[ 'text' ];
		}

		if ( Input::Cmd( 'method' ) == 'xhr' ) {
			if ( $redirect && $message ) {
				SPFactory::message()->setMessage( $message, false, $type );
			}
			$url = str_replace( '&amp;', '&', $url );
			SPFactory::mainframe()
				->cleanBuffer()
				->customHeader();
			echo json_encode(
				[
					'message'  => [ 'text' => $message, 'type' => $type ],
					'redirect' => [ 'url' => $url, 'execute' => ( bool ) $redirect ],
					'data'     => $data,
				]
			);
			exit;
		}
		else {
			if ( $message ) {
				if ( strstr( $url, 'com_sobipro' ) ) {
					SPFactory::message()->setMessage( $message, false, $type );
					$message = C::ES;
				}
			}
			else {
				$message = C::ES;
			}
			$url = strstr( 'index.php', $url ) && trim( $url ) != 'index.php' ? Sobi::Url( $url ) : $url;
			Sobi::Redirect( $url, $message, C::ES, $redirect );
		}
	}

	/**
	 * @param $xml - path to xml file inside the administrator directory (e.g. field.definitions.filter)
	 * @param $type - object type or array with error url
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function validate( $xml, $type )
	{
		$definition = SPLoader::path( $xml, 'adm', true, 'xml' );
		if ( $definition ) {
			if ( is_array( $type ) ) {
				$errorUrl = Sobi::Url( $type );
			}
			else {
				$errorUrl = Sobi::Url( [ 'task' => $type . '.edit', 'sid' => Input::Sid() ] );
			}
			$xdef = new DOMXPath( SPFactory::LoadXML( $definition ) );
			$required = $xdef->query( '//field[@required="true"]' );
			if ( $required->length ) {
				for ( $index = 0; $index < $required->length; $index++ ) {
					$node = $required->item( $index );
					$name = $node->attributes->getNamedItem( 'name' )->nodeValue;

					if ( !Input::Raw( str_replace( '.', '_', $name ) ) ) {
						$this->response( $errorUrl, Sobi::Txt( 'PLEASE_FILL_IN_ALL_REQUIRED_FIELDS' ), false, C::ERROR_MSG, [ 'required' => $name ] );
					}
				}
			}
		}
	}
}