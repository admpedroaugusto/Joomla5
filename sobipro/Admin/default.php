<?php
/**
 * @package SobiPro multi-directory component with content construction support
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
 * @modified 15 September 2023 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

$data = $this->getData();
?>
<?php echo $this->toolbar(); ?>
<?php $this->trigger( 'OnStart' ); ?>
<?php if ( isset( $data[ 'data' ] ) && count( $data[ 'data' ] ) ) : ?>
	<?php foreach ( $data[ 'data' ] as $element ) : ?>
		<?php $this->getParser()->parse( $element ); ?>
	<?php endforeach; ?>
<?php endif ?>
<?php $this->trigger( 'OnEnd' ); ?>

