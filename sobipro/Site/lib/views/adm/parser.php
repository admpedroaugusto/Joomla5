<?php
/**
 * @package SobiPro Library
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
 * @created 10-Jan-2009 by Radek Suski
 * @modified 20 October 2023 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

use Sobi\C;
use Sobi\Input\Input;
use Sobi\Utils\StringUtils;

/**
 * Class SPTplParser
 */
class SPTplParser
{
	public const LABELWIDTH = 3;
	public const ELEMENTWIDTH = 9;

	/** @var bool */
	protected $tabsContentOpen = false;
	/** @var bool */
	protected $activeTab = false;
	/** @var bool */
	protected $table = true;
	/** @var bool */
	protected $loopTable = true;
	/** @var string */
	protected $thTd = 'th';
	/** @var array */
	protected $_out = [];
	/** @var bool */
	protected $loopOpen = false;
	/** @var int */
	protected $loopCol = 0;
	/** @var string */
	protected $coltype = 'lg';
	/** @var int */
	protected $base = 9;
	/** @var int */
	protected $labelwidth = self::LABELWIDTH;
	/** @var int */
	protected $elementwidth = self::ELEMENTWIDTH;
	/** @var int */
	protected $offset = self::LABELWIDTH;
	/** @var bool */
	protected $header = false;

	/** @var array */
	protected $_tickerIcons = [
		0  => 'times-circle',
		1  => 'check-circle',
		-1 => 'stop-circle',
		-2 => 'pause-circle',
	];
	/** @var string */
	protected $_checkedOutIcon = 'lock';
	/** @var array */
	protected $html = [ 'div', 'span', 'p', 'h1', 'h2', 'h3', 'h4', 'a', 'button', 'url', 'img', 'table', 'ul', 'li', 'pre', 'label', 'tr', 'th', 'td', 'code', 'i' ];
	/** @var array */
	protected $internalAttributes = [ 'condition' ];

	/**
	 * SPTplParser constructor.
	 *
	 * @param bool $table
	 */
	public function __construct( $table = false )
	{
		$this->table = $table;
	}

