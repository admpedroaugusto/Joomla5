<?php
/**
 * @package: SobiPro Library
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2022 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 10-Jun-2010 by Radek Suski
 * @modified 10 August 2022 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadView( 'view', true );

/**
 * Class SPExtensionsView
 */
class SPExtensionsView extends SPAdmView
{
	/**
	 * @throws ReflectionException
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function display()
	{
		switch ( $this->get( 'task' ) ) {
			case 'manage':
				$this->browse();
				$this->determineTemplate( 'extensions', 'section' );
				break;
		}
		parent::display();
	}

	/**
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function browse()
	{
		/* create the header */
		$list =& $this->get( 'applications' );
		$plugins = [];
		if ( count( $list ) ) {
			$c = 0;
			foreach ( $list as $plugin ) {
				$plugin[ 'id' ] = $plugin[ 'type' ] . '.' . $plugin[ 'pid' ];
				$plugins[ $c++ ] = $plugin;
			}
		}
		$this->assign( $plugins, 'applications' );
		$sectionName = Sobi::Section( true );
		$this->assign( $sectionName, 'section' );
	}

	/**
	 * @param $title
	 *
	 * @return mixed|string|string[]
	 * @deprecated since 2.0
	 */
	public function setTitle( $title )
	{
//		$title = parent::setTitle( $title );

		return $title;
	}
}
