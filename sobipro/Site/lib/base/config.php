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
 * @created 10-Jan-2009 5:24:15 PM
 * @modified 14 October 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

use Sobi\C;
use Sobi\FileSystem\FileSystem;
use Sobi\Input\Input;
use Sobi\Lib\Factory;
use Sobi\Utils\Type;

/**
 * Class SPConfig
 */
class SPConfig
{
	/*** @var array */
	private $_store = [];
	/*** @var bool */
	public static $cs = false;
	/** @var array */
	private $_icons = [];

	/** @var array */
	private static $fields = [];

	/** @var array */
	private static $parentPathSet = [];


	/**
	 * SPConfig constructor.
	 *
	 * @throws SPException
	 */
	private function __construct()
	{
//		SPLoader::loadClass( 'cms.base.fs' );
		SPLoader::loadClass( 'base.registry' );
	}

	/**
	 * @param string $icon
	 * @param null $def
	 * @param string $section
	 *
	 * @return null
	 */
	public function icon( string $icon, $def = null, $section = 'general' )
	{
		if ( strstr( $icon, '.' ) ) {
			$icon = explode( '.', $icon );
			$section = $icon[ 0 ];
			$icon = $icon[ 1 ];
		}
		$this->initIcons();

		return $this->_icons[ $section ][ $icon ] ?? $def;
	}

	/**
	 * @return array
	 */
	public function icons()
	{
		$this->initIcons();

		return $this->_icons;
	}

	/**
	 * Simple initialization method.
	 *
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function init()
	{
		if ( self::$cs ) {
			Sobi::Error( 'config', SPLang::e( 'CRITICAL_SECTION_VIOLATED' ), C::ERROR, 500, __LINE__, __CLASS__ );
		}
		/* define critical section to avoid infinite loops */
		self::$cs = true;

		/* evaluate the name field fid. Consider it may have changed. */
		$previewsNameFieldFid = self::key( 'entry.name_field' );
		$settings = Input::Arr( 'spcfg' );
		$newNameFieldFid = $previewsNameFieldFid;
		if ( count( $settings ) ) {
			$newNameFieldFid = $settings[ 'entry.name_field' ] ?? $previewsNameFieldFid;
		}
		$nameFieldFid = $previewsNameFieldFid !== $newNameFieldFid ? $newNameFieldFid : $previewsNameFieldFid;
		if ( $nameFieldFid ) {
			$fc = SPLoader::loadModel( 'field' );
			$field = new $fc();
			$field->init( $nameFieldFid );
			$this->set( 'name_field_nid', $field->get( 'nid' ), 'entry' );
		}
		if ( defined( 'SOBIPRO_ADM' ) ) {
			if ( self::key( 'language.adm_domain' ) ) {
				SPLang::registerDomain( self::key( 'language.adm_domain' ) );
			}
		}
		else {
			if ( self::key( 'language.domain' ) ) {
				SPLang::registerDomain( self::key( 'language.domain' ) );
			}
		}
		/* set allowed request attributes and tags */
		SPRequest::setTagsAllowed( $this->key( 'html.allowed_tags_array' ) );
		SPRequest::setAttributesAllowed( $this->key( 'html.allowed_attributes_array' ) );

		$this->_store[ 'general' ][ 'root' ] = SOBI_ROOT;
		$this->_store[ 'general' ][ 'path' ] = SOBI_PATH;
		$this->_store[ 'general' ][ 'cms' ] = SOBI_CMS;
		$this->_store[ 'general' ][ 'live_path' ] = SOBIPRO_FOLDER;

		if ( !file_exists( SOBI_PATH . '/etc/encryption.php' ) ) {
			$key = bin2hex( openssl_random_pseudo_bytes( 32 ) );
			$content = "<?php\ndefined( 'SOBIPRO' ) || exit( 'Restricted access' );\nself::set( 'key', '$key', 'encryption' );\n";
			file_put_contents( SOBI_PATH . '/etc/encryption.php', $content );
		}
		else {
			include_once( SOBI_PATH . '/etc/encryption.php' );
		}

