<?php namespace spitfire\utils;

use spitfire\exceptions\PrivateException;
use spitfire\mvc\Director;
use function console;

/* 
 * The MIT License
 *
 * Copyright 2019 César de la Cal Bretschneider <cesar@magic3w.com>.
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

class BuildDirector extends Director
{
	
	public function dependencies() {
		
		$deps = basedir() . '/bin/settings/dependencies.php';
		
		if (!file_exists($deps)) {
			throw new PrivateException('Dependencies file is not available, expeced at ' . $deps, 1909101401);
		}
		
		$result = 0;
		
		$depend = function ($cb, $name) use (&$result) {
			if ($cb()) { console()->success($name)->ln(); }
			else { console()->error($name)->ln(); $result = 1; }
		};
		
		include $deps;
		
		return $result;
	}
	
	/**
	 * Recursively builds the assets for an application. Please note that your 
	 * application needs to be loaded for this to work.
	 */
	public function assets($namespace = '') {
		
		
		if (!\spitfire\core\Environment::get('assets.directory.deploy')) {
			throw new PrivateException('Build directory is not defined, run php console spitfire.utils.Environment set assets.directory.deploy /assets/deploy/' . date('Y-m-d-H-i') . '/');
		}
		
		/**
		 * This needs to be moved towards the environments so a user can configure
		 * which tool they wish to use.
		 */
		$preprocessors = collect(\spitfire\core\Environment::get()->subtree('assets.preprocessors.'))
			->each(function ($e) { return new $e; });
		
		/*@var $app \spitfire\App*/
		$app = spitfire()->getApp($namespace);
		
		$assets = $app->assets();
		$all = $assets->all();

		$dir = spitfire()->getCWD() . rtrim(\spitfire\core\Environment::get('assets.directory.deploy') . $app->getMapping()->getURISpace(), '\/') . DIRECTORY_SEPARATOR;
		console()->info('Output directory: ' . $dir)->ln();
		
		/**
		 * Loop over the assets for the application. Each asset will then be built 
		 * into a deployment ready asset.
		 */
		foreach ($all as $asset) {
			$path = $assets->map($asset);
			$preprocessor = $preprocessors[pathinfo($path, PATHINFO_EXTENSION)]?? new \spitfire\io\asset\NoopPreprocessor();
			$output = $dir . pathinfo($asset, PATHINFO_DIRNAME) . '/' . pathinfo($asset, PATHINFO_FILENAME) . '.' . $preprocessor->extension(pathinfo($path, PATHINFO_EXTENSION));

			if (!file_exists(pathinfo($output, PATHINFO_DIRNAME))) {
				mkdir(pathinfo($output, PATHINFO_DIRNAME), 0777, true);
			}

			$preprocessor->build($path, $output);
			console()->success($asset . ' --> ' . $output)->ln();
		}
	}
	
	public function unit() {
		$phpunit = exec('which phpunit');
		$config  = SPITFIRE_BASEDIR . '/phpunit.xml';
		$tests   = SPITFIRE_BASEDIR . '/tests/';
		
		if (!$phpunit) {
			throw new PrivateException('PHPUnit is not installed or could not be found');
		}
		
		console()->info(sprintf('Using PHPUnit executable: %s', $phpunit))->ln();
		console()->info(sprintf('Test directory: %s', $tests))->ln();
		
		system(sprintf('%s --configuration %s %s', $phpunit, $config, $tests),  $return);
		return $return;
	}
	
}
