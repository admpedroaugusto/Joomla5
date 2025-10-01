<?php
/**
 * @package Default Template V8 for SobiPro multi-directory component with content construction support
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006–2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/GPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according to section 7 of GPL v3.
 * See https://www.gnu.org/licenses/gpl.html and https://www.sigsiu.net/licenses.
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * @modified 08 February 2024 by Sigrid Suski
 */

/* Warning:
 * If you have added here your own functions accessing the database, and you experience that SobiPro is slow,
 * it is HIGHLY recommended to remove these functions.
 * The more you add your own code, especially if accessing the database, the more you slow down SobiPro!
 * The functions here are not cached by SobiPro's caching mechanisms.
 * Keep in mind that you bypass SobiPro speed acceleration measures!
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

use Sobi\C;
use Sobi\Communication\CURL;
use Sobi\Input\Input;

/**
 * Class tplDefault8
 */
abstract class tplDefault8
{
	/**
	 * @param string|array $title
	 *
	 * @return void
	 * @throws \SPException
	 */
	public static function AddTitle( $title )
	{
		Sobi::Title( $title );
	}

	/**
	 * @param string|array $title
	 *
	 * @return void
	 * @throws \SPException
	 */
	public static function SetTitle( $title )
	{
		Sobi::Title( $title, false );
	}

	/**
	 * @param string $key
	 * @param null $def
	 * @param string $section
	 *
	 * @return array|mixed|string|null
	 * @throws \SPException
	 */
	public static function Cfg( string $key, $def = null, string $section = 'general' )
	{
		return Sobi::Cfg( $key, $def, $section );
	}

	/**
	 * @param string $styles
	 *
	 * @return void
	 */
	public static function AddCSSStyles( string $styles )
	{
		SPFactory::header()->addCSSCode( $styles );
	}

	/**
	 * @param string $name
	 *
	 * @return void
	 */
	public static function LoadFont( string $name )
	{
		SPFactory::header()->addHeadLink( "//fonts.googleapis.com/css?family=" . $name, "text/css", C::ES, "stylesheet" );
	}

	/**
	 * @param string $name
	 *
	 * @return void
	 */
	public static function ApplyBaseFont( string $name )
	{
		SPFactory::header()->addCSSCode( ".SobiPro.default8 {font-family:'" . $name . "', sans serif;}" );
	}

	/**
	 * @param string $name
	 *
	 * @return void
	 */
	public static function ApplyFont( string $name )
	{
		SPFactory::header()->addCSSCode( ".SobiPro.default8 h1, .SobiPro.default8 h2, .SobiPro.default8 h3, .SobiPro.default8 h4 {font-family:'" . $name . "', sans serif;}" );
	}

	/**
	 * @return void
	 */
	public static function setCaptcha()
	{
		SPFactory::header()->addJsUrl( 'https://www.google.com/recaptcha/api.js' );
	}

	/**
	 * Called right at the beginning of the submit process (before save).
	 *
	 * @param SPEntry $model -> the entry model
	 * @param array $tCfg -> the template configuration (general and entry-edit)
	 *
	 * @return void
	 * @throws SPException
	 */
	public static function BeforeSubmitEntry( SPEntry &$model, array $tCfg = [] )
	{
		if ( !defined( 'SOBIPRO_ADM' ) ) {
			if ( array_key_exists( 'recaptcha', $tCfg ) && $tCfg[ 'recaptcha' ] ) {

				if ( !strlen( Input::String( 'g-recaptcha-response' ) ) ) {
					SPFactory::mainframe()
						->cleanBuffer()
						->customHeader();
					echo json_encode(
						[
							'message'  => [ 'type' => C::ERROR_MSG,
							],
							'redirect' => false,
							'data'     => [ 'bs'    => array_key_exists( 'bs', $tCfg ) && $tCfg[ 'bs' ] ? $tCfg[ 'bs' ] : 5,
							                'error' => [ 'field-g-recaptcha' => 'You have failed to prove your humanity!' ] ],
						]
					);
					exit;
				}
			}
		}
	}

