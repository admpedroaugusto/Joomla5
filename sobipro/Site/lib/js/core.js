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

 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created Wed, Nov 4, 2020 13:55:41 by Radek Suski
 * @modified 05 October 2023 by Sigrid Suski
 */
"use strict";const SobiCore={Ready:e=>{document.addEventListener("DOMContentLoaded",e)},ClearCache:()=>{window.location.reload()},DebOut:e=>{try{console.log(e)}catch(e){}},Log:e=>{try{console.log(e)}catch(e){}},InArray:(e,t,r)=>null==t?-1:t.indexOf(e,r),Els:(e,t="",r="spctrl",o="data-")=>SobiCore.El(e,t,r,o,!0),Elements:({name:e,suffix:t="",prefix:r="spctrl",globalPrefix:o="data-"}={})=>SobiCore.El(e,t,r,o,!0),El:(e,t="",r="spctrl",o="data-",n=!1)=>{t.length&&(t="-"+t);let l=SobiCore.QueryAll("["+o+r+t+'="'+e+'"]');return void 0!==l&&(l.length>1||n?l:l[0])},Element:({name:e,suffix:t="",prefix:r="spctrl",globalPrefix:o="data-",forceArray:n=!1}={})=>SobiCore.El(e,t,r,o,n),SerialiseForm:e=>{const t=[];return e.forEach((e=>{t.push({name:e.getName(),value:e.getValue()})})),t},CreateElementFromHTML:e=>{let t=document.createElement("div");return t.innerHTML=e.trim(),t.firstChild},ShowWorkingState:e=>{let t=SobiCore.Query("#SobiPro"),r=SobiCore.Query("#spctrl-spinner");r&&(e?(t.style.opacity=.3,t.parentNode.insertBefore(r,t),r.classList.remove("hidden")):(t.style.opacity=1,r.classList.add("hidden")))},CreateRequest:(e,t="")=>{const r=[];for(let t in e)e.hasOwnProperty(t)&&r.push(t+"="+encodeURIComponent(e[t]));return t+r.join("&")},Post:async(e,t,r="json",o={"Content-Type":"application/x-www-form-urlencoded",Accept:"application/json"})=>{const n=await fetch(t,{method:"POST",mode:"cors",cache:"no-cache",credentials:"same-origin",headers:o,redirect:"follow",referrerPolicy:"no-referrer",body:SobiCore.CreateRequest(e)});return"json"===r?n.json():n.text()},QueryAll:e=>{if(null!=document.querySelector(e))return SobiCore.ExtendElement(document.querySelectorAll(e))},Query:e=>{if(null!=document.querySelector(e))return SobiCore.ExtendElement([document.querySelector(e)])[0]},Q:e=>SobiCore.Query(e),ExtendElement:e=>(e.forEach((e=>{e.val=(t=null)=>(t&&(e.value=t),e.value),e.getValue=()=>e.value,e.getName=()=>e.name,e.attr=(t,r)=>{if(void 0===r)return e.getAttribute(t);e.setAttribute(t,r)},e.change=t=>{e.addEventListener("change",t)},e.click=t=>{e.addEventListener("click",t)},e.addClass=t=>{e.classList.add(t)},e.removeClass=t=>{e.classList.remove(t)},e.on=(t,r)=>{e.addEventListener(t,r)},e.find=t=>{if(null!=e.querySelector(t))return SobiCore.ExtendElement(e.querySelectorAll(t))}})),e)};
document.addEventListener("DOMContentLoaded", function() {
    // Seleciona todos os elementos com a classe "spClassViewSelect_status"
    const statusElements = document.querySelectorAll('.spClassViewSelect_status');

    // Itera sobre cada elemento
    statusElements.forEach(function(statusElement) {
        // Encontra o elemento filho com a classe "sp-entry-value"
        const valueElement = statusElement.querySelector('.sp-entry-value');

        if (valueElement) {
            // Obtém o texto do elemento filho e remove espaços extras
            const labelText = valueElement.textContent.trim().toLowerCase();

            // Define a cor do fundo de acordo com o texto
            switch (true) {
                case labelText === 'em andamento':
                    statusElement.style.backgroundColor = '#4aa60b';
                    break;
                case labelText === 'homologado' || labelText === 'homologada':
                    statusElement.style.backgroundColor = '#4382df';
                    break;
                case labelText === 'encerrado' || labelText === 'encerrada':
                    statusElement.style.backgroundColor = '#bbbbbb';
                    break;
                case labelText === 'cancelado' || labelText === 'cancelada':
                    statusElement.style.backgroundColor = '#e14f5d';
                    break;
                default:
                    statusElement.style.backgroundColor = '#ffc107';
                    // Cor padrão (amarelo)
            }
        }
    });
});

