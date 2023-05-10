<?php namespace spitfire\event;

/*
 * The MIT License
 *
 * Copyright 2020 César de la Cal Bretschneider <cesar@magic3w.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * A hook dispatcher is just a small collection of listeners that are attached
 * to a certain hook.
 *
 * This class provides a simple iterator for the dispatch method to alleviate the
 * complexity of the EventDispatcher. This is not a public class.
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
