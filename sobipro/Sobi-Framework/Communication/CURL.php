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
 * @created Sat, Dec 3, 2016 15:22:50 by Radek Suski
 * @modified 17 January 2023 by Sigrid Suski
 */

//declare( strict_types=1 );

namespace Sobi\Communication;

defined( 'SOBI' ) || exit( 'Restricted access' );

use Sobi\{
	C,
	Framework,
	Error\Exception
};

/**
 * Class CURL
 * @package Sobi\Communication
 */
class CURL
{
	/**
	 * @var false|resource|null
	 */
	protected $resource = null;
	/**
	 * @var array
	 */
	protected $curlStatus = 0;
	/**
	 * @var array
	 */
	static public $infoCodes = [
		'effective_url'           => CURLINFO_EFFECTIVE_URL,
		'http_code'               => CURLINFO_HTTP_CODE,
		'response_code'           => CURLINFO_HTTP_CODE,
		'filetime'                => CURLINFO_FILETIME,
		'total_time'              => CURLINFO_TOTAL_TIME,
		'namelookup_time'         => CURLINFO_NAMELOOKUP_TIME,
		'connect_time'            => CURLINFO_CONNECT_TIME,
		'pretransfer_time'        => CURLINFO_PRETRANSFER_TIME,
		'starttransfer_time'      => CURLINFO_STARTTRANSFER_TIME,
		'redirect_time'           => CURLINFO_REDIRECT_TIME,
		'size_upload'             => CURLINFO_SIZE_UPLOAD,
		'size_download'           => CURLINFO_SIZE_DOWNLOAD,
		'speed_download'          => CURLINFO_SPEED_DOWNLOAD,
		'speed_upload'            => CURLINFO_SPEED_UPLOAD,
		'header_size'             => CURLINFO_HEADER_SIZE,
		'header_out'              => CURLINFO_HEADER_OUT,
		'request_size'            => CURLINFO_REQUEST_SIZE,
		'ssl_verifyresult'        => CURLINFO_SSL_VERIFYRESULT,
		'content_length_download' => CURLINFO_CONTENT_LENGTH_DOWNLOAD,
		'content_length_upload'   => CURLINFO_CONTENT_LENGTH_UPLOAD,
		'content_type'            => CURLINFO_CONTENT_TYPE,
	];
	/**
	 * @var array
	 */
	static public $optionsCodes = [
		'autoreferer'          => CURLOPT_AUTOREFERER,
		'binarytransfer'       => CURLOPT_BINARYTRANSFER,
		'cookiesession'        => CURLOPT_COOKIESESSION,
		'crlf'                 => CURLOPT_CRLF,
		'dns_use_global_cache' => CURLOPT_DNS_USE_GLOBAL_CACHE,
		'failonerror'          => CURLOPT_FAILONERROR,
		'filetime'             => CURLOPT_FILETIME,
		'followlocation'       => CURLOPT_FOLLOWLOCATION,
		'forbid_reuse'         => CURLOPT_FORBID_REUSE,
		'fresh_connect'        => CURLOPT_FRESH_CONNECT,
		'ftp_use_eprt'         => CURLOPT_FTP_USE_EPRT,
		'ftp_use_epsv'         => CURLOPT_FTP_USE_EPSV,
		'ftpappend'            => CURLOPT_FTPAPPEND,
		'ftpascii'             => CURLOPT_TRANSFERTEXT,
		'ftplistonly'          => CURLOPT_FTPLISTONLY,
		'header'               => CURLOPT_HEADER,
		'httpget'              => CURLOPT_HTTPGET,
		'httpproxytunnel'      => CURLOPT_HTTPPROXYTUNNEL,
		//			'mute' => CURLOPT_MUTE,
		'netrc'                => CURLOPT_NETRC,
		'nobody'               => CURLOPT_NOBODY,
		'noprogress'           => CURLOPT_NOPROGRESS,
		'nosignal'             => CURLOPT_NOSIGNAL,
		'post'                 => CURLOPT_POST,
		'put'                  => CURLOPT_PUT,
		'returntransfer'       => CURLOPT_RETURNTRANSFER,
		'ssl_verifypeer'       => CURLOPT_SSL_VERIFYPEER,
		'transfertext'         => CURLOPT_TRANSFERTEXT,
		'unrestricted_auth'    => CURLOPT_UNRESTRICTED_AUTH,
		'upload'               => CURLOPT_UPLOAD,
		'verbose'              => CURLOPT_VERBOSE,
		'buffersize'           => CURLOPT_BUFFERSIZE,
		//		'closepolicy' => CURLOPT_CLOSEPOLICY,
		'connecttimeout'       => CURLOPT_CONNECTTIMEOUT,
		'dns_cache_timeout'    => CURLOPT_DNS_CACHE_TIMEOUT,
		'ftpsslauth'           => CURLOPT_FTPSSLAUTH,
		'http_version'         => CURLOPT_HTTP_VERSION,
		'httpauth'             => CURLOPT_HTTPAUTH,
		'curlauth_any'         => CURLAUTH_ANY,
		'curlauth_anysafe'     => CURLAUTH_ANYSAFE,
		'infilesize'           => CURLOPT_INFILESIZE,
		'low_speed_limit'      => CURLOPT_LOW_SPEED_LIMIT,
		'low_speed_time'       => CURLOPT_LOW_SPEED_TIME,
		'maxconnects'          => CURLOPT_MAXCONNECTS,
		'maxredirs'            => CURLOPT_MAXREDIRS,
		'port'                 => CURLOPT_PORT,
		'proxyauth'            => CURLOPT_PROXYAUTH,
		'proxyport'            => CURLOPT_PROXYPORT,
		'proxytype'            => CURLOPT_PROXYTYPE,
		'resume_from'          => CURLOPT_RESUME_FROM,
		'ssl_verifyhost'       => CURLOPT_SSL_VERIFYHOST,
		'sslversion'           => CURLOPT_SSLVERSION,
		'timecondition'        => CURLOPT_TIMECONDITION,
		'timeout'              => CURLOPT_TIMEOUT,
		'timevalue'            => CURLOPT_TIMEVALUE,
		'cainfo'               => CURLOPT_CAINFO,
		'capath'               => CURLOPT_CAPATH,
		'cookie'               => CURLOPT_COOKIE,
		'cookiefile'           => CURLOPT_COOKIEFILE,
		'cookiejar'            => CURLOPT_COOKIEJAR,
		'customrequest'        => CURLOPT_CUSTOMREQUEST,
		'egdsocket'            => CURLOPT_EGDSOCKET,
		'encoding'             => CURLOPT_ENCODING,
		'ftpport'              => CURLOPT_FTPPORT,
		'interface'            => CURLOPT_INTERFACE,
		'krb4level'            => CURLOPT_KRB4LEVEL,
		'postfields'           => CURLOPT_POSTFIELDS,
		'proxy'                => CURLOPT_PROXY,
		'proxyuserpwd'         => CURLOPT_PROXYUSERPWD,
		'random_file'          => CURLOPT_RANDOM_FILE,
		'range'                => CURLOPT_RANGE,
		'referer'              => CURLOPT_REFERER,
		'ssl_cipher_list'      => CURLOPT_SSL_CIPHER_LIST,
		'sslcert'              => CURLOPT_SSLCERT,
		'sslcertpasswd'        => CURLOPT_SSLCERTPASSWD,
		'sslcerttype'          => CURLOPT_SSLCERTTYPE,
		'sslengine'            => CURLOPT_SSLENGINE,
		'sslengine_default'    => CURLOPT_SSLENGINE_DEFAULT,
		'sslkey'               => CURLOPT_SSLKEY,
		'sslkeypasswd'         => CURLOPT_SSLKEYPASSWD,
		'sslkeytype'           => CURLOPT_SSLKEYTYPE,
		'url'                  => CURLOPT_URL,
		'useragent'            => CURLOPT_USERAGENT,
		'userpwd'              => CURLOPT_USERPWD,
		'http200aliases'       => CURLOPT_HTTP200ALIASES,
		'httpheader'           => CURLOPT_HTTPHEADER,
		'postquote'            => CURLOPT_POSTQUOTE,
		'quote'                => CURLOPT_QUOTE,
		'file'                 => CURLOPT_FILE,
		'infile'               => CURLOPT_INFILE,
		'stderr'               => CURLOPT_STDERR,
		'writeheader'          => CURLOPT_WRITEHEADER,
		'headerfunction'       => CURLOPT_HEADERFUNCTION,
		'readfunction'         => CURLOPT_READFUNCTION,
		'writefunction'        => CURLOPT_WRITEFUNCTION,
	];
	protected $error = [];

