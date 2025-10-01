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
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @modified 10 January 2022 by Sigrid Suski
 */

;SobiCore.Ready( function () {
	let dependencyBtn = SobiCore.Query( '#field-dependency' );

	function spSwitchDependency() {
		let selected = dependencyBtn.attr( 'data-spctrl-active' );
		if ( selected === "1" ) {
			SobiCore.El( 'dependency' ).removeClass( 'hidden' );
			SobiCore.El( 'regular' ).addClass( 'hidden' );
		}
		else {
			SobiCore.El( 'dependency' ).addClass( 'hidden' );
			SobiCore.El( 'regular' ).removeClass( 'hidden' );
		}
	}

	spSwitchDependency();
	dependencyBtn.click( function () {
		spSwitchDependency();
	} );
} );
