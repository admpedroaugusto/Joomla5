<?php
/**
 * @package: SobiPro Library
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
 * @created 10-Jun-2010 by Radek Suski
 * @modified 28 January 2021 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadView( 'config', true );

/**
 * Class SPAdmTemplateView
 */
class SPAdmTemplateView extends SPConfigAdmView
{
	/**
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function display()
	{
		switch ( $this->get( 'task' ) ) {
			case 'edit':
				$this->edit();
				break;
		}
		parent::display();
	}

	/**
	 * @param $title
	 *
	 * @deprecated since 2.0
	 */
	public function setTitle( $title )
	{
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function edit()
	{
		$jsFiles = [ 'codemirror.codemirror' ];
		$ext = $this->get( 'file_ext' );
		$mode = null;
		switch ( strtolower( $ext ) ) {
			case 'xsl':
			case 'xml':
				$jsFiles[] = 'codemirror.mode.xml.xml';
				break;
			case 'linc':
			case 'less':
				$jsFiles[] = 'codemirror.mode.less.less';
				break;
			case 'css':
				$jsFiles[] = 'codemirror.mode.css.css';
				break;
			case 'js':
				$jsFiles[] = 'codemirror.mode.javascript.javascript';
				break;
			case 'php':
				$jsFiles[] = 'codemirror.mode.clike.clike';
				$jsFiles[] = 'codemirror.mode.php.php';
				$jsFiles[] = 'codemirror.mode.htmlmixed.htmlmixed';
				$jsFiles[] = 'codemirror.mode.xml.xml';
				$jsFiles[] = 'codemirror.mode.javascript.javascript';
				$jsFiles[] = 'codemirror.mode.css.css';
				$mode = 'application/x-httpd-php';
				break;
			case 'ini':
				$jsFiles[] = 'codemirror.mode.properties.properties';
				break;
		}
		SPFactory::header()
			->addJsFile( $jsFiles )
			->addCssFile( 'codemirror.codemirror' )
			->addJsCode( 'document.addEventListener( "DOMContentLoaded", function( event ) { SPInitTplEditor( "' . $mode . '" ) } );' );
	}
}