document.addEventListener("DOMContentLoaded", function () {
    // Seleciona os elementos
    let inputField = document.getElementById("field_nome");
    let categorySelect = document.getElementById("field_categoria");
    let modalitySelect = document.getElementById("field_modalidade");
    let statusSelect = document.getElementById("field_status"); // Campo de status

    // Obtém o ano atual dinamicamente
    let currentYear = new Date().getFullYear();

    // Função para gerar dinamicamente os mapeamentos entre anos/modalidades e seus valores nos selects
    function generateMappings() {
        let yearMapping = {}; // Mapeamento de anos para valores do select de categoria
        let modalityMapping = {}; // Mapeamento de modalidades para valores do select de modalidade
        let inProgressValue = null; // Armazena o valor da opção "Em andamento"
        let statusMapping = { "em andamento": null, "encerrado": null, "encerrada": null };

        // Mapeia categorias
        let categoryOptions = categorySelect.options;
        for (let i = 0; i < categoryOptions.length; i++) {
            let text = categoryOptions[i].textContent.trim(); // Texto da opção
            let value = categoryOptions[i].value; // Valor da opção

            // Captura anos dinamicamente
            let yearMatch = text.match(/\b(201[0-9]|202[0-9])\b/);
            if (yearMatch) {
                let year = yearMatch[0];
                yearMapping[year] = value;
            }

            // Identifica a opção "Em andamento" dinamicamente
            if (text.toLowerCase().includes("em andamento")) {
                inProgressValue = value;
            }
        }

        // Mapeia modalidades
        let modalityOptions = modalitySelect.options;
        for (let i = 0; i < modalityOptions.length; i++) {
            let text = modalityOptions[i].textContent.trim().toLowerCase(); // Texto da opção (em minúsculas)
            let value = modalityOptions[i].value; // Valor da opção

            // Salva no mapeamento, associando o nome da modalidade ao value correspondente
            modalityMapping[text] = value;
        }

        // Mapeia status
        let statusOptions = statusSelect.options;
        for (let i = 0; i < statusOptions.length; i++) {
            let text = statusOptions[i].textContent.trim().toLowerCase();
            let value = statusOptions[i].value;

            if (text.includes("em andamento")) {
                statusMapping["em andamento"] = value;
            } else if (text.includes("encerrado")) {
                statusMapping["encerrado"] = value;
            } else if (text.includes("encerrada")) {
                statusMapping["encerrada"] = value;
            }
        }

        return { yearMapping, inProgressValue, modalityMapping, statusMapping };
    }

    // Função para detectar o ano e a modalidade digitados e atualizar os selects
    function detectChanges() {
        let inputValue = inputField.value.toLowerCase(); // Converte para minúsculas para busca flexível
        let { yearMapping, inProgressValue, modalityMapping, statusMapping } = generateMappings(); // Obtém os mapeamentos dinâmicos

        let selectedYear = null;

        // Expressão regular para capturar anos de 2010 a 2029
        let yearMatch = inputValue.match(/\b(201[0-9]|202[0-9])\b/);
        if (yearMatch) {
            selectedYear = parseInt(yearMatch[0]);

            if (selectedYear === currentYear && inProgressValue) {
                categorySelect.value = inProgressValue; // Seleciona "Em andamento" se for o ano atual
            } else if (yearMapping[selectedYear]) {
                categorySelect.value = yearMapping[selectedYear]; // Seleciona a opção correspondente ao ano
            } else {
                categorySelect.value = ""; // Reseta se não houver correspondência
            }
        } else {
            categorySelect.value = ""; // Reseta se nenhum ano válido for encontrado
        }

        // Verifica se o input contém alguma palavra-chave de modalidade e seleciona a correspondente
        for (let key in modalityMapping) {
            if (inputValue.includes(key)) {
                modalitySelect.value = modalityMapping[key]; // Seleciona a modalidade encontrada
                break; // Para a busca ao encontrar a primeira correspondência
            }
        }

        // Define o status com base no ano identificado
        if (selectedYear !== null) {
            if (selectedYear === currentYear && statusMapping["em andamento"]) {
                statusSelect.value = statusMapping["em andamento"];
            } else if (statusMapping["encerrado"]) {
                statusSelect.value = statusMapping["encerrado"];
            } else if (statusMapping["encerrada"]) {
                statusSelect.value = statusMapping["encerrada"];
            }
        } else {
            statusSelect.value = ""; // Reseta se nenhum ano for identificado
        }
    }

    // Adiciona o evento de input para monitorar mudanças no campo de texto
    inputField.addEventListener("input", detectChanges);
});