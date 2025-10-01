/**
 * @package SobiPro Library
 *
 * @author Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * @url https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 17 May 2022 by Sigrid Suski
 * @modified 16 January 2024 by Sigrid Suski
 */

/* define Bootstrap version for front- and back-end; this file needs to be included after the core.js file */
;let sobipro = document.getElementById( 'SobiPro' );
sobipro = sobipro ? sobipro : document.querySelector( '.SobiPro' );
const bs = sobipro ? (sobipro.dataset.bs ? parseInt( sobipro.dataset.bs ) : 5) : 5;
const site = sobipro ? ( sobipro.dataset.site ? sobipro.dataset.site : 'administrator' ) : 'administrator';
console.log( 'Bootstrap is set to ' + bs + ' on ' + site );

