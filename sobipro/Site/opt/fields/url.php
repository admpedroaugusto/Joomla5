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
 * @created Tue, Feb 12, 2013 by Radek Suski
 * @modified 14 February 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadClass( 'opt.fields.inbox' );

use Sobi\C;
use Sobi\Input\Cookie;
use Sobi\Input\Input;
use Sobi\Communication\CURL;
use Sobi\Lib\Factory;
use Sobi\Utils\Serialiser;
use Sobi\Utils\StringUtils;

/**
 * Class SPField_Url
 */
class SPField_Url extends SPField_Inbox implements SPFieldInterface
{
	/* add here properties which are different from their initial value (model or derived class)
	   and properties valid only for this class. */

	/* properties with different value */
	/** @var string */
	protected $dType = 'special';
	/** @var int */
	protected $maxLength = 150;
	/** @var int */
	protected $bsWidth = 4;
	/** @var string */
	protected $cssClass = 'spClassUrl';
	/** @var string */
	protected $cssClassView = 'spClassViewUrl';
	/** @var string */
	protected $cssClassEdit = 'spClassEditUrl';
	/** @var bool */
	protected $untranslatable = false;

	/* properties for this and derived classes */
	/** @var bool */
	protected $ownLabel = true;
	/** @var bool */
	protected $floatOwnLabel = false;
	/**  @var string */
	protected $labelsLabel = "Visit our Site";
	/** @var int */
	protected $labelMaxLength = 150;
	/** @var bool */
	protected $validateUrl = false;
	/** @var array */
	protected $allowedProtocols = [ 'https', 'http', 'relative', 'ftp' ];
	/** @var bool */
	protected $newWindow = false;
	/** @var bool */
	protected $noFollow = false;
	/** @var bool */
	protected $countClicks = false;
	/** @var bool */
	protected $deleteClicks = true;
	/** @var bool */
	protected $counterToLabel = false;

	/** @var bool */
	private static $CAT_FIELD = true;

	/**
	 * SPField_Url constructor. Get language dependant settings.
	 *
	 * @param $field
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function __construct( &$field )
	{
		parent::__construct( $field );

		$this->labelsLabel = SPLang::getValue( $this->nid . '-labels-label', 'field_' . $this->fieldType, Sobi::Section(), C::ES, C::ES, $this->fid );
	}

	/**
	 * Returns the parameter list (params). All properties not set in the model but used in the xml file of the field.
	 * No language dependant values, no values from the model (columns in the database).
	 *
	 * @return array
	 */
	protected function getAttr(): array
	{
		return [ 'itemprop', 'helpposition', 'showEditLabel', 'maxLength', 'cssClassView', 'cssClassEdit', 'bsWidth', 'labelAsPlaceholder', 'ownLabel', 'floatOwnLabel', 'labelMaxLength', 'validateUrl', 'allowedProtocols', 'newWindow', 'countClicks', 'counterToLabel', 'noFollow', 'deleteClicks', 'untranslatable' ];
	}

	/**
	 * @return array -> all properties which are not in the XML file but its default value needs to be set
	 */
	protected function getDefaults()
	{
		return [ 'suggesting' => false ];
	}

