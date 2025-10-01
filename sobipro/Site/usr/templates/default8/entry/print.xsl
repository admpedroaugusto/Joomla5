<?xml version="1.0" encoding="UTF-8"?><!--
 @package Default Template V8 for SobiPro multi-directory component with content construction support

 @author
 Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 Url: https://www.Sigsiu.NET

 @copyright Copyright (C) 2006–2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 @license GNU/GPL Version 3
 This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 3
 as published by the Free Software Foundation, and under the additional terms according to section 7 of GPL v3.
 See https://www.gnu.org/licenses/gpl.html and https://www.sigsiu.net/licenses.

 This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
-->

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">
	<xsl:output method="xml" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" encoding="UTF-8"/>

	<xsl:include href="../common/globals.xsl"/> <!-- do not comment or remove -->
	<xsl:include href="../common/showfields.xsl"/>

	<xsl:template match="/entry_details">
		<div class="sp-details sp-print">
			<!-- Printout preview warning -->
			<div class="sp-print-warning text-danger mb-4 d-print-none">
				<xsl:value-of select="php:function( 'SobiPro::TemplateTxt' , 'PREVIEW' )"/>
				<xsl:text>&#160;</xsl:text>
				<xsl:value-of select="section"/>
			</div>
			<script>window.print();</script>

			<!-- Entry Name -->
			<h1>
				<xsl:value-of select="entry/name"/>
			</h1>

			<!-- use sp-print-break for a new page on printout -->
			<!-- use d-print-none to not print -->

			<!-- Loop to show all enabled fields from fields manager -->
			<xsl:variable name="layout">
				<xsl:if test="//config/dvtabular/@value = 1">sp-entry-table</xsl:if>
			</xsl:variable>
			<div class="{$layout} dv" data-role="content">
				<xsl:for-each select="entry/fields/*">
					<!-- Exclude all fields you want to show separately or not to show at all. -->
					<xsl:if test="name() != 'field_company_logo'">
						<!-- Show all other fields in the loop. -->
						<xsl:call-template name="showfield">
							<xsl:with-param name="fieldname" select="."/>
							<xsl:with-param name="view" select="'dv'"/>
						</xsl:call-template>
					</xsl:if>
				</xsl:for-each>
			</div>

			<!-- Main Image -->
			<xsl:if test="//config/printimage/@value = 1">
				<div class="sp-print-image">
					<xsl:call-template name="showfield">
						<xsl:with-param name="fieldname" select="entry/fields/field_company_logo"/>
						<xsl:with-param name="view" select="'dv'"/>
					</xsl:call-template>
				</div>
			</xsl:if>

			<!-- Map -->
			<!--			<xsl:if test="count(entry/fields/field_map/data/*) or string-length(entry/fields/field_map/data) > 0">-->
			<!--				<div class="mt-4 sp-print-break">-->
			<!--					<xsl:call-template name="showfield">-->
			<!--						<xsl:with-param name="fieldname" select="entry/fields/field_map"/>-->
			<!--						<xsl:with-param name="view" select="'dv'"/>-->
			<!--					</xsl:call-template>-->
			<!--				</div>-->
			<!--			</xsl:if>-->

			<!-- Gallery -->
			<!--			<xsl:if test="//config/printimage/@value = 1">-->
			<!--				<div class="mt-4">-->
			<!--					<label>-->
			<!--						<xsl:value-of select="php:function( 'SobiPro::Txt' , 'IMAGE_GALLERY' )"/>-->
			<!--					</label>-->
			<!--					<div class="d-flex sp-print-gallery">-->
			<!--						<xsl:for-each select="entry/fields/*[starts-with(name(),'field_gallery')]">-->
			<!--							<xsl:if test="count(data/*) > 0">-->
			<!--								<div class="">-->
			<!--									<xsl:call-template name="showfield">-->
			<!--										<xsl:with-param name="fieldname" select="."/>-->
			<!--										<xsl:with-param name="view" select="'dv'"/>-->
			<!--									</xsl:call-template>-->
			<!--								</div>-->
			<!--							</xsl:if>-->
			<!--						</xsl:for-each>-->
			<!--					</div>-->
			<!--				</div>-->
			<!--			</xsl:if>-->
		</div>
	</xsl:template>
</xsl:stylesheet>