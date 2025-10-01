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
 * @modified 20 May 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadController( 'controller' );

use Sobi\C;
use Sobi\Input\Cookie;
use Sobi\Input\Input;
use Sobi\FileSystem\FileSystem;
use Sobi\Lib\Factory;

/**
 * Class SPEntryCtrl
 */
class SPEntryCtrl extends SPController
{
	/** @var string */
	protected $_type = 'entry';
	/** @var string */
	protected $_defTask = 'details';
	/** @var array */
	protected $request = [];

	/**
	 * @return bool
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 * @throws \Exception
	 */
	public function execute()
	{
		$retval = false;
		Input::Set( 'task', $this->_type . '.' . $this->_task );
		switch ( $this->_task ) {
			case 'edit':
			case 'add':
				Sobi::ReturnPoint();
				SPLoader::loadClass( 'html.input' );
				$this->editForm();
				break;
			case 'approve':
			case 'unapprove':
				$retval = true;
				$this->approve( $this->_task == 'approve' );
				break;
			case 'publish':
			case 'unpublish':
			case 'hide':
				$retval = true;
				$this->state( $this->_task == 'publish' );
				break;
			case 'submit':
				$this->submit();
				break;
			case 'details':
				$this->visible();
				$this->details();
				Sobi::ReturnPoint();
				break;
			case 'payment':
				$this->payment();
				break;
			default:
				if ( !parent::execute() ) {
					Sobi::Error( 'entry_ctrl', SPLang::e( 'TASK_NOT_FOUND' ), C::NOTICE, 404, __LINE__, $this->name() );
				}
				else {
					$retval = true;
				}
				break;
		}

		return $retval;
	}

	/**
	 * Approves or un-approves an entry.
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function approve( $approve )
	{
		if ( $this->_model->isCheckedOut() ) {
			Sobi::Redirect( Sobi::Back(), Sobi::Txt( 'EN.IS_CHECKED_OUT', $this->_model->get( 'name' ) ), C::ERROR_MSG, true );
		}
		if ( ( ( $this->_model->get( 'owner' ) == Sobi::My( 'id' ) ) && Sobi::Can( 'entry.manage.own' ) ) || Sobi::Can( 'entry.manage.*' ) ) {
			try {
				Factory::Db()->update( 'spdb_object', [ 'approved' => $approve ? 1 : 0 ], [ 'id' => $this->_model->get( 'id' ), 'oType' => 'entry' ] );
				if ( $approve ) {
					$this->_model->approveFields( $approve );
				}
				else {
					SPFactory::cache()->deleteObj( 'entry', $this->_model->get( 'id' ) );
				}
			}
			catch ( SPException $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
			}
			Sobi::Trigger( $this->name(), __FUNCTION__, [ &$this->_model ] );
			SPFactory::history()->logAction( $approve ? SPC::LOG_APPROVE : SPC::LOG_UNAPPROVE,
				$this->_model->get( 'id' ),
				$this->_model->get( 'section' ),
				$this->type(),
				C::ES,
				[ 'name' => $this->_model->get( 'name' ) ]
			);
//			$this->response( Sobi::Url( [ 'sid' => $this->_model->get( 'id' ) ] ), Sobi::Txt( $approve ? 'EMN.APPROVED' : 'EMN.UNAPPROVED', $this->_model->get( 'name' ) ), false, C::SUCCESS_MSG );
			$this->response( Sobi::Back(), Sobi::Txt( $approve ? 'EMN.APPROVED' : 'EMN.UNAPPROVED', $this->_model->get( 'name' ) ), false, C::SUCCESS_MSG );
		}
		else {
			Sobi::Error( 'entry', SPLang::e( 'UNAUTHORIZED_ACCESS' ), C::ERROR, 403, __LINE__, __FILE__ );
		}
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
		parent::checkIn( Input::Int( 'entry_id', 'request', $sid ), $redirect );
	}

	/**
	 * Publishes or un-publishes an entry.
	 *
	 * @param $state
	 *
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function state( $state )
	{
		if ( $this->_model->get( 'id' ) ) {
			if ( $this->_model->isCheckedOut() ) {
				$this->response( Sobi::Back(), Sobi::Txt( 'EN.IS_CHECKED_OUT', $this->_model->get( 'name' ) ), false, C::WARN_MSG );
			}
			if ( ( ( $this->_model->get( 'owner' ) == Sobi::My( 'id' ) ) && Sobi::Can( 'entry.publish.own' ) ) || Sobi::Can( 'entry.publish.*' ) ) {
				$this->_model->changeState( $state );
				SPFactory::history()->logAction( $state ? SPC::LOG_PUBLISH : SPC::LOG_UNPUBLISH,
					$this->_model->get( 'id' ),
					$this->_model->get( 'section' ),
					$this->type(),
					C::ES,
					[ 'name' => $this->_model->get( 'name' ) ]
				);
				$this->response( Sobi::Back(), Sobi::Txt( $state ? 'EMN.PUBLISHED' : 'EMN.UNPUBLISHED', $this->_model->get( 'name' ) ) . ' ', false, C::SUCCESS_MSG );
			}
			else {
				Sobi::Error( 'entry', SPLang::e( 'UNAUTHORIZED_ACCESS' ), C::ERROR, 403, __LINE__, __FILE__ );
			}
		}
		else {
			$this->response( Sobi::Back(), Sobi::Txt( 'CHANGE_NO_ID' ), false, C::ERROR_MSG );
		}
	}

	/**
	 * Checks an entry with its fields before saving it.
	 * Called from frontend only (backend => validate())
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 * @throws \Exception
	 */
	protected function submit()
	{
		/* check security token */
		if ( !Factory::Application()->checkToken() ) {
			Sobi::Error( 'Token', SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ), C::ERROR, 403, __LINE__, __FILE__ );
		}

