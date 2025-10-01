<?php
/**
 * @package SobiPro multi-directory component with content construction support
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006–2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/GPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according section 7 of GPL v3.
 * See https://www.gnu.org/licenses/gpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * @created 28 November 2009 by Radek Suski
 * @modified 22 March 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadClass( 'opt.fields.inbox' );

use Sobi\C;
use Sobi\FileSystem\File;
use Sobi\FileSystem\FileSystem;
use Sobi\FileSystem\Image;
use Sobi\Input\Input;
use Sobi\Lib\Factory;
use Sobi\Utils\StringUtils;

/**
 * Class SPField_Image
 */
class SPField_Image extends SPField_Inbox implements SPFieldInterface
{
	/* add here properties which are different from their initial value (model or derived class)
	   and properties valid only for this class. */

	/* properties with different value */

	/** @var string */
	protected $dType = 'special';
	/** @var int */
	protected $bsWidth = 10;
	/** @var string */
	protected $cssClass = 'spClassImage';
	/** @var string */
	protected $cssClassView = 'spClassViewImage';
	/** @var string */
	protected $cssClassEdit = 'spClassEditImage';

	/* properties for and derived classes */
	/** @var bool */
	protected $detectTransparency = true;
	/** @var bool */
	protected $keepOrg = true;
	/** @var bool */
	protected $resize = true;
	/** @var bool */
	protected $convert = false;
	/** @var bool */
	protected $crop = false;
	/** @var double */
	protected $maxSize = 2097152;
	/** @var int */
	protected $resizeWidth = 500;
	/** @var int */
	protected $resizeHeight = 500;
	/** @var string */
	protected $imageName = '{orgname}';
	/** @var string */
	protected $imageFloat = C::ES;
	/** @var bool */
	protected $generateThumb = true;
	/** @var string */
	protected $thumbFloat = C::ES;
	/** @var string */
	protected $float = C::ES;
	/** @var string */
	protected $thumbName = '{orgname}';
	/** @var int */
	protected $thumbWidth = 400;
	/** @var int */
	protected $thumbHeight = 400;
	/** @var string */
	protected $inVcard = 'thumb';
	/** @var string */
	protected $inDetails = 'image';
	/** @var string */
	protected $inCategory = 'image';
	/** @var string */
	protected $savePath = 'images/sobipro/entries/{id}/';
	/** @var bool */
	protected $ownAltText = false;
	/** @var bool */
	protected $floatOwnAltText = false;
	/** @var int */
	protected $altMaxLength = 150;
	/** @var string */
	protected $altPattern = C::ES;
	/** @var array */
	protected $created = [ 'createdTime' => '1000-01-01 00:00:00', 'createdBy' => 0, 'createdIP' => '000.000.000.000' ];
	/** @var array */
	protected $prefix = [ 'image' => 'img_', 'thumb' => 'thumb_', 'ico' => 'ico_' ];

	/** @var bool */
	private static $CAT_FIELD = true;

	/**
	 * SPField_Image constructor. Get language dependant settings.
	 *
	 * @param $field
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function __construct( &$field )
	{
		parent::__construct( $field );

		$this->altPattern = SPLang::getValue( $this->nid . '-alt', 'field_' . $this->fieldType, Sobi::Section(), C::ES, C::ES, $this->fid );
	}

	/**
	 * Returns the parameter list (params). All properties not set in the model but used in the xml file of the field.
	 * No language dependant values, no values from the model (columns in the database).
	 *
	 * @return array
	 */
	protected function getAttr(): array
	{
		return [ 'itemprop', 'helpposition', 'showEditLabel', 'cssClassView', 'cssClassEdit', 'bsWidth', 'savePath', 'inDetails', 'inVcard', 'thumbHeight', 'thumbWidth', 'thumbName', 'keepOrg', 'resize', 'maxSize', 'resizeWidth', 'resizeHeight', 'imageName', 'generateThumb', 'thumbFloat', 'imageFloat', 'crop', 'inCategory', 'float', 'detectTransparency', 'convert', 'ownAltText', 'floatOwnAltText', 'altMaxLength', ];
	}

	/**
	 * Shows the field in the entry form.
	 *
	 * @param bool $return return or display directly
	 *
	 * @return string
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function field( $return = false )
	{
		if ( $this->enabled ) {
			$this->suffix = C::ES; //clear if any

			$icoFile = $oldImage = $style = $dc = C::ES;
			static $js = false;
			$files = $this->getExistingFiles();

			if ( is_array( $files ) && count( $files ) ) {
				$dc = ' data-sp-content="1"';

				$oldImage = array_key_exists( 'original', $files ) ? $files[ 'original' ] : $files[ 'image' ];
				if ( isset( $files[ 'ico' ] ) ) {
					$icoFile = $files[ 'ico' ];
				}
				else {
					if ( isset( $files[ 'thumb' ] ) ) {
						$icoFile = $files[ 'thumb' ];
					}
				}
			}
			if ( $icoFile ) {
				$img = Sobi::Cfg( 'live_site' ) . $icoFile;
				$style = "margin-right: 5px";
			}

			$field = C::ES;
			$fw = defined( 'SOBIPRO_ADM' ) ? C::BOOTSTRAP5 : Sobi::Cfg( 'template.framework-style', C::BOOTSTRAP5 );
			if ( $fw == C::BOOTSTRAP2 || $fw == C::BOOTSTRAP3 ) {
				$field .= '<div class="sp-field-image" style="display: flex !important;"' . $dc . '>';
			}
			else {
				$field .= '<div class="sp-field-image d-flex"' . $dc . '>';
			}
			$previewclass = $dc ? 'spctrl-edit-image-preview sp-preview-image' : 'spctrl-edit-image-preview';
			$field .= "<div id=\"{$this->nid}_img_preview\" class=\"$previewclass\" style=\"$style\">";

			$altText = C::ES;
			if ( $files && array_key_exists( 'data', $files ) ) {
				$altText = array_key_exists( 'alt', $files[ 'data' ] ) ? $files[ 'data' ][ 'alt' ] : C::ES;
			}
			if ( $icoFile && FileSystem::Exists( JPATH_ROOT . '/' . $icoFile ) ) {
				$field .= "<img src=\"$img\" alt=\"$altText\" />";
			}
			$field .= '</div>';

			if ( $fw == C::BOOTSTRAP2 || $fw == C::BOOTSTRAP3 ) {
				$field .= '<div style="width: 100%;">';
			}
			else {
				$field .= '<div class="w-100">';
			}
			$field .= '<div class="sp-field-image-delete">';

			if ( $oldImage ) {
				if ( !defined( 'SOBIPRO_ADM' ) ) {
					$pos = strrpos( $oldImage, '/' );
					$oldImage = $pos === false ? $oldImage : substr( $oldImage, $pos + 1 );
					$oldImage = preg_replace( '/^' . $this->prefix[ 'image' ] . '/', C::ES, $oldImage );
				}
				$ext = pathinfo( $oldImage, PATHINFO_EXTENSION );
				if ( $ext == 'webp' ) {
					$oldImage = preg_replace( '/.webp$/', C::ES, $oldImage );
					if ( defined( 'SOBIPRO_ADM' ) ) {
						$oldImage = $oldImage . ' as webp';
					}
				}
				$delText = Sobi::Txt( 'FD.IMG_DELETE_CURRENT_IMAGE' ) . '<small> (' . $oldImage . ')</small>';
			}
			else {
				$delText = Sobi::Txt( 'FD.IMG_DELETE_CURRENT_IMAGE' );
			}
			$cparams[ 'class' ] = defined( 'SOBIPRO_ADM' ) ? 'spClassImage' : $this->cssClass;
			if ( $icoFile && !FileSystem::Exists( JPATH_ROOT . '/' . $icoFile ) ) {
				$cparams[ 'disabled' ] = 'disabled';
			}

			if ( $icoFile ) {
				$field .= SPHtml_Input::checkbox( $this->nid . '_delete', 1, $delText, $this->nid . '_delete', false, $cparams );
			}
			$field .= '</div>';
			$class = $this->required ? 'spctrl-field-image-upload' . ' required' : 'spctrl-field-image-upload';
			$params[ 'sclass' ] = $fw == C::BOOTSTRAP2 ? ( $this->bsWidth > 5 ? ' span' . ( $this->bsWidth - 3 ) : ' span' . $this->bsWidth ) : C::ES;

			$field .= SPHtml_Input::fileUpload( $this->nid, 'image/*', C::ES, $class, preg_replace( '/field_/', 'field.', $this->nid, 1 ) . '.upload', [], $params );

			/* Image Alt tag handling */
			if ( $this->ownAltText ) {
				$tparams = [ 'id' => $this->nid . '-alt', 'class' => 'sp-field-image-alt' ];
				if ( $this->altMaxLength ) {
					$tparams[ 'maxlength' ] = $this->altMaxLength;
				}
				$placeholder = Sobi::Txt( 'FD.IMG_ALT_TAG' );
				$tparams[ 'placeholder' ] = $tparams[ 'aria-label' ] = $placeholder;
				$tparams[ 'class' ] .= $fw == C::BOOTSTRAP2 ? ( $this->bsWidth > 3 ? ' span' . ( $this->bsWidth - 2 ) : ' span' . $this->bsWidth ) : C::ES;
				$title = SPHtml_Input::text( $this->nid . '-alt', $altText, $tparams );

				$floating = $fw == C::BOOTSTRAP5 ? $this->floatOwnAltText : false;
				$label = $floating ? "<label for=\"$this->nid-label\">" . $placeholder . ' </label > ' : C::ES;
				$class = $floating ? "form-floating" : C::ES;
				$field .= "<div class=\"sp-field-image-alt $class\">$title$label</div>";
			}

			$field .= '</div></div>';


			if ( !$js ) {
				SPFactory::header()
					->addJsFile( 'opt.field_image_edit' )
					->addJsCode( 'SobiCore.Ready( function () { SobiPro.jQuery( ".spctrl-field-image-upload" ).SPFileUploader(); } );' );
				$js = true;
			}
			if ( $this->crop ) {
				SPFactory::header()
					->addJsFile( 'Tps.cropper' )
					->addCssFile( 'cropper' );
				$field .= SPHtml_Input::modalWindow( Sobi::Txt( 'IMAGE_CROP_HEADER' ), $this->nid . '_modal' );
			}
			if ( !$return ) {
				echo $field;
			}
			else {
				return $field;
			}
		}

		return C::ES;
	}

	/**
	 * @param $data
	 *
	 * @return string
	 */
	public function getRawData( &$data )
	{
		if ( is_string( $data ) ) {
			try {
				$data = SPConfig::unserialize( $data );
			}
			catch ( SPException $x ) {
				$data = null;
			}
		}

		/* Sigrid: remove data node if imex (don't know why, ask Radek) */
		$task = Input::String( 'task' );
		$imex = ( $task == 'imex.doImport' || $task == 'imex.doCImport' );

		if ( isset( $data[ 'data' ] ) && $imex ) {
			unset( $data[ 'data' ] );
		}

		return SPConfig::serialize( $data );
	}

	/**
	 * Gets the data for this field from $_FILES and verifies them the first time.
	 * Backend ONLY!!
	 *
	 * @param \SPEntry $entry
	 * @param string $request
	 * @param false $clone
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function validate( $entry, $request, $clone = false )
	{
		/* no checks if the entry is just cloned */
		if ( !$clone ) {
			$this->verify( $entry, $request );
		}
	}

	/**
	 * Gets the data for this field, verifies them the first time.
	 * Frontend ONLY!!
	 *
	 * @param SPEntry $entry
	 * @param string $tsId
	 * @param string $request
	 *
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function submit( &$entry, $tsId = C::ES, $request = 'POST' )
	{
		$save = [];

		if ( $this->verify( $entry, $request ) ) {  /* verify the data the first time */
			/* check if we are using the ajax upload - then we don't need to handle temp data */
			$check = Input::String( $this->nid );
			if ( !$check ) {    /* if no AJAX upload */
				/* save the file to temporary folder */
				$tempfile = Input::File( $this->nid, 'tmp_name' );
				if ( $tempfile ) {
					$temp = str_replace( '.', '-', $tsId );
					$path = SPLoader::dirPath( "tmp/edit/$temp/images", 'front', false );
					$path .= '/' . Input::File( $this->nid, 'name' );
					$file = new File();
					$file->upload( $tempfile, $path );
					$save[ $this->nid ] = $path;
				}
			}
			else {
				$save[ $this->nid ] = $check;
			}
			$save[ $this->nid . '_delete' ] = Input::Bool( $this->nid . '_delete' );
		}

		/* Alt text can be entered without uploading an image */
		if ( $this->ownAltText ) {
			$save[ $this->nid . '-alt' ] = Input::Raw( $this->nid . '-alt', $request );
		}

		return $save;
	}

	/**
	 * Verifies the uploaded file.
	 *
	 * @param SPEntry $entry
	 * @param string $request
	 *
	 * @return bool
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function verify( $entry, $request )
	{
		static $store = null;

		/* first time verify and a file was uploaded: $_FILES is still set, get the file data from there. */
		$directory = Input::String( $this->nid );
		$tempfile = Input::File( $this->nid, 'tmp_name' );

		/* second time verify: fetch the data from the request cache.
		'requestcache_stored' contains the entry name and the data for this field only. */
		if ( $store == null ) {
			$store = SPFactory::registry()->get( 'requestcache_stored' );
		}
		if ( is_array( $store ) && isset( $store[ $this->nid ] ) ) {
			if ( !strstr( $store[ $this->nid ], 'file://' ) && !strstr( $store[ $this->nid ], 'directory://' ) ) {
				$tempfile = $store[ $this->nid ];
			}
			else {
				$directory = $store[ $this->nid ];
			}
		}

		$fileSize = Input::File( $this->nid, 'size' );
		if ( $directory && strstr( $directory, 'directory://' ) ) {
			// list( $tempfile, $dirName, $files ) = $this->getAjaxFiles( $directory ); // below PHP 7.1
			[ $tempfile, $dirName, $files ] = $this->getAjaxFiles( $directory );        // works only since PHP 7.1 which is the minimum requirement for SobiPro 1.5!!
			if ( is_array( $files ) && count( $files ) ) {
				foreach ( $files as $file ) {
					if ( $file == '.' || $file == '..'
						|| strpos( $file, 'icon_' ) !== false
						|| strpos( $file, 'cropped_' ) !== false
						|| strpos( $file, 'resized_' ) !== false
						|| strpos( $file, '.var' ) !== false
					) {
						continue;
					}
					$fileSize = filesize( $dirName . $file );
				}
			}
		}

		$task = Input::Task();
		$dexs = 0;
		/* do the checks only if no data import via Imex */
		if ( !( $task == 'imex.doImport' || $task == 'imex.doCImport' ) ) {
			$dexs = strlen( $tempfile );

			/* check if field is required (if not just uploaded) */
			if ( $this->required && !$dexs ) {
				/* if not just uploaded, get the existing images if available */
				$files = $this->getExistingFiles();
				if ( ( is_array( $files ) && !( count( $files ) ) ) || !$files ) {
					throw new SPException( SPLang::e( 'FIELD_REQUIRED_ERR', $this->name ) );
				}
			}

			/* check if uploaded file is too large */
			if ( $fileSize > $this->maxSize ) {
				throw new SPException( SPLang::e( 'FIELD_IMG_TOO_LARGE', $this->name, $fileSize, $this->maxSize ) );
			}

			/* check if there was an adminField */
			if ( $this->adminField && ( $dexs || Input::Bool( $this->nid . '_delete' ) ) ) {
				if ( !Sobi:: Can( 'entry.adm_fields.edit' ) ) {
					throw new SPException( SPLang::e( 'FIELD_NOT_AUTH', $this->name ) );
				}
			}

			if ( $dexs ) {
				/* check if it was just uploaded and not for free */
				if ( !( $this->isFree ) && $this->fee ) {
					SPFactory::payment()->add( $this->fee, $this->name, $entry->get( 'id' ), $this->fid );
				}

				/* check if it was editLimit */
				if ( $this->editLimit == 0 && !( Sobi::Can( 'entry.adm_fields.edit' ) ) ) {
					throw new SPException( SPLang::e( 'FIELD_NOT_AUTH_EXP', $this->name ) );
				}

				/* check if it was editable */
				if ( !$this->editable && !( Sobi::Can( 'entry.adm_fields.edit' ) ) && $entry->get( 'version' ) > 1 ) {
					throw new SPException( SPLang::e( 'FIELD_NOT_AUTH_NOT_ED', $this->name ) );
				}
			}
		}

		return $dexs > 0;
	}

	/**
	 * Gets the data for a field and save it in the database.
	 *
	 * @param SPEntry $entry
	 * @param string $request
	 * @param bool $clone
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 * @throws \Exception
	 */
	public function saveData( &$entry, $request = 'POST', $clone = false )
	{
		if ( $this->enabled ) {
			if ( $clone ) { // if the entry should be duplicated
				$orgSid = Input::Sid();
				$this->loadData( $orgSid );
				$files = $this->getExistingFiles();
				$cloneFiles = [];
				if ( isset( $files[ 'image' ] ) && FileSystem::Exists( SOBI_ROOT . '/' . $files[ 'image' ] ) ) {
					$this->cloneFiles( $entry, $files, $cloneFiles );

					return;
				}
			}

			//initializations
			$fileSize = Input::File( $this->nid, 'size' );

			$cropped = null;
			$cache = false;

			static $store = null;
			if ( $store == null ) {
				$store = SPFactory::registry()->get( 'requestcache_stored' );
			}
			if ( is_array( $store ) && isset( $store[ $this->nid ] ) ) {
				if ( !strstr( $store[ $this->nid ], 'file://' ) && !strstr( $store[ $this->nid ], 'directory://' ) ) {
					$tempfile = $store[ $this->nid ];
					$cache = true;
					$orgName = Input::File( $this->nid, 'name', $request );
				}
				else {
					Input::Set( $this->nid, $store[ $this->nid ] );
					$orgName = Input::File( $this->nid, 'name' );
					$tempfile = Input::File( $this->nid, 'tmp_name' );
				}
			}
			else {
				$tempfile = Input::File( $this->nid, 'tmp_name' );
				$orgName = Input::File( $this->nid, 'name' );
			}
			$sPath = $this->parseName( $entry, $orgName, $this->savePath );
			$path = SPLoader::dirPath( $sPath, 'root', false );

			/** Wed, Oct 15, 2014 13:51:03
			 * Implemented a cropper with Ajax checker.
			 * This is the actual method to get those files
			 * Other methods left for BC
			 * */

			$dirName = C::ES;
			if ( !$tempfile ) {
				$directory = Input::String( $this->nid );
				if ( strlen( $directory ) ) {

					/* if a new image was uploaded, delete the existing images (if any) */
					$files = $this->getExistingFiles();
					if ( isset ( $files ) ) {       // if there is already an old image, delete it
						$this->delImgs( $entry->get( 'id' ) );
					}

					/* get the new uploaded files */
					[ $tempfile, $dirName, $files, $coordinates ] = $this->getAjaxFiles( $directory );
					if ( $files && count( $files ) ) {
						// check all temporary generated files
						foreach ( $files as $file ) {
							if ( $file == '.' || $file == '..'
								|| strpos( $file, 'icon_' ) !== false
								|| strpos( $file, 'resized_' ) !== false
								|| strpos( $file, '.var' ) !== false
							) {
								continue;
							}
							/* upload the cropped file (will be deleted later) */
							if ( strpos( $file, 'cropped_' ) !== false ) {
								$cropped = $dirName . $file;
								FileSystem::Upload( $cropped, $path . basename( $cropped ) ); // upload cropped image
								continue;
							}
							$fileSize = filesize( $dirName . $file );
							$orgName = $file;
						}
					}
					if ( is_string( $coordinates ) && strlen( $coordinates ) ) { // if the user changed the cropped area then crop again
						$coordinates = json_decode( StringUtils::Clean( $coordinates ), true );
						$croppedImage = new Image( $dirName . $orgName );
						$croppedImage->crop( $coordinates[ 'width' ], $coordinates[ 'height' ], 'top-left', $coordinates[ 'x' ], $coordinates[ 'y' ] );
						$cropped = 'cropped_' . $orgName;
						$croppedImage->saveAs( $path . $cropped );
					}
					$tempfile = $cropped && strlen( $cropped ) ? $cropped : $dirName . $orgName;
				}
			}

			/* process the images */
			$files = [];
			$task = Input::String( 'task' );
			$imex = $task == 'imex.doImport' || $task == 'imex.doCImport';

			/* if we have an image to process (image was just uploaded)
			   $tempfile contains the path to the image to use; $orgName its original name */
			if ( $tempfile && $orgName ) {
				$imageType = $this->convert ? 'WEBP' : C::ES;
				$nameArray = explode( '.', $orgName );
				$ext = strtolower( array_pop( $nameArray ) );
				$nameArray[] = $ext;
				$orgName = implode( '.', $nameArray );

				if ( ( $fileSize > $this->maxSize ) && !$imex ) {
					throw new SPException( SPLang::e( 'FIELD_IMG_TOO_LARGE', $this->name, $fileSize, $this->maxSize ) );
				}
				if ( $cropped && $this->keepOrg ) {   // if we have a cropped image, but should keep the original ...
					FileSystem::Upload( $dirName . $orgName, $path . $orgName );  // ... upload the original image
				}
				if ( $cache ) {
					$orgImage = new Image( $tempfile );
					$orgImage->move( $path . $orgName );
				}
				else {
					$orgImage = new Image();
					$nameArray = explode( '.', $orgName );
					$ext = strtolower( array_pop( $nameArray ) );
					$nameArray[] = $ext;
					$orgName = implode( '.', $nameArray );
					if ( $cropped ) {
						$orgImage->setFile( $path . basename( $tempfile ) );    // original image is now the cropped image
					}
					else {
						$orgImage->upload( $dirName . $orgName, $path . $orgName );
					}
				}

				/* EXIF handling for uploaded files */
				$files[ 'data' ][ 'exif' ] = $orgImage->exif();
				$this->cleanExif( $files[ 'data' ][ 'exif' ] );

				if ( Sobi::Cfg( 'image_field.fix_rotation', true ) ) {
					if ( $orgImage->fixRotation() ) {
						$orgImage->save();
					}
				}

				/* Alt tag handling for uploaded files */
				if ( $this->ownAltText ) {
					$alt = Factory::Db()->escape( strip_tags( Input::String( $this->nid . '-alt' ) ) );
					$files[ 'data' ][ 'alt' ] = $alt ? : C::ES;
				}

				/* Large image handling */
				$image = clone $orgImage;
				$image->setTransparency( $this->detectTransparency );
				if ( $this->resize ) {
					try {
						$image->resample( $this->resizeWidth ? : 0, $this->resizeHeight ? : 0 );
						$files[ 'image' ] = $this->parseName( $entry, $orgName, $this->prefix[ 'image' ] . $this->imageName, true );
						$image->saveAs( $path . $files[ 'image' ] );
						if ( $imageType == 'WEBP' ) {
							$fname = pathinfo( $files[ 'image' ], PATHINFO_BASENAME ) . '.webp';
							$image->setType( $imageType )->saveAs( $path . $fname );
							$files[ 'image' ] = $fname;
						}
					}
					catch ( Sobi\Error\Exception $x ) {
						Sobi::Error( $this->name(), SPLang::e( 'FIELD_IMG_CANNOT_RESAMPLE', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
						$image->delete();
						throw new SPException( SPLang::e( 'FIELD_IMG_CANNOT_RESAMPLE', $x->getMessage() ) );
					}
				}
				else {  // the large image always exists even if not resized
					try {
						$image->read();
						$files[ 'image' ] = $this->parseName( $entry, $orgName, $this->prefix[ 'image' ] . $this->imageName, true );
						$image->saveAs( $path . $files[ 'image' ] );
						if ( $imageType == 'WEBP' ) {
							$fname = pathinfo( $files[ 'image' ], PATHINFO_BASENAME ) . '.webp';
							$image
								->setType( $imageType )
								->saveAs( $path . $fname );
							$files[ 'image' ] = $fname;
						}
					}
					catch ( Sobi\Error\Exception $x ) {
						Sobi::Error( $this->name(), SPLang::e( 'FIELD_IMG_CANNOT_SAVE', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
						$image->delete();
						throw new SPException( SPLang::e( 'FIELD_IMG_CANNOT_SAVE', $x->getMessage() ) );
					}
				}

				/* Thumbnail handling */
				if ( $this->generateThumb ) {   // if cropping is set, thumbnail will be generated from the cropped image
					$thumb = clone $orgImage;
					$thumb->setTransparency( $this->detectTransparency );
					try {
						$thumb->resample( $this->thumbWidth ? : 0, $this->thumbHeight ? : 0 );
						$files[ 'thumb' ] = $this->parseName( $entry, $orgName, $this->prefix[ 'thumb' ] . $this->thumbName, true );
						$thumb->saveAs( $path . $files[ 'thumb' ] );
						if ( $imageType == 'WEBP' ) {
							$fname = pathinfo( $files[ 'thumb' ], PATHINFO_BASENAME ) . '.webp';
							$thumb
								->setType( $imageType )
								->saveAs( $path . $fname );
							$files[ 'thumb' ] = $fname;
						}
					}
					catch ( Sobi\Error\Exception $x ) {
						Sobi::Error( $this->name(), SPLang::e( 'FIELD_IMG_CANNOT_RESAMPLE', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
						$thumb->delete();
						throw new SPException( SPLang::e( 'FIELD_IMG_CANNOT_RESAMPLE', $x->getMessage() ) );
					}
				}

				/* Ico handling */
				$ico = clone $orgImage;
				try {
					$ico->setTransparency( $this->detectTransparency );

					$icoSize = explode( ':', Sobi::Cfg( 'image.ico_size', '100:100' ) );
					[ $originalWidth, $originalHeight ] = getimagesize( $ico->getPathname() );
					$aspectRatio = $originalWidth / $originalHeight;
					$ico->resample( (int) ceil( $icoSize[ 0 ] ), (int) ceil( $icoSize[ 0 ] / $aspectRatio ) );  // resize
//					$ico->resample( $icoSize[ 0 ] ? : 0, $icoSize[ 1 ? : 0 ] );  // resize

					$files[ 'ico' ] = $this->parseName( $entry, strtolower( $orgName ), $this->prefix[ 'ico' ] . '{orgname}' /*. $this->nid*/, true );
					$ico->saveAs( $path . $files[ 'ico' ] );    // save ico always as original image type
				}
				catch ( Sobi\Error\Exception $x ) {
					Sobi::Error( $this->name(), SPLang::e( 'FIELD_IMG_CANNOT_RESAMPLE', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
					$ico->delete();
					throw new SPException( SPLang::e( 'FIELD_IMG_CANNOT_RESAMPLE', $x->getMessage() ) );
				}

				/* Handling of original image */
				if ( !$this->keepOrg ) {
					if ( $this->crop ) {
						$orgImage->setFile( $path . $orgName );
					}
					$orgImage->delete();
				}
				else {
					$files[ 'original' ] = $this->parseName( $entry, $orgName, '{orgname}', true );
					if ( $imageType == 'WEBP' ) {
						$image = clone $orgImage;
						$fname = pathinfo( $files[ 'original' ], PATHINFO_BASENAME ) . '.webp';
						$image
							->setType( $imageType )
							->saveAs( $path . $fname );
						$files[ 'original' ] = $fname;
					}
				}

				/* add the path in front of the file names */
				foreach ( $files as $index => $file ) {
					if ( $index == 'data' ) {   /* skip the exif data */
						continue;
					}
					$files[ $index ] = $sPath . $file;
				}
			}

			// if no image to process (no newly uploaded image)
			else {
				/* delete the existing images (checkbox ticked)? */
				if ( Input::Bool( $this->nid . '_delete' ) ) {
					$this->delImgs( $entry->get( 'id' ) );
					$files = [];
				}
				else {
					/* Alt tag handling if no image is uploaded */
					if ( $this->ownAltText ) {
						$files = $this->getExistingFiles();
						if ( $files && count( $files ) ) {
							/* if alt tag hasn't changed, we have nothing to do here */
							$alt = Factory::Db()->escape( strip_tags( Input::String( $this->nid . '-alt' ) ) );
							if ( array_key_exists( 'alt', $files[ 'data' ] ) && $files[ 'data' ][ 'alt' ] == $alt ) {
								return;
							}
							$files[ 'data' ][ 'alt' ] = $alt;
						}
						else {
							return;
						}
					}
					else {
						return;
					}
				}
			}

			/* delete the temporary crop file is existing */
			if ( $cropped ) {   // delete the cropped image as it is a temporary file
				$this->delImage( $path . basename( $cropped ) );
			}
			/* remove temporary files */
			if ( $dirName && $dirName != SOBI_ROOT && FileSystem::Exists( $dirName ) ) { /* remove the temporary folder */
				FileSystem::Rmdir( $dirName );
			}

			$this->setData( $files );

			/* store the data in the database */
			if ( !$imex ) { // Imex is doing that by itself
				$this->storeData( $entry, $files );   // store the general field's data
			}
		}
	}

	/**
	 * Upload of an image via AJAX.
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function ProxyUpload()
	{
		$ident = Input::Cmd( 'ident', 'post' );
		$tempfile = Input::File( $ident, 'tmp_name' );
		$secret = md5( Sobi::Cfg( 'secret' ) );
		if ( $tempfile ) {
			$properties = Input::File( $ident );
			$orgName = $properties[ 'name' ];

			$extension = FileSystem::GetExt( $orgName );
			$orgName = str_replace( '.' . $extension, '.' . strtolower( $extension ), $orgName );
			$pathInfo = pathinfo( $orgName );
//			$orgName = StringUtils::Nid( $pathInfo[ 'filename' ], false, false, true ) . '.' . strtolower( $extension );
			$orgName = $pathInfo[ 'filename' ] . '.' . strtolower( $extension );

			/* check if uploaded file is too large */
			if ( $properties[ 'size' ] > $this->maxSize ) {
				$this->message( [ 'type' => 'error', 'text' => SPLang::e( 'FIELD_IMG_TOO_LARGE', $this->name, $properties[ 'size' ], $this->maxSize ), 'id' => '', ] );
			}

			$dirNameHash = md5( $orgName . time() . $secret );
			$dirName = SPLoader::dirPath( "tmp/files/$secret/$dirNameHash", 'front', false );
			FileSystem::Mkdir( $dirName );
			$path = $dirName . $orgName;
			$orgImage = new Image();
			if ( !$orgImage->upload( $tempfile, $path ) ) {
				$this->message( [ 'type' => 'error', 'text' => SPLang::e( 'CANNOT_UPLOAD_FILE' ), 'id' => '' ] );
			}

			$files[ 'data' ][ 'exif' ] = $orgImage->exif();
			$this->cleanExif( $files[ 'data' ][ 'exif' ] );
			if ( Sobi::Cfg( 'image_field.fix_rotation', true ) ) {
				if ( $orgImage->fixRotation() ) {
					$orgImage->save();
				}
			}

			// generate a cropped file in case the user doesn't crop it manually
			if ( $this->crop ) {
				$croppedImage = clone $orgImage;
				[ $originalWidth, $originalHeight ] = getimagesize( $path );
				$aspectRatio = $this->resizeWidth / $this->resizeHeight;
				$width = (int) min( $aspectRatio * $originalHeight, $originalWidth );
				$height = (int) min( $originalWidth / $aspectRatio, $originalHeight );
				try {
					$croppedImage->setTransparency( $this->detectTransparency );
					$croppedImage->crop( $width, $height );
					$croppedImage->saveAs( $dirName . 'cropped_' . $orgName );
					$ico = clone $croppedImage;
				}
				catch ( Sobi\Error\Exception $x ) {
					$this->message( [ 'type' => 'error', 'text' => SPLang::e( 'FIELD_IMG_CANNOT_CROP', $x->getMessage() ), 'id' => '', ] );
				}
			}
			else {
				$ico = clone $orgImage;
			}

			// create the large image
			$image = clone $orgImage;
			try {
				// if cropping, generate the image representing the cropped area
				$previewSize = explode( ':', Sobi::Cfg( 'image.preview_size', '500:500' ) );
				$image->setTransparency( $this->detectTransparency );
				$image->resample( $previewSize[ 0 ] ? : 0, $previewSize[ 1 ] ? : 0 );
				$image->saveAs( $dirName . 'resized_' . $orgName );
			}
			catch ( Sobi\Error\Exception $x ) {
				$image->delete();
				$this->message( [ 'type' => 'error', 'text' => SPLang::e( 'FIELD_IMG_CANNOT_RESAMPLE', $x->getMessage() ), 'id' => C::ES, ] );
			}

			$path = $orgImage->getPathname();

			// create small icon image
			try {
				$ico->setTransparency( $this->detectTransparency );

				$icoSize = explode( ':', Sobi::Cfg( 'image.ico_size', '100:100' ) );
				/* @todo check if you gget the ratio */
				//$ico->resample( $icoSize[ 0 ] ? : 0, $icoSize[ 1 ] ? : 0 );
				[ $originalWidth, $originalHeight ] = getimagesize( $ico->getPathname() );
				$aspectRatio = $originalWidth / $originalHeight;
				$ico->resample( (int) ceil( $icoSize[ 0 ] ), (int) ceil( $icoSize[ 0 ] / $aspectRatio ) );  // resize
//   				$ico->resample( $icoSize[ 0 ] ? : 0, $icoSize[ 1 ] ? : 0 );
				$ico->saveAs( $dirName . 'icon_' . $orgName );
			}
			catch ( Sobi\Error\Exception $x ) {
				$ico->delete();
				$this->message( [ 'type' => 'error', 'text' => SPLang::e( 'FIELD_IMG_CANNOT_RESAMPLE', $x->getMessage() ), 'id' => '', ] );
			}

			/* check for the real mime file type of the uploaded file */
			$allowed = FileSystem::LoadIniFile( SOBI_PATH . '/etc/files' );
			$realMime = SPFactory::Instance( 'services.fileinfo', $path )->mimeType();
			if ( strlen( $realMime ) && !( in_array( $realMime, $allowed ) ) ) {
				FileSystem::Delete( $path );
				$this->message( [ 'type' => 'error', 'text' => SPLang::e( 'FILE_WRONG_TYPE', $realMime ), 'id' => '' ] );
			}

			$properties[ 'tmp_name' ] = $path;

			$out = SPConfig::serialize( $properties );
			FileSystem::Write( SPLoader::dirPath( "tmp/files/$secret", 'front', false ) . '/' . $orgName . '.var', $out );

			$response = [
				'type' => 'success',
				'text' => $this->crop ? Sobi::Txt( 'IMAGE_UPLOADED_CROP', $properties[ 'name' ], $realMime ) : Sobi::Txt( 'FILE_UPLOADED', $properties[ 'name' ] ),
				'id'   => 'directory://' . $dirNameHash,
				'data' => [
					'name'     => $properties[ 'name' ],
					'type'     => $properties[ 'type' ],
					'size'     => $properties[ 'size' ],
					'original' => $dirNameHash . '/' . $properties[ 'name' ],
					'icon'     => $dirNameHash . '/' . 'icon_' . $orgName,
					'crop'     => $this->crop,
					'height'   => $this->resizeHeight,
					'width'    => $this->resizeWidth,
				],
			];
		}
		else {
			$response = [ 'type' => 'error', 'text' => SPLang::e( 'CANNOT_UPLOAD_FILE_NO_DATA' ), 'id' => '', ];
		}
		$this->message( $response );
	}

	/**
	 * Will be called also from Imex import.
	 *
	 * @param $data
	 */
	public function cleanExif( &$data )
	{
		// Wed, Feb 19, 2014 17:17:20
		// we need to remove junk from indexes too
		// it appears to be the easies method
		$data = json_encode( $data, JSON_UNESCAPED_UNICODE );
		$data = preg_replace( '/\p{Cc}+/u', C::ES, $data );
		$data = str_replace( 'UndefinedTag:', C::ES, $data );
		$data = json_decode( $data, true );
		if ( is_array( $data ) && count( $data ) ) {
			foreach ( $data as $index => $row ) {
				if ( is_array( $row ) ) {
					$this->cleanExif( $row );
				}
				else {
					if ( $row ) {
						$data[ $index ] = preg_replace( '/\p{Cc}+/u', C::ES, $row );
					}
				}
			}
		}
	}

	/**
	 * @param $sid
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function deleteData( $sid )
	{
		parent::deleteData( $sid );
		$this->delImgs( $sid );
	}

	/**
	 * Deletes all existing images of an entry.
	 *
	 * @param $sid
	 *
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function delImgs( $sid )
	{
		$files = $this->getExistingFiles();
		if ( is_array( $files ) && count( $files ) ) {
			foreach ( $files as $file ) {
				if ( $file instanceof stdClass || is_array( $file ) || !strlen( $file ) || $file == '.' || $file == '..' ) {
					continue;
				}
				$file = FileSystem::FixPath( SOBI_ROOT . "/$file" );
				/* should never happen but who knows .... */
				if ( $file == SOBI_ROOT ) {
					continue;
				}
				if ( FileSystem::Exists( $file ) ) {
					FileSystem::Delete( $file );
					$ext = pathinfo( $file, PATHINFO_EXTENSION );
					if ( $ext == 'webp' ) {
						$f = preg_replace( '/.webp$/', C::ES, $file );
						if ( $f == SOBI_ROOT ) {
							continue;
						}
						if ( FileSystem::Exists( $f ) ) {
							FileSystem::Delete( $f );
						}
					}
				}
			}
			/* delete them also from the database, but first get the created data */
			$this->created = Factory::Db()
				->select( [ 'createdTime', 'createdBy', 'createdIP' ], 'spdb_field_data', [ 'sid' => $this->sid, 'fid' => $this->fid, 'lang' => Sobi::Lang( true ) ] )
				->loadAssoc();

			Factory::Db()->delete( 'spdb_field_data', [ 'fid' => $this->fid, 'sid' => $sid ] );
		}
	}

	/**
	 * Deletes one image on one entry.
	 * Will be called also from Imex import.
	 *
	 * @param $image
	 */
	public function delImage( $image )
	{
		if ( strlen( $image ) ) {
			// should never happen but who knows ....
			if ( $image == SOBI_ROOT ) {
				return;
			}
			if ( FileSystem::Exists( $image ) ) {
				FileSystem::Delete( $image );
			}
		}
	}

	/**
	 * @param $deg
	 * @param $min
	 * @param $sec
	 * @param $hem
	 *
	 * @return float
	 */
	protected function convertGPS( $deg, $min, $sec, $hem )
	{
		$degree = (float) $deg + ( ( ( (float) $min / 60 ) + ( (float) $sec / 3600 ) / 100 ) );

		return ( $hem == 'S' || $hem == 'W' ) ? $degree * -1 : $degree;
	}

	/**
	 * @return array
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function struct()
	{
		$files = $this->getExistingFiles();

		$exifToPass = [];
		if ( isset( $files[ 'original' ] ) ) {
			$files[ 'orginal' ] = $files[ 'original' ];
		}
		if ( isset( $files[ 'data' ][ 'exif' ] ) && Sobi::Cfg( 'image_field.pass_exif', true ) ) {
			$exif = json_encode( $files[ 'data' ][ 'exif' ] );
			$exif = str_replace( 'UndefinedTag:', C::ES, $exif );
			$exif = preg_replace( '/\p{Cc}+/u', C::ES, $exif );
			$exif = json_decode( preg_replace( '/[^a-zA-Z0-9\{\}\:\.\,\(\)\"\'\/\\\\!\?\[\]\@\#\$\%\^\&\*\+\-\_]/', '', $exif ), true );
			if ( isset( $exif[ 'EXIF' ] ) ) {
				$tags = Sobi::Cfg( 'image_field.exif_data', [] );
				if ( count( $tags ) ) {
					foreach ( $tags as $tag ) {
						$exifToPass[ 'BASE' ][ $tag ] = $exif[ 'EXIF' ][ $tag ] ?? 'unknown';
					}
				}
			}
			if ( isset( $exif[ 'FILE' ] ) ) {
				$exifToPass[ 'FILE' ] = $exif[ 'FILE' ];
			}
			if ( isset( $exif[ 'FILE' ] ) ) {
				$exifToPass[ 'FILE' ] = $exif[ 'FILE' ];
			}
			if ( isset( $exif[ 'IFD0' ] ) ) {
				$tags = Sobi::Cfg( 'image_field.exif_id_data', [] );
				if ( count( $tags ) ) {
					foreach ( $tags as $tag ) {
						$exifToPass[ 'IFD0' ][ $tag ] = $exif[ 'IFD0' ][ $tag ] ?? 'unknown';
					}
				}
			}
			if ( isset( $files[ 'data' ][ 'exif' ][ 'GPS' ] ) && ( $files[ 'data' ][ 'exif' ][ 'GPS' ][ 'GPSStatus' ] != 'V' ) ) { //and if not 'void'
				$exifToPass[ 'GPS' ][ 'coordinates' ][ 'latitude' ] = $this->convertGPS( $files[ 'data' ][ 'exif' ][ 'GPS' ][ 'GPSLatitude' ][ 0 ], $files[ 'data' ][ 'exif' ][ 'GPS' ][ 'GPSLatitude' ][ 1 ], $files[ 'data' ][ 'exif' ][ 'GPS' ][ 'GPSLatitude' ][ 2 ], $files[ 'data' ][ 'exif' ][ 'GPS' ][ 'GPSLatitudeRef' ] );
				$exifToPass[ 'GPS' ][ 'coordinates' ][ 'longitude' ] = $this->convertGPS( $files[ 'data' ][ 'exif' ][ 'GPS' ][ 'GPSLongitude' ][ 0 ], $files[ 'data' ][ 'exif' ][ 'GPS' ][ 'GPSLongitude' ][ 1 ], $files[ 'data' ][ 'exif' ][ 'GPS' ][ 'GPSLongitude' ][ 2 ], $files[ 'data' ][ 'exif' ][ 'GPS' ][ 'GPSLongitudeRef' ] );
				$exifToPass[ 'GPS' ][ 'coordinates' ][ 'latitude-ref' ] = $files[ 'data' ][ 'exif' ][ 'GPS' ][ 'GPSLatitudeRef' ] ?? 'unknown';
				$exifToPass[ 'GPS' ][ 'coordinates' ][ 'longitude-ref' ] = $files[ 'data' ][ 'exif' ][ 'GPS' ][ 'GPSLongitudeRef' ] ?? 'unknown';
				$tags = Sobi::Cfg( 'image_field.exif_gps_data', [] );
				if ( count( $tags ) ) {
					foreach ( $tags as $tag ) {
						$exifToPass[ 'GPS' ][ $tag ] = $files[ 'data' ][ 'exif' ][ 'GPS' ][ 'GPS' . $tag ] ?? 'unknown';
					}
				}

			}
		}
		$float = null;
		if ( is_array( $files ) && count( $files ) ) {
			$this->cssClass = strlen( $this->cssClass ) ? $this->cssClass : 'sp-field-data';
			$this->cssClass = $this->cssClass . ' ' . $this->nid;
			$this->cleanCss();
			switch ( $this->currentView ) {
				default:
				case 'vcard':
					$img = $this->inVcard;
					break;
				case 'details':
					$img = $this->inDetails;
					break;
				case 'category':
					$img = $this->inCategory;
			}
			$prefix = $this->prefix[ $img ];
			if ( isset( $files[ $img ] ) ) {
				$show = $files[ $img ];
			}
			else {
				if ( isset( $files[ 'thumb' ] ) ) {
					$show = $files[ 'thumb' ];
					$prefix = $this->prefix[ 'thumb' ];
				}
				else {
					if ( isset( $files[ 'original' ] ) ) {
						$show = $files[ 'original' ];
						$prefix = '';
					}
					else {
						if ( isset( $files[ 'ico' ] ) ) {
							$show = $files[ 'ico' ];
							$prefix = $this->prefix[ 'ico' ];
						}
					}
				}
			}

			if ( isset( $show ) ) {
				/* get filename for alt and title tags */
				if ( $this->ownAltText
					&& array_key_exists( 'data', $files )
					&& array_key_exists( 'alt', $files[ 'data' ] )
					&& $files[ 'data' ][ 'alt' ]
				) {
					$alttag = $files[ 'data' ][ 'alt' ];
				}
				else {
					if ( $this->altPattern ) {
						$sid = Input::Sid();
						if ( $sid && $en = SPFactory::Entry( $sid ) ) {
							$alttag = $this->parseName( $en, C::ES, $this->altPattern, false, true );
						}
					}
					else {
						$alttag = array_key_exists( 'original', $files ) ? pathinfo( $files[ 'original' ], PATHINFO_FILENAME ) : str_replace( $prefix, "", pathinfo( $show, PATHINFO_FILENAME ) );
						$alttag = ( pathinfo( $alttag, PATHINFO_FILENAME ) );  /* in case it was a webp image */
					}
				}
				switch ( $img ) {
					case 'thumb':
						$float = $this->thumbFloat;
						break;
					case 'image':
						$float = $this->imageFloat;
						break;
				}
				if ( $this->currentView == 'category' ) {
					$float = $this->float;
				}

				$isWebp = 'false';
				$ext = pathinfo( $show, PATHINFO_EXTENSION );
//				if ( $ext == 'webp' && $this->convert ) {   /* webp image */
				if ( $ext == 'webp' ) {   /* webp image */
					$isWebp = 'true';
					$showOri = preg_replace( '/.webp$/', C::ES, $show );
					$originalfile = FileSystem::FixUrl( Sobi::Cfg( 'live_site' ) . $showOri );
					$originaltype = FileSystem::Exists( $originalfile ) ? image_type_to_mime_type( exif_imagetype( $showOri ) ) : C::ES;
					$data[ '_source_0' ] = [
						'_complex'    => 1,
						'_data'       => null,
						'_attributes' => [
							'srcset' => FileSystem::FixUrl( Sobi::Cfg( 'live_site' ) . $show ),
							'type'   => 'image/webp',
						],
					];
					$data[ '_source_1' ] = [
						'_complex'    => 1,
						'_data'       => null,
						'_attributes' => [
							'srcset' => $originalfile,
							'type'   => $originaltype,
						],
					];
					$data[ 'img' ] = [
						'_complex'    => 1,
						'_data'       => null,
						'_attributes' => [
							'class' => $this->cssClass,
							'src'   => FileSystem::FixUrl( Sobi::Cfg( 'live_site' ) . $showOri ),
							'alt'   => $alttag,
							'title' => $alttag,
						],
					];
					if ( $float ) {
						$data[ 'img' ][ '_attributes' ][ 'style' ] = "float:$float;";
					}
					$_data = [ 'picture' => $data ];

				}
				else {  /* no webp image */
					$data = [
						'_complex'    => 1,
						'_data'       => null,
						'_attributes' => [
							'class' => $this->cssClass,
							'src'   => FileSystem::FixUrl( Sobi::Cfg( 'live_site' ) . $show ),
							'alt'   => $alttag,
							'title' => $alttag,
						],
					];
					if ( $float ) {
						$data[ '_attributes' ][ 'style' ] = "float:$float;";
					}
					$_data = [ 'img' => $data ];
				}

				return [
					'_complex'    => 1,
					'_data'       => $_data,
					'_attributes' => [
						'icon'      => isset( $files[ 'ico' ] ) ? FileSystem::FixPath( $files[ 'ico' ] ) : null,
						'image'     => isset( $files[ 'image' ] ) ? FileSystem::FixPath( $files[ 'image' ] ) : null,
						'thumbnail' => isset( $files[ 'thumb' ] ) ? FileSystem::FixPath( $files[ 'thumb' ] ) : null,
						'original'  => isset( $files[ 'original' ] ) ? FileSystem::FixPath( $files[ 'original' ] ) : null,
						'class'     => $this->cssClass,
						'webp'      => $isWebp,
					],
					'_options'    => [ 'exif' => $exifToPass ],
				];
			}
		}
	}

	/**
	 * @param $response
	 *
	 * @throws SPException
	 */
	protected function message( $response )
	{
		SPFactory::mainframe()
			->cleanBuffer()
			->customHeader();
		echo json_encode( $response );
		exit;
	}

	/**
	 * AJAX
	 *
	 * @return void
	 */
	public function ProxyIcon()
	{
		$secret = md5( Sobi::Cfg( 'secret' ) );
		$file = Input::String( 'file' );
		$file = explode( '/', $file );
		$dirName = SPLoader::dirPath( "tmp/files/$secret/$file[0]", 'front', true );
		$fileName = $dirName . $file[ 1 ];

		if ( !function_exists( 'exif_imagetype' ) ) {
			header( 'Content-Type:' . 'image/xyz' );
		}
		else {
			header( 'Content-Type:' . image_type_to_mime_type( exif_imagetype( $fileName ) ) );
		}
		header( 'Content-Length: ' . filesize( $fileName ) );
		readfile( $fileName );

		exit;
	}

	/**
	 * @param $directory
	 *
	 * @return array
	 */
	private function getAjaxFiles( $directory )
	{
		$secret = md5( Sobi::Cfg( 'secret' ) );
		$coordinates = C::ES;
		$dirNameHash = str_replace( 'directory://', C::ES, $directory );
		if ( strstr( $dirNameHash, '::coordinates://' ) ) {
			$struct = explode( '::coordinates://', $dirNameHash );
			$dirNameHash = $struct[ 0 ];
			$coordinates = $struct[ 1 ];
		}
		$data = $dirNameHash;
		$dirName = SPLoader::dirPath( "tmp/files/$secret/$dirNameHash", 'front', false );
		$files = false;
		if ( FileSystem::Exists( $dirName ) ) {
			$files = scandir( $dirName );
		}

		return [ $data, $dirName, $files, $coordinates ];
	}

	/**
	 * @return array|mixed
	 */
	protected function getExistingFiles()
	{
		$files = $this->getRaw();
		if ( is_string( $files ) && strlen( $files ) > 0 ) {
			try {
				$files = SPConfig::unserialize( $files );

				//return $files;
			}
			catch ( SPException $x ) {
				return [];
			}
		}
		else {
			$files = [];
		}

		return count( $files ) ? $files : [];
	}

	/**
	 * @param $entry
	 * @param $files
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function storeData( $entry, $files )
	{
		if ( is_array( $files ) && count( $files ) ) {
			if ( get_class( $this ) == 'SPField_Image' ) {  // if saved from frontend
				$this->verify( $entry, 'post' );
			}

			$db = Factory::Db();
			$time = Input::Now();
			$IP = Input::Ip4();
			$uid = Sobi::My( 'id' );

			/* if we are here, we can save these data */
			/* collect the needed params */
			$params = [];
			$params[ 'publishUp' ] = $entry->get( 'publishUp' ) ?? $db->getNullDate();
			$params[ 'publishDown' ] = $entry->get( 'publishDown' ) ?? $db->getNullDate();
			$params[ 'fid' ] = $this->fid;
			$params[ 'sid' ] = $entry->get( 'id' );
			$params[ 'section' ] = Sobi::Reg( 'current_section' );
			$params[ 'lang' ] = Sobi::Lang();
			$params[ 'enabled' ] = $entry->get( 'state' );
			$params[ 'baseData' ] = SPConfig::serialize( $files );
			$params[ 'params' ] = C::ES;
			$params[ 'options' ] = null;
			$params[ 'approved' ] = $entry->get( 'approved' );
			$params[ 'confirmed' ] = $entry->get( 'confirmed' );

			/* if it is the first version, it is a new entry */
			if ( $entry->get( 'version' ) == 1 ) {
				$params[ 'createdTime' ] = $time;
				$params[ 'createdBy' ] = $uid;
				$params[ 'createdIP' ] = $IP;
			}
			else {
				/* get the stored creation data */
				if ( is_array( $this->created ) && count( $this->created ) ) {
					$params[ 'createdTime' ] = $this->created[ 'createdTime' ];
					$params[ 'createdBy' ] = $this->created[ 'createdBy' ];
					$params[ 'createdIP' ] = $this->created[ 'createdIP' ];
				}
			}
			$params[ 'updatedTime' ] = $time;
			$params[ 'updatedBy' ] = $uid;
			$params[ 'updatedIP' ] = $IP;
			$params[ 'copy' ] = (int) !$entry->get( 'approved' );

//			if ( ( Sobi::My( 'id' ) == $entry->get( 'owner' ) ) && ( $this->editLimit > 0 ) ) {
//				--$this->editLimit;         // storeData is called only if the image has change in some way
//			}
			$this->setEditLimit( $entry, $params[ 'baseData' ] );
			$params[ 'editLimit' ] = $this->editLimit;

			/* save it to the database */
			$this->saveToDatabase( $params, $entry->get( 'version' ), false );
		}
	}

	/**
	 * @param $entry
	 * @param $files
	 * @param $cloneFiles
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function cloneFiles( $entry, $files, $cloneFiles )
	{
		$orgName = basename( $files[ 'original' ] ?? $files[ 'image' ] );

		// clean the filename
		$orgName = str_replace( $this->prefix[ 'image' ], C::ES, $orgName );    // remove the prefix
		$orgName = preg_replace( '/\d{1,}clone\_/', C::ES, $orgName, 1 );   // remove clone identifier

		$sPath = $this->parseName( $entry, $orgName, $this->savePath );
		if ( isset( $files[ 'original' ] ) && FileSystem::Exists( SOBI_ROOT . '/' . $files[ 'original' ] ) ) {
			$cloneFiles[ 'original' ] = $sPath . $this->parseName( $entry, $orgName, '{id}clone_{orgname}', true );
			FileSystem::Copy( SOBI_ROOT . '/' . $files[ 'original' ], SOBI_ROOT . '/' . $cloneFiles[ 'original' ] );
		}

		if ( isset( $files[ 'image' ] ) && FileSystem::Exists( SOBI_ROOT . '/' . $files[ 'image' ] ) ) {
			$cloneFiles[ 'image' ] = $sPath . $this->parseName( $entry, $orgName, $this->prefix[ 'image' ] . '{id}clone_' . $this->imageName, true );
			FileSystem::Copy( SOBI_ROOT . '/' . $files[ 'image' ], SOBI_ROOT . '/' . $cloneFiles[ 'image' ] );
		}

		if ( isset( $files[ 'thumb' ] ) && FileSystem::Exists( SOBI_ROOT . '/' . $files[ 'thumb' ] ) ) {
			$cloneFiles[ 'thumb' ] = $sPath . $this->parseName( $entry, $orgName, $this->prefix[ 'thumb' ] . '{id}clone_' . $this->thumbName, true );
			FileSystem::Copy( SOBI_ROOT . '/' . $files[ 'thumb' ], SOBI_ROOT . '/' . $cloneFiles[ 'thumb' ] );
		}

		if ( isset( $files[ 'ico' ] ) && FileSystem::Exists( SOBI_ROOT . '/' . $files[ 'ico' ] ) ) {
			$cloneFiles[ 'ico' ] = $sPath . $this->parseName( $entry, strtolower( $orgName ), $this->prefix[ 'ico' ] . '{id}clone_{orgname}', true );
			FileSystem::Copy( SOBI_ROOT . '/' . $files[ 'ico' ], SOBI_ROOT . '/' . $cloneFiles[ 'ico' ] );
		}

		$this->storeData( $entry, $cloneFiles );
	}

	/**
	 * @param string $data
	 * @param int $section
	 * @param bool $startWith
	 * @param bool $ids
	 *
	 * @return array
	 */
	public function searchSuggest( $data, $section, $startWith = true, $ids = false )
	{
		return [];
	}

	/**
	 * Compares two versions of the field's data visually.
	 *
	 * @param $revision
	 * @param $current
	 *
	 * @return array
	 */
	public function compareRevisions( $revision, $current )
	{
		$rev = $cur = null;
		if ( $revision ) {
			if ( isset( $revision[ 'image' ] ) ) {
				$rev[] = 'img: ' . basename( $revision[ 'image' ] );
			}
			else {
				$rev[] = 'img: ' . basename( $revision );
			}
			if ( array_key_exists( 'data', $revision ) ) {
				if ( array_key_exists( 'alt', $revision[ 'data' ] ) ) {
					$rev[] = 'alt: ' . $revision[ 'data' ][ 'alt' ];
				}
			}
			$rev = implode( "\n", ( $rev ) );
		}
		if ( $current ) {
			if ( isset( $current[ 'image' ] ) ) {
				$cur[] = 'img: ' . basename( $current[ 'image' ] );
			}

			else {
				$rev[] = 'img: ' . basename( $current );
			}
			if ( array_key_exists( 'data', $current ) ) {
				if ( array_key_exists( 'alt', $current[ 'data' ] ) ) {
					$cur[] = 'alt: ' . $current[ 'data' ][ 'alt' ];
				}
			}
			$cur = implode( "\n", ( $cur ) );
		}

		return [ 'current' => $cur, 'revision' => $rev ];
	}

	/**
	 * Returns the raw data formatted for the history revision.
	 *
	 * @return mixed|string|null
	 */
	public function getData( $data = null )
	{
		if ( !$data ) {
			$data = $this->getExistingFiles();

			return $data;
		}
		if ( !is_array( $data ) ) {
			try {
				$data = SPConfig::unserialize( $data );
			}
			catch ( SPException $x ) {
			}
		}

		return $data;
	}
}