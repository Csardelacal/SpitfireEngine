<?php namespace spitfire\storage;

/*
 *
 * Copyright (C) 2023-2023 César de la Cal Bretschneider <cesar@magic3w.com>.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-13 01  USA
 *
 */

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
	
	/**
	 * Get access to the underlying FlySystem Filesystem.
	 */
	public function fly() : Flysystem
	{
		return $this->fs;
	}
}
