<?php
/**
 * @package: SobiPro Library
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2021 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 04-Mar-2009 by Radek Suski
 * @modified 08 June 2021 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

/**
 * Class SPPageNavXSLT
 */
final class SPPageNavXSLT
{
	/**
	 * @var int
	 */
	private $limit = 0;
	/**
	 * @var int
	 */
	private $count = 0;
	/**
	 * @var int
	 */
	private $current = 0;
	/**
	 * @var array
	 */
	private $url = [];

	/**
	 * @param int $limit - number of entries to show on a page
	 * @param int $count - number off all entries
	 * @param int $current - current site to display
	 * @param string $url - URL for th navigation
	 */
	public function __construct( $limit, $count, $current, $url )
	{
		$this->limit = $limit;
		$this->count = $count;
		$this->current = $current ? : 1;
		$this->url = $url;
	}

	/**
	 * Returns SobiPro Arr2XML array with the navigation.
	 *
	 * @return array
	 * @throws \SPException
	 */
	public function get()
	{
		$pn = [];
		$pages = $this->limit > 0 ? ceil( $this->count / $this->limit ) : 0;
		if ( $pages > 1 ) {
			/* start and previous buttons */
			if ( $this->current != 1 ) {
				$_attributes[ 'url' ] = Sobi::Url( array_merge( $this->url, [ 'site' => 1 ] ) );
			}
			$_attributes[ 'side' ] = 1;
			$_attributes[ 'label' ] = 'start';
			$pn[] = [
				'_complex'    => 1,
				'_data'       => Sobi::Txt( 'PN.START' ),
				'_attributes' => $_attributes,
			];

			$_attributes = null;
			if ( $this->current != 1 ) {
				$_attributes[ 'url' ] = Sobi::Url( array_merge( $this->url, [ 'site' => ( $this->current - 1 ) ] ) );
			}
			$_attributes[ 'side' ] = 1;
			$_attributes[ 'label' ] = 'prev';
			$pn[] = [
				'_complex'    => $this->current - 1,
				'_data'       => Sobi::Txt( 'PN.PREVIOUS' ),
				'_attributes' => $_attributes,
			];

			/* middle buttons */
			for ( $page = 1; $page <= $pages; $page++ ) {
				/** when we have many pages a lot of nodes is being generated and it is slowing the whole site down */
				if ( $pages > 100 ) {
					if ( $page > $this->current + 10 || $page < $this->current - 10 ) {
						continue;
					}
				}
				$_attributes = [];
				if ( $page == $this->current ) {
					$_attributes[ 'selected' ] = 1;
				}
				elseif ( $page > 1 ) {
					$_attributes[ 'url' ] = Sobi::Url( array_merge( $this->url, [ 'site' => $page ] ) );
				}
				else {
					$_attributes[ 'url' ] = Sobi::Url( array_merge( $this->url, [ 'site' => $page ] ) );
				}
				$_attributes[ 'side' ] = $page;
				$pn[] = [
					'_complex'    => 1,
					'_data'       => $page,
					'_attributes' => $_attributes,
				];
			}

			/* next button */
			$_attributes = null;
			if ( $this->current != $pages ) {
				$_attributes[ 'url' ] = Sobi::Url( array_merge( $this->url, [ 'site' => ( $this->current + 1 ) ] ) );
				$_attributes[ 'side' ] = $this->current + 1;
			}
			$_attributes[ 'label' ] = 'next';
			$pn[] = [
				'_complex'    => 1,
				'_data'       => Sobi::Txt( 'PN.NEXT' ),
				'_attributes' => $_attributes,
			];
			/* end button */
			$_attributes = null;
			if ( $this->current != $pages ) {
				$_attributes[ 'url' ] = Sobi::Url( array_merge( $this->url, [ 'site' => $pages ] ) );
				$_attributes[ 'side' ] = $pages;
			}
			$_attributes[ 'label' ] = 'end';
			$pn[] = [
				'_complex'    => 1,
				'_data'       => Sobi::Txt( 'PN.END' ),
				'_attributes' => $_attributes,
			];


			return [ 'current_site_txt' => Sobi::Txt( 'PN.CURRENT_SITE', [ 'current' => $this->current, 'pages' => $pages ] ),
			         'current_site'     => $this->current,
			         'all_sites'        => $pages,
			         'baseurl'          => Sobi::Url( $this->url ),
			         'entries'          => $this->count,
			         'sites'            => $pn, ];
		}
	}
}
