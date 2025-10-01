<?php
/**
 * @package: Sobi Framework
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET
 * @copyright Copyright (C) 2006 - 2016 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See http://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * @created Wed, Jan 27, 2021 11:43:11 by Radek Suski
 * @modified 14 July 2021 by Radek Suski
 */
//declare( strict_types=1 );

namespace Sobi\Lib;

use Sobi\Application\Joomla;
use Sobi\Interfaces\Application\{FileSystemInterface, MailInterface};
use Sobi\Application\Joomla\{Helper, Installer, Mail, URL, Database\MySQLi, FileSystem as JFileSystem};


class Factory
{
	public static function & Application() : Joomla
	{
		return Joomla::Instance();
	}

	public static function & Db(): MySQLi
	{
		return MySQLi::Instance();
	}

	public static function & ApplicationHelper(): Helper
	{
		return Helper::Instance();
	}

	public static function & ApplicationInstaller(): Installer
	{
		return Installer::Instance();
	}

	public static function & Fs(): FileSystemInterface
	{
		return JFileSystem::Instance();
	}

	public static function & URL(): URL
	{
		return URL::Instance();
	}

	public static function & Mail(): MailInterface
	{
		return Mail::Instance();
	}
}
