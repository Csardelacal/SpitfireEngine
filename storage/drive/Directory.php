<?php namespace spitfire\storage\drive;

use spitfire\core\CollectionInterface;
use spitfire\exceptions\FileNotFoundException;
use spitfire\exceptions\FilePermissionsException;
use spitfire\storage\objectStorage\ObjectDirectoryInterface;
use spitfire\storage\objectStorage\ObjectStorageInterface;
use function collect;

class Directory implements ObjectDirectoryInterface
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

	public function get($name): ObjectStorageInterface {
		if (is_dir($this->path . '/' . $name)) { 
			return new Directory(realpath($this->path . '/' . $name)); 
		}
		elseif(file_exists($this->path . '/' . $name)) { 
			return new File(realpath($this->path . '/' . $name)); 
		}
		
		throw new FileNotFoundException($this->path . '/' . $name . ' was not found', 1805301553);
	}
	
	public function make($name) : \spitfire\storage\objectStorage\BlobInterface {
		if (file_exists($this->path . '/' . $name)) {
			throw new FilePermissionsException('File ' . $name . ' already exists', 1805301554);
		}
		
		return new File(realpath($this->path . '/' . $name));
	}

	public function all(): CollectionInterface {
		$contents = scandir($this->path);
		
		return collect($contents)->each(function ($e) {
			if (is_dir($this->path . '/' . $e)) { return new Directory(realpath($this->path . '/' . $e)); }
			else                                { return new File(realpath($this->path . '/' . $e)); }
		});
	}

	public function getURI() : string {
		return 'file://' . $this->path;
	}

	public function getParent(): ObjectDirectoryInterface {
		return new Directory(dirname($this->path));
	}

}
