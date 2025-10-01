<?php
/**
 * @package SobiPro multi-directory component with content construction support
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
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
 * @created 27-Nov-2009 by Radek Suski
 * @modified 14 February 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadClass( 'opt.fields.radio' );

use Sobi\C;
use Sobi\Input\Input;
use Sobi\Lib\Factory;
use Sobi\Utils\StringUtils;

/**
 * Class SPField_ChbxGr
 */
class SPField_ChbxGr extends SPField_Radio implements SPFieldInterface
{
	/* add here properties which are different from their initial value (model or derived class)
	   and properties valid only for this class. */

	/* properties with different value */
	/** @var string */
	protected $dType = 'predefined_multi_data_multi_choice';
	/** @var string */
	protected $cssClass = 'spClassCheckbox';
	/** @var string */
	protected $cssClassView = 'spClassViewCheckbox';
	/** @var string */
	protected $cssClassEdit = 'spClassEditCheckbox';
	/** @var string */
	protected $cssClassSearch = 'spClassSearchCheckbox';

	/* properties for this and derived classes */
	/** @var bool */
	protected $multi = true;

	/* properties only for this class */
	/** @var bool */
	protected $switch = false;

	/** @var bool */
	private static $CAT_FIELD = true;

	/**
	 * Returns the parameter list (params). All properties not set in the model but used in the xml file of the field.
	 * No language dependant values, no values from the model (columns in the database).
	 *
	 * @return array
	 */
	protected function getAttr(): array
	{
		return [ 'searchMethod', 'itemprop', 'helpposition', 'suggesting', 'showEditLabel', 'metaSeparator', 'cssClassView', 'cssClassSearch', 'cssClassEdit', 'bsWidth', 'bsSearchWidth', 'ssize', 'searchOperator', 'optInLine', 'optWidth', 'switch', 'meaning' ];
	}

	/**
	 * Shows the field in the edit entry or add entry form.
	 *
	 * @param bool $return return or display directly
	 *
	 * @return string
	 * @throws \SPException
	 */
	public function field( $return = false )
	{
		if ( $this->enabled ) {
			$class = defined( 'SOBIPRO_ADM' ) ? 'spClassCheckbox' : $this->cssClass;
			$class = $this->required ? $class . ' required' : $class;
			$html = $this->getField( $class, $this->required );

			if ( !$return ) {
				echo $html;
			}
			else {
				return $html;
			}
		}

		return C::ES;
	}

	/**
	 * @param string $class
	 * @param $required
	 * @param string $selected
	 *
	 * @return string
	 * @throws \SPException
	 */
	private function getField( string $class, $required, string $selected = C::ES ): string
	{
//		$fw =  defined( 'SOBIPRO_ADM' )  ? C::BOOTSTRAP5 : Sobi::Cfg( 'template.framework-style', C::BOOTSTRAP5 );

		$params = [ 'class' => $class ];
		$params[ 'switch' ] = $this->switch;
		if ( $this->optWidth ) {
			$params[ 'style' ] = "width:{$this->optWidth}px;";
		}

		$selected = $selected ? : $this->getRaw();
		if ( is_array( $selected ) && count( $selected ) ) {
			/* prepare the array for the in_array function in SPHtml_Input::checkBox */
			$selected = array_merge( $selected, array_keys( $selected ) );
		}
		if ( !$selected && $this->defaultValue ) {
			$selected = explode( ',', $this->defaultValue );
			$selected = array_map( 'trim', $selected );
		}
		$dc = $selected ? 'data-sp-content="1"' : C::ES;
		$appearance = $this->optInLine ? 'inline' : 'block';

		$values = $this->getValues();

		$fieldlist = C::ES;
		$list = SPHtml_Input::checkBoxGroup( $this->nid, $values, $this->nid, $selected, $params, $appearance, true );
		if ( is_array( $list ) && count( $list ) ) {
			$fieldlist = implode( '', $list );
			$fieldlist = "<div  id=\"$this->nid\" class=\"sp-field-checkbox spctrl-validategroup\" $dc>$fieldlist</div>";
		}

		return $fieldlist;
	}

	/**
	 * @return array
	 */
	private function getValues(): array
	{
		$values = [];
		if ( is_array( $this->options ) && count( $this->options ) ) {
			foreach ( $this->options as $option ) {
				$values[ $option[ 'id' ] ] = $option[ 'label' ] . ( strlen( $this->suffix ) ? ' ' . $this->suffix : C::ES );
			}
		}

		return $values;
	}

	/**
	 * Returns meta description.
	 *
	 * @return string
	 */
	public function metaDesc()
	{
		return ( $this->addToMetaDesc && is_array( $this->getRaw() ) && count( $this->getRaw() ) ) ? implode( ', ', $this->getRaw() ) : C::ES;
	}

	/**
	 * Returns meta keys.
	 *
	 * @return string
	 */
	public function metaKeys()
	{
		$data = $this->getRaw();

		return $this->addToMetaKeys && is_array( $data ) && count( $data ) ? implode( ', ', $this->getRaw() ) : C::ES;
	}

