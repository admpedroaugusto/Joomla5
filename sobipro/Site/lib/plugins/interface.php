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
 * @created 13-Jan-2009 by Radek Suski
 * @modified 30 May 2023 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

use Sobi\C;
use Sobi\Input\Input;
use Sobi\Lib\Factory;

/**
 * Class SPPlugins
 */
final class SPPlugins
{
	/*** @var array */
	private $_actions;
	/*** @var array */
	private $_plugins;

	/**
	 * SPPlugins constructor.
	 * @throws SPException
	 */
	private function __construct()
	{
		SPLoader::loadClass( 'plugins.plugin' );
	}

	/**
	 * @return \SPPlugins
	 */
	public static function & getInstance()
	{
		static $plugins = false;
		if ( !$plugins || !( $plugins instanceof SPPlugins ) ) {
			$plugins = new self();
		}

		return $plugins;
	}

	/**
	 * @param $task
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function load( $task )
	{
		$db = Factory::Db();
		$enabled = $db
			->select( 'pid', 'spdb_plugins', [ 'enabled' => 1 ] )
			->loadResultArray();

		$adm = defined( 'SOBIPRO_ADM' ) ? 'adm.' : C::ES;
		$condition = [ $adm . '*', $adm . $task ];
		$taskelements = [];
		if ( strstr( $task, '.' ) ) {
			$taskelements = explode( '.', $task );
			$condition[] = $adm . $taskelements[ 0 ] . '.*';
			$task = $taskelements[ 0 ] . '.' . $taskelements[ 1 ];
		}

		$dynamics = [];
		if ( is_array( $this->_actions ) && array_key_exists( $task, $this->_actions ) && is_array( $this->_actions[ $task ] ) ) {
			if ( count( $this->_actions[ $task ] ) && $this->getDynamics( $this->_actions[ $task ] ) ) {
				$dynamics = $this->_actions[ $task ];
			}
		}
		$this->_actions[ $task ] = $pids = [];
		try {
			$pids = $db
				->select( 'pid', 'spdb_plugin_task', [ 'onAction' => $condition, 'pid' => $enabled ] )
				->loadResultArray();
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( 'Plugins', $x->getMessage(), C::WARNING, 0, __LINE__, __FILE__ );
		}
//		if ( !( count( $pids ) ) ) {
//			$this->_actions[ $task ] = [];
//		}

		// get section dependant apps
		if ( Sobi::Section() && count( $pids ) ) {
			try {
				$this->_actions[ $task ] = $db
					->select( 'pid', 'spdb_plugin_section', [ 'section' => Sobi::Section(), 'enabled' => 1, 'pid' => $pids ] )
					->loadResultArray();
			}
			catch ( SPException $x ) {
				Sobi::Error( 'Plugins', $x->getMessage(), C::WARNING, 0, __LINE__, __FILE__ );
			}
		}

		/* if we didn't get section it can be also because it wasn't initialized yet
		   * but then we have at least on of these id in request - if so; just do nothing
		   * it will be initialized later anyway
		 * */
		else {
			if ( !( Input::Sid() || Input::Pid() ) ) {
				$this->_actions[ $task ] = array_merge( $dynamics, $pids );
			}
		}

		if ( count( $dynamics ) ) {
			$this->_actions[ $task ] = array_merge( $dynamics, $this->_actions[ $task ] );
		}

