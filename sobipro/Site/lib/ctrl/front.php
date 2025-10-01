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
 * @modified 25 May 2023 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadController( 'controller' );

use Sobi\Input\Input;
use Sobi\Lib\Factory;

/**
 * Class SPFront
 */
class SPFront extends SPController
{
	/**
	 * @var array
	 */
	private $_sections = [];
	/**
	 * @var string
	 */
	protected $_defTask = 'front';
	/**
	 * @var string
	 */
	protected $_type = 'front';

	/**
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function getSections()
	{
		$sections = [];
		try {
			$sections = Factory::Db()
				->select( '*', 'spdb_object', [ 'oType' => 'section' ], 'id' )
				->loadObjectList();
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'CANNOT_GET_SECTIONS_LIST', $x->getMessage() ), SPC::WARNING, 500, __LINE__, __FILE__ );
		}
		if ( count( $sections ) ) {
			foreach ( $sections as $section ) {
				if ( Sobi::Can( 'section', 'access', $section->id, 'valid' ) ) {
					$s = SPFactory::Section( $section->id );
					$s->extend( $section );
					$this->_sections[] = $s;
				}
			}
			Sobi::Trigger( $this->name(), __FUNCTION__, [ &$this->_sections ] );
		}
	}

	/**
	 * @return void
	 * @throws \ReflectionException
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function execute()
	{
		/* parent class executes the plugins */
		Input::Set( 'task', $this->_type . '.' . $this->_task );
		switch ( $this->_task ) {
			case 'front':
				$this->getSections();
				/** @var SPAdmPanelView $view */
				$view = SPFactory::View( 'front' );
				$view->assign( $this->_sections, 'sections' );

				$view->determineTemplate( 'front', SPC::DEFAULT_TEMPLATE );
				$view->display();
				break;

			default:
				/* case parents or plugin didn't register this task, it was an error */
				if ( !parent::execute() ) {
					Sobi::Error( $this->name(), SPLang::e( 'SUCH_TASK_NOT_FOUND', Input::Task() ), SPC::NOTICE, 404, __LINE__, __FILE__ );
				}
				break;
		}
	}
}
