<?php
/**
 * @package SobiPro Library
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2006-2025 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according to section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 15 July 2021 by Sigrid Suski
 * @modified 05 March 2025 by Sigrid Suski
 */

defined( 'SOBIPRO' ) || exit( 'Restricted access' );

use Sobi\C;
use Sobi\Lib\Factory;

/**
 * Class SPHistory
 */
class SPHistory
{
	/**
	 * @return \SPHistory
	 */
	public static function & getInstance()
	{
		static $history = null;
		if ( !( $history instanceof SPHistory ) ) {
			$history = new self();
		}

		return $history;
	}

	/**
	 * Logs an action.
	 *
	 * @param string $action
	 * @param int $sid
	 * @param int $section
	 * @param string $type
	 * @param string $message
	 * @param array $params
	 * @param array $changes
	 *
	 * @return $this
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function & logAction( string $action,
	                             int    $sid = 0,
	                             int    $section = 0,
	                             string $type = 'entry',
	                             string $message = C::ES,
	                             array  $params = [],
	                             array  $changes = [] ): SPHistory
	{
		if ( !defined( 'SOBI_TRIMMED' ) && ( Sobi::Cfg( 'logging.action', true ) || Sobi::Cfg( 'entry.versioning', true ) ) ) {

			$jparams = C::ES;
			if ( Sobi::Cfg( 'logging.action', true ) && $params ) {
				$jparams = json_encode( $params );
			}

			/* no action logging */
			else {
				/* only entry versioning! */
				if ( $type != 'entry' || $action != SPC::LOG_SAVE ) {
					return $this;
				}
			}

			/* save changes only if versioning on switched on */
			if ( Sobi::Cfg( 'entry.versioning', true ) ) {
				$changes = SPConfig::serialize( $changes );
			}
			else {
				$changes = [];
			}

			$log = [
				'revision'     => microtime( true ) . '.' . $sid . '.' . Sobi::My( 'id' ),
				'changedAt'    => 'FUNCTION:NOW()',
				'uid'          => Sobi::My( 'id' ),
				'userName'     => Sobi::My( 'name' ),
				'userEmail'    => Sobi::My( 'email' ),
				'type'         => $type,
				'changeAction' => $action,
				'site'         => defined( 'SOBIPRO_ADM' ) ? 'adm' : 'site',
				'sid'          => $sid,
				'section'      => $section,
				'changes'      => $changes,
				'params'       => $jparams,
				'reason'       => $message,
				'language'     => Sobi::Lang(),
			];
			Factory::Db()->insert( 'spdb_history', $log );

