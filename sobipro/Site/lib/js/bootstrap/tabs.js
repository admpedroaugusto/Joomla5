/**
 * @package: SobiPro Library

 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET

 * @copyright Copyright (C) 2006 - 2021 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 17 June 2021 by Sigrid Suski
 * @modified 30 November 2022 by Sigrid Suski
 *
 * Compatibility file for old SobiPro templates!
 * @deprecated since 2.0; will be removed in 3.0
 */

/* compatibility script for old templates */
;var script = document.createElement( 'script' );
script.src = SPLiveSite + 'components/com_sobipro/lib/js/adm/tabs.js';
script.type = 'text/javascript';
document.head.appendChild( script );
