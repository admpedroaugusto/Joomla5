<?php
/**
 * @package SobiPro Library
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006–2025 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according to section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 14-Jan-2009 by Radek Suski
 * @modified 09 January 2025 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadView( 'interface' );

use Sobi\C;
use Sobi\Input\Input;
use Sobi\FileSystem\FileSystem;
use Sobi\Lib\Factory;
use Sobi\Utils\StringUtils;

/**
 * Class SPFrontView
 */
abstract class SPFrontView extends SPObject implements SPView
{
	/**
	 * @var array
	 */
	protected $_attr = [];
	/**
	 * @var array
	 */
	protected $_config = [];
	/**
	 * @var string
	 */
	protected $_template = C::ES;
	/**
	 * @var array
	 */
	protected $_hidden = [];
	/**
	 * @var bool
	 */
	protected $_fout = true;
	/**
	 * @var bool
	 */
	protected $_plgSect = true;
	/**
	 * @var string
	 */
	protected $_type = 'root';
	/**
	 * @var DOMDocument
	 */
	protected $_xml = null;
	/**
	 * @var string
	 */
	protected $_task = C::ES;
	/**
	 * @var string
	 */
	protected $_templatePath = C::ES;
	/**
	 * @var string
	 */
	protected $tTask = C::ES;
	/**
	 * @var array
	 */
	protected $nonStaticData = null;


	/**
	 * SPFrontView constructor.
	 *
	 * @param null $tTask
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function __construct( $tTask = C::ES )
	{
		$this->tTask = $tTask;
		Sobi::Trigger( 'Create', $this->name(), [ &$this ] );
	}

	/**
	 * @return string
	 * @throws SPException
	 */
	protected function tplPath()
	{
		if ( !$this->_templatePath ) {
			$tpl = Sobi::Cfg( 'section.template', SPC::DEFAULT_TEMPLATE );
			$file = explode( '.', $tpl );
			if ( strstr( $file[ 0 ], 'cms:' ) ) {
				$file[ 0 ] = str_replace( 'cms:', C::ES, $file[ 0 ] );
				$file = SPFactory::mainframe()->path( implode( '.', $file ) );
				$this->_templatePath = SPLoader::dirPath( $file, 'root', false );
			}
			else {
				$this->_templatePath = SPLoader::dirPath( 'usr.templates.' . $tpl, 'front', false );
			}
		}
		SPFactory::registry()->set( 'current_template_path', $this->_templatePath );

		return $this->_templatePath;
	}

	/**
	 * @param $var
	 * @param $label
	 *
	 * @return SPFrontView
	 */
	public function & assign( &$var, $label )
	{
		$this->_attr[ $label ] =& $var;

		return $this;
	}

	/**
	 * @param $var
	 * @param $label
	 *
	 * @return SPFrontView
	 */
	public function & addHidden( $var, $label )
	{
		$this->_hidden[ $label ] = $var;

		return $this;
	}

	/**
	 * @param $path
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function loadCSSFile( $path )
	{
		Sobi::Trigger( 'loadCSSFile', $this->name(), [ &$path ] );

		$tplPath = $this->tplPath();
		if ( FileSystem::Exists( $tplPath . '/' . 'css' . '/' . $path . '.css' ) ) {
			$path = 'absolute.' . $tplPath . '.css.' . $path;
			SPFactory::header()->addCssFile( $path, false, 'all' );
		}
		else {
			SPFactory::header()->addCssFile( $path );
		}
	}

	/**
	 * @param $path
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function loadJsFile( $path )
	{
		Sobi::Trigger( 'loadJsFile', $this->name(), [ &$path ] );
		if ( FileSystem::Exists( $this->tplPath() . '/' . 'js' . '/' . $path . '.js' ) ) {
			$path = 'absolute.' . $this->tplPath() . '.js.' . $path;
			SPFactory::header()->addJsFile( $path );
		}
		else {
			SPFactory::header()->addJsFile( $path );
		}
	}

	/**
	 *
	 */
	public function parseTemplate()
	{
	}

	/**
	 * @param $template
	 *
	 * @return $this
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function & setTemplate( $template ): self
	{
		$file = explode( '.', $template );
		if ( strstr( $file[ 0 ], 'cms:' ) ) {
			$file[ 0 ] = str_replace( 'cms:', C::ES, $file[ 0 ] );
			$file = SPFactory::mainframe()->path( implode( '.', $file ) );
			$this->_template = SPLoader::path( $file, 'root', false, C::ES );
		}
		else {
			$this->_template = SOBI_PATH . '/usr/templates/' . str_replace( '.', '/', $template );
		}
		Sobi::Trigger( 'setTemplate', $this->name(), [ &$this->_template ] );

		return $this;
	}

	/**
	 * @param $title
	 *
	 * @deprecated since 2.0
	 */
	public function setTitle( $title )
	{
//		Sobi::Trigger( 'setTitle', $this->name(), [ &$title ] );
//		SPFactory::header()->setTitle( Sobi::Txt( $title ) );
	}

	/**
	 * Returns copy of stored key.
	 *
	 * @param $key
	 * @param null $def
	 * @param string $section
	 *
	 * @return array|mixed|string|null
	 * @internal param string $label
	 */
	public function key( $key, $def = null, $section = 'general' )
	{
		if ( strstr( $key, '.' ) ) {
			$key = explode( '.', $key );
			$section = $key[ 0 ];
			$key = $key[ 1 ];
		}

		return $this->_config[ $section ][ $key ] ?? Sobi::Cfg( $key, $def, $section );
	}

	/**
	 * Returns copy of stored key.
	 *
	 * @param string $section
	 *
	 * @return array
	 */
	public function csection( $section )
	{
		return $this->_config[ $section ] ?? [];
	}

