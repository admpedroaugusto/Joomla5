<?php
/**
 * @package: SobiPro multi-directory component with content construction support
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2023 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/GPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/gpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * @created 01 August 2012 by Radek Suski
 * @modified 01 March 2023 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

/* Content (Body) of modal window for selection of category images */

$dc = $this->count( 'directories' );
$fc = $this->count( 'files' );
?>
<script type="text/javascript">
	function spSelect( e ) {
		parent.<?php $this->show( 'callback' ); ?>( e.src, e.alt );
		e.focus();
	}
</script>

<div class="SobiPro">
	<?php if ( $dc ) { ?>
		<div class="small mb-1 text-secondary">
			<span class="me-1"><?php echo Sobi::Txt( 'CATEGORY.IMAGE_CHOOSER_PATH' ) ?></span> <span><?php echo $this->_attr[ 'folder' ] ?></span>
		</div>
		<div class="sp-image-folders">
			<?php for ( $i = 0; $i < $dc; ++$i ) { ?>
				<div class="sp-image-folder">
					<a href="<?php $this->show( 'directories.url', $i ); ?>">
						<span title="<?php $this->show( 'directories.name', $i ); ?> (<?php $this->show( 'directories.count', $i ); ?>)" class="<?php $this->show( 'symbol' ); ?>"></span> </a>
					<a href="<?php $this->show( 'directories.url', $i ); ?>" class="small">
						<?php $this->show( 'directories.name', $i ); ?> (<?php $this->show( 'directories.count', $i ); ?>) </a>
				</div>
			<?php } ?>
		</div>
	<?php } ?>
	<?php if ( $fc ) { ?>
		<ul class="sp-image-files">
			<?php for ( $i = 0; $i < $fc; ++$i ) { ?>
				<li>
					<div class="sp-img-thumb">
						<label>
							<img alt="<?php $this->show( 'files.name', $i ); ?>" title="<?php $this->show( 'files.shortname', $i ); ?>" src="<?php $this->show( 'files.path', $i ); ?>" onclick="spSelect( this )">
						</label>
					</div>
					<div class="sp-img-caption">
						<p>
							<?php $this->show( 'files.shortname', $i ); ?>
						</p>
					</div>
				</li>
			<?php } ?>
		</ul>
	<?php } ?>
	<?php if ( !$dc && !$fc ) { ?>
		<p<?php echo Sobi::Txt( 'CATEGORY.IMAGE_CHOOSER_FILES' ) ?></p>
	<?php } ?>
</div>
