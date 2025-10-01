<?php
/**
 * @package: Sobi Framework
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006-2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 1 Mar 2021 by Radek Suski
 * @modified 27 February 2024 by Sigrid Suski
 */

//declare( strict_types=1 );

namespace Sobi\Application\Joomla\Database;


defined( 'SOBI' ) || exit( 'Restricted access' );

use Joomla\CMS\Factory as JFactory;
use Sobi\{C,
	Framework,
	Lib\Instance,
	Error\Exception,
	Lib\ParamsByName
};
use Sobi\Utils\{
	Serialiser,
	StringUtils,
	Type
};
use stdClass;

/**
 * Class JMySQLi
 * @package Sobi\Database
 */
class MySQLi
{
	use Instance;
	use ParamsByName;

	/*** @var \Joomla\Database\Mysqli\MysqliDriver */
	protected $db = null;
	/*** @var string */
	protected $prefix = '#__';
	/*** @var int */
	protected $count = 0;
	/*** @var \mysqli */
	protected $mysql;

	/**
	 * @return MySQLi
	 */
	protected function __construct()
	{
		$this->db = JFactory::getDBO();

		return $this;
	}

	/**
	 * @return MySQLi
	 */
	public static function & getInstance(): MySQLi
	{
		return self::Instance();
	}

	/**
	 * Returns the error number.
	 *
	 * @return int
	 * @deprecated
	 */
	public function getErrorNum(): ?int
	{
		return $this->db->getErrorNum();
	}

	/**
	 * Returns the error message.
	 *
	 * @return string
	 * @deprecated
	 */
	public function getErrorMsg(): ?string
	{
		return $this->db->getErrorMsg();
	}

	/**
	 * Proxy pattern.
	 *
	 * @param string $method
	 * @param array $args
	 *
	 * @return mixed
	 * @throws \Sobi\Error\Exception
	 */
	public function __call( string $method, array $args )
	{
		if ( $this->db && method_exists( $this->db, $method ) ) {
			$Args = [];
			// http://www.php.net/manual/en/function.call-user-func-array.php#91503
			foreach ( $args as $k => &$arg ) {
				$Args[ $k ] = &$arg;
			}

			return call_user_func_array( [ $this->db, $method ], $Args );
		}
		else {
			throw new Exception( Framework::Txt( 'CALL_TO_UNDEFINED_CLASS_METHOD', get_class( $this->db ), $method ) );
		}
	}

	/**
	 * Returns a database escaped string.
	 *
	 * @param string $text string to be escaped
	 * @param bool $esc extra escaping
	 *
	 * @return string
	 */
	public function getEscaped( string $text, bool $esc = false ): string
	{
		return $this->db->getEscaped( StringUtils::Clean( $text ), $esc );
	}

	/**
	 * Returns a database escaped string.
	 *
	 * @param string|null $text string to be escaped
	 * @param bool $esc extra escaping
	 *
	 * @return string
	 */
	public function escape( string $text, bool $esc = false ): string
	{
		return strlen( (string) $text ) ? $this->db->escape( $text, $esc ) : C::ES;
	}

	/**
	 * Returns database null date format.
	 * @return string Quoted null date string
	 */
	public function getNullDate(): string
	{
		return $this->db->getNullDate();
	}

	/**
	 * Sets the SQL query string for later execution.
	 *
	 * @param string $sql
	 *
	 * @return MySQLi
	 * @throws \Sobi\Error\Exception
	 */
	public function & setQuery( string $sql ): MySQLi
	{
		$sql = str_replace( 'spdb', $this->prefix . 'sobipro', $sql );
		$sql = str_replace( 'NOW()', '\'' . gmdate( 'Y-m-d H:i:s' ) . '\'', $sql );
		try {
			$this->db->setQuery( $sql );
		}
		catch ( \Exception $x ) {
			throw new Exception( $x->getMessage() );
		}

		return $this;
	}

	/**
	 * @param string $file
	 * @param string $prefix
	 * @param string $extension
	 *
	 * @return array
	 * @throws \Sobi\Error\Exception
	 */
	public function loadFile( string $file, string $prefix, string $extension ): array
	{
		$sql = file_get_contents( $file );
		$sql = explode( "\n", $sql );
		$log = [];
		if ( is_array( $sql ) && count( $sql ) ) {
			foreach ( $sql as $query ) {
				if ( strlen( (string) $query ) ) {
					$this->exec( str_replace( $prefix . '_', $this->prefix . $extension . '_', $query ) );
					$log[] = $query;
				}
			}
		}

		return $log;
	}

	/**
	 * Alias for select where $distinct is true.
	 *
	 * @param string | array $toSelect
	 * @param string | array $tables
	 * @param string | array $where
	 * @param string|null $order
	 * @param int $limit
	 * @param int $limitStart
	 * @param string $group
	 *
	 * @return MySQLi
	 * @throws \Sobi\Error\Exception
	 */
	public function dselect( $toSelect, $tables, $where = C::ES, string $order = C::ES, int $limit = 0, int $limitStart = 0, string $group = C::ES ): MySQLi
	{
		return $this->select( $toSelect, $tables, $where, $order, $limit, $limitStart, true, $group );
	}

