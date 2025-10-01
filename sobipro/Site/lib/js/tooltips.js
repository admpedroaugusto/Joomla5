/**
 * @package: SobiPro Library

 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET

 * @copyright Copyright (C) 2006 - 2022 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 *
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 16 October 2012 by Radek Suski
 * @modified 15 December 2022 by Sigrid Suski
 */
SobiCore.Ready((function(){try{let e=[].slice.call(document.querySelectorAll("a[rel=sp-tooltip]"));e.map((function(e){return new bootstrap.Tooltip(e,{html:!0,animation:!1})})),e=SobiCore.QueryAll("a[rel=sp-tooltip]"),e&&e.forEach((function(e){e.addEventListener("click",(e=>{"#"===e.currentTarget.getAttribute("href")&&e.preventDefault()}))}))}catch(e){}try{let e;e=5===bs?"popover-arrow":"arrow";let t='<div class="popover" role="tooltip"><div class="'+e+'"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>';[].slice.call(document.querySelectorAll("a[rel=sp-popover]")).map((function(e){return new bootstrap.Popover(e,{html:!0,template:t,animation:!1})}))}catch(e){}}));