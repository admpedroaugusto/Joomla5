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
 * @created 24.04.21 by Radek Suski
 * @modified 26 April 2021 by Radek Suski
 */
declare( strict_types=1 );

namespace SobiPro\Controllers;
use SobiPro\Models\Entry as Model;

require_once __DIR__ . '/../../lib/ctrl/entry.php';

/** @todo ^^ Remove after full transition ^^ */
class Entry extends \SPEntryCtrl
	/** @todo ^^ Remove after full transition ^^ */
{
	/** @var \SobiPro\Models\Entry */
	protected $_model;

//	public function setModel( $model )
//	{
//		$this->_model = new Model();
//		\Sobi::Trigger( 'Entry', __FUNCTION__, [ &$this->_model ] );
//		/** Legacy */
//		\Sobi::Trigger( 'SPEntryCtrl', __FUNCTION__, [ &$this->_model ] );
//	}
}
