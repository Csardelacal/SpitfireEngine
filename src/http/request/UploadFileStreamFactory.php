<?php

declare(strict_types=1);
namespace spitfire\http\request;

use spitfire\io\stream\Stream;

class UploadFileStreamFactory
{
	
	public function create(string $path)
	{
		$writable = is_writable($path);
		return new Stream(fopen($path, $writable? 'r+' : 'r'), true, true, $writable);
	}
}
