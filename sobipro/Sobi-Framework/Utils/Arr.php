<?php
/**
 * @package: Sobi Framework
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006 - 2022 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created Tue, Mar 28, 2017 Radek Suski
 * @modified 15 August 2022 by Sigrid Suski
 */

//declare( strict_types=1 );

namespace Sobi\Utils;

use DOMDocument;
use Sobi\C;

defined( 'SOBI' ) || exit( 'Restricted access' );

/**
 * Class Arr
 * @package Sobi\Utils
 */
class Arr
{
	/*** @var array */
	protected $_arr = [];
	/** @var bool */
	protected $legacy = true;

	/**
	 * @param string $str
	 * @param string $sep
	 * @param string $sep2
	 */
	public function fromString( string $str, string $sep, string $sep2 = C::ES )
	{
		if ( strstr( $str, $sep ) ) {
			$arr = explode( $sep, $str );
			if ( $sep2 ) {
				$c = 0;
				foreach ( $arr as $field ) {
					if ( strstr( $field, $sep2 ) ) {
						$f = explode( $sep2, $field );
						$this->_arr[ $f[ 0 ] ] = $f[ 1 ];
					}
					else {
						$this->_arr[ $c ] = $field;
						$c++;
					}
				}
			}
			else {
				$this->_arr = $arr;
			}
		}
	}

	/**
	 * @param array $array
	 * @param string $inDel
	 * @param string $outDel
	 *
	 * @return string
	 */
	public static function ToString( array $array, string $inDel = '=', string $outDel = ' ' ): string
	{
		$out = [];
		if ( is_array( $array ) && count( $array ) ) {
			foreach ( $array as $key => $item ) {
				if ( is_array( $item ) ) {
					$out[] = Arr::ToString( $item, $inDel, $outDel );
				}
				else {
					$out[] = "{$key}{$inDel}\"{$item}\"";
				}
			}
		}

		return implode( $outDel, $out );
	}

	/**
	 * @return array
	 */
	public function toArr(): array
	{
		return $this->_arr;
	}

