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

 @modified 06 June 2022 by Sigrid Suski
-->

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">
	<xsl:output method="xml" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" encoding="UTF-8"/>
	<xsl:include href="manage.xsl"/>
	<xsl:include href="vcard.xsl"/>

	<xsl:template name="entriesLoop">
		<xsl:variable name="entriesCount">
			<xsl:value-of select="count(entries/entry)"/>
		</xsl:variable>

		<xsl:choose>
			<!-- flexible number of entries in a row -->
			<xsl:when test="//config/entries_in_line/@value = 'flex' and $bs > 3">
				<div class="row mb-3">
					<xsl:for-each select="entries/entry">
						<div class="col-sm sp-flex-card mb-3">
							<xsl:call-template name="vcard"/>
						</div>
					</xsl:for-each>
				</div>
			</xsl:when>

			<!-- fix number of entries in a row -->
			<xsl:otherwise>
				<xsl:variable name="entriesInLine">
					<xsl:choose>
						<!-- do not use 'flex' for Bootstrap 3 and below -->
						<xsl:when test="//config/entries_in_line/@value = 'flex' ">2</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="//config/entries_in_line/@value"/>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:variable>

				<xsl:variable name="cellClass">
					<xsl:value-of select="floor( 12 div $entriesInLine )"/>
				</xsl:variable>

				<xsl:variable name="col">
					<xsl:choose>
						<xsl:when test="$bs = 2">span</xsl:when>
						<xsl:otherwise>col-sm-</xsl:otherwise>
					</xsl:choose>
				</xsl:variable>
				<xsl:variable name="row">
					<xsl:choose>
						<xsl:when test="$bs = 2">row-fluid mb-3</xsl:when>
						<xsl:otherwise>row mb-3</xsl:otherwise>
					</xsl:choose>
				</xsl:variable>

				<xsl:for-each select="entries/entry">
					<xsl:if test="($entriesInLine > 1 and (position() = 1 or (position() mod $entriesInLine) = 1 )) or $entriesInLine = 1">
						<xsl:text disable-output-escaping="yes">&lt;div class="</xsl:text>
						<xsl:value-of select="$row"/>
						<xsl:text disable-output-escaping="yes">" &gt;</xsl:text>
					</xsl:if>
					<div class="{$col}{$cellClass}">
						<xsl:call-template name="vcard"/>
					</div>
					<xsl:if test="($entriesInLine > 1 and ((position() mod $entriesInLine) = 0 or position() = $entriesCount)) or $entriesInLine = 1">
						<xsl:text disable-output-escaping="yes">&lt;/div&gt;</xsl:text>
					</xsl:if>
				</xsl:for-each>

			</xsl:otherwise>
		</xsl:choose>

	</xsl:template>
</xsl:stylesheet>
