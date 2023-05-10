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
 * An event records that something happened on the system. By nature, events are
 * immutable. This means that Listeners can subscribe to events and produce side
 * effects, but the event itself cannot be modified.
 *
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
abstract class Event
{
	
	/**
	 * Whether the event will be raised to a higher dispatcher if the current one
	 * did not stop the propagation to higher dispatchers.
	 *
	 * @var bool
	 */
	private $bubbles = true;
	
	/**
	 * Indicates whether the event is requested to stop by any of the listeners.
	 *
	 * @var bool
	 */
	private $stopped = false;
	
	/**
	 * The component dispatching the event can provide a default callable to be executed
	 * in the event of the hooks not capturing (stopping / preventing) the event.
	 *
	 * If this flag is set to true by the time it reaches the default callable, this
	 * callable will not be executed.
	 *
	 * @var bool
	 */
	private $preventDefault = false;
	
	/**
	 * Returns true if the event is intended to bubble up to higher up dispatchers.
	 * When using bubbling events please make sure to use very specific naming to prevent
	 * applications from creating event collisions, these can often be fatal for
	 * applications.
	 *
	 * @return bool
	 */
	public function bubbles() : bool
	{
		return $this->bubbles;
	}
	
	/**
	 * Returns true if the event was stopped and is not intended to be passed to
	 * another listener.
	 *
	 * Some listeners may require an event to be stopped from continuing, this is
	 * often the case when dealing with events that, if processed by many listeners,
	 * could generate a destructive interference.
	 *
	 * @return bool
	 */
	public function isStopped() : bool
	{
		return $this->stopped;
	}
	
	/**
	 * Indicates that this event was prevented from being passed to the default.
	 * Please note that stopping an event's propagation implies it not being passed
	 * to the default.
	 *
	 * @return bool
	 */
	public function isPrevented() : bool
	{
		return $this->preventDefault;
	}
	
	/**
	 * Prevents the event from being passed to the default handler that the source
	 * provided for handling the event.
	 *
	 * @param bool $set
	 * @return Event
	 */
	public function preventDefault(bool$set = true) : Event
	{
		$this->preventDefault = $set;
		return $this;
	}
	
	/**
	 * Prevents the event from being passed to other listeners, terminating it after
	 * this listener has concluded executing it's body.
	 *
	 * @param bool $set
	 * @return Event
	 */
	public function stopPropagation(bool$set = true) : Event
	{
		$this->stopped = $set;
		return $this;
	}
	
	/**
	 * Determines whether the event is allowed to bubble. Bubbling means that the event
	 * is passed to a parent dispatcher if the dispatch could not handle it.
	 *
	 * @param bool $set
	 * @return self
	 */
	public function preventBubbling(bool $set = true) : self
	{
		$this->bubbles = !$set;
		return $this;
	}
}
