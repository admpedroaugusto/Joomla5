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
 * @created 10-Jan-2009 by Radek Suski
 * @modified 18 September 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

use Sobi\C;
use Sobi\Input\Input;
use Sobi\Lib\Factory;
use Sobi\Utils\StringUtils;

use Joomla\CMS\Access\Access as JAccess;
use Joomla\CMS\Factory as JFactory;

/**
 * Class SpAdmToolbar
 */
class SpAdmToolbar
{
	/**
	 * @var null
	 */
	protected $title = null;
	/**
	 * @var null
	 */
	protected $subtitle = null;
	/**
	 * @var null
	 */
	protected $icon = null;
	/**
	 * @var string
	 */
	protected $class = '';
	/**
	 * @var array
	 */
	protected $buttons = [];
	/**
	 * @var null
	 */
	protected $output = null;

	/**
	 * @var bool
	 */
	protected $first = false;

	/** @var array */
	private $icons = [
		'apply'                => 'save',
		'cancel'               => 'ban-circle',
		'exit'                 => 'sign-out-alt',
		'help'                 => 'question',
		'save'                 => 'save',
		'duplicate'            => 'paste',
		'new'                  => 'plus',
		'delete'               => 'trash',
		'actions'              => 'share',
		'enable'               => 'check',
		'disable'              => 'times',
		'publish'              => 'check',
		'hide'                 => 'times',
		'approve'              => 'thumbs-up',
		'revoke'               => 'thumbs-down',
		'entry'                => 'file-alt',
		'category'             => 'folder-open',
		'panel'                => '',
		'config'               => '',
		'acl'                  => '',
		'extensions.installed' => '',
		'options'              => 'bars',
		'sections'             => 'sitemap',
		'template.info'        => '',
		'selected'             => 'check',
		'not-selected'         => 'check-empty',
		'rule'                 => 'user',
	];

	/** @var array */
	private $labels = [
		'apply'                => 'SAVE_ONLY',
		'cancel'               => 'CANCEL',
		'exit'                 => 'EXIT',
		'help'                 => 'HELP',
		'save'                 => 'SAVE_EXIT',
		'duplicate'            => 'SAVE_AS_COPY',
		'new'                  => 'ADD_NEW',
		'delete'               => 'DELETE',
		'actions'              => 'ACTIONS',
		'publish'              => 'PUBLISH',
		'hide'                 => 'UNPUBLISH',
		'enable'               => 'ENABLE',
		'disable'              => 'DISABLE',
		'approve'              => 'APPROVE',
		'revoke'               => 'REVOKE',
		'panel'                => 'CONTROL_PANEL',
		'config'               => 'GLOBAL_CONFIG',
		'acl'                  => 'ACL',
		'extensions.installed' => 'SAM',
		'options'              => 'OPTIONS',
		'sections'             => 'SECTIONS',
		'template.info'        => 'TEMPLATE',
	];

	/**
	 * SpAdmToolbar constructor.
	 */
	private function __construct()
	{
	}

	/**
	 * return SpAdmToolbar
	 */
	public static function & getInstance()
	{
		static $toolbar = null;
		if ( !$toolbar ) {
			$toolbar = new self();
		}

		return $toolbar;
	}

	/**
	 * Sets the data from the toolbar title.
	 *
	 * @param $data
	 */
	public function setToolbarTitle( $data )
	{
		$this->title = $data[ 'title' ];
		$this->subtitle = $data[ 'subtitle' ];
		$this->icon = $data[ 'icon' ];
		$this->class = $data[ 'class' ];
	}

	/**
	 * @param $arr
	 */
	public function addButtons( $arr )
	{
		$this->buttons = $arr;
	}

	/**
	 * Creates the SobiPro toolbar.
	 *
	 * @param array $options
	 *
	 * @return string
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function render( array $options = [] ): string
	{
		if ( is_array( $this->buttons ) && count( $this->buttons ) ) {
			$id = $options[ 'id' ] ?? 'spctrl-adm-toolbar';
			$this->output[] = '<div id="spctrl-spinner" class="dk-spinner hidden"><div class="fas fa-spinner fa-spin fa-lg" aria-hidden="true"></div></div>';

			/* add the trial version banner for SobiPro trial version */
			if ( defined( 'SOBI_TRIMMED' ) ) {
				$this->output[] = '<div class="dk-demo bg-delta text-white text-center shadow-sm demo"><div>SOBIPRO TRIAL VERSION</div></div >';
				$this->class .= ' demo';
			}

