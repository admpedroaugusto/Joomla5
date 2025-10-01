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
 * as published by the Free Software Foundation, and under the additional terms according to section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 10-Jun-2010 by Radek Suski
 * @modified 16 July 2024 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );
SPLoader::loadController( 'config', true );

use Sobi\C;
use Sobi\FileSystem\Directory;
use Sobi\FileSystem\File;
use Sobi\FileSystem\FileSystem;
use Sobi\Input\Input;
use Sobi\Lib\Factory;
use Sobi\Utils\StringUtils;

/**
 * Class SPTemplateCtrl
 */
class SPTemplateCtrl extends SPConfigAdmCtrl
{
	/** @var string */
	protected $_type = 'template';
	/** @var string */
	protected $_defTask = 'edit';

	/**
	 * SPTemplateCtrl constructor.
	 */
	public function __construct()
	{
		if ( !( Sobi::Can( 'cms.admin' ) || Sobi::Can( 'cms.options' ) ) ) {
			if ( !Sobi::Can( 'section.configure' ) ) {
				Sobi::Error( $this->name(), SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ), C::ERROR, 403, __LINE__, __FILE__ );
				exit();
			}
		}
	}

	/**
	 * @throws \ReflectionException
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 * @throws \Exception
	 */
	public function execute()
	{
		$this->_task = strlen( $this->_task ) ? $this->_task : $this->_defTask;
		switch ( $this->_task ) {
			case 'edit':
				$this->editFile();
				Sobi::ReturnPoint();
				break;
			case 'save':
			case 'saveAs':
			case 'compileSave':
				$this->save( $this->_task == 'saveAs', $this->_task == 'compileSave' );
				break;
			case 'info':
				$this->info();
				break;
//			case 'deleteFile':
//				$this->deleteFile();
//				break;
			case 'delete':
				$this->delete();
				break;
			case 'compile':
				$this->compile();
				break;
			case 'clone': /* duplicate template */
				$this->duplicateTemplate();
				break;
			case 'list':
				$this->getTemplateFiles();
				break;
			case 'settings':
				$this->templateSettings();
				break;
			case 'saveConfig':
				$this->saveConfig();
				break;
			default:
				/* case plugin didn't register this task, it was an error */
				if ( !parent::execute() ) {
					Sobi::Error( $this->name(), SPLang::e( 'SUCH_TASK_NOT_FOUND', Input::Task() ), C::NOTICE, 404, __LINE__, __FILE__ );
				}
				break;
		}
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function saveConfig()
	{
		if ( !Factory::Application()->checkToken() ) {
			Sobi::Error( 'Token', SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ), C::ERROR, 403, __LINE__, __FILE__ );
		}

		$config = Input::Arr( 'settings' );
		$templateName = Input::Cmd( 'templateName' );
		if ( !strlen( $templateName ) ) {
			$templateName = SPC::DEFAULT_TEMPLATE;
		}

		/* merge the general entry-edit with other entry-edit data (e.g. for packages) */
		foreach ( $config as $configFile => $settings ) {
			if ( $configFile != 'entry.edit' && array_key_exists( 'entry-edit', $settings ) ) {
				$config[ $configFile ][ 'entry-edit' ] = array_merge( $settings[ 'entry-edit' ], $config[ 'entry.edit' ][ 'entry-edit' ] );
			}
		}

		foreach ( $config as $configFile => $settings ) {
			$store = json_encode( $settings );

			if ( isset( $settings[ 'theme' ] ) && count( $settings[ 'theme' ] ) ) {
				foreach ( $settings[ 'theme' ] as $file => $variables ) {
					$themeFile = FileSystem::FixPath( $this->templatePath( $templateName ) . '/css/themes/_' . $file . '.linc' );
					if ( FileSystem::Exists( $themeFile ) ) {
						$themesContent = FileSystem::Read( $themeFile );
						foreach ( $variables as $variable => $value ) {
							$themesContent = preg_replace( "/@$variable:[^\n]*\;/", "@$variable: $value;", $themesContent );
						}
						try {
							FileSystem::Write( $themeFile, $themesContent );
						}
						catch ( Exception $x ) {
							$this->response( Sobi::Url( 'template.settings' ), Sobi::Txt( 'TP.SETTINGS_NOT_SAVED', $x->getMessage() ), false, C::ERROR_MSG );
						}
					}
				}
			}
			if ( isset( $settings[ 'less' ] ) && count( $settings[ 'less' ] ) ) {
				foreach ( $settings[ 'less' ] as $file => $variables ) {
					$lessFile = FileSystem::FixPath( $this->templatePath( $templateName ) . '/css/' . $file . '.less' );
					if ( FileSystem::Exists( $lessFile ) ) {
						$lessContent = FileSystem::Read( $lessFile );
						foreach ( $variables as $variable => $value ) {
							// @colour-set: sobipro;
							$lessContent = preg_replace( "/@$variable:[^\n]*\;/", "@$variable: $value;", $lessContent );
						}
						try {
							FileSystem::Write( $lessFile, $lessContent );
							SPConfig::compileLessFile( $lessFile, str_replace( 'less', 'css', $lessFile ), true );
						}
						catch ( Exception $x ) {
							$this->response( Sobi::Url( 'template.settings' ), Sobi::Txt( 'TP.SETTINGS_NOT_SAVED', $x->getMessage() ), false, C::ERROR_MSG );
						}
					}
				}
			}
			try {
				FileSystem::Write( FileSystem::FixPath( $this->templatePath( $templateName ) . str_replace( '.', '/', $configFile ) . '.json' ), $store );
			}
			catch ( SPException $x ) {
				$this->response( Sobi::Url( 'template.settings' ), Sobi::Txt( 'TP.SETTINGS_NOT_SAVED', $x->getMessage() ), false, C::ERROR_MSG );
			}
		}
		SPFactory::cache()
			->cleanSectionXML( Sobi::Section() );
		$this->response( Sobi::Url( 'template.settings' ), Sobi::Txt( 'TP.SETTINGS_SAVED' ), false, C::SUCCESS_MSG );
	}

	/**
	 * @throws ReflectionException
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function templateSettings()
	{
		$templateName = Input::Cmd( 'template' );
		$templateSettings = [];
		$file = C::ES;
		if ( !strlen( $templateName ) ) {
			$templateName = SPC::DEFAULT_TEMPLATE;
		}

		$dir = $this->templatePath( $templateName );
		/** @var SPAdmTemplateView $view */
		$view = SPFactory::View( 'template', true );
		if ( Sobi::Section() && Sobi::Cfg( 'section.template', SPC::DEFAULT_TEMPLATE ) == SPC::DEFAULT_TEMPLATE && !defined( 'SOBI_TRIMMED' ) ) {
			SPFactory::message()
				->warning( Sobi::Txt( 'TP.DEFAULT_WARN', 'https://www.sigsiu.net/help_screen/template.info' ), false )
				->setSystemMessage();
		}
		if ( FileSystem::Exists( $dir . '/template.xml' ) ) {
			$file = $this->getTemplateData( $dir, $view, $templateName );
		}
		else {
			SPFactory::message()
				->warning( Sobi::Txt( 'TP.MISSING_DEFINITION_FILE' ), false )
				->setSystemMessage();
		}
		/** search for all json files */
		$directory = new Directory( $dir );
		$configs = array_keys( $directory->searchFile( '.json', false, 2 ) );
		if ( count( $configs ) ) {
			foreach ( $configs as $file ) {
				$prefix = C::ES;
				if ( basename( dirname( $file ) ) != $templateName ) {
					$prefix = basename( dirname( $file ) ) . '-';
				}
				$templateSettings[ $prefix . basename( $file, '.json' ) ] = json_decode( FileSystem::Read( $file ), true );
			}
		}
		$plugins = Factory::Db()
			->select( 'pid', 'spdb_plugins' )
			->loadAssocList( 'pid' );

		$menu = $this->createMenu();
		if ( Sobi::Section() ) {
			$menu->setOpen( 'MENU.SECTION.TEMPLATES' );
		}
		else {
			$menu->setOpen( 'MENU.GLOBAL.TEMPLATES' );
		}

		$view->setCtrl( $this );
		$entriesOrdering = $view->namesFields( null, true );
		$params = [ 'position.asc', 'position.desc' ];
		$entriesOrderingPlain = $view->namesFields( $params, true );
		$entriesFields = $view->entryFields( true );
		$categoryFields = $view->categoryFields( true );
		$sid = Sobi::Section();

		$view
			->assign( $menu, 'menu' )
			->assign( $this->_task, 'task' )
			->assign( $sid, 'sid' )
			->assign( $templateSettings, 'settings' )
			->assign( $entriesOrdering, 'entriesOrdering' )
			->assign( $entriesOrderingPlain, 'entriesOrderingPlain' )
			->assign( $entriesFields, 'entriesFields' )
			->assign( $categoryFields, 'categoryFields' )
			->assign( $plugins, 'apps' )
			->addHidden( $templateName, 'templateName' );

		Sobi::Trigger( 'Settings', $this->name(), [ &$file, &$view ] );

		$view->determineTemplate( 'template', 'config', $dir );
		$view->display();
	}

	/**
	 * @param bool $outputMessage
	 *
	 * @return mixed
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function compile( bool $outputMessage = true )
	{
		if ( !Factory::Application()->checkToken() ) {
			Sobi::Error( 'Token', SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ), C::ERROR, 403, __LINE__, __FILE__ );
		}

		$filename = Input::Cmd( 'fileName' );
		$compress = strpos( $filename, 'css.theme.' ) > 0;
		$file = $this->file( $filename );
		$output = str_replace( 'less', 'css', $file );

		Sobi::Trigger( 'BeforeCompileLess', $this->name(), [ &$file ] );
		$redirectUrl = [ 'task' => 'template.edit', 'file' => $filename ];
		if ( Sobi::Section() ) {
			$redirectUrl[ 'sid' ] = Sobi::Section();
		}
		if ( !$file ) {
			$this->response( Sobi::Url( $redirectUrl ), SPLang::e( 'Missing file to compile %s', Input::String( "filePath" ) ), false, C::ERROR_MSG );
		}
		try {
			SPConfig::compileLessFile( $file, $output, $compress );
			if ( $outputMessage ) {
				$this->response( Sobi::Url( $redirectUrl ), Sobi::Txt( 'TP.LESS_FILE_COMPILED', str_replace( SOBI_PATH, C::ES, $output ) ), false, C::SUCCESS_MSG );
			}
			else {
				return Sobi::Txt( 'TP.LESS_FILE_COMPILED', str_replace( SOBI_PATH, C::ES, $output ) );
			}
		}
		catch ( Exception $x ) {
			$this->response( Sobi::Url( $redirectUrl ), SPLang::e( 'TP.LESS_FILE_NOT_COMPILED', Input::String( "filePath" ) ), false, C::ERROR_MSG );
		}
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function getTemplateFiles()
	{
		$type = Input::Cmd( 'type', 'post' );
		if ( strstr( $type, '.' ) ) {
			$type = explode( '.', $type );
			$type = $type[ 0 ];
		}
		$directory = $this->templatePath( Sobi::Cfg( 'section.template', SPC::DEFAULT_TEMPLATE ) );
		$directory = FileSystem::FixPath( $directory . '/' . $type );
		$arr = [];
		if ( file_exists( $directory ) ) {
			$files = scandir( $directory );
			if ( is_array( $files ) && count( $files ) ) {
				foreach ( $files as $file ) {
					$stack = explode( '.', $file );
					if ( array_pop( $stack ) == 'xsl' ) {
						$arr[] = [ 'name' => $stack[ 0 ], 'filename' => $file ];
					}
				}
			}
		}
		Sobi::Trigger( 'List', 'Templates', [ &$arr ] );
		SPFactory::mainframe()->cleanBuffer();
		echo json_encode( $arr );

		exit;
	}

//	protected function deleteFile()
//	{
//		if( !( SPFactory::mainframe()->checkToken() ) ) {
//			Sobi::Error( 'Token', SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ), SPC::ERROR, 403, __LINE__, __FILE__ );
//		}
//		$file = $this->file( Input::Cmd( 'sp_fedit' ) );
//		Sobi::Trigger( 'Delete', $this->name(), array( &$content, &$file ) );
//		if( !$file ) {
//			throw new SPException( SPLang::e( 'Missing  file to delete %s', Input::Cmd( 'sp_fedit' ) ) );
//		}
//		$fClass = new File( );
//		$File = new $fClass( $file );
//		if( $File->delete() ) {
//			$redirectUrl = array( 'task' => 'template.edit', 'file' => 'template.xml' );
//			if( Input::Sid() ) {
//				$redirectUrl[ 'sid' ] = Input::Sid();
//			}
//			Sobi::Redirect( Sobi::Url( $redirectUrl ), 'File has been deleted' );
//		}
//		else {
//			Sobi::Redirect( SPMainFrame::getBack(), 'Cannot delete the file', SPC::ERROR_MSG );
//		}
//	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function delete()
	{
		$template = Input::Cmd( 'templateName' );
		if ( $template == SPC::DEFAULT_TEMPLATE ) {
			$this->response( Sobi::Url( 'template.info' ), Sobi::Txt( 'TP.DO_NOT_REMOVE_DEFAULT' ), true, 'error' );
		}
		if ( $template == SPC::TEMPLATE_STORAGE || $template == SPC::TEMPLATE_FRONT ) {
			$this->response( Sobi::Url( [ 'task' => 'template.info', 'template' => $template ] ), Sobi::Txt( 'TP.DO_NOT_REMOVE' ), true, 'error' );
		}

		$dir = $this->templatePath( $template );
		if ( $dir && FileSystem::Delete( $dir ) ) {
			$sid = Input::Pid();
			if ( $sid ) {
				$redirectUrl = [ 'task' => 'config.general', 'pid' => $sid ];
			}
			else {
//				$redirectUrl = [ 'task' => 'config.general' ];
				$redirectUrl = [ 'task' => 'template.info', 'template' => SPC::DEFAULT_TEMPLATE ];
			}
			SPFactory::history()->logAction( SPC::LOG_DELETE, 0, Sobi::Section(), 'template', C::ES,
				[ 'name' => $template ]
			);
			$this->response( Sobi::Url( $redirectUrl ), Sobi::Txt( 'TP.REMOVED' ), true, 'success' );
		}
		else {
			$this->response( Sobi::Back(), Sobi::Txt( 'TP.CANNOT_REMOVE' ), false, 'error' );
		}
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function duplicateTemplate()
	{
		$srcName = Input::Cmd( 'templateName' );
		if ( $srcName == SPC::TEMPLATE_STORAGE || $srcName == SPC::TEMPLATE_FRONT ) {
			$this->response( Sobi::Url( [ 'task' => 'template.info', 'template' => $srcName ] ), Sobi::Txt( 'TP.DO_NOT_CLONE' ), true, C::ERROR_MSG );
		}

		$newName = Input::String( 'templateNewName', 'post', 'Duplicated Template' );
		$newFolderName = $newId = StringUtils::Nid( $newName );
		$counter = 1;
		while ( FileSystem::Exists( SPLoader::dirPath( SPC::TEMPLATE_PATH . $newFolderName, 'front', false ) ) ) {
			$newFolderName = $newId . '-' . $counter++;
		}
		$newPath = SPLoader::dirPath( SPC::TEMPLATE_PATH . $newFolderName, 'front', false );

		/* copy the files of the template */
		$srcPath = $this->templatePath( $srcName );
		if ( !FileSystem::Copy( $srcPath, $newPath ) ) {
			throw new SPException( SPLang::e( 'COULD_NOT_COPY_DIRECTORY', $srcPath, $newPath ) );
		}

		/* modify the XML file of the new template */
		$oldName = C::ES;
		$defFile = SPLoader::path( $newPath . '/template', 'absolute', true, 'xml' );
		if ( $defFile ) {
			$definition = new DOMDocument();
			$definition->load( $defFile );
			$xdef = new DOMXPath( $definition );

			/* create creation date */
			$date = SPFactory::config()->date( time(), C::ES, 'Y-m-d' );
			$xdef->query( '/template/creationDate' )->item( 0 )->nodeValue = $date;

			/* create name and id */
			$oldName = $xdef->query( '/template/name' )->item( 0 )->nodeValue;
			$xdef->query( '/template/name' )->item( 0 )->nodeValue = $newName;
			$xdef->query( '/template/id' )->item( 0 )->nodeValue = $newFolderName;

			/* create new description */
			$newDesc = Sobi::Txt( 'TP.CLONE_NOTE', [ 'name' => $oldName, 'date' => SPFactory::config()->date( time(), 'date.publishing_format' ) ] );
			$newDesc = '<p class="text-success"><strong>' . $newDesc . '</strong></p>';
			$oldDesc = $xdef->query( '/template/description' )->item( 0 )->nodeValue;
			$oldDesc = str_ireplace( 'class="text-success"', C::ES, $oldDesc );
			$xdef->query( '/template/description' )->item( 0 )->nodeValue = "$newDesc\n$oldDesc";

			$xmlFile = new File( $defFile );
			$xmlFile->content( $definition->saveXML() );
			$xmlFile->save();
		}

		/** Replace template's prefixes  */
		if ( FileSystem::Exists( $newPath . '/template.php' ) ) {
			$content = FileSystem::Read( $newPath . '/template.php' );
			$class = [];
			preg_match( '/\s*(class)\s+(\w+)/', $content, $class );
			$className = $class[ 2 ];

			/* b3-default template can no longer be duplicated */
			/* if for example b3-default */
//			if ( strstr( $srcName, '-' ) ) {
//				$srcName = explode( '-', $srcName );
//				/* take the longer part - it's most likely the right one */
//				$srcName = strlen( $srcName[ 0 ] > $srcName[ 1 ] ) ? $srcName[ 0 ] : $srcName[ 1 ];
//			}
//			$newClassName = stristr( $className, $srcName ) ?
//				str_ireplace( $srcName, ucfirst( $newId ), $className ) :
//				( $className == 'TplFunctions' ? 'tpl' . ucfirst( $newId ) . 'Functions' : $className . ucfirst( $newId ) );

			/* TplFunctions was the name of the very first file. As b3-default templates are no longer supported, it can also be removed */
//			$newClassName = ( $className == 'TplFunctions' ? 'tpl' . ucfirst( $newId ) . 'Functions' : 'tpl' . ucfirst( $newId ) );
			$newClassName = 'tpl' . ucfirst( $newId );
			$newClassName = str_ireplace( '-', C::ES, $newClassName );

			$content = str_replace( 'class ' . $className, 'class ' . $newClassName, $content );
			$content = str_replace( 'SobiPro.' . $srcName, 'SobiPro.' . $newId, $content );    //replace css class
			FileSystem::Write( $newPath . '/template.php', $content );

			/* now go through all XSL files */
			$directory = new Directory( $newPath );
			$files = $directory->searchFile( '.xsl', false, 3 );
			if ( count( $files ) ) {
				$files = array_keys( $files );
				foreach ( $files as $file ) {
					$content = FileSystem::Read( $file );
					if ( strstr( $content, "'$className::" ) ) {
						$content = str_replace( "'$className::", "'$newClassName::", $content );
						FileSystem::Write( $file, $content );
					}
				}
			}
		}
		/* now the name-spaces in style sheet files */
		$directory = new Directory( $newPath );
		$files = $directory->searchFile( [ '.less', '.css', '.linc' ], false, 3 );
		if ( is_array( $files ) && count( $files ) ) {
			//$oldTplName = Input::Cmd( 'templateName' );
			$files = array_keys( $files );
			foreach ( $files as $file ) {
				$content = FileSystem::Read( $file );
				if ( strstr( $content, $srcName ) ) {
					$content = str_replace( $srcName, $newId, $content );
					FileSystem::Write( $file, $content );
				}
			}
		}

		SPFactory::history()->logAction( SPC::LOG_DUPLICATE, 0, Sobi::Section(), 'template', C::ES,
			[ 'name'   => $oldName,
			  'new'    => $newName,
			  'folder' => $newFolderName ]
		);

		$this->response( Sobi::Url( [ 'task'     => 'template.info',
		                              'template' => str_replace( SOBI_PATH . '/usr/templates/', C::ES, $newFolderName ) ] ),
			Sobi::Txt( 'TP.DUPLICATED' ), false, C::SUCCESS_MSG
		);
	}

	/**
	 * @throws ReflectionException
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	private function info()
	{
		$templateName = Input::Cmd( 'template' );
		if ( !strlen( $templateName ) ) {
			$templateName = SPC::DEFAULT_TEMPLATE;
		}
		$dir = $this->templatePath( $templateName );
		/** @var SPAdmTemplateView $view */
		$view = SPFactory::View( 'template', true );
		if ( Sobi::Section() && Sobi::Cfg( 'section.template', SPC::DEFAULT_TEMPLATE ) == SPC::DEFAULT_TEMPLATE && !defined( 'SOBI_TRIMMED' ) ) {
			SPFactory::message()
				->warning( Sobi::Txt( 'TP.DEFAULT_WARN', 'https://www.sigsiu.net/help_screen/template.info' ), false )
				->setSystemMessage();
		}

		$definition = C::ES;
		if ( FileSystem::Exists( $dir . '/template.xml' ) ) {
			$definition = $this->getTemplateData( $dir, $view, $templateName );
		}
		else {
			SPFactory::message()
				->warning( Sobi::Txt( 'TP.MISSING_DEFINITION_FILE' ), false )
				->setSystemMessage();
		}

		$sid = Sobi::Section();
		$menu = $this->createMenu();
		if ( $sid ) {
			$menu->setOpen( 'MENU.SECTION.TEMPLATES' );
		}
		else {
			$menu->setOpen( 'MENU.GLOBAL.TEMPLATES' );
		}

		$view
			->assign( $menu, 'menu' )
			->assign( $this->_task, 'task' )
			->assign( $sid, 'sid' )
			->addHidden( $templateName, 'templateName' );

		Sobi::Trigger( 'Info', $this->name(), [ &$definition, &$view ] );

		$view->determineTemplate( 'template', 'info' );
		$view->display();
	}

	/**
	 * @param string $template
	 *
	 * @return string
	 * @throws SPException|\Sobi\Error\Exception
	 */
	public function getTemplateTree( string $template ): string
	{
		if ( FileSystem::Exists( SPLoader::dirPath( SPC::TEMPLATE_PATH ) . $template ) ) {
			return $this->listTemplates( SPLoader::dirPath( SPC::TEMPLATE_PATH ) . $template, false );
		}
		else {
			SPFactory::message()->error( Sobi::Txt( 'TP.TEMPLATE_MISSING', Sobi::Cfg( 'section.template' ) ), false );

			return C::ES;
		}
	}

	/**
	 * @param bool $apply
	 * @param bool $clone
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function save( $apply = false, $clone = false )
	{
		if ( !Factory::Application()->checkToken() ) {
			Sobi::Error( 'Token', SPLang::e( 'UNAUTHORIZED_ACCESS_TASK', Input::Task() ), C::ERROR, 403, __LINE__, __FILE__ );
		}

		$content = Input::Raw( 'file_content', 'post' );
		$fileName = $this->file( Input::String( 'fileName' ), !$apply );
		Sobi::Trigger( 'Save', $this->name(), [ &$content, &$fileName ] );
		if ( !$fileName ) {
			throw new SPException( SPLang::e( 'Missing  file to save %s', Input::Cmd( 'fileName' ) ) );
		}
		$file = new File( $fileName );
		$file->content( stripslashes( $content ) );
		try {
			$file->save();

			$message = Sobi::Txt( 'TP.FILE_SAVED' );
			if ( $clone ) {
				$message .= "\n" . $this->compile( false );
			}

			$redirectUrl = [ 'task' => 'template.edit', 'file' => Input::Cmd( 'fileName' ) ];
			if ( Sobi::Section() ) {
				$redirectUrl[ 'sid' ] = Sobi::Section();
			}
			$this->response( Sobi::Url( $redirectUrl ), $message, $apply, C::SUCCESS_MSG );
		}
		catch ( Sobi\Error\Exception $x ) {
			$this->response( Sobi::Back(), $x->getMessage(), false, C::ERROR_MSG );
		}
	}

	/**
	 * @param $file
	 * @param bool $exits
	 *
	 * @return bool|string|string[]
	 * @throws SPException|\Sobi\Error\Exception
	 */
	private function file( $file, bool $exits = true )
	{
		$ext = FileSystem::GetExt( $file );
		$file = explode( '.', $file );
		unset( $file[ count( $file ) - 1 ] );
		$wanted = implode( '/', $file ) . '.' . $ext;
		if ( strstr( $file[ 0 ], 'cms:' ) ) {
			$file[ 0 ] = str_replace( 'cms:', C::ES, $file[ 0 ] );
			$file = SPFactory::mainframe()->path( implode( '.', $file ) );
			$file = FileSystem::FixPath( SPLoader::path( $file, 'root', $exits, $ext ) );
		}
		else {
			$file = FileSystem::FixPath( SPLoader::path( SPC::TEMPLATE_PATH . implode( '.', $file ), 'front', $exits, $ext ) );
		}
		if ( !$file ) {
			Sobi::Error( $this->name(), SPLang::e( 'FILE_NOT_FOUND', $wanted ), C::WARNING, 404, __LINE__, __FILE__ );
		}

		return $file;
	}

	/**
	 * @param string $template
	 *
	 * @return array|bool|string
	 * @throws SPException|\Sobi\Error\Exception
	 */
	private function templatePath( string $template )
	{
		$templatePath = explode( '.', $template );
		if ( strstr( $templatePath[ 0 ], 'cms:' ) ) {
			$templatePath[ 0 ] = str_replace( 'cms:', C::ES, $templatePath[ 0 ] );
			$templatePath = SPFactory::mainframe()->path( implode( '.', $templatePath ) );
			$templatePath = SPLoader::dirPath( $templatePath, 'root', true );
		}
		else {
			/** @var array $templatePath */
			$templatePath = SPLoader::dirPath( SPC::TEMPLATE_PATH . implode( '.', $templatePath ), 'front', true );
		}
		if ( !$templatePath ) {
			$this->response( Sobi::Url( [ 'task' => 'template.info', 'template' => SPC::DEFAULT_TEMPLATE ] ), SPLang::e( 'TEMPLATE_NOT_FOUND', $template ), true, C::ERROR_MSG );
		}

		return $templatePath;
	}

	/**
	 * @throws SPException
	 * @throws \Sobi\Error\Exception|\ReflectionException
	 */
	private function editFile()
	{
		if ( Sobi::Section() && Sobi::Cfg( 'section.template', SPC::DEFAULT_TEMPLATE ) == SPC::DEFAULT_TEMPLATE && !defined( 'SOBI_TRIMMED' ) ) {
			SPFactory::message()
				->warning( Sobi::Txt( 'TP.DEFAULT_WARN', 'https://www.sigsiu.net/help_screen/template.info' ), false )
				->setSystemMessage();
		}
		$file = Input::String( 'file' );
		$file = $this->file( $file );
		$ext = FileSystem::GetExt( $file );
		$filename = FileSystem::GetFileName( $file );
		$fileContent = FileSystem::Read( $file );
		$path = str_replace( '\\', '/', SOBI_PATH );
		if ( strstr( $file, $path ) ) {
			$filepath = str_replace( $path . '/usr/templates/', C::ES, $file );
		}
		else {
			$filepath = str_replace( SOBI_ROOT, C::ES, $file );
		}

		$sid = Sobi::Section();
		$menu = $this->createMenu();
		if ( $sid ) {
			$menu->setOpen( 'MENU.SECTION.TEMPLATES' );
		}
		else {
			$menu->setOpen( 'MENU.GLOBAL.TEMPLATES' );
		}

		/** @var SPAdmTemplateView $view */
		$view = SPFactory::View( 'template', true );
		$view
			->assign( $fileContent, 'file_content' )
			->assign( $filepath, 'file_path' )
			->assign( $filename, 'file_name' )
			->assign( $ext, 'file_ext' )
			->assign( $menu, 'menu' )
			->assign( $this->_task, 'task' )
			->assign( $sid, 'sid' )
			->addHidden( Input::String( 'file' ), 'fileName' )
			->addHidden( $filepath, 'filePath' );

		Sobi::Trigger( 'Edit', $this->name(), [ &$file, &$view ] );

		$view->determineTemplate( 'template', 'edit' );
		$view->display();
	}

	/**
	 * @param $dir
	 * @param $view
	 * @param $templateName
	 *
	 * @return string
	 * @throws SPException
	 */
	protected function getTemplateData( $dir, $view, $templateName )
	{
		$info = new DOMDocument();
		$info->load( $dir . '/template.xml' );
		$xinfo = new DOMXPath( $info );
		$template = [];
		$template[ 'name' ] = $xinfo->query( '/template/name' )->item( 0 )->nodeValue;
		$view->assign( $template[ 'name' ], 'template_name' );
		$template[ 'author' ] = [
			'name'  => $xinfo->query( '/template/authorName' )->item( 0 )->nodeValue,
			'email' => $xinfo->query( '/template/authorEmail' )->item( 0 )->nodeValue,
			'url'   => $xinfo->query( '/template/authorUrl' )->item( 0 )->nodeValue ? $xinfo->query( '/template/authorUrl' )->item( 0 )->nodeValue : C::ES,
		];
		$template[ 'copyright' ] = $xinfo->query( '/template/copyright' )->item( 0 )->nodeValue;
		$template[ 'license' ] = $xinfo->query( '/template/license' )->item( 0 )->nodeValue;
		$template[ 'date' ] = $xinfo->query( '/template/creationDate' )->item( 0 )->nodeValue;
		$template[ 'version' ] = $xinfo->query( '/template/version' )->item( 0 )->nodeValue;
		$template[ 'description' ] = $xinfo->query( '/template/description' )->item( 0 )->nodeValue;
		$template[ 'id' ] = $xinfo->query( '/template/id' )->item( 0 )->nodeValue;

		if ( $xinfo->query( '/template/previewImage' )->length && $xinfo->query( '/template/previewImage' )->item( 0 )->nodeValue ) {
			$template[ 'preview' ] = FileSystem::FixUrl( Sobi::Cfg( 'live_site' ) . str_replace( '\\', '/', str_replace( SOBI_ROOT . '/', C::ES, $dir ) ) . '/' . $xinfo->query( '/template/previewImage' )->item( 0 )->nodeValue );
		}

		$file = C::ES;
		if ( $xinfo->query( '/template/files/file' )->length ) {
			$files = [];
			foreach ( $xinfo->query( '/template/files/file' ) as $file ) {
				$filePath = $dir . '/' . $file->attributes->getNamedItem( 'path' )->nodeValue;
				if ( $filePath && is_file( $filePath ) ) {
					$filePath = $templateName . '.' . str_replace( '/', '.', $file->attributes->getNamedItem( 'path' )->nodeValue );
				}
				else {
					$filePath = C::ES;
				}
				$files[] = [
					'file'        => $file->attributes->getNamedItem( 'path' )->nodeValue,
					'description' => $file->nodeValue,
					'filepath'    => $filePath,
				];
			}
			$template[ 'files' ] = $files;
			$view->assign( $files, 'files' );
		}
		$view->assign( $template, 'template' );

		return $file;
	}
}