		/* leave the critical section */
		self::$cs = false;
	}

	/**
	 * Singleton - returns instance of the config object.
	 *
	 * @return \SPConfig
	 */
	public static function & getInstance()
	{
		static $config = false;
		if ( !( $config instanceof SPConfig ) ) {
			$config = new SPConfig();
		}

		return $config;
	}

	/**
	 * Gets config values from ini file.
	 *
	 * @param string $path
	 * @param bool $sections
	 * @param bool $adm
	 * @param string $defSection
	 *
	 * @return bool
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function addIniFile( $path, bool $sections = true, bool $adm = false, $defSection = 'general' )
	{
		$file = FileSystem::FixPath( $adm ? SOBI_ADM_PATH . '/' : SOBI_PATH . '/' . str_replace( '.', '/', $path ) );

		if ( !( $content = FileSystem::LoadIniFile( $file, $sections ) ) ) {
			Sobi::Error( 'config', sprintf( 'CANNOT_PARSE_INI_FILE', $file ), C::WARNING, 0, __LINE__, __CLASS__ );

			return false;
		}
		if ( is_array( $content ) && count( $content ) ) {
			if ( $sections ) {
				foreach ( $content as $section => $values ) {
					if ( !isset( $this->_store[ $section ] ) ) {
						$this->_store[ $section ] = [];
					}
					$currSec =& $this->_store[ $section ];
					if ( !empty( $values ) ) {
						foreach ( $values as $key => $value ) {
							$_c = explode( '_', $key );
							if ( $_c[ count( $_c ) - 1 ] == 'array' ) {
								$value = explode( '|', $value );
							}
							$currSec[ $key ] = $this->structuralData( $value );
						}
					}
				}
			}
			else {
				$currSec =& $this->_store[ $defSection ];
				foreach ( $content as $key => $value ) {
					$currSec[ $key ] = $value;
				}
			}

			return true;
		}
		else {
			Sobi::Error( 'config', SPLang::e( 'EMPTY_INIFILE', $path ), C::WARNING, 0, __LINE__, __CLASS__ );

			return false;
		}
	}

	/**
	 * Gets config values from database table.
	 *
	 * @param string $table name of table
	 * @param int $id object id/directory section number
	 * @param string $section name of row where the section name is stored
	 * @param string $key name of row where the key name is stored
	 * @param string $value name of row where the value is stored
	 * @param string $object
	 * @param bool $parseObject parse directory section
	 *
	 * @return bool
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function addTable( $table, $id = 0, string $section = 'cSection', string $key = 'sKey', string $value = 'sValue', string $object = 'section', bool $parseObject = true )
	{
		$db = Factory::Db();
		$where = null;
		$order = 'configsection';
		if ( $parseObject ) {
			if ( $id ) {
				$where = [ $object => [ 0, $id ] ];
				$order = "$object, configsection";
			}
			else {
				$where = [ $object => 0 ];
			}
		}
		try {
			$db->select( [ "$section AS configsection", "$key AS sKey", "$value AS sValue" ], $table, $where, $order );
			$config = $db->loadObjectList();
			foreach ( $config as $row ) {
				if ( !isset( $this->_store[ $row->configsection ] ) ) {
					$this->_store[ $row->configsection ] = [];
				}
				$_c = explode( '_', $row->sKey );
				if ( $_c[ count( $_c ) - 1 ] == 'array' || $_c[ count( $_c ) - 1 ] == 'arr' ) {
					try {
						$row->sValue = self::unserialize( $row->sValue );
					}
					catch ( SPException $x ) {
						Sobi::Error( 'config', $x->getMessage() . ' [ ' . $row->sKey . ' ] ', SPC::WARNING, 0, __LINE__, __CLASS__ );
					}
				}
				if ( $row->configsection == 'debug' && $row->sKey == 'level' ) {
					if ( !( defined( 'PHP_VERSION_ID' ) ) || PHP_VERSION_ID < 50300 ) {
						$row->sKey = $row->sKey == 30719 ? 6143 : $row->sKey;
					}
				}
				$this->_store[ $row->configsection ][ $row->sKey ] = $this->structuralData( $row->sValue );
			}
		}
		catch ( SPException $x ) {
			Sobi::Error( 'config', $x->getMessage(), C::WARNING, 0, __LINE__, __CLASS__ );
		}

		return true;
	}

	/**
	 * @param int $sid
	 * @param array $types
	 * @param bool $cat
	 * @param bool $enabled
	 * @param string $showIn (details, vcard, both, hidden)
	 *
	 * @return array
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function fields( $sid = 0, $types = [], $cat = false, $enabled = false, $showIn = C::ES )
	{
		if ( !$cat ) {
			$params = [ 'section' => $sid, 'adminField>' => -1 ];
		}
		else {
			$params = [ 'section' => $sid, 'adminField' => -1 ];
		}
		if ( $types ) {
			$params[ 'fieldType' ] = $types;
		}
		if ( $enabled ) {
			$params[ 'enabled' ] = 1;
		}
		if ( $showIn && ( $showIn == 'vcard' || $showIn == 'details' ) ) {
			$enabled = $enabled ? " AND ( enabled = 1 )" : C::ES;
			$adminField = $cat ? " AND  ( adminField ='-1' )" : " AND  ( adminField >'-1' )";
			$query = "SELECT fid FROM spdb_field WHERE ( section = {$params['section']} )  " . $adminField . $enabled . " AND (showIn = '$showIn' OR showIn = 'both') ORDER BY position";
			$results = Factory::Db()
				->setQuery( $query )
				->loadResultArray();
		}
		else {
			if ( $showIn ) {
				if ( $showIn == 'special' ) {
					$params[ '!showIn' ] = 'hidden';
				}
				else {
					$params[ 'showIn' ] = $showIn;
				}
			}
			$results = Factory::Db()
				->select( 'fid', 'spdb_field', $params, 'position' )
				->loadResultArray();
		}
		$fields = [];
		if ( count( $results ) ) {
			$labels = SPLang::translateObject( $results, [ 'name' ], 'field', C::ES, 'fid' );
			foreach ( $results as $id ) {
				$fields[ $id ] = $labels[ $id ][ 'value' ];
			}
		}

		return $fields;
	}

	/**
	 * @param $data
	 * @param bool $force
	 *
	 * @return array|false|mixed|string|string[]
	 * @throws \SPException
	 */
	public function structuralData( $data, $force = false )
	{
		if ( is_string( $data ) && strstr( $data, '://' ) ) {
			$struct = explode( '://', $data );
			switch ( $struct[ 0 ] ) {
				case 'json':
					if ( strstr( $struct[ 1 ], "':" ) || strstr( $struct[ 1 ], "{'" ) || strstr( $struct[ 1 ], "['" ) ) {
						$struct[ 1 ] = str_replace( "'", '"', $struct[ 1 ] );
					}
					$data = json_decode( $struct[ 1 ] );
					break;
				case 'serialized':
					if ( strstr( $struct[ 1 ], "':" ) || strstr( $struct[ 1 ], ":'" ) || strstr( $struct[ 1 ], "['" ) ) {
						$struct[ 1 ] = str_replace( "'", '"', $struct[ 1 ] );
					}
					$data = unserialize( $struct[ 1 ] );
					break;
				case 'csv':
					if ( function_exists( 'str_getcsv' ) ) {
						$data = str_getcsv( $struct[ 1 ] );
					}
					else {
						Sobi::Error( 'config', 'Function "str_getcsv" does not exist!' );
					}
					break;
			}
		}
		elseif ( is_string( $data ) && $force ) {
			if ( strstr( $data, '|' ) ) {
				$data = explode( '|', $data );
			}
			elseif ( strstr( $data, ',' ) ) {
				$data = explode( ',', $data );
			}
			elseif ( strstr( $data, ';' ) ) {
				$data = explode( ';', $data );
			}
			else {
				$data = [ $data ];
			}
		}

		return $data;
	}

	/**
	 * Stores a key.
	 *
	 * @param string $label
	 * @param mixed $var
	 * @param string $section
	 *
	 * @return bool
	 */
	public function set( $label, $var, $section = 'general' )
	{
		if ( !isset( $this->_store[ $section ][ $label ] ) ) {
			$this->_store[ $section ][ $label ] = $var;

			return true;
		}
		else {
			/** @todo need to think here something * */
			//Sobi::Error( 'config', SPLang::e( 'SET_EXISTING_KEY', $label ), SPC::NOTICE, 0, __LINE__, __CLASS__ );
			return false;
		}
	}

	/**
	 * Stores a key.
	 *
	 * @param string $label
	 * @param mixed $var
	 * @param string $section
	 *
	 * @return bool
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	public function change( $label, $var, $section = 'general' )
	{
		if ( isset( $this->_store[ $section ][ $label ] ) ) {
			$this->_store[ $section ][ $label ] = $var;

			return true;
		}
		else {
			Sobi::Error( 'config', SPLang::e( 'CHANGE_NOT_EXISTING_KEY', $label ), C::NOTICE, 0, __LINE__, __CLASS__ );

			return false;
		}
	}

	/**
	 * Deletes a stored variable.
	 *
	 * @param string $label
	 * @param string $section
	 *
	 * @return bool
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	public function unsetKey( $label, $section = "general" )
	{
		if ( isset( $this->_store[ $section ][ $label ] ) ) {
			unset( $this->_store[ $section ][ $label ] );

			return true;
		}
		else {
			Sobi::Error( 'config', SPLang::e( 'UNSET_NOT_EXISTING_KEY', $label ), C::NOTICE, 0, __LINE__, __CLASS__ );

			return false;
		}
	}

	/**
	 * Returns a copy of a stored key.
	 *
	 * @param string $label
	 * @param mixed $def
	 * @param string $section
	 *
	 * @return mixed
	 */
	public function get( $label, $def = null, $section = 'general' )
	{
		return $this->key( $label, $def, $section );
	}

	/**
	 * Returns a copy of a stored key.
	 *
	 * @param $key
	 * @param mixed $def
	 * @param string $section
	 *
	 * @return mixed
	 * @internal param string $label
	 */
	public function key( $key, $def = null, $section = 'general' )
	{
		if ( strstr( $key, '.' ) ) {
			$key = explode( '.', $key );
			$section = $key[ 0 ];
			$key = $key[ 1 ];
		}
		$return = $this->_store[ $section ][ $key ] ?? $def;

		/* the config value can contain other config keys to parse in the form:
		   * [cfg:live_site] - deprecated or {cfg:live_site}
		   */
		if ( is_string( $return ) && ( strstr( $return, '[cfg:' ) || strstr( $return, '{cfg:' ) ) ) {
			preg_match_all( '/\[cfg:([^\]]*)\]/', $return, $matches );
			if ( !( isset( $matches[ 1 ] ) ) || !( count( $matches[ 1 ] ) ) ) {
				preg_match_all( '/\{cfg:([^}]*)\}/', $return, $matches );
			}
			if ( count( $matches[ 1 ] ) ) {
				foreach ( $matches[ 1 ] as $i => $replacement ) {
					if ( $this->key( $replacement ) ) {
						$return = str_replace( $matches[ 0 ][ $i ], $this->key( $replacement ), $return );
					}
				}
			}
		}

		return $return;
	}

	/**
	 * @return array
	 */
	public function getSettings()
	{
		return $this->_store;
	}

	/**
	 * Checks if a variable is already stored.
	 *
	 * @param string $label
	 * @param string $section
	 *
	 * @return bool
	 */
	public function keyIsset( $label, $section = 'general' )
	{
		return isset( $this->_store[ $section ][ $label ] );
	}

	/**
	 * Returns backtrace array.
	 *
	 * @return array
	 */
	public static function getBacktrace()
	{
		return self::backtrace( false, [ 'file', 'line', 'function', 'class' ], true, false, false );
	}

	/**
	 * Creates backtrace array.
	 *
	 * @param bool $store - store into a file
	 * @param array $out - format
	 * @param bool $return as array
	 * @param bool $hide - embed within html comment
	 * @param bool $do - output directly
	 *
	 * @return mixed
	 */
	public static function backtrace( $store = false, $out = [ 'file', 'line', 'function', 'class' ], $return = false, $hide = false, bool $do = true )
	{
		$trace = [];
		$backtrace = debug_backtrace();
		if ( count( $backtrace ) ) {
			foreach ( $backtrace as $level ) {
				$l = [];
				foreach ( $out as $i ) {
					$l[ $i ] = $level[ $i ] ?? 'none';
					$l[ $i ] = str_replace( SOBI_ROOT, C::ES, $l[ $i ] );
				}
				$trace[] = $l;
			}
		}
		if ( $do ) {
			return self::debOut( $trace, $hide, $return, $store );
		}
		else {
			return $trace;
		}
	}

	/**
	 * Creates debug output.
	 *
	 * @param mixed $str - string/object/array to parse
	 * @param bool $hide - embed within a HTML comment
	 * @param bool $return - return or output directly
	 * @param bool $store - store within a file
	 *
	 * @return mixed
	 */
	public static function debOut( $str = null, $hide = false, $return = false, $store = false )
	{
		$return = $store ? 1 : $return;
		if ( !$str ) {
			$str = 'Empty';
		}
		if ( $hide ) {
			echo "\n\n<!-- SobiPro Debug: ";
		}
		elseif ( !$return ) {
			echo "<h4>";
		}
		if ( is_object( $str ) /*|| is_array( $str )*/ ) {
			try {
				$str = highlight_string( "<?php\n\$data = " . var_export( $str, true ), true );
			}
			catch ( Exception $x ) {
				$str = $x->getMessage();
			}
		}
		elseif ( is_array( $str ) ) {
			$str = highlight_string( "<?php\n\$data = " . var_export( $str, true ), true );
		}
		if ( !( $return ) ) {
			echo $str;
		}
		if ( $hide ) {
			echo "  -->\n\n";
		}
		elseif ( !( $return ) ) {
			echo "</h4>";
		}
		if ( $store ) {
			file_put_contents(
				SPLoader::path( 'var.log.debug', 'front', false, 'html' ),
				'<br/>[' . date( DATE_RFC822 ) . "]<br/>$str<br/>",
				C::FS_APP
			);
		}
		elseif ( $return ) {
			return $str;
		}

		return $str;
	}

	/**
	 * Tries to revert date created by calendar field to database-acceptable format.
	 *
	 * @param string $str
	 * @param string $format
	 *
	 * @return double
	 */
	public function rdate( $str, $format = 'calendar.date_format' )
	{
//        $date = array();
//        $format = $this->key( $format );
//        $format = preg_replace( '/[^\w]/', '_', $format );
//        $format = str_replace( array( 'dd', 'y' ), array( 'd', 'Y' ), $format );
//        $format = explode( '_', $format );
//        $str = preg_replace( '/[^\w]/', '_', $str );
//        $str = explode( '_', $str );
//        foreach ( $format as $i => $k ) {
//            $date[ strtolower( $k ) ] = $str[ $i ];
//        }
//        $str = null;
//        $str .= isset( $date[ 'd' ] ) ? $date[ 'd' ] : ' ';
//        $str .= ' ';
//        /** @todo find alternative for it */
//        //$str .= isset( $date[ 'm' ] ) ? SPFactory::lang()->revert( $date[ 'm' ] ) : ' ';
//        $str .= isset( $date[ 'm' ] ) ? $date[ 'm' ] : ' ';
//        $str .= ' ';
//        $str .= isset( $date[ 'y' ] ) ? $date[ 'y' ] : ' ';
//        if ( isset( $date[ 'h' ] ) && isset( $date[ 'h' ] ) && isset( $date[ 'h' ] ) ) {
//            $str .= ' ' . $date[ 'h' ] . ' ' . $date[ 'i' ] . ' ' . $date[ 's' ];
//        }
//        return strtotime( $str );
		$date = [];
		$format = $this->key( $format );
		$format = preg_replace( '/[^\w]/', '_', $format );
		$format = str_replace( [ 'dd', 'y' ], [ 'd', 'Y' ], $format );
		$format = explode( '_', $format );
		$str = preg_replace( '/[^\w]/', '_', $str );
		$str = explode( '_', $str );
		foreach ( $format as $i => $k ) {
			$date[ strtolower( $k ) ] = $str[ $i ];
		}
		$str = null;
		$str .= $date[ 'y' ] ?? ' ';
		$str .= '-';
		/** @todo find alternative for it */
		//$str .= isset( $date[ 'm' ] ) ? SPFactory::lang()->revert( $date[ 'm' ] ) : ' ';
		$str .= $date[ 'm' ] ?? ' ';
		$str .= '-';
		$str .= $date[ 'd' ] ?? ' ';

		if ( isset( $date[ 'h' ] ) && isset( $date[ 'i' ] ) && isset( $date[ 's' ] ) ) {
			$str .= ' ' . $date[ 'h' ] . ':' . $date[ 'i' ] . ':' . $date[ 's' ];
		}

		return strtotime( $str );
	}

	/**
	 * Returns the name field's nid of a specified section.
	 * If no section is given, the nid of the current section is used.
	 *
	 * @param string $section
	 *
	 * @return string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function nameFieldNid( string $section = C::ES ): string
	{
		$nid = C::ES;

		$nameField = $this->nameField( $section );
		if ( $nameField instanceof SPField ) {
			$nid = $nameField->get( 'nid' );
		}

		return $nid;
	}

	/**
	 * Returns the name field's fid of a specified section as stored in the database.
	 * If no section is given, the fid of the current section is used.
	 *
	 * @param string $section
	 *
	 * @return int
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function nameFieldFid( string $section = C::ES ): int
	{
		$currentSection = $section == Sobi::Section() || !$section;
		/* if request is for current section, get the fid from the configuration settings. Otherwise, get it from the database.*/
		if ( $currentSection ) {
			$fid = (int) Sobi::Cfg( 'entry.name_field', 0 );

			if ( !$fid ) {
				/* if the current section is requested, show an error message */
				SPFactory::message()->warning( 'NO_NAME_FIELD_SELECTED' );

				Sobi::Error( __CLASS__, 'Name field missing: Have you disabled/deleted the name field?', C::WARNING, 0, __LINE__, __FILE__ );
			}
		}

		/* get the fid of another section from the database */
		else {
			$where = [ 'sKey'     => 'name_field',
			           'cSection' => 'entry',
			           'section'  => $section ];

			$fid = (int) Factory::Db()
				->select( 'sValue', 'spdb_config', $where )
				->loadResult();
		}

		return $fid;
	}

	/**
	 * Returns a list of all name field's fids from the database.
	 *
	 * @throws \Sobi\Error\Exception
	 */
	public function nameFieldFids(): ?array
	{
		return Factory::Db()
			->select( 'sValue', 'spdb_config', [ 'sKey' => 'name_field', 'cSection' => 'entry' ] )
			->loadResultArray();
	}

	/**
	 * Returns the name/title field and gives an error message if field does not exist or is not enabled.
	 *
	 * @return SPDBObject - SPField
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function nameField( $section = C::ES )
	{
		$error = false;
		$section = $section ? : Sobi::Section();
		$nameFieldFid = Sobi::Cfg( 'entry.name_field', 0 );

		if ( $nameFieldFid ) {
			if ( !isset( self::$fields[ $section ][ $nameFieldFid ] ) ) {
				/* @var SPField $nameField */
				$nameField = SPFactory::Model( 'field', true );
				$nameField->init( $nameFieldFid );

				if ( $nameField->get( 'nid' ) && $nameField->get( 'enabled' ) ) {
					self::$fields[ $section ][ $nameFieldFid ] = $nameField;
				}
				else {
					$error = true;
				}
			}
		}
		else {
			$error = true;
		}

		if ( $error ) {
			SPFactory::message()->warning( 'NO_NAME_FIELD_SELECTED' );
			Sobi::Error( __CLASS__, 'Name field missing: Have you disabled/deleted the name field?', C::WARNING, 0, __LINE__, __FILE__ );
		}

		return self::$fields[ $section ][ $nameFieldFid ] ?? SPFactory::Model( 'field', true );
	}

