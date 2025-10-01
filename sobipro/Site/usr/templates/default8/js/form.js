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

	let tabtrigger = 'shown.bs.tab';
	let collapsetrigger = 'shown.bs.collapse';

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

	/* resize the map in entry form, necessary if the map is in a tab */
	SobiPro.jQuery( 'a[href="#address"]' ).on( tabtrigger, function( event ) {
		resizeMap();
	} );

	/* resize the map, necessary if the map is in a collapsable element */
	SobiPro.jQuery( '#address' ).on( collapsetrigger, function( event ) {
		resizeMap();
	} );
} );