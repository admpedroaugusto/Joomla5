<?php
/**
 * @package SobiPro Library
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
 * @modified 15 September 2023 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

use Sobi\C;
use Sobi\Input\Input;
use Sobi\Utils\StringUtils;

/**
 * Class SPAdmSiteMenu
 */
final class SPAdmSiteMenu
{
	use \SobiPro\Helpers\MenuTrait;

	/**
	 * @var array
	 */
	private $_sections = [];
	/**
	 * @var int
	 */
	private $_sid = 0;
	/**
	 * @var array
	 */
	private $_view = [];
	/**
	 * @var bool|mixed|string|null
	 */
	private $_task = C::ES;
	/**
	 * @var null
	 */
	private $_open = null;
	/**
	 * @var array
	 */
	private $_custom = [];

	/**
	 * SPAdmSiteMenu constructor.
	 *
	 * @param string $task
	 * @param int $sid
	 *
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function __construct( string $task = C::ES, int $sid = 0 )
	{
		SPFactory::header()->addJsFile( 'menu', true );
		$this->_task = $task ? : Input::Task();
		$this->_sid = $sid;
		SPFactory::registry()->set( 'adm_menu', $this );
	}

	/**
	 * @param string $name
	 * @param array $section
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function addSection( string $name, array $section )
	{
        Sobi::Trigger( 'SectionName', 'AdmMenu', [ &$name, $section ] );
		if ( $name == 'MENU.SECTION.APPS' ) {
			/** @var SPExtensionsCtrl $extensionsCtrl */
			$extensionsCtrl = SPFactory::Controller( 'extensions', true );
			/* leave it here to support older applications */
			$links = $extensionsCtrl->appsMenu();
			if ( count( $links ) ) {
				$section = array_merge( $section, $links );
			}
		}
		else {
			if ( $name == 'MENU.SECTION.TEMPLATES' && Sobi::Section() && Sobi::Cfg( 'section.template', SPC::DEFAULT_TEMPLATE ) ) {
				/** @var SPTemplateCtrl $templateCtrl */
				$templateCtrl = SPFactory::Controller( 'template', true );
				$this->_custom[ $name ][ 'after' ][] = $templateCtrl->getTemplateTree( Sobi::Cfg( 'section.template', SPC::DEFAULT_TEMPLATE ) );
			}
		}
		$this->_sections[ $name ] =& $section;
	}

	/**
	 * @param string $section
	 * @param string $html
	 * @param bool $before
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function addCustom( string $section, $html, bool $before = false )
	{
        $index = $before ? 'before' : 'after';
        Sobi::Trigger( 'Custom', 'AdmMenu', [ &$html, $section ] );
        $this->_custom[ $section ][ $index ][] = $html;
	}

	/**
	 * @return string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function display()
	{
		$this->_view[] = "\n<!-- Start of SobiPro left side menu -->";
		$this->_view[] = '<div class="dk-menu-container"><div class="dk-menu-wrapper">'; //id=SPaccordionTabs

		$this->_view[] = '<div class="card mb-3">';
		$this->_view[] = '<div class="card-body">';

		/* the menu toggle area */
		$this->_view[] = '<div id="spctrl-togglemenu" class="sp-menu-toggle mb-2">';
		$this->_view[] = '<button class="btn btn-primary w-100 btn-sm" id="spctrl-togglemenu-btn" type="button" aria-label="' . Sobi::Txt( "ACCESSIBILITY.TOGGLE_MENU" ) . '">';
		$this->_view[] = '<span id="spctrl-togglemenu-icon" class="fas fa-toggle-on" aria-hidden="true"></span>';
		$this->_view[] = '<span>' . Sobi::Txt( 'TB.SIDEMENU' ) . '</span>';
		$this->_view[] = '</button></div>';  /* close sp-menu-toggle */

		/* the SobiPro logo */
		$media = Sobi::Cfg( 'media_folder_live' );
		$name = 'sobipro';
		$this->_view[] = "<div class='text-center'><a href=\"https://www.Sigsiu.NET\" title=\"Sigsiu.NET GmbH - Software Development\"><img src=\"$media/$name.png\" alt=\"Sigsiu.NET GmbH - Software Development\" class=\"img-fluid\" /></a></div>";

		$this->_view[] = '</div></div>';    /* close card body and card */

		/* the menu items */
		$fs = C::ES;
		if ( count( $this->_sections ) ) {
			if ( $this->_task == 'section.view' ) {
				$this->_open = 'MENU.SECTION.ENT_CAT';
			}
			$this->_view[] = '<div class="accordion mb-2" id="sp-menu">';
			foreach ( $this->_sections as $section => $list ) {
				$sid = StringUtils::Nid( $section );
				$nid = ltrim( strstr( $sid, '-' ), '-' );
				$in = C::ES;
				if ( !$fs ) {
					$fs = $sid;
				}
				if ( !$this->_open && array_key_exists( $this->_task, $list ) ) {
					$this->_open = $sid;
					$in = 'show';
				}
				if ( $this->_open && $section == $this->_open ) {
					$in = 'show';
				}
				if ( !$this->_open && array_key_exists( $this->_task, $list ) ) {
					$in = 'show';
				}
				$ariaexpanded = $in == 'show' ? 'true' : 'false';
				$collapsed = $in == 'show' ? C::ES : 'collapsed';
				$arialabelledby = 'heading-' . $sid;

				$this->_view[] = '<div class="accordion-item ' . $nid . '">';
				$this->_view[] = '<div class="accordion-header" id="' . $arialabelledby . '">';
				$this->_view[] = '<button class="accordion-button ' . $collapsed . '" data-bs-toggle="collapse" type="button" data-bs-target="#' . $sid . '" aria-expanded="' . $ariaexpanded . '" aria-controls="' . $sid . '">';
				$this->_view[] = Sobi::Txt( $section );
				$this->_view[] = '</button>';
				$this->_view[] = '</div>';  /* close card-header */
				$this->_view[] = '<div id="' . $sid . '" class="collapse ' . $in . '" data-bs-parent="#sp-menu" aria-labelledby="' . $arialabelledby . '">';
				$this->_view[] = '<div class="accordion-body">';
				$this->_view[] = $this->section( $list, $section );
				$this->_view[] = '</div>';  /* close accordion-body */
				$this->_view[] = '</div>';  /* close collapse container */
				$this->_view[] = '</div>';  /* close accordion-item */
			}
			$this->_view[] = '</div>';  /* close accordion */
		}
		$this->_view[] = '<div class="card brand" style="display: inherit;"><div class="card-footer">© <a href="https://www.sigsiu.net">Sigsiu.NET GmbH</a></div></div>';

		$this->_view[] = "</div></div>";    /* close dk-menu-container and dk-menu-wrapper */
		$this->_view[] = "<!-- End of SobiPro left side menu -->\n";

		return implode( "\n", $this->_view );
	}

	/**
	 * @param string $open
	 */
	public function setOpen( string $open )
	{
		$this->_open = $open;
	}

	/**
	 * @param array $section
	 * @param string $tab
	 *
	 * @return string
	 * @throws SPException
	 */
	private function section( array $section, string $tab ): string
	{
		$output = C::ES;
		if ( isset( $this->_custom[ $tab ][ 'before' ] ) && is_array( $this->_custom[ $tab ][ 'before' ] ) ) {
			foreach ( $this->_custom[ $tab ][ 'before' ] as $html ) {
				$output .= "\n$html";
			}
		}
		if ( count( $section ) ) {
			$output .= "<div class='list-group list-group-flush'>";
			foreach ( $section as $pos => $label ) {
				if ( strlen( $label ) < 3 ) {
					$label = str_replace( '.', '_', $pos );
				}
				$label = Sobi::Txt( $label );
				if ( $this->_sid ) {
					$url = Sobi::Url( [ 'task' => $pos, 'pid' => $this->_sid ] );
				}
				else {
					$url = Sobi::Url( [ 'task' => $pos ] );
				}
				$appclass = C::ES;
				if ( !$this->isCoreTask( $tab, $pos ) ) {
					$appclass = 'dk-menu-app';
				}
				$class = 'list-group-item list-group-item-action';
				if ( Input::Task() == $pos || $this->_task == $pos ) {
					$class .= ' active';
				}
				$output .= "<a href=\"$url\" class=\"$class $appclass\">$label</a>";

			}
			$output .= "</div>";
		}
		if ( isset( $this->_custom[ $tab ][ 'after' ] ) && is_array( $this->_custom[ $tab ][ 'after' ] ) ) {
			foreach ( $this->_custom[ $tab ][ 'after' ] as $html ) {
				$output .= "$html";
			}
		}

		return $output;
	}
}