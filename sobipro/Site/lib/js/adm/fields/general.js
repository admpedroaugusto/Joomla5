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
 * @created 13 August 2015 by Radek Suski
 * @modified 17 January 2022 by Sigrid Suski
 */

;SobiCore.Ready( function () {
	function spSwapSecondOrdering() {
		let selected = SobiCore.El( 'search-ordering' ).val();
		selected !== 'priority' ? SobiCore.El( 'sm-second' ).addClass( 'hidden' ) : SobiCore.El( 'sm-second' ).removeClass( 'hidden' );
		selected !== 'priority' ? SobiCore.El( 'sm-order-fields' ).removeClass( 'hidden' ) : SobiCore.El( 'sm-order-fields' ).addClass( 'hidden' );
	}

	spSwapSecondOrdering();
	SobiCore.El( 'search-ordering' ).change( function () {
		spSwapSecondOrdering();
	} );

	function spCDNSettings() {
		let selected = SobiCore.El( 'framework' ).val();
		selected === '2' ? SobiCore.El( 'cdn' ).removeClass( 'hidden' ) : SobiCore.El( 'cdn' ).addClass( 'hidden' );
	}

	spCDNSettings();
	SobiCore.El( 'framework' ).change( function () {
		spCDNSettings();
	} );
} );
