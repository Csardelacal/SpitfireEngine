<?php namespace spitfire\storage\database\pagination;

use spitfire\core\http\URL;

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

interface RendererInterface
{
	/**
	 * This method allows the paginator to define the URL (and parameter within it)
	 * to be replaced across pages with different numbers.
	 * 
	 * @param URL $url
	 * @param string $parameter
	 */
	public function setURL(URL$url, $parameter);
	
	public function emptyResultMessage();
	
	public function page($number);
	
	public function current($number);
	
	public function previous($number);
	public function next($number);
	
	public function first();
	public function last($number);
	
	public function before();
	public function after();
	
	public function gap();
	public function jumpTo($current, $total);
	public function pageOf($current, $total);
	
}
