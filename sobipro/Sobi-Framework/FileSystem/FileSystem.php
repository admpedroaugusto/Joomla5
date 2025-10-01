<?php
/**
 * @package: Sobi Framework
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2021 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created  Thu, Dec 1, 2016 by Radek Suski
 * @modified 08 July 2021 by Sigrid Suski
 */
//declare( strict_types=1 );

namespace Sobi\FileSystem;

use Sobi\Lib\Factory;

defined( 'SOBI' ) || exit( 'Restricted access' );

/**
 * Class FileSystem
 * @package Sobi\FileSystem
 * @method static Exists( string $file ): bool
 * @method static Clean( string $file, $safe = false ): string
 * @method static GetExt( string $file ): string
 * @method static GetFileName( string $file ): string
 * @method static Copy( string $source, string $destination )
 * @method static Delete( string $file ): bool
 * @method static Move( string $source, string $destination ): bool
 * @method static Read( string $file ): string
 * @method static FixPath( string $path ): string
 * @method static FixUrl( string $url ): string
 * @method static Write( string $file, string $buffer, $append = false ): bool
 * @method static Upload( string $name, string $destination ): bool
 * @method static Chmod( string $path, int $hex ): bool
 * @method static Mkdir( string $path, $mode = 0755 ): bool
 * @method static Rmdir( string $path ): bool
 * @method static Readable( string $path ): bool
 * @method static Writable( string $path ): bool
 * @method static Owner( string $path ): int
 * @method static Rename( string $source, string $destination ): bool
 * @method static LoadXML( string $file, int $options = 0 ): \DOMDocument
 * @method static LoadIniFile( string $path, bool $sections = true, bool $skipCustom = false, $mode = INI_SCANNER_NORMAL ): array
 * @method static  WriteIniFile( string $path, array $values ): bool
 */
abstract class FileSystem
{
	public static function __callStatic( $name, $arguments )
	{
		return call_user_func_array( [ Factory::Fs(), lcfirst( $name ) ], $arguments );
	}
}
