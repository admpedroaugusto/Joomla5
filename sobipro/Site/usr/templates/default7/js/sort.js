/**
 * @package: Default Template V7 for SobiPro multi-directory component with content construction support
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2023 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/GPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/gpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * @modified 24 March 2023 by Sigrid Suski
 */

;SobiCore.Ready( function () {

	SobiPro.jQuery( '.spctrl-sort-switch' ).bind( 'click', function ( event ) {
		event.preventDefault();

		SobiPro.jQuery( '#spctrl-waiting-sort' ).attr( 'style', 'display:inline' );

		SobiPro.jQuery( '.spctrl-sort-switch.active' ).removeAttr( 'aria-current' ).removeClass( 'active' ).removeAttr( 'data-spctrl-state' );
		SobiPro.jQuery( this ).addClass( 'active' ).attr( 'data-spctrl-state', 'active' ).attr( 'aria-current', 'true' );

		let order = SobiPro.jQuery( this ).attr( 'rel' );
		let sid = SobiPro.jQuery( '[data-category]' ).attr( 'data-category' );
		let site = SobiPro.jQuery( '[name="currentSite"]' ).val();

		SobiPro.jQuery
			.ajax( {
				url: document.URL,
				data: {'sptpl': 'section.ajax', 'tmpl': 'component', 'format': 'raw', 'xmlc': 1, 'sid': sid, 'eorder': order, 'ordering': 1, 'site': site},
				type: 'POST',
				dataType: 'html'
			} )
			.done( function ( data ) {
				SobiPro.jQuery( '#spctrl-entry-container' ).html( data );

				// highlight the search words
				let term = SobiPro.jQuery( '#spctrl-searchbox' ).val();
				if ( term !== undefined && term !== spSearchDefStr ) {
					let words = term.match( /("[^"]+"|[^"\s]+)/g );
					if ( words.length && typeof highlight === 'function' && SobiPro.jQuery.isFunction( highlight ) ) {
						SobiPro.jQuery.each( words, function ( i, word ) {
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
				document.querySelectorAll( ".sp-title" ).forEach( resizeField );

				// reset navigation
				// SobiPro.jQuery( '.spctrl-ajax-navigation' ).removeAttr( 'disabled' );
				// SobiPro.jQuery( '[name="currentSite"]' ).val( 1 );

				SobiPro.jQuery( '#spctrl-waiting-sort' ).attr( 'style', 'display:none' );
			} );
	} );

} );
