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
 * Mutable events allow the plugins to modify the payload of the event, providing
 * the application with the ability to override the payload.
 * 
 * This is interesting in applications where the payload is passed with the intent
 * of receiving a modified version from the plugin.
 * 
 * For example, if your application wishes to use a plugin to generate a URl to the
 * user's profile, overriding the generic URL that the system may generate itself,
 * you can do something like:
 * 
 * event(new MutableEvent('user.profile.url', $username));
 * 
 * Your plugin can then have a listener like this registered:
 * 
 * event()->on('user.profile.url', function (MutableEvent$e) { $e->setBody('https://yourhomepage/user/' . $e->getOriginal()); });
 * 
 * Since your plugin is always referring to the original body of the event, you 
 * never risk generating conflicts (beyond overwriting) between the plugins. So,
 * for example, if your application registers both:
 * 
 * event()->on('user.profile.url', function (MutableEvent$e) { $e->setBody('https://yourhomepage/user/' . $e->getOriginal()); });
 * event()->on('user.profile.url', function (MutableEvent$e) { $e->setBody('https://youraccountserver/user/' . $e->getOriginal()); });
 * 
 * The system simply will pick the last one, overriding the first one.
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class MutableEvent extends Event
{
	
	private $override = null;
	
	public function getBody()
	{
		return $this->override?? parent::getBody();
	}
	
	public function getOriginal()
	{
		return parent::getBody();
	}
	
	public function setBody($value)
	{
		return $this->override = $value;
	}
}