	/**
	 * Called right at the beginning of the save (after submit) process.
	 * Allows, for example, to modify the $store data.
	 *
	 * @param \SPEntry $model -> the entry model
	 * @param array $store -> the POST data as modified by the field
	 * @param array $tCfg -> the template configuration (general and entry-edit)
	 *
	 * @return void
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function BeforeStoreEntry( SPEntry &$model, array &$store, array $tCfg = [] )
	{
		if ( !defined( 'SOBIPRO_ADM' ) ) {
			if ( array_key_exists( 'recaptcha', $tCfg ) && $tCfg[ 'recaptcha' ] ) {
				try {
					$connection = new CURL();
					$resecret = array_key_exists( 'resecret', $tCfg ) ? $tCfg[ 'resecret' ] : C::ES;
					$requestcache = SPFactory::registry()->get( 'requestcache' );
					$connection->setOptions(
						[
							'url'            => 'https://www.google.com/recaptcha/api/siteverify',
							'connecttimeout' => 10,
							'returntransfer' => true,
							'header'         => false,
							'verbose'        => true,
							'ssl_verifypeer' => false,
							'ssl_verifyhost' => false,
							'fresh_connect'  => true,
							'useragent'      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:76.0) Gecko/20100101 Firefox/76.0',
							'postfields'     => http_build_query( [
									'secret'   => $resecret,
									'response' => array_key_exists( 'g-recaptcha-response', $requestcache ) ? $requestcache[ 'g-recaptcha-response' ] : C::ES,
								]
							),
							'post'           => 1,
						]
					);

					$response = $connection->exec();
					$response = json_decode( $response );
					if ( !$response->success ) {
						$codes = 'error-codes';
						$msg = 'An error occured';
						foreach ( $response->$codes as $err ) {
							switch ( $err ) {
								case 'missing-input-secret':
									$msg = 'The secret parameter is missing.';
									break;
								case 'invalid-input-secret':
									$msg = 'The secret parameter is invalid or malformed.';
									break;
								case 'missing-input-response':
									$msg = 'The response parameter is missing.';
									break;
								case 'invalid-input-response':
									$msg = 'The response parameter is invalid or malformed.';
									break;
								case 'bad-request':
									$msg = 'The request is invalid or malformed.';
									break;
								case 'timeout-or-duplicate':
									$msg = 'The response is no longer valid: either is too old or has been used previously.';
									break;
							}
						}
						SPFactory::message()->setMessage( $msg, false, C::ERROR_MSG );
						Sobi::Redirect( Sobi::Back(), C::ES, C::ES, true );
					}
				}
				catch ( Exception|SPException $x ) {
					SPFactory::message()->setMessage( 'Error while connecting to Google reCaptcha.', false, C::ERROR_MSG );
					Sobi::Redirect( Sobi::Back(), C::ES, C::ES, true );
				}
			}
		}
	}

	/**
	 * Set of possible simple plug-ins/hookup functions which allow manipulating data while adding
	 * a new or saving an existing entry.
	 */

	/**
	 * Called at the end of the submit process.
	 *
	 * @param SPEntry $model
	 * */
//	public static function AfterSubmitEntry( SPEntry &$model )
//	{
//	}

	/**
	 * Called right at the end of the save process.
	 *
	 * @param SPEntry $model
	 * */
//	public static function AfterStoreEntry( SPEntry &$model )
//	{
//	}

	/**
	 * Called right before the payment is being stored in the payment registry.
	 * SPFactory::payment()->store( $sid );
	 *
	 * @param int $sid - id of the entry
	 * */
//	public static function BeforeStoreEntryPayment( $sid )
//	{
//	}

	/**
	 * Called right before the payment view (Between the submit action and the save action).
	 *
	 * @param array $data
	 */
//	public static function BeforePaymentView( &$data )
//	{
//	}
}
