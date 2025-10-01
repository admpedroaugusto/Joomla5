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
 * @modified 19 February 2024 by Sigrid Suski
 */
defined( 'SOBIPRO' ) || exit( 'Restricted access' );

use Joomla\CMS\User\UserFactoryInterface;
use Sobi\C;
use Sobi\Input\Input;
use Sobi\Lib\Factory;

use Joomla\CMS\Access\Access as JAccess;
use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\User\User as JUser;

/**
 * Class SPJoomlaUser
 */
class SPJoomlaUser extends JUser
{
	/** @var array */
	protected $_permissions = [];
	/** @var array */
	protected $_availablePerm = [];
	/** @var array */
	protected $_prules = [];
	/** @var array */
	protected $_prequest = [];
	/** @var array */
	protected $_special = [ 'txt.js', 'progress', 'api.sections', 'api.category', 'api.entries', 'api.entry', 'api.fields', 'api.changes' ];

	/** @var array */
	protected $gid = [];


	/**
	 * @param int $id
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function __construct( $id = 0 )
	{
		$this->groups = JAccess::getGroupsByUser( $id );
		parent::__construct( $id );

		$this->gid[] = 0;
		foreach ( $this->groups as $value ) {
			$this->gid[ $value ] = $value;
		}
		/* Do not use class Sobi!!! */
		if ( SPFactory::config()->key( 'user.sobipro_groups', false ) ) {
//		if ( Sobi::Cfg( 'user.sobipro_groups', false ) ) {
			$this->sobiproGroups();
		}
		/* include default visitor permissions */
		$this->parentGids();
	}

	/**
	 * Checks if the current user is an admin in backend.
	 *
	 * @return bool
	 * @throws \Exception
	 */
