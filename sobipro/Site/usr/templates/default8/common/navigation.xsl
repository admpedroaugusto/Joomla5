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
	<xsl:template match="navigation" name="navigation">
		<xsl:if test="count( sites/* ) &gt; 0">
			<div class="clearfix"/>

			<xsl:variable name="navclass">
				<xsl:if test="//config/pagination/@value = 'pagination1' or //config/pagination/@value = 'pagination2'">text-center</xsl:if>
			</xsl:variable>

			<xsl:variable name="jscontrol">
				<xsl:choose>
					<xsl:when test="//config/pagination/@value = 'ajax'">spctrl-static-navigation hidden</xsl:when>
				</xsl:choose>
			</xsl:variable>

			<xsl:if test="//config/pagination/@value = 'ajax'">
				<div class="sp-navigation">
					<button type="button" class="btn btn-delta hidden spctrl-ajax-navigation" data-pages="{all_sites}">
						<span id="spctrl-waiting-navigation" style="display:none">
							<xsl:value-of select="php:function( 'SobiPro::Icon', 'refresh-spin', $font )" disable-output-escaping="yes"/>
							<xsl:text> </xsl:text>
						</span>
						<xsl:value-of select="php:function( 'SobiPro::TemplateTxt', 'NAVIGATION.SHOW_MORE')"/>
					</button>
				</div>
			</xsl:if>

			<nav class="d-none d-sm-block {$navclass} sp-navigation {$jscontrol}" aria-label="{php:function( 'SobiPro::TemplateTxt', 'ACCESSIBILITY.FE.NAVIGATION' )}">
				<xsl:call-template name="showpagination">
					<xsl:with-param name="psize"/>
				</xsl:call-template>
			</nav>
			<nav class="d-block d-sm-none {$navclass} sp-navigation {$jscontrol}" aria-label="{php:function( 'SobiPro::TemplateTxt', 'ACCESSIBILITY.FE.NAVIGATION' )}">
				<xsl:call-template name="showpagination">
					<xsl:with-param name="psize">pagination-sm</xsl:with-param>
				</xsl:call-template>
			</nav>
			<div class="clearfix"/>
		</xsl:if>
	</xsl:template>

	<!-- general pagination template-->
	<xsl:template name="showpagination">
		<xsl:param name="psize"/>

		<xsl:variable name="visiblePages">
			<xsl:value-of select="//config/pagination-pages/@value"/>
		</xsl:variable>

		<xsl:variable name="limit">
			<xsl:choose>
				<xsl:when test="../../current_site &lt; (number($visiblePages) div 2)">
					<xsl:value-of select="number($visiblePages) - number(../../current_site)"/>
				</xsl:when>
				<xsl:when test="../../current_site &gt; (count( ../../sites/* ) - number($visiblePages))">
					<xsl:value-of select="(number($visiblePages) - 1) - ( ../../all_sites - number(../../current_site) )"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="number($visiblePages) div 2"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<xsl:variable name="addon">
			<xsl:choose>
				<!-- remove pagination-lg|pagination-large on small devices and for B2 -->
				<xsl:when test="$psize">
					<xsl:choose>
						<xsl:when test="contains(//config/pagination-addon/@value,'pagination-lg')">
							<xsl:value-of
									select="concat(substring-before(//config/pagination-addon/@value,'pagination-lg'),substring-after(//config/pagination-addon/@value,'pagination-lg'))"/>
						</xsl:when>
						<xsl:when test="contains(//config/pagination-addon/@value,'pagination-large')">
							<xsl:value-of
									select="concat(substring-before(//config/pagination-addon/@value,'pagination-large'),substring-after(//config/pagination-addon/@value,'pagination-large'))"/>
						</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="//config/pagination-addon/@value"/>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="//config/pagination-addon/@value"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<xsl:choose>
			<!-- Pagination 1, Continuous -->
			<xsl:when test="//config/pagination/@value = 'ajax' or //config/pagination/@value = 'pagination1'">
				<ul class="pagination {$psize} d-flex justify-content-center {$addon}">
					<xsl:call-template name="pagination1">
						<xsl:with-param name="psize">
							<xsl:value-of select="$psize"/>
						</xsl:with-param>
						<xsl:with-param name="limit">
							<xsl:value-of select="$limit"/>
						</xsl:with-param>
					</xsl:call-template>
				</ul>
			</xsl:when>

			<!-- Pagination 2-->
			<xsl:otherwise>
				<ul class="pagination {$psize} d-flex justify-content-center {$addon}">
					<xsl:call-template name="pagination2">
						<xsl:with-param name="psize">
							<xsl:value-of select="$psize"/>
						</xsl:with-param>
						<xsl:with-param name="limit">
							<xsl:value-of select="$limit"/>
						</xsl:with-param>
					</xsl:call-template>
				</ul>
			</xsl:otherwise>
		</xsl:choose>

		<div class="clearfix"/>
		<input type="hidden" name="currentSite" value="1"/>
	</xsl:template>

	<!-- specific pagination template pagination1 -->
	<xsl:template name="pagination1">
		<xsl:param name="psize"/>
		<xsl:param name="limit"/>
		<xsl:variable name="current">
			<xsl:value-of select="current_site"/>
		</xsl:variable>

		<xsl:for-each select="sites/site">
			<xsl:variable name="show">
				<xsl:choose>
					<!-- before -->
					<xsl:when test="(.) &gt; ( $current - $limit ) and (.) &lt; $current">1</xsl:when>
					<!-- after -->
					<xsl:when test="(.) &lt; ( $current + $limit ) and (.) &gt; $current">2</xsl:when>
					<!-- selected -->
					<xsl:when test="(.) = $current">3</xsl:when>
					<!-- with text -->
					<xsl:when test="number(.) != (.)">4</xsl:when>
					<xsl:otherwise>0</xsl:otherwise>
				</xsl:choose>
			</xsl:variable>

			<xsl:if test="$show &gt; 0">
				<li class="page-item">
					<xsl:if test="not( @url )">
						<xsl:attribute name="class">page-item disabled</xsl:attribute>
					</xsl:if>
					<xsl:if test="@selected = 1">
						<xsl:attribute name="class">page-item active</xsl:attribute>
						<xsl:attribute name="aria-current">page</xsl:attribute>
					</xsl:if>
					<xsl:choose>
						<xsl:when test="@url">
							<a href="{@url}" class="page-link">
								<xsl:choose>
									<xsl:when test="$show = 4 and //config/pagination-symbols/@value = 1">
										<xsl:attribute name="aria-label">
											<xsl:value-of select="."/>
										</xsl:attribute>
										<xsl:choose>
											<xsl:when test="@label = 'start'">
												<xsl:value-of select="php:function( 'SobiPro::Icon', 'laquo', $font )" disable-output-escaping="yes"/>
											</xsl:when>
											<xsl:when test="@label = 'end'">
												<xsl:value-of select="php:function( 'SobiPro::Icon', 'raquo', $font )" disable-output-escaping="yes"/>
											</xsl:when>
											<xsl:when test="@label = 'prev'">
												<xsl:value-of select="php:function( 'SobiPro::Icon', 'lsaquo', $font )" disable-output-escaping="yes"/>
											</xsl:when>
											<xsl:when test="@label = 'next'">
												<xsl:value-of select="php:function( 'SobiPro::Icon', 'rsaquo', $font )" disable-output-escaping="yes"/>
											</xsl:when>
										</xsl:choose>
									</xsl:when>
									<xsl:otherwise>
										<xsl:value-of select="."/>
									</xsl:otherwise>
								</xsl:choose>
							</a>
						</xsl:when>
						<xsl:otherwise>
							<span class="page-link">
								<xsl:if test="not(@selected = 1)">
									<xsl:attribute name="tabindex">-1</xsl:attribute>
									<xsl:attribute name="aria-disabled">true</xsl:attribute>
								</xsl:if>
								<xsl:choose>
									<xsl:when test="$show = 4 and //config/pagination-symbols/@value = 1">
										<xsl:attribute name="aria-label">
											<xsl:value-of select="."/>
										</xsl:attribute>
										<xsl:choose>
											<xsl:when test="@label = 'start'">
												<xsl:value-of select="php:function( 'SobiPro::Icon', 'laquo', $font )" disable-output-escaping="yes"/>
											</xsl:when>
											<xsl:when test="@label = 'end'">
												<xsl:value-of select="php:function( 'SobiPro::Icon', 'raquo', $font )" disable-output-escaping="yes"/>
											</xsl:when>
											<xsl:when test="@label = 'prev'">
												<xsl:value-of select="php:function( 'SobiPro::Icon', 'lsaquo', $font )" disable-output-escaping="yes"/>
											</xsl:when>
											<xsl:when test="@label = 'next'">
												<xsl:value-of select="php:function( 'SobiPro::Icon', 'rsaquo', $font )" disable-output-escaping="yes"/>
											</xsl:when>
										</xsl:choose>
									</xsl:when>
									<xsl:otherwise>
										<xsl:value-of select="."/>
									</xsl:otherwise>
								</xsl:choose>
							</span>
						</xsl:otherwise>
					</xsl:choose>
				</li>
			</xsl:if>
		</xsl:for-each>
	</xsl:template>

	<!-- specific pagination template pagination2 -->
	<xsl:template name="pagination2">
		<xsl:param name="psize"/>
		<xsl:param name="limit"/>
		<xsl:variable name="pages">
			<xsl:value-of select="all_sites"/>
		</xsl:variable>
		<xsl:variable name="current">
			<xsl:value-of select="current_site"/>
		</xsl:variable>
		<xsl:variable name="baseurl">
			<xsl:value-of select="baseurl"/>
		</xsl:variable>

		<xsl:for-each select="sites/site">
			<xsl:variable name="show">
				<xsl:choose>
					<!-- first two -->
					<xsl:when test="(.) = 1 or ((.) = 2 and $psize = '')">1</xsl:when>
					<!-- dots -->
					<xsl:when test="(.) = 3 and (($pages - $current) &lt; ($pages div 2 ))">5</xsl:when>

					<!-- before -->
					<xsl:when test="((.) != 1 and (.) != $pages) and (.) &gt; ($current - $limit ) and (.) &lt; $current">1</xsl:when>
					<!-- dots -->
					<xsl:when test="(.) = 3 and (.) &lt; ($current - $limit )">5</xsl:when>
					<!-- last two -->
					<xsl:when test="((.) = $pages - 1  and $psize = '') or (.) = $pages">2</xsl:when>
					<!-- dots -->
					<xsl:when test="(.) = ($pages - 2) and (($pages - $current) &gt; ($pages div 2))">5</xsl:when>
					<!-- after -->
					<xsl:when test="(.) &lt; ( $current + $limit ) and (.) &gt; $current">2</xsl:when>
					<!-- dots -->
					<xsl:when test="(.) = ($pages - 2) and (.) &gt; ( $current + $limit )">5</xsl:when>
					<!-- selected -->
					<xsl:when test="(.) = $current">3</xsl:when>
					<!-- with text -->
					<xsl:when test="@label = 'next' or @label = 'prev'">4</xsl:when>
					<xsl:when test="@label = 'start' or @label = 'end'">0</xsl:when>
					<xsl:otherwise>0</xsl:otherwise>
				</xsl:choose>
			</xsl:variable>

			<xsl:if test="$show &gt; 0">
				<li class="page-item">
					<xsl:if test="not( @url )">
						<xsl:attribute name="class">page-item disabled</xsl:attribute>
					</xsl:if>
					<xsl:if test="@selected = 1">
						<xsl:attribute name="class">page-item active</xsl:attribute>
						<xsl:attribute name="aria-current">page</xsl:attribute>
					</xsl:if>
					<xsl:choose>
						<xsl:when test="$show = 5">
							<span class="spctrl-pagination-input page-link" data-pages="{$pages}" data-location="{$baseurl}">
								<span class="sp-navdots">...</span>
							</span>
						</xsl:when>
						<xsl:when test="@url">
							<a href="{@url}" class="page-link">
								<xsl:choose>
									<xsl:when test="$show = 4 and //config/pagination-symbols/@value = 1">
										<xsl:attribute name="aria-label">
											<xsl:value-of select="."/>
										</xsl:attribute>
										<xsl:choose>
											<xsl:when test="@label = 'start'">
												<xsl:value-of select="php:function( 'SobiPro::Icon', 'laquo', $font )" disable-output-escaping="yes"/>
											</xsl:when>
											<xsl:when test="@label = 'end'">
												<xsl:value-of select="php:function( 'SobiPro::Icon', 'raquo', $font )" disable-output-escaping="yes"/>
											</xsl:when>
											<xsl:when test="@label = 'prev'">
												<xsl:value-of select="php:function( 'SobiPro::Icon', 'lsaquo', $font )" disable-output-escaping="yes"/>
											</xsl:when>
											<xsl:when test="@label = 'next'">
												<xsl:value-of select="php:function( 'SobiPro::Icon', 'rsaquo', $font )" disable-output-escaping="yes"/>
											</xsl:when>
										</xsl:choose>
									</xsl:when>
									<xsl:otherwise>
										<xsl:value-of select="."/>
									</xsl:otherwise>
								</xsl:choose>
							</a>
						</xsl:when>
						<xsl:otherwise>
							<span class="page-link">
								<xsl:if test="not(@selected = 1)">
									<xsl:attribute name="tabindex">-1</xsl:attribute>
									<xsl:attribute name="aria-disabled">true</xsl:attribute>
								</xsl:if>
								<xsl:choose>
									<xsl:when test="$show = 4 and //config/pagination-symbols/@value = 1">
										<xsl:attribute name="aria-label">
											<xsl:value-of select="."/>
										</xsl:attribute>
										<xsl:choose>
											<xsl:when test="@label = 'start'">
												<xsl:value-of select="php:function( 'SobiPro::Icon', 'laquo', $font )" disable-output-escaping="yes"/>
											</xsl:when>
											<xsl:when test="@label = 'end'">
												<xsl:value-of select="php:function( 'SobiPro::Icon', 'raquo', $font )" disable-output-escaping="yes"/>
											</xsl:when>
											<xsl:when test="@label = 'prev'">
												<xsl:value-of select="php:function( 'SobiPro::Icon', 'lsaquo', $font )" disable-output-escaping="yes"/>
											</xsl:when>
											<xsl:when test="@label = 'next'">
												<xsl:value-of select="php:function( 'SobiPro::Icon', 'rsaquo', $font )" disable-output-escaping="yes"/>
											</xsl:when>
										</xsl:choose>
									</xsl:when>
									<xsl:otherwise>
										<xsl:value-of select="."/>
									</xsl:otherwise>
								</xsl:choose>
							</span>
						</xsl:otherwise>
					</xsl:choose>
				</li>
			</xsl:if>
		</xsl:for-each>
	</xsl:template>
</xsl:stylesheet>
