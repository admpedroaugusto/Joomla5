<?php
/**
 * @package SobiPro multi-directory component with content construction support
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006–2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/GPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/gpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * @created Sat, Oct 20, 2012 by Radek Suski
 * @modified 18 October 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadClass( 'opt.fields.fieldtype' );

use Sobi\C;
use Sobi\Input\Input;
use Sobi\FileSystem\FileSystem;
use Sobi\Lib\Factory;
use Sobi\Utils\Serialiser;
use Sobi\Error\Exception;

/**
 * Class SPField_Category
 */
class SPField_Category extends SPFieldType implements SPFieldInterface
{
	/* add here properties which are different from their initial value (model or derived class)
	   and properties valid only for this class. */

	/* properties with different value */
	/** @var string */
	protected $dType = 'special';
	/** @var int */
	protected $bsWidth = 4;
	/** @var int */
	protected $bsSearchWidth = 4;
	/** @var int */
	protected $height = 150;
	/** @var string */
	protected $cssClass = 'spClassCategory';
	/** @var string */
	protected $cssClassEdit = 'spClassEditCategory';
	/** @var string */
	protected $cssClassSearch = 'spClassSearchCategory';
	/** @var string */
	protected $searchMethod = 'select';

	/* properties for this and derived classes */
	/** @var int */
	protected $size = 0;
	/** @var int */
	protected $ssize = 0;

	/* properties only for this class */
	/** @var string */
	protected $method = 'mselect';
	/** @var int */
	protected $catsMaxLimit = 10;
	/** @var bool */
	protected $catsWithChilds = true;
	/** @var bool */
	protected $isPrimary = false;
	/** @var int */
	protected $searchHeight = 100;
	/** @var string */
	protected $orderCatsBy = 'name.asc';
	/** @var string */
	protected $searchOrderCatsBy = 'name.asc';
	/** @var string */
	protected $sid = C::ES;

	/* properties for fixed category */
	/** @var string */
	protected $fixedCid = C::ES;

	/* properties for tree */
	/** @var bool */
	protected $modal = false;

	/* properties for tree or mselect */
	/** @var bool */
	protected $preselect = true;
	/** @var bool */
	protected $presumed = false;

	/** @var array */
	protected $_selectedCats = [];
	/** @var array */
	protected $_cats = [];

	/** @var bool */
	private static $FILTER_FIELD = true;

	/**
	 * SPField_Category constructor. Get language dependant settings.
	 *
	 * @param $field
	 */
	public function __construct( &$field )
	{
		parent::__construct( $field );

		$this->orderCatsBy = $this->orderCatsBy ? : 'position.asc';
		$this->searchOrderCatsBy = $this->searchOrderCatsBy ? : 'position.asc';
		if ( $this->method == 'fixed' ) {
//			$this->editable = true;
			$this->editLimit = 5;
		}
		if ( $this->method == 'fixed' && in_array( Input::Task(), [ 'entry.add', 'entry.edit' ] ) ) {
			$this->isOutputOnly = true;
		}
		$this->preselect = $this->preselect && !( $this->method == 'tree' && $this->method == 'mselect' && !$this->method == 'preselect' );
	}

	/**
	 * Returns the parameter list (params). All properties not set in the model but used in the xml file of the field.
	 * No language dependant values, no values from the model (columns in the database).
	 *
	 * @return array
	 */
	protected function getAttr(): array
	{
		return [ 'searchMethod', 'itemprop', 'helpposition', 'showEditLabel', 'height', 'cssClassSearch', 'cssClassEdit', 'bsWidth', 'bsSearchWidth', 'size', 'ssize', 'method', 'modal', 'isPrimary', 'catsMaxLimit', 'catsWithChilds', 'fixedCid', 'searchHeight', 'orderCatsBy', 'searchOrderCatsBy', 'preselect', 'presumed' ];
	}

	/**
	 * @return array -> all properties which are not in the XML file but its default value needs to be set
	 */
	protected function getDefaults(): array
	{
		return [ 'suggesting' => false ];
	}

	/**
	 * @return void
	 */
	public function loadData(): void
	{
//		if ( $this->method == 'fixed' ) {
//			$this->editable = true;
		// meeeh ;)
//			$this->__call( 'set', [ 'editable', true ] );
//		}
	}

