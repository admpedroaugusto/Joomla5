<?php
/**
 * @package: Sobi Framework
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET
 * @copyright Copyright (C) 2006 - 2021 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See http://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * @created Tue, Mar 2, 2021 10:45:31 by Radek Suski
 * @modified 14 July 2021 by Radek Suski
 */
//declare( strict_types=1 );

namespace Sobi\Interfaces\Application;

use DOMDocument;

interface FileSystemInterface
{
	/**
	 * @param string $file
	 *
	 * @return bool
	 */
	public function exists( string $file ): bool;

	/**
	 * @param string $file
	 * @param bool $safe
	 *
	 * @return string
	 */
	public function clean( string $file, bool $safe = false ): string;

	/**
	 * @param string $file
	 *
	 * @return mixed|string
	 */
	public function getExt( string $file ): string;

	/**
	 * @param string $file
	 *
	 * @return mixed|string
	 */
	public function getFileName( string $file ): string;

	/**
	 * @param string $source
	 * @param string $destination
	 *
	 * @return mixed
	 * @throws \Sobi\Error\Exception
	 */
	public function copy( string $source, string $destination );

	/**
	 * @param string $file
	 *
	 * @return bool|void
	 * @throws \Sobi\Error\Exception
	 */
	public function delete( string $file ): bool;

	/**
	 *     *
	 * @param string $source
	 * @param string $destination
	 *
	 * @return bool
	 */
	public function move( string $source, string $destination ): bool;

	/**
	 * @param string $file
	 *
	 * @return string
	 */
	public function read( string $file ): string;

	/**
	 * @param string $path
	 *
	 * @return string
	 */
	public function fixPath( string $path ): string;

	/**
	 * @param string $url
	 *
	 * @return string
	 */
	public function fixUrl( string $url ): string;

	/**
	 * @param string $file
	 * @param string $buffer
	 * @param bool $append
	 *
	 * @return bool
	 * @throws \Sobi\Error\Exception
	 */
	public function write( string $file, string $buffer, bool $append = false ): bool;

	/**
	 * @param string $name
	 * @param string $destination
	 *
	 * @return bool
	 * @throws \Sobi\Error\Exception
	 */
	public function upload( string $name, string $destination ): bool;

	/**
	 * @param string $path
	 * @param string $hex
	 *
	 * @return bool
	 */
	public function chmod( string $path, int $hex ): bool;

	/**
	 * @param string $path
	 * @param int $mode
	 *
	 * @return bool
	 * @throws \Sobi\Error\Exception
	 */
	public function mkdir( string $path, int $mode = 0755 ): bool;

	/**
	 * @param string $path
	 */
	public function rmdir( string $path ): bool;

	/**
	 * @param string $path
	 *
	 * @return bool
	 */
	public function readable( string $path ): bool;

	/**
	 * @param string $path
	 *
	 * @return bool
	 */
	public function writable( string $path ): bool;

	/**
	 * @param string $path
	 *
	 * @return int
	 */
	public function owner( string $path ): int;

	/**
	 * @param string $source
	 * @param string $destination
	 *
	 * @return bool
	 */
	public function rename( string $source, string $destination ): bool;

	/**
	 * @param string $file
	 * @param int $options
	 *
	 * @return DOMDocument
	 */
	public function loadXML( string $file, int $options = 0 ): DOMDocument;

	/**
	 * @param string $path
	 * @param bool $sections
	 * @param bool $skipCustom - do not try to find overrides
	 * @param int $mode - INI_SCANNER_NORMAL or INI_SCANNER_TYPED
	 *
	 * @return array
	 */
	public function loadIniFile( string $path, bool $sections = true, bool $skipCustom = false, $mode = INI_SCANNER_NORMAL ): array;

	/**
	 * @param string $path
	 * @param array $values
	 *
	 * @return bool
	 */
	public function WriteIniFile( string $path, array $values ): bool;

	/**
	 * @return FileSystemInterface
	 */
	public static function Instance();
}
