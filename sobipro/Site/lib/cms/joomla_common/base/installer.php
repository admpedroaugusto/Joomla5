<?php
/**
 * @package: SobiPro Library
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2022 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 05-Mar-2011 by Radek Suski
 * @modified 09 August 2022 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Installer\InstallerHelper;
use Joomla\CMS\Language\Text;
use Sobi\C;
use Sobi\FileSystem\File;
use Sobi\FileSystem\FileSystem;
use Sobi\Lib\Factory;
use Sobi\Utils\StringUtils;

/**
 * Class SPJoomlaInstaller
 */
class SPJoomlaInstaller
{
	/**
	 * @var null
	 */
	protected $error = null;
	/**
	 * @var null
	 */
	protected $errorType = null;
	/**
	 * @var null
	 */
	protected $id = null;
	/**
	 * @var null
	 */
	protected $definition = null;
	/**
	 * @var int
	 */
	private $c = 0;

	/**
	 * SPJoomlaInstaller constructor.
	 */
	public function __construct()
	{
	}

	/**
	 * @param $def
	 *
	 * @return array|mixed
	 * @throws \Sobi\Error\Exception|\SPException
	 */
	public function remove( $def )
	{
		$type = $def->getElementsByTagName( 'type' )->item( 0 )->nodeValue;
		$eid = $def->getElementsByTagName( 'id' )->item( 0 )->nodeValue;
		$name = $def->getElementsByTagName( 'name' )->item( 0 )->nodeValue;
		if ( $type == 'module' || $type == 'plugin' ) {
			$name = Text::_( $name );
		}

		switch ( $type ) {
			case 'module':
				$result = $this->removeModule( $def->getElementsByTagName( 'id' )->item( 0 )->nodeValue );
				break;
			case 'plugin':
				$result = $this->removePlugin( $def->getElementsByTagName( 'id' )->item( 0 )->nodeValue );
				break;
		}
		if ( $result ) {
			Factory::Db()->delete( 'spdb_plugins', [ 'pid' => $eid, 'type' => $type ], 1 );

			$version = $def->getElementsByTagName( 'version' )->item( 0 )->nodeValue;
			$tag = $def->getElementsByTagName( 'tag' )->item( 0 )->nodeValue;
			$version = $tag ? $version . ' ' . $tag : $version;
			$params = [ 'name' => $name . ' ' . $version, 'type' => $type ];
			SPFactory::history()->logAction( SPC::LOG_REMOVE, 0, 0,
			                                 'application',
			                                 C::ES,
			                                 $params
			);

			return Sobi::Txt( 'CMS_EXT_REMOVED', $name );
		}

		return [ 'msg' => Sobi::Txt( 'CMS_EXT_NOT_REMOVED', $name ), 'msgtype' => 'error' ];
	}

	/**
	 * @param $module
	 *
	 * @return bool
	 * @throws \Sobi\Error\Exception
	 */
	public function removeModule( $module )
	{
		$id = Factory::Db()->select( 'id', '#__modules', [ 'module' => $module ] )->loadResult();
		if ( $id ) {
			if ( $this->removeExt( 'module', $id ) ) {
				Factory::Db()->delete( 'spdb_plugins', [ 'pid' => $module ] );

				return true;
			}
		}

		return false;
	}

	/**
	 * @param $plugin
	 *
	 * @return bool
	 * @throws \Sobi\Error\Exception
	 */
	public function removePlugin( $plugin )
	{
		$pluginArr = explode( '_', $plugin, 2 );
		$id = Factory::Db()->select( 'id', '#__plugins', [ 'folder' => $pluginArr[ 0 ], 'element' => $pluginArr[ 1 ] ] )->loadResult();
		if ( $id ) {
			if ( $this->removeExt( 'plugin', $id ) ) {
				Factory::Db()->delete( 'spdb_plugins', [ 'pid' => $plugin ] );

				return true;
			}
		}

		return false;
	}

