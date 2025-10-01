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
 * @created 11-Jan-2009 by Radek Suski
 * @modified 30 April 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

use Sobi\C;
use Sobi\FileSystem\FileSystem;
use Sobi\Input\Input;
use Sobi\Lib\Factory;
use Sobi\Utils\Arr;
use Sobi\Utils\StringUtils;
use Sobi\Utils\Type;

/**
 * Class SPHtml_Input
 *
 * @formatter:off
 * @method static modalWindow( string $header, string $id = C::ES, string $content = C::ES, string $class = C::ES, string $closeText = 'CLOSE', string $saveText = 'SAVE', string $style = C::ES, bool $dismiss = true, string $role = C::ES, bool $dismissModalOnSave = true )
 * @method static radioList( string $name, array $values, string $id, $checked, array|string $params = C::ES, string $appearance = 'block', bool $asArray = false )
 * @method static checkBoxGroup( string $name, array $values, string $id, $selected = C::ES, $params = C::ES, string $appearance = 'block', bool $asArray = false )
 * @method static checkbox( string $name, string $value, string $label = C::ES, string $id = C::ES, bool $checked = false, $params = C::ES, string $appearance = 'block', string $image = C::ES )
 * @method static radio( string $name, string $value, string $label = C::ES, string $id = C::ES, string|bool $checked = false, $params = C::ES, string $appearance = 'block', string $image = C::ES )
 * @method static radioGroup( string $name, array $values, string $id, array|string $checked = false, array|string $param = C::ES )
 * @method static select( string $name, array $values, string|array $selected = C::ES, bool $multi = false, string|array $params = C::ES )
 * @method static text( string $name, string $value = C::ES, $params = C::ES )
 * @method static textarea( string $name, string $value = C::ES, bool $editor = false, $width = C::ES, int $height = '350', string|array $params = C::ES, array $editorParams = [] )
 * @method static button( string $name, string $value = C::ES, string|array $params = C::ES, string $class = C::ES, string $icon = C::ES )
 * @method static dategetter( string $name, $value, string $class = C::ES, $dateFormat = SPC::DEFAULT_DB_DATE, $params = C::ES, $addOffset = 'true' )
 * @method static datepicker( string $name, $value, string $dateFormat = SPC::DEFAULT_DB_DATE, $params = C::ES, string $icon = 'calendar', $gmt = true, string $timeOffset = C::ES )
 * @method static userGetter( string $name, $value, $params = C::ES, string $class = C::ES, string $format = '%user' )
 * @method static userselector( string $name, $value, $groups = null, $params = C::ES, string $icon = 'user', string $header = 'USER_SELECT_HEADER', string $format = '%user', $orderBy = 'id' )
 * @method static fileUpload( string $name, string $accept = '*', string $value = C::ES, string $class = 'spctrl-file-upload', string $task = 'file.upload', array $request = [], $param = C::ES )
 * @method static toggle( string $name, string $value, string $id, string $prefix, $params = C::ES )
 * @method static tristate( string $name, string $value, string $id, string $prefix, $params = C::ES )
 * @method static hidden( string $name, string $value = C::ES, string $id = C::ES, $params = C::ES )
 * @method static submit( string $name, string $value = C::ES, $params = C::ES )
 * @formatter:on
 */
abstract class SPHtml_Input
{
	/**
	 * @param $name
	 * @param $args
	 *
	 * @return false|mixed
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	public static function __callStatic( $name, $args )
	{
		$method = '_' . $name;
		if ( defined( 'SOBIPRO_ADM' ) ) {
			return self::$method( ... $args );
//			return call_user_func_array( [ 'self', '_' . $name ], $args );
		}
		else {
			static $className = false;
			if ( !$className ) {
				$package = Sobi::Reg( 'current_template' );
				if ( FileSystem::Exists( FileSystem::FixPath( $package . '/input.php' ) ) ) {
					$path = FileSystem::FixPath( $package . '/input.php' );
					ob_start();
					$content = file_get_contents( $path );
					$class = [];
					preg_match( '/\s*(class)\s+(\w+)/', $content, $class );
					if ( isset( $class[ 2 ] ) ) {
						$className = $class[ 2 ];
					}
					else {
						Sobi::Error( 'Custom Input Class', SPLang::e( 'Cannot determine class name in file %s.', str_replace( SOBI_ROOT, C::ES, $path ) ), C::WARNING, 0 );

						return false;
					}
					require_once( $path );
				}
				else {
					$className = true;
				}
			}
			if ( is_string( $className ) && method_exists( $className, $name ) ) {
				return call_user_func_array( [ $className, $name ], $args );
			}
			else {
				return self::$method( ... $args );
//				return call_user_func_array( [ 'self', '_' . $name ], $args );
			}
		}
	}

	/**
	 * Correct the params. Creating an array if it is a comma separated string and trimming the values.
	 *
	 * @param $params
	 */
	public static function checkArray( &$params )
	{
		if ( $params && is_string( $params ) && strstr( $params, ',' ) ) {
			$arrUtils = new Arr();
			$arrUtils->fromString( $params, ',', '=' );
			$params = $arrUtils->toArr();
		}
		$tempParams = [];
		if ( is_array( $params ) ) {
			foreach ( $params as $key => $value ) {
				$tempParams[ trim( $key ) ] = $value;
			}
		}
		$params = is_array( $tempParams ) ? $tempParams : [];
	}

	/**
	 * @param $params
	 * @param array $options
	 */
	public static function cleanParams( &$params, $options = [] )
	{
		static $list = [ 'type', 'dateFormat', 'icon', 'addOffset', 'translatable', 'condition', 'invert-condition' ];
		$options = array_merge( $options, $list );

		if ( $params && count( $options ) ) {
			foreach ( $options as $option ) {
				if ( array_key_exists( $option, $params ) ) {
					unset ( $params[ $option ] );
				}
			}
		}
	}

	/**
	 * @param $params
	 *
	 * @return string
	 */
	public static function paramsToString( $params ): string
	{
		$paramString = C::ES;
		self::checkArray( $params );
		if ( $params && is_array( $params ) && count( $params ) ) {
			self::cleanParams( $params );

			foreach ( $params as $param => $value ) {
				if ( $param == 'required' ) {
					continue;
				}
				$value = trim( str_replace( '"', '\'', $value ) );
				$param = str_replace( [ '\'', '"' ], C::ES, trim( $param ) );
				$paramString .= " $param=\"$value\"";
			}
		}

		return $paramString;
	}

	/**
	 * @param string|null $txt
	 *
	 * @return string
	 * @throws \Exception
	 *
	 * @method static translate( string $txt )
	 */
	public static function _translate( ?string $txt ): string
	{
		if ( $txt != null && strlen( $txt ) ) {
			if ( strstr( $txt, 'translate:' ) ) {
				$matches = [];
				preg_match( '/translate\:\[(.*)\]/', $txt, $matches );
				$txt = str_replace( $matches[ 0 ], Sobi::Txt( $matches[ 1 ] ), $txt );
			}

			return StringUtils::Clean( $txt );
		}
		else {
			return C::ES;
		}
	}

	/**
	 * @param $params
	 *
	 * @return string
	 */
	protected static function getTrimmed( &$params ): string
	{
		$disabled = C::ES;
		if ( $params && array_key_exists( 'condition', $params ) ) {
			$disabled = $params[ 'condition' ] == 'trim' ? ' disabled="disabled"' : C::ES;
			unset( $params[ 'condition' ] );
		}

		return $disabled;
	}

	/**
	 * Creates ajax file upload field.
	 * For SobiPro front-end and back-end.
	 *
	 * @param string $name - name of the html field
	 * @param string $accept - accepted file types
	 * @param string $value - possible value for the inbox
	 * @param string $class - class name
	 * @param string $task - task override
	 * @param array $request - custom request
	 * @param string|array $param
	 *
	 * @return string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function _fileUpload( string $name, string $accept = '*', $value = C::ES, string $class = 'spctrl-file-upload', string $task = 'file.upload', array $request = [], $param = C::ES ): string
	{
		$scripts = [ 'Jquery.jquery-form', 'fileupload' ];
//		if ( is_string( $scripts ) ) {
//			$scripts = SPFactory::config()->structuralData( $scripts );
//		}
		SPFactory::header()->addJsFile( $scripts ); //( 'addJsFile', [ 'script' => 'Jquery.jquery-form', 'defer' => null ] );

		if ( !count( $request ) ) {
			$request = [
				'option'                        => 'com_sobipro',
				'task'                          => $task,
				'sid'                           => Sobi::Section(),
				'ident'                         => $name . '-file',
				Factory::Application()->token() => 1,
				'format'                        => 'raw',
			];
		}
		$uid = StringUtils::FieldNid( $name );
		$disabled = ( $name == 'SobiProExtension' ) && defined( 'SOBI_TRIMMED' ) ? "disabled" : C::ES;

		$fw = defined( 'SOBIPRO_ADM' ) ? C::BOOTSTRAP5 : Sobi::Cfg( 'template.framework-style', C::BOOTSTRAP5 );

		$html = "<div class=\"$class\" data-section=\"" . Sobi::Section() . '">';

		/* Bootstrap 5 */
		if ( $fw == C::BOOTSTRAP5 ) {
			$finput = "<div class=\"input-group spctrl-fileupload $disabled\" aria-label=\"" . Sobi::Txt( 'ARIA.FILEUP-GROUP' ) . '\">';

			$finput .= "<input name=\"$name-file\" class=\"form-control selected\" type=\"file\" size=\"0\" value=\"$value\" id=\"$uid\" accept=\"$accept\"" . ( $disabled ? ' disabled="disabled"' : C::ES ) . '/>';
			$finput .= '<label class="input-group-text upload hidden" for="' . $uid . '" rel=\'' . json_encode( $request ) . '\'>' . Sobi::Txt( 'UPLOAD_SELECT' ) . '</label>';
			$finput .= '<button class="btn btn-secondary remove disabled" disabled="disabled" type="button">' . Sobi::Icon( 'trash' ) . '</button>';

			$finput .= '</div>';    /* close input-group */

			$progress = '<div class="hidden progress-container progress mt-2">';
			$progress .= '<div class="progress-bar bg-success" role="progressbar" style="width:0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>';
			$progress .= '</div>';
		}

