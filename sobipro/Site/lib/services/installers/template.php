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
 * @created 17-Jun-2010 by Radek Suski
 * @modified 21 June 2023 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadClass( 'services.installers.installer' );

use Sobi\C;
use Sobi\Input\Input;
use Sobi\FileSystem\FileSystem;
use Sobi\FileSystem\Directory;
use Sobi\Lib\Factory;
use Sobi\Utils\StringUtils;

/**
 * Class SPTemplateInstaller
 */
class SPTemplateInstaller extends SPInstaller
{
	/**
	 * @return string
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function install()
	{
		$id = $this->xGetString( 'id' );
		$name = $this->xGetString( 'name' );

		/* check if the template needs some apps installed */
		$requirements = $this->xGetChilds( 'requirements/*' );
		if ( $requirements && ( $requirements instanceof DOMNodeList ) ) {
			SPFactory::Instance( 'services.installers.requirements' )->check( $requirements );
		}
		/* check if we should force or update if the template (path) already exists */
		if ( !Input::Bool( 'force' ) && !Input::Bool( 'update' ) ) {
			if ( SPLoader::dirPath( 'usr/templates/' . $id ) ) {
				$rootFile = basename( $this->root ) . '/' . basename( $this->xmlFile );
				if ( Factory::Db()
						->select( 'COUNT(pid)', 'spdb_plugins',
							[ 'pid'  => 'template_packager',
							  'type' => 'application' ] )
						->loadResult() == 1
				) {
					throw new SPException( SPLang::e( 'TEMPLATE_INST_DUPLICATE', $name ) . ' ' .
						Sobi::Txt( 'FORCE_TPL_SECTION_UPDATE',
							Sobi::Url( [ 'task' => 'extensions.install', 'force' => 1, 'root' => $rootFile ] ),
							Sobi::Url( [ 'task' => 'extensions.install', 'update' => 1, 'root' => $rootFile ] )
						)
					);
				}
				else {
					throw new SPException( SPLang::e( 'TEMPLATE_INST_DUPLICATE', $name ) . ' ' . Sobi::Txt( 'FORCE_TPL_UPDATE', Sobi::Url( [ 'task' => 'extensions.install', 'force' => 1, 'root' => $rootFile ] ) ) );
				}
			}
		}

		/* install language files */
		$language = $this->xGetChilds( 'language/file' );
		if ( ( $language instanceof DOMNodeList ) && $language->length ) {
			$langFiles = [];
			$folder = @$this->xGetChilds( 'language/@folder' )->item( 0 )->nodeValue;
			foreach ( $language as $file ) {
				$adm = false;
				if ( $file->attributes->getNamedItem( 'admin' ) ) {
					$adm = $file->attributes->getNamedItem( 'admin' )->nodeValue == 'true';
				}
				$langFiles[ $file->attributes->getNamedItem( 'lang' )->nodeValue ][] =
					[
						'path' => FileSystem::FixPath( "$this->root/$folder/" . trim( $file->nodeValue ) ),
						'name' => $file->nodeValue,
						'adm'  => $adm,
					];
			}
			Factory::ApplicationInstaller()->installLanguage( $langFiles, false, true );
		}

		/* handle the template files (move them from the tmp folder to the template folder) */
		$path = SPLoader::dirPath( 'usr/templates/' . $id, 'front', false );
		if ( Input::Bool( 'force' ) || Input::Bool( 'update' ) ) {
			/* Move the files and overwrite */
			$from = new Directory( $this->root );
			$from->moveFiles( $path, true, true );
		}
		elseif ( !FileSystem::Move( $this->root, $path ) ) {
			throw new SPException( SPLang::e( 'CANNOT_MOVE_DIRECTORY', $this->root, $path ) );
		}

		/* Create/update the section */
//		if ( !( Input::Bool( 'force' ) ) ) {
		$section = $this->xGetChilds( 'install' );
		if ( ( $section instanceof DOMNodeList ) && $section->length ) {
			$this->section( $id, Input::Bool( 'update' ) );
		}
//		}