//	/**
//	 * Returns the name/title field
//	 * @return SPField
//	 */
//	public function sectionFields()
//	{
//		if( Sobi::Section() ) {
//			if( !( isset( self::$fields[ Sobi::Section() ] ) && count( self::$fields[ Sobi::Section() ] ) ) ) {
//				$db =& Factory::Db();
//		        try {
//		        	$db->select( '*', 'spdb_field', array( 'section' => $sid ), 'position' );
//		        	$fields[ $sid ] = $db->loadObjectList();
//		        	Sobi::Trigger( $this->name(), ucfirst( __FUNCTION__ ), array( &$fields ) );
//		        }
//		        catch ( SPException $x ) {
//		        	Sobi::Error( $this->name(), SPLang::e( 'CANNOT_GET_FIELDS_DB_ERR', $x->getMessage() ), SPC::ERROR, 500, __LINE__, __FILE__ );
//		        }
//			}
//			return self::$fields[ Sobi::Section() ];
//		}
//	}

	/**
	 * Returns a formatted date.
	 *
	 * @param int|string $time - time or date
	 * @param string $formatKey
	 * @param string $format - section and key in the config
	 * @param bool $gmt
	 *
	 * @return string
	 */
	public function date( $time = 0, $formatKey = 'date.db_format', $format = C::ES, bool $gmt = false )
	{
		if ( $time == Factory::Db()->getNullDate() ) {
			return C::ES;
		}
		if ( !is_numeric( $time ) ) {
			$time = strtotime( $time );
		}
		if ( !$time ) {
			return C::ES;
		}
		if ( !$format ) {
			$format = $this->key( $formatKey, SPC::DEFAULT_DB_DATE );
		}
		$date = $time ? ( is_numeric( $time ) ? $time : strtotime( $time ) ) : time();

		return $gmt ? gmdate( $format, (int) $date ) : date( $format, (int) $date );
	}

	/**
	 * Returns time offset to UTC.
	 *
	 * @return int
	 * @throws Exception
	 */
	public function getTimeOffset()
	{
		static $offset = 0;
		if ( !$offset ) {
			$tz = new DateTimeZone( Sobi::Cfg( 'time_offset' ) );
			$offset = $tz->getOffset( new DateTime( 'now', new DateTimeZone( 'UTC' ) ) );
		}

		return $offset;
	}

	/**
	 * @param string $var
	 * @param string $name
	 *
	 * @return mixed
	 * @throws SPException
	 */
	public static function unserialize( $var, string $name = C::ES )
	{
		$returnValue = C::ES;
		if ( is_string( $var ) && strlen( $var ) > 2 ) {
			if ( ( $var2 = base64_decode( $var, true ) ) ) {
				if ( function_exists( 'gzinflate' ) ) {
					if ( ( $returnValue = @gzinflate( $var2 ) ) ) {
						if ( !( $returnValue = @unserialize( $returnValue ) ) ) {
							throw new SPException( sprintf( 'Cannot unserialize compressed variable %s', $returnValue ) );
						}
					}
					else {
						if ( !( $returnValue = @unserialize( $var2 ) ) ) {
							throw new SPException( sprintf( 'Cannot unserialize raw (?) encoded variable %s', $var2 ) );
						}
					}
				}
				else {
					if ( !( $returnValue = @unserialize( $var2 ) ) ) {
						throw new SPException( sprintf( 'Cannot unserialize raw encoded variable %s', $var2 ) );
					}
				}
			}
			else {
				if ( !( $returnValue = @unserialize( $var ) ) ) {
					throw new SPException( sprintf( 'Cannot unserialize raw variable %s', $var ) );
				}
			}
		}

		return $returnValue;
	}

	/**
	 * @param mixed $var
	 *
	 * @return string
	 */
	public static function serialize( $var )
	{
		if ( ( is_array( $var ) && count( $var ) ) || is_object( $var ) ) {
			$var = serialize( $var );
		}
		if ( is_string( $var ) && function_exists( 'gzdeflate' ) && ( strlen( $var ) > 500 ) ) {
			$var = gzdeflate( $var, 9 );
		}
		if ( is_string( $var ) && strlen( $var ) > 2 ) {
			$var = base64_encode( $var );
		}

		return is_string( $var ) ? $var : C::ES;
	}

	/**
	 * @param $key
	 * @param $val
	 * @param string $cfgSection
	 *
	 * @return SPConfig
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function & saveCfg( $key, $val, $cfgSection = 'general' )
	{
		if ( Sobi::Can( 'configure', 'section' ) ) {
			if ( strstr( $key, '.' ) ) {
				$key = explode( '.', $key );
				$cfgSection = $key[ 0 ];
				$key = $key[ 1 ];
			}
			Sobi::Trigger( 'Config', 'Save', [ &$key, &$val, &$cfgSection ] );

			try {
				Factory::Db()->insertUpdate( 'spdb_config',
					[ 'sKey'     => $key,
					  'sValue'   => $val,
					  'section'  => Sobi::Reg( 'current_section', 0 ),
					  'critical' => 0,
					  'cSection' => $cfgSection ]
				);
			}
			catch ( \Sobi\Error\Exception $x ) {
				Sobi::Error( 'config', SPLang::e( 'CANNOT_SAVE_CONFIG', $x->getMessage() ), C::WARNING, 500, __LINE__, __CLASS__ );
			}
		}

		return $this;
	}

	/**
	 * Returns linked lists (names or ids) of parent elements to the given id.
	 *
	 * @param int $id - the id of the object
	 * @param bool $names - names or ids only
	 * @param bool $onlyParents
	 * @param bool $join
	 *
	 * @return array|bool|mixed
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function getParentPathLegacy( int $id, bool $names = false, bool $onlyParents = false, bool $join = false )
	{
		$db = Factory::Db();
//		if ( !is_numeric( $id ) ) {
//			return false;
//		}
		$ident = 'relations_path' . ( $names ? '_names' : C::ES ) . ( $onlyParents ? '_parents' : C::ES ) . ( $join ? '_join' : C::ES );
		$cached = SPFactory::cache()->getVar( $ident, $id );
		if ( $cached ) {
			return $cached;
		}
		else {
			$cid = $id;
		}

		$path = $onlyParents ? [] : [ $id ];
		while ( $id > 0 ) {
			try {
				// it doesn't make sense, but it happened because of a bug in the SigsiuTree category selector
				$id = $db
					->select( 'pid', 'spdb_relations', [ 'id' => $id, '!pid' => $id ] )
					->loadResult();
				if ( $id ) {
					$path[] = ( int ) $id;
				}
			}
			catch ( Exception $x ) {
				Sobi::Error( __FUNCTION__, SPLang::e( 'CANNOT_GET_PARENT_ID', $x->getMessage() ), C::WARNING, 500, __LINE__, __CLASS__ );
			}
		}
		if ( $names && count( $path ) ) {
			$path = $this->getParentPathNames( $path, $join );
		}
		$path = array_reverse( $path );
		SPFactory::cache()->addVar( $path, $ident, $cid );

		return $path;
	}

	/**
	 * @param int $id
	 * @param bool $names
	 * @param bool $onlyParents
	 * @param bool $join
	 *
	 * @return array|bool|mixed
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function getParentPath( int $id, bool $names = false, bool $onlyParents = false, bool $join = false ): array
	{
		if ( !$id ) {
			self::$parentPathSet[ $id ] = false;

			return [];
		}

		self::$parentPathSet[ $id ] = true;
		$cid = md5( "$id$names$onlyParents$join" );
		static $cache = [];
		if ( isset( $cache[ $cid ] ) ) {
			return $cache[ $cid ];
		}
		try {
			if ( $id != Sobi::Section() ) {
				$relations = SPFactory::getCategoryRelations( $id );
				if ( count( $relations ) ) {
					$path = [];

					foreach ( $relations as $relation ) {
						// re-define it here as we need to go through one path only
						foreach ( $relation as $parent ) {
							$path[] = (int) $parent[ 'id' ];
						}
						if ( !$onlyParents && !in_array( $id, $path ) ) {
							$path[] = $id;
						}
						Type::TypecastArray( $path );
						break 1;
					}
				}
				else {
					$type = Factory::Db()
						->select( 'oType', 'spdb_object', [ 'id' => $id ] )
						->loadResult();
					if ( $type == 'section' ) {
						$path = [ Sobi::Section() ?? $id ];
					}
					else {
						if ( Sobi::Cfg( 'debug', false ) ) {
							trigger_error( sprintf( 'Object with the id %s does not return any results. Most likely invalid relation.', $id ), C::NOTICE );
						}

						self::$parentPathSet[ $id ] = false;

						return [];
					}
				}
			}
			else {
				$path = [ (int) Sobi::Section() ];
			}
			if ( $names && count( $path ) ) {
				$path = $this->getParentPathNames( $path, $join );
			}
		}
		catch ( Sobi\Error\Exception|RuntimeException $x ) {
			$path = $this->getParentPathLegacy( $id, $names, $onlyParents, $join );
			if ( !count( $path ) ) {
				self::$parentPathSet[ $id ] = false;
			}
		}
		$cache[ $cid ] = $path;

		return $path;
	}

	/**
	 * Gets the section of a given category or entry ($id).
	 * Returns the section, if $id is already a section.
	 * Note, the procedure does not return the section id $id is already the section.
	 * Therefore, we check the table directly if $id is a section.
	 *
	 * @param int $id
	 *
	 * @return int
	 * @throws \SPException
	 */
	public function getParentPathSection( int $id ): int
	{
		$section = Sobi::Reg( 'current_section', 0 );
		self::$parentPathSet[ $id ] = true;

		if ( $id ) {
			/* if we have a section and the id is a section ... */
			if ( $id == $section ) {
				return $section;
			}

			/* we do not have a section (menu, dashboard, ...) */
			else {
				try {
					/* if id is not a section, check the object table (new preferred way) */
					$sid = (int) Factory::Db()
						->select( 'section', 'spdb_object', [ 'id' => $id ] )
						->loadResult();

					if ( $sid ) {
						return ( $sid );
					}

					/* the object table does not yet contain the section id */
					else {
						/* get the section from the relation table if id is a section */
						$sid = (int) Factory::Db()
							->select( 'id', 'spdb_relations', [ 'id' => $id, 'pid' => 0, 'oType' => 'section' ] )
							->loadResult();

						if ( $sid ) {
							return $sid;
						}

						/* if id is not a section, get the relations-path */
						else {
							$relations = [];
							try {
								$relations = SPFactory::getCategoryRelations( $id );
							}
							catch ( Sobi\Error\Exception|RuntimeException $x ) {
								while ( $id > 0 ) {
									try {
										$id = ( int ) Factory::Db()
											->select( 'pid', 'spdb_relations', [ 'id' => $id, '!pid' => $id ] )
											->loadResult();
										if ( $id ) {
											$relations[] = $id;
										}
									}
									catch ( Exception $x ) {
										Sobi::Error( __FUNCTION__, SPLang::e( 'CANNOT_GET_PARENT_ID', $x->getMessage() ), C::WARNING, 500, __LINE__, __CLASS__ );
									}
								}
								$relations = array_reverse( $relations );
							}
						}
					}
				}
				catch ( Sobi\Error\Exception|RuntimeException $x ) {
					return $section;
				}
			}

			/* we haven't a section id yet */
			if ( count( $relations ) ) {
				SPFactory::registry()->set( 'current_relations', $relations );
				foreach ( $relations as $relation ) {
					if ( is_int( $relation ) && $relation == $section ) {
						return $relation;
					}
					if ( is_array( $relation ) && count( $relation ) ) {
						foreach ( $relation as $object ) {
							if ( $object[ 'type' ] == 'section' ) {
								return $object[ 'id' ];
							}
						}
					}
					else {
						if ( is_array( $relation ) && $relation[ 'type' ] == 'section' ) {
							return $relation[ 'id' ];
						}
					}
				}
			}
		}

		self::$parentPathSet[ $id ] = false;

		return $section ? : 0;        /* take the previously set section */
	}

	/**
	 *  Gets the state of parent path for entry $id.
	 *
	 * @param int $id
	 *
	 * @return int
	 */
	public function getParentPathState( int $id ): int
	{
		return self::$parentPathSet[ $id ] !== null ? self::$parentPathSet[ $id ] : false;
	}

	/**
	 * Returns the section the old way by travelling through the relations path in the relations table.
	 *
	 * @param int $sid
	 *
	 * @return int
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function getSectionLegacy( int $sid ): int
	{
		$path = [];
		$id = $sid;
		$section = 0;

		/* travel the relation path up from id to section */
		while ( $id > 0 ) {
			try {
				$id = ( int ) Factory::Db()
					->select( 'pid', 'spdb_relations', [ 'id' => $id ] )
					->loadResult();
				if ( $id ) {
					$path[] = $id;
				}
			}
			catch ( Exception $x ) {
				Sobi::Error( 'CoreCtrl', SPLang::e( 'DB_REPORTS_ERR', $x->getMessage() ), C::ERROR, 500, __LINE__, __FILE__ );
			}
		}

		if ( count( $path ) ) {
			$path = array_reverse( $path );
			$section = (int) ( $path[ 0 ] ? : 0 );
//			$section = SPFactory::object( $this->_section );
		}

		return $section;
	}

	/**
	 * @return void
	 */
	protected function initIcons()
	{
		if ( !count( $this->_icons ) ) {
			if ( Sobi::Reg( 'current_template' ) && FileSystem::Exists( Sobi::Reg( 'current_template' ) . '/js/icons.json' ) ) {
				$this->_icons = json_decode( FileSystem::Read( FileSystem::FixPath( Sobi::Reg( 'current_template' ) . '/js/icons.json' ) ), true );
			}
			else {
				$this->_icons = json_decode( FileSystem::Read( SOBI_PATH . '/etc/icons.json' ), true );
			}
		}
	}

	/**
	 * @param array $path
	 * @param bool $join
	 *
	 * @return array
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function getParentPathNames( array $path, bool $join ): array
	{
		$names = SPLang::translateObject( $path, [ 'name', 'alias' ], [ 'section', 'category', 'entry' ] );
		if ( is_array( $names ) && !empty( $names ) ) {
			foreach ( $path as $i => $id ) {
				if ( $join ) {
					$name = array_key_exists( 'value', $names[ $id ] ) ? $names[ $id ][ 'value' ] :
						( array_key_exists( 'name', $names[ $id ] ) ? $names[ $id ][ 'name' ] : C::ES );
					$path[ $i ] = [ 'id' => $id, 'name' => $name, 'alias' => $names[ $id ][ 'alias' ] ];
				}
				else {
					if ( array_key_exists( $id, $names ) ) {
						$path[ $i ] = array_key_exists( 'value', $names[ $id ] ) ? $names[ $id ][ 'value' ] :
							( array_key_exists( 'name', $names[ $id ] ) ? $names[ $id ][ 'name' ] : C::ES );
					}
				}
			}
		}

		return $path;
	}

	/**
	 * Copies the template storage files of an application from the storage to the current template.
	 * 'root' => 'apps/application/'
	 * 'template' => Sobi::Cfg( 'section.template' )    -> template name
	 * 'compile' => true|false  -> compile theme.less
	 *
	 * @param array $data
	 * @param bool $overwrite -> overwrite the file if exists
	 *
	 * @return array|false[]
	 * @throws \Less_Exception_Parser
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function copyStorage( array $data = [], bool $overwrite = false ): array
	{
		$destination = C::ES;
		$result = [ 'copied' => false ];
		if ( count( $data ) ) {
			$source = SPLoader::dirPath( SPC::TEMPLATE_PATH . SPC::TEMPLATE_STORAGE . '/' . $data[ 'root' ] );
			$destination = SPLoader::dirPath( SPC::TEMPLATE_PATH . $data[ 'template' ] );

			if ( FileSystem::Exists( $source ) && is_dir( $source ) ) {
				$result = self::copyStorageFolder( $source, $destination, $overwrite );
			}
		}

		/* compile theme.less if set so and if the files are copied */
		if ( $data[ 'compile' ] && $result[ 'copied' ] ) {
			$lessfile = FileSystem::FixPath( $destination . '/css/theme.less' );
			self::compileLessFile( $lessfile, str_replace( 'less', 'css', $lessfile ) );
		}

		if ( $result[ 'copied' ] ) {
			SPFactory::history()->logAction( SPC::LOG_COPYSTORAGE,
				0,
				Sobi::Section(),
				'file',
				C::ES,
				[ 'name'     => $data[ 'root' ],
				  'source'   => $data[ 'source' ] ?? C::ES,
				  'template' => $data[ 'template' ],
				  'compile'  => $data[ 'compile' ] ]
			);
		}

		return $result;
	}

	/**
	 * @param string $source
	 * @param string $destination
	 * @param bool $overwrite
	 *
	 * @return array
	 */
	protected static function copyStorageFolder( string $source, string $destination, bool $overwrite ): array
	{
		static $copied = false;
		if ( !FileSystem::Exists( $destination ) ) {
			FileSystem::Mkdir( $destination );
		}
		$items = scandir( $source );
		if ( count( $items ) ) {
			foreach ( $items as $item ) {
				if ( $item != '.' && $item != '..' ) {
					try {
						if ( is_dir( $source . '/' . $item ) ) {
							$result = self::copyStorageFolder( $source . '/' . $item, $destination . '/' . $item, $overwrite );
						}
						else {
							if ( !( FileSystem::Exists( $destination . '/' . $item ) && !$overwrite ) ) {
								$copied = FileSystem::Copy( $source . '/' . $item, $destination . '/' . $item );
							}
						}
					}
					catch ( Exception $x ) {
					}
				}
			}
		}
		$result[ 'copied' ] = $copied;

		return $result;
	}

	/**
	 * @param string $file
	 * @param string $output
	 * @param bool $compress
	 *
	 * @return void
	 * @throws \Less_Exception_Parser
	 * @throws \Exception
	 */
	public static function compileLessFile( string $file, string $output, bool $compress = false )
	{
		include_once( SOBI_PATH . '/lib/services/thirdparty/less/Autoloader.php' );
		Less_Autoloader::register();

		if ( $compress ) {
			$options = [
				'compress'   => true,
				'strictMath' => true,
			];
		}
		else {
			$options = [];
		}
		$parser = new Less_Parser( $options );
		$parser->parseFile( $file );
		$css = $parser->getCss();
		if ( FileSystem::Exists( $output ) ) {
			FileSystem::Delete( $output );
		}
		FileSystem::Write( $output, $css );
	}
}