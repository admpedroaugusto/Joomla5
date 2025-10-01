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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 01 August 2012 by Radek Suski
 * @modified 26 August 2024 by Sigrid Suski
 */

defined( '_JEXEC' ) or die();

use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Sobi\Lib\Factory;
use Sobi\FileSystem\FileSystem;
use Sobi\C;

if ( !class_exists( 'JElement' ) ) {
	/**
	 * @package Joomla.Framework
	 * @subpackage Parameter
	 * @copyright Copyright (C) 2005 - 2010 Open Source Matters. All rights reserved.
	 * @license GNU/GPL, see LICENSE.php
	 * Joomla! is free software. This version may have been modified pursuant
	 * to the GNU General Public License, and as distributed it includes or
	 * is derivative of works licensed under the GNU General Public License or
	 * other free or open source software licenses.
	 * See COPYRIGHT.php for copyright notices and details.
	 */

// Check to ensure this file is within the rest of the framework
	defined( 'JPATH_BASE' ) or die();

	/**
	 * Parameter base class.
	 *
	 * The JElement is the base class for all JElement types
	 *
	 * @abstract
	 * @package Joomla.Framework
	 * @subpackage Parameter
	 */
	class JElement extends stdClass
	{
		/* do not replace '' with C::ES as the SobiPro autoloader will not be called at that time */

		/**
		 * Element name.
		 *
		 * This has to be set in the final renderer classes.
		 *
		 * @access protected
		 * @var string
		 */
		public $_name = '';

		/**
		 * Reference to the object that instantiated the element.
		 *
		 * @access protected
		 * @var object
		 */
		public $_parent = null;

		/**
		 * Constructor
		 *
		 * @access protected
		 *
		 * @param null $parent
		 */
		public function __construct( $parent = null )
		{
			$this->_parent = $parent;
		}

		/**
		 * Gets the element name.
		 *
		 * @access public
		 * @return string    type of the parameter
		 */
		public function getName()
		{
			return $this->_name;
		}

		/**
		 * @param $xmlElement
		 * @param $value
		 * @param string $control_name
		 *
		 * @return array
		 */
		public function render( &$xmlElement, $value, $control_name = 'params' )
		{
			$name = $xmlElement->attributes( 'name' );
			$label = $xmlElement->attributes( 'label' );
			$descr = $xmlElement->attributes( 'description' );
			//make sure we have a valid label
			$label = $label ? $label : $name;
			$result[ 0 ] = $this->fetchTooltip( $label, $descr, $xmlElement, $control_name, $name );
			$result[ 1 ] = $this->fetchElement( $name, $value, $xmlElement, $control_name );
			$result[ 2 ] = $descr;
			$result[ 3 ] = $label;
			$result[ 4 ] = $value;
			$result[ 5 ] = $name;

			return $result;
		}

		/**
		 * @param $label
		 * @param $description
		 * @param $xmlElement
		 * @param string $control_name
		 * @param string $name
		 *
		 * @return string
		 */
		public function fetchTooltip( $label, $description, &$xmlElement, $control_name = C::ES, $name = C::ES )
		{
			$output = '<label id="' . $control_name . $name . '-lbl" for="' . $control_name . $name . '"';
			if ( $description ) {
				$output .= ' class="hasTip" title="' . Text::_( $label ) . '::' . Text::_( $description ) . '">';
			}
			else {
				$output .= '>';
			}
			$output .= Text::_( $label ) . '</label>';

			return $output;
		}
	}
}

/**
 * Class JElementSPSection
 */
class JElementSPSection extends JElement
{
	/* do not replace '' with C::ES as the SobiPro autoloader will not be called at that time */
	/**
	 * @var string
	 */
	protected $task = '';
	/**
	 * @var string
	 */
	protected $taskName = '';
	/**
	 * @var string
	 */
	protected $oType = '';
	/**
	 * @var string
	 */
	protected $oTypeName = '';
	/**
	 * @var string
	 */
	protected $oName = '';
	/**
	 * @var null
	 */
	protected $sid = null;
	/**
	 * @var null
	 */
	protected $section = null;
	/**
	 * @var null
	 */
	protected $tpl = null;
	/**
	 * @var int
	 */
	protected $cid = 0;

