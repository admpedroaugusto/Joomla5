/**
 * @package: SobiPro Library

 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET

 * @copyright Copyright (C) 2006 - 2022 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @modified 31 August 2022 by Sigrid Suski
 *
 * Attention!! Do not pack!!
 */

;SobiCore.Ready( function () {
	SobiPro.jQuery( '#SPAdminForm' ).on( 'AfterAjaxSubmit', function ( e, t, response ) {
		SobiPro.jQuery( '*[data-coordinates]' ).attr( 'data-coordinates', ' ' );
		SobiPro.jQuery( '.spctrl-field-image-upload' ).find( ':hidden' ).val( '' );
		SobiPro.jQuery( '.spctrl-image-crop' )
			.css( 'cursor', 'default' )
			.unbind( 'click' )
			.prop( 'onclick', null )
			.off( 'click' );
	} );

	SobiPro.jQuery( '.spctrl-field-image-upload' ).bind( 'uploadComplete', function ( ev, response ) {
		if ( response.responseJSON != undefined ) {

			if ( response.responseJSON.data && response.responseJSON.data.icon && response.responseJSON.data.icon.length ) {
				let type = '';
				let cursor = 'default';
				if ( response.responseJSON.data.crop == 1 ) {
					type = 'spctrl-image-crop';
					cursor = 'pointer';
				}

				let id = SobiPro.jQuery( this ).parent().find( '.idStore' ).attr( 'name' );
				let nid = id.replace( 'field_', 'field.' );

				let preview = SobiPro.jQuery( this )
					.parent()
					.parent()
					.find( '.spctrl-edit-image-preview' );

				preview.html( '<img style="cursor:' + cursor + ';" id="' + id + '_icon" class="' + type + '" src="' + SPLiveSite + 'index.php?option=com_sobipro&task=' + nid + '.icon&sid=' + SobiPro.jQuery( this ).data( 'section' ) + '&file=' + response.responseJSON.data.icon + '" alt=""/>' )
					.css( {
						'margin-right': '.3rem',
						'margin-bottom': '.3rem',
					} );
				preview.addClass( 'sp-preview-image' );
				SobiPro.jQuery( '#' + id + '_icon' )
					.attr( 'data-width', response.responseJSON.data.width )
					.attr( 'data-height', response.responseJSON.data.height );

				SobiPro.jQuery( '.spctrl-image-crop' ).click( function () {
					let id = SobiPro.jQuery( this ).attr( 'id' ).replace( '_icon', '' );
					let url = SobiPro.jQuery( this ).attr( 'src' ).replace( 'icon_', '' );
					let pid = SobiPro.jQuery( this ).attr( 'id' ).replace( '_icon', '_preview' );
					if ( SobiPro.jQuery( '#' + id + '_modal' ).attr( 'data-image-url' ) != url ) {
						SobiPro.jQuery( '#' + id + '_modal' ).attr( 'data-image-url', url );
						SobiPro.jQuery( '#' + id + '_modal' )
							.find( '.modal-body' )
							.html( '<img src="' + url + '" id="' + pid + '" alt="crop image"/>' );
						let proxy = SobiPro.jQuery( this );
						SobiPro.jQuery( '#' + pid ).cropper( {
							aspectRatio: proxy.data( 'width' ) / proxy.data( 'height' ),
							data: {
								x: 0,
								y: 0,
							},
							done: function ( data ) {
								if ( data.length || true ) {
									SobiPro.jQuery( '#' + id + '_modal' ).attr( 'data-coordinates', '::coordinates://' + JSON.stringify( {
										'x': data.x,
										'y': data.y,
										'height': data.height,
										'width': data.width,
									} ) );
								}
							}
						} );
					}
					let modalWindow;
					if ( bs === 5 ) {
						new bootstrap.Modal( document.getElementById( id + '_modal' ) ).show();
						modalWindow = SobiPro.jQuery( '#' + id + '_modal' );
					}
					else {
						modalWindow = SobiPro.jQuery( '#' + id + '_modal' ).modal();
						if ( bs === 2 ) {
							modalWindow.show();
						}
					}
					modalWindow.find( 'a.save' ).click( function () {
						let store = SobiPro.jQuery( '[name="' + id + '"]' );
						let current = store.val();
						if ( current && SobiPro.jQuery( '#' + id + '_modal' ).data( 'coordinates' ).length ) {
							if ( current.indexOf( 'coordinates://' ) != -1 ) {
								let currentArray = current.split( '::coordinates://' );
								store.val( currentArray[ 0 ] + SobiPro.jQuery( '#' + id + '_modal' ).data( 'coordinates' ) );
							}
							else {
								store.val( current + SobiPro.jQuery( '#' + id + '_modal' ).data( 'coordinates' ) );
							}
						}
						if ( bs === 2 ) {
							modalWindow.hide();
						}
					} );
				} );
			}
		}
	} );
} );
