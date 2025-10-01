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
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created Thu, Aug 9, 2012 by Radek Suski
 * @modified 23 February 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadView( 'interface' );

use Sobi\C;
use Sobi\FileSystem\FileSystem;
use Sobi\Input\Input;
use Sobi\Lib\Factory;
use Sobi\Utils\StringUtils;
use Sobi\Error\Exception;
use Sobi\Utils\Type;

/**
 * Class SPAdmView
 */
class SPAdmView extends SPObject implements SPView
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
	protected $_template = null;
	/**
	 * @var string
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
	 * @var array
	 */
	protected $_output = [];
	/**
	 * @var bool
	 */
	protected $_native = false;
	/**
	 * @var DOMDocument
	 */
	protected $_xml = false;

	/**
	 * @var bool|null
	 */
	protected $_absolutePath = null;

	/**
	 * SPAdmView constructor.
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function __construct()
	{
		SPLoader::loadClass( 'mlo.input' );
		SPFactory::header()->addJsFile( 'tooltips' );
		Sobi::Trigger( 'Create', $this->name(), [ &$this ] );
	}

	/**
	 * @param $var
	 * @param $label
	 *
	 * @return $this
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
	 * @return $this
	 */
	public function & addHidden( $var, $label )
	{
		$this->_hidden[ $label ] = $var;
		$this->_attr[ 'request' ][ $label ] = $var;

		return $this;
	}

	/**
	 * @param string $path
	 * @param bool $absolute
	 *
	 * @throws ReflectionException
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function loadDefinition( string $path, bool $absolute = false )
	{
		$this->_absolutePath = $path;
		$path = SPLoader::translatePath( $path, ( $absolute ? 'absolute' : 'adm' ), true, 'xml', false );
		$this->_xml = new DOMDocument( Sobi::Cfg( 'xml.version', '1.0' ), Sobi::Cfg( 'xml.encoding', 'UTF-8' ) );
		$this->_xml->load( $path );
		Sobi::Trigger( 'AfterLoadDefinition', $this->name(), [ &$this, &$this->_xml ] );
		$this->parseDefinition( $this->_xml->getElementsByTagName( 'definition' ) );
	}

	/**
	 * @param $type
	 * @param $template
	 * @param null $absolutePath
	 *
	 * @return $this
	 * @throws ReflectionException
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function & determineTemplate( $type, $template, $absolutePath = null )
	{
		$acl = [
			'config'   => Sobi::Can( 'cms.admin' ) || Sobi::Can( 'cms.options' ), /* configuration & acl = all */
			'options'  => Sobi::Can( 'cms.admin' ) || Sobi::Can( 'cms.options' ),
			'admin'    => Sobi::Can( 'cms.admin' ),
			'apps'     => Sobi::Can( 'cms.admin' ) || Sobi::Can( 'cms.apps' ),
			'section'  => [
				'configure' => Sobi::Can( 'section.configure' ),
			],
			'category' => [
				'add'     => Sobi::Can( 'category.add' ),
				'edit'    => Sobi::Can( 'category.edit' ),
				'delete'  => Sobi::Can( 'category.delete' ),
				'visible' => Sobi::Can( 'category.delete' ) || Sobi::Can( 'category.add' ),
			],
			'entry'    => [
				'add'     => Sobi::Can( 'entry.add' ),
				'edit'    => Sobi::Can( 'entry.edit' ),
				'delete'  => Sobi::Can( 'entry.delete' ),
				'approve' => Sobi::Can( 'entry.approve' ),
				'publish' => Sobi::Can( 'entry.publish' ),
				'visible' => Sobi::Can( 'entry.delete' ) || Sobi::Can( 'entry.add' ),
			],
		];

		if ( SPLoader::translatePath( "$type.$template", 'adm', true, 'xml' ) || $absolutePath ) {
			$secs = [];
			try {
				$secs = Factory::Db()
					->select( 'id', 'spdb_object', [ 'oType' => 'section' ], 'id' )
					->loadResultArray();
			}
			catch ( Exception $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 500, __LINE__, __FILE__ );
			}
			foreach ( $secs as $section ) {
				$acl[ 'section' ][ $section ][ 'configure' ] = Sobi::Can( 'section', 'configure', 'any', $section );
				$acl[ 'section' ][ $section ][ 'access' ] = Sobi::Can( 'section', 'access', 'any', $section );
			}
			$this->assign( $acl, 'acl' );

			$sectionscopy = $this->sections( $secs, true );
			$this->assign( $sectionscopy, 'sections-copy' );

			$sections = $this->sections( $secs );
			$this->assign( $sections, 'sections-list' );

			$nid = Sobi::Section( 'nid' );
			$nid = $nid ? '/' . $nid : C::ES;
			$groups = Sobi::My( 'groups' );
			$disableOverrides = null;
			if ( is_array( $groups ) ) {
				$disableOverrides = array_intersect( $groups, Sobi::Cfg( 'templates.disable-overrides', [] ) );
			}
			/** Case we have also override  */
			if ( !$disableOverrides && SPLoader::translatePath( "$type$nid/$template", 'adm', true, 'xml' ) ) {
				$this->loadDefinition( "$type$nid/$template" );
			}
			else {
				$this->loadDefinition( ( $absolutePath ? "$absolutePath.$template" : "$type.$template" ), (bool) $absolutePath );
			}
			if ( Sobi::Section() && SPLoader::translatePath( "$type$nid/$template", 'adm' ) ) {
				$this->setTemplate( "$type$nid.$template" );
			}
			else {
				$this->setTemplate( 'default' );
			}
		}

		return $this;
	}

	/**
	 * @param false $copy
	 *
	 * @return array
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function sections( $sections = [], $copy = false )
	{
		$subMenu = [];

		if ( !count( $sections ) ) {
			try {
				$sections = Factory::Db()
					->select( 'id', 'spdb_object', [ 'oType' => 'section' ], 'id' )
					->loadResultArray();
			}
			catch ( Exception $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 500, __LINE__, __FILE__ );
			}
		}

		$sectionLength = 30;
		if ( count( $sections ) ) {
			$sections = SPLang::translateObject( $sections, 'name' );
			foreach ( $sections as $section ) {
				if ( Sobi::Can( 'section', 'access', '*', $section[ 'id' ] )
					|| Sobi::Can( 'section', 'configure', '*', $section[ 'id' ] )
				) {
					if ( $copy ) {
						$subMenu[] = [
							'type'    => '',
							'task'    => 'field.copyto.' . $section[ 'id' ],
							'url'     => [ 'sid' => $section[ 'id' ] ],
							'label'   => StringUtils::Clean( strlen( $section[ 'value' ] ) < $sectionLength ? $section[ 'value' ] : substr( $section[ 'value' ], 0, $sectionLength - 3 ) . ' ...' ),
							'icon'    => '',
							'element' => 'button',
						];
					}
					else {
						$subMenu[] = [
							'type'    => 'url',
							'task'    => '',
							'url'     => [ 'sid' => $section[ 'id' ] ],
							'label'   => StringUtils::Clean( strlen( $section[ 'value' ] ) < $sectionLength ? $section[ 'value' ] : substr( $section[ 'value' ], 0, $sectionLength - 3 ) . ' ...' ),
							'icon'    => '',
							'element' => 'button',
						];
					}
				}
			}
		}

		return $subMenu;
	}

	/**
	 * @return array
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	public function languages()
	{
		$subMenu = [];
		if ( Sobi::Cfg( 'lang.multimode', false ) ) {
			$availableLanguages = SPLang::availableLanguages();
			$sectionLength = 30;
			if ( count( $availableLanguages ) ) {
				$sid = Input::Sid();
				if ( !$sid ) {
					$sid = Sobi::Section();
				}
				$task = Input::Task();
				$url = [ 'sid' => $sid, 'task' => $task, 'sp-language' => C::ES ];
				if ( $task == 'field.edit' ) {
					$url = [ 'sid' => $sid, 'task' => $task, 'fid' => Input::Int( 'fid' ), 'sp-language' => C::ES ];
				}
				foreach ( $availableLanguages as $language ) {
					$url[ 'sp-language' ] = $language[ 'tag' ];
					$subMenu[] = [
						'type'     => 'url',
						'task'     => C::ES,
						'url'      => $url,
						'label'    => strlen( $language[ 'nativeName' ] ) < $sectionLength ? $language[ 'nativeName' ] : substr( $language[ 'nativeName' ], 0, $sectionLength - 3 ) . ' ...',
						'icon'     => 's',
						'element'  => 'button',
						'selected' => $language[ 'tag' ] == Sobi::Lang(),
					];
				}
			}
		}

		return $subMenu;
	}

	/**
	 * @param \DOMNodeList $xml
	 *
	 * @throws \ReflectionException
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function parseDefinition( DOMNodeList $xml )
	{
		Sobi::Trigger( 'beforeParseDefinition', $this->name(), [ &$this, &$xml ] );
		/** @var DOMNode $node */
		foreach ( $xml as $node ) {
			if ( strstr( $node->nodeName, '#' ) ) {
				continue;
			}
			if ( !$this->xmlCondition( $node ) ) {
				continue;
			}
			switch ( $node->nodeName ) {
				case 'header':
					$this->xmlHeader( $node->childNodes );
					break;
				case 'config':
					$this->xmlConfig( $node->childNodes );
					break;
				case 'toolbar':
					$this->xmlToolbar( $node );
					break;
				case 'body':
					if ( $node->attributes->getNamedItem( 'disable-menu' ) && $node->attributes->getNamedItem( 'disable-menu' )->nodeValue == 'true' ) {
						Input::Set( 'hidemainmenu', 1 );
					}
					$this->xmlBody( $node->childNodes, $this->_output[ 'data' ] );
					break;
				case 'definition':
					$this->parseDefinition( $node->childNodes );
					break;
			}
		}
		Sobi::Trigger( 'afterParseDefinition', $this->name(), [ &$this ] );
	}

	/**
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function getData()
	{
		Sobi::Trigger( 'beforeReturnData', $this->name(), [ &$this->_output ] );

		return $this->_output;
	}

	/**
	 * @return string|null
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function toolbar()
	{
		return SPFactory::AdmToolbar()->render();
	}

	/**
	 * @return \SPDBObject|null
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function & getParser()
	{
		static $parser = null;
		if ( !$parser ) {
			$parser = SPFactory::Instance( 'views.adm.parser' );
		}

		return $parser;
	}

	/**
	 * @param $xml
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function xmlToolbar( $xml )
	{
		$title = $xml
			->attributes
			->getNamedItem( 'title' );
		if ( $title ) {
			$title = $title->nodeValue;
		}
		$subtitle = $xml
			->attributes
			->getNamedItem( 'subtitle' );
		if ( $subtitle ) {
			$subtitle = $subtitle->nodeValue;
		}
		$icon = $xml
			->attributes
			->getNamedItem( 'icon' );
		$icon = $icon ? $icon->nodeValue : C::ES;

		$class = $xml
			->attributes
			->getNamedItem( 'class' );
		$class = $class ? $class->nodeValue : C::ES;

		/* set the SobiPro toolbar title and the browser title */
		if ( $title ) {
			$title = $this->parseTitleValue( $title );
			$subtitle = $subtitle ? $this->parseTitleValue( $subtitle ) : C::ES;
			SPFactory::AdmToolbar()->setToolbarTitle( [ 'title'    => $title,
			                                            'subtitle' => $subtitle,
			                                            'icon'     => $icon,
			                                            'class'    => $class ] );
			SPFactory::mainframe()->setBrowserTitle( $title );
		}

		$buttons = [];
		foreach ( $xml->childNodes as $node ) {
			if ( strstr( $node->nodeName, '#' ) ) {
				continue;
			}
			if ( !$this->xmlCondition( $node ) ) {
				continue;
			}
			/** @var DOMNode $node */
			switch ( $node->nodeName ) {
				case 'button':
					$buttons[] = $this->xmlButton( $node );
					break;
				case 'divider':
					$buttons[] = [ 'element' => 'divider' ];
					break;
				case 'group':
					$group = [ 'element' => 'group', 'buttons' => [] ];
					foreach ( $node->attributes as $attr ) {
						if ( $attr->nodeName == 'label' ) {
							$group[ $attr->nodeName ] = Sobi::Txt( $attr->nodeValue );
						}
						else {
							$group[ $attr->nodeName ] = $attr->nodeValue;
						}
					}
					foreach ( $node->childNodes as $bt ) {
						if ( strstr( $bt->nodeName, '#' ) ) {
							continue;
						}
						$group[ 'buttons' ][] = $this->xmlButton( $bt );
					}
					$buttons[] = $group;
					break;
				case 'buttons':
					$group = [ 'element' => 'buttons', 'buttons' => [], 'label' => $node->attributes->getNamedItem( 'label' ) ? Sobi::Txt( $node->attributes->getNamedItem( 'label' )->nodeValue ) : C::ES ];
					foreach ( $node->attributes as $attr ) {
						if ( $attr->nodeName == 'label' ) {
							continue;
						}
						$group[ $attr->nodeName ] = $attr->nodeValue;
					}
					/** it has to have child nodes or these children are defined in value  */
					if ( $node->hasChildNodes() ) {
						foreach ( $node->childNodes as $bt ) {
							if ( strstr( $bt->nodeName, '#' ) ) {
								continue;
							}
							if ( !$this->xmlCondition( $bt ) ) {
								continue;
							}
							if ( $bt->nodeName == 'nav-header' || $bt->nodeName == 'dropdown-header' ) {
								$group[ 'buttons' ][] = [ 'element' => 'dropdown-header', 'label' => Sobi::Txt( $bt->attributes->getNamedItem( 'label' )->nodeValue ) ];
							}
							else {
								$group[ 'buttons' ][] = $this->xmlButton( $bt );
							}
						}
					}
					else {
						$group[ 'buttons' ] = $this->get( $node->attributes->getNamedItem( 'buttons' )->nodeValue );
					}
					if ( isset( $group[ 'buttons' ] ) && count( $group[ 'buttons' ] ) ) {
						$buttons[] = $group;
					}
					break;
			}
		}
		Sobi::Trigger( 'beforeRenderToolbar', $this->name(), [ &$buttons ] );
		SPFactory::AdmToolbar()->addButtons( $buttons );
	}

	/**
	 * @param $xml
	 * @param null $prefix
	 * @param null $subject
	 * @param int $i
	 *
	 * @return bool
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function xmlCondition( $xml, $prefix = C::ES, $subject = null, $i = -1 )
	{
		if ( $xml->hasAttributes() && $xml->attributes->getNamedItem( $prefix . 'condition' ) && $xml->attributes->getNamedItem( $prefix . 'condition' )->nodeValue ) {
			$condition = $xml->attributes->getNamedItem( $prefix . 'condition' )->nodeValue;

			return $this->evaluateCondition( $condition, $subject, $i, false );
		}
		if ( $xml->hasAttributes() && $xml->attributes->getNamedItem( $prefix . 'invert-condition' ) && $xml->attributes->getNamedItem( $prefix . 'invert-condition' )->nodeValue ) {
			$condition = $xml->attributes->getNamedItem( $prefix . 'invert-condition' )->nodeValue;

			return $this->evaluateCondition( $condition, $subject, $i, true );
		}

		return true;
	}

	/**
	 * @param $condition
	 * @param $subject
	 * @param int $i
	 * @param bool $invert
	 *
	 * @return bool
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function evaluateCondition( $condition, $subject, int $i, bool $invert ): bool
	{
		$value = null;
		$retval = true;
		if ( $condition ) {
			$and = false;
			if ( strstr( $condition, ' and ' ) ) {
				$and = true;    /* and operator */
				$condition = explode( ' and ', $condition );
			}
			else {
				if ( strstr( $condition, ' or ' ) ) {
					$and = false;   /* or operator */
					$condition = explode( ' or ', $condition );
				}
			}
			if ( is_array( $condition ) ) {
				foreach ( $condition as $cond ) {   /* evaluate multiple conditions from and/or */

					$val = $this->getConditionValue( $subject ? $subject . '.' . $cond : $cond, $i );

					if ( $value === null ) {
						$value = (boolean) $val;
					}
					else {
						if ( $and ) {
							$value = (boolean) $value && (boolean) $val;
						}
						else {
							$value = (boolean) $value || (boolean) $val;
							if ( $value ) {
								break;
							}
						}
					}
				}
			}
			else {
				$value = $this->getConditionValue( $subject ? $subject . '.' . $condition : $condition, $i );
			}

			$retval = $invert ? !( $value ) : $value;
		}

		return $retval;
	}

	/**
	 * @param $condition
	 * @param $i
	 *
	 * @return bool|int
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function getConditionValue( $condition, $i )
	{
		// allow to have a global condition within a loop like condition="/ordering = position.asc"
		if ( strstr( $condition, './' ) ) {
			$i = -1;
			$condition = preg_replace( '/.*\.\//', C::ES, $condition );
		}

		if ( strstr( $condition, '=' ) ) {
			$condition = explode( '=', $condition );
			$equal = trim( $condition[ 1 ] );
			$condition = trim( $condition[ 0 ] );
			$value = $this->get( $condition, $i );
			$value = $value == $equal;
		}
		else {
			if ( strstr( $condition, '.contains(' ) ) {
				$condition = explode( '.contains', $condition );
				$pattern = trim( str_replace( [ '(', ')' ], C::ES, $condition[ 1 ] ) );
				$condition = trim( $condition[ 0 ] );
				$value = $this->get( $condition, $i );
				$value = !( ( strpos( $value, $pattern ) === false ) );
			}
			else {
				$value = $this->get( $condition, $i );
			}
		}
		$value = $value === null ? false : $value;

		return is_array( $value ) ? (boolean) count( $value ) : $value;
	}

	/**
	 * @param $xml
	 * @param array $attributes
	 *
	 * @return array
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function xmlButton( $xml, array $attributes = [] ): array
	{
		$button = [
			'type'    => null,
			'task'    => null,
			'label'   => null,
			'icon'    => null,
			'target'  => null,
			'buttons' => null,
			'element' => 'button',
		];
		if ( $xml->attributes->length ) {
			/** @var DOMElement $attr */
			foreach ( $xml->attributes as $attr ) {
				if ( $attr->nodeName == 'label' ) {
					$button[ $attr->nodeName ] = Sobi::Txt( $attr->nodeValue );
				}
				else {
					$button[ $attr->nodeName ] = $attr->nodeValue;
				}
			}
			if ( $xml->hasChildNodes() ) {
				foreach ( $xml->childNodes as $node ) {
					if ( strstr( $node->nodeName, '#' ) ) {
						continue;
					}
					if ( !( $this->xmlCondition( $node ) ) ) {
						continue;
					}
					$button[ 'buttons' ][] = $this->xmlButton( $node, $attributes );
				}
			}
		}
		if ( count( $attributes ) ) {
			$button = array_merge( $button, $attributes );
		}

		return $button;
	}

	/**
	 * @param $key
	 * @param int $i
	 *
	 * @return string|string[]
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function replaceValue( $key, $i = -1 )
	{
		preg_match( '/var\:\[([a-zA-Z0-9\.\_\-]*)\]/', $key, $matches );
		// for keys like: 'var:[section]'
		switch ( $matches[ 1 ] ) {
			case 'section':
				$value = Sobi::Section( true );
				break;
			case 'language':
				$value = Sobi::Lang();
				break;
			case 'template':
				$value = $this->get( 'template_name' );
				break;
			case 'path':
				$value = $this->get( 'file_path' );
				break;
			case 'field':
				$value = $this->get( 'field.name' );
				break;
			case 'field_nid':
				$value = $this->get( 'field.nid' );
				break;
			case 'field_type':
				$value = $this->get( 'field.fieldType' );
				break;
			case 'section_name':
				$value = $this->get( 'section.name' );
				break;
			case 'category_name':
				$value = Input::Sid() == Sobi::Section() ? $this->get( 'section.name' ) : $this->get( 'category.name' );
				break;
			case 'maxUpload':
				$value = $this->get( 'maxUpload' );
				break;
			case 'variableValue':
				$value = $this->get( 'variableValue' );
				break;
			default:
				$value = $this->get( $matches[ 1 ], $i );
		}
		if ( is_string( $value ) || is_numeric( $value ) ) {
			$key = str_replace( $matches[ 0 ], $value, $key );
		}

		return $key;
	}

	/**
	 * @param $key
	 * @param int $i
	 *
	 * @return mixed
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function parseValue( $key, $i = -1 )
	{
		if ( strstr( $key, 'var:[' ) ) {
			$key = $this->replaceValue( $key, $i );
		}
		else {
			$key = Sobi::Txt( $key );
		}
		if ( strstr( $key, 'var:[' ) ) {
			$key = $this->replaceValue( $key, $i );
		}

		return $key;
	}

	/**
	 * @param $key
	 *
	 * @return mixed|string|string[]
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function parseTitleValue( $key )
	{
		if ( strstr( $key, 'var:[' ) ) {
			$key = $this->replaceValue( $key, -1 );
		}
		elseif ( strstr( $key, '{' ) ) {
			$key = (array) SPFactory::config()->structuralData( 'json://' . $key );
			$task = Input::Task();
			$key = $key[ $task ] ?? $key;
			if ( is_array( $key ) ) {
				if ( strstr( $task, 'field.add' ) ) {
					$key = $key[ 'field.add' ];
				}
				if ( strstr( $task, 'field.edit' ) ) {
					$key = $key[ 'field.edit' ];
				}
			}
			$key = $this->parseValue( Sobi::Txt( $key ) );
		}
		else {
			$key = Sobi::Txt( $key );
		}
		if ( strstr( $key, 'var:[' ) ) {
			$key = $this->replaceValue( $key, -1 );
		}

		return $key;
	}

	/**
	 * @param $xml
	 * @param $output
	 *
	 * @throws ReflectionException
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function xmlBody( $xml, &$output )
	{
		foreach ( $xml as $node ) {
			if ( strstr( $node->nodeName, '#' ) ) {
				continue;
			}
			if ( !( $this->xmlCondition( $node ) ) ) {
				continue;
			}
			$element = [
				'label'      => null,
				'type'       => $node->nodeName,
				'content'    => null,
				'attributes' => null,
			];
			$element = $this->xmlAttributes( $node, $element );

			/** @var DOMNode $node */
			switch ( $node->nodeName ) {
				case 'tab':
				case 'fieldset':
					$element[ 'label' ] = $node->attributes->getNamedItem( 'label' ) ? Sobi::Txt( $node->attributes->getNamedItem( 'label' )->nodeValue ) : null;
					$element[ 'id' ] = $node->attributes->getNamedItem( 'label' ) ? StringUtils::Nid( $node->attributes->getNamedItem( 'label' )->nodeValue ) : null;
					if ( $node->hasChildNodes() ) {
						$this->xmlBody( $node->childNodes, $element[ 'content' ] );
					}
					else {
						$element[ 'content' ] = $node->nodeValue;
					}
					$element[ 'attributes' ][ 'labelwidth' ] = $node->attributes->getNamedItem( 'labelwidth' ) ? $this->get( $node->attributes->getNamedItem( 'labelwidth' )->nodeValue ) : null;

					break;
				case 'url':
					$element[ 'label' ] = $node->attributes->getNamedItem( 'label' ) ? Sobi::Txt( $node->attributes->getNamedItem( 'label' )->nodeValue ) : C::ES;
					$element[ 'link' ] = $this->xmlUrl( $node );
					$element[ 'attributes' ][ 'href' ] = $element[ 'link' ];
					if ( $node->attributes->getNamedItem( 'value' ) ) {
						$content = $this->get( $node->attributes->getNamedItem( 'value' )->nodeValue );
						if ( !$content ) {
							$content = $node->attributes->getNamedItem( 'value' )->nodeValue;
						}
						$element[ 'content' ] = $content;
					}
					if ( !$element[ 'content' ] ) {
						$element[ 'content' ] = $element[ 'label' ];
					}
					break;
				case 'text':
					$element[ 'content' ] = $this->xmlText( $node );
					break;
				case 'legend':
					if ( $node->hasChildNodes() ) {
						$objects = [];
						foreach ( $node->childNodes as $param ) {
							if ( $param->attributes
								&& $param->attributes->getNamedItem( 'value' )  /* required */
								&& $this->xmlCondition( $param )
							) {
								$value = $param->attributes->getNamedItem( 'parse' ) ?
									Sobi::Txt( $param->attributes->getNamedItem( 'value' )->nodeValue ) :
									$param->attributes->getNamedItem( 'value' )->nodeValue;
								$icon = $param->attributes->getNamedItem( 'icon' ) ? $param->attributes->getNamedItem( 'icon' )->nodeValue : '';

								$objects[] = [
									'value' => $value,
									'icon'  => $icon,
								];
							}
						}
						if ( count( $objects ) ) {
							$element[ 'content' ] = $objects;
						}
					}
					break;
				case 'field':
					$this->xmlField( $node, $element );
					break;
				case 'loop':
					$this->xmlLoop( $node, $element );
					break;
				case 'popover':
				case 'tooltip':
					$this->xmlToolTip( $node, $element );
					break;
				case 'pagination':
					$this->xmlPagination( $node, $element );
					break;
				case 'message':
					if ( $node->attributes->getNamedItem( 'parse' ) && $node->attributes->getNamedItem( 'parse' )->nodeValue ) {
						$msg = $node->attributes->getNamedItem( 'parse' )->nodeValue;
						$element[ 'attributes' ][ 'label' ] = $this->get( $msg . '.label' );
						$element[ 'attributes' ][ 'type' ] = $this->get( $msg . '.type' );
					}
					break;
				case 'file':
					$this->xmlFile( $node, $element );
					break;
				case 'include':
					$path = substr( $this->_absolutePath, 0, strrpos( $this->_absolutePath, '/' ) + 1 );
					$xml = new DOMDocument( Sobi::Cfg( 'xml.version', '1.0' ), Sobi::Cfg( 'xml.encoding', 'UTF-8' ) );
					$path = SPLoader::translatePath( $path . $node->attributes->getNamedItem( 'filename' )->nodeValue, 'absolute', true, 'xml', false );
					if ( $path ) {
						$xml->load( $path );
						$this->xmlBody( $xml->getElementsByTagName( $node->attributes->getNamedItem( 'element' )->nodeValue ), $element[ 'content' ] );
					}
					$element = $element[ 'content' ] ?
						( ( $element[ 'content' ][ 0 ][ 'content' ] ) ? $element[ 'content' ][ 0 ] : null ) : null;   /* ignore the include element */
					break;
				case 'call':
					$element[ 'type' ] = 'text';
					$element[ 'content' ] = $this->xmlCall( $node );
					break;
				case 'menu':
					$element[ 'content' ] = $this->menu( true );
					break;
				case 'toolbar':
					$element[ 'content' ] = $this->toolbar();
					break;
				default:
					if ( $node->hasChildNodes() ) {
						$this->xmlBody( $node->childNodes, $element[ 'content' ] );
					}
					else {
						if ( !( strstr( $node->nodeName, '#' ) ) ) {
							$element[ 'content' ] = $node->nodeValue;
						}
					}
				/** No break here */
				case 'cells':
					if ( $node->attributes->getNamedItem( 'value' ) ) {
						$customCells = $this->get( $node->attributes->getNamedItem( 'value' )->nodeValue );
						if ( is_array( $customCells ) && count( $customCells ) ) {
							foreach ( $customCells as $cell ) {
								$element[ 'content' ][] = [
									'label'      => $cell[ 'label' ] ?? null,
									'type'       => 'cell',
									'content'    => $cell[ 'content' ],
									'attributes' => $element[ 'attributes' ],
								];
							}
						}
					}
					break;

			}
			if ( $element ) {
				$output[] = $element;
			}
		}
	}

	/**
	 * @param $node
	 * @param $element
	 *
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function xmlFile( $node, &$element )
	{
		$type = $node->attributes->getNamedItem( 'type' )->nodeValue;
		$translatable = $node->attributes->getNamedItem( 'translatable' ) && $node->attributes->getNamedItem( 'translatable' )->nodeValue;
		$admin = $node->attributes->getNamedItem( 'start-path' ) ? $node->attributes->getNamedItem( 'start-path' )->nodeValue : 'front';
		$filename = $node->attributes->getNamedItem( 'filename' )->nodeValue;
		$path = explode( '.', $filename );
		$filename = array_pop( $path );
		$dirPath = implode( '.', $path );
		$element[ 'type' ] = 'text';
		if ( $translatable ) {
			$file = SPLoader::path( $dirPath . '.' . Sobi::Lang() . '.' . $filename, $admin, true, $type );
			if ( !( $file ) ) {
				$file = SPLoader::path( $dirPath . '.en-GB.' . $filename, $admin, true, $type );
			}
			if ( $file ) {
				$element[ 'content' ] = FileSystem::Read( $file );
			}
			else {
				$element[ 'content' ] = SPLoader::path( $dirPath . '.' . Sobi::Lang() . '.' . $filename, $admin, false, $type );
			}
		}
	}

	/**
	 * @param $node
	 * @param $element
	 * @param null $subject
	 * @param int $index
	 *
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function xmlToolTip( $node, &$element, $subject = null, $index = -1 )
	{
		foreach ( $node->attributes as $attribute ) {
			$element[ $attribute->nodeName ] = Sobi::Txt( $attribute->nodeValue );
		}
		foreach ( $node->childNodes as $param ) {
			if ( strstr( $param->nodeName, '#' ) ) {
				continue;
			}
			$element[ $param->attributes->getNamedItem( 'name' )->nodeValue ] = $this->xmlParams( $param, $subject, $index );
		}
		$unsets = [ 'type', 'title', 'content' ];
		foreach ( $unsets as $unset ) {
			if ( isset( $element[ 'attributes' ][ $unset ] ) ) {
				unset( $element[ 'attributes' ][ $unset ] );
			}
		}
	}

	/**
	 * @param $node
	 * @param $element
	 *
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function xmlPagination( $node, &$element )
	{
		$args = [];
		/** @var DOMElement $attribute */
		foreach ( $node->attributes as $attribute ) {
			$args[ $attribute->nodeName ] = $attribute->nodeValue;
		}
		foreach ( $node->childNodes as $param ) {
			if ( strstr( $param->nodeName, '#' ) ) {
				continue;
			}
			$args[ $param->attributes->getNamedItem( 'name' )->nodeValue ] = $this->xmlParams( $param );
		}
		/** @var SPPagination $pagination */
		$pagination = SPFactory::Instance( 'views.adm.pagination' );
		foreach ( $args as $var => $val ) {
			$pagination->set( $var, $val );
		}
		$element[ 'content' ] = $pagination->display( true );
	}

	/**
	 * @param $node
	 * @param int $subject
	 * @param int $i
	 *
	 * @return array|mixed|null
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function xmlText( $node, $subject = 0, $i = -1 )
	{
		$value = null;
		if ( $node->attributes->getNamedItem( 'value' ) ) {
			if ( $node->attributes->getNamedItem( 'parse' ) && $node->attributes->getNamedItem( 'parse' )->nodeValue == 'true' ) {
				if ( strlen( $subject ) && $subject ) {
					$value = $this->get( $subject . '.' . $node->attributes->getNamedItem( 'value' )->nodeValue, $i );
				}
				else {
					$value = $this->get( $node->attributes->getNamedItem( 'value' )->nodeValue, $i );
				}
			}
			else {
				$args = [ $node->attributes->getNamedItem( 'value' )->nodeValue ];
				if ( $node->hasChildNodes() ) {
					foreach ( $node->childNodes as $param ) {
						if ( strstr( $param->nodeName, '#' ) ) {
							continue;
						}
						if ( $param->attributes->getNamedItem( 'value' ) ) {
							if ( $param->attributes->getNamedItem( 'parse' ) && $param->attributes->getNamedItem( 'parse' )->nodeValue == 'true' ) {
								$args[] = $this->get( $param->attributes->getNamedItem( 'value' )->nodeValue );
							}
							else {
								$args[] = $param->attributes->getNamedItem( 'value' )->nodeValue;
							}
						}
						else {
							$args[] = $param->nodeValue;
						}
					}
				}
				$value = call_user_func_array( [ 'SPLang', '_' ], $args );
			}
		}

		return $value;
	}

	/**
	 * @param $node
	 * @param $element
	 *
	 * @throws ReflectionException
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function xmlLoop( $node, &$element )
	{
		$subject = $node->attributes->getNamedItem( 'subject' )->nodeValue;
		static $count = 0;
		if ( $subject == 'entry.fields' || $subject == 'category.fields' ) {
			$this->xmlFields( $element );

			return;
		}
		else {
			if ( $subject == 'config.keys' ) {
				$this->xmlKeys( $element );

				return;
			}
			else {
				if ( strstr( $subject, '.' ) ) {
					$tempSubject = $this->get( $subject );
					$this->assign( $tempSubject, 'temporary' . ++$count );
					$subject = 'temporary' . $count;
				}
			}
		}
		$objectsCount = $this->count( $subject );
		$objects = [];
		if ( $node->attributes->getNamedItem( 'type' ) && $node->attributes->getNamedItem( 'type' )->nodeValue == 'fields' ) {
			$fields = $this->get( $subject );
			foreach ( $fields as $field ) {
				if ( method_exists( 'SPHtml_Input', '_' . $field[ 'type' ] ) ) {
					$method = new ReflectionMethod( 'SPHtml_Input', '_' . $field[ 'type' ] );
					$methodArgs = [];
					$methodParams = $method->getParameters();
					foreach ( $methodParams as $param ) {
						if ( isset( $field[ $param->name ] ) ) {
							$methodArgs[] = $field[ $param->name ];
						}
						else {
							if ( $param->isDefaultValueAvailable() ) {
								$methodArgs[] = $param->getDefaultValue();
							}
							else {
								$methodArgs[] = null;
							}
						}
					}
					$objects[] = [
						'label'   => $field[ 'label' ],
						'type'    => 'field',
						'content' => call_user_func_array( [ 'SPHtml_Input', $field[ 'type' ] ], $methodArgs ),
						'args'    => [ 'type' => $field[ 'type' ] ],
						'adds'    => [
							'after'  => [ $field[ 'required' ] ? Sobi::Txt( 'EX.SOAP_RESP_REQ' ) : Sobi::Txt( 'EX.SOAP_RESP_OPT' ) ],
							'before' => null,
						],
					];
				}
			}
		}
		else {
			for ( $i = 0; $i < $objectsCount; $i++ ) {
				$row = [];
				/** @var DOMNode $cell */
				foreach ( $node->childNodes as $cell ) {
					if ( strstr( $cell->nodeName, '#' ) ) {
						continue;
					}
					$this->xmlCell( $cell, $subject, $i, $row );
				}
				$attr = [];
				if ( $node->hasAttributes() ) {
					/** @var DOMElement $attribute */
					foreach ( $node->attributes as $attribute ) {
						$attr[ $attribute->nodeName ] = $attribute->nodeValue;
					}
				}
				$objects[] = [
					'label'      => null,
					'type'       => 'loop-row',
					'content'    => $row,
					'attributes' => $attr,
				];
			}
		}
		$element[ 'content' ] = $objects;
	}

	/**
	 * Specifies the data get from an entry.
	 *
	 * @param $element
	 *
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function xmlFields( &$element )
	{
		$fields = $this->get( 'fields' );
		$objects = [];
		foreach ( $fields as $i => $field ) {
			$output = $field->field( true );
			if ( !( $output ) ) {
				continue;
			}
			$adds = null;
			$width = $field->get( 'bsWidth' );
			if ( !$width ) {
				$width = 10;
			}
			$objects[ $i ] = [
				'label'            => $field->get( 'name' ),
				'type'             => 'field',
				'content'          => $output,
				'args'             => [ 'type'     => $field->get( 'type' ),
				                        'width'    => $width,
				                        'label'    => $field->get( 'showEditLabel' ),
				                        'required' => $field->get( 'required' ) ],
				'adds'             => [ 'before' => null, 'after' => $adds ],
				'help-text'        => $field->get( 'description' ),
				'help-position'    => $field->get( 'helpposition' ),
				'id'               => $field->get( 'nid' ),
				'revisions-change' => $field->get( 'revisionChange' ),
			];
		}
		$element[ 'content' ] = $objects;
	}

	/**
	 * @param $element
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function xmlKeys( &$element )
	{
		$sections = $this->get( 'keys' );
		$objects = $config_results = [];
		if ( is_array( $sections ) ) {
			if ( array_key_exists( 'config_results', $sections ) ) {
				$config_results = $sections[ 'config_results' ];
				unset( $sections[ 'config_results' ] );
			}
			foreach ( $sections as $label => $section ) {
				$count = 1;
				foreach ( $section as $name => $key ) {
					/* create the content for this section */

					$width = 9;
					$keylabel = $name;
					$params[ 'id' ] = StringUtils::Nid( $keylabel );
					$name = "cfgfile[$label][$name]";
					$type = 'text';
					if ( gettype( $key ) == 'boolean' ) {
						$type = 'boolean';
						/* add indicator for boolean value to name */
						$name = 'bool.' . $keylabel;
						$name = "cfgfile[$label][$name]";
						$output = SPHtml_Input::toggle( $name, $key, $params[ 'id' ], 'boolean' );
					}
					else {
						if ( strlen( $key ) > 110 ) {
							$type = 'textarea';
							$output = SPHtml_Input::textarea( $name, $key, false, C::ES, 150, $params );
						}
						else {
							if ( is_numeric( $key ) ) {
								$type = 'numtext';
								$width = 4;
							}
							$output = SPHtml_Input::text( $name, $key, $params );
						}
					}
					$objects[] = [
						'label'   => $keylabel, /* key label */
						'section' => $label, /* section label */
						'count'   => $count++,  /* keys counter in the section*/
						'type'    => 'key',
						'content' => $output,
						'args'    => [ 'type' => $type, 'width' => $width ],
						'adds'    => [ 'before' => null, 'after' => null ],
						'id'      => $params[ 'id' ],
					];
				}
			}
		}
		if ( count( $config_results ) ) {
			/* if there has an error occurred */
			if ( array_key_exists( 'error', $config_results ) && $config_results[ 'error' ] ) {
				$output = '<div class="px-4 py-5 my-5 text-center"><span class="fas fa-6x mb-4 fa-exclamation-triangle text-danger" aria-hidden="true"></span><h3 class="display-6">' . $sections . '</h3></div>';

				$objects[] = [ 'content'    => $output,
				               'type'       => 'field',
				               'attributes' => [ 'stand-alone' => 'true' ],
				];
			}
			/* no error but other messages to show ? */
			else {
				$msg = null;
				/* although not both messages may occur at the same time, we implement the possibility to show all */
				if ( array_key_exists( 'combined', $config_results ) && $config_results[ 'combined' ] ) {
					$msg = Sobi::Txt( 'GBN.CFG.CONFIG_MERGED_MSG' );
				}
				if ( array_key_exists( 'created', $config_results ) && $config_results[ 'created' ] ) {
					$msg = $msg ? $msg . '<br/>' : null;
					$msg .= Sobi::Txt( 'GBN.CFG.CONFIG_COPY_MSG' );
				}

				if ( $msg ) {
					$alert = [ 'content'    => '<div class="alert alert-outline-danger dk-config-alert-lg" role="alert">' . $msg . '</div>',
					           'type'       => 'field',
					           'attributes' => [ 'stand-alone' => 'true' ],
					];
					array_unshift( $objects, $alert );
				}
			}
		}

		$element[ 'content' ] = $objects;
	}

	/**
	 * @param $cell
	 * @param $subject
	 * @param $i
	 * @param $objects
	 *
	 * @throws ReflectionException
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function xmlCell( $cell, $subject, $i, &$objects )
	{
		if ( !$this->xmlCondition( $cell, C::ES, $subject, $i ) ) {
			return;
		}
		$element = [
			'label'      => null,
			'type'       => $cell->nodeName,
			'content'    => null,
			'attributes' => null,
		];
		@$type = $cell->attributes->getNamedItem( 'type' ) ? $cell->attributes->getNamedItem( 'type' )->nodeValue : null;
		$this->cellAttributes( $cell, $element, $subject, $i );
		if ( $cell->nodeName == 'cells' ) {
			$customCells = $this->get( $subject . '.' . $cell->attributes->getNamedItem( 'value' )->nodeValue, $i );
			if ( $customCells && count( $customCells ) ) {
				$a = $element[ 'attributes' ];
				$a[ 'type' ] = 'text';
				foreach ( $customCells as $customCell ) {
					$objects[] = [
						'label'      => null,
						'type'       => 'cell',
						'content'    => $customCell,
						'attributes' => $a,
					];
				}
			}
		}
		else {
			if ( $cell->nodeName == 'text' ) {
				$element[ 'content' ] = $this->xmlText( $cell, $subject, $i );
			}
			else {
				if ( $cell->nodeName == 'date' ) {
					$element[ 'type' ] = 'text';
				}
				else {
					if ( $cell->nodeName == 'field' ) {
						$this->xmlField( $cell, $element, $element[ 'content' ], true, $subject, $i );
					}
					else {
						if ( $cell->nodeName == 'call' ) {
							$element[ 'type' ] = 'text';
							$element[ 'content' ] = $this->xmlCall( $cell );
						}
					}
				}
			}
		}
		if ( $cell->hasChildNodes() ) {
			/** @var DOMNode $child */
			foreach ( $cell->childNodes as $child ) {
				if ( strstr( $child->nodeName, '#' ) ) {
					continue;
				}
				/** @var DOMNode $param */
				switch ( $child->nodeName ) {
					case 'url':
						if ( $child->attributes->getNamedItem( 'class' ) && $child->attributes->getNamedItem( 'class' )->nodeValue ) {
							$element[ 'attributes' ][ 'link-class' ] = $child->attributes->getNamedItem( 'class' )->nodeValue;
						}
						if ( $child->attributes->getNamedItem( 'role' ) && $child->attributes->getNamedItem( 'class' )->nodeValue ) {
							$element[ 'attributes' ][ 'link-role' ] = $child->attributes->getNamedItem( 'role' )->nodeValue;
						}
						if ( $child->attributes->getNamedItem( 'id' ) && $child->attributes->getNamedItem( 'id' )->nodeValue ) {
							$element[ 'attributes' ][ 'link-id' ] = $child->attributes->getNamedItem( 'id' )->nodeValue;
						}
						if ( $child->attributes->getNamedItem( 'icon' ) && $child->attributes->getNamedItem( 'icon' )->nodeValue ) {
							$element[ 'attributes' ][ 'icon' ] = $child->attributes->getNamedItem( 'icon' )->nodeValue;
						}
						if ( $child->attributes->getNamedItem( 'aria-after' ) && $child->attributes->getNamedItem( 'aria-after' )->nodeValue ) {
							$element[ 'attributes' ][ 'aria-after' ] = $child->attributes->getNamedItem( 'aria-after' )->nodeValue;
						}
						if ( $child->attributes->getNamedItem( 'aria-before' ) && $child->attributes->getNamedItem( 'aria-before' )->nodeValue ) {
							$element[ 'attributes' ][ 'aria-before' ] = $child->attributes->getNamedItem( 'aria-before' )->nodeValue;
						}
						$element[ 'link' ] = $this->xmlUrl( $child, $subject, $i );

						//$element[ 'sid' ] = $this->xmlSid( $child, $subject, $i );
						break;
					case 'popover':
					case 'tooltip':
						$this->xmlToolTip( $child, $element, $subject, $i );
						$element[ 'type' ] = $child->nodeName;
						break;
					case 'button':
						$attributes = [];
						foreach ( $child->attributes as $attribute ) {
							$attributes[ $attribute->nodeName ] = $this->parseValue( str_replace( 'var:[', 'var:[' . $subject . '.', $attribute->nodeValue ), $i );
						}
						$element[ 'content' ] = $this->xmlButton( $child, $attributes );
						break;
					case 'call':
						$element[ 'type' ] = 'text';
						$element[ 'content' ] = $this->xmlCall( $child, $subject, $i );
						break;
					case 'loop':
						$this->xmlLoop( $child, $element );
						break;
					default:
						$this->xmlCell( $child, $subject, $i, $element[ 'childs' ] );
						break;
				}
			}
		}
		$objects[] = $element;
	}

	/**
	 * @param $cell
	 * @param $element
	 * @param $subject
	 * @param $i
	 *
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function cellAttributes( $cell, &$element, $subject, $i )
	{
		/** @var DOMElement $attribute */
		foreach ( $cell->attributes as $attribute ) {
			switch ( $attribute->nodeName ) {
				case 'label':
					$element[ 'label' ] = Sobi::Txt( $attribute->nodeValue );
					break;
				case 'rel':
					$element[ 'label' ] = $attribute->nodeValue;
					break;
				case 'box-label':
					$element[ 'label' ] = $this->get( $subject . '.' . $attribute->nodeValue, $i );
					break;
				case 'value':
					$element[ 'content' ] = $this->get( $subject . '.' . $attribute->nodeValue, $i );
					break;
				case 'id-prefix':
					$element[ 'attributes' ][ 'id' ] = $attribute->nodeValue . $element[ 'attributes' ][ 'id' ];
					break;
				case 'checked-out-by':
				case 'checked-out-time':
				case 'valid-since':
				case 'locked':
				case 'valid-until':
				case 'id':
					$element[ 'attributes' ][ $attribute->nodeName ] = $this->get( $subject . '.' . $attribute->nodeValue, $i );
					break;
				case 'checked':
					if ( $this->get( $subject . '.' . $attribute->nodeValue, $i ) ) {
						$element[ 'attributes' ][ 'checked' ] = true;
					}
					break;
				default:
					$element[ 'attributes' ][ $attribute->nodeName ] = $this->parseValue( str_replace( 'var:[', 'var:[' . $subject . '.', $attribute->nodeValue ), $i );
					break;
			}
		}
	}

	/**
	 * @param $node
	 * @param null $subject
	 * @param int $index
	 *
	 * @return array|string|null
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function xmlUrl( $node, $subject = C::ES, $index = -1 )
	{
		$url = [];
		foreach ( $node->childNodes as $param ) {
			if ( strstr( $param->nodeName, '#' ) ) {
				continue;
			}
			$url[ $param->attributes->getNamedItem( 'name' )->nodeValue ] = $this->xmlParams( $param, $subject, $index );
		}

		/* link-condition, perhaps with parameter */
		if ( $node->attributes->getNamedItem( 'link-condition' ) ) {
			$condition = $node->attributes->getNamedItem( 'link-condition' )->nodeValue;
			if ( strstr( $condition, 'param:[' ) ) {
				preg_match( '/param\:\[([a-zA-Z0-9\.\_\-]*)\]/', $condition, $matches );
				$condition = str_replace( $matches[ 0 ], $url[ $matches[ 1 ] ], $condition );
			}

			if ( !$this->evaluateCondition( $condition, $subject, -1, false ) ) {
				return null;
			}
		}
		if ( $node->attributes->getNamedItem( 'type' ) && $node->attributes->getNamedItem( 'type' )->nodeValue == 'intern' ) {
			$js = $node->attributes->getNamedItem( 'js' ) && $node->attributes->getNamedItem( 'js' )->nodeValue == 'true';
			$sef = $itemId = $node->attributes->getNamedItem( 'sef' ) && $node->attributes->getNamedItem( 'sef' )->nodeValue == 'true';
			$live = $node->attributes->getNamedItem( 'live' ) && $node->attributes->getNamedItem( 'live' )->nodeValue == 'true';
			$link = SPFactory::mainframe()->url( $url, $js, $sef, $live, $itemId );
		}
		else {
			$link = $node->attributes->getNamedItem( 'host' )->nodeValue;
			if ( $node->attributes->getNamedItem( 'hash' ) && $node->attributes->getNamedItem( 'hash' )->nodeValue ) {
				$prefix = null;
				if ( $node->attributes->getNamedItem( 'hash-prefix' ) && $node->attributes->getNamedItem( 'hash-prefix' )->nodeValue ) {
					$prefix = $node->attributes->getNamedItem( 'hash-prefix' )->nodeValue;
				}
				$link = '#' . $prefix . $this->get( $subject . '.' . $node->attributes->getNamedItem( 'hash' )->nodeValue, $index );
			}
			if ( !( strstr( $link, '://' ) ) && !( strstr( $link, '#' ) ) ) {
				if ( $subject ) {
					$link = $this->get( $subject . '.' . $link, $index );
				}
				else {
					$link = $this->get( $link, $index );
				}
			}
			if ( count( $url ) ) {
				$link .= http_build_query( $url );
			}
		}

		return $link;
	}

	/**
	 * @param $node
	 * @param null $subject
	 * @param int $index
	 *
	 * @return array|int|mixed
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function xmlSid( $node, $subject = null, $index = -1 )
	{
		$url = [];
		$sid = 0;
		foreach ( $node->childNodes as $param ) {
			if ( strstr( $param->nodeName, '#' ) ) {
				continue;
			}
			$url[ $param->attributes->getNamedItem( 'name' )->nodeValue ] = $this->xmlParams( $param, $subject, $index );
			if ( count( $url ) ) {
				$sid = array_key_exists( 'sid', $url ) ? $url[ 'sid' ] : 0;
			}
		}

		return $sid;
	}

	/**
	 * @param $param
	 * @param string $subject
	 * @param int $index
	 *
	 * @return array
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function xmlParams( $param, $subject = C::ES, $index = -1 )
	{
		if ( !$param->hasChildNodes() ) {
			if ( $param->attributes->getNamedItem( 'parse' ) && $param->attributes->getNamedItem( 'parse' )->nodeValue == 'true' ) {
				$currentSubject = $subject ? $subject . '.' : null;
				/** we need to skip sometimes, and sometimes override the current subject
				 * i.e. getting section id which is not a part of the object*/
				if ( $param->attributes->getNamedItem( 'subject' ) ) {
					if ( $param->attributes->getNamedItem( 'subject' )->nodeValue == 'skip' ) {
						$currentSubject = null;
					}
					else {
						$currentSubject = $param->attributes->getNamedItem( 'subject' )->nodeValue . '.';
					}
				}
				if ( $currentSubject ) {
					$value = $this->get( $currentSubject . $param->attributes->getNamedItem( 'value' )->nodeValue, $index );
				}
				else {
					$value = $this->get( $param->attributes->getNamedItem( 'value' )->nodeValue );
				}
			}
			else {
				$value = isset( $param->attributes->getNamedItem( 'value' )->nodeValue ) ? $param->attributes->getNamedItem( 'value' )->nodeValue : null;
			}
		}
		else {
			$value = [];
			foreach ( $param->childNodes as $node ) {
				if ( strstr( $node->nodeName, '#' ) ) {
					continue;
				}
				if ( isset( $node->attributes->getNamedItem( 'name' )->nodeValue ) && $node->attributes->getNamedItem( 'name' )->nodeValue ) {
					$value[ $node->attributes->getNamedItem( 'name' )->nodeValue ] = $this->xmlParams( $node, $subject, $index );
				}
				else {
					$value[] = $this->xmlParams( $node, $subject, $index );
				}
			}
		}

		return $value;
	}

	/**
	 * @param $node
	 * @param $element
	 * @param null $value
	 * @param bool $skipCondition
	 * @param null $subject
	 * @param int $i
	 *
	 * @throws ReflectionException
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function xmlField( $node, &$element, $value = null, bool $skipCondition = false, $subject = C::ES, $i = -1 )
	{
		if ( !$skipCondition && !$this->xmlCondition( $node ) ) {
			return;
		}
		if ( Input::Task() == 'entry.edit' && Input::Cmd( 'revision' ) && isset( $element[ 'attributes' ][ 'name' ] ) ) {
			$i = str_replace( 'entry.', C::ES, $element[ 'attributes' ][ 'name' ] );
			if ( isset( $this->_attr[ 'revision' ][ $i ] ) ) {
				$element[ 'revisions-change' ] = $element[ 'attributes' ][ 'name' ];
			}
		}
		/** process all attributes  */
		$attributes = $node->attributes;
		$params = [];
		$args = [ 'type' => null, 'name' => null, 'value' => $value ];
		$adds = [ 'before' => null, 'after' => null ];
		$xml = [];
		$element[ 'help-position' ] = 'below';
		if ( $attributes->length ) {
			/** @var DOMElement $attribute */
			foreach ( $attributes as $attribute ) {
				$xml[ $attribute->nodeName ] = $attribute->nodeValue;
				switch ( $attribute->nodeName ) {
					case 'name':
						$args[ 'id' ] = StringUtils::Nid( $attribute->nodeValue );
						$element[ 'id' ] = $args[ 'id' ];
						$params[ 'id' ] = $args[ 'id' ];
//					case 'name':
					case 'type':
					case 'width':
					case 'height':
					case 'prefix':
					case 'aria-after':
					case 'aria-before':
						$args[ $attribute->nodeName ] = $attribute->nodeValue;
					break;
					case 'help-position':
						$element[ $attribute->nodeName ] = $attribute->nodeValue;
						break;
					case 'help-text':
						if ( !defined( 'SOBI_TRIMMED' ) ) {
							$element[ $attribute->nodeName ] = Sobi::Txt( $attribute->nodeValue );
							if ( strstr( $element[ $attribute->nodeName ], 'var:[' ) ) {
								$element[ $attribute->nodeName ] = $this->parseValue( $attribute->nodeValue );
							}
						}
						break;
					case 'editor':
					case 'multi':
					$args[ $attribute->nodeName ] = $attribute->nodeValue == 'true';
						break;
					case 'selected':
						/** if it is being called from a loop, we have to try it that way first */
						if ( strlen( $subject ) ) {
							$args[ $attribute->nodeName ] = $this->get( $subject . '.' . $attribute->nodeValue, $i );
							if ( $args[ $attribute->nodeName ] ) {
								break;
							}
						}
					case 'value':
						if ( $value ) {
							break;
						}
					/** no break here */
					case 'values':
						$args[ $attribute->nodeName ] = $this->get( $attribute->nodeValue );
						break;
					case 'value-parsed':
						$args[ 'value' ] = $attribute->nodeValue;
						break;
					case 'value-text':
						$args[ 'value' ] = Sobi::Txt( $attribute->nodeValue );
						break;
					case 'label':
					case 'header':
						$text = Sobi::Txt( $attribute->nodeValue );
						$element[ $attribute->nodeName ] = $text;
						$args[ $attribute->nodeName ] = $text;
						break;
					case 'placeholder':
						$params[ $attribute->nodeName ] = Sobi::Txt( $attribute->nodeValue );
						break;
					default:
						if ( strstr( $attribute->nodeValue, 'var:[' ) ) {
							$params[ $attribute->nodeName ] = $this->parseValue( $attribute->nodeValue );
						}
						else {
							$params[ $attribute->nodeName ] = ( $attribute->nodeValue );
						}
						$args[ $attribute->nodeName ] = $params[ $attribute->nodeName ];
						break;
				}
			}
		}
		if ( $node->hasChildNodes() ) {
			foreach ( $node->childNodes as $child ) {
				if ( strstr( $child->nodeName, '#' ) ) {
					continue;
				}
				/** @var DOMNode $child */
				switch ( $child->nodeName ) {
					case 'values':
						if ( $child->childNodes->length ) {
							$values = [];
							/** @var DOMNode $value */
							foreach ( $child->childNodes as $value ) {
								if ( strstr( $value->nodeName, '#' ) ) {
									continue;
								}
								/** select list with groups e.g. */
								if ( $value->nodeName == 'values' ) {
									$group = [];
									if ( $value->hasChildNodes() ) {
										foreach ( $value->childNodes as $groupNode ) {
											if ( strstr( $groupNode->nodeName, '#' ) ) {
												continue;
											}
											$group[ $groupNode->attributes->getNamedItem( 'value' )->nodeValue ] = Sobi::Txt( $groupNode->attributes->getNamedItem( 'label' )->nodeValue );
										}
									}
									$values[ Sobi::Txt( $value->attributes->getNamedItem( 'label' )->nodeValue ) ] = $group;
								}
								else {
									$vv = $value->attributes->getNamedItem( 'value' )->nodeValue;
									$vl = $value->attributes->getNamedItem( 'label' ) ? $value->attributes->getNamedItem( 'label' )->nodeValue : $vv;
									$xml[ 'childs' ][ $child->nodeName ][ $vv ] = $vl;
									$values[ $vv ] = Sobi::Txt( $vl );
								}
							}
						}
						$args[ 'values' ] = $values;
						break;
					case 'value':
						if ( $child->childNodes->length ) {
							/** @var DOMNode $value */
							foreach ( $child->childNodes as $value ) {
								if ( strstr( $value->nodeName, '#' ) ) {
									continue;
								}
								switch ( $value->nodeName ) {
									case 'url':
										$params = [];
										$content = 'no content given';
										$noContent = false;
										foreach ( $value->attributes as $a ) {
											switch ( $a->nodeName ) {
												case 'type':
												case 'host':
													break;
												case 'content':
													$v = $this->get( trim( $a->nodeValue ) );
													if ( !$v ) {
														$v = Sobi::Txt( trim( $a->nodeValue ) );
														$noContent = true;
													}
													$content = $v;
													break;
												case 'uri':
													$params[ 'href' ] = $this->get( trim( $a->nodeValue ) );
												default:
													$params[ $a->nodeName ] = $a->nodeValue;
													break;
											}
										}
										if ( !isset( $params[ 'href' ] ) ) {
											$params[ 'href' ] = $this->xmlUrl( $value );
										}
										if ( isset( $params[ 'uri' ] ) ) {
											unset ( $params[ 'uri' ] );
										}
										if ( $noContent ) {
											$args[ 'value' ] = $content;
										}
										else {
											$link = '<a ';
											foreach ( $params as $k => $v ) {
												$link .= $k . '="' . $v . '" ';
											}
											$link .= '>' . $content . '</a>';
											$args[ 'value' ] = $link;
										}
										break;
								}
							}
						}
						break;
					case 'attribute':
						$name = $child->attributes->getNamedItem( 'name' )->nodeValue;
						$value = $this->get( $child->attributes->getNamedItem( 'value' )->nodeValue );
						if ( in_array( $name, [ 'disabled', 'readonly' ] ) && !$value ) {
							break;  // was continue;
						}
						if ( $name == 'label' ) {
							$element[ $name ] = $value;
						}
						else {
							$params[ $name ] = $value;
							$args[ $name ] = $value;
						}
						break;
					case 'add':
						if ( $child->childNodes->length ) {
							/** @var DOMNode $value */
							foreach ( $child->childNodes as $value ) {
								if ( strstr( $value->nodeName, '#' ) ) {
									continue;
								}
								if ( $value->nodeName == 'call' ) {
									$v = $this->xmlCall( $value );
								}
								else {
									if ( $value->nodeName == 'text' ) {
										$v = $value->nodeValue;
									}
									else {
										if ( $value->nodeName == 'button' ) {
											$v = $this->xmlButton( $value );
										}
									}
								}
								$adds[ $child->attributes->getNamedItem( 'where' )->nodeValue ][] = $v;
							}
						}
						break;
				}
			}
		}
		if ( array_key_exists( 'aria-label', $params ) ) {
			$params[ 'aria-label' ] = Sobi::Txt( $params[ 'aria-label' ] );
		}
		$args[ 'params' ] = $params;
		$element[ 'args' ] = $args;
		$element[ 'adds' ] = $adds;
		$element[ 'request' ] = $xml;
		if ( array_key_exists( 'name', $args ) && !( strlen( (string) $args[ 'name' ] ) ) ) {
			$args[ 'name' ] = isset( $xml[ 'value' ] ) && strlen( (string) $xml[ 'value' ] ) ? $xml[ 'value' ] : uniqid();
		}
		switch ( $args[ 'type' ] ) {
			case 'output':
				$content = $args[ 'value' ] ? (string) $this->get( $args[ 'value' ] ) : C::ES;
				$element[ 'content' ] = strlen( $content ) ? $content : $args[ 'value' ];
				$element[ 'attributes' ][ 'attributes' ] = $element[ 'content' ];
				break;
			case 'custom':
				$field = $this->get( $args[ 'fid' ] );
				if ( $field && $field instanceof SPField ) {
					$element[ 'label' ] = $field->get( 'name' );
					if ( count( $params ) ) {
						foreach ( $params as $k => $p ) {
							if ( $k == 'class' ) {
								$k = 'cssClass';
							}
							$field->set( $k, $p );
						}
					}
					$element[ 'content' ] = $field->field( true );
				}
				break;
			default:
				if ( method_exists( 'SPHtml_Input', $args[ 'type' ] ) || method_exists( 'SPHtml_Input', '_' . $args[ 'type' ] ) ) {
					$method = new ReflectionMethod( 'SPHtml_Input', '_' . $args[ 'type' ] );
					$methodArgs = [];
					$methodParams = $method->getParameters();
					foreach ( $methodParams as $param ) {
						if ( isset( $args[ $param->name ] ) ) {
							if ( $param->getType() ) {
								try {
									Type::Cast( $args[ $param->name ], $param->getType()->getName() );
								}
								catch ( Throwable $x ) {
									Sobi::Error( $this->name(), '[ debug ] ' . $x->getMessage() );
								}
							}
							$methodArgs[] = $args[ $param->name ];
						}
						else {
							if ( $param->name == 'value' && !isset( $args[ 'value' ] ) && isset( $args[ 'name' ] ) ) {
								$argument = $this->get( $args[ 'name' ] );
								try {
									$name = 'string';
									if ( $param->getType() ) {
										$name = $param->getType()->getName();
									}
									else {
										if ( $param->isDefaultValueAvailable() ) {
											$name = gettype( $param->getDefaultValue() );
										}
									}
									Type::Cast( $argument, $name );
								}
								catch ( Throwable $x ) {
									Sobi::Error( $this->name(), '[ debug ] ' . $x->getMessage() );
								}
								$methodArgs[] = $argument;
							}
							else {
								if ( $param->isDefaultValueAvailable() ) {
									$methodArgs[] = $param->getDefaultValue();
								}
								else {
									$methodArgs[] = null;
								}
							}
						}
					}
					$element[ 'content' ] = call_user_func_array( [ 'SPHtml_Input', $args[ 'type' ] ], $methodArgs );
				}
				else {
					Sobi::Error( $this->name(), SPLang::e( 'METHOD_DOES_NOT_EXISTS', $args[ 'type' ] ), C::WARNING, 0, __LINE__, __FILE__ );
				}
				break;
		}
	}

	/**
	 * @param $value
	 * @param null $subject
	 * @param int $i
	 *
	 * @return false|mixed
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function xmlCall( $value, $subject = null, $i = -1 )
	{
		$function = $value->attributes->getNamedItem( 'function' )->nodeValue;
		$r = false;
		$params = [];
		if ( $value->hasChildNodes() ) {
			foreach ( $value->childNodes as $p ) {
				if ( strstr( $p->nodeName, '#' ) ) {
					continue;
				}
				if ( $p->attributes->length && $p->attributes->getNamedItem( 'value' ) ) {
					$subject = $subject ? $subject . '.' . $p->attributes->getNamedItem( 'value' )->nodeValue : $p->attributes->getNamedItem( 'value' )->nodeValue;
					$v = $this->get( $subject, $i );
					if ( $v ) {
						$params[] = $v;
					}
					else {
						if ( $p->attributes->getNamedItem( 'default' ) ) {
							$params[] = $p->attributes->getNamedItem( 'default' )->nodeValue;
						}
					}
				}
				else {
					$params[] = $p->nodeValue;
				}
			}
		}
		if ( strstr( $function, '::' ) ) {
			$function = explode( '::', $function );
			if ( class_exists( $function[ 0 ] ) && method_exists( $function[ 0 ], $function[ 1 ] ) ) {
				$r = call_user_func_array( [ $function[ 0 ], $function[ 1 ] ], $params );
			}
			else {
				Sobi::Error( $this->name(), SPLang::e( 'METHOD_DOES_NOT_EXISTS', $function[ 0 ] . '::' . $function[ 1 ] ), C::WARNING, 0, __LINE__, __FILE__ );
			}
		}
		else {
			if ( strstr( $function, '.' ) ) {
				$function = explode( '.', $function );
				$object = $this->get( $function[ 0 ] );
				$r = call_user_func_array( [ $object, $function[ 1 ] ], $params );
			}
			else {
				if ( function_exists( $function ) ) {
					$r = call_user_func_array( $function, $params );
				}
				else {
					Sobi::Error( $this->name(), SPLang::e( 'METHOD_DOES_NOT_EXISTS', $function ), C::WARNING, 0, __LINE__, __FILE__ );
				}
			}
		}

		return $r;
	}

	/**
	 * @param $xml
	 *
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function xmlConfig( $xml )
	{
		/** @var DOMNode $node */
		foreach ( $xml as $node ) {
			switch ( $node->nodeName ) {
				case 'hidden':
					$hidden = $node->childNodes;
					foreach ( $hidden as $field ) {
						if ( !$this->xmlCondition( $field ) ) {
							continue;
						}
						/** @var DOMNode $field */
						if ( !strstr( $field->nodeName, '#' ) ) {
							if ( $field->attributes->getNamedItem( 'const' ) && $field->attributes->getNamedItem( 'const' )->nodeValue ) {
								$this->addHidden( $field->attributes->getNamedItem( 'const' )->nodeValue, $field->attributes->getNamedItem( 'name' )->nodeValue );
							}
							else {
								if ( $field->attributes->getNamedItem( 'translate' ) && $field->attributes->getNamedItem( 'translate' )->nodeValue ) {
									$this->addHidden( Sobi::Txt( $field->attributes->getNamedItem( 'translate' )->nodeValue ), $field->attributes->getNamedItem( 'name' )->nodeValue );
								}
								else {
									$value = null;
									$name = $field->attributes->getNamedItem( 'name' )->nodeValue;
									if ( $field->attributes->getNamedItem( 'value' ) && $field->attributes->getNamedItem( 'value' )->nodeValue ) {
										$value = $this->get( $field->attributes->getNamedItem( 'value' )->nodeValue );
									}
									else {
										if ( $field->attributes->getNamedItem( 'default' ) ) {
											$value = $field->attributes->getNamedItem( 'default' )->nodeValue;
										}
									}
									$this->addHidden( Input::String( $name, 'request', $value ?? C::ES ), $name );
								}
							}
						}
					}
					break;
				default:
					if ( !strstr( $node->nodeName, '#' ) ) {
						if ( $node->attributes->getNamedItem( 'value' ) ) {
							$this->_config[ 'general' ][ $node->nodeName ] = $node->attributes->getNamedItem( 'value' )->nodeValue;
						}
					}
					break;
			}
		}
	}

	/**
	 * @param $xml
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function xmlHeader( $xml )
	{
		/** @var DOMNode $node */
		foreach ( $xml as $node ) {
			if ( strstr( $node->nodeName, '#' ) ) {
				continue;
			}
			switch ( $node->nodeName ) {
				case 'script':
					SPFactory::header()->addJsCode( $node->nodeValue );
					break;
				case 'style':
					SPFactory::header()->addCSSCode( $node->nodeValue );
					break;
				case 'language':
					Factory::Application()->loadLanguage( $node->nodeValue );
					break;
				case 'file':
					switch ( $node->attributes->getNamedItem( 'type' )->nodeValue ) {
						case 'style':
							$this->loadCSSFile( $node->attributes->getNamedItem( 'filename' )->nodeValue, false );
							break;
						case 'script':
							$this->loadJsFile( $node->attributes->getNamedItem( 'filename' )->nodeValue, false );
							break;
						case 'language':
							Factory::Application()->loadLanguage( $node->attributes->getNamedItem( 'filename' )->nodeValue );
							break;
					}
					break;
				/* old title in Joomla header (still there for compatibility) */
//				case 'title':
//					$this->setTitle( $node->attributes->getNamedItem( 'value' )->nodeValue );
//					break;
			}
		}
		$this->setSectionTitle();
	}

	/**
	 * @return string
	 * @throws SPException
	 */
	protected function legacyMessages()
	{
		$messages = SPFactory::message()->getMessages();
		$out = [];
		if ( count( $messages ) ) {
			foreach ( $messages as $type => $texts ) {
				if ( count( $texts ) ) {
					$out[] = "<div class=\"alert alert-$type alert-dismissible dk-system-alert\">";
					foreach ( $texts as $text ) {
						$out[] = "<div>$text</div>";
					}
					$out[] = '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
					$out[] = '</div>';
				}
			}
		}

		return implode( '', $out );
	}

	/**
	 * @param $cfg
	 *
	 * @return mixed
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function parseMenu( $cfg )
	{
		if ( count( $cfg ) ) {
			foreach ( $cfg as $i => $key ) {
				if ( strstr( $key, 'var:[' ) ) {
					preg_match( '/var\:\[([a-zA-Z0-9\.\_\-]*)\]/', $key, $matches );
					$key = str_replace( $matches[ 0 ], $this->get( $matches[ 1 ] ), $key );
				}
				if ( strstr( $key, '->' ) ) {
					$key = explode( '->', $key );
					$callback = trim( $key[ 0 ] );
					$params = isset( $key[ 1 ] ) ? trim( $key[ 1 ] ) : null;
					if ( strstr( $callback, '.' ) ) {
						$callback = explode( '.', $callback );
						$class = trim( $callback[ 0 ] );
						if ( !class_exists( $class ) ) {
							$class = 'SP' . ucfirst( $class );
						}
						$method = isset( $callback[ 1 ] ) ? trim( $callback[ 1 ] ) : null;
						if ( $method && class_exists( $class ) && method_exists( $class, $method ) ) {
							$cfg[ $i ] = call_user_func_array( [ $class, $method ], [ $params ] );
						}
						else {
							Sobi::Error( 'Function from INI', SPLang::e( 'Function %s::%s does not exist!', $class, $method ), C::WARNING, 0, __LINE__, __FILE__ );
						}
					}
					else {
						if ( function_exists( $callback ) ) {
							$cfg[ $i ] = call_user_func_array( $callback, [ $params ] );
						}
						else {
							Sobi::Error( 'Function from INI', SPLang::e( 'Function %s does not exist!', $callback ), C::WARNING, 0, __LINE__, __FILE__ );
						}
					}
				}
				else {
					$cfg[ $i ] = trim( $key );
				}
			}
		}

		return $cfg;
	}

	/**
	 * @param $path
	 * @param bool $adm
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function loadCSSFile( $path, bool $adm = true )
	{
		Sobi::Trigger( 'loadCSSFile', $this->name(), [ &$path ] );
		if ( strstr( $path, '|' ) ) {
			$path = explode( '|', $path );
			$adm = $path[ 1 ];
			$path = $path[ 0 ];
		}
		SPFactory::header()->addCssFile( $path, $adm );
	}

	/**
	 * @param string $path
	 * @param bool $adm
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function loadJsFile( $path, bool $adm = true )
	{
		Sobi::Trigger( 'loadJsFile', $this->name(), [ &$path ] );
		if ( strstr( $path, '|' ) ) {
			$path = explode( '|', $path );
			$adm = $path[ 1 ];
			$path = $path[ 0 ];
		}
		SPFactory::header()->addJsFile( $path, $adm );
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
	public function & setTemplate( $template )
	{
		$this->_template = $template;
		Sobi::Trigger( 'setTemplate', $this->name(), [ &$this->_template ] );

		return $this;
	}

	/**
	 * Sets the default (section) title in SobiPro toolbar in back-end.
	 *
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	public function setSectionTitle()
	{
		$section = Sobi::Section( true );
		$sobipro = defined( 'SOBI_TRIMMED' ) ? 'SobiPro Trial' : 'SobiPro';

		if ( $section ) {
			$this->set( $sobipro . " [$section]", 'site_title' );
		}
		else {
			$this->set( $sobipro, 'site_title' );
		}
	}

	/**
	 * @param $title
	 *
	 * @return mixed|string
	 *
	 * @deprecated since 2.0
	 */
	public function setTitle( $title )
	{
//		if ( strstr( $title, '{' ) ) {
//			$title = ( array ) SPFactory::config()->structuralData( 'json://' . $title );
//			$task = Input::Task();
//			$title = $title[ $task ];
//		}
//		$title = $this->parseValue( Sobi::Txt( $title ) );
//		Sobi::Trigger( 'setTitle', $this->name(), [ &$title ] );
//		SPFactory::header()->setTitle( $title );
//		$this->set( $title, 'site_title' );
//
		return $title;
	}

	/**
	 * Returns copy of stored key.
	 *
	 * @param $key
	 * @param null $def
	 * @param string $section
	 *
	 * @return array|mixed|string|null
	 */
	protected function key( $key, $def = null, $section = 'general' )
	{
		if ( strstr( $key, '.' ) ) {
			$key = explode( '.', $key );
			$section = $key[ 0 ];
			$key = $key[ 1 ];
		}

		return $this->_config[ $section ][ $key ] ?? Sobi::Cfg( $key, $def, $section );
	}

	/**
	 * @param $attr
	 * @param null $vars
	 */
	protected function txt( $attr, $vars = null )
	{
		if ( strpos( $attr, '[JS]' ) === false ) {
			echo str_replace( ' ', '&nbsp;', Sobi::Txt( $attr, $vars ) );
		}
		else {
			echo Sobi::Txt( $attr, $vars );
		}
	}

	/**
	 * @param $date
	 * @param bool $start
	 *
	 * @return int|mixed|string|null
	 */
	protected function date( $date, $start = true )
	{
		$config =& SPFactory::config();
		$date = $config->date( $date );
		if ( $date == 0 ) {
			$date = $start ? Sobi::Txt( 'ALWAYS_VALID' ) : Sobi::Txt( 'NEVER_EXPIRES' );
		}

		return $date;
	}

	/**
	 * @return mixed|null
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function field()
	{
		$params = func_get_args();
		$field = null;
		if ( isset( $params[ 0 ] ) ) {
			if ( !method_exists( 'SPHtml_Input', $params[ 0 ] ) ) {
				$params[ 0 ] = '_' . $params[ 0 ];
			}
			if ( method_exists( 'SPHtml_Input', $params[ 0 ] ) ) {
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
	}

	/**
	 * @param $attr
	 * @param int $index
	 *
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function show( $attr, $index = -1 )
	{
		if ( strstr( $attr, 'config.' ) !== false ) {
			echo $this->key( str_replace( 'config.', C::ES, $attr ) );
		}
		else {
			echo $this->get( $attr, $index );
		}
	}

	/**
	 * @param $attr
	 * @param int $index
	 *
	 * @return int
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function count( $attr, $index = -1 ): int
	{
		$el =& $this->get( $attr, $index );

		return is_array( $el ) ? count( $el ) : 0;
	}

	/**
	 * @param string|array $attr
	 * @param mixed $name
	 *
	 * @return $this|SPObject
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
	 * @return array|int
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function & get( $attr, $index = -1 )
	{
		$r = null;
		if ( is_string( $attr ) && strstr( $attr, '.' ) ) {
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
					if ( is_array( $var ) || is_object( $var ) ) {
						/* it has to be SPObject subclass to access the attribute */
						if ( is_object( $var ) && method_exists( $var, 'has' ) /*&& $var->has( $property )*/ ) {
							if ( method_exists( $var, 'get' ) ) {
								$var = $var->get( $property, null, true );
							}
						}
						/* otherwise, try to access std object */
						else {
							if ( is_object( $var ) && isset( $var->$property ) ) {
								$var = $var->$property;
							}
							else {
								if ( $property == 'length' && is_array( $var ) ) {
									$r = count( $var );

									return $r;
								}
								/* otherwise, try to access array field */
								else {
									if ( is_array( $var ) && isset( $var[ $property ] ) ) {
										$var = $var[ $property ];
									}
									else {
										return $r;
									}
								}
							}
						}
					}
					else {
						return $r;
					}
				}
			}
			$r = $var;
		}
		else {
			$r = null;
		}
		$r = is_string( $r ) ? StringUtils::Clean( $r ) : $r;

		return $r;
	}

	/**
	 * @return void
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function display()
	{
		$tpl = SPLoader::path( $this->_template . '_override', 'adm.template' );
		if ( !$tpl ) {
			if ( strstr( $this->_template, 'absolute://' ) ) {
				$tpl = FileSystem::FixPath( str_replace( 'absolute://', C::ES, $this->_template ) );
			}
			else {
				$tpl = SPLoader::path( $this->_template, 'adm.template' );
			}
		}
		if ( !$tpl ) {
			$tpl = SPLoader::translatePath( $this->_template, 'adm.template', false );
			Sobi::Error( $this->name(), SPLang::e( 'TEMPLATE_DOES_NOT_EXISTS', $tpl ), C::ERROR, 500, __LINE__, __FILE__ );
			exit();
		}
		Sobi::Trigger( 'Display', $this->name(), [ &$this ] );
		$action = $this->key( 'action' );

		echo "\n<!-- Output of SobiPro component -->\n";
		echo '<div class="SobiPro" id="SobiPro" data-bs="5" data-sp-task="' . Input::Task() . '" data-site="adm">' . "\n";
		echo $action ? "\n<form action=\"$action\" data-spctrl=\"form\" method=\"post\" name=\"adminForm\" id=\"SPAdminForm\" enctype=\"multipart/form-data\" accept-charset=\"utf-8\" >\n" : C::ES;

		$prefix = 'SP_';
		include( $tpl );
		if ( count( $this->_hidden ) ) {
			$this->_hidden[ Factory::Application()->token() ] = 1;
			$this->_hidden[ 'spsid' ] = microtime( true ) + ( ( Sobi::My( 'id' ) * mt_rand( 5, 15 ) ) / mt_rand( 5, 15 ) );
			foreach ( $this->_hidden as $name => $value ) {
				echo "\n<input type=\"hidden\" name=\"$name\" id=\"$prefix$name\" value=\"$value\"/>";
			}
		}
		echo $action ? "\n</form>\n" : C::ES;
		echo '</div>' . "\n";
		echo "\n<!-- End of output of SobiPro component -->\n";
		Sobi::Trigger( 'AfterDisplay', $this->name() );
	}

	/**
	 * @param bool $return
	 *
	 * @return string
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function menu( bool $return = false ): string
	{
		$menu = $this->get( 'menu' );
		if ( $menu && method_exists( $menu, 'display' ) ) {
			if ( $return ) {
				return $menu->display();
			}
			else {
				echo $menu->display();
			}
		}

		return C::ES;
	}

	/**
	 * @param $ids
	 *
	 * @return array|false|mixed|null
	 * @throws \SPException
	 */
	protected function userData( $ids )
	{
		return SPUser::getBaseData( $ids );
	}

	/**
	 * @param string $action
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function trigger( string $action )
	{
		echo Sobi::TriggerPlugin( $action, $this->_plgSect );
	}

	/**
	 * @param int $id
	 * @param bool $parents
	 * @param bool $last
	 * @param int $offset
	 *
	 * @return string
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function parentPath( $id, $parents = false, $last = false, $offset = 2 ): string
	{
		static $pathArray = [];
		$path = C::ES;
		if ( !count( $pathArray ) ) {
			$pathArray = SPFactory::config()->getParentPath( $id, true, $parents );
		}
		if ( !$last ) {
			if ( is_array( $pathArray ) && count( $pathArray ) ) {
				$path = implode( Sobi::Cfg( 'string.path_separator', ' > ' ), $pathArray );
			}
		}
		else {
			if ( is_array( $pathArray ) && isset( $pathArray[ count( $pathArray ) - $offset ] ) ) {
				$path = $pathArray[ count( $pathArray ) - $offset ];
			}
		}

		return StringUtils::Clean( $path );
	}

	/**
	 * @param $node
	 * @param $element
	 *
	 * @return mixed
	 * @throws SPException|\Sobi\Error\Exception
	 */
	protected function xmlAttributes( $node, $element )
	{
		$attributes = $node->attributes;
		if ( $attributes->length ) {
			/** @var DOMElement $attribute */
			foreach ( $attributes as $attribute ) {
				switch ( $attribute->nodeName ) {
					case 'label':
						$element[ 'attributes' ][ $attribute->nodeName ] = Sobi::Txt( $attribute->nodeValue );
						$element[ 'attributes' ][ $attribute->nodeName ] = $this->parseValue( $element[ 'attributes' ][ $attribute->nodeName ] );
						break;
					case 'class':
					case 'rel' :
						$element[ 'attributes' ][ $attribute->nodeName ] = $attribute->nodeValue;
						break;
					case 'link-condition':
						$element[ 'attributes' ][ $attribute->nodeName ] = $this->xmlCondition( $node, 'link-' );
						break;
					default:
						$element[ 'attributes' ][ $attribute->nodeName ] = $this->parseValue( $attribute->nodeValue );
						break;
				}
				if ( array_key_exists( 'link-condition', $element[ 'attributes' ] ) && !$element[ 'attributes' ][ 'link-condition' ] && isset( $element[ 'attributes' ][ 'link' ] ) ) {
					unset ( $element[ 'attributes' ][ 'link' ] );
				}
			}
		}

		return $element;
	}
}
