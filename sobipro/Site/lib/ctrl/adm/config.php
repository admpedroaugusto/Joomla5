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
 * @modified 10 June 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadController( 'controller' );

use Sobi\C;
use Sobi\FileSystem\File;
use Sobi\FileSystem\FileSystem;
use Sobi\Input\Input;
use Sobi\FileSystem\DirectoryIterator;
use Sobi\Lib\Factory;
use Sobi\Utils\StringUtils;

/**
 * Class SPConfigAdmCtrl
 */
class SPConfigAdmCtrl extends SPController
{
	use SobiPro\Helpers\ConfigurationTrait;

	use SobiPro\Helpers\MenuTrait {
		setMenuItems as protected;
	}

	/*** @var string */
	protected $_type = 'config';
	/*** @var string */
	protected $_defTask = 'general';
	/*** @var string */
	protected $_aclCheck = 'section.configure';

	/**
	 * SPConfigAdmCtrl constructor.
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	public function __construct()
	{
		$registry =& SPFactory::registry();
		$registry->loadDBSection( 'config' );
		$this->_task = (string) $this->_task;
		$this->_task = strlen( $this->_task ) ? $this->_task : $this->_defTask;
		if ( !Sobi::Reg( 'current_section' ) && $this->_task == 'general' ) {
			$this->_task = 'global';
			if ( !( Sobi::Can( 'cms.admin' ) || Sobi::Can( 'cms.options' ) ) ) {
				SPFactory::message()->setMessage( Sobi::Txt( 'MSG.UNAUTHORIZED_ACCESS_TASK', Input::Task() ), false, C::ERROR_MSG );
				Sobi::Redirect( Sobi::Back(), C::ES, C::ES, true );
//				Sobi::Error( 'ACL', SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ), C::WARNING, 403, __LINE__, __FILE__ );
			}
		}
		else {
			if ( !$this->_aclCheck ) {
				SPFactory::message()->setMessage( Sobi::Txt( 'MSG.UNAUTHORIZED_ACCESS_TASK', Input::Task() ), false, C::ERROR_MSG );
				Sobi::Redirect( Sobi::Back(), C::ES, C::ES, true );
//				Sobi::Error( 'ACL', SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ), C::WARNING, 403, __LINE__, __FILE__ );
			}
		}
		parent::__construct();
	}

	/**
	 * @return bool
	 * @throws ReflectionException
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function execute()
	{
		switch ( $this->_task ) {
			case 'clean':
				SPFactory::cache()->cleanSection();
				$this->response( Sobi::Back(), Sobi::Txt( 'MSG.CACHE_CLEANED' ), false, C::SUCCESS_MSG );
				break;
			case 'cleanall':
				SPFactory::cache()->cleanAll();
				$this->response( Sobi::Back(), Sobi::Txt( 'MSG.CACHES_CLEANED' ), false, C::SUCCESS_MSG );
				break;
			case 'saveOrdering':
				$this->saveDefaultOrdering();
				break;
			case 'saveFileAndData':
			case 'save':
				$this->save( $this->_task == 'saveFileAndData' );
				break;
			case 'saveFile':
				$this->saveConfigFile();
				break;
			case 'crawler':
				$this->crawler();
				break;
			case 'fields':
				/** @TODO check this static method */
				$this->fields();
				break;
			case 'saveRejectionTpl':
				$this->saveRejectionTpl();
				break;
			case 'rejectionTemplates':
				$this->rejectionTemplates();
				break;
			case 'deleteRejectionTpl':
				$this->deleteRejectionTpl();
				break;

			default:
				/* case plugin didn't register this task, it was an error */
				if ( !parent::execute() && !$this->view() ) {
					Sobi::Error( $this->name(), SPLang::e( 'SUCH_TASK_NOT_FOUND', Input::Task() ), C::NOTICE, 404, __LINE__, __FILE__ );
				}