	/**
	 * Creates a "select" SQL query to perform a full text search.
	 *
	 * @param string | array $toSelect - table rows to select
	 * @param string | array $tables - from which table(s)
	 * @param string | array $where - SQL select condition
	 * @param string | array $match - match() clause
	 * @param string $against - against() clause
	 * @param bool $booleanMode - search modifier
	 * @param string $order - order by
	 * @param int $limit - maximal number of rows
	 * @param int $limitStart - start position
	 * @param bool $distinct
	 * @param string $groupBy - column to group by
	 *
	 * @return MySQLi
	 * @throws \Sobi\Error\Exception
	 */
	public function & selectFullText( $toSelect, $tables, $where = C::ES, $match = C::ES, string $against = C::ES, bool $booleanMode = true, string $order = C::ES, int $limit = 0, int $limitStart = 0, bool $distinct = true, string $groupBy = C::ES ): MySQLi
	{
		$limits = null;
		$ordering = null;
		$where = $where ? $this->where( $where ) : C::ES;
		$where = $where ? "WHERE {$where}" : null;
		$distinct = $distinct ? ' DISTINCT ' : null;
		$tables = is_array( $tables ) ? implode( ', ', $tables ) : $tables;
		$toSelect = is_array( $toSelect ) ? implode( ', ', $toSelect ) : $toSelect;
//		if ( $against != '%' ) {
		$match = is_array( $match ) ? implode( ', ', $match ) : $match;
		$match = ( $where ? 'AND ' : '' ) . "MATCH( {$match} )";
		$against = "AGAINST('{$against}'" . ( $booleanMode ? ' IN BOOLEAN MODE' : '' ) . ")";
//		}
//		else {
//			$match = $against = null;
//		}
		$groupBy = $groupBy ? "GROUP BY {$groupBy}" : null;
		$limitStart = $limitStart < 0 ? 0 : $limitStart;
		if ( $limit ) {
			$limits = "LIMIT {$limitStart}, {$limit}";
		}
		if ( $order ) {
			$n = false;
			if ( strstr( $order, '.num' ) ) {
				$order = str_replace( '.num', C::ES, $order );
				$n = true;
			}
			if ( strstr( (string) $order, ',' ) ) {
				$o = explode( ',', $order );
				$order = [];
				foreach ( $o as $p ) {
					if ( strstr( (string) $p, '.' ) ) {
						$p = explode( '.', $p );
						$order[] = $p[ 0 ] . ' ' . strtoupper( $p[ 1 ] );
					}
					else {
						$order[] = $p;
					}
				}
				$order = implode( ', ', $order );
			}
			else {
				if ( strstr( (string) $order, '.' ) && ( stristr( $order, 'asc' ) || stristr( $order, 'desc' ) ) ) {
					$order = explode( '.', $order );
					$ext = array_pop( $order );
					if ( $n ) {
						$order = implode( '.', $order ) . '+0 ' . $ext;
					}
					else {
						$order = implode( '.', $order ) . ' ' . $ext;
					}
				}
				else {
					if ( $n ) {
						$order .= '+0';
					}
				}
			}
			$ordering = "ORDER BY {$order}";
		}

		// throw new Exception( "SELECT {$distinct}{$toSelect} FROM {$tables} {$where} {$match} {$against} {$groupBy} {$ordering} {$limits}" );

		$this->setQuery( "SELECT {$distinct}{$toSelect} FROM {$tables} {$where} {$match} {$against} {$groupBy} {$ordering} {$limits}" );

		return $this;
	}

	/**
	 * Creates a "select" SQL query.
	 *
	 * @param string | array $toSelect - table rows to select
	 * @param string | array $tables - from which table(s)
	 * @param string | array $where - SQL select condition
	 * @param string $order - order by
	 * @param int $limit - maximal number of rows
	 * @param int $limitStart - start position
	 * @param bool $distinct - clear??
	 * @param string $groupBy - column to group by
	 *
	 * @return MySQLi
	 * @throws \Sobi\Error\Exception
	 */
	public function & select( $toSelect, $tables, $where = C::ES, string $order = C::ES, int $limit = 0, int $limitStart = 0, bool $distinct = false, string $groupBy = C::ES ): MySQLi
	{
		$limits = null;
		$ordering = null;
		$where = $where ? $this->where( $where ) : C::ES;
		$where = $where ? "WHERE {$where}" : null;
		$distinct = $distinct ? ' DISTINCT ' : null;
		$tables = is_array( $tables ) ? implode( ', ', $tables ) : $tables;
		$groupBy = $groupBy ? "GROUP BY {$groupBy}" : null;
		$limitStart = $limitStart < 0 ? 0 : $limitStart;
		if ( $limit ) {
			$limits = "LIMIT {$limitStart}, {$limit}";
		}
		if ( is_array( $toSelect ) ) {
			$toSelect = implode( ',', $toSelect );
		}
		if ( $order ) {
			$n = false;
			if ( strstr( $order, '.num' ) ) {
				$order = str_replace( '.num', C::ES, $order );
				$n = true;
			}
			if ( strstr( (string) $order, ',' ) ) {
				$o = explode( ',', $order );
				$order = [];
				foreach ( $o as $p ) {
					if ( strstr( (string) $p, '.' ) ) {
						$p = explode( '.', $p );
						$order[] = $p[ 0 ] . ' ' . strtoupper( $p[ 1 ] );
					}
					else {
						$order[] = $p;
					}
				}
				$order = implode( ', ', $order );
			}
			else {
				if ( strstr( (string) $order, '.' ) && ( stristr( $order, 'asc' ) || stristr( $order, 'desc' ) ) ) {
					$order = explode( '.', $order );
					$ext = array_pop( $order );
					if ( $n ) {
						$order = implode( '.', $order ) . '+0 ' . $ext;
					}
					else {
						$order = implode( '.', $order ) . ' ' . $ext;
					}
				}
				else {
					if ( $n ) {
						$order .= '+0';
					}
				}
			}
			$ordering = "ORDER BY {$order}";
		}
		$this->setQuery( "SELECT {$distinct}{$toSelect} FROM {$tables} {$where} {$groupBy} {$ordering} {$limits}" );

		return $this;
	}

