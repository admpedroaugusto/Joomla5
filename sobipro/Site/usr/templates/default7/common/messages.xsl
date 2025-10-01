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

 @modified 17 September 2024 by Sigrid Suski
-->

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">
	<xsl:output method="xml" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" encoding="UTF-8"/>
	<xsl:template match="messages">
		<div class="sp-message">
			<xsl:for-each select="./*">
				<div class="alert alert-delta alert-dismissible alert-{name()}" role="alert">
					<xsl:choose>
						<xsl:when test="$bs = 5"> <!-- Bootstrap 5 -->
							<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{php:function( 'SobiPro::Txt', 'ACCESSIBILITY.DISMISS_MESSAGE' )}"/>
						</xsl:when>
						<xsl:otherwise> <!-- Bootstrap 4 -->
							<button type="button" class="close" data-dismiss="alert" aria-label="{php:function( 'SobiPro::Txt', 'ACCESSIBILITY.DISMISS_MESSAGE' )}">
								<span aria-hidden="true">×</span>
							</button>
						</xsl:otherwise>
					</xsl:choose>
					<xsl:for-each select="./*">
						<xsl:value-of select="."/>
						<div class="clearfix"/>
					</xsl:for-each>
				</div>
			</xsl:for-each>
			<div class="alert alert-delta alert-dismissible hide" id="sobipro-message" role="alert">
				<xsl:choose>
					<xsl:when test="$bs = 5"> <!-- Bootstrap 5 -->
						<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{php:function( 'SobiPro::Txt', 'ACCESSIBILITY.DISMISS_MESSAGE' )}"/>
					</xsl:when>
					<xsl:otherwise> <!-- Bootstrap 4 -->
						<button type="button" class="close" data-dismiss="alert" aria-label="{php:function( 'SobiPro::Txt', 'ACCESSIBILITY.DISMISS_MESSAGE' )}">
							<span aria-hidden="true">×</span>
						</button>
					</xsl:otherwise>
				</xsl:choose>
			</div>
		</div>
	</xsl:template>
</xsl:stylesheet>
