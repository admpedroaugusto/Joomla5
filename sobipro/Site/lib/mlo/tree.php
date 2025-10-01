<?php
/**
 * @package SobiPro Library
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Email: sobi[at]sigsiu.net
 *
 * Url: https://www.Sigsiu.NET
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
 * @modified 31 August 2023 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

use Sobi\C;
use Sobi\FileSystem\FileSystem;
use Sobi\Lib\Factory;

/**
 * Class SigsiuTree
 */
final class SigsiuTree extends SPObject
{
	/**
	 * array with all needed images
	 *
	 * @var array
	 */
	private $_images = [
		'root'        => 'base.gif',
		'join'        => 'empty.gif',
		'joinBottom'  => 'empty.gif',
		'plus'        => 'arrow_close.gif',
		'plusBottom'  => 'arrow_close.gif',
		'minus'       => 'arrow_open.gif',
		'minusBottom' => 'arrow_open.gif',
		'folder'      => 'folder.gif',
		'disabled'    => 'disabled.gif',
		'folderOpen'  => 'folderopen.gif',
		'line'        => 'empty.gif',
		'empty'       => 'empty.gif',
	];
	/**
	 * @var string
	 */
	private $tree = C::ES;
	/**
	 * @var string
	 */
	private $_task = 'tree.node';
	/**
	 * @var string
	 */
	private $_url = C::ES;
	/**
	 * @var string
	 */
	private $_tag = 'div';
	/**
	 * @var string
	 */
	private $_id = 'sigsiu_tree_categories';
	/**
	 * @var string
	 */
	private $_ordering = 'position';
	/**
	 * @var int
	 */
	private $_sid = 0;
	/**
	 * @var int
	 */
	private $_pid = 0;
	/**
	 * @var array
	 */
	private $_disabled = [];

	/**
	 * Sets category, or set of category ids which should not be selectable in the tree.
	 *
	 * @param int $cid
	 */
	public function disable( $cid )
	{
		if ( is_array( $cid ) ) {
			$this->_disabled = array_merge( $this->_disabled, $cid );
		}
		else {
			$this->_disabled[] = $cid;
		}
	}

	/**
	 * Returns the created Tree.
	 *
	 * @return string
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function getTree()
	{
		Sobi::Trigger( 'SigsiuTree', ucfirst( __FUNCTION__ ), [ &$this->tree ] );

		return $this->tree;
	}

	/**
	 * @param false $return
	 *
	 * @return string
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function display( $return = false )
	{
		Sobi::Trigger( 'SigsiuTree', ucfirst( __FUNCTION__ ), [ &$this->tree ] );
		if ( $return ) {
			return $this->tree;
		}
		else {
			echo $this->tree;
		}

		return C::ES;
	}

	/**
	 * @param $id
	 */
	public function setId( $id )
	{
		$this->_id = $id;
	}

	/**
	 * @param int $pid
	 */
	public function setPid( $pid )
	{
		$this->_pid = $pid;
	}

	/**
	 * @param $images
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function setImages( $images )
	{
		Sobi::Trigger( 'SigsiuTree', ucfirst( __FUNCTION__ ), [ &$images ] );
		if ( $images && is_array( $images ) ) {
			foreach ( $images as $img => $loc ) {
				if ( file_exists( SOBI_ROOT . '/' . $loc ) ) {
					$this->_images[ $img ] = $loc;
				}
			}
			foreach ( $this->_images as $img => $loc ) {
				$this->_images[ $img ] = FileSystem::FixUrl( Sobi::Cfg( 'tree_images', Sobi::Cfg( 'media_folder_live' ) . 'tree/' . $loc ) );
			}
		}
	}

	/**
	 * @param string $tag
	 */
	public function setTag( $tag )
	{
		$this->_tag = $tag;
	}

	/**
	 * @param string $task
	 */
	public function setTask( $task )
	{
		$this->_task = $task;
	}

	/**
	 * @param string $href
	 */
	public function setHref( $href )
	{
		$this->_url = $href;
	}

	/**
	 * SigsiuTree constructor.
	 *
	 * @param string $ordering
	 * @param array $opts
	 */
	public function __construct( $ordering = 'position', $opts = [] )
	{
		$this->_ordering = $ordering;
		foreach ( $this->_images as $img => $loc ) {
			$this->_images[ $img ] = FileSystem::FixUrl( Sobi::Cfg( 'tree_images', Sobi::Cfg( 'media_folder_live' ) . 'tree/' . $loc ) );
		}
		if ( count( $opts ) ) {
			foreach ( $opts as $key => $value ) {
				$this->$key = $value;
			}
		}
	}