				return false;
		}

		return true;
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function deleteRejectionTpl()
	{
		if ( !Factory::Application()->checkToken() ) {
			Sobi::Error( 'Token', SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ), C::ERROR, 403, __LINE__, __FILE__ );
		}

		$templates = $this->getRejectionsTemplates();
		foreach ( $templates as $tid => $template ) {
			unset( $templates[ $tid ][ 'description' ] );
		}
		unset( $templates[ Input::Cmd( 'tid' ) ] );
		if ( count( $templates ) ) {
			SPFactory::registry()->saveDBSection( $templates, 'rejections-templates_' . Sobi::Section() );
		}
		Factory::Db()->delete( 'spdb_language', [ 'sKey' => Input::Cmd( 'tid' ), 'section' => Sobi::Section() ] );
		$this->response( Sobi::Back(), Sobi::Txt( 'ENTRY_REJECT_DELETED_TPL' ), false, C::SUCCESS_MSG );
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function rejectionTemplates()
	{
		$templates = $this->getRejectionsTemplates();
		SPFactory::mainframe()
			->cleanBuffer()
			->customHeader();
		echo json_encode( $templates );
		exit;
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function saveRejectionTpl()
	{
		if ( !Factory::Application()->checkToken() ) {
			Sobi::Error( 'Token', SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ), C::ERROR, 403, __LINE__, __FILE__ );
		}

		$templates = $this->getRejectionsTemplates();
		$id = StringUtils::Nid( Input::String( 'templateName' ) );
		$templates[ $id ] = [
			'params'  => SPConfig::serialize(
				[
					'trigger.unpublish' => Input::Bool( 'trigger_unpublish' ),
					'trigger.unapprove' => Input::Bool( 'trigger_unapprove' ),
					'unpublish'         => Input::Bool( 'unpublish' ),
					'discard'           => Input::Bool( 'discard' ),
				]
			),
			'key'     => $id,
			'value'   => Input::String( 'templateName' ),
			'options' => [],
		];
		foreach ( $templates as $tid => $template ) {
			unset( $templates[ $tid ][ 'description' ] );
		}
		SPFactory::registry()->saveDBSection( $templates, 'rejections-templates_' . Sobi::Section() );
		$data = [
			'key'     => $id,
			'value'   => Input::Html( 'reason', 'post' ),
			'type'    => 'rejections-templates',
			'id'      => Sobi::Section(),
			'section' => Sobi::Section(),
			'options' => Input::String( 'templateName' ),
		];
		SPLang::saveValues( $data );
		$this->response( Sobi::Back(), Sobi::Txt( 'ENTRY_REJECT_SAVED_TPL' ), false, C::SUCCESS_MSG );
	}

	/**
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function getRejectionsTemplates()
	{
		$templates = SPFactory::registry()
			->loadDBSection( 'rejections-templates_' . Sobi::Section() )
			->get( 'rejections-templates_' . Sobi::Section() );
		if ( !$templates ) {
			$templates = SPFactory::registry()
				->loadDBSection( 'rejections-templates' )
				->get( 'rejections-templates' );
		}

		$f = [];
		foreach ( $templates as $tid => $template ) {
			$desc = SPLang::getValue( $tid, 'rejections-templates', Sobi::Section() );
			if ( !$desc ) {
				$desc = SPLang::getValue( $tid, 'rejections-templates', 0 );
			}
			$f[ $tid ] = [
				'params'      => SPConfig::unserialize( $template[ 'params' ] ),
				'key'         => $tid,
				'value'       => $template[ 'value' ],
				'description' => $desc,
				'options'     => $template[ 'options' ],
			];
		}
		ksort( $f );

		return $f;
	}

	/**
	 * @param int $sid
	 * @param string $types
	 *
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function fields( $sid = 0, string $types = C::ES )
	{
		if ( !$sid ) {
			$sid = Input::Sid();
		}
		if ( !$types ) {
			$types = Input::String( 'types' );
			$types = SPFactory::config()->structuralData( $types, true );
		}
		$fields = SPConfig::fields( $sid, $types );
		if ( Input::Bool( 'fields-xhr' ) ) {
			SPFactory::mainframe()
				->cleanBuffer()
				->customHeader();
			exit( json_encode( $fields ) );
		}
		else {
			return $fields;
		}
	}

	/**
	 * @return bool
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception|\ReflectionException
	 */
	protected function crawler()
	{
		$cronCommandFile = SOBI_PATH . '/lib/ctrl/cron.php';
		$phpCmd = PHP_BINDIR . '/php';
		$section = Sobi::Section();
		$liveUrl = Sobi::Cfg( 'live_site' );
		$cron = [
			'type'  => 'outline-info',
			'label' => nl2br( Sobi::Txt( 'CRAWLER_CRON_INFO', "$phpCmd $cronCommandFile section=$section liveURL=$liveUrl", "$phpCmd $cronCommandFile liveURL=$liveUrl", "$phpCmd $cronCommandFile --help" ) ),
		];
		$trim = defined( 'SOBI_TRIMMED' );

		$view = $this->getView( 'config.' . $this->_task );
		$view
			->assign( $cron, 'cron' )
			->assign( $trim, 'trim' )
			->setCtrl( $this );

		Sobi::Trigger( 'After' . ucfirst( $this->_task ), $this->name(), [ &$view ] );

		$view
			->determineTemplate( 'config', $this->_task )
			->display();

		return true;
	}

	/**
	 * Saves ordering and limit in backend if the save button is pressed.
	 * It saves it section-independent.
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 * @throws \Exception
	 */
	protected function saveDefaultOrdering()
	{
		$subject = Input::Cmd( 'target' );
		$section = StringUtils::Nid( Sobi::Section( true ) );

		//$order = Sobi::GetUserState( "$subject.order", C::ES );
		$order = Sobi::GetUserState( "$subject.order", ( $subject == 'categories' ? 'corder' : 'eorder' ),
			Sobi::Cfg( "admin.$subject-order.$section", C::ES ) );

		$saved = false;
		if ( strlen( $order ) ) {
			SPFactory::config()->saveCfg( "admin.$subject-order.$section", $order );
			$saved = true;
		}

//		$limit = Sobi::GetUserState( $subject . '.limit', 10 );
		$limit = Sobi::GetUserState( "$subject.limit", ( $subject == 'categories' ? 'climit' : 'elimit' ),
			Sobi::Cfg( "admin.$subject-limit.$section", 0 ) );

		if ( $limit ) {
			SPFactory::config()->saveCfg( "admin.$subject-limit.$section", $limit );
			$saved = true;
		}

		if ( $saved ) {
			$this->response( Sobi::Back(), Sobi::Txt( 'MSG_DEFAULT_ORDERING_SAVED' ), false );
		}
	}

	/**
	 * Saves the configuration file config_override.ini.
	 *
	 * @param string $msg -> the message to show in case of success
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function saveConfigFile( string $msg = 'MSG.CONFIGFILE_SAVED' )
	{
		$ovrConfig = SOBI_PATH . '/etc/config_override.ini';
		if ( Sobi::Can( 'configure', 'section' ) ) {
			$values = Input::Arr( 'cfgfile' );
			Sobi::Trigger( 'SaveConfigFile', $ovrConfig, [ &$values ] );

			try {
				FileSystem::WriteIniFile( $ovrConfig, $values );
			}
			catch ( Exception $x ) {
				$this->response( Sobi::Back(), $x->getMessage(), false, C::ERROR_MSG );
			}
			Sobi::Trigger( 'After', 'SaveConfigFile', [ &$data ] );
		}
		$this->response( Sobi::Back(), Sobi::Txt( $msg ), false, C::SUCCESS_MSG );
	}

	/**
	 * @param $task
	 *
	 * @return \SPAdmView
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 * @throws \Exception
	 */
	protected function getView( $task )
	{
		SPLoader::loadClass( 'html.input' );
		$sid = Sobi::Section();

		/* create menu */
		$menu = $this->setMenuItems( $task );
		//$menu = $this->createMenu( $task );

		if ( $sid ) {
			/** @var \SPSection $cSec */
			$cSec = SPFactory::Model( 'section' );
			$cSec->init( $sid );
		}
		else {
			$cSec = [ 'name' => Sobi::Txt( 'GB.CFG.GLOBAL_CONFIGURATION' ) ];
		}

		/** @var SPAdmView $view */
		$view = SPFactory::View( 'config', true );
		$view
			->assign( $task, 'task' )
			->assign( $cSec, 'section' )
			->assign( $menu, 'menu' )
			->addHidden( SPFactory::registry()->get( 'current_section' ), 'sid' );

		return $view;
	}

	/**
	 * @return bool
	 * @throws ReflectionException
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function view()
	{
		Sobi::ReturnPoint();

		$view = $this->getView( 'config.' . $this->_task );
		$view->setCtrl( $this );
		$trim = defined( 'SOBI_TRIMMED' );
		$IP = Input::Ip4( 'REMOTE_ADDR', 'SERVER', 0 );

		/* general configuration */
		if ( $this->_task == 'general' ) {
			$this->checkTranslation();
			$fields = $this->getNameFields();
			$nameFields = [];
			if ( count( $fields ) ) {
				foreach ( $fields as $field ) {
					$nameFields[ $field->get( 'fid' ) ] = $field->get( 'name' );
				}
			}
			// create a list of alpha fields
			$alphaFields = [];
			$fields = $this->getNameFields( true, Sobi::Cfg( 'alphamenu.field_types' ) );
			if ( count( $fields ) ) {
				foreach ( $fields as $field ) {
					// check for encrypted fields; these cannot be used
					$params = $field->get( 'params' );
					$encrypted = false;
					if ( isset ( $params[ 'encryptData' ] ) ) {
						if ( $params[ 'encryptData' ] == 1 ) {
							$encrypted = true;
						}
					}
					if ( !$encrypted ) {
						$alphaFields[ $field->get( 'fid' ) ] = $field->get( 'name' );
					}
				}
			}
			// create a list of ordering fields
			$unsets = [ 'position.asc', 'position.desc', 'RAND()' ];    // parameters to remove
			$orderingFields = $view->namesFields( $unsets, true, 'view' );
			$templateList = $view->templatesList();

			// same unsets
			$searchOrdering = $view->namesFields( $unsets, true, 'search' );
			$searchorderingFields = $searchOrdering;

			// same unsets
			$entriesOrdering = $view->namesFields( $unsets, true, 'view' );

			// add these two to the $entriesOrdering
			$temp[ 'disabled' ] = Sobi::Txt( 'SECOND_ORDER_DISABLED' );
			$temp[ 'random' ] = Sobi::Txt( 'ORDER_BY_RANDOM' );
			$entriesOrdering = array_merge( $temp, $entriesOrdering );

			// add these to the $searchOrdering
			$temp = [];
			$temp[ 'priority' ] = Sobi::Txt( 'ORDER_PRIORITY' );
			$temp[ 'random' ] = Sobi::Txt( 'ORDER_BY_RANDOM' );
			$searchOrdering = array_merge( $temp, $searchOrdering );

			$temp = [];
			// add this one to the $searchFields
			$temp[ 'random' ] = Sobi::Txt( 'ORDER_BY_RANDOM' );
			$searchorderingFields = array_merge( $temp, $searchorderingFields );

			$view
				->assign( $nameFields, 'nameFields' )
				->assign( $templateList, 'templatesList' )
				->assign( $searchOrdering, 'searchOrdering' )
				->assign( $entriesOrdering, 'entriesOrdering' )
				->assign( $alphaFields, 'alphaMenuFields' )
				->assign( $orderingFields, 'orderingFields' )
				->assign( $searchorderingFields, 'searchorderingFields' );
			$languages = $view->languages();
			$view->assign( $languages, 'languages-list' );
			$multiLang = Sobi::Cfg( 'lang.multimode', false );
			$view->assign( $multiLang, 'multilingual' );
		}

		/* global configuration */
		else {
			/* handle the configuration file */
			$config = null;
			$configResults = [ 'created' => false, 'combined' => false, 'error' => C::ES ];
			$coreConfig = SOBI_PATH . '/etc/config';
			$ovrConfig = SOBI_PATH . '/etc/config_override';
			if ( !FileSystem::Exists( $ovrConfig . '.ini' ) ) {
				try {
					FileSystem::Copy( $coreConfig . '.ini', $ovrConfig . '.ini' );
					$configResults[ 'created' ] = true;
				}
				catch ( Exception $x ) {
					$configResults[ 'error' ] = Sobi::Txt( 'GBN.CFG.CONFIG_COPY_ERROR', $ovrConfig . '.ini' );
				}
			}
			if ( FileSystem::Exists( $ovrConfig . '.ini' ) ) {
				/* read the override file */
				try {
					$config = FileSystem::LoadIniFile( $ovrConfig, true, true, INI_SCANNER_TYPED );
					if ( !$configResults[ 'created' ] ) {
						/* read the core file */
						$core = FileSystem::LoadIniFile( $coreConfig, true, true, INI_SCANNER_TYPED );

						/* check for new sections and keys in the core file */
						foreach ( $core as $section => $key ) {
							/* if the section already exists in override file */
							if ( array_key_exists( $section, $config ) ) {
								foreach ( $core[ $section ] as $key => $value ) {
									/* if the key is not available in the override file ... */
									if ( !array_key_exists( $key, $config[ $section ] ) ) {
										/* ... add it */
										$config[ $section ][ $key ] = $core[ $section ][ $key ];
										$configResults[ 'combined' ] = true;
									}
								}
							}
							/* new section in core file ... */
							else {
								/* ... add it */
								$config[ $section ] = $core[ $section ];
								$configResults[ 'combined' ] = true;
							}
						}
						/* check for sections and keys removed from the core file */
						foreach ( $config as $section => $key ) {
							/* if the section also exists in the core file */
							if ( array_key_exists( $section, $core ) ) {
								foreach ( $config[ $section ] as $key => $value ) {
									/* if the key is not available in the core file ... */
									if ( !array_key_exists( $key, $core[ $section ] ) ) {
										/* ... remove it */
										unset ( $config[ $section ][ $key ] );
										$configResults[ 'combined' ] = true;
									}
								}
							}
							/* section does not exist in the core file any more... */
							else {
								/* ... remove it */
								unset ( $config[ $section ] );
								$configResults[ 'combined' ] = true;
							}
						}
					}
				}
				catch ( Exception $x ) {
					$config = null;
				}
			}
			$configResults[ 'error' ] = $config ? C::ES : Sobi::Txt( 'GBN.CFG.CONFIG_READ_ERROR', $ovrConfig . '.ini' );
			$config[ 'config_results' ] = $configResults;
			$view->assign( $config, 'keys' );
		}

		$view->addHidden( $IP, 'current-ip' );
		$view->assign( $trim, 'trim' );

		Sobi::Trigger( $this->_task, $this->name(), [ &$view ] );

		$view->determineTemplate( 'config', $this->_task );
		$view->display();

		Sobi::Trigger( 'After' . ucfirst( $this->_task ), $this->name(), [ &$view ] );

		return true;
	}

	/**
	 * @param string $tpl
	 * @param bool $cmsOv
	 *
	 * @return string
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function listTemplates( string $tpl = C::ES, bool $cmsOv = true )
	{
		SPFactory::header()->addJsFile( 'Tps.dtree', false, C::ES, false, 'js', C::ES );
		$ls = Sobi::Cfg( 'media_folder_live' ) . 'tree';
		$nodes = null;
		$count = 0;
		$tpl = FileSystem::FixPath( $tpl ? : SPLoader::dirPath( 'usr.templates' ) );
		if ( Sobi::Section() ) {
			$realName = Sobi::Txt( 'TP.INFO' );
			$iTask = Sobi::Url( [ 'task' => 'template.info', 'template' => basename( $tpl ), 'sid' => Sobi::Section() ] );
			$nodes .= "spTpl.add( -123, 0,'$realName','$iTask', '', '', '$ls/info.png' );\n";
			if ( file_exists( "$tpl/config.xml" ) ) {
				$realName = Sobi::Txt( 'TP.SETTINGS' );
				$iTask = Sobi::Url( [ 'task' => 'template.settings', 'template' => basename( $tpl ), 'sid' => Sobi::Section() ] );
				$nodes .= "spTpl.add( -120, 0,'$realName','$iTask', '', '', '$ls/settings.png' );\n";
			}
		}
		$this->travelTpl( new DirectoryIterator( $tpl ), $nodes, 0, $count );
		if ( $cmsOv ) {
			$cms = Factory::ApplicationInstaller()->templatesPath( 'com_sobipro' );
			if ( isset( $cms[ 'name' ] ) && isset( $cms[ 'data' ] ) && is_array( $cms[ 'data' ] ) && count( $cms[ 'data' ] ) ) {
				$count++;
				if ( isset( $cms[ 'icon' ] ) ) {
					$nodes .= "spTpl.add( $count, 0, '{$cms['name']}', '', '', '', '{$cms['icon']}', '{$cms['icon']}' );\n";
				}
				else {
					$nodes .= "spTpl.add( $count, 0, '{$cms['name']}' );\n";
				}
				$current = $count;
				foreach ( $cms[ 'data' ] as $name => $path ) {
					$count++;
					$nodes .= "spTpl.add( $count, $current,'$name' );\n";
					$this->travelTpl( new DirectoryIterator( $path ), $nodes, $count, $count, true );
				}
			}
		}
		$t = null;
		if ( Sobi::Section() ) {
			$file = SPLoader::path( SPC::TEMPLATE_PATH . Sobi::Cfg( 'section.template', SPC::DEFAULT_TEMPLATE ) . '/template', 'front', true, 'xml' );
			if ( $file ) {
				$def = new DOMDocument();
				$def->load( $file );
				$xdef = new DOMXPath( $def );
				$t = $xdef->query( '/template/name' )->item( 0 )->nodeValue;
			}
			// templates .xml file does no longer exist
			else {
				SPFactory::message()->error( Sobi::Txt( 'TP.TEMPLATE_MISSING', Sobi::Cfg( 'section.template', SPC::DEFAULT_TEMPLATE ) ), false );
			}
		}
		else {
			$t = Sobi::Txt( 'GB.TEMPLATES' );
		}
		SPFactory::header()->addJsCode( "
			icons = {
						root : '$ls/base.gif',
						folder : '$ls/folder.gif',
						folderOpen : '$ls/folderopen.gif',
						node : '$ls/page.gif',
						empty : '$ls/empty.gif',
						line : '$ls/empty.gif',
						join : '$ls/empty.gif',
						joinBottom : '$ls/empty.gif',
						plus : '$ls/arrow_close.gif',
						plusBottom : '$ls/arrow_close.gif',
						minus : '$ls/arrow_open.gif',
						minusBottom	: '$ls/arrow_open.gif',
						nlPlus : '$ls/nolines_plus.gif',
						nlMinus : '$ls/nolines_minus.gif'
			};
			const spTpl = new dTree( 'spTpl', icons );	\n
			document.addEventListener( 'DOMContentLoaded', function( event )
//			SobiCore.Ready( function ()
			{
				spTpl.add(0, -1, '$t' );\n
				$nodes \n
				try { document.getElementById( 'spTpl' ).innerHTML = spTpl } catch( e ) {}
			} );
		"
		);

		/** for some reason jQuery is not able to add the tree  */
		return "<div id=\"spTpl\"></div>";
	}

	/**
	 * @param $dir
	 * @param $nodes
	 * @param $current
	 * @param $count
	 * @param false $package
	 *
	 * @throws SPException|\Sobi\Error\Exception
	 */
	private function travelTpl( $dir, &$nodes, $current, &$count, $package = false )
	{
		$ls = FileSystem::FixUrl( Sobi::Cfg( 'media_folder_live' ) . 'tree' );
		static $root = null;
		if ( !$root ) {
			$root = new File( str_replace( '\\', '/', SOBI_PATH ) );
		}
		$exceptions = [ 'config.xml', 'config.json', 'tmp' ];
		foreach ( $dir as $file ) {
			$task = null;
			$fileName = $file->getFilename();
			if ( in_array( $fileName, $exceptions ) ) {
				continue;
			}
			if ( $file->isDot() ) {
				continue;
			}
			$count++;
			if ( $file->isDir() ) {
				if ( $current == 0 || $package ) {
					if ( strstr( $file->getPathname(), $root->getPathname() ) ) {
						$filePath = str_replace( $root->getPathname() . '/usr/templates/', C::ES, $file->getPathname() );
					}
					else {
						$filePath = 'cms:' . str_replace( SOBI_ROOT . '/', C::ES, $file->getPathname() );
					}
					$filePath = str_replace( '/', '.', $filePath );
					$insertTask = Sobi::Url( [ 'task' => 'template.info', 'template' => $filePath ] );
					$nodes .= "spTpl.add( $count, $current,'$fileName','', '', '', '$ls/imgfolder.gif', '$ls/imgfolder.gif' );\n";
					if ( !Sobi::Section() ) {
						$count2 = $count * -100;
						$fileName = Sobi::Txt( 'TP.INFO' );
						$nodes .= "spTpl.add( $count2, $count,'$fileName','$insertTask', '', '', '$ls/info.png' );\n";
						if ( file_exists( $file->getPathname() . "/config.xml" ) ) {
							$fileName = Sobi::Txt( 'TP.SETTINGS' );
							$count2--;
							$insertTask = Sobi::Url( [ 'task' => 'template.settings', 'template' => $filePath ] );
							$nodes .= "spTpl.add( $count2, $count,'$fileName','$insertTask', '', '', '$ls/settings.png' );\n";
						}
					}
				}
				else {
					$nodes .= "spTpl.add( $count, $current,'$fileName','');\n";
				}
				$this->travelTpl( new DirectoryIterator( $file->getPathname() ), $nodes, $count, $count );
			}
			else {
				$ext = FileSystem::GetExt( $fileName );
				if ( in_array( $ext, [ 'htaccess', 'zip' ] ) || $fileName == 'index.html' ) {
					continue;
				}
				switch ( strtolower( $ext ) ) {
					case 'php':
						$ico = $ls . '/php.png';
						break;
					case 'xml':
						$ico = $ls . '/xml.png';
						break;
					case 'xsl':
						$ico = $ls . '/xsl.png';
						break;
					case 'css':
						$ico = $ls . '/css.png';
						break;
					case 'jpg':
					case 'jpeg':
					case 'png':
					case 'bmp':
					case 'gif':
					case 'webp':
						$ico = $ls . '/img.png';
						$task = 'javascript:void(0);';
						break;
					case 'ini':
						$ico = $ls . '/ini.png';
						break;
					case 'linc':
					case 'less':
						$ico = $ls . '/less.png';
						break;
					case 'js':
						$ico = $ls . '/js.png';
						break;
					default:
						$ico = $ls . '/page.gif';
				}
				if ( !$task ) {
					if ( strstr( $file->getPathname(), $root->getPathname() ) ) {
						$filePath = str_replace( $root->getPathname() . '/usr/templates/', C::ES, $file->getPathname() );
					}
					else {
						$filePath = 'cms:' . str_replace( SOBI_ROOT . '/', C::ES, $file->getPathname() );
					}
					$filePath = str_replace( '/', '.', $filePath );
					if ( Sobi::Section() ) {
						$task = Sobi::Url( [ 'task' => 'template.edit', 'file' => $filePath, 'sid' => Sobi::Section() ] );
					}
					else {
						$task = Sobi::Url( [ 'task' => 'template.edit', 'file' => $filePath ] );
					}
				}
				$nodes .= "spTpl.add( $count, $current,'$fileName','$task', '', '', '$ico' );\n";
			}
		}
	}

	/**
	 * @param string $task
	 *
	 * @return SPAdmSiteMenu
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function & createMenu( string $task = C::ES )
	{
		if ( !$task ) {
			$task = 'config.' . $this->_task;
		}
		if ( Sobi::Section() ) {
			/** @var \SPSection $cSec */
			$cSec = SPFactory::Model( 'section' );
			$cSec->init( Sobi::Section() );
		}

		return $this->setMenuItems( $task );
	}

	/**
	 * Returns an array with field object of the field type which is given as parameters.
	 *
	 * @param array $types
	 * @param bool $catFields
	 * @param string $order
	 *
	 * @return array
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function getTypedFields( array $types = [], bool $catFields = false, string $order = 'position.num' ): array
	{
		$fids = [];
		try {
			if ( $catFields ) {
				if ( is_array( $types ) && count( $types ) ) {
					$fids = Factory::Db()
						->select( 'fid', 'spdb_field', [ 'fieldType' => $types, 'section' => Sobi::Reg( 'current_section' ), 'adminField' => -1 ], $order )
						->loadResultArray();
				}
				else {
					$fids = Factory::Db()
						->select( 'fid', 'spdb_field', [ 'section' => Sobi::Reg( 'current_section' ), 'adminField' => -1 ], $order )
						->loadResultArray();
				}
			}
			else {
				if ( is_array( $types ) && count( $types ) ) {
					$fids = Factory::Db()
						->select( 'fid', 'spdb_field', [ 'fieldType' => $types, 'section' => Sobi::Reg( 'current_section' ), 'adminField>' => -1 ], $order )
						->loadResultArray();
				}
				else {
					$fids = Factory::Db()
						->select( 'fid', 'spdb_field', [ 'section' => Sobi::Reg( 'current_section' ), 'adminField>' => -1 ], $order )
						->loadResultArray();
				}
			}
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'CANNOT_GET_FIELD_FOR_NAMES', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}

		$fields = [];
		if ( count( $fids ) ) {
			foreach ( $fids as $fid ) {
				/** @var SPField $field */
				$field = SPFactory::Model( 'field', true );
				$field->init( $fid );
				try {
					$field->setCustomField( $fields );
				}
				catch ( SPException $x ) {
					$fields[ $fid ] = $field;
				}
			}
		}

		return $fields;
	}

	/**
	 * Returns an array with field object of the field type which is possible to use it as entry name field.
	 *
	 * @param bool $pos
	 * @param array $types
	 * @param bool $catFields
	 *
	 * @return array
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function getNameFields( bool $pos = false, array $types = [], bool $catFields = false ): array
	{
		// removed static because we have different settings for Alpha Index
		/* static */
