<?php namespace spitfire\storage\drive;

use spitfire\exceptions\FilePermissionsException;

class Directory
{
	
	private $path;
	
	public function __construct($path) {
		$this->path = $path;
	}
	
	public function exists() {
		#If the path is not a directory but exists then this directory cannot be
		#created
		if (!is_dir($this->path) && file_exists($this->path)) {
			throw new FilePermissionsException('Directory ' . $this->path . ' is not a directory');
		}
		
		#If the directory is not being replaced by a file, then we can return the 
		#value that is_dir would usually return.
		return is_dir($this->path);
	}
	
	public function create() {
		#We run a recursive mkdir to create the directories needed to get to the 
		#path. If this feils, we'll throw an exception.
		if (!mkdir($this->path, umask(), true)) {
			throw new FilePermissionsException();
		}
		
		return true;
	}
	
	public function isWritable() {
		return is_writable($this->path);
	}
	
}