	/**
	 * Creates a "delete" SQL query.
	 *
	 * @param string $table - in which table
	 * @param string | array $where - SQL delete condition
	 * @param int $limit - maximal number of rows to delete
	 *
	 * @return MySQLi
	 * @throws \Sobi\Error\Exception
	 */
	public function & delete( string $table, $where, int $limit = 0 ): MySQLi
	{
		$where = $this->where( $where );
		$limit = $limit ? "LIMIT $limit" : null;
		try {
			$this->exec( "DELETE FROM {$table} WHERE {$where} {$limit}" );
		}
		catch ( \Exception $e ) {
			throw new Exception( $e->getMessage() );
		}

		return $this;
	}

	/**
	 * Creates a "drop table" SQL query.
	 *
	 * @param string $table - in which table
	 * @param bool $ifExists
	 *
	 * @return MySQLi
	 * @throws \Sobi\Error\Exception
	 */
	public function & drop( string $table, bool $ifExists = true ): MySQLi
	{
		$ifExists = $ifExists ? 'IF EXISTS' : null;
		try {
			$this->exec( "DROP TABLE {$ifExists} {$table}" );
		}
		catch ( \Exception $e ) {
			throw new Exception( $e->getMessage() );
		}

		return $this;
	}

	/**
	 * Creates a "truncate table" SQL query.
	 *
	 * @param string $table - in which table
	 *
	 * @return MySQLi
	 * @throws \Sobi\Error\Exception
	 */
	public function & truncate( string $table ): MySQLi
	{
		try {
			$this->exec( "TRUNCATE TABLE {$table}" );
		}
		catch ( \Exception $e ) {
			throw new Exception( $e->getMessage() );
		}

		return $this;
	}

	/**
	 * Creates where condition from a given array.
	 *
	 * @param array|string $where - array with values. array( 'id' => 5, 'published' => 1 ) OR array( 'id' => array( 5, 3, 4 ), 'published' => 1 )
	 * @param string $andor - join conditions through AND or OR
	 *
	 * @return string
	 */
	public function where( $where, string $andor = 'AND' ): string
	{
		if ( is_array( $where ) ) {
			$w = [];
			foreach ( $where as $col => $val ) {
				$equal = '=';
				$not = false;
				// sort of workaround for incompatibility between RC3 and RC4
				if ( $col == 'language' && !( count( (array) $val ) ) ) {
					$val = 'en-GB';
				}
				/* like:
					 * 	array( '!key' => 'value' )
					 * 	produces sql query with
					 * 	key NOT 'value'
					 */
				if ( strpos( (string) $col, '!' ) !== false && strpos( (string) $col, '!' ) == 0 ) {
					$col = trim( str_replace( '!', C::ES, $col ) );
					$not = true;
				}
				/* current means get previous query */
				if ( is_string( $val ) && $val == '@CURRENT' ) {
					$n = $not ? 'NOT' : null;
					$val = $this->db->getQuery();
					$w[] = " ( {$col} {$n} IN ( {$val} ) ) ";
				}
				/* see SPDb#valid() */
				else {
					if ( $col === '@VALID' ) {
						$w[] = $val;
					}
					else {
						if ( is_numeric( $col ) ) {
							$w[] = $this->escape( (string) $val );
						}
						/* like:
							 * 	array( 'key' => array( 'from' => 1, 'to' => 10 ) )
							 * 	produces sql query with
							 * 	key BETWEEN 1 AND 10
							 */
						else {
							if ( is_array( $val ) && ( isset( $val[ 'from' ] ) || isset( $val[ 'to' ] ) ) ) {
								if ( ( isset( $val[ 'from' ] ) && isset( $val[ 'to' ] ) ) && $val[ 'from' ] != C::NO_VALUE && $val[ 'to' ] != C::NO_VALUE ) {
									$val[ 'to' ] = $this->escape( (string) $val[ 'to' ] );
									$val[ 'from' ] = $this->escape( (string) $val[ 'from' ] );
									$w[] = " ( {$col} * 1.0 BETWEEN {$val['from']} AND {$val['to']} ) ";
								}
								else {
									if ( $val[ 'from' ] != C::NO_VALUE && $val[ 'to' ] == C::NO_VALUE ) {
										$val[ 'from' ] = $this->escape( (string) $val[ 'from' ] );
										$w[] = " ( {$col} * 1.0 > {$val['from']} ) ";
									}
									else {
										if ( $val[ 'from' ] == C::NO_VALUE && $val[ 'to' ] != C::NO_VALUE ) {
											$val[ 'to' ] = $this->escape( (string) $val[ 'to' ] );
											$w[] = " ( {$col} * 1.0 < {$val['to']} ) ";
										}
									}
								}

							}
							/* like:
								 * 	array( 'key' => array( 1,2,3,4 ) )
								 * 	produces sql query with
								 * 	key IN ( 1,2,3,4 )
								 */
							else {
								if ( is_array( $val ) ) {
									$v = [];
									foreach ( $val as $k ) {
										if ( strlen( (string) $k ) || $k == C::NO_VALUE ) {
											$k = $k == C::NO_VALUE ? null : $k;
											$k = $this->escape( (string) $k );
											$v[] = "'{$k}'";
										}
									}
									$val = implode( ',', $v );
									$n = $not ? 'NOT' : null;
									$w[] = " ( {$col} {$n} IN ( {$val} ) ) ";
								}
								else {
									/* changes the equal sign */
									$n = $not ? '!' : null;
									/* is lower */
									if ( strpos( (string) $col, '<' ) ) {
										$equal = '<';
										$col = trim( str_replace( '<', C::ES, $col ) );
									}
									/* is greater */
									else {
										if ( strpos( (string) $col, '>' ) ) {
											$equal = '>';
											$col = trim( str_replace( '>', C::ES, $col ) );
										}
										/* is like */
										else {
											if ( strpos( (string) $val, '%' ) !== false ) {
												if ( $n == '!' ) {
													$n = null;
													$equal = 'NOT LIKE';
												}
												else {
													$equal = 'LIKE';
												}
											}
											/* regular expressions handling
												  * array( 'key' => 'REGEXP:^search$' )
												  */
											else {
												if ( strpos( (string) $val, 'REGEXP:' ) !== false ) {
													$equal = 'REGEXP';
													$val = str_replace( 'REGEXP:', C::ES, $val );
												}
												else {
													if ( strpos( (string) $val, 'RLIKE:' ) !== false ) {
														$equal = $not ? 'NOT RLIKE' : 'RLIKE';
														$val = str_replace( 'RLIKE:', C::ES, $val );
														$w[] = " ( {$col} {$equal} '{$val}' ) ";
														continue;
													}
												}
											}
										}
									}
									/* ^^ regular expressions handling ^^ */

									/* SQL functions within the query
										  * array( 'created' => 'FUNCTION:NOW()' )
										  */
									if ( strstr( (string) $val, 'FUNCTION:' ) ) {
										$val = str_replace( 'FUNCTION:', C::ES, $val );
									}
									else {
										$val = $this->escape( (string) $val );
										$val = "'{$val}'";
									}
									$w[] = " ( {$col} {$n}{$equal}{$val} ) ";
								}
							}
						}
					}
				}
			}
			$where = implode( " {$andor} ", $w );
		}

		return $where;
	}


