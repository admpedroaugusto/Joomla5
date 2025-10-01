<?php
/**
 * @package SobiPro Library
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * @created 05-May-2010 by Radek Suski
 * @modified 12 January 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

/**
 * Class SPC
 */
abstract class SPC
{
	public const FC = 20;
	public const DEFAULT_TEMPLATE = 'default8';
	public const TEMPLATE_STORAGE = 'storage';
	public const TEMPLATE_PATH = 'usr/templates/';
	public const INSTALL_PATH = 'tmp/install/';
	public const REPO_PATH = 'etc/repos/';
	public const TEMPLATE_FRONT = 'front';
	public const DEFAULT_SAMPLES = '/samples';
	public const UPDATES_INTERVALL = 60 * 60 * 12;  // every 12 hours
	public const USERTYPE_VISITOR = 'Visitor';
	public const USERTYPE_ADMINISTRATOR = 'Administrator';
	public const USERTYPE_REGISTERED = 'Registered';

	public const WARNING = E_USER_WARNING;
	public const NOTICE = E_USER_NOTICE;
	public const ERROR = E_USER_ERROR;
	public const NO_VALUE = -90001;
	public const ERROR_MSG = 'error';
	public const WARN_MSG = 'warning';
	public const NOTICE_MSG = 'warning';
	public const INFO_MSG = 'info';
	public const SUCCESS_MSG = 'success';
	public const DEFAULT_DATE = 'm-d-Y H:i:s';
	public const DEFAULT_DB_DATE = 'Y-m-d H:i:s';

	public const LOG_PUBLISH = 'publish';
	public const LOG_UNPUBLISH = 'unpublish';
	public const LOG_ENABLE = 'enable';
	public const LOG_DISABLE = 'disable';
	public const LOG_APPROVE = 'approve';
	public const LOG_UNAPPROVE = 'unapprove';
	public const LOG_REJECT = 'reject';
	public const LOG_DISCARD = 'discard';
	public const LOG_DELETE = 'delete';
	public const LOG_SAVE = 'save';
	public const LOG_EDIT = 'edit';
	public const LOG_ADD = 'add';
	public const LOG_ADDINDIRECT = 'addindirect';
	public const LOG_INSTALL = 'install';
	public const LOG_REMOVE = 'remove';
	public const LOG_DOWNLOAD = 'download';
	public const LOG_REPOINSTALL = 'repoinstall';
	public const LOG_REPOREGISTER = 'register';
	public const LOG_REPOFETCH = 'fetch';
	public const LOG_CLONE = 'clone';
	public const LOG_DUPLICATE = 'duplicate';
	public const LOG_COPY = 'copy';
	public const LOG_REQUIRED = 'required';
	public const LOG_UNREQUIRED = 'unrequired';
	public const LOG_EDITABLE = 'editable';
	public const LOG_UNEDITABLE = 'uneditable';
	public const LOG_FREE = 'free';
	public const LOG_UNFREE = 'unfree';
	public const LOG_ACTION = 'action';
	public const LOG_COPYSTORAGE = 'copystorage';
	public const LOG_COPYFILE = 'copyfile';
}
