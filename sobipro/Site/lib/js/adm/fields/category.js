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
 * @created 16 October 2012 by Radek Suski
 * @modified 15 October 2021 by Sigrid Suski
 */

;SobiCore.Ready( function () {
	spCategorySwapMethod( SobiPro.jQuery( '#field-method' ).val() );
	SobiPro.jQuery( '#field-method' ).change( function () {
		spCategorySwapMethod( SobiPro.jQuery( this ).val() );
	} );

	function spCategorySwapMethod( method ) {
		SobiPro.jQuery( '.sp-chooser-method' ).hide();
		SobiPro.jQuery( '.sp-chooser-method :input' ).attr( 'disabled', 'disabled' );
		SobiPro.jQuery( '#sp-chooser-' + method + ' :input' ).removeAttr( 'disabled', 'disabled' );
		SobiPro.jQuery( '#sp-chooser-' + method ).show();
		if ( method == 'fixed' ) {
			SobiPro.jQuery( '#field-editable :button' ).attr( 'disabled', 'disabled' );
			SobiPro.jQuery( '#field-editlimit' ).attr( 'disabled', 'disabled' );
		}
		else {
			SobiPro.jQuery( '#field-editable :button' ).removeAttr( 'disabled', 'disabled' );
			SobiPro.jQuery( '#field-editlimit' ).removeAttr( 'disabled', 'disabled' );
		}
	}
} );