	/**
	 * @param $data
	 *
	 * @return array|false|mixed|string|string[]|null
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function cleanData( $data = C::ES )
	{
		$this->_selectedCats = $data ? : $this->getRaw();
		$this->_selectedCats = is_null( $this->_selectedCats ) ? [] : $this->_selectedCats;

		/* if the data are structural (json, ...) or several fixed categories */
		if ( is_string( $this->_selectedCats ) ) {
			if ( strstr( $this->_selectedCats, '://' ) ) {
				$this->_selectedCats = SPFactory::config()->structuralData( $this->_selectedCats );
			}
			else {
				/* if the data are comma separated */
				if ( strstr( $this->_selectedCats, ',' ) ) {
					$this->_selectedCats = explode( ',', $this->_selectedCats );
					$data = [];
					if ( count( $this->_selectedCats ) ) {
						foreach ( $this->_selectedCats as $cid ) {
							$data[] = trim( $cid );
						}
						$this->_selectedCats = $data;
					}
				}
				else {
					/* if the data are not numerical and not an array -> string  */
					$category = (int) $this->_selectedCats;
					if ( (string) $category == $this->_selectedCats ) {
						$this->_selectedCats = [ $this->_selectedCats ];
					}
					else {
						/* if the data are a simple string -> probably serialized */
						$this->_selectedCats = Serialiser::Unserialise( $this->_selectedCats );
					}
				}
			}
		}
		if ( !$this->_selectedCats ) {
			// use Input::Sid() only if preselect allowed (used in back-end and from front-end menu)
			if ( $this->preselect ) {
				if ( Input::Task() == 'entry.add' && Input::Sid() != Sobi::Section() ) {
					$this->_selectedCats = [ Input::Sid() ];
					$this->presumed = true;
				}
			}
		}