	/**
	 * Initialize a cURL session
	 *
	 * @param string $url
	 *
	 * @return void
	 */
	public function __construct( string $url = C::ES )
	{
		if ( function_exists( 'curl_init' ) ) {
			/* For some reason on certain PHP/CURL version it causes error if the $url is null */
			if ( $url ) {
				$this->resource = curl_init( $url );
			}
			else {
				$this->resource = curl_init();
			}
		}
		else {
			$this->curlStatus = 999;
		}
	}

	/**
	 * Sets an option on the given cURL session handle.
	 *
	 * @param string|int $option - The CURLOPT_XXX option to set.
	 * @param mixed $value - The value to be set on option
	 *
	 * @return bool
	 */
	public function setOption( $option, $value ): bool
	{
		if ( is_string( $option ) && isset( self::$optionsCodes[ $option ] ) ) {
			$option = self::$optionsCodes[ $option ];
		}

		return curl_setopt( $this->resource, $option, $value );
	}

	/**
	 * Sets multiple options for a cURL session
	 *
	 * @param array $options - An array specifying which options to set and their values. The keys should be valid curl_setopt() constants or their integer equivalents.
	 *
	 * @return bool
	 */
	public function setOptions( array $options ): bool
	{
		if ( count( $options ) ) {
			foreach ( $options as $opt => $set ) {
				if ( is_string( $opt ) && isset( self::$optionsCodes[ $opt ] ) ) {
					unset( $options[ $opt ] );
					$options[ self::$optionsCodes[ $opt ] ] = $set;
				}
			}
		}

		return curl_setopt_array( $this->resource, $options );
	}

