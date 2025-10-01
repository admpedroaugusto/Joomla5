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
 * @created 11 November 2012 by Radek Suski
 * @modified 15 October 2021 by Sigrid Suski
 */

// DO NOT SHRINK VARIABLES WHILE PACKING!!!!!

;SobiCore.Ready( function () {
	new SpUserSelector();
} );

function SpUserSelector() {
	"use strict";
	var SpUserSelector = this;
	var site = 1;
	var form = null;
	var modal = null;
	var active = false;
	var selected = 0;
	var responseContainer = null;
	var query = '';

	SobiPro.jQuery( '.spctrl-user-selector .spctrl-trigger' ).click( function ( ev ) {

		if ( this.semaphor ) {
			return true;
		}
		this.semaphor = true;

		SpUserSelector.selector = SobiPro.jQuery( this ).parent().parent();
		SpUserSelector.modal = SpUserSelector.selector.find( '.modal' );

		SpUserSelector.form = SpUserSelector.selector.find( ':input[type=hidden]' );
		SpUserSelector.responseContainer = SpUserSelector.modal.find( '.spctrl-response' );
		SpUserSelector.getUsers( SpUserSelector.getForm( SpUserSelector.form ), SpUserSelector.modal );

		SpUserSelector.modal.find( '.save' ).click( function () {
			if ( SpUserSelector.selected ) {
				SpUserSelector.selector.find( '[rel^="selected"]' ).val( SpUserSelector.selected[ 'id' ] );
				SpUserSelector.selector.find( '.user-name' ).val( SpUserSelector.selected[ 'text' ] );
			}
		} );

		var modal = this.parentElement.parentElement.querySelector( '.modal' );
		modal.querySelector( '.modal-title' ).classList.add( 'w-100' );
		modal.querySelector( '.modal-body' ).classList.add( 'sp-user-selector' );
		new bootstrap.Modal( modal ).show();
		//SpUserSelector.modal.modal();

		var proxy = this;
		SpUserSelector.modal.on( 'hidden.bs.modal', function () {
			proxy.semaphor = 0;
			SpUserSelector.modal.find( '.spctrl-response' ).html( '' );
			SpUserSelector.site = 1;
			SpUserSelector.active = false;
		} );
	} );

	/* load more users */
	SobiPro.jQuery( '.spctrl-user-selector .spctrl-more' ).click( function () {
		var request = SpUserSelector.getForm( SpUserSelector.form );
		request[ 'site' ] = ++SpUserSelector.site;
		request[ 'q' ] = SpUserSelector.query;
		SpUserSelector.getUsers( request );
	} );

	/* search for a user */
	SobiPro.jQuery( '.spctrl-user-selector .spctrl-search' ).keyup( function () {
		var request = SpUserSelector.getForm( SpUserSelector.form );
		request[ 'q' ] = SobiPro.jQuery( this ).val();
		SpUserSelector.query = request[ 'q' ];
		SpUserSelector.modal.find( '.spctrl-response' ).html( '' );
		request[ 'site' ] = 1;
		SpUserSelector.getUsers( request );
	} );

	this.getForm = function ( form ) {
		var data = {'site': this.site};
		form.each( function ( i, e ) {
			var el = SobiPro.jQuery( e );
			if ( el.attr( 'rel' ) == 'selected' ) {
				data[ 'selected' ] = el.val();
				SpUserSelector.selected = data[ 'selected' ];
			}
			else {
				if ( el.attr( 'name' ).indexOf( 'Ssid' ) != -1 ) {
					data[ 'ssid' ] = el.val();
				}
				else {
					data[ el.attr( 'name' ) ] = el.val();
				}
			}
		} );
		return data;
	};

	this.getUsers = function ( data ) {
		SobiPro.jQuery.ajax( {
			'type': 'post',
			'url': SobiProUrl.replace( '%task%', 'user.search' ),
			'data': data,
			'dataType': 'json',
			success: function ( response ) {
				SpUserSelector.site = response.site;
				if ( response.sites > response.site ) {
					SpUserSelector.modal.find( '.spctrl-more' ).removeClass( 'hidden' )
				}
				else {
					SpUserSelector.modal.find( '.spctrl-more' ).addClass( 'hidden' )
				}
				SpUserSelector.responseContainer.html( '' );
				SobiPro.jQuery.each( response.users, function ( i, e ) {
					var active = ' btn-unchecked';
					if ( e.id == SpUserSelector.selected && !(SpUserSelector.active) ) {
						active = ' btn-yes active';
					}
					SpUserSelector.responseContainer.html(
						SpUserSelector.responseContainer.html() + '<button class="btn btn-sm mt-1 w-100' + active + '" type="button" value="' + e.id + '" name="userSelect">' + e.text + '</button>'
					);
				} );
				SpUserSelector.responseContainer.find( '.btn' ).click( function () {
					SpUserSelector.active = true;
					SpUserSelector.selected = {'id': SobiPro.jQuery( this ).val(), 'text': SobiPro.jQuery( this ).html()};
					SpUserSelector.responseContainer.find( '.btn' ).removeClass( 'active' ).attr( 'data-spctrl-state', '' );
					SpUserSelector.responseContainer.find( '.btn' ).removeClass( 'btn-yes' );
					SpUserSelector.responseContainer.find( '.btn' ).addClass( 'btn-unchecked' );
					SobiPro.jQuery( this ).removeClass( 'btn-unchecked' );
					SobiPro.jQuery( this ).addClass( 'btn-yes' );
				} );
			}
		} );
	};
}