	/**
	 * @return mixed|void
	 */
	private function pb()
	{
		/** WARNING!!!
		 * This part is "encoded", not to complicate or hide anything.
		 * The "Powered By" footer can be easily disabled in the SobiPro configuration.
		 * We are not forcing anyone to display it nor violate anyone's freedom!!
		 * But for some reason it happens from time to time that some very clever people instead of disable it the right way
		 * prefer to tinker in the core code which of course lead to the famous situation "no I cannot update because I modified the code"
		 *
		 * So actually this is encoded here just to protect some people from their own, well, "intelligence" ...
		 * */
		$p = "YToxOntpOjA7czoyNDA6IjxkaXYgaWQ9InNwLXNvYmlwcm8tZm9vdGVyIj5Qb3dlcmVkIGJ5IDxhIHRpdGxlPSJTb2JpUHJvIC0gRGlyZWN0b3J5IGNvbXBvbmVudCB3aXRoIGNvbnRlbnQgY29uc3RydWN0aW9uIHN1cHBvcnQiIGhyZWY9Imh0dHBzOi8vd3d3LnNpZ3NpdS5uZXQiIGFyaWEtbGFiZWw9IlNvYmlQcm8gLSAgRGlyZWN0b3J5IGNvbXBvbmVudCB3aXRoIGNvbnRlbnQgY29uc3RydWN0aW9uIHN1cHBvcnQiPlNvYmlQcm88L2E+PC9kaXY+CiI7fQ==";
		if ( !Sobi::Cfg( 'show_pb', true ) || Input::Cmd( 'method', 'post' ) == 'xhr' ) {
			return;
		}
		try {
			$p = SPConfig::unserialize( $p );
		}
		catch ( SPException $x ) {
			return;
		}

		return $p[ 0 ];
	}

	/**
	 * @return void
	 */
	protected function jsonDisplay()
	{
		echo json_encode( $this->_attr );
	}

	/**
	 * @param string $out
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception|\DOMException
	 * @throws \Exception
	 */
	public function display( string $out = C::ES )
	{
		if ( Input::Cmd( 'format' ) == 'json' && Sobi::Cfg( 'output.json_enabled', false ) ) {
			$this->jsonDisplay();

			return;
		}

		$this->templateSettings();

		$type = $this->key( 'template_type', 'xslt' );
		if ( $type != 'php' && Sobi::Cfg( 'global.disable_xslt', false ) ) {
			$type = 'php';
		}
		$task = Input::Task();

		$functions = [];
		if ( $this->key( 'functions' ) ) {
			$functions = $this->registerFunctions();
		}

		$parserClass = SPLoader::loadClass( 'mlo.template_' . $type );
		if ( $parserClass ) {
			/** @var SPTemplateXSLT $parser */
			$parser = new $parserClass();
		}
		else {
			throw new SPException( SPLang::e( 'CANNOT_LOAD_PARSER', $type ) );
		}

		/* Fix URl if available */
		$this->_attr[ 'template_path' ] = $this->_templatePath ? FileSystem::FixUrl( str_replace( SOBI_ROOT, Sobi::Cfg( 'live_site' ), $this->_templatePath ) ) : C::ES;

		$messages = SPFactory::message()->getMessages();
		if ( count( $messages ) ) {
			foreach ( $messages as $type => $content ) {
				$this->_attr[ 'messages' ][ $type ] = array_values( $content );
			}
		}

		$visitor = $this->get( 'visitor' );
		if ( $visitor && !( is_array( $visitor ) ) ) {
			$this->_attr[ 'visitor' ] = $this->visitorArray( $visitor );
		}

		$parser->setProxy( $this );
		$parser->setData( $this->_attr );
		$parser->setXML( $this->_xml );
		$parser->setCacheData( [ 'hidden' => $this->_hidden ] );
		$parser->setType( $this->_type );
		$parser->setTemplate( $this->_template );

		Sobi::Trigger( 'Display', $this->name(), [ $type, &$this->_attr ] );

		$html = C::ES;
		$out = $out ? : strtolower( $this->key( 'output', $this->key( 'output', 'html' ), $this->tTask ) );
		//$action = $this->key( 'form.action' );
		$form = $this->csection( 'form' );
		if ( $form ) {
			$opt = SPFactory::mainframe()->form();
			if ( is_array( $opt ) && count( $opt ) ) {
				foreach ( $opt as $l => $v ) {
					$this->addHidden( $v, $l );
				}
			}
			$form[ 'method' ] = isset( $form[ 'method' ] ) && $form[ 'method' ] ? $form[ 'method' ] : 'post';
			$form[ 'action' ] = Sobi::Cfg( 'live_site' ) . 'index.php';
			$html .= "\n<form ";
			foreach ( $form as $p => $value ) {
				$html .= $p . '="' . $value . '" ';
			}
			$html .= "data-spctrl=\"form\" >\n";
		}
		$html .= $parser->display( $out, $functions );

		$hidden = C::ES;
		if ( is_array( $this->_hidden ) && count( $this->_hidden ) ) {
			$this->_hidden[ Factory::Application()->token() ] = 1;
			foreach ( $this->_hidden as $name => $value ) {
				$hidden .= "\n<input type=\"hidden\" id=\"SP_$name\" name=\"$name\" value=\"$value\"/>";
			}
			// xhtml strict valid
			$hidden = "<div>$hidden</div>";
			$html .= $hidden;
		}

		$html .= $form ? "\n</form>\n" : C::ES;
		/* SobiPro type specific content parser */
		Sobi::Trigger( 'ContentDisplay', $this->name(), [ &$html ] );

		/* common content parser */
		$cParse = $this->key( 'parse', -1 );
		/* if it was specified in the template config file or it was set in the section config and not disabled in the template config */
		if ( !( strstr( $task, '.edit' ) || strstr( $task, '.add' ) || in_array( $task, Sobi::Cfg( 'plugins.content_disable', [] ) ) ) ) {
			if ( $cParse == 1 || ( Sobi::Cfg( 'parse_template_content', false ) && $cParse == -1 ) ) {
				Sobi::Trigger( 'Parse', 'Content', [ &$html ] );
			}
		}

		header( 'SobiPro: ' . Sobi::Section() );

		if ( Input::Int( 'crawl' ) ) {
			SPFactory::cache()->setJoomlaCaching( false );
		}

		if ( $out == 'html' && ( !strlen( Input::Cmd( 'format' ) ) || Input::Cmd( 'format' ) == 'html' || Input::Int( 'crawl' ) ) ) {
			$html .= $this->pb();
			if ( ( Input::Cmd( 'dbg' ) || Sobi::Cfg( 'debug', false ) ) && Sobi::My( 'id' ) ) {
				$start = Sobi::Reg( 'start' );
				$mem = $start[ 0 ];
				$time = $start[ 1 ];
				$queries = Factory::Db()->getCount();
				$mem = number_format( memory_get_usage() - $mem );
				$time = microtime( true ) - $time;
				SPConfig::debOut( "Memory: $mem<br/>Time: $time<br/> Queries: $queries" );
			}
			$templateName = Sobi::Cfg( 'section.template', SPC::DEFAULT_TEMPLATE );
			$fw = Sobi::Cfg( 'template.framework-style', C::BOOTSTRAP5 );
			$version = Factory::ApplicationHelper()->myVersion( true );
			$trim = defined( 'SOBI_TRIMMED' ) ? 'yes' : 'no';

			echo "\n<!-- Start of SobiPro component 2.x -->\n<div id=\"SobiPro\" class=\"SobiPro $templateName\" data-template=\"$templateName\" data-bs=\"$fw\" data-version=\"$version\" data-trial=\"$trim\" data-site=\"site\">\n$html\n</div>\n<!-- End of SobiPro component; Copyright (C) 2006–2025 Sigsiu.NET GmbH -->\n";
		}
		else {
			$this->customOutput( $html );
		}
		Sobi::Trigger( 'AfterDisplay', $this->name() );
	}

