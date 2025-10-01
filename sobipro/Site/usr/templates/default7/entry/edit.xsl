<?xml version="1.0" encoding="UTF-8"?><!--
 @package Default Template V7 for SobiPro multi-directory component with content construction support

 @author
 Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 Url: https://www.Sigsiu.NET

 @copyright Copyright (C) 2006 - 2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 @license GNU/GPL Version 3
 This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 3
 as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 See https://www.gnu.org/licenses/gpl.html and https://www.sigsiu.net/licenses.

 This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

 @modified 08 February 2024 by Sigrid Suski
-->

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">
	<xsl:output method="xml" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" encoding="UTF-8"/>

	<xsl:include href="../common/globals.xsl"/> <!-- do not comment or remove -->
	<xsl:include href="../common/topmenu.xsl"/>
	<xsl:include href="../common/messages.xsl"/>
	<xsl:include href="../common/editfields.xsl"/>

	<xsl:template match="/entry_form">
		<xsl:if test="//config/recaptcha/@value = 1">
			<xsl:value-of select="php:function( 'tplDefault7::setCaptcha' )"/>
		</xsl:if>
		<div class="sp-entry-edit" novalidate="novalidate">
			<xsl:call-template name="topMenu">
				<xsl:with-param name="searchbox">false</xsl:with-param>
				<xsl:with-param name="title">
					<xsl:value-of select="name"/>
				</xsl:with-param>
			</xsl:call-template>
			<xsl:apply-templates select="messages"/>

			<!-- Snippet for the field highlighting feature. Do not remove or change it! -->
			<xsl:variable name="development">
				<xsl:if test="//config/development/@value = 1">
					<xsl:text>development</xsl:text>
				</xsl:if>
			</xsl:variable>

			<xsl:if test="string-length(description) > 0">
				<div class="sp-edit-description">
					<xsl:value-of select="description" disable-output-escaping="yes"/>
				</div>
			</xsl:if>

			<xsl:variable name="formclass">
				<xsl:if test="$bs &lt; 4">
					<xsl:text>form-horizontal </xsl:text>
				</xsl:if>
				<xsl:value-of select="//config/columns-edit/@value"/>
			</xsl:variable>

			<div class="{$formclass} mt-3">
				<xsl:for-each select="entry/fields/*">
					<xsl:call-template name="editfield">
						<xsl:with-param name="fieldname" select="."/>
					</xsl:call-template>
				</xsl:for-each>

				<!-- reCaptcha: please adapt the column widths to your needs -->
				<xsl:if test="//config/recaptcha/@value = 1">
					<div id="field-g-recaptcha-container" class="row mb-4">
						<div id="field-g-recaptcha" class="col-lg-6 offset-lg-3 g-recaptcha" data-sitekey="{//config/republic/@value}" data-theme="dark"/>
						<div id="field-g-recaptcha-message" class="col-lg-9 offset-lg-3 invalid-feedback"/>
					</div>
				</xsl:if>
			</div>
			<div class="clearfix"/>

			<xsl:if test="//config/required-star/@value = 1">
				<div class="sp-required-message">
					<sup>
						<span class="sp-star">
							<xsl:value-of select="php:function( 'SobiPro::Icon', 'star', $font )" disable-output-escaping="yes"/>
						</span>
					</sup>
					<xsl:value-of select="php:function( 'SobiPro::Txt', 'ENTRY_REQUIRED_MESSAGE' )"/>
				</div>
			</xsl:if>

			<xsl:variable name="efb-position">
				<xsl:if test="//config/efb-position/@value = 1">
					<xsl:text>sp-fixed</xsl:text>
				</xsl:if>
			</xsl:variable>
			<div class="sp-editform-buttons {$efb-position}">
				<button class="btn btn-beta spctrl-cancel" type="button">
					<xsl:value-of select="entry/fields/cancel_button/data/button"/>
				</button>
				<button class="btn btn-delta spctrl-submit" type="button" data-loading-text="Loading...">
					<xsl:value-of select="entry/fields/save_button/data/input/@value"/>
				</button>
			</div>
			<div class="clearfix"/>

			<xsl:call-template name="bottomHook"/>
		</div>

		<input type="hidden" name="method" value="xhr"/>
		<input type="hidden" name="format" value="raw"/>
	</xsl:template>
</xsl:stylesheet>
