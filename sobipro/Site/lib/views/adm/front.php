<?php
/**
 * @package: SobiPro Library
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2023 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 14-Jan-2009 by Radek Suski
 * @modified 03 March 2023 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadView( 'view', true );

use Sobi\C;

/**
 * Class SPAdmPanelView
 */
class SPAdmPanelView extends SPAdmView
{
	/**
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function display()
	{
		$sections =& $this->get( 'sections' );
		$_sections = [];
		if ( is_array( $sections ) && count( $sections ) ) {
			foreach ( $sections as $section ) {
				$name = $section->get( 'name' );
				$id = $section->get( 'id' );
				$url = Sobi::Url( [ 'sid' => $id ] );
				$_section = [];
				$_section[ 'id' ] = $id;
				$_section[ 'nid' ] = $section->get( 'nid' );
				$_section[ 'name' ] = "<a href=\"$url\">$name</a>";
				$_section[ 'createdTime' ] = $section->get( 'createdTime' );
				$_section[ 'metaDesc' ] = $section->get( 'metaDesc' );
				$_section[ 'metaKey' ] = $section->get( 'metaKey' );
				$_section[ 'description' ] = $section->get( 'description' );
				$_section[ 'url' ] = $url;

				$_sections[] = $_section;
			}
		}
		$this->set( $_sections, 'sections' );
		parent::display();
	}

	/**
	 * @return string
	 * @throws \Sobi\Error\Exception
	 */
	public static function phpSettings(): string
	{
		$limit = ini_get( 'memory_limit' );
		$postsize = ini_get( 'post_max_size' );
		$uploadsize = ini_get( 'upload_max_filesize' );

		$sizes = "<div class=\"phpinfo alert alert-outline-info\">"
			. SPLang::e( 'PHP_SIZE_MEMORY', $limit ) . "<br/>"
			. SPLang::e( 'PHP_UPLOAD_SIZE', $uploadsize ) . "<br/>"
			. SPLang::e( 'PHP_POST_SIZE', $postsize ) . "</div>";

		return $sizes;
	}

	/**
	 * @return string
	 * @throws \Sobi\Error\Exception
	 */
	public static function phpWarnings(): string
	{
		$messages = [];
		$limit = ini_get( 'memory_limit' );
		$postsize = ini_get( 'post_max_size' );
		$uploadsize = ini_get( 'upload_max_filesize' );

		$memory_limit = Sobi::getBytes( $limit );
		if ( $memory_limit < ( 32 * 1024 * 1024 ) && $memory_limit != -1 ) {
			// 32MB
			$messages[] = [ 'message'     => SPLang::e( 'PHP_WARNING_LOWMEMORYWARN' ),
			                'description' => SPLang::e( 'PHP_WARNING_LOWMEMORYDESC', '32M', '32M' ) ];
		}
		else {
			if ( $memory_limit < ( 64 * 1024 * 1024 ) && $memory_limit != -1 ) { /*64MB*/
				$messages[] = [ 'message'     => SPLang::e( 'PHP_WARNING_MEDMEMORYWARN' ),
				                'description' => SPLang::e( 'PHP_WARNING_MEDMEMORYDESC', '64M', '64M' ) ];
			}
		}

		$post_max_size = Sobi::getBytes( $postsize );
		$upload_max_filesize = Sobi::getBytes( $uploadsize );

		if ( $post_max_size < $upload_max_filesize ) {
			$messages[] = [ 'message'     => SPLang::e( 'PHP_WARNING_UPLOADBIGGERTHANPOST' ),
			                'description' => Sobi::Txt( 'PHP_WARNING_UPLOADBIGGERTHANPOSTDESC' ) ];
		}
		if ( $post_max_size < ( 16 * 1024 * 1024 ) ) { // /*16MB*/
			$messages[] = [ 'message'     => SPLang::e( 'PHP_WARNING_SMALLPOSTSIZE' ),
			                'description' => SPLang::e( 'PHP_WARNING_SMALLPOSTSIZEDESC', '16M' ) ];
		}
		if ( $upload_max_filesize < ( 16 * 1024 * 1024 ) ) { // /*16MB*/
			$messages[] = [ 'message'     => SPLang::e( 'PHP_WARNING_SMALLUPLOADSIZE' ),
			                'description' => SPLang::e( 'PHP_WARNING_SMALLUPLOADSIZEDESC', '16M' ) ];
		}

		$warning = C::ES;
		if ( is_array( $messages ) && count( $messages ) ) {
			$warning = "<div class=\"phpwarnings alert alert-outline-danger\">";
			$warning .= '<span><a class="alert-link" data-bs-toggle="collapse" href="#detailedMessages" role="button" aria-expanded="false" aria-controls="detailedMessages">' . SPLang::e( 'PHP_WARNINGS', count( $messages ) ) . '</a></span>';
			$warning .= '<div class="collapse" id="detailedMessages">';
			foreach ( $messages as $msg ) {
				$warning .= "<h6 class=\"mt-2\">" . $msg[ 'message' ] . "</h6>" . $msg[ 'description' ] . "";
			}
			$warning .= "</div></div>";
		}

		return $warning;
	}
}