		if ( !$this->_model ) {
			$this->setModel( SPLoader::loadModel( $this->_type ) );
		}
		else {
			if ( $this->_model->get( 'oType' ) != 'entry' ) {
				Sobi::Error( 'Entry', sprintf( 'Serious security violation. Trying to save an object which claims to be an entry, but it is a %s. Task was %s', $this->_model->get( 'oType' ), Input::Task() ), C::ERROR, 403, __LINE__, __FILE__ );
				exit;
			}
		}

		$ajax = Input::Cmd( 'method' ) == 'xhr';
		/* let's create a simple plug-in method from the template to allow modifying the request */
		$tplPackage = Sobi::Cfg( 'section.template', SPC::DEFAULT_TEMPLATE );

		$this->template();
		$this->tplCfg( $tplPackage );

		$customClass = null;
		if ( isset( $this->_tCfg[ 'general' ][ 'functions' ] ) && $this->_tCfg[ 'general' ][ 'functions' ] ) {
			$customClass = SPLoader::loadClass( '/' . str_replace( '.php', C::ES, $this->_tCfg[ 'general' ][ 'functions' ] ), false, 'templates' );
			if ( method_exists( $customClass, 'BeforeSubmitEntry' ) ) {
				$customClass::BeforeSubmitEntry( $this->_model, $this->_tCfg[ 'general' ] );
			}
		}

		$sid = $this->_model->get( 'id' );
		$this->_model->init( Input::Sid() );
		$this->_model->getRequest( $this->_type );
		Sobi::Trigger( $this->name(), __FUNCTION__, [ &$this->_model ] );

		if ( $sid ) {
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

		$this->_model->loadFields( Sobi::Reg( 'current_section' ) );
		$fields = $this->_model->get( 'fields' );
		$tsId = Input::String( 'SPro_editentry', 'cookie' );

		$tsIdToRequest = false;
		if ( !strlen( $tsId ) ) {
			$tsId = ( microtime( true ) * 100 ) . '.' . rand( 0, 99 ) . '.' . str_replace( [ ':', '.' ], C::ES, Input::Ip4() );
			SPLoader::loadClass( 'env.cookie' );
			Cookie::Set( 'editentry', $tsId, Cookie::Hours( 48 ) );
			/* check if we have set the cookie. in case we were not able for some reason to set the cookie, we are going to pass the tsId into the URL */
			$cookie_tsId = Input::String( 'SPro_editentry', 'cookie' );
			$tsIdToRequest = !( $cookie_tsId == $tsId );
		}

		$store = [];
		if ( count( $fields ) ) {
			$error = false;
			$new = !$this->_model->get( 'id' );
			foreach ( $fields as $field ) {
				if ( $field->enabled( 'form', $new ) ) {
					try {
						$request = $field->submit( $this->_model, $tsId );
						if ( is_array( $request ) && count( $request ) ) {
							/* get the data processed by the field */
							$store = array_merge( $store, $request );
						}
					}
					catch ( SPException $x ) {
						$error = true;
						$msgs[ $field->get( 'nid' ) ] = $x->getMessage();
//						$this->response( Sobi::Back(), $x->getMessage(), !( $ajax ), SPC::ERROR_MSG, [ 'error' => $field->get( 'nid' ) ] );
					}
				}
			}
			if ( $error ) {
				$this->response( Sobi::Back(), C::ES, !$ajax, C::ERROR_MSG, [ 'error' => $msgs, 'bs' => Sobi::Cfg( 'template.framework-style', C::BOOTSTRAP5 ) ] );
			}
		}

		/* save the data entered in request cache (edit-cache)*/
		/* try first to save it in Sobi cache */
		if ( Sobi::Cfg( 'cache.l3_enabled', true ) ) {
			SPFactory::cache()->addVar( [ 'post' => $_POST, 'files' => $_FILES, 'store' => $store ], 'request_cache_' . $tsId );
		}
		/* if Sobi cache is not available, save it in temporary cache files */
		else {
			$file = str_replace( '.', '-', $tsId );
			$buffer = SPConfig::serialize( $_POST );
			FileSystem::Write( SPLoader::path( 'tmp/edit/' . $file . '/post', 'front', false, 'var' ), $buffer );

			if ( $_FILES ) {
				$buffer = SPConfig::serialize( $_FILES );
				FileSystem::Write( SPLoader::path( 'tmp/edit/' . $file . '/files', 'front', false, 'var' ), $buffer );
			}
			$buffer = SPConfig::serialize( $store );
			FileSystem::Write( SPLoader::path( 'tmp/edit/' . $file . '/store', 'front', false, 'var' ), $buffer );
		}
		if ( !Sobi::Can( 'entry.payment.free' ) && SPFactory::payment()->count( $this->_model->get( 'id' ) ) ) {
			$this->paymentView( $tsId );
		}
		else {
			if ( $customClass && method_exists( $customClass, 'AfterSubmitEntry' ) ) {
				$customClass::AfterSubmitEntry( $this->_model );
			}
			$url = [ 'task' => 'entry.save', 'pid' => Sobi::Reg( 'current_section' ), 'sid' => $sid ];
			if ( $tsIdToRequest ) {
				$url[ 'ssid' ] = $tsId;
			}
			$this->response( Sobi::Url( $url, false, false ) );
		}
	}

