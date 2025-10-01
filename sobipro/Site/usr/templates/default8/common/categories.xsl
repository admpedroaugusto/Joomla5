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
	<xsl:include href="category.xsl"/>

	<!-- all (sub-)categories of a section/category -->
	<xsl:template name="categoriesLoop">
		<xsl:variable name="categoriesCount">
			<xsl:value-of select="count( categories/category )"/>
		</xsl:variable>

		<xsl:if test="$categoriesCount > 0">
			<div id="spctrl-category-container-{//config/hidecategories/@value}" class="sp-category-container">
				<xsl:choose>
					<!-- flexible number of entries in a row -->
					<xsl:when test="//config/categories_in_line/@value = 'flex'">
						<div class="row mb-3">
							<xsl:for-each select="categories/category">
								<div class="col-sm sp-flex-card mb-3">
									<xsl:call-template name="category"/>
								</div>
							</xsl:for-each>
						</div>
					</xsl:when>

					<!-- fix number of categories in a row -->
					<xsl:otherwise>
						<xsl:variable name="categoriesInLine">
							<xsl:choose>
								<xsl:when test="//config/categories_in_line/@value = 'flex'">2</xsl:when>
								<xsl:otherwise>
									<xsl:value-of select="//config/categories_in_line/@value"/>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:variable>

						<xsl:variable name="cellClass">
							<xsl:value-of select="floor( 12 div $categoriesInLine )"/>
						</xsl:variable>

						<xsl:for-each select="categories/category">
							<xsl:if test="($categoriesInLine > 1 and (position() = 1 or (position() mod $categoriesInLine) = 1)) or $categoriesInLine = 1">
								<xsl:text disable-output-escaping="yes">&lt;div class="row mb-3 align-items-end" &gt;</xsl:text>
							</xsl:if>
							<div class="col-sm-{$cellClass}">
								<xsl:call-template name="category"/>
							</div>
							<xsl:if test="($categoriesInLine > 1 and ((position() mod $categoriesInLine) = 0 or position() = $categoriesCount)) or $categoriesInLine = 1">
								<xsl:text disable-output-escaping="yes">&lt;/div&gt;</xsl:text>
							</xsl:if>
						</xsl:for-each>
					</xsl:otherwise>
				</xsl:choose>
			</div>
		</xsl:if>
	</xsl:template>
</xsl:stylesheet>
