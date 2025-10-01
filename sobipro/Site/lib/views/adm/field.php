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
 * @modified 01 September 2022 by Sigrid Suski
 */

use Sobi\C;
use Sobi\Lib\Factory;

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadView( 'view', true );

/**
 * Class SPFieldAdmView (Fields Manager)
 */
class SPFieldAdmView extends SPAdmView
{
	/**
	 * @var array
	 */
	private $_templates = [];

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function display()
	{
		switch ( trim( $this->get( 'task' ) ) ) {
			case 'edit':
			case 'add':
				$this->displayForm();
				break;
			case 'list':
			default:
				parent::display();
				break;
		}
	}

	/**
	 * @param $title
	 *
	 * @return void
	 *
	 * @deprecated since 2.0
	 */
	public function setTitle( $title )
	{
//		if ( strstr( SPRequest::task(), '.add' ) ) {
//			$title = str_replace( 'EDIT', 'ADD', $title );
//		}
//		$title = Sobi::Txt( $title, [ 'field' => $this->get( 'field.name' ), 'field_type' => $this->get( 'field.fieldType' ) ] );
//		Sobi::Trigger( 'setTitle', $this->name(), [ &$title ] );
//		SPFactory::header()->setTitle( $title );
//		$this->set( $title, 'site_title' );
	}

	/**
	 * @param $template
	 *
	 * @return $this|SPFieldAdmView
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function & setTemplate( $template )
	{
		if ( !$this->_template ) {
			$this->_template = $template;
		}
		$this->_templates[] = $template;
		Sobi::Trigger( 'setTemplate', $this->name(), [ &$this->_templates ] );

		return $this;
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function displayForm()
	{
		Sobi::Trigger( 'Display', $this->name(), [ &$this ] );
		$action = $this->key( 'action' );

		echo '<div class="SobiPro" id="SobiPro" data-bs="5" data-site="adm">' . "\n";
		echo $action ? "<form action=\"$action\" method=\"post\" name=\"adminForm\" id=\"SPAdminForm\" enctype=\"multipart/form-data\" accept-charset=\"utf-8\" >\n" : C::ES;
		foreach ( $this->_templates as $tpl ) {
			$template = SPLoader::path( $tpl, 'adm.template' );
			if ( !$template ) {
				$tpl = SPLoader::translatePath( $tpl, 'adm.template', false );
				Sobi::Error( $this->name(), SPLang::e( 'CANNOT_LOAD_TEMPLATE_AT', $tpl ), C::ERROR, 500, __LINE__, __FILE__ );
			}
			else {
				include( $template );
			}
		}

		if ( count( $this->_hidden ) ) {
			$this->_hidden[ Factory::Application()->token() ] = 1;
			foreach ( $this->_hidden as $name => $value ) {
				echo "<input type=\"hidden\" name=\"$name\" id=\"SP_$name\" value=\"$value\"/>";
			}
		}
		echo $action ? "</form>\n" : C::ES;
		echo "</div>\n";
		Sobi::Trigger( 'AfterDisplay', $this->name() );
	}
}
