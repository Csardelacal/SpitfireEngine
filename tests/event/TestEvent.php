<?php namespace tests\spitfire\event;

/*
 *
 * Copyright (C) 2023-2023 César de la Cal Bretschneider <cesar@magic3w.com>.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-13 01  USA
 *
 */


use spitfire\event\Event;

/**
 * An event records that something happened on the system. By nature, events are
 * immutable. This means that Listeners can subscribe to events and produce side
 * effects, but the event itself cannot be modified.
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class TestEvent extends Event
{
	private $payload;
	
	public function __construct(string $payload)
	{
		$this->payload = $payload;
	}
	
	public function payload()
	{
		return $this->payload;
	}
	
	public function setPayload(string $payload)
	{
		$this->payload = $payload;
	}
}
