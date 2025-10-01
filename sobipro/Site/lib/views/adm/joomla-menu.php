<?php
/**
 * @package: SobiPro Library
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2020 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 14-Jan-2009 by Radek Suski
 * @modified 05 November 2020 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadView( 'view', true );

use Sobi\Input\Input;

/**
 * Class SPAdmJoomlaMenuView
 */
class SPAdmJoomlaMenuView extends SPAdmView
{
	/**
	 * Creates the functionality box to select on in Joomla menu manager.
	 *
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function functions()
	{
		$functions = $this->get( 'functions' );
		$out = [];
		$section = Input::Int( 'section' );
		$out[] = '<form action="index.php" method="post" class="SobiPro">';
		$out[] = SPHtml_Input::select( 'function', $functions, null, false, [ 'id' => 'spctrl-functions', 'class' => 'w-35' ] );
		$out[] = '<input type="hidden" name="option" value="com_sobipro">';
		$out[] = '<input type="hidden" name="task" value="menu">';
		$out[] = '<input type="hidden" name="tmpl" value="component">';
		$out[] = '<input type="hidden" name="format" value="html">';
		$out[] = '<input type="hidden" name="mid" value="' . Input::Int( 'mid' ) . '">';
		$out[] = '<input type="hidden" name="section" value="' . $section . '">';
		$out[] = '</form>';
		echo implode( "\n", $out );
	}
}
