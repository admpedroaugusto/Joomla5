<?xml version="1.0" encoding="UTF-8"?><!--
 @package Default Template V8 for SobiPro multi-directory component with content construction support

 @author
 Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 Url: https://www.Sigsiu.NET

 @copyright Copyright (C) 2006-2023 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 @license GNU/GPL Version 3
 This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 3
 as published by the Free Software Foundation, and under the additional terms according to section 7 of GPL v3.
 See https://www.gnu.org/licenses/gpl.html and https://www.sigsiu.net/licenses.

 This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

 @modified 05 June 2023 by Sigrid Suski
-->

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">
	<xsl:output method="xml" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" encoding="UTF-8"/>

	<xsl:template name="editfield">
		<xsl:param name="fieldname"/>

		<xsl:if test="( name($fieldname) != 'save_button' ) and ( name($fieldname) != 'cancel_button' )">

			<!-- id of the field -->
			<xsl:variable name="fieldId" select="name($fieldname)"/>
			<!-- label width -->
			<xsl:variable name="lw">
				<xsl:choose>
					<xsl:when test="//config/columns-edit/@value = 'twocolumns'">
						<xsl:value-of select="//config/label-width-edit/@value"/>
					</xsl:when>
					<xsl:otherwise>12</xsl:otherwise>
				</xsl:choose>
			</xsl:variable>
			<!-- full content width -->
			<xsl:variable name="fcw">
				<xsl:value-of select="12 - number($lw)"/>
			</xsl:variable>
			<!-- content width -->
			<xsl:variable name="cw">
				<xsl:choose>
					<xsl:when test="//config/columns-edit/@value = 'twocolumns'">
						<xsl:choose>
							<xsl:when test="string-length( $fieldname/@width ) > 0">
								<xsl:choose>
									<xsl:when test="number($fieldname/@width) + number($lw) > 12">
										<xsl:value-of select="$fcw"/>
									</xsl:when>
									<xsl:otherwise>
										<xsl:value-of select="$fieldname/@width"/>
									</xsl:otherwise>
								</xsl:choose>
							</xsl:when>
							<xsl:otherwise>{$fcw}</xsl:otherwise>
						</xsl:choose>
					</xsl:when>
					<xsl:otherwise>
						<xsl:choose>
							<xsl:when test="string-length( $fieldname/@width ) > 0">
								<xsl:value-of select="$fieldname/@width"/>
							</xsl:when>
							<xsl:otherwise>12</xsl:otherwise>
						</xsl:choose>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:variable>
			<!-- column name and width -->
			<xsl:variable name="colname">
				<xsl:text>col-</xsl:text>
				<xsl:if test="not(//config/grid-edit/@value = 'xs')">
					<xsl:value-of select="//config/grid-edit/@value"/>
					<xsl:text>-</xsl:text>
				</xsl:if>
			</xsl:variable>
			<!-- offset name and width -->
			<xsl:variable name="ofsname">
				<xsl:text>offset-</xsl:text>
				<xsl:if test="not(//config/grid-edit/@value = 'xs')">
					<xsl:value-of select="//config/grid-edit/@value"/>
					<xsl:text>-</xsl:text>
				</xsl:if>
			</xsl:variable>


			<!-- which layout? -->
			<xsl:choose>
				<!-- 2 columns layout -->
				<xsl:when test="//config/columns-edit/@value = 'twocolumns'">

					<!-- overall container -->
					<div class="row {$fieldname/@css-edit} mb-4" id="{$fieldId}-container">
						<xsl:call-template name="development">
							<xsl:with-param name="fieldname" select="$fieldname"/>
						</xsl:call-template>

						<!-- payment box if not for free -->
						<xsl:if test="string-length( $fieldname/fee ) > 0 and not($fieldname/@data-meaning = 'price' or $fieldname/@data-meaning = 'terms')">
							<div class="{$colname}{$fcw} {$ofsname}{$lw}">
								<div class="form-check form-switch sp-paybox">
									<input name="{$fieldId}Payment" id="{$fieldId}-payment" value="" type="checkbox" class="spctrl-payment-box form-check-input"/>
									<label class="form-check-label" for="{$fieldId}-payment">
										<xsl:value-of select="$fieldname/fee_msg"/><xsl:text> </xsl:text>
										<xsl:value-of select="php:function( 'SobiPro::TemplateTxt', 'TP.PAYMENT_ADD' )"/>
									</label>
								</div>
							</div>
						</xsl:if>

						<!-- label if it should be shown -->
						<xsl:if test="$fieldname/label/@show = 1">
							<label class="{$colname}{$lw} col-form-label" for="{$fieldId}">
								<xsl:choose>
									<!-- if helptext as popover -->
									<xsl:when test="string-length( $fieldname/description ) and $fieldname/description/@position = 'popup'">
										<a href="#" rel="sp-popover" tabindex="0" data-bs-container="#SobiPro" data-bs-trigger="hover" data-bs-placement="top"
										   data-bs-content="{$fieldname/description}"
										   data-bs-title="{$fieldname/label}" data-bs-original-title="{$fieldname/label}">
											<xsl:value-of select="$fieldname/label"/>
										</a>
									</xsl:when>
									<xsl:otherwise>
										<xsl:value-of select="$fieldname/label"/>
									</xsl:otherwise>
								</xsl:choose>
								<!-- required star -->
								<xsl:if test="$fieldname/@required = 1 and //config/required-star/@value = 1">
									<sup>
										<span class="sp-star">
											<xsl:value-of select="php:function( 'SobiPro::Icon', 'star', $font )" disable-output-escaping="yes"/>
										</span>
										<span class="visually-hidden">
											<xsl:value-of select="php:function( 'SobiPro::TemplateTxt', 'ACCESSIBILITY.REQUIRED' )"/>
										</span>
									</sup>
								</xsl:if>
							</label>
						</xsl:if>

						<!-- help offset -->
						<xsl:variable name="helpoffset">
							<xsl:choose>
								<xsl:when test="$fieldname/label/@show = 0 and string-length( $fieldname/description ) > 0 and $fieldname/description/@position = 'above'">
									<xsl:value-of select="$ofsname"/><xsl:value-of select="$lw"/>
								</xsl:when>
							</xsl:choose>
						</xsl:variable>

						<!-- helptext (description) above (right of label) -->
						<xsl:if test="string-length( $fieldname/description ) and $fieldname/description/@position = 'above'">
							<div class="{$colname}{$fcw} {$helpoffset} form-text text-muted help-block above mb-1">
								<xsl:copy-of select="$fieldname/description/div"/>
							</div>
						</xsl:if>

						<!-- content offset -->
						<xsl:variable name="cob4">
							<xsl:choose>
								<xsl:when
										test="$fieldname/label/@show = 1 and not(string-length( $fieldname/description ) and $fieldname/description/@position = 'above')"/>
								<xsl:otherwise>
									<xsl:value-of select="$ofsname"/><xsl:value-of select="$lw"/>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:variable>

						<!-- content element -->
						<div class="{$colname}{$cw} {$cob4}" id="{$fieldId}-input-container">
							<xsl:if test="contains($fieldname/@css-class,'spClassInfo')"> <!-- is info field -->
								<xsl:attribute name="data-role">
									<xsl:text>content</xsl:text>
								</xsl:attribute>
							</xsl:if>
							<xsl:if test="string-length( $fieldname/description ) > 0">
								<xsl:attribute name="aria-describedby">
									<xsl:value-of select="$fieldId"/><xsl:text>-helpblock</xsl:text>
								</xsl:attribute>
							</xsl:if>
							<xsl:choose>
								<xsl:when test="$fieldname/data/@escaped">
									<xsl:value-of select="$fieldname/data" disable-output-escaping="yes"/>
								</xsl:when>
								<xsl:otherwise>
									<xsl:copy-of select="$fieldname/data/*"/>
								</xsl:otherwise>
							</xsl:choose>
						</div>

						<!-- helptext (description) right -->
						<xsl:if test="string-length( $fieldname/description ) and $fieldname/description/@position = 'right'">
							<div class="{$colname}{number($fcw) - number($cw)} form-text text-muted help-block right">
								<xsl:copy-of select="$fieldname/description/div"/>
							</div>
						</xsl:if>

						<!-- error message container -->
						<div class="{$colname}{$fcw} {$ofsname}{$lw} feedback-container">
							<div id="{$fieldId}-message" class="invalid-feedback"/>
						</div>

						<!-- hint for admin fields -->
						<xsl:if test="$fieldname/@data-administrative = 1">
							<div class="{$colname}{$fcw} {$ofsname}{$lw} form-text text-muted help-block below administrative">
								<xsl:value-of select="php:function( 'SobiPro::TemplateTxt', 'TP.ADMINISTRATIVE', string($fieldname/label))"/>
							</div>
						</xsl:if>

						<!-- helptext (description) below -->
						<xsl:if test="string-length( $fieldname/description ) and $fieldname/description/@position = 'below'">
							<div class="{$colname}{$fcw} {$ofsname}{$lw} form-text text-muted help-block below">
								<xsl:copy-of select="$fieldname/description/div"/>
							</div>
						</xsl:if>
					</div>
				</xsl:when>


				<!-- one column (label above input) -->
				<xsl:otherwise>

					<!-- overall container -->
					<div class="row {$fieldname/@css-edit} mb-3" id="{$fieldId}-container">
						<xsl:call-template name="development">
							<xsl:with-param name="fieldname" select="$fieldname"/>
						</xsl:call-template>

						<!-- label if it should be shown -->
						<xsl:if test="$fieldname/label/@show = 1">
							<label class="{$colname}12 col-form-label" for="{$fieldId}">
								<xsl:choose>
									<!-- if helptext as popover -->
									<xsl:when test="string-length( $fieldname/description ) and $fieldname/description/@position = 'popup'">
										<a href="#" rel="sp-popover" tabindex="0" data-bs-container="#SobiPro" data-bs-trigger="hover" data-bs-placement="top"
										   data-bs-content="{$fieldname/description}"
										   data-bs-title="{$fieldname/label}" data-bs-original-title="{$fieldname/label}">
											<xsl:value-of select="$fieldname/label"/>
										</a>
									</xsl:when>
									<xsl:otherwise>
										<xsl:value-of select="$fieldname/label"/>
									</xsl:otherwise>
								</xsl:choose>
								<!-- required star -->
								<xsl:if test="$fieldname/@required = 1 and //config/required-star/@value = 1">
									<sup>
										<span class="sp-star">
											<xsl:value-of select="php:function( 'SobiPro::Icon', 'star', $font )" disable-output-escaping="yes"/>
										</span>
										<span class="visually-hidden">
											<xsl:value-of select="php:function( 'SobiPro::TemplateTxt', 'ACCESSIBILITY.REQUIRED' )"/>
										</span>
									</sup>
								</xsl:if>
							</label>
						</xsl:if>

						<!-- payment box if not for free -->
						<xsl:if test="string-length( $fieldname/fee ) > 0 and not(contains($fieldname/@css-edit, 'entryprice') and $fieldname/@type = 'chbxgroup' and $fieldname/@required = '1')">
							<div class="{$colname}12">
								<div class="form-check form-switch sp-paybox">
									<input name="{$fieldId}Payment" id="{$fieldId}-payment" value="" type="checkbox" class="spctrl-payment-box form-check-input"/>
									<label class="form-check-label" for="{$fieldId}-payment">
										<xsl:value-of select="$fieldname/fee_msg"/><xsl:text> </xsl:text>
										<xsl:value-of select="php:function( 'SobiPro::TemplateTxt', 'TP.PAYMENT_ADD' )"/>
									</label>
								</div>
							</div>
						</xsl:if>

						<!-- helptext (description) above -->
						<xsl:if test="string-length( $fieldname/description ) and $fieldname/description/@position = 'above'">
							<div class="{$colname}12 form-text text-muted help-block above mb-1" id="{$fieldId}-helpblock">
								<xsl:copy-of select="$fieldname/description/div"/>
							</div>
						</xsl:if>

						<!-- content element -->
						<div class="{$colname}{$cw}" id="{$fieldId}-input-container">
							<xsl:if test="contains($fieldname/@css-class,'spClassInfo')"> <!-- is info field -->
								<xsl:attribute name="data-role">
									<xsl:text>content</xsl:text>
								</xsl:attribute>
							</xsl:if>
							<xsl:if test="string-length( $fieldname/description ) > 0">
								<xsl:attribute name="aria-describedby">
									<xsl:value-of select="$fieldId"/><xsl:text>-helpblock</xsl:text>
								</xsl:attribute>
							</xsl:if>
							<xsl:choose>
								<xsl:when test="$fieldname/data/@escaped">
									<xsl:value-of select="$fieldname/data" disable-output-escaping="yes"/>
								</xsl:when>
								<xsl:otherwise>
									<xsl:copy-of select="$fieldname/data/*"/>
								</xsl:otherwise>
							</xsl:choose>
						</div>

						<!-- helptext (description) right -->
						<xsl:if test="string-length( $fieldname/description ) and $fieldname/description/@position = 'right'">
							<div class="{$colname}{12 - number($cw)} form-text text-muted help-block right" id="{$fieldId}-helpblock">
								<xsl:copy-of select="$fieldname/description/div"/>
							</div>
						</xsl:if>

						<!-- error message container -->
						<div class="{$colname}12 feedback-container">
							<div id="{$fieldId}-message" class="invalid-feedback"/>
						</div>

						<!-- hint for admin fields -->
						<xsl:if test="$fieldname/@data-administrative = 1">
							<div class="{$colname}12 form-text text-muted help-block below administrative">
								<xsl:value-of select="php:function( 'SobiPro::TemplateTxt', 'TP.ADMINISTRATIVE', string($fieldname/label))"/>
							</div>
						</xsl:if>

						<!-- helptext (description) below -->
						<xsl:if test="string-length( $fieldname/description ) and $fieldname/description/@position = 'below'">
							<div class="{$colname}12 form-text text-muted help-block below" id="{$fieldId}-helpblock">
								<xsl:copy-of select="$fieldname/description/div"/>
							</div>
						</xsl:if>
					</div>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:if>
	</xsl:template>
</xsl:stylesheet>