//		$cache = [ 'pos' => null, 'npos' => null ];

		/* alpha index/ordering */
		if ( $pos ) {
//			if ( $cache[ 'pos' ] ) {
//				return $cache[ 'pos' ];
//			}
			if ( !count( $types ) ) {
				// field types for ordering by field (needs corresponding sortBy method)
				$types = explode( ', ', Sobi::Cfg( 'field_types_for_ordering', 'inbox, select, radio, calendar, geomap' ) );
			}
		}
		else {
//			if ( $cache[ 'npos' ] ) {
//				return $cache[ 'npos' ];
//			}
			if ( !count( $types ) ) {
				$types = explode( ', ', Sobi::Cfg( 'field_types_for_name', 'inbox' ) );
			}
		}

		try {
			$fids = [];
			if ( $catFields ) {
				$fids = Factory::Db()
					->select( 'fid', 'spdb_field', [ 'fieldType' => $types, 'enabled' => 1, 'section' => Sobi::Reg( 'current_section' ), 'adminField' => -1 ] )
					->loadResultArray();
			}
			else {
				$fids = Factory::Db()
					->select( 'fid', 'spdb_field', [ 'fieldType' => $types, 'enabled' => 1, 'section' => Sobi::Reg( 'current_section' ), 'adminField>' => -1 ], 'position.asc' )
					->loadResultArray();
			}
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'CANNOT_GET_FIELD_FOR_NAMES', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}
		$fields = [];
		if ( count( $fids ) ) {
			foreach ( $fids as $fid ) {
				/** @var SPField $field */
				$field = SPFactory::Model( 'field', true );
				$field->init( $fid );
				try {
					$field->setCustomField( $fields );
				}
				catch ( SPException $x ) {
					$fields[ $fid ] = $field;
				}
			}
		}

