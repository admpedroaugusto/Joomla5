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
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See http://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU LESSER General Public License for more details.
 *
 * @created Mon, Jan 14, 2013 by Radek Suski
 * @modified 23 February 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
require_once dirname( __FILE__ ) . '/../../joomla_common/base/mainframe.php';

use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Sobi\C;

/**
 * Interface between SobiPro and Joomla.
 *
 * Class SPJ3MainFrame
 */
class SPJ3MainFrame extends SPJoomlaMainFrame implements SPMainframeInterface
{
	/**
	 * @param string|array $title
	 * @param bool $forceAdd
	 *
	 * @return void
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	public function setTitle( $title, bool $forceAdd = false )
	{
		/* set backend title */
		if ( defined( 'SOBIPRO_ADM' ) ) {
			$sobipro = defined( 'SOBI_TRIMMED' ) ? 'SobiPro Trial' : 'SobiPro';
			static $section = C::ES;
			if ( !$section ) {
				$section = Sobi::Section( true );
			}
			if ( strlen( $section ) && !strstr( $title, $section ) ) {
				if ( strlen( $title ) ) {
					$title = $title . ' - ' . $sobipro . " [$section]";
				}
				else {
					$title = $sobipro . " [$section]";
				}
			}
			else {
				$dash = ( $title ) ? ' - ' : C::ES;
				$title = $title . $dash . $sobipro;
			}
			ToolbarHelper::title( $title, 'sobipro' );
		}

		/* set frontend title */
		else {
			if ( $forceAdd ) {
				$document = JFactory::getApplication()->getDocument();
				$document->setTitle( 'SobiPro' ); //get the title Joomla has set
			}
			parent::setTitle( $title );
		}
	}

	/**
	 * @return SPJoomlaMainFrame
	 */
	public static function & getInstance()
	{
		static $mainframe = false;
		if ( !( $mainframe instanceof self ) ) {
			$mainframe = new self();
		}

		return $mainframe;
	}

	/**
	 * @param $document
	 *
	 * @return mixed
	 */
	protected function getMetaDescription( $document )
	{
		return $document->getDescription();
	}

	/**
	 * @return \Joomla\Registry\Registry|string|null
	 * @throws \Exception
	 */
	public function getMenuParams()
	{
		$active = JFactory::getApplication()->getMenu()->getActive();

		if ( $active ) {
			return $active->getParams();
		}
		else {
			return null;
		}
	}

	/**
	 * Sets the back-end browser title only.
	 *
	 * @param string $title
	 *
	 * @throws \Exception
	 */
	public function setBrowserTitle( string $title )
	{
		$this->setTitle( html_entity_decode( $title ) );
	}
}


