<?xml version="1.0" encoding="UTF-8"?><!--
 @package: Colour test template

 @author
 Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 Url: https://www.Sigsiu.NET

 @copyright Copyright (C) 2006–2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 @license GNU/GPL Version 3
 This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 3
 as published by the Free Software Foundation, and under the additional terms according to section 7 of GPL v3.
 See https://www.gnu.org/licenses/gpl.html and https://www.sigsiu.net/licenses.

 This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
-->

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">
	<xsl:output method="xml" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" encoding="UTF-8"/>

	<xsl:include href="../common/globals.xsl"/> <!-- do not comment or remove -->
	<xsl:include href="../common/topmenu.xsl"/>
	<xsl:include href="../common/alphamenu.xsl"/>
	<xsl:include href="../common/messages.xsl"/>
	<xsl:include href="../common/showfields.xsl"/>

	<xsl:template match="/entry_details">

		<h1>Details view template to test the colour themes and custom colours</h1>
		<xsl:if test="not($bs = 5) or not($font = 'fa5')">
			<div class="alert alert-delta">
				The typography page needs Bootstrap 5 and Font Awesome 5 to be set in the section configuration.
				<br/>
				You have set Bootstrap
				<xsl:value-of select="$bs"/> and font <xsl:value-of select="$font"/>. Please change the false setting(s).
			</div>
		</xsl:if>
		<div class="alert alert-delta">There is no functionality in this page. So do not click and expect correct reaction!</div>

		<h2>Colour values of the selected colour theme</h2>
		<p>You need to switch on "Colour test (typography.xsl)"</p>
		<div class="mb-4">
			<div class="debug-text">
				<h1/>
				<p class="color"/>
				<p class="lumi"/>
				<p class="ratio"/>
				<p class="hsl"/>
				<p class="hsv"/>
			</div>
			<div class="debug-alpha">
				<h1/>
				<p class="color"/>
				<p class="lumi"/>
				<p class="ratio"/>
				<p class="hsl"/>
				<p class="hsv"/>
			</div>
			<div class="debug-beta">
				<h1/>
				<p class="color"/>
				<p class="lumi"/>
				<p class="ratio"/>
				<p class="hsl"/>
				<p class="hsv"/>
			</div>
			<div class="debug-gamma">
				<h1/>
				<p class="color"/>
				<p class="lumi"/>
				<p class="ratio"/>
				<p class="hsl"/>
				<p class="hsv"/>
			</div>
			<div class="debug-delta">
				<h1/>
				<p class="color"/>
				<p class="lumi"/>
				<p class="ratio"/>
				<p class="hsl"/>
				<p class="hsv"/>
			</div>
		</div>

		<xsl:call-template name="topMenu">
			<xsl:with-param name="searchbox">true</xsl:with-param>
			<xsl:with-param name="title"/>
		</xsl:call-template>
		<xsl:apply-templates select="alphaMenu"/>

		<h2 class="mt-4">Backgrounds</h2>
		<p>Add 'bg' for corner style and padding.</p>
		<div>
			<h4 class="mt-2">dark backgrounds</h4>
			<div class="bg-alpha mt-2">
				This is a text with class .bg-alpha' set.
			</div>
			<div class="bg-beta mt-2">
				This is a text with class 'bg-beta' set.
			</div>
			<div class="bg-gamma mt-2">
				This is a text with class 'bg-gamma' set.
			</div>
			<div class="bg-delta mt-2">
				This is a text with class 'bg-delta' set.
			</div>
			<h4 class="mt-2">light backgrounds</h4>
			<div class="bg-alpha-dark mt-2">
				This is a text with class .bg-alpha-dark' set.
			</div>
			<div class="bg-alpha-medium mt-2">
				This is a text with class .bg-alpha-medium' set.
			</div>
			<div class="bg-alpha-light mt-2">
				This is a text with class .bg-alpha-light' set.
			</div>
			<div class="bg-beta-dark mt-2">
				This is a text with class .bg-beta-dark' set.
			</div>
			<div class="bg-beta-medium mt-2">
				This is a text with class .bg-beta-medium' set.
			</div>
			<div class="bg-beta-light mt-2">
				This is a text with class .bg-beta-light' set.
			</div>
			<div class="bg-gamma-dark mt-2">
				This is a text with class .bg-gamma-dark' set.
			</div>
			<div class="bg-gamma-medium mt-2">
				This is a text with class .bg-gamma-medium' set.
			</div>
			<div class="bg-gamma-light mt-2">
				This is a text with class .bg-gamma-light' set.
			</div>
			<div class="bg-delta-dark mt-2">
				This is a text with class .bg-delta-dark' set.
			</div>
			<div class="bg-delta-medium mt-2">
				This is a text with class .bg-delta-medium' set.
			</div>
			<div class="bg-delta-light mt-2">
				This is a text with class .bg-delta-light' set.
			</div>
			<h4 class="mt-2">outlined backgrounds</h4>
			<div class="bg-outline-alpha mt-2">
				This is a text with class .bg-outline-alpha' set.
			</div>
			<div class="bg-outline-beta mt-2">
				This is a text with class 'bg-outline-beta' set.
			</div>
			<div class="bg-outline-gamma mt-2">
				This is a text with class 'bg-outline-gamma' set.
			</div>
			<div class="bg-outline-delta mt-2">
				This is a text with class 'bg-outline-delta' set.
			</div>
			<h4 class="mt-2">backgrounds with padding and border</h4>
			<div class="bg bg-alpha-light border border-alpha-medium mt-2" style="border: 1px solid;">
				This is a text with class 'bg bg-alpha-light border border-alpha-medium' set.
			</div>
			<div class="bg bg-beta-light border border-beta-medium mt-2" style="border: 1px solid;">
				This is a text with class 'bg bg-beta-light border border-beta-medium' set.
			</div>
			<div class="bg bg-gamma-light border border-gamma-medium mt-2" style="border: 1px solid;">
				This is a text with class 'bg bg-gamma-light border border-gamma-medium' set.
			</div>
			<div class="bg bg-delta-light border border-delta-medium mt-2" style="border: 1px solid;">
				This is a text with class 'bg bg-delta-light border border-delta-medium' set.
			</div>
		</div>

		<h2 class="mt-4">Text colours</h2>
		<div class="p-1">This is a text without specific class set (usage of @text-color).</div>
		<div class="text-alpha p-1">This is a text with class 'text-alpha' set.</div>
		<div class="text-beta p-1">This is a text with class 'text-beta' set.</div>
		<div class="text-gamma p-1">This is a text with class 'text-gamma' set.</div>
		<div class="text-delta p-1">This is a text with class 'text-delta' set.</div>
		<div class="p-1">Example of the <a href="#">link colour '@link-color'</a> on background colour '@back-color' and text colour '@text-color'
		</div>

		<h2 class="mt-4">Border colours</h2>
		<div class="d-flex">
			<span class="border border-secondary border-5" style="display: inline-block;width: 12rem;height: 5rem;margin: .25rem;padding:.25rem;background-color: #f5f5f5;">
				border border-secondary
			</span>
			<span class="border border-alpha border-5" style="display: inline-block;width: 12rem;height: 5rem;margin: .25rem;padding:.25rem;background-color: #f5f5f5;">border
				border-alpha
			</span>
			<span class="border border-beta border-5" style="display: inline-block;width: 12rem;height: 5rem;margin: .25rem;padding:.25rem;background-color: #f5f5f5;">border
				border-beta
			</span>
			<span class="border border-gamma border-5" style="display: inline-block;width: 12rem;height: 5rem;margin: .25rem;padding:.25rem;background-color: #f5f5f5;">border
				border-gamma
			</span>
			<span class="border border-delta border-5" style="display: inline-block;width: 12rem;height: 5rem;margin: .25rem;padding:.25rem;background-color: #f5f5f5;">border
				border-delta
			</span>
		</div>
		<h2 class="mt-4">Alerts</h2>
		<div class="alert alert-dismissible alert-alpha fade show" id="sobipro-message" role="alert">
			<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"/>
			<h4>Alert header .h4</h4>
			<hr/>
			This is an alert box with class 'alert alert-alpha' set and a <a href="#" class="alert-link">link with .alert-link</a>.
		</div>
		<div class="alert alert-dismissible alert-beta fade show" id="sobipro-message" role="alert">
			<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"/>
			<h4>Alert header .h4</h4>
			<hr/>
			This is an alert box with class 'alert alert-beta' set and a <a href="#" class="alert-link">link with .alert-link</a>.
		</div>
		<div class="alert alert-dismissible alert-gamma fade show" id="sobipro-message" role="alert">
			<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"/>
			<h4>Alert header .h4</h4>
			<hr/>
			This is an alert box with class 'alert alert-gamma' set and a <a href="#" class="alert-link">link with .alert-link</a>.
		</div>
		<div class="alert alert-dismissible alert-delta fade show" id="sobipro-message" role="alert">
			<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"/>
			<h4>Alert header .h4</h4>
			<hr/>
			This is an alert box with class 'alert alert-delta' set and a <a href="#" class="alert-link">link with .alert-link</a>.
		</div>
		<div class="alert alert-dismissible alert-outline-alpha fade show" id="sobipro-message" role="alert">
			<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"/>
			<h4>Alert header .h4</h4>
			<hr/>
			This is an alert box with class 'alert alert-outline-alpha' set and a <a href="#" class="alert-link">link with .alert-link</a>.
		</div>
		<div class="alert alert-dismissible alert-outline-beta fade show" id="sobipro-message" role="alert">
			<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"/>
			<h4>Alert header .h4</h4>
			<hr/>
			This is an alert box with class 'alert alert-outline-beta' set and a <a href="#" class="alert-link">link with .alert-link</a>.
		</div>
		<div class="alert alert-dismissible alert-outline-gamma fade show" id="sobipro-message" role="alert">
			<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"/>
			<h4>Alert header .h4</h4>
			<hr/>
			This is an alert box with class 'alert alert-outline-gamma' set and a <a href="#" class="alert-link">link with .alert-link</a>.
		</div>
		<div class="alert alert-dismissible alert-outline-delta fade show" id="sobipro-message" role="alert">
			<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"/>
			<h4>Alert header .h4</h4>
			<hr/>
			This is an alert box with class 'alert alert-outline-delta' set and a <a href="#" class="alert-link">link with .alert-link</a>.
		</div>
		<div class="clearfix"/>

		<h2 class="mt-4">Buttons</h2>
		<div class="d-flex">
			<button class="btn btn-secondary me-1" type="button">Cancel (btn-secondary)</button>
			<button class="btn btn-alpha me-1" type="button">btn-alpha</button>
			<button class="btn btn-beta me-1 " type="button">btn-beta</button>
			<button class="btn btn-gamma me-1" type="button">btn-gamma</button>
			<button class="btn btn-delta me-1" type="button">btn-delta</button>
		</div>
		<div class="d-flex mt-3">
			<button class="btn btn-outline-secondary me-1" type="button">Cancel (btn-outline-secondary)</button>
			<button class="btn btn-outline-alpha me-1" type="button">btn-outline-alpha</button>
			<button class="btn btn-outline-beta me-1 " type="button">btn-outline-beta</button>
			<button class="btn btn-outline-gamma me-1" type="button">btn-outline-gamma</button>
			<button class="btn btn-outline-delta me-1" type="button">btn-outline-delta</button>
		</div>
		<div class="clearfix"/>

		<h2 class="mt-4">Badges and Pill badges</h2>
		<div class="d-flex">
			<span class="badge bg-secondary me-1">bg-secondary</span>
			<span class="badge bg-alpha me-1">bg-alpha</span>
			<span class="badge bg-beta me-1 ">bg-beta</span>
			<span class="badge bg-gamma me-1">bg-gamma</span>
			<span class="badge bg-delta me-1">bg-delta</span>
			<span class="badge bg-alpha rounded-pill me-1">bg-alpha rounded-pill</span>
			<span class="badge bg-beta rounded-pill me-1 ">bg-beta rounded-pill</span>
			<span class="badge bg-gamma rounded-pill me-1">bg-gamma rounded-pill</span>
			<span class="badge bg-delta rounded-pill me-1">bg-delta rounded-pill</span>
		</div>

		<h2 class="mt-4">Link Badges</h2>
		<div class="d-flex">
			<a href="#" class="badge bg-secondary me-1">bg-secondary</a>
			<a href="#" class="badge bg-alpha me-1">bg-alpha</a>
			<a href="#" class="badge bg-beta me-1 ">bg-beta</a>
			<a href="#" class="badge bg-gamma me-1">bg-gamma</a>
			<a href="#" class="badge bg-delta me-1">bg-delta</a>
		</div>

		<h2 class="mt-4">Outline Badges w/o and w/ link</h2>
		<div class="d-flex">
			<span class="badge bg-outline-alpha me-1">bg-alpha-light</span>
			<span class="badge bg-outline-beta me-1 ">bg-beta-light</span>
			<span class="badge bg-outline-gamma me-1">bg-gamma-light</span>
			<span class="badge bg-outline-delta me-1">bg-delta-light</span>
			<a class="badge bg-outline-alpha me-1">bg-alpha-light</a>
			<a class="badge bg-outline-beta me-1 ">bg-beta-light</a>
			<a class="badge bg-outline-gamma me-1">bg-gamma-light</a>
			<a class="badge bg-outline-delta me-1">bg-delta-light</a>
		</div>

		<h2 class="mt-4">Pagination</h2>
		<p>pagination pagination-sm, pagination, pagination pagination-lg</p>
		<nav aria-label="Pagination Colours">
			<ul class="pagination pagination-sm">
				<li class="page-item disabled">
					<a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
				</li>
				<li class="page-item">
					<a class="page-link" href="#">1</a>
				</li>
				<li class="page-item active" aria-current="page">
					<a class="page-link" href="#">2</a>
				</li>
				<li class="page-item">
					<a class="page-link" href="#">3</a>
				</li>
				<li class="page-item">
					<a class="page-link" href="#">Next</a>
				</li>
			</ul>
			<ul class="pagination mt-2">
				<li class="page-item disabled">
					<a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
				</li>
				<li class="page-item">
					<a class="page-link" href="#">1</a>
				</li>
				<li class="page-item active" aria-current="page">
					<a class="page-link" href="#">2</a>
				</li>
				<li class="page-item">
					<a class="page-link" href="#">3</a>
				</li>
				<li class="page-item">
					<a class="page-link" href="#">Next</a>
				</li>
			</ul>
			<ul class="pagination pagination-lg mt-2">
				<li class="page-item disabled">
					<a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
				</li>
				<li class="page-item">
					<a class="page-link" href="#">1</a>
				</li>
				<li class="page-item active" aria-current="page">
					<a class="page-link" href="#">2</a>
				</li>
				<li class="page-item">
					<a class="page-link" href="#">3</a>
				</li>
				<li class="page-item">
					<a class="page-link" href="#">Next</a>
				</li>
			</ul>
			<ul class="pagination pagination-alpha mt-2">
				<li class="page-item disabled">
					<a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
				</li>
				<li class="page-item">
					<a class="page-link" href="#">1</a>
				</li>
				<li class="page-item active" aria-current="page">
					<a class="page-link" href="#">2</a>
				</li>
				<li class="page-item">
					<a class="page-link" href="#">3</a>
				</li>
				<li class="page-item">
					<a class="page-link" href="#">Next</a>
				</li>
			</ul>
			<ul class="pagination pagination-beta mt-2">
				<li class="page-item">
					<a class="page-link" href="#">Previous</a>
				</li>
				<li class="page-item">
					<a class="page-link" href="#">1</a>
				</li>
				<li class="page-item">
					<a class="page-link" href="#">2</a>
				</li>
				<li class="page-item active" aria-current="page">
					<a class="page-link" href="#">3</a>
				</li>
				<li class="page-item disabled">
					<a class="page-link" href="#" tabindex="-1" aria-disabled="true">Next</a>
				</li>
			</ul>
			<ul class="pagination pagination-gamma mt-2">
				<li class="page-item disabled">
					<a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
				</li>
				<li class="page-item">
					<a class="page-link" href="#">1</a>
				</li>
				<li class="page-item active" aria-current="page">
					<a class="page-link" href="#">2</a>
				</li>
				<li class="page-item">
					<a class="page-link" href="#">3</a>
				</li>
				<li class="page-item">
					<a class="page-link" href="#">Next</a>
				</li>
			</ul>
			<ul class="pagination pagination-delta mt-2">
				<li class="page-item disabled">
					<a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
				</li>
				<li class="page-item">
					<a class="page-link" href="#">1</a>
				</li>
				<li class="page-item active" aria-current="page">
					<a class="page-link" href="#">2</a>
				</li>
				<li class="page-item">
					<a class="page-link" href="#">3</a>
				</li>
				<li class="page-item">
					<a class="page-link" href="#">Next</a>
				</li>
			</ul>
			<ul class="pagination d-flex pagination-round pagination-delta mt-2">
				<li class="page-item disabled">
					<span class="page-link" tabindex="-1" aria-disabled="true" aria-label="Previous">
						<span class="fas fa-angle-left" aria-hidden="true"/>
					</span>
				</li>
				<li class="page-item active" aria-current="page">
					<span class="page-link">1</span>
				</li>
				<li class="page-item">
					<a href="#" class="page-link">2</a>
				</li>
				<li class="page-item">
					<a href="#" class="page-link">3</a>
				</li>
				<li class="page-item">
					<a href="#" class="page-link" aria-label="Next">
						<span class="fas fa-angle-right" aria-hidden="true"/>
					</a>
				</li>
			</ul>
		</nav>

		<div class="sp-detail-entry mt-4" data-role="content">
			<h1 class="sp-namefield">Entry Name in details view</h1>

			<h2 class="mt-4">No-image element (via css and as image)</h2>
			<div class="sp-noimage-container css sp-entry-row spClassImage" style="float:left">
				<div class="sp-noimage css">
					<xsl:value-of select="php:function( 'SobiPro::Icon', 'ban', 'fa5')" disable-output-escaping="yes"/>
				</div>
			</div>
			<div class="sp-noimage-container sp-entry-row spClassImage">
				<div class="sp-noimage picture">
					<img src="{//template_path}images/{//config/noimage-img/@value}" alt="No image available" style="float:left"/>
				</div>
			</div>
			<div class="clearfix"/>

			<h2 class="mt-4">Tables</h2>
			<h4>Simple table without effects</h4>
			<div class="table-responsive">
				<table class="table">
					<thead>
						<tr>
							<th scope="col" colspan="3" class="text-center">header</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td class="text-center">cell</td>
							<td class="text-center">cell</td>
							<td class="text-center">cell</td>
						</tr>
						<tr>
							<td class="text-center">cell</td>
							<td class="text-center">cell</td>
							<td class="text-center">cell</td>
						</tr>
					</tbody>
				</table>
			</div>
			<h4>Bordered and striped with hover effect</h4>
			<div class="table-responsive">
				<table class="table table-bordered table-hover table-striped">
					<caption>(table-bordered table-hover table-striped)</caption>
					<thead class="thead-gamma">
						<tr>
							<th scope="col" colspan="3" class="text-center">thead-gamma</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td class="text-center">cell</td>
							<td class="text-center">cell</td>
							<td class="text-center">cell</td>
						</tr>
						<tr>
							<td class="text-center">cell</td>
							<td class="text-center">cell</td>
							<td class="text-center">cell</td>
						</tr>
						<tr>
							<td class="text-center">cell</td>
							<td class="text-center">cell</td>
							<td class="text-center">cell</td>
						</tr>
					</tbody>
					<tfoot>
						<tr>
							<th scope="col" colspan="3" class="text-center">foot</th>
						</tr>
					</tfoot>
				</table>
			</div>
			<h4>Bordered with hover effect and contextual classes to color table rows</h4>
			<div class="table-responsive">
				<table class="table table-bordered table-sm table-hover">
					<caption>(table-bordered table-sm table-hover)</caption>
					<thead class="thead-delta">
						<tr>
							<th scope="col" colspan="3" class="text-center">thead-delta</th>
						</tr>
					</thead>
					<tbody>
						<tr class="table-secondary">
							<td class="text-center">row table-secondary</td>
							<td class="text-center">cell</td>
							<td class="text-center">cell</td>
						</tr>
						<tr class="table-alpha">
							<td class="text-center">row table-alpha</td>
							<td class="text-center">cell</td>
							<td class="text-center">cell</td>
						</tr>
						<tr class="table-beta">
							<td class="text-center">row table-beta</td>
							<td class="text-center">cell</td>
							<td class="text-center">cell</td>
						</tr>
						<tr class="table-gamma">
							<td class="text-center">row table-gamma</td>
							<td class="text-center">cell</td>
							<td class="text-center">cell</td>
						</tr>
						<tr class="table-delta">
							<td class="text-center">row table-delta</td>
							<td class="text-center">cell</td>
							<td class="text-center">cell</td>
						</tr>
					</tbody>
				</table>
			</div>
			<h4>Bordered with hover effect and contextual classes to color table cells</h4>
			<div class="table-responsive">
				<table class="table table-bordered table-hover">
					<caption>(table-bordered table-hover)</caption>
					<thead class="thead-dark">
						<tr>
							<th scope="col" colspan="3" class="text-center">header</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td class="text-center table-secondary">cell table-secondary</td>
							<td class="text-center table-alpha">cell table-alpha</td>
							<td class="text-center table-beta">
								<a class="table-link" href="#">link</a>
								table-beta
							</td>
						</tr>
						<tr>
							<td class="text-center table-beta">cell table-beta</td>
							<td class="text-center table-gamma">cell table-gamma</td>
							<td class="text-center table-delta">cell table-delta</td>
						</tr>
					</tbody>
				</table>
			</div>
			<h4>Bordered with hover effect and vertical striped</h4>
			<div class="table-responsive">
				<table class="table table-bordered table-hover table-delta table-vstriped">
					<caption>(table-bordered table-hover table-vstriped table-delta)</caption>
					<thead>
						<tr>
							<th scope="col" colspan="6" class="text-center">header</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td class="text-center">cell</td>
							<td class="text-center">cell</td>
							<td class="text-center">cell</td>
							<td class="text-center">cell</td>
							<td class="text-center">cell</td>
							<td class="text-center">cell</td>
						</tr>
						<tr>
							<td class="text-center">cell</td>
							<td class="text-center">cell</td>
							<td class="text-center">
								<a class="table-link" href="#">link</a>
							</td>
							<td class="text-center">cell</td>
							<td class="text-center">cell</td>
							<td class="text-center">cell</td>
						</tr>
						<tr>
							<td class="text-center">cell</td>
							<td class="text-center">cell</td>
							<td class="text-center">cell</td>
							<td class="text-center">cell</td>
							<td class="text-center">cell</td>
							<td class="text-center">cell</td>
						</tr>
					</tbody>
				</table>
			</div>
			<h4>Dark table, bordered with hover effect</h4>
			<p>If you are using a Bootstrap 5 based Joomla template, do not use the SobiPro bg-classes for templates.</p>
			<div class="table-responsive">
				<table class="table table-bordered table-hover">
					<caption>(table-bordered table-hover)</caption>
					<thead>
						<tr>
							<th scope="col" colspan="3" class="text-center">header</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td class="text-center">cell</td>
							<td class="text-center">cell</td>
							<td class="text-center">cell</td>
						</tr>
						<tr class="bg-alpha">
							<td class="text-center">row bg-alpha</td>
							<td class="text-center">cell</td>
							<td class="text-center">cell</td>
						</tr>
						<tr class="bg-beta">
							<td class="text-center">row bg-beta</td>
							<td class="text-center">cell</td>
							<td class="text-center">
								<a class="table-link text-light" href="#">link</a>
							</td>
						</tr>
						<tr class="bg-gamma">
							<td class="text-center">row bg-gamma</td>
							<td class="text-center">cell</td>
							<td class="text-center">cell</td>
						</tr>
						<tr class="bg-delta">
							<td class="text-center">row bg-delta</td>
							<td class="text-center">cell</td>
							<td class="text-center">cell</td>
						</tr>
					</tbody>
					<tfoot>
						<tr>
							<th scope="col" colspan="3" class="text-center">foot</th>
						</tr>
					</tfoot>
				</table>
			</div>

			<h2 class="mt-4">Cards</h2>
			<div class="d-flex">
				<div class="card bg-secondary text-white mb-3 me-1" style="max-width: 18rem;">
					<div class="card-header">Header</div>
					<div class="card-body">
						<h5 class="card-title">Secondary card title</h5>
						<p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
					</div>
				</div>
				<div class="card bg-alpha mb-3 me-1" style="max-width: 18rem;">
					<div class="card-header">Header</div>
					<div class="card-body">
						<h5 class="card-title">Alpha card title</h5>
						<p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
					</div>
				</div>
				<div class="card bg-beta mb-3 me-1" style="max-width: 18rem;">
					<div class="card-header">Header</div>
					<div class="card-body">
						<h5 class="card-title">Beta card title</h5>
						<p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
					</div>
				</div>
				<div class="card bg-gamma mb-3 me-1" style="max-width: 18rem;">
					<div class="card-header">Header</div>
					<div class="card-body">
						<h5 class="card-title">Gamma card title</h5>
						<p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
					</div>
				</div>
				<div class="card bg-delta mb-3 me-1" style="max-width: 18rem;">
					<div class="card-header">Header</div>
					<div class="card-body">
						<h5 class="card-title">Delta card title</h5>
						<p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
					</div>
				</div>
			</div>
			<div class="d-flex">
				<div class="card bg-alpha-light mb-3 me-1" style="max-width: 18rem;">
					<div class="card-header">Header</div>
					<div class="card-body">
						<h5 class="card-title">Light Alpha card title</h5>
						<p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
					</div>
				</div>
				<div class="card bg-beta-light mb-3 me-1" style="max-width: 18rem;">
					<div class="card-header">Header</div>
					<div class="card-body">
						<h5 class="card-title">Light Beta card title</h5>
						<p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
					</div>
				</div>
				<div class="card bg-gamma-light mb-3 me-1" style="max-width: 18rem;">
					<div class="card-header">Header</div>
					<div class="card-body">
						<h5 class="card-title">Light Gamma card title</h5>
						<p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
					</div>
				</div>
				<div class="card bg-delta-light mb-3 me-1" style="max-width: 18rem;">
					<div class="card-header">Header</div>
					<div class="card-body">
						<h5 class="card-title">Light Delta card title</h5>
						<p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
					</div>
				</div>
			</div>
			<div class="d-flex">
				<div class="card bg-alpha-medium mb-3 me-1" style="max-width: 18rem;">
					<div class="card-header">Header</div>
					<div class="card-body">
						<h5 class="card-title">Medium Alpha card title</h5>
						<p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
					</div>
				</div>
				<div class="card bg-beta-medium mb-3 me-1" style="max-width: 18rem;">
					<div class="card-header">Header</div>
					<div class="card-body">
						<h5 class="card-title">Medium Beta card title</h5>
						<p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
					</div>
				</div>
				<div class="card bg-gamma-medium mb-3 me-1" style="max-width: 18rem;">
					<div class="card-header">Header</div>
					<div class="card-body">
						<h5 class="card-title">Medium Gamma card title</h5>
						<p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
					</div>
				</div>
				<div class="card bg-delta-medium mb-3 me-1" style="max-width: 18rem;">
					<div class="card-header">Header</div>
					<div class="card-body">
						<h5 class="card-title">Medium Delta card title</h5>
						<p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
					</div>
				</div>
			</div>
			<div class="d-flex">
				<div class="card bg-alpha-dark mb-3 me-1" style="max-width: 18rem;">
					<div class="card-header">Header</div>
					<div class="card-body">
						<h5 class="card-title">Dark Alpha card title</h5>
						<p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
					</div>
				</div>
				<div class="card bg-beta-dark mb-3 me-1" style="max-width: 18rem;">
					<div class="card-header">Header</div>
					<div class="card-body">
						<h5 class="card-title">Dark Beta card title</h5>
						<p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
					</div>
				</div>
				<div class="card bg-gamma-dark mb-3 me-1" style="max-width: 18rem;">
					<div class="card-header">Header</div>
					<div class="card-body">
						<h5 class="card-title">Dark Gamma card title</h5>
						<p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
					</div>
				</div>
				<div class="card bg-delta-dark mb-3 me-1" style="max-width: 18rem;">
					<div class="card-header">Header</div>
					<div class="card-body">
						<h5 class="card-title">Dark Delta card title</h5>
						<p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
					</div>
				</div>
			</div>

			<h2 class="mt-4">Dropdown Buttons</h2>
			<div>
				<div class="btn-group me-2">
					<button type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">+
						Action
					</button>
					<div class="dropdown-menu dropdown-secondary">
						<a class="dropdown-item" href="#">Action</a>
						<a class="dropdown-item" href="#">Another action</a>
						<a class="dropdown-item" href="#">Something else here</a>
						<div class="dropdown-divider"/>
						<a class="dropdown-item" href="#">Separated link</a>
					</div>
				</div>
				<div class="btn-group me-2">
					<button type="button" class="btn btn-alpha dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
						Action
					</button>
					<div class="dropdown-menu dropdown-alpha">
						<a class="dropdown-item" href="#">Action</a>
						<a class="dropdown-item" href="#">Another action</a>
						<a class="dropdown-item" href="#">Something else here</a>
						<div class="dropdown-divider"/>
						<a class="dropdown-item" href="#">Separated link</a>
					</div>
				</div>
				<div class="btn-group me-2">
					<button type="button" class="btn btn-beta dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
						Action
					</button>
					<div class="dropdown-menu dropdown-beta">
						<a class="dropdown-item" href="#">Action</a>
						<a class="dropdown-item" href="#">Another action</a>
						<a class="dropdown-item" href="#">Something else here</a>
						<div class="dropdown-divider"/>
						<a class="dropdown-item" href="#">Separated link</a>
					</div>
				</div>
				<div class="btn-group me-2">
					<button type="button" class="btn btn-gamma dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
						Action
					</button>
					<div class="dropdown-menu dropdown-gamma">
						<a class="dropdown-item" href="#">Action</a>
						<a class="dropdown-item" href="#">Another action</a>
						<a class="dropdown-item" href="#">Something else here</a>
						<div class="dropdown-divider"/>
						<a class="dropdown-item" href="#">Separated link</a>
					</div>
				</div>
				<div class="btn-group">
					<button type="button" class="btn btn-delta dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
						Action
					</button>
					<div class="dropdown-menu dropdown-delta">
						<a class="dropdown-item" href="#">Action</a>
						<a class="dropdown-item" href="#">Another action</a>
						<a class="dropdown-item" href="#">Something else here</a>
						<div class="dropdown-divider"/>
						<a class="dropdown-item" href="#">Separated link</a>
					</div>
				</div>
			</div>
			<div>
				<h4 class="mt-2">Split dropdown buttons</h4>
				<div class="btn-group me-2">
					<button type="button" class="btn btn-secondary">Action</button>
					<button type="button" class="btn btn-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-haspopup="true"
					        aria-expanded="false">
						<span class="sr-only visually-hidden">Toggle Dropdown</span>
					</button>
					<div class="dropdown-menu dropdown-alpha">
						<a class="dropdown-item" href="#">Action</a>
						<a class="dropdown-item" href="#">Another action</a>
						<a class="dropdown-item" href="#">Something else here</a>
						<div class="dropdown-divider"/>
						<a class="dropdown-item" href="#">Separated link</a>
					</div>
				</div>
				<div class="btn-group me-2">
					<button type="button" class="btn btn-alpha">Action</button>
					<button type="button" class="btn btn-alpha dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-haspopup="true"
					        aria-expanded="false">
						<span class="sr-only visually-hidden">Toggle Dropdown</span>
					</button>
					<div class="dropdown-menu dropdown-alpha">
						<a class="dropdown-item" href="#">Action</a>
						<a class="dropdown-item" href="#">Another action</a>
						<a class="dropdown-item" href="#">Something else here</a>
						<div class="dropdown-divider"/>
						<a class="dropdown-item" href="#">Separated link</a>
					</div>
				</div>
				<div class="btn-group me-2">
					<button type="button" class="btn btn-beta">Action</button>
					<button type="button" class="btn btn-beta dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-haspopup="true"
					        aria-expanded="false">
						<span class="sr-only visually-hidden">Toggle Dropdown</span>
					</button>
					<div class="dropdown-menu dropdown-beta">
						<a class="dropdown-item" href="#">Action</a>
						<a class="dropdown-item" href="#">Another action</a>
						<a class="dropdown-item" href="#">Something else here</a>dropdown
						<div class="dropdown-divider"/>
						<a class="dropdown-item" href="#">Separated link</a>
					</div>
				</div>
				<div class="btn-group me-2">
					<button type="button" class="btn btn-gamma">Action</button>
					<button type="button" class="btn btn-gamma dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-haspopup="true"
					        aria-expanded="false">
						<span class="sr-only visually-hidden">Toggle Dropdown</span>
					</button>
					<div class="dropdown-menu dropdown-gamma">
						<a class="dropdown-item" href="#">Action</a>
						<a class="dropdown-item" href="#">Another action</a>
						<a class="dropdown-item" href="#">Something else here</a>
						<div class="dropdown-divider"/>
						<a class="dropdown-item" href="#">Separated link</a>
					</div>
				</div>
				<div class="btn-group">
					<button type="button" class="btn btn-delta">Action</button>
					<button type="button" class="btn btn-delta dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-haspopup="true"
					        aria-expanded="false">
						<span class="sr-only visually-hidden">Toggle Dropdown</span>
					</button>
					<div class="dropdown-menu dropdown-delta">
						<a class="dropdown-item" href="#">Action</a>
						<a class="dropdown-item" href="#">Another action</a>
						<a class="dropdown-item" href="#">Something else here</a>
						<div class="dropdown-divider"/>
						<a class="dropdown-item" href="#">Separated link</a>
					</div>
				</div>
			</div>

			<h2 class="mt-4">List group</h2>
			<ul class="list-group">
				<li class="list-group-item">A simple default list group item</li>
				<li class="list-group-item list-group-item-secondary">A simple secondary list group item</li>
				<li class="list-group-item list-group-item-alpha">A simple alpha list group item</li>
				<li class="list-group-item list-group-item-beta">A simple beta list group item</li>
				<li class="list-group-item list-group-item-gamma">A simple gamma list group item</li>
				<li class="list-group-item list-group-item-delta">A simple delta list group item</li>
			</ul>
			<div class="list-group mt-4">
				<a href="#" class="list-group-item list-group-item-action">A simple default list group item</a>
				<a href="#" class="list-group-item list-group-item-action list-group-item-secondary">A simple secondary list group item</a>
				<a href="#" class="list-group-item list-group-item-action list-group-item-alpha">A simple alpha list group item</a>
				<a href="#" class="list-group-item list-group-item-action list-group-item-beta">A simple beta list group item</a>
				<a href="#" class="list-group-item list-group-item-action list-group-item-gamma">A simple gamma list group item</a>
				<a href="#" class="list-group-item list-group-item-action list-group-item-delta">A simple delta list group item</a>
			</div>


			<h2 class="mt-4">Nav tabs and nav pills</h2>

			<!-- Standard Tabs -->
			<ul class="nav nav-tabs sp-navtabs" id="tab" role="tablist">
				<li class="nav-item" role="tablist">
					<a class="nav-link active" id="standard-tab" href="#standard" aria-controls="standard" data-bs-toggle="tab" role="tab" aria-selected="true">
						<xsl:value-of select="php:function( 'SobiPro::TemplateTxt' , 'Standard Tab' )"/>
					</a>
				</li>
				<li class="nav-item" role="tablist">
					<a class="nav-link" id="alpha-tab" href="#alpha" aria-controls="alpha" data-bs-toggle="tab" role="tab" aria-selected="false">
						<xsl:value-of select="php:function( 'SobiPro::TemplateTxt' , 'Standard Tab (alpha)' )"/>
					</a>
				</li>
				<li class="nav-item" role="tablist">
					<a class="nav-link" id="beta-tab" href="#beta" aria-controls="beta" data-bs-toggle="tab" role="tab" aria-selected="false">
						<xsl:value-of select="php:function( 'SobiPro::TemplateTxt' , 'Standard Tab (beta)' )"/>
					</a>
				</li>
				<li class="nav-item" role="tablist">
					<a class="nav-link" id="gamma-tab" href="#gamma" aria-controls="gamma" data-bs-toggle="tab" role="tab" aria-selected="false">
						<xsl:value-of select="php:function( 'SobiPro::TemplateTxt' , 'Standard Tab (gamma)' )"/>
					</a>
				</li>
				<li class="nav-item" role="tablist">
					<a class="nav-link" id="delta-tab" href="#delta" aria-controls="delta" data-bs-toggle="tab" role="tab" aria-selected="false">
						<xsl:value-of select="php:function( 'SobiPro::TemplateTxt' , 'Standard Tab (delta)' )"/>
					</a>
				</li>
			</ul>
			<div class="tab-content tab-delta" id="tabcontent">
				<div class="tab-pane fade show active" id="standard" role="tabpanel" aria-labelledby="standard-tab">
					<div class="spClassViewText">
						The content has set tab-delta. the other panes have a specific colour set with pane-alpha, pane-beta, pane-gamma and pane-delta.
						<br/>
						Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Morbi commodo, ipsum sed pharetra gravida, orci magna rhoncus neque,
						id pulvinar odio lorem non turpis. Nullam sit amet enim. Suspendisse id velit vitae ligula volutpat condimentum. Aliquam erat
						volutpat. Sed quis velit. Nulla facilisi. Nulla libero. Vivamus pharetra posuere sapien.
					</div>
				</div>
				<div class="tab-pane pane-alpha fade" id="alpha" role="tabpanel" aria-labelledby="alpha-tab">
					<div class="spClassViewText">Pane set with pane-alpha.
						<br/>
						Nam consectetuer. Sed aliquam, nunc eget euismod ullamcorper, lectus nunc ullamcorper orci, fermentum bibendum enim nibh eget
						ipsum. Donec porttitor ligula eu dolor. Maecenas vitae nulla consequat libero cursus venenatis. Nam magna enim, accumsan eu, blandit sed, blandit a, eros.
					</div>
				</div>
				<div class="tab-pane pane-beta fade" id="beta" role="tabpanel" aria-labelledby="beta-tab">
					<div class="spClassViewText">Pane set with pane-beta.<br/>Nam consectetuer. Sed aliquam, nunc eget euismod ullamcorper, lectus nunc ullamcorper orci, fermentum
						bibendum enim nibh eget
						ipsum. Donec porttitor ligula eu dolor. Maecenas vitae nulla consequat libero cursus venenatis. Nam magna enim, accumsan eu, blandit sed, blandit a, eros.
					</div>
				</div>
				<div class="tab-pane pane-gamma fade" id="gamma" role="tabpanel" aria-labelledby="gamma-tab">
					<div class="spClassViewText">Pane set with pane-gamma.<br/>Nam consectetuer. Sed aliquam, nunc eget euismod ullamcorper, lectus nunc ullamcorper orci, fermentum
						bibendum enim nibh eget
						ipsum. Donec porttitor ligula eu dolor. Maecenas vitae nulla consequat libero cursus venenatis. Nam magna enim, accumsan eu, blandit sed, blandit a, eros.
					</div>
				</div>
				<div class="tab-pane pane-delta fade" id="delta" role="tabpanel" aria-labelledby="delta-tab">
					<div class="spClassViewText">Pane set with pane-delta.<br/>Nam consectetuer. Sed aliquam, nunc eget euismod ullamcorper, lectus nunc ullamcorper orci, fermentum
						bibendum enim nibh eget
						ipsum. Donec porttitor ligula eu dolor. Maecenas vitae nulla consequat libero cursus venenatis. Nam magna enim, accumsan eu, blandit sed, blandit a, eros.
					</div>
				</div>
			</div>

			<!-- Coloured Tabs -->
			<ul class="nav nav-tabs sp-navtabs nav-gamma" id="tab" role="tablist">
				<li class="nav-item" role="tablist">
					<a class="nav-link active" id="coloured-tab" href="#coloured" aria-controls="coloured" data-bs-toggle="tab" role="tab" aria-selected="true">
						<xsl:value-of select="php:function( 'SobiPro::TemplateTxt' , 'Coloured Tab' )"/>
					</a>
				</li>
				<li class="nav-item nav-alpha" role="tablist">
					<a class="nav-link" id="alpha-coloured-tab" href="#alpha-coloured" aria-controls="alpha-coloured" data-bs-toggle="tab" role="tab"
					   aria-selected="false">
						<xsl:value-of select="php:function( 'SobiPro::TemplateTxt' , 'Coloured Tab (alpha)' )"/>
					</a>
				</li>
				<li class="nav-item nav-beta" role="tablist">
					<a class="nav-link" id="beta-coloured-tab" href="#beta-coloured" aria-controls="beta-coloured" data-bs-toggle="tab" role="tab"
					   aria-selected="false">
						<xsl:value-of select="php:function( 'SobiPro::TemplateTxt' , 'Coloured Tab (beta)' )"/>
					</a>
				</li>
				<li class="nav-item nav-gamma" role="tablist">
					<a class="nav-link" id="gamma-coloured-tab" href="#gamma-coloured" aria-controls="gamma-coloured" data-bs-toggle="tab" role="tab"
					   aria-selected="false">
						<xsl:value-of select="php:function( 'SobiPro::TemplateTxt' , 'Coloured Tab (gamma)' )"/>
					</a>
				</li>
				<li class="nav-item nav-delta" role="tablist">
					<a class="nav-link" id="delta-coloured-tab" href="#delta-coloured" aria-controls="delta-coloured" data-bs-toggle="tab" role="tab"
					   aria-selected="false">
						<xsl:value-of select="php:function( 'SobiPro::TemplateTxt' , 'Coloured Tab (delta)' )"/>
					</a>
				</li>
			</ul>
			<div class="tab-content sp-tab-content tab-gamma" id="tabcontent">
				<div class="tab-pane fade show active" id="coloured" role="tabpanel" aria-labelledby="coloured-tab">
					<div class="spClassViewText">
						The content has set tab-gamma. The tab ul has set nav-gamma. The other tabs and content have set specific colours.
						<br/>
						Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Morbi commodo, ipsum sed pharetra gravida, orci magna rhoncus neque,
						id pulvinar odio lorem non turpis. Nullam sit amet enim. Suspendisse id velit vitae ligula volutpat condimentum. Aliquam erat
						volutpat. Sed quis velit. Nulla facilisi. Nulla libero. Vivamus pharetra posuere sapien.
					</div>
				</div>
				<div class="tab-pane pane-alpha fade" id="alpha-coloured" role="tabpanel" aria-labelledby="alpha-coloured-tab">
					<div class="spClassViewText">Nam consectetuer. Sed aliquam, nunc eget euismod ullamcorper, lectus nunc ullamcorper orci, fermentum bibendum enim nibh eget
						ipsum. Donec porttitor ligula eu dolor. Maecenas vitae nulla consequat libero cursus venenatis. Nam magna enim, accumsan eu, blandit sed, blandit a, eros.
					</div>
				</div>
				<div class="tab-pane pane-beta fade" id="beta-coloured" role="tabpanel" aria-labelledby="beta-coloured-tab">
					<div class="spClassViewText">Nam consectetuer. Sed aliquam, nunc eget euismod ullamcorper, lectus nunc ullamcorper orci, fermentum bibendum enim nibh eget
						ipsum. Donec porttitor ligula eu dolor. Maecenas vitae nulla consequat libero cursus venenatis. Nam magna enim, accumsan eu, blandit sed, blandit a, eros.
					</div>
				</div>
				<div class="tab-pane pane-gamma fade" id="gamma-coloured" role="tabpanel" aria-labelledby="gamma-coloured-tab">
					<div class="spClassViewText">Nam consectetuer. Sed aliquam, nunc eget euismod ullamcorper, lectus nunc ullamcorper orci, fermentum bibendum enim nibh eget
						ipsum. Donec porttitor ligula eu dolor. Maecenas vitae nulla consequat libero cursus venenatis. Nam magna enim, accumsan eu, blandit sed, blandit a, eros.
					</div>
				</div>
				<div class="tab-pane pane-delta fade" id="delta-coloured" role="tabpanel" aria-labelledby="delta-coloured-tab">
					<div class="spClassViewText">Nam consectetuer. Sed aliquam, nunc eget euismod ullamcorper, lectus nunc ullamcorper orci, fermentum bibendum enim nibh eget
						ipsum. Donec porttitor ligula eu dolor. Maecenas vitae nulla consequat libero cursus venenatis. Nam magna enim, accumsan eu, blandit sed, blandit a, eros.
					</div>
				</div>
			</div>

			<!-- Pills -->
			<ul class="nav nav-pills sp-navtabs nav-gamma" id="tab" role="tablist">
				<li class="nav-item" role="tablist">
					<a class="nav-link active" id="pills-tab" href="#pills" aria-controls="pills" data-bs-toggle="pill" role="tab" aria-selected="true">
						<xsl:value-of select="php:function( 'SobiPro::TemplateTxt' , 'Pills' )"/>
					</a>
				</li>
				<li class="nav-item nav-alpha" role="tablist">
					<a class="nav-link" id="alpha-pills-tab" href="#alpha-pills" aria-controls="alpha-pills" data-bs-toggle="pill" role="tab" aria-selected="false">
						<xsl:value-of select="php:function( 'SobiPro::TemplateTxt' , 'Pills (alpha)' )"/>
					</a>
				</li>
				<li class="nav-item nav-beta" role="tablist">
					<a class="nav-link" id="beta-pills-tab" href="#beta-pills" aria-controls="beta-pills" data-bs-toggle="pill" role="tab" aria-selected="false">
						<xsl:value-of select="php:function( 'SobiPro::TemplateTxt' , 'Pills (beta)' )"/>
					</a>
				</li>
				<li class="nav-item nav-gamma" role="tablist">
					<a class="nav-link" id="gamma-pills-tab" href="#gamma-pills" aria-controls="gamma-pills" data-bs-toggle="pill" role="tab" aria-selected="false">
						<xsl:value-of select="php:function( 'SobiPro::TemplateTxt' , 'Pills (gamma)' )"/>
					</a>
				</li>
				<li class="nav-item nav-delta" role="tablist">
					<a class="nav-link" id="delta-pills-tab" href="#delta-pills" aria-controls="delta-pills" data-bs-toggle="pill" role="tab" aria-selected="false">
						<xsl:value-of select="php:function( 'SobiPro::TemplateTxt' , 'Pills (delta)' )"/>
					</a>
				</li>
			</ul>
			<div class="tab-content tab-gamma" id="tabcontent">
				<div class="tab-pane fade show active" id="pills" role="tabpanel" aria-labelledby="pills-tab">
					<div class="spClassViewText">
						The content has set tab-gamma. The tab ul has set nav-gamma. The other tabs and content have set specific colours.
						<br/>
						Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Morbi commodo, ipsum sed pharetra gravida, orci magna rhoncus neque,
						id pulvinar odio lorem non turpis. Nullam sit amet enim. Suspendisse id velit vitae ligula volutpat condimentum. Aliquam erat
						volutpat. Sed quis velit. Nulla facilisi. Nulla libero. Vivamus pharetra posuere sapien.
					</div>
				</div>
				<div class="tab-pane pane-alpha fade" id="alpha-pills" role="tabpanel" aria-labelledby="alpha-pills-tab">
					<div class="spClassViewText">Nam consectetuer. Sed aliquam, nunc eget euismod ullamcorper, lectus nunc ullamcorper orci, fermentum bibendum enim nibh eget
						ipsum. Donec porttitor ligula eu dolor. Maecenas vitae nulla consequat libero cursus venenatis. Nam magna enim, accumsan eu, blandit sed, blandit a, eros.
					</div>
				</div>
				<div class="tab-pane pane-beta fade" id="beta-pills" role="tabpanel" aria-labelledby="beta-pills-tab">
					<div class="spClassViewText">Nam consectetuer. Sed aliquam, nunc eget euismod ullamcorper, lectus nunc ullamcorper orci, fermentum bibendum enim nibh eget
						ipsum. Donec porttitor ligula eu dolor. Maecenas vitae nulla consequat libero cursus venenatis. Nam magna enim, accumsan eu, blandit sed, blandit a, eros.
					</div>
				</div>
				<div class="tab-pane pane-gamma fade" id="gamma-pills" role="tabpanel" aria-labelledby="gamma-pills-tab">
					<div class="spClassViewText">Nam consectetuer. Sed aliquam, nunc eget euismod ullamcorper, lectus nunc ullamcorper orci, fermentum bibendum enim nibh eget
						ipsum. Donec porttitor ligula eu dolor. Maecenas vitae nulla consequat libero cursus venenatis. Nam magna enim, accumsan eu, blandit sed, blandit a, eros.
					</div>
				</div>
				<div class="tab-pane pane-delta fade" id="delta-pills" role="tabpanel" aria-labelledby="delta-pills-tab">
					<div class="spClassViewText">Nam consectetuer. Sed aliquam, nunc eget euismod ullamcorper, lectus nunc ullamcorper orci, fermentum bibendum enim nibh eget
						ipsum. Donec porttitor ligula eu dolor. Maecenas vitae nulla consequat libero cursus venenatis. Nam magna enim, accumsan eu, blandit sed, blandit a, eros.
					</div>
				</div>
			</div>

			<!-- Staples -->
			<ul class="nav nav-pills sp-navtabs staples nav-beta" id="tab" role="tablist">
				<li class="nav-item" role="tablist">
					<a class="nav-link active" id="staples-tab" href="#staples" aria-controls="staples" data-bs-toggle="pill" role="tab" aria-selected="true">
						<xsl:value-of select="php:function( 'SobiPro::TemplateTxt' , 'Staples' )"/>
					</a>
				</li>
				<li class="nav-item nav-alpha" role="tablist">
					<a class="nav-link" id="alpha-staples-tab" href="#alpha-staples" aria-controls="alpha-staples" data-bs-toggle="pill" role="tab"
					   aria-selected="false">
						<xsl:value-of select="php:function( 'SobiPro::TemplateTxt' , 'Staples (alpha)' )"/>
					</a>
				</li>
				<li class="nav-item nav-beta" role="tablist">
					<a class="nav-link" id="beta-staples-tab" href="#beta-staples" aria-controls="beta-staples" data-bs-toggle="pill" role="tab" aria-selected="false">
						<xsl:value-of select="php:function( 'SobiPro::TemplateTxt' , 'Staples (beta)' )"/>
					</a>
				</li>
				<li class="nav-item nav-gamma" role="tablist">
					<a class="nav-link" id="gamma-staples-tab" href="#gamma-staples" aria-controls="gamma-staples" data-bs-toggle="pill" role="tab"
					   aria-selected="false">
						<xsl:value-of select="php:function( 'SobiPro::TemplateTxt' , 'Staples (gamma)' )"/>
					</a>
				</li>
				<li class="nav-item nav-delta" role="tablist">
					<a class="nav-link" id="delta-staples-tab" href="#delta-staples" aria-controls="delta-staples" data-bs-toggle="pill" role="tab"
					   aria-selected="false">
						<xsl:value-of select="php:function( 'SobiPro::TemplateTxt' , 'Staples (delta)' )"/>
					</a>
				</li>
			</ul>
			<div class="tab-content staples tab-beta" id="tabcontent">
				<div class="tab-pane fade show active" id="staples" role="tabpanel" aria-labelledby="staples-tab">
					<div class="spClassViewText">
						The content has set tab-beta. The tab ul has set nav-beta. The other tabs and content have set specific colours.
						<br/>
						Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Morbi commodo, ipsum sed pharetra gravida, orci magna rhoncus neque,
						id pulvinar odio lorem non turpis. Nullam sit amet enim. Suspendisse id velit vitae ligula volutpat condimentum. Aliquam erat
						volutpat. Sed quis velit. Nulla facilisi. Nulla libero. Vivamus pharetra posuere sapien.
					</div>
				</div>
				<div class="tab-pane pane-alpha fade" id="alpha-staples" role="tabpanel" aria-labelledby="alpha-staples-tab">
					<div class="spClassViewText">Nam consectetuer. Sed aliquam, nunc eget euismod ullamcorper, lectus nunc ullamcorper orci, fermentum bibendum enim nibh eget
						ipsum. Donec porttitor ligula eu dolor. Maecenas vitae nulla consequat libero cursus venenatis. Nam magna enim, accumsan eu, blandit sed, blandit a, eros.
					</div>
				</div>
				<div class="tab-pane pane-beta fade" id="beta-staples" role="tabpanel" aria-labelledby="beta-staples-tab">
					<div class="spClassViewText">Nam consectetuer. Sed aliquam, nunc eget euismod ullamcorper, lectus nunc ullamcorper orci, fermentum bibendum enim nibh eget
						ipsum. Donec porttitor ligula eu dolor. Maecenas vitae nulla consequat libero cursus venenatis. Nam magna enim, accumsan eu, blandit sed, blandit a, eros.
					</div>
				</div>
				<div class="tab-pane pane-gamma fade" id="gamma-staples" role="tabpanel" aria-labelledby="gamma-staples-tab">
					<div class="spClassViewText">Nam consectetuer. Sed aliquam, nunc eget euismod ullamcorper, lectus nunc ullamcorper orci, fermentum bibendum enim nibh eget
						ipsum. Donec porttitor ligula eu dolor. Maecenas vitae nulla consequat libero cursus venenatis. Nam magna enim, accumsan eu, blandit sed, blandit a, eros.
					</div>
				</div>
				<div class="tab-pane pane-delta fade" id="delta-staples" role="tabpanel" aria-labelledby="delta-staples-tab">
					<div class="spClassViewText">Nam consectetuer. Sed aliquam, nunc eget euismod ullamcorper, lectus nunc ullamcorper orci, fermentum bibendum enim nibh eget
						ipsum. Donec porttitor ligula eu dolor. Maecenas vitae nulla consequat libero cursus venenatis. Nam magna enim, accumsan eu, blandit sed, blandit a, eros.
					</div>
				</div>
			</div>
		</div>
	</xsl:template>
</xsl:stylesheet>