	/**
	 * Gets the data from the request-cache and stores the post data in the edit-cache.
	 * The data are stored in the cache when user submits an entry.
	 * They are restored if the user comes back (later or from payment screen).
	 *
	 * @param string $tsId
	 * @param string $cache
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function getCache( string $tsId, string $cache = 'requestcache' )
	{
		/* get the data from the request cache (edit cache) */
		$request = SPFactory::cache()->getVar( 'request_cache_' . $tsId );

		/* do not merge data of different entries */
		if ( $request && $request[ 'post' ][ 'sid' ] != 0 && Input::Sid() != $request[ 'post' ][ 'sid' ] ) {
			$request[ 'post' ] = $request[ 'files' ] = $request[ 'store' ] = [];
		}
		$post = $store = $files = [];

		/* try from Sobi Cache first */
		if ( $request && isset( $request[ 'post' ] ) && isset( $request[ 'store' ] ) && isset( $request[ 'files' ] ) ) {
			$post = $request[ 'post' ];
			$files = $request[ 'files' ];
			$store = $request[ 'store' ];
		}

		/* in case the data are not stored in the cache, they are probably stored in temporary files */
		else {
			$file = str_replace( '.', '-', $tsId );
			if ( strlen( $file ) ) {
				$tempDir = SPLoader::dirPath( 'tmp/edit/' . $file );
				$postFile = SPLoader::path( $tempDir . '/post', 'front', true, 'var' );
				$filesFile = SPLoader::path( $tempDir . '/files', 'front', true, 'var' );
				$storeFile = SPLoader::path( $tempDir . '/store', 'front', true, 'var' );

				$post = $postFile ? SPConfig::unserialize( FileSystem::Read( $postFile ) ) : [];
				$files = $filesFile ? SPConfig::unserialize( FileSystem::Read( $filesFile ) ) : [];
				$store = $storeFile ? SPConfig::unserialize( FileSystem::Read( $storeFile ) ) : [];

				/* do not merge data of different entries */
				if ( isset( $post[ 'sid' ] ) && $post[ 'sid' ] != 0 && Input::Sid() != $post[ 'sid' ] ) {
					$post = $store = $files = [];
				}
			}
		}

