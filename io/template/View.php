<?php namespace spitfire\io\template;

use Phug\Phug;
use Psr\Http\Message\StreamInterface;

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

 /**
  * The view class allows to pass a filename and a set of data for the system to
  * generate a string containing the generated html for the view.
  * 
  * @todo Allow to configure the flags for caching and debugging
  * @todo Implement other engines than pug so we can also generate proper JSON templates
  * @todo Template should inherit from StreamInterface in the future so response
  * can be sent directly from the 
  */
class View
{
	
	/**
	 * The file to be used for rendering. We currently only support pug
	 * files for this.
	 * 
	 * @var string
	 */
	private $file;
	
	/**
	 * 
	 * @var array<string,mixed>
	 */
	private $data;
	
	public function __construct(string $file, array $data) 
	{
		$this->file = $file;
		$this->data = $data;
	}
	
	/**
	 * Generate a string from the provided template file and data.
	 * 
	 * @return string
	 */
	public function render() : string
	{
		/**
		 * Just render the file using pug
		 */
		return Phug::renderFile($this->file, $this->data, [
			'debug' => false,
			'cache_dir' => spitfire()->locations()->storage('cache/views/')
		]);
	}
}
