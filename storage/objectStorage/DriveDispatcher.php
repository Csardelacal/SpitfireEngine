<?php namespace spitfire\storage\objectStorage;

use spitfire\exceptions\PrivateException;

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

class DriveDispatcher
{
	
	private $drives = [];
	
	/**
	 * Registers a drive with the dispatcher. Once your drive is registered, you
	 * can use it normally.
	 * 
	 * @param DriveInterface $drive
	 */
	public function register(DriveInterface$drive) {
		$this->drives[trim($drive->scheme(), ':/')] = $drive;
	}
	
	/**
	 * Mount a location as a virtual drive. Please note that this uses the standard
	 * drive mechanism.
	 * 
	 * @param string $scheme
	 * @param DirectoryInterface $location
	 * @return DriveInterface
	 */
	public function mount($scheme, DirectoryInterface$location) {
		$sc = trim($scheme, ':/');
		$drive = new Drive($sc, $location);
		
		return $this->register($drive);
	}
	
	public function get($location) : NodeInterface {
		$pieces = explode('://', $location, 2);
		
		if(!isset($pieces[1])) {
			throw new PrivateException('Invalid URI provided', 1805301529);
		}
		
		list($scheme, $path) = $pieces;
		
		return $this->drives[$scheme]->get($path);
	}
	
}