	/**
	 * @param $type
	 * @param $id
	 *
	 * @return bool
	 */
	protected function removeExt( $type, $id )
	{
		return Installer::getInstance()->uninstall( $type, $id );
	}

	/**
	 * @param $def
	 * @param $files
	 * @param $dir
	 *
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception|\DOMException
	 */
	public function install( $def, $files, $dir )
	{
		switch ( $def->documentElement->getAttribute( 'type' ) ) {
			case 'language':
				$msg = $this->installLanguage( $def, $dir );
				break;
			default:
				$msg = $this->installExtension( $def, $dir );
				break;
		}

		return $msg;
	}

	/**
	 * Installation of Joomla modules/plugins.
	 *
	 * @param $def
	 * @param $dir
	 *
	 * @return array
	 * @throws SPException|\DOMException
	 */
	protected function installExtension( $def, $dir )
	{
		$this->checkRequirements( $def );
		$installer = Installer::getInstance();
		$type = InstallerHelper::detectType( $dir );
		$xp = new DOMXPath( $def );
		try {
			if ( !$installer->install( $dir ) ) {
				$this->error = Sobi::Txt( 'CMS_REPORTSERROR' );
				$this->errorType = C::ERROR_MSG;

				return [ 'msg' => $this->error, 'msgtype' => C::ERROR_MSG ];
			}
			else {
				// it was core update - break now
				if ( $type == 'component' ) {
					SPFactory::cache()->cleanAll();

					return [ 'msg' => Sobi::Txt( 'CMS_SOBIPRO_UPDATE_INSTALLED', $def->getElementsByTagName( 'version' )->item( 0 )->nodeValue ), 'msgtype' => C::SUCCESS_MSG ];
				}
				$extname = $def->getElementsByTagName( 'name' )->item( 0 )->nodeValue;
				$this->id = StringUtils::Nid( $extname );
				if ( $type == 'module' || $type == 'plugin' ) {
					$extname = Text::_( $extname );
				}
				$msg = Sobi::Txt( 'CMSEX_INSTALLED', $type, $extname );

				$id = $xp->query( '//filename[@module|@plugin]' )->item( 0 );
				if ( $id ) {
					$this->id = strlen( $id->getAttribute( 'module' ) ) ? $id->getAttribute( 'module' ) : $id->getAttribute( 'plugin' );
					if ( strlen( $def->documentElement->getAttribute( 'group' ) ) ) {
						$this->id = $def->documentElement->getAttribute( 'group' ) . '_' . $this->id;
					}
					if ( $this->id ) {
						$this->definition = new DOMDocument();
						$this->definition->formatOutput = true;
						$this->definition->preserveWhiteSpace = false;
						$this->definition->appendChild( $this->definition->createElement( 'SobiProApp' ) );
						$root = $this->definition->getElementsByTagName( 'SobiProApp' )->item( 0 );
						$root->appendChild( $this->definition->createElement( 'id', $this->id ) );
						$root->appendChild( $this->definition->createElement( 'type', $type ) );
						$root->appendChild( $this->definition->createElement( 'name', $def->getElementsByTagName( 'name' )->item( 0 )->nodeValue ) );
						$root->appendChild( $this->definition->createElement( 'uninstall', 'cms.base.installer:remove' ) );
						$this->definition->appendChild( $root );
						$dir = SPLoader::dirPath( 'etc.installed.' . $type . 's', 'front', false );
						if ( !( FileSystem::Exists( $dir ) ) ) {
							FileSystem::Mkdir( $dir );
						}
						$path = $dir . '/' . $this->id . '.xml';
						$file = new File( $path );
						$this->definition->normalizeDocument();
						$file->content( $this->definition->saveXML() );
						$file->save();
						$this->storeData( $type, $def );
					}

					$version = $def->getElementsByTagName( 'version' )->item( 0 )->nodeValue;
					$tag = C::ES;
					if ( $def->getElementsByTagName( 'tag' )->length ) {
						$tag = $def->getElementsByTagName( 'tag' )->item( 0 )->nodeValue;
					}
					$version = $tag ? $version . ' ' . $tag : $version;
					$params = [ 'name' => $extname . ' ' . $version, 'type' => $type ];
					SPFactory::history()->logAction( SPC::LOG_INSTALL, 0, 0,
					                                 'application',
					                                 C::ES,
					                                 $params
					);

					return [ 'msg' => $msg, 'msgtype' => C::SUCCESS_MSG ];
				}
				else {
					$this->error = Sobi::Txt( 'CMS_EXT_NOT_INSTALLED' ) . ' ' . 'Installer file wrong.';
					$this->errorType = C::ERROR_MSG;

					return [ 'msg' => $this->error, 'msgtype' => C::ERROR_MSG ];
				}
			}
		}
		catch ( Sobi\Error\Exception $x ) {
			$this->error = Sobi::Txt( 'CMS_EXT_NOT_INSTALLED' ) . ' ' . $x->getMessage();
			$this->errorType = C::ERROR_MSG;

			return [ 'msg' => $this->error, 'msgtype' => C::ERROR_MSG ];
		}
	}

