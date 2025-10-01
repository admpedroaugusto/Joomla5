/**
 * @package: SobiPro Library
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2020 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @modified 15 October 2021 by Sigrid Suski
 */

;
function SP_close() {
	$( "sbox-btn-close" ).fireEvent( "click" );
	let semaphor = 0;
}

function SPCatPageNav( site, sid ) {
	SPPageNav( site, sid, 'cLimStart' );
}

function SPEntriesPageNav( site, sid ) {
	SPPageNav( site, sid, 'eLimStart' );
}

function SPCatPageLimit( sid ) {
	SPPageNavLimit( sid, 'cLimStart' );
}

function SPEntriesPageLimit( sid ) {
	SPPageNavLimit( sid, 'eLimStart' );
}

function SPPageNav( site, sid, id ) {
	SPSid( sid );
	SP_id( id ).value = site;
	SP_id( 'SPAdminForm' ).submit();
}

function SPPageNavLimit( sid, id ) {
	SPSid( sid );
	SP_id( id ).value = 0;
	SP_id( 'SPAdminForm' ).submit();
}

function SPReorder( type, sid ) {
	SP_id( 'task' ).value = type + '.reorder';
	SPSid( sid );
	SP_id( 'SPAdminForm' ).submit();
}

function SPOrdering( col, dir, name, sid ) {
	SPAddAdmFormVal( name, col + '.' + dir );
	SPSid( sid );
	SP_id( 'SPAdminForm' ).submit();
}

function SPSid( sid ) {
	SPAddAdmFormVal( 'sid', sid );
}

function SPAddAdmFormVal( name, value ) {
	let s = document.createElement( 'input' );
	s.setAttribute( 'name', name );
	s.setAttribute( 'type', 'hidden' );
	s.setAttribute( 'value', value );
	SP_id( 'SPAdminForm' ).appendChild( s );
}

function SPCheckListElements( name, toggler ) {
	let el = spName( name + '[]' );
	let on = toggler.value == 1;
	for ( let i = 0; i < el.length; i++ ) {
		el[ i ].checked = on;
	}
	SP_id( 'boxchecked' ).value = (toggler.value == 1) ? el.length : 0;
	toggler.value = (toggler.value == 1) ? 0 : 1;
}

function SPCheckListElement( toggler ) {
	SP_id( 'boxchecked' ).value = (toggler.checked == true) ? +1 : -1;
}

function SPResetCount( type ) {
	if ( SP_id( 'sp_counter' ).value && SP_id( type + '.id' ).value ) {
		SobiPro.Request( SobiProAdmUrl.replace( '%task%', type + '.resetCounter' ) + '&sid=' + SP_id( type + '.id' ).value + '&format=raw', {
			method: 'get', onComplete: function () {
				SP_id( 'sp_counter' ).value = 0;
			}
		} ).request();
	}
}