	/**
	 * Sample usage
	 *        $fields = array(
	 *            'url' => 'VARCHAR(255) NOT NULL',
	 *            'crid' => 'INT(11) NOT NULL AUTO_INCREMENT',
	 *            'state' => 'TINYINT(1) NOT NULL'
	 *        );
	 *        $keys = array(
	 *            'crid' => 'primary',
	 *            'url' => 'unique'
	 *        );
	 *        SPFactory::db()->createTable( 'crawler', $fields, $keys, true, 'MyISAM' );
	 * Would create query like:
	 *         CREATE TABLE IF NOT EXISTS `#__sobipro_crawler` (
	 *            `url`   VARCHAR(255) NOT NULL,
	 *            `crid`  INT(11)      NOT NULL AUTO_INCREMENT,
	 *            `state` TINYINT(1)   NOT NULL,
	 *            PRIMARY KEY (`crid`),
	 *            UNIQUE KEY `url` (`url`)
	 *         ) ENGINE = MyISAM DEFAULT CHARSET = utf8;
	 *
	 * @param string $name - table name without any prefix
	 * @param array $fields - array with fields definition like: $fields[ 'url' ] = 'VARCHAR(255) NOT NULL';
	 * @param array $keys - optional array with keys defined like: $keys[ 'url' ] = 'unique'; || $keys[ 'url, crid' ] = 'primary';
	 * @param bool $notExists - adds "CREATE TABLE IF NOT EXISTS"
	 * @param string $engine - optional engine type
	 * @param string $charset
	 *
	 * @return $this
	 * @throws \Sobi\Error\Exception
	 */
	public function & createTable( string $name, array $fields, array $keys = [], bool $notExists = false, string $engine = C::ES, string $charset = 'utf8' ): MySQLi
	{
		$name = "#__sobipro_{$name}";
		$query = $notExists ? "CREATE TABLE IF NOT EXISTS `{$name}` " : "CREATE TABLE `{$name}` ";
		$subQuery = null;
		$count = count( $fields );
		$i = 0;
		foreach ( $fields as $name => $definition ) {
			$i++;
			$subQuery .= "`{$name}` {$definition}";
			if ( $i < $count || count( $keys ) ) {
				$subQuery .= ', ';
			}
			else {
				$subQuery .= ' ';
			}
		}
		if ( count( $keys ) ) {
			$count = count( $keys );
			$i = 0;
			foreach ( $keys as $key => $type ) {
				$type = strtoupper( $type );
				if ( strstr( (string) $key, ',' ) ) {
					$_keys = explode( ',', $key );
					foreach ( $_keys as $i => $subkey ) {
						$_keys[ $i ] = "`{$subkey}`";
					}
					$key = implode( ',', $_keys );
				}
				else {
					$key = "`{$key}`";
				}
				$subQuery = "{$type} KEY ( {$key} )";
				if ( $i < $count ) {
					$subQuery .= ', ';
				}
				else {
					$subQuery .= ' ';
				}

			}
		}
		$query .= "( {$subQuery} ) ";
		if ( $engine ) {
			$query .= " ENGINE = {$engine} ";
		}
		$query .= "DEFAULT CHARSET = {$charset};";
		$this->exec( $query );

		return $this;
	}