	/**
	 * @return JElementSPSection|static|null
	 */
	public static function & getInstance()
	{
		static $instance = null;
		if ( !( $instance instanceof self ) ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Will be called for SobiPro modules in backend.
	 *
	 * JElementSPSection constructor.
	 * @throws SPException|\Sobi\Error\Exception
	 * @throws \Exception
	 */
	public function __construct()
	{
		static $loaded = false;
		if ( $loaded ) {
			return true;
		}
		define( 'SOBIPRO', true );
		$sobiRoot = JPATH_ROOT . '/components/com_sobipro/';
		require_once( $sobiRoot . 'lib/sobi.php' );
		Sobi::Initialise( 0, true );

		/* load the Bootstrap modules needed for SobiPro in modules and menu?  */
		if ( SOBI_CMS == 'joomla4' ) {
			JFactory::getApplication()
				->getDocument()
				->getWebAssetManager()
				->useScript( 'bootstrap.modal' );
		}

		SPLoader::loadClass( 'mlo.input' );
		Factory::Application()->loadLanguage( 'com_sobipro.sys' );

		$head = SPFactory::header();
		$head
			->initBase( true )
			->addJsFile( [ 'adm.joomla-modules' ] );

//		$this->cid = Input::Int( 'id' );
		$this->getSections();
		$strings = [
			'objects' => [
				'entry'    => Sobi::Txt( 'OTYPE_ENTRY' ),
				'category' => Sobi::Txt( 'OTYPE_CATEGORY' ),
				'section'  => Sobi::Txt( 'OTYPE_SECTION' ),
			],
			'labels'  => [
				'category' => Sobi::Txt( 'SOBI_SELECT_CATEGORY' ),
				'entry'    => Sobi::Txt( 'SOBI_SELECT_ENTRY' ),
			],
			//			'task'    => $this->task,
		];
		$strings = json_encode( $strings );

		$head
//			->addJsCode( "SPJmenuFixTask( '$this->taskName' );" )
			->addJsFile( 'bootstrap.utilities.typeahead' )
			->addJsCode( "let spStrings = $strings" );

		if ( $this->task != 'list.date' ) {
			$head->addJsCode( /** @lang JavaScript */ 'SobiCore.Ready( function () { 
				let calendar = SobiCore.Query( "#spctrl-calendar" );
				if ( calendar ) {
					calendar.parentElement.parentElement.css( "display", "none" ); 
				}
			} );'
			);
		}
		else {
			$head->addCSSCode( '.spctrl-calendar .chzn-container {width: 100px!important; } ' );
			$head->addCSSCode( '.spctrl-calendar select {width: inherit;} ' );
		}

		parent::__construct();
		$loaded = true;
	}

	protected function getSections()
	{
		try {
			$this->sections = Factory::Db()
				->select( '*', 'spdb_object', [ 'oType' => 'section' ], 'id' )
				->loadObjectList( 'id' );
		}
		catch ( Exception $x ) {
		}
	}

	/**
	 * @throws Exception
	 */
	protected function determineTask()
	{
		/* @todo needs to be checked as I haven't found the reason if it yet */
		return;

		$link = $this->getLink();
		$query = [];
		parse_str( $link, $query );
		$this->task = $query[ 'task' ] ?? null;
		if ( $this->task ) {
			$def = SPFactory::LoadXML( SOBI_PATH . '/metadata.xml' );
			$xdef = new DOMXPath( $def );
			$nodes = $xdef->query( "//option[@value='$this->task']" );
			if ( count( $nodes ) ) {
				$this->taskName = 'SobiPro - ' . Text::_( $nodes->item( 0 )->attributes->getNamedItem( 'name' )->nodeValue );
			}
		}
		else {
			$this->taskName = Text::_( 'SP.SOBI_SECTION' );
		}
	}

	/**
	 * @return string
	 * @throws \Exception
	 */
	protected function getLink()
	{
		/* @todo needs to be checked as I haven't found the reason if it yet */
		return C::ES;

//		static $link = null;
//		$data = JFactory::getApplication()->getUserState( 'com_menus.edit.item.data' );
//		if ( !$link ) {
//			if ( is_array( $data ) && $data[ 'id' ] == $this->cid ) {
//				$link = $data[ 'link' ];
//			}
//			else {
//				$model = BaseDatabaseModel::getInstance( 'MenusModelItem' )->getItem();
//				$link = $model->link;
//			}
//		}
//
//		return str_replace( 'amp;', C::ES, $link );
	}

