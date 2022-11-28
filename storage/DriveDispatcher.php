<?php namespace spitfire\storage;

use League\Flysystem\DirectoryListing;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\StorageAttributes;
use Psr\Http\Message\StreamInterface;
use spitfire\exceptions\ApplicationException;
use spitfire\storage\FileSystem as StorageFileSystem;

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
 *
 * @mixin StorageFileSystem
 */
class DriveDispatcher
{
	
	/**
	 *
	 * @var StorageFileSystem
	 */
	private $fallback;
	
	/**
	 * 
	 * @var array<string,StorageFileSystem>
	 */
	private $drives = [];
	
	public function __construct()
	{
		$this->fallback = new StorageFileSystem(new Filesystem(new LocalFilesystemAdapter(
			spitfire()->locations()->storage()
		)));
	}
	
	/**
	 * Registers a drive with the dispatcher. Once your drive is registered, you
	 * can use it normally.
	 *
	 * @param string $scheme
	 * @param FileSystem $drive
	 * @return void
	 */
	public function register($scheme, $drive) : void
	{
		$this->drives[$scheme] = $drive;
	}
	
	/**
	 * Registers a drive with the dispatcher. Once your drive is registered, you
	 * can use it normally.
	 *
	 * @param string $drive
	 * @return void
	 */
	public function unregister(string $drive) : void
	{
		unset($this->drives[trim($drive, ':/')]);
	}
	
	/**
	 * Retrieves a file or directory (node) from a given string. By default, URIs
	 * are extracted by splitting it by forward slashes.
	 *
	 * @param string $drive
	 * @return StorageFileSystem
	 */
	public function drive($drive) : StorageFileSystem
	{
		$scheme = trim($drive, ':/');
		
		if (!isset($this->drives[$scheme])) {
			throw new ApplicationException('Scheme ' . $scheme . ' cannot be handled', 1805301529);
		}
		
		return $this->drives[$scheme];
	}
	
	/**
	 * 
	 * @return array{0:StorageFileSystem,1:string}
	 */
	public function pathInfo(string $uri) : array
	{
		/**
		 * Never accept empty strings.
		 */
		assert(!empty($uri));
		
		$pieces = explode('://', $uri, 2);
		
		if (isset($pieces[1])) {
			$_return = [
				$this->drive($pieces[0]),
				$pieces[1]
			];
		}
		else {
			$_return = [
				$this->fallback,
				$pieces[0]
			];
		}
		
		assert($_return[0] instanceof StorageFileSystem);
		return $_return;
	}
	
	/**
	 * 
	 * @param string $location
	 * @param string $contents
	 * @param array{} $config
	 * @return void
	 */
	public function write(string $location, string $contents, array $config = []): void
	{
		list($drive, $path) = $this->pathInfo($location);
		$drive->write($path, $contents, $config);
	}
	
	/**
	 * 
	 * @param string $location
	 * @param StreamInterface $contents
	 * @param array{} $config
	 * @return StreamInterface
	 */
	public function writeStream(string $location, StreamInterface $contents, array $config = []): StreamInterface
	{
		list($drive, $path) = $this->pathInfo($location);
		return $drive->writeStream($path, $contents, $config);
	}
	
	public function read(string $location): string
	{
		list($drive, $path) = $this->pathInfo($location);
		return $drive->read($path);
	}
	
	public function readStream(string $location) : StreamInterface
	{
		list($drive, $path) = $this->pathInfo($location);
		return $drive->readStream($path);
	}
	
	public function delete(string $location): void
	{
		list($drive, $path) = $this->pathInfo($location);
		$drive->delete($path);
	}
	
	public function deleteDirectory(string $location): void
	{
		list($drive, $path) = $this->pathInfo($location);
		$drive->deleteDirectory($path);
	}
	
	/**
	 * 
	 * @return DirectoryListing<StorageAttributes>
	 */
	public function listContents(string $location, bool $deep = Filesystem::LIST_SHALLOW): DirectoryListing
	{
		list($drive, $path) = $this->pathInfo($location);
		return $drive->listContents($path, $deep);
	}
	
	public function fileExists(string $location): bool
	{
		list($drive, $path) = $this->pathInfo($location);
		return $drive->fileExists($path);
	}
	
	public function directoryExists(string $location): bool
	{
		list($drive, $path) = $this->pathInfo($location);
		return $drive->directoryExists($path);
	}
	
	/**
	 * 
	 * @param string $name
	 * @param mixed[] $arguments
	 * @return mixed
	 */
	public function __call(string $name, array $arguments)
	{
		list($drive, $path) = $this->pathInfo($arguments[0]);
		$arguments[0] = $path;
		return $drive->$name(...$arguments);
	}
}
