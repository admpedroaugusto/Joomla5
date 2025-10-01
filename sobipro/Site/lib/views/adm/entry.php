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
 * @created 10-Jan-2009 by Radek Suski
 * @modified 08 February 2022 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadView( 'view', true );

/**
 * Class SPEntryAdmView
 */
class SPEntryAdmView extends SPAdmView
{
	/**
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function _display()
	{
//		SPLoader::loadClass( 'html.tooltip' );
		switch ( $this->get( 'task' ) ) {
			case 'edit':
				$languages = $this->languages();
				$this->assign( $languages, 'languages-list' );
				$multiLang = Sobi::Cfg( 'lang.multimode', false );
				$this->assign( $multiLang, 'multilingual' );
			/* no break by intention */
			case 'add':
				$this->edit();
				break;
		}
		parent::display();
	}

	/**
	 * @throws SPException
	 */
	private function edit()
	{
		$id = $this->get( 'entry.id' );
		if ( $id ) {
			$this->addHidden( $id, 'entry.id' );
		}
		$sid = SPRequest::int( 'pid' ) ? SPRequest::int( 'pid' ) : SPRequest::sid();
		$catChooserUrl = Sobi::Url( [ 'task' => 'category.chooser', 'sid' => $sid, 'out' => 'html', 'multiple' => 1 ], true );
		$this->assign( $catChooserUrl, 'cat_chooser_url' );
	}

	/**
	 * @param $title
	 *
	 * @deprecated since 2.0
	 */
	public function setTitle( $title )
	{
	}
}
