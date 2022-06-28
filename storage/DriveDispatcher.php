<?php namespace spitfire\storage;

use League\Flysystem\DirectoryListing;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
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
	private $drives = [];
	
	/**
	 * Registers a drive with the dispatcher. Once your drive is registered, you
	 * can use it normally.
	 *
	 * @param FileSystem $drive
	 */
	public function register($scheme, $drive)
	{
		$this->drives[$scheme] = $drive;
		$this->fallback = new StorageFileSystem(new Filesystem(new LocalFilesystemAdapter(getcwd())));
	}
	
	/**
	 * Registers a drive with the dispatcher. Once your drive is registered, you
	 * can use it normally.
	 *
	 * @param string $drive
	 */
	public function unregister(string $drive)
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
	
	public function pathInfo(string $uri)
	{
		/**
		 * Never accept empty strings.
		 */
		assert(!empty($uri));
		
		$pieces = explode('://', $uri, 2);
		
		if (isset($pieces[1])) {
			return [
				$this->drive($pieces[0]),
				$pieces[1]
			];
		}
		else {
			return [
				$this->fallback,
				$pieces[0]
			];
		}
	}
	
	public function write(string $location, string $contents, array $config = []): void
	{
		list($drive, $path) = $this->pathInfo($location);
		$drive->write($path, $contents, $config);
	}
	
	public function writeStream(string $location, StreamInterface $contents, array $config = []): void
	{
		list($drive, $path) = $this->pathInfo($location);
		$drive->writeStream($path, $contents, $config);
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
	
	public function __call($name, $arguments)
	{
		list($drive, $path) = $this->pathInfo($arguments[0]);
		$arguments[0] = $path;
		return $drive->$name(...$arguments);
	}
}