		$this->request = [];
		if ( isset( $post ) && isset( $store ) && isset( $files ) ) {
			/* merge the files into post and store data (needed by some applications)*/
			if ( is_array( $files ) && count( $files ) ) {
				$post = array_merge( $post, $files );
				$store = array_merge( $store, $files );
			}

			$this->request[ 'post' ] = $post;
			$this->request[ 'store' ] = $store;
			$this->request[ 'files' ] = $files;

			SPFactory::registry()->set( $cache, $post );
			SPFactory::registry()->set( 'requestcache_stored', $store );

			/* sets the fields data to the $_REQUEST) */
			$this->setFieldRequestData( $store );
		}
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception|\DOMException
	 */
	private function payment()
	{
		$sid = Input::Sid();
		$data = SPFactory::cache()->getObj( 'payment', $sid, (int) Sobi::Section(), true );
		if ( !$data ) {
			$tsId = Input::String( 'tsid' );
			$tfile = SPLoader::path( 'tmp/edit/' . $tsId . '/payment', 'front', false, 'var' );
			if ( FileSystem::Exists( $tfile ) ) {
				$data = SPConfig::unserialize( FileSystem::Read( $tfile ) );
			}
		}
		if ( !$data ) {
			Sobi::Error( 'payment', SPLang::e( 'Session expired' ), C::ERROR, 500, __LINE__, __FILE__ );
		}
		if ( $data[ 'ident' ] != Input::String( 'SPro_payment_' . $sid, 'cookie' ) ) {
			Sobi::Error( 'payment', SPLang::e( 'UNAUTHORIZED_ACCESS' ), C::ERROR, 403, __LINE__, __FILE__ );
		}
		$this->paymentView( C::ES, $data[ 'data' ] );
	}

	/**
	 * @param string $tsId
	 * @param array $data
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 * @throws \DOMException
	 */
	private function paymentView( string $tsId = C::ES, array $data = [] )
	{
		/* determine template package */
		$tplPackage = Sobi::Cfg( 'section.template', SPC::DEFAULT_TEMPLATE );
		/* load template config */
		$this->tplCfg( $tplPackage );

		$customClass = null;
		if ( isset( $this->_tCfg[ 'general' ][ 'functions' ] ) && $this->_tCfg[ 'general' ][ 'functions' ] ) {
			$customClass = SPLoader::loadClass( '/' . str_replace( '.php', C::ES, $this->_tCfg[ 'general' ][ 'functions' ] ), false, 'templates' );
			if ( method_exists( $customClass, 'BeforePaymentView' ) ) {
				$customClass::BeforePaymentView( $data );
			}
		}
		SPFactory::mainframe()->addObjToPathway( $this->_model );
		$visitor = SPUser::getCurrent();

		/** @var SPPaymentView $view */
		$view = SPFactory::View( 'payment', $this->template );
		$view
			->assign( $this->_model, 'entry' )
			->assign( $data, 'pdata' )
			->assign( $visitor, 'visitor' )
			->assign( $this->_task, 'task' )
			->addHidden( $tsId, 'speditentry' )
			->addHidden( $tsId, 'ssid' );

		$view
			->setConfig( $this->_tCfg, $this->_task )
			->setTemplate( $tplPackage . '.payment.' . $this->_task );

		Sobi::Trigger( ucfirst( $this->_task ), $this->name(), [ &$view, &$this->_model ] );

//		$buffering = ini_get( 'output_buffering' );
		if ( Input::Cmd( 'method', 'post' ) == 'xhr' ) {
			SPFactory::mainframe()->cleanBuffer();
			ob_start();     /* start output buffering in case it was set to no value */
			$view->display();
			$response = ob_get_contents();

			$this->response( Sobi::Back(), $response, false, C::INFO_MSG );
		}
		else {
			$view->display();
		}
		if ( $customClass && method_exists( $customClass, 'AfterPaymentView' ) ) {
			$customClass::AfterPaymentView();
		}
	}