	/**
	 * @param array $val
	 *
	 * @return string
	 */
	public function argsOr( array $val ): string
	{
		$cond = [];
		foreach ( $val as $i => $k ) {
			$equal = ' = ';
			if ( strpos( $i, '<' ) ) {
				$equal = '<';
				$i = trim( str_replace( '<', C::ES, $i ) );
			}
			/* is greater */
			else {
				if ( strpos( $i, '>' ) ) {
					$equal = '>';
					$i = trim( str_replace( '>', C::ES, $i ) );
				}
			}
			if ( is_string( $k ) && strpos( $k, '%' ) !== false ) {
				$equal = ' LIKE ';
				$k = "'$k'";
			}
			if ( $i == '@VALID' ) {
				$cond[] .= $k;
			}
			else {
				$cond[] .= $i . $equal . $k;
			}
		}
		$cond = implode( ' OR ', $cond );

		return '( ' . $cond . ' )';
	}

	/**
	 * Creates a "update" SQL query.
	 *
	 * @param string $table - table to update
	 * @param array $set - two-dimensional array with table row name to update => new value
	 * @param array|string $where - SQL update condition
	 * @param int $limit
	 *
	 * @return \Sobi\Application\Joomla\Database\MySQLi
	 * @throws Exception
	 */
	public function & update( string $table, array $set, $where, int $limit = 0 ): MySQLi
	{
		$change = [];
		$where = $this->where( $where );
		foreach ( $set as $var => $state ) {
			if ( is_array( $state ) || is_object( $state ) ) {
				$state = Serialiser::serialise( $state );
			}
			/* false must be 0 and not '' for strict database */
			$var = is_bool( $var ) ? ( $var ? 1 : 0 ) : $this->escape( (string) $var );
			$state = is_bool( $state ) ? ( $state ? 1 : 0 ) : $this->escape( (string) $state );
			if ( is_string( $state ) && strstr( $state, 'FUNCTION:' ) ) {
				$state = str_replace( 'FUNCTION:', C::ES, $state );
			}
			else {
				if ( ( strstr( (string) $var, 'valid' ) || stristr( $var, 'time' ) ) && strlen( (string) $state ) == 0 ) {
					$state = '\'0000-00-00 00:00:00\'';
				}
				else {
					if ( strlen( (string) $state ) == 2 && $state == '++' ) {
						$state = "{$var} + 1";
					}
					else {
						$state = "'{$state}'";
					}
				}
			}
			$change[] = "{$var} = {$state}";
		}
		$change = implode( ',', $change );
		$l = $limit ? " LIMIT {$limit} " : null;
		$this->exec( "UPDATE {$table} SET {$change} WHERE {$where}{$l}" );

		return $this;
	}

	/**
	 * Creates a "replace" SQL query.
	 *
	 * @param string $table - table name
	 * @param array $values - two-dimensional array with table row name => value
	 *
	 * @return \Sobi\Application\Joomla\Database\MySQLi
	 * @throws \Sobi\Error\Exception
	 */
	public function & replace( string $table, array $values ): MySQLi
	{
		$v = [];
		foreach ( $values as $val ) {
			if ( is_array( $val ) || is_object( $val ) ) {
				$val = Serialiser::Serialise( $val );
			}
			$val = is_bool( $val ) ? ( $val ? 1 : 0 ) : $this->escape( (string) $val );
			if ( strstr( (string) $val, 'FUNCTION:' ) ) {
				$v[] = str_replace( 'FUNCTION:', C::ES, $val );
			}
			else {
				$v[] = "'{$val}'";
			}
		}
		$v = implode( ',', $v );
		try {
			$this->exec( "REPLACE INTO {$table} VALUES ({$v})" );
		}
		catch ( \Exception $e ) {
			throw new Exception( $e->getMessage() );
		}

		return $this;
	}

	/**
	 * Creates a "insert" SQL query.
	 *
	 * @param string $table - table name
	 * @param array $values - two-dimensional array with table row name => value
	 * @param bool $ignore - adds "IGNORE" after "INSERT" command
	 * @param bool $normalize - if the $values is a two-dimensional, array and it's not complete - fit to the columns
	 *
	 * @return MySQLi
	 * @throws \Sobi\Error\Exception
	 */
	public function & insert( string $table, array $values, bool $ignore = false, bool $normalize = true ): MySQLi
	{
		$ignore = $ignore ? 'IGNORE ' : null;
		if ( $normalize ) {
			$this->normalize( $table, $values );
		}
		foreach ( $values as $val ) {
			if ( is_array( $val ) || is_object( $val ) ) {
				$val = Serialiser::Serialise( $val );
			}
			$val = is_bool( $val ) ? ( $val ? 1 : 0 ) : $this->escape( (string) $val );
			if ( strstr( (string) $val, 'FUNCTION:' ) ) {
				$v[] = str_replace( 'FUNCTION:', C::ES, $val );
			}
			else {
				$v[] = "'{$val}'";
			}
		}
		$v = implode( ',', $v );
		try {
			$this->exec( "INSERT {$ignore} INTO {$table} VALUES ({$v})" );
		}
		catch ( \Exception $e ) {
			throw new Exception( $e->getMessage() );
		}

		return $this;
	}

