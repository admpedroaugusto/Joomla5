<?php
/**
 * @package: SobiPro multi-directory component with content construction support
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2021 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/GPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/gpl.html and https://www.sigsiu.net/licenses.
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * @created 01 August 2012 by Radek Suski
 * @modified 02 June 2021 by Sigrid Suski
 */

use Joomla\CMS\Factory;
use Sobi\Input\Input;
use SobiPro\Autoloader;

defined( '_JEXEC' ) || exit( 'Restricted access' );
require_once( __DIR__ . '/sobiconst.php' );
define( 'SOBI_ACL', 'adm' );

/* load Bootstrap for the back-end */
if ( SOBI_CMS == 'joomla4' ) {
	Factory::getApplication()
		->getDocument()
		->getWebAssetManager()
		//->registerAndUseScript( 'jquery' )
		->useScript( 'bootstrap.alert' )
//		->useScript( 'bootstrap.button' )
//		->useScript( 'bootstrap.carousel' )
		->useScript( 'bootstrap.collapse' )
//		->useScript( 'bootstrap.dropdown' )
		->useScript( 'bootstrap.modal' )
		->useScript( 'bootstrap.popover' )
//		->useScript( 'bootstrap.scrollspy' )
		->useScript( 'bootstrap.tab' )
		->useScript( 'bootstrap.toast' );
}
require_once( SOBI_PATH . '/lib/base/fs/loader.php' );
include_once( SOBI_PATH . '/Library/Autoloader.php' );

Autoloader::Instance()->register();

SPLoader::loadController( 'interface' );
SPLoader::loadClass( 'base.filter' );
SPLoader::loadClass( 'base.request' );
$class = SPLoader::loadController( 'adm.sobipro' );
$sobi = new $class( Input::Task() );
$sobi->execute();
