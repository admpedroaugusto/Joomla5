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
	<xsl:include href="../common/topmenu.xsl"/>
	<xsl:include href="../common/alphamenu.xsl"/>
	<xsl:include href="../common/categories.xsl"/>
	<xsl:include href="../common/entries.xsl"/>
	<xsl:include href="../common/navigation.xsl"/>
	<xsl:include href="../common/messages.xsl"/>
	<xsl:include href="../common/sortview.xsl"/>

	<xsl:template match="/category">
		<xsl:variable name="sectionName">
			<xsl:value-of select="section"/>
		</xsl:variable>

		<xsl:if test="//config/deactivaterss/@value = 0">
			<xsl:variable name="rssUrlSection">{"sid":"<xsl:value-of select="section/@id"/>","sptpl":"feeds.rss","out":"raw"}
			</xsl:variable>
			<xsl:value-of select="php:function( 'SobiPro::AlternateLink', $rssUrlSection, 'application/atom+xml', $sectionName )"/>
			<xsl:variable name="rssUrl">{"sid":"<xsl:value-of select="id"/>","sptpl":"feeds.rss","out":"raw"}
			</xsl:variable>
			<xsl:variable name="categoryName">
				<xsl:value-of select="name"/>
			</xsl:variable>
			<xsl:value-of select="php:function( 'SobiPro::AlternateLink', $rssUrl, 'application/atom+xml', $categoryName )"/>
		</xsl:if>

		<!-- for proper work a container is needed, we assume that the component area is placed into a container by the template.
		If not, you need to add a container around the SobiPro output here -->
		<div class="sp-listing category">
			<xsl:call-template name="topMenu">
				<xsl:with-param name="searchbox">true</xsl:with-param>
				<xsl:with-param name="title"/>
			</xsl:call-template>
			<xsl:apply-templates select="alphaMenu"/>
			<xsl:apply-templates select="messages"/>

			<h2 data-category="{id}">
				<xsl:if test="//config/showicon/@value = 1">
					<xsl:choose>
						<xsl:when test="string-length(icon/@element) > 0">
							<xsl:element name="{icon/@element}">
								<xsl:attribute name="class">
									<xsl:value-of select="icon/@class"/>
								</xsl:attribute>
								<xsl:value-of select="icon/@content"/>
							</xsl:element>
						</xsl:when>
						<xsl:otherwise>
							<xsl:if test="string-length( icon ) > 0">
								<img alt="{name}" src="{icon}"/>
							</xsl:if>
						</xsl:otherwise>
					</xsl:choose>
					<xsl:text> </xsl:text>
				</xsl:if>
				<xsl:value-of select="name"/>
			</h2>

			<!-- add to category button -->
			<xsl:if test="//config/addtocat/@value = 1 and php:function('SobiPro::Can','entry','add',null,'own')">
				<div class="pull-right float-right float-end">
					<a href="{//menu/add/@url}?sid={id}" type="button" class="btn btn-alpha btn-sm mb-1 ms-1">
						<xsl:value-of select="php:function( 'SobiPro::TemplateTxt', 'EN.ENTRY_TO_CAT' )"/>
					</a>
				</div>
			</xsl:if>

			<!-- category desription -->
			<xsl:if test="string-length(description) > 0">
				<div class="sp-category-description" data-role="content">
					<xsl:value-of select="description" disable-output-escaping="yes"/>
				</div>
			</xsl:if>

			<!-- category fields above -->
			<xsl:if test="//config/fieldsposition/@value = 'above'">
				<div class="sp-category-fields" data-role="content">
					<xsl:for-each select="fields/*">
						<xsl:call-template name="showfield">
							<xsl:with-param name="fieldname" select="."/>
							<xsl:with-param name="view" select="'category'"/>
						</xsl:call-template>
					</xsl:for-each>
					<div class="clearfix"/>
				</div>
			</xsl:if>

			<!-- don't hide the categories -->
			<xsl:if test="//config/hidecategories/@value != 'none'">
				<xsl:if test="count (categories/category) and //config/hidecategories/@value = 'hide'">
					<div class="sp-category-button">
						<input id="spctrl-category-show" class="btn btn-alpha" name="spCategoryShow" type="button"/>
					</div>
				</xsl:if>
				<xsl:call-template name="categoriesLoop"/>
			</xsl:if>

			<!-- category fields between -->
			<xsl:if test="//config/fieldsposition/@value = 'between'">
				<div class="sp-category-fields" data-role="content">
					<xsl:for-each select="fields/*">
						<xsl:call-template name="showfield">
							<xsl:with-param name="fieldname" select="."/>
							<xsl:with-param name="view" select="'category'"/>
						</xsl:call-template>
					</xsl:for-each>
					<div class="clearfix"/>
				</div>
			</xsl:if>

			<!-- select ordering dropdown -->
			<xsl:apply-templates select="orderingMenu"/>

			<!-- the entries -->
			<div class="sp-entries-container" id="spctrl-entry-container">
				<xsl:call-template name="entriesLoop"/>
			</div>

			<!-- navigation -->
			<xsl:apply-templates select="navigation"/>

			<xsl:if test="//config/fieldsposition/@value = 'below'">
				<div class="sp-category-fields" data-role="content">
					<xsl:for-each select="fields/*">
						<xsl:call-template name="showfield">
							<xsl:with-param name="fieldname" select="."/>
							<xsl:with-param name="view" select="'category'"/>
						</xsl:call-template>
					</xsl:for-each>
					<div class="clearfix"/>
				</div>
			</xsl:if>

			<xsl:call-template name="bottomHook"/>
		</div>
		<input type="hidden" id="hidetext" value="{php:function( 'SobiPro::TemplateTxt', 'CATEGORIES_HIDE' )}"/>
		<input type="hidden" id="showtext" value="{php:function( 'SobiPro::TemplateTxt', 'CATEGORIES_SHOW' )}"/>
	</xsl:template>
</xsl:stylesheet>