	/**
	 * @param bool $apply
	 * @param bool $clone
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 * @throws \Exception
	 */
	protected function save( $apply = false, $clone = false )
	{
		$new = true;
		if ( !$this->_model ) {
			$this->setModel( SPLoader::loadModel( $this->_type ) );
		}
		if ( $this->_model->get( 'oType' ) != 'entry' ) {
			Sobi::Error( 'Entry', sprintf( 'Serious security violation. Trying to save an object which claims to be an entry, but it is a %s. Task was %s', $this->_model->get( 'oType' ), Input::Task() ), C::ERROR, 403, __LINE__, __FILE__ );

			exit;
		}

		/* check if we have stored last edit in cache */
		$tsId = Input::String( 'SPro_editentry', 'cookie' );
		if ( !$tsId ) {
			$tsId = Input::Cmd( 'ssid' );
		}
		// Bug #66
		// 1.4.3
//		$request = $this->getCache( $tsId );

		// 1.4.6
		/* get the data from last edit from the cache */
		$this->getCache( $tsId );

		$this->_model->init( Input::Sid() );

		$tplPackage = Sobi::Cfg( 'section.template', SPC::DEFAULT_TEMPLATE );
		$this->template();
		$this->tplCfg( $tplPackage );

		$customClass = null;
		if ( isset( $this->_tCfg[ 'general' ][ 'functions' ] ) && $this->_tCfg[ 'general' ][ 'functions' ] ) {
			$customClass = SPLoader::loadClass( '/' . str_replace( '.php', C::ES, $this->_tCfg[ 'general' ][ 'functions' ] ), false, 'templates' );
			if ( method_exists( $customClass, 'BeforeStoreEntry' ) && is_array( $this->request ) ) {
				/* give the POST data to the template to edit and view them */
				$customClass::BeforeStoreEntry( $this->_model, $this->request[ 'post' ], $this->_tCfg[ 'general' ] );
			}
		}

		if ( array_key_exists( 'post', $this->request ) ) {
			SPFactory::registry()->set( 'requestcache', $this->request[ 'post' ] );
			SPFactory::registry()->set( 'requestcache_stored', $this->request[ 'store' ] );

			/* 'post' also sets request */
			$this->setFieldRequestData( $this->request[ 'post' ], false, 'post' );
		}

		$preState = [
			'approved' => $this->_model->get( 'approved' ),
			'state'    => $this->_model->get( 'state' ),
			'new'      => !$this->_model->get( 'id' ),
		];
		SPFactory::registry()->set( 'object_previous_state', $preState );

		$this->_model->getRequest( $this->_type, 'post' );
		Sobi::Trigger( $this->name(), __FUNCTION__, [ &$this->_model ] );

		if ( $this->_model->get( 'id' ) && $this->_model->get( 'id' ) == Input::Sid() ) {
			$new = false;
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
		// Bug #66
		// 1.4.3
//		$this->_model->save( $request );

		//1.4.6
		$this->_model->save( 'post' );

		/* if there is something to pay */
		/* 26 September 2020, Sigrid: Also if the admin adds paid fields, payments have to be stored. Necessary for the Expiration app (Scenario 1).
		Scenario 1: admin adds an option for the user free of charge, but when renewing it has to be paid. This would not work if the paid fields
		are not stored in the payments table even if for free the first time.
		Scenario 2: admin adds an option for the user free of charge, when the user edit his entry he still does not need to pay for that option
		and the option should not be removed.
		This also would not work correctly if the paid fields are not stored in the payments table. */
		$pCount = SPFactory::payment()->count( $this->_model->get( 'id' ) );
		if ( $pCount /*&& !( Sobi::Can( 'entry.payment.free' ) )*/ ) {
			if ( $customClass && method_exists( $customClass, 'BeforeStoreEntryPayment' ) ) {
				$customClass::BeforeStoreEntryPayment( $this->_model->get( 'id' ) );
			}
			SPFactory::payment()->store( $this->_model->get( 'id' ) );
		}

		/* delete cache files on after */
		$file = str_replace( '.', '-', $tsId );
		if ( SPLoader::dirPath( 'tmp/edit/' . $file ) ) {
			FileSystem::Delete( SPLoader::dirPath( 'tmp/edit/' . $file ) );
		}
		else {
			SPFactory::cache()->deleteVar( 'request_cache_' . $tsId );
		}
		SPLoader::loadClass( 'env.cookie' );
		Cookie::Delete( 'editentry' );

		$sid = $this->_model->get( 'id' );
		$pid = Input::Pid() ? Input::Pid() : Sobi::Section();
		Input::Set( 'method', 'html' );
		$redirect = null;
		$url = C::ES;
		if ( $new ) {
			if ( $this->_model->get( 'state' ) || Sobi::Can( 'entry', 'access', 'unpublished_own' ) || Sobi::Can( 'entry', 'access', 'unpublished_any' ) ) {
				$msg = $this->_model->get( 'state' ) ? Sobi::Txt( 'EN.ENTRY_SAVED' ) : Sobi::Txt( 'EN.ENTRY_SAVED_NP' );
				$url = Sobi::Url( [ 'sid' => $sid, 'pid' => $pid ], false, false );
			}
			else {
				// determine if there is a custom redirect
				if ( Sobi::Cfg( 'redirects.entry_save_enabled' ) && !( $pCount && !( Sobi::Can( 'entry.payment.free' ) ) ) ) {
					$redirect = Sobi::Cfg( 'redirects.entry_save_url', C::ES );
					if ( !preg_match( '/http[s]?:\/\/.*/', $redirect ) && $redirect != 'index.php' ) {
						$redirect = Sobi::Url( $redirect );
					}
					$msg = Sobi::Txt( Sobi::Cfg( 'redirects.entry_save_msg', 'EN.ENTRY_SAVED_NP' ) );
				}
				else {
					$msg = Sobi::Txt( 'EN.ENTRY_SAVED_NP' );
					$url = Sobi::Url( [ 'sid' => $pid ], false, false );
				}
			}
		}
		/* I know, it could be in one statement, but it is more readable like this */
		elseif ( $this->_model->get( 'approved' ) || Sobi::Can( 'entry.access.unapproved_own' ) || Sobi::Can( 'entry.access.unapproved_any' ) ) {
			$url = Sobi::Url( [ 'sid' => $sid, 'pid' => $pid ] );
			$msg = $this->_model->get( 'approved' ) ? Sobi::Txt( 'EN.ENTRY_SAVED' ) : Sobi::Txt( 'EN.ENTRY_SAVED_NA' );
		}
		else {
			if ( $this->_model->get( 'approved' ) ) {
				$msg = Sobi::Txt( 'EN.ENTRY_SAVED' );
			}
			else {
				$msg = Sobi::Txt( 'EN.ENTRY_SAVED_NA' );
			}
			$url = Sobi::Url( [ 'sid' => $sid, 'pid' => $pid ], false, false );
		}
		if ( $pCount && !( Sobi::Can( 'entry.payment.free' ) ) ) {
			$ident = md5( microtime() . $tsId . $sid . time() );
			$data = [ 'data' => SPFactory::payment()->summary( $sid ), 'ident' => $ident ];
			$url = Sobi::Url( [ 'sid' => $sid, 'task' => 'entry.payment' ], false, false );
			if ( Sobi::Cfg( 'cache.l3_enabled', true ) ) {
				SPFactory::cache()->addObj( $data, 'payment', $sid, Sobi::Section(), true );
			}
			else {
				$buffer = SPConfig::serialize( $data );
				FileSystem::Write( SPLoader::path( 'tmp/edit/' . $ident . '.payment', 'front', false, 'var' ), $buffer );
				$url = Sobi::Url( [ 'sid' => $sid, 'task' => 'entry.payment', 'tsid' => $ident ], false, false );
			}
			SPLoader::loadClass( 'env.cookie' );
			Cookie::Set( 'payment_' . $sid, $ident, Cookie::Days( 1 ) );
		}
		if ( $customClass && method_exists( $customClass, 'AfterStoreEntry' ) ) {
			$customClass::AfterStoreEntry( $this->_model );
		}
		$this->logChanges( SPC::LOG_SAVE,
			$preState[ 'new' ] ? Sobi::Txt( 'HISTORY.INITIALVERSION' ) : Input::String( 'history-note' )
		);
		$this->response( $redirect ? : $url, $msg, true, $redirect ? Sobi::Cfg( 'redirects.entry_save_msgtype', C::SUCCESS_MSG ) : C::SUCCESS_MSG );
	}


	/**
	 * Authorises an action.
	 *
	 * @param string $action
	 * @param string $ownership
	 *
	 * @return bool
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function authorise( $action = 'access', $ownership = 'valid' )
	{
		if ( !Sobi::Can( $this->_type, $action, $ownership, Sobi::Section() ) ) {
			switch ( $action ) {
				case 'add':
					$section = SPFactory::Section( Sobi::Section() );
					if ( Sobi::Cfg( 'redirects.entry_add_enabled', false ) && strlen( $section->get( 'redirectEntryAddUrl' ) ) ) {
						$this->escape( $section->get( 'redirectEntryAddUrl' ), SPLang::e( Sobi::Cfg( 'redirects.entry_add_msg', 'UNAUTHORIZED_ACCESS' ) ), Sobi::Cfg( 'redirects.entry_add_msgtype', 'message' ) );
					}
					else {
						Sobi::Error( $this->name(), SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ), C::ERROR, 403, __LINE__, __FILE__ );
					}
					break;
				default:
					Sobi::Error( $this->name(), SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ), C::ERROR, 403, __LINE__, __FILE__ );
					break;
			}
		}

		return true;
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function editForm()
	{
		if ( Sobi::My( 'id' ) || $this->_task == 'add' ) {
			$this->authorise( $this->_task, 'own' );
		}
		else {
			$this->authorise( $this->_task, 'any' );
		}
		if ( $this->_task != 'add' ) {
			$sid = Input::Sid();
			$sid = $sid ? : Input::Pid();
		}
		else {
			$this->_model = null;
			$sid = Input::Pid();
		}

		if ( $this->_model && $this->_model->isCheckedOut() ) {
			Sobi::Redirect( Sobi::Url( [ 'sid' => Input::Sid() ] ), Sobi::Txt( 'EN.IS_CHECKED_OUT', $this->_model->get( 'name' ) ), C::ERROR_MSG, true );
		}

		/* determine template package */
		$tplPackage = Sobi::Cfg( 'section.template', SPC::DEFAULT_TEMPLATE );

		/* load template config */
		$this->template();
		$this->tplCfg( $tplPackage );

		/* check if we have stored last edit in cache */
		$this->getCache( Input::String( 'SPro_editentry', 'cookie' ), 'editcache' );
		$section = SPFactory::Model( 'section' );
		$section->init( Sobi::Section() );
		SPFactory::cache()->setJoomlaCaching( false );
		//header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
//		header('Cache-Control: no-store, no-cache, must-revalidate');
//		header('Cache-Control: post-check=0, pre-check=0', FALSE);
//		header('Pragma: no-cache');

		if ( $this->_model ) {
			/* handle meta data */
			SPFactory::header()->objMeta( $this->_model );

			/* add pathway */
			SPFactory::mainframe()->addObjToPathway( $this->_model );
		}
		/* if adding new */
		else {
			/* handle meta data */
			if ( Sobi::Cfg( 'meta.always_add_section' ) ) {
				SPFactory::header()->objMeta( $section );
			}
			if ( $this->_task == 'add' ) {
				SPFactory::header()->addKeyword( $section->get( 'efMetaKeys' ) );

				$desc = $section->get( 'efMetaDesc' );
				if ( $desc ) {
					$separator = Sobi::Cfg( 'meta.separator', '.' );
					$desc .= $separator;
					SPFactory::header()->addDescription( $desc );
				}
			}
			/* add standard pathway */
			SPFactory::mainframe()->addToPathway( Sobi::Txt( 'EN.ADD_PATH_TITLE' ), Sobi::Url( 'current' ) );
			if ( !Sobi::Cfg( 'browser.no_title', false ) ) {
//				SPFactory::mainframe()->setTitle( Sobi::Txt( 'EN.ADD_TITLE', [ 'section' => $section->get( 'name' ) ] ) );
				// as Sobi does not recognize Joomla add entry menu links, we cannot remove it as the menu link and the link in SobiPro's top menu will be treated as different links
//				$title = [];
//				if ( !( Sobi::Cfg( 'browser.add_title', false ) ) ) {
				$title = Sobi::Txt( 'EN.ADD_TITLE_SHORT' );
//				}
				SPFactory::mainframe()->setTitle( $title );
			}
			/* add pathway */
//			SPFactory::mainframe()->addObjToPathway( $section );
			$this->setModel( SPLoader::loadModel( 'entry' ) );
		}
		$this->_model->formatDatesToEdit();
		$id = $this->_model->get( 'id' );
		if ( !$id ) {
			$this->_model->set( 'state', 1 );
		}

		if ( $this->_task != 'add' && !( $this->authorise( $this->_task, ( $this->_model->get( 'owner' ) == Sobi::My( 'id' ) ) ? 'own' : '*' ) ) ) {
			throw new SPException( SPLang::e( 'YOU_ARE_NOT_AUTH_TO_EDIT_THIS_ENTRY' ) );
		}

		$this->_model->loadFields( Sobi::Reg( 'current_section' ) );

		/* get fields for this section */
		$fields = $this->_model->get( 'fields' );

		if ( !count( $fields ) ) {
			throw new SPException( SPLang::e( 'CANNOT_GET_FIELDS_IN_SECTION', Sobi::Reg( 'current_section' ) ) );
		}

		/* create the validation script to check if required fields are filled in and the filters, if any, match */
		$this->createValidationScript( $fields );

		/* check out the model */
		$this->_model->checkOut();
		$class = SPLoader::loadView( 'entry' );
		$view = new $class( $this->template );
		$view->assign( $this->_model, 'entry' );

		$cache = Sobi::Reg( 'editcache' );
		/* get the categories */
		if ( isset( $cache ) && isset( $cache[ 'entry_parent' ] ) ) {
			$cats = explode( ',', $cache[ 'entry_parent' ] );
		}
		else {
			$cats = $this->_model->getCategories( true );
		}
		if ( count( $cats ) ) {
			$tCats = [];
			foreach ( $cats as $cid ) {
				$tCats2 = SPFactory::config()->getParentPath( ( int ) $cid, true );
				if ( count( $tCats2 ) ) {
					$tCats[] = implode( Sobi::Cfg( 'string.path_separator', ' > ' ), $tCats2 );
				}
			}
			if ( count( $tCats ) ) {
				$path = implode( "\n", $tCats );
				$view->assign( $path, 'parent_path' );
			}
			$parents = implode( ", ", $cats );
			$view->assign( $parents, 'parents' );
		}
		else {
			$parent = $sid == Sobi::Reg( 'current_section' ) ? 0 : $sid;
			if ( $parent ) {
				$imploded = implode( Sobi::Cfg( 'string.path_separator', ' > ' ), SPFactory::config()->getParentPath( $parent, true ) );
				$view->assign( $imploded, 'parent_path' );
			}
			$view->assign( $parent, 'parents' );
		}
		$visitor = SPFactory::user()->getCurrent();

		$view
			->assign( $this->_task, 'task' )
			->assign( $fields, 'fields' )
			->assign( $id, 'id' )
			->assign( $id, 'sid' )
			->assign( $visitor, 'visitor' );

		$view
			->setConfig( $this->_tCfg, $this->template )
			->setTemplate( $tplPackage . '.' . $this->templateType . '.' . ( $this->template == 'add' ? 'edit' : $this->template ) );

		$view->addHidden( ( $sid ? : Input::Sid() ), 'pid' )
			->addHidden( $id, 'sid' )
			->addHidden( ( Input::Pid() && Input::Pid() != $id ) ? Input::Pid() : Sobi::Section(), 'pid' )
			->addHidden( 'entry.submit', SOBI_TASK );

		Sobi::Trigger( $this->name(), __FUNCTION__, [ &$view ] );

		$view->display();
	}