	/**
	 * Sets an URL.
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	public function setUrl( string $url ): bool
	{
		return $this->setOption( CURLOPT_URL, $url );
	}

	/**
	 * TRUE to include the header in the output.
	 *
	 * @param int $timeOut
	 *
	 * @return bool
	 * @internal param bool $header
	 */
	public function setTimeOut( int $timeOut ): bool
	{
		return $this->setOption( CURLOPT_CONNECTTIMEOUT, $timeOut );
	}

	/**
	 * TRUE to include the header in the output.
	 *
	 * @param bool $header
	 *
	 * @return bool
	 */
	public function setHeader( bool $header ): bool
	{
		return $this->setOption( CURLOPT_HEADER, $header );
	}

	/**
	 */
	public function __destruct()
	{
		$this->close();
	}

	/**
	 * Closes a cURL session and frees all resources.
	 *
	 * @return void
	 */
	public function close(): void
	{
		if ( is_resource( $this->resource ) ) {
			@curl_close( $this->resource );
		}
	}

	/**
	 * Execute the given cURL session.
	 * Returns TRUE on success or FALSE on failure.
	 * However, if the CURLOPT_RETURNTRANSFER  option is set, it will return the result on success, FALSE on failure.
	 * @return bool|string
	 */
	public function exec(): ?string
	{
		$this->error[ 'active' ] = false;
		$r = curl_exec( $this->resource );
		$inf = $this->info();
		if ( $inf[ 'http_code' ] == 301 || $inf[ 'http_code' ] == 302 ) {
			$this->setOption( 'header', true );
			$r = curl_exec( $this->resource );
			preg_match( '/Location: (http.*)/', $r, $newUrl );
			$this->setOption( 'header', false );
			$this->setOption( 'url', $newUrl[ 1 ] );

			return $this->exec();
		}

		return $r;
	}

	/**
	 * Gets information about the last transfer.
	 *
	 * @param string $opt - correspond option
	 *
	 * @return mixed
	 */
	public function info( string $opt = C::ES )
	{
		if ( $opt && is_string( $opt ) && isset( self::$infoCodes[ $opt ] ) ) {
			$opt = self::$infoCodes[ $opt ];

			return curl_getinfo( $this->resource, $opt );
		}
		else {
			return curl_getinfo( $this->resource );
		}
	}

