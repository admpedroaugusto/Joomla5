<?php
/**
 * @package: Sobi Framework
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2011-2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according to section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created Thu, Dec 1, 2016 by Radek Suski
 * @modified 23 August 2024 by Sigrid Suski
 */

namespace Sobi\Input;

use Joomla\Input\Input;
use Sobi\Lib\Instance;

defined( 'SOBI' ) || exit( 'Restricted access' );

/**
 * Request Class
 */
class Request extends Input
{
	use Instance;

	/**
	 * @param $name
	 * @param $request
	 *
	 * @return void
	 */
	public function setRequest( $name, $request )
	{
		$this->$name = $request;
	}
}
