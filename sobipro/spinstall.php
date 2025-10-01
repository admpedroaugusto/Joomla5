<?php
/**
 * @package SobiPro multi-directory component with content construction support
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006–2025 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/GPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according to section 7 of GPL v3.
 * See https://www.gnu.org/licenses/gpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * @modified 05 March 2025 by Sigrid Suski
 */

defined( '_JEXEC' ) || exit( 'Restricted access' );

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Installer\InstallerAdapter;
use Sobi\Utils\Serialiser;
use SobiPro\Autoloader;

/**
 * Class com_sobiproInstallerScript
 */
class com_sobiproInstallerScript
{
	/**
	 * The minimum PHP version required to install this SobiPro version
	 *
	 * @var   string
	 */
	protected $minimumPHPVersion = '8.0.0';

	/**
	 * The minimum Joomla! version required to install this SobiPro version
	 *
	 * @var   string
	 */
	protected $minimumJoomlaVersion = '4.1';

	/**
	 * The maximum Joomla! version this SobiPro version can be installed on
	 *
	 * @var   string
	 */
	protected $maximumJoomlaVersion = '5.3.99';

	/**
	 * @var     array|bool
	 */
	private static $installedSobiPro;
	/**
	 * @var     string
	 */
	private static $minimumSobiProVersion;

	/**
	 * @var bool
	 */
	private static $sobiLoaded = false;

	/**
	 * Constructor
	 *
	 * @param InstallerAdapter $adapter The object responsible for running this script
	 */
	public function __construct( InstallerAdapter $adapter )
	{
	}

	/**
	 * Called before any type of action.
	 * Runs just before any installation action is performed on the component.
	 * Verifications and pre-requisites should run in this function.
	 *
	 * @param $action -> Which action is happening (install|discover_install|update)
	 * @param $adapter -> The object responsible for running this script
	 *
	 * @return bool -> True to let the installation proceed, false to halt the installation
	 */
	public function preflight( $action, InstallerAdapter $adapter ): bool
	{
		$return = true;

		if ( $action != 'uninstall' ) {
			/* Version from the current installation manifest file version */
			$manifest = $adapter->getManifest();
			$release = $manifest->version;

			// Check the minimum PHP version
			if ( !version_compare( PHP_VERSION, $this->minimumPHPVersion, 'ge' ) ) {
				$msg = "<p><strong>You need PHP $this->minimumPHPVersion or later to install SobiPro $release!</strong></p>";
				Log::add( $msg, Log::ERROR, 'jerror' );

				$return = false;
			}

			// Check the minimum Joomla! version
			if ( !version_compare( JVERSION, $this->minimumJoomlaVersion, 'ge' ) ) {
				$msg = "<p><strong>You need Joomla $this->minimumJoomlaVersion or later to install SobiPro $release!</strong></p>";
				Log::add( $msg, Log::ERROR, 'jerror' );

				$return = false;
			}

			// Check the maximum Joomla! version
			if ( !version_compare( JVERSION, $this->maximumJoomlaVersion, 'le' ) ) {
				$msg = "<p><strong>You need Joomla $this->maximumJoomlaVersion or earlier to install SobiPro $release.</strong></p>";
				Log::add( $msg, Log::ERROR, 'jerror' );

				$return = false;
			}

			if ( $action == 'update' ) {
				/* Get the minimum SobiPro version this SobiPro version can be installed on */
				self::$minimumSobiProVersion = (string) $manifest->SobiPro->requirements->core->attributes()->version;
				self::$installedSobiPro = $this->getInstalledSobiPro();

				if ( self::$installedSobiPro && self::$installedSobiPro->version < self::$minimumSobiProVersion ) {
					$msg = "<p><strong>You have installed SobiPro " . self::$installedSobiPro->version . ". Please install first SobiPro " . self::$minimumSobiProVersion . " and then SobiPro $release!</strong></p>";
					Log::add( $msg, Log::ERROR, 'jerror' );

					$return = false;
				}
			}
			if ( !$return ) {
				return false;
			}

			/* Always reset the OPcache if it's enabled. Otherwise, there's a good chance the server will not know we are
			   replacing .php scripts. This is a major concern since PHP 5.5 included and enabled OPcache by default. */
			if ( function_exists( 'opcache_reset' ) ) {
				opcache_reset();
			}
			clearstatcache();

			// Show the essential information on install/update back-end
			echo "<h2>Installing SobiPro version $release ...</h2>";

			/* Delete the old plugin */
			$this->uninstallExtension( 'plugin', 'spHeader' );
			/* Installing the SobiPro header plugin */
			$this->installPlugins( $adapter->getParent()->get( 'paths' ) );
		}

		return true;
	}

	/**
	 * Called after any type of action.
	 * Runs right after any installation action is performed on the component.
	 *
	 * @param $action -> Which action is happening (install|discover_install|update)
	 * @param $adapter -> The object responsible for running this script
	 */
	public function postflight( $action, $adapter )
	{
		if ( $action == 'install' ) {
			$this->installSamples();
		}

		return true;
	}

	/**
	 * Called on update.
	 *
	 * @param \Joomla\CMS\Installer\InstallerAdapter $adapter
	 *
	 * @return bool
	 */
	public function update( InstallerAdapter $adapter ): bool
	{
		if ( $this->installFramework() ) {
			$this->showGreeting( true );
			$ret = true;
		}
		else {
			$ret = false;
		}

		$this->updateFilesandFolders();
		$this->updateDatabase();

		return $ret;
	}

	/**
	 * Called on installation.
	 *
	 * @param \Joomla\CMS\Installer\InstallerAdapter $adapter
	 *
	 * @return bool
	 */
	public function install( InstallerAdapter $adapter ): bool
	{
		if ( $this->installFramework() ) {
			$this->showGreeting( false );
			$ret = true;
		}
		else {
			$ret = false;
		}

		$this->updateFilesandFolders();

		/* allow public search for new installations */
		$db = Factory::getDBO();
		try {
			$pid = $db->setQuery( 'SELECT `pid` FROM `#__sobipro_permissions` WHERE `subject` = "section" AND `action` = "search"' )->loadResult();
			$db->setQuery( "INSERT IGNORE INTO `#__sobipro_permissions_map` (`rid`, `sid`, `pid`) VALUES (1, 1, $pid)" )->execute();
		}
		catch ( Exception $x ) {
		}

		$this->createProcedures();

		return $ret;
	}

	/**
	 * Called on uninstallation.
	 */
	public function uninstall( InstallerAdapter $adapter )
	{
		$db = Factory::getDBO();
		$error = false;
		$msg = '';

		$installedSobiPro = $this->getInstalledSobiPro();
		if ( $installedSobiPro ) {
			$msg .= "<p>Uninstalling SobiPro version $installedSobiPro->version ...</p>";
		}
		else {
			$msg .= "<p>Uninstalling SobiPro ...</p>";
		}

		/* remove the header plugin */
		$query = $db->getQuery( true )
			->select( 'extension_id' )
			->from( '#__extensions' )
			->where( 'type = ' . $db->quote( 'plugin' ) )
			->where( 'element = ' . $db->quote( 'sobiproheader' ) );

		$id = $db->setQuery( $query )->loadResult();

		if ( $id ) {
			$installer = new Installer();
			$installer->uninstall( 'plugin', $id, 0 );
			$msg .= '<p>...SobiPro Header plugin removed</p>';
		}

		/* remove the framework */
		if ( Folder::delete( implode( '/', [ JPATH_ROOT, 'libraries', 'sobi' ] ) ) ) {
			$msg .= '<p>...SobiPro Framework removed</p>';
		}
		else {
			$msg .= '<p>Error removing SobiPro Framework. Please remove it manually from "/libraries/sobi".</p>';
			$error = true;
		}

		/* remove all tables starting with SobiPro prefix */
		try {
			$prefix = $db->getPrefix();
			$db->setQuery( "show tables like '" . $prefix . "sobipro_%'" );
			$tables = $db->loadColumn();
			foreach ( $tables as $table ) {
				$db->setQuery( "DROP TABLE {$table};" )->execute();
			}
			$msg .= "<span style=\"display:block;\">...all SobiPro '{$prefix}sobipro_' tables removed from database</p>";
		}
		catch ( Exception $x ) {
			$msg .= "<span style=\"display:block;\">Removing SobiPro tables from database failed. Please remove all tables starting with '{$prefix}sobipro_' manually.</p>";
			$error = true;
		}
		try {
			/* remove the procedure if exists */
			$mySQLi = $db->getConnection();
			if ( method_exists( $mySQLi, 'real_query' ) ) {
				$mySQLi->real_query( 'DROP PROCEDURE IF EXISTS SPGetRelationsPath;' );
				$msg .= "<span style=\"display:block;\">...SobiPro's procedure 'SPGetRelationsPath' removed from database</p>";
			}
		}
		catch ( Exception $x ) {
			$msg .= "<span style=\"display:block;\">Removing SobiPro's procedure 'SPGetRelationsPath' from database failed. Please remove it manually.</p>";
			$error = true;
		}

		/* delete the SobiPro folder from the images folder */
		if ( Folder::delete( implode( '/', [ JPATH_ROOT, 'images', 'sobipro' ] ) ) ) {
			$msg .= '<p>...all SobiPro files removed from images folder</p>';
		}
		else {
			$msg .= '<p>Error removing files from images folder. Please remove them manually.</p>';
			$error = true;
		}

		/* Finish message */
		if ( $error ) {
			$msg .= '<p><br/><strong>Done! Please check the occurred problems and fix them manually to remove SobiPro completely from your system.</strong></p>';
		}
		else {
			$msg .= '<p><br/><strong>Done! All SobiPro files and database tables have been removed from your system.</strong></p>';
		}
		$msg .= "<p><strong>Thank you for using SobiPro!</strong></p>";

		Log::add( $msg, Log::INFO, 'jerror' );

		return !$error;
	}

