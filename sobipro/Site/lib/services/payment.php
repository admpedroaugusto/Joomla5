<?php
/**
 * @package: SobiPro Library
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2020 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 05-Feb-2010 by Radek Suski
 * @modified 28 July 2021 by Sigrid Suski
 */

use Sobi\Lib\Factory;
use Sobi\Utils\StringUtils;

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

/**
 * Class SPPayment
 */
final class SPPayment
{
	/**
	 * @var array
	 */
	private $payments = [ [] ];
	/**
	 * @var array
	 */
	private $discounts = [];

	/**
	 * @var null
	 */
	private $refNum = null;

	/* just to prevent direct creation */
	/**
	 * SPPayment constructor.
	 */
	private function __construct()
	{
	}

	/**
	 * Singleton.
	 *
	 * @return SPPayment
	 */
	public static function & getInstance()
	{
		static $me = null;
		if ( !$me || !( $me instanceof SPPayment ) ) {
			$me = new SPPayment();
		}

		return $me;
	}

	/**
	 * @param $sid
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function store( $sid )
	{
		if ( count( $this->payments[ $sid ] ) ) {
			$positions = [];
			$this->refNum = time() . '.' . $sid;
			foreach ( $this->payments[ $sid ] as $position ) {
				$positions[] = [
					'refNum'     => $this->refNum,
					'sid'        => $sid,
					'fid'        => $position[ 'id' ],
					'subject'    => $position[ 'reference' ],
					'dateAdded'  => 'FUNCTION:NOW()',
					'datePaid'   => null,
					'validUntil' => null,
					'paid'       => 0,
					'amount'     => $position[ 'amount' ],
				];
			}
			try {
				Sobi::Trigger( 'Payment', ucfirst( __FUNCTION__ ), [ &$positions ] );
				Factory::Db()->insertArray( 'spdb_payments', $positions );
			}
			catch ( SPException $x ) {
				Sobi::Error( 'Payment', SPLang::e( 'CANNOT_SAVE_PAYMENT_DB_ERR', $x->getMessage() ), SPC::ERROR, 500, __LINE__, __FILE__ );
			}
		}
	}

	/**
	 * @param int $id
	 * @param false $app
	 *
	 * @return array|mixed
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function summary( $id = 0, $app = false )
	{
		/**
		 * we have two models here:
		 *  - the german alike is that all prices including VAT already
		 *  - the USA alike is that all prices are net
		 */
		$vat = Sobi::Cfg( 'payments.vat', 0 );
		$vatsub = Sobi::Cfg( 'payments.vat_brutto', true );
		$sumnetto = 0;
		$sumbrutto = 0;
		$positionsArray = [];
		$discountArray = [];
		if ( isset( $this->payments[ $id ] ) && count( $this->payments[ $id ] ) ) {
			foreach ( $this->payments[ $id ] as $payment ) {
				if ( $vat ) {
					if ( $vatsub ) {
						$netto = $payment[ 'amount' ] / ( 1 + ( $vat / 100 ) );
						$svat = $payment[ 'amount' ] - $netto;
//						$sumvat = +$svat;
						$brutto = $payment[ 'amount' ];
					}
					else {
						$netto = $payment[ 'amount' ];
						$svat = $netto * $vat;
//						$sumvat = +$svat;
						$brutto = $netto * ( 1 + ( $vat / 100 ) );
					}
					$sumnetto += $netto;
					$sumbrutto += $brutto;
					$positionsArray[] = [ 'reference' => $payment[ 'reference' ], 'netto' => StringUtils::Currency( $netto ), 'brutto' => StringUtils::Currency( $brutto ), 'vat' => self::percent( $vat ), 'fid' => $payment[ 'id' ] ];
				}
				else {
					$sumnetto += $payment[ 'amount' ];
					$sumbrutto += $payment[ 'amount' ];
					$positionsArray[] = [ 'reference' => $payment[ 'reference' ], 'amount' => StringUtils::Currency( $payment[ 'amount' ] ), 'fid' => $payment[ 'id' ] ];
				}
			}
		}
//		$this->discounts[ $id ][ 'discount' ] = '12%';
//		$this->discounts[ $id ][ 'for' ] = 'discount for new customer';