	/**
	 * Inits the tree.
	 *
	 * @param $sid
	 * @param int $current
	 * @param bool $unpublished
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function init( $sid, int $current = 0, bool $unpublished = true )
	{
		$head =& SPFactory::header();
		$tree = C::ES;
		$matrix = C::ES;
		$this->_sid = $sid;
		if ( $current ) {
			$this->startScript( $current );
		}

		$section = $this->getSection( $sid );
		$sectionLink = $this->parseLink( $section );
		$sectionName = $section->get( 'name' );
		$childs = $this->getChilds( $sid );
		$countNodes = count( $childs, 0 );
		$lastNode = 0;

		if ( $this->_id == "sigsiu_tree_categories" ) {   //categories tree called from iframe, SobiPro scope needed
			$tree .= "<$this->_tag class=\"SobiPro\">";
		}
		$tree .= "<$this->_tag class=\"spctrl-sigsiutree dk-sigsiutree $this->_id\">";
		$tree .= "<$this->_tag class=\"dk-sigsiutree-node\" id=\"{$this->_id}stNode0\">";
		if ( !in_array( $sid, $this->_disabled ) ) {
			$tree .= "<a href=\"$sectionLink\" id=\"{$this->_id}_imgFolderUrl0\"><img id=\"{$this->_id}0\" src=\"{$this->_images['root']}\" alt=\"$sectionName\"/></a>";
		}
		else {
			$tree .= "<img id=\"{$this->_id}0\" src=\"{$this->_images['root']}\" alt=\"$sectionName\"/>";
		}
		if ( !in_array( $sid, $this->_disabled ) ) {
			$tree .= "<a href=\"$sectionLink\"  rel=\"$sid\" data-sid=\"$sid\" class=\"treeNode\" id=\"{$this->_id}_CatUrl0\">$sectionName</a>";
		}
		else {
			$tree .= $sectionName;
		}
		$tree .= "</$this->_tag>";
		$tree .= "<$this->_tag id=\"$this->_id\" class=\"clip\" style=\"display: block;\">";

		if ( count( $childs ) ) {
			foreach ( $childs as $category ) {
				$countNodes--;
				// clean string produces html entities and these are invalid in XML
				$categoryName = $category->get( 'name' ); /*$this->cleanString*/
				if ( !$category->get( 'state' ) && defined( 'SOBIPRO_ADM' ) && $unpublished ) {
					$categoryName = $categoryName . ' (' . Sobi::Txt( 'UNPUBLISHED' ) . ')';
				}
				$hasChilds = count( $category->getChilds( 'category' ) );
				$cid = $category->get( 'id' );
				$url = $this->parseLink( $category );
				$disabled = in_array( $cid, $this->_disabled );

				$tree .= "<$this->_tag class=\"dk-sigsiutree-node\" id=\"{$this->_id}stNode$cid\">";

				if ( $hasChilds ) {
					if ( $countNodes == 0 && !$disabled ) {
						$lastNode = $cid;
						$tree .= "<a href=\"javascript:{$this->_id}_stmExpand( $cid, 0, $this->_pid );\" id=\"{$this->_id}_imgUrlExpand$cid\"><img src=\"{$this->_images[ 'plusBottom' ]}\" id=\"{$this->_id}_imgExpand$cid\" style=\"border-style:none;\" alt=\"expand\"/></a>";
						$matrix .= "{$this->_id}_stmImgMatrix[ $cid ] = new Array( 'plusBottom' );";
					}
					elseif ( !$disabled ) {
						$tree .= "<a href=\"javascript:{$this->_id}_stmExpand( $cid, 0, $this->_pid );\" id=\"{$this->_id}_imgUrlExpand$cid\"><img src=\"{$this->_images[ 'plus' ]}\" id=\"{$this->_id}_imgExpand$cid\" style=\"border-style:none;\" alt=\"expand\"/></a>";
						$matrix .= "{$this->_id}_stmImgMatrix[ $cid ] = new Array( 'plus' );";
					}
					else {
						$tree .= "<img src=\"{$this->_images[ 'join' ]}\" id=\"{$this->_id}_imgExpand$cid\" style=\"border-style:none;\" alt=\"expand\"/>";
						$matrix .= "{$this->_id}_stmImgMatrix[ $cid ] = new Array( 'plus' );";
					}
				}
				else {
					if ( $countNodes == 0 && !$disabled ) {
						$lastNode = $cid;
						$tree .= "<img src=\"{$this->_images[ 'joinBottom' ]}\" style=\"border-style:none;\" id=\"{$this->_id}_imgJoin$cid\" alt=\"\"/>";
						$matrix .= "{$this->_id}_stmImgMatrix[ $cid ] = new Array( 'join' );";
					}
					elseif ( !$disabled ) {
						$tree .= "<img src=\"{$this->_images[ 'join' ]}\" style=\"border-style:none;\" id=\"{$this->_id}_imgJoin$cid\" alt=\"\"/>";
						$matrix .= "{$this->_id}_stmImgMatrix[ $cid ] = new Array( 'joinBottom' );";
					}
					else {
						$tree .= "<img src=\"{$this->_images[ 'joinBottom' ]}\" id=\"{$this->_id}_imgExpand$cid\" style=\"border-style:none;\" alt=\"expand\"/>";
						$matrix .= "{$this->_id}_stmImgMatrix[ $cid ] = new Array( 'plus' );";
					}

				}
				if ( !$disabled ) {
					$tree .= "<a href=\"$url\" id=\"{$this->_id}_imgFolderUrl$cid\"><img src=\"{$this->_images[ 'folder' ]}\" style=\"border-style:none;\" id=\"{$this->_id}_imgFolder$cid\" alt=\"folder\"/></a><a href=\"$url\" rel=\"$cid\" data-sid=\"$cid\" class=\"treeNode\" id=\"{$this->_id}_CatUrl$cid\">$categoryName</a>";
				}
				else {
					$tree .= "<img src=\"{$this->_images[ 'disabled' ]}\" style=\"border-style:none;\" id=\"{$this->_id}_imgFolder$cid\" alt=\"\"/>$categoryName</a>";
				}

