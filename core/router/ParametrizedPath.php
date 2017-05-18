<?php namespace spitfire\core\router;

use spitfire\core\Path;

/* 
 * The MIT License
 *
 * Copyright 2017 César de la Cal Bretschneider <cesar@magic3w.com>.
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

class ParametrizedPath extends Path
{
	
	/**
	 * 
	 * @param mixed $data
	 */
	public function replace($data) {
		$path = new Path(
			self::replaceIn($this->getApp(), $data), 
			self::replaceIn($this->getController(), $data), 
			self::replaceIn($this->getAction(), $data), 
			self::replaceIn($this->getObject(), $data), 
			$this->getFormat(), 
			self::replaceIn($this->getParameters(), $data)
		);
		
		return $path;
	}
	
	/**
	 * 
	 * @return mixed
	 * @todo Implement
	 */
	public function extract(Path$from) {
		
	}
	
	private static function replaceIn($src, $data) {
		
		/*
		 * If we passed an array we will individually replace every src with their
		 * valid data.
		 */
		if (is_array($src)) {
			$copy = [];
			foreach ($src as $a => $b) { $copy[$a] = self::replaceIn($b, $data); } 
			return $copy;
		}
		
		if ($src instanceof Pattern) {
			$name = $src->getName();
			
			if (isset($data[$name])) { return $data[$name]; }
			else                     { throw new \spitfire\exceptions\PrivateException('Invalid parameter: ' . $name, 1705181741); }
		}
		
		if (is_scalar($src) || empty($src)) {
			return $src;
		}
		
		throw new \spitfire\exceptions\PrivateException('Path contains invalid objects - ' . $src, 1705181742);
	}
	
	/**
	 * 
	 * @todo Implement
	 */
	public static function fromArray() {
		
	}
	
}
