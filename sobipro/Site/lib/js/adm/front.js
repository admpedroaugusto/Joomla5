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
 * @modified 05 December 2022 by Sigrid Suski
 */
let updateSemaphor=0;SobiCore.Ready((function(){let e=document.getElementById("spctrl-updates-pane");e&&e.addEventListener("shown.bs.collapse",(e=>{if(0===updateSemaphor){updateSemaphor=1;let e=document.getElementById("spctrl-versions");e&&(e.innerHTML=SobiPro.Txt("CHECKING_FOR_UPDATES")+'&nbsp;<img src="../media/sobipro/adm/progress.gif" alt="in progress"/>',SobiCore.Post({},SobiProAdmUrl.replace("%task%","extensions.updates")).then((t=>{"use strict";if(t.err)e.innerHTML='<span class="text-danger">'+t.err+"</span>";else{let a="";for(let e in t){let s,d,i=t[e].name,r='title="'+i+": "+t[e].update_txt+'"';"false"===t[e].update?(s='<div class="fas fa-check"'+r+' aria-hidden="true"></div>',d=" dk-uptodate"):(s='<div class="fas fa-times" '+r+' aria-hidden="true"></div>',d=" dk-outdated",i="SobiPro"===i?'<a href="index.php?option=com_installer&view=update" '+r+">"+i+"</a>":'<a href="index.php?option=com_sobipro&task=extensions.browse" '+r+">"+i+"</a>"),a+='<div class="dk-updates-app"><span class="dk-updates-state'+d+'"> '+s+" "+i+" ("+t[e].update_txt+")</span></div>"}e.innerHTML=a}})))}}))}));