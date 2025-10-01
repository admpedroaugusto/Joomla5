/**
 * @package Default Template V8 for SobiPro multi-directory component with content construction support

 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Url: https://www.Sigsiu.NET

 * @copyright Copyright (C) 2006–2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/GPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according to section 7 of GPL v3.
 * See https://www.gnu.org/licenses/gpl.html and https://www.sigsiu.net/licenses.

 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 */

;SobiCore.Ready( () => {
	/* remove/add the string 'SH.SEARCH_FOR_BOX' from the general search box */
	// let searchDefStr = '';
	// let searchBox = SobiCore.Query( '#spctrl-searchbox' );
	// if ( searchBox ) {
	// 	searchBox.addEventListener( 'click', () => {
	// 		searchDefStr = searchDefStr === '' ? SobiPro.Txt( 'SH.SEARCH_FOR_BOX' ) : searchDefStr;
	// 		if ( searchBox.value === searchDefStr ) {
	// 			searchBox.value = '';
	// 		}
	// 	} );
	// 	searchBox.addEventListener( 'blur', () => {
	// 		searchDefStr = searchDefStr === '' ? SobiPro.Txt( 'SH.SEARCH_FOR_BOX' ) : searchDefStr;
	// 		if ( searchBox.value === '' ) {
	// 			searchBox.value = searchDefStr;
	// 		}
	// 	} );
	// }

	/* toggle the extended search parameters */
	try {
		SobiPro.jQuery( '#spctrl-extended-search' ).slideToggle( 'fast' );
		SobiCore.Query( '#spctrl-extended-options-btn' ).addEventListener( 'click', () => {
			SobiPro.jQuery( '#spctrl-extended-search' ).slideToggle( 'fast' );
		} );
	} catch (e) {
	}

	/* toggle the bottom buttons and text */
	let areaBtn = SobiCore.Query( '#spctrl-search-area-btn' );
	if ( areaBtn ) {
		try {
			areaBtn.addEventListener( "click", () => {
				if ( areaBtn.getAttribute( 'data-visible' ) === 'false' ) {
					areaBtn.innerHTML = SobiPro.Txt( 'TP.SEARCH_HIDE' );  //set new
					areaBtn.setAttribute( 'data-visible', 'true' );
					SobiCore.Query( '#spctrl-bottom-button' ).setAttribute( 'style', 'display:inline' );
				}
				else {
					areaBtn.setAttribute( 'data-visible', 'false' );
					areaBtn.innerHTML = SobiPro.Txt( 'TP.SEARCH_REFINE' );
					SobiCore.Query( '#spctrl-bottom-button' ).setAttribute( 'style', 'display:none' );
				}
			} );
		} catch (e) {
		}
	}

	function resizeMap() {
		if ( typeof SPGeoMapsReg !== "undefined" ) {
			SobiPro.jQuery( window ).trigger( 'resize' );
			try {
				for ( const id in SPGeoMapsReg ) {
					const handler = SPGeoMapsReg[ id ];
					try {
						/* for google maps */
						handler.Map.setCenter( handler.Position );
					} catch (e) {
						/* for leaflet maps */
						try {
							L.Util.requestAnimFrame( handler.Map.invalidateSize, handler.Map, !1, handler.Map._container );
						} catch (e) {
							console.log( e );
						}
					}
				}
			} catch (e) {
				console.log( e );
			}
		}
	}

	// let tabtrigger = bs > 2 ? 'shown.bs.tab' : 'shown';
	let collapsetrigger = 'shown.bs.collapse';

	/* resize the map, necessary if the map is in a collapsable element */
	SobiCore.Query( '#spctrl-search-area' ).addEventListener( collapsetrigger, ( event ) => {
		resizeMap();
	} );
} );