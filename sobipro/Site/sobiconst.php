<?php
/**
 * @package SobiPro multi-directory component with content construction support
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2023 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/GPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/gpl.html and https://www.sigsiu.net/licenses.
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * @created 07 May 2021 by Sigrid Suski
 * @modified 08 November 2023 by Sigrid Suski
 */

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Uri\Uri;

defined( '_JEXEC' ) || exit( 'Restricted access' );

defined( 'SOBIPRO' ) || define( 'SOBIPRO', true );
defined( 'SOBI_TESTS' ) || define( 'SOBI_TESTS', false );
defined( 'SOBI_TASK' ) || define( 'SOBI_TASK', 'task' );
defined( 'SOBI_ROOT' ) || define( 'SOBI_ROOT', JPATH_ROOT );
defined( 'SOBIPRO_FOLDER' ) || define( 'SOBIPRO_FOLDER', '/components/com_sobipro' );

$version = preg_replace( '/alpha|beta|rc/', '', JVERSION );
if ( !defined( 'SOBI_CMS' ) ) {
	/* as it cannot be installed on Joomla below 3, joomla16 will never be set! */
	define( 'SOBI_CMS', version_compare( $version, '4.0.0', 'ge' ) ?
		'joomla4' : ( version_compare( JVERSION, '3.0.0', 'ge' ) ? 'joomla3' : 'joomla16' )
	);
}
if ( !defined( 'SOBI_ORICMS' ) ) {
	define( 'SOBI_ORICMS', version_compare( $version, '5.0.0', 'ge' ) ?
		'joomla5' : SOBI_CMS );
}

/* the default language is the default language of the front-end */
defined( 'SOBI_DEFLANG' ) || define( 'SOBI_DEFLANG', ComponentHelper::getParams( 'com_languages' )->get( 'site', 'en-GB' ) );
defined( 'SOBI_DEFADMLANG' ) || define( 'SOBI_DEFADMLANG', ComponentHelper::getParams( 'com_languages' )->get( 'administrator', 'en-GB' ) );
//defined( 'SOBI_TRIMMED' ) || define( 'SOBI_TRIMMED', true );

defined( 'SOBI_PATH' ) || define( 'SOBI_PATH', JPATH_ROOT . '/components/com_sobipro' );
defined( 'SOBI_MEDIA' ) || define( 'SOBI_MEDIA', JPATH_ROOT . '/media/sobipro' );
/* needs the backslash as it is missing in the config file */
defined( 'SOBI_IMAGES' ) || define( 'SOBI_IMAGES', JPATH_ROOT . '/images/sobipro/' );

defined( 'SOBI_IMAGES_LIVE' ) || define( 'SOBI_IMAGES_LIVE', Uri::root() . 'images/sobipro/' );
defined( 'SOBI_MEDIA_LIVE' ) || define( 'SOBI_MEDIA_LIVE', Uri::root() . 'media/sobipro/' );

