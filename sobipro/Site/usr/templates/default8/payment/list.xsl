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
	<xsl:template name="paymentTable">
		<div class="table-responsive">
			<table class="table table-striped table-delta sp-payment-items">
				<thead>
					<tr>
						<th>#</th>
						<th>
							<xsl:value-of select="php:function( 'SobiPro::TemplateTxt', 'PAYMENT_POSITION_NAME' )"/>
						</th>
						<th>
							<xsl:if test="summary/@vat-raw > 0">
								<div class="float-end">
									<xsl:value-of select="php:function( 'SobiPro::TemplateTxt', 'PAYMENT_POSITION_NET' )"/>
								</div>
							</xsl:if>
						</th>
						<th class="sp-payment-price">
							<div class="float-end">
								<xsl:choose>
									<xsl:when test="summary/@vat-raw > 0">
										<xsl:value-of select="php:function( 'SobiPro::TemplateTxt', 'PAYMENT_POSITION_GROSS' )"/>
									</xsl:when>
									<xsl:otherwise>
										<xsl:value-of select="php:function( 'SobiPro::TemplateTxt', 'PAYMENT_POSITION_AMOUNT' )"/>
									</xsl:otherwise>
								</xsl:choose>
							</div>
						</th>
					</tr>
				</thead>
				<tbody>
					<xsl:for-each select="positions/position">
						<tr>
							<td>
								<xsl:value-of select="position()"/>
							</td>
							<td>
								<xsl:value-of select="."/>
							</td>
							<td>
								<xsl:if test="//summary/@vat-raw > 0">
									<div class="float-end">
										<xsl:value-of select="@netto"/>
									</div>
								</xsl:if>
							</td>
							<td class="sp-payment-price">
								<div class="float-end">
									<xsl:choose>
										<xsl:when test="//summary/@vat-raw > 0">
											<xsl:value-of select="@brutto"/>
										</xsl:when>
										<xsl:otherwise>
											<xsl:value-of select="summary/@vat-raw"/>
											<xsl:value-of select="@amount"/>
										</xsl:otherwise>
									</xsl:choose>
								</div>
							</td>
						</tr>
					</xsl:for-each>
					<xsl:if test="string-length(discount/@discount) > 0">
						<tr>
							<td/>
							<td colspan="2">
								<xsl:value-of select="discount/@for"/>
								<xsl:if test="discount/@is-percentage = 'true'">
									<xsl:text> (</xsl:text>
									<xsl:value-of select="discount/@discount"/>
									<xsl:text>)</xsl:text>
								</xsl:if>
							</td>
							<td>
								<div class="float-end">
									<xsl:text>-</xsl:text>
									<xsl:value-of select="discount/@discount-sum"/>
								</div>
							</td>
						</tr>
					</xsl:if>

				</tbody>
			</table>

			<table class="table sp-payment">
				<tbody>
					<tr class="sp-payment-summary-caption">
						<td colspan="4">
							<xsl:value-of select="php:function( 'SobiPro::TemplateTxt', 'PAYMENT_POSITION_SUMMARY' )"/>:
						</td>
					</tr>
					<xsl:choose>
						<xsl:when test="summary/@vat-raw > 0">
							<tr>
								<td colspan="3" class="bg-delta-medium border-delta-dark">
									<div class="float-end">
										<xsl:value-of select="php:function( 'SobiPro::TemplateTxt', 'PAYMENT_POSITION_NET' )"/>
									</div>
								</td>
								<td class="bg-delta-medium border-delta-dark">
									<div class="float-end">
										<xsl:value-of select="summary/@sum-netto"/>
									</div>
								</td>
							</tr>
							<tr>
								<td colspan="3">
									<div class="float-end">
										<xsl:value-of select="php:function( 'SobiPro::TemplateTxt', 'VAT' )"/>
										(<xsl:value-of select="summary/@vat"/>)
									</div>
								</td>
								<td>
									<div class="float-end">
										<xsl:value-of select="summary/@sum-vat"/>
									</div>
								</td>
							</tr>
							<tr class="table-delta sp-payment-summary">
								<td colspan="3" class="bg-delta-dark border-delta-dark">
									<div class="float-end">
										<xsl:value-of select="php:function( 'SobiPro::TemplateTxt', 'PAYMENT_POSITION_GROSS' )"/>
									</div>
								</td>
								<td class="bg-delta-dark border-delta-dark sp-payment-price">
									<div class="float-end">
										<xsl:value-of select="summary/@sum-brutto"/>
									</div>
								</td>
							</tr>
						</xsl:when>
						<xsl:otherwise>
							<tr class="table-delta sp-payment-summary">
								<td colspan="3">
									<div class="float-end">
										<xsl:value-of select="php:function( 'SobiPro::TemplateTxt', 'PAYMENT_POSITION_TOTAL' )"/>
									</div>
								</td>
								<td>
									<div class="float-end">
										<xsl:value-of select="summary/@sum-amount"/>
									</div>
								</td>
							</tr>
						</xsl:otherwise>
					</xsl:choose>
					<xsl:if test="string-length(summary/@coupon) > 0">
						<tr>
							<td colspan="4">
								<div class="float-end">
									<xsl:value-of select="summary/@coupon"/>
								</div>
							</td>
						</tr>
					</xsl:if>
				</tbody>
			</table>
		</div>
	</xsl:template>
</xsl:stylesheet>
