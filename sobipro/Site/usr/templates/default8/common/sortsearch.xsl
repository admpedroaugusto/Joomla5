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

	<xsl:template match="orderingMenu">
		<xsl:if test="count( orderings/* ) and //config/searchorderlist/@value = 1">
			<div class="sp-ordering-list search">
				<div class="dropdown">
					<button class="btn btn-beta dropdown-toggle" type="button" id="sp-sortsearch-dropdown" data-bs-toggle="dropdown" aria-haspopup="true"
					        aria-expanded="false">
						<span id="spctrl-waiting-sort" style="display:none">
							<xsl:text> </xsl:text>
							<xsl:value-of select="php:function( 'SobiPro::Icon', 'refresh-spin', $font )" disable-output-escaping="yes"/>
						</span>
						<xsl:value-of select="php:function( 'SobiPro::TemplateTxt', 'ENTRY_ORDERING_SELECT' )"/>
					</button>
					<div class="dropdown-menu dropdown-alpha" aria-labelledby="sp-sortsearch-dropdown">
						<xsl:for-each select="orderings/*">
							<a href="#" rel="{name()}" class="spctrl-sort-switch dropdown-item" tabindex="0">
								<xsl:variable name="recent">
									<xsl:value-of select="//orderings/@current"/>
								</xsl:variable>
								<xsl:if test="name() = $recent">
									<xsl:attribute name="class">spctrl-sort-switch dropdown-item active</xsl:attribute>
								</xsl:if>
								<xsl:value-of select="."/>
							</a>
						</xsl:for-each>
					</div>
				</div>
			</div>
		</xsl:if>
		<div class="clearfix"/>
	</xsl:template>
</xsl:stylesheet>