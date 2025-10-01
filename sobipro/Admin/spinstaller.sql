# @package SobiPro multi-directory component with content construction support
# @author
# Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
# Url: https://www.Sigsiu.NET
# @copyright Copyright (C) 2011 - 2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
# @license GNU/GPL Version 3
# This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License version 3
# as published by the Free Software Foundation, and under the additional terms according to section 7 of GPL v3.
# See https://www.gnu.org/licenses/gpl.html and https://www.sigsiu.net/licenses.
# This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
# @modified 30 May 2024 by Sigrid Suski

CREATE TABLE IF NOT EXISTS `#__sobipro_counter` (
`sid` INT(11) NOT NULL,
`counter` INT(11) NOT NULL,
`lastUpdate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (`sid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__sobipro_view_cache` (
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
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__sobipro_view_cache_relation` (
`cid` INT(11) NOT NULL,
`sid` INT(11) NOT NULL,
PRIMARY KEY (`cid`, `sid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__sobipro_crawler` (
`url` VARCHAR(190) NOT NULL,
`crid` INT(11) NOT NULL AUTO_INCREMENT,
`state` TINYINT(1) NOT NULL,
PRIMARY KEY (`crid`),
UNIQUE KEY `url`(`url`(100))
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__sobipro_category` (
`id` INT(11) NOT NULL COMMENT 'category id',
`section` INT(11) NOT NULL COMMENT 'section id',
`position` INT(11) COMMENT 'category position',
`param1` TEXT COMMENT 'reserved for future use',
`parseDesc` ENUM ('0','1','2') NOT NULL DEFAULT '2',
`param2` TEXT NULL COMMENT 'reserved for future use',
`showIntrotext` ENUM ('0','1','2') NOT NULL DEFAULT '2',
`icon` VARCHAR(150) COMMENT 'category icon',
`showIcon` ENUM ('0','1','2') NOT NULL DEFAULT '2',
`allFields` TINYINT(2) NOT NULL DEFAULT 1 COMMENT 'all fields are assigned to the category',
`entryFields` TEXT COMMENT 'list of fields assigned to the category',
PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__sobipro_config` (
`sKey` VARCHAR(150) NOT NULL COMMENT 'configuration key',
`sValue` TEXT COMMENT 'configuration value',
`section` INT(11) NOT NULL DEFAULT 0 COMMENT 'section id',
`critical` TINYINT(1) DEFAULT 0,
`cSection` VARCHAR(30) NOT NULL COMMENT 'configuration section',
PRIMARY KEY (`sKey`, `section`, `cSection`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

INSERT IGNORE INTO `#__sobipro_config`( `sKey`, `sValue`, `section`, `critical`, `cSection` )
VALUES
( 'action', '1', 0, 0, 'logging' ),
( 'allowed_attributes_array',
  'YTo4OntpOjA7czo1OiJjbGFzcyI7aToxO3M6MjoiaWQiO2k6MjtzOjU6InN0eWxlIjtpOjM7czo0OiJocmVmIjtpOjQ7czozOiJzcmMiO2k6NTtzOjQ6Im5hbWUiO2k6NjtzOjM6ImFsdCI7aTo3O3M6NToidGl0bGUiO30=', 0, 0,
  'html' ),
( 'allowed_tags_array',
  'YToxNzp7aTowO3M6MToiYSI7aToxO3M6MToicCI7aToyO3M6MjoiYnIiO2k6MztzOjI6ImhyIjtpOjQ7czozOiJkaXYiO2k6NTtzOjI6ImxpIjtpOjY7czoyOiJ1bCI7aTo3O3M6NDoic3BhbiI7aTo4O3M6NToidGFibGUiO2k6OTtzOjI6InRyIjtpOjEwO3M6MjoidGQiO2k6MTE7czozOiJpbWciO2k6MTI7czoyOiJoMSI7aToxMztzOjI6ImgyIjtpOjE0O3M6MjoiaDMiO2k6MTU7czoyOiJoNCI7aToxNjtzOjI6Img1Ijt9',
  0, 0, 'html' ),
( 'compress_js', '1', 0, 0, 'cache' ),
( 'currency', '€', 0, 0, 'payments' ),
( 'dec_point', ',', 0, 0, 'payments' ),
( 'discount_to_netto', '0', 0, 0, 'payments' ),
( 'display_errors', '0', 0, 0, 'debug' ),
( 'engb_preload', '1', 0, 0, 'lang' ),
( 'extra_fields_array', '', 0, 0, 'alphamenu' ),
( 'fields_array', 'YTo0OntpOjA7czoxMjoiY291bnRlci5kZXNjIjtpOjE7czoxNjoiY3JlYXRlZFRpbWUuZGVzYyI7aToyO3M6MTQ6ImZpZWxkX25hbWUuYXNjIjtpOjM7czoxNToiZmllbGRfbmFtZS5kZXNjIjt9', 0, 0,
  'ordering' ),
( 'fields_array', '', 0, 0, 'sordering' ),
( 'format', '%value %currency', 0, 0, 'payments' ),
( 'include_css_files', '1', 0, 0, 'cache' ),
( 'include_js_files', '1', 0, 0, 'cache' ),
( 'jquery-load', '1', 0, 0, 'template' ),
( 'l3_enabled', '1', 0, 0, 'cache' ),
( 'letters', 'A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z,0-9', 0, 0, 'alphamenu' ),
( 'level', '2', 0, 0, 'debug' ),
( 'list_format', 'd.m.Y', 0, 0, 'date' ),
( 'multimode', '0', 0, 0, 'lang' ),
( 'number_categories', '5', 0, 0, 'cpanel' ),
( 'number_entries', '5', 0, 0, 'cpanel' ),
( 'number_history', '5', 0, 0, 'cpanel' ),
( 'percent_format', '%number%sign', 0, 0, 'payments' ),
( 'publishing_format', 'd.m.Y H:i', 0, 0, 'date' ),
( 'show_categories', '0', 0, 0, 'cpanel' ),
( 'show_entries', '1', 0, 0, 'cpanel' ),
( 'show_faulty', '1', 0, 0, 'cpanel' ),
( 'show_history', '1', 0, 0, 'cpanel' ),
( 'show_pb', '1', 0, 0, 'general' ),
( 'vat', '7', 0, 0, 'payments' ),
( 'vat_brutto', '1', 0, 0, 'payments' ),
( 'xml_enabled', '1', 0, 0, 'cache' ),
( 'xml_no_reg', '0', 0, 0, 'cache' );

CREATE TABLE IF NOT EXISTS `#__sobipro_errors` (
`eid` INT(25) NOT NULL AUTO_INCREMENT,
`date` DATETIME NOT NULL,
`errNum` INT(5) NOT NULL,
`errCode` INT(5) NOT NULL,
`errMsg` TEXT NOT NULL,
`errFile` TEXT NOT NULL,
`errLine` INT(10) NOT NULL,
`errSect` VARCHAR(50) NOT NULL,
`errUid` INT(11) NOT NULL,
`errIp` VARCHAR(40) DEFAULT '0.0.0.0',
`errRef` TEXT NOT NULL,
`errUa` TEXT NOT NULL,
`errReq` TEXT NOT NULL,
`errCont` TEXT NOT NULL,
`errBacktrace` TEXT NOT NULL,
PRIMARY KEY (`eid`)
) ENGINE = InnoDB DEFAULT CHARSET = `utf8mb4` COLLATE = `utf8mb4_unicode_ci` AUTO_INCREMENT = 1;

CREATE TABLE IF NOT EXISTS `#__sobipro_field` (
`fid` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'field id',
`nid` VARCHAR(150) NOT NULL COMMENT 'field alias',
`adminField` TINYINT(1) DEFAULT 0 COMMENT 'classifies a field to be shown publicly, for admin only or as a category field',
`admList` INT(10) DEFAULT 0 COMMENT 'field shown in entry list',
`dataType` INT(11) DEFAULT 0,
`enabled` TINYINT(1) DEFAULT 0,
`fee` DOUBLE DEFAULT 0,
`fieldType` VARCHAR(50) NOT NULL,
`filter` VARCHAR(150) COMMENT 'alias of a used filter',
`isFree` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=field is for free',
`position` INT(11),
`priority` INT(11) NOT NULL DEFAULT 5 COMMENT 'search priority',
`required` TINYINT(1) DEFAULT 0,
`section` INT(11) NOT NULL COMMENT 'section id',
`multiLang` TINYINT(4) DEFAULT 0,
`uniqueData` TINYINT(1) DEFAULT 0,
`validate` TINYINT(1) DEFAULT 0,
`addToMetaDesc` TINYINT(1) DEFAULT 0,
`addToMetaKeys` TINYINT(1) DEFAULT 0,
`editLimit` INT(11) DEFAULT -1,
`editable` TINYINT(4) DEFAULT 1,
`showIn` ENUM ('both','details','vcard','hidden') NOT NULL DEFAULT 'both',
`allowedAttributes` TEXT,
`allowedTags` TEXT,
`editor` VARCHAR(190) COMMENT 'use WYSIWYG editor',
`inSearch` TINYINT(4) DEFAULT 0 COMMENT 'searchable',
`withLabel` TINYINT(4) DEFAULT 1,
`cssClass` VARCHAR(50),
`parse` TINYINT(4) DEFAULT 0,
`template` VARCHAR(190) COMMENT 'reserved for future use',
`notice` TEXT COMMENT 'administrator notes',
`params` TEXT COMMENT 'additional parameters',
`defaultValue` TEXT,
`version` INT(11) NOT NULL DEFAULT 1,
PRIMARY KEY (`fid`),
KEY `enabled`(`enabled`),
KEY `position`(`position`),
KEY `section`(`section`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__sobipro_field_data` (
`publishUp` DATETIME DEFAULT '0000-00-00 00:00:00' COMMENT 'reserved for future use',
`publishDown` DATETIME DEFAULT '0000-00-00 00:00:00' COMMENT 'reserved for future use',
`fid` INT(11) NOT NULL DEFAULT 0 COMMENT 'field id',
`sid` INT(11) NOT NULL DEFAULT 0 COMMENT 'entry/category id',
`section` INT(11) NOT NULL DEFAULT 0 COMMENT 'section id',
`lang` VARCHAR(50) NOT NULL DEFAULT 'en-GB',
`enabled` TINYINT(1) NOT NULL DEFAULT 0,
`params` MEDIUMTEXT,
`options` MEDIUMTEXT,
`baseData` LONGTEXT,
`approved` TINYINT(1) DEFAULT 0,
`confirmed` TINYINT(1) DEFAULT 0,
`createdTime` DATETIME DEFAULT '0000-00-00 00:00:00',
`createdBy` INT(11) DEFAULT 0,
`createdIP` VARCHAR(40) DEFAULT '0.0.0.0',
`updatedTime` DATETIME DEFAULT '0000-00-00 00:00:00',
`updatedBy` INT(11) DEFAULT 0,
`updatedIP` VARCHAR(40) DEFAULT '0.0.0.0',
`copy` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=unapproved version of field data',
`editLimit` INT(11) DEFAULT -1,
PRIMARY KEY (`fid`, `section`, `lang`, `sid`, `copy`),
KEY `enabled`(`enabled`),
KEY `copy`(`copy`),
FULLTEXT KEY `baseData`(`baseData`)
-- Although InnoDB is actually the better choice because, among other things, it solves access problems with multiple users, read access is slower than MyISAM. This can lead to a noticeable loss of speed with large amounts of data.
) ENGINE = MyISAM DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__sobipro_field_option` (
`fid` INT(11) NOT NULL COMMENT 'field id',
`optValue` VARCHAR(100) NOT NULL COMMENT 'option value',
`optPos` INT(11) NOT NULL,
`img` VARCHAR(150),
`optClass` VARCHAR(50),
`actions` TEXT,
`class` TEXT,
`optParent` VARCHAR(100) COMMENT 'option parent',
PRIMARY KEY (`fid`, `optValue`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__sobipro_field_option_selected` (
`fid` INT(11) NOT NULL COMMENT 'field id',
`sid` INT(11) NOT NULL COMMENT 'entry/category id',
`optValue` VARCHAR(100) NOT NULL COMMENT 'option value',
`params` MEDIUMTEXT,
`copy` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=unapproved version of field data',
PRIMARY KEY (`fid`, `sid`, `optValue`, `copy`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__sobipro_field_types` (
`tid` CHAR(50) NOT NULL,
`fType` VARCHAR(50) NOT NULL,
`tGroup` VARCHAR(100) NOT NULL,
`fPos` INT(11) NOT NULL AUTO_INCREMENT,
PRIMARY KEY (`tid`, `tGroup`),
UNIQUE KEY `pos`(`fPos`)
# 1.x: AUTO_INCREMENT=13
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci AUTO_INCREMENT = 20;

INSERT IGNORE INTO `#__sobipro_field_types`( `tid`, `fType`, `tGroup`, `fPos` )
VALUES
( 'inbox', 'Input Box', 'free_single_simple_data', 1 ),
( 'textarea', 'Text Area', 'free_single_simple_data', 2 ),
( 'multiselect', 'Multiple Select List', 'predefined_multi_data_multi_choice', 3 ),
( 'chbxgroup', 'Check Box Group', 'predefined_multi_data_multi_choice', 4 ),
( 'button', 'Button', 'special', 5 ),
( 'info', 'Information', 'free_single_simple_data', 6 ),
( 'select', 'Single Select List', 'predefined_multi_data_single_choice', 7 ),
( 'radio', 'Radio Buttons', 'predefined_multi_data_single_choice', 8 ),
( 'image', 'Image', 'special', 9 ),
( 'url', 'URL', 'special', 10 ),
( 'category', 'Category', 'special', 11 ),
( 'email', 'Email', 'special', 12 );

CREATE TABLE IF NOT EXISTS `#__sobipro_field_url_clicks` (
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
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__sobipro_language` (
`sKey` VARCHAR(150) NOT NULL COMMENT 'object key',
`sValue` MEDIUMTEXT COMMENT 'object value',
`section` INT(11) NOT NULL DEFAULT 0,
`language` VARCHAR(50) NOT NULL DEFAULT 'en-GB',
`oType` VARCHAR(150) NOT NULL COMMENT 'object type',
`fid` INT(11) NOT NULL COMMENT 'field id if oType=field',
`id` INT(11) NOT NULL COMMENT 'section/entry/category id',
`params` LONGTEXT,
`options` LONGTEXT,
`explanation` LONGTEXT,
PRIMARY KEY (`sKey`, `language`, `id`, `fid`),
KEY `sKey`(`sKey`),
KEY `section`(`section`),
KEY `language`(`language`),
FULLTEXT KEY `sValue`(`sValue`)
-- Although InnoDB is actually the better choice because, among other things, it solves access problems with multiple users, read access is slower than MyISAM. This can lead to a noticeable loss of speed with large amounts of data.
) ENGINE = MyISAM DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

INSERT IGNORE INTO `#__sobipro_language`( `sKey`, `sValue`, `section`, `language`, `oType`, `fid`, `id`, `params`, `options`, `explanation` )
VALUES
( 'bankdata',
  '<p>Payment Subject: \"Entry #{entry.id} in {section.name} at {cfg:site_name}.\"</p>\r\n<p>Account Owner: Jon Doe\r\nAccount No.: 8274230479\r\nBank No.: 8038012380\r\nIBAN: 234242343018\r\nBIC: 07979079779ABCDEFGH</p>',
  1, 'en-GB', 'application', 0, 1, '', '', '' ),
( 'ppexpl', '<p>Please click on the button below to pay via Paypal.</p>', 1, 'en-GB', 'application', 0, 1, '', '', '' ),
( 'ppsubject', 'Entry #{entry.id} in {section.name} at {cfg:site_name}.', 1, 'en-GB', 'application', 0, 1, '', '', '' ),
( 'rejection-of-a-new-entry', 'The entry {entry.name} has been rejected as it does not comply with the rules.\n\nRejected by {user.name} at {date%d F Y H:i:s}.\n', 0, 'en-GB',
  'rejections-templates', 0, 1, '', '', '' ),
( 'rejection-of-changes', 'The changes in the entry {entry.name} has been discarded as they are violating our rules.\n\nRejected by {user.name} at {date%d F Y H:i:s}.\n', 0,
  'en-GB', 'rejections-templates', 0, 1, '', '', '' ),
( 'filter-website_full', 'Please enter a valid URL address in the $field field!', 0, 'en-GB', 'fields_filter', 0, 0, '', '', '' ),
( 'filter-website', 'Please enter a valid website address without the protocol in the $field field!', 0, 'en-GB', 'fields_filter', 0, 0, '', '', '' ),
( 'filter-title', 'The data entered in the $field field contains not allowed characters!', 0, 'en-GB', 'fields_filter', 0, 0, '', '', '' ),
( 'filter-single_letter', 'The $field field accepts only a one letter value!', 0, 'en-GB', 'fields_filter', 0, 0, '', '', '' ),
( 'filter-phone', 'Please enter a valid phone number in the $field field!', 0, 'en-GB', 'fields_filter', 0, 0, '', '', '' ),
( 'filter-integer', 'Please enter a numeric value in the $field field!', 0, 'en-GB', 'fields_filter', 0, 0, '', '', '' ),
( 'filter-float', 'Please enter a float value like 9.9 or 12.34 in the $field field!', 0, 'en-GB', 'fields_filter', 0, 0, '', '', '' ),
( 'filter-alphanum', 'In the $field field only alphabetic and numeric characters are allowed!', 0, 'en-GB', 'fields_filter', 0, 0, '', '', '' ),
( 'filter-email', 'Please enter an email address in the $field field!', 0, 'en-GB', 'fields_filter', 0, 0, '', '', '' ),
( 'filter-alpha', 'In the $field field only letters are allowed!', 0, 'en-GB', 'fields_filter', 0, 0, '', '', '' );

CREATE TABLE IF NOT EXISTS `#__sobipro_object` (
`id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'entry/category id',
`nid` VARCHAR(190) NOT NULL COMMENT 'entry/category alias',
`name` VARCHAR(190) COMMENT 'reserved for future use',
`approved` TINYINT(1) NOT NULL,
`confirmed` TINYINT(1) DEFAULT 0,
`counter` INT(11) NOT NULL DEFAULT 0,
`cout` INT(11) DEFAULT 0,
`coutTime` DATETIME DEFAULT '0000-00-00 00:00:00',
`createdTime` DATETIME DEFAULT '0000-00-00 00:00:00',
`defURL` VARCHAR(190),
`metaDesc` TEXT,
`metaKeys` TEXT,
`metaAuthor` VARCHAR(150) NOT NULL DEFAULT '',
`metaRobots` VARCHAR(150) NOT NULL DEFAULT '',
`options` TEXT,
`oType` VARCHAR(150) NOT NULL COMMENT 'object type',
`owner` INT(11) DEFAULT 0,
`ownerIP` VARCHAR(40) DEFAULT '0.0.0.0',
`params` TEXT,
`section` INT(11) DEFAULT 0,
`parent` INT(11) DEFAULT 0,
`state` TINYINT(4) NOT NULL,
`stateExpl` TEXT,
`updatedTime` DATETIME DEFAULT '0000-00-00 00:00:00',
`updater` INT(11),
`updaterIP` VARCHAR(40),
`validSince` DATETIME NOT NULL,
`validUntil` DATETIME,
`version` INT(11) NOT NULL DEFAULT 1,
PRIMARY KEY (`id`),
KEY `name`(`name`),
KEY `oType`(`oType`),
KEY `owner`(`owner`),
KEY `parent`(`parent`),
KEY `state`(`state`),
KEY `validSince`(`validSince`),
KEY `validUntil`(`validUntil`),
KEY `version`(`version`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci AUTO_INCREMENT = 54;

CREATE TABLE IF NOT EXISTS `#__sobipro_payments` (
`pid` INT(11) NOT NULL AUTO_INCREMENT,
`refNum` VARCHAR(50) NOT NULL,
`sid` INT(11) NOT NULL,
`fid` INT(11) NOT NULL,
`subject` VARCHAR(150) NOT NULL,
`dateAdded` DATETIME NOT NULL,
`datePaid` DATETIME DEFAULT '0000-00-00 00:00:00',
`validUntil` DATETIME DEFAULT '0000-00-00 00:00:00',
`paid` TINYINT(4) DEFAULT 0,
`amount` DOUBLE NOT NULL,
`params` TEXT,
PRIMARY KEY (`pid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci AUTO_INCREMENT = 16;

CREATE TABLE IF NOT EXISTS `#__sobipro_permissions` (
`pid` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'permission id',
`subject` VARCHAR(150) NOT NULL,
`action` VARCHAR(50) NOT NULL,
`value` VARCHAR(50) NOT NULL,
`site` VARCHAR(50) NOT NULL,
`published` TINYINT(1) NOT NULL,
PRIMARY KEY (`pid`),
UNIQUE KEY `uniquePermission`(`subject`(50), `action`(14), `value`(18), `site`(18))
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci AUTO_INCREMENT = 100;

INSERT IGNORE INTO `#__sobipro_permissions`( `pid`, `subject`, `action`, `value`, `site`, `published` )
VALUES
( 1, '*', '*', '*', 'front', 0 ),
( 2, 'section', '*', '*', 'front', 0 ),
( 3, 'section', 'access', '*', 'front', 1 ),
( 4, 'section', 'access', 'valid', 'front', 1 ),
( 6, 'category', '*', '*', 'front', 0 ),
( 7, 'category', 'access', 'valid', 'front', 1 ),
( 8, 'category', 'access', '*', 'front', 1 ),
( 9, 'entry', '*', '*', 'front', 1 ),
( 10, 'entry', 'access', 'valid', 'front', 1 ),
( 11, 'entry', 'access', '*', 'front', 1 ),
( 12, 'entry', 'access', 'unpublished_own', 'front', 1 ),
( 13, 'entry', 'access', 'unapproved_own', 'front', 0 ),
( 14, 'entry', 'access', 'unpublished_any', 'front', 1 ),
( 15, 'entry', 'access', 'unapproved_any', 'front', 1 ),
( 16, 'entry', 'add', 'own', 'front', 1 ),
( 17, 'entry', 'edit', 'own', 'front', 1 ),
( 18, 'entry', 'edit', '*', 'front', 1 ),
( 19, 'entry', 'manage', '*', 'front', 1 ),
( 20, 'entry', 'publish', '*', 'front', 1 ),
( 21, 'entry', 'publish', 'own', 'front', 1 ),
( 22, 'entry', 'adm_fields', '*', 'front', 0 ),
( 23, 'entry', 'adm_fields', 'see', 'front', 0 ),
( 24, 'entry', 'adm_fields', 'edit', 'front', 1 ),
( 25, 'entry', 'payment', 'free', 'front', 1 ),
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
( 99, 'entry', 'publish', '*', 'adm', 1 );

# DELETE
# FROM `#__sobipro_permissions`
# WHERE `pid` = 5;
# ALTER TABLE `#__sobipro_permissions`
# ADD UNIQUE KEY `uniquePermission`(`subject`(50), `action`(14), `value`(18), `site`(18));

INSERT IGNORE INTO `#__sobipro_permissions`( `pid`, `subject`, `action`, `value`, `site`, `published` )
VALUES
( NULL, 'section', 'search', '*', 'front', 1 ),
( NULL, 'entry', 'delete', 'own', 'front', 1 ),
( NULL, 'entry', 'delete', '*', 'front', 1 ),
( NULL, 'entry', 'manage', 'own', 'front', 1 ),
( NULL, 'entry', 'access', 'expired_own', 'front', 1 ),
( NULL, 'entry', 'access', 'expired_any', 'front', 1 );

CREATE TABLE IF NOT EXISTS `#__sobipro_permissions_groups` (
`rid` INT(11) NOT NULL COMMENT 'rule id',
`gid` INT(11) NOT NULL COMMENT 'permission group id',
PRIMARY KEY (`rid`, `gid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__sobipro_permissions_map` (
`rid` INT(11) NOT NULL COMMENT 'rule id',
`sid` INT(11) NOT NULL COMMENT 'section id',
`pid` INT(11) NOT NULL COMMENT 'permission id',
PRIMARY KEY (`rid`, `sid`, `pid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__sobipro_permissions_rules` (
`rid` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'rule id',
`name` VARCHAR(190) NOT NULL COMMENT 'rule name',
`nid` VARCHAR(50) NOT NULL COMMENT 'rule alias',
`validSince` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
`validUntil` DATETIME,
`note` TEXT,
`state` TINYINT(4) NOT NULL,
PRIMARY KEY (`rid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci AUTO_INCREMENT = 3;

CREATE TABLE IF NOT EXISTS `#__sobipro_plugins` (
`pid` VARCHAR(50) NOT NULL COMMENT 'application id',
`name` VARCHAR(150) NOT NULL COMMENT 'application name',
`version` VARCHAR(50) NOT NULL,
`description` TEXT,
`author` VARCHAR(150),
`authorURL` VARCHAR(190),
`authorMail` VARCHAR(150),
`enabled` TINYINT(1) NOT NULL DEFAULT 0,
`type` VARCHAR(100) NOT NULL,
`depend` TEXT NOT NULL COMMENT 'reserved for future use',
UNIQUE KEY `pid`(`pid`(35), `type`(65))
) ENGINE = InnoDB COLLATE = utf8mb4_unicode_ci DEFAULT CHARSET = utf8mb4;

INSERT IGNORE INTO `#__sobipro_plugins`( `pid`, `name`, `version`, `description`, `author`, `authorURL`, `authorMail`, `enabled`, `type`, `depend` )
VALUES
( 'bank_transfer', 'Offline Payment', '2.4', NULL, 'Sigsiu.NET GmbH', 'https://www.sigsiu.net/', 'no-reply@sigsiu.net', 1, 'payment', '' ),
( 'paypal', 'PayPal', '2.4', NULL, 'Sigsiu.NET GmbH', 'https://www.sigsiu.net/', 'no-reply@sigsiu.net', 1, 'payment', '' ),
( 'chbxgroup', 'Check Box Group', '2.4', NULL, 'Sigsiu.NET GmbH', 'https://www.sigsiu.net/', 'no-reply@sigsiu.net', 1, 'field', '' ),
( 'email', 'Email', '2.4', NULL, 'Sigsiu.NET GmbH', 'https://www.sigsiu.net/', 'no-reply@sigsiu.net', 1, 'field', '' ),
( 'image', 'Image', '2.4', NULL, 'Sigsiu.NET GmbH', 'https://www.sigsiu.net/', 'no-reply@sigsiu.net', 1, 'field', '' ),
( 'inbox', 'Input Box', '2.4', NULL, 'Sigsiu.NET GmbH', 'https://www.sigsiu.net/', 'no-reply@sigsiu.net', 1, 'field', '' ),
( 'multiselect', 'Multiple Select List', '2.4', NULL, 'Sigsiu.NET GmbH', 'https://www.sigsiu.net/', 'no-reply@sigsiu.net', 1, 'field', '' ),
( 'radio', 'Radio Buttons', '2.4', NULL, 'Sigsiu.NET GmbH', 'https://www.sigsiu.net/', 'no-reply@sigsiu.net', 1, 'field', '' ),
( 'select', 'Single Select List', '2.4', NULL, 'Sigsiu.NET GmbH', 'https://www.sigsiu.net/', 'no-reply@sigsiu.net', 1, 'field', '' ),
( 'textarea', 'Text Area', '2.4', NULL, 'Sigsiu.NET GmbH', 'https://www.sigsiu.net/', 'no-reply@sigsiu.net', 1, 'field', '' ),
( 'url', 'URL', '2.4', NULL, 'Sigsiu.NET GmbH', 'https://www.sigsiu.net/', 'no-reply@sigsiu.net', 1, 'field', '' ),
( 'category', 'Category', '2.4', NULL, 'Sigsiu.NET GmbH', 'https://www.sigsiu.net/', 'no-reply@sigsiu.net', 1, 'field', '' ),
( 'info', 'Information', '2.4', NULL, 'Sigsiu.NET GmbH', 'https://www.sigsiu.net/', 'no-reply@sigsiu.net', 1, 'field', '' ),
( 'button', 'Button', '2.4', NULL, 'Sigsiu.NET GmbH', 'https://www.sigsiu.net/', 'no-reply@sigsiu.net', 1, 'field', '' );

CREATE TABLE IF NOT EXISTS `#__sobipro_plugin_section` (
`section` INT(11) NOT NULL COMMENT 'section id',
`pid` VARCHAR(50) NOT NULL COMMENT 'application id',
`type` VARCHAR(50) NOT NULL COMMENT 'application type',
`enabled` TINYINT(1) DEFAULT 0,
`position` INT(11) DEFAULT 0,
PRIMARY KEY (`section`, `pid`, `type`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__sobipro_plugin_task` (
`pid` VARCHAR(50) NOT NULL COMMENT 'application id',
`onAction` VARCHAR(150) NOT NULL COMMENT 'action task',
`type` VARCHAR(50) NOT NULL COMMENT 'application type',
UNIQUE KEY `pid`(`pid`(20), `onAction`(60), `type`(20))
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

INSERT IGNORE INTO `#__sobipro_plugin_task`( `pid`, `onAction`, `type` )
VALUES
( 'bank_transfer', 'adm.*', 'payment' ),
( 'bank_transfer', 'adm_menu', 'payment' ),
( 'bank_transfer', 'entry.payment', 'payment' ),
( 'bank_transfer', 'entry.save', 'payment' ),
( 'bank_transfer', 'entry.submit', 'payment' ),
( 'paypal', 'adm.*', 'payment' ),
( 'paypal', 'adm_menu', 'payment' ),
( 'paypal', 'entry.payment', 'payment' ),
( 'paypal', 'entry.save', 'payment' ),
( 'paypal', 'entry.submit', 'payment' );

CREATE TABLE IF NOT EXISTS `#__sobipro_registry` (
`section` VARCHAR(150) NOT NULL,
`key` VARCHAR(150) NOT NULL,
`value` TEXT NOT NULL,
`params` TEXT,
`description` TEXT,
`options` TEXT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

INSERT IGNORE INTO `#__sobipro_registry`( `section`, `key`, `value`, `params`, `description`, `options` )
VALUES
( 'fields_filter', 'website_full', 'Website with Protocol', 'L15odHRwcz86XC9cL1tcd1wuLV0rXC57MX1bYS16QS1aXXsyLDI0fVtcL117MCwxfSQv', '', '' ),
( 'fields_filter', 'website', 'Website w/o Protocol', 'L14oW1x3MC05LV0rXC4pK1thLXpBLVpdezIsfShcLy4qKT8kLw==', '', '' ),
( 'fields_filter', 'title', 'Valid Title', 'L15bXHdcZF0rW1x3XGRccyFAXCRcJVwmXCpcIlwnXC1cK19dKiQv', '', 'custom' ),
( 'fields_filter', 'single_letter', 'Single Letter', 'L15bYS16QS1aXSQv', '', '' ),
( 'fields_filter', 'phone', 'Telephone Number', 'L14oXCtcZHsxLDN9XHM/KT8oXHM/XChbXGRdXClccz8pP1tcZFwtXHNcLl0rJC8=', '', '' ),
( 'fields_filter', 'integer', 'Decimal Value', 'L15cZCskLw==', '', '' ),
( 'fields_filter', 'float', 'Float Value', 'L15cZCsoXC5cZCopPyQv', '', '' ),
( 'fields_filter', 'alphanum', 'Alphanumeric String', 'L15bYS16QS1aMC05XSskLw==', '', '' ),
( 'fields_filter', 'email', 'Email Address', 'L15bXHdcLi1dK0BbXHdcLi1dK1wuW2EtekEtWl17MiwyNH0kLw==', '', '' ),
( 'fields_filter', 'alpha', 'Alphabetic String', 'L15bYS16QS1aXSskLw==', '', '' ),
( 'paypal', 'ppcc', 'EUR', '', '', '' ),
( 'paypal', 'pprurl', '{cfg:live_site}/index.php?option=com_sobipro&sid={section.id}', '', '', '' ),
( 'paypal', 'ppcancel', '{cfg:live_site}/index.php?option=com_sobipro&sid={section.id}', '', '', '' ),
( 'paypal', 'ppurl', 'https://www.paypal.com/cgi-bin/webscr', '', '', '' ),
( 'paypal', 'ppemail', 'change@me.com', '', '', '' ),
( 'paypal', 'pploc', '1', '', '', '' ),
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
  '' );

CREATE TABLE IF NOT EXISTS `#__sobipro_relations` (
`id` INT(11) NOT NULL COMMENT 'sid',
`pid` INT(11) NOT NULL COMMENT 'parent id',
`oType` VARCHAR(150) NOT NULL COMMENT 'object type',
`position` INT(11) DEFAULT 0,
`validSince` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
`validUntil` DATETIME,
`copy` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=unapproved version of relation',
PRIMARY KEY (`id`, `pid`),
KEY `oType`(`oType`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__sobipro_search` (
`ssid` VARCHAR(50) NOT NULL,
`lastActive` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
`searchCreated` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
`requestData` TEXT,
`uid` INT(11) DEFAULT 0,
`browserData` TEXT,
`entriesResults` LONGTEXT,
`catsResults` MEDIUMTEXT,
PRIMARY KEY (`ssid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__sobipro_section` (
`id` INT(11) NOT NULL COMMENT 'reserved for future use',
`description` TEXT COMMENT 'reserved for future use',
PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__sobipro_users_relation` (
`uid` INT(11) NOT NULL,
`gid` INT(11) NOT NULL,
`validSince` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
`validUntil` DATETIME,
PRIMARY KEY (`uid`, `gid`, `validSince`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__sobipro_user_group` (
`description` TEXT,
`gid` INT(11) NOT NULL AUTO_INCREMENT,
`enabled` INT(11) DEFAULT 0,
`pid` INT(11) NOT NULL,
`groupName` VARCHAR(150) NOT NULL,
PRIMARY KEY (`gid`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci AUTO_INCREMENT = 5000;

CREATE TABLE IF NOT EXISTS `#__sobipro_history` (
`revision` VARCHAR(150) NOT NULL,
`changedAt` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
`uid` INT(11) DEFAULT 0,
`userName` VARCHAR(150),
`userEmail` VARCHAR(150),
`type` VARCHAR(80) NOT NULL COMMENT 'object type',
`changeAction` VARCHAR(150) NOT NULL,
`site` ENUM ('site','adm') NOT NULL,
`sid` INT(11) NOT NULL,
`section` INT(11) DEFAULT 0,
`changes` TEXT,
`params` TEXT,
`reason` TEXT,
`language` VARCHAR(50) NOT NULL DEFAULT 'en-GB',
PRIMARY KEY (`revision`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
