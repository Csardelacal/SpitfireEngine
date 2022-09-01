<?php namespace spitfire\core\http\request\components;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use spitfire\io\UploadFile;

/*
 * Copyright (C) 2021 César de la Cal Bretschneider <cesar@magic3w.com>.
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
 * MA 02110-1301  USA
 */
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
	public function withUploadedFiles(array $uploadedFiles) : ServerRequestInterface
	{
		#For this trait to work properly it needs to be applied to a class that implements
		#the request interface.
		assert($this instanceof ServerRequestInterface);
		
		$copy = clone $this;
		$copy->uploads = $uploadedFiles;
		return $copy;
	}
	
	/**
	 *
	 * @todo Support nested file arrays.
	 * @return UploadedFileInterface[]
	 */
	public static function filesFromGlobals() : array
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