		/* execute a file during installation */
		$exec = $this->xGetString( 'exec' );
		if ( $exec && FileSystem::Exists( "$path/$exec" ) ) {
			include_once( "$path/$exec" );
		}

		/* delete the zip file in the template folder */
		$dir = new Directory( $path );
		$zip = array_keys( $dir->searchFile( '.zip', false, 2 ) );
		if ( count( $zip ) ) {
			foreach ( $zip as $file ) {
				FileSystem::Delete( $file );
			}
		}

		/* delete the installation files */
		Sobi::Trigger( 'After', 'InstallTemplate', [ $id ] );
		$dir = new Directory( SPLoader::dirPath( 'tmp/install' ) );
		$dir->deleteFiles();

		return Sobi::Txt( 'TP.TEMPLATE_HAS_BEEN_INSTALLED', [ 'template' => $name ] );
	}

	/**
	 * Creates/updates the section.
	 *
	 * @param $tpl
	 * @param $update
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function section( $tpl, $update )
	{
		$path = 'install/section/';
		$name = $this->xGetString( $path . 'name' );

		$db = Factory::Db();
		$sectionid = $db
			->select( 'id', 'spdb_object', [ 'oType' => 'section', 'nid' => StringUtils::Nid( $name ) ] )
			->loadResult();

		if ( $sectionid && $update ) {
			/* update section */
			$section = SPFactory::Section( $sectionid );
		}
		else {
			/* create new section */
			$section =& SPFactory::Instance( 'models.section' );
			$section->set( 'name', $name );
			$section->set( 'nid', StringUtils::Nid( $name ) );
		}

		/* get base section data */
		$section->set( 'description', $this->xGetString( $path . 'description' ) );
		$section->set( 'sfMetaDesc', $this->xGetString( $path . 'sfMetaDesc' ) );
		$section->set( 'sfMetaKeys', $this->xGetString( $path . 'sfMetaKeys' ) );
		$section->set( 'efMetaDesc', $this->xGetString( $path . 'efMetaDesc' ) );
		$section->set( 'efMetaKeys', $this->xGetString( $path . 'efMetaKeys' ) );
		$section->set( 'efTitle', $this->xGetString( $path . 'efTitle' ) );
		$section->set( 'sfTitle', $this->xGetString( $path . 'sfTitle' ) );
		$section->set( 'efDesc', $this->xGetString( $path . 'efDesc' ) );
		$section->set( 'sfDesc', $this->xGetString( $path . 'sfDesc' ) );
		$section->set( 'metaDesc', $this->xGetString( $path . 'metaDesc' ) );
		$section->set( 'metaKeys', $this->xGetString( $path . 'metaKeys' ) );
		$section->set( 'metaAuthor', $this->xGetString( $path . 'metaAuthor' ) );
		$section->set( 'metaRobots', $this->xGetString( $path . 'metaRobots' ) );

		$fieldspath = $path . 'fields/*';
		$fields = $this->xGetChilds( $fieldspath );
		if ( ( $fields instanceof DOMNodeList ) && $fields->length ) {
			$section->save( $update, false );
		}
		else {
			$section->save( $update );
		}
		$sid = $section->get( 'id' );

		/* create the settings */
		$settings = [];

		/* if there are fields to create */
		if ( ( $fields instanceof DOMNodeList ) && $fields->length ) {
			$fids = $this->fields( $fields, $sid, $update );
			$settings[ 'entry' ][ 'name_field' ] = $fids[ $this->xGetString( $path . 'nameField' ) ];
//			$settings[ 'list' ][ 'entries_ordering' ] = $this->xGetString( $path . 'nameField' );
		}
//		else {
//			$settings[ 'list' ][ 'entries_ordering' ] = $this->xGetString( $path . 'nameField' );
//		}
		$settings[ 'section' ][ 'template' ] = $tpl;

		/* the options contain some settings; used in older templates (SobiCars) */
		$options = $this->xGetChilds( $path . 'options/*' );
		if ( ( $options instanceof DOMNodeList ) && $options->length ) {
			foreach ( $options as $option ) {
				$value = $option->nodeValue;
				if ( in_array( $option->nodeValue, [ 'true', 'false' ] ) ) {
					$value = $option->nodeValue == 'true';
				}
				$key = explode( '.', $option->getAttribute( 'attribute' ) );
				$settings[ trim( $key[ 0 ] ) ][ trim( $key[ 1 ] ) ] = $value;
			}
		}

		/* nice setting names; used in older templates (SobiCars, SobiSort 2.0) */
		$settings[ 'general' ][ 'top_menu' ] = $this->xGetString( $path . 'showTopMenu' ) == 'true';
		$settings[ 'list' ][ 'cat_desc' ] = $this->xGetString( $path . 'showCategoryDesc' ) == 'true';
		$settings[ 'list' ][ 'cat_meta' ] = $this->xGetString( $path . 'showCategoryMeta' ) == 'true';
		$settings[ 'list' ][ 'cat_full' ] = $this->xGetString( $path . 'showCategoryFull' ) == 'true';
		$settings[ 'list' ][ 'subcats' ] = $this->xGetString( $path . 'showCategorySubcats' ) == 'true';
		$settings[ 'list' ][ 'categories_in_line' ] = ( int ) $this->xGetString( $path . 'catsInLine' );
		$settings[ 'list' ][ 'entries_in_line' ] = ( int ) $this->xGetString( $path . 'entriesInLine' );
		$settings[ 'list' ][ 'entries_limit' ] = ( int ) $this->xGetString( $path . 'entriesOnPage' );
		$settings[ 'list' ][ 'entry_meta' ] = $this->xGetString( $path . 'showEntryMeta' ) == 'true';
		$settings[ 'list' ][ 'entry_cats' ] = $this->xGetString( $path . 'showEntryCategories' ) == 'true';

		/* read all available settings with their real names */
		$options = $this->xGetChilds( $path . 'settings/*' );

		/* the exceptions from these cSections; add here keys which should never be written or which have a special handling later */
		$exceptions = [ 'alphamenu' => [ 'primary_field', 'extra_fields_array' ],
		                'entry'     => [ 'name_field', 'maxCats' ],
		                'general'   => [ 'field_types_for_ordering' ],
		                'html'      => [ 'allowed_tags_array', 'allowed_attributes_array' ],
		                'list'      => [ 'entries_limit', 'entries_in_line', 'entries_ordering', 'categories_in_line', 'categories_ordering' ],
		                'meta'      => [ 'always_add_entryinput', 'always_add_search' ],
		                'ordering'  => [ 'fields_array' ],
		                'sordering' => [ 'fields_array' ],
		                'template'  => [ 'icon_fonts_arr' ] ];

		if ( ( $options instanceof DOMNodeList ) && $options->length ) {
			foreach ( $options as $option ) {
				$value = $option->getAttribute( 'value' );
				$written = false;
				if ( array_key_exists( $option->getAttribute( 'section' ), $exceptions ) && in_array( $option->getAttribute( 'key' ), $exceptions[ $option->getAttribute( 'section' ) ] ) ) {
					switch ( $option->getAttribute( 'key' ) ) {

						/* single fid */
						case 'primary_field':
							$value = $db
								->select( 'fid', 'spdb_field', [ 'section' => $sid, 'nid' => $value ] )
								->loadResult();
							break;

						/* fid array */
						case 'extra_fields_array':
							$value = json_decode( str_replace( "'", '"', $value ) );
							$value = $db
								->select( 'fid', 'spdb_field', [ 'section' => $sid, 'nid' => $value ] )
								->loadResultArray();
							SPFactory::config()->saveCfg( $option->getAttribute( 'key' ), $value, $option->getAttribute( 'section' ) );
							$written = true;
							break;

						case 'icon_fonts_arr':
						case 'fields_array':
						case 'allowed_tags_array':
						case 'allowed_attributes_array':
							$value = json_decode( str_replace( "'", '"', $value ) );
							SPFactory::config()->saveCfg( $option->getAttribute( 'key' ), $value, $option->getAttribute( 'section' ) );
							$written = true;
							break;
					}
				}
				elseif ( in_array( $value, [ 'true', 'false' ] ) ) {
					$value = $value == 'true';
				}

				if ( !$written ) {
					$settings[ $option->getAttribute( 'section' ) ][ $option->getAttribute( 'key' ) ] = $value;
				}
			}
		}

		/* prepare the settings and write them to the database */
		$values = [];
		foreach ( $settings as $cSection => $setting ) {
			foreach ( $setting as $k => $v ) {
				$values[] = [ 'sKey' => $k, 'sValue' => $v, 'section' => $sid, 'critical' => 0, 'cSection' => $cSection ];
			}
		}
		try {
			$db->insertArray( 'spdb_config', $values, true );
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
		}

		if ( !$update ) {
			/* create default permission */
			SPFactory::Controller( 'acl', true )
				->addNewRule( $name, [ $sid ], [ 'section.access.valid', 'section.search.*', 'category.access.valid', 'entry.access.valid', 'entry.add.own' ], [ 'visitor', 'registered' ], "Default permissions for the section $name"
				);

			/* create section applications */
			try {
				$installed = $db
					->select( [ 'type', 'pid' ], 'spdb_plugins', [ 'type' => [ 'application', 'payment' ] ] )
					->loadAssocList();
				foreach ( $installed as $index => $plugin ) {
					$installed[ $index ][ 'section' ] = $sid;
					$installed[ $index ][ 'enabled' ] = 0;
					$installed[ $index ][ 'position' ] = 0;
					$db->insertUpdate( 'spdb_plugin_section', $installed[ $index ] );
				}
			}
			catch ( Sobi\Error\Exception $x ) {
				Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
			}
		}

		/* create/update the categories */
		$categories = $this->xGetChilds( $path . 'categories/*' );
		if ( ( $categories instanceof DOMNodeList ) && $categories->length ) {
			$this->categories( $categories, $sid, $update );
		}
		Sobi::Trigger( 'After', 'SaveConfig', [ &$values ] );
	}

	/**
	 * Reads the categories (recursive).
	 *
	 * @param $categories
	 * @param $parent
	 * @param $update
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function categories( $categories, $parent, $update )
	{
		static $section = null;
		if ( !$section ) {
			$section = $parent; // the first time it is the section id
		}

		for ( $i = 0; $i < $categories->length; $i++ ) {
			$category = $categories->item( $i );
			if ( $category->nodeName == 'category' ) {
				$name = $this->getTextFromNode( $category, 'name' );
				$nid = $this->getTextFromNode( $category, 'alias' );
				if ( !$nid ) {
					$nid = StringUtils::Nid( $name );
				}
				$catid = Factory::Db()
					->select( 'id', 'spdb_object', [ 'oType' => 'category', 'nid' => $nid, 'parent>' => $section - 1 ] )
					->loadResult();

				/* If this didn't get a result, we try it with the name.
					Hey users, add the alias for save category update or use Imex! */