	/**
	 * @param $output
	 *
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function customOutput( $output )
	{
		$header = $this->key( 'output.header', false );
		if ( $this->key( 'output.clear', false ) ) {
			SPFactory::mainframe()->cleanBuffer();
		}
		if ( strlen( $header ) ) {
			header( $header );
		}
		if ( Input::Int( 'crawl' ) ) {
			header( 'SobiPro: ' . Sobi::Section() );
		}
		if ( Input::Cmd( 'xhr' ) == 1 ) {
			SPFactory::mainframe()
				->cleanBuffer()
				->customHeader();
		}
		echo $output;
		if ( $this->key( 'output.close', false ) ) {
			exit;
		}
	}

	/**
	 * @return array|bool
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function registerFunctions()
	{
		static $classes = [];
		$functions = [];
		$package = Sobi::Reg( 'current_template' );
		if ( FileSystem::Exists( FileSystem::FixPath( $package . '/' . $this->key( 'functions' ) ) ) ) {
			$path = FileSystem::FixPath( $package . '/' . $this->key( 'functions' ) );
			$content = file_get_contents( $path );
			$class = [];
			preg_match( '/\s*(class)\s+(\w+)/', $content, $class );
			if ( isset( $class[ 2 ] ) ) {
				$className = $class[ 2 ];
			}
			else {
				Sobi::Error( $this->name(), SPLang::e( 'Cannot determine class name in file %s.', str_replace( SOBI_ROOT . '/', C::ES, $path ) ), C::WARNING, 0 );

				return false;
			}
			if ( !isset( $classes[ $className ] ) ) {
				include_once( $path );
				$classes[ $className ] = $path;
			}
			else {
				if ( $classes[ $className ] != $path ) {
					Sobi::Error( __CLASS__, 'Class with this name has already been defined, but this is not the same class.', C::WARNING );

					return [];
				}
			}
			$methods = get_class_methods( $className );
			if ( count( $methods ) ) {
				foreach ( $methods as $method ) {
					$functions[] = $className . '::' . $method;
				}
			}
		}
		else {
			Sobi::Error( $this->name(), SPLang::e( 'FUNCFILE_DEFINED_BUT_FILE_DOES_NOT_EXISTS', $this->_template . '/' . $this->key( 'functions' ) ), C::WARNING, 0 );
		}

		return $functions;
	}

	/**
	 * @param $attr
	 * @param null $vars
	 */
	public function txt( $attr, $vars = null )
	{
		echo Sobi::Txt( $attr, $vars );
	}

	/**
	 * @param $obj
	 *
	 * @return string|string[]
	 * @throws SPException
	 */
	protected function metaKeys( $obj )
	{
		$keys = $obj->get( 'metaKeys' );
		$arrayMeta = ( !empty( $keys ) ) ? explode( Sobi::Cfg( 'string.meta_keys_separator', ',' ), $keys ) : C::ES;
		if ( is_array( $arrayMeta ) && count( $arrayMeta ) ) {
			foreach ( $arrayMeta as $i => $v ) {
				$arrayMeta[ $i ] = trim( $v );
			}
		}

		return $arrayMeta;
	}

	/**
	 * @return mixed|null
	 * @throws SPException|\Sobi\Error\Exception
	 * @internal param mixed $attr
	 */
	public function field()
	{
		$params = func_get_args();
		$field = C::ES;
		if ( isset( $params[ 0 ] ) ) {
			if ( method_exists( 'SPHtml_input', $params[ 0 ] ) ) {
				foreach ( $params as $i => $param ) {
					if ( is_string( $param ) && strstr( $param, 'value:' ) ) {
						$param = str_replace( 'value:', C::ES, $param );
						$params[ $i ] = $this->get( $param );
					}
				}
				$method = $params[ 0 ];
				array_shift( $params );
				$field = call_user_func_array( [ 'SPHtml_Input', $method ], $params );
			}
			else {
				Sobi::Error( $this->name(), SPLang::e( 'METHOD_DOES_NOT_EXISTS', $params[ 0 ] ), C::WARNING, 0, __LINE__, __FILE__ );
			}
		}
		else {
			Sobi::Error( $this->name(), SPLang::e( 'NOT_ENOUGH_PARAMETERS' ), C::NOTICE, 0, __LINE__, __FILE__ );
		}
		if ( $this->_fout ) {
			echo $field;
		}
		else {
			return $field;
		}

		return $field;
	}