	/**
	 * @return false|mixed
	 */
	protected function getInstalledSobiPro()
	{
		$db = Factory::getDbo();
		$query = $db->getQuery( true );
		$installed = false;

		$query->select( $db->quoteName( [ 'name', 'manifest_cache', 'type' ] ) )
			->from( $db->quoteName( '#__extensions' ) )
			->where( $db->quoteName( 'type' ) . ' = ' . $db->quote( 'component' ) )
			->where( $db->quoteName( 'element' ) . ' = ' . $db->quote( 'com_sobipro' ) );
		$extension = $db->setQuery( $query )->loadObjectList();

		if ( count( $extension ) ) {
			$installed = json_decode( $extension[ 0 ]->manifest_cache );
		}

		return $installed;
	}

	/**
	 * @param false $update
	 */
	protected function showGreeting( bool $update = false )
	{
		$version = preg_replace( '/alpha|beta|rc/', '', JVERSION );
		define( 'SOBI_CMS', version_compare( $version, '4.0.0', 'ge' ) ?
			'joomla4' : ( version_compare( JVERSION, '3.0.0', 'ge' ) ? 'joomla3' : 'joomla16' )
		);

		$classes = ( SOBI_CMS == 'joomla4' ) ? '' : 'alert alert-info';
		$styles = ( SOBI_CMS == 'joomla4' ) ? '' : ' margin-bottom: 50px;';
		$welcome = $update ? 'Thank you for updating SobiPro!' : 'Welcome to SobiPro!';

		echo '<div class="' . $classes . '" style="margin-top: 20px;"><h3>' . $welcome . '</h3><p>
SobiPro will now check your system. Please check if there are any errors or warnings. If the system check reports errors, your SobiPro installation will probably not work. If you see warnings, some SobiPro functions may be disrupted or malfunctioning. In these cases, you should take a look at the  <a href="https://www.sigsiu.net/sobipro/requirements"><strong>SobiPro Requirements page</strong></a> on our website.</p>
<p>You can install languages directly from our <a href="index.php?option=com_sobipro&task=extensions.browse"><strong>Repository</strong></a> or download them from our <a href="https://www.sigsiu.net/center/languages"><strong>website</strong></a> and install it in the <a href="index.php?option=com_sobipro&task=extensions.installed"><strong>SobiPro Application Manager</strong></a>.</p></div>';
		echo '<iframe src="index.php?option=com_sobipro&task=requirements&init=1&tmpl=component&update=' . ( $update ? '1' : '0' ) . '" style="border: 0 solid #dbe4f0; border-radius: 10px; height: 900px; width: 100%;' . $styles . '" class="sp-install"></iframe>';

	}

	/**
	 * @param string $type
	 * @param string $element
	 */
	protected function uninstallExtension( string $type, string $element )
	{
		$db = Factory::getDbo();
		$query = $db->getQuery( true );

		$query->select( 'extension_id' )
			->from( '#__extensions' )
			->where( 'type = ' . $db->quote( $type ) )
			->where( 'element = ' . $db->quote( $element ) );
		$id = $db->setQuery( $query )->loadResult();

		if ( $id ) {
			$installer = new Installer();
			$installer->uninstall( $type, $id );
		}
	}

	/**
	 * @param $source
	 */
	protected function installPlugins( $source )
	{
		$source = $source[ 'source' ];
		$plugins = [ 'Header' ];
		$path = $source . '/Plugins';

		$installer = new Installer();
		$db = Factory::getDBO();
		foreach ( $plugins as $plugin ) {
			$dir = $path . '/' . $plugin;
			if ( $installer->install( $dir ) ) {
				$element = 'sobipro' . strtolower( $plugin );
				try {
					$db->setQuery( "UPDATE #__extensions SET enabled =  '1' WHERE element = '$element';" )->execute();
				}
				catch ( Exception $x ) {
				}
			}
		}
	}

	/**
	 * @return void
	 */
	protected function updateFilesandFolders()
	{
		$filesRemoval = [
			'lib/base/database.php',
			'lib/cms/joomla_common/base/database.php',
			'lib/cms/joomla_common/base/fs.php',
			'lib/cms/joomla3/base/database.php',
			'lib/cms/joomla3/base/fs.php',
			'lib/cms/joomla3/base/lang.php',
			'lib/cms/joomla3/base/helper.php',
			'services/installers/schemas/application.xsd',
		];
		$sobiRoot = JPATH_ROOT . '/components/com_sobipro/';
		foreach ( $filesRemoval as $file ) {
			if ( file_exists( $sobiRoot . $file ) ) {
				File::delete( $sobiRoot . $file );
			}
		}
		/* Delete temporary and unnecessary folders */
		if ( file_exists( $sobiRoot . 'usr/locale/' ) ) {
			Folder::delete( $sobiRoot . 'usr/locale/' );
		}
		if ( file_exists( $sobiRoot . 'lib/cms/joomla16/' ) ) {
			Folder::delete( $sobiRoot . 'lib/cms/joomla16/' );
		}

		/* Images folder (category images) */
		$sobiImages = JPATH_ROOT . '/images/sobipro/';
		if ( !file_exists( $sobiImages ) ) {
			Folder::create( $sobiImages );
		}
		if ( !file_exists( $sobiImages . 'categories/' ) ) {
			Folder::create( $sobiImages . 'categories/' );

			if ( file_exists( $sobiRoot . 'tmp/install/image.png' ) ) {
				File::move( $sobiRoot . 'tmp/install/image.png', $sobiImages . 'categories/image.png' );
			}
		}

		/* Handling sample data */
//		if ( file_exists( $sobiRoot . 'tmp/SampleData/entries/' ) ) {
//			Folder::move( $sobiRoot . 'tmp/SampleData/entries/', $sobiImages . 'entries/' );
//		}

		/* Update repository */
		try {
			$this->loadSobi();
			require_once( $sobiRoot . 'lib/ctrl/adm/extensions.php' );
			$ctrl = new SPExtensionsCtrl();
			$ctrl->updateRepository( 'repository.2.0.xml' );
		}
		catch ( Exception $x ) {
			Log::add( 'Error initialising SobiPro: ' . $x->getMessage(), Log::ERROR, 'jerror' );
		}
	}

	/**
	 * Sobi Framework installation.
	 *
	 * @return bool
	 */
	protected function installFramework(): bool
	{
		$libpathShort = '/libraries/sobi';
		$libpath = JPATH_ROOT . $libpathShort;
		$fwPackage = JPATH_ROOT . '/components/com_sobipro/Sobi-Framework.zip';
		$fwPackageShort = 'Site/Sobi-Framework.zip';

		if ( !file_exists( $libpath ) ) {
			Folder::create( $libpath );
		}
		else {
			$files = scandir( $libpath );
			if ( count( $files ) ) {
				foreach ( $files as $file ) {
					if ( strstr( $file, '.tar.gz' ) || strstr( $file, '.php' ) ) {
						File::delete( $libpath . '/' . $file );
					}
				}
			}
		}

		if ( file_exists( $fwPackage ) ) {
			try {
				$arch = new Joomla\Archive\Zip();
				$arch->extract( $fwPackage, $libpath );
				File::delete( $fwPackage );

				return true;
			}
			catch ( Exception $x ) {
				$msg = "<p>Failed to unpack the Sobi Framework '$fwPackageShort' to '$libpathShort'.</p>";
				Log::add( $msg, Log::ERROR, 'jerror' );
			}
		}
		else {
			$msg = "<p>Sobi Framework file '$fwPackageShort' not copied to components folder on installation.</p>";
			Log::add( $msg, Log::ERROR, 'jerror' );
		}

		return false;
	}

	/**
	 * Installs the sample data automatically for the trial version.
	 * Sobi is already initialized.
	 */
	protected function installSamples()
	{
		if ( defined( 'SOBI_TRIMMED' ) ) {
			require_once( SOBI_PATH . '/lib/ctrl/adm/requirements.php' );
			$ctrl = new SPRequirements();
			$ctrl->installSamples( true );
		}
	}

