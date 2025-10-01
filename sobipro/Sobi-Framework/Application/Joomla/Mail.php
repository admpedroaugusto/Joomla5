<?php
/**
 * @package: Sobi Framework
 *
 * @author
 * Name: Sigrid Suski & Radek Suski, Sigsiu.NET GmbH
 * Url: https://www.Sigsiu.NET
 *
 * @copyright Copyright (C) 2011-2024 Sigsiu.NET GmbH (https://www.sigsiu.net). All rights reserved.
 * @license GNU/LGPL Version 3
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License version 3
 * as published by the Free Software Foundation, and under the additional terms according to section 7 of GPL v3.
 * See https://www.gnu.org/licenses/lgpl.html and https://www.sigsiu.net/licenses.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * @created 07 July 2021 by Radek Suski
 * @modified 27 October 2022 by Sigrid Suski
 */

//declare( strict_types=1 );

namespace Sobi\Application\Joomla;

use Joomla\CMS\Factory;
use Sobi\C;
use Joomla\CMS\Mail\{Mail as JMail, MailHelper as JMailHelper};
use Sobi\Lib\{Instance, ParamsByName};
use Sobi\Interfaces\Application\MailInterface;

/**
 * class Mail
 */
class Mail implements MailInterface
{
	use Instance;
	use ParamsByName;

	/**
	 * @var \Joomla\CMS\Mail\Mail
	 */
	protected $mailer;

	/**
	 * Mail constructor.
	 */
	protected function __construct()
	{
//		$this->mailer = JMail::getInstance();
		$this->mailer = Factory::getMailer();
	}

	/**
	 * @param string $recipient
	 * @param string $name
	 *
	 * @return \Sobi\Interfaces\Application\MailInterface
	 * @throws \PHPMailer\PHPMailer\Exception
	 */
	public function & addRecipient( string $recipient, string $name = C::ES ): MailInterface
	{
		JMailHelper::cleanLine( $recipient );
		if ( JMailHelper::isEmailAddress( $recipient ) ) {
			$this->mailer->addRecipient( $recipient, $name );
		}

		return $this;
	}

	/**
	 * @param string $address
	 * @param string $name
	 *
	 * @return \Sobi\Interfaces\Application\MailInterface
	 * @throws \PHPMailer\PHPMailer\Exception
	 */
	public function & setSender( string $address, string $name = C::ES ): MailInterface
	{
		JMailHelper::cleanLine( $address );
		if ( JMailHelper::isEmailAddress( $address ) ) {
			$this->mailer->setSender( [ $address, $name ] );
		}

		return $this;
	}

	/**
	 * @param string $subject
	 *
	 * @return \Sobi\Interfaces\Application\MailInterface
	 */
	public function & setSubject( string $subject ): MailInterface
	{
		$this->mailer->setSubject( JMailHelper::cleanLine( $subject ) );

		return $this;
	}

	/**
	 * @param string $content
	 *
	 * @return \Sobi\Interfaces\Application\MailInterface
	 */
	public function & setBody( string $content ): MailInterface
	{
		$this->mailer->setBody( JMailHelper::cleanBody( $content ) );

		return $this;
	}

	/**
	 * @param string $address
	 * @param string $name
	 *
	 * @return \Sobi\Interfaces\Application\MailInterface
	 * @throws \PHPMailer\PHPMailer\Exception
	 */
	public function & addAddress( string $address, string $name = C::ES ): MailInterface
	{
		JMailHelper::cleanLine( $address );
		if ( JMailHelper::isEmailAddress( $address ) ) {
			$this->mailer->addAddress( $address, $name );
		}

		return $this;
	}

	/**
	 * @return \Sobi\Interfaces\Application\MailInterface
	 */
	public function & clearAddresses(): MailInterface
	{
		$this->mailer->clearAllRecipients();
		$this->mailer->clearReplyTos();

		return $this;
	}

	/**
	 * @param bool $isHTML
	 *
	 * @return \Sobi\Interfaces\Application\MailInterface
	 */
	public function & isHTML( bool $isHTML = true ): MailInterface
	{
		$this->mailer->isHtml( $isHTML );

		return $this;
	}

	/**
	 * @param string $address
	 * @param string $name
	 *
	 * @return \Sobi\Interfaces\Application\MailInterface
	 * @throws \PHPMailer\PHPMailer\Exception
	 */
	public function & addCC( string $address, string $name = C::ES ): MailInterface
	{
		JMailHelper::cleanLine( $address );
		if ( JMailHelper::isEmailAddress( $address ) ) {
			$this->mailer->addCC( $address, $name );
		}

		return $this;
	}

	/**
	 * @param string $address
	 * @param string $name
	 *
	 * @return \Sobi\Interfaces\Application\MailInterface
	 * @throws \PHPMailer\PHPMailer\Exception
	 */
	public function & addBCC( string $address, string $name = C::ES ): MailInterface
	{
		JMailHelper::cleanLine( $address );
		if ( JMailHelper::isEmailAddress( $address ) ) {
			$this->mailer->addBCC( $address, $name );
		}

		return $this;
	}

	/**
	 * @param string $address
	 * @param string $name
	 *
	 * @return \Sobi\Interfaces\Application\MailInterface
	 * @throws \PHPMailer\PHPMailer\Exception
	 */
	public function & addReplyTo( string $address, string $name = C::ES ): MailInterface
	{
		JMailHelper::cleanLine( $address );
		if ( JMailHelper::isEmailAddress( $address ) ) {
			$this->mailer->addReplyTo( $address, $name );
		}

		return $this;
	}

	/**
	 * @return bool
	 * @throws \PHPMailer\PHPMailer\Exception
	 */
	public function send(): bool
	{
		return $this->mailer->Send();
	}

	/**
	 * @param string $file
	 * @param string $name
	 * @param string $encoding
	 * @param string $type
	 * @param string $disposition
	 *
	 * @return \Sobi\Interfaces\Application\MailInterface
	 * @throws \PHPMailer\PHPMailer\Exception
	 */
	public function & addAttachment( string $file, string $name = C::ES, string $encoding = 'base64', string $type = 'application/octet-stream', string $disposition = 'attachment' ): MailInterface
	{
		$this->mailer->addAttachment( $file, $name, $encoding, $type, $disposition );

		return $this;
	}

	/**
	 * @param string $cert
	 * @param string $key
	 * @param string $pass
	 * @param string $extracerts
	 *
	 * @return \Sobi\Interfaces\Application\MailInterface
	 */
	public function & sign( string $cert, string $key, string $pass, $extracerts = C::ES ): MailInterface
	{
		$this->mailer->sign( $cert, $key, $pass, $extracerts );

		return $this;
	}
}