	/**
	 * @param $data
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function parse( $data )
	{
		if ( is_array( $data ) ) {
			foreach ( $this->internalAttributes as $attribute ) {
				if ( isset( $data[ 'attributes' ][ $attribute ] ) ) {
					unset( $data[ 'attributes' ][ $attribute ] );
				}
			}
			if ( $this->isSetTo( $data, 'type' ) ) {
				$this->openElement( $data );
				$this->parseElement( $data );
			}
			if ( $this->isSetTo( $data, 'content' ) && is_array( $data[ 'content' ] ) && count( $data[ 'content' ] ) ) {
				foreach ( $data[ 'content' ] as $element ) {
					$this->parse( $element );
				}
			}
			if ( $this->isSetTo( $data, 'type' ) ) {
				$this->closeElement( $data );
			}
		}
		echo implode( C::ES, $this->_out );
		$this->_out = [];
	}

	/**
	 * @param $element
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function parseElement( $element )
	{
		if ( !$this->isSetTo( $element, 'attributes' ) ) {
			$element[ 'attributes' ] = [];
		}
		switch ( $element[ 'type' ] ) {
			case 'key':
				if ( $element[ 'count' ] == 1 ) {
					/* only the first key gets a fieldset on top */
					$this->_out[] = '<div class="row mb-lg-3 dk-fieldset">';
					if ( $this->isSetTo( $element, 'section' ) ) {
						$offset = self::LABELWIDTH;
						$this->_out[] = "<label class=\"col-$this->coltype-$offset\">" . Sobi::Txt( "GBN.CFG.CONFIGFILE_SECTION" ) . ': ' . $element[ 'section' ] . '</label>';
					}
					$this->_out[] = '</div>';
				}
			/* no break by intention */
			case 'field':
				/* only the content? */
				if ( $this->isSetTo( $element, 'attributes' ) && $this->isSetTo( $element[ 'attributes' ], 'stand-alone', 'true' ) ) {
					$this->_out[] = $element[ 'content' ];
					break;
				}
				/* by default grid = true (false: do not use row and col-classes to get the label above the element) */
				$grid = !( array_key_exists( 'grid', $element ) && $element[ 'grid' ] == 'false' );
				$width = array_key_exists( 'width', $element[ 'args' ] ) ? (int) $element[ 'args' ][ 'width' ] : self::ELEMENTWIDTH;

				/* correction only for base 12 pages (edit entry/category pages) and if labelwidth > 0 */
				if ( $width && $this->base == 12 && $this->labelwidth > 0 ) {
					if ( $width + $this->labelwidth > 12 ) {
						$width = 12 - $this->labelwidth;
					}
				}
				if ( $this->table ) {
					$this->_out[] = '<tr>';
					$this->_out[] = '<td>';
				}
				if ( !$this->header ) {
					$row = $grid ? ' row' : C::ES;
					$hidden = C::ES;
					if ( $this->isSetTo( $element[ 'attributes' ], 'type' ) && $element[ 'attributes' ][ 'type' ] == 'hidden' ) {
						$hidden = ' hidden';
					}
					$this->_out[] = '<div class="mb-3' . $row . $hidden . '" role="group">';

					/* show description above content? */
					if ( $this->isSetTo( $element, 'help-position', 'above' ) && $this->isSetTo( $element, 'help-text' ) ) {
						/* if the helptext is above, but the labelwidth is 0, then add a potential label before the helptext*/
						$this->createLabel( $element, $grid );

						$offset = $this->isSetTo( $element, 'label' ) && strlen( $element[ 'label' ] ) ? C::ES : $this->offset;
						$this->_out[] = "<div class=\"col-$this->coltype-$this->elementwidth $offset form-text text-muted help-block above mb-1\" id=\"{$element[ 'id' ]}_helpblock\">";
						$this->_out[] = $element[ 'help-text' ];
						$this->_out[] = '</div>';
					}
				}
				if ( !( $this->isSetTo( $element, 'help-position', 'above' ) ) ) {
					$this->createLabel( $element, $grid );
				}

				if ( $this->table ) {
					$this->_out[] = '</td>';
				}

				if ( !$this->header ) {
					$warn = C::ES;
					if ( $this->isSetTo( $element[ 'attributes' ], 'warn' ) && strlen( $element[ 'attributes' ][ 'warn' ] ) ) {
						$warn = $element[ 'attributes' ][ 'warn' ];
					}
					$describedby = $this->isSetTo( $element, 'help-text' ) ? "aria-describedby=\"{$element[ 'id' ]}_helpblock\"" : C::ES;
					$col = $grid ? "col-$this->coltype-$width " : C::ES;
					$offset = ( $this->isSetTo( $element, 'help-position', 'above' ) && $this->isSetTo( $element, 'help-text' ) ) ? $this->offset : C::ES;
					$this->_out[] = "<div class=\"$col $offset $warn\" $describedby>";
				}

				/* output a specific text? */
				if ( $element[ 'args' ][ 'type' ] == 'output' ) {
					if ( $this->isSetTo( $element, 'id' ) && !( $this->isSetTo( $element[ 'args' ][ 'params' ], 'id' ) ) ) {
						$this->_out[] = "<div class=\"dk-output\" id=\"{$element['id']}\">";
					}
					else {
						if ( array_key_exists( 'content', $element ) && $element[ 'content' ] && strlen( $element[ 'content' ] ) > 90 ) {
							$this->_out[] = "<div class=\"dk-output dk-textarea\">";
						}
						else {
							$this->_out[] = "<div class=\"dk-output\">";
						}
					}
					$outclass = C::ES;
					if ( $this->isSetTo( $element[ 'args' ][ 'params' ], 'class' ) ) {
						$outclass = $element[ 'args' ][ 'params' ][ 'class' ];
					}
					elseif ( $this->isSetTo( $element[ 'attributes' ], 'class' ) ) {
						$outclass = $element[ 'attributes' ][ 'class' ];
					}

					$id = C::ES;
					if ( $this->isSetTo( $element[ 'args' ][ 'params' ], 'id' ) ) {
						$id = 'id="' . $element[ 'args' ][ 'params' ][ 'id' ] . '" ';
					}
					if ( $outclass ) {
						$this->_out[] = "<div class=\"$outclass\" $id>";
					}
					else {
						$this->_out[] = "<div $id>";
					}
					if ( $this->isSetTo( $element[ 'attributes' ], 'icons' ) ) {
						$icons = json_decode( str_replace( "'", '"', $element[ 'attributes' ][ 'icons' ] ), true );
						$element[ 'content' ] = (int) $element[ 'content' ];
						$icon = $this->isSetTo( $icons, $element[ 'content' ] ) ? $icons[ $element[ 'content' ] ] : $this->_tickerIcons[ $element[ 'content' ] ];
						$element[ 'content' ] = '<span class="fas fa-' . $icon . '" aria-hidden="true"></span>';
					}
				}

				if ( $this->table ) {
					$this->_out[] = '<td>';
				}

				$class = C::ES;
				$length = ( array_key_exists( 'length', $element[ 'attributes' ] ) ) ? $element[ 'attributes' ][ 'length' ] : C::ES; /* length wrapper for e.g. upload file elements */
				if ( is_array( $element[ 'adds' ][ 'before' ] ) && count( $element[ 'adds' ][ 'before' ] )
					|| is_array( $element[ 'adds' ][ 'after' ] ) && count( $element[ 'adds' ][ 'after' ] )
				) {
					$class = 'input-group';
				}
				if ( $length ) {
					$class .= " $length";
				}
				if ( $class ) {
					$this->_out[] = "<div class=\"$class\">";
				}
				if ( is_array( $element[ 'adds' ][ 'before' ] ) && count( $element[ 'adds' ][ 'before' ] ) ) {
					foreach ( $element[ 'adds' ][ 'before' ] as $o ) {
						if ( $this->isSetTo( $o, 'element', 'button' ) ) {
							if ( $this->isSetTo( $o, 'icon' ) ) {
								$o[ 'label' ] = '<span class="fas fa-' . $o[ 'icon' ] . '" aria-hidden="true"></span>' . $o[ 'label' ];
							}
							$data = C::ES;
							foreach ( $o as $attribute => $value ) {
								if ( !( strstr( $attribute, 'data-' ) ) ) {
									continue;
								}
								$data .= ' ' . $attribute . '="' . $value . '" ';
							}
							$this->_out[] = "<button class=\"{$o['class']}\" $data type=\"button\">{$o['label']}</button>";
						}
						else {
							$this->_out[] = "<span class=\"input-group-text\">$o</span>";
						}
					}
				}

				/** here is the right content output */
				if ( $this->isSetTo( $element[ 'attributes' ], 'aria-before' ) ) {
					$this->_out[] = '<span class="visually-hidden">' . Sobi::Txt( $element[ 'attributes' ][ 'aria-before' ] ) . '</span>';
				}
				$this->_out[] = $element[ 'content' ];
				if ( $this->isSetTo( $element[ 'attributes' ], 'aria-after' ) ) {
					$this->_out[] = '<span class="visually-hidden">' . Sobi::Txt( $element[ 'attributes' ][ 'aria-after' ] ) . '</span>';
				}

				/* if input-group: add the after elements (append) */
				if ( is_array( $element[ 'adds' ][ 'after' ] ) && count( $element[ 'adds' ][ 'after' ] ) ) {
					foreach ( $element[ 'adds' ][ 'after' ] as $o ) {
						if ( $this->isSetTo( $o, 'element', 'button' ) ) {
							if ( $this->isSetTo( $o, 'icon' ) ) {
								$o[ 'label' ] = '<span class="fas fa-' . $o[ 'icon' ] . '" aria-hidden="true"></span>' . $o[ 'label' ];
							}
							$data = C::ES;
							foreach ( $o as $attribute => $value ) {
								if ( !strstr( $attribute, 'data-' ) ) {
									continue;
								}
								$data .= ' ' . $attribute . '="' . $value . '" ';
							}
							$this->_out[] = "<button class=\"{$o['class']}\" $data type=\"button\">{$o['label']}</button>";
						}
						else {
							$this->_out[] = "<span class=\"input-group-text\">$o</span>";
						}
					}
				}

				if ( $element[ 'args' ][ 'type' ] == 'output' ) {
					$this->_out[] = '</div></div>';
				}
				if ( $class || $length ) {
					$this->_out[] = '</div>';
				}
				if ( $this->table ) {
					$this->_out[] = '</td>';
					$this->_out[] = '</tr>';
				}
				if ( !$this->header ) {
					$this->_out[] = '</div>';
					/* description on right side? */
					if ( $this->isSetTo( $element, 'help-position', 'right' ) && $this->isSetTo( $element, 'help-text' ) ) {
						$rightwidth = $this->elementwidth - $width;
						$this->_out[] = "<div class=\"col-$this->coltype-$rightwidth form-text text-muted help-block right\" id=\"{$element[ 'id' ]}_helpblock\">";
						$this->_out[] = $element[ 'help-text' ];
						$this->_out[] = '</div>';
					}
					/* error message from validation? */
					if ( $element[ 'type' ] == 'field' && $this->isSetTo( $element, 'id' ) ) {
						$this->_out[] = "<div class=\"col-$this->coltype-$this->elementwidth $this->offset feedback-container\">";
						$this->_out[] = "<div id=\"{$element[ 'id' ]}-message\" class=\"invalid-feedback\"/>";
						$this->_out[] = '</div></div>';
					}

					/* description below? */
					if ( $this->isSetTo( $element, 'help-position', 'below' ) && $this->isSetTo( $element, 'help-text' ) ) {
						$this->_out[] = "<div class=\"col-$this->coltype-$this->elementwidth $this->offset form-text text-muted help-block below\" id=\"{$element[ 'id' ]}_helpblock\">";
						$this->_out[] = $element[ 'help-text' ];
						$this->_out[] = '</div>';
					}
					$this->_out[] = '</div>';
				}
				break;
			case 'header':
				$this->header = true;
				$this->_out[] = '<div class="dk-header-title d-flex"><div class="fas fa-' . $element[ 'attributes' ][ 'icon' ] . ' fa-2x" aria-hidden="true"></div>';
				$this->_out[] = '<div class="title">' . $element[ 'attributes' ][ 'label' ] . '</div>';
				$this->_out[] = '</div>';
				break;
			case 'url':
				$containertype = C::ES;
				if ( array_key_exists( 'container', $element[ 'attributes' ] ) ) {  /* open container if set */
					$containertype = $element[ 'attributes' ][ 'container' ];
					switch ( $containertype ) {
						case 'quickicon':
							$this->_out[] = '<div class="dk-quickicon-icon d-flex align-items-end fs-quickicon">';
							break;
						default:
							$containertype = C::ES;
					}
				}
				if ( $this->isSetTo( $element[ 'attributes' ], 'image' ) ) {
					$this->_out[] = "<img src=\"{$element['attributes']['image']}\" alt=\"{$element['attributes']['label']}\" />";
				}
				if ( $this->isSetTo( $element[ 'attributes' ], 'icon' ) ) {
					$this->_out[] = "<div class=\"fas fa-{$element['attributes']['icon']}\" aria-hidden=\"true\"></div>";
				}
				if ( $containertype ) {    /* add content into the container if set */
					switch ( $containertype ) {
						case 'quickicon':
							$this->_out[] = '</div><div class="dk-quickicon-name d-flex align-items-end">';
							$this->_out[] = $element[ 'content' ];
							$this->_out[] = '</div>';
							break;
					}
				}
				else { /* if no container, close the href element, open it again and add the content */
					$this->closeElement( $element );
					$this->openElement( $element );
					$this->_out[] = $element[ 'content' ];
				}
				break;
			case 'legend':
				$class = $this->isSetTo( $element[ 'attributes' ], 'class' ) ? $element[ 'attributes' ][ 'class' ] : C::ES;
				$this->_out[] = '<div class="mb-3 row">';
				$this->_out[] = "<div class=\"col-lg-12 $class\">";
				if ( $element[ 'content' ] ) {
					foreach ( $element[ 'content' ] as $msg ) {
						$this->_out[] = '<p class="mb-0">';
						if ( $this->isSetTo( $msg, 'icon' ) ) {
							$this->_out[] = '<span class="' . $msg[ 'icon' ] . '" aria-hidden="true"></span>';
						}
						$this->_out[] = $msg[ 'value' ];
						$this->_out[] = '</p>';
					}
				}
				$this->_out[] = '</div></div>';
				break;
			case 'text':
			case 'menu':
			case 'toolbar':
				$this->_out[] = $element[ 'content' ];
				break;
		}
	}

	/**
	 * Checks if an array index is set and not null, and optional if it is set to a specific value.
	 *
	 * @param $element -> array to test
	 * @param $index -> index to the array
	 * @param null $value -> value to test (optional)
	 *
	 * @return bool
	 */
	protected function isSetTo( $element, $index, $value = null ): bool
	{
		if ( !(
			isset( $element )
			&& is_array( $element )
			&& array_key_exists( $index, $element )
			&& isset( $element[ $index ] )
		)
		) {
			return false;
		}
		if ( $value ) {
			return $element[ $index ] === $value;
		}

		return true;
	}

	/**
	 * returns its value if an array index is set and not null.
	 *
	 * @param $element -> array to test
	 * @param $index -> index to the array
	 *
	 * @return bool|string
	 */
	protected function getValue( $element, $index )
	{
		return (
			isset( $element )
			&& is_array( $element )
			&& array_key_exists( $index, $element )
			&& isset( $element[ $index ] )
		) ? $element[ $index ] : null;
	}

	/**
	 * Checks if an array index is set, an array and has items.
	 *
	 * @param $element -> array to test
	 * @param $index -> index to the array
	 *
	 * @return bool
	 */
	protected function isSetCount( $element, $index )
	{
		if ( !(
			isset( $element )
			&& is_array( $element )
			&& array_key_exists( $index, $element )
			&& isset( $element[ $index ] )
			&& is_array( $element[ $index ] )
			&& count( $element[ $index ] ) )
		) {
			return false;
		}

		return true;
	}

	/**
	 * @param $data
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function openElement( $data )
	{
		switch ( $data[ 'type' ] ) {
			case 'tabs':
				$this->tabsHeader( $data );
				break;
			case 'tab':
				if ( !$this->activeTab ) {
					$this->_out[] = '<div class="tab-pane active" id="' . $data[ 'id' ] . '">';
					$this->activeTab = true;
				}
				else {
					$this->_out[] = '<div class="tab-pane" id="' . $data[ 'id' ] . '">';
				}
				break;
			case 'fieldset':    /* data */
				if ( $this->table ) {
					$this->_out[] = '<table class="table table-striped table-bordered table-sm">';
					$this->_out[] = '<tbody>';
					$this->loopCol = 0;
				}
				$this->coltype = $this->isSetTo( $data[ 'attributes' ], 'coltype' ) ? $data[ 'attributes' ][ 'coltype' ] : 'lg';
				$this->base = $this->isSetTo( $data[ 'attributes' ], 'base' ) ? $data[ 'attributes' ][ 'base' ] : 9;
				$this->labelwidth = $this->isSetTo( $data[ 'attributes' ], 'labelwidth' ) ? $data[ 'attributes' ][ 'labelwidth' ] : self::LABELWIDTH;
				$this->offset = (int) $this->labelwidth > 0 ? "offset-$this->coltype-$this->labelwidth" : C::ES;
				$this->elementwidth = 12 - (int) $this->labelwidth;

				if ( !$this->isSetTo( $data[ 'attributes' ], 'show', 'no' ) ) {
					$fieldset = ( $this->isSetTo( $data[ 'attributes' ], 'class' ) ) ? 'dk-fieldset ' . $data[ 'attributes' ][ 'class' ] : 'dk-fieldset';
					$this->_out[] = '<div class="row mb-lg-3 ' . $fieldset . '">';
					if ( $this->isSetTo( $data, 'label' ) ) {
						$this->_out[] = "<label class=\"col-$this->coltype-3\">" . $data[ 'label' ] . '</label>';
					}
					$this->_out[] = '</div>';
				}
				break;
			case 'head':    /* table header */
				$this->thTd = 'th scope="col"';
				$this->_out[] = '<thead>';
				$this->_out[] = '<tr>';
				break;
			case 'cell':    /* table cell */
				$td = $this->thTd;
				if ( $this->loopTable && $this->loopOpen ) {
					$this->loopCol++;
					$td = $this->loopCol == 1 ? 'th scope="row"' : $this->thTd;
				}
				else {
					/* if not table header */
					if ( $td == 'td' ) {
						/* use it as fake loop to determine column 1 */
						$td = ( $this->loopCol == 0 ) ? 'th scope="row"' : $this->thTd;
						if ( $this->loopCol == 0 ) {
							$this->loopCol = 1;
						}
					}
				}
				$this->proceedCell( $data, $this->loopTable ? $td : 'div' );
				break;
			case 'header':  /* utility header above content (filter, sort, ...) */
				$class = 'dk-header btn-toolbar mb-2';
				$class = $this->isSetTo( $data[ 'attributes' ], 'class' ) ? $class . ' ' . $data[ 'attributes' ][ 'class' ] : $class;
				$this->_out[] = '<div class="' . $class . '" role="toolbar" aria-label="' . Sobi::Txt( "ACCESSIBILITY.HEADER" ) . '">';
				break;
			case 'loop':    /* table loop */
				$this->loopTable = true;
				if ( $this->isSetTo( $data[ 'attributes' ], 'table' ) ) {
					$this->loopTable = !( $data[ 'attributes' ][ 'table' ] == 'false' );
				}
				if ( $this->loopTable ) {
					$this->_out[] = '<tbody>';
				}
				$this->loopOpen = true;
				break;
			case 'loop-row':
				if ( $this->loopTable ) {
					$loopClass = $this->isSetTo( $data[ 'attributes' ], 'class' ) ? 'class ="' . $data[ 'attributes' ][ 'class' ] . '"' : C::ES;
					$this->_out[] = "<tr $loopClass>";
					$this->loopCol = 0;
				}
				break;
			case 'message':
				$this->message( $data );
				break;
			case 'notice':
				$this->message( $data, true );
				break;
			case 'hint':
				$this->hint( $data[ 'attributes' ] );
				break;
			case 'tooltip':
			case 'popover':
				$this->tooltip( $data );
				break;
			case 'pagination':
				$this->_out[] = $data[ 'content' ];
				break;
			case 'table':   /* open table */
				if ( $this->isSetTo( $data[ 'attributes' ], 'class' ) ) {
					$data[ 'attributes' ][ 'class' ] = 'table ' . $data[ 'attributes' ][ 'class' ];
				}
				else {
					$data[ 'attributes' ][ 'class' ] = 'table table-striped table-hover table-sm';
				}
				$this->loopCol = 0;
			//break; no break by intention
			default:
				if ( in_array( $data[ 'type' ], $this->html ) ) {
					$element = $data[ 'type' ];
					if ( $data[ 'type' ] == 'url' ) {
						$element = 'a';
					}
					$attributes = C::ES;
					if ( is_array( $data[ 'attributes' ] ) && count( $data[ 'attributes' ] ) ) {
						foreach ( $data[ 'attributes' ] as $attribute => $value ) {
							/* list of elements not to put directly in the HTML code */
							if ( in_array( $attribute, [ 'type', 'image', 'label', 'icon', 'container', 'trim', 'invert-condition', 'condition' ] ) ) {
								if ( !( $attribute == 'type' && in_array( $data[ 'type' ], [ 'button', 'input' ] ) ) ) {
									continue;
								}
							}
							$attributes .= " $attribute=\"$value\"";
						}
					}
					$this->_out[] = "<$element $attributes>";
				}
				break;
		}
	}

	/**
	 * @param $data
	 */
	protected function tooltip( $data )
	{
		if ( $this->isSetTo( $data[ 'attributes' ], 'condition' ) ) {
			unset( $data[ 'attributes' ][ 'condition' ] );
		}
		if ( !( $this->isSetTo( $data[ 'attributes' ], 'href' ) ) ) {
			/** in case it gets through the params */
			if ( $this->isSetTo( $data, 'href' ) ) {
				$data[ 'attributes' ][ 'href' ] = $data[ 'href' ];
				$data[ 'attributes' ][ 'target' ] = '_blank';
			}
			else {
				$data[ 'attributes' ][ 'href' ] = '#';
			}
		}
		$data[ 'attributes' ][ 'rel' ] = $data[ 'type' ];
		if ( $data[ 'type' ] == 'tooltip' ) {
			$data[ 'attributes' ][ 'title' ] = htmlspecialchars( $data[ 'content' ], ENT_COMPAT );
			$data[ 'attributes' ][ 'rel' ] = 'sp-tooltip';
		}
		else {
			if ( $data[ 'type' ] == 'popover' ) {
				$data[ 'attributes' ][ 'data-bs-title' ] = $data[ 'title' ] ? htmlspecialchars( Sobi::Txt( $data[ 'title' ] ), ENT_COMPAT ) : C::ES;
				$data[ 'attributes' ][ 'data-bs-content' ] = $data[ 'content' ] ? htmlspecialchars( $data[ 'content' ], ENT_COMPAT ) : C::ES;
				$data[ 'attributes' ][ 'data-bs-placement' ] = ( array_key_exists( 'placement', $data ) ) ? $data[ 'placement' ] : 'right';
				$data[ 'attributes' ][ 'data-bs-trigger' ] = ( array_key_exists( 'trigger', $data ) ) ? $data[ 'trigger' ] : 'hover';
				$data[ 'attributes' ][ 'data-bs-container' ] = '#SobiPro';
				if ( array_key_exists( 'class', $data ) ) {
					$data[ 'attributes' ][ 'data-bs-custom-class' ] = $data[ 'class' ];
				}
				$data[ 'attributes' ][ 'rel' ] = 'sp-popover';
			}
		}
		$element = '<a ';
		foreach ( $data[ 'attributes' ] as $attribute => $value ) {
			$element .= " $attribute=\"$value\" ";
		}
		if ( $this->isSetTo( $data, 'icon' ) ) {
			$element .= "><span class=\"fas fa-{$data['icon']}\" aria-hidden=\"true\"></span></a>";
		}
		else {
			$element .= ">{$data['title']}</a>";
		}
		$this->_out[] = $element;
	}

	/**
	 * @param array $data
	 * @param false $notice
	 *
	 * @throws \SPException
	 */
	protected function message( $data, $notice = false )
	{
		$class = $this->isSetTo( $data[ 'attributes' ], 'class' ) ? $data[ 'attributes' ][ 'class' ] : C::ES;
		$id = $this->isSetTo( $data[ 'attributes' ], 'id' ) ? $data[ 'attributes' ][ 'id' ] : C::ES;
		$alert = $notice ? 'notice' : 'alert';
		$dismissible = $this->isSetTo( $data[ 'attributes' ], 'dismiss-button', 'true' );
		$class .= $dismissible ? ' alert-dismissible' : C::ES;

		$style = $this->isSetTo( $data[ 'attributes' ], 'style' ) ? $data[ 'attributes' ][ 'style' ] . '-' : C::ES;
		$type = $this->isSetTo( $data[ 'attributes' ], 'type' ) ? $alert . '-' . $style . $data[ 'attributes' ][ 'type' ] : C::ES;

		if ( $this->isSetTo( $data[ 'attributes' ], 'label' ) ) {
			if ( $this->isSetTo( $data[ 'attributes' ], 'type' ) && strpos( $data[ 'attributes' ][ 'type' ], 'heading' ) === 0 ) {
				switch ( $data[ 'attributes' ][ 'type' ] ) {
					case 'heading1':
						$heading = 'h1';
						break;
					case 'heading2':
						$heading = 'h2';
						break;
					case 'heading3':
						$heading = 'h3';
						break;
					case 'heading4':
						$heading = 'h4';
						break;
					case 'heading5':
						$heading = 'h5';
						break;
					default:
						$heading = $data[ 'attributes' ][ 'type' ];
						break;
				}
				$this->_out[] = "<div class=\"$class\"><$heading>{$data[ 'attributes' ][ 'label' ]}</$heading></div>";
			}

			/* it's an alert message */
			else {
				$icon = C::ES;
				if ( $this->isSetTo( $data[ 'attributes' ], 'icon', 'true' ) && $type ) {
					$icontype = strpos( $data[ 'attributes' ][ 'type' ], 'success' ) !== false ? 'success' :
						( strpos( $data[ 'attributes' ][ 'type' ], 'info' ) !== false ? 'info' :
							( strpos( $data[ 'attributes' ][ 'type' ], 'warning' ) !== false ? 'warning' : 'error' ) );
					switch ( $icontype ) {
						case 'success':
							$icon = 'fas fa-hand-point-up';
							break;
						case 'info':
							$icon = 'fas fa-lightbulb';
							break;
						case 'warning':
							$icon = 'fas fa-hand-point-right';
							break;
						case 'error':
						default:
						$icon = 'fas fa-hand-point-down';
							break;
					}
					$icon = "<div class=\"$icon\" aria-hidden=\"true\"></div> ";
				}
				$this->_out[] = "<div class=\"$alert $type $class\" role=\"alert\">";
				if ( $data[ 'attributes' ][ 'type' ] != 'success' && $this->isSetTo( $data[ 'attributes' ], 'link' ) ) {
					$this->_out[] = $icon . "<a href=\"?option=com_sobipro&task={$data[ 'attributes' ][ 'link' ]}\">" . $data[ 'attributes' ][ 'label' ] . '</a>';
				}
				else {
					$this->_out[] = $icon . $data[ 'attributes' ][ 'label' ];
				}
				if ( $dismissible ) {
					$this->_out[] = '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="' . Sobi::Txt( "ACCESSIBILITY.DISMISS_MESSAGE" ) . '"></button>';
				}
				$this->_out[] = '</div>';
			}
		}

		/* no text given, get the text from the system messages */
		else {
			$attr = [];
			if ( $id == 'spctrl-message' ) {
				if ( $this->isSetTo( $data[ 'attributes' ], 'type' ) ) {
					unset( $data[ 'attributes' ][ 'type' ] );
				}
				if ( count( $data[ 'attributes' ] ) ) {
					foreach ( $data[ 'attributes' ] as $attribute => $value ) {
						$attr[] = "$attribute=\"$value\"";
					}
				}
				$attr = implode( ' ', $attr );
				$messages = SPFactory::message()->getMessages();
				$msgOut = [];
				if ( count( $messages ) ) {
					foreach ( $messages as $type => $texts ) {
						if ( count( $texts ) ) {
							$msgOut[] = "<div class=\"$alert $alert-$type alert-dismissible dk-system-alert\" role=\"alert\">";
							foreach ( $texts as $text ) {
								$msgOut[] = "$text";
							}
							$msgOut[] = '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="' . Sobi::Txt( "ACCESSIBILITY.DISMISS_MESSAGE" ) . '"></button>';
							$msgOut[] = '</div>';
						}
					}
				}
				$this->_out[] = "<div $attr>";
				if ( count( $msgOut ) ) {
					foreach ( $msgOut as $out ) {
						$this->_out[] = $out;
					}
				}
				$this->_out[] = '</div>';
			}

			/* no label and not system messages -> construct an empty alert to be filled by Javascript */
			else {
				$id = $id ? " id=\"$id\"" : C::ES;
				$this->_out[] = "<div class=\"$alert $type $class\"$id role=\"alert\">";
				if ( $dismissible ) {
					$this->_out[] = '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="' . Sobi::Txt( "ACCESSIBILITY.DISMISS_MESSAGE" ) . '"></button>';
				}
				$this->_out[] = '</div>';
			}
		}
	}

	/**
	 * @param $data
	 */
	public function closeElement( $data )
	{
		switch ( $data[ 'type' ] ) {
			case 'tabs':
				$this->tabsContentOpen = false;
			/* no tab intentionally */
			case 'tab':
				$this->_out[] = '</div>';
				break;
			case 'fieldset':
				if ( $this->table ) {
					$this->_out[] = '</tbody>';
					$this->_out[] = '</table>';
				}
				break;
			case 'head':
				$this->_out[] = '</tr>';
				$this->_out[] = '</thead>';
				$this->thTd = 'td';
				$this->loopCol = 0;
				break;
			case 'header':
				$this->_out[] = '</div>';
				$this->header = false;
				break;
			case 'loop':
				if ( $this->loopTable ) {
					$this->_out[] = '</tbody>';
				}
				$this->loopTable = true;
				$this->loopOpen = false;
				break;
			case 'loop-row':
				if ( $this->loopTable ) {
					$this->_out[] = '</tr>';
				}
				break;
			case 'link':
				$data[ 'type' ] = 'a';
			default:
				if ( in_array( $data[ 'type' ], $this->html ) ) {
					$element = $data[ 'type' ];
					if ( $data[ 'type' ] == 'url' ) {
						$element = 'a';
					}
					$this->_out[] = "</$element>";
				}
				break;
		}
	}

	/**
	 * Parses a table cell.
	 *
	 * @param $cell
	 * @param string $span
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 * @throws \Exception
	 */
	public function proceedCell( $cell, $span = C::ES )
	{
		$proceeded = false;
		$span = $this->isSetTo( $cell[ 'attributes' ], 'element' ) ? $cell[ 'attributes' ][ 'element' ] : $span;
		if ( $cell[ 'type' ] == 'text' ) {
			$this->parseElement( $cell );

			return;
		}
		if ( $cell[ 'type' ] == 'tooltip' || $cell[ 'type' ] == 'popover' ) {
			$this->tooltip( $cell );

			return;
		}
		$data = C::ES;
		if ( $this->isSetCount( $cell, 'attributes' ) ) {
			foreach ( $cell[ 'attributes' ] as $attribute => $value ) {
				if ( !strstr( $attribute, 'data-' ) ) {
					continue;
				}
				$data .= ' ' . $attribute . '="' . $value . '" ';
			}
		}
		$params = C::ES;
		if ( $this->isSetTo( $cell[ 'attributes' ], 'type', 'button' ) ) {
			$params = 'type="button" role="button"';
		}
		if ( $this->isSetTo( $cell[ 'attributes' ], 'class' ) ) {
			$class = $cell[ 'attributes' ][ 'class' ];
			$this->_out[] = "<$span $data class=\"$class\" $params>";
		}
		elseif ( $span ) {
			$this->_out[] = "<$span $data $params>";
		}

		$type = $this->isSetTo( $cell[ 'attributes' ], 'type' ) ? $cell[ 'attributes' ][ 'type' ] : 'text';
		switch ( $type ) {
			/** no break here - continue */
			case 'btn':
			case 'text':
			case 'link':
			case 'button':
				if ( $type == 'link' ) {
					$class = $id = $arialabel = $target = $role = C::ES;
					if ( $this->isSetTo( $cell[ 'attributes' ], 'link-class' ) ) {
						$class = "class=\"{$cell['attributes']['link-class']}\"";
					}
					if ( $this->isSetTo( $cell[ 'attributes' ], 'aria-label' ) ) {
						$arialabel = "aria-label=\"{$cell['attributes']['aria-label']}\"";
					}
					if ( $this->isSetTo( $cell[ 'attributes' ], 'link-id' ) ) {
						$id = "id=\"{$cell['attributes']['link-id']}\"";
					}
					if ( $this->isSetTo( $cell[ 'attributes' ], 'target' ) ) {
						$target = "target=\"{$cell['attributes']['target']}\"";
					}
					if ( $this->isSetTo( $cell[ 'attributes' ], 'link-role' ) ) {
						$role = "role=\"{$cell['attributes']['link-role']}\"";
					}
					$this->_out[] = "<a href=\"{$cell['link']}\" $class $id $target $arialabel $role>";
				}

				if ( $this->isSetTo( $cell[ 'attributes' ], 'aria-before' ) ) {
					$this->_out[] = '<span class="visually-hidden">' . Sobi::Txt( $cell[ 'attributes' ][ 'aria-before' ] ) . '</span>';
				}
				if ( $this->isSetTo( $cell[ 'attributes' ], 'icon' ) ) {
					$this->_out[] = '<span class="fas fa-' . $cell[ 'attributes' ][ 'icon' ] . '" aria-hidden="true"></span>';
				}
				if ( $this->isSetTo( $cell[ 'attributes' ], 'aria-after' ) ) {
					$this->_out[] = '<span class="visually-hidden">' . Sobi::Txt( $cell[ 'attributes' ][ 'aria-after' ] ) . '</span>';
				}

				if ( $type == 'text' ) {
					/* if to show a date */
					if ( $this->isSetTo( $cell[ 'attributes' ], 'dateFormat' ) ) {
						$value = $cell[ 'content' ] ? strtotime( $cell[ 'content' ] ) : C::ES;
						$offset = 0;
						if ( !$this->isSetTo( $cell[ 'attributes' ], 'addOffset', false ) ) {
							$value = strtotime( $cell[ 'content' ] . 'UTC' );
							$offset = SPFactory::config()->getTimeOffset();
						}

						if ( substr( $cell[ 'attributes' ][ 'dateFormat' ], 0, 4 ) === "cfg:" ) {
							$format = Sobi::Cfg( 'date.' . substr( $cell[ 'attributes' ][ 'dateFormat' ], 4 ), SPC::DEFAULT_DATE );
						}
						else {
							$format = $cell[ 'attributes' ][ 'dateFormat' ];
						}
						$cell[ 'content' ] = SPFactory::config()->date( $value + $offset, C::ES, $format );
					}
				}
				$hasLabel = false;
				if ( $this->isSetTo( $cell[ 'attributes' ], 'label' ) ) {
					$this->_out[] = $cell[ 'attributes' ][ 'label' ];
					$hasLabel = true;
				}
				if ( $this->isSetTo( $cell, 'label' ) ) {
					$class = C::ES; //if label in cell directly (with optional class) add a span as it could be a label/value pair
					if ( $this->isSetTo( $cell[ 'attributes' ], 'class' ) ) {
						$class = "class=\"{$cell['attributes']['class']}Label\"";
					}
					$this->_out[] = "<span $class>{$cell['label']}</span>";
					$hasLabel = true;
				}
				if ( $this->isSetTo( $cell[ 'content' ], 'element', 'button' ) ) {
					$this->renderButton( $cell[ 'content' ] );
				}
				elseif ( $cell[ 'content' ] ) {
					if ( $hasLabel ) {
						$this->_out[] = ' ' . $cell[ 'content' ];
					}
					else {
						$this->_out[] = $cell[ 'content' ];
					}
				}
				if ( $type == 'link' ) {
					$this->_out[] = "</a>";
				}
				/* a button within a text element */
				if ( $type == 'btn' ) {
					$btnclass = $this->isSetTo( $cell[ 'attributes' ], 'btnclass' ) ? $cell[ 'attributes' ][ 'btnclass' ] : C::ES;
					$btnlink = $this->isSetTo( $cell[ 'attributes' ], 'task' ) ? "href=\"?option=com_sobipro&task={$cell['attributes']['task']}\"" : C::ES;
					$this->_out[] = "<a class=\"btn $btnclass\" $btnlink>";
					if ( $this->isSetTo( $cell[ 'attributes' ], 'btnicon' ) ) {
						$this->_out[] = '<span class="fas fa-' . $cell[ 'attributes' ][ 'btnicon' ] . '" aria-hidden="true"></span>';
					}
					$this->_out[] = "</a>";
				}
				break;
			case 'image':
				$this->_out[] = "<img src=\"{$cell['link']}\" alt=\"\" />";
				break;
			case 'ordering':
				if ( $this->isSetTo( $cell[ 'attributes' ], 'label' ) ) {  /* if table header */
					$this->_out[] = $cell[ 'attributes' ][ 'label' ];
					$this->_out[] = '<button class="btn btn-secondary btn-xs ms-1" name="spReorder" rel="' . $cell[ 'attributes' ][ 'rel' ] . '" aria-label="' . Sobi::Txt( "ACCESSIBILITY.REORDER" ) . '">';
					$this->_out[] = '<span class="fas fa-sort" aria-hidden="true"></span>';
					$this->_out[] = '</button>';
				}
				else {  /* table body -> create the thingy */
					$orderingId = rand();
					$this->_out[] = '<div class="input-group input-group-sm dk-reorder" id="sp-reorder-' . $orderingId . '">';
					if ( $this->isSetCount( $cell, 'childs' ) ) {
						foreach ( $cell[ 'childs' ] as $child ) {
							$this->proceedCell( $child, C::ES );
						}
					}
					$this->_out[] = SPHtml_Input::text( $cell[ 'attributes' ][ 'name' ], $cell[ 'content' ], [ 'class' => 'form-control form-control-sm', 'data-spctrl' => 'ordering', 'aria-label' => Sobi::Txt( "ACCESSIBILITY.ORDERING" ) ] );
					$this->_out[] = '</div>';
					$proceeded = true;  /* children are already proceeded */
				}
				break;
			case 'checkbox':
				$cell = $this->checkbox( $cell );
				break;
			case 'ticker':
				$cell = $this->ticker( $cell );
				break;
			case 'statusticker':        /* ticker without link */
				$cell = $this->statusticker( $cell );
				break;
		}
		if ( !$proceeded && ( $this->isSetCount( $cell, 'childs' ) ) ) {
			foreach ( $cell[ 'childs' ] as $child ) {
				$this->proceedCell( $child, 'div' );
			}
		}
		/* remove scope parameters from closing element */
		$span = str_replace( 'scope="row"', C::ES, $span );
		$span = str_replace( 'scope="col"', C::ES, $span );
		$this->_out[] = "</$span>";
	}

	/**
	 * A state represented by 2 icons in a table with a link to change the state.
	 *
	 * @param $cell
	 *
	 * @return mixed
	 * @throws SPException
	 */
	public function ticker( $cell )
	{
		$index = $cell[ 'content' ];
		$aOpen = false;
		$color = C::ES;

		if ( $index == 127 ) {    // a wrong data from Imex
			$index = 0;
		}

		$linkClass = $this->isSetTo( $cell[ 'attributes' ], 'link-class' ) ? $cell[ 'attributes' ][ 'link-class' ] : C::ES;

		$linkId = $this->isSetTo( $cell[ 'attributes' ], 'link-id' ) ? $cell[ 'attributes' ][ 'link-id' ] : null;
		$linkId = isset( $linkId ) ? ' id="' . $linkId . '"' : C::ES;

		$timeTicker = ( array_key_exists( 'valid-until', $cell[ 'attributes' ] ) ) || ( array_key_exists( 'valid-since', $cell[ 'attributes' ] ) );

		$linkLabels = [];
		if ( $this->isSetTo( $cell[ 'attributes' ], 'aria-labels' ) ) {
			$linkLabels = json_decode( str_replace( "'", '"', $cell[ 'attributes' ][ 'aria-labels' ] ), true );
		}
		if ( count( $linkLabels ) ) {
			if ( $index != C::ES && $index != null ) {
				$label = $this->isSetTo( $linkLabels, $index ) ? $linkLabels[ $index ] : C::ES;
			}
			else {
				$label = $this->isSetTo( $linkLabels, 0 ) ? $linkLabels[ 0 ] : C::ES;
			}
		}
		$linkLabel = isset ( $label ) ? 'aria-label="' . Sobi::Txt( $label ) . '"' : C::ES;

		if ( $timeTicker ) {
			$now = gmdate( 'U' );
			/** is expired ? */
			if ( $this->isSetTo( $cell[ 'attributes' ], 'valid-until' ) && strtotime( $cell[ 'attributes' ][ 'valid-until' ] . ' UTC' ) < $now && strtotime( $cell[ 'attributes' ][ 'valid-until' ] ) > 0 ) {
				$index = -1;
				$aOpen = true;
				$txt = Sobi::Txt( 'ROW_EXPIRED', gmdate( Sobi::Cfg( 'date.publishing_format', SPC::DEFAULT_DATE ), strtotime( $cell[ 'attributes' ][ 'valid-until' ] . 'UTC' ) + SPFactory::config()->getTimeOffset() ) );
				$this->_out[] = "<a href=\"#\" rel=\"sp-tooltip\" data-bs-container=\"#SobiPro\" data-bs-original-title=\"$txt\" class=\"$linkClass\" $linkLabel role=\"button\">";
				$color = 'dk-text-orange';
			}
			/** is pending ? */
			else {
				if ( ( $this->isSetTo( $cell[ 'attributes' ], 'valid-since' ) && strtotime( $cell[ 'attributes' ][ 'valid-since' ] . ' UTC' ) > $now ) && $index == 1 ) {
					$index = -2;
					$aOpen = true;
					$txt = Sobi::Txt( 'ROW_PENDING', gmdate( Sobi::Cfg( 'date.publishing_format', SPC::DEFAULT_DATE ), strtotime( $cell[ 'attributes' ][ 'valid-since' ] . 'UTC' ) + SPFactory::config()->getTimeOffset() ) );
					$this->_out[] = "<a href=\"#\" rel=\"sp-tooltip\" data-bs-container=\"#SobiPro\" data-bs-original-title=\"$txt\" class=\"$linkClass\" $linkLabel role=\"button\">";
					$color = 'dk-text-green';
				}
				else {
					if ( $index < 0 ) {
						$color = 'dk-text-gray';
						$txt = 'Locked';
						if ( $this->isSetTo( $cell[ 'attributes' ], 'status-text' ) ) {
							$txt = json_decode( str_replace( "'", '"', $cell[ 'attributes' ][ 'status-text' ] ), true );
							$txt = Sobi::Txt( $txt[ $index ] );
							$color = 'dk-text-azure';
						}
						$aOpen = true;
						$this->_out[] = "<a href=\"#\" rel=\"sp-tooltip\" data-bs-container=\"#SobiPro\" data-bs-original-title=\"$txt\" class=\"$linkClass\" $linkId $linkLabel role=\"button\">";
					}
					else {
						if ( $this->isSetTo( $cell, 'link' ) ) {
							$cell[ 'link' ] = $cell[ 'link' ] . '&t=' . microtime( true );
							$aOpen = true;
							$this->_out[] = "<a href=\"{$cell['link']}\" class=\"$linkClass\" $linkId $linkLabel role=\"button\">";
						}
					}
				}
			}
		}
		else {
			if ( $this->isSetTo( $cell, 'link' ) ) {
				if ( $index < 0 ) {
					$txt = C::ES;
					if ( $this->isSetTo( $cell[ 'attributes' ], 'status-text' ) ) {
						$txt = json_decode( str_replace( "'", '"', $cell[ 'attributes' ][ 'status-text' ] ), true );
						$txt = Sobi::Txt( $txt[ $index ] );
					}
					$aOpen = true;
					if ( $txt ) {
						$this->_out[] = "<a href=\"#\" rel=\"sp-tooltip\" data-bs-container=\"#SobiPro\" data-bs-original-title=\"$txt\" class=\"$linkClass\" $linkId $linkLabel role=\"button\">";
					}
					else {
						$this->_out[] = "<a href=\"#\" class=\"$linkClass\" $linkId $linkLabel role=\"button\">";
					}
				}
				else {
					$aOpen = true;
					$cell[ 'link' ] = $cell[ 'link' ] . '&t=' . microtime( true );
					$this->_out[] = "<a href=\"{$cell['link']}\" class=\"$linkClass\" $linkId $linkLabel role=\"button\">";
				}
			}
			else {
				if ( array_key_exists( 'link', $cell ) ) {
					$this->_out[] = "<span class=\"dk-status-$index $linkClass\" $linkLabel>";
				}
				else {
					$aOpen = true;
					if ( $this->isSetTo( $cell[ 'attributes' ], 'status-text' ) ) {
						$txt = json_decode( str_replace( "'", '"', $cell[ 'attributes' ][ 'status-text' ] ), true );
						$txt = Sobi::Txt( $txt[ $index ] );
						$this->_out[] = "<a href=\"#\" rel=\"sp-tooltip\" data-bs-container=\"#SobiPro\" data-bs-original-title=\"$txt\" class=\"dk-status-$index $linkClass\" $linkId $linkLabel role=\"button\">";
					}
					else {
						$this->_out[] = "<a href=\"#\" class=\"dk-status-$index $linkClass\" $linkId $linkLabel role=\"button\">";
					}
				}
			}
		}
		$icons = [];
		if ( $this->isSetTo( $cell[ 'attributes' ], 'icons' ) ) {
			$icons = json_decode( str_replace( "'", '"', $cell[ 'attributes' ][ 'icons' ] ), true );
		}
		if ( !( count( $icons ) ) ) {
			$icons = $this->_tickerIcons;
		}
		if ( $index != C::ES && $index != null ) {
			$icon = $this->isSetTo( $icons, $index ) ? $icons[ $index ] : $this->_tickerIcons[ $index ];
		}
		else {
			$icon = $this->isSetTo( $icons, 0 ) ? $icons[ 0 ] : C::ES;
		}
		$fa = $this->isSetTo( $cell[ 'attributes' ], 'fa' ) ? $cell[ 'attributes' ][ 'fa' ] : 'fas';

		$iconColors = [];
		if ( $this->isSetTo( $cell[ 'attributes' ], 'colors' ) ) {
			$iconColors = json_decode( str_replace( "'", '"', $cell[ 'attributes' ][ 'colors' ] ), true );
		}
		if ( count( $iconColors ) && !$color ) {
			if ( $index != C::ES && $index != null ) {
				$color = $this->isSetTo( $iconColors, $index ) ? $iconColors[ $index ] : 'dk-text-gray';
			}
			else {
				$color = $this->isSetTo( $iconColors, 0 ) ? $iconColors[ 0 ] : C::ES;
			}
		}

		$this->_out[] = '<span class="' . $fa . ' fa-' . $icon . ' ' . $color . '" aria-hidden="true"></span>';
		if ( $aOpen ) {
			$this->_out[] = "</a>";
		}

		return $cell;
	}

	/**
	 * A state represented by 2 icons in a table.
	 *
	 * @param $cell
	 *
	 * @return mixed
	 */
	public function statusticker( $cell )
	{
		$index = $cell[ 'content' ];
		$color = C::ES;

		if ( $index == 127 ) {    // a wrong data from Imex
			$index = 0;
		}

		$icons = [];
		if ( $this->isSetTo( $cell[ 'attributes' ], 'icons' ) ) {
			$icons = json_decode( str_replace( "'", '"', $cell[ 'attributes' ][ 'icons' ] ), true );
		}
		if ( !count( $icons ) ) {
			$icons = $this->_tickerIcons;
		}
		if ( $index != C::ES && $index != null ) {
			$icon = $this->isSetTo( $icons, $index ) ? $icons[ $index ] : $this->_tickerIcons[ $index ];
		}
		else {
			$icon = $this->isSetTo( $icons, 0 ) ? $icons[ 0 ] : C::ES;
		}
		$fa = $this->isSetTo( $cell[ 'attributes' ], 'fa' ) ? $cell[ 'attributes' ][ 'fa' ] : 'fas';

		$iconColors = [];
		if ( $this->isSetTo( $cell[ 'attributes' ], 'colors' ) ) {
			$iconColors = json_decode( str_replace( "'", '"', $cell[ 'attributes' ][ 'colors' ] ), true );
		}
		if ( count( $iconColors ) && !$color ) {
			if ( $index != C::ES && $index != null ) {
				$color = $this->isSetTo( $iconColors, $index ) ? $iconColors[ $index ] : 'dk-text-gray';
			}
			else {
				$color = $this->isSetTo( $iconColors, 0 ) ? $iconColors[ 0 ] : C::ES;
			}
		}

		$this->_out[] = '<span class="' . $fa . ' fa-' . $icon . ' ' . $color . '" aria-hidden="true"></span>';

		return $cell;
	}


	/**
	 * @param $cell
	 *
	 * @return array|mixed
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function checkbox( $cell )
	{
		$label = $this->isSetTo( $cell[ 'attributes' ], 'aria-label' ) ? $cell[ 'attributes' ][ 'aria-label' ] : C::ES;
		$linkLabel = isset ( $label ) ? 'aria-label="' . Sobi::Txt( $label ) . '"' : C::ES;

		/* First let's check if it is not checked out (don't show own checked out) */
		if ( $this->isSetTo( $cell[ 'attributes' ], 'checked-out-by' ) && $this->isSetTo( $cell[ 'attributes' ], 'checked-out-time' ) /*&& $cell[ 'attributes' ][ 'checked-out-by' ] != Sobi::My( 'id' )*/ && strtotime( $cell[ 'attributes' ][ 'checked-out-time' ] ) > gmdate( 'U' ) ) {
			if ( $this->isSetTo( $cell[ 'attributes' ], 'checked-out-icon' ) ) {
				$icon = $cell[ 'attributes' ][ 'checked-out-icon' ];
			}
			else {
				$icon = $this->_checkedOutIcon;
			}
			$cell[ 'attributes' ][ 'checked-out-time' ] = gmdate( Sobi::Cfg( 'date.publishing_format', SPC::DEFAULT_DATE ), strtotime( $cell[ 'attributes' ][ 'checked-out-time' ] . 'UTC' ) + SPFactory::config()->getTimeOffset() );
			$user = SPUser::getInstance( $cell[ 'attributes' ][ 'checked-out-by' ] );

			$txt = Sobi::Txt( 'CHECKED_OUT', $user->get( 'name' ), $cell[ 'attributes' ][ 'checked-out-time' ] );
			$this->_out[] = "<a href=\"#\" rel=\"sp-tooltip\" data-bs-container=\"#SobiPro\" data-bs-original-title=\"$txt\">";
			$color = 'dk-text-red';

			$this->_out[] = '<span class="fas fa-' . $icon . ' ' . $color . '" aria-hidden="true"></span>';
			$this->_out[] = '</a>';

			return $cell;
		}
		else {
			if ( $this->isSetTo( $cell[ 'attributes' ], 'locked', true ) ) {
				$icon = $this->isSetTo( $cell[ 'attributes' ], 'locked-icon' ) ? $cell[ 'attributes' ][ 'locked-icon' ] : $this->_checkedOutIcon;

				$txt = $this->isSetTo( $cell[ 'attributes' ], 'locked-text' ) ? $cell[ 'attributes' ][ 'locked-text' ] : $this->_checkedOutIcon;
				$this->_out[] = "<a href=\"#\" rel=\"sp-tooltip\" data-bs-container=\"#SobiPro\" data-bs-original-title=\"$txt\">";
				$color = 'dk-text-azure';

				$this->_out[] = '<span class="fas fa-' . $icon . ' ' . $color . '" aria-hidden="true"></span>';
				$this->_out[] = '</a>';

				return $cell;
			}
		}
		$type = $this->isSetTo( $cell[ 'attributes' ], 'input-type' ) ? $cell[ 'attributes' ][ 'input-type' ] : 'checkbox';
		$class = 'class="form-check-input"';

		if ( $this->isSetTo( $cell[ 'attributes' ], 'rel' ) ) {
			$this->_out[] = "<input type=\"$type\" $class name=\"spToggle\" value=\"1\" rel=\"{$cell[ 'attributes' ][ 'rel' ]}\" $linkLabel/>";

			return $cell;
		}

		else {
			$id = $idS = $checked = null;
			if ( $this->isSetTo( $cell[ 'attributes' ], 'checked' ) ) {
				$checked = 'checked="checked" ';
			}
			$multiple = $this->isSetTo( $cell[ 'attributes' ], 'multiple', 'false' ) ? C::ES : '[]';

			if ( $this->isSetTo( $cell, 'label' ) ) {
				$this->_out[] = '<div class="form-check">';
				$id = uniqid( StringUtils::Nid( strtolower( $cell[ 'label' ] ) ) );
				$idS = 'id="' . $id . '" ';
			}
			$this->_out[] = "<input type=\"$type\" $class name=\"{$cell[ 'attributes' ][ 'name' ]}$multiple\" value=\"{$cell[ 'content' ]}\" $checked $idS $linkLabel/>";
			if ( $this->isSetTo( $cell, 'label' ) ) {
				$this->_out[] = '<label for="' . $id . '" class="form-label">' . $cell[ 'label' ] . '</label>';
				$this->_out[] = '</div>';
			}

			return $cell;
		}
	}

	/**
	 * @param $element
	 */
	public function tabsHeader( $element )
	{
		$this->tabsContentOpen = true;
		$this->activeTab = false;
		$this->_out[] = '<nav><ul class="nav nav-tabs" data-spctrl="nav-tabs" role="tablist">';
		$active = false;
		if ( $this->isSetCount( $element, 'content' ) ) {
			foreach ( $element[ 'content' ] as $tab ) {
				$spctrl = $this->getValue( $tab[ 'attributes' ], 'data-spctrl' );
				$spctrl = $spctrl ? "data-spctrl=\"$spctrl\"" : C::ES;
				if ( !$active ) {
					$active = true;
					$this->_out[] = "<li class=\"nav-item\" $spctrl><a href=\"#{$tab[ 'id' ]}\" class=\"nav-link active\" data-bs-toggle=\"tab\" role=\"tab\">{$tab[ 'label' ]}</a></li>";
				}
				else {
					$this->_out[] = "<li class=\"nav-item\" $spctrl><a href=\"#{$tab['id']}\" class=\"nav-link\" data-bs-toggle=\"tab\" role=\"tab\">{$tab['label']}</a> </li>";
				}
			}
		}

		$this->_out[] = '</ul></nav>';
		$class = $this->isSetCount( $element, 'attributes' ) && $this->isSetTo( $element[ 'attributes' ], 'class' ) ? $element[ 'attributes' ][ 'class' ] : C::ES;
		$this->_out[] = "<div class=\"tab-content responsive $class\">";
	}

	/**
	 * @param $button
	 * @param false $list
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function renderButton( $button, $list = false )
	{
		$relation = $relationS = $href = C::ES;
		$class = $this->getValue( $button, 'class' );
		if ( $this->isSetTo( $button, 'task' ) ) {
			$relation = $button[ 'task' ];
		}
		$ariaLabel = $this->isSetTo( $button, 'aria-label' ) ? "aria-label=\"{$button[ 'aria-label' ]}\"" : C::ES;
		$label = $button[ 'label' ];
		$target = $this->isSetTo( $button, 'target' ) ? "target=\"{$button['target']}\"" : C::ES;
		if ( $this->isSetCount( $button, 'buttons' ) ) {
			$this->_out[] = '<div class="btn-group">';
			$this->_out[] = "<a href=\"$href\" class=\"btn $class\" $target rel=\"$relation\">";
			if ( !( $this->isSetTo( $button, 'icon' ) ) ) {
				$icon = 'cog';
			}
			else {
				$icon = $button[ 'icon' ];
			}
			if ( $icon != 'none' ) {
				$this->_out[] = '<span class="fas fa-' . $icon . '" aria-hidden="true"></span>&nbsp;&nbsp;' . $label;
			}
			else {
				$this->_out[] = $label;
			}
			$this->_out[] = '</a>';
			$this->_out[] = '<button class="btn dropdown-toggle" data-bs-toggle="dropdown"><span class="fas fa-caret-down" aria-hidden="true"></span>&nbsp;&nbsp;</button>';
			$this->_out[] = '<div class="dropdown-menu" id="' . StringUtils::Nid( $button[ 'task' ] ) . '">';
			$this->_out[] = '<ul class="nav nav-stacked SpDropDownBt">';
			if ( $this->isSetCount( $button, 'buttons' ) ) {
				foreach ( $button[ 'buttons' ] as $bt ) {
					$this->renderButton( $bt, true );
				}
			}
			$this->_out[] = '</ul>';
			$this->_out[] = '</div>';
			$this->_out[] = '</div>';
		}
		elseif ( !$list ) {
			if ( $relation || $href ) {
				$this->_out[] = "<a href=\"$href\" rel=\"$relation\" class=\"btn $class\" $target $ariaLabel>";
			}
			else {
				if ( $this->isSetTo( $button, 'rel' ) ) {
					$relationS = "rel=\"{$button['rel']}\"";
				}
				$this->_out[] = "<div class=\"btn $class\" $relationS $target $ariaLabel role=\"button\">";
			}
			if ( !( $this->isSetTo( $button, 'icon' ) ) ) {
				$icon = 'cog';
			}
			else {
				$icon = $button[ 'icon' ];
			}
			$label = $label ? '&nbsp;&nbsp;' . $label : C::ES;
			$this->_out[] = '<span class="fas fa-' . $icon . '" aria-hidden="true"></span>' . $label;
			if ( $relation || $href ) {
				$this->_out[] = '</a>';
			}
			else {
				$this->_out[] = '</div>';
			}
		}
		elseif ( $button[ 'element' ] == 'nav-header' || $button[ 'element' ] == 'dropdown-header' ) {
			$this->_out[] = '<span class="dropdown-header">' . $button[ 'label' ] . '</span>';
		}
		else {
			$this->_out[] = "<li><a href=\"$href $target\" rel=\" $relation \">";
			if ( !$this->isSetTo( $button, 'icon' ) ) {
				$icon = 'cog';
			}
			else {
				$icon = $button[ 'icon' ];
			}
			$label = ( $label ) ? '&nbsp;&nbsp;' . $label : C::ES;
			$this->_out[] = '<span class="fas fa-' . $icon . '" aria-hidden="true"></span>' . $label;
			$this->_out[] = '</a></li>';
		}
	}

	/**
	 * @param $data
	 *
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function hint( $data ): void
	{
		$this->_out[] = "<div class=\"px-5 py-5 my-5 text-center\">";
		if ( $this->isSetTo( $data, 'icon' ) ) {
			$this->_out[] = "<span class=\"fas fa-7x mb-4 fa-{$data['icon']}\" aria-hidden=\"true\"></span></a>";
		}
		if ( $this->isSetTo( $data, 'label' ) ) {
			$this->_out[] = "<h1 class=\"display-5 fw-semibold\">{$data[ 'label' ]}</h1>";
		}
		$this->_out[] = '<div class="col-lg-8 mx-auto">';
		if ( $this->isSetTo( $data, 'description' ) ) {
			$this->_out[] = '<p class="lead mb-4">' . $data[ 'description' ] . '</p>';
		}
		if ( $this->isSetTo( $data, 'help' ) || $this->isSetTo( $data, 'config' ) || $this->isSetTo( $data, 'btn1' ) || $this->isSetTo( $data, 'btn2' ) || $this->isSetTo( $data, 'btn3' ) ) {
			$this->_out[] = '<div class="d-grid gap-2 d-sm-flex justify-content-sm-center">';
			if ( $this->isSetTo( $data, 'config' ) ) {
				$this->_out[] = "<a href=\"?option=com_sobipro&task={$data['config']}\" class=\"btn btn-primary btn-lg px-4 me-sm-3\">" . Sobi::Txt( 'HINT.CONFIG' ) . '</a>';
			}

			$this->isSetTo( $data, 'btn1' ) ? $this->getHintButton( $data[ 'btn1' ] ) : null;
			$this->isSetTo( $data, 'btn2' ) ? $this->getHintButton( $data[ 'btn2' ] ) : null;
			$this->isSetTo( $data, 'btn3' ) ? $this->getHintButton( $data[ 'btn3' ] ) : null;

			if ( $this->isSetTo( $data, 'help' ) ) {
				$this->_out[] = "<a href=\"https://www.sigsiu.net/help_screen/{$data['help']}\" class=\"btn btn-outline-secondary btn-lg px-4\" target=\"_blank\" >" . Sobi::Txt( 'HINT.MORE' ) . '</a>';
			}
			$this->_out[] = '</div>';
		}
		$this->_out[] = '</div></div>';
	}

	/**
	 * @param $button
	 *
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function getHintButton( $button ): void
	{
		$btn = json_decode( str_replace( "'", '"', $button ), true );
		if ( $this->isSetTo( $btn, 'task' ) ) {
			$caption = Sobi::Txt( $this->isSetTo( $btn, 'caption' ) ? $btn[ 'caption' ] : 'HINT.NEW' );
			$paramslist = $this->isSetTo( $btn, 'param' ) ? $btn[ 'param' ] : C::ES;
			$params = C::ES;
			if ( $paramslist ) {
				$paramslist = explode( ':', $paramslist );
				foreach ( $paramslist as $param ) {
					switch ( $param ) {
						case 'section':
							$params .= '&sid=' . Sobi::Section();
							break;
						case 'sid':
							$params .= '&sid=' . Input::Sid();
							break;
					}
				}
			}
			$class = $btn[ 'class' ] ?? C::ES;
			$this->_out[] = "<a href=\"?option=com_sobipro&task={$btn['task']}$params\" class=\"btn btn-primary btn-lg px-4 me-sm-3 $class\">" . $caption . '</a>';
		}
	}

	/**
	 * @param $element
	 * @param bool $grid
	 *
	 * @throws \Sobi\Error\Exception
	 */
	protected function createLabel( $element, bool $grid )
	{
		/* has it a label? */
		if ( $this->isSetTo( $element, 'label' ) && strlen( $element[ 'label' ] ) ) {
			if ( !( $this->isSetTo( $element, 'id' ) ) ) {
				$element[ 'id' ] = $this->isSetTo( $element[ 'attributes' ], 'id' ) ? $element[ 'attributes' ][ 'id' ] : StringUtils::Nid( $element[ 'label' ] );
			}

			/* show description as popup? */
			if ( $this->isSetTo( $element, 'help-text' ) ) {
				if ( $this->isSetTo( $element, 'help-position', 'popup' ) ) {
					$element[ 'label' ] = '<a href="#" rel="sp-popover" data-bs-trigger="hover" data-bs-container="#SobiPro" data-bs-placement="top" data-bs-title="' . htmlspecialchars( $element[ 'label' ], ENT_COMPAT ) . '" data-bs-content="' . htmlspecialchars( $element[ 'help-text' ], ENT_COMPAT ) . '">' . $element[ 'label' ] . '</a>';
				}
			}

			$add = C::ES;
			/* translatable element ? */
			if ( Sobi::Cfg( 'lang.multimode', false ) && ( $this->isSetTo( $element[ 'args' ], 'translatable', 1 ) || ( $this->isSetTo( $element[ 'args' ], 'translatable', 'true' ) ) ) ) {
				$add .= '<sup><span class="fas fa-globe dk-translatable" aria-hidden="true"></span><span class="visually-hidden">' . Sobi::Txt( "ACCESSIBILITY.TRANSLATABLE" ) . '</span></sup>';
			}

			/* required element ? */
			if ( $this->isSetTo( $element[ 'args' ], 'required', 1 ) || ( $this->isSetTo( $element[ 'args' ], 'required', 'true' ) ) ) {
				$add .= '<sup><span class="fas fa-star dk-required" aria-hidden="true"></span><span class="visually-hidden">' . Sobi::Txt( "ACCESSIBILITY.REQUIRED" ) . '</span></sup>';
			}

			/* revision compare? */
			if ( $this->isSetTo( $element, 'revisions-change' ) ) {
				$i = strlen( $element[ 'revisions-change' ] ) > 5 ? $element[ 'revisions-change' ] : $element[ 'id' ];
				$add .= '&nbsp;<a data-fid="' . $i . '" href="#" class="btn btn-sm btn-warning spctrl-revision-compare" aria-label="' . Sobi::Txt( "ACCESSIBILITY.REVISION_COMPARE" ) . '">&nbsp;<span class="fas fa-arrows-alt-h" aria-hidden="true"></span></a>';
			}

			if ( array_key_exists( 'label', $element[ 'args' ] ) && !$element[ 'args' ][ 'label' ] ) {
				$content = C::ES;
			}
			else {
				$content = $element[ 'label' ] . $add;
			}

			if ( $this->labelwidth == 0 ) {
				$col = "col-$this->coltype-12 col-form-label text-start";
			}
			else {
				$col = $grid ? "col-$this->coltype-$this->labelwidth col-form-label" : 'col-form-label';
			}
			$this->_out[] = "<label class=\"$col\" for=\"{$element['id']}\">$content</label>";
		}
	}
}