		/* Bootstrap 4 */
		elseif ( $fw == C::BOOTSTRAP4 ) {
			$finput = '<div class="input-group w-100" aria-label="' . Sobi::Txt( 'ARIA.FILEUP-GROUP' ) . '\">';

			$finput .= "<div class=\"custom-file spctrl-fileupload\">";
			$finput .= "<input name=\"$name-file\" class=\"custom-file-input selected\" type=\"file\" size=\"0\" value=\"$value\" id=\"$uid\" accept=\"$accept\"/>";
			$finput .= '<label class="custom-file-label upload" for="' . $uid . '" rel=\'' . json_encode( $request ) . '\'>' . Sobi::Txt( 'UPLOAD_SELECT' ) . '</label>';
			$finput .= '</div>';

			$finput .= '<div class="input-group-append">';
			$finput .= '<button class="btn btn-secondary remove disabled" disabled="disabled" type="button">' . Sobi::Icon( 'trash' ) . '</button>';
			$finput .= '</div>';    /* input-group-append */

			$finput .= '</div>';    /* close input-group */

			$progress = '<div class="hidden progress-container progress mt-2">';
			$progress .= '<div class="progress-bar bg-success" role="progressbar" style="width:0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>';
			$progress .= '</div>';
		}

		/* Bootstrap 3 */
		elseif ( $fw == C::BOOTSTRAP3 ) {
			$finput = '<div class="input-group spctrl-fileupload" aria-label="' . Sobi::Txt( 'ARIA.FILEUP-GROUP' ) . '\">';

			$finput .= "<input type=\"text\" readonly=\"readonly\" class=\"form-control selected\" value=\"$value\"/>";
			$finput .= "<input name=\"$name-file\" class=\"hidden\" type=\"file\" size=\"0\" value=\"\" id=\"$uid\" accept=\"$accept\"/>";

			$finput .= "<div class=\"input-group-btn\">";
			$finput .= '<button class="btn btn-default select" type="button">' . Sobi::Icon( 'eye' ) . '&nbsp;' . Sobi::Txt( 'UPLOAD_SELECT' ) . '</button>';
			$finput .= '<button class="btn btn-default upload hidden" disabled="disabled" type="button" rel=\'' . json_encode( $request ) . '\'></button>';
			$finput .= '<button class="btn btn-default remove disabled" disabled="disabled" type="button">' . Sobi::Icon( 'trash' ) . '</button>';
			$finput .= '</div>';    /* close input-group-btn */

			$finput .= '</div>';    /* close input-group */

			$progress = '<div class="hidden progress-container">';
			$progress .= '<div class="progress">';
			$progress .= '<div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%">0%</div>';
			$progress .= '</div>';
			$progress .= '</div>';
		}

		/* Bootstrap 2 */
		else {
			$finput = '<div class="spctrl-fileupload input-append" aria-label="' . Sobi::Txt( 'ARIA.FILEUP-GROUP' ) . '\">';

			$finput .= "<input name=\"$name-file\" class=\"hidden sp-hidden\" type=\"file\" size=\"0\" value=\"\" id=\"$uid\" accept=\"$accept\"/>";
			$sclass = is_array( $param ) ? ( array_key_exists( 'sclass', $param ) ? $param[ 'sclass' ] : C::ES ) : C::ES;
			$finput .= "<input type=\"text\" readonly=\"readonly\" class=\"selected $sclass\" value=\"$value\"/>";

			$finput .= '<button class="btn select add-on" type="button">' . Sobi::Icon( 'eye' ) . '&nbsp;' . Sobi::Txt( 'UPLOAD_SELECT' ) . '</button>';
			$finput .= '<button class="btn upload hidden" disabled="disabled" type="button" rel=\'' . json_encode( $request ) . '\'></button>';
			$finput .= '<button class="btn remove disabled add-on" disabled="disabled" type="button">' . Sobi::Icon( 'trash' ) . '</button>';

			$finput .= '</div>';    /* close control-group */

			$progress = '<div class="progress-container progress progress-success hidden">';
			$progress .= '<div class="progress-bar bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%">0%</div>';
			$progress .= '</div>';
		}

		/* no close button as it won't open again without reload -> no further messages */
		$alert = '<div class="alert alert-danger hidden"><div></div></div>';
		$alert .= "<input type=\"hidden\" name=\"$name\" value=\"\" class='idStore'/>";

		$html .= $finput . $progress . $alert . '</div>';    /* and close $class */

		Sobi::Trigger( 'Field', 'File', [ &$html ] );

