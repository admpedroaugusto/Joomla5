/**
 * @package SobiPro Library
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006–2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according to section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 01 August 2012 by Radek Suski
 * @modified 26 August 2024 by Sigrid Suski
 */
SobiCore.Ready((function(){function e(){if("block"===SobiPro.jQuery("#spctrl-menu").css("display")){SobiPro.jQuery("#spctrl-menu").hide("fast"),SobiPro.jQuery("#spctrl-menu-toggler").hide(),SobiPro.jQuery("#spctrl-menu-toggler").html(SobiPro.jQuery("#spctrl-togglemenu").html()),SobiPro.jQuery("#spctrl-togglemenu-btn").removeClass("w-100"),SobiPro.jQuery("#spctrl-togglemenu-btn").removeClass("btn-sm"),SobiPro.jQuery("#spctrl-togglemenu-icon").addClass("fa-toggle-off"),SobiPro.jQuery("#spctrl-togglemenu-icon").removeClass("fa-toggle-on"),SobiPro.jQuery("#spctrl-menu-toggler").show(),SobiPro.jQuery("#spctrl-menu").siblings("div").removeClass("col-lg-9").addClass("col-lg-12"),SobiPro.jQuery("#spctrl-togglemenu-btn").click((function(){e()}));try{localStorage.setItem("SobiProSideMenu","closed")}catch(e){}}else{SobiPro.jQuery("#spctrl-menu").show("fast"),SobiPro.jQuery("#spctrl-menu-toggler").html(""),SobiPro.jQuery("#spctrl-menu").siblings("div").removeClass("col-lg-12").addClass("col-lg-9");try{localStorage.setItem("SobiProSideMenu","open")}catch(e){}}}SobiPro.jQuery("#spctrl-togglemenu-btn").click((function(){e()}));document.querySelectorAll("#spctrl-menu .collapse").forEach((e=>{const o=bootstrap.Collapse.getOrCreateInstance("#"+e.getAttribute("id"),{toggle:!1}),r=o.hide,t=()=>{try{throw new Error}catch(e){-1==e.stack.indexOf("atum/js/template")?(o.hide=r,o.hide()):o.hide=t}};o.hide=t}));"closed"===localStorage.getItem("SobiProSideMenu")&&SobiPro.jQuery("#spctrl-togglemenu-btn").click()}));