<?php namespace spitfire\core\app\support\directors;

use spitfire\cli\arguments\CLIParameters;
use spitfire\core\app\support\manifest\ComposerReader;
use spitfire\mvc\Director;

/**
 * 
 */
class ManifestCacheBuildDirector extends Director
{
	
	public function parameters() : array
	{
		return [
			'v' => '--verbose',
			'--verbose' => [
				'type' => 'bool',
				'description' => 'Provide additional debugging output'
			]
		];
	}
	
	public function exec(array $parameters, CLIParameters $arguments) : int
	{
		$reader = new ComposerReader();
		$manifest = $reader->read(spitfire()->locations()->root('composer.json'));
		
		$export = var_export($manifest, true);
		$cache = spitfire()->locations()->root('bin/apps.php');
		
		file_put_contents($cache, sprintf('return %s', $export));
		
		return 0;
	}
}
