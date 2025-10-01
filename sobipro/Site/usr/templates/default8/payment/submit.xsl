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
	<xsl:include href="../common/globals.xsl"/>
	<xsl:include href="list.xsl"/>

	<xsl:template match="/payment_details">
		<div id="spctrl-payment-modal">
			<div class="modal fade" tabindex="-1" aria-labelledby="sparia-title" aria-hidden="true">
				<div class="modal-dialog modal-lg modal-fullscreen-sm-down">
					<div class="modal-content">
						<div class="modal-header">
							<h3 class="modal-title" id="sparia-title">
								<xsl:value-of select="php:function( 'SobiPro::TemplateTxt', 'PAYMENT_CHOSEN_FOLLOWING_OPTIONS' )"/>
							</h3>
						</div>
						<div class="modal-body">
							<xsl:call-template name="paymentTable"/>
						</div>
						<div class="modal-footer">
							<a href="#" class="btn btn-beta back" data-bs-dismiss="modal" type="button">
								<xsl:value-of select="php:function( 'SobiPro::TemplateTxt', 'EN.PAYMENT_BACK_BT' )"/>
							</a>
							<a href="{/payment_details/save_url}" class="btn btn-delta" type="button">
								<xsl:value-of select="php:function( 'SobiPro::TemplateTxt', 'EN.PAYMENT_SAVE_ENTRY_BT' )"/>
							</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</xsl:template>
</xsl:stylesheet>