		return $html;
	}

	/**
	 * Creates an HTML input box.
	 * For SobiPro front-end and back-end.
	 *
	 * @param string $name - name of the html field
	 * @param string $value - selected value
	 * @param string|array $params - two-dimensional array with additional html parameters. Can be also string defined, comma separated array with equal sign as key to index separator.
	 *
	 * @return string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function _text( string $name, $value = C::ES, $params = C::ES ): string
	{
		self::checkArray( $params );
		if ( array_key_exists( 'length', $params ) ) {
			unset ( $params[ 'length' ] );
		}

		$fw = defined( 'SOBIPRO_ADM' ) ? C::BOOTSTRAP5 : Sobi::Cfg( 'template.framework-style', C::BOOTSTRAP5 );

		$required = C::ES;
		if ( $params && is_array( $params ) && array_key_exists( 'required', $params ) ) {
			$required = $params[ 'required' ] ? 'required' : C::ES;
			unset ( $params[ 'required' ] );
		}

		$class = $params[ 'class' ] ?? C::ES;
		if ( $fw >= C::BOOTSTRAP3 && strpos( $class, 'form-control' ) === false ) {
			$class .= ' form-control';
		}
		if ( $params && is_array( $params ) && array_key_exists( 'class', $params ) ) {
			unset ( $params[ 'class' ] );
		}

		$type = array_key_exists( 'type', $params ) ? $params[ 'type' ] : 'text';

		$params = self::paramsToString( $params );

		$value = strlen( (string) $value ) ? str_replace( '"', '&quot;', StringUtils::Entities( $value, true ) ) : C::ES;

		$html = "<input type=\"$type\" class=\"$class $required\" name=\"$name\" value=\"$value\" $params/>";

		Sobi::Trigger( 'Field', ucfirst( __FUNCTION__ ), [ &$html ] );

		return $html;
	}

	/**
	 * Creates simple HTML SubmitButton.
	 *
	 * @param string $name - name of the html field
	 * @param string $value - selected value
	 * @param string|array $params - two-dimensional array with additional html parameters.
	 *          Can be also string defined, comma separated array with equal sign as key to index separator.
	 *
	 * @return string
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	public static function _submit( string $name, $value = C::ES, $params = C::ES ): string
	{
		$params = self::paramsToString( $params );
		$value = self::translate( $value );
		$value = strlen( $value ) ? StringUtils::Entities( $value, true ) : C::ES;
		$html = "<input type=\"submit\" name=\"$name\" value=\"$value\" $params/>";

		Sobi::Trigger( 'Field', ucfirst( __FUNCTION__ ), [ &$html ] );

		return $html;
	}

	/**
	 * Displays a hidden token field.
	 *
	 * @return string
	 */
	public static function _token(): string
	{
		return '<input type="hidden" name="' . Factory::Application()->token() . '" value="1" />';
	}

	/**
	 * Creates simple HTML SubmitButton.
	 * If $icon is given, it will be prepended to the $value
	 *
	 * @param string $name - name of the html field
	 * @param string $value - selected value
	 * @param string|array $params - two-dimensional array with additional html parameters. Can be also string defined, comma separated array with equal sign as key
	 *     to index separator.
	 * @param string $class
	 * @param string $icon
	 *
	 * @return string
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	public static function _button( string $name, $value = C::ES, $params = C::ES, string $class = C::ES, string $icon = C::ES ): string
	{
		self::checkArray( $params );

		$ariaAfter = $ariaBefore = C::ES;
		if ( $params && is_array( $params ) ) {
			if ( array_key_exists( 'stand-alone', $params ) ) {
				unset( $params[ 'stand-alone' ] );
			}
			if ( array_key_exists( 'icon', $params ) ) {
				unset( $params[ 'icon' ] );
			}
			if ( array_key_exists( 'aria-after', $params ) ) {
				$ariaAfter = $params[ 'aria-after' ];
				unset( $params[ 'aria-after' ] );
			}
			if ( array_key_exists( 'aria-before', $params ) ) {
				$ariaBefore = $params[ 'aria-before' ];
				unset( $params[ 'aria-before' ] );
			}
		}
		if ( $icon ) {
			$icon = Sobi::Icon( $icon );
			if ( $ariaBefore ) {
				$icon = '<span class="visually-hidden">' . Sobi::Txt( $ariaBefore ) . '</span>' . $icon;
			}
			if ( $ariaAfter ) {
				$icon .= '<span class="visually-hidden">' . Sobi::Txt( $ariaAfter ) . '</span>';
			}
		}

		$trigger = C::ES;
		// bootstrap modal needs a href
		if ( isset( $params[ 'href' ] ) && !( strstr( $params[ 'href' ], '#' ) ) ) {
			SPFactory::header()->addJsCode( "
				function _{$name}Redirect()
				{
					window.location ='{$params['href']}';
					return false;
				}
			"
			);
			$params[ 'href' ] = htmlentities( $params[ 'href' ] );
			$trigger = "onclick=\"{$name}Redirect()\"";
			unset( $params[ 'href' ] );
		}

		if ( $class ) {
			$params[ 'class' ] = $class;
		}

		$params = self::paramsToString( $params );
		$value = strlen( $value ) ? self::translate( $value ) : C::ES;

		$html = "<button type=\"button\" name=\"$name\" $trigger $params>$icon $value</button>";

		Sobi::Trigger( 'Field', ucfirst( __FUNCTION__ ), [ &$html ] );

		return $html;
	}

	/**
	 * Creates a textarea field with or without WYSIWYG editor.
	 * For SobiPro front-end and back-end.
	 *
	 * @param string $name - name of the html field
	 * @param string $value - selected value
	 * @param bool $editor - true = WYSIWYG editor
	 * @param string|int $width - width of the created textarea field in pixel
	 * @param string|int $height - height of the created textarea field in pixel
	 * @param array|string $params - two-dimensional array with additional html parameters. Can be also string defined, comma separated array with equal sign as key to index
	 *     separator.
	 * @param array $editorParams - parameters for the WYSIWYG editor only
	 *
	 * @return string
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	public static function _textarea( string $name, $value = C::ES, bool $editor = false, $width = C::ES, $height = 350, $params = C::ES, array $editorParams = [] ): string
	{
//		$fw = ( defined( 'SOBIPRO_ADM' ) ) ? C::BOOTSTRAP5 : Sobi::Cfg( 'template.framework-style', C::BOOTSTRAP5 );
		$value = $value ?? C::ES;
		$editorType = $editor;
		self::checkArray( $params );

		/* $width contains originally the column width, but the width for the WYSIWYG editor is 100% within the column width */
		$width = '100%';
		Sobi::Trigger( 'BeforeCreateField', ucfirst( __FUNCTION__ ), [ &$name, &$value, &$editor, &$width, &$height, &$params ] );

		$id = $params && is_array( $params ) && array_key_exists( 'id', $params ) ? $params[ 'id' ] : C::ES;

		if ( !isset( $params[ 'style' ] ) ) {
			$params[ 'style' ] = "height: {$height}px;";
		}

		$value = StringUtils::Entities( $value );

		/* WYSIWYG editor */
		if ( $editorType ) {
			$editorClass = SPLoader::loadClass( Sobi::Cfg( 'html.editor', 'cms.html.editor' ) );
			if ( $editorClass ) {
				$editor = new $editorClass();
				if ( !is_array( $editorParams ) && strlen( $editorParams ) ) {
					$editorParams = (array) SPFactory::config()->structuralData( $editorParams );
				}
				$editorParams[ 'class' ] = isset( $params[ 'class' ] ) ? $params[ 'class' ] .= ' form-control' : 'form-control';
				$editorParams[ 'id' ] = $id;

				$area = $editor->display( $name, $value, $width, $height, ( boolean ) Sobi::Cfg( 'html.editor_buttons', false ), $editorParams );
			}
		}

		/* simple text area */
		else {
			$params[ 'class' ] = isset( $params[ 'class' ] ) ? $params[ 'class' ] .= ' form-control' : 'form-control';
			$params = self::paramsToString( $params );
			$area = "<textarea name=\"$name\" aria-describedby=\"$id\" $params>$value</textarea>";
		}

		Sobi::Trigger( 'Field', ucfirst( __FUNCTION__ ), [ &$area ] );

		return $area;
	}

	/**
	 * Creates a group of check boxes.
	 * For SobiPro front-end and back-end.
	 *
	 * @param string $name - name of the html field
	 * @param array $values - two-dimensional array with values and their labels. array( 'enabled' => 1, 'disabled' => 0 )
	 * @param string $id - id prefix of the field
	 * @param string|array $selected - two-dimensional array with values and their labels. array( 'enabled' => 1, 'disabled' => 0 )
	 * @param string|array $params - two-dimensional array with additional html parameters. Can be also string defined, comma separated array with equal sign as key
	 *     to index separator.
	 * @param string $appearance
	 * @param bool $asArray - returns array instead of a string
	 *
	 * @return array|string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function _checkBoxGroup( string $name, array $values, string $id, $selected = C::ES, $params = C::ES, string $appearance = 'block', bool $asArray = false )
	{
		self::checkArray( $values );
		if ( $selected !== C::ES && !is_array( $selected ) ) {
			$selected = [ $selected ];
		}
		else {
			if ( !is_array( $selected ) ) {
				$selected = [];
			}
		}
		$list = [];
		if ( count( $values ) ) {
			foreach ( $values as $value => $label ) {
				$checked = in_array( $value, $selected, true );
				if ( is_array( $label ) ) {
					$image = $label[ 'image' ];
					$value = $label[ 'label' ];
				}
				else {
					$image = C::ES;
				}
				$list[] = self::checkbox( $name . '[]', $value, $label, $id . '_' . $value, $checked, $params, $appearance, $image );
			}
		}
		Sobi::Trigger( 'Field', ucfirst( __FUNCTION__ ), [ &$list ] );

		return $asArray ? $list : ( count( $list ) ? C::ES . implode( "", $list ) . C::ES : C::ES );
	}

	/**
	 * Creates single checkbox.
	 * For SobiPro front-end and back-end.
	 *
	 * @param string $name - name of the html field
	 * @param string $value - values of the html field
	 * @param string $label - label to display beside the field.
	 * @param string $id - id of the field
	 * @param bool $checked - is selected or not / or string $checked the checked value
	 * @param string|array $params - two-dimensional array with additional html parameters. Can be also string defined, comma separated array with equal sign as key to index
	 *     separator.
	 * @param string $appearance
	 * @param string $image
	 *
	 * @return string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function _checkbox( string $name, $value, string $label = C::ES, string $id = C::ES, bool $checked = false, $params = C::ES, string $appearance = 'block', string $image = C::ES ): string
	{
		if ( !is_bool( $checked ) ) {
			$checked = $checked == $value;
		}

		$label = strlen( $label ) ? self::cleanOptions( self::translate( $label ) ) : C::ES;
		$lcontent = ( $image ) ? "<img src=\"$image\" alt=\"$label\"/>" : ( strlen( $label ) ? $label : C::ES );
		$lend = strlen( $lcontent ) ? "</label>" : C::ES;

		$checked = $checked ? "checked=\"checked\" " : C::ES;
		$ids = $id ? "id=\"$id\" " : $id;
		$name = self::cleanOptions( $name );
		$value = self::cleanOptions( $value );

		$fw = defined( 'SOBIPRO_ADM' ) ? C::BOOTSTRAP5 : Sobi::Cfg( 'template.framework-style', C::BOOTSTRAP5 );

		/* SobiPro front-end and back-end */
		$divstyle = C::ES;
		if ( isset( $params[ 'style' ] ) ) {
			$divstyle = $params[ 'style' ] ? "style=\"{$params[ 'style' ]}\" " : C::ES;
			unset( $params[ 'style' ] );
		}

		switch ( $fw ) {
			case C::BOOTSTRAP5:
				$divclass = ( $appearance == 'inline' ) ? 'form-check form-check-inline' : 'form-check';
				if ( isset( $params[ 'switch' ] ) ) {
					if ( $params[ 'switch' ] ) {
						$divclass .= ' form-switch';
					}
				}
				$div = "<div class=\"$divclass\" $divstyle>";
				$params[ 'class' ] = ( isset( $params[ 'class' ] ) ) ? $params[ 'class' ] . ' form-check-input' : 'form-check-input';
				$lstart = strlen( $lcontent ) ? "<label for=\"$id\" class=\"form-check-label\">" : C::ES;
				break;
			case C::BOOTSTRAP4:
				$divclass = 'custom-control custom-checkbox';
				if ( isset( $params[ 'switch' ] ) ) {
					if ( $params[ 'switch' ] ) {
						$divclass = 'custom-control custom-switch';
					}
				}
				$divclass .= ( $appearance == 'inline' ) ? ' custom-control-inline' : C::ES;
				$div = "<div class=\"$divclass\" $divstyle>";
				$params[ 'class' ] = isset( $params[ 'class' ] ) ? $params[ 'class' ] . ' custom-control-input' : 'custom-control-input';
				$lstart = strlen( $lcontent ) ? "<label for=\"$id\" class=\"custom-control-label\">" : C::ES;
				break;
			case C::BOOTSTRAP3:
				$divclass = 'checkbox';
				$div = "<div class=\"$divclass $appearance\" $divstyle>";
				$class = $appearance == 'inline' ? 'checkbox-inline' : C::ES;
				$lstart = strlen( $lcontent ) ? "<label for=\"$id\" class=\"$class\">" : C::ES;
				break;
			default:    /* Bootstrap 2 */
				$divclass = 'checkbox';
				$div = "<div class=\"$divclass $appearance\" $divstyle>";
				$class = $appearance == 'inline' ? 'inline' : C::ES;
				$lstart = strlen( $lcontent ) ? "<label for=\"$id\" class=\"checkbox $class\">" : C::ES;
		}

		unset( $params[ 'switch' ] );
		$params = self::paramsToString( $params );
		$box = "<input type=\"checkbox\" name=\"$name\" $ids value=\"$value\" $checked $params/>";

		if ( $fw >= C::BOOTSTRAP4 ) {
			$html = $div . $box . $lstart . $lcontent . $lend . '</div>';
		}
		else {
			$html = $div . $lstart . $box . $lcontent . $lend . '</div>';
		}
		Sobi::Trigger( 'Field', ucfirst( __FUNCTION__ ), [ &$html ] );

		return $html;
	}

	/**
	 * Creates a radio button group.
	 * For SobiPro front-end and back-end.
	 *
	 * @param string $name - name of the html field
	 * @param array $values - two-dimensional array with values and their labels. array( 'enabled' => 1, 'disabled' => 0 )
	 * @param string $id - id prefix of the field
	 * @param string $checked - value of the selected field
	 * @param string|array $params - two-dimensional array with additional html parameters.
	 * Can be also string defined, comma separated array with equal sign as key to index separator.
	 *
	 * @return array|string
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	public static function _radioGroup( string $name, array $values, string $id, $checked = false, $params = C::ES )
	{
		self::checkArray( $values );

		$list = [];
		$arialabel = isset( $params[ 'aria-label' ] ) ? "aria-label=\"{$params['aria-label']}\"" : C::ES;
		$html = "<div class=\"btn-group\" role=\"group\" $arialabel>";
		if ( $params && is_array( $params ) && array_key_exists( 'aria-label', $params ) ) {
			unset( $params[ 'aria-label' ] );
		}
		if ( count( $values ) ) {
			foreach ( $values as $value => $label ) {
				if ( is_numeric( $value ) ) {
					$id = $id . '_' . ( $value == 1 ? 'yes' : ( $value == 0 ? 'no' : $value ) );
				}
				else {
					$id = $id . '_' . $value;
				}
				$list[] = self::radio( $name, $value, $label, $id, $checked, $params, 'group', C::ES );
			}
		}
		$html .= is_array( $list ) && count( $list ) ? implode( "", $list ) : C::ES;
		$html .= '</div>';

		Sobi::Trigger( 'Field', ucfirst( __FUNCTION__ ), [ &$html ] );

		return $html;
	}

	/**
	 * Creates list of radio boxes.
	 * For SobiPro front-end and back-end.
	 *
	 * @param string $name - name of the html field
	 * @param array $values - two-dimensional array with values and their labels. array( 'enabled' => 1, 'disabled' => 0 )
	 * @param string $id - id prefix of the field
	 * @param string|bool $checked - value of the selected field
	 * @param string|array $params - two-dimensional array with additional html parameters. Can be also string defined, comma separated array with equal sign as key
	 *     to index separator.
	 * @param string $appearance - 'block' = separate line for each; 'inline' = all in line; 'group' = as button group
	 * @param bool $asArray - returns array instead of a string
	 *
	 * @return array|string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function _radioList( string $name, array $values, string $id, $checked = false, $params = C::ES, string $appearance = 'block', bool $asArray = false )
	{
		self::checkArray( $values );

		$list = [];
		if ( $params && is_array( $params ) && array_key_exists( 'aria-label', $params ) ) {
			unset( $params[ 'aria-label' ] );
		}
		if ( count( $values ) ) {
			foreach ( $values as $value => $label ) {
				if ( is_numeric( $value ) ) {
					$idoption = $id . '_' . ( $value == 1 ? 'yes' : ( $value == 0 ? 'no' : $value ) );
				}
				else {
					$idoption = $id . '_' . $value;
				}
				$list[] = self::radio( $name, $value, $label, $idoption, $checked, $params, $appearance, C::ES );
			}
		}

		Sobi::Trigger( 'Field', ucfirst( __FUNCTION__ ), [ &$list ] );

		return $asArray ? $list : ( count( $list ) ? C::ES . implode( "", $list ) . C::ES : C::ES );
	}

	/**
	 * Creates single radio button.
	 * For SobiPro front-end and back-end.
	 *
	 * @param string $name - name of the html field
	 * @param string $value - values of the html field
	 * @param string $label - label to display beside the field.
	 * @param string $id - id of the field
	 * @param string|bool $checked - is selected or not / or string $checked the checked value
	 * @param string|array $params - two-dimensional array with additional html parameters. Can be also string defined, comma separated array with equal sign as key
	 *     to index separator.
	 * @param string $appearance
	 * @param string $image - url of an image
	 *
	 * @return string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function _radio( string $name, $value, string $label = C::ES, string $id = C::ES, $checked = false, $params = C::ES, string $appearance = 'block', string $image = C::ES )
	{
		if ( !is_bool( $checked ) ) {
			$checked = $checked == $value;
		}
		$label = strlen( $label ) ? self::cleanOptions( self::translate( $label ) ) : C::ES;
		$lcontent = ( $image ) ? "<img src=\"$image\" alt=\"$label\"/>" : ( strlen( $label ) ? $label : C::ES );
		$lend = strlen( $lcontent ) ? "</label>" : C::ES;

		$checked = $checked ? "checked=\"checked\" " : C::ES;
		$ids = $id ? "id=\"$id\"" : $id;
		$name = self::cleanOptions( $name );
		$value = self::cleanOptions( $value );

		$fw = defined( 'SOBIPRO_ADM' ) ? C::BOOTSTRAP5 : Sobi::Cfg( 'template.framework-style', C::BOOTSTRAP5 );

		$divstyle = C::ES;
		if ( $params && is_array( $params ) && array_key_exists( 'style', $params ) ) {
			$divstyle = isset( $params[ 'style' ] ) ? "style=\"{$params[ 'style' ]}\" " : C::ES;
			unset( $params[ 'style' ] );
		}

		$divs = '<div>';
		$dive = '</div>';
		/* SobiPro front-end and back-end */
		switch ( $fw ) {
			case C::BOOTSTRAP5:
				if ( $appearance == 'group' ) {
					$params[ 'class' ] = ( isset( $params[ 'class' ] ) ) ? $params[ 'class' ] .= ' btn-check' : 'btn-check';
				}
				else {
					$divs = $appearance == 'inline' ? 'form-check form-check-inline' : 'form-check';
					$divs = "<div class=\"$divs\" $divstyle>";
					$params[ 'class' ] = ( isset( $params[ 'class' ] ) ) ? $params[ 'class' ] .= ' form-check-input' : 'form-check-input';
				}
				$lstart = strlen( $lcontent ) ? "<label for=\"$id\" class=\"form-check-label\">" : C::ES;
				break;
			case C::BOOTSTRAP4:
				if ( $appearance == 'group' ) {
				}
				else {
					$divs = $appearance == 'inline' ? 'custom-control custom-radio custom-control-inline' : 'custom-control custom-radio';
					$divs = "<div class=\"$divs\" $divstyle>";
					$params[ 'class' ] = ( isset( $params[ 'class' ] ) ) ? $params[ 'class' ] .= ' custom-control-input' : 'custom-control-input';
				}
				$lstart = strlen( $lcontent ) ? "<label for=\"$id\" class=\"custom-control-label\">" : C::ES;
				break;
			case C::BOOTSTRAP3:
				$class = C::ES;
				if ( $appearance == 'group' ) {
				}
				else {
					$divs = "<div class=\"radio $appearance\" $divstyle>";
					$class = ( $appearance == 'inline' ) ? 'radio-inline' : C::ES;
				}
				$lstart = strlen( $lcontent ) ? "<label for=\"$id\" class=\"$class\">" : C::ES;
				break;
			default:    /* Bootstrap 2 */
				$class = C::ES;
				if ( $appearance == 'group' ) {
				}
				else {
					$divs = "<div class=\"radio $appearance\" $divstyle>";
					$class = $appearance == 'inline' ? 'inline' : C::ES;
				}
				$lstart = strlen( $lcontent ) ? "<label for=\"$id\" class=\"radio $class\">" : C::ES;
		}

		$params = self::paramsToString( $params );
		$box = "<input type=\"radio\" name=\"$name\" $ids autocomplete=\"off\" value=\"$value\" $checked $params/>";

		if ( $fw >= C::BOOTSTRAP4 ) {
			$html = $divs . $box . $lstart . $lcontent . $lend . $dive;
		}
		else {
			$html = $divs . $lstart . $box . $lcontent . $lend . $dive;
		}

		Sobi::Trigger( 'Field', ucfirst( __FUNCTION__ ), [ &$html ] );

		return $html;
	}

	/**
	 * Creates a select list.
	 * For SobiPro front-end and back-end.
	 *
	 * Example: list with multiple select options
	 * SPHtml_Input::select( 'fieldname',
	 *      [ 'translate:[ perms.can_delete ]' => 'can_delete',
	 *        'translate:[ perms.can_edit ]' => 'can_edit',
	 *        'translate:[ perms.can_see ]' => 'can_see ],
	 *      [ 'can_see', 'can_delete' ), true, [ 'class' => 'inputbox', 'size' => 5 ] ]
	 * );
	 *
	 * Example: list with multiple select options and optgroups
	 * SPHtml_Input::select( 'fieldname',
	 *             [ 'categories' => [ 'translate:[ perms.can_delete_categories ]' => 'can_delete_categories',
	 *                                 'translate:[ perms.can_edit_categories ]' => 'can_edit_categories',
	 *                                 'translate:[ perms.can_see_categories ]' => 'can_see_categories' ],
	 *               'entries' => [ 'translate:[ perms.can_delete_entries ]' => 'can_delete_entries',
	 *                              'translate:[ perms.can_edit_entries ]' => 'can_edit_entries',
	 *                              'translate:[ perms.can_see_entries ]' => 'can_see_entries' ],
	 *             ]
	 *             [ 'can_see_categories', 'can_delete_entries', 'can_edit_entries' ], true, [ 'class' => 'inputbox', 'size' => 5 ]
	 * );
	 *
	 * @param string $name - name of the html field
	 * @param array $values - two-dimensional array with values and their labels. array( 'enabled' => 1, 'disabled' => 0 )
	 * @param string|array $selected - one-dimensional array with selected values
	 * @param bool $multi - multiple select is allowed or not
	 * @param string|array $params - two-dimensional array with additional html parameters. Can be also string defined, comma separated array with equal sign as key to index.
	 *
	 * @return string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function _select( string $name, array $values = [], $selected = C::ES, bool $multi = false, $params = C::ES ): string
	{
		$fw = defined( 'SOBIPRO_ADM' ) ? C::BOOTSTRAP5 : Sobi::Cfg( 'template.framework-style', C::BOOTSTRAP5 );

		/* SobiPro front-end and back-end */
		$fwClass = C::ES;  /* Bootstrap 2 */
		switch ( $fw ) {
			/* Bootstrap 3 */
			case C::BOOTSTRAP3:
				$fwClass = ' form-control';
				break;
			/* Bootstrap 4 */
			case C::BOOTSTRAP4:
				$fwClass = ' custom-select';
				break;
			case C::BOOTSTRAP5:
				$fwClass = ' form-select';
				break;
		}

		self::checkArray( $params );
		$disabled = self::getTrimmed( $params );

		$numeric = true;
		if ( is_array( $params ) ) {
			if ( isset( $params[ 'size' ] ) && $params[ 'size' ] == 1 ) {
				unset( $params[ 'size' ] );
			}
			if ( isset( $params[ 'numeric' ] ) ) {
				$numeric = (bool) $params[ 'numeric' ];
				unset ( $params[ 'numeric' ] );
			}
		}
		if ( !isset( $params[ 'id' ] ) ) {
			$params[ 'id' ] = StringUtils::FieldNid( $name );
		}
		$params[ 'class' ] = isset( $params[ 'class' ] ) ? $params[ 'class' ] .= $fwClass : $fwClass;

		$data = self::createDataTag( $params );
		$params = self::paramsToString( $params );

		if ( $multi ) {
			$multi = 'multiple = "multiple" ';
			$name .= '[]';
		}

		$cells = C::ES;
		if ( is_array( $values ) && count( $values ) ) {
			if ( $numeric ) {
				Type::TypecastArray( $values );
			}
			self::checkArray( $values );

			if ( strstr( $name, '_array' ) ) {
				self::checkArray( $selected );
			}
			if ( $selected !== C::ES && !is_array( $selected ) ) {
				$selected = [ $selected ];
			}
			else {
				if ( !is_array( $selected ) ) {
					$selected = [];
				}
			}

			$cells = implode( "", self::createOptions( $values, $selected, $numeric ) );
		}
		$html = "<select name=\"$name\" $multi $params $data $disabled>$cells</select>";

		Sobi::Trigger( 'Field', ucfirst( __FUNCTION__ ), [ &$html ] );

		return $html;
	}

	/**
	 * Creates the options for a select list.
	 * For SobiPro front-end and back-end.
	 *
	 * @param array $values
	 * @param array $selected
	 * @param bool $numeric
	 *
	 * @return array
	 */
	protected static function createOptions( array $values, array $selected = [], bool $numeric = true ): array
	{
		if ( is_array( $selected ) && count( $selected ) && $numeric ) {
			Type::TypecastArray( $selected );
		}

		$cells = [];
		if ( count( $values ) ) {
			foreach ( $values as $v => $l ) {
				$v = $numeric ? $v : (string) $v;
				/* if one of both values was an array - it is a group */
				if ( ( is_array( $l ) || is_array( $v ) ) && !isset( $l[ 'label' ] ) ) {
					$grouplabel = Sobi::Txt( strtoupper( (string) $v ) );

					$cells[] = "<optgroup label=\"$grouplabel\">";
					if ( count( $l ) ) {
						foreach ( $l as $ov => $ol ) {
							/** when there is a group */
							if ( is_array( $ol ) && !( isset( $ol[ 'label' ] ) ) ) {
								self::optionGroup( $cells, $selected, $ol, $ov );
							}
							else {
								/** when we have special params */
								if ( is_array( $ol ) && ( isset( $ol[ 'label' ] ) ) ) {
									$sel = in_array( $ol[ 'value' ], $selected, true ) ? 'selected="selected" ' : C::ES;
									$ol = self::cleanOptions( $ol[ 'label' ] );
									$ov = self::cleanOptions( $ol[ 'value' ] );
									$p = C::ES;
									$oParams = [];
									if ( isset( $ol[ 'params' ] ) && is_array( $ol[ 'params' ] ) && count( $ol[ 'params' ] ) ) {
										foreach ( $ol[ 'params' ] as $param => $value ) {
											$oParams[] = "$param=\"$value\"";
										}
									}
									if ( count( $oParams ) ) {
										$p = implode( ' ', $oParams );
										$p = " $p ";
									}
//									$cells[] = "<option $p $sel value=\"$ov\"$t>$ol</option>";
									$cells[] = "<option $p $sel value=\"$ov\">$ol</option>";
								}
								else {
									$sel = in_array( $ov, $selected, true ) ? 'selected="selected" ' : C::ES;
									$ol = self::cleanOptions( $ol );
									$ov = self::cleanOptions( $ov );
//									$cells[] = "<option $sel value=\"$ov\"$t>$ol</option>";
									$cells[] = "<option $sel value=\"$ov\">$ol</option>";
								}
							}
						}
					}
					$cells[] = "</optgroup>";
				}
				else {
					/** when we have special params */
					if ( is_array( $l ) && ( isset( $l[ 'label' ] ) ) ) {
						$sel = in_array( $l[ 'value' ], $selected, true ) ? 'selected="selected" ' : C::ES;
						$ol = self::cleanOptions( $l[ 'label' ] );
						$ov = self::cleanOptions( $l[ 'value' ] );
						$p = C::ES;
						$oParams = [];
						if ( isset( $l[ 'params' ] ) && count( $l[ 'params' ] ) ) {
							foreach ( $l[ 'params' ] as $param => $value ) {
								$oParams[] = "$param=\"$value\"";
							}
						}
						if ( count( $oParams ) ) {
							$p = implode( ' ', $oParams );
							$p = " $p ";
						}
//						$cells[] = "<option $p $sel value=\"$ov\"$t>$ol</option>";
						$cells[] = "<option $p $sel value=\"$ov\">$ol</option>";
					}
					else {
						$sel = in_array( $v, $selected, true ) ? 'selected="selected" ' : C::ES;
						$v = self::cleanOptions( $v );
						$l = self::cleanOptions( self::translate( $l ) );
//						$cells[] = "<option $sel value=\"$v\"$t>$l</option>";
						$cells[] = "<option $sel value=\"$v\">$l</option>";
					}
				}
			}
		}

		return $cells;
	}

	/**
	 * @param string $opt
	 *
	 * @return string|string[]|null
	 */
	public static function cleanOptions( string $opt )
	{
		return preg_replace( '/(&)([^a-zA-Z0-9#]+)/', '&amp;\2', self::translate( $opt ) );
	}

	/**
	 * For SobiPro front-end and back-end.
	 *
	 * @param $cells
	 * @param $selected
	 * @param array $grp
	 * @param string $title
	 */
	protected static function optionGroup( &$cells, $selected, array $grp, string $title )
	{
		$cells[] = "<optgroup label=\"$title\">";
		foreach ( $grp as $value => $label ) {
			$value = StringUtils::Entities( $value, true );
			if ( is_array( $label ) ) {
				self::optionGroup( $cells, $selected, $label, $value );
			}
			else {
				$sel = in_array( $value, $selected, true ) ? 'selected="selected" ' : C::ES;
				$label = StringUtils::Entities( self::translate( $label ), true );
				$cells[] = "<option $sel value=\"$value\">$label</option>";
			}
		}
		$cells[] = "</optgroup>";
	}

	/**
	 * Special function _to create enabled/disabled states radio list.
	 * For SobiPro back-end only.
	 *
	 * @param string $name - name of the html field
	 * @param array $value - selected value
	 * @param string $id - id prefix of the field
	 * @param string $label - label prefix to display beside the fields
	 * @param string|array $params - two-dimensional array with additional html parameters.
	 * Can be also string defined, comma separated array with equal sign as key to index separator.
	 * @param bool $inline
	 *
	 * @return string
	 * @deprecated
	 */
	public static function _states( string $name, $value, $id, $label, $params = C::ES, bool $inline = false )
	{
		return self::radioList( $name, [ '0' => "translate:[{$label}_no]",
		                                 '1' => "translate:[{$label}_yes]" ],
		                        $id, ( int ) $value, $params, $inline, false );
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @param string $id
	 * @param string $prefix
	 * @param string|array $params
	 *
	 * @return string
	 */
	public static function _tristate( string $name, string $value, string $id, string $prefix, $params = C::ES ): string
	{
		$numvalue = (int) $value;
		$disabled = self::getTrimmed( $params );
		$prefix = strtoupper( $prefix );

		self::checkArray( $params );
		self::cleanParams( $params, [ 'id' ] );
		$params = self::paramsToString( $params );

		$html = '<div class="btn-group tristate-button" role="group" data-bs-toggle="buttons-radio" id="' . $id . ' data-spctrl-active="' . $value . '">';
		$html .= '<button type="button" name="' . $name . '" class="btn min-w-4 ' . ( $numvalue == 2 ? 'btn-global' : 'btn-unchecked' ) . '" value="2" data-spctrl-state="' . ( $numvalue == 2 ? 'active' : C::ES ) . '"' . $disabled . '>' . Sobi::Txt( $prefix . '_GLOBAL' ) . '</button>';

		return self::getButtons( $name, $numvalue, $disabled, $prefix, $html );
	}

	/**
	 * Special function to create enabled/disabled states radio list (Yes/No button).
	 * For SobiPro back-end only.
	 *
	 * @param string $name - name of the html field
	 * @param string $value - selected value
	 * @param string $id - id prefix of the field
	 * @param string $prefix
	 * @param string|array $params
	 *
	 * @return string
	 * @internal param array $params - two-dimensional array with additional html parameters.
	 * Can be also string defined, comma separated array with equal sign as key to index separator.
	 * @internal param string $label - label prefix to display beside the fields
	 */
	public static function _toggle( string $name, string $value, $id, string $prefix, $params = C::ES ): string
	{
		$numvalue = (int) $value;
		$disabled = self::getTrimmed( $params );
		$prefix = strtoupper( $prefix );

		self::checkArray( $params );
		self::cleanParams( $params, [ 'id' ] );
		$params = self::paramsToString( $params );

		$html = '<div class="btn-group toggle-button"  role="group" data-bs-toggle="buttons-radio" id="' . $id . '"' . $params . ' data-spctrl-active="' . $value . '">';

		return self::getButtons( $name, $numvalue, $disabled, $prefix, $html );
	}

	/**
	 * Creates field with date selector.
	 *
	 * @param string $name - name of the html field
	 * @param array $value - selected value
	 * @param string $id - id prefix of the field
	 * @param string|array $params - two-dimensional array with additional html parameters. Can be also string defined, comma separated array with equal sign as key
	 *     to index separator.
	 *
	 * @deprecated since 2.0
	 */
	public static function _calendar( string $name, $value, string $id = C::ES, $params = C::ES )
	{
	}

	/**
	 * For SobiPro front-end and back-end.
	 *
	 * @param string $name - field name
	 * @param string $value - field value
	 * @param string $dateFormat - date format in PHP
	 * @param string|array $params - additional parameters
	 * @param string $icon
	 * @param bool $gmt
	 * @param string $timeOffset
	 *
	 * @return string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function _datepicker( string $name, $value, string $dateFormat = SPC::DEFAULT_DB_DATE, $params = C::ES, string $icon = 'calendar', $gmt = true, string $timeOffset = C::ES ): string
	{
		$fw = defined( 'SOBIPRO_ADM' ) ? C::BOOTSTRAP5 : Sobi::Cfg( 'template.framework-style', C::BOOTSTRAP5 );

		//self::createLangFile();
		Sobi::createDatepickerLangFile();
		SPFactory::header()->addJsFile( [ 'locale.' . Sobi::Lang( false ) . '_date_picker', 'bootstrap.utilities.datepicker' ] );

		$timeOffset = strlen( $timeOffset ) ? $timeOffset : Sobi::Cfg( 'time_offset' );

		self::checkArray( $params );

		if ( !isset( $params[ 'id' ] ) ) {
			$params[ 'id' ] = StringUtils::Nid( $name );
		}

		if ( !isset( $params[ 'data' ] ) ) {
			$params[ 'data' ] = [];
		}

		if ( substr( $dateFormat, 0, 4 ) === "cfg:" ) {
			$dateFormat = Sobi::Cfg( 'date.' . substr( $dateFormat, 4 ), SPC::DEFAULT_DATE );
		}
		if ( strstr( $dateFormat, 'A' ) || strstr( $dateFormat, 'a' ) ) {
			$dateFormat = str_replace( [ 'h', 'H' ], [ 'g', 'G' ], $dateFormat );
			$params[ 'data' ][ 'am-pm' ] = 'true';
		}
		else {
			$params[ 'data' ][ 'am-pm' ] = 'false';
		}
		$jsDateFormat = $dateFormat;
		$jsReplacements = [
			'y' => 'yy',
			'Y' => 'yyyy',
			'F' => 'MM',
			'n' => 'm',
			'm' => 'MM',
			'd' => 'dd',
			'j' => 'd',
			'H' => 'hh',
			'g' => 'HH',
			'G' => 'HH',
			'i' => 'mm',
			's' => 'ss',
			'A' => 'PP',
			'a' => 'PP',
		];
		foreach ( $jsReplacements as $php => $js ) {
			$jsDateFormat = str_replace( $php, $js, $jsDateFormat );
		}

		$params[ 'data' ][ 'format' ] = $jsDateFormat;
		$params[ 'data' ][ 'time-offset' ] = $offset = SPFactory::config()->getTimeOffset();
		$params[ 'data' ][ 'time-zone' ] = $timeOffset;
		if ( !$gmt ) {    /* gmt = addoffset */
			$params[ 'data' ][ 'time-offset' ] = $offset = 0;
			$params[ 'data' ][ 'time-zone' ] = 0;
		}
		$params[ 'data' ][ 'bs' ] = $fw;
		$params[ 'data' ][ 'append-to' ] = 'body';  /* should work also without SobiPro scope */
		$data = self::createDataTag( $params );

		/* evaluate the value */
		if ( $value && !is_numeric( $value ) ) {
			$value = $gmt ? strtotime( $value . 'UTC' ) : strtotime( $value );
		}
		$valueDisplay = $value ? SPFactory::config()->date( $value + $offset, C::ES, $dateFormat, $gmt ) : C::ES;

		if ( is_string( $value ) && strstr( $value, '.' ) ) {
			$value = explode( '.', $value );
			$value = $value[ 0 ];
		}
		$value = $value ? ( $value * 1000 ) : C::ES; /* no offset, we are using UTC times only */

		/* set the icons on the input field */
		$symbolIconTime = Sobi::Icon( 'time', C::ES, false );
		if ( defined( 'SOBIPRO_ADM' ) ) {
			/* on back-end the icon will be toggled depending on date or time picker */
			$symbolIcon = Sobi::Icon( $icon, C::ES, false );
			$btnIcon = '<span data-date-icon="' . $symbolIcon . '" data-time-icon="' . $symbolIconTime . '" class="spctrl-trigger-icon ' . $symbolIcon . '" aria-hidden="true"></span>';
		}
		else {
			if ( strpos( $icon, 'icon-' ) || strpos( $icon, 'fa-' ) ) {
				$symbolIcon = $icon;
			}
			else {
				$symbolIcon = Sobi::Icon( $icon, C::ES, false );
			}
			$el = Sobi::getFont( true );
			$btnIcon = '<' . $el . ' class="' . $symbolIcon . '" aria-hidden="true"></' . $el . '>';
		}
		$suffix = C::ES;
		if ( $params && is_array( $params ) && array_key_exists( 'suffix', $params ) && $params[ 'suffix' ] ) {
			$suffix = $params[ 'suffix' ];
			unset ( $params[ 'suffix' ] );
		}

		$class = 'form-control';
		$btnWrap = $btnClass = $fwGroup = C::ES;
		switch ( $fw ) {
			case C::BOOTSTRAP5:
				$fwGroup = 'input-group';
				$btnClass = 'input-group-text spctrl-trigger';
				if ( $suffix ) {
					$suffix = "<span class=\"input-group-text\">$suffix</span>";
				}
				break;
			case C::BOOTSTRAP4:
				$fwGroup = 'input-group';
				$btnWrap = 'input-group-append';
				$btnClass = 'input-group-text spctrl-trigger';
				if ( $suffix ) {
					$suffix = "<span class=\"input-group-text\">$suffix</span>";
				}
				break;
			case C::BOOTSTRAP3:
				$fwGroup = 'input-group';
				$btnClass = 'input-group-addon spctrl-trigger';
				if ( $suffix ) {
					$suffix = "<span>$suffix</span>";
				}
				break;
			case C::BOOTSTRAP2:
				$fwGroup = 'input-append';
				$btnClass = 'add-on spctrl-trigger';

				if ( $suffix ) {
					$suffix = "<span>$suffix</span>";
				}
				$class = C::ES;

				break;
		}
		if ( !isset( $params[ 'class' ] ) ) {
			$params[ 'class' ] = $class ? : C::ES;
		}
		else {
			$params[ 'class' ] .= $class ? ' ' . $class : C::ES;
		}
		$params = self::paramsToString( $params );

		/* create the input field */
		$input = '<input type="text" disabled="disabled" value="' . $valueDisplay . '" ' . $params . ' name="' . $name . 'Holder" ' . $data . '/>';
		$input .= '<input type="hidden" value="' . $value . '" name="' . $name . '"/>';

		/* the button to open the picker */
		$btn = $btnWrap ? '<div class="' . $btnWrap . '">' : C::ES;
		$btn .= '<span class="' . $btnClass . '" type="button">' . $btnIcon . '</span>';
		$btn .= $btnWrap ? '</div>' : C::ES;

		$html = '<div class="' . $fwGroup . ' date spctrl-datepicker">';
		$html .= $input . $btn . $suffix;
		$html .= '</div>';

		Sobi::Trigger( 'Field', ucfirst( __FUNCTION__ ), [ &$html ] );

		return "$html";
	}

	/**
	 * @param $data
	 *
	 * @return string
	 * @throws \Sobi\Error\Exception
	 */
	public static function createDataTag( &$data ): string
	{
		if ( is_array( $data ) && isset( $data[ 'data' ] ) && count( $data[ 'data' ] ) ) {
			$tag = ' ';
			foreach ( $data[ 'data' ] as $name => $value ) {
				$name = StringUtils::Nid( preg_replace( '/(?<!^)([A-Z])/', '-\\1', $name ) );
				$tag .= "data-$name=\"$value\" ";
			}
			unset( $data[ 'data' ] );

			return $tag;
		}

		return C::ES;
	}

	/**
	 * @param $data
	 *
	 * @return string
	 */
	public static function findDataTag( &$data ): string
	{
		if ( is_array( $data ) && count( $data ) ) {
			$tag = ' ';
			foreach ( $data as $name => $value ) {
				if ( strstr( $name, 'data-' ) ) {
					$tag .= "$name=\"$value\" ";
					unset( $data[ $name ] );
				}
			}

			return $tag;
		}

		return C::ES;
	}

	/**
	 * For SobiPro back-end.
	 *
	 * @param string $name - field name
	 * @param string $value - field value
	 * @param string $class
	 * @param string $dateFormat - date format in PHP
	 * @param string|array $params - additional parameters
	 * @param string $addOffset
	 *
	 * @return string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 * @internal param string $icon - field icon
	 */
	public static function _dategetter( string $name, string $value, string $class = C::ES, string $dateFormat = SPC::DEFAULT_DB_DATE, $params = C::ES, $addOffset = 'true' )
	{
		//self::createLangFile();
		Sobi::createDatepickerLangFile();

		self::checkArray( $params );
		if ( !isset( $params[ 'id' ] ) ) {
			$params[ 'id' ] = StringUtils::Nid( $name );
		}
		if ( $class ) {
			$params[ 'class' ] = $class;
		}
		$params = self::paramsToString( $params );

		$date = strtotime( $value );
		$offset = 0;
		if ( $addOffset ) {
			$date = strtotime( $value . 'UTC' );
			$offset = SPFactory::config()->getTimeOffset();
		}

		/* SobiPro back-end only */
		if ( defined( 'SOBIPRO_ADM' ) ) {
			if ( $date && substr( $dateFormat, 0, 4 ) === "cfg:" ) {
				$dateFormat = Sobi::Cfg( 'date.' . substr( $dateFormat, 4 ), SPC::DEFAULT_DATE );
			}
			$valueDisplay = $date ? SPFactory::config()->date( $date + $offset, C::ES, $dateFormat ) : C::ES;
			$html = '<div class="dk-output">';
			$html .= '<div ' . $params . '>' . $valueDisplay . '</div>';
			$html .= '</div>';
		}

		/* Front-end only */
		else {
			$valueDisplay = $date ? SPFactory::config()->date( $date + $offset, C::ES, $dateFormat ) : C::ES;
			$html = '<div class="dk-output">';
			$html .= '<span ' . $params . '>' . $valueDisplay . '</span>';
			$html .= '</div>';
		}
		Sobi::Trigger( 'Field', ucfirst( __FUNCTION__ ), [ &$html ] );

		return $html;
	}

	/**
	 * Creates the text file for the date/time picker.
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function createLangFile()
	{
		static $created = false;
		if ( !$created ) {
			$lang = [
				'months'      => Sobi::Txt( 'JS_CALENDAR_MONTHS' ),
				'monthsShort' => Sobi::Txt( 'JS_CALENDAR_MONTHS_SHORT' ),
				'days'        => Sobi::Txt( 'JS_CALENDAR_DAYS' ),
				'daysShort'   => Sobi::Txt( 'JS_CALENDAR_DAYS_SHORT' ),
				'daysMin'     => Sobi::Txt( 'JS_CALENDAR_DAYS_MINI' ),
				'today'       => Sobi::Txt( 'JS_CALENDAR_TODAY' ),
				'buttons'     => Sobi::Txt( 'JS_DATEPICKER_BUTTONS' ),
				'picks'       => Sobi::Txt( 'JS_DATEPICKER_PICKS' ),
				'switches'    => Sobi::Txt( 'JS_DATEPICKER_SWITCHES' ),
				'prevs'       => Sobi::Txt( 'JS_DATEPICKER_PREVIOUS' ),
				'nexts'       => Sobi::Txt( 'JS_DATEPICKER_NEXT' ),
				'countup'     => Sobi::Txt( 'JS_DATEPICKER_COUNTUP' ),
				'countdown'   => Sobi::Txt( 'JS_DATEPICKER_COUNTDOWN' ),
				'states'      => Sobi::Txt( 'JS_DATEPICKER_STATES' ),
			];
			$check = md5( serialize( $lang ) );
			if ( !( SPLoader::JsFile( 'locale.' . Sobi::Lang( false ) . '_date_picker', false, true, false ) ) || !( stripos( FileSystem::Read( SPLoader::JsFile( 'locale.' . Sobi::Lang( false ) . '_date_picker', false, false, false ) ), $check ) ) ) {
				foreach ( $lang as $k => $v ) {
					$lang[ $k ] = explode( ',', $v );
				}
				$lang = json_encode( $lang );
				$content = "\nvar spDatePickerLang=$lang";
				$content .= "\n//$check";
				FileSystem::Write( SPLoader::JsFile( 'locale.' . Sobi::Lang( false ) . '_date_picker', false, false, false ), $content );
			}
		}
		$created = true;
	}

	/**
	 * For SobiPro back-end only.
	 *
	 * @param string $name
	 * @param $value
	 * @param null $groups
	 * @param string|array $params
	 * @param string $icon
	 * @param string $header
	 * @param string $format
	 * @param string $orderBy
	 *
	 * @return string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 * @throws \Exception
	 */
	public static function _userselector( string $name, $value, $groups = null, $params = C::ES, string $icon = 'user', string $header = 'USER_SELECT_HEADER', string $format = '%user', string $orderBy = 'id' ): string
	{
		$html = C::ES;

		/* SobiPro back-end only */
		if ( defined( 'SOBIPRO_ADM' ) ) {
			static $count = 0;
			static $session = null;
			if ( !$session ) {
				$session = SPFactory::user()->getUserState( 'userselector', C::ES, [] );
			}
			self::checkArray( $params );
			if ( !isset( $params[ 'id' ] ) ) {
				$params[ 'id' ] = StringUtils::Nid( $name );
			}

			SPFactory::header()->addJsFile( 'adm.userselector' );
			$user = SPUser::getBaseData( ( int ) $value );
			$settings = [
				'groups'   => $groups,
				'format'   => $format,
				'user'     => Sobi::My( 'id' ),
				'ordering' => $orderBy,
				'time'     => microtime( true ),
			];
			if ( count( $session ) ) {
				foreach ( $session as $id => $data ) {
					if ( microtime( true ) - $data[ 'time' ] > 3600 ) {
						unset( $session[ $id ] );
					}
				}
			}
			$ssid = md5( microtime() . Sobi::My( 'id' ) . ++$count );
			$session[ $ssid ] =& $settings;
			SPFactory::user()->setUserState( 'userselector', $session );
			$userData = C::ES;
			if ( $user ) {
				$replacements = [];
				preg_match_all( '/\%[a-z]*/', $format, $replacements );
				$placeholders = [];
				if ( isset( $replacements[ 0 ] ) && count( $replacements[ 0 ] ) ) {
					foreach ( $replacements[ 0 ] as $placeholder ) {
						$placeholders[] = str_replace( '%', C::ES, $placeholder );
					}
				}
				if ( count( $replacements ) ) {
					foreach ( $placeholders as $attribute ) {
						if ( isset( $user->$attribute ) ) {
							$format = str_replace( '%' . $attribute, (string) $user->$attribute, $format );
						}
					}
					$userData = str_replace( '"', C::ES, $format );
				}
			}
			$modal = '<div class="spctrl-response" data-bs-toggle="buttons-radio"></div><button class="btn btn-secondary btn-sm w-100 mt-3 hidden spctrl-more" type="button">' . Sobi::Txt( 'LOAD_MORE' ) . '</button>';

			$filter = '<input type="text" placeholder="' . Sobi::Txt( 'FILTER' ) . '" class="form-control spctrl-search" data-spctrl="disable-enter" name="q" id="userFilter" aria-label="' . Sobi::Txt( 'ACCESSIBILITY.FILTER' ) . '">';
			$id = $params[ 'id' ];
			$params = self::paramsToString( $params );

			$headline = '<label for="userFilter">' . Sobi::Txt( $header ) . '</label>' . $filter;

			$html = '<div class="spctrl-user-selector">';
			$html .= '<div class="input-group">';
			$html .= '<input type="text" value="' . $userData . '" ' . $params . ' name="' . $name . 'Holder" readonly="readonly" class="form-control spctrl-trigger user-name"/>';
			$html .= '<input type="hidden" value="' . $value . '" name="' . $name . '" rel="selected"/>';
			$html .= '<input type="hidden" value="' . $ssid . '" name="' . $name . 'Ssid"/>';
			$html .= '<input type="hidden" value="1" name="' . Factory::Application()->token() . '"/>';
			$html .= '<span class="input-group-text spctrl-trigger" type="button"><span class="fas fa-' . $icon . '"></span></span>';
			$html .= '</div>';

			$html .= self::modalWindow( $headline,
			                            $id . '-window',
			                            $modal,
			                            C::ES,
			                            'CLOSE',
			                            'SAVE',
			                            C::ES,
			                            false
			);

			$html .= '</div>';
		}

		Sobi::Trigger( 'Field', ucfirst( __FUNCTION__ ), [ &$html ] );

		return $html;
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @param string|array $params
	 * @param string $class
	 * @param string $format
	 *
	 * @return string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function _userGetter( string $name, string $value, $params = C::ES, string $class = C::ES, string $format = '%user' ): string
	{
		self::checkArray( $params );
		if ( !isset( $params[ 'id' ] ) ) {
			$params[ 'id' ] = StringUtils::Nid( $name );
		}
		if ( $class ) {
			$params[ 'class' ] = $class;
		}
		$user = SPUser::getBaseData( ( int ) $value );
		$userData = C::ES;
		if ( $user ) {
			$replacements = [];
			preg_match_all( '/\%[a-z]*/', $format, $replacements );
			$placeholders = [];
			if ( isset( $replacements[ 0 ] ) && count( $replacements[ 0 ] ) ) {
				foreach ( $replacements[ 0 ] as $placeholder ) {
					$placeholders[] = str_replace( '%', C::ES, $placeholder );
				}
			}
			if ( count( $replacements ) ) {
				foreach ( $placeholders as $attribute ) {
					if ( isset( $user->$attribute ) ) {
						$format = str_replace( '%' . $attribute, (string) $user->$attribute, $format );
					}
				}
				$userData = $format;
			}
		}
		$params = self::paramsToString( $params );
		$html = '<div class="dk-output">';
		$html .= '<div ' . $params . '>' . $userData . '</div>';
		$html .= '</div>';

		Sobi::Trigger( 'Field', ucfirst( __FUNCTION__ ), [ &$html ] );

		return $html;
	}

	/**
	 * Creates a modal window.
	 * For SobiPro front-end and back-end.
	 *
	 * @param string $header
	 * @param string $id
	 * @param string $content
	 * @param string $class
	 * @param string $closeText
	 * @param string $saveText
	 * @param string $style
	 * @param bool $dismiss
	 * @param string $role
	 * @param bool $dismissModalOnSave
	 *
	 * @return string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public static function _modalWindow( string $header, string $id = C::ES, $content = C::ES, $class = C::ES, string $closeText = 'CLOSE', string $saveText = 'SAVE', $style = C::ES, bool $dismiss = true, $role = C::ES, bool $dismissModalOnSave = true ): string
	{
		$dismissModalOnSave = !is_string( $dismissModalOnSave ) && $dismissModalOnSave == 'false' ? $dismissModalOnSave : false;
		$uid = strlen( $id ) ? $id : uniqid( 'modal-' );
		$tid = $uid . '-title';
		$ids = strlen( $uid ) ? '" id="' . $uid . '"' : C::ES;

		if ( $style ) {
			$style = " style=\"$style\"";
		}
		$fw = defined( 'SOBIPRO_ADM' ) ? C::BOOTSTRAP5 : Sobi::Cfg( 'template.framework-style', C::BOOTSTRAP5 );
		$closeColor = defined( 'SOBIPRO_ADM' ) ? 'btn-alpha' : 'btn-outline-alpha';
		$closeColor = Sobi::Cfg( 'template.supportold', false ) && !defined( 'SOBIPRO_ADM' ) ? 'btn-default' : $closeColor;
		$saveColor = 'btn-success';

		$divs = "<div class=\"modal-dialog $class\" $style>";
		$divs .= '<div class="modal-content">';
		$dive = '</div></div>';

		$modalclass = 'modal fade';
		$dismissbtn = $dismiss ? '<button type="button" class="close" data-spctrl="close-modal" data-dismiss="modal" aria-label="' . Sobi::Txt( "ACCESSIBILITY.CLOSE" ) . '"><span aria-hidden="true">×</span></button>' : C::ES;

		switch ( $fw ) {
			case C::BOOTSTRAP5:
				$attr = 'data-bs-dismiss="modal"';
				$dismissbtn = $dismiss ? '<button type="button" class="btn-close" data-spctrl="close-modal"  data-bs-dismiss="modal" aria-label="' . Sobi::Txt( "ACCESSIBILITY.CLOSE" ) . '"></button>' : C::ES;
				break;
			case C::BOOTSTRAP4:
			case C::BOOTSTRAP3:
				$attr = 'data-dismiss="modal"';
				break;
			default:    /* Bootstrap 2 */
				$attr = 'data-dismiss="modal"';
				$modalclass .= ' hide';
				$divs = $dive = C::ES;
		}

		/* save and close buttons */
		$save = $saveText ? '<a data-spctrl="save-modal"  href="#" id="' . $uid . '-save" class="btn ' . $saveColor . ' save ms-2" ' . ( $dismissModalOnSave ? $attr : C::ES ) . '>' . Sobi::Txt( $saveText ) . '</a>' : C::ES;
		$close = '<button type="button" data-spctrl="close-modal" class="btn ' . $closeColor . '" ' . $attr . '>' . Sobi::Txt( $closeText ) . '</button>';

		$role = strlen( $role ) ? ' data-spctrl-role="' . $role . '" ' : C::ES;
		$html = '<div class="SobiPro ' . $modalclass . $ids . $style . $role . ' tabindex="-1" aria-labelledby="' . $tid . '" aria-hidden="true" role="dialog">';
		$html .= $divs;

		/* modal header */
		$html .= '<div class="modal-header">';
		if ( $fw == C::BOOTSTRAP2 ) {
			$html .= $dismissbtn;
			$html .= '<h3 class="modal-title" id ="' . $tid . '">' . $header . '</h3>';
		}
		else {
			$html .= '<h3 class="modal-title" id ="' . $tid . '">' . $header . '</h3>';
			$html .= $dismissbtn;
		}
		$html .= '</div>';
		$html .= '<div class="modal-body">' . $content . '</div>';
		$html .= '<div class="modal-footer">' . $close . $save . '</div>';
		$html .= $dive . '</div>';

		Sobi::Trigger( 'Field', ucfirst( __FUNCTION__ ), [ &$html ] );

		return $html;
	}

	/**
	 * For SobiPro front-end and back-end.
	 *
	 * @param string $name
	 * @param string $value
	 * @param string $id
	 * @param string|array $params
	 *
	 * @return string
	 * @throws \Sobi\Error\Exception
	 */
	public static function _hidden( string $name, string $value = C::ES, string $id = C::ES, $params = C::ES ): string
	{
		$data = self::createDataTag( $params ) . ' ' . self::findDataTag( $params );
		$id = $id ? : StringUtils::FieldNid( $name );
		$html = "<input type=\"hidden\" name=\"$name\" id=\"$id\" value=\"$value\" $data/>";

		return $html;
	}

	/**
	 * @param string $name
	 * @param int $value
	 * @param string $disabled
	 * @param string $prefix
	 * @param string $html
	 *
	 * @return string
	 */
	protected static function getButtons( string $name, int $value, string $disabled, string $prefix, string $html ): string
	{
		$html .= '<button type="button" name="' . $name . '" class="btn min-w-4 ' . ( $value == 1 ? 'btn-yes' : 'btn-unchecked' ) . '" value="1" data-spctrl-state="' . ( $value == 1 ? 'active' : C::ES ) . '"' . $disabled . '>' . Sobi::Txt( $prefix . '_YES' ) . '</button>';
		$html .= '<button type="button" name="' . $name . '" class="btn min-w-4 ' . ( $value == 0 ? 'btn-no' : 'btn-unchecked' ) . '" value="0" data-spctrl-state="' . ( $value == 0 ? 'active' : C::ES ) . '"' . $disabled . '>' . Sobi::Txt( $prefix . '_NO' ) . '</button>';
		$html .= '</div>';

		return $html;
	}
}