	/**
	 * @param $label
	 * @param $description
	 * @param $node
	 * @param string $control_name
	 * @param string $name
	 *
	 * @return string|null
	 */
	public function fetchTooltip( $label, $description, &$node, $control_name = C::ES, $name = C::ES )
	{
		switch ( $label ) {
			case 'cid':
				if ( $this->task ) {
					return '&nbsp;';
				}
				$label = Text::_( 'SP.SOBI_SELECT_CATEGORY' );
				break;
			case 'SOBI_SELECT_DATE':
				if ( $this->task != 'list.date' ) {
					return null;
				}
				$label = Text::_( 'SP.SOBI_SELECT_ENTRY' );
				break;

		}

		return parent::fetchTooltip( $label, $node->attributes( 'msg' ), $node, $control_name, $name );
	}

	/**
	 * Modal window to select a category from the category tree in a SobiPro module.
	 *
	 * @return string
	 */
	protected function getCategories()
	{
		if ( $this->sid == 0 ) {
			$this->oType = 'section';
		}

		if ( SOBI_CMS == 'joomla4' ) {
			$style = C::ES;
			$class = ' w-50';
		}
		else {
			$style = 'width: 220px !important';
			$class = ' btn-sm';
		}
		$params = [
			'id'    => 'spctrl-category',
			'class' => ( $this->oType == 'category' ? 'btn btn-primary' : 'btn btn-secondary' ) . $class,
			'style' => $style,
		];
		if ( $this->task && $this->task != 'entry.add' ) {
			$params[ 'disabled' ] = 'disabled';
		}

		return
			'<div class="SobiPro" data-bs="5" data-site="adm">' .
			SPHtml_Input::button( 'spctrl-category', $this->oType == 'category' ? $this->oName : Sobi::Txt( 'SOBI_SELECT_CATEGORY' ), $params ) .
			'<div class="modal" id="spctrl-category-modal" aria-hidden="true" aria-labelledby="sparia-title" tabindex="-1" >
				<div class="modal-dialog modal-md modal-fullscreen-sm-down">
                    <div class="modal-content">
						<div class="modal-header">
							<h3 class="modal-title" id="sparia-title">' . Sobi::Txt( 'SOBI_SELECT_CATEGORY' ) . '</h3>
	                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="' . Sobi::Txt( "ACCESSIBILITY.CLOSE" ) . '"></button>
						</div>
		                <div class="modal-body pe-0">
		                    <div id="spctrl-category-chooser"></div>
		                </div>
		                <div class="modal-footer">
		                    <a href="#" id="spctrl-category-clean" class="btn btn-secondary" data-bs-dismiss="modal" >' . Sobi::Txt( 'SOBI_MENU_CLEAR' ) . '</a>
		                    <a href="#" class="btn btn-secondary" data-bs-dismiss="modal" role="button">' . Sobi::Txt( 'SOBI_CLOSE_WINDOW' ) . '</a>
		                    <a href="#" id="spctrl-category-save" class="btn btn-success" data-bs-dismiss="modal" role="button">' . Sobi::Txt( 'SOBI.JMENU_SAVE' ) . '</a>
		                </div>
		            </div>
               </div>
            </div>
            <input type="hidden" name="selectedCat" id="selectedCat" value=""/>
            <input type="hidden" name="selectedCatName" id="selectedCatName" value=""/>
        </div>';
	}