	/**
	 * @return void
	 */
    protected function createProcedures()
        {
            $db = Factory::getDbo();
            /**
            * We need to use the native mysqli driver
            * to leverage Joomla's usage of prepared statements
            * because these do not support particular mySQL commands
            * @var \mysqli $mySQLi
            */
            $mySQLi = $db->getConnection();
            
            // Get the MySQL user currently connected
            $definer = $db->setQuery("SELECT USER();")->loadResult();

            // Escape special characters in definer
            [$user, $host] = explode('@', $definer);
            $escapedDefiner = "`" . $user . "`@`" . $host . "`";
            
            if (method_exists($mySQLi, 'real_query')) {
                $mySQLi->real_query('DROP PROCEDURE IF EXISTS SPGetRelationsPath;');
                $procedure = <<<PROCEDURE
    CREATE DEFINER=$escapedDefiner PROCEDURE SPGetRelationsPath(IN sid INT(20), OUT `returnPath` TEXT)
    BEGIN
    DECLARE parentSid INT DEFAULT 0;
    DECLARE loopResult TEXT DEFAULT NULL;
    DECLARE spResult TEXT DEFAULT NULL;
    DECLARE spRow TEXT DEFAULT NULL;
    DECLARE spParent INT;
    DECLARE spDone INT;
    DECLARE allParents CURSOR FOR SELECT pid FROM {$db->getPrefix()}sobipro_relations WHERE id = sid;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET spDone = 1;
    OPEN allParents;

    getAllParents:
    LOOP
    FETCH allParents INTO spParent;
    IF spDone = 1 THEN LEAVE getAllParents; END IF;
    SET parentSid = spParent;
    SET loopResult = NULL;

    WHILE parentSid IS NOT NULL
    DO
    IF loopResult IS NOT NULL THEN 
    SET loopResult = (SELECT CONCAT(loopResult, ',', parentSid)); 
    ELSE 
    SET loopResult = parentSid; 
    END IF;
    SET parentSid = (SELECT pid FROM {$db->getPrefix()}sobipro_relations WHERE id = parentSid ORDER BY position LIMIT 1);
    END WHILE;

    SET spRow = (SELECT GROUP_CONCAT('{ "id": "', id, '", "type": "', oType, '", "spParent": "', pid, '", "position": "', position, '" }')
    FROM {$db->getPrefix()}sobipro_relations
    WHERE FIND_IN_SET(id, loopResult));

    SET spRow = (SELECT CONCAT(' "', spParent, '": [ ', spRow, ' ] '));
    IF spResult IS NOT NULL THEN 
    SET spResult = (SELECT CONCAT(spResult, ',', spRow)); 
    ELSE 
    SET spResult = spRow; 
    END IF;

    END LOOP;
    CLOSE allParents;
    SET returnPath = (SELECT CONCAT('json://{ ', spResult, ' }'));
    END;
    PROCEDURE;
                $mySQLi->multi_query($procedure);
            }
        }

	/**
	 * Makes all changes to the database according to the SobiPro version installed.
	 *
	 * @return void
	 */
	public function updateDatabase(): void
	{
		if ( self::$installedSobiPro ) {
			/* if installed version between minimumSobiProVersion (from installer file e.g. 1.6.1) and 1.999 */
			if ( version_compare( self::$installedSobiPro->version, "1.999", 'le' )
			) {
				$this->updateDBOnce();  /* update DB when installing from below 2.x */
			}
			$this->updateDB();  /* execute always */
		}

		$this->createProcedures();
	}

	/**
	 * Template for Database alterations during 2.5 to 2.x.
	 */
	protected function updateDB()
	{
		if ( version_compare( self::$installedSobiPro->version, "2.3.99", 'le' )
		) {
			$this->updateDB23();  /* update DB for all changes during 2.0 to 2.3 series of SobiPro */
		}
		if ( version_compare( self::$installedSobiPro->version, "2.5.99", 'le' )
		) {
			$this->updateDB25();    /* update DB for all changes during 2.4 and 2.5 series of SobiPro */
		}

		/* general alterations */
		$db = Factory::getDbo();
		$msg = '';

		try {
			$sections = $db->setQuery( "SELECT * FROM `#__sobipro_object` WHERE `oType` = 'section';" )->loadObjectList();
			if ( is_array( $sections ) && count( $sections ) ) {
				foreach ( $sections as $section ) {
					/* set wildcard on for all sections with no wildcard set */
					$wildcard = $db->setQuery( "SELECT `sValue` FROM `#__sobipro_config` WHERE `sKey` = 'wildcard-search' AND `section` = $section->id;" )->loadResult();
					if ( $wildcard == null ) {
						$db->setQuery( "INSERT IGNORE INTO `#__sobipro_config` ( `sKey`, `sValue`, `section`, `critical`, `cSection` ) VALUES ('wildcard', '1', $section->id, 0, 'search');" )->execute();
					}
					if ( defined( 'SOBI_TRIMMED' ) ) {
						$default = SPC::DEFAULT_TEMPLATE;
						$db->setQuery( "UPDATE `#__sobipro_config` SET `sValue` = '$default' WHERE `sKey` = 'template' AND `section` = $section->id AND `cSection`= 'section' " )->execute();
					}
				}
			}
		}
		catch ( Exception $x ) {
			$msg .= '<p>Failed to ...: ' . $x->getMessage() . '</p>';
		}

		try {
			$db->setQuery( "UPDATE `#__sobipro_registry` SET `params` = 'L14oW1x3MC05LV0rXC4pK1thLXpBLVpdezIsfShcLy4qKT8kLw==' WHERE `section` = 'fields_filter' AND `key` = 'website' " )->execute();
		}
		catch ( Exception $x ) {
			$msg .= '<p>Failed to update filters in table #__sobipro_registry ´: ' . $x->getMessage() . '</p>';
		}

		if ( $msg ) {
			Log::add( $msg, Log::ERROR, 'jerror' );
		}
	}

	/**
	 * Database alterations in 2.4 and 2.5 series of SobiPro
	 */
	protected function updateDB25()
	{
		$db = Factory::getDbo();
		$msg = '';

		/* As read access to the database is faster with MyISAM, especially for larger databases, existing sobipro_field_data and sobipro_language tables will no longer be changed to InnoDB. */
//		try {
//			$db->setQuery( 'ALTER TABLE `#__sobipro_field_data` ENGINE = InnoDB;' )->execute();
//			$db->setQuery( 'ALTER TABLE `#__sobipro_language` ENGINE = InnoDB;' )->execute();
//		}
//		catch ( Exception $x ) {
//			$msg .= '<p>Failed to convert table #__sobipro_field_data or #__sobipro_language to InnoDB: ' . $x->getMessage() . '</p>';
//		}

		try {
			$db->setQuery( "UPDATE `#__sobipro_registry` SET `params` = 'L15bXHdcLi1dKy57MX1bYS16QS1aXXsyLDI0fVtcL117MCwxfSQv' WHERE `section` = 'fields_filter' AND `key` = 'website' " )->execute();
			$db->setQuery( "UPDATE `#__sobipro_registry` SET `params` = 'L15odHRwcz86XC9cL1tcd1wuLV0rXC57MX1bYS16QS1aXXsyLDI0fVtcL117MCwxfSQv' WHERE `section`='fields_filter' AND `key` = 'website_full' " )->execute();
		}
		catch ( Exception $x ) {
			$msg .= '<p>Failed to update filters in table #__sobipro_registry ´: ' . $x->getMessage() . '</p>';
		}

		if ( $msg ) {
			Log::add( $msg, Log::ERROR, 'jerror' );
		}
	}

