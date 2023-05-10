<?php namespace spitfire\event;

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


use Closure;

/**
 * This simplified listener allows to use a closure to listen for events instead
 * of defining your own listener class.
 *
 * @template T of Event
 * @implements ListenerInterface<T>
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class Listener implements ListenerInterface
{
	
	/**
	 *
	 * @var Closure
	 */
	private $callable;
	
	public function __construct(Closure $callable)
	{
		$this->callable = $callable;
	}
	
	public function react(Event $event)
	{
		return ($this->callable)($event);
	}
}
