<?php namespace spitfire\core\router;

use spitfire\core\Collection;
use spitfire\core\Path;
use spitfire\exceptions\PrivateException;

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
	 * Creates a path from the given parameters (as a Parameters object or array),
	 * including the unparsed data from a Parameter object (in the event it's 
	 * provided.
	 * 
	 * @param mixed|Parameters $data
	 */
	public function replace($data) {
		
		/*
		 * Parameter objects are treated in a special manner. We can use them to 
		 * extract the additional (so called 'unparsed') parameters
		 */
		if ($data instanceof Parameters) {
			$data = $data->getParameters();
			$add  = $data->getUnparsed();
		}
		/*
		 * Arrays have no additional data so we do not need to splice them.
		 */
		else {
			$add  = Array();
		}
		
		/*
		 * Construct the path that contains the replaced parameters and therefore 
		 * can be used to construct the path that can be used to handle a request
		 * with controller, action and object.
		 */
		$path = new Path(
			self::replaceIn($this->getApp(), $data), 
			self::replaceIn($this->getController(), $data), 
			self::replaceIn($this->getAction(), $data), 
			array_merge(self::replaceIn($this->getObject(), $data), $add), 
			$this->getFormat(), 
			self::replaceIn($this->getParameters(), $data)
		);
		
		return $path;
	}
	
	/**
	 * 
	 * @return mixed
	 */
	public function extract(Path$from) {
		/*
		 * Since we have to loop over several elements we use a function that we 
		 * then can call anonymously.
		 * 
		 * The lenient parameter indicates that the source array can be larger 
		 * than the pattern array and provide overflow data.
		 */
		$fn = function($a, $b, $lenient = false) {
			$_ret = [];
			
			/*
			 * First we check whether the patterns are compatible in the first place
			 * by verifying that the length of the arrays is equal.
			 */
			if ( count($a) > count($b) || (!$lenient && count($a) !== count($b)) ) {
				throw new PrivateException('Array too short', 1705212217); 
			}
			
			for($i = 0, $c = count($a); $i < $c; $i++) {
				if ($a[$i] instanceof Pattern && $a[$i]->getName()) {
					$_ret[$a[$i]->getName()] = $b[$i];
				}
			}
			
			return $_ret;
		};
		
		$p = new Parameters();
		$p->addParameters($fn([$this->getApp()],        [$from->getApp()]));
		$p->addParameters($fn($this->getController(),   $from->getController()));
		$p->addParameters($fn([$this->getAction()],     [$from->getAction()]));
		$p->addParameters($fn($this->getObject(),       $from->getObject(), true));
		$p->addParameters($fn([$this->getParameters()], [$from->getParameters()]));
		$p->setUnparsed(array_slice($from->getObject(), count($this->getObject())));
		
		return $p;
	}
	
	public function getPatterns() {
		#Extract the patterns
		$patterns = new Collection(array_merge(
			[$this->getApp()],
			$this->getController(),
			[$this->getAction()],
			$this->getObject(),
			$this->getParameters()
		));
		
		return $patterns->filter(function ($e) { return $e instanceof Pattern; });
	}
	
	private static function replaceIn($src, $data) {
		
		/*
		 * If we passed an array we will individually replace every src with their
		 * valid data.
		 */
		if (is_array($src)) {
			return array_map(function($e) use ($data) { return self::replaceIn($e, $data); }, $src);
		}
		
		if ($src instanceof Pattern) {
			$name = $src->getName();
			
			if (!$name) { return $src->getPattern()[0]; }
			else        { return current($src->test($data[$name])); }
		}
		
		if (is_scalar($src) || empty($src)) {
			return $src;
		}
		
		throw new PrivateException('Path contains invalid objects - ' . $src, 1705181742);
	}
	
	/**
	 * 
	 * @todo Implement
	 */
	public static function fromArray() {
		
	}
	
}