	/**
	 * @param $type
	 * @param $def
	 *
	 * @throws \Sobi\Error\Exception
	 */
	protected function storeData( $type, $def )
	{
		$extname = $def->getElementsByTagName( 'name' )->item( 0 )->nodeValue;
		$description = $def->getElementsByTagName( 'description' )->item( 0 )->nodeValue;
		if ( $type == 'module' || $type == 'plugin' ) {
			$extname = Text::_( $extname );
			$description = Text::_( $description );
		}
		$version = $def->getElementsByTagName( 'version' )->item( 0 )->nodeValue;
		$tag = C::ES;
		if ( $def->getElementsByTagName( 'tag' )->length && $type != 'language' ) {
			$tag = $def->getElementsByTagName( 'tag' )->item( 0 )->nodeValue;
		}

		$version = $tag ? $version . ' ' . $tag : $version;

		Factory::Db()->insertUpdate(
			'spdb_plugins',
			[
				'pid'         => $this->id,
				'name'        => $extname,
				'version'     => $version,
				'description' => $description,
				'author'      => $def->getElementsByTagName( 'author' )->item( 0 )->nodeValue,
				'authorUrl'   => $def->getElementsByTagName( 'authorUrl' )->item( 0 )->nodeValue,
				'authorMail'  => $def->getElementsByTagName( 'authorEmail' )->item( 0 )->nodeValue,
				'enabled'     => 1, 'type' => $type, 'depend' => null,
			]
		);
	}

