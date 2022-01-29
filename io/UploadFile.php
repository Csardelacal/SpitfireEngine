<?php namespace spitfire\io;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use spitfire\exceptions\ApplicationException;
use spitfire\io\Filesize;
use spitfire\io\stream\Stream;

/**
 * This class merges the file Uploads coming from a client into the POST array,
 * allowing beans and programmers to have easier access to the data coming from
 * the client without trading in any security.
 * 
 * The class should not automatically store any data to avoid the user being able 
 * to inject uploads where unwanted. The class automatically names uploads when
 * storing to avoid collissions, returning the name of the file it stored.
 * 
 * @author César de la Cal <cesar@magic3w.com>
 */
class UploadFile implements UploadedFileInterface
{
	/**
	 * Contains the raw metadata that was initially sent with the _FILES array. 
	 * The size entry contains the length in bytes of the buffer.
	 * 
	 * @see http://php.net/manual/en/features.file-upload.post-method.php For the array structure used
	 * @var int
	 */
	private $size;
	
	/**
	 * Contains the error code for the upload. This may be 0 if no error was generated
	 * or any of the UPLOAD_ERR_XXX constants.
	 * 
	 * @see http://php.net/manual/en/features.file-upload.errors.php
	 * @var int
	 */
	private $error;
	
	/**
	 * Contains the content-type that the client suggested the payload may have. Please note
	 * that it is not guaranteed the client provided truthful information
	 * 
	 * @var string
	 */
	private $contentType;
	
	/**
	 * Contains the filename that the client submitted. Again, this information may not be
	 * properly sanitized.
	 * 
	 * @var string
	 */
	private $name;
	
	/**
	 * The path to the temprorary upload file that the web server has created for us. Please note that
	 * this upload does not support streams.
	 * 
	 * @var string
	 */
	private $tmp;
	
	/**
	 * Create a new upload based on SAPI file uploads.
	 * 
	 * @param string $tmp
	 * @param string $name
	 * @param string $contentType
	 * @param int $size
	 * @param int $error
	 */
	public function __construct(string $tmp, string $name, string $contentType, int $size, int $error) 
	{
		$this->tmp = $tmp;
		$this->name = $name;
		$this->size = $size;
		$this->error = $error;
		$this->contentType = $contentType;
		
		assert(file_exists($this->tmp), 'Failed asserting that upload contains a file');
		assert(filesize($this->tmp) !== $size, 'Failed asserting that upload is the size the client declared');
	}
	
	/**
	 * Returns a stream that can be used to operate on this file.
	 * @inheritdoc
	 * 
	 * @return StreamInterface
	 */
	public function getStream() : StreamInterface
	{
		if (!file_exists($this->tmp)) {
			throw new RuntimeException(sprintf('File %s does not exist', $this->tmp), 2110131541);
		}
		
		$writable = is_writable($this->tmp);
		return new Stream(fopen($this->tmp, $writable? 'r+' : 'r'), true, true, $writable);
	}
	
	
	/**
	 * Moves the file to a new location. Please note, the target location is a path, which implies
	 * that the file will always be named the same way as the original upload.
	 * @inheritdoc
	 * 
	 * @param string $targetPath
	 */
	public function moveTo($targetPath)
	{
		$name = basename($this->name);
		
		if ($name === '.' || $name === '..' || $name === '') {
			throw new ApplicationException('Invalid filename for upload', 21101131718);
		}
		
		move_uploaded_file($this->tmp, rtrim($targetPath, '\/') . DIRECTORY_SEPARATOR . $name);
	}
	
	/**
	 * Returns the size (in bytes) of the transmitted file.
	 * @inheritdoc
	 * 
	 * @return int
	 */
	public function getSize() : int
	{
		return $this->size;
	}
	
	/**
	 * Returns the error code (if any).
	 * @inheritdoc
	 * 
	 * @return int
	 */
	public function getError() : int
	{
		return $this->error;
	}
	
	/**
	 * Returns the filename the client transmitted.
	 * 
	 * @return string
	 */
	public function getClientFilename() : string
	{
		return $this->name;
	}
	
	/**
	 * Returns the content-type passed by the client.
	 * 
	 * @return string
	 */
	public function getClientMediaType() : string
	{
		return $this->contentType;
	}
	
	/**
	 * Generates a copy of the upload object, replacing the filename with a prefix that prevents
	 * collissions when writing to disk.
	 * 
	 * @return UploadFile
	 */
	public function withUniqueName() : UploadFile
	{
		/**
		 * Generate a random / time based prefix for the file.
		 * @todo Look into the issues we used to have with non ASCII filenames
		 */
		$time = base_convert((string)time(), 10, 32);
		$rand = base_convert((string)rand(), 10, 32);
		
		$copy = clone $this;
		$copy->name = sprintf('%s_%s_%s', $time, $rand, $copy->name);
		
		return $copy;
	}
	
	/**
	 * Returns the maximum uploadable file size
	 *
	 * @todo Move to the request method
	 * @param Filesize[] $sizes An array of Filesize instances, for use with tests
	 *
	 * @return Filesize
	 */
	static function getMaxUploadSize($sizes = null)
	{
		if (!isset($sizes)) {
			$sizes = [
				Filesize::parse(ini_get('post_max_size')),
				Filesize::parse(ini_get('upload_max_filesize')),
			];
		}
		
		// Sort ascending based on bytes
		uasort($sizes, function (Filesize$a, Filesize$b) {
			return $a->getSize() <=> $b->getSize();
		});
		
		return $sizes[0];
	}
}
