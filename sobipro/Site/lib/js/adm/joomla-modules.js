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
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * @modified 26 January 2022 by Sigrid Suski
 */

/* for Joomla menu */

SobiCore.Ready( () => {
	spModuleHandling()
} );

// function SPJmenuFixTask( b ) {
// 	SobiCore.Ready( () => {
// 		try {
// 			SobiPro.jQuery( SobiPro.jQuery( '#jform_type-lbl' ).siblings()[ 0 ] ).val( b );
// 		} catch (e) {
// 		}
// 		try {
// 			var a = SobiPro.jQuery( '[name*="jform[type]"]' ).parent().find( 'input[type=text]' );
// 			a.val( b );
// 			a.css( 'min-width', '200px' );
// 			SobiPro.jQuery( '#jform_link' ).css( 'min-width', '500px' );
// 		} catch (e) {
// 		}
// 	} );
// }

function spModuleHandling() {
	try {
		/* handle the Joomla submit buttons save and apply to validate if a SobiPro section is selected */
		let submitBtn = Joomla.submitbutton;
		Joomla.submitbutton = function ( pressbutton, type ) {
			if ( pressbutton.indexOf( 'save' ).indexOf === -1 || pressbutton.indexOf( 'apply' ) === -1 || validateSection() ) {
				submitBtn( pressbutton, type );
			}
		}
	} catch (x) {
	}

	let elementSid = document.getElementById( "spctrl-sid" );
	let elementSection = document.getElementById( "spctrl-section" );
	let elementCategory = document.getElementById( "spctrl-category" );
	let elementEntry = document.getElementById( "spctrl-entry" );

	if ( elementCategory != null ) {
		/* Listener for selecting a category */
		elementCategory.addEventListener( "click", ( event ) => {
			if ( elementSid.value === '0' ) {
				SobiPro.Alert( "PLEASE_SELECT_SECTION_FIRST" );
				return false;
			}
			else {
				let elementChooser = document.getElementById( "spctrl-category-chooser" );
				if ( elementChooser ) {
					let requestUrl = SobiProUrl.replace( '%task%', 'category.chooser' ) + '&treetpl=rchooser&multiple=1&tmpl=component&sid=' + elementSid.value;
					elementChooser.innerHTML = '<iframe id="spctrl-frame" class="dk-frame" src="' + requestUrl + '"> </iframe>';
					new bootstrap.Modal( document.getElementById( 'spctrl-category-modal' ) ).show();
				}
			}
		} );
	}

	// if ( SobiPro.jQuery( "#spctrl-entry" ) != null ) {
	// 	SobiPro.jQuery( "#spctrl-entry" ).bind( "click", function ( e ) {
	// 		if ( SobiPro.jQuery( "#sid" ).val() == 0 ) {
	// 			SigsiuModalBox.Alert( "PLEASE_SELECT_SECTION_FIRST", '', 'error' );
	// 			return false;
	// 		}
	// 		else {
	// 			SobiPro.jQuery( '#spctrl-entry-chooser' ).typeahead( {
	// 				source: function ( b, c ) {
	// 					var d = {
	// 						'option': 'com_sobipro',
	// 						'task': 'entry.search',
	// 						'sid': SobiPro.jQuery( "#sid" ).val(),
	// 						'search': c,
	// 						'format': 'raw'
	// 					};
	// 					return SobiPro.jQuery.ajax( {
	// 						'type': 'post',
	// 						'url': 'index.php',
	// 						'data': d,
	// 						'dataType': 'json',
	// 						success: function ( a ) {
	// 							responseData = [];
	// 							if ( a.length ) {
	// 								for ( var i = 0; i < a.length; i++ ) {
	// 									responseData[ i ] = {
	// 										'name': a[ i ].name + ' ( ' + a[ i ].id + ' )',
	// 										'id': a[ i ].id,
	// 										'title': a[ i ].name
	// 									}
	// 								}
	// 								b.process( responseData );
	// 								SobiPro.jQuery( '.typeahead' ).addClass( 'typeahead-width' ).css( 'font-size', '13px' );
	// 								SobiPro.jQuery( '#spctrl-entry-chooser' ).after( SobiPro.jQuery( '.typeahead' ) );
	// 							}
	// 						}
	// 					} );
	// 				},
	// 				onselect: function ( a ) {
	// 					SobiPro.jQuery( '#selectedEntry' ).val( a.id );
	// 					SobiPro.jQuery( '#selectedEntryName' ).val( a.title );
	// 				},
	// 				property: "name"
	// 			} );
	// 			new bootstrap.Modal( document.getElementById( 'spctrl-entry-modal' ) ).show();
	// 			// SobiPro.jQuery( '#spctrl-entry-modal' ).modal();
	// 		}
	// 	} );
	// }
	// SobiPro.jQuery( '#spEntrySelect' ).bind( "click", function ( e ) {
	// 	if ( !(SobiPro.jQuery( '#selectedEntry' ).val()) ) {
	// 		return;
	// 	}
	// 	SobiPro.jQuery( '#spctrl-sid' ).val( SobiPro.jQuery( '#selectedEntry' ).val() );
	// 	SPSetObjectType( spStrings.objects.entry );
	// 	SobiPro.jQuery( "#spctrl-entry" ).addClass( 'btn-primary' ).html( SobiPro.htmlEntities( SobiPro.jQuery( '#selectedEntryName' ).val() ) );
	// 	SobiPro.jQuery( "#spctrl-category" ).removeClass( 'btn-primary' ).html( spStrings.labels.category );
	// 	SPReloadTemplates( 'entry' );
	// } );

	/* Listener for saving the selected category (click on modal window save button) */
	let saveBtn = document.getElementById( "spctrl-category-save" );
	if ( saveBtn ) {
		saveBtn.addEventListener( "click", ( event ) => {
			let selected;
			let elementSelected = document.getElementById( "selectedCat" );
			if ( elementSelected ) {
				selected = elementSelected.value;
			}
			if ( selected ) {
				elementSid.value = selected;
				// SPSetObjectType( spStrings.objects.category );
				elementCategory.classList.add( 'btn-primary' );
				elementCategory.classList.remove( 'btn-secondary' );
				elementCategory.innerText = SobiPro.htmlEntities( document.getElementById( "selectedCatName" ).value );

				// if ( elementEntry ) {
				// 	elementEntry.classList.remove( 'btn-primary' );
				// 	elementEntry.innerText = spStrings.labels.entry;
				// }
				// SPReloadTemplates( 'category' );
			}
		} );
	}

	/* Listener for removing the selected category (click on modal window clean button) */
	let cleanBtn = document.getElementById( "spctrl-category-clean" );
	if ( cleanBtn ) {
		cleanBtn.addEventListener( "click", ( event ) => {
			selected = elementSection.querySelector( 'option:checked' ).value;
			if ( selected ) {
				elementCategory.classList.add( 'btn-secondary' );
				elementCategory.classList.remove( 'btn-primary' );
				elementCategory.innerText = spStrings.labels.category;
				elementSid.value = selected;
				//SPReloadTemplates( 'category' );
			}
		} );
	}

	// SobiPro.jQuery( '#sptpl' ).change( function () {
	// 	if ( SobiPro.jQuery( this ).find( 'option:selected' ).val() ) {
	// 		SobiPro.jQuery( this ).attr( 'name', SobiPro.jQuery( this ).attr( 'name' ).replace( '-sptpl-', 'sptpl' ) );
	// 	}
	// 	else {
	// 		SobiPro.jQuery( this ).attr( 'name', SobiPro.jQuery( this ).attr( 'name' ).replace( 'sptpl', '-sptpl-' ) );
	// 		SobiPro.jQuery( '#jform_link' ).val( SobiPro.jQuery( '#jform_link' ).val().replace( /\&sptpl\=[a-zA-Z0-9\-\_\.]*/gi, '' ) )
	// 	}
	// } );
	// SobiPro.jQuery( '.spctrl-calendar' ).find( 'select' ).change( function () {
	// 	"use strict";
	// 	var a = [];
	// 	SobiPro.jQuery( '.spctrl-calendar' ).find( 'select' ).each( function ( i, e ) {
	// 		if ( SobiPro.jQuery( this ).val() ) {
	// 			a.push( SobiPro.jQuery( this ).val() );
	// 		}
	// 	} );
	// 	SobiPro.jQuery( '#selectedDate' ).val( a.join( '.' ) );
	// } );
}

// function SPSetObjectType( a ) {
// 	/* check if this is still necessary as 'otype' is not available !!!! */
// 	if ( !(spStrings.task) && document.getElementById( 'otype' ) ) {
// 		document.getElementById( 'otype' ).value = a;
//
// 		//SobiPro.jQuery( "#otype" ).val( a );
// 	}
// }

function validateSection() {
	let elementSid = document.getElementById( "spctrl-sid" );
	if ( elementSid && (elementSid.value === "0" || elementSid.value === "") ) {
		SobiPro.Alert( 'YOU_HAVE_TO_AT_LEAST_SELECT_A_SECTION' );
		return false;
	}
	else {
		return true;
	}
}