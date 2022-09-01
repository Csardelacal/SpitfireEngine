<?php namespace spitfire\core\http\request\components;

use Psr\Http\Message\UploadedFileInterface;
use spitfire\io\UploadFile;

trait hasUploads
{
	/**
	 * The array tree of uploads, this is a mix of UploadInterfaces and arrays. The
	 * application can the use the array to manipulate the uploaded file.
	 *
	 * @see https://www.php-fig.org/psr/psr-7/meta/
	 * @var (array|UploadedFileInterface)[]
	 */
	private $uploads;
	
	/**
	 * Returns a tree of uploaded files. The tree contains arrays mixed with UploadedFileInterface
	 * objects that contain the actual metadata.
	 *
	 * @see https://www.php-fig.org/psr/psr-7/meta/
	 * @return (array|UploadedFileInterface)[]
	 */
	public function getUploadedFiles()
	{
		return $this->uploads;
	}
	
	/**
	 *
	 * @param (array|UploadedFileInterface)[] $uploadedFiles
	 */
	public function withUploadedFiles(array $uploadedFiles)
	{
		$copy = clone $this;
		$copy->uploads = $uploadedFiles;
		return $copy;
	}
	
	/**
	 *
	 * @todo Support nested file arrays.
	 */
	public static function filesFromGlobals()
	{
		$_return = [];
		
		foreach ($_FILES as $key => $upload) {
			
			/**
			 * We currently do not support uploads that are within nested arrays. This is a
			 * known limitation, but it should be fine for our use case.
			 *
			 * We do not assert this, since it is entirely possible that a user submits bad
			 * data during runtime.
			 */
			assume(!is_array($upload['name']), 'File array support is currently disabled');
			
			/**
			 * Add the uploaded file to the list of uploads.
			 */
			$_return[$key] = new UploadFile(
				$upload['tmp_name'],
				$upload['name'],
				$upload['type'],
				$upload['size'],
				$upload['error']
			);
		}
		
		return $_return;
	}
}
