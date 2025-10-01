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
 * @created 04-Mar-2009 by Radek Suski
 * @modified 30 September 2022 by Sigrid Suski
 */

use Sobi\C;

/**
 * class SPPagination
 */
final class SPPagination extends SPObject
{
	/** @var int */
	protected $limit = 0;
	/** @var int */
	protected $count = 0;
	/** @var int */
	protected $current = 0;
	/** @var string */
	protected $set = C::ES;
	/** @var string */
	protected $class = C::ES;
	/** @var array */
	private $_content = [];
	/** @var array */
	protected $url = [];
	/** @var string */
	protected $inputbox = C::ES;
	/** @var string */
	protected $type = C::ES;

	/**
	 * @param bool $return
	 *
	 * @return string
	 * @throws SPException
	 */
	public function display( bool $return = false ): string
	{
		$pages = $this->limit > 0 ? ceil( $this->count / $this->limit ) : 0;
		/** if we have any pages */
		if ( $pages > 1 ) {
			$this->_content[] = "<nav class=\"d-flex align-items-center $this->class\" aria-label=\"Page navigation\">";

			$this->_content[] = '<ul class="pagination">';
			if ( $this->current == 1 ) {
				$this->cell( ( strtoupper( $this->type ) == 'ICON' ) ? '<span class="fas fa-fast-backward" aria-hidden="true"></span>' : Sobi::Txt( 'PN.START' ), '#', 'disabled' );
				$this->cell( ( $this->type == 'ICON' || $this->type == 'icon' ) ? '<span class="fas fa-backward" aria-hidden="true"></span>' : Sobi::Txt( 'PN.PREVIOUS' ), '#', 'disabled' );
			}
			else {
				$this->url[ $this->set ] = 1;
				$this->cell( ( strtoupper( $this->type ) == 'ICON' ) ? '<span class="fas fa-fast-backward" aria-hidden="true"></span>' : Sobi::Txt( 'PN.START' ), Sobi::Url( $this->url ) );
				$this->url[ $this->set ] = $this->current - 1;
				$this->cell( ( $this->type == 'ICON' || $this->type == 'icon' ) ? '<span class="fas fa-backward" aria-hidden="true"></span>' : Sobi::Txt( 'PN.PREVIOUS' ), Sobi::Url( $this->url ) );
			}
			for ( $page = 1; $page <= $pages; $page++ ) {
				if ( $pages > 1000 && ( $page % 1000 != 0 ) ) {
					continue;
				}
				else {
					if ( $pages > 100 && ( $page % 100 != 0 ) ) {
						continue;
					}
					else {
						if ( $pages > 15 && ( $page % 5 != 0 ) ) {
							continue;
						}
					}
				}
				$this->url[ $this->set ] = $page;
				if ( $page == $this->current ) {
					$this->cell( $page, Sobi::Url( $this->url ), 'active' );
				}
				else {
					$this->cell( $page, Sobi::Url( $this->url ) );
				}
			}
			if ( $this->current == $pages ) {
				$this->cell( ( strtoupper( $this->type ) == 'ICON' ) ? '<span class="fas fa-forward" aria-hidden="true"></span>' : Sobi::Txt( 'PN.NEXT' ), '#', 'disabled' );
				$this->cell( ( strtoupper( $this->type ) == 'ICON' ) ? '<span class="fas fa-fast-forward" aria-hidden="true"></span>' : Sobi::Txt( 'PN.END' ), '#', 'disabled' );
			}
			else {
				$this->url[ $this->set ] = $this->current + 1;
				$this->cell( ( strtoupper( $this->type ) == 'ICON' ) ? '<span class="fas fa-forward" aria-hidden="true"></span>' : Sobi::Txt( 'PN.NEXT' ), Sobi::Url( $this->url ) );
				$this->url[ $this->set ] = $pages;
				$this->cell( ( strtoupper( $this->type ) == 'ICON' ) ? '<span class="fas fa-fast-forward" aria-hidden="true"></span>' : Sobi::Txt( 'PN.END' ), Sobi::Url( $this->url ) );
			}
			$this->_content[] = "</ul>";

			if ( $this->inputbox == 'right' ) {
				$this->inputbox();
			}
			// close overall container
			$this->_content[] = "</nav>";
		}

		$pn = implode( "\n", $this->_content );
		if ( $return ) {
			return $pn;
		}
		else {
			echo $pn;
		}

		return C::ES;
	}

	/**
	 * @param string $text
	 * @param string $href
	 * @param string $class
	 */
	protected function cell( string $text, string $href = '#', string $class = C::ES )
	{
		$class = "page-item $class";
		if ( $href ) {
			$this->_content[] = "<li class=\"$class\"><a href=\"$href\" class=\"page-link\">$text</a></li>";
		}
	}

	/**
	 * @return void
	 */
	protected function inputbox()
	{
		$this->_content[] = "<div class=\"input-group w-15\">
		  <input class=\"form-control\" type=\"text\" name=\"$this->set\" value=\"$this->current\" data-spctrl=\"submit\">
		  <button class=\"btn btn-info\" type=\"submit\">" . Sobi::Txt( 'PN.GO' ) . "</button>";
	}
}
