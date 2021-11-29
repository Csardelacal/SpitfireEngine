<?php namespace spitfire\storage\objectStorage;

use spitfire\exceptions\ApplicationException;
use spitfire\exceptions\FileNotFoundException;
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
	public function register($scheme, $drive) {
		$this->drives[$scheme] = $drive;
	}
	
	/**
	 * Registers a drive with the dispatcher. Once your drive is registered, you
	 * can use it normally.
	 * 
	 * @param \spitfire\storage\drive\MountPoint|string $drive
	 */
	public function unregister($drive) {
		if ($drive instanceof DriveInterface) {
			$drive = $drive->scheme();
		}
		
		unset($this->drives[trim($drive, ':/')]);
	}
	
	/**
	 * Retrieves a file or directory (node) from a given string. By default, URIs
	 * are extracted by splitting it by forward slashes.
	 * 
	 * @deprecated since version 20200520
	 * @param string $location
	 * @return \spitfire\storage\objectStorage\Blob
	 */
	public function get($location) : NodeInterface {
		$pieces = explode('://', $location, 2);
		
		if(!isset($pieces[1])) {
			throw new PrivateException('Invalid URI provided', 1805301529);
		}
		
		list($scheme, $path) = $pieces;
		
		if(!isset($this->drives[$scheme])) {
			throw new PrivateException('Scheme ' . $scheme . ' cannot be handled', 1805301529);
		}
		
		$mount  = $this->drives[$scheme];
		
		/*
		 * Now that the mount has been located, recurse over the pieces to find
		 * the resource the user is looking for.
		 */
		$pieces = array_filter(explode('/', $path));
		$resource = $mount;
		
		foreach ($pieces as $piece) {
			
			if (!$resource instanceof DirectoryInterface) {
				throw new FileNotFoundException('Trying to recurse into a file', 1808111144);
			}
			
			$resource = $resource->open($piece);
		}
		
		return $resource;
	}
	
	/**
	 * 
	 * @param type $location
	 * @return \spitfire\storage\objectStorage\Blob
	 * @throws PrivateException
	 */
	public function retrieve($location) {
		$args = array_reduce(func_get_args(), function ($c, $e) {
			if (empty($c)) { return $e; }
			return rtrim($c, '\/') . DIRECTORY_SEPARATOR . ltrim($e, '\/');
		}, null);
		
		$pieces = explode('://', $args, 2);
		
		if(!isset($pieces[1])) {
			throw new ApplicationException('Invalid URI provided', 1805301529);
		}
		
		list($scheme, $path) = $pieces;
		
		if(!isset($this->drives[$scheme])) {
			throw new ApplicationException('Scheme ' . $scheme . ' cannot be handled', 1805301529);
		}
		
		$mount  = $this->drives[$scheme];
		
		return new Blob($scheme, $mount, $path);
	}
	
}
