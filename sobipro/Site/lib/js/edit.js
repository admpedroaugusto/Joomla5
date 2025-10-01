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

 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * This is the default JavaScript for the edit screen for old templates for SobiPro 1.0.
 * It requires a default or default based frontend template (default2 to default6)
 *
 * @deprecated since 2.0. Will be removed in 3.0
 *
 * @created 15 January 2013 by Radek Suski
 * @modified 02 September 2022 by Sigrid Suski
 */
function SPTriggerFrakingWYSIWYGEditors(){try{var e=Object.keys(tinyMCE.editors);SobiPro.jQuery.each(e,(function(e,r){0!=r&&(tinyMCE.execCommand("mceToggleEditor",!1,r),tinyMCE.execCommand("mceToggleEditor",!1,r))}))}catch(e){}for(var r=["unload","onbeforeunload","onunload"],o=0;o<r.length;o++){try{window.dispatchEvent(r[o])}catch(e){}try{window.fireEvent(r[o])}catch(e){}try{SobiPro.jQuery(document).triggerHandler(r[o])}catch(e){}try{SobiPro.jQuery.each(CKEDITOR.instances,(function(e,r){r.destroy(),CKEDITOR.replace(e)}))}catch(e){}}try{tinyMCE.triggerSave()}catch(e){}try{SobiPro.jQuery.each(Joomla.editors.instances,(function(){this.save()}))}catch(e){}}function SPTriggerSpinner(){}SobiPro.jQuery(document).ready((function(){var e='<div class="popover"><div class="arrow"></div><div class="popover-inner"><div class="pull-right close spclose">x</div><h3 class="popover-title"></h3><div class="popover-content"><p></p></div></div></div>';function r(){"use strict";this.boxes=SobiPro.jQuery(".payment-box");var r=this;this.boxes.each((function(e,r){(r=SobiPro.jQuery(r)).targetContainer=SobiPro.jQuery("#"+r.attr("id").replace("-payment","-input-container")),r.toggleTarget=r.targetContainer.find("*").not("option"),r.targetIframes=r.targetContainer.find("iframe").parent(),r.disableTargets=function(){this.toggleTarget.attr("disabled","disabled"),this.targetContainer.children().css("opacity","0.3"),this.targetIframes.css("display","none")},r.disableTargets(),r.change((function(){SobiPro.jQuery(this).is(":checked")?(r.toggleTarget.removeAttr("disabled"),r.targetContainer.children().css("opacity","1"),r.targetIframes.css("display","")):r.disableTargets()}))})),this.sendRequest=function(){var e=SobiPro.jQuery("#spEntryForm").serialize();SobiPro.jQuery(SobiPro.jQuery("#spEntryForm").find(":button")).each((function(r,o){var t=SobiPro.jQuery(o);t.hasClass("active")&&(e+="&"+t.attr("name")+"="+t.val())})),SobiPro.jQuery.ajax({url:SPLiveSite+"index.php",data:e,type:"post",dataType:"json",success:function(e){if("error"==e.message.type)r.errorHandler(e);else if(1==e.redirect.execute)window.location.replace(SPLiveSite+e.redirect.url);else if("info"==e.message.type){SobiPro.jQuery(e.message.text).appendTo(SobiPro.jQuery("#SobiPro"));var o=SobiPro.jQuery("#SpPaymentModal").find(".modal").modal();o.on("hidden",(function(){SobiPro.jQuery("#SpPaymentModal").remove()})),o.on("hidden.bs.modal",(function(){SobiPro.jQuery("#SpPaymentModal").remove()}))}}})},this.dismissAlert=function(e,r,o){e.popover("hide"),r.addClass("hide"),e.remove(),o.removeClass("error")},this.errorHandler=function(o){SobiPro.jQuery.each(o.data.error,(function(o,t){var i=SobiPro.jQuery("#"+o),a=SobiPro.jQuery("#"+o+"-message");let n=SobiCore.Query("#"+o+"-message");var s=SobiPro.jQuery("#"+o+"-container");s.addClass("error");if(a.length){var c=SobiPro.jQuery('<a data-placement="bottom" rel="sp-popover" data-content="'+t+'" data-original-title="'+SobiPro.Txt("ATTENTION")+'">&nbsp;</a>');a.append(c),n.classList.remove("hide","hidden"),c.popover({template:e}),c.popover("show");var d=n.querySelector(".close");d&&d.addEventListener("click",(()=>{r.dismissAlert(c,a,s)})),n.scrollIntoView(),i.focus((function(){r.dismissAlert(c,a,s)}))}else alert(t)}))},SobiPro.jQuery(".sobipro-submit").click((function(e){SPTriggerSpinner(),SPTriggerFrakingWYSIWYGEditors(),r.sendRequest(),e.preventDefault(),e.stopPropagation()})),SobiPro.jQuery(".sobipro-cancel").click((function(e){SobiPro.jQuery("#SP_task").val("entry.cancel"),r.sendRequest()}))}SobiPro.jQuery("a[rel=popover]").popover({html:!0,trigger:"click",template:e}).click((function(e){e.preventDefault();var r=SobiPro.jQuery(this);r.parent().find(".close").click((function(){r.popover("hide")}))})),setTimeout((function(){new r}),1e3)}));