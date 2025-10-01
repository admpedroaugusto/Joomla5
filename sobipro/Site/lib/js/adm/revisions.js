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
 * @created 30 August 2013 by Radek Suski
 * @modified 15 October 2021 by Sigrid Suski
 */

;SobiCore.Ready( function () {
	let compareMap = [].slice.call( document.querySelectorAll( '.spctrl-revision-compare' ) );
	compareMap.map( ( el, index ) => {
		el.addEventListener( "click", ( e ) => {
			e.preventDefault();
			new bootstrap.Modal( document.getElementById( 'spctrl-revisions-window' ) ).show();
			document.querySelector( '.spctrl-diff' ).innerHTML = '<div class="spctrl-spinner dk-spinner-modal"><div class="fas fa-spinner fa-spin fa-lg" aria-hidden="true"></div></div>';
			let request = {
				'option': 'com_sobipro',
				'task': 'entry.revisions',
				'sid': document.getElementById( 'SP_sid' ).value,
				'format': 'raw',
				'tmpl': 'component',
				'method': 'xhr',
				'revision': document.getElementById( 'SP_revision' ).value,
				'fid': e.currentTarget.dataset.fid,
				'html': 1
			};
			SobiPro.jQuery.ajax( {
				url: 'index.php',
				type: 'post',
				dataType: 'json',
				data: request
			} ).done( function ( response ) {
				document.querySelector( '.spctrl-diff' ).innerHTML = response.diff;
			} );
		} );
	} );
} );