		// here is a special exception for the custom listings
		// it can be l.alpha or list.alpha or listing.alpha
		/**
		 * Fri, Apr 6, 2018 11:44:02
		 * This is most likely wrong. It should add it for different task.
		 * Now it loads all plugins, even those that are disabled for this section
		 * every time the task contains "list" or similar
		 */
		if ( preg_match( '/^list\..*/', $task ) || preg_match( '/^l\..*/', $task ) ) {
//			$this->_actions[ 'listing' . '.' . $taskelements[ 1 ] ] = $pids;
			$this->_actions[ 'listing' . '.' . $taskelements[ 1 ] ] =& $this->_actions[ $task ];
			$this->_actions[ 'section' . '.' . $taskelements[ 1 ] ] =& $this->_actions[ $task ];
		}
	}

	/**
	 * @modified 6 December 2019 by Sigrid Suski: if $action is set use it (=new), otherwise use Input::Task()
	 *
	 * @param $action
	 * @param $object
	 */
	public function registerHandler( $action, &$object )
	{
		static $count = 0;
		$count++;
		$this->_plugins[ 'dynamic_' . $count ] = $object;
		$task = ( $action ) ? : Sobi::Reg( 'task', Input::Task() );
		$this->_actions[ $task ][] = 'dynamic_' . $count;
	}

	/**
	 * @param $plugin
	 *
	 * @throws SPException
	 */
	private function initPlugin( $plugin )
	{
		if ( SPLoader::translatePath( 'opt/plugins/' . $plugin . '/init' ) ) {
			$pluginclass = SPLoader::loadClass( $plugin . '.init', false, 'plugin' );
			$this->_plugins[ $plugin ] = new $pluginclass( $plugin );
		}
		else {
			Sobi::Error( 'Class Load', sprintf( 'Cannot load application file at %s. File does not exist or is not readable.', $plugin ), C::WARNING, 0 );
		}
	}

	/**
	 * Checks if the plugins in the list are dynamic only. If not, return false.
	 *
	 * @param array|string|null $task
	 *
	 * @return bool
	 */
	protected function getDynamics( $task ): bool
	{
		if ( !is_array( $task ) || ( is_array( $task ) && !count( $task ) ) ) {
			return true;
		}
		foreach ( $task as $plugin ) {
			if ( strpos( $plugin, 'dynamic' ) === false ) { /* if a plugin is not dynamic */
				return false;
			}
		}

		return true;
	}

	/**
	 * @param string $action
	 * @param string $subject
	 * @param array $params
	 *
	 * @return bool
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function trigger( string $action, string $subject = C::ES, array $params = [] )
	{
		static $actions = [];
		static $count = 0;

		$action = ucfirst( $action ) . ucfirst( $subject );
		$action = str_replace( 'SP', C::ES, $action );
		$action = str_replace( 'SobiPro\Models\Entry', 'Entry', $action );

		$task = Sobi::Reg( 'task', Input::Task() );
		$task = strlen( $task ) ? $task : '*';
		if ( strstr( $task, '.' ) ) {
			$taskelements = explode( '.', $task );
			$task = $taskelements[ 0 ] . '.' . $taskelements[ 1 ];
		}

		/* Joomla! -> Unable to load renderer class */
		if ( $action == 'ParseContent' && Input::Cmd( 'format' ) == 'raw' ) {
			return false;
		}

		$actions[ $count++ ] = $action;
		SPFactory::mainframe()->trigger( $action, $params ); /* this always */

		/* An application should not trigger other applications as applications are running non-parallel, The only
		 * exception to this is, if an application wants an action of an application to be triggered. In this case the
		 * action has to begin with 'App' */
		if ( $count < 2 || substr( $action, 0, 3 ) == 'App' ) {

			/* load all plugins having method for this action */
			if ( !isset( $this->_actions[ $task ] )
				|| !count( $this->_actions[ $task ] )
				|| $this->getDynamics( $this->_actions[ $task ] )
			) {
				$this->load( $task );
			}

//			if (substr( $action, 0, 3 ) == 'App') {
//				$app = 1;   //for breakpoints
//			}

			/* if there were any plugin for this action, check if these are loaded */
			if ( is_array( $this->_actions[ $task ] ) && count( $this->_actions[ $task ] ) ) {
				foreach ( $this->_actions[ $task ] as $plugin ) {
					/* in case this plugin wasn't initialised */
					if ( !isset( $this->_plugins[ $plugin ] ) ) {
						$this->initPlugin( $plugin );
					}

					if ( $plugin && strlen( $plugin ) && array_key_exists( $plugin, $this->_plugins ) ) {
						$priority = method_exists( $this->_plugins[ $plugin ], 'priority' ) ? $this->_plugins[ $plugin ]->priority() : 'NORMAL';
						if ( isset( $this->_plugins[ $plugin ] ) && $this->_plugins[ $plugin ]->provide( $action ) && ( $priority == 'LOW' )
						) {
							/* call the method */
							call_user_func_array( [ $this->_plugins[ $plugin ], $action ], $params );
						}
					}
				}
				foreach ( $this->_actions[ $task ] as $plugin ) {
					if ( $plugin && strlen( $plugin ) && array_key_exists( $plugin, $this->_plugins ) ) {
						$priority = method_exists( $this->_plugins[ $plugin ], 'priority' ) ? $this->_plugins[ $plugin ]->priority() : 'NORMAL';
						if ( isset( $this->_plugins[ $plugin ] ) && $this->_plugins[ $plugin ]->provide( $action ) && ( $priority == 'NORMAL' )
						) {
							/* call the method */
							call_user_func_array( [ $this->_plugins[ $plugin ], $action ], $params );
						}
					}
				}
				foreach ( $this->_actions[ $task ] as $plugin ) {
					if ( $plugin && strlen( $plugin ) && array_key_exists( $plugin, $this->_plugins ) ) {
						$priority = method_exists( $this->_plugins[ $plugin ], 'priority' ) ? $this->_plugins[ $plugin ]->priority() : 'NORMAL';
						if ( isset( $this->_plugins[ $plugin ] ) && $this->_plugins[ $plugin ]->provide( $action ) && ( $priority == 'HIGH' )
						) {
							/* call the method */
							call_user_func_array( [ $this->_plugins[ $plugin ], $action ], $params );
						}
					}
				}
			}
		}

		unset( $actions[ $count ] );
		$count--;

		return true;
	}
}
