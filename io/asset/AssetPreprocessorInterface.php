<?php namespace spitfire\io\asset;

/* 
 * The MIT License
 *
 * Copyright 2020 César de la Cal Bretschneider <cesar@magic3w.com>.
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

/**
 * An asset preprocessor will be invoked whenever Spitfire is constructing 
 * assets for the application. Asset preprocessors can be added from the environments
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
interface AssetPreprocessorInterface
{
	
	/**
	 * Constructs an asset and writes it to the output file. Spitfire will programmatically
	 * decide the output file.
	 * 
	 * @param string $input
	 * @param string $output
	 */
	public function build($input, $output);
	
	/**
	 * This endpoint allows the asset preprocessor to report itself as unavailable.
	 * If this happens, the preprocessor will be ignored.
	 */
	public function available();
	
	public function extension($original);
}