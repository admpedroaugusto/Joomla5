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

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl" xmlns:xls="http://www.w3.org/1999/XSL/Transform"
                exclude-result-prefixes="php">
	<xsl:output method="xml" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" encoding="UTF-8"/>

	<xsl:include href="../common/globals.xsl"/> <!-- do not comment or remove -->
	<xsl:include href="../common/topmenu.xsl"/>
	<xsl:include href="../common/messages.xsl"/>
	<xsl:include href="list.xsl"/>

	<xsl:template match="/payment_details">
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
				<xsl:value-of select="php:function( 'SobiPro::TemplateTxt', 'PAYMENT_SELECT_PAYMENT' )"/>:
			</p>

			<!-- payment methods -->
			<div class="payment-details row">
				<xsl:for-each select="payment_methods/*">
					<div class="col-sm-6 d-none d-sm-block">
						<xsl:call-template name="showPaymentMethod"/>
					</div>
					<div class="col-xs-12 col-12 d-block d-sm-none">
						<xsl:call-template name="showPaymentMethod"/>
					</div>
				</xsl:for-each>
			</div>

			<xsl:call-template name="bottomHook"/>
		</div>
	</xsl:template>

	<!-- Show a payment method -->
	<xsl:template name="showPaymentMethod">
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
	</xsl:template>
</xsl:stylesheet>
