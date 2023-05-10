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
