<?php namespace spitfire\log;

use Psr\Log\LoggerInterface;

/*
 * The MIT License
 *
 * Copyright 2021 César de la Cal Bretschneider <cesar@magic3w.com>.
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
 * This is a really simple class that performs passthrough operations for the 
 * log. This allows the application to function without a proper logger
 */
class ConsoleLogger implements LoggerInterface 
{
	
	/**
	 * 
	 * @see https://www.php-fig.org/psr/psr-3/ For the source of this method
	 * @param string $message
	 * @param array<mixed> $context
	 * @return string
	 */
	function interpolate($message, array $context = array()) 
	{
		// build a replacement array with braces around the context keys
		$replace = array();
		foreach ($context as $key => $val) {
			// check that the value can be cast to string
			if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
				$replace['{' . $key . '}'] = $val;
			}
		}
		
		// interpolate replacement values into the message and return
		return strtr($message, $replace);
	}
	
	public function alert($message, array $context = array()): void 
	{
		console()->error($this->interpolate($message, $context))->ln();
	}
	
	public function critical($message, array $context = array()): void 
	{
		console()->error($this->interpolate($message, $context))->ln();
	}
	
	public function debug($message, array $context = array()): void 
	{
		console()->error($this->interpolate($message, $context))->ln();
	}

	public function emergency($message, array $context = array()): void 
	{
		console()->error($this->interpolate($message, $context))->ln();
	}

	public function error($message, array $context = array()): void 
	{
		console()->error($this->interpolate($message, $context))->ln();
	}

	public function info($message, array $context = array()): void 
	{
		console()->info($this->interpolate($message, $context))->ln();
	}

	public function log($level, $message, array $context = array()): void 
	{
		console()->info($level . ': ' . $this->interpolate($message, $context))->ln();
	}

	public function notice($message, array $context = array()): void 
	{
		console()->info($this->interpolate($message, $context))->ln();
	}

	public function warning($message, $context = array()): void 
	{
		console()->error($this->interpolate($message, $context))->ln();
	}

}
