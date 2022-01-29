<?php namespace spitfire\core\event;

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
 * The event dispatcher acts as the central hub where event listeners are hosted
 * and where events are sent. The event dispatcher can be used in two notable ways:
 * 
 * * Writing global listeners with the global event() function
 * * Writing scoped listeners by instantiating it within the class you need to provide extensions too
 * 
 * Our first way is more convenient when handling global plugins that modify the
 * behavior of an application generically.
 * 
 * The second allows to target certain components of our application explicitly
 * while reducing the risk of collissions.
 */
class EventDispatcher
{
	
	private $listeners = [];
	
	/**
	 * Creates a listener and attaches it. This will allow the listener to receive
	 * events that happen whenever an event with the same event name is fired and
	 * dispatched.
	 * 
	 * @param string $for
	 * @param Closure|callable $fn
	 * @return \spitfire\core\event\Listener
	 */
	public function on(string $for): Listener
	{
		
		if (!isset($this->listeners[$for])) {
			$this->listeners[$for] = new Listener();
		}
		
		return $this->listeners[$for];
	}
	
	/**
	 * Dispatches an event to all listeners that are expecting the event.
	 * 
	 * @param \spitfire\core\event\Event $event
	 * @return type
	 */
	public function dispatch(Event$event)
	{
		
		if (!isset($this->listeners[$event->getAction()])) {
			return;
		}
		
		$this->listeners[$event->getAction()]->handle($event);
	}
}