//				if ( !$catid && $update ) {
//					$catid = Factory::Db()
//						->select( 'id', 'spdb_object', [ 'oType' => 'category', 'name' => $name, 'parent>' => $section - 1 ] )
//						->loadResult();
//				}

				if ( $catid && $update ) {
					/* update category */
					$cat = SPFactory::Category( $catid );
				}
				else {
					/* create new category */
					$cat = SPFactory::Model( 'category' );
					if ( !$catid ) {
						$cat->set( 'nid', $nid );
					}
				}

				/* Base category data */
				$cat->set( 'name', $name );
				$cat->set( 'parent', $parent );
				$state = $this->getTextFromNode( $category, 'state' );
				if ( $state ) {
					$cat->set( 'state', $state );
				}
				else {
					$cat->set( 'state', 1 );
				}
				$cat->set( 'description', $this->getTextFromNode( $category, 'description' ) );
				$cat->set( 'introtext', $this->getTextFromNode( $category, 'introtext' ) );
				$icon = $this->getTextFromNode( $category, 'icon' );
				$cat->set( 'icon', $icon );
				$cat->set( 'showIcon', $this->getTextFromNode( $category, 'showIcon' ) );
				$cat->set( 'showIntrotext', $this->getTextFromNode( $category, 'showIntrotext' ) );
				$cat->set( 'parseDesc', $this->getTextFromNode( $category, 'parseDesc' ) );
				$cat->set( 'metaKeys', $this->getTextFromNode( $category, 'metaKeys' ) );
				$cat->set( 'metaDesc', $this->getTextFromNode( $category, 'metaDesc' ) );
				$cat->set( 'metaAuthor', $this->getTextFromNode( $category, 'metaAuthor' ) );
				$cat->set( 'metaRobots', $this->getTextFromNode( $category, 'metaRobots' ) );
				$cat->set( 'entryFields', json_decode( str_replace( "'", '"', $this->getTextFromNode( $category, 'entryFields' ) ), true ) );
				$allFields = $this->getTextFromNode( $category, 'allFields' );
				if ( $allFields ) {
					$cat->set( 'allFields', $allFields );
				}
				else {
					$cat->set( 'allFields', 1 );
				}

				$params = json_decode( str_replace( "'", '"', $this->getTextFromNode( $category, 'params' ) ), true );

				if ( strstr( $icon, '}' ) ) {
					$iconFont = json_decode( str_replace( "'", '"', $icon ), true );
					$cat->setParam( 'icon-font', $iconFont[ 'font' ] );
					$cat->setParam( 'icon-font-add-class', $params[ 'icon-font-add-class' ] );
				}

				/* Additional data */
				$options = $category->getElementsByTagName( 'option' );
				if ( ( $options instanceof DOMNodeList ) && $options->length ) {
					foreach ( $options as $option ) {
						$value = $option->nodeValue;
						if ( in_array( $option->nodeValue, [ 'true', 'false' ] ) ) {
							$value = $option->nodeValue == 'true';
						}
						$cat->set( $option->getAttribute( 'attribute' ), $value );
					}
				}

				/* save the category */
				$cat->save();   /* creates the nid (alias) for new categories */

				/* Handle fields */
