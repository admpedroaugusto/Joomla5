<?php
/**
 * @package SobiPro multi-directory component with content construction support
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license   GNU/GPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/gpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * @modified 12 January 2024 by Sigrid Suski
 */

defined( '_JEXEC' ) or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Sobi\FileSystem\FileSystem;

/**
 * Class plgSystemSpHeader
 */
class plgSystemSobiProHeader extends CMSPlugin
{
	/**
	 * @return void
	 * @throws \Sobi\Error\Exception|\SPException
	 */
	public function onBeforeCompileHead()
	{
		/* if the class exists, it means something initialized it already, and we can send the header */
		$app = Factory::getApplication();
		if ( class_exists( 'SPFactory' ) && !( $app->input->get( 'format' ) == 'raw' ) ) {
			SPFactory::header()->createHeaderContent();
		}
	}

	/**
	 * @throws \Exception
	 */
	public function onAfterRender()
	{
		$app = Factory::getApplication();

		/* To avoid having our header on the bottom of the page in Joomla 4, we need to add it first after Joomla has rendered the content. */
		if ( class_exists( 'SPFactory' ) && !( $app->input->get( 'format' ) == 'raw' ) ) {
			try {
				$header = SPFactory::header()->createHeaderContent( true );
				$buffer = $app->getBody();
				$buffer = str_replace( '</head>', "\n" . $header . "\n</head>\n", $buffer );
				$app->setBody( $buffer );
			}
			catch ( Exception $e ) {
				exit ( $e->getMessage() );
			}
		}
	}

	/**
	 * @param $options
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function onUserAfterLogin( $options )
	{
		$app = Factory::getApplication();
		if ( $app->isClient( 'administrator' ) ) {
			require_once( JPATH_ROOT . '/components/com_sobipro/lib/sobi.php' );
			Sobi::Initialise( 0, true );
			require_once( JPATH_ROOT . '/components/com_sobipro/lib/ctrl/adm/extensions.php' );

			if ( Sobi::Can( 'cms.apps' ) && Sobi::Cfg( 'extensions.check_updates', true ) ) {
				$ctrl = new SPExtensionsCtrl();
				try {
					if ( class_exists( 'SoapClient' ) ) {
						$updates = $ctrl->updates( false );
					}
					else {
						SPFactory::message()
							->error( Sobi::Txt( 'REQ.SOAP_NOT_AVAILABLE' ), false, false )
							->setSystemMessage();
					}
				}
				catch ( Sobi\Error\Exception $x ) {
					SPFactory::message()
						->error( Sobi::Txt( 'UPDATE.SSL_ERROR' ), false, false )
						->setSystemMessage();

					return;
				}
				$apps = [];
				if ( count( $updates ) ) {
					foreach ( $updates as $update ) {
						if ( $update[ 'update' ] == 'true' ) {
							if ( $update[ 'name' ] != 'SobiPro' ) {
								$apps[] = $update[ 'name' ];
							}
						}
					}
				}
				if ( count( $apps ) ) {
					//$count = count( $apps );
//					if ( count( $apps ) > 3 ) {
//						$apps = array_slice( $apps, 0, 3 );
//						$apps[] = '...';
//					}
					$message = [ 'count' => count( $apps ),
					             'text'  => Sobi::Txt( 'UPDATE.APPS_OUTDATED' ), ];
					$message = json_encode( $message );
					FileSystem::Write( JPATH_ROOT . '/components/com_sobipro/tmp/message.json', $message );
				}
				else {
					FileSystem::Delete( JPATH_ROOT . '/components/com_sobipro/tmp/message.json' );
				}
			}
			else {
				FileSystem::Delete( JPATH_ROOT . '/components/com_sobipro/tmp/message.json' );
			}
		}
	}
}