			/* Handling for a restricted demo version of SobiPro */
			$demoUserGroups = unserialize( Factory::Db()
				->select( 'sValue', 'spdb_config', [ 'sKey' => 'demoGroups', 'cSection' => 'demoSite' ] )
				->loadResult() );

			if ( is_array( $demoUserGroups ) && count( $demoUserGroups ) ) {
				$currentUser = JFactory::getApplication()->getIdentity() ? : JFactory::getUser();
				$currentUserGroups = JAccess::getGroupsByUser( $currentUser->id, false );
				if ( !$currentUser->authorise( 'core.admin' ) ) {
					foreach ( $demoUserGroups as $group ) {
						if ( in_array( $group, $currentUserGroups ) ) {
							$this->output[] = '<div class="dk-demo bg-danger text-white text-center shadow-sm demo"><div>Permissions have been limited to certain areas. Also, several administrative actions are disabled for security reasons.</div></div >';
							$this->class .= ' demosite';
							break;
						}
					}
				}
			}

			$this->output[] = '<div class="dk-subhead mb-3 shadow-sm ' . $this->class . '" id="' . $id . '">'; /* 1. container */
			$this->output[] = '<div id="sp-container-collapse" class="container-collapse"></div>';
			$this->output[] = '<div class="row">'; /* 2. container */
			$this->output[] = '<div class="col-lg-12">'; /* 3. container */
			$this->output[] = '<nav aria-label="' . Sobi::Txt( "ACCESSIBILITY.TOOLBAR" ) . '" tabindex="-1">'; /* nav container */
			$this->output[] = '<div class="btn-toolbar d-flex gap-1" role="toolbar">'; /* 4. container */

			/* add a 'go to Joomla' Button for Joomla 4 and 5  */
			if ( SOBI_CMS == 'joomla4' ) {
                $this->output[] = '<button class="btn btn-light" id="spctrl-jsidebar-btn" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-wrapper" aria-controls="sidebar-wrapper" aria-expanded="false" aria-label="' . Sobi::Txt( "ACCESSIBILITY.JSIDEBAR" ) . '"><span class="fas fa-exchange-alt" aria-hidden="true"></span></button>';
			}
			/* add a 'trial version' button for the SobiPro trial version */
			if ( defined( 'SOBI_TRIMMED' ) ) {
				$this->output[] = '<div class="ms-1"><a class="btn" href="?option=com_sobipro&task=demo" type="button">' . Sobi::Txt( "DEMO.INFORMATION" ) . '</a></div>';
			}

			$this->output[] = '<div id="spctrl-menu-toggler"></div>';
			if ( $this->subtitle ) {
				$this->output[] = '<div class="btn-group flex-grow-1 d-flex flex-column mt-0 dk-title-area"><div>' . $this->title . '</div><div class="small">' . $this->subtitle . '</div></div>';
			}
			else {
				$this->output[] = '<div class="btn-group flex-grow-1 dk-title-area">' . $this->title . '</div>';
			}

			$this->first = true;
			foreach ( $this->buttons as $button ) {
				switch ( $button[ 'element' ] ) {
					case 'group':
						$this->output[] = '<div class="btn-group dropdown dropdown-status-group" role="group" aria-label="ACCESSIBILITY.TOOLBAR_BUTTONS">';
						foreach ( $button[ 'buttons' ] as $bt ) {
							$this->renderButton( $bt );
						}
						$this->output[] = '</div>';
						break;
					case 'button':
						$this->renderButton( $button );
						break;
					case 'divider':
						$this->output[] = '<div class="divider" role="presentation"></div>';
						break;
					case 'buttons':
						$icon = ( isset( $button[ 'icon' ] ) && $button[ 'icon' ] ) ? $button[ 'icon' ] : $this->getIcon( $button );
						$label = ( isset( $button[ 'label' ] ) && $button[ 'label' ] ) ? $button[ 'label' ] : $this->getLabel( $button );
						$colour = ( isset( $button[ 'colour' ] ) && $button[ 'colour' ] ) ? $button[ 'colour' ] : '';
						$ddc = isset( $button[ 'dropdown-class' ] ) ? ' ' . $button[ 'dropdown-class' ] : $this->getDDC( $button ); /* normally 'dropdown-menu-right' to align the menu on right side */
						$tbc = isset( $button[ 'toolbar-class' ] ) ? ' ' . $button[ 'toolbar-class' ] : null;
						$this->first = false;
						$btClass = isset( $button[ 'class' ] ) ? ' ' . $button[ 'class' ] : null;
						$uid = uniqid( 'tb-menu-link-' );
						$this->output[] = '<div class="btn-group dropdown dropdown-status-group' . $tbc . '" role="group">'; /* 1. button container */
						$this->output[] = '<button class="button-status-group btn ' . $btClass . ' ' . $colour . ' dropdown-toggle" id="' . $uid . '" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-haspopup="true" aria-expanded="false" type="button">';
						$this->output[] = '<span class="fas fa-' . $icon . '" aria-hidden="true"></span>&nbsp;&nbsp;' . $label;
						$this->output[] = '</button>';
						$this->output[] = '<div class="dropdown-menu ' . $ddc . '" aria-labelledby="' . $uid . '">'; /* 2. button container */
						foreach ( $button[ 'buttons' ] as $bt ) {
							$this->renderButton( $bt, true );
						}
						$this->output[] = '</div>';     /* close 2. button container */
						$this->output[] = '</div>';     /* close 1. button container */
						break;
				}
			}
			$this->output[] = '</div>'; /* close 4. container */
			$this->output[] = '</nav>'; /* close nav container */
			$this->output[] = '</div>'; /* close 3. container */
			$this->output[] = '</div>'; /* close 2. container */
			$this->output[] = '</div>'; /* close 1. container */