	/**
	 * Gets field specific values if these are in another table.
	 *
	 * @param $sid - id of the entry
	 * @param $fullData - the database row form the spdb_field_data table
	 * @param $rawData - raw data of the field content
	 * @param $fData - full formatted data of the field content
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
				foreach ( $order as $opt ) {
					if ( isset( $rawData[ $opt ] ) ) {
						$sRawData[] = $rawData[ $opt ];
					}
				}
				$fData = implode( "</li>\n\t<li>", $sRawData );
				$fData = "<ul id=\"$this->nid\" class=\"$this->cssClass\">\n\t<li>$fData</li>\n</ul>\n";
				$fullData->baseData = $fData;
			}
		}
		catch ( SPException $x ) {
			Sobi::Error( __CLASS__, SPLang::e( 'CANNOT_GET_SELECTED_OPTION', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}
	}

	/**
	 * @param $data
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
				/* check if such option exist at all */
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
	 *
	 * @param string $table - table or tables join
	 * @param array $conditions - array with conditions
	 * @param string $oPrefix
	 * @param string $eOrder
	 * @param string $eDir
	 *
	 * @return bool
	 */
	public static function sortBy( &$table, &$conditions, &$oPrefix, &$eOrder, $eDir )
	{
		// it sorts the entries by the option names of a checkbox group (the invisible thing).
		// Important: each entry needs to have an option set, means you need at least 2 options. Entries without option set, are not shown at all!!
		// This sorting method makes sense only for 'yes'/'no' options (e.g. isFavoured) as it does not sort by the shown value and is therefore not language dependent.
		// 4.12.20, Sigrid: wonder when it is called

		$table = Factory::Db()->join(
			[
				[ 'table' => 'spdb_field_option_selected', 'as' => 'sdata', 'key' => 'fid' ],
				[ 'table' => 'spdb_object', 'as' => 'spo', 'key' => [ 'sdata.sid', 'spo.id' ] ],
				[ 'table' => 'spdb_field_data', 'as' => 'fdata', 'key' => [ 'fdata.fid', 'sdata.fid' ] ],
				[ 'table' => 'spdb_field', 'as' => 'fdef', 'key' => [ 'fdef.fid', 'sdata.fid' ] ],
				[ 'table' => 'spdb_relations', 'as' => 'sprl', 'key' => [ 'spo.id', 'sprl.id' ] ],
			]
		);
		$oPrefix = 'spo.';
		$conditions[ 'spo.oType' ] = 'entry';
		if ( !isset( $conditions[ 'sprl.pid' ] ) ) {
			$conditions[ 'sprl.pid' ] = Input::Sid();
		}
		if ( isset( $conditions[ 'ids' ] ) ) {
			unset( $conditions[ 'ids' ] );
		}
		$conditions[ 'fdef.nid' ] = $eOrder;
		$eOrder = 'sdata.optValue.' . $eDir;

		return true;
	}

	/**
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function struct()
	{
		$baseData = $this->getRaw();
		$list = $struct = $data = [];
		$order = SPFactory::cache()->getVar( 'order_' . $this->nid );
		if ( !$order ) {
			$order = Factory::Db()->select( 'optValue', 'spdb_field_option', [ 'fid' => $this->id ], 'optPos' )->loadResultArray();
			SPFactory::cache()->addVar( $order, 'order_' . $this->nid );
		}
		if ( is_array( $baseData ) && count( $baseData ) ) {
			$this->cssClass = strlen( $this->cssClass ) ? $this->cssClass : 'sp-field-data';
			$this->cssClass = $this->cssClass . ' ' . $this->nid;
			$this->cleanCss();
			foreach ( $order as $opt ) {
				if ( isset( $baseData[ $opt ] ) ) {
					$list[] = [ '_tag' => 'li', '_value' => StringUtils::Clean( $baseData[ $opt ] ), '_class' => $opt, /*'_id' => trim( $this->nid.'_'.strtolower( $opt ) )*/ ];
				}
			}
			foreach ( $this->options as $opt ) {
				$struct[] = [
					'_complex'    => 1,
					'_data'       => $opt[ 'label' ],
					'_attributes' => [ 'selected' => ( isset( $baseData[ $opt[ 'id' ] ] ) ? 'true' : 'false' ), 'id' => $opt[ 'id' ], 'position' => $opt[ 'position' ] ],
				];
			}
			$data = [
				'ul' => [
					'_complex'    => 1,
					'_data'       => $list,
					'_attributes' => [ /* 'id' => $this->nid, */
					                   'class' => $this->cssClass ] ],
			];
		}

		if ( count( $list ) ) {
			return [
				'_complex'    => 1,
				'_data'       => $data,
				'_attributes' => [ 'lang' => $this->lang, 'class' => $this->cssClass ],
				'_options'    => $struct,
			];
		}

		return [];
	}
}
