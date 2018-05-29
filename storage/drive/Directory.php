<?php namespace spitfire\storage\drive;

use spitfire\core\CollectionInterface;
use spitfire\exceptions\FilePermissionsException;
use spitfire\storage\objectStorage\BlobInterface;
use spitfire\storage\objectStorage\ObjectStorageInterface;

class Directory implements ObjectStorageInterface
{
	
	private $path;
	
	public function __construct($path) {
		$this->path = $path;
	}
	
	public function create() {
		#We run a recursive mkdir to create the directories needed to get to the 
		#path. If this feils, we'll throw an exception.
		if (!mkdir($this->path, umask(), true)) {
			throw new FilePermissionsException();
		}
		
		return true;
	}
	
	public function exists() : bool {
		#If the path is not a directory but exists then this directory cannot be
		#created
		if (!is_dir($this->path) && file_exists($this->path)) {
			throw new FilePermissionsException('Directory ' . $this->path . ' is not a directory');
		}
		
		#If the directory is not being replaced by a file, then we can return the 
		#value that is_dir would usually return.
		return is_dir($this->path);
	}
	
	public function isWritable() : bool {
		return is_writable($this->path);
	}

	public function get($name): BlobInterface {
		if (is_dir($this->path . '/' . $name)) { throw new FileNotFoundException('Object is a file'); }
		else                                   { return new File(realpath($this->path . '/' . $name)); }
	}

	public function list(): CollectionInterface {
		$contents = scandir($this->path);
		
		return collect($contents)->each(function ($e) {
			if (is_dir($this->path . '/' . $e)) { return new Directory(realpath($this->path . '/' . $e)); }
			else                                { return new File(realpath($this->path . '/' . $e)); }
		});
	}

	public function getURI() {
		return 'file://' . $this->path;
	}

}