	/**
	 * @param $cfg
	 * @param $template
	 *
	 * @return SPFrontView
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function & setConfig( $cfg, $template )
	{
		$this->_config = $cfg;
		if ( isset( $cfg[ $template ] ) && count( $cfg[ $template ] ) ) {
			foreach ( $cfg[ $template ] as $k => $v ) {
				$this->_config[ $k ] = $v;
			}
		}
		if ( isset( $this->_config[ 'general' ][ 'css_files' ] ) ) {
			$this->_config[ 'general' ][ 'css_files' ] = explode( ',', $this->_config[ 'general' ][ 'css_files' ] );
			foreach ( $this->_config[ 'general' ][ 'css_files' ] as $file ) {
				if ( trim( $file ) ) {
					$this->loadCSSFile( trim( $file ) );
				}
			}
		}
		if ( isset( $this->_config[ 'general' ][ 'js_files' ] ) ) {
			$this->_config[ 'general' ][ 'js_files' ] = explode( ',', $this->_config[ 'general' ][ 'js_files' ] );
			foreach ( $this->_config[ 'general' ][ 'js_files' ] as $file ) {
				if ( trim( $file ) ) {
					$this->loadJsFile( trim( $file ) );
				}
			}
		}
//		if ( $this->key( 'site_title' ) ) {
//			$this->setTitle( $this->key( 'site_title' ) );
//		}
		if ( isset( $this->_config[ 'hidden' ] ) ) {
			foreach ( $this->_config[ 'hidden' ] as $name => $defValue ) {
				$this->addHidden( Input::String( $name, 'request', $defValue ), $name );
			}
		}
		Sobi::Trigger( 'afterLoadConfig', $this->name(), [ &$this->_config ] );

		return $this;
	}

	/**
	 * Used in views/tpl/icon.php to show the elements of the category images popup box.
	 *
	 * @param $attr
	 * @param int $index
	 *
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function show( $attr, $index = -1 )
	{
		if ( strstr( $attr, 'config.' ) !== false ) {
			echo $this->key( str_replace( 'config.', C::ES, $attr ) );
		}
		else { /* fictive attribute added, to remove path and get only name */
			if ( $attr == 'files.shortname' ) {
				$name = $this->get( 'files.name', $index );
				$pos = strrpos( $name, '/' );
				if ( !( $pos === false ) ) {
					$name = substr( $name, $pos + 1 );
				}
				echo $name;
			}
			else {
				echo $this->get( $attr, $index );
			}
		}
	}

	/**
	 * @param $attr
	 * @param int $index
	 *
	 * @return int
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function count( $attr, $index = -1 )
	{
		$element =& $this->get( $attr, $index );

		return count( $element );
	}

	/**
	 * @param string $attr
	 * @param mixed $name
	 *
	 * @return $this|SPFrontView
	 */
	public function & set( $attr, $name )
	{
		$this->_attr[ $name ] = $attr;

		return $this;
	}

	/**
	 * @param string $attr
	 * @param int $index
	 *
	 * @return mixed|string|null
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function & get( $attr, $index = -1 )
	{
		if ( strstr( $attr, '.' ) ) {
			$properties = explode( '.', $attr );
		}
		else {
			$properties[ 0 ] = $attr;
		}
		if ( isset( $this->_attr[ $properties[ 0 ] ] ) ) {
			$var = null;
			/* if array field */
			if ( $index > -1 ) {
				if ( is_array( $this->_attr[ $properties[ 0 ] ] ) && isset( $this->_attr[ $properties[ 0 ] ][ trim( $index ) ] ) ) {
					$var = $this->_attr[ $properties[ 0 ] ][ trim( $index ) ];
				}
				else {
					Sobi::Error( $this->name(), SPLang::e( 'ATTR_DOES_NOT_EXISTS', $attr ), C::NOTICE, 0, __LINE__, __FILE__ );
				}
			}
			else {
				$var = $this->_attr[ $properties[ 0 ] ];
			}
			/* remove first field of properties */
			array_shift( $properties );
			/* if there are still fields in array, accessing object attribute or array field */
			if ( is_array( $properties ) && count( $properties ) ) {
				foreach ( $properties as $property ) {
					$property = trim( $property );
					/* it has to be SPObject subclass to access the attribute */
					if ( !is_array( $var ) && method_exists( $var, 'has' ) && $var->has( $property ) ) {
						if ( method_exists( $var, 'get' ) ) {
							$var = $var->get( $property );
						}
						else {
							/*@TODO need to create error object */
							$retval = C::ES;

							return $retval;
						}
					}
					/* otherwise, try to access array field */
					else {
						if ( is_array( $var ) /*&& key_exists( $property, $var )*/ ) {
							$var = $var[ $property ];
						}
						else {
							/* nothing to show */
							Sobi::Error( $this->name(), SPLang::e( 'NO_PROPERTY_TO_SHOW', $attr ), C::NOTICE, 0, __LINE__, __FILE__ );
							/*@TODO need to create error object */
							$retval = C::ES;

							return $retval;
						}
					}
				}
			}
			$retval = $var;
		}
		else {
			$retval = C::ES;
		}
		$retval = is_string( $retval ) ? StringUtils::Clean( $retval ) : $retval;

		return $retval;
	}

	/**
	 * @param $data
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function alphaMenu( &$data )
	{
		if ( $this->key( 'alphamenu.show', Sobi::Cfg( 'alphamenu.show' ) ) ) {
			$letters = explode( ',', $this->key( 'alphamenu.letters', Sobi::Cfg( 'alphamenu.letters' ) ) );

			/** @var SPEntry $entry */
			$entry = SPFactory::Model( 'entry' );
			$entry->loadFields( Sobi::Section() );
			$fs = $entry->getFields( 'id' );
			$defField = true;
			if ( count( $letters ) ) {
				foreach ( $letters as $i => $letter ) {
					$letters[ $i ] = trim( $letter );
				}
			}
			$field = explode( '.', Input::Task( 'get' ) );
			if ( strstr( Input::Task( 'get' ), 'field' ) && isset( $field[ 3 ] ) ) {
				$field = $field[ 3 ];
				$defField = false;
			}
			else {
				if ( strlen( Input::Cmd( 'alpha_field' ) ) ) {
					$field = Input::Cmd( 'alpha_field' );
					$defField = false;
				}
				else {
					$field = Sobi::Cfg( 'alphamenu.primary_field', SPFactory::config()->nameField()->get( 'id' ) );
					if ( isset( $fs[ $field ] ) && ( $fs[ $field ] instanceof SPObject ) ) {
						$field = $fs[ $field ]->get( 'nid' );
					}
					else {
						$id = SPFactory::config()->nameField()->get( 'id' );
						if ( $id && in_array( $id, $fs ) ) {       //check if the user hasn't disabled the name field for what reason ever
							$field = $fs[ $id ]->get( 'nid' );
						}
					}
				}
			}
			$cat = ( strpos( Input::Task(), 'list.alpha' ) === 0 ) ? Input::Int( 'cat' ) : Input::Sid();
			if ( $this->key( 'alphamenu.verify', Sobi::Cfg( 'alphamenu.verify' ) ) ) {
				if ( Sobi::Cfg( 'alphamenu.catdependent', false ) ) {
					$entries = SPFactory::cache()->getVar( 'alpha_entries_' . $field . $cat );
				}
				else {
					$entries = SPFactory::cache()->getVar( 'alpha_entries_' . $field );
				}
				if ( !$entries ) {

					/** @var SPAlphaListing $alphCtrl */
					$alphCtrl = SPFactory::Instance( 'opt.listing.alpha' );
					$entries = [];
					$entriesRecursive = (bool) $this->key( 'entries_recursive', Sobi::Cfg( 'category.entries_recursive', false ), 'general' );
					$categories = $alphCtrl->categories( $entriesRecursive );
					foreach ( $letters as $letter ) {
						$params = [ 'letter' => $letter ];
						if ( $field ) {
							$params[ 'field' ] = $field;
						}
						$alphCtrl->setParams( $params );
						$entries[ $letter ] = $alphCtrl->entries( $field, $categories );
					}
					if ( Sobi::Cfg( 'alphamenu.catdependent', false ) ) {
						SPFactory::cache()->addVar( $entries, 'alpha_entries_' . $field . $cat );
					}
					else {
						SPFactory::cache()->addVar( $entries, 'alpha_entries_' . $field );
					}
				}
				foreach ( $letters as $letter ) {
					$le = [ '_complex' => 1, '_data' => trim( $letter ) ];

					$urlLetter = SPFactory::Instance( 'types.string', $letter )
						->toLower()
						->trim()
						->get();
					if ( is_array( $entries[ $letter ] ) && count( $entries[ $letter ] ) ) {
						if ( !$defField ) {
							$task = 'list.alpha.' . $urlLetter . '.' . $field;
						}
						else {
							$task = 'list.alpha.' . $urlLetter;
						}
						if ( Sobi::Cfg( 'alphamenu.catdependent', false ) ) {
							$le[ '_attributes' ] = [ 'url' => Sobi::Url( [ 'sid' => Sobi::Section(), 'task' => $task, 'cat' => $cat ] ) ];
						}
						else {
							$le[ '_attributes' ] = [ 'url' => Sobi::Url( [ 'sid' => Sobi::Section(), 'task' => $task ] ) ];
						}
					}
					$l[] = $le;
				}
			}
			else {
				foreach ( $letters as $i => $letter ) {
					$urlLetter =
						SPFactory::Instance( 'types.string', $letter )
							->toLower()
							->trim()
							->get();
					if ( Sobi::Cfg( 'alphamenu.catdependent', false ) ) {
						$attributes = [ 'url' => Sobi::Url( [ 'sid' => Sobi::Section(), 'task' => 'list.alpha.' . $urlLetter, 'cat' => $cat ] ) ];
					}
					else {
						$attributes = [ 'url' => Sobi::Url( [ 'sid' => Sobi::Section(), 'task' => 'list.alpha.' . $urlLetter ] ) ];
					}
					$l[] = [
						'_complex'    => 1,
						'_data'       => trim( $letter ),
						'_attributes' => $attributes,
					];
				}
			}

			$category = [];
			if ( Sobi::Cfg( 'alphamenu.catdependent', false ) ) {
				$c = SPFactory::Category( $cat );
				$category = [
					'_complex'    => 1,
					'_data'       => $c->get( 'name' ),
					'_attributes' => [ 'dependent' => 'true', 'nid' => $c->get( 'nid' ), 'id' => $cat, 'lang' => Sobi::Lang( false ) ],
				];
			}
			/*
			 <fields current="field_name">
			  <field_name>Company Name</field_name>
			  <field_city>City</field_city>
			  <field_contact>Contact Person</field_contact>
			  <field_phone>Phone</field_phone>
			  <field_country>Country</field_country>
			</fields>
			*/
			$fields = Sobi::Cfg( 'alphamenu.extra_fields_array' );
			$extraFields = [];
			if ( is_array( $fields ) && count( $fields ) ) {
				array_unshift( $fields, Sobi::Cfg( 'alphamenu.primary_field' ) );
				foreach ( $fields as $fid ) {
					if ( isset( $fs[ $fid ] ) && method_exists( $fs[ $fid ], 'get' ) ) {
						if ( $fs[ $fid ]->get( 'enabled' ) ) {
							$extraFields[ $fs[ $fid ]->get( 'nid' ) ] = $fs[ $fid ]->get( 'name' );
						}
					}
				}
				if ( count( $extraFields ) < 2 ) {
					$extraFields = [];
				}
				$extraFields = [
					'_complex'    => 1,
					'_data'       => $extraFields,
					'_attributes' => [ 'current' => $field ],
				];
			}
			$data[ 'alphaMenu' ] = [ '_complex' => 1, '_data' => [ 'letters' => $l, 'fields' => $extraFields, 'category' => $category ] ];
		}
	}

	/**
	 * @param $data
	 * @param $recent
	 * @param $fields
	 *
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function switchOrderingMenu( &$data, $recent, $fields )
	{
		$orderFields = $this->get( 'orderFields' );
		$oFields = C::ES;
		if ( is_array( $orderFields ) && count( $orderFields ) ) {
			foreach ( $orderFields as $fid => $order ) {
				if ( !is_array( $fields ) ) {
					unset( $orderFields[ $fid ] );
				}
				else {
					if ( !in_array( $fid, $fields ) ) {
						unset( $orderFields[ $fid ] );
					}
				}
			}

			$oFields = [
				'_complex'    => 1,
				'_data'       => $orderFields,
				'_attributes' => [ 'current' => $recent, 'lang' => Sobi::Lang( false ) ],
			];
		}
		$data[ 'orderingMenu' ] = [ '_complex' => 1, '_data' => [ 'orderings' => $oFields ] ];
	}


	/**
	 * @param $data
	 *
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function menuOptions( &$data )
	{
		$menuparams = SPFactory::mainframe()->getMenuParams();
		if ( $menuparams ) {
			$data[ 'jheading' ] = [
				'_complex'    => 1,
				'_data'       => $menuparams->get( 'page_heading' ),
				'_attributes' => [
					'lang'              => Sobi::Lang( false ),
					'page_heading'      => $menuparams->get( 'page_heading' ),
					'show_page_heading' => ( $menuparams->get( 'show_page_heading' ) == C::ES ) ? 0 : $menuparams->get( 'show_page_heading' ),
					'pageclass_sfx'     => $menuparams->get( 'pageclass_sfx' ),
				],
			];
		}
	}

	/**
	 * @param \SPUser $visitor
	 *
	 * @return array
	 */
	protected function visitorArray( $visitor )
	{
		if ( $visitor->get( 'guest' ) == 1 ) {
			$usertype = SPC::USERTYPE_VISITOR;
		}
		elseif ( $visitor->get( 'isRoot' ) ) {
			$usertype = SPC::USERTYPE_ADMINISTRATOR;
		}
		else {
			$usertype = SPC::USERTYPE_REGISTERED;
		}

		return [
			'_complex'    => 1,
			'_data'       => [
				'name'     => $visitor->get( 'name' ),
				'username' => $visitor->get( 'username' ),
				'usertype' => [
					'_complex'    => 1,
					'_data'       => $usertype,
					'_attributes' => [ 'gid' => implode( ', ', $visitor->get( 'gid' ) ) ],
				],
			],
			'_attributes' => [ 'id' => $visitor->get( 'id' ) ],
		];
	}

	/**
	 * @param string $action
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function trigger( string $action )
	{
		echo Sobi::TriggerPlugin( $action, $this->_plgSect );
	}

	/**
	 * @param $id
	 * @param bool $parents
	 *
	 * @return string
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function parentPath( $id, bool $parents = false ): string
	{
		$path = SPFactory::config()->getParentPath( $id, true, $parents );

		return StringUtils::Clean( count( $path ) ? implode( Sobi::Cfg( 'string.path_separator', ' > ' ), $path ) : C::ES );
	}

	/**
	 * Load all non-static data.
	 * At the moment only the counter.
	 *
	 * @param array $objects
	 *
	 * @throws \Sobi\Error\Exception
	 */
	protected function loadNonStaticData( array $objects )
	{
		$this->nonStaticData = Factory::Db()
			->select( [ 'counter', 'sid' ], 'spdb_counter', [ 'sid' => $objects ] )
			->loadAssocList( 'sid' );
	}

	/**
	 * @param int $id
	 * @param string $attribute
	 *
	 * @return mixed|string
	 */
	protected function getNonStaticData( int $id, string $attribute )
	{
		return $this->nonStaticData[ $id ][ $attribute ] ?? C::ES;
	}

	/**
	 * @param $fields
	 * @param $view
	 *
	 * @return array
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function fieldStruct( $fields, $view )
	{
		$data = [];
		foreach ( $fields as $field ) {
			if ( $field->enabled( $view ) && $field->get( 'id' ) != Sobi::Cfg( 'entry.name_field' ) ) {
				$struct = $field->struct();
				$options = null;
				if ( isset( $struct[ '_options' ] ) ) {
					$options = $struct[ '_options' ];
					unset( $struct[ '_options' ] );
				}
				$data[ $field->get( 'nid' ) ] = [
					'_complex'    => 1,
					'_data'       => [
						'label' => [
							'_complex'    => 1,
							'_data'       => $field->get( 'name' ),
							'_attributes' => [ 'lang' => Sobi::Lang( false ), 'show' => $field->get( 'withLabel' ) ],
						],
						'data'  => $struct,
					],
					'_attributes' => [ 'id'             => $field->get( 'id' ),
					                   'itemprop'       => $field->get( 'itemprop' ),
					                   'type'           => $field->get( 'type' ),
					                   'suffix'         => $field->get( 'suffix' ),
					                   'position'       => $field->get( 'position' ),
					                   'numeric'        => ( $field->get( 'numeric' ) == 1 ) ? 1 : 0,
					                   'untranslatable' => ( $field->get( 'untranslatable' ) == 1 ) ? 1 : 0,
					                   'css_view'       => $field->get( 'cssClassView' ),
					                   'css_class'      => ( strlen( $field->get( 'cssClass' ) ) ? $field->get( 'cssClass' ) : 'sp-field' ),
					],
				];
				if ( Sobi::Cfg( 'entry.field_description', false ) ) {
					$data[ $field->get( 'nid' ) ][ '_data' ][ 'description' ] = [ '_complex' => 1, '_xml' => 1, '_data' => $field->get( 'description' ) ];
				}
				if ( $options ) {
					$data[ $field->get( 'nid' ) ][ '_data' ][ 'options' ] = $options;
				}
				if ( isset( $struct[ '_xml_out' ] ) && count( $struct[ '_xml_out' ] ) ) {
					foreach ( $struct[ '_xml_out' ] as $k => $v )
						$data[ $field->get( 'nid' ) ][ '_data' ][ $k ] = $v;
				}
			}
		}
		$this->validateFields( $data );

		return $data;
	}

	/**
	 * @param array $fields
	 *
	 * @throws SPException
	 */
	protected function validateFields( array $fields )
	{
		foreach ( $fields as $data ) {
			if ( isset( $data[ '_data' ][ 'data' ][ '_validate' ] ) && count( $data[ '_data' ][ 'data' ][ '_validate' ] ) ) {
				$class = str_replace( [ '/', '.php' ], [ '.', C::ES ], $data[ '_data' ][ 'data' ][ '_validate' ][ 'class' ] );
				if ( $class ) {
					$method = $data[ '_data' ][ 'data' ][ '_validate' ][ 'method' ];
					$class = SPLoader::loadClass( $class );
					$class::$method( $data[ '_data' ][ 'data' ] );
				}
			}
		}
	}

	/**
	 * @param $data
	 *
	 * @throws SPException|\Exception
	 */
	protected function fixTimes( &$data )
	{
		$fix = [ 'valid_since', 'valid_until', 'updated_time', 'created_time' ];
		static $offset = null;
		if ( $offset === null ) {
			$offset = SPFactory::config()->getTimeOffset();
		}
		foreach ( $fix as $index ) {
			if ( !isset( $data[ $index ] ) || !$data[ $index ] ) {
				continue;
			}
			$timestamp = strtotime( $data[ $index ] . 'UTC' );
			$data[ $index ] = [
				'_complex'    => 1,
				'_data'       => gmdate( Sobi::Cfg( 'date.publishing_format', SPC::DEFAULT_DATE ), $timestamp + $offset ),
				'_attributes' => [
					'UTC'       => $data[ $index ],
					'timestamp' => $timestamp,
					'offset'    => $offset,
					'timezone'  => Sobi::Cfg( 'time_offset' ),
				],
			];
		}
	}

	/**
	 * @param $data
	 *
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function menu( &$data )
	{
		if ( Sobi::Cfg( 'general.top_menu', true ) ) {
			$data[ 'menu' ] = [
				'front' => [
					'_complex'    => 1,
					'_data'       => Sobi::Reg( 'current_section_name' ),
					'_attributes' => [
						'lang' => Sobi::Lang( false ), 'url' => Sobi::Url( [ 'sid' => Sobi::Section() ] ),
					],
				],
			];
			if ( Sobi::Can( 'section.search' ) ) {
				$data[ 'menu' ][ 'search' ] = [
					'_complex'    => 1,
					'_data'       => Sobi::Txt( 'MN.SEARCH' ),
					'_attributes' => [
						'lang' => Sobi::Lang( false ), 'url' => Sobi::Url( [ 'task' => 'search', 'sid' => Sobi::Section() ] ),
					],
				];
			}
			if ( Sobi::Can( 'entry', 'add', 'own', Sobi::Section() ) ) {
				$data[ 'menu' ][ 'add' ] = [
					'_complex'    => 1,
					'_data'       => Sobi::Txt( 'MN.ADD_ENTRY' ),
					'_attributes' => [
						'lang' => Sobi::Lang( false ), 'url' => Sobi::Url( [ 'task' => 'entry.add', 'sid' => Input::Sid() ] ),
					],
				];
			}
		}
	}

	/**
	 * @param $entry
	 *
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function getFieldsToDisplay( $entry )
	{
		$primaryCat = $entry->getPrimary();
		$fieldsToDisplay = [];
		if ( $primaryCat ) {
			$primaryCatiD = $primaryCat[ 'pid' ];

			/** @var SPCategory $primaryCat */
			$primaryCat = SPFactory::Model( 'category' );
			$primaryCat->load( $primaryCatiD );
			if ( !$primaryCat->get( 'allFields' ) ) {
				$fieldsToDisplay = $primaryCat->get( 'entryFields' );
			}
		}

		return $fieldsToDisplay;
	}

	/**
	 * Gets the template settings from the json files if not already available in _config.
	 */
	protected function templateSettings()
	{
		if ( !( isset( $this->_attr[ 'config' ] ) && count( $this->_attr[ 'config' ] ) ) ) {
			/* if no config data gathered */
			if ( !( isset( $this->_config[ 'general' ] ) && count( $this->_config[ 'general' ] ) ) ) {
				/* if not already available */
				if ( SPLoader::translatePath( "$this->_templatePath.config", 'absolute', true, 'json' ) ) {
					$config = json_decode( FileSystem::Read( SPLoader::translatePath( "$this->_templatePath.config", 'absolute', true, 'json' ) ), true );
					$task = Input::Task() == 'entry.add' ? 'entry.edit' : Input::Task();
					$settings = [];
					foreach ( $config as $section => $setting ) {
						$settings[ str_replace( '-', '.', $section ) ] = $setting;
					}
					if ( Input::Cmd( 'sptpl' ) ) {
						$file = Input::String( 'sptpl' );
					}
					else {
						if ( strstr( $task, '.' ) ) {
							$file = explode( '.', $task );
							$file = $file[ 1 ];
						}
						else {
							$file = $task;
						}
					}
					$templateType = SPFactory::registry()->get( 'template_type' );
					if ( isset( $templateType ) && strstr( $file, $templateType ) ) {
						$file = str_replace( $templateType, C::ES, $file );
					}
					if ( SPLoader::translatePath( "$this->_templatePath.$templateType.$file", 'absolute', true, 'json' ) ) {
						$subConfig = json_decode( FileSystem::Read( SPLoader::translatePath( "$this->_templatePath.$templateType.$file", 'absolute', true, 'json' ) ), true );
						if ( is_array( $subConfig ) && count( $subConfig ) ) {
							foreach ( $subConfig as $section => $subSettings ) {
								foreach ( $subSettings as $k => $v ) {
									$settings[ str_replace( '-', '.', $section ) ][ $k ] = $v;
								}
							}
						}
					}
					if ( isset( $settings[ $task ] ) ) {
						foreach ( $settings[ $task ] as $k => $v ) {
							if ( is_array( $v ) ) { // to be able to use multiselect lists in template settings
								foreach ( $v as $kk => $vv ) {
									$settings[ 'general' ][ $k . '-' . $kk ] = $vv;
								}
							}
							else {
								$settings[ 'general' ][ $k ] = $v;
							}
						}
					}
				}
			}
			else {
				/* config data are already available, remove unnecessary items */
				$settings = $this->_config;
				if ( array_key_exists( 'form', $settings ) ) {
					unset( $settings[ 'form' ] );
				}
				if ( array_key_exists( 'template_type', $settings[ 'general' ] ) ) {
					unset( $settings[ 'general' ][ 'template_type' ] );
				}
				if ( array_key_exists( 'css_files', $settings[ 'general' ] ) ) {
					unset( $settings[ 'general' ][ 'css_files' ] );
				}
				if ( array_key_exists( 'js_files', $settings[ 'general' ] ) ) {
					unset( $settings[ 'general' ][ 'js_files' ] );
				}
				if ( array_key_exists( 'functions', $settings[ 'general' ] ) ) {
					unset( $settings[ 'general' ][ 'functions' ] );
				}
			}

			/* settings from other files are already added to the general array */
			if ( isset( $settings[ 'general' ] ) ) {
				foreach ( $settings[ 'general' ] as $k => $v ) {
					$this->_attr[ 'config' ][ $k ] = [
						'_complex'    => 1,
						'_attributes' => [
							'value' => $v,
						],
					];
				}
			}
//			if ( isset( $settings[ $task ] ) ) {
//				foreach ( $settings[ $task ] as $k => $v ) {
//					if ( is_array( $v ) ) { // to be able to use multiselect lists in template settings
//						foreach ( $v as $kk => $vv ) {
//							$this->_attr[ 'config' ][ $k . '-' . $kk ] = [
//								'_complex'    => 1,
//								'_attributes' => [
//									'value' => $vv,
//								],
//							];
//						}
//					}
//					else {
//						$this->_attr[ 'config' ][ $k ] = [
//							'_complex'    => 1,
//							'_attributes' => [
//								'value' => $v,
//							],
//						];
//					}
//				}
//			}
		}
	}

	/**
	 * @param \SPEntry $entry
	 * @param array $en
	 *
	 * @return array
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function getEntryUrls( SPEntry $entry, array $en ): array
	{
		$user = Sobi::My( 'id' );
		$isOwner = $user && ( $user == $entry->get( 'owner' ) );
		$sid = $entry->get( 'id' );

		/* Edit entry URL: if the user may edit the entry or the user owns the entry and may edit own entries */
		if ( Sobi::Can( 'entry', 'edit', '*' )
			|| ( $isOwner && Sobi::Can( 'entry', 'edit', 'own' ) )
		) {
			$en[ 'edit_url' ] = Sobi::Url( [ 'task' => 'entry.edit', 'sid' => $sid/*, 'pid' => $entry->get( 'parent' )*/ ] );
		}
		else {
			if ( isset( $en[ 'edit_url' ] ) ) {
				unset( $en[ 'edit_url' ] );
			}
		}

		/* Un-approve/approve entry URL: if the user may manage (un-approve/approve) the entry */
		if ( Sobi::Can( 'entry', 'manage', '*' ) ) {
			$en[ 'approve_url' ] = Sobi::Url( [ 'task' => ( $entry->get( 'approved' ) ? 'entry.unapprove' : 'entry.approve' ), 'sid' => $sid ] );
		}
		else {
			if ( isset( $en[ 'approve_url' ] ) ) {
				unset( $en[ 'approve_url' ] );
			}
		}

		/* Delete entry URL: if the user may delete the entry or the user owns the entry and may delete own entries */
		if ( Sobi::Can( 'entry', 'delete', '*' )
			|| ( $isOwner && Sobi::Can( 'entry', 'delete', 'own' ) )
		) {
			$en[ 'delete_url' ] = Sobi::Url( [ 'task' => 'entry.delete', 'sid' => $sid ] );
		}
		else {
			if ( isset( $en[ 'delete_url' ] ) ) {
				unset( $en[ 'delete_url' ] );
			}
		}

		/* Un-publish/publish entry URL: if the user may un-publish/publish the entry or the user owns the entry and may un-publish/publish own entries */
		if ( Sobi::Can( 'entry', 'publish', '*' )
			|| ( $isOwner && Sobi::Can( 'entry', 'publish', 'own' ) )
		) {
			$en[ 'publish_url' ] = Sobi::Url( [ 'task' => ( $entry->get( 'state' ) ? 'entry.unpublish' : 'entry.publish' ), 'sid' => $sid ] );
		}
		else {
			if ( isset( $en[ 'publish_url' ] ) ) {
				unset( $en[ 'publish_url' ] );
			}
		}

		return $en;
	}
}
