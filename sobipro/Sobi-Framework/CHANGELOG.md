# Changelog for Sobi Framework

#### @package

Sobi Framework

#### @author

Name: Sigrid Suski and Radek Suski, Sigsiu.NET GmbH  
Email: no-reply@sigsiu.net  
Url: https://www.Sigsiu.NET

#### @copyright

Copyright (C) 2006–2025 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.  
@license GNU/LGPL Version 3  
This program is free software: you can redistribute it and/or modify it under the terms of GNU Lesser General Public License version 3 as published by the Free Software
Foundation, and under the additional terms according to section 7 of LGPL v3.  
See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Lesser General Public License for more details.

#### Legend:

(*) Security Fix  
(#) Bug Fix  
(+) Addition  
(-) Removed  
(!) Change


### 2.2.3 (28 March 2025)

    (!) Constant C::BOOTSTRAP_JS added

### 2.2.2 (05 November 2024)

    (!) Input::Html() now changes '%7Bentry.url%7D' back to '{entry.url}'

### 2.2.1 (23 August 2024)

    (#) Did not work without enabled Backward Compatibility plugin
    (#) GetInt() does no longer work correctly (Joomla problem)
    (#) Mail constructor does not always work

### 2.2 (27 February 2024)

    (+) Binary Fulltext search added

### 2.1.4 (28 April 2023)

    (!) Joomla languages loaded via Application

### 2.1.3 (01 March 2023)

    (+) Possibility to set a fix height or width for images

    (#) GetExt() returns the whole path if file does not have an extension

### 2.1.2 (31 January 2023)

    (#) Wrong return type for CURL method info()

### 2.1.1 (31 October 2022)

    (+) New method in Mail to clear recipient addresses

### 2.1.0 (31 August 2022)

    (!) PHP 8.1 compatibility

### 2.0.2 (28 July 2022)

    (+) Constant BOOTSTRAP6 added for future
    
    (#) Missing constant SOBI_APP
    (#) Constant INFO_MSG not declared
    (#) Wrong return type in case ini file could not be parsed

### 2.0.1 (25 April 2022)

    (+) Constant BOOTSTRAP_STYLES added

### 2.0 (17 January 2022)

    (!) Version set to 2.0

### 2.0 Beta 1 (9 November 2021)

    (!) Handle bool values in ini files correctly

### 2.0 Alpha 2 (19 October 2021)

    (-) not used header file removed

### 2.0 Alpha 1 (01 October 2021)

    (+) WriteIniFile method added and 'mode' parameter added to LoadIniFile method
    (+) Mail wrapper for Joomla
    (+) Several additional wrappers added specifically for Joomla

    (!) File System Wrapper specifically for Joomla
    (!) strict_types removed for the moment

    (-) Nid creation for fields added

    (#) false must be 0 and not '' for strict database

### 1.5 (28 May 2021)

    (!) Interface to Joomla moved to Framework (used by SobiPro 2.0)
    (!) Version determination moved to Framework (used by SobiPro 2.0)
   	(!) Router adapted to work with J4 too
   	(!) Database normalisation re-worked. Takes care now of default values and data types.

    (#) Index of array converted to string (TypecastArray) as float can't be an index
    (#) Incorrect SQL statement

### 1.4 (26 February 2021)

    (+) Bootstrap 5 constants added for SobiPro 2.0
    (+) Possibility to set $_FILES value

    (!) SobiPro 2.0 constants changed

    (#) Failed to move file throws exception
    (#) Request data returns 'null' for '0' values. This is not the desired behaviour.

### 1.3 (15 December 2020)

    (+) Various constants added for SobiPro 2.0
    (+) Extended Utils/Type.php with TypecastVariable and TypecastArray
    (+) New type handling utility added
    
    (!) Input:Int() extended by parameter noZero
    
    (#) Debug output in set query removed and switched to Exception throw

### 1.2 (30 September 2020)

    (+) Recursive file moving/copying added to the DirectoryIterator 
    
    (!) Detecting graphics editor depends on WEBP capability
    (!) Possibility to use normalize with insertArray()

    (#) Fixes for latest MySQL version
    (#) FileSystem::Delete() does not always return success status

### 1.1 (31 July 2020)

    (+) Added support for WEBP images into Grafika
    (+) Introducing of semantic versioning
    (+) webp_quality config key for WEBP images added
    
    (!) Image->saveAs() creates editor object if it not already exists
    
    (#) Braces of placeholders are changed to entities when in urls (Issue #11)
    (#) Usage of wrong translation method for some error messages (Txt() instead of Error())

### 1.0.13 (05 June 2020)

    (+) Output of error messages added
    (+) CURL initialisation error added

    (#) FILTER_SANITIZE_ADD_SLASHES does not work for PHP versions below 7.4

### 1.0.12 (30 May 2020)

    (!) CURL error messages improved and initialisation status added
    (!) FILTER_SANITIZE_MAGIC_QUOTES is deprecated, use FILTER_SANITIZE_ADD_SLASHES instead

### 1.0.11 (21 January 2020)

	(#) double usage of variable $e

### 1.0.10 (26 November 2019)

	(+) Workaround for Joomla returnig wrongfully an integer when array with the same name is set

### 1.0.9 (13 February 2019)

	(+) Implementation of FileSystem::FixUrl and failsafe for FileSystem::FixPath

	(#) Image Rotation does not work correctly
	(#) Original file should not be deleted after resampling

### 1.0.8 (10 January 2019)

	(#) Image rotation not called
	(#) Input::Search breaks after first result

### 1.0.7 (6 September 2018)

	(+) Simple OpenSSL encryption added 
	(+) Option to replace characters with null in StringUtils::Nid
	(+) Added constant for success message 
	(+) Support for attributes and custom node names in Arr class 
	
	(!) Getting "files" array from PHP directly

	(#) Count on non-arrays in PHP 7.2.x (Issue #103)

### 1.0.6 (4 May 2018)

	(#) HTML filter, reverting html entities (Issue #8)

### 1.0.5 (3 May 2018)

	(#) Closing for empty tags changed to short closing and leads to problems (Issue #7)
	(#) Each save doubles the number of line-breaks in textareas (Issue #6)

### 1.0.4 (25 April 2018)

    (+) Implemented AthosHun\HTMLFilter
    (+) Implemented Grafika 
    (+) Autoloader support for third party libraries
    
	(#) Input::Search returns an array now
	(#) Missing slash in RegEx (Issue #3) 

### 1.0.3 (28 December 2017)

    (+) Input::Search implemented 
    
	(!) Switching Input::Raw back to vanilla PHP
	(!) Autoloader inclusion changed from relative to absolute path. Seems doesn't work on some servers. 

	(#) Wrong calls in Input::Timestamp
	(#) Or-operation of where conditions with time as parameter failed

### 1.0.2 (4 March 2017)

	(#) Wrong regex for Input::Cmd

### 1.0.1 (1 March 2017)

	(!) Array syntax changed

	(#) SPC used in Framework; junk left after moving functionality to the Framework (Issue #1766)

### 1.0.0 (21 January 2017)

	(+) Initial release 