	/**
	 * Details View.
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function details()
	{
		/* determine template package */
		$tplPackage = Sobi::Cfg( 'section.template', SPC::DEFAULT_TEMPLATE );

		/* load template config */
		$this->template();
		$this->tplCfg( $tplPackage );

		if ( $this->_model->get( 'oType' ) != 'entry' ) {
			Sobi::Error( 'Entry', sprintf( 'Serious security violation. Trying to save an object which claims to be an entry, but it is a %s. Task was %s', $this->_model->get( 'oType' ), Input::Task() ), C::ERROR, 403, __LINE__, __FILE__ );
			exit;
		}
		/* add pathway */
		SPFactory::mainframe()->addObjToPathway( $this->_model );
		$this->_model->loadFields( Sobi::Reg( 'current_section' ) );
		$this->_model->formatDatesToDisplay();
		$this->_model->countVisit();

		$class = SPLoader::loadView( 'entry' );
		$visitor = SPFactory::user()->getCurrent();

		$view = new $class( $this->template );
		$view->assign( $this->_model, 'entry' )
			->assign( $visitor, 'visitor' )
			->assign( $this->_task, 'task' );

		$view->setConfig( $this->_tCfg, $this->template );
		$view->setTemplate( $tplPackage . '.' . $this->templateType . '.' . $this->template );

