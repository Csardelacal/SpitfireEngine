<?php namespace spitfire\io\media;

use spitfire\exceptions\ApplicationException;
use spitfire\io\media\exceptions\EncoderUnavailableException;
use spitfire\storage\objectStorage\Blob;

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

class MediaDispatcher
{
	
	private $associations = [];
	
	/**
	 * 
	 * @throws ApplicationException
	 * 
	 * @param string $mime
	 * @param MediaManipulatorInterface $manipulator
	 * @return void
	 */
	public function register($mime, MediaManipulatorInterface$manipulator)
	{
		
		if (!$manipulator->supports($mime)) {
			throw new ApplicationException('Invalid association', 1805301139);
		}
		
		$this->associations[$mime] = $manipulator;
	}
	
	/**
	 * 
	 * @throws EncoderUnavailableException
	 * 
	 * @param Blob $object
	 * @return MediaManipulatorInterface
	 */
	public function load(\spitfire\storage\objectStorage\Blob$object) : MediaManipulatorInterface
	{
		if (isset($this->associations[$object->mime()])) {
			$copy = clone $this->associations[$object->mime()];
			$copy->load($object);
			return $copy;
		}
		
		throw new EncoderUnavailableException(
			sprintf('No manipulator found for %s(%s)', $object->uri(), $object->mime()), 
			1805301140
		);
	}
}