//				$cid = $cat->get( 'id' );
//				$fields = $this->xdef->query( 'fields', $category );
//				if ( $fields && $fields->length ) {
//					$fieldsData = [];
//					if ( ( $fields instanceof DOMNodeList ) && $fields->length ) {
//						foreach ( $fields->item( 0 )->childNodes as $field ) {
//							if ( $field->nodeName == '#text' ) {
//								continue;
//							}
//							$fieldsData[ $field->nodeName ] = [];
//							$this->categoryFieldsData( $field, $fieldsData );
//						}
//					}
//					if ( is_array( $fieldsData ) && count( $fieldsData ) ) {
//						static $categoryFields = null;
//						if ( !( $categoryFields ) ) {
//							$categoryFields = $cat->loadFields( $section, true )
//								->getFields();
//						}
//						/** @var SPAdmField $field */
//						foreach ( $categoryFields as $field ) {
//							$nid = str_replace( '_', '-', $field->get( 'nid' ) );
//							if ( isset( $fieldsData[ $nid ] ) ) {
//
//								$field->loadData( $cid );
//								try {
//									$field->loadType(); // for some fields the type is not known ??
//									$field->setFieldData( $fieldsData[ $nid ] );
//								}
//								catch ( SPException $x ) {
//									switch ( $field->get( 'fieldType' ) ) {
//										case 'url':
//										case 'email':
//										case 'button':
//											$value = json_decode( str_replace( "'", '"', $fieldsData[ $nid ] ) );
//											$value = SPConfig::serialize( $value );
//											$field->setRawData( $value );
//											break;
//										case 'chbxgroup':
//										case 'multiselect':
//											$value = json_decode( str_replace( "'", '"', $fieldsData[ $nid ] ) );
//											$field->setRawData( $value );
//											break;
//										default:
//											$field->setRawData( $fieldsData[ $nid ] );
//									}
//
//									$a = is_array( $fieldsData[ $nid ] ) ? '[]' : null;
//									/** not really happy about this solution */
//									Input::Set( $field->get( 'nid' ) . $a, $fieldsData[ $nid ], 'post' );
//									$field->saveData( $cat, 'post' );
//								}
//							}
//						}
//					}
//				}

				/* Handle sub categories */
				$childs = $this->xdef->query( 'childs/category', $category );
				if ( ( $childs instanceof DOMNodeList ) && $childs->length ) {
					$this->categories( $childs, $cat->get( 'id' ), $update );
				}
			}
		}
	}

	/**
	 * Reads the category fields data.
	 *
	 * @param (DOMElement) $field
	 * @param $data
	 */
	protected function categoryFieldsData( $field, &$data )
	{
		if ( $field->childNodes->length ) {
			foreach ( $field->childNodes as $node ) {
				if ( $node->nodeName == '#text' ) {
					continue;
				}
				$data[ $field->nodeName ][ $node->nodeName ] = [];
				$this->categoryFieldsData( $node, $data[ $field->nodeName ] );
				if ( is_array( count( $data[ $field->nodeName ][ $node->nodeName ] ) ) && !( count( $data[ $field->nodeName ][ $node->nodeName ] ) ) ) {
					$data[ $field->nodeName ][ $node->nodeName ] = $node->nodeValue;
				}
			}
		}
		if ( !count( $data[ $field->nodeName ] ) ) {
			$data[ $field->nodeName ] = $node->nodeValue;
		}
	}

	/**
	 * Reads the fields and create/update them.
	 *
	 * @param $fields
	 * @param $sid
	 * @param $update
	 *
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function fields( $fields, $sid, $update )
	{
		$position = 0;
		$fids = [];
		foreach ( $fields as $field ) {
			$specificSetting = [];
			if ( $field->nodeName == 'field' ) {
				$position++;
				$attr = [];
				$attr[ 'editLimit' ] = -1;
				$ftype = $this->getTextFromNode( $field, 'type' );
				$nid = $this->getTextFromNode( $field, 'name' );

				$fid = Factory::Db()
					->select( 'fid', 'spdb_field', [ 'section' => $sid, 'nid' => $nid ] )
					->loadResult();

				if ( $fid && $update ) {
					/* update an existing field */
					/** @var \SPField $f */
					$f = SPFactory::Model( 'field', true );
					$f->extend( $this->loadField( $fid ) );
				}
				else {
					/* create a new field */
					/** @var \SPAdmField $f */
					$f =& SPFactory::Instance( 'models.adm.field' );
					$f->loadType( $ftype );
				}

				$options = $field->getElementsByTagName( 'option' );
				if ( ( $options instanceof DOMNodeList ) && $options->length ) {
					foreach ( $options as $option ) {
						$value = trim( $option->nodeValue );
						if ( in_array( $option->nodeValue, [ 'true', 'false' ] ) ) {
							$value = $option->nodeValue == 'true';
						}
						$attr[ $option->getAttribute( 'attribute' ) ] = $value;
					}
				}

				$specials = $field->getElementsByTagName( 'specific' );
				if ( ( $specials instanceof DOMNodeList ) && $specials->length ) {
					$index = 0;
					foreach ( $specials->item( 0 )->childNodes as $setting ) {
						if ( $setting->nodeName == 'data' ) {
							$index++;
							foreach ( $setting->attributes as $attribute ) {
								$specificSetting[ $index ][ 'attributes' ][ $attribute->nodeName ] = $attribute->nodeValue;
							}
							$specificSetting[ $index ][ 'value' ] = trim( $setting->nodeValue );
						}
					}
				}
				$options = $field->getElementsByTagName( 'value' );

				if ( ( $options instanceof DOMNodeList ) && $options->length ) {
					$values = $addOptions = [];
					foreach ( $options as $option ) {
						/* handles standard options in select/checkbox group etc */
						if ( $option->parentNode->getAttribute( 'attribute' ) == 'fieldOptions' ) {
							$id = strlen( $option->getAttribute( 'name' ) ) ? $option->getAttribute( 'name' ) : 0;
							$parent = strlen( $option->getAttribute( 'parent' ) ) ? $option->getAttribute( 'parent' ) : C::ES;
							if ( $id ) {
								$values[] = [ 'id' => $id, 'name' => $option->nodeValue, 'parent' => $parent ];
							}
							else {
								$addOptions[ $option->parentNode->getAttribute( 'attribute' ) ][] = $option->nodeValue;
							}
						}
						/* handles multiple selected options in field parameters */
						else {
							if ( strlen( $option->getAttribute( 'name' ) ) ) {
								$attr[ $option->parentNode->getAttribute( 'attribute' ) ][ $option->getAttribute( 'name' ) ] = $option->nodeValue;
							}
							else {
								$attr[ $option->parentNode->getAttribute( 'attribute' ) ][] = $option->nodeValue;
							}
						}
					}
					if ( is_array( $addOptions ) && count( $addOptions ) ) {
						foreach ( $addOptions as $name => $options ) {
							$values[ $name ] = $options;
						}
					}
					/* we need the exact array format as the field expects, so we have to have numeric index */
					if ( is_array( $values ) && count( $values ) ) {
						foreach ( $values as $value ) {
							$attr[ 'options' ][] = $value;
						}
					}
				}

				$attr[ 'nid' ] = $nid;
				$attr[ 'name' ] = $this->getTextFromNode( $field, 'label' );
				$attr[ 'required' ] = $this->getTextFromNode( $field, 'required' ) == 'true';
				$attr[ 'showIn' ] = $this->getTextFromNode( $field, 'showIn' );
				$attr[ 'adminField' ] = $this->getTextFromNode( $field, 'adminField' );
				$attr[ 'type' ] = $ftype;
				$attr[ 'fieldType' ] = $ftype;
				$attr[ 'section' ] = $sid;
				$attr[ 'position' ] = $attr[ 'position' ] ?? $position;
				$attr[ 'enabled' ] = $attr[ 'enabled' ] ?? true;
				$attr[ 'editable' ] = $attr[ 'editable' ] ?? true;
				$attr[ 'metaKeys' ] = $this->getTextFromNode( $field, 'metaKeys' );
				$attr[ 'metaAuthor' ] = $this->getTextFromNode( $field, 'metaAuthor' );
				$attr[ 'metaRobots' ] = $this->getTextFromNode( $field, 'metaRobots' );
				$attr[ 'metaDesc' ] = $this->getTextFromNode( $field, 'metaDesc' );

				/* let's create/update the field */
				if ( !$fid || !$update ) {
					$f->saveNew( $attr );
				}
				else {
					$f->save( $attr );
				}
				if ( is_array( $specificSetting ) && count( $specificSetting ) ) {
					try {
						$f->loadType();
						if ( $f->get( '_type' ) && method_exists( $f->get( '_type' ), 'importField' ) ) {
							$f->importField( $specificSetting, $attr[ 'nid' ] );
						}
					}
					catch ( SPException $x ) {
					}
				}
				$f->save( $attr );
				$fids[ $attr[ 'nid' ] ] = $f->get( 'id' );
			}
		}

		return $fids;
	}

	/**
	 * @param $fid
	 *
	 * @return \stdClass
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	private function loadField( $fid )
	{
		$db = Factory::Db();
		try {
			$field = $db
				->select( '*', $db->join( [
					[ 'table' => 'spdb_field', 'as' => 'sField', 'key' => 'fieldType' ],
					[ 'table' => 'spdb_field_types', 'as' => 'sType', 'key' => 'tid' ] ] ),
					[ 'fid' => $fid ] )
				->loadObject();
		}
		catch ( Sobi\Error\Exception $x ) {
			Sobi::Error( $this->name(), SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::WARNING, 500, __LINE__, __FILE__ );
		}

		return $field;
	}

	/**
	 * @param $el
	 * @param $node
	 *
	 * @return string
	 */
	private function getTextFromNode( $el, $node ): string
	{
		if ( $el->getElementsByTagName( $node )->length ) {
			return trim( $el->getElementsByTagName( $node )->item( 0 )->nodeValue );
		}
		else {
			return C::ES;
		}
	}
}
