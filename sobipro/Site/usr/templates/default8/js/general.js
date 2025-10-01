/**
 * @package Default Template V8 for SobiPro multi-directory component with content construction support
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006–2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/GPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according to section 7 of GPL v3.
 * See https://www.gnu.org/licenses/gpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 */

;SobiCore.Ready( function() {
	//initialise all popovers and tooltips which has the attribute data-sp-toggle
	let template;
	template = '<div class="SobiPro popover" role="tooltip"><div class="popover-arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>';
	let popoverTriggerList = [].slice.call( document.querySelectorAll( '[data-sp-toggle="popover"]' ) );
	popoverTriggerList.map( function( popoverTriggerEl ) {
		return new bootstrap.Popover( popoverTriggerEl, {'html': true, 'trigger': 'hover', 'template': template, 'animation': false} );
	} );
	let tooltipTriggerList = [].slice.call( document.querySelectorAll( '[data-sp-toggle="tooltip"]' ) );
	tooltipTriggerList.map( function( tooltipTriggerEl ) {
		return new bootstrap.Tooltip( tooltipTriggerEl, {'trigger': 'hover', 'animation': false} );
	} );
	let carouselElement = document.getElementById( 'spctrl-carousel' );
	if ( carouselElement ) {
		const carousel = new bootstrap.Carousel( carouselElement );
	}

	// Handle the alpha switch
	SobiPro.jQuery( '.spctrl-alpha-switch' ).bind( 'click', function( e ) {
		e.preventDefault();

		SobiPro.jQuery( '.spctrl-alpha-switch.active' ).removeAttr( 'aria-current' ).removeClass( 'active' ).removeAttr( 'data-spctrl-state' );
		SobiPro.jQuery( this ).addClass( 'active' ).attr( 'data-spctrl-state', 'active' ).attr( 'aria-current', 'true' );

		SobiPro.jQuery
			.ajax( {
				url: SobiProUrl.replace( '%task%', 'list.alpha.switch.' + SobiPro.jQuery( this ).attr( 'rel' ) ),
				data: {sid: SobiProSection, tmpl: "component", format: "raw"},
				success: function( jsonObj ) {
					SobiPro.jQuery( '#spctrl-alpha-index' ).html( jsonObj.index );
				}
			} );
	} );

	try {
		document.getElementById( "spctrl-delete-entry" ).addEventListener( "click", ( event ) => {
			"use strict";
			if ( !( confirm( SobiPro.Txt( 'CONFIRM_DELETE_ENTRY' ) ) ) ) {
				event.preventDefault();
			}
		} );
	} catch (e) {
	}

	try {
		let catButton = document.getElementById( 'spctrl-category-show' );
		let showtext = document.getElementById( 'showtext' );
		let hidetext = document.getElementById( 'hidetext' );
		if ( catButton && showtext && hidetext ) {
			SobiPro.jQuery( '#spctrl-category-container-hide' ).slideToggle( 'fast' );

			catButton.setAttribute( 'data-visible', false );
			catButton.value = showtext.value;

			/* clicked on the category show/hide button */
			catButton.addEventListener( 'click', () => {
				/* if the categories are not visible */
				if ( catButton.getAttribute( 'data-visible' ) === 'false' ) {
					catButton.value = hidetext.value;
					catButton.setAttribute( 'data-visible', 'true' );
				}
				/* if the categories are visible */
				else {
					catButton.value = showtext.value;
					catButton.setAttribute( 'data-visible', 'false' );
				}
				SobiPro.jQuery( '#spctrl-category-container-hide' ).slideToggle( 'fast' );
			} );
		}
	} catch (e) {
	}

	document.querySelectorAll( ".spctrl-resize" ).forEach( resizeField );
	// document.querySelectorAll( ".spctrl-resize span" ).forEach( resizeField );
} );

// resize the title in vCard to fit in one line
function resizeField( item, index, arr ) {
	let parent = item.parentElement;
	let windowWidth = parseFloat( window.getComputedStyle( parent, null ).getPropertyValue( 'width' ) );
	let currentSize = parseFloat( window.getComputedStyle( item, null ).getPropertyValue( 'font-size' ) );
	let maxLength = ( windowWidth * ( currentSize * .28 ) ) / 100.0; //20;
	let currentLength = parseInt( item.textContent.length, 10 );
	if ( maxLength < currentLength ) {
		currentSize = currentSize * maxLength / currentLength;
		item.style.fontSize = currentSize.toString( 10 ) + 'px';
	}
}
