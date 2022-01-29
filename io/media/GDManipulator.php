<?php namespace spitfire\io\media;

use Exception;
use spitfire\exceptions\PrivateException;
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

class GDManipulator implements MediaManipulatorInterface
{
	
	private $tmp;
	private $meta;
	private $compression = 8;
	
	public function blur(): MediaManipulatorInterface
	{
		imagefilter($this->img, IMG_FILTER_SELECTIVE_BLUR);
		return $this;
	}
	
	public function fit($x, $y): MediaManipulatorInterface
	{
		$wider = ($this->meta[0] / $x) > ($this->meta[1] / $y);
		
		if ($wider) {
			$ratio    = $this->meta[1] / $y;
			$offset_x = ($this->meta[0] - $x * $ratio) / 2;
			$offset_y = 0;
		}
		else {
			$ratio    = $this->meta[0] / $x;
			$offset_y = ($this->meta[1] - $y * $ratio) / 2;
			$offset_x = 0;
		}
		
		if ($offset_x == 0 && $offset_y == 0) {
			$x = min($this->meta[0], $x);
			$y = min($this->meta[1], $y);
		}
		
		$img = imagecreatetruecolor($x, $y);
		imagecolortransparent($img, imagecolorallocatealpha($img, 255, 255, 255, 127));
		imagealphablending($img, false);
		imagesavealpha($img, true);
		imagecopyresampled($img, $this->img, 0, 0, $offset_x, $offset_y, $x, $y, $this->meta[0]-2*$offset_x, $this->meta[1]-2*$offset_y);
		$this->img = $img;
		
		$this->meta[0] = $x;
		$this->meta[1] = $y;
		
		return $this;
	}
	
	public function grayscale(): MediaManipulatorInterface
	{
		imagefilter($this->img, IMG_FILTER_GRAYSCALE);
		return $this;
	}
	
	public function load(Blob $blob): MediaManipulatorInterface
	{
		
		if ($this->tmp) {
			unlink($this->tmp);
		}
		
		$this->tmp = '/tmp/' . rand();
		file_put_contents($this->tmp, $blob->read());
		
		
		$this->meta = getimagesize($this->tmp);
		
		if (!function_exists('imagecreatefrompng')) {
			throw new PrivateException("GD is not installed.", 1805301100);
		}
		
		switch ($this->meta[2]) {
			case IMAGETYPE_PNG: 
				$this->img = imagecreatefrompng($this->tmp);
				imagealphablending($this->img, false);
				imagesavealpha($this->img, true);
				break;
			/*
			 * If the image is a webp file, we use GD to manipulate it just like we 
			 * would handle any other image type. Since WEBP supports transparency,
			 * we will tell GD to handle transparency.
			 */
			case IMAGETYPE_WEBP:
				$this->img = imagecreatefromwebp($this->tmp);
				imagealphablending($this->img, false);
				imagesavealpha($this->img, true);
				break;
			case IMAGETYPE_JPEG:  
				$this->img = imagecreatefromjpeg($this->tmp);
				break;
			case IMAGETYPE_GIF: 
				$this->img = imagecreatefromgif($this->tmp);
				break;
			default:
				throw new PrivateException('Unsupported image type: ' . $this->meta[2]);
		}
		
		return $this;
	}
	
	public function quality($target = MediaManipulatorInterface::QUALITY_VERYHIGH): MediaManipulatorInterface
	{
		//TODO: Implement
		return $this;
	}
	
	public function at($x, $y)
	{
		return imagecolorat($this->img, $x, $y);
	}
	
	public function background($r, $g, $b, $alpha = 0): MediaManipulatorInterface
	{
		
		
		$img = imagecreatetruecolor($this->meta[0], $this->meta[1]);
		imagecolortransparent($img, imagecolorallocatealpha($img, 255, 255, 255, 127));
		imagealphablending($img, true);
		imagesavealpha($img, true);
		
		$bgcolor = imagecolorallocatealpha($img, $r, $g, $b, $alpha);
		imagefilledrectangle($img, 0, 0, $this->meta[0], $this->meta[1], $bgcolor);
		
		imagecopy($img, $this->img, 0, 0, 0, 0, $this->meta[0], $this->meta[1]);
		$this->img = $img;
		
		return $this;
	}
	
	public function scale($target, $side = MediaManipulatorInterface::WIDTH): MediaManipulatorInterface
	{
		
		
		if ($side === MediaManipulatorInterface::HEIGHT) {
			$height = $target;
			$width = $this->meta[0] * $target / $this->meta[1];
		}
		
		if ($side === MediaManipulatorInterface::WIDTH) {
			$width = $target;
			$height = $this->meta[1] * $target / $this->meta[0];
		}
		
		$img = imagecreatetruecolor($width, $height);
		imagecolortransparent($img, imagecolorallocatealpha($img, 0, 0, 0, 127));
		imagealphablending($img, false);
		imagesavealpha($img, true);
		imagecopyresampled($img, $this->img, 0, 0, 0, 0, $width, $height, $this->meta[0], $this->meta[1]);
		$this->img = $img;
		
		$this->meta[0] = $width;
		$this->meta[1] = $height;
		
		return $this;
	}
	
	public function store(Blob $location): Blob
	{
		
		if (!$location->isWritable()) {
			throw new PrivateException('Cannot write to target', 1805301104);
		}
		
		switch (pathinfo($location->uri(), PATHINFO_EXTENSION)) {
			case 'jpg':
			case 'jpeg':
				imagejpeg($this->img, $this->tmp, $this->compression * 10);
				break;
			/*
			 * Allows the system to manipulate webp files, an upcoming format pioneered
			 * by Google that allows to reduce the overhead of images being transferred
			 * significantly by reducing file size.
			 * 
			 * The format supports transparency.
			 * 
			 * Read more on: https://developers.google.com/speed/webp
			 */
			case 'webp':
				imagewebp($this->img, $this->tmp, $this->compression * 10);
				break;
			case 'png':
			default:
				imagepng($this->img, $this->tmp, $this->compression);
				
				try {
					PNGQuant::compress($this->tmp, $this->tmp); 
				}
				catch (Exception$e) {
/*If PNGQuant is not installed, we do nothing*/ 
				}
		}
		
		$location->write(file_get_contents($this->tmp));
		unlink($this->tmp);
		
		return $location;
	}
	
	public function supports(string $mime): bool
	{
		
		switch ($mime) {
			case 'image/jpeg':
			case 'image/jpg':
			case 'image/png':
			case 'image/gif':
			case 'image/webp':
				return true;
			default: 
				return false;
		}
	}
	
	public function poster(): MediaManipulatorInterface
	{
		return $this;
	}
	
	public function dimensions()
	{
		return array($this->meta[0], $this->meta[1]);
	}
}