	/**
	 * @return string
	 */
	private function getEntry()
	{
		$params = [
			'id'    => 'spctrl-entry',
			'class' => $this->oType == 'entry' ? 'btn input-large btn-primary' : 'btn input-medium',
			'style' => 'margin-top: 10px; width: 300px',
		];
		if ( $this->task ) {
			$params[ 'disabled' ] = 'disabled';
		}

		return
			'<div class="SobiPro" data-bs="5" data-site="adm">' .
			SPHtml_Input::button( 'spctrl-entry', $this->oType == 'entry' ? $this->oName : Sobi::Txt( 'SOBI_SELECT_ENTRY' ), $params ) .
			'<div class="modal" id="spctrl-entry-modal" tabindex="-1">
				<div class="modal-dialog">
                    <div class="modal-content">
						<div class="modal-header">
							<h3 class="modal-title" id="sparia-title">' . Sobi::Txt( 'SOBI_SELECT_ENTRY' ) . '</h3>
							<button class="close" data-dismiss="modal" aria-label="' . Sobi::Txt( "ACCESSIBILITY.CLOSE" ) . '"><span aria-hidden="true">×</span></button>
                        </div>
                        <div class="modal-body" style="overflow-y: visible;">
	                        <label>' . Sobi::Txt( 'SOBI_SELECT_ENTRY_TYPE_TITLE' ) . '</label>
	                        <input type="text" data-provide="typeahead" autocomplete="off" id="spctrl-entry-chooser" class="span6" style="width: 95%;" placeholder="' . Sobi::Txt( 'SOBI_SELECT_ENTRY_TYPE' ) . '">
		                </div>
		                <div class="modal-footer">
		                    <a href="#" class="btn btn-secondary" data-dismiss="modal">' . Sobi::Txt( 'SOBI_CLOSE_WINDOW' ) . '</a>
		                    <a href="#" id="spEntrySelect" class="btn btn-success" data-dismiss="modal">' . Sobi::Txt( 'SOBI.JMENU_SAVE' ) . '</a>
		                </div>
	                </div>
                </div>
            </div>
            <input type="hidden" name="selectedEntry" id="selectedEntry" value=""/>
            <input type="hidden" name="spctrl-selected-entry-name" id="spctrl-selected-entry-name" value=""/>
        </div>';
	}

	/**
	 * Fallback. Needs to be implemented by each module.
	 *
	 * @param $name
	 *
	 * @return string
	 */
	public function fetchElement( $name )
	{
		return "<div class=\"SobiPro\" data-bs=\"5\" data-site=\"adm\"></div>";
	}

	/**
	 * @return string
	 * @throws \Sobi\Error\Exception
	 * @throws \Exception
	 */
	protected function getCalendar()
	{
		if ( $this->task == 'list.date' ) {
			$link = $this->getLink();
			$query = [];
			parse_str( $link, $query );
			$selected = [ 'year' => null, 'month' => null, 'day' => null ];
			if ( isset( $query[ 'date' ] ) ) {
				$date = explode( '.', $query[ 'date' ] );
				$selected[ 'year' ] = isset( $date[ 0 ] ) && $date[ 0 ] ? $date[ 0 ] : null;
				$selected[ 'month' ] = isset( $date[ 1 ] ) && $date[ 1 ] ? $date[ 1 ] : null;
				$selected[ 'day' ] = isset( $date[ 2 ] ) && $date[ 2 ] ? $date[ 2 ] : null;
			}
			else {
				$query[ 'date' ] = C::ES;
			}
			$months = [ null => Sobi::Txt( 'FMN.HIDDEN_OPT' ) ];
			$monthsNames = Sobi::Txt( 'JS_CALENDAR_MONTHS' );
			$monthsNames = explode( ',', $monthsNames );
			$years = [ null => Sobi::Txt( 'FD.SEARCH_SELECT_LABEL' ) ];
			for ( $i = 1; $i < 12; $i++ ) {
				$months[ $i ] = $monthsNames[ $i - 1 ];
			}
			$days = [ null => Sobi::Txt( 'FMN.HIDDEN_OPT' ) ];

			for ( $i = 1; $i < 32; $i++ ) {
				$days[ $i ] = $i;
			}
			$exYears = Sobi\Lib\Factory::Db()
				->dselect( 'EXTRACT( YEAR FROM createdTime )', 'spdb_object' )
				->loadResultArray();
			if ( count( $exYears ) ) {
				foreach ( $exYears as $year ) {
					$years[ $year ] = $year;
				}
			}

			return
				'<div class="SobiPro spctrl-calendar">' .
				SPHtml_Input::select( 'sp_year', $years, $selected[ 'year' ] ) .
				SPHtml_Input::select( 'sp_month', $months, $selected[ 'month' ] ) .
				SPHtml_Input::select( 'sp_day', $days, $selected[ 'day' ] ) .
				'<input type="hidden" name="urlparams[date]" id="selectedDate" value="' . trim( $query[ 'date' ] ) . '"/>
				</div>';

		}
		else {
			SPFactory::header()->addJsCode( 'SobiCore.Ready( function () { SobiPro.jQuery( "#spctrl-calendar" ).parent().css( "display", "none" ); } );' );

			return '<span id="spctrl-calendar"></span>';
		}
	}

