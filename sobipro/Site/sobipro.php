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
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * @created 01 August 2012 by Radek Suski
 * @modified 11 May 2021 by Sigrid Suski
 */

use Joomla\CMS\Factory;
use Sobi\Input\Input;
use SobiPro\Autoloader;

defined( '_JEXEC' ) || exit( 'Restricted access' );

require_once ( __DIR__ . '/sobiconst.php' );
define( 'SOBI_ACL', 'front' );

/* load jQuery */
//if ( SOBI_CMS == 'joomla4' ) {
//	Factory::getApplication()
//		->getDocument()
//		->getWebAssetManager()
//		->registerAndUseScript( 'jquery' );
//}

require_once( SOBI_PATH . '/lib/base/fs/loader.php' );
include_once dirname( __FILE__ ) . '/Library/Autoloader.php';

Autoloader::Instance()->register();

SPLoader::loadController( 'interface' );
SPLoader::loadClass( 'base.filter' );
SPLoader::loadClass( 'base.request' );

// Try to catch direct file calls. Like /directory/piwik.php
if ( preg_match( '/\.php$/', Input::Task() ) || strlen( Input::Task() ) > 50 ) {
	throw new Exception( 'Page Not Found! (invalid task and/or url specified)', 404 );
}
$class = SPLoader::loadController( 'sobipro' );
$sobi = new $class( Input::Task() );
$sobi->execute();
