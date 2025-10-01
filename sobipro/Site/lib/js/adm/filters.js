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
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 22 January 2013 by Radek Suski
 * @modified 09 February 2022 by Sigrid Suski
 */
SobiCore.Ready((()=>{try{let e=SobiCore.QueryAll(".spctrl-filter-edit"),t=document.querySelector("[data-lang]").getAttribute("data-lang"),r=SobiProAdmUrl.replace("%task%","filter.edit")+"&tmpl=component&sp-language="+t;e&&e.forEach((function(e){e.addEventListener("click",(e=>{let t=!1,l="",i=e.currentTarget;i.hasAttribute("rel")&&(l=i.getAttribute("rel"),r+="&fid="+l);let o=SobiCore.Query("#spctrl-modal");if(o){o.querySelector(".modal-body").innerHTML='<iframe src="'+r+'" id="spctrl-frame" class="container-fluid dk-frame"> </iframe>';let e=SobiCore.Query("#spctrl-frame");e.addEventListener("load",(t=>{let r=SobiCore.Query("#spctrl-filter-delete");r&&(null===e.contentDocument.querySelector("#filter-regex").getAttribute("readonly")?(r.removeAttribute("disabled"),r.addEventListener("click",(e=>{e.preventDefault(),document.location=SobiProAdmUrl.replace("%task%","filter.delete")+"&filter_id="+l}))):r.setAttribute("disabled","disabled"))}))}if(l.length){if(void 0===SobiCore.Query("#spctrl-filter-delete")){let e=o.querySelector(".modal-footer"),t=document.createElement("button");t.setAttribute("class","btn btn-danger"),t.setAttribute("disabled","disabled"),t.setAttribute("data-bs-dismiss","modal"),t.setAttribute("id","spctrl-filter-delete"),t.innerHTML=SobiPro.Txt("DELETE_FILTER");let r=document.createElement("div");r.appendChild(t),e.appendChild(r)}}else{let e=SobiCore.Query("#spctrl-filter-delete");e&&e.parentElement.remove()}const a=SobiCore.El("edit-modal","role");new bootstrap.Modal(a).show(),SobiCore.El("save-modal").click((()=>{t=!0;const e=SobiPro.jQuery("#spctrl-frame").contents().find("body #SPAdminForm");SobiPro.jQuery.ajax({url:"index.php",data:e.serialize(),type:"post",dataType:"json"}).done((t=>{const r='<div class="alert alert-'+t.message.type+' alert-dismissible mt-0">'+t.message.text+'<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="'+SobiPro.Txt("ACCESSIBILITY.CLOSE")+'"></button></div>';e.find("#spctrl-message").html(r),t.data.required&&e.find('[name^="'+t.data.required+'"]').addClass("danger").focus().focusout((function(){SobiPro.jQuery(this).val()&&SobiPro.jQuery(this).removeClass("danger").addClass("success")}))}))})),a.addEventListener("hidden.bs.modal",(e=>{t&&window.location.replace(String(window.location).replace("#",""))}))}))}))}catch(e){}}));