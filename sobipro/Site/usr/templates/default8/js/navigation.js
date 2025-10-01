/**
 * @package Default Template V8 for SobiPro multi-directory component with content construction support

 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * * Url: https://www.Sigsiu.NET

 * @copyright Copyright (C) 2006–2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/GPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according to section 7 of GPL v3.
 * See https://www.gnu.org/licenses/gpl.html and https://www.sigsiu.net/licenses.

 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 */

;SobiCore.Ready( function() {
	let staticNav = document.querySelector( '.spctrl-static-navigation' );
	let ajaxNav = document.querySelector( '.spctrl-ajax-navigation' );

	/* ajax pagination handling */
	if ( staticNav && ajaxNav ) {
		ajaxNav.classList.remove( 'hidden' );
		staticNav.classList.add( 'hidden' );
		let lastSite = parseInt( ajaxNav.dataset.pages );

		ajaxNav.addEventListener( 'click', () => {
			let currentsite = document.querySelector( '[name="currentSite"]' );
			let site = parseInt( currentsite.value ) + 1;

			let waiting = document.getElementById( 'spctrl-waiting-navigation' );
			waiting.setAttribute( 'style', 'display:inline' );

			let request = {'sptpl': 'section.ajax', 'tmpl': 'component', 'site': site, 'format': 'raw', 'xmlc': 1};
			SobiCore.Post( request, document.URL, 'html' )
				.then( data => {
					let container = SobiCore.Query( '#spctrl-entry-container' );
					let content = container.innerHTML;
					container.innerHTML = content + data;

					currentsite.value = site;
					if ( site === lastSite ) {
						ajaxNav.setAttribute( 'disabled', 'disabled' );
					}
					waiting.setAttribute( 'style', 'display:none' );

					if ( typeof spVotingListener === "function" ) {
						spVotingListener();
					}
					if ( typeof spCollectionListener === "function" ) {
						spCollectionListener();
					}
					if ( typeof spCollectionButtonListener === "function" ) {
						spCollectionButtonListener();
					}
				} );
		} );
	}

	/* Pagination type 2 */
	function processNavigation( papa ) {
		let pages = parseInt( data.pages );
		let val = ( parseInt( papa.parentElement.previousElementSibling.textContent, 10 ) || 0 ) + 1;
		let oriHtml = papa.innerHTML;
		papa.innerHTML = '<input type="number" min="1" max="' + pages + '" step="1" value="' + val + '">';
		papa.classList.add( 'open' );
		let inputEl = papa.querySelector( 'input' );
		inputEl.focus();

		inputEl.addEventListener( 'click', ( event ) => {
			event.stopPropagation();
		} );

		inputEl.addEventListener( 'keyup', ( event ) => {
			let val = inputEl.value;
			if ( event.key === 'Enter' && val !== '' ) {
				if ( ( val > 0 ) && ( val <= pages ) ) {
					while ( papa.firstChild )
						papa.removeChild( papa.firstChild );
					papa.innerHTML = oriHtml;
					papa.classList.remove( 'open' );
					document.location = data.location + '?site=' + val;
				}
			}
			else {
				if ( event.key === 'Esc' || event.key === 'Escape' ) {
					while ( papa.firstChild )
						papa.removeChild( papa.firstChild );
					papa.innerHTML = oriHtml;
					papa.classList.remove( 'open' );
				}
			}
		} );

		inputEl.addEventListener( 'blur', () => {
			let value = inputEl.value;
			while ( papa.firstChild )
				papa.removeChild( papa.firstChild );
			papa.innerHTML = oriHtml;
			papa.classList.remove( 'open' );
			if ( value !== '' ) {
				document.location = data.location + '?site=' + value;
			}
			return false;
		} );

		return false;
	}

	/* the input box for the page to go to for pagination type 2 */
	let data;
	let pagination = document.querySelector( '.spctrl-pagination-input' );
	if ( pagination ) {
		data = pagination.dataset;
		document.querySelectorAll( '.spctrl-pagination-input' ).forEach( ( item ) => {
			item.addEventListener( 'click', ( event ) => {
				processNavigation( event.currentTarget );
			} )
		} );
	}
} );
