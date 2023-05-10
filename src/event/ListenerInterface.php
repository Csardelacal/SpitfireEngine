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


/**
 * Classes that implement this interface are allowed to listen for events using
 * spitfire's event mechanism.
 *
 * Whenever the listener gets invoked, your react method will be called and you
 * can handle the event. For the specifics of a particular event, please refer to
 * the vendor's documentation.
 *
 * @template T of Event
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
interface ListenerInterface
{
	
	/**
	 * This method is invoked whenever an event that this listener is registered
	 * for is fired. Please note that the method may not be invoked if the event
	 * was earlier cancelled.
	 *
	 * @param T $event
	 * @return mixed
	 */
	public function react(Event $event);
}
