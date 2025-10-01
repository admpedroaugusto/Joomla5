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

 @modified 16 June 2021 by Sigrid Suski
-->

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl" xmlns:xls="http://www.w3.org/1999/XSL/Transform"
                exclude-result-prefixes="php">
	<xsl:output method="xml" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" encoding="UTF-8"/>

	<xsl:include href="../common/globals.xsl"/> <!-- do not comment or remove -->
	<xsl:include href="../common/topmenu.xsl"/>
	<xsl:include href="../common/messages.xsl"/>
	<xsl:include href="list.xsl"/>

	<xsl:template match="/payment_details">
		<xsl:variable name="visible-mobile">
			<xsl:choose>
				<xsl:when test="$bs >3">d-block d-sm-none</xsl:when>
				<xsl:when test="$bs = 3">hidden-sm hidden-md hidden-lg</xsl:when>
				<xsl:otherwise>hidden-tablet hidden-desktop</xsl:otherwise><!-- Bootstrap 2 -->
			</xsl:choose>
		</xsl:variable>
		<xsl:variable name="visible-other">
			<xsl:choose>
				<xsl:when test="$bs > 3">d-none d-sm-block</xsl:when>
				<xsl:when test="$bs = 3">hidden-xs</xsl:when>
				<xsl:otherwise>hidden-phone</xsl:otherwise><!-- Bootstrap 2 -->
			</xsl:choose>
		</xsl:variable>
		<xsl:variable name="row">
			<xsl:choose>
				<xsl:when test="$bs > 2">row</xsl:when>
				<xsl:otherwise>row-fluid</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<div class="sp-payment">
			<xsl:call-template name="topMenu">
				<xsl:with-param name="searchbox">true</xsl:with-param>
				<xsl:with-param name="title"/>
			</xsl:call-template>
			<xsl:apply-templates select="messages"/>

			<h2>
				<xsl:value-of select="entry"/>
			</h2>
			<xsl:call-template name="paymentTable"/>

			<p>
				<xsl:value-of select="php:function( 'SobiPro::Txt', 'PAYMENT_SELECT_PAYMENT' )"/>:
			</p>

			<!-- payment methods -->
			<div class="payment-details {$row}">
				<xsl:choose>
					<!-- Bootstrap 3,4,5 -->
					<xsl:when test="$bs > 2">
						<xsl:for-each select="payment_methods/*">
							<div class="col-sm-6 {$visible-other}">
								<xsl:call-template name="showPaymentMethod"/>
							</div>
							<div class="col-xs-12 col-12 {$visible-mobile}">
								<xsl:call-template name="showPaymentMethod"/>
							</div>
						</xsl:for-each>
					</xsl:when>

					<!-- Bootstrap 2 -->
					<xsl:otherwise>
						<ul class="thumbnails">
							<li class="span12">
								<xsl:for-each select="payment_methods/*">
									<div class="span6 {$visible-other}">
										<xsl:call-template name="showPaymentMethod"/>
									</div>
									<div class="span12 {$visible-mobile}">
										<xsl:call-template name="showPaymentMethod"/>
									</div>
								</xsl:for-each>
							</li>
						</ul>
					</xsl:otherwise>
				</xsl:choose>
			</div>

			<xsl:call-template name="bottomHook"/>
		</div>
	</xsl:template>

	<!-- Show a payment method -->
	<xsl:template name="showPaymentMethod">
		<xsl:choose>
			<!-- Bootstrap 4,5 -->
			<xsl:when test="$bs >= 4">
				<div class="card">
					<div class="card-body">
						<h4 class="card-title">
							<xsl:value-of select="@title"/>
						</h4>
						<div class="card-text">
							<xsl:choose>
								<xsl:when test="@escaped">
									<xsl:value-of select="." disable-output-escaping="yes"/>
								</xsl:when>
								<xsl:otherwise>
									<xsl:choose>
										<xsl:when test="count(./*) > 0">
											<xsl:copy-of select="./*"/>
										</xsl:when>
										<xsl:otherwise>
											<xsl:value-of select="." disable-output-escaping="yes"/>
										</xsl:otherwise>
									</xsl:choose>
								</xsl:otherwise>
							</xsl:choose>
						</div>
					</div>
				</div>
			</xsl:when>

			<!-- Bootstrap 3 -->
			<xsl:when test="$bs = 3">
				<div class="thumbnail">
					<div class="caption">
						<h4>
							<xsl:value-of select="@title"/>
						</h4>
						<xsl:choose>
							<xsl:when test="@escaped">
								<xsl:value-of select="." disable-output-escaping="yes"/>
							</xsl:when>
							<xsl:otherwise>
								<xsl:choose>
									<xsl:when test="count(./*) > 0">
										<xsl:copy-of select="./*"/>
									</xsl:when>
									<xsl:otherwise>
										<xsl:value-of select="." disable-output-escaping="yes"/>
									</xsl:otherwise>
								</xsl:choose>
							</xsl:otherwise>
						</xsl:choose>
					</div>
				</div>
			</xsl:when>

			<!-- Bootstrap 2 -->
			<xsl:otherwise>
				<div class="thumbnail">
					<h4>
						<xsl:value-of select="@title"/>
					</h4>
					<div>
						<xsl:choose>
							<xsl:when test="@escaped">
								<xsl:value-of select="." disable-output-escaping="yes"/>
							</xsl:when>
							<xsl:otherwise>
								<xsl:choose>
									<xsl:when test="count(./*) > 0">
										<xsl:copy-of select="./*"/>
									</xsl:when>
									<xsl:otherwise>
										<xsl:value-of select="." disable-output-escaping="yes"/>
									</xsl:otherwise>
								</xsl:choose>
							</xsl:otherwise>
						</xsl:choose>
					</div>
				</div>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
</xsl:stylesheet>
