<?php namespace spitfire\mvc\middleware\standard;

use spitfire\core\ContextInterface;
use spitfire\core\Response;
use spitfire\mvc\middleware\MiddlewareInterface;

/* 
 * The MIT License
 *
 * Copyright 2019 César de la Cal Bretschneider <cesar@magic3w.com>.
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

class SynchronizedMiddleware implements MiddlewareInterface
{
	
	private $fh;

	public function before(ContextInterface $context) {
		
		if (!array_key_exists('synchronized', $context->annotations)) {
			return;
		}
		
		if (!file_exists(sprintf('%s/bin/usr/lock/', basedir()))) {
			mkdir(sprintf('%s/bin/usr/lock/', basedir()));
		}
		
		$file = sprintf('%s/bin/usr/lock/.%s_%s', basedir(), str_replace('\\', '-', get_class($context->director?? $context->controller)), $context->action);
		$this->fh = fopen($file, file_exists($file)? 'r' : 'w+');
		
		flock($this->fh, LOCK_EX);
	}
	
	public function after(ContextInterface $context, Response $response = null) {
		$this->fh && flock($this->fh, LOCK_UN);
	}

}
