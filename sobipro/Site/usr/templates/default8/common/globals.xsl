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
	<xsl:include href="font.xsl"/>

	<xsl:variable name="bs">
		<xsl:value-of select="//config/bs/@value"/>
	</xsl:variable>
	<xsl:variable name="font">
		<xsl:value-of select="//config/font/@value"/>
	</xsl:variable>

	<xsl:template name="development">
		<xsl:param name="fieldname"/>
		<xsl:if test="//config/development/@value = 1">
			<xsl:attribute name="title">
				<xsl:value-of select="name($fieldname)"/><xsl:text> (</xsl:text><xsl:value-of select="$fieldname/@type"/><xsl:text>)</xsl:text>
			</xsl:attribute>
			<xsl:attribute name="data-debug">
				<xsl:text>development</xsl:text>
			</xsl:attribute>
		</xsl:if>
	</xsl:template>

</xsl:stylesheet>
