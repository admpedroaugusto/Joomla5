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

 @modified 19 September 2024 by Sigrid Suski
-->

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">
	<xsl:output method="xml" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" encoding="UTF-8"/>

	<xsl:include href="../common/globals.xsl"/> <!-- do not comment or remove -->
	<xsl:include href="../common/topmenu.xsl"/>
	<xsl:include href="../common/manage.xsl"/>
	<xsl:include href="../common/alphamenu.xsl"/>
	<xsl:include href="../common/messages.xsl"/>
	<xsl:include href="../common/showfields.xsl"/>

	<!-- Uncomment only if Profile Field is installed -->
	<!--<xsl:include href="../common/profile.xsl" />-->

	<!-- Uncomment only if Review & Ratings App is installed -->
	<!--<xsl:include href="../common/review.xsl" />-->

	<!-- Uncomment only if Collection App is installed -->
	<!--<xsl:include href="../common/collection.xsl" />-->

	<xsl:template match="/entry_details">

		<!-- For proper work a container is needed, we assume that the component area is placed into a container by the template.
		If not, you need to add a container around the SobiPro output here -->
		<div class="sp-details">
			<xsl:call-template name="topMenu">
				<xsl:with-param name="searchbox">true</xsl:with-param>
				<xsl:with-param name="title"/>
			</xsl:call-template>
			<xsl:apply-templates select="alphaMenu"/>
			<xsl:apply-templates select="messages"/>

			<!-- Add itemprop="itemReviewed" itemscope="itemscope" itemtype="https://schema.org/<yourtype> to the div below if using Reviews and Ratings application "-->
			<div class="sp-detail-entry">
				<div class="d-flex align-items-start mt-3">
					<xsl:call-template name="manage">
						<xsl:with-param name="entry" select="entry"/>
					</xsl:call-template>

					<xsl:if test="( //reviews/settings/rating_enabled = 1 ) and document('')/*/xsl:include[@href='../common/review.xsl'] ">
						<xsl:call-template name="ratingStars"/>
					</xsl:if>

					<!-- works only with Voting application -->
					<xsl:if test="count(entry/voting/*) > 0">
						<xsl:copy-of select="entry/voting/*"/>
					</xsl:if>

					<h1 class="sp-namefield">
						<xsl:call-template name="development">
							<xsl:with-param name="fieldname" select="entry/name"/>
						</xsl:call-template>
						<xsl:value-of select="entry/name"/>
						<xsl:call-template name="status">
							<xsl:with-param name="entry" select="entry"/>
						</xsl:call-template>
					</h1>
					<xsl:if test="//config/showprint/@value = 1">
						<xsl:variable name="printUrl">
							{"tmpl":"component","sptpl":"print","out":"html","sid":"<xsl:value-of select="entry/@id"/>"}
						</xsl:variable>
						<div class="ms-auto">
							<a class="btn btn-delta" type="button" onclick="javascript:window.open( this.href, 'print', 'status = 1, height = auto, width = 700' ); return false;">
								<xsl:attribute name="href">
									<xsl:value-of select="php:function( 'SobiPro::Url', $printUrl )"/>
								</xsl:attribute>
								<xsl:value-of select="php:function( 'SobiPro::Icon', 'print', $font )" disable-output-escaping="yes"/>
								<xsl:text> </xsl:text>
								<xsl:value-of select="php:function( 'SobiPro::Txt' , 'PRINT' )"/>
							</a>
						</div>
					</xsl:if>
				</div>
				<!-- Example for showing the description of the primary category the entry is located in -->
				<!--				<xsl:if test="count(entry/categories)>0">-->
				<!--					<xsl:for-each select="entry/categories/category">-->
				<!--						<xsl:if test="@primary = 'true' and string-length(description) > 0">-->
				<!--							<div class="sp-category-description" data-role="content">-->
				<!--								<xsl:value-of select="description" disable-output-escaping="yes"/>-->
				<!--							</div>-->
				<!--						</xsl:if>-->
				<!--					</xsl:for-each>-->
				<!--				</xsl:if>-->

				<!-- Uncomment only if Collection App is installed -->
				<!--<xsl:call-template name="collection"><xsl:with-param name="entry" select="entry"/></xsl:call-template>-->

				<xsl:choose>
					<xsl:when test="//config/showtabs/@value = 1">
						<xsl:call-template name="tabbedDetailsView"/>
					</xsl:when>
					<xsl:otherwise>
						<!-- Loop to show all enabled fields from fields manager -->
						<xsl:variable name="layout">
							<xsl:if test="//config/dvtabular/@value = 1">sp-entry-table</xsl:if>
						</xsl:variable>
						<div class="{$layout} dv" data-role="content">
							<xsl:for-each select="entry/fields/*">
								<xsl:call-template name="showfield">
									<xsl:with-param name="fieldname" select="."/>
									<xsl:with-param name="view" select="'dv'"/>
								</xsl:call-template>
							</xsl:for-each>
						</div>

						<xsl:if test="document('')/*/xsl:include[@href='../common/review.xsl'] ">
							<xsl:call-template name="ratingSummary"/>
						</xsl:if>
					</xsl:otherwise>
				</xsl:choose>


				<xsl:if test="count(entry/categories) > 0">
					<div class="sp-entry-categories mt-3">
						<xsl:value-of select="php:function( 'SobiPro::TemplateTxt' , 'ENTRY_LOCATED_IN' )"/><xsl:text> </xsl:text>
						<xsl:for-each select="entry/categories/category">
							<a href="{@url}">
								<xsl:choose>
									<xsl:when test="string-length(name) > 0">
										<xsl:value-of select="name"/>
									</xsl:when>
									<xsl:otherwise>
										<xsl:value-of select="."/>
									</xsl:otherwise>
								</xsl:choose>
							</a>
							<xsl:if test="position() != last()">
								<xsl:text> | </xsl:text>
							</xsl:if>
						</xsl:for-each>
					</div>
				</xsl:if>
			</div>
			<div class="clearfix"/>

			<xsl:if test="//config/showtabs/@value != 1">
				<xsl:if test="document('')/*/xsl:include[@href='../common/review.xsl'] ">
					<xsl:choose>
						<xsl:when test="count(/entry_details/review_form/*) or (/entry_details/reviews/summary_review/overall > 0)">
							<xsl:call-template name="reviewForm"/>
							<xsl:call-template name="reviews"/>
						</xsl:when>
						<xsl:otherwise>
							<div class="sp-review-first-msg">
								<xsl:value-of select="php:function( 'SobiPro::Icon', 'exclamation-circle-large', $font )" disable-output-escaping="yes"/>
								<xsl:text> </xsl:text>
								<xsl:value-of select="php:function( 'SobiPro::TemplateTxt', 'ENTRY_NO_REVIEWS_NO_ADD', string(entry/name) )"/>
							</div>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:if>
			</xsl:if>

			<!-- Uncomment only if Profile Field is installed -->
			<!--<xsl:call-template name="UserContributions" />-->

			<xsl:call-template name="bottomHook"/>
		</div>
	</xsl:template>

	<xsl:template name="tabbedDetailsView">
		<xsl:variable name="tabstyle">
			<xsl:value-of select="//config/tabstyle/@value"/>
		</xsl:variable>
		<xsl:variable name="tabstyleonly">
			<xsl:if test="contains($tabstyle ,'sp-navtabs')">
				<xsl:value-of select="substring-before($tabstyle,'sp-navtabs')"/>
			</xsl:if>
		</xsl:variable>
		<xsl:variable name="tabcolor">
			<xsl:value-of select="//config/tabcolor/@value"/>
		</xsl:variable>

		<!-- the tabs -->
		<nav class="navbar navbar-expand-lg {$tabstyle}">
			<!-- <div class="container-fluid"> -->
			<button class="navbar-toggler navbar-{$tabcolor} sp-toggler bg-bata" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
			        aria-controls="navbarNav"
			        aria-expanded="false"
			        aria-label="Toggle navigation">
				<xsl:value-of select="php:function( 'SobiPro::Icon', 'bars', $font)" disable-output-escaping="yes"/>
				<!-- <span class="navbar-toggler-icon"></span> -->
			</button>
			<div class="collapse navbar-collapse" id="navbarNav">
				<ul class="nav nav-{$tabstyle} nav-{$tabcolor}" id="#details">
					<li class="nav-item">
						<a href="#tab1" aria-controls="tab1" class="nav-link active" aria-current="page" data-bs-toggle="tab">
							<xsl:value-of select="php:function( 'SobiPro::Txt' , 'Tab 1' )"/>
						</a>
					</li>
					<li class="nav-item">
						<a href="#tab2" aria-controls="tab2" role="tab" data-bs-toggle="tab" class="nav-link">
							<xsl:value-of select="php:function( 'SobiPro::Txt' , 'Tab 2' )"/>
						</a>
					</li>
					<!-- Gallery: only if there are images in the entry with alias starting with 'field_gallery' -->
					<xsl:if test="count(entry/fields/*[starts-with(name(),'field_gallery')]/data/*) > 0">
						<li class="nav-item">
							<a href="#gallery" aria-controls="gallery" role="tab" data-bs-toggle="tab" class="nav-link">
								<xsl:value-of select="php:function( 'SobiPro::Txt' , 'GALLERY' )"/>
							</a>
						</li>
					</xsl:if>
					<!-- Feedback: only if Review and Rating App is installed -->
					<xsl:if test="document('')/*/xsl:include[@href='../common/review.xsl']">
						<li class="nav-item">
							<a href="#feedback" aria-controls="feedback" role="tab" data-bs-toggle="tab" class="nav-link">
								<xsl:value-of select="php:function( 'SobiPro::Txt' , 'FEEDBACK' )"/>
							</a>
						</li>
					</xsl:if>
				</ul>
			</div>
			<!-- </div> -->
		</nav>

		<!-- the tab content -->
		<div class="tab-content {$tabstyleonly} tab-{$tabcolor}" data-role="content">
			<!-- Tab 1 -->
			<div role="tabpanel" class="tab-pane active" id="tab1">
				<div class="d-flex flex-column flex-lg-row">
					<div class="flex-shrink-0 sp-image">
						<!-- Add here fields for tab 1 like shown below -->
						<!-- Main Image -->
						<xsl:call-template name="showfield">
							<xsl:with-param name="fieldname" select="entry/fields/field_company_logo"/>
							<xsl:with-param name="view" select="'dv'"/>
						</xsl:call-template>
					</div>
					<div>
						<!-- Description -->
						<xsl:call-template name="showfield">
							<xsl:with-param name="fieldname" select="entry/fields/field_description"/>
							<xsl:with-param name="view" select="'dv'"/>
						</xsl:call-template>
					</div>
				</div>
			</div>

			<!-- Tab 2 -->
			<div role="tabpanel" class="tab-pane" id="tab2">
				<div class="d-flex flex-column flex-lg-row mb-4">
					<div class="flex-shrink-0">
						<!-- Add here fields for tab 2 like shown below-->
						<xsl:call-template name="showfield">
							<xsl:with-param name="fieldname" select="entry/fields/field_street"/>
							<xsl:with-param name="view" select="'dv'"/>
						</xsl:call-template>
						<xsl:call-template name="showfield">
							<xsl:with-param name="fieldname" select="entry/fields/field_city"/>
							<xsl:with-param name="view" select="'dv'"/>
						</xsl:call-template>
						<xsl:call-template name="showfield">
							<xsl:with-param name="fieldname" select="entry/fields/field_country"/>
							<xsl:with-param name="view" select="'dv'"/>
						</xsl:call-template>
					</div>
				</div>
			</div>

			<!-- Gallery tab (Tab3) -->
			<div role="tabpanel" class="tab-pane" id="gallery">
				<xsl:if test="count(entry/fields/*[starts-with(name(),'field_gallery')]/data/*) > 0">
					<xsl:choose>
						<xsl:when test="//config/showcarousel/@value = 1">
							<!-- Gallery with Bootstrap Carousel -->
							<div id="spctrl-carousel" class="carousel sp-carousel-pane slide" data-bs-ride="true" data-bs-theme="dark">
								<!-- Indicators -->
								<div class="carousel-indicators">
									<xsl:for-each select="entry/fields/*[starts-with(name(),'field_gallery')]">
										<xsl:if test="count(data/*) > 0">
											<button type="button" data-bs-target="#spctrl-carousel">
												<xsl:attribute name="data-bs-slide-to">
													<xsl:value-of select="position() - 1"/>
												</xsl:attribute>
												<xsl:attribute name="class">sp-carousel-target
													<xsl:if test="position() = 1">active</xsl:if>
												</xsl:attribute>
												<xsl:attribute name="aria-current">
													<xsl:if test="position() = 1">true</xsl:if>
												</xsl:attribute>
												<xsl:attribute name="aria-label">
													<xsl:value-of select="."/>
												</xsl:attribute>
											</button>
										</xsl:if>
									</xsl:for-each>
								</div>

								<!-- Wrapper for slides -->
								<div class="carousel-inner">
									<xsl:for-each select="entry/fields/*[starts-with(name(),'field_gallery')]">
										<xsl:if test="count(data/*) > 0">
											<div data-bs-interval="10000">
												<xsl:attribute name="class">
													<xsl:choose>
														<xsl:when test="position() = 1">carousel-item active</xsl:when>
														<xsl:otherwise>carousel-item</xsl:otherwise>
													</xsl:choose>
												</xsl:attribute>
												<xsl:attribute name="id">
													<xsl:value-of select="position() - 1"/>
												</xsl:attribute>
												<!-- use the large image -->
												<img src="{data/@image}" itemprop="image" class="sp-carousel-image d-block" alt=""/>
												<div class="carousel-caption d-none d-md-block">
													<xsl:value-of select="data/img/@alt"/>
												</div>
											</div>
										</xsl:if>
									</xsl:for-each>
								</div>

								<!-- Carousel controls -->
								<button class="carousel-control-prev" type="button" data-bs-target="#spctrl-carousel" data-bs-slide="prev">
									<!--									<span class="carousel-control-prev-icon" aria-hidden="true"/>-->
									<xsl:value-of select="php:function( 'SobiPro::Icon', 'chevron-left', $font)" disable-output-escaping="yes"/>
									<span class="visually-hidden">
										<xsl:value-of select="php:function( 'SobiPro::Txt' , 'PN.PREVIOUS' )"/>
									</span>
								</button>
								<button class="carousel-control-next" type="button" data-bs-target="#spctrl-carousel" data-bs-slide="next">
									<!--									<span class="carousel-control-next-icon" aria-hidden="true"/>-->
									<xsl:value-of select="php:function( 'SobiPro::Icon', 'chevron-right', $font)" disable-output-escaping="yes"/>
									<span class="visually-hidden">
										<xsl:value-of select="php:function( 'SobiPro::Txt' , 'PN.NEXT' )"/>
									</span>
								</button>
							</div>
						</xsl:when>

						<xsl:otherwise>
							<!-- normal image gallery-->
							<div class="d-flex flex-wrap justify-content-center">
								<xsl:for-each select="entry/fields/*[starts-with(name(),'field_gallery')]">
									<xsl:call-template name="showfield">
										<xsl:with-param name="fieldname" select="."/>
										<xsl:with-param name="view" select="'dv'"/>
									</xsl:call-template>
								</xsl:for-each>
							</div>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:if>
			</div>

			<!-- Feedback tab (tab 4) -->
			<!-- Feedback: only if Review and Rating App is installed -->
			<xsl:if test="document('')/*/xsl:include[@href='../common/review.xsl'] ">
				<div class="tab-pane" id="feedback">
					<xsl:call-template name="ratingSummary"/>
					<xsl:choose>
						<xsl:when test="count(/entry_details/review_form/*) or (/entry_details/reviews/summary_review/overall > 0)">
							<div class="">
								<xsl:call-template name="reviewForm"/>
								<xsl:call-template name="reviews"/>
							</div>
						</xsl:when>
						<xsl:otherwise>
							<div class="sp-review-first-msg">
								<xsl:value-of select="php:function( 'SobiPro::Icon', 'exclamation-circle-large', $font )" disable-output-escaping="yes"/>
								<xsl:text> </xsl:text>
								<xsl:value-of select="php:function( 'SobiPro::Txt', 'ENTRY_NO_REVIEWS_NO_ADD', string(entry/name) )"/>
							</div>
						</xsl:otherwise>
					</xsl:choose>
				</div>
			</xsl:if>
		</div>
	</xsl:template>
</xsl:stylesheet>
