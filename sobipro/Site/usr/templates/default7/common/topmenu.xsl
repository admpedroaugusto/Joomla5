<?xml version="1.0" encoding="UTF-8"?><!--
 @package: Default Template V7 for SobiPro multi-directory component with content construction support

 @author
 Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 Email: sobi[at]sigsiu.net
 Url: https://www.Sigsiu.NET

 @copyright Copyright (C) 2006 - 2023 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 @license GNU/GPL Version 3
 This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 3
 as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 See https://www.gnu.org/licenses/gpl.html and https://www.sigsiu.net/licenses.

 This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

 @modified 05 June 2023 by Sigrid Suski
-->

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">
	<xsl:output method="xml" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" encoding="UTF-8"/>

	<xsl:template name="topMenu">
		<xsl:param name="searchbox"/>
		<xsl:param name="title"/>

		<!-- load the fonts needed -->
		<xsl:call-template name="font"/>

		<!-- Show the Directory name, resp. Joomla page heading -->
		<xsl:choose>
			<xsl:when test="jheading/@show-page-heading = 1">
				<div class=" {jheading/@pageclass-sfx}">
					<h1>
						<xsl:value-of select="jheading"/>
					</h1>
				</div>
			</xsl:when>
			<xsl:otherwise>
				<h1>
					<xsl:value-of select="section"/>
					<xsl:if test="string-length($title) > 0">
						<xsl:text> - </xsl:text><xsl:value-of select="$title"/>
					</xsl:if>
				</h1>
			</xsl:otherwise>
		</xsl:choose>

		<!-- if top menu is switched on in SobiPro settings -->
		<xsl:if test="count(//menu/*) > 0">
			<xsl:choose>
				<xsl:when test="//config/topmenu/@value = 'standard'">
					<xsl:variable name="qsicon">
						<xsl:value-of select="php:function( 'SobiPro::Icon', 'search', $font )" disable-output-escaping="yes"/>
					</xsl:variable>
					<xsl:choose>

						<!-- Bootstrap 5 -->
						<xsl:when test="$bs = 5">
							<nav class="navbar navbar-expand-md navbar-dark sp-topmenu standard" role="navigation">
								<div class="container-fluid">
									<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topmenu" aria-controls="topmenu"
									        aria-expanded="false" aria-label="{php:function( 'SobiPro::Txt', 'ACCESSIBILITY.FE.TOGGLE_NAVIGATION' )}">
										<span class="navbar-toggler-icon"/>
									</button>
									<div id="topmenu" class="collapse navbar-collapse">
										<xsl:call-template name="navigationbar">
											<xsl:with-param name="nav">navbar-nav me-auto</xsl:with-param>
											<xsl:with-param name="navlink">nav-link</xsl:with-param>
										</xsl:call-template>
										<xsl:if test="//menu/search and $searchbox = 'true'">
											<form class="sp-quicksearch-form d-flex my-2 my-lg-0 pl-2 pr-2">
												<input type="search" id="sp-quicksearch" name="sp_search_for" autocomplete="off"
												       class="search-query form-control"
												       placeholder="{php:function( 'SobiPro::Txt', 'SH.SEARCH_FOR' )}"
												       aria-label="{php:function( 'SobiPro::Txt', 'ACCESSIBILITY.FE.SEARCH_FOR' )}"/>
												<button class="btn btn-delta ms-2" type="submit">
													<xsl:copy-of select="$qsicon"/>
												</button>
												<input type="hidden" name="task" value="search.search"/>
												<input type="hidden" name="option" value="com_sobipro"/>
												<input type="hidden" name="sid" value="{//@id}"/>
											</form>
										</xsl:if>
									</div>
								</div>
							</nav>
						</xsl:when>

						<!-- Bootstrap 4 -->
						<xsl:when test="$bs = 4">
							<nav class="navbar navbar-expand-md navbar-dark sp-topmenu standard" role="navigation">
								<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#topmenu" aria-controls="topmenu" aria-expanded="false"
								        aria-label="{php:function( 'SobiPro::Txt', 'ACCESSIBILITY.TOGGLE_NAVIGATION' )}">
									<span class="navbar-toggler-icon"/>
								</button>
								<div id="topmenu" class="collapse navbar-collapse">
									<xsl:call-template name="navigationbar">
										<xsl:with-param name="nav">navbar-nav me-auto</xsl:with-param>
										<xsl:with-param name="navlink">nav-link</xsl:with-param>
									</xsl:call-template>
									<xsl:if test="//menu/search and $searchbox = 'true'">
										<form class="sp-quicksearch-form form-inline my-2 my-lg-0 pl-2 pr-2">
											<input type="search" id="sp-quicksearch" name="sp_search_for" autocomplete="off"
											       class="search-query form-control"
											       placeholder="{php:function( 'SobiPro::Txt', 'SH.SEARCH_FOR' )}"
											       aria-label="{php:function( 'SobiPro::Txt', 'ACCESSIBILITY.FE.SEARCH_FOR' )}"/>
											<button class="btn btn-delta ms-2" type="submit">
												<xsl:copy-of select="$qsicon"/>
											</button>
											<input type="hidden" name="task" value="search.search"/>
											<input type="hidden" name="option" value="com_sobipro"/>
											<input type="hidden" name="sid" value="{//@id}"/>
										</form>
									</xsl:if>
								</div>
							</nav>
						</xsl:when>

						<!-- Bootstrap 3 -->
						<xsl:when test="$bs = 3">
							<nav class="navbar navbar-default sp-topmenu standard" role="navigation">
								<div class="container-fluid">
									<div class="navbar-header">
										<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#topmenu" aria-controls="topmenu"
										        aria-expanded="false" aria-label="{php:function( 'SobiPro::Txt', 'ACCESSIBILITY.TOGGLE_NAVIGATION' )}">
											<xsl:value-of select="php:function( 'SobiPro::Icon', 'bars', $font )" disable-output-escaping="yes"/>
										</button>
									</div>

									<div id="topmenu" class="collapse navbar-collapse">
										<xsl:call-template name="navigationbar">
											<xsl:with-param name="nav">nav navbar-nav</xsl:with-param>
											<xsl:with-param name="navlink">nav-link</xsl:with-param>
										</xsl:call-template>

										<xsl:if test="//menu/search and $searchbox = 'true'">
											<form class="sp-quicksearch-form navbar-form navbar-right">
												<div class="form-group">
													<input type="text" id="sp-quicksearch" name="sp_search_for" autocomplete="off"
													       class="search-query form-control"
													       placeholder="{php:function( 'SobiPro::Txt', 'SH.SEARCH_FOR' )}"
													       aria-label="{php:function( 'SobiPro::Txt', 'ACCESSIBILITY.FE.SEARCH_FOR' )}"/>
												</div>
												<button class="btn btn-delta ms-2" type="submit">
													<xsl:copy-of select="$qsicon"/>
												</button>
												<input type="hidden" name="task" value="search.search"/>
												<input type="hidden" name="option" value="com_sobipro"/>
												<input type="hidden" name="sid" value="{//@id}"/>
											</form>
										</xsl:if>
									</div>
								</div>
							</nav>
						</xsl:when>

						<xsl:otherwise> <!-- Bootstrap 2 -->
							<div class="navbar" role="navigation">
								<div class="navbar-inner sp-topmenu standard">
									<div class="container">
										<a class="btn btn-navbar text-delta navbar-toggle" data-toggle="collapse" data-target="#topmenu" aria-controls="topmenu"
										   aria-label="{php:function( 'SobiPro::Txt', 'ACCESSIBILITY.TOGGLE_NAVIGATION' )}">
											<xsl:value-of select="php:function( 'SobiPro::Icon', 'bars', $font )" disable-output-escaping="yes"/>
										</a>

										<div id="topmenu" class="nav-collapse collapse in">
											<xsl:call-template name="navigationbar">
												<xsl:with-param name="nav">nav</xsl:with-param>
												<xsl:with-param name="navlink">nav-link</xsl:with-param>
											</xsl:call-template>

											<xsl:if test="//menu/search and $searchbox = 'true'">
												<form class="sp-quicksearch-form navbar-form pull-right">
													<!--													<label class="hidden" for="sp-quicksearch">{php:function( 'SobiPro::Txt', 'SH.SEARCH_FOR' )}</label>-->
													<input type="text" id="sp-quicksearch" name="sp_search_for" autocomplete="off"
													       class="search-query"
													       placeholder="{php:function( 'SobiPro::Txt', 'SH.SEARCH_FOR' )}"
													       aria-label="{php:function( 'SobiPro::Txt', 'ACCESSIBILITY.FE.SEARCH_FOR' )}"/>
													<button class="btn btn-delta ms-2" type="submit">
														<xsl:copy-of select="$qsicon"/>
													</button>
													<input type="hidden" name="task" value="search.search"/>
													<input type="hidden" name="option" value="com_sobipro"/>
													<input type="hidden" name="sid" value="{//@id}"/>
												</form>
											</xsl:if>
										</div>
									</div>
								</div>
							</div>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:when>
				<xsl:when test="//config/topmenu/@value = 'linkbar'">
					<xsl:call-template name="linkbar"/>
				</xsl:when>
				<xsl:when test="//config/topmenu/@value = 'buttonbar'">
					<xsl:call-template name="buttonbar"/>
				</xsl:when>
			</xsl:choose>
		</xsl:if>

	</xsl:template>

	<xsl:template name="navigationbar">
		<xsl:param name="nav"/>
		<xsl:param name="navlink"/>

		<xsl:variable name="searchcollapse-in">
			<xsl:choose>
				<xsl:when test="$bs >= 4">show</xsl:when>
				<xsl:otherwise>in</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<xsl:variable name="currentUrl">
			<xsl:value-of select="php:function( 'SobiPro::Url', 'current' )"/>
		</xsl:variable>
		<xsl:variable name="task">
			<xsl:value-of select="php:function( 'SobiPro::Request', 'task' )"/>
		</xsl:variable>
		<xsl:variable name="editing">
			<xsl:choose>
				<xsl:when test="$task = 'entry.add' or $task='entry.edit'">1</xsl:when>
				<xsl:otherwise>0</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<ul class="{$nav}" role="menubar" aria-label="{php:function( 'SobiPro::Txt', 'ACCESSIBILITY.FE.SECTION_NAVIGATION', string(section) )}/>">
			<xsl:if test="//menu/front">
				<li class="nav-item">
					<a href="{//menu/front/@url}" tabindex="0" role="menuitem" class="nav-link">
						<xsl:choose>
							<xsl:when test="//menu/add">
								<xsl:choose>
									<xsl:when test="//menu/search">
										<xsl:if test="$editing = 0 and not(contains($currentUrl, //menu/search/@url))">
											<xsl:choose>
												<xsl:when test="not(//collection/menu/@url)">
													<xsl:attribute name="class">
														<xsl:value-of select="$navlink"/> active
													</xsl:attribute>
													<span class="sr-only visually-hidden">(current)</span>
												</xsl:when>
												<xsl:when test="//collection/menu/@url and not(contains($currentUrl, //collection/menu/@url))">
													<xsl:attribute name="class">
														<xsl:value-of select="$navlink"/> active
													</xsl:attribute>
													<span class="sr-only visually-hidden">(current)</span>
												</xsl:when>
											</xsl:choose>
										</xsl:if>
									</xsl:when>
									<xsl:otherwise>
										<xsl:if test="$editing = 0">
											<xsl:if test="not(contains($currentUrl, //collection/menu/@url))">
												<xsl:attribute name="class">
													<xsl:value-of select="$navlink"/> active
												</xsl:attribute>
												<span class="sr-only visually-hidden">(current)</span>
											</xsl:if>
										</xsl:if>
									</xsl:otherwise>
								</xsl:choose>
							</xsl:when>
							<xsl:otherwise>
								<xsl:if test="not(contains($currentUrl, //menu/search/@url))">
									<xsl:if test="not(contains($currentUrl, //collection/menu/@url))">
										<xsl:attribute name="class">
											<xsl:value-of select="$navlink"/> active
										</xsl:attribute>
										<span class="sr-only visually-hidden">(current)</span>
									</xsl:if>
								</xsl:if>
							</xsl:otherwise>
						</xsl:choose>
						<xsl:value-of select="php:function( 'SobiPro::Icon', 'list', $font)" disable-output-escaping="yes"/>
						<xsl:text> </xsl:text>
						<xsl:value-of select="//menu/front"/>
					</a>
				</li>
			</xsl:if>

			<xsl:choose>
				<xsl:when test="//menu/add">
					<li class="nav-item">
						<a href="{//menu/add/@url}" role="menuitem" class="nav-link">
							<xsl:if test="$editing = 1">
								<xsl:attribute name="class">
									<xsl:value-of select="$navlink"/> active
								</xsl:attribute>
								<span class="sr-only visually-hidden">(current)</span>
							</xsl:if>
							<xsl:value-of select="php:function( 'SobiPro::Icon', 'plus-circle', $font )" disable-output-escaping="yes"/>
							<xsl:text> </xsl:text>
							<xsl:value-of select="//menu/add"/>
						</a>
					</li>
				</xsl:when>
				<xsl:otherwise>
					<xsl:if test="string-length(//config/redirectlogin/@value) > 0">
						<li>
							<a href="{//config/redirectlogin/@value}" role="menuitem" class="nav-link">
								<xsl:value-of select="php:function( 'SobiPro::Icon', 'plus-circle', $font )" disable-output-escaping="yes"/>
								<xsl:text> </xsl:text>
								<xsl:value-of select="php:function( 'SobiPro::Txt', 'MN.ADD_ENTRY' )"/>
							</a>
						</li>
					</xsl:if>
				</xsl:otherwise>
			</xsl:choose>

			<xsl:if test="//menu/search">
				<li class="nav-item">
					<a href="{//menu/search/@url}/?sparam={$searchcollapse-in}" tabindex="0" role="menuitem" class="nav-link">
						<xsl:if test="contains($currentUrl, //menu/search/@url)">
							<xsl:attribute name="class">
								<xsl:value-of select="$navlink"/> active
							</xsl:attribute>
							<span class="sr-only visually-hidden">(current)</span>
						</xsl:if>
						<xsl:value-of select="php:function( 'SobiPro::Icon', 'search', $font )" disable-output-escaping="yes"/>
						<xsl:text> </xsl:text>
						<xsl:value-of select="//menu/search"/>
					</a>
				</li>
			</xsl:if>

			<xsl:if test="count(//collection/button) > 0">
				<li class="nav-item">
					<xsl:variable name="mnu">
						<xsl:if test="contains($currentUrl, //collection/menu/@url)">
							<xsl:text> active</xsl:text>
						</xsl:if>
					</xsl:variable>
					<a href="{//collection/button/a/@href}" tabindex="0" role="menuitem" style="{//collection/button/a/@style}">
						<xsl:attribute name="class">
							<xsl:value-of select="//collection/button/a/@class"/>
							<xsl:text> </xsl:text>
							<xsl:value-of select="$navlink"/>
							<xsl:value-of select="$mnu"/>
						</xsl:attribute>
						<xsl:copy-of select="//collection/button/a/span"/>
						<xsl:value-of select="//collection/button/a"/>
					</a>
					<!--<xsl:copy-of select="//collection/button/*"/>-->
				</li>
			</xsl:if>
		</ul>

	</xsl:template>

	<xsl:template name="linkbar">
		<xsl:variable name="searchcollapse-in">
			<xsl:choose>
				<xsl:when test="$bs >= 4">show</xsl:when>
				<xsl:otherwise>in</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<div class="sp-topmenu linkbar" role="menubar">
			<xsl:variable name="currentUrl">
				<xsl:value-of select="php:function( 'SobiPro::Url', 'current' )"/>
			</xsl:variable>
			<xsl:variable name="editing">
				<xsl:choose>
					<xsl:when test="//menu/add and ($currentUrl = //menu/add/@url or contains($currentUrl,'entry.edit'))">1</xsl:when>
					<xsl:otherwise>0</xsl:otherwise>
				</xsl:choose>
			</xsl:variable>

			<ul>
				<xsl:if test="//menu/front">
					<li>
						<a class="sp-topmenu-link" href="{//menu/front/@url}" tabindex="0" role="menuitem">
							<xsl:choose>
								<xsl:when test="//menu/add">
									<xsl:choose>
										<xsl:when test="//menu/search">
											<xsl:if test="$editing = 0 and not(contains($currentUrl, //menu/search/@url))">
												<xsl:if test="not(contains($currentUrl, //collection/menu/@url))">
													<xsl:attribute name="class">sp-topmenu-link active</xsl:attribute>
												</xsl:if>
											</xsl:if>
										</xsl:when>
										<xsl:otherwise>
											<xsl:if test="$editing = 0">
												<xsl:if test="not(contains($currentUrl, //collection/menu/@url))">
													<xsl:attribute name="class">sp-topmenu-link active</xsl:attribute>
												</xsl:if>
											</xsl:if>
										</xsl:otherwise>
									</xsl:choose>
								</xsl:when>
								<xsl:otherwise>
									<xsl:if test="not(contains($currentUrl, //menu/search/@url))">
										<xsl:if test="not(contains($currentUrl, //collection/menu/@url))">
											<xsl:attribute name="class">sp-topmenu-link active</xsl:attribute>
										</xsl:if>
									</xsl:if>
								</xsl:otherwise>
							</xsl:choose>
							<xsl:value-of select="//menu/front"/>
						</a>
					</li>
				</xsl:if>
				<xsl:choose>
					<xsl:when test="//menu/add">
						<li>
							<a class="sp-topmenu-link" href="{//menu/add/@url}" tabindex="0" role="menuitem">
								<xsl:if test="$editing = 1">
									<xsl:attribute name="class">sp-topmenu-link active</xsl:attribute>
								</xsl:if>
								<xsl:text> </xsl:text>
								<xsl:value-of select="//menu/add"/>
							</a>
						</li>
					</xsl:when>
					<xsl:otherwise>
						<xsl:if test="string-length(//config/redirectlogin/@value) > 0">
							<li>
								<a class="sp-topmenu-link" href="{//config/redirectlogin/@value}" tabindex="0" role="menuitem">
									<xsl:text> </xsl:text>
									<xsl:value-of select="php:function( 'SobiPro::Txt', 'MN.ADD_ENTRY' )"/>
								</a>
							</li>
						</xsl:if>
					</xsl:otherwise>
				</xsl:choose>
				<xsl:if test="//menu/search">
					<li>
						<a class="sp-topmenu-link" href="{//menu/search/@url}/?sparam={$searchcollapse-in}" tabindex="0" role="menuitem">
							<xsl:if test="$currentUrl = //menu/search/@url">
								<xsl:attribute name="class">sp-topmenu-link active</xsl:attribute>
							</xsl:if>
							<xsl:value-of select="//menu/search"/>
						</a>
					</li>
				</xsl:if>
				<xsl:if test="count(//collection/button) > 0">
					<li>
						<xsl:variable name="mnu">
							<xsl:if test="contains($currentUrl, //collection/menu/@url)">
								<xsl:text>sp-topmenu-link active</xsl:text>
							</xsl:if>
						</xsl:variable>
						<a tabindex="0" role="menuitem" href="{//collection/button/a/@href}" style="{//collection/button/a/@style}">
							<xsl:attribute name="class">
								<xsl:value-of select="//collection/button/a/@class"/>
								<xsl:value-of select="$mnu"/>
							</xsl:attribute>
							<xsl:copy-of select="//collection/button/a/span"/>
							<xsl:value-of select="//collection/button/a"/>
						</a>
						<!--<xsl:copy-of select="//collection/button/*"/>-->
					</li>
				</xsl:if>
			</ul>
		</div>
	</xsl:template>

	<xsl:template name="buttonbar">
		<xsl:variable name="searchcollapse-in">
			<xsl:choose>
				<xsl:when test="$bs >= 4">show</xsl:when>
				<xsl:otherwise>in</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<div class="sp-topmenu buttonbar" role="menubar">
			<div class="menu">
				<xsl:if test="//menu/front">
					<a href="{//menu/front/@url}" tabindex="0" class="btn btn-alpha" role="menuitem" data-role="button">
						<xsl:value-of select="//menu/front"/>
					</a>
				</xsl:if>
			</div>
			<div class="add">
				<xsl:choose>
					<xsl:when test="//menu/add">
						<a href="{//menu/add/@url}" tabindex="0" class="btn btn-delta" role="menuitem">
							<xsl:value-of select="php:function( 'SobiPro::Txt', 'MN.ADD_ENTRY' )"/>
						</a>
					</xsl:when>
					<xsl:otherwise>
						<xsl:if test="string-length(//config/redirectlogin/@value) > 0">
							<a href="{//config/redirectlogin/@value}" class="btn btn-delta" tabindex="0" role="menuitem" data-role="button">
								<xsl:value-of select="php:function( 'SobiPro::Txt', 'MN.ADD_ENTRY' )"/>
							</a>
						</xsl:if>
					</xsl:otherwise>
				</xsl:choose>
			</div>
			<div class="search">
				<xsl:if test="//menu/search">
					<a href="{//menu/search/@url}/?sparam={$searchcollapse-in}" class="btn btn-delta" tabindex="0" role="menuitem" data-role="button">
						<xsl:value-of select="//menu/search"/>
					</a>
				</xsl:if>
			</div>
			<xsl:if test="count(//collection/button) > 0">
				<div class="collection">
					<a tabindex="0" role="menuitem" href="{//collection/button/a/@href}" style="{//collection/button/a/@style}">
						<xsl:attribute name="class">
							<xsl:text>btn </xsl:text>
							<xsl:value-of select="//collection/button/a/@class"/>
						</xsl:attribute>
						<xsl:copy-of select="//collection/button/a/span"/>
						<xsl:value-of select="//collection/button/a"/>
					</a>
				</div>
			</xsl:if>
		</div>
	</xsl:template>

	<xsl:template name="bottomHook">
		<xsl:if test="//config/debug/@value = 1">
			<div class="mt-5">
				<p>
					<xsl:text>Task: </xsl:text>
					<xsl:value-of select="php:function( 'SobiPro::Request', 'task' )"/>
				</p>
				<xsl:for-each select="//config/*">
					<xsl:value-of select="name()"/>
					<xsl:text>: </xsl:text>
					<xsl:value-of select="@value"/>
					<br/>
				</xsl:for-each>
			</div>
		</xsl:if>
	</xsl:template>

</xsl:stylesheet>