		Sobi::Trigger( $this->name(), __FUNCTION__, [ &$view ] );
		SPFactory::header()->objMeta( $this->_model );

		$view->display();
		SPFactory::cache()->addObj( $this->_model, 'entry', $this->_model->get( 'id' ) );
	}

	/**
	 * @param $fields
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function createValidationScript( $fields )
	{
		/* get input filters */
		$filters = SPFactory::filter()->getFilters();
		$validate = [];
		foreach ( $fields as $field ) {
			$filter = $field->get( 'filter' );
			if ( $filter && isset( $filters[ $filter ] ) ) {
				$f = new stdClass();
				$f->name = $field->get( 'nid' );
				$f->filter = base64_decode( $filters[ $filter ][ 'params' ] );
				$f->msg = Sobi::Txt( '[JS]' . $filters[ $filter ][ 'message' ] );
				$validate[] = $f;
			}
		}
		if ( count( $validate ) ) {
			Sobi::Trigger( $this->name(), __FUNCTION__, [ &$validate ] );
			$validate = json_encode( ( $validate ) );
			$header =& SPFactory::header();
			$header->addJsVarFile( 'efilter', md5( $validate ), [ 'OBJ' => addslashes( $validate ) ] );
		}
	}

	/**
	 * Logs the changes made on an entry in the history.
	 *
	 * @param $action
	 * @param string $reason
	 *
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function logChanges( $action, string $reason = C::ES )
	{
		$changes = $this->_model->getCurrentBaseData();
		$fields = $this->_model->getFields();
		if ( count( $fields ) ) {
			foreach ( $fields as $nid => $field ) {
				if ( !$field->get( 'type' ) == 'info' ) {
					$changes[ 'fields' ][ $nid ] = $field->getRaw();
				}
			}
		}
		SPFactory::history()->logAction( $action,
			$this->_model->get( 'id' ),
			$this->_model->get( 'section' ),
			'entry',
			$reason,
			[],
			$changes
		);
	}

	/**
	 * Sets the stored data into the $_POST and $_REQUEST arrays.
	 *
	 * @param $data
	 * @param string $request
	 *
	 * @return void
	 */
	private function setFieldRequestData( $data, $onlyFields = true, string $request = 'request' ): void
	{
		if ( is_array( $data ) && count( $data ) ) {
			foreach ( $data as $index => $value ) {
				/* only pass fields' data */
				if ( $onlyFields ) {
					if ( strstr( $index, 'field_' ) ) {
						Input::Set( $index, $value, $request );
					}
				}
				else {
					Input::Set( $index, $value, $request );
				}
			}
		}
	}
}