//	public function isAdmin(): bool
//	{
//		$id = JFactory::getApplication()->getIdentity();
//		$root = JFactory::getApplication()->getIdentity()->get( 'isRoot' );
//		$result = defined( 'SOBIPRO_ADM' ) && JFactory::getApplication()->getIdentity()->get( 'isRoot' ) !== null ?
//			JFactory::getApplication()->getIdentity()->get( 'isRoot' ) :
//			JUser::authorise( 'core.admin' );
//
//		return $result;
//	}


	/**
	 * Load SobiPro's own user groups and add them to the Joomla user groups.
	 */
	protected function sobiproGroups()
	{
		try {
			$db = Factory::Db();
			$valid = $db->valid( 'rel.validUntil', 'rel.validSince', 'grp.enabled' );
			$join = [
				[ 'table' => 'spdb_user_group', 'as' => 'grp', 'key' => 'gid' ],
				[ 'table' => 'spdb_users_relation', 'as' => 'rel', 'key' => 'gid' ],
			];

			$gids = $db
				->select( 'rel.gid', $db->join( $join ), [ '@VALID' => $valid, 'uid' => $this->id ] )
				->loadResultArray();

			if ( count( $gids ) ) {
				$this->gid = array_merge( $gids, $this->gid );
			}
		}
		catch ( Sobi\Error\Exception $x ) {
			if ( class_exists( 'Sobi', false ) ) {
				Sobi::Error( 'permissions', sprintf( 'Cannot load SobiPro user groups. %s', $x->getMessage() ), C::WARNING, 0, __LINE__, __CLASS__ );
			}
		}
	}

	/**
	 * @return string[]
	 * @throws \Sobi\Error\Exception|\SPException
	 */
	public static function availableGroups(): array
	{
		$groups = [ 0 => 'visitor' ];

		/* SobiPro user groups */
		if ( SPFactory::config()->key( 'user.sobipro_groups', false ) ) {
			$usergroups = Factory::Db()
				->select( [ 'groupName', 'gid' ], 'spdb_user_group' )
				->loadAssocList( 'gid' );
			if ( count( $usergroups ) ) {
				foreach ( $usergroups as $gid => $data ) {
					$groups[ $gid ] = $data[ 'groupName' ];
				}
			}
		}
		/* Joomla user groups */
		$usergroups = Factory::Db()
			->select( [ 'title', 'id' ], '#__usergroups' )
			->loadAssocList( 'id' );
		if ( count( $usergroups ) ) {
			foreach ( $usergroups as $gid => $data ) {
				$groups[ $gid ] = $data[ 'title' ];
			}
		}

		return $groups;
	}

	/**
	 * @param $gids
	 *
	 * @return array|int[]|string[]|null
	 * @throws \Sobi\Error\Exception
	 */
	public static function groups( $gids )
	{
		$groups = $usergroups = [];
		if ( $gids instanceof self ) {
			$gids = $gids->get( 'gid' );
		}
		if ( count( $gids ) ) {
			$groups = array_flip( $gids );
			/* SobiPro user groups (deprecated) */
			if ( SPFactory::config()->key( 'user.sobipro_groups', false ) ) {
				$usergroups = Factory::Db()
					->select( [ 'groupName', 'gid' ], 'spdb_user_group', [ 'gid' => $gids ] )
					->loadAssocList( 'gid' );
				if ( count( $usergroups ) ) {
					foreach ( $usergroups as $gid => $data ) {
						if ( isset( $groups[ $gid ] ) ) {
							$groups[ $gid ] = $data[ 'groupName' ];
						}
					}
				}
			}

			/* Joomla user groups */
			if ( count( $usergroups ) < count( $groups ) ) {
				$usergroups = Factory::Db()
					->select( [ 'title', 'id' ], '#__usergroups', [ 'id' => $gids ] )
					->loadAssocList( 'id' );
				if ( count( $usergroups ) ) {
					foreach ( $usergroups as $gid => $data ) {
						if ( isset( $groups[ $gid ] ) ) {
							$groups[ $gid ] = $data[ 'title' ];
						}
					}
				}
			}
		}

		return $groups;
	}

	/**
	 * @param int $id
	 *
	 * @return string
	 */
	public static function userUrl( $id )
	{
		return 'index.php?option=com_users&amp;task=user.edit&amp;id=' . $id;
	}


	/**
	 * Gets all parent groups.
	 *
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function parentGids()
	{
		if ( count( $this->gid ) ) {
			foreach ( $this->gid as $gid ) {
				if ( $gid >= 5000 && SPFactory::config()->key( 'user.sobipro_groups', false ) ) {
					/* manage SobiPro user groups */
					$gids = [];
					while ( $gid > 5000 ) {
						try {
							$gid = Factory::Db()
								->select( 'pid', 'spdb_user_group', [ 'gid' => $gid, 'enabled' => 1 ] )
								->loadResult();
							$gids[] = $gid;
						}
						catch ( Exception $x ) {
							if ( class_exists( 'Sobi', false ) ) {
								Sobi::Error( 'permissions', SPLang::e( 'Cannot load additional gids. %s', $x->getMessage() ), C::WARNING, 0, __LINE__, __CLASS__ );
							}
						}
					}
					$cgids = Access::parentGroups( $gid );
					$cgids[] = $gid;
					$gids = array_merge( $gids, $cgids );
				}
				else {
					/* Joomla user groups */
					$gids = Access::parentGroups( $gid );
					$gids[] = $gid;
				}
				if ( is_array( $gids ) && count( $gids ) ) {
					foreach ( $gids as $giddi ) {
						$this->gid[] = $giddi;
					}
				}
			}
		}
		$this->gid = array_unique( $this->gid );
	}

	/**
	 * Checks the permission for an action.
	 *
	 * @param $subject
	 * @param string $action -> e.g. 'edit'
	 * @param string $value
	 * @param string $section
	 *
	 * @return bool|mixed
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 * @internal param string $ownership - e.g. own, all or global
	 */
	public function can( $subject, $action = 'access', $value = 'valid', $section = C::ES )
	{
		if ( strstr( $subject, '.' ) ) {
			$subject = explode( '.', $subject );
			$action = $subject[ 1 ];
			if ( isset( $subject[ 2 ] ) ) {
				$value = $subject[ 2 ];
			}
			$subject = $subject[ 0 ];
		}
		if ( !$section ) {
			$section = Sobi::Section();
		}
		$can = $this->authorisePermission( $section, $subject, $action, $value );
		if ( SPFactory::registry()->_isset( 'plugins' ) ) {
			Sobi::Trigger( 'Authorise', 'Permission', [ &$can, $section, $subject, $action, $value ] );
		}

		return $can;
	}

	/**
	 * @param $section
	 * @param $subject
	 * @param $action
	 * @param $value
	 *
	 * @return bool|mixed
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 * @throws \Exception
	 */
	public function authorisePermission( $section, $subject, $action, $value )
	{
		if ( $this->isAdmin() ) {
			return true;
		}
		// native joomla ACL
		if ( $subject == 'cms' ) {
			$identity = JFactory::getApplication()->getIdentity();
			if ( $identity ) {
				return $identity->authorise( 'core.' . $action, 'com_sobipro' );
			}
			else {
				return false;
			}
		}
		/* translate automatic created request */
		switch ( $action ) {
			case 'cancel':
				return true;
				break;
			case 'save':
			case 'submit':
				$id = Input::Sid( 'request', Input::Int( 'rid' ) );
				$action = $id ? 'edit' : 'add';
				break;
			case 'enable':
			case 'hide':
			case 'disable':
				$action = 'manage';
				break;
			case 'apply':
				$action = 'edit';
			case 'details':
			case 'view':
				$action = 'access';
		}
		if ( in_array( $subject, [ 'acl', 'config', 'extensions' ] ) ) {
			$action = 'manage';
			$section = 0;
		}
		if ( !$section ) {
			$value = 'global';
			if ( in_array( Input::Task(), $this->_special ) ) {
				return true;
			}
		}
		/* admin panel or site front */
		$site = SOBI_ACL;
		/* initialise */
		$auth = false;
		/* if not initialised */
		if ( !isset( $this->_permissions[ $section ] ) || !count( $this->_permissions[ $section ] ) ) {
			$this->initPermissions( (int) $section );
		}

		/* if already requested, return the answer */
		$i = "[$site][$section][$subject][$action][$value]";
		if ( isset( $this->_prequest[ $i ] ) ) {
			return $this->_prequest[ $i ];
		}
		if ( isset( $this->_permissions[ $section ] ) ) {
			if ( isset( $this->_permissions[ $section ][ $subject ] ) ) {
				if ( isset( $this->_permissions[ $section ][ $subject ][ $action ] ) ) {
					if ( isset( $this->_permissions[ $section ][ $subject ][ $action ][ $value ] ) ) {
						$auth = $this->_permissions[ $section ][ $subject ][ $action ][ $value ];
					}
					else {
						if ( isset( $this->_permissions[ $section ][ $subject ][ '*' ][ '*' ] ) ) {
							$auth = $this->_permissions[ $section ][ $subject ][ '*' ][ '*' ];
						}
						else {
							if ( isset( $this->_permissions[ $section ][ $subject ][ $action ][ '*' ] ) ) {
								$auth = $this->_permissions[ $section ][ $subject ][ $action ][ '*' ];
							}
						}
					}
				}
				else {
					if ( isset( $this->_permissions[ $section ][ $subject ][ '*' ] ) ) {
						$auth = $this->_permissions[ $section ][ $subject ][ '*' ];
						if ( array_key_exists( '*', $auth ) ) {
							$auth = $auth[ '*' ];
						}
					}
				}
			}
			else {
				if ( isset( $this->_permissions[ $section ][ '*' ] ) ) {
					$auth = $this->_permissions[ $section ][ '*' ];
				}
			}
		}

		// @@ just for tests
//		$a = ( $auth ) ? 'GRANTED' : 'DENIED';//var_export( debug_backtrace( false ), true ) ;
//		SPConfig::debOut("{$action} {$subject} {$value} === {$a} ");

		/* store the answer for future request */
		$this->_prequest[ $i ] = $auth;

		return $auth;
	}

	/**
	 * @param int $sid
	 *
	 * @return void
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function initPermissions( int $sid = 0 )
	{
		$sid = $sid ? : (int) Sobi::Section();
		if ( isset( $this->_permissions[ $sid ] ) ) {
			return;
		}
		$db = Factory::Db();

		/* first thing we need is all rule ids for the group where the user is assigned to */
		$join = [
			[ 'table' => 'spdb_permissions_groups', 'as' => 'spgr', 'key' => 'rid' ],
			[ 'table' => 'spdb_permissions_rules', 'as' => 'sprl', 'key' => 'rid' ],
		];
		$gids = implode( ', ', $this->gid );
		$valid = $db->valid( 'sprl.validUntil', 'sprl.validSince', 'state' );
		$valid .= "AND spgr.gid in( $gids ) ";

		try {
			$this->_prules = $db
				->dselect( 'sprl.rid', $db->join( $join ), [ '@VALID' => $valid ] )
				->loadResultArray();
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( 'permissions', SPLang::e( 'CANNOT_GET_PERMISSIONS', $x->getMessage() ), C::WARNING, 500, __LINE__, __CLASS__ );
		}

		/* if we have the rule ids we need to get permission for this section and global permission */
		$permissions = [];
		if ( count( $this->_prules ) ) {
			try {
				$permissions = $db
					->select( 'pid', 'spdb_permissions_map', [ 'sid' => $sid, 'rid' => $this->_prules ] )
					->loadResultArray();
			}
			catch ( Exception $x ) {
				Sobi::Error( 'permissions', SPLang::e( 'CANNOT_GET_USERS_DATA', $x->getMessage() ), C::WARNING, 500, __LINE__, __CLASS__ );
			}
		}
		/* get all available permissions */
		try {
			$this->_availablePerm = $db
				->select( '*', 'spdb_permissions', [ 'site' => SOBI_ACL, 'published' => 1 ] )
				->loadAssocList( 'pid' );
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( 'permissions', SPLang::e( 'CANNOT_GET_PERMISSIONS', $x->getMessage() ), C::WARNING, 0, __LINE__, __CLASS__ );
		}

		/* create permissions array */
		$this->_permissions[ $sid ] = [];
		if ( count( $permissions ) ) {
			foreach ( $permissions as $perm ) {
				if ( isset( $this->_availablePerm[ $perm ] ) ) {
					if ( !isset( $this->_permissions[ $sid ][ $this->_availablePerm[ $perm ][ 'subject' ] ] ) ) {
						$this->_permissions[ $sid ][ $this->_availablePerm[ $perm ][ 'subject' ] ] = [];
					}
					$this->_permissions[ $sid ][ $this->_availablePerm[ $perm ][ 'subject' ] ][ $this->_availablePerm[ $perm ][ 'action' ] ][ $this->_availablePerm[ $perm ][ 'value' ] ] = true;
				}
			}
		}
	}

	/**
	 * Gets base data from Joomla users table.
	 * Often there are only little information needed, so it does not make
	 * sense to instance the big object just to get these data.
	 *
	 * @param array|int $id
	 *
	 * @return array|false|int|mixed
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function getBaseData( $id )
	{
		if ( is_int( $id ) ) {
			$ids = [ $id ];
		}
		else {
			$ids = $id;
		}
		if ( !is_array( $ids ) || !count( $ids ) ) {
			return false;
		}

		$data = [];
		try {
			$data = Factory::Db()
				->select( '*', '#__users', [ 'id' => $ids ] )
				->loadObjectList( 'id' );
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( 'user', SPLang::e( 'CANNOT_GET_USERS_DATA', $x->getMessage() ), C::WARNING, 0, __LINE__, __CLASS__ );
		}
		if ( is_int( $id ) ) {
			return $data[ $id ] ?? 0;
		}
		else {
			return $data;
		}
	}

	/**
	 * @return \SPUser|false
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 * @throws \Exception
	 */
	public static function & getCurrent()
	{
		static $user = false;
		if ( !( $user instanceof SPUser ) ) {
			if ( SOBI_CMS != 'joomla4' ) {
				$uid = JFactory::getUser()->get( 'id' );
			}
			else {
				$identity = JFactory::getApplication()->getIdentity();
				$uid = $identity ? $identity->get( 'id' ) : 0;
			}
			$user = new SPUser( $uid );
		}

		return $user;
	}

	/**
	 * Sets the value of a user state variable.
	 *
	 * @param string $key -  The path of the state.
	 * @param $value -  The value of the variable.
	 *
	 * @return mixed  - The previous state, if one existed.
	 * @throws Exception
	 */
	public function setUserState( string $key, &$value )
	{
		return JFactory::getApplication()->setUserState( "com_sobipro.$key", $value );
	}

	/**
	 * Gets the value of a user state from request.
	 *
	 * @param string $key - The key of the user state variable.
	 * @param string $request - The name of the variable passed in a request.
	 * @param string $default - The default value for the variable if not found. Optional.
	 * @param string $type - Filter for the variable.
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function & getUserState( string $key, $request, $default = null, $type = 'none' )
	{
		$userState = JFactory::getApplication()->getUserStateFromRequest( "com_sobipro.$key", $request, $default, $type );
		Input::Set( $request, $userState ? : C::ES );

		return $userState;
	}

	/**
	 * Gets the value of a user data stored in session.
	 *
	 * @param string $key - The key of the user state variable.
	 * @param null $default - The default value for the variable if not found. Optional.
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function & getUserData( string $key, $default = null )
	{
		$userState = JFactory::getApplication()->getUserState( "com_sobipro.$key", $default );

		return $userState;
	}

	/**
	 * Sets the value of a user data.
	 *
	 * @param string $key - The path of the state.
	 * @param string $value - The value of the variable.
	 *
	 * @return mixed    The previous state, if one existed.
	 * @throws Exception
	 */
