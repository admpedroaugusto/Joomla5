<?xml version="1.0" encoding="UTF-8"?><!--
 @package: Default Template V7 for SobiPro multi-directory component with content construction support

 @author
 Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 Email: sobi[at]sigsiu.net
 Url: https://www.Sigsiu.NET

 @copyright Copyright (C) 2006 - 2022 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 @license GNU/GPL Version 3
 This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 3
 as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 See https://www.gnu.org/licenses/gpl.html and https://www.sigsiu.net/licenses.

 This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

 @modified 06 June 2022 by Sigrid Suski
-->

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl" xmlns:xls="http://www.w3.org/1999/XSL/Transform"
                exclude-result-prefixes="php">
	<xsl:output method="xml" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" encoding="UTF-8"/>

	<xsl:include href="../common/globals.xsl"/> <!-- do not comment or remove -->
	<xsl:include href="../common/topmenu.xsl"/>
	<xsl:include href="../common/alphamenu.xsl"/>
	<xsl:include href="../common/navigation.xsl"/>
	<xsl:include href="../common/entries.xsl"/>
	<xsl:include href="../common/messages.xsl"/>
	<xsl:include href="../common/searchfields.xsl"/>
	<xsl:include href="../common/sortsearch.xsl"/>

	<xsl:template match="/search">
		<!-- for proper work a container is needed, we assume that the component area is placed into a Bootstrap container by the template.
		If not, you need to add a container around the SobiPro output here -->
		<div class="sp-search">
			<xsl:call-template name="topMenu">
				<xsl:with-param name="searchbox">false</xsl:with-param>
				<xsl:with-param name="title">
					<xsl:value-of select="name"/>
				</xsl:with-param>
			</xsl:call-template>
			<xsl:apply-templates select="messages"/>

			<xsl:if test="string-length(description) > 0">
				<div class="sp-search-description">
					<xsl:value-of select="description" disable-output-escaping="yes"/>
				</div>
			</xsl:if>

			<!-- collapse attribute -->
			<xsl:variable name="collapse-in">
				<xsl:choose>
					<xsl:when test="$bs >= 4">show</xsl:when>
					<xsl:otherwise>in</xsl:otherwise>
				</xsl:choose>
			</xsl:variable>

			<xsl:variable name="sparam">
				<xsl:choose>
					<xsl:when test="//config/hidesearch/@value = '1'">
						<xsl:value-of select="php:function( 'SobiPro::Request', 'sparam' )"/>
					</xsl:when>
					<xsl:otherwise>
						<xsl:value-of select="$collapse-in"/>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:variable>

			<!-- form class name -->
			<xsl:variable name="formclass">
				<xsl:if test="$bs &lt; 4">
					<xsl:text>form-horizontal </xsl:text>
				</xsl:if>
				<xsl:value-of select="//config/columns-search/@value"/>
			</xsl:variable>

			<div class="collapse {$sparam}" id="spctrl-search-area" role="search">
				<div id="spctrl-search-form-container" class="sp-search-form {$formclass}">
					<xsl:if test="/search/fields/searchbox">
						<xsl:call-template name="searchbox"/>
					</xsl:if>

					<!-- extended search fields -->
					<xsl:if test="count( /search/fields/* ) &gt; 3">
						<div class="sp-search-fields">
							<xsl:if test="//config/extendedsearch/@value = '1'">
								<xsl:attribute name="id">spctrl-extended-search</xsl:attribute>
							</xsl:if>
							<xsl:for-each select="fields/*">
								<xsl:if test="position() &gt; 3">
									<xsl:call-template name="searchfield">
										<xsl:with-param name="fieldname" select="."/>
									</xsl:call-template>
								</xsl:if>
							</xsl:for-each>
						</div>
					</xsl:if>
				</div>
			</div>

			<!-- extra buttons if complete search is hide-able -->
			<xsl:if test="//config/hidesearch/@value = '1'">
				<xsl:call-template name="bottomline">
					<xsl:with-param name="sparam" select="$sparam"/>
					<xsl:with-param name="collapse-in" select="$collapse-in"/>
				</xsl:call-template>
			</xsl:if>

			<!-- results area -->
			<div class="sp-listing search" data-category="{section/@id}">
				<!-- results message and ordering button -->
				<xsl:if test="message">
					<div class="sp-results-message">
						<span class="result">
							<xsl:value-of select="message"/>
						</span>
						<xsl:if test="search_order != 'priority'">
							<xsl:apply-templates select="orderingMenu"/>
						</xsl:if>
					</div>
				</xsl:if>

				<!-- search results -->
				<div class="sp-entries-container" id="spctrl-entry-container">
					<xsl:call-template name="entriesLoop"/>
				</div>
				<xsl:apply-templates select="navigation"/>

				<xsl:call-template name="bottomHook"/>
			</div>
		</div>
	</xsl:template>


	<!-- Template: SEARCHBOX -->
	<!-- search keywords input box, buttons and search phrases -->
	<xsl:template name="searchbox">

		<!-- column name and width -->
		<xsl:variable name="colname">
			<xsl:choose>
				<xsl:when test="$bs > 2">
					<xsl:text>col-</xsl:text>
					<xsl:if test="not(//config/grid-search/@value = 'xs' and $bs > 3)">
						<xsl:value-of select="//config/grid-search/@value"/>
						<xsl:text>-</xsl:text>
					</xsl:if>
				</xsl:when>
				<xsl:otherwise>span</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<xsl:choose>
			<!-- Bootstrap 5 -->
			<xsl:when test="$bs = 5">
				<div class="row mb-3">
					<label class="{$colname}12 col-form-label" for="spctrl-searchbox">
						<xsl:value-of select="/search/fields/searchbox/label"/>
					</label>

					<div class="{$colname}12 sp-search-top d-md-flex">
						<input type="text" name="sp_search_for" value="{/search/fields/searchbox/data/input/@value}"
						       class="form-control search-query" autocomplete="off" id="spctrl-searchbox"
						       placeholder="{php:function( 'SobiPro::Txt', 'SH.SEARCH_FOR_BOX' )}"/>
						<xsl:if test="/search/fields/top_button/label">
							<button type="submit" class="btn btn-delta">
								<xsl:text>&#160;</xsl:text>
								<xsl:value-of select="/search/fields/top_button/label"/>
							</button>
						</xsl:if>
						<xsl:if test="count( /search/fields/* ) &gt; 3 and //config/extendedsearch/@value = '1'">
							<button type="button" class="btn btn-secondary" name="spctrl-extended-options-btn"
							        id="spctrl-extended-options-btn">
								<xsl:value-of select="php:function( 'SobiPro::Txt', 'EXTENDED_SEARCH' )"/>
							</button>
						</xsl:if>
					</div>
				</div>
				<!-- search phrases -->
				<xsl:if test="count(/search/fields/phrase/*)">
					<div class="row mb-3">
						<label class="{$colname}12 col-form-label" for="sp-searchphrases">
							<xsl:value-of select="/search/fields/phrase/label"/>
						</label>
						<div class="{$colname}12 sp-search-phrases d-md-flex" id="sp-searchphrases">
							<div class="btn-group" role="group" aria-label="{php:function( 'SobiPro::Txt', 'ACCESSIBILITY.FE.FIND_ENTRIES_THAT_HAVE' )}"
							     id="spsearchphrase">
								<xsl:for-each select="/search/fields/phrase/data/*">
									<xsl:copy-of select="./input"/>
									<label class="spctrl-phrase btn btn-outline-beta">
										<xsl:attribute name="for">
											<xsl:value-of select="./label/@for"/>
										</xsl:attribute>
										<xsl:value-of select="./label"/>
									</label>
								</xsl:for-each>
							</div>
						</div>
					</div>
				</xsl:if>
			</xsl:when>

			<!-- Bootstrap 4 -->
			<xsl:when test="$bs = 4">
				<div class="form-group row">
					<label class="{$colname}12 col-form-label" for="spctrl-searchbox">
						<xsl:value-of select="/search/fields/searchbox/label"/>
					</label>
					<div class="{$colname}12 sp-search-top d-md-flex">
						<input type="text" name="sp_search_for" value="{/search/fields/searchbox/data/input/@value}"
						       class="form-control search-query" autocomplete="off" id="spctrl-searchbox"
						       placeholder="{php:function( 'SobiPro::Txt', 'SH.SEARCH_FOR_BOX' )}"/>
						<xsl:if test="/search/fields/top_button/label">
							<button type="submit" class="btn btn-delta">
								<xsl:text>&#160;</xsl:text>
								<xsl:value-of select="/search/fields/top_button/label"/>
							</button>
						</xsl:if>
						<xsl:if test="count( /search/fields/* ) &gt; 3 and //config/extendedsearch/@value = '1'">
							<button type="button" class="btn btn-secondary" name="spctrl-extended-options-btn" id="spctrl-extended-options-btn">
								<xsl:value-of select="php:function( 'SobiPro::Txt', 'EXTENDED_SEARCH' )"/>
							</button>
						</xsl:if>
					</div>
				</div>
				<!-- search phrases -->
				<xsl:if test="count(/search/fields/phrase/*)">
					<div class="form-group row">
						<label class="{$colname}12 col-form-label" for="sp-searchphrases">
							<xsl:value-of select="/search/fields/phrase/label"/>
						</label>
						<div class="{$colname}12 sp-search-phrases" id="sp-searchphrases">
							<div class="btn-group btn-group-toggle" role="group"
							     aria-label="{php:function( 'SobiPro::Txt', 'ACCESSIBILITY.FE.FIND_ENTRIES_THAT_HAVE' )}" data-toggle="buttons">
								<xsl:for-each select="/search/fields/phrase/data/*">
									<label class="spctrl-phrase btn btn-beta">
										<xsl:attribute name="for">
											<xsl:value-of select="./label/@for"/>
										</xsl:attribute>
										<xsl:copy-of select="./input"/>
										<xsl:value-of select="./label"/>
									</label>
								</xsl:for-each>
							</div>
						</div>
					</div>
				</xsl:if>
			</xsl:when>

			<!-- Bootstrap 3 -->
			<xsl:when test="$bs = 3">
				<div class="form-group onecolumn">
					<label class="{$colname}12 control-label" for="spctrl-searchbox">
						<xsl:value-of select="/search/fields/searchbox/label"/>
					</label>

					<div class="{$colname}12 sp-search-top d-md-flex">
						<input type="text" name="sp_search_for" value="{/search/fields/searchbox/data/input/@value}"
						       class="form-control search-query" autocomplete="off" id="spctrl-searchbox"
						       placeholder="{php:function( 'SobiPro::Txt', 'SH.SEARCH_FOR_BOX' )}"/>
						<xsl:if test="/search/fields/top_button/label">
							<button type="submit" class="btn btn-delta">
								<xsl:text>&#160;</xsl:text>
								<xsl:value-of select="/search/fields/top_button/label"/>
							</button>
						</xsl:if>
						<xsl:if test="count( /search/fields/* ) &gt; 3 and //config/extendedsearch/@value = '1'">
							<button type="button" class="btn btn-alpha" name="spctrl-extended-options-btn" id="spctrl-extended-options-btn">
								<xsl:value-of select="php:function( 'SobiPro::Txt', 'EXTENDED_SEARCH' )"/>
							</button>
						</xsl:if>
					</div>
				</div>
				<!-- search phrases -->
				<xsl:if test="count(/search/fields/phrase/*) > 0">
					<div class="form-group onecolumn">
						<label class="{$colname}12 control-label" for="sp-searchphrases">
							<xsl:value-of select="/search/fields/phrase/label"/>
						</label>
						<div class="{$colname}12 sp-search-phrases" id="sp-searchphrases">
							<div class="btn-group" data-toggle="buttons">
								<xsl:for-each select="/search/fields/phrase/data/*">
									<label class="spctrl-phrase btn btn-beta">
										<xsl:if test="./label/input/@checked = 'checked'">
											<xsl:attribute name="class">spctrl-phrase btn btn-beta active</xsl:attribute>
										</xsl:if>
										<xsl:attribute name="for">
											<xsl:value-of select="./label/@for"/>
										</xsl:attribute>
										<xsl:copy-of select="./label/input"/>
										<xsl:value-of select="./label"/>
									</label>
								</xsl:for-each>
							</div>
						</div>
					</div>
				</xsl:if>
			</xsl:when>

			<!-- Bootstrap 2 -->
			<xsl:otherwise>
				<div class="control-group row-fluid onecolumn">
					<label class="span12 control-label" for="spctrl-searchbox">
						<xsl:value-of select="/search/fields/searchbox/label"/>
					</label>
					<div class="clearfix"/>

					<div class="span12 sp-search-top d-md-flex">
						<input type="text" name="sp_search_for" value="{/search/fields/searchbox/data/input/@value}"
						       class="form-control search-query" autocomplete="off" id="spctrl-searchbox"
						       placeholder="{php:function( 'SobiPro::Txt', 'SH.SEARCH_FOR_BOX' )}"/>
						<xsl:if test="/search/fields/top_button/label">
							<button type="submit" class="btn btn-delta">
								<xsl:text>&#160;</xsl:text>
								<xsl:value-of select="/search/fields/top_button/label"/>
							</button>
						</xsl:if>
						<xsl:if test="count( /search/fields/* ) &gt; 3 and //config/extendedsearch/@value = '1'">
							<button type="button" class="btn btn-alpha" name="spctrl-extended-options-btn" id="spctrl-extended-options-btn">
								<xsl:value-of select="php:function( 'SobiPro::Txt', 'EXTENDED_SEARCH' )"/>
							</button>
						</xsl:if>
					</div>
				</div>
				<!-- search phrases -->
				<xsl:if test="count(/search/fields/phrase/*) > 0">
					<div class="control-group row-fluid onecolumn">
						<label class="span12 control-label" for="sp-searchphrases">
							<xsl:value-of select="/search/fields/phrase/label"/>
						</label>
						<div class="span12 sp-search-phrases" id="sp-searchphrases">
							<div class="btn-group" data-toggle="buttons-radio">
								<xsl:for-each select="/search/fields/phrase/data/*">
									<label class="spctrl-phrase btn btn-beta">
										<xsl:if test="./label/input/@checked = 'checked'">
											<xsl:attribute name="class">spctrl-phrase btn btn-beta active</xsl:attribute>
										</xsl:if>
										<xsl:attribute name="for">
											<xsl:value-of select="./label/@for"/>
										</xsl:attribute>
										<xsl:copy-of select="./label/input"/>
										<xsl:value-of select="./label"/>
									</label>
								</xsl:for-each>
							</div>
						</div>
					</div>
				</xsl:if>

			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<!-- Template: BOTTOMLINE -->
	<!-- refine search button line after the fields  -->
	<xsl:template name="bottomline">
		<xsl:param name="sparam"/>
		<xsl:param name="collapse-in"/>

		<!-- column name and width -->
		<xsl:variable name="colname">
			<xsl:choose>
				<xsl:when test="$bs > 2">
					<xsl:text>col-</xsl:text>
					<xsl:if test="not(//config/grid-search/@value = 'xs' and $bs > 3)">
						<xsl:value-of select="//config/grid-search/@value"/>
						<xsl:text>-</xsl:text>
					</xsl:if>
				</xsl:when>
				<xsl:otherwise>span</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<xsl:choose>
			<!-- Bootstrap 5 -->
			<xsl:when test="$bs = 5">
				<div class="row mb-3">
					<div class="{$colname}12 sp-search-bottom d-md-flex">
						<xsl:choose>
							<!-- search is visible -->
							<xsl:when test="$sparam = 'show'">
								<button class="btn btn-alpha search-parameters" id="spctrl-search-area-btn" data-bs-toggle="collapse" data-bs-target="#spctrl-search-area"
								        aria-expanded="true" aria-controls="spctrl-search-area" data-visible="true" type="button">
									<xsl:value-of select="php:function( 'SobiPro::Txt', 'TP.SEARCH_HIDE' )"/>
								</button>
								<button type="submit" id="spctrl-bottom-button" class="btn btn-delta bottom-search">
									<xsl:text>&#160;</xsl:text>
									<xsl:value-of select="/search/fields/top_button/label"/>
								</button>
							</xsl:when>
							<!--search is hidden -->
							<xsl:otherwise>
								<button class="btn btn-alpha search-parameters" id="spctrl-search-area-btn" data-bs-toggle="collapse" data-bs-target="#spctrl-search-area"
								        aria-expanded="false" aria-controls="spctrl-search-area" data-visible="false" type="button">
									<xsl:value-of select="php:function( 'SobiPro::Txt', 'TP.SEARCH_REFINE' )"/>
								</button>
								<button type="submit" id="spctrl-bottom-button" class="btn btn-delta bottom-search" style="display:none">
									<xsl:text>&#160;</xsl:text>
									<xsl:value-of select="/search/fields/top_button/label"/>
								</button>
							</xsl:otherwise>
						</xsl:choose>
					</div>
				</div>
			</xsl:when>

			<!-- Bootstrap 4 -->
			<xsl:when test="$bs = 4">
				<div class="form-group row">
					<div class="{$colname}12 sp-search-bottom d-md-flex">
						<xsl:choose>
							<!-- search is visible -->
							<xsl:when test="$sparam = 'show'">
								<button class="btn btn-alpha search-parameters" id="spctrl-search-area-btn" data-toggle="collapse" data-target="#spctrl-search-area"
								        aria-expanded="true" aria-controls="spctrl-search-area" data-visible="true" type="button">
									<xsl:value-of select="php:function( 'SobiPro::Txt', 'TP.SEARCH_HIDE' )"/>
									<button type="submit" id="spctrl-bottom-button" class="btn btn-delta bottom-search">
										<xsl:text>&#160;</xsl:text>
										<xsl:value-of select="/search/fields/top_button/label"/>
									</button>
								</button>
							</xsl:when>
							<!--search is hidden -->
							<xsl:otherwise>
								<button class="btn btn-alpha search-parameters" id="spctrl-search-area-btn" data-toggle="collapse" data-target="#spctrl-search-area"
								        aria-expanded="false" aria-controls="spctrl-search-area" data-visible="false" type="button">
									<xsl:value-of select="php:function( 'SobiPro::Txt', 'TP.SEARCH_REFINE' )"/>
								</button>
								<button type="submit" id="spctrl-bottom-button" class="btn btn-delta bottom-search" style="display:none">
									<xsl:text>&#160;</xsl:text>
									<xsl:value-of select="/search/fields/top_button/label"/>
								</button>
							</xsl:otherwise>
						</xsl:choose>
					</div>
				</div>
			</xsl:when>

			<!-- Bootstrap 2, 3 -->
			<xsl:otherwise>
				<xsl:variable name="row">
					<xsl:choose>
						<xsl:when test="$bs = 2">row-fluid</xsl:when>
						<xsl:otherwise>row</xsl:otherwise>
					</xsl:choose>
				</xsl:variable>
				<div class="form-group {$row}">
					<div class="{$colname}12 sp-search-bottom">
						<xsl:choose>
							<!-- search is visible -->
							<xsl:when test="$sparam = 'in'">
								<button class="btn btn-alpha search-parameters" id="spctrl-search-area-btn" data-toggle="collapse" data-target="#spctrl-search-area"
								        aria-expanded="true" aria-controls="spctrl-search-area" data-visible="true" type="button">
									<xsl:value-of select="php:function( 'SobiPro::Txt', 'TP.SEARCH_HIDE' )"/>
								</button>
								<button type="submit" id="spctrl-bottom-button" class="btn btn-delta bottom-search">
									<xsl:text>&#160;</xsl:text>
									<xsl:value-of select="/search/fields/top_button/label"/>
								</button>
							</xsl:when>
							<!--search is hidden -->
							<xsl:otherwise>
								<button class="btn btn-alpha search-parameters collapsed" id="spctrl-search-area-btn" data-toggle="collapse" data-target="#spctrl-search-area"
								        aria-expanded="false" aria-controls="spctrl-search-area" data-visible="false" type="button">
									<xsl:value-of select="php:function( 'SobiPro::Txt', 'TP.SEARCH_REFINE' )"/>
								</button>
								<button type="submit" id="spctrl-bottom-button" class="btn btn-delta bottom-search" style="display:none">
									<xsl:text>&#160;</xsl:text>
									<xsl:value-of select="/search/fields/top_button/label"/>
								</button>
							</xsl:otherwise>
						</xsl:choose>
					</div>
				</div>
			</xsl:otherwise>
		</xsl:choose>

	</xsl:template>
</xsl:stylesheet>