	/**
	 * Database alterations in 2.0 to 2.3 series of SobiPro
	 */
	protected function updateDB23()
	{
		$db = Factory::getDbo();
		$msg = '';

		try {
			/* make room for IPv6 */
			$db->setQuery( "ALTER TABLE `#__sobipro_errors` CHANGE `errIp` `errIp` VARCHAR(40);" )->execute();
		}
		catch ( Exception $x ) {
			$msg .= '<p>Failed to increase column size in table #__sobipro_errors: ' . $x->getMessage() . '</p>';
		}

		try {
			/* core plugins should also be trigger-able */
			$db->setQuery( "INSERT IGNORE INTO `#__sobipro_plugin_task` (`pid`, `onAction`, `type`) 
				VALUES ('bank_transfer','adm.*', 'payment');"
			)->execute();
			$db->setQuery( "INSERT IGNORE INTO `#__sobipro_plugin_task` (`pid`, `onAction`, `type`) 
				VALUES ('paypal','adm.*', 'payment');"
			)->execute();
		}
		catch ( Exception $x ) {
			$msg .= '<p>Failed to add trigger on core plugins in table #__sobipro_plugin_task: ' . $x->getMessage() . '</p>';
		}

		try {
			/* add the section id to the items */
			$columns = $db->setQuery( "SHOW COLUMNS FROM `#__sobipro_object`" )->loadAssocList( 'Field' );
			if ( !isset( $columns[ 'section' ] ) ) {
				$db->setQuery( "ALTER TABLE `#__sobipro_object` ADD `section` INT(11) DEFAULT 0 COMMENT 'section id' AFTER `params`;" )->execute();
				$db->setQuery( 'UPDATE `#__sobipro_object` SET `section` = 0' )->execute();
			}
		}
		catch ( Exception $x ) {
			$msg .= '<p>Failed to add missing column section into table #__sobipro_object: ' . $x->getMessage() . '</p>';
		}

		try {
			/* limit the index for some tables */
			$db->setQuery( "ALTER TABLE `#__sobipro_crawler` DROP INDEX `url`" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_crawler` ADD UNIQUE KEY `url`(`url`(100))" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_permissions` DROP INDEX `uniquePermission`" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_permissions` ADD UNIQUE KEY `uniquePermission`(`subject`(50), `action`(14), `value`(18), `site`(18))" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_plugins` DROP INDEX `pid`" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_plugins` ADD UNIQUE KEY `pid`(`pid`(35), `type`(65))" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_plugin_task` DROP INDEX `pid`" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_plugin_task` ADD UNIQUE KEY `pid`(`pid`(20), `onAction`(60), `type`(20))" )->execute();
		}
		catch ( Exception $x ) {
			$msg .= '<p>Failed to change index key: ' . $x->getMessage() . '</p>';
		}

		if ( $msg ) {
			Log::add( $msg, Log::ERROR, 'jerror' );
		}
	}

	/**
	 * Database alterations from 1.x version.
	 * Done while upgrade from 1.x to 2.x.
	 */
	protected function updateDBOnce()
	{
		$db = Factory::getDbo();
		$msg = '';

		//$db->setQuery( $db->getAlterDbCharacterSet() )->execute();

		/* Table conversions from 1.x to 2.x */
		/* --------------------------------- */

		/* __sobipro_counter */
		try {
			$db->setQuery( "CREATE TABLE IF NOT EXISTS `#__sobipro_counter` (
`sid` INT(11) NOT NULL,
`counter` INT(11) NOT NULL,
`lastUpdate` TIMESTAMP NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
PRIMARY KEY (`sid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;"
			)->execute();
			$db->setQuery( 'ALTER TABLE `#__sobipro_counter` CONVERT TO CHARACTER SET `utf8mb4` COLLATE `utf8mb4_unicode_ci`;' )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_counter` CHANGE 
`lastUpdate` `lastUpdate` TIMESTAMP NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp();"
			)->execute();
		}
		catch ( Exception $x ) {
			$msg .= '<p>Failed to create or convert table #__sobipro_counter: ' . $x->getMessage() . '</p>';
		}

		/* __sobipro_view_cache */
		try {
			$db->setQuery( "CREATE TABLE IF NOT EXISTS `#__sobipro_view_cache` (
`cid` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'cache id',
`section` INT(11) NOT NULL,
`sid` INT(11) NOT NULL,
`fileName` VARCHAR(100) NOT NULL,
`task` VARCHAR(100) NOT NULL,
`site` INT(11) NOT NULL,
`request` VARCHAR(190) NOT NULL,
`language` VARCHAR(15) NOT NULL,
`template` VARCHAR(150) NOT NULL,
`configFile` TEXT NOT NULL,
`userGroups` VARCHAR(190) NOT NULL,
`created` DATETIME NOT NULL,
PRIMARY KEY (`cid`),
KEY `sid`(`sid`),
KEY `section`(`section`),
KEY `language`(`language`),
KEY `task`(`task`),
KEY `request`(`request`),
KEY `site`(`site`),
KEY `userGroups`(`userGroups`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;"
			)->execute();
			$db->setQuery( 'ALTER TABLE `#__sobipro_view_cache` CONVERT TO CHARACTER SET `utf8mb4` COLLATE `utf8mb4_unicode_ci`;' )->execute();
		}
		catch ( Exception $x ) {
			$msg .= '<p>Failed to create table #__sobipro_view_cache: ' . $x->getMessage() . '</p>';
		}

		/* __sobipro_view_cache_relation */
		try {
			$db->setQuery( "CREATE TABLE IF NOT EXISTS `#__sobipro_view_cache_relation` (
`cid` INT(11) NOT NULL,
`sid` INT(11) NOT NULL,
PRIMARY KEY (`cid`, `sid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;"
			)->execute();
			$db->setQuery( 'ALTER TABLE `#__sobipro_view_cache_relation` CONVERT TO CHARACTER SET `utf8mb4` COLLATE `utf8mb4_unicode_ci`;' )->execute();
		}
		catch ( Exception $x ) {
			$msg .= '<p>Failed to create table #__sobipro_view_cache_relation: ' . $x->getMessage() . '</p>';
		}

		/* __sobipro_crawler */
		try {
			$db->setQuery( "CREATE TABLE IF NOT EXISTS `#__sobipro_crawler` (
`url` VARCHAR(190) NOT NULL,
`crid` INT(11) NOT NULL AUTO_INCREMENT,
`state` TINYINT(1) NOT NULL,
PRIMARY KEY (`crid`),
UNIQUE KEY `url`(`url`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;"
			)->execute();
			$db->setQuery( 'ALTER TABLE `#__sobipro_crawler` CONVERT TO CHARACTER SET `utf8mb4` COLLATE `utf8mb4_unicode_ci`;' )->execute();
		}
		catch ( Exception $x ) {
			$msg .= '<p>Failed to create table #__sobipro_crawler: ' . $x->getMessage() . '</p>';
		}

		/* __sobipro_category */
		$columns = $db->setQuery( "SHOW COLUMNS FROM `#__sobipro_category`" )->loadAssocList( 'Field' );
		try {
			if ( !isset( $columns[ 'allFields' ] ) ) {
				$db->setQuery( "ALTER TABLE `#__sobipro_category` ADD `allFields` TINYINT(2) NOT NULL DEFAULT 1 COMMENT 'all fields are assigned to the category' AFTER `showIcon`, ADD `entryFields` TEXT  COMMENT 'list of fields assigned to the category' AFTER `allFields`;" )->execute();
				$db->setQuery( 'UPDATE `#__sobipro_category` SET `allFields` = 1' )->execute();
			}
			if ( !isset( $columns[ 'section' ] ) ) {
				$db->setQuery( "ALTER TABLE `#__sobipro_category` ADD `section` INT(11) NOT NULL COMMENT 'section id' AFTER `id`;" )->execute();
				$categories = $db->setQuery( 'SELECT `id` FROM `#__sobipro_category` WHERE 1' )->loadColumn();

				if ( is_array( $categories ) && count( $categories ) ) {
					$this->loadSobi();
					foreach ( $categories as $id ) {
						$sid = SPFactory::config()->getParentPathSection( $id );
//						$sid = is_array( $parent ) && count( $parent ) ? $parent[ 0 ] : $id;
						$db->setQuery( "UPDATE `#__sobipro_category` SET `section` = $sid WHERE `id` = $id;" )->execute();
					}
				}
			}
		}
		catch ( Exception $x ) {
			$msg .= '<p>Failed to add missing columns into table #__sobipro_category: ' . $x->getMessage() . '</p>';
		}
		try {
			$db->setQuery( 'ALTER TABLE `#__sobipro_category` CONVERT TO CHARACTER SET `utf8mb4` COLLATE `utf8mb4_unicode_ci`;' )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_category` CHANGE `section` `section` INT(11) NOT NULL COMMENT 'section id'" )->execute();

			if ( isset( $columns[ 'description' ] ) ) {
				$db->setQuery( "ALTER TABLE `#__sobipro_category` CHANGE `description` `param1` TEXT COMMENT 'reserved for future use';" )->execute();
			}
			if ( isset( $columns[ 'introtext' ] ) ) {
				$db->setQuery( "ALTER TABLE `#__sobipro_category` CHANGE `introtext` `param2` TEXT COMMENT 'reserved for future use';" )->execute();
			}
			$db->setQuery( "ALTER TABLE `#__sobipro_category` CHANGE `allFields` `allFields` TINYINT(2) NOT NULL DEFAULT 1 COMMENT 'all fields are assigned to the category';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_category` CHANGE `entryFields` `entryFields` TEXT COMMENT 'list of fields assigned to the category';" )->execute();
		}
		catch ( Exception $x ) {
			$msg .= '<p>Failed to convert table #__sobipro_category: ' . $x->getMessage() . '</p>';
		}

		/* __sobipro_config */
		try {
			$db->setQuery( 'ALTER TABLE `#__sobipro_config` CONVERT TO CHARACTER SET `utf8mb4` COLLATE `utf8mb4_unicode_ci`;' )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_config` CHANGE `sKey` `sKey` VARCHAR(150) NOT NULL COMMENT 'configuration key';" )->execute();

			$db->setQuery( "ALTER TABLE `#__sobipro_config` CHANGE `section` `section` INT(11) NOT NULL DEFAULT 0 COMMENT 'section id'" )->execute();

			$db->setQuery( "ALTER TABLE `#__sobipro_config` CHANGE `critical` `critical` TINYINT(1) DEFAULT 0;" )->execute();

			$db->setQuery( 'DELETE FROM `#__sobipro_config` WHERE `sKey` = "xml_raw" AND `section` = 0;' )->execute();
			$db->setQuery( 'DELETE FROM `#__sobipro_config` WHERE `sKey` = "field_types_for_ordering"' )->execute();

			/* change some settings */
			$sections = $db->setQuery( "SELECT * FROM `#__sobipro_object` WHERE `oType` = 'section';" )->loadObjectList();
			if ( is_array( $sections ) && count( $sections ) ) {
				foreach ( $sections as $section ) {
					$frameworkStyle = $db->setQuery( "SELECT `sValue` FROM `#__sobipro_config` WHERE `sKey` = 'framework-style' AND `section` = $section->id;" )->loadResult();

					if ( $frameworkStyle == 0 ) {   /* check if the new settings aren't already set */
						$bootstrapDisabled = $db->setQuery( "SELECT `sValue` FROM `#__sobipro_config` WHERE `sKey` = 'bootstrap-disabled' AND `section` = $section->id;" )->loadResult();
						$bootstrap3Styles = $db->setQuery( "SELECT `sValue` FROM `#__sobipro_config` WHERE `sKey` = 'bootstrap3-styles' AND `section` = $section->id;" )->loadResult();
						$bootstrap3Load = $db->setQuery( "SELECT `sValue` FROM `#__sobipro_config` WHERE `sKey` = 'bootstrap3-load' AND `section` = $section->id;" )->loadResult();
						$bootstrap3Source = $db->setQuery( "SELECT `sValue` FROM `#__sobipro_config` WHERE `sKey` = 'bootstrap3-source' AND `section` = $section->id;" )->loadResult();

						/* Bootstrap 2:
						'bootstrap3-styles' = 0 => framework-style = 2
						and 'bootstrap-disabled' = 1 => framework-load = 0 (none)
						and 'bootstrap-disabled' = 0 and 'bootstrap3-source' = 1'=> framework-load = 1 (local)
						and 'bootstrap-disabled' = 0 and 'bootstrap3-source' = 0'=> framework-load = 2 (CDN)
						*/
						/* Bootstrap 3:
						'bootstrap3-styles' = 1 => framework-style = 3
						and 'bootstrap3-load' = 0 => framework-load = 0 (none)
						and 'bootstrap3-load' = 1 and 'bootstrap3-source' = 1'=> framework-load = 1 (local)
						and 'bootstrap3-load' = 1 and 'bootstrap3-source' = 0'=> framework-load = 2 (CDN)
						*/
						if ( $bootstrap3Styles ) {
							$frameworkStyle = 3;
							$frameworkLoad = $bootstrap3Load ? ( $bootstrap3Source ? 1 : 2 ) : 0;
						}
						else {
							$frameworkStyle = 2;
							$frameworkLoad = $bootstrapDisabled ? 0 : ( $bootstrap3Source ? 1 : 2 );
						}
						$db->setQuery( "INSERT IGNORE INTO `#__sobipro_config` ( `sKey`, `sValue`, `section`, `critical`, `cSection` ) VALUES ('framework-load', $frameworkLoad, $section->id, 0, 'template');" )->execute();
						$db->setQuery( "INSERT IGNORE INTO `#__sobipro_config` ( `sKey`, `sValue`, `section`, `critical`, `cSection` ) VALUES ('framework-style', $frameworkStyle, $section->id, 0, 'template');" )->execute();
					}
					/* check of Font Awesome 3 local is selected */
					$font = $db->setQuery( "SELECT `sValue` FROM `#__sobipro_config` WHERE `sKey` = 'icon_fonts_arr' AND `section` = $section->id;" )->loadResult();
					if ( $font ) {
						$font = SPConfig::unserialize( $font );
						foreach ( $font as $i => $item ) {
							if ( $item == 'font-awesome-3-local' ) {
								$font[ $i ] = 'font-awesome-3';
							}
						}
						$font = SPConfig::serialize( $font );
						$db->setQuery( "INSERT IGNORE INTO `#__sobipro_config` ( `sKey`, `sValue`, `section`, `critical`, `cSection` ) VALUES ('icon_fonts_arr', '$font', $section->id, 0, 'template');" )->execute();
					}

					/* transfer the redirect Urls to the language table */
					$redirectSectionUrl = $db->setQuery( "SELECT `sValue` FROM `#__sobipro_config` WHERE `sKey` = 'section_access_url' AND `section` = $section->id;" )->loadResult();
					$redirectCategoryUrl = $db->setQuery( "SELECT `sValue` FROM `#__sobipro_config` WHERE `sKey` = 'category_access_url' AND `section` = $section->id;" )->loadResult();
					$redirectEntryUrl = $db->setQuery( "SELECT `sValue` FROM `#__sobipro_config` WHERE `sKey` = 'entry_access_url' AND `section` = $section->id;" )->loadResult();
					$redirectEntryAddUrl = $db->setQuery( "SELECT `sValue` FROM `#__sobipro_config` WHERE `sKey` = 'entry_add_url' AND `section` = $section->id;" )->loadResult();
					$redirectEntrySaveUrl = $db->setQuery( "SELECT `sValue` FROM `#__sobipro_config` WHERE `sKey` = 'entry_save_url' AND `section` = $section->id;" )->loadResult();
					$redirectSearchUrl = $db->setQuery( "SELECT `sValue` FROM `#__sobipro_config` WHERE `sKey` = 'section_search_url' AND `section` = $section->id;" )->loadResult();
					$db->setQuery( "INSERT IGNORE INTO `#__sobipro_language`( `sKey`, `sValue`, `section`, `language`, `oType`, `fid`, `id`, `params`, `options`, `explanation` ) VALUES
( 'redirectSectionUrl', '$redirectSectionUrl', 0, 'en-GB',  'section', 0, $section->id, '', '', '' ),
( 'redirectCategoryUrl', '$redirectCategoryUrl', 0, 'en-GB', 'section', 0, $section->id, '', '', '' ),
( 'redirectEntryUrl', '$redirectEntryUrl', 0, 'en-GB',  'section', 0, $section->id, '', '', '' ),
( 'redirectEntryAddUrl', '$redirectEntryAddUrl', 0, 'en-GB',  'section', 0, $section->id, '', '', '' ),
( 'redirectEntrySaveUrl', '$redirectEntrySaveUrl', 0, 'en-GB',  'section', 0, $section->id, '', '', '' ),
( 'redirectSearchUrl', '$redirectSearchUrl', 0, 'en-GB',  'section', 0, $section->id, '', '', '' );"
					)->execute();

					/* transfer the filter messages to the language table */
					$filters = $db->setQuery( "SELECT `key`, `description` FROM `#__sobipro_registry` WHERE `section` = 'fields_filter';" )->loadObjectList();
					if ( is_array( $filters ) && count( $filters ) ) {
						foreach ( $filters as $filter ) {
							if ( $filter->description ) {
								$filter->key = 'filter-' . $filter->key;
								$db->setQuery( "INSERT IGNORE INTO `#__sobipro_language`( `sKey`, `sValue`, `section`, `language`, `oType`, `fid`, `id`, `params`, `options`, `explanation` ) VALUES 
								( '$filter->key', '$filter->description', 0, 'en-GB', 'fields_filter', 0, 0, '', '', '');"
								)->execute();
							}
						}
					}
				}
			}
		}
		catch ( Exception $x ) {
			$msg .= '<p>Failed to convert table #__sobipro_config: ' . $x->getMessage() . '</p>';
		}

		/* __sobipro_field */
		try {
			$db->setQuery( 'ALTER TABLE `#__sobipro_field` CONVERT TO CHARACTER SET `utf8mb4` COLLATE `utf8mb4_unicode_ci`;' )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field` CHANGE `adminField` `adminField` TINYINT(1) DEFAULT 0 COMMENT 'classifies a field to be shown publicly, for admin only or as a category field';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field` CHANGE `admList` `admList` INT(10) DEFAULT 0 COMMENT 'field shown in entry list';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field` CHANGE `dataType` `dataType` INT(11) DEFAULT 0;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field` CHANGE `enabled` `enabled` TINYINT(1) DEFAULT 0;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field` CHANGE `fee` `fee` DOUBLE DEFAULT 0;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field` CHANGE `fieldType` `fieldType` VARCHAR(50) NOT NULL;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field` CHANGE `isFree` `isFree` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=field is for free';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field` CHANGE `priority` `priority` INT(11) NOT NULL DEFAULT 5 COMMENT 'search priority';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field` CHANGE `required` `required` TINYINT(1) DEFAULT 0;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field` CHANGE `section` `section` INT(11) NOT NULL COMMENT 'section id';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field` CHANGE `multiLang` `multiLang` TINYINT(4) DEFAULT 0" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field` CHANGE `uniqueData` `uniqueData` TINYINT(1) DEFAULT 0;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field` CHANGE `validate` `validate` TINYINT(1) DEFAULT 0;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field` CHANGE `addToMetaDesc` `addToMetaDesc` TINYINT(1) DEFAULT 0;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field` CHANGE `addToMetaKeys` `addToMetaKeys` TINYINT(1) DEFAULT 0;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field` CHANGE `editLimit` `editLimit` INT(11) DEFAULT -1" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field` CHANGE `editable` `editable` TINYINT(4) DEFAULT 1;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field` CHANGE `allowedAttributes` `allowedAttributes` TEXT;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field` CHANGE `allowedTags` `allowedTags` TEXT;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field` CHANGE `editor` `editor` VARCHAR(190) COMMENT 'use WYSIWYG editor';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field` CHANGE `inSearch` `inSearch` TINYINT(4) DEFAULT 0 COMMENT 'searchable';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field` CHANGE `withLabel` `withLabel` TINYINT(4) DEFAULT 1;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field` CHANGE `cssClass` `cssClass` VARCHAR(50);" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field` CHANGE `parse` `parse` TINYINT(4) DEFAULT 0;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field` CHANGE `template` `template` VARCHAR(190) COMMENT 'reserved for future use';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field` CHANGE `notice` `notice` TEXT COMMENT 'administrator notes';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field` CHANGE `params` `params` TEXT COMMENT 'additional parameters';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field` CHANGE `defaultValue` `defaultValue` TEXT;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field` CHANGE `version` `version` INT(11) NOT NULL DEFAULT 1;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field` CHANGE `editLimit` `editLimit` INT(11) DEFAULT -1;" )->execute();
		}
		catch ( Exception $x ) {
			$msg .= '<p>Failed to convert table #__sobipro_field: ' . $x->getMessage() . '</p>';
		}

		/* __sobipro_field_data */
		try {
			$db->setQuery( 'ALTER TABLE `#__sobipro_field_data` CONVERT TO CHARACTER SET `utf8mb4` COLLATE `utf8mb4_unicode_ci`;' )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field_data` CHANGE `publishUp` `publishUp` DATETIME DEFAULT '0000-00-00 00:00:00' COMMENT 'reserved for future use';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field_data` CHANGE `publishDown` `publishDown` DATETIME DEFAULT '0000-00-00 00:00:00' COMMENT 'reserved for future use';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field_data` CHANGE `fid` `fid` INT(11) NOT NULL DEFAULT 0 COMMENT 'field id';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field_data` CHANGE `sid` `sid` INT(11) NOT NULL DEFAULT 0 COMMENT 'entry/category id';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field_data` CHANGE `section` `section` INT(11) NOT NULL DEFAULT 0 COMMENT 'section id';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field_data` CHANGE `lang` `lang` VARCHAR(50) DEFAULT 'en-GB';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field_data` CHANGE `enabled` `enabled` TINYINT(1) NOT NULL DEFAULT 0;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field_data` CHANGE `approved` `approved` TINYINT(1) DEFAULT 0;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field_data` CHANGE `confirmed` `confirmed` TINYINT(1) DEFAULT 0;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field_data` CHANGE `createdTime` `createdTime` DATETIME DEFAULT '0000-00-00 00:00:00';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field_data` CHANGE `createdBy` `createdBy` INT(11) DEFAULT 0;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field_data` CHANGE `createdIP` `createdIP` VARCHAR(40) DEFAULT '000.000.000.000';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field_data` CHANGE `updatedTime` `updatedTime` DATETIME DEFAULT '0000-00-00 00:00:00';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field_data` CHANGE `updatedBy` `updatedBy` INT(11) DEFAULT 0;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field_data` CHANGE `updatedIP` `updatedIP` VARCHAR(40) DEFAULT '000.000.000.000';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field_data` CHANGE `copy` `copy` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=unapproved version of field data';" )->execute();

			$db->setQuery( 'ALTER TABLE `#__sobipro_field_data` CHANGE `baseData` `baseData` LONGTEXT CHARACTER SET `utf8mb4` COLLATE `utf8mb4_unicode_ci`;' )->execute();

			$cols = $db->setQuery( 'SHOW INDEX FROM  `#__sobipro_field_data`' )->loadAssocList( 'Key_name' );
			if ( !isset( $cols[ 'baseData' ] ) ) {
				try {
					$db->setQuery( 'ALTER TABLE `#__sobipro_field_data` ENGINE = MyISAM;' )->execute();
					$db->setQuery( 'ALTER TABLE  `#__sobipro_field_data` ADD FULLTEXT `baseData` (`baseData`);' )->execute();
				}
				catch ( Exception $x ) {
					$msg .= '<p>Failed to convert table #__sobipro_field_data to MyISAM: ' . $x->getMessage() . '</p>';
				}
			}
			$col = $db->setQuery( "SHOW COLUMNS FROM `#__sobipro_field_data` LIKE 'editLimit'" )->loadResult();
			if ( $col != 'editLimit' ) {
				$db->setQuery( 'ALTER TABLE  `#__sobipro_field_data` ADD `editLimit` INT(11) DEFAULT -1;' )->execute();
			}
		}
		catch ( Exception $x ) {
			$msg .= '<p>Failed to convert table #__sobipro_field_data: ' . $x->getMessage() . '</p>';
		}

		/* __sobipro_field_option */
		try {
			$db->setQuery( 'ALTER TABLE `#__sobipro_field_option` CONVERT TO CHARACTER SET `utf8mb4` COLLATE `utf8mb4_unicode_ci`;' )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field_option` CHANGE `img` `img` VARCHAR(150);" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field_option` CHANGE `optClass` `optClass` VARCHAR(50);" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field_option` CHANGE `actions` `actions` TEXT;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field_option` CHANGE `class` `class` TEXT;" )->execute();
			$db->setQuery( "UPDATE #__sobipro_field_option_selected SET `optValue` = REPLACE (`optValue`, '_', '-')" )->execute();  /* 1.1 */
			$db->setQuery( "UPDATE #__sobipro_field_option SET `optValue` = REPLACE (`optValue`, '_', '-')" )->execute();   /* 1.1 */
		}
		catch ( Exception $x ) {
			$msg .= '<p>Failed to convert table #__sobipro_field_option: ' . $x->getMessage() . '</p>';
		}

		/* __sobipro_field_option_selected */
		try {
			$db->setQuery( 'ALTER TABLE `#__sobipro_field_option_selected` CONVERT TO CHARACTER SET `utf8mb4` COLLATE `utf8mb4_unicode_ci`;' )->execute();
			$db->setQuery( 'ALTER TABLE `#__sobipro_field_option_selected` CHANGE `params` `params` MEDIUMTEXT;' )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field_option_selected` CHANGE `copy` `copy` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = unapproved version of field data';" )->execute();
		}
		catch ( Exception $x ) {
			$msg .= '<p>Failed to convert table #__sobipro_field_option_selected: ' . $x->getMessage() . '</p>';
		}

		/* __sobipro_field_types */
		try {
			$db->setQuery( 'ALTER TABLE `#__sobipro_field_types` CONVERT TO CHARACTER SET `utf8mb4` COLLATE `utf8mb4_unicode_ci`;' )->execute();
			$db->setQuery( "INSERT IGNORE INTO `#__sobipro_field_types` (`tid`, `fType`, `tGroup`, `fPos`) VALUES ('category', 'Category', 'special', 11);" )->execute();
			$db->setQuery( "INSERT IGNORE INTO `#__sobipro_field_types` (`tid`, `fType`, `tGroup`, `fPos`) VALUES ('info', 'Information', 'free_single_simple_data', 6);" )->execute();
			$db->setQuery( "INSERT IGNORE INTO `#__sobipro_field_types` (`tid`, `fType`, `tGroup`, `fPos`) VALUES ('button', 'Button', 'special', 5);" )->execute();
		}
		catch ( Exception $x ) {
			$msg .= '<p>Failed to convert table #__sobipro_field_types: ' . $x->getMessage() . '</p>';
		}

		/* __sobipro_field_url_clicks */
		try {
			$db->setQuery( "CREATE TABLE IF NOT EXISTS `#__sobipro_field_url_clicks` (
`date` DATETIME NOT NULL,
`uid` INT(11) NOT NULL,
`sid` INT(11) NOT NULL,
`fid` VARCHAR(50) NOT NULL,
`ip` VARCHAR(40) NOT NULL,
`section` INT(11) NOT NULL,
`browserData` TEXT,
`osData` TEXT,
`humanity` INT(3) NOT NULL,
PRIMARY KEY (`date`, `sid`, `fid`, `ip`, `section`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;"
			)->execute();
			$db->setQuery( 'ALTER TABLE `#__sobipro_field_url_clicks` CONVERT TO CHARACTER SET `utf8mb4` COLLATE `utf8mb4_unicode_ci`;' )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field_url_clicks` CHANGE `ip` `ip` VARCHAR(40) NOT NULL;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field_url_clicks` CHANGE `browserData` `browserData` TEXT;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_field_url_clicks` CHANGE `osData` `osData` TEXT;" )->execute();
		}
		catch ( Exception $x ) {
			$msg .= '<p>Failed to convert table #__sobipro_field_types: ' . $x->getMessage() . '</p>';
		}

		/* __sobipro_language */
		$cols = $db->setQuery( 'SHOW INDEX FROM  #__sobipro_language' )->loadAssocList( 'Key_name' );
		if ( !isset( $cols[ 'sValue' ] ) ) {
			try {
				$db->setQuery( 'ALTER TABLE `#__sobipro_language` ENGINE = MyISAM;' )->execute();
				$db->setQuery( 'ALTER TABLE `#__sobipro_language` ADD FULLTEXT  `sValue` (`sValue`);' )->execute();
			}
			catch ( Exception $x ) {
				$msg .= '<p>Failed to convert table #__sobipro_language to MyISAM: ' . $x->getMessage() . '</p>';
			}
		}
		try {
			$db->setQuery( 'ALTER TABLE `#__sobipro_language` CONVERT TO CHARACTER SET `utf8mb4` COLLATE `utf8mb4_unicode_ci`;' )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_language` CHANGE `sKey` `sKey` VARCHAR(150) NOT NULL COMMENT 'object key';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_language` CHANGE `sValue` `sValue` MEDIUMTEXT COMMENT 'object value';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_language` CHANGE `section` `section` INT(11) NOT NULL DEFAULT 0;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_language` CHANGE `language` `language` VARCHAR(50) NOT NULL DEFAULT 'en-GB';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_language` CHANGE `oType` `oType` VARCHAR(150) NOT NULL COMMENT 'object type';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_language` CHANGE `sKey` `sKey` VARCHAR(150) NOT NULL COMMENT 'object key';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_language` CHANGE `sKey` `sKey` VARCHAR(150) NOT NULL COMMENT 'object key';" )->execute();
			$db->setQuery( 'ALTER TABLE `#__sobipro_language` CHANGE `params` `params` LONGTEXT;' )->execute();
			$db->setQuery( 'ALTER TABLE `#__sobipro_language` CHANGE `options` `options` LONGTEXT;' )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_language` CHANGE `sKey` `sKey` VARCHAR(150) NOT NULL COMMENT 'object key';" )->execute();
			$db->setQuery( 'ALTER TABLE `#__sobipro_language` CHANGE `explanation` `explanation` LONGTEXT;' )->execute();
			$db->setQuery( "INSERT IGNORE INTO `#__sobipro_language`( `sKey`, `sValue`, `section`, `language`, `oType`, `fid`, `id`, `params`, `options`, `explanation` )
VALUES
( 'rejection-of-a-new-entry', 'The entry {entry.name} has been rejected as it does not comply with the rules.\n\nRejected by {user.name} at {date%d F Y H:i:s}.\n', 0, 'en-GB',
  'rejections-templates', 0, 1, '', '', '' ),
( 'rejection-of-changes', 'The changes in the entry {entry.name} has been discarded as they are violating our rules.\n\nRejected by {user.name} at {date%d F Y H:i:s}.\n', 0,
  'en-GB', 'rejections-templates', 0, 1, '', '', '' );"
			)->execute();
			$db->setQuery( "UPDATE `#__sobipro_language` SET `sKey` = REPLACE(  `sKey` ,  '_',  '-' ) WHERE `oType` =  'field_option'" )->execute();
		}
		catch ( Exception $x ) {
			$msg .= '<p>Failed to convert table #__sobipro_language: ' . $x->getMessage() . '</p>';
		}

		/* __sobipro_object */
		try {
			$db->setQuery( 'ALTER TABLE `#__sobipro_object` CONVERT TO CHARACTER SET `utf8mb4` COLLATE `utf8mb4_unicode_ci`;' )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_object` CHANGE `approved` `approved` TINYINT(1) NOT NULL;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_object` CHANGE `confirmed` `confirmed` TINYINT(1) DEFAULT 0;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_object` CHANGE `counter` `counter` INT(11) NOT NULL DEFAULT 0;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_object` CHANGE `cout` `cout` INT(11) DEFAULT 0;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_object` CHANGE `coutTime` `coutTime` DATETIME DEFAULT '0000-00-00 00:00:00';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_object` CHANGE `createdTime` `createdTime` DATETIME DEFAULT '0000-00-00 00:00:00';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_object` CHANGE `metaAuthor` `metaAuthor` VARCHAR(150) NOT NULL DEFAULT '';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_object` CHANGE `metaRobots` `metaRobots` VARCHAR(150) NOT NULL DEFAULT '';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_object` CHANGE `oType` `oType` VARCHAR(150) NOT NULL COMMENT 'object type';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_object` CHANGE `owner` `owner` INT(11) DEFAULT 0;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_object` CHANGE `ownerIP` `ownerIP` VARCHAR(40) DEFAULT '000.000.000.000';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_object` CHANGE `parent` `parent` INT(11) DEFAULT 0;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_object` CHANGE `stateExpl` `stateExpl` TEXT;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_object` CHANGE `updatedTime` `updatedTime` DATETIME DEFAULT '0000-00-00 00:00:00';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_object` CHANGE `updater` `updater` INT(11);" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_object` CHANGE `updaterIP` `updaterIP` VARCHAR(40);" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_object` CHANGE `validUntil` `validUntil` DATETIME;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_object` CHANGE `version` `version` INT(11) NOT NULL DEFAULT 1;" )->execute();
		}
		catch ( Exception $x ) {
			$msg .= '<p>Failed to convert table #__sobipro_object: ' . $x->getMessage() . '</p>';
		}

		/* __sobipro_payments */
		try {
			$db->setQuery( "ALTER TABLE `#__sobipro_payments` CHANGE `datePaid` `datePaid` DATETIME DEFAULT '0000-00-00 00:00:00';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_payments` CHANGE `validUntil` `validUntil` DATETIME DEFAULT '0000-00-00 00:00:00';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_payments` CHANGE `paid` `paid` TINYINT(4) DEFAULT 0;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_payments` CHANGE `params` `params` TEXT;" )->execute();
		}
		catch ( Exception $x ) {
			$msg .= '<p>Failed to convert table #__sobipro_payments: ' . $x->getMessage() . '</p>';
		}

		/* __sobipro_permissions */
		try {
			$db->setQuery( 'ALTER TABLE `#__sobipro_permissions` CONVERT TO CHARACTER SET `utf8mb4` COLLATE `utf8mb4_unicode_ci`;' )->execute();
			$cols = $db->setQuery( 'SHOW INDEX FROM `#__sobipro_permissions`' )->loadAssocList( 'Key_name' );

			/*			$cols2 = $db->setQuery( "SHOW KEYS FROM tablename WHERE Key_name = 'uniquePermission'" )->execute();*/

			if ( !isset( $cols[ 'uniquePermission' ] ) ) {
				$db->setQuery( 'ALTER TABLE `#__sobipro_permissions` ADD UNIQUE  `uniquePermission` (  `subject` ,  `action` ,  `value` ,  `site` );' )->execute();
			}
			$db->setQuery( "ALTER TABLE `#__sobipro_permissions` CHANGE `subject` `subject` VARCHAR(150) NOT NULL;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_permissions` CHANGE `action` `action` VARCHAR(50) NOT NULL;" )->execute();
		}
		catch ( Exception $x ) {
			$msg .= '<p>Failed to convert table #__sobipro_permissions: ' . $x->getMessage() . '</p>';
		}

		/* __sobipro_permissions_rules */
		try {
			$db->setQuery( 'ALTER TABLE `#__sobipro_permissions_rules` CONVERT TO CHARACTER SET `utf8mb4` COLLATE `utf8mb4_unicode_ci`;' )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_permissions_rules` CHANGE `validSince` `validSince` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_permissions_rules` CHANGE `validUntil` `validUntil` DATETIME;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_permissions_rules` CHANGE `note` `note` TEXT;" )->execute();

			$db->setQuery( "UPDATE `#__sobipro_permissions` SET `value` =  '*' WHERE  `pid` = 18;" )->execute();
			$db->setQuery( 'DELETE FROM `#__sobipro_permissions` WHERE `pid` = 5;' )->execute();
			$db->setQuery( "INSERT IGNORE INTO `#__sobipro_permissions`( `pid`, `subject`, `action`, `value`, `site`, `published` )
VALUES
( 86, 'entry', '*', '*', 'adm', 1 ),
( 87, 'category', '*', '*', 'adm', 1 ),
( 88, 'section', '*', '*', 'adm', 1 ),
( 89, 'section', 'access', '*', 'adm', 1 ),
( 90, 'section', 'configure', '*', 'adm', 1 ),
( 91, 'section', 'delete', '*', 'adm', 0 ),
( 92, 'category', 'edit', '*', 'adm', 1 ),
( 93, 'category', 'add', '*', 'adm', 1 ),
( 94, 'category', 'delete', '*', 'adm', 1 ),
( 95, 'entry', 'edit', '*', 'adm', 1 ),
( 96, 'entry', 'add', '*', 'adm', 1 ),
( 97, 'entry', 'delete', '*', 'adm', 1 ),
( 98, 'entry', 'approve', '*', 'adm', 1 ),
( 99, 'entry', 'publish', '*', 'adm', 1 );"
			)->execute();
			$db->setQuery( "INSERT IGNORE INTO `#__sobipro_permissions`( `pid`, `subject`, `action`, `value`, `site`, `published` )
VALUES ( NULL, 'section', 'search', '*', 'front', 1 ),
( NULL, 'entry', 'delete', 'own', 'front', 1 ),
( NULL, 'entry', 'delete', '*', 'front', 1 ),
( NULL, 'entry', 'manage', 'own', 'front', 1 ),
( NULL, 'entry', 'access', 'expired_own', 'front', 1 ),
( NULL, 'entry', 'access', 'expired_any', 'front', 1 );"
			)->execute();
		}
		catch ( Exception $x ) {
			$msg .= '<p>Failed to convert table #__sobipro_permissions_rules: ' . $x->getMessage() . '</p>';
		}

		/* __sobipro_plugins */
		try {
			$db->setQuery( 'ALTER TABLE `#__sobipro_plugins` CONVERT TO CHARACTER SET `utf8mb4` COLLATE `utf8mb4_unicode_ci`;' )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_plugins` CHANGE `enabled` `enabled` TINYINT(1) NOT NULL DEFAULT 0;" )->execute();

			$db->setQuery( "INSERT IGNORE INTO `#__sobipro_plugins` (`pid`, `name`, `version`, `description`, `author`, `authorURL`, `authorMail`, `enabled`, `type`, `depend`) 
VALUES 
( 'category', 'Category', '2.0', NULL, 'Sigsiu.NET GmbH', 'https://www.sigsiu.net/', 'sobi@sigsiu.net', 1, 'field', '' );"
			)->execute();
			$db->setQuery( "INSERT IGNORE INTO `#__sobipro_plugins` (`pid`, `name`, `version`, `description`, `author`, `authorURL`, `authorMail`, `enabled`, `type`, `depend`) 
VALUES 
( 'info', 'Information', '2.0', NULL, 'Sigsiu.NET GmbH', 'https://www.sigsiu.net/', 'sobi@sigsiu.net', 1, 'field', '' );"
			)->execute();
			$db->setQuery( "INSERT IGNORE INTO `#__sobipro_plugins` (`pid`, `name`, `version`, `description`, `author`, `authorURL`, `authorMail`, `enabled`, `type`, `depend`) 
VALUES 
( 'button', 'Button', '2.0', NULL, 'Sigsiu.NET GmbH', 'https://www.sigsiu.net/', 'sobi@sigsiu.net', 1, 'field', '' );"
			)->execute();
		}
		catch ( Exception $x ) {
			$msg .= '<p>Failed to convert table #__sobipro_plugins: ' . $x->getMessage() . '</p>';
		}

		/* __sobipro_plugin_section */
		try {
			$db->setQuery( 'ALTER TABLE `#__sobipro_plugin_section` CONVERT TO CHARACTER SET `utf8mb4` COLLATE `utf8mb4_unicode_ci`;' )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_plugin_section` CHANGE `section` `section` INT(11) NOT NULL COMMENT 'section id';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_plugin_section` CHANGE `pid` `pid` VARCHAR(50) NOT NULL COMMENT 'application id';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_plugin_section` CHANGE `enabled` `enabled` TINYINT(1) DEFAULT 0;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_plugin_section` CHANGE `position` `position` INT(11) DEFAULT 0;" )->execute();
		}
		catch ( Exception $x ) {
			$msg .= '<p>Failed to convert table #__sobipro_plugin_section: ' . $x->getMessage() . '</p>';
		}

		/* __sobipro_plugin_task */
		try {
			$db->setQuery( 'ALTER TABLE `#__sobipro_plugin_task` CONVERT TO CHARACTER SET `utf8mb4` COLLATE `utf8mb4_unicode_ci`;' )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_plugin_task` CHANGE `pid` `pid` VARCHAR(50) NOT NULL COMMENT 'application id';" )->execute();
		}
		catch ( Exception $x ) {
			$msg .= '<p>Failed to convert table #__sobipro_plugin_task: ' . $x->getMessage() . '</p>';
		}

		/* __sobipro_registry */
		try {
			$db->setQuery( 'ALTER TABLE `#__sobipro_registry` CONVERT TO CHARACTER SET `utf8mb4` COLLATE `utf8mb4_unicode_ci`;' )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_registry` CHANGE `params` `params` TEXT;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_registry` CHANGE `description` `description` TEXT;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_registry` CHANGE `options` `options` TEXT;" )->execute();

			$db->setQuery( "INSERT IGNORE INTO `#__sobipro_registry` (`section`, `key`, `value`, `params`, `description`, `options`) 
VALUES 
( 'rejections-templates',
  'rejection-of-a-new-entry',
  'Rejection of a new entry',
  'YTo0OntzOjE3OiJ0cmlnZ2VyLnVucHVibGlzaCI7YjoxO3M6MTc6InRyaWdnZXIudW5hcHByb3ZlIjtiOjA7czo5OiJ1bnB1Ymxpc2giO2I6MTtzOjc6ImRpc2NhcmQiO2I6MDt9',
  '',
  '' ),
( 'rejections-templates',
  'rejection-of-changes',
  'Rejection of changes',
  'YTo0OntzOjE3OiJ0cmlnZ2VyLnVucHVibGlzaCI7YjowO3M6MTc6InRyaWdnZXIudW5hcHByb3ZlIjtiOjE7czo5OiJ1bnB1Ymxpc2giO2I6MDtzOjc6ImRpc2NhcmQiO2I6MTt9',
  '',
  '' );"
			)->execute();

			$db->setQuery( "UPDATE `#__sobipro_registry` SET `params` = 'L15bXHdcLi1dK0BbXHdcLi1dK1wuW2EtekEtWl17MiwyNH0kLw==' WHERE `key` = 'email' " )->execute();
		}
		catch ( Exception $x ) {
			$msg .= '<p>Failed to convert table #__sobipro_registry: ' . $x->getMessage() . '</p>';
		}

		/* __sobipro_relations */
		try {
			$db->setQuery( 'ALTER TABLE `#__sobipro_relations` CONVERT TO CHARACTER SET `utf8mb4` COLLATE 			
`utf8mb4_unicode_ci`;'
			)->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_relations` CHANGE `oType` `oType` VARCHAR(150) NOT NULL COMMENT 'object type'" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_relations` CHANGE `position` `position` INT(11) DEFAULT 0;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_relations` CHANGE `validSince` `validSince` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_relations` CHANGE `validUntil` `validUntil` DATETIME;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_relations` CHANGE `copy` `copy` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=unapproved version of relation';" )->execute();
		}
		catch ( Exception $x ) {
			$msg .= '<p>Failed to convert table #__sobipro_relations: ' . $x->getMessage() . '</p>';
		}

		/* __sobipro_search */
		try {
			$db->setQuery( 'ALTER TABLE `#__sobipro_search` CONVERT TO CHARACTER SET `utf8mb4` COLLATE `utf8mb4_unicode_ci`;' )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_search` CHANGE `ssid` `ssid` VARCHAR(50) NOT NULL;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_search` CHANGE `requestData` `requestData` TEXT;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_search` CHANGE `uid` `uid` INT(11) DEFAULT 0;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_search` CHANGE `browserData` `browserData` TEXT;" )->execute();
			$db->setQuery( 'ALTER TABLE `#__sobipro_search` CHANGE `entriesResults` `entriesResults` LONGTEXT;' )->execute();
			$db->setQuery( 'ALTER TABLE `#__sobipro_search` CHANGE `catsResults` `catsResults` MEDIUMTEXT;' )->execute();
		}
		catch ( Exception $x ) {
			$msg .= '<p>Failed to convert table #__sobipro_search: ' . $x->getMessage() . '</p>';
		}

		/* __sobipro_section */
		try {
			$db->setQuery( 'ALTER TABLE `#__sobipro_section` CONVERT TO CHARACTER SET `utf8mb4` COLLATE 
`utf8mb4_unicode_ci`;'
			)->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_section` CHANGE `id` `id` INT(11) NOT NULL COMMENT 'reserved for future use';" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_section` CHANGE `description` `description` TEXT COMMENT 'reserved for future use';" )->execute();
		}
		catch ( Exception $x ) {
			$msg .= '<p>Failed to convert table #__sobipro_section: ' . $x->getMessage() . '</p>';
		}

		/* __sobipro_users_relation */
		try {
			$db->setQuery( 'ALTER TABLE `#__sobipro_users_relation` CONVERT TO CHARACTER SET `utf8mb4` COLLATE 
`utf8mb4_unicode_ci`;'
			)->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_users_relation` CHANGE `uid` `uid` INT(11) NOT NULL;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_users_relation` CHANGE `gid` `gid` INT(11) NOT NULL;" )->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_users_relation` CHANGE `validSince` `validSince` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00';" )->execute();
		}
		catch ( Exception $x ) {
			$msg .= '<p>Failed to convert table #__sobipro_users_relation: ' . $x->getMessage() . '</p>';
		}

		/* __sobipro_user_group */
		try {
			$db->setQuery( "CREATE TABLE IF NOT EXISTS `#__sobipro_user_group` (
`description` TEXT,
`gid` INT(11) NOT NULL AUTO_INCREMENT,
`enabled` INT(11) DEFAULT 0,
`pid` INT(11) NOT NULL,
`groupName` VARCHAR(150) NOT NULL,
PRIMARY KEY (`gid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci AUTO_INCREMENT = 5000;"
			)->execute();
			$db->setQuery( 'ALTER TABLE `#__sobipro_user_group` CONVERT TO CHARACTER SET `utf8mb4` COLLATE 
`utf8mb4_unicode_ci`;'
			)->execute();
			$db->setQuery( "ALTER TABLE `#__sobipro_user_group` CHANGE `groupName` `groupName` VARCHAR(150) NOT NULL;" )->execute();
		}
		catch ( Exception $x ) {
			$msg .= '<p>Failed to convert table #__sobipro_user_group: ' . $x->getMessage() . '</p>';
		}

		/* __sobipro_history */
		try {
			$db->setQuery( "CREATE TABLE IF NOT EXISTS `#__sobipro_history` (
`revision` VARCHAR(150) NOT NULL,
`changedAt` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
`uid` INT(11) DEFAULT 0,
`userName` VARCHAR(150),
`userEmail` VARCHAR(150),
`changeAction` VARCHAR(150) NOT NULL,
`site` ENUM ('site','adm') NOT NULL,
`sid` INT(11) NOT NULL,
`section` INT(11) DEFAULT 0,
`changes` TEXT,
`params` TEXT,
`reason` TEXT,
`language` VARCHAR(50) NOT NULL DEFAULT 'en-GB',
PRIMARY KEY (`revision`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;"
			)->execute();

			/* rename column `change` to `changeAction` */
			$col = $db->setQuery( "SHOW COLUMNS FROM  `#__sobipro_history`  LIKE 'changeAction'" )->loadResult();
			if ( $col != 'changeAction' ) {
				$db->setQuery( 'ALTER TABLE `#__sobipro_history` CHANGE `change` `changeAction` VARCHAR(150) NOT NULL;' )->execute();
			}
			/* add column 'type' and fill with 'entry' */
			$col = $db->setQuery( "SHOW COLUMNS FROM `#__sobipro_history` LIKE 'type'" )->loadResult();
			if ( $col != 'type' ) {
				$db->setQuery( "ALTER TABLE `#__sobipro_history` ADD `type` VARCHAR(80) NOT NULL AFTER `userEmail`;" )->execute();
				$db->setQuery( "UPDATE `#__sobipro_history` SET `type` = 'entry' WHERE `type` = '';" )->execute();

				/* change name of some changeAction actions */
				$db->setQuery( "UPDATE `#__sobipro_history` SET `changeAction` = 'publish' WHERE `changeAction` = 'published';" )->execute();
				$db->setQuery( "UPDATE `#__sobipro_history` SET `changeAction` = 'unpublish' WHERE `changeAction` = 'unpublished';" )->execute();
				$db->setQuery( "UPDATE `#__sobipro_history` SET `changeAction` = 'approve' WHERE `changeAction` = 'approved';" )->execute();
				$db->setQuery( "UPDATE `#__sobipro_history` SET `changeAction` = 'unapprove' WHERE `changeAction` = 'unapproved';" )->execute();
			}

			/* add column 'section' */
			$col = $db->setQuery( "SHOW COLUMNS FROM `#__sobipro_history` LIKE 'section'" )->loadResult();
			if ( $col != 'section' ) {
				$db->setQuery( "ALTER TABLE `#__sobipro_history` ADD `section` INT(11) DEFAULT 0 AFTER `sid`;" )->execute();
			}
		}
		catch ( Exception $x ) {
			$msg .= '<p>Failed to create or convert table #__sobipro_history: ' . $x->getMessage() . '</p>';
		}

		if ( $msg ) {
			Log::add( $msg, Log::ERROR, 'jerror' );
		}
		else {
			$msg = '<p>The database tables were converted successfully.</p>';
			Log::add( $msg, Log::INFO, 'jerror' );
		}
	}

	/**
	 * @throws \Exception
	 */
	protected function loadSobi(): void
	{
		if ( !self::$sobiLoaded ) {
			define( 'SOBIPRO', true );
			$sobiRoot = JPATH_ROOT . '/components/com_sobipro/';
			require_once( $sobiRoot . 'lib/sobi.php' );
			try {
				Sobi::Initialise( 0, true );
				self::$sobiLoaded = true;
			}
			catch ( Exception $x ) {
				Log::add( 'Error initializing SobiPro: ' . $x->getMessage(), Log::ERROR, 'jerror' );
			}
		}
	}
}
