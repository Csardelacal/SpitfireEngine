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
 * A hook dispatcher is just a small collection of listeners that are attached
 * to a certain hook.
 *
 * This class provides a simple iterator for the dispatch method to alleviate the
 * complexity of the Event dispatcher. This is not a public class.
 *
 * @template T of Event
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class HookDispatcher
{
	
	/**
	 * The array containing the listeners to be invoked whenever the hook that this
	 * dispatcher represents is invoked.
	 *
	 * @var ListenerInterface<T>[]
	 */
	private $listeners = [];
	
	/**
	 * Push a new listener onto the list. Whenever the system dispatches an event
	 * with the appropriate hook for this to be executed, the system will invoke
	 * this listener.
	 *
	 * @param ListenerInterface<T> $listener
	 * @return $this
	 */
	public function add(ListenerInterface$listener)
	{
		$this->listeners[] = $listener;
		return $this;
	}
	
	/**
	 * Dispatches the event to the listeners. Listeners have the option to return
	 * a value that (if not overridden by a later listener) will be returned to the
	 * source to use.
	 *
	 * @param T $event
	 * @return mixed
	 */
	public function dispatch(Event $event)
	{
		$listeners = $this->listeners;
		$t = null;
		
		while (!$event->isStopped() && !empty($listeners)) {
			/*@var $listener ListenerInterface*/
			$listener = array_shift($listeners);
			$t = $listener->react($event);
		}
		
		return $t;
	}
}
