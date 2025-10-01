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
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @modified 29 March 2021 by Sigrid Suski
 * @modified 15 October 2021 by Sigrid Suski
 */

;SobiCore.Ready( function () {
	SobiPro.jQuery( '.spctrl-entry-reject' ).SobiProRejections();
} );

SobiPro.jQuery.fn.SobiProRejections = function () {
	const proxy = this;
	this.Templates;
	this.header;
	this.RemoveButton = false;
	this.Sid;
	let modal = SobiPro.jQuery( '#spctrl-reject-entry-window' );
	let modalWindow = document.getElementById( 'spctrl-reject-entry-window' );

	this.click( function ( e ) {
		e.preventDefault();
		new bootstrap.Modal( modalWindow ).show();
		//modal.modal();
		if ( modal.find( 'select' ).children( 'option' ).length == 0 ) {
			modal.find( '.spctrl-spinner' ).removeClass( 'hidden' );
			proxy.GetTemplates();
		}
		let title = modal.find( '.modal-title' );
		if ( !(proxy.header) ) {
			proxy.header = title.html();
		}
		proxy.Sid = SobiPro.jQuery( this ).parent().parent().find( '[name="e_sid[]"]' ).val();
		title.html( proxy.header + ' - ' + SobiPro.jQuery( this ).parent().parent().find( '.entry-name' ).find( 'a' ).html() );
	} );

	this.GetTemplates = function () {
		modal.find( 'select' ).find( 'option' ).remove().end();
		let request = {
			'option': 'com_sobipro',
			'task': 'config.rejectionTemplates',
			'sid': SobiProSection,
			'format': 'raw',
			'tmpl': 'component',
			'method': 'xhr'
		};
		SobiPro.jQuery.ajax( {
			url: 'index.php',
			type: 'post',
			dataType: 'json',
			data: request
		} ).done( function ( response ) {
			proxy.Templates = response;
			SobiPro.jQuery.each( response, function ( i, e ) {
				modal.find( 'select' ).append( new Option( e.value, i ) );
			} );
			if ( !(proxy.RemoveButton) ) {
				proxy.RemoveButton = true;
				let deletebutton = modal.find( '.spctrl-remove-tpl' ).removeClass( 'hidden' );
				modal.find( 'select' )
					.after( deletebutton )
					.parent().addClass( 'd-flex' );

				SobiPro.jQuery( '.spctrl-remove-tpl' ).click( function () {
					proxy.RemoveTemplate();
				} )
			}
			proxy.GetTemplate();
			modal.find( '.spctrl-spinner' ).addClass( 'hidden' );
		} );
	};

	this.RemoveTemplate = function () {
		let id = modal.find( 'select' ).find( ':selected' ).val();
		if ( id && confirm( SobiPro.Txt( 'ENTRY_REJECT_TEMPLATE_DELETE' ) ) ) {
			let request = {
				'option': 'com_sobipro',
				'task': 'config.deleteRejectionTpl',
				'sid': SobiProSection,
				'tid': id,
				'format': 'raw',
				'tmpl': 'component',
				'method': 'xhr'
			};
			SobiPro.jQuery( modal.find( ':input' ) ).each( function ( i, b ) {
				let bt = SobiPro.jQuery( b );
				request[ bt.attr( 'name' ) ] = bt.val();
			} );
			SobiPro.jQuery.ajax( {
				url: 'index.php',
				type: 'post',
				dataType: 'json',
				data: request
			} ).done( function ( response ) {
				SigsiuBox.message( 'sp-done-box', response.message.text );
				//alert( response.message.text );
				proxy.GetTemplates();
			} );
		}
	};
	this.GetTemplate = function () {
		let id = modal.find( 'select' ).find( ':selected' ).val();
		if ( id && this.Templates[ id ] ) {
			modal.find( '[name="reason"]' ).val( this.Templates[ id ].description );

			//modal.find( '[name="trigger.unpublish"]' ).button( 'toggle' ); //????

			SobiPro.jQuery.each( this.Templates[ id ].params, function ( i, e ) {
				if ( e ) {
					modal.find( '[name="' + i + '"][value="1"]' ).addClass( 'btn-yes' )
						.removeClass( 'btn-unchecked' )
						.attr( 'data-spctrl-state', 'active' )
						.removeClass( 'active' );
					modal.find( '[name="' + i + '"][value="0"]' ).removeClass( 'btn-no' )
						.addClass( 'btn-unchecked' )
						.attr( 'data-spctrl-state', '' )
						.removeClass( 'active' );
				}
				else {
					modal.find( '[name="' + i + '"][value="0"]' ).addClass( 'btn-no' )
						.removeClass( 'btn-unchecked' )
						.attr( 'data-spctrl-state', 'active' )
						.removeClass( 'active' );

					modal.find( '[name="' + i + '"][value="1"]' ).removeClass( 'btn-yes' )
						.addClass( 'btn-unchecked' )
						.attr( 'data-spctrl-state', '' )
						.removeClass( 'active' );
				}
			} );
		}
	};

	modal.find( 'select' ).change( function () {
		proxy.GetTemplate();
	} );

	modal.find( '.spctrl-reject' ).click( function ( e ) {
		e.preventDefault();
		let request = {
			'option': 'com_sobipro',
			'task': 'entry.reject',
			'sid': proxy.Sid,
			'format': 'raw',
			'tmpl': 'component',
			'method': 'xhr'
		};
		SobiPro.jQuery( modal.find( ':input' ) ).each( function ( i, b ) {
			let bt = SobiPro.jQuery( b );
			request[ bt.attr( 'name' ) ] = bt.val();
		} );
		SobiPro.jQuery( modal.find( ':button' ) ).each( function ( i, b ) {
			let bt = SobiPro.jQuery( b );
			if ( bt.hasClass( 'active' ) || bt.attr( 'data-spctrl-state' ) == 'active' ) {
				request[ bt.attr( 'name' ) ] = bt.val();
				bt.removeClass( 'active' );
			}
		} );
		SobiPro.jQuery.ajax( {
			url: 'index.php',
			type: 'post',
			dataType: 'json',
			data: request
		} ).done( function ( response ) {
			if ( response.redirect.execute ) {
				window.location.replace( response.redirect.url );
			}
			else {
				SigsiuBox.message( 'sp-done-box', response.message.text );
				//alert( response.message.text );
			}
		} );
	} );

	modal.find( '.spctrl-save-tpl' ).click( function ( e ) {
		e.preventDefault();
		let name = window.prompt( SobiPro.Txt( 'ENTRY_REJECT_TEMPLATE_NAME_PROMPT' ), modal.find( 'select' ).find( ':selected' ).text() );
		if ( name && name.length ) {
			let request = {
				'option': 'com_sobipro',
				'task': 'config.saveRejectionTpl',
				'templateName': name,
				'sid': SobiProSection,
				'format': 'raw',
				'tmpl': 'component',
				'method': 'xhr',
				'reason': modal.find( 'textarea' ).val()
			};
			SobiPro.jQuery( modal.find( ':input' ) ).each( function ( i, b ) {
				let bt = SobiPro.jQuery( b );
				request[ bt.attr( 'name' ) ] = bt.val();
			} );
			SobiPro.jQuery( modal.find( ':button' ) ).each( function ( i, b ) {
				let bt = SobiPro.jQuery( b );
				if ( bt.hasClass( 'active' ) || bt.attr( 'data-spctrl-state' ) == 'active' ) {
					request[ bt.attr( 'name' ) ] = bt.val();
				}
			} );

			SobiPro.jQuery.ajax( {
				"url": 'index.php',
				"type": 'post',
				"dataType": 'json',
				"data": request
			} ).done( function ( response ) {

				SigsiuBox.message( 'sp-done-box', response.message.text );
				//alert( response.message.text );
				proxy.GetTemplates();
			} );
		}
	} );
};