	/**
	 * Check if given array ia array of integers
	 *
	 * @param array $arr
	 *
	 * @return bool
	 */
	public static function IsAnInt( array $arr ): bool
	{
		if ( is_array( $arr ) && count( $arr ) ) {
			foreach ( $arr as $k ) {
				if ( ( int ) $k != $k ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * @param array $arr
	 *
	 * @return string
	 */
	public function toINIString( array $arr ): string
	{
		$this->_arr = $arr;
		$out = [];
		if ( is_array( $this->_arr ) && count( $this->_arr ) ) {
			foreach ( $this->_arr as $key => $value ) {
				if ( is_array( $value ) && !( is_string( $value ) ) ) {
					$out[] = "[{$key}]";
					if ( count( $value ) ) {
						foreach ( $value as $k => $v ) {
							$k = $this->_cleanIni( $k );
							$out[] = "{$k} = \"{$v}\"";
						}
					}
				}
				else {
					$key = $this->_cleanIni( $key );
					$out[] = "{$key} = \"{$value}\"";
				}
			}
		}

		return implode( "\n", $out );
	}

	/**
	 * @param $txt
	 *
	 * @return string
	 */
	protected function _cleanIni( $txt ): string
	{
		return str_replace( [ '?{}|&~![()^"' ], C::ES, $txt );
	}

	/**
	 * @param array $arr
	 * @param string $root
	 * @param bool $returnDOM
	 *
	 * @return \DOMDocument|string
	 *
	 * @throws \Sobi\Error\Exception|\DOMException
	 */
	public function toXML( array $arr, string $root = 'root', bool $returnDOM = false )
	{
		$dom = new DOMDocument( '1.0', 'UTF-8' );
		$dom->formatOutput = true;
		$node = $dom->appendChild( $dom->createElement( $this->nid( $root ) ) );
		$this->_toXML( $arr, $node, $dom );
		if ( $returnDOM ) {
			return $dom;
		}

		return $dom->saveXML();
	}

	/**
	 * @param array $arr
	 * @param \DOMNode $node
	 * @param \DOMDocument $dom
	 *
	 * @throws \Sobi\Error\Exception|\DOMException
	 */
	protected function _toXML( array $arr, \DOMNode $node, DOMDocument &$dom )
	{
		if ( is_array( $arr ) && count( $arr ) ) {
			$attributes = null;
			foreach ( $arr as $name => $value ) {
				if ( is_numeric( $name ) ) {
					if ( is_array( $value ) && isset( $value[ 'xml-tag' ] ) ) {
						$name = $value[ 'xml-tag' ];
						$attributes = $value[ 'xml-attributes' ] ?? null;
						$value = $value[ 'xml-value' ];
					}
					else {
						$name = 'value';
					}
				}
				if ( is_array( $value ) ) {
					$nn = $node->appendChild( $dom->createElement( $this->nid( $name ) ) );
					if ( $attributes && is_array( $attributes ) && count( $attributes ) ) {
						foreach ( $attributes as $attribute => $attributeValue ) {
							$domAttribute = $dom->createAttribute( $attribute );
							$domAttribute->value = $attributeValue;
							$nn->appendChild( $domAttribute );
						}
					}
					$this->_toXML( $value, $nn, $dom );
				}
				else {
					if ( is_string( $value ) ) {
						$value = preg_replace( '/&(?![#]?[a-z0-9]+;)/i', '&amp;', $value );
					}
					$newElement = $dom->createElement( $this->nid( $name ), $value );
					if ( $attributes && is_array( $attributes ) && count( $attributes ) ) {
						foreach ( $attributes as $attribute => $attributeValue ) {
							$domAttribute = $dom->createAttribute( $attribute );
							$domAttribute->value = $attributeValue;
							$newElement->appendChild( $domAttribute );
						}
					}
					$node->appendChild( $newElement );
				}
			}
		}
	}

	/**
	 * @param string $txt
	 *
	 * @return string
	 *
	 * @throws \Sobi\Error\Exception
	 */
	protected function nid( string $txt ): string
	{
		return $this->legacy ? strtolower( StringUtils::Nid( $txt ) ) : StringUtils::Nid( $txt );
	}

	/**
	 * @param \DOMDocument $dom
	 * @param string $root
	 *
	 * @return array
	 */
	public function fromXML( DOMDocument $dom, string $root ): array
	{
		$r = $dom->getElementsByTagName( $root );
		$arr = [];
		$this->_fromXML( $r, $arr );

		return $arr;
	}

	/**
	 * @param array $arr
	 * @param string $root
	 * @param bool $returnDOM
	 *
	 * @return string
	 * @throws \Sobi\Error\Exception|\DOMException
	 * @internal param \DOMDocument $dom
	 */
	public function createXML( array $arr, string $root = 'root', bool $returnDOM = false ): string
	{
		$this->legacy = false;

		return $this->toXML( $arr, $root, $returnDOM );
	}

	/**
	 * @param \DOMNodeList $dom
	 * @param array $arr
	 *
	 * @return void
	 */
	protected function _fromXML( \DOMNodeList $dom, array &$arr )
	{
		foreach ( $dom as $node ) {
			/** DOMNode $node */
			if ( $node->hasChildNodes() ) {
				if ( $node->childNodes->item( 0 )->nodeName == '#text' && $node->childNodes->length == 1 ) {
					$arr[ $node->nodeName ] = $node->nodeValue;
				}
				else {
					$arr[ $node->nodeName ] = [];
					$this->_fromXML( $node->childNodes, $arr[ $node->nodeName ] );
				}
			}
			else {
				if ( $node->nodeName != '#text' ) {
					$arr[ $node->nodeName ] = $node->nodeValue;
				}
			}
		}
	}
}