				$tree .= "</$this->_tag>";
				if ( $hasChilds && !$disabled ) {
					$tree .= "<$this->_tag id=\"{$this->_id}_childsContainer$cid\" class=\"clip\" style=\"display: block; display:none;\"></$this->_tag>";
				}
			}
		}
		$tree .= "</$this->_tag>";
		$tree .= "</$this->_tag>";
		if ( $this->_id == "sigsiu_tree_categories" ) {
			$tree .= "</$this->_tag>";
		}
		$this->createScript( $lastNode, $childs, $matrix, $head );
		$this->tree = $tree;
	}

	/**
	 * Returns information about subcategories in XML format.
	 *
	 * @param $sid
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function extend( $sid )
	{
		$childs = $this->getChilds( $sid );
		Sobi::Trigger( 'SigsiuTree', ucfirst( __FUNCTION__ ), [ &$childs ] );

		SPFactory::mainframe()->cleanBuffer();
		header( 'Content-type: application/xml' );
		echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
		echo "\n<root>";

		if ( count( $childs ) ) {
			foreach ( $childs as $category ) {
				$categoryName = $this->cleanString( $category->get( 'name' ) );
				$hasChilds = count( $category->getChilds( 'category' ) );
				$cid = $category->get( 'id' );
				$pid = $category->get( 'parent' );
				$url = preg_replace( '/&(?![#]?[a-z0-9]+;)/i', '&amp;', $this->parseLink( $category ) );
				$disabled = in_array( $cid, $this->_disabled );
				if ( $disabled ) {
					continue;
				}
				echo "\n\t<category>";
				echo "\n\t\t<catid>$cid</catid>";
				echo "\n\t\t<name>$categoryName</name>";
				echo "\n\t\t<introtext>.</introtext>";
				echo "\n\t\t<parentid>$pid</parentid>";
				echo "\n\t\t<childs>$hasChilds</childs>";
				echo "\n\t\t<url>$url</url>";
				echo "\n\t</category>";
			}
		}
		echo "\n</root>";
		/* we don't need any other information so we can go out */
		exit();
	}

	/**
	 * @param int $current
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function startScript( $current )
	{
		$path = SPFactory::config()->getParentPath( $current );

		if ( !$this->getChilds( $path[ count( $path ) - 1 ] ) ) {
			unset( $path[ count( $path ) - 1 ] );
		}
		if ( count( $path ) ) {
			foreach ( $path as $index => $category ) {
				if ( $category == Sobi::Section() ) {
					unset ( $path[ $index ] );
				}
			}
		}
		//unset( $path[ 0 ] );
		$func = $this->_id . '_stmExpand';
		$script = C::ES;
		if ( count( $path ) ) {
			foreach ( $path as $i => $cid ) {
				$retard = $i * 150;
				$script .= "\t\twindow.setTimeout( '$func( $cid, $i, 0 )', $retard );\n";
			}
			SPFactory::header()->addJsCode( "\tSobiCore.Ready( function () { \n$script\n \t} );" );
		}
	}

	/**
	 * @param $lastNode
	 * @param $childs
	 * @param $matrix
	 * @param $head
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function createScript( $lastNode, $childs, $matrix, $head )
	{
		$params = [];
		$params[ 'ID' ] = $this->_id;
		$params[ 'LAST_NODE' ] = ( string ) $lastNode;
		$params[ 'IMAGES_ARR' ] = C::ES;
		$params[ 'IMAGES_MATRIX' ] = $matrix;
		foreach ( $this->_images as $img => $loc ) {
			$params[ 'IMAGES_ARR' ] .= "{$this->_id}_stmImgs[ '$img' ] = '$loc';";
		}
		$params[ 'URL' ] = Sobi::Url(
			[
				'task'   => $this->_task,
				'sid'    => $this->_sid,
				'out'    => 'xml',
				'expand' => '__JS__',
				'pid'    => '__JS2__',
			],
			true, false
		);
		$params[ 'URL' ] = str_replace( '__JS__', '" + ' . $this->_id . '_stmcid + "', $params[ 'URL' ] );
		$params[ 'URL' ] = str_replace( '__JS2__', '" + ' . $this->_id . '_stmPid + "', $params[ 'URL' ] );
		$params[ 'FAIL_MSG' ] = Sobi::Txt( 'AJAX_FAIL' );
		$params[ 'TAG' ] = $this->_tag;
		$params[ 'SPINNER' ] = FileSystem::FixUrl( Sobi::Cfg( 'media_folder_live' ) . 'adm/spinner.gif' );
		Sobi::Trigger( 'SigsiuTree', ucfirst( __FUNCTION__ ), [ &$params ] );
		$head->addJsVarFile( 'tree', md5( count( $childs, COUNT_RECURSIVE ) . $this->_id . $this->_sid . $this->_task . serialize( $params ) ), $params );
	}

	/**
	 * @param $sid
	 * @param false $count
	 *
	 * @return array|int
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function getChilds( $sid, $count = false )
	{
		$childs = [];
		try {
			$ids = Factory::Db()
				->select( 'id', 'spdb_relations', [ 'pid' => $sid, 'oType' => 'category' ] )
				->loadResultArray();
		}
		catch ( Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'CANNOT_GET_CHILDS_DB_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}
		if ( $count ) {
			return count( $ids );
		}
		if ( count( $ids ) ) {
			foreach ( $ids as $id ) {
				$child = SPFactory::Category( $id );
				if ( $child->get( 'state' ) || defined( 'SOBIPRO_ADM' ) ) {
					$childs[] = $child;
				}
			}
		}
		uasort( $childs, [ $this, 'sortChilds' ] );

		return $childs;
	}

	/**
	 * @param $from
	 * @param $to
	 *
	 * @return float|int
	 */
	public function sortChilds( $from, $to )
	{
		switch ( $this->_ordering ) {
			case 'name':
			case 'name.asc':
			case 'name.desc':
				$retval = strcasecmp( $from->get( 'name' ), $to->get( 'name' ) );
				if ( $this->_ordering == 'name.desc' ) {
					$retval = $retval * -1;
				}
				break;
			default:
			case 'position':
			case 'position.asc':
			case 'position.desc':
				$retval = $from->get( 'position' ) > $to->get( 'position' ) ? 1 : -1;
				if ( $this->_ordering == 'position.desc' ) {
					$retval = $retval * -1;
				}
				break;
		}

		return $retval;
	}

	/**
	 * @param $sid
	 *
	 * @return SPSection
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function getSection( $sid )
	{
		SPLoader::loadModel( 'section' );
		$section = new SPSection();
		$section->init( $sid );

		return $section;
	}

	/**
	 * Parses the link (replace placeholders).
	 *
	 * @param $category
	 *
	 * @return string|array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function parseLink( $category )
	{
		static $placeHolders = [
			'{sid}',
			'{name}',
			'{introtext}',
		];
		$replacement = [
			$category->get( 'id' ),
			$category->get( 'name' ),
			$category->get( 'introtext' ),
		];
		$link = str_replace( $placeHolders, $replacement, $this->_url );
		Sobi::Trigger( 'SigsiuTree', ucfirst( __FUNCTION__ ), [ &$link ] );

		return $link;
	}

	/**
	 * Cleans string for javascript.
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	protected function cleanString( string $string ): string
	{
		return str_replace( '&amp;', '&#38;', htmlspecialchars( $string, ENT_COMPAT, 'UTF-8' ) );
	}
}