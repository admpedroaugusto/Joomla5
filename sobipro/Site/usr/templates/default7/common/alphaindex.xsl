<?xml version="1.0" encoding="UTF-8"?><!--
 @package: Default Template V7 for SobiPro multi-directory component with content construction support

 @author
 Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 Email: sobi[at]sigsiu.net
 Url: https://www.Sigsiu.NET

 @copyright Copyright (C) 2006 - 2021 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 @license GNU/GPL Version 3
 This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 3
 as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 See https://www.gnu.org/licenses/gpl.html and https://www.sigsiu.net/licenses.

 This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

 @modified 24 August 2021 by Sigrid Suski
-->

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">
	<xsl:output method="xml" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" encoding="UTF-8"/>
	<xsl:template match="letters|/menu">
		<xsl:variable name="letter">
			<xsl:value-of select="php:function( 'SobiPro::Request', 'letter' )"/>
		</xsl:variable>
		<xsl:variable name="arialabel">
			<xsl:value-of select="php:function( 'SobiPro::Txt', 'ACCESSIBILITY.FE.ALPHA' )"/>
		</xsl:variable>
		<xsl:choose>
			<xsl:when test="//config/bs/@value = 2">
				<div class="pagination" aria-label="{$arialabel}">
					<ul class="sp-alpha pagination-delta flex-wrap">
						<xsl:for-each select="alphaMenu/letters/letter | letter">
							<li class="page-item">
								<xsl:choose>
									<xsl:when test="not( @url )">
										<xsl:attribute name="class">page-item disabled</xsl:attribute>
										<div class="sr-only visually-hidden">
											<xsl:value-of select="php:function( 'SobiPro::Txt', 'ACCESSIBILITY.FE.ALPHA_DISABLED' )"/>
										</div>
									</xsl:when>
									<xsl:otherwise>
										<xsl:choose>
											<xsl:when test=". = $letter">
												<xsl:attribute name="class">page-item active</xsl:attribute>
												<div class="sr-only visually-hidden">
													<xsl:value-of select="php:function( 'SobiPro::Txt', 'ACCESSIBILITY.FE.ALPHA_ACTIVE' )"/>
												</div>
											</xsl:when>
											<xsl:otherwise>
												<div class="sr-only visually-hidden">
													<xsl:value-of select="php:function( 'SobiPro::Txt', 'ACCESSIBILITY.FE.ALPHA_ENABLED' )"/>
												</div>
											</xsl:otherwise>
										</xsl:choose>
									</xsl:otherwise>
								</xsl:choose>
								<xsl:choose>
									<xsl:when test="@url">
										<a href="{@url}" tabindex="0" class="page-link">
											<xsl:value-of select="."/>
										</a>
									</xsl:when>
									<xsl:otherwise>
										<span class="page-link">
											<xsl:value-of select="."/>
										</span>
									</xsl:otherwise>
								</xsl:choose>
							</li>
						</xsl:for-each>
					</ul>
				</div>
			</xsl:when>
			<xsl:otherwise>
				<ul class="sp-alpha pagination pagination-delta flex-wrap" aria-label="{$arialabel}">
					<xsl:for-each select="alphaMenu/letters/letter | letter">
						<li class="page-item">
							<xsl:choose>
								<xsl:when test="not( @url )">
									<xsl:attribute name="class">page-item disabled</xsl:attribute>
									<div class="sr-only visually-hidden">
										<xsl:value-of select="php:function( 'SobiPro::Txt', 'ACCESSIBILITY.FE.ALPHA_DISABLED' )"/>
									</div>
								</xsl:when>
								<xsl:otherwise>
									<xsl:choose>
										<xsl:when test=". = $letter">
											<xsl:attribute name="class">page-item active</xsl:attribute>
											<div class="sr-only visually-hidden">
												<xsl:value-of select="php:function( 'SobiPro::Txt', 'ACCESSIBILITY.FE.ALPHA_ACTIVE' )"/>
											</div>
										</xsl:when>
										<xsl:otherwise>
											<div class="sr-only visually-hidden">
												<xsl:value-of select="php:function( 'SobiPro::Txt', 'ACCESSIBILITY.FE.ALPHA_ENABLED' )"/>
											</div>
										</xsl:otherwise>
									</xsl:choose>
								</xsl:otherwise>
							</xsl:choose>
							<xsl:choose>
								<xsl:when test="@url">
									<a href="{@url}" tabindex="0" class="page-link">
										<xsl:value-of select="."/>
									</a>
								</xsl:when>
								<xsl:otherwise>
									<span class="page-link">
										<xsl:value-of select="."/>
									</span>
								</xsl:otherwise>
							</xsl:choose>
						</li>
					</xsl:for-each>
				</ul>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
</xsl:stylesheet>