	/**
	 * Fits a two dimensional array to the necessary columns of the given table.
	 *
	 * @param string $table - table name
	 * @param array $values
	 *
	 * @return \Sobi\Application\Joomla\Database\MySQLi
	 * @throws \Sobi\Error\Exception
	 */
	public function & normalize( string $table, array &$values ): MySQLi
	{
		$cols = $this->getColumns( $table, true );
		$normalized = [];
		/* sort the properties in the same order */
		foreach ( $cols as $field => $data ) {
			$normalized[ $field ] = $values[ $field ] ?? ( $data[ 'Default' ] ?? Type::SQLNull( $data[ 'Type' ] ) );
		}
		$values = $normalized;

		return $this;
	}

	/**
	 * Creates a "insert" SQL query with multiple values.
	 * Attention: if normalize is false, all different columns must be set for all rows
	 * [a,b] and [a,c] from [a,b,c] does not work
	 * [a,b] and [a,b] from [a,b,c] does work
	 * [a,b,c] and [a,b,c] from [a,b,c] does not work
	 * Attention: if normalize is true, non-set values will be cleared in the table!!
	 *
	 * @param string $table - table name
	 * @param array $values - one-dimensional array with two-dimensional array with table row name => value
	 * @param bool $update - update existing row if cannot insert it because of duplicate primary key
	 * @param bool $ignore - adds "IGNORE" after "INSERT" command
	 * @param bool $normalize
	 *
	 * @return MySQLi
	 * @throws \Sobi\Error\Exception
	 */
	public function & insertArray( string $table, array $values, bool $update = false, bool $ignore = false, bool $normalize = true ): MySQLi
	{
		$ignore = $ignore ? 'IGNORE ' : null;
		$rows = [];
		foreach ( $values as $arr ) {
			$v = [];
			$vars = [];
			$k = [];
			if ( $normalize ) {
				$this->normalize( $table, $arr );
			}
			foreach ( $arr as $var => $val ) {
				if ( is_array( $val ) || is_object( $val ) ) {
					$val = Serialiser::serialise( $val );
				}
				$vars[] = "{$var} = VALUES( {$var} )";
				$k[] = $var;
				$val = is_bool( $val ) ? ( $val ? 1 : 0 ) : $this->escape( (string) $val );
				if ( strstr( (string) $val, 'FUNCTION:' ) ) {
					$v[] = str_replace( 'FUNCTION:', C::ES, $val );
				}
				else {
					$v[] = "'{$val}'";
				}
			}
			$rows[] = implode( ',', $v );
		}
		$vars = implode( ', ', $vars );
		$rows = implode( " ), \n ( ", $rows );
		$k = implode( '`,`', $k );
		$update = $update ? "ON DUPLICATE KEY UPDATE {$vars}" : null;
		try {
			$this->exec( "INSERT {$ignore} INTO {$table} ( `{$k}` ) VALUES ({$rows}) {$update}" );
		}
		catch ( \Exception $e ) {
			throw new Exception( $e->getMessage() );
		}

		return $this;
	}

	/**
	 * Creates a "insert" SQL query with update if cannot insert it because of duplicate primary key.
	 *
	 * @param string $table - table name
	 * @param array $values - two-dimensional array with table row name => value
	 *
	 * @return MySQLi
	 * @throws \Sobi\Error\Exception
	 */
	public function & insertUpdate( string $table, array $values ): MySQLi
	{
		$v = [];
		$c = [];
		$k = [];
		foreach ( $values as $var => $val ) {
			if ( is_array( $val ) || is_object( $val ) ) {
				$val = Serialiser::Serialise( $val );
			}
			/* false must be 0 and not '' for strict database */
			$val = is_bool( $val ) ? ( $val ? 1 : 0 ) : $this->escape( (string) $val );
			if ( strstr( (string) $val, 'FUNCTION:' ) ) {
				$f = str_replace( 'FUNCTION:', C::ES, $val );
				$v[] = $f;
				$c[] = "{$var} = {$f}";
			}
			else {
				if ( ( strstr( (string) $var, 'valid' ) || stristr( $var, 'time' ) ) && strlen( (string) $val ) == 0 ) {
					$v[] = '\'0000-00-00 00:00:00\'';
				}
				else {
					$v[] = "'{$val}'";
					$c[] = "{$var} = '{$val}'";
				}
			}
			$k[] = "`{$var}`";

		}
		$v = implode( ',', $v );
		$c = implode( ',', $c );
		$k = implode( ',', $k );
		try {
			$this->exec( "INSERT INTO {$table} ({$k}) VALUES ({$v}) ON DUPLICATE KEY UPDATE {$c}" );
		}
		catch ( \Exception $e ) {
			throw new Exception( $e->getMessage() );
		}

		return $this;
	}

