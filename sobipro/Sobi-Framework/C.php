<?php
/**
 * @package Sobi Framework
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006-2025 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according to section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created Thu, Dec 1, 2016 11:53:09 by Radek Suski
 * @modified 31 March 2025 by Sigrid Suski
 */

namespace Sobi;

defined( 'SOBI' ) || exit( 'Restricted access' );

/**
 * Class C
 * @package Sobi
 */
abstract class C
{
	public const VERSION = '2.2.3';

	public const FS_APP = FILE_APPEND;
	public const WARNING = E_USER_WARNING;
	public const NOTICE = E_USER_NOTICE;
	public const ERROR = E_USER_ERROR;
	public const NO_VALUE = -90001;
	public const ERROR_MSG = 'error';
	public const WARN_MSG = 'warning';
	public const NOTICE_MSG = 'warning';
	public const INFO_MSG = 'info';
	public const SUCCESS_MSG = 'success';
	public const GLOBAL = 2;
	public const YES = 1;
	public const NO = 0;
	public const ROOT = JPATH_ROOT;
	public const DS = DIRECTORY_SEPARATOR;
	public const ES = '';
	public const BOOTSTRAP2 = 2;
	public const BOOTSTRAP3 = 3;
	public const BOOTSTRAP4 = 4;
	public const BOOTSTRAP5 = 5;
	public const BOOTSTRAP6 = 6;
	public const NONE = 0;
	public const BOOTSTRAP_NONE = 0;
	public const BOOTSTRAP_LOCAL = 1;
	public const BOOTSTRAP_CDN = 2;
	public const BOOTSTRAP_STYLES = 3;
	public const BOOTSTRAP_JS = 4;
}
