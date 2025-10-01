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

	<xsl:template name="searchfield">
		<xsl:param name="fieldname"/>

		<!-- id of the field -->
		<xsl:variable name="fieldId" select="name($fieldname)"/>
		<!-- label width -->
		<xsl:variable name="lw">
			<xsl:choose>
				<xsl:when test="//config/columns-search/@value = 'twocolumns'">
					<xsl:value-of select="//config/label-width-search/@value"/>
				</xsl:when>
				<xsl:otherwise>12</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<!-- content width -->
		<xsl:variable name="cw">
			<xsl:choose>
				<xsl:when test="//config/columns-search/@value = 'twocolumns'">
					<xsl:choose>
						<xsl:when test="string-length( $fieldname/@width ) > 0">
							<xsl:choose>
								<xsl:when test="number($fieldname/@width) + number($lw) > 12">
									<xsl:value-of select="12 - number($lw)"/>
								</xsl:when>
								<xsl:otherwise>
									<xsl:value-of select="$fieldname/@width"/>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="12 - number($lw)"/>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:when>
				<xsl:otherwise>
					<xsl:choose>
						<xsl:when test="string-length( $fieldname/@width ) > 0">
							<xsl:value-of select="$fieldname/@width"/>
						</xsl:when>
						<xsl:otherwise>12</xsl:otherwise>
					</xsl:choose>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<!-- column name and width -->
		<xsl:variable name="colname">
			<xsl:text>col-</xsl:text>
			<xsl:if test="not(//config/grid-search/@value = 'xs')">
				<xsl:value-of select="//config/grid-search/@value"/>
				<xsl:text>-</xsl:text>
			</xsl:if>
		</xsl:variable>

		<!-- overall container -->
		<div class="row {$fieldname/@css-search} mb-3" id="{$fieldId}-container">
			<xsl:call-template name="development">
				<xsl:with-param name="fieldname" select="$fieldname"/>
			</xsl:call-template>

			<!-- label -->
			<label class="{$colname}{$lw} col-form-label" for="{$fieldId}-input-container">
				<xsl:value-of select="$fieldname/label"/>
			</label>

			<!-- content element -->
			<div class="{$colname}{$cw}" id="{$fieldId}-input-container">
				<xsl:choose>
					<xsl:when test="$fieldname/data/@escaped">
						<xsl:value-of select="$fieldname/data" disable-output-escaping="yes"/>
					</xsl:when>
					<xsl:otherwise>
						<xsl:copy-of select="$fieldname/data/*"/>
					</xsl:otherwise>
				</xsl:choose>
			</div>
		</div>
	</xsl:template>

</xsl:stylesheet>
