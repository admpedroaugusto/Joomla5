/**
 * @package SobiPro Library

 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Url: https://www.Sigsiu.NET

 * @copyright Copyright (C) 2006–2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 13 August 2015 by Radek Suski
 * @modified 01 March 2024 by Sigrid Suski
 */

;SobiCore.Ready( function() {
	let term = SobiPro.jQuery( '#spctrl-searchbox' ).val();
	if ( term ) {
		if ( term === spSearchDefStr ) {
			// if (typeof(sessionStorage) !== "undefined") {
			// 	sessionStorage.removeItem('searchword');
			// }
			return true;
		}

		let words = term.match( /("[^"]+"|[^"\s]+)/g );
		// if (typeof(sessionStorage) !== "undefined") {
		// 	sessionStorage.searchword = words;
		// }
		if ( words && words.length ) {
			SobiPro.jQuery.each( words, ( i, word ) => {

				/* for old templates */
				let container = document.querySelector( '.spEntriesContainer' );
				if ( container ) {
					SobiPro.jQuery( container ).highlight( word );
				}
				/* for default7 and up */
				container = document.getElementById( 'spctrl-entry-container' );
				if ( container ) {
					SobiPro.jQuery( container ).highlight( word );
				}
			} );
		}
	}
} );