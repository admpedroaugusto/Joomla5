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
 * @created 13 July 2021 by Radek Suski
 * @modified 27 October 2022 by Sigrid Suski
 */

namespace Sobi\Interfaces\Application;

use Sobi\C;

/**
 *
 */
interface MailInterface
{
	public static function & Instance();

	public function & addRecipient( string $recipient, string $name = C::ES ): self;

	public function & setSender( string $from ): self;

	public function & setSubject( string $subject ): self;

	public function & setBody( string $content ): self;

	public function & addAddress( string $address, string $name = C::ES ): self;

	public function & clearAddresses(): self;

	public function & isHTML( bool $isHTML = true ): self;

	public function & addCC( string $address, string $name = C::ES ): self;

	public function & addBCC( string $address, string $name = C::ES ): self;

	public function & addReplyTo( string $address, string $name = C::ES ): self;

	public function & addAttachment( string $file, string $name = C::ES, string $encoding = 'base64', string $type = 'application/octet-stream', string $disposition = 'attachment' ): self;

	public function & sign( string $cert, string $key, string $pass, $extracerts = C::ES ): self;

	public function send(): bool;
}
