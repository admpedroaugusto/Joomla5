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
 * @modified 03 August 2022 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

use Sobi\Input\Input;

SPLoader::loadController( 'section' );

/**
 * Class SPListingCtrl
 */
class SPListingCtrl extends SPSectionCtrl
{
	/**
	 * @return bool|mixed
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function execute()
	{
		Input::Set( 'task', $this->_type . '.' . $this->_task );
		if ( strstr( $this->_task, '.' ) ) {
			$task = explode( '.', $this->_task );
			$class = SPLoader::loadClass( 'opt.listing.' . $task[ 0 ] );
		}
		else {
			$class = SPLoader::loadClass( 'opt.listing.' . $this->_task );
		}
		if ( $class ) {
			$imp = class_implements( $class );
			if ( is_array( $imp ) && in_array( 'SPListing', $imp ) ) {
				$listing = new $class();
//				if ( !( isset( $class::$compatibility ) ) ) {
//					define( 'SOBI_LEGACY_LISTING', true );
//					if ( strstr( $this->_task, '.' ) ) {
//						$t = explode( '.', $this->_task );
//						$listing->setTask( $t[ 0 ] );
//					}
//					else {
//						$listing->setTask( $this->_task );
//					}
//				}
//				else {
				$listing->setTask( $this->_task );

//				}

				return $listing->execute();
			}
			else {
				Sobi::Error( $this->name(), SPLang::e( 'SUCH_TASK_NOT_FOUND', 'wrong class definition' ), SPC::NOTICE, 404, __LINE__, __FILE__ );
			}
		}
		else {
			/* in case parent didn't register this task, it is an error */
			if ( !parent::execute() && $this->name() == __CLASS__ ) {
				Sobi::Error( $this->name(), SPLang::e( 'SUCH_TASK_NOT_FOUND', Input::Task() ), SPC::NOTICE, 404, __LINE__, __FILE__ );
			}
		}
	}

	/**
	 * @param $eOrder
	 * @param null $eLimit
	 * @param null $eLimStart
	 * @param bool $count
	 * @param array $conditions
	 * @param bool $entriesRecursive
	 * @param int $pid
	 *
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function entries( $eOrder, $eLimit = null, $eLimStart = null, $count = false, $conditions = [], $entriesRecursive = false, $pid = -1 )
	{
		return $this->getEntries( $eOrder, $eLimit, $eLimStart, $count, $conditions, $entriesRecursive, $pid );
	}
}