//	public function setUserData( string $key, &$value )
//	{
//		return self::setUserState( $key, $value );
//	}

	/**
	 * Checks if the current user is an admin.
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function isAdmin(): bool
	{
		$application = JFactory::getApplication();
		$identity = $application->getIdentity();
		if ( !$identity ) {
			$application->loadIdentity( $this );
			$identity = $application->getIdentity();
		}
		if ( $identity ) {
			return $identity->authorise( 'core.admin' );
		}
		else {
			return false;
		}
	}

	/**
	 * Gets a Joomla user by a given user id.
	 *
	 * @param int $uid
	 *
	 * @return JUser|null
	 */
	public static function getUser( int $uid ): ?JUser
	{
		$user = null;
		try {
			if ( SOBI_CMS != 'joomla4' ) {
				$user = JFactory::getUser( $uid );
			}
			else {
				$user = JFactory::getContainer()->get( UserFactoryInterface::class )->loadUserById( $uid );
			}
		}
		catch ( Exception $x ) {
		}

		return $user;
	}

	/**
	 * @param string $username
	 *
	 * @return JUser|null
	 * @throws \Sobi\Error\Exception
	 */
	public static function getUserByUsername( string $username ): ?JUser
	{
		$uid = (int) Factory::Db()
			->select( 'id', '#__users', [ 'username' => $username ] )
			->loadResult();

		return self::getUser( $uid );
	}

	/**
	 * @param string $email
	 *
	 * @return JUser|null
	 * @throws \Sobi\Error\Exception
	 */
	public static function getUserByEmail( string $email ): ?JUser
	{
		$uid = (int) Factory::Db()
			->select( 'id', '#__users', [ 'email' => $email ] )
			->loadResult();

		return self::getUser( $uid );
	}

	/**
	 * Checks if a user is a superuser.
	 *
	 * @param int $uid
	 *
	 * @return bool
	 */
	public static function isRoot( int $uid ): bool
	{
		if ( $uid ) {
			$user = self::getUser( $uid );
			if ( $user ) {
				try {
					return $user->authorise( 'core.admin' );
				}
				catch ( Exception $x ) {
				}
			}
		}

		return false;
	}

	/**
	 * @param string $username
	 *
	 * @return bool
	 * @throws \Sobi\Error\Exception
	 */
	public static function isRootByUsername( string $username ): bool
	{
		if ( $username ) {
			$user = self::getUserByUsername( $username );
			if ( $user && $user->get( 'id' ) ) {
				try {
					return $user->authorise( 'core.admin' );
				}
				catch ( Exception $x ) {
				}
			}
		}

		return false;
	}

	/**
	 * @param string $email
	 *
	 * @return bool
	 * @throws \Sobi\Error\Exception
	 */
	public static function isRootByEmail( string $email ): bool
	{
		if ( $email ) {
			$user = self::getUserByEmail( $email );
			if ( $user && $user->get( 'id' ) ) {
				try {
					return $user->authorise( 'core.admin' );
				}
				catch ( Exception $x ) {
				}
			}
		}

		return false;
	}

	/**
	 * @param string $username
	 * @param string $email
	 *
	 * @return int
	 * @throws \Sobi\Error\Exception
	 */
	public static function getUserId( string $username, string $email ): int
	{
		return (int) Factory::Db()
			->select( 'id', '#__users', [ 'username' => $username, 'email' => $email, ] )
			->loadResult();
	}

	/**
	 * @param string $field -> username or email
	 * @param string $value
	 * @param int $myself
	 *
	 * @return bool
	 * @throws \Sobi\Error\Exception
	 */
	public static function userExists( string $field, string $value, int $myself = 0 ): bool
	{
		return (bool) Factory::Db()
			->select( 'id', '#__users', [ $field => $value, '!id' => $myself ] )
			->loadResult();
	}

	/**
	 * Returns if a user with given uid already exists.
	 *
	 * @param int $uid
	 * @param int $myself
	 *
	 * @return bool
	 * @throws \Sobi\Error\Exception
	 */
	public static function userIdExists( int $uid, int $myself = 0 ): bool
	{
		return self::userExists( 'id', $uid, $myself );
	}

	/**
	 * Returns if a user with given email already exists.
	 *
	 * @param string $email
	 * @param int $myself
	 *
	 * @return bool
	 * @throws \Sobi\Error\Exception
	 */
	public static function userEmailExists( string $email, int $myself = 0 ): bool
	{
		return self::userExists( 'email', $email, $myself );
	}

	/**
	 * Returns if a user with given username already exists.
	 *
	 * @param string $name
	 * @param int $myself
	 *
	 * @return bool
	 * @throws \Sobi\Error\Exception
	 */
	public static function userNameExists( string $name, int $myself = 0 ): bool
	{
		return self::userExists( 'username', $name, $myself );
	}

	/**
	 * @param int $uid
	 * @param bool $block
	 *
	 * @return bool
	 */
	public static function block( int $uid, bool $block ): bool
	{
		if ( $uid ) {
			$user = self::getUser( $uid );
			if ( $user ) {
				try {
					if ( $block ) {
						$user->set( 'block', 1 );
					}
					else {
						$user->set( 'activation', 0 );
						$user->set( 'block', 0 );
					}

					return $user->save();
				}
				catch ( Exception $x ) {
				}
			}
		}

		return false;
	}

	/**
	 * Deletes a Joomla user by given uid.
	 *
	 * @param int $uid
	 *
	 * @return bool
	 */
	public static function deleteUser( int $uid ): bool
	{
		if ( $uid ) {
			$user = self::getUser( $uid );
			if ( $user ) {
				try {
					return $user->delete();
				}
				catch ( Exception $x ) {
				}
			}
		}

		return false;
	}

	/**
	 * @param int $uid
	 * @param array $groups
	 * @param bool $merge
	 *
	 * @return bool
	 */
	public static function setGroups( int $uid, array $groups, bool $merge ): bool
	{
		if ( $uid ) {
			$user = self::getUser( $uid );
			if ( $user ) {
				$groups = $merge ? array_merge( $groups, $user->get( 'groups' ) ) : $groups;
				$user->set( 'groups', $groups );
			}
			try {
				return $user->save();
			}
			catch ( Exception $x ) {
			}
		}

		return false;
	}

	/**
	 * @param int $uid
	 *
	 * @return array
	 */
	public static function getGroups( int $uid ): array
	{
		$groups = [];
		if ( $uid ) {
			$user = self::getUser( $uid );
			if ( $user ) {
				$groups = $user->get( 'groups' );
			}
		}

		return $groups;
	}

	/**
	 * Updates a Joomla user.
	 *
	 * @param array $userData
	 *
	 * @return bool
	 */
	public static function updateUser( array &$userData ): bool
	{
		if ( $userData[ 'uid' ] ) {
			$user = self::getUser( $userData[ 'uid' ] );
			if ( $user ) {
				/* if password has been changed */
				if ( isset( $userData[ 'pass' ] ) ) {
					$userData[ 'password' ] = $userData[ 'password2' ] = $userData[ 'pass' ];
					$user->bind( $userData );
				}
				$user->set( 'name', $userData[ 'name' ] );
				$user->set( 'username', $userData[ 'username' ] );
				$user->set( 'email', $userData[ 'email' ] );

				try {
					return $user->save();
				}
				catch ( Exception $x ) {
				}
			}
		}

		return false;
	}

	/**
	 * @param array $settings
	 *
	 * @return array
	 */
	public static function createUser( array $settings ): array
	{
		$userData[ 'result' ] = false;
		$user = new JUser();

		$pwd[ 'password' ] = $pwd[ 'password2' ] = $settings[ 'password' ];
		$user->bind( $pwd );

		if ( isset( $settings[ 'groups' ] ) ) {
			$user->set( 'groups', $settings[ 'groups' ] );
		}
		if ( isset( $settings[ 'name' ] ) ) {
			$user->set( 'name', $settings[ 'name' ] );
		}
		if ( isset( $settings[ 'username' ] ) ) {
			$user->set( 'username', $settings[ 'username' ] );
		}
		if ( isset( $settings[ 'email' ] ) ) {
			$user->set( 'email', $settings[ 'email' ] );
		}

		if ( isset( $settings[ 'activation' ] ) ) {
			$user->set( 'activation', (int) $settings[ 'activation' ] );
		}
		if ( isset( $settings[ 'block' ] ) ) {
			$user->set( 'block', (int) $settings[ 'block' ] );
		}
		if ( isset( $settings[ 'sendEmail' ] ) ) {
			$user->set( 'sendEmail', (int) $settings[ 'sendEmail' ] );
		}
		if ( isset( $settings[ 'resetCount' ] ) ) {
			$user->set( 'resetCount', (int) $settings[ 'resetCount' ] );
		}
		if ( isset( $settings[ 'isRoot' ] ) ) {
			$user->set( 'isRoot', (int) $settings[ 'isRoot' ] );
		}
		if ( isset( $settings[ 'requireReset' ] ) ) {
			$user->set( 'requireReset', (int) $settings[ 'requireReset' ] );
		}

		if ( $user->save() ) {
			$userData[ 'uid' ] = $user->get( 'id' );
			$userData[ 'pass' ] = $pwd[ 'password' ];
			$userData[ 'result' ] = true;
		}
		else {
			$userData[ 'error' ] = $user->get( '_errors' )[ 0 ];
		}

		return $userData;
	}

}

/**
 * class Access
 */
class Access extends JAccess
{
	/**
	 * @param $gid
	 *
	 * @return mixed
	 */
	public static function parentGroups( $gid )
	{
		return self::getGroupPath( $gid );
	}
}
