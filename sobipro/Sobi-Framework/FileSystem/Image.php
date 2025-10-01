<?php
/**
 * @package: Sobi Framework
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
 * @created by Radek Suski
 * @modified 27 February 2023 by Sigrid Suski
 */

//declare( strict_types=1 );

namespace Sobi\FileSystem;

use Grafika\Grafika;
use Sobi\{
	C,
	Framework,
	Error\Exception
};

/**
 * Class Image
 * @package Sobi\FileSystem
 */
class Image extends File
{
	/*** @var array */
	protected $exif = [];
	/*** @var bool */
	protected $transparency = true;
	/*** @var \Grafika\EditorInterface */
	protected $editor = null;
	/*** @var \Grafika\ImageInterface */
	protected $image = null;
	/*** @var string */
	protected $type = C::ES;


	/**
	 * @param bool $transparency
	 *
	 * @return \Sobi\FileSystem\Image
	 * @throws \Exception
	 */
	public function & setTransparency( bool $transparency ): Image
	{
		$this->createEditor();
		$this->transparency = $transparency;
		if ( method_exists( $this->image, 'fullAlphaMode' ) ) {
			$this->image->fullAlphaMode( $transparency );
		}

		return $this;
	}

	/**
	 * @param string $sections
	 * @param bool $array
	 *
	 * @return array
	 */
	public function exif( string $sections = C::ES, bool $array = true ): array
	{
		if ( function_exists( 'exif_read_data' ) && $this->_filename ) {
			if ( in_array( strtolower( FileSystem::GetExt( $this->_filename ) ), [ 'jpg', 'jpeg', 'tiff' ] ) ) {
				$this->exif = exif_read_data( $this->_filename, $sections, $array );
			}

			return $this->exif;
		}
		else {
			return [];
		}
	}


	/**
	 * Resample image.
	 *
	 * @param int $width
	 * @param int $height
	 * @param string $position
	 * @param int $offsetX
	 * @param int $offsetY
	 *
	 * @return $this
	 * @throws \Exception
	 */
	public function & crop( int $width, int $height, string $position = 'center', int $offsetX = 0, int $offsetY = 0 ): Image
	{
		$this->createEditor();
		$this->editor->crop( $this->image, $width, $height, $position, $offsetX, $offsetY );

		return $this;
	}

	/**
	 * Resample image.
	 *
	 * @param int $width
	 * @param int $height
	 *
	 * @return $this
	 * @throws Exception|\Exception
	 */
	public function & resample( int $width, int $height ): Image
	{
		[ $wOrg, $hOrg ] = getimagesize( $this->_filename );


		/* if not always and image is smaller */
		if ( ( ( $wOrg <= $width ) && ( $hOrg <= $height ) ) ) {
			return $this;
		}

		$orgRatio = $wOrg / $hOrg;

		if ( $width == 0 ) {
			$width = $height * $orgRatio;
		}
		elseif ( $height == 0 ) {
			$height = (int) ( $width / $orgRatio );
		}
		else {
			if ( ( $width / $height ) > $orgRatio ) {
				$width = $height * $orgRatio;
			}
			else {
				$height = (int) ( $width / $orgRatio );
			}
		}
		$this->createEditor();
		$this->editor->resizeExact( $this->image, $width, $height );

		return $this;
	}

	/**
	 * @param string $path
	 *
	 * @return bool|\Grafika\EditorInterface
	 * @throws \Sobi\Error\Exception
	 */
	public function saveAs( string $path ): bool
	{
		// Mon, May 17, 2021 13:56:38
		// backward compatibility
		if ( func_num_args() > 1 ) {
			$this->setType( func_get_args()[ 1 ] );
			// @todo: deprecated error/notice
		}
		if ( !$this->image ) {
			$this->createEditor();
		}
		$quality = ( $this->type == 'WEBP' ) ? Framework::Cfg( 'image.webp_quality', 80 ) : Framework::Cfg( 'image.jpeg_quality', 90 );
		try {
			$this->editor
				->save( $this->image, $path, $this->type, $quality );

			return true;
		}
		catch ( \Exception $x ) {
			// @todo: you know what
//			trigger_error()
			return false;
		}
	}

	public function & setType( string $type ): Image
	{
		$this->type = $type;

		return $this;
	}

	/**
	 * @return bool|\Grafika\EditorInterface
	 * @throws \Sobi\Error\Exception
	 */
	public function save(): bool
	{
		// Mon, May 17, 2021 13:56:38
		// backward compatibility
		if ( func_num_args() > 1 ) {
			$this->setType( func_get_args()[ 1 ] );
			// @todo: deprecated error/notice
		}
		$this->createEditor();
		$quality = ( $this->type == 'webp' ) ? Framework::Cfg( 'image.webp_quality', 80 ) : Framework::Cfg( 'image.jpeg_quality', 90 );

		try {
			$this->editor
				->save( $this->image, $this->_filename, $this->type, $quality );
		}
		catch ( \Exception $x ) {
			trigger_error( $x->getMessage(), C::WARNING );

			return false;
		}

		return true;
	}

	/**
	 * Rotate image.
	 *
	 * @param int $angle
	 *
	 * @return $this
	 * @throws \Exception
	 */
	public function & rotate( int $angle ): Image
	{
		$this->createEditor();
		$this->editor->rotate( $this->image, $angle );

		return $this;
	}

	/**
	 * @return bool
	 * @throws \Exception
	 */
	public function fixRotation(): bool
	{
		$return = true;
		if ( isset( $this->exif[ 'IFD0' ][ 'Orientation' ] ) ) {
			switch ( $this->exif[ 'IFD0' ][ 'Orientation' ] ) {
				case 3:
					$this->rotate( 180 );
					break;
				case 6:
					$this->rotate( -90 );
					break;
				case 8:
					$this->rotate( 90 );
					break;
				default:
					$return = false;
					break;
			}
		}

		return $return;
	}

	/**
	 * @throws \Exception
	 */
	protected function createEditor()
	{
		if ( $this->_filename && !( $this->editor ) ) {
			$this->editor = Grafika::createEditor()
				->open( $this->image, $this->_filename );
		}
	}
}
