<?xml version="1.0" encoding="UTF-8"?><!--
 @package: Default Template V7 for SobiPro multi-directory component with content construction support

 @author
 Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 Email: sobi[at]sigsiu.net
 Url: https://www.Sigsiu.NET

 @copyright Copyright (C) 2006 - 2022 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 @license GNU/GPL Version 3
 This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 3
 as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 See https://www.gnu.org/licenses/gpl.html and https://www.sigsiu.net/licenses.

 This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

 @modified 10 Mai 2022 by Sigrid Suski
-->

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">
	<!-- Uncomment only if Review & Ratings App is installed -->
	<!--<xsl:import href="review.xsl" />-->
	<xsl:output method="xml" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" encoding="UTF-8"/>
	<xsl:include href="showfields.xsl"/>
	<!-- Uncomment only if Collection App is installed -->
	<!--<xsl:include href="collection.xsl" />-->

	<xsl:template name="vcard">
		<xsl:variable name="layout">
			<xsl:if test="//config/vctabular/@value = 1">sp-entry-table</xsl:if>
		</xsl:variable>

		<div class="d-flex align-items-start">
			<xsl:call-template name="manage">
				<xsl:with-param name="entry" select="."/>
			</xsl:call-template>

			<xsl:if test="( //reviews/settings/rating_enabled = 1 ) and document('')/*/xsl:import[@href='review.xsl']">
				<xsl:call-template name="ratingStars"/>
			</xsl:if>

			<!-- works only with Voting application -->
			<xsl:if test="count(voting/*) > 0">
				<xsl:copy-of select="voting/*"/>
			</xsl:if>

			<h2 class="sp-namefield w-100">
				<xsl:call-template name="development">
					<xsl:with-param name="fieldname" select="entry/name"/>
				</xsl:call-template>
				<a href="{url}" class="sp-title">
					<xsl:value-of select="name"/>
					<xsl:call-template name="status">
						<xsl:with-param name="entry" select="."/>
					</xsl:call-template>
				</a>
			</h2>
		</div>
		<!-- Uncomment only if Collection App is installed -->
		<!--<xsl:call-template name="collection"><xsl:with-param name="entry" select="."/></xsl:call-template>-->

		<div class="{$layout}">
			<xsl:for-each select="fields/*">
				<xsl:call-template name="showfield">
					<xsl:with-param name="fieldname" select="."/>
					<xsl:with-param name="view" select="'vcard'"/>
				</xsl:call-template>
			</xsl:for-each>
		</div>
		<div class="clearfix"/>
	</xsl:template>

</xsl:stylesheet>
