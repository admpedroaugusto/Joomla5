<?php
/**
 * @package: SobiPro multi-directory component with content construction support
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2022 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/GPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/gpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * @created 01 August 2012 by Radek Suski
 * @modified 05 January 2022 by Sigrid Suski
 */

/* Category chooser to select a category in a module. */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
?>
<script type="text/javascript">
	function SP_selectCat( sid ) {
		let url = document.getElementById( 'sigsiu_tree_categories_CatUrl' + sid );
		try {
			url.focus();
		} catch (e) {
		}
		parent.document.getElementById( 'selectedCat' ).value = sid;
		parent.document.getElementById( 'selectedCatName' ).value = url.innerHTML;
	}
</script>
<div>
	<?php $this->get( 'tree' )->display(); ?>
</div>