			return implode( "\n", $this->output );
		}

		return C::ES;
	}

	/**
	 * @param $button
	 * @param false $list
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function renderButton( $button, $list = false )
	{
		$rel = null;
		$onclick = null;
		$title = '';
		$btClass = $button[ 'class' ] ?? null;
		$colour = $button[ 'colour' ] ?? null;
		$tbc = isset( $button[ 'toolbar-class' ] ) ? ' ' . $button[ 'toolbar-class' ] : null;

		$this->first = false;
		if ( isset( $button[ 'type' ] ) && $button[ 'type' ] == 'url' ) {
			$href = $this->getLink( $button );
		}
		elseif ( ( !( isset( $button[ 'task' ] ) ) || !( $button[ 'task' ] ) ) ) {
			$href = $this->getLink( $button );
		}
		else {
			$rel = !( $this->istrim( $button ) ) ? $button[ 'task' ] : null;
			$href = '#';
		}

		/* get the button label */
		if ( !( isset( $button[ 'label' ] ) ) || !( $button[ 'label' ] ) ) {
			$label = $this->getLabel( $button );
		}
		else {
			$label = $button[ 'label' ];
		}

		/* get the button icon */
		if ( !( isset( $button[ 'icon' ] ) && $button[ 'icon' ] ) ) {
			$icon = $this->getIcon( $button, true );
		}
		else {
			$icon = $button[ 'icon' ];
		}

		if ( isset( $button[ 'confirm' ] ) ) {
			$title = 'data-sp-confirm="' . Sobi::Txt( $button[ 'confirm' ] ) . '" ';
		}

		$target = isset( $button[ 'target' ] ) && $button[ 'target' ] ? "target=\"{$button['target']}\"" : null;

		/* Dropdown Button with function */
		$trim = $this->istrim( $button ) ? 'disabled' : null;
		if ( isset( $button[ 'buttons' ] ) && is_array( $button[ 'buttons' ] ) && count( $button[ 'buttons' ] ) ) {
			$this->output[] = '<div class="btn-group dropdown dropdown-status-group' . $tbc . '" role="group">'; /* 1. button container */
			$this->output[] = "<a href=\"$href\" class=\"button-status-group btn $btClass $colour $trim\" $target rel=\"$rel\">";
			$this->output[] = '<span class="fas fa-' . $icon . ' "aria-hidden="true"></span>&nbsp;&nbsp;' . $label;
			$this->output[] = '</a>';
			$this->output[] = '<button class="btn btn-list' . $btClass . ' ' . $colour . ' dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false"><span class="visually-hidden">' . Sobi::Txt( "ACCESSIBILITY.DROPDOWN_TOGGLE" ) . '</span></button>';
			$id = $button[ 'task' ] ? ' id="spmenu-' . StringUtils::Nid( $button[ 'task' ] ) . '"' : null;
			$this->output[] = '<div class="dropdown-menu dropdown-menu-end"' . $id . '>';   /* dropdown container */
			foreach ( $button[ 'buttons' ] as $bt ) {
				$this->renderButton( $bt, true );
			}
			$this->output[] = '</div>'; /* close dropdown container */
			$this->output[] = '</div>'; /* close 1. button container*/
		}

		/** Single Standard button (even within dropdown list) */
		elseif ( !( $list ) ) {    /* single button (not within dropdown list) */
			$this->output[] = "<div class=\"btn-group $tbc\">";
			$this->output[] = "<a href=\"$href\" rel=\"$rel\" class=\"btn $btClass $colour $trim\" $target $onclick $title>";
			$this->output[] = '<span class="fas fa-' . $icon . '" aria-hidden="true"></span>&nbsp;&nbsp;' . $label;
			$this->output[] = '</a></div>';
		}
		/** button within dropdown list */
		elseif ( $button[ 'element' ] == 'nav-header' || $button[ 'element' ] == 'dropdown-header' ) {  /* nav-header for compatibility */
			$this->output[] = '<span class="dropdown-header">' . $button[ 'label' ] . '</span>';
		}
		else {
			if ( $button[ 'type' ] == 'help' ) {
				$this->output[] = '<hr class="dropdown-divider" role="presentation">';
			}
			$this->output[] = "<a href=\"$href\" $target $title rel=\"$rel\" class=\"dropdown-item $btClass $colour $trim\">";
			if ( isset( $button[ 'selected' ] ) ) {
				if ( !$button[ 'selected' ] ) {
					$icon = $this->icons[ 'not-selected' ];
				}
				else {
					$icon = $this->icons[ 'selected' ];
				}
			}
			if ( $icon ) {
				$this->output[] = '<span class="fas fa-' . $icon . ' fa-fw" aria-hidden="true"></span>&nbsp;&nbsp;' . $label;
			}
			else {
				$this->output[] = $label;
			}
			$this->output[] = '</a>';
		}
	}

	/**
	 * @param $value
	 *
	 * @return bool
	 */
	private function istrim( $value )
	{
		return isset( $value[ 'trim' ] ) && ( $value[ 'trim' ] === 'true' ) && defined( 'SOBI_TRIMMED' );
	}

	/**
	 * @param $button
	 *
	 * @return mixed|string
	 * @throws SPException
	 */
	private function getLink( $button )
	{
		$link = '#';
		if ( isset( $button[ 'type' ] ) ) {
			switch ( $button[ 'type' ] ) {
				case 'help':
					$link = 'https://www.sigsiu.net/help_screen/' . Sobi::Reg( 'help_task', Input::Task() );
					break;
				case 'url':
					if ( isset( $button[ 'sid' ] ) && $button[ 'sid' ] == 'true' ) {
						$link = Sobi::Url( [ 'task' => $button[ 'task' ], 'sid' => Input::Sid() ? Input::Sid() : Input::Pid() ] );
					}
					else {
						$link = Sobi::Url( $button[ 'task' ] ? : $button[ 'url' ] );
					}
					break;
			}
		}

		return $link;
	}

	/**
	 * @param $button
	 * @param bool $group
	 *
	 * @return mixed|string
	 */
	private function getIcon( $button, $group = false )
	{
		if ( array_key_exists( 'type', $button ) && ( isset( $button[ 'type' ] ) && $button[ 'type' ] == 'url' ) ) {
			$button[ 'type' ] = $button[ 'task' ];

			return $this->getIcon( $button );
		}
		if ( array_key_exists( 'type', $button ) && isset( $this->icons[ $button[ 'type' ] ] ) ) {
			$icon = $this->icons[ $button[ 'type' ] ];
		}
		else {
//			$icon = $group ? 'list' : '';
			$icon = '';
		}

		return $icon;
	}

	/**
	 * @param $button
	 *
	 * @return mixed
	 * @throws SPException
	 */
	private function getLabel( $button )
	{
		if ( isset( $button[ 'type' ] ) && $button[ 'type' ] == 'url' ) {
			$button[ 'type' ] = $button[ 'task' ];

			return $this->getLabel( $button );
		}
		if ( isset( $this->labels[ $button[ 'type' ] ] ) ) {
			$label = Sobi::Txt( 'TB.' . strtoupper( $this->labels[ $button[ 'type' ] ] ) );
		}
		else {
			$label = Sobi::Txt( 'TB.' . strtoupper( $button[ 'type' ] ) );
		}

		return $label;
	}

	/**
	 * @param $button
	 *
	 * @return string|null
	 */
	private function getDDC( $button )
	{
		$ddc = null;
		if ( isset( $button[ 'type' ] ) && ( $button[ 'type' ] == 'sections' || $button[ 'type' ] == 'options' ) ) {
			$ddc = ' dropdown-menu-end';
		}

		return $ddc;
	}
}