	/**
	 * @return string
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function getTemplates()
	{
		$selected = $this->tpl;
		$templates = [];
		$name = $this->tpl ? 'urlparams[sptpl]' : 'urlparams[-sptpl-]';
		$templates[ '' ] = Sobi::Txt( 'SELECT_TEMPLATE_OVERRIDE' );
		$template = Factory::Db()
			->select( 'sValue', 'spdb_config', [ 'section' => $this->section, 'sKey' => 'template', 'cSection' => 'section' ] )
			->loadResult();
		$templateDir = $this->templatePath( $template );
		$this->listTemplates( $templates, $templateDir, $this->oType );
		$params = [ 'id' => 'sptpl' ];
		$field = SPHtml_Input::select( $name, $templates, $selected, false, $params );

		return "<div class=\"SobiPro\" data-bs=\"5\" data-site=\"adm\">$field</div>";
	}

	/**
	 * @param $tpl
	 *
	 * @return string
	 * @throws SPException
	 */
	protected function templatePath( $tpl )
	{
		$file = explode( '.', $tpl );
		if ( strstr( $file[ 0 ], 'cms:' ) ) {
			$file[ 0 ] = str_replace( 'cms:', C::ES, $file[ 0 ] );
			$file = SPFactory::mainframe()->path( implode( '.', $file ) );
			$template = SPLoader::path( $file, 'root', false, C::ES );
		}
		else {
			$template = SOBI_PATH . '/usr/templates/' . str_replace( '.', '/', $tpl );
		}

		return $template;
	}

	/**
	 * @param $arr
	 * @param $path
	 * @param $type
	 */
	protected function listTemplates( &$arr, $path, $type )
	{
		switch ( $type ) {
			case 'entry':
			case 'entry.add':
			case 'section':
			case 'category':
			case 'search':
				$path = FileSystem::FixPath( $path . '/' . $this->oType );
				break;
			case 'list.user':
			case 'list.date':
				$path = FileSystem::FixPath( $path . '/listing' );
				break;
			default:
				if ( strstr( $type, 'list' ) ) {
					$path = FileSystem::FixPath( $path . '/listing' );
				}
				break;
		}
		if ( file_exists( $path ) ) {
			$files = scandir( $path );
			if ( is_array( $files ) && count( $files ) ) {
				foreach ( $files as $file ) {
					$stack = explode( '.', $file );
					if ( array_pop( $stack ) == 'xsl' ) {
						$arr[ $stack[ 0 ] ] = $file;
					}
				}
			}
		}
	}

	/**
	 * @param $sid
	 */
	protected function determineObjectType( $sid )
	{
		return;

//		$this->oType = null;
//		if ( $this->task ) {
//			$this->oTypeName = Sobi::Txt( 'TASK_' . strtoupper( $this->task ) );
//			$this->oType = $this->task;
//		}
//		else {
//			if ( $sid ) {
//				$this->oType = Factory::Db()
//					->select( 'oType', 'spdb_object', [ 'id' => $sid ] )
//					->loadResult();
//				$this->oTypeName = Sobi::Txt( 'OTYPE_' . strtoupper( $this->oType ) );
//			}
//		}
//		switch ( $this->oType ) {
//			case 'entry':
//				$this->oName = SPFactory::Entry( $sid )->get( 'name' );
//				break;
//			case 'section':
//				$this->oName = SPFactory::Section( $sid )->get( 'name' );
//				break;
//			case 'category':
//				$this->oName = SPFactory::Category( $sid )->get( 'name' );
//				break;
//			default:
//				$this->oName = null;
//				break;
//		}
	}
}
