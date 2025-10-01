<?php
/**
 * @package: SobiPro Library
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2023 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 10-Jan-2009 by Radek Suski
 * @modified 07 April 2023 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadView( 'section' );

use Sobi\C;
use Sobi\Input\Input;

/**
 * Class SPCategoryView
 */
class SPCategoryView extends SPSectionView implements SPView
{
	/**
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function chooser()
	{
		$pid = $this->get( 'category.parent' );
		$path = C::ES;
		if ( !$pid ) {
			$pid = Input::Sid();
		}
		$this->assign( $pid, 'parent' );
		$id = $this->get( 'category.id' );
		$id = $id ? : $pid;
		if ( $id ) {
			$path = $this->parentPath( $id );
		}
		$this->assign( $path, 'parent_path' );
		$url = Sobi::Url( [ 'task' => 'category.parents', 'out' => 'json', 'format' => 'raw' ], true );
		$this->assign( $url, 'parent_ajax_url' );
		/* @TODO */
		$tpl = str_replace( implode( '/', [ 'usr', 'templates', 'category' ] ), 'views/tpl/', $this->_template . '.php' );
		Sobi::Trigger( 'Display', $this->name(), [ &$this ] );
		include( $tpl );

		Sobi::Trigger( 'AfterDisplay', $this->name() );
	}

	/**
	 * @return void
	 */
	public function icon()
	{
		/* @TODO */
		$tpl = str_replace( implode( '/', [ 'usr', 'templates', 'category' ] ), 'views/tpl/', $this->_template . '.php' );
		include( $tpl );
	}
}
