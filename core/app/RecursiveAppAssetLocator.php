<?php namespace spitfire\core\app;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use spitfire\collection\Collection;
use spitfire\utils\Strings;
use function collect;

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
 * The recursive app asset locator does exactly this. It iterates over a folder 
 * that the application specified and list all the assets.
 * 
 * Please note that this does not assume that any of your files are not assets.
 * Providing an asset directory to this locator will return a list of absolutely
 * all the files in the directory and it's subdirectories.
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class RecursiveAppAssetLocator implements AppAssetsInterface
{
	
	private $basedir;
	
	public function __construct($basedir)
	{
		$this->basedir = rtrim($basedir, '\/') . DIRECTORY_SEPARATOR;
	}
	
	/**
	 * Returns the entire list of the assets needed for the application. Spitfire
	 * will then place these assets wherever they're needed.
	 * 
	 * @return Collection
	 */
	public function all(): Collection
	{
		$_ret = collect();
		
		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->basedir, FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS));
		
		foreach ($iterator as $asset) {
			$_ret->push(Strings::startsWith($asset, $this->basedir)? substr($asset, strlen($this->basedir)) : $asset);
		}
		
		return $_ret;
	}
	
	/**
	 * Returns the full path to the asset. Please note that there is a difference
	 * between the asset and the path it's at.
	 * 
	 * While the concepts are similar, an asset is something relative to the asset
	 * directory of the application, something like 'my/script.js' which allows the
	 * developer of the application to reference it within his code with
	 * 
	 * asset($this->app, 'my/script.js'); //If the app is explicitly mentioned
	 * 
	 * The path is the location on disk it's located at, like /var/www/mydomain.com/public/bin/apps/myapp/assets/my/script.js
	 * 
	 * I don't think anyone would want to put this into their code-base more than once.
	 * Additionally, spitfire will move the file to
	 * /var/www/mydomain.com/public/assets/deploy/2020/04/21/myappsurl/my/script.js
	 * when building it, which is another, massive mouthful to write and manage.
	 * 
	 * @param string $asset
	 * @return string
	 */
	public function map(string $asset): string
	{
		return $this->basedir . $asset;
	}
}