	/**
	 * @param $def
	 * @param $dir
	 *
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception|\DOMException
	 */
	protected function installLanguage( $def, $dir )
	{
		$this->checkRequirements( $def );
		$this->definition = new DOMDocument();
		$this->definition->formatOutput = true;
		$this->definition->preserveWhiteSpace = false;
		$this->definition->appendChild( $this->definition->createElement( 'SobiProApp' ) );
		$Install = $this->definition->createElement( 'installLog' );
		$Files = $this->definition->createElement( 'files' );
		$filesLog = [];
		$this->id = $def->getElementsByTagName( 'tag' )->item( 0 )->nodeValue;

		if ( $def->getElementsByTagName( 'administration' )->length ) {
			$this->langFiles( 'administration', $def, $dir, $filesLog );
		}
		if ( $def->getElementsByTagName( 'site' )->length ) {
			$this->langFiles( 'site', $def, $dir, $filesLog );
		}
		$this->storeData( 'language', $def );
		$dir = SPLoader::dirPath( 'etc.installed.languages', 'front', false );
		if ( !( FileSystem::Exists( $dir ) ) ) {
			FileSystem::Mkdir( $dir );
		}
		foreach ( $filesLog as $file ) {
			$Files->appendChild( $this->definition->createElement( 'file', $file ) );
		}

		$Install->appendChild( $Files );
		$root = $this->definition->getElementsByTagName( 'SobiProApp' )->item( 0 );
		$root->appendChild( $this->definition->createElement( 'id', $this->id ) );
		$root->appendChild( $this->definition->createElement( 'type', 'language' ) );
		$root->appendChild( $this->definition->createElement( 'name', $def->getElementsByTagName( 'name' )->item( 0 )->nodeValue ) );
		$root->appendChild( $Install );
		$this->definition->appendChild( $root );
		$path = "$dir/$this->id.xml";
		$file = new File( $path );
		$this->definition->normalizeDocument();
		$file->content( $this->definition->saveXML() );
		$file->save();

		$name = $def->getElementsByTagName( 'name' )->item( 0 )->nodeValue;
		$version = $def->getElementsByTagName( 'version' )->item( 0 )->nodeValue;
		$params = [ 'name' => $name . ' ' . $version, 'type' => 'language' ];
		SPFactory::history()->logAction( SPC::LOG_INSTALL, 0, 0,
		                                 'application',
		                                 C::ES,
		                                 $params
		);
		if ( !( $this->error ) ) {
			return [ 'msg' => Sobi::Txt( 'LANG_INSTALLED', $name ), 'msgtype' => C::SUCCESS_MSG ];
		}
		else {
			return [ 'msg' => Sobi::Txt( 'LANG_INSTALLED', $def->getElementsByTagName( 'name' )->item( 0 )->nodeValue ) . "\n" . $this->error, 'msgtype' => $this->errorType ];
		}
	}

	/**
	 * @param $tag
	 * @param $def
	 * @param $dir
	 * @param $FilesLog
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function langFiles( $tag, $def, $dir, &$FilesLog )
	{
		$target = ( $tag == 'administration' ) ?
			implode( '/', [ SOBI_ROOT, 'administrator', 'language', $this->id ] ) :
			implode( '/', [ SOBI_ROOT, 'language', $this->id ] );
		if ( !( file_exists( $target ) ) ) {
			$this->error = Sobi::Txt( 'LANG_INSTALL_NO_CORE', $this->id );
			$this->errorType = C::WARN_MSG;
			FileSystem::Mkdir( $target );
		}
		$files = $def
			->getElementsByTagName( $tag )
			->item( 0 )
			->getElementsByTagName( 'files' )
			->item( 0 );
		$folder = $files->getAttribute( 'folder' );
		$folder = $dir . $folder . '/';
		foreach ( $files->getElementsByTagName( 'filename' ) as $file ) {
			if ( ( file_exists( $folder . $file->nodeValue ) ) ) {
				if ( !( FileSystem::Copy( $folder . $file->nodeValue, $target . '/' . $file->nodeValue ) ) ) {
					SPFactory::message()->error( Sobi::Txt( 'Cannot copy %s to %s', $folder . $file->nodeValue, $target . '/' . $file->nodeValue ), false );
				}
				else {
					$FilesLog[] = str_replace( [ '//', SOBI_ROOT ], [ '/', C::ES ], $target . '/' . $file->nodeValue );
				}
			}
			else {
				SPFactory::message()->error( Sobi::Txt( 'File %s does not exist!', $folder . $file->nodeValue ), false );
			}
		}
	}

	/**
	 * @param $def
	 *
	 * @throws SPException
	 */
	protected function checkRequirements( $def )
	{
		$xp = new DOMXPath( $def );
		$requirements = $xp->query( '//SobiPro/requirements/*' );
		if ( $requirements && ( $requirements instanceof DOMNodeList ) ) {
			$reqCheck =& SPFactory::Instance( 'services.installers.requirements' );
			$reqCheck->check( $requirements );
		}
	}
}
