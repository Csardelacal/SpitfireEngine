<?php namespace spitfire\io\curl;

/* 
 * The MIT License
 *
 * Copyright 2018 César de la Cal Bretschneider <cesar@magic3w.com>.
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

class Request
{
	
	private $method = 'GET';
	
	private $url;
	
	private $headers = [];
		
	private $body = [];
	
	public function __construct($url) {
		$this->url = URLReflection::fromURL($url);
	}
	
	public function header($name, $value) {
		$this->headers[$name] = $value;
	}
	
	public function post($parameter, $value = null) {
		$this->method = 'POST';
		
		if ($value === null) {
			$this->body = $parameter;
		}
		else {
			$this->body[$parameter] = $value;
		}
		
		return $this;
	}
	
	public function send() {
		$ch = curl_init((string)$this->url);
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		if (!empty($this->body)) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $this->body);
		}
		
		/*
		 * Set the appropriate headers for the request (in case we need some special
		 * headers that do not conform)
		 */
		curl_setopt($ch, CURLOPT_HTTPHEADER, array_map(function ($k, $v) { 
			return "$k : $v"; 
		}, array_keys($this->headers), $this->headers));
		
		//Todo: CURLOPT_PROGRESSFUNCTION
	}
}
