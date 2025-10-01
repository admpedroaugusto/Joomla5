<?xml version="1.0" encoding="UTF-8"?><!--
 @package Default Template V7 for SobiPro multi-directory component with content construction support

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

 @modified 19 September 2024 by Sigrid Suski
-->

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">
	<xsl:output method="xml" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" encoding="UTF-8"/>

	<xsl:include href="../common/globals.xsl"/> <!-- do not comment or remove -->
	<xsl:include href="../common/topmenu.xsl"/>
	<xsl:include href="../common/manage.xsl"/>
	<xsl:include href="../common/alphamenu.xsl"/>
	<xsl:include href="../common/messages.xsl"/>
	<xsl:include href="../common/showfields.xsl"/>

	<!-- Uncomment only if Profile Field is installed -->
	<!--<xsl:include href="../common/profile.xsl" />-->

	<!-- Uncomment only if Review & Ratings App is installed -->
	<!--<xsl:include href="../common/review.xsl" />-->

	<!-- Uncomment only if Collection App is installed -->
	<!--<xsl:include href="../common/collection.xsl" />-->

	<xsl:template match="/entry_details">

		<!-- For proper work a container is needed, we assume that the component area is placed into a container by the template.
		If not, you need to add a container around the SobiPro output here -->
		<div class="sp-details">
			<xsl:call-template name="topMenu">
				<xsl:with-param name="searchbox">true</xsl:with-param>
				<xsl:with-param name="title"/>
			</xsl:call-template>
			<xsl:apply-templates select="alphaMenu"/>
			<xsl:apply-templates select="messages"/>

			<!-- Add itemprop="itemReviewed" itemscope="itemscope" itemtype="https://schema.org/<yourtype> to the div below if using Reviews and Ratings application "-->
			<div class="sp-detail-entry">
				<div class="d-flex align-items-start mt-3">
					<xsl:call-template name="manage">
						<xsl:with-param name="entry" select="entry"/>
					</xsl:call-template>

					<xsl:if test="( //reviews/settings/rating_enabled = 1 ) and document('')/*/xsl:include[@href='../common/review.xsl'] ">
						<xsl:call-template name="ratingStars"/>
					</xsl:if>

					<!-- works only with Voting application -->
					<xsl:if test="count(entry/voting/*) > 0">
						<xsl:copy-of select="entry/voting/*"/>
					</xsl:if>

					<h1 class="sp-namefield">
						<xsl:call-template name="development">
							<xsl:with-param name="fieldname" select="entry/name"/>
						</xsl:call-template>
						<xsl:value-of select="entry/name"/>
						<xsl:call-template name="status">
							<xsl:with-param name="entry" select="entry"/>
						</xsl:call-template>
					</h1>
				</div>
				<!-- Example for showing the description of the primary category the entry is located in -->
				<!--				<xsl:if test="count(entry/categories)>0">-->
				<!--					<xsl:for-each select="entry/categories/category">-->
				<!--						<xsl:if test="@primary = 'true' and string-length(description) > 0">-->
				<!--							<div class="sp-category-description" data-role="content">-->
				<!--								<xsl:value-of select="description" disable-output-escaping="yes"/>-->
				<!--							</div>-->
				<!--						</xsl:if>-->
				<!--					</xsl:for-each>-->
				<!--				</xsl:if>-->

				<!-- Uncomment only if Collection App is installed -->
				<!--<xsl:call-template name="collection"><xsl:with-param name="entry" select="entry"/></xsl:call-template>-->

				<!-- Loop to show all enabled fields from fields manager -->
				<xsl:variable name="layout">
					<xsl:if test="//config/dvtabular/@value = 1">sp-entry-table</xsl:if>
				</xsl:variable>
				<div class="{$layout} dv" data-role="content">
					<xsl:for-each select="entry/fields/*">
						<xsl:call-template name="showfield">
							<xsl:with-param name="fieldname" select="."/>
							<xsl:with-param name="view" select="'dv'"/>
						</xsl:call-template>
					</xsl:for-each>
				</div>

				<xsl:if test="document('')/*/xsl:include[@href='../common/review.xsl'] ">
					<xsl:call-template name="ratingSummary"/>
				</xsl:if>

				<xsl:if test="count(entry/categories)>0">
					<div class="sp-entry-categories mt-3">
						<xsl:value-of select="php:function( 'SobiPro::Txt' , 'ENTRY_LOCATED_IN' )"/><xsl:text> </xsl:text>
						<xsl:for-each select="entry/categories/category">
							<a href="{@url}">
								<xsl:choose>
									<xsl:when test="string-length(name) > 0">
										<xsl:value-of select="name"/>
									</xsl:when>
									<xsl:otherwise>
										<xsl:value-of select="."/>
									</xsl:otherwise>
								</xsl:choose>
							</a>
							<xsl:if test="position() != last()">
								<xsl:text> | </xsl:text>
							</xsl:if>
						</xsl:for-each>
					</div>
				</xsl:if>
			</div>
			<div class="clearfix"/>

			<xsl:if test="document('')/*/xsl:include[@href='../common/review.xsl'] ">
				<xsl:choose>
					<xsl:when test="count(/entry_details/review_form/*) or (/entry_details/reviews/summary_review/overall > 0)">
						<xsl:call-template name="reviewForm"/>
						<xsl:call-template name="reviews"/>
					</xsl:when>
					<xsl:otherwise>
						<div class="sp-review-first-msg">
							<xsl:value-of select="php:function( 'SobiPro::Icon', 'exclamation-circle-large', $font )" disable-output-escaping="yes"/>
							<xsl:text> </xsl:text>
							<xsl:value-of select="php:function( 'SobiPro::Txt', 'ENTRY_NO_REVIEWS_NO_ADD', string(entry/name) )"/>
						</div>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:if>

			<!-- Uncomment only if Profile Field is installed -->
			<!--<xsl:call-template name="UserContributions" />-->

			<xsl:call-template name="bottomHook"/>
		</div>
	</xsl:template>
</xsl:stylesheet>
