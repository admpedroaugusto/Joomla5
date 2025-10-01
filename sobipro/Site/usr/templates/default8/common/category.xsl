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

 @modified 23 August 2024 by Sigrid Suski
-->

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">
	<xsl:output method="xml" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" encoding="UTF-8"/>

	<xsl:template name="category">
		<xsl:variable name="subcatsNumber">
			<xsl:value-of select="//./number_of_subcats"/>
		</xsl:variable>
		<xsl:variable name="iconwidth">
			<xsl:number value="//config/caticon_width/@value"/>
		</xsl:variable>
		<xsl:variable name="namewidth">
			<xsl:number value="12 - $iconwidth"/>
		</xsl:variable>
		<xsl:variable name="row">
			<xsl:choose>
				<xsl:when test="//config/catnamebelow/@value = 1">flex-column</xsl:when>
				<xsl:otherwise></xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<div class="d-flex {$row} align-items-center">
			<div class="col-{$iconwidth} sp-category-icon">
				<xsl:choose>
					<!-- the category icon is an icon -->
					<xsl:when test="string-length( icon/@element ) > 0">
						<a href="{url}" aria-label="{php:function( 'SobiPro::TemplateTxt', 'ACCESSIBILITY.FE.SWITCH_CATEGORY',string(name) )}">
							<xsl:element name="{icon/@element}">
								<xsl:attribute name="class">
									<xsl:value-of select="icon/@class"/>
								</xsl:attribute>
								<xsl:value-of select="icon/@content"/>
							</xsl:element>
						</a>
					</xsl:when>
					<!-- the category icon is an image -->
					<xsl:otherwise>
						<xsl:if test="string-length( icon ) > 0">
							<xsl:choose>
								<!-- if only the icon image should be shown, add a popover with the category name -->
								<xsl:when test="//config/nocatname/@value = 1">
									<a class="sp-category-imageurl" href="{url}" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-content="{name}" title=""
									   data-bs-container="#SobiPro"
									   data-bs-placement="top" data-sp-toggle="popover">
										<img alt="{name}" src="{icon}"/>
									</a>
								</xsl:when>
								<xsl:otherwise>
									<a href="{url}" class="sp-category-imageurl">
										<img alt="{name}" src="{icon}"/>
									</a>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:if>
					</xsl:otherwise>
				</xsl:choose>
			</div>
			<!--zum Zentrieren align-items-center hinzufügen -->
			<div class="col-{$namewidth} sp-category-name-container d-flex flex-column">
				<div class="sp-category-name">
					<xsl:if test="//config/nocatname/@value = 0">
						<a href="{url}" class="sp-category-title">
							<xsl:value-of select="name"/>
						</a>
					</xsl:if>
					<xsl:if test="//config/countentries/@value = 1">
						<span class="sp-entry-count">
							<xsl:text> (</xsl:text>
							<xsl:value-of select="php:function( 'SobiPro::Count', string( @id ), 'entry' )"/>
							<xsl:text>)</xsl:text>
						</span>
					</xsl:if>
					<xsl:call-template name="cat-status">
						<xsl:with-param name="state" select="./state"/>
					</xsl:call-template>
				</div>

				<!-- remove text-truncate to get an intro with multiple lines -->
				<div class="sp-category-intro w-100 text-truncate">
					<xsl:value-of select="introtext" disable-output-escaping="yes"/>
				</div>

				<!-- Output here category fields for subcategories -->

				<div class="sp-sub-categories">
					<xsl:for-each select="subcategories/subcategory">
						<xsl:if test="position() &lt; ( $subcatsNumber + 1 )">
							<a href="{@url}" class="sp-category-title">
								<small>
									<xsl:value-of select="."/>
								</small>
							</a>
							<xsl:call-template name="cat-status">
								<xsl:with-param name="state" select="./@state"/>
							</xsl:call-template>
							<xsl:if test="position() != last() and position() &lt; $subcatsNumber">
								<span role="separator">
									<xsl:text>, </xsl:text>
								</span>
							</xsl:if>
						</xsl:if>
					</xsl:for-each>
				</div>
			</div>
		</div>
	</xsl:template>

	<xsl:template name="cat-status">
		<xsl:param name="state"/>
		<xsl:if test="$state = 'unpublished'">
			<a tabindex="0" type="button" class="sp-entry-status" data-bs-toggle="popover" data-bs-trigger="focus"
			   data-bs-content="{php:function( 'SobiPro::TemplateTxt', 'CATEGORY_STATUS_UNPUBLISHED' )}" title="" data-sp-toggle="popover">
				<xsl:value-of select="php:function( 'SobiPro::Icon', 'remove-circle', $font )" disable-output-escaping="yes"/>
			</a>
		</xsl:if>
	</xsl:template>

</xsl:stylesheet>