	/**
	 * @param string $url
	 *
	 * @return array
	 * @throws Exception
	 */
	public function certificate( string $url ): array
	{
		$errno = null;
		$errorString = null;
		if ( stristr( $url, 'https' ) ) {
			$url = str_ireplace( 'https://', C::ES, $url );
		}
		if ( strstr( $url, '/' ) ) {
			$url = explode( '/', $url );
			$url = $url[ 0 ];
		}
		if ( !( CURL::ValidateHttp( 'https://' . $url ) ) ) {
			return [ 'err' => 500, 'msg' => Framework::Error( 'INVALID_URL', 'https://' . $url ) ];
		}
		$this->setOptions(
			[
				'url'            => 'https://' . $url,
				'connecttimeout' => 10,
				'header'         => true,
				'returntransfer' => true,
				'ssl_verifypeer' => false,
				'ssl_verifyhost' => 2,
				'nobody'         => true,
			]
		);
		if ( !( CURL::ValidCode( $this->exec() ) ) ) {
			$err = $this->info();

			return [ 'err' => $err[ 'http_code' ], 'msg' => Framework::Error( 'NO_CONNECT', 'https://' . $url ) ];
		}
		$res = stream_context_create( [ 'ssl' => [ 'capture_peer_cert' => true ] ] );
		// @todo we can try to catch and if validation fails do it w/o but give the user a warning
//		$res = stream_context_create( [ 'ssl' => [ 'capture_peer_cert' => true, 'verify_peer' => false, 'verify_peer_name' => false ] ] );
		$client = stream_socket_client( "ssl://{$url}:443", $errno, $errorString, 30, STREAM_CLIENT_CONNECT, $res );
		if ( !$client ) {
			if ( $errno == 0 ) {
				return [ 'err' => 998, 'msg' => Framework::Error( 'CURL_INITIALISATION_ERROR', 'https://' . $url ) ];
			}
			else {
				return [ 'err' => $errno, 'msg' => $errorString ];
			}
		}
		$cont = stream_context_get_options( $client );
		if ( !$errno ) {
			return openssl_x509_parse( $cont[ 'ssl' ][ 'peer_certificate' ] );
		}
		else {
			return [ 'err' => $errno, 'msg' => $errorString ];
		}
	}

	/**
	 * @param string $url
	 *
	 * @return bool
	 */
	protected static function ValidateHttp( string $url ): bool
	{
		return (bool) preg_match( '|http[s]?://[a-z0-9.\-_]{3,}\.[a-z]{2,5}.*|i', $url );
	}

	/**
	 * @param string $response
	 *
	 * @return int
	 */
	public static function GetCode( string $response ): int
	{
		$matches = [];
		if ( preg_match( '/HTTP\/1\.\d+\s+(\d+)/', $response, $matches ) ) {
			return ( int ) $matches[ 1 ];
		}
		else {
			return 0;
		}
	}

	/**
	 * @param $response
	 *
	 * @return bool
	 */
	public static function ValidCode( $response ): bool
	{
		return ( ( CURL::GetCode( $response ) >= 200 ) && ( CURL::GetCode( $response ) < 400 ) );
	}

	/**
	 * Returns the last error number and/or a string containing the last error for the current session.
	 *
	 * @param bool $message - return string containing the last error
	 * @param bool $number - return error number
	 *
	 * @return string
	 */
	public function error( bool $message = true, bool $number = true ): string
	{
		$err = [];
		$errno = curl_errno( $this->resource );
		if ( $number ) {
			$err[] = $errno;
		}
		if ( $message ) {
			$err[] = curl_error( $this->resource );
		}
		if ( $errno ) {
			return implode( ': ', $err );
		}
		else {
			return C::ES;
		}
	}

	/**
	 * Returns the initialisation status as number and/or as string.
	 *
	 * @param bool $message
	 * @param bool $number
	 *
	 * @return string
	 * @throws Exception
	 */
	public function status( bool $message = true, bool $number = true ): string
	{
		$err = [];
		$errno = $this->curlStatus;

		if ( $number ) {
			$err[] = $errno;
		}
		if ( $message ) {
			$err[] = Framework::Error( 'CURL_NOT_INSTALLED' );
		}
		if ( $errno ) {
			return implode( ': ', $err );
		}
		else {
			return C::ES;
		}
	}
}