			SPFactory::cache( -1 )->deleteObj( 'cpanel_history', -1, -1 );
		}

		return $this;
	}

	/**
	 * @param $sid
	 * @param string $type
	 *
	 * @return array
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function getHistory( $sid, string $type = 'entry' ): array
	{
		$log = [];
		switch ( $type ) {
			case 'entry':
				$log = Factory::Db()
					->select( '*', 'spdb_history', [ 'sid' => $sid, 'changeAction' => SPC::LOG_SAVE ], 'changedAt.desc', 100 )
					->loadAssocList( 'revision' );

				if ( Sobi::Cfg( 'entry.versioning', true ) ) {
					if ( count( $log ) ) {
						foreach ( $log as $revision => $data ) {
							try {
								$log[ $revision ][ 'changes' ] = SPConfig::unserialize( $data[ 'changes' ] );
							}
							catch ( SPException $x ) {
								SPFactory::message()->warning( sprintf( "Can't restore revision from %s. Error was '%s'", $data[ 'changedAt' ], $x->getMessage() ), false );
							}
						}
					}
				}

				break;
		}

		return $log;
	}

	/**
	 * @param $item
	 * @param false $sentence
	 * @param false $plain
	 *
	 * @return string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function getObject( &$item, bool $sentence = false, bool $plain = false ): string
	{
		$retval = C::ES;
		$name = Sobi::getParam( $item[ 'params' ], 'name' );
		$name = $name == C::NO_VALUE ? C::ES : $name;
		$simpleText = true;

		/* entry handling */
		if ( $item[ 'sid' ] && $item[ 'type' ] == 'entry' ) {
			$object = SPFactory::EntryRow( $item[ 'sid' ] );
			switch ( $item[ 'changeAction' ] ) {
				case SPC::LOG_PUBLISH:
				case SPC::LOG_UNPUBLISH:
				case SPC::LOG_APPROVE:
				case SPC::LOG_UNAPPROVE:
				case SPC::LOG_REQUIRED:
				case SPC::LOG_UNREQUIRED:
				case SPC::LOG_DELETE:
				case SPC::LOG_SAVE:
				case SPC::LOG_EDIT:
				case SPC::LOG_ADD:
					$site = $item[ 'site' ] == 'site' ? Sobi::Txt( 'HISTORY_SENTENCE_AREA_SITE' ) : Sobi::Txt( 'HISTORY_SENTENCE_AREA_ADM' );
					$site = " ($site)";
					break;
				default:
					$site = C::ES;
					break;
			}
			$name = $object ? ( $name ? : $object->get( 'name' ) ) : $name;

			/* object still exists and edit url should be shown */
			if ( $object && !$plain ) {
				$simpleText = false;
				$url = "index.php?option=com_sobipro&task=entry.edit&sid={$item['sid']}";
				if ( $sentence ) {
					$retval = Sobi::Txt( 'HISTORY_SENTENCE_TYPE_ENTRY' ) . " <a href=\"$url\"><em>$name</em></a>" . $site;
				}
				else {
					$retval = "<a href=\"$url\">$name ({$item['sid']})</a>";
				}
			}

			/* don't show edit url */
			if ( $simpleText ) {
				if ( $sentence ) {
					$retval = Sobi::Txt( 'HISTORY_SENTENCE_TYPE_ENTRY' ) . " <span><em>$name</em></span>" . $site;
				}
				else {
					$deleted = ( !$object ) ? '<em> - ' . Sobi::Txt( 'HISTORY.DELETED' ) . '</em>' : C::ES;
					$retval = "$name ({$item['sid']})" . $deleted;
				}
			}
		}

		/* category handling */
		if ( $item[ 'sid' ] && $item[ 'type' ] == 'category' ) {
			$object = SPFactory::Category( $item[ 'sid' ] );
			$name = $object ? ( $name ? : $object->name() ) : $name;
			if ( $object && !$plain ) {
				$simpleText = false;
				$url = "index.php?option=com_sobipro&task=category.edit&sid={$item['sid']}";
				if ( $sentence ) {
					$retval = Sobi::Txt( 'HISTORY_SENTENCE_TYPE_CATEGORY' ) . " <a href=\"$url\"><em>$name</em></a>";
				}
				else {
					$retval = "<a href=\"$url\">$name ({$item['sid']})</a>";
				}
			}

			/* don't show edit url */
			if ( $simpleText ) {
				if ( $sentence ) {
					$retval = Sobi::Txt( 'HISTORY_SENTENCE_TYPE_CATEGORY' ) . " <span><em>$name</em></span>";
				}
				else {
					$deleted = ( !$object ) ? '<em> - ' . Sobi::Txt( 'HISTORY.DELETED' ) . '</em>' : C::ES;
					$retval = "$name ({$item['sid']})" . $deleted;
				}
			}
		}

		/* section handling */
		if ( $item[ 'sid' ] && $item[ 'type' ] == 'section' ) {
			$object = SPFactory::Section( $item[ 'sid' ] );
			$name = $object ? ( $name ? : $object->name() ) : $name;
			if ( $object && !$plain && Sobi::Can( 'section.configure' ) ) {
				$simpleText = false;
				$url = "index.php?option=com_sobipro&task=config&sid={$item['sid']}";
				if ( $sentence ) {
					$retval = Sobi::Txt( 'HISTORY_SENTENCE_TYPE_SECTION' ) . " <a href=\"$url\"><em>$name</em></a>";
				}
				else {
					$retval = "<a href=\"$url\">$name ({$item['sid']})</a>";
				}
			}

			/* don't show edit url */
			if ( $simpleText ) {
				if ( $sentence ) {
					$retval = Sobi::Txt( 'HISTORY_SENTENCE_TYPE_SECTION' ) . " <span><em>$name</em></span>";
				}
				else {
					$retval = "$name ({$item['sid']})";
				}
			}
		}

		/* application handling */
		if ( $item[ 'type' ] == 'application' ) {
			if ( $sentence ) {
				switch ( $item[ 'changeAction' ] ) {
					default:
					case SPC::LOG_PUBLISH:
					case SPC::LOG_UNPUBLISH:
					case SPC::LOG_INSTALL:
					case SPC::LOG_REMOVE:
					case SPC::LOG_DOWNLOAD:
					case SPC::LOG_ENABLE:
					case SPC::LOG_DISABLE:
					$oText = Sobi::Txt( 'HISTORY_SENTENCE_TYPE_' . strtoupper( $item[ 'type' ] ) );
						break;
					case SPC::LOG_REPOINSTALL:
						$oText = Sobi::Txt( 'HISTORY_SENTENCE_TYPE_REPOSITORY' );
						break;
					case SPC::LOG_REPOFETCH:
						$oText = Sobi::Txt( 'HISTORY_SENTENCE_TYPE_REPOSITORYCONTENT' );
						break;
					case SPC::LOG_REPOREGISTER:
						$oText = $name == C::ES ? Sobi::Txt( 'HISTORY_SENTENCE_TYPE_NOSUBSCRIPTION' ) : Sobi::Txt( 'HISTORY_SENTENCE_TYPE_SUBSCRIPTION' );
						break;
				}
				$retval = $oText . ( $name ? " <span><em>$name</em></span>" : C::ES );
			}
			else {
				$retval = "$name";
				if ( $item[ 'sid' ] > 0 ) {
					$retval .= ' (' . $item[ 'sid' ] . ')';
				}
			}
		}

		/* acl handling */
		if ( $item[ 'type' ] == 'acl' ) {
			/** @var SPAclCtrl $object */
			$text = Sobi::Txt( $item[ 'sid' ] ? 'HISTORY_SENTENCE_TYPE_ACL' : 'HISTORY_SENTENCE_TYPE_PERMISSION' );
			$object = SPFactory::Controller( 'acl', true )->ruleExists( $item[ 'sid' ] );
			/* object still exists and edit url should be shown */
			if ( $object && !$plain && Sobi::Can( 'cms.acl' ) ) {
				$simpleText = false;
				$url = "index.php?option=com_sobipro&task=acl.edit&rid={$item['sid']}";
				if ( $sentence ) {
					$retval = $text . " <a href=\"$url\"><em>$name</em></a>";
				}
				else {
					$retval = "<a href=\"$url\">$name ({$item['sid']})</a>";
				}
			}

			/* don't show edit url */
			if ( $simpleText ) {
				if ( $sentence ) {
					$retval = $text . " <span><em>$name</em></span>";
				}
				else {
					$retval = "$name";
					if ( $item[ 'sid' ] > 0 ) {
						$retval .= ' (' . $item[ 'sid' ] . ')';
					}
				}
			}
		}

		/* template handling */
		if ( $item[ 'type' ] == 'template' ) {
			$text = Sobi::Txt( 'HISTORY_SENTENCE_TYPE_TEMPLATE' );
			$to = Sobi::Txt( 'HISTORY_SENTENCE_TYPE_TEMPLATE_TO' );
			$new = Sobi::getParam( $item[ 'params' ], 'new', C::ES );

			/* object still exists and edit url should be shown */
			if ( !$plain ) {
				if ( Sobi::Can( 'cms.admin' ) || Sobi::Can( 'cms.options' ) ) {
					$simpleText = false;
					$url = Sobi::Url( [ 'task'     => 'template.info',
					                    'template' => str_replace( SOBI_PATH . '/usr/templates/', C::ES, Sobi::getParam( $item[ 'params' ], 'folder' ) ) ] );

					switch ( $item[ 'changeAction' ] ) {
						case SPC::LOG_DUPLICATE:
							$retval = $sentence ? "$text <em>$name</em> $to <a href=\"$url\"><em>$new</em></a>" : "$name -> <a href=\"$url\">$new</a>";
							break;
						case SPC::LOG_DELETE:
							$simpleText = true;
							break;
						default:
							$retval = $sentence ? $text . " <a href=\"$url\"><em>$name</em></a>" : "<a href=\"$url\">$name</a>";
							break;
					}
				}
			}

			/* don't show url */
			if ( $simpleText ) {
				switch ( $item[ 'changeAction' ] ) {
					case SPC::LOG_DUPLICATE:
						$retval = $sentence ? "$text <span><em>$name</em></span> $to <em>$new</em>" : "$name -> $new";
						break;
					default:
						$retval = $sentence ? "$text <span><em>$name</em></span>" : "$name";
						break;
				}
			}
		}

		/* field handling */
		if ( $item[ 'sid' ] && $item[ 'type' ] == 'field' ) {
			/** @var SPField $field */
			$field = SPFactory::Model( 'field', true );
			$field->init( $item[ 'sid' ] );
			$name = $field ? ( $name ? : $field->name() ) : $name;
			$type = Sobi::getParam( $item[ 'params' ], 'type' );
			$type = Sobi::Txt( 'APP.' . strtoupper( $type ) );
			$from = ( $item[ 'changeAction' ] == SPC::LOG_COPY ) ? ' ' .
				Sobi::Txt( 'HISTORY_SENTENCE_SENTENCE_FROMSECTION' ) . ' ' .
				$this->getSectionName( Sobi::getParam( $item[ 'params' ], 'from' ) ) : C::ES;

			/* object still exists and edit url should be shown */
			if ( $field->get( 'nid' ) && !$plain && Sobi::Can( 'section.configure' ) ) {
				$simpleText = false;
				$url = "index.php?option=com_sobipro&task=field.edit&fid={$item['sid']}&sid={$item[ 'section' ]}";
				if ( $sentence ) {
					$retval = Sobi::Txt( 'HISTORY_SENTENCE_TYPE_FIELDS', $type ) . " <a href=\"$url\"><em>$name</em></a>" . $from;
				}
				else {
					$retval = "<a href=\"$url\">$name [$type] ({$item['sid']})</a>";
				}
			}

			/* don't show edit url */
			if ( $simpleText ) {
				if ( $sentence ) {
					$retval = Sobi::Txt( 'HISTORY_SENTENCE_TYPE_FIELDS', $type ) . " <span><em>$name</em></span>" . $from;
				}
				else {
					$retval = "$name [$type]";
					if ( $item[ 'sid' ] > 0 ) {
						$retval .= ' (' . $item[ 'sid' ] . ')';
					}
				}
			}
		}

		/* copying of files */
		if ( $item[ 'type' ] == 'file' ) {
			switch ( $item[ 'changeAction' ] ) {
				default:
				case SPC::LOG_COPYSTORAGE:
					$file = Sobi::getParam( $item[ 'params' ], 'template' );
					$name = $sentence ? "$name" : "storage/$name -&gt;  $file";
					$oText = Sobi::Txt( 'HISTORY_SENTENCE_TYPE_STORAGE' );
					break;
			}
			if ( $sentence ) {
				$retval = $oText . ( $name ? " <span><em>$name</em></span>" : C::ES );
			}
			else {
				$retval = "$name";
			}
		}

		/* configuration */
		if ( $item[ 'type' ] == 'config' ) {
			$oText = C::ES;
			switch ( $item[ 'changeAction' ] ) {
				case SPC::LOG_DELETE:
					switch ( Sobi::getParam( $item[ 'params' ], 'type' ) ) {
						case 'action-logging-all':
							$oText = Sobi::Txt( 'HISTORY_SENTENCE_TYPE_ACTIONLOGGING_ALL' );
							$name = !$sentence ? Sobi::Txt( 'HISTORY.ACTIONS' ) : C::ES;
							break;
						default:
							break;
					}
					break;
				default:
					break;
			}
			if ( $sentence ) {
				$retval = $oText . ( $name ? " <span><em>$name</em></span>" : C::ES );
			}
			else {
				$retval = "$name";
			}
		}

		/* ask other applications */
		Sobi::Trigger( 'Show', 'ActionLog', [ &$item, $sentence, $plain, &$retval ] );

		return $retval;
	}

	/**
	 * Gets the last $count history items.
	 *
	 * @param $count
	 *
	 * @return array
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function getHistorySentence( $count ): array
	{
		$history = [];
		$last = Factory::Db()
			->select( '*', 'spdb_history',
			          [ '!changeAction' => SPC::LOG_SAVE ], 'changedAt.desc', $count
			)
			->loadAssocList();

		if ( count( $last ) ) {
			foreach ( $last as $i => $item ) {
				$item[ 'params' ] = (array) json_decode( $item[ 'params' ] );
				$section = $item[ 'section' ];

				$norights = false;
				switch ( $item[ 'type' ] ) {
					case 'category':
					case 'entry':
					case 'section':
						if ( !Sobi::Can( 'section', 'access', 'any', $section ) ) {
							$norights = true;
						}
						break;
					case 'config':
					case 'field':
						if ( !Sobi::Can( 'section', 'configure', 'any', $section ) ) {
							$norights = true;
						}
						break;
					case 'acl':
						if ( !Sobi::Can( 'acl', 'config', 'any' ) ) {
							$norights = true;
						}
						break;
					case 'application':
						if ( !Sobi::Can( 'cms.apps' ) ) {
							$norights = true;
						}
						break;
				}
				if ( $norights ) {
					continue;
				}

				/* object */
				$object = $this->getObject( $item, true );

				/* section name */
				if ( $item[ 'type' ] != 'acl' ) {
					$history[ $i ][ 'section' ] = $this->getSectionName( $section );
				}

				/* action date */
				$history[ $i ][ 'date' ] = $item[ 'changedAt' ];

				/* user */
				$owner = $this->getUserUrl( $item );

				$data = [
					'object'   => $object,
					'user'     => $owner,
					'action'   => $item[ 'params' ][ 'action' ] ?? C::ES,
					'template' => $item[ 'params' ][ 'template' ] ?? C::ES ];

				$history[ $i ][ 'action' ] = $this->createSentence( $item, $data );
				if ( !$history[ $i ][ 'action' ] ) {
					unset( $history[ $i ] );
				}
			}
		}

		return array_values( $history );    /* renumber the array */
	}

	/**
	 * Returns the given log items as table formatted data.
	 *
	 * @param $items
	 *
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function getHistoryTable( &$items )
	{
		if ( is_array( $items ) && count( $items ) ) {
			foreach ( $items as $i => $item ) {
				$item[ 'params' ] = (array) json_decode( $item[ 'params' ] );

				$items[ $i ][ 'object' ] = $this->getObject( $item );
				$items[ $i ][ 'params' ] = $item[ 'params' ];   /* in case an app changed params */
				if ( $item[ 'type' ] != 'acl' ) {
					$items[ $i ][ 'section' ] = $this->getSectionName( $item[ 'section' ] );
				}
				$items[ $i ][ 'site' ] = $item[ 'site' ] == 'site' ? Sobi::Txt( 'HISTORY_SENTENCE_AREA_SITE' ) : Sobi::Txt( 'HISTORY_SENTENCE_AREA_ADM' );
				$items[ $i ][ 'source' ] = isset( $item[ 'params' ][ 'source' ] ) ? Sobi::Txt( 'APP.' . strtoupper( $item[ 'params' ][ 'source' ] ) ) : C::ES;
				$data = [
					'object'   => $this->getObject( $item, true, true ),
					'user'     => $this->getUserUrl( $item, true ),
					'action'   => $item[ 'params' ][ 'action' ] ?? C::ES,
					'template' => $item[ 'params' ][ 'template' ] ?? C::ES ];

				$items[ $i ][ 'sentence' ] = $this->createSentence( $item, $data );
				if ( !$items[ $i ][ 'sentence' ] ) {
					unset( $items[ $i ] );
				}
			}
		}
	}

	/**
	 * Gets a revision from the history table.
	 *
	 * @param $rev
	 *
	 * @return array
	 * @throws SPException
	 * @throws \Sobi\Error\Exception
	 */
	public function getRevision( $rev ): array
	{
		if ( Sobi::Cfg( 'entry.versioning', true ) ) {
			$log = ( array ) Factory::Db()
				->select( '*', 'spdb_history', [ 'revision' => $rev ] )
				->loadObject();
			if ( count( $log ) ) {
				$log[ 'changes' ] = SPConfig::unserialize( $log[ 'changes' ] );
			}

			return $log;
		}
		else {
			return [];
		}
	}

	/**
	 * Deletes a revision from the history table.
	 *
	 * @param $rev
	 *
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	public function deleteRevision( $rev )
	{
		if ( Sobi::Cfg( 'entry.versioning', true ) ) {
			try {
				Factory::Db()->delete( 'spdb_history', [ 'revision' => $rev ] );
			}
			catch ( Sobi\Error\Exception $x ) {
				Sobi::Error( __FUNCTION__, SPLang::e( 'Cannot delete history revision. Db reports %s.', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
			}
		}
	}

	/**
	 * Deletes all revisions of type SPC::LOG_SAVE for a given entry.
	 *
	 * @param $sid
	 *
	 * @throws \SPException|\Sobi\Error\Exception
	 */
	public function deleteAllRevisions( $sid )
	{
		if ( Sobi::Cfg( 'entry.versioning', true ) ) {
			try {
				Factory::Db()->delete( 'spdb_history', [ 'sid' => $sid, 'changeAction' => SPC::LOG_SAVE ] );
			}
			catch ( Sobi\Error\Exception $x ) {
				Sobi::Error( __FUNCTION__, SPLang::e( 'Cannot delete history for entry. Db reports %s.', $x->getMessage() ), C::WARNING, 0, __LINE__, __FILE__ );
			}
		}
	}

	/**
	 * @param $item
	 * @param bool $plain
	 *
	 * @return string
	 */
	protected function getUserUrl( $item, bool $plain = false ): string
	{
		if ( $item[ 'uid' ] ) {
			if ( $plain ) {
				$owner = "<span><em>{$item[ 'userName' ]}</em></span>";
			}
			else {
				$url = SPUser::userUrl( $item[ 'uid' ] );
				$owner = "<a href=\"$url\"><em>{$item[ 'userName' ]}</em></a>";
			}
		}
		else {
			$owner = '<span><em>' . Sobi::Txt( 'LOG_GUEST' ) . '</em></span>';
		}

		return $owner;
	}

	/**
	 * @param $section
	 *
	 * @return string
	 * @throws \SPException
	 * @throws \Sobi\Error\Exception
	 */
	protected function getSectionName( $section ): string
	{
		$sectionName = $section ? SPLang::translateObject( $section, 'name', 'section' ) : C::ES;
		$sectionName = $sectionName ? $sectionName[ $section ][ 'value' ] : C::ES;

		return ( $sectionName == C::ES ) && $section ? '<em>' . Sobi::Txt( 'HISTORY.DELETED' ) . ' (' . $section . ')</em>' : $sectionName;
	}

	/**
	 * @param array $item
	 * @param array $data
	 *
	 * @return string
	 */
	protected function createSentence( array $item, array $data ): string
	{
		$key = $item[ 'key' ] ?? C::ES;
		if ( !$key ) {
			switch ( $item[ 'changeAction' ] ) {
				case SPC::LOG_PUBLISH:
					$key = $item[ 'uid' ] == 0 ? 'HISTORY_SENTENCE_PUBLISH_GUEST' : 'HISTORY_SENTENCE_PUBLISH_USER';
					break;
				case SPC::LOG_UNPUBLISH:
					$key = $item[ 'uid' ] == 0 ? 'HISTORY_SENTENCE_UNPUBLISH_GUEST' : 'HISTORY_SENTENCE_UNPUBLISH_USER';
					break;
				case SPC::LOG_APPROVE:
					$key = $item[ 'uid' ] == 0 ? 'HISTORY_SENTENCE_APPROVE_GUEST' : 'HISTORY_SENTENCE_APPROVE_USER';
					break;
				case SPC::LOG_UNAPPROVE:
					$key = $item[ 'uid' ] == 0 ? 'HISTORY_SENTENCE_UNAPPROVE_GUEST' : 'HISTORY_SENTENCE_UNAPPROVE_USER';
					break;
				case SPC::LOG_REJECT:
					$key = 'HISTORY_SENTENCE_REJECT';
					break;
				case SPC::LOG_DISCARD:
					$key = 'HISTORY_SENTENCE_DISCARD';
					break;
				case SPC::LOG_DELETE:
					$key = $item[ 'uid' ] == 0 ? 'HISTORY_SENTENCE_DELETE_GUEST' : 'HISTORY_SENTENCE_DELETE_USER';
					break;
				case SPC::LOG_SAVE:
					$key = $item[ 'uid' ] == 0 ? 'HISTORY_SENTENCE_SAVE_GUEST' : 'HISTORY_SENTENCE_SAVE_USER';
					break;
				case SPC::LOG_EDIT:
					$key = $item[ 'uid' ] == 0 ? 'HISTORY_SENTENCE_EDIT_GUEST' : 'HISTORY_SENTENCE_EDIT_USER';
					break;
				case SPC::LOG_ADD:
					$key = $item[ 'uid' ] == 0 ? 'HISTORY_SENTENCE_ADD_GUEST' : 'HISTORY_SENTENCE_ADD_USER';
					break;
				case SPC::LOG_ACTION:
					$key = $item[ 'uid' ] == 0 ? 'HISTORY_SENTENCE_ACTION_GUEST' : 'HISTORY_SENTENCE_ACTION_USER';
					break;
				case SPC::LOG_ADDINDIRECT:
					$key = 'HISTORY_SENTENCE_ADDINDIRECT';
					break;
				case SPC::LOG_REMOVE:
					$key = 'HISTORY_SENTENCE_REMOVE';
					break;
				case SPC::LOG_DOWNLOAD:
					$key = 'HISTORY_SENTENCE_DOWNLOAD';
					break;
				case SPC::LOG_INSTALL:
				case SPC::LOG_REPOINSTALL:
					$key = 'HISTORY_SENTENCE_INSTALL';
					break;
				case SPC::LOG_REPOREGISTER:
					$key = 'HISTORY_SENTENCE_REGISTER';
					break;
				case SPC::LOG_REPOFETCH:
					$key = 'HISTORY_SENTENCE_FETCH';
					break;
				case SPC::LOG_CLONE:
				case SPC::LOG_DUPLICATE:
					$key = 'HISTORY_SENTENCE_DUPLICATE';
					break;
				case SPC::LOG_COPY:
					$key = 'HISTORY_SENTENCE_COPY';
					break;
				case SPC::LOG_ENABLE:
					$key = 'HISTORY_SENTENCE_ENABLE';
					break;
				case SPC::LOG_DISABLE:
					$key = 'HISTORY_SENTENCE_DISABLE';
					break;
				case SPC::LOG_REQUIRED:
					$key = 'HISTORY_SENTENCE_REQUIRE';
					break;
				case SPC::LOG_UNREQUIRED:
					$key = 'HISTORY_SENTENCE_UNREQUIRE';
					break;
				case SPC::LOG_EDITABLE:
					$key = 'HISTORY_SENTENCE_EDITABLE';
					break;
				case SPC::LOG_UNEDITABLE:
					$key = 'HISTORY_SENTENCE_UNEDITABLE';
					break;
				case SPC::LOG_FREE:
					$key = 'HISTORY_SENTENCE_FREE';
					break;
				case SPC::LOG_UNFREE:
					$key = 'HISTORY_SENTENCE_UNFREE';
					break;
				case SPC::LOG_COPYSTORAGE:
					$key = 'HISTORY_SENTENCE_COPYSTORAGE';
					break;
				default:
					$key = C::ES;
					break;
			}
		}

		return $this->parseSentence( $key, $data );
	}

	/**
	 * @param $key
	 * @param array $data
	 *
	 * @return string
	 */
	protected function parseSentence( $key, array $data = [] ): string
	{
		$key = Sobi::Txt( $key );
		if ( count( $data ) ) {
			while ( strstr( $key, 'var:[' ) ) {
				preg_match( '/var\:\[([a-zA-Z0-9\.\_\-]*)\]/', $key, $matches );
				$value = $data[ $matches[ 1 ] ];
				if ( is_string( $value ) || is_numeric( $value ) ) {
					$key = str_replace( $matches[ 0 ], $value, $key );
				}
				else {
					$key = str_replace( $matches[ 0 ], C::ES, $key );
				}
			}
		}

		return $key;
	}
}