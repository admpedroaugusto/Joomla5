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
 * @created 02-Jul-2010 by Radek Suski
 * @modified 27 November 2023 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadClass( 'services.installers.installer' );

use Sobi\C;
use Sobi\FileSystem\File;
use Sobi\FileSystem\FileSystem;
use Sobi\FileSystem\Directory;
use Sobi\Lib\Factory;
use Sobi\Error\Exception;

/**
 * Class SPAppInstaller
 */
class SPAppInstaller extends SPInstaller
{
	/**
	 * @return string
	 * @throws SPException
	 * @throws \Sobi\Error\Exception|\DOMException
	 */
	public function install()
	{
		$log = [];
		$type = $this->xGetString( 'type' ) && strlen( $this->xGetString( 'type' ) ) ? $this->xGetString( 'type' ) : ( $this->xGetString( 'fieldType' ) ? 'field' : C::ES );
		$id = $this->xGetString( 'id' );

		if ( $this->installed( $id, $type ) ) {
			Factory::Db()->delete( 'spdb_plugins', [ 'pid' => $id, 'type' => $type ] );
		}
		Sobi::Trigger( 'Before', 'InstallPlugin', [ $id ] );
		$requirements = $this->xGetChilds( 'requirements/*' );
		if ( $requirements instanceof DOMNodeList ) {
			/** @var SPRequirements $reqCheck */
			$reqCheck =& SPFactory::Instance( 'services.installers.requirements' );
			$reqCheck->check( $requirements );
		}

		$permissions = $this->xGetChilds( 'permissions/*' );
		if ( $permissions instanceof DOMNodeList ) {

			/** @var SPAclCtrl $permsCtrl */
			$permsCtrl =& SPFactory::Instance( 'ctrl.adm.acl' );
			for ( $i = 0; $i < $permissions->length; $i++ ) {
				$perm = explode( '.', $permissions->item( $i )->nodeValue );
				$permsCtrl->addPermission( $perm[ 0 ], $perm[ 1 ], $perm[ 2 ] );
				$log[ 'permissions' ][] = $permissions->item( $i )->nodeValue;
			}
		}

		$actions = $this->xGetChilds( 'actions/*' );
		if ( $actions instanceof DOMNodeList ) {
			$log[ 'actions' ] = $this->actions( $actions, $id );
		}

		$dir = SPLoader::dirPath( 'etc/installed/' . $type . 's', 'front', false );
		if ( !FileSystem::Exists( $dir ) ) {
			FileSystem::Mkdir( $dir );
			FileSystem::Mkdir( "$dir/$id/backup" );
		}

		$files = $this->xGetChilds( 'files' );
		$date = str_replace( [ ':', ' ' ], [ '-', '_' ], SPFactory::config()->date( time() ) );
		if ( ( $files instanceof DOMNodeList ) && $files->length ) {
			$log[ 'files' ] = $this->files( $files, $id, "$dir/$id/backup/$date" );
			if ( is_array( $log[ 'files' ] ) && count( $log[ 'files' ] ) ) {
				if ( $log[ 'files' ][ 'created' ] && count( $log[ 'files' ][ 'created' ] ) ) {
					foreach ( $log[ 'files' ][ 'created' ] as $i => $f ) {
						$log[ 'files' ][ 'created' ][ $i ] = str_replace( SOBI_ROOT, C::ES, $f );
					}
				}
				if ( $log[ 'files' ][ 'modified' ] && count( $log[ 'files' ][ 'modified' ] ) ) {
					foreach ( $log[ 'files' ][ 'modified' ] as $i => $f ) {
						$log[ 'files' ][ 'modified' ][ $i ] = str_replace( SOBI_ROOT, C::ES, $f );
					}
				}
			}
		}

		$languages = $this->xGetChilds( 'language' );
		if ( $languages->length ) {
			$langFiles = [];
			foreach ( $languages as $language ) {
				$folder = $language->attributes->getNamedItem( 'folder' ) ? $language->attributes->getNamedItem( 'folder' )->nodeValue : null;
				foreach ( $language->childNodes as $file ) {
					$adm = false;
					if ( strstr( $file->nodeName, '#' ) ) {
						continue;
					}
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
			}
			$log[ 'files' ][ 'created' ] = array_merge( $log[ 'files' ][ 'created' ], Factory::ApplicationInstaller()->installLanguage( $langFiles, false ) );
		}

		$sql = $this->xGetString( 'sql' );
		if ( $sql && FileSystem::Exists( "$this->root/$sql" ) ) {
			try {
				$log[ 'sql' ] = Factory::Db()->loadFile( "$this->root/$sql", 'spdb', 'sobipro' );
			}
			catch ( Exception $x ) {
				Sobi::Error( 'installer', SPLang::e( 'CANNOT_EXECUTE_QUERIES', $x->getMessage() ), C::WARNING, 500, __LINE__, __FILE__ );

				return false;
			}
		}
		if ( !( $this->xGetString( 'type' ) && strlen( $this->xGetString( 'type' ) ) ) && strlen( $this->xGetString( 'fieldType' ) ) ) {
			$log[ 'field' ] = $this->field();
		}

		$exec = $this->xGetString( 'exec' );
		if ( $exec && FileSystem::Exists( "$this->root/$exec" ) ) {
			include_once "$this->root/$exec";
		}

		$this->plugin( $id, $type );
		$this->log( $log );
		$this->definition->formatOutput = true;
		$this->definition->preserveWhiteSpace = false;
		$this->definition->normalizeDocument();
		$path = "$dir/$id.xml";
		$file = new File( $path );
		//		$file->content( $this->definition->saveXML() );
		// why the hell DOM cannot format it right. Try this
		$outXML = $this->definition->saveXML();
		$xml = new DOMDocument();
		$xml->preserveWhiteSpace = false;
		$xml->formatOutput = true;
		$xml->loadXML( $outXML );
		$file->content( $xml->saveXML() );
		$file->save();

		switch ( $type ) {
			case 'SobiProApp':
				$type = 'plugin';
			default:
			case 'update':
			case 'plugin':
			case 'field':
				$t = Sobi::Txt( 'EX.' . strtoupper( $type ) . '_TYPE' );
				break;
			case 'payment':
				$t = Sobi::Txt( 'EX.PAYMENT_METHOD_TYPE' );
				break;
		}
		Sobi::Trigger( 'After', 'InstallPlugin', [ $id ] );
		$dir = new Directory( SPLoader::dirPath( 'tmp.install' ) );
		$dir->deleteFiles();

		$version = $this->xGetString( 'version' );
		$tag = $this->xGetString( 'tag' );
		$version = $tag ? $version . ' ' . $tag : $version;

		$params = [ 'name' => $this->xGetString( 'name' ) . ' ' . $version, 'type' => $t ];
		SPFactory::history()->logAction( SPC::LOG_INSTALL, 0, 0,
			'application',
			C::ES,
			$params
		);

		return Sobi::Txt( 'EX.EXTENSION_HAS_BEEN_INSTALLED', $params );
	}

	/**
	 * @param string $msg
	 */
	protected function _d( $msg )
	{
		if ( Sobi::Cfg( 'debug', false ) ) {
			SPConfig::debOut( $msg, false, false, true );
		}
	}

	/**
	 * @param $log
	 *
	 * @throws \DOMException
	 */
	protected function log( $log )
	{
		if ( is_array( $log ) && count( $log ) ) {
			$install = $this->definition->createElement( 'installLog' );
			foreach ( $log as $section => $values ) {
				switch ( $section ) {
					case 'files':
						if ( isset( $values[ 'modified' ] ) && count( $values[ 'modified' ] ) ) {
							$modifiedFiles = $this->definition->createElement( 'modified' );
							foreach ( $values[ 'modified' ] as $file ) {
								if ( is_array( $file ) && count( $file ) ) {
									$filesLog = $this->definition->createElement( 'file' );
									$fixedFile = FileSystem::FixPath( $file[ 'file' ] );
									$this->_d( sprintf( 'Line %s: File %s is modified', __LINE__, $fixedFile ) );

									$filesLog->appendChild( $this->definition->createElement( 'path', $fixedFile ) );
									$filesLog->appendChild( $this->definition->createElement( 'size', $file[ 'size' ] ) );
									$filesLog->appendChild( $this->definition->createElement( 'checksum', $file[ 'checksum' ] ) );
									if ( $file[ 'backup' ] ) {
										$filesLog->appendChild( $this->definition->createElement( 'backup', FileSystem::FixPath( $file[ 'backup' ] ) ) );
									}
									$modifiedFiles->appendChild( $filesLog );
								}
							}
							$install->appendChild( $modifiedFiles );
							if ( isset( $values[ 'created' ] ) && count( $values[ 'created' ] ) ) {
								$createdFiles = $this->definition->createElement( 'files' );
								foreach ( $values[ 'created' ] as $file ) {
									$fixedFile = FileSystem::FixPath( $file );
									$this->_d( sprintf( 'Line %s: File %s is created', __LINE__, $fixedFile ) );
									$createdFiles->appendChild( $this->definition->createElement( 'file', $fixedFile ) );
								}
								$install->appendChild( $createdFiles );
							}
						}
						else {
							if ( is_array( $values ) && count( $values ) ) {
								$files = $this->definition->createElement( 'files' );
								foreach ( $values as $file ) {
									$fixedFile = FileSystem::FixPath( $file );
									$this->_d( sprintf( 'Line %s: File %s is created', __LINE__, $fixedFile ) );
									$files->appendChild( $this->definition->createElement( 'file', $fixedFile ) );
								}
								$install->appendChild( $files );
							}
						}
						break;
					case 'actions':
					{
						$actions = $this->definition->createElement( 'actions' );
						if ( is_array( $values ) && count( $values ) ) {
							foreach ( $values as $action ) {
								$actions->appendChild( $this->definition->createElement( 'action', $action ) );
							}
							$install->appendChild( $actions );
						}
						break;
					}
					case 'field':
					{
						$install->appendChild( $this->definition->createElement( 'field', $values ) );
						break;
					}
					case 'permissions':
					{
						$permission = $this->definition->createElement( 'permissions' );
						if ( is_array( $values ) && count( $values ) ) {
							foreach ( $values as $action ) {
								$permission->appendChild( $this->definition->createElement( 'permission', $action ) );
							}
							$install->appendChild( $permission );
						}
						break;
					}
					case 'sql':
					{
						if ( is_array( $values ) && count( $values ) ) {
							$queries = $this->definition->createElement( 'sql' );
							/* first find all 'create table' */
							$tables = [];
							foreach ( $values as $query ) {
								if ( stristr( $query, 'create table' ) ) {
									preg_match( '/spdb_[a-z0-9\-_]*/i', $query, $table );
									if ( count( $table ) ) {
										$tables[] = $table[ 0 ];
									}
								}
							}
							if ( is_array( $values ) && count( $tables ) ) {
								$tbls = $this->definition->createElement( 'tables' );
								foreach ( $tables as $table ) {
									$tbls->appendChild( $this->definition->createElement( 'table', $table ) );
								}
								$queries->appendChild( $tbls );
							}
							$inserts = [];
							/* second loop to find all 'insert table' */
							foreach ( $values as $query ) {
								if ( stristr( $query, 'insert into' ) ) {
									preg_match( '/spdb_[a-z0-9\-_]*/i', $query, $match );
									$table = $match[ 0 ] ?? null;
									/* will be dropped anyway */
									if ( in_array( $table, $tables ) || !( $table ) ) {
										continue;
									}
									preg_match( '/\([^\)]*\)/i', $query, $match );
									$cols = isset( $match[ 0 ] ) ? str_ireplace( [ '`', '(', ')' ], [ C::ES, C::ES, C::ES ], $match[ 0 ] ) : C::ES;
									$cols = explode( ',', $cols );
									preg_match( '/VALUES.*\)/i', $query, $match );
									$values = isset( $match[ 0 ] ) ? str_ireplace( [ 'VALUES', '(', ')' ], [ C::ES, C::ES, C::ES ], $match[ 0 ] ) : C::ES;
									$values = explode( ',', $values );
									$cc = is_array( $cols ) ? count( $cols ) : 0;
									$v = [];
									if ( $cc ) {
										for ( $i = 0; $i < $cc; $i++ ) {
											$v[ trim( str_replace( "'", C::ES, $cols[ $i ] ) ) ] = trim( str_ireplace( "NULL", C::ES, trim( trim( $values[ $i ] ), "\x22\x27" ) ) );
										}
									}
									else {
										foreach ( $values as $value ) {
											$v[] = trim( str_ireplace( "NULL", C::ES, trim( trim( $value ), "\x22\x27" ) ) );
										}
									}
									$inserts[ $table ][] = $v;
								}
							}
							if ( is_array( $inserts ) && count( $inserts ) ) {
								$ins = $this->definition->createElement( 'queries' );
								foreach ( $inserts as $table => $cols ) {
									foreach ( $cols as $values ) {
										$query = $this->definition->createElement( 'insert' );
										$attr = $this->definition->createAttribute( 'table' );
										$attr->appendChild( $this->definition->createTextNode( $table ) );
										$query->appendChild( $attr );
										foreach ( $values as $col => $value ) {
											if ( is_numeric( $col ) ) {
												$col = 'value';
											}
											if ( strlen( $value ) > 50 ) {
												$node = $query->appendChild( $this->definition->createElement( $col ) );
												$node->appendChild( $this->definition->createCDATASection( $value ) );
											}
											else {
												$query->appendChild( $this->definition->createElement( $col, $value ) );
											}
										}
										$ins->appendChild( $query );
									}
								}
								$queries->appendChild( $ins );
							}
							$install->appendChild( $queries );
						}
						break;
					}
				}
			}
			$root = $this->definition->getElementsByTagName( 'SobiProApp' )->item( 0 );
			$root->appendChild( $install );
			$root->normalize();
			$this->definition->appendChild( $root );
		}
	}

	/**
	 * @param $action
	 * @param string $id
	 *
	 * @return array
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function actions( $action, $id ): array
	{
		$adds = $actions = [];
		for ( $i = 0; $i < $action->length; $i++ ) {
			$actions[] = $action->item( $i )->nodeValue;
			$adds[ $i ] = [ 'pid' => $id, 'onAction' => $action->item( $i )->nodeValue ];
		}
		if ( is_array( $adds ) && count( $adds ) ) {
			try {
				Factory::Db()->insertArray( 'spdb_plugin_task', $adds, false, true );
			}
			catch ( Exception $x ) {
				throw new SPException( SPLang::e( 'CANNOT_INSTALL_PLUGIN_DB_ERR', $x->getMessage() ) );
			}
		}

		return $actions;
	}

	/**
	 * @param string $id
	 * @param string $type
	 *
	 * @return bool
	 */
	protected function installed( $id, $type ): bool
	{
		$res = 0;
		try {
			$res = Factory::Db()
				->select( 'COUNT( pid )', 'spdb_plugins', [ 'pid' => $id, 'type' => $type ] )
				->loadResult();
		}
		catch ( Exception $x ) {
		}

		return $res > 0;
	}

	/**
	 * @param $id
	 * @param $type
	 *
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function plugin( $id, $type )
	{
		if ( $this->xGetString( 'type' ) != 'update' ) {
			try {
				$version = $this->xGetString( 'version' );
				$tag = $this->xGetString( 'tag' );
				$version = $tag ? $version . ' ' . $tag : $version;

				Factory::Db()->insertUpdate( 'spdb_plugins', [ 'pid'         => $id,
				                                               'name'        => $this->xGetString( 'name' ),
				                                               'version'     => $version,
				                                               'description' => $this->xGetString( 'description' ),
				                                               'author'      => $this->xGetString( 'authorName' ),
				                                               'authorURL'   => $this->xGetString( 'authorUrl' ),
				                                               'authorMail'  => $this->xGetString( 'authorEmail' ),
				                                               'enabled'     => 1,
				                                               'type'        => $type,
				                                               'depend'      => null ]
				);
				if ( $this->xGetString( 'type' ) == 'payment' ) {
					Factory::Db()->insert( 'spdb_plugin_task', [ 'pid' => $id, 'onAction' => 'PaymentMethodView', 'type' => 'payment' ] );
				}
			}
			catch ( Exception $x ) {
				throw new SPException( SPLang::e( 'CANNOT_INSTALL_PLUGIN_DB_ERR', $x->getMessage() ) );
			}
		}
	}

	/**
	 * @return string
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	protected function field()
	{
		$node = $this->xGetChilds( 'fieldType' )->item( 0 );
		$tid = $node->attributes->getNamedItem( 'typeId' )->nodeValue;
		$tGroup = $node->attributes->getNamedItem( 'fieldGroup' )->nodeValue;
		$fType = $node->nodeValue;
		try {
			Factory::Db()->insert( 'spdb_field_types', [ 'tid' => $tid, 'fType' => $fType, 'tGroup' => $tGroup, 'fPos' => 0 ], true );
		}
		catch ( Exception $x ) {
			throw new SPException( SPLang::e( 'CANNOT_INSTALL_FIELD_DB_ERR', $x->getMessage() ) );
		}

		return $tid;
	}

	/**
	 * @param $folders
	 * @param $eid
	 * @param $backup
	 *
	 * @return array[]
	 * @throws \SPException
	 */
	protected function files( $folders, $eid, $backup )
	{
		$log = [ 'created' => [], 'modified' => [] ];
		foreach ( $folders as $folder ) {
			$target = $folder->attributes->getNamedItem( 'path' )->nodeValue;
			if ( strstr( $target, 'templates:default' ) ) {
				$target = str_replace( 'templates:default', 'templates:' . SPC::DEFAULT_TEMPLATE, $target );
			}
			$basePath = explode( ':', $target );
			$basePath = $basePath[ 0 ];
			$target = str_replace( $basePath . ':', C::ES, $target );
			$target = $this->joinPath( $basePath, $target, $eid );
			if ( !FileSystem::Exists( $target ) ) {
				if ( FileSystem::Mkdir( $target ) ) {
					$log[ 'created' ][] = $target;
				}
			}
			foreach ( $folder->childNodes as $child ) {
				// the path within the node value is the "from" path and has to be removed from the target path
				$remove = null;
				if ( strstr( $child->nodeValue, '/' ) ) {
					$remove = explode( '/', $child->nodeValue );
					array_pop( $remove );
					if ( !is_string( $remove ) && count( $remove ) ) {
						$remove = implode( '/', $remove );
					}
					else {
						$remove = null;
					}
				}
				switch ( $child->nodeName ) {
					case 'folder':
					{
						/* the directory iterator is a nice thing, but it needs a lot of time and memory so let's simplify it */
						$files = [];
						$this->travelDir( $this->root . '/' . $child->nodeValue, $files );
						if ( is_array( $files ) && count( $files ) ) {
							$this->_d( sprintf( 'List %s', print_r( $files, true ) ) );
							foreach ( $files as $file ) {
								$this->_d( sprintf( 'Parsing %s', $file ) );
								$tfile = FileSystem::FixPath( str_replace( $this->root, C::ES, $file ) );
								$bPath = FileSystem::FixPath( $backup . $tfile );
								if ( $remove && strstr( $tfile, $remove ) && strpos( $tfile, $remove ) < 2 ) {
									$tfile = FileSystem::FixPath( str_replace( $remove, C::ES, $tfile ) );
								}
								$targetFile = FileSystem::FixPath( $target . $tfile );
								if ( FileSystem::Exists( $targetFile ) ) {
									FileSystem::Copy( $targetFile, $bPath );
								}
								if ( FileSystem::Copy( $file, $targetFile ) ) {
									// if this file existed already, do not add it to the log
									if ( !FileSystem::Exists( $bPath ) ) {
										$log[ 'created' ][] = $targetFile;
										$this->_d( sprintf( 'File %s does not exist', $targetFile ) );
									}
									else {
										$this->_d( sprintf( 'File %s exists and will be backed up', $targetFile ) );
										$log[ 'modified' ][] = [ 'file' => $targetFile, 'size' => filesize( $targetFile ), 'checksum' => md5_file( $targetFile ), 'backup' => $bPath ];
									}
								}
								else {
									$this->_d( sprintf( 'Cannot copy %s to %s', $file, $targetFile ) );
								}
							}
						}
						break;
					}
					case 'file':
					{
						$bPath = C::ES;
						$tfile = $child->nodeValue;
						// remove the installation path
						if ( $remove && strstr( $child->nodeValue, $remove ) && strpos( $child->nodeValue, $remove ) < 2 ) {
							$tfile = FileSystem::FixPath( str_replace( $remove, C::ES, $child->nodeValue ) );
						}
						$targetFile = FileSystem::FixPath( $target . $tfile );
						// if modifying file - backup file
						if ( FileSystem::Exists( $targetFile ) ) {
							$bPath = FileSystem::FixPath( "$backup/$child->nodeValue" );
							FileSystem::Copy( $targetFile, $bPath );
						}
						if ( FileSystem::Copy( "$this->root/$child->nodeValue", $targetFile ) ) {
							// if this file existed already, do not add it to the log
							if ( $bPath && !FileSystem::Exists( $bPath ) ) {
								$log[ 'created' ][] = $targetFile;
								$this->_d( sprintf( 'File %s does not exist', $targetFile ) );
							}
							else {
								// if modifying file - store the current data so when we are going to restore it, we can be sure we are not overwriting some file
								$log[ 'modified' ][] = [ 'file' => $targetFile, 'size' => filesize( $targetFile ), 'checksum' => md5_file( $targetFile ), 'backup' => $bPath ];
								/** 1.1 changes - we don't want to restore the backed up files because it causes much more problems as it is worth */
								$log[ 'created' ][] = $targetFile;
								$this->_d( sprintf( 'File %s exists and will be backed up', $targetFile ) );
							}
						}
						break;
					}
				}
			}
		}

		return $log;
	}

	/**
	 * @param string $dir
	 * @param array $files
	 *
	 * @throws \SPException
	 */
	protected function travelDir( string $dir, array &$files )
	{
		if ( FileSystem::Exists( $dir ) ) {
			$scan = scandir( $dir );
			$this->_d( sprintf( 'Parsing %s directory', $dir ) );
			if ( is_array( $scan ) && count( $scan ) ) {
				foreach ( $scan as $value ) {
					$this->_d( sprintf( 'Children %s in directory %s', $value, $dir ) );
					if ( $value != '.' && $value != '..' ) {
						if ( is_dir( "$dir/$value" ) ) {
							$this->travelDir( "$dir/$value", $files );
						}
						else {
							$this->_d( sprintf( 'Adding file %s', "$dir/$value" ) );
							$files[] = "$dir/$value";
						}
					}
				}
			}
		}
	}

	/**
	 * @param string $base
	 * @param string $path
	 * @param string $eid
	 *
	 * @return string
	 */
	protected function joinPath( $base, $path, $eid )
	{
		switch ( $base ) {
			case 'home':
				$path = FileSystem::FixPath( SPLoader::newDir( "opt/plugins/$eid.$path" ) . '/' );
				break;
			case 'fields':
				$path = FileSystem::FixPath( SPLoader::newDir( "opt/fields/$path" ) . '/' );
				break;
			case 'templates':
				$path = FileSystem::FixPath( SPLoader::newDir( "usr/templates/$path" ) . '/' );
				break;
			case 'storage':
				$path = FileSystem::FixPath( SPLoader::newDir( "usr/templates/storage/$path" ) . '/' );
				break;
			case 'config':
				$path = FileSystem::FixPath( SPLoader::newDir( "etc/$path" ) . '/' );
				break;
			case 'lib':
			case 'Library':
			case 'ctrl':
			case 'models':
			case 'media':
			case 'views':
			case 'js':
			case 'adm':
			case 'front':
			case 'css':
			case 'less':
			case 'linc':
				$path = FileSystem::FixPath( SPLoader::newDir( FileSystem::FixPath( $path ), $base ) . '/' );
				break;
			case 'img':
				$path = FileSystem::FixPath( SPLoader::newDir( Sobi::Cfg( 'images_folder' ) . '/' . $path, 'root' ) );
				break;
		}

		return $path;
	}

	/**
	 * @param $changes
	 *
	 * @deprecated since 2.0
	 */
	protected function revert( $changes )
	{
//		$files = [];
//		foreach ( $changes as $file ) {
//			if ( $file->hasChildNodes() ) {
//				$f = [];
//				foreach ( $file->childNodes as $node ) {
//					if ( $node->nodeName != '#text' ) {
//						$f[ $node->nodeName ] = $node->nodeValue;
//					}
//				}
//				$files[] = $f;
//			}
//		}
//		if ( count( $files ) ) {
//			foreach ( $files as $file ) {
//				if ( strstr( $file[ 'path' ], '/opt/' ) || strstr( $file[ 'path' ], '/field/' ) ) {
//					FileSystem::Delete( SOBI_ROOT . $file[ 'path' ] );
//					continue;
//				}
//				if ( FileSystem::Exists( SOBI_ROOT . $file[ 'path' ] ) ) {
//					if ( md5_file( SOBI_ROOT . $file[ 'path' ] ) ) {
//						if ( FileSystem::Exists( SOBI_ROOT . $file[ 'backup' ] ) ) {
//							if ( !( FileSystem::Copy( SOBI_ROOT . $file[ 'backup' ], SOBI_ROOT . $file[ 'path' ] ) ) ) {
//								Sobi::Error( 'installer', SPLang::e( 'Cannot restore file. Cannot copy from "%s" to "%s".', $file[ 'path' ], $file[ 'backup' ] ), C::WARNING, 0 );
//							}
//						}
//						else {
//							Sobi::Error( 'installer', SPLang::e( 'Cannot restore file "%s". Backup file does not exist.', $file[ 'path' ] ), C::WARNING, 0 );
//						}
//					}
//					else {
//						Sobi::Error( 'installer', SPLang::e( 'Cannot restore file "%s". File has been modified since the installation.', $file[ 'path' ] ), C::WARNING, 0 );
//					}
//				}
//				else {
//					Sobi::Error( 'installer', SPLang::e( 'Cannot restore file "%s". File does not exist.', $file[ 'path' ] ), C::WARNING, 0 );
//				}
//			}
//		}
	}

	/**
	 * @return string
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function remove()
	{
		$pid = $this->xGetString( 'id' );
		$function = $this->xGetString( 'uninstall' );
		if ( $function ) {
			$obj = explode( ':', $function );
			$function = $obj[ 1 ];
			$obj = $obj[ 0 ];

			return SPFactory::Instance( $obj )->$function( $this->definition );
		}
		$permissions = $this->xGetChilds( 'installLog/permissions/*' );
		if ( $permissions && ( $permissions instanceof DOMNodeList ) ) {
			$permsCtrl =& SPFactory::Instance( 'ctrl.adm.acl' );
			for ( $i = 0; $i < $permissions->length; $i++ ) {
				$perm = explode( '.', $permissions->item( $i )->nodeValue );
				$permsCtrl->removePermission( $perm[ 0 ], $perm[ 1 ], $perm[ 2 ] );
			}
		}

		$files = $this->xGetChilds( 'installLog/files/*' );
		if ( $files && ( $files instanceof DOMNodeList ) ) {
			for ( $i = 0; $i < $files->length; $i++ ) {
				$file = $files->item( $i )->nodeValue;
				if ( !strstr( $file, SOBI_ROOT ) ) {
					$file = FileSystem::FixPath( SOBI_ROOT . "/$file" );
				}
				if ( FileSystem::Exists( $file ) ) {
					FileSystem::Delete( $file );
				}
			}
		}
		$files = $this->xGetChilds( 'installLog/modified/*' );
		if ( $files && ( $files instanceof DOMNodeList ) ) {
			for ( $i = 0; $i < $files->length; $i++ ) {
				$file = $files->item( $i )->getElementsByTagName( 'path' )->item( 0 )->nodeValue;
				if ( !strstr( $file, SOBI_ROOT ) ) {
					$file = FileSystem::FixPath( SOBI_ROOT . "/$file" );
				}
				if ( FileSystem::Exists( $file ) ) {
					FileSystem::Delete( $file );
				}
			}
		}

		$actions = $this->xGetChilds( 'installLog/actions/*' );
		if ( $actions && ( $actions instanceof DOMNodeList ) ) {
			for ( $i = 0; $i < $actions->length; $i++ ) {
				try {
					Factory::Db()->delete( 'spdb_plugin_task', [ 'pid' => $pid, 'onAction' => $actions->item( $i )->nodeValue ] );
				}
				catch ( Exception $x ) {
					Sobi::Error( 'installer', SPLang::e( 'Cannot remove plugin task "%s". Db query failed. Error: %s.', $actions->item( $i )->nodeValue, $x->getMessage() ), C::WARNING, 0 );
				}
			}
			if ( $this->xGetString( 'type' ) == 'payment' ) {
				try {
					Factory::Db()->delete( 'spdb_plugin_task', [ 'pid' => $pid, 'onAction' => 'PaymentMethodView' ] );
				}
				catch ( Exception $x ) {
					Sobi::Error( 'installer', SPLang::e( 'Cannot remove plugin task "PaymentMethodView". Db query failed. Error: %s.', $x->getMessage() ), C::WARNING, 0 );
				}
			}
		}

		$field = $this->xdef->query( "/$this->type/fieldType[@typeId]" );
		if ( $field && $field->length ) {
			try {
				Factory::Db()->delete( 'spdb_field_types', [ 'tid' => $field->item( 0 )->getAttribute( 'typeId' ) ] );
			}
			catch ( SPException $x ) {
				Sobi::Error( 'installer', SPLang::e( 'CANNOT_REMOVE_FIELD_DB_ERR', $field->item( 0 )->getAttribute( 'typeId' ), $x->getMessage() ), C::WARNING, 0 );
			}
		}

		$tables = $this->xGetChilds( 'installLog/sql/tables/*' );
		if ( $tables && ( $tables instanceof DOMNodeList ) ) {
			for ( $i = 0; $i < $tables->length; $i++ ) {
				try {
					Factory::Db()->drop( $tables->item( $i )->nodeValue );
				}
				catch ( Exception $x ) {
					Sobi::Error( 'installer', SPLang::e( 'CANNOT_DROP_TABLE', $tables->item( $i )->nodeValue, $x->getMessage() ), C::WARNING, 0 );
				}
			}
		}

		$inserts = $this->xGetChilds( 'installLog/sql/queries/*' );
		if ( $inserts && ( $inserts instanceof DOMNodeList ) ) {
			for ( $i = 0; $i < $inserts->length; $i++ ) {
				$table = $inserts->item( $i )->attributes->getNamedItem( 'table' )->nodeValue;
				$where = [];
				$cols = $inserts->item( $i )->childNodes;
				if ( $cols->length ) {
					for ( $j = 0; $j < $cols->length; $j++ ) {
						$where[ $cols->item( $j )->nodeName ] = $cols->item( $j )->nodeValue;
					}
				}
				try {
					Factory::Db()->delete( $table, $where, 1 );
				}
				catch ( Exception $x ) {
					Sobi::Error( 'installer', SPLang::e( 'CANNOT_DELETE_DB_ENTRIES', $table, $x->getMessage() ), C::WARNING, 0 );
				}
			}
		}
		$type = $this->xGetString( 'type' ) && strlen( $this->xGetString( 'type' ) ) ? $this->xGetString( 'type' ) : ( $this->xGetString( 'fieldType' ) ? 'field' : C::ES );
		switch ( $type ) {
			default:
			case 'SobiProApp':
			case 'plugin':
				$t = Sobi::Txt( 'EX.PLUGIN_TYPE' );
				break;
			case 'field':
				$t = Sobi::Txt( 'EX.FIELD_TYPE' );
				break;
			case 'payment':
				$t = Sobi::Txt( 'EX.PAYMENT_METHOD_TYPE' );
				break;
			case 'language':
				$t = Sobi::Txt( 'EX.LANGUAGE_TYPE' );
				break;
			case 'module':
				$t = Sobi::Txt( 'EX.MODULE_TYPE' );
				break;
		}
		try {
			Factory::Db()->delete( 'spdb_plugins', [ 'pid' => $pid, 'type' => $type ], 1 );
		}
		catch ( Exception $x ) {
			Sobi::Error( 'installer', SPLang::e( 'CANNOT_DELETE_PLUGIN_DB_ERR', $pid, $x->getMessage() ), C::ERROR, 0 );
		}

		try {
			Factory::Db()->delete( 'spdb_plugin_section', [ 'pid' => $pid ] );
		}
		catch ( Exception $x ) {
			Sobi::Error( 'installer', SPLang::e( 'CANNOT_DELETE_PLUGIN_SECTION_DB_ERR', $pid, $x->getMessage() ), C::WARNING, 0 );
		}

		$version = $this->xGetString( 'version' );
		/* if language, the tag is the language code */
		$tag = $type != 'language' ? $this->xGetString( 'tag' ) : C::ES;
		$version = $tag ? $version . ' ' . $tag : $version;

		$params = [ 'name' => $this->xGetString( 'name' ) . ' ' . $version, 'type' => $t ];
		SPFactory::history()->logAction( SPC::LOG_REMOVE, 0, 0,
			'application',
			C::ES,
			$params
		);

		FileSystem::Delete( $this->xmlFile );

		return ucfirst( Sobi::Txt( 'EX.EXTENSION_HAS_BEEN_REMOVED', $params ) );
	}
}
