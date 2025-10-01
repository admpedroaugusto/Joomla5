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
 * @created Thu, Nov 8, 2012 by Radek Suski
 * @modified 19 May 2023 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadController( 'controller' );

use Sobi\C;
use Sobi\Input\Input;
use Sobi\Lib\Factory;

/**
 * Class SPUserCtrl
 */
class SPUserCtrl extends SPController
{
	/**
	 * @var string
	 */
	protected $_type = 'user';

	/**
	 * @return bool
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function execute()
	{
		$retval = false;
		switch ( $this->_task ) {
			case 'search':
				$this->search();
				break;
			default:
				/* case plugin didn't register this task, it was an error */
				if ( !parent::execute() ) {
					Sobi::Error( $this->name(), SPLang::e( 'SUCH_TASK_NOT_FOUND', Input::Task() ), C::NOTICE, 404, __LINE__, __FILE__ );
				}
				else {
					$retval = true;
				}
				break;
		}

		return $retval;
	}

	/**
	 * AJAX
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function search()
	{
		if ( !Factory::Application()->checkToken() ) {
			Sobi::Error( 'Token', SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ), C::ERROR, 403, __LINE__, __FILE__ );
		}

		$ssid = Input::Base64( 'ssid' );
		$query = Input::String( 'q' );
		$session = SPFactory::user()->getUserState( 'userselector', C::ES, [] );
		$setting = $session[ $ssid ];

		/* get the site to display */
		$site = Input::Int( 'site', 'request', 1 );
		$eLim = Sobi::Cfg( 'user_selector.entries_limit', 18 );
		$eLimStart = ( $site - 1 ) * $eLim;
		$params = [];

		if ( $query ) {
			$query = '%' . $query . '%';
			$params = Factory::Db()->where( [ 'name' => $query, 'username' => $query, 'email' => $query ], 'OR' );
		}
		try {
			$count = Factory::Db()
				->select( 'COUNT(*)', '#__users', $params, $setting[ 'ordering' ] )
				->loadResult();
			$data = Factory::Db()
				->select( [ 'id', 'name', 'username', 'email', 'registerDate', 'lastvisitDate' ], '#__users', $params, $setting[ 'ordering' ], $eLim, $eLimStart )
				->loadAssocList();
		}
		catch ( Exception $x ) {
			echo $x->getMessage();
			exit;
		}

		$response = [ 'sites' => ceil( $count / $eLim ), 'site' => $site ];
		if ( count( $data ) ) {
			$replacements = [];
			preg_match_all( '/\%[a-z]*/', $setting[ 'format' ], $replacements );
			$placeholders = [];
			if ( isset( $replacements[ 0 ] ) && count( $replacements[ 0 ] ) ) {
				foreach ( $replacements[ 0 ] as $placeholder ) {
					$placeholders[] = str_replace( '%', C::ES, $placeholder );
				}
			}
			if ( count( $replacements ) ) {
				foreach ( $data as $index => $user ) {
					$txt = $setting[ 'format' ];
					foreach ( $placeholders as $attribute ) {
						if ( isset( $user[ $attribute ] ) ) {
							$txt = str_replace( '%' . $attribute, $user[ $attribute ], $txt );
						}
					}
					$data[ $index ][ 'text' ] = $txt;
				}
			}
			$response[ 'users' ] = $data;
		}
		SPFactory::mainframe()->cleanBuffer();
		echo json_encode( $response );

		exit;
	}
}
