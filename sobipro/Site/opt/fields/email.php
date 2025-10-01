<?php
/**
 * @package SobiPro multi-directory component with content construction support
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006-2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/GPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according to section 7 of GPL v3.
 * See https://www.gnu.org/licenses/gpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * @created 15-Jan-2009 by Radek Suski
 * @modified 25 November 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadClass( 'opt.fields.url' );

use Sobi\C;
use Sobi\Input\Input;
use Sobi\Lib\Factory;
use Sobi\Utils\Encryption;
use Sobi\Utils\Serialiser;
use Sobi\Utils\StringUtils;

/**
 * Class SPField_Email
 */
class SPField_Email extends SPField_Url implements SPFieldInterface
{
	/* add here properties which are different from their initial value (model or derived class)
	   and properties valid only for this class. */

	/* properties with different value */
	/** @var string */
	protected $dType = 'special';
	/** @var string */
	protected $cssClass = 'spClassEmail';
	/** @var string */
	protected $cssClassView = 'spClassViewEmail';
	/** @var string */
	protected $cssClassEdit = 'spClassEditEmail';
	/*** @var string */
	protected $labelsLabel = "Contact us by Email";
	/** @var bool */
	protected $untranslatable = false;

	/* properties only for this class */
	/*** @var bool */
	protected $botProtection = true;

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
		return [ 'itemprop', 'helpposition', 'showEditLabel', 'maxLength', 'cssClassView', 'cssClassEdit', 'bsWidth', 'encryptData', 'labelAsPlaceholder', 'ownLabel', 'floatOwnLabel', 'labelMaxLength', 'botProtection', 'untranslatable' ];
	}

	/**
	 * Shows the field in the edit entry or add entry form.
	 *
	 * @param bool $return -> return or display directly
	 *
	 * @return string
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
			}
			if ( is_array( $raw ) ) {
				$lvalue = isset( $raw[ 'label' ] ) ? StringUtils::Clean( $raw[ 'label' ] ) : C::ES;
				$value = $raw[ 'url' ] ?? C::ES;
			}
			else {
				$value = (string) $raw;
				$lvalue = C::ES;
			}

			$fw = defined( 'SOBIPRO_ADM' ) ? C::BOOTSTRAP5 : Sobi::Cfg( 'template.framework-style', C::BOOTSTRAP5 );

			$ownlabel = C::ES;
			if ( $this->ownLabel ) {
				$params[ 'aria-label' ] = $this->__get( 'name' );
				$placeholder = ( $this->placeholder ) ? : $this->__get( 'name' );
				if ( $this->labelAsPlaceholder ) {  /* show placeholder */
					$params[ 'placeholder' ] = C::ES;
					$params[ 'aria-label' ] = $placeholder;
				}

				$bsClass = $fw == C::BOOTSTRAP2 ? ' w-100' : C::ES;
				$tparams = [ 'id' => $this->nid . '-label', 'class' => 'sp-field-email-title' . $bsClass ];
				if ( $this->labelMaxLength ) {
					$tparams[ 'maxlength' ] = $this->labelMaxLength;
				}
				$placeholder = $this->labelsLabel ? StringUtils::Clean( $this->labelsLabel ) : $this->__get( 'name' );
				$tparams[ 'placeholder' ] = $tparams[ 'aria-label' ] = $placeholder;

				$title = SPHtml_Input::text( $this->nid . '-label', $lvalue, $tparams );
				$floating = $fw == C::BOOTSTRAP5 ? $this->floatOwnLabel : false;
				$label = $floating ? "<label for=\"$this->nid-label\">" . $placeholder . '</label>' : C::ES;
				$class = $floating ? " form-floating" : C::ES;
				$ownlabel = "<div class=\"sp-field-email-label $class\">$title$label</div>";
			}

			$class = defined( 'SOBIPRO_ADM' ) ? 'spClassEmail' : $this->cssClass;
			$class = $this->required ? $class . ' required' : $class;
			$class .= $fw == C::BOOTSTRAP2 ? ' w-100' : C::ES;
			$class .= $fw == C::BOOTSTRAP2 && $this->suffix ? ' suffix' : C::ES;

			$params = [ 'id' => $this->nid, 'class' => $class, 'data-field' => 'url' ];
			if ( $this->maxLength ) {
				$params[ 'maxlength' ] = $this->maxLength;
			}

			$label = Sobi::Txt( 'FD.MAIL_EMAIL_ADDRESS' );
			if ( !$this->ownLabel && $this->labelAsPlaceholder ) { // the field label will be shown only if labelAsPlaceholder is true and no own label for the email is selected
				$label = $this->__get( 'name' );  /* get the field's label from the model */
			}
			$params[ 'placeholder' ] = C::ES;
			$params[ 'aria-label' ] = $label;

			if ( $value == C::ES && $this->defaultValue ) {
				$value = $this->defaultValue;
			}
			$dc = $value || $lvalue ? ' data-sp-content="1"' : C::ES;

			$gs = "<div class=\"sp-field-email input-group\"$dc>";
			$ge = '</div>';
			switch ( $fw ) {
				case C::BOOTSTRAP5:
					$suffix = "<span class=\"input-group-text\">$this->suffix</span>";
					break;
				case C::BOOTSTRAP4:
					$suffix = "<div class='input-group-append'><span class=\"input-group-text\">$this->suffix</span></div>";
					break;
				case C::BOOTSTRAP3:
					$suffix = "<div class='input-group-addon'><span>$this->suffix</span></div>";
					$gs = '<div class="sp-field-email' . ( $this->suffix ? ' input-group' : C::ES ) . "\"$dc>";
					break;
				case  C::BOOTSTRAP2:  /* Bootstrap 2 */
					$gs = $this->suffix ? "<div class=\"sp-field-email input-append\"$dc>" : "<div class=\"sp-field-email\"$dc>";
					$suffix = "<span class='add-on'><span>$this->suffix</span></span>";
					break;
			}

			/* Construct the HTML */
			$html = $ownlabel;
			$html .= $gs;
			$html .= SPHtml_Input::text( $this->nid, $value, $params );
			if ( $this->suffix ) {
				$html .= $suffix;
			}
			$html .= $ge;

			if ( !$return ) {
				echo $html;
			}
			else {
				return $html;
			}
		}
		else {
			return C::ES;
		}

		return C::ES;
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
			foreach ( $data as $name => $value ) {
				if ( $name == 'url' ) {
					$return[ $this->nid ] = $value;
				}
				else {
					$return[ $this->nid . '-' . $name ] = $value;
				}
			}
			//return Input::Search( $this->nid, $request );
		}

		return $return;
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
	 * @param $entry
	 * @param $request
	 *
	 * @return array
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function verify( $entry, $request )
	{
		/* get the URL */
		$data = trim( (string) Input::Raw( $this->nid, $request ) );
		$data = $data ? Factory::Db()->escape( strip_tags( $data ) ) : $data;
		$dexs = strlen( $data );

		/* check if it was required */
		if ( $this->required && !$dexs ) {
			throw new SPException( SPLang::e( 'FIELD_REQUIRED_ERR', $this->name ) );
		}

		$save = [];
		if ( $dexs ) {
			$save[ 'url' ] = $data;

			if ( $this->ownLabel ) {
				$save[ 'label' ] = Factory::Db()->escape( strip_tags( (string) Input::Raw( $this->nid . '-label', $request ) ) );
			}

			/* check if there was a filter */
			if ( $this->filter ) {
				$filters = SPFactory::filter()->getFilters();
				$filter = $filters[ $this->filter ] ?? [];
				if ( !count( $filter ) ) {
					throw new SPException( SPLang::e( 'FIELD_FILTER_ERR', $this->filter ) );
				}
				else {
					if ( !preg_match( base64_decode( $filter[ 'params' ] ), $data ) ) {
						throw new SPException( str_replace( '$field', $this->name, SPLang::e( $filter[ 'message' ] ) ) );
					}
				}
			}

			/* check if there was an adminField */
			if ( $this->adminField && !Sobi:: Can( 'entry.adm_fields.edit' ) ) {
				throw new SPException( SPLang::e( 'FIELD_NOT_AUTH', $this->name ) );
			}

			/* check if it was free */
			if ( !$this->isFree && $this->fee ) {
				SPFactory::payment()->add( $this->fee, $this->name, $entry->get( 'id' ), $this->fid );
			}

			/* check if it should contains unique data */
			if ( $this->uniqueData ) {
				$matches = $this->searchData( $data, Sobi::Reg( 'current_section' ), C::ES );
				if ( count( $matches ) ) {
					throw new SPException( SPLang::e( 'FIELD_NOT_UNIQUE', $this->name ) );
				}
			}

			/* check if it was editLimit */
			if ( $this->editLimit == 0 && !Sobi::Can( 'entry.adm_fields.edit' ) ) {
				throw new SPException( SPLang::e( 'FIELD_NOT_AUTH_EXP', $this->name ) );
			}

			/* check if it was editable */
			if ( !$this->editable && !Sobi::Can( 'entry.adm_fields.edit' ) && $entry->get( 'version' ) > 1 ) {
				throw new SPException( SPLang::e( 'FIELD_NOT_AUTH_NOT_ED', $this->name ) );
			}

			/* check the response code */
			if ( $this->validateUrl ) {
				if ( preg_match( '/[a-z0-9]@[a-z0-9].[a-z]/i', $data ) ) {
					$domain = explode( '@', $data, 2 );
					$domain = $domain[ 1 ];
					if ( !checkdnsrr( $domain, 'MX' ) ) {
						throw new SPException( SPLang::e( 'FIELD_MAIL_NO_MX', $data ) );
					}
				}
				else {
					throw new SPException( SPLang::e( 'FIELD_MAIL_WRONG_FORM', $data ) );
				}
			}
		}
		$this->setData( $save );

		return $save;
	}

	/**
	 * @param $data
	 *
	 * @throws SPException
	 */
	public static function validateVisibility( &$data )
	{
		SPLoader::loadClass( 'env.browser' );
		$humanity = SPBrowser::getInstance()->get( 'humanity' );
		$display = Sobi::Cfg( 'mail_protection.show' );
		if ( !( $humanity >= $display ) ) {
			$data[ '_data' ] = [];
		}
	}

	/**
	 * @param array|string $data
	 *
	 * @return array|mixed|string
	 */
	public function cleanData( $data = C::ES )
	{
		$data = $data ? : $this->getRaw();

		return isset( $data[ 'url' ] ) && strlen( $data[ 'url' ] ) ? $data[ 'url' ] : C::ES;
	}

	/**
	 * Needs its own raw data conversion to support encryption and own labels.
	 * Called from the generic function getRaw().
	 *
	 * @param $data
	 *
	 * @return array|mixed|string|string[]|null
	 * @throws \Sobi\Error\Exception
	 */
	public function getRawData( $data )
	{
		try {
			if ( $data == null ) {
				return C::ES;
			}
			if ( $this->encryptData ) {
				if ( is_array( $data ) && strstr( $data[ 'url' ], 'encrypted://' ) ) {
					$data[ 'url' ] = Serialiser::StructuralData( $data[ 'url' ] );
				}
				else {
					if ( is_string( $data ) && strstr( $data, 'encrypted://' ) ) {
						$data = Serialiser::StructuralData( $data );
					}
					else {
						if ( !is_array( $data ) ) {
							$data = SPConfig::unserialize( $data );
						}
						if ( is_array( $data ) && strstr( $data[ 'url' ], 'encrypted://' ) ) {
							$data[ 'url' ] = Serialiser::StructuralData( $data[ 'url' ] );
						}
					}
				}
			}
			else {
				if ( !is_array( $data ) ) {
					$data = SPConfig::unserialize( $data );
				}
			}
		}
		catch ( SPException $x ) {
			$data = C::ES;
		}

		return $data;
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
	protected function fromCache( $cache )
	{
		$data = [];
		if ( isset( $cache[ $this->nid ] ) ) {
			$data[ 'url' ] = $cache[ $this->nid ];
		}
		if ( isset( $cache[ $this->nid . '-label' ] ) ) {
			$data[ 'label' ] = $cache[ $this->nid . '-label' ];
		}

		return $data;
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
			/* if we are here, we can save these data */
			$db = Factory::Db();

			$save = $this->verify( $entry, $request );
			if ( $this->encryptData && is_array( $save ) && count( $save ) ) {
				$save[ 'url' ] = 'encrypted://' . Encryption::Encrypt( $save[ 'url' ], Sobi::Cfg( 'encryption.key' ) );
			}
			$time = Input::Now();
			$IP = Input::Ip4( 'REMOTE_ADDR' );
			$uid = Sobi::My( 'id' );

			/* collect the needed params */
			$params = [];
			$params[ 'publishUp' ] = $entry->get( 'publishUp' ) ?? $db->getNullDate();
			$params[ 'publishDown' ] = $entry->get( 'publishDown' ) ?? $db->getNullDate();
			$params[ 'fid' ] = $this->fid;
			$params[ 'sid' ] = $entry->get( 'id' );
			$params[ 'section' ] = Sobi::Section();
			$params[ 'lang' ] = Sobi::Lang();
			$params[ 'enabled' ] = $entry->get( 'state' );
			$params[ 'params' ] = C::ES;
			$params[ 'options' ] = null;
			$params[ 'baseData' ] = is_array( $save ) ? Serialiser::Serialize( $save ) : $save;
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
			$this->saveToDatabase( $params, $entry->get( 'version' ), $this->untranslatable ? : false );
		}
	}

	/**
	 * @return array
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function struct()
	{
		$data = $this->getRaw();
		if ( isset( $data[ 'url' ] ) && strlen( $data[ 'url' ] ) ) {
			$show = true;
			if ( $this->ownLabel ) {
				if ( !( isset( $data[ 'label' ] ) && strlen( $data[ 'label' ] ) ) ) {
					$data[ 'label' ] = ( $this->labelsLabel == C::ES ) ? $data[ 'url' ] : $this->labelsLabel;
				}
			}
			else {
				/* use the URL even if a label is given */
				$data[ 'label' ] = $data[ 'url' ];
			}

			/* @TODO: add second step */
			if ( $this->botProtection ) {
				SPLoader::loadClass( 'env.browser' );
				$humanity = SPBrowser::getInstance()->get( 'humanity' );
				$display = Sobi::Cfg( 'mail_protection.show' );
				$show = $humanity >= $display;
			}
			if ( $show ) {
				$this->cssClass = strlen( $this->cssClass ) ? $this->cssClass : 'sp-field-data';
				$this->cssClass = $this->cssClass . ' ' . $this->nid;
				$this->cleanCss();
				$attributes = [ 'href' => "mailto:{$data['url']}", 'class' => $this->cssClass ];
				if ( $this->newWindow ) {
					$attributes[ 'target' ] = '_blank';
				}
				$data = [
					'_complex'    => 1,
					'_data'       => StringUtils::Clean( $data[ 'label' ] ),
					'_attributes' => $attributes,
				];

				return [
					'_complex'    => 1,
					'_validate'   => [ 'class' => str_replace( str_replace( '\\', '/', SOBI_PATH ), C::ES, str_replace( '\\', '/', __FILE__ ) ), 'method' => 'validateVisibility' ],
					'_data'       => [ 'a' => $data ],
					'_attributes' => [ 'lang' => Sobi::Lang( false ), 'class' => $this->cssClass ],
				];
			}
		}

		return [];
	}
}