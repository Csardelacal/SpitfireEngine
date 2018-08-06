<?php namespace spitfire\storage\objectStorage;

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
 * The virtual drive class. This class is the "entry point" to any storage interface
 * and access the data within the driver.
 * 
 * Drives can be registered with the dispatcher to allow applications to retrieve
 * data from locations they have programmed in. For example, if you use an external
 * storage provider like s3, you could register s3:// as a scheme to store your 
 * data to and point the directory.uploads environment variable to your S3 server.
 * 
 * The data will be stored and you'll receive a URI like s3://xxxx/yyyy/zzzz that
 * points you to the file.
 * 
 * If, in the future you wished to start storing your data somewhere else, you can 
 * just point the directory.uploads variable somewhere else completely.
 * 
 * This is somewhat based on the way other "Virtual drive" libraries work, except 
 * that it focuses on providing text based URIs for convenient storage, which
 * allow your app to work with the data with absolute transparency.
 */
class Drive implements DriveInterface
{
	
	/**
	 * The URI scheme used to identify the drive. This works much like URI schemes
	 * or the drive letters on windows machines, except that it does not restrict
	 * the amount of characters it can use.
	 *
	 * @var string
	 */
	private $scheme;
	
	/**
	 * The entry point to this drive. This is the root mount of the virtual filesystem
	 * and should be able to retrieve the data appropriately. This  may contain the
	 * login settings for cloud based storage.
	 *
	 * @var Directory
	 */
	private $root;
	
	/**
	 * Create a new virtual drive to bind a physical location to a virtual scheme.
	 * Your application can therefore create a virtual drive like uploads:// to
	 * link to your upload directory.
	 * 
	 * This makes the task of system administration and, for example, moving a directory
	 * to a new location faster, since you just need to update the root of the
	 * virtual drive.
	 * 
	 * @param string $scheme
	 * @param \spitfire\storage\objectStorage\Directory $root
	 */
	public function __construct($scheme, Directory$root) {
		$this->scheme = $scheme;
		$this->root = $root;
	}
	
	/**
	 * Retrieve the scheme the virtual drive, when provided the scheme, the drive
	 * manager will link all the requests for this scheme to the drive.
	 * 
	 * @return string
	 */
	public function scheme() {
		return $this->scheme;
	}
	
	/**
	 * Get the root location for the virtual drive.
	 * 
	 * @return \spitfire\storage\objectStorage\Directory
	 */
	public function root(): Directory {
		return $this->root;
	}
	
	/**
	 * Retrieves a file or directory (node) from a given string. By default, URIs
	 * are extracted by splitting it by forward slashes.
	 * 
	 * @param string $name
	 * @return \spitfire\storage\objectStorage\Node
	 */
	public function get($name) : NodeInterface {
		$pieces = explode('/', $name);
		$location = $this->root;
		
		foreach ($pieces as $piece) {
			$location = $location->open($piece);
		}
		
		return  $location;
	}
	
	public function getParent(): DirectoryInterface {
		throw new \BadMethodCallException('Called getParent on drive', 1808061411);
	}
	
	public function getURI(): string {
		return $this->scheme . '://';
	}

}
