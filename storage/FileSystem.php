<?php namespace spitfire\storage;

use League\Flysystem\Filesystem as Flysystem;
use Psr\Http\Message\StreamInterface;
use spitfire\io\stream\Stream;

/**
 *
 * @mixin Flysystem
 */
class FileSystem
{
	
	/**
	 *
	 * @var Flysystem
	 */
	private $fs;
	
	public function __construct(Flysystem $fs)
	{
		$this->fs = $fs;
	}
	
	public function writeStream(string $location, StreamInterface $contents, array $config = []): StreamInterface
	{
		$handle = $contents->detach();
		$this->fs->writeStream($location, $handle, $config);
		return new Stream($handle, $contents->isSeekable(), $contents->isReadable(), $contents->isWritable());
	}
	
	public function readStream(string $location): StreamInterface
	{
		$handle = $this->fs->readStream($location);
		$meta   = stream_get_meta_data($handle);
		
		$mode = trim($meta['mode'], 'bt');
		
		return new Stream(
			$handle,
			$meta['seekable'],
			in_array($mode, Stream::READABLE) !== false,
			in_array($mode, Stream::WRITABLE) !== false
		);
	}
	
	public function __call($name, $arguments)
	{
		return $this->fs->$name(...$arguments);
	}
}
