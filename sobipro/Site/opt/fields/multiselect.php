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
 * @created 26-Nov-2009 by Radek Suski
 * @modified 23 December 2022 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadClass( 'opt.fields.select' );

use Sobi\C;
use Sobi\Lib\Factory;
use Sobi\Utils\StringUtils;

/**
 * Class SPField_MultiSelect
 */
class SPField_MultiSelect extends SPField_Select implements SPFieldInterface
{
	/* add here properties which are different from their initial value (model or derived class)
	   and properties valid only for this class. */

	/* properties with different value */
	/*** @var string */
	protected $dType = 'predefined_multi_data_multi_choice';
	/** @var string */
	protected $cssClass = 'spClassMSelect';
	/** @var string */
	protected $cssClassView = 'spClassViewMSelect';
	/** @var string */
	protected $cssClassEdit = 'spClassEditMSelect';
	/** @var string */
	protected $cssClassSearch = 'spClassSearchMSelect';

	/*** @var int */
	protected $size = 10;
	/*** @var bool */
	protected $multi = true;

	/** @var bool */
	private static $CAT_FIELD = true;

	/**
	 * Gets field specific values if these are in another table.
	 *
	 * @param $sid
	 * @param $fullData
	 * @param $rawData
	 * @param $fData
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function loadData( $sid, &$fullData, &$rawData, &$fData )
	{
		static $lang = C::ES;
		if ( !$lang ) {
			$lang = Sobi::Lang( false );
		}

		$db = Factory::Db();
		$table = $db->join(
			[
				[ 'table' => 'spdb_field_option_selected', 'as' => 'sdata', 'key' => 'fid' ],
				[ 'table' => 'spdb_field_data', 'as' => 'fdata', 'key' => 'fid' ],
				[ 'table' => 'spdb_language', 'as' => 'ldata', 'key' => [ 'sdata.optValue', 'ldata.sKey' ] ],
			]
		);
		try {
			$db->select(
				'*, sdata.copy as scopy',
				$table,
				[
					'sdata.fid'   => $this->id,
					'sdata.sid'   => $sid,
					'fdata.sid'   => $sid,
					'ldata.oType' => 'field_option',
					'ldata.fid'   => $this->id,
				],
				'scopy', 0, 0, true /*, 'sdata.optValue' */
			);
			$data = $db->loadObjectList();

			if ( $data && count( $data ) ) {
				$order = SPFactory::cache()->getVar( 'order_' . $this->nid );
				if ( !$order ) {
					$order = $db
						->select( 'optValue', 'spdb_field_option', [ 'fid' => $this->id ], 'optPos' )
						->loadResultArray();
					SPFactory::cache()->addVar( $order, 'order_' . $this->nid );
				}

				$rawData = $sRawData = [];
				$copied = false;
				/* check which version the user may see */
				$copy = $this->checkCopy();
				foreach ( $data as $selected ) {
					// if there was at least once copy
					if ( $selected->scopy ) {
						$copied = true;
					}
				}
				/* check what we should show */
				$remove = ( int ) $copied && $copy;
				foreach ( $data as $selected ) {
					if ( $selected->scopy == $remove ) {
						/* if not already set or the language fits better */
						if ( !isset( $rawData[ $selected->optValue ] ) || $selected->language == $lang ) {
							$rawData[ $selected->optValue ] = $selected->sValue;
						}
					}
				}
				foreach ( $order as $id => $opt ) {
					if ( isset( $rawData[ $opt ] ) ) {
						$sRawData[] = $rawData[ $opt ];
						//$this->_selected[ $id ] = $opt;
					}
				}
				$fData = implode( "</li>\n\t<li>", $sRawData );
				$fData = "<ul id=\"$this->nid\" class=\"$this->cssClass\">\n\t<li>$fData</li>\n</ul>\n";
				$fullData->baseData = $fData;
			}
		}
		catch ( SPException $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'CANNOT_GET_SELECTED_OPTIONS', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}
	}

	/**
	 * Returns meta description.
	 *
	 * @return string
	 */
	public function metaDesc()
	{
		return $this->addToMetaDesc && count( $this->getRaw() ) ? implode( ', ', $this->getRaw() ) : C::ES;
	}

	/**
	 * Returns meta keys.
	 *
	 * @return string
	 */
	public function metaKeys()
	{
		return $this->addToMetaKeys && count( $this->getRaw() ) ? implode( ', ', $this->getRaw() ) : C::ES;
	}

	/**
	 * @return array|null
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function struct()
	{
		$baseData = $this->getRaw();
		$list = $struct = [];
		$order = SPFactory::cache()->getVar( 'order_' . $this->nid );
		if ( !$order ) {
			$order = Factory::Db()->select( 'optValue', 'spdb_field_option', [ 'fid' => $this->id ], 'optPos' )->loadResultArray();
			SPFactory::cache()->addVar( $order, 'order_' . $this->nid );
		}
		$this->cssClass = strlen( $this->cssClass ) ? $this->cssClass : 'sp-field-data';
		$this->cssClass = $this->cssClass . ' ' . $this->nid;
		$this->cleanCss();

		foreach ( $order as $opt ) {
			if ( isset( $baseData[ $opt ] ) ) {
				$list[] = [ '_tag' => 'li', '_value' => StringUtils::Clean( $baseData[ $opt ] ), '_class' => $opt ];
			}
		}
		foreach ( $this->options as $opt ) {
			if ( isset( $opt[ 'options' ] ) && is_array( $opt[ 'options' ] ) ) {
				foreach ( $opt[ 'options' ] as $sub ) {
					$struct[] = [
						'_complex'    => 1,
						'_data'       => $sub[ 'label' ],
						'_attributes' => [ 'group'    => $opt[ 'id' ],
						                   'selected' => isset( $baseData[ $sub [ 'id' ] ] ) ? 'true' : 'false',
						                   'id'       => $sub[ 'id' ],
						                   'position' => $sub[ 'position' ] ],
					];
				}
			}
			else {
				$struct[] = [
					'_complex'    => 1,
					'_data'       => $opt[ 'label' ],
					'_attributes' => [ 'selected' => isset( $baseData[ $opt[ 'id' ] ] ) ? 'true' : 'false',
					                   'id'       => $opt[ 'id' ],
					                   'position' => $opt[ 'position' ] ],
				];
			}
		}
		$data = [
			'ul' => [
				'_complex'    => 1,
				'_data'       => $list,
				'_attributes' => [ 'class' => $this->cssClass ] ],
		];

		return [
			'_complex'    => 1,
			'_data'       => count( $list ) ? $data : C::ES,
			'_attributes' => [ 'lang' => $this->lang ? $this->lang : Sobi::Lang( false ), 'class' => $this->cssClass ],
			'_options'    => $struct,
		];
	}

	/**
	 * @param array|string $data
	 * @param string $request
	 *
	 * @return array
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function fetchData( $data, $request = 'post' )
	{
		if ( is_array( $data ) && count( $data ) ) {
			$selected = [];
			foreach ( $data as $opt ) {
				/* check if such an option exists at all */
				if ( !isset( $this->optionsById[ $opt ] ) ) {
					throw new SPException( SPLang::e( 'FIELD_NO_SUCH_OPT', $opt, $this->name ) );
				}
				$selected[] = preg_replace( '/^[a-z0-9]\.\-\_/i', C::ES, $opt );
			}

			return $selected;
		}
		else {
			return [];
		}
	}

	/**
	 * Static function to create the right SQL-Query if a entries list should be sorted by this field.
	 * Will never be called as sorting by this field is not possible
	 *
	 * @param string $tables
	 * @param array $conditions
	 * @param string $oPrefix
	 * @param string $eOrder
	 * @param string $eDir
	 *
	 * @return bool
	 */
	public static function sortBy( &$tables, &$conditions, &$oPrefix, &$eOrder, $eDir )
	{
		return false;
	}

	/**
	 * @param $values
	 *
	 * @return bool
	 */
	protected function required( &$values )
	{
		return false;
	}
}