	/**
	 * Returns current query.
	 *
	 * @return string
	 */
	public function getQuery(): string
	{
		return str_replace( $this->prefix, $this->db->getPrefix(), $this->db->getQuery() );
	}

	/**
	 * Returns queries counter.
	 *
	 * @return int
	 */
	public function getCount(): int
	{
		return $this->count;
	}

	/**
	 * Execute the query.
	 *
	 * @return bool database resource or <var>false</var>.
	 */
	public function query(): bool
	{
		$this->count++;

		return $this->db->execute();
	}

	/**
	 * Loads the first field of the first row returned by the query.
	 *
	 * @return string
	 * @throws \Sobi\Error\Exception
	 */
	public function loadResult(): ?string
	{
		try {
			$r = $this->db->loadResult();
			$r = (string) $r;
			$this->count++;
		}
		catch ( \Exception $e ) {
			throw new Exception( $e->getMessage() );
		}

		return $r ?? C::ES;
	}

	/**
	 * Loads an array of single field results into an array.
	 *
	 * @return array
	 * @throws \Sobi\Error\Exception
	 */
	public function loadResultArray(): ?array
	{
		try {
			$r = $this->db->loadColumn();
			$this->count++;
		}
		catch ( \Exception $e ) {
			throw new Exception( $e->getMessage() );
		}

		return $r;
	}

	/**
	 * Loads a assoc list of database rows.
	 *
	 * @param string $key field name of a primary key
	 *
	 * @return array If <var>key</var> is empty as sequential list of returned records.
	 * @throws Exception
	 */
	public function loadAssocList( string $key = C::ES ): array
	{
		try {
			$r = $this->db->loadAssocList( $key );
			$this->count++;
		}
		catch ( \Exception $e ) {
			throw new Exception( $e->getMessage() );
		}

		return $r;
	}

	/**
	 * Loads the first row of a query into an object.
	 *
	 * @return \stdClass
	 * @throws Exception
	 */
	public function loadObject(): stdClass
	{
		try {
			$r = $this->db->loadObject();
			$this->count++;
		}
		catch ( \Exception $e ) {
			throw new Exception( $e->getMessage() );
		}
		if ( $r && is_object( $r ) ) {
			$attr = get_object_vars( $r );
			foreach ( $attr as $property => $value ) {
				if ( is_string( $value ) && strstr( (string) $value, '"' ) ) {
					$r->$property = StringUtils::Clean( $value );
				}
			}
		}

		return $r ?? new stdClass();
	}

	/**
	 * Loads a list of database objects.
	 *
	 * @param string $key
	 *
	 * @return array If <var>key</var> is empty as sequential list of returned records.
	 * @throws Exception
	 */
	public function loadObjectList( string $key = C::ES ): array
	{
		try {
			$r = $this->db->loadObjectList( $key );
			$this->count++;
		}
		catch ( \Exception $e ) {
			throw new Exception( $e->getMessage() );
		}

		return $r ?? [];
	}

	/**
	 * Loads the first row of the query.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function loadRow(): array
	{
		try {
			$r = $this->db->loadRow();
			$this->count++;
		}
		catch ( \Exception $e ) {
			throw new Exception( $e->getMessage() );
		}

		return $r ?? [];
	}

	/**
	 * Loads a list of database rows (numeric column indexing).
	 *
	 * @param string $key field name of a primary key
	 *
	 * @return array If <var>key</var> is empty as sequential list of returned records.
	 * @throws Exception
	 */
	public function loadRowList( string $key = C::ES ): array
	{
		try {
			$r = $this->db->loadRowList( $key );
			$this->count++;
		}
		catch ( \Exception $e ) {
			throw new Exception( $e->getMessage() );
		}

		return $r ?? [];
	}

	/**
	 * Returns an error statement.
	 *
	 * @return string
	 * @deprecated
	 */
	public function stderr(): string
	{
		return $this->db->stderr();
	}

	/**
	 * Returns the ID generated from the previous insert operation.
	 *
	 * @return int
	 */
	public function insertId(): int
	{
		return $this->db->insertid();
	}

	/**
	 * executing query (update/insert etc).
	 *
	 * @param string $query - query to execute
	 *
	 * @return mixed
	 * @throws \Sobi\Error\Exception
	 */
	public function exec( string $query )
	{
		try {
			$this->setQuery( $query );
			$r = $this->execute();
		}
		catch ( \Exception $e ) {
			throw new Exception( $e->getMessage() );
		}

		return $r;
	}

	/**
	 * Returns all rows of given table.
	 *
	 * @param string $table
	 * @param bool $assoc
	 *
	 * @return array
	 * @throws \Sobi\Error\Exception
	 */
	public function getColumns( string $table, bool $assoc = false ): array
	{
		static $cache = [];
		if ( !( isset( $cache[ $table ][ $assoc ] ) ) ) {
			$this->setQuery( "SHOW COLUMNS FROM {$table}" );
			try {
				$cache[ $table ][ $assoc ] = $assoc ? $this->loadAssocList( 'Field' ) : $this->loadResultArray();
			}
			catch ( \Exception $e ) {
				throw new Exception( $e->getMessage() );
			}
		}

		return $cache[ $table ][ $assoc ] ?? [];
	}

