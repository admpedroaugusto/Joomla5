<?php
/**
 * @package SobiPro Library
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006–2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according to section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * @created 04 December 2013 by Radek Suski
 * @modified 23 August 2024 by Sigrid Suski
 */

defined( '_JEXEC' ) or die();
defined( 'SOBIPRO' ) || define( 'SOBIPRO', true );
defined( 'SOBIPRO_ADM' ) || define( 'SOBIPRO_ADM', true );

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Form\FormField;
use Joomla\Component\Menus\Administrator\Model\ItemModel;
use Sobi\C;
use Sobi\Lib\Factory;

//include_once JPATH_ADMINISTRATOR . '/components/com_menus/tables/menu.php';

/**
 * Class JFormFieldNative
 * for Joomla menu handling.
 */
class JFormFieldNative extends FormField
{
	/**
	 * @var array
	 */
	protected $params = [];
	/**
	 * @var int
	 */
	public static $sid = 0;
	/**
	 * @var int
	 */
	public static $functionsLabel = 0;
	/**
	 * @var int
	 */
	public static $section = 0;
	/**
	 * @var int
	 */
	public static $mid = 0;

	/**
	 * JFormFieldNative constructor.
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	public function __construct()
	{
		$this->initialise();

		parent::__construct();
	}

	/**
	 * @throws \SPException|\Sobi\Error\Exception
	 * @throws \Exception
	 */
	private function initialise()
	{
		static $loaded = false;
		if ( !$loaded || true ) {
			require_once( JPATH_SITE . '/components/com_sobipro/lib/sobi.php' );
			Sobi::Initialise( 0, true );
			SPFactory::header()
				->initBase( true )
				->addJsFile( [ 'bootstrap.utilities.typeahead', 'adm.joomla-menu' ] );
			$loaded = true;
			SPLoader::loadClass( 'mlo.input' );
			SPLoader::loadClass( 'models.datamodel' );
			SPLoader::loadClass( 'models.dbobject' );
			SPLoader::loadModel( 'section' );

			if ( SOBI_CMS == 'joomla3' ) {
				$model = BaseDatabaseModel::getInstance( 'MenusModelItem' )->getItem();
			}
			/* Joomla4 */
			else {
				$menu = new ItemModel();
				$model = $menu->getItem();
			}
			self::$mid = $model->id;
			if ( isset( $model->params[ 'sobiprosettings' ] ) && strlen( $model->params[ 'sobiprosettings' ] ) ) {
				$this->params = json_decode( base64_decode( $model->params[ 'sobiprosettings' ] ) );
			}
			$jsString = json_encode(
				[
					'component'   => Sobi::Txt( 'SOBI_NATIVE_TASKS' ),
					'buttonLabel' => Sobi::Txt( 'SOBI_SELECT_FUNCTIONALITY' ),
				]
			);
			SPFactory::header()->addJsCode( "let spStrings = $jsString; " );
		}
	}

	/**
	 * @return string
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function getInput()
	{
		if ( !self::$sid ) {
			self::$sid = $this->params->sid ?? $this->value;
			$link = $this->form->getValue( 'link' );
			if ( !self::$sid && strlen( $link ) ) {
				parse_str( $link, $request );
				if ( isset( $request[ 'sid' ] ) && $request[ 'sid' ] ) {
					self::$sid = $request[ 'sid' ];
				}
			}

			if ( self::$sid ) {
				self::$section = SPFactory::config()->getParentPathSection( self::$sid );
                //self::$section = $path[ 0 ] ? : self::$sid;
            }

			$this->getFunctionsLabel();
		}

		return $this->fieldname == 'sid' ? $this->loadSection() : $this->loadAdvanced();
	}

	/**
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function getFunctionsLabel()
	{
		if ( isset( $this->params->interpreter ) ) {
			$interpreter = explode( '.', $this->params->interpreter );
			$function = array_pop( $interpreter );
			$obj = SPFactory::Instance( implode( '.', $interpreter ) );
			self::$functionsLabel = $obj->$function( self::$sid, self::$section );
		}
		elseif ( isset( $this->params->text ) ) {
			if ( isset( $this->params->loadTextFile ) ) {
				Factory::Application()->loadLanguage( $this->params->loadTextFile );
			}
			self::$functionsLabel = Sobi::Txt( $this->params->text );
		}
	}

	/**
	 * @return mixed
	 * @throws \Sobi\Error\Exception|\SPException
	 */
	protected function loadSection()
	{
		$sections = [];
		$sectionsOutput = [];
		try {
			$sections = Factory::Db()
				->select( '*', 'spdb_object', [ 'oType' => 'section' ], 'id' )
				->loadObjectList();
		}
		catch ( Exception $x ) {
			Sobi::Error( $this->name(), $x->getMessage(), SPC::ERROR, 500, __LINE__, __FILE__ );
		}
		if ( count( $sections ) ) {
			$sectionsOutput[] = Sobi::Txt( 'SOBI_SELECT_SECTION' );
			foreach ( $sections as $section ) {
				if ( Sobi::Can( 'section', 'access', 'valid', $section->id ) ) {
					$s = new SPSection();
					$s->extend( $section );
					$sectionsOutput[ $s->get( 'id' ) ] = $s->get( 'name' );
				}
			}
		}
		$params = [ 'id' => 'spctrl-section', 'class' => 'required' ];

		return SPHtml_Input::select( 'section', $sectionsOutput, self::$section, false, $params );
	}

	/**
	 * The button and modal for SobiPro functionality in Joomla menu.
	 *
	 * @return string
	 */
	protected function loadAdvanced()
	{
		$label = self::$functionsLabel ? : Text::_( 'SP.SOBI_SELECT_FUNCTIONALITY' );

		$btnSize = SOBI_CMS == 'joomla4' ? C::ES : ' btn-sm';

		return
			'<div class="SobiPro">' .
			'	<div id="spctrl-selector" class="btn btn-primary' . $btnSize . '" data-mid="' . self::$mid . '">' .
			'		<span class="fas fa-expand"></span>&nbsp;<span id="spctrl-selected-function">' . $label . '</span>' .
			'	</div>' .
			'   <div class="modal fade" id="spctrl-modal" aria-hidden="true" aria-labelledby="sparia-title" tabindex="-1">' .
			'      <div class="modal-dialog modal-lg modal-fullscreen-sm-down">' .
			'         <div class="modal-content">' .
			'            <div class="modal-header">' .
			'                <h3 class="modal-title" id="sparia-title">' . Sobi::Txt( 'SOBI_SELECT_FUNCTIONALITY' ) . '</h3>' .
			'                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="' . Sobi::Txt( "ACCESSIBILITY.CLOSE" ) . '"></button>' .
			'            </div>' .
			'            <div class="modal-body pe-0">' .
			'               <span class="fas fa-spinner fa-spin fa-lg"></span>' .
			'            </div>' .
			'            <div class="modal-footer">' .
			'               <a href="#" class="btn btn-secondary" data-bs-dismiss="modal">' . Sobi::Txt( 'SOBI_CLOSE_WINDOW' ) . '</a>' .
			'               <a href="#" class="btn btn-success spctrl-save" data-bs-dismiss="modal">' . Sobi::Txt( 'SOBI.JMENU_SAVE' ) . '</a>' .
			'            </div>' .
			'         </div>' .
			'      </div>' .
			'   </div>' .
			'   <input type="hidden" id="spctrl-selected-sid" name="jform[request][sid]" value="' . self::$sid . '"/>' .
			'</div>';
	}
}
