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

 @modified 20 August 2024 by Sigrid Suski
-->

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">
	<xsl:output method="xml" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" encoding="UTF-8"/>

	<xsl:template name="showfield">
		<xsl:param name="fieldname"/>
		<xsl:param name="view"/>
		<xsl:choose>
			<xsl:when test="count($fieldname/data/*) or string-length($fieldname/data) > 0">
				<div>
					<xsl:call-template name="development">
						<xsl:with-param name="fieldname" select="$fieldname"/>
					</xsl:call-template>

					<xsl:if test="string-length($fieldname/@css-view) > 0">
						<xsl:attribute name="class">
							<xsl:value-of select="$fieldname/@css-view"/>
							<xsl:text> sp-entry-row</xsl:text>
						</xsl:attribute>
					</xsl:if>

					<xsl:variable name="suffix">
						<xsl:if test="string-length($fieldname/@suffix) > 0"> <!-- suffix -->
							<xsl:text> </xsl:text>
							<xsl:choose>
								<xsl:when test="$view = 'dv'">
									<span class="sp-entry-suffix sp-detail-suffix">
										<xsl:value-of select="$fieldname/@suffix"/>
									</span>
								</xsl:when>
								<xsl:otherwise>
									<span class="sp-entry-suffix">
										<xsl:value-of select="$fieldname/@suffix"/>
									</span>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:if>
					</xsl:variable>

					<xsl:choose>
						<xsl:when test="count($fieldname/data/*) > 0">  <!-- complex data -->
							<xsl:if test="string-length($fieldname/@itemprop) > 0"> <!-- itemprop attached to div container -->
								<xsl:attribute name="itemprop">
									<xsl:value-of select="$fieldname/@itemprop"/>
								</xsl:attribute>
							</xsl:if>
							<xsl:if test="$fieldname/label/@show = 1"> <!-- field label -->
								<span class="sp-entry-label">
									<!-- for Font Awesome 5 and 6 only: add icon before label via CSS class -->
									<xsl:if test="contains($fieldname/@css-class, 'label-')">
										<xsl:variable name="icon" select="substring-after($fieldname/@css-class,'label-')"/>
										<span class="fas fa-{$icon} "/>
										<xsl:text> </xsl:text>
									</xsl:if>
									<xsl:value-of select="$fieldname/label"/><xsl:text>: </xsl:text>
								</span>
							</xsl:if>
							<div class="sp-entry-value">
								<xsl:choose>
									<xsl:when test="contains($fieldname/@css-class,'primary') and $view = 'vcard' and $fieldname/@type = 'image'">
										<a href="{../../url}">
											<xsl:copy-of select="$fieldname/data/*"/>
										</a>
									</xsl:when>
									<xsl:otherwise>
										<xsl:choose>
											<xsl:when test="string-length($suffix) > 0 and ($fieldname/@type = 'multiselect' or $fieldname/@type = 'chbxgroup')">
												<ul>
													<xsl:for-each select="$fieldname/data/ul/li">
														<li class="{@class}">
															<xsl:value-of select="."/>
															<xsl:copy-of select="$suffix"/>
														</li>
													</xsl:for-each>
												</ul>
											</xsl:when>
											<xsl:otherwise>
												<xsl:copy-of select="$fieldname/data/*"/>
												<xsl:copy-of select="$suffix"/>
											</xsl:otherwise>
										</xsl:choose>
									</xsl:otherwise>
								</xsl:choose>
							</div>
						</xsl:when>
						<xsl:otherwise> <!-- no complex data -->
							<xsl:if test="string-length($fieldname/data) > 0">
								<xsl:choose>
									<xsl:when test="contains($fieldname/@css-class,'spClassText')"> <!-- is textarea -->
										<xsl:attribute name="data-role">
											<xsl:text>content</xsl:text>
										</xsl:attribute>
										<xsl:if test="string-length($fieldname/@itemprop) > 0"> <!-- itemprop attached to div container -->
											<xsl:attribute name="itemprop">
												<xsl:value-of select="$fieldname/@itemprop"/>
											</xsl:attribute>
										</xsl:if>
										<xsl:if test="$fieldname/label/@show = 1"> <!-- field label -->
											<span class="sp-entry-label">
												<!-- for Font Awesome 5 and 6 only: add icon before label via CSS class -->
												<xsl:if test="contains($fieldname/@css-class, 'label-')">
													<xsl:variable name="icon" select="substring-after($fieldname/@css-class,'label-')"/>
													<span class="fas fa-{$icon} "/>
													<xsl:text> </xsl:text>
												</xsl:if>
												<xsl:value-of select="$fieldname/label"/>
												<xsl:text>: </xsl:text>
											</span>
										</xsl:if>
										<div class="sp-entry-value">
											<xsl:choose>
												<xsl:when
														test="contains($fieldname/@css-view,'shorten') and ($view = 'vcard') and //config/textlength/@value != 'no'">
													<xsl:value-of select="substring ($fieldname/data,1,//config/textlength/@value)"
													              disable-output-escaping="yes"/>...
												</xsl:when>
												<xsl:otherwise>
													<xsl:value-of select="$fieldname/data" disable-output-escaping="yes"/>
												</xsl:otherwise>
											</xsl:choose>
										</div>
									</xsl:when>
									<xsl:otherwise> <!-- no textarea -->
										<xsl:if test="contains($fieldname/@css-class,'spClassInfo')"> <!-- is info field -->
											<xsl:attribute name="data-role">
												<xsl:text>content</xsl:text>
											</xsl:attribute>
										</xsl:if>
										<xsl:if test="$fieldname/label/@show = 1"> <!-- field label -->
											<span class="sp-entry-label">
												<!-- for Font Awesome 5 and 6 only: add icon before label via CSS class -->
												<xsl:if test="contains($fieldname/@css-class, 'label-')">
													<xsl:variable name="icon" select="substring-after($fieldname/@css-class,'label-')"/>
													<span class="fas fa-{$icon} "/>
													<xsl:text> </xsl:text>
												</xsl:if>
												<xsl:value-of select="$fieldname/label"/>
												<xsl:text>:</xsl:text>
											</span>
										</xsl:if>
										<span class="sp-entry-value">  <!-- add surrounding span -->
											<xsl:if test="string-length($fieldname/@itemprop) > 0"> <!-- attach itemprop to span -->
												<xsl:attribute name="itemprop">
													<xsl:value-of select="$fieldname/@itemprop"/>
												</xsl:attribute>
											</xsl:if>
											<xsl:value-of select="$fieldname/data" disable-output-escaping="yes"/>
											<xsl:copy-of select="$suffix"/>
										</span>
									</xsl:otherwise>
								</xsl:choose>
							</xsl:if>
						</xsl:otherwise>
					</xsl:choose>
				</div>
			</xsl:when>
			<xsl:otherwise>
				<xsl:if test="$view != 'category'">
					<xsl:if test="$fieldname/@type = 'image'">
						<xsl:if test="//config/noimage/@value > 0">
							<xsl:variable name="floatvalue">
								<xsl:choose>
									<xsl:when test="$view = 'vcard' or $view = 'module'">
										<xsl:value-of select="//config/noimage-floatvc/@value"/>
									</xsl:when>
									<xsl:otherwise>
										<xsl:value-of select="//config/noimage-floatdv/@value"/>
									</xsl:otherwise>
								</xsl:choose>
							</xsl:variable>
							<div class="sp-noimage-container {//config/noimage-type/@value} {$fieldname/@css-view} sp-entry-row" style="float:{$floatvalue}">
								<xsl:call-template name="development">
									<xsl:with-param name="fieldname" select="$fieldname"/>
								</xsl:call-template>
								<xsl:choose>
									<xsl:when test="(//config/noimage/@value = 1) or (//config/noimage/@value = 2 and contains($fieldname/@css-view,'placeholder'))">
										<xsl:choose>
											<xsl:when test="//config/noimage-type/@value = 'css'">
												<div class="sp-noimage css">
													<xsl:value-of select="php:function( 'SobiPro::Icon', 'ban', $font )" disable-output-escaping="yes"/>
												</div>
											</xsl:when>
											<xsl:otherwise>
												<div class="sp-noimage picture">
													<img src="{//template_path}images/{//config/noimage-img/@value}" alt="No image available" style="float:{$floatvalue}"/>
												</div>
											</xsl:otherwise>
										</xsl:choose>
									</xsl:when>
								</xsl:choose>
							</div>
						</xsl:if>
					</xsl:if>
				</xsl:if>
			</xsl:otherwise>
		</xsl:choose>
		<xsl:text>&#xa;</xsl:text>
	</xsl:template>
</xsl:stylesheet>