		if ( $app ) { // triggered by Notifications app
			Sobi::Trigger( 'AppSetDiscount', ucfirst( __FUNCTION__ ), [ &$this->discounts, $id ] );
		}
		else {
			Sobi::Trigger( 'SetDiscount', ucfirst( __FUNCTION__ ), [ &$this->discounts, $id ] );
		}
		// Discount Calculation
		if ( isset( $this->discounts[ $id ][ 'discount' ] ) && $this->discounts[ $id ][ 'discount' ] ) {
			$isPercentage = strstr( $this->discounts[ $id ][ 'discount' ], '%' );
			$discountValue = str_replace( '%', '', $this->discounts[ $id ][ 'discount' ] );

			// with VAT
			if ( $vat ) {
				if ( Sobi::Cfg( 'payments.discount_to_netto', false ) ) {
					if ( $isPercentage ) {
						$discount = $sumnetto * ( double ) ( $discountValue / 100 );
					}
					else {
						$discount = $discountValue;
					}
					$sumnetto = ( ( $sumnetto - $discount ) < 0.0 ) ? 0.0 : $sumnetto - $discount;
					$sumbrutto = $sumnetto * ( 1 + ( $vat / 100 ) );
				}
				else {
					//percental discount
					if ( $isPercentage ) {
						$discount = $sumbrutto * ( double ) ( $discountValue / 100 );
					}
					//absolute discount
					else {
						$discount = $discountValue;
					}
					$sumbrutto = $sumbrutto - $discount;
					$sumnetto = $sumnetto / ( 1 + ( $vat / 100 ) );
				}
				$discountArray = [ 'discount_sum'     => StringUtils::Currency( $discount ),
				                   'discount_sum_raw' => $discount,
				                   'discount'         => $isPercentage ? $this->discounts[ $id ][ 'discount' ] : StringUtils::Currency( $discountValue ),
				                   'discount_raw'     => $discountValue,
				                   'is_percentage'    => $isPercentage ? 'true' : 'false',
				                   'netto'            => StringUtils::Currency( $sumnetto ),
				                   'netto_raw'        => $sumnetto,
				                   'brutto'           => StringUtils::Currency( $sumbrutto ),
				                   'brutto_raw'       => $sumbrutto,
				                   'for'              => $this->discounts[ $id ][ 'for' ],
				];
				if ( isset ( $this->discounts[ $id ][ 'code' ] ) ) {
					$discountArray[ 'code' ] = $this->discounts[ $id ][ 'code' ];
				}
			}

			// without VAT
			else {
				if ( $isPercentage ) {
					$discount = $sumbrutto * ( double ) ( $discountValue / 100 );
				}
				else {
					$discount = $discountValue;
				}
				$sumbrutto = $sumbrutto - $discount;
				$sumnetto = $sumbrutto;

				$discountArray = [ 'discount_sum'     => StringUtils::Currency( $discount ),
				                   'discount_sum_raw' => $discount,
				                   'discount'         => $isPercentage ? $this->discounts[ $id ][ 'discount' ] : StringUtils::Currency( $discountValue ),
				                   'discount_raw'     => $discountValue,
				                   'is_percentage'    => $isPercentage ? 'true' : 'false',
				                   'amount'           => StringUtils::Currency( $sumbrutto ),
				                   'amount_raw'       => $sumbrutto,
				                   'for'              => $this->discounts[ $id ][ 'for' ],
				];
				if ( isset ( $this->discounts[ $id ][ 'code' ] ) ) {
					$discountArray[ 'code' ] = $this->discounts[ $id ][ 'code' ];
				}

			}
		}

