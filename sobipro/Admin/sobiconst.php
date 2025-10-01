<?php
/**
 * @package: SobiPro multi-directory component with content construction support
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2022 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/GPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/gpl.html and https://www.sigsiu.net/licenses.
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * @created 07 May 2021 by Sigrid Suski
 * @modified 02 August 2022 by Sigrid Suski
 */

defined( '_JEXEC' ) || exit( 'Restricted access' );

defined( 'SOBIPRO_ADM' ) || define( 'SOBIPRO_ADM', true );
require_once( JPATH_ROOT . '/components/com_sobipro/sobiconst.php' );

defined( 'SOBI_ADM_PATH' ) || define( 'SOBI_ADM_PATH', JPATH_ADMINISTRATOR . '/components/com_sobipro' );
$adm = str_replace( JPATH_ROOT, '', JPATH_ADMINISTRATOR );
defined( 'SOBIPRO_ADM_FOLDER' ) || define( 'SOBIPRO_ADM_FOLDER', $adm . '/components/com_sobipro' );
defined( 'SOBI_ADM_FOLDER' ) || define( 'SOBI_ADM_FOLDER', $adm );

