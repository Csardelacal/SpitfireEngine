<?php namespace spitfire\storage;

use League\Flysystem\Filesystem as Flysystem;
use Psr\Http\Message\StreamInterface;
use spitfire\io\stream\Stream;
use spitfire\utils\Mixin;

/**
 *
 * @mixin Flysystem
 */
class FileSystem
{
	
	use Mixin;
	
	/**
	 *
	 * @var Flysystem
	 */
	private $fs;
	
	public function __construct(Flysystem $fs)
	{
		$this->fs = $fs;
		$this->mixin($fs);
	}
	
	/**
	 * 
	 * @param string $location
	 * @param StreamInterface $contents
	 * @param array{} $config
	 */
	public function writeStream(string $location, StreamInterface $contents, array $config = []): StreamInterface
	{
		$handle = $contents->detach();
		$this->fs->writeStream($location, $handle, $config);
		return Stream::fromHandle($handle);
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
}
