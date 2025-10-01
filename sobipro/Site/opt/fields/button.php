<?php
/**
 * @package SobiPro multi-directory component with content construction support
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/GPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/gpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * @created 10 January 2017 by Sigrid Suski
 * @modified 14 February 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

use Sobi\C;
use Sobi\Utils\StringUtils;

SPLoader::loadClass( 'opt.fields.url' );

/**
 * Class SPField_Button
 */
class SPField_Button extends SPField_Url implements SPFieldInterface
{
	/* add here properties which are different from their initial value (model or derived class)
	   and properties valid only for this class. */

	/* properties with different value */
	/** @var string */
	protected $dType = 'special';
	/** @var int */
	protected $bsWidth = 8;
	/** @var string */
	protected $cssClass = 'spClassButton';
	/** @var string */
	protected $cssClassView = 'spClassViewButton';
	/** @var string */
	protected $cssClassEdit = 'spClassEditButton';

	/* properties for this and derived classes */
	/**  @var string */
	protected $labelsLabel = "Download";
	/** @var array */
	protected $allowedProtocols = [ 'https', 'http', 'relative' ];

	/** @var string */
	protected $cssButtonClass = 'btn btn-secondary';
	/** @var string */
	protected $cssIconClass = 'fas fa-download';
	/** @var bool */
	protected $useIcon = true;

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
		return [ 'itemprop', 'helpposition', 'showEditLabel', 'maxLength', 'cssClassView', 'cssClassEdit', 'bsWidth', 'labelAsPlaceholder', 'ownLabel', 'floatOwnLabel', 'labelMaxLength', 'validateUrl', 'allowedProtocols', 'newWindow', 'countClicks', 'counterToLabel', 'noFollow', 'deleteClicks', 'useIcon', 'cssIconClass', 'cssButtonClass' ];
	}


	/**
	 * Shows the field in the edit entry or add entry form.
	 *
	 * @param false $return -> return or display directly
	 *
	 * @return string|null
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function field( $return = false )
	{
		if ( $this->enabled ) {

			/* if data are stored in the editcache (requestcache)  and get them */
			$fdata = Sobi::Reg( 'editcache' );
			if ( $fdata && is_array( $fdata ) ) {
				$raw = $this->fromCache( $fdata );
			}
			else {
				/* get the data stored in the database (in case of edit) */
				$raw = $this->getRaw();
				if ( !is_array( $raw ) ) {
					try {
						$raw = SPConfig::unserialize( $raw );
					}
					catch ( SPException $x ) {
						$raw = C::ES;
					}
				}
			}

			$fw = defined( 'SOBIPRO_ADM' ) ? C::BOOTSTRAP5 : Sobi::Cfg( 'template.framework-style', C::BOOTSTRAP5 );

			$ownlabel = C::ES;
			if ( $this->ownLabel ) {
				$bsClass = $fw == C::BOOTSTRAP2 ? ' w-100' : C::ES;
				$tparams = [ 'id' => $this->nid . '-label', 'class' => 'sp-field-button-title' . $bsClass ];
				if ( $this->labelMaxLength ) {
					$tparams[ 'maxlength' ] = $this->labelMaxLength;
				}
				$placeholder = $this->labelsLabel ? StringUtils::Clean( $this->labelsLabel ) : $this->__get( 'name' );
				$tparams[ 'placeholder' ] = $tparams[ 'aria-label' ] = $placeholder;

				$lvalue = is_array( $raw ) && isset( $raw[ 'label' ] ) ? StringUtils::Clean( $raw[ 'label' ] ) : C::ES;
				$title = SPHtml_Input::text( $this->nid . '-label', $lvalue, $tparams );
				$floating = ( $fw == C::BOOTSTRAP5 ) ? $this->floatOwnLabel : false;
				$label = ( $floating ) ? "<label for=\"$this->nid-label\">" . $placeholder . '</label>' : C::ES;
				$class = ( $floating ) ? " form-floating" : C::ES;
				$ownlabel = "<div class=\"sp-field-button-label$class\">$title$label</div>";
			}

			$class = defined( 'SOBIPRO_ADM' ) ? 'spClassButton' : $this->cssClass;
			$class = $this->required ? $class . ' required' : $class;
			$class .= $fw == C::BOOTSTRAP2 ? ' w-100' : C::ES;
			$class .= $fw == C::BOOTSTRAP2 && $this->suffix ? ' suffix' : C::ES;

			$params = [ 'id' => $this->nid, 'class' => $class, 'data-field' => 'url' ];
			if ( $this->maxLength ) {
				$params[ 'maxlength' ] = $this->maxLength;
			}

			$label = Sobi::Txt( 'FD.URL_ADDRESS' );
			if ( !$this->ownLabel && $this->labelAsPlaceholder ) { // the field label will be shown only if labelAsPlaceholder is true and no own label for the URL is selected
				$label = $this->__get( 'name' );  /* get the field's label from the model */
			}
			$params[ 'aria-label' ] = $label;
			$params[ 'placeholder' ] = C::ES;

			$value = is_array( $raw ) && isset( $raw[ 'url' ] ) ? $raw[ 'url' ] : C::ES;
			if ( $value == null ) {
				if ( $this->defaultValue ) {
					$value = $this->defaultValue;
				}
			}
			$dc = ( $value || $lvalue ) ? ' data-sp-content="1"' : C::ES;

			/* Protocol handling */
			$protocols = [];
			if ( is_array( $this->allowedProtocols ) && count( $this->allowedProtocols ) ) {
				foreach ( $this->allowedProtocols as $protocol ) {
					$protocols[ $protocol ] = $protocol . '://';
				}
			}
			else {
				$protocols = [ 'https' => 'https://', 'http' => 'http://' ];
			}
			$sparams = [ 'id' => $this->nid . '-protocol', 'size' => 1, 'class' => 'sp-field-button-protocol', 'aria-label' => Sobi::Txt( "ACCESSIBILITY.SELECT_PROTOCOL" ) ];
			$flippedProtocols = array_flip( $protocols );
			$flippedProtocols = array_values( $flippedProtocols );
			$selected = is_array( $raw ) && isset( $raw[ 'protocol' ] ) ? $raw[ 'protocol' ] : $flippedProtocols[ 0 ];

			$gs = "<div class=\"sp-field-button input-group\"$dc>";
			$ge = '</div>';
			switch ( $fw ) {
				case C::BOOTSTRAP5:
					$ps = $pe = C::ES;
					$suffix = "<span class=\"input-group-text\">$this->suffix</span>";
					break;
				case C::BOOTSTRAP4:
					$ps = '<div class="input-group-prepend">';
					$pe = '</div>';
					$suffix = "<div class='input-group-append'><span class=\"input-group-text\">$this->suffix</span></div>";
					break;
				case C::BOOTSTRAP3:
					$ps = '<div class="input-group-btn">';
					$pe = '</div>';
					$suffix = "<div class='input-group-addon'><span>$this->suffix</span></div>";
					break;
				case  C::BOOTSTRAP2:  /* Bootstrap 2 */
					$gs = $this->suffix ? "<div class=\"sp-field-button input-prepend input-append\"$dc>" : "<div class=\"sp-field-button input-prepend\"$dc>";
					$sparams[ 'class' ] .= ' add-on';
					$ps = C::ES;
					$pe = C::ES;
					$suffix = "<span class='add-on'><span>$this->suffix</span></span>";
					break;
			}

			$ps .= SPHtml_Input::select( $this->nid . '-protocol', $protocols, $selected, false, $sparams );
			$ps .= $pe;    // end btn-group

			/* Construct the HTML */
			$html = $ownlabel;
			$html .= $gs;
			$html .= $ps;  /* add the protocols select list */
			$html .= SPHtml_Input::text( $this->nid, $value, $params );
			if ( $this->suffix ) {
				$html .= $suffix;
			}
			$html .= $ge;

			/* click counter handling */
			SPFactory::header()->addJsFile( 'opt.field_url_edit' );
			if ( $this->countClicks && $this->sid && ( $this->deleteClicks || SPFactory::user()->isAdmin() ) ) {
				if ( $fw >= C::BOOTSTRAP4 ) {
					$button = 'btn btn-secondary btn-sm mt-1';
				}
				else {
					$button = 'btn btn-default btn-sm btn-small';
				}
				$counter = $this->getCounter();
				$classes = $button . ' spctrl-counter-reset';
				$attr = [];
				if ( !$counter ) {
					$attr[ 'disabled' ] = 'disabled';
				}
				$attr[ 'data-spctrl' ] = 'counter-reset';
				$html .= SPHtml_Input::button( $this->nid . '_reset', Sobi::Txt( 'FM.URL.EDIT_CLICKS', $counter ), $attr, $classes );
			}

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
	 * Gets the data stored in the cache.
	 * The data are stored in the cache when user submits an entry.
	 * They are restored if the user comes back (later or from payment screen).
	 * (requestcache = editcache)
	 *
	 * @param $cache
	 *
	 * @return array
	 */
	private function fromCache( $cache )
	{
		$data = [];
		if ( isset( $cache ) && isset( $cache[ $this->nid . '-label' ] ) ) {
			$data[ 'label' ] = $cache[ $this->nid . '-label' ];
		}
		if ( isset( $cache ) && isset( $cache[ $this->nid ] ) ) {
			$data[ 'url' ] = $cache[ $this->nid ];
		}
		if ( isset( $cache ) && isset( $cache[ $this->nid . '-protocol' ] ) ) {
			$data[ 'protocol' ] = $cache[ $this->nid . '-protocol' ];
		}

		return $data;
	}

	/**
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function struct()
	{
		$data = SPConfig::unserialize( $this->getRaw() );
		if ( isset( $data[ 'url' ] ) && strlen( $data[ 'url' ] ) ) {
			$counter = -1;
			if ( $data[ 'protocol' ] == 'relative' ) {
				$url = $data[ 'url' ];
			}
			else {
				$url = $data[ 'protocol' ] . '://' . $data[ 'url' ];
			}
			if ( !( isset( $data[ 'label' ] ) && strlen( $data[ 'label' ] ) ) ) {
				$data[ 'label' ] = ( $this->labelsLabel == '' && !$this->useIcon ) ? $url : $this->labelsLabel;
			}
			if ( $this->useIcon && isset( $data[ 'label' ] ) && strlen( $data[ 'label' ] ) ) {
				$data[ 'label' ] = ' ' . $data[ 'label' ];
			}
			$this->cssClass = strlen( $this->cssClass ) ? $this->cssClass : 'sp-field-data';
			$this->cssClass = $this->cssClass . ' ' . $this->cssButtonClass;
			$this->cssClass = $this->cssClass . ' ' . $this->nid;

			$attributes = [ 'href' => $url, 'class' => $this->cssClass ];
			if ( $this->countClicks ) {
				SPFactory::header()->addJsFile( 'opt.field_url' );
				$this->cssClass = $this->cssClass . ' spctrl-visit-countable';
				$counter = $this->getCounter();
				$attributes[ 'data-sid' ] = $this->sid;
				if ( Sobi::Cfg( 'cache.xml_enabled' ) ) {
					$attributes[ 'data-counter' ] = $counter;
					$attributes[ 'data-refresh' ] = 'true';
				}
				$attributes[ 'class' ] = $this->cssClass;
				if ( $this->counterToLabel ) {
					$data[ 'label' ] = Sobi::Txt( 'FM.URL.COUNTER_WITH_LABEL2', [ 'label' => $data[ 'label' ], 'counter' => $counter ] );
				}
			}
			$this->cleanCss();
			if ( strlen( $url ) ) {
				if ( $this->newWindow ) {
					$attributes[ 'target' ] = '_blank';
					$attributes[ 'rel' ] = 'noopener noreferrer';
				}
				if ( $this->noFollow ) {
					if ( $this->newWindow ) {
						$attributes[ 'rel' ] = 'nofollow noopener noreferrer';
					}
					else {
						$attributes[ 'rel' ] = 'nofollow';
					}
				}
				if ( $this->useIcon ) {
					$f[ 'i' ] = [
						'_complex'    => 1,
						'_data'       => ' ',
						'_attributes' => [ 'class' => $this->cssIconClass ],
					];
				}
				$f[ 'span' ] = StringUtils::Clean( $data[ 'label' ] );
				$data = [
					'_complex'    => 1,
					'_data'       => $f,
					'_attributes' => $attributes,
				];

				return [
					'_complex'    => 1,
					'_data'       => [ 'a' => $data ],
					'_attributes' => [ 'lang' => Sobi::Lang( false ), 'class' => $this->cssClass, 'counter' => $counter ],
				];
			}
		}
	}
}
