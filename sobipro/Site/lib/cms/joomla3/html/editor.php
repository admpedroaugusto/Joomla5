<?php
/**
 * @package SobiPro Library
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006–2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according to section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 21-Jan-2009 by Radek Suski
 * @modified 23 August 2024 by Sigrid Suski
 */

use Joomla\CMS\Editor\Editor;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Sobi\C;
use Sobi\Input\Input;

/**
 * class SPCMSEditor
 */
class SPCMSEditor
{
	/**
	 * @param string $name The control name
	 * @param string $html The contents of the text area
	 * @param string $width The width of the text area (px or %)
	 * @param string $height The height of the text area (px or %)
	 * @param bool $buttons True and the editor buttons will be displayed
	 * @param array $params Associative array of editor parameters
	 *
	 * @return string
	 */
	public function display( $name, $html, $width, $height, $buttons, $params = [] )
	{
		if ( Input::Cmd( 'format' ) != 'raw' ) {
//			$user = Factory::getUser();
//			$editor = Editor::getInstance($user->getParam('editor', Factory::getConfig()->get('editor')));
//			$editor->display('text', $this->item->text, '', '', '', '', false);

			HTMLHelper::_( 'behavior.core' );
			$editor = Editor::getInstance( Factory::getConfig()->get( 'editor' ) );

			/* public function display($name, $html, $width, $height, $col, $row, $buttons = true, $id = null, $asset = null, $author = null, $params = array()) */

			return $editor->display( $name, $html, $width, $height, 75, 20, $buttons, null, null, null, $params );
		}

		return C::ES;
	}
}
