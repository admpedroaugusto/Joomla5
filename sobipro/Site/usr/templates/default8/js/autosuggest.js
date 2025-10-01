/**
 * @package Default Template V8 for SobiPro multi-directory component with content construction support
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006–2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/GPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according to section 7 of GPL v3.
 * See https://www.gnu.org/licenses/gpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * @modified 29 November 2024 by Sigrid Suski
 */

// Search suggest script for autoComplete included in SobiPro (for search box and quicksearch)
;SobiCore.Ready( () => {
	let selector = '.search-query';
	let inputField = document.querySelector( selector );
	if ( inputField ) {
		let id = inputField.getAttribute( 'id' );
		//inputField.setAttribute( 'data-toggle', 'dropdown' );

		// noinspection JSPotentiallyInvalidConstructorUsage
		const acComplete = new autoComplete( {
			selector: () => {
				return inputField;
			},
			threshold: 1,   /* adjust 'search.suggest_min_chars */
			wrapper: bs > 2,
			data: {
				/* normally all possible values, for us already the results */
				src: async() => {
					if ( inputField.value ) {
						return await SobiCore.Post( {
							'option': 'com_sobipro',
							'sid': SobiProSection,
							'task': 'search.suggest',
							'term': inputField.value,
							// 'method': 'xhr'
						}, SPLiveSite + 'index.php' )
						// .then( content => content.data );
					}
				},
				keys: false,
				cache: false,
			},
			resultsList: {
				tag: 'div',
				id: id + '_suggestlist',
				class: 'sp-autocomplete-suggest dropdown-menu dropdown-alpha',
				tabSelect: true,
				destination: () => {
					return inputField;
				},
				position: 'afterend',
				element: ( list, data ) => {
					if ( data.matches.length ) {
						list.classList.add( 'show' );
					}
				},
				noResults: false,
				maxResults: 20,
			},
			resultItem: {
				tag: 'button',
				id: id + '_suggestitem',
				class: 'dropdown-item',
				highlight: 'sp-highlight',
				selected: 'active',
				element: ( list, data ) => {
					list.setAttribute( 'type', 'button' );
				},
			},
			events: {
				input: {
					selection: ( event ) => {
						const feedback = event.detail;
						/* Replace Input value with the selected value */
						acComplete.input.value = feedback.selection.value;
					},
					close: ( event ) => {
					}
				}
			},
			submit: true,
		} );
	}
} );
