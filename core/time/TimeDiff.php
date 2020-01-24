<?php namespace spitfire\core\time;

use spitfire\exceptions\PrivateException;

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
 * The time difference object is the result of calculating the relative time between
 * two timestamps. This is not intended to provide an accurate result, but instead
 * is used to generate end user representation of a time difference.
 * 
 * We used to generate a string like '30 seconds ago'. But over time we realized 
 * that this reduced the flexibility in a serious manner when working with applications
 * that would rathe (for example) display a shorter version of the string.
 * 
 * This also allows applications to programmatically analyze the result, for example,
 * an application that for posts older than a year whishes to present the date, we 
 * could do something like:
 * 
 * <code>
 * $diff = Time::relative($time);
 * echo $diff->unit() == Time::YEAR? date('m/d/Y', $time) : $diff;
 * </code>
 */
class TimeDiff
{
	
	const REL_FUTURE = true;
	const REL_PAST = false;
	
	private $future;
	private $amt;
	private $unit;
	
	private $locale;
	
	
	public function __construct($future, $amt, $unit) {
		$this->future = $future;
		$this->amt = $amt;
		$this->unit = $unit;
		
		try                          { $this->locale = _t()->domain('spitfire.time'); } 
		catch (PrivateException $ex) { $this->locale = new DefaultLocale(); }
	}
	
	public function amt() {
		return $this->amt;
	}
	
	public function unit() {
		return $this->unit;
	}
	
	public function future() {
		return $this->future;
	}

	public function __toString() {
		return (string)$this->locale->say(sprintf('%%s %s %s', $this->unit, $this->future? 'in' : 'ago'), $this->amt);
	}
	
}