		return !$this->_selectedCats ? [] : $this->_selectedCats;
	}

	/**
	 * Shows the field in the edit entry or add entry form.
	 *
	 * @param bool $return return or display directly
	 *
	 * @return string
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function field( $return = false )
	{
		if ( !$this->enabled ) {
			return C::ES;
		}

		Sobi::$filter = explode( '.', $this->orderCatsBy );
		$this->suffix = C::ES;
		$this->loadCategories();

		$sid = Input::Sid();
		if ( !$this->sid ) {
			$this->sid = $sid != Sobi::Section() ? $sid : C::ES;
		}

		$fw = defined( 'SOBIPRO_ADM' ) ? C::BOOTSTRAP5 : Sobi::Cfg( 'template.framework-style', C::BOOTSTRAP5 );

		$this->presumed = false;
		$this->cleanData(); // gets the field value (selected categories) in $this->_selectedCats

		$entry = null;
		if ( $this->sid ) {
			$entry = SPFactory::Entry( $this->sid );
		}

		/* if nothing stored for this field */
		if ( !$this->_selectedCats || ( is_array( $this->_selectedCats ) && !count( $this->_selectedCats ) ) ) {

			/* no preselect */
			if ( !$this->preselect ) {
				$this->_selectedCats = [];
			}

			/* preselect */
			else {
				// gets categories already stored for that entry
				if ( $entry ) {
					/* if the destination category type can store only one category, use the first given category */
					$selectedCats = (array) array_keys( $entry->get( 'categories' ) );
					if ( count( $selectedCats ) ) {
						$this->_selectedCats = [ $selectedCats[ 0 ] ];
					}
					if ( is_array( $this->_selectedCats ) && count( $this->_selectedCats ) ) {
						$this->presumed = true;
					}
				}
			}
		}

		$errorMessage = C::ES;
		if ( $entry && !$entry->get( 'valid' ) && !count( $entry->get( 'categories' ) ) ) {
			$this->cssClass .= ' is-invalid';
			$errorMessage = '<p class="sp-invalid">' . Sobi::Txt( 'EN.SET_CAT_SELECT_CATEGORY' ) . '</p>';
		}

		$this->withLabel = true;
		if ( !( ( int ) $this->catsMaxLimit ) ) {
			$this->catsMaxLimit = 10;   //set to standard value
		}
		if ( is_array( $this->_selectedCats ) && ( count( $this->_selectedCats ) > $this->catsMaxLimit ) ) {
			$this->_selectedCats = array_slice( $this->_selectedCats, 0, $this->catsMaxLimit );
		}

		$field = C::ES;
		switch ( $this->method ) {
			case 'tree':
				$field = $this->tree( $fw );
				break;
			case 'select':
				$field = $this->select( $fw );
				break;
			case 'pselect':
				$field = $this->pselect( $fw );
				break;
			case 'mselect':
				$field = $this->mSelect( $fw );
				break;
		}
		$field .= $errorMessage;

		if ( !$return ) {
			echo $field;
		}
		else {
			return $field;
		}

		return C::ES;
	}

	/**
	 * @param int $fw
	 *
	 * @return string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function tree( int $fw = C::BOOTSTRAP5 ): string
	{
		$selectedCategories = $params = $selectParams = [];
		$dc = C::ES;

		/** @var SigsiuTree $tree */
		$tree = SPFactory::Instance( 'mlo.tree', Sobi::Cfg( 'list.categories_ordering' ), [ 'preventParents' => !$this->catsWithChilds ] );
		$tree->setHref( '#' );
		$tree->setTask( 'category.chooser' );
		$tree->setId( $this->nid );
		$tree->disable( Sobi::Section() );
		$tree->init( Sobi::Section() );

		$params[ 'maxcats' ] = $this->catsMaxLimit;
		$params[ 'field' ] = $this->nid;
		$params[ 'preventParents' ] = !$this->catsWithChilds;

		SPFactory::header()
			->addJsFile( 'opt.field_category_tree' )
			->addJsCode( 'SobiCore.Ready( function () { new SigsiuTreeEdit( ' . json_encode( $params ) . '); } );' );

		if ( is_array( $this->_selectedCats ) && count( $this->_selectedCats ) ) {
			$dc = 'data-sp-content="1"';
			$selected = SPLang::translateObject( $this->_selectedCats, 'name', 'category' );
			if ( count( $selected ) ) {
				$count = 0;
				foreach ( $selected as $category ) {
					if ( $category[ 'id' ] == $this->sid && Input::Task() != 'entry.add' ) {
						continue;
					}
					$selectedCategories[ $category[ 'id' ] ] = $category[ 'value' ];
					$count++;
					if ( $count == $this->catsMaxLimit ) {
						break;
					}
				}
			}
		}

		$maxheight = strlen( $this->height ) ? "max-height: {$this->height}px;" : C::ES;

		if ( $this->height > 100 ) {
			$selectParams[ 'style' ] = "min-height: {$this->height}px;";
		}

		/* create the add and delete buttons */
		$btncolour = Sobi::Cfg( 'template.supportold', false ) && !defined( 'SOBIPRO_ADM' ) ? 'btn-default' : 'btn-delta';
		$addBtParams = [ 'class' => 'btn btn-sm ' . $btncolour ];
		$delBtParams = [ 'class' => 'btn btn-sm ' . $btncolour ];

		if ( count( $selectedCategories ) >= $this->catsMaxLimit ) {
			$addBtParams[ 'disabled' ] = 'disabled';
			$selectParams[ 'readonly' ] = 'readonly';
		}
		else {
			if ( !count( $selectedCategories ) ) {
				$delBtParams[ 'disabled' ] = 'disabled';
			}
		}

		$row = 'row';
		$col = 'col-sm';
		$colspan = 'col-sm-';
		$params = [ 'class' => 'btn ' . $btncolour, 'href' => '#' . $this->nid . '_modal', 'id' => $this->nid . '_modal_fire' ];

		switch ( $fw ) {
			case C::BOOTSTRAP5:
				$treeclass = 'sp-field-category' . ( $this->required ? ' required' : C::ES );
				$params[ 'data-bs-toggle' ] = 'modal';
				break;
			case C::BOOTSTRAP4:
				$treeclass = 'sp-field-category' . ( $this->required ? ' required' : C::ES );
				$params[ 'data-toggle' ] = 'modal';
				break;
			case C::BOOTSTRAP3:
				$col = 'col-sm-6';
				$treeclass = 'sp-field-category container-fluid' . ( $this->required ? ' required' : C::ES );
				$params[ 'data-toggle' ] = 'modal';
				break;
			default:    /* Bootstrap 2 */
				$row = 'row-fluid';
				$col = 'span6';
				$colspan = 'span';
				$selectParams[ 'class' ] = 'span12';
				$treeclass = 'sp-field-category' . $this->required ? ' required' : C::ES;
				$addBtParams = [ 'class' => 'btn btn-small ' . $btncolour ];
				$delBtParams = [ 'class' => 'btn btn-small ' . $btncolour ];
				$params[ 'data-toggle' ] = 'modal';
		}

		$html = "<div class=\"$row\">"; /* overall row */
		$html .= "<div class=\"spctrl-sigsiutree-container $col\" $maxheight > {$tree->display( true )}</div>";

		$html .= "<div class=\"$col\">"; /* right column including selected and buttons */

		$html .= "<div class=\"$row gx-0\">";  /* inner row */
		$html .= "<div class=\"selected {$colspan}12\">"; /* selected column */
		$html .= SPHtml_Input::select( $this->nid . '_list', $selectedCategories, [], true, $selectParams );
		$html .= SPHtml_Input::hidden( $this->nid, 'json://' . json_encode( array_keys( $selectedCategories ) ) );
		$html .= '</div>'; /* close selected column */

		$html .= "<div class=\"sp-buttons {$colspan}12\">"; /* buttons column */
		$html .= SPHtml_Input::button( 'addCategory', Sobi::Txt( 'CC.ADD_BT' ), $addBtParams, C::ES, 'plus' );
		$html .= SPHtml_Input::button( 'removeCategory', Sobi::Txt( 'CC.DEL_BT' ), $delBtParams, C::ES, 'minus' );
		$html .= '</div>';  /* close buttons column */

		$html .= '</div>'; /* close inner row */
		$html .= '</div>'; /* close right column */
		$html .= '</div>';  /* close overall row */

