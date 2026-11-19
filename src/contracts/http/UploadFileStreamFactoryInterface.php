<?php

declare(strict_types=1);
namespace spitfire\contracts\http;

use Psr\Http\Message\StreamInterface;

/**
 * Factory interface for creating streams from uploaded files.
 * 
 * Implementations of this interface should provide a method to create a stream
 * from a given file path.
 */
interface UploadFileStreamFactoryInterface
{
	public function create(string $path) : StreamInterface;
}
