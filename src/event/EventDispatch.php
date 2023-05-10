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

class EventDispatch
{
	
	/**
	 *
	 * @var EventDispatch|null
	 */
	private $parent = null;
	
	/**
	 *
	 * @var HookDispatcher<Event>[]
	 */
	private $hooks;
	
	public function __construct(EventDispatch $parent = null)
	{
		$this->parent = $parent;
		$this->hooks = [];
	}
	
	/**
	 *
	 * @template T of Event
	 * @param class-string<T> $name
	 * @param ListenerInterface<T> $listener
	 * @return ListenerInterface<T>
	 */
	public function hook(string $name, ListenerInterface $listener) : ListenerInterface
	{
		
		if (!isset($this->hooks[$name])) {
			$this->hooks[$name] = new HookDispatcher();
		}
		
		$this->hooks[$name]->add($listener);
		return $listener;
	}
	
	/**
	 * Dispatches an event. Depending on the type of event we will have two different
	 * behaviors:
	 *
	 * 1. Observers - The listeners will only perform side effects.
	 * 2. Mutators  = The listeners can affect the output and are therefore usually mutually exclusive.
	 *
	 * Examples for observers are events that perform tasks like notifying the user
	 * of a new message via email, which does not stop the system from notifying the
	 * user (for example) via SMS.
	 *
	 * A mutating event will be an event where the target application expects a single
	 * outcome. For example, if the system has a hook to print the url of the homepage
	 * to the buffer.
	 *
	 * Usually, the application will invoke it like this:
	 * <code>&lt;?= $this->event->dispatch('myapp.output.url.homepage', function () { return url(); }); </code>
	 *
	 * The listener for our custom homepage link may look something like this:
	 * <code>
	 * spitfire()->getApplication('forum')->event->hook('myapp.output.url.homepage',
	 *   new Listener(function (Event$event) {
	 *     $event->preventDefault();
	 *     return 'https://mywebsite.com';
	 *   }
	 * );
	 * </code>
	 *
	 * Please note that mutators inherently compete for the output / return of the function,
	 * this means that they should only be used carefully and without adding many listeners
	 * to them since their behavior will become less predictable.
	 *
	 * Also, you can use this system to create complicated mutators by making use
	 * of closures, etc, but 90% of the mutators are really simple output  managing
	 * functions, so that's what we're using here.
	 *
	 * Please note: The exact specification of how a payload for an event is handled
	 * really depends on the vendor of the event, please refer to their documentation
	 * for specifics.
	 *
	 * @param Event $event
	 * @param Closure|null $continue
	 * @return mixed
	 */
	public function dispatch(Event $event, Closure $continue = null)
	{
		
		/*
		 * By default, the return of an event will be null. This means that no listener
		 * interacted with the event.
		 */
		$_r = null;
		$hook = get_class($event);
		
		/*
		 * If a listener that is on the current event source wishes to interact with
		 * our event, we will prioritize these over the elements that were registered
		 * in higher levels.
		 */
		if (isset($this->hooks[$hook])) {
			$t  = $this->hooks[$hook]->dispatch($event);
			$_r = $t === null? $_r : $t;
		}
		
		/*
		 * If our hook was stopped by any of the listeners we already interacted with,
		 * the system should return the value we received earlier.
		 */
		if ($event->isStopped()) {
			return $_r;
		}
		
		/*
		 * Otherwise, if the event bubbles and it has a parent, we continue within
		 * the parent.
		 */
		if ($event->bubbles() && $this->parent) {
			$t = $this->parent->dispatch($event, $continue);
			return $t? $t : $_r;
		}
		
		/*
		 * A listener can request the event to continue bubbling and allow the other
		 * hooks to interact with the event, but the listener may have requested the
		 * original code to not be executed.
		 */
		if ($event->isPrevented()) {
			return $_r;
		}
		
		/**
		 * By default we fall back to the original application's predefined behavior.
		 * This allows the application to react to behavior that was not overridden.
		 */
		return $continue? $continue($event) : $_r;
	}
}