//		if ( defined( 'SOBIPRO_ADM' ) ) {
//			$treeclass .= ' sp-admin-entry';
//		}
//
		$html = "<div class=\"$treeclass\" id=\"{$this->nid}_canvas\" $dc>$html</div>";

		if ( $this->presumed ) {
			$html .= '<p class="sp-cat-presumed">' . Sobi::Txt( 'EN.SET_CAT_PRESUMED' ) . '</p>';
		}

		if ( $this->modal ) {
			$html = SPHtml_Input::modalWindow( Sobi::Txt( 'EN.SELECT_CAT_PATH' ),
				$this->nid . '_modal',
				$html,
				'modal-lg modal-dialog-scrollable',
				'CLOSE',
				C::ES
			);
			$params[ 'data-sp-disable' ] = 'true';
			$button = '<div class="sp-field-category">';
			$button .= SPHtml_Input::button( 'select-category', Sobi::Txt( 'EN.SELECT_CAT_PATH' ), $params );
			$button .= '</div>';

			$html = $button . $html;
		}

		return $html;
	}

	/**
	 * @param int $fw
	 *
	 * @return string
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function select( int $fw = C::BOOTSTRAP5 ): string
	{
		$html = C::ES;
		if ( is_array( $this->_cats ) && count( $this->_cats ) ) {
			$values = [];

			$class = defined( 'SOBIPRO_ADM' ) ? 'spClassCategory' : $this->cssClass;
			$class .= $fw == C::BOOTSTRAP2 ? ' w-100' : C::ES;

			$params = [
				'id'    => $this->nid,
				'class' => ( $this->required ? 'required ' : C::ES ) . $class,
			];
			$this->createValues( $this->_cats, $values, Sobi::Cfg( 'category_chooser.margin_sign', '-' ) );
			$selected = $this->_selectedCats;
			if ( is_array( $selected ) && count( $selected ) ) {
				foreach ( $selected as $i => $v ) {
					$selected[ $i ] = (string) $v;
				}
			}
			$dc = $selected ? 'data-sp-content="1"' : C::ES;

			$class = 'sp-field-category';
			if ( $fw != C::BOOTSTRAP3 ) {
				$class = 'sp-field-category input-group';
			}
			$html = "<div class=\"$class\" $dc>";
			$html .= SPHtml_Input::select( $this->nid, $values, $selected, false, $params );
			$html .= '</div>';

			if ( $this->presumed ) {
				$html .= '<p class="sp-cat-presumed">' . Sobi::Txt( 'EN.SET_CAT_PRESUMED' ) . '</p>';
			}
		}

		return $html;
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function ProxyLoadCategories()
	{
		if ( !count( $this->_cats ) ) {
			Sobi::$filter = explode( '.', Input::Cmd( 'method' ) == 'search' ? $this->searchOrderCatsBy : $this->orderCatsBy );
			$this->loadCategories();
			SPFactory::mainframe()
				->cleanBuffer()
				->customHeader();
			echo json_encode( [ 'categories' => $this->_cats[ Sobi::Section() ][ 'childs' ] ] );
			exit;
		}
	}

	/**
	 * @param int $fw
	 *
	 * @return string
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function pselect( int $fw = C::BOOTSTRAP5 ): string
	{
		SPFactory::header()->addJsFile( 'opt.field_category_pselect' );
		$class = $this->cssClass;
		$class .= $fw == C::BOOTSTRAP2 ? ' w-100' : C::ES;
		$params = [
			'id'    => $this->nid,
			'class' => 'spctrl-field-category ' . ( $this->required ? 'required ' : C::ES ) . $class,
		];
		$selected = $this->_selectedCats;
		$dc = $selected ? 'data-sp-content="1"' : C::ES;

		$params[ 'data' ][ 'task' ] = str_replace( 'field_', 'field.', $this->nid ) . '.loadCategories';
		$params[ 'data' ][ 'selected' ] = $selected[ 0 ] ?? 0;
		$params[ 'data' ][ 'method' ] = 'edit';
		$params[ 'data' ][ 'none' ] = '----';

		$class = 'sp-field-category spctrl-validateselectgroup';
		if ( $fw != C::BOOTSTRAP3 ) {
			$class = 'sp-field-category input-group spctrl-validateselectgroup';
		}
		$html = "<div class=\"$class\" id=\"$this->nid\" $dc>";
		$html .= SPHtml_Input::select( $this->nid, [], [], false, $params );
		$html .= '</div>';

		if ( $this->presumed ) {
			$html .= '<p class="sp-cat-presumed">' . Sobi::Txt( 'EN.SET_CAT_PRESUMED' ) . '</p>';
		}

		return $html;
	}

	/**
	 * @param int $fw
	 *
	 * @return string
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function mSelect( int $fw = C::BOOTSTRAP5 ): string
	{
		if ( count( $this->_cats ) ) {
			$values = [];
			$class = $this->cssClass;
			if ( $fw == C::BOOTSTRAP3 ) {
				$class .= ' category';
			}
			$class .= $fw == C::BOOTSTRAP2 ? ' w-100' : C::ES;
			$params = [
				'id'    => $this->nid,
				'class' => ( $this->required ? 'required ' : C::ES ) . $class,
			];
			$this->size = $this->height ? ( $this->height / 20 ) : $this->size; //compatibility
			$this->size = $this->size == C::ES ? 10 : $this->size;
			$params [ 'size' ] = $this->size;

			$this->createValues( $this->_cats, $values, Sobi::Cfg( 'category_chooser.margin_sign', '-' ) );
			$selected = $this->_selectedCats;
			$dc = $selected ? 'data-sp-content="1"' : C::ES;

			if ( is_array( $selected ) && count( $selected ) ) {
				foreach ( $selected as $i => $v ) {
					$selected[ $i ] = (string) $v;
				}
			}

			$class = 'sp-field-category';
			if ( $fw != C::BOOTSTRAP3 ) {
				$class = 'sp-field-category input-group';
			}
			$html = "<div class=\"$class\" $dc>";
			$html .= SPHtml_Input::select( $this->nid, $values, $selected, true, $params );
			$html .= '</div>';

			if ( $this->presumed ) {
				$html .= '<p class="sp-cat-presumed">' . Sobi::Txt( 'EN.SET_CAT_PRESUMED' ) . '</p>';
			}

			$opt = json_encode( [ 'id' => $this->nid, 'limit' => $this->catsMaxLimit ] );
			SPFactory::header()
				->addJsFile( 'opt.field_category' )
				->addJsCode( "SobiCore.Ready( function() { SPCategoryChooser( $opt ); } );" );

			return $html;
		}

		return C::ES;
	}

	/**
	 * @param array $cats
	 * @param array $result
	 * @param string $margin
	 * @param bool $selector
	 *
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	private function createValues( array $cats, array &$result, string $margin, bool $selector = true )
	{
		foreach ( $cats as $cat ) {
			if ( !$cat[ 'state' ] && !Sobi::Can( 'category', 'access', 'unpublished_any' ) ) {
				continue;
			}
			$params = [];
			if ( $selector || $cat[ 'type' ] == 'section' ) {
				if ( $cat[ 'type' ] == 'section' || ( count( ( $cat[ 'childs' ] ) ) && !$this->catsWithChilds ) ) {
					$params[ 'disabled' ] = 'disabled';
				}
			}
			$result[] = [
				'label'  => $margin . ' ' . $cat[ 'name' ],
				'value'  => $cat[ 'sid' ],
				'params' => $params,
			];
			if ( count( ( $cat[ 'childs' ] ) ) ) {
				$this->createValues( $cat[ 'childs' ], $result, Sobi::Cfg( 'category_chooser.margin_sign', '-' ) . $margin, $selector );
			}
		}
	}

	/**
	 * Loads the categories tree. Can be called only for the current section.
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function loadCategories()
	{
		/* frontend: read the available categories from the categories file */
		$catFilename = 'etc/categories/categories_' . Sobi::Lang( false ) . '_' . Sobi::Section();
		if ( !defined( 'SOBIPRO_ADM' ) && ( !$this->_cats || !count( $this->_cats ) ) ) {
			$catFile = SPLoader::path( $catFilename, 'front', true, 'json' );
			if ( $catFile ) {
				$this->_cats = json_decode( FileSystem::Read( $catFile ), true );
			}
		}

		/* if we do not have categories at this point, try to read the categories from the var cache */
		if ( !$this->_cats || !count( $this->_cats ) ) {
			$cachevar = 'categories_tree' . ( defined( 'SOBIPRO_ADM' ) ? '_adm' : '_front' );
			$this->_cats = SPFactory::cache()->getVar( $cachevar, (int) Sobi::Section() );

			/* if there are no categories in the var cache too */
			if ( !$this->_cats || !count( $this->_cats ) ) {
				$this->_cats = []; /* has to be of type array */
				/* recreate the categories and store them in the var cache */
				$this->travelCats( Sobi::Section(), $this->_cats, true );
				SPFactory::cache()->addVar( $this->_cats, $cachevar, (int) Sobi::Section() );

				/* frontend: write the categories into the categories file */
				if ( !defined( 'SOBIPRO_ADM' ) ) {
					$cache = json_encode( $this->_cats );
					FileSystem::Write( SPLoader::path( $catFilename, 'front', false, 'json' ), $cache );
				}
			}
		}
		Sobi::sort( $this->_cats );
	}

	/**
	 * @param string|int $sid
	 * @param array $cats
	 * @param bool $init
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function travelCats( $sid, array &$cats, bool $init = false )
	{
		$category = $init ? SPFactory::Section( $sid ) : SPFactory::Category( $sid );
		$cats = !$cats ? [] : $cats;
		if ( $category->get( 'state' ) || defined( 'SOBIPRO_ADM' ) ) {
			$name = $category->get( 'name' );
			if ( !$category->get( 'state' ) ) {
				$name = $name . ' (' . Sobi::Txt( 'UNPUBLISHED' ) . ')';
			}
			$cats[ $sid ] = [
				'sid'      => $sid,
				'state'    => $category->get( 'state' ),
				'name' => $name,
				'type'     => $category->get( 'oType' ),
				'position' => $category->get( 'position' ),
				'url'      => Sobi::Url( [ 'title' => Sobi::Cfg( 'sef.alias', true ) ? $category->get( 'nid' ) : $category->get( 'name' ), 'sid' => $category->get( 'id' ) ] ),
				'childs'   => [],
			];
			$childs = $category->getChilds( 'category' );
			if ( count( $childs ) ) {
				foreach ( $childs as $id => $name ) {
					$this->travelCats( $id, $cats[ $sid ][ 'childs' ] );
				}
			}
		}
	}

	/**
	 * Shows the field in the search form.
	 *
	 * @param bool $return return or display directly
	 *
	 * @return string
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function searchForm( $return = false )
	{
		$fw = defined( 'SOBIPRO_ADM' ) ? C::BOOTSTRAP5 : Sobi::Cfg( 'template.framework-style', C::BOOTSTRAP5 );

		Sobi::$filter = explode( '.', $this->searchOrderCatsBy );
		$this->loadCategories();
		$values = $selected = [];
		if ( count( $this->_cats ) ) {
			if ( $this->searchMethod == 'select' ) {
				$values = [ '0' => [ 'label' => Sobi::Txt( 'FMN.SEARCH_SELECT_CATEGORY' ), 'value' => C::ES ] ];
			}

			$this->createValues( $this->_cats, $values, Sobi::Cfg( 'category_chooser.margin_sign', '-' ), false );
			$selected = $this->_selected;
			if ( $selected ) {
				if ( is_numeric( $selected ) ) {
					$selected = [ $selected ];
				}
				foreach ( $selected as $i => $v ) {
					$selected[ $i ] = (string) $v;
				}
			}
		}
		$class = $this->cssClass;
		if ( $fw == C::BOOTSTRAP2 ) {
			$class .= ' w-100';
		}
		$html = C::ES;
		if ( $this->searchMethod == 'select' ) {
			$params = [
				'id'    => $this->nid,
				'class' => $class,
			];
			$html = SPHtml_Input::select( $this->nid, $values, $selected, false, $params );
		}
		else {
			if ( $this->searchMethod == 'mselect' ) {
				$params = [
					'id'    => $this->nid,
					'class' => $class,
				];
				$this->ssize = $this->searchHeight ? ( $this->searchHeight / 20 ) : $this->ssize; //compatibility
				$this->ssize = $this->ssize == C::ES ? 10 : $this->ssize;
				$params [ 'size' ] = $this->ssize;

				$html = SPHtml_Input::select( $this->nid, $values, $selected, true, $params );
			}
			else {
				if ( $this->searchMethod == 'pselect' ) {
					SPFactory::header()->addJsFile( 'opt.field_category_pselect' );
					$params = [
						'id'    => $this->nid,
						'class' => 'spctrl-field-category ' . $class,
					];
					$params[ 'data' ][ 'task' ] = preg_replace( '/_/', '.', $this->nid, 1 ) . '.loadCategories';
					$params[ 'data' ][ 'method' ] = 'search';
					$params[ 'data' ][ 'selected' ] = $selected ? $selected[ 0 ] : $selected;
					$html = SPHtml_Input::select( $this->nid, [], [], false, $params );
				}
			}
		}

		return $html;
	}

	/**
	 * Gets the data for this field, verifies them the first time.
	 * Frontend ONLY!!
	 *
	 * @param SPEntry $entry
	 * @param string $tsId
	 * @param string $request
	 *
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function submit( &$entry, $tsId = C::ES, $request = 'POST' )
	{
		$data = $this->verify( $entry, $request );

		$return = [];
		if ( count( $data ) ) {
			$return[ $this->nid ] = $data;
			//return Input::Search( $this->nid, $request );
		}

		return $return;
	}

	/**
	 * Verifies the data and returns them.
	 *
	 * @param $entry
	 * @param $request
	 *
	 * @return array
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function verify( $entry, $request )
	{
		$data = Input::Arr( $this->nid, 'post', [] );
		if ( !$data ) {
			$dataString = Input::String( $this->nid, 'post' );
			if ( strstr( $dataString, '://' ) ) {
				$data = SPFactory::config()->structuralData( $dataString );
			}
			else {
				$dataString = Input::Int( $this->nid, 'post' );
				if ( $dataString ) {
					$data = [ $dataString ];
				}
			}
		}
		else {
			if ( !( ( int ) $this->catsMaxLimit ) ) {
				$this->catsMaxLimit = 10;   //set to standard value
			}
			if ( count( $data ) > $this->catsMaxLimit && count( $data ) > 1 ) {
				$data = array_slice( $data, 0, $this->catsMaxLimit );
			}
		}
		/* check if it was required */
		$dexs = count( $data );

		/* primary category always needs to be set */
		if ( ( $this->required || $this->isPrimary ) && !$dexs && $this->method != 'fixed' ) {
			throw new SPException( SPLang::e( 'FIELD_REQUIRED_ERR', $this->name ) );
		}

		if ( $dexs ) {
			/* check if there was an adminField */
			if ( $this->adminField && $this->method != 'fixed' ) {
				if ( !Sobi:: Can( 'entry.adm_fields.edit' ) ) {
					throw new SPException( SPLang::e( 'FIELD_NOT_AUTH', $this->name ) );
				}
			}
			/* check if it was free */
			if ( !$this->isFree && $this->fee ) {
				SPFactory::payment()->add( $this->fee, $this->name, $entry->get( 'id' ), $this->fid );
			}
			/* check if it was editLimit */
			if ( $this->editLimit == 0 && !( Sobi::Can( 'entry.adm_fields.edit' ) ) ) {
				throw new SPException( SPLang::e( 'FIELD_NOT_AUTH_EXP', $this->name ) );
			}
			/* check if it was editable */
			if ( !$this->editable && !Sobi::Can( 'entry.adm_fields.edit' ) && $entry->get( 'version' ) > 1 ) {
				throw new SPException( SPLang::e( 'FIELD_NOT_AUTH_NOT_ED', $this->name ) );
			}
			if ( !$this->catsWithChilds ) {
				foreach ( $data as $cid ) {
					$cat = SPFactory::Category( $cid );
					if ( count( $cat->getChilds( 'category' ) ) ) {
						throw new SPException( SPLang::e( 'CAT_FIELD_SELECT_CAT_WITH_NO_CHILDS', $this->name ) );
					}
				}
			}
		}
		$this->setData( $data );

		return $data;
	}

	/**
	 * This function is used for the case that a field wasn't used for some reason while saving an entry.
	 * But it has to perform some operation, e.g. the category field is set to be administrative and isn't used,
	 * but it needs to pass the previously selected categories to the entry model
	 *
	 * @param SPEntry $entry
	 * @param string $request
	 *
	 * @return bool
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function finaliseSave( $entry, string $request = 'post' ): bool
	{
		if ( !$this->enabled ) {
			return false;
		}

		$cats = SPFactory::registry()->get( 'request_categories', [] );
		$cats = array_unique( array_merge( $cats, $this->cleanData( $this->method == 'fixed' ? $this->fixedCid : C::ES ) ) );
		SPFactory::registry()->set( 'request_categories', $cats );

		return true;
	}

	/**
	 * Gets the data for a field and save it in the database.
	 *
	 * @param \SPEntry $entry
	 * @param string $request
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function saveData( &$entry, $request = 'POST' )
	{
		if ( $this->enabled ) {
			if ( $this->method == 'fixed' ) {
				$fixed = $this->fixedCid;
				$fixed = explode( ',', $fixed );
				$data = [];
				if ( count( $fixed ) ) {
					foreach ( $fixed as $cid ) {
						$data[] = trim( $cid );
					}
				}
				if ( !count( $data ) ) {
					throw new SPException( SPLang::e( 'FIELD_CC_FIXED_CID_NOT_SELECTED', $this->name ) );
				}

			}
			else {
				$data = $this->verify( $entry, $request );
			}

			$db = Factory::Db();
			if ( count( $data ) ) {
				/* if we are here, we can save these data */

				$time = Input::Now();
				$IP = Input::Ip4();
				$uid = Sobi::My( 'id' );

				/* collect the needed params */
				$params = [];
				$params[ 'publishUp' ] = $entry->get( 'publishUp' ) ?? $db->getNullDate();
				$params[ 'publishDown' ] = $entry->get( 'publishDown' ) ?? $db->getNullDate();
				$params[ 'fid' ] = $this->fid;
				$params[ 'sid' ] = $entry->get( 'id' );
				$params[ 'section' ] = Sobi::Reg( 'current_section' );
				$params[ 'lang' ] = Sobi::Lang();
				$params[ 'enabled' ] = $entry->get( 'state' );
				$params[ 'params' ] = C::ES;
				$params[ 'options' ] = null;
				$params[ 'baseData' ] = Serialiser::Serialise( $data );
				$params[ 'approved' ] = $entry->get( 'approved' );
				$params[ 'confirmed' ] = $entry->get( 'confirmed' );
				/* if it is the first version, it is new entry */
				if ( $entry->get( 'version' ) == 1 ) {
					$params[ 'createdTime' ] = $time;
					$params[ 'createdBy' ] = $uid;
					$params[ 'createdIP' ] = $IP;
				}
				$params[ 'updatedTime' ] = $time;
				$params[ 'updatedBy' ] = $uid;
				$params[ 'updatedIP' ] = $IP;
				$params[ 'copy' ] = (int) !( $entry->get( 'approved' ) );

				$this->setEditLimit( $entry, $params[ 'baseData' ] );
				$params[ 'editLimit' ] = $this->editLimit;

				/* save it to the database */
				$this->saveToDatabase( $params, $entry->get( 'version' ), false );
				if ( $this->isPrimary && ( $this->method == 'select' || $this->method == 'pselect' || $this->method == 'fixed' ) ) {
					$db->update( 'spdb_object', [ 'parent' => $data[ 0 ] ], [ 'id' => $entry->get( 'id' ) ] );
				}
			}
			else {
				$this->deleteFieldData( $entry->get( 'id' ), $this->fid );
				if ( $this->isPrimary ) {
					$db->update( 'spdb_object', [ 'parent' => Sobi::Reg( 'current_section' ) ], [ 'id' => $entry->get( 'id' ) ] );
				}
			}
			$this->finaliseSave( $entry );
		}
	}

	/**
	 * @param $data
	 * @param $results
	 * @param $priorities
	 *
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function searchNarrowResults( $data, &$results, &$priorities ): array
	{
		if ( is_numeric( $data ) ) {
			$data = [ $data ];
		}
		if ( count( $data ) ) {
			$this->loadCategories();

			$categories = [];
			if ( count( $this->_cats ) ) {
				foreach ( $data as $cid ) {
					$this->getChildCategories( $this->_cats, $cid, $categories );
				}
			}
			if ( count( $categories ) ) {
				// narrowing down - it's a special method instead the regular search because we would have to handle too much data in the search
				if ( count( $results ) ) {
					foreach ( $results as $index => $sid ) {
						$relation = Factory::Db()
							->dselect( 'id', 'spdb_relations', [ 'id' => $sid, 'oType' => 'entry', 'pid' => $categories ] )
							->loadResultArray();
						if ( !count( $relation ) ) {
							unset( $results[ $index ] );
						}
					}

				} // it's a real search now - in case we hadn't anything to filter out
				else {
					$results = Factory::Db()
						->dselect( 'id', 'spdb_relations', [ 'oType' => 'entry', 'pid' => $categories ] )
						->loadResultArray();
					$priorities[ $this->priority ] = $results;
				}
			}
		}

		return $results;
	}

	/**
	 * @param $categories
	 * @param $cid
	 * @param $results
	 */
	private function getChildCategories( $categories, $cid, &$results )
	{
		foreach ( $categories as $category ) {
			if ( $cid == $category[ 'sid' ] ) {
				$results[] = $category[ 'sid' ];
				$this->categoryChilds( $results, $category[ 'childs' ] );
				break;
			}
			if ( count( $category[ 'childs' ] ) ) {
				$this->getChildCategories( $category[ 'childs' ], $cid, $results );
			}
		}
	}

	/**
	 * @param $results
	 * @param $categories
	 */
	private function categoryChilds( &$results, $categories )
	{
		foreach ( $categories as $category ) {
			$results[] = $category[ 'sid' ];
			if ( count( $category[ 'childs' ] ) ) {
				$this->categoryChilds( $results, $category[ 'childs' ] );
			}
		}
	}

	/**
	 * Gets the data for this field from $_FILES and verifies them the first time.
	 * Backend ONLY!!
	 *
	 * @param \SPEntry $entry
	 * @param string $request
	 * @param false $clone
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function validate( $entry, $request, $clone = false )
	{
		$this->verify( $entry, $request );
	}

	/**
	 * @param $revision
	 * @param $current
	 *
	 * @return array
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function compareRevisions( $revision, $current ): array
	{
		$cur = $current ? SPLang::translateObject( $current, 'name', 'category' ) : [];
		foreach ( $cur as $i => $category ) {
			$cur[ $i ] = $cur[ $i ][ 'value' ] . ' (' . $i . ')';
		}
		$cur = implode( "\n", $cur );

		$rev = $revision ? SPLang::translateObject( $revision, 'name', 'category' ) : [];
		foreach ( $rev as $i => $category ) {
			$rev[ $i ] = $rev[ $i ][ 'value' ] . ' (' . $i . ')';
		}
		$rev = implode( "\n", $rev );

		return [ 'current' => $cur, 'revision' => $rev ];
	}
}