	/**
	 * Shows the field in the edit entry or add entry form.
	 *
	 * @param false $return -> return or display directly
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
				try {
					$raw = SPConfig::unserialize( $raw );
					if ( is_array( $raw ) ) {
						$lvalue = isset( $raw[ 'label' ] ) ? StringUtils::Clean( $raw[ 'label' ] ) : C::ES;
						$value = $raw[ 'url' ] ?? C::ES;
					}
					else {
						$lvalue = C::ES;
						$value = (string) $raw;
					}
				}
				catch ( SPException $x ) {
					$value = C::ES;
					$lvalue = C::ES;
				}
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
				$tparams = [ 'id' => $this->nid . '-label', 'class' => 'sp-field-url-title' . $bsClass ];
				if ( $this->labelMaxLength ) {
					$tparams[ 'maxlength' ] = $this->labelMaxLength;
				}
				$placeholder = $this->labelsLabel ? StringUtils::Clean( $this->labelsLabel ) : $this->__get( 'name' );
				$tparams[ 'placeholder' ] = $tparams[ 'aria-label' ] = $placeholder;

				$title = SPHtml_Input::text( $this->nid . '-label', $lvalue, $tparams );
				$floating = $fw == C::BOOTSTRAP5 ? $this->floatOwnLabel : false;
				$label = $floating ? "<label for=\"$this->nid-label\">" . $placeholder . '</label>' : C::ES;
				$class = $floating ? "form-floating" : C::ES;
				$ownlabel = "<div class=\"sp-field-url-label $class\">$title$label</div>";
			}

			$class = defined( 'SOBIPRO_ADM' ) ? 'spClassUrl' : $this->cssClass;
			$class = $this->required ? $class . ' required' : $class;
			$class .= $fw == C::BOOTSTRAP2 ? ( $this->bsWidth > 2 ? ' span' . ( $this->bsWidth - 1 ) : ' span' . $this->bsWidth ) : C::ES;
			$class .= $fw == C::BOOTSTRAP2 && $this->suffix ? ' suffix' : C::ES;

			$params = [ 'id' => $this->nid, 'class' => $class, 'data-field' => 'url' ];
			if ( $this->maxLength ) {
				$params[ 'maxlength' ] = $this->maxLength;
			}

			$label = Sobi::Txt( 'FD.URL_ADDRESS' );
			if ( !$this->ownLabel && $this->labelAsPlaceholder ) { // the field label will be shown only if labelAsPlaceholder is true and no own label for the URL is selected
				$label = $this->__get( 'name' );  /* get the field's label from the model */
			}
			$params[ 'placeholder' ] = C::ES;
			$params[ 'aria-label' ] = $label;

			if ( $value == C::ES && $this->defaultValue ) {
				$value = $this->defaultValue;
			}
			$dc = $value || $lvalue ? ' data-sp-content="1"' : C::ES;

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
			$sparams = [ 'id' => $this->nid . '-protocol', 'size' => 1, 'class' => 'sp-field-url-protocol', 'aria-label' => Sobi::Txt( "ACCESSIBILITY.SELECT_PROTOCOL" ) ];
			$flippedProtocols = array_values( array_flip( $protocols ) );
			$selected = is_array( $raw ) && isset( $raw[ 'protocol' ] ) ? $raw[ 'protocol' ] : $flippedProtocols[ 0 ];

			$gs = "<div class=\"sp-field-url input-group\"$dc>";
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
					$gs = $this->suffix ? "<div class=\"sp-field-url input-prepend input-append\"$dc>" : "<div class=\"sp-field-url input-prepend\"$dc>";
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
				$button = $fw >= C::BOOTSTRAP4 ? 'btn btn-secondary btn-sm mt-1' : 'btn btn-default btn-sm btn-small';
				$counter = $this->getCounter();
				$classes = $button . ' spctrl-counter-reset';
				$attr = [];
				if ( !( $counter ) ) {
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
		else {
			return C::ES;
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
	 * @param $real
	 * @param $sid
	 * @param $nid
	 *
	 * @return string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected static function getHits( $real, $sid, $nid )
	{
		$query = [ 'sid' => $sid, 'fid' => $nid, 'section' => Sobi::Section() ];
		if ( $real ) {
			$query[ 'humanity>' ] = Sobi::Cfg( 'field_url.humanity', 90 );
		}
		$counter = Factory::Db()
			->select( 'count(*)', 'spdb_field_url_clicks', $query )
			->loadResult();

		return $counter;
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
				$data[ 'label' ] = ( $this->labelsLabel == '' ) ? $url : $this->labelsLabel;
			}
			$this->cssClass = strlen( $this->cssClass ) ? $this->cssClass : 'sp-field-data';
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
					$data[ 'label' ] = Sobi::Txt( 'FM.URL.COUNTER_WITH_LABEL', [ 'label' => $data[ 'label' ], 'counter' => $counter ] );
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
				$data = [
					'_complex'    => 1,
					'_data'       => StringUtils::Clean( $data[ 'label' ] ),
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

	/**
	 * @param bool $real
	 *
	 * @return string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function getCounter( $real = true )
	{
		$counter = self::getHits( $real, $this->sid, $this->nid );

		return $counter;
	}

	/**
	 * @param array|string $data
	 *
	 * @return string|array
	 * @throws \SPException
	 */
	public function cleanData( $data = C::ES )
	{
		$data = $data ? : SPConfig::unserialize( $this->getRaw() );

		$url = C::ES;
		if ( isset( $data[ 'url' ] ) && strlen( $data[ 'url' ] ) ) {
			if ( $data[ 'protocol' ] == 'relative' ) {
				$url = Sobi::Cfg( 'live_site' ) . $data[ 'url' ];
			}
			else {
				$url = $data[ 'protocol' ] . '://' . $data[ 'url' ];
			}
		}

		return $url;
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
		/* remove protocol if given in the url */
		$data = preg_replace( '/([a-z]{1,5}\:\/\/)/i', C::ES, $data );
		$dexs = strlen( $data );

		/* check if it was required */
		if ( $this->required && !$dexs ) {
			throw new SPException( SPLang::e( 'FIELD_REQUIRED_ERR', $this->name ) );
		}

		$save = [];
		if ( $dexs ) {
			/* escape it although not necessary */
			$save[ 'protocol' ] = Factory::Db()->escape( Input::Word( $this->nid . '-protocol', $request ) );
			$save[ 'url' ] = $data;

			if ( $this->ownLabel ) {
				$save[ 'label' ] = Factory::Db()->escape( trim( strip_tags( (string) Input::Raw( $this->nid . '-label', $request ) ) ) );
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
				$rclass = new CURL();
				$errno = $response = 0;
				try {
					$connection = new $rclass();
					$errno = $connection->error( false, true );
					$status = $connection->status( false, true );
					/* if CURL initialisation failed (CURL not installed) */
					if ( $status || $errno ) {
						$errmsg = 'Code ' . $status ? $connection->status() : $connection->error();
					}
					else {
						$connection->setOptions(
							[
								'url'            => $save[ 'protocol' ] . '://' . $data,
								'connecttimeout' => 10,
								'header'         => false,
								'returntransfer' => true,
							]
						);
						$connection->exec();
						$response = $connection->info( 'response_code' );
						$errno = $connection->error( false );
						$errmsg = $connection->error();
						$connection->close();
						if ( $errno ) {
							Sobi::Error( $this->name(), SPLang::e( 'FIELD_URL_CANNOT_VALIDATE', $errmsg ), C::WARNING, 0, __LINE__, __FILE__ );
						}
					}
				}
				catch ( SPException $x ) {
					Sobi::Error( $this->name(), SPLang::e( 'FIELD_URL_CANNOT_VALIDATE', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
				}
				if ( $errno || $status || ( $response != 200 ) ) {
					$response = ( $errno || $status ) ? $errmsg : $response;
					Sobi::Error( $this->name(), SPLang::e( 'FIELD_URL_ERR', $save[ 'protocol' ] . '://' . $data, $response ), C::WARNING, 0, __LINE__, __FILE__ );
					throw new SPException( SPLang::e( 'FIELD_URL_ERR', $save[ 'protocol' ] . '://' . $data, $response ) );
				}
			}
		}

		return $save;
	}

	/**
	 * Gets the data for a field and saves it in the database.
	 *
	 * @param SPEntry $entry
	 * @param string $request
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function saveData( &$entry, $request = 'POST' )
	{
		if ( $this->enabled ) {
			/* if we are here, we can save these data */
			$db = Factory::Db();
			$save = $this->verify( $entry, $request );
			$this->setRawData( $save );

			$time = Input::Now();
			$IP = Input::Ip4( 'REMOTE_ADDR' );
			$uid = Sobi::My( 'id' );

			/* if we are here, we can save these data */
			/* collect the needed params */
			$params = [];
			$params[ 'publishUp' ] = $entry->get( 'publishUp' ) ?? $db->getNullDate();
			$params[ 'publishDown' ] = $entry->get( 'publishDown' ) ?? $db->getNullDate();
			$params[ 'fid' ] = $this->fid;
			$params[ 'sid' ] = $entry->get( 'id' );
			$params[ 'section' ] = Sobi::Section();
			$params[ 'lang' ] = Sobi::Lang();
			$params[ 'enabled' ] = $entry->get( 'state' );
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
			$params[ 'copy' ] = (int) !$entry->get( 'approved' );

			$this->setEditLimit( $entry, $params[ 'baseData' ] );
			$params[ 'editLimit' ] = $this->editLimit;

			/* save it to the database */
			$this->saveToDatabase( $params, $entry->get( 'version' ), $this->untranslatable ? : false );
		}
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function ProxyReset()
	{
		$eid = Input::Int( 'eid' );
		/* let's allow it for admins only right now (later we can extend it a bit) */
//		$entry = SPFactory::Entry( $eid );
		if ( Sobi::Can( 'entry.manage.any' ) ) {
			Factory::Db()->delete( 'spdb_field_url_clicks', [ 'section' => Sobi::Section(), 'sid' => $eid, 'fid' => $this->nid ] );
		}
		echo 1;
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function ProxyHits()
	{
		SPFactory::mainframe()
			->cleanBuffer()
			->customHeader();
		$r = ( int ) self::getHits( true, Input::Int( 'eid' ), $this->nid );
		echo $r;

		exit;
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 * @throws \Exception
	 */
	public function ProxyCount()
	{
		SPLoader::loadClass( 'env.browser' );
		SPLoader::loadClass( 'env.cookie' );

		$browser = SPBrowser::getInstance();
		$this->nid = str_replace( [ '.count', '.' ], [ C::ES, '_' ], Input::Task() );
		$ident = $this->nid . '_' . Input::Int( 'eid' );
		$check = Input::Cmd( 'count_' . $ident, 'cookie' );
		if ( !$check ) {
			$data = [
				'date'        => 'FUNCTION:NOW()',
				'uid'         => Sobi::My( 'id' ),
				'sid'         => Input::Int( 'eid' ),
				'fid'         => $this->nid,
				'ip'          => Input::Ip4( 'REMOTE_ADDR' ),
				'section'     => Sobi::Section(),
				'browserData' => $browser->get( 'browser' ),
				'osData'      => $browser->get( 'system' ),
				'humanity'    => $browser->get( 'humanity' ),
			];
			Factory::Db()->insert( 'spdb_field_url_clicks', $data );
			Cookie::Set( 'count_' . $ident, 1, Cookie::Hours( 2 ) );
		}
	}

	/**
	 * Processes the url and label for the comparison method.
	 *
	 * @param $url
	 * @param bool $addType
	 *
	 * @return string
	 */
	protected function paresUrl( $url, $addType = true )
	{
		if ( $url[ 'protocol' ] == 'relative' ) {
			$dUrl = ( $addType ? 'url: ' : C::ES ) . $url[ 'url' ];
		}
		else {
			$dUrl = ( $addType ? 'url: ' : C::ES ) . $url[ 'protocol' ] . '://' . $url[ 'url' ];
		}
		if ( isset( $url[ 'label' ] ) ) {
			return ( $addType ? 'label: ' : C::ES ) . "{$url['label']}\n$dUrl";
		}
		else {
			return $dUrl;
		}
	}

	/**
	 * Compares two versions of the field's data visually.
	 *
	 * @param $revision
	 * @param $current
	 *
	 * @return array
	 */
	public function compareRevisions( $revision, $current )
	{
		if ( $this->type == 'email' ) {
			$revision[ 'protocol' ] = 'relative';
			$current[ 'protocol' ] = 'relative';
		}

		return [ 'current' => $this->paresUrl( $current ), 'revision' => $this->paresUrl( $revision ) ];
	}

	/**
	 * Returns the formatted data from raw data.
	 *
	 * @param null $data
	 *
	 * @return mixed|null
	 * @throws \SPException
	 */
	public function getData( $data = null )
	{
		if ( !$data ) {
			$data = $this->getRaw();
			$data = SPConfig::unserialize( $data );
		}

		return $data;
	}
}
