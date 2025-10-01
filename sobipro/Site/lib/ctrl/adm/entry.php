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
 * @created 10-Jan-2009 by Radek Suski
 * @modified 25 September 2023 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadController( 'controller' );
SPLoader::loadController( 'entry' );

use Sobi\C;
use Sobi\Input\Input;
use Sobi\FileSystem\FileSystem;
use Sobi\Lib\Factory;
use SobiPro\Controllers\Entry;

/**
 * Class SPEntryAdmCtrl
 */
class SPEntryAdmCtrl extends Entry
{
	/**
	 * @return bool
	 * @throws \ReflectionException
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function execute()
	{
		$r = false;
		switch ( $this->_task ) {
			case 'edit':
			case 'add':
				$r = true;
				$this->editForm();
				break;
			case 'approve':
			case 'unapprove':
				$r = true;
				$this->authorise( 'approve' );
				$this->approval( $this->_task == 'approve' );
				break;
			case 'up':
			case 'down':
				$this->authorise( 'edit' );
				$r = true;
				$this->singleReorder( $this->_task == 'up' );
				break;
			case 'clone':
				$this->authorise( 'edit' );
				$r = true;
				/* first check in the cloned entry */
				$this->checkIn( $this->_model->get( 'id' ) );
				$this->_model = null;
				Input::Set( 'entry_id', 0, 'post' );
				Input::Set( 'entry_state', 0, 'post' );
				$this->save( false, true );
				break;
			case 'saveWithRevision':
				$this->authorise( 'edit' );
				$this->save( true );
				break;
			case 'reorder':
				$this->authorise( 'edit' );
				$r = true;
				$this->reorder();
				break;
			case 'reject':
				$this->authorise( 'approve' );
				$r = true;
				$this->reject();
				break;
			case 'revisions':
				$r = true;
				$this->revisions();
				break;
			case 'deleteHistory':
				$r = true;
				$this->deleteHistory();
				break;
			case 'search':
				$this->search();
				break;
			case 'deleteAll':
				$this->deleteAll();
				break;
			default:
				/* case plugin didn't register this task, it was an error */
				if ( !parent::execute() ) {
					Sobi::Error( $this->name(), SPLang::e( 'SUCH_TASK_NOT_FOUND', Input::Task() ), SPC::NOTICE, 404, __LINE__, __FILE__ );
				}
				else {
					$r = true;
				}
				break;
		}

		return $r;
	}

	/**
	 * Compares two data and shows result in a modal window.
	 * Ajax.
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function revisions()
	{
		$revision = SPFactory::history()->getRevision( Input::Cmd( 'revision' ) );
		$sid = Input::Sid();
		$fid = Input::Cmd( 'fid' );

		/* Fields data */
		if ( strstr( $fid, 'field_' ) ) {
			$fid = Factory::Db()
				->select( 'fid', 'spdb_field', [ 'nid' => $fid, 'section' => Sobi::Section(), 'adminField>' => -1 ] )
				->loadResult();

			/** @var SPField $field */
			$field = SPFactory::Model( 'field' );
			$field->init( $fid );
			$field->loadData( $sid );

			if ( isset( $revision[ 'changes' ][ 'fields' ][ $field->get( 'nid' ) ] ) ) {
				$revision = $revision[ 'changes' ][ 'fields' ][ $field->get( 'nid' ) ];
				try {
					$revision = $field->getData( $revision );
				}
				catch ( SPException $x ) {
				}
			}
			else {
				$revision = "";
			}
			/* get the formatted data for the field */
			try {
				$current = $field->getData();
			}
			catch ( SPException $x ) {
				$current = $field->data();
			}

			if ( !( is_array( $current ) ) ) {
				try {
					$current = SPConfig::unserialize( $current );
				}
				catch ( SPException $x ) {
				}
			}
			if ( !( is_array( $revision ) ) ) {
				try {
					$revision = SPConfig::unserialize( $revision );
				}
				catch ( SPException $x ) {
				}
			}
			try {
				$data = $field->compareRevisions( $revision, $current );
			}
			catch ( SPException $x ) {
				if ( is_array( $current ) ) {
					$current = print_r( $current, true );
				}
				if ( is_array( $revision ) ) {
					$revision = print_r( $revision, true );
				}
				$data = [
					'current'  => $current,
					'revision' => $revision,
				];
			}
		}

		/* Core data */
		else {
			$i = str_replace( 'entry.', C::ES, $fid );
			if ( isset( $revision[ 'changes' ][ $i ] ) ) {
				$revision = $revision[ 'changes' ][ $i ];
			}
			else {
				$revision = "";
			}
			switch ( $i ) {
				case 'owner':
				case 'updater':
					$currentUser = null;
					$pastUser = null;
					if ( $this->_model->get( $i ) ) {
						$currentUser = SPUser::getBaseData( ( int ) $this->_model->get( $i ) );
						$currentUser = $currentUser->name . ' (' . $currentUser->id . ')';
					}
					if ( $revision ) {
						$pastUser = SPUser::getBaseData( ( int ) $revision );
						$pastUser = $pastUser->name . ' (' . $pastUser->id . ')';
					}
					$data = [
						'current'  => $currentUser,
						'revision' => $pastUser,
					];
					break;
				case 'validSince':
				case 'validUntil':
				case 'createdTime':
				case 'updatedTime':
					$revision = SPFactory::config()->date( $revision, 'date.publishing_format' );
					$current = SPFactory::config()->date( $this->_model->get( $i ), 'date.publishing_format' );
					$data = [
						'current'  => $current,
						'revision' => $revision,
					];
					break;
				case 'state':
				case 'approved':
					$data = [
						'current'  => $this->_model->get( $i ) . ( $this->_model->get( $i ) ? ' (' . Sobi::Txt( 'YES_NO_GLOBAL_YES' ) . ')' : ' (' . Sobi::Txt( 'YES_NO_GLOBAL_NO' ) . ')' ),
						'revision' => $revision . ( $revision ? ' (' . Sobi::Txt( 'YES_NO_GLOBAL_YES' ) . ')' : ' (' . Sobi::Txt( 'YES_NO_GLOBAL_NO' ) . ')' ),
					];
					break;
				default:
					$data = [
						'current'  => $this->_model->get( $i ),
						'revision' => $revision,
					];
					break;
			}
		}
		if ( !( Input::Bool( 'html', 'post', false ) ) ) {
			$data = [
				'current'  => html_entity_decode( strip_tags( $data[ 'current' ] ), ENT_QUOTES, 'UTF-8' ),
				'revision' => html_entity_decode( strip_tags( $data[ 'revision' ] ), ENT_QUOTES, 'UTF-8' ),
			];

		}
		$data = [
			'current'  => explode( "\n", $data[ 'current' ] ),
			'revision' => explode( "\n", $data[ 'revision' ] ),
		];

		$diff = SPFactory::Instance( 'services.thirdparty.diff.lib.Diff', $data[ 'revision' ], $data[ 'current' ] );
		$renderer = SPFactory::Instance( 'services.thirdparty.diff.lib.Diff.Renderer.Html.SideBySide' );
		$difference = $diff->Render( $renderer );
		$data[ 'diff' ] = $difference;

		SPFactory::mainframe()
			->cleanBuffer()
			->customHeader();
		echo json_encode( $data );

		exit;
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function reject()
	{
		if ( !SPFactory::mainframe()->checkToken() ) {
			Sobi::Error( 'Token', SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ), C::ERROR, 403, __LINE__, __FILE__ );
		}
		if ( $this->authorise( 'manage' ) ) {
//			$changes = [];
			$objects = [
				'entry'  => $this->_model,
				'user'   => SPFactory::user(),
				'author' => SPFactory::Instance( 'cms.base.user', $this->_model->get( 'owner' ) ),
			];
			$messages = SPFactory::registry()->get( 'messages' );
			$reason = SPLang::replacePlaceHolders( Input::Html( 'reason', 'post', C::ES ), $objects );
			$objects[ 'reason' ] = nl2br( $reason );
			$messages[ 'rejection' ] = $objects;
			SPFactory::registry()->set( 'messages', $messages );
			$this->_model->setMessage( $reason, 'reason' );
			SPFactory::history()->logAction( SPC::LOG_REJECT,
				$this->_model->get( 'id' ),
				$this->_model->get( 'section' ),
				'entry',
				$reason,
				[ 'name' => $this->_model->get( 'name' ) ]
			);

			if ( Input::Bool( 'unpublish', 'post', false ) ) {
				$this->_model->changeState( 0, $reason, false );
//				$changes[] = 'unpublish';
				SPFactory::history()->logAction( SPC::LOG_UNPUBLISH,
					$this->_model->get( 'id' ),
					$this->_model->get( 'section' ),
					'entry',
					Sobi::Txt( 'EN.REJECT_HISTORY' ),
					[ 'name' => $this->_model->get( 'name' ) ]
				);
			}
			if ( Input::Bool( 'trigger_unpublish', 'post', false ) ) {
				Sobi::Trigger( 'Entry', 'AfterChangeState', [ $this->_model, 0, 'messages' => $this->_model->get( 'messages' ) ] );
			}
			if ( Input::Bool( 'discard', 'post', false ) ) {
//				$changes[] = 'discard';
				$data = $this->_model->discard( false );
				SPFactory::history()->logAction( SPC::LOG_DISCARD,
					$this->_model->get( 'id' ),
					$this->_model->get( 'section' ),
					'entry',
					Sobi::Txt( 'EN.REJECT_HISTORY' ),
					[ 'name' => $this->_model->get( 'name' ) ]
				);
			}
			if ( Input::Bool( 'trigger_unapprove', 'post', false ) ) {
				Sobi::Trigger( 'Entry', 'AfterUnapprove', [ $this->_model, 0 ] );
			}
			Sobi::Trigger( 'Entry', 'AfterReject', [ $this->_model, 0 ] );
			$this->response( Sobi::Back(), Sobi::Txt( 'ENTRY_REJECTED', $this->_model->get( 'name' ) ), true, C::SUCCESS_MSG );
		}
	}

	/**
	 * Ajax method.
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function search()
	{
		$term = Input::String( 'search', 'post', C::ES );
		$fid = Sobi::Cfg( 'entry.name_field' );

		/** @var SPField $field */
		$field = SPFactory::Model( 'field' );
		$field->init( $fid );

		$s = Sobi::Section();
		$data = $field->searchSuggest( $term, $s, true, true );
		SPFactory::mainframe()->cleanBuffer();
		echo json_encode( $data );

		exit;
	}

	/**
	 * @param false $apply
	 * @param false $clone
	 *
	 * @throws \ReflectionException
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function save( $apply = false, $clone = false )
	{
		if ( !SPFactory::mainframe()->checkToken() ) {
			Sobi::Error( 'Token', SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ), C::ERROR, 403, __LINE__, __FILE__ );
		}
		$sets = [];
		$sid = !$clone ? ( Input::Sid() ? Input::Sid() : Input::Int( 'entry_id' ) ) : 0;
		$apply = ( int ) $apply;
		if ( !$this->_model ) {
			$this->setModel( SPLoader::loadModel( $this->_type ) );
		}
		$this->_model->init( $sid );

		$tplPackage = Sobi::Cfg( 'section.template', SPC::DEFAULT_TEMPLATE );
		$this->tplCfg( $tplPackage, Input::Task() );
		$customClass = null;
		if ( isset( $this->_tCfg[ 'general' ][ 'functions' ] ) && $this->_tCfg[ 'general' ][ 'functions' ] ) {
			$customClass = SPLoader::loadClass( '/' . str_replace( '.php', C::ES, $this->_tCfg[ 'general' ][ 'functions' ] ), false, 'templates' );
			if ( method_exists( $customClass, 'BeforeStoreEntry' ) ) {
				$customClass::BeforeStoreEntry( $this->_model, $_POST );
			}
		}

		$preState = [
			'approved' => $this->_model->get( 'approved' ),
			'state'    => $this->_model->get( 'state' ),
			'new'      => !( $this->_model->get( 'id' ) ),
		];
		SPFactory::registry()->set( 'object_previous_state', $preState );

		$this->_model->getRequest( $this->_type );
		$this->authorise( $this->_model->get( 'id' ) ? 'edit' : 'add' );

		/* Check the input data */
		try {
			$this->_model->validate( 'post', $clone );
		}
		catch ( SPException $x ) {
			$back = Sobi::GetUserState( 'back_url', Sobi::Url( [ 'task' => 'entry.add', 'sid' => Sobi::Section() ] ) );
			$data = $x->getData();
			$this->response( $back, $x->getMessage(), false, 'error', [ 'required' => $data[ 'messages' ] ] );
		}

		/* Save the data */
		try {
			$this->_model->save();
		}
		catch ( SPException $x ) {
			$back = Sobi::GetUserState( 'back_url', Sobi::Url( [ 'task' => 'entry.add', 'sid' => Sobi::Section() ] ) );
			$this->response( $back, $x->getMessage(), false, 'error' );
		}

		/* 26 September 2020, Sigrid: Also from backend the payments have to be stored. Necessary for the Expiration app (Scenario 1).
		Scenario 1: admin adds an option for the user free of charge, but when renewing it has to be paid. This would not work if the paid fields
		are not stored in the payments table even if for free the first time.
		Scenario 2: admin adds an option for the user free of charge, when the user edit his entry he still does not need to pay for that option
		and the option should not be removed.
		This also would not work correctly if the paid fields are not stored in the payments table. */
		$pCount = SPFactory::payment()->count( $this->_model->get( 'id' ) );
		if ( $pCount ) {
			if ( $customClass && method_exists( $customClass, 'BeforeStoreEntryPayment' ) ) {
				$customClass::BeforeStoreEntryPayment( $this->_model->get( 'id' ) );
			}
			SPFactory::payment()->store( $this->_model->get( 'id' ) );
		}

		$sid = $this->_model->get( 'id' );
		$sets[ 'sid' ] = $sid;
		$sets[ 'entry.nid' ] = $this->_model->get( 'nid' );
		$sets[ 'entry.id' ] = $sid;

		if ( $customClass && method_exists( $customClass, 'AfterStoreEntry' ) ) {
			$customClass::AfterStoreEntry( $this->_model );
		}

		if ( Input::String( 'history-note' ) || $this->_task == 'saveWithRevision' || Sobi::Cfg( 'entry.versioningAdminBehaviour', 1 ) ) {
			$this->logChanges( SPC::LOG_SAVE, $preState[ 'new' ] ? Sobi::Txt( 'HISTORY.INITIALVERSION' ) : Input::String( 'history-note' ) );
		}
		if ( $apply || $clone ) {
			if ( $clone ) {
				$msg = Sobi::Txt( 'MSG.OBJ_CLONED_ENTRY' );
				$this->response( Sobi::Url( [ 'task' => $this->_type . '.edit', 'sid' => $sid ] ), $msg );
			}
			else {
				$msg = Sobi::Txt( 'MSG.OBJ_SAVED_ENTRY' );
				$this->response( Sobi::Url( [ 'task' => $this->_type . '.edit', 'sid' => $sid ] ), $msg, true, 'success', [ 'sets' => $sets ] );
			}
		}
		elseif ( $this->_task == 'saveAndNew' ) {
			$msg = Sobi::Txt( 'MSG.ALL_CHANGES_SAVED' );
			$sid = $this->_model->get( 'parent' );
			if ( !$sid ) {
				$sid = Sobi::Section();
			}
			$this->response( Sobi::Url( [ 'task' => $this->_type . '.add', 'sid' => $sid ] ), $msg, true, 'success', [ 'sets' => $sets ] );

		}
		else {
			$this->response( Sobi::Back(), Sobi::Txt( 'MSG.OBJ_SAVED', [ 'type' => Sobi::Txt( strtoupper( $this->_model->get( 'oType' ) ) ) ] ) );
		}
	}

	/**
	 * Deletes all entries in a section (purge).
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function deleteAll()
	{
		SPFactory::mainframe()->checkToken();
		$count = Input::Int( 'counter' );
		$eid = Input::Eid();
		if ( !$eid ) {
			if ( !$count ) {
				$entries = SPFactory::Section( Sobi::Section() )->getChilds( 'entry', true, 0, false );
				$count = count( $entries );
				$store = json_encode( $entries );
				// storing it in JSON file so the Ajax needs to only deliver an index and not hold the complete array.
				FileSystem::Write( SOBI_PATH . '/var/tmp/' . Input::Cmd( 'spsid' ) . '.json', $store );
			}
			else {
				$entries = json_decode( FileSystem::Read( SOBI_PATH . '/var/tmp/' . Input::Cmd( 'spsid' ) . '.json' ) );
			}
			$this->response( Sobi::Back(), Sobi::Txt( 'DELETE_ENTRIES_COUNT', $count ), false, C::SUCCESS_MSG, [ 'counter' => $count, 'entries' => $entries ] );
		}
		$entry = SPFactory::Entry( $eid );
		$entry->delete();
		SPFactory::cache()->purgeSectionVars();
		$this->response( Sobi::Back(), Sobi::Txt( 'DELETED_ENTRY', $entry->get( 'name' ) ), false, C::SUCCESS_MSG );
	}

	/**
	 * @param $approve
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function approval( $approve )
	{
		$sids = Input::Arr( 'e_sid', 'request', [] );
		if ( !count( $sids ) ) {
			if ( $this->_model->get( 'id' ) ) {
				$sids = [ $this->_model->get( 'id' ) ];
			}
			else {
				$sids = [];
			}
		}
		if ( !( count( $sids ) ) ) {
			$this->response( Sobi::Back(), Sobi::Txt( 'CHANGE_NO_ID' ), false, SPC::ERROR_MSG );
		}
		else {
			foreach ( $sids as $sid ) {
				try {
					Factory::Db()->update( 'spdb_object', [ 'approved' => $approve ? 1 : 0 ], [ 'id' => $sid, 'oType' => 'entry' ] );
					$entry = SPFactory::Entry( $sid );
					if ( $approve ) {
						$entry->approveFields( $approve );
					}
					else {
						SPFactory::cache()->deleteObj( 'entry', $sid );
					}
				}
				catch ( SPException $x ) {
					Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), SPC::WARNING, 0, __LINE__, __FILE__ );
				}
			}
			SPFactory::history()->logAction( $approve ? SPC::LOG_APPROVE : SPC::LOG_UNAPPROVE,
				$this->_model->get( 'id' ),
				$this->_model->get( 'section' ),
				$this->type(),
				C::ES,
				[ 'name' => $this->_model->get( 'name' ) ]
			);

			SPFactory::cache()->purgeSectionVars();
			$this->response( Sobi::Back(), Sobi::Txt( $approve ? 'EMN.APPROVED' : 'EMN.UNAPPROVED', $entry->get( 'name' ) ), false, SPC::SUCCESS_MSG );
		}
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function reorder()
	{
		$db = Factory::Db();
		$sids = Input::Arr( 'ep_sid', 'request', [] );
		/* re-order it to the valid ordering */
		$order = [];
		asort( $sids );
		$eLimStart = Input::Int( 'eLimStart', 'request', 0 );
		$eLimit = Sobi::GetUserState( 'adm.entries.limit', 'elimit', Sobi::Cfg( 'adm_list.entries_limit', 25 ) );
		$LimStart = $eLimStart ? ( ( $eLimStart - 1 ) * $eLimit ) : $eLimStart;

		if ( count( $sids ) ) {
			$c = 0;
			foreach ( $sids as $sid => $pos ) {
				$order[ ++$c ] = $sid;
			}
		}
		$pid = Input::Sid();
		foreach ( $order as $sid ) {
			try {
				$db->update( 'spdb_relations', [ 'position' => ++$LimStart ], [ 'id' => $sid, 'oType' => 'entry', 'pid' => $pid ] );
				SPFactory::cache()->deleteObj( 'entry', $sid );
			}
			catch ( SPException $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), SPC::WARNING, 0, __LINE__, __FILE__ );
			}
		}
		$this->response( Sobi::Back(), Sobi::Txt( 'EMN.REORDERED' ), true, SPC::SUCCESS_MSG );
	}

	/**
	 * @param $up
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function singleReorder( $up )
	{
		$db = Factory::Db();
		$eq = $up ? '<' : '>';
		$dir = $up ? 'position.desc' : 'position.asc';
		$current = $this->_model->getPosition( Input::Pid() );
		try {
			$db->select( 'position, id', 'spdb_relations', [ 'position' . $eq => $current, 'oType' => 'entry', 'pid' => Input::Pid() ], $dir, 1 );
			$interchange = $db->loadAssocList();
			if ( $interchange && count( $interchange ) ) {
				$db->update( 'spdb_relations', [ 'position' => $interchange[ 0 ][ 'position' ] ], [ 'oType' => 'entry', 'pid' => Input::Pid(), 'id' => $this->_model->get( 'id' ) ], 1 );
				$db->update( 'spdb_relations', [ 'position' => $current ], [ 'oType' => 'entry', 'pid' => Input::Pid(), 'id' => $interchange[ 0 ][ 'id' ] ], 1 );
			}
			else {
				$current = $up ? --$current : ++$current;
				$db->update( 'spdb_relations', [ 'position' => $current ], [ 'oType' => 'entry', 'pid' => Input::Pid(), 'id' => $this->_model->get( 'id' ) ], 1 );
			}
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), SPC::WARNING, 500, __LINE__, __FILE__ );
		}
		$this->response( Sobi::Back(), Sobi::Txt( 'ENTRY_POSITION_CHANGED' ), true, SPC::SUCCESS_MSG );
	}

	/**
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function deleteHistory()
	{
		$sid = Input::Sid();
		if ( $sid ) {
			SPFactory::history()->deleteAllRevisions( $sid );
		}
		$this->response( Sobi::Url( [ 'task' => $this->_type . '.edit', 'sid' => $sid, 'pid' => Input::Pid() ] ), Sobi::Txt( 'MSG.HISTORY_DELETED' ), true, 'success' );
	}

	/**
	 * @throws \ReflectionException
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function editForm()
	{
		$sid = Input::Pid();
		$sid = $sid ? : Input::Sid();

		/** @var SPAdmView $view */
		$view = SPFactory::View( 'entry', true );
		$this->checkTranslation();
		/* if adding new */
		if ( !$this->_model ) {
			$this->setModel( SPLoader::loadModel( 'entry' ) );
		}
		$id = $this->_model->get( 'id' );
		if ( !$id ) {
			$this->_model->set( 'state', 1 );
			$this->_model->set( 'approved', 1 );
		}
		else {
			$languages = $view->languages();
			$view->assign( $languages, 'languages-list' );
			$multiLang = Sobi::Cfg( 'lang.multimode', false );
			$view->assign( $multiLang, 'multilingual' );
		}
		$this->_model->loadFields( Sobi::Section(), true );
		$this->_model->formatDatesToEdit();

		if ( $this->_model->isCheckedOut() ) {
			SPFactory::message()->error( Sobi::Txt( 'EN.IS_CHECKED_OUT', $this->_model->get( 'name' ) ), false );
		}
		else {
			/* check out the model */
			$this->_model->checkOut();
		}
		/* get fields for this section */
		/* @var SPEntry $this - >_model */
		$fields = $this->_model->get( 'fields', [] );
		if ( !count( $fields ) ) {
			throw new SPException( SPLang::e( 'CANNOT_GET_FIELDS_IN_SECTION', Sobi::Reg( 'current_section' ) ) );
		}

		/* Loading of History Entry Data */
		$revisionChange = false;
		$rev = Input::Cmd( 'revision' );
		$revisionsDelta = [];
		if ( $rev ) {
			$revision = SPFactory::history()->getRevision( Input::Cmd( 'revision' ) );
			if ( isset( $revision[ 'changes' ] ) && count( $revision[ 'changes' ] ) ) {
				$formatted = gmdate( Sobi::Cfg( 'date.publishing_format', SPC::DEFAULT_DATE ), strtotime( $revision[ 'changedAt' ] ) + SPFactory::config()->getTimeOffset() );
				SPFactory::message()->warning( Sobi::Txt( 'HISTORY_REVISION_WARNING', $formatted ), false );
				foreach ( $fields as $i => $field ) {
					if ( ( $field->get( 'enabled' ) ) && $field->enabled( 'form' ) && !( $field->get( 'type' ) == 'info' ) ) {
						if ( isset( $revision[ 'changes' ][ 'fields' ][ $field->get( 'nid' ) ] ) ) {
							$revisionData = $revision[ 'changes' ][ 'fields' ][ $field->get( 'nid' ) ];
							try {
								$revisionData = $field->getData( $revisionData );
							}
							catch ( SPException $x ) {
							}
						}
						else {
							$revisionData = null;
						}
						try {
							$currentData = $field->getData();
						}
						catch ( SPException $x ) {
							$currentData = $field->data();
						}

						if ( is_array( $revisionData ) && !( is_array( $currentData ) ) ) {
							try {
								$currentData = SPConfig::unserialize( $currentData );
							}
							catch ( SPException $x ) {
							}
						}

						/* compare both versions to show changes icon */
						if ( $revisionData || $currentData ) {
							if ( md5( serialize( $currentData ) ) != md5( serialize( $revisionData ) ) ) {
								$field->revisionChanged()->setRawData( $revisionData );
							}
						}
						$fields[ $i ] = $field;
					}
				}
				unset( $revision[ 'changes' ][ 'fields' ] );
				foreach ( $revision[ 'changes' ] as $attr => $value ) {
					if ( $value != $this->_model->get( $attr ) ) {
						$revisionsDelta[ $attr ] = $value;
						$this->_model->setRevData( $attr, $value );
					}
				}
				$revisionChange = true;
			}
			else {
				SPFactory::message()
					->error( Sobi::Txt( 'HISTORY_REVISION_NOT_FOUND' ), false )
					->setSystemMessage();
			}
		}

		/* Delete a revision */
		$revDelete = Input::Cmd( 'delete' );
		if ( $revDelete ) {
			SPFactory::history()->deleteRevision( $revDelete );
			$revDelete = '';
		}

		$f = [];
		foreach ( $fields as $field ) {
			if ( ( $field->get( 'enabled' ) ) && $field->enabled( 'form' ) ) {
				$f[] = $field;
			}
		}

		/* create the validation script to check if required fields are filled in and the filters, if any, match */
		$this->createValidationScript( $fields );
		$view->assign( $this->_model, 'entry' );

		//header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
		/*		header('Cache-Control: no-store, no-cache, must-revalidate');
				header('Cache-Control: post-check=0, pre-check=0', FALSE);
				header('Pragma: no-cache');*/

		/* History items for the entry */
		$history = [];
		$historybehaviour = (int) Sobi::Cfg( 'entry.versioningAdminBehaviour', 1 );
		$historyon = Sobi::Cfg( 'entry.versioning', true );
		$historylog = !$historybehaviour && $historyon;

		if ( $historyon ) {
			$messages = SPFactory::history()->getHistory( $id, 'entry' );
			if ( count( $messages ) ) {
				foreach ( $messages as $message ) {
					$message[ 'changeAction' ] = Sobi::Txt( 'HISTORY_CHANGE_TYPE_' . str_replace( '-', '_', strtoupper( $message[ 'changeAction' ] ) ) );
					$message[ 'site' ] = Sobi::Txt( 'HISTORY_SENTENCE_AREA_' . strtoupper( $message[ 'site' ] ) );
					$message[ 'changedAt' ] = gmdate( Sobi::Cfg( 'date.publishing_format', SPC::DEFAULT_DATE ), strtotime( $message[ 'changedAt' ] ) + SPFactory::config()->getTimeOffset() );
					if ( strlen( $message[ 'reason' ] ) ) {
						$message[ 'status' ] = 1;
					}
					else {
						$message[ 'status' ] = 0;
					}
					$history[] = $message;
				}
			}
		}
		else {
			$historybehaviour = 1;
		}

		$labelwidth = Sobi::Cfg( 'admintemplate.entry_labelwidth', 3 );
		$reg = Sobi::Reg( 'current_section' );

		if ( $this->_model ) {
			if ( !$this->_model->get( 'valid' ) ) {
				$status = $this->_model->get( 'parentPathSet' ) ? Sobi::Txt( 'EN.SET_CAT_NO_CATEGORY' ) : Sobi::Txt( 'EN.SET_CAT_NO_SECTION' );
				$view->assign( $status, 'status' );
			}
		}

		$view
			->assign( $this->_task, 'task' )
			->assign( $f, 'fields' )
			->assign( $labelwidth, 'labelwidth' )
			->assign( $id, 'id' )
			->assign( $history, 'history' )
			->assign( $revisionChange, 'revision-change' )
			->assign( $revisionsDelta, 'revision' )
			->assign( $historybehaviour, 'history-behaviour' )
			->assign( $historylog, 'history-log' )
			->assign( $historyon, 'history-on' )
			->assign( $reg, 'sid' )
			->addHidden( $rev, 'revision' )
			->addHidden( $revDelete, 'delete' )
			->addHidden( $sid, 'pid' );

		Sobi::Trigger( 'Entry', 'AdmView', [ &$view ] );

		$view->determineTemplate( 'entry', 'edit' );
		$view->display();
	}
}
