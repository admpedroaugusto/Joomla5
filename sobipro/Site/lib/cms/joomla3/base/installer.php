<?php
/**
 * @package SobiPro Library
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET
 * @copyright Copyright (C) 2006 - 2023 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 *
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 05-Mar-201 by Radek Suski
 * @modified 02 May 2023 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
require_once dirname( __FILE__ ) . '/../../joomla_common/base/installer.php';

use Joomla\CMS\Installer\Installer;
use Sobi\C;
use Sobi\FileSystem\FileSystem;
use Sobi\Lib\Factory;

/**
 *
 */
class SPCmsInstaller extends SPJoomlaInstaller
{
	/**
	 * @param $def
	 *
	 * @return array|mixed
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function remove( $def )
	{
		$type = $def->getElementsByTagName( 'type' )->item( 0 )->nodeValue;
		$eid = $def->getElementsByTagName( 'id' )->item( 0 )->nodeValue;
		$name = $def->getElementsByTagName( 'name' )->item( 0 )->nodeValue;
		$version = null;
		if ( $type == 'module' || $type == 'plugin' ) {
			/* Do not get the translated name from Joomla, therefore get it from the database (Sigrid) */
			$name = Factory::Db()->select( 'name', 'spdb_plugins', [ 'pid' => $eid ] )->loadResult();
			$version = Factory::Db()->select( 'version', 'spdb_plugins', [ 'pid' => $eid ] )->loadResult();
		}

		$id = Factory::Db()
			->select( 'extension_id', '#__extensions', [ 'type' => $type, 'element' => $eid ] )
			->loadResult();
		if ( Installer::getInstance()->uninstall( $type, $id ) ) {
			Factory::Db()->delete( 'spdb_plugins', [ 'pid' => $eid, 'type' => $type ], 1 );

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
	 * @param DOMDocument $def
	 * @param string $dir
	 *
	 * @return array
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function installExt( $def, $dir )
	{
		if ( $def->firstChild->nodeName == 'install' ) {
			$content = $def->saveXML();
			$content = str_replace( [ '<install', '</install>' ], [ '<extension', '</extension>' ], $content );
			FileSystem::Write( $dir . '/temp.xml', $content );
			$def = new DOMDocument();
			$def->load( $dir . '/temp.xml' );
		}

		return parent::installExt( $def, $dir );
	}
}

