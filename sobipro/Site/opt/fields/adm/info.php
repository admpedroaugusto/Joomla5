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
 * @created 12-Sep-2015 by Sigrid Suski
 * @modified 01 September 2022 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadClass( 'opt.fields.info' );

/**
 * Class SPField_InfoAdm
 */
class SPField_InfoAdm extends SPField_Info
{
	/**
	 * Saves the translatable elements.
	 *
	 * @param $attr
	 */
	public function save( &$attr )
	{
		if ( $this->nid ) {
			$this->nid = $attr[ 'nid' ];
		}

		/* add the field specific attributes as param to the general attributes. */
		parent::save( $attr );
	}

	/**
	 * Saves the field specific data for a new or duplicated field.
	 *
	 * @param $attr
	 * @param $fid
	 */
	public function saveNew( &$attr, $fid = 0 )
	{
		if ( $fid ) {
			$this->id = $this->fid = $fid;
		}
		$this->save( $attr );
	}

	/**
	 * @param $attr
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function saveLanguageData( $attr ): void
	{
		/* generate and save the language specific field parameters */
		if ( !isset( $attr[ 'viewInfo' ] ) ) {
			$attr[ 'viewInfo' ] = $this->viewInfo;
		}
		if ( !isset( $attr[ 'entryInfo' ] ) ) {
			$attr[ 'entryInfo' ] = $this->entryInfo;
		}
		$data = [
			'key'     => $this->nid . '-viewInfo',
			'value'   => $attr[ 'viewInfo' ],
			'type'    => 'field_' . $this->fieldType,
			'fid'     => $this->fid,
			'id'      => $attr[ 'section' ],
			'section' => $attr[ 'section' ],
		];
		SPLang::saveValues( $data );

		$data = [
			'key'     => $this->nid . '-entryInfo',
			'value'   => $attr[ 'entryInfo' ],
			'type'    => 'field_' . $this->fieldType,
			'fid'     => $this->fid,
			'id'      => $attr[ 'section' ],
			'section' => $attr[ 'section' ],
		];
		SPLang::saveValues( $data );
	}

	/**
	 * @return array
	 */
	public function exportField(): array
	{
		$data = [];
		$data[] = [ 'attributes' => [ 'name' => 'viewInfo' ], 'value' => $this->viewInfo ];
		$data[] = [ 'attributes' => [ 'name' => 'entryInfo' ], 'value' => $this->entryInfo ];

		return $data;
	}

	/**
	 * @param $data
	 * @param $nid
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function importField( $data, $nid )
	{
		if ( is_array( $data ) && count( $data ) ) {
			$this->nid = $nid;
			foreach ( $data as $set ) {
				$attr = $set[ 'attributes' ][ 'name' ];
				$this->$attr = $set[ 'value' ];
			}
			$viewInfo = [
				'key'     => $this->nid . '-viewInfo',
				'value'   => $this->viewInfo,
				'type'    => 'field_' . $this->fieldType,
				'fid'     => $this->fid,
				'id'      => Sobi::Section(),
				'section' => Sobi::Section(),
			];
			SPLang::saveValues( $viewInfo );
			$entryInfo = [
				'key'     => $this->nid . '-entryInfo',
				'value'   => $this->entryInfo,
				'type'    => 'field_' . $this->fieldType,
				'fid'     => $this->fid,
				'id'      => Sobi::Section(),
				'section' => Sobi::Section(),
			];
			SPLang::saveValues( $entryInfo );
		}
	}
}