	/**
	 * Rolls back the current transaction, canceling its changes.
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function rollback(): bool
	{
		return $this->exec( 'ROLLBACK;' ) !== false;
	}

	/**
	 * Begins a new transaction.
	 *
	 * @return bool
	 */
	public function transaction(): bool
	{
		return true;//$this->exec( 'START TRANSACTION;' ) !== false ? true : false;
	}

	/**
	 * Commits the current transaction, making its changes permanent.
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function commit(): bool
	{
		return $this->exec( 'COMMIT;' ) !== false;
	}

	/**
	 * Returns current datetime in database acceptable format.
	 *
	 * @return string
	 */
	public function now(): string
	{
		return date( 'Y-m-d H:i:s' );
	}

	/**
	 * Creates syntax for join two tables.
	 *
	 * @param array $params - two cells array with table name <var>table</var>, alias name <var>as</var> and common key <var>key</var>
	 * @param string $through - join direction (left/right)
	 *
	 * @return string
	 */
	public function join( array $params, string $through = 'left' ): string
	{
		$through = strtoupper( $through );
		$join = null;
		if ( count( $params ) > 2 ) {
			$joins = [];
			$c = 0;
			foreach ( $params as $table ) {
				if ( isset( $table[ 'table' ] ) ) {
					$join = "\n {$table['table']} AS {$table['as']} ";
					if ( $c > 0 ) {
						if ( isset( $table[ 'key' ] ) ) {
							if ( is_array( $table[ 'key' ] ) ) {
								$join .= " ON {$table['key'][0]} =  {$table['key'][1]} ";
							}
							else {
								$join .= " ON {$params[0]['as']}.{$table['key']} =  {$table['as']}.{$table['key']} ";
							}
						}
					}
					$joins[] = $join;
				}
				$c++;
			}
			$join = implode( " {$through} JOIN ", $joins );
		}
		else {
			if (
				( isset( $params[ 0 ][ 'table' ] ) && isset( $params[ 0 ][ 'as' ] ) && isset( $params[ 0 ][ 'key' ] ) )
				&& ( isset( $params[ 1 ][ 'table' ] ) && isset( $params[ 1 ][ 'as' ] ) && isset( $params[ 1 ][ 'key' ] ) )
			) {
				$join = " {$params[0]['table']} AS {$params[0]['as']} {$through} JOIN {$params[1]['table']} AS {$params[1]['as']} ON {$params[0]['as']}.{$params[0]['key']} =  {$params[1]['as']}.{$params[1]['key']}";
			}
		}

		return $join ?? C::ES;
	}

	/**
	 * Creates syntax to check the expiration date, state, and start publishing date off an row.
	 *
	 * @param string $until - row name where the expiration date is stored
	 * @param string $since - row name where the start date is stored
	 * @param string $pub - row name where the state is stored (e.g. 'published')
	 * @param array $exception
	 *
	 * @return string
	 */
	public function valid( string $until, string $since = C::ES, string $pub = C::ES, array $exception = [] ): string
	{
		$null = $this->getNullDate();
		$pub = $pub ? " AND {$pub} = 1 " : C::ES;
		$stamp = date( 'Y-m-d H:i:s', 0 );
		if ( $since ) {
			//			$since = "AND ( {$since} < '{$now}' OR {$since} IN( '{$null}', '{$stamp}' ) ) ";
			$since = "AND ( {$since} < NOW() OR {$since} IN( '{$null}', '{$stamp}' ) ) ";
		}
		if ( $exception && is_array( $exception ) ) {
			$ex = [];
			foreach ( $exception as $subject => $value ) {
				$ex[] = "{$subject} = '{$value}'";
			}
			$exception = implode( 'OR', $ex );
			$exception = 'OR ' . $exception;
		}
		else {
			$exception = C::ES;
		}

		return "( ( {$until} > NOW() OR {$until} IN ( '{$null}', '{$stamp}' ) ) {$since} {$pub} ) {$exception} ";
	}

	/**
	 * It's only suitable for fetching data from procedures created for sp only
	 *
	 * @param string $name
	 * @param array $params
	 * @param string $returnTag
	 *
	 * @return array|null
	 * @throws \Sobi\Error\Exception
	 */
	public function procedure( string $name, array $params = [], string $returnTag = 'return' ): ?array
	{
		$this->mysql = $this->mysql ?? $this->db->getConnection();
		$params = implode( $params );
		$result = $this
			->mysql
			->query( "CALL {$name}( {$params}, @{$returnTag} )" );
		if ( !( $result ) ) {
			throw new Exception( $this->mysql->error );
		}
		$response = $this
			->setQuery( 'SELECT @' . $returnTag )
			->loadResult();

		return strlen( $response ) ? Serialiser::StructuralData( $response ) : [];
	}

	/**
	 * @param string $sql
	 *
	 * @return \Sobi\Application\Joomla\Database\MySQLi
	 * @throws \Sobi\Error\Exception
	 */
	public function & realExec( string $sql ): MySQLi
	{
		$this->mysql = $this->mysql ?? $this->db->getConnection();
		$sql = str_replace( 'spdb', $this->prefix . 'sobipro', $sql );
		$sql = str_replace( $this->prefix, $this->db->getPrefix(), $sql );
		if ( !( $this->mysql->real_query( $sql ) ) ) {
			throw new Exception( 'Query could not have been executed' );
		}

		return $this;
	}
}
