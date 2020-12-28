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
 * Classes that implement this interface are allowed to listen for events using
 * spitfire's event mechanism.
 * 
 * Whenever the listener gets invoked, your react method will be called and you 
 * can handle the event. For the specifics of a particular event, please refer to
 * the vendor's documentation.
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
interface ListenerInterface
{
	
	/**
	 * This method is invoked whenever an event that this listener is registered
	 * for is fired. Please note that the method may not be invoked if the event
	 * was earlier cancelled.
	 * 
	 * @param Event $event
	 */
	public function react(Event$event);
	
}