		// Calculation of total sums
		if ( $vat ) {
			if ( $vatsub ) {    // all prices are brutto
				$sumnetto = $sumbrutto / ( 1 + ( $vat / 100 ) );
				$sumvat = $sumbrutto - $sumnetto;
			}
			else {      // all prices are netto
				$sumbrutto = $sumnetto * ( 1 + ( $vat / 100 ) );
				$sumvat = $sumnetto * ( $vat / 100 );
			}
			$summaryArray = [
				'sum_netto'      => StringUtils::Currency( $sumnetto ),
				'sum_netto_raw'  => $sumnetto,
				'sum_brutto'     => StringUtils::Currency( $sumbrutto ),
				'sum_brutto_raw' => $sumbrutto,
				'sum_vat'        => StringUtils::Currency( $sumvat ),
				'sum_vat_raw'    => $sumvat,
				'vat'            => self::percent( $vat ),
				'vat_raw'        => $vat,
			];
		}
		//total sums without VAT
		else {
			$summaryArray = [ 'sum_amount'     => StringUtils::Currency( $sumbrutto ),
			                  'sum_amount_raw' => $sumbrutto,
			];
		}
		$retVal = [ 'positions' => $positionsArray, 'discount' => $discountArray, 'summary' => $summaryArray, 'refNum' => $this->refNum ];
		Sobi::Trigger( 'Payment', 'AfterSummary', [ &$retVal, $id ] );

		return $retVal;
	}

	/**
	 * @param double $amount
	 * @param string $reference - just a text to save in the db
	 * @param int $sid - id of the entry
	 * @param null $fid - field id or unique reference identifier
	 *
	 * @return bool
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function add( $amount, $reference, $sid = 0, $fid = null )
	{
		if ( ( $sid && $this->check( $sid, $fid ) )/* || ( Sobi::Can( 'entry.payment.free' ) )*/ ) {
			return true;
		}
		/* 26 September 2020 by Sigrid Suski:
		   as each field can be paid for each entry only once, we index the array with $fid to avoid double items which occurs,
		   when adding paid fields from backend (new as of 1.6.1 to let expiration app work for paid fields added from backend) */
		$this->payments[ $sid ][ $fid ] = [ 'reference' => $reference, 'amount' => $amount, 'id' => $fid ];
		Sobi::Trigger( 'Payment', ucfirst( __FUNCTION__ ), [ &$this->payments, $sid ] );
	}

	/**
	 * @param int $sid
	 *
	 * @return int|mixed
	 */
	public function count( $sid = 0 )
	{
		$payment = 0;
		if ( isset( $this->payments[ $sid ] ) && count( $this->payments[ $sid ] ) ) {
			foreach ( $this->payments[ $sid ] as $position ) {
				$payment += $position[ 'amount' ];
			}
		}

		return $payment;
	}

	/**
	 * @param $sid
	 * @param $fid
	 *
	 * @return false|string
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function check( $sid, $fid )
	{
		$db = Factory::Db();
		$count = false;
		/* try to save */
		try {
			$db->select( 'COUNT( pid )', 'spdb_payments', [ 'sid' => $sid, 'fid' => $fid ] );
			$count = $db->loadResult();
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( 'Payment', SPLang::e( 'CANNOT_GET_PAYMENTS', $x->getMessage() ), SPC::WARNING, 0, __LINE__, __FILE__ );
		}

		return $count;
	}

	/**
	 * @param $entry
	 * @param $data
	 *
	 * @return array|mixed
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function getMethods( $entry, $data )
	{
		$methods = [];
		Sobi::Trigger( 'Payment', 'MethodView', [ &$methods, $entry, &$data ] );

		return $methods;
	}

	/**
	 * @param double $amount
	 * @param string $reference
	 * @param int $sid
	 *
	 * @return void
	 */
	public function addDiscount( $amount, $reference, $sid = 0 )
	{
		$this->discounts[ $sid ][] = [ 'reference' => $reference, 'amount' => $amount ];
	}

	/**
	 * @param $value
	 *
	 * @return array|mixed|string|string[]|null
	 * @throws SPException
	 */
	public static function percent( $value )
	{
		return str_replace( [ '%number', '%sign' ], [ $value, '%' ], Sobi::Cfg( 'payments.percent_format', '%number%sign' ) );
	}

	/**
	 * @param $sid
	 *
	 * @throws \Sobi\Error\Exception
	 */
	public function deletePayments( $sid )
	{
		Factory::Db()
			->delete( 'spdb_payments', [ 'sid' => $sid ] )
			->delete( 'spdb_payments', [ 'sid' => ( $sid * -1 ) ] );
	}
}
