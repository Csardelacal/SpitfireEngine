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


class Event
{
	
	private $payload;
	
	private $bubbles = true;
	private $stopped = false;
	private $preventDefault = false;
	
	
	public function __construct($payload, $options = []) {
		$this->payload = $payload;
		foreach ($options as $option => $value) { $this->$option = $value; }
	}
	
	public function payload() {
		return $this->payload;
	}
	
	public function bubbles() {
		return $this->bubbles;
	}
	
	public function isStopped() {
		return $this->stopped;
	}
	
	public function isPrevented() {
		return $this->preventDefault;
	}
	
	public function preventDefault($set = true) {
		$this->preventDefault = $set;
		return $this;
	}
	
	public function stopPropagation($set = true) {
		$this->stopped = $set;
		return $this;
	}
}
