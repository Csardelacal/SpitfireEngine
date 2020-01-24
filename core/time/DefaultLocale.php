<?php namespace spitfire\core\time;

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

class DefaultLocale extends \spitfire\locale\Locale
{
	
	private $messages = [
		'%s second ago' => ['%s seconds ago', 'One second ago', '%s seconds ago'],
		'%s minute ago' => ['%s minutes ago', 'One minute ago', '%s minutes ago'],
		'%s hour ago' => ['%s hours ago', 'One hour ago', '%s hours ago'],
		'%s day ago' => ['%s days ago', 'One day ago', '%s days ago'],
		'%s week ago' => ['%s weeks ago', 'One week ago', '%s weeks ago'],
		'%s month ago' => ['%s months ago', 'One month ago', '%s months ago'],
		'%s year ago' => ['%s years ago', 'One year ago', '%s years ago'],
		'%s second in' => ['in %s seconds', 'in one second', 'in %s seconds'],
		'%s minute in' => ['in %s minutes', 'in one minute', 'in %s minutes'],
		'%s hour in' => ['in %s hours', 'in one hour', 'in %s hours'],
		'%s day in' => ['in %s days', 'in one day', 'in %s days'],
		'%s week in' => ['in %s weeks', 'in one week', 'in %s weeks'],
		'%s month in' => ['in %s months', 'in one month', 'in %s months'],
		'%s year in' => ['in %s years', 'in one year', 'in %s years'],
	];
	
	public function getCurrency(): \spitfire\locale\CurrencyLocalizer {
		return null;
	}

	public function getDateFormatter(): \spitfire\locale\DateFormatter {
		return null;
	}

	public function getMessage($msgid) {
		return $this->messages[$msgid]?? '';
	}

}
