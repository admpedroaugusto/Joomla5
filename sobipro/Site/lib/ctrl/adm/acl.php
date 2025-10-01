<?php
/**
 * @package SobiPro Library
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
 * @created 29-Jan-2009 by Radek Suski
 * @modified 19 May 2023 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadController( 'config', true );

use Sobi\C;
use Sobi\Input\Input;
use Sobi\Lib\Factory;
use Sobi\Utils\StringUtils;

/**
 * Class SPAclCtrl
 */
final class SPAclCtrl extends SPConfigAdmCtrl
{
	/*** @var string */
	protected $_type = 'acl';
	/*** @var string */
	protected $_defTask = 'list';
	/*** @var array */
	private $_perms = [];

	/**
	 * SPAclCtrl constructor.
	 *
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	public function __construct()
	{
		if ( !Input::Task() == 'logs' && !Sobi::Can( 'cms.admin' ) ) {
			Sobi::Error( 'ACL', SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ), C::WARNING, 403, __LINE__, __FILE__ );
			exit();
		}
		parent::__construct();
	}

	/**
	 * @return void
	 * @throws \ReflectionException
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function execute()
	{
		$this->_task = strlen( $this->_task ) ? $this->_task : $this->_defTask;
		switch ( $this->_task ) {
			case 'enable':
			case 'disable':
				$this->state( $this->_task == 'enable' );
				break;
			case 'add':
			case 'edit':
				$this->edit();
				break;
			case 'list':
				$this->listRules();
				break;
			case 'cancel':
				$this->response( Sobi::Url( 'acl' ) );
				break;
			case 'save':
			case 'apply':
				$this->save( $this->_task == 'apply' );
				break;
			case 'delete':
				$this->delete();
				break;
			case 'toggle.enabled':
				$this->toggle();
				break;
			case 'section':
				$this->listSectionRules();
				break;
			default:
				/* case plugin didn't register this task, it was an error */
				if ( !( Sobi::Trigger( 'Execute', $this->name(), [ &$this ] ) ) ) {
					Sobi::Error( 'ACL', SPLang::e( 'SUCH_TASK_NOT_FOUND', Input::Task() ), C::NOTICE, 404, __LINE__, __FILE__ );
				}
				break;
		}
	}

	/**
	 * @return void
	 * @throws \Sobi\Error\Exception|SPException
	 */
	protected function toggle(): bool
	{
		$state = Factory::Db()
			->select( 'state', 'spdb_permissions_rules', [ 'rid' => Input::Int( 'rid' ) ] )
			->loadResult();

		$this->state( !$state );
	}

	/**
	 * @param $subject
	 * @param $action
	 * @param $value
	 * @param string $site
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function removePermission( $subject, $action, $value, string $site = 'front' )
	{
		Sobi::Trigger( 'Acl', __FUNCTION__, [ &$subject, &$action, &$value, &$site ] );
		try {
			Factory::Db()->delete( 'spdb_permissions',
			                       [ 'subject' => $subject,
			                         'action'  => $action,
			                         'value'   => $value,
			                         'site'    => $site ]
			);
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( 'ACL', SPLang::e( 'CANNOT_REMOVE_PERMISSION_DB_ERR', $subject, $action, $action, $x->getMessage() ), C::WARNING, 0 );
		}
	}

	/**
	 * @param $subject
	 * @param $action
	 * @param $value
	 * @param string $site
	 * @param int $published
	 *
	 * @return void
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function addPermission( $subject, $action, $value, string $site = 'front', $published = 1 )
	{
		Sobi::Trigger( 'ACL', __FUNCTION__, [ &$subject, &$action, &$value, &$site, &$published ] );
		if ( !count( $this->_perms ) ) {
			$this->loadPermissions();
		}
		if ( !( isset( $this->_perms[ $site ][ $subject ] )
			&& isset( $this->_perms[ $site ][ $subject ][ $action ] )
			&& in_array( $value, $this->_perms[ $site ][ $subject ][ $action ] ) )
		) {
			$this->_perms[ $site ][ $subject ][ $action ][] = $value;
			try {
				Factory::Db()->insert( 'spdb_permissions',
				                       [ 'pid'       => null,
				                         'subject'   => $subject,
				                         'action'    => $action,
				                         'value'     => $value,
				                         'site'      => $site,
				                         'published' => $published ]
				);
				SPFactory::history()->logAction( SPC::LOG_ADDINDIRECT, 0, 0, 'acl', C::ES, [ 'name' => $subject . '.' . $action . '.' . $value ]
				);
			}
			catch ( Sobi\Error\Exception $x ) {
				Sobi::Error( 'ACL', SPLang::e( 'CANNOT_ADD_NEW_PERMS', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
			}
		}
	}

	/**
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function loadPermissions()
	{
		try {
			$permissions = Factory::Db()
				->select( '*', 'spdb_permissions' )
				->loadObjectList();
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( 'ACL', SPLang::e( 'CANNOT_GET_PERMISSION_LIST', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );

			return;
		}
		foreach ( $permissions as $permission ) {
			if ( !isset( $this->_perms[ $permission->site ] ) ) {
				$this->_perms[ $permission->site ] = [];
			}
			if ( !isset( $this->_perms[ $permission->site ][ $permission->subject ] ) ) {
				$this->_perms[ $permission->site ][ $permission->subject ] = [];
			}
			if ( !isset( $this->_perms[ $permission->site ][ $permission->subject ][ $permission->action ] ) ) {
				$this->_perms[ $permission->site ][ $permission->subject ][ $permission->action ] = [];
			}
			$this->_perms[ $permission->site ][ $permission->subject ][ $permission->action ][] = $permission->value;
		}
	}

	/**
	 * Adds a new rule indirectly from creating a new section or installing a template.
	 *
	 * @param $name
	 * @param $sections
	 * @param $perms
	 * @param $groups
	 * @param string $note
	 *
	 * @return int
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function addNewRule( $name, $sections, $perms, $groups, $note = C::ES )
	{
		SPLoader::loadClass( 'cms.base.users' );

		$rid = 0;
		$db = Factory::Db();
		try {
			$db->insertUpdate( 'spdb_permissions_rules',
			                   [ 'name'       => $name,
			                     'nid'        => StringUtils::Nid( $name ),
			                     'validSince' => $db->getNullDate(),
			                     'validUntil' => $db->getNullDate(),
			                     'note'       => $note,
			                     'state'      => 1 ]
			);
			$rid = $db->insertId();
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( 'ACL', SPLang::e( 'CANNOT_CREATE_RULE_DB_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}

		$affectedGroups = [];
		$gids = SPUser::availableGroups();
		foreach ( $gids as $id => $group ) {
			if ( in_array( $group, $groups ) || in_array( strtolower( $group ), $groups ) ) {
				$affectedGroups[] = [ 'rid' => $rid, 'gid' => $id ];
			}
		}
		try {
			$db->insertArray( 'spdb_permissions_groups', $affectedGroups );
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( 'ACL', SPLang::e( 'CANNOT_INSERT_GROUPS_DB_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}

		if ( !count( $this->_perms ) ) {
			$this->loadPermissions();
		}
		$map = [];
		foreach ( $perms as $perm ) {
			$perm = explode( '.', $perm );
			$pid = 0;
			try {
				$pid = $db
					->select( 'pid', 'spdb_permissions',
					          [ 'subject' => $perm[ 0 ],
					            'action'  => $perm[ 1 ],
					            'value'   => $perm[ 2 ] ?? C::ES,
					            'site'    => $perm[ 3 ] ?? 'front' ]
					)
					->loadResult();
			}
			catch ( Sobi\Error\Exception $x ) {
				Sobi::Error( 'ACL', SPLang::e( 'CANNOT_CREATE_RULE_DB_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
			}
			if ( $pid ) {
				foreach ( $sections as $sid ) {
					$map[] = [ 'rid' => $rid, 'sid' => $sid, 'pid' => $pid ];
				}
			}
		}
		if ( count( $map ) ) {
			try {
				$db->insertArray( 'spdb_permissions_map', $map, true );
				SPFactory::history()->logAction( SPC::LOG_ADDINDIRECT, $rid, 0, 'acl', C::ES, [ 'name' => $name ] );
			}
			catch ( Sobi\Error\Exception $x ) {
				Sobi::Error( 'ACL', SPLang::e( 'CANNOT_INSERT_GROUPS_DB_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
			}
		}

		return $rid;
	}

	/**
	 * Saves a rule.
	 *
	 * @param bool $apply
	 * @param false $clone
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function save( $apply = false, $clone = false )
	{
		Sobi::Trigger( 'Save', 'Acl', [ &$this ] );
		if ( !Factory::Application()->checkToken() ) {
			Sobi::Error( 'Token', SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ), C::ERROR, 403, __LINE__, __FILE__ );
		}

		$db = Factory::Db();
		$rid = Input::Rid();
		$this->validate( 'acl.edit', [ 'task' => 'acl.edit', 'rid' => $rid ] );
		if ( $rid ) {
			$this->remove( $rid );
		}
		$vs = Input::Timestamp( 'set_validSince' );
		$vu = Input::Timestamp( 'set_validUntil' );
		$vs = $vs ? gmdate( Sobi::Cfg( 'date.db_format', SPC::DEFAULT_DB_DATE ), (int) $vs ) : $db->getNullDate();
		$vu = $vu ? gmdate( Sobi::Cfg( 'date.db_format', SPC::DEFAULT_DB_DATE ), (int) $vu ) : $db->getNullDate();

		$name = Input::String( 'set_name' );
		$nid = Input::Cmd( 'set_nid' );
		$note = Input::String( 'set_note' );
		$state = Input::Int( 'set_state', 'request', 1 );
		$gids = Input::Arr( 'set_groups' );
		$sids = Input::Arr( 'set_sections' );
		$permissionsFront = Input::Arr( 'set_permissions' );
		$permissionsAdm = Input::Arr( 'set_adm_permissions' );

		/* when can publish any, then can see any unpublished */
		if ( in_array( 20, $permissionsFront ) ) {
			$permissionsFront[] = 14;
		}
		/* when can publish own, then can see own unpublished */
		if ( in_array( 21, $permissionsFront ) ) {
			$permissionsFront[] = 12;
		}
		/* when entry manage, then entry access unapproved any */
		if ( in_array( 19, $permissionsFront ) ) {
			$permissionsFront[] = 15;
		}
		$perms = array_merge( $permissionsFront, $permissionsAdm );

		/* update or insert the rule definition */
		try {
			$db->insertUpdate( 'spdb_permissions_rules',
			                   [ 'rid'        => $rid,
			                     'name'       => $name,
			                     'nid'        => $nid ? $nid : StringUtils::Nid( $name ),
			                     'validSince' => $vs,
			                     'validUntil' => $vu,
			                     'note'       => $note,
			                     'state'      => $state ]
			);
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( 'ACL', SPLang::e( 'CANNOT_CREATE_RULE_DB_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}
		$logAction = $rid == 0 ? SPC::LOG_ADD : SPC::LOG_EDIT;
		$rid = $rid ? : $db->insertId();

		/* insert the groups ids */
		if ( count( $gids ) ) {
			foreach ( $gids as $i => $gid ) {
				$gid = $gid == C::ES ? 0 : $gid;
				$gids[ $i ] = [ 'rid' => $rid, 'gid' => $gid ];
			}
			try {
				$db->insertArray( 'spdb_permissions_groups', $gids );
			}
			catch ( Sobi\Error\Exception $x ) {
				Sobi::Error( 'ACL', SPLang::e( 'CANNOT_INSERT_GROUPS_DB_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
			}
		}

		try {
			$admPermissions = $db->select( '*', 'spdb_permissions', [ 'site' => 'adm' ] )->loadResultArray();
		}
		catch ( Exception $x ) {
			Sobi::Error( 'ACL', SPLang::e( 'CANNOT_GET_PERMISSIONS_DB_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}

		/* create permission and section map */
		if ( count( $sids ) && count( $perms ) ) {
			$map = [];
			/* travel the sections */
			foreach ( $sids as $sid ) {
				foreach ( $perms as $pid ) {
					if ( in_array( $pid, $admPermissions ) ) {
						$map[] = [ 'rid' => $rid, 'sid' => $sid, 'pid' => $pid ];
					}
					else {
						$map[] = [ 'rid' => $rid, 'sid' => $sid, 'pid' => $pid ];
					}
				}
			}
			try {
				$db->insertArray( 'spdb_permissions_map', $map, true );
			}
			catch ( Sobi\Error\Exception $x ) {
				Sobi::Error( 'ACL', SPLang::e( 'CANNOT_INSERT_GROUPS_DB_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
			}
		}
		SPFactory::cache()->cleanAll();

		/* trigger plugins */
		Sobi::Trigger( 'AfterSave', 'Acl', [ &$this ] );

		SPFactory::history()->logAction( $logAction, $rid, 0, 'acl', C::ES, [ 'name' => $name ] );
		/* set redirect */
		$this->response( Sobi::Url( $apply ? [ 'task' => 'acl.edit', 'rid' => $rid ] : 'acl' ), Sobi::Txt( 'ACL_RULE_SAVED' ), !$apply, C::SUCCESS_MSG, [ 'sets' => [ 'rid' => $rid ] ] );
	}

	/**
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function delete()
	{
		$db = Factory::Db();

		$rids = Input::Arr( 'rid', 'request', [] );
		if ( !count( $rids ) ) {
			if ( Input::Int( 'rid' ) ) {
				$rids = [ Input::Int( 'rid' ) ];
			}
			else {
				$this->response( Sobi::Back(), Sobi::Txt( 'ACL_SELECT_RULE_FIRST' ), true, C::ERROR_MSG );
			}
		}
		$info = [];

		/* Collect the rule names before deleting them */
		if ( count( $rids ) ) {
			foreach ( $rids as $rid ) {
				$info[ $rid ] = [ 'name' => $this->getRuleName( $rid ), 'section' => $this->getSection( $rid ) ];
			}
		}
		try {
			$db->delete( 'spdb_permissions_groups', [ 'rid' => $rids ] );
			$db->delete( 'spdb_permissions_map', [ 'rid' => $rids ] );
			$db->delete( 'spdb_permissions_rules', [ 'rid' => $rids ] );
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( 'ACL', SPLang::e( 'CANNOT_REMOVE_RULES_DB_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}

		if ( count( $rids ) ) {
			foreach ( $rids as $rid ) {
				SPFactory::history()->logAction( SPC::LOG_DELETE, $rid, 0, 'acl', C::ES, $info[ $rid ] ? : [] );
			}
		}
		$this->response( Sobi::Url( 'acl' ), Sobi::Txt( 'ACL_RULE_DELETED' ), true, C::SUCCESS_MSG );
	}

	/**
	 * @param $rid
	 *
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function remove( $rid )
	{
		$db = Factory::Db();
		try {
			$db->delete( 'spdb_permissions_groups', [ 'rid' => $rid ] );
			$db->delete( 'spdb_permissions_map', [ 'rid' => $rid ] );
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( 'ACL', SPLang::e( 'CANNOT_REMOVE_PERMISSIONS_DB_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}
	}

	/**
	 * @param $state
	 *
	 * @return void
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function state( $state )
	{
		$rid = Input::Int( 'rid' );
		$where = C::ES;
		if ( !$rid ) {
			$rid = Input::Arr( 'rid' );
			if ( count( $rid ) ) {
				$where = [ 'rid' => $rid ];
			}
		}
		else {
			$where = [ 'rid' => $rid ];
		}
		if ( !$where ) {
			$this->response( Sobi::Back(), Sobi::Txt( 'ACL_SELECT_RULE_FIRST' ), true, C::ERROR_MSG );

			return;
		}
		try {
			Factory::Db()->update( 'spdb_permissions_rules', [ 'state' => (int) $state ], $where );

			if ( is_array( $rid ) ) {
				foreach ( $rid as $rule ) {
					SPFactory::history()->logAction( $state ? SPC::LOG_PUBLISH : SPC::LOG_UNPUBLISH,
					                                 $rule,
					                                 0,
					                                 'acl',
					                                 C::ES,
					                                 [ 'name' => $this->getRuleName( $rule ) ]
					);
				}
			}
			else {
				SPFactory::history()->logAction( $state ? SPC::LOG_PUBLISH : SPC::LOG_UNPUBLISH,
				                                 $rid,
				                                 0,
				                                 'acl',
				                                 C::ES,
				                                 [ 'name' => $this->getRuleName( $rid ) ]
				);
			}
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( 'ACL', SPLang::e( 'Db reports %s.', $x->getMessage() ), C::ERROR, 500, __LINE__, __FILE__ );

			return;
		}
		$this->response( Sobi::Back(), Sobi::Txt( 'ACL.MSG_STATE_CHANGED' ), true, C::SUCCESS_MSG );
	}

	/**
	 * @throws \ReflectionException
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function edit()
	{
		if ( !Sobi::Can( 'cms.admin' ) ) {
			Sobi::Error( 'ACL', SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ), C::WARNING, 403, __LINE__, __FILE__ );
			exit();
		}

		$rid = Input::Rid();
		SPLoader::loadClass( 'cms.base.users' );

		$db = Factory::Db();
		try {
			$sections = $db
				->select( '*', 'spdb_object', [ 'oType' => 'section' ] )
				->loadObjectList();
			$admPermissions = $db
				->select( '*', 'spdb_permissions', [ 'site' => 'adm', 'published' => 1 ] )
				->loadObjectList();
			$frontPermissions = $db
				->select( '*', 'spdb_permissions', [ 'site' => 'front', 'published' => 1 ] )
				->loadObjectList();
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( 'ACL', SPLang::e( 'Db reports %s.', $x->getMessage() ), SPC::WARNING, 500, __LINE__, __FILE__ );
		}

		/** @var SPAclView $view */
		$view = SPFactory::View( 'acl', true );
		$view
			->assign( $this->_task, 'task' )
			->assign( $sections, 'sections' )
			->assign( $admPermissions, 'adm_permissions' )
			->assign( $frontPermissions, 'permissions' );

		if ( $rid ) {
			try {
				$rule = $db
					->select( '*', 'spdb_permissions_rules', [ 'rid' => $rid ] )
					->loadAssocList( 'rid' );
				$rule = $rule[ $rid ];
				if ( $rule[ 'validSince' ] == $db->getNullDate() ) {
					$rule[ 'validSince' ] = C::ES;
				}
				if ( $rule[ 'validUntil' ] == $db->getNullDate() ) {
					$rule[ 'validUntil' ] = C::ES;
				}
				$view->assign( $rule[ 'name' ], 'rule' );
				$rule[ 'groups' ] = $db
					->select( 'gid', 'spdb_permissions_groups', [ 'rid' => $rid ] )
					->loadResultArray();

				$rule[ 'permissions' ] = $db
					->select( '*', 'spdb_permissions_map', [ 'rid' => $rid ] )
					->loadAssocList();
				$view->assign( $rule, 'set' );
			}
			catch ( Sobi\Error\Exception $x ) {
				Sobi::Error( 'ACL', SPLang::e( 'Db reports %s.', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
			}
		}
		else {
			$rule = [
				'validUntil'  => C::ES,
				'validSince'  => C::ES,
				'name'        => C::ES,
				'nid'         => C::ES,
				'note'        => C::ES,
				'permissions' => [],
			];
			$view->assign( $rule, 'set' );
		}
		$userGroups = $this->userGroups();
		$view->assign( $userGroups, 'groups' );

		$view->display();
	}

	/**
	 * @param bool $disabled
	 *
	 * @return array
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	public function userGroups( bool $disabled = false )
	{
		SPLoader::loadClass( 'cms.base.users' );
		$cgids = SPUsers::getGroupsField();
		if ( $disabled ) {
			foreach ( $cgids as $gid => $group ) {
				$cgids[ $gid ][ 'disable' ] = true;
			}
		}
		$gids = $parents = $groups = [];
		try {
			$ids = Factory::Db()->select( [ 'pid', 'groupName', 'gid' ], 'spdb_user_group', [ 'enabled' => 1 ] )->loadAssocList( 'gid' );
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( 'ACL', SPLang::e( 'Db reports %s.', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}
		if ( count( $ids ) ) {
			$this->sortGroups( $ids, $gids, $parents );
		}
		foreach ( $cgids as $group ) {
			$groups[] = $group;
			preg_match( '/\.([&nbsp;]+)\-/', $group[ 'text' ], $nbsp );
			if ( !( isset( $nbsp[ 1 ] ) ) ) {
				$nbsp[ 1 ] = null;
			}
			if ( isset( $parents[ $group[ 'value' ] ] ) ) {
				foreach ( $parents[ $group[ 'value' ] ] as $gid => $grp ) {
					$this->addGroups( $grp, $groups, $nbsp[ 1 ] );
				}
			}
		}

		return $groups;
	}

	/**
	 * @param $group
	 * @param $groups
	 * @param $nbsp
	 */
	protected function addGroups( $group, &$groups, $nbsp )
	{
		$nbsp = $nbsp . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		$groups[] = [ 'value' => $group[ 'gid' ], 'text' => '.' . $nbsp . '-&nbsp;' . $group[ 'groupName' ] ];
		if ( isset( $group[ 'childs' ] ) && count( $group[ 'childs' ] ) ) {
			foreach ( $group[ 'childs' ] as $gid => $grp ) {
				$this->addGroups( $grp, $groups, $nbsp );
			}
		}
	}

	/**
	 * @param $ids
	 * @param $gids
	 * @param $parents
	 */
	protected function sortGroups( $ids, &$gids, &$parents )
	{
		foreach ( $ids as $gid => $group ) {
			if ( $group[ 'pid' ] >= 5000 && Sobi::Cfg( 'user.sobipro_groups', false ) ) {
				$this->getGroupChildren( $gid, $ids, $group, $gids );
				if ( !isset( $gids[ $group[ 'pid' ] ] ) ) {
					$gids[ $group[ 'pid' ] ] = $ids[ $group[ 'pid' ] ];
				}
				$gids[ $group[ 'pid' ] ][ 'childs' ][ $gid ] = $group;
			}
			else {
				$gids[ $gid ] = $group;
				$gids[ $gid ][ 'childs' ] = [];
			}
		}
		if ( count( $gids ) ) {
			foreach ( $gids as $gid => $group ) {
				if ( $group[ 'pid' ] >= 5000 && Sobi::Cfg( 'user.sobipro_groups', false ) ) {
					unset( $gids[ $gid ] );
				}
				else {
					$parents[ $group[ 'pid' ] ][] = $gids[ $gid ];
				}
			}
		}
	}

	/**
	 * @param $gid
	 * @param $ids
	 * @param $group
	 * @param $gids
	 */
	protected function getGroupChildren( $gid, $ids, &$group, &$gids )
	{
		foreach ( $ids as $cgid => $cgroup ) {
			if ( $cgroup[ 'pid' ] == $gid ) {
				if ( isset( $ids[ $gid ] ) ) {
					$this->getGroupChildren( $cgid, $ids, $cgroup, $gids );
					$group[ 'childs' ][ $cgid ] = $cgroup;
				}
			}
		}
	}

	/**
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 * @throws \Exception
	 */
	protected function listRules()
	{
		if ( !Sobi::Can( 'cms.admin' ) ) {
			Sobi::Error( 'ACL', SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ), C::WARNING, 403, __LINE__, __FILE__ );
			exit();
		}

		Sobi::ReturnPoint();
		$order = SPFactory::user()->getUserState( 'acl.order', 'position', 'rid.asc' );
		try {
			$rules = Factory::Db()
				->select( '*', 'spdb_permissions_rules', C::ES, $order )
				->loadObjectList();
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( 'ACL', SPLang::e( 'Db reports %s.', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}

		$menu = $this->createMenu( 'acl' );

		/** @var SPAclView $view */
		$view = SPFactory::View( 'acl', true );
		$view
			->assign( $this->_task, 'task' )
			->assign( $rules, 'rules' )
			->assign( $menu, 'menu' )
			->determineTemplate( 'acl', 'list' );

		$view->display();
	}

	/**
	 * @throws \ReflectionException
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 * @throws \Exception
	 */
	protected function listSectionRules()
	{
		if ( !Sobi::Can( 'cms.admin' ) ) {
			Sobi::Error( 'ACL', SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ), C::WARNING, 403, __LINE__, __FILE__ );
			exit();
		}

		Sobi::ReturnPoint();
//		$order = SPFactory::user()->getUserState( 'acl.order', 'position', 'rid.asc' );

		SPLoader::loadClass( 'cms.base.users' );
		$db = Factory::Db();
		try {
			$admPermissions = $db
				->select( '*', 'spdb_permissions', [ 'site' => 'adm', 'published' => 1 ] )
				->loadObjectList();
			$frontPermissions = $db
				->select( '*', 'spdb_permissions', [ 'site' => 'front', 'published' => 1 ] )
				->loadObjectList();
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( 'ACL', SPLang::e( 'Db reports %s.', $x->getMessage() ), C::WARNING, 500, __LINE__, __FILE__ );
		}

		$userGroups = $this->userGroups();
		$menu = $this->createMenu( 'acl.section' );

		/** @var SPAclView $view */
		$view = SPFactory::View( 'acl', true );
		$view
			->assign( $this->_task, 'task' )
			->assign( $admPermissions, 'adm_permissions' )
			->assign( $frontPermissions, 'permissions' )
			->assign( $userGroups, 'groups' )
			->assign( $menu, 'menu' )
//			->determineTemplate( 'acl', 'rules' )
			->display();
	}

	/**
	 * @param $rid
	 *
	 * @return string
	 */
	protected function getSection( $rid )
	{
		try {
			$sid = Factory::Db()
				->dselect( 'sid', 'spdb_permissions_map', [ 'rid' => $rid ] )
				->loadResult();
		}
		catch ( Sobi\Error\Exception $x ) {
			$sid = C::ES;
		}

		return $sid;
	}

	/**
	 * @param $rid
	 *
	 * @return string
	 */
	protected function getRuleName( $rid ): string
	{
		try {
			$name = Factory::Db()
				->select( 'name', 'spdb_permissions_rules', [ 'rid' => $rid ] )
				->loadResult();
		}
		catch ( Sobi\Error\Exception $x ) {
			$name = C::ES;
		}

		return $name;
	}

	/**
	 * @param $rid
	 *
	 * @return bool
	 */
	public function ruleExists( $rid ): bool
	{
		return (bool) $this->getRuleName( $rid );
	}
}
