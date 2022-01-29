<?php namespace spitfire\core\app;

use spitfire\collection\Collection;

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
 * The app assets interface enforces a pattern in which applications must be able
 * to locate an asset for their operation, and they must be able to list all the
 * assets they need to operate.
 * 
 * This way, Spitfire will be able to copy the assets to the deployment location
 * so they are properly compressed and available from the webroot.
 * 
 * This component is only used during initialization of the app, once the assets
 * are deployed, spitfire will no longer need this asset. It is therefore considered
 * good practice to instantiate this inside the app's assets() method.
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
interface AppAssetsInterface
{
	
	
	/**
	 * This must provide a full list of relative paths to the assets that the app
	 * whishes to use once deployed. Assets are not allowed to be any run-time
	 * executable files (like php files), they will be processed by Spitfire.
	 * 
	 * @return Collection
	 */
	public function all() : Collection;
	
	/**
	 * Maps an asset to a file on disk. Passing the asset name as an argument should
	 * return a string with the location on disk of the asset.
	 * 
	 * Please note that an asset name may look anything like "js/my.js" (including
	 * relative paths and file extensions).
	 * 
	 * @param string $asset
	 * @return string
	 */
	public function map(string$asset) : string;
}
