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

/* Category chooser to select a parent category for a category. */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

?>
<script type="text/javascript">
	function SP_selectCat( sid ) {
		parent.document.getElementById( 'SP_selectedCid' ).value = sid;
		var separator = '<?php echo Sobi::Cfg( 'string.path_separator', ' > ' ); ?>';
		var cats = [];
		try {
			SP_id( 'sigsiu_tree_categories_CatUrl' + sid ).focus();
		} catch (e) {
		}
		new SobiPro.Json( '<?php $this->show( 'parent_ajax_url' ); ?>' + '&sid=' + sid, {} ).done( ( jsonObj ) => {
			catName = '';

			Object.entries( jsonObj.categories ).forEach( ( category ) => {
				const [ i, cat ] = category;
				cats[ cats.length ] = cat.name;
				catName = cat.name;
			} );
			selectedPath = cats.join( separator );
			parent.document.getElementById( 'SP_selectedCatPath' ).value = SobiPro.StripSlashes( selectedPath );
			parent.document.getElementById( 'SP_selectedCatName' ).value = SobiPro.StripSlashes( catName );
		} );
	}
</script>
<div>
	<div><?php $this->get( 'tree' )->display(); ?></div>
</div>
