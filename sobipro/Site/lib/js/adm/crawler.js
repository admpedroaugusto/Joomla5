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
 * @modified 15 October 2021 by Sigrid Suski
 */
function SobiProCrawler(e){var s=this;this.message=SobiPro.jQuery("#spctrl-progress-message"),this.spinner='<span class="fas fa-sync fa-spin" aria-hidden="true"></span>&nbsp;',SobiPro.jQuery("#spctrl-crawler-response").find(".invalidate").remove(),this.row=SobiPro.jQuery("#spctrl-crawler-response").find("tbody").find("tr"),SobiPro.jQuery("#spctrl-crawler-response").find("tbody").find("tr").addClass("hidden"),this.request={option:"com_sobipro",format:"raw",task:e,sid:SobiProSection},this.setMessage=function(e,s){s?this.message.html(this.spinner+e):this.message.html(e)},this.setMessage(SobiPro.Txt("PROGRESS_WORKING"),!0),SobiPro.jQuery("#spctrl-crawler-response").removeClass("hidden"),this.parseResponse=function(e){SobiPro.jQuery.each(e,(function(e,r){let a;switch(r.code){case 200:a='<span class="badge bg-success">'+r.code+"</span>";break;case 412:a='<span class="badge bg-info">'+r.code+"</span>";break;case 501:a='<span class="badge bg-danger">'+r.code+"</span>";break;default:a='<span class="badge bg-warning">'+r.code+"</span>"}var t=s.row.clone();t.find(".url").html(r.url),t.find(".code").html(a),t.find(".links").html(r.count),t.find(".time").html(r.time),t.removeClass("hidden"),t.addClass("invalidate"),SobiPro.jQuery("#spctrl-crawler-response").find("tbody").prepend(t)}))},this.getResponse=function(){SobiPro.jQuery.ajax({type:"post",url:SPLiveSite+"index.php",data:s.request,dataType:"json",success:function(e){s.request.task="crawler",e.data.length&&(s.setMessage(e.message,!0),s.parseResponse(e.data)),"done"!=e.status?s.getResponse():(s.request.task="crawler.init",s.setMessage(e.message,!1))}})},this.getResponse()}SobiCore.Ready((function(){SobiPro.jQuery("#SPAdminForm").on("BeforeAjaxSubmit",(function(e,s,r){"crawler.init"!=r&&"crawler.restart"!=r||(s.takeOver=!0,new SobiProCrawler(r))}))}));