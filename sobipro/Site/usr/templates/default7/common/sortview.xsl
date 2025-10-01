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

	<xsl:template match="orderingMenu">
		<xsl:if test="count( orderings/* ) and //config/orderlist/@value = 1">
			<xsl:variable name="col">
				<xsl:choose>
					<xsl:when test="$bs = 2">span12</xsl:when>
					<xsl:otherwise>col-md-12</xsl:otherwise>
				</xsl:choose>
			</xsl:variable>
			<xsl:variable name="row">
				<xsl:choose>
					<xsl:when test="$bs = 2">row-fluid</xsl:when>
					<xsl:otherwise>row</xsl:otherwise>
				</xsl:choose>
			</xsl:variable>

			<div class="{$row}">
				<div class="{$col}">
					<div class="sp-ordering-menu">
						<div class="sp-ordering-list">
							<xsl:choose>
								<!-- Bootstrap 4 and 5 -->
								<xsl:when test="$bs >= 4">
									<div class="dropdown">
										<xsl:choose>
											<xsl:when test="$bs = 5"> <!-- Bootstrap 5 -->
												<button class="btn btn-alpha dropdown-toggle" type="button" id="sp-sortview-dropdown" data-bs-toggle="dropdown"
												        aria-haspopup="true" aria-expanded="false">
													<span id="spctrl-waiting-sort" style="display:none">
														<xsl:text> </xsl:text>
														<xsl:value-of select="php:function( 'SobiPro::Icon', 'refresh-spin', $font )" disable-output-escaping="yes"/>
														<xsl:text> </xsl:text>
													</span>
													<xsl:value-of select="php:function( 'SobiPro::Txt', 'ENTRY_ORDERING_SELECT' )"/>
												</button>
											</xsl:when>
											<xsl:otherwise> <!-- Bootstrap 4 -->
												<button class="btn btn-alpha dropdown-toggle" type="button" id="sp-sortview-dropdown" data-toggle="dropdown"
												        aria-haspopup="true" aria-expanded="false">
													<span id="spctrl-waiting-sort" style="display:none">
														<xsl:text> </xsl:text>
														<xsl:value-of select="php:function( 'SobiPro::Icon', 'refresh-spin', $font )" disable-output-escaping="yes"/>
														<xsl:text> </xsl:text>
													</span>
													<xsl:value-of select="php:function( 'SobiPro::Txt', 'ENTRY_ORDERING_SELECT' )"/>
												</button>
											</xsl:otherwise>
										</xsl:choose>
										<div class="dropdown-menu dropdown-menu-end dropdown-menu-right dropdown-alpha" aria-labelledby="sp-sortview-dropdown">
											<xsl:for-each select="orderings/*">
												<a href="#" rel="{name()}" class="spctrl-sort-switch dropdown-item" tabindex="0">
													<xsl:variable name="recent">
														<xsl:value-of select="//orderings/@current"/>
													</xsl:variable>
													<xsl:if test="name() = $recent">
														<xsl:attribute name="class">spctrl-sort-switch dropdown-item active</xsl:attribute>
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
										<button class="btn btn-alpha dropdown-toggle" type="button" id="sp-sortview-dropdown" data-toggle="dropdown" aria-haspopup="true"
										        aria-expanded="false">
											<span id="spctrl-waiting-sort" style="display:none">
												<xsl:text> </xsl:text>
												<xsl:value-of select="php:function( 'SobiPro::Icon', 'refresh-spin', $font )" disable-output-escaping="yes"/>
												<xsl:text> </xsl:text>
											</span>
											<xsl:value-of select="php:function( 'SobiPro::Txt', 'ENTRY_ORDERING_SELECT' )"/>
											<!--											<xsl:text> </xsl:text>-->
											<!--											<span class="caret"/>-->
										</button>
										<ul class="dropdown-menu dropdown-alpha" aria-labelledby="sp-sortview-dropdown">
											<xsl:for-each select="orderings/*">
												<li>
													<a href="#" rel="{name()}" class="spctrl-sort-switch dropdown-item" tabindex="0">
														<xsl:variable name="recent">
															<xsl:value-of select="//orderings/@current"/>
														</xsl:variable>
														<xsl:if test="name() = $recent">
															<xsl:attribute name="class">spctrl-sort-switch dropdown-item active</xsl:attribute>
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
					</div>
				</div>
			</div>
		</xsl:if>
		<div class="clearfix"/>
	</xsl:template>
</xsl:stylesheet>