//		$cache[ $pos ? 'pos' : 'npos' ] = $fields;

		return $fields;
	}

	/**
	 * Saves the general/global config.
	 *
	 * @param bool $apply => true = save also configuration file
	 * @param bool $clone
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function save( $apply = false, $clone = false )
	{
		$sid = Sobi::Section();
		if ( $sid ) {
			$this->_type = 'section';
			$this->authorise( 'configure' );
			/* check if required fields are filled in */
			$this->validate( 'config.general', [ 'task' => 'config.general', 'sid' => $sid ] );
		}
		else {
			if ( !( Sobi::Can( 'cms.admin' ) || Sobi::Can( 'cms.options' ) ) ) {
				Sobi::Error( $this->name(), SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ), C::ERROR, 403, __LINE__, __FILE__ );
				exit;
			}
			/* check if required fields are filled in */
			$this->validate( 'config.global', [ 'task' => 'config.global' ] );
		}
		$data = Input::Arr( 'spcfg', 'request', [] );
		// strange thing =8-O
		if ( !isset( $data[ 'alphamenu.extra_fields_array' ] ) ) {
			$data[ 'alphamenu.extra_fields_array' ] = [];
		}
		if ( !isset( $data[ 'ordering.fields_array' ] ) ) {
			$data[ 'ordering.fields_array' ] = [];
		}
		if ( !isset( $data[ 'sordering.fields_array' ] ) ) {
			$data[ 'sordering.fields_array' ] = [];
		}
		if ( !isset( $data[ 'template.icon_fonts_arr' ] ) ) {
			$data[ 'template.icon_fonts_arr' ] = [];
		}

		[ $values, $section ] = $this->prepareConfiguration( $data );

		if ( $section ) {
			/* @var SPSection $sec */
			$sec = SPFactory::Model( 'section' );
			$sec->init( Input::Sid() );
			$sec->getRequest( 'section' );
			$sec->save( true );
		}
		Sobi::Trigger( 'SaveConfig', $this->name(), [ &$values ] );
		try {
			Factory::Db()->insertArray( 'spdb_config', $values, true );
		}
		catch ( Sobi\Error\Exception $x ) {
			$this->response( Sobi::Back(), $x->getMessage(), false, C::ERROR_MSG );
		}

		/* Write the template general config files and change and compile the LESS files, if saving general config */
		if ( $section ) {
			/* clean the section cache */
			SPFactory::cache()->cleanSection();
			$path = SOBI_PATH . '/' . SPC::TEMPLATE_PATH . Sobi::Cfg( 'section.template', SPC::DEFAULT_TEMPLATE );
			$filename = FileSystem::FixPath( $path . '/config.json' );
			if ( FileSystem::Exists( $filename ) ) {
				$configuration = json_decode( FileSystem::Read( $filename ) );

				//$convertbs = [ C::BOOTSTRAP2 => 'bs2', C::BOOTSTRAP3 => 'bs3', C::BOOTSTRAP4 => 'bs4', C::BOOTSTRAP5 => 'bs5', ];
				$convertfont = [ 0                       => 'fa3',
				                 'font-awesome-3'        => 'fa3',
				                 'font-awesome-4'        => 'fa4',
				                 'font-awesome-5'        => 'fa5',
				                 'font-awesome-6'        => 'fa6',
				                 'font-google-materials' => 'gm' ];

				$configuration->general->bs = $data[ 'template.framework-style' ];
				$configuration->general->font = $convertfont[ ( is_array( $data[ 'template.icon_fonts_arr' ] ) && count( $data[ 'template.icon_fonts_arr' ] ) ) ? $data[ 'template.icon_fonts_arr' ][ 0 ] : 0 ];

				$variable = 'bs';
				foreach ( $configuration->less as $file => $variables ) {
					$lessfile = FileSystem::FixPath( $path . '/css/' . $file . '.less' );
					if ( FileSystem::Exists( $lessfile ) ) {
						$lessContent = FileSystem::Read( $lessfile );
						$lessContent = preg_replace( "/@$variable:[^\n]*\;/", "@$variable: {$data[ 'template.framework-style' ]};", $lessContent );
						try {
							FileSystem::Write( $lessfile, $lessContent );
							SPConfig::compileLessFile( $lessfile, str_replace( 'less', 'css', $lessfile ), true );
						}
						catch ( Exception $x ) {
							$this->response( Sobi::Back(), SPLang::e( 'TP.LESS_FILE_NOT_COMPILED', $x->getMessage() ), false, C::ERROR_MSG );
						}
					}
				}
				try {
					$configuration = json_encode( $configuration );
					FileSystem::Write( $filename, $configuration );
				}
				catch ( Exception $x ) {
					$this->response( Sobi::Back(), $x->getMessage(), false, C::ERROR_MSG );
				}
			}
		}
		else {
			/* clean all caches */
			SPFactory::cache()->cleanAll();
		}

		Sobi::Trigger( 'After', 'SaveConfig', [ &$values ] );

		if ( $apply ) {
			$this->saveConfigFile( 'MSG.CONFIGBOTH_SAVED' );
		}
		$this->response( Sobi::Back(), Sobi::Txt( 'MSG.CONFIG_SAVED' ), false, C::SUCCESS_MSG );
	}

	/**
	 * Checks the section template for applications which have a styles file included in theme.less.
	 *
	 * @param $stylefile
	 *
	 * @return string
	 */
	protected function checkTemplate( $stylefile ): string
	{
		$hint = C::ES;
		if ( $stylefile ) {
			$template = Sobi::Cfg( 'section.template', SPC::DEFAULT_TEMPLATE );
			if ( !FileSystem::Exists( SOBI_PATH . '/usr/templates/' . $template . '/css/helper/_applications.linc' ) ) {
				$hint = Sobi::Txt( 'TEMPLATE_COMPATIBILITY', $stylefile );
			}
		}

		return $hint;
	}
}