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
	<xsl:template name="manage">
		<xsl:param name="entry"/>

		<!-- no edit or manage buttons if entry is expired -->
		<xsl:if test=" not($entry/state = 'expired') and ($entry/approve_url or $entry/edit_url or $entry/publish_url or $entry/delete_url)">
			<xsl:variable name="arialabel">
				<xsl:value-of select="php:function( 'SobiPro::TemplateTxt', 'ACCESSIBILITY.FE.ENTRY_MANAGEMENT' )"/>
			</xsl:variable>

			<div class="dropdown sp-manage">
				<button class="btn btn-beta btn-sm dropdown-toggle" type="button" id="sp-manage-dropdown" data-bs-toggle="dropdown" aria-haspopup="true"
				        aria-expanded="false" aria-label="{$arialabel}">
					<xsl:value-of select="php:function( 'SobiPro::Icon', 'edit', $font )" disable-output-escaping="yes"/>
				</button>
				<div class="dropdown-menu dropdown-beta" aria-labelledby="sp-manage-dropdown">
					<xsl:if test="$entry/publish_url">
						<a href="{$entry/publish_url}" class="dropdown-item">
							<xsl:choose>
								<xsl:when test="$entry/state = 'published'">
									<xsl:value-of select="php:function( 'SobiPro::TemplateTxt', 'ENTRY_MANAGE_DISABLE' )"/>
								</xsl:when>
								<xsl:otherwise>
									<xsl:value-of select="php:function( 'SobiPro::TemplateTxt', 'ENTRY_MANAGE_ENABLE' )"/>
								</xsl:otherwise>
							</xsl:choose>
						</a>
					</xsl:if>
					<xsl:if test="$entry/approve_url and $entry/approved = 0">
						<a href="{$entry/approve_url}" class="dropdown-item">
							<xsl:value-of select="php:function( 'SobiPro::TemplateTxt', 'ENTRY_MANAGE_APPROVE' )"/>
						</a>
					</xsl:if>
					<xsl:if test="$entry/edit_url">
						<a href="{$entry/edit_url}" class="dropdown-item">
							<xsl:value-of select="php:function( 'SobiPro::TemplateTxt', 'ENTRY_MANAGE_EDIT' )"/>
						</a>
					</xsl:if>
					<xsl:if test="$entry/delete_url">
						<a href="{$entry/delete_url}" id="spctrl-delete-entry" class="dropdown-item">
							<xsl:value-of select="php:function( 'SobiPro::TemplateTxt', 'ENTRY_MANAGE_DELETE' )"/>
						</a>
					</xsl:if>
				</div>
			</div>
		</xsl:if>

	</xsl:template>

	<xsl:template name="status">
		<xsl:param name="entry"/>

		<xsl:if test="$entry/approved = 0">
			<a tabindex="0" type="button" class="sp-entry-status" data-bs-toggle="popover" data-bs-trigger="hover"
			   data-bs-content="{php:function( 'SobiPro::TemplateTxt', 'ENTRY_STATUS_UNAPPROVED' )}" title="" data-bs-container="#SobiPro" data-sp-toggle="popover">
				<xsl:value-of select="php:function( 'SobiPro::Icon', 'thumbs-down', $font )" disable-output-escaping="yes"/>
				<span class="visually-hidden">
					<xsl:value-of select="php:function( 'SobiPro::TemplateTxt', 'ENTRY_STATUS_UNAPPROVED' )"/>
				</span>
			</a>
		</xsl:if>
		<xsl:if test="$entry/state = 'unpublished'">
			<a tabindex="0" type="button" class="sp-entry-status" data-bs-toggle="popover" data-bs-trigger="hover"
			   data-bs-content="{php:function( 'SobiPro::TemplateTxt', 'ENTRY_STATUS_UNPUBLISHED' )}" title="" data-bs-container="#SobiPro" data-sp-toggle="popover">
				<xsl:value-of select="php:function( 'SobiPro::Icon', 'remove-circle', $font )" disable-output-escaping="yes"/>
				<span class="visually-hidden">
					<xsl:value-of select="php:function( 'SobiPro::TemplateTxt', 'ENTRY_STATUS_UNPUBLISHED' )"/>
				</span>
			</a>
		</xsl:if>
		<xsl:if test="$entry/state = 'expired'">
			<a tabindex="0" type="button" class="sp-entry-status" data-bs-toggle="popover" data-bs-trigger="hover"
			   data-bs-content="{php:function( 'SobiPro::TemplateTxt', 'ENTRY_STATUS_EXPIRED' )}" title="" data-bs-container="#SobiPro" data-sp-toggle="popover">
				<xsl:value-of select="php:function( 'SobiPro::Icon', 'exclamation-triangle', $font )" disable-output-escaping="yes"/>
				<span class="visually-hidden">
					<xsl:value-of select="php:function( 'SobiPro::TemplateTxt', 'ENTRY_STATUS_EXPIRED' )"/>
				</span>
			</a>
		</xsl:if>
	</xsl:template>
</xsl:stylesheet>