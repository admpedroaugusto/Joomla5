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

 @modified 08 March 2022 by Sigrid Suski
-->

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">
	<xsl:output method="xml" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" encoding="UTF-8"/>
	<xsl:include href="alphaindex.xsl"/>
	<xsl:template match="alphaMenu">
		<div class="sp-alpha-menu d-flex">
			<xsl:if test="count( fields/* ) > 0">
				<div class="sp-alpha-list">
					<xsl:choose>
						<!-- Bootstrap 4, 5 -->
						<xsl:when test="$bs >= 4">
							<div class="dropdown">
								<xsl:choose>
									<xsl:when test="$bs = 5"> <!-- Bootstrap 5 -->
										<button class="btn btn-delta dropdown-toggle" type="button" id="sp-alphamenu-dropdown" data-bs-toggle="dropdown"
										        aria-haspopup="true"
										        aria-expanded="false">
											<xsl:value-of select="php:function( 'SobiPro::Txt', 'ALPHALIST_SELECT' )"/>
											<span class="visually-hidden">
												<xsl:value-of select="php:function( 'SobiPro::Txt', 'ACCESSIBILITY.FE.ALPHA_SELECT' )"/>
											</span>
										</button>
									</xsl:when>
									<xsl:otherwise> <!-- Bootstrap 4 -->
										<button class="btn btn-delta dropdown-toggle" type="button" id="sp-alphamenu-dropdown" data-toggle="dropdown" aria-haspopup="true"
										        aria-expanded="false">
											<xsl:value-of select="php:function( 'SobiPro::Txt', 'ALPHALIST_SELECT' )"/>
											<span class="sr-only">
												<xsl:value-of select="php:function( 'SobiPro::Txt', 'ACCESSIBILITY.FE.ALPHA_SELECT' )"/>
											</span>
										</button>
									</xsl:otherwise>
								</xsl:choose>
								<div class="dropdown-menu dropdown-delta" aria-labelledby="sp-alphamenu-dropdown">
									<xsl:for-each select="fields/*">
										<a href="#" rel="{name()}" class="spctrl-alpha-switch dropdown-item" tabindex="0">
											<xsl:variable name="recent">
												<xsl:value-of select="//fields/@current"/>
											</xsl:variable>
											<xsl:if test="name() = $recent">
												<xsl:attribute name="class">spctrl-alpha-switch dropdown-item active</xsl:attribute>
												<xsl:attribute name="aria-current">true</xsl:attribute>
												<xsl:attribute name="data-spctrl-state">active</xsl:attribute>
											</xsl:if>
											<xsl:value-of select="."/>
										</a>
									</xsl:for-each>
								</div>
							</div>
						</xsl:when>

						<!-- Bootstrap 3 und 2 -->
						<xsl:otherwise>
							<div class="dropdown">
								<button class="btn btn-delta dropdown-toggle" type="button" id="sp-alphamenu-dropdown" data-toggle="dropdown" aria-haspopup="true"
								        aria-expanded="false">
									<xsl:value-of select="php:function( 'SobiPro::Txt', 'ALPHALIST_SELECT' )"/><xsl:text> </xsl:text>
									<span class="sr-only">
										<xsl:value-of select="php:function( 'SobiPro::Txt', 'ACCESSIBILITY.FE.ALPHA_SELECT' )"/>
									</span>
									<span class="caret"/>
								</button>
								<ul class="dropdown-menu" aria-labelledby="sp-alphamenu-dropdown">
									<xsl:for-each select="fields/*">
										<li>
											<a href="#" rel="{name()}" class="spctrl-alpha-switch" tabindex="0">
												<xsl:variable name="recent">
													<xsl:value-of select="//fields/@current"/>
												</xsl:variable>
												<xsl:if test="name() = $recent">
													<xsl:attribute name="class">spctrl-alpha-switch active</xsl:attribute>
													<xsl:attribute name="aria-current">true</xsl:attribute>
													<xsl:attribute name="data-spctrl-state">active</xsl:attribute>
												</xsl:if>
												<xsl:value-of select="."/>
											</a>
										</li>
									</xsl:for-each>
								</ul>
							</div>
						</xsl:otherwise>
					</xsl:choose>
				</div>
			</xsl:if>
			<div id="spctrl-alpha-index">
				<xsl:apply-templates select="letters"/>
			</div>
		</div>
	</xsl:template>
</xsl:stylesheet